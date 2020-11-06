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
define('ENV','teste');  // utilizar em ambiente de teste

try {
	$data_log['login_fabrica']      = 3;
	$data_log['dest']               = 'kaique.magalhaes@telecontrol.com.br';
	$data_log['log']                = 2;
	$login_fabrica = $data_log['login_fabrica'] ;
	$fabrica_nome = 'colormaq';
	date_default_timezone_set('America/Sao_Paulo');
    // $log[] = Date('d/m/Y H:i:s ')."Inicio do Programa";

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$arquivo = "libera-extrato-lgr.txt";

	// $phpCron = new PHPCron($fabrica, __FILE__);
	// $phpCron->inicio();

	$notificar_falha = 0;
	$destinatarios_clientes = "kaique.magalhaes@telecontrol.com.br";
    //$destinatarios_clientes = "paulo\@telecontrol.com.br";

	if (ENV == 'teste' ) {
		$arquivos     	= "entrada/";
		mkdir($arquivos, 0777, true);
	} else {
		//$arquivos     = "/home/colormaq/colormaq-telecontrol/";
	}

     $data_sistema = Date('Y-m-d');

     if(file_exists($arquivos.$arquivo)){

     	$linhas = file_get_contents($arquivos.$arquivo);
     	$linhas = explode("\n",$linhas);

     	$erro = $msg_erro;

     		$msg_erro = "";



          foreach ($linhas as $key => $value) {

               list(
                    $txt_extrato_devolucao,
                    $txt_codigo_posto, 
                    $txt_nf_origem, 
                    $txt_serie_origem,
                    $txt_nota_fiscal, 
                    $txt_peca,
                    $txt_qtde_conf, 
                    ) = explode("\t",$value);

                    $extrato_devolucao  = trim($txt_extrato_devolucao);
                          $codigo_posto = trim($txt_codigo_posto); 
                          $nf_origem    = trim($txt_nf_origem);  
                          $nota_fiscal  = trim($txt_nota_fiscal);
                          $serie_origem = trim($txt_serie_origem); 
                          $peca         = trim($txt_peca);
                          $qtde_conf    = trim($txt_qtde_conf);
                     
               $sql = "SELECT posto 
                       FROM tbl_posto_fabrica 
                       WHERE codigo_posto = '$codigo_posto' 
                       AND fabrica = $login_fabrica";

               $res = pg_query($con,$sql);

               $posto = pg_fetch_result($res, 0, 'posto');

			         $sql = "SELECT tbl_faturamento_item.faturamento,
                              tbl_faturamento.extrato_devolucao 
                       FROM tbl_faturamento_item 
                       JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                       WHERE tbl_faturamento_item.nota_fiscal_origem = '$nf_origem'
                       AND tbl_faturamento.fabrica = $login_fabrica
                       AND tbl_faturamento.distribuidor   = $posto
                       AND tbl_faturamento.extrato_devolucao = $extrato_devolucao";
                         
     		       $res_faturamento = pg_query($con,$sql);
     		
                 if (pg_num_rows($res_faturamento) > 0){
                    for ($x=0;$x<pg_num_rows($res_faturamento);$x++) { 
                      $faturamento        = pg_fetch_result($res_faturamento, $i, 'faturamento');
                      $extrato_devolucao  = pg_fetch_result($res_faturamento, $i, 'extrato_devolucao');

                 			pg_query($con,"BEGIN");
                 			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca'";
                 			$res = pg_query($con,$sql);
                      $peca = pg_fetch_result($res, 0, 'peca');
                        
                      if(strlen($peca)>0 ){

                           $sql_lgr = "UPDATE tbl_extrato_lgr
                                       SET qtde_nf = qtde - {$qtde_conf}
                                       WHERE peca  = $peca
                          						 AND extrato = $extrato_devolucao 
                                       AND posto   = $posto
                          						 AND (qtde_nf is null or qtde_nf > $qtde_conf)";

                                       $res_lgr = pg_query($con,$sql_lgr);
                                       $msg_erro .= pg_errormessage($con);

                           $sql_lgr = "UPDATE tbl_faturamento_item
                                       SET nota_fiscal_origem = '$nf_origem'
                                       WHERE peca  = $peca
                                       AND faturamento = $faturamento";
                                       $res_lgr = pg_query($con,$sql_lgr);
                                       $msg_erro .= pg_errormessage($con);            

                      }
                           
                      
                           $sql = "SELECT SUM(coalesce(qtde_nf)) AS total_devolucao FROM tbl_extrato_lgr WHERE extrato = $extrato_devolucao";
                           $res = pg_query($con,$sql);
                           $msg_erro .= pg_errormessage($con);
                           
                           $total_devolucao = pg_fetch_result($res, 0, "total_devolucao");
                           
                           if($total_devolucao == 0){
                                
                                $sql = "UPDATE tbl_faturamento
                                SET conferencia = CURRENT_TIMESTAMP,
                                cancelada = NULL
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

                           $sql = "UPDATE tbl_faturamento
                                   SET baixa = CURRENT_TIMESTAMP
                                   WHERE faturamento = $faturamento
                                   AND fabrica = $login_fabrica";
                            $res = pg_query($con,$sql);
                            $msg_erro .= pg_errormessage($con);          

                           if(strlen($msg_erro)>0){
                                 echo"erro";
                                 pg_query($con,"ROLLBACK");
                            }else{
                                 echo"foi";
                                 pg_query($con,"COMMIT");
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

