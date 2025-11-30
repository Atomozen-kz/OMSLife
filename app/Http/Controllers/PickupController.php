<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PickupController extends Controller
{
    public function showLoginForm()
    {
        return view('pickup.login'); // вернёт blade с формой
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $pickup = PickupPoint::where('username', $request->username)->first();

        if ($pickup && $pickup->password == $request->password) {
            Auth::guard('pickup')->login($pickup);
            return redirect()->route('pickup.dashboard');
        }

        return back()->withErrors([
            'username' => 'Неверный логин или пароль',
        ]);
    }


    public function logout()
    {
        Auth::guard('pickup')->logout();
        return redirect()->route('pickup.loginForm');
    }

    public function dashboard()
    {
        // Здесь можно проверить, что у нас guard('pickup') залогинен
        // И показать форму toggle is_open
        $pickup = Auth::guard('pickup')->user();
        return view('pickup.dashboard', compact('pickup'));
    }

    public function updateIsOpen(Request $request)
    {
        // Для примера, вы можете использовать 'boolean' или просто 0/1
        $pickup = Auth::guard('pickup')->user();
        if (!$pickup) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $pickup->is_open = (bool)$request->input('is_open');
        $pickup->save();

        return response()->json([
            'success' => true,
            'message' => 'Статус обновлён',
        ]);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0|max:5',
        ]);

        // Получаем пользователя через guard('pickup')
        $pickup = Auth::guard('pickup')->user();

        // Обновляем статус
        $pickup->quantity = $request->quantity;
        $pickup->save();

        return response()->json(['success' => true]);
    }


}
