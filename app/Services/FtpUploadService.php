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
                'exists' => Storage::disk('private')->exists($correctPath)
            ]);

            // Verifica esistenza file
            if (!Storage::disk('private')->exists($correctPath)) {
                throw new \Exception("File AX non trovato nel percorso specificato: {$correctPath}");
            }

            // Ottieni il percorso completo del file
            $localPath = Storage::disk('private')->path($correctPath);

            // Verifica che il file sia leggibile
            if (!is_readable($localPath)) {
                throw new \Exception("File non leggibile: {$localPath}");
            }

            // Imposta lo stato iniziale
            $upload->sftp_status = 'processing';
            $upload->save();

            // Inizializza la connessione FTP con la porta specificata
            $this->connection = ftp_connect($this->config['host'], $this->config['port']);
            if (!$this->connection) {
                throw new \Exception('Impossibile connettersi al server FTP');
            }

            // Login
            if (!ftp_login($this->connection, $this->config['username'], $this->config['password'])) {
                throw new \Exception('Impossibile autenticarsi al server FTP');
            }

            // Abilita la modalità passiva
            ftp_pasv($this->connection, true);

            // Costruisci il percorso remoto
            $remotePath = rtrim($this->config['remote_path'], '/') . '/' . $filename;

            Log::channel('sftp')->debug('FtpUploadService: Tentativo di upload', [
                'local_path' => $localPath,
                'remote_path' => $remotePath,
                'file_size' => filesize($localPath)
            ]);

            // Upload del file
            if (!ftp_put($this->connection, $remotePath, $localPath, FTP_BINARY)) {
                throw new \Exception('Errore durante l\'upload del file su FTP');
            }

            // Verifica che il file sia stato caricato
            $remoteSize = ftp_size($this->connection, $remotePath);
            $localSize = filesize($localPath);
            
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
                'error' => $e->getMessage()
            ]);

            $upload->sftp_status = 'error';
            $upload->sftp_error_message = $e->getMessage();
            $upload->save();

            throw $e;
        } finally {
            // Chiudi la connessione FTP se è aperta
            if ($this->connection && is_resource($this->connection)) {
                ftp_close($this->connection);
            }
        }
    }
}