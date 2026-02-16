<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MissionFile extends Model
{
    protected $fillable = [
        'tenant_id',
        'original_name',
        'stored_path',
        'mime_type',
        'type',
        'status',
        'error_message',
        'processed_at',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'processed_at' => 'datetime'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
