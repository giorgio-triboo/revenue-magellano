<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Controllers\Auth\ForgotPasswordController;
use Illuminate\Support\Facades\Password;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;



class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-users');
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        try {
            $status = $request->get('status', 'active');

            // Query di base con le relazioni
            $query = User::with(['role', 'publisher']);

            // Gestione del filtro stato
            switch ($status) {
                case 'deleted':
                    $query->onlyTrashed(); // Solo utenti eliminati
                    break;
                case 'all':
                    $query->withTrashed(); // Tutti gli utenti, inclusi quelli eliminati
                    break;
                case 'active':
                default:
                    $query->whereNull('deleted_at'); // Solo utenti attivi
                    break;
            }

            // Ricerca
            if ($request->filled('search')) {
                $search = str_replace('*', '%', $request->search);
                // Sanitizzazione aggiuntiva per sicurezza
                $search = strip_tags($search);
                $search = addcslashes($search, '%_'); // Escape caratteri speciali LIKE
                
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'LIKE', "%{$search}%")
                        // Ricerca separata su first_name e last_name (più sicuro di DB::raw)
                        ->orWhere(function ($subQuery) use ($search) {
                            $subQuery->where('first_name', 'LIKE', "%{$search}%")
                                ->orWhere('last_name', 'LIKE', "%{$search}%");
                        })
                        ->orWhereHas('publisher', function ($query) use ($search) {
                            $query->where('legal_name', 'LIKE', "%{$search}%")
                                ->orWhere('company_name', 'LIKE', "%{$search}%");
                        });
                });
            }

            $users = $query->paginate(12)->withQueryString();
            $roles = Role::all();

            Log::info('Lista utenti recuperata', [
                'status' => $status,
                'search' => $request->search,
                'count' => $users->count()
            ]);

            return view('users.index', compact('users', 'roles'));
        } catch (\Exception $e) {
            Log::error('Errore nel recupero della lista utenti', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Si è verificato un errore nel recupero della lista utenti.');
        }
    }

    /**
     * Show user details.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }


    /**
     * Update user's role.
     */
    public function updateRole(Request $request, User $user)
    {
        try {
            if (!Gate::allows('updateRole', $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non hai i permessi per modificare questo ruolo.'
                ], 403);
            }

            $validated = $request->validate([
                'role_id' => 'required|exists:roles,id'
            ]);

            $user->update($validated);

            Log::info('Ruolo utente aggiornato', [
                'user_id' => $user->id,
                'old_role' => $user->getOriginal('role_id'),
                'new_role' => $validated['role_id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ruolo aggiornato con successo'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento del ruolo', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'aggiornamento del ruolo.'
            ], 500);
        }
    }



    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        try {
            // Log per debug
            \Log::info('Update user request:', [
                'request_data' => $request->all(),
                'user_id' => $user->id
            ]);

            // Regole di validazione base
            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
            ];

            // Aggiungi role_id alla validazione solo se non è l'utente corrente
            if ($user->id !== auth()->id()) {
                $rules['role_id'] = 'required|exists:roles,id';
            }

            $validated = $request->validate($rules);

            // Se il role_id non è presente nella richiesta (campo disabilitato), usa quello esistente
            if (!isset($validated['role_id'])) {
                $validated['role_id'] = $user->role_id;
            }

            // Aggiornamento utente
            $updated = $user->update($validated);

            \Log::info('Update result:', [
                'user_id' => $user->id,
                'updated' => $updated,
                'new_data' => $validated
            ]);

            if (!$updated) {
                throw new \Exception('Failed to update user data');
            }

            return redirect()->route('users.index')
                ->with('success', 'Utente aggiornato con successo.');

        } catch (\Exception $e) {
            \Log::error('Errore nell\'aggiornamento dell\'utente', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withInput()
                ->with('error', 'Si è verificato un errore durante l\'aggiornamento dell\'utente.');
        }
    }

    public function export(Request $request)
    {
        try {
            Log::debug('Inizio esportazione utenti', ['filters' => $request->all()]);

            // Verifica permessi
            if (!auth()->user()->canExportData()) {
                Log::warning('Tentativo di esportazione non autorizzato', [
                    'user_id' => auth()->id()
                ]);
                return back()->with('error', 'Non hai i permessi per esportare i dati.');
            }

            // Preparazione query base
            $query = User::with(['role', 'publisher'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = $request->search;
                    return $query->where(function ($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereHas('publisher', function ($query) use ($search) {
                                $query->where('company_name', 'like', "%{$search}%")
                                    ->orWhere('vat_number', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($request->filled('status'), function ($query) use ($request) {
                    return $query->where('is_active', $request->status === 'active');
                });

            // Esegui l'esportazione
            $timestamp = now()->format('Y-m-d_His');
            $filename = "utenti_{$timestamp}.xlsx";

            Log::info('Esportazione utenti completata', [
                'filename' => $filename,
                'user_id' => auth()->id()
            ]);

            return Excel::download(new UsersExport($query), $filename);

        } catch (\Exception $e) {
            Log::error('Errore durante l\'esportazione degli utenti', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Si è verificato un errore durante l\'esportazione degli utenti.');
        }
    }


    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        try {
            DB::transaction(function () use ($user) {
                // Invece di eliminare l'utente, lo disattiviamo
                $user->update([
                    'is_active' => false,
                    'is_validated' => false
                ]);

                // Utilizziamo softDelete per mantenere lo storico
                $user->delete();
            });

            Log::info('Utente disattivato con successo', [
                'user_id' => $user->id,
                'action' => 'soft_delete'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Utente disattivato con successo.'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore nella disattivazione dell\'utente', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante la disattivazione dell\'utente.'
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);

            DB::transaction(function () use ($user) {
                $user->restore();
                $user->update([
                    'is_active' => true,
                    'is_validated' => true
                ]);
            });

            Log::info('Utente riattivato con successo', [
                'user_id' => $user->id,
                'action' => 'restore'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Utente riattivato con successo.'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore nella riattivazione dell\'utente', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante la riattivazione dell\'utente.'
            ], 500);
        }
    }






    /**
     * Send password reset link to user.
     */
    public function sendPasswordReset(User $user)
    {
        // Crea una richiesta simulata per il reset della password
        $request = new Request(['email' => $user->email]);

        // Richiama il metodo di reset della password utilizzato normalmente dagli utenti
        $status = Password::sendResetLink($request->only('email'));

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => __($status)
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => __($status)
            ], 500);
        }
    }


    /**
     * Get user details for modal.
     */
    public function getDetails(User $user)
    {
        try {
            $userData = $user->load('role', 'publisher');

            return response()->json([
                'success' => true,
                'data' => $userData
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nel recupero dei dettagli utente', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore nel recupero dei dettagli utente.'
            ], 500);
        }
    }

    /**
     * Aggiorna lo stato di validazione dell'utente
     */
    public function updateValidationStatus(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'is_validated' => 'required|boolean'
            ]);

            $updateData = [
                'is_validated' => $validated['is_validated'],
                'is_active' => $validated['is_validated'] // Stesso valore di is_validated
            ];

            \Log::info('Aggiornamento stato validazione', [
                'user_id' => $user->id,
                'update_data' => $updateData
            ]);

            $updated = $user->update($updateData);

            if (!$updated) {
                throw new \Exception('Failed to update validation status');
            }

            \Log::info('Stati validazione e attivazione aggiornati', [
                'user_id' => $user->id,
                'is_validated' => $user->is_validated,
                'is_active' => $user->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stati di validazione e attivazione aggiornati con successo'
            ]);

        } catch (\Exception $e) {
            \Log::error('Errore nell\'aggiornamento degli stati', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'aggiornamento degli stati.'
            ], 500);
        }
    }

    /**
     * Aggiorna lo stato attivo/inattivo dell'utente
     */
    public function updateActiveStatus(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean'
            ]);

            $user->update([
                'is_active' => $validated['is_active']
            ]);

            Log::info('Stato attivo utente aggiornato', [
                'user_id' => $user->id,
                'is_active' => $validated['is_active']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stato attivo aggiornato con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento dello stato attivo', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'aggiornamento dello stato attivo.'
            ], 500);
        }
    }

    // Modifica del metodo edit per includere le informazioni di stato
    public function edit(User $user)
    {
        $roles = Role::all();

        // Formattiamo le date per la visualizzazione
        $emailVerificationDate = $user->email_verified_at ? $user->email_verified_at->format('d/m/Y H:i') : null;

        return view('users.edit', compact('user', 'roles', 'emailVerificationDate'));
    }
}