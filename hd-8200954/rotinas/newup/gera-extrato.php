<?php


try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$bug         = '';
	$fabrica     = 201;
	
	$dia_mes     = "28";
	$dia_extrato = date('Y-m-d H:i:s');
	#$dia_extrato = "2012-07-28 02:00:00";

	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	$vet['fabrica'] = 'newup';
	$vet['tipo']    = 'extrato';
	$vet['dest']    = 'ronald.santos@telecontrol.com.br';
	$vet['log']     = 2;
	
	$sql9 = " select os into temp newup_extrato from tbl_os_status join tbl_os_extra using(os) where tbl_os_extra.extrato isnull and tbl_os_status.status_os = 104 and fabrica_status = $fabrica;
			SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '14 days')::date ;  ";
	$res9 = pg_query($con,$sql9);
	$data_15 = pg_fetch_result($res9, 0, 0);

	$sql = "SELECT  tbl_os.posto, COUNT(*) AS qtde
			FROM tbl_os
			JOIN tbl_os_extra USING (os)			
			LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $fabrica AND tbl_posto_fabrica.extrato_programado = CURRENT_DATE
			WHERE tbl_os.fabrica = $fabrica
			AND  tbl_os_extra.extrato IS NULL
			AND  tbl_os.excluida      IS NOT TRUE
			AND  (tbl_os.cancelada     IS NOT TRUE OR tbl_os.cancelada IS NULL)
			AND  tbl_os.posto <> 6359 
			AND  tbl_os.finalizada    <= '$dia_extrato'
			AND  tbl_os.finalizada::date <= current_date
			AND  (tbl_os_status.status_os != 104 OR tbl_os_status.status_os IS NULL)
			AND  (tbl_os_status.status_os != 171 OR tbl_os_status.status_os IS NULL)
			GROUP BY tbl_os.posto
			ORDER BY tbl_os.posto ";
	$res      = pg_query($con, $sql);
	$msg_erro = pg_last_error($con);
    
    if (pg_num_rows($res) > 0 && strlen($msg_erro) == 0) {

		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$posto = pg_result($res, $i, 'posto');
			$qtde  = pg_result($res, $i, 'qtde');
			$msg_erro = "";
			$resP = pg_query($con,"BEGIN TRANSACTION");			
			
			$sql2 = "INSERT INTO tbl_extrato (fabrica, posto, data_geracao,mao_de_obra, pecas, total) VALUES ($fabrica, $posto,'$dia_extrato', 0, 0, 0);";
			$res2 = pg_query($con, $sql2);

			$msg_erro .= pg_last_error($con);

			$sql3      = "SELECT CURRVAL ('seq_extrato');";
			$res3      = pg_query($con, $sql3);
			$extrato   = pg_result($res3, 0, 0);

			$msg_erro .= pg_last_error($con);

			$sql4 = "UPDATE tbl_extrato_lancamento SET extrato = $extrato
				WHERE tbl_extrato_lancamento.fabrica = $fabrica
				AND   tbl_extrato_lancamento.extrato IS NULL
				AND   tbl_extrato_lancamento.posto = $posto; ";
			$res4 = pg_query($con, $sql4);

			$msg_erro .= pg_last_error($con);

			$sql4 = "UPDATE tbl_os_extra SET extrato = $extrato
						FROM  tbl_os
						LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os
						WHERE tbl_os.posto   = $posto
						AND   tbl_os.fabrica = $fabrica
						AND   tbl_os.os      = tbl_os_extra.os
						AND   tbl_os_extra.extrato IS NULL
						AND   tbl_os.excluida      IS NOT TRUE
						AND   tbl_os.finalizada    <= '$dia_extrato' 
						AND   tbl_os.finalizada::date <= current_date
						AND   tbl_os.os not in (select os from newup_extrato)";
			$res4      = pg_query($con, $sql4);
			$msg_erro .= pg_last_error($con);

			$sql5 = "UPDATE tbl_extrato
					SET avulso = (
						SELECT SUM (valor)
						FROM tbl_extrato_lancamento
						WHERE tbl_extrato_lancamento.extrato = tbl_extrato.extrato
					)
					WHERE tbl_extrato.extrato = $extrato;

				UPDATE tbl_extrato
					SET total = mao_de_obra + CASE WHEN avulso isnull THEN 0 ELSE avulso END
				WHERE tbl_extrato.extrato =$extrato";
			$res5      = pg_query($con, $sql5);
            $msg_erro .= pg_last_error($con);

            $sql6 = "SELECT fn_calcula_extrato ($fabrica, $extrato)";
            $res6 = pg_query($con, $sql6);
            $msg_erro .= pg_last_error($con);

			$sqlLGR = "UPDATE tbl_faturamento_item SET 
					extrato_devolucao = $extrato 
					FROM tbl_peca,tbl_faturamento,tbl_extrato
					WHERE tbl_peca.peca = tbl_faturamento_item.peca
					AND tbl_faturamento.posto = tbl_extrato.posto
					AND tbl_faturamento.fabrica = tbl_extrato.fabrica
					AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
					AND tbl_faturamento.fabrica = $fabrica
					AND tbl_faturamento.emissao >='2010-01-01'
					AND tbl_faturamento.emissao <='$data_15'
					AND tbl_faturamento.cancelada IS NULL
					AND tbl_faturamento_item.extrato_devolucao IS NULL
					
					AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
					AND tbl_extrato.extrato = $extrato";
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
				JOIN tbl_os_item USING(os_item)
				JOIN tbl_os_produto USING(os_produto)
				JOIN tbl_os ON(tbl_os.os = tbl_os_produto.os)
				JOIN tbl_produto ON(tbl_os.produto = tbl_produto.produto)
				JOIN tbl_linha ON(tbl_produto.linha = tbl_linha.linha)
				WHERE tbl_extrato.fabrica = $fabrica
				AND tbl_extrato.extrato = $extrato
				AND lower(tbl_linha.nome) = 'lavadora'
				GROUP BY tbl_extrato.extrato,
				tbl_extrato.posto,
				tbl_faturamento_item.peca";
			$resLGR = pg_query($con,$sqlLGR2);
			$msg_erro .= pg_last_error($con);


			$sql6 = "SELECT fn_calcula_extrato ($fabrica, $extrato)";
            $res6 = pg_query($con, $sql6);
            $msg_erro .= pg_last_error();
    
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
		Log::envia_email($vet, 'Log - Extrato newup', $bug);

	}
	
	$phpCron->termino();

} catch (Exception $e) {

	Log::envia_email($data,Date('d/m/Y H:i:s')." - newup - Erro na geração de extrato(gera-extrato.php)", $e->getMessage());

}?>
