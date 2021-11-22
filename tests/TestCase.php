<?php

namespace MrRobertAmoah\Tests;

use Illuminate\Support\Facades\File;
use MrRobertAmoah\DTO\Providers\DTOServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TestCase extends TestbenchTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            DTOServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
    }

    public function makeDTOFiles(string|array $fileName, string $content = '')
    {
        if (is_string($fileName)) {
            return File::put("{$this->getDTODirectory()}/{$fileName}.php", $content);
        }

        foreach ($fileName as $name) {
            File::put("{$this->getDTODirectory()}/{$name}.php", $content);
        }
    }

    public function makeDTODirectory()
    {
        File::makeDirectory(
            path: $this->getDTODirectory(),
            force: true
        );
    }

    public function assertDTOFileExists(string $string)
    {
        $this->assertFileExists("{$this->getDTODirectory()}/{$string}.php");
    }

    public function deleteDTODirectory()
    {
        File::deleteDirectory("{$this->getDTODirectory()}");
    }

    public function deleteDTOConfig()
    {
        if (!File::exists(config_path('dto.php'))) {
            return;
        }

        File::delete(config_path('dto.php'));
    }

    public function getDTODirectory()
    {
        return "{$this->app->basePath('app')}/DTOs";
    }

    protected function makeFile($file)
    {
        return new UploadedFile($file,File::name($file),File::mimeType($file),null,true);
    }
}