<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\PromzonaGeoObject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PromzonaGeoObjectController extends Controller
{
   public function index(){
       $token = config('app.promzonaGeoObjectsApiToken');

       $response = Http::withHeaders([
           'Authorization' => 'Bearer ' . $token,
       ])->post('https://omglife.kz/api/promzona-all-data');

       if ($response->successful()) {
           $data = $response->json();
           $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

           return response($jsonContent)
               ->header('Content-Type', 'application/json')
               ->header('Content-Disposition', 'attachment; filename="promzona_data.json"');
       }

       return response()->json([
           'error' => 'Не удалось получить данные',
           'message' => $response->body()
       ], $response->status());
   }

   public function files()
   {
       $token = config('app.promzonaGeoObjectsApiToken');

       $response = Http::withHeaders([
           'Authorization' => 'Bearer ' . $token,
       ])->get('https://omglife.kz/api/promzona/files');

       if ($response->successful()) {
            return response()->json(array_merge($response->json() ?? [], ['token' => $token]));
       }

       return response()->json([
           'error' => 'Не удалось получить данные',
           'message' => $response->body()
       ], $response->status());
   }

   public function map_version()
   {
       return response()->json([
           'version' => 1,
       ]);
   }
}
