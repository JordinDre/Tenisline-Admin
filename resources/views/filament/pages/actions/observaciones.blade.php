<div class="overflow-x-auto">
    <table class="min-w-full divide-zinc-200 border border-zinc-200 rounded-lg shadow-sm">
        <thead class="bg-zinc-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold tracking-wider">
                    Observaciones
                </th>
                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold tracking-wider">
                    Usuario
                </th>
                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold tracking-wider">
                    Fecha
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-zinc-200">
            @foreach ($record->observaciones as $observacion)
                <tr>
                    <td class="px-6 py-4 whitespace-normal break-words">
                        <div class="text-sm text-zinc-900">{{ $observacion->observacion }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-zinc-900">{{ $observacion->user->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-zinc-900">{{ $observacion->created_at->format('d/m/Y H:i') }}</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
