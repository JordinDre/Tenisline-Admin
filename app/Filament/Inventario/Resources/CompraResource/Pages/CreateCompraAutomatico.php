<?php

namespace App\Filament\Inventario\Resources\CompraResource\Pages;

use App\Filament\Inventario\Resources\CompraResource;
use App\Models\Banco;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\TipoPago;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

class CreateCompraAutomatico extends CreateRecord
{
    protected static string $resource = CompraResource::class;

    protected static ?string $slug = 'create-automatico';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('subtotal')
                    ->required()
                    ->inputMode('decimal')
                    ->rule('numeric')
                    ->minValue(1),
                TextInput::make('total')
                    ->required()
                    ->inputMode('decimal')
                    ->rule('numeric')
                    ->minValue(1),
                Select::make('bodega_id')
                    ->relationship('bodega', 'bodega')
                    ->preload()
                    ->searchable()
                    ->required(),
                Select::make('proveedor_id')
                    ->relationship('proveedor', 'name', fn (Builder $query) => $query->role('proveedor'))
                    ->searchable(),
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->dehydrated(false)
                    ->required(),
                Textarea::make('observaciones'),
                Select::make('tipo_pago_id')
                    ->label('Tipo de Pago')
                    ->relationship('tipoPago', 'tipo_pago')
                    ->preload()
                    ->placeholder('Seleccione')
                    ->live()
                    ->required()
                    ->searchable(),
                Repeater::make('pagos')
                    ->relationship('pagos')
                    ->defaultItems(0)
                    ->schema([
                        Select::make('tipo_pago_id')
                            ->label('Forma de Pago')
                            ->relationship('tipoPago', 'tipo_pago', fn (Builder $query) => $query->whereIn('tipo_pago', TipoPago::FORMAS_PAGO))
                            ->required()
                            ->live()
                            ->columnSpan(['sm' => 1, 'md' => 2])
                            ->searchable()
                            ->preload(),
                        TextInput::make('monto')
                            ->label('Monto')
                            ->prefix('Q')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $set('total', $get('monto'));
                            })
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->minValue(1)
                            ->required(),
                        Hidden::make('total'),
                        Hidden::make('user_id')
                            ->default(auth()->user()->id),
                        TextInput::make('no_documento')
                            ->label('No. Documento')->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    if (
                                        Pago::where('banco_id', $get('banco_id'))
                                            ->where('fecha_transaccion', $get('fecha_transaccion'))
                                            ->where('no_documento', $value)
                                            ->exists()
                                    ) {
                                        $fail('La combinación de Banco, Fecha de Transacción y No. Documento ya existe en los pagos.');
                                    }
                                },
                            ])
                            ->required(),
                        TextInput::make('no_autorizacion')
                            ->label('No. Autorización')
                            ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                            ->required(),
                        TextInput::make('no_auditoria')
                            ->label('No. Auditoría')
                            ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                            ->required(),
                        TextInput::make('afiliacion')
                            ->label('Afiliación')
                            ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                            ->required(),
                        Select::make('cuotas')
                            ->options([1 => 1, 3 => 3, 6 => 6, 9 => 9, 12 => 12])
                            ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                            ->required(),
                        TextInput::make('nombre_cuenta')
                            ->visible(fn (Get $get) => $get('tipo_pago_id') == 6 && $get('tipo_pago_id') != null)
                            ->required(),
                        Select::make('banco_id')
                            ->label('Banco')
                            ->columnSpan(['sm' => 1, 'md' => 2])
                            ->required()
                            ->relationship(
                                'banco',
                                'banco',
                                function ($query) {
                                    return $query->whereIn('banco', Banco::BANCOS_DISPONIBLES);
                                }
                            )
                            ->searchable()
                            ->preload(),
                        DatePicker::make('fecha_transaccion')
                            ->default(now())
                            ->required(),
                        FileUpload::make('imagen')
                            ->image()
                            ->downloadable()
                            ->label('Imágen')
                            ->imageEditor()
                            ->disk(config('filesystems.disks.s3.driver'))
                            ->directory(config('filesystems.default'))
                            ->visibility('public')
                            ->appendFiles()
                            ->maxSize(5000)
                            ->resize(50)
                            ->openable()
                            ->columnSpan(['sm' => 1, 'md' => 3])
                            ->optimize('webp'),
                    ]),
                Repeater::make('Cliente')
                    ->relationship('factura')
                    ->schema([
                        TextInput::make('fel_uuid')
                            ->required()
                            ->label('No. Autorización'),
                        TextInput::make('fel_numero')
                            ->required()
                            ->label('No. DTE'),
                        TextInput::make('fel_serie')
                            ->required()
                            ->label('No. Serie'),
                        Hidden::make('user_id')
                            ->default(auth()->user()->id),
                    ]),
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

    protected function beforeCreate(): void
    {
        $fecha = $this->data['fecha'];

        $productos = Producto::whereDate('created_at', $fecha)->get();

        if ($productos->isEmpty()) {
            Notification::make()
                ->title('Error al crear la compra')
                ->body('No existe productos con esa fecha de creación')
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
                'producto_id' => $p->id,
                'cantidad' => 1,
                'precio' => $p->precio_costo ?? 0,
                'subtotal' => $p->precio_costo ?? 0,
            ]);
        }
    }
}
