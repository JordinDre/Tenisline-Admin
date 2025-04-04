<div class="overflow-x-auto">
    <table class="w-full divide-zinc-200 border border-zinc-200 rounded-lg shadow-sm">
        <thead class="bg-zinc-50">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Creado</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Prefechado</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Estado</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">NIT</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Nombre Comercial</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Razón Social</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Asesor</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Tipo de Envío</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Envío</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Subtotal</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Total</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Pagado</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Bodega</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Pagos</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Tipo de Pago</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">En Línea</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Tipo de Guía</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Guías</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Cantidad de Guías</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Productos</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Recibió</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Estado Envío</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Actualizado</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-zinc-200">
            @foreach ($ordenes as $orden)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->created_at }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->prefechado }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->estado }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->cliente->nit ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->cliente->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->cliente->razon_social ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->asesor->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->tipo_envio }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->envio }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->subtotal }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->total }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">Q {{ $orden->pagos->sum('monto') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->bodega->bodega ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->pagos()->exists() ? 'Sí' : 'No' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->tipo_pago->tipo_pago ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->enlinea ? 'Sí' : 'No' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->guias->pluck('tipo')->join(', ') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->guias->pluck('tracking')->join(', ') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->guias->pluck('cantidad')->join(', ') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @foreach ($orden->detalles as $detalle)
                            {{ $detalle['producto']['id'] ?? 'N/A' }},
                            {{ $detalle['producto']['codigo'] ?? 'N/A' }},
                            {{ $detalle['producto']['descripcion'] ?? 'N/A' }},
                            {{ $detalle['producto']['marca']['marca'] ?? 'N/A' }},
                            {{ $detalle['producto']['presentacion']['presentacion'] ?? 'N/A' }}<br>
                        @endforeach
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->recibio }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->estado_envio }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $orden->updated_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
