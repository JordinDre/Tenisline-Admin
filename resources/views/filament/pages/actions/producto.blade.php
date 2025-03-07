@if ($url)
    <div class="max-w-screen-md mx-auto overflow-auto max-h-[80vh]">
        <img 
            src="{{ $url }}" 
            alt="{{ $alt }}" 
            loading="lazy" 
            class="w-full h-auto rounded-lg"
        />
    </div>
@else
    <p class="text-center text-gray-500">No hay imagen disponible</p>
@endif
