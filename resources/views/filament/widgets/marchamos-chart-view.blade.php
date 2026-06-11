<x-filament::widget>
    <x-filament::card class="p-6 space-y-6">

        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Costos de Ofertados
            </h3>
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                <div class="text-center mb-2">
                    <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Total Costos Ofertados</div>
                    <div class="text-base font-bold text-purple-900 dark:text-purple-100">
                        Q{{ number_format($costoOfertados, 2) }}
                    </div>
                    <div class="text-sm text-purple-500 dark:text-purple-300 mt-1">
                        {{ number_format($cantidadOfertados, 0) }} {{ $cantidadOfertados === 1 ? 'par' : 'pares' }}
                    </div>
                </div>
                
                @if(count($bodegasOfertados) > 0)
                <div class="mt-3 pt-3 border-t border-purple-200 dark:border-purple-800/30 text-xs">
                    <div class="font-semibold text-purple-700 dark:text-purple-300 mb-1 text-center">Por Bodega:</div>
                    <ul class="space-y-1">
                        @foreach($bodegasOfertados as $bo)
                        <li class="flex justify-between items-center text-purple-600 dark:text-purple-400 px-2">
                            <span>{{ $bo['nombre'] }}</span>
                            <span class="font-medium">{{ $bo['cantidad'] }} {{ $bo['cantidad'] === 1 ? 'par' : 'pares' }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>

        <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Costos por Marchamo
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach ($marchamosData as $marchamo)
                    <div class="{{ $marchamo['colores']['bg'] }} p-4 rounded-lg flex flex-col h-full">
                        <div class="text-center mb-auto">
                            <div class="text-sm font-medium {{ $marchamo['colores']['text'] }}">
                                {{ ucfirst($marchamo['nombre']) }}
                            </div>
                            <div class="text-base font-bold {{ $marchamo['colores']['textBold'] }}">
                                Q{{ number_format($marchamo['costo'], 2) }}
                            </div>
                            <div class="text-sm {{ $marchamo['colores']['text'] }} mt-1">
                                {{ number_format($marchamo['cantidad'], 0) }} {{ $marchamo['cantidad'] === 1 ? 'par' : 'pares' }}
                            </div>
                        </div>
                        
                        @if(count($marchamo['bodegas']) > 0)
                        <div class="mt-3 pt-3 border-t {{ str_replace('bg-', 'border-', $marchamo['colores']['bg']) }} border-opacity-50 text-xs w-full">
                            <ul class="space-y-1">
                                @foreach($marchamo['bodegas'] as $mb)
                                <li class="flex justify-between items-center {{ $marchamo['colores']['text'] }} opacity-90 px-1">
                                    <span class="truncate pr-2">{{ $mb['nombre'] }}</span>
                                    <span class="font-medium whitespace-nowrap">{{ $mb['cantidad'] }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

    </x-filament::card>
</x-filament::widget>
