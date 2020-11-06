<?php
/**
 *
 * igera-pedido-os.php
 *
 * Geração de pedidos de troca com base na OS
 *
 * @author  Ronald Santos
 * @version 2013.01.21
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $vet['fabrica'] = 'newup';
    $vet['tipo'] 	= 'pedido';
    $vet['log'] 	= 2;
	$fabrica 		= 201;
    $data_sistema	= Date('Y-m-d');
    $logs_erro				= array();
	
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	if (ENV == 'teste' ) {
		$vet['dest'] 		= 'helpdesk@telecontrol.com.br';
    } else {
    	$vet['dest'] 		= 'ronald.santos@telecontrol.com.br';
    }

    $arquivo_err = "/tmp/newup/gera-pedido-troca-{$data_sistema}.err";
    $arquivo_log = "/tmp/newup/gera-pedido-troca-{$data_sistema}.log";
    system ("mkdir /tmp/newup/ 2> /dev/null ; chmod 777 /tmp/newup/" );

    $getDate = getDate();
	$dia_posto = "AND tbl_posto.posto NOT IN (6359,519914)";
	if ($getDate["wday"] == 4) {
		$dia_posto = "AND tbl_posto.posto <> 6359";
	} 
    
    $sql = "SELECT  DISTINCT
				tbl_posto.posto   ,
				tbl_produto.linha
			FROM    tbl_os_item
			JOIN    tbl_servico_realizado USING (servico_realizado)
			JOIN    tbl_os_produto        USING (os_produto)
			JOIN    tbl_os                USING (os)
			JOIN    tbl_posto             USING (posto)
			JOIN    tbl_produto           ON tbl_os.produto            = tbl_produto.produto
			JOIN    tbl_posto_fabrica     ON tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
			WHERE   tbl_os_item.pedido        IS NULL
			AND     tbl_os.excluida           IS NOT TRUE
			AND     tbl_os.validada           IS NOT NULL
			$dia_posto
			AND     tbl_os_troca.gerar_pedido IS TRUE
			AND     tbl_os.fabrica      = $fabrica";
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
			$linha = pg_result($res,$i,'linha');
			
			unset($logs_erro);
			
			$resultX = pg_query($con,"BEGIN TRANSACTION");

			$sql = "SELECT  tbl_os_troca.peca,
						tbl_os.os
					FROM    tbl_os
					JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
					JOIN    tbl_produto           ON tbl_os.produto  = tbl_produto.produto
					WHERE   tbl_os_troca.gerar_pedido IS TRUE
					AND     tbl_os_troca.pedido       IS NULL
					AND     tbl_os.fabrica    = $fabrica
					AND     tbl_os.posto      = $posto
					AND     tbl_produto.linha = $linha ";
			$result = pg_query($con, $sql);

			if(pg_last_error($con)){
				$logs_erro[] = $sql."<br>".pg_last_error($con);
			}
    
			if(pg_num_rows($result) > 0 AND count($logs_erro) == 0){
				
				for($x = 0; $x < pg_num_rows($result); $x++){
					$peca = pg_result($result,$x,'peca');
					$os   = pg_result($result,$x,'os');

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
													$condicao ,
													'$tipo_pedido'     ,
													TRUE      ,
													0
												) RETURNING pedido;";					
					$resultX = pg_query($con, $sql);
					if(pg_last_error($con)){
						$logs_erro[] = $sql."<br>".pg_last_error($con);
					} else {
						$pedido = pg_result($resultX,0,0);

						$sql = "SELECT total_troca FROM tbl_os_troca WHERE os = $os";
						$resultX = pg_query($con, $sql);

						if(pg_num_rows($resultX) > 0){
							$total_troca = pg_result($resultX,0,'total_troca');
						}


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
															1      ,
															0      ,
															0      ,
															't'
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


							$sql = "SELECT fn_atualiza_os_item_pedido_item (os_item,$pedido,$pedido_item,$fabrica)
									FROM tbl_os_item
									WHERE peca = $peca
									AND os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
							$resultX = pg_query($con, $sql);
							if(pg_last_error($con)){
								$logs_erro[] = $sql."<br>".pg_last_error($con);
							}

							$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
							$resultX = pg_query($con, $sql);
							
							if(pg_last_error($con)){
								$logs_erro[] = $sql."<br>".pg_last_error($con);
							}
						}
					}
				}
			}

			if (count($logs_erro)>0){
				$resultX = pg_query($con, "ROLLBACK TRANSACTION");
			}else{
				$resultX = pg_query($con,"COMMIT TRANSACTION");
			}
		}
	}

	if ($logs_erro) {

		$logs_erro = implode("<br>", $logs_erro);
		Log::log2($vet, $logs_erro);

	}

	if ($logs_erro) {

		Log::envia_email($vet, "Log de ERROS - Geração de Pedido de Troca de OS newup", $logs_erro);

	}

	$phpCron->termino();
    
} catch (Exception $e) {
	echo $e->getMessage();
}
