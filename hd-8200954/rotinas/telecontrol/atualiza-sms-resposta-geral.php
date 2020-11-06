<?php
include_once dirname(__FILE__) .'/../../dbconfig.php';
include_once dirname(__FILE__) .'/../../includes/dbconnect-inc.php';
include_once dirname(__FILE__) .'/../../class/sms/sms.class.php';


function retira_acentos( $texto ){
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
    return str_replace( $array1, $array2, $texto );
}

$sql = "SELECT fabrica FROM tbl_fabrica where api_secret_key_sms notnull and ativo_fabrica";
$resx = pg_query($con, $sql); 

for($i=0; $i< pg_num_rows($resx);$i++) {
	$fabrica = pg_fetch_result($resx, $i, 'fabrica'); 

	$sms = new SMS($fabrica);


	$date_default = new DateTime();
	$date_default->setTimeZone(new DateTimeZone('America/Sao_Paulo'));

	$date_tomorrow = new DateTime('-44 day');
	$date_tomorrow->setTimeZone(new DateTimeZone('America/Sao_Paulo'));

	$sms_reports = $sms->getReplyReport($date_tomorrow->format("Y-m-d 00:00:00"),$date_default->format("Y-m-d 23:59:59"),$sender);

	$sms_reports = json_decode($sms_reports);

	pg_prepare(
		$con, 'pesquisa_sms_resposta',
		'SELECT sms, resposta, credito_resposta, data FROM tbl_sms_resposta WHERE sms = $1 AND data = $2 ;'
	);

	pg_prepare(
		$con, 'sms_resposta',
		'INSERT INTO tbl_sms_resposta (sms,resposta,credito_resposta,data) VALUES ($1, $2, $3, $4);'
	);

	if ($fabrica == 174) {
		pg_prepare(
			$con, 'pesquisa_sms',
			'SELECT hd_chamado FROM tbl_sms WHERE sms = $1;'
		);
	}

	foreach ($sms_reports as $sms_report) {
		$num_msg = retira_acentos($sms_report->ReceivedContent);
		$num_msg = strlen($num_msg);
		if($num_msg > 160) {
			$credito = ceil($num_msg/153);
		}else{
			$credito = ceil($num_msg/160);
		}
		$res = pg_execute($con, 'pesquisa_sms_resposta', array($sms_report->SenderName, $sms_report->ReceivedDate ));

		if (pg_num_rows($res) == 0) {
			$res = pg_execute($con, 'sms_resposta', array($sms_report->SenderName, $sms_report->ReceivedContent, $credito, $sms_report->ReceivedDate));
			if ($fabrica == 174) {
				$res_sql = pg_execute($con, 'pesquisa_sms', array($sms_report->SenderName));
				$hd_chamado = pg_fetch_result($res_sql, 0, 'hd_chamado');
				$sql = "INSERT INTO tbl_hd_chamado_item(hd_chamado, data, comentario,  interno) values ($hd_chamado, current_timestamp, 'Resposta SMS : $sms_report->ReceivedContent' , 't')";
				$aux_res = pg_query($con, $sql);
			}
		}
	}

	sleep(31);
}
