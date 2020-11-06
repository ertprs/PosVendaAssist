<?php
error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	require "/www/assist/www/rotinas/calcula_extrato_excecao_mobra.php";

	$bug         = '';
	$fabrica     = 122;
	$dia_mes     = date('d');
	#$dia_mes     = "28";
	$dia_extrato = date('Y-m-d H:i:s');
	#$dia_extrato = "2012-07-28 02:00:00";

	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	$vet['fabrica'] = 'wurth';
	$vet['tipo']    = 'extrato';
	$vet['dest']    = 'helpdesk@telecontrol.com.br';
	$vet['log']     = 2;



	$sql9 = "SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '14 days')::date";
	$res9 = pg_query($con,$sql9);
	$data_15 = pg_fetch_result($res9, 0, 0);

	$sql = "SELECT  tbl_os.posto, COUNT(*) AS qtde
			FROM tbl_os
			JOIN tbl_os_extra USING (os)
			JOIN    tbl_posto_fabrica     ON tbl_posto_fabrica.posto   = tbl_os.posto
					AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			JOIN 	tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
					AND tbl_tipo_posto.posto_interno IS NOT TRUE
			WHERE tbl_os.fabrica = $fabrica
			AND   tbl_os_extra.extrato IS NULL
			AND   tbl_os.excluida      IS NOT TRUE
			/*AND   tbl_os.posto <> 6359*/
			AND   tbl_os.finalizada    < '$dia_extrato'
			AND   tbl_os.finalizada::date < current_date
			GROUP BY tbl_os.posto
			ORDER BY tbl_os.posto ";

	$res      = pg_query($con, $sql);
	$msg_erro = pg_last_error($con);

	if (pg_num_rows($res) > 0 && strlen($msg_erro) == 0) {

		for ($i = 0; $i < pg_num_rows($res); $i++) {


			$posto = pg_result($res, $i, 'posto');
			$qtde  = pg_result($res, $i, 'qtde');

			$resP = pg_query($con,"BEGIN TRANSACTION");


			$sql5      = "SELECT fn_fechamento_extrato ($posto, $fabrica, current_date)";
			$res5      = pg_query($con, $sql5);
			$msg_erro .= pg_last_error($con);

			$extrato = pg_fetch_result($res5, 0, 0);

			$sql6      = "SELECT fn_calcula_extrato ($fabrica, $extrato)";
			$res6      = pg_query($con, $sql6);
			$msg_erro .= pg_last_error($con);

			//instanciando o objeto (cem "Calculo Excecao Mobra")
			$cem = new ExcecaoMobra($extrato,$fabrica);
			$cem->calculaExcecaoMobra();

			$sql6      = "UPDATE tbl_extrato set aprovado = now(), liberado= now() where extrato = $extrato";
			$res6      = pg_query($con, $sql6);
			$msg_erro .= pg_last_error($con);

			$erros_cem = $cem->getErros();

			if (count($erros_cem)>0) {
				$msg_erro .= $cem->getErros();
			}

			$intervalo = "";

			$sql7 = "SELECT tbl_faturamento.faturamento,tbl_faturamento.emissao
						FROM tbl_faturamento
						JOIN tbl_faturamento_item USING(faturamento)
						WHERE fabrica = $fabrica
						AND tbl_faturamento_item.extrato_devolucao notnull
						AND distribuidor isnull
						ORDER BY emissao DESC LIMIT 1";
			$res7 = pg_query($con,$sql7);

			if(pg_num_rows($res7) == 0){
				$sql7 = "SELECT faturamento,emissao  FROM tbl_faturamento WHERE fabrica = $fabrica ORDER BY emissao DESC LIMIT 1";
				$res7 = pg_query($con,$sql7);
				$intervalo = "OK";
			}

			if(pg_num_rows($res7) > 0){
				$faturamento 	= pg_fetch_result($res7, 0, 'faturamento');
				$emissao 		= pg_fetch_result($res7, 0, 'emissao');

				if(empty($intervalo)){
					$sql10 = "SELECT CURRENT_DATE - '$emissao'";
					$res10 = pg_query($con,$sql10);
					$intervalo = pg_fetch_result($res10, 0, 0);
					$cond = " AND tbl_faturamento.faturamento > $faturamento ";
				}else{
					$emissao = '2012-11-01';
				}

				if($intervalo > 90 OR $intervalo == "OK"){

					$sqlLGR = "UPDATE tbl_faturamento_item SET
							extrato_devolucao = $extrato
							FROM tbl_peca,tbl_faturamento,tbl_extrato
							WHERE tbl_peca.peca = tbl_faturamento_item.peca
							$cond
							AND tbl_faturamento.posto = tbl_extrato.posto
							AND tbl_faturamento.fabrica = tbl_extrato.fabrica
							AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							AND tbl_peca.fabrica = $fabrica
							AND tbl_faturamento.fabrica = $fabrica
							AND tbl_faturamento.emissao >='$emissao'
							AND tbl_faturamento.emissao >='$data_15'
							AND tbl_faturamento.cancelada IS NULL
							AND tbl_peca.devolucao_obrigatoria
							AND tbl_faturamento_item.extrato_devolucao IS NULL
							AND (tbl_faturamento_item.cfop ILIKE '59%' OR tbl_faturamento_item.cfop ILIKE '69%')
							AND tbl_extrato.extrato = $extrato";
					$resLGR = pg_query($con,$sqlLGR);
					$msg_erro .= pg_last_error($con);

					$sqlLGR = "UPDATE tbl_faturamento SET
							extrato_devolucao = $extrato
							FROM tbl_faturamento_item
							WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							AND tbl_faturamento.posto = $posto
							AND tbl_faturamento.fabrica = $fabrica
							AND tbl_faturamento.emissao >='$emissao'
							AND tbl_faturamento.emissao >='$data_15'
							AND tbl_faturamento_item.extrato_devolucao = $extrato";
					$resLGR = pg_query($con,$sqlLGR);
					$msg_erro .= pg_last_error($con);

					$sqlLGR2 = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde)
						SELECT
						tbl_extrato.extrato,
						tbl_extrato.posto,
						tbl_faturamento_item.peca,
						SUM (tbl_faturamento_item.qtde)
						FROM tbl_extrato
						JOIN tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
						WHERE tbl_extrato.fabrica = $fabrica
						AND tbl_extrato.extrato = $extrato
						GROUP BY tbl_extrato.extrato,
						tbl_extrato.posto,
						tbl_faturamento_item.peca";
					$resLGR2 = pg_query($con,$sqlLGR2);
					$msg_erro .= pg_last_error($con);
				}


			}

			if (strlen($msg_erro) > 0) {
				$resP = pg_query('ROLLBACK;');
				$bug .= $msg_erro;

				Log::log2($vet, $msg_erro);

			} else {

				$resP = pg_query('COMMIT;');

			}

		}

	}

	if (strlen($bug) > 0) {

		Log::envia_email($vet, 'Log - Extrato Wurth', $bug);

	}
	
	$phpCron->termino();

} catch (Exception $e) {

	echo $e->getMessage();

}?>
