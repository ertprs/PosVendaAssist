<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../plugins/fileuploader/TdocsMirror.php';
include_once '../../class/communicator.class.php';

if ($_serverEnvironment == 'development'){
    $URL_BASE = "http://novodevel.telecontrol.com.br/~felipe/posvenda/externos/viapol/";
    $admin = 12002;//devel
}else{
    $URL_BASE = "https://posvenda.telecontrol.com.br/assist/externos/viapol/";
    $admin = 12160;//prod
}

$login_fabrica = 189;

function getCidades($con, $consumidor_cidade, $consumidor_estado) {
    $sql = "
        SELECT  tbl_cidade.cidade,
                tbl_cidade.nome AS cidade_nome
        FROM    tbl_cidade
        WHERE   tbl_cidade.cod_ibge IS NOT NULL
        AND     tbl_cidade.estado = '$consumidor_estado'
        AND     tbl_cidade.nome = '$consumidor_cidade'
  ORDER BY      cidade_nome
    ";
    $res = pg_query($con,$sql);

    $resultado = pg_fetch_object($res);
    $cidades = array("cidade_id" => $resultado->cidade, "cidade_nome" => $resultado->cidade_nome);

    return $cidades;
}

if (!function_exists('converte_data')) {
    function converte_data($date)
    {
        $date = explode("-", preg_replace('/\//', '-', $date));
        $date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
        if (sizeof($date)==3)
            return $date2;
        else return false;
    }
}

if (!function_exists('checaCPF')) {
    function checaCPF  ($cpf,$return_str = true, $use_savepoint = false){
       global $con, $login_fabrica; 
            $cpf = preg_replace("/\D/","",$cpf);   
            if ((($login_fabrica==52  and strlen($_REQUEST['pre_os'])>0) or
                $login_fabrica==11 or $login_fabrica == 172) and
                date_to_timestamp($_REQUEST['data_abertura'])<date_to_timestamp('24/12/2009')) return $cpf;
            if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) return false;

            if(strlen($cpf) > 0){
                $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
                if ($res_cpf === false) {
                    $cpf_erro = pg_last_error($con);
                    return ($return_str) ? $cpf_erro : false;
                }
            }
            return $cpf;

    }
}

if (!function_exists('Valida_Data')) {
    function Valida_Data($dt){
        $data = explode("/","$dt");
        $d = $data[0];
        $m = $data[1];
        $y = $data[2];

        $res = checkdate($m,$d,$y);
        if ($res == 1){
           return "ok";
        } else {
           return "erro";
        }
    }
}

if ($_POST) {

        $dispara_email = [
                            "RH" => "rh@viapol.com.br",
                            "MARKETING" => "marketing@viapol.com.br"
                        ]; 

        if (!filter_input(INPUT_POST,"nome")) {
            $msg_erro["msg"][] = "Preencha o campo CPF";
            $msg_erro['campos'][] = "nome";
        }

        if (!filter_input(INPUT_POST,"email",FILTER_VALIDATE_EMAIL)) {
            $msg_erro["msg"][] = "Preencha o campo E-mail";
            $msg_erro['campos'][] = "email";
        }

        if (!filter_input(INPUT_POST,"fone") && !filter_input(INPUT_POST,"celular")) {
            $msg_erro["msg"][] = "Preencha um  Telefone ou Celular";
            $msg_erro['campos'][] = "fone";
        }

        if (!filter_input(INPUT_POST,"profissao")) {
            $msg_erro["msg"][] = "Preencha o campo Profissão";
            $msg_erro['campos'][] = "profissao";
        }

        if (!filter_input(INPUT_POST,"cep")) {
            $msg_erro["msg"][] = "Preencha o campo CEP";
            $msg_erro['campos'][] = "cep";
        }

        if (!filter_input(INPUT_POST,"endereco")) {
            $msg_erro["msg"][] = "Preencha o campo Endereço";
            $msg_erro['campos'][] = "endereco";
        }

        if (!filter_input(INPUT_POST,"numero")) {
            $msg_erro["msg"][] = "Preencha o campo Número";
            $msg_erro['campos'][] = "numero";
        }

        if (!filter_input(INPUT_POST,"bairro")) {
            $msg_erro["msg"][] = "Preencha o campo Bairro";
            $msg_erro['campos'][] = "bairro";
        }

        if (!filter_input(INPUT_POST,"cidade")) {
            $msg_erro["msg"][] = "Preencha o campo Cidade";
            $msg_erro['campos'][] = "cidade";
        }
        if (!filter_input(INPUT_POST,"estado")) {
            $msg_erro["msg"][] = "Preencha o campo UF";
            $msg_erro['campos'][] = "estado";
        }

        if (!filter_input(INPUT_POST,"departamento")) {
            $msg_erro["msg"][] = "Preencha o campo Departamento";
            $msg_erro['campos'][] = "departamento";
        }

        $valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",filter_input(INPUT_POST,"cpf_cnpj")));

        if (empty($valida_cpf_cnpj)) {
            $cpf_cnpj = checaCPF(filter_input(INPUT_POST,"cpf_cnpj"),false);
        } else {
            $msg_erro["msg"][]    = $valida_cpf_cnpj;
            $msg_erro['campos'][] = "cpf_cnpj";
        }

        if (!filter_input(INPUT_POST,"mensagem")) {
            $msg_erro["msg"][] = "Preencha o campo Mensagem";
            $msg_erro['campos'][] = "mensagem";
        }
        if (count($msg_erro["msg"]) == 0) {
            
            $departamento   = trim(filter_input(INPUT_POST,"departamento",FILTER_SANITIZE_SPECIAL_CHARS));
            $profissao      = trim(filter_input(INPUT_POST,"profissao",FILTER_SANITIZE_SPECIAL_CHARS));
            $nome           = trim(filter_input(INPUT_POST,"nome",FILTER_SANITIZE_SPECIAL_CHARS));
            $email          = trim(filter_input(INPUT_POST,"email",FILTER_SANITIZE_EMAIL));
            $celular        = trim(filter_input(INPUT_POST,"celular",FILTER_SANITIZE_NUMBER_INT));
            $fone           = trim(filter_input(INPUT_POST,"telefone",FILTER_SANITIZE_NUMBER_INT));
            $cep            = trim(filter_input(INPUT_POST,"cep",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
            $endereco       = trim(filter_input(INPUT_POST,"endereco"));
            $numero         = trim(filter_input(INPUT_POST,"numero"));
            $bairro         = trim(filter_input(INPUT_POST,"bairro",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
            $cidade         = trim(filter_input(INPUT_POST,"cidade",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
            $estado         = trim(filter_input(INPUT_POST,"estado",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
            $mensagem       = trim(filter_input(INPUT_POST,"mensagem",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
           
            $corpoEmail = "
                <p><b>Nome:</b> {$nome}</p>
                <p><b>Email:</b> {$email}</p>
                <p><b>Telefone:</b> {$fone} - <b>Celular:</b> {$celular}</p>
                <p><b>Profissão:</b> {$profissao} </p>
                <p><b>CEP:</b> {$cep} - <b>Endereço:</b> {$endereco}, <b>Número:</b> {$numero}</p>
                <p><b>Bairro:</b> {$bairro} <b>Cidade:</b> {$cidade} - <b>UF:</b> {$estado}</p>
                <p><b>Departamento:</b> {$departamento} </p>
                <p><b>Mensagem:</b> {$mensagem} </p>
            ";


            if (in_array($departamento, ["RH","MARKETING"])) {

                $mailTc = new TcComm('smtp@posvenda');
                $res = $mailTc->sendMail(
                                            $dispara_email[$departamento],
                                            "Fale Conosco via site Viapol",
                                            $corpoEmail,
                                            'noreply@telecontrol.com.br'
                                        );
                if ($res) {
                    $_POST["nome"] = "";
                    $_POST["email"] = "";
                    $_POST["celular"] = "";
                    $_POST["cep"] = "";
                    $_POST["endereco"] = "";
                    $_POST["numero"] = "";
                    $_POST["bairro"] = "";
                    $_POST["cidade"] = "";
                    $_POST["estado"] = "";
                    $_POST["departamento"] = "";
                    $_POST["mensagem"] = "";
                    $_POST["cpf_cnpj"] = "";
                    $_POST["profissao"] = "";
                    $_POST["telefone"] = "";

                    $msg = "Formulário enviado com sucesso!<br> Em breve nossa equipe entrará em contato.";
                } else {
                    $msg_erro["msg"][] = "Erro ao enviar formulário.";
                }
            } else {

                if (empty($cidade)) {
                    $cidade = "null";
                } else {
                    $consumidor_cidade = getCidades($con, $cidade, $estado);
                    $cidade = $consumidor_cidade["cidade_id"];
                }
                $cep     = str_replace(["-", " ", "."],"",$cep);
                
                $sql_busca_origem = "SELECT 
                                        hd_chamado_origem AS origem_id
                                    FROM  tbl_hd_chamado_origem
                                    WHERE fabrica       = {$login_fabrica}
                                          AND descricao = 'FALE CONOSCO'
                                    LIMIT 1";
                $res_busca_origem = pg_query($con, $sql_busca_origem);
                if (pg_num_rows($res_busca_origem) > 0) {

                    $origem_id        = pg_fetch_result($res_busca_origem, 0, 'origem_id');

                    $sql_busca_admin  = "SELECT COUNT(hc.hd_chamado) AS qtde,
                                            a.admin,
                                            a.email
                                        FROM tbl_admin a
                                        JOIN tbl_hd_origem_admin hoa ON hoa.admin    = a.admin 
                                            AND hoa.fabrica           = {$login_fabrica}
                                            AND hoa.hd_chamado_origem = {$origem_id}
                                        LEFT JOIN tbl_hd_chamado hc  ON hc.atendente = a.admin 
                                            AND hc.fabrica = {$login_fabrica}
                                            AND hc.status  = 'Aberto'
                                        WHERE a.fabrica    = {$login_fabrica}
                                            AND a.ativo IS TRUE
                                            AND a.atendente_callcenter IS TRUE
                                        GROUP BY a.admin
                                        ORDER BY qtde ASC
                                            LIMIT 1";

                    $res_busca_admin  = pg_query($con, $sql_busca_admin);

                    if (pg_num_rows($res_busca_admin) > 0) {
                        //$id_atendente    = pg_fetch_result($res_busca_admin, 0, 'admin');
                        $id_atendente    = $admin;
                        $email_atendente = pg_fetch_result($res_busca_admin, 0, 'email');
                        $data_providencia = date('Y-m-d H:i:s', strtotime('+2 hours', strtotime(date("Y-m-d H:i:s"))));

                         $sqlAcao = "SELECT hd_motivo_ligacao
                              FROM tbl_hd_motivo_ligacao
                             WHERE fabrica = {$login_fabrica}
                               AND descricao = 'AGUARDANDO CLASSIFICACAO'
                            LIMIT 1";
                        $resAcao = pg_query($con, $sqlAcao);
                        if (pg_num_rows($resAcao) > 0) {
                            $hd_motivo_ligacao = pg_fetch_result($resAcao, 0, 'hd_motivo_ligacao');
                        }
                        $res = pg_query($con,"BEGIN TRANSACTION");

                        $sqlInsHd = "
                            INSERT INTO tbl_hd_chamado (
                                admin,
                                fabrica,
                                atendente,
                                fabrica_responsavel,
                                status,
                                titulo,
                                data_providencia
                            ) VALUES (
                                $admin,
                                $login_fabrica,
                                $id_atendente,
                                $login_fabrica,
                                'Aberto',
                                'Atendimento Fale Conosco - Site',
                                '$data_providencia'
                            ) RETURNING hd_chamado;
                        ";
                        $resInsHd = pg_query($con,$sqlInsHd);

                        $erro .= pg_last_error($con);
                        $hd_chamado = pg_fetch_result($resInsHd,0,'hd_chamado');

                        $sqlInsEx = "
                            INSERT INTO tbl_hd_chamado_extra (
                                fone,
                                hd_chamado,
                                hd_chamado_origem,
                                origem,
                                consumidor_revenda,
                                nome,
                                email,
                                celular,
                                endereco,
                                numero,
                                bairro,
                                cep,
                                cidade,
                                reclamado,
                                hd_motivo_ligacao,
                                cpf
                            ) VALUES (
                                '$fone',
                                $hd_chamado,
                                $origem_id,
                                'FALE CONOSCO',
                                'C',
                                '".utf8_decode($nome)."',
                                '".utf8_decode($email)."',
                                '$celular',
                                '".utf8_decode($endereco)."',
                                '$numero',
                                '".utf8_decode($bairro)."',
                                '$cep',
                                $cidade,
                                '".utf8_decode($mensagem)."',
                                $hd_motivo_ligacao,
                                '$cpf_cnpj'
                            )
                        ";
                        $resInsEx = pg_query($con,$sqlInsEx);
                        $erro = pg_last_error($con);

                        $sqlInsItem = "
                            INSERT INTO tbl_hd_chamado_item (
                                hd_chamado,
                                comentario,
                                status_item
                            ) VALUES (
                                $hd_chamado,
                                'Abertura de chamado via Fale Conosco - Site {$site}',
                                'Aberto'
                            )
                        ";

                        $resInsItem = pg_query($con,$sqlInsItem);
                        $erro .= pg_last_error($con);
                        if (!empty($erro)) {

                            $res = pg_query($con,"ROLLBACK TRANSACTION");
                            $msg_erro["msg"][] = "Erro ao fazer o registro do atendimento.";

                        } else {
                            $res = pg_query($con,"COMMIT TRANSACTION");

                            $mailTc = new TcComm('smtp@posvenda');
                            $res = $mailTc->sendMail(
                                            $email_atendente,
                                            "Fale Conosco via site Viapol Nº ".$hd_chamado,
                                            $corpoEmail,
                                            'noreply@telecontrol.com.br'
                                        );

                            $_POST["nome"] = "";
                            $_POST["email"] = "";
                            $_POST["celular"] = "";
                            $_POST["cep"] = "";
                            $_POST["endereco"] = "";
                            $_POST["numero"] = "";
                            $_POST["bairro"] = "";
                            $_POST["cidade"] = "";
                            $_POST["estado"] = "";
                            $_POST["telefone"] = "";
                            $_POST["cpf_cnpj"] = "";
                            $_POST["departamento"] = "";
                            $_POST["mensagem"] = "";
                            $_POST["profissao"] = "";
                            
                            $msg = "Atendimento aberto com sucesso!<br> <b>Nº do protocolo:  {$hd_chamado}</b><br> Em breve nossa equipe entrará em contato.";
                        }
                    } else {
                        $msg_erro["msg"][] = "Ocorreu um erro ao gravar o atendimento, favor entrar em contato com a fábrica.";
                    }
                }
            }
        }
}
header('Content-type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

<title>Fale Conosco </title>
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">
<!-- CSS Files -->
<link rel="stylesheet" href="../bootstrap3/css/bootstrap.min.css" />
<link rel="stylesheet" href="<?php echo $URL_BASE;?>css/style.css?v=<?php echo date("YmdHis");?>" />
<link rel="stylesheet" href="<?php echo $URL_BASE;?>css/style-new.css?v=<?php echo date("YmdHis");?>" />

<style type="text/css">
    body{
        margin: 0;
        padding:0;
        background: #ffffff !important;
    }
    .control-label{
        font-weight: 300;
    }
    .txt_normal{
        font-weight: 300;
    }

    .campos_obg{
        color: #d90000;
        font-size: 0.7em;
    }
    label{color: #004fa2;font-weight: normal;padding-top: 8px;}
    select{
        height: 38px;
    font-size: 16px;
    margin-bottom: 10px;
    padding-left:10px;
    width: 100%;
    }
</style>
</head>
<body>

<div class="container">
 


<div class="mindBody"> 
        
    <img class="img960">
    
    <div class="max-width">
        <div class="unit size1of1">
    
            <div class="pageInfo">
                <div class="row">
                    <div class="col-sm-12">
                        <h2>Administração e Fábrica</h2>
                        <p>Rodovia Vito Ardito, nº 6401 - Km 118,5 - Jd.&nbsp;<span class="notranslate">Campo Grande - Caçapava/SP CEP: 12282-535</span>&nbsp;<br><span class="notranslate">Tel.:(12) 3221-3000 Fax: (12) 3653-3409 -&nbsp;<a href="https://www.google.com.br/maps/place/Rod.+Vito+Ardito,+6401+-+Jardim+Campo+Grande,+Ca%C3%A7apava+-+SP/@-23.0718298,-45.6541158,17z/data=!3m1!4b1!4m2!3m1!1s0x94cc56eb5a9441c3:0x91f9e460cd1e4c1d"><span class="entre_linhas">Mapa de Localização</span></a></span></p>
                    </div>
                </div><br><br>
                <div class="row">
                    <div class="col-sm-12">
                        <h2>Escritório Técnico Comercial</h2>
                        <p><span class="notranslate">Rua Apeninos, 1.126 - 3º andar - Paraíso - São Paulo/SP - CEP 04104-021</span>&nbsp;<br><span class="notranslate">Tel.: (11) 2107-3400 Fax: (11) 2107-3429 -&nbsp;<a href="https://www.google.com.br/maps/place/R.+Apeninos,+1126+-+Vila+Mariana,+S%C3%A3o+Paulo+-+SP,+04104-021/@-23.5774576,-46.6395537,17z/data=!3m1!4b1!4m2!3m1!1s0x94ce599ae717a24b:0xf21beeb7a10b8ca5"><span class="entre_linhas">Mapa de Localização</span></a></span></p>
                    </div>
                </div><br><br>
                <div class="row">
                    <div class="col-sm-12">
                        <h2>Filial Nordeste</h2>
                        <p>Rodovia BA 522 - KM 03 - Distrito Industrial</p>
                        <p>Candeias - BA - CEP 43813-300<br><span class="notranslate">Tel./Fax: (71) 3118-2000 -&nbsp;<a href="https://www.google.com.br/maps/place/BA-522,+3,+Candeias+-+BA/@-12.6679228,-38.5413073,16z/data=!4m2!3m1!1s0x71671177aadefef:0x82c59d7b85b6eea6" title="Teste"><span class="entre_linhas">Mapa de Localização</span></a></span></p>
                    </div>
                </div>

                <?php if (count($msg_erro["msg"]) > 0) {?>
                    <div class="alert alert-danger">
                        <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
                    </div>
                <?php }?>

                <?php if (strlen($msg) > 0) {?>
                    <div class="alert alert-success">
                        <h4><?php echo $msg;?></h4>
                    </div>
                <?php }?>
                <div class="msg_valida"></div>
                <br/>
                <form action="" method="post" name="form_ocorrencia" enctype="multipart/form-data" id="contactForm">
                    <div class="row">
                        <div class="col-sm-2"><label>Nome*:</label></div>
                        <div class="col-sm-10"><input name="nome" value="<?php echo (isset($_POST["nome"]) && strlen($_POST["nome"]) > 0) ? $_POST["nome"] : '';?>" type="text"></div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>Email*:</label></div>
                        <div class="col-sm-6"><input name="email" value="<?php echo (isset($_POST["email"]) && strlen($_POST["email"]) > 0) ? $_POST["email"] : '';?>" type="text"></div>
                        <div class="col-sm-1"><label>CPF/CNPJ:</label></div>
                        <div class="col-sm-3"><input name="cpf_cnpj" id="cpf_cnpj" value="<?php echo (isset($_POST["cpf_cnpj"]) && strlen($_POST["cpf_cnpj"]) > 0) ? $_POST["cpf_cnpj"] : '';?>" type="text"></div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>Telefone:</label></div>
                        <div class="col-sm-2"><input name="telefone" class="fone" value="<?php echo (isset($_POST["telefone"]) && strlen($_POST["telefone"]) > 0) ? $_POST["telefone"] : '';?>" type="text"></div>
                        <div class="col-sm-1"><label>Celular*:</label></div>
                        <div class="col-sm-3"><input name="celular" class="celular" value="<?php echo (isset($_POST["celular"]) && strlen($_POST["celular"]) > 0) ? $_POST["celular"] : '';?>" type="text"></div>
                        <div class="col-sm-1"><label>Profissão*:</label></div>
                        <div class="col-sm-3"><input name="profissao" value="<?php echo (isset($_POST["profissao"]) && strlen($_POST["profissao"]) > 0) ? $_POST["profissao"] : '';?>" type="text"></div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>CEP*:</label></div>
                        <div class="col-sm-2"><input name="cep" value="<?php echo (isset($_POST["cep"]) && strlen($_POST["cep"]) > 0) ? $_POST["cep"] : '';?>" class="cep" type="text"></div>
                        <div class="col-sm-1"><label>Endereço*:</label></div>
                        <div class="col-sm-5"><input name="endereco" value="<?php echo (isset($_POST["endereco"]) && strlen($_POST["endereco"]) > 0) ? $_POST["endereco"] : '';?>" class="endereco" type="text"></div>
                        <div class="col-sm-1"><label>Número*:</label></div>
                        <div class="col-sm-1"><input name="numero" value="<?php echo (isset($_POST["numero"]) && strlen($_POST["numero"]) > 0) ? $_POST["numero"] : '';?>" class="numero" type="text"></div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>Bairro*:</label></div>
                        <div class="col-sm-2"><input name="bairro" value="<?php echo (isset($_POST["bairro"]) && strlen($_POST["bairro"]) > 0) ? $_POST["bairro"] : '';?>" class="bairro" type="text"></div>
                        <div class="col-sm-1"><label>Cidade*:</label></div>
                        <div class="col-sm-5"><input name="cidade" value="<?php echo (isset($_POST["cidade"]) && strlen($_POST["cidade"]) > 0) ? $_POST["cidade"] : '';?>" class="cidade" type="text"></div>
                        <div class="col-sm-1"><label>UF*:</label></div>
                        <div class="col-sm-1"><input name="estado" value="<?php echo (isset($_POST["estado"]) && strlen($_POST["estado"]) > 0) ? $_POST["estado"] : '';?>"  class="estado" maxlength="2" type="text"></div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>Departamento*:</label></div>
                        <div class="col-sm-10">

                            <select name="departamento">
                                <option value="">Selecione...</option>
                                <option <?php echo ($_POST["departamento"] == 'SAC')        ? 'selected' : '';?> value="SAC">SAC</option>
                                <option <?php echo ($_POST["departamento"] == 'COMERCIAL')  ? 'selected' : '';?> value="COMERCIAL">Comercial</option>
                                <option <?php echo ($_POST["departamento"] == 'RH')         ? 'selected' : '';?> value="RH">RH</option>
                                <option <?php echo ($_POST["departamento"] == 'DUVIDA')    ? 'selected' : '';?> value="DUVIDA">Duvida Técnica</option>
                                <option <?php echo ($_POST["departamento"] == 'MARKETING')  ? 'selected' : '';?> value="MARKETING">Marketing</option>
                                <option <?php echo ($_POST["departamento"] == 'ESPECIFICACAO')  ? 'selected' : '';?> value="ESPECIFICACAO">Especificação Técnica</option>
                            </select>
                        </div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-2"><label>Mensagem*:</label></div>
                        <div class="col-sm-10"><textarea name="mensagem" rows="5" cols="20"><?php echo (isset($_POST["mensagem"]) && strlen($_POST["mensagem"]) > 0) ? $_POST["mensagem"] : '';?></textarea></div>
                    </div><br>
                    <div class="row">
                        <div class="col-sm-12"><button type="button" class="contactButton newPurpBtn">Enviar</button></div>
                    </div><br>
            </form>
            </div><!--pageInfo-->   
        <br clear="all">
        </div>  
        <br clear="all">
    </div>  
    </div>


</div>
<script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="../institucional/lib/mask/mask.min.js"></script>
<?php if ($site == "logasa") {?>
<?php }?>
<script type="text/javascript">
$(function(){

    $(".fone").mask("(00) 0000-0000");
    $(".celular").mask("(00) 00000-0000");
    $(".cep").mask("00000-000");
    $("#cpf_cnpj").focus(function(){
       $(this).unmask();
       $(this).mask("99999999999999");
    });
       
   $("#cpf_cnpj").blur(function(){
       var el = $(this);
       el.unmask();
       
       if(el.val().length > 11){
           el.mask("99.999.999/9999-99");
       }

       if(el.val().length <= 11){
           el.mask("999.999.999-99");
       }
   });
    $(".contactButton").click(function(){
        
        var msg_campos      = "";
        var departamento    = $("select[name=departamento]").val();
        var nome            = $("input[name=nome]").val();
        var profissao       = $("input[name=profissao]").val();
        var email           = $("input[name=email]").val();
        var telefone        = $("input[name=telefone]").val();
        var celular         = $("input[name=celular]").val();
        var cep             = $("input[name=cep]").val();
        var endereco        = $("input[name=endereco]").val();
        var mensagem        = $("input[name=mensagem]").val();
        var numero          = $("input[name=numero]").val();
        var bairro          = $("input[name=bairro]").val();
        var cidade          = $("input[name=cidade]").val();
        var estado          = $("input[name=estado]").val();

        if (nome == "") {
            msg_campos += "Preencha o campo <b>Nome</b><br>";
        }

        if (email == "") {
            msg_campos += "Preencha o campo <b>E-mail</b><br>";
        }

        if (profissao == "") {
            msg_campos += "Preencha o campo <b>Profissão</b><br>";
        }


        if (cep == "") {
            msg_campos += "Preencha o campo <b>CEP</b><br>";
        }

        if (endereco == "") {
            msg_campos += "Preencha o campo <b>Endereço</b><br>";
        }

        if (numero == "") {
            msg_campos += "Preencha o campo <b>Número</b><br>";
        }

        if (bairro == "") {
            msg_campos += "Preencha o campo <b>Bairro</b><br>";
        }

        if (cidade == "") {
            msg_campos += "Selecione o campo <b>Cidade</b><br>";
        }

        if (estado == "") {
            msg_campos += "Selecione o campo <b>UF</b><br>";
        }

        if (departamento == "") {
            msg_campos += "Selecione o campo <b>Departamento</b><br>";
        }

        if (mensagem == "") {
            msg_campos += "Selecione o campo <b>Mensagem</b><br>";
        }

        $(".msg_valida").html('');

        if (msg_campos != "") {
            $(".msg_valida").html('<div class="alert alert-danger">'+msg_campos+'</div>');
            return false;
        }
        $("form[name=form_ocorrencia]").submit();

    });
    $(".cep").blur(function() {
        busca_cep($(this).val(),"database");
    });
            
});

function busca_cep(cep,method){
    var img = $("<img />", { src: "../../imagens/loading_img.gif", css: { width: "30px", height: "30px" } });
    if (typeof method == "undefined" || method.length == 0) {
        method = "webservice";
        $.ajaxSetup({
            timeout: 10000
        });
    } else {
        $.ajaxSetup({
            timeout: 10000
        });
    }
    $.ajax({
        async: true,
        url: "../ajax_cep.php",
        type: "GET",
        data: {
            cep: cep,
            method: method
        },
        beforeSend: function() {
            $(".estado").prop("disabled","disabled");
            $(".cidade").prop("disabled","disabled");
        },
        success: function(data) {
            results = data.split(";");

            if (results[0] != "ok") {
                alert(results[0]);
            } else {
                $(".estado").data("callback", "selectCidade").data("callback-param", results[3]);
                $(".estado").val(results[4]);
                $(".endereco").val(results[1]);
                $(".bairro").val(results[2]);
                $(".cidade").val(results[3]);
                $(".numero").focus();
                $(".estado").removeAttr("disabled");
                $(".cidade").removeAttr("disabled");

            }
            $.ajaxSetup({
                timeout: 0
            });
        },
        error: function(xhr, status, error) {
            busca_cep(cep, "database");
        }
    });
}
</script>
</body>
</html>
