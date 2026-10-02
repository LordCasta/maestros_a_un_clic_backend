<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\VerificationStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_path',
        'role',
        'commune_id',
        'address',
        'latitude',
        'longitude',
        'verification_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'verification_status' => VerificationStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'rating_avg' => 'float',
            'rating_count' => 'integer',
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'blocked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relaciones

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function professionalProfile(): HasOne
    {
        return $this->hasOne(ProfessionalProfile::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function latestVerificationRequest(): HasOne
    {
        return $this->hasOne(VerificationRequest::class)->latestOfMany();
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

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    // Scopes

    public function scopeProfessionals(Builder $query): void
    {
        $query->where('role', Role::Professional);
    }

    /**
     * Profesionales que se pueden mostrar en búsquedas públicas:
     * verificados y no bloqueados (HU008, HU038).
     */
    public function scopePubliclyListed(Builder $query): void
    {
        $query->professionals()
            ->where('verification_status', VerificationStatus::Approved)
            ->whereNull('blocked_at');
    }

    // Helpers

    public function isClient(): bool
    {
        return $this->role === Role::Client;
    }

    public function isProfessional(): bool
    {
        return $this->role === Role::Professional;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::Approved;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }
}
