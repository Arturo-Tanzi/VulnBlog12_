<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!$request->user()->isAdmin()){
            return redirect()->route('article.index')->with('message','Not authorized');
        }
        return $next($request); //Ritorna la richiesta al prossimo middleware o al controller se l'utente è un admin
    }

    public function toggleUsersAdmin($id){
        $user = User::find($id); //Trova l'utente nel database in base all'ID fornito
        $user->is_admin = !$user->is_admin; //Inverte il valore del campo is_admin dell'utente (se è true diventa false, e viceversa)
        
        Log::info("User $user->email has been" . ($user->is_admin ? " promoted" : " demoted" . "to Admin at " . now(). "from " . $request->ip()));
        // Logga l'evento di cambio dello status admin dell'utente con un messaggio informativo che include l'email dell'utente, se è stato promosso o declassato a admin, la data e l'ora del cambio di status, e l'indirizzo IP da cui è avvenuto il cambio

        $user->save(); //Salva le modifiche al database
        return back();
    }
}
