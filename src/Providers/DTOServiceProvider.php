<?php

namespace MrRobertAmoah\DTO\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MrRobertAmoah\DTO\Console\CreateDTOCommand;

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
        $this->commands([
            CreateDTOCommand::class
        ]);
    }

    private function registerPublishables()
    {
        $this->publishes([
            __DIR__ . "/../../config/dto.php" => config_path("dto.php")
        ], 'dto-config');
    }
}