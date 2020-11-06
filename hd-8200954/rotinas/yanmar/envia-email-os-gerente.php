<?php

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'willian.spalaor@telecontrol.com.br');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $fabrica = 148;
	$fabrica_nome = 'yanmar';

	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();
	
	$sql = "SELECT admin FROM tbl_admin WHERE fabrica = $fabrica AND ativo IS TRUE AND email = 'rodrigo_marques@yanmar.com'";
	$qry = pg_query($sql);

	if(pg_num_rows($qry) > 0){

		$id_admin = pg_fetch_result($qry, 0, 'admin');

		$sql = "SELECT COUNT(os)
				FROM tbl_os
				JOIN tbl_tipo_atendimento USING(tipo_atendimento,fabrica)
				WHERE tbl_os.fabrica = 148
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.finalizada IS NOT NULL
				AND tbl_os.finalizada::date BETWEEN CURRENT_DATE - INTERVAL '7 days' and CURRENT_DATE - INTERVAL '1 day'
				AND (upper(tbl_tipo_atendimento.descricao) = 'GARANTIA' OR upper(tbl_tipo_atendimento.descricao) LIKE 'PMP%')";

		$qry = pg_query($sql);

		if(pg_num_rows($qry) > 0){

		 	require_once dirname(__FILE__) . '/../../class/communicator.class.php';

		 	if (ENV == 'producao') {
				$email ='rodrigo_marques@yanmar.com';
			} else {
				$email = DEV_EMAIL;
			}

			date_default_timezone_set('America/Sao_Paulo');

			$data_inicio = date('d/m', strtotime('-7 days'));
			$data_fim = date('d/m', strtotime('-1 day'));

			$param_inicio = date('Y-m-d', strtotime('-7 days'));
			$param_fim = date('Y-m-d', strtotime('-1 day'));

			$assunto =  "Telecontrol - OS's Fechadas de {$data_inicio} à {$data_fim}";
			$params = '?admin='.base64_encode($id_admin).'&data_inicio='.$param_inicio.'&data_fim='.$param_fim;

			if(ENV == 'producao'){
				$url = 'https://posvenda.telecontrol.com.br/assist/externos/yanmar/relacao-os-finalizada-7-dias.php' . $params;
			}else{
				$url = 'http://novodevel.telecontrol.com.br/~spalaor/chamados/HD-7866220/externos/yanmar/relacao-os-finalizada-7-dias.php' . $params;
			}

			$assunto =  "Telecontrol - OS's Fechadas de {$data_inicio} à {$data_fim}";

			$body  = "<p style='padding-bottom:10px'><b>Segue abaixo a relação das OS's Fechadas de {$data_inicio} à {$data_fim}:";
			$body .= "<span style='color:#bf1010'><i> Apenas OS de Garantia</i></span></b></p>";
			$body .= "<p style='max-width: 480px;'><a style='text-decoration: none;color: #39419c;font-weight: bold;'href='{$url}'>Clique aqui</a> para ser direcionado para a Relação de OS Aguardando Avaliação Final da Gerência.</p>";
		
	        $mailTc = new TcComm('smtp@posvenda');

	        $res = $mailTc->sendMail(
	            $email,
	            $assunto,
	            $body,
	            'noreply@telecontrol.com.br'
	        );            
		}
	}

    $phpCron->termino();
    
} catch (Exception $e) {
	echo $e->getMessage();
}

