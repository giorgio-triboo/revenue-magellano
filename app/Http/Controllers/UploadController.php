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
        $instructions = <<<EOT
ISTRUZIONI PER IL CARICAMENTO
/ Formato File
- estensione file .csv
- utilizzare ; come separatore delle colonne
- l'ordine delle colonne non è vincolante al caricamento del file

/ Colonne Specifiche
- "anno_consuntivo" e "anno_competenza" prevedono l'anno in 4 cifre (es. "2025")
- "mese_consuntivo" e "mese_competenza" prevedono due cifre per la data (es. "01", non "1")
- "nome_campagna_HO" non è vincolante ma si consiglia di utilizzare sempre lo stesso in modo da facilitare la ricerca
- "nome_publisher" non è required per il caricamento
- "publisher_id" e "sub_publisher_id" prevedono l'inserimento di una cifra per i nuemeri dal 1-9 (es. "1", non "01")
- "payout" e "importo" possono avere massimo 2 decimali separati da "," (esempio 10,01) e non prevedono caratteri speciali (es. "€")
- "quantita_validata" accetta solo valori interi
- "data_invio" e "note", campi di testo non required per il caricamento
- "tipologia_revenue" accettate cpl, cpc, cpm, tmk, crg, cpa, sms

Intestazioni obbligatorie:
anno_consuntivo
mese_consuntivo
anno_competenza
mese_competenza
nome_campagna_HO
nome_publisher
publisher_id
sub_publisher_id
tipologia_revenue
quantita_validata
pay
importo
data_invio
note
EOT;

        Storage::disk('private')->put('template/istruzioni.txt', $instructions);

        return Storage::disk('private')->download(
            'template/istruzioni.txt',
            'istruzioni_caricamento.txt',
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]
        );
    }

    public function sendEmail(FileUpload $upload)
    {
        try {
            Log::info('Debug Upload', [
                'upload_id' => $upload->id
            ]);

            // Verifica se l'email è già stata inviata
            if ($upload->notification_sent_at !== null) {
                return response()->json([
                    'message' => 'Le email sono già state inviate per questo upload'
                ], 400);
            }

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

            // Aggiorniamo il timestamp di invio
            $upload->update([
                'notification_sent_at' => now()
            ]);

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

    public function uploadToSftp(FileUpload $upload)
    {
        Log::channel('sftp')->debug('UploadController: Richiesta upload SFTP ricevuta', [
            'upload_id' => $upload->id,
            'user_id' => auth()->id()
        ]);

        if (!Gate::allows('upload-to-sftp')) {
            Log::channel('sftp')->warning('UploadController: Accesso non autorizzato all\'upload SFTP', [
                'user_id' => auth()->id()
            ]);
            abort(403, 'Non autorizzato a caricare file su SFTP.');
        }

        try {
            Log::channel('sftp')->debug('UploadController: Inizializzazione SftpService');

            $sftpService = app(FtpUploadService::class);

            Log::channel('sftp')->debug('UploadController: Avvio upload file', [
                'upload_id' => $upload->id
            ]);

            $sftpService->uploadFile($upload);

            Log::channel('sftp')->info('UploadController: Upload SFTP completato con successo', [
                'upload_id' => $upload->id
            ]);

            return response()->json([
                'message' => 'File caricato su SFTP con successo'
            ]);

        } catch (\Exception $e) {
            Log::channel('sftp')->error('UploadController: Errore durante l\'upload su SFTP', [
                'upload_id' => $upload->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Errore durante l\'upload su SFTP: ' . $e->getMessage()
            ], 500);
        }
    }
}