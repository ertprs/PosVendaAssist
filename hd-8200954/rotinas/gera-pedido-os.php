<?php

try {

	include dirname(__FILE__) . '/../dbconfig.php';
	include dirname(__FILE__) . '/../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/funcoes.php';

	date_default_timezone_set('America/Sao_Paulo');

	if (empty($argv[1])) {
		echo "ERRO: passagem de parâmetro: fábrica.\n";
		exit;
	}

	$fabrica     = strtolower($argv[1]);
	$login_posto = strtolower($argv[2]);
	$data        = date('d-m-Y');
	
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	function logErro($sql, $error_msg)
	{
		$err = "==============================\n\n";
		$err.= $sql . "\n\n";
		$err.= $error_msg . "\n\n";

		return $err;
	}

	$sql = "SELECT lower(nome) as nome FROM tbl_fabrica WHERE fabrica = $fabrica";
	$result_f = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);
	if (!empty($msg_erro)) {
		echo "ERRO: nao foi possível determinar fábrica.\n\n    $msg_erro";
		exit;
	}
	$fabrica_nome =  pg_fetch_result($result_f, 0, 'nome');

	$vet['fabrica'] = $fabrica_nome;
	$vet['dest']    = 'helpdesk@telecontrol.com.br';

	$dir = '/tmp/' . $vet['fabrica'] . '/pedidos';
	if (!is_dir($dir)) {
		if (!mkdir($dir, 0777, true)) {
			throw new Exception("ERRO: Falha ao criar diretório de processamento: $dir");
		}
	}

	$log = 'gera-pedido-os.log';
	$erro_log = 'gera-pedido-os.err';

	$nlog = fopen($dir . '/' . $log, "w");
	$elog = fopen($dir . '/' . $erro_log, "w");

	$setDate = pg_query($con, "SET DateStyle TO 'SQL,EUROPEAN'");
	if (pg_last_error($con)) {
		$log_erro = logErro("SET DateStyle TO 'SQL,EUROPEAN'", pg_last_error($con));
		fwrite($elog, $log_erro);
		fclose($nlog);
		fclose($elog);
		throw new Exception(pg_last_error($con));
	}

	if (!empty($login_posto)) {
		$cond = " AND tbl_posto.posto = $login_posto ";
	} else {
		$cond = "";
	}

	if ($fabrica == 59) {
		$condPosto = " tbl_posto_fabrica.posto NOT IN(6359,56319,57188,149099) ";
	} else{
		$condPosto = " NOT (tbl_posto_fabrica.posto = 6359 ) ";
	}

	$sql = "SELECT
				tbl_os.posto        ,
				tbl_produto.linha   ,
				tbl_os_item.peca    ,
				tbl_os_item.os_item ,
				tbl_os_item.qtde    ,
				tbl_os.sua_os
			INTO TEMP tmp_pedido_$fabrica
			FROM    tbl_os_item
			JOIN    tbl_servico_realizado USING (servico_realizado)
			JOIN    tbl_os_produto USING (os_produto)
			JOIN    tbl_os         USING (os)
			JOIN    tbl_posto      USING (posto)
			JOIN    tbl_produto
				ON tbl_os.produto            = tbl_produto.produto
			JOIN    tbl_posto_fabrica
				ON tbl_posto_fabrica.posto   = tbl_os.posto
				AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			WHERE
				$condPosto
				AND     tbl_os_item.pedido IS NULL
				AND     tbl_os.excluida    IS NOT TRUE
				AND     tbl_os.validada    IS NOT NULL
				AND     tbl_servico_realizado.gera_pedido
				AND     tbl_os.fabrica      = $fabrica
				$cond
				AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				OR      tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')
				AND     tbl_os.troca_garantia       IS NULL
				AND     tbl_os.troca_garantia_admin IS NULL

			AND   tbl_os.os NOT IN (
			SELECT interv.os
			FROM (SELECT
					ultima.os,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$fabrica and tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
					FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica and status_os IN (62,64,147) ) ultima
				) interv
			WHERE interv.ultimo_status IN (62,147)
			)";

	$result_p = pg_query($con, $sql);

	if (pg_last_error($con)) {
		$log_erro = logErro($sql, pg_last_error($con));
		fclose($nlog);
		fclose($elog);
		throw new Exception(pg_last_error($con));
	}

	$sql = "SELECT DISTINCT(posto) AS posto, linha FROM tmp_pedido_$fabrica GROUP BY posto, linha";
	$result_px = pg_query($con, $sql);

	if (pg_last_error($con)) {
		$log_erro = logErro($sql, pg_last_error($con));
		fwrite($elog, $log_erro);
		fclose($nlog);
		fclose($elog);
		throw new Exception(pg_last_error($con));
	}

	$numrows = pg_num_rows($result_px);

	for ($i = 0; $i < $numrows; $i++) {
		$erro = 0;
		$posto = pg_fetch_result($result_px, $i, 'posto');
		$linha = pg_fetch_result($result_px, $i, 'linha');

		$sql = "SELECT
					SUM(qtde) AS qtde,
					peca
				FROM tmp_pedido_$fabrica
					WHERE posto = $posto
					AND   linha = $linha
				GROUP BY peca";
		$result2 = pg_query($con, $sql);
		$numrows2 = pg_num_rows($result2);

		if (pg_last_error($con)) {
			$log_erro = logErro($sql, pg_last_error($con));
			fwrite($elog, $log_erro);
			$erro = 1;
		}

		$res = pg_query($con, "BEGIN TRANSACTION");

		#Garantia
		$sql = "select condicao from tbl_condicao where fabrica = ".$fabrica." and lower(descricao) = 'garantia'";
		$resultG = pg_query($con, $sql);

		if (pg_last_error($con)) {
			$log_erro = logErro($sql, pg_last_error($con));
			fwrite($elog, $log_erro);
			$erro = 1;
		}

		$condicao = pg_fetch_result($resultG, 0, 'condicao');

		#Tipo_pedido
		$sql = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$fabrica." and lower(descricao) = 'garantia'";
		$resultP = pg_query($con,$sql);

		if (pg_last_error($con)) {
			$log_erro = logErro($sql, pg_last_error($con));
			fwrite($elog, $log_erro);
			$erro = 1;
		}

		$tipo_pedido = pg_fetch_result($resultP, 0, 'tipo_pedido');

		if ($fabrica == 88){
			$tipo_frete = 'NOR';
		} else {
			$tipo_frete = ' ';
		}

		$sql = "INSERT INTO tbl_pedido
				(
					posto        ,
					fabrica      ,
					condicao     ,
					tipo_pedido  ,
					linha        ,
					tipo_frete   ,
					status_pedido
				) VALUES (
					$posto      ,
					$fabrica    ,
					$condicao   ,
					$tipo_pedido,
					$linha      ,
					'$tipo_frete',
					1
				)";
		$resultX  = pg_query($con, $sql);

		if (pg_last_error($con)) {
			$log_erro = logErro($sql, pg_last_error($con));
			fwrite($elog, $log_erro);
			$erro = 1;
		}

		$sql = "SELECT currval ('seq_pedido') AS pedido";
		$resultX = pg_query($con, $sql);

		if (pg_last_error($con)) {
			$log_erro = logErro($sql, pg_last_error($con));
			fwrite($elog, $log_erro);
			$erro = 1;
		}

		$pedido = pg_result($resultX, 0, 'pedido');

		for ($j = 0; $j < $numrows2; $j++) {
			$peca = pg_fetch_result($result2, $j, 'peca');
			$qtde = pg_fetch_result($result2, $j, 'qtde');

			$sql = "INSERT INTO tbl_pedido_item (
						pedido,
						peca  ,
						qtde  ,
						qtde_faturada,
						qtde_cancelada
					) VALUES (
						$pedido,
						$peca  ,
						$qtde  ,
						0      ,
						0      )";
			$resultX  = pg_query($con,$sql);

			if (pg_last_error($con)) {
				$log_erro = logErro($sql, pg_last_error($con));
				fwrite($elog, $log_erro);
				$erro = 1;
			}

			$sql = "SELECT CURRVAL ('seq_pedido_item') AS pedido_item";
			$resultX = pg_query($con,$sql);

			if (pg_last_error($con)) {
				$log_erro = logErro($sql, pg_last_error($con));
				fwrite($elog, $log_erro);
				$erro = 1;
			}

			$pedido_item = pg_fetch_result($resultX, 0, 'pedido_item');

			$sql = "SELECT fn_atualiza_os_item_pedido_item(os_item, $pedido, $pedido_item, $fabrica)
					FROM   tmp_pedido_$fabrica
					WHERE  tmp_pedido_$fabrica.peca  = $peca
					AND    tmp_pedido_$fabrica.posto = $posto
					AND    tmp_pedido_$fabrica.linha = $linha";
			$resultX = pg_query($con,$sql);

			if (pg_last_error($con)) {
				$log_erro = logErro($sql, pg_last_error($con));
				fwrite($elog, $log_erro);
				$erro = 1;
			}

		}

		$sql = "SELECT fn_pedido_finaliza ($pedido, $fabrica)";
		$resultX  = pg_query($con,$sql);

		if (pg_last_error($con)) {
			$log_erro = logErro($sql, pg_last_error($con));
			fwrite($elog, $log_erro);
			$erro = 1;
		}

		if ($erro == 1) {
			pg_query($con, "ROLLBACK TRANSACTION");

			$sqlY = "SELECT DISTINCT codigo_posto,
						tmp_pedido_$fabrica.sua_os,
						referencia,
						qtde,
						tbl_tabela_item.preco
					FROM tmp_pedido_$fabrica
					JOIN tbl_posto_fabrica
						ON tbl_posto_fabrica.posto = tmp_pedido_$fabrica.posto
						and tbl_posto_fabrica.fabrica = $fabrica
					JOIN tbl_peca USING(peca)
					JOIN tbl_posto_linha
						ON tbl_posto_linha.posto     = tmp_pedido_$fabrica.posto
					JOIN tbl_tabela_item
						ON tbl_tabela_item.peca      = tmp_pedido_$fabrica.peca
						and tbl_tabela_item.tabela    = tbl_posto_linha.tabela
					JOIN tbl_tabela
						ON tbl_tabela.tabela = tbl_tabela_item.tabela
						AND tbl_tabela.fabrica = $fabrica";
			$resultY = pg_query($con, $sqlY);
			$rowsY = pg_num_rows($resultY);

			if ($rowsY > 0) {
				while ($fetch = pg_fetch_array($resultY)) {
					$codigo_posto = $fetch['codigo_posto'];
					$sua_os       = $fetch['sua_os'];
					$referencia   = $fetch['referencia'];
					$qtde         = $fetch['qtde'];
					$preco        = $fetch['preco'];

					$log = "Posto:".$codigo_posto." - OS:".$sua_os." - Peça:".$referencia." - Qtde:".$qtde." - Preço:".$preco."\r \n";
					fwrite($nlog, $log);
				}

			}

		} else {
			pg_query($con,"COMMIT TRANSACTION");
		}

	}

	fclose($nlog);
	fclose($elog);

	if (file_exists($dir . '/' . $log) AND filesize($dir . '/' . $log) > 0) {
		$contents = file_get_contents($dir . '/' . $log);
		$subj = $vet['fabrica'] . ' - Erros ao criar Pedidos com base nas OSs';
	    $msg = "Alguns pedidos não foram criados a partir de suas OS, e serão gerados automaticamente assim que os problemas forem solucionados.\n";
		$msg.= "<br/><br/>\n";
		$msg.= "<b>Verifique tabelas de preços, cadastro de peças, etc.</b>\n";
		$msg.= "<br/><br/>\n";
		$msg.= str_replace("\n", "<br/>\n", $contents);
		Log::envia_email($vet, $subj, $msg);
	}

	if (file_exists($dir . '/' . $erro_log) AND filesize($dir . '/' . $erro_log) > 0) {
		$contents = file_get_contents($dir . '/' . $erro_log);
		$subj = $vet['fabrica'] . ' - Erros ao criar Pedidos com base nas OSs';
	    $msg = "Alguns pedidos não foram criados a partir de suas OS, e serão gerados automaticamente assim que os problemas forem solucionados.\n";
		$msg.= "<br/><br/>\n";
		$msg.= "<b>Verifique tabelas de preços, cadastro de peças, etc.</b>\n";
		$msg.= "<br/><br/>\n";
		$msg.= str_replace("\n", "<br/>\n", $contents);
		Log::envia_email($vet, $subj, $msg);
	}
	
	$phpCron->termino();

} catch (Exception $e) {
	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	Log::envia_email($vet,APP, $msg );
}

