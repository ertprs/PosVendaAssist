<?php

error_reporting(E_ALL);

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../../funcoes.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    /* Dados iniciais */
	$fabrica      = 1;
	$fabrica_nome = "Black & Decker";
	$log_posto    = array();
	$msg_erro     = array();
	$env 		  = "producao"; // test | producao

	/*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Fechamento automatico para OSs com pedidos faturados a mais de 20 dias - {$fabrica_nome}")); // Titulo
    if ($env == "producao" ) {

	    $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("guilherme.silva@telecontrol.com.br");
        $limit = " LIMIT 1";
    }


    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
	Seleciona os extratos e postos
    */
    $sql = "SELECT
		tbl_os.os,
		tbl_os.posto,
		tbl_posto.nome AS posto_nome,
		tbl_os.sua_os,
		tbl_posto_fabrica.codigo_posto ,
		tbl_os_produto.os_produto,
		count(tbl_os_item.os_item) as item,
		count(os_item_nf) as item_nf,
		(select max(data_input) from tbl_os_item_nf join tbl_os_item using(os_item) where tbl_os_item.os_produto = tbl_os_produto.os_produto) as data_input
		INTO TEMP tmp_black_os_fecha
		FROM tbl_os
		INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
		INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		LEFT JOIN tbl_os_item_nf ON tbl_os_item_nf.os_item  = tbl_os_item.os_item
		INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = {$fabrica} AND tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
		INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		left join tbl_pedido_cancelado using(pedido,peca)
		WHERE
			tbl_os.fabrica = {$fabrica}
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.data_fechamento ISNULL
			AND tbl_os.data_abertura >= '2015-01-01'
			and tbl_pedido_cancelado.pedido isnull
			AND (status_os_ultimo isnull or status_os_ultimo not in (110,111,112))
			group by tbl_os.os,
			tbl_os.posto,
			tbl_posto.nome ,
			tbl_os.sua_os,
			tbl_posto_fabrica.codigo_posto ,
			tbl_os_produto.os_produto;

		SELECT  os, sua_os, codigo_posto,posto, posto_nome, sum(item) as item , sum(item_nf) as item_nf, max(data_input) as data_input
		INTO TEMP tmp_black_oss
		FROM tmp_black_os_fecha
		group by os, sua_os, codigo_posto,posto, posto_nome;

		UPDATE tmp_black_oss SET data_input = tbl_os_status.data FROM tbl_os_status WHERE tbl_os_status.os = tmp_black_oss.os AND extrato notnull and data > data_input;

		SELECT *
		FROM tmp_black_oss
		WHERE item = item_nf
		AND (data_input + INTERVAL '20 DAYS') <= CURRENT_DATE ;
	";
	$res = pg_query($con, $sql);

	if(strlen(pg_last_error($con)) > 0){

		$msg_erro[] = "Erro ao selecionar as OSs com pedidos faturados a mais de 20 dias. Erro (".pg_last_error($con).").";

	}else{

		if(pg_num_rows($res) > 0){

	        for($i = 0; $i < pg_num_rows($res); $i++){
				$msg_erro = array();

				$os           = pg_fetch_result($res, $i, "os");
				$posto        = pg_fetch_result($res, $i, "posto");
				$posto_nome   = pg_fetch_result($res, $i, "posto_nome");
				$sua_os       = pg_fetch_result($res, $i, "sua_os");
				$codigo_posto = pg_fetch_result($res, $i, "codigo_posto");

				$os_posto = $codigo_posto . $sua_os;

				$res_os = pg_query($con,"BEGIN TRANSACTION");

				$sql_fechamento = "UPDATE tbl_os SET data_fechamento = CURRENT_DATE, data_conserto = CURRENT_TIMESTAMP WHERE os = {$os} AND fabrica = {$fabrica}";
				$res_fechamento = pg_query($con, $sql_fechamento);

				$sql_fechamento_os = "UPDATE tbl_os_extra SET obs_fechamento = 'Automático' WHERE os = {$os} ";
				$res_fechamento_os = pg_query($con, $sql_fechamento_os);

				/*
                 * - Verifica se o Posto faz o cálculo
                 * da Taxa Administrativa
                 *
                 * ( funcoes.php )
                 */

                calculaTaxaAdministrativa($con,$fabrica,$posto,$os);

				if(strlen(pg_last_error($con))){

					$msg_erro[] = "Erro ao atualizar a data de fechamento para a OS {$os}. Erro (".pg_last_error($con).").";

				}else{

					$sql_os = "SELECT fn_finaliza_os({$os}, {$fabrica})";
					$res_os = pg_query($con, $sql_os);

					if(strlen(pg_last_error($con)) > 0){

						$msg_erro[] = "Erro ao finalizar a OS {$os_posto}. Erro (".pg_last_error($con).").";

					}else{

						$sql_estoque = "SELECT fn_estoque_os({$os}, {$fabrica})";
						$res_estoque = pg_query($con, $sql_estoque);

						if(strlen(pg_last_error($con)) > 0){

							$msg_erro[] = "Erro ao atualizar o estoque da OS {$os}. Erro (".pg_last_error($con).").";

						}else{

							$mensagem = "Prezada autorizada {$codigo_posto} - {$posto_nome}, informamos que a OS <strong>{$os_posto}</strong>
										foi finalizada automaticamente, pois estava a mais de 20 dias com pedido faturado em nosso sistema.";

							$sql_comunicado = "INSERT INTO tbl_comunicado (
		                            mensagem,
		                            descricao,
		                            tipo,
		                            fabrica,
		                            posto,
		                            ativo
		                        ) VALUES (
		                            '$mensagem',
		                            'OS finalizada automaticamente - OS {$os_posto}',
		                            'Comunicado Automatico',
		                            $fabrica,
		                            $posto,
		                            't'
		                        );";

		            		$res_comunicado = pg_query ($con, $sql_comunicado);

		            		if(strlen(pg_last_error())){

		            			$msg_erro[] = "Erro ao gravar o Comunicado para o posto {$codigo_posto} - {$posto_nome}. Erro (".pg_last_error($con).").";

		            		}else{
		            		}
						}
					}
				}

				if (count($msg_erro)==0){
					$res_os = pg_query($con,"COMMIT TRANSACTION");
				}else{
					$res_os = pg_query($con,"ROLLBACK TRANSACTION");
				}
	        }
	    }
	}

    if(count($msg_erro) > 0){

    	$logClass->adicionaLog(implode("<br />", $msg_erro));
        $logClass->enviaEmails();

    }

    /*
    * Cron Término
    */
    $phpCron->termino();

} catch (Excpection $e) {
	echo $e->getMessage();
}

?>
