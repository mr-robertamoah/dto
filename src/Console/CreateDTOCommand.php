<?php

namespace MrRobertAmoah\DTO\Console;

use MrRobertAmoah\DTO\Console\BaseCommand;

class CreateDTOCommand extends BaseCommand
{
    protected $signature = "dto:make 
        {fileName* : these are the names of the DTOs you want to create} 
        {--folderName= : this is the name of the folder you would want to put the DTOs}
        {--attachDTO : this helps to attach DTO to the file names provided.}
        {--force : this option is to force the creation of the file even if it already exists}";

    protected $description = "this is to help you create a DTO file easily";

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