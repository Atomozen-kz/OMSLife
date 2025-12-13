<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\PartnerPlace;
use App\Models\PartnerPlaceVisit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerPlaceApiController extends Controller
{
    /**
     * Получить список всех активных партнёрских мест
     *
     * @return JsonResponse
     */
    public function getPartnerPlaces(): JsonResponse
    {
        $places = PartnerPlace::where('status', true)
            ->select('id', 'name', 'description', 'address', 'category', 'logo', 'qr_code')
            ->orderBy('name')
            ->get()
            ->map(function ($place) {
                if ($place->logo) {
                    $place->logo = url($place->logo);
                }
                return $place;
            });

        return response()->json([
            'success' => true,
            'data' => $places,
        ]);
    }

    /**
     * Подтвердить посещение по QR-коду
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirmVisit(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => 'required|string|uuid',
        ]);

        $qrCode = $request->input('qr_code');
        $sotrudnik = $request->user();

        // Найти место по QR-коду
        $place = PartnerPlace::where('qr_code', $qrCode)
            ->where('status', true)
            ->first();

        if (!$place) {
            return response()->json([
                'success' => false,
                'message' => 'Место не найдено или неактивно',
            ], 404);
        }

        // Проверка: был ли визит в это место за последние 2 часа
        $twoHoursAgo = Carbon::now()->subHours(2);
        $recentVisit = PartnerPlaceVisit::where('partner_place_id', $place->id)
            ->where('sotrudnik_id', $sotrudnik->id)
            ->where('visited_at', '>=', $twoHoursAgo)
            ->first();

        if ($recentVisit) {
            $nextAllowedTime = Carbon::parse($recentVisit->visited_at)->addHours(2);
            $minutesLeft = (int) now()->diffInMinutes($nextAllowedTime, false);

            return response()->json([
                'success' => false,
                'message' => "Вы уже отметились в этом месте. Повторное сканирование доступно через {$minutesLeft} минут.",
                'next_allowed_at' => $nextAllowedTime->toIso8601String(),
            ], 429);
        }

        // Создать запись о визите
        $visit = PartnerPlaceVisit::create([
            'partner_place_id' => $place->id,
            'sotrudnik_id' => $sotrudnik->id,
            'visited_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Посещение успешно подтверждено',
            'data' => [
                'visit_id' => $visit->id,
                'place_name' => $place->name,
                'place_category' => $place->category,
                'visited_at' => $visit->visited_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Получить историю посещений сотрудника
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyVisits(Request $request): JsonResponse
    {
        $sotrudnik = $request->user();

        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $visits = PartnerPlaceVisit::with(['partnerPlace:id,name,category,logo,address'])
            ->where('sotrudnik_id', $sotrudnik->id)
            ->orderBy('visited_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $visitsData = $visits->getCollection()->map(function ($visit) {
            $place = $visit->partnerPlace;
            return [
                'id' => $visit->id,
                'visited_at' => $visit->visited_at->toIso8601String(),
                'visited_at_formatted' => $visit->visited_at->format('d.m.Y H:i'),
                'place' => [
                    'id' => $place->id,
                    'name' => $place->name,
                    'category' => $place->category,
                    'address' => $place->address,
                    'logo' => $place->logo ? url($place->logo) : null,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $visitsData,
            'pagination' => [
                'current_page' => $visits->currentPage(),
                'last_page' => $visits->lastPage(),
                'per_page' => $visits->perPage(),
                'total' => $visits->total(),
            ],
        ]);
    }

    /**
     * Получить статистику посещений сотрудника
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyVisitsStats(Request $request): JsonResponse
    {
        $sotrudnik = $request->user();

        $totalVisits = PartnerPlaceVisit::where('sotrudnik_id', $sotrudnik->id)->count();

        $visitsThisMonth = PartnerPlaceVisit::where('sotrudnik_id', $sotrudnik->id)
            ->whereBetween('visited_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $visitsThisWeek = PartnerPlaceVisit::where('sotrudnik_id', $sotrudnik->id)
            ->whereBetween('visited_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $uniquePlacesVisited = PartnerPlaceVisit::where('sotrudnik_id', $sotrudnik->id)
            ->distinct('partner_place_id')
            ->count('partner_place_id');

        return response()->json([
            'success' => true,
            'data' => [
                'total_visits' => $totalVisits,
                'visits_this_month' => $visitsThisMonth,
                'visits_this_week' => $visitsThisWeek,
                'unique_places_visited' => $uniquePlacesVisited,
            ],
        ]);
    }
}

