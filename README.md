# **Data Transfer Objects (DTOs)**

## About DTOs
This is a [laravel](https://laravel.com) package that will help you easily create data transfer objects from 
requests and arrays by simple using four simple steps:
+ Install package
+ Create DTO through commandline
+ Set public properties of the class
+ Then create your DTOs from requests or arrays

The DTO sets the properties with values from request or array so that this property bag (data transfer object) can be passed on to your functions in parts of your controllers, services or even actions;

## Installation
Install package using composer
```
    composer require mr-robertamoah/dto
```

## Configuration
To start configuring the workings of this package, first publish the configuration file using:
```
    php artisan vendor publish --tag=dto-config
```
This command adds ```dto.php``` file in the config folder.
The following shows the keys of the config file and what they do:
+ **folderName**: This will help you use a folder name other than DTOs which is the default
+ **attachDTO**: If this is set to ```true``` the name of every DTO you create will end with DTO
+ **forcePropertiesOnDTO**: An exception is thrown whenever you try to set a value to a non-existent property. Setting this to ```true``` will dynamically create the property on the DTO object.
+ **suppressPropertyNotFoundException**: When set to ```true```, no DTOPropertyNotFoundException will be thrown, rather, the object will be returned.
+ **suppressMethodNotFoundException**: When set to ```true```, no DTOMeothdNotFoundException will be thrown, rather, the object will be returned.


## Usage
Below are a break down of how to use the DTO
### Setting Properties
```php
    class UserDTO extends BaseDTO
    {
        public $name;
        public $email;
    }
```
### Creating
#### **From Request**
```php
    public function create(Illuminate\Http\Request $request)
    {
        $dto = UserDTO::fromRequest($request);
        //or
        $dto = UserDTO::new()->fromRequest($request);
    }
```
Note that this request should be of type ```Illuminate\Http\Request```.
#### **From Array**
```php
    public function create(Illuminate\Http\Request $request)
    {
        $data = [
            'name' => 'Robert Amoah',
            'email' => 'mr_robertamoah@yahoo.com',
        ];

        $dto = UserDTO::fromArray($data);
        //or
        $dto = UserDTO::new()->fromArray($data);
    }
```

### Transfering Data
```php
    public function create(Illuminate\Http\Request $request)
    {
        $dto = UserDTO::fromRequest($request);

        $user = UserService::createUser($dto);
    }
```

### Using The Data
```php
    public function createUser(UserDTO $userDTO)
    {
        $user = User::create($userDTO->getFilledData());
    }
```

### Extending The Creation Methods
You can extend either ```fromArray``` or ```fromRequest``` methods using ```fromArrayExtension``` and ```fromRequestExtension``` protected methods respectively. These methods will receive the first arguments (the array and request respectively) passed into the main creation methods and must return the same object;
```php
    class UserDTO extends BaseDTO
    {
        public $name;
        public $email;
        public $date;

        protected function fromRequestExtenstion(Illuminate\Http\Request $request) : BaseDTO
        {
            return $this->date = new Carbon();
        }

        protected function fromArrayExtenstion(array $data) : BaseDTO
        {
            return $this->date = new Carbon();
        }
    }
```

## Properties
This section shows you the protected properties available on the ```BaseDTO``` class which helps you get the best out of your dto objects.
#### **dtoDataKeys**
If you would want to get some properties other than the filled ones then you have to indicate the properties in this array
```php
    // dto class
    class UserDTO extends BaseDTO
    {
        protected array $dtoDataKeys = [
            'name', 'email'
        ];
    }

    //in services, controller or actions
    public function createUser(UserDTO $userDTO)
    {
        $user = User::create($userDTO->getData());
    }
```
Note that the ```getData``` method will return an empty array if no property is added to the ```dtoDataKeys```. If you pass ```true``` to the method like ```getData(true)```, then properties that have been filled will be returned as an array.
#### **dtoFileKeys**
This property helps you set the appropriate property names for files to be retrieved from a request as well as easily get an array of all such properties by using the ```getFiles``` method.
```php
    // dto class
    class UserDTO extends BaseDTO
    {
        protected array $dtoFileKeys = [
            'image1', 'image2'
        ];
    }

    // in service
    public function createUser(UserDTO $userDTO)
    {
        foreach($userDTO->getFiles() as $key => $file) {
            $file->save();
        }
    }
```
Also, ```dtoDataKeys``` and ```dtoFileKeys``` can both be set dynamically by calling the ```setDataKeys``` and ```setFileKeys``` methods respectively on an object. The append versions of these methods adds extra keys to the arrays (dtoDataKeys and dtoFileKeys).
```php
    UserDTO::new()
        ->setDataKeys(['name', 'email'])
        ->setFileKeys('image1, image2');
```
#### **dtoExclude**
Add the names of properties to this array if you do not want it to given a value during the creation of a DTO.
```php
    // dto class
    class UserDTO extends BaseDTO
    {
        protected array $dtoExclude = [
            'image1',
        ];
    }
```
#### **dtoOnly**
Add the names of properties to this array if you only want them to be given a value during the creation of a DTO. In the example below, only name and emails will be given a value during the creation of the DTO.
```php
    // dto class
    class UserDTO extends BaseDTO
    {
        public $name;
        public $email;
        public $age;

        protected array $dtoExclude = [
            'name', 'email'
        ];
    }
```
Note that the ```dtoOnly``` will take precendence over the ```dtoExclude``` once it has at least one entry.

## Important Methods
These methods allow you to use the dto fluently while getting the most out of it.
#### **with[PropertyName]**
This allows you to call a public method which starts with ```with``` and ends with the name of a property. The argument of this method should be the value you wish to assign to the property. The method will return the DTO after the value has be assigned. 
```php

    // in service
    public function createUser(UserDTO $userDTO)
    {
        $userDTO = $userDTO->withName('James Coffie');

        $user = User::create($userDTO->getData()):
    }
```
#### **addData**
This allows you to pass an array that contains property names as keys that point to values you would want assigned to the DTO. The keys of the array must match the names of the properties to which you want values assigned. The method will return the DTO after the value has be assigned. 
```php

    // in service
    public function createUser(UserDTO $userDTO)
    {
        $userDTO = $userDTO->addData([
            'name' => 'James Coffie',
            'email' => 'jamescoffie123@zigzag.org',
        ]);
        
        $user = User::create($userDTO->getData()):
    }
```
#### **new**
This is a static method that allows you to create an new object from the DTO class. 
```php

    // in controller
    public function create($request)
    {
        $userDTO = UserDTO::new();
        
        $user = UserService::create(
            $userDTO->setFileKeys(['image1', 'image2'])->fromRequest($request)
        ):
    }
```
#### **forceProperties**
The DTO will throw a DTOPropertyNotFound exception when you try to assign a value to a property not set in the DTO class. A property will be set dynamically when you use this method before creating the DTO.
```php

    // in controller
    public function create($request)
    {
        $userDTO = UserDTO::new();
        
        $user = UserService::create(
            $userDTO->forceProperties()->fromRequest($request)
        ):
    }
```

## Exceptions
These are the following exceptions that are thrown by this package:
+ DTOPropertyNotFound
+ DTOMethodNotFound
+ DTOWrongArgument
+ DTOFileAlreadyExists 

Note that DTOFileAlreadyExists can only thrown in console whereas the others can only be thrown when using the DTO. You can suppress ```DTOPropertyNotFound``` and ```DTOMethodNotFound``` exceptions in the configuration file. 