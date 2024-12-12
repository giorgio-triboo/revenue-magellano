<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Publisher;
use App\Models\Role;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\AccountVerification;
use Tests\Browser\Pages\RegisterPage;

class RegistrationTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        
        // Crea i ruoli necessari
        Role::create([
            'name' => 'Publisher',
            'code' => 'publisher',
            'description' => 'Publisher role'
        ]);
        
        Role::create([
            'name' => 'Operative',
            'code' => 'operative',
            'description' => 'Operative role'
        ]);
    }

    /** @test */
    public function test_registration_page_loads_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->assertSee('Registrazione')
                    ->assertSee('Partita IVA')
                    ->assertPresent('#vat_number')
                    ->assertSee('Completa tutti i passaggi per accedere alla piattaforma');
        });
    }

    /** @test */
    public function test_vat_number_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    // Test campo vuoto
                    ->press('Avanti')
                    ->assertSee('Il campo partita IVA è obbligatorio')
                    
                    // Test formato non valido
                    ->type('vat_number', '123')
                    ->press('Avanti')
                    ->assertSee('La partita IVA deve essere di 11 caratteri')
                    
                    // Test formato valido
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    ->assertDontSee('La partita IVA deve essere di 11 caratteri');
        });
    }

    /** @test */
    public function test_company_data_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    
                    // Test campi vuoti
                    ->press('Avanti')
                    ->assertSee('Il campo nome azienda è obbligatorio')
                    ->assertSee('Il campo ragione sociale è obbligatorio')
                    ->assertSee('Il campo sito web è obbligatorio')
                    
                    // Test dati validi
                    ->type('company_name', 'Test Company')
                    ->type('legal_name', 'Test Legal Name')
                    ->type('website', 'https://example.com')
                    ->press('Avanti')
                    ->assertDontSee('Il campo nome azienda è obbligatorio');
        });
    }

    /** @test */
    public function test_address_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    ->type('company_name', 'Test Company')
                    ->type('legal_name', 'Test Legal Name')
                    ->type('website', 'https://example.com')
                    ->press('Avanti')
                    
                    // Test campi vuoti
                    ->press('Avanti')
                    ->assertSee('Il campo provincia è obbligatorio')
                    ->assertSee('Il campo città è obbligatorio')
                    ->assertSee('Il campo CAP è obbligatorio')
                    
                    // Test CAP non valido
                    ->type('state', 'Milano')
                    ->type('city', 'Milano')
                    ->type('postal_code', '123')
                    ->press('Avanti')
                    ->assertSee('Il CAP deve essere di 5 caratteri')
                    
                    // Test dati validi
                    ->type('postal_code', '20100')
                    ->press('Avanti')
                    ->assertDontSee('Il CAP deve essere di 5 caratteri');
        });
    }

    /** @test */
    public function test_bank_data_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    ->type('company_name', 'Test Company')
                    ->type('legal_name', 'Test Legal Name')
                    ->type('website', 'https://example.com')
                    ->press('Avanti')
                    ->type('state', 'Milano')
                    ->type('city', 'Milano')
                    ->type('postal_code', '20100')
                    ->press('Avanti')
                    
                    // Test campi vuoti
                    ->press('Avanti')
                    ->assertSee('Il campo IBAN è obbligatorio')
                    ->assertSee('Il campo SWIFT è obbligatorio')
                    
                    // Test formato IBAN non valido
                    ->type('iban', '123')
                    ->type('swift', 'ABCD')
                    ->press('Avanti')
                    ->assertSee('L\'IBAN deve essere di 27 caratteri')
                    ->assertSee('Il codice SWIFT deve essere tra 8 e 11 caratteri')
                    
                    // Test dati validi
                    ->type('iban', 'IT60X0542811101000000123456')
                    ->type('swift', 'BCITITMM')
                    ->press('Avanti')
                    ->assertDontSee('L\'IBAN deve essere di 27 caratteri');
        });
    }

    /** @test */
    public function test_personal_data_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    ->type('company_name', 'Test Company')
                    ->type('legal_name', 'Test Legal Name')
                    ->type('website', 'https://example.com')
                    ->press('Avanti')
                    ->type('state', 'Milano')
                    ->type('city', 'Milano')
                    ->type('postal_code', '20100')
                    ->press('Avanti')
                    ->type('iban', 'IT60X0542811101000000123456')
                    ->type('swift', 'BCITITMM')
                    ->press('Avanti')
                    
                    // Test campi vuoti
                    ->press('Avanti')
                    ->assertSee('Il campo nome è obbligatorio')
                    ->assertSee('Il campo cognome è obbligatorio')
                    
                    // Test dati validi
                    ->type('first_name', 'John')
                    ->type('last_name', 'Doe')
                    ->press('Avanti')
                    ->assertDontSee('Il campo nome è obbligatorio');
        });
    }

    /** @test */
    public function test_credentials_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    ->type('company_name', 'Test Company')
                    ->type('legal_name', 'Test Legal Name')
                    ->type('website', 'https://example.com')
                    ->press('Avanti')
                    ->type('state', 'Milano')
                    ->type('city', 'Milano')
                    ->type('postal_code', '20100')
                    ->press('Avanti')
                    ->type('iban', 'IT60X0542811101000000123456')
                    ->type('swift', 'BCITITMM')
                    ->press('Avanti')
                    ->type('first_name', 'John')
                    ->type('last_name', 'Doe')
                    ->press('Avanti')
                    
                    // Test campi vuoti
                    ->press('Completa Registrazione')
                    ->assertSee('Il campo email è obbligatorio')
                    ->assertSee('Il campo password è obbligatorio')
                    
                    // Test email non valida
                    ->type('email', 'invalid-email')
                    ->press('Completa Registrazione')
                    ->assertSee('Il formato dell\'email non è valido')
                    
                    // Test password debole
                    ->type('email', 'test@example.com')
                    ->type('password', '123')
                    ->type('password_confirmation', '123')
                    ->press('Completa Registrazione')
                    ->assertSee('La password deve contenere almeno 8 caratteri')
                    
                    // Test password non coincidenti
                    ->type('password', 'Password123!')
                    ->type('password_confirmation', 'DifferentPassword123!')
                    ->press('Completa Registrazione')
                    ->assertSee('Le password non coincidono');
        });
    }

    /** @test */
    public function test_successful_registration_flow()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    // Step 1: Partita IVA
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    
                    // Step 2: Dati Aziendali
                    ->type('company_name', 'Test Company')
                    ->type('legal_name', 'Test Legal Name')
                    ->type('website', 'https://example.com')
                    ->press('Avanti')
                    
                    // Step 3: Indirizzo
                    ->type('state', 'Milano')
                    ->type('city', 'Milano')
                    ->type('postal_code', '20100')
                    ->press('Avanti')
                    
                    // Step 4: Dati Bancari
                    ->type('iban', 'IT60X0542811101000000123456')
                    ->type('swift', 'BCITITMM')
                    ->press('Avanti')
                    
                    // Step 5: Dati Personali
                    ->type('first_name', 'John')
                    ->type('last_name', 'Doe')
                    ->press('Avanti')
                    
                    // Step 6: Credenziali
                    ->type('email', 'test@example.com')
                    ->type('password', 'Password123!')
                    ->type('password_confirmation', 'Password123!')
                    ->press('Completa Registrazione')
                    
                    // Verifica successo
                    ->assertPathIs('/login')
                    ->assertSee('Registrazione completata con successo')
                    ->assertSee('Controlla la tua email per attivare l\'account');

            // Verifica creazione utente nel database
            $this->assertDatabaseHas('users', [
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'is_active' => false
            ]);

            // Verifica creazione publisher nel database
            $this->assertDatabaseHas('publishers', [
                'vat_number' => '12345678901',
                'company_name' => 'Test Company',
                'legal_name' => 'Test Legal Name'
            ]);

            // Verifica invio email
            Mail::assertSent(AccountVerification::class, function ($mail) {
                return $mail->hasTo('test@example.com');
            });
        });
    }

    /** @test */
    public function test_triboo_user_registration()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    // Completa tutti i passaggi come sopra ma con email Triboo
                    ->type('email', 'user@triboo.it')
                    ->type('password', 'Password123!')
                    ->type('password_confirmation', 'Password123!')
                    ->press('Completa Registrazione');

            // Verifica che sia stato assegnato il ruolo operativo
            $user = User::where('email', 'user@triboo.it')->first();
            $this->assertEquals('operative', $user->role->code);
        });
    }

    /** @test */
    public function test_duplicate_vat_number_with_different_user()
    {
        // Prima registrazione
        Publisher::create([
            'vat_number' => '12345678901',
            'company_name' => 'Existing Company',
            'legal_name' => 'Existing Legal Name',
            'website' => 'https://existing.com',
            'state' => 'Milano',
            'city' => 'Milano',
            'postal_code' => '20100',
            'iban' => 'IT60X0542811101000000123456',
            'swift' => 'BCITITMM'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    // Verifica che i campi vengano pre-compilati
                    ->assertValue('company_name', 'Existing Company')
                    ->assertValue('legal_name', 'Existing Legal Name')
                    ->assertValue('website', 'https://existing.com')
                    // Completa la registrazione
                    ->type('first_name', 'Second')
                    ->type('last_name', 'User')
                    ->type('email', 'second@example.com')
                    ->type('password', 'Password123!')
                    ->type('password_confirmation', 'Password123!')
                    ->press('Completa Registrazione');

            // Verifica che il nuovo utente sia associato al publisher esistente
            $user = User::where('email', 'second@example.com')->first();
            $this->assertEquals('12345678901', $user->publisher->vat_number);
        });
    }

    /** @test */
    public function test_registration_form_persistence()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    // Compila primo step
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    
                    // Compila secondo step
                    ->type('company_name', 'Test Company')
                    ->type('legal_name', 'Test Legal Name')
                    ->type('website', 'https://example.com')
                    ->press('Avanti')
                    
                    // Torna indietro
                    ->press('Indietro')
                    
                    // Verifica che i dati siano mantenuti
                    ->assertValue('company_name', 'Test Company')
                    ->assertValue('legal_name', 'Test Legal Name')
                    ->assertValue('website', 'https://example.com')
                    
                    // Torna ancora indietro
                    ->press('Indietro')
                    
                    // Verifica persistenza primo step
                    ->assertValue('vat_number', '12345678901');
        });
    }

    /** @test */
    public function test_password_strength_requirements()
    {
        $this->browse(function (Browser $browser) {
            // Naviga fino allo step delle credenziali
            $browser->visit('/register')
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    ->type('company_name', 'Test Company')
                    ->type('legal_name', 'Test Legal Name')
                    ->type('website', 'https://example.com')
                    ->press('Avanti')
                    ->type('state', 'Milano')
                    ->type('city', 'Milano')
                    ->type('postal_code', '20100')
                    ->press('Avanti')
                    ->type('iban', 'IT60X0542811101000000123456')
                    ->type('swift', 'BCITITMM')
                    ->press('Avanti')
                    ->type('first_name', 'John')
                    ->type('last_name', 'Doe')
                    ->press('Avanti')
                    
                    // Test password troppo corta
                    ->type('password', 'Aa1!')
                    ->assertSee('Minimo 8 caratteri')
                    
                    // Test password senza maiuscola
                    ->type('password', 'password123!')
                    ->assertSee('Almeno una maiuscola')
                    
                    // Test password senza minuscola
                    ->type('password', 'PASSWORD123!')
                    ->assertSee('Almeno una minuscola')
                    
                    // Test password senza numero
                    ->type('password', 'PasswordTest!')
                    ->assertSee('Almeno un numero')
                    
                    // Test password senza carattere speciale
                    ->type('password', 'Password123')
                    ->assertSee('Almeno un carattere speciale')
                    
                    // Test password valida
                    ->type('password', 'Password123!')
                    ->assertDontSee('Minimo 8 caratteri')
                    ->assertDontSee('Almeno una maiuscola')
                    ->assertDontSee('Almeno una minuscola')
                    ->assertDontSee('Almeno un numero')
                    ->assertDontSee('Almeno un carattere speciale');
        });
    }

    /** @test */
    public function test_email_uniqueness()
    {
        // Crea un utente esistente
        User::factory()->create([
            'email' => 'existing@example.com'
        ]);

        $this->browse(function (Browser $browser) {
            // Naviga fino allo step delle credenziali
            $browser->visit('/register')
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    ->type('company_name', 'Test Company')
                    ->type('legal_name', 'Test Legal Name')
                    ->type('website', 'https://example.com')
                    ->press('Avanti')
                    ->type('state', 'Milano')
                    ->type('city', 'Milano')
                    ->type('postal_code', '20100')
                    ->press('Avanti')
                    ->type('iban', 'IT60X0542811101000000123456')
                    ->type('swift', 'BCITITMM')
                    ->press('Avanti')
                    ->type('first_name', 'John')
                    ->type('last_name', 'Doe')
                    ->press('Avanti')
                    
                    // Prova ad usare un'email esistente
                    ->type('email', 'existing@example.com')
                    ->type('password', 'Password123!')
                    ->type('password_confirmation', 'Password123!')
                    ->press('Completa Registrazione')
                    ->assertSee('Questa email è già stata registrata');
        });
    }

    /** @test */
    public function test_session_timeout_handling()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    
                    // Simula timeout della sessione
                    ->script("window.localStorage.clear();");
                    
            // Ricarica la pagina
            $browser->refresh()
                    ->assertPathIs('/register')
                    ->assertSee('La sessione è scaduta. Riprova.')
                    ->assertValue('vat_number', '');
        });
    }

    /** @test */
    public function test_registration_with_malformed_data()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    // Prova ad inviare dati malformati
                    ->type('vat_number', '<script>alert("xss")</script>')
                    ->press('Avanti')
                    ->assertSee('Il formato della partita IVA non è valido')
                    
                    // Prova con URL malformato
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    ->type('website', 'not-a-url')
                    ->press('Avanti')
                    ->assertSee('Il formato del sito web non è valido');
        });
    }

    /** @test */
    public function test_registration_rate_limiting()
    {
        $this->browse(function (Browser $browser) {
            // Simula multipli tentativi di registrazione
            for ($i = 0; $i < 6; $i++) {
                $browser->visit('/register')
                        ->type('vat_number', '12345678901')
                        ->press('Avanti');
            }
            
            // Verifica il rate limiting
            $browser->assertSee('Troppi tentativi. Riprova tra qualche minuto.');
        });
    }

    /** @test */
    public function test_browser_back_button_handling()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->type('vat_number', '12345678901')
                    ->press('Avanti')
                    ->type('company_name', 'Test Company')
                    ->press('Avanti')
                    
                    // Usa il pulsante indietro del browser
                    ->back()
                    
                    // Verifica che i dati siano mantenuti
                    ->assertValue('company_name', 'Test Company')
                    ->assertPresent('#company_name');
        });
    }

    /** @test */
    public function test_registration_completion_email()
    {
        $this->browse(function (Browser $browser) {
            // Completa la registrazione
            $browser->visit('/register')
                    // ... completare tutti gli step ...
                    ->press('Completa Registrazione');

            // Verifica struttura email
            Mail::assertSent(AccountVerification::class, function ($mail) {
                return $mail->hasTo('test@example.com') &&
                       $mail->subject === 'Verifica il tuo account' &&
                       str_contains($mail->render(), 'Grazie per esserti registrato');
            });
        });
    }
}