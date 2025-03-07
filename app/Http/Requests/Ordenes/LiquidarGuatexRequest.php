<?php

namespace App\Http\Requests\Ordenes;

use Illuminate\Foundation\Http\FormRequest;

class LiquidarGuatexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'no_acreditamiento' => 'required|numeric',
            'fecha_transaccion' => 'required|date',
            'no_documento' => 'required',
            'monto_cod' => 'required|numeric',
            'monto' => 'required|numeric',
            'total_liquidacion' => 'required|numeric',
        ];
    }

    public function messages()
    {
        return [
            'no_acreditamiento.required' => 'El Número de Acreditamiento es requerido',
            'no_acreditamiento.numeric' => 'El  Número de Acreditamiento debe ser numérico',

            'fecha_transaccion.required' => 'La Fecha de Transacción es requerida',
            'fecha_transaccion.date' => 'La Fecha de Transacción no cumple con el formato',

            'no_documento.required' => 'El Número de Documento o Número de Depósito es requerido',

            'monto_cod.required' => 'El Monto COD es requerido',
            'monto_cod.numeric' => 'El  Monto COD debe ser numérico',

            'monto.required' => 'El Monto del Depósito es requerido',
            'monto.numeric' => 'El Monto del Depósito debe ser numérico',

            'total_liquidacion.required' => 'El Total de la Liquidación es requerido',
            'total_liquidacion.numeric' => 'El Total de la Liquidación debe ser numérico',
        ];
    }
}
