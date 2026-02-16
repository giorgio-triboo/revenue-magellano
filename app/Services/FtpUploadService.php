<?php

namespace App\Services;

use App\Models\FileUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FtpUploadService
{
    protected $connection;
    protected $config;

    public function __construct()
    {
        $this->config = [
            'host' => config('ftp.host'),
            'username' => config('ftp.username'),
            'password' => config('ftp.password'),
            'remote_path' => config('ftp.remote_path', '/'),
            'port' => config('ftp.port', 21),
        ];

        // Log della configurazione (mascherando la password)
        Log::channel('sftp')->debug('FtpUploadService: Configurazione inizializzata', [
            'host' => $this->config['host'],
            'username' => $this->config['username'],
            'port' => $this->config['port'],
            'remote_path' => $this->config['remote_path'],
            'has_password' => !empty($this->config['password'])
        ]);
    }

    public function uploadFile(FileUpload $upload)
    {
        Log::channel('sftp')->debug('FtpUploadService: Inizio upload file', [
            'upload_id' => $upload->id,
            'ax_export_path' => $upload->ax_export_path
        ]);

        try {
            if (!$upload->ax_export_path) {
                throw new \Exception('File AX non ancora generato');
            }

            // Costruisci il percorso corretto
            $filename = basename($upload->ax_export_path);
            $correctPath = 'exports/' . $filename;

            Log::channel('sftp')->debug('FtpUploadService: Verifica percorsi', [
                'filename' => $filename,
                'correct_path' => $correctPath,
                'exists' => Storage::disk('private')->exists($correctPath),
                'storage_path' => Storage::disk('private')->path($correctPath)
            ]);

            // Verifica esistenza file
            if (!Storage::disk('private')->exists($correctPath)) {
                throw new \Exception("File AX non trovato nel percorso specificato: {$correctPath}");
            }

            // Ottieni il percorso completo del file
            $localPath = Storage::disk('private')->path($correctPath);

            // Verifica che il file sia leggibile
            if (!is_readable($localPath)) {
                Log::channel('sftp')->error('FtpUploadService: File non leggibile', [
                    'path' => $localPath,
                    'permissions' => fileperms($localPath),
                    'owner' => fileowner($localPath),
                    'group' => filegroup($localPath)
                ]);
                throw new \Exception("File non leggibile: {$localPath}");
            }

            // Imposta lo stato iniziale
            $upload->sftp_status = 'processing';
            $upload->save();

            // Evita che max_execution_time interrompa l'upload (errore 115 / timeout)
            set_time_limit(300);

            // Test della risoluzione DNS
            $dnsResult = gethostbyname($this->config['host']);
            Log::channel('sftp')->debug('FtpUploadService: DNS lookup', [
                'host' => $this->config['host'],
                'resolved_ip' => $dnsResult,
                'is_ip' => $dnsResult !== $this->config['host']
            ]);

            // Inizializza la connessione FTP con timeout
            Log::channel('sftp')->debug('FtpUploadService: Tentativo di connessione FTP', [
                'host' => $this->config['host'],
                'port' => $this->config['port']
            ]);

            $this->connection = @ftp_connect($this->config['host'], $this->config['port'], 30);
            
            if (!$this->connection) {
                $error = error_get_last();
                Log::channel('sftp')->error('FtpUploadService: Errore di connessione', [
                    'error_message' => $error['message'] ?? 'Nessun messaggio di errore',
                    'error_type' => $error['type'] ?? 'Tipo errore sconosciuto'
                ]);
                throw new \Exception('Impossibile connettersi al server FTP: ' . ($error['message'] ?? ''));
            }

            // Login con debug
            Log::channel('sftp')->debug('FtpUploadService: Tentativo di login', [
                'username' => $this->config['username']
            ]);

            if (!@ftp_login($this->connection, $this->config['username'], $this->config['password'])) {
                $error = error_get_last();
                Log::channel('sftp')->error('FtpUploadService: Errore di login', [
                    'error_message' => $error['message'] ?? 'Nessun messaggio di errore'
                ]);
                throw new \Exception('Impossibile autenticarsi al server FTP: ' . ($error['message'] ?? ''));
            }

            // Abilita la modalità passiva e verifica
            $pasv_result = ftp_pasv($this->connection, true);
            Log::channel('sftp')->debug('FtpUploadService: Modalità passiva', [
                'enabled' => $pasv_result
            ]);

            // Timeout lungo per connessione dati (evita errore 115 "Operation now in progress")
            if (function_exists('ftp_set_option')) {
                @ftp_set_option($this->connection, FTP_TIMEOUT_SEC, 120);
            }

            // Verifica dello spazio disponibile (se supportato)
            if (function_exists('ftp_raw')) {
                $raw = ftp_raw($this->connection, 'SITE QUOTA');
                Log::channel('sftp')->debug('FtpUploadService: Informazioni quota', [
                    'raw_response' => $raw
                ]);
            }

            // Costruisci il percorso remoto
            $remotePath = rtrim($this->config['remote_path'], '/') . '/' . $filename;

            // Verifica esistenza directory remota
            $pwd = ftp_pwd($this->connection);
            Log::channel('sftp')->debug('FtpUploadService: Directory corrente', [
                'current_dir' => $pwd,
                'target_path' => $remotePath
            ]);

            Log::channel('sftp')->debug('FtpUploadService: Tentativo di upload', [
                'local_path' => $localPath,
                'remote_path' => $remotePath,
                'file_size' => filesize($localPath),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]);

            // Upload con retry (errore 115 "Operation now in progress" può essere transiente)
            $maxAttempts = 3;
            $upload_result = false;
            $lastError = null;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $upload_result = @ftp_put($this->connection, $remotePath, $localPath, FTP_BINARY);
                if ($upload_result) {
                    break;
                }
                $lastError = error_get_last();
                if ($attempt < $maxAttempts) {
                    Log::channel('sftp')->warning('FtpUploadService: Retry upload', [
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'error' => $lastError['message'] ?? null
                    ]);
                    sleep(2);
                }
            }

            if (!$upload_result) {
                $error = $lastError ?? error_get_last();
                Log::channel('sftp')->error('FtpUploadService: Errore upload', [
                    'error_message' => $error['message'] ?? 'Nessun messaggio di errore',
                    'attempts' => $maxAttempts
                ]);
                throw new \Exception('Errore durante l\'upload del file su FTP: ' . ($error['message'] ?? ''));
            }

            // Verifica che il file sia stato caricato
            $remoteSize = ftp_size($this->connection, $remotePath);
            $localSize = filesize($localPath);
            
            Log::channel('sftp')->debug('FtpUploadService: Verifica dimensioni', [
                'local_size' => $localSize,
                'remote_size' => $remoteSize,
                'match' => $remoteSize === $localSize
            ]);

            if ($remoteSize !== $localSize) {
                throw new \Exception("Errore di verifica dimensione file: locale {$localSize} bytes, remoto {$remoteSize} bytes");
            }

            // Aggiorna lo stato del file
            $upload->sftp_status = 'completed';
            $upload->sftp_uploaded_at = now();
            $upload->save();

            Log::channel('sftp')->info('FtpUploadService: File caricato con successo', [
                'upload_id' => $upload->id,
                'remote_path' => $remotePath,
                'file_size' => $localSize
            ]);

            return true;

        } catch (\Exception $e) {
            Log::channel('sftp')->error('FtpUploadService: Errore durante l\'upload', [
                'upload_id' => $upload->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $upload->sftp_status = 'error';
            $upload->sftp_error_message = $e->getMessage();
            $upload->save();

            throw $e;
        } finally {
            // Chiudi la connessione FTP se è aperta
            if ($this->connection && is_resource($this->connection)) {
                ftp_close($this->connection);
                Log::channel('sftp')->debug('FtpUploadService: Connessione FTP chiusa');
            }
        }
    }
}