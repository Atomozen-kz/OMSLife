<?php

namespace App\Http\Controllers;

use App\Models\PartnerPlace;
use App\Models\PartnerPlaceVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PartnerPlaceDashboardController extends Controller
{
    /**
     * Показать форму входа
     */
    public function showLoginForm()
    {
        if (Auth::guard('partner_place')->check()) {
            return redirect()->route('partner-place.dashboard');
        }

        return view('partner-place.login');
    }

    /**
     * Авторизация партнёра
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $partnerPlace = PartnerPlace::where('username', $request->username)->first();

        if (!$partnerPlace || !Hash::check($request->password, $partnerPlace->password)) {
            return back()->withErrors(['username' => 'Неверный логин или пароль']);
        }

        if (!$partnerPlace->status) {
            return back()->withErrors(['username' => 'Ваш аккаунт неактивен']);
        }

        Auth::guard('partner_place')->login($partnerPlace);

        return redirect()->route('partner-place.dashboard');
    }

    /**
     * Выход из системы
     */
    public function logout()
    {
        Auth::guard('partner_place')->logout();
        return redirect()->route('partner-place.loginForm');
    }

    /**
     * Дашборд партнёра
     */
    public function dashboard(Request $request)
    {
        $partnerPlace = Auth::guard('partner_place')->user();

        // Фильтр по дате
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Статистика
        $visitsToday = PartnerPlaceVisit::where('partner_place_id', $partnerPlace->id)
            ->whereDate('visited_at', today())
            ->count();

        $visitsThisWeek = PartnerPlaceVisit::where('partner_place_id', $partnerPlace->id)
            ->whereBetween('visited_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $visitsThisMonth = PartnerPlaceVisit::where('partner_place_id', $partnerPlace->id)
            ->whereBetween('visited_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $totalVisits = PartnerPlaceVisit::where('partner_place_id', $partnerPlace->id)->count();

        // Список посетителей с фильтрацией по дате
        $visits = PartnerPlaceVisit::with('sotrudnik')
            ->where('partner_place_id', $partnerPlace->id)
            ->whereDate('visited_at', '>=', $dateFrom)
            ->whereDate('visited_at', '<=', $dateTo)
            ->orderBy('visited_at', 'desc')
            ->paginate(20)
            ->appends([
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]);

        return view('partner-place.dashboard', compact(
            'partnerPlace',
            'visitsToday',
            'visitsThisWeek',
            'visitsThisMonth',
            'totalVisits',
            'visits',
            'dateFrom',
            'dateTo'
        ));
    }
}

