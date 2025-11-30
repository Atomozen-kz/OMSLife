<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {

            // Проверяем, является ли запись запросом
            if ($entry->type === 'request') {
                $path = $entry->content['uri'] ?? '';

                // Список путей, которые нужно исключить из мониторинга
                $excludedPaths = [
                    '/admin/api/notifications',
                    // Добавьте другие пути при необходимости
                ];

                foreach ($excludedPaths as $excludedPath) {
                    if (Str::is($excludedPath, $path)) {
                        return false; // Исключаем этот запрос из мониторинга
                    }
                }
            }

            // Показываем все записи (убрал условие $isLocal)
            return true;
//            return $isLocal ||
//                   $entry->isReportableException() ||
//                   $entry->isFailedRequest() ||
//                   $entry->isFailedJob() ||
//                   $entry->isScheduledTask() ||
//                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                'atom.ozen@gmail.com'
            ]);
        });
    }
}
