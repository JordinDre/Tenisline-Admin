<div style="display: flex; align-items: flex-start;">
    {{-- <img src="{{ $imagenUrl }}" alt="Imagen del producto" style="width: 100px; height: 100px; object-fit: cover; margin-right: 10px;" /> --}}
    <div>
        <div style="font-weight: bold; color: black;">ID: {{ $producto->id }} - <span style="color: black;">{{ $producto->codigo }}</span></div>
        <div style="color: black;">Descripción: {{ $producto->descripcion }}</div>
        <div style="color: black;">Marca: {{ $producto->marca->marca }}, Talla: {{ $producto->talla }}, Estilo: {{ $producto->genero }}</div>
        <div style="color: black; font-weight: bold; margin-top: 5px;">Existencia: {{ $stock }}</div>
    </div>
</div>
