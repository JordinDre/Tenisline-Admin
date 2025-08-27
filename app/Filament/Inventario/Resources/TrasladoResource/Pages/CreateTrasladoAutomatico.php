<?php

namespace App\Filament\Inventario\Resources\TrasladoResource\Pages;

use Exception;
use Filament\Actions;
use App\Models\Bodega;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Producto;
use Filament\Forms\Form;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Filament\Inventario\Resources\TrasladoResource;

class CreateTrasladoAutomatico extends CreateRecord
{
    protected static string $resource = TrasladoResource::class;

    protected static ?string $slug = 'create-automatico';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
            Select::make('salida_id')
                ->relationship(
                    'salida',
                    'bodega',
                    fn (Builder $query) => $query->whereHas('user', function ($query) {
                        $query->where('user_id', auth()->user()->id);
                    })
                )
                ->preload()
                ->searchable()
                ->disableOptionWhen(fn (string $value, Get $get): bool => $value == $get('entrada_id'))
                ->columnSpan(2)
                ->required(),
            Select::make('entrada_id')
                ->relationship(
                    'entrada',
                    'bodega',
                    fn (Builder $query) => $query->whereHas('user', function ($query) {
                        $query->whereNotIn('bodega_id', [Bodega::TRASLADO, Bodega::MAL_ESTADO]);
                    })
                )
                ->preload()
                ->searchable()
                ->disableOptionWhen(fn (string $value, Get $get): bool => $value == $get('salida_id'))
                ->columnSpan(2)
                ->required(),
            Hidden::make('emisor_id')
                ->default(auth()->user()->id)
                ->dehydrated(true),
            DatePicker::make('fecha')
                ->label('Fecha')
                ->dehydrated(false)
                ->required(),
            Textarea::make('observaciones')
                ->columnSpanFull(),
            Repeater::make('detalles')
                ->relationship('detalles') 
                ->schema([
                    Select::make('producto_id')
                        ->relationship('producto', 'descripcion')
                        ->disabled(),
                ])
                ->hidden() 
                ->dehydrated(true),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['emisor_id'] = auth()->id(); 
        return $data;
    }

    protected function beforeCreate(): void
    {
        $fecha = $this->data['fecha'];

        $productos = Producto::whereDate('created_at', $fecha)->get();

        if ($productos->isEmpty()) {
            Notification::make()
                ->title('Error al crear el traslado')
                ->body("No existe productos con esa fecha de creación")
                ->danger()
                ->send();

            $this->halt(); 
        }
    }

    protected function afterCreate(): void
    {
        $fecha = $this->data['fecha'];
        
        $productos = Producto::whereDate('created_at', $fecha)->get();

        foreach ($productos as $p) {
            $this->record->detalles()->create([
                'producto_id'      => $p->id,
                'cantidad_enviada' => 1,
            ]);
        }
    }

}
