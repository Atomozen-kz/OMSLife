<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\PickupPointsMilkApiRequest;
use App\Services\PickupPointService;
use Illuminate\Http\JsonResponse;

class PickupPointController extends Controller
{
    /**
     * @var PickupPointService
     */
    protected $pickupPointService;

    public function __construct(PickupPointService $pickupPointService)
    {
        $this->pickupPointService = $pickupPointService;
    }

    /**
     * Возвращает список пунктов выдачи молока.
     *
     * @return JsonResponse
     */
    public function getPickupPointsMilk(PickupPointsMilkApiRequest $request): JsonResponse
    {
        $pickupPoints = $this->pickupPointService->getPickupPointsForApi();
        $sotrudnik = auth()->user();
        return response()->json([
            'success' => true,
            'milk_code' => $sotrudnik->milkCodes ? $sotrudnik->milkCodes->code : null,
            'data' => $pickupPoints,
        ]);
    }
}
