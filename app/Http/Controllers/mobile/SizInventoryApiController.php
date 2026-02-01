<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\SizType;
use App\Models\SizInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SizInventoryApiController extends Controller
{
    /**
     * Получить список всех видов СИЗ
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSizTypes(Request $request): JsonResponse
    {
        $request->validate([
            'lang' => 'required|string|in:ru,kz',
        ]);

        $lang = $request->input('lang');

        $sizTypes = SizType::select(
            'id',
            'name_ru',
            'name_kz',
            'unit_ru',
            'unit_kz'
        )->get()->map(function ($type) use ($lang) {
            return [
                'id' => $type->id,
                'name' => $lang === 'kz' ? $type->name_kz : $type->name_ru,
                'unit' => $lang === 'kz' ? $type->unit_kz : $type->unit_ru,
                'name_ru' => $type->name_ru,
                'name_kz' => $type->name_kz,
                'unit_ru' => $type->unit_ru,
                'unit_kz' => $type->unit_kz,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $sizTypes,
        ]);
    }

    /**
     * Получить наличие СИЗ по всем типам
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllInventory(Request $request): JsonResponse
    {
        $request->validate([
            'lang' => 'nullable|string|in:ru,kz',
        ]);

        $lang = $request->input('lang', 'ru');

        $inventory = SizType::with(['inventory' => function ($query) {
            $query->orderBy('size');
        }])->get()->map(function ($type) use ($lang) {
            return [
                'id' => $type->id,
                'name' => $lang === 'kz' ? $type->name_kz : $type->name_ru,
                'unit' => $lang === 'kz' ? $type->unit_kz : $type->unit_ru,
                'sizes' => $type->inventory->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'size' => $item->size,
                        'quantity' => $item->quantity,
//                        'in_stock' => $item->quantity > 0,
                    ];
                }),
                'total_quantity' => $type->inventory->sum('quantity'),
                'available_sizes_count' => $type->inventory->where('quantity', '>', 0)->count(),
                'total_sizes_count' => $type->inventory->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'last_update' => SizInventory::max('updated_at') ? \Carbon\Carbon::parse(SizInventory::max('updated_at'))->format('d.m.Y H:i:s') : null,
            'data' => $inventory,
        ]);
    }

    /**
     * Получить наличие СИЗ по конкретному типу
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getInventoryByType(Request $request): JsonResponse
    {
        $request->validate([
            'lang' => 'required|string|in:ru,kz',
            'type_id' => 'required|integer|exists:siz_types,id',
        ]);

        $lang = $request->input('lang');
        $typeId = $request->input('type_id');

        $type = SizType::with(['inventory' => function ($query) {
            $query->orderBy('size');
        }])->findOrFail($typeId);

        $data = [
            'id' => $type->id,
            'name' => $lang === 'kz' ? $type->name_kz : $type->name_ru,
            'unit' => $lang === 'kz' ? $type->unit_kz : $type->unit_ru,
            'name_ru' => $type->name_ru,
            'name_kz' => $type->name_kz,
            'unit_ru' => $type->unit_ru,
            'unit_kz' => $type->unit_kz,
            'sizes' => $type->inventory->map(function ($item) {
                return [
                    'id' => $item->id,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'in_stock' => $item->quantity > 0,
                    'status' => $item->quantity > 0 ? 'available' : 'out_of_stock',
                ];
            }),
            'total_quantity' => $type->inventory->sum('quantity'),
            'available_sizes_count' => $type->inventory->where('quantity', '>', 0)->count(),
            'out_of_stock_sizes_count' => $type->inventory->where('quantity', 0)->count(),
            'total_sizes_count' => $type->inventory->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Получить только доступные СИЗ (quantity > 0)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableInventory(Request $request): JsonResponse
    {
        $request->validate([
            'lang' => 'required|string|in:ru,kz',
        ]);

        $lang = $request->input('lang');

        $inventory = SizType::whereHas('inventory', function ($query) {
            $query->where('quantity', '>', 0);
        })->with(['inventory' => function ($query) {
            $query->where('quantity', '>', 0)->orderBy('size');
        }])->get()->map(function ($type) use ($lang) {
            return [
                'id' => $type->id,
                'name' => $lang === 'kz' ? $type->name_kz : $type->name_ru,
                'unit' => $lang === 'kz' ? $type->unit_kz : $type->unit_ru,
                'sizes' => $type->inventory->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'size' => $item->size,
                        'quantity' => $item->quantity,
                    ];
                }),
                'total_quantity' => $type->inventory->sum('quantity'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    /**
     * Получить отсутствующие СИЗ (quantity = 0)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOutOfStockInventory(Request $request): JsonResponse
    {
        $request->validate([
            'lang' => 'required|string|in:ru,kz',
        ]);

        $lang = $request->input('lang');

        $inventory = SizType::whereHas('inventory', function ($query) {
            $query->where('quantity', 0);
        })->with(['inventory' => function ($query) {
            $query->where('quantity', 0)->orderBy('size');
        }])->get()->map(function ($type) use ($lang) {
            return [
                'id' => $type->id,
                'name' => $lang === 'kz' ? $type->name_kz : $type->name_ru,
                'unit' => $lang === 'kz' ? $type->unit_kz : $type->unit_ru,
                'out_of_stock_sizes' => $type->inventory->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'size' => $item->size,
                    ];
                }),
                'out_of_stock_count' => $type->inventory->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    /**
     * Поиск СИЗ по названию
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchSiz(Request $request): JsonResponse
    {
        $request->validate([
            'lang' => 'required|string|in:ru,kz',
            'query' => 'required|string|min:2',
        ]);

        $lang = $request->input('lang');
        $query = $request->input('query');

        $types = SizType::where('name_ru', 'LIKE', "%{$query}%")
            ->orWhere('name_kz', 'LIKE', "%{$query}%")
            ->with(['inventory' => function ($q) {
                $q->orderBy('size');
            }])
            ->get()
            ->map(function ($type) use ($lang) {
                return [
                    'id' => $type->id,
                    'name' => $lang === 'kz' ? $type->name_kz : $type->name_ru,
                    'unit' => $lang === 'kz' ? $type->unit_kz : $type->unit_ru,
                    'sizes' => $type->inventory->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'size' => $item->size,
                            'quantity' => $item->quantity,
                            'in_stock' => $item->quantity > 0,
                        ];
                    }),
                    'total_quantity' => $type->inventory->sum('quantity'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $types,
            'count' => $types->count(),
        ]);
    }

    /**
     * Получить статистику по СИЗ
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $request->validate([
            'lang' => 'required|string|in:ru,kz',
        ]);

        $lang = $request->input('lang');

        $totalTypes = SizType::count();
        $totalInventory = SizInventory::count();
        $availableInventory = SizInventory::where('quantity', '>', 0)->count();
        $outOfStockInventory = SizInventory::where('quantity', 0)->count();
        $totalQuantity = SizInventory::sum('quantity');

        $topTypes = SizType::with(['inventory' => function ($query) {
            $query->orderBy('size');
        }])->get()->map(function ($type) use ($lang) {
            return [
                'id' => $type->id,
                'name' => $lang === 'kz' ? $type->name_kz : $type->name_ru,
                'unit' => $lang === 'kz' ? $type->unit_kz : $type->unit_ru,
                'total_quantity' => $type->inventory->sum('quantity'),
            ];
        })->sortByDesc('total_quantity')->take(10)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'total_types' => $totalTypes,
                'total_inventory_items' => $totalInventory,
                'available_items' => $availableInventory,
                'out_of_stock_items' => $outOfStockInventory,
                'total_quantity' => $totalQuantity,
                'availability_percentage' => $totalInventory > 0 ? round(($availableInventory / $totalInventory) * 100, 2) : 0,
                'top_types' => $topTypes,
            ],
        ]);
    }
}
