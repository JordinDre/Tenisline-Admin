<?php

namespace App\Filament\Resources\MetaResource\Widgets;

use App\Models\Labor;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class Calendario extends FullCalendarWidget
{
    public Model|string|null $model = Labor::class;

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
        return [
            CreateAction::make()
                ->mountUsing(
                    function (Form $form, array $arguments) {
                        $form->fill([
                            'date' => $arguments['start'] ?? null,
                        ]);
                    }
                )
                ->action(function (array $data) {
                    $existingLabor = Labor::where('date', $data['date'])->first();
                    if ($existingLabor) {
                        Notification::make()
                            ->title('Error')
                            ->body('Ya existe un evento programado para esta fecha.')
                            ->danger()
                            ->send();

                        return;
                    }
                    Labor::create($data);
                    Notification::make()
                        ->title('Evento creado')
                        ->body('El evento ha sido creado exitosamente.')
                        ->success()
                        ->send();
                    $this->redirect(request()->header('Referer'));
                }),
            Action::make('Generar días laborales')
                ->action('generateLaborsForCurrentMonth')
                ->color('primary')
                ->icon('heroicon-o-calendar'),
        ];
    }

    public function generateLaborsForCurrentMonth(): void
    {
        $dates = collect();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        for ($date = $startOfMonth; $date->lte($endOfMonth); $date->addDay()) {
            if (! $date->isSunday()) {
                $dates->push($date->toDateString());
            }
        }

        foreach ($dates as $date) {
            Labor::firstOrCreate([
                'date' => $date,
            ], [
                'title' => 'Día Laboral',
            ]);
        }

        Notification::make()
            ->title('Días laborales Generados')
            ->success()
            ->send();

        $this->redirect(request()->header('Referer'));
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
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

    public function config(): array
    {
        return [
        /* 'headerToolbar' => [
                'left' => 'dayGridMonth',
                'center' => 'title',
                'right' => 'prev,next today',
            ], */];
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
