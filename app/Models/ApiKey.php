<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'key',
        'secret',
        'name',
        'is_active',
        'rate_limit',
        'monthly_quota',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'rate_limit' => 'integer',
        'monthly_quota' => 'integer',
        'usage_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to only include active keys.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
