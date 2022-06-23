<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config;

class SeriesClient extends RestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'Series';

        if ($config = Config::getConfigForService('series', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * Retrieves seriesmetadata for a given series identifier from conntected Opencast
     *
     * @param string series_id Identifier for a Series
     *
     * @return array|boolean response of a series, or false if unable to get
     */
    public function getSeries($series_id)
    {
        $response = $this->opencastApi->series->get($series_id);
        
        if ($response['code'] == 200) {
            return $response['body'];
        }
        return false;
    }

    /**
     * Updates the ACL for a given series in OC Matterhorn
     * 
     * @param string $series_id series identifier
     * @param array $acl_data ACL
     * 
     * @return bool success or not
     */

    public function updateAccesscontrolForSeminar($series_id, $acl_data)
    {
        $response = $this->opencastApi->series->updateAcl($series_id, $acl_data);

        if ($response['code'] == 204) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves episode metadata for a given series
     * identifier from conntected Opencast
     *
     * @return array|boolean response of series, or false if unable to get
     */
    public function getAllSeriesTitle()
    {
        $response = $this->opencastApi->series->getTitles();

        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }
}
