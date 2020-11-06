<?php
/**
 *
 * gera-pedido-troca-lote.php
 *
 * Geração de pedidos de troca com base na OS
 *
 * @author  Ronald Santos
 * @version 2013.07.04
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	if (!empty($argv[1])) {
		$distribuidor = (strtolower($argv[1]) == '4311') ? " AND     tbl_os_troca.distribuidor = 4311 " : " AND     tbl_os_troca.distribuidor isnull ";
	}else{
		$distribuidor = " AND     tbl_os_troca.distribuidor isnull ";
	}

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $vet['fabrica'] = 'bestway';
    $vet['tipo'] 	= 'pedido';
    $vet['log'] 	= 2;
	$fabrica 		= 81;
    $data_sistema	= Date('Y-m-d');
    $logs_erro				= array();

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

    if (ENV == 'producao' ) {
		$vet['dest'] 		= 'helpdesk@telecontrol.com.br';
    } else {
    	$vet['dest'] 		= 'ronald.santos@telecontrol.com.br';
    }

    $arquivo_err = "/tmp/bestway/gera-pedido-troca-{$data_sistema}.err";
    $arquivo_log = "/tmp/bestway/gera-pedido-troca-{$data_sistema}.log";
    system ("mkdir /tmp/bestway/ 2> /dev/null ; chmod 777 /tmp/bestway/" );

	require __DIR__ . '/../../classes/Posvenda/Model/Os.php';
	$osModel = new \Posvenda\Model\Os($fabrica);

    $sql = "SELECT  DISTINCT
		tbl_posto.posto   ,
		tbl_produto.linha
		FROM    tbl_os
		JOIN    tbl_posto      USING (posto)
		JOIN    tbl_produto          ON tbl_os.produto            = tbl_produto.produto
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
		JOIN    tbl_os_troca         ON tbl_os_troca.os = tbl_os.os
		WHERE   NOT tbl_os.excluida
		AND     tbl_posto.posto <> 6359
		AND     tbl_os_troca.gerar_pedido IS TRUE
		AND     tbl_os_troca.ressarcimento IS FALSE
		AND     tbl_os_troca.troca_revenda IS FALSE
		AND     tbl_os_troca.pedido IS NULL
		AND     tbl_os_troca.obs_causa = 'troca_lote'
		$distribuidor
		AND     tbl_os.fabrica      = $fabrica
		AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO' ) ";
	$res = pg_query($con, $sql);

	if(pg_last_error($con)){
    	$logs_erro[] = $sql."<br>".pg_last_error($con);
    }

	if(pg_num_rows($res) > 0 AND count($logs_erro) == 0){

		for($i = 0; $i < pg_num_rows($res); $i++){
			$posto = pg_result($res,$i,'posto');
			$linha = pg_result($res,$i,'linha');

			$resultT = pg_query($con,"BEGIN TRANSACTION");

			$sql = "INSERT INTO tbl_pedido (
											posto     ,
											fabrica   ,
											linha     ,
											condicao  ,
											tipo_pedido,
											troca      ,
											total
										) VALUES (
											$posto    ,
											$fabrica  ,
											$linha    ,
											1397 ,
											'154'     ,
											TRUE      ,
											0
										) RETURNING pedido;";
			$resultK = pg_query($con, $sql);
			if(pg_last_error($con)){
				$erro .= $sql."<br>".pg_last_error($con);
			} else {
				$pedido = pg_result($resultK,0,0);
			}

			$sql = "SELECT  DISTINCT tbl_os.os
					FROM    tbl_os
					JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
					JOIN    tbl_produto           ON tbl_os.produto  = tbl_produto.produto
					JOIN    tbl_os_produto        ON tbl_os_troca.os = tbl_os_produto.os
					JOIN    tbl_os_item           ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					WHERE   tbl_os_troca.gerar_pedido  IS TRUE
					AND     tbl_os_troca.pedido        IS NULL
					AND     tbl_os.excluida IS NOT TRUE
					AND     tbl_os_troca.ressarcimento IS FALSE
					AND     tbl_os_troca.troca_revenda IS FALSE
					AND     tbl_os_item.servico_realizado IN(7658)
					AND     tbl_os.fabrica    = $fabrica
					AND     tbl_os.posto      = $posto
					AND     tbl_produto.linha = $linha
					$distribuidor
					AND     tbl_os_troca.obs_causa = 'troca_lote'";
            $resultX = pg_query($con, $sql);

            for($j = 0; $j < pg_num_rows($resultX); $j++){

            	$os = pg_result($resultX,$j,'os');

            	if (!$osModel->consultaAuditoriaOS($os)) {
            		continue;
            	}

				$sql = "SELECT 	peca,
								os_item,
								qtde
							FROM tbl_os_produto
							JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
							JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
							AND     tbl_os_item.servico_realizado IN(7658)
							AND tbl_servico_realizado.troca_produto IS TRUE
							WHERE tbl_os_produto.os = $os
							AND tbl_os_item.pedido IS NULL";
				$result = pg_query($con, $sql);

				if(pg_last_error($con)){
					$erro .= $sql."<br>".pg_last_error($con);
				}

				if(pg_num_rows($result) > 0 AND strlen($erro) == 0){

					for($x = 0; $x < pg_num_rows($result); $x++){

						$peca 		= pg_fetch_result($result,$x,'peca');
						$os_item 	= pg_fetch_result($result,$x,'os_item');
						$qtde   	= pg_fetch_result($result,$x,'qtde');

						$erro = "";

						$sql = "INSERT INTO tbl_pedido_item (
															pedido,
															peca  ,
															qtde  ,
															qtde_faturada,
															qtde_cancelada,
															troca_produto
														) VALUES (
															$pedido,
															$peca  ,
															$qtde  ,
															0      ,
															0      ,
															't'
														) RETURNING pedido_item";
						$resultK = pg_query($con, $sql);

						if(pg_last_error($con)){
							$erro .= $sql."<br>".pg_last_error($con);
						} else {
							$pedido_item = pg_fetch_result($resultK,0,0);

							$sql = "UPDATE tbl_os_troca SET pedido = $pedido, pedido_item = $pedido_item WHERE os = $os AND pedido isnull AND obs_causa = 'troca_lote'";
							$resultK = pg_query($con, $sql);
							if(pg_last_error($con)){
								$erro .= $sql."<br>".pg_last_error($con);
							}


							$sql = "SELECT fn_atualiza_os_item_pedido_item($os_item,$pedido,$pedido_item,$fabrica)";
							$resultK = pg_query($con, $sql);
							if(pg_last_error($con)){
								$erro .= $sql."<br>".pg_last_error($con);
							}
						}

					}


				}

            }

            $sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
			$resultK = pg_query($con, $sql);
			if(pg_last_error($con)){
				echo $erro .= "OS: $os - " . pg_last_error($con) ;
			}

			if (strlen($erro)>0){
				$erro = preg_replace('/ERROR: /','',$erro);
				$erro = preg_replace('/CONTEXT:  .+\nPL.+/','',$erro);
				$resultK = pg_query($con, "ROLLBACK TRANSACTION");
				$logs_erro[] = $erro;
				$erro="";
			}else{
				$resultK = pg_query($con,"COMMIT TRANSACTION");
			}

		}
	}

	if ($logs_erro) {

		$logs_erro = implode("<br>", $logs_erro);
		Log::log2($vet, $logs_erro);

	}

	if ($logs_erro) {

		Log::envia_email($vet, "Log de ERROS - Geração de Pedido de Troca de OS Bestway", $logs_erro);

	}

    $phpCron->termino();

} catch (Exception $e) {
	echo $e->getMessage();
}
