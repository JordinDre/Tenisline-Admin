<?php

namespace App\Filament\Inventario\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\CajaChica;
use Filament\Tables\Table;
use Filament\Resources\Resource;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $slug = 'cajachica';

    protected static ?string $pluralModelLabel = 'Caja Chica';

    protected static ?string $navigationLabel = 'Caja Chica';

    protected static ?string $navigationGroup = 'Caja Chica';

    protected static ?int $navigationSort = 3;

    public static function getPermissionPrefixes(): array
    {
        return [
            
        ];
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                    TextInput::make('no_documento')
                        ->label('No. Documento'),
                    Textarea::make('detalle_gasto')
                        ->label('Detalle del gasto')
                        ->columnSpanFull(),
                    TextInput::make('autoriza')
                        ->label('Quien Autorizo'),
                    TextInput::make('monto')
                        ->label('Monto'),
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

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_documento')
                    ->label('No. Documento'),
                Tables\Columns\TextColumn::make('detalle_gasto')
                    ->label('No. Documento'),
                Tables\Columns\TextColumn::make('autoriza')
                    ->label('No. Documento'),
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
}
