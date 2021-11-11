<?php

namespace MrRobertAmoah\DTO\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MrRobertAmoah\DTO\Exceptions\DTOFileAlreadyExists;

class CreateDTOCommand extends Command
{
    protected $signature = "dto:make 
        {fileName* : these are the names of the DTOs you want to create} 
        {--folderName= : this is the name of the folder you would want to put the DTOs}
        {--attachDTO : this helps to attach DTO to the file names provided.}
        {--force : this option is to force the creation of the file even if it already exists}";

    protected $description = "this is to help you create a DTO file easily";

    protected $reservedNames = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
    ];

    protected $fileName;

    protected $directory;

    protected $subFolders;

    protected $namespace;

    protected $folderName;

    private function isReservedName($fileNames): string | null
    {
        foreach ($fileNames as  $fileName) {
           if (! in_array(trim($fileName), $this->reservedNames)) {
               continue;
           }

           return $fileName;
        }

        return null;
    }

    private function getFullDirectory(string $folderName): string
    {
        return "{$this->laravel->basePath('app')}/{$folderName}";
    }

    private function setNamespace()
    {
        $namespace = $this->getAppNamespace();

        $this->namespace = "{$namespace}\\{$this->folderName}";
    }

    private function makeDirectory(string $directory)
    {
        if (File::isDirectory($directory)) {
            return;
        }
        
        File::makeDirectory($directory, 0777, true, true);
    }

    private function makeDTODirectory()
    {
        $this->makeDirectory($this->directory);
    }

    private function setFolderName()
    {
        $this->folderName = $this->option('folderName') ?: config('dto.folderName', 'DTOs');

        if (strlen($this->folderName) < 1) {
            
            $this->folderName = 'DTOs';
        }
        
        $this->folderName = trim($this->folderName);

        if (Str::startsWith($this->folderName, $this->getAppNamespace())) {
            $this->folderName = Str::replaceFirst(
                $this->laravel->getNamespace(),
                "",
                $this->folderName
            );
        }
    }

    private function setDirectory()
    {
        $this->directory = $this->getFullDirectory($this->folderName);
    }

    private function getAppNamespace() {
        return trim($this->laravel->getNamespace(), "\\");
    }

    private function adjustFileName($fileName)
    {
        $fileName = trim($fileName, "\\/");
        
        if (Str::endsWith($fileName, '.php')) {
            $fileName = Str::beforeLast($fileName, '.php');
        }

        $fileName = $this->setSubFoldersAndFileName($fileName); //contains the subfolders
        
        $this->info($this->fileName);

        if ($this->subFolders) {
            
            $this->makeDirectory("{$this->directory}/{$this->subFolders}");
        }

        $this->titleCaseFileName();

        if (! config('dto.attachDTO') && ! $this->option('attachDTO')) {
            return;
        }

        if (strtolower(substr($this->fileName, -3)) === 'dto') {
            $name = substr($this->fileName, 0, strlen($this->fileName) - 3);

            $this->fileName = "{$name}DTO";

            return;
        }

        $this->fileName = "{$this->fileName}DTO";
    }

    private function titleCaseFileName()
    {
        $this->fileName = strtoupper(substr($this->fileName, 0, 1)) . substr($this->fileName, 1, strlen($this->fileName));
    }

    private function setSubFoldersAndFileName(string $fileName)
    {
        $this->fileName = $fileName;

        if (!str_contains($fileName, '/') || str_contains($fileName, '\\')) {
           $this->subFolders = null;

           return $fileName;
        }

        $fileName = str_replace('\\', '/', $fileName);

        $this->subFolders = substr($fileName, 0, strrpos($fileName, '/'));

        $this->fileName = ltrim(str_replace($this->subFolders, '', $fileName), '/');

        return $fileName;
    }

    private function checkPath(string $path)
    {
        if (! File::exists($path)) {
            return;
        }

        if ($this->option('force')) {
            return File::delete($path);
        }

        throw new DTOFileAlreadyExists("{$path} already exists. If you want to create it then set the --force option to true");
    }

    private function createDTOs()
    {
        foreach ($this->argument('fileName') as $fileName) {
            $this->adjustFileName($fileName);

            $path = $this->getPath();

            $this->checkPath($path);
            
            File::put(
                $path, 
                $this->adjustStubContent($this->getStub())
            );

            // dd($this->fileName, $fileName);

            $this->info("{$this->fileName}.php was successfully created.");
        }
    }

    private function getPath()
    {
        if ($this->subFolders) {
            return "{$this->directory}/{$this->subFolders}/{$this->fileName}.php";
        }

        return "{$this->directory}/{$this->fileName}.php";
    }

    private function adjustStubContent(string $content)
    {
        $content = str_replace("{{ namespace }}", $this->getNamespace(), $content);

        $content = str_replace("{{ class }}", $this->fileName, $content);

        return $content;
    }

    private function getNamespace()
    {
        if ($this->subFolders) {
            $subFolders = str_replace('/', '\\', $this->subFolders);

            return "{$this->namespace}\\{$subFolders}";
        }

        return $this->namespace;
    }

    private function getStub()
    {
        return File::get(__DIR__ . "/../stubs/dto.stub");
    }

    private function getFileCount()
    {
        return count($this->argument('fileName'));
    }

    public function handle()
    {
        $this->info("process of creating {$this->getFileCount()} dto file(s) has started");

        if ($fileName = $this->isReservedName($this->argument('fileName'))) {
            $this->error("Sorry, {$fileName} name provided is reserved.");

            return 0;
        }

        $this->setFolderName();

        $this->setDirectory();

        $this->makeDTODirectory();

        $this->setNamespace();

        $this->createDTOs();

        $this->info("successfully created all dto files");

        return 1;
    }
    
}