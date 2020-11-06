<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include '../class/ComunicatorMirror.php';
include '../class/sms/sms_v1.class.php';

include 'autentica_admin.php';

$secao = 0;

function geraCodigo()
{
    $codigo = md5(date("YmdHis") . microtime() . rand(100, 1000));

    return substr($codigo, 0, 10);
}

function printJson($json)
{
    header("Content-Type: application/json");
    echo json_encode($json);
    exit;
}

function sendSMS($celular, $codigo)
{
    $curl = curl_init();

    $message = utf8_encode("Seu codigo de validacao: " . $codigo . ", obrigado!");

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://sms.comtele.com.br/api/c864927d-c11c-46fe-8f07-1f42a92dff23/sendmessage?sender=6154311&receivers=" . urlencode($celular) . "&content=" . urlencode($message),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
            "sender" => "6154311",
            "receivers" => $celular,
            "content" => $message,
        ]),
        CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache",
            "Content-Type: application/json",
        ),
    ));

    $enviar = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    return ($info["http_code"] == 200) ? true : false;
}

if (array_key_exists("action", $_POST)) {

    switch ($_POST['action']) {
        case "saveEmail":

            $nome = $_POST['nome'];
            $sobrenome = $_POST['sobrenome'];
            $email = $_POST['email'];

            $body = utf8_encode("Olá <b>" . $nome . "</b><br><br>
                Abaixo está seu código de validação, copie ele e cole no campo solicitado dentro do sistema <br><br>
                <h2>:codigo</h2><br><br><p>Obrigado!</p>");

            $communicatorMirror = new ComunicatorMirror();


            $sql = "SELECT * FROM tbl_email_check where admin = $1";
            $res = pg_query_params($con, $sql, [$login_admin]);
            if (pg_num_rows($res)) {
                $res = pg_fetch_array($res);


                $codigo = $res['codigo'];
                if ($res['email'] != $email) {
                    //gerar novo codigo, enviar email
                    $codigo = geraCodigo();
                }

                $sql = "UPDATE tbl_email_check set nome = $1, sobrenome = $2, email = $3, codigo = $4 WHERE email_check = $5";
                $res = pg_query_params($con, $sql, [$nome, $sobrenome, $email, $codigo, $res['email_check']]);


                if (!pg_last_error($con)) {
                    try {
                        $body = str_replace(":codigo", $codigo, $body);
                        $communicatorMirror->post($email, utf8_encode("Código de verificação Telecontrol - Pós-Vendas"), $body);
                    } catch (\Exception $e) {
                        $responseJson = [
                            "exception" => utf8_encode("Ocorreu um erro ao inserir as informações"),
                            "logError" => $e->getMessage()
                        ];
                        printJson($responseJson);
                    }

                    $responseJson = [
                        "message" => utf8_encode("Atualizado com sucesso")
                    ];
                } else {
                    $responseJson = [
                        "exception" => utf8_encode("Ocorreu um erro ao inserir as informações")
                    ];

                }
                printJson($responseJson);
            } else {
                $codigo = geraCodigo();

                $sql = "INSERT INTO tbl_email_check(admin, nome, sobrenome, email, data_solicitacao, codigo) VALUES($1, $2, $3, $4, $5, $6)";
                $data = new DateTime("now", new DateTimeZone("America/Sao_Paulo"));
                $data = $data->format("Y-m-d H:i:s");
                $res = pg_query_params($con, $sql, [$login_admin, $nome, $sobrenome, $email, $data, $codigo]);

                if (!pg_last_error($con)) {
                    try {
                        $body = str_replace(":codigo", $codigo, $body);
                        $communicatorMirror->post($email, utf8_encode("Código de verificação Telecontrol - Pós-Vendas"), $body);
                    } catch (Exception $e) {

                        $responseJson = [
                            "exception" => utf8_encode("Ocorreu um erro ao inserir as informações"),
                            "logError" => $e->getMessage()
                        ];
                        printJson($responseJson);
                    }

                    $responseJson = [
                        "message" => utf8_encode("Inserido com sucesso")
                    ];
                } else {
                    $responseJson = [
                        "exception" => utf8_encode("Ocorreu um erro ao inserir as informações")
                    ];
                }
                printJson($responseJson);
            }
            break;
        case "checkCode":

            $sql = "SELECT email_check,email FROM tbl_email_check WHERE admin = $1 AND LOWER(codigo)= $2";
            $res = pg_query_params($con, $sql, [$login_admin, strtolower($_POST['code'])]);

            if (pg_num_rows($res) > 0) {
                $res = pg_fetch_array($res);
                $email = $res['email'];

                $data = new DateTime("now", new DateTimeZone("America/Sao_Paulo"));
                $data = $data->format("Y-m-d H:i:s");

                $sql = "UPDATE tbl_email_check SET data_confirmacao = $1 WHERE admin = $2 AND LOWER(codigo) = $3";
                $res = pg_query_params($con, $sql, [$data, $login_admin, strtolower($_POST['code'])]);
                if (!pg_last_error($con)) {
                    $sql = "UPDATE tbl_admin set email = $1 WHERE admin = $2";
                    $res = pg_query_params($con, $sql, [$email, $login_admin]);
                    if (pg_last_error($con)) {
                        $responseJson = [
                            "exception" => utf8_encode("Ocorreu um erro ao validar seu código"),
                            "errorLog" => utf8_encode(pg_last_error($con))
                        ];
                        printJson($responseJson);
                    }

                    $responseJson = [
                        "message" => utf8_encode("Código Validado!")
                    ];
                } else {
                    $responseJson = [
                        "exception" => utf8_encode("Ocorreu um erro ao validar seu código"),
                        "errorLog" => utf8_encode(pg_last_error($con))
                    ];
                }
            } else {
                $responseJson = [
                    "exception" => utf8_encode("Código Inválido")
                ];
            }
            printJson($responseJson);
            break;
        case "sendPhone":
            $celular = $_POST['phone'];

            $sql = "SELECT email_check FROM tbl_email_check WHERE admin = $1";
            $res = pg_query_params($con, $sql, [$login_admin]);
            if (pg_num_rows($res) == 0) {
                $responseJson = [
                    "exception" => utf8_encode("Informe primeiramente seu email")
                ];
                printJson($responseJson);
            }
            $res = pg_fetch_array($res);

            $codigo = geraCodigo();
            $codigo = substr($codigo, 0, 4);


            $sql = "UPDATE tbl_email_check SET celular = $1, codigo_sms = $2 WHERE admin = $3 ";
            $res = pg_query_params($con, $sql, [$celular, $codigo, $login_admin]);
            if (!pg_last_error($con)) {


                $response = sendSMS($celular, $codigo);

                if ($response) {
                    $responseJson = [
                        "message" => utf8_encode("Dados atualizados")
                    ];
                    printJson($responseJson);
                } else {
                    $responseJson = [
                        "exception" => utf8_encode("Ocorreu um erro ao disparar o SMS para seu número")
                    ];
                    printJson($responseJson);
                }

            } else {
                $responseJson = [
                    "exception" => utf8_encode("Ocorreu um erro ao inserir seu telefone"),
                    "errorLog" => utf8_encode(pg_last_error($con))
                ];
            }

            printJson($responseJson);
            break;
        case "checkSmsCode":
            $codigo = $_POST['code'];
            $sql = "SELECT email_check, celular, codigo_sms FROM tbl_email_check WHERE admin = $1";
            $res = pg_query_params($con, $sql, [$login_admin]);
            if (pg_num_rows($res) == 0) {
                $responseJson = [
                    "exception" => utf8_encode("Informe primeiramente seu email")
                ];
                printJson($responseJson);
            }
            $res = pg_fetch_array($res);


            if ($res['celular'] == "") {
                $responseJson = [
                    "exception" => utf8_encode("Informe seu telefone")
                ];
                printJson($responseJson);
            }

            if (strtolower($res['codigo_sms']) != strtolower($codigo)) {
                $responseJson = [
                    "exception" => utf8_encode("Código Inválido")
                ];
                printJson($responseJson);
            }

            $data = new DateTime("now", new DateTimeZone("America/Sao_Paulo"));
            $data = $data->format("Y-m-d H:i:s");

            $sql = "UPDATE tbl_email_check SET data_confirmacao_sms = $1 WHERE email_check = $2 AND admin = $3";
            $res = pg_query_params($con, $sql, [$data, $res['email_check'], $login_admin]);
            if (!pg_last_error($con)) {
                $responseJson = [
                    "message" => utf8_encode("Dados atualizados obrigado!")
                ];
                printJson($responseJson);
            } else {
                $responseJson = [
                    "exception" => utf8_encode("Ocorreu um erro ao validar seu código")
                ];
                printJson($responseJson);
            }
            break;
        case "resendSmsCode":
            $sql = "SELECT email_check, celular, codigo_sms FROM tbl_email_check WHERE admin = $1";
            $res = pg_query_params($con, $sql, [$login_admin]);
            if (pg_num_rows($res) == 0) {
                $responseJson = [
                    "exception" => utf8_encode("Informe primeiramente seu email")
                ];
                printJson($responseJson);
            }
            $res = pg_fetch_array($res);

            if ($res['celular'] == "") {
                $responseJson = [
                    "exception" => utf8_encode("Informe seu telefone")
                ];
                printJson($responseJson);
            }

            $celular = $res['celular'];
            $codigo = $res['codigo_sms'];

            $response = sendSMS($celular, $codigo);
            if ($response) {
                $responseJson = [
                    "message" => utf8_encode("SMS Reenviado")
                ];
                printJson($responseJson);
            } else {
                $responseJson = [
                    "exception" => utf8_encode("Ocorreu um erro ao disparar o SMS para seu número")
                ];
                printJson($responseJson);
            }
            break;
    }
}


$sql = "SELECT nome_completo, email, fone FROM tbl_admin WHERE admin = $1";
$res = pg_query_params($con, $sql, [$login_admin]);
$res = pg_fetch_array($res);
if (!pg_last_error($con)) {
    $nomeCompleto = $res['nome_completo'];
    $email = $res['email'];
    $fone = $res['fone'];

    $nomeCompleto = explode(" ", $nomeCompleto);
    $nome = $nomeCompleto[0];
    unset($nomeCompleto[0]);
    $sobrenome = implode(" ", $nomeCompleto);

} else {
    echo "Usuário não encontrado";
    exit;
}

$sql = "SELECT celular,email_check, data_confirmacao, data_confirmacao, data_confirmacao_sms FROM tbl_email_check where admin = $1";
$res = pg_query_params($con, $sql, [$login_admin]);

if (pg_num_rows($res) > 0) {
    $res = pg_fetch_array($res);

    if ($res['data_confirmacao'] != "" && $res['data_confirmacao_sms'] != "") {
        $secao = 5;
    } elseif ($res['celular'] != "") {
        $secao = 4;
        $celular = $res['celular'];
    } elseif ($res['data_confirmacao'] != "") {
        $secao = 3;
    } else {
        $secao = 2;
    }
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Confirmação de Email</title>
    <meta http-equiv="content-Type" content="text/html; charset=iso-8859-1">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <style>
        .breadcrumb {
            padding: 0px;
            background: #D4D4D4;
            list-style: none;
            overflow: hidden;
            margin-top: 20px;
        }

        .breadcrumb > li + li:before {
            padding: 0;
        }

        .breadcrumb li {
            float: left;
        }

        .breadcrumb li.active a {
            background: brown; /* fallback color */
            background: #ffc107;
        }

        .breadcrumb li.completed a {
            background: brown; /* fallback color */
            background: hsla(153, 57%, 51%, 1);
        }

        .breadcrumb li.active a:after {
            border-left: 30px solid #ffc107;
        }

        .breadcrumb li.completed a:after {
            border-left: 30px solid hsla(153, 57%, 51%, 1);
        }

        .breadcrumb li a {
            color: white;
            text-decoration: none;
            padding: 10px 0 10px 45px;
            position: relative;
            display: block;
            float: left;
        }

        .breadcrumb li a:after {
            content: " ";
            display: block;
            width: 0;
            height: 0;
            border-top: 50px solid transparent; /* Go big on the size, and let overflow hide */
            border-bottom: 50px solid transparent;
            border-left: 30px solid hsla(0, 0%, 83%, 1);
            position: absolute;
            top: 50%;
            margin-top: -50px;
            left: 100%;
            z-index: 2;
        }

        .breadcrumb li a:before {
            content: " ";
            display: block;
            width: 0;
            height: 0;
            border-top: 50px solid transparent; /* Go big on the size, and let overflow hide */
            border-bottom: 50px solid transparent;
            border-left: 30px solid white;
            position: absolute;
            top: 50%;
            margin-top: -50px;
            margin-left: 1px;
            left: 100%;
            z-index: 1;
        }

        .breadcrumb li:first-child a {
            padding-left: 15px;
        }

        .breadcrumb li a:hover {
            background: #ffc107;
        }

        .breadcrumb li a:hover:after {
            border-left-color: #ffc107 !important;
        }
    </style>
</head>
<body>


<div class="container-fluid" id="id_tc_container">
    <div class="row" style="margin-top: 10px">
        <div class="col-md-12 text-center">
            <img style="width: 200px;" src="../image/logo_telecontrol.png" class="img-fluid" alt="Marca Telecontrol">
            <hr>
        </div>
        <div class="col-md-12 text-center">
            <h4>Atualização de Informações</h4>
            <p>Como regra de segurança, estamos recadastrando os emails dos Admins do sistema Telecontrol, e gostaríamos de contar com sua colaboração</p>
            <small>Siga as instruções para finalizar o processo</small>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <ul class="breadcrumb">
                <li class="btn-change-section active"><a id="btn-secao1" href="javascript:void(0);">Nome e Email</a></li>
                <li class="btn-change-section"><a id="btn-secao2" href="javascript:void(0);">Validação do Email</a></li>
                <li class="btn-change-section"><a id="btn-secao3" href="javascript:void(0);">Telefone</a></li>
                <li class="btn-change-section"><a id="btn-secao4" href="javascript:void(0);">Validação do Telefone</a></li>
                <li class="btn-change-section"><a id="btn-secao5" href="javascript:void(0);">Finalização</a></li>
            </ul>
        </div>

    </div>


    <div class="row">
        <div class="col-md-12">

            <div class="secao-ativa" id="secao-1">
                <small>Caso seu nome ou email esteja incorreto, por favor colocar as informações corrigidas, no próximo passo vamos validar seu email.</small>
                <hr>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="nome">Nome</label>
                            <input type="text" class="form-control" id="nome" aria-describedby="Nome" placeholder="Nome" value="<?= $nome ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="sobrenome">Sobrenome</label>
                            <input type="text" class="form-control" id="sobrenome" aria-describedby="Sobrenome" placeholder="Sobrenome" value="<?= $sobrenome ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="sobrenome">Endereço de Email</label>
                            <input type="text" class="form-control" id="email" aria-describedby="Endereço" placeholder="Endereço" value="<?= $email ?>">
                        </div>
                        <div class="col-md-6 text-right">
                            <button style="margin-top: 32px" type="button" id="btn-send-email" class="btn btn-primary">Enviar <i class="fa fa-arrow-circle-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="secao" id="secao-2" style="display: none">
                <small>Enviamos seu código no email <b id="email-informed"></b>, copie ele e cole no campo abaixo</small>
                <hr>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4">
                            <label for="nome">Código</label>
                            <input type="text" class="form-control text-center" id="codigo" aria-describedby="Código" placeholder="_ _ _ _ _ _ _ _ _ _" value="">
                        </div>
                        <div class="col-md-6 text-left">
                            <button style="margin-top: 32px" type="button" id="btn-back-section1" class="btn btn-default"><i class="fa fa-arrow-circle-left"></i> Informar outro email</button>
                        </div>
                        <div class="col-md-6 text-right">
                            <button style="margin-top: 32px" type="button" id="btn-check-code" class="btn btn-primary">Validar <i class="fa fa-arrow-circle-right"></i></button>
                        </div>

                    </div>
                </div>
            </div>

            <div class="secao" id="secao-3" style="display: none">
                <small>Agora vamos validar seu telefone, enviaremos posteriormente um código via SMS para validação</small>
                <hr>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4">
                            <label for="nome">Telefone</label>
                            <input type="text" class="form-control text-center" id="telefone" aria-describedby="Telefone" placeholder="" value="<?= $celular ?>">
                        </div>
                        <div class="col-md-12 text-right">
                            <button style="margin-top: 32px" type="button" id="btn-send-phone" class="btn btn-primary">Enviar <i class="fa fa-arrow-circle-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="secao" id="secao-4" style="display: none">
                <small>Enviamos um código via SMS, digite ele no campo abaixo, <br><b>Pode acontecer do SMS demorar para chegar, nesse caso pode fechar esse modal e amanhã retomaremos o processo, guarde seu código assim que ele chegar.</b></small>
                <hr>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4">
                            <label for="nome">Código</label>
                            <input type="text" class="form-control text-center" id="codigo-sms" aria-describedby="Código" placeholder="_ _ _ _" value="">
                        </div>
                        <div class="col-md-6 text-left">
                            <button style="margin-top: 32px" type="button" id="btn-back-section3" class="btn btn-default"><i class="fa fa-arrow-circle-left"></i> Informar outro número</button>
                        </div>
                        <div class="col-md-4 text-right">
                            <button style="float:right;margin-top: 32px" type="button" id="btn-resend-code" class="btn btn-secondary"><i class="fa fa-phone"></i> Reenviar código via SMS<b id="phone-sended"></b></button>
                        </div>
                        <div class="col-md-2 text-right">
                            <button style="margin-top: 32px" type="button" id="btn-check-sms" class="btn btn-primary">Validar <i class="fa fa-arrow-circle-right"></i></button>
                        </div>

                    </div>
                </div>
            </div>

            <form class="secao" id="secao-5" style="display: none">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h3>Obrigado <b id="nome-usuario"><?= $nome ?></b>, suas informações foram atualizadas com sucesso!</h3>
                    </div>
                    <div class="col-md-12 text-center">
                        <button style="margin-top: 32px" type="button" id="btn-close-shadow" class="btn btn-success">Fechar <i class="fa fa-times"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js"></script>
<script></script>
<script>
    var secao = "<?=$secao?>";

    $(function () {
        $("#telefone").mask("(99) 99999-9999");

        $("#btn-send-email").click(function () {
            $("#btn-send-email").find("i").removeClass("fa-arrow-circle-right");
            $("#btn-send-email").find("i").addClass("fa-spin");
            $("#btn-send-email").find("i").addClass("fa-refresh");

            var nome = $("#nome").val();
            var sobrenome = $("#sobrenome").val();
            var email = $("#email").val();

            $.ajax("#", {
                method: "POST",
                data: {
                    action: "saveEmail",
                    nome: nome,
                    sobrenome: sobrenome,
                    email: email
                }
            }).done(function (response) {
                $("#btn-send-email").find("i").addClass("fa-arrow-circle-right");
                $("#btn-send-email").find("i").removeClass("fa-spin");
                $("#btn-send-email").find("i").removeClass("fa-refresh");

                $("#email-informed").val(email);

                if (response.exception == undefined) {
                    goToSecao(2, function () {
                        $("#email-informed").html($("#email").val());
                    });
                } else {
                    alert(response.exception);
                }
            })
        });

        $("#btn-check-code").click(function () {
            $("#btn-check-code").find("i").removeClass("fa-arrow-circle-right");
            $("#btn-check-code").find("i").addClass("fa-spin");
            $("#btn-check-code").find("i").addClass("fa-refresh");

            var code = $("#codigo").val();

            $.ajax("#", {
                method: "POST",
                data: {
                    action: "checkCode",
                    code: code
                }
            }).done(function (response) {
                $("#btn-check-code").find("i").addClass("fa-arrow-circle-right");
                $("#btn-check-code").find("i").removeClass("fa-spin");
                $("#btn-check-code").find("i").removeClass("fa-refresh");

                if (response.exception == undefined) {

                    goToSecao(3);

                } else {
                    alert(response.exception);
                }
            });
        });

        $("#btn-send-phone").click(function () {
            $("#btn-send-phone").find("i").removeClass("fa-arrow-circle-right");
            $("#btn-send-phone").find("i").addClass("fa-spin");
            $("#btn-send-phone").find("i").addClass("fa-refresh");

            var phone = $("#telefone").val();
            if (phone == "") {
                alert("Informe um email");
                return false;
            }

            $.ajax("#", {
                method: "POST",
                data: {
                    action: "sendPhone",
                    phone: phone
                }
            }).done(function (response) {
                $("#btn-send-phone").find("i").addClass("fa-arrow-circle-right");
                $("#btn-send-phone").find("i").removeClass("fa-spin");
                $("#btn-send-phone").find("i").removeClass("fa-refresh");

                if (response.exception == undefined) {
                    goToSecao(4);
                } else {
                    alert(response.exception);
                }
            });
        });

        $("#btn-check-sms").click(function () {
            $("#btn-check-sms").find("i").removeClass("fa-arrow-circle-right");
            $("#btn-check-sms").find("i").addClass("fa-spin");
            $("#btn-check-sms").find("i").addClass("fa-refresh");

            var code = $("#codigo-sms").val();

            $.ajax("#", {
                method: "POST",
                data: {
                    action: "checkSmsCode",
                    code: code
                }
            }).done(function (response) {
                $("#btn-check-sms").find("i").addClass("fa-arrow-circle-right");
                $("#btn-check-sms").find("i").removeClass("fa-spin");
                $("#btn-check-sms").find("i").removeClass("fa-refresh");

                if (response.exception == undefined) {
                    goToSecao(5, function () {
                        $("#nome-usuario").val($("#nome").val());
                    });
                } else {
                    alert(response.exception);
                }
            });
        });

        $("#btn-close-shadow").click(function () {
            window.parent.Shadowbox.close();
        });

        $("#btn-resend-code").click(function () {
            $("#btn-resend-code").find("i").removeClass("fa-phone");
            $("#btn-resend-code").find("i").addClass("fa-spin");
            $("#btn-resend-code").find("i").addClass("fa-refresh");

            $.ajax("#", {
                method: "POST",
                data: {
                    action: "resendSmsCode"
                }
            }).done(function (response) {
                $("#btn-resend-code").find("i").addClass("fa-phone");
                $("#btn-resend-code").find("i").removeClass("fa-spin");
                $("#btn-resend-code").find("i").removeClass("fa-refresh");

                if (response.exception == undefined) {
                    alert(response.message);
                } else {
                    alert(response.exception);
                }
            });
        });

        $("#btn-back-section1").click(function () {
            goToSecao(1);
        });

        $("#btn-back-section3").click(function () {
            goToSecao(3);
        });

        if (secao != "0") {
            if (secao == 2) {
                goToSecao(secao, function () {
                    $("#email-informed").html($("#email").val());
                });
            } else {
                goToSecao(secao);
            }
        }
    });

    function goToSecao(secao, callback) {
        $(".secao-ativa").slideUp(1000, function () {
            $(".secao-ativa").removeClass("secao-ativa")
            var li = $("#btn-secao" + secao).parent("li");
            $(".btn-change-section").removeClass("active")
            $(".btn-change-section").removeClass("completed")
            $(li).prevAll("li").addClass("completed")
            $(li).addClass("active");

            if (callback != undefined && callback != null) {
                callback();
            }

            $("#secao-" + secao).slideDown(500);
            $("#secao-" + secao).addClass("secao-ativa");
        });
    }
</script>
</body>
</html>
