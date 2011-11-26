<?php
	require_once "SearchClient.php";
	
	$search = new SearchClient($matterhorn_base_url = "test", $username = "test2", $password = "test3");
	var_dump($search);
	try {
		$search->getConfig();
	} catch (Exception $e) {
		echo $e->getMessage();
	}
?>