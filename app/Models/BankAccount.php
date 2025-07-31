<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;
    protected $table = 'accounts';

    protected $fillable = ['tenant_id', 'balance'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }
}
