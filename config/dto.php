<?php

return [
    //the name of the folder to use in your laravel app directory
    "folderName" => null,

    // when set to true, 'DTO' will be attached to the name of the dto class and file
    "attachDTO" => false,

    // when true, non-existing properties you try to set on the dto will be dynamically set
    "forcePropertiesOnDTO" => false,

    //this prevents the throwing of DTOPropertyNotFound exception
    "suppressPropertyNotFoundException" => false,

    //this prevents the throwing of DTOMethodNotFound exception
    "suppressMethodNotFoundException" => false,
];