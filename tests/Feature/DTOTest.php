<?php

namespace MrRobertAmoah\Tests;

use MrRobertAmoah\DTO\Exceptions\DTOMethodNotFound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use MrRobertAmoah\DTO\DTOs\ExampleDTO;
use MrRobertAmoah\DTO\DTOs\FilesDTO;
use MrRobertAmoah\DTO\DTOs\ImageDTO;
use MrRobertAmoah\DTO\DTOs\ImageExcludeDTO;
use MrRobertAmoah\DTO\DTOs\ImageOnlyDTO;
use MrRobertAmoah\DTO\Exceptions\DTOFileAlreadyExists;
use MrRobertAmoah\DTO\Exceptions\DTOPropertyNotFound;
use MrRobertAmoah\DTO\Exceptions\DTOWrongArgument;
use MrRobertAmoah\DTO\Models\User;

class DTOTest extends TestCase
{
    use RefreshDatabase;

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

    public function testDTOCanBeCreatedFromArrayUsingSetProperties()
    {        
        $data = [
            'name' => 'robert amoah'
        ];

        $dto = ExampleDTO::fromArray($data);

        $this->assertIsObject($dto);

        $this->assertEquals($dto->name, $data['name']);
    }

    public function testDTOCanBeCreatedFromRequestUsingSetProperties()
    {        
        $data = [
            'name' => 'robert amoah'
        ];

        $request = Request::createFromGlobals();

        $dto = ExampleDTO::fromRequest($request->replace($data));

        $this->assertIsObject($dto);

        $this->assertEquals($dto->name, $data['name']);
    }

    public function testDTOCanSetDataKeysWithArray()
    {
        $data = ['name', 'date'];
        $dto = ExampleDTO::new()->setDataKeys($data);

        $this->assertIsArray($dto->getDataKeys());
        $this->assertEquals($data, $dto->getDataKeys());
    }

    public function testDTOCanSetDataKeysWithString()
    {
        $data = 'name, date';
        $dto = ExampleDTO::new()->setDataKeys($data);

        $this->assertIsArray($dto->getDataKeys());
        $this->assertCount(2, $dto->getDataKeys());
        $this->assertEquals('name', $dto->getDataKeys()[0]);
    }

    public function testDTOExpectsWrongArgumentExceptionWhenSettingDataKeys()
    {
        $this->expectException(DTOWrongArgument::class);

        ExampleDTO::new()->setDataKeys();
    }

    public function testDTOCanGetDataAsArrayWhenDataKeysAreSet()
    {
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/jpeg',
            'size' => 72382923892,
            'path' => 'storage/images'
        ];

        $request = Request::createFromGlobals();

        $dto = ImageDTO::new()->setDataKeys('name, mime')->fromRequest($request->replace($data));

        $this->assertIsArray($dto->getDataKeys());

        $this->assertEquals($dto->name, $data['name']);
        $this->assertEquals($dto->mime, $data['mime']);
        $this->assertCount(2, $dto->getDataKeys());

        $dtoData = $dto->getData();
        ray($dtoData);
        $this->assertIsArray($dtoData);
        $this->assertArrayHasKey('name', $dtoData);
        $this->assertArrayHasKey('mime', $dtoData);
        $this->assertArrayNotHasKey('size', $dtoData);
        $this->assertArrayNotHasKey('path', $dtoData);
    }

    public function testDTOCanGetEmptyDataAsArrayWhenDataKeysAreNotSet()
    {
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/jpeg',
            'size' => 72382923892,
            'path' => 'storage/images'
        ];

        $request = Request::createFromGlobals();

        $dto = ImageDTO::fromRequest($request->replace($data));

        $this->assertIsArray($dto->getDataKeys());

        $this->assertEquals($dto->name, $data['name']);
        $this->assertEquals($dto->mime, $data['mime']);
        $this->assertCount(0, $dto->getDataKeys());

        $dtoData = $dto->getData();
        $this->assertIsArray($dtoData);
        $this->assertArrayNotHasKey('name', $dtoData);
        $this->assertArrayNotHasKey('mime', $dtoData);
        $this->assertArrayNotHasKey('size', $dtoData);
        $this->assertArrayNotHasKey('path', $dtoData);
    }

    public function testDTOCanGetDataAsArrayWhenDataKeysAreNotSetButAllIsSetToTrue()
    {
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/jpeg',
            'path' => 'storage/images'
        ];

        $request = Request::createFromGlobals();

        $dto = ImageDTO::fromRequest($request->replace($data));

        $this->assertIsArray($dto->getDataKeys());

        $this->assertEquals($dto->name, $data['name']);
        $this->assertEquals($dto->mime, $data['mime']);
        $this->assertCount(0, $dto->getDataKeys());

        $dtoData = $dto->appendSpecifiedDataKeys([
            'name'=> 'name_key', 'mime'=>'mime_type'
        ])->getData(all: true); //all is set to true
        $this->assertIsArray($dtoData);
        $this->assertArrayHasKey('name_key', $dtoData);
        $this->assertArrayHasKey('mime_type', $dtoData);
        $this->assertArrayHasKey('path', $dtoData);
    }

    public function testDTOCanGetFilledDataAsArrayWhenDataKeysAreNotSetButFilledIsSetToTrue()
    {
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/jpeg',
            'size' => 72382923892,
            'path' => 'storage/images'
        ];

        $request = Request::createFromGlobals();

        $dto = ImageDTO::fromRequest($request->replace($data));

        $this->assertIsArray($dto->getDataKeys());

        $this->assertEquals($dto->name, $data['name']);
        $this->assertEquals($dto->mime, $data['mime']);
        $this->assertCount(0, $dto->getDataKeys());

        $dtoData = $dto->getData(true);
        $this->assertIsArray($dtoData);
        $this->assertArrayHasKey('name', $dtoData);
        $this->assertArrayHasKey('mime', $dtoData);
        $this->assertArrayHasKey('path', $dtoData);
        $this->assertArrayNotHasKey('size', $dtoData);
    }

    public function testDTOCanGetFilledDataAsArray()
    {
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/jpeg',
            'size' => 72382923892,
            'path' => 'storage/images'
        ];

        $request = Request::createFromGlobals();

        $dto = ImageDTO::fromRequest($request->replace($data));

        $this->assertIsArray($dto->getDataKeys());

        $this->assertEquals($dto->name, $data['name']);
        $this->assertEquals($dto->mime, $data['mime']);
        $this->assertCount(0, $dto->getDataKeys());

        $dtoData = $dto->getFilledData();
        $this->assertIsArray($dtoData);
        $this->assertArrayHasKey('name', $dtoData);
        $this->assertArrayHasKey('mime', $dtoData);
        $this->assertArrayHasKey('path', $dtoData);
        $this->assertArrayNotHasKey('size', $dtoData);
    }

    //set files values
    public function testDTOCanSetPropertiesAsFilesUsingArray()
    {
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/jpeg',
            'size' => 72382923892,
            'path' => 'storage/images',
            'file' => null
        ];

        $request = Request::createFromGlobals();
        
        $dto = ImageDTO::new()->setFileKeys([
            'file', 'hey'
        ])->fromRequest($request->replace($data));
        
        $fileKeys = $dto->getFileKeys();

        $this->assertIsArray($fileKeys);
        $this->assertCount(2, $fileKeys);

        $this->assertEquals('file', $fileKeys[0]);
        $this->assertEquals('hey', $fileKeys[1]);
    }

    public function testDTOCanSetPropertyUsingWithPropertyMethod()
    { 
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/jpeg',
            'size' => 72382923892,
            'path' => 'storage/images',
            'file' => null
        ];

        $request = Request::createFromGlobals();
        
        $dto = ImageDTO::new()->setFileKeys([
            'file', 'hey'
        ])->fromRequest($request->replace($data));
        
        $dto = $dto->withName('sup.jpg');

        $this->assertEquals('sup.jpg',  $dto->name);
    }

    public function testDTOCannotSetWrongPropertyUsingWithPropertyMethod()
    { 
        $this->expectException(DTOPropertyNotFound::class);

        $data = [
            'name' => 'cool.png',
            'mime' => 'image/jpeg',
            'size' => 72382923892,
            'path' => 'storage/images',
            'file' => null
        ];

        $request = Request::createFromGlobals();
        
        $dto = ImageDTO::new()->setFileKeys([
            'file', 'hey'
        ])->fromRequest($request->replace($data));
        
        $dto = $dto->withImage('sup.jpg');

        $this->assertEquals('sup.jpg',  $dto->name);
    }

    public function testDTOCanSetPropertiesUsingWithAddDataMethod()
    { 
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/png',
            'size' => 72382923892,
            'path' => 'storage/images',
            'file' => null
        ];

        $request = Request::createFromGlobals();
        
        $dto = ImageDTO::new()->setFileKeys([
            'file', 'hey'
        ])->fromRequest($request->replace($data));
        
        $dto = $dto->addData(['name' => 'sup.jpg', 'path' => 'image/jpg']);

        $this->assertEquals('sup.jpg',  $dto->name);
        $this->assertEquals('image/jpg',  $dto->path);
    }

    public function testDTOCannotSetWrongPropertiesUsingWithAddDataMethod()
    { 
        $this->expectException(DTOPropertyNotFound::class);

        $data = [
            'name' => 'cool.png',
            'mime' => 'image/png',
            'size' => 72382923892,
            'path' => 'storage/images',
            'file' => null
        ];

        $request = Request::createFromGlobals();
        
        $dto = ImageDTO::new()->setFileKeys([
            'file', 'hey'
        ])->fromRequest($request->replace($data));
        
        $dto = $dto->addData(['image' => 'sup.jpg', 'path' => 'image/jpg']);

        $this->assertEquals('sup.jpg',  $dto->name);
        $this->assertEquals('image/jpg',  $dto->path);
    }

    public function testDTOExpectsWrongArgumentExceptionWhenCallingStaticMethodWithNoOrWrongArguments()
    {
        $this->expectException(DTOWrongArgument::class);

        ExampleDTO::fromArray('hey'); //wrong argument
    }

    public function testDTOExpectsWrongArgumentExceptionWhenCallingWrongStaticMethod()
    {
        $this->expectException(DTOMethodNotFound::class);

        ExampleDTO::setDataKeys(); //wrong static method
    }

    public function testDTOExpectsWrongArgumentExceptionWhenCallingMethodWithNoOrWrongArguments()
    {
        $this->expectException(DTOWrongArgument::class);

        ExampleDTO::new()->setFileKeys(); //no arguments
    }

    public function testDTOExpectsWrongArgumentExceptionWhenCallingWrongMethod()
    {
        $this->expectException(DTOMethodNotFound::class);

        ExampleDTO::new()->setDataKey(); //wrong method
    }

    public function testDTOCanSetValuesOfOnlyPropertiesInDTOOnly()
    {
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/jpeg',
            'size' => 72382923892,
            'path' => 'storage/images',
            'file' => null
        ];

        $request = Request::createFromGlobals();
        
        $dto = ImageOnlyDTO::fromRequest($request->replace($data));

        $this->assertCount(2, $dto->getFilledData());
        $this->assertEquals($data['name'], $dto->name);
        $this->assertEquals($data['mime'], $dto->mime);
        $this->assertNotEquals($data['path'], $dto->path);
        $this->assertNotEquals($data['size'], $dto->size);

    }
    
    public function testDTOCanSetValuesOfPropertiesWhileExcludingThoseInDTOExclude()
    {
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/jpeg',
            'size' => 72382923892,
            'path' => 'storage/images',
            'file' => null
        ];

        $request = Request::createFromGlobals();
        
        $dto = ImageExcludeDTO::fromRequest($request->replace($data));

        $this->assertCount(4, $dto->getFilledData());
        $this->assertEquals($data['name'], $dto->name);
        $this->assertEquals($data['mime'], $dto->mime);
        $this->assertNotEquals($data['path'], $dto->path);
        $this->assertNotEquals($data['size'], $dto->size);

    }

    public function testDTOCanSetFilePropertiesUsingWithAddDataMethod()
    { 
        $data = [
            'name' => 'cool.png',
            'mime' => 'image/png',
            'size' => 72382923892,
            'path' => 'storage/images',
            'file' => null
        ];

        $file = __DIR__ . "/../../src/stubs/dto.stub";
        $files = [
            'one' => $this->makeFile($file),
            'multiple' => [
                new UploadedFile($file,File::name($file),File::mimeType($file),null,true),
                $this->makeFile($file)
            ]
        ];

        $request = new Request(
            query: $data,
            files: $files
        );
        
        $dto = FilesDTO::fromRequest($request);
        // dd($dto);
        $this->assertEquals($data['name'],  $dto->name);
        $this->assertEquals($files['one']->getClientOriginalName(),  $dto->one->getClientOriginalName());
        $this->assertCount(2,  $dto->multiple);
    }
}