<?php
	

	$ip = $_SERVER['REMOTE_ADDR'];

	$ipArray = array("ip"=> $ip);

	$ipjson = json_encode($ipArray);

	echo $ipjson;
	

?>