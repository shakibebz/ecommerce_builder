<?php

namespace App\Models;


use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

class StoreUserAdmin extends Authenticatable
{
    use HasApiTokens, HasRoles;
    protected $table = 'store_user_admin';
    protected $guard_name = 'store_admin';

    protected $fillable = [
        'store_id',
        'username',
        'firstname',
        'lastname',
        'email',
        'password',
        'is_active',
    ];

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];


    public function store()
    {
        return $this->belongsTo(Stores::class, 'store_id');
    }

    // A convenient "shortcut" relationship to get the tenant
    public function tenant()
    {
        return $this->hasOneThrough(Tenant::class, Stores::class, 'id', 'id', 'store_id', 'tenant_id');
    }
}
