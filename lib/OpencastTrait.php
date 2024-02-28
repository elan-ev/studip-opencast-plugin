<?php

namespace Opencast;

use Opencast\Errors\Error;
use Opencast\Errors\UnprocessableEntityException;

Trait OpencastTrait
{

    public function getRequestData($request, $required_fields = [])
    {
        $body = (string) $request->getBody();
        if ('' === $body) {
            //throw new UnprocessableEntityException('Empty request', 500);
            return false;
        }
        $result = json_decode($body, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new UnprocessableEntityException('Error while parsing json: '
                . json_last_error_msg() . ', Body: ' . $body, 500);
        }

        foreach ($required_fields as $field) {
            if (!isset($result[$field])) {
                throw new Error('missing field in json-data: ' . $field, 422);
            }
        }

        return $result;
    }

    public function createResponse($data, $response)
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }

    public function createEmptyResponse($response)
    {
        return $this->createResponse(['data' => []], $response);
    }

    public function transform($entry, $relations = [])
    {
        $data = [
            'type'       => strtolower((new \ReflectionClass($entry))->getShortName()),
            'id'         => $entry->getId(),
            'attributes' => $entry->toArray()
        ];

        if (!empty($relationships = $entry->getRelationships())) {
            $data['relationships'] = $relationships;
        }

        return $data;
    }

    public function toArray($data, $relations = [])
    {
        $result = [];

        foreach ($data as $entry) {
            $result[] = $this->transform($entry);
        }

        return ['data' => $result];
    }
}
