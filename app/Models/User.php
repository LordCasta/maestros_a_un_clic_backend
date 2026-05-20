<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ClientProfile;
use App\Models\ProfessionalProfile;
use App\Models\Favorite;
use App\Models\Booking;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'latitude',
        'longitude',
        'commune',
        'is_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
        ];
    }

    // Relationships
    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function professionalProfile(): HasOne
    {
        return $this->hasOne(ProfessionalProfile::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'client_id');
    }

    public function bookingsAsClient(): HasMany
    {
        return $this->hasMany(Booking::class, 'client_id');
    }

    public function bookingsAsProfessional(): HasMany
    {
        return $this->hasMany(Booking::class, 'professional_id');
    }

    // Helpers
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isProfessional(): bool
    {
        return $this->role === 'professional';
    }
}
