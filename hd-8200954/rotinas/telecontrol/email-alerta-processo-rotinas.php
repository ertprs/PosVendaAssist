<?php

error_reporting(E_ALL);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
    include dirname(__FILE__) . '/../../class/communicator.class.php';

    $fabrica        = 10;

    $mailTc = new TcComm('smtp@posvenda');

    $email_helpdesk = "helpdesk@telecontrol.com.br";
    //$email_helpdesk = "kaique.magalhaes@telecontrol.com.br";

    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $sql = "SELECT tbl_perl.programa,
    			   tbl_fabrica.nome,
    			   TO_CHAR(tbl_perl_processado.inicio_processo, 'mm/dd/yyyy HH24:MI') as inicio_processo
    		FROM tbl_perl_processado
    		JOIN tbl_perl    ON tbl_perl.perl 	 = tbl_perl_processado.perl
    		JOIN tbl_fabrica ON tbl_perl.fabrica = tbl_fabrica.fabrica
    		WHERE inicio_processo IS NOT NULL
    		AND fim_processo IS NULL
    		AND inicio_processo::date = current_date";
   	$res = pg_query($con, $sql);

   	if (pg_num_rows($res) > 0) {

   		$msg = "As seguintes rotinas ainda não foram processadas: <br /><br />
   				<table border=1 style='border-collapse: collapse;'>
   					<thead>
   						<tr style='background-color: darkblue;color: white;font-weight: bolder;'>
   							<td>Fábrica</td>
   							<td>Programa</td>
   							<td>Início Processo</td>
   						</tr>
   					</thead>
   					<tbody>";

	   	while ($dadosPerl = pg_fetch_object($res)) {

	   		$msg .= "<tr>
	   					<td>{$dadosPerl->nome}</td>
	   					<td>{$dadosPerl->programa}</td>
	   					<td>{$dadosPerl->inicio_processo}</td>
	   				</tr>";

	   	}

	   	$msg .= "</tbody>
	   		</table>";

		$mailTc->sendMail(
	        $email_helpdesk,
	        "Alerta de rotinas não processadas",
	        $msg,
	        'noreply@telecontrol.com.br'
	    );

	}

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}