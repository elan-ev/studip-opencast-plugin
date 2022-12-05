<?php

namespace Opencast\Errors;

class RESTError extends \Exception implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory()
    {
        return 'Opencast REST API';
    }
}
