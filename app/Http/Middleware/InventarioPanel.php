<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventarioPanel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /* if (auth()->check() && (auth()->user()->hasRole('cliente') || auth()->user()->hasRole('proveedor'))) {
            return redirect('/');
        } */

        return $next($request);
    }
}
