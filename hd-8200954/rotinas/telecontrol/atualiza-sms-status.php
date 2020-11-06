<?php
include_once dirname(__FILE__) .'/../../dbconfig.php';
include_once dirname(__FILE__) .'/../../includes/dbconnect-inc.php';
include_once dirname(__FILE__) .'/../../class/sms/sms.class.php';

if (!empty($argv[1])) {
	$fabrica = $argv[1];
	$cond = " AND fabrica = $fabrica ";
}
global $_serverEnvironment;

$sql = "SELECT sms, fabrica, destinatario, texto_sms FROM tbl_sms WHERE status_sms = 'Erro na Comtele' $cond ORDER BY fabrica";

$resx = pg_query($con, $sql); 

for($i=0; $i< pg_num_rows($resx);$i++) {
	$sms_id       = pg_fetch_result($resx, $i, 'sms'); 
	$fabrica      = pg_fetch_result($resx, $i, 'fabrica'); 
	$destinatario = pg_fetch_result($resx, $i, 'destinatario'); 
	$texto_sms    = pg_fetch_result($resx, $i, 'texto_sms'); 
	$api          = new SimpleREST();

	if($_serverEnvironment == 'development') {
		$key = '02e85ac1-bbcf-4d00-acf4-678087245444';	
	}else{
		$sqlKey = "SELECT api_secret_key_sms FROM tbl_fabrica WHERE fabrica = $fabrica LIMIT 1";
		$resKey = pg_query($con, $sqlKey);
		$key    = pg_fetch_result($resKey, 0, 'api_secret_key_sms');
	}

	$postData = array(
		"sender"    => $sms_id,
		"receivers" => $destinatario,
		"content"   => $texto_sms
	);

	$url = 'https://sms.comtele.com.br/api/'.$key.'/sendmessage?' . http_build_query($postData, '&');

	$api
		->setUrl($url)
		->addParam($postData)
		->send('POST');

	if ($api->statusCode == 200) {
		$sqlUpdate = "UPDATE tbl_sms SET status_sms = 'Enviada com Sucesso' WHERE sms = $sms_id AND fabrica = $fabrica";
		$resUpdate = pg_query($con, $sqlUpdate);
	}

	unset($sms_id, $fabrica, $destinatario, $texto_sms, $api, $sqlKey, $resKey, $key, $postData, $url, $api, $sqlUpdate, $resUpdate);
}
