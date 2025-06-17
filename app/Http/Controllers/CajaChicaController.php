<?php

namespace App\Http\Controllers;

use App\Models\CajaChica;
use Illuminate\Http\Request;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CajaChicaController extends Controller
{
    public static function confirmar(CajaChica $caja_chica)
    {
        try {
            $caja_chica->estado = 'confirmada';
            $caja_chica->save();
            activity()->performedOn($caja_chica)->causedBy(auth()->user())->withProperties($caja_chica)->event('confirmación')->log('Caja Chica confirmada');

            Notification::make()
                ->color('success')
                ->title('Caja Chica confirmada correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al confirmar la Caja Chica')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function anular(CajaChica $caja_chica)
    {
        try {
            DB::transaction(function () use ($caja_chica) {
                $caja_chica->estado = 'anulada';
                $caja_chica->save();
                activity()->performedOn($caja_chica)->causedBy(auth()->user())->withProperties($caja_chica)->event('anulación')->log('Caja Chica anulada');
            });
            Notification::make()
                ->color('success')
                ->title('Caja Chica anulada correctamente')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al anular la Caja Chica')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
