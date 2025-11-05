<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'otp_code', 'is_used', 'expires_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

