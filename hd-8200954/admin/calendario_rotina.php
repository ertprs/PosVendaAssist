<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "call_center";
include 'autentica_admin.php';

include_once "class/tdocs.class.php";

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;

$oRoutine = new Routine();
$oRoutineSchedule = new RoutineSchedule();
$oLog = new Log();

$oRoutine->setFactory($login_fabrica);

$s3_tdocs = new TDocs($con, $login_fabrica);

if (isset($_POST['btnAcao'])) {
    $data_inicial = $_POST['data_inicial'];
    $data_final = $_POST['data_final'];
    $status = $_POST['status'];
    $tipo_ordem = $_POST['tipo_ordem'];
    $familia = $_POST['familia'];

    if (strlen($data_inicial) == 0 && strlen($data_final) == 0) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
            $msg_erro["campos"][] = "data_inicial";
            $msg_erro["campos"][] = "data_final";
    }

    if (count($msg_erro['msg']) == 0) {
        if (strlen($data_inicial) == 0) {
            $msg_erro["msg"][]    = "O campo Data Inicial não pode ser vazia";
            $msg_erro["campos"][] = "data_inicial";
        } else {
            $dat = explode ("/", $data_inicial);
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) {
                $msg_erro["msg"][]    = "O campo Data Inicial não contém uma data válida";
                $msg_erro["campos"][] = "data_inicial";
            } else {
                $aux_data_inicial = $dat[2].'-'.$dat[1].'-'.$dat[0];
            }
        }

        if (strlen($data_final) == 0) {
            $msg_erro["msg"][]    = "O campo Data Final não pode ser vazia";
            $msg_erro["campos"][] = "data_final";
        } else {
            $dat = explode ("/", $data_final);
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) {
                $msg_erro["msg"][]    = "O campo Data Final não contém uma data válida";
                $msg_erro["campos"][] = "data_final";
            } else {
                $aux_data_final = $dat[2].'-'.$dat[1].'-'.$dat[0];
            }
        }

        if (count($msg_erro['msg']) == 0) {
            if (strtotime($aux_data_inicial) > strtotime($aux_data_final)) {
                $msg_erro["msg"][]    = "O campo Data Inicial é maior que o campo Data Final";
                $msg_erro["campos"][] = "data_inicial";
                $msg_erro["campos"][] = "data_final";
            }
        }
    }
}

$routineSelected = (!empty($_REQUEST['routine'])) ? $_REQUEST['routine'] : $_REQUEST['routine_selected'];

if (!empty($routineSelected)) {
    $oRoutineSchedule->setRoutine($routineSelected);
    $rotinas = $oRoutineSchedule->SelectRoutineScheduleWithContext($login_fabrica);
} else {
    $rotinas = $oRoutineSchedule->SelectRoutineScheduleWithContext($login_fabrica);
}

$title = "Monitor Interfaces de Ordens de Serviço";

if ($login_fabrica != 158) {
    $title = "Monitor de rotinas automatizadas";
}

$layout_menu = "callcenter";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "mask",
    "dataTable"
);

include __DIR__.'/plugin_loader.php';
?>

<script type="text/javascript">
$(function(){

    $.datepickerLoad(Array("data_inicial", "data_final"));

    Shadowbox.init();

    $(document).on('click', 'button[name=btnError]', function(){

        var linha = $(this).attr('rel');
        var schedule_log = $("input[name=routineScheduleLog_"+linha+"]").val();

        shadow_width = screen.width * 98/100;

        Shadowbox.open({
            content: "calendario_rotina_ajax.php?show_errors=t&schedule_log="+schedule_log,
            player: "iframe",
            height: 600,
            width: shadow_width
        })
    });

    $(document).on('change', '#routine', function() {
        $(this).closest('form').trigger('submit');
    });

});

</script>

<?

$total_rotinas = count($rotinas);
$data_anterior = "";

if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?= $msg_erro["msg"][0]; ?></h4>
    </div>
<? } ?>

<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>

<form name="frm_filtro_routine" method="POST" action='<?= $PHP_SELF; ?>' align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela'>Filtrar Rotinas</div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8 tac'>
            <div class='control-group'>
                <label class='control-label' for='routine'>Contexto:</label>
                <div class='controls controls-row'>
                    <select name="routine" id="routine" class="span12">
                        <option value="">SELECIONE</option>
                        <? $rotinasSelect = $oRoutine->SelectRoutine();
                            foreach ($rotinasSelect as $rotinaLinha) {
                                $rotina = $rotinaLinha['routine'];
                                $contexto = $rotinaLinha['context'];
                                $selectedContext = ($rotina == $routineSelected) ? "SELECTED" : ""; ?>
                                <option value="<?= $rotina; ?>" <?= $selectedContext; ?>><?= $contexto; ?></option>
                        <? } ?>
                    </select>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
</form>

<form name="frm_busca_log" method="POST" action='<?= $PHP_SELF; ?>' align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa (Resultados de Execuções)</div>
    <br />
    <input type="hidden" name="routine_selected" value="<?= $routineSelected; ?>" />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial:</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <input type="text" name="data_inicial" id="data_inicial" class="span6" value="<?= $data_inicial; ?>" />
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final:</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <input type="text" name="data_final" id="data_final" class="span6" value="<?= $data_final; ?>" />
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='status'>Status:</label>
                <div class='controls controls-row'>
                    <select name="status" id="status" class="span10">
                        <option value=""<?= (strlen($status) == 0 && $status == "") ? " SELECTED" : ""; ?>></option>
                        <option value="1"<?= (strlen($status) > 0 && $status == 1) ? " SELECTED" : ""; ?>>Sucesso</option>
                        <option value="2"<?= (strlen($status) > 0 && $status == 2) ? " SELECTED" : ""; ?>>Processado Parcial</option>
                        <option value="0"<?= (strlen($status) > 0 && $status == 0) ? " SELECTED" : ""; ?>>Erro</option>
                    </select>
                </div>
            </div>
        </div>
        <? if ($login_fabrica == 158) { ?>
            <div class='span3'>
                <div class="control-group">
                    <label class="control-label" for="tipo_ordem">Erros p/ Tipo Atendimento:</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <select id="tipo_ordem" name="tipo_ordem" class="span12">
                                <option value="">Selecione</option>
                                <option value="ZKR1" <?= ($tipo_ordem == 'ZKR1') ? "SELECTED" : ""; ?>>ZKR1 - Movimentação</option>
                                <option value="ZKR2" <?= ($tipo_ordem == 'ZKR2') ? "SELECTED" : ""; ?>>ZKR2 - Movimentação Usado</option>
                                <option value="ZKR3" <?= ($tipo_ordem == 'ZKR3') ? "SELECTED" : ""; ?>>ZKR3 - Corretiva</option>
                                <option value="ZKR5" <?= ($tipo_ordem == 'ZKR5') ? "SELECTED" : ""; ?>>ZKR5 - Preventiva</option>
                                <option value="ZKR6" <?= ($tipo_ordem == 'ZKR6') ? "SELECTED" : ""; ?>>ZKR6 - Preventiva</option>
                                <option value="ZKR9" <?= ($tipo_ordem == 'ZKR9') ? "SELECTED" : ""; ?>>ZKR9 - Piso</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        <? } ?>
        <div class="span2"></div>
    </div>
    <?
    $oRoutine->setRoutine($routineSelected);
    if ($oRoutine->SelectRoutine("Imp. Arquivos KOF")) { ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span3'>
                <div class="control-group">
                    <label class="control-label" for="familia">Família:</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <select id="familia" name="familia" class="span12">
                                <option value="">Selecione</option>
                                <?
                                $sql = "SELECT * FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo IS TRUE;";
                                $res = pg_query($con, $sql);

                                if (pg_num_rows($res) > 0) {
                                    while ($result = pg_fetch_object($res)) {
                                        $selected = (trim($result->descricao) == $familia) ? "SELECTED" : ""; ?>
                                        <option value='<?= $result->descricao; ?>' <?= $selected; ?> ><?= $result->descricao; ?></option>
                                    <? }
                                } else { ?>
                                <? } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
    <? } ?>
    <br />
    <div class="row-fluid tac">
    <input type="hidden" name="btnAcao" value="" />
        <button type="button" name="btnPesquisar" class="btn btn-default" onclick="if ($('input[name=btnAcao]').val() == '') { $('form[name=frm_busca_log]').submit(); } else { alert('Aguarde o processamento do formulário!'); }">Pesquisar</button>
    </div>
</form>

<table id="tbl_routine_schedule" class="table table-striped table-bordered table-hover table-large">
    <thead>
        <tr class="titulo_coluna">
            <th rowspan="2">Contexto</th>
            <th colspan="5">Agendamento</th>
        </tr>
        <tr class="titulo_coluna">
            <th>Mês</th>
            <th>Dia da Semana</th>
            <th>Hora</th>
            <th>Minuto</th>
            <th>Fuso</th>
        </tr>
    </thead>
    <tbody>
        <? foreach($rotinas as $linha => $rotina) { ?>
            <tr>
                <td><?= $rotina['context']; ?></td>
                <td><?= $rotina['month_day']; ?></td>
                <td class="tac">
                    <?
                    switch ($rotina['week_day']) {
                        case '0': echo 'Domingo'; break;
                        case '1': echo 'Segunda-feira'; break;
                        case '2': echo 'Terça-feira'; break;
                        case '3': echo 'Quarta-feira'; break;
                        case '4': echo 'Quinta-feira'; break;
                        case '5': echo 'Sexta-feira'; break;
                        case '6': echo 'Sábado'; break;
                        default: echo ""; break;
                    }
                    ?>
                </td>
                <td class="tac"><?= $rotina['hour']; ?></td>
                <td class="tac"><?= (strlen($rotina['hour']) == 0) ? "a cada ".$rotina['minute']." minutos" : $rotina['minute']; ?></td>
                <td><?= date_default_timezone_get('America/Brasilia'); ?></td>
            </tr>
            <? $routines_schedule[] = $rotina['routine_schedule'];
        } ?>
    </tbody>
</table>
<br />

<?
$routines_schedule = implode(',', $routines_schedule);
$semFiltro = false;

if (isset($_POST['btnAcao'])) {
    if (count($msg_erro['msg']) == 0) {
        $oLog->setStatus($status);
        $log_rotinas = $oLog->SelectRoutinesLog($routines_schedule, $aux_data_inicial, $aux_data_final, $tipo_ordem, $familia, $login_fabrica);
    }
} else if (!empty($_GET["log"])) {
    $log_rotinas = $oLog->SelectRoutinesLog($routines_schedule, null, null, null, null, $login_fabrica, $_GET["log"]);
} else {
    $aux_data_final = date("Y-m-d");
    $aux_data_inicial = date("Y-m-d");
    $log_rotinas = $oLog->SelectRoutinesLog($routines_schedule, $aux_data_inicial, $aux_data_final);
    $semFiltro = true;
}
if (count($msg_erro['msg']) == 0) {
    if (!$log_rotinas) { ?>
        <div class="alert">
            <h4>Nenhum resultado encontrado</h4>
        </div>
    <? } else {
        if ($semFiltro == true) { ?>
            <div class="alert">
                <h4>Execuções dos últimos 3 dias</h4>
            </div>
        <? } ?>
        </div>
        <table id="relatorios_log_rotinas" class="table table-striped table-bordered table-hover">
            <thead>
                <tr class="titulo_coluna">
                    <th class="tac" colspan="10">Resultado das Rotinas executadas</th>
                </tr>
                <tr class="titulo_coluna">
                    <th>Contexto</th>
                    <th>Data Início</th>
                    <th>Data Fim</th>
                    <th>Fuso</th>
                    <th>Nome do Arquivo</th>
                    <th>Linhas no Arquivo</th>
                    <th>Registros</th>
                    <th>Registros com Sucesso</th>
                    <th>Mensagem de Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($log_rotinas as $key => $log) {
                    $context = $log['context'];
                    $routine_schedule_log = $log['routine_schedule_log'];
                    $date_start = $log['date_start'];
                    $date_finish = $log['date_finish'];
                    $file_name = $log['file_name'];
                    $total_line_file = $log['total_line_file'];
                    $total_record = $log['total_record'];
                    $total_record_processed = $log['total_record_processed'];
                    $status = $log['status'];
                    $status_message = ($date_finish == "") ? "Processando..." : utf8_decode($log['status_message']);

                    if ($status != 1) {
                        $lineColor = "class='alert-danger'";
                    } else {
                        $lineColor = "";
        		    }

        		    if (strtolower($context) == 'retorno kof') {
        			    $caminho = "imbera/retorno-kof/";
        		    } else {
        			    $caminho = "imbera/processado/";
        		    }
        		    ?>
                    <tr <?= $lineColor; ?>>
                        <td class="tac"><?= $context; ?></td>
                        <td><?= $date_start; ?></td>
                        <td><?= $date_finish; ?></td>
                        <td><?= date_default_timezone_get('America/Brasilia'); ?></td>
                        <td><a href="<?= $caminho.$file_name; ?>" target="_blank"><?= $file_name; ?></a></td>
                        <td class="tac"><?= $total_line_file; ?></td>
                        <td class="tac"><?= $total_record; ?></td>
                        <td class="tac"><?= $total_record_processed; ?></td>
                        <td class="tac"><?= $status_message; ?></td>
                        <td>
                            <? if (in_array($login_fabrica, array(169,170))) {
                                $s3_tdocs->setContext('fabrica', 'log');
                                $link_tdocs = $s3_tdocs->getDocumentsByRef($routine_schedule_log)->url;
                                if (!empty($link_tdocs)) { ?>
                                    <a class="btn btn-info btn-mini" href="<?= $link_tdocs;?>" target="_blank">Ver log</a>
                                <? }

                                $s3_tdocs->setContext('fabrica', 'logsimples');
                                $link_tdocs_simples = $s3_tdocs->getDocumentsByRef($routine_schedule_log)->url;
                                if (!empty($link_tdocs_simples)) { ?>
                                    <br/><br/>
                                    <a class="btn btn-info btn-mini" href="<?= $link_tdocs_simples;?>" target="_blank">Ver log simples</a>
                                <? }

                            }
                            if ($total_record > $total_record_processed) { ?>
                                <input name="routineScheduleLog_<?= $key; ?>" type="hidden" value="<?= $routine_schedule_log; ?>">
                                <button name="btnError" class="btn btn-info btn-mini" rel="<?= $key; ?>">Erros</button>
                            <? } ?>
                        </td>
                    </tr>
                <? } ?>
            </tbody>
        </table>
        <script>
            $.dataTableLoad({
                table: "#relatorios_log_rotinas",
                aaSorting: []
            });
        </script>
    <? }
} ?>

<? include "rodape.php"; ?>
