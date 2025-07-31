<?php

namespace App\Services\Notifications\Exceptions;

class AllProvidersFailedException extends \Exception
{
    // Custom exception thrown when all providers in the failover chain have failed.
}
