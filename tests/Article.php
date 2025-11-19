<?php

namespace Fishdaa\LaravelResourcePermissions\Tests;

use Fishdaa\LaravelResourcePermissions\Traits\HasAssignedModels;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasAssignedModels;

    protected $fillable = [
        'title',
        'content',
    ];
}

