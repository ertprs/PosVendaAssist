<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "HISTÓRICO DE TEMPO DE STATUS DO ATENDIMENTO";
$layout_menu = "callcenter";
$admin_privilegios="call_center";


if (filter_input(INPUT_POST,"pesquisar")) {
    $data_inicio            = filter_input(INPUT_POST,"data_inicio");
    $data_fim               = filter_input(INPUT_POST,"data_fim");
    $atendimento            = filter_input(INPUT_POST,"atendimento");
    $status                 = filter_input(INPUT_POST,"status");
    $situacao_atendimento   = filter_input(INPUT_POST,"situacao_atendimento");
// echo "Busca";exit;

    if (empty($data_inicio) && empty($data_fim) && empty($atendimento)) {
        $msg_erro["msg"][]    = "Os campos principais para busca: Data ou Atendimento";
        $msg_erro["campos"][] = "data_inicio";
        $msg_erro["campos"][] = "data_fim";
        $msg_erro["campos"][] = "atendimento";
    }

    if ((!empty($data_inicio) && !empty($data_fim)) || !empty($atendimento)) {
        if (empty($situacao_atendimento) && empty($atendimento)) {
            $msg_erro["msg"][]    = "Escolha a situação do atendimento";
            $msg_erro["campos"][] = "situacao_atendimento";
        } else {
            if ($situacao_atendimento == "fechada" && empty($data_inicio) && empty($data_fim)) {
                $msg_erro["msg"][]    = "Preencha o período para busca dos atendimentos fechados";
                $msg_erro["campos"][] = "data_inicio";
                $msg_erro["campos"][] = "data_fim";
            }
        }
    }

    if (!is_array($msg_erro)) {
        if (!empty($data_inicio) && !empty($data_fim)) {
            list ($di, $mi, $ai) = explode("/", $data_inicio);
            list ($df, $mf, $af) = explode("/", $data_fim);

            if (checkdate($mi,$di,$ai) && checkdate($mf,$df,$af)) {
                $xdata_inicio = $ai."-".$mi."-".$di;
                $xdata_fim = $af."-".$mf."-".$df;
            } else {
                $msg_erro["msg"][]    = "Preencha corretamente o período para busca dos atendimentos";
                $msg_erro["campos"][] = "data_inicio";
                $msg_erro["campos"][] = "data_fim";
            }
        }

        if ($situacao_atendimento == "fechada" && !empty($xdata_inicio) && !empty($xdata_fim)) {
            $condFechamento = "
                AND    tbl_hd_chamado.data    BETWEEN '$xdata_inicio' AND '$xdata_fim'
                AND    tbl_hd_chamado.status  = 'RESOLVIDO'
            ";
        }

        if ($situacao_atendimento == "aberta") {
            if (empty($status)) {
                $condAbertas = "
                    AND tbl_hd_chamado.status <> 'RESOLVIDO'
                ";
            } else {
                $condAbertas = "
                    AND tbl_hd_chamado.status = '$status'
                ";
            }

            if (!empty($xdata_inicio) && !empty($xdata_fim)) {
                $condData = "
                    AND tbl_hd_chamado.data BETWEEN '$xdata_inicio 00:00:00' AND '$xdata_fim 23:59:59'
                ";
            }
        }

        if ($situacao_atendimento == 'todas') {
            if (!empty($xdata_inicio) && !empty($xdata_fim)) {
                $condData = "
                    AND tbl_hd_chamado.data BETWEEN '$xdata_inicio 00:00:00' AND '$xdata_fim 23:59:59'
                ";
            }
        }

        if (!empty($atendimento)) {
            $condAtendimento = "
                AND tbl_hd_chamado.hd_chamado = $atendimento
            ";
        }

        $sqlHD = "
            SELECT  tbl_hd_chamado.hd_chamado,
                    TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS data_abertura
            FROM    tbl_hd_chamado
            WHERE   tbl_hd_chamado.fabrica              = $login_fabrica
            AND     tbl_hd_chamado.fabrica_responsavel  = $login_fabrica
            $condFechamento
            $condAbertas
            $condData
            $condAtendimento
    ORDER BY     tbl_hd_chamado.hd_chamado
        ";
//     echo nl2br($sqlHD);
        $resHD = pg_query($con,$sqlHD);


    }
}

include 'cabecalho_new.php';

$plugins = array(
    "datepicker",
    "dataTable",
    "maskedinput",
    "alphanumeric"
);

include 'plugin_loader.php';
?>
<style type="text/css">
.mais{
    font-size: 18px;
    font-weight: bold;
}
.mais:hover{
    cursor: pointer;
}
</style>

<script type="text/javascript">

$(function() {
    $.datepickerLoad(Array("data_fim", "data_inicio"));
//     $.dataTableLoad({
//         table: "#table_callcenter_tempo_status"
//     });
    $("#atendimento").numeric();

});
function mostraTempo(a){

    if($("#linha_chamado_"+a+"_hidden").is(":visible")){
        $("#linha_chamado_"+a+"_hidden").hide();
    }else{
        $("#linha_chamado_"+a+"_hidden").show();
    }
}


</script>
<?php
if (is_array($msg_erro)) {
?>
<br />
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='relatorio_callcenter_tempo_status' method='POST' action='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
    <div class="titulo_tabela">Parâmetros de Pesquisa</div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_inicio", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicio'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span4'>
<!--                         <h5 class='asteristico'>*</h5> -->
                            <input type="text" name="data_inicio" id="data_inicio" size="12" maxlength="10" class='span12' value= "<?=$data_inicio?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_fim", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_fim'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
<!--                         <h5 class='asteristico'>*</h5> -->
                            <input type="text" name="data_fim" id="data_fim" size="12" maxlength="10" class='span12' value="<?=$data_fim?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("atendimento", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='atendimento'>Atendimento</label>
                <div class='controls controls-row'>
                    <div class='span6'>
                        <input type="text" name="atendimento" id="atendimento" size="12" maxlength="10" class='span12' value= "<?=$atendimento?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='status'>Status</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="status" id="status">
                            <option value="">SELECIONE</option>
<?php
    $sql = "SELECT  DISTINCT
                    status
            FROM    tbl_hd_status
            WHERE   fabrica = $login_fabrica
            AND     status <> 'RESOLVIDO'
      ORDER BY      status
    ";
    $res = pg_query($con,$sql);
    if(pg_numrows($res)>0){
        for($x=0;pg_numrows($res)>$x;$x++){
            $xstatus = pg_result($res,$x,status);
?>
                            <option value='<?=$xstatus?>' <?=($xstatus == $status) ? "SELECTED" : ""?>><?=$xstatus?></option>
<?php
        }
    }
?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group <?=(in_array("situacao_atendimento", $msg_erro["campos"])) ? "error" : ""?>'>
                <input type="radio" value="todas" name="situacao_atendimento"  <?=($situacao_atendimento == "todas") ? "checked" : ""?> />Todas
                &nbsp;&nbsp;
                <input type="radio" value="aberta" name="situacao_atendimento"  <?=($situacao_atendimento == "aberta") ? "checked" : ""?> />Abertas
                &nbsp;&nbsp;
                <input type="radio" value="fechada" name="situacao_atendimento" <?=($situacao_atendimento == "fechada") ? "checked" : ""?> />Fechadas
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <br />
    <p class="tac">
        <input type="submit" class="btn" name="pesquisar" value="Pesquisar" />
    </p>
    <br />
</form>

<?php
if (filter_input(INPUT_POST,"pesquisar") && !is_array($msg_erro)) {
    if (pg_num_rows($resHD) > 0) {
?>
<table id="table_callcenter_tempo_status" class='table table-striped table-bordered table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th >+</th>
            <th >Atendimento</th>
            <th >Data Abertura</th>
        </tr>
    </thead>
    <tbody>
<?php
        $data = date("d-m-Y-H-i");
        $fileName = "relatorio_tempo_status_{$data}.csv";
        $file = fopen("/tmp/{$fileName}", "w");

        $head = "Atendimento;Data Abertura;Status;Tempo\r\n";

        fwrite($file, $head);
        $body = '';

        while ($hd = pg_fetch_object($resHD)) {
?>
        <tr id="linha_<?=$hd->hd_chamado?>">
            <td style="text-align:center;"><span onclick='mostraTempo(<?=$hd->hd_chamado?>)' class='mais'>+</span></td>
            <td style="text-align:center;"><a href='callcenter_interativo_new.php?callcenter=<?=$hd->hd_chamado?>' target="blank" ><?=$hd->hd_chamado?></a></td>
            <td style="text-align:center;"><?=$hd->data_abertura?></td>
        </tr>
<?php
            $sqlHDI = "
                SELECT  tbl_hd_chamado_item.hd_chamado_item,
                        tbl_hd_chamado_item.status_item,
                        TO_CHAR(tbl_hd_chamado_item.data,'YYYY-MM-DD') AS data
                FROM    tbl_hd_chamado_item
                WHERE   hd_chamado = ".$hd->hd_chamado."
          ORDER BY      hd_chamado_item
            ";
            $resHDI = pg_query($con,$sqlHDI);

            if (pg_num_rows($resHDI)) {
?>
        <tr id='linha_chamado_<?=$hd->hd_chamado?>_hidden' style='display:none'>
            <td colspan='3' style='padding:0px !important'>
                <table class='table table-bordered table-large table-fixed'>
                    <thead>
                        <th>Status</th>
                        <th>Tempo</th>
                    </thead>
                    <tbody>
<?php
                $numero = 0;
                unset($ordena);
                unset($linha);
                while ($item = pg_fetch_object($resHDI)) {
                    $status_item = $item->status_item;
                    $data = $item->data;


                    $ordena[$numero][$status_item] = $data;
                    $numero++;
                }

                $item_antigo    = "";
                $valorHoras     = "";
                $aux            = "";
                $auxLinha       = "";
                $ultimoId       = "";
                foreach ($ordena as $ordem => $mostraitem) {
                    $aux = key($mostraitem);
                    if ($item_antigo != $aux && $item_antigo != "") {
                        $horaNova                       = date_create($mostraitem[$aux]);
                        $horaAntiga                     = date_create($valorHoras);
                        $intervalo                      = date_diff($horaAntiga,$horaNova);
                        $linha[$ordem][$item_antigo]    = $intervalo->format("%a dias");

                        $item_antigo    = $aux;
                        $valorHoras     = $mostraitem[$aux];
                        $ultimoId       = $ordem;
                    } else if ($item_antigo == "") {
                        $item_antigo    = $aux;
                        $valorHoras     = $mostraitem[$aux];
                    } else {
                        continue;
                    }
                }

                if ($aux != "RESOLVIDO") {
                    $horaNova   = date_create(Date('Y-m-d'));
                    $horaAntiga = date_create($valorHoras);
                    $intervalo              = date_diff($horaAntiga,$horaNova);
                    $linha[$ultimoId+1][$aux] = $intervalo->format("%a dias");
                }

                foreach ($linha as $item_linha => $valor_linha) {
                    $auxLinha = key($valor_linha);
                    $body .= $hd->hd_chamado."; ".$hd->data_abertura."; ".$auxLinha." ; ".$valor_linha[$auxLinha]."\r\n";
?>
                        <tr>
                            <td><?=$auxLinha?></td>
                            <td><?=$valor_linha[$auxLinha]?></td>
                        </tr>
<?php
                }
?>
                    </tbody>
                </table>
            </td>
        </tr>
<?php
            }
        }
?>
    </tbody>
    <tfoot>
<?php
        fwrite($file, $body);
        fclose($file);
        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");
        }
?>
        <div id='container' style='width: 150px; height: 50px; margin: 0 auto'>
            <img width='20' height='20' src='imagens/excel.png'> <a href='xls/<?=$fileName?>'>Gerar Arquivo Excel</a>
        </div>
    </tfoot>
</table>
<?php
    } else {
?>
<div class='container'>
    <div class='alert'>
            <h4>Nenhum resultado encontrado</h4>
    </div>
</div>
<?php
    }
}
include "rodape.php";

?>
