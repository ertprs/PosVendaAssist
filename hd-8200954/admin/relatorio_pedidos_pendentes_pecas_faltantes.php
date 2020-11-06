<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia,call_center";
include "autentica_admin.php";
include "funcoes.php";

if ($_POST["btn_acao"] == "submit") {
    $codigo_posto    = $_POST["codigo_posto"];
	$descricao_posto = $_POST["descricao_posto"];
    $estado_posto    = $_POST["estado_posto"];
    
    if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "
            SELECT tbl_posto_fabrica.posto
            FROM tbl_posto
            JOIN tbl_posto_fabrica USING(posto)
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND (
                (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
                OR
                (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
            )
        ";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
            $wherePosto = "AND tbl_pedido.posto = {$posto}";
		}
	}
    
    if (!empty($estado_posto)) {
        $whereEstadoPosto = "AND tbl_posto_fabrica.contato_estado = '{$estado_posto}'";
    }
}

$sql = "
    SELECT 
        tbl_pedido.pedido,
        TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY HH24:MI') AS data,
        tbl_tipo_pedido.descricao AS tipo_pedido,
        tbl_status_pedido.descricao AS status_pedido,
        tbl_posto_fabrica.codigo_posto,
        tbl_posto.nome AS nome_posto,
        tbl_pedido_item.peca,
        tbl_peca.referencia AS referencia_peca,
        tbl_peca.descricao AS descricao_peca,
        (tbl_pedido_item.qtde - (COALESCE(tbl_pedido_item.qtde_faturada, 0) + COALESCE(tbl_pedido_item.qtde_cancelada, 0))) AS qtde_pendente
    FROM tbl_pedido
    INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$login_fabrica}
    INNER JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
    INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
    INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
    WHERE tbl_pedido.fabrica = {$login_fabrica}
    AND tbl_pedido.status_pedido IN(2, 5, 1)
    AND (tbl_pedido_item.qtde - (COALESCE(tbl_pedido_item.qtde_faturada, 0) + COALESCE(tbl_pedido_item.qtde_cancelada, 0))) > 0
    {$wherePosto}
    {$whereEstadoPosto}
    ORDER BY tbl_pedido.data ASC, tbl_pedido_item.peca ASC, tbl_pedido.posto ASC
";
$resSubmit = pg_query($con, $sql);

 if ($_POST["gerar_excel"] && isset($resSubmit) && pg_num_rows($resSubmit) > 0) {
    $arquivo = "relatorio_pedidos_pendentes_pecas_faltantes-".date("d-m-Y-H:i").".csv";
    system("touch /tmp/{$arquivo}");
    file_put_contents("/tmp/{$arquivo}", "'PEDIDO';'DATA';'TIPO';'STATUS';'CÓDIGO POSTO';'NOME POSTO';'REFERÊNCIA PEÇA';'DESCRIÇÃO PEÇA';'QUANTIDADE'\n", FILE_APPEND);
    
    while ($r = pg_fetch_object($resSubmit)) {
        file_put_contents("/tmp/{$arquivo}", "'{$r->pedido}';'{$r->data}';'{$r->tipo_pedido}';'{$r->status_pedido}';'{$r->codigo_posto}';'{$r->nome_posto}';'{$r->referencia_peca}';'{$r->descricao_peca}';'{$r->qtde_pendente}'\n", FILE_APPEND);
    }
    
    system("mv /tmp/{$arquivo} xls/{$arquivo}");
    exit("xls/{$arquivo}");
}

$layout_menu = "callcenter";
$title = "Pedidos pendentes com peça faltante";
include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "dataTable",
    "select2"
);

include("plugin_loader.php");

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<form method="POST" class="form-search form-inline tc_formulario" style="margin: 0 auto;" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>
	<br/>
    
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span3">
            <div class="control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>">
                <label class="control-label" for="codigo_posto">Código Posto</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input type="text" name="codigo_posto" id="codigo_posto" class="span12" value="<?=getValue('codigo_posto')?>" >
                        <span class="add-on" rel="lupa"><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>">
                <label class="control-label" for="descricao_posto">Nome Posto</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input type="text" name="descricao_posto" id="descricao_posto" class="span12" value="<?=getValue('descricao_posto')?>" >
                        <span class="add-on" rel="lupa"><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span3">
            <div class="control-group">
                <label class="control-label" for="estado_posto">Estado Posto</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <select id="estado_posto" name="estado_posto" >
                            <option value="" >Selecione</option>
                            <?php
                            foreach ($array_estados as $sigla => $nome) {
                                $selected = (getValue("estado_posto") == $sigla) ? "selected" : "";
                                echo "<option value='{$sigla}' {$selected} >{$nome}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <p><br/>
        <button class="btn" id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type="hidden" id="btn_click" name="btn_acao" />
    </p><br/>
</form>
<br />

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        $total_pecas = array();
        ?>
        <div id="grafico" ></div>
        <br />
        <table id="resultado_pesquisa" class="table table-striped table-bordered table-hover table-fixed" >
            <thead>
                <tr class="titulo_coluna" >
                    <th>Pedido</th>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Posto</th>
                    <th>Peça</th>
                    <th>Quantidade</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($r = pg_fetch_object($resSubmit)) {
                    if (!isset($total_pecas[$r->peca])) {
                        $total_pecas[$r->peca] = array(
                            "name" => utf8_encode("{$r->referencia_peca} - {$r->descricao_peca}"),
                            "y" => $r->qtde_pendente
                        );
                    } else {
                        $total_pecas[$r->peca]["y"] += $r->qtde_pendente;
                    }
                    echo "
                        <tr>
                            <td><a href='pedido_admin_consulta.php?pedido={$r->pedido}' target='_blank' >{$r->pedido}</a></td>
                            <td>{$r->data}</td>
                            <td>{$r->tipo_pedido}</td>
                            <td>{$r->status_pedido}</td>
                            <td>{$r->codigo_posto} - {$r->nome_posto}</td>
                            <td>{$r->referencia_peca} - {$r->descricao_peca}</td>
                            <td class='tac' >{$r->qtde_pendente}</td>
                        </tr>
                    ";
                }
                
                $json_grafico = array();
                
                foreach ($total_pecas as $i => $peca) {
                    $peca["y"] = (int) $peca["y"];
                    $json_grafico[] = $peca;
                }
                
                $json_grafico = json_encode($json_grafico);
                ?>
            </tbody>
        </table>
        
        <br />
        
        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?=excelPostToJson($_POST)?>' />
            <span><img src='imagens/excel.png' /></span>
            <span class="txt">Gerar Arquivo CSV</span>
        </div>
    <?php
    } else {
    ?>
        <div class="container" ><div class="alert alert-danger" ><h4>Nenhum resultado encontrado</h4></div></div>
    <?php
    }
}
?>

<script src="plugins/highcharts/highcharts_4.2.5.js" ></script>
<script>

Shadowbox.init();
$.autocompleteLoad(["posto"]);
$("#estado_posto").select2();
$("span[rel=lupa]").on("click", function () { $.lupa($(this)); });

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

<?php
if (isset($resSubmit) && pg_num_rows($resSubmit) > 0) {
?>
    $.dataTableLoad({ table: "#resultado_pesquisa" });
    
    Highcharts.chart("grafico", {
       chart: {
           type: "pie"
       },
       title: {
           text: "Quantidade pendente de peças faltantes"
       },
       plotOptions: {
           pie: {
               cursor: "pointer",
               dataLabels: {
                   enabled: true,
                   format: '<b>{point.name}</b>: {point.y}',
               }
           },
           series: {
               point: {
                   events: {
                       click: function() {
                           $("#resultado_pesquisa_filter input").val(this.name).trigger("keyup");
                       }
                   }
               }
           }
       },
       series: [{
           name: "Quantidade pendente",
           data: <?=$json_grafico?>
       }]
    });
<?php
}
?>

</script>

<br />

<?php
include "rodape.php";