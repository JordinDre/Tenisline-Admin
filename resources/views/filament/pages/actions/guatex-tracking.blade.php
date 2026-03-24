<div class="p-4">
    @if(empty($movimientos))
        <div class="text-center py-4 text-gray-500">
            No se encontraron movimientos para esta guía.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2">Fecha/Hora</th>
                        <th class="px-4 py-2">Estado / Punto</th>
                        <th class="px-4 py-2">Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movimientos as $mov)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-4 py-2 whitespace-nowrap">
                                {{ $mov['tfecha'] ?? '' }} {{ $mov['thora'] ?? '' }}
                            </td>
                            <td class="px-4 py-2">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $mov['testado'] ?? 'Sin estado' }}
                                </span>
                                <br>
                                <span class="text-xs">{{ $mov['tpunto'] ?? '' }}</span>
                            </td>
                            <td class="px-4 py-2 text-xs">
                                {{ $mov['tobservaciones'] ?? '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
