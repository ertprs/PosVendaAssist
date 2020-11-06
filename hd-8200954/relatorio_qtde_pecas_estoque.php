<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

//$admin_privilegios = "call_center";

include "autentica_usuario.php";
include "funcoes.php";

if ($_POST["acao"]) {
	$msg_erro = array(
        "msg"    => array(),
        "campos" => array()
    );

    $peca_referencia = $_POST["peca_referencia"];

    if(strlen(trim($peca_referencia)) > 0){
        $sql_peca = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$peca_referencia'";
        $res_peca = pg_query($con, $sql_peca);
        $peca_id = pg_fetch_result($res_peca,0,peca);
    }


    if (empty($msg_erro["msg"])) {
        if (!empty($peca_id)) {
            $wherePeca = "AND tbl_estoque_posto.peca = {$peca_id}";
        }

        $sql = "SELECT tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome AS nome_posto,
					tbl_distribuidor_sla.unidade_negocio,
					tbl_peca.referencia AS peca_referencia,
					tbl_peca.descricao AS peca_descricao,
					tbl_estoque_posto.qtde
					FROM tbl_estoque_posto
					JOIN tbl_posto ON tbl_posto.posto = tbl_estoque_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto_distribuidor_sla_default ON tbl_posto_distribuidor_sla_default.posto = tbl_posto_fabrica.posto
					AND tbl_posto_distribuidor_sla_default.fabrica = $login_fabrica
					JOIN tbl_distribuidor_sla ON tbl_distribuidor_sla.distribuidor_sla = tbl_posto_distribuidor_sla_default.distribuidor_sla
					JOIN tbl_peca ON tbl_peca.peca = tbl_estoque_posto.peca AND tbl_peca.fabrica = $login_fabrica
					WHERE tbl_estoque_posto.fabrica = $login_fabrica
					AND tbl_estoque_posto.posto = $login_posto
					$wherePeca";
		$resQtdeEstoque = pg_query($con, $sql);
		if (pg_num_rows($resQtdeEstoque) == 0) {
            $msg_erro["msg"][] = "Nenhum resultado encontrado";
    	}
	}
}

$layout_menu = "tecnica";
$title       = "RELATÓRIO DE QTDE PEÇAS ESTOQUE";

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "dataTable"
);

include __DIR__.'/admin/plugin_loader.php';

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

<form method="POST" class="form-search form-inline tc_formulario" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>
    <br />
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="peca_referencia" >Referência da Peça</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input id="peca_referencia" name="peca_referencia" class="span12" type="text" value="<?=getValue('peca_referencia')?>" <?=$peca_input_readonly?> />
                        <span class="add-on" rel="lupa" >
                            <i class="icon-search"></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="peca_descricao" >Descrição da Peça</label>
                <div class="controls controls-row" >
                    <div class="span10 input-append" >
                        <input id="peca_descricao" name="peca_descricao" class="span12" type="text" value="<?=getValue('peca_descricao')?>" <?=$peca_input_readonly?> />
                        <span class="add-on" rel="lupa" >
                            <i class="icon-search"></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>

    <br />

    <p>
        <button class="btn" type="submit" name="acao" value="pesquisar" >Pesquisar</button>
    </p>

    <br />
</form>


<?php
if (pg_num_rows($resQtdeEstoque) > 0) {
	$resQtdeEstoque = pg_fetch_all($resQtdeEstoque);
	ob_start();
    ?>
    <table class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
        	<tr>
                <th class="titulo_coluna" nowrap >Posto Autorizado</th>
                <td colspan="12"><?=$resQtdeEstoque[0]["codigo_posto"]?> - <?=$resQtdeEstoque[0]["nome_posto"]?></td>
            </tr>
            <tr>
                <th class="titulo_coluna" nowrap >Centro Distribuidor</th>
                <td colspan="12"><?=$resQtdeEstoque[0]["unidade_negocio"]?></td>
            </tr>
            <tr class="titulo_coluna" >
            	<th>Referência da Peça</th>
                <th>Descrição da Peça</th>
                <th>Estoque</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($resQtdeEstoque as $row) {
                $row = (object) $row;
                ?>
                <tr>
                	<td class="tac"><?=$row->peca_referencia?></td>
                    <td class="tac"><?=$row->peca_descricao?></td>
                    <td class="tac" ><?=$row->qtde?></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <script>
		$.dataTableLoad({ table: "#relatorio_qtde_pecas" });
	</script>
    <?php

    	$csv  = "relatorio-qtde-pecas-estoque-{$login_fabrica}-".date("YmdHi").".csv";
        $file = fopen("/tmp/".$csv, "w");
        $rows_csv = $resQtdeEstoque;
        $titulo = array(
            'Código Posto',
            'Nome Posto',
            'Centro Distribuidor',
            'Referência da Peça',
            'Descrição da Peça',
            'Estoque'
        );

        fwrite($file, $titulo);
        $linhas = implode(";", $titulo)."\r\n";
        foreach ($rows_csv as $key => $value) {
            $linhas .= implode(";", $value)."\r\n";
        }

        fwrite($file, $linhas);
        fclose($file);
        if(file_exists("/tmp/{$csv}")) {

			system("mv /tmp/{$csv} xls/{$csv}");

		}

	    if (file_exists("xls/{$csv}") && filesize("xls/{$csv}") > 0) {
		?>
	        <hr />
	        <p class="tac" >
	            <button type="button" class="btn btn-success download-xls" data-xls="<?="xls/{$csv}"?>" ><i class="icon-file icon-white" ></i> Download XLS</button>
	        </p>
	    <?php
	    }
}
?>
<script>

Shadowbox.init();
$("#data_inicial, #data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

$(document).on("click", "span[rel=lupa]", function() {
    $.lupa($(this));
});

/**
 * Lupa do Posto Autorizado
 */
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

/**
 * Lupa de Peça
 */
$(document).on("click", "span[rel=trocar_peca]", function() {
    $("#peca_id, #peca_referencia, #peca_descricao").val("");

    $("#peca_referencia, #peca_descricao")
    .prop({ readonly: false })
    .next("span[rel=trocar_peca]")
    .attr({ rel: "lupa" })
    .find("i")
    .removeClass("icon-remove")
    .addClass("icon-search")
    .removeAttr("title");
});

function retorna_peca(retorno) {
    $("#peca_id").val(retorno.peca);
    $("#peca_referencia").val(retorno.referencia);
    $("#peca_descricao").val(retorno.descricao);

    $("#peca_referencia, #peca_descricao")
    .prop({ readonly: true })
    .next("span[rel=lupa]")
    .attr({ rel: "trocar_peca" })
    .find("i")
    .removeClass("icon-search")
    .addClass("icon-remove")
    .attr({ title: "Trocar Peça" });
}

/**
 * Evento do botão de download do XLS
 */
$("button.download-xls").on("click", function() {
    var xls = $(this).data("xls");
    window.open(xls);
});

</script>

<?php
include "rodape.php";
?>
