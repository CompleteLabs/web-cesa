<?php

namespace Cesa\Contact\Providers;

use Illuminate\Support\ServiceProvider;

class ContactServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register any plugin services
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        
        // Load views if any
        // $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'contact');
        
        // Load routes if any
        // $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        
        // Publish assets or config if needed
        // $this->publishes([
        //     __DIR__ . '/../Config/contact.php' => config_path('contact.php'),
        // ], 'contact-config');
    }
}
