<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class apartment_detail extends Model
{
    protected $table = 'apartment_details';

    protected $primaryKey = 'apartment_id';

    protected $fillable = [
        'owner_id',
        'apartment_description',
        'floorNumber',
        'roomNumber',
        'image',
        'available_from',
        'available_to',
        'governorate',
        'area',
        'price',
        'is_furnished',
        'status',
    ];

    protected $casts = [
        'image' => 'array',
        'available_from' => 'date',
        'available_to' => 'date',
        'price' => 'decimal:2',
        'is_furnished' => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
