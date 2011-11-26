<?php
    class OCRestClient
    {
		protected $matterhorn_base_url;
		protected $username;
		protected $password;
		
		function __construct($matterhorn_base_url = null, $username = null, $password = null){
	        $this->matterhorn_base_url = $matterhorn_base_url;
	        $this->username = $username;
	        $this->password = $password;

	        // setting up a curl-handler
	        $this->ochandler = curl_init();
	        curl_setopt($this->ochandler, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($this->ochandler, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
	        curl_setopt($this->ochandler, CURLOPT_USERPWD, 'matterhorn_system_account'.':'.'CHANGE_ME');
	        curl_setopt($this->ochandler, CURLOPT_ENCODING, "ISO-8859-1");
	        curl_setopt($this->ochandler, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest"));
		}
		
		function getConfig($service_type) {
			if(isset($service_type)) {
		        $stmt = DBManager::get()->prepare("SELECT * FROM `oc_config` WHERE service_type = ?");

		        $stmt->execute(array($service_type));
		        return $stmt->fetch();
		 	} else {
				throw new Exception(_("Es wurde kein Servicetyp angegeben."));
			}
		}

		
		function setConfig() {
			$stmt = DBManager::get()->prepare("REPLACE INTO `oc_config` (service_type, service_url, service_user, service_password) VALUES (?,?,?,?)");
			return $stmt->execute(array($service_type, $service_url, $service_user, $service_password));
			
		}
		
		function getJSON($service_url) {
			if(isset($service_url)) {
				curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$service_url);
				$response = curl_exec($this->ochandler);
				$httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
				if ($httpCode == 404){
			    	return false;
				} else {
					return json_decode($response);
				}
			} else {
				throw new Exception(_("Es wurde keine Service URL angegben"));
			}
			
		}
		
		function getXML($service_url) {
			if(isset($service_url)) {
				curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$service_url);
				$response = curl_exec($this->ochandler);
				$httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
				if ($httpCode == 404){
			    	return false;
				} else {
					return $response;
				}
			} else {
				throw new Exception(_("Es wurde keine Service URL angegben"));
			}
		}
    
    }
?>
