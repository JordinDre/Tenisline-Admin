<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ReporteHistorialClienteExport implements FromCollection, WithHeadings
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
        if ($this->data->isEmpty()) {
            return [];
        }

        return array_keys((array) $this->data->first());
    }
}
