<?php
include_once dirname(__FILE__) . '/../../dbconfig.php';
include_once dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . '../../class/communicator.class.php';
include_once dirname(__FILE__) . '/../../class/sms/sms.class.php';
// include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

$sms    = new SMS();
$mail   = new TcComm('smtp@posvenda', 'noreply@telecontrol.com.br');
$msgTpl = 'Saldo de envio de SMS abaixo do m�nimo: %s.'.
	"\n\nPor favor, adicionar mais cr�dito para a f�brica: <strong>%s</strong>.";

error_reporting(E_ALL & !E_NOTICE);
$fabricas = $sms::getFabricasSms();

foreach ($fabricas as $i) {

	$sms->setFabrica($i);
	$saldo = $sms->obterSaldo();
	// echo "Saldo para a f�brica $i - " . $sms->nomefabrica . ": $saldo\n";

	if (is_numeric($saldo) and $saldo <= 500) {
		$subject = date('d/m/Y') . " - Saldo SMS abaixo do m�nimo: ".$sms->nome_fabrica;

		$mail->sendMail(
			'ronaldo@telecontrol.com.br,joao.junior@telecontrol.com.br',
			// 'manuel@telecontrol.com.br',
			$subject,
			sprintf($msgTpl, $saldo, $sms->nome_fabrica),
			'noreply@telecontrol.com.br'
		);
	}
}

