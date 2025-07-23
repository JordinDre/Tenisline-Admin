@isset($detalles)
    @if(count($detalles))
        <table class="...">
            {{-- tu tabla aquí --}}
        </table>
    @else
        <p class="text-gray-500 text-sm">No hay productos agregados.</p>
    @endif
@else
    <p class="text-gray-400 text-sm italic">Productos no disponibles en este modo.</p>
@endisset

<table class="w-full text-sm border rounded">
    <thead class="bg-gray-100">
        <tr>
            <th class="px-4 py-2 text-left">#</th>
            <th class="px-4 py-2 text-left">Producto</th>
            <th class="px-4 py-2 text-left">Cantidad</th>
            <th class="px-4 py-2 text-left">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($detalles as $index => $detalle)
            <tr class="border-t">
                <td class="px-4 py-2">{{ $index + 1 }}</td>
                <td class="px-4 py-2">{{ $detalle['descripcion'] }}</td>
                <td class="px-4 py-2">{{ $detalle['cantidad_enviada'] }}</td>
                <td class="px-4 py-2">
                    @if (method_exists($this, 'isEdit') && $this->isEdit())
                        <button
                            wire:click="eliminarDetalle({{ $index }})"
                            type="button"
                            class="text-red-600 hover:underline"
                        >
                            Eliminar
                        </button>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-2 text-center text-gray-500">No hay productos.</td>
            </tr>
        @endforelse
    </tbody>
</table>
