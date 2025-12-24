<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Symfony\Component\HttpKernel\Profiler\Profile;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    protected $appends = ['profile_image_url', 'card_image_url'];

    public function getProfileImageUrlAttribute()
    {
        return $this->ProfileImage
            ? asset('storage/' . $this->ProfileImage)
            : null;
    }

    public function getCardImageUrlAttribute()
    {
        return $this->CardImage
            ? asset('storage/' . $this->CardImage)
            : null;
    }
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
        ];
    }
    public function Apartment_detail(): HasMany
    {
        return $this->hasMany(apartmentDetail::class);
    }
     public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    public function rating()
    {
        return $this->hasOne(Rating::class);
    }
    public function favorit()
    {
        return $this->hasMany(Favorit::class);
    }
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
