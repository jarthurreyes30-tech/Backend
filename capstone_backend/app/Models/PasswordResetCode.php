<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PasswordResetCode extends Model
{
    protected $fillable = [
        'email',
        'token_hash',
        'attempts',
        'ip',
        'expires_at',
        'used',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * Check if the reset code is expired
     */
    public function isExpired(): bool
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }

    /**
     * Check if the reset code has been used
     */
    public function isUsed(): bool
    {
        return $this->used === true;
    }

    /**
     * Check if maximum attempts reached
     */
    public function hasMaxAttempts(): bool
    {
        return $this->attempts >= 5;
    }

    /**
     * Increment attempt counter
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Mark as used
     */
    public function markAsUsed(): void
    {
        $this->update([
            'used' => true,
            'used_at' => Carbon::now(),
        ]);
    }

    /**
     * Scope for active (non-expired, non-used) codes
     */
    public function scopeActive($query)
    {
        return $query->where('used', false)
                     ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope for specific email
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }
}
