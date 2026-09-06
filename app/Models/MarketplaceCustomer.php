<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class MarketplaceCustomer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'default_notes',
        'marketing_consent',
        'otp_hash',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_hash',
    ];

    protected function casts(): array
    {
        return [
            'marketing_consent' => 'boolean',
            'otp_expires_at' => 'datetime',
        ];
    }
}
