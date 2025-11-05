<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DemandForecast extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'blood_type', 'location', 'forecast_date', 'predicted_units'
    ];
}

