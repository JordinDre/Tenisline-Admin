<?php

namespace App\Filament\Inventario\Resources\TrasladoResource\Pages;

use App\Filament\Inventario\Resources\TrasladoResource;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\TrasladoController;
use App\Models\Producto;
use App\Models\Traslado;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Kenepa\ResourceLock\Resources\Pages\Concerns\UsesResourceLock;

class ConfirmTraslado extends EditRecord
{
    use UsesResourceLock;

    protected static string $resource = TrasladoResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Confirmar '.$this->record->id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)
                    ->schema([
                        Select::make('salida_id')
                            ->relationship(
                                'salida',
                                'bodega',
                            )
                            ->preload()
                            ->disabled()
                            ->searchable()
                            ->disableOptionWhen(fn (string $value, Get $get): bool => $value == $get('entrada_id'))
                            ->columnSpan(2)
                            ->required(),
                        Select::make('entrada_id')
                            ->relationship(
                                'entrada',
                                'bodega',
                            )
                            ->preload()
                            ->disabled()
                            ->searchable()
                            ->disableOptionWhen(fn (string $value, Get $get): bool => $value == $get('salida_id'))
                            ->columnSpan(2)
                            ->required(),
                    ]),
                Textarea::make('observaciones')
                    ->disabled()
                    ->columnSpanFull(),
                Repeater::make('detalles')
                    ->label('')
                    ->relationship()
                    ->columns(['default' => 4, 'md' => 6, 'lg' => 1, 'xl' => 6])
                    ->grid([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        Select::make('producto_id')
                            ->label('Producto')
                            ->relationship('producto', 'descripcion', function ($query) {
                                $query->withTrashed(); // Incluir productos eliminados
                            })
                            ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductos($record, '', $get('../../entrada_id')))
                            ->allowHtml()
                            ->disabled()
                            ->searchable(['id'])
                            ->getSearchResultsUsing(function (string $search, Get $get): array {
                                return ProductoController::searchProductos($search, '', $get('../../entrada_id'));
                            })
                            ->optionsLimit(20)
                            ->columnSpanFull()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->required(),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('cantidad_enviada')
                                    ->label('Cantidad Enviada')
                                    ->disabled(),
                                TextInput::make('cantidad_recibida')
                                    ->label('Cantidad Recibida')
                                    ->minValue(0)
                                    ->maxValue(fn (Get $get) => $get('cantidad_enviada'))
                                    ->inputMode('decimal')
                                    ->rule('numeric')
                                    ->required(),
                            ]),
                    ])->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addable(false),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            Action::make('confirm')
                ->label('Confirmar')
                ->requiresConfirmation()
                ->color('success')
                ->closeModalByClickingAway(false)
                ->icon('heroicon-o-check-circle')
                ->action(function (): void {
                    $this->record->update($this->form->getState()); // Actualiza el traslado con los datos del formulario
                    TrasladoController::confirmar($this->record);
                }),
        ];
    }
}
