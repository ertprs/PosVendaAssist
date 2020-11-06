<?php
/**
 *
 * exportar_extratos.php 
 *
 * exportar extratos 
 *
 * @author  William Lopes
 * @version 2015.01.30
 * 
 *
 */

error_reporting(E_ALL ^ E_NOTICE);
// define('ENV','producao');  // production Alterar para produção ou algo assim
define('ENV','producao');  // utilizar em ambiente de teste

try {
	$data_log['login_fabrica']      = 50;
	$data_log['dest']               = 'william.lopes@telecontrol.com.br';
	$data_log['log']                = 2;
     $login_fabrica = $data_log['login_fabrica'];
	date_default_timezone_set('America/Sao_Paulo');

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';


     $data_geracao = date("Y-m-d");
	$arquivo = "exporta-extrato.txt";

	$notificar_falha = 0;
	$destinatarios_clientes = "william.lopes\@telecontrol.com.br";
    //$destinatarios_clientes = "paulo\@telecontrol.com.br";

	if (ENV == 'producao' ) {
		$origem_envio     =  "/home/colormaq/telecontrol-colormaq/";

	} else {
		$origem_envio   = dirname(__FILE__) . '/../../../colormaq/';
	}

     $sql = "SELECT tbl_posto.cnpj,
                    tbl_extrato.data_geracao::date as data_geracao,
                    tbl_extrato.total
               FROM tbl_extrato
                    JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
               WHERE tbl_extrato.fabrica = $login_fabrica
               AND tbl_extrato.data_geracao::date >= '$data_geracao'";
   
     $res = pg_query($con,$sql);

     $count = pg_num_rows($res);
     if ( $count > 0){
          $file_arquivo = $origem_envio.$arquivo;
          $fp   = fopen($file_arquivo, 'w');
          
          if (!is_resource($fp)) {
            throw new Exception('Erro ao criar arquivo de exportação.'."\n");
          }


          for ($i=0; $i < $count; $i++) { 

               $cnpj = pg_fetch_result($res, $i,"cnpj");
               $data_geracao = pg_fetch_result($res, $i,"data_geracao");
               $total = pg_fetch_result($res, $i,"total");

               fwrite($fp, $cnpj.'|');
               fwrite($fp, $data_geracao.'|');
               fwrite($fp, $total);
               fwrite($fp, "\r\n");


          }

     fclose($fp);
          
	}
     
     if(strlen($msg_erro) > 0){
     	$msg_erro .= Date('d/m/Y H:i:s ')."Fim do Programa";
     }
     if (file_exists($file_arquivo) and (filesize($file_arquivo) > 0)) {

          date_default_timezone_set('America/Sao_Paulo');
          
          $destino = ' /tmp/colormaq/extratos/exporta-extrato-'.$data_geracao.'.txt';
          
          copy($file_arquivo, $destino);
          system("cp {$file_arquivo} {$destino};");
     }


 } catch (Exception $e) {
 	$e->getMessage();
 	$msg = "Arquivo: ".__FILE__."\nErro na linha: " . $e->getLine() . "\nErro descrição: " . $e->getMessage();
 	echo $msg."\n";

 	Log::envia_email($data_log,Date('d/m/Y H:i:s')."Erro ao executar ", $msg);
 }

