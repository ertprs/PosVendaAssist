<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);
if (strlen($_REQUEST["xvisita"]) == 0 && $_REQUEST["visita"]) {
    $roteiro_posto = $_REQUEST["visita"];
    if (verificaVisita($roteiro_posto)) {
        $erro = false;
        $retorno  = getResultado($roteiro_posto);
        $tecnico  = $retorno["tecnico"];
        $data_visita  = $retorno['data_visita'];
        $xdata_visita  = $retorno['data_visita'];


        if ($retorno["tipo_de_local"] == "CL") {
            $dados_contato = getCliente($retorno["codigo"]);
            $medias["media_os"] = "";
            $medias["media_treinamento"] = "";
            $medias["media_compra"] = "";
            $ultimasVisitas = getUltimasVisitas($retorno["codigo"]);
            $descontos["desconto_eletrica"] = "";
            $descontos["desconto_ope"] = "";
            $descontos["desconto_lavadoras"] = "";
        

        } elseif ($retorno["tipo_de_local"] == "PA") {
            $dados_contato = getPosto($retorno["codigo"]);
            $medias["media_os"] = getMediaOs($dados_contato["posto"]);
            $medias["media_treinamento"] = getMediaTreinamento($dados_contato["posto"]);
            $medias["media_compra"] = getMediaCompra($dados_contato["posto"]);
            $ultimasVisitas = getUltimasVisitas($retorno["codigo"]);
            $xdescontos = getDescontoOPE($retorno["codigo"]);
            $descontos["desconto_eletrica"]  = $xdescontos["desconto_eletrica"];
            $descontos["desconto_ope"]       = $xdescontos["desconto_ope"];
            $descontos["desconto_lavadoras"] = $xdescontos["desconto_lavadoras"];


        } elseif ($retorno["tipo_de_local"] == "RV") {

            $dados_contato = getRevenda($retorno["codigo"]);
            $medias["media_os"] = "";
            $medias["media_treinamento"] = "";
            $medias["media_compra"] = "";
            $ultimasVisitas = getUltimasVisitas($retorno["codigo"]);

            $descontos["desconto_eletrica"]  = "";
            $descontos["desconto_ope"]       = "";
            $descontos["desconto_lavadoras"] = "";

        }


    } else {
        $erro = true;
    }
} elseif (isset($_REQUEST["xvisita"]) && strlen($_REQUEST["xvisita"]) > 0) {

    $xvisita = $_REQUEST["xvisita"];

    $roteiro_posto = $_REQUEST["visita"];
    if (!verificaVisita($roteiro_posto)) {
        $erro = false;
        $retorno  = getResultado($roteiro_posto,$xvisita);
        $tecnico  = $retorno["tecnico"];
        $data_visita  = $retorno['data_visita'];
        $xdata_visita  = $retorno['data_visita'];


        if ($retorno["tipo_de_local"] == "CL") {
            $dados_contato = getCliente($retorno["codigo"]);
            $medias["media_os"] = "";
            $medias["media_treinamento"] = "";
            $medias["media_compra"] = "";
            $ultimasVisitas = getUltimasVisitas($retorno["codigo"]);
            $descontos["desconto_eletrica"] = "";
            $descontos["desconto_ope"] = "";
            $descontos["desconto_lavadoras"] = "";
        

        } elseif ($retorno["tipo_de_local"] == "PA") {
            $dados_contato = getPosto($retorno["codigo"]);
            $medias["media_os"] = getMediaOs($dados_contato["posto"]);
            $medias["media_treinamento"] = getMediaTreinamento($dados_contato["posto"]);
            $medias["media_compra"] = getMediaCompra($dados_contato["posto"]);
            $ultimasVisitas = getUltimasVisitas($retorno["codigo"]);
            $xdescontos = getDescontoOPE($retorno["codigo"]);
            $descontos["desconto_eletrica"]  = $xdescontos["desconto_eletrica"];
            $descontos["desconto_ope"]       = $xdescontos["desconto_ope"];
            $descontos["desconto_lavadoras"] = $xdescontos["desconto_lavadoras"];


        } elseif ($retorno["tipo_de_local"] == "RV") {

            $dados_contato = getRevenda($retorno["codigo"]);
            $medias["media_os"] = "";
            $medias["media_treinamento"] = "";
            $medias["media_compra"] = "";
            $ultimasVisitas = getUltimasVisitas($retorno["codigo"]);

            $descontos["desconto_eletrica"]  = "";
            $descontos["desconto_ope"]       = "";
            $descontos["desconto_lavadoras"] = "";

        }

        $checkin   = geraDataTimeNormal($retorno["checkin"]);
        $checkout  = geraDataTimeNormal($retorno["checkout"]);
        $descricao = $retorno["descricao_visita"];


    } else {
        $erro = true;
    }




} else {
    $erro = true;
}

if ($_POST) {

    $msg_erro = array();
    $msg_sucesso = array();

    $checkin   = $_POST["checkin"];
    $checkout  = $_POST["checkout"];
    $data_visita  = $_POST["data_visita"]." 00:00:00";
    $descricao = $_POST["descricao"];

    if (isset($_POST["xvisita"]) && strlen($_POST["xvisita"]) > 0) {


        if (strlen($descricao) == 0) {
            $msg_erro["campos"][] = "descricao";
            $msg_erro["msg"][] = "Campo Descrição da Visita é obrigatório";
        }


        $dados = array();

        if (count($msg_erro["msg"]) == 0 ) {

            $dados["visita"]        = $_POST["xvisita"];
            $dados["descricao"]     = $descricao;
            $retorno = updateVisita($dados);
            
            if (!$retorno["erro"]) {
                $msg_sucesso["msg"][] = $retorno["msg"];
                $checkin   = "";
                $checkout  = "";
                $descricao = "";
                echo "<meta http-equiv=refresh content=\"3;URL=listagem_roteiros.php\">";
            } else {
                $msg_erro["msg"][] = $retorno["msg"];
            }
        }


    } else {

        if (strlen($checkin) == 0) {
            $msg_erro["campos"][] = "checkin";
            $msg_erro["msg"][] = "Campo Check-In é obrigatório";
        }

        if (strlen($checkout) == 0) {
            $msg_erro["campos"][] = "checkout";
            $msg_erro["msg"][] = "Campo Check-Out é obrigatório";
        }

        if (strlen($descricao) == 0) {
            $msg_erro["campos"][] = "descricao";
            $msg_erro["msg"][] = "Campo Descrição da Visita é obrigatório";
        }

        if (strlen($checkin) > 0 && strlen($checkout) > 0) {


            $aux_checkin   = geraDataTimeBD($checkin);
            $aux_checkout  = geraDataTimeBD($checkout);
            $aux_data_visita  = geraDataTimeBD($data_visita);

            if (strtotime($aux_checkin) < strtotime($aux_data_visita) || strtotime($aux_checkout) < strtotime($aux_data_visita)) {
                $msg_erro["msg"][]    = "Data de Check-In e/ou Check-Out não pode ser menor que a data de visita";
                $msg_erro["campos"][] = "checkin";
                $msg_erro["campos"][] = "checkout";
            }

            if (!validateDate($aux_checkin) or !validateDate($aux_checkout)) {
                $msg_erro["msg"][]    = "Data Inválida";
                $msg_erro["campos"][] = "checkin";
                $msg_erro["campos"][] = "checkout";
            } else {

                if (strtotime($aux_checkout) < strtotime($aux_checkin)) {
                    $msg_erro["msg"][]    = "Check-Out não pode ser menor que Check-In";
                    $msg_erro["campos"][] = "checkout";
                }
            }
        }

        $dados = array();

        if (count($msg_erro["msg"]) == 0 ) {
            $xaux_checkin  = new DateTime($aux_checkin);
            $xaux_checkout = new DateTime($aux_checkout);

            $diff = $xaux_checkin->diff($xaux_checkout);

            $tempo_visita = trataTime($diff);

            $dados["checkin"]       = $aux_checkin;
            $dados["checkout"]      = $aux_checkout;
            $dados["descricao"]     = $descricao;
            $dados["roteiro_posto"] = $roteiro_posto;
            $dados["tempo_visita"]  = $tempo_visita;
            $retorno = insertVisita($dados);
            
            if (!$retorno["erro"]) {
                $msg_sucesso["msg"][] = $retorno["msg"];
                $checkin   = "";
                $checkout  = "";
                $descricao = "";
                echo "<meta http-equiv=refresh content=\"3;URL=listagem_roteiros.php\">";
            } else {
                $msg_erro["msg"][] = $retorno["msg"];
            }
        }

    }
    
}


if (!empty($roteiro_posto)) {
    $tempUniqueId = $roteiro_posto;
    $anexoNoHash = null;
} else if (strlen($_POST["anexo_chave"]) > 0) {
    $tempUniqueId = $_POST["anexo_chave"];
    $anexoNoHash = true;
} else {
    $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
    $anexoNoHash = true;
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





function trataTime($diff) {
    if (strlen($diff->h) == 1) {
        $hora = "0".$diff->h;
    } else {
        $hora = $diff->h;
    }
    if (strlen($diff->i) == 1) {
        $min = "0".$diff->i;
    } else {
        $min = $diff->i;
    }
    if (strlen($diff->s) == 1) {
        $seg = "0".$diff->s;
    } else {
        $seg = $diff->s;
    }

    if ($diff->d > 0) {
        $hora = ($diff->d*24);
    }
    return $hora.":".$min.":".$seg;

}
function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}
function geraDataNormal($data) {
    $vetor = explode('-', $data);
    $dataTratada = $vetor[2] . '/' . $vetor[1] . '/' . $vetor[0];
    return $dataTratada;
}

function geraDataTimeNormal($data) {
    $vetor = explode('-', $data);
    $vetor2 = explode(' ', $vetor[2]);
    $dataTratada = $vetor2[0] . '/' . $vetor[1] . '/' . $vetor[0] . ' ' . $vetor2[1];
    return $dataTratada;
}

function geraDataTimeBD($data) {
 
    list($dia, $mes, $anox) = explode('/', $data);
    list($ano, $hora) = explode(' ', $anox);

    $dataTratada = $ano . '-' . $mes . '-' . $dia . ' ' . $hora;
    return $dataTratada;
}

function insertVisita($dados = array()) {
    global $login_fabrica, $con;

    if (empty($dados)) {
        return array("erro" => true, "msg" => "Dados da visita, não enviado");
    }

    $sql = "INSERT INTO tbl_roteiro_visita (roteiro_posto, descricao, checkin, checkout, tempo_visita) VALUES (".$dados['roteiro_posto'].", '".$dados['descricao']."', '".$dados['checkin']."', '".$dados['checkout']."', '".$dados['tempo_visita']."')";
    $res = pg_query($con, $sql);
    
    if (pg_last_error()) {
        return array("erro" => true, "msg" => "Erro ao gravar a visita");
    }


    $sqlUp = "UPDATE tbl_roteiro_posto SET status = 'OK', data_update='".date('Y-m-d H:i:s')."'  WHERE roteiro_posto=".$dados['roteiro_posto'];
    $resUp = pg_query($con, $sqlUp);

    return array("erro" => false, "msg" => "Visita gravada com sucesso");
    
}
function updateVisita($dados = array()) {
    global $login_fabrica, $con;

    if (empty($dados)) {
        return array("erro" => true, "msg" => "Dados da visita, não enviado");
    }

    $sql = "UPDATE tbl_roteiro_visita SET descricao='".$dados['descricao']."' WHERE roteiro_visita=".$dados['visita'];
    $res = pg_query($con, $sql);
    
    if (pg_last_error()) {
        return array("erro" => true, "msg" => "Erro ao gravar a visita");
    }

    return array("erro" => false, "msg" => "Visita gravada com sucesso");
    
}







function getLegendaTipoContato($sigla) {
    $arr =  array("CL" => "Cliente","RV" => "Revenda","PA" => "Posto Autorizado");
    return $arr[$sigla];
}

function getResultado($roteiro_posto, $temVisita = null) {
    global $login_fabrica, $con;

    if ($temVisita) {
        $joinVisita = " JOIN tbl_roteiro_visita ON tbl_roteiro_visita.roteiro_posto=tbl_roteiro_posto.roteiro_posto";
        $whereVisita = " AND tbl_roteiro_visita.roteiro_visita={$temVisita}";
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

function getUltimasVisitas($codigo) {
    global $login_fabrica, $con;

    $sql = "SELECT tbl_roteiro_posto.roteiro_posto,tbl_roteiro_posto.data_visita, tbl_roteiro_posto.tipo_de_visita, tbl_roteiro_tecnico.tecnico
                 FROM tbl_roteiro
                 JOIN tbl_roteiro_posto ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro
                 JOIN tbl_roteiro_visita ON tbl_roteiro_visita.roteiro_posto = tbl_roteiro_posto.roteiro_posto
                 JOIN tbl_roteiro_tecnico ON tbl_roteiro_tecnico.roteiro = tbl_roteiro_posto.roteiro
                WHERE tbl_roteiro.fabrica = {$login_fabrica} 
                  AND tbl_roteiro_posto.codigo = '{$codigo}'
                  LIMIT 5
                ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        return pg_fetch_all($res);
    }
    return array();
}
function verificaVisita($roteiro_posto) {
    global $login_fabrica, $con;

    $sql = "SELECT tbl_roteiro_posto.roteiro_posto
                 FROM tbl_roteiro
                 JOIN tbl_roteiro_posto ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro
                 JOIN tbl_roteiro_visita ON tbl_roteiro_visita.roteiro_posto = tbl_roteiro_posto.roteiro_posto
                WHERE tbl_roteiro.fabrica = {$login_fabrica} 
                  AND tbl_roteiro_posto.roteiro_posto = {$roteiro_posto}
                ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        return false;
    }
    return true;
}

function getTecnicos($tecnico = null) {
    global $con,$login_fabrica;
    $cond = "";
    if (strlen($tecnico) > 0) {
        $cond = " AND tbl_tecnico.tecnico = {$tecnico}";
    }

    $sql = "SELECT  tecnico, nome
              FROM tbl_tecnico
             WHERE tbl_tecnico.ativo IS TRUE
               AND tipo_tecnico = 'TF'
               AND tbl_tecnico.fabrica = {$login_fabrica} {$cond} ORDER BY nome ASC";

    $res = pg_query($con, $sql);

    if (strlen($tecnico) > 0) {
        return pg_fetch_object($res);
    }
    return pg_fetch_all($res);
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


function getMediaOs($posto, $revenda = null) {

    global $login_fabrica, $con;
    $data_hoje     = date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months'));

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


function getMediaCompra($posto) {

    global $login_fabrica, $con;
    $data_hoje     = date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months'));

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

function getMediaTreinamento($posto) {

    global $login_fabrica, $con;
    $data_hoje     = date('Y-m-d');
    $data_vinte_quatro_seis = date('Y-m-d', strtotime('-24 months'));
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

function getLegendaTipoVisita($sigla) {
    $legenda = array("VT" => "Visita Técnica","VC" => "Visita Comercial","VA" => "Visita Administrativa","CM" => "Clínica Makita","FE" => "Feira/Evento","TN" => "Treinamento");
    return $legenda[$sigla];
}

$layout_menu = "tecnica";
$title = "Realizar Visita Agendada";
include 'cabecalho_new.php';

$plugins = array(
    "datetimepickerbs2",
    "mask",
    "shadowbox",
);

include("plugin_loader.php");
?>
<style>
    .icon-edit {
        background-position: -95px -75px;
    }
    .icon-remove {
        background-position: -312px -3px;
    }
    .icon-search {
        background-position: -48px -1px;
    }
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        var datav = '<?php echo $data_visita;?>';
        var date = new Date(datav);
        date.setDate(date.getDate());
        Shadowbox.init();
        $(".btn-ver-detalhe").click(function(){
            var id_visita = $(this).data("id");
            Shadowbox.open({
                content: "ver_detalhes_visita.php?id_visita="+id_visita,
                player: "iframe",
                width:  800,
                height: 500
            });
        });
        $('#date_checkin').datetimepicker({'language': 'pt-BR','startDate': date});
        $('#date_checkout').datetimepicker({'language': 'pt-BR','startDate': date});
    });
</script>
<?php if ($erro == true) {?>
    <div class="alert alert-error">
        <h4>Nenhuma visita encontrada</h4>
    </div>
<?php exit;}?>
<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }?>
<?php if (count($msg_erro["msg"]) == 0 && count($msg_sucesso["msg"]) > 0) {?>
    <div class="alert alert-success">
        <h4><?=implode("<br />", $msg_sucesso["msg"])?></h4>
    </div>
<?php }?>
    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>
    <form name='frm_relatorio' METHOD='POST' ACTION='realizar_visita.php?visita=<?php echo $roteiro_posto;?>' align='center' class='form-search form-inline tc_formulario' >
        <input type="hidden" name="pesquisa" value="true">
        <input type="hidden" name="xvisita" value="<?php echo $xvisita;?>">
        <input type="hidden" name="data_visita" value="<?php echo geraDataNormal($xdata_visita);?>">
        <div class='titulo_tabela '>Realizar Visita</div>
        <br/>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span2">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Tipo de Contato</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo getLegendaTipoContato($retorno["tipo_de_local"]);?>" disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Código</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo $retorno["codigo"];?>" disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span6">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Nome/Razão Social</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo $dados_contato["nome"];?>" disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span3">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Telefone</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo $dados_contato["fone"];?>"  disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span7">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Endereço completo</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo $dados_contato["endereco"].', Nº '.$dados_contato["numero"].' - '.$dados_contato["complemento"].' - '.$dados_contato["bairro"].' - '.$dados_contato["nome_cidade"].'/'.$dados_contato["nome_estado"];?>"  disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span3">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Treinamentos realizados (2 anos)</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo $medias["media_treinamento"];?>" disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Média OS (6 meses)</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo $medias["media_os"];?>" disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Média Compra (6 meses)</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo "R$ " . number_format($medias["media_compra"], 2, ",", ".");?>" disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span3">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Desconto peças (Elétrica)</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo $descontos["desconto_eletrica"];?>" disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Desconto peças (OPE)</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo $descontos["desconto_ope"];?>" disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class='control-group '>
                    <label class='control-label' for='tecnico'>Desconto peças (Lavadoras)</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" value="<?php echo $descontos["desconto_lavadoras"];?>" disabled class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>



        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class='titulo_tabela '>Últimas Visitas Realizadas</div> 
                <table class="table table-bordered table-striped table-fixed">
                    <thead>
                        <tr class='titulo_tabela '>
                            <th>Data Visita </th>
                            <th>Tipo Visita </th>
                            <th class="tal">Responsável </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (count($ultimasVisitas) == 0) {
                            echo  '<tr><td colspan="3" class="tac">Nenhuma visita realizada.</td></tr>';
                        } else {
                            foreach ($ultimasVisitas as $key => $rows) {
                               $responsavel =  getTecnicos($rows["tecnico"]);
                        ?>
                        <tr>
                            <td class="tac">
                                <a href="#" data-id="<?php echo $rows["roteiro_posto"];?>" class="btn-ver-detalhe">
                                <?php echo geraDataNormal($rows["data_visita"]);?>
                                </a> 
                            </td>
                            <td class="tac"><?php echo getLegendaTipoVisita($rows["tipo_de_visita"]);?></td>
                            <td class="tal"><?php echo $responsavel->nome;?> </td>
                        </tr>
                        <?php }}?>
                    </tbody>
                </table>   
            </div>
            <div class='span1'></div>
        </div>

        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class='control-group <?=(in_array("tecnico", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tecnico'>Responsável pela Visita</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <select name="tecnico" disabled id="tecnico" class="span12">
                                <option value="">Selecione ...</option>
                                <?php foreach (getTecnicos() as $key => $rows) {?>
                                    <option <?php echo ($tecnico == $rows["tecnico"]) ? "selected" : "";?> value="<?php echo $rows["tecnico"];?>"><?php echo $rows["nome"];?> </option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>

        <?php 
            $disabled = "";
            if (strlen($xvisita) > 0) {
                $disabled = "disabled";
            }

        ?>

        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span3'>
                    <label class='control-label' for='checkin'>Data Visita</label><br />
                    <input size="12" type="text" disabled value="<?php echo geraDataNormal($xdata_visita);?>" class="span12" >
            </div>
            <div class='span3'>
                <div id="date_checkin" class='input-append control-group <?=(in_array("checkin", $msg_erro["campos"])) ? "error" : ""?>''>
                    <label class='control-label' for='checkin'>Check-In</label><br />
                    <h5 class='asteristico'>*</h5>
                    <input <?php echo $disabled;?> size="12" type="text" data-format="dd/MM/yyyy hh:mm:ss" name="checkin" id="checkin" value="<?=$checkin?>" class="span12" >
                    <span class="add-on">
                        <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
                    </span>
                </div>
            </div>
            <div class='span3'>
                <div id="date_checkout" class='input-append control-group <?=(in_array("checkout", $msg_erro["campos"])) ? "error" : ""?>''>
                    <label class='control-label' for='checkout'>Check-Out</label><br />
                    <h5 class='asteristico'>*</h5>
                    <input <?php echo $disabled;?> size="12" data-format="dd/MM/yyyy hh:mm:ss" type="text" name="checkout" id="checkout" value='<?=$checkout?>' class="span12 mask-datetimepicker">
                    <span class="add-on">
                        <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
                    </span>
                </div>
            </div>
            <div class='span1'></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class='control-group <?=(in_array("tecnico", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tecnico'>Descrição da Visita</label>
                    <h5 class='asteristico'>*</h5>
                    
                    <div class="controls controls-row">
                        <div class="span12">
                            <textarea name="descricao" id="descricao" class="span12"  rows="10"><?php echo $descricao;?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div><br />
        <div class='row-fluid'>
            <div class='span12'>
                <?php

                     $boxUploader = array(
                        "div_id" => "div_anexos",
                        "prepend" => $anexo_prepend,
                        "context" => "roteiro",
                        "unique_id" => $tempUniqueId,
                        "hash_temp" => $anexoNoHash,
                        "bootstrap" => true
                    );
                    include "../box_uploader.php";
                ?>
            </div>
        </div>
        <p><br/>
            <button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> <br />
  </div>
</div> 
<?php include 'rodape.php';?>
