<div class="space-y-4">
    @if($pagos->isEmpty())
        <div class="text-center py-8 bg-zinc-50 rounded-lg">
            <p class="text-zinc-500 text-sm">No hay pagos de liquidación registrados para este cierre.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full divide-zinc-200 border border-zinc-200 rounded-lg shadow-sm">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Tipo de Pago</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Monto</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Banco</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">No. Documento</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Fecha Transacción</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Usuario</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Fecha Registro</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Imagen</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-zinc-200">
                    @foreach ($pagos as $pago)
                        <tr class="hover:bg-zinc-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $pago->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                {{ $pago->tipoPago?->tipo_pago ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                Q {{ number_format($pago->monto, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $pago->banco?->banco ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $pago->no_documento ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $pago->fecha_transaccion ? \Carbon\Carbon::parse($pago->fecha_transaccion)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $pago->user?->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500">
                                {{ \Carbon\Carbon::parse($pago->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($pago->imagen)
                                    <a href="{{ \Storage::disk(config('filesystems.disks.s3.driver'))->url($pago->imagen) }}" 
                                       target="_blank" 
                                       class="text-blue-600 hover:text-blue-800 underline">
                                        Ver imagen
                                    </a>
                                @else
                                    <span class="text-zinc-400">Sin imagen</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-zinc-50">
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-right text-sm font-bold">Total:</td>
                        <td class="px-6 py-4 text-sm font-bold text-green-700">
                            Q {{ number_format($pagos->sum('monto'), 2) }}
                        </td>
                        <td colspan="6"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Resumen por tipo de pago -->
        <div class="bg-zinc-50 rounded-lg p-4 mt-4">
            <h4 class="text-sm font-semibold text-zinc-700 mb-3">Resumen por Tipo de Pago</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @php
                    $resumenPorTipo = $pagos->groupBy(fn($pago) => $pago->tipoPago?->tipo_pago ?? 'Desconocido');
                @endphp
                
                @foreach($resumenPorTipo as $tipoPago => $pagosTipo)
                    <div class="bg-white rounded-lg border border-zinc-200 p-3">
                        <p class="text-xs text-zinc-500 mb-1">{{ $tipoPago }}</p>
                        <p class="text-lg font-bold text-zinc-900">Q {{ number_format($pagosTipo->sum('monto'), 2) }}</p>
                        <p class="text-xs text-zinc-400">{{ $pagosTipo->count() }} {{ $pagosTipo->count() === 1 ? 'pago' : 'pagos' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

