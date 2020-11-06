<?php
/**
 *
 * importa-posto.php
 *
 * Importação de postos Makita
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'marisa.silvana@telecontrol.com.br');

try {
    #include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
    include dirname(__FILE__)."/../../dbconfig.php";
    include dirname(__FILE__)."/../../includes/dbconnect-inc.php";
    include dirname(__FILE__)."/../funcoes.php";
    
    // MAKITA
    $fabrica = 42;
    $fabrica_nome = 'makita';

    function trataEmail($var) {
        if (!empty($var)) {
            $var = trim($var);
            $var = str_replace(" ", ";", $var);
            $var = str_replace("/", ";", $var);
            $var = str_replace(",", ";", $var);
            $var = explode(";", $var);
            $var = $var[0];
        }
        return $var;
    }

    function strtim($var) {
        if (!empty($var)) {
            $var = trim($var);
            $var = str_replace("'", "\'", $var);
            $var = str_replace("/", "", $var);
        }
        return $var;
    }

    function retiraAspas($var) {
        if (!empty($var)) {
            $var = trim($var);
            $var = str_replace("'", "", $var);
        }
        return $var;
    }

    function logErro($sql, $error_msg) {
        $err = "==============================\n\n";
        $err.= $sql . "\n\n";
        $err.= $error_msg . "\n\n";
        return $err;
    }

    function cortaStr($str, $len) {
        return substr($str, 0, $len);
    }

    function adicionalTrim($str, $len = 0) {
        $str = str_replace(".", "", $str);
        $str = str_replace("-", "", $str);
        if ($len != 0) {
            $str = cortaStr($str, $len);
        }
        return $str;
    }

    $diretorio_origem = '/www/cgi-bin/' . $fabrica_nome . '/entrada';
    //$diretorio_origem = 'entrada';//teste local
    $arquivo_origem   = 'posto.txt';


    date_default_timezone_set('America/Sao_Paulo');
    $now = date('Ymd_His');

    $log_dir = '/tmp/' . $fabrica_nome . '/logs';
    $arq_log = $log_dir . '/importa-posto-' . $now . '.log';
    $err_log = $log_dir . '/importa-posto-err-' . $now . '.log';
    $err_log_admin = $log_dir . '/importa-posto-erro-email-' . $now . '.log';

    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
          throw new Exception("ERRO: Não foi possível criar logs. Falha ao criar diretório: $log_dir");
        }
    }

    $arquivo = $diretorio_origem . '/' . $arquivo_origem;

    if (file_exists($arquivo) and (filesize($arquivo) > 0)) {
        $conteudo = file_get_contents($arquivo);
        $conteudo = explode("\n", $conteudo);

        $nlog      = fopen($arq_log, "w");
        $elog      = fopen($err_log, "w");
        $elogAdmin = fopen($err_log_admin, "w");

        foreach ($conteudo as $linha) {
            if (!empty($linha)) {
                list (
                        $codigo, 
                        $razao, 
                        $fantasia, 
                        $cnpj, 
                        $insc, 
                        $endereco, 
                        $numero, 
                        $complemento, 
                        $bairro, 
                        $cep, 
                        $cidade, 
                        $estado, 
                        $email, 
                        $telefone, 
                        $fax, 
                        $contato, 
                        $capital_interior,
                        $cod_atividade,
                        $categoria
                    ) = explode ("\t",$linha);

                $original = array(
                                    $codigo, 
                                    $razao, 
                                    $fantasia, 
                                    $cnpj, 
                                    $insc, 
                                    $endereco, 
                                    $numero, 
                                    $complemento, 
                                    $bairro, 
                                    $cep, 
                                    $cidade, 
                                    $estado, 
                                    $email, 
                                    $telefone, 
                                    $fax, 
                                    $contato, 
                                    $capital_interior,
                                    $cod_atividade,
                                    $categoria
                                );


                $codigo             = strtim($codigo);
                $razao              = strtim($razao);
                $fantasia           = strtim($fantasia);
                $cnpj               = strtim($cnpj);
                $insc               = strtim($insc);
                $endereco           = strtim($endereco);
                $numero             = strtim($numero);
                $complemento        = strtim($complemento);
                $bairro             = strtim($bairro);
                $cep                = strtim($cep);
                $cidade             = strtim($cidade);
                $estado             = strtim($estado);
                $email              = trataEmail($email);
                $telefone           = strtim($telefone);
                $fax                = strtim($fax);
                $contato            = strtim($contato);
                $capital_interior   = retiraAspas($capital_interior);
                $cod_atividade      = strtim($cod_atividade);
                $categoria          = strtim($categoria);

                if (!empty($codigo)) {
                    $codigo = "'". $codigo ."'";
                } else {
                    $codigo = "null";
                }

                if (!empty($razao)) {
                    $razao = substr($razao,0,60);
                    $razao = pg_escape_string($razao);
                    $razao = "'". $razao ."'";

                } else {
                    $razao = "null";
                }

                if (!empty($fantasia)) {
                    $fantasia = substr($fantasia,0,30);
                    $fantasia = pg_escape_string($fantasia);
                    $fantasia = "'". $fantasia ."'";
                } else {
                    $fantasia = "null";
                }

                if (!empty($cnpj)) {
                    $cnpj = "'". $cnpj ."'";
                } else {
                    $cnpj = "null";
                }

                if (!empty($insc)) {
                    $insc = "'". $insc ."'";
                } else {
                    $insc = "null";
                }

                if (!empty($endereco)) {
                    $endereco = substr($endereco,0,50);
                    $endereco = pg_escape_string($endereco);
                    $endereco = "'". $endereco ."'";
                } else {
                    $endereco = "null";
                }

                if (!empty($numero)) {
                    $numero = substr($numero,0,10);
                    $numero = "'". $numero ."'";
                } else {
                    $numero = "null";
                }
            
                if (!empty($complemento)) {
                    $complemento = substr($complemento,0,20);
                    $complemento = pg_escape_string($complemento);
                    $complemento = "'". $complemento ."'";
                } else {
                    $complemento = "null";
                }
                
                if (!empty($bairro)) {
                    $bairro = substr($bairro,0,20);                    
                    $bairro = pg_escape_string($bairro);
                    $bairro = "'". $bairro ."'";
                } else {
                    $bairro = "null";
                }
            
                if (!empty($cep)) {
                    $cep = substr($cep,0,8);
                    $cep = "'". $cep ."'";
                } else {
                    $cep = "null";
                }
            
                if (!empty($cidade)) {
                    $cidade = substr($cidade,0,30);
                    $cidade = pg_escape_string($cidade);
                    $cidade = "'". $cidade ."'";
                } else {
                    $cidade = "null";
                }
            
                if (!empty($estado)) {
                    $estado = substr($estado,0,2);
                    $estado = "'". $estado ."'";
                } else {
                    $estado = "null";
                }
            

                if (!empty($email)) {
                    $email = substr($email,0,50);
                    $email = pg_escape_string($email);
                    $email = "'". $email ."'";
                } else {
                    $email = "null";
                }
        
                if (!empty($telefone)) {
                    $telefone = substr($telefone,0,30);
                    $telefone = "'". $telefone ."'";
                } else {
                    $telefone = "null";
                }

                if (!empty($fax)) {
                    $fax = substr($fax,0,30);
                    $fax = "'". $fax ."'";
                } else {
                    $fax = "null";
                }

                if (!empty($contato)) {
                    $contato = substr($contato,0,30);
                    $contato = "'". $contato ."'";
                } else {
                    $contato = "null";
                }

                if (!empty($capital_interior)) {
                    $capital_interior = substr($capital_interior,0,10);
                    $capital_interior = "'". $capital_interior ."'";
                } else {
                    $capital_interior = "null";
                }

                if (!empty($cod_atividade)) {
                    $cod_atividade = substr($cod_atividade,0,2);
                    $cod_atividade = $cod_atividade;
                } else {
                    $cod_atividade = "null";
                }

                if (!empty($categoria)) {
                    $categoria = substr($categoria,0,1);
                    $categoria = $categoria;
                } else {
                    $categoria = "null";
                }

                $posto = '';

                $parametros_adicionais = json_encode(array("codigo_aividade" => $cod_atividade, "categoria" => $categoria));
                $parametros_adicionais = "'" . $parametros_adicionais . "'";

                ### VERIFICA EXISTÊNCIA DO POSTO
                $sql = "SELECT tbl_posto.posto
                        FROM   tbl_posto
                        WHERE  tbl_posto.cnpj = $cnpj";
                //die(nl2br($sql));
                $res = pg_query($con, $sql);

                ### INCLUI O POSTO QUE NÃO EXISTE
                if (pg_num_rows($res) == 0) {
                    $sqlInsertPosto = "INSERT INTO tbl_posto (
                                nome            ,
                                nome_fantasia   ,
                                cnpj            ,
                                endereco        ,
                                numero          ,
                                complemento     ,
                                bairro          ,
                                cep             ,
                                cidade          ,
                                estado          ,
                                email           ,
                                fone            ,
                                fax             ,
                                contato         ,
                                capital_interior,
                                parametros_adicionais
                            )VALUES (
                                $razao           ,
                                $fantasia        ,
                                $cnpj            ,
                                $endereco        ,
                                $numero          ,
                                $complemento     ,
                                $bairro          ,
                                $cep             ,
                                $cidade          ,
                                $estado          ,
                                $email           ,
                                $telefone        ,
                                $fax             ,
                                $contato         ,
                                $capital_interior,
                                $parametros_adicionais
                            )";
                    //die(nl2br($sqlInsertPosto));                            
                    $resInsertPosto = pg_query($con, $sqlInsertPosto);

                    if (pg_last_error($con)) {
                        $lest_error = pg_last_error($con);
                        if (strstr($lest_error, 'Email inv&aacute;') || strstr($lest_error, 'fn_valida_email_posto_fabrica()')){
                            $last_error = pg_last_error();
                            $error_msg_admin = preg_replace("/CONTEXT:.*/", "", $last_error);
                            $email_erro = str_replace('ERROR:  Email inv&aacute;lido ', '', $error_msg_admin);
                            fwrite($elogAdmin, "Erro Posto Telecontrol $posto - Email Inválido: $email_erro \n\n");
                            fwrite($elog, logErro($sqlInsertPosto, pg_last_error())); 
                        }else{
                            fwrite($elog, logErro($sqlInsertPosto, pg_last_error())); 
                        }
                            continue;
                    } else {
                        $query_posto_id = pg_query($con, "SELECT currval ('seq_posto') AS seq_posto");
                        $posto = pg_fetch_result($query_posto_id, 0, 'seq_posto');
                        fwrite($nlog, "Posto $posto - $razao CNPJ $cnpj inserido com sucesso \n");   
                    }
                }else{
                    $posto = pg_fetch_result($res,0,'posto');                
                }

                $sqlChecaPostoFabrica = "SELECT  tbl_posto_fabrica.fabrica, tbl_tipo_posto.descricao, tbl_tipo_posto.tipo_posto
		    FROM    tbl_posto_fabrica
		    INNER JOIN tbl_tipo_posto USING(tipo_posto,fabrica)
                    WHERE   tbl_posto_fabrica.posto   = $posto
                    AND     tbl_posto_fabrica.fabrica = $fabrica";

                    //die(nl2br($sqlChecaPostoFabrica));

                $resChecaPostoFabrica = pg_query($con, $sqlChecaPostoFabrica);
                if (pg_num_rows($resChecaPostoFabrica) == 0) {
                    
                    // Selecionando o tipo do posto                    
                    if($cod_atividade == 02 AND in_array($categoria, array(1,2,3,5,'A','B','D'))){
                        $tipo_posto = 159; //Autorizada
                        $parametros_adicionais = json_encode(array("pedido_venda" => "t", "pedido_consumo" => "f"));
                        $digita_os = 't';
                    } else if($cod_atividade == 03 AND in_array($categoria, array(1,2,3,5,'A','C'))){
                        $tipo_posto = 160; //Autorizada/Revenda
                        $parametros_adicionais = json_encode(array("pedido_venda" => "t", "pedido_consumo" => "f"));
                        $digita_os = 't';
                    } else if($cod_atividade == 03 AND in_array($categoria, array(6))){
                        $tipo_posto = 381; //Autorizada/Locadora
                        $parametros_adicionais = json_encode(array("pedido_venda" => "t", "pedido_consumo" => "t"));
                        $digita_os = 't';
                    } else if($cod_atividade == 01 AND in_array($categoria, array(6))){
                        $tipo_posto = 269; //Locadora                        
                        $parametros_adicionais = json_encode(array("pedido_venda" => "f", "pedido_consumo" => "t"));                    
                        $digita_os = 'f';
                    } else if($cod_atividade == 01 AND !in_array($categoria, array(6))){
                        $tipo_posto = 358; //Revenda
                        $parametros_adicionais = json_encode(array("pedido_venda" => "f", "pedido_consumo" => "f"));
                        $digita_os = 'f';
                    } else if($cod_atividade == 00 AND in_array($categoria, array(4,8))){
                        $tipo_posto = 748; //Industria
                        $parametros_adicionais = json_encode(array("pedido_venda" => "f", "pedido_consumo" => "t")); 
                        $digita_os = 't';                   
                    } else {
                        $tipo_posto = 159; //Padrão - Auorizada
                        $parametros_adicionais = json_encode(array("pedido_venda" => "t", "pedido_consumo" => "f"));
                        $digita_os = 't';
                    }

                    ### INSERE POSTO NA TABELA POSTO-FABRICA
                    $sqlInsertPostoFabrica = "INSERT INTO tbl_posto_fabrica (
                            posto            ,
                            fabrica          ,
                            senha            ,
                            tipo_posto       ,
                            login_provisorio ,
                            codigo_posto     ,
                            credenciamento   ,
                            nome_fantasia    ,
                            contato_nome     ,
                            contato_endereco ,
                            contato_numero   ,
                            contato_complemento,
                            contato_bairro   ,
                            contato_cep      ,
                            contato_cidade   ,
                            contato_estado   ,
                            contato_email    , 
                            digita_os        ,                           
                            parametros_adicionais
                        ) VALUES (
                            $posto           ,
                            $fabrica         ,
                            '*'              ,
                            $tipo_posto      ,
                            't'              ,
                            $codigo          ,
                            'CREDENCIADO'    ,
                            $fantasia        ,
                            $contato         ,
                            $endereco        ,
                            $numero          ,
                            $complemento     ,
                            $bairro          ,
                            $cep             ,
                            $cidade          ,
                            $estado          ,
                            $email           ,
                            '$digita_os'     ,
                            '$parametros_adicionais'
                        )";

                    //die(nl2br($sqlInsertPostoFabrica));

                    $resInsertPostoFabrica = pg_query($con, $sqlInsertPostoFabrica);

                    if (pg_last_error($con)) {
                        $lest_error = pg_last_error($con);
                        if (strstr($lest_error, 'Email inv&aacute;') || strstr($lest_error, 'fn_valida_email_posto_fabrica()')){
                            $last_error = pg_last_error();
                            $error_msg_admin = preg_replace("/CONTEXT:.*/", "", $last_error);
                            $email_erro = str_replace('ERROR:  Email inv&aacute;lido ', '', $error_msg_admin);
                            fwrite($elogAdmin, "Erro Posto Telecontrol $posto - Email Inválido: $email_erro \n\n");
                            fwrite($elog, logErro($sqlInsertPostoFabrica, pg_last_error()));
                        }else{
                                fwrite($elog, logErro($sqlInsertPostoFabrica, pg_last_error()));
                        } 
                    } /*else { 
                        $sql_log_credenciamento = "INSERT INTO tbl_credenciamento (
                                                    posto, 
                                                    fabrica, 
                                                    data, 
                                                    texto, 
                                                    status
                                                )  VALUES (
                                                    $posto,
                                                    $fabrica,
                                                    now(),
                                                    'Integração',
                                                    'CREDENCIADO'
                                                )";
                        $res_log_credenciamento = pg_query($con, $sql_log_credenciamento);

                        fwrite($nlog, "Posto $posto - $razao CNPJ $cnpj inserido no POSTO_FABRICA \n"); 
                    }*/

                    ### HD-6752028  -  SELECIONAR CONDIÇÕES PARA NOVOS POSTOS
                    $sqlCondicao = "SELECT 
                                        condicao, 
                                        visivel 
                                    FROM 
                                        tbl_condicao
                                    WHERE codigo_condicao IN('516','517','490','519','518','206','521','525','514','515','520','436','511','512')
				    AND fabrica = $fabrica
				";

                    $resCondicao = pg_query($con, $sqlCondicao);

                    ### HD-6752028 - INSERIR CONDICAO AO NOVO POSTO
                    for ($x=0; $x<pg_num_rows($resCondicao); $x++){
                        $x_condicao = pg_fetch_result($resCondicao, $x, 'condicao');
                        $x_visivel  = pg_fetch_result($resCondicao, $x, 'visivel'); 
                        $sqlInsereCondicao = "INSERT INTO tbl_posto_condicao (
                                                                posto, condicao, visivel
                                                            ) VALUES (
                                                                {$posto}, {$x_condicao}, '{$x_visivel}')";

                        //die(nl2br($sqlInsereCondicao));

                        $resInsereCondicao = pg_query($con, $sqlInsereCondicao);
                    } 
                } else {
                    $tipo_posto = 159; //Padrão - Auorizada
                    $parametros_adicionais = json_encode(array("pedido_venda" => "t", "pedido_consumo" => "f"));
                    $digita_os = 't';
                }

		    $desc_tipo_posto = pg_fetch_result($resChecaPostoFabrica,0,'descricao');

		    if(strpos($desc_tipo_posto,'TOP') !== false OR strtoupper($desc_tipo_posto) == "FILIAIS"){
			$tipo_posto = pg_fetch_result($resChecaPostoFabrica,0,'tipo_posto');
		    }
                    ### ATUALIZA POSTO-FABRICA
                    /*$sqlUpdatePostoFabrica = "UPDATE tbl_posto_fabrica SET
                        codigo_posto            = $codigo      ,                        
                        contato_endereco        = $endereco    ,
                        contato_numero          = $numero      ,
                        contato_complemento     = $complemento ,
                        contato_bairro          = $bairro      ,
                        contato_cidade          = $cidade      ,
                        contato_estado          = $estado      ,
                        contato_email           = $email       ,
                        tipo_posto              = $tipo_posto  ,
                        parametros_adicionais   = '$parametros_adicionais',
                        digita_os               = '$digita_os'
                    WHERE tbl_posto_fabrica.posto   = $posto
                    AND   tbl_posto_fabrica.fabrica = $fabrica;
                        ";*/

                    $sqlUpdatePostoFabrica = "UPDATE tbl_posto_fabrica SET
                        codigo_posto            = $codigo      ,                        
                        contato_endereco        = $endereco    ,
                        contato_numero          = $numero      ,
                        contato_complemento     = $complemento ,
                        contato_bairro          = $bairro      ,
                        contato_cidade          = $cidade      ,
                        contato_estado          = $estado      ,
                        contato_email           = $email                               
                    WHERE tbl_posto_fabrica.posto   = $posto
                    AND   tbl_posto_fabrica.fabrica = $fabrica;
                        ";                        

                    //die(nl2br($sqlUpdatePostoFabrica));

                    $resUpdatePostoFabrica = pg_query($con, $sqlUpdatePostoFabrica);

                    if (pg_last_error($con)) {
                        $lest_error = pg_last_error($con);
                        if (strstr($lest_error, 'Email inv&aacute;') || strstr($lest_error, 'fn_valida_email_posto_fabrica()')){
                            $last_error = pg_last_error();
                            $error_msg_admin = preg_replace("/CONTEXT:.*/", "", $last_error);
                            $email_erro = str_replace('ERROR:  Email inv&aacute;lido ', '', $error_msg_admin);
                            fwrite($elogAdmin, "Erro Posto Telecontrol $posto - Email Inválido: $email_erro \n\n");
                            fwrite($elog, logErro($sqlUpdatePostoFabrica, pg_last_error()));
                        }else{
                            fwrite($elog, logErro($sqlUpdatePostoFabrica, pg_last_error()));
                        } 
                    } else {
                        fwrite($nlog, "Posto $posto - $razao CNPJ $cnpj atualizado no POSTO_FABRICA \n"); 
                    }
                }
            }
        }

        fclose($nlog);
        fclose($elog);
        fclose($elogAdmin); 

        if (filesize($arq_log) > 0) {
                require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
                $assunto = ucfirst($fabrica_nome) . ' - Importacao de postos ' . date('d/m/Y');

                $mail = new PHPMailer();
                $mail->IsHTML(true);
                $mail->From = 'helpdesk@telecontrol.com.br';
                $mail->FromName = 'Telecontrol';

                if (ENV == 'producao') {
                    $mail->AddAddress('helpdesk@telecontrol.com.br');
                } else {
                    $mail->AddAddress(DEV_EMAIL);
                }

                $mail->Subject = $assunto;
                $mail->Body = "Segue anexo arquivo de postos importado na rotina...<br/><br/>";
                $mail->AddAttachment($arq_log);


                if (!$mail->Send()) {
                    echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
                } else {
                    unlink($log_dir . '/importa-posto-' . $now . '.log');
                }
            
        }

        if (filesize($err_log) > 0) {
                require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
                $assunto = ucfirst($fabrica_nome) . ' - Erros na importacao de postos ' . date('d/m/Y');

                $mail = new PHPMailer();
                $mail->IsHTML(true);
                $mail->From = 'helpdesk@telecontrol.com.br';
                $mail->FromName = 'Telecontrol';

                if (ENV == 'producao') {
                    $mail->AddAddress('helpdesk@telecontrol.com.br');
                } else {
                    $mail->AddAddress(DEV_EMAIL);
                }


                $mail->Subject = $assunto;
                $mail->Body = "Segue anexo log de erro na importação de postos...<br/><br/>";
                $mail->AddAttachment($err_log);

                if (!$mail->Send()) {
                    echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
                } else {
                    unlink($log_dir . '/importa-posto-err-' . $now . '.log');
                }

        }

        if (filesize($err_log_admin) > 0) {
                require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
                $assunto = 'Telecontrol - Erro na Importacao de postos ' . date('d/m/Y');

                $mail = new PHPMailer();
                $mail->IsHTML(true);
                $mail->From = 'helpdesk@telecontrol.com.br';
                $mail->FromName = 'Telecontrol';

                if (ENV == 'producao') {
                    $mail->AddAddress('atsbc02@makita.com.br');
                    $mail->AddAddress('atsbc03@makita.com.br');
                    $mail->AddAddress('renan@makita.com.br');
                } else {
                    $mail->AddAddress(DEV_EMAIL);
                }
                
                $mail->Subject = $assunto;
                $mail->Body = "Segue anexo log de erro na importação de postos...<br/><br/>";
                $mail->AddAttachment($err_log_admin);


                if (!$mail->Send()) {
                    echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
                } else {
                    unlink($log_dir . '/importa-posto-' . $now . '.log');
                }
            
        }
        

        $data_arq_process = date('Ymd');
        //system("mv $arquivo teste/posto-$data_arq_process.txt");//teste local
        system("mv $arquivo /tmp/$fabrica_nome/posto-$data_arq_process.txt");

    }
//}
 catch (Exception $e) {
    echo $e->getMessage();
}
