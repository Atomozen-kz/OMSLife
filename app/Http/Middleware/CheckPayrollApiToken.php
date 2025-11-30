<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class CheckPayrollApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $staticToken = Config::get('app.payrollApiToken');
        $token = $request->header('Authorization');

        if (!$token || $token !== 'Bearer ' . $staticToken) {
            return response()->json([
                'success' => false,
                'message' => 'Неавторизованный доступ.',
            ], 401);
        }

        return $next($request);
    }
}
