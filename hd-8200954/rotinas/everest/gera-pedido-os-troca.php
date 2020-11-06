<?php
/**
 *
 * gera-pedido-os.php
 *
 * Geração de pedidos de troca com base na OS
 *
 * @author  William Ap. Brandino
 * @version 2016.08.29
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';

    $vet['fabrica_nome'] = 'everest';
    $vet['tipo']    = 'pedido';
    $vet['log']     = 2;
	$vet['fabrica'] = '94';
	$fabrica 		= 94;
    $data_sistema	= Date('Y-m-d');
    $logs_erro				= array();

	if (ENV != 'teste' ) {
		$vet['dest'] 		= 'helpdesk@telecontrol.com.br';
    } else {
    	$vet['dest'] 		= 'william.brandino@telecontrol.com.br';
    }

    $arquivo_err = "/tmp/everest/gera-pedido-troca-{$data_sistema}.err";
    $arquivo_log = "/tmp/everest/gera-pedido-troca-{$data_sistema}.log";
    system ("mkdir /tmp/everest/ 2> /dev/null ; chmod 777 /tmp/everest/" );


    $sql = "SELECT  DISTINCT
				tbl_os.posto
			FROM    tbl_os_item
			JOIN    tbl_servico_realizado USING (servico_realizado)
			JOIN    tbl_os_produto        USING (os_produto)
			JOIN    tbl_os                USING (os)
			JOIN    tbl_posto_fabrica     ON tbl_posto_fabrica.posto   = tbl_os.posto
					AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			JOIN 	tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
					AND tbl_tipo_posto.posto_interno IS NOT TRUE
			JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os and tbl_os_troca.fabric = tbl_os.fabrica
			WHERE   tbl_os_troca.pedido        IS NULL
			AND     tbl_os.excluida           IS NOT TRUE
			AND     tbl_os.validada           IS NOT NULL
			AND     tbl_os_troca.gerar_pedido IS TRUE
			AND     tbl_os.fabrica      = $fabrica";
	$res = pg_query($con,$sql);

	if(pg_last_error($con)){
    	$logs_erro[] = $sql."<br>".pg_last_error($con);
    }

	if(pg_num_rows($res) > 0 AND count($logs_erro) == 0){

		for($i = 0; $i < pg_num_rows($res); $i++){
			$posto = pg_result($res,$i,'posto');

			$logs_erro = array();

			$sql = "SELECT  1 as qtde, tbl_os_troca.peca,
                            tbl_os.os
                    INTO TEMP tmp_pedido_troca_{$posto}
                    FROM    tbl_os
					JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
					JOIN    tbl_produto           ON tbl_os.produto  = tbl_produto.produto
					WHERE   tbl_os_troca.gerar_pedido IS TRUE
					AND     tbl_os_troca.pedido       IS NULL
					AND     tbl_os.fabrica    = $fabrica
					AND     tbl_os.posto      = $posto";
			$result = pg_query($con,$sql);

			if(pg_last_error($con)){
				$logs_erro[] = $sql."<br>".pg_last_error($con);
			}

            $sql = "SELECT count(qtde) AS qtde, peca FROM tmp_pedido_troca_{$posto} GROUP BY peca";
			$result = pg_query($con,$sql);

			if(pg_num_rows($result) > 0 AND count($logs_erro) == 0){
                $resultX = pg_query($con,"BEGIN TRANSACTION");
                $sql = "INSERT INTO tbl_pedido (
                                                posto     ,
                                                fabrica   ,
                                                condicao  ,
                                                tipo_pedido,
                                                troca      ,
                                                total
                                            ) VALUES (
                                                $posto    ,
                                                $fabrica  ,
                                                '1755'     ,
                                                '211'     ,
                                                TRUE      ,
                                                0
                                            ) RETURNING pedido;";
//                                             echo $sql;exit;
                $resultX = pg_query($con,$sql);
                $pedido = pg_result($resultX,0,0);
                if(pg_last_error($con)){
                    $logs_erro[] = $sql."<br>".pg_last_error($con);
                } else {
                    for($x = 0; $x < pg_num_rows($result); $x++){
                        $peca = pg_result($result,$x,'peca');
                        $qtde = pg_result($result,$x,'qtde');

                        if (empty($peca)) {
                            continue;
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
															$qtde  ,
															0      ,
															0      ,
															't'
														) RETURNING pedido_item";
						$resultZ = pg_query($con,$sql);
						if(pg_last_error($con)){
							$logs_erro[] = $sql."<br>".pg_last_error($con);
						} else {
							$pedido_item = pg_result($resultZ,0,0);
//  echo "==>".$pedido_item."\r\n";

                            $sql = "UPDATE tbl_os_troca SET pedido = $pedido, pedido_item = $pedido_item
                                WHERE os IN (
                                    SELECT os FROM tmp_pedido_troca_{$posto} WHERE peca = $peca
                                )";
							$resultUp = pg_query($con,$sql);
							if(pg_last_error($con)){
								$logs_erro[] = $sql."<br>".pg_last_error($con);
							}


							$sql = "SELECT fn_atualiza_os_item_pedido_item (os_item,$pedido,$pedido_item,$fabrica)
									FROM tbl_os_item
									WHERE peca = $peca
                                    AND os_produto IN (
                                        SELECT os_produto FROM tbl_os_produto WHERE os IN (
                                           SELECT os FROM tmp_pedido_troca_{$posto} WHERE peca = $peca
                                       )
                                    )";
							$resultFn = pg_query($con,$sql);
							if(pg_last_error($con)){
								$logs_erro[] = $sql."<br>".pg_last_error($con);
							}

							
						}
					}

                    $sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
                    $resultFnP = pg_query($con,$sql);

                    if(pg_last_error($con)){
                        $logs_erro[] = $sql."<br>".pg_last_error($con);
                    }
				}

				if (count($logs_erro)>0){
                    $resultx = pg_query($con, "ROLLBACK TRANSACTION");
                }else{
                    $resultx = pg_query($con,"COMMIT TRANSACTION");
                }
			}
		}
	}

	if (count($logs_erro) > 0 ) {
		$logs_erro = implode("<br>", $logs_erro);
		Log::log2($vet, $logs_erro);
	}

	if ($logs_erro) {

		Log::envia_email($vet, "Log de ERROS - Geração de Pedido de Troca de OS Everest", $logs_erro);

	}

// var_dump($logs_erro);

} catch (Exception $e) {
	echo $e->getMessage();
}
