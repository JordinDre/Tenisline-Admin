<div>
    <!-- Resumen General -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg text-center">
            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Ventas</div>
            <div class="text-base font-bold text-blue-900 dark:text-blue-100">
                Q{{ number_format($totalVentas, 2) }}
            </div>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg text-center">
            <div class="text-sm font-medium text-green-600 dark:text-green-400">Total Productos</div>
            <div class="text-base font-bold text-green-900 dark:text-green-100">
                {{ number_format($totalCantidad, 0) }}
            </div>
        </div>
        
        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg text-center">
            <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Total Clientes</div>
            <div class="text-base font-bold text-purple-900 dark:text-purple-100">
                {{ number_format($totalClientes, 0) }}
            </div>
        </div>
        
        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg text-center">
            <div class="text-sm font-medium text-red-600 dark:text-red-400">Rentabilidad Promedio</div>
            <div class="text-base font-bold text-red-900 dark:text-red-100">
                {{ number_format($rentabilidadPromedio, 2) }}%
            </div>
        </div>
    </div>

    <!-- Tabla de Datos -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ventas por Vendedor</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ordenado por total de ventas (mayor a menor)</p>
        </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Asesor
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Total Ventas
                                <svg class="inline-block w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Total Productos
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Total Clientes
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Rentabilidad
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($data as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $item['asesor'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    Q{{ number_format($item['total'], 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 ">
                                    {{ number_format($item['cantidad'], 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 ">
                                    {{ number_format($item['clientes'], 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        @if($item['rentabilidad'] >= 0.5) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($item['rentabilidad'] >= 0.3) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @elseif($item['rentabilidad'] >= 0) bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                        @endif">
                                        {{ number_format($item['rentabilidad'] * 100, 2) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No hay datos disponibles
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
