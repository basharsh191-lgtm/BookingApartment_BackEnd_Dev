<?php

namespace App\Models;

use Illuminate\Container\Attributes\Storage;
use Illuminate\Database\Eloquent\Model;


class ApartmentImage extends Model
{
   protected $guarded = [];
   protected $table = 'apartment_imags';
   protected $appends = ['image_url'];

    /**
     *  Accessor: يقوم بتوليد الرابط الكامل للصورة.
     *
     * @return string|null
     */
    public function getImageUrlAttribute()
    {
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : null;
    }

   public function apartment()
   {
    return $this->belongsTo(apartmentDetail::class);
   }

}
