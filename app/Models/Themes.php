<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Themes extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'config',
        'layout',
        'store_id',

    ];

    protected $casts = [
        'config' => 'json',
        'layout' => 'json',
    ];
    public function store()
    {
        return $this->belongsTo(Stores::class);
    }
}
