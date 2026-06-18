<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $table = 'coupons';

    protected $fillable = [
        'stripe_coupon_id',
        'code',
        'discount_type',
        'discount_value',
        'duration',
        'max_redemptions',
        'times_redeemed',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_redemptions' => 'integer',
        'times_redeemed' => 'integer',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Check if the coupon is valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        if ($this->max_redemptions !== null && $this->times_redeemed >= $this->max_redemptions) {
            return false;
        }

        return true;
    }
}

