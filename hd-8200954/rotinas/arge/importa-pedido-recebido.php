<?php
/**
 *
 * importa-pedido-recebido.php
 *
 * Importação de pedidos de pecas recebidos pela fábrica
 *
 * @author  Ronald Santos
 * @version 2014.06.18
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	$data['login_fabrica'] 	= 137;
    $data['fabrica'] 		= 'arge';
    $data['arquivo_log'] 	= 'importa-pedido-recebido';
	$data['tipo'] 			= 'importa-pedido';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $erro 					= false;

    /* Log */
    $log = new Log2();

    if (ENV == 'producao' ) {
	    $data['dest'] 		= 'helpdesk@telecontrol.com.br';
	    $data['dest_cliente']  	= 'helpdesk@telecontrol.com.br';
	    $data['origem']		= "/home/arge/arge-telecontrol/";
// 	    $data['file']		= 'pedido_recebido.txt';
    } else {
	    $data['dest'] 		= 'ronald.santos@telecontrol.com.br';
	    $data['dest_cliente'] 	= 'ronald.santos@telecontrol.com.br';
	    $data['origem']		= "/home/ronald/perl/arge/entrada/";
// 	    $data['file']		= 'pedidos_recebidos.txt';
    }

	extract($data);

	define('APP', 'Importa Pedidos Recebidos - '.$fabrica);

	$arquivo_err = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica}/ 2> /dev/null ; chmod 0777 {$arquivos}/{$fabrica}/" );

	$array_arquivos = scandir($origem);
    sort($array_arquivos);
    $array_arquivos = array_slice($array_arquivos,2);
	foreach($array_arquivos as $chave=>$valor){
		$file = null;
		if(!preg_match("/telecontrol_pedidos_recebidos_[0-9-_\.]+\.txt/",$valor)){
				unset($array_arquivos[$chave]);
				continue;
		}else{
				$file = $valor;
		}

			if(file_exists($origem.$file) and filesize($origem.$file) > 0  and !empty($file)){

				$sql = "DROP TABLE IF EXISTS arge_recibo;";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);


				$sql = "CREATE TABLE arge_recibo (
						  txt_codigo_posto   text,
						  txt_pedido         text,
						  txt_qtde           text
					  )";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$linhas = file_get_contents($origem.$file);
				$linhas = explode("\n",$linhas);

				$erro = $msg_erro;

				foreach($linhas AS $linha){

					$msg_erro = "";

					list($txt_codigo_posto, $txt_pedido, $txt_qtde) = explode("\t",$linha);

					if(!empty($txt_pedido)){

						$res = pg_query($con,"BEGIN");

						$sql = "INSERT INTO arge_recibo(txt_codigo_posto,txt_pedido,txt_qtde) VALUES('$txt_codigo_posto', '$txt_pedido','$txt_qtde')";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if(!empty($msg_erro)){
							$res = pg_query($con,"ROLLBACK");
							$erro .= $msg_erro;
						} else {
							$res = pg_query($con,"COMMIT");
						}
					}
				}

				$sql = "UPDATE arge_recibo SET
							txt_codigo_posto = trim(txt_codigo_posto),
							txt_pedido       = trim(txt_pedido),
							txt_qtde         = trim(txt_qtde)";
				$res = pg_query($con,$sql);

				$sql = "ALTER TABLE arge_recibo ADD COLUMN posto INT4";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "ALTER TABLE arge_recibo ADD COLUMN pedido INT4";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "ALTER TABLE arge_recibo ADD COLUMN itens INT4";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "ALTER TABLE arge_recibo ADD COLUMN validado boolean";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE arge_recibo SET posto = tbl_posto_fabrica.posto
						FROM tbl_posto_fabrica
						WHERE tbl_posto_fabrica.codigo_posto = arge_recibo.txt_codigo_posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE arge_recibo SET pedido = tbl_pedido.pedido
						FROM tbl_pedido
						WHERE tbl_pedido.pedido = arge_recibo.txt_pedido::numeric
						AND tbl_pedido.fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "DROP TABLE IF EXISTS arge_recibo_sem_posto";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT * INTO arge_recibo_sem_posto
								FROM arge_recibo
								WHERE posto IS NULL";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "DROP TABLE IF EXISTS arge_recibo_sem_pedido";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT * INTO arge_recibo_sem_pedido
								FROM arge_recibo
								WHERE pedido IS NULL";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "DELETE FROM arge_recibo
								WHERE posto IS NULL";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);


				$sql = "DELETE FROM arge_recibo
								WHERE pedido IS NULL";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE arge_recibo SET itens = (SELECT COUNT(pedido_item) FROM tbl_pedido_item WHERE pedido = arge_recibo.pedido)";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE arge_recibo SET validado = 't' WHERE itens = txt_qtde::numeric";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT fn_atualiza_pedido_recebido_fabrica (tbl_pedido.pedido,$login_fabrica, current_date )
						FROM   arge_recibo, tbl_pedido
						WHERE tbl_pedido.pedido = arge_recibo.pedido::numeric
						AND tbl_pedido.posto = arge_recibo.posto::numeric
						AND tbl_pedido.recebido_fabrica IS NULL
						AND arge_recibo.validado IS TRUE";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT fn_atualiza_pedido_recebido_status ($login_fabrica);";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if (!empty($msg_erro)) {

					$log->adicionaLog("Erro ao Importar Pedidos Recebidos");

					/* Log de Erro */
					$log->adicionaTituloEmail("Logs de Erro da Rotina de Importação de Pedidos Recebidos - Arge");

					$log->adicionaEmail("caroline.miler@arge.com.br");
					$log->adicionaEmail("hermes.nebel@arge.com.br");
					$log->adicionaEmail("helpdesk@telecontrol.com.br");

					$log->enviaEmails();

					/* Fim Log de Erro */

					$msg_erro .= "\n\n".$log_erro;
					//$fp = fopen("/tmp/arge/pedido-recebidos.err","w");
					$fp = fopen("/home/perl/arge/entrada/pedido-recebidos.err","w");
					fwrite($fp,$msg_erro);
					fclose($fp);
					$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
					Log::envia_email($data, APP, $msg);

				} else {

					$fp = fopen("/tmp/arge/pedido-recebidos.err","w");
					$fp = fopen("/home/perl/arge/entrada/pedido-recebidos.err","w");
					fwrite($fp,$log_erro);
					fclose($fp);

					system("mv $origem$file /tmp/arge/pedido-recebidos".date('Y-m-d-H-i-s').".txt") ;

					Log::log2($data, APP . ' - Executado com Sucesso - ' . date('Y-m-d-H-i-s'));

					$sql = "SELECT pedido,qtde AS qtde_pedido, txt_qtde AS qtde_enviada FROM arge_recibo WHERE validado IS NOT TRUE";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$pedidos = pg_fetch_all($res);
						//$dest = "jupter@telecontrol.com.br";
						$dest = "ronald.santos@telecontrol.com.br";

						Log::envia_email($data,Date('d/m/Y H:i:s')." - Arge - Pedidos com quantidade de itens divergentes", implode("\r\n",$pedidos));
					}

				}

			}
	}
}catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - Arge - Importa pedidos recebidos (importa-pedido-recebido.php)", $msg);
}?>
