<?php
/**
 *
 * importa-ratreio-faturamento.php
 *
 * Importação dos código de rastreio por faturamento NF
 *
 * @author  Lucas Maestro
 * @version 2016.03.23
 *
*/

function retira_acentos( $texto ){
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
    return str_replace( $array1, $array2, $texto );
}



error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim


try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
    include dirname(__FILE__) . '/conexao_ftp_einhell.php';


    $local_file = dirname(__FILE__) . '/entrada/rastreio-faturamento.txt';
    $server_file = "Telecontrol/Sent Data/rastreio-faturamento.txt";

    $conn_id = ftp_connect($ftp_server);
    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
    ftp_pasv($conn_id, true);

    ftp_get($conn_id, $local_file, $server_file, FTP_BINARY); 

    ftp_close($conn_id);

    $data['login_fabrica']  = 160;
    $data['fabrica']        = 'einhell';
    $data['arquivo_log']    = 'importa-ratreio-faturamento';
    $data['log']            = 2;
    $data['arquivos']       = "/tmp";
    $data['data_sistema']   = Date('Y-m-d');
    $logs                   = array();
    $logs_erro              = array();
    $logs_cliente           = array();
    $erro                   = false;

    if (ENV == 'producao' ) {
        $data['dest']       = 'helpdesk@telecontrol.com.br';
        $data['dest_cliente']   = 'daniel.pereira@einhell.com';
        $data['origem']     = "/www/assist/www/rotinas/einhell/entrada/";
        $data['file']       = 'rastreio-faturamento.txt';
    } else {
        $data['dest']       = 'ronald.santos@telecontrol.com.br';
        $data['dest_cliente']   = 'lucas.maestro@telecontrol.com.br';
        $data['origem']     = 'entrada/';
        $data['file']       = 'rastreio-faturamento.txt';
    }

    extract($data);
    
    define('APP', 'Importa Pedidos Recebidos - '.$fabrica);

    $arquivo_err = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica}/ 2> /dev/null ; chmod 0777 {$arquivos}/{$fabrica}/" ); 


    if(file_exists($origem.$file)){
       
        $linhas = file_get_contents($origem.$file);
        $linhas = explode("\n",$linhas);

        foreach($linhas AS $linha){

            $msg_erro = "";

			list($txt_cnpj_posto, $txt_nf, $txt_rastreio) = explode(";",$linha);
			$txt_rastreio = str_replace("\r","",$txt_rastreio);
            if(!empty($txt_cnpj_posto)){ 

                $res = pg_query($con,"BEGIN");

                $txt_nf = str_pad($txt_nf, 9, "0", STR_PAD_LEFT);

                $sql_rastreio = "SELECT tbl_faturamento.faturamento
                                            FROM tbl_faturamento 
                                            INNER JOIN tbl_posto on tbl_posto.posto = tbl_faturamento.posto
                                            WHERE tbl_posto.cnpj = '$txt_cnpj_posto' 
                                            and tbl_faturamento.nota_fiscal = '$txt_nf'
                                            ";

                $res_rastreio = pg_query($con, $sql_rastreio);

                if(pg_num_rows($res_rastreio)>0){
                    $faturamento = pg_fetch_result($res_rastreio, 0, 'faturamento');

                    $txt_rastreio = retira_acentos("$txt_rastreio");

                    $sql_update = "UPDATE tbl_faturamento SET  conhecimento = '$txt_rastreio' WHERE faturamento = $faturamento";
                    $res_update = pg_query($con, $sql_update);
                    

                }

                $msg_erro = pg_last_error();

                if(!empty($msg_erro)){
                    $res = pg_query($con,"ROLLBACK");
                    $erro .= $msg_erro;
                } else {
                    $res = pg_query($con,"COMMIT");
                }

            }
        }

    }

       system("mv $origem$file /tmp/einhell/rastreio-faturamento".date('Y-m-d-H-i').".txt");

}catch (Exception $e) {
    $e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - einhell - Importa código de rastreio (importa-ratreio-faturamento.php)", $msg);
}






?>
