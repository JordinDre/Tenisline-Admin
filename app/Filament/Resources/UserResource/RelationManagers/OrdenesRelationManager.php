<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Concerns\CanBeEmbeddedInModals;

class OrdenesRelationManager extends RelationManager
{
    use CanBeEmbeddedInModals;

    protected static string $relationship = 'ordenes';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prefechado')
                    ->sortable()
                    ->dateTime('d/m/Y'),

                Tables\Columns\TextColumn::make('estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('cliente.nit')
                    ->label('NIT')

                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.name')
                    ->label('Nombre Comercial')

                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.razon_social')
                    ->label('Razón Social')

                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor')

                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_envio')
                    ->label('Tipo de Envío'),
                Tables\Columns\TextColumn::make('envio')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->money('GTQ')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('GTQ')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pagado')
                    ->label('Pagado')
                    ->visible(auth()->user()->can('validate_pay_orden'))
                    ->formatStateUsing(function ($record) {
                        return 'Q '.$record->pagos->sum('monto');
                    })
                    ->copyable(),
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->label('Bodega'),
                Tables\Columns\IconColumn::make('tiene_pagos')
                    ->label('Pagos')
                    ->boolean()
                    ->getStateUsing(function ($record) {
                        return $record->pagos()->exists(); // Verifica si hay pagos asociados
                    }),
                Tables\Columns\TextColumn::make('tipo_pago.tipo_pago')
                    ->label('Tipo de Pago')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('enlinea')
                    ->label('Enlinea')
                    ->boolean(),
                Tables\Columns\TextColumn::make('guias.tipo')
                    ->label('Tipo de Guia')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('guias.tracking')
                    ->label('Guias')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('guias.cantidad')
                    ->label('Cantidad de Guias')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('detalles.producto.id')
                    ->label('ID Producto')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('detalles.producto.codigo')
                    ->label('Cod Producto')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('detalles.producto.descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('detalles.producto.marca.marca')
                    ->label('Marca')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('detalles.producto.presentacion.presentacion')
                    ->label('Presentación')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('recibio')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado_envio')
                    ->copyable()
                    ->sortable(),
                /* Tables\Columns\TextColumn::make('factura.numero')
                    ->sortable(), */
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            /* ->headerActions([
                Tables\Actions\CreateAction::make(),
            ]) */
            ->actions([
            /* Tables\Actions\ViewAction::make(), */])
            /* ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]) */;
    }
}
