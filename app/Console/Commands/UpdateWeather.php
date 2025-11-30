<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UpdateWeather extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-weather';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Загрузка JSON файла с описаниями погоды
        $jsonPath = storage_path('app/transformed_weather_data.json');
        if (!file_exists($jsonPath)) {
            $this->error('Файл с описаниями погоды не найден!');
            return;
        }

        $weatherDescriptions = json_decode(file_get_contents($jsonPath), true);

        // 2. Запрос к API Open-Meteo
        $apiUrl = 'https://api.open-meteo.com/v1/forecast';
        $latitude = 43.3125;
        $longitude = 52.875;

        $openMeteoToken = Config::get('app.openMeteoApiKey');

        $response = Http::withHeaders([
            'X-Gismeteo-Token' => $openMeteoToken,
        ])->get($apiUrl, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
            'timezone' => 'Asia/Aqtau'
        ]);


        if (!$response->successful()) {
            $this->error('Не удалось получить данные с API');
            return;
        }

        $data = $response->json();

        // 3. Переформатирование данных
        $formattedData = [];
        foreach ($data['daily']['time'] as $index => $date) {
            $weatherCode = $data['daily']['weather_code'][$index];
            $maxTemp = round($data['daily']['temperature_2m_max'][$index]);
            $minTemp = round($data['daily']['temperature_2m_min'][$index]);

            $descriptionRu = $weatherDescriptions[$weatherCode]['description']['ru'] ?? 'Неизвестно';
            $descriptionKz = $weatherDescriptions[$weatherCode]['description']['kz'] ?? 'Белгісіз';
            $iconDay = $weatherDescriptions[$weatherCode]['image']['day'] ?? '';
            $iconNight = $weatherDescriptions[$weatherCode]['image']['night'] ?? '';

            $formattedData[] = [
                'date' => Carbon::make($date)->format('d.m'),
                'max_temperature' => $maxTemp,
                'min_temperature' => $minTemp,
                'description' => [
                    'ru' => $descriptionRu,
                    'kz' => $descriptionKz
                ],
                'icon' => [
                    'day' => $iconDay,
                    'night' => $iconNight
                ]
            ];
        }

        // 4. Сохранение переформатированных данных
        Storage::disk('public')->put('weather_data.json', json_encode($formattedData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->info('Данные о погоде успешно обновлены и сохранены.');
    }
}
