<?php

namespace Opencast\Middlewares\REST;

use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ConnectionMiddlewares
{
    /**
     * Returns a Guzzle middleware that retries failed HTTP requests.
     *
     * Retries requests on connection exceptions or 5xx server errors, using exponential backoff.
     * The number of retries can be configured via the $limit parameter (default: 3).
     *
     * @param int $limit Maximum number of retry attempts (default: 3).
     * @return callable The configured Guzzle retry middleware.
     */
    public static function failedRequestsRetry(int $limit = 3)
    {
        return Middleware::retry(
            function (
                $retries,
                RequestInterface $request,
                ResponseInterface $response = null,
                \Exception $exception = null
            ) use ($limit) {
                // Retry on connection exceptions or 5xx server errors, up to 3 times by default or the limit passed.
                if ($retries >= $limit) {
                    return false;
                }
                if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                    return true;
                }
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }
                return false;
            },
            function ($retries) {
                // Exponential backoff: 1000ms, 2000ms, 4000ms, ...
                return 1000 * pow(2, $retries - 1);
            }
        );
    }
}
