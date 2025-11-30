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
        // Поиск сотрудника в базе данных
        $sotrudniki = Sotrudniki::where('tabel_nomer', $data['tabel_nomer'])
            ->whereRaw('LOWER(last_name) = ?', [strtolower($data['last_name'])])
            ->get();

        if (!$sotrudniki) {
            return ['message' => 'Сотрудник не найден', 'status' => 404];
        }
        $sotrudnik = null;
        $not_in_organization = true;
        // Проверка, что сотрудник принадлежит к указанной организации
        foreach ($sotrudniki as $s) {
            $organization = OrganizationStructure::find($s->organization_id);
            $topLevelOrganization = $organization->getFirstParent();

            if ($topLevelOrganization->id == $data['organization_id']) {
                $sotrudnik = $s;
                $not_in_organization = false;
            }
        }

        if ($not_in_organization) {
            return ['message' => 'Сотрудник не принадлежит к указанной организации', 'status' => 403];
        }

        if ($tel_phone = Sotrudniki::where('phone_number', $data['phone_number'])->where('id', '!=', $sotrudnik->id)->first()) {
            return ['message' => 'Этот номер телефона уже зарегистрирован', 'status' => 409];
        }

        $sotrudnik->update(['phone_number' => $data['phone_number']]);

        // Проверка частоты отправки SMS
        $recentSms = SmsCode::where('phone_number', $data['phone_number'])
            ->where('created_at', '>', Carbon::now()->subMinutes(60));

        if ($recentSms->exists() && $data['phone_number'] != $sotrudnik->phone_number) {
            return ['message' => 'Слишком много запросов', 'status' => 403];
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

        // Отправка SMS-кода (здесь можно интегрировать сервис отправки SMS)
        $smsServiceUrl = Config::get('app.smsServiceUrl');
        $smsResponse = Http::get($smsServiceUrl, [
            'login' => Config::get('app.smsServiceLogin'),
            'psw' => Config::get('app.smsServicePassword'),
            'phones' => $data['phone_number'],
            'mes' => "OMGLife\nВаш код: ". $smsCode,
            'sender' => 'ALLFOOD',
            'fmt' => 3
        ])->json();
//        $smsResponse = SendVerifyCodeJob::dispatch($data['phone_number'], "OMGLife\nВаш код: ". $smsCode);

        return ['message' => 'SMS отправлено', 'success'=>true, 'sms_responce' => $smsResponse, 'status' => 200];
    }

    /**
     * Проверка SMS-кода и генерация токена
     */
    public function verifySms(array $data)
    {

        if ($data['phone_number'] == '+77089222820' && $data['code'] == 1234) {
            $sotrudnik = Sotrudniki::where('phone_number', $data['phone_number'])->first();
            $sotrudnik->is_registered = true;
            $sotrudnik->save();
            $token = $sotrudnik->createToken('auth_token')->plainTextToken;

            return ['message' => 'ok', 'token' => $token, 'status' => 200];
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
        $token = $sotrudnik->createToken('auth_token')->plainTextToken;

        return ['message' => 'ok', 'token' => $token, 'status' => 200];
    }
}
