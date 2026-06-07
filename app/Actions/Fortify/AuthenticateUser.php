<?php

namespace App\Actions\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthenticateUser
{
    public function __invoke(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password'); //Recupero le credenziali di email e password dalla richiesta di login
        $user = \App\Models\User::where('email', $credentials['email'])->first(); //Cerco l'utente nel database in base all'email fornita nelle credenziali. Se l'utente esiste, viene restituito un oggetto User, altrimenti viene restituito null.
        if (!$user) {
           $pepper = config('app.pepper'); // Recupera il pepper dalla configurazione
           $passwordWithSaltPepper = $credentials['password'] . $user->salt . $pepper; // Combina la password con il salt e il pepper
           // Verifica la password con salt e pepper utilizzando Hash::check, che supporta bcrypt e altri algoritmi di hashing sicuri
           if (Hash::check($passwordWithSaltPepper, $user->password)) {
               Auth::login($user);
               Log::info("User $user->name logged in at " . now(). "from " . $request->ip()); // Logga l'evento di login con il nome dell'utente, la data e l'ora del login, e l'indirizzo IP da cui è avvenuto il login
               return $user;
           }

        }
        return null; // Ritorna null se l'autenticazione fallisce
    }
}