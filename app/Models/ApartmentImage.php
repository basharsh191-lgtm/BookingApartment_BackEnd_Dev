<?php

namespace App\Models;

use Illuminate\Container\Attributes\Storage;
use Illuminate\Database\Eloquent\Model;


class ApartmentImage extends Model
{
   protected $guarded = [];
protected $table = 'apartment_imags';
   public function apartment()
   {
    return $this->belongsTo(apartmentDetail::class);
   }

}
