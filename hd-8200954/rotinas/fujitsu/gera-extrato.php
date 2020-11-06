<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$bug         = '';
	$fabrica     = 138;
	$dia_mes     = date('d');	
	$dia_extrato = date('Y-m-d H:i:s');	
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	$vet['fabrica'] = 'fujitsu';
	$vet['tipo']    = 'extrato';
	$vet['dest']    = 'lucas.carlos@telecontrol.com.br';

	/* Log */
    $log = new Log2();
    $log->adicionaLog(array("titulo" => "Log erro Geração de Extrato Fujitsu")); // Titulo
  
	$log->adicionaEmail("ronald.santos@telecontrol.com.br");

	$sql9 = "SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '14 days')::date";
	$res9 = pg_query($con,$sql9);
	$data_15 = pg_fetch_result($res9, 0, 0);

	$sql = "SELECT tbl_posto_fabrica.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
			FROM tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE tbl_posto_fabrica.fabrica = {$fabrica}
			AND tbl_posto_fabrica.posto <> 6359
			AND tbl_posto_fabrica.credenciamento IN('CREDENCIADO','EM DESCREDENCIAMENTO')
			ORDER BY tbl_posto.nome ";
	$res      = pg_query($con, $sql);
	$msg_erro = pg_last_error($con);

	if (pg_num_rows($res) > 0 && strlen($msg_erro) == 0) {

		/* Class Extrato */
        include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

        $classExtrato = new Extrato($fabrica);

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$msg_erro = "";
			$posto = pg_result($res, $i, 'posto');
			$nome = pg_result($res, $i, 'nome');
			$codigo_posto = pg_result($res, $i, 'codigo_posto');
			$qtde  = pg_result($res, $i, 'qtde');

			$resP = pg_query($con,"BEGIN TRANSACTION");

			$sql2 = "INSERT INTO tbl_extrato (fabrica, posto, data_geracao,mao_de_obra, pecas, total,avulso) VALUES ($fabrica, $posto,'$dia_extrato', 0, 0, 0, 0);";

			$res2 = pg_query($con, $sql2);

			if(strlen(pg_last_error($con) > 0)){
				$log->adicionaLog("Erro ao gravar extrato para o posto : {$codigo_posto} - {$nome}");
        		$log->adicionaLog("linha");
        		$msg_erro .= pg_last_error($con);
			}else{
				$sql3      = "SELECT CURRVAL ('seq_extrato');";
				$res3      = pg_query($con, $sql3);
				$extrato   = pg_result($res3, 0, 0);
				$msg_erro .= pg_last_error($con);
			}

			$sql4 = "UPDATE tbl_os_extra SET extrato = $extrato
					FROM tbl_os, tbl_hd_chamado,tbl_resposta
					WHERE tbl_os.posto = {$posto}
					AND tbl_os.fabrica = {$fabrica}
					AND tbl_os.os = tbl_os_extra.os
					AND tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado
					AND tbl_hd_chamado.fabrica_responsavel = {$fabrica}
					AND tbl_resposta.os = tbl_os.os
					AND tbl_resposta.sem_resposta IS NOT TRUE
					AND tbl_os_extra.extrato IS NULL
					AND tbl_os_extra.admin_paga_mao_de_obra IS TRUE
					AND tbl_hd_chamado.status = 'Resolvido'
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.finalizada IS NOT NULL
					AND tbl_os.finalizada <= '$dia_extrato'";

			$res4 = pg_query($con, $sql4); 

			$sqlCond = "SELECT count(1) as total FROM tbl_os_extra WHERE extrato = {$extrato}";
			$count = pg_query($con, $sqlCond);

			if(pg_fetch_result($count, 0, total) == 0){
				$cond = " AND tbl_extrato_lancamento.debito_credito = 'C' ";
			}

			if(strlen(pg_last_error($con) > 0)){
				$log->adicionaLog("Erro ao relacionar as OS com o extrato para o posto : {$codigo_posto} - {$nome}");
        		$log->adicionaLog("linha");
        		$msg_erro .= pg_last_error($con);
			}

			$sql4 = "UPDATE tbl_extrato_lancamento SET extrato = $extrato
				WHERE tbl_extrato_lancamento.fabrica = $fabrica
				AND   tbl_extrato_lancamento.extrato IS NULL
				AND   tbl_extrato_lancamento.posto = $posto
				{$cond}; ";

			$res4 = pg_query($con, $sql4); 	

			if(strlen(pg_last_error($con) > 0)){
				$log->adicionaLog("Erro ao gravar os lançamentos avulsos para o posto : {$codigo_posto} - {$nome}");
        		$log->adicionaLog("linha");
        		$msg_erro .= pg_last_error($con);
			}

			$sql8 = "SELECT valor FROM tbl_extrato_lancamento WHERE extrato = {$extrato};";

			$res8 = pg_query($con, $sql8);

			$sql5 = "UPDATE tbl_extrato
					SET avulso = (
						SELECT SUM (valor)
						FROM tbl_extrato_lancamento
						WHERE tbl_extrato_lancamento.extrato = tbl_extrato.extrato
					)
				WHERE tbl_extrato.fabrica = $fabrica
				AND tbl_extrato.data_geracao > CURRENT_DATE";
			$res5      = pg_query($con, $sql5);
			if(strlen(pg_last_error($con) > 0)){
				$log->adicionaLog("Erro ao atualizar os valores dos lançamentos avulsos para o posto : {$codigo_posto} - {$nome}");
        		$log->adicionaLog("linha");
        		$msg_erro .= pg_last_error($con);
			}

			/* Calcula o Extrato */
	        $sql = "SELECT fn_calcula_extrato ($fabrica,$extrato)";
            pg_query($con, $sql);

            $sqlTotalExtrato = "SELECT total FROM tbl_extrato WHERE extrato = $extrato  and fabrica = $fabrica ";
            $resTotalExtrato = pg_query($con, $sqlTotalExtrato);
            if(pg_num_rows($resTotalExtrato)>0){
            	$total_extrato = pg_fetch_result($resTotalExtrato, 0, total);
            }

			$sqlLGR = "UPDATE tbl_faturamento_item SET
					extrato_devolucao = $extrato
					FROM tbl_os_item,tbl_faturamento,tbl_extrato, tbl_peca
					WHERE tbl_os_item.os_item = tbl_faturamento_item.os_item
					AND tbl_faturamento.posto = tbl_extrato.posto
					AND tbl_faturamento.fabrica = tbl_extrato.fabrica
					AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
					AND tbl_faturamento.fabrica = $fabrica
					AND tbl_faturamento.emissao >='2010-01-01'
					AND tbl_faturamento.emissao <='$data_15'
					AND tbl_faturamento.cancelada IS NULL
					AND tbl_faturamento_item.extrato_devolucao IS NULL
					AND tbl_peca.peca = tbl_os_item.peca
					AND tbl_os_item.peca_obrigatoria
					AND tbl_peca.aguarda_inspecao IS NOT TRUE
					AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
					AND tbl_extrato.extrato = $extrato";
			$resLGR = pg_query($con,$sqlLGR);
			$msg_erro .= pg_last_error($con);

			if(strlen(pg_last_error($con) > 0)){
				$log->adicionaLog("Erro LGR - $msg_erro");
        		$log->adicionaLog("linha");
        		$msg_erro .= pg_last_error($con);
			}

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

			if(strlen(pg_last_error($con) > 0)){
				$log->adicionaLog("Erro LGR - $msg_erro");
        		$log->adicionaLog("linha");
        		$msg_erro .= pg_last_error($con);
			}

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

			if(strlen(pg_last_error($con) > 0)) {
				$log->adicionaLog("Erro LGR - $msg_erro");
        		$log->adicionaLog("linha");     
        		$msg_erro .= pg_last_error($con);
			}

			/*
			if($total_extrato < 250){
				$msg_erro .= "Total do Extrato menor que R$ 250,00";
				$log->adicionaLog($msg_erro);  
        		$log->adicionaLog("linha");
			}
			 */
			if (strlen($msg_erro) > 0) {
				$resP = pg_query('ROLLBACK;');
				$bug .= $msg_erro;
				$vet['log'] = 2;
				Log::log2($vet, $msg_erro);     

				$msg_erro = ""; 
			} else {
				$resP = pg_query('COMMIT;');

			}

		}

	}else{
		if(strlen($msg_erro) > 0){
			$log->adicionaLog("Erro ao selecionar Postos para gerar Extrato");
        	$log->adicionaLog("linha");
		}
	}

	if (strlen($bug) > 0) {

		 //envia email para HelpDESK
	    if(!empty($erro)){
		    if($log->enviaEmails() == "200"){
	          echo "Log de erro enviado com Sucesso!";
	        }else{
	          echo $log->enviaEmails();
	        }
	    }    
	}

	$phpCron->termino();

} catch (Exception $e) {

	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);
    $log->adicionaLog($msg);

    if($log->enviaEmails() == "200"){
      echo "Log de erro enviado com Sucesso!";
    }else{
      echo $log->enviaEmails();
    }

}

