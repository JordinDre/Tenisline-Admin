<?php

namespace App\Filament\Ventas\Widgets;

use App\Models\Labor;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Saade\FilamentFullCalendar\Actions\ViewAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarioLaboral extends FullCalendarWidget
{
    public Model|string|null $model = Labor::class;

    protected static ?int $sort = 2;

    protected static ?string $heading = 'Calendario Laboral';

    public static function canView(): bool
    {
        if (! Schema::hasTable('ordens')) { // Reemplaza 'ordens' con el nombre real de tu tabla
            return false; // Si la tabla 'ordens' NO existe, NO mostrar el widget
        }

        return auth()->user()->can('widget_CalendarioLaboral');
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return Labor::query()
            ->where('date', '>=', $fetchInfo['start'])
            ->where('date', '<=', $fetchInfo['end'])
            ->get()
            ->map(
                fn (Labor $labor) => [
                    'id' => $labor->id,
                    'title' => $labor->title,
                    'start' => $labor->date,
                    'end' => $labor->date,
                    'color' => 'rgb(21 128 61)',
                    'textColor' => '#ffffff',
                    'description' => $labor->description ?? '',
                ]
            )
            ->all();
    }

    protected function headerActions(): array
    {
        return [];
    }

    protected function modalActions(): array
    {
        return [];
    }

    protected function viewAction(): Action
    {
        return ViewAction::make();
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Título')
                ->placeholder('Día laboral')
                ->required(),
            TextInput::make('description')
                ->label('Descripción'),
            DatePicker::make('date')
                ->label('Fecha Seleccionada')
                ->required(),
        ];
    }

    public function eventDidMount(): string
    {
        return <<<'JS'
        function({ event, el }){
            // Tooltip con título y descripción
            el.setAttribute("x-tooltip", "tooltip");
            el.setAttribute("x-data", `{ tooltip: "${event.title} - ${event.extendedProps.description}" }`);
    
            // Limpiar el contenido previo del elemento
            el.innerHTML = "";
    
            // Crear el elemento para el título
            const titleElement = document.createElement('div');
            titleElement.textContent = event.title;
            titleElement.style.fontWeight = "bold"; // Negrita
            titleElement.style.fontSize = "16px"; // Tamaño de fuente
            titleElement.style.color = event.textColor; // Color del texto
            el.appendChild(titleElement);
    
            // Crear el elemento para la descripción
            if (event.extendedProps.description) {
                const descriptionElement = document.createElement('div');
                descriptionElement.textContent = event.extendedProps.description;
                descriptionElement.style.color = "#000000"; // Texto negro
                descriptionElement.style.fontSize = "14px"; // Tamaño de fuente ligeramente más grande
                descriptionElement.style.fontWeight = "bold"; // Texto en negrita
                descriptionElement.style.backgroundColor = "#FFA500"; // Fondo naranja
                descriptionElement.style.padding = "6px 10px"; // Espaciado interno
                descriptionElement.style.marginTop = "8px"; // Separación del título
                descriptionElement.style.borderRadius = "5px"; // Bordes redondeados
                descriptionElement.style.boxShadow = "0 2px 5px rgba(0, 0, 0, 0.2)"; // Sombra para profundidad
                el.appendChild(descriptionElement);
            }
        }
        JS;
    }
}
