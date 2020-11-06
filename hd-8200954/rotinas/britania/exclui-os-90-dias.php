<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	$bug         = '';
	$fabrica     = 3;
	$dia_mes     = date('d');

	$vet['fabrica'] = 'britania';
	$vet['tipo']    = 'excluios';
	$vet['dest']    = 'helpdesk@telecontrol.com.br';
	$vet['log']     = 2;


	if ($dia_mes==10){
		$sql = "SELECT tbl_os.os
			FROM tbl_os
			LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto and tbl_os_item.fabrica_i = $fabrica
			WHERE tbl_os.fabrica = $fabrica
			AND tbl_os.data_fechamento IS NULL
			AND tbl_os.finalizada IS NULL
			AND tbl_os.os_fechada is false
			AND tbl_os.consumidor_revenda = 'C'
			AND tbl_os.data_digitacao::date < current_date - INTERVAL '90 days'
			/* AND tbl_os.posto = 6359 AND tbl_os.data_digitacao > '2011-01-01' */
			AND tbl_os.excluida is not true
			AND tbl_os_item.os_item IS NULL;
			";
		$res       = pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);

		if (pg_num_rows($res) > 0 && strlen($msg_erro) == 0) {

			for ($i = 0; $i < pg_num_rows($res); $i++) {
			    $os = pg_result($res,$i,'os');
			
			    if(strlen($os)>0){
				$sqlx = "SELECT fn_exclui_os($os,$fabrica)";
				$resx       = pg_query($con, $sqlx);
			    	$xmsg_erro .= pg_last_error($con);
				if(strlen($xmsg_erro)>0){
					$msg_erro  .= nl2br($xmsg_erro)." \n";
				}
			    }

			}
		}

		if (strlen($msg_erro) > 0) {
			$bug .= $msg_erro;
			Log::log2($vet, $msg_erro);
		}
	}

	if (strlen($bug) > 0) {

		Log::envia_email($vet, 'Log - Exclui OS aberta a mais de 90 dias', $bug);
	}

} catch (Exception $e) {

	echo $e->getMessage();

}?>
