<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUserAdmin extends Model
{

    protected $table = 'tenant_user_admins';

    protected $fillable = [
        'tenant_id',
        'username',
        'firstname',
        'lastname',
        'email',
        'password',
        'is_active',
    ];

    public function tenant()
    {
        return $this->belongsTo('App\Models\Tenant', 'tenant_id');
    }
}
