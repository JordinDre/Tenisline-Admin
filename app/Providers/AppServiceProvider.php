<?php

namespace App\Providers;

use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Vite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Filament\Notifications\Notification;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Filament::serving(function () {

            Filament::registerRenderHook(
                'panels::page.start',
                function () {

                    if (! \App\Filament\Ventas\Resources\VentaResource::hayBloqueo()) {
                        return null;
                    }

                    return view('filament.bloqueo-ventas');
                }
            );

        });

        Vite::prefetch(concurrency: 3);
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Table::$defaultNumberLocale = 'es_GT';

        Model::unguard();
        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
            $panelSwitch->modalHeading('Cambiar de Panel');
            $panelSwitch->modalWidth('lg');
            $panelSwitch->icons([
                'admin' => 'heroicon-c-adjustments-vertical',
                'inventario' => 'heroicon-m-inbox-stack',
                'ventas' => 'heroicon-c-shopping-cart',
            ], $asImage = false);
            $panelSwitch->iconSize(25);
            $panelSwitch->labels([
                'admin' => 'Admin',
                'inventario' => 'Inventario',
                'ventas' => 'Ordenes y Ventas',
            ]);
        });

        FilamentColor::register([
            'primary' => Color::Sky,
            'danger' => Color::Red,
            'zinc' => Color::Gray,
            'info' => Color::Blue,
            'success' => Color::Green,
            'warning' => Color::Amber,
            'yellow' => Color::Yellow,
            'orange' => Color::Orange,
            'indigo' => Color::Indigo,
            'pink' => Color::Pink,
            'violet' => Color::Violet,
            'lime' => Color::Lime,
            'teal' => Color::Teal,
            'purple' => Color::Purple,
            'rose' => Color::Rose,
        ]);
    }
}
