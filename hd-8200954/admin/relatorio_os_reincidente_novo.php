<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";

include "autentica_admin.php";
include "funcoes.php";
include "../helpdesk/mlg_funciones.php";

function procura_auditoria_tipo($auditorias, $tipo, $observacao = null) {
    $achou = false;

    foreach ($auditorias as $row => $auditoria) {
        if (is_null($observacao) && $auditoria[$tipo] == "t") {
            $achou = true;
            break;
        } else if ($auditoria[$tipo] == "t" && strpos(strtolower($auditoria["observacao"]), strtolower($observacao))) {
            $achou = true;
            break;
        }
    }

    return $achou;
}

if ($_POST) { 
    $msg_erro = array(
        "msg"    => array(), 
        "campos" => array()
    );

    $data_inicial = $_POST["data_inicial"];
    $data_final   = $_POST["data_final"];

    if (empty($data_inicial) || empty($data_final)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data_inicial";
        $msg_erro["campos"][] = "data_final";
    } else {
        list($dia, $mes, $ano) = explode("/", $data_inicial);

        if (!strtotime("$ano-$mes-$dia")) {
            $msg_erro["msg"][]    = "Data inicial inválida";
            $msg_erro["campos"][] = "data_inicial";
        } else {
            $data_inicial = "$ano-$mes-$dia";
        }

        list($dia, $mes, $ano) = explode("/", $data_final);

        if (!strtotime("$ano-$mes-$dia")) {
            $msg_erro["msg"][]    = "Data final inválida";
            $msg_erro["campos"][] = "data_final";
        } else {
            $data_final = "$ano-$mes-$dia";
        }

        if (strtotime("{$data_inicial} +6 months") < strtotime($data_final)) {
            $msg_erro["msg"][]    = "O limite de pesquisa entre as datas é de 6 meses";
            $msg_erro["campos"][] = "data_final";
            $msg_erro["campos"][] = "data_inicial";
        }
    }

    $estado   = $_POST["estado"];
    $posto_id = $_POST["posto_id"];

    if (empty($msg_erro["msg"])) {
        if (!empty($estado)) {
            $whereEstado = "AND LOWER(posto_fabrica.contato_estado) = LOWER('{$estado}')";
        }

        if (!empty($posto_id)) {
            $wherePosto = "AND posto_fabrica.posto = {$posto_id}";
        }

        if ($login_fabrica == 158) {
		$colunaClienteAdmin   = "cliente_admin.codigo AS \"Cliente Admin\", ";
		$colunaPatrimonio     = "os_extra.serie_justificativa AS \"Patrimônio\", ";
            $leftJoinHdChamado    = "LEFT JOIN tbl_hd_chamado AS hd_chamado ON hd_chamado.hd_chamado = os.hd_chamado AND hd_chamado.fabrica = {$login_fabrica}";
            $leftJoinClienteAdmin = "LEFT JOIN tbl_cliente_admin AS cliente_admin ON cliente_admin.cliente_admin = hd_chamado.cliente_admin AND cliente_admin.fabrica = {$login_fabrica}";
	}

	if($login_fabrica == 35){
		$colunaConsumidorNome = "os.consumidor_nome AS \"Nome Cliente\", ";
	}

        $sql = "
            SELECT DISTINCT
                os.os AS \"OS\",
                TO_CHAR(os.data_abertura, 'DD/MM/YYYY') AS \"Data Abertura\",
                TO_CHAR(os.data_conserto, 'DD/MM/YYYY') AS \"Data Conserto\",
		TO_CHAR(os.data_fechamento, 'DD/MM/YYYY') AS \"Data Fechamento\",
		{$colunaConsumidorNome}
                {$colunaClienteAdmin}
                (posto_fabrica.codigo_posto || ' - ' || posto.nome) AS \"Posto\",
                tipo_atendimento.descricao AS \"Tipo de Atendimento\",
                (produto.referencia || ' - ' || produto.descricao) AS \"Produto\",
                os_produto.serie AS \"Série\",
                $colunaPatrimonio
		os_extra.os_reincidente AS \"OS Reincidente\"
            FROM tbl_os AS os
            INNER JOIN tbl_posto_fabrica AS posto_fabrica ON posto_fabrica.posto = os.posto AND posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto AS posto ON posto.posto = posto_fabrica.posto
            INNER JOIN tbl_os_produto AS os_produto ON os_produto.os = os.os
            INNER JOIN tbl_produto AS produto ON produto.produto = os_produto.produto AND produto.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_os_extra AS os_extra ON os_extra.os = os.os
            LEFT JOIN tbl_os_campo_extra AS os_campo_extra ON os_campo_extra.os = os.os AND os_campo_extra.fabrica = {$login_fabrica}
            INNER JOIN tbl_tipo_atendimento AS tipo_atendimento ON tipo_atendimento.tipo_atendimento = os.tipo_atendimento AND tipo_atendimento.fabrica = {$login_fabrica}
            {$leftJoinHdChamado}
            {$leftJoinClienteAdmin}
            WHERE os.fabrica = {$login_fabrica}
            AND os.data_abertura BETWEEN '{$data_inicial}' AND '{$data_final}'
            AND os.os_reincidente IS TRUE
            AND os.excluida IS NOT TRUE
            {$whereEstado}
            {$wherePosto}
            ORDER BY os.os DESC
        ";
        $resPesquisa = pg_query($con, $sql);

        if (!pg_num_rows($resPesquisa)) {
            $msg_erro["msg"][] = "Não foram encontradas Ordens de Serviço Reincidentes";
        }
    }
}

$layout_menu = "auditoria";
$title       = "RELATÓRIO DE OS REINCIDENTE";

include "cabecalho_new.php";
?>

<!-- HTML -->
<?php
if (count($msg_erro["msg"]) > 0) { 
?>
    <br />

    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>

    <br />
<?php
} 
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>

    <br />

    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span2">
            <div class="control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="data_inicial" >Data Inicial</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="data_inicial" class="span12" name="data_inicial" value="<?=getValue('data_inicial')?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array('data_final', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="data_final" >Data Final</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="data_final" class="span12" name="data_final" value="<?=getValue('data_final')?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="estado" >Estado</label>
                <div class="controls control-row">
                    <select id="estado" name="estado" class="span12" >
                        <option value="" >Selecione</option>
                        <?php
                        foreach ($array_estados() as $sigla => $estado_nome) {
                            $selected = ($estado == $sigla) ? "selected" : "";

                            echo "<option value='{$sigla}' {$selected} >{$estado_nome}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <?php
    if (strlen(getValue("posto_id")) > 0) {
        $posto_input_readonly     = "readonly";
        $posto_span_rel           = "trocar_posto";
        $posto_input_append_icon  = "remove";
        $posto_input_append_title = "title='Trocar Posto'";
    } else {
        $posto_input_readonly     = "";
        $posto_span_rel           = "lupa";
        $posto_input_append_icon  = "search";
        $posto_input_append_title = "";
    }
    ?>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span3" >
            <div class="control-group" >
                <label class="control-label" for="posto_codigo" >Código do Posto</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input id="posto_codigo" name="posto_codigo" class="span12" type="text" value="<?=getValue('posto_codigo')?>" <?=$posto_input_readonly?> />
                        <span class="add-on" rel="<?=$posto_span_rel?>" >
                            <i class="icon-<?=$posto_input_append_icon?>" <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        <input type="hidden" id="posto_id" name="posto_id" value="<?=getValue('posto_id')?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="posto_nome" >Nome do Posto</label>
                <div class="controls controls-row" >
                    <div class="span10 input-append" >
                        <input id="posto_nome" name="posto_nome" class="span12" type="text" value="<?=getValue('posto_nome')?>" <?=$posto_input_readonly?> />
                        <span class="add-on" rel="<?=$posto_span_rel?>" >
                            <i class="icon-<?=$posto_input_append_icon?>" <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br />

    <p>
        <button class="btn" type="submit" name="acao" value="pesquisar" >Pesquisar</button>
    </p>

    <br />
</form>
</div>

<?php
if (pg_num_rows($resPesquisa) > 0) {
    $csvPesquisa = array2csv($resPesquisa, ";", true, false);
    
    $resPesquisa = array_map(function($r) {
        $r["OS"] = createHTMLLink(
            "os_press.php?os={$r['OS']}",
            $r["OS"],
            "target='_blank'"
        );

        $r["OS Reincidente"] = createHTMLLink(
            "os_press.php?os={$r['OS Reincidente']}",
            $r["OS Reincidente"],
            "target='_blank'"
        );

        return $r;
    }, pg_fetch_all($resPesquisa));

    echo array2table(array_merge(
        array(
            "attrs" => array(
                "caption"      => "Rélatório de OS Reincidente",
                "captionAttrs" => "class='titulo_coluna'",
                "tableAttrs"   => "class='table table-bordered table-striped resultado-pesquisa'",
                "headerAttrs"  => "class='titulo_coluna'"
            )
        ),
        $resPesquisa
    ));

    $csv  = "relatorio-os-reincidente-{$login_fabrica}-{$login_admin}-".date("YmdHi").".csv";
    $file = fopen("/tmp/".$csv, "w");
    fwrite($file, $csvPesquisa);
    fclose($file);
    system("mv /tmp/{$csv} xls/{$csv}");

    echo "
        <p class='tac' >
            <button type='button' class='btn btn-success download-csv' data-csv='{$csv}' ><i class='icon-download-alt icon-white' ></i> Download CSV</button>
        </p>
    ";
}

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "select2",
   "tooltip",
   "dataTable"
);

include "plugin_loader.php";

?>

<!-- CSS -->
<style>

table.resultado_pesquisa {
    margin: 0 auto;
    width: 1280px;
}

</style>

<!-- JavaScript -->
<script>
    
Shadowbox.init();
$("select").select2();
$("#data_inicial, #data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

$(document).on("click", "span[rel=lupa]", function() {
    $.lupa($(this));
});

$(document).on("click", "span[rel=trocar_posto]", function() {
    $("#posto_id, #posto_codigo, #posto_nome").val("");

    $("#posto_codigo, #posto_nome")
    .prop({ readonly: false })
    .next("span[rel=trocar_posto]")
    .attr({ rel: "lupa" })
    .find("i")
    .removeClass("icon-remove")
    .addClass("icon-search")
    .removeAttr("title");
});

function retorna_posto(retorno) {
    $("#posto_id").val(retorno.posto);
    $("#posto_codigo").val(retorno.codigo);
    $("#posto_nome").val(retorno.nome);

    $("#posto_codigo, #posto_nome")
    .prop({ readonly: true })
    .next("span[rel=lupa]")
    .attr({ rel: "trocar_posto" })
    .find("i")
    .removeClass("icon-search")
    .addClass("icon-remove")
    .attr({ title: "Trocar Posto" });
}

<?php
if (count($resPesquisa) > 0) {
?>
    $.dataTableLoad({ table: "table.resultado-pesquisa" });

    $("button.download-csv").on("click", function() {
        var csv = $(this).data("csv");

        window.open("xls/"+csv);
    });
<?php
}
?>

</script>

<?php

include "rodape.php";

?>
