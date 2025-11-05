<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BloodRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiver_id', 'blood_type', 'reason', 'quantity_ml', 'priority', 'status', 'location'
    ];

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function donorMatches()
    {
        return $this->hasMany(DonorMatch::class, 'request_id');
    }
}

