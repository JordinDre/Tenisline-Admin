<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;

class Reportes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Reportes';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes';

    protected static string $view = 'filament.pages.reportes';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('VentasGeneral')
                ->label('Ventas General')
                ->icon('heroicon-o-document-text')
                ->modalHeading('Generar Reporte')
                ->form([
                    DatePicker::make('fecha_incial')
                        ->required(),
                    DatePicker::make('fecha_final')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $url = route('reporte.ventasgeneral', [
                        'fecha_incial' => $data['fecha_incial'],
                        'fecha_final' => $data['fecha_final'],
                    ]);

                    return response()->redirectTo($url);
                }),
            Action::make('VentasDetalle')
                ->label('Ventas Detalle')
                ->icon('heroicon-o-document-text')
                ->modalHeading('Generar Reporte')
                ->form([
                    DatePicker::make('fecha_incial')
                        ->required(),
                    DatePicker::make('fecha_final')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $url = route('reporte.ventasdetalle', [
                        'fecha_incial' => $data['fecha_incial'],
                        'fecha_final' => $data['fecha_final'],
                    ]);

                    return response()->redirectTo($url);
                }),
            Action::make('Pagos')
                ->label('Pagos')
                ->icon('heroicon-o-document-text')
                ->modalHeading('Generar Reporte')
                ->form([
                    DatePicker::make('fecha_incial')
                        ->required(),
                    DatePicker::make('fecha_final')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $url = route('reporte.pagos', [
                        'fecha_incial' => $data['fecha_incial'],
                        'fecha_final' => $data['fecha_final'],
                    ]);

                    return response()->redirectTo($url);
                }),
            Action::make('Resultados')
                ->label('Resultados')
                ->icon('heroicon-o-document-text')
                ->modalHeading('Generar Reporte Resultados')
                ->form([
                    DatePicker::make('fecha_inicial')->required(),
                    DatePicker::make('fecha_final')->required(),
                ])
                ->action(function (array $data) {
                    $url = route('reporte.resultados', [
                        'fecha_inicial' => $data['fecha_inicial'],
                        'fecha_final' => $data['fecha_final'],
                    ]);

                    return response()->redirectTo($url);
                }),

            Action::make('HistorialCliente')
                ->label('Historial Cliente')
                ->icon('heroicon-o-document-text')
                ->modalHeading('Generar Reporte')
                ->form([
                    Select::make('cliente_id')
                        ->label('Cliente')
                        ->options(
                            User::all()
                                ->mapWithKeys(function ($user) {
                                    $label = trim(($user->name ?? '').' — '.($user->razon_social ?? ''));

                                    return [$user->id => $label];
                                })
                        )
                        ->required()
                        ->columnSpan(['sm' => 1, 'md' => 9])
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    $url = route('reporte.historialcliente', [
                        'cliente_id' => $data['cliente_id'],
                    ]);

                    return response()->redirectTo($url);
                }),
            Action::make('ReporteInventario')
                ->label('Reporte de Inventario')
                ->icon('heroicon-o-document-text')
                ->modalHeading('Generar Reporte')
                ->action(function () {
                    $url = route('reporte.reporteinventario');

                    return response()->redirectTo($url);
                }),
        ];
    }
}
