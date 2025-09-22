<div class="space-y-6">
    <!-- Resumen General -->
    <div class="grid grid-cols-1 md:grid-cols-7 gap-4 mb-6">
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg text-center">
            <div class="text-xs font-medium text-green-600 dark:text-green-400">Total Meta</div>
            <div class="text-sm font-bold text-green-900 dark:text-green-100">
                Q{{ number_format($this->getViewData()['totalMeta'] / 2, 2) }}
            </div>
        </div>
        
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg text-center">
            <div class="text-xs font-medium text-blue-600 dark:text-blue-400">Total Ventas</div>
            <div class="text-sm font-bold text-blue-900 dark:text-blue-100">
                Q{{ number_format($this->getViewData()['totalVentas'], 2) }}
            </div>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg text-center">
            <div class="text-xs font-medium text-orange-600 dark:text-orange-400">Total Proyección</div>
            <div class="text-sm font-bold text-orange-900 dark:text-orange-100">
                Q{{ number_format($this->getViewData()['totalProyeccion'], 2) }}
            </div>
        </div>
        
        <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg text-center">
            <div class="text-xs font-medium text-orange-600 dark:text-orange-400">Total Proyección %</div>
            <div class="text-sm font-bold text-orange-900 dark:text-orange-100">
                {{ $this->getViewData()['totalVentas'] > 0 ? number_format($this->getViewData()['totalProyeccion'] / $this->getViewData()['totalVentas'], 2) : '0.00' }}%
            </div>
        </div>
        
        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg text-center">
            <div class="text-xs font-medium text-purple-600 dark:text-purple-400">Uni. Vendidas</div>
            <div class="text-sm font-bold text-purple-900 dark:text-purple-100">
                {{ number_format($this->getViewData()['totalUnidadesVendidas'], 0) }}
            </div>
        </div>
        
        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg text-center">
            <div class="text-xs font-medium text-red-600 dark:text-red-400">Uni. Proyectadas</div>
            <div class="text-sm font-bold text-red-900 dark:text-red-100">
                {{ number_format($this->getViewData()['totalUnidadesProyectadas'], 0) }}
            </div>
        </div>
        
        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg text-center">
            <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400">Rendimiento Promedio</div>
            <div class="text-sm font-bold text-indigo-900 dark:text-indigo-100">
                {{ number_format($this->getViewData()['rentabilidadPromedio'] / 2, 2) }}%
            </div>
        </div>
    </div>

    <!-- Tabla de Datos -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ventas por Empleado</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ordenado por proyección (mayor a menor)</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Empleado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Meta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Venta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Proyección
                            <svg class="inline-block w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Proyección %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Uni. Vendidas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Uni. Proyectadas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rendimiento</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->getViewData()['data'] as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ $item['asesor'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <span class="font-bold text-green-600">Q{{ number_format($item['meta'] / 2, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <span class="font-bold text-blue-600">Q{{ number_format($item['total'], 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <span class="font-medium text-orange-600">Q{{ number_format($item['proyeccion'], 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <span class="font-medium text-orange-600">{{ $item['total'] > 0 ? number_format($item['proyeccion'] / $item['total'], 2) : '0.00' }}%</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                {{ number_format($item['unidades_vendidas'], 0) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                {{ number_format($item['unidades_proyectadas'], 0) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @php
                                $rentabilidad = ($item['rentabilidad'] * 100) / 2;
                                $colorClass = $rentabilidad >= 50 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                            ($rentabilidad >= 30 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200');
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $colorClass }}">
                                {{ number_format($rentabilidad, 2) }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
