<div class="space-y-6">
    <!-- Resumen General -->
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Ventas</div>
            <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                Q{{ number_format($totalVentas, 2) }}
            </div>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-green-600 dark:text-green-400">Total Meta</div>
            <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                Q{{ number_format($totalMeta, 2) }}
            </div>
        </div>
        
        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Alcance Promedio</div>
            <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                {{ number_format($totalAlcance, 2) }}%
            </div>
        </div>
        
        <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-orange-600 dark:text-orange-400">Total Proyección</div>
            <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                Q{{ number_format($totalProyeccion, 2) }}
            </div>
        </div>
        
        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-red-600 dark:text-red-400">Total Clientes</div>
            <div class="text-2xl font-bold text-red-900 dark:text-red-100">
                {{ number_format($totalClientes) }}
            </div>
        </div>
        
        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Total Productos</div>
            <div class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">
                {{ number_format($totalProductos) }}
            </div>
        </div>
    </div>

    <!-- Tabla de Resumen por Bodega -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Resumen por Bodega</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bodega</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Meta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ventas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Alcance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Proyección</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Diferencia</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Eficiencia</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Clientes</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($data as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                @if($item['bodega'] === 'Zacapa') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($item['bodega'] === 'Chiquimula') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @elseif($item['bodega'] === 'Esquipulas') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                @endif">
                                {{ $item['bodega'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <span class="font-bold text-green-600">Q{{ number_format($item['meta'], 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <span class="font-bold text-blue-600">Q{{ number_format($item['total'], 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @php
                                $alcance = $item['alcance'];
                                $colorClass = $alcance >= 100 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                            ($alcance >= 80 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200');
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $colorClass }}">
                                {{ number_format($alcance, 2) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <span class="font-medium text-orange-600">Q{{ number_format($item['proyeccion'], 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @php
                                $diferencia = $item['diferencia'];
                                $colorClass = $diferencia >= 0 ? 'text-green-600' : 'text-red-600';
                            @endphp
                            <span class="font-bold {{ $colorClass }}">
                                Q{{ number_format($diferencia, 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @php
                                $eficiencia = $item['eficiencia'] * 100;
                                $colorClass = $eficiencia >= 100 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                            ($eficiencia >= 80 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200');
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $colorClass }}">
                                {{ number_format($eficiencia, 2) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-center">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                {{ number_format($item['clientes']) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
