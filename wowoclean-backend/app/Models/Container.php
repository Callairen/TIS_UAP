<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Container extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'container_id',
        'waste_type',
        'weight_kg',
        'status',
    ];

    public function trackingLogs(): HasMany
    {
        return $this->hasMany(TrackingLog::class);
    }
}