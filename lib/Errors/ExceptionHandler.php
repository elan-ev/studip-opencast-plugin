<?php

namespace Opencast\Errors;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as ResponseInterface;
use Psr\Log\LoggerInterface;
use Opencast\VersionHelper;

/**
 * Dieser spezielle Exception Handler wird in der Slim-Applikation
 * für alle JSON-API-Routen installiert und sorgt dafür, dass auch
 * evtl. Fehler JSON-API-kompatibel geliefert werden.
 */
class ExceptionHandler
{
    /**
     * Diese Methode wird aufgerufen, sobald es zu einer Exception
     * kam, und generiert eine entsprechende JSON-API-spezifische Response.
     */
    public function __invoke(
        Request $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
        ?LoggerInterface $logger = null
    ): ResponseInterface {
        if ($exception instanceof Error) {
            $httpCode = $exception->getCode();
            $errors = new ErrorCollection();

            if (!$displayErrorDetails) {
                $exception->clearDetails();
            }

            $errors->add($exception);
        } elseif ($exception instanceof \Slim\Exception\HttpException) {
            $httpCode = (int) $exception->getCode();
            if ($httpCode === 0 && method_exists($exception, 'getStatusCode')) {
                $httpCode = (int) $exception->getStatusCode();
            }

            $details = $displayErrorDetails ? (string) $exception : null;

            $errors = new ErrorCollection();
            $errors->add(new Error($exception->getMessage(), $httpCode, $details));
        } else {
            // Log always php exceptions
            error_log($exception);

            $httpCode = 500;
            $details = null;

            $message = $exception->getMessage();

            if ($displayErrorDetails) {
                $details = (string) $exception;
            }

            $errors = new ErrorCollection();
            $errors->add(new Error($message, $httpCode, $details));
        }

        $response = VersionHelper::createResponse();

        if (!empty($errors)) {
            $response->getBody()->write($errors->json());
            $response = $response->withHeader(
                "Content-Type",
                "application/vnd.api+json"
            );
        }

        return $response->withStatus($httpCode);
    }
}
