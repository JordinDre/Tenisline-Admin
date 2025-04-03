<?php

namespace App\Filament\Ventas\Resources\TotalVentaResource\Pages;

use Carbon\Carbon;
use App\Models\Pago;
use App\Models\Venta;
use Filament\Actions;
use App\Models\TipoPago;
use App\Models\TotalVenta;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use App\Filament\Ventas\Resources\TotalVentaResource;

class CreateTotalVenta extends CreateRecord
{
    protected static string $resource = TotalVentaResource::class;

}
