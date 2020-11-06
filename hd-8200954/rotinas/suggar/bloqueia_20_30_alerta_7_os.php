<?php
/**
 *
 * Envia alerta por Email para OS aberta a mais de 7 e menos de 20 dias.
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // producao / teste

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';

	$login_fabrica 	= "24";
	$fabrica_nome  	= "suggar";
	$data_sistema	= Date('Y-m-d');
	$arquivos = "/tmp";

	$arquivo_err = "{$arquivos}/{$fabrica_nome}/bloqueia-os-7-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/bloqueia-os-7-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" ); 

	/* Inicio Processo */
	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();
	// - OS abertas entre 7 a 20 dias sem lançamento de peças.
	$sql = "SELECT tbl_os.os, tbl_os.posto
				FROM tbl_os
				LEFT JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os 
				WHERE fabrica = $login_fabrica
				AND posto <> 6359
				AND excluida IS NOT TRUE
				AND data_fechamento IS NULL
				AND finalizada IS NULL
				AND tbl_os_produto.os_produto IS NULL
				AND (CURRENT_DATE - data_abertura) BETWEEN 7 and 20 ;";
	$res = pg_query($con,$sql);

	$total = pg_num_rows($res);

	if($total > 0){

		$array_os_posto = [];

		for ($i=0; $i < $total; $i++) { 
			
			$erro = "";
			$os = pg_fetch_result($res, $i, 'os');
			$posto = pg_fetch_result($res, $i, 'posto');

			$resX = pg_query($con,"BEGIN");

			$sql_tem_extra = "SELECT os FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
			$res_tem_extra = pg_query($con, $sql_tem_extra);

			$msg_gravar = '{"mensagem_os":"Alerta Procon", "os_7_dias_sem_peca": true}';
			
			if (pg_num_rows($res_tem_extra) > 0) {
				$sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = coalesce(campos_adicionais::jsonb, '{}') || '$msg_gravar' WHERE os = $os AND fabrica = $login_fabrica";
			} else {
				$sql = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os, $login_fabrica, '$msg_gravar')";
			}

			$resP = pg_query ($con,$sql);
			$erro = pg_errormessage($con);
			
			if (strlen($erro) > 0){
				$log_erro[] = $os . " - ". $erro;
				$resX = pg_query ($con,"ROLLBACK");
			}else{
				$log_sucesso[] = $os . " - Alerta gravado com sucesso";
				$resX = pg_query ($con,"COMMIT");
				$array_os_posto[$posto][] = $os;
			}

		}

	}

   	// - OS abertas com mais de 20 dias sem lançamento de peças.
	$sql = "SELECT tbl_os.os, tbl_os.posto
				FROM tbl_os
				LEFT JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os 
				WHERE fabrica = $login_fabrica
				AND posto <> 6359
				AND excluida IS NOT TRUE
				AND data_fechamento IS NULL
				AND finalizada IS NULL
				AND tbl_os_produto.os_produto IS NULL
				AND CURRENT_DATE - tbl_os.data_abertura > 20 ;";
	$res = pg_query($con,$sql);

	$total = pg_num_rows($res);

	if($total > 0){

		for ($i=0; $i < $total; $i++) { 
			
			$erro = "";
			$os = pg_fetch_result($res, $i, 'os');
			$posto = pg_fetch_result($res, $i, 'posto');

			$resX = pg_query($con,"BEGIN");

			$sql_tem_extra = "SELECT os FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
			$res_tem_extra = pg_query($con, $sql_tem_extra);

			$msg_gravar = '{"mensagem_os":"OS com mais de 20 dias aguardando lançamento de peça", "os_20_dias_sem_peca": true}';
			
			if (pg_num_rows($res_tem_extra) > 0) {
				$sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = true, campos_adicionais = coalesce(campos_adicionais::jsonb, '{}') || '$msg_gravar' WHERE os = $os AND fabrica = $login_fabrica";
			} else {
				$sql = "INSERT INTO tbl_os_campo_extra (os, fabrica, os_bloqueada, campos_adicionais) VALUES ($os, $login_fabrica, true, '$msg_gravar')";
			}

			$resP = pg_query ($con,$sql);
			$erro = pg_errormessage($con);
			
			if (strlen($erro) > 0){
				$log_erro[] = $os . " - ". $erro;
				$resX = pg_query ($con,"ROLLBACK");
			}else{
				$log_sucesso[] = $os . " - OS Bloqueada com sucesso";
				$resX = pg_query ($con,"COMMIT");
			}

		}

	}

	if(count($log_erro) > 0){
    	$file_log_erro = fopen($arquivo_err,"w+");
        fputs($file_log_erro,implode("\r\n", $log_erro));
        fclose ($file_log_erro);
    }

    if(count($log_sucesso) > 0){
    	$file_log_sucesso = fopen($arquivo_log,"w+");
        fputs($file_log_sucesso,implode("\r\n", $log_sucesso));
        fclose ($file_log_sucesso);
    }

    if (count($array_os_posto) > 0) {
    	foreach ($array_os_posto as $key => $value) {
    		$oss = implode(",", $value);

    		$msg_comunicado = "Verificar a(s) OS(s) aberta(s) a mais de 7 dias e sem lançamento(s) de peça(s). \r OS(s): $oss";

    		$sql = "INSERT INTO tbl_comunicado (mensagem, data, tipo, fabrica, obrigatorio_site, descricao, posto, ativo, pais) VALUES ('$msg_comunicado', now(), 'Comunicado', $login_fabrica, TRUE, 'Alerta Procon', $key, TRUE, 'BR')";
    		$res = pg_query($con, $sql);

    		if (pg_last_error()) {
    			$log_erro[] = $key . " - ". pg_last_error();
    		}
    	}
    }

	$phpCron->termino();
	
}catch(Exception $e){

	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);
    
    Log::envia_email($data,Date('d/m/Y H:i:s')." - Suggar - Bloqueio de OS automatico (bloqueia_20_30_alerta_7_os_dias.php)", $msg);

}
