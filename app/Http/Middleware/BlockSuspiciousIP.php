<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSuspiciousIP
{
    protected $maxAttempts = 5; // Numero massimo di tentativi consentiti
    protected $decayMinutes = 1; // Tempo di blocco in minuti
    protected $blockMinutes = 1; // Tempo di blocco in minuti
    /**
     * Handle an incoming request.
     *
     * @param  Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $key = $this->throttleKey($ip);

        if (Cache::has($key . ':blocked')) {
            Session::flash('error', "Too many attempts. Your IP has been blocked for $this->blockMinutes minute(s).");
            return redirect()->back();
        }
        if(Cache::has($key)){
            $attempts = Cache::increment($key);
        if ($attempts > $this->maxAttempts) {
            Cache::put($key . ':blocked', true, $this->blockMinutes * 60);
            Log::warning("IP $ip has been blocked for $this->blockMinutes minute(s) due to too many attempts.");
            Session::flash('error', "Too many attempts. Your IP has been blocked for $this->blockMinutes minute(s).");
            return redirect()->back();
        }
        } else {
            Cache::put($key, 1, $this->decayMinutes * 60);
        }
        return $next($request);
    }
    protected function throttleKey($ip)
    {
        return 'login_attempts:' . $ip;
    }
}
