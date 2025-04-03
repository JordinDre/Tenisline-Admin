<?php

namespace App\Filament\Ventas\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\TotalVenta;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Ventas\Resources\TotalVentaResource\Pages;
use App\Filament\Ventas\Resources\TotalVentaResource\RelationManagers;

class TotalVentaResource extends Resource
{
    protected static ?string $model = TotalVenta::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $pluralModelLabel = 'Deposito Ventas';

    protected static ?string $navigationLabel = 'Deposito Ventas';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 2;
    
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create'
        ];
    }

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Grid::make(2)->schema([
                DatePicker::make('fecha_transaccion')
                    ->label('Fecha de Depósito')
                    ->required(),
            ]),
            Repeater::make('pagos')
                ->relationship()
                ->label('Información de Pago')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('no_documento')
                            ->label('No. Documento')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('monto')
                            ->label('Monto')
                            ->numeric()
                            ->required()
                            ->rules([
                                function (callable $get) {
                                    return function (string $attribute, $value, callable $fail) use ($get) {
                                        $fecha = $get('../../fecha_transaccion') ?? now()->toDateString();

                                        $totalVentasEfectivo = DB::table('ventas')
                                            ->join('pagos', function ($join) {
                                                $join->on('ventas.id', '=', 'pagos.pagable_id')
                                                     ->where('pagos.pagable_type', 'App\Models\Venta')
                                                     ->where('pagos.tipo_pago_id', 1); 
                                            })
                                            ->whereDate('pagos.fecha_transaccion', $fecha)
                                            ->sum('pagos.total');

                                        if ($value != $totalVentasEfectivo) {
                                            $fail("El monto ingresado Q.$value no coincide con el total de ventas en efectivo del día Q.$totalVentasEfectivo.");
                                        }
                                    };
                                }
                            ]),

                        FileUpload::make('imagen')
                            ->image()
                            ->downloadable()
                            ->label('Imagen')
                            ->disk(config('filesystems.disks.s3.driver'))
                            ->directory(config('filesystems.default'))
                            ->visibility('public')
                            ->appendFiles()
                            ->openable()
                            ->columnSpan(['sm' => 1, 'md' => 3]),
                    ]),
                ])
                ->defaultItems(1),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pagos.monto')
                    ->label('Monto')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pagos.no_documento')
                    ->label('No. Documento'),
                Tables\Columns\TextColumn::make('fecha_transaccion')
                    ->label('Fecha del Deposito'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTotalVentas::route('/'),
            'create' => Pages\CreateTotalVenta::route('/create'),
            'edit' => Pages\EditTotalVenta::route('/{record}/edit'),
        ];
    }
}
