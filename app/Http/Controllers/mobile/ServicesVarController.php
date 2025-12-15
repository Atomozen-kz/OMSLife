<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServicesVarApiRequest;
use App\Services\ServicesVarApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicesVarController extends Controller
{
    protected ServicesVarApiService $servicesVarApiService;

    public function __construct(ServicesVarApiService $servicesVarApiService)
    {
        $this->servicesVarApiService = $servicesVarApiService;
    }

    /**
     * Возвращает данные services_vars в зависимости от языка.
     *
     * @param ServicesVarApiRequest $request
     * @return JsonResponse
     */
    public function index(ServicesVarApiRequest $request): JsonResponse
    {
        try {
            $lang = $request->input('lang');

            if (!in_array($lang, ['ru', 'kz'])) {
             $lang = 'ru';
            }

            $data = $this->servicesVarApiService->getServicesVarsByLang($lang);

            return response()->json(array('data' => $data, 'success'=>true), 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            // Логирование ошибки может быть добавлено здесь
            return response()->json([
                'error' => 'Произошла ошибка при обработке запроса.',
            ], 500);
        }
    }
}
