<?php

namespace App\Orchid\Screens;

use App\Models\PromzonaGeoObject;
use App\Models\PromzonaType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class PromzonaMapEditScreen extends Screen
{
    public $geoObject;

    public function query(PromzonaGeoObject $geoObject): iterable
    {
        $parentOptions = PromzonaGeoObject::whereNull('parent_id')->get();
        return [
            'geoObject'     => $geoObject,
            'promzonaTypes' => PromzonaType::all(),
            'parentOptions' => $parentOptions,
        ];
    }

    public function name(): ?string
    {
        return 'Редактирование геообъекта';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Назад')
                ->icon('arrow-left')
                ->route('platform.promzona-geo-objects'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('promzona-map.edit-point', [
                'geoObject' => $this->geoObject,
            ]),
        ];
    }

    public function clearMap(Request $request)
    {
        return Alert::info('Карта очищена');
    }

    public function saveGeoObject(Request $request)
    {
        $validated = $request->validate([
            'geometry'  => 'nullable|string',
            'geoObj.id' => 'nullable|integer|exists:promzona_geo_objects,id',
            'name'      => 'nullable|string|max:255',
            'id_type'   => 'nullable|integer|exists:promzona_types,id',
            'parent_id' => 'nullable|integer|exists:promzona_geo_objects,id',
        ]);

        try {
            if (isset($validated['geoObj']['id'])) {
                $geoObj = PromzonaGeoObject::findOrFail($validated['geoObj']['id']);
            } else {
                $geoObj = new PromzonaGeoObject();
            }

            // Обновляем поля, если они присутствуют в запросе
            if (isset($validated['geometry'])) {
                $geoObj->geometry = $validated['geometry'];
            }
            if (isset($validated['name'])) {
                $geoObj->name = $validated['name'];
            }
            if (isset($validated['id_type'])) {
                $geoObj->id_type = $validated['id_type'];
            }
            // Даже если parent_id равен пустой строке, приводим к null
            $geoObj->parent_id = $validated['parent_id'] ?? null;

            $geoObj->save();

            return response()->json(['success' => true, 'id' => $geoObj->id]);
        } catch (\Exception $e) {
            Log::error('Ошибка сохранения данных', [
                'error'        => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ]);
        }
    }


    public function getChildren(Request $request)
    {
        $parentId = $request->get('parent_id');
        $children = PromzonaGeoObject::where('parent_id', $parentId)
            ->pluck('name', 'id');
        return response()->json($children);
    }
}
