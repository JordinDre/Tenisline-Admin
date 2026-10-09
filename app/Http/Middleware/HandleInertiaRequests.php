<?php

namespace App\Http\Middleware;

use App\Models\Guia;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? $request->user()->load('roles') : null,
                'carrito' => $request->user()
                    ? $request->user()->load(['carrito.producto.marca'])->carrito
                    : [],
                'url' => config('filesystems.disks.s3.url'),
                'envio_gratis' => Guia::ENVIO_GRATIS,
                'envio' => Guia::ENVIO,
            ],
            'tienda' => [
                'nombre' => config('tienda.nombre'),
                'eslogan' => config('tienda.eslogan'),
                'sucursales' => config('tienda.sucursales'),
                // Avisos de la barra superior, configurables en Filament > Promociones web
                'avisos' => fn () => \Illuminate\Support\Facades\Cache::remember('tienda:avisos', 60, fn () => collect(\App\Models\Tienda::first()?->contenido ?? [])
                    ->where('type', 'aviso')
                    ->pluck('data')
                    ->filter(fn ($a) => ! empty($a['texto']) && ($a['activo'] ?? true))
                    ->map(fn ($a) => ['texto' => $a['texto'], 'enlace' => $a['enlace'] ?? null, 'cta' => $a['boton'] ?? null])
                    ->values()),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
