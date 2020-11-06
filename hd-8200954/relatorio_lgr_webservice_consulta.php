<?

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
    include __DIR__."/class/tdocs.class.php";
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';

$array_estados = $array_estados();

/* Recebe uma request da Tela de cadastro de Nova solicitação em caso de Suceso na gravação */
if (isset($_REQUEST['cadastro'])) {
    $msg_sucesso = "Solicitação cadastrada com Sucesso";
}

if (isset($_REQUEST['btn_acao']) || isset($_REQUEST['cadastro'])) {

    $msg_erro = array(
        "msg"    => array(), 
        "campos" => array()
    );

    $data_inicial       = $_REQUEST["data_inicial"];
    $data_final         = $_REQUEST["data_final"];
    $codigo_posto       = $_REQUEST['codigo_posto'];
    $descricao_posto    = $_REQUEST['descricao_posto'];
    $nota_fiscal        = $_REQUEST['nota_fiscal'];
    $faturamento        = $_REQUEST['faturamento'];
    $conhecimento       = $_REQUEST['conhecimento'];
    $status             = $_REQUEST['status'];
    $os                 = $_REQUEST['os'];
    $nota_fiscal_origem = $_REQUEST['nota_fiscal_origem'];

    if (!isset($_REQUEST['cadastro']) || (isset($_REQUEST['cadastro']) && (!empty($nota_fiscal) || !empty($faturamento) || !empty($conhecimento) || !empty($status) || !empty($data_inicial) || !empty($data_final) || !empty($os) || !empty($nota_fiscal_origem) || !empty($codigo_posto) || !empty($descricao_posto)))) {

        unset($msg_sucesso);

        if (empty($nota_fiscal) && empty($faturamento) && empty($conhecimento) && empty($status) && empty($data_inicial) && empty($data_final) && empty($os) && empty($nota_fiscal_origem) && empty($codigo_posto) && empty($descricao_posto)) {
            $msg_erro["msg"]["campos_obrigatorios"] = "Preencha algum campo para realizar uma pesquisa";
        } else {
            if ($areaAdmin === true) {
                if (!empty($codigo_posto)) {
                    $sqlPst = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND codigo_posto = '{$codigo_posto}';";
                    $resPst = pg_query($con,$sqlPst);

                    if (pg_num_rows($resPst) > 0) {
                        $login_posto = pg_fetch_result($resPst, 0, "posto");
                    } else {
                        $msg_erro["msg"][] = "Posto não encontrado";
                        $msg_erro["campos"][] = "posto";
                    }
                }
            }

            if (!empty($data_inicial) || !empty($data_final)) {
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
                    }
                }
            }
        }

        if (!empty($nota_fiscal)) {
            $whereNf = "AND TRIM(f.nota_fiscal) = TRIM('{$nota_fiscal}')";
        }

        if (!empty($faturamento)) {
            $whereFat = "AND f.faturamento = {$faturamento}";
        }

        if (!empty($conhecimento)) {
            $whereCon = "AND TRIM(f.pedido_fabricante) = TRIM('{$conhecimento}')";
        }

        if (!empty($status)) {
            if ($status == 'aguardando') {
                $whereSts = "AND f.cancelada IS NULL AND f.devolucao_concluida IS NOT TRUE";
            } else if ($status == 'aprovada') {
                $whereSts = "AND f.devolucao_concluida IS TRUE";
            } else if ($status == 'cancelada') {
                $whereSts = "AND f.cancelada IS NOT NULL";
            }
        }

        if (!empty($os) || !empty($nota_fiscal_origem)) {
            $leftJoinItem = "LEFT JOIN tbl_faturamento_item fi ON fi.faturamento = f.faturamento";

            if (!empty($os)) {
                $whereOs = "AND (fi.os = {$os} OR TRIM(fi.obs_conferencia) = TRIM('{$os}'))";
            }

            if (!empty($nota_fiscal_origem)) {
                $whereNfOrigem = "AND TRIM(fi.nota_fiscal_origem) = TRIM('{$nota_fiscal_origem}')";
            }
        }

    }

    if (count($msg_erro["msg"]) == 0) {
        if (!empty($login_posto)) {
            $wherePst = "AND f.distribuidor = {$login_posto}";
        }

        if (!empty($data_final) && !empty($data_inicial) && empty($nota_fiscal) && empty($faturamento) && empty($conhecimento)) {
            $whereData = "AND f.data_input BETWEEN '{$data_inicial} 00:00' AND '{$data_final} 23:59'";
        }

        if (isset($_REQUEST['cadastro'])) {
            $orderLimit = "
                ORDER BY faturamento DESC
                LIMIT 10
            ";
        }

        $sqlLgr = "
            SELECT DISTINCT
                f.faturamento,
                TO_CHAR(f.emissao, 'DD/MM/YY') AS emissao,
		TO_CHAR(f.data_input, 'DD/MM/YY') AS data_solicitacao,
                f.nota_fiscal,
                CASE WHEN (SELECT COUNT(*) FROM tbl_faturamento_interacao WHERE fabrica = {$login_fabrica} AND faturamento = f.faturamento AND admin IS NOT NULL AND ocorrencia IS NULL) > 0 THEN 't' ELSE 'f' END AS interacao,
                CASE WHEN (SELECT COUNT(*) FROM tbl_faturamento_interacao WHERE fabrica = {$login_fabrica} AND faturamento = f.faturamento AND admin IS NOT NULL AND interacao IS NULL) > 0 THEN 't' ELSE 'f' END AS ocorrencia,
                f.cancelada,
                f.devolucao_concluida,
                f.conhecimento,
                f.pedido_fabricante,
                t.nome AS transportadora,
                pf.codigo_posto||' - '||p.nome AS posto
            FROM tbl_faturamento f
            JOIN tbl_posto_fabrica pf ON pf.posto = f.distribuidor AND pf.fabrica = {$login_fabrica}
            JOIN tbl_posto p ON p.posto = pf.posto
            JOIN tbl_transportadora_fabrica tf ON tf.transportadora = f.transportadora AND tf.fabrica = {$login_fabrica}
            JOIN tbl_transportadora t ON t.transportadora = tf.transportadora
            {$leftJoinItem}
            WHERE f.fabrica = {$login_fabrica}
            {$whereNf}
            {$whereFat}
            {$whereCon}
            {$wherePst}
            {$whereData}
            {$whereSts}
            {$whereOs}
            {$whereNfOrigem}
            {$orderLimit};
        ";

        $resLgr = pg_query($con, $sqlLgr);
        $count = pg_num_rows($resLgr);
    }
}

$layout_menu = ($areaAdmin) ? 'financeiro' : 'devolucao';
$title = traduz("CONSULTA DE RELATÓRIOS DE DEVOLUÇÃO DE PEÇAS (LGR)");

if ($areaAdmin === true) {
    include __DIR__.'/admin/cabecalho_new.php';
} else {
    include __DIR__.'/cabecalho_new.php';
}

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "leaflet"
);

include __DIR__.'/admin/plugin_loader.php';

if (count($msg_erro["msg"]) > 0) { ?>
    <br />
    <div class="alert alert-error"><h4><?= implode("<br />", $msg_erro["msg"]); ?></h4></div>
<? } else {
    if (!empty($msg_sucesso)) { ?>
        <br />
        <div class="alert alert-success"><h4><?= $msg_sucesso; ?></h4></div>
    <? }
} ?>

<form name="frm_pesquisa_lgr" id="frm_lgr" method="POST" class="form-search form-inline" enctype="multipart/form-data" >
    <div id="div_informacoes" class="tc_formulario">
        <div class="titulo_tabela">Parâmetros de Pesquisa</div>
        <br />
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span2">
                <div class='control-group <?=(in_array('nota_fiscal', $msg_erro['campos'])) ? "error" : "" ?>'>
                    <label class="control-label" for="nota_fiscal">NF Devolução</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input id="nota_fiscal" name="nota_fiscal" class="span12" type="text" maxlength="20" value="<?= getValue('nota_fiscal'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group">
                    <label class="control-label" for="faturamento">Acompanhamento</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" id="faturamento" class="span12" name="faturamento" value="<?=getValue('faturamento')?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2">
                <div class='control-group'>
                    <label class="control-label" for="conhecimento">Autorização Coleta</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input id="conhecimento" name="conhecimento" class="span12" type="text" maxlength="20" value="<?= getValue('conhecimento'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span2">
                <div class='control-group <?=(in_array('nota_fiscal_origem', $msg_erro['campos'])) ? "error" : "" ?>'>
                    <label class="control-label" for="nota_fiscal_origem">NF Remessa</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input id="nota_fiscal_origem" name="nota_fiscal_origem" class="span12" type="text" maxlength="20" value="<?= getValue('nota_fiscal_origem'); ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group">
                    <label class="control-label" for="os">OS</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" id="os" class="span12" name="os" value="<?=getValue('os')?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class='control-group'>
                    <label class="control-label" for="status">Status</label>
                    <div class="controls controls-row">
                        <div class='span12'>
                            <select class='frm span12' name='status' >
                                <option value=''>Selecione</option>
                                <option value='aguardando' <? if ($status == "aguardando") echo " selected "; ?> >Aguardando Autorização de Coleta</option>
                                <option value='aprovada' <? if ($status == "aprovada") echo " selected "; ?> >Aprovada</option>
                                <option value='cancelada' <? if ($status == "cancelada") echo " selected "; ?> >Reprovada</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span3">
                <div class="control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? 'error' : '' ?>" >
                    <label class="control-label" for="data_inicial">Data Inicial (Solicitação)</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" id="data_inicial" class="span12" name="data_inicial" value="<?=getValue('data_inicial')?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group <?=(in_array('data_final', $msg_erro['campos'])) ? 'error' : '' ?>" >
                    <label class="control-label" for="data_final">Data Final (Solicitação)</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" id="data_final" class="span12" name="data_final" value="<?=getValue('data_final')?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <? if ($areaAdmin === true) { ?>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group <?= (in_array("posto", $msg_erro["campos"])) ? "error" : ""; ?>'>
                        <label class='control-label' for='codigo_posto'>Código Posto</label>
                        <div class='controls controls-row'>
                            <div class='span8 input-append'>
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
                                <input type="text" name="posto_nome" id="descricao_posto" class='span12' value="<?= $posto_nome ?>" >
                                <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
        <? } ?>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span8 tac">
                <input type="hidden" name="btn_acao" id="btn_acao" value="">
                <button type="button" class="btn btn-default btn_pesquisar" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('pesquisar'); $('form[name=frm_pesquisa_lgr]').submit(); } else { alert('Aguarde! A pesquisa está sendo processada.'); return false; }">Pesquisar</button>
            </div>
            <div class="span2"></div>
        </div>
    </div>
</form>

<? if ($count > 0) {
    if (!empty($msg_sucesso)) { ?>
        <div class="alert alert-info">Últimas solicitações efetuadas pelo autorizado, para outras utilize a pesquisa.</div>
    <? } ?>

    <table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-large'>
        <thead>
            <tr>
                <th class="titulo_coluna">Status</th>
                <th class="titulo_coluna">Acompanhamento</th>
                <? if ($areaAdmin === true) { ?>
                    <th class="titulo_coluna">Posto</th>
                <? } ?>
                <th class="titulo_coluna">Nota Fiscal</th>
                <th class="titulo_coluna">Data Solicitação</th>
                <th class="titulo_coluna">Transportadora</th>
                <th class="titulo_coluna">AC</th>
                <th class="titulo_coluna">E-Ticket</th>
                <th class="titulo_coluna">Existem mensagens</th>
                <? if ($areaAdmin === false) { ?>
                    <th class="titulo_coluna">Ocorrência</th>
                <? } ?>
                <th class="titulo_coluna">Ações</th>
            </tr>
        </thead>
        <tbody>
            <? for($i = 0; $i < $count; $i++) {
                $rFaturamento       = pg_fetch_result($resLgr, $i, "faturamento");
                $rNotaFiscal        = pg_fetch_result($resLgr, $i, "nota_fiscal");
		$rDataSolicitacao   = pg_fetch_result($resLgr, $i, "data_solicitacao");
                $rEmissao           = pg_fetch_result($resLgr, $i, "emissao");
                $rObs               = pg_fetch_result($resLgr, $i, "interacao");
                $rOcorrencia        = pg_fetch_result($resLgr, $i, "ocorrencia");
                $rCancelada         = pg_fetch_result($resLgr, $i, "cancelada");
                $rDevConcluida      = pg_fetch_result($resLgr, $i, "devolucao_concluida");
                $rConhecimento      = pg_fetch_result($resLgr, $i, "conhecimento");
                $rPedidoFabricante  = pg_fetch_result($resLgr, $i, "pedido_fabricante");
                $rPosto             = pg_fetch_result($resLgr, $i, "posto");
                $rTransportadora    = pg_fetch_result($resLgr, $i, "transportadora");
                
                if (!empty($rCancelada)) {
                    $status = '<span class="label label-warning">Reprovada</span>';
                } else if (!empty($rDevConcluida)) {
                    $status = '<span class="label label-success">Aprovada</span>';
                } else if (empty($rCancelada) && empty($rDevConcluida)) {
                    $status = '<span class="label label-important">Aguardando<br />Autorização<br />de Coleta</span>';
                } ?>
                <tr>
                    <td class="tac status_<?= $rFaturamento; ?>"><?= $status; ?></td>
                    <td class="tac"><?= $rFaturamento; ?></td>
                    <? if ($areaAdmin === true) { ?>
                        <td class="tac"><?= $rPosto; ?></td>
                    <? } ?>
                    <td class="tac"><?= $rNotaFiscal; ?></td>
                    <td class="tac"><?= $rDataSolicitacao; ?></td>
                    <td><?= $rTransportadora; ?></td>
                    <td class="tac ac_<?= $rFaturamento; ?>">
                        <?= $rPedidoFabricante; ?><br />
                        <? if ($areaAdmin === false && empty($rCancelada) && !empty($rDevConcluida)) { ?>
                            <a href="downloads/midea/etiqueta_devolucao.pdf" target="_blank">Etiqueta</a>
                        <? } ?>    
                    </td>
                    <td class="tac eticket_<?= $rFaturamento; ?>"><?= $rConhecimento; ?></td>
                    <td class="tac obs_<?= $rFaturamento; ?>"><?= ($rObs == 't') ? "<span style='color:red;font-weight:bold;'>SIM</span>" : "NÃO"; ?></td>
                    <? if ($areaAdmin === false) { ?>
                        <td class="tac tdOco_<?= $rFaturamento; ?>"><?= ($rOcorrencia == 't') ? "<button type='button' id='ocorrencia_{$rFaturamento}' class='btn btn-link ocorrencia' rel='{$rFaturamento}'>Sim</button>" : "NÃO"; ?></td>
                    <? } ?>
                    <td class="tac">
                        <button type="button" class="btn btn-mini btn-warning interagir" rel="<?= $rFaturamento; ?>">Interagir</button>
                        <? if ($areaAdmin === true && empty($rCancelada) && !empty($rDevConcluida)) {
                            $mostraOcorrencia = "style='display:block;'";
                        } else {
                            $mostraOcorrencia = "style='display:none;'";
                        } ?>
                        <br />
                        <button type="button" id="ocorrencia_<?= $rFaturamento; ?>" class="btn btn-mini btn-info ocorrencia" rel="<?= $rFaturamento; ?>" <?= $mostraOcorrencia; ?>>Ocorrência</button>
                    </td>
                </tr>
            <? } ?>
        </tbody>
    </table>

<? } else if (count($msg_erro['msg']) == 0 && !empty($_REQUEST['btn_acao'])) { ?>
    <div class="alert">
        <h4>Nenhum resultado encontrado para essa pesquisa.</h4>
    </div>
    <br />
<? } ?>

<script type="text/javascript">
$(function() {

    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function() { $.lupa($(this)); });
    $("#data_inicial, #data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

    $(".interagir").click(function() {
        var faturamento = $(this).attr('rel');

        Shadowbox.open({
            content: "relatorio_lgr_webservice_ajax.php?faturamento="+faturamento,
            player: "iframe",
            width: 800,
            height: 600
        })

        return false;

    });

    $(".ocorrencia").click(function() {
        var faturamento = $(this).attr('rel');

        Shadowbox.open({
            content: "relatorio_lgr_webservice_ocorrencia_ajax.php?faturamento="+faturamento,
            player: "iframe",
            width: 800,
            height: 600
        })

        return false;

    });

})

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function atualiza_status(faturamento, acao, ac = null, eticket = null) {
    if (acao == "aprovar") {
        $(".status_"+faturamento).html('<span class="label label-success">Aprovada</span>');
        $("#ocorrencia_"+faturamento).show();
    } else if (acao == "reprovar") {
        $(".status_"+faturamento).html('<span class="label label-warning">Reprovada</span>');
    }
    if (ac != null && ac != '') {
        $(".ac_"+faturamento).html(ac);
    }
    if (eticket != null && ac != '') {
        $(".eticket_"+faturamento).html(eticket);
    }
    $(".obs_"+faturamento).html('SIM');
}

</script>

<? include "rodape.php"; ?>
