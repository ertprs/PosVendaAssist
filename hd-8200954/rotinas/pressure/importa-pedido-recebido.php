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

	$data['login_fabrica'] 	= 131;
    $data['fabrica'] 		= 'pressure';
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
	    $data['dest'] 		= 'ronald.santos@telecontrol.com.br';
	    $data['dest_cliente']  	= 'ronald.santos@telecontrol.com.br';
	    $data['origem']		= "/home/pressure/pressure-telecontrol/";
	    $data['file']		= 'pedidos_recebidos.txt';
    } else {
	    // $data['dest'] 		= 'ronald.santos@telecontrol.com.br';
	    // $data['dest_cliente'] 	= 'ronald.santos@telecontrol.com.br';
	    $data['dest'] 		= 'anderson.luciano@telecontrol.com.br';
	    $data['dest_cliente'] 	= 'anderson.luciano@telecontrol.com.br';
	    // $data['origem']		= dirname(__FILE__) . "/entrada/";
	    $data['origem']		= "/home/anderson/public_html/rotinateste/pressure/pressure-telecontrol/";
	    $data['file']		= 'pedidos_recebidos.txt';
    }

    extract($data);
	
	define('APP', 'Importa Pedidos Recebidos - '.$fabrica);

	$arquivo_err = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica}/ 2> /dev/null ; chmod 0777 {$arquivos}/{$fabrica}/" ); 
    
    if(file_exists($origem.$file)){
		
		$sql = "DROP TABLE IF EXISTS pressure_recibo;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "CREATE TABLE pressure_recibo (
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

			list($txt_codigo_posto, $txt_pedido, $txt_qtde) = explode(";",$linha);
			
			if(!empty($txt_pedido)){

				$res = pg_query($con,"BEGIN");

				$sql = "INSERT INTO pressure_recibo(txt_codigo_posto,txt_pedido,txt_qtde) VALUES('$txt_codigo_posto', '$txt_pedido','$txt_qtde')";				
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

		$sql = "UPDATE pressure_recibo SET 
					txt_codigo_posto = trim(txt_codigo_posto),
					txt_pedido       = trim(txt_pedido),
					txt_qtde         = trim(txt_qtde)";
		$res = pg_query($con,$sql);

		$sql = "ALTER TABLE pressure_recibo ADD COLUMN posto INT4";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		
		$sql = "ALTER TABLE pressure_recibo ADD COLUMN pedido INT4";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "ALTER TABLE pressure_recibo ADD COLUMN itens INT4";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "ALTER TABLE pressure_recibo ADD COLUMN validado boolean";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "UPDATE pressure_recibo SET posto = tbl_posto.posto
				FROM tbl_posto, tbl_posto_fabrica
				WHERE tbl_posto.cnpj = pressure_recibo.txt_codigo_posto
				AND tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "UPDATE pressure_recibo SET pedido = tbl_pedido.pedido
				FROM tbl_pedido
				WHERE tbl_pedido.pedido = pressure_recibo.txt_pedido::numeric
				AND tbl_pedido.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "DROP TABLE IF EXISTS pressure_recibo_sem_posto";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT * INTO pressure_recibo_sem_posto 
                        FROM pressure_recibo 
                        WHERE posto IS NULL";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "DROP TABLE IF EXISTS pressure_recibo_sem_pedido";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT * INTO pressure_recibo_sem_pedido 
                        FROM pressure_recibo 
                        WHERE pedido IS NULL";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "DELETE FROM pressure_recibo 
                        WHERE posto IS NULL";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);


		$sql = "DELETE FROM pressure_recibo 
                        WHERE pedido IS NULL";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE pressure_recibo SET itens = (SELECT COUNT(pedido_item) FROM tbl_pedido_item WHERE pedido = pressure_recibo.pedido)";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "UPDATE pressure_recibo SET validado = 't' WHERE itens = txt_qtde::numeric AND txt_qtde <> ''";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

        $sql = "SELECT fn_atualiza_pedido_recebido_fabrica (pedido,$login_fabrica,current_date)
		FROM pressure_recibo
		WHERE pressure_recibo.pedido notnull
		AND validado IS TRUE;";        
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
        if (!empty($msg_erro)) {
			$msg_erro .= "\n\n".$log_erro;
			//$fp = fopen("/tmp/pressure/pedido-recebidos.err","w");
			$fp = fopen("/home/anderson/public_html/rotinateste/pressure/pedido-recebidos.err","w");
			fwrite($fp,$msg_erro);
			fclose($fp);
			$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
			Log::envia_email($data, APP, $msg);

		}

        $sql = "SELECT fn_atualiza_pedido_recebido_status ($login_fabrica);";        
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if (!empty($msg_erro)) {
			$msg_erro .= "\n\n".$log_erro;
			$fp = fopen("/tmp/pressure/pedido-recebidos.err","w");
			fwrite($fp,$msg_erro);
			fclose($fp);
			$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
			Log::envia_email($data, APP, $msg);

		} else {
			
			$fp = fopen("/tmp/pressure/pedido-recebidos.err","w");
			fwrite($fp,$log_erro);
			fclose($fp);

			system("mv $origem$file /tmp/pressure/pedido-recebidos".date('Y-m-d-H-i').".txt");

			Log::log2($data, APP . ' - Executado com Sucesso - ' . date('Y-m-d-H-i'));

			$sql = "SELECT pedido, txt_qtde AS qtde_enviada FROM pressure_recibo WHERE validado IS NOT TRUE";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$pedidos = pg_fetch_all($res);
				$dest = "jupter@telecontrol.com.br";
				
				Log::envia_email($data,Date('d/m/Y H:i:s')." - Pressure - Pedidos com quantidade de itens divergentes", implode("\r\n",$pedidos));
			}

		}

	}
}catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - Pressure - Importa pedidos recebidos (importa-pedido-recebido.php)", $msg);
}?>
