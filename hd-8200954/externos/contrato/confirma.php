<?php

include '../../admin/dbconfig.php';
include '../../admin/includes/dbconnect-inc.php';
include '../../admin/funcoes.php';
include '../../fn_traducao.php';
use GestaoContrato\Contrato;
use GestaoContrato\Comunicacao;

function geraDataTimeNormal($data) {
    list($ano, $mes, $vetor) = explode("-", $data);
    $resto = explode(" ", $vetor);
    $dia = $resto[0];
    return $dia."/".$mes."/".$ano;
}

function geraDataNormal($data) {
    list($ano, $mes, $dia) = explode("-", $data);
    return $dia."/".$mes."/".$ano;
}

$tipo       = trim($_GET['tipo']);
$token      = $_GET['h'];
$ip_cliente = $_SERVER["REMOTE_ADDR"];

if (strlen($token) == 0 || strlen($tipo) == 0) {
    $msg_erro = "Proposta não encontrada";
}

$logos = [];
$logos["190"] = "logos/nilfisk_logo.png";


if (strlen($msg_erro) == 0) {
    $token = base64_decode($token);
    list($contrato, $fabrica, $email_cliente, $expira_token) = explode("|", $token);

    if (strtotime(date("Y-m-d H:i:s")) > strtotime($expira_token)) {
        $msg_erro = "O prazo para Aprovação ou Reprovação dessa proposta está expirado, entre em contato  com o representante.";
    }

    if (strlen($msg_erro) == 0) {
        $objComunicacao = new Comunicacao($fabrica, $con,$fabrica);
        $objContrato   = new Contrato($fabrica, $con);
        $dadosContrato = $objContrato->get($contrato)[0];
        $aprovacao_cliente["cliente_email"]     = $email_cliente;
        $aprovacao_cliente["cliente_ip"]        = $ip_cliente;
        $aprovacao_cliente["data_reprovacao"]   = date("Y-m-d H:i:s");

        //verifica se ja nao foi aprovado ou reprovado
        if (strlen($dadosContrato["data_aprovacao_cliente"]) > 0) {
            $msg_erro = "Proposta já foi aprovada em: ".geraDataTimeNormal($dadosContrato["data_aprovacao_cliente"]);
        }

        if (strlen($dadosContrato["data_cancelado"]) > 0 && strlen($msg_erro) == 0) {
            $msg_erro = "Proposta foi reprovada em: ".geraDataNormal($dadosContrato["data_cancelado"]);
        }

        //aprova ou reprova
        if (strlen($msg_erro) == 0) {
            if ($tipo == "aprova") {

                $retorno = $objContrato->aprova_reprova_proposta_cliente($contrato, "Aprovar",$aprovacao_cliente);
                if ($retorno) {
                    $msg_success = "Proposta Aprovada com sucesso";
                    $objComunicacao->enviaNotificacaoPropostaAprovadaReprovadaPorCliente($dadosContrato, "Aprovada");

                } else {
                    $msg_erro = "Houve um erro ao Aprovar a Proposta, entre em contato com o Representante";
                }

            } else {
            
                $retorno = $objContrato->aprova_reprova_proposta_cliente($contrato, "Reprovar",$aprovacao_cliente);
                if ($retorno) {
                    $msg_success = "Proposta Reprovada com sucesso";
                    $objComunicacao->enviaNotificacaoPropostaAprovadaReprovadaPorCliente($dadosContrato, "Reprovada");
               } else {
                    $msg_erro = "Houve um erro ao Reprovar a Proposta, entre em contato com o Representante";
                }
                
            }

        }
    }
}
header('Content-type: text/html; charset=iso-8859-1');

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="iso-8859-1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Aprova ou Reprova Proposta de  Contrato</title>
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

    <table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
        <tr>
            <th style="text-align: center;"><img width="120" src="../../<?php echo $logos[$fabrica];?>" alt=""></th>
            <th class="tar" colspan="4">
                <div style="text-align: center;width: 200px;border: solid 1px #eee;float: right;">
                    <h4>Nº da Proposta</h4>
                    <h1><?=$contrato?></h1>
                </div>
            </th>
        </tr>
    </table>
    <table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;margin-top: 5px;" >
        <tr>
            <td>
                <?php if (strlen($msg_erro) > 0) {?>
                    <div class="alert alert-danger tac">
                        <h3><?php echo $msg_erro;?></h3>
                    </div>
                <?php }?>
                <?php if (strlen($msg_erro) == 0 && strlen($msg_success) > 0) {?>
                    <div class="alert alert-success tac">
                        <h3><?php echo $msg_success;?></h3>
                    </div>
                <?php }?>
            </td>
        </tr>
    </table>
</div>
</body>
</html>