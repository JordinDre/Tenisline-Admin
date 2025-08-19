<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteDiarioExport implements FromCollection, WithHeadings
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
        return ['Tienda', 'Fecha', 'Turno', 'Ventas Día', 'Precio Venta', 'Precio Costo', 'Utilidad Día', 'Utilidad Financista', 'Utilidad Neta'];
    }
}
