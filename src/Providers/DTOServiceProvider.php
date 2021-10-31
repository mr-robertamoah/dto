<?php

namespace MrRobertAmoah\Providers;

use Illuminate\Support\ServiceProvider;

class DTOServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishables();
        }
    }
    
    public function register()
    {
        
    }

    private function registerPublishables()
    {
        $this->publishes([
            __DIR__ . "/../../config/dto" => config_path("dto.php")
        ], 'dto-config');
    }
}