<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailableSurveysRequest;
use App\Http\Requests\AllSurveysRequest;
use App\Http\Requests\SurveyDetailRequest;
use App\Models\Sotrudniki;
use App\Services\SurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    protected $surveyService;

    public function __construct(SurveyService $surveyService)
    {
        $this->surveyService = $surveyService;
    }

    /**
     * 1. Получить доступные опросы для пользователя.
     *
     * @param AvailableSurveysRequest $request
     * @return JsonResponse
     */
    public function getAvailableSurveys(AvailableSurveysRequest $request): JsonResponse
    {
        $user = auth()->user(); // Предполагается, что пользователь связан с моделью Sotrudniki
        $lang = $request->input('lang');
        if (!in_array($lang, ['ru', 'kz'])) {
            $lang = 'ru';
        }

        $availableSurveys = $this->surveyService->getAvailableSurveys($user, $lang);

        return response()->json([
            'success' => true,
            'data' => $availableSurveys,
        ]);
    }

    /**
     * 2. Получить список всех опросов пользователя.
     *
     * @param AllSurveysRequest $request
     * @return JsonResponse
     */
    public function getAllSurveys(AllSurveysRequest $request): JsonResponse
    {
        $user = auth()->user();
        $lang = $request->input('lang');
        if (!in_array($lang, ['ru', 'kz'])) {
            $lang = 'ru';
        }
        $allSurveys = $this->surveyService->getAllSurveys($user, $lang);

        return response()->json([
            'success' => true,
            'data' => $allSurveys,
        ]);
    }

    /**
     * 3. Получить детали конкретного опроса.
     *
     * @param SurveyDetailRequest $request
     * @return JsonResponse
     */
    public function getSurveyDetail(SurveyDetailRequest $request): JsonResponse
    {
        $id = $request->input('id');

        $survey = $this->surveyService->getSurveyDetail($id);

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => 'Опрос не найден.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $survey,
        ]);
    }

    /**
     * 4. Проверить прошёл ли пользователь опрос с указанным id. Если не прошёл — вернуть опрос.
     *
     * @param SurveyDetailRequest $request
     * @return JsonResponse
     */
    public function checkSurvey(Request $request): JsonResponse
    {
        $user = auth()->user();
        $lang = $request->input('lang');

        if ($lang == 'kz') {
            $id = 2;
        } else {
            $id = 1;
        }
//        $id = $request->input('id');

        $result = $this->surveyService->getSurveyIfNotTaken($user, $id);

        if (is_null($result)) {
            return response()->json([
                'success' => false,
                'message' => 'Опрос не найден.',
            ], 404);
        }

        if ($result['taken']) {
            return response()->json([
                'success' => true,
                'taken' => true,
                'message' => 'Опрос уже пройден.',
            ]);
        }

        return response()->json([
            'success' => true,
            'taken' => false,
            'data' => $result['survey'],
        ]);
    }
}
