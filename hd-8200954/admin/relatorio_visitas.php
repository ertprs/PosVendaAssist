<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

if ($_POST) {

    $tipo          = $_POST["tipo"];
    $tipo_rel      = $_POST["tipo_rel"];
    $tecnico       = $_POST["tecnico"];
    $cond       = "";
    $estado         = $_POST["estado"];
    $tipo_contato   = (strlen(trim($_POST["tipo_contato"])) > 0) ? $_POST["tipo_contato"] : "TD";
    $codigo         = trim($_POST["codigo"]);
    $descricao      = trim($_POST["descricao"]);
    $status_roteiro = trim($_POST["status_roteiro"]);
    $tipo_visita    = trim($_POST["tipo_visita"]);

    $data_inicial    = trim($_REQUEST["data_inicial"]);
    $data_final      = trim($_REQUEST["data_final"]);


    if (strlen($data_inicial) == 0 && strlen($data_final)  == 0) {
        $msg_erro["msg"][]    = "Selecione ao menos um parâmetro para a pesquisa.";
        $msg_erro["campos"][] = "data";
    } 

    if ((strlen($data_inicial) > 0) && (strlen($data_final) > 0)){

        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_inicial = "{$yi}-{$mi}-{$di}";
            $aux_data_final   = "{$yf}-{$mf}-{$df}";

            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                $msg_erro["campos"][] = "data";
            }
        }
    }

    /*if (strlen($tipo_contato) == 0) {
        $msg_erro["msg"][] = "Escolha um Tipo de contato";
        $msg_erro["campos"][] = "tipo_contato";
    }*/

    if (count($msg_erro["msg"]) == 0) {

        if ($login_fabrica == 42) {
            $cond .= " AND tbl_roteiro_visita.checkin >= '$aux_data_inicial 00:00:00' AND tbl_roteiro_visita.checkout <= '$aux_data_final 23:59:59' ";
        } else {
            $cond .= " AND tbl_roteiro.data_inicio BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
        }

        if (!empty($status_roteiro)) {
            $cond .= " AND tbl_roteiro.status_roteiro = ".$status_roteiro;
        }

        if (!empty($codigo)) {
            $cond .= " AND tbl_roteiro_posto.codigo = '$codigo'";
        }

        if (strlen($tecnico) > 0) {
            $cond .= " AND tbl_roteiro_tecnico.tecnico = ".$tecnico;
        }

        if (!empty($tipo_visita)) {
            $cond .= " AND UPPER(tbl_roteiro_posto.tipo_de_visita) = UPPER('$tipo_visita') ";
        }

        if ($tipo_contato == 'TD') {
            $condC = " AND tbl_roteiro_posto.tipo_de_local = 'CL'";
            $condP = " AND tbl_roteiro_posto.tipo_de_local = 'PA'";
            $condR = " AND tbl_roteiro_posto.tipo_de_local = 'RV'";

            if (!empty($estado)) {
                $condC .= " AND UPPER(tbl_cidade.estado) = UPPER('$estado') ";
                $condP .= " AND UPPER(tbl_posto_fabrica.contato_estado) = UPPER('$estado') ";
                $condR .= " AND UPPER(tbl_cidade.estado) = UPPER('$estado') ";
            }

        } else {
            $cond .= " AND tbl_roteiro_posto.tipo_de_local = '$tipo_contato'";
        }

        $joinRoteiroP .= " JOIN tbl_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo";         
        $joinRoteiroP .= " JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} ";         
        $camposP      .= " tbl_posto_fabrica.codigo_posto AS codigo_contato, tbl_posto.nome, tbl_posto.posto, tbl_posto_fabrica.nome_fantasia, tbl_posto_fabrica.contato_cidade AS cidade, tbl_posto_fabrica.contato_estado AS estado, ";
        
        $joinRoteiroR .= " JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_roteiro_posto.codigo"; 
        $joinRoteiroR .= " JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade"; 
        $camposR      .= " tbl_revenda.cnpj AS codigo_contato, tbl_revenda.nome, tbl_revenda.revenda, tbl_cidade.estado, tbl_cidade.nome AS cidade, ";
        
        $joinRoteiroC .= " JOIN tbl_cliente ON tbl_cliente.cpf = tbl_roteiro_posto.codigo"; 
        $joinRoteiroC .= " JOIN tbl_cidade ON tbl_cliente.cidade = tbl_cidade.cidade"; 
        $camposC      .= " tbl_cliente.codigo_cliente AS codigo_contato, tbl_cliente.nome, tbl_cliente.cliente, tbl_cidade.estado, tbl_cidade.nome AS cidade, ";


        if ($tipo_contato == 'CL') {
            $joinRoteiro = $joinRoteiroC;
						$campos      = $camposC;
						if (!empty($estado)) {
            	$cond .= " AND UPPER(tbl_cidade.estado) = UPPER('$estado') ";
						}
        }
        if ($tipo_contato == 'RV') {
            $joinRoteiro = $joinRoteiroR;
            $campos      = $camposR;
						if (!empty($estado)) {
							$cond .= " AND UPPER(tbl_cidade.estado) = UPPER('$estado') ";
						}
				}
        if ($tipo_contato == 'PA') {
            $joinRoteiro = $joinRoteiroP;
						$campos      = $camposP;
						if (!empty($estado)) {
            	$cond .= " AND UPPER(tbl_posto_fabrica.contato_estado) = UPPER('$estado') ";
						}
        }

        $retorno          = array();
        $dataf            = date('Y-m-d');
        $datai6           = date('Y-m-d', strtotime('-6 months'));
        $datai12          = date('Y-m-d', strtotime('-12 months'));
        $joinRoteiro     .= " "; 

        if ($tipo_contato == 'TD') {

            if ($tipo_rel == "seis_doze_visitado") {
                $retorno = array();
                $setaData = new DateTime($datai6);
                $data612 = new DateTime('-12 month');


                $cond .= " AND tbl_roteiro.status_roteiro = 3 AND data_inicio::date  BETWEEN '$datai6' AND '".$data612->format('Y-m-d')."'"; 

                $rel_cliente = getResultado($joinRoteiroC, $cond.$condC, $camposC);
                $rel_posto   = getResultado($joinRoteiroP, $cond.$condP, $camposP);
                $rel_revenda = getResultado($joinRoteiroR, $cond.$condR, $camposR);

                if (empty($rel_cliente)) {
                    $rel_cliente = [];
                }
                if (empty($rel_posto)) {
                    $rel_posto = [];
                }
                if (empty($rel_revenda)) {
                    $rel_revenda = [];
                }
                $retorno     = array_merge($rel_cliente, $rel_posto, $rel_revenda);

                if (empty($retorno)) {
                    $msg_erro["msg"][] = "Nenhum registro encontrado";
                } 

            } elseif ($tipo_rel == "seis_visitado") {
                $retorno = array();
                $cond  .= " AND tbl_roteiro.status_roteiro = 3 AND data_inicio::date BETWEEN '$datai6' AND '$dataf'"; 
                
                $rel_cliente = getResultado($joinRoteiroC, $cond.$condC, $camposC);
                $rel_posto   = getResultado($joinRoteiroP, $cond.$condP, $camposP);
                $rel_revenda = getResultado($joinRoteiroR, $cond.$condR, $camposR);

                if (empty($rel_cliente)) {
                    $rel_cliente = [];
                }
                if (empty($rel_posto)) {
                    $rel_posto = [];
                }
                if (empty($rel_revenda)) {
                    $rel_revenda = [];
                }

                $retorno     = array_merge($rel_cliente, $rel_posto, $rel_revenda);

                if (empty($retorno)) {
                    $msg_erro["msg"][] = "Nenhum registro encontrado";
                }

            } else {
                $retorno = array();

                //$cond .= " AND tbl_roteiro_posto.status = 'OK'"; 

                $rel_cliente = getResultado($joinRoteiroC, $cond.$condC, $camposC);
                $rel_posto   = getResultado($joinRoteiroP, $cond.$condP, $camposP);
                $rel_revenda = getResultado($joinRoteiroR, $cond.$condR, $camposR);
                if (empty($rel_cliente)) {
                    $rel_cliente = [];
                }
                if (empty($rel_posto)) {
                    $rel_posto = [];
                }
                if (empty($rel_revenda)) {
                    $rel_revenda = [];
                }
                $retorno     = array_merge($rel_cliente, $rel_posto, $rel_revenda);
                if (empty($retorno)) {
                    $msg_erro["msg"][] = "Nenhum registro encontrado";
                } 
            }

        } else {


            if ($tipo_rel == "doze_nao_visitado") {

                $cond .= " AND tbl_roteiro.status_roteiro <> 3 AND tbl_roteiro_posto.tipo_de_local = 'PA' AND tbl_roteiro_posto.codigo IS NOT NULL AND tbl_roteiro.data_inicio BETWEEN '$datai12 00:00:00' AND '$dataf 23:59:59'"; 
                //$cond .= " AND tbl_roteiro.status_roteiro <> 3 AND tbl_roteiro_posto.tipo_de_local = 'PA' AND tbl_roteiro_posto.codigo IS NOT NULL AND tbl_roteiro.data_inicio BETWEEN '$datai12 00:00:00' AND '$dataf 23:59:59'"; 

                $retorno  = getResultado($joinRoteiro, $cond, $campos);
                if (empty($retorno)) {
                    $msg_erro["msg"][] = "Nenhum posto encontrado";
                } 

            } elseif ($tipo_rel == "seis_doze_visitado") {


                $setaData = new DateTime($datai6);
                $data612 = new DateTime('-12 month');


                $cond .= " AND tbl_roteiro.status_roteiro = 3 AND data_inicio::date  BETWEEN '$datai6' AND '".$data612->format('Y-m-d')."'"; 

                $retorno = getResultado($joinRoteiro, $cond, $campos);

                if (empty($retorno)) {
                    $msg_erro["msg"][] = "Nenhum registro encontrado";
                } 

            } elseif ($tipo_rel == "seis_visitado") {

                $cond  .= " AND tbl_roteiro.status_roteiro = 3 AND data_inicio::date BETWEEN '$datai6' AND '$dataf'"; 

                $retorno = getResultado($joinRoteiro, $cond, $campos);

                if (empty($retorno)) {
                    $msg_erro["msg"][] = "Nenhum registro encontrado";
                }

            } else {
               // $cond .= " AND tbl_roteiro_posto.status = 'OK'"; 

                $retorno = getResultado($joinRoteiro, $cond,$campos);

                if (empty($retorno)) {
                    $msg_erro["msg"][] = "Nenhum registro encontrado";
                } 
            }
        }

    }
}





if ($_POST["gerar_excel"]) {

    $data = date("d-m-Y-H:i");
    $fileName = "relatorio_visitas-{$data}.xls";
    $file = fopen("/tmp/{$fileName}", "w");


    fwrite($file, "
    <table border='1'>
        <thead>
            <tr>
                <th colspan='100%' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                    RELATÓRIO DE VISITAS
                </th>
            </tr>
            <tr>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Roteiro</th>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo de Visita</th>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Inicio</th>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Fim</th>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Responsável visita</th>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo Contato</th>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código do cliente</th>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome/Razão Social</th>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</th>
                <th nowrap bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição da Visita</th>
            </tr>
        </thead>
        <tbody>
    ");
             if (count($retorno) > 0) {
                foreach ($retorno as $key => $rows) {
                    fwrite($file, "
                            <tr>
                                <td class='tal'>".$rows["roteiro"]."</td>
                                <td class='tal'>".getLegendaTipoVisita($rows["tipo_de_visita"])."</td>
                                <td class='tal'>".$rows["checkin"]."</td>
                                <td class='tal'>".$rows["checkout"]."</td>
                                <td class='tal'>".$rows["tecnico"]."</td>
                                <td class='tal'>".$rows["tipo_de_local"]."</td>
                                <td class='tal'>".$rows["codigo_contato"]."</td>
                                <td class='tal'>".$rows["nome"]."</td>
                                <td class='tal'>".$rows["cidade"]."</td>
                                <td class='tal'>".$rows["estado"]."</td>
                                <td class='tal'>".($rows["descricao"])."</td>
                            </tr>");
                }
            }
    fwrite($file, "</tbody></table>");

    fclose($file);
    if (file_exists("/tmp/{$fileName}")) {
        system("mv /tmp/{$fileName} xls/{$fileName}");

        // devolve para o ajax o nome doa rquivo gerado
        echo "xls/{$fileName}";
    }
    exit;
}


function getResultado($joins = '', $where = '', $campos = '') {
    global $login_fabrica, $con;

    $data_hoje     = date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months'));
    $data_vinte_quatro_seis = date('Y-m-d', strtotime('-24 months'));

    $sql = "SELECT $campos
                   tbl_roteiro_tecnico.tecnico,
                   tbl_roteiro.roteiro,
                   tbl_roteiro_posto.tipo_de_visita,
                   tbl_roteiro_posto.tipo_de_local,
                   tbl_roteiro.status_roteiro,
                   tbl_roteiro_posto.codigo,
                   tbl_roteiro_posto.status,
                   tbl_roteiro_posto.roteiro_posto,
                   tbl_roteiro_visita.checkin,
                   tbl_roteiro_visita.checkout,
                   tbl_roteiro_visita.descricao,
                   tbl_roteiro.admin
                 FROM tbl_roteiro
                 JOIN tbl_roteiro_posto ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro
                 JOIN tbl_roteiro_tecnico ON tbl_roteiro.roteiro = tbl_roteiro_tecnico.roteiro
                 JOIN tbl_roteiro_visita ON tbl_roteiro_visita.roteiro_posto = tbl_roteiro_posto.roteiro_posto
                      {$joins}
                WHERE tbl_roteiro.fabrica = {$login_fabrica} 
                      {$where}";
    $res = pg_query($con, $sql);

    if (pg_last_error()) {
        return array();
    }

    foreach (pg_fetch_all($res) as $key => $rows) {
        $retorno[$key]["checkin"]           = geraDataTimeNormal($rows["checkin"]);
        $retorno[$key]["checkout"]          = geraDataTimeNormal($rows["checkout"]);
        $retorno[$key]["roteiro_posto"]     = $rows["roteiro_posto"];
        $retorno[$key]["roteiro"]           = $rows["roteiro"];
        $retorno[$key]["admin"]             = getAdmins($rows["admin"])->nome_completo;
        $retorno[$key]["tecnico"]           = getTecnicos($rows["tecnico"])->nome;
        $retorno[$key]["posto"]             = $rows["posto"];
        $retorno[$key]["tipo_de_visita"]    = $rows["tipo_de_visita"];
        $retorno[$key]["tipo_de_local"]     = getLegendaTipoContato($rows["tipo_de_local"]);
        $retorno[$key]["codigo"]            = $rows["codigo"];
        $retorno[$key]["codigo_contato"]            = $rows["codigo_contato"];
        $retorno[$key]["status"]            = $rows["status"];
        $retorno[$key]["cidade"]            = $rows["cidade"];
        $retorno[$key]["descricao"]         = mb_detect_encoding($rows["descricao"],'UTF-8',true) ? utf8_decode($rows["descricao"]) :  $rows["descricao"];
        $retorno[$key]["estado"]            = $rows["estado"];
        $retorno[$key]["nome"]              = empty($rows["nome"]) ? "" : utf8_decode($rows["nome"]);
       
    }

    return $retorno;
}
function geraDataTimeNormal($data) {
    $vetor = explode('-', $data);
    $vetor2 = explode(' ', $vetor[2]);
    $dataTratada = $vetor2[0] . '/' . $vetor[1] . '/' . $vetor[0] . ' ' . $vetor2[1];
    return $dataTratada;
}
function getLegendaTipoContato($sigla) {
    $arr =  array("CL" => "Cliente","RV" => "Revenda","PA" => "Posto Autorizado");
    return $arr[$sigla];
}

function getAdmins($admin = null) {
    global $con;
    global $login_fabrica;
    $cond = "";
    if (strlen($admin) > 0) {
        $cond = " AND tbl_admin.admin = {$admin}";
    }
    $sql = "SELECT  admin, nome_completo
              FROM tbl_admin
             WHERE tbl_admin.ativo IS TRUE
               AND tbl_admin.fabrica = {$login_fabrica} 
             $cond 
             ORDER BY nome_completo ASC";

    $res = pg_query($con, $sql);
    if (strlen($admin) > 0) {
        return pg_fetch_object($res);
    }
    return pg_fetch_all($res);
}

function getStatusRoteiro() {

    global $con,  $login_fabrica;

    $sql = "SELECT  * FROM tbl_status_roteiro";
    $res = pg_query($con, $sql);
    return pg_fetch_all($res);

}

function getLegendaTipoVisita($sigla) {
    $aa = array("VT" => "Visita Técnica","VC" => "Visita Comercial","VA" => "Visita Administrativa","CM" => "Clínica Makita","FE" => "Feira/Evento","TN" => "Treinamento");
     return $aa[$sigla];
}

function getTecnicos($tecnico = null) {
    global $con;
    global $login_fabrica;
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


$layout_menu = "tecnica";
$title = "Relatório de Visitas";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "select2",
    "datepicker",
    "mask",
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

    function retorna_tecnico(retorno){      
        $("#cpf_tecnico").val(retorno.cpf);
        $("#nome_tecnico").val(retorno.nome);
    }

    function retorna_consumidor(retorno){       
        $("#descricao").val(retorno.nome);
        $("#codigo").val(retorno.cpf);
    }

    function retorna_posto(retorno){
        $("#codigo").val(retorno.cnpj);
        $("#descricao").val(retorno.nome);
    }

    function retorna_revenda(retorno){
        $("#codigo").val(retorno.cnpj);
        $("#descricao").val(retorno.razao);
    }

    $(function() {
        Shadowbox.init();
        $.datepickerLoad(Array("data_inicial", "data_final"));
        $.dataTableLoad("#tabela");
        $(".select2").select2();

        $("select[name=tipo_contato]").change(function () {
            var tipo_contato = $(this).val();
            var label_codigo = "";
            var label_descricao = "";
            $("#codigo").removeAttr("disabled");
            $("#descricao").removeAttr("disabled");

            if (tipo_contato == 'CL') {
                label_codigo = "CPF/CNPJ Cliente";
                label_descricao = "Nome do Cliente";
                $("input[name=lupa_config]").attr("tipo", "consumidor");
                $(".lup_cod").attr("parametro", "cnpj");
                $(".lup_desc").attr("parametro", "nome_consumidor");
            } else if (tipo_contato == 'RV') {
                label_codigo = "CNPJ Revenda";
                label_descricao = "Nome da Revenda";
                $("input[name=lupa_config]").attr("tipo", "revenda");
                $(".lup_cod").attr("parametro", "cnpj");
                $(".lup_desc").attr("parametro", "razao_social");
            } else if (tipo_contato == 'PA') {
                label_codigo = "CNPJ do Posto";
                label_descricao = "Nome do Posto";
                $("input[name=lupa_config]").attr("tipo", "posto");
                $(".lup_cod").attr("parametro", "codigo");
                $(".lup_desc").attr("parametro", "nome");
            } else {
                label_codigo = "Código";
                label_descricao = "Nome";
                $("#codigo").attr("disabled", true);
                $("#descricao").attr("disabled", true);
                $("#codigo").val("");
                $("#descricao").val("");
            }

            $(".label_codigo").html(label_codigo);
            $(".label_descricao").html(label_descricao);

        });

        $("span[rel=lupa]").click(function () {
            var estado = $("#estado").val();
            var cidade = $("#cidade").val();

            $("input[name=lupa_config]").attr({"estado": estado, "cidade":cidade})
            $.lupa($(this), ["estado", "cidade", "refe"]);
        });
    });

   $(document).on("click",".btn-ver-detalhe",function(){
   	var id_visita = $(this).data("id");
            Shadowbox.open({
                content: "ver_detalhes_visita.php?id_visita="+id_visita,
                player: "iframe",
                width:  800,
                height: 550
            });

   });
</script>
<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }?>
    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>
    <form name='frm_relatorio' METHOD='POST' ACTION='relatorio_visitas.php' align='center' class='form-search form-inline tc_formulario' >
        <input type="hidden" name="pesquisa" value="true">
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span5'>
                            <h5 class='asteristico'>*</h5>
                            <input size="12" maxlength="10" type="text" name="data_inicial" id="data_inicial" value="<?=$data_inicial?>" class="span12" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_final'>Data Final</label>
                    <div class='controls controls-row'>
                        <div class='span5'>
                            <h5 class='asteristico'>*</h5>
                            <input size="12" maxlength="10" type="text" name="data_final" id="data_final" value='<?=$data_final?>' class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span4">
                <div class='control-group <?=(in_array("tecnico", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tecnico'>Responsável pela Visita</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <select name="tecnico" id="tecnico" class="span12 select2">
                                <option value="">Selecione ...</option>
                                <?php foreach (getTecnicos() as $key => $rows) {?>
                                    <option <?php echo ($tecnico == $rows["tecnico"]) ? "selected" : "";?> value="<?php echo $rows["tecnico"];?>"><?php echo $rows["nome"];?> </option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label'>Status Roteiro</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select name="status_roteiro" class='span12 select2' id="status_roteiro">
                                <option value="" selected="selected"> Selecione...</option>
                                <?php foreach (getStatusRoteiro() as $key => $rows) {?>
                                    <option <?php echo ($status_roteiro == $rows["status_roteiro"]) ? "selected" : "";?> value="<?php echo $rows["status_roteiro"];?>"><?php echo $rows["descricao"];?> </option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2" >
                <div class="control-group" >
                    <label class="control-label" >Estado</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <select class="span12" id="estado" name="estado" >
                                <option value="" >Selecione</option>
                                <?php
                                $sqlEstado = " SELECT estado FROM tbl_estado WHERE visivel AND pais = 'BR' ORDER BY estado ASC";
                                $resEstado = pg_query($con, $sqlEstado);

                                while ($row = pg_fetch_object($resEstado)) {
                                    $selected = ($row->estado == $_POST["estado"]) ? "selected" : "";
                                    echo "<option value='{$row->estado}' {$selected} >{$row->estado}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class='span3'>
                <div class='control-group <?php echo (in_array("tipo_contato", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tipo_contato'>Tipo de Contato</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>

                            <select name="tipo_contato" id="tipo_contato"  class='span12' >
                                <option value="">Selecione ...</option>
                                <option value="CL" <?php echo ($tipo_contato == "CL") ? "selected" : "";?>>Cliente</option>
                                <option value="RV" <?php echo ($tipo_contato == "RV") ? "selected" : "";?>>Revenda</option>
                                <option value="PA" <?php echo ($tipo_contato == "PA") ? "selected" : "";?>>Posto Autorizado</option>
                                <option value="TD" <?php echo ($tipo_contato == "TD") ? "selected" : "";?>>Todos</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class='span1'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span7'>
                <div class='control-group'>
                    <label class='control-label'>Tipo</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select name="tipo_rel" class='span12 select2' id="tipo_rel">
                                <option value="" selected="selected"> Selecione...</option>
                                <option value="seis_visitado" <?php echo ($tipo_rel == "seis_visitado")      ? "selected" : "";?>> Visitados nos últimos 6 meses</option>
                                <option value="seis_doze_visitado" <?php echo ($tipo_rel == "seis_doze_visitado") ? "selected" : "";?>> Visitados após os últimos 6 meses até os últimos 12 meses</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?php echo (in_array("tipo_visita", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='tipo_visita'>Tipo de Visita</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select name="tipo_visita" id="tipo_visita" class='span12'>
                                <option value="">Selecione ...</option>
                                <option value="VA" <?php echo ($tipo_visita == "VA") ? "selected" : "";?>>Visita Admistrativa</option>
                                <option value="VC" <?php echo ($tipo_visita == "VC") ? "selected" : "";?>>Visita Comercial</option>
                                <option value="VT" <?php echo ($tipo_visita == "VT") ? "selected" : "";?>>Visita Técnica</option>
                                <option value="CM" <?php echo ($tipo_visita == "CM") ? "selected" : "";?>>Clínica Makita</option>
                                <option value="FE" <?php echo ($tipo_visita == "FE") ? "selected" : "";?>>Feira/Evento</option>
                                <option value="TN" <?php echo ($tipo_visita == "TN") ? "selected" : "";?>>Treinamento</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span2'>
                <div class='control-group <?php echo (in_array("tipo_contato", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tipo_contato'>Tipo de Contato</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <?php
                                if (strlen($tipo_contato) > 0) {

                                    if ($tipo_contato == "CL") {
                                        $label_cod  = "CPF/CNPJ Cliente";
                                        $label_desc = "Nome do Cliente";
                                    } elseif ($tipo_contato == "RV") {
                                        $label_cod  = "CNPJ Revenda";
                                        $label_desc = "Nome da Revenda";
                                    } elseif ($tipo_contato == "PA") {
                                        $label_cod  = "CNPJ Posto";
                                        $label_desc = "Nome do Posto";
                                    } else {
                                        $label_cod  = "Código";
                                        $label_desc = "Nome";
                                    }
                                }
                            ?>
                            <select name="tipo_contato" id="tipo_contato"  class='span12' >
                                <option value="">Selecione ...</option>
                                <option value="CL" <?php echo ($tipo_contato == "CL") ? "selected" : "";?>>Cliente</option>
                                <option value="RV" <?php echo ($tipo_contato == "RV") ? "selected" : "";?>>Revenda</option>
                                <option value="PA" <?php echo ($tipo_contato == "PA") ? "selected" : "";?>>Posto Autorizado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label label_codigo' for='codigo'><?php echo $label_cod;?> </label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" name="codigo" disabled id="codigo" class='span12' value="<?php echo $codigo ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" class="lup_cod" name="lupa_config" tipo="posto" parametro="codigo" cidade="" refe="roteiro" estado=""/>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label label_descricao' for='descricao'><?php echo $label_desc;?> </label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <input type="text" name="descricao" disabled id="descricao" class='span12' value="<?php echo $descricao ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" class="lup_desc" name="lupa_config" tipo="posto" parametro="nome" cidade="" refe="roteiro" estado=""/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> <br />
  </div>
  <div class="container-fluid">
	<?	
	if (count($retorno) > 0) {
	?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th nowrap align="left">Roteiro</th>
                <th nowrap align="left">Data Inicio</th>
                <th nowrap align="left">Data Fim</th>
                <th nowrap align="left">Tipo de Visita</th>
                <th nowrap align="left">Responsável visita</th>
                <th nowrap>Tipo Contato</th>
                <th nowrap>Código do cliente</th>
                <th nowrap class="tal">Nome/Razão Social</th>
                <th nowrap align="left">Cidade</th>
                <th nowrap align="left">Estado</th>
                <th nowrap align="left">Descrição da Visita</th>
                <th nowrap>Destalhes da Visita</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (count($retorno) > 0) {
                foreach ($retorno as $key => $rows) {
            ?>
            <tr>
                <td class='tac'><a href="cadastro_roteiro_tecnico.php?roteiro=<?php echo $rows["roteiro"];?>" target="_blank"><?php echo $rows["roteiro"];?></a></td>
                <td class='tal'><?php echo $rows["checkin"];?></td>
                <td class='tal'><?php echo $rows["checkout"];?></td>
                <td class='tal'><?=getLegendaTipoVisita($rows["tipo_de_visita"])?></td>
                <td class='tal'><?php echo $rows["tecnico"];?></td>
                <td class='tal'><?php echo $rows["tipo_de_local"];?></td>
                <td class='tal'><?php echo $rows["codigo_contato"];?></td>
                <td class='tal'><?php echo $rows["nome"];?></td>
                <td class='tal'><?php echo $rows["cidade"];?></td>
                <td class='tal'><?php echo $rows["estado"];?></td>
                <td class='tal'><?php echo ($rows["descricao"]);?></td>
                
                <td class='tac'>
                    <button type="button" data-id="<?php echo $rows["roteiro_posto"];?>" class="btn btn-ver-detalhe btn-mini btn-info"><i class="icon-search icon-white"></i> Visualizar </button>     
                </td>
            </tr>
            <?php }}?>
        </tbody>
    </table>
    </div>
    <?php
        include 'include/funcoes.php';
        $jsonPOST = excelPostToJson($_POST);
    ?>
    <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
    <div id='gerar_excel' class="btn_excel">
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Gerar Arquivo Excel</span>
    </div>
    <?php }?>
</div> 
<?php include 'rodape.php';?>
