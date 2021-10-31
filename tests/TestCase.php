<?php

namespace MrRobertAmoah\Tests;

use MrRobertAmoah\Providers\DTOServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

class TestCase extends TestbenchTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            DTOServiceProvider::class
        ];
    }
}