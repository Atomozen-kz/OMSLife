<?php

namespace App\Services;

use App\Models\Sotrudniki;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TokenService
{
    /**
     * Время жизни access токена в минутах
     */
    const ACCESS_TOKEN_LIFETIME = 60 * 24 * 30; // 30 дней

    /**
     * Время жизни refresh токена в минутах
     */
    const REFRESH_TOKEN_LIFETIME = 60 * 24 * 90; // 90 дней

    /**
     * Генерирует новую пару токенов для сотрудника
     *
     * @param Sotrudniki $sotrudnik
     * @return array
     */
    public function generateTokens(Sotrudniki $sotrudnik): array
    {
        // Проверяем, является ли аккаунт тестовым
        $testPhones = ['+77089222820', '+77081139347'];
        if (in_array($sotrudnik->phone_number, $testPhones)) {
            return $this->generateStaticTokens($sotrudnik);
        }

        $accessToken = $this->generateToken();
        $refreshToken = $this->generateToken();
        $expiresAt = Carbon::now()->addMinutes(self::ACCESS_TOKEN_LIFETIME);

        // Сохраняем токены в базу данных
        $sotrudnik->update([
            'access_token' => hash('sha256', $accessToken),
            'refresh_token' => hash('sha256', $refreshToken),
            'token_expires_at' => $expiresAt,
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt->toDateTimeString(),
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Генерирует статичные токены для тестовых аккаунтов
     *
     * @param Sotrudniki $sotrudnik
     * @return array
     */
    public function generateStaticTokens(Sotrudniki $sotrudnik): array
    {
        // Генерируем статичные токены на основе номера телефона
        $accessToken = 'test_access_' . md5($sotrudnik->phone_number . '_access');
        $refreshToken = 'test_refresh_' . md5($sotrudnik->phone_number . '_refresh');
        $expiresAt = Carbon::now()->addYears(10); // Долгий срок для тестовых аккаунтов

        // Сохраняем токены в базу данных
        $sotrudnik->update([
            'access_token' => hash('sha256', $accessToken),
            'refresh_token' => hash('sha256', $refreshToken),
            'token_expires_at' => $expiresAt,
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt->toDateTimeString(),
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Генерирует случайный токен
     *
     * @return string
     */
    private function generateToken(): string
    {
        return Str::random(80);
    }

    /**
     * Проверяет валидность access токена
     *
     * @param string $token
     * @return Sotrudniki|null
     */
    public function validateAccessToken(string $token): ?Sotrudniki
    {
        $hashedToken = hash('sha256', $token);

        $sotrudnik = Sotrudniki::where('access_token', $hashedToken)
            ->where('token_expires_at', '>', Carbon::now())
            ->first();

        return $sotrudnik;
    }

    /**
     * Обновляет токены используя refresh токен
     *
     * @param string $refreshToken
     * @return array|null
     */
    public function refreshTokens(string $refreshToken): ?array
    {
        $hashedToken = hash('sha256', $refreshToken);

        $sotrudnik = Sotrudniki::where('refresh_token', $hashedToken)->first();

        if (!$sotrudnik) {
            return null;
        }

        // Генерируем новую пару токенов
        return $this->generateTokens($sotrudnik);
    }

    /**
     * Удаляет токены сотрудника (logout)
     *
     * @param Sotrudniki $sotrudnik
     * @return void
     */
    public function revokeTokens(Sotrudniki $sotrudnik): void
    {
        $sotrudnik->update([
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
        ]);
    }

    /**
     * Проверяет истек ли access токен
     *
     * @param Sotrudniki $sotrudnik
     * @return bool
     */
    public function isTokenExpired(Sotrudniki $sotrudnik): bool
    {
        if (!$sotrudnik->token_expires_at) {
            return true;
        }

        return Carbon::parse($sotrudnik->token_expires_at)->isPast();
    }
}

