<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

function posto_interno($posto){

    $sql = "SELECT
                tbl_tipo_posto.posto_interno
            FROM tbl_posto_fabrica
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
            WHERE
                tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND tbl_posto_fabrica.codigo_posto = {$posto}";
    $res = pg_query($con, $sql);

    $posto_interno = pg_fetch_result($res, 0, "posto_interno");

    return ($posto_interno == "t") ? 't' : 'f';

}

use \Posvenda\Os;
use \Posvenda\Pedido;
use \Posvenda\Fabricas\_158\ExportaPedido;

$oOS = new Os($login_fabrica);
$oPedido = new Pedido($login_fabrica, null, 'os');
$oExportaPedido = new ExportaPedido($oPedido, $oOS, $login_fabrica);

$btn_acao = $_REQUEST["btn_acao"];

$env = ($_serverEnvironment == 'development') ? "dev" : "";

if (isset($btn_acao)) {

    if ($btn_acao == 'pesquisar' && count($msg_erro['msg']) == 0) {

        $codigo_posto     = $_REQUEST['codigo_posto'];
        $posto_nome       = $_REQUEST['posto_nome'];
        $estados_pesquisa = $_REQUEST['estado'];

        if (!empty($codigo_posto) && !empty($posto_nome)) {

            $sql = "
                SELECT
                    p.posto,
                    pf.codigo_posto,
                    tp.posto_interno,
                    p.nome
                FROM tbl_posto_fabrica pf
                JOIN tbl_tipo_posto tp USING(tipo_posto,fabrica)
                JOIN tbl_posto p USING(posto)
                WHERE pf.codigo_posto = '{$codigo_posto}'
                AND pf.fabrica = {$login_fabrica};
            ";

            $res = pg_query($con, $sql);

            if (pg_fetch_all($res) > 0) {
                $posto = pg_fetch_result($res, 0, posto);
                $posto_interno = pg_fetch_result($res, 0, posto_interno);
                $codigo_posto = pg_fetch_result($res, 0, codigo_posto);
                $posto_nome = pg_fetch_result($res, 0, nome);

                if ($posto_interno == "t") {
                    $msg_erro['msg'][] = "Piso não gera pedido de peças";
                    $msg_erro['campos'][] = "posto";
                }

            } else {
                $msg_erro['msg'][] = "Posto não encontrado";
                $msg_erro['campos'][] = "posto";
            }

        } else {
            if (count($estados_pesquisa) == 0) {
                $msg_erro['msg'][] = "É necessário selecionar um posto ou estado válidos";
                $msg_erro['campos'][] = "posto";
            }
        }

        if (count($msg_erro['msg']) == 0 || count($estados_pesquisa) == 1) {
            $os = $_REQUEST['os'];

            if (empty($os)) {
                $os = null;
            }

            if (count($estados_pesquisa) > 0) {
                $postosEstados = $oExportaPedido->getPostosEstados($estados_pesquisa);
                
                foreach ($postosEstados as $key) {
                    $idPostos[] = $key["posto"];
                }

                $posto         = $idPostos;
            }

            if (!empty($posto) || count($posto) > 0) {
                $oss = $oExportaPedido->getOsGeraPedido($posto, null, $os);
	            $pedidos = $oExportaPedido->getPedidoBonGar($posto);
            }

            if (is_array($oss) && is_array($pedidos)) {
                $oss = array_merge($oss, $pedidos);
    	    } else if (is_array($pedidos)) {
    		    $oss = $pedidos;
    	    }

            if (!$oss) {
                $msg_erro['msg'][] = "Nenhuma pendência de pedido";
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
        } else if (count($estados_pesquisa) > 0) {
            $postosEstados = $oExportaPedido->getPostosEstados($estados_pesquisa);
        }   

    } else if ($btn_acao == 'gera_pedido') {

        try {

            $posto = $_REQUEST['posto'];
            $os = $_REQUEST['os'];
            $posto_interno = $_REQUEST['posto_interno'];
            $json_dados = json_decode(stripslashes($_REQUEST['dados']), true);

            $condicao = $oPedido->getCondicaoGarantia();
            $tipo_pedido = $oExportaPedido->getTipoPedido('GAR');

            $oPedido->_model->getPDO()->beginTransaction();

            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => $condicao,
                "fabrica"       => $login_fabrica,
                "status_pedido" => 1,
                "finalizado"       => "'".date("Y-m-d H:i:s")."'"
            );

            /*
            * Grava o Pedido
            */

            $oPedido->grava($dados, null);
            $pedido = $oPedido->getPedido();

            foreach($json_dados["pecas"] as $id => $peca) {

                if (!empty($peca['pedido_item'])) {
                    continue;
                }

                $dadosItens = array();

                /*
                * Insere o Pedido Item
                */
                $preco = $oPedido->getPrecoPecaGarantia($id, $os);

                $dadosItens[] = array(
                    "pedido"            => (int)$pedido,
                    "peca"              => $peca["peca"],
                    "qtde"              => $peca["qtde_pedido"],
                    "qtde_faturada"     => 0,
                    "qtde_cancelada"    => 0,
                    "preco"             => $preco,
                    "total_item"        => $preco * $peca["qtde_pedido"]
                );

                $oPedido->gravaItem($dadosItens, $pedido);

                /*
                * Resgata o Pedido Item
                */
                $pedido_item = $oPedido->getPedidoItem();

                /*
                * Atualiza os Pedidos Item na OS Item
                */
                $oPedido->atualizaOsItemPedidoItem($peca["os_item"], $pedido, $pedido_item, $login_fabrica);

            }

            $oPedido->finaliza($pedido);

            /*
            * Atualiza Dados do Pedido
            */
            $json_dados['status_pedido'] = $dados['status_pedido'];
            $json_dados['data_pedido'] = date(Ymd);


            $dados_exporta[$os][$pedido] = $json_dados;

            /*
             * Commit
             */
            $oPedido->_model->getPDO()->commit();

            /*
            * Se Posto tiver Depósito (ForaKit/Piso) senão (CriaOrdemVenda)
            */
            /*if (!empty($json_dados['centro_custo']) && $json_dados['garantia_antecipada'] != 't') {
                $oExportaPedido->pedidoIntegracao($dados_exporta, $tipo_exportacao);
            } else {
                $oExportaPedido->pedidoIntegracaoSemDeposito($dados_exporta);
            }*/

            if ($oExportaPedido->verificaExportado($pedido)) {
                $return = array("msg" => utf8_encode("Pedido Gerado/Exportado com Sucesso"), "param" => 1, "pedido" => $pedido);
            } else {
                $return = array("msg" => utf8_encode("Pedido Gerado com Sucesso, falha na exportação"), "param" => 2, "pedido" => $pedido);
            }
        
        } catch (Exception $e) {

            $oPedido->_model->getPDO()->rollBack();
            $return = array("msg" => utf8_encode($e->getMessage()), "param" => 0, "pedido" => "");

        }

        echo json_encode($return);
        exit;

    } else if ($btn_acao == 'gera_pedidos') {

        // Várias OSs
        $oss = $_REQUEST['oss'];

    } else if ($btn_acao == 'exporta_pedido') {

        $posto_interno = $_REQUEST['posto_interno'];
        $os            = $_REQUEST['os'];
        $pedido        = $_REQUEST['pedido'];
        $json_dados    = json_decode(stripslashes($_REQUEST['dados']), true);

	    $dados_exporta[$os][$pedido] = $json_dados;

        /*
        * Se Posto tiver Depósito (ForaKit/Piso) senão (CriaOrdemVenda)
        */
    	if (strtotime("today") > strtotime("2017-11-30 00:00:00")) {
    	        $oExportaPedido->pedidoIntegracaoSemDeposito($dados_exporta);
    	} else {
            	if (!in_array($json_dados['unidade_negocio'], array('6201','6500', '6600', '6900', '7000')) && !empty($json_dados['centro_custo']) && $json_dados['garantia_antecipada'] != 't' && $oExportaPedido->verificaTerceiroGarantia($os) == false) {
                		$oExportaPedido->pedidoIntegracao($dados_exporta, $tipo_exportacao);
            	} else {
                		$oExportaPedido->pedidoIntegracaoSemDeposito($dados_exporta);
            	}
    	}

        if ($oExportaPedido->verificaExportado($pedido)) {
            $return = array("msg" => utf8_encode("Pedido exportado com sucesso"), "param" => 1);
        } else {
            $return = array("msg" => utf8_encode("O pedido não foi exportado, clique no pedido para ver mais detalhes"), "param" => 0);
        }

        echo json_encode($return);
        exit;
        
    } else if ($btn_acao == "gerar_pedido_postos") {
        $postos_bonificacao = $_POST["postos_bonificacao"];

        //require_once '../rotinas/imbera/gera-pedido-reabastece-estoque.php';
        require_once '../rotinas/imbera/gera-pedido-reabastece-estoque-garantia.php';

        $msg_sucesso = "Rotina executada com sucesso";
    }
}

$layout_menu = "callcenter";
$title = "Pedido de Peças NTP";
include "cabecalho_new.php";

$plugins = array(
    "multiselect",
    "lupa",
    "autocomplete",
    "datepicker",
    "mask",
    "dataTable",
    "shadowbox"
);

include "plugin_loader.php"; ?>

<script type="text/javascript" charset="utf-8">
var array_ajax = [];

$(function(){
    
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();
    
    $("span[rel=lupa]").click(function () { $.lupa($(this));});

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
            url: "<?= $PHP_SELF; ?>",
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
            url: "<?= $PHP_SELF; ?>",
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

    $("#estadoPostoAutorizado").multiselect({
        selectedText: "selecionados # de #"
    });

   $("#checkAll").click(function(){
        if ($(this).is(":checked")) {
            $(".checkbox").prop("checked", true);
        } else {
            $(".checkbox").prop("checked", false);
        }
   });

   $("#submit_formulario_postos").click(function(){
        $("form[name=formulario_postos]").submit();
   });

   $(document).on('click', '.btn-pedidos', function() {
        var posto = $(this).data("posto");

        Shadowbox.open({
            content: 'exibe_pedidos_posto.php?posto='+posto,
            player: "iframe",
            title:  "Pedidos do Posto",
            width:  800,
            height: 500
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
            url: window.location,
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

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

</script>

<? if (count($msg_erro['msg']) > 0) { ?>
    <div class='alert alert-error'>
        <h4><?= implode("<br />", $msg_erro['msg']); ?></h4>
    </div>
<? }

if (strlen($msg_sucesso) > 0) { ?>
    <div class='alert alert-success'>
        <h4><?= $msg_sucesso; ?></h4>
    </div>
<? } ?>

<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name='frm_pedido_manual' method='POST' action='<?= $PHP_SELF; ?>' align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?= (in_array("posto", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="codigo_posto" name="codigo_posto" class='span8' maxlength="20" value="<?= $codigo_posto ?>" />
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        <input type="hidden" name="posto" value="<?= $posto ?>" />
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
                        <input type="text" id="descricao_posto" name="posto_nome" class='span12' value="<?= $posto_nome; ?>" />
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <label class='control-label' for='os'>OS</label>
            <div class='controls controls-row'>
                <div class='span7'>
                    <input type="text" id="os" name="os" class='span12' maxlength="20" value="<?= $os; ?>" />
                </div>
            </div>
        </div>
        <div class="span4">
            <label class="control-label" for="">Estado</label>
            <div class="controls controls-row">
                <select name="estado[]" id="estadoPostoAutorizado" multiple="multiple">
                    <?php
                      foreach ($array_estados() as $sigla => $estados) {
                          $ufSelected = (in_array($sigla, $estados_pesquisa)) ? 'selected="selected"' : '';?>
                          <option value='<?= $sigla ?>' <?= $ufSelected ?> >
                                <?= utf8_decode($estados)?>
                          </option>
                    <?php
                      }
                     ?>
                </select>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid tac">
        <br />
        <input type='hidden' name='btn_acao' id='btn_acao' value='' />
        <input type="button" value="Pesquisar" id='btn_pesquisar' class='btn btn-default' onclick="if ($('#btn_acao').val() == '' ) { $('#btn_acao').val('pesquisar'); $(this).parents('form').submit(); } else { alert('Aguarde submissão'); }" alt="Pesquisar" />
        <?php
        if (count($postosEstados) > 0) { ?>
            <br /><br />
            <button type="button" id="submit_formulario_postos" name="btn_pedido_postos" class="btn btn-primary">
                Gerar pedido de bonificação para os postos selecionados
            </button>
            <br /><br />
            <?php     
        } else if ($oss != false && count($msg_erro['msg']) == 0) { ?>
            <br /><br />
            <button type="button" id="exportar_selecionados" class="btn btn-primary">
                Exportar os pedidos selecionados
            </button>
        <?php
        } ?>
        <br />
    </div>
</form>
<div class="container">
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
<br />
<? if ($oss != false && count($msg_erro['msg']) == 0 && count($postosEstados) == 0) { ?>
    
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
                    <input type="checkbox" id="checkAll" value="tudo" checked />
                </th>
                <th>Exportar</th>
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
} else if (count($postosEstados) > 0) { 
    ?>
    <form name="formulario_postos" action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
        <input type="hidden" value="gerar_pedido_postos" name="btn_acao" />
        <input type="hidden" value='<?= json_encode($estados_pesquisa) ?>' name="estados_pedido" />
        <table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-fixed'>
            <thead>
                <tr class='titulo_tabela'>
                    <th colspan="7">
                        Lista de Postos dos Estados: <strong><?= implode(', ', $estados_pesquisa) ?></strong>
                    </th>
                </tr>
                <tr class='titulo_coluna'>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Estado</th>
                    <th>
                        Todos
                        <br />
                        <input type="checkbox" id="checkAll" value="tudo" checked />
                    </th>
                    <th>Pedidos</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($postosEstados as $key => $dados) { 
                    $posto           = $dados['posto'];
                    $posto_codigo    = $dados['codigo_posto'];
                    $posto_descricao = $dados['nome'];
                    $estado          = $dados['estado'];

                ?>  
                    <tr>
                        <td class="tac"><?= $posto_codigo ?></td>
                        <td><?= $posto_descricao ?></td>
                        <td class="tac"><?= $estado ?></td>
                        <td class="tac">
                            <input type="checkbox" name="postos_bonificacao[]" class="checkbox" value="<?= $posto ?>" checked />
                        </td>
                        <td class="tac">
                            <button type="button" class="btn btn-info btn-small btn-pedidos" data-posto="<?= $posto ?>">       
                                Exibir Pedidos
                            </button>
                        </td>
                    </tr>
                <?php
                } ?>
            </tbody>
        </table>
    </form>

<?php
} 
 ?>
<?php
if (count($postosEstados) > 0) { ?>
    <script>
        $.dataTableLoad({ table: "#resultado_pesquisa" });
    </script>
<?php 
} 
include 'rodape.php'; ?>
