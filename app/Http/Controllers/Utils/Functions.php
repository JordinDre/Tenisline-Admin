<?php

namespace App\Http\Controllers\Utils;

class Functions
{
    public static function money($valor)
    {
        $valor = is_numeric($valor) ? floatval($valor) : 0;

        return 'Q '.number_format($valor, 2, '.', ',');
    }

    public static function eliminarTildes($cadena)
    {
        $cadena = strtr($cadena, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'ñ' => 'n',
            'Ñ' => 'N',
        ]);

        return $cadena;
    }

    public static function nombreMes($mes)
    {
        $nombresMeses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];

        return $nombresMeses[$mes];
    }

    public static function obtenerMeses()
    {
        return [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];
    }

    public static function obtenerAnios()
    {
        return range(now()->year - 3, now()->year + 1);
    }
}
