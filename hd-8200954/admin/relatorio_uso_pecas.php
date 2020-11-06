<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

use Posvenda\Regras;
use \Posvenda\Pedido;
use \Posvenda\Fabricas\_158\PedidoBonificacao;

$oPedido            = new Pedido($login_fabrica);
$oPedidoBonificacao = new PedidoBonificacao($oPedido);

$btn_acao = $_REQUEST['btn_acao'];

if (strlen($btn_acao) > 0) {

    if ($btn_acao == 'pesquisar') {
        $codigo_posto = $_REQUEST['codigo_posto'];
        $posto_nome = $_REQUEST['posto_nome'];
        $cpf_tecnico = $_REQUEST['cpf_tecnico'];
        $nome_tecnico = $_REQUEST['nome_tecnico'];
        $data_inicial = $_REQUEST['data_inicial'];
        $data_final = $_REQUEST['data_final'];

        if (strlen($codigo_posto) == 0 && strlen($posto_nome) == 0) {
            $msg_erro['msg'][] = "É necessário selecionar um Posto";
            $msg_erro['campos'][] = "posto";
        } else {
            $sql = "SELECT posto, tipo_posto FROM tbl_posto_fabrica WHERE codigo_posto = '{$codigo_posto}' AND fabrica = {$login_fabrica}";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) == 0) {
                $msg_erro["msg"][]    = "Posto selecionado não encontrado";
                $msg_erro["campos"][] = "posto";
            } else {
                $posto = pg_fetch_result($res, 0, posto);
                $tipo_posto = pg_fetch_result($res, 0, tipo_posto);
                if(strlen($posto) == 0) {
                    $msg_erro["msg"][]    = "Posto selecionado não encontrado";
                    $msg_erro["campos"][] = "posto";
                }
            }
        }

        if (count($msg_erro['msg']) == 0) {

            $posto_interno_nao_gera = \Posvenda\Regras::get("posto_interno_nao_gera", "pedido_garantia", $fabrica);

            if ($posto_interno_nao_gera == true) {
                $wherePostoInterno = "AND tp.posto_interno IS NOT TRUE";
            }

            $sql = "
                SELECT
                    't' AS peca_usada,
                    p.peca,
                    p.referencia||' - '||p.descricao AS desc_peca,
                    SUM(epm.qtde_saida) AS qtde_pendente,
                    ep.qtde,
                    ep.estoque_maximo
                FROM tbl_estoque_posto ep
                JOIN tbl_posto_fabrica pf ON pf.posto = ep.posto AND pf.fabrica = {$login_fabrica}
                JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
                JOIN tbl_peca p ON p.peca = ep.peca AND p.fabrica = {$login_fabrica}
                JOIN tbl_estoque_posto_movimento epm ON epm.peca = p.peca AND epm.posto = {$posto} AND epm.fabrica = {$login_fabrica}
                JOIN tbl_os_item oi ON oi.os_item = epm.os_item
                JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = {$login_fabrica}
                JOIN tbl_os_produto op USING(os_produto)
                JOIN tbl_os o ON o.os = op.os AND o.fabrica = {$login_fabrica}
                JOIN tbl_os_campo_extra oce ON oce.os = o.os AND oce.fabrica = {$login_fabrica}
                JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
                LEFT JOIN tbl_pedido pd ON pd.pedido = oi.pedido AND pd.fabrica = {$login_fabrica}
                LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
                WHERE ep.fabrica = {$login_fabrica}
                AND ep.posto = {$posto}
                AND epm.qtde_saida IS NOT NULL
                AND epm.qtde_entrada IS NULL
                AND sr.gera_pedido IS FALSE
                AND sr.peca_estoque IS TRUE
                {$wherePostoInterno}
                AND (ta.fora_garantia IS TRUE OR tp.tecnico_proprio IS TRUE)
                AND JSON_FIELD('unidadeNegocio', oce.campos_adicionais) NOT IN ('6107','6101','6108','6103','6102','6106','6104','6200')
		        AND ((pd.status_pedido = 1 AND pi.qtde - pi.qtde_cancelada > 0) OR oi.pedido IS NULL)
                GROUP BY p.peca,p.referencia,p.descricao,ep.qtde,ep.estoque_maximo
                UNION
                SELECT
                    'f' AS peca_usada,
                    p.peca,
                    p.referencia||' - '||p.descricao AS desc_peca,
                    ep.estoque_maximo - (ep.qtde + COALESCE(xepm.qtde_saida,0) + COALESCE(xpdi.qtde,0)) AS qtde_pendente,
                    ep.qtde,
                    ep.estoque_maximo
                FROM tbl_estoque_posto ep
                JOIN tbl_peca p ON p.peca = ep.peca AND p.fabrica = {$login_fabrica}
                LEFT JOIN (SELECT oi.peca, COALESCE(SUM(epm.qtde_saida),0) AS qtde_saida FROM tbl_estoque_posto_movimento epm JOIN tbl_os_item oi USING(os_item) WHERE epm.fabrica = {$login_fabrica} AND epm.posto = {$posto} AND oi.pedido IS NULL GROUP BY oi.peca) xepm ON xepm.peca = ep.peca
                LEFT JOIN (SELECT pdi.peca, COALESCE(SUM(pdi.qtde),0) AS qtde FROM tbl_pedido_item pdi JOIN tbl_pedido pd USING(pedido) JOIN tbl_tipo_pedido tpd USING(tipo_pedido,fabrica) LEFT JOIN tbl_os_item oi USING(pedido_item) WHERE pd.posto = {$posto} AND pd.fabrica = {$login_fabrica} AND tpd.garantia_antecipada IS TRUE AND ((tpd.uso_consumo IS TRUE AND pd.status_pedido != 1) OR oi.os_item IS NOT NULL) AND pdi.qtde_faturada + pdi.qtde_cancelada < pdi.qtde GROUP BY pdi.peca) xpdi ON xpdi.peca = ep.peca
                WHERE ep.fabrica = {$login_fabrica}
                AND ep.posto = {$posto};
            ";
            $res = pg_query($con, $sql);
            $pecas = pg_fetch_all($res);

        }

    }
}

$title = "Inventário de Peças";
$layout_menu = "callcenter";

include "cabecalho_new.php";

$plugins = array(
    "lupa",
    "autocomplete",
    "mask",
    "shadowbox",
    "dataTable"
);

include __DIR__.'/plugin_loader.php';
?>

<script type="text/javascript" charset="utf-8">
$(function(){

    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () { $.lupa($(this)); });

    $("#gerar_pedido").click(function() {
        var posto = $('#posto').val();
        var that = $(this);
        var tipo_posto = $('#tipo_posto').val();

        $(that).text("gerando...").prop({ disabled: true });

        $.ajax({
            type: "POST",
            url: "relatorio_uso_pecas_ajax.php",
            data: { ajax_pedido_bonificado: true, posto: posto, tipo_posto: tipo_posto }
        }).done(function (retorno) {
            $(that).text("Gerar Pedidos").prop({ disabled: false });
            retorno = JSON.parse(retorno);
            if (retorno.msg_kit != undefined) {
                alert(retorno.msg+"\n"+retorno.msg_kit);
            } else {
                alert(retorno.msg);
            }
            $(".btn_pesquisar").click();
        })
    });

    $("a[id^=qtde_estoque_movimento_]").click(function() {
        var linha = $(this).attr('rel');
        var posto = $('#posto').val();
        var peca = $('#peca_'+linha).val();
        var fabrica = "<?= $login_fabrica; ?>";

        Shadowbox.open({
            content: "relatorio_uso_pecas_ajax.php?ajax_estoque_movimento=true&posto="+posto+"&peca="+peca+"&fabrica="+fabrica,
            player: "iframe",
            width: 800,
            height: 600
        })

        return false;

    });

    $(document).on('click', '.exportar_pedido', function() {
        var pedido = $(this).attr('rel');
        var os = $('#os_'+pedido).val();
        var posto = $('#posto').val();
        var that = $(this);
        var dados = $('#dados_pedido_'+pedido).val();

        $.ajax({
            type: "POST",
            url: "relatorio_uso_pecas_ajax.php",
            data: { ajax_exporta_pedido: true, dados: dados, posto: posto, os: os },
            beforeSend: function() {
                $(that).text("Exportando Pedido...").prop({ disabled: true });
            },
        }).done(function (retorno) {
            retorno = JSON.parse(retorno);
            if (retorno.param == 1) {
                $(that).hide();
		alert(retorno.msg);
		location.reload();
            } else {
                $(that).text("Exportar Pedido").prop({ disabled: false });
                alert(retorno.msg);
            }
        })
    });

    $(document).on('click', 'button[name=exportar_pedidos]', function() {
        var posto = $('#posto').val();
        var that = $(this);
        var dados = $("#dados_exportar_pedidos").val();

        $.ajax({
            type: "POST",
            url: "relatorio_uso_pecas_ajax.php",
            data: { ajax_exporta_pedidos: true, dados: dados, posto: posto },
            beforeSend: function() {
                $(that).text("processando...").prop({ disabled: true });
            },
        }).done(function (retorno) {
            retorno = JSON.parse(retorno);
            $(that).text("Exportar Pedidos").prop({ disabled: false });
            alert(retorno.msg);
            $(".btn_pesquisar").click();
        })

    });

});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}
</script>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"]); ?></h4>
    </div>
<? } ?>

<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name="frm_busca" method="POST" action="<?= $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?= (in_array("posto", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?= $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?= (in_array("posto", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class='control-label' for='descricao_posto'>Razão Social</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="posto_nome" id="descricao_posto" class='span12' value="<?= $posto_nome ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <br />
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span8 tac">
            <input type="hidden" name="btn_acao" id="btn_acao" value="">
            <button type="button" class="btn btn-default btn_pesquisar" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('pesquisar'); $('form[name=frm_busca]').submit(); } else { alert('Aguarde! A pesquisa está sendo processada.'); return false; }">Pesquisar</button>
        </div>
        <div class="span2"></div>
    </div>
</form>
<? if ($pecas != false) { ?>
    <div class="row-fluid tac">
        <input type="hidden" id="posto" name="posto" value="<?= $posto; ?>" />
        <input type="hidden" id="tipo_posto" name="tipo_posto" value="<?= $tipo_posto; ?>" />
        <button type='button' class='btn' name='gerar_pedido' id="gerar_pedido">Gerar Pedidos</button>
    </div>

    <?
    if (!empty($posto)) {

        $pedidosExportarAumentoKit = $oPedidoBonificacao->buscaPedidosAumentoKit($posto, $login_fabrica);

        if (count($pedidosExportarAumentoKit) > 0) {
            $pedidosExportarAumentoKit = $oPedidoBonificacao->organizaEstoque($pedidosExportarAumentoKit);
        }

        $pedidosParaExportar = $oPedidoBonificacao->verificaEstoqueBonificacao($posto, $login_fabrica);

        if (count($pedidosParaExportar) > 0) {
            $pedidosParaExportar = $oPedidoBonificacao->organizaEstoque($pedidosParaExportar);
            $pedidosParaExportar = $oPedidoBonificacao->adicionaNotaFiscal($pedidosParaExportar, $posto, $login_fabrica);
        }

        if (count($pedidosParaExportar) > 0 && count($pedidosExportarAumentoKit) > 0) {
            $pedidosParaExportar = $pedidosParaExportar + $pedidosExportarAumentoKit;
	} else if (count($pedidosExportarAumentoKit) > 0) {
	    $pedidosParaExportar = $pedidosExportarAumentoKit;
	}

        if (count($pedidosParaExportar) > 0 && $oPedidoBonificacao->verificaExportacao($pedidosParaExportar) === true) { ?>
            <div class="row-fluid tac">
                <input type="hidden" name="dados_exportar_pedidos" id="dados_exportar_pedidos" value='<?= json_encode($pedidosParaExportar); ?>' />
                <button type='button' class='btn' name='exportar_pedidos' id="exportar_pedidos">Exportar Pedidos</button>
            </div>
            <table id="resultado_pedidos_exportar" class='table table-striped table-bordered table-hover table-fixed'>
                <thead>
                    <tr class='titulo_coluna'>
                        <th colspan="5">Pedidos aguardando exportação</th>
                    </tr>
                    <tr class='titulo_coluna'>
                        <th>OS</th>
                        <th>Pedido</th>
                        <th>Depósito</th>
                        <th>Tipo Atendimento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <? foreach ($pedidosParaExportar as $os => $dados) {
                            foreach($dados as $pedido => $value) {
                                if ($value['status_pedido'] == 1) {
                                    $resDeposito = $value['centro_custo'];
                                    $resCodAtend = $value['codigo_tipo_atendimento'];
                                    $resDescAtend = $value['desc_tipo_atendimento'];
                                    
                                    $json_dados = json_encode($dados); ?>
                                    <tr>
                                        <td class="tac"><?= $os; ?></td>
                                        <td class="tac" id="pedido_<?= $pedido; ?>"><a href='pedido_admin_consulta.php?pedido=<?= $pedido; ?>' target='_blank'><?= $pedido; ?></a></td>
                                        <td class="tac"><?= $resDeposito; ?></td>
                                        <td class="tac"><?= $resCodAtend.' - '.$resDescAtend; ?></td>
                                        <td class="tac" rowspan="2" style="vertical-align:middle;">
                                            <input type="hidden" name="os_<?= $pedido; ?>" id="os_<?= $pedido; ?>" value='<?= $os; ?>' />
                                            <input type="hidden" name="dados_pedido_<?= $pedido; ?>" id="dados_pedido_<?= $pedido; ?>" value='<?= $json_dados; ?>' />
                                            <button value="exportar_pedido" class="btn btn-info btn-small exportar_pedido" rel="<?= $pedido; ?>">Exportar Pedido</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4">
                                            <table id="resultado_pesquisa_pecas" class='table table-striped table-bordered table-hover table-fixed'>
                                                <thead>
                                                    <tr>
                                                        <th>Peça</th>
                                                        <th>Quantidade</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <? foreach($value['pecas'] as $peca) { 
                                                        $resReferencia = $peca['referencia'];
                                                        $resDescPeca = utf8_decode($peca['desc_peca']);
                                                        $resQtde = $peca['qtde_pedido']; ?>
                                                        <tr>
                                                            <td><?= $resReferencia." - ".$resDescPeca; ?></td>
                                                            <td class="tac"><?= $resQtde; ?></td>
                                                        </tr>
                                                    <? } ?>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                <? } else {
                                    continue;
                            }
                        }
                    } ?>
                </tbody>
            </table>
            <br />
        <? }
    }
    ?>

    <table class="table table-striped table-bordered table-hover table-fixed">
        <thead>
            <tr class="titulo_coluna">
                <th colspan="100%">Movimentação de Peças Pendentes</th>
            </tr>
            <tr class="titulo_coluna">
                <th>Peça</th>
                <th>Estoque Máximo</th>
                <th>Estoque Atual</th>
                <th>Qtde. Usado/Aumento Kit</th>
            </tr>
        </thead>
        <tbody>
            <? foreach ($pecas as $linha => $peca) { ?>
                <tr>
                    <td><?= $peca['desc_peca']; ?></td>
                    <td class="tac"><?= $peca['estoque_maximo']; ?></td>
                    <td class="tac"><?= $peca['qtde']; ?></td>
                    <td class="tac">
                        <? if ($peca['peca_usada'] == 't') { ?>
                            <input type="hidden" id="peca_<?= $linha; ?>" value="<?= $peca['peca']; ?>" />
                            <a id="qtde_estoque_movimento_<?= $linha; ?>" rel="<?= $linha; ?>" style="cursor:pointer;"><?= $peca['qtde_pendente']; ?></a>
                        <? } else {
                            echo $peca['qtde_pendente'];
                        } ?>
                    </td>
                </tr>
            <? } ?>
        </tbody>
    </table>
<? } else {
    if (isset($btn_acao) && count($msg_erro['msg']) == 0) {
    ?>
        <div class="alert">
            <h4>Não existem peças pendentes de pedido ou com pedidos pendente de exportação.</h4>
        </div>
    <?
    }
}
include "rodape.php"; ?>
