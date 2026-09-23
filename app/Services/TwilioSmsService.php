<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioSmsService
{
    /**
     * Envía por SMS, vía Twilio Verify, el código de confirmación ya generado por el sistema
     * (no uno generado por Twilio) usando el Alphanumeric Sender ID ya aprobado para Guatemala.
     * En el entorno de testing no llama a la API real.
     */
    public function enviarCodigo(string $numeroDestino, string $codigo): void
    {
        if (app()->environment('testing')) {
            Log::info('TwilioSmsService: envío simulado (entorno testing)', ['to' => $numeroDestino]);

            return;
        }

        $client = new Client(config('services.twilio.account_sid'), config('services.twilio.auth_token'));

        $client->verify->v2->services(config('services.twilio.verify_sid'))
            ->verifications
            ->create($numeroDestino, 'sms', [
                'customCode' => $codigo,
                'templateSid' => config('services.twilio.verify_template_sid'),
                'locale' => 'es',
            ]);
    }
}
