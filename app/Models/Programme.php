<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Programme extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'programmes';

    protected $fillable = [
        'name',
        'faculty',
        'level',
        'type',
        'status',
    ];
}
