<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
    include 'autentica_admin.php';
    $areaAdmin = true;
} else {
    include 'autentica_usuario.php';
    $areaAdmin = false;
}

include "funcoes.php";
include_once '../class/tdocs.class.php';
include_once "../classes/mpdf61/mpdf.php";
include_once '../class/communicator.class.php';

$classProtocolo = new \Posvenda\Fabricas\_1\Protocolo($login_fabrica, $con);

$tcComm = new TcComm('smtp@posvenda');

$protocolo = $_GET['protocolo'];
$tipo      = $_GET['tipo'];

$tDocs    = new TDocs($con, $login_fabrica, 'protocolo');
$tDocsAss = new TDocs($con, $login_fabrica, 'assinatura');
$pdf      = new mPDF;

if (isset($_POST['aprovarProtocolo'])) {

    $protocolo            = $_POST['protocolo'];
    $arrExtratos          = $_POST['extratosRemover'];
    $arrExtratosAprovados = $_POST['extratosAprovados'];
    $arrExtratosRetidos   = $_POST['extratosRetidos'];
    $arrJustificativas    = $_POST['justificativas'];
    $tipo                 = $_POST['tipo'];

    pg_query($con, "BEGIN");

    if ($tipo == "auditar") {

        if (count($arrExtratos) > 0) {

            foreach ($arrExtratos as $extratoId) {

                $classProtocolo->removeExtratoProtocolo($extratoId);

            }

        }

        //gerar um novo arquivo com os protocolos que foram aprovados
        $retorno = $classProtocolo->aprovaProtocolo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,"alterar", null, $tDocs, $pdf, $tDocsAss);

        /*
            contas receber bloqueado por enquanto
            $classProtocolo->insereStatusProtocolo($protocolo, "an_cr");

            $classProtocolo->enviaEmailTransferencia($protocolo, "analista_contas_receber", $tcComm);
        */

        $classProtocolo->insereStatusProtocolo($protocolo, "an_cp");

        $classProtocolo->aprovaProtocolo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,"alterar", null, $tDocs, $pdf, $tDocsAss);

        $classProtocolo->enviaEmailTransferencia($protocolo, "analista_contas_pagar", $tcComm);

    } else if ($tipo == "contas_receber") {

        foreach ($arrExtratosAprovados as $key => $extratoId) {

            $classProtocolo->insereStatusExtrato($extratoId, "Aprovado Contas Receber");

        }

        foreach ($arrExtratosRetidos as $key => $extratoId) {

            $justificativa = $arrJustificativas[$key];

            $classProtocolo->insereStatusExtrato($extratoId, "Retido Contas Receber", $justificativa);

        }

        //verifica se o protocolo foi auditado parcialmente
        if ($classProtocolo->verificaProtocoloAuditado($protocolo)) {

            $classProtocolo->insereStatusProtocolo($protocolo, "ge_cr");

            $classProtocolo->enviaEmailTransferencia($protocolo, "gerente_contas_receber", $tcComm);

        }

    } else if ($tipo == "contas_pagar") {

        if (count($arrExtratos) > 0) {

            foreach ($arrExtratos as $extratoId) {

                $classProtocolo->removeExtratoProtocolo($extratoId);

            }

        }

        $classProtocolo->insereStatusProtocolo($protocolo, "ge_cp");

        //gerar um novo arquivo com os protocolos que foram aprovados
        $retorno = $classProtocolo->aprovaProtocolo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,"alterar", null, $tDocs, $pdf, $tDocsAss);

        $classProtocolo->enviaEmailTransferencia($protocolo, "gerente_contas_pagar", $tcComm);

    }

    if (pg_last_error()) {

        pg_query($con, "ROLLBACK");

        $retorno = [
            "erro" => true
        ];

    } else {

        pg_query($con, "COMMIT");

        $retorno = [
            "erro" => false
        ];

    }

    exit(json_encode($retorno));

}

if (isset($_POST["aprovacao_gerente_contas"])) {

    $aprovar_reprovar = $_POST['aprovar_reprovar'];
    $justificativa    = $_POST['justificativa_gerente'];
    $protocolo        = $_POST['protocolo'];

    pg_query($con, "BEGIN");

    if ($aprovar_reprovar == "aprovar_protocolo") {

        $classProtocolo->insereStatusProtocolo($protocolo, "an_cp");

        $classProtocolo->aprovaProtocolo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,"alterar", null, $tDocs, $pdf, $tDocsAss);

        $classProtocolo->enviaEmailTransferencia($protocolo, "analista_contas_pagar", $tcComm);

    } else if ($aprovar_reprovar == "reprovar_protocolo") {

        //altera os registros da tbl_extrato_status para voltar para o analista de CR
        $classProtocolo->retornaProtocoloParaAnalise($protocolo);

        $sqlExtratosProtocolo = "SELECT extrato FROM tbl_extrato_agrupado WHERE codigo = '{$protocolo}'";
        $resExtratosProtocolo = pg_query($con, $sqlExtratosProtocolo);

        foreach (pg_fetch_all($resExtratosProtocolo) as $chave => $valor) {

            $classProtocolo->insereStatusExtrato($valor["extrato"], "Reprovado Gerente Contas Receber", $justificativa);

        }

        $classProtocolo->insereStatusProtocolo($protocolo, "an_cr");

        $classProtocolo->enviaEmailTransferencia($protocolo, "analista_contas_receber_reprova", $tcComm);

    }

    if (pg_last_error()) {

        pg_query($con, "ROLLBACK");

        $retorno = [
            "erro" => true
        ];

    } else {

        pg_query($con, "COMMIT");

        $retorno = [
            "erro" => false
        ];

    }

    exit(json_encode($retorno));

}

if (isset($_POST['remover_extrato'])) {

    pg_query($con, "BEGIN");

    $protocolo = $_POST['protocolo'];
    $extrato   = $_POST['extrato'];

    $classProtocolo->removeExtratoProtocolo($extrato);

    $classProtocolo->aprovaProtocolo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,"alterar", null, $tDocs, $pdf, $tDocsAss);

    if (pg_last_error()) {

        pg_query($con, "ROLLBACK");

        $retorno = [
            "erro" => true
        ];

    } else {

        pg_query($con, "COMMIT");

        $retorno = [
            "erro" => false
        ];

    }

   exit(json_encode($retorno));

}

if (isset($_POST['finalizar_protocolo'])) {

    pg_query($con, "BEGIN");

    $protocolo = $_POST['protocolo'];

    $classProtocolo->insereStatusProtocolo($protocolo, "final");

    $classProtocolo->aprovaProtocolo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,"finalizar", null, $tDocs, $pdf, $tDocsAss);

    if (pg_last_error()) {

        pg_query($con, "ROLLBACK");

        $retorno = [
            "erro" => true
        ];

    } else {

        pg_query($con, "COMMIT");

        $retorno = [
            "erro" => false
        ];

    }

   exit(json_encode($retorno));

}

?>
<!DOCTYPE html />
<html>
    <head>
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
        <script src="bootstrap/js/bootstrap.js"></script>
        <script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
        <link type="text/css" href="plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">
        <style>
            th {
                background-color: darkblue;
                color: white;
            }
        </style>
        <script>
            $(function(){

                $("#concluir").click(function(){

                    if (confirm("Não será possível desfazer esta ação. Confirma a alteração?")) {

                        let tipo = $("#tipo").val();

                        var arrExtratosRetidos   = [];
                        var arrExtratosAprovados = [];
                        var arrExtratosRemover   = [];
                        var arrJustificativas    = [];
                        var erro = "";
                        if (tipo == "auditar" || tipo == "contas_pagar") {

                            $(".check_extrato:not(:checked)").map(function(){
                                arrExtratosRemover.push($(this).data("extrato"));
                            });

                        } else if (tipo == "contas_receber") {

                            let arrExtratos = [];
                            $(".td-aprovar-reter").each(function(){

                                if ($(this).find(".reter").is(":checked")) {

                                    if ($(this).find(".justificativa").val() == "") {

                                        erro = "Preencha as justificativas para todos os extratos retidos";

                                    }

                                    arrExtratosRetidos.push($(this).find(".reter").data("extrato"));
                                    arrJustificativas.push($(this).find(".justificativa").val());

                                } else if ($(this).find(".aprovar").is(":checked")) {
                                    console.log($(this).find(".aprovar").data("extrato"));
                                    arrExtratosAprovados.push($(this).find(".aprovar").data("extrato"));

                                }

                            });


                        }

                        if (erro.length > 0) {
                            $("#msg_erro").show().find("h4").text(erro);
                            $("body").scrollTop(0);
                            return;
                        }

                        $("#concluir").prop("disabled", true).text("Aguarde...");

                        $.ajax({
                            url: window.location,
                            type:"POST",
                            dataType:"JSON",
                            data: {
                                protocolo: '<?= $protocolo ?>',
                                aprovarProtocolo: true,
                                extratosRemover: arrExtratosRemover,
                                justificativas: arrJustificativas,
                                extratosAprovados: arrExtratosAprovados,
                                extratosRetidos: arrExtratosRetidos,
                                tipo: tipo
                            }
                        })
                        .done(function(data){
                                
                            if (!data.erro) {

                                $("button[protocolo=<?= (string) $protocolo ?>]", window.parent.document).remove();

                                window.parent.Shadowbox.close();

                            } else {

                                alert("Erro ao aprovar protocolos");

                            }

                        });

                    }
                    
                });

                $(".visualiza_nf").click(function(){

                    let extrato = $(this).attr("extrato");

                    Shadowbox.init();

                    Shadowbox.open({
                        content: "visualizar_nfe_servico.php?extrato="+extrato,
                        player: "iframe",
                        title: "Visualizar NFe",
                        height: 300,
                        width: 400
                    });

                });

                $(".reter").click(function(){

                    $(this).closest('.td-aprovar-reter').find('.justificativa').show();

                });

                $(".aprovar").click(function(){

                    $(this).closest('.td-aprovar-reter').find('.justificativa').hide();

                });

                $("#aprovar_protocolo, #reprovar_protocolo").click(function(){

                    let justificativa = $("#justificativa_gerente").val();

                    if ($.trim(justificativa) == "" && $(this).attr("id") == "reprovar_protocolo") {
                        alert("Informe uma justificativa!");
                        return;
                    }

                    $("#aprovar_protocolo, #reprovar_protocolo").prop("disabled", true).text("Aguarde...");

                    $.ajax({
                        url: window.location,
                        type:"POST",
                        dataType:"JSON",
                        data: {
                            aprovar_reprovar: $(this).attr("id"),
                            protocolo: '<?= $protocolo ?>',
                            justificativa_gerente: justificativa,
                            aprovacao_gerente_contas: true
                        }
                    })
                    .done(function(data){
                            
                        if (!data.erro) {

                            $("button[protocolo=<?= (string) $protocolo ?>]", window.parent.document).remove();

                            window.parent.Shadowbox.close();

                        } else {

                            alert("Erro ao aprovar protocolos");

                        }

                    });

                });

                $(".check_todos").click(function(){

                    if ($(this).is(":checked")) {

                        $(".check_extrato").prop("checked", true);

                    } else {

                        $(".check_extrato").prop("checked", false);

                    }

                });

                $(".remover-extrato").click(function(){

                    let that = $(this);

                    let extrato = $(that).data("extrato");

                    if (confirm("Não será possível desfazer esta ação. Confirma a alteração?")) {

                        $(".remover-extrato").prop("disabled", true);
                        $(that).prop("disabled", true).text("Removendo...");

                        $.ajax({
                            url: window.location,
                            type:"POST",
                            dataType:"JSON",
                            data: {
                                remover_extrato: true,
                                protocolo: '<?= $protocolo ?>',
                                extrato: extrato
                            }
                        })
                        .done(function(data){
                                
                            if (!data.erro) {

                                $(that).closest("tr").remove();

                            } else {

                                alert("Erro ao reprovar protocolos");

                            }

                            $(".remover-extrato").prop("disabled", false);

                        });

                    }

                });

                $("#finalizar_protocolo").click(function(){

                    if (confirm("Não será possível desfazer esta ação. Confirma a alteração?")) {

                        $(this).prop("disabled", true).text("Aguarde...");

                        $.ajax({
                            url: window.location,
                            type:"POST",
                            dataType:"JSON",
                            data: {
                                finalizar_protocolo: true,
                                protocolo: '<?= $protocolo ?>'
                            }
                        })
                        .done(function(data){
                                
                            if (!data.erro) {

                                $("button[protocolo=<?= (string) $protocolo ?>]", window.parent.document).remove();

                                window.parent.Shadowbox.close();

                            } else {

                                alert("Erro ao reprovar protocolos");

                            }

                        });

                    }

                });

            });
            
        </script>
    </head>
    <body>
        <input type="hidden" id="tipo" value="<?= $tipo ?>" />
        <?php
        if ($tipo == "auditar") { ?>
            <br />
            <div class="alert alert-info">
                <h4>Selecione os extratos que continuarão no protocolo. Os extratos que não forem selecionados serão removidos do protocolo atual.</h4>
            </div>
        <?php
        } else if ($tipo == "contas_receber") { ?>
            <div class="alert alert-info">
                <h4>O protocolo pode ser auditado parcialmente, e será enviado para o gerente de contas a receber somente quando todos os extratos forem auditados.</h4>
            </div>
            <?php
            $sqlVerificaReprova = "SELECT parametros_adicionais->>'justificativa' as justificativa
                                   FROM tbl_extrato_agrupado
                                   JOIN tbl_extrato_status USING(extrato)
                                   WHERE codigo = '{$protocolo}'
                                   AND obs = 'Reprovado Gerente Contas Receber'
                                   ORDER BY tbl_extrato_status.data DESC
                                   LIMIT 1";
            $resVerificaReprova = pg_query($con, $sqlVerificaReprova);

            if (pg_num_rows($resVerificaReprova) > 0) { ?>
                <div class="alert alert-danger">
                    <h4>Reprovado pelo gerente de contas a receber.<br /> Justificativa: <?= pg_fetch_result($resVerificaReprova, 0, "justificativa") ?></h4>
                </div>
            <?php
            } ?>
            
            <br />
        <?php
        } else if ($tipo == "gerente_contas") { ?>
            <div class="alert alert-info">
                <h4>Caso reprovar, o protocolo voltará para o analista de contas a receber.</h4>
            </div>
        <?php
        }
        ?>
        <div id="msg_erro" class="alert alert-danger" style="display: none;">
            <h4></h4>
        </div>
        <br />
        <table class="table table-bordered">
            <thead>
                <tr>
                    <?php
                    if (in_array($tipo, ["contas_receber","auditar"])) { ?>

                        <th>
                            Aprovados <br />
                            <label>
                                <input type="checkbox" class="check_todos" /> Todos
                            </label>
                        </th>

                    <?php
                    } else if ($tipo == "contas_receber" || $tipo == "gerente_pagar" || $tipo == "contas_pagar") { ?>

                        <th>Ação</th>

                    <?php
                    } 

                    if ($tipo == "gerente_contas") { ?>
                        <th width="150">Status</th>
                    <?php
                    } ?>
                    <th>Código</th>
                    <th>Posto</th>
                    <th>Data Geração</th>
                    <th>Extrato</th>
                    <th>NF Autorizado</th>
                    <th>Total</th>
                    <th>Impressão</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sqlGeracao = "
                    SELECT  tbl_posto_fabrica.codigo_posto,
                            tbl_posto.nome,
                            TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
                            tbl_extrato.protocolo AS numero_extrato,
                            tbl_extrato_extra.nota_fiscal_mao_de_obra AS nf_autorizada,
                            tbl_extrato.total,
                            tbl_extrato.extrato,
                            TO_CHAR(extrato_retido.data, 'DD/MM/YYYY') as data_retido,
                            TO_CHAR(extrato_aprovado.data, 'DD/MM/YYYY') as data_aprovado,
                            extrato_retido.justificativa as justificativa_retimento
                    FROM    tbl_extrato_agrupado
                    JOIN    tbl_extrato         USING(extrato)
                    JOIN    tbl_extrato_extra   USING(extrato)
                    JOIN    tbl_posto_fabrica   USING(fabrica,posto)
                    JOIN    tbl_posto           USING(posto)
                    LEFT JOIN LATERAL (

                        SELECT data
                        FROM tbl_extrato_status
                        WHERE tbl_extrato_status.extrato = tbl_extrato.extrato
                        AND tbl_extrato_status.obs = 'Aprovado Contas Receber'
                        LIMIT 1

                    ) extrato_aprovado ON true
                    LEFT JOIN LATERAL (

                        SELECT data,
                               parametros_adicionais->>'justificativa' as justificativa
                        FROM tbl_extrato_status
                        WHERE tbl_extrato_status.extrato = tbl_extrato.extrato
                        AND tbl_extrato_status.obs = 'Retido Contas Receber'
                        LIMIT 1

                    ) extrato_retido ON true
                    WHERE   tbl_extrato.fabrica         = {$login_fabrica}
                    AND     tbl_extrato_agrupado.codigo = '{$protocolo}'
                    ORDER BY      tbl_posto_fabrica.codigo_posto
                ";
                $resGeracao = pg_query($con,$sqlGeracao);

                $totalGeral = 0;
                while ($extratos = pg_fetch_object($resGeracao)) {

                    $total   = $extratos->total;
                    $extrato = $extratos->extrato;

                    if(!empty($extrato)) {
                       $totalTx =  somaTxExtratoBlack($extrato); 
                       $total+=$totalTx;
                    }

                    $totalGeral += $total;

                ?>
                    <tr>
                        <?php

                        if ($tipo == "gerente_pagar" || $tipo == "contas_pagar") { ?>
                            <td>
                                <button class="btn btn-danger remover-extrato" data-extrato="<?= $extratos->extrato ?>">
                                    Remover
                                </button>
                            </td>
                        <?php
                        }

                        if ($tipo == "auditar") { ?>
                            <td class="tac">
                                <input type="checkbox" class="check_extrato" data-extrato="<?= $extratos->extrato ?>" />
                            </td>
                        <?php
                        } 
                        //contas receber bloqueado por enquanto
                        //if (in_array($tipo, ["contas_receber","contas_pagar","gerente_pagar", "gerente_contas"])) { 
                        if (in_array($tipo, ["contas_receber", "gerente_contas"])) { 
                            if (!empty($extratos->data_retido)) { ?>

                                <td style="color: darkred;font-weight: bolder;">
                                    Retido: <?= $extratos->data_retido ?><br />
                                    Justificativa: <?= $extratos->justificativa_retimento ?>
                                </td>

                            <?php
                            } else if (!empty($extratos->data_aprovado)) { ?>

                                <td style="color: darkgreen;font-weight: bolder;">
                                    Aprovado: <?= $extratos->data_aprovado ?>
                                </td>

                            <?php
                            } else {
                            ?>

                                <td class="tac td-aprovar-reter" nowrap>
                                    <input type="radio" name="aprovar_reter_<?= $extratos->extrato ?>" checked /> <strong>Sem ação</strong> &nbsp;&nbsp;
                                    <input type="radio" name="aprovar_reter_<?= $extratos->extrato ?>" class="aprovar" data-extrato="<?= $extratos->extrato ?>" value="aprovar" /> <strong>Aprovar</strong> &nbsp;&nbsp;
                                    <input type="radio" name="aprovar_reter_<?= $extratos->extrato ?>" class="reter" data-extrato="<?= $extratos->extrato ?>" value="reter" /> <strong>Reter</strong>
                                    <br />
                                    <input type="text" class="justificativa" placeholder="Justificativa" value="" style="display: none;" />
                                </td>

                            <?php
                            }
                            ?>
                        <?php
                        } ?>
                        <td><?= $extratos->codigo_posto ?></td>
                        <td><?= $extratos->nome ?></td>
                        <td><?= $extratos->data_geracao ?></td>
                        <td><?= $extratos->numero_extrato ?></td>
                        <td><?= $extratos->nf_autorizada ?></td>
                        <td><?= number_format($total, 2, ',', '.') ?></td>
                        <td nowrap>
                            <a class="btn btn-info btn-small visualiza_nf" extrato="<?= $extratos->extrato ?>" target="_blank">NFe M.O</a>
                            <input class="btn btn-primary btn-small" type="button" value="Imprimir" onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<?= $extratos->extrato ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" alt="Imprimir" border="0" style="cursor:pointer;">
                            <a class="btn btn-primary btn-small" href="os_extrato_print_blackedecker.php?extrato=<?= $extratos->extrato ?>" target="_blank">Simplificado</a>
                            <a class="btn btn-primary btn-small" href="os_extrato_detalhe_print_blackedecker.php?extrato=<?= $extratos->extrato ?>" target="_blank">Detalhado</a>
                        </td>
                    </tr>
                <?php
                }
                ?> 
            </tbody>
                <tfoot>
                    <tr>
                        <?php
                        $colspan = "100%";
                        if (in_array($tipo, ["auditar","contas_receber","contas_pagar"])) {
                        ?>
                            <th colspan="1">
                                <button class="btn btn-success" id="concluir">
                                    Concluir <?= $tipo == "contas_pagar" ? "Processo" : "" ?>
                                </button>
                            </th>
                        <?php
                            $colspan = "8";
                        }
                        ?>
                        <th colspan="<?= $colspan ?>">
                            Total: R$ <?= number_format($totalGeral, 2, ',', '.') ?>
                        </th>
                    </tr>
                </tfoot>
        </table>
        <?php
        if ($tipo == "gerente_contas") { ?>
            <br />
            <div class="row row-fluid">
                <center>
                    <button type="button" id="aprovar_protocolo" class="btn btn-success">Aprovar</button>
                    <a href="#myModal" role="button" class="btn btn-warning" data-toggle="modal">Reprovar</a>
                </center>
            </div>
            <div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="myModalLabel">Informe uma Justificativa</h3>
              </div>
              <div class="modal-body">
                <textarea class="form-control" id="justificativa_gerente" rows="8" cols="10" style="width: 100%;"></textarea>
              </div>
              <div class="modal-footer">
                <button class="btn btn-primary" id="reprovar_protocolo">Confirmar Reprova</button>
              </div>
            </div>
        <?php
        } else if ($tipo == "gerente_pagar") { ?>

            <div class="row row-fluid">
                <center>
                    <button type="button" id="finalizar_protocolo" class="btn btn-success">Finalizar Protocolo</button>
                </center>
            </div>

        <?php
        }
        ?>
    </body>
</html>
