<?php
/**
 *
 * os_congeladas_60_dias.php -> Alterado para 30 dias HD-7144987
 *
 * Cancelamento de OS abertas a mais de 30 dias com peça lançada, só aguardando para ser finalizada
 *
 * @author William Lopes
 * @version 05.01.2015
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // producao / teste

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	$login_fabrica 	= "24";
	$fabrica_nome  	= "suggar";
	$log_erro 	 	= array();
	$log_sucesso 	= array();
	$data_sistema	= Date('Y-m-d');
	$arquivos = "/tmp";

	$arquivo_err = "{$arquivos}/{$fabrica_nome}/fechamento-os-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/fechamento-os-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" ); 

	/* Inicio Processo */
	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	$sql = "SELECT os
			FROM tbl_os
			JOIN tbl_os_produto USING(os)
			JOIN tbl_os_item USING(os_produto)
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.data_fechamento IS NULL
			AND tbl_os.finalizada IS NULL
			AND CURRENT_DATE - tbl_os.data_abertura > 30
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.cancelada IS NOT TRUE
			AND tbl_os_item.os_item NOTNULL
			AND tbl_os.status_checkpoint = 4";
	$res = pg_query($con,$sql);

	$total = pg_num_rows($res);

	if($total > 0){

		for ($i=0; $i < $total; $i++) { 

			$erro = "";
			$os = pg_fetch_result($res, $i, 'os');

			$resX = pg_query($con,"BEGIN");

			$msg_gravar = '{"mensagem_os":"OS Congelada, pois passou de 30 dias aguardando ser finalizada", "os_30_congelada": true}';

			$sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = TRUE, campos_adicionais = coalesce(campos_adicionais::jsonb, '{}') || '$msg_gravar' WHERE os = $os AND fabrica = $login_fabrica";
			$resP = pg_query ($con,$sql);
			$erro = pg_errormessage($con);

			if (strlen(trim($erro)) == 0) {
				$sql = "UPDATE tbl_os_extra SET extrato = 0 WHERE os = $os AND i_fabrica = $login_fabrica AND extrato IS NULL";
				$res = pg_query($con, $sql);
				$erro = pg_last_error();
			}

			if (strlen($erro) > 0){
				$log_erro[] = $os . " - ". $erro;
				$resX = pg_query ($con,"ROLLBACK");
			}else{
				$log_sucesso[] = $os . " - Congelada com sucesso";
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

    $phpCron->termino();
	
}catch(Exception $e){

	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - Suggar - Congelamento de OS autometico 30 dias (os_congeladas_60_dias.php)", $msg);

}
