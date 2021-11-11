<?php

namespace MrRobertAmoah\DTO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MrRobertAmoah\Database\Factories\UserFactory;

class User extends Model
{
    use HasFactory;

    protected $guard = [];

    public static function newFactory()
    {
        return UserFactory::new();
    }
}