<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publisher;
use App\Models\SubPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubPublisherController extends Controller
{
    public function __construct()
    {
        // Verifica l'autenticazione e imposta il ruolo
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if ($user) {
                \Log::debug("SubPublisherController: Utente autenticato", ['user_id' => $user->id, 'user_role' => $user->role ?? 'no_role']);
            } else {
                \Log::debug("SubPublisherController: Utente non autenticato");
            }

            return $next($request);
        });
    }

    public function store(Request $request, Publisher $publisher)
    {
        Log::debug('SubPublisherController@store: Inizio', [
            'user_id' => auth()->id(),
            'publisher_id' => $publisher->id
        ]);

        try {
            $this->authorize('createFor', [SubPublisher::class, $publisher]);

            $validated = $request->validate([
                'display_name' => 'required|string|max:255',
                'invoice_group' => 'required|string|max:255',
                'ax_name' => 'required|string|max:255',
                'channel_detail' => 'nullable|string',
                'notes' => 'nullable|string',
                'is_primary' => 'boolean'
            ]);

            $subPublisher = $publisher->subPublishers()->create($validated);

            Log::info('SubPublisherController@store: Successo', [
                'sub_publisher_id' => $subPublisher->id
            ]);

            return response()->json([
                'message' => 'Database creato con successo',
                'data' => $subPublisher
            ], 201);

        } catch (\Exception $e) {
            Log::error('SubPublisherController@store: Errore', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function update(Request $request, Publisher $publisher, SubPublisher $subPublisher)
    {
        Log::debug('SubPublisherController@update: Inizio richiesta', [
            'user_id' => auth()->id(),
            'publisher_id' => $publisher->id,
            'sub_publisher_id' => $subPublisher->id,
            'request_data' => $request->all()
        ]);

        try {
            Log::debug('SubPublisherController@update: Verifica autorizzazione', [
                'user_id' => auth()->id(),
                'publisher_id' => $publisher->id,
                'sub_publisher_id' => $subPublisher->id
            ]);

            $this->authorize('update', $subPublisher);

            $validated = $request->validate([
                'display_name' => 'required|string|max:255',
                'notes' => 'nullable|string',
                'is_primary' => 'boolean'
            ]);

            $subPublisher->update($validated);

            Log::info('SubPublisherController@update: Sub-publisher aggiornato', [
                'publisher_id' => $publisher->id,
                'sub_publisher_id' => $subPublisher->id,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Database aggiornato con successo',
                'data' => $subPublisher
            ]);
        } catch (\Exception $e) {
            Log::error('SubPublisherController@update: Errore', [
                'publisher_id' => $publisher->id,
                'sub_publisher_id' => $subPublisher->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Si è verificato un errore durante l\'aggiornamento del database'
            ], 500);
        }
    }

    public function destroy(Publisher $publisher, SubPublisher $subPublisher)
    {
        Log::debug('SubPublisherController@destroy: Inizio richiesta di cancellazione', [
            'user_id' => auth()->id(),
            'publisher_id' => $publisher->id,
            'sub_publisher_id' => $subPublisher->id
        ]);

        try {
            $this->authorize('delete', $subPublisher);

            $subPublisher->delete();

            Log::info("SubPublisherController@destroy: Sub-publisher eliminato", [
                'publisher_id' => $publisher->id,
                'sub_publisher_id' => $subPublisher->id,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Database eliminato con successo'
            ]);

        } catch (\Exception $e) {
            Log::error("SubPublisherController@destroy: Errore durante la cancellazione", [
                'publisher_id' => $publisher->id,
                'sub_publisher_id' => $subPublisher->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Errore durante la cancellazione del database'
            ], 500);
        }
    }
}
