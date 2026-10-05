<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailOtpToken extends Model
{
    protected $fillable = ['user_id', 'email', 'otp_hash', 'expires_at', 'failed_attempts', 'send_count', 'send_window_started_at', 'delivery_status'];

    protected $hidden = ['otp_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'send_window_started_at' => 'datetime',
            'failed_attempts' => 'integer',
            'send_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->lessThanOrEqualTo(now());
    }
}
