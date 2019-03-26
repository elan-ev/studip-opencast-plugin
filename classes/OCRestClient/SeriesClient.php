<?php

class SeriesClient extends OCRestClient
{
    static $me;
    public $serviceName = 'Series';

    function __construct($config_id = 1)
    {
        if ($config = parent::getConfig('series', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
        }
    }

    /**
     *  getAllSeries() - retrieves all series from connected Opencast-Matterhorn Core
     *
     *  @return array response all series
     */
    function getAllSeries()
    {
        $cache = StudipCacheFactory::getCache();
        $cache_key = 'oc_allseries';
        $all_series = $cache->read($cache_key);

        $count = 100;

        if($all_series === false) {
            $service_url = "/series.json?count=".$count;

            if($series = $this->getJSON($service_url)){
                $catalog = $series->catalogs;
                $offset = intval(ceil($series->totalCount / $count));
                for($i = 1; $i < $offset; $i++) {
                    $additional_series = $this->getSeriesOffset($count, $i);
                    if($additional_series){
                        $catalog = array_merge($catalog,$additional_series);
                    }
                }
                $cache->write($cache_key, serialize($catalog), 7200);
                return $catalog;
            } else return false;
        } else return unserialize($all_series);
    }

    /**
     *  getAllSeries() - retrieves all series for a given offset from connected Opencast-Matterhorn Core
     *
     *  @param int count maximal number of series that should be returned
     *  @param int startpage offset
     *
     *  @return array response all series for given offset
     */
     function getSeriesOffset($count, $startpage) {
        $service_url = "/series.json?count=".$count."&startPage=".$startpage;

        if($series = $this->getJSON($service_url)){
            return $series->catalogs;
        } else return false;
    }

    // todo
    function getOneSeries($seriesID)
    {
            return $this->getJSON('/'.$seriesID. '.json');
    }

    /**
     *  getSeries() - retrieves seriesmetadata for a given series identifier from conntected Opencast-Matterhorn Core
     *
     *  @param string series_id Identifier for a Series
     *
     *  @return array response of a series
     */
    function getSeries($series_id) {

        $service_url = "/".$series_id.".json";
        if($series = $this->getJSON($service_url)){
            return $series;
        } else return false;
    }

    /**
     *  getSeriesDublinCore() - retrieves DC Representation for a given series identifier from conntected Opencast-Matterhorn Core
     *
     *  @param string series_id Identifier for a Series
     *
     *  @return string DC representation of a series
     */
    function getSeriesDublinCore($series_id) {

        $service_url = "/".$series_id."/dublincore";
        if($seriesDC = $this->getXML($service_url)){
            // dublincore representation is returned in XML
            //$seriesDC = simplexml_load_string($seriesDC);
            return $seriesDC;

        } else return false;
    }


    /**
     * createSeriesForSeminar - creates an new Series for a given course in OC Matterhorn
     * @param string $course_id  - course identifier
     * @return bool success or not
     */
    function createSeriesForSeminar($course_id)
    {
        $dublinCore = studip_utf8encode(OCSeriesModel::createSeriesDC($course_id));

        $acl = OpencastLTI::generate_standard_acls($course_id);

        $post = array(
            'series' => $dublinCore,
            'acl'    => $acl['visible']->as_xml()
        );

        $res = $this->getXML('/', $post, false, true);

        $string = str_replace('dcterms:', '', $res[0]);
        $xml = simplexml_load_string($string);
        $json = json_decode(json_encode($xml), true);

        if ($res[1] == 201) {
            $new_series = json_decode($res[0]);
            $series_id = $json['identifier'];
            OCSeriesModel::setSeriesforCourse($course_id, $series_id, 'visible', 1, time());

            self::updateAccescontrolForSeminar($series_id, $acl['visible']->as_xml());

            return true;
        } else {
            return false;
        }
    }


    /**
     * updateAccescontrolForSeminar - updates the ACL for a given series in OC Matterhorn
     * @param string $series_id  - series identifier
     * @param array  $acl_data   -studip_utf8encoded ACL
     * @return bool success or not
     */

    function updateAccescontrolForSeminar($series_id, $acl_data) {

        $post =  array('acl' => $acl_data);
        $res = $this->getXML('/'.$series_id.'/accesscontrol', $post, false, true);

        if ($res[1] == 204){
            return true;
        } else {
            return false;
        }
    }


    /**
     *  removeSeries() - removes a series for a given identifier from the Opencast-Matterhorn Core
     *
     *  @param string series_id Identifier for a Series
     *
     *  @return success either true or false
     */
    function removeSeries($series_id) {

        $service_url = "/".$series_id;
        curl_setopt($this->ochandler,CURLOPT_URL,$this->base_url.$service_url);
        curl_setopt($this->ochandler, CURLOPT_CUSTOMREQUEST, "DELETE");
        //TODO �ber REST Classe laufen lassen, getXML, getJSON...
        $response = curl_exec($this->ochandler);
        $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
        if($httpCode == 204){
            return true;
        } else return false;
    }


    // static functions...
    static function storeAllSeries($series_id) {
        $stmt = DBManager::get()->prepare("SELECT * FROM `oc_series` WHERE series_id = ?");
        $stmt->execute(array($series_id));
        if(!$stmt->fetch()) {
            $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_series (series_id)
                VALUES (?)");
            return $stmt->execute(array($series_id));
        }
        else return false;
    }
}
