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

    /**
     * Required. With $timestamps = false, Laravel's getDates() returns an empty
     * array, so created_at is handed back as a raw MongoDB\BSON\UTCDateTime that
     * Carbon::parse() cannot read. Without this cast every password reset threw
     * InvalidFormatException before the token was ever checked.
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];
}
