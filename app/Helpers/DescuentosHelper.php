<?php

namespace App\Helpers;

class DescuentosHelper
{
    public static function aplicarDescuentoMitadPorPar(array $detalles): ?array
    {
        $detalles = array_values($detalles);

        $totalPares = collect($detalles)->sum('cantidad');
        $descuentosIndividualesActivos = collect($detalles)
            ->filter(fn ($d) => ($d['oferta'] ?? false) || ($d['oferta_20'] ?? false))
            ->count();

        $slotsDisponibles = floor($totalPares / 2);
        $paresConDescuentoGlobal = $slotsDisponibles - $descuentosIndividualesActivos;

        if ($paresConDescuentoGlobal <= 0) {
            return $detalles;
        }

        $sinDescuentoIndividual = collect($detalles)->filter(function ($item) {
            return ! ($item['oferta'] ?? false) && ! ($item['oferta_20'] ?? false);
        })->toArray();

        $paresSinDescuento = [];
        foreach ($sinDescuentoIndividual as $index => $detalle) {
            for ($i = 0; $i < ($detalle['cantidad'] ?? 0); $i++) {
                $paresSinDescuento[] = [
                    'original_index' => $index,
                    'precio' => $detalle['precio'] ?? 0,
                ];
            }
        }

        usort($paresSinDescuento, fn ($a, $b) => $a['precio'] <=> $b['precio']);

        $descuentosAplicados = [];
        for ($i = 0; $i < $paresConDescuentoGlobal && $i < count($paresSinDescuento); $i++) {
            $index = $paresSinDescuento[$i]['original_index'];
            $descuentosAplicados[$index] = ($descuentosAplicados[$index] ?? 0) + 1;
        }

        $detallesFinales = [];
        foreach ($detalles as $index => $detalle) {
            if (($detalle['oferta'] ?? false) || ($detalle['oferta_20'] ?? false)) {
                $detallesFinales[] = $detalle;

                continue;
            }

            $cantidadConDescuento = $descuentosAplicados[$index] ?? 0;
            $cantidadSinDescuento = ($detalle['cantidad'] ?? 0) - $cantidadConDescuento;

            if ($cantidadConDescuento > 0) {
                $detalleConDescuento = $detalle;
                $detalleConDescuento['cantidad'] = $cantidadConDescuento;
                $detalleConDescuento['precio'] = ($detalle['precio'] ?? 0) / 2;
                $detalleConDescuento['subtotal'] = $cantidadConDescuento * ($detalleConDescuento['precio'] ?? 0);
                $detallesFinales[] = $detalleConDescuento;
            }

            if ($cantidadSinDescuento > 0) {
                $detalleSinDescuento = $detalle;
                $detalleSinDescuento['cantidad'] = $cantidadSinDescuento;
                $detalleSinDescuento['subtotal'] = $cantidadSinDescuento * ($detalleSinDescuento['precio'] ?? 0);
                $detallesFinales[] = $detalleSinDescuento;
            }
        }

        return $detallesFinales;
    }
}
