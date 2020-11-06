<?php 
/**
 *
 * verifica-data-audiencia.php
 *
 * Enviar email para o Responsável 1 dia antes da data de audiência 1 e 2 
 *
 * @author  Lucas Maestro
 * @version 2017-07-19
 *
*/
error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // producao Alterar para produção ou algo assim

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica']  = 42;
    $data['fabrica_nome']   = 'makita';
    $data['arquivo_log']    = 'verifica-data-audiencia';
    $data['log']            = 2;
    $data['arquivos']       = "/tmp";
    $data['data_sistema']   = Date('Y-m-d');
    $logs                   = array();
    $logs_erro              = array();
    $logs_cliente           = array();
    $erro                   = false;

    $login_fabrica = 42;
    $phpCron = new PHPCron($login_fabrica, __FILE__);
    $phpCron->inicio();

    if (ENV == 'producao' ) {
        $data['dest']       = 'helpdesk@telecontrol.com.br';
    } else {
        $data['dest']       = 'lucas.carlos@telecontrol.com.br';
    }

    $sql = "SELECT  tbl_processo_item.data_audiencia1, tbl_processo_item.data_audiencia2, tbl_processo.admin, tbl_admin.nome_completo, tbl_admin.email, tbl_processo.numero_processo
        FROM tbl_processo_item 
        INNER JOIN tbl_processo ON tbl_processo.processo = tbl_processo_item.processo and tbl_processo.fabrica = $login_fabrica
        INNER JOIN tbl_admin on tbl_admin.admin = tbl_processo.admin AND tbl_admin.fabrica = $login_fabrica
        WHERE ( tbl_processo_item.data_audiencia1::date = current_date + 1 or  tbl_processo_item.data_audiencia2::date = current_date + 1) AND tbl_processo.fabrica = $login_fabrica ";
    $res = pg_query($con, $sql);

    if(strlen(pg_last_error($con))>0){
        $msg_erro = pg_last_error($con);
    }

    for($i=0; $i<pg_num_rows($res); $i++){
        $data_audiencia1    = pg_fetch_result($res, $i, data_audiencia1);
        $data_audiencia2    = pg_fetch_result($res, $i, data_audiencia2);
        $nome_completo      = pg_fetch_result($res, $i, nome_completo);
        $numero_processo    = pg_fetch_result($res, $i, numero_processo);
        $email              = pg_fetch_result($res, $i, email);

        $hora_audiencia1 = substr($data_audiencia1, 11);
        list($ano, $mes, $dia)  = explode("-",substr($data_audiencia1, 0, 10));
        $audiencias1 = "$dia/$mes/$ano $hora_audiencia1";

        $hora_audiencia2 = substr($data_audiencia2, 11);
        list($ano, $mes, $dia)  = explode("-",substr($data_audiencia2, 0, 10));
        $audiencias2 = "$dia/$mes/$ano $hora_audiencia2";

        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->IsHTML();
        #$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
        $mail->Subject = Date('d/m/Y')." - Alerta de Audiência ";
        $mail->Body = "Alerta de audiências marcadas para o processo de número $numero_processo, favor confirmar presença do preposto. \n Audiências1: $audiencias1\n Audiências2: $audiencias2\n ";
        $mail->AddAddress("$email");
        $mail->Send();
        
    }
    
    if(strlen($msg_erro) >0 ){
        $msg = "Erro ao executar rotina 'Verifica audiência - Makita' ";

        Log::envia_email($data,Date('d/m/Y H:i:s')." - Makita - Erro na verifica audiência", $msg);
    }

?>