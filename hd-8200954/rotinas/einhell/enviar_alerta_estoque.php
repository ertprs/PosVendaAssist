<?php

error_reporting(E_ALL);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/communicator.class.php';
    	

    $fabrica_nome   = "Einhell";
    $fabrica        = 160;

    $ambiente = "producao";

    if ($ambiente == "devel") {
    	$url_base = "http://novodevel.telecontrol.com.br/~lucas/PosVenda";
    	$email_log = "lucas.carlos@telecontrol.com.br";
    } else {
    	$url_base = "https://posvenda.telecontrol.com.br/assist";
    	$email_log = "helpdesk@telecontrol.com.br";
    }

    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Log Class
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro envio alerta estoque - Einhell")); // Titulo
    $logClass->adicionaEmail($email_log);

    //busca o posto 
    $sqlPosto = "SELECT tbl_intencao_compra_peca.posto, tbl_posto.nome, tbl_posto_fabrica.contato_email FROM tbl_intencao_compra_peca join tbl_posto on tbl_posto.posto = tbl_intencao_compra_peca.posto inner join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $fabrica where tbl_intencao_compra_peca.fabrica = $fabrica  and informado = 'f' and data_informacao is null group by tbl_intencao_compra_peca.posto, tbl_posto.nome, tbl_posto_fabrica.contato_email ";
    $resPosto = pg_query($con, $sqlPosto);
    if(strlen(pg_last_error($con))>0){
        $msg_erro .= pg_last_error($con);
    }
    for($i=0;$i<pg_num_rows($resPosto);$i++){

        $res = pg_query($con,"BEGIN TRANSACTION");

        $posto          = pg_fetch_result($resPosto, $i, posto);
        $nome           = pg_fetch_result($resPosto, $i, nome);
        $contato_email  = pg_fetch_result($resPosto, $i, contato_email);

        $msg = "As peças solicitadas anteriormente nos pedidos abaixo estão disponíveis para compra: <br><br> <b>Pedido - Referência - Descrição </b>";

        $msgPeca = "";
        $enviarEmail = false;

        $sql = "SELECT tbl_peca.peca, tbl_peca.descricao, tbl_peca.referencia, tbl_intencao_compra_peca.pedido, tbl_intencao_compra_peca.posto, tbl_intencao_compra_peca.qtde from tbl_intencao_compra_peca join tbl_peca on tbl_peca.peca = tbl_intencao_compra_peca.peca and tbl_peca.fabrica = $fabrica where tbl_intencao_compra_peca.fabrica = $fabrica and informado = 'f' and data_informacao is null and tbl_intencao_compra_peca.posto = $posto ";
        $res = pg_query($con, $sql);
        if(strlen(pg_last_error($con))>0){
            $msg_erro .= pg_last_error($con);
        }

        for($a=0; $a<pg_num_rows($res); $a++){
            $referencia = pg_fetch_result($res, $a, referencia);
            $descricao  = pg_fetch_result($res, $a, descricao);
            $peca       = pg_fetch_result($res, $a, peca);
            $qtde       = pg_fetch_result($res, $a, qtde);
            $pedido     = pg_fetch_result($res, $a, pedido);

            $sqlEstoque = "SELECT qtde FROM tbl_posto_estoque WHERE posto = 4311 
                    AND  qtde >= $qtde 
                    and peca = ".$peca;
            $resEstoque = pg_query($con, $sqlEstoque);
            if(pg_num_rows($resEstoque)>0){

                $msgPeca .= "<br>$pedido - $referencia - $descricao ";  

                $enviarEmail = true;

                $sqlupd = "UPDATE tbl_intencao_compra_peca set informado = true, data_informacao = now() where posto = $posto and fabrica = $fabrica and peca = $peca ";
                $resupd = pg_query($con, $sqlupd);
                if(strlen(pg_last_error($con))>0){
                    $msg_erro .= pg_last_error($con);
                }
            }
        }
        $msg_final = "<br><br> Caso tenha interesse em adquirir as peças, favor lançar um novo pedido. ";
        $msg .= $msgPeca. $msg_final;       
        
        if(strlen($msg_erro)>0){
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }else{
            $res = pg_query($con,"COMMIT TRANSACTION");

			if($enviarEmail == true){
                $mailTc = new TcComm('smtp@posvenda');
                $res = $mailTc->sendMail(
                    $contato_email,
                    'Disponibilidade de Peças - Einhell',
                    $msg,
                    'noreply@telecontrol.com.br'
                );    
            }
        }
    }
    $phpCron->termino();
} catch (Exception $e) {
    echo $e->getMessage();
}
