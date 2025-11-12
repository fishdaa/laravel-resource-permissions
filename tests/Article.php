<?php

namespace Fishdaa\LaravelResourcePermissions\Tests;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'content',
    ];
}

