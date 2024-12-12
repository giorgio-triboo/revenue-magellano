<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VatCheckController extends Controller
{
    public function check(Request $request)
    {
        try {
            $request->validate([
                'vat_number' => 'required|string|size:11'
            ]);

            $publisher = Publisher::where('vat_number', $request->vat_number)->first();

            Log::info('Verifica partita IVA', [
                'vat_number' => $request->vat_number,
                'exists' => (bool)$publisher
            ]);

            if ($publisher) {
                return response()->json([
                    'exists' => true,
                    'publisher' => [
                        'company_name' => $publisher->company_name,
                        'legal_name' => $publisher->legal_name,
                        'website' => $publisher->website,
                        'county' => $publisher->county,
                        'city' => $publisher->city,
                        'postal_code' => $publisher->postal_code,
                        'iban' => $publisher->iban,
                        'swift' => $publisher->swift,
                    ]
                ]);
            }

            return response()->json(['exists' => false]);

        } catch (\Exception $e) {
            Log::error('Errore durante il controllo della partita IVA', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Errore durante la verifica della partita IVA'
            ], 500);
        }
    }
}