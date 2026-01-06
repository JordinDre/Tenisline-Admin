<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use App\Models\Venta;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Symfony\Component\HttpFoundation\Response;
use App\Filament\Ventas\Resources\VentaResource;

class BloquearSistemaPorVentaPendiente
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->hasAnyRole([
            'super_admin',
            'administrador',
            'supervisor',
        ])) {
            return $next($request); 
        }

        $ventaBloqueante = Venta::where('estado', 'validacion_pago')
            ->where('created_at', '<=', now()->subHour())
            ->first();

        if (! $ventaBloqueante) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        if (str_contains($routeName, 'ventas')) {
            return $next($request);
        }

        return redirect('/ventas/ventas')
            ->with('bloqueo_sistema', true);
    }
}
