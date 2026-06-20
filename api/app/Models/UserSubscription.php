<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'plan_code',
    'status',
    'provider',
    'provider_customer_id',
    'provider_subscription_id',
    'trial_ends_at',
    'current_period_ends_at',
    'canceled_at',
])]
class UserSubscription extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
