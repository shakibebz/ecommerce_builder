<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryMapping extends Model
{

    protected $fillable = [
        'source_name',
        'magento_category_id',
        'is_mapped',
    ];

    protected $casts = [
        'is_mapped' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // If the Magento ID has been filled in, the mapping is complete.
            if (!empty($model->magento_category_id)) {
                $model->is_mapped = true;
            }
        });
    }
}
