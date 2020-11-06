<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include __DIR__.'/funcoes.php';

use Posvenda\Cockpit;

include 'cockpit/api/persys.php';

$technical_id   = $_GET["technical_id"];
$maximum_amount = $_GET["maximum_amount"];

$sql = "
    SELECT MIN(tbl_tecnico_agenda.data_agendamento) AS minDate
    FROM tbl_tecnico_agenda
    INNER JOIN tbl_os ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_os.fabrica = {$login_fabrica} AND tbl_os.finalizada IS NULL
    WHERE tbl_tecnico_agenda.fabrica = {$login_fabrica}
    AND tbl_tecnico_agenda.tecnico = {$technical_id}
";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
    $minDate = pg_fetch_result($res, 0, "minDate");
}

?>

<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css?<?=rand()?>" />
<link type="text/css" rel="stylesheet" media="screen" href="cockpit/css/technical_schedule.css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js" ></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js" ></script>
<script src="plugins/jquery.mask.js" ></script>
<script src="cockpit/js/form_wizard.js?<?=rand()?>" ></script>
<script src="cockpit/js/technical_schedule.js?<?=rand()?>" ></script>

<?
$plugins = array(
    "datetimepicker",
    "maskedinput",
    "bootstrap3"
);

include __DIR__.'/plugin_loader.php';
?>

<div id="cockpitFormWizard" style="height: 100%;" ></div>

<script>

$(window).on("load", function() {
    $("#cockpitFormWizard").cockpit_form_wizard();

    technical_schedule.init();
    technical_schedule.setTechnical({
        id: <?=$technical_id?>,
        internal: true,
        maximum_amount: <?=$maximum_amount?>
    });

    <?php
    if (!empty($minDate)) {
    ?>
        technical_schedule.init_date = "<?=$minDate?>";
    <?php
    }
    ?>

    window.delay = function(fnc) {
        if (navigator.userAgent.match(/OPR/g)) {
            setTimeout(fnc, delay);
        } else {
            window.setTimeout(fnc, 100);
        }
    };

    window.delay(form_wizard.activeFirstTab());
});

function close_modal_technical_change() {
    $("#change_technical_modal iframe").attr({ src: "" });
    $("#change_technical_modal").modal('hide');
}

window.technical_changed = function(ticket, technical) {
    $("#change_technical_modal iframe").attr({ src: "" });
    $("#change_technical_modal").modal('hide');

    if (technical != technical_schedule.technical_selected) {
        technical_schedule.remove_schedule(null, ticket);
        technical_schedule.create_week_table(technical_schedule.load_week_post_it);

        form_wizard.showSuccess("Atendimento transferido com sucesso");
    } else {
        form_wizard.showSuccess("Alterações salvas com sucesso");
    }
}

</script>