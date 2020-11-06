<?php

/* Rotina de Importa Boletos */

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $env = ($_serverEnvironment == "development") ? "teste" : "producao";
    $fabrica = 138;

    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Log Class
    */ 
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log de erro - Importação de Boleto Fujitsu")); // Titulo
    
    if ($env == "teste") {
        $logClass->adicionaEmail("guilherme.silva@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    }

    /*
    * Mensagem de Erro
    */
    $msg_erro = array();

    /*
    * TDocs
    */
    include_once "/www/assist/www/class/tdocs.class.php";
    $tDocs = new TDocs($con, $fabrica);

    $dir_boletos = ($env == "teste") ? "boletos/" : "/home/fujitsu/fujitsu-telecontrol/";

    $lista_boletos_arr = scandir($dir_boletos);

    foreach ($lista_boletos_arr as $nome_arquivo) {
        
        if (strlen($nome_arquivo) <= 3) {

            continue;

        }

        list($pedido, $ext) = explode(".", $nome_arquivo);

        if (is_numeric($pedido)) {
            
            if (in_array(strtolower($ext), array("pdf", "png", "jpg", "jpeg"))) {
                
                $sql_pedido_valido = "SELECT pedido FROM tbl_pedido WHERE pedido = {$pedido} AND fabrica = {$fabrica}";
                $res_pedido_valido = pg_query($con, $sql_pedido_valido);

                if (pg_num_rows($res_pedido_valido) > 0) {

                    $arquivo_boleto_arr = array();

                    foreach (glob($dir_boletos.$nome_arquivo) as $arquvo) {
                        $tamanho_arquivo = filesize($arquvo);
                    }

                    $tipo = ($ext == "pdf") ? "application/{$ext}" : "image/{$ext}";

                    $arquivo_boleto_arr["name"]     = $nome_arquivo;
                    $arquivo_boleto_arr["size"]     = $tamanho_arquivo;
                    $arquivo_boleto_arr["tmp_name"] = $dir_boletos.$nome_arquivo;
                    $arquivo_boleto_arr["type"]     = $tipo;
                    
                    $anexou = $tDocs->uploadFileS3($arquivo_boleto_arr, $pedido, false, "pedido", "boletopedido");

                    if(!$anexou){
                    
                        $dir_boleto = $dir_boletos.$nome_arquivo;
                        $msg_erro[] = "Erro ao realizar o upload para o TDocs do boleto do pedido {$pedido} - {$dir_boleto}";

		    }else{
			unlink($dir_boletos.$nome_arquivo);
		    }

                }

            }

        }

    }

    if(count($msg_erro) > 0){

        $logClass->adicionaLog(implode("<br />", $msg_erro));

        if ($logClass->enviaEmails() == "200") {
 #           echo "Log de erro enviado com Sucesso!";
        } else {
            $logClass->enviaEmails();
        }

    }

    $phpCron->termino();


} catch (Exception $e) {

#    echo $e->getMessage();

}

?>
