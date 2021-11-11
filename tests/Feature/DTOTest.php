<?php

namespace MrRobertAmoah\Tests;

use Illuminate\Support\Facades\File;
use MrRobertAmoah\DTO\Exceptions\DTOFileAlreadyExists;

class DTOTest extends TestCase
{

    public function testCommandCanCreateASingleDTOFile()
    {
        $this->deleteDTODirectory();

        $this
            ->artisan('dto:make', ['fileName' => ['image']])
            ->expectsOutput('process of creating 1 dto file(s) has started')
            ->expectsOutput('Image.php was successfully created.')
            ->expectsOutput('successfully created all dto files')
            ->assertExitCode(1);

        $this->assertDTOFileExists("Image");

        $this->deleteDTODirectory();
    }

    public function testCommandCanCreateASingleDTOFileAndAttachDTOThroughOptions()
    {
        $this->deleteDTODirectory();

        $this
            ->artisan('dto:make', ['fileName' => ['image', 'videos/videoFile'], '--attachDTO' => true])
            ->expectsOutput('process of creating 2 dto file(s) has started')
            ->expectsOutput('ImageDTO.php was successfully created.')
            ->expectsOutput('VideoFileDTO.php was successfully created.')
            ->expectsOutput('successfully created all dto files')
            ->assertExitCode(1);

        $this->assertDTOFileExists("ImageDTO");
        $this->assertDTOFileExists("videos/VideoFileDTO");

        $this->deleteDTODirectory();
    }

    public function testConfigIsPublished()
    {
        $this->deleteDTOConfig();
        
        $this->artisan('vendor:publish', ['--tag' => 'dto-config']);

        $this->assertFileExists(config_path('dto.php'));

        $this->assertFileEquals(config_path('dto.php'), __DIR__ . "/../../config/dto.php");

        $this->deleteDTOConfig();
    }

    public function testCommandCanCreateASingleDTOFileAndAttachDTOThroughConfig()
    {
        $this->deleteDTODirectory();
        
        $this->artisan('vendor:publish', ['--tag' => 'dto-config']);

        config(['dto.attachDTO' => true]);

        $this
            ->artisan('dto:make', ['fileName' => ['image']])
            ->expectsOutput('process of creating 1 dto file(s) has started')
            ->expectsOutput('ImageDTO.php was successfully created.')
            ->expectsOutput('successfully created all dto files')
            ->assertExitCode(1);

        $this->assertDTOFileExists("ImageDTO");

        $this->deleteDTODirectory();
    }
    
    public function testCommandCanCreateAMiultipleDTOFile()
    {
        $this->deleteDTODirectory();

        $this
            ->artisan('dto:make', ['fileName' => ['image', 'video']])
            ->expectsOutput('process of creating 2 dto file(s) has started')
            ->expectsOutput('Image.php was successfully created.')
            ->expectsOutput('Video.php was successfully created.')
            ->expectsOutput('successfully created all dto files')
            ->assertExitCode(1);

        $this->assertDTOFileExists("Image");
        $this->assertDTOFileExists("Video");

        $this->deleteDTODirectory();
    }
    
    public function testCommandCanCreateASingleDTOFileInSubfolder()
    {
        $this->deleteDTODirectory();

        $this
            ->artisan('dto:make', ['fileName' => ['images/file']])
            ->expectsOutput('process of creating 1 dto file(s) has started')
            ->expectsOutput('File.php was successfully created.')
            ->expectsOutput('successfully created all dto files')
            ->assertExitCode(1);

        $this->assertDTOFileExists("Images/File");

        $this->deleteDTODirectory();
    }
    
    public function testCommandCanCreateMultipleDTOFilesInSubfolder()
    {
        $this->deleteDTODirectory();

        $this
            ->artisan('dto:make', ['fileName' => ['images/imageFile', 'videos/videoFile']])
            ->expectsOutput('process of creating 2 dto file(s) has started')
            ->expectsOutput('ImageFile.php was successfully created.')
            ->expectsOutput('VideoFile.php was successfully created.')
            ->expectsOutput('successfully created all dto files')
            ->assertExitCode(1);

        $this->assertDTOFileExists("Images/ImageFile");
        $this->assertDTOFileExists("Videos/VideoFile");

        $this->deleteDTODirectory();
    }

    public function testCommandThrowsErrorWhenFileExistsAndForceOptionIsFalse()
    {
        $this->expectException(DTOFileAlreadyExists::class);

        $this->makeDTODirectory();

        $this->makeDTOFiles('Image');
        
        $this->artisan('dto:make', ['fileName' => ['image', 'video']]);
        
        $this->assertDTOFileExists('Image');

        $this->deleteDTODirectory();
    }

    public function testCommandDoesntThrowErrorWhenFileExistsAndForceOptionIsTrue()
    {
        $this->makeDTODirectory();

        $this->makeDTOFiles('Image');
        
        $this->artisan('dto:make', ['fileName' => ['image', 'video'], '--force' => true]);
        
        $this->assertDTOFileExists('Image');

        $this->deleteDTODirectory();
    }
}