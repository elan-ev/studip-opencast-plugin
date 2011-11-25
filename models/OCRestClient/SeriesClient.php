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
			$rest_end_point = "/series/all.json";
			curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point);
			$response = curl_exec($this->ochandler);
			$httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
			if ($httpCode == 404){
		    	return false;
			} else {
				$series = json_decode($response);
				return $series->seriesList;
			}
		}
		/**
		 *  getSeries() - retrieves all series from conntected Opencast-Matterhorn Core
		 *  
		 *  @param string series_id Identifier for a Series	
		 *	
		 *	@return array response of a series
		 */
		function getSeries($series_id) {
			$rest_end_point = "/series/";
			curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point.$series_id.'.json');
			$response = curl_exec($this->ochandler);
			$httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
			if ($httpCode == 404){
		    	return false;
			} else {
				$serie = json_decode($response); 
		     	return $serie->series;
			}
		}
		
		function getSeriesDublinCore($series_id) {
			$rest_end_point = "/series/";
			curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point.$series_id.'/dublincore');
			$response = curl_exec($this->ochandler);
			$httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
			if ($httpCode == 404){
		    	return false;
			} else {
				
				// dublincore representation is returned in XML
		     	$xml = simplexml_load_string($response);
				return $xml;
			}
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