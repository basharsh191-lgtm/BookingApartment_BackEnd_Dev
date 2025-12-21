<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class apartmentDetail extends Model
{
    protected $table = 'apartment_details';

    protected $attributes = [
        'scheduled_for_deletion' => false,
    ];
    protected $appends = ['avg_rating'];
    protected $fillable = [
        'owner_id',
        'apartment_description',
        'floorNumber',
        'roomNumber',
        'available_from',
        'available_to',
        'governorate',
        'city',
        'area',
        'price',
        'free_wifi',
        'status',
        'scheduled_for_deletion',
    ];



    protected $casts = [
        'available_from' => 'date',
        'available_to' => 'date',
        'price' => 'decimal:2',
        'free_wifi' => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function ratings(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Rating::class, 'apartment_id');
    }
    public function favorit()
    {
        return $this->hasMany(favorit::class);
    }
    public function getAvgRatingAttribute()
    {
        return $this->ratings()->avg('stars') ?? 3;
    }
    public function images()
    {
        return $this->hasMany(ApartmentImage::class,'apartment_details_id');
    }
}
