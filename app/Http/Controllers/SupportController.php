<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\SupportRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;



class SupportController extends Controller
{
    public function show()
    {
        return view('support.show');
    }

    public function send(Request $request)
    {
        try {
            // Validazione dell'input
            $validator = Validator::make($request->all(), [
                'category' => [
                    'required',
                    'string',
                    'in:technical,billing'
                ],
                'subject' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[\p{L}\p{N}\s\-\_\.\,\!\?]+$/u'
                ],
                'description' => [
                    'required',
                    'string',
                    'max:1000',
                    'regex:/^[\p{L}\p{N}\s\-\_\.\,\!\?\(\)]+$/u'
                ]
            ], [
                'category.required' => 'La categoria è obbligatoria',
                'category.in' => 'La categoria selezionata non è valida',
                'subject.required' => 'L\'oggetto è obbligatorio',
                'subject.max' => 'L\'oggetto non può superare i 255 caratteri',
                'subject.regex' => 'L\'oggetto contiene caratteri non consentiti',
                'description.required' => 'La descrizione è obbligatoria',
                'description.max' => 'La descrizione non può superare i 1000 caratteri',
                'description.regex' => 'La descrizione contiene caratteri non consentiti'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();

            // Sanificazione aggiuntiva
            $validated['subject'] = strip_tags($validated['subject']);
            $validated['description'] = strip_tags($validated['description']);
            
            // Pulizia HTML se necessario
            $validated['description'] = clean($validated['description'], [
                'HTML.Allowed' => 'p,br,b,strong,i,em'
            ]);

            $user = auth()->user();

            Log::info('Nuova richiesta di supporto ricevuta', [
                'user_id' => $user->id,
                'publisher_id' => $user->publisher_id,
                'category' => $validated['category']
            ]);

            // Invia email agli admin
            try {
                $admins = User::getActiveAdmins();
                
                foreach ($admins as $admin) {
                    Mail::to($admin->email)->send(new SupportRequestNotification(
                        $validated,
                        $user
                    ));
                }

                Log::info('Email di supporto inviate con successo', [
                    'user_id' => $user->id,
                    'admin_count' => $admins->count()
                ]);

            } catch (\Exception $e) {
                Log::error('Errore invio email richiesta supporto', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);

                // Non lanciamo l'eccezione per non bloccare la risposta
                // ma logghiamo l'errore
            }

            return response()->json([
                'success' => true,
                'message' => 'La tua richiesta di supporto è stata inviata con successo'
            ]);

        } catch (ValidationException $e) {
            Log::warning('Validazione fallita per richiesta supporto', [
                'user_id' => auth()->id(),
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore di validazione',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Errore nell\'invio della richiesta di supporto', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'invio della richiesta. Riprova più tardi.'
            ], 500);
        }
    }

}