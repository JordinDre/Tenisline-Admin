<div class="w-full bg-red-600 px-4 py-3">
    <div class="flex items-center justify-between gap-4">
        <div class="!text-white !opacity-100 text-sm leading-tight">
            <span class="font-semibold">Sistema bloqueado:</span>
            Existe una venta pendiente de validación de pago por más de 1 hora.
            Debe validarse o anularse para continuar.
        </div>

        <a
            href="{{ \App\Filament\Ventas\Resources\VentaResource::getUrl(panel: filament()->getCurrentPanel()->getId()) }}"
            class="shrink-0 bg-white text-red-600 px-3 py-1 rounded text-xs font-semibold hover:bg-gray-100"
        >
            Ir a Ventas
        </a>
    </div>
</div>
