<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Tenant extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'tenant';

    protected $fillable = [
        'tname',
        'email',
        'phone',
        'password',
        'address',
        'national_code',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'updated_at',
        'created_at'
    ];

    public function stores()
    {
        return $this->hasMany(Stores::class, 'owner_id');
    }
    public function account()
    {
        return $this->hasOne(BankAccount::class, 'tenant_id');
    }
}
