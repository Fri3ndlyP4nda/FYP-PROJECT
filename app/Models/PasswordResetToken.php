<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class PasswordResetToken extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'password_reset_tokens';

    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    public $timestamps = false;
}
