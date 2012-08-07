<?php


    require_once "OCRestClient.php";
    require_once $this->trails_root.'/models/OCModel.php';

    class SeriesClient extends OCRestClient
    {
        function __construct() {
            if ($config = parent::getConfig('series')) {
                parent::__construct($config['service_url'],
                                    $config['service_user'],
                                    $config['service_password']);
            } else {
                throw new Exception (_("Die Seriesservice Konfiguration wurde nicht im gültigen Format angegeben."));
            }
        }

        /**
         *  getAllSeries() - retrieves all series from conntected Opencast-Matterhorn Core
         *
         *  @return array response all series
         */
        function getAllSeries() {
            $service_url = "/series/series.json";
            if($series = self::getJSON($service_url)){
                return $series->catalogs;
            } else return false;
        }

        /**
         *  getSeries() - retrieves seriesmetadata for a given series identifier from conntected Opencast-Matterhorn Core
         *
         *  @param string series_id Identifier for a Series
         *
         *	@return array response of a series
         */
        function getSeries($series_id) {

            $service_url = "/series/".$series_id.".json";
            if($series = self::getJSON($service_url)){
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

            $service_url = "/series/".$series_id."/dublincore";
            if($seriesDC = self::getXML($service_url)){
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
        function createSeriesForSeminar($course_id) {


            $xml = utf8_encode(OCModel::creatSeriesXML($course_id));

            $acl = utf8_encode(OCModel::createACLXML());


            $post = array('series' => $xml, 'acl' => $acl);


            $rest_end_point = "/series/";
            $uri = $rest_end_point;
            // setting up a curl-handler
            curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);
            curl_setopt($this->ochandler, CURLOPT_POST, true);
            curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $post);


            $response = curl_exec($this->ochandler);
            $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

            if ($httpCode == 201){

                utf8_decode($response);
                $s  = new SimpleXMLElement($response);
                $series_id = $s->xpath('//dcterms:identifier');
                $series_id = $series_id[0][0];

                OCModel::setSeriesforCourse($course_id, $series_id, 'visible', 1);

                return true;
            } else {
                return false;
            }
        }

        /**
         *  getSeriesDublinCore() - retrieves DC Representation for a given series identifier from conntected Opencast-Matterhorn Core
         *
         *  @param string series_id Identifier for a Series
         *
         *  @return success either true or false
         */
        function removeSeries($series_id) {

            $service_url = "/series/".$series_id;
            curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$service_url);
            curl_setopt($this->ochandler, CURLOPT_CUSTOMREQUEST, "DELETE");

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
?>