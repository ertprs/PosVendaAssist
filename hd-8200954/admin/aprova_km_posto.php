<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="auditoria";

include "autentica_admin.php";
include "funcoes.php";

$array_reincidencias = array(98,99,100,101,161,162);

if ($_POST["btn_acao"] == "submit") {
    $os                 = $_POST['os'];
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $aprova             = $_POST['aprova'];
    $codigo_posto       = $_POST['codigo_posto'];
    $descricao_posto    = $_POST['descricao_posto'];
    $todas              = $_POST['todas'];

    if(strlen($todas) == 0){
        if ((in_array($aprova, array("aprovadas", "reprovadas")) && (empty($data_inicial) || empty($data_final))) && empty($os)) {
            $msg_erro["msg"][] = "Para realizar está pesquisa é necessário informar a data incial";
            $msg_erro["campos"][] = "data";
        }else{
            if(empty($data_inicial) && empty($data_final)){
                $aux_data_inicial = "";
                $aux_data_final = "";
            }
            list($di, $mi, $yi) = explode("/", $data_inicial);
            list($df, $mf, $yf) = explode("/", $data_final);

            if ((!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) && strlen($os) == 0) {
                if(!empty($data_inicial) && !empty($data_final)){
                    $msg_erro["msg"][]    = "Data Inválida";
                    $msg_erro["campos"][] = "data";
                }
            } else {
                $aux_data_inicial = "{$yi}-{$mi}-{$di}";
                $aux_data_final   = "{$yf}-{$mf}-{$df}";

                if (strtotime($aux_data_final) < strtotime($aux_data_inicial) && (!empty($data_inicial) && !empty($data_final))) {
                    $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                    $msg_erro["campos"][] = "data";
                }
            }
        }

        if (strlen($os)>0){
            $Xos = " AND tbl_os.sua_os = '$os' ";
        }

        if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
            $sql = "SELECT tbl_posto_fabrica.posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica USING(posto)
                    WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                    AND (
                        (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
                        OR
                        (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
                    )";
            $res = pg_query($con ,$sql);

            if (!pg_num_rows($res)) {
                $msg_erro["msg"][]    = "Posto não encontrado";
                $msg_erro["campos"][] = "posto";
            } else {
                $posto = pg_fetch_result($res, 0, "posto");
                $sql_posto .= " AND tbl_posto_fabrica.posto = $posto
                                AND tbl_posto_fabrica.fabrica = $login_fabrica
                ";
            }
        }

        if ((in_array($aprova, array("aprovadas", "reprovadas")) && (empty($data_inicial) || empty($data_final))) && empty($os)) {
            $msg_erro["msg"][] = "Para realizar está pesquisa é necessário informar a data incial e data final";
            $msg_erro["campos"][] = "data";
        }

        if(strlen($aprova) == 0){
            $aprova = "aprovacao";
            $aprovacao = "98";
        }elseif($aprova=="aprovacao"){
            $aprovacao = "98";
        }elseif($aprova=="aprovadas"){
            $aprovacao = "99, 100";
        }elseif($aprova=="reprovadas"){
            $aprovacao = "101";
        }
    }else{
        $aux_data_inicial   = date("Y-m-01");
        $aux_data_final     = date("Y-m-d");
        $aprovacao          = "98,99,100,101";
        if (strlen($data_inicial) or strlen($data_final)){
            list($di, $mi, $yi) = explode("/", $data_inicial);
            list($df, $mf, $yf) = explode("/", $data_final);

            if ((!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf))) {
                $msg_erro["msg"][]    = "Data Inválida";
                $msg_erro["campos"][] = "data";
            } else {
                $x_data_inicial = "{$yi}-{$mi}-{$di}";
                $x_data_final   = "{$yf}-{$mf}-{$df}";

                if (strtotime($x_data_final) < strtotime($x_data_inicial)) {
                    $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                    $msg_erro["campos"][] = "data";
                }
                if (strtotime($x_data_inicial) < strtotime($aux_data_inicial)) {
                    $msg_erro["msg"][]    = "Data Inicial não pode ser menor que o primeiro dia do mês";
                    $msg_erro["campos"][] = "data";
                }else{
                    $aux_data_inicial = $x_data_inicial;
                }
                if (strtotime($x_data_final) > strtotime($aux_data_final)) {
                    $msg_erro["msg"][]    = "Data Final não pode ser maior que a data atual";
                    $msg_erro["campos"][] = "data";
                }else{
                    $aux_data_final = $x_data_final;
                }
            }
        }
    }

    if(count($msg_erro["msg"]) == 0){
        $sql =  "SELECT interv.os
                INTO TEMP tmp_interv_$login_admin
                FROM (
                    SELECT
                    ultima.os,
                    (
                        SELECT status_os
                        FROM tbl_os_status
                        WHERE status_os IN (" . implode(',', $array_reincidencias) . ")
                        AND tbl_os_status.os = ultima.os AND tbl_os_status.fabrica_status = $login_fabrica
                        ORDER BY data DESC LIMIT 1
                    ) AS ultimo_status
                    FROM (
                        SELECT DISTINCT os
                        FROM tbl_os_status
                        WHERE status_os IN (" . implode(',', $array_reincidencias) . ")
                        $cond_auditoria
                        AND tbl_os_status.fabrica_status = $login_fabrica
                    ) ultima
                ) interv
                WHERE interv.ultimo_status IN ($aprovacao);

                CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

                /* select os from  tmp_interv_$login_admin; */

                SELECT  tbl_os.posto,
                        tbl_posto.nome                     AS posto_nome
                FROM    tmp_interv_$login_admin X
                JOIN    tbl_os                          ON  tbl_os.os = X.os
                JOIN    tbl_produto                     ON  tbl_produto.produto                                     = tbl_os.produto
                JOIN    tbl_posto                       ON  tbl_os.posto                                            = tbl_posto.posto
                JOIN    tbl_posto_fabrica               ON  tbl_posto.posto                                         = tbl_posto_fabrica.posto
                                                        AND tbl_posto_fabrica.fabrica                               = $login_fabrica
        LEFT JOIN    tbl_defeito_constatado          ON  tbl_defeito_constatado.defeito_constatado               = tbl_os.defeito_constatado
        LEFT JOIN    tbl_defeito_constatado_grupo    ON  tbl_defeito_constatado_grupo.defeito_constatado_grupo   = tbl_defeito_constatado.defeito_constatado_grupo
                WHERE   tbl_os.fabrica = $login_fabrica
                    $sql_add
                    $Xos
                    $sql_posto
            ";
        if(strlen($os) > 0){
            $sql .= "
                AND     tbl_os.os = $os
                ";
        }else{
            if (strlen($aux_data_inicial) > 0 AND strlen($aux_data_final) > 0) {
                $sql .= "
                AND     tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                ";
            }
            $sql .= "
        GROUP BY      tbl_os.posto,
                        tbl_posto.nome
        ORDER BY      tbl_posto.nome
                ";
        }
        $resSubmit = pg_query($con,$sql);
    }
}


$layout_menu = "auditoria";
$title = "APROVAÇÃO / REPROVAÇÃO DE DESLOCAMENTO DE KM POR POSTO.";
include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "mask",
    "dataTable",
    "shadowbox"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
$(function() {
    var hora = new Date();
    var engana = hora.getTime();

    $.datepickerLoad(Array("data_inicial","data_final"));
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init({
        handleOversize: "drag"
    });

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $("input[type=checkbox]").click(function(){
        if($(this).is(":checked")){
            $("#os").attr("disabled",true).val("");
            $("#codigo_posto").attr("disabled",true).val("");
            $("#descricao_posto").attr("disabled",true).val("");
            $("input[type=radio]").attr("disabled",true).val("");
        }else{
            $("#os").attr("disabled",false);
            $("#codigo_posto").attr("disabled",false);
            $("#descricao_posto").attr("disabled",false);
            $("input[type=radio]").attr("disabled",false);
        }
    });

    if($("input[type=checkbox]").is(":checked")){
        $("#os").attr("disabled",true).val("");
        $("#codigo_posto").attr("disabled",true).val("");
        $("#descricao_posto").attr("disabled",true).val("");
        $("input[type=radio]").attr("disabled",true).val("");
    }else{
        $("#os").attr("disabled",false);
        $("#codigo_posto").attr("disabled",false);
        $("#descricao_posto").attr("disabled",false);
        $("input[type=radio]").attr("disabled",false);
    }
});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function abreOsIntervencaoKm(posto,os,data_inicial,data_final,aprova){
    Shadowbox.open({
        content: "aprova_km_posto_janela.php?posto="+posto+"&os="+os+"&data_inicial="+data_inicial+"&data_final="+data_final+"&aprova="+aprova,
        player: "iframe",
        title:  "Km de OS por posto",
        width:  1024,
        height: 500
    });
}
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<form name='frm_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <input type="hidden" name="acao"  value="pesquisar">
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label' for='os'>Número da OS</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="os" name="os" class='span12' maxlength="20" value="<? echo $os ?>" >
                        </div>
                    </div>
                </div>
            </div>
        <div class="span2"></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <label class='control-label' >Mostrar as OS:</label>
            <div class='controls controls-row'>
                <div class='span12 input-append'>
                    <label class="radio">
                        <input type="radio" name="aprova" value="aprovacao" <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>
                        Em Aprovação
                    </label>
                    <label class="radio">
                        <input type="radio" name="aprova" value="aprovadas" <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>
                        Aprovadas
                    </label>
                    <label class="radio">
                        <input type="radio" name="aprova" value="reprovadas" <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>
                        Reprovadas
                    </label>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <label class='control-label' for='todas'>Todas Solicitações</label>
            <div class='controls controls-row'>
                <div class='span12 input-append'>
                    <input type="checkbox" name="todas" id="todas" value="sim" <? if(trim($todas) == 'sim') echo "checked='checked'"; ?>/>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
?>
<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' >
    <thead>
        <tr class='titulo_coluna' >
            <th>Posto</th>
        </tr>
    </thead>
    <tbody>
<?php
        for($i=0;$i<$count;$i++){
            $posto      = pg_fetch_result($resSubmit, $i, 'posto');
            $posto_nome = pg_fetch_result($resSubmit, $i, 'posto_nome');
?>
        <tr>
            <td class='tal'><a style="cursor:pointer" onclick="javascript:abreOsIntervencaoKm(<?=$posto?>,'<?=$os?>','<?=$aux_data_inicial?>','<?=$aux_data_final?>','<?=$aprova?>');"><?=$posto_nome?></a></td>
        </tr>
<?php
        }
    }else{
?>
        <div class="container">
            <div class="alert">
                <h4>Nenhum resultado encontrado</h4>
            </div>
        </div>
<?php
    }
?>
    </tbody>
</table>

<?php
}
include 'rodape.php';
?>