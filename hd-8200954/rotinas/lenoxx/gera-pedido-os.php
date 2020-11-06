<?php
/**
 *
 * igera-pedido-os.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author  Éderson Sandre
 * @version 2012.04.12
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	require dirname(__FILE__) . '/../../funcoes.php';
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


    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $logs 					= array();
    $log_cliente 			= array();
    $erro 					= false;
	
    $phpCron = new PHPCron($data['fabrica'], __FILE__);
	$phpCron->inicio();

	$data_sistema 			= Date('Y-m-d');

	if (ENV == 'producao' ) {
		$data['dest'] 			= 'helpdesk@telecontrol.com.br';
		$data['dest_cliente']   = 'erasmo@lenoxxsound.com.br';
    } else {
    	$data['dest'] 			= 'luis.carlos@telecontrol.com.br';
    	$data['dest_cliente'] 	= 'guilherme.silva@gmail.com';
    }

    extract($data);    

    $sql = "SET DateStyle TO 'SQL,EUROPEAN'";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs[] = $sql;
    	$logs[] = pg_last_error($con);
    	$erro   = true;
    }

    foreach ($fabricas as $fabrica => $fabrica_nome) {
		$erro = false; 
		$erro_transaction= false;
    	$arquivo_log = "/tmp/{$fabrica_nome}/{$data_sistema}_gera_pedido_os.err";

    	$fl          = fopen($arquivo_log,"w+");

    	system ("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );

		$sql = "SELECT  DISTINCT 
		         tbl_os.os          ,
				 tbl_os.sua_os 	    ,
				 tbl_os_item.os_item,
				 tbl_os_item.peca 	,
				 tbl_os.posto 	    ,
				 tbl_os_item.qtde,
			     tbl_posto_fabrica.transportadora
			INTO   TEMP tmp_os_{$fabrica_nome}
			FROM    tbl_os_item
				JOIN    tbl_servico_realizado USING (servico_realizado)
				JOIN    tbl_os_produto USING (os_produto)
				JOIN    tbl_os         USING (os)
				JOIN    tbl_posto      USING (posto)
				JOIN    tbl_produto          ON tbl_os.produto            = tbl_produto.produto AND tbl_os.fabrica = tbl_produto.fabrica_i
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			WHERE   tbl_os_item.pedido IS NULL
				AND     tbl_os.excluida    IS NOT TRUE
				AND     tbl_os.validada    IS NOT NULL
				AND     tbl_os.posto        NOT IN (6359)
				AND     (tbl_servico_realizado.troca_de_peca AND tbl_servico_realizado.gera_pedido AND tbl_servico_realizado.troca_produto IS FALSE)
				AND     tbl_os.fabrica      = $fabrica
				AND     tbl_os_item.fabrica_i = $fabrica
				AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO' );

			CREATE INDEX tmp_os_{$fabrica_nome}_os ON tmp_os_{$fabrica_nome}(os);

			ALTER TABLE tmp_os_{$fabrica_nome} add pedido_item integer;

			SELECT  DISTINCT tmp_os_{$fabrica_nome}.os, tmp_os_{$fabrica_nome}.sua_os, tmp_os_{$fabrica_nome}.posto,tmp_os_{$fabrica_nome}.transportadora
			FROM    tmp_os_{$fabrica_nome}
			WHERE   tmp_os_{$fabrica_nome}.os NOT IN(
					SELECT interv.os
							FROM (
							SELECT 
							ultima.os, 
							(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND tbl_os_status.os = ultima.os  AND status_os IN (62,64,65)  ORDER BY data DESC LIMIT 1) AS ultimo_status
							FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND status_os IN (62,64,65) ) ultima
							) interv
							WHERE interv.ultimo_status IN (62,65)
			)
			AND     tmp_os_{$fabrica_nome}.os NOT IN (
					SELECT interv.os
							FROM (
							SELECT 
							ultima.os, 
							(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND tbl_os_status.os = ultima.os  AND status_os IN (72,73) ORDER BY data DESC LIMIT 1) AS ultimo_status
							FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND status_os IN (72,73) ) ultima
							) interv
							WHERE interv.ultimo_status = 72
			)
			AND     tmp_os_{$fabrica_nome}.os NOT IN (
					SELECT interv.os
							FROM (
							SELECT 
							ultima.os, 
							(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND tbl_os_status.os = ultima.os  AND status_os IN (87,88) ORDER BY data DESC LIMIT 1) AS ultimo_status
							FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND status_os IN (87,88) ) ultima
							) interv
							WHERE interv.ultimo_status = 87
			)
			AND     tmp_os_{$fabrica_nome}.os NOT IN (
					SELECT interv.os
							FROM (
							SELECT 
							ultima.os, 
							(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND tbl_os_status.os = ultima.os AND status_os IN (158,159,160) ORDER BY data DESC LIMIT 1) AS ultimo_status
							FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND status_os IN (158,159,160) ) ultima
							) interv
							WHERE interv.ultimo_status IN (158,160)
			);";
		//echo nl2br($sql); exit;

	    $res = pg_query($con, $sql);
	    if(pg_last_error($con)){
	    	$logs[] = $sql;
	    	$logs[] = pg_last_error($con);
	    	$erro   = true;
	    }
	    $log_cliente[] 		= "Total de OS para gerar pedido ".pg_num_rows($res);

	    if(pg_num_rows($res) > 0 AND !$erro){

	    	$sql_condicao = "SELECT condicao FROM tbl_condicao WHERE fabrica = {$fabrica} AND descricao ~* 'Antecipada' ";
	    	$res_condicao = pg_query($con, $sql_condicao);

	    	if(pg_num_rows($res_condicao) > 0){
	    		$condicao = pg_fetch_result($res_condicao, 0, "condicao");
	    	}else{
	    		continue;
	    	}

	    	$sql_tipo_pedido = "SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = {$fabrica} AND descricao ~* 'Antecipada' ";
	    	$res_tipo_pedido = pg_query($con, $sql_tipo_pedido);

	    	if(pg_num_rows($res_tipo_pedido) > 0){
	    		$tipo_pedido = pg_fetch_result($res_tipo_pedido, 0, "tipo_pedido");
	    	}else{
	    		continue;
	    	}

	    	for($i = 0; $i < pg_num_rows($res); $i++){
	    		extract(pg_fetch_array($res));

	    		$sql_auditoria = "SELECT auditoria_os
						FROM tbl_auditoria_os 
						WHERE os = $os
						AND (bloqueio_pedido IS TRUE OR cancelada IS NOT NULL OR reprovada IS NOT NULL)
						ORDER BY auditoria_os DESC ";
				$res_auditoria = pg_query($con, $sql_auditoria);
				
				if(pg_num_rows($res_auditoria)>0){
					continue;
				}

	    		$sql  = "SELECT  count(os_item) AS itens FROM tmp_os_{$fabrica_nome} WHERE os = $os ;";
	    		$res1 = pg_query($con, $sql);
				if(pg_last_error($con)){
					$logs[] = $sql;
					$logs[] = pg_last_error($con);
					$erro   = true;
					$itens  = 0; 
				}else{
					$itens 			= pg_fetch_result($res1, 0);
					$itens = 1;
					$itens_gerados 	= 0;
				}
				for($x = 0; $x < $itens; $x++){

					$sql = "SELECT  
								os_item ,
								peca 	,
								qtde
							FROM tmp_os_{$fabrica_nome}
							WHERE os = $os
								AND   pedido_item ISNULL
							LIMIT 10;";
					$res1 = pg_query($con, $sql);
					if(pg_last_error($con)){
						$logs[] = $sql;
						$logs[] = pg_last_error($con);
						$erro   = true;
					}

					if(pg_num_rows($res1) > 0){
						$sql = "SELECT tipo_posto, codigo_posto FROM tbl_posto_fabrica WHERE fabrica = $fabrica AND posto = $posto";
						$res2 = pg_query($con, $sql);
						if(pg_last_error($con)){
							$logs[] = $sql;
							$logs[] = pg_last_error($con);
							$erro   = true;
						}
						
						$transportadora = (empty($transportadora)) ? "null": $transportadora;
						extract(pg_fetch_array($res2));
						// $condicao = "67";

						pg_query($con, "BEGIN TRANSACTION");
						$erro_transaction = false;

						if($x == 0) {
							$pedido = null;							
							
							$sql = "INSERT INTO tbl_pedido (
										posto     ,
										fabrica   ,
										linha     ,
										condicao  ,
										tipo_pedido,
										distribuidor,
										exportado,
										transportadora
									) VALUES (
										$posto    ,
										$fabrica  ,
										203       ,
										$condicao ,
										$tipo_pedido,
										4311,
										now(),
										$transportadora
									);";
							pg_query($con, $sql);
							if(pg_last_error($con)){
								$log_cliente[] 		= "Erro ao inserir pedido para o posto {$codigo_posto}";
								$logs[]				= "### INSERT PEDIDO ###";
								$logs[] 			= $sql;
								$logs[] 			= pg_last_error($con);
								$erro   			= true;
								$erro_transaction 	= true;
							}

							$sql  			= "SELECT currval ('seq_pedido')";
							$res_ins_pedido = pg_query($con, $sql);
							if(pg_last_error($con)){
								$logs[] 			= $sql;
								$logs[] 			= pg_last_error($con);
								$erro   			= true;
								$erro_transaction 	= true;
							}
							$pedido = pg_fetch_result($res_ins_pedido, 0);
						}

						if(intval($pedido) <> 0){

							for($j = 0; $j < pg_num_rows($res1); $j++){
								extract(pg_fetch_array($res1));
								//echo $peca."\n";
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
											0      
										);";
								$res4 = pg_query($con, $sql);
								if(pg_last_error($con)){
									$log_cliente[] 		= "Erro ao inserir itens no pedido {$pedido}";
									$logs[]				= "### INSERT PEDIDO ITEM ###";
									$logs[] 			= $sql;
									$logs[] 			= pg_last_error($con);
									$erro   			= true;
									$erro_transaction 	= true;
								}

								$sql  				 = "SELECT currval ('seq_pedido_item')";
								$res_ins_pedido_item = pg_query($con, $sql);
								if(pg_last_error($con)){
									$logs[] 			= $sql;
									$logs[] 			= pg_last_error($con);
									$erro   			= true;
									$erro_transaction 	= true;
								}
								$pedido_item = pg_fetch_result($res_ins_pedido_item, 0);

								$sql = "SELECT 
											fn_atualiza_os_item_pedido_item(os_item, $pedido, $pedido_item, $fabrica)
										FROM tbl_os_item 
										WHERE os_item = $os_item ;

										UPDATE tmp_os_{$fabrica_nome} SET 
											pedido_item = $pedido_item 
										WHERE os_item  = $os_item ;";
								$res_up_pedido_item = pg_query($con, $sql);
								if(pg_last_error($con)){
									$logs[]				= "### ATUALIZA PEDIDO ITEM ###";
									$logs[] 			= $sql;
									$logs[] 			= pg_last_error($con);
									$erro   			= true;
									$erro_transaction 	= true;
								}
							}

							$sql 				 = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
							$res_finaliza_pedido = pg_query($con, $sql);
							if(pg_last_error($con)){
								$logs[]				= "### FINALIZA PEDIDO ###";
								$logs[] 			= $sql;
								$logs[] 			= pg_last_error($con);
								$erro   			= true;
								$erro_transaction 	= true;
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
	    	}
	    }
		
		$phpCron->termino();
 
	}

} catch (Exception $e) {

	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\nErro na linha: " . $e->getLine() . "\r\nErro descrição: " . $e->getMessage();
    //echo $msg."\r\n";

    Log::envia_email($data_log,Date('d/m/Y H:i:s')." - Erro ao executar gera pedido os Lenoxx", $msg);
}?>
