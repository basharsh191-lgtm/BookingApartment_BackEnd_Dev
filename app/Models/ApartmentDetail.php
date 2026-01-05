<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Container\Attributes\Log;
use Illuminate\Database\Eloquent\Model;

class ApartmentDetail extends Model
{
//    protected $table = 'apartment_details';

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
        'governorate_id',
        'city',
        'area',
        'price',
        'free_wifi',
        'status',
        'scheduled_for_deletion',
    ];



    protected $casts = [

        'available_from' => 'date:d-m-Y',
        'available_to' => 'date:d-m-Y',
        'price' => 'decimal:2',
        'free_wifi' => 'boolean',
    ];

public function scopeFilterByGovernorate($query, $governorateId)
    {
        if ($governorateId) {
            return $query->where('governorate_id', $governorateId);
        }
        return $query;
    }
    public function scopeFilterByCity($query,$city)
    {
        if($city)
        {
            return $query->where('city','LIKE',"%".$city."%");
        }
        return $query;
    }
public function scopeAvailableForEntirePeriod($query, ?string $startDate, ?string $endDate)
{
    if (!$startDate || !$endDate) {
        return $query;
    }
    return $query->whereHas('displayPeriods', function ($q) use ($startDate, $endDate) {
            $checkIn = Carbon::parse($startDate)->format('Y-m-d');
            $checkOut = Carbon::parse($endDate)->format('Y-m-d');
            $q->where('display_start_date', '<=', $checkIn)
            ->where('display_end_date', '>=', $checkOut);
    });
}


    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function ratings()
    {
        return $this->hasOne(Rating::class, 'apartment_id');
    }
    public function getAvgRatingAttribute()
    {
        return round($this->ratings()->avg('stars') ?? 0, 2);
    }
    public function favorit()
    {
        return $this->hasMany(favorit::class);
    }
    public function images()
    {
        return $this->hasMany(ApartmentImage::class,'apartment_details_id');
    }
    public function governorate()
    {
    return $this->belongsTo(Province::class,'governorate_id');
    }
    public function displayPeriods()
    {
        return $this->hasMany(DisplayPeriod::class , 'apartment_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'apartment_id');
    }
    /**
     * عند إنشاء شقة، ننشئ فترة معروضة كاملة تلقائياً
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($apartment) {
            $apartment->displayPeriods()->create([
                'display_start_date' => $apartment->available_from,
                'display_end_date' => $apartment->available_to ?? '2200-01-01'
            ]);
        });
    }

}
