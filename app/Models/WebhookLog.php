<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class WebhookLog extends Model
{
    use HasFactory;

    protected $table = 'webhook_logs';

    protected $fillable = [
        'source',
        'event_id',
        'event_type',
        'payload',
        'processed',
        'error',
    ];

    protected $casts = [
        'payload' => 'json',
        'processed' => 'boolean',
    ];

    /**
     * Scope a query to only include unprocessed webhook logs.
     */
    public function scopeUnprocessed(Builder $query): Builder
    {
        return $query->where('processed', false);
    }
}
