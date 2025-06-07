<?php

namespace App\Helpers;

use Filament\Notifications\Notification;

class DescuentosHelper
{
    public static function aplicarDescuentoMitadPorPar(array $detalles): array|null
    {
        $totalPares = array_sum(array_column($detalles, 'cantidad'));
        if ($totalPares < 2) {
            return null;
        }

        $pares = [];
        foreach ($detalles as $index => $detalle) {
            for ($i = 0; $i < $detalle['cantidad']; $i++) {
                $pares[] = [
                    'index' => $index,
                    'precio' => $detalle['precio'],
                ];
            }
        }

        usort($pares, fn($a, $b) => $a['precio'] <=> $b['precio']);

        $paresConDescuento = floor($totalPares / 2);

        $descuentosAplicados = [];

        for ($i = 0; $i < $paresConDescuento; $i++) {
            $index = $pares[$i]['index'];
            $descuentosAplicados[$index] = ($descuentosAplicados[$index] ?? 0) + 1;
        }

        foreach ($descuentosAplicados as $index => $cantidadConDescuento) {
            $detalle = &$detalles[$index];
            $nuevosDetalles = [];

            $conDescuento = [
                'producto_id' => $detalle['producto_id'],
                'cantidad' => $cantidadConDescuento,
                'precio' => $detalle['precio'] / 2,
                'subtotal' => $cantidadConDescuento * ($detalle['precio'] / 2),
            ];

            $sinDescuento = [
                'producto_id' => $detalle['producto_id'],
                'cantidad' => $detalle['cantidad'] - $cantidadConDescuento,
                'precio' => $detalle['precio'],
                'subtotal' => ($detalle['cantidad'] - $cantidadConDescuento) * $detalle['precio'],
            ];

            unset($detalles[$index]);
            if ($sinDescuento['cantidad'] > 0) {
                $detalles[] = $sinDescuento;
            }
            if ($conDescuento['cantidad'] > 0) {
                $detalles[] = $conDescuento;
            }
        }

        return array_values($detalles); 
    }
}
