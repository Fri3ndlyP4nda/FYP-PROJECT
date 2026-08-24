<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Course extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'courses';

    protected $fillable = [
        'course_code',
        'course_name',
        'faculty',
        'programme',
        'status'
    ];
}
