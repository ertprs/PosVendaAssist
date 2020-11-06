<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';

use GestaoContrato\Os;

$objOs = new Os($login_fabrica, $con);

$os_pre_agendadas                       = $objOs->getOsPreAgendadas();
$os_sem_confirmacao                     = $objOs->getOsSemConfirmacao();
$os_agendamento_confirmadas             = $objOs->getOsAgendamentosConfirmadas();
$os_agendadas_passaram_data_agendamento = $objOs->getOsAgendadasPassaramDataAgendamento();
$os_agendamento_avencer_15_10_5         = $objOs->getOsAgendamentosAvencerDias();
$os_10_postos_mais_volumes              = $objOs->getOsPostosMaisVolumes();


$layout_menu = "gerencia";

$title = "DASHBOARD O.S. AGENDADAS";

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
    .td_os{
        background: #ccc !important;
        border-color: transparent !important;
        border-radius: 0px !important;
        text-align: center !important;
        font-weight: bold !important;
    }
    .td_td_os{
        border-color: transparent !important;
        border-radius: 0px !important;
        text-align: center !important;
        border: solid 1px #ccc !important;
    }
</style>
<script>
    
    $(function(){
        $(document).on('click', '.add_os', function(){
            var posicao = $(this).data('id')
            if( $(".mostra_os_"+posicao).is(":visible")){
              $(".mostra_os_"+posicao).hide();
            }else{
              $(".mostra_os_"+posicao).show();
            }
        });

    });

    
</script>
<h3 class="titulo_coluna ajuste_titulo" style="margin-top: 0px;">O.S. Pré-Agendadas</h3>
<table class='table table-striped table-bordered table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class='tal'>Posto Autorizado</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
    <?php
        if (count($os_pre_agendadas) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {
            foreach ($os_pre_agendadas as $k => $rows) {
                echo "
                <tr>
                    <td class='tal'>".$rows["nome_posto"]."</td>
                    <td class='tac'>
                        <span class='add_os' data-id='pa_".$k."' style='cursor:pointer;color:blue;font-weight:bold;'>
                            <u>".count($rows["os"])."</u>
                        </span>
                    </td>
                </tr>
                <tr class='mostra_os_pa_".$k."' style='display:none;'>
                    <td class='tac' colspan='2' align='center'>
                        <table border='1' cellspacing='2' cellpadding='2' style='width:450px;margin:0 auto;border-collapse: unset !important;border-spacing: 2px !important'>
                            <tr>
                                <td class='td_os'>DATA PRÉ AGENDAMENTO</td>
                                <td class='td_os'>OS</td>
                            </tr>";
                            if (count($rows["os"]) > 0){
                                foreach ($rows["os"] as $i => $xos) {
                                echo "<tr>
                                        <td class='td_td_os'>".$rows["data_agendamento"][$i]."</td>
                                        <td class='td_td_os'><a target='_blank' href='os_press.php?os={$xos}'>{$xos}</a></td>
                                    </tr>";
                                }
                            }

                echo "      
                        </table>
                    </td>
                </tr>
                ";

            }
        }
    ?>
    </tbody>
</table>
<h3 class="titulo_coluna ajuste_titulo" style="margin-top: 20px;">O.S. Sem Confirmação de Agendamento</h3>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class='tal'>Posto Autorizado</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
    <?php
        if (count($os_sem_confirmacao) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {
            foreach ($os_sem_confirmacao as $k => $rows) {
                echo "
                <tr>
                    <td class='tal'>".$rows["nome_posto"]."</td>
                    <td class='tac'>
                        <span class='add_os' data-id='osc_".$k."' style='cursor:pointer;color:blue;font-weight:bold;'>
                            <u>".count($rows["os"])."</u>
                        </span>
                    </td>
                </tr>
                <tr class='mostra_os_osc_".$k."' style='display:none;'>
                    <td class='tac' colspan='2' align='center'>
                        <table border='1' cellspacing='2' cellpadding='2' style='width:450px;margin:0 auto;border-collapse: unset !important;border-spacing: 2px !important'>
                            <tr>
                                <td class='td_os'>DATA AGENDAMENTO</td>
                                <td class='td_os'>OS</td>
                            </tr>";
                            if (count($rows["os"]) > 0){
                                foreach ($rows["os"] as $i => $xos) {
                                echo "<tr>
                                        <td class='td_td_os'>".$rows["data_agendamento"][$i]."</td>
                                        <td class='td_td_os'><a target='_blank' href='os_press.php?os={$xos}'>{$xos}</a></td>
                                    </tr>";
                                }
                            }

                echo "      
                        </table>
                    </td>
                </tr>
                ";

            }
        }
    ?>
    </tbody>
</table>
<h3 class="titulo_coluna ajuste_titulo" style="margin-top: 20px;">O.s. com agendamento confirmado</h3>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class='tal'>Posto Autorizado</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
    <?php
        if (count($os_agendamento_confirmadas) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {
            foreach ($os_agendamento_confirmadas as $k => $rows) {

                echo "
                <tr>
                    <td class='tal'>".$rows["nome_posto"]."</td>
                    <td class='tac'>
                        <span class='add_os' data-id='ac_".$k."' style='cursor:pointer;color:blue;font-weight:bold;'>
                            <u>".count($rows["os"])."</u>
                        </span>
                    </td>
                </tr>
                <tr class='mostra_os_ac_".$k."' style='display:none;'>
                    <td class='tac' colspan='2' align='center'>
                        <table border='1' cellspacing='2' cellpadding='2' style='width:450px;margin:0 auto;border-collapse: unset !important;border-spacing: 2px !important'>
                            <tr>
                                <td class='td_os'>DATA AGENDAMENTO</td>
                                <td class='td_os'>DATA CONFIRMAÇÃO</td>
                                <td class='td_os'>OS</td>
                            </tr>";
                            if (count($rows["os"]) > 0){
                                foreach ($rows["os"] as $i => $xos) {
                                echo "<tr>
                                        <td class='td_td_os'>".$rows["data_agendamento"][$i]."</td>
                                        <td class='td_td_os'>".$rows["data_confirmacao"][$i]."</td>
                                        <td class='td_td_os'><a target='_blank' href='os_press.php?os={$xos}'>{$xos}</a></td>
                                    </tr>";
                                }
                            }

                echo "      
                        </table>
                    </td>
                </tr>
                ";

            }
        }
    ?>
    </tbody>
</table>

<h3 class="titulo_coluna ajuste_titulo">O.S. agendadas que já passaram a data agendada e não foram finalizadas</h3>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class="tal">Posto Autorizado</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (count($os_agendadas_passaram_data_agendamento) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {
            foreach ($os_agendadas_passaram_data_agendamento as $k => $rows) {

                  echo "
                <tr>
                    <td class='tal'>".$rows["nome_posto"]."</td>
                    <td class='tac'>
                        <span class='add_os' data-id='oada_".$k."' style='cursor:pointer;color:blue;font-weight:bold;'>
                            <u>".count($rows["os"])."</u>
                        </span>
                    </td>
                </tr>
                <tr class='mostra_os_oada_".$k."' style='display:none;'>
                    <td class='tac' colspan='2' align='center'>
                        <table border='1' cellspacing='2' cellpadding='2' style='width:450px;margin:0 auto;border-collapse: unset !important;border-spacing: 2px !important'>
                            <tr>
                                <td class='td_os'>DATA AGENDAMENTO</td>
                                <td class='td_os'>DATA CONFIRMAÇÃO</td>
                                <td class='td_os'>OS</td>
                            </tr>";
                            if (count($rows["os"]) > 0){
                                foreach ($rows["os"] as $i => $xos) {
                                echo "<tr>
                                        <td class='td_td_os'>".$rows["data_agendamento"][$i]."</td>
                                        <td class='td_td_os'>".$rows["data_confirmacao"][$i]."</td>
                                        <td class='td_td_os'><a target='_blank' href='os_press.php?os={$xos}'>{$xos}</a></td>
                                    </tr>";
                                }
                            }

                echo "      
                        </table>
                    </td>
                </tr>
                ";

            }
        }
    ?>
    </tbody>
</table>

<h3 class="titulo_coluna ajuste_titulo">Agendamentos a vencer em 15, 10 e 5 dias</h3>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class="tal">Posto Autorizado</th>
            <th>5 dias</th>
            <th>10 dias</th>
            <th>>=15 dias</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (count($os_agendamento_avencer_15_10_5) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {
            foreach ($os_agendamento_avencer_15_10_5 as $k => $rows) {
                echo "<tr>";
                echo "<td class='tal'>".$rows["nome"]."</td>";
                echo "<td class='tac'>".$rows["cinco_dia"]."</td>";
                echo "<td class='tac'>".$rows["dez_dia"]."</td>";
                echo "<td class='tac'>".$rows["quinze_dia"]."</td>";
                echo "</tr>";

            }
        }
    ?>
    </tbody>
</table>
<h3 class="titulo_coluna ajuste_titulo">OS 10 postos com maior volume de O.S. agendadas</h3>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th class='tal'>Posto Autorizado</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (count($os_10_postos_mais_volumes) == 0) {
            echo "
                    <tr>
                        <td colspan='100%'>Nenhum registro</td>
                    </tr>
                ";
        } else {

            foreach ($os_10_postos_mais_volumes as $k => $rows) {
               echo "
                <tr>
                    <td class='tal'>".$rows["nome_posto"]."</td>
                    <td class='tac'>
                        <span class='add_os' data-id='oada_".$k."' style='font-weight:bold;'>
                            ".$rows["total"]."
                        </span>
                    </td>
                </tr>
                ";

            }
        }
    ?>
    </tbody>
</table>
<?php

include "rodape.php";






























































































