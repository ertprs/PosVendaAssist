<?php
/**
 *
 * bloqueia-posto-extrato.php
 *
 * Bloqueia Postos com OS abertas com risco de Procon Suggar
 *
 * @author  Ronald Santos
 * @version 2015.06.03
 *
 */

// error_reporting(E_ALL ^ E_NOTICE);

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$data_log['login_fabrica'] = 24;
	$data_log['dest'] = 'helpdesk@telecontrol.com.br';
	$data_log['log'] = 2;

	date_default_timezone_set('America/Sao_Paulo');
	$log[] = Date('d/m/Y H:i:s ')."Inicio do Programa";

	$login_fabrica = 24;
	
	$phpCron = new PHPCron($login_fabrica, __FILE__); 
	$phpCron->inicio();

	if (ENV == 'teste' ) {
        	$data_log['dest'] = 'maicon.luiz@telecontrol.com.br';
	}
    
	$sql = "
		SELECT DISTINCT
			e.extrato
		FROM tbl_extrato e
		JOIN tbl_posto_fabrica pf USING(posto,fabrica)
		LEFT JOIN tbl_extrato_pagamento ep USING(extrato)
		LEFT JOIN tbl_extrato_status es ON es.extrato = e.extrato AND es.fabrica = {$login_fabrica}
		WHERE e.fabrica = {$login_fabrica}
		AND pf.credenciamento != 'DESCREDENCIADO'
		AND e.data_geracao BETWEEN '2018-07-01' AND CURRENT_DATE - INTERVAL '60 days'
		AND es.data::DATE < CURRENT_DATE - INTERVAL '60 days'
		AND ep.data_pagamento IS NULL
		AND e.aprovado IS NOT NULL
		AND e.liberado IS NOT NULL
		AND e.bloqueado IS NOT TRUE;
	";

	$res = pg_query($con, $sql);
	
	while($extratosObj = pg_fetch_object($res)) {
		$upd = "UPDATE tbl_extrato SET bloqueado = TRUE WHERE extrato = {$extratosObj->extrato};";
		pg_query($con, $upd);

		if (strlen(pg_last_error()) > 0) {
			$log_erro[] = "Ocorreu um erro bloqueando o extrato {$extratosObj->extrato}";
		}		
	}

	if(count($log_erro) > 0){
		$header  = "MIME-Version: 1.0\n";
		$header .= "Content-type: text/html; charset=iso-8859-1\n";
		$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

		mail("maicon.luiz@telecontrol.com.br", "TELECONTROL / SUGGAR ({$data}) - BLOQUEIA POSTO", implode("<br />", $log_erro), $header);

		$fp = fopen("/tmp/suggar/bloqueia_posto_extrato.err","w");
		fwrite($fp,implode("<br />", $log_erro));
		fclose($fp);
	}

	$phpCron->termino();

} catch (Exception $e) {
	$e->getMessage();
	$msg = "Arquivo: ".__FILE__."\r\nErro na linha: " . $e->getLine() . "\r\nErro descrição: " . $e->getMessage();
	Log::envia_email($data_log,Date('d/m/Y H:i:s')." - Erro ao executar bloqueio de postos", $msg);
}
