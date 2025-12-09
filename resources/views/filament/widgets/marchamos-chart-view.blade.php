<x-filament::widget>
    <x-filament::card class="p-6 space-y-6">

        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Totales por Marchamo
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg text-center">
                <div class="text-sm font-medium text-red-600 dark:text-red-400">Rojo</div>
                <div class="text-base font-bold text-red-900 dark:text-red-100">
                    Q{{ number_format($rojo, 2) }}
                </div>
            </div>

            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg text-center">
                <div class="text-sm font-medium text-orange-600 dark:text-orange-400">Naranja</div>
                <div class="text-base font-bold text-orange-900 dark:text-orange-100">
                    Q{{ number_format($naranja, 2) }}
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg text-center">
                <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Celeste</div>
                <div class="text-base font-bold text-blue-900 dark:text-blue-100">
                    Q{{ number_format($celeste, 2) }}
                </div>
            </div>

            {{-- <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg text-center">
                <div class="text-sm font-medium text-green-600 dark:text-green-400">Total General</div>
                <div class="text-base font-bold text-green-900 dark:text-green-100">
                    Q{{ number_format($total, 2) }}
                </div>
            </div> --}}

        </div>

    </x-filament::card>
</x-filament::widget>
