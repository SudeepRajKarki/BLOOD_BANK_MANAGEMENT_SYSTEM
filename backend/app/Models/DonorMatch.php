<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DonorMatch extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'request_id', 'donor_id', 'match_score', 'status', 'scheduled_at', 'scheduled_location'
    ];

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function request()
    {
        return $this->belongsTo(BloodRequest::class, 'request_id');
    }
}

