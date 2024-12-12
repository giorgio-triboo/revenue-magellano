<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublisherController extends Controller
{
    public function show(Publisher $publisher)
    {
        try {
            $this->authorize('view', $publisher);

            // Filtra i dati sensibili per gli operativi
            $data = $publisher->toArray();
            if (!auth()->user()->isAdmin()) {
                unset($data['iban'], $data['swift']);
            }

            Log::info('API - Dettagli publisher recuperati', [
                'user_id' => auth()->id(),
                'publisher_id' => $publisher->id
            ]);

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('API - Errore nel recupero dei dettagli publisher', [
                'user_id' => auth()->id(),
                'publisher_id' => $publisher->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Si è verificato un errore nel recupero dei dati del publisher'
            ], 500);
        }
    }
}