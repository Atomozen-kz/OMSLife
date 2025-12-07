<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentSignController;
use App\Http\Controllers\mobile\ApiPayrollSlipController;
use App\Http\Controllers\mobile\AppealController;
use App\Http\Controllers\mobile\BankIdeaController;
use App\Http\Controllers\mobile\BankIdeaV2Controller;
use App\Http\Controllers\mobile\ChatController;
use App\Http\Controllers\mobile\ExtractionApiController;
use App\Http\Controllers\mobile\FaqController;
use App\Http\Controllers\mobile\GlobalPageController;
use App\Http\Controllers\mobile\LoyaltyCardController;
use App\Http\Controllers\mobile\NewsApiController;
use App\Http\Controllers\mobile\PickupPointController;
use App\Http\Controllers\mobile\PromzonaController;
use App\Http\Controllers\mobile\PromzonaGeoObjectController;
use App\Http\Controllers\mobile\ServicesVarController;
use App\Http\Controllers\mobile\SotrudnikiController;
use App\Http\Controllers\mobile\SpravkaSotrudnikamController;
use App\Http\Controllers\mobile\StoriesController;
use App\Http\Controllers\mobile\StructureController;
use App\Http\Controllers\mobile\PushSotrudnikamController;
use App\Http\Controllers\mobile\SurveyController;
use App\Http\Controllers\mobile\SurveyResponseController;
use App\Http\Controllers\mobile\TrainingRecordController;
use App\Http\Controllers\mobile\FinancialAssistanceApiController;
use App\Http\Controllers\PayrollSlipController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/getFirstParentStructure', [StructureController::class, 'getFirstParentStructure']);

Route::post('/register', [SotrudnikiController::class, 'register']);
Route::post('/verify-sms', [SotrudnikiController::class, 'verifySms']);
Route::post('/refresh-token', [SotrudnikiController::class, 'refreshToken']);

Route::post('/getMainNews', [NewsApiController::class, 'getMainNews']);
Route::post('/getAllNews', [NewsApiController::class, 'getAllNews']);

Route::post('/getOneNewsPublic', [NewsApiController::class, 'getOneNewsPublic']);

Route::middleware('auth:custom')->group(function () {
    Route::post('/services-vars', [ServicesVarController::class, 'index']);

    Route::post('/updatePhotoProfile', [SotrudnikiController::class, 'updatePhotoProfile']);
    Route::get('/getSotrudnikDetails', [SotrudnikiController::class, 'getSotrudnikDetails']);
    Route::post('/updateFcmToken', [SotrudnikiController::class, 'updateFcmToken']);
    Route::post('/updateLang', [SotrudnikiController::class, 'updateLang']);
    Route::post('/updateGender', [SotrudnikiController::class, 'updateGender']);
    Route::post('/updateBirthdayShow', [SotrudnikiController::class, 'updateBirthdayShow']);
    Route::post('/getSubscribedTopics', [SotrudnikiController::class, 'getSubscribedTopics']);

    Route::post('/birthdays', [SotrudnikiController::class, 'birthdaysList']);
    Route::post('/birthdaysSendText', [SotrudnikiController::class, 'birthdaysSendText']);

    Route::post('/logout', [SotrudnikiController::class, 'logout']);

//    Route::post('/getMainNews', [NewsApiController::class, 'getMainNews']);
//    Route::post('/getAllNews', [NewsApiController::class, 'getAllNews']);
    Route::post('/getNewsWithComments', [NewsApiController::class, 'getNewsWithComments']);
    Route::post('/addCommentNews', [NewsApiController::class, 'addComment']);
    Route::post('/deleteCommentNews', [NewsApiController::class, 'deleteCommentNews']);
    Route::post('/news/like', [NewsApiController::class, 'toggleLike']);


    Route::post('/getCategoryStories', [StoriesController::class, 'getCategoryStories']);
    Route::post('/getStories', [StoriesController::class, 'getStories']);

    Route::post('/getNewNotificationsCount', [PushSotrudnikamController::class, 'getNewNotificationsCount']);
    Route::post('/getAllPushNotifications', [PushSotrudnikamController::class, 'getAllPushNotifications']);
    Route::post('/getPushNotification', [PushSotrudnikamController::class, 'getPushNotification']);

    Route::post('/getPickupPointsMilk', [PickupPointController::class, 'getPickupPointsMilk']);

    Route::prefix('surveys')->group(function () {
        // 1. Получить доступные опросы для пользователя
        Route::post('/available', [SurveyController::class, 'getAvailableSurveys']);

        // 2. Получить список всех опросов пользователя
        Route::post('/all', [SurveyController::class, 'getAllSurveys']);

        // 3. Получить детали конкретного опроса
        Route::post('/getSurveyDetail', [SurveyController::class, 'getSurveyDetail']);

        // 4. Отправить ответы на опрос
        Route::post('/responses/{id}', [SurveyResponseController::class, 'submitResponse']);

        Route::post('/check_survey', [SurveyController::class, 'checkSurvey']);
    });

    Route::post('/spravka-sotrudnikam/request', [SpravkaSotrudnikamController::class, 'requestCertificate']);
    Route::post('/spravka-sotrudnikam/active', [SpravkaSotrudnikamController::class, 'getActiveCertificates']);

    // Получить список расчетных листов для сотрудника
    Route::post('/payroll-slips/list', [PayrollSlipController::class, 'list']);
    // Получить детали конкретного расчетного листа
    Route::post('/payroll-slips/detail', [PayrollSlipController::class, 'detail']);
    Route::post('/payroll-slips/download', [PayrollSlipController::class, 'download']);
    Route::post('/payroll-slips/download-jpg', [PayrollSlipController::class, 'downloadJpg']);

    Route::prefix('/loyalty-cards')->group(function () {
        Route::post('/categories', [LoyaltyCardController::class, 'categories']);
        Route::post('/index', [LoyaltyCardController::class, 'indexWithCategory']);
        Route::post('/', [LoyaltyCardController::class, 'index']);
    });


    Route::prefix('/promzona')->group(function () {
        Route::post('objects', [PromzonaController::class, 'getObjects']);
        Route::post('filters', [PromzonaController::class, 'getFilters']);
        Route::post('search', [PromzonaController::class, 'searchObjects']);
        Route::post('add', [PromzonaController::class, 'addObject']);
    });

    Route::post('/training-records', [TrainingRecordController::class, 'getTrainingRecords']);

    Route::post('/extraction/getMainScreenData', [ExtractionApiController::class, 'getMainScreenData']);
    Route::post('/extraction/getMonthData', [ExtractionApiController::class, 'getMonthData']);

    Route::post('/promzona/searchByName', [PromzonaGeoObjectController::class, 'searchByName']);

    Route::post('/faqs', [FaqController::class, 'getFaqs']);

    Route::prefix('/appeal')->group(function () {
        Route::post('createAppeal', [AppealController::class, 'createAppeal']);
        Route::post('myAppeals', [AppealController::class, 'myAppeals']);
        Route::post('getAppealTopics', [AppealController::class, 'getAppealTopics']);
        Route::post('getDetails/{appealId}', [AppealController::class, 'getAppealDetails']);
        Route::post('getStatusHistory/{appealId}', [AppealController::class, 'getAppealStatusHistory']);
        Route::post('getAnswers/{appealId}', [AppealController::class, 'getAppealAnswers']);
    });

    Route::post('/payroll-slip/enable', [ApiPayrollSlipController::class, 'enablePayrollSlip']);
    Route::post('/payroll-slip/disable', [ApiPayrollSlipController::class, 'disablePayrollSlip']);


    Route::post('/bank-ideas/all', [BankIdeaController::class, 'all']);
    Route::post('/bank-ideas/my-ideas', [BankIdeaController::class, 'myIdeas']);
    Route::post('/bank-ideas/one', [BankIdeaController::class, 'one']);
    Route::post('/bank-ideas/store', [BankIdeaController::class, 'store']);
    Route::post('/bank-ideas/update-idea/{id}', [BankIdeaController::class, 'updateIdea']);
    Route::post('/bank-ideas/delete-idea/{id}', [BankIdeaController::class, 'deleteIdea']);
    Route::post('/bank-ideas/{id}/vote', [BankIdeaController::class, 'vote']);
    Route::post('/bank-ideas/{id}/comment', [BankIdeaController::class, 'comment']);
    Route::post('/bank-ideas/delete_comment/{id}', [BankIdeaController::class, 'deleteComment']);

    // V2 API (backwards compatible) — добавляем только новый метод getTypesForBankIdeas, остальные методы переадресованы на v1
    Route::prefix('v2')->group(function () {
        Route::post('/bank-ideas/all', [BankIdeaV2Controller::class, 'all']);
        Route::post('/bank-ideas/my-ideas', [BankIdeaV2Controller::class, 'myIdeas']);
        Route::post('/bank-ideas/one', [BankIdeaV2Controller::class, 'one']);
        Route::post('/bank-ideas/store', [BankIdeaV2Controller::class, 'store']);
        Route::post('/bank-ideas/update-idea/{id}', [BankIdeaV2Controller::class, 'updateIdea']);
        Route::post('/bank-ideas/delete-idea/{id}', [BankIdeaV2Controller::class, 'deleteIdea']);
        Route::post('/bank-ideas/{id}/vote', [BankIdeaV2Controller::class, 'vote']);
        Route::post('/bank-ideas/{id}/comment', [BankIdeaV2Controller::class, 'comment']);
        Route::post('/bank-ideas/delete_comment/{id}', [BankIdeaV2Controller::class, 'deleteComment']);

        // Новый метод, возвращающий список типов идей (активные и неактивные)
        Route::get('/bank-ideas/types', [BankIdeaV2Controller::class, 'getTypesForBankIdeas']);
    });


    Route::post('/globalPages', [GlobalPageController::class, 'index']);
    Route::post('/globalPages/{id}', [GlobalPageController::class, 'show']);

    // API для материальной помощи
    Route::prefix('financial-assistance')->group(function () {
        Route::get('/types', [FinancialAssistanceApiController::class, 'getTypes']);
        Route::get('/types/{typeId}', [FinancialAssistanceApiController::class, 'getTypeDetails']);
        Route::post('/requests', [FinancialAssistanceApiController::class, 'submitRequest']);
        Route::get('/requests', [FinancialAssistanceApiController::class, 'getUserRequests']);
        Route::get('/requests/{requestId}', [FinancialAssistanceApiController::class, 'getRequestDetails']);
        Route::get('/requests/{requestId}/pdf', [FinancialAssistanceApiController::class, 'downloadRequestPdf']);
        Route::get('/requests/{requestId}/saved-pdf', [FinancialAssistanceApiController::class, 'getSavedRequestPdf']);
    });

    Route::prefix('chat')->group(function () {
        Route::post('send', [ChatController::class, 'sendMessage']);

        Route::post('history', [ChatController::class, 'getChatHistory']);

        Route::post('archive/{sotrudnikId}', [ChatController::class, 'archiveChat']);
    });

    // END AUTH GROUPS
});

//Chat cors for n8n
Route::prefix('chat')->group(function () {
    Route::get('audio/{messageId}', [ChatController::class, 'getAudio'])->name('api.chat.audio');
    Route::post('receive', [ChatController::class, 'receiveMessage']);
});
// ALL
//Route::middleware('platform.auth')->group(function () {
    Route::get('/sign/get-hash/{documentId}', [DocumentSignController::class, 'getHash']);
    Route::post('/sign/register-signature', [DocumentSignController::class, 'registerSignature']);
    Route::post('/sign/register-document', [DocumentSignController::class, 'registerDocument']);
    Route::post('/sign/build-document-card', [DocumentSignController::class, 'buildDocumentCard']);
//});
Route::post('/payroll-slips-upload', [PayrollSlipController::class, 'upload'])->middleware('payroll.auth');
Route::post('/test-payroll-slips-upload', [PayrollSlipController::class, 'test_upload'])->middleware('payroll.auth');

Route::post('/send_test_push', [SotrudnikiController::class, 'send_test_push']);


Route::get('/adapicCmsGetSgnerInfo/{spravka_id}', [DocumentController::class, 'adapicCmsGetSgnerInfo']);
Route::get('/modifyPdf', [DocumentController::class, 'modifyPdf']);
