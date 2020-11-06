<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
include_once 'class/email/mailer/class.phpmailer.php';
$mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

if(in_array($login_fabrica, array(1,35))){
    $contrato_posto = true;
}

if($contrato_posto){
    include_once "class/tdocs.class.php";
}

if($contrato_posto && isset($_GET["excluir_contrato"])){

    $id = $_GET["id"];

    if(strlen($id) > 0){

        $tDocs = new TDocs($con, $login_fabrica);

        $tDocs->setContext("posto", "contrato")->removeDocumentById($id);

    }

    header("location: posto_cadastro_new.php");
    exit;

}

###########POST NOVO###########
$bloqueia_atualizar_endereco = in_array($login_fabrica, array( 30,52,85 )) ? true : false ;    // HD 2189175

$bloqueia_input_campos = in_array($login_fabrica, array( 1, 3, 11, 50, 172)) ? true : false ;

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];
if ($btn_acao == "gravar") {
    $email              = trim($_POST ['email']);
    $fone               = trim($_POST ['fone']);
    $fone2              = trim($_POST ['fone2']);
    $fax                = trim($_POST ['fax']);
    $nome_fantasia      = trim($_POST ['nome_fantasia']);
    $capital_interior   = trim($_POST ['capital_interior']);
    $endereco           = trim($_POST ['endereco']);
    $numero             = trim($_POST ['numero']);
    $complemento        = trim($_POST ['complemento']);
    $bairro             = trim($_POST ['bairro']);
    $cep                = trim($_POST ['cep']);
    $cidade             = trim($_POST ['cidade']);
    $cidade             = trim($_POST ['estado']);
    $contato            = trim($_POST ['contato']);

    if($login_fabrica == 20){
        $email_alternativo  = trim($_POST['email_alternativo']);
        $data_nomeacao      = trim($_POST['data_nomeacao']);
    }

    if ($login_fabrica == 30){
        $fone3          = trim($_POST ['fone3']);
    }
    if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90 ){
        $banco              = trim($_POST ['banco']);
        $agencia            = trim($_POST ['agencia']);
        $conta              = trim($_POST ['conta']);
        $nomebanco          = trim($_POST ['nomebanco']);
        $favorecido_conta   = trim($_POST ['favorecido_conta']);
        $conta_operacao     = trim($_POST ['conta_operacao']);//HD 8190 5/12/2007 Gustavo
        $cpf_conta          = trim($_POST ['cpf_conta']);
        $tipo_conta         = trim($_POST ['tipo_conta']);
        $obs_conta          = trim($_POST ['obs_conta']);
    }

    //hd 11308 14/1/2008
    if($login_fabrica == 15){
        $im = trim($_POST ['im']);
        if (strlen($im) > 0){
            $xim = "'".$im."'";
        }else{
            $xim = 'null';
        }
    }

    if(strlen($email) > 0){
        $xemail = "'".$email."'";
    }else{
        $xemail = 'null';
    }

    if(strlen($fone) > 0){
        $xfone = "'".$fone."'";
    }else{
        $xfone = 'null';
    }

    if(strlen($fone2) > 0){
        $xfone2 = "'".$fone2."'";
    }else{
        $xfone2 = 'null';
    }

    if(strlen($fone3) > 0){
        $xfone3 = "'".$fone3."'";
    }else{
        $xfone3 = 'null';
    }

    if(strlen($fax) > 0){
        $xfax = "'".$fax."'";
    }else{
        $xfax = 'null';
    }

    if(strlen($nome_fantasia) > 0){
        $xnome_fantasia = "'".$nome_fantasia."'";
    }else{
        $xnome_fantasia = 'null';
    }

    if(strlen($capital_interior) > 0){
        $xcapital_interior = "'".$capital_interior."'";
    }else{
        $xcapital_interior = 'null';
    }

    if(strlen($endereco) > 0){
        $xendereco = "'".$endereco."'";
    }else{
        $xendereco = 'null';
    }

    if(strlen($numero) > 0){
        $xnumero = "'".$numero."'";
    }else{
        $xnumero = 'null';
    }

    if(strlen($complemento) > 0){
        $xcomplemento = "'".$complemento."'";
    }else{
        $xcomplemento = 'null';
    }

    if(strlen($bairro) > 0){
        $xbairro = "'".$bairro."'";
    }else{
        $xbairro = 'null';
    }

    if(strlen($cep) > 0){
        $xcep = str_replace (".","",$cep);
        $xcep = str_replace ("-","",$xcep);
        $xcep = str_replace (" ","",$xcep);
        $xcep = "'".$xcep."'";
    }else{
        $xcep = 'null';
    }

    if (strlen($cidade) > 0){
        $xcidade = "'".$cidade."'";
    }else{
        $xcidade = 'null';
    }

    if(strlen($estado) > 0){
        $xestado = "'".$estado."'";
    }else{
        $xestado = 'null';
    }

    if(strlen($contato) > 0){
        $xcontato = "'".$contato."'";
    }else{
        $xcontato = 'null';
    }

    //email Ronaldo 12/01/2010
    if($login_fabrica == 81 OR $login_fabrica == 40 OR $login_fabrica == 90){
        if(strlen($banco) > 0) {
            $xbanco = "'".$banco."'";
            $sqlB = "SELECT nome FROM tbl_banco WHERE codigo = '$banco'";
            $resB = @pg_exec($con,$sqlB);
            if (@pg_numrows($resB) == 1) {
                $xnomebanco = "'" . trim(@pg_result($resB,0,0)) . "'";
            }else{
                $xnomebanco = "null";
            }
        }else{
            $xbanco     = "null";
            $xnomebanco = "null";
        }

        $xagencia          = (strlen($agencia) > 0) ? "'".$agencia."'" : 'null';
        $xconta            = (strlen($conta) > 0) ? "'".$conta."'" : 'null';
        $xfavorecido_conta = (strlen($favorecido_conta) > 0) ? "'".$favorecido_conta."'" : 'null';
        $xconta_operacao   = (strlen($conta_operacao) > 0) ? "'".$conta_operacao."'" : 'null';
        $xtipo_conta       = (strlen($tipo_conta) > 0) ? "'".$tipo_conta."'" : 'null';

        //HD 1119644 - PEDIU PARA TIRAR VALIDAÇÃO PARA BESTWAY
        if($login_fabrica == 40 or $login_fabrica == 90){
            if($tipo_conta!='Conta jurídica'){
                $msg_erro["msg"][] = "A conta tem que ser somente JURÍDICA!";
            }
        }
        $cpf_conta = str_replace (".","",$cpf_conta);
        $cpf_conta = str_replace ("-","",$cpf_conta);
        $cpf_conta = str_replace ("/","",$cpf_conta);
        $cpf_conta = str_replace (" ","",$cpf_conta);

        if (strlen($cpf_conta) <> 14 AND $tipo_conta == 'Conta jurídica'){
            $msg_erro["msg"][] = "CNPJ da Conta jurídica inválida";
        }

        $xcpf_conta = (strlen($cpf_conta) > 0) ? "'".$cpf_conta."'" : 'null';
        $xobs_conta = (strlen($obs_conta) > 0) ? "'".$obs_conta."'" : 'null';

        if(strlen($cpf_conta) > 0){
            $valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$cpf_conta));

            if(empty($valida_cpf_cnpj)){
                $sqlvalida = "SELECT fn_valida_cnpj_cpf('$cpf_conta')";
                $resvalida = @pg_exec($con,$sqlvalida);
                if(strlen(pg_errormessage($con)) > 0){
                    $msg_erro["msg"][] = "CNPJ Inválido!";
                }
            }else{
                $msg_erro["msg"][] = $valida_cpf_cnpj;
            }
        }
    }
    //email do Ronaldo

    // Atualização de dados

    if(!count($msg_erro["msg"])) {
        $res = pg_query ($con,"BEGIN TRANSACTION");
        if(strlen($login_posto) > 0 and !in_array( $login_fabrica, array( 1, 3, 11, 30, 50, 172))) {
            if($login_fabrica == 20){

                list($di, $mi, $yi) = explode("/", $data_nomeacao);
                if(!checkdate($mi,$di,$yi)){
                    $msg_erro["msg"][] = traduz("data.nomeação.inválida",$con,$cook_idioma);
                } else {                    
                    $sql_dados_alternativos = "SELECT parametros_adicionais 
                                                FROM tbl_posto_fabrica 
                                                WHERE fabrica = $login_fabrica
                                                AND posto = $login_posto";

                    $res_dados_alternativos = pg_query($con, $sql_dados_alternativos);


                    $dados_alternativos = json_decode(pg_fetch_result($res_dados_alternativos, 0, parametros_adicionais), true);
                    
                    $dados_alternativos['email_alternativo']    = $email_alternativo;
                    $dados_alternativos['data_nomeacao']        = $data_nomeacao;

                    $dados_alternativos = json_encode($dados_alternativos);
                    $update_alternativo = ", parametros_adicionais = '{$dados_alternativos}' ";
                }
            }            

            $sql = "";
            if ($bloqueia_atualizar_endereco == false) {
                $sql = "UPDATE tbl_posto_fabrica SET
                            contato_nome = $xcontato ,
                            contato_endereco = $xendereco ,
                            contato_numero = $xnumero ,
                            contato_complemento = $xcomplemento ,
                            contato_bairro = $xbairro ,
                            contato_cep = $xcep ,
                            nome_fantasia = $xnome_fantasia ,
                            contato_email = $xemail
                            $update_alternativo                        
                        WHERE posto = $login_posto AND fabrica = $login_fabrica; ";
            }

            $sql .= " UPDATE tbl_posto SET
                    contato = $xcontato 
                    ";

            if($login_fabrica==15){
                $sql .= ", im = $xim ";
            }
            $sql .= " WHERE tbl_posto.posto = $login_posto";
            //die(nl2br($sql));
            $res = pg_query($con,$sql);

            if (pg_errormessage ($con) > 0) {
                $msg_erro["msg"][] = pg_errormessage ($con);
            }

            if($login_fabrica == 20 AND pg_errormessage ($con) == 0){
                include_once 'class/communicator.class.php';

                if($_serverEnvironment == "development"){
                    $email = 'suporte@telecontrol.com.br';                    
                } else {
                    $email = 'caroline.lopes@br.bosch.com';    
                }
                
                $assunto = traduz("Aviso de alteração da Data Nomeação - Telecontrol");                
                $mensagem = "
                    Prezados, <br /> 
                    Informamos que o posto <strong>{$codigo} - {$nome}</strong> ({$cnpj}) realizou alteração na Data Nomeação através do sistema da Telecontrol. <br /><br />
                    Data Nomeação: {$data_nomeacao}";

                $mailTc = new TcComm($externalId);
                $res = $mailTc->sendMail(
                    $email,
                    $assunto,
                    $mensagem,
                    $externalEmail
                );
            }
        }
        #---------------------- Alteração de Dados para Britânia  ---------------------
        #29/11/2008 MLG - HD 53598  O Posto da Bosch (20) pode também alterar seu cadastro
        #           Alteração:
        #               if (strlen ($login_posto) > 0 and 8 <---- $login_fabrica==3 ---->8 ) {

        if(strlen ($login_posto) > 0 and in_array( $login_fabrica, array( 3, 15, 20, 24, 40, 81, 90 )) and $bloqueia_atualizar_endereco == false) {

            if(!count($msg_erro["msg"])) {
                $sql = "SELECT
                            contato_endereco   ,
                            contato_numero     ,
                            contato_complemento,
                            contato_bairro     ,
                            contato_cep        ,
                            contato_email      ,
                            capital_interior   ,
                            tbl_posto_fabrica.contato_nome as contato,
                            tbl_posto.capital_interior   ,
                            tbl_posto_fabrica.contato_fone_comercial as fone,
                            tbl_posto_fabrica.contato_fone_residencial as fone2, ";
             
                $sql .= "
                        tbl_posto_fabrica.contato_fax            as fax,
                        tbl_posto_fabrica.nome_fantasia
                        FROM tbl_posto_fabrica
                        JOIN tbl_posto USING(posto)
                        WHERE tbl_posto_fabrica.posto = $login_posto
                        AND  tbl_posto_fabrica.fabrica = $login_fabrica";
                $res = pg_exec($con,$sql);

                if (pg_num_rows ($res) > 0) {
                    $bendereco            = trim(pg_result($res,0,contato_endereco));
                    $bnumero              = trim(pg_result($res,0,contato_numero));
                    $bcomplemento         = trim(pg_result($res,0,contato_complemento));
                    $bcapital_interior    = trim(pg_result($res,0,capital_interior));
                    $bbairro              = trim(pg_result($res,0,contato_bairro));
                    $bcep                 = trim(pg_result($res,0,contato_cep));
                    $bemail               = trim(pg_result($res,0,contato_email));
                    $bcontato             = trim(pg_result($res,0,contato));
                    $bfone                = trim(pg_result($res,0,fone));
                    $bfone2               = trim(pg_result($res,0,fone2));

                    $bfax                 = trim(pg_result($res,0,fax));
                    $bnome_fantasia       = trim(pg_result($res,0,nome_fantasia));
                }

                $sql = "UPDATE tbl_posto_fabrica SET
                            contato_endereco        = $xendereco               ,
                            contato_numero          = $xnumero                 ,
                            contato_complemento     = $xcomplemento            ,
                            contato_bairro          = $xbairro                 ,
                            contato_cep             = $xcep                    ,
                            contato_fone_comercial  = $xfone                   ,
                            contato_fone_residencial= $xfone2                  ,";                

                $sql .= "
                            contato_fax             = $xfax                    ,
                            nome_fantasia           = $xnome_fantasia          ,
                            atualizacao             = current_timestamp        ";

                if ($bloqueia_input_campos) {

                    $sql .= ", contato_email           = $xemail";

                }

                if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90){
                    $sql .= ",
                            banco                   = $xbanco                  ,
                            agencia                 = $xagencia                ,
                            conta                   = $xconta                  ,
                            nomebanco               = $xnomebanco              ,
                            favorecido_conta        = $xfavorecido_conta       ,
                            conta_operacao          = $xconta_operacao         ,
                            cpf_conta               = $xcpf_conta              ,
                            tipo_conta              = $xtipo_conta              ,
                            obs_conta               = $xobs_conta              ";
                }
                $sql .= " WHERE tbl_posto_fabrica.posto = $login_posto
                        AND   tbl_posto_fabrica.fabrica = $login_fabrica ";

                $res = pg_query($con,$sql);
                if (pg_errormessage ($con) > 0) $msg_erro["msg"][] = pg_errormessage ($con);

                if(!count($msg_erro["msg"])) {
                    $sql = "UPDATE tbl_posto SET
                            capital_interior = upper ($xcapital_interior)      ,
                            contato         = $xcontato                        ,
                            fone            = $xfone                           ,
                            fax             = $xfax                            ,
                            nome_fantasia   = $xnome_fantasia
                            WHERE tbl_posto.posto = $login_posto";
                    $res = pg_query($con,$sql);

                    $sql = "UPDATE tbl_posto_fabrica SET contato_nome = $xcontato WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
                    $res = pg_query($con,$sql);

                    if (pg_errormessage ($con) > 0) $msg_erro["msg"][] = pg_errormessage ($con);
                }
            }
        }else{

            if (!$bloqueia_input_campos) {
                $campoContatoEmail = "contato_email           = $xemail, ";
            }

            $sql = "UPDATE tbl_posto_fabrica SET 
                    contato_fax             = $xfax                    ,
                    nome_fantasia           = $xnome_fantasia          ,
                    atualizacao             = current_timestamp        ,
                    {$campoContatoEmail}
                    contato_nome            = $xcontato, 
                    contato_fone_comercial  = $xfone                   ,
                    contato_fone_residencial= $xfone2                  ";                  
            if ($login_fabrica == 30) {
                $sql .= ", contato_cel = $xfone3 ";
            }        
            
            $sql .= "WHERE tbl_posto_fabrica.posto = $login_posto
                    AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
            $res = pg_query($con,$sql);

            if (pg_errormessage ($con) > 0) $msg_erro["msg"][] = pg_errormessage ($con);

            if(!count($msg_erro["msg"])) {
                $sql = "UPDATE tbl_posto SET ";
                       
                if($login_fabrica != 30){
                    $sql .= " capital_interior = upper ($xcapital_interior)      , ";
                }

                $sql .= "contato         = $xcontato                        ,
                        fone            = $xfone                           ,
                        fax             = $xfax                            ,
                        nome_fantasia   = $xnome_fantasia
                        WHERE tbl_posto.posto = $login_posto";
                $res = pg_query($con,$sql);
      
                if (pg_errormessage ($con) > 0) $msg_erro["msg"][] = pg_errormessage ($con);
            }
        }
    }

    // grava posto_fabrica
    if(!count($msg_erro["msg"])) {

        if(strlen($_POST["senha"]) > 0){

            $senha = trim ($_POST['senha']);
            $senha2 = trim ($_POST['senha2']);

            if ($senha <> $senha2) {
                $msg_erro["msg"][] = traduz("as.senhas.nao.sao.iguais.redigite",$con,$cook_idioma);
            }

            //Wellington 31/08/2006 - MINIMO 6 CARACTERES SENDO UM MINIMO DE 2 LETRAS E MINIMO DE 2 NUMEROS
            if(strlen($senha) > 0) {
                if (strlen(trim($senha)) >= 6) {
                    //- verifica qtd de letras e numeros da senha digitada -//
                    $count_letras  = 0;
                    $count_numeros = 0;
                    $letras  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $numeros = '0123456789';

                    for ($i = 0; $i <= strlen($senha); $i++) {
                        if ( strpos($letras, substr($senha, $i, 1)) !== false)
                            $count_letras++;

                        if ( strpos ($numeros, substr($senha, $i, 1)) !== false)
                            $count_numeros++;
                    }

                    if ($count_letras < 2) {
                        $msg_erro["msg"][] = traduz("senha.invalida.a.senha.deve.ter.pelo.menos.2.letras",$con,$cook_idioma);
                    }
                    if ($count_numeros < 2) {
                        $msg_erro["msg"][] = traduz("senha.invalida.a.senha.deve.ter.pelo.menos.2.numeros",$con,$cook_idioma);
                    }
                }else{
                    $msg_erro["msg"][] = traduz("a.senha.deve.conter.um.minimo.de.6.caracteres",$con,$cook_idioma);
                }
                $xsenha = "'".$senha."'";
            }
            // else if($login_fabrica<>3 and $login_fabrica<>81 and $login_fabrica <> 90 and $login_fabrica <> 24 and $login_fabrica <> 15){
            //     $msg_erro["msg"][] = traduz("digite.uma.senha",$con,$cook_idioma);
            // }

            // verifica se o posto já tem codigo e senha cadastrados para alguma outra fábrica
            $sql = "SELECT tbl_posto_fabrica.fabrica
                FROM   tbl_posto_fabrica
                WHERE  tbl_posto_fabrica.posto   = $login_posto
                AND    tbl_posto_fabrica.senha   = '$senha'
                AND    tbl_posto_fabrica.fabrica <> $login_fabrica";
            $res = pg_query($con,$sql);
    #echo $sql;exit;
            if (pg_num_rows ($res) > 0) {
                $msg_erro["msg"][] = "Senha já utilizada para outra fabrica. Favor cadastrar uma nova senha.";
               # $msg_erro["msg"][] = traduz("senha.invalida.por.favor.digite.uma.nova.senha.para.esta.fabrica",$con,$cook_idioma);
            }

        }

        if(!count($msg_erro["msg"])) {
            $sql = "SELECT  *
                    FROM    tbl_posto_fabrica
                    WHERE   posto   = $login_posto
                    AND     fabrica = $login_fabrica ";
            $res = pg_query($con,$sql);
            $total_rows = pg_num_rows($res);

            if(pg_num_rows ($res) > 0) {
                if(strlen($senha) > 0){
                    $sql = "UPDATE tbl_posto_fabrica SET
                            senha                = '$senha',
                            data_expira_senha = current_date + interval '90day'
                        WHERE tbl_posto_fabrica.posto   = $login_posto
                        AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
                    $res = pg_query($con,$sql);
                    if (strlen (pg_errormessage ($con)) > 0) $msg_erro["msg"][] = pg_errormessage($con);
                }
            }
        }
    }

    if($login_fabrica == 24) {
        $sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);

        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
            $linha = pg_fetch_result ($res,$i,linha);
            $atende       = $_POST ['atende_'       . $linha];

            if(strlen ($atende) == 0) {
                $sql = "DELETE FROM tbl_posto_linha WHERE posto = $login_posto AND linha = $linha";
                $resX = pg_query ($con,$sql);
            }else{
                $sql = "SELECT * FROM tbl_posto_linha WHERE posto = $login_posto AND linha = $linha";
                $resX = pg_query ($con,$sql);
                if(pg_num_rows ($resX) == 0) {
                    $sql = "INSERT INTO tbl_posto_linha (
                                posto   ,
                                linha
                            ) VALUES (
                                $login_posto   ,
                                $linha
                            )";
                    $resX = pg_query ($con,$sql);
                }
            }
        }
    }

    if(!count($msg_erro["msg"])){
        $res = pg_query ($con,"COMMIT TRANSACTION");
        $msg_success = traduz('Alteração realizada com sucesso.');

        if($contrato_posto){

            $qtde_contratos = $_POST["qtde_contratos"];

            $tDocs = new TDocs($con, $login_fabrica);

            $info = $tDocs->getdocumentsByRef($login_posto, "posto", "contrato");

            $qtde_contratos_uploads = count($info->attachListInfo);

            if($qtde_contratos_uploads >= 5){

                $msg_erro["msg"][] = traduz('A quantidade de uploads de contratos não pode ser superior a 5!');

            }else{

                $envia_email_admin = false;

                for($c = 1; $c <= $qtde_contratos; $c++){

                    $contrato_file = $_FILES["contrato_{$c}"];

                    if($contrato_file["size"] > 0){

                        /* HD-3980490 Retirado o limite do anexo*/
                        $anexoID = $tDocs->uploadFileS3($contrato_file, $login_posto, false, "posto", "contrato");

                        if (!$anexoID) {
                            $msg_erro["msg"][] = traduz('Erro ao salvar o contato!');
                            break;
                        }else{
                            $envia_email_admin = true;
                        }
                    }

                }

                if($envia_email_admin){

                    if($_serverEnvironment == "development"){
                        //$email = "guilherme.silva@telecontrol.com.br;joao.junior@telecontrol.com.br;projeto@sbdbrasil.com.br";
                        $remetente_email[] = "thiago.tobias@telecontrol.com.br";
                        $remetente_email[] = "oscar.borges@telecontrol.com.br";
                        $email = implode(";", $remetente_email);
                    }else{
                        if (in_array($login_posto, [1])) {
                            $email = "contratoat@sbdbrasil.com.br";
                        } elseif (in_array($login_posto, [35])) {
                            $email = "suporte@telecontrol.com.br";
                        } 
                        
                    }

                    $sql_dados_posto = "SELECT 
                                    tbl_posto_fabrica.codigo_posto AS posto_codigo,
                                    tbl_posto.nome AS posto_nome,
                                    tbl_posto.cnpj AS posto_cnpj 
                                FROM tbl_posto 
                                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = {$login_fabrica} AND tbl_posto_fabrica.posto = tbl_posto.posto 
                                WHERE 
                                    tbl_posto.posto = {$login_posto}";
                    $res_dados_posto = pg_query($con, $sql_dados_posto);
                    
                    $posto_codigo   = pg_fetch_result($res_dados_posto, 0, "posto_codigo");
                    $posto_nome     = pg_fetch_result($res_dados_posto, 0, "posto_nome");
                    $posto_cnpj     = pg_fetch_result($res_dados_posto, 0, "posto_cnpj");

                    include_once 'class/communicator.class.php';

                    $assunto = traduz("Aviso de upload de contrato de prestação de serviço - % - Telecontrol",null,null,[$posto_nome]);
                    $mensagem = "
                        Prezados, <br /> 
                        informamos que o posto <strong>{$posto_codigo} - {$posto_nome}</strong> ({$posto_cnpj}) realizou o upload do contrato de prestação de serviços 
                        através do sistema da Telecontrol. <br />
                        Data: ".date("d/m/Y H:i:s")."
                    ";

                    $mailTc = new TcComm($externalId);
                    $res = $mailTc->sendMail(
                        $email,
                        $assunto,
                        $mensagem,
                        $externalEmail
                    );

                }

            }

        }
    }else{
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }

    if(!count($msg_erro["msg"])) {
        # ENVIA EMAIL
        $sql = "SELECT  email_gerente
            FROM    tbl_fabrica
            WHERE   fabrica = $login_fabrica
            AND     email_gerente notnull;";
        $resw = pg_query($con,$sql);

        if(pg_num_rows($resw) > 0 OR $login_fabrica==3){

            if($login_fabrica==3){
                //gustavo@telecontrol.com.br
                $email_britania = "cadastro.at@britania.com.br";
            }else{
                $email_gerente = pg_fetch_result($resw,0,'email_gerente');
                $email = explode (";",$email_gerente);
            }

            $sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            $codigo_posto = pg_fetch_result($res,0,'codigo_posto');

            #'------------ Manda email para GERENTE -------------
            if($login_fabrica==3 AND ($nome_fantasia<>$bnome_fantasia OR $email<>$bemail OR $endereco<>$bendereco OR $numero<>$bnumero OR $complemento<>$bcomplemento OR $bairro<>$bbairro OR $cep<>$bcep OR $fone<>$bfone OR $fax<>$bfax OR $contato<>$bcontato OR $capital_interior<>$bcapital_interior)){
                $text .= "<table width='600'>";
                $text .= "<tr><td colspan='2'>Houve alteração no cadastro do posto $codigo_posto - $login_nome.</td></tr>";
                $text .= "<tr><td colspan='2'>";
                $text .= "<BR>";
                $text .= "</td></tr>";
                if($nome_fantasia<>$bnome_fantasia){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Nome Fantasia = ".$nome_fantasia;
                    $text .= "</td></tr>";
                }
                if($email<>$bemail){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Email = ".$email;
                    $text .= "</td></tr>";
                }
                if($endereco<>$bendereco){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Endereço = ".$endereco;
                    $text .= "</td></tr>";
                }
                if($numero<>$bnumero){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Endereço = ".$numero;
                    $text .= "</td></tr>";
                }
                if($complemento<>$bcomplemento){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Complemento = ".$complemento;
                    $text .= "</td></tr>";
                }
                if($bairro<>$bbairro){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Bairro = ".$bairro;
                    $text .= "</td></tr>";
                }
                if($cep<>$bcep){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Cep = ".$cep;
                    $text .= "</td></tr>";
                }
                if($fone<>$bfone){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Fone = ".$fone;
                    $text .= "</td></tr>";
                }
                if($fax<>$bfax){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Fax = ".$fax;
                    $text .= "</td></tr>";
                }
                if($contato<>$bcontato){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Contato = ".$contato;
                    $text .= "</td></tr>";
                }
                if($capital_interior<>$bcapital_interior){
                    $text .= "<tr><td colspan='2'>";
                    $text .= "Capital/Interior = ".$capital_interior;
                    $text .= "</td></tr>";
                }
                    $text .= "</table>";
            }else if($login_fabrica<>3){
                $text .= "<table width='600'>";
                if ($sistema_lingua=='ES'){
                    $text .= "<tr><td colspan='2'><font size='2' face='verdana'>Fue cambiado el catastro del servicio $codigo_posto - $login_nome. Confira el sistema interno con el site.</font></td></tr>";
                }else{
                    $text .= "<tr><td colspan='2'><font size='2' face='verdana'>Houve alteração no cadastro do posto $codigo_posto - $login_nome. Confira o sistema interno com o site.</font></td></tr>";
                }
                $text .= "</table>";
            }

            if (strlen($text) > 0) {
                if ($sistema_lingua=='ES'){
                    $subject    = "Cambio em el catastro del servicio.";
                }else{
                    $subject    = "Alteração no Cadastro do Posto.";
                }
                if($login_fabrica==3){
                    //mail ($email_britania, stripslashes($subject), "$text" , "$cabecalho");
                    $mailer->IsSMTP();
                    $mailer->IsHTML();
                    $mailer->AddAddress($email_britania);
                    $mailer->Subject = $subject;
                    $mailer->Body = $text;
                    $mailer->Send();

                }else{
                    for ($i=0 ; $i < count($email); $i++){
                        mail ($email[$i] , stripslashes(utf8_encode($subject)), utf8_encode("$text") , "$cabecalho");
                        //echo "enviou $i";
                    }
                }
                $from_nome  = "";
                $from_email = "";
                $to_email   = "";
                $cc_nome    = "";
                $cc_email   = "";
                $subject    = "";
                $cabecalho  = "";
            }
        }
        #fim
        #header ("Location: $PHP_SELF");
        #exit;
    }
}

#-------------------- Pesquisa Posto -----------------
if(!count($msg_erro["msg"])) {
    $sql = "SELECT  tbl_posto_fabrica.posto               ,
                    tbl_posto_fabrica.codigo_posto        ,
                    tbl_posto_fabrica.tipo_posto          ,
                    tbl_posto_fabrica.transportadora_nome ,
                    tbl_posto_fabrica.transportadora      ,
                    tbl_posto_fabrica.cobranca_endereco   ,
                    tbl_posto_fabrica.cobranca_numero     ,
                    tbl_posto_fabrica.cobranca_complemento,
                    tbl_posto_fabrica.cobranca_bairro     ,
                    tbl_posto_fabrica.cobranca_cep        ,
                    tbl_posto_fabrica.cobranca_cidade     ,
                    tbl_posto_fabrica.cobranca_estado     ,
                    tbl_posto_fabrica.obs                 ,
                    tbl_posto_fabrica.atualizacao         ,
                    tbl_posto.nome                        ,
                    tbl_posto.cnpj                        ,
                    tbl_posto.ie                          ,
                    tbl_posto.im                          ,";
            if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90 ){
                $sql .= "
                    tbl_posto_fabrica.banco               ,
                    tbl_posto_fabrica.agencia             ,
                    tbl_posto_fabrica.conta               ,
                    tbl_posto_fabrica.nomebanco           ,
                    tbl_posto_fabrica.favorecido_conta    ,
                    tbl_posto_fabrica.conta_operacao      ,
                    tbl_posto_fabrica.cpf_conta           ,
                    tbl_posto_fabrica.atendimento         ,
                    tbl_posto_fabrica.tipo_conta          ,
                    tbl_posto_fabrica.obs_conta           ,";
            }
            $sql .= "tbl_posto_fabrica.contato_endereco       AS endereco,
                    tbl_posto_fabrica.contato_numero         AS numero,
                    tbl_posto_fabrica.contato_complemento    AS complemento,
                    tbl_posto_fabrica.contato_bairro         AS bairro,
                    tbl_posto_fabrica.contato_cep            AS cep,
                    tbl_posto_fabrica.contato_cidade         AS cidade,
                    tbl_posto_fabrica.contato_estado         AS estado,
                    tbl_posto_fabrica.contato_email          AS email,
                    tbl_posto_fabrica.contato_fone_comercial AS fone,
                    tbl_posto_fabrica.contato_fone_residencial AS fone2,
                    tbl_posto_fabrica.contato_cel AS fone3,
                    tbl_posto_fabrica.contato_fax            AS fax,
                    tbl_posto_fabrica.contato_nome           AS contato,
                    tbl_posto.capital_interior            ,
                    tbl_posto_fabrica.nome_fantasia       ,
                    tbl_posto_fabrica.senha               ,
                    tbl_posto_fabrica.desconto,
                    tbl_posto_fabrica.parametros_adicionais
            FROM    tbl_posto
            LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
            WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
            AND     tbl_posto_fabrica.posto   = $login_posto ";
    $res = pg_query($con,$sql);

    $posto_parametros_adicionais = array();

    if (pg_num_rows ($res) > 0) {
        $codigo           = trim(pg_result($res,0,codigo_posto));
        $nome             = trim(pg_result($res,0,nome));
        $cnpj             = trim(pg_result($res,0,cnpj));
        $ie               = trim(pg_result($res,0,ie));
        $atualizacao      = trim(pg_result($res,0,atualizacao));
        $im               = trim(pg_result($res,0,im));
        if (strlen($cnpj) == 14) {
            $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
        }
        if (strlen($cnpj) == 11) {
            $cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
        }
        $endereco         = trim(pg_result($res,0,endereco));
        $endereco         = str_replace("\"","",$endereco);
        $numero           = trim(pg_result($res,0,numero));
        $complemento      = trim(pg_result($res,0,complemento));
        $bairro           = trim(pg_result($res,0,bairro));
        $cep              = trim(pg_result($res,0,cep));
        $cidade           = trim(pg_result($res,0,cidade));
        $estado           = trim(pg_result($res,0,estado));
        $email            = trim(pg_result($res,0,email));
        $fone             = trim(pg_result($res,0,fone));
        $fone2            = trim(pg_result($res,0,fone2));
        $fone3            = trim(pg_result($res,0,fone3));
        $fax              = trim(pg_result($res,0,fax));
        $contato          = trim(pg_result($res,0,contato));
        $obs              = trim(pg_result($res,0,obs));
        $capital_interior = trim(pg_result($res,0,capital_interior));
        $senha            = trim(pg_result($res,0,senha));
        $nome_fantasia    = trim(pg_result($res,0,nome_fantasia));

        if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90){
            $banco            = trim(pg_result($res,0,banco));
            $agencia          = trim(pg_result($res,0,agencia));
            $conta            = trim(pg_result($res,0,conta));
            $nomeconta        = trim(pg_result($res,0,nomebanco));
            $favorecido_conta = trim(pg_result($res,0,favorecido_conta));
            $cpf_conta        = trim(pg_result($res,0,cpf_conta));
            $tipo_conta       = trim(pg_result($res,0,tipo_conta));
            $obs_conta        = trim(pg_result($res,0,obs_conta));
        }
        $cobranca_endereco    = trim(pg_result($res,0,cobranca_endereco));
        $cobranca_numero      = trim(pg_result($res,0,cobranca_numero));
        $cobranca_complemento = trim(pg_result($res,0,cobranca_complemento));
        $cobranca_bairro      = trim(pg_result($res,0,cobranca_bairro));
        $cobranca_cep         = trim(pg_result($res,0,cobranca_cep));
        $cobranca_cidade      = trim(pg_result($res,0,cobranca_cidade));
        $cobranca_estado      = trim(pg_result($res,0,cobranca_estado));

        $posto_parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true);
    }
}


$title = traduz("suas.informacoes",$con,$cook_idioma);
$layout_menu = "cadastro";

include 'cabecalho_new.php';
$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);
include("plugin_loader.php");
###########FIM POST NOVO###########
?>

<!-- <script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script> -->
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<!-- <script type="text/javascript" src="js/jquery.js"></script> -->
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<!--<script type="text/javascript" src="js/jquery.dimensions.js"></script> -->
<script type="text/javascript" src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script type='text/javascript' src='js/jquery.maskedinput.js'></script>
<script type="text/javascript" src="admin/js/jquery.mask.js"></script>
<script type="text/javascript"    src="js/jquery.alphanumeric.js"></script>

<script language='javascript'>
    function checarNumero(campo){
        var num = campo.value.replace(",",".");
        campo.value = parseInt(num);
        if (campo.value=='NaN') {
            campo.value='';
        }
    }
    function isNumberKey ( evt ){
        var charCode = ( evt.which ) ? evt.which : event.keyCode;
        if ( charCode > 31 && (charCode < 48 || charCode > 57) ) return false;
        return true;
    }
    //adiciona mascara de cep
    function MascaraCep(cep){
        if(mascaraInteiro(cep)==false){
            event.returnValue = false;
        }
        return formataCampo(cep, '00.000-000', event);
    }
    var hora = new Date();
    var engana = hora.getTime();
    $().ready(function() {
        $('#banco_nome').autocomplete("autocomplete_banco_ajax.php?engana=" + engana,{
            minChars: 3,
            delay: 150,
            width: 450,
            scroll: true,
            scrollHeight: 200,
            matchContains: false,
            highlightItem: false,
            formatItem: function (row)   {return row[0]+" - "+row[1]},
            formatResult: function(row)  {return row[0];}
        });
        $('#banco_nome').result(function(event, data, formatted) {
            //alert(data[0]);
            $("#banco_nome").val(data[0] + '-' + data[1]);
            //HD 344430: O banco deve ser recuperado e gravado pelo campo tbl_banco.codigo e não por tbl_banco.banco
            $("#banco").val(data[0]);
        });

        setTimeout(function(){
            $('.alert-success').hide('slow');
        }, 2000);

    })
    $(function(){
        $.datepickerLoad(Array("data_nomeacao"));         

        $("#ver_senha").change(function() {
            if($('.password').attr('type') == 'text'){
                $('.password').attr('type', 'password');
            }else{
                $('.password').attr('type', 'text');
            }
        })

        $('input[name=im]').numeric();

        var phoneMask = function(){
            if($(this).val().match(/^\(0/)){
                $(this).val('(');
                return;
            }
            if($(this).val().match(/^\([1-9][0-9]\) *[0-8]/)){
                $(this).mask('(00) 0000-0000');
                console.debug('telefone');
            }else{
                $(this).mask('(00) 00000-0000');
                console.debug('celular');
            }
            $(this).keyup(phoneMask);
        };

        $('.telefone').keyup(phoneMask);

        <?php if ($login_fabrica == 175) { #hd-4011248 ?> 
            
            $("input").filter(function(){
                return !$(this).hasClass('password');
            }).prop("disabled", true);

        <?php } ?>

    });
    function buscaCEP(cep, endereco, bairro, method) {
        if (typeof cep != "undefined" && cep.length > 0) {
            if (typeof method == "undefined" || method.length == 0) {
                method = "webservice";

                $.ajaxSetup({
                    timeout: 3000
                });
            } else {
                $.ajaxSetup({
                                    timeout: 5000
                            });
            }

            $.ajax({
                url: "ajax_cep.php",
                type: "GET",
                data: { cep: cep, method: method },
                error: function(xhr, status, error) {
                    buscaCEP(cep, endereco, bairro, "database");
                },
                success: function(data) {
                    results = data.split(";");

                    // if (results[4] != undefined && results[4].length > 0) {
                    //     $("#consumidor_cidade, #cidade").removeAttr("readonly");
                    // } else {
                    //     $("#consumidor_cidade, #cidade").attr({ "readonly": "readonly" });
                    // }

                    if (results[0] == "ok") {
                        //if (results[4] != undefined) estado.value = results[4];
                        //if (results[3] != undefined) cidade.value = results[3];
                        if (results[1] != undefined && results[1].length > 0) endereco.value = results[1];
                        if (results[2] != undefined && results[2].length > 0) bairro.value = results[2];
                    }
                }
            });
        }
    }

    <?php if($contrato_posto){ ?>

        function excluir_contrato(id){

            var r = confirm("Você deseja realmente excluir esse contrato?");

            if (r == true) {

                location.href = "posto_cadastro_new.php?excluir_contrato=true&id="+id;

            }

        }

    <?php } ?>

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <br/>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<?php
if (strlen($msg_success) > 0) {
?>
    <br/>
    <div class="alert alert-success">
        <h4><?=$msg_success?></h4>
    </div>
<?php
    
    if(in_array($login_fabrica, array(1))){

        ?>

        <script>
            setTimeout(function(){
                location.href = "menu_cadastro.php";
            }, 4000);
        </script>

        <?php

    }

}
?>


<br/>
<div class="alert alert-info">
    <? fecho ("para.alterar.os.outros.campos.entre.em.contato.com.o.fabricante",$con,$cook_idioma);?>
</div>
<br/>
<?php

    if ($login_fabrica == 24 || $login_fabrica == 15) {

        if ($login_fabrica == 24) {
            $data_hora = '2010-06-09 09:36:39.548903';
        } else if ($login_fabrica == 15) {
            $data_hora = '2010-08-10 09:36:39.548903';
        }

        $sql = "SELECT CASE WHEN '$atualizacao' <= '$data_hora' THEN 'sim' ELSE 'NAO' END";
        $res = pg_exec($con,$sql);

        if(pg_num_rows($res) > 0) {
            $resposta = pg_result($res,0,0);
        }

        if($resposta == 'sim') {
        ?>
            <div class="alert alert-block">
                <?= traduz('Por favor, para continuar é necessário atualizar os dados, se todos dados estiverem corretos, clique no botão Gravar.
                <br/>Após isso para continuar acesse a Aba Ordem de Serviço no canto superior esquerdo da tela.') ?>
            </div>
        <?php
        }
    }

    if($login_fabrica == 15){
        $span = "span3";
        $span2 = "span10";
    }else{
        $span2 = "span11";
        $span = "span4";
    }

?>
<!-- NOVO FORM -->
<form name='frm_posto' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' enctype="multipart/form-data" >
    <div class='titulo_tabela '><? echo traduz("informacoes.cadastrais",$con,$cook_idioma);?></div>
    <br/>
    <div class="row-fluid">
        <div class="span1"></div>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='codigo'><? echo traduz("codigo",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="codigo" readonly size="30" maxlength="30" class='span12' value="<?=$codigo?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='nome'><? echo traduz("razao.social",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <input type="text" name="nome" readonly size="30" maxlength="50" class='span12' value="<?=$nome?>" >
                    </div>
                </div>
            </div>
        </div>

        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='cnpj'><? fecho ("cnpj.cpf",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="cnpj" size="12" readonly maxlength="20" class='span12' value= "<?=$cnpj?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>

    <div class='row-fluid'>
        <div class='span1'></div>

        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='ie'><? fecho ("ie",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="ie" size="12" readonly maxlength="20" class='span12' value="<?=$ie?>" >
                    </div>
                </div>
            </div>
        </div>

        <?php if($login_fabrica == 15){?>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='im'><? echo traduz("im",$con,$cook_idioma);?></label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <input type="text" name="im" size="12" maxlength="20" class='span12' value= "<?=$im?>" onblur="checarNumero(this);">
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <div class='<?=$span?> bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='fone'><? echo traduz("fone",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='<?=$span2?>'>
                        <input type="text" name="fone" id="fone" size="30" maxlength="15" class='span12 telefone' value="<?=$fone?>" >
                    </div>
                </div>
            </div>
        </div>
        <?php if($login_fabrica == 81){ ?>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label' for='fone2'><? echo traduz("fone",$con,$cook_idioma).' 2';?></label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="text" name="fone2" id="fone2"  size="30" maxlength="20" class='span12 telefone' value="<?=$fone2?>" >
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <? if($login_fabrica <> 15 AND $login_fabrica <> 81){ ?>
            <div class='span3 bloqueia_input'>
                <div class='control-group'>
                    <label class='control-label' for='fax'><? echo traduz("fax",$con,$cook_idioma);?></label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="text" name="fax" size="13" maxlength="20" class='span12 telefone' value="<?=$fax?>" >
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>


        <div class='span1'></div>
    </div>

    <div class='row-fluid'>
        <div class="span1"></div>
        <? if($login_fabrica == 15 OR $login_fabrica == 81){ ?>
            <div class='span3 bloqueia_input'>
                <div class='control-group'>
                    <label class='control-label' for='fax'><? echo traduz("fax",$con,$cook_idioma);?></label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="text" name="fax" size="13" maxlength="20" class='span12 telefone' value="<?=$fax?>" >
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php  if($login_fabrica == 30){ ?>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='fone2'><?= traduz('Fone Celular 1') ?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="fone2" size="13" maxlength="20" class='span12 telefone' value="<?=$fone2?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='fone3'><?= traduz('Fone Celular 2') ?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="fone3" id="fone3" maxlength="15" class='span12 telefone' value="<?=$fone3?>" >
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>

        <div class='span3 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='contato'><? echo traduz("contato",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                         <input type="text" name="contato" size="30" maxlength="30" class='span12' value="<?=$contato?>" >
                    </div>
                </div>
            </div>
        </div>

        <? if($login_fabrica == 20) { ?>
            <div class='span4 bloqueia_input'>
                <div class='control-group'>
                    <label class='control-label' for='email'><? echo traduz("email",$con,$cook_idioma);?></label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <input type="text" name="email" size="40" maxlength="50" class='span12' value="<?=$email?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label' for='email_alternativo'><? echo traduz("email.alternativo",$con,$cook_idioma);?></label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="text" name="email_alternativo" size="13" class='span12' value="<?=$email_alternativo?>" >
                        </div>
                    </div>
                </div>
            </div>            
        <? } ?>        

<?if ($login_fabrica == 3){?>

        <div class='span4 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='nome_fantasia'><? echo traduz("nome.fantasia",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <input type="text" name="nome_fantasia" size="40" maxlength="30" class='span12' value="<?=$nome_fantasia?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span3 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='capital_interior'><? echo traduz("capital.interior",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span7'>
                        <?php //HD 24581
                        if ($login_fabrica==30){
                        ?>
                            <input type="text" readonly name="capital_interior" size="40" maxlength="30" class='span12' value="<?=$capital_interior?>">
                        <?php
                        }else{ ?>
                            <select name='capital_interior' style='width:165px;'>
                                <option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> ><? echo strtoupper(traduz("capital",$con,$cook_idioma));?></option>
                                <option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> ><? echo strtoupper(traduz("interior",$con,$cook_idioma));?></option>
                            </select>
                        <? } ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span1"></div>
        <div class='span3 form_endereco bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='cep'><? echo traduz("cep",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="cep" size="8" maxlength="8" class='span12' value="<?=$cep?>" onblur=" buscaCEP(this.value, this.form.endereco, this.form.bairro);" <? if($login_fabrica==50) echo "onblur=\"checarNumero(this);\"";?> >
                    </div>
                </div>
            </div>
        </div>
        <div class='span8 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='endereco'><? echo traduz("endereco",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="endereco" size="42" maxlength="50" class='span12' value="<?=$endereco?>" >
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid form_endereco">
        <div class="span1"></div>
        <div class='span3 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='bairro'><? echo traduz("bairro", $con, $cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="bairro" size="40" maxlength="20" class='span12' value="<?=$bairro?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span1 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='numero'><? echo traduz("numero",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="numero" size="10" maxlength="10" class='span12' value="<?=$numero?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='complemento'><? echo traduz("complemento",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="complemento" size="35" maxlength="40" class='span12' value="<?=$complemento?>" style='width: 90px;' >
                    </div>
                </div>
            </div>
        </div>
        <div class='span1 form_endereco'>
            <div class='control-group'>
                <label class='control-label' for='estado'><? echo traduz("estado",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <input type="text" readonly name="estado" size="35" maxlength="40" class='span12' value="<?=$estado?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span3 form_endereco'>
            <div class='control-group'>
                <label class='control-label' for='cidade'><? echo traduz("cidade",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" readonly name="cidade" size="35" maxlength="40" class='span12' value="<?=$cidade?>" >
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span1"></div>
        <div class='span8 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='email'><? echo traduz("email",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="email" size="30" maxlength="50" class='span12' value="<?=$email?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>

<?}else{?>
    </div>
    <div class="row-fluid">
        <div class="span1"></div>
        <div class='span3 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='capital_interior'><? echo traduz("capital.interior",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span7'>
                        <?php //HD 24581
                        if ($login_fabrica==30 || $login_fabrica == 175){
                        ?>
                            <input type="text" readonly name="capital_interior" size="40" maxlength="30" class='span12' value="<?=$capital_interior?>">
                        <?php
                        }else{ ?>
                            <select name='capital_interior' style='width:165px;'>
                                <option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> ><? echo strtoupper(traduz("capital",$con,$cook_idioma));?></option>
                                <option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> ><? echo strtoupper(traduz("interior",$con,$cook_idioma));?></option>
                            </select>
                        <? } ?>

                    </div>
                </div>
            </div>
        </div>
        <div class='span4 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='nome_fantasia'><? echo traduz("nome.fantasia",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <input type="text" name="nome_fantasia" size="40" maxlength="30" class='span12' value="<?=$nome_fantasia?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span3 form_endereco bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='cep'><? echo traduz("cep",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="cep" size="8" maxlength="8" class='span12' value="<?=$cep?>" onblur=" buscaCEP(this.value, this.form.endereco, this.form.bairro);" <? if($login_fabrica==50) echo "onblur=\"checarNumero(this);\"";?> >
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>

    <div class="row-fluid form_endereco">
        <div class="span1"></div>
        <div class='span3 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='endereco'><? echo traduz("endereco",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="endereco" size="42" maxlength="50" class='span12' value="<?=$endereco?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='bairro'><? echo traduz("bairro", $con, $cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <input type="text" name="bairro" size="40" maxlength="20" class='span12' value="<?=$bairro?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span1 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='numero'><? echo traduz("numero",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="numero" size="10" maxlength="10" class='span12' value="<?=$numero?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2 bloqueia_input'>
            <div class='control-group'>
                <label class='control-label' for='complemento'><? echo traduz("complemento",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="complemento" size="35" maxlength="40" class='span12' value="<?=$complemento?>" style='width: 90px;' >
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>

    <div class="row-fluid">
        <div class="span1"></div>
        <div class='span3 form_endereco'>
            <div class='control-group'>
                <label class='control-label' for='cidade'><? echo traduz("cidade",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" readonly name="cidade" size="35" maxlength="40" class='span12' value="<?=$cidade?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4 form_endereco'>
            <div class='control-group'>
                <label class='control-label' for='estado'><? echo traduz("estado",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <input type="text" readonly name="estado" size="35" maxlength="40" class='span12' value="<?=$estado?>" >
                    </div>
                </div>
            </div>
        </div>
        <? if($login_fabrica != 20) { ?>
            <div class='span3 bloqueia_input'>
                <div class='control-group'>
                    <label class='control-label' for='email'><? echo traduz("email",$con,$cook_idioma);?></label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="text" name="email" size="30" maxlength="50" class='span12' value="<?=$email?>">
                        </div>
                    </div>
                </div>
            </div>
        <? } else { ?>
            <div class='span3'>
                <div class='control-group'>                    
                    <label class='obrigatorio' for='data_nomeacao'>*&nbsp;<? echo traduz("data.nomeação",$con,$cook_idioma);?></label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="text" name="data_nomeacao" id="data_nomeacao" size="13" maxlength="20" class='span12' value="<?=$data_nomeacao?>" >
                        </div>
                    </div>
                </div>
            </div>             
        <? } ?>
        <div class="span1"></div>
    </div>

<?}?>
    <?php if($login_fabrica <> 1){ ?>
    <div class='titulo_tabela '><? fecho("observacoes",$con,$cook_idioma);?></div>
    <div class="container">
        <div class="row-fluid">
            <div class="span2"></div>
                <div class="span8">
                    <div class='control-group'>
                        <label class='control-label'></label>
                        <div class='controls controls-row'>
                            <div class="span12">
                                <textarea class="span12" readonly rows="3"><? echo $obs ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="span2"></div>
        </div>
    </div>


    <?php } ?>
    <!-- ---------------------------  Informações Bancárias ------------------------- -->

    <?php if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90){ ?>
        <br/>
        <div class="titulo_tabela"><?= traduz('Informações Bancárias') ?></div><br/>

        <div class="row-fluid">
            <div class="span1"></div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label' for='cpf_conta'><?= traduz('CNPJ Empresa') ?></label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="text" name="cpf_conta" size="14" maxlength="19" class='span12' value="<?=$cpf_conta?>" onkeypress='return isNumberKey(event)'>
                        </div>
                    </div>
                </div>
            </div>

            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='favorecido_conta'><?= traduz('Nome da Empresa') ?> <span class="label label-important">Somente Conta Jurídica</span></label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <input type="text" name="favorecido_conta" size="60" maxlength="50" class='span12' value="<?=$favorecido_conta?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label' for='banco_nome'><?= traduz('BANCO') ?></label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <?php
                            if (strlen($banco) > 0) {
                                //HD 344430: o banco deve ser recuperado e gravado sempre pelo campo tbl_banco.codigo
                                $sql_banco = "SELECT codigo,
                                            nome, banco
                                        FROM tbl_banco
                                        WHERE codigo = '$banco'";
                                $rs_banco = pg_query($con, $sql_banco);
                                $banco_nome = pg_fetch_result($rs_banco,0,codigo) . ' - ' . pg_fetch_result($rs_banco,0,nome);
                            } else {
                                $banco      = '';
                                $banco_nome = '';
                            }?>
                            <input type="text" id="banco_nome" name="banco_nome" size="90" maxlength="20" class='span12 Caixa' title="Digite o nome/código do banco." value="<?=$banco_nome?>">
                            <input id="banco" name="banco" type="hidden" value="<?=$banco?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>

        <div class="row-fluid">
            <div class="span1"></div>

            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label' for='tipo_conta'><?= traduz('Tipo de Conta') ?></label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <select name='tipo_conta' style='width: 165px;'>
                            <?php
                                // if (strlen($tipo_conta)>0){
                                //     echo " DISABLED";
                                // }
                            ?>
                                <option selected></option>
                                <option value='Conta conjunta'   <? if ($tipo_conta == 'Conta conjunta')   echo "selected"; ?>>Conta conjunta</option>
                                <option value='Conta corrente'   <? if ($tipo_conta == 'Conta corrente')   echo "selected"; ?>>Conta corrente</option>
                                <option value='Conta individual' <? if ($tipo_conta == 'Conta individual') echo "selected"; ?>>Conta individual</option>
                                <option value='Conta jurídica'   <? if ($tipo_conta == 'Conta jurídica')   echo "selected"; ?>>Conta jurídica</option>
                                <option value='Conta poupança'   <? if ($tipo_conta == 'Conta poupança')   echo "selected"; ?>>Conta poupança</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='agencia'><?= traduz('Agência') ?></label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <input type="text" name="agencia" size="10" maxlength="10" class='span12' value="<?=$agencia?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label' for='conta'><?= traduz('Conta') ?></label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="text" name="conta" size="15" maxlength="15" class='span12' value="<?=$conta?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>

        <br/>
        <div class="titulo_tabela"><?= traduz('Observações') ?></div>
        <div class="container">
            <div class="row-fluid">
                <div class="span2"></div>
                    <div class="span8">
                        <div class='control-group'>
                            <label class='control-label'></label>
                            <div class='controls controls-row'>
                                <div class="span12">
                                    <textarea class="span12" name="obs_conta" rows="3"><? echo $obs_conta; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                <div class="span2"></div>
            </div>
        </div><br/>
    <?php } ?>

    <!-- ---------------------------  Cobranca ------------------------- -->
    <br/>
    <div class="titulo_tabela"><? echo traduz("informacoes.para.cobranca",$con,$cook_idioma);?></div>
    <br />
    <div class="row-fluid">
        <div class="span1"></div>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='endereco_cobranca'><? echo traduz("endereco",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="endereco_cobranca" readonly size="40" maxlength="30" class='span12' value="<?=$cobranca_endereco?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='cobranca_bairro'><? echo traduz("bairro",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <input type="text" name="cobranca_bairro" readonly size="40" maxlength="30" class='span12' value="<?=$cobranca_bairro?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span1'>
            <div class='control-group'>
                <label class='control-label' for='cobranca_numero'><? echo traduz("numero",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="cobranca_numero" readonly size="40" maxlength="30" class='span12' value="<?=$$cobranca_numero?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group'>
                <label class='control-label' for='cobranca_complemento'><? echo traduz("complemento",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="cobranca_complemento" readonly size="40" maxlength="30" class='span12' value="<?=$cobranca_complemento?>" style="width: 90px;" >
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>

    <div class="row-fluid">
        <div class="span1"></div>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='cobranca_cidade'><? echo traduz("cidade",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="cobranca_cidade" readonly size="40" maxlength="30" class='span12' value="<?=$cobranca_cidade?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='cobranca_estado'><? echo traduz("estado",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <input type="text" name="cobranca_estado" readonly size="40" maxlength="30" class='span12' value="<?=$cobranca_estado?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='cobranca_cep'><? echo traduz("cep",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <input type="text" name="cobranca_cep" readonly size="40" maxlength="30" class='span12' value="<?=$cobranca_cep?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>

    <?php if ($login_fabrica == 156): ?>
    <br/>
    <div class="titulo_tabela"><?= traduz('Atestado de Capacitação') ?></div>

    <?php
    $sqlFPA = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica";
    $qryFPA = pg_query($con, $sqlFPA);

    $fabrica_parametros_adicionais = json_decode(pg_fetch_result($qryFPA, 0, 'parametros_adicionais'), true);
    $atestadoCapacitacao = array();

    if (array_key_exists("atestadoCapacitacao", $fabrica_parametros_adicionais)) {
        $atestadoCapacitacao = $fabrica_parametros_adicionais["atestadoCapacitacao"];
    }

    $posto_capacitacao = array();

    if (!empty($posto_parametros_adicionais)) {
        foreach ($posto_parametros_adicionais as $key => $val) {
            preg_match("/^data_capacitacao_(.*)/", $key, $matches);

            if ($matches) {
                $posto_capacitacao[$matches[1]] = $val;
            }
        }
    }
    ?>

    <div class="row-fluid">
        <div class="row">
            <div class="span2"></div>
            <div class="span4"><strong><?= traduz('Capacitação') ?></strong></div>
            <div class="span4"><strong><?= traduz('Validade') ?></strong></div>
            <div class="span2"></div>
        </div>

        <?php
        foreach ($atestadoCapacitacao as $idx => $capacitacao) {
            if (array_key_exists($capacitacao, $posto_capacitacao)) {
            ?>
            <div class="row">
                <div class="span2"></div>
                <div class="span4"><?php echo $capacitacao ?></div>
                <div class="span4"><?php echo $posto_capacitacao[$capacitacao]; ?></div>
                <div class="span2"></div>
            </div>
            <?php
            }
        }
        ?>
    </div>

    <?php endif ?>

    <?php if($login_fabrica == 24){ ?>
        <div class="titulo_tabela"><?= traduz('Linhas') ?></div>
        <table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_coluna'>
                    <th><?= traduz('Linha') ?></th>
                    <th>Atende</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $sql = "SELECT  tbl_linha.linha,
                                        tbl_linha.nome
                                FROM    tbl_linha
                                WHERE   tbl_linha.fabrica = $login_fabrica ";
                        $res = pg_query ($con,$sql);

                    if (pg_num_rows($res)>0) {
                        for ($i=0;$i<pg_num_rows($res);$i++) {
                            $linha = pg_result($res,$i,linha);
                            $nome = pg_result($res,$i,nome);

                            $sqlX = "SELECT * FROM tbl_posto_linha WHERE posto = $login_posto AND linha = $linha";

                            $resX = pg_query ($con,$sqlX);

                            if (pg_num_rows ($resX) == 1) {
                                $check        = " CHECKED ";
                            }

                            echo "<tr>
                                    <td class='tac'>$nome</td>
                                    <td class='tac'><input type='checkbox' value='$linha' name='atende_$linha' $check></td>
                            </tr>";
                            $check = '';
                        }
                    }
                ?>
            </tbody>
        </table>

    <?php } ?>

    <br />

    <div class="titulo_tabela"><? echo traduz("digite.a.senha.somente.se.for.alterar",$con,$cook_idioma);?></div>
    <br/>
    <div class="row-fluid">
        <div class="span1"></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='senha'><? echo traduz("alterar.senha",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <input type="password" name="senha" size="10" maxlength="10" class='span12 password' value="">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='senha2'><? echo traduz("repita.nova.senha",$con,$cook_idioma);?></label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <input type="password" name="senha2" size="10" maxlength="10" class='span12 password' value="">
                    </div>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class='control-group'>
                <div class='controls controls-row'>
                    <label class="checkbox" style="padding-top: 23px;">
                        <input type="checkbox" id="ver_senha" value="">
                        <?= traduz('Visualizar senha') ?>
                    </label>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>

    <?php if($contrato_posto){ ?>

        <?php

        $tDocs = new TDocs($con, $login_fabrica);

        $info = $tDocs->getdocumentsByRef($login_posto, "posto", "contrato");

        if(count($info->attachListInfo) > 0){

            $qtde_contratos = 5 - count($info->attachListInfo);

        }else{
            $qtde_contratos = 5;
        }

        ?>

        <input type="hidden" name="qtde_contratos" value="<?php echo $qtde_contratos; ?>">

        <br />

        <div class="titulo_tabela"><? echo traduz("upload.de.contratos",$con,$cook_idioma);?></div>
        <br/>

        <style>
            .box-contrato{
                float: left; 
                height: 100px; 
                width: 110px;
                padding: 14px;
                text-align: center;
            }
            .box-contrato img{
                width: 73px;
            }
            .box-contrato button{
                margin-top: 10px;
            }
        </style>

        <?php

        if($qtde_contratos < 5){

            echo "<div class='row-fluid'>";

                echo "<div class='span1'></div>";

                echo "<div class='span10'>";

                foreach ($info->attachListInfo as $anexo) {

                    $tdocs_id = $anexo["tdocs_id"];
                    $link_arq = $anexo["link"];
                    $icon_pdf = "imagens/pdf_icone.png";
                    
                    echo "
                    <div class='box-contrato'>
                        <a href='{$link_arq}' target='_blank'>
                            <img src='{$icon_pdf}' />
                        </a>
                        <!-- Retirado a pedido do João Jr, pois o posto deverá solicitar ao admin a exclusão do anexo -->
                        <!-- <button type='button' class='btn btn-danger' onclick='excluir_contrato(\"{$tdocs_id}\")'>
                            Excluir
                        </button> -->
                    </div>
                    ";

                }

                echo "<div style='clear: both;'></div>";

                echo "</div>";

            echo "</div>";

        }

        ?>
        
        <?php for($i = 1; $i <= $qtde_contratos; $i++){ ?>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label' for='senha'><? echo traduz("contrato.{$i}",$con,$cook_idioma);?></label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <input type="file" name="contrato_<?php echo $i; ?>" class='span12'>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <?php } ?>

    <?php } ?>

    <p><br/>
        <input type="hidden" name="btn_acao" value="">
        <button class='btn'  style="cursor: pointer;" title="<?=traduz('gravar.formulario', $con)?>"
            onclick="if (document.frm_posto.btn_acao.value == '') {
                document.frm_posto.btn_acao.value='gravar';
                document.frm_posto.submit();
            } else {
                alert ('<?=traduz('aguarde.submissao', $con)?>')
            }"><?=traduz('gravar', $con)?>
        </button>
    </p><br/>
</form>

<script>
		document.onkeydown = function(){
			switch (event.keyCode){
			case 116 : //F5 button
				event.returnValue = false;
				event.keyCode = 0;
				return false;
			case 82 : //R button
				if (event.ctrlKey){ 
					event.returnValue = false;
					event.keyCode = 0;
					return false;
				}
			}
		}
</script>

<?php if($bloqueia_atualizar_endereco) {  ?>
        <script>
            $('.form_endereco input').attr('readonly', true);
        </script>
<?php } ?>

<?php if($bloqueia_input_campos) {  ?>
        <script>
            $('.bloqueia_input input, select').attr('readonly', true);
        </script>
<?php } ?>
<?php
    //hd chamado - 3505
    //hd chamado - 18385
    if (in_array( $login_fabrica, array( 1, 11, 50, 87, 172))) { ?>
        <script>
            var formi = document.frm_posto;
            for( i=0; i<formi.length; i++ ) {
                if (formi.elements[i].type === 'text' || formi.elements[i] === 'select-one' ) {
                    formi.elements[i].disable = true;
                }
            }
        </script>
<?php } ?>
<? include "rodape.php"; ?>
