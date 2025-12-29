<?php

namespace App\Providers\Filament;

use Filament\Panel;
use App\Models\User;
use Filament\PanelProvider;
use Kenepa\Banner\BannerPlugin;
use Filament\Navigation\MenuItem;
use App\Http\Middleware\AdminPanel;
use Filament\Support\Enums\MaxWidth;
use Filament\Http\Middleware\Authenticate;
use Kenepa\ResourceLock\ResourceLockPlugin;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Kainiklas\FilamentScout\FilamentScoutPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use App\Http\Middleware\BloquearSistemaPorVentaPendiente;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            /* ->registration() */
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
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->databaseNotifications()
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('15rem')
            ->breadcrumbs(false)
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->selectable(true)
                    ->editable(true),
                ResourceLockPlugin::make(),
                \TomatoPHP\FilamentPWA\FilamentPWAPlugin::make(),
                \Hasnayeen\Themes\ThemesPlugin::make()->canViewThemesPage(fn () => auth()->user() ? auth()->user()->hasAnyRole(User::ROLES_ADMIN) : false),
                /* FilamentSpatieLaravelBackupPlugin::make(), */
                ActivitylogPlugin::make()
                    ->label('Actividad')
                    ->pluralLabel('Actividades')
                    ->navigationIcon('tabler-history-toggle')
                    ->navigationSort(3),
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
                BannerPlugin::make()->persistsBannersInDatabase()
                    ->navigationLabel('Banners')
                    ->title('Banners')
                    ->subheading('Administra los Banners')
                /* ->bannerManagerAccessPermission('banner-manager') */,
                /* FilamentScoutPlugin::make()
                    ->useMeilisearch(), */
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Tienda')
                    ->url(fn (): string => '/catalogo')
                    ->icon('tabler-building-store'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
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
                AdminPanel::class,
                BloquearSistemaPorVentaPendiente::class,
            ]);
    }
}
