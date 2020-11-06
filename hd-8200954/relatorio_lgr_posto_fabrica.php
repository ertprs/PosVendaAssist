 <?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'fn_logoResize.php';
include 'admin/funcoes.php';

$title = "Relatório LGR";
include "cabecalho.php";


if (isset($_POST["btn_acao"])) {
    $data_inicial   = $_POST["data_inicial"];
    $data_final     = $_POST["data_final"];
    $nota_devolucao = $_POST["nota_devolucao"];

    $xdata_final   = formata_data($data_final);
    $xdata_inicial = formata_data($data_inicial);

    if (!empty($xdata_final) && !empty($xdata_inicial)) {

        $cond_data = "AND tbl_faturamento.data_input BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:49'";
        $cond_data_extrato = " AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'";
    }

    if (!empty($nota_devolucao)) {
        $cond_nota = " AND tbl_faturamento.nota_fiscal = '$nota_devolucao' ";
    }

} else {
    $cond_data = " AND tbl_faturamento.data_input::date >= current_date - interval '2 month'";
    $cond_data_extrato = " AND tbl_extrato.data_geracao::date >= current_date - interval '2 month'";
}

$sqlPendentes = "SELECT DISTINCT ON (tbl_faturamento.faturamento)
                        tbl_faturamento.faturamento,
                        tbl_faturamento.nota_fiscal,
                        tbl_extrato.extrato,
                        tbl_extrato.data_geracao,
                        tbl_faturamento.emissao,
                        tbl_faturamento.total_nota,
                        tbl_faturamento.baixa,
                        tbl_faturamento.devolucao_concluida,
                        tbl_faturamento.data_input,
                        SUM(tbl_faturamento_item.qtde) as qtde
                 FROM tbl_faturamento 
                 JOIN tbl_extrato ON tbl_faturamento.extrato_devolucao = tbl_extrato.extrato
                 LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                 WHERE tbl_faturamento.distribuidor = $login_posto
                 AND tbl_faturamento.fabrica        = $login_fabrica
                 AND tbl_faturamento.baixa IS NULL
                 AND tbl_faturamento.cfop IN ('694921','694922','694923','594919','594920','594921','594922','594923')
                 $cond_data
                 $cond_nota
                 GROUP BY 
                        tbl_faturamento.faturamento,
                        tbl_faturamento.nota_fiscal,
                        tbl_extrato.extrato,
                        tbl_extrato.data_geracao,
                        tbl_faturamento.emissao,
                        tbl_faturamento.baixa,
                        tbl_faturamento.devolucao_concluida,
                        tbl_faturamento.total_nota,
                        tbl_faturamento.data_input";
$resPendentes = pg_query($con, $sqlPendentes);

$sqlRecebidas = "SELECT DISTINCT ON (tbl_faturamento.faturamento)
                        tbl_faturamento.faturamento,
                        tbl_faturamento.nota_fiscal,
                        tbl_extrato.extrato,
                        tbl_extrato.data_geracao,
                        tbl_faturamento.emissao,
                        tbl_faturamento.total_nota,
                        tbl_faturamento.baixa,
                        tbl_faturamento.devolucao_concluida,
                        tbl_faturamento.data_input,
                        SUM(tbl_faturamento_item.qtde) as qtde
                 FROM tbl_faturamento 
                 JOIN tbl_extrato ON tbl_faturamento.extrato_devolucao = tbl_extrato.extrato
                 LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                 WHERE tbl_faturamento.distribuidor = $login_posto
                 AND tbl_faturamento.fabrica        = $login_fabrica
                 AND tbl_faturamento.baixa IS NOT NULL
                 AND tbl_faturamento.devolucao_concluida IS NOT TRUE
                 AND tbl_faturamento.cfop IN ('694921','694922','694923','594919','594920','594921','594922','594923')
                 $cond_data
                 $cond_nota
                 GROUP BY 
                        tbl_faturamento.faturamento,
                        tbl_faturamento.nota_fiscal,
                        tbl_extrato.extrato,
                        tbl_extrato.data_geracao,
                        tbl_faturamento.emissao,
                        tbl_faturamento.baixa,
                        tbl_faturamento.devolucao_concluida,
                        tbl_faturamento.total_nota,
                        tbl_faturamento.data_input";
$resRecebidas = pg_query($con, $sqlRecebidas);

$sqlAguardando = "SELECT  tbl_extrato_lgr.extrato,
                          tbl_extrato.data_geracao,
                          SUM(tbl_extrato_lgr.qtde) AS qtde_total
                FROM tbl_extrato_lgr
                JOIN tbl_extrato using(extrato)
                LEFT JOIN tbl_faturamento ON tbl_faturamento.extrato_devolucao = tbl_extrato_lgr.extrato AND tbl_faturamento.distribuidor = $login_posto AND tbl_faturamento.fabrica  = $login_fabrica
                WHERE tbl_faturamento.faturamento is null
                AND tbl_extrato.fabrica  = $login_fabrica
                AND tbl_extrato.posto    = $login_posto
                $cond_data_extrato
                GROUP BY 
                tbl_extrato_lgr.extrato,
                tbl_extrato.data_geracao 
                ";

$resAguardando = pg_query($con, $sqlAguardando);

?>
<link type="text/css" rel="stylesheet" media="screen" href="admin/bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="admin/bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="admin/css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="admin/css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<script src="admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="admin/bootstrap/js/bootstrap.js"></script>
<script src='admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
<script src='admin/plugins/shadowbox_lupa/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='admin/plugins/shadowbox_lupa/shadowbox.css' />
<script src='admin/plugins/jquery.mask.js'></script>
<script src='admin/plugins/dataTable.js'></script>
<link rel='stylesheet' type='text/css' href='admin/plugins/dataTable.css' />
<div class="container" style="width: 55%;">
<script type="text/javascript">
    $(function() {
        $("#data_inicial").datepicker().mask("99/99/9999");
        $("#data_final").datepicker().mask("99/99/9999");
        Shadowbox.init();

        $(".exibir_pecas").click(function(){
            var faturamento = $(this).attr("faturamento");
            var nota_fiscal = $(this).attr("nota");

            Shadowbox.open({
                content: "exibir_pecas_faturamento_britania.php?faturamento="+faturamento+"&nota="+nota_fiscal,
                player: "iframe",
                title:  "Peças da NF",
                width:  800,
                height: 500
            });
        });
    });
</script>
    <br />
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
    <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br />
        <div class='row-fluid'>
            <div class='span3'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span5'>
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
                        <div class='span5'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span3'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='data_inicial'>Nota de Devolução</label>
                    <div class='controls controls-row'>
                        <div class='span8'>
                            <input type="text" name="nota_devolucao" id="nota_devolucao"  maxlength="15" class='span12' value= "<?=$nota_devolucao?>">
                        </div>
                    </div>
                </div>
            </div>
        </div> 
        <p><br/>
            <button class='btn' id="btn_acao" name="btn_acao">Pesquisar</button>
        </p><br/>   
    </form>
</div>
<div class="container">
    <div class="alert alert-warning">
        <h5>Extratos aguardando preenchimento do posto</h5>
    </div> 
    <table class="table table-bordered table-fixed" id="aguardando_nf">
        <thead>
            <tr class="titulo_tabela">
                <th colspan="6">Extratos Aguardando Emissão de NF</th>
            </tr>    
            <tr class="titulo_coluna">
                <th>Extrato</th>
                <th>Data do Extrato</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $data_table_aguardando = 1;
        if (pg_num_rows($resAguardando) > 0) {  
                for ($i=0;$i < pg_num_rows($resAguardando);$i++) {
                    $faturamento            = pg_fetch_result($resAguardando, $i, 'faturamento');
                    $status_nfe             = pg_fetch_result($resAguardando, $i, 'status_nfe');
                    $data_geracao           = pg_fetch_result($resAguardando, $i, 'data_geracao');
                    $extrato                = pg_fetch_result($resAguardando, $i, 'extrato');
                    $qtde_pendencia         = pg_fetch_result($resAguardando, $i, 'qtde_total');

        ?>
                    <tr>
                        <td class="tac"><a href="extrato_posto_devolucao_lgr.php?extrato=<?= $extrato ?>" target="_blank"><?= $extrato ?></a></td>
                        <td class="tac"><?= mostra_data_hora($data_geracao) ?></td>
                    </tr>
        <?php 
                }

        } else { 
            $data_table_aguardando = 0;
        ?>
            <tr>
                <td colspan="100%" class="tac">Nenhum resultado encontrado</td>
            </tr>
        <?php 
        } 
        ?>          
        </tbody>  
    </table>
    <br /><br />
        <div class="alert alert-warning">
        <h5>Notas ainda não baixadas na fábrica</h5>
    </div>
    <table class="table table-bordered table-fixed" id="nf_pendentes">
        <thead>
            <tr class="titulo_tabela">
                <th colspan="9">Notas com Recebimento Pendente</th>
            </tr>    
            <tr class="titulo_coluna">
                <th>Extrato</th>
                <th>Data do Extrato</th>
                <th>Nota Fiscal</th>
                <th>Quantidade a Devolver</th>
                <th>Valor Total</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $data_table_pendente = 1;
        if (pg_num_rows($resPendentes) > 0) { 
                for ($i=0;$i < pg_num_rows($resPendentes);$i++) {
                    $faturamento            = pg_fetch_result($resPendentes, $i, 'faturamento');
                    $status_nfe             = pg_fetch_result($resPendentes, $i, 'status_nfe');
                    $data_geracao           = pg_fetch_result($resPendentes, $i, 'data_geracao');
                    $extrato                = pg_fetch_result($resPendentes, $i, 'extrato');
                    $nota_fiscal            = pg_fetch_result($resPendentes, $i, 'nota_fiscal');
                    $emissao                = pg_fetch_result($resPendentes, $i, 'emissao');
                    $total_nota             = pg_fetch_result($resPendentes, $i, 'total_nota');

                    $sql = "SELECT SUM(qtde) as qtde_pendente
                            FROM tbl_faturamento_item
                            WHERE faturamento = $faturamento";
                    $res = pg_query($con, $sql);
                    
                    $qtde_pendente = pg_fetch_result($res, 0, 'qtde_pendente');

                    $sql = "SELECT DISTINCT ON (tbl_faturamento_item.peca)
                                tbl_extrato_lgr.qtde_nf,
                                tbl_extrato_lgr.qtde - tbl_extrato_lgr.qtde_nf as qtde_devolvida
                            FROM tbl_faturamento_item
                            JOIN tbl_extrato_lgr ON tbl_faturamento_item.peca = tbl_extrato_lgr.peca AND tbl_extrato_lgr.extrato = $extrato
                            JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento AND tbl_faturamento.extrato_devolucao = tbl_extrato_lgr.extrato
                            WHERE tbl_faturamento_item.faturamento = $faturamento
                            AND tbl_faturamento.distribuidor = $login_posto";

                    $res = pg_query($con, $sql);

                    $qtde_devolvida = 0;

                    for ($x=0;$x < pg_num_rows($res);$x++) {
                        $qtde_devolvida += pg_fetch_result($res, $x, 'qtde_devolvida');
                    }
                    
                    $qtde_devolver = $qtde_pendente - $qtde_devolvida;
        ?>    
                    <tr>
                        <td class="tac"><a href='extrato_posto_devolucao_lgr.php?extrato=<?=$extrato?>' target='_blank'><?= $extrato ?></a></td>
                        <td class="tac"><?= mostra_data_hora($data_geracao) ?></td>
                        <td class="tac"><?= $nota_fiscal ?></td>
                        <td class="tac"><a href="#" class="exibir_pecas" faturamento="<?= $faturamento ?>" nota="<?= $nota_fiscal ?>">
                                <?= $qtde_devolver ?>
                            </a></td>
                        <td><?= number_format($total_nota,2,',','.') ?></td>
                    </tr>
        <?php 
                }
            } else { 
                $data_table_pendente = 0;
        ?>
            <tr>
                <td colspan="100%" class="tac">Nenhum resultado encontrado</td>
            </tr>
        <?php } ?>     
        </tbody>
    </table>
    <br /><br />
    <div class="alert alert-warning">
        <h5>Notas que já foram baixadas pela fábrica</h5>
    </div> 
    <table class="table table-bordered table-fixed" id="nf_recebidas">
        <thead>
            <tr class="titulo_tabela">
                <th colspan="11">Notas Recebidas</th>
            </tr>    
            <tr class="titulo_coluna">
                <th>Extrato</th>
                <th>Data do Extrato</th>
                <th>Nota Fiscal</th>
                <th>Data Nota Fiscal</th>
                <th>Qtde. De Peças</th>
                <th>Valor Total</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $data_table_recebidas = 1;
        if (pg_num_rows($resRecebidas) > 0) {  
                for ($i=0;$i < pg_num_rows($resRecebidas);$i++) {
                    $faturamento            = pg_fetch_result($resRecebidas, $i, 'faturamento');
                    $total_nota             = pg_fetch_result($resRecebidas, $i, 'total_nota');
                    $status_nfe             = pg_fetch_result($resRecebidas, $i, 'status_nfe');
                    $data_geracao           = pg_fetch_result($resRecebidas, $i, 'data_geracao');
                    $extrato                = pg_fetch_result($resRecebidas, $i, 'extrato');
                    $nota_fiscal            = pg_fetch_result($resRecebidas, $i, 'nota_fiscal');
                    $emissao                = pg_fetch_result($resRecebidas, $i, 'emissao');
                    $qtde_pendencia         = pg_fetch_result($resRecebidas, $i, 'qtde');
                    $qtde_nf                = pg_fetch_result($resRecebidas, $i, 'qtde_devolvida');
        ?>    
                    <tr>
                        <td class="tac"><?= $extrato ?></td>
                        <td class="tac"><?= mostra_data_hora($data_geracao) ?></td>
                        <td class="tac"><?= $nota_fiscal ?></td>
                        <td class="tac"><?= mostra_data($emissao) ?></td>
                        <td class="tac">
                            <a href="#" class="exibir_pecas" faturamento="<?= $faturamento ?>" nota="<?= $nota_fiscal ?>">
                                <?= $qtde_pendencia ?>
                            </a>
                        </td>
                        <td><?= number_format($total_nota,2,',','.') ?></td>
                    </tr>
        <?php 
                }

        } else { 
            $data_table_recebidas = 0;
        ?>
            <tr>
                <td colspan="100%" class="tac">Nenhum resultado encontrado</td>
            </tr>
        <?php 
        } 
        ?>    
        </tbody>
    </table>    
</div>
<script>
    <?php if ($data_table_aguardando != 0) { ?>
        $.dataTableLoad({ table: "#aguardando_nf" });
    <?php } ?>

    <?php if ($data_table_recebidas != 0) { ?>
        $.dataTableLoad({ table: "#nf_recebidas" });
    <?php } ?>

    <?php if ($data_table_pendente != 0) { ?>
        $.dataTableLoad({ table: "#nf_pendentes" });
    <?php } ?>
</script>    
<?php
    include "rodape.php";
?>
