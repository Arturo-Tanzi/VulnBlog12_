<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
}
