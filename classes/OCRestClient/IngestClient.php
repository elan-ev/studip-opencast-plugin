<?php

use Opencast\Models\OCConfig;

class IngestClient extends OCRestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'IngestClient';

        if ($config = OCConfig::getConfigForService('ingest', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     *  createMediaPackage - Creates an empty media package
     *
     * @return $mediapackage
     */
    public function createMediaPackage()
    {
        $service_url = "/createMediaPackage";
        if ($mediapackage = self::getXML($service_url)) {
            return $mediapackage;
        } else return false;
    }

    /**
     *  addDCCatalog - Add a dublincore episode catalog to a given media package using an url
     *
     * @param $mediapackage
     * @param $dublincore
     * @param $flavor
     *
     * @return $mediapackage - the augmented mediapackage
     */
    public function addDCCatalog($mediaPackage, $dublinCore, $flavor = null)
    {
        $service_url = "/addDCCatalog";
        $data        = [
            'mediaPackage' => $mediaPackage,
            'dublinCore'   => $dublinCore
        ];
        if ($flavor != null) {
            $data['flavor'] = $flavor;
        }
        if ($mediapackage = $this->getXML($service_url, $data, false)) {
            return $mediapackage;
        }
    }

    /**
     *  ingest - Ingest the completed media package into the system, retrieving all URL-referenced files
     *
     * @param string $mediapackage
     * @param $workFlowDefinitionID
     *
     * @return $mediapackage
     */
    public function ingest($mediaPackage, $workFlowDefinitionID, $addendum = '')
    {
        $service_url               = "/ingest/" . $workFlowDefinitionID . $addendum;
        $mediaPackageParsed        = new SimpleXMLElement($mediaPackage);
        $mediaPackageXMLAttributes = $mediaPackageParsed->attributes();

        $data = [
            'mediaPackage'         => $mediaPackage,
            'workflowDefinitionId' => $workFlowDefinitionID
        ];
        if ($mediapackage = $this->getXML($service_url, $data, false)) {
            return $mediapackage;
        }
    }

    /**
     * Add a track to the passed media-package
     *
     * @param string $mediaPackage
     * @param string $trackURI
     * @param string $flavor
     */
    public function addTrack($mediaPackage, $trackURI, $flavor)
    {
        $data = [
            'url'          => $trackURI,
            'flavor'       => $flavor,
            'mediaPackage' => $mediaPackage,
            'tags'         => ''
        ];

        if ($res = $this->getXML('/addTrack', http_build_query($data), false, false, true)) {
            return $res;
        }
    }

    public function schedule($media_package, $capabilities,  $publishLive, $worklow_definition = null)
    {
        $uri = '/schedule';

        if ($worklow_definition != null) {
            $uri .= '/' . $worklow_definition;
        }

        $query = [
            'mediaPackage'         => $media_package,
            'capture.device.names' => $capabilities,
        ];

        if ($publishLive) {
            $query['publishLive'] = 'True';
        }

        $res = $this->getXML($uri, http_build_query($query), false, true, true);
        if ($res) {
            return $res;
        } else {
            return false;
        }
    }
}
