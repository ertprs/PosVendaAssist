<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	$bug         = '';
	$fabrica     = 125;
	$dia_mes     = date('d');
	#$dia_mes     = "28";
	$dia_extrato = date('Y-m-d H:i:s');
	#$dia_extrato = "2012-07-28 02:00:00";
	$data_limite = date('Y-m-d');


	$vet['fabrica'] = 'saintgobain';
	$vet['tipo']    = 'extrato';
	$vet['dest']    = 'ronald.santos@telecontrol.com.br';
	$vet['log']     = 2;


	$sql9 = "SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '14 days')::date";
	$res9 = pg_query($con,$sql9);
	$data_15 = pg_fetch_result($res9, 0, 0);

	$sql = "SELECT  posto, COUNT(*) AS qtde
			FROM    tbl_os
			JOIN    tbl_os_extra        USING (os)
            JOIN    tbl_posto_fabrica   USING (posto)
            JOIN    tbl_tipo_posto      ON tbl_tipo_posto.tipo_posto    = tbl_posto_fabrica.tipo_posto
                                        AND tbl_tipo_posto.fabrica      = $fabrica
                                        AND tbl_posto_fabrica.fabrica   = $fabrica
			WHERE   tbl_os.fabrica              = $fabrica
			AND     tbl_os_extra.extrato        IS NULL
			AND     tbl_os.excluida             IS NOT TRUE
			AND     tbl_os.posto            	<> 6359 
            AND     tbl_tipo_posto.tipo_posto   <> 398
			AND     tbl_os.finalizada::date     <= '$dia_extrato'
			AND     tbl_os.finalizada::date     <= current_date
      GROUP BY      tbl_os.posto
      ORDER BY      tbl_os.posto ";

	$res      = pg_query($con, $sql);
	$msg_erro = pg_last_error($con);

	if (pg_num_rows($res) > 0 && strlen($msg_erro) == 0) {

		for ($i = 0; $i < pg_num_rows($res); $i++) {


			$posto = pg_result($res, $i, 'posto');
			$qtde  = pg_result($res, $i, 'qtde');

			$resP = pg_query($con,"BEGIN TRANSACTION");


			$sql_extrato = "SELECT fn_fechamento_extrato ($posto, $fabrica, '$data_limite');";
			$res_extrato = pg_query($con,$sql_extrato);
			$msg_erro .= pg_errormessage($con);

			$sql_extrato = "SELECT extrato
							FROM tbl_extrato
							WHERE fabrica = $fabrica
							AND posto = $posto
							AND data_geracao::date = CURRENT_DATE";
			$res_extrato = pg_query($con,$sql_extrato);
			$msg_erro = pg_errormessage($con);

			if(empty($msg_erro)){

				$extrato = pg_fetch_result($res_extrato, 0, 'extrato');

                $sql_extrato = "SELECT fn_calcula_extrato($fabrica, $extrato);";
		
                $res_extrato = pg_query($con,$sql_extrato);
                $msg_erro .= pg_errormessage($con);

				$sql_libera = "UPDATE tbl_extrato
								SET aprovado = CURRENT_TIMESTAMP,
								liberado = CURRENT_DATE
								WHERE extrato = $extrato
								AND fabrica = $fabrica";

				$res_libera = pg_query($con,$sql_libera);
				$msg_erro .= pg_errormessage($con);

				$sqlLGR = "UPDATE tbl_faturamento_item SET
							extrato_devolucao = $extrato
							FROM tbl_os_item,tbl_faturamento,tbl_extrato, tbl_peca
							WHERE tbl_os_item.os_item = tbl_faturamento_item.os_item
							AND tbl_faturamento.posto = tbl_extrato.posto
							AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							AND tbl_faturamento.fabrica in (10,$fabrica)
							AND tbl_faturamento.emissao >='2010-01-01'
							AND	tbl_peca.fabrica = $fabrica
							AND tbl_faturamento.emissao <='$data_15'
							AND tbl_faturamento.cancelada IS NULL
							AND tbl_faturamento_item.extrato_devolucao IS NULL
							AND tbl_peca.peca = tbl_os_item.peca
							AND tbl_os_item.peca_obrigatoria
							AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
							AND tbl_extrato.extrato = $extrato
							AND (select count(1) from tbl_faturamento join tbl_faturamento_item using(faturamento) join tbl_os_item using(os_item) join tbl_extrato ON tbl_faturamento.posto = tbl_extrato.posto where tbl_faturamento.fabrica in(10,$fabrica) and tbl_faturamento_item.extrato_devolucao IS NULL and tbl_os_item.peca_obrigatoria IS TRUE AND tbl_extrato.extrato = $extrato and tbl_os_item.fabrica_i= $fabrica) >= 3";
				$resLGR = pg_query($con,$sqlLGR);
				$msg_erro .= pg_last_error($con);

				$sqlLGR = "UPDATE tbl_faturamento SET
						extrato_devolucao = $extrato
						FROM tbl_faturamento_item
						WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
						AND tbl_faturamento.posto = $posto
						AND tbl_faturamento.fabrica = $fabrica
						AND tbl_faturamento.emissao >='2010-01-01'
						AND tbl_faturamento.emissao <='$data_15'
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
				$resLGR = pg_query($con,$sqlLGR2);
				$msg_erro .= pg_last_error($con);
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

		Log::envia_email($vet, 'Log - Extrato saintgobain', $bug);

	}

} catch (Exception $e) {

	Log::envia_email($data,Date('d/m/Y H:i:s')." - saintgobain - Erro na geração de extrato(gera-extrato.php)", $e->getMessage());

}?>
