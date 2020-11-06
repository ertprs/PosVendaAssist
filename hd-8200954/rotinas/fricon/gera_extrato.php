<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$bug         = '';
	$fabrica     = 52;
	$dia_mes     = date('d');
	#$dia_mes     = "28";
	$dia_extrato = date('Y-m-d H:i:s');
	#$dia_extrato = "2012-07-28 02:00:00";

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	$vet['fabrica'] = 'fricon';
	$vet['tipo']    = 'extrato';
	$vet['dest']    = 'helpdesk@telecontrol.com.br';
	$vet['log']     = 2;

	if ($dia_mes == '07') {
		$cond = " AND tbl_posto_fabrica.contato_estado in ('BA', 'MG', 'DF', 'MT', 'RO', 'AP', 'RR', 'TO', 'AC') ";
	}

	if ($dia_mes == '14') {
		$cond = " AND tbl_posto_fabrica.contato_estado in ('PE', 'RN', 'PI', 'PA', 'AL', 'SE', 'MS', 'AM', 'MA') ";
	}

	if ($dia_mes == '21') {
		$cond = " AND tbl_posto_fabrica.contato_estado in ('SP', 'PR', 'SC') ";
	}

	if ($dia_mes == '28') {
		$cond = " AND tbl_posto_fabrica.contato_estado in ('CE', 'ES', 'PB', 'RJ', 'GO', 'RS') ";
	}

	if (strlen($cond) > 0) {

		$sql = "SELECT  tbl_os.posto, COUNT(1) AS qtde
				FROM 	tbl_os
				JOIN    tbl_produto       ON tbl_produto.produto = tbl_os.produto AND tbl_produto.linha <> 557 AND tbl_produto.fabrica_i = tbl_os.fabrica
				JOIN 	tbl_os_extra      ON tbl_os.os = tbl_os_extra.os          AND tbl_os.fabrica = tbl_os_extra.i_fabrica
				JOIN 	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $fabrica
				WHERE 	tbl_os.fabrica = $fabrica
				AND   	tbl_posto_fabrica.credenciamento IN('CREDENCIADO', 'EM DESCREDENCIAMENTO')
				$cond
				AND     tbl_os_extra.extrato IS NULL
				AND	    tbl_os.excluida      IS NOT TRUE
				AND	    tbl_os.finalizada    <= '$dia_extrato'
				AND	    tbl_os.finalizada::date <> '$dia_extrato'
				AND	    tbl_os.posto       <> 6359
				GROUP BY tbl_os.posto
				ORDER BY tbl_os.posto ";

		$res      = pg_query($con, $sql);
		$msg_erro = pg_last_error($con);

		if (pg_num_rows($res) > 0 && strlen($msg_erro) == 0) {

			for ($i = 0; $i < pg_num_rows($res); $i++) {


				$posto = pg_result($res, $i, 'posto');

				$sqlMO = "UPDATE tbl_os SET mao_de_obra = 0
				FROM tbl_os_extra
				WHERE  tbl_os_extra.os = tbl_os.os
				AND    tbl_os.posto    = $posto
				AND    tbl_os.fabrica  = $fabrica
				AND    tbl_os_extra.extrato   IS NULL
				AND    tbl_os.mao_de_obra     IS NULL";
				$resMO = pg_query($con,$sqlMO);

				$sqlPE = "UPDATE tbl_os SET pecas = 0
				FROM tbl_os_extra
				WHERE  tbl_os_extra.os = tbl_os.os
				AND    tbl_os.posto    = $posto
				AND    tbl_os.fabrica  = $fabrica
				AND    tbl_os_extra.extrato   IS NULL
				AND    tbl_os.pecas           IS NULL" ;
				$resPE = pg_query($con,$sqlPE);

				$msg_erro  = null;
				$res1      = pg_query('BEGIN;');
				$msg_erro .= pg_last_error($con);

				$sql2 = "INSERT INTO tbl_extrato (fabrica, posto, data_geracao,mao_de_obra, pecas, total) VALUES ($fabrica, $posto,'$dia_extrato', 0, 0, 0);";
				$res2 = pg_query($con, $sql2);

				$msg_erro .= pg_last_error($con);

				$sql3      = "SELECT CURRVAL ('seq_extrato');";
				$res3      = pg_query($con, $sql3);
				$extrato   = pg_result($res3, 0, 0);

				$msg_erro .= pg_last_error($con);

				$sql4 = "UPDATE    tbl_os_extra SET extrato = $extrato
							FROM   tbl_os
							JOIN   tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.linha <> 557 AND tbl_produto.fabrica_i = tbl_os.fabrica
							WHERE  tbl_os_extra.os = tbl_os.os
							AND    tbl_os.posto    = $posto
							AND    tbl_os.fabrica  = $fabrica
							AND    tbl_os_extra.extrato    IS NULL
							AND    tbl_os.data_fechamento  IS NOT NULL
							AND    tbl_os.finalizada       IS NOT NULL
							AND    tbl_os.mao_de_obra      IS NOT NULL
							AND    tbl_os.pecas            IS NOT NULL
							AND    (tbl_os.excluida        IS FALSE OR tbl_os.excluida IS NULL)
							AND    tbl_os.data_fechamento  <= '$dia_extrato'
							AND    tbl_os.finalizada::date <= '$dia_extrato'
							AND     tbl_os.os NOT IN ( SELECT interv_reinc.os
														FROM (
															SELECT
															ultima_reinc.os,
															(SELECT status_os
															FROM 	tbl_os_status
															WHERE 	fabrica_status = $fabrica
															AND 	tbl_os_status.os = ultima_reinc.os
															AND 	status_os IN (62,64,67,134,13,131,135,19,99,139,155)
															AND     tbl_os_status.extrato isnull
															ORDER BY data DESC LIMIT 1) AS ultimo_reinc_status
															FROM (SELECT DISTINCT os
																	FROM tbl_os_status
																	WHERE fabrica_status = $fabrica
																	AND status_os IN (62,64,67,134,13,131,135,19,99,139,155) ) ultima_reinc
															) interv_reinc
														WHERE interv_reinc.ultimo_reinc_status IN (62,67,134,13,131)
													);";

				$res4      = pg_query($con, $sql4);
				$msg_erro .= pg_last_error($con);

				$sql5      = "SELECT fn_calcula_extrato ($fabrica, $extrato)";
				$res5      = pg_query($con, $sql5);
				$msg_erro .= pg_last_error($con);

				if (strlen($msg_erro) > 0) {

					$res6 = pg_query('ROLLBACK;');
					$bug .= $msg_erro;

					Log::log2($vet, $msg_erro);

				} else {

					$res6 = pg_query('COMMIT;');

				}

			}

		}

	}

	if (strlen($bug) > 0) {

		Log::envia_email($vet, 'Log - Extrato Fricon', $bug);

	}

	$phpCron->termino();

} catch (Exception $e) {

	echo $e->getMessage();

}?>
