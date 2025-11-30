<?php

namespace App\Services;

use App\Models\PickupPoint;
use Carbon\Carbon;

class PickupPointService
{
    /**
     * Получает все пункты выдачи молока и обрабатывает их для API.
     *
     * @return array
     */
    public function getPickupPointsForApi(): array
    {
        $pickupPoints = PickupPoint::where('status', 1)->get();

        $processedPoints = $pickupPoints->map(function ($pickup) {

            return [
                'name' => $pickup->name,
                'quantity' => $pickup->quantity,
                'status' => $pickup->is_open ? 1 : 0,
                'is_open' => $pickup->is_open,
                'logo' => url($pickup->logo),
                'address' => $pickup->address,
                'geolocation' => [
                    'lat' => $pickup->lat,
                    'lng' => $pickup->lng,
                ],
            ];
        })->toArray();

        return $processedPoints;
    }
}
