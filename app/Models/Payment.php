<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_number',
        'method',
        'amount',
        'status',
        'transaction_reference',
        'note',
        'qr',
        'md5',
        'payload',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'payload' => 'array',
        'paid_at' => 'datetime',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeCash(Builder $query): Builder
    {
        return $query->where('method', 'cash');
    }

    public function scopeKhqr(Builder $query): Builder
    {
        return $query->where('method', 'khqr');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isKhqr(): bool
    {
        return $this->method === 'khqr';
    }

    public function isCash(): bool
    {
        return $this->method === 'cash';
    }

    public function markAsPaid(?string $reference = null, ?array $rawPayload = null): void
    {
        $this->update([
            'status'                => 'paid',
            'transaction_reference' => $reference ?? $this->transaction_reference,
            'payload'               => $rawPayload ?? $this->payload,
            'paid_at'               => now(),
        ]);
    }
}
