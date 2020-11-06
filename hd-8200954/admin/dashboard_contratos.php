<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';

use GestaoContrato\TipoContrato;
use GestaoContrato\Contrato;
use GestaoContrato\ContratoStatus;
use GestaoContrato\TabelaPreco;
use GestaoContrato\ContratoStatusMovimento;

$objContrato                    = new Contrato($login_fabrica, $con);
$objTipoContrato                = new TipoContrato($login_fabrica, $con);
$objContratoStatus              = new ContratoStatus($login_fabrica, $con);
$objTabelaPreco                 = new TabelaPreco($login_fabrica, $con);
$objContratoStatusMovimento     = new ContratoStatusMovimento($login_fabrica, $con);


$ag_aprovacao       = $objContrato->getContratoStatus("Aguardando Aprovação da Proposta");
$reprovado          = $objContrato->getContratoStatus("Cancelado");

$aprovado           = $objContrato->getContratoStatus("Aguardando Assinatura");
$aprovado_ag           = $objContrato->getContratoStatus("Aguardando Assinatura", true);
$propostas_avencer  = $objContrato->getPropostasAvencer();

//echo "<pre>".print_r($aprovado_ag,1)."</pre>";exit;

$layout_menu = "gerencia";

$title = "DASHBOARD DE PROPOSTAS X CONTRATOS";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "highcharts"
);

include("plugin_loader.php");

?>
<style>
    .ajuste_titulo{
        padding-bottom: 5px;
        font-size: 16px;
        margin-bottom: 0px;
        margin-top: 60px;
        text-transform: uppercase;
    }
</style>
<script>
    
    $(function(){
        

    });

    
</script>
<h3 class="titulo_coluna ajuste_titulo" style="margin-top: 0px;color: black !important;background-color: yellow !important;">Propostas Aguardando Aprovação</h3>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class='tal'>Representante</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
    <?php
        if (count($ag_aprovacao) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {
            foreach ($ag_aprovacao as $representante => $qtde) {
                echo "<tr>";
                echo "<td class='tal'>{$representante}</td>";
                echo "<td class='tac'>".count($qtde)."</td>";
                echo "</tr>";

            }
        }
    ?>
    </tbody>
</table>
<h3 class="titulo_coluna ajuste_titulo" style="margin-top: 20px;color: #fff !important;background-color: green !important;">Propostas Aprovadas</h3>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class='tal'>Representante</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
    <?php
        if (count($aprovado) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {
            foreach ($aprovado as $representante => $qtde) {
                echo "<tr>";
                echo "<td class='tal'>{$representante}</td>";
                echo "<td class='tac'>".count($qtde)."</td>";
                echo "</tr>";

            }
        }
    ?>
    </tbody>
</table>
<h3 class="titulo_coluna ajuste_titulo" style="color: #fff !important;background-color: red !important;margin-top: 20px;">Propostas Reprovadas</h3>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class='tal'>Representante</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
    <?php
        if (count($reprovado) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {
            foreach ($reprovado as $representante => $qtde) {
                echo "<tr>";
                echo "<td class='tal'>{$representante}</td>";
                echo "<td class='tac'>".count($qtde)."</td>";
                echo "</tr>";

            }
        }
    ?>
    </tbody>
</table>

<h3 class="titulo_coluna ajuste_titulo">Propostas a vencer</h3>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class="tal">Representante</th>
            <th>1 dia</th>
            <th>5 dias</th>
            <th>10 dias</th>
            <th>>=15 dias</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (count($propostas_avencer) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {
            foreach ($propostas_avencer as $k => $rows) {
                echo "<tr>";
                echo "<td class='tal'>".$rows["nome_representante"]."</td>";
                echo "<td class='tac'>".$rows["um_dia"]."</td>";
                echo "<td class='tac'>".$rows["cinco_dia"]."</td>";
                echo "<td class='tac'>".$rows["dez_dia"]."</td>";
                echo "<td class='tac'>".$rows["quinze_dia"]."</td>";
                echo "</tr>";

            }
        }
    ?>
    </tbody>
</table>
<h3 class="titulo_coluna ajuste_titulo">Propostas aprovadas, aguardando cadastro de contrato</h3>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class='tac'>Nº</th>
            <th class='tal'>Representante</th>
            <th class='tal'>Cliente</th>
            <th>Ação</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (count($aprovado_ag) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {

            foreach ($aprovado_ag as $representante => $rows) {
                echo "<tr>
                <td class='tac'><a href='cadastro_contrato.php?contrato=".$rows["contrato"]."' target='_blank'>".$rows["contrato"]."</a></td>
                <td class='tal'>".$rows["nome_representante"]."</td>
                <td class='tal'>".$rows["nome_cliente"]."</td>
                <td class='tac'><a href='cadastro_contrato.php?contrato=".$rows["contrato"]."' class='btn btn-mini btn-info' target='_blank'>Cadastrar Contrato</a></td>
                </tr>";

            }
        }
    ?>
    </tbody>
</table>
<?php

include "rodape.php";
?>
