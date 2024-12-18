<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Publisher;
use App\Mail\AccountVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    protected array $vatConfig = [
        'supported_countries' => [
            'IT' => 'Italia',
            'FR' => 'Francia',
            'DE' => 'Germania',
            'ES' => 'Spagna',
            'GB' => 'Regno Unito',
            'CY' => 'Cipro',
            'IE' => 'Irlanda',
            'AA' => 'Altro',
        ],
        'rules' => [
            'IT' => [
                'length' => 11,
                'pattern' => '/^\d{11}$/',
                'description' => 'Numero di 11 cifre'
            ],
            'FR' => [
                'length' => 11,
                'pattern' => '/^[A-Z0-9]{2}\d{9}$/',
                'description' => 'Lettera + 11 cifre'
            ],
            'DE' => [
                'length' => 9,
                'pattern' => '/^\d{9}$/',
                'description' => '9 cifre'
            ],
            'ES' => [
                'length' => 9,
                'pattern' => '/^[A-Z0-9]\d{7}[A-Z0-9]$/',
                'description' => '1 carattere + 7 cifre + 1 carattere'
            ],
            'GB' => [
                'length' => 9,
                'pattern' => '/^\d{9}$/',
                'description' => '9 cifre'
            ],
            'CY' => [
                'length' => 9,
                'pattern' => '/^\d{9}$/',
                'description' => '9 cifre'
            ],
            'IE' => [
                'length' => 9,
                'pattern' => '/^\d{7}[A-Z]{1,2}$/',
                'description' => '7 cifre seguite da 1 o 2 lettere'
            ],
            'AA' => [
            'pattern' => '/^[A-Za-z0-9]{1,30}$/',
            'description' => 'Inserisci un valore alfanumerico massimo 30 caratteri'
            ]
        ]
    ];


    public function show()
    {
        return view('auth.register', ['countries' => $this->vatConfig['supported_countries']]);
    }


    protected function validateVatNumber($countryCode, $number)
{
    $rules = $this->vatConfig['rules'];

    if (!isset($rules[$countryCode])) {
        return [
            'valid' => false,
            'message' => 'Paese non supportato'
        ];
    }

    $cleanNumber = preg_replace('/[\s\-\.]/', '', $number);

    // Caso speciale per 'AA'
    if ($countryCode === 'AA') {
        if (strlen($cleanNumber) > 30) {
            return [
                'valid' => false,
                'message' => 'Il numero non può superare i 30 caratteri'
            ];
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $cleanNumber)) {
            return [
                'valid' => false,
                'message' => 'Sono ammessi solo caratteri alfanumerici'
            ];
        }

        return [
            'valid' => true,
            'cleanNumber' => $cleanNumber
        ];
    }

    // Validazione normale per gli altri paesi
    if (strlen($cleanNumber) !== $rules[$countryCode]['length']) {
        return [
            'valid' => false,
            'message' => "Il numero deve essere di {$rules[$countryCode]['length']} caratteri per $countryCode"
        ];
    }

    if (!preg_match($rules[$countryCode]['pattern'], $cleanNumber)) {
        return [
            'valid' => false,
            'message' => "Formato non valido per il paese selezionato"
        ];
    }

    return [
        'valid' => true,
        'cleanNumber' => $cleanNumber
    ];
}

    public function checkVat(Request $request)
    {
        try {
            $request->validate([
                'country_code' => ['required', 'string', 'size:2', 'in:' . implode(',', array_keys($this->vatConfig['supported_countries']))],
                'vat_number' => 'required|string'
            ]);

            $validation = $this->validateVatNumber($request->country_code, $request->vat_number);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message']
                ], 422);
            }

            $fullVatNumber = $request->country_code . $validation['cleanNumber'];
            $publisher = Publisher::where('vat_number', $fullVatNumber)->first();

            Log::info('Verifica partita IVA', [
                'country_code' => $request->country_code,
                'vat_number' => $request->vat_number,
                'full_vat' => $fullVatNumber,
                'exists' => (bool) $publisher
            ]);

            if ($publisher) {
                return response()->json([
                    'exists' => true,
                    'publisher' => [
                        'company_name' => $publisher->company_name,
                        'legal_name' => $publisher->legal_name,
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

    public function register(Request $request)
{
    try {
        // Validazione base
        $baseValidation = [
            'country_code' => ['required', 'string', 'size:2', 'in:' . implode(',', array_keys($this->vatConfig['supported_countries']))],
            'vat_number' => 'required|string',
            'company_name' => 'required|string|max:255',
            'legal_name' => 'required|string|max:255',
            'county' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|size:5',
            'iban' => 'required|string',
            'swift' => 'max:11',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'privacy_accepted' => 'required|boolean|accepted',
        ];

        $validatedData = $request->validate($baseValidation);

        // Validazione specifica VAT
        $vatValidation = $this->validateVatNumber($validatedData['country_code'], $validatedData['vat_number']);
        if (!$vatValidation['valid']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'vat_number' => [$vatValidation['message']]
            ]);
        }

        DB::beginTransaction();

        try {
            $role = Role::where('code', 'publisher')->first();

            if (!$role) {
                Log::error('Ruolo non trovato', [
                    'role_code' => 'publisher',
                    'email' => $validatedData['email']
                ]);
                throw new \Exception("Ruolo 'publisher' non trovato");
            }

            $fullVatNumber = $validatedData['country_code'] . $vatValidation['cleanNumber'];

            $publisher = Publisher::where('vat_number', $fullVatNumber)->first();

            if (!$publisher) {
                $publisher = Publisher::create([
                    'vat_number' => $fullVatNumber,
                    'company_name' => $validatedData['company_name'],
                    'legal_name' => $validatedData['legal_name'],
                    'county' => $validatedData['county'],
                    'city' => $validatedData['city'],
                    'postal_code' => $validatedData['postal_code'],
                    'iban' => $validatedData['iban'],
                    'swift' => $validatedData['swift'],
                    'is_active' => true
                ]);

                // Creazione del record ax_data associato
                $publisher->axData()->create([
                    'ax_vend_account' => null,
                    'ax_vend_id' => null,
                    'vend_group' => null,
                    'party_type' => null,
                    'tax_withhold_calculate' => null,
                    'item_id' => null,
                    'ax_vat_number' => null,
                    'email' => null,
                    'cost_profit_center' => null,
                    'address_country' => null,
                    'address_country_id' => null,
                    'address_county' => null,
                    'address_county_id' => null,
                    'address_city' => null,
                    'address_city_zip' => null,
                    'address_street' => null,
                    'payment' => null,
                    'payment_mode' => null,
                    'currency_code' => null,
                    'sales_tax_group' => null,
                    'number_sequence_group_id' => null
                ]);
            }

            $activationToken = Str::random(64);
            $user = User::create([
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role_id' => $role->id,
                'publisher_id' => $publisher->id,
                'is_active' => false,
                'activation_token' => $activationToken,
                'privacy_accepted' => $validatedData['privacy_accepted'],
                'privacy_verified_at' => now()
            ]);

            $verificationUrl = url("/verify-email/{$activationToken}");
            Mail::to($user->email)->send(new AccountVerification($user, $verificationUrl));

            DB::commit();

            Log::info('Registrazione completata con successo', [
                'user_id' => $user->id,
                'publisher_id' => $publisher->id
            ]);

            $successMessage = 'Registrazione completata con successo! Controlla la tua email per attivare l\'account.';

            if ($request->wantsJson()) {
                // Imposta il messaggio in sessione prima di restituire la risposta JSON
                session()->flash('success', $successMessage);

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'redirect' => route('login')
                ]);
            }

            return redirect()->route('login')->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::warning('Errore di validazione durante la registrazione', [
            'errors' => $e->errors()
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Errore di validazione',
                'errors' => $e->errors()
            ], 422);
        }

        return redirect()->back()
            ->withErrors($e->errors())
            ->withInput();

    } catch (\Exception $e) {
        Log::error('Errore durante la registrazione', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante la registrazione.',
                'errors' => ['general' => $e->getMessage()]
            ], 500);
        }

        return redirect()->back()
            ->withInput()
            ->withErrors(['general' => 'Si è verificato un errore durante la registrazione.']);
    }
}
}