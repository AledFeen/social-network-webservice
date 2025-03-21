<?php

namespace App\Providers;

use App\Services\PrivacySettings\checkingSettings;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    use checkingSettings;
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes();
        Broadcast::routes(['middleware' => ['web']]);
        require base_path('routes/channels.php');
    }
}
