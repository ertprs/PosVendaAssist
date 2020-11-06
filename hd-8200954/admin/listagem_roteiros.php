<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/AuditorLog.php';
include_once "../class/tdocs.class.php";
$tDocs       = new TDocs($con, $login_fabrica);

function getRoteiroList($tecnico, $status_roteiro, $data_inicial, $data_final, $estado = null, $tipo_contato = null, $codigo = null, $descricao = null){
    global $con, $login_fabrica;
    $cond = "";
    $join = "";

    if (strlen($tecnico) > 0) {
        $cond .= " AND tbl_roteiro_tecnico.tecnico=".$tecnico;
    }

    if (strlen($status_roteiro) > 0) {
        $cond .= " AND tbl_roteiro.status_roteiro=".$status_roteiro;
    }

    if (!empty($estado) && empty($estado)) {

        $join_estado = " LEFT JOIN tbl_cidade cd ON tbl_roteiro_posto.cidade = cd.cidade
                         LEFT JOIN tbl_posto_fabrica pf ON tbl_roteiro_posto.posto = pf.posto AND pf.fabrica = $login_fabrica ";
        $cond .= " AND (UPPER(cd.estado) = UPPER('$estado') OR UPPER(pf.contato_estado) = UPPER('$estado')) ";
    }

    if (!empty($tipo_contato)) {

        $joinRoteiroP .= " JOIN tbl_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo";         
        $joinRoteiroP .= " JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} ";         
        
        $joinRoteiroR .= " JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_roteiro_posto.codigo"; 
        $joinRoteiroR .= " JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade"; 
        
        $joinRoteiroC .= " JOIN tbl_cliente ON tbl_cliente.cpf = tbl_roteiro_posto.codigo"; 
        $joinRoteiroC .= " JOIN tbl_cidade ON tbl_cliente.cidade = tbl_cidade.cidade"; 

        if ($tipo_contato == 'CL') {
            $joinRoteiro = $joinRoteiroC;
            if (!empty($estado)) {
                $cond .= " AND UPPER(tbl_cidade.estado) = UPPER('$estado') ";           
            }
        }
        if ($tipo_contato == 'RV') {
            $joinRoteiro = $joinRoteiroR;
            if (!empty($estado)) {
                $cond .= " AND UPPER(tbl_cidade.estado) = UPPER('$estado') ";           
            }
        }
        if ($tipo_contato == 'PA') {
            $joinRoteiro = $joinRoteiroP;
            if (!empty($estado)) {
                $cond .= " AND UPPER(tbl_posto_fabrica.contato_estado) = UPPER('$estado') ";           
            }
        }
    }

    if (!empty($codigo)) {
        $cond .= " AND tbl_roteiro_posto.codigo = '$codigo'";
    }


    $sql = "SELECT DISTINCT tbl_roteiro.roteiro,
                    tbl_roteiro.tipo_roteiro,
                    tbl_roteiro.ativo,
                    tbl_roteiro.data_inicio,
                    tbl_roteiro.data_termino,
                    tbl_status_roteiro.status_roteiro,
                    tbl_status_roteiro.descricao as status_descricao,
                    tbl_roteiro.solicitante,
                    tbl_roteiro.qtde_dias,
                    tbl_roteiro.excecoes,
                    tbl_roteiro_tecnico.roteiro_tecnico,
                    tbl_tecnico.cpf,
                    tbl_tecnico.nome AS nome_tecnico
            FROM tbl_roteiro
            JOIN tbl_roteiro_tecnico ON tbl_roteiro_tecnico.roteiro = tbl_roteiro.roteiro
            JOIN tbl_status_roteiro ON tbl_status_roteiro.status_roteiro = tbl_roteiro.status_roteiro 
            JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_roteiro_tecnico.tecnico AND tbl_tecnico.fabrica = tbl_roteiro.fabrica AND tbl_tecnico.fabrica= $login_fabrica 
            LEFT JOIN tbl_roteiro_posto ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro
            $join_estado
            $joinRoteiro
           WHERE tbl_roteiro.fabrica = $login_fabrica 
             AND tbl_roteiro.data_inicio BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
           {$cond}
        ORDER BY tbl_roteiro.data_inicio";
    $res = pg_query($con, $sql);
    return pg_fetch_all($res);

}
function getLegendaTipoContato($sigla) {
    $arr =  array("CL" => "Cliente","RV" => "Revenda","PA" => "Posto Autorizado");
    return $arr[$sigla];
}
function getStatusRoteiro() {

    global $con,  $login_fabrica;

    $sql = "SELECT  * FROM tbl_status_roteiro";
    $res = pg_query($con, $sql);
    return pg_fetch_all($res);

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
function getLegendaStatus($sigla) {
    $legenda =  array("AC" => "A Confirmar", "CF" => "Confirmado", "OK" => "Visita feita", "CC" => "Cancelado");
    return $legenda[$sigla];
}
function geraDataTimeNormal($data) {
    $vetor = explode('-', $data);
    $vetor2 = explode(' ', $vetor[2]);
    $dataTratada = $vetor2[0] . '/' . $vetor[1] . '/' . $vetor[0] . ' ' . $vetor2[1];
    return $dataTratada;
}
function getLegendaTipoVisita($sigla) {
    $legenda = array("VT" => "Visita Técnica","VC" => "Visita Comercial","VA" => "Visita Administrativa","CM" => "Clínica Makita","FE" => "Feira/Evento","TN" => "Treinamento");
    return $legenda[$sigla];
}


if ($_POST) {


    $tecnico        = $_POST["tecnico"];
    $status_roteiro = trim($_POST["status_roteiro"]);
    $data_inicial   = $_POST["data_inicial"];
    $data_final     = $_POST["data_final"];
    $estado         = trim($_POST["estado"]);
    $tipo_contato   = trim($_POST["tipo_contato"]);
    $codigo         = trim($_POST["codigo"]);
    $descricao      = trim($_POST["descricao"]);

    if(empty($data_inicial)){
        $msg_erro["campos"][] = "data";
        $msg_erro["msg"][] =  "Preencha a Data Inicio";
    }
       

    if(empty($data_final)){
        $msg_erro["campos"][] = "data";
        $msg_erro["msg"][] =  "Preencha a Data Final";
    }


    if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
      
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
    $roteiros = array();
    if (count($msg_erro["msg"]) == 0) {

        $roteiros = getRoteiroList($tecnico, $status_roteiro, $aux_data_inicial, $aux_data_final, $estado, $tipo_contato, $codigo, $descricao);

        if (count($roteiros) == 0 || empty($roteiros)) {
            $msg_erro["msg"][] = "Nenhum registro encontrado";
        }
   
    }

}

function geraDataNormal($data) {
    $vetor = explode('-', $data);
    $dataTratada = $vetor[2] . '/' . $vetor[1] . '/' . $vetor[0];
    return $dataTratada;
}
function geraDataBD($data) {
    $vetor = explode('/', $data);
    $dataTratada = $vetor[2] . '-' . $vetor[1] . '-' . $vetor[0];
    return $dataTratada;
}

$layout_menu = "tecnica";
$title = "Listagem de Roteiros";
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
    $(function() {
        Shadowbox.init();
        $(".select2").select2();
        $("#data_inicial").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
        $("#data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

        $(".btn-ver-detalhe").click(function(){
            var id_visita = $(this).data("id");
            Shadowbox.open({
                content: "ver_detalhes_visita.php?id_visita="+id_visita,
                player: "iframe",
                width:  800,
                height: 500
            });
        });
 

        $(".btn-add-visita-avulsa").click(function(){
            var roteiro = $(this).data("roteiro");
            window.location.href="adiciona_visita_avulsa.php?roteiro="+roteiro;

        });

        $(".btn-mais-detalhes").click(function(){
            var id = $(this).data("id");
            var posicao = $(this).data("posicao");
            $(".tr_roteiro_"+id).show();

            if (posicao == 1) {
                $(".tr_roteiro_"+id).hide();
                $(this).data("posicao", 0);
            } else {
                $(this).data("posicao", 1);
            }

        });

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

</script>
<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }?>
    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>
    <form name='frm_relatorio' METHOD='POST' ACTION='listagem_roteiros.php' align='center' class='form-search form-inline tc_formulario' >
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
            <div class="span5">
                <div class='control-group'>
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
            <!-- <div class='span3'>
                <div class='control-group'>
                    <label class='control-label' for='tipo_contato'>Tipo de Contato</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select name="tipo_contato" id="tipo_contato"  class='span12' >
                                <option value="">Selecione ...</option>
                                <option value="CL" <?php echo ($tipo_contato == "CL") ? "selected" : "";?>>Cliente</option>
                                <option value="RV" <?php echo ($tipo_contato == "RV") ? "selected" : "";?>>Revenda</option>
                                <option value="PA" <?php echo ($tipo_contato == "PA") ? "selected" : "";?>>Posto Autorizado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'>Status Roteiro</label>
                    <div class='controls controls-row'>
                        <div class='span11'>
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
        <div class="row-fluid">
            <div class="span2"></div>
            
        </div>
     
        <p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> <br />
</div> 
<?php if (count($roteiros) > 0 && !empty($roteiros)) {?>
    <div class="container-fluid">
        <table id="roteiros-list" class='table table-striped table-bordered table-fixed' >
            <thead>
                <tr class='titulo_coluna' >
                    <th>Roteiro</th>
                    <th>Tipo Roteiro</th>
                    <th>Data Início</th>
                    <th>Data Término</th>
                    <th>Responsável pelo roteiro</th>
                    <th>Estado</th>
                    <th>Cidade</th>
                    <th>Ativo</th>
                    <th>Status</th>
                    <th>Solicitante</th>
                    <th>Qtde Dias</th>
                    <!-- <th>Log</th> -->
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                
                foreach ($roteiros as $item) { 

                    $sql_t = "SELECT posto,cidade FROM tbl_roteiro_posto WHERE roteiro = {$item['roteiro']}";
                    $res_t = pg_query($con,$sql_t);

                    $estadoTabela = array();
                    $cidadeTabela = array();

                    for ($t=0; $t < pg_num_rows($res_t) ; $t++) { 
                        $posto_t = pg_fetch_result($res_t, $t, posto);
                        $cidade_t = pg_fetch_result($res_t, $t, cidade);
                        

                        if (!empty($posto_t)) {
                            $sql_tp = "SELECT contato_estado,contato_cidade FROM tbl_posto_fabrica WHERE posto = {$posto_t} AND fabrica = {$login_fabrica}; ";
                            $res_tp = pg_query($con,$sql_tp);
                            $estadoPostoTabela = pg_fetch_result($res_tp, 0, contato_estado);
                            $cidadePostoTabela = pg_fetch_result($res_tp, 0, contato_cidade);

                            if(!in_array($cidadePostoTabela, $cidadeTabela)){
                                $cidadeTabela[]=$cidadePostoTabela;
                            }
                            if (!in_array($estadoPostoTabela, $estadoTabela)) {
                                $estadoTabela[]=$estadoPostoTabela;
                            }

                        }
                        if (!empty($cidade_t)) {
                            $sql_tp = "SELECT estado,nome FROM tbl_cidade WHERE cidade = {$cidade_t};";
                            $res_tp = pg_query($con,$sql_tp);
                            $estadoCidadeTabela = pg_fetch_result($res_tp, 0, estado);
                            $cidadeCidadeTabela = pg_fetch_result($res_tp, 0, nome);

                            if (!in_array($cidadeCidadeTabela, $cidadeTabela)) {
                                $cidadeTabela[]=$cidadeCidadeTabela;
                            }

                            if (!in_array($estadoCidadeTabela, $estadoTabela)) {
                                $estadoTabela[]=$estadoCidadeTabela;
                            }                           
                        }
                    }
                    
                    $estadoTabela = implode(" / ", $estadoTabela);
                    $cidadeTabela = implode(" / ", $cidadeTabela);

                    if ($item['ativo'] == 't') {
                        $ativoTabela = 'Ativo';
                    }else{
                        $ativoTabela = 'Inativo';
                    }

                    ?>

                    <tr>
                        <td class="tac">
                            <a href="cadastro_roteiro_tecnico.php?roteiro=<?php echo $item['roteiro'];?>&edit=true" target="_blank"><?php echo $item['roteiro'] ?><a></td>
                        <td class="tac"><?php echo $roteiroTipo = $item['tipo_roteiro'] == "RA" ? "Roteiro Administrativo" : "Roteiro Técnico";?></td>
                        <td class="tac"><?php echo geraDataTimeNormal($item['data_inicio']);?></td>
                        <td class="tac"><?php echo geraDataTimeNormal($item['data_termino']);?></td>
                        <td class="tal"><?php echo $item["nome_tecnico"];?></td>
                        <td class="tac"><?php echo $estadoTabela ?></td>
                        <td class="tac"><?php echo $cidadeTabela ?></td>
                        <td class="tac"><?php echo $ativoTabela ?></td>
                        <td class="tac"><?php echo $item['status_descricao'] ?></td>
                        <td class="tac"><?php echo $item['solicitante'] ?></td>
                        <td class="tac"><?php echo $item['qtde_dias'] ?></td>
                        <!-- <td class="tac">
                            <a class="show-log btn btn-primary" href="#" data-object="roteiro" data-title="CADASTRO DE ROTEIRO" data-value="<?php echo $login_fabrica;?>*<?php echo $item['roteiro'];?>">Log</a>
                        </td> -->
                        <td class="tac" nowrap>
                            <button type="button" data-posicao="0" class="btn btn-info btn-mini  btn-mais-detalhes" data-id="<?php echo $item['roteiro'];?>">+ detalhes</button> 
                            <?php if (!in_array($item["status_roteiro"], array(3,4))) {?>
                            <a href="cancela_roteiro.php?roteiro=<?php echo $item['roteiro'];?>" class="btn btn-mini btn-danger">Cancelar Roteiro</a>
                            <?php }?>

                        </td>
                    </tr>
                    <tr data-posicao="0" class="tr_roteiro_<?php echo $item['roteiro'];?>" style="display: none;">
                        <td colspan="100%">
                            <table width="100%" class="table table-hover table-bordered table-fixed">
                                <tr>
                                    <td colspan="100%" style="background: #d90000;color:#ffffff;font-weight: bold;text-align: center;">AGENDA DE VISITAS</td>
                                </tr>
                                <tr>
                                    <th style="background: #cccccc !important;">Data Visita</th>
                                    <th style="background: #cccccc !important;">Tipo Visita</th>
                                    <th style="background: #cccccc !important;">Tipo Contato</th>
                                    <th style="background: #cccccc !important;"class="tal">Nome/ Razão</th>
                                    <?php if ($login_fabrica == 42) { ?>
                                        <th style="background: #cccccc !important;">Cidade</th>
                                    <?php } ?>
                                    <th style="background: #cccccc !important;">Qtde Horas</th>
                                   <!--  <th style="background: #cccccc !important;"class="tal">Contato</th> -->
                                    <th style="background: #cccccc !important;">Status</th>
                                    <th style="background: #cccccc !important;">Motivo Cancelamento</th>
                                    <th style="background: #cccccc !important;">Ações</th>
                                </tr>
                                <?php 
                                    $sql = "SELECT tbl_roteiro_posto.tipo_de_visita, 
                                                   tbl_roteiro_posto.qtde_horas,
                                                   tbl_roteiro_posto.status,
                                                   tbl_roteiro_posto.data_visita,
                                                   tbl_roteiro_posto.contato,
                                                   tbl_roteiro_visita.roteiro_visita,
                                                   tbl_roteiro_posto.roteiro_posto,
                                                   tbl_roteiro_posto.tipo_de_local,
                                                   tbl_roteiro_posto.motivo_reagendamento, 
                                                   CASE WHEN tbl_roteiro_posto.tipo_de_local = 'PA' THEN
                                                       tbl_posto.nome
                                                   WHEN tbl_roteiro_posto.tipo_de_local = 'CL' THEN
                                                       tbl_cliente.nome
                                                   WHEN tbl_roteiro_posto.tipo_de_local = 'RV' THEN       
                                                       tbl_revenda.nome
                                                   END  AS nome_contato,
                                                   CASE WHEN tbl_roteiro_posto.tipo_de_local = 'PA' THEN
                                                       tbl_posto_fabrica.codigo_posto
                                                   WHEN tbl_roteiro_posto.tipo_de_local = 'CL' THEN
                                                       tbl_cliente.codigo_cliente
                                                   WHEN tbl_roteiro_posto.tipo_de_local = 'RV' THEN       
                                                       tbl_revenda.cnpj 
                                                   END AS codigo_contato,
                                                   CASE WHEN tbl_roteiro_posto.tipo_de_local = 'PA' THEN
                                                       tbl_posto_fabrica.contato_cidade 
                                                   WHEN tbl_roteiro_posto.tipo_de_local = 'CL' THEN
                                                       cd_c.nome
                                                   WHEN tbl_roteiro_posto.tipo_de_local = 'RV' THEN       
                                                       cd_r.nome
                                                   END AS cidade
                                              FROM tbl_roteiro_posto
                                         LEFT JOIN tbl_roteiro_visita ON tbl_roteiro_visita.roteiro_posto=tbl_roteiro_posto.roteiro_posto 
                                         LEFT JOIN tbl_cliente ON tbl_cliente.cpf = tbl_roteiro_posto.codigo 
                                         LEFT JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_roteiro_posto.codigo 
                                         LEFT JOIN tbl_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo 
                                         LEFT JOIN tbl_cidade cd_r ON tbl_revenda.cidade = cd_r.cidade
                                         LEFT JOIN tbl_cidade cd_c ON tbl_cliente.cidade = cd_c.cidade
                                         LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto  AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                             WHERE tbl_roteiro_posto.roteiro = " . $item['roteiro'] . " 
                                               AND tipo_de_local IS NOT NULL  
                                               ORDER BY Tbl_roteiro_posto.data_visita asc";
                                        $res = pg_query($con, $sql);

                                        foreach (pg_fetch_all($res) as $key => $rows) {
                                ?>
                                <tr>
                                    <td class="tac"><?php echo geraDataNormal($rows["data_visita"]);?></td>
                                    <td class="tac"><?php echo getLegendaTipoVisita($rows["tipo_de_visita"]);?></td>
                                    <td class="tac"><?php echo getLegendaTipoContato($rows["tipo_de_local"]);?></td>
                                    <td><?php echo $rows["codigo_contato"];?> - <?php echo $rows["nome_contato"];?></td>
                                    <?php if ($login_fabrica == 42) { ?>
                                            <td class="tac"><?=$rows["cidade"]?></td>
                                    <?php } ?>
                                    <td class="tac"><?php echo $rows["qtde_horas"];?></td>
                                    <!-- <td><?php //echo $rows["contato"];?></td> -->
                                    <td class="tac"><?php echo getLegendaStatus($rows["status"]);?></td>
                                    <td class="tac"><?php echo $rows["motivo_reagendamento"];?></td>
                                    <td class="tac">
                                        <?php if (strlen($rows["roteiro_visita"]) > 0) {?>
                                        <button type="button" data-id="<?php echo $rows["roteiro_posto"];?>" class="btn btn-warning btn-mini btn-ver-detalhe">Ver detalhes</button>
                                        <a href="realizar_visita.php?xvisita=<?php echo $rows["roteiro_visita"];?>&visita=<?php echo $rows["roteiro_posto"];?>" class="btn btn-mini btn-info">Editar Visita</a>
                                        <?php } else {?>
                                        <?php if (in_array($rows["status"], array("CF","AC")) AND strlen($rows["roteiro_visita"]) == 0) {?>
                                        <a href="realizar_visita.php?visita=<?php echo $rows["roteiro_posto"];?>" class="btn btn-mini btn-success">Realizar Visita</a>
                                        <a href="reagendar_visita.php?visita=<?php echo $rows["roteiro_posto"];?>" class="btn btn-mini btn-primary">Reagendar Visita</a>
                                        <?php }?>
                                        <?php }?>
                                        <?php if (!in_array($rows["status"], array("CC","OK")) AND strlen($rows["roteiro_visita"]) == 0) {?>
                                        <a href="cancela_visita.php?visita=<?php echo $rows["roteiro_posto"];?>" class="btn btn-mini btn-danger">Cancelar Visita</a>
                                        <?php }?>
                                    </td>
                                </tr>
                            <?php }?>
                                <tr>
                                    <td colspan="100%" align="center" class="tac">
                                        <button type="button" data-roteiro="<?php echo $item['roteiro'];?>" class="btn btn-primary btn-add-visita-avulsa">Adicionar Visita Avulsa</button></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div> 
<?php } ?>
<?php include 'rodape.php';?>
