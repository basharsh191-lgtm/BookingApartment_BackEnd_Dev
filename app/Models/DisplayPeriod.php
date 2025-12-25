<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisplayPeriod extends Model
{
    protected $fillable = [
        'apartment_id',
        'display_start_date',
        'display_end_date'
    ];

    protected $casts = [
        'display_start_date' => 'date',
        'display_end_date' => 'date',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(ApartmentDetail::class);
    }

}
