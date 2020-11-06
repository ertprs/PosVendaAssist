<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
$tDocs       = new TDocs($con, $login_fabrica);


if ($_GET["id_visita"]) {
    $roteiro_posto = $_GET["id_visita"];

if (!empty($roteiro_posto)) {
    $sqlID = "SELECT external_id FROM tbl_roteiro_posto WHERE roteiro_posto = $roteiro_posto ";
    $resID = pg_query($con, $sqlID);
    if (pg_num_rows($resID) > 0) {
        $external_id = pg_fetch_result($resID, 0, 'external_id');
        $sqlAnexo = "SELECT tdocs FROM tbl_tdocs WHERE referencia_id = $external_id AND fabrica = $login_fabrica";
        $resAnexo = pg_query($con, $sqlAnexo);
        if (pg_num_rows($resAnexo) > 0) {
            $sqlUpAnexo = "UPDATE tbl_tdocs SET referencia_id = $roteiro_posto WHERE referencia_id = $external_id AND fabrica = $login_fabrica";
            $resUpAnexo = pg_query($con, $sqlUpAnexo);
        }
        $tempUniqueId = $roteiro_posto;
        $anexoNoHash = null;    
    } else {
        $tempUniqueId = $roteiro_posto;
        $anexoNoHash = null;
    }
} else if (strlen($_POST["anexo_chave"]) > 0) {
    $tempUniqueId = $_POST["anexo_chave"];
    $anexoNoHash = true;
} else {
    $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
    $anexoNoHash = true;
}

function getResultado($roteiro_posto, $temVisita = null) {
    global $login_fabrica, $con;

    if ($temVisita) {
        $joinVisita = " JOIN tbl_roteiro_visita ON tbl_roteiro_visita.roteiro_posto=tbl_roteiro_posto.roteiro_posto";
        #$whereVisita = " AND tbl_roteiro_visita.roteiro_visita={$temVisita}";
        $camposVisita = ",tbl_roteiro_visita.checkin, tbl_roteiro_visita.checkout, tbl_roteiro_visita.descricao AS descricao_visita, tbl_roteiro_visita.tempo_visita";
    }


    $sql = "SELECT 
                   tbl_roteiro_tecnico.tecnico,
                   tbl_roteiro.roteiro,
                   tbl_roteiro.status_roteiro,
                   tbl_roteiro_posto.data_visita,
                   tbl_roteiro_posto.codigo,
                   tbl_roteiro_posto.status,
                   tbl_roteiro_posto.roteiro_posto,
                   tbl_roteiro_posto.tipo_de_local,
                   tbl_roteiro.admin
                   {$camposVisita}
                 FROM tbl_roteiro
                 JOIN tbl_roteiro_posto ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro
                 JOIN tbl_roteiro_tecnico ON tbl_roteiro.roteiro = tbl_roteiro_tecnico.roteiro
                 $joinVisita
                WHERE tbl_roteiro.fabrica = {$login_fabrica} 
                $whereVisita
                  AND tbl_roteiro_posto.roteiro_posto = {$roteiro_posto}
                ";                
    $res = pg_query($con, $sql);
    if (pg_last_error()) {
        return array();
    }

    return pg_fetch_assoc($res);
    
}

function getCliente($cpf){
    global $con,$login_fabrica;

    $sql = "SELECT tbl_cliente.*,
                   tbl_cidade.nome AS nome_cidade,
                   tbl_cidade.estado AS nome_estado
                 FROM tbl_cliente
            LEFT JOIN tbl_cidade USING(cidade)
                WHERE tbl_cliente.cpf = '$cpf'";
    $res = pg_query($con, $sql);
    if (pg_last_error() || pg_num_rows($res) == 0) {
        return array();
    }

    return pg_fetch_assoc($res);
}

function getUltimasVisitas($codigo) {
    global $login_fabrica, $con;

    $sql = "SELECT tbl_roteiro_posto.roteiro_posto,tbl_roteiro_posto.data_visita, tbl_roteiro_posto.tipo_de_visita, tbl_roteiro_tecnico.tecnico
                 FROM tbl_roteiro
                 JOIN tbl_roteiro_posto ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro
                 JOIN tbl_roteiro_visita ON tbl_roteiro_visita.roteiro_posto = tbl_roteiro_posto.roteiro_posto
                 JOIN tbl_roteiro_tecnico ON tbl_roteiro_tecnico.roteiro = tbl_roteiro_posto.roteiro
                WHERE tbl_roteiro.fabrica = {$login_fabrica} 
                  AND tbl_roteiro_posto.codigo = '{$codigo}'
                  ORDER BY data_visita DESC LIMIT 1
                ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        return pg_fetch_all($res);
    }
    return array();
}

function getPosto($cnpj){
    global $con,$login_fabrica;

    $sql = "SELECT UPPER(tbl_posto.nome) AS nome, 
               tbl_posto.posto, 
               tbl_posto_fabrica.contato_nome, 
               tbl_posto_fabrica.contato_endereco AS endereco, 
               tbl_posto_fabrica.contato_complemento AS complemento,
               tbl_posto_fabrica.contato_numero AS numero,
               tbl_posto_fabrica.contato_bairro AS bairro, 
               tbl_posto_fabrica.contato_fone_comercial AS fone, 
               tbl_posto_fabrica.contato_cidade AS nome_cidade, 
               tbl_posto_fabrica.contato_estado AS nome_estado, 
               tbl_posto_fabrica.latitude,
               tbl_posto_fabrica.longitude
             FROM tbl_posto_fabrica
             JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
             AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            WHERE tbl_posto.cnpj = '$cnpj'";

    $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return array();
    }

    return pg_fetch_assoc($res);
}

function getRevenda($cnpj){
    global $con,$login_fabrica;

    $sql = "SELECT tbl_revenda.*,
                   tbl_cidade.nome AS nome_cidade,
                   tbl_cidade.estado AS nome_estado
                 FROM tbl_revenda
            LEFT JOIN tbl_cidade USING(cidade)
                WHERE tbl_revenda.cnpj = '$cnpj'";
    $res = pg_query($con, $sql);
    if (pg_last_error() || pg_num_rows($res) == 0) {
        return array();
    }

    return pg_fetch_assoc($res);
}

function getDescontoOPE($cnpj) {
    global $con;
    $sql = "SELECT * FROM makita_sa1_cliente WHERE a1_cgc = '$cnpj';";
    $res = pg_query($con, $sql);


    $cliente_despec_ele  = pg_fetch_result($res, 0, 'a1_despec');
    $cliente_despec_lav  = pg_fetch_result($res, 0, 'a1_despec1');
    $cliente_despec_ope  = pg_fetch_result($res, 0, 'a1_despec2');
    if (strlen($cliente_despec_ele) == 0) {
        $cliente_despec_ele = 0 ;
    } 
    if (strlen($cliente_despec_lav) == 0) {
        $cliente_despec_lav = 0 ;
    } 
    if (strlen($cliente_despec_ope) == 0) {
        $cliente_despec_ope = 0 ;
    } 
    $cliente_despec_ele = str_replace (',','.',$cliente_despec_ele);
    $cliente_despec_lav = str_replace (',','.',$cliente_despec_lav);
    $cliente_despec_ope = str_replace (',','.',$cliente_despec_ope);


    return ["desconto_lavadoras" => $cliente_despec_lav, "desconto_eletrica" => $cliente_despec_ele, "desconto_ope" => $cliente_despec_ope];
}

function getMediaTreinamento($posto, $dt = null) {

    global $login_fabrica, $con;
    $data_hoje     = (!empty($dt)) ? $dt : date('Y-m-d');
    $data_vinte_quatro_seis = date('Y-m-d', strtotime('-24 months', strtotime($data_hoje)));
    $sql = "SELECT COUNT(tbl_treinamento_posto.posto) AS media_treinamento
             FROM tbl_treinamento_posto 
             JOIN tbl_treinamento USING(treinamento) 
            WHERE tbl_treinamento.fabrica = {$login_fabrica} 
              AND tbl_treinamento_posto.posto = {$posto}
              AND tbl_treinamento_posto.data_inscricao::date BETWEEN '$data_vinte_quatro_seis' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return $retorno["media_treinamento"];
}

function getMediaOs($posto, $revenda = null, $dt = null) {

    global $login_fabrica, $con;
    $data_hoje     = (!empty($dt)) ? $dt : date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months', strtotime($data_hoje)));

    $sql = "SELECT (COUNT(os)/6)  AS media_os
              FROM tbl_os
             WHERE fabrica = {$login_fabrica} 
               AND status_checkpoint = 9
               AND posto = {$posto}
               AND data_digitacao::date BETWEEN '$data_seis_ant' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return $retorno["media_os"];

}

function getMediaCompra($posto, $dt = null) {

    global $login_fabrica, $con;
    $data_hoje     = (!empty($dt)) ? $dt : date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months', strtotime($data_hoje)));

    $sql = "SELECT (sum(total)/6) AS media_compra
             FROM tbl_pedido
            WHERE fabrica = {$login_fabrica} 
              AND status_pedido IN(4,5)
              AND posto = {$posto}
              AND data::date BETWEEN '$data_seis_ant' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return $retorno["media_compra"];

}

if ($login_fabrica == 42) {

    if (!empty($roteiro_posto)) {
        $erro = false;
        $retorno  = getResultado($roteiro_posto, true);
        $tecnico  = $retorno["tecnico"];
        $data_visita  = $retorno['data_visita'];
        $xdata_visita  = $retorno['data_visita'];
        $mostrar = false;

        if ($retorno["tipo_de_local"] == "PA") {
            $dados_contato = getPosto($retorno["codigo"]);
            $posto_id = $dados_contato["posto"];
            $medias["media_os"] = getMediaOs($dados_contato["posto"], '', $retorno["checkout"]);
            $medias["media_treinamento"] = getMediaTreinamento($dados_contato["posto"], $retorno["checkout"]);
            $medias["media_compra"] = getMediaCompra($dados_contato["posto"], $retorno["checkout"]);
            $ultimasVisitas = getUltimasVisitas($retorno["codigo"]);
            if (count($ultimasVisitas) > 0) {
                $ultimaVisita = date("d/m/Y", strtotime($ultimasVisitas[0]['data_visita']));
            }
            $xdescontos = getDescontoOPE($retorno["codigo"]);
            $descontos["desconto_eletrica"]  = $xdescontos["desconto_eletrica"];
            $descontos["desconto_ope"]       = $xdescontos["desconto_ope"];
            $descontos["desconto_lavadoras"] = $xdescontos["desconto_lavadoras"];
            $mostrar = true;
        } else {
            $mostrar = false;
        }

    } else {
        $erro = true;
    }
}

?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <meta http-equiv=pragma content=no-cache>
          <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
  <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />

  <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <?php
        $plugins = array(
            "shadowbox",
        );
        include("plugin_loader.php");

        ?>
        <script language="javascript">

            $(function() {

            });
        </script>
       
    </head>
    <body>
        <?php

            $sql = "SELECT tbl_roteiro_visita.*, tbl_roteiro_posto.observacao, EXTRACT(EPOCH FROM (tbl_roteiro_visita.checkout - tbl_roteiro_visita.checkin)/3600) AS tp_visita FROM tbl_roteiro_visita JOIN tbl_roteiro_posto USING (roteiro_posto) WHERE tbl_roteiro_visita.roteiro_posto = {$roteiro_posto} ";
            $res = pg_query($con, $sql);
            if (pg_last_error()) {
                exit("<div class='alert alert-danger'> Nenhum dados cadastrados</div>");
            }

            $retorno = pg_fetch_object($res);
            if (empty($retorno)) {
                exit("<div class='alert alert-danger'> Nenhum dados cadastrados</div>");
            }

                $dataDigitacao = new DateTime($retorno->checkin);
                $dataHoje      = new DateTime($retorno->checkout);

                $data1  = $dataDigitacao->format('Y-m-d H:i:s');
                $data2  = $dataHoje->format('Y-m-d H:i:s');
                $data11  = $dataDigitacao->format('d/m/Y H:i:s');
                $data22  = $dataHoje->format('d/m/Y H:i:s');

                if ($login_fabrica == 42) {
                    $assuntoId = '';
                    $assuntoIdArray = [];
                    $sqlCont = "SELECT tbl_roteiro_posto.contato, tbl_roteiro_visita.descricao FROM tbl_roteiro_posto LEFT JOIN tbl_roteiro_visita USING(roteiro_posto) WHERE roteiro_posto =".$retorno->roteiro_posto;
                    $resCont = pg_query($con, $sqlCont);
                    if (pg_num_rows($resCont) > 0) {
                        $contatoArr = pg_fetch_result($resCont, 0, 'contato');
                        $contatoArr = str_replace('\\\\u', '\\u', $contatoArr);
                        $contatoArr = json_decode($contatoArr, true);

                        $detlhes_visita = pg_fetch_result($resCont, 0, 'descricao');

                        $assuntoId = (is_array($contatoArr)) ? $contatoArr : json_decode($contatoArr,true);

                        if (count($assuntoId) > 0) {

                            foreach ($assuntoId as $key => $value) {
                                if ($key === "assunto") {
                                    if (isset($value["assunto"])) {
                                        $assuntoIdArray["assunto"] = (isset($value["assunto"])) ? $value["assunto"] : $value;
                                    } else {
                                        if (count($value)) {
                                            foreach ($value as $k => $v) {
                                                $assuntoIdArray["assunto"] = $value;
                                            }
                                        }
                                    }
                                }

                                if ($key === "assunto_add" || isset($value["assunto_add"])) {
                                    $assuntoIdArray["assunto_add"] = (isset($value["assunto_add"])) ? $value["assunto_add"] : $value;
                                }
                            }

                            if (!empty($assuntoIdArray['assunto'])) {
                                if (count($assuntoIdArray["assunto"]) > 1) {
                                    $arrDesAssunto = [];
                                    foreach ($assuntoIdArray["assunto"] as $ky => $vl) {
                                        $sqlAss = "SELECT assunto FROM tbl_roteiro_assunto WHERE roteiro_assunto = ".$vl;
                                        $resAss = pg_query($con, $sqlAss);
                                        $arrDesAssunto[] = pg_fetch_result($resAss, 0, 'assunto');
                                    }
                                    $assuntoDesc = implode(", ", $arrDesAssunto);
                                } else {
                                    $sqlAss = "SELECT assunto FROM tbl_roteiro_assunto WHERE roteiro_assunto = ".$assuntoIdArray['assunto'];
                                    $resAss = pg_query($con, $sqlAss);
                                    $assuntoDesc = pg_fetch_result($resAss, 0, 'assunto');
                                }
                            }
                        }
                    }
                }
                
        ?>
         
          <div class="container">
            <table width="100%" border="1">
                <tr>
                    <td colspan="2" align="center"> <h4>Detalhes da Visita</h4></td>
                </tr>
                <tr>
                    <td class="tar centro"><b>Checkin:</b></td>
                    <td><?php echo $data11;?></td>
                </tr>
                <tr>
                    <td class="tar"><b>Checkout:</b></td>
                    <td><?php echo $data22;?></td>
                </tr>
                <tr>
                    <td class="tar"><b>Tempo Visita:</b></td>
                    <td><?php echo number_format($retorno->tp_visita,2,'H:','.')."M"; ?></td>
                </tr>
                <tr>
                    <td colspan="2" align="center"><b>Fotos:</b></td>
                </tr>
                <tr>
                    <td colspan="2">
                    <?php
                         $boxUploader = array(
                            "div_id" => "div_anexos",
                            "prepend" => $anexo_prepend,
                            "context" => "roteiro",
                            "unique_id" => $tempUniqueId,
                            "hash_temp" => $anexoNoHash,
                            "bootstrap" => false,
                            "hidden_button" => true
                        );
                        include "../box_uploader.php";
                    ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="center"><b>Observação:</b></td>
                </tr>
                <tr>
                    <td colspan="2"><?php echo (mb_check_encoding($retorno->observacao, "UTF-8")) ? utf8_decode($retorno->observacao) : $retorno->observacao;?></td>
                </tr>
                <?php if (!empty($assuntoDesc) || !empty($detlhes_visita)) { ?>
                    <tr>
                        <td colspan="1" align="center"><b>Assunto:</b></td>
                        <td colspan="1" align="center"><b>Resumo da Visita:</b></td>
                    </tr>
                    <tr>
                        <td colspan="1"><?php echo (mb_check_encoding($assuntoDesc, "UTF-8")) ? utf8_decode($assuntoDesc) : $assuntoDesc;?></td>
                        <td colspan="1"><?php echo (mb_check_encoding($detlhes_visita, "UTF-8")) ? utf8_decode($detlhes_visita) : $detlhes_visita;?></td> 
                    </tr>
                <?php } ?>
                <?php if ($login_fabrica == 42 && $mostrar) { ?>
                    <tr>
                        <table width="100%" border="1">
                            <tr>
                                <td colspan="2" align="center"><b>Informações Adicionais</b></td>
                            </tr>
                            <tr>
                                <td class="tar centro"><b>Treinamentos realizados (2 anos):</b></td>
                                <td><?=$medias["media_treinamento"]?></td>
                            </tr>
                            <tr>
                                <td class="tar"><b>Média OS (6 meses):</b></td>
                                <td><?=$medias["media_os"]?></td>
                            </tr>
                            <tr>
                                <td class="tar"><b>Média Compra (6 meses):</b></td>
                                <td><?php echo "R$ " . number_format($medias["media_compra"], 2, ",", ".");?></td>
                            </tr>
                            <tr>
                                <td class="tar"><b>Desconto peças (Elétrica):</b></td>
                                <td><?=$descontos["desconto_eletrica"]?></td>
                            </tr>
                            <tr>
                                <td class="tar"><b>Desconto peças (OPE):</b></td>
                                <td><?=$descontos["desconto_ope"]?></td>
                            </tr>
                            <tr>
                                <td class="tar"><b>Desconto peças (Lavadoras):</b></td>
                                <td><?=$descontos["desconto_lavadoras"]?></td>
                            </tr>
                            <tr>
                                <td class="tar"><b>Data da última visita:</b></td>
                                <td><a href="listagem_visita.php?posto=<?=$posto_id?>" target='_blank'><?=$ultimaVisita?></a></td>
                            </tr>
                        </table>
                    </tr>
                <?php } ?>
            </table>
          </div>
         <style>
            body{
                background: #eeeeee;
                margin-top: 20px;
            }
            table tr td {
                padding-left: 20px;
                font-size: 15px;
            }
            .container{
                background: #ffffff;
                padding-top: 20px;
                padding-bottom: 20px;
            }
            .tar{
                text-align: right;

            }
            .tc_formulario{
              background: #ffffff !important;
            }
            .centro {
                width: 50%;
            }
            #btn-call-fileuploader, #div_anexos .titulo_tabela{display: none;}
        </style>
    </body>
</html>


<?php } else {
    exit("<div class='alert alert-danger'> Nenhum dados cadastrados</div>");
}
?>

