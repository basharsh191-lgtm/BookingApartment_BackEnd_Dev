<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    protected $appends = ['profile_image_url', 'card_image_url'];

    /**
     * دالة لإنشاء رابط الصورة الشخصية
     */
    public function getProfileImageUrlAttribute()
    {
        return $this->getImageUrl($this->ProfileImage);
    }

    /**
     * دالة لإنشاء رابط صورة البطاقة
     */
    public function getCardImageUrlAttribute()
    {
        return $this->getImageUrl($this->CardImage);
    }

    /**
     * دالة مساعدة لإنشاء URL للصور مع السيرفر المحدد
     */
    private function getImageUrl($imagePath)
    {
        if (!$imagePath) {
            return null;
        }

        // الحصول على الرابط الأساسي من config
        $baseUrl = config('app.url', 'http://10.0.2.2:8000');
        // إذا كان المسار يحتوي على storage/ بالفعل
        if (strpos($imagePath, 'storage/') === 0) {
            $relativePath = substr($imagePath, 8);
        } else {
            $relativePath = $imagePath;
        }
        // بناء الرابط الكامل
        return rtrim($baseUrl, '/') . '/storage/' . ltrim($relativePath, '/');
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
        return $this->hasMany(ApartmentDetail::class,'apartment_id');
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
        return $this->hasMany(Booking::class,'tenant_id');
    }        public function Message()
    {
        return $this->hasMany(Message::class);
    }
}
