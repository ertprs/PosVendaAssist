<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

use \Posvenda\Os;
use \Posvenda\Pedido;
use \Posvenda\Fabricas\_158\ExportaPedido;

$oOS = new Os($login_fabrica);
$oPedido = new Pedido($login_fabrica, null, 'os');
$oExportaPedido = new ExportaPedido($oPedido, $oOS, $login_fabrica);

$posto = $_REQUEST['posto'];

$os = $_REQUEST['os'];

if (empty($os)) {
    $os = null;
}

$oss = $oExportaPedido->getOsGeraPedido($posto, null, $os);
$pedidos = $oExportaPedido->getPedidoBonGar($posto);

if (is_array($oss) && is_array($pedidos)) {
    $oss = array_merge($oss, $pedidos);
} else if (is_array($pedidos)) {
    $oss = $pedidos;
}

if (!$oss) {
    $msg_erro['msg'][] = "Nenhuma pendência de pedido para esse Posto";
} else {
    foreach ($oss as $linha => $os_linha) {

        if (!empty($os_linha['os'])) {
            $resOSs[$linha] = $oExportaPedido->getPedido($os_linha['os']);
        } else if (!empty($os_linha['pedido'])) {
            $resOSs[$linha] = $oExportaPedido->getPedido(null, $os_linha['pedido']);
        }

        foreach ($resOSs[$linha] as $key => $value) {
            if ((strlen($value['os']) > 0 && $os_anterior != $value['os']) || (strlen($value['pedido']) > 0 && $pedido_anterior != $value['pedido'])) {
                $osPedido[$value['os']][$value['pedido']] = array(
                    'codigo_posto'            => $value['codigo_posto'],
                    'centro_custo'            => $value['centro_custo'],
                    'codigo_tipo_atendimento' => $value['codigo_tipo_atendimento'],
                    'desc_tipo_atendimento'   => utf8_encode($value['desc_tipo_atendimento']),
                    'garantia_antecipada'     => $value['garantia_antecipada'],
                    'status_pedido'           => $value['status_pedido'],
                    'data_pedido'             => $value['data_pedido'],
                    'desc_tipo_pedido'        => utf8_encode($value['desc_tipo_pedido']),
                );
                if (!empty($value['campos_adicionais'])) {
                    $campos_adicionais = json_decode($value['campos_adicionais'], true);
                    if (!empty($campos_adicionais['unidadeNegocio'])) {
                        $osPedido[$value['os']][$value['pedido']]['unidade_negocio'] = $campos_adicionais['unidadeNegocio'];
                    }
                }
            }

            $osPedido[$value['os']][$value['pedido']]['pecas'][$value['peca']] = array(
                'peca'              => $value['peca'],
                'referencia'        => $value['referencia'],
                'desc_peca'         => utf8_encode($value['desc_peca']),
                'preco'             => $value['preco'],
                'unidade'           => utf8_encode($value['unidade']),
                'qtde_pedido'       => $value['qtde_pedido'],
                'os_item'           => $value['os_item'],
                'pedido_item'       => $value['pedido_item'],
                'nf'                => $value['nf'],
            );

            $os_anterior = $value['os'];
            $pedido_anterior = $value['pedido'];

        }
    }

}

?>
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<script src="bootstrap/js/bootstrap.js"></script>
<script>
    $(function(){

        $(document).on('click', '.gera_pedido', function() {
            var pedido = $(this).attr('rel');
            var os = $('#os_'+pedido).val();
            var that = $(this);
            var dados = $('#dados_pedido_'+pedido).val();
            var posto = $('#posto').val();
            var posto_interno = $('#posto_interno').val();

            $(that).text("Gerando Pedidos...").prop({ disabled: true });

            $.ajax({
                type: "POST",
                url: "pedido_gera_manual.php",
                data: { btn_acao: 'gera_pedido', posto: posto, posto_interno: posto_interno, os: os, dados: dados },
            }).done(function (retorno) {
                retorno = JSON.parse(retorno);
                if (retorno.param == 1) {
                    $("#pedido_"+os).html("<a href='pedido_admin_consulta.php?pedido="+retorno.pedido+"' target='_blank'>"+retorno.pedido+"</a>");
                    $(that).hide();
                    alert(retorno.msg);
                } else if (retorno.param == 2) {
                    $("#pedido_"+os).html("<a href='pedido_admin_consulta.php?pedido="+retorno.pedido+"' target='_blank'>"+retorno.pedido+"</a>");
                    $(that).attr("class", "btn btn-info btn-small exporta_pedido");
                    $(that).attr("value", "exporta_pedido");
                    $(that).text("Exportar Pedido").prop({ disabled: false });
                    alert(retorno.msg);
                } else {
                    $(that).text("Gerar Pedido").prop({ disabled: false });
                    alert(retorno.msg);
                }
            })

        });

        $(document).on('click', '.exporta_pedido', function() {
            var pedido = $(this).attr('rel');
            var os = $('#os_'+pedido).val();
            var that = $(this);
            var dados = $('#dados_pedido_'+pedido).val();
            var posto_interno = $('#posto_interno').val();

            $(that).text("Exportando Pedido...").prop({ disabled: true });

            $.ajax({
                type: "POST",
                url: "pedido_gera_manual.php",
                data: { btn_acao: 'exporta_pedido', posto_interno: posto_interno, dados: dados, os: os, pedido: pedido },
            }).done(function (retorno) {
                retorno = JSON.parse(retorno);
                if (retorno.param == 1) {
                    $(that).hide();
                    alert(retorno.msg);
                } else {
                    $(that).text("Exportar Pedido").prop({ disabled: false });
                    alert(retorno.msg);
                }
            });

        });

        $(document).on('click', '.btn-mostra-peca', function() {
            var pedido = $(this).data("pedido");

            if ($(this).hasClass("btn-success")) {
                $(this).removeClass("btn-success");
                $(this).addClass("btn-danger").text("Esconder peças");
                $(".pecas_"+pedido).show();
            } else {
                $(this).removeClass("btn-danger");
                $(this).addClass("btn-success").text("Exibir peças");
                $(".pecas_"+pedido).hide();
            }
        });

        $("#checkAll").click(function(){
            if ($(this).is(":checked")) {
                $(".checkbox").prop("checked", true);
            } else {
                $(".checkbox").prop("checked", false);
            }
        });

        $('#exportar_selecionados').click(function(){

            array_ajax = [];

            $("#status_exportacao").show();

            $(".checkbox:checked").each(function(){
                $("#dados_exportacao").append("<tr><td class='tac'>"+$(this).val()+"</td><td id='status_pedido_"+$(this).val()+"' class='tac'></td></tr>");
            });

            $(".checkbox:checked").each(function(){
                var pedido = $(this).val();
                var os = $('#os_'+pedido).val();
                var that = $(this);
                var dados = $('#dados_pedido_'+pedido).val();
                var posto_interno = $('#posto_interno').val();

                array_ajax.push({ btn_acao: 'exporta_pedido', posto_interno: posto_interno, dados: dados, os: os, pedido: pedido });
            });

            if (array_ajax.length > 0) {
                $(".btn").prop("disabled", true);

                exporta_pedido(0);
            }
        });    
    });

    function exporta_pedido(i) {
        if (typeof array_ajax[i] == "object") {
            var pedido = array_ajax[i].pedido;

            $.ajax({
                type: "POST",
                url: 'pedido_gera_manual.php',
                data: array_ajax[i],
                beforeSend: function(){
                    $("#status_pedido_"+pedido).html("<div class='label label-warning'>Exportando...</div>");
                },
                timeout: 300000
            }).fail(function(retorno) {
                $("#status_pedido_"+pedido).html("<div class='label label-important'>Erro ao enviar pedido</div>");
            }).done(function (retorno) {
                retorno = JSON.parse(retorno);

                if (retorno.param == 1) {
                    $("#status_pedido_"+pedido).html("<div class='label label-success'>"+retorno.msg+"</div>");
                    $(".tr_"+pedido).remove();
                } else {
                    $("#status_pedido_"+pedido).html("<div class='label label-important'>"+retorno.msg+"</div>");
                }

                if (i + 1 == array_ajax.length) {
                    $(".btn").prop("disabled", false);
                }

                exporta_pedido(++i);
            });
        }
    }
</script>

<?php
if ($oss != false && count($msg_erro['msg']) == 0) { ?>
    <div class="container tac">
        <br />
        <button type="button" id="exportar_selecionados" class="btn btn-primary">
            Exportar os pedidos selecionados
        </button>
        <br /><br />
        <table class="table table-bordered table-striped" id="status_exportacao" style="display: none;width: 70%;">
            <thead>
                <tr class="titulo_tabela">
                    <th>Pedido</th>
                    <th>Status Exportação</th>
                </tr>
            </thead>
            <tbody id="dados_exportacao">
            </tbody>
        </table>
    </div>
    <input type="hidden" name="posto" id="posto" value="<?= $posto; ?>" />
    <input type="hidden" name="posto_interno" id="posto_interno" value="<?= $posto_interno; ?>" />
    <table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
            <tr class='titulo_coluna'>
                <th colspan="7">OSs com pendências de pedido / Pedidos aguardando exportação</th>
            </tr>
            <tr class='titulo_coluna'>
                <th>OS</th>
                <th>Pedido</th>
                <th>Tipo Pedido</th>
                <th>Depósito</th>
                <th>Tipo Atendimento</th>
                <th>
                    Todos
                    <br />
                    <input type="checkbox" name="exportar_todos" id="checkAll" value="tudo" checked />
                </th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <? foreach ($osPedido as $o => $xdados) {
            foreach($xdados as $pedido => $value) {

                $resDeposito       = $value['centro_custo'];
                $resCodAtend       = $value['codigo_tipo_atendimento'];
                $resDescAtend      = $value['desc_tipo_atendimento'];
                $resDescTipoPedido = utf8_decode($value['desc_tipo_pedido']);

                $json_dados = json_encode($value); ?>
                <tr class="tr_<?= $pedido ?>">
                    <td class="tac"><?= $o; ?></td>
                    <td class="tac" id="pedido_<?= $o; ?>"><a href='pedido_admin_consulta.php?pedido=<?= $pedido; ?>' target='_blank'><?= $pedido; ?></a></td>
                    <td class="tac"><?= $resDescTipoPedido; ?></td>
                    <td class="tac"><?= $resDeposito; ?></td>
                    <td class="tac"><?= $resCodAtend.' - '.$resDescAtend; ?></td>
                    <td class="tac" style="border-bottom: 1px solid #dddddd;">
                        <input type="checkbox" class="checkbox" value="<?= $pedido ?>" checked />
                    </td>
                    <td class="tac" rowspan="2" style="vertical-align:middle;">
                        <input type="hidden" name="os_<?= (strlen($pedido) > 0) ? $pedido : $o; ?>" id="os_<?= (strlen($pedido) > 0) ? $pedido : $o; ?>" value='<?= $o; ?>' />
                        <input type="hidden" name="dados_pedido_<?= (strlen($pedido) > 0) ? $pedido : $o; ?>" id="dados_pedido_<?= (strlen($pedido) > 0) ? $pedido : $o; ?>" value='<?= $json_dados; ?>' />
                        <? if (empty($pedido)) { ?>
                            <button value="gera_pedido" class="btn btn-primary btn-small gera_pedido" rel="<?= $o; ?>">Gerar Pedido</button>
                        <? } else { ?>
                            <button value="exporta_pedido" class="btn btn-info btn-small exporta_pedido" rel="<?= $pedido; ?>">Exportar Pedido</button>
                        <? } ?>
                    </td>
                </tr>
                <tr class="tr_<?= $pedido ?>">
                    <td colspan="6" class="tac">
                        <button class="btn btn-success btn-mostra-peca" data-pedido="<?= $pedido ?>" style="width: 50%;">
                            Exibir peças
                        </button>
                        <table style="display: none;" id="resultado_pesquisa_pecas" class='table table-striped table-bordered table-hover table-fixed pecas_<?= $pedido ?>'>
                            <thead>
                                <tr>
                                    <th>Peça</th>
                                    <th>Quantidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <? foreach($value['pecas'] as $xpeca) { 
                                    $resReferencia = $xpeca['referencia'];
                                    $resDescPeca = utf8_decode($xpeca['desc_peca']);
                                    $resQtde = $xpeca['qtde_pedido']; ?>
                                    <tr>
                                        <td><?= $resDescPeca; ?></td>
                                        <td class="tac"><?= $resQtde; ?></td>
                                    </tr>
                                <? } ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <? $pedido_anterior = $value['pedido'];
            }
        } ?>
        </tbody>
    </table>
<?php
} else { ?>
    <br /><br />
    <div class="alert alert-warning"><h3>Não foram encontrados pedidos para este posto</h3></div>
<?php
}
?>