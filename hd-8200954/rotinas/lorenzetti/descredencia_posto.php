<?php

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','production');  // production Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
	require dirname(__FILE__) . '/../funcoes.php';

    $data_log['login_fabrica'] = 19;
    $data_log['dest'] = 'helpdesk@telecontrol.com.br';
    $data_log['log'] = 2;

    date_default_timezone_set('America/Sao_Paulo');
    $log[] = Date('d/m/Y H:i:s ')."Inicio do Programa";

	$login_fabrica = 19;
	
	$phpCron = new PHPCron($login_fabrica, __FILE__); 
	$phpCron->inicio();

	if (ENV == 'teste' ) {        
        $data_log['dest'] = 'lucas.carlos@telecontrol.com.br';
		$destinatarios_clientes = "lucas.carlos@telecontrol.com.br";
    } else {
        $data_log['dest'] = 'helpdesk@telecontrol.com.br';
        $destinatarios_clientes = "helpdesk@telecontrol.com.br";
    }
        
    $sqlBuscaPosto = "SELECT distinct tbl_posto_fabrica.posto, credenciamento.dias  
                        from tbl_posto_fabrica
                        join (select data, posto, case when tbl_credenciamento.dias is null then 30 else tbl_credenciamento.dias end as dias from tbl_credenciamento where fabrica = $login_fabrica and status = 'EM DESCREDENCIAMENTO' ) as credenciamento ON credenciamento.posto = tbl_posto_fabrica.posto
                        where fabrica = $login_fabrica and credenciamento = 'EM DESCREDENCIAMENTO'  
                        and credenciamento.data < current_date - dias and tbl_posto_fabrica.posto <> 6359";
    $resBuscaPosto = pg_query($con, $sqlBuscaPosto);
    
    for($i=0; $i<pg_num_rows($resBuscaPosto); $i++){

        $res = pg_query($con,"BEGIN");

        $posto = pg_fetch_result($resBuscaPosto, $i, 'posto');

        $sqlInsert = "INSERT INTO tbl_credenciamento (posto, fabrica, data, texto, status) VALUES ($posto, $login_fabrica, now(), 'Descredenciamento Automático', 'DESCREDENCIADO') ";
        $resInsert = pg_query($con, $sqlInsert);

        if(strlen(pg_last_error($con))>0){
            $msg_erro .= pg_last_error($con); 
        }
        $sqlUpdate = "UPDATE tbl_posto_fabrica SET credenciamento = 'DESCREDENCIADO' WHERE posto = $posto and fabrica = $login_fabrica "; 
        $resUpdate = pg_query($con, $sqlUpdate);

        if(strlen(pg_last_error($con))>0){
            $msg_erro .= pg_last_error($con); 
        }

        if(strlen(trim($msg_erro))==0){   
            $res = pg_query($con,"commit");  
        }else{
            $res = pg_query($con,"ROLLBACK");  
            $log_erro[] = $msg_erro;   
        }
    }

    if(count($log_erro) > 0){        

        $header  = "MIME-Version: 1.0\n";
        $header .= "Content-type: text/html; charset=iso-8859-1\n";
        $header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

        mail("$destinatarios_clientes", "TELECONTROL / Lorenzetti ({$data}) - DESCREDENCIA POSTO", implode("<br />", $log_erro), $header);

        $fp = fopen("/tmp/Lorenzetti/descredencia_posto.err","w");
        fwrite($fp,implode("<br />", $log_erro));
        fclose($fp);
    }

    $phpCron->termino();

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\nErro na linha: " . $e->getLine() . "\r\nErro descrição: " . $e->getMessage();
    Log::envia_email($data_log,Date('d/m/Y H:i:s')." - Erro ao descredenciar postos", $msg);
}
