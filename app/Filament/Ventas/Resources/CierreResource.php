<?php

namespace App\Filament\Ventas\Resources;

use App\Filament\Ventas\Resources\CierreResource\Pages;
use App\Models\Cierre;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class CierreResource extends Resource
{
    protected static ?string $model = Cierre::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?string $navigationLabel = 'Cierres';

    protected static ?string $label = 'Cierre';

    protected static ?string $pluralLabel = 'Cierres';

    protected static ?string $slug = 'cierres';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bodega_id')
                    ->label('Bodega')
                    ->relationship(
                        'bodega',
                        'bodega',
                        fn (Builder $query) => $query
                            ->whereHas('user', fn ($q) => $q->where('user_id', auth()->id())
                            )
                            ->whereNotIn('bodega', ['Mal estado', 'Traslado'])
                            ->where('bodega', 'not like', '%bodega%')
                    )
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) {
                            $exists = Cierre::where('bodega_id', $value)
                                ->whereNull('cierre')
                                ->exists();

                            if ($exists) {
                                $fail('Ya existe un cierre abierto para esta bodega. Debe cerrar el anterior.');
                            }

                            $cierreHoy = Cierre::where('user_id', auth()->id())
                                ->whereDate('apertura', now()->toDateString())
                                ->exists();

                            if ($cierreHoy) {
                                $fail('Ya realizaste un cierre hoy. Solo puedes crear uno por día.');
                            }

                        },
                    ])
                    ->searchable()
                    ->preload()
                    ->live()
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->user()->id),
                Forms\Components\Hidden::make('apertura')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->label('Bodega')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('apertura')
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('cierre')
                    ->searchable()
                    ->dateTime()
                    ->sortable(),
                /* Tables\Columns\TextColumn::make('ventas_ids')
                    ->label('Ventas')
                    ->listWithLineBreaks()
                    ->searchable(), */
                Tables\Columns\TextColumn::make('total_tenis')
                    ->label('Cantidad Tenis'),
                Tables\Columns\TextColumn::make('total_ventas')
                    ->label('Total')
                    ->money('GTQ') // o 'USD', o elimina si no quieres formato de moneda
                    ->sortable(),
                Tables\Columns\TextColumn::make('resumen_pagos')
                    ->label('Resumen Pagos')
                    ->listWithLineBreaks(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Cerrar')
                    ->action(function (Cierre $record) {
                        $record->update([
                            'cierre' => now(),
                        ]);
                    })
                    ->visible(fn (Cierre $record) => $record->user_id === auth()->id() && $record->cierre === null)
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check'),
                Action::make('cierre')
                    ->icon('heroicon-o-document-arrow-down')
                    ->modalContent(fn (Cierre $record): View => view(
                        'filament.pages.actions.iframe',
                        [
                            'record' => $record,
                            'title' => 'Cierre #'.$record->id,
                            'route' => route('pdf.cierre', ['id' => $record->id]),
                            'open' => true,
                        ],
                    ))
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->slideOver()
                    ->stickyModalHeader()
                    ->modalSubmitAction(false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCierres::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->when(
                ! $user->hasAnyRole(['administrador', 'super_admin']),
                fn (Builder $query) => $query->where('user_id', $user->id)
            )
            ->orderByDesc('apertura');
    }
}
