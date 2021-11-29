<?php

namespace MrRobertAmoah\DTO\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MrRobertAmoah\DTO\Exceptions\DTOFileAlreadyExists;

class BaseCommand extends Command
{
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

    protected function isReservedName($fileNames): string | null
    {
        foreach ($fileNames as  $fileName) {
           if (! in_array(trim($fileName), $this->reservedNames)) {
               continue;
           }

           return $fileName;
        }

        return null;
    }

    protected function getFullDirectory(string $folderName): string
    {
        return "{$this->laravel->basePath('app')}/{$folderName}";
    }

    protected function setNamespace()
    {
        $namespace = $this->getAppNamespace();

        $this->namespace = "{$namespace}\\{$this->folderName}";
    }

    protected function makeDirectory(string $directory)
    {
        if (File::isDirectory($directory)) {
            return;
        }
        
        File::makeDirectory($directory, 0777, true, true);
    }

    protected function makeDTODirectory()
    {
        $this->makeDirectory($this->directory);
    }

    protected function setFolderName()
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

    protected function setDirectory()
    {
        $this->directory = $this->getFullDirectory($this->folderName);
    }

    protected function getAppNamespace() {
        return trim($this->laravel->getNamespace(), "\\");
    }

    protected function adjustFileName($fileName)
    {
        $fileName = trim($fileName, "\\/");
        
        if (Str::endsWith($fileName, '.php')) {
            $fileName = Str::beforeLast($fileName, '.php');
        }

        $fileName = $this->setSubFoldersAndFileName($fileName); //contains the subfolders

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

    protected function titleCaseFileName()
    {
        $this->fileName = ucfirst($this->fileName);
    }

    protected function setSubFoldersAndFileName(string $fileName)
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

    protected function checkPath(string $path)
    {
        if (! File::exists($path)) {
            return;
        }

        if ($this->option('force')) {
            return File::delete($path);
        }

        throw new DTOFileAlreadyExists("{$path} already exists. If you want to create it then set the --force option to true");
    }

    protected function createDTOs()
    {
        foreach ($this->argument('fileName') as $fileName) {
            $this->adjustFileName($fileName);

            $path = $this->getPath();

            $this->checkPath($path);
            
            File::put(
                $path, 
                $this->adjustStubContent($this->getStub())
            );

            $this->info("{$this->fileName}.php was successfully created.");
        }
    }

    protected function getPath()
    {
        if ($this->subFolders) {
            return "{$this->directory}/{$this->subFolders}/{$this->fileName}.php";
        }

        return "{$this->directory}/{$this->fileName}.php";
    }

    protected function adjustStubContent(string $content)
    {
        $content = str_replace("{{ namespace }}", $this->getNamespace(), $content);

        $content = str_replace("{{ class }}", $this->fileName, $content);

        return $content;
    }

    protected function getNamespace()
    {
        if ($this->subFolders) {
            $subFolders = str_replace('/', '\\', $this->subFolders);

            return "{$this->namespace}\\{$subFolders}";
        }

        return $this->namespace;
    }

    protected function getStub()
    {
        return File::get(__DIR__ . "/../stubs/dto.stub");
    }

    protected function getFileCount()
    {
        return count($this->argument('fileName'));
    }
}