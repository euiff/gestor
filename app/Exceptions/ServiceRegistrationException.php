<?php

namespace App\Exceptions;

use Exception;

class ServiceRegistrationException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}