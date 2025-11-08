<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'campaign_id',
        'blood_type',
        'quantity_ml',
        'donation_date', 
        'location',    
      ];

   

    public function donor()
    {
        return $this->belongsTo(User::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    // Optional: Accessor if you want 'donation_date' as alias
    public function getDonationDateAttribute()
    {
        return $this->created_at?->toDateString();
    }
}