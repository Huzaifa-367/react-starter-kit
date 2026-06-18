<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastNotification extends Model
{
    use HasFactory;

    protected $table = 'broadcast_notifications';

    protected $fillable = [
        'admin_id',
        'title',
        'body',
        'channels',
        'target_type',
        'target_id',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'data',
    ];

    protected $casts = [
        'channels' => 'json',
        'data' => 'json',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
