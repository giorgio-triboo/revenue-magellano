<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->isAdmin();
    }

    public function rules()
    {
        $userId = $this->route('user')->id;

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $userId],
            'role_id' => ['required', 'exists:roles,id'],
        ];

        // Aggiungi regole per i campi publisher solo se necessario
        if ($this->has('company_name')) {
            $rules = array_merge($rules, [
                'company_name' => ['required', 'string', 'max:255'],
                'legal_name' => ['required', 'string', 'max:255'],
                'vat_number' => ['required', 'string', 'size:11'],
                'website' => ['required', 'string', 'max:255'],
                'state' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'postal_code' => ['required', 'string', 'size:5'],
                'iban' => ['required', 'string', 'size:27'],
                'swift' => ['required', 'string', 'between:8,11']
            ]);
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'first_name.required' => 'Il nome è obbligatorio',
            'last_name.required' => 'Il cognome è obbligatorio',
            'email.required' => 'L\'email è obbligatoria',
            'email.email' => 'L\'email deve essere un indirizzo valido',
            'email.unique' => 'Questo indirizzo email è già in uso',
            'role_id.required' => 'Il ruolo è obbligatorio',
            'role_id.exists' => 'Il ruolo selezionato non è valido',
            // ... altri messaggi di errore personalizzati
        ];
    }
}