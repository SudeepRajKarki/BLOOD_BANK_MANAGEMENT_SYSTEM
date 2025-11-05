<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BloodInventory extends Model
{
    use HasFactory;

    // Migration created table name as `blood_inventory` (singular), so explicitly set it
    protected $table = 'blood_inventory';

    protected $fillable = [
        'blood_type', 'quantity_ml', 'location'
    ];

    public $timestamps = false;
}

