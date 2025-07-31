<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'street',
        'city',
        'region',
        'region_code',
        'region_id',
        'postcode',
        'country_id',
        'is_default',
    ];

}
