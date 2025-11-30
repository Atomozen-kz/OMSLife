<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Auth\AuthenticationException;

class Authenticate extends Middleware
{
    /**
     * Handle unauthenticated user.
     */
    protected function unauthenticated($request, array $guards)
    {
        if ($request->expectsJson()) {
            throw new AuthenticationException(
                'Unauthenticated.', $guards, null
            );
        }

        if (in_array('pickup', $guards)) {
            redirect()->guest(route('pickup.loginForm'))->send();
            exit;
        }

        abort(401, 'Неавторизованный доступ. Пожалуйста, войдите в систему.');
    }
}
