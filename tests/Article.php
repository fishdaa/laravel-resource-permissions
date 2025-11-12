<?php

namespace Fishdaa\LaravelResourcePermissions\Tests;

use Fishdaa\LaravelResourcePermissions\Traits\HasAssignedUsers;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasAssignedUsers;

    protected $fillable = [
        'title',
        'content',
    ];
}

