<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class favorit extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function apartment()
    {
        return $this->belongsTo(apartment_detail::class);
    }
}
