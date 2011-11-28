<?php
	require_once "OCRestClient.php";
	class SchedulerClient extends OCRestClient
	{
		function __construct() {
			if ($config = parent::getConfig('scheduler')) {
				parent::__construct($config['service_url'],
									$config['service_user'],
									$config['service_password']);
			} else {
				throw new Exception (_("Die Schedulerservice Konfiguration wurde nicht im gültigen Format angegeben."));
			}
		}
	}
?>