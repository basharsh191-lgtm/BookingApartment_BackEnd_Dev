<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apartment_detail extends Model
{
    protected $table = 'department_details';

    protected $primaryKey = 'department_id';

    protected $fillable = [
        'owner_id',
        'department_description',
        'image',
        'start_date',
        'end_date',
        'governorate',
        'area',
        'price',
    ];

    protected $casts = [
        'image' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'area' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
