<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../plugins/fileuploader/TdocsMirror.php';
include_once '../../class/communicator.class.php';

if ($_serverEnvironment == 'development'){
    $URL_BASE = "http://novodevel.telecontrol.com.br/~felipe/chamados/hd-implantacao-syllent/externos/syllent/";
}else{
    $URL_BASE = "https://posvenda.telecontrol.com.br/assist/externos/syllent/";
}


$login_fabrica = 195;

function getCidades($con, $consumidor_cidade, $consumidor_estado) {
    $cidade = utf8_decode($consumidor_cidade);
    $sql = "
        SELECT  tbl_cidade.cidade,
                tbl_cidade.nome AS cidade_nome
        FROM    tbl_cidade
        WHERE   tbl_cidade.cod_ibge IS NOT NULL
        AND     tbl_cidade.estado = '$consumidor_estado'
        AND     UPPER(fn_retira_especiais(tbl_cidade.nome)) = UPPER(fn_retira_especiais('{$cidade}'))
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
                            "SAC" => "felipe.marttos@telecontrol.com.br",
                            "COMERCIAL" => "felipe.marttos@telecontrol.com.br",
                            "RH" => "felipe.marttos@telecontrol.com.br",
                            "DUVIDA" => "felipe.marttos@telecontrol.com.br",
                            "MARKETING" => "felipe.marttos@telecontrol.com.br",
                        ]; 

        if (!filter_input(INPUT_POST,"nome")) {
            $msg_erro["msg"][] = "Preencha o campo Nome";
            $msg_erro['campos'][] = "nome";
        }

        if (!filter_input(INPUT_POST,"email",FILTER_VALIDATE_EMAIL)) {
            $msg_erro["msg"][] = "O E-mail é inválido";
            $msg_erro['campos'][] = "email";
        }

        if (!filter_input(INPUT_POST,"destino")) {
            $msg_erro["msg"][] = "Preencha o campo Departamento";
            $msg_erro['campos'][] = "destino";
        }

        if (!filter_input(INPUT_POST,"mensagem")) {
            $msg_erro["msg"][] = "Preencha o campo Mensagem";
            $msg_erro['campos'][] = "mensagem";
        }
        if (count($msg_erro["msg"]) == 0) {
            
            $destino        = trim(filter_input(INPUT_POST,"destino",FILTER_SANITIZE_SPECIAL_CHARS));
            $nome           = trim(filter_input(INPUT_POST,"nome",FILTER_SANITIZE_SPECIAL_CHARS));
            $email          = trim(filter_input(INPUT_POST,"email",FILTER_SANITIZE_EMAIL));
            $fone           = trim(filter_input(INPUT_POST,"telefone",FILTER_FLAG_STRIP_LOW));
            $cidade         = trim(filter_input(INPUT_POST,"cidade",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
            $estado         = trim(filter_input(INPUT_POST,"estado",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
            $mensagem       = trim(filter_input(INPUT_POST,"mensagem",FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
           
            $corpoEmail = "
                <p><b>Nome:</b> {$nome}</p>
                <p><b>Email:</b> {$email}</p>
                <p><b>Telefone:</b> {$fone} </p>
                <p><b>Cidade:</b> {$cidade} - <b>UF:</b> {$estado}</p>
                <p><b>Destino:</b> {$destino} </p>
                <p><b>Mensagem:</b> {$mensagem} </p>
            ";


            if (in_array($destino, ["RH","MARKETING","SAC","COMERCIAL","DUVIDA"])) {

                $mailTc = new TcComm('smtp@posvenda');
                $res = $mailTc->sendMail(
                                            $dispara_email[$destino],
                                            "Fale Conosco via site Syllent",
                                            $corpoEmail,
                                            'noreply@telecontrol.com.br'
                                        );
                if ($res) {
                    $_POST["nome"] = "";
                    $_POST["email"] = "";
                    $_POST["cidade"] = "";
                    $_POST["estado"] = "";
                    $_POST["destino"] = "";
                    $_POST["mensagem"] = "";
                    $_POST["telefone"] = "";

                    $msg = "Formulário enviado com sucesso!<br> Em breve nossa equipe entrará em contato.";
                } else {
                    $msg_erro["msg"][] = "Erro ao enviar formulário.";
                }
            } else {
                $semCidade = false;
                if (strlen($cidade) == 0) {
                    $semCidade = true;
                } else {
                    $consumidor_cidade = getCidades($con, $cidade, $estado);
                    $cidade = $consumidor_cidade["cidade_id"];
                }

                $cep     = str_replace(["-", " ", "."],"",$cep);
                

                $cp_hd_classificacao = '';
                $hd_classificacao = '';
                $hd_chamado_origem = null;
                $sqlOrigem = "SELECT hd_chamado_origem
                                FROM tbl_hd_chamado_origem 
                               WHERE  ativo IS TRUE
                                 AND UPPER(descricao) = 'FALE CONOSCO'
                                 AND fabrica = {$login_fabrica}";
                $resOrigem = pg_query($con, $sqlOrigem);
                if (pg_num_rows($resOrigem) > 0) {
                    $hd_chamado_origem = pg_fetch_result($resOrigem, 0, 'hd_chamado_origem');
                }
                $sqlHdO = "SELECT tbl_hd_origem_admin.admin 
                          FROM tbl_hd_origem_admin 
                         WHERE hd_chamado_origem = {$hd_chamado_origem} 
                           AND fabrica = {$login_fabrica}";
                $resHdO = pg_query($con, $sqlHdO);
                $xadmin = null;
                if (pg_num_rows($resHdO) > 0) {
                    $xadmin = pg_fetch_result($resHdO, 0, 'admin');
                }

                $sql_classificacao = "SELECT hd_classificacao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND UPPER(descricao) = 'FALE CONOSCO'";
                $res_classificacao = pg_query($con,$sql_classificacao);
                if (pg_num_rows($res_classificacao) > 0) {
                    $cp_hd_classificacao = ',hd_classificacao';
                    $hd_classificacao = ','.pg_fetch_result($res_classificacao, 0, 'hd_classificacao');
                }
                $campoCidade = "cidade,";
                $valorCidade = "'$cidade',";
                if ($semCidade) {
                    $campoCidade = "";
                    $valorCidade = "";
                }
                $res = pg_query($con,"BEGIN TRANSACTION");

                $sqlInsHd = "
                        INSERT INTO tbl_hd_chamado (
                            fabrica,
                            atendente,
                            admin,
                            fabrica_responsavel,
                            status,
                            titulo,
                            categoria
                            {$cp_hd_classificacao}
                        ) VALUES (
                            $login_fabrica,
                            $xadmin,
                            $xadmin,
                            $login_fabrica,
                            'Aberto',
                            'Atendimento Fale Conosco - Site {$site}',
                            'reclamacao_produto'
                            {$hd_classificacao}
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
                        {$campoCidade}
                        reclamado
                    ) VALUES (
                        '$fone',
                        $hd_chamado,
                        $hd_chamado_origem,
                        'FALE CONOSCO',
                        'C',
                        '".utf8_decode($nome)."',
                        '".utf8_decode($email)."',
                        {$valorCidade}
                        '".utf8_decode($mensagem)."'
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
                    $_POST["cidade"] = "";
                    $_POST["estado"] = "";
                    $_POST["telefone"] = "";
                    $_POST["destino"] = "";
                    $_POST["mensagem"] = "";
                    
                    $msg = "Atendimento aberto com sucesso!<br> <b>Nº do protocolo:  {$hd_chamado}</b><br> Em breve nossa equipe entrará em contato.";
                }
            }
            /*} else {
                $msg_erro["msg"][] = "Ocorreu um erro ao gravar o atendimento, favor entrar em contato com a fábrica.";
            }*/
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
    label{color: #24397a;font-weight: normal;padding-top: 8px;}
    select{
        height: 38px;
    font-size: 16px;
    margin-bottom: 10px;
    padding-left:10px;
    width: 100%;
    }
    .btn-info {
        color: #fff;
        background-color: #24397a;
        border-color: #77b8de;
    }

    .btn-info:hover {
        color: #24397a;
        background-color: #77b8de;
        border-color: #24397a;
    }
</style>
</head>
<body>

<div class="container">
    <h3 class="tac">Fale Conosco</h3>
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
            <div class="col-sm-6">
                <label>Nome*:</label>
                <input name="nome" class="form-control" value="<?php echo (isset($_POST["nome"]) && strlen($_POST["nome"]) > 0) ? $_POST["nome"] : '';?>" type="text">
            </div>
            <div class="col-sm-6">
                <label>Email*:</label>
                <input name="email" class="form-control" value="<?php echo (isset($_POST["email"]) && strlen($_POST["email"]) > 0) ? $_POST["email"] : '';?>" type="text">
            </div>
        </div><br>
        <div class="row">
            <div class="col-sm-2">
                <label>Telefone:</label>
                <input name="telefone" class="form-control fone" value="<?php echo (isset($_POST["telefone"]) && strlen($_POST["telefone"]) > 0) ? $_POST["telefone"] : '';?>" type="text">
            </div>
            <div class="col-sm-9">
                <label>Cidade:</label>
                <input name="cidade" class="form-control" value="<?php echo (isset($_POST["cidade"]) && strlen($_POST["cidade"]) > 0) ? $_POST["cidade"] : '';?>" class="cidade" type="text">
            </div>
            <div class="col-sm-1">
                <label>UF:</label>
                <input name="estado" class="form-control" value="<?php echo (isset($_POST["estado"]) && strlen($_POST["estado"]) > 0) ? $_POST["estado"] : '';?>"  class="estado" maxlength="2" type="text">
            </div>
        </div><br>
        <div class="row">
            <div class="col-sm-12">
                <label>Destino*:</label>
                <select name="destino" class="form-control">
                    <option value="">Selecione...</option>
                    <option <?php echo ($_POST["destino"] == 'SAC')             ? 'selected' : '';?> value="SAC">SAC</option>
                    <option <?php echo ($_POST["destino"] == 'COMERCIAL')       ? 'selected' : '';?> value="COMERCIAL">Comercial</option>
                    <option <?php echo ($_POST["destino"] == 'RH')              ? 'selected' : '';?> value="RH">RH</option>
                    <option <?php echo ($_POST["destino"] == 'DUVIDA')          ? 'selected' : '';?> value="DUVIDA">Duvida Técnica</option>
                    <option <?php echo ($_POST["destino"] == 'MARKETING')       ? 'selected' : '';?> value="MARKETING">Marketing</option>
                    <option <?php echo ($_POST["destino"] == 'ASSISTENCIA')     ? 'selected' : '';?> value="ASSISTENCIA">Assistência Técnica</option>
                </select>
            </div>
        </div><br>
        <div class="row">
            <div class="col-sm-12">
                <label>Mensagem*:</label>
                <textarea name="mensagem" rows="5" class="form-control" cols="20"><?php echo (isset($_POST["mensagem"]) && strlen($_POST["mensagem"]) > 0) ? $_POST["mensagem"] : '';?></textarea>
            </div>
        </div><br>
        <div class="row">
            <div class="col-sm-12"><button type="button" class="contactButton btn btn-info">Enviar</button></div>
        </div><br>
    </form>

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
        var destino    = $("select[name=destino]").val();
        var nome            = $("input[name=nome]").val();
        var email           = $("input[name=email]").val();
        var telefone        = $("input[name=telefone]").val();
        var mensagem        = $("textarea[name=mensagem]").val();
        var cidade          = $("input[name=cidade]").val();
        var estado          = $("input[name=estado]").val();

        if (nome == "") {
            msg_campos += "Preencha o campo <b>Nome</b><br>";
        }

        if (email == "") {
            msg_campos += "Preencha o campo <b>E-mail</b><br>";
        }

        if (destino == "") {
            msg_campos += "Selecione o campo <b>Destino</b><br>";
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
