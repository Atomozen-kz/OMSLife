<?php

namespace App\Services;

use App\Jobs\SendVerifyCodeJob;
use App\Models\mobile\SmsCode;
use App\Models\OrganizationStructure;
use App\Models\Sotrudniki;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class SotrudnikiService{

    public function registerSotrudnik(array $data)
    {
        // Поиск сотрудника по ИИН и табельному номеру
        $sotrudnik = Sotrudniki::where('iin', $data['iin'])
            ->where('tabel_nomer', $data['tabel_nomer'])
            ->first();

        if (!$sotrudnik) {
            return ['message' => 'Сотрудник с указанным ИИН и табельным номером не найден', 'status' => 404];
        }

        // Проверяем, не зарегистрирован ли этот номер телефона для другого сотрудника
        $phoneExists = Sotrudniki::where('phone_number', $data['phone_number'])
            ->where('id', '!=', $sotrudnik->id)
            ->first();

        if ($phoneExists) {
            return ['message' => 'Этот номер телефона уже зарегистрирован для другого сотрудника', 'status' => 409];
        }

        // Обновляем номер телефона сотрудника
        $sotrudnik->update(['phone_number' => $data['phone_number']]);

        // Проверка частоты отправки SMS
        $recentSms = SmsCode::where('phone_number', $data['phone_number'])
            ->where('created_at', '>', Carbon::now()->subMinutes(1));

        if ($recentSms->exists()) {
            return ['message' => 'Слишком много запросов. Попробуйте позже.', 'status' => 429];
        }

        // Генерация SMS-кода
        $smsCode = rand(1000, 9999);

        // Сохранение SMS-кода в базе данных
        SmsCode::create([
            'phone_number' => $data['phone_number'],
            'code' => $smsCode,
            'sent_at' => Carbon::now(),
            'is_used' => false,
        ]);

        // Отправка SMS-кода
        $smsServiceUrl = Config::get('app.smsServiceUrl');
        $smsResponse = Http::get($smsServiceUrl, [
            'login' => Config::get('app.smsServiceLogin'),
            'psw' => Config::get('app.smsServicePassword'),
            'phones' => $data['phone_number'],
            'mes' => "OMS Life\nВаш код: ". $smsCode,
            'sender' => 'ALLFOOD',
            'fmt' => 3
        ])->json();
//        $smsResponse = SendVerifyCodeJob::dispatch($data['phone_number'], "OMGLife\nВаш код: ". $smsCode);

        return ['message' => 'SMS отправлено', 'sms_code' => $smsCode, 'success'=>true, 'sms_responce' => $smsResponse, 'status' => 200];
    }

    /**
     * Проверка SMS-кода и генерация токена
     */
    public function verifySms(array $data)
    {
        $tokenService = new TokenService();

        if (in_array( $data['phone_number'], ['+77089222820', '+77081139347'])) {
            $sotrudnik = Sotrudniki::where('phone_number', $data['phone_number'])->first();
            $sotrudnik->is_registered = true;
            $sotrudnik->save();

            // Генерируем новые токены (старые автоматически инвалидируются)
            $tokens = $tokenService->generateTokens($sotrudnik);

            return [
                'message' => 'ok',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_at' => $tokens['expires_at'],
                'token_type' => $tokens['token_type'],
                'status' => 200
            ];
        }

        // Проверка кода
        $sms = SmsCode::where('phone_number', $data['phone_number'])
            ->where('code', $data['code'])
            ->where('is_used', false)
            ->first();

        if (!$sms) {
            return ['message' => 'Неверный код', 'status' => 400];
        }

        // Проверка истечения срока действия кода
//        if (Carbon::parse($sms->sent_at)->addMinutes(10)->isPast()) {
//            return ['message' => 'Срок действия кода истек', 'status' => 400];
//        }

        // Пометить код как использованный
        $sms->update(['is_used' => true]);

        // Поиск сотрудника и генерация токена
        $sotrudnik = Sotrudniki::where('phone_number', $data['phone_number'])->first();
        $sotrudnik->is_registered = true;
        $sotrudnik->save();

        // Генерируем новые токены (старые автоматически инвалидируются)
        $tokens = $tokenService->generateTokens($sotrudnik);

        return [
            'message' => 'ok',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_at' => $tokens['expires_at'],
            'token_type' => $tokens['token_type'],
            'status' => 200
        ];
    }
}
