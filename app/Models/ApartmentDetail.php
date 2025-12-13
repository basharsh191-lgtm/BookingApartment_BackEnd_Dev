<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class apartmentDetail extends Model
{
    protected $table = 'apartment_details';

   // protected $primaryKey = 'apartment_id';

    protected $fillable = [
        'owner_id',
        'apartment_description',
        'floorNumber',
        'roomNumber',
        'image',
        'available_from',
        'available_to',
        'governorate',
        'city',
        'area',
        'price',
        'free_wifi',
        'status',
    ];

    protected $casts = [
        'image' => 'array',
        'available_from' => 'date',
        'available_to' => 'date',
        'price' => 'decimal:2',
        'free_wifi' => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function rating()
    {
        return $this->hasMany(Rating::class);
    }
    public function favorit()
    {
        return $this->hasMany(favorit::class);
    }
}
