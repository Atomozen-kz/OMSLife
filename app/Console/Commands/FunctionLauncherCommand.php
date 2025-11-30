<?php

namespace App\Console\Commands;

use App\Services\PushkitNotificationService;
use Illuminate\Console\Command;

class FunctionLauncherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:func';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'запускает нужную функцию';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        $service = new PushkitNotificationService();
//        $service->dispatchPushNotification();
//        $this->info("Result: " . $result);
    }
}
