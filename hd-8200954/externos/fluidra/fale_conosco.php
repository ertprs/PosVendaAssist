<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';
include '../../class/communicator.class.php';


$login_fabrica = 191;
$admin         = 12062;
$atendente     = 12062;
$origem        = 182;//devel
$site        = 'www.fluidra.com.br';

function validaEmail() {
    global $_POST;

    $email = $_POST["email"];

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido");
    }
}

if (isset($_POST["ajax_enviar"])) {

    $regras = array(
        "notEmpty" => array(
            "nome",
            "email",
            "motivo_contato",
            "mensagem"
        ),
        "validaEmail"  => "email"
    );

    $msg_erro = array(
        "msg"    => array(),
        "campos" => array()
    );

    foreach ($regras as $regra => $campo) {
        switch ($regra) {
            case "notEmpty":
                foreach($campo as $input) {
                    $valor = trim($_POST[$input]);

                    if (empty($valor)) {
                        $msg_erro["msg"]["obg"] = utf8_encode("Preencha todos os campos obrigatórios");
                        $msg_erro["campos"][]   = $input;
                    }
                }
                break;

            default:
                $valor = trim($_POST[$campo]);
                if (!empty($valor)) {
                    try {
                        call_user_func($regra);
                    } catch(Exception $e) {
                        $msg_erro["msg"][]    = utf8_encode($e->getMessage());
                        $msg_erro["campos"][] = $campo;
                    }
                }
                break;
        }
    }

    if (count($msg_erro["msg"]) > 0) {
        $retorno = array("erro" => $msg_erro);
    } else {
        $nome            = utf8_decode(trim($_POST["nome"]));
        $email           = trim($_POST["email"]);
        $assunto         = trim($_POST["motivo_contato"]);
        $mensagem        = utf8_decode(trim($_POST["mensagem"]));

        if($assunto != "assistencia"){

             $corpoEmail = "
                <p><b>Nome:</b> {$nome}</p>
                <p><b>Email:</b> {$email}</p>
                <p><b>Assunto:</b> ".strtoupper($assunto)." </p>
                <p><b>Mensagem:</b> {$mensagem} </p>
            ";

            $mailTc = new TcComm('smtp@posvenda');
            $res =  $mailTc->sendMail(
                        'ronald.santos@telecontrol.com.br',
                        "Fale Conosco via site Fluidra",
                        $corpoEmail,
                        'noreply@telecontrol.com.br'
                    );
            if($res){
                $retorno = array("sucesso" => true);
            }
        }else{

            $sql = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} AND lower(descricao) = 'fale conosco'";
            $res = pg_query($con,$sql);
            $hd_chamado_origem = pg_fetch_result($res, 0, 'hd_chamado_origem');

            try {

                pg_query($con, "BEGIN");

                $sql = "INSERT INTO tbl_hd_chamado (
                                            admin,
                                            data,
                                            fabrica_responsavel,
                                            fabrica,
                                            titulo,
                                            status,
                                            atendente
                                        ) VALUES (
                                            $admin,
                                            CURRENT_TIMESTAMP,
                                            $login_fabrica,
                                            $login_fabrica,
                                            'Atendimento Fale Conosco',
                                            'Aberto',
                                            $atendente
                                            
                                        )RETURNING hd_chamado";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao abrir o atendimento #1" . pg_last_error());
                }

                $hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

                $sql = "INSERT INTO tbl_hd_chamado_extra
                            (
                                hd_chamado        ,
                                nome              ,
                                email             ,
                                reclamado, 
                                origem,
                                hd_chamado_origem,
                                consumidor_revenda
                            )
                            VALUES
                            (
                                $hd_chamado,
                                '$nome',
                                '$email',
                                '$mensagem',
                                'Fale Conosco',
                                $hd_chamado_origem,
                                'C'
                            )";
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao abrir o atendimento #2" . pg_last_error());
                }

                $sql = "INSERT INTO tbl_hd_chamado_item (
                    admin,
                    status_item,
                    hd_chamado ,
                    comentario
                ) VALUES (
                    $admin,
                    'Aberto',
                    $hd_chamado,
                    'Abertura de chamado via Fale Conosco - Site $site'
                )";
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao abrir o atendimento #3");
                }

                if (count($msg_erro["msg"]) > 0) {
                    $retorno = array("erro" => $msg_erro);
                }else{
                    
                    pg_query($con, "COMMIT");
                    $retorno = array("sucesso" => true, "hd_chamado" => $hd_chamado);
                }

            } catch (Exception $e) {
                $msg_erro["msg"][] = $e->getMessage();
                $retorno = array("erro" => $msg_erro);
                pg_query($con, "ROLLBACK");
            }
        }

    }

    exit(json_encode($retorno));
}

?>

<!DOCTYPE html />
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
    <meta name="language" content="pt-br" />
    <title>Fale Conosco</title>

    <!-- jQuery -->
    <script type="text/javascript" src="../callcenter/plugins/jquery-1.11.3.min.js" ></script>

    <!-- Bootstrap -->
    <script type="text/javascript" src="../callcenter/plugins/bootstrap/js/bootstrap.min.js" ></script>
    <link rel="stylesheet" type="text/css" href="../callcenter/plugins/bootstrap/css/bootstrap.min.css" />

    <!-- Plugins Adicionais -->
    <script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
    <script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
    <script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
    <script type="text/javascript" src="../../plugins/jquery.form.js"></script>
    <link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css" />


    <script type="text/javascript">
        $(function(){

            var btn;
            $("#form_fale_conosco").ajaxForm({
                complete:function(data){
                    data = $.parseJSON(data.responseText);

                    //data = JSON.parse(data);
                    if (data.erro) {
                        var msg_erro = [];

                        $.each(data.erro.msg, function(key, value) {
                            msg_erro.push(value);
                        });

                        $("#msg_erro").html("<span style='font-weight: bold;' >Desculpe!</span><br />"+msg_erro.join("<br />"));

                        data.erro.campos.forEach(function(input) {
                            $("input[name="+input+"], textarea[name="+input+"], select[name="+input+"]").parents("div.form-group").addClass("has-error");
                        });

                        $("#msg_erro").show();
                    } else {
                        if (typeof data.hd_chamado != "undefined") {
                            $("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.<br />Protocolo: "+data.hd_chamado).show();
                        } else {
                            $("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.").show();
                        }

                        $("input, textarea, select").val("");
                    }

                    $(document).scrollTop(0);
                    $("#enviar").button("reset");
                }
            });

            $("#enviar").click(function() {
                btn      = $(this);

                $("div.input.erro").removeClass("erro");
                $("#msg_erro").html("").hide();
                $("#msg_sucesso").hide();
                $(btn).button("loading");

                $("#form_fale_conosco").submit();
            });
        });

       
    </script>
    <style>
        label {
            color: #8f8f8f;
        }

        .campo_obrigatorio {
            color: darkred;
        }
    </style>
</head>
<body style="background-color: #ffffff;">

<div class="container">
    <h3 style="color:#00adf0;">Para um contato online, utilize o formulário abaixo preenchendo todos os campos:</h3>
    <div id="msg_erro" class="alert alert-danger alert-dismissible" style="display: none;">

    </div>
    <div id="msg_sucesso" class="alert alert-success alert-dismissible" style="display: none;">

    </div>
    <form id="form_fale_conosco" action='fale_conosco.php' enctype="multipart/form-data" method="post">
        <input type="hidden" name="ajax_enviar" value='true' />
        <br />
        <div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            <label for="nome" >Nome<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="nome" name="nome" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6" >
            <label for="email" >Seu E-mail<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="email" name="email" />
        </div>

        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="motivo_contato" >Assunto<span class="campo_obrigatorio"> *</span></label>
            <select class="form-control" id="motivo_contato" name="motivo_contato" >
                <option value='' class="selecione">Selecione</option>
                <option value='assistencia' class="selecione">Assistência Técnica</option>
                <option value='vendas' class="selecione">Vendas</option>
                <option value='rh' class="selecione">RH</option>
                <option value='financeiro' class="selecione">Financeiro</option>
                <option value='compras' class="selecione">Compras</option>
                <option value='marketing' class="selecione">Marketing</option>
                <option value='outros' class="selecione">Outros</option>
            </select>
        </div>

        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="mensagem" >Mensagem<span class="campo_obrigatorio"> *</span></label>
            <textarea class="form-control" name="mensagem" rows="6" ></textarea>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" align="center">
            <button style="background-color: #428bca;color: white;width: 100px;" type="button" id="enviar" class="btn btn-md" data-loading-text="ENVIANDO..." >Enviar</button>
        </div>
    </form>
</div>

