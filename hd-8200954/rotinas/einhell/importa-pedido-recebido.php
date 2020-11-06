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
date_default_timezone_set('America/Sao_Paulo');
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
    include dirname(__FILE__) . '/conexao_ftp_einhell.php';


    $data['login_fabrica']  = 160;
    $data['fabrica']        = 'einhell';
    $data['arquivo_log']    = 'importa-pedido-recebido';
    $data['tipo']           = 'importa-pedido';
    $data['log']            = 2;
    $data['arquivos']       = "/tmp";
    $data['data_sistema']   = Date('Y-m-d');
    $logs                   = array();
    $logs_erro              = array();
    $logs_cliente           = array();
    $erro                   = false;

    if (ENV == 'producao' ) {
        $data['dest']       = 'helpdesk@telecontrol.com.br';
        $data['dest_cliente']   = 'lucas.carlos@telecontrol.com.br';
        $data['origem']     = "/www/assist/www/rotinas/einhell/entrada/";
    } else {
        $data['dest']       = 'ronald.santos@telecontrol.com.br';
        $data['dest_cliente']   = 'ronald.santos@telecontrol.com.br';
        $data['origem']     = 'entrada/';
    }

    extract($data);
    
    define('APP', 'Importa Pedidos Recebidos - '.$fabrica);

    $arquivo_err = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica}/ 2> /dev/null ; chmod 0777 {$arquivos}/{$fabrica}/" ); 


    $conn_id = ftp_connect($ftp_server);
    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
    ftp_pasv($conn_id, true);

    ftp_chdir($conn_id, "Telecontrol/Sent Confirm Orders/");

    $lista = ftp_nlist($conn_id, ".");

    foreach($lista as $arquivo){

        $local_file = dirname(__FILE__) . "/entrada/$arquivo";
        $server_file = "$arquivo";
        
        ftp_get($conn_id, $local_file, $server_file, FTP_BINARY); 


        if(file_exists($origem.$arquivo)){

            $sql = "DROP TABLE IF EXISTS einhell_recibo;";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);


            $sql = "CREATE TABLE einhell_recibo (
                      txt_codigo_posto   text,
                      txt_pedido         text,
                      txt_qtde           text
                  )";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $linhas = file_get_contents($origem.$arquivo);
            $linhas = explode("\n",$linhas);

            $erro = $msg_erro;

            foreach($linhas AS $linha){

                $msg_erro = "";

                list($txt_codigo_posto, $txt_pedido, $txt_qtde) = explode(";",$linha);
              
                if(!empty($txt_pedido)){

                    $res = pg_query($con,"BEGIN");

                    $sql = "INSERT INTO einhell_recibo(txt_codigo_posto,txt_pedido,txt_qtde) VALUES('$txt_codigo_posto', '$txt_pedido','$txt_qtde')";

                    
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

            $sql = "UPDATE einhell_recibo SET 
                        txt_codigo_posto = trim(txt_codigo_posto),
                        txt_pedido       = trim(txt_pedido),
                        txt_qtde         = trim(txt_qtde)";
            $res = pg_query($con,$sql);

            $sql = "ALTER TABLE einhell_recibo ADD COLUMN posto INT4";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);
            
            $sql = "ALTER TABLE einhell_recibo ADD COLUMN pedido INT4";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "ALTER TABLE einhell_recibo ADD COLUMN itens INT4";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "ALTER TABLE einhell_recibo ADD COLUMN validado boolean";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "update einhell_recibo set posto = tbl_posto_fabrica.posto
                    FROM tbl_posto_fabrica, tbl_posto
                    WHERE tbl_posto.cnpj = einhell_recibo.txt_codigo_posto
                    AND tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.posto = tbl_posto.posto";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "UPDATE einhell_recibo SET pedido = tbl_pedido.pedido
                    FROM tbl_pedido
                    WHERE tbl_pedido.pedido = einhell_recibo.txt_pedido::numeric
                    AND tbl_pedido.fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "DROP TABLE einhell_recibo_sem_posto";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "SELECT * INTO einhell_recibo_sem_posto 
                            FROM einhell_recibo 
                            WHERE posto IS NULL";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "DROP TABLE einhell_recibo_sem_pedido";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "SELECT * INTO einhell_recibo_sem_pedido 
                            FROM einhell_recibo 
                            WHERE pedido IS NULL";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "SELECT * from einhell_recibo where posto is null or pedido is null";
            $res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);
            if(pg_num_rows($res)>0){
                for($a=0; $a<pg_num_rows($res); $a++){
                    $pedido_null = pg_fetch_result($res, $a, txt_pedido);
                    $posto_null = pg_fetch_result($res, $a, txt_codigo_posto);

                    $log_erro .= "O pedido $pedido_null e posto $posto_null não foi importado, pedido ou posto não encontrado. \n\n";
                }
            }

            $sql = "DELETE FROM einhell_recibo 
                            WHERE posto IS NULL";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "DELETE FROM einhell_recibo 
                            WHERE pedido IS NULL";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "UPDATE einhell_recibo SET itens = (SELECT COUNT(pedido_item) FROM tbl_pedido_item WHERE pedido = einhell_recibo.pedido)";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);


            //gera log caso a qtde de itens do pedido que estamos recebendo para confirmar for diferente do que esta no pedido.
            $sql = "select txt_pedido, itens, (SELECT COUNT(pedido_item) FROM tbl_pedido_item WHERE pedido = einhell_recibo.pedido)as qtd_pedido from einhell_recibo";
            $res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);
            if(pg_num_rows($res)>0){
                for($x=0; $x<pg_num_rows($res); $x++){
                    $itens_qtde = pg_fetch_result($res, $x, itens);
                    $qtd_pedido = pg_fetch_result($res, $x, qtd_pedido);
                    $txt_pedido = pg_fetch_result($res, $x, txt_pedido);

                    if($itens_qtde != $qtd_pedido){
                        $log_erro .= "O pedido $txt_pedido está com quantidade de ítens diferente do exportado. Não será confirmado.\n\n"; 
                    }
                }
            }

            $sql = "UPDATE einhell_recibo SET validado = 't' WHERE itens = txt_qtde::numeric";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "SELECT fn_atualiza_pedido_recebido_fabrica (tbl_pedido.pedido,$login_fabrica, current_date )
                    FROM   einhell_recibo, tbl_pedido
                    WHERE tbl_pedido.pedido = einhell_recibo.pedido
                    AND tbl_pedido.posto = einhell_recibo.posto
                    AND tbl_pedido.recebido_fabrica IS NULL
                    AND einhell_recibo.validado IS TRUE";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

			$sql = "SELECT fn_atualiza_status_pedido ($login_fabrica, tbl_pedido.pedido )
                    FROM   einhell_recibo, tbl_pedido
                    WHERE tbl_pedido.pedido = einhell_recibo.pedido
                    AND tbl_pedido.posto = einhell_recibo.posto
                    AND einhell_recibo.validado IS TRUE";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);
   
            if (!empty($msg_erro)) {
                $msg_erro .= "\n\n".$log_erro;
                $fp = fopen("/tmp/einhell/pedido-recebidos-$arquivo".date('Y-m-d-H-i-s').".err","w");
                fwrite($fp,$msg_erro);
                fclose($fp);
                $msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
                Log::envia_email($data, APP, $msg);

            } else {
                $fp = fopen("/tmp/einhell/pedido-recebidos-$arquivo".date('Y-m-d-H-i-s').".err","w");
                fwrite($fp,$log_erro);
                fclose($fp);

                system("mv $origem$arquivo /tmp/einhell/pedido-recebidos-$arquivo-".date('Y-m-d-H-i-s').".txt");

                Log::log2($data, APP . ' - Executado com Sucesso - ' . date('Y-m-d-H-i'));

                Log::envia_email($data,Date('d/m/Y H:i:s')." - Einhell - Log de Importação de Pedidos Recebidos", $log_erro);

                $sql = "SELECT pedido, txt_qtde AS qtde_enviada FROM einhell_recibo WHERE validado IS NOT TRUE";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0){
                    $pedidos = pg_fetch_all($res);
                    $dest = "helpdesk@telecontrol.com.br";
                    Log::envia_email($data,Date('d/m/Y H:i:s')." - einhell - Pedidos com quantidade de itens divergentes", implode("\r\n",$pedidos));
                }
            }
        }
        ftp_delete($conn_id, $arquivo);
    }

    ftp_close($conn_id);
    

}catch (Exception $e) {
    $e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - Einhell - Importa pedidos recebidos (importa-pedido-recebido.php)", $msg);
}?>
