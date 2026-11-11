<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'transaction_code',
    'customer_name',
    'customer_whatsapp',
    'pickup_date',
    'pickup_time',
    'total_price',
    'status',
    'payment_status',
    'paid_at',
    'note',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
            'total_price' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
