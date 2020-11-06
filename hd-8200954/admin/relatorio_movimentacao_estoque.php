<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "call_center";

include "autentica_admin.php";
include "funcoes.php";

if ($_POST["acao"]) { 
    $msg_erro = array(
        "msg"    => array(), 
        "campos" => array()
    );

    $data_inicial = $_POST["data_inicial"];
    $data_final   = $_POST["data_final"];

    if (empty($data_inicial) || empty($data_final)) {
        $msg_erro["msg"]["campos_obrigatorios"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]                   = "data_inicial";
        $msg_erro["campos"][]                   = "data_final";
    } else {
        list($dia, $mes, $ano) = explode("/", $data_inicial);

        if (!strtotime("$ano-$mes-$dia")) {
            $msg_erro["msg"]["data_invalida"] = "Data inválida";
            $msg_erro["campos"][]             = "data_inicial";
        } else {
            $data_inicial = "$ano-$mes-$dia";
        }

        list($dia, $mes, $ano) = explode("/", $data_final);

        if (!strtotime("$ano-$mes-$dia")) {
            $msg_erro["msg"]["data_invalida"] = "Data inválida";
            $msg_erro["campos"][]             = "data_final";
        } else {
            $data_final = "$ano-$mes-$dia";
        }

        if (!$msg_erro["msg"]["data_invalida"]) {
            if (strtotime($data_final) < strtotime($data_inicial)) {
                $msg_erro["msg"]["data_invalida"] = "Data Final não pode ser maior que a Data Inicial";
                $msg_erro["campos"][]             = "data_inicial";
                $msg_erro["campos"][]             = "data_final";
            } else if (strtotime("{$data_inicial} +3 month") < strtotime($data_final)) {
                $msg_erro["msg"]["data_invalida"] = "O Período para pesquisa não pode ser superior a 3 mês";
                $msg_erro["campos"][]             = "data_inicial";
                $msg_erro["campos"][]             = "data_final";
            }
        }
    }

    $posto_id = $_POST["posto_id"];

    if (empty($posto_id)) {
        $msg_erro["msg"]["campos_obrigatorios"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]                   = "posto";
    }

    $peca_id = $_POST["peca_id"];

    if (empty($msg_erro["msg"])) {
        if (!empty($peca_id)) {
            $wherePeca = "
                AND estoque_posto.peca = {$peca_id}
            ";
        }

        $sql = "
            SELECT
             	 DISTINCT
		  posto.nome AS posto,
                peca.referencia AS peca_referencia,
                peca.descricao AS peca_descricao,
                estoque_posto.qtde AS estoque_atual,
		TO_CHAR(movimentacao.data_digitacao, 'DD/MM/YYYY HH24:MI') AS data_movimentacao, 
		movimentacao.data_digitacao,
                movimentacao.qtde_entrada, 
                movimentacao.qtde_saida,
                movimentacao.obs,
                pedido.pedido,
                tipo_pedido.pedido_em_garantia,
                movimentacao.nf,
                movimentacao.parametros_adicionais,
                TO_CHAR(faturamento.emissao, 'DD/MM/YYYY') AS data_nf,
                movimentacao.os,
                status_os.descricao as status_os_descricao,
                TO_CHAR(os.data_fechamento, 'DD/MM/YYYY') AS os_data_fechamento,
                os_campo_extra.campos_adicionais
            FROM tbl_estoque_posto estoque_posto
            INNER JOIN tbl_posto_fabrica posto_fabrica ON posto_fabrica.posto = estoque_posto.posto AND posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto posto ON posto.posto = posto_fabrica.posto
            INNER JOIN tbl_peca peca ON peca.peca = estoque_posto.peca AND peca.fabrica = {$login_fabrica}
            INNER JOIN tbl_estoque_posto_movimento movimentacao ON movimentacao.posto = estoque_posto.posto AND movimentacao.peca = estoque_posto.peca AND movimentacao.fabrica = {$login_fabrica}
            LEFT JOIN tbl_faturamento faturamento ON faturamento.faturamento = movimentacao.faturamento AND faturamento.fabrica = {$login_fabrica}
            LEFT JOIN tbl_faturamento_item faturamento_item ON faturamento_item.faturamento = faturamento.faturamento AND faturamento_item.peca = movimentacao.peca and movimentacao.pedido = faturamento_item.pedido
            LEFT JOIN tbl_pedido_item pedido_item ON pedido_item.pedido = faturamento_item.pedido and pedido_item.peca = faturamento_item.peca
            LEFT JOIN tbl_pedido pedido ON pedido.pedido = pedido_item.pedido AND pedido.fabrica = {$login_fabrica}
            LEFT JOIN tbl_tipo_pedido tipo_pedido ON tipo_pedido.tipo_pedido = pedido.tipo_pedido AND tipo_pedido.fabrica = {$login_fabrica}
            LEFT JOIN tbl_os os ON os.os = movimentacao.os AND os.fabrica = {$login_fabrica}
            LEFT JOIN tbl_status_checkpoint status_os on status_os.status_checkpoint = os.status_checkpoint
            LEFT JOIN tbl_os_campo_extra os_campo_extra ON os_campo_extra.os = os.os
            WHERE estoque_posto.fabrica = {$login_fabrica}
            AND estoque_posto.posto = {$posto_id}
            AND movimentacao.data_digitacao BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'
            {$wherePeca}
            ORDER BY movimentacao.data_digitacao DESC
        ";

//	echo nl2br($sql);
        $resMovimentacaoEstoque = pg_query($con, $sql);

        if (pg_num_rows($resMovimentacaoEstoque) == 0) {
            $msg_erro["msg"][] = "Nenhum resultado encontrado";
	}

	$sqlPostoUnidadeNegocio = "
		SELECT 
			centro_distribuidor.centro AS centro_distribuidor_codigo,
			centro_distribuidor.descricao AS centro_distribuidor_descricao,
			centro_distribuidor.unidade_negocio AS unidade_negocio_principal
		FROM tbl_posto_fabrica pf
		INNER JOIN tbl_posto_distribuidor_sla_default centro_distribuidor_posto ON centro_distribuidor_posto.posto = pf.posto AND centro_distribuidor_posto.fabrica = {$login_fabrica}
		INNER JOIN tbl_distribuidor_sla centro_distribuidor ON centro_distribuidor.distribuidor_sla = centro_distribuidor_posto.distribuidor_sla
		WHERE pf.fabrica = {$login_fabrica}
		AND pf.posto = {$posto_id}
	";
	$resPostoUnidadeNegocio = pg_query($con, $sqlPostoUnidadeNegocio);
	$postoUnidadeNegocio = pg_fetch_assoc($resPostoUnidadeNegocio);
    }
}

$layout_menu = "call_center";
$title       = "RELATÓRIO DE MOVIMENTAÇÃO DE ESTOQUE";

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput"
);

include "plugin_loader.php";

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
            <div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="posto_codigo" >Código do Posto</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <h5 class='asteristico'>*</h5>
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
            <div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="posto_nome" >Nome do Posto</label>
                <div class="controls controls-row" >
                    <div class="span10 input-append" >
                        <h5 class='asteristico'>*</h5>
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

    <?php
    if (strlen(getValue("peca_id")) > 0) {
        $peca_input_readonly     = "readonly";
        $peca_span_rel           = "trocar_peca";
        $peca_input_append_icon  = "remove";
        $peca_input_append_title = "title='Trocar Peça'";
    } else {
        $peca_input_readonly     = "";
        $peca_span_rel           = "lupa";
        $peca_input_append_icon  = "search";
        $peca_input_append_title = "";
    }
    ?>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span3" >
            <div class="control-group" >
                <label class="control-label" for="peca_referencia" >Referência da Peça</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input id="peca_referencia" name="peca_referencia" class="span12" type="text" value="<?=getValue('peca_referencia')?>" <?=$peca_input_readonly?> />
                        <span class="add-on" rel="<?=$peca_span_rel?>" >
                            <i class="icon-<?=$peca_input_append_icon?>" <?=$peca_input_append_title?> ></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                        <input type="hidden" id="peca_id" name="peca_id" value="<?=getValue('peca_id')?>" />
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
                        <span class="add-on" rel="<?=$peca_span_rel?>" >
                            <i class="icon-<?=$peca_input_append_icon?>" <?=$peca_input_append_title?> ></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
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
if (pg_num_rows($resMovimentacaoEstoque) > 0) {
    $resMovimentacaoEstoque = pg_fetch_all($resMovimentacaoEstoque);

    ob_start();
    ?>

    <table class="table table-bordered table-large" style="margin: 0 auto;" >
        <thead>
            <tr>
                <th class="titulo_coluna" nowrap >Posto Autorizado</th>
                <td colspan="14"><?=$resMovimentacaoEstoque[0]["posto"]?></td>
            </tr>
            <tr>
                <th class="titulo_coluna" nowrap >Centro Distribuidor</th>
                <td colspan="14"><?=$postoUnidadeNegocio["centro_distribuidor_codigo"]." - ".$postoUnidadeNegocio["centro_distribuidor_descricao"]?></td>
            </tr>
            <tr>
                <th class="titulo_coluna" nowrap >Unidade de Negócio</th>
                <td colspan="14"><?=$postoUnidadeNegocio["unidade_negocio_principal"]?></td>
            </tr>
            <tr class="titulo_coluna" >
                <th>Referência da Peça</th>
                <th>Descrição da Peça</th>
                <th>Estoque</th>
                <th>Tipo</th>
                <th>Qtde</th>
                <th>Data</th>
                <th>Observação</th>
                <th>Pedido</th>
                <th>Tipo Pedido</th>
                <th>Nota Fiscal</th>
                <th>Data NF</th>
                <th>OS</th>
                <? if($login_fabrica == 158){?>
                <th>Status da OS</th>
                <?}?>
                <th>Data do Fechamento</th>
                <th>Unidade de Negócio</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($resMovimentacaoEstoque as $row) {
                $row = (object) $row;

                if (!empty($row->qtde_entrada)) {
                    $tipo_td_class = "text-success";
                    $tipo_td_style = "background-color: #DFF0D8;";
                    $tipo_td_text  = "Entrada";
                } else {
                    $tipo_td_class = "text-error";
                    $tipo_td_style = "background-color: #F2DEDE;";
                    $tipo_td_text  = "Saída";
                }

                if (!empty($row->pedido)) {
                    if ($row->pedido_em_garantia == "t") {
                        $tipo_pedido_td_class = "text-success";
                        $tipo_pedido_td_style = "background-color: #DFF0D8;";
                        $tipo_pedido_td_text  = "Garantia";
                    } else {
                        $tipo_pedido_td_class = "text-error";
                        $tipo_pedido_td_style = "background-color: #F2DEDE;";
                        $tipo_pedido_td_text  = "Fora de Garantia";
                    }
                } else {
                    unset($tipo_pedido_td_class, $tipo_pedido_td_text, $tipo_pedido_td_style);
                }

                if (!empty($row->parametros_adicionais)) {
                    $row->parametros_adicionais = json_decode($row->parametros_adicionais);
                    $unidade_negocio        = $row->parametros_adicionais->unidadeNegocio;
                } else {
                    $unidade_negocio = $postoUnidadeNegocio["unidade_negocio_principal"];
                }
                ?>
                <tr>
                    <td><?=$row->peca_referencia?></td>
                    <td><?=$row->peca_descricao?></td>
                    <td class="tac" ><?=$row->estoque_atual?></td>
                    <td class="tac <?=$tipo_td_class?>" style="<?=$tipo_td_style?>" nowrap ><?=$tipo_td_text?></td>
                    <td class="tac" ><?=(!empty($row->qtde_entrada)) ? $row->qtde_entrada : $row->qtde_saida?></td>
                    <td nowrap ><?=$row->data_movimentacao?></td>
                    <td><?=$row->obs?></td>
                    <td><?=$row->pedido?></td>
                    <td class="tac <?=$tipo_pedido_td_class?>" style="<?=$tipo_pedido_td_style?>" nowrap ><?=$tipo_pedido_td_text?></td>
                    <td><?=$row->nf?></td>
                    <td><?=$row->data_nf?></td>
                    <td><?=$row->os?></td>
                    <? if($login_fabrica == 158){?>
                    <td><?=$row->status_os_descricao?></td>
                    <?}?>
                    <td><?=$row->os_data_fechamento?></td>
                    <td><?=$unidade_negocio?></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>

    <?php
    $html = ob_get_contents();
    ob_end_flush();

    $data    = date("dmYHi");
    $arquivo = "relatorio-movimentacao-estoque-{$login_fabrica}-{$login_admin}-{$data}.xls";

    system("touch /tmp/{$arquivo}");
    file_put_contents("/tmp/{$arquivo}", $html);
    copy("/tmp/{$arquivo}", "xls/{$arquivo}");

    if (file_exists("xls/{$arquivo}") && filesize("xls/{$arquivo}") > 0) {
    ?>
        <hr />

        <p class="tac" >
            <button type="button" class="btn btn-success download-xls" data-xls="<?="xls/{$arquivo}"?>" ><i class="icon-file icon-white" ></i> Download XLS</button>
        </p>
    <?php
    }
}
?>

<style>

table.table-large {
    width: 1366px;
}

</style>

<script>

$(function() {
	Shadowbox.init();
});

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
