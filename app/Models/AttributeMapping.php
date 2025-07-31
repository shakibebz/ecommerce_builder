<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_label',
        'magento_attribute_code',
        'magento_attribute_type',
        'is_mapped',
    ];

    protected $casts = [
        'is_mapped' => 'boolean',
    ];

    // This automatically updates the 'is_mapped' status when the admin saves the form.
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (!empty($model->magento_attribute_code) && !empty($model->magento_attribute_type)) {
                $model->is_mapped = true;
            }
        });
    }
}
