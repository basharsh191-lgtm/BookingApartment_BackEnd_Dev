<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{

    protected $fillable = ['mobile', 'otp', 'expires_at'];
    public $timestamps = true;
}
