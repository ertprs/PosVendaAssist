<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "gerencia";
include "autentica_admin.php";

if ($_GET["ajax_file_link"]) {
    try {
        include __DIR__."/../class/tdocs.class.php";
        $tdocs = new TDocs($con, $login_fabrica);

        $log_id = $_GET["log_id"];

        $attach = $tdocs->getDocumentsByRef($log_id, "log")->attachListInfo;
        $link   = $tdocs->getDocumentLocation(key($attach));
        
        exit(json_encode(array(
            "link" => utf8_encode($link)
        )));
    } catch(Exception $e) {
        exit(json_encode(array(
            "error" => utf8_encode($e->getMessage())
        )));
    }
}

if (isset($_POST["search"])) {
    $error = array("message" => array(), "inputs" => array());

    $date = $_POST["date"];

    if (empty($date)) {
        $error["message"]["required"] = "Preencha os campos obrigatórios";
        $error["inputs"][]     = "date";
    } else {
        list($day, $month, $year) = explode("/", $date);

        $date = "{$year}-{$month}-{$day}";

        if (!strtotime($date)) {
            $error["message"]["invalid_date"] = "Data inválida";
            $error["inputs"][]                = "date";
        }
    }

    if (empty($error["message"])) {
        $sql = "
            SELECT 
                rsl.routine_schedule_log AS id,
                TO_CHAR(rsl.date_start, 'DD/MM/YYYY HH24:MI:SS') AS initial_date,
                TO_CHAR(rsl.date_finish, 'DD/MM/YYYY HH24:MI:SS') AS end_date,
                rsl.date_start,
                rsl.total_record AS total_tickets,
                rsl.total_record_processed AS total_tickets_scheduled
            FROM tbl_routine r
            INNER JOIN tbl_routine_schedule rs ON rs.routine = r.routine
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule = rs.routine_schedule
            WHERE r.factory = {$login_fabrica}
            AND rsl.create_at BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'
            AND rsl.date_finish IS NOT NULL
            AND rsl.file_name IS NOT NULL
            AND LOWER(r.context) LIKE 'abertura de tickets%'
            ORDER BY rsl.date_start DESC
        ";
        $qry = pg_query($con, $sql);

        if (!pg_num_rows($qry)) {
            $error["message"]["result_not_found"] = "Nenhum resultado encontrado";
        } else {
            $result = pg_fetch_all($qry);
        }
    }

}

$layout_menu = "gerencia";
$title       = "Log do Auto Agendamento";

include "cabecalho_new.php";

if (count($error["message"]) > 0) { 
?>
    <div class="alert alert-error" >
        <h4><?=implode("<br />", $error["message"])?></h4>
    </div>
<?php
}
?>

<div class="row" >
    <b class="obrigatorio pull-right" >* Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>

    <br />

    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span2" >
            <div class="control-group <?=(in_array('date', $error['inputs'])) ? 'error' : ''?>" >
                <div class="controls controls-row" >
                    <label class="control-label" for="date">Data</label>
                    <div class="controls controls-row" >
                        <h5 class="asteristico" >*</h5>
                        <input type="text" class="date span12" name="date" value="<?=getValue('date')?>" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br />

    <div class="row-fluid tac" >
        <button type="submit" name="search" class="btn" >Pesquisar</button>
    </div>
</form>

<?php
if ($result) {
?>
    <table class="table table-bordered table-striped table-hover table-fixed table-result" >
        <thead>
            <tr class="titulo_coluna" >
                <th>Data de Inicio</th>
                <th>Data de Término</th>
                <th>Total Atendimentos</th>
                <th>Total Agendados</th>
                <th>Arquivo de Log</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($result as $i => $c) {
                echo "
                    <tr>
                        <td class='tac' >{$c['initial_date']}</td>
                        <td class='tac' >{$c['end_date']}</td>
                        <td class='tac' >{$c['total_tickets']}</td>
                        <td class='tac' >".(int) $c['total_tickets_scheduled']."</td>
                        <td class='tac' >
                            <button type='button' class='btn btn-info btn-small view-log-file' data-log-id='{$c['id']}' ><i class='icon-file icon-white' ></i> Visualizar</button>
                        </td>
                    </tr>
                ";
            }
            ?>
        </tbody>
    </table>
<?php
}

$plugins = array(
    "datepicker",
    "mask",
    "dataTable"
);

include __DIR__.'/plugin_loader.php';
?>

<script>

$("input.date").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

$("button.view-log-file").on("click", function() {
    var link = $(this).data("link");

    if (link) {
        window.open(link);
        return false;
    }

    var logId = $(this).data("log-id");
    var btn   = $(this);

    $.ajax({
        async: true,
        url: "log_auto_agendamento.php",
        type: "get",
        data: {
            ajax_file_link: true,
            log_id: logId
        },
        timeout: 30000,
        beforeSend: function() {
            $(btn).prop({ disabled: true }).html("<i class='icon-file icon-white' ></i> Preparando Arquivo...");
        }
    })
    .fail(function(r) {
        alert("Erro ao preparar arquivo, tempo limite esgotado");
        $(btn).prop({ disabled: false }).html("<i class='icon-file icon-white' ></i> Visualizar");
    })
    .done(function(r) {
        r = JSON.parse(r);

        if (r.error) {
            alert(r.error);
        } else {
            $(btn).data({ "link": r.link });
            window.open(r.link);
        }

        $(btn).prop({ disabled: false }).html("<i class='icon-file icon-white' ></i> Visualizar");
    });
});

$.dataTableLoad({
    table: ".table-result",
    type: "custom",
    sorting: false,
    config: ["info"]
});

</script>

<?php
include "rodape.php";
?>
