<?php
/**
 *
 * importa-pecas.php
 *
 * liberar os extratos do LGR colormaq
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
	$login_fabrica = $data_log['login_fabrica'] ;
	$fabrica_nome = 'colormaq';
	date_default_timezone_set('America/Sao_Paulo');
    // $log[] = Date('d/m/Y H:i:s ')."Inicio do Programa";

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$arquivo = "exporta-extrato.txt";

	// $phpCron = new PHPCron($fabrica, __FILE__);
	// $phpCron->inicio();

	$notificar_falha = 0;
	$destinatarios_clientes = "william.lopes\@telecontrol.com.br";
    //$destinatarios_clientes = "paulo\@telecontrol.com.br";

	if (ENV == 'teste' ) {
		$arquivos     	= dirname(__FILE__) . '/../../../colormaq/';
		mkdir($arquivos, 0777, true);
	} else {
		$arquivos     = "/home/colormaq/colormaq-telecontrol/";
	}

     $data_sistema = Date('Y-m-d');

     if(file_exists($arquivos.$arquivo)){

     	$linhas = file_get_contents($arquivos.$arquivo);
     	$linhas = explode("\n",$linhas);

     	$erro = $msg_erro;

     		$msg_erro = "";



          foreach ($linhas as $key => $value) {

               list(
                    $txt_cnpj, 
                    $txt_nota_fiscal, 
                    $txt_peca, 
                    $txt_qtde_conf, 
                    ) = explode("\t",$value);
     		$txt_cnpj = str_replace('.','',$txt_cnpj);
     		$txt_cnpj = str_replace('/','',$txt_cnpj);
     		$txt_cnpj = str_replace('-','',$txt_cnpj);
               
               $sql = "SELECT posto FROM tbl_posto WHERE cnpj = '$txt_cnpj'";
               $res = pg_query($con,$sql);
               $posto = pg_fetch_result($res, 0, 'posto');

			 $sql = "SELECT faturamento, 
                              extrato_devolucao 
                         FROM tbl_faturamento 
                         WHERE nota_fiscal = '$txt_nota_fiscal'
                         AND fabrica = $login_fabrica
                         AND distribuidor = $posto ";
     		$res = pg_query($con,$sql);
     		
               if (pg_num_rows($res) > 0){
                    $faturamento        = pg_fetch_result($res, 0, 'faturamento');
                    $extrato_devolucao  = pg_fetch_result($res, 0, 'extrato_devolucao');
     			$res = pg_query($con,"BEGIN");
     			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$txt_peca'";
     			$res = pg_query($con,$sql);
                    $peca = pg_fetch_result($res, 0, peca);
                    
                    if(strlen($peca)>0 ){

                         $sql_lgr = "UPDATE tbl_extrato_lgr
                         SET qtde_nf = qtde - {$txt_qtde_conf}
                         WHERE peca = $peca
						 AND extrato = $extrato_devolucao 
						 AND (qtde_nf isnull or qtde_nf > $txt_qtde_conf)";
                         $res_lgr = pg_query($con,$sql_lgr);
                         $msg_erro .= pg_errormessage($con);

                    }
                         
                    $sql = "SELECT peca FROM tbl_extrato_lgr WHERE qtde_nf IS NULL AND extrato =  $extrato_devolucao";
                    $res = pg_query($con,$sql);
                    
                    if (pg_num_rows($res)==0){

                         $sql = "SELECT SUM(coalesce(qtde_nf)) AS total_devolucao FROM tbl_extrato_lgr WHERE extrato = $extrato_devolucao";
                         $res = pg_query($con,$sql);
                         $msg_erro .= pg_errormessage($con);
                         
                         $total_devolucao = pg_fetch_result($res, 0, "total_devolucao");
                         
                         if($total_devolucao == 0){
                              
                              $sql = "UPDATE tbl_faturamento
                              SET conferencia = CURRENT_TIMESTAMP,
                              cancelada = NULL,
                              devolucao_concluida = 't'
                              WHERE faturamento = $faturamento
                              AND fabrica = $login_fabrica
                              AND distribuidor IS NOT NULL ";
                              $res = pg_query($con,$sql);   
                              $msg_erro .= pg_errormessage($con);

                              $sql = "UPDATE tbl_faturamento_item
                              SET qtde_inspecionada = qtde
                              WHERE faturamento = $faturamento ";
                              $res = pg_query($con,$sql);
                              $msg_erro .= pg_errormessage($con);
                              
                         }
                         if(strlen($msg_erro)>0){
                               echo"erro";
                                $res = pg_query($con,"ROLLBACK");
                          }else{
                               echo"foi";
                                $res = pg_query($con,"COMMIT");
                          }
 
                    }

     		}

     	}
     }
    

     if(strlen($msg_erro) > 0){
     	$msg_erro .= Date('d/m/Y H:i:s ')."Fim do Programa";
     }

 } catch (Exception $e) {
 	$e->getMessage();
 	$msg = "Arquivo: ".__FILE__."\nErro na linha: " . $e->getLine() . "\nErro descrição: " . $e->getMessage();
 	echo $msg."\n";

 	Log::envia_email($data_log,Date('d/m/Y H:i:s')."Erro ao executar ", $msg);
 }

