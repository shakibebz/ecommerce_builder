<?php

namespace App\Enums;

enum FrontendInputType: string
{
    case TEXT = 'text';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case TEXTAREA = 'textarea';
    case PRICE = 'price';
    case BOOLEAN = 'boolean';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case GALLERY = 'gallery';
    case MEDIA_IMAGE = 'media_image';
}
