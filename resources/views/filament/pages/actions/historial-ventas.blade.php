<div class="overflow-x-auto">
    <table class="w-full divide-zinc-200 border border-zinc-200 rounded-lg shadow-sm">
        <thead class="bg-zinc-50">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Id Venta</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Nombre</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Fecha Venta</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Código Producto</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Descripción</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Marca</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Talla</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Género</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Cantidad</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Sub Total</th>
                <th class="px-6 py-3 text-left text-sm font-semibold tracking-wider">Asesor</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-zinc-200">
            @foreach ($ventas as $venta)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $venta->venta_id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $venta->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $venta->codigo }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $venta->descripcion }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $venta->marca }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $venta->talla }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $venta->genero }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $venta->cantidad }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">Q {{ number_format($venta->subtotal, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $venta->asesor }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
