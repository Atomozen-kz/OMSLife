<?php

namespace App\Http\Middleware;

use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithCustomToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Токен не предоставлен'
            ], 401);
        }

        $tokenService = new TokenService();
        $sotrudnik = $tokenService->validateAccessToken($token);

        if (!$sotrudnik) {
            return response()->json([
                'message' => 'Недействительный или истекший токен'
            ], 401);
        }

        // Устанавливаем аутентифицированного пользователя
        auth()->setUser($sotrudnik);

        return $next($request);
    }
}
