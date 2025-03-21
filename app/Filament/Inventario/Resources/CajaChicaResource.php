<?php

namespace App\Filament\Inventario\Resources;

use Filament\Forms;
use App\Models\Pago;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\CajaChica;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Inventario\Resources\CajaChicaResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use App\Filament\Inventario\Resources\CajaChicaResource\RelationManagers;

class CajaChicaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = CajaChica::class;

    protected static ?string $navigationIcon = 'tabler-box';

    protected static ?string $pluralModelLabel = 'Caja Chica';

    protected static ?string $navigationLabel = 'Caja Chica';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 3;

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
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('detalle_gasto')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('autoriza')
                            ->label('Quien autoriza')
                            ->maxLength(255),
                        
                    ]),
                Forms\Components\Repeater::make('pagos')
                    ->relationship()
                    ->label('Información de Pago')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('no_documento')
                                    ->label('No. Documento')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('monto')
                                    ->label('Monto')
                                    ->numeric()
                                    ->required(),
                                Hidden::make('user_id')
                                    ->default(auth()->user()->id),
                                FileUpload::make('imagen')
                                    ->image()
                                    ->downloadable()
                                    ->label('Imágen')
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
                Tables\Columns\TextColumn::make('detalle_gasto')
                    ->label('Detalle del gasto'),
                Tables\Columns\TextColumn::make('autoriza')
                    ->label('Quien Autorizo'),
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
            'index' => Pages\ListCajaChicas::route('/'),
            'create' => Pages\CreateCajaChica::route('/create'),
            'edit' => Pages\EditCajaChica::route('/{record}/edit'),
        ];
    }

    public static function create(array $data): ?CajaChica
{
    return DB::transaction(function () use ($data) {
        // Prepara los datos específicos para CajaChica
        $cajaChicaData = collect($data)
            ->only(['detalle_gasto', 'imagen'])
            ->toArray();

        // Crea el registro de CajaChica
        $cajaChica = CajaChica::create($cajaChicaData);

        // Prepara los datos para Pago
        if ($cajaChica) {
            $pagoData = collect($data)
                ->only(['no_documento', 'autoriza', 'monto'])
                ->merge([
                    'pagable_id' => $cajaChica->id,
                    'pagable_type' => CajaChica::class,
                ])
                ->toArray();

            // Crea el registro de Pago asociado
            Pago::create($pagoData);

            return $cajaChica;
        }

        return null;
    });
}
}
