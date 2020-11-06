<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/tdocs.class.php';
include_once "../classes/mpdf61/mpdf.php";
include_once '../class/communicator.class.php';

$tcComm = new TcComm('smtp@posvenda');

$classProtocolo = new \Posvenda\Fabricas\_1\Protocolo($login_fabrica, $con, $tcComm);

if($login_fabrica == 1){
    $title       = "PROTOCOLO DE EXTRATOS";
}else{
    $title       = "APROVAÇÃO PROTOCOLO - ASSINATURA";
}

$layout_menu = 'financeiro';

if (isset($_POST['gerar_planilha'])) {

    $protocolo = $_POST["protocolo"];

    $filename = "relatorio-protocolo-{$protocolo}-".date('Ydm').".csv";
    $file     = fopen("/tmp/{$filename}", "w");

    $sql = "SELECT  tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
                    tbl_extrato.protocolo AS numero_extrato,
                    tbl_extrato_extra.nota_fiscal_mao_de_obra AS nf_autorizada,
                    tbl_extrato.total,
                    tbl_extrato.extrato
            FROM    tbl_extrato_agrupado
            JOIN    tbl_extrato         USING(extrato)
            JOIN    tbl_extrato_extra   USING(extrato)
            JOIN    tbl_posto_fabrica   USING(fabrica,posto)
            JOIN    tbl_posto           USING(posto)
            WHERE   tbl_extrato.fabrica         = $login_fabrica
            AND     tbl_extrato_agrupado.codigo = '$protocolo'
      ORDER BY      tbl_posto_fabrica.codigo_posto";
    $res = pg_query($con, $sql);

    $thead = "Control;Fornecedor;Data Efetiva;Tipo;Número Doc;SubSerie;Parcela;Moeda;Data Venc;Conta;Sub Conta;Centro Custo;Tipo;Linha;Conta;Sub Conta;Centro Custo;Projeto;Entidade;Valor;Descrição1;Descrição2;Descrição3;Descrição4;Descrição5;Pgt Consumidor;Item;Quantidade;Diario\n";

    $tbody = "";
    while ($dados = pg_fetch_object($res)) {

        $tbody .= "{$dados->total};{$dados->codigo_posto};;;{$dados->numero_extrato};;;;;;;;;;;;;;;{$dados->total};;;;;;;;;\n";

    }

    fwrite($file, $thead);
    fwrite($file, $tbody);

    fclose($file);

    if (file_exists("/tmp/{$filename}")) {
        system("mv /tmp/{$filename} xls/{$filename}");

        echo "xls/{$filename}";

    }
    exit;

}

$aprova_protocolo = $admin_parametros_adicionais['aprova_protocolo'];

$tDocs    = new TDocs($con,$login_fabrica,'protocolo');
$tDocsAss = new TDocs($con, $login_fabrica, 'assinatura');
$pdf      = new mPDF;

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $protocolo = filter_input(INPUT_POST,'protocolo');
    $tipo = filter_input(INPUT_POST,'tipo');
    $just = filter_input(INPUT_POST,'justificativa');

    pg_query($con, "BEGIN");

    switch ($tipo) {
        case "excluir_protocolo":
            $classProtocolo->excluiProtocolo($login_fabrica,$protocolo);
            break;
        case "aprovar_protocolo":
            $retorno = $classProtocolo->aprovaProtocolo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,"aprovar", null, $tDocs, $pdf, $tDocsAss);
            $classProtocolo->enviaEmailTransferencia($protocolo, "analista_posvenda", $tcComm);
            break;
        case "reprovar_protocolo":
            $retorno = $classProtocolo->aprovaProtocolo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,"reprovar",$just, $tDocs, $pdf, $tDocsAss);
            break;
        case "ressalva_protocolo":
            $retorno = $classProtocolo->aprovaProtocolo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,"ressalva",$just, $tDocs, $pdf, $tDocsAss);
            break;
    }

    if (pg_last_error()) {
        pg_query($con,"ROLLBACK TRANSACTION");
        exit("erro");
    } else {
        pg_query($con,"COMMIT TRANSACTION");
    }

    exit($retorno);
}

if (isset($_POST['aprovacao'])) {

    $tipoAprovacao = $_POST['aprovacao'];

    $codigoStatus = [];
    switch ($tipoAprovacao) {
        case 'posvenda':
            $codigoStatus = ["an_pv","ge_pv"];
            break;
        case 'contas_receber':
            $codigoStatus = ["an_cr","ge_cr"];
            break;
        case 'contas_pagar':
            $codigoStatus = ["an_cp","ge_cp","final"];
            break;
    }

    $cond = " AND status_protocolo.codigo IN ('".implode("','", $codigoStatus)."')";

}

include 'cabecalho_new.php';
$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "alphanumeric"
);
include("plugin_loader.php");

$permissoesLogin = $classProtocolo->getPermissoesLogin($login_admin);

if (!in_array("gerente_contas_receber", $permissoesLogin) && !in_array("gerente_contas_pagar", $permissoesLogin) && !in_array("gerente_posvenda", $permissoesLogin)) {
    exit("<div class='alert alert-danger'><h4>Apenas os gerentes tem acesso à esta tela</h4></div>");
}
?>

<script type="text/javascript">
$(function() {

    Shadowbox.init();
    
    $(".visualiza-protocolo").click(function(){

        let protocolo = $(this).attr("protocolo");
        let auditar   = $(this).attr("auditar");

        let parametros = "";
        if (auditar == "t") {
            parametros = "&tipo=gerente_contas";
        }

        let gerente_cp = $(this).attr("gerente_cp");
        if (gerente_cp == "t") {
            parametros = "&tipo=gerente_pagar";
        }

        Shadowbox.open({
            content: "detalhe_protocolo.php?protocolo="+protocolo+parametros,
            player: "iframe",
            title: "Detalhes do protocolo "+protocolo,
            height: 1000,
            width: 2000
        });

    });

    $(".gerar-excel").click(function(){

        let protocolo = $(this).attr("protocolo");

        $.ajax({
            url: window.location,
            type: "POST",
            data: {
                gerar_planilha: true,
                protocolo: protocolo
            },
            beforeSend: function () {
                loading("show");
            },
            complete: function (data) {
                window.open(data.responseText, "_blank");

                loading("hide");
            }
        });


    });

    $("#data_inicial").datepicker({
        minDate:"-360d",
        maxDate:"0"
    });
    $("#data_final").datepicker({
        minDate:"-360d",
        maxDate:"0"
    });

    $("button").click(function(e){
        e.preventDefault();

        var aut         = $(this).attr("name").split("_");
        var tipo        = aut[0];
        var protocolo   = aut[1];

        switch(tipo) {
            case "aprovar":
                if (confirm("Tem certeza que deseja aprovar o protocolo "+protocolo+"?")) {
                    $.ajax({
                        url:"aprova_protocolo.php",
                        type:"POST",
                        dataType:"JSON",
                        data:{
                            ajax:true,
                            tipo:"aprovar_protocolo",
                            protocolo:protocolo
                        },
                        beforeSend:function(){
                            $("button[name*="+protocolo+"]").attr("disabled",true);
                        }
                    })
                    .done(function(data){
                        if (data.ok) {
                            alert("Protocolo "+protocolo+" aprovado.");
                            window.open(data.protocolo);
                            window.location.reload();
                        }
                    });
                }
                break;
            case "reprovar":
            case "ressalva":
                var envio = tipo+"_"+protocolo;
                Shadowbox.init();
                Shadowbox.open({
                    content: $("#DivMotivo").html().replace(/__OsAcao__/, envio),
                    player: "html",
                    height: 135,
                    width: 400,
                    options: {
                        enableKeys: false
                    }
                });

                break;
            case "excluir":
                if (confirm("Tem certeza que deseja excluir o protocolo "+protocolo+"?")) {
                    $.ajax({
                        url:"aprova_protocolo.php",
                        type:"POST",
                        dataType:"JSON",
                        data:{
                            ajax:true,
                            tipo:"excluir_protocolo",
                            protocolo:protocolo
                        },
                        beforeSend:function(){
                            $("button[name*="+protocolo+"]").attr("disabled",true);
                        }
                    })
                    .done(function(data){
                        if (data.ok) {
                            alert("Protocolo "+protocolo+" excluído. Os extratos vinculados estão liberados para serem incluídos em outro protocolo.");
                            window.location.reload();
                        }
                    });
                }
                break;
        }
    })
});

$(document).on("click","#button_motivo",function(e){

    var aux         = $(this).attr("rel").split("_");
    var tipo        = aux[0];
    var protocolo   = aux[1]
    var motivo      = $.trim($("#sb-container").find("textarea[name=text_motivo]").val());

    if (motivo.length == 0) {
        alert("Digite o motivo");
    } else {
         $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                tipo:tipo+"_protocolo",
                protocolo:protocolo,
                justificativa:motivo
            },
            beforeSend:function(){
                $("#sb-container").find("div.conteudo").hide();
                $("#sb-container").find("div.loading").show();
            }
        })
        .done(function(data){
            if (data.ok) {
                $("#sb-container").find("div.loading").hide();
                alert("Protocolo "+protocolo+" "+data.acao+".");

                window.open(data.protocolo);
                window.location.reload();
                Shadowbox.close();

            }
        })
        .fail(function(){
            alert("Erro ao gravar ação da aprovação de protocolo.");
            $("#sb-container").find("div.conteudo").hide();
            $("#sb-container").find("div.loading").hide();
            Shadowbox.close();
        });
    }

});

</script>
<form name="frm_protocolo" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" align="center" class="form-search form-inline tc_formulario">
    <div class="titulo_tabela ">Situação de Protocolo
    </div>
    <br />
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span3">
            <label>
                <input type="radio" name="aprovacao" value="posvenda" <?= $_POST['aprovacao'] == 'posvenda' || !isset($_POST['aprovacao']) ? "checked" : "" ?> /> &nbsp; Aprovação Pós-Venda <br />
            </label>
        </div>
        <!-- contas receber bloqueado por enquanto
        <div class="span3">
            <label>
                <input type="radio" name="aprovacao" value="contas_receber" <?= $_POST['aprovacao'] == 'contas_receber' ? "checked" : "" ?> /> &nbsp; Aprovação Contas a Receber <br />
            </label>
        </div>
        -->
        <div class="span3">
            <label>
                <input type="radio" name="aprovacao" value="contas_pagar" <?= $_POST['aprovacao'] == 'contas_pagar' ? "checked" : "" ?> /> &nbsp; Aprovação Contas a Pagar
            </label>
        </div>
    </div>
    <div class="row-fluid tac">
        <input type="submit" class="btn" name="btn_acao" value="Pesquisar" />
    </div>
</form>
<?php
    $sqlTotalProtocolo = "
        SELECT  DISTINCT
                tbl_extrato_agrupado.codigo                             AS protocolo,
                TO_CHAR(tbl_extrato_agrupado.data_agrupa,'DD/MM/YYYY')  AS data_protocolo,
                COUNT(tbl_extrato_agrupado.extrato)                     AS conta_extrato,
                SUM(tbl_extrato.total)                                  AS total_extratos_protocolo,
                tbl_extrato_agrupado.aprovado                           AS protocolo_aprovado,
                tbl_extrato_agrupado.reprovado                          AS protocolo_reprovado,
                status_protocolo.descricao                              AS status_protocolo,
                status_protocolo.codigo                                 AS codigo_status_protocolo
        FROM    tbl_extrato_agrupado
        JOIN    tbl_extrato USING(extrato)
        LEFT JOIN LATERAL (

            SELECT tbl_status_extrato_agrupado.codigo,
                   tbl_status_extrato_agrupado.descricao
            FROM tbl_status_extrato_agrupado
            JOIN tbl_extrato_agrupado_status USING(status_extrato_agrupado)
            WHERE tbl_extrato_agrupado_status.extrato_agrupado_codigo = tbl_extrato_agrupado.codigo
            ORDER BY tbl_extrato_agrupado_status.data_input DESC
            LIMIT 1

        ) status_protocolo ON true
        WHERE   tbl_extrato.fabrica = $login_fabrica
        {$cond}
        GROUP BY tbl_extrato_agrupado.codigo,
                 tbl_extrato_agrupado.data_agrupa,
                 tbl_extrato_agrupado.aprovado,
                 tbl_extrato_agrupado.reprovado,
                 status_protocolo.descricao,
                 status_protocolo.codigo
        ORDER BY tbl_extrato_agrupado.codigo
    ";
    $resTotalProtocolo = pg_query($con,$sqlTotalProtocolo);
    if (pg_num_rows($resTotalProtocolo) > 0 && isset($_POST['aprovacao'])) { ?>

        <table id="extrato_assinatura" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_coluna'>
                    <th>Protocolo</th>
                    <th>Data Geração</th>
                    <th>Qtde Extratos</th>
                    <th>Total Extratos</th>
                    <th>Arquivos</th>
                    <th>Status Atual</th>
                    <th>Ações</th>
                </tr>
            <thead>
            <tbody>
        <?php
                while ($protocolos = pg_fetch_object($resTotalProtocolo)) {
            ?>
                    <tr class="<?=$protocolos->protocolo?>">
                        <td><a style="cursor: pointer;" class="visualiza-protocolo" protocolo="<?= $protocolos->protocolo ?>"><?=$protocolos->protocolo?></a></td>
                        <td class="tar"><?=$protocolos->data_protocolo?></td>
                        <td class='tac'><?=$protocolos->conta_extrato?></td>
                        <td class="tar">R$ <?=number_format($protocolos->total_extratos_protocolo,2,',','.')?></td>
                        <td class='tac'>
            <?php
                        $realProtocolo  = (int)$protocolos->protocolo;
                        $qtdeAnexos     = $tDocs->getDocumentsByRef($realProtocolo)->attachListInfo;

                        foreach ($qtdeAnexos as $anexos) {
            ?>
                            <a href='<?=$anexos['link']?>' target="_blank"><img src="../imagens/icone_pdf.jpg" border='0' style="width:35px;" /></a>
            <?php
                        }
            ?>
                        </td>
                        <td><?= $protocolos->status_protocolo ?></td>
                            <td class='tac'>
                <?php
                                if (empty($protocolos->protocolo_aprovado) && empty($protocolos->protocolo_reprovado)) {

                                    $attr = "";
                                    if (!in_array("gerente_posvenda", $permissoesLogin)) {

                                        $attr = "disabled title='Sem permissão'";

                                    }
                ?>
                                    <button <?= $attr ?> id='aprovar_<?=$protocolos->protocolo?>'  name='aprovar_<?=$protocolos->protocolo?>' class='btn btn-success'>Aprovar</button>
                                    <button <?= $attr ?> id='reprovar_<?=$protocolos->protocolo?>' name='reprovar_<?=$protocolos->protocolo?>' class='btn btn-warning'>Reprovar</button>
                <?php
                                }

                                if (empty($protocolos->protocolo_aprovado) && !empty($protocolos->protocolo_reprovado)) {

                                    $attr = "";
                                    if (!in_array("gerente_posvenda", $permissoesLogin)) {

                                        $attr = "disabled title='Sem permissão'";

                                    }

                ?>
                                    <button id='ressalva_<?=$protocolos->protocolo?>' name='ressalva_<?=$protocolos->protocolo?>' class='btn btn-info'>Aprovar Com Obs</button>
                <?php
                                }

                                if ($protocolos->codigo_status_protocolo == "ge_cr") { 

                                    $attr = "";
                                    if (!in_array("gerente_contas_receber", $permissoesLogin)) {

                                        $attr = "disabled title='Sem permissão'";

                                    }

                                    ?>
                                    <button <?= $attr ?> class="btn btn-primary visualiza-protocolo" name="audit" protocolo="<?= $protocolos->protocolo ?>" auditar="t">Auditar</button>
                                <?php
                                }

                                if ($protocolos->codigo_status_protocolo == "ge_cp") {

                                    $attr = "";
                                    if (!in_array("gerente_contas_pagar", $permissoesLogin)) {

                                        $attr = "disabled title='Sem permissão'";

                                    }

                                    ?>
                                    <button <?= $attr ?> class="btn btn-primary visualiza-protocolo" name="audit" protocolo="<?= $protocolos->protocolo ?>" gerente_cp="t">Auditar</button>
                                <?php
                                }

                                if ($protocolos->codigo_status_protocolo == "final") { ?>
                                    <button  class="btn btn-success gerar-excel" name="gera_planilha" protocolo="<?= $protocolos->protocolo ?>" gerente_cp="t">Gerar Planilha</button>
                                <?php
                                }
                ?>
                            </td>
                    <tr>
            <?php
                }
        ?>
            <tbody>
        <table>
        <div id="DivMotivo" style="display: none;" >
            <div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
            <div class="conteudo" >
                <div class="titulo_tabela" >Informe o Motivo</div>
                    <div class="row-fluid">
                        <div class="span12">
                            <div class="controls controls-row">
                                <textarea name="text_motivo" id="text_motivo" class="span12" maxlength="200" style="resize: none;"></textarea>
                                <label style="margin-top: -9px;margin-bottom: -21px;color: darkgrey" id="contador">200</label>
                            </div>
                        </div>
                    </div>
                    <p><br />
                    <button type="button" id = "button_motivo" name="button_motivo" class="btn btn-block btn-success" rel="__OsAcao__" >Gravar</button>
                    </p><br />
                </div>
            </div>
        </div>
<?php
    } else if (isset($_POST['aprovacao'])) {
?>
<div class='container alert alert-warning'><h4>Não foram encontrados resultados</h4></div>
<?php
    }
?>

<?php
include "rodape.php";
?>
