<?php
namespace Opencast\Models\REST;

use Opencast\Models\Config;

class IngestClient extends RestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'IngestClient';

        if ($config = Config::getConfigForService('ingest', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * Creates an empty media package
     *
     * @return string media package in xml format, or false if unable to create
     */
    public function createMediaPackage()
    {
        $response = $this->opencastApi->ingest->createMediaPackage();

        if ($response['code'] == 200) {
            return $response['body'];
        }
        return false;
    }

    /**
     * Add a dublincore episode catalog to a given media package
     *
     * @param string $mediaPackage The media package
     * @param string $dublinCore DublinCore catalog.
     * @param string $flavor DublinCore Flavor
     *
     * @return string augmented media package in xml format, or false if unable to add
     */
    public function addDCCatalog($mediaPackage, $dublinCore, $flavor = '')
    {
        $response = $this->opencastApi->ingest->addDCCatalog($mediaPackage, $dublinCore, $flavor);

        if ($response['code'] == 200) {
            return $response['body'];
        }
        return false;
    }

    /**
     * Schedule an event based on the given media package.
     *
     * @param string $mediaPackage The media package
     * @param string $workflowDefinitionId  Workflow definition id
     * @param string $capabilities Device Capabilities
     *
     * @return boolean whether the event is scheduled or not
     */
    public function schedule($mediaPackage, $workflowDefinitionId = '', $capabilities = '')
    {
        if (!empty($capabilities)) {
            $uri = "/ingest/schedule";

            if (!empty($workflowDefinitionId)) {
                $uri .= "/{$workflowDefinitionId}";
            }

            $query = [
                'mediaPackage' => $mediaPackage,
            ];

            if (!empty($capabilities)) {
                $query['capture.device.names'] = $capabilities;
            }

            $options = $this->ocRestClient->getFormParams($query);
            $response = $this->ocRestClient->performPost($uri, $options);

            return (in_array($response['code'], [200, 201]) !== false);
        } else {
            $response = $this->opencastApi->ingest->schedule($mediaPackage, $workflowDefinitionId);
            return (in_array($response['code'], [200, 201]) !== false);
        }

        return false;
    }
}
