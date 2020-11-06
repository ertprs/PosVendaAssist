<?php
/**
 * 2017.05.08
 * @author  Guilherme Monteiro
 * @version 2.0
 *
*/

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/communicator.class.php';
include dirname(__FILE__) . '/../funcoes.php';

/* AmazonTC */
include dirname(__FILE__) . '/../../class/aws/s3_config.php';
include S3CLASS;

/* SIMPLE HTML DOM */
include dirname(__FILE__) . '/../../class/simpleHtmlDom.class.php';
$htmlDom = new simple_html_dom();

$login_fabrica = 174;


/*
* Log
*/
$logClass = new Log2();

$logClass->adicionaLog(array("titulo" => "Log erro - Leitor Email Aquarius.")); // Titulo
if ($_serverEnvironment == 'development') {
    $logClass->adicionaEmail("guilherme.monteiro@telecontrol.com.br");
} else {
    $logClass->adicionaEmail('luis.carlos@telecontrol.com.br');
    $logClass->adicionaEmail('guilherme.curcio@telecontrol.com.br');
}

$msg_erro = array();

/* VERIFICA SE A ROTINA AINDA ESTA PROCESSANDO */
$arquivo_rotina = basename($_SERVER["SCRIPT_FILENAME"]);
$processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina}"));
$arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

$count_routine = 0;
foreach ($processos as $value) {
    if (preg_match("/(.*)php (.*)\/midea\/{$arquivo_rotina}/", $value)) {
        $count_routine += 1;
    }
}
if ($count_routine > 2) { exit; }
/* FIM VERIFICAÇÃO */

function passwordDecrypt($enc) {
    $key1 = preg_replace("/\/.+/", "", $enc);
    $key2 = preg_replace("/.+\//", "", $enc);
    $key = $key2.$key1;
    $key = hex2bin($key);
    $enc = str_replace($key1."/", "", $enc);
    $enc = str_replace("/".$key2, "", $enc);
    return openssl_decrypt($enc, 'aes-128-cbc', $key);
}

function flushLog($msg) {
    global $login_fabrica;
    $arq = 'aquarius';
    $arquivo_log = "/tmp/leitor-email-$arq-".date("Ymd").".txt";

    ob_start();
    if (!file_exists($arquivo_log)) {
        system("touch {$arquivo_log}");
    }else{
        echo "\n";
    }

    echo date('H:m')." - $msg";
    $b = ob_get_contents();

    file_put_contents($arquivo_log, $b, FILE_APPEND);
    ob_end_flush();
    ob_clean();
}

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();

/* PREPARES */
pg_prepare($con, 'inclui_atendente', "UPDATE tbl_hd_chamado SET atendente = $1 WHERE hd_chamado = $2 AND fabrica = $login_fabrica");

pg_prepare($con, 'consulta_admins', "SELECT
                                        tbl_callcenter_email_admin.admin,
                                        tbl_callcenter_email.limite_atendimento
                                    FROM tbl_callcenter_email
                                        JOIN tbl_callcenter_email_admin USING(callcenter_email)
                                        JOIN tbl_admin ON tbl_admin.admin = $1 AND tbl_admin.email = tbl_callcenter_email.email AND tbl_admin.fabrica = {$login_fabrica}
                                    WHERE tbl_callcenter_email.fabrica = {$login_fabrica}");

pg_prepare($con, 'qtde_atend_admin', "SELECT
                                        COUNT(tbl_hd_chamado_extra.hd_chamado) AS qtde_chamado
                                    FROM tbl_hd_origem_admin
                                        JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_origem_admin.hd_chamado_origem AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
                                        JOIN tbl_admin ON tbl_admin.admin = tbl_hd_origem_admin.admin AND tbl_admin.fabrica = {$login_fabrica} AND tbl_admin.admin = $1
                                        LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.atendente = tbl_hd_origem_admin.admin AND tbl_hd_chamado.fabrica = {$login_fabrica} AND lower(tbl_hd_chamado.status) not in ('resolvido', 'cancelado')
					LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_extra.hd_chamado_origem = tbl_hd_chamado_origem.hd_chamado_origem
                                    WHERE tbl_hd_origem_admin.fabrica = {$login_fabrica}
                                        AND tbl_admin.ativo IS TRUE
                                        AND tbl_hd_chamado_origem.descricao = 'Intelipost'");

$fila_distribuicao = array();
try{
    /* LISTA TODOS OS CHAMADOS SEM ATENDENTES */
    $sql = "SELECT DISTINCT hd_chamado, admin, data
            FROM tbl_hd_chamado WHERE fabrica = {$login_fabrica} AND atendente IS NULL ORDER BY data, admin";
    $res  = pg_query($con, $sql);
    $rows = pg_num_rows($res);
    if ($rows > 0) {
        for ($i = 0; $i < $rows; $i++) {
            $admin = pg_fetch_result($res, $i, 'admin');
            $hd_chamado = pg_fetch_result($res, $i, 'hd_chamado');
            $fila_distribuicao[$admin][] = $hd_chamado;
        }
    }

    $sql = "SELECT
                tbl_callcenter_email.email,
                tbl_callcenter_email.hostname,
                tbl_callcenter_email.senha,
                tbl_admin.admin
            FROM tbl_callcenter_email JOIN tbl_admin ON(tbl_callcenter_email.callcenter_email = tbl_admin.callcenter_email AND tbl_admin.fabrica = {$login_fabrica})
            WHERE ativa IS TRUE AND tbl_callcenter_email.fabrica = {$login_fabrica}";
    $res  = pg_query($con, $sql);
    $rows = pg_num_rows($res);
    
    if ($rows > 0) {
        for ($i = 0; $i < $rows; $i++) {
            $hostname = '{'.pg_fetch_result($res, $i, 'hostname').'}INBOX';
            $username = pg_fetch_result($res, $i, 'email');
            $password = passwordDecrypt(pg_fetch_result($res, $i, 'senha'));
            $admin    = pg_fetch_result($res, $i, 'admin');
            $inbox = imap_open($hostname, $username, $password);
            if (strlen(imap_last_error()) > 0) {
                $msg_erro[] = "Erro ao tentar iniciar conexão com o email: $username. Erro: ".imap_last_error();
                //throw new Exception("Erro ao tentar iniciar conexão com o email: $username. Erro: ".imap_last_error());
            }
            $emails = imap_search($inbox,'UNSEEN');
            if(is_array($emails)) {

                foreach($emails as $email_number) {
                    $struct   = imap_fetchstructure($inbox, $email_number);

                    $email_header = imap_header($inbox, $email_number);

                    $titulo_email = strtolower($email_header->subject);
                    $titulo_email = iconv_mime_decode($titulo_email,0,"UTF-8");

		    if (!preg_match("/o pedido .+ aguardando retirada/", $titulo_email) && !preg_match("/^houve falha na entrega do pedido/", $titulo_email) && !preg_match("/status. n.+visitado$/", $titulo_email)) {
                        continue;
                    }

		    if ($email_header->from[0]->host != "mcpm.com.br") {
			continue;
		    }


                    $partstring     = '';
                    $partattachment = array();
                    $filename       = '';
                    $attachment     = array();
                    for ($k=0; $k < count($struct->parts); $k++) {
                        if (count($struct->parts[$k]->parts)) { /* MULTIDIMENSIONAL */
                            for ($j=0; $j < count($struct->parts[$k]->parts); $j++) {
                                if ($struct->parts[$k]->parts[$j]->subtype == 'PLAIN') {
                                    $partstring = (1 + $k).".".($j + 1);
                                }
                                if ($struct->parts[$k]->parts[$j]->disposition == 'ATTACHMENT') {
                                    for ($aux=0; $aux < count($struct->parts[$k]->parts[$j]->parameters); $aux++) {
                                        if ($struct->parts[$k]->parts[$j]->parameters[$aux]->attribute == 'NAME') {
                                            $filename = $struct->parts[$k]->parts[$j]->parameters[$aux]->value;
                                            $partattachment[] = array((1 + $k).".".($j + 1), $filename);
                                        }
                                    }
                                }
                            }
                        }else{
                            if ($struct->parts[$k]->subtype == 'PLAIN') {
                                $partstring = $k + 1;
                            }
                            if ($struct->parts[$k]->disposition == 'ATTACHMENT') {
                                for ($aux=0; $aux < count($struct->parts[$k]->parameters); $aux++) {
                                    if ($struct->parts[$k]->parameters[$aux]->attribute == 'NAME') {
                                        $filename = $struct->parts[$k]->parameters[$aux]->value;
                                        $partattachment[] = array($k + 1, $filename);
                                    }
                                }
                            }
                        }
                    }

                    $message = imap_qprint(imap_fetchbody($inbox, $email_number, 2, FT_PEEK));

                    $htmlDom = str_get_html("<html><body>$message</body></html>");
                
                    $array_dados_html = array();
                    foreach($htmlDom->find('table tbody tr td') as $header) {
                        $array_dados_html[] = $header->innertext;
                    }
                    $dados_msg = $array_dados_html[17];

                    $codigo_rastreio = $array_dados_html[22];
                    $codigo_rastreio = trim(strip_tags($codigo_rastreio));

                    $exp_dados_msg = explode("<br>", $dados_msg);
                    
                    $array_dados_msg = array();
                    foreach ($exp_dados_msg as $key => $value) {
                        $value = strip_tags($value);
                        $array_dados_msg[] = preg_replace("/\*.+\*/", "", $value);
                    }
                    $dados_msg = array_filter($array_dados_msg);
                    
                    if (count($dados_msg) == 5){
                        $nome_consumidor        = utf8_decode($dados_msg[0]);
                        $dados_endereco         = explode(",", $dados_msg[1]);
                        $rua_consumidor         = utf8_decode($dados_endereco[0]);
                        $numero_consumidor      = $dados_endereco[1];
                        $numero_consumidor      = substr(preg_replace('/(^\d+).+/','$1', trim($numero_consumidor)), 0, 20);
                        $complemento_consumidor = utf8_decode($dados_msg[2]);
                        $complemento_consumidor = substr($complemento_consumidor, 0, 38);
                        $bairro_consumidor      = utf8_decode($dados_msg[3]);
                        $dados_cidade           = explode(" ", $dados_msg[4]);
                        $cep_consumidor         = $dados_cidade[0];
                        $cep_consumidor         = str_replace("-", "", $cep_consumidor);
                        unset($dados_cidade[0]);
                        $cidade_consumidor      = implode(' ', $dados_cidade);
                        $cidade_consumidor      = str_replace("-", "", $cidade_consumidor);
                        $cidade_consumidor      = trim(utf8_decode($cidade_consumidor));
                    }else{
                        $nome_consumidor        = utf8_decode($dados_msg[0]);
                        $dados_endereco         = explode(",", $dados_msg[1]);
                        $rua_consumidor         = utf8_decode($dados_endereco[0]);
                        $numero_consumidor      = $dados_endereco[1];
                        $numero_consumidor      = substr(preg_replace('/(^\d+).+/','$1', trim($numero_consumidor)), 0, 20);
                        $bairro_consumidor      = utf8_decode($dados_msg[2]);
                        $dados_cidade           = explode(" ", $dados_msg[3]);
                        $cep_consumidor         = $dados_cidade[0];
                        $cep_consumidor         = str_replace("-", "", $cep_consumidor);
                        unset($dados_cidade[0]);
                        $cidade_consumidor      = implode(' ', $dados_cidade);
                        $cidade_consumidor      = str_replace("-", "", $cidade_consumidor);
                        $cidade_consumidor      = trim(utf8_decode($cidade_consumidor));
                    }

		    $cidade_consumidor = str_replace('\'', '', $cidade_consumidor);
		    $cidade_consumidor = str_replace('DO OESTE', 'DOESTE', $cidade_consumidor);
		    $cidade_consumidor = trim(preg_replace('/\(.+/', '', $cidade_consumidor));

                    $sql_cidade = "SELECT 
                                cidade,cod_ibge 
                            FROM tbl_cidade 
                            WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais(trim('{$cidade_consumidor}')))";
                    $res_cidade = pg_query($con, $sql_cidade);
                    
                    if (pg_num_rows($res_cidade) > 0){
                        $cidade_consumidor = pg_fetch_result($res_cidade, 0, 'cidade');
                    }

                    if (count($partattachment)) {
                        foreach ($partattachment as $part) {
                            $file = imap_fetchbody($inbox, $email_number, $part[0], FT_PEEK);

                            $decoded_data = base64_decode($file);
                            if ($decoded_data == false) {
                                $attachment[] = array("filename" => addslashes($part[1]), "filedata" => $file);
                            } else {
                                $attachment[] = array("filename" => addslashes($part[1]), "filedata" => $decoded_data);
                            }
                        }
                    }
                    
                    /* RETIRA ASSINATURA DO EMAIL SE POSSUIR */
                    $assinatura = strpos($message, '--=20');

                    if ($assinatura) {
                        $message = trim(substr($message, 0, $assinatura));
                    }

                    $message = str_replace("'","",$message);
                    $overview = imap_fetch_overview($inbox, $email_number);
                    $email    = TcComm::parseEmail($overview[0]->from);
                    $email    = $email[0];
                    $nome     = $overview[0]->from;
                    $nome     = iconv_mime_decode(preg_replace('/<.+$/','',$nome));

                    $valida = strstr($email, 'noreply');
                    if($valida == true){
                        continue;
                    }
                    
                    pg_query($con, 'BEGIN');
                    /* INSERE NOVO ATENDIMENTO SEM VINCULAR PARA NENHUM ATENDENTE */
                    $data_providencia = date('Y-m-d').' 00:00:00';
                    $sql_insert = "INSERT INTO tbl_hd_chamado(
                                        admin,
                                        fabrica_responsavel,
                                        fabrica,
                                        titulo,
                                        status,
                                        atendente,
                                        categoria
                                    )VALUES(
                                        $admin,
                                        $login_fabrica,
                                        $login_fabrica,
                                        'Atendimento interativo',
                                        'Aberto',
                                        null,
                                        'reclamacao_produto'
                                    )RETURNING hd_chamado";
                    $res_insert = pg_query($con, $sql_insert);
                    if (strlen(pg_last_error()) > 0) {
                        pg_query($con, 'ROLLBACK');
                        $msg_erro[] = "(1) Erro ao tentar inserir uma novo atendimento do email de origem: $email. Erro: ".pg_last_error();
                        //throw new Exception("(1) Erro ao tentar inserir uma novo atendimento do email de origem: $email. Erro: ".pg_last_error());
                    }
                    $hd_chamado = pg_result($res_insert, 0, 'hd_chamado');
                    
                    $sql_origem = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} AND descricao = 'Intelipost' ";
                    $res_origem = pg_query($con, $sql_origem);
                    
                    if(pg_num_rows($res_origem) == 0){
                        $msg_erro[] = "Não foi possível encontrar o código da origem do chamado do tipo Email";
                        //throw new Exception("Não foi possível encontrar o código da origem do chamado do tipo Email");
                    }
                    $hd_chamado_origem = pg_fetch_result($res_origem, 0, 'hd_chamado_origem');

                    if (strlen($complemento_consumidor) > 0){
                       $valor_complemento = $complemento_consumidor;
                    }else{
                        $valor_complemento = '';
                    }

                    $titulo_email = str_replace("fwd:", "", $titulo_email);
                    $titulo_email = utf8_decode($titulo_email);

                    $codigo_rastreio = utf8_decode($codigo_rastreio);
                    $reclamado = "$titulo_email <br/> $codigo_rastreio";

		    $nome_consumidor = addslashes(str_replace("'", "", $nome_consumidor));
		    $valor_complemento = addslashes(str_replace("'", "", $valor_complemento));

                    $sql_insert = "INSERT INTO tbl_hd_chamado_extra(
                                        hd_chamado,
                                        origem,
                                        nome,
                                        endereco,
                                        numero,
                                        complemento,
                                        bairro,
                                        cep,
                                        cidade,
                                        abre_os,
                                        atendimento_callcenter,
                                        hd_chamado_origem,
                                        reclamado
                                    )VALUES(
                                        $hd_chamado,
                                        'Intelipost',
                                        E'$nome_consumidor',
                                        '$rua_consumidor',
                                        '$numero_consumidor',
                                        E'$valor_complemento',
                                        '$bairro_consumidor',
                                        '$cep_consumidor',
                                        $cidade_consumidor,
                                        'f',
                                        't',
                                        $hd_chamado_origem,
                                        E'$reclamado'
                                    )";
                    pg_query($con, $sql_insert);
                    
                    if (strlen(pg_last_error()) > 0) {
			$error = pg_last_error();
			echo $sql_insert;
			echo $error;
                        pg_query($con, 'ROLLBACK');
                        $msg_erro[] = "(2) Erro ao tentar inserir uma novo atendimento do email de origem: $email. Titulo: $titulo_email";
                        //throw new Exception("(2) Erro ao tentar inserir uma novo atendimento do email de origem: $email. Erro: ".pg_last_error());
                    }
                    
                    if (!count($msg_erro)){
                        imap_setflag_full($inbox, $email_number, "\\Seen");
                        pg_query($con, 'COMMIT');
                        $fila_distribuicao[$admin][] = $hd_chamado;
                    }
                }
            }
        }
    }
}catch(Exception $e){
    flushLog($e->getMessage());
}

/* INICIALIZA DISTRIBUIÇÃO */
if (count($fila_distribuicao)) {
    foreach ($fila_distribuicao as $admin => $array_chamados) {
        $array_admins = array();
        $res_admins   = pg_execute($con, 'consulta_admins', array($admin));
        for ($i = 0; $i < pg_num_rows($res_admins); $i++) {
            $atendente = pg_fetch_result($res_admins, $i, 'admin');
            $limite_atendimento = pg_fetch_result($res_admins, $i, 'limite_atendimento');

            $res_qtde = pg_execute($con, 'qtde_atend_admin', array($atendente));
            $limite_atendimento = $limite_atendimento - pg_fetch_result($res_qtde, 0, 'qtde_chamado');
            $array_admins[$admin][$atendente] = ($limite_atendimento < 0) ? 0 : $limite_atendimento;

            #echo "AT:$atendente,  LIMT: $limite_atendimento -> qtde:".pg_fetch_result($res_qtde, 0, 'qtde_chamado');
        }
       
        arsort($array_admins[$admin]);
        
        foreach ($array_chamados as $hd_chamado) {
            foreach ($array_admins[$admin] as $atendente => $limite_atendimento) {
                if (($limite_atendimento - 1) < 0) { continue; }

                /* ATUALIZA VALOR E ORDENA NOVAMENTE */
                $array_admins[$admin][$atendente] = $limite_atendimento - 1;
                arsort($array_admins[$admin]);

                $res = pg_execute($con, 'inclui_atendente',array($atendente, $hd_chamado));
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro[] = "Erro ao tentar distribuir o chamado $hd_chamado. Erro: ".pg_last_error();
                    flushLog("Erro ao tentar distribuir o chamado $hd_chamado. Erro: ".pg_last_error());
                }
                break;
            }
        }
    }
}

if(!empty($msg_erro)){
    $logClass->adicionaLog(implode("<br />", $msg_erro));

    if($logClass->enviaEmails() == "200"){
      echo "Log de erro enviado com Sucesso!";
    }else{
      $logClass->enviaEmails();
    }
}


$phpCron->termino();

?>
