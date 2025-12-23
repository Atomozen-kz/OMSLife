<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetLoyaltyCardsCategoriesRequest;
use App\Http\Requests\GetLoyaltyCardsRequest;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyCardsCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class LoyaltyCardController extends Controller
{
    // старая версия
    public function index(): JsonResponse
    {
        $loyaltyCards = LoyaltyCard::select('id', 'name', 'description', 'instagram', 'discount_percentage', 'address', 'status', 'logo')
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($card) {
                $card->logo = url($card->logo);  // Форматируем logo как полный URL
                return $card;
            });

        return response()->json([
            'data' => $loyaltyCards,
        ]);
    }
    /**
     * Получить список организаций с картами лояльности.
     *
     * @return JsonResponse
     */
    public function indexWithCategory(GetLoyaltyCardsRequest $request): JsonResponse
    {
        $categoryId = $request->input('category_id');

        $query = LoyaltyCard::select(
            'id', 'name', 'description', 'instagram',
            'discount_percentage', 'address', 'status',
            'category_id', 'logo','sort_order'
        );

        // Фильтруем по category_id, если параметр передан
        if (!is_null($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        $loyaltyCards = $query->orderBy('sort_order')->get()->map(function ($card) {
            $card->logo = url($card->logo); // Форматируем logo как полный URL
            return $card;
        });

        return response()->json([
            'data' => $loyaltyCards,
        ]);
    }

    public function categories(GetLoyaltyCardsCategoriesRequest $request): JsonResponse
    {
        $lang = $request->input('lang');
        if (!in_array($lang, ['ru', 'kz', 'kk'])) {
            $lang = 'ru';
        }
        $langColumn = 'name_' . $lang;
        $categoriesList = LoyaltyCardsCategory::where('status',1)->select('id', $langColumn,'image_path','color_rgb')->get()->map(function ($category) {
            $category->url = url($category->image_path);
            $category->color_rgb = array_map('intval', explode(',', $category->color_rgb));
            return $category;
        });

        $categoriesList = $categoriesList->map(function ($category) use ($langColumn) {
            return [
                'id'   => $category->id,
                'name' => $category->{$langColumn},
                'url' => $category->url,
                'color_rgb' => $category->color_rgb,
            ];
        });

        return response()->json([
            'data' => $categoriesList,
        ]);
    }
}
