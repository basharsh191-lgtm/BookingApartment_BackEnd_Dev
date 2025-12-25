<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
protected $guarded=[];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function apartment()
    {
        return $this->belongsTo(ApartmentDetail::class,'apartment_id');
    }
}
