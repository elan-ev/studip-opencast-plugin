<?php
	require_once "OCRestClient.php";
	class SeriesClient extends OCRestClient
	{
		function __construct($config) {
			if (is_array($config)) {
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
		 *	@return array response all series
		 */
		function getAllSeries() {
			$service_url = "/series/all.json";
			if($series = self::getJSON($service_url)){
				return $series->seriesList;
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
				return $series->series;
			} else return false;
		}
		/**
		 *  getSeriesDublinCore() - retrieves DC Representation for a given series identifier from conntected Opencast-Matterhorn Core
		 *  
		 *  @param string series_id Identifier for a Series	
		 *	
		 *	@return string DC representation of a series
		 */
		function getSeriesDublinCore($series_id) {
			
			$service_url = "/series/".$series_id."/dublincore";
			if($seriesDC = self::getXML($service_url)){
				// dublincore representation is returned in XML
		     	//$seriesDC = simplexml_load_string($seriesDC);
				return $seriesDC;
				
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