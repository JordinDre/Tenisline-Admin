<?php

namespace App\Providers\Filament;

use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Kainiklas\FilamentScout\FilamentScoutPlugin;
use Kenepa\Banner\BannerPlugin;
use Kenepa\ResourceLock\ResourceLockPlugin;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class InventarioPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('inventario')
            ->path('inventario')
            ->login()
            ->profile()
            ->brandName('TenisLine')
            ->brandLogo(asset('images/logo.png'))
            ->darkModeBrandLogo(asset('images/logoBlanco.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/icono.png'))
            ->font('Poppins')
            ->databaseTransactions()
            ->maxContentWidth(MaxWidth::Full)
            ->spa()
            ->unsavedChangesAlerts()
            ->discoverResources(in: app_path('Filament/Inventario/Resources'), for: 'App\\Filament\\Inventario\\Resources')
            ->discoverPages(in: app_path('Filament/Inventario/Pages'), for: 'App\\Filament\\Inventario\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Inventario/Widgets'), for: 'App\\Filament\\Inventario\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \Hasnayeen\Themes\Http\Middleware\SetTheme::class,
                /* InventarioPanel::class, */
            ])
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('15rem')
            ->databaseNotifications()
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Tienda')
                    ->url(fn (): string => '/')
                    ->icon('tabler-building-store'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->breadcrumbs(false)
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->selectable(true)
                    ->editable(true),
                \Hasnayeen\Themes\ThemesPlugin::make()->canViewThemesPage(fn () => auth()->user() ? auth()->user()->hasAnyRole(User::ROLES_ADMIN) : false),
                ResourceLockPlugin::make(),
                BannerPlugin::make()->persistsBannersInDatabase()->disableBannerManager(),
                /* FilamentScoutPlugin::make()
                    ->useMeilisearch(), */
            ]);
    }
}
