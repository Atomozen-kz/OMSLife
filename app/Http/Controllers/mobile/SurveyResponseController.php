<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitSurveyResponseRequest;
use App\Models\Sotrudniki;
use App\Models\Survey;
use App\Services\SurveyService;
use Illuminate\Http\JsonResponse;

class SurveyResponseController extends Controller
{
    protected $surveyService;

    public function __construct(SurveyService $surveyService)
    {
        $this->surveyService = $surveyService;
    }

    /**
     * 4. Отправить ответы пользователя на опрос.
     *
     * @param SubmitSurveyResponseRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function submitResponse(SubmitSurveyResponseRequest $request, int $id): JsonResponse
    {
        $user = auth()->user();
//        $lang = $request->input('lang'); // Язык опроса, полученный от приложения
        $survey = Survey::find($id);
        // Проверка, что опрос доступен для пользователя
        $availableSurveys = $this->surveyService->getAvailableSurveys($user, $survey->lang);
        if (!$availableSurveys->contains('id', $id)) {
            return response()->json([
                'success' => false,
                'message' => 'Опрос недоступен для прохождения или уже пройден.',
            ], 403);
        }



        // Сохранение ответов
        $responses = $request->input('responses', []);
        $textResponses = $request->input('text_responses', []);

        $success = $this->surveyService->saveSurveyResponse($user, $id, $responses, $textResponses, $survey->lang);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Ваши ответы успешно сохранены.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при сохранении ответов.',
            ], 500);
        }
    }
}
