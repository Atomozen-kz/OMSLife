<?php

namespace App\Orchid\Screens;

use App\Models\PromzonaGeoObject;
use App\Models\PromzonaType;
use App\Orchid\Filters\PromzonaNamesFilter;
use App\Orchid\Filters\PromzonaTypesFilter;
use App\Orchid\Layouts\PromzonaSelection;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class PromzonaGeoObjectsScreen extends Screen
{
    public function filters(): array
    {
        return [
            PromzonaTypesFilter::class,
            PromzonaNamesFilter::class,
        ];
    }

    public function query(): iterable
    {
        $geoObjectsQuery = PromzonaGeoObject::defaultSort('name')
            ->with('promzonaType');

        if ($name = request('name')) {
            $geoObjectsQuery->where('name', 'like', "%{$name}%");
        }
        return [
            'promzonaTypes' => PromzonaType::all(),
            'geoObjects' => PromzonaGeoObject::defaultSort('name')
                ->filters()
                ->filtersApply([PromzonaTypesFilter::class, PromzonaNamesFilter::class])
                ->with('promzonaType')->paginate(),
        ];
    }

    public function name(): ?string
    {
        return 'Объекты пром. зоне';
    }

    public function commandBar(): iterable
    {
        return [
//            ModalToggle::make('Import GeoJSON OBJECTS')
//                ->icon('cloud-upload')
//                ->modal('importGeoJsonModal')
//                ->method('importGeoJson'),
//
//            ModalToggle::make('Import ZU GeoJSON')
//                ->icon('cloud-upload')
//                ->modal('importZUGeoJsonModal')
//                ->method('importZUGeoJson'),
//
//            ModalToggle::make('Import Wells GeoJSON')
//                ->icon('cloud-upload')
//                ->modal('importWellsGeoJsonModal')
//                ->method('importWellsGeoJson'),

            ModalToggle::make('Показать типы объектов')
                ->modal('showTypesModal'),
            Link::make('Добавить объект')->route('platform.promzona-edit-point'),
        ];
    }

    public function layout(): iterable
    {
        return [
            PromzonaSelection::class,
//            Layout::selection([
//                PromzonaTypesFilter::class,
//                PromzonaNamesFilter::class
//            ]),

            Layout::table('geoObjects', [
                TD::make('name', 'Название')
                    ->sort(),

                TD::make('promzonaType.name_ru', 'Тип объекта')
                    ->render(function (PromzonaGeoObject $record) {
                        return optional($record->promzonaType)->name_ru ?? 'Не указан';
                    }),

                TD::make('geometry', 'Геометрия')
                    ->render(function ($geoObject) {
                        $geometry = json_decode($geoObject->geometry, true);
                        return view('promzona-map.view-point', [
                            'geometry' => $geometry,
                            'id'       => $geoObject->id,
                        ])->render();
                    }),

                TD::make('actions', 'Действия')
                    ->render(function ($geoObject) {
                        $links = [
                            Link::make('Редактировать')
                                ->icon('pencil')
                                ->route('platform.promzona-edit-point', ['geoObject' => $geoObject->id]),
                            Button::make('Удалить')
                                ->icon('trash')
                                ->confirm('Вы уверены, что хотите удалить объект?')
                                ->method('deleteGeoObject')
                                ->parameters(['id' => $geoObject->id]),
                        ];
                        return implode(' ', array_map(function ($link) {
                            return (string) $link;
                        }, $links));
                    })->width(100)->alignRight(),
            ]),

            Layout::modal('importGeoJsonModal', [
                Layout::rows([
                    Input::make('geojson_file')
                        ->type('file')
                        ->title('Upload GeoJSON')
                        ->required(),
                ]),
            ])->title('Import GeoJSON')->applyButton('Import'),

            Layout::modal('importZUGeoJsonModal', [
                Layout::rows([
                    Input::make('zu_geojson_file')
                        ->type('file')
                        ->title('Upload ZU GeoJSON')
                        ->required(),
                ]),
            ])->title('Import ZU GeoJSON')->applyButton('Import'),

            Layout::modal('importWellsGeoJsonModal', [
                Layout::rows([
                    Input::make('wells_geojson_file')
                        ->type('file')
                        ->title('Upload Wells GeoJSON')
                        ->required(),
                ]),
            ])->title('Import Wells GeoJSON')->applyButton('Import'),

            Layout::modal('showTypesModal', [
                Layout::table('promzonaTypes', [
                    TD::make('name_kz', 'Название (KZ)'),
                    TD::make('name_ru', 'Название (RU)'),
                    TD::make('icon_text', 'Код типа'),
                ]),
            ])
                ->title('Типы объектов')
                ->withoutApplyButton()
                ->applyButton('Закрыть'),
        ];
    }

    public function importGeoJson(Request $request)
    {
        $file = $request->file('geojson_file');
        $geoJson = json_decode(file_get_contents($file->getPathname()), true);

        foreach ($geoJson['features'] as $feature) {
            $properties = $feature['properties'];
            $geometry = json_encode($feature['geometry']);

            $pd = $properties['PD'] ?? null;
            $type = PromzonaType::where('name_ru', 'LIKE', '%' . $pd . '%')->first();

            PromzonaGeoObject::create([
                'name'     => $properties['NAME'] ?? null,
                'type'     => 'object',
                'id_type'  => $type ? $type->id : null,
                'comment'  => $properties['WORKSHOP'] ?? null,
                'geometry' => $geometry,
            ]);
        }

        Alert::info('GeoJSON imported successfully.');
    }

    public function importZUGeoJson(Request $request)
    {
        $file = $request->file('zu_geojson_file');
        $geoJson = json_decode(file_get_contents($file->getPathname()), true);

        foreach ($geoJson['features'] as $feature) {
            $properties = $feature['properties'];
            $geometry = json_encode($feature['geometry']);

            $ngdu = $properties['NGDU_'] ?? null;
            $type = $ngdu ? PromzonaType::where('name_ru', 'like', "%$ngdu%")->first() : null;

            PromzonaGeoObject::create([
                'name'     => $properties['NAME_'] ?? null,
                'type'     => 'zu',
                'id_type'  => $type ? $type->id : null,
                'geometry' => $geometry,
            ]);
        }

        Alert::info('ZU GeoJSON imported successfully.');
    }

    public function importWellsGeoJson(Request $request)
    {
        $file = $request->file('wells_geojson_file');
        $geoJson = json_decode(file_get_contents($file->getPathname()), true);

        foreach ($geoJson['features'] as $feature) {
            $properties = $feature['properties'];
            $geometry = json_encode($feature['geometry']);

            $name = 'СК-' . ($properties['WELLS'] ?? '');
            $comment = ($properties['DATABUR'] ?? '') . ' ' . ($properties['ISPOLNITEL'] ?? '');

            PromzonaGeoObject::create([
                'name'     => $name,
                'type'     => 'sk',
                'comment'  => $comment,
                'geometry' => $geometry,
            ]);
        }

        Alert::info('Wells GeoJSON imported successfully.');
    }

    public function deleteGeoObject(Request $request)
    {
        $id = $request->get('id');
        $geoObject = PromzonaGeoObject::findOrFail($id);
        $geoObject->delete();
        Alert::info('Объект удален успешно.');
    }
}
