<?php
/**
 *
 * importa-pedido-recebido.php
 *
 * Importação de pedidos de pecas recebidos pela fábrica
 *
 * @author  Ronald Santos
 * @version 2014.01.17
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	$data['login_fabrica'] 	= 167;
	$data['fabrica'] 		= 'brother';
	$data['arquivo_log'] 	= 'importa-pedido-recebido';
	$data['tipo'] 			= 'importa-pedido';
	$data['log'] 			= 2;
	$data['arquivos'] 		= "/tmp";
	$data['data_sistema'] 	= Date('Y-m-d');
	$logs 					= array();
	$logs_erro				= array();
	$logs_cliente			= array();
	$erro 					= false;

    if (ENV == 'producao' ) {
	    $data['dest'] 			= 'helpdesk@telecontrol.com.br';
	    $data['dest_cliente']  	= 'ronald.santos@telecontrol.com.br';
	    $data['origem']			= "/home/brother/brother-telecontrol/";
	    $data['file']			= 'pedido.txt';
    } else {
	    $data['dest'] 			= 'ronald.santos@telecontrol.com.br';
	    $data['dest_cliente'] 	= 'ronald.santos@telecontrol.com.br';
	    $data['origem']			= '/home/ronald/public_html/PosVendaAssist/rotinas/brother/';
	    $data['file']			= 'pedido.txt';
    }

    extract($data);
	
	define('APP', 'Importa Pedidos Recebidos - '.$fabrica);

	$arquivo_err = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica}/ 2> /dev/null ; chmod 0777 {$arquivos}/{$fabrica}/" ); 
    if(file_exists($origem.$file)){
		$sql = "DROP TABLE IF EXISTS brother_recibo;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "CREATE TABLE brother_recibo (
				data 		text,
				cnpj		text,
				tipo 		text,
				txt_pedido  text,
				txt_qtde	text
			  )";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$linhas = file_get_contents($origem.$file);
		$linhas = explode("\n",$linhas);

		$erro = $msg_erro;

		foreach($linhas AS $linha){

			$msg_erro = "";

			list($data,$cnpj, $tipo,$txt_pedido, $txt_qtde) = explode("|",$linha);
			if(!empty($txt_pedido)){
				
				$cnpj = trim($cnpj);
				$tipo = trim($tipo);
				$txt_pedido = trim($txt_pedido);
				$txt_qtde = trim($txt_qtde);
				$data = trim($data);

				$res = pg_query($con,"BEGIN");

				$sql = "INSERT INTO brother_recibo(data,cnpj,tipo,txt_pedido,txt_qtde) VALUES('$data','$cnpj', '$tipo','$txt_pedido','$txt_qtde')";
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

		$sql = "UPDATE brother_recibo SET 
					cnpj 			 = trim(cnpj),
					txt_pedido       = trim(txt_pedido)";
		$res = pg_query($con,$sql);

		$sql = "ALTER TABLE brother_recibo ADD COLUMN posto INT4";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		
		$sql = "ALTER TABLE brother_recibo ADD COLUMN pedido INT4";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "UPDATE brother_recibo SET posto = tbl_posto_fabrica.posto
				FROM tbl_posto_fabrica, tbl_posto
				WHERE tbl_posto.cnpj = brother_recibo.cnpj
				AND tbl_posto.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "UPDATE brother_recibo SET pedido = tbl_pedido.pedido
				FROM tbl_pedido
				WHERE tbl_pedido.pedido = brother_recibo.txt_pedido::numeric
				AND tbl_pedido.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "DROP TABLE brother_recibo_sem_posto";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT * INTO brother_recibo_sem_posto 
                        FROM brother_recibo 
                        WHERE posto IS NULL";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "DROP TABLE brother_recibo_sem_pedido";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT * INTO brother_recibo_sem_pedido 
                        FROM brother_recibo 
                        WHERE pedido IS NULL";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "DELETE FROM brother_recibo 
                        WHERE posto IS NULL OR pedido IS  NULL";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT fn_atualiza_pedido_recebido_fabrica (tbl_pedido.pedido,$login_fabrica, current_date )
                FROM   brother_recibo, tbl_pedido
                WHERE tbl_pedido.pedido = brother_recibo.pedido
                AND tbl_pedido.posto = brother_recibo.posto
                AND tbl_pedido.recebido_fabrica IS NULL";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT fn_atualiza_pedido_recebido_status ($login_fabrica);";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if (!empty($msg_erro)) {
			$msg_erro .= "\n\n".$log_erro;
			$fp = fopen("/tmp/brother/pedido-recebidos.err","w");
			fwrite($fp,$msg_erro);
			fclose($fp);
			$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
			Log::envia_email($data, APP, $msg);

		} else {
			$fp = fopen("/tmp/brother/pedido-recebidos.err","w");
			fwrite($fp,$log_erro);
			fclose($fp);

			system("mv $origem$file /tmp/brother/pedido-recebidos".date('Y-m-d-H-i').".txt");

			Log::log2($data, APP . ' - Executado com Sucesso - ' . date('Y-m-d-H-i'));

		}

	}
}catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - brother - Importa pedidos recebidos (importa-pedido-recebido.php)", $msg);
}?>
