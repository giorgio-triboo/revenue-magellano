<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class RegisterPage extends Page
{
    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return '/register';
    }

    /**
     * Assert that the browser is on the page.
     *
     * @param  Browser  $browser
     * @return void
     */
    public function assert(Browser $browser)
    {
        $browser->assertPathIs($this->url())
                ->assertSee('Registrazione')
                ->assertPresent('#vat_number');
    }

    /**
     * Fill step 1: VAT number
     */
    public function fillStep1(Browser $browser, $vatNumber)
    {
        $browser->type('vat_number', $vatNumber)
                ->press('Avanti');
    }

    /**
     * Fill step 2: Company data
     */
    public function fillStep2(Browser $browser, $companyName, $legalName, $website)
    {
        $browser->type('company_name', $companyName)
                ->type('legal_name', $legalName)
                ->type('website', $website)
                ->press('Avanti');
    }

    /**
     * Fill step 3: Address
     */
    public function fillStep3(Browser $browser, $state, $city, $postalCode)
    {
        $browser->type('state', $state)
                ->type('city', $city)
                ->type('postal_code', $postalCode)
                ->press('Avanti');
    }

    /**
     * Fill step 4: Bank details
     */
    public function fillStep4(Browser $browser, $iban, $swift)
    {
        $browser->type('iban', $iban)
                ->type('swift', $swift)
                ->press('Avanti');
    }

    /**
     * Fill step 5: Personal data
     */
    public function fillStep5(Browser $browser, $firstName, $lastName)
    {
        $browser->type('first_name', $firstName)
                ->type('last_name', $lastName)
                ->press('Avanti');
    }

    /**
     * Fill step 6: Credentials
     */
    public function fillStep6(Browser $browser, $email, $password)
    {
        $browser->type('email', $email)
                ->type('password', $password)
                ->type('password_confirmation', $password)
                ->press('Completa Registrazione');
    }

    /**
     * Complete full registration
     */
    public function completeRegistration(Browser $browser, array $data)
    {
        $this->fillStep1($browser, $data['vat_number']);
        $this->fillStep2($browser, $data['company_name'], $data['legal_name'], $data['website']);
        $this->fillStep3($browser, $data['state'], $data['city'], $data['postal_code']);
        $this->fillStep4($browser, $data['iban'], $data['swift']);
        $this->fillStep5($browser, $data['first_name'], $data['last_name']);
        $this->fillStep6($browser, $data['email'], $data['password']);
    }
}