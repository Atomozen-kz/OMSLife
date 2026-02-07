<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpsDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WialonIngestController extends Controller
{
    public function ingest(Request $request)
    {
        // защита (токен из Node)
        $token = $request->header('X-INGEST-TOKEN');
        if (!$token || $token !== config('gps.ingest_token')) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'protocol' => 'nullable|string|max:32',
            'device_id' => 'required|string|max:64',

            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
            'speed' => 'nullable|integer|min:0|max:400',
            'course' => 'nullable|integer|min:0|max:360',
            'altitude' => 'nullable|numeric',
            'sats' => 'nullable|integer|min:0|max:200',

            'device_time' => 'nullable|string|max:32',
            'received_at' => 'nullable|string|max:64',

            'sensors' => 'nullable|array',
            'raw' => 'nullable|string',
        ]);

        $deviceTime = !empty($data['device_time']) ? Carbon::parse($data['device_time']) : null;
        $receivedAt = !empty($data['received_at']) ? Carbon::parse($data['received_at']) : now();

        // только последнее состояние (upsert)
        GpsDevice::updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'protocol' => $data['protocol'] ?? 'wialon_ips',
                'lat' => $data['lat'] ?? null,
                'lon' => $data['lon'] ?? null,
                'speed' => $data['speed'] ?? null,
                'course' => $data['course'] ?? null,
                'altitude' => $data['altitude'] ?? null,
                'sats' => $data['sats'] ?? null,
                'device_time' => $deviceTime,
                'received_at' => $receivedAt,
                'sensors' => $data['sensors'] ?? null,
                'raw' => $data['raw'] ?? null,
            ]
        );

        return response()->json(['ok' => true]);
    }
}
