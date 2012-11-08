<?php


    require_once "OCRestClient.php";
    require_once $this->trails_root.'/models/OCModel.php';
    require_once $this->trails_root.'/models/OCSeriesModel.php';

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


            $dublinCore = utf8_encode(OCSeriesModel::createSeriesDC($course_id));
            
            $ACLData = array( 'ROLE_ADMIN' => array(
                                                'read' => 'true',
                                                'write' => 'true',
                                                'analyze' => 'true'),
                               'ROLE_ANONYMOUS' => array(
                                                'read' => 'true'
                               )
                        );
            $ACL = OCSeriesModel::createSeriesACL($ACLData); 

            $post = array('series' => $dublinCore,
                        'acl' => $ACL);

            $res = self::getXML('/series', $post, false, true);
            $string = str_replace('dcterms:', '', $res[0]);
            $xml = simplexml_load_string($string);
            $json = json_decode(json_encode($xml), true);

            if ($res[1] == 201){

                $new_series = json_decode($res[0]);
                $series_id = $json['identifier'];
                OCSeriesModel::setSeriesforCourse($course_id, $series_id, 'visible', 1);

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
