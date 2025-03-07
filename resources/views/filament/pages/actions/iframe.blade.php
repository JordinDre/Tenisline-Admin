<div class="w-full h-screen flex flex-col">
    <style>
        .title-button-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem; 
        }
        .button-custom {
            background-color: #3B82F6;
            color: white;
            font-weight: 600;
            padding: 0.5rem 1rem; 
            border-radius: 0.375rem; 
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); 
            transition: background-color 0.2s ease;
        }
        .button-custom:hover {
            background-color: #2563EB; 
        }
    </style>

    <div class="title-button-container"> 
        <h2 class="text-lg font-semibold">{{ $title }}</h2>
        
        @if($open) 
            <button 
                class="button-custom flex items-center" 
                onclick="window.open('{{ $route }}', '_blank')">
                Abrir en nueva pestaña
            </button>
        @endif
    </div>

    <iframe src="{{ $route }}" style="width: 100%; height: calc(100% - 60px);" frameborder="0"></iframe> 
</div>
