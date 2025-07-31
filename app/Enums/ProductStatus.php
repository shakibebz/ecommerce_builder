<?php

namespace App\Enums;

enum ProductStatus: string
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Synced = 'synced';
    case SyncFailed = 'sync_failed';
}
