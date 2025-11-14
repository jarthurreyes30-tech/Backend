<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password', // Hashed password
        'role',
        'verification_code',
        'verification_token',
        'expires_at',
        'attempts',
        'resend_count',
        'registration_data', // JSON field for additional data
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'registration_data' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Check if the verification has expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if max attempts reached
     */
    public function hasMaxAttempts()
    {
        return $this->attempts >= 5;
    }

    /**
     * Check if max resends reached
     */
    public function hasMaxResends()
    {
        return $this->resend_count >= 3;
    }

    /**
     * Increment attempts
     */
    public function incrementAttempts()
    {
        $this->increment('attempts');
    }

    /**
     * Increment resend count
     */
    public function incrementResendCount()
    {
        $this->increment('resend_count');
    }
}
