<div class="space-y-6">
    <!-- Resumen General -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Ventas</div>
            <div class="text-base font-bold text-blue-900 dark:text-blue-100">
                Q{{ number_format($this->getViewData()['totalVentas'], 2) }}
            </div>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-green-600 dark:text-green-400">Total Cantidad</div>
            <div class="text-base font-bold text-green-900 dark:text-green-100">
                {{ number_format($this->getViewData()['totalCantidad'], 0) }}
            </div>
        </div>
        
        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Total Clientes</div>
            <div class="text-base font-bold text-purple-900 dark:text-purple-100">
                {{ number_format($this->getViewData()['totalClientes'], 0) }}
            </div>
        </div>
        
        <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-orange-600 dark:text-orange-400">Total Tallas</div>
            <div class="text-base font-bold text-orange-900 dark:text-orange-100">
                {{ number_format($this->getViewData()['totalTallas'], 0) }}
            </div>
        </div>
    </div>

    <!-- Tabla de Datos -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ventas por Talla</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Talla</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Ventas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cantidad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Clientes</th>
                        </tr>
                    </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->getViewData()['data'] as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $item->talla }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <span class="font-bold text-green-600">Q{{ number_format($item->total, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-center">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                {{ number_format($item->cantidad, 0) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-center">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                {{ number_format($item->clientes, 0) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
