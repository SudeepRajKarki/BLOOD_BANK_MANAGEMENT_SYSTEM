<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChurnPrediction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'likelihood_score', 'prediction_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

