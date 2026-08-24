<?php

namespace App\Models;

// CRITICAL: Notice we are importing the MongoDB Eloquent Model, not the standard Illuminate one.
use MongoDB\Laravel\Eloquent\Model;

class TestItem extends Model
{
    // Explicitly tell this model to use the MongoDB connection
    protected $connection = 'mongodb';

    // The name of the collection (similar to a table in SQL)
    protected $collection = 'test_items';

    // Allow these fields to be mass-assigned
    protected $fillable = ['name', 'status'];
}
