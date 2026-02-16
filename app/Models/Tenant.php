<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'plan_id',
        'plan', // Legacy or direct plan name
        'settings',
        'expires_at'
    ];

    protected $casts = [
        'settings' => 'array',
        'expires_at' => 'datetime'
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
