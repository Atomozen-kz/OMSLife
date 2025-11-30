<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingRecordController extends Controller
{
    /**
     * Получить записи об обучении сотрудника.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrainingRecords(Request $request)
    {
        $userId = Auth::id(); // Получаем ID сотрудника из токена
        $lang = $request->get('lang', 'ru'); // Получаем язык запроса (по умолчанию 'ru')

        // Получаем все типы обучения
        $trainingTypes = TrainingType::all();

        // Формируем результат
        $records = $trainingTypes->map(function ($type) use ($userId, $lang) {
            // Получаем последнюю запись для данного типа
            $record = TrainingRecord::where('id_training_type', $type->id)
                ->where('id_sotrudnik', $userId)
                ->latest('completion_date')
                ->first();

            if (!$record) {
                return null; // Возвращаем null, если записи нет
            }

            // Рассчитываем процент
            $daysValid = $record->validity_date->diffInDays($record->completion_date, true);
            $daysPassed = (int) Carbon::now()->diffInDays($record->completion_date, true);
            $daysLeft = (int) Carbon::now()->diffInDays($record->validity_date, true);
            $percentage = ($daysPassed * 100)/$daysValid;
//            $percentage = min(100, round(($daysPassed / ($validityDays + $daysPassed)) * 100));

            return [
                'type_name' => $lang === 'kz' ? $type->name_kz : $type->name_ru,
                'type_code' => $type->type_code,
                'completion_date' => Carbon::make($record->completion_date->format('Y-m-d'))->isoFormat('LL'),
                'validity_date' => Carbon::make($record->validity_date->format('Y-m-d'))->isoFormat('LL'),
                'percent' => $percentage,
                'days_passed' => $daysPassed,
                'validityDays' => $daysLeft,
                'certificate_number' => $record->certificate_number,
                'protocol_number' => $record->protocol_number,
            ];
        })->filter(); // Удаляем null из коллекции

        return response()->json([
            'data' => $records->values(), // Обновляем индексы массива
        ]);
    }

}
