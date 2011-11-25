<?php
	require_once "OCRestClient.php";
	class SearchClient extends OCRestClient
	{
		function __construct($config) {
			if (is_array($config)) {
				parent::__construct($config['service_url'],
									$config['service_user'],
									$config['service_password']);
			} else {
				throw new Exception (_("Die Searchservice Konfiguration wurde nicht im gültigen Format angegeben."));
			}
		}

		function getEpisode($series_id) {
			$rest_end_point = "/search/series.json?id=";
			curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point.$series_id.'&episodes=true&series=true');
			$response = curl_exec($this->ochandler);
			$httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
			if ($httpCode == 404){
			    return false;
			} else {
			     return json_decode($response);
			}
		}
	}
?>