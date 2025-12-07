<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\updateFcmTokenRequest;
use App\Http\Requests\UpdatePhotoProfileRequest;
use App\Http\Requests\VerifySmsRequest;
use App\Jobs\ManageTopicSubscriptionsJob;
use App\Jobs\SendPushNotification;
use App\Models\mobile\SmsCode;
use App\Models\OrganizationStructure;
use App\Models\PushSotrudnikam;
use App\Models\Sotrudniki;
use App\Services\FirebaseNotificationService;
use App\Services\PushkitNotificationService;
use App\Services\SotrudnikiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Exception\Messaging\InvalidArgument;


class SotrudnikiController extends Controller
{
    protected $sotrudnikService;
    protected $firebaseService;

    public function __construct(SotrudnikiService $sotrudnikService,FirebaseNotificationService $firebaseService)
    {
        $this->sotrudnikService = $sotrudnikService;
        $this->firebaseService = $firebaseService;
    }

    /** Регистрация пользователя */
    public function register(RegisterRequest $request):JsonResponse
    {
        $result = $this->sotrudnikService->registerSotrudnik($request->validated());

        return response()->json(['message' => $result['message'], 'sms_code'=>$result['sms_code'], 'sms_responce' => $result['sms_responce'] ?? null], $result['status']);
    }

    /** Регистрация пользователя 2 этап*/
    public function verifySms(VerifySmsRequest $request)
    {
        $result = $this->sotrudnikService->verifySms($request->validated());

        return response()->json([
            'message' => $result['message'],
            'access_token' => $result['access_token'] ?? null,
            'refresh_token' => $result['refresh_token'] ?? null,
            'expires_at' => $result['expires_at'] ?? null,
            'token_type' => $result['token_type'] ?? 'Bearer',
        ], $result['status']);
    }

    /**
     * Обновить фото профиля сотрудника.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePhotoProfile(UpdatePhotoProfileRequest $request)
    {
        // Получение аутентифицированного пользователя
        $sotrudnik = auth()->user();

        // Обработка загрузки файла
        if ($request->hasFile('photo_profile')) {
            $file = $request->file('photo_profile');

            // Генерация уникального имени файла
            $fileName = $sotrudnik->id . '_' . time() . '.' . $file->getClientOriginalExtension();


            // Сохранение файла в хранилище (например, storage/app/public/profile_photos/)
            try {
                Storage::disk('public')->put('profile_photos/' . $fileName, file_get_contents($file));
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось сохранить фото профиля. '.$e->getMessage(),
                ], 500);
            }

            // Удаление старого фото, если оно существует
            if ($sotrudnik->photo_profile) {
                Storage::delete('public/' . $sotrudnik->photo_profile);
            }

            // Обновление пути к фото профиля в базе данных
            $sotrudnik->photo_profile = 'profile_photos/' . $fileName;
            $sotrudnik->save();

            // Формирование URL для доступа к фото
            $photoUrl = Storage::disk('public')->url($sotrudnik->photo_profile);

            return response()->json([
                'success' => true,
                'message' => 'Фото профиля успешно обновлено.',
                'data' => [
                    'photo_profile_url' => $photoUrl,
                ],
            ]);
        }

        // Если файл не найден в запросе
        return response()->json([
            'success' => false,
            'message' => 'Фото профиля не найдено в запросе.',
        ], 400);
    }

    /* Получение данных профиля */
    public function getSotrudnikDetails()
    {
        // Получение аутентифицированного сотрудника
        $sotrudnik = auth()->user();

        return response()->json([
            'id' => $sotrudnik->id,
            'full_name' => $sotrudnik->full_name,
            'iin' => $sotrudnik->iin,
            'tabel_nomer' => $sotrudnik->tabel_nomer,
            'phone_number' => $sotrudnik->phone_number,
            'organization' => $sotrudnik->organization->getFirstParent()->name_ru ?? 'Не указано',
            'child_organization' => $sotrudnik->organization->name_ru ?? 'Не указано',
            'position' => $sotrudnik->position->name_ru ?? 'Не указано',
            'photo_profile' => $sotrudnik->photo_profile ? Storage::disk('public')->url($sotrudnik->photo_profile) : null,
            'gender' => $sotrudnik->gender ?? null,
            'lang' => $sotrudnik->lang ?? null,
            'birthday_show' => (bool) ($sotrudnik->birthday_show ?? true)
        ]);
    }

    public function updateFcmToken(UpdateFcmTokenRequest $request)
    {
        $sotrudnik = auth()->user();

        if (!$sotrudnik instanceof Sotrudniki) {
            return response()->json(['error' => 'Пользователь не является сотрудником.'], 403);
        }

        $fcmToken = $request->input('fcm_token');
        $lang = $request->input('lang');
        $os = $request->input('os');

        try {
            // Обновляем токен в базе данных
            $this->firebaseService->updateFcmToken($sotrudnik, $fcmToken,$os);

            if ($os != Sotrudniki::OS['harmony']) {
                // Отправляем Job в очередь
                dispatch(new ManageTopicSubscriptionsJob($sotrudnik, $lang));
            }

            return response()->json(['message' => 'Токен обновлен. Подписки будут обновлены в фоне.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getSubscribedTopics(Request $request)
    {
        // Получаем аутентифицированного пользователя
        $sotrudnik = auth()->user();

        if (!$sotrudnik || !$sotrudnik->fcm_token) {
            return response()->json(['error' => 'FCM-токен не найден у сотрудника.'], 400);
        }

        $topics = $this->firebaseService->getSubscribedTopics($sotrudnik->fcm_token);

//        if (isset($topics['error'])) {
//            return response()->json(['error' => $topics['error']], 400);
//        }

        return response()->json($topics, 200);
    }

    public function send_test_push()
    {
        $push = PushSotrudnikam::findOrFail(2);
        $sendResults = $this->firebaseService->sendPushNotification($push);
        return response()->json($sendResults, 200);
    }

    public function updateLang(Request $request)
    {
        $validatedData = $request->validate([
            'lang' =>'required|string|in:ru,kz',
        ]);
        $sotrudnik = auth()->user();

        if (!$sotrudnik instanceof Sotrudniki) {
            return response()->json(['error' => 'Пользователь не является сотрудником.'], 403);
        }

        $lang = $validatedData['lang'];

        try {
            Sotrudniki::where('id', $sotrudnik->id)->update(['lang' => $lang]);
            return response()->json(['message' => 'Язык обновлен'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    public function updateGender(Request $request)
    {
        $validatedData = $request->validate([
            'gender' =>'required|string|in:man,woman',
        ]);
        $sotrudnik = auth()->user();

        if (!$sotrudnik instanceof Sotrudniki) {
            return response()->json(['error' => 'Пользователь не является сотрудником.'], 403);
        }

        $gender = $validatedData['gender'];

        try {
            Sotrudniki::where('id', $sotrudnik->id)->update(['gender' => $gender]);
            return response()->json(['message' => 'Пол обновлен'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    public function updateBirthdayShow(Request $request)
    {
        $validatedData = $request->validate([
            'birthday_show' =>'required|boolean',
        ]);
        $sotrudnik = auth()->user();

        if (!$sotrudnik instanceof Sotrudniki) {
            return response()->json(['error' => 'Пользователь не является сотрудником.'], 403);
        }

        $data = $validatedData['birthday_show'];

        try {
            Sotrudniki::where('id', $sotrudnik->id)->update(['birthday_show' => $data]);
            return response()->json(['message' => 'Значение обновлено'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function birthdaysList(Request $request)
    {
        $lang = $request->input('lang', 'ru');
        $langColumn = 'name_' . $lang;
        $lang == 'ru' ?
            $text = 'Уважаемые коллеги !
Поздравляем всех именинников с Днём рождения! Желаем крепкого здоровья, успехов в работе, благополучия и исполнения всех желаний. Пусть каждый день приносит новые возможности и радость!
С наилучшими пожеланиями, АО ӨзенМұнайГаз'
            : $text = 'Құрметті әріптестер!
Туған күнін тойлап жатқандардың барлығын туған күнімен құттықтаймын! Сізге зор денсаулық, жұмысыңызға табыс, амандық пен барлық тілектеріңіздің орындалуын тілейміз. Әр күн жаңа мүмкіндіктер мен қуаныш әкелсін!
Ізгі ниетпен, «ӨзенМұнайГаз» АҚ';
        try {
            $today = now();

            $whoBirthday = Sotrudniki::with('organization')
                ->where('birthday_show', 1)
                ->whereMonth('birthdate', $today->month)
                ->whereDay('birthdate', $today->day)
                ->select('id', 'organization_id', 'full_name', 'birthdate', 'photo_profile')
                ->orderBy('birthdate', 'ASC')
                ->get();

            $responseBirthdays = [];
            foreach ($whoBirthday as $index => $birthday) {
                $responseBirthdays[$index]['id'] = $birthday['id'];
                $responseBirthdays[$index]['full_name'] = $birthday['full_name'];
                $responseBirthdays[$index]['age'] = Carbon::parse($birthday->birthdate)->age;
                $organization = (new \App\Models\OrganizationStructure)->getFirstParentById($birthday['organization']['id']);
                $responseBirthdays[$index]['organization_name'] = $organization[$langColumn];
                $responseBirthdays[$index]['photo_profile'] = $birthday->photo_profile
                    ? Storage::disk('public')->url($birthday->photo_profile)
                    : null;
            }
            $response = [
                'text' => $text,
                'birthdays' => $responseBirthdays,
            ];
            return response()->json(['message' => 'Есть список', 'data' => $response], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function birthdaysSendText(Request $request)
    {
        $validatedData = $request->validate([
            'id' =>'required|integer',
            'text' =>'required|string|max:255',
        ]);
        try {
            $recipient = Sotrudniki::find($validatedData['id']);
            if (!$recipient) {
                return response()->json(['error' => 'Получатель не найден.'], 404);
            }

            $sotrudnik = auth()->user();

            $notification = new PushSotrudnikam();
            $notification->title = $sotrudnik->full_name;
            $notification->mini_description = $validatedData['text'];
            $notification->sender_id = $sotrudnik->id;
            $notification->sended = 1;
            $notification->for_all = 0;
            $notification->expiry_date = Carbon::now()->addDays(7);
            $notification->recipient_id = $recipient->id;
            $notification->body = "";
            $notification->lang = $recipient->lang;
            $notification->save();

            $message_data = [
                'title' => $notification->title,
                'body' => $notification->mini_description,
                'image' => null,
                'data' => [
//                    'page' => '/education',
//                    'page' => '/ideas',
//                    'page' => '/news',
                    'page' => '/message',
//                    'page' => '/payslip',
                    'id' => $notification->id,
                ],
            ];

            // Логируем отправленное сообщение
            Log::channel('push')->info('Push notification sent CONGREGATION', [
                'recipient_id' => $recipient->id,
                'recipient_name' => $recipient->full_name,
                'message' => $message_data,
                'timestamp' => now()->toDateTimeString(),
            ]);

            if($recipient->os === Sotrudniki::OS['harmony']) {
                $tokens[0] = $recipient->fcm_token;
                $service = new PushkitNotificationService();
                $service->dispatchPushNotification($message_data,$tokens);
            } else {
                SendPushNotification::dispatch($recipient->id, $message_data);
            }

            return response()->json(['message' => 'Принято'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Обновить токены используя refresh token
     */
    public function refreshToken(Request $request)
    {
        $validatedData = $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $tokenService = new \App\Services\TokenService();
        $tokens = $tokenService->refreshTokens($validatedData['refresh_token']);

        if (!$tokens) {
            return response()->json([
                'message' => 'Недействительный refresh токен'
            ], 401);
        }

        return response()->json([
            'message' => 'Токены успешно обновлены',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_at' => $tokens['expires_at'],
            'token_type' => $tokens['token_type'],
        ], 200);
    }

    /**
     * Выход из аккаунта (удаление токенов)
     */
    public function logout()
    {
        $sotrudnik = auth()->user();

        if (!$sotrudnik instanceof Sotrudniki) {
            return response()->json(['error' => 'Пользователь не аутентифицирован.'], 401);
        }

        $tokenService = new \App\Services\TokenService();
        $tokenService->revokeTokens($sotrudnik);

        return response()->json(['message' => 'Успешный выход из аккаунта'], 200);
    }
}
