<?php
include_once dirname(__FILE__) .'/../../dbconfig.php';
include_once dirname(__FILE__) .'/../../includes/dbconnect-inc.php';
include_once dirname(__FILE__) .'/../../class/sms/sms.class.php';

if (!empty($argv[1])) {
	$fabrica = $argv[1];
	$cond = " AND fabrica = $fabrica ";
}

$sql = "SELECT fabrica FROM tbl_fabrica where api_secret_key_sms notnull and ativo_fabrica $cond ";
$resx = pg_query($con, $sql); 

for($i=0; $i< pg_num_rows($resx);$i++) {
	$fabrica = pg_fetch_result($resx, $i, 'fabrica'); 
	$sms = new SMS($fabrica);

	$date_default = new DateTime();
	$date_default->setTimeZone(new DateTimeZone('America/Sao_Paulo'));

	$date_tomorrow = new DateTime('-44 day');
	$date_tomorrow->setTimeZone(new DateTimeZone('America/Sao_Paulo'));

	$sms_reports = $sms->getDetailedReport($date_tomorrow->format("Y-m-d 00:00:00"),$date_default->format("Y-m-d 23:59:59"));

	$sms_reports = utf8_encode($sms_reports);
	$sms_reports = json_decode($sms_reports,true);

	pg_prepare(
		$con, 'sms_status',
		'UPDATE tbl_sms SET status_sms = $1 WHERE fabrica = $2 AND sms = $3;'
	);

	foreach ($sms_reports['Object'] as $key => $sms_report) {
		if(strlen($sms_report['Sender']) < 11) {
			$status_sms = $sms_report['Status'] == 'Delivered' ? 'Enviada com Sucesso' : 'Erro no Envio' ;
			$res = pg_execute($con, 'sms_status', array($status_sms, $fabrica, $sms_report['Sender']));
		}
	}
	sleep(31);
}
$sql = "UPDATE tbl_sms  set status_sms='Enviada com Sucesso' where status_sms isnull and data > '2018-08-01 00:00' and data < current_date- interval '3 days'";
$resx = pg_query($con, $sql); 
