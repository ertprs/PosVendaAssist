<?php

$host = $_SERVER['HTTP_HOST'];
$apiLink = $_REQUEST["apiLink"];	

$apiLink =  str_replace(array("http://","https://"), array("",""), $apiLink);

if(strstr($host, "devel.telecontrol")){	
	$explodedUrl = explode("/", $apiLink);
	$explodedUrl[1] = $explodedUrl[1]."-dev";	
	$apiLink = implode("/", $explodedUrl);	
}
$x = explode("/", $apiLink);
$host = array_shift($x);
$api = array_shift($x);

foreach ($x as $i => $v) {
  $x[$i] = urlencode($v);
}

$apiLink = "http://".$host . "/" . $api . "/" . implode("/", $x);

$curl = curl_init();

curl_setopt_array($curl, array(
	CURLOPT_URL => $apiLink,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => array(
		"cache-control: no-cache",
	),
  ));

$response = curl_exec($curl);
$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

header(sprintf('HTTP/1.0 %s',$http_status));
header('Content-Type: application/json');
echo utf8_encode($response);
