<?php

namespace App\Auth;

use App\Models\Sotrudniki;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class CustomTokenGuard implements Guard
{
    use GuardHelpers;

    protected $request;
    protected $provider;

    public function __construct($provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $token = $this->request->bearerToken();

        if (!$token) {
            return null;
        }

        $this->user = Sotrudniki::where('access_token', $token)->first();

        return $this->user;
    }

    public function validate(array $credentials = [])
    {
        return !is_null($this->user());
    }
}
