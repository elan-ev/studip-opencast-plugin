<?php

use Opencast\Models\OCConfig;
use Opencast\LTI\OpencastLTI;

class SeriesClient extends OCRestClient
{
    static $me;
    public $serviceName = 'Series';

    function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('series', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
        }
    }

    /**
     *  getSeries() - retrieves seriesmetadata for a given series identifier from conntected Opencast-Matterhorn Core
     *
     *  @param string series_id Identifier for a Series
     *
     *  @return array response of a series
     */
    function getSeries($series_id)
    {
        return $this->getJSON('/'.$series_id. '.json');
    }

    /**
     * createSeriesForSeminar - creates an new Series for a given course in OC Matterhorn
     * @param string $course_id  - course identifier
     * @return bool success or not
     */
    function createSeriesForSeminar($course_id)
    {
        $dublinCore = OCSeriesModel::createSeriesDC($course_id);

        $acl = OpencastLTI::generate_standard_acls($course_id);

        $vis = Config::get()->OPENCAST_HIDE_EPISODES
            ? 'invisible'
            : 'visible';

        $post = array(
            'series' => $dublinCore,
            'acl'    => $acl[$vis]->as_xml()
        );

        $res = $this->getXML('/', $post, false, true);

        $string = str_replace('dcterms:', '', $res[0]);
        $xml = simplexml_load_string($string);
        $json = json_decode(json_encode($xml), true);

        if ($res[1] == 201) {
            $new_series = json_decode($res[0]);
            $series_id = $json['identifier'];
            OCSeriesModel::setSeriesforCourse($course_id, 1, $series_id, $vis, 1, time());

            self::updateAccesscontrolForSeminar($series_id, $acl[$vis]->as_xml());

            return true;
        } else {
            return false;
        }
    }


    /**
     * updateAccesscontrolForSeminar - updates the ACL for a given series in OC Matterhorn
     * @param string $series_id  series identifier
     * @param array  $acl_data   ACL
     * @return bool success or not
     */

    function updateAccesscontrolForSeminar($series_id, $acl_data) {

        $post =  array('acl' => $acl_data);
        $res = $this->getXML('/'.$series_id.'/accesscontrol', $post, false, true);

        if ($res[1] == 204){
            return true;
        } else {
            return false;
        }
    }
}
