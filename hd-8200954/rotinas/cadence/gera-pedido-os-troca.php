<?php
/**
 *
 * igera-pedido-os.php
 *
 * Geração de pedidos de troca com base na OS
 *
 * @author  Guilherme Silva
 * @version 2014.08.20
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../../class/log/log.class.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $vet['fabrica'] = 'cadence';
    $vet['tipo'] 	= 'pedido';
    $vet['log'] 	= 2;
	$fabrica 		= 35;
    $data_sistema	= Date('Y-m-d');
    $logs_erro				= array();

	/* Log */
    $log = new Log();
    $log->adicionaLog(array("titulo" => "Log erro Geração de Pedidos Cadence")); // Titulo
    
	if (ENV == 'producao' ) {
		$log->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
    	$log->adicionaEmail("kaique.magalhaes@telecontrol.com.br");
    }

    $arquivo_err = "/tmp/cadence/gera-pedido-troca-{$data_sistema}.err";
    $arquivo_log = "/tmp/cadence/gera-pedido-troca-{$data_sistema}.log";
    system ("mkdir /tmp/cadence/ 2> /dev/null ; chmod 777 /tmp/cadence/" );

    
    $sql = "SELECT  DISTINCT
				tbl_posto.posto   ,
				tbl_produto.linha,
				tbl_os_troca.peca,
				tbl_os_troca.os
			FROM    tbl_os                
			JOIN    tbl_posto             USING (posto)
			JOIN    tbl_produto           ON tbl_os.produto            = tbl_produto.produto
			JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os 
			WHERE   tbl_os_troca.pedido        IS NULL
			AND     tbl_os.excluida           IS NOT TRUE
			AND     tbl_os.validada           IS NOT NULL
			AND     tbl_os.finalizada         IS NULL
			AND     tbl_posto.posto           <> 6359
			AND		tbl_os_troca.peca notnull
			AND     tbl_os_troca.gerar_pedido IS TRUE
			AND     tbl_os_troca.fabric = $fabrica  
			AND     tbl_os.fabrica      = $fabrica";
	$res = pg_query($con, $sql);

	if(pg_last_error($con)){
    	$log->adicionaLog("Erro ao listar as os");
        $log->adicionaLog("linha");
    	throw new Exception ($msg_erro);
    }

	if(pg_num_rows($res) > 0 AND count($logs_erro) == 0){
		
		for($i = 0; $i < pg_num_rows($res); $i++){
			$posto = pg_result($res,$i,'posto');
			$linha = pg_result($res,$i,'linha');
			$os    = pg_result($res,$i,'os');
			$peca  = pg_result($res,$i,'peca');

			if (!consultaAuditoriaOS($os)) {
				continue;
			}

			$condicao = "960";
			
			unset($logs_erro);
			
			$resultX = pg_query($con,"BEGIN TRANSACTION");

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
						'113',
						TRUE      ,
						0
					) RETURNING pedido;";					
			$resultX = pg_query($con, $sql);
			if(pg_last_error($con)){
				$logs_erro[] = "Erro ao gravar pedido para o Posto: $codigo_posto - $nome_posto";
				$log->adicionaLog("Erro ao gravar pedido para o Posto: $codigo_posto - $nome_posto");
    			$log->adicionaLog("linha");
    			$erro = "*";
			}else{					
				$pedido = pg_result($resultX,0,0);

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
							1      ,
							0      ,
							0      ,
							't'    ,
							0
						) RETURNING pedido_item";
				$resultX = pg_query($con, $sql);

				if(pg_last_error($con)){
					$logs_erro[] = "Erro ao gravar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto";
					$log->adicionaLog("Erro ao gravar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto");
	        		$log->adicionaLog("linha");
	        		$erro = "*";
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
						$logs_erro[] = "Erro ao atualizar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto";
						$log->adicionaLog("Erro ao atualizar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto");
	        			$log->adicionaLog("linha");
	        			$erro = "*";
					}

					$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
					$resultX = pg_query($con, $sql);
					
					if(pg_last_error($con)){
						$logs_erro[] = "Erro ao finalizar o pedido $pedido para o Posto: $codigo_posto - $nome_posto";
						$log->adicionaLog("Erro ao finalizar o pedido $pedido para o Posto: $codigo_posto - $nome_posto");
		        		$log->adicionaLog("linha");
		        		$erro = "*";
					}
				}
			}
			
			if ($erro == "*") {
				$resultX = pg_query($con, "ROLLBACK TRANSACTION");
			}else{
				$resultX = pg_query($con,"COMMIT TRANSACTION");
			}
		}
	}

	if(count($log_erro) > 0){
    	$file_log = fopen($arquivo_err,"w+");
       	fputs($file_log,implode("\r\n", $log_erro));
        fclose ($file_log);
    }

	 //envia email para HelpDESK
    if($erro){
	    if($log->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          echo $log->enviaEmails();
        }
    } 

    
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

function consultaAuditoriaOS($os) {
        global $con, $fabrica;

        if(!empty($os)){
            $sql   = "SELECT DISTINCT auditoria_status, observacao FROM tbl_auditoria_os WHERE os = {$os};";
            $query = pg_query($con, $sql);

            $res   = pg_fetch_all($query);

            $bloqueio_pedido = false;

            for($i = 0; $i < count($res); $i++) {

                $auditoria_status = $res[$i]["auditoria_status"];
                $observacao       = $res[$i]["observacao"];

                $sqlBloqPedido = "
                    SELECT auditoria_os, liberada, bloqueio_pedido, cancelada
                    FROM tbl_auditoria_os
                    WHERE os = {$os}
                    AND auditoria_status = {$auditoria_status}
                    AND fn_retira_especiais(observacao) = fn_retira_especiais('{$observacao}')
                    ORDER BY data_input DESC
                    LIMIT 1
                ";
                $queryBloqPedido = pg_query($con, $sqlBloqPedido);
    	        $resBloqPedido   = pg_fetch_all($queryBloqPedido);

            	if ($resBloqPedido[0]['bloqueio_pedido'] == 't' && $resBloqPedido[0]['liberada'] == "") {
                    $bloqueia_pedido = true;
    	        }
            }

            if($bloqueia_pedido){
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }

    }