<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stores extends Model
{
    use HasFactory;

    protected $table = 'stores';

    protected $fillable = [
        'name',
        'owner_id',
        'domain',
        'store_category',
        'registration_date',
        'expiration_date',
        'is_active',
        'code',
    ];

    private $store_name;

    private $owner_id;


    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function admins()
    {
        return $this->hasMany(StoreUserAdmin::class, 'store_id');
    }
    public function themes()
    {
        return $this->hasMany(Themes::class);
    }
}
