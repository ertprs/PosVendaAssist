<?php
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('America/Sao_Paulo');

define('ENV', 'producao');

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../../funcoes.php';
    require_once dirname(__FILE__) . '/../../class/email/PHPMailer/class.phpmailer.php';

    if(ENV == "producao"){
        $arquivos = "/tmp/gelopar";
        $origem_arq   = "/www/cgi-bin/gelopar/entrada";  
    }else{
        $arquivos = "./entrada/tmp/gelopar";
        $origem_arq   = "./entrada";
    }

    $now      = date("d-m-Y_H_i_s");
    $fabrica  = "85" ;

	if (!file_exists("$origem_arq/telecontrol-produtos.txt")) {
		die("Não existia arquivo: $origem_arq/telecontrol-produtos.txt\n");
	}

	if (filesize("$origem_arq/telecontrol-produtos.txt") == 0) {
        $assunto = 'Arquivo de integração de produtos vazio';

        $mail = new PHPMailer();
        $mail->IsHTML(true);
        $mail->From = 'helpdesk@telecontrol.com.br';
        $mail->FromName = 'Telecontrol';
		$mail->AddAddress('sidney.sanches@gelopar.com.br');

        $mail->Subject = $assunto;
        $mail->Body = 'O arquivo telecontrol-produtos.txt estava vazio.';

        if (!$mail->Send()) {
			echo 'Erro ao enviar email: ' . $mail->ErrorInfo . "\n";
        }

		exit;
	}
    
    $arq_log = $arquivos . '/log_imp_produto-' . $now . '.log';
    $err_log = $arquivos . '/erro_imp_produto-' . $now . '.txt';

    $sql = "DROP TABLE gelopar_produto;";
    $result = pg_query($con, $sql);


    $sql = "CREATE TABLE gelopar_produto (
                referencia         varchar(100),
                descricao          varchar(100),
                txt_origem         varchar(3),
                txt_gelopar        varchar(50),
                txt_familia        varchar(50),
                txt_desc_familia   varchar(50),
                txt_preco          varchar(50),
                txt_ipi            varchar(50),
                txt_voltagem       varchar(20)
            )";
    $result = pg_query($con, $sql);

    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    #-------------- Importa Arquivo de Produtos -------#
    if (file_exists("$origem_arq/telecontrol-produtos.txt")) {
        $dados = file("$origem_arq/telecontrol-produtos.txt");

        foreach($dados as $linha){

            list (
                $referencia,
                $descricao,
                $origem,
                $gelopar,
                $codigo_familia,
                $familia,
                $preco,
                $ipi,
                $voltagem                
            ) = explode ("\t",$linha);

            $referencia = trim($referencia);
            $descricao = trim($descricao);
            $origem = trim($origem);
            $gelopar = trim($gelopar);
            $codigo_familia = trim($codigo_familia);
            $familia = trim($familia);
            $preco = trim($preco);
            $ipi = trim($ipi);
            $voltagem = trim($voltagem);

            $sql_verifica_linha = "
                SELECT familia 
                    FROM tbl_familia 
                    WHERE codigo_familia = '$codigo_familia' 
                        AND fabrica = $fabrica";
            $res_verifica_linha = pg_query($con, $sql_verifica_linha);

            if(pg_num_rows($res_verifica_linha)==0){
                $log_erro .= "Família $codigo_familia não encontrada.\n\r<br />";
                continue;
            }

            if(strlen(trim($descricao))>80){
                $log_erro .= "A descrição $descricao foi alterada para ".substr($descricao, 0, 80). "pois deve ter até 80 caracteres. \n\r<br />";
                $descricao = substr($descricao, 0, 80);
            }

            if(strlen(trim($referencia))>30){
                $log_erro .= "A referencia $referencia não foi importada por estar acima da quantidade de caracteres permitido. \n\r<br />";
                continue;
            }

            if(strlen(trim($voltagem))>20){
                $log_erro .= "A voltagem $voltagem foi alterada para ".substr($voltagem, 0, 20). "pois deve ter até 20 caracteres. \n\r<br />";
                $voltagem = substr($voltagem, 0, 20);
            }

            if(strlen(trim($origem))>3){
                $log_erro .= "A origem $origem foi alterada para ".substr($origem, 0, 3). "pois deve ter até 3 caracteres. \n\r<br />";
                $origem = substr($origem, 0, 3);
            }

            $sql = "INSERT INTO gelopar_produto (
                referencia,
                descricao,
                txt_origem,
                txt_gelopar,
                txt_familia,
                txt_desc_familia,
                txt_preco,
                txt_ipi,
                txt_voltagem) 
                VALUES (
                '$referencia',
                '$descricao',
                '$origem',
                '$gelopar',
                '$codigo_familia',
                '$familia',
                '$preco',
                '$ipi',
                '$voltagem') ";

            $res = pg_query($con, $sql);

            if(strlen(trim(pg_last_error()))>0) {
                $msg_erro .= "\n\r<br />=====================================\n\r<br />";
                $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
                $msg_erro .= "\n\r<br />=====================================\n\r<br />";
            }
        }
    }    

    $sql = "ALTER TABLE gelopar_produto  ADD COLUMN produto int4;";
    $result = pg_query($con, $sql);
    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    $sql = "ALTER TABLE gelopar_produto  ADD COLUMN linha int4;";
    $result = pg_query($con, $sql);
    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    $sql = "ALTER TABLE gelopar_produto  ADD COLUMN familia int4;";
    $result = pg_query($con, $sql);
    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    $sql = "UPDATE gelopar_produto 
            SET linha = 581;
            ";
    $result = pg_query($con, $sql);

    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    $sql = "UPDATE gelopar_produto
            SET familia = tbl_familia.familia
            FROM tbl_familia
            WHERE UPPER(txt_familia) = UPPER(tbl_familia.codigo_familia) 
            AND tbl_familia.fabrica = $fabrica
            ";
    $result = pg_query($con, $sql);
    if(strlen(trim(pg_last_error(). "\n\n"))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    $sql = "UPDATE gelopar_produto 
            SET descricao = substr(descricao,0,80)
            ";
    $result = pg_query($con, $sql);
    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    $sql = "UPDATE gelopar_produto 
            SET txt_voltagem = substr(txt_voltagem,0,20)
            ";
    $result = pg_query($con, $sql);
    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    $sql = "UPDATE gelopar_produto 
            SET txt_origem = substr(txt_origem,0,3)
            ";
    $result = pg_query($con, $sql);
    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    ### GERA ARQUIVO COM ERROS
    $sql = "SELECT    *
        FROM      gelopar_produto
        WHERE     gelopar_produto.linha IS NULL 
            OR gelopar_produto.familia IS NULL ";
    $res = pg_query($con, $sql);
    for($z=0; $z<pg_num_rows($res); $z++){
        $gelopar_produto_referencia = pg_fetch_result($res, $z, 'referencia');

        $log_erro .= "O produto $gelopar_produto_referencia está sem linha ou família, não foi importado.\n\r<br /> ";
    }

    $sql = "DELETE FROM gelopar_produto
            WHERE  gelopar_produto.linha IS NULL 
            OR     gelopar_produto.familia IS NULL 
            ";
    $result = pg_query($con, $sql);
    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    $sql = "SELECT  
                    referencia, 
                    descricao,
                    txt_voltagem
            FROM gelopar_produto
            WHERE   upper(trim(gelopar_produto.referencia)) NOT IN (
                SELECT upper(trim(tbl_produto.referencia))
                FROM   tbl_produto
                JOIN   tbl_linha ON tbl_linha.linha = tbl_produto.linha
                WHERE  tbl_linha.fabrica = $fabrica
            );"; 
    $result = pg_query($con, $sql);
    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\r<br />Erro sql -". pg_last_error()."\n\r<br />$sql\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    if (pg_num_rows($result) > 0) {

        $nlog = fopen($arq_log, "w");
        $log = "REFERENCIA\tDESCRICAO\n ";

        for($i=0; $i<pg_num_rows($result); $i++){
            $referencia = pg_fetch_result($result, $i, 'referencia');
            $descricao  = pg_fetch_result($result, $i, 'descricao');
            $voltagem   = pg_fetch_result($result, $i, 'txt_voltagem');
            $log .= "$referencia\t$descricao\t$voltagem\n";            
        }

        fwrite($nlog, $log . "\n");
        fclose($nlog);
    }

    if(strlen(trim($log))>0){
        
        $assunto = 'GELOPAR - Registros inseridos na integração de produtos '.date('d/m/Y');

        $mail = new PHPMailer();
        $mail->IsHTML(true);
        $mail->From = 'helpdesk@telecontrol.com.br';
        $mail->FromName = 'Telecontrol';

        if (ENV == 'producao') {
            $mail->AddAddress('wally.jarek@gelopar.com.br');
            $mail->AddAddress('sidney.sanches@gelopar.com.br');
            $mail->AddAddress('fernanda.carlota@gelopar.com.br');
            $mail->AddAddress('felipe.barbosa@gelopar.com.br');
            $mail->AddAddress('sergio.jash@gelopar.com.br');
            $mail->AddAddress('helpdesk@telecontrol.com.br');
        } else {
            $mail->AddAddress('thiago.tobias@telecontrol.com.br');
        }

        $mail->Subject = $assunto;

        //$log = str_replace("\n", "<br>", $log);

        $mail->Body = "Novos produtos cadastrados na linha 'INDEFINIDO'.<BR> Será necessário alterar a linha e marcar o campo ATIVO caso faça parte de produtos de garantia.<BR><BR>Segue os produtos cadastrados:<BR>\n\r<br /> $log";        

        if (!$mail->Send()) {
            $msg_erro .= "\n\r<br />Erro ao enviar email: ".$mail->ErrorInfo."\n\r<br />";
        }
    }

    if(strlen(trim($log_erro))>0){
        
        $assunto = 'GELOPAR -  LOG INTEGRAÇÃO TELECONTROL '.date('d/m/Y');

        $mail = new PHPMailer();
        $mail->IsHTML(true);
        $mail->From = 'helpdesk@telecontrol.com.br';
        $mail->FromName = 'Telecontrol';

        if (ENV == 'producao') {
            $mail->AddAddress('wally.jarek@gelopar.com.br');
            $mail->AddAddress('sidney.sanches@gelopar.com.br');
            $mail->AddAddress('helpdesk@telecontrol.com.br');
        } else {
            $mail->AddAddress('thiago.tobias@telecontrol.com.br');
        }

        $mail->Subject = $assunto;

        //$log_erro = str_replace("\n", "<br>", $log_erro);

        $mail->Body = "Prezado Cliente, <br><br> Na integração :<br> $log_erro <br><br> Qualquer dúvida entrar em contato com o Suporte Telecontrol via CHAT ou responde esse e-mail. <br><br>
            Telecontrol Sistemas";        

        if (!$mail->Send()) {
            $msg_erro .= "\n\r<br />Erro ao enviar email: " . $mail->ErrorInfo."\n\r<br />";
        }
    }

    $res = pg_query($con,"BEGIN");

    # INSERE PRODUTOS NOVOS
    $sql_verifica_produto = "UPDATE tbl_produto SET  
                descricao = gelopar_produto.descricao,
                linha = gelopar_produto.linha,
                origem = gelopar_produto.txt_origem,                
                ipi = replace (trim (gelopar_produto.txt_ipi),',','.')::numeric,
                ativo = true,
                voltagem = gelopar_produto.txt_voltagem
                FROM gelopar_produto
                WHERE gelopar_produto.referencia = tbl_produto.referencia and tbl_produto.fabrica_i = $fabrica";
    $res_verifica_produto = pg_query($con, $sql_verifica_produto);

    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\rErro sql Update - ". pg_last_error() . "\n\r<br />$sql_verifica_produto\n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }
    if(!empty($msg_erro)){
        $res = pg_query($con,"ROLLBACK");
    } else {
        $res = pg_query($con,"COMMIT");
    }

    $sql = "INSERT INTO tbl_produto 
            (
                    referencia        ,
                    descricao         ,
                    linha             ,
                    familia           ,
                    origem            ,
                    garantia          ,
                    mao_de_obra       ,
                    mao_de_obra_admin ,
                    ipi               ,
                    ativo             ,
                    voltagem          
            )
            SELECT  
                    DISTINCT 
                    gelopar_produto.referencia   ,
                    gelopar_produto.descricao    ,
                    gelopar_produto.linha        ,
                    gelopar_produto.familia      ,
                    gelopar_produto.txt_origem   ,
                    12                           ,
                    0                            ,
                    0                            ,
                    replace (trim (gelopar_produto.txt_ipi),',','.')::numeric,
                    false,
                    gelopar_produto.txt_voltagem
            FROM    gelopar_produto
            WHERE   upper(trim(replace(fn_retira_especiais(gelopar_produto.referencia),' ',''))) NOT IN (
                SELECT upper(trim(replace(fn_retira_especiais(tbl_produto.referencia),' ','')))
                FROM   tbl_produto
                JOIN   tbl_linha ON tbl_linha.linha = tbl_produto.linha
                WHERE  tbl_linha.fabrica = $fabrica
            );";
    $result = pg_query($con, $sql);

    if(strlen(trim(pg_last_error()))>0) {
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
        $msg_erro .= "\n\rErro sql Insert -". pg_last_error() . "\n\r<br /> $sql \n\r<br />";
        $msg_erro .= "\n\r<br />=====================================\n\r<br />";
    }

    if(strlen(trim($msg_erro))>0){

        $assunto = 'GELOPAR - ERRO integração de produtos '.date('d/m/Y') ;

        $mail = new PHPMailer();
        $mail->IsHTML(true);
        $mail->From = 'helpdesk@telecontrol.com.br';
        $mail->FromName = 'Telecontrol';

        if (ENV == 'producao') {
            $mail->AddAddress('helpdesk@telecontrol.com.br');
        } else {
            $mail->AddAddress('thiago.tobias@telecontrol.com.br');
        }

        $mail->Subject = $assunto;

        //$msg_errox = str_replace("\n", "<br>", $msg_erro);

        $mail->Body = "Segue erro na integração de produtos:<BR> $msg_erro "; 

        if (!$mail->Send()) {
            $msg_erro .= "\n\r<br />Erro ao enviar email: ".$mail->ErrorInfo."\n\r<br />";
        }

        //$msg_erro = str_replace(array("<br />","\r"), "", $msg_erro);;
        $nerr = fopen($err_log, "w");
        fwrite($nerr, $msg_erro . "\n");
        fclose($nerr);
    }

    system ("mv $origem_arq/telecontrol-produtos.txt  /tmp/gelopar/telecontrol-produtos-$now.txt");
