<?php
include_once dirname(__FILE__) .'/../../dbconfig.php';
include_once dirname(__FILE__) .'/../../includes/dbconnect-inc.php';
include_once dirname(__FILE__) .'/../../class/email/mailer/class.phpmailer.php';
include_once dirname(__FILE__) .'/../../class/sms/sms.class.php';

 
$sql = "SELECT distinct fabrica from tbl_sms_pendente ";
$resj = pg_query($con, $sql); 

pg_prepare(
	$con, 'consumidor',
	'SELECT consumidor_celular, TO_CHAR(data_fechamento, \'DD/MM/YYYY\'), sua_os, texto_sms FROM tbl_os left join tbl_sms using(os) WHERE os = $1'
);

for($j = 0 ; $j < pg_num_rows($resj); $j++) {
	$fabrica_sms = pg_fetch_result($resj, $j, 'fabrica'); 
	$sms = new SMS($fabrica_sms);

	$sms_pendentes = $sms->selecionarSMSPedente($fabrica_sms);

	if (!$sms_pendentes)
		die;

	for ($i = 0; $i < count($sms_pendentes); $i++) {

		$fabrica    = $sms_pendentes[$i]['fabrica'];
		$hd_chamado = $sms_pendentes[$i]['hd_chamado'];
		$os         = $sms_pendentes[$i]['os'];

		/* Destinatário */
		$res = pg_execute($con, 'consumidor', (array)$os);

		if (pg_num_rows($res) > 0) {

			$destinatario = pg_fetch_result($res, 0, 'consumidor_celular');
			$data         = pg_fetch_result($res, 0, 'data_fechamento');
			$texto_sms    = pg_fetch_result($res, 0, 'texto_sms');
			$sua_os       = pg_fetch_result($res, 0, 'sua_os') ? : $os;

			if(strlen(trim($destinatario)) < 5 or !$sms->validaDestinatario($destinatario)) {
				$sms->excluirSMSPendente($os);
				continue; 
			}

			/* Envia SMS */
			$enviar = $sms->setFabrica($fabrica)
				->enviarMensagem($destinatario, $os, $data, $texto_sms);

			/* Se enviou */
			if ($enviar == true) {
				$sms->excluirSMSPendente($os);
			}
		}
	}
}
