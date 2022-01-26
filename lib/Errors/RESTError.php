<?php

namespace Opencast\Errors;

use GraphQL\Error\ClientAware;

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
