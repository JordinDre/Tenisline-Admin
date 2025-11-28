<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteUltimaCompraClienteExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['ID Producto', 'Código', 'Descripción', 'Marca', 'Precio de Venta', 'Precio Costo', 'Zacapa', 'Bodega Central', 
        'Mal estado', 'Traslado', 'Bodega Zacapa', 'Chiquimula', 'Bodega Chiquimula', 'Esquipulas', 'Bodega Esquipulas', 'Total Existencias'];
    }
}
