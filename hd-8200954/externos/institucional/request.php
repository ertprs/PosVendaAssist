<?php

$host = $_SERVER['HTTP_HOST'];
$env = "PRODUCTION";

if(strstr($host, "devel.telecontrol") OR strstr($host, "homologacao.telecontrol")){
  $env = "HOMOLOGATION";
}

$application_keys = array(
  "black" => array(
    "HOMOLOGATION" => "4d921c21fff0114ae3fbbea01b56d9b196707d17",
    "PRODUCTION" => "7ccecf5d519564746e606ab0be3ece8400122ab2"
  ),
  "tecvoz" => array(
    "HOMOLOGATION" => "06201f24cc628278d464d276026f226411437edc",
    "PRODUCTION" => "b56561417a59a786dfff6c6f19a72e4594898d62"
  ),
  "cadence" => array(
    "HOMOLOGATION" => "fb3619b45559a2f789c2dfb7eaf822e54586b146",
    "PRODUCTION" => "a3c8fe2d36d5401f07642380ea8afcea112c0a9c"
  ),
  "mallory" => array(
    "HOMOLOGATION" => "92473c8a74874deb2739be6c3ae2bf5c89b54bb4",
    "PRODUCTION" => "770e2fa1fde0c11652dee165b978cf46f9a031bd"
  ),
  "precision" => array(
    "HOMOLOGATION" => "4b4e499d1cd644ba5e4231caf4a949a2b566cbc7",
    "PRODUCTION" => "0b0b1067fd0ba17a7564bb15a7cc74b1fdf7dd13"
	),
"cuisinart" => array(
    "PRODUCTION" => "7b4f033bdc66ed0c425605bc8405b8551618dd5e"
),
"ingco" => array(
    "PRODUCTION" => "90b876e0ac790fc47258cbb9132beb444495460c"
  ),
  "lepono" => array(
    "HOMOLOGATION" => "c99d8e247a29ecb6d0d21812bbb8f797d40ac135",
    "PRODUCTION" => "908a72ffdaf4303cef83376d17432f09f1635cf3"
  ),
  "mq" => array(
    "HOMOLOGATION" => "eb268bf93a90e3551384541eb6eb669387153959",
    "PRODUCTION" => "ea3d39a1ae9613f84e69d6ecfe1d61c097e66d74"
  ),
  "fluidra" => array(
    "HOMOLOGATION" => "2f7aee16f4c4160dd13cb7179180e4da09202e75",
    "PRODUCTION"   => "d77b9214cdff814f25486f9a11a54d29b8c59f77"
  )
);

$application_key = $application_keys[$_POST['fabrica']][$env];

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api2.telecontrol.com.br/institucional/".$_POST['url'],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 300,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "access-application-key: ".$application_key,
    "access-env: ".$env
  ),
));
$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}
?>
