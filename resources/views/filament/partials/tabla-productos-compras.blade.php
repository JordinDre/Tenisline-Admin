@php
    $isEditable = method_exists($livewire, 'eliminarDetalle') && $livewire instanceof \Filament\Resources\Pages\CreateRecord || $livewire instanceof \Filament\Resources\Pages\EditRecord;
    $items = $livewire->detalles ?? collect();
@endphp

<div class="fi-fo-placeholder-text-input block text-sm">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 rounded-md shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-4 py-3">Producto</th>
                <th scope="col" class="px-4 py-3 text-right">Cantidad</th>
                <th scope="col" class="px-4 py-3 text-right">Precio Costo</th>
                <th scope="col" class="px-4 py-3 text-right">Subtotal</th>
                @if(isset($livewire) && ($livewire instanceof \App\Filament\Inventario\Resources\CompraResource\Pages\CreateCompra || $livewire instanceof \App\Filament\Inventario\Resources\CompraResource\Pages\EditCompra))
                    <th scope="col" class="px-4 py-3 text-center">Acción</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($detalles as $index => $detalle)
                @php
                    $descripcion = $detalle['descripcion'] ?? 'N/A';
                    $cantidad = (float)($detalle['cantidad'] ?? 0);
                    $precio = (float)($detalle['precio'] ?? 0);
                    $subtotalItem = $cantidad * $precio;
                @endphp
                <tr class="border-b dark:border-gray-700">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                        {{ $descripcion }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        {{ $cantidad }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        Q {{ number_format($precio, 2) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        Q {{ number_format($subtotalItem, 2) }}
                    </td>
                    {{-- @if(isset($livewire) && ($livewire instanceof \App\Filament\Inventario\Resources\CompraResource\Pages\CreateCompra || $livewire instanceof \App\Filament\Inventario\Resources\CompraResource\Pages\EditCompra))
                        <td class="px-4 py-3 text-center">
                            <button
                                wire:click="eliminarDetalle({{ $index }})"
                                type="button"
                                class="text-red-600 hover:text-red-800 dark:text-red-500 dark:hover:text-red-400 font-medium"
                                title="Eliminar producto"
                            >
                                Eliminar
                            </button>
                        </td>
                    @endif --}}
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        No hay productos agregados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
