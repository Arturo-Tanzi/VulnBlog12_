<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::min(12)
            ->letters() // Richiede almeno una lettera
            ->mixedCase() // Richiede sia lettere maiuscole che minuscole
            ->numbers() // Richiede almeno un numero
            ->symbols() // Richiede almeno un simbolo
            ->uncompromised() // Verifica che la password non sia stata compromessa in una violazione dei dati
        , 'confirmed'];
    }
}
