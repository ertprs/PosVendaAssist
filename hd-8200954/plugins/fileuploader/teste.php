<?php


$file = curl_file_create(realpath("chatonline.png"));

$curl = curl_init();

curl_setopt_array($curl, array(
//        CURLOPT_URL => "http://api2.telecontrol.com.br/tdocs/document",
CURLOPT_URL => "http://novodevel.telecontrol.com.br/~anderson/PosVendaAssist/fileuploader/receiver.php",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_POST => 1,
CURLOPT_POSTFIELDS => array("file" => $file),
CURLOPT_HTTPHEADER => array(
	"access-application-key: 32e1ea7c54c0d7c144bc3d3045d8309a5b137af9",
	"access-env: PRODUCTION",
),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

file_put_contents("/tmp/log.log", "TESTE"."\n",FILE_APPEND);
file_put_contents("/tmp/log.log", print_r($response,1)."\n",FILE_APPEND);

echo "LASK";
