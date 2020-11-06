<?php

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	/* Class Pedido */
    include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';

    $vet['fabrica'] = 'elginautomacao';
    $vet['tipo'] 	= 'pedido';
    $vet['log'] 	= 2;
    $fabrica 		= 156;
    $data_sistema	= Date('Y-m-d');
    $logs_erro				= array();

	if (ENV != 'teste' ) {
		$vet['dest'] 		= 'helpdesk@telecontrol.com.br';
		$arquivo_err = "/tmp/".$vet['fabrica']."/gera-pedido-troca-{$data_sistema}.err";
    		$arquivo_log = "/tmp/".$vet['fabrica']."/gera-pedido-troca-{$data_sistema}.log";
    		$posto_teste = " AND tbl_posto_fabrica.posto  NOT IN (6359) ";
    	}else{
    		$vet['dest'] 		= 'william.lopes@telecontrol.com.br';
    	}


    $sql = "SELECT
		        DISTINCT tbl_os.os,
		        tbl_os.posto
		    FROM tbl_os
		    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
		    INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		    INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE
		    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$fabrica}
		    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
		    INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.produto = tbl_os_produto.produto
		    WHERE tbl_os.fabrica = {$fabrica}
		    AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
		    AND tbl_servico_realizado.gera_pedido IS TRUE
		    AND tbl_servico_realizado.troca_produto IS TRUE
		    AND tbl_os.excluida IS NOT TRUE
		    AND tbl_os.validada IS NOT NULL
		    AND tbl_peca.produto_acabado IS TRUE
		    AND tbl_os_item.pedido IS NULL
		    {$posto_teste}
		    ";


	$res = pg_query($con, $sql);

	if(pg_last_error($con)){
    	$logs_erro[] = $sql."<br>".pg_last_error($con);
    }

    #Garantia
	$sql = "select condicao from tbl_condicao where fabrica = ".$fabrica." and lower(descricao) = 'garantia';";
	$resultG = pg_query($con, $sql);
	if(pg_last_error($con)){
		$logs_erro[] = $sql."<br>".pg_last_error($con);
	}else{
		$condicao = pg_result($resultG,0,'condicao');
	}

	#Tipo_pedido
	$sql = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$fabrica." and lower(descricao) = 'garantia';";
	$resultP = pg_query($con, $sql);
	if(pg_last_error($con)){
		$logs_erro[] = $sql."<br>".pg_last_error($con);
	}else{
		$tipo_pedido = pg_result($resultP,0,'tipo_pedido');
	}

	if(pg_num_rows($res) > 0 AND count($logs_erro) == 0){

		for($i = 0; $i < pg_num_rows($res); $i++){
			$posto = pg_result($res,$i,'posto');
			$os = pg_result($res,$i,"os");
			$finalizado = date("Y-m-d H:i:s");

			unset($logs_erro);

			// $resultX = pg_query($con,"BEGIN TRANSACTION");
			$pedidoClass = new \Posvenda\Pedido($fabrica);

			$tabela = $pedidoClass->_model->getTabelaPreco($posto, $tipo_pedido);

			$sql = "INSERT INTO tbl_pedido (
						posto     ,
						fabrica   ,
						condicao  ,
						tipo_pedido,
						troca      ,
						total,
						status_pedido,
						finalizado,
						exportado,
						tabela
					) VALUES (
						$posto    ,
						$fabrica  ,
						$condicao ,
						'$tipo_pedido'     ,
						TRUE      ,
						0,
						2,
						'$finalizado',
						'$finalizado',
						$tabela
					) RETURNING pedido;";
			$resultX = pg_query($con, $sql);
			$pedido = pg_result($resultX,0,0);

			$pedidoClass->setPedido($pedido);

			if(pg_last_error($con)){
				$logs_erro[] = $sql."<br>".pg_last_error($con);
			}

			$sql = "SELECT  tbl_os_item.os_item, tbl_os_item.peca, tbl_os_item.qtde
					FROM    tbl_os
					JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
					JOIN    tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					JOIN    tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					WHERE   tbl_os_troca.gerar_pedido IS TRUE
					AND     tbl_os_troca.pedido       IS NULL
					AND     tbl_os_troca.peca = tbl_os_item.peca
					AND     tbl_os.fabrica    = $fabrica
					AND     tbl_os.posto      = $posto
					AND     tbl_os_troca.os = $os";

			$result = pg_query($con, $sql);

			if(pg_last_error($con)){
				$logs_erro[] = $sql."<br>".pg_last_error($con);
			}

			if(pg_num_rows($result) > 0 AND count($logs_erro) == 0){

				for($x = 0; $x < pg_num_rows($result); $x++){
					$peca = pg_result($result,$x,'peca');
					$os_item   = pg_result($result,$x,'os_item');
					$qtde  = pg_result($result,$x,"qtde");

					$preco = $pedidoClass->getPrecoPecaGarantia($peca, $os);

					$sql = "INSERT INTO tbl_pedido_item (
								pedido,
								peca  ,
								qtde  ,
								qtde_faturada,
								qtde_cancelada,
								troca_produto,
								preco
							) VALUES (
								$pedido,
								$peca  ,
								$qtde      ,
								0      ,
								0      ,
								't',
								$preco
							) RETURNING pedido_item";
					$resultX = pg_query($con, $sql);

					if(pg_last_error($con)){
						$logs_erro[] = $sql."<br>".pg_last_error($con);
					} else {
						$pedido_item = pg_result($resultX,0,0);

						$sql = "UPDATE tbl_os_troca SET pedido = $pedido, pedido_item = $pedido_item WHERE os = $os";
						$resultX = pg_query($con, $sql);
						if(pg_last_error($con)){
							$logs_erro[] = $sql."<br>".pg_last_error($con);
						}

						$msg_erro = $pedidoClass->atualizaOsItemPedidoItem($os_item, $pedido, $pedido_item, $fabrica, $con);

						if(!empty($msg_erro)){
		        			$logs_erro[] = "<br>".$msg_erro;
		        		}

						/* $sql = "SELECT fn_atualiza_os_item_pedido_item ($os_item,$pedido,$pedido_item,$fabrica)";
						$resultX = pg_query($con, $sql);
						if(pg_last_error($con)){
							$logs_erro[] = $sql."<br>".pg_last_error($con);
						} */
					}
				}

				/* $sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
				$resultX = pg_query($con, $sql);

				if(pg_last_error($con)){
					$logs_erro[] = $sql."<br>".pg_last_error($con);
				} */

			}

			if (count($logs_erro)>0){
				// $resultX = pg_query($con, "ROLLBACK TRANSACTION");
			}else{
				// $resultX = pg_query($con,"COMMIT TRANSACTION");
			}

			$pedidoClass->finaliza($pedido);
			unset($pedidoClass);

		}
	}

	if (count($logs_erro) > 0 ) {
		$logs_erro = implode("<br>", $logs_erro);
		Log::log2($vet, $logs_erro);

	}

	if ($logs_erro) {
		Log::envia_email($vet, "Log de ERROS - Geração de Pedido de Troca de OS V8 Brasil", $logs_erro);

	}


} catch (Exception $e) {
	echo $e->getMessage();
}
