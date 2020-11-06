<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/tdocs.class.php';
include_once "../classes/mpdf61/mpdf.php";
include_once '../class/communicator.class.php';

$classProtocolo = new \Posvenda\Fabricas\_1\Protocolo($login_fabrica, $con);
$tDocs          = new TDocs($con,$login_fabrica,'protocolo');

$title       = "Relat�rio Status Protocolo";

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

$dadosProtocolo = $classProtocolo->listaProtocolos();
?>
<script>

    $(function(){

        Shadowbox.init();

        $(".visualiza-protocolo").click(function(){

            let protocolo = $(this).attr("protocolo");

            Shadowbox.open({
                content: "detalhe_protocolo.php?protocolo="+protocolo,
                player: "iframe",
                title: "Detalhes do protocolo "+protocolo,
                height: 1000,
                width: 2000
            });

        });

    });
    
</script>
<?php

    if (count($dadosProtocolo) > 0) { ?>
        <table id="contas_receber" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="5">Protocolos Pendentes de Aprova��o</th>
                </tr>
                <tr class='titulo_coluna'>
                    <th>Protocolo</th>
                    <th>Data Gera��o</th>
                    <th>Arquivos</th>
                    <th>Status</th>
                    <th>A��es</th>
                </tr>
            <thead>
            <tbody>
    <?php
        foreach ($dadosProtocolo as $key => $value) {
    
    ?>
            <tr>
                <td class="tac"><?= $value["protocolo"] ?></td>
                <td class="tac"><?= $value["data_protocolo"] ?></td>
                <td class='tac'>
    <?php
                $realProtocolo  = (int) $value["protocolo"];
                $qtdeAnexos     = $tDocs->getDocumentsByRef($value["protocolo"])->attachListInfo;

                foreach ($qtdeAnexos as $anexos) {
    ?>
                    <a href='<?=$anexos['link']?>' target="_blank"><img src="../imagens/icone_pdf.jpg" border='0' style="width:35px;" /></a>
    <?php
                }
    ?>
                </td>
                <td class="tac">
                    <?= $value["status"] ?>
                </td>
                <td class="tac">
                    <button  class="btn btn-primary visualiza-protocolo" protocolo="<?= $value["protocolo"] ?>">Visualizar</button>
                </td>
            <tr>
    <?php
        }

    } else { ?>
        <div class="alert alert-warning">
            <h4>Nenhum protocolo pendente</h4>
        </div>
    <?php
    }
?>
    <tbody>
<table>
<script type="text/javascript">

</script>
<?php
include "rodape.php";
?>
