<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Models\Seguimiento;
use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public static function nit($nit)
    {
        $normalizedNit = strtoupper(str_replace(['-', '/', '.', ' '], '', $nit));
        $cfValues = ['CF', 'C/F', ''];

        if (in_array($normalizedNit, $cfValues)) {
            return 'CF';
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://consultace.feel.com.gt/api/consulta-nit',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(['nit' => $normalizedNit]),
            CURLOPT_HTTPHEADER => [
                'UsuarioApi: '.config('services.fel.usuario_api'),
                'LlaveApi: '.config('services.fel.llave_api'),
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $arregloNit = json_decode($response, true);

        if (isset($arregloNit['resultado']) && $arregloNit['resultado'] === true) {
            Notification::make()
                ->color('success')
                ->title('NIT Correcto')
                ->body($arregloNit['nombre'])
                ->success()
                ->send();

            return $arregloNit['nombre'];
        } else {
            Notification::make()
                ->color('danger')
                ->title('Error al buscar NIT')
                ->body($arregloNit['error'] ?? 'No se encontró el NIT')
                ->danger()
                ->send();

            return 'CF';
        }
    }

    public static function sumarSaldo($user, $monto)
    {
        DB::transaction(function () use ($user, $monto) {
            if (($user->saldo + $monto) > $user->credito) {
                throw new \Exception("El cliente no puede tener más crédito, el saldo actual es de ({$user->saldo}) y excede el crédito permitido ({$user->credito}).");
            } else {
                $user->saldo += $monto;
                $user->save();
            }
        });
    }

    public static function restarSaldo($user, $monto)
    {
        DB::transaction(function () use ($user, $monto) {
            $user->saldo -= $monto;
            $user->save();
        });
    }

    public static function asignar($user)
    {
        try {
            DB::transaction(function () use ($user) {
                $asesor = auth()->user();

                // Verificar si el asesor ya tiene un cliente asignado
                if ($asesor->asignado_id !== null) {
                    throw new Exception('Ya tienes un cliente asignado.');
                }

                // Contar asesores telemarketing disponibles (sin asignación previa)
                $asesoresDisponibles = User::role('asesor telemarketing')
                    ->whereNull('asignado_id')
                    ->count();

                if ($asesoresDisponibles === 0) {
                    throw new Exception('No hay asesores disponibles para asignar clientes.');
                }

                // Verificar si el cliente ya tiene un asesor asignado
                if (User::where('asignado_id', $user->id)->exists()) {
                    throw new Exception('Este cliente ya ha sido asignado a otro asesor.');
                }

                // Obtener los primeros N clientes sin asignar (según asesores disponibles)
                $clientesSinAsignar = User::whereNull('asignado_id')
                    ->whereHas('ordenes', function ($query) {
                        $query->whereRaw(
                            '
                        (SELECT MAX(created_at) 
                         FROM ordens 
                         WHERE ordens.cliente_id = users.id) < ?',
                            [now()->subDays(60)]
                        )->whereNotIn('estado', Orden::ESTADOS_EXCLUIDOS);
                    })
                    ->orderByRaw('
                    CASE 
                        WHEN (SELECT MAX(created_at) 
                              FROM seguimientos 
                              WHERE seguimientos.user_id = users.id) >= ? 
                        THEN 1 
                        ELSE 0 
                    END ASC, 
                    (SELECT MIN(created_at) 
                     FROM ordens 
                     WHERE ordens.cliente_id = users.id) ASC
                ', [now()->subDays(7)])
                    ->limit($asesoresDisponibles)
                    ->pluck('id')
                    ->toArray();

                // Verificar si el usuario está en la lista de clientes asignables
                if (! in_array($user->id, $clientesSinAsignar)) {
                    throw new Exception('Este cliente no está disponible para asignación en este momento.');
                }

                // Asignar el cliente al asesor
                $user->asesores()->sync($asesor->id);
                $asesor->update(['asignado_id' => $user->id]);
            });

            Notification::make()
                ->color('success')
                ->title('Cliente asignado con éxito')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al asignar cliente')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function seguimiento($data, $user)
    {
        try {
            DB::transaction(function () use ($data, $user) {
                $seguimiento = new Seguimiento;
                $seguimiento->seguimiento = $data['seguimiento'];
                $seguimiento->user_id = $user->id;
                $seguimiento->redactor_id = auth()->user()->id;
                $user->seguimientos()->save($seguimiento);

                $user->asesores()->sync(auth()->user()->id);
                if (auth()->user()->asignado_id == $user->id) {
                    auth()->user()->update(['asignado_id' => null]);
                }
            });
            Notification::make()
                ->title('Seguimiento registrado')
                ->color('success')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->color('danger')
                ->title('Error al asignar cliente')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
