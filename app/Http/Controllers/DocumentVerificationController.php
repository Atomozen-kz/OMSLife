<?php
namespace App\Http\Controllers;

use App\Models\SpravkaSotrudnikam;
use Illuminate\Http\Request;

class DocumentVerificationController extends Controller
{
    /**
     * Отображение страницы проверки документа.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function verify($id)
    {
        // Найти справку по ID
        $spravka = SpravkaSotrudnikam::find($id);

        if (!$spravka) {
            return view('verification.failed', ['message' => 'Документ не найден.']);
        }

        // Возвращаем страницу успешной проверки
        return view('verification.success', ['spravka' => $spravka]);
    }
}
