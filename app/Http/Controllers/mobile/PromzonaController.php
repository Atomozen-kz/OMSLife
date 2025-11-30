<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddPromzonaObjectRequest;
use App\Http\Requests\SearchPromzonaObjectRequest;
use App\Models\OrganizationStructure;
use App\Models\PromzonaObject;
use App\Models\PromzonaType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromzonaController extends Controller
{
    /**
     * Получить список объектов.
     *
     * @return JsonResponse
     */
    public function getObjects(Request $request): JsonResponse
    {
        $language = $request->input('lang', 'ru');
        $parent_id = $request->input('parent_id', null);

        $parent = [
            'id' => NULL,
            'name' => NULL,
            'icon' => NULL
        ];

        if ($parent_id) {
            $p = PromzonaObject::find($parent_id);
            $parent = [
                    'id' => $p->id, // Имя на текущем языке
                    'name' => $p->type->{"name_$language"}. ' - '. $p->number, // Имя на текущем языке
                    'icon_text' => $p->type->icon_text // Иконка
                ];
        }

        $objects = PromzonaObject::with(['type'])
            ->where('status', 1) // Фильтруем только объекты со статусом = 1
            ->where('parent_id', $parent_id)
            ->get()
            ->map(function ($object) use ($language) {
                return [
                    'id' => $object->id,
                    'name' => $object->type?->{"name_$language"}.' - '.$object->number, // Имя типа на текущем языке
                    'icon_text' => $object->type?->icon_text, // Иконка
                    'lat' => $object->lat,
                    'lng' => $object->lng,
                    'created_at' => $object->created_at->isoFormat('L'), // Форматируем дату
                ];
            });

        return response()->json(['parent' => $parent, 'data' => $objects]);
    }

    /**
     * Получить список организаций и типов объектов.
     *
     * @return JsonResponse
     */
    public function getFilters(Request $request): JsonResponse
    {
        // Получаем все статусы и объекты для фильтрации
        $language = $request->input('lang', 'ru');
        return response()->json([
            'types' => PromzonaType::where('status', true)->get(['id', 'name_'.$language, 'icon_text']),
            //'organizations' => OrganizationStructure::where('is_promzona', true)->get(['id', 'name_'.$language]),
        ]);
    }

    /**
     * Поиск объектов.
     *
     * @param SearchPromzonaObjectRequest $request
     * @return JsonResponse
     */
    public function searchObjects(SearchPromzonaObjectRequest $request): JsonResponse
    {
        $language = $request->input('lang', 'ru');
        $query = PromzonaObject::query();

        // Фильтрация по id_organization, если указано
        if ($request->filled('id_organization')) {
            $query->where('id_organization', $request->id_organization);
        }

        // Фильтрация по id_type, если указано
        if ($request->filled('id_type')) {
            $query->where('id_type', $request->id_type);
        }

        // Фильтрация по номеру объекта, если указано
        if ($request->filled('number')) {
            $query->where('number', 'LIKE', "%{$request->number}%");
        }

        // Фильтрация по географическим координатам, если указаны
        $objects = $query->with(['type', 'organization'])
            ->where('status', 1) // Учитываем только объекты с `status = 1`
            ->get()
            ->map(function ($object) use ($language) {
                return [
                    'id' => $object->id,
                    'organization_name' => $object->organization?->{"name_$language"}, // Имя организации на текущем языке
                    'type_name' => $object->type?->{"name_$language"}, // Имя типа на текущем языке
                    'icon_text' => $object->type?->icon_text, // Иконка
                    'number' => $object->number,
                    'lat' => $object->lat,
                    'lng' => $object->lng,
                    'created_at' => $object->created_at->isoFormat('L'), // Форматируем дату
                ];
            });

        return response()->json(['data' => $objects]);
    }

    /**
     * Добавить объект.
     *
     * @param AddPromzonaObjectRequest $request
     * @return JsonResponse
     */
    public function addObject(AddPromzonaObjectRequest $request): JsonResponse
    {
        $userId = auth()->user()->id;

        if (
            PromzonaObject::where('id_type', $request->id_type)
            ->where('id_organization', $request->id_organization)
            ->where('number', $request->number)
            ->first()
        ){
            return response()->json(['message' => 'Объект с таким номером уже существует'], 409);
        }

        $object = PromzonaObject::create([
            'id_type' => $request->id_type,
            'id_organization' => $request->id_organization,
            'number' => $request->number,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'id_sotrudnik' => $userId,
        ]);

        return response()->json(['message' => 'Объект успешно добавлен']);
    }
}
