<?php
/**
 *
 * igera-pedido-os.php
 *
 * Geração de pedidos de pecas com base na OS Troca
 *
 * @author  Éderson Sandre
 * @version 2012.04.12
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $fabricas = array(
        11 => "lenoxx",
        172 => "pacific",
    );

    if (array_key_exists(1, $argv)) {
        $data['fabrica'] = $argv[1];
    } else {
        $data['fabrica']      = 11;
    }

    if (!array_key_exists($data['fabrica'], $fabricas)) {
        die("ERRO: argumento inválido - " . $data['fabrica'] . "\n");
    }

    $data['fabrica_nome'] = $fabricas[$data['fabrica']];
    $data['arquivo_log'] 	= 'gera_pedido_os_troca';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $logs 					= array();
    $log_cliente 			= array();
    $erro 					= false;
	$data_sistema 			= Date('Y-m-d');
	
    $phpCron = new PHPCron($data['fabrica'], __FILE__);
	$phpCron->inicio();

	if (ENV == 'producao' ) {
		$data['dest'] 			= 'helpdesk@telecontrol.com.br';
		$data['dest_cliente']   = 'erasmo@lenoxxsound.com.br';
    } else {
    	$data['dest'] 			= 'ederson.sandre@telecontrol.com.br';
    	$data['dest_cliente'] 	= 'ederson.sandre@gmail.com';
    }

    extract($data);

    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$data_sistema}_{$arquivo_log}.err";
    $new_arq_log = "{$arquivos}/{$fabrica_nome}/{$data_sistema}_gera_pedido_os_troca.txt";
echo $new_arq_log;
    $fl          = fopen($arquivo_log,"w+");
    $new_fl      = fopen($new_arq_log,"w+");

    $sql = "SET DateStyle TO 'SQL,EUROPEAN'";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs["ERROR"]["data"][] = ["sql" => $sql, "error" => pg_last_error($con)];
    	$erro   = true;
    } else {
    	$logs["SUCCESS"]["data"][] = ["sql" => $sql]; 
    }

    $sql = "SELECT  DISTINCT
				tbl_posto.posto   				,
				tbl_produto.linha				,
				tbl_posto_fabrica.codigo_posto
			FROM    tbl_os_item
				JOIN    tbl_servico_realizado USING (servico_realizado)
				JOIN    tbl_os_produto        USING (os_produto)
				JOIN    tbl_os                USING (os)
				JOIN    tbl_posto             USING (posto)
				JOIN    tbl_produto           ON tbl_os.produto            = tbl_produto.produto
				JOIN    tbl_posto_fabrica     ON tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
			WHERE  tbl_os_item.pedido        IS NULL
                AND     tbl_os_troca.pedido       IS NULL /* hd 235047 */
                AND     tbl_os.excluida           IS NOT TRUE
                AND     tbl_os.validada           IS NOT NULL
                AND     tbl_posto.posto           <> 6359
                AND     tbl_os_troca.gerar_pedido  IS TRUE
                AND     tbl_os_troca.ressarcimento IS NOT TRUE
                AND     tbl_os.fabrica      = $fabrica
                AND     tbl_os_troca.fabric = $fabrica";
    $res = pg_query($con, $sql);
    
    if(pg_last_error($con)){
    	$logs["ERROR"]["select_os_item"][] = ["sql" => $sql, "error" => pg_last_error($con)];
    	$erro   = true;
    } else {
    	$logs["SUCCESS"]["select_os_item"][] = ["sql" => $sql];
    }

    if(pg_num_rows($res) > 0 AND !$erro){
    	for($i = 0; $i < pg_num_rows($res); $i++){
    		extract(pg_fetch_array($res));

    		pg_query($con, "BEGIN TRANSACTION");
    		$erro_transaction = false;

    		$sql = "SELECT  tbl_os_troca.peca,
							tbl_os.os
					FROM tbl_os
						JOIN tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
						JOIN tbl_produto           ON tbl_os.produto  = tbl_produto.produto
					WHERE tbl_os_troca.gerar_pedido  IS TRUE
	                    AND tbl_os_troca.pedido        IS NULL
	                    AND tbl_os_troca.ressarcimento IS NOT TRUE
	                    AND tbl_os.excluida           IS NOT TRUE
	                    AND tbl_os.validada           IS NOT NULL
	                    AND tbl_os.fabrica      = $fabrica
	                    AND tbl_os.posto        = $posto
	                    AND tbl_produto.linha   = $linha 
	                    AND tbl_os_troca.fabric = $fabrica
	                    AND tbl_os.os NOT IN (
	                            SELECT interv.os
	                                            FROM (
	                                            SELECT 
	                                            ultima.os, 
	                                            (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND tbl_os_status.os = ultima.os AND status_os IN (158,159,160) ORDER BY data DESC LIMIT 1) AS ultimo_status
	                                            FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND status_os IN (158,159,160) ) ultima
	                                            ) interv
	                                            WHERE interv.ultimo_status IN (158,160));";
			$res1 = pg_query($con, $sql);
			
			if(pg_last_error($con)){
				$logs["ERROR"]["select_os"][] = ["sql" => $sql, "error" => pg_last_error($con)];
				$erro   			= true;
				$erro_transaction 	= true;
			} else {
				$logs["SUCCESS"]["select_os"][]	= ["sql" => $sql];
			}

			if(pg_num_rows($res1) > 0 AND !$erro){
				for($x = 0; $x < pg_num_rows($res1); $x++){
					extract(pg_fetch_array($res1));
					//echo "$os\n";
					$condicao = ($fabrica == 11) ? 67 : 3593 ; 
					$tipo_pedido = ($fabrica == 11) ? 84 : 393; 
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
								$condicao     ,
								$tipo_pedido    ,
								TRUE      ,
								0
							) RETURNING pedido;";
					$res_ins_pedido = pg_query($con, $sql);
					if(pg_last_error($con)){
						$log_cliente[] 		              = "Erro ao inserir pedido para o posto {$codigo_posto}";
						$logs["ERROR"]["insert_pedido"][] = ["sql" => $sql, "error" => pg_last_error($con)];
						$erro   			= true;
						$erro_transaction 	= true;
					} else {
						$logs["SUCCESS"]["insert_pedido"][]	= ["sql" => $sql, "id" => pg_fetch_result($res_ins_pedido, 0)];
					}
					$pedido = pg_fetch_result($res_ins_pedido, 0);

					$sql = "INSERT INTO tbl_pedido_item (
								pedido,
								peca  ,
								qtde  ,
								qtde_faturada,
								qtde_cancelada,
								troca_produto ,
								preco
							) VALUES (
								$pedido,
								$peca  ,
								1      ,
								0      ,
								0      ,
								't'    ,
								0
							) RETURNING pedido_item;";
					$res_ins_pedido_item = pg_query($con, $sql);
					if(pg_last_error($con)){
						$logs["ERROR"]["insert_pedido_item"][] = ["sql" => $sql, "error" => pg_last_error($con)];
						$erro   			= true;
						$erro_transaction 	= true;
					} else {
						$logs["SUCCESS"]["insert_pedido_item"][] = ["sql" => $sql, "id" => pg_fetch_result($res_ins_pedido_item, 0)];
					}
					$pedido_item = pg_fetch_result($res_ins_pedido_item, 0);

					$sql = "UPDATE tbl_os_troca SET pedido = $pedido, pedido_item = $pedido_item WHERE os = $os and pedido isnull";
					$res3 = pg_query($con, $sql);
					if(pg_last_error($con)){
						$log_cliente[] 		= "Erro ao inserir itens no pedido {$pedido}";
						$logs["ERROR"]["update_os_troca"][]	= ["sql" => $sql, "error" => pg_last_error($con)];
						$erro   			= true;
						$erro_transaction 	= true;
					} else {
						$logs["SUCCESS"]["update_os_troca"][] = ["sql" => $sql, "id" => $pedido];
					}

					$sql = "SELECT fn_atualiza_os_item_pedido_item(os_item, $pedido, $pedido_item, $fabrica)
							FROM tbl_os_item 
							WHERE peca       = $peca 
							AND pedido isnull
							AND   os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os) ";
					$res4 = pg_query($con, $sql);
					if(pg_last_error($con)){
						$log_cliente[] 		= "Erro ao inserir itens no pedido {$pedido}";
						$logs["ERROR"]["fn_atualiza_os_item_pedido_item"][]	= ["sql" => $sql, "error" => pg_last_error($con)];
						$erro   			= true;
						$erro_transaction 	= true;
					} else {
						$logs["SUCCESS"]["fn_atualiza_os_item_pedido_item"][] = ["sql" => $sql, "id" => $pedido];
					}

					$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
					$res_finaliza_pedido = pg_query($con, $sql);
					if(pg_last_error($con)){
						$logs["ERROR"]["fn_pedido_finaliza"][] = ["sql" => $sql, "error" => pg_last_error($con)];
						$erro   			= true;
						$erro_transaction 	= true;
					} else {
						$logs["SUCCESS"]["fn_pedido_finaliza"][] = ["sql" => $sql, "id" => $pedido];  
					}
				}
			}

			if($erro_transaction){
				pg_query($con, "ROLLBACK TRANSACTION");
				$erro_transaction = false;
			}else{
				pg_query($con, "COMMIT TRANSACTION");
			}
    	}
    }
	$phpCron->termino();
	
	$layout_erro = "";
	$layout_sucess = "";

	if (count($logs["ERROR"]) > 0) {
		foreach ($logs["ERROR"] as $titulo => $rows) {
			$layout_erro .= "### {$titulo} ###\n";
			foreach ($rows as $key => $conteudo) {
				$layout_erro .= "# SQL: \n".$conteudo['sql']."\n\n";
				$layout_erro .= "# ERRO: \n".$conteudo['error']."\n\n";
			}
			$layout_erro .= "################################################################################################### \n\n\n";
		}
	}

	if (count($logs["SUCCESS"]) > 0) {
		foreach ($logs["SUCCESS"] as $titulo => $rows) {
			$layout_sucess .= "### {$titulo} ###\n";
			foreach ($rows as $key => $conteudo) {
				$layout_sucess .= "# SQL: \n".$conteudo['sql']."\n\n";
				if ($conteudo["id"]) {
					$layout_sucess .= "# ID: \n".$conteudo['id']."\n\n";
				}
			}
			$layout_sucess .= "################################################################################################### \n\n\n";
		}
	}

	fwrite($fl, $layout_erro);
	fclose($fp);
	fwrite($new_fl, $layout_sucess);
	fclose($new_fl);

} catch (Exception $e) {

	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\nErro na linha: " . $e->getLine() . "\r\nErro descrição: " . $e->getMessage();
    //echo $msg."\r\n";

    Log::envia_email($data_log,Date('d/m/Y H:i:s')." - Erro ao executar gera pedido os troca Lenoxx", $msg);
}?>
