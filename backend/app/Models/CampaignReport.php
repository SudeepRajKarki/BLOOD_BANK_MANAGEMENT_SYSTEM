<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'report_text',
        'total_quantity_ml',
        'by_type',
        'donors',
        'created_by',
    ];

    protected $casts = [
        'by_type' => 'array',
        'donors' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
