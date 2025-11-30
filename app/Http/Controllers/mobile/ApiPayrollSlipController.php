<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\Sotrudniki;
use Illuminate\Http\Request;

class ApiPayrollSlipController extends Controller
{
    public function enablePayrollSlip(Request $request)
    {
        $request->validate([
            'iin' => 'required|string|size:12', // ИИН строго 12 символов
        ]);

        $iin = $request->input('iin');
        $employee = auth()->user();

        if (!$employee) {
            return response()->json(['message' => 'Сотрудник не найден'], 404);
        }

        $another_employee = Sotrudniki::where('iin', $iin)->where('id', '!=', $employee->id)->first();

        if ($another_employee) {
            return response()->json([
               'message' => 'Этот ИИН уже зарегистрирован другим сотрудником',
               'status' => 409,
            ]);
        }
        // Проверяем валидность ИИН
        if (!$this->isValidIIN($iin)) {
            return response()->json(['message' => 'Неверный ИИН'], 400);
        }

        // Получаем дату рождения из ИИН
        $birthdate = $this->extractBirthdateFromIIN($iin);

        //гендер пати
        if (isset($iin[6]) && is_numeric($iin[6])) {
            if ($iin[6] != '0') {
                $gender = ($iin[6] % 2 === 0) ? 'woman' : 'man';
            }
        }

        // Обновляем данные сотрудника
        $employee->update([
            'iin' => $iin,
            'birthdate' => $birthdate,
            'is_payroll_slip_func' => true,
            'gender' => $gender ?? null,
        ]);

        return response()->json(['message' => 'Функционал расчетных листов подключен'], 200);
    }

    private function isValidIIN(string $iin): bool
    {
        if (strlen($iin) !== 12 || !ctype_digit($iin)) {
            return false;
        }

        $coefficients = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        $checksum = array_sum(array_map(function ($digit, $coef) {
                return $digit * $coef;
            }, str_split(substr($iin, 0, 11)), $coefficients)) % 11;

        if ($checksum === 10) {
            $coefficients = [3, 4, 5, 6, 7, 8, 9, 10, 11, 1, 2];
            $checksum = array_sum(array_map(function ($digit, $coef) {
                    return $digit * $coef;
                }, str_split(substr($iin, 0, 11)), $coefficients)) % 11;
        }

        return (int)$iin[11] === $checksum;
    }

    private function extractBirthdateFromIIN(string $iin): string
    {
        $year = (int)substr($iin, 0, 2);
        $month = (int)substr($iin, 2, 2);
        $day = (int)substr($iin, 4, 2);
        $centuryIndicator = (int)substr($iin, 6, 1);

        // Определяем век по индикатору
        $century = match (true) {
            // Индикаторы 1 или 2 → 1800-е годы
            $centuryIndicator === 1 || $centuryIndicator === 2 => 1800,

            // Индикаторы 3 или 4 → 1900-е годы
            $centuryIndicator === 3 || $centuryIndicator === 4 => 1900,

            // Индикаторы 5 или 6 → 2000-е годы
            $centuryIndicator === 5 || $centuryIndicator === 6 => 2000,

            // Индикатор 0 → уточняем по году: 0–25 → 2000, 26–99 → 1900
            $centuryIndicator === 0 && $year <= 25 => 2000,
            $centuryIndicator === 0 && $year > 25 => 1900,

            default => throw new \Exception('Неверный ИИН'),
        };

        // Формируем дату рождения
        return sprintf('%d-%02d-%02d', $century + $year, $month, $day);
    }
    public function disablePayrollSlip(Request $request)
    {
        $employee = auth()->user();

        if (!$employee) {
            return response()->json(['message' => 'Сотрудник не найден'], 404);
        }

        $employee->update([
            'is_payroll_slip_func' => false,
        ]);

        return response()->json(['message' => 'Функционал расчетных листов отключен'], 200);
    }
}
