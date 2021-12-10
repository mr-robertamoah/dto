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
        
        $this->registerCommands();
    }
    
    public function register()
    {
        
    }

    private function registerPublishables()
    {
        $this->publishes([
            __DIR__ . "/../../config/dto.php" => config_path("dto.php")
        ], 'dto-config');
    }

    private function registerCommands()
    {
        $this->commands([
            CreateDTOCommand::class
        ]);
    }
}