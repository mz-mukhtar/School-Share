<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpgradeRequest extends Model
{
    protected $fillable = [
        'user_id',
        'requested_plan',
        'billing_cycle',
        'status',
        'mail_delivery_status',
        'mail_delivery_error',
        'mail_sent_at',
        'admin_notes',
        'processed_at',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'mail_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function formattedPlan(): string
    {
        return match($this->requested_plan) {
            'pro'    => 'Pro',
            'custom' => 'Custom (White-label)',
            default  => ucfirst($this->requested_plan),
        };
    }

    public function formattedPrice(): string
    {
        if ($this->requested_plan === 'custom') return 'Contact for pricing';
        return $this->billing_cycle === 'yearly' ? '2,000 ETB/year' : '200 ETB/month';
    }
}
