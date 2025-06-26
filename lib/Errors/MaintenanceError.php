<?php

namespace Opencast\Errors;

class MaintenanceError extends Error
{
    function __construct($message = null)
    {
        $message = $message ?? _('Opencast befindet sich derzeit in Wartung');
        parent::__construct($message, 503);
    }
}
