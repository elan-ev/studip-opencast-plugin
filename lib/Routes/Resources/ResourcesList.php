<?php

namespace Opencast\Routes\Resources;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;

use Opencast\Models\Resources;
use Opencast\Models\Helpers;
use Opencast\Models\OCCourseModel;
use Opencast\Models\REST\CaptureAgentAdminClient;
use Opencast\Models\REST\WorkflowClient;

class ResourcesList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $resources = Helpers::getResources();


        $caa_client      = CaptureAgentAdminClient::getInstance();
        $workflow_client = WorkflowClient::getInstance();

        $agents          = $caa_client->getCaptureAgents();
        $this->agents    = $agents;


        foreach ($this->resources as $resource) {
            $assigned_agents = Helpers::getCAforResource($resource['resource_id']);

            if ($assigned_agents) {
                $existing_agent = false;

                foreach ($agents as $key => $agent) {
                    if ($agent->name ==  $assigned_agents['capture_agent']) {
                        unset($agents[$key]);
                        $existing_agent = true;
                    }
                }

                if (!$existing_agent){
                    Helpers::removeCAforResource($resource['resource_id'], $assigned_agents['capture_agent']);
                    $this->flash['messages'] = array('info' => sprintf($this->_("Der Capture Agent %s existiert nicht mehr und wurde entfernt."),$assigned_agents['capture_agent'] ));
                }
            }
        }

        $available_agents = $agents;
        $definitions = $workflow_client->getDefinitions()->definition;

        $allowed_tags = ['schedule', 'schedule-ng'];

        $definitions = array_filter($definitions, function($elem) use ($allowed_tags) {
            foreach ($allowed_tags as $tag) {
                return in_array($tag, $elem->tags->tag) === true
                    || $elem->tags->tag == $tag;
            }
        });

        array_walk($definitions, function(&$elem) {
            $elem = [
                'id'    => $elem->id,
                'title' => $elem->title
            ];
        });

        $assigned_cas = Helpers::getAssignedCAS();

        $workflows = array_filter(
            $workflow_client->getTaggedWorkflowDefinitions(),
            function ($element) {
                return (in_array('schedule', $element['tags']) !== false
                    || in_array('schedule-ng', $element['tags']) !== false)
                    ? $element
                    : false;
            }
        );

        $current_workflow = OCCourseModel::getWorkflowWithCustomCourseID('default_workflow','upload');


        return $this->createResponse(compact('resources', 'available_agents',
            'definitions', 'assigned_cas', 'workflows', 'current_workflow'), $response);

    }
}
