<?php

namespace Opencast\GraphQL;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

class GraphQL extends OpencastController
{
    use OpencastTrait;

    private function setResolvers($resolvers)
    {
        \GraphQL\Executor\Executor::setDefaultFieldResolver(function ($source, $args, $context, \GraphQL\Type\Definition\ResolveInfo $info) use ($resolvers) {
            $fieldName = $info->fieldName;

            if (is_null($fieldName)) {
                throw new \Exception('Could not get $fieldName from ResolveInfo');
            }

            if (is_null($info->parentType)) {
                throw new \Exception('Could not get $parentType from ResolveInfo');
            }

            $parentTypeName = $info->parentType->name;

            if (isset($resolvers[$parentTypeName])) {
                $resolver = $resolvers[$parentTypeName];

                if (is_array($resolver)) {
                    if (array_key_exists($fieldName, $resolver)) {
                        $value = $resolver[$fieldName];

                        return is_callable($value) ? $value($source, $args, $context, $info) : $value;
                    }
                }

                if (is_object($resolver)) {
                    if (isset($resolver->{$fieldName})) {
                        $value = $resolver->{$fieldName};

                        return is_callable($value) ? $value($source, $args, $context, $info) : $value;
                    }
                }
            }

            return \GraphQL\Executor\Executor::defaultFieldResolver($source, $args, $context, $info);
        });
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $this->setResolvers(include 'resolvers.php');
        $schema = \GraphQL\Utils\BuildSchema::build(file_get_contents(__DIR__ . '/schema.graphql'));

        $body           = (string)$request->getBody();
        $input          = json_decode($body, true);
        $query          = $input['query'];
        $variableValues = $input['variables'] ?? null;

        $context = [
            'user_id'   => $GLOBALS['user']->id
        ];


        $result = \GraphQL\GraphQL::executeQuery($schema, $query, null, null, $variableValues);

        $debug = \GraphQL\Error\DebugFlag::INCLUDE_DEBUG_MESSAGE
            | \GraphQL\Error\DebugFlag::INCLUDE_TRACE;

        return $this->createResponse($result->toArray(
            $debug
        ), $response);
    }
}
