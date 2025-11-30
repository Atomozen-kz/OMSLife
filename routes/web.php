<?php

use App\Exports\PositionsExport;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\PickupController;
use App\Orchid\Screens\TrainingCenterScreen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/privacy-policy', function () {
    return view('privacy');
})->name('privacy.policy');

Route::get('/export-positions', function () {
    return Excel::download(new PositionsExport, 'positions.xlsx');
});

Route::post('/preview/update', function (Request $request) {
    $textKz = $request->input('text_kz');
    $textRu = $request->input('text_ru');
    $signer = $request->input('signer');
    $sotrudnik = $request->input('sotrudnik');

    // Генерация HTML предпросмотра
    return view('pdf.spravka_html_pdf', [
        'text_kz' => $textKz,
        'text_ru' => $textRu,
        'signer' => $signer,
        'sotrudnik' => $sotrudnik, // Заполняется при сохранении справки
    ])->render();
})->name('preview.update');

Route::get('/spravka_proverka/{id}', [DocumentVerificationController::class, 'verify'])->name('document.verify');

Route::prefix('pickup')->group(function () {
    Route::get('/login', [PickupController::class, 'showLoginForm'])->name('pickup.loginForm');
    Route::post('/login', [PickupController::class, 'login'])->name('pickup.login');

    // Группа, защищённая авторизацией pickup
    Route::middleware('auth:pickup')->group(function () {
        Route::get('/dashboard', [PickupController::class, 'dashboard'])->name('pickup.dashboard');
        Route::post('/update-is-open', [PickupController::class, 'updateIsOpen'])->name('pickup.updateIsOpen');
        Route::post('/logout', [PickupController::class, 'logout'])->name('pickup.logout');
        Route::post('/pickup/update-status', [PickupController::class, 'updateStatus'])->name('pickup.updateStatus');
        Route::get('/', [PickupController::class, 'dashboard']);
    });
});

Route::prefix('admin')->group(function (){
    Route::get('/export-training-records', [TrainingCenterScreen::class, 'exportExcel'])
        ->name('training.export');
});
