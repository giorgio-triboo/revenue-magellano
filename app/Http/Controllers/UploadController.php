<?php

namespace App\Http\Controllers;

use App\Models\Statement;
use App\Models\FileUpload;
use App\Services\UploadService;
use App\Services\ExportService;
use App\Mail\FilePublishedNotification;
use App\Mail\StatementPublished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Events\FileUploadProcessed;
use app\Models\User;
use App\Mail\StatementPublishedTest;
use App\Services\FtpUploadService;

class UploadController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function index()
    {
        if (!Gate::allows('view-uploads')) {
            abort(403, 'Non autorizzato ad accedere a questa sezione.');
        }

        $uploads = FileUpload::with(['user', 'statements'])
            ->when(auth()->user()->role->code === 'publisher', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest()
            ->paginate(10);

        return view('uploads.index', compact('uploads'));
    }

    public function list()
    {
        if (!Gate::allows('view-uploads')) {
            return response()->json(['message' => 'Non autorizzato'], 403);
        }

        try {
            $uploads = FileUpload::with(['user', 'statements'])
                ->when(auth()->user()->role->code === 'publisher', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->whereIn('status', ['pending', 'processing'])
                ->latest()
                ->get()
                ->map(function ($upload) {
                    return [
                        'id' => $upload->id,
                        'status' => $upload->status,
                        'progress_percentage' => $upload->progress_percentage,
                        'processed_records' => $upload->processed_records,
                        'total_records' => $upload->total_records,
                        'ax_export_status' => $upload->status,
                        'sftp_status' => $upload->sftp_status,
                    ];
                });

            return response()->json($uploads);

        } catch (\Exception $e) {
            Log::error('Error fetching uploads list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Errore nel recupero della lista degli upload'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        Log::channel('upload')->emergency('UploadController: INIZIO RICHIESTA UPLOAD', [
            'user' => auth()->check() ? 'autenticato' : 'non autenticato',
            'user_id' => auth()->id(),
            'method' => $request->method(),
            'ajax' => $request->ajax(),
            'headers' => $request->headers->all(),
            'has_file' => $request->hasFile('file'),
        ]);

        Log::channel('upload')->debug('UploadController: Inizio richiesta store', [
            'user_id' => auth()->id(),
            'headers' => $request->headers->all(),
            'has_file' => $request->hasFile('file'),
            'content_type' => $request->header('Content-Type')
        ]);

        try {
            // Verifica del token CSRF
            if (!$request->hasValidSignature() && !$request->header('X-CSRF-TOKEN')) {
                Log::channel('upload')->error('UploadController: CSRF token mancante o non valido');
                return response()->json([
                    'message' => 'Token di sicurezza non valido'
                ], 403);
            }

            // Verifica dei permessi
            if (!Gate::allows('upload-files')) {
                Log::channel('upload')->error('UploadController: Utente non autorizzato', [
                    'user_id' => auth()->id()
                ]);
                return response()->json([
                    'message' => 'Non autorizzato ad effettuare upload'
                ], 403);
            }

            $validated = $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240',
                'year' => 'required|integer|min:2000|max:2100',
                'month' => 'required|integer|min:1|max:12',
            ]);

            Log::channel('upload')->debug('UploadController: Validazione file completata.', [
                'validated' => $validated
            ]);

            $processDate = Carbon::createFromDate($validated['year'], $validated['month'], 1)->format('Y-m-d');

            $upload = DB::transaction(function () use ($request, $processDate) {
                Log::channel('upload')->debug('UploadController: Inizio transazione DB.');

                try {
                    $upload = $this->uploadService->handleFileUpload(
                        $request->file('file'),
                        auth()->id(),
                        $processDate
                    );

                    Log::channel('upload')->debug('UploadController: Upload completato.', [
                        'upload_id' => $upload->id
                    ]);

                    return $upload;
                } catch (\Exception $e) {
                    Log::channel('upload')->error('UploadController: Errore durante la transazione DB', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            });

            event(new FileUploadProcessed($upload));

            Log::channel('upload')->info('UploadController: File caricato con successo.', [
                'upload_id' => $upload->id,
                'user_id' => auth()->id(),
                'filename' => $request->file('file')->getClientOriginalName(),
                'process_date' => $processDate
            ]);

            return response()->json([
                'message' => 'File caricato con successo',
                'upload_id' => $upload->id,
                'status' => $upload->status,
                'process_date' => $processDate
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('upload')->error('UploadController: Errore di validazione', [
                'errors' => $e->errors(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Errore di validazione',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::channel('upload')->error('UploadController: Errore durante l\'upload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            $errorMessage = app()->environment('local')
                ? $e->getMessage()
                : 'Si è verificato un errore durante il caricamento del file. Riprova più tardi.';

            return response()->json([
                'message' => $errorMessage,
                'status' => 'error'
            ], 500);
        }
    }

    public function publish($id)
    {
        if (!Gate::allows('publish-files')) {
            abort(403, 'Non autorizzato a pubblicare file.');
        }

        try {
            $upload = FileUpload::findOrFail($id);

            if (!$upload->isCompleted()) {
                return response()->json([
                    'message' => 'Il file non può essere pubblicato in questo stato'
                ], 422);
            }

            DB::beginTransaction();
            try {
                $upload->status = FileUpload::STATUS_PUBLISHED;
                $upload->published_at = now();
                $upload->save();

                $upload->statements()->update([
                    'is_published' => true,
                    'published_at' => now()
                ]);

                DB::commit();

                Log::info('File published successfully', [
                    'upload_id' => $upload->id,
                    'user_id' => auth()->id()
                ]);

                return response()->json([
                    'message' => 'File pubblicato con successo',
                    'upload' => [
                        'id' => $upload->id,
                        'status' => $upload->status,
                        'published_at' => $upload->published_at->toDateTimeString()
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error in publish action', [
                'upload_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Errore durante la pubblicazione: ' . $e->getMessage()
            ], 500);
        }
    }

    public function unpublish($id)
    {
        if (!Gate::allows('publish-files')) {
            abort(403, 'Non autorizzato a rimuovere la pubblicazione.');
        }

        try {
            $upload = FileUpload::findOrFail($id);

            if (!$upload->isPublished()) {
                return response()->json([
                    'message' => 'Il file non è attualmente pubblicato'
                ], 422);
            }

            DB::beginTransaction();
            try {
                $upload->status = FileUpload::STATUS_COMPLETED;
                $upload->published_at = null;
                $upload->save();

                $upload->statements()->update([
                    'is_published' => false,
                    'published_at' => null
                ]);

                DB::commit();

                Log::info('File unpublished successfully', [
                    'upload_id' => $upload->id,
                    'user_id' => auth()->id()
                ]);

                return response()->json([
                    'message' => 'File rimosso dalla pubblicazione con successo',
                    'upload' => [
                        'id' => $upload->id,
                        'status' => $upload->status,
                        'published_at' => null
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error in unpublish action', [
                'upload_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Errore durante la rimozione dalla pubblicazione: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(FileUpload $upload)
    {
        if (!Gate::allows('download-files')) {
            abort(403, 'Non autorizzato a scaricare file.');
        }

        try {
            if (!$upload->ax_export_path) {
                return response()->json([
                    'message' => 'File AX non ancora generato o in elaborazione'
                ], 422);
            }

            $fileName = basename($upload->ax_export_path);

            if (!Storage::disk('private')->exists('exports/' . $fileName)) {
                return response()->json([
                    'message' => 'File AX non trovato'
                ], 404);
            }

            return Storage::disk('private')->download('exports/' . $fileName);

        } catch (\Exception $e) {
            Log::error('Errore durante il download del file AX', [
                'upload_id' => $upload->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Errore durante il download del file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendTestEmail(FileUpload $upload)
    {
        try {
            Log::info('Invio email di test', [
                'upload_id' => $upload->id
            ]);

            $user = auth()->user();

            if (!$user || !$user->email) {
                return response()->json([
                    'message' => 'Email utente non disponibile per il test'
                ], 400);
            }

            Mail::to($user->email)
                ->send(new StatementPublishedTest($upload));

            Log::info('Email di test inviata con successo', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'message' => 'Email di test inviata con successo a ' . $user->email
            ]);

        } catch (\Exception $e) {
            Log::error('Errore durante l\'invio dell\'email di test', [
                'error' => $e->getMessage(),
                'upload_id' => $upload->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Errore durante l\'invio dell\'email di test: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(FileUpload $upload)
    {
        if (!Gate::allows('view-uploads')) {
            abort(403, 'Non autorizzato a visualizzare i dettagli del file.');
        }

        try {
            $upload->load(['user', 'statements']);

            return response()->json([
                'id' => $upload->id,
                'year' => $upload->process_date->year,
                'month' => $upload->process_date->month,
                'status' => $upload->status,
                'error_message' => $upload->error_message,
                'processed_records' => $upload->processed_records,
                'total_records' => $upload->total_records,
                'progress_percentage' => $upload->progress_percentage,
                'processing_stats' => $upload->processing_stats,
                'created_at' => $upload->created_at,
                'processed_at' => $upload->processed_at,
                'published_at' => $upload->published_at,
                'user' => [
                    'id' => $upload->user->id,
                    'name' => $upload->user->name
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching upload details', [
                'upload_id' => $upload->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Errore nel recupero dei dettagli del file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        if (!Gate::allows('upload-files')) {
            abort(403, 'Non autorizzato a eliminare file.');
        }

        try {
            $upload = FileUpload::findOrFail($id);

            if ($upload->isPublished()) {
                return response()->json([
                    'message' => 'Non è possibile eliminare un file pubblicato. Rimuovere prima dalla pubblicazione.'
                ], 422);
            }

            DB::beginTransaction();
            try {
                if ($upload->stored_filename && Storage::disk('private')->exists($upload->stored_filename)) {
                    Storage::disk('private')->delete($upload->stored_filename);
                }

                $upload->statements()->delete();
                $upload->delete();

                DB::commit();

                Log::info('Upload deleted successfully', [
                    'upload_id' => $id,
                    'user_id' => auth()->id(),
                    'filename' => $upload->stored_filename
                ]);

                return response()->json([
                    'message' => 'File eliminato con successo'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error deleting upload', [
                'upload_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Errore durante l\'eliminazione del file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/templates/upload-def.csv');

        if (!Storage::exists('templates/upload-def.csv')) {
            Storage::put(
                'templates/upload-def.csv',
                "anno_consuntivo;mese_consuntivo;anno_competenza;mese_competenza;nome_campagna_HO;publisher_id;sub_publisher_id;tipologia_revenue;quantita_validata;pay;importo\n" .
                "2025;1;2025;1;campagna 1;1;1;cpl;100;25;2500"
            );
        }

        return response()->download($path, 'template_consuntivo.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function sendEmail(FileUpload $upload)
    {
        try {
            Log::info('Debug Upload', [
                'upload_id' => $upload->id
            ]);

            $statements = Statement::where('file_upload_id', $upload->id)
                ->with([
                    'publisher.users' => function ($query) {
                        $query->where('is_active', true)
                            ->where('can_receive_email', true)
                            ->whereHas('role', function ($q) {
                                $q->where('code', 'publisher');
                            });
                    }
                ])
                ->get();

            Log::info('Debug Statements', [
                'total_statements' => $statements->count()
            ]);

            $validPublishers = $statements
                ->pluck('publisher')
                ->filter()
                ->unique('id')
                ->filter(function ($publisher) {
                    return $publisher->users->isNotEmpty();
                });

            if ($validPublishers->isEmpty()) {
                return response()->json([
                    'message' => 'Nessun publisher con utenti validi trovato per questo upload'
                ], 400);
            }

            $sentCount = 0;
            foreach ($validPublishers as $publisher) {
                foreach ($publisher->users as $user) {
                    if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                        Mail::to($user->email)
                            ->send(new StatementPublished($upload));
                        $sentCount++;
                        Log::info('Email inviata a user', [
                            'publisher_id' => $publisher->id,
                            'user_id' => $user->id,
                            'email' => $user->email
                        ]);
                    }
                }
            }

            if ($sentCount === 0) {
                throw new \Exception('Nessuna email valida trovata per gli utenti dei publisher');
            }

            return response()->json([
                'message' => "Email inviate con successo a {$sentCount} utenti"
            ]);

        } catch (\Exception $e) {
            Log::error('Errore invio email pubblicazione', [
                'error' => $e->getMessage(),
                'upload_id' => $upload->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Errore durante l\'invio delle email'
            ], 500);
        }
    }

    // In UploadController.php

public function uploadToSftp(FileUpload $upload)
{
    Log::channel('sftp')->debug('UploadController: Inizio processo upload SFTP', [
        'upload_id' => $upload->id,
        'user_id' => auth()->id(),
        'file_status' => $upload->status,
        'sftp_status' => $upload->sftp_status,
        'file_info' => [
            'original_filename' => $upload->original_filename,
            'stored_filename' => $upload->stored_filename,
            'file_size' => $upload->file_size,
            'mime_type' => $upload->mime_type
        ]
    ]);

    if (!Gate::allows('upload-to-sftp')) {
        Log::channel('sftp')->warning('UploadController: Accesso non autorizzato', [
            'user_id' => auth()->id(),
            'permissions' => auth()->user()->permissions->pluck('name')
        ]);
        abort(403, 'Non autorizzato a caricare file su SFTP.');
    }

    try {
        $sftpService = app(FtpUploadService::class);
        
        // Verifica preliminare della connessione
        Log::channel('sftp')->info('UploadController: Verifica connessione SFTP');
        $connectionTest = $sftpService->testConnection();
        
        if (!$connectionTest['success']) {
            Log::channel('sftp')->error('UploadController: Test connessione fallito', [
                'error' => $connectionTest['error'],
                'details' => $connectionTest['details'] ?? null
            ]);
            
            $upload->update([
                'sftp_status' => 'error',
                'sftp_error_message' => $connectionTest['error']
            ]);
            
            throw new \Exception("Errore connessione SFTP: " . $connectionTest['error']);
        }

        // Verifica esistenza file
        if (!Storage::disk('private')->exists($upload->stored_filename)) {
            Log::channel('sftp')->error('UploadController: File locale non trovato', [
                'filepath' => $upload->stored_filename,
                'original_filename' => $upload->original_filename
            ]);
            
            $upload->update([
                'sftp_status' => 'error',
                'sftp_error_message' => 'File non trovato nel filesystem locale'
            ]);
            
            throw new \Exception("File non trovato nel filesystem locale");
        }

        Log::channel('sftp')->info('UploadController: Avvio upload file', [
            'filename' => basename($upload->stored_filename),
            'size' => $upload->file_size,
            'mime_type' => $upload->mime_type
        ]);

        // Aggiorna lo stato prima dell'upload
        $upload->update(['sftp_status' => 'processing']);

        $result = $sftpService->uploadFile($upload);

        if (!$result['success']) {
            $upload->update([
                'sftp_status' => 'error',
                'sftp_error_message' => $result['error']
            ]);
            
            throw new \Exception($result['error']);
        }

        // Upload completato con successo
        $upload->update([
            'sftp_status' => 'completed',
            'sftp_error_message' => null,
            'sftp_uploaded_at' => now()
        ]);

        Log::channel('sftp')->info('UploadController: Upload completato', [
            'upload_id' => $upload->id,
            'remote_path' => $result['remote_path'] ?? null,
            'uploaded_at' => $upload->sftp_uploaded_at
        ]);

        return response()->json([
            'message' => 'File caricato su SFTP con successo',
            'upload' => [
                'id' => $upload->id,
                'sftp_status' => $upload->sftp_status,
                'sftp_uploaded_at' => $upload->sftp_uploaded_at
            ]
        ]);

    } catch (\Exception $e) {
        $errorContext = [
            'upload_id' => $upload->id,
            'error' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'trace' => $e->getTraceAsString(),
            'file_info' => [
                'original_filename' => $upload->original_filename,
                'stored_filename' => $upload->stored_filename,
                'file_size' => $upload->file_size,
                'mime_type' => $upload->mime_type,
                'exists' => Storage::disk('private')->exists($upload->stored_filename)
            ],
            'server_info' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ];

        Log::channel('sftp')->error('UploadController: Errore critico durante upload SFTP', $errorContext);

        // Aggiorna lo stato se non è già stato fatto nel catch precedente
        if ($upload->sftp_status !== 'error') {
            $upload->update([
                'sftp_status' => 'error',
                'sftp_error_message' => $e->getMessage()
            ]);
        }

        return response()->json([
            'message' => 'Errore durante l\'upload su SFTP: ' . $e->getMessage(),
            'debug_info' => app()->environment('local') ? $errorContext : null
        ], 500);
    }
}

// In FtpUploadService.php

public function testConnection(): array
{
    try {
        $config = config('filesystems.disks.sftp');
        
        Log::channel('sftp')->debug('FtpUploadService: Test connessione', [
            'host' => $config['host'],
            'port' => $config['port'],
            'username' => $config['username']
        ]);

        $sftp = new \phpseclib3\Net\SFTP($config['host'], $config['port']);
        
        if (!$sftp->login($config['username'], $this->getAuthenticationMethod())) {
            $lastError = $sftp->getLastError() ?: 'Autenticazione fallita';
            Log::channel('sftp')->error('FtpUploadService: Errore login', [
                'error' => $lastError,
                'auth_type' => !empty($config['privateKey']) ? 'private_key' : 'password'
            ]);
            return [
                'success' => false, 
                'error' => $lastError,
                'details' => ['auth_failed' => true]
            ];
        }

        // Test lettura directory
        $pwd = $sftp->pwd();
        $ls = $sftp->nlist($pwd);
        
        if ($ls === false) {
            $error = $sftp->getLastError() ?: 'Impossibile leggere la directory remota';
            return [
                'success' => false,
                'error' => $error,
                'details' => ['directory_listing_failed' => true]
            ];
        }
        
        Log::channel('sftp')->info('FtpUploadService: Connessione stabilita', [
            'current_dir' => $pwd,
            'can_list_files' => true,
            'files_count' => count($ls)
        ]);

        return ['success' => true];

    } catch (\Exception $e) {
        Log::channel('sftp')->error('FtpUploadService: Errore connessione', [
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'details' => [
                'code' => $e->getCode(),
                'type' => get_class($e)
            ]
        ];
    }
}
}