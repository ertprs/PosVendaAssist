<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include __DIR__.'/funcoes.php';

use Posvenda\Cockpit;

include 'cockpit/api/persys.php';

$cockpit = new Cockpit($login_fabrica);

$ticket = $_GET["ticket"];

$sql = "
    SELECT tbl_hd_chamado.hd_chamado, tbl_os.os, tbl_hd_chamado_cockpit.hd_chamado_cockpit_prioridade, tbl_tecnico_agenda.tecnico, tbl_tecnico_agenda.data_agendamento
    FROM tbl_hd_chamado_cockpit
    INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_cockpit.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
    INNER JOIN tbl_os ON tbl_os.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_os.fabrica = {$login_fabrica}
    INNER JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = {$login_fabrica}
    WHERE tbl_hd_chamado_cockpit.fabrica = {$login_fabrica}
    AND tbl_hd_chamado_cockpit.hd_chamado_cockpit = {$ticket}
";
$res = pg_query($con, $sql);

$osTelecontrol       = pg_fetch_result($res, 0, "os");
$telecontrolProtocol = pg_fetch_result($res, 0, "hd_chamado");
$priority            = pg_fetch_result($res, 0, "hd_chamado_cockpit_prioridade");
$technical_selected  = pg_fetch_result($res, 0, "tecnico");
$scheduled_date      = pg_fetch_result($res, 0, "data_agendamento");

$ticket_data               = $cockpit->getDadosTicket($ticket);
$ticket_data["cepCliente"] = str_replace("-", "", $ticket_data["cepCliente"]);

$osKof              = $ticket_data["osKof"];
$clientId           = $ticket_data["idCliente"];
$clientName         = $ticket_data["nomeFantasia"];
$distributionCenter = $ticket_data["centroDistribuidor"];
$product            = $cockpit->getProdutoByRef($ticket_data["modeloKof"]);

$client_address = array(
    "address"       => $ticket_data["enderecoCliente"],
    "neighbordhood" => $ticket_data["bairroCliente"],
    "city"          => $ticket_data["cidadeCliente"],
    "state"         => $ticket_data["estadoCliente"],
    "country"       => $ticket_data["paisCliente"],
    "zip_code"      => $ticket_data["cepCliente"]
);
$client_address = json_encode($client_address);

$call_type          = $ticket_data["tipoOrdem"];
$call_type_warranty = $ticket_data["garantia"];

?>

<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="cockpit/css/map.css" />
<link type="text/css" rel="stylesheet" media="screen" href="cockpit/css/technical_schedule.css" />
<link type="text/css" rel="stylesheet" media="screen" href="plugins/mapbox/map.css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js" ></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js" ></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/jquery.ui.autocomplete.js" ></script>
<script src="plugins/jquery.mask.js" ></script>
<script src="plugins/mapbox/map.js" ></script>
<script src="plugins/mapbox/mapbox.js" ></script>
<script src="plugins/mapbox/geocoder.js" ></script>
<script src="plugins/mapbox/polyline.js" ></script>
<script src="cockpit/js/form_wizard.js" ></script>
<script src="cockpit/js/map.js" ></script>
<script src="cockpit/js/technical_schedule.js" ></script>

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

var technical_changed = function (ticket, technical) {
    window.parent.technical_changed(ticket, technical);
};

$(window).on("load", function() {
    $("#cockpitFormWizard").cockpit_form_wizard({
        ticket: <?=$ticket?>,
        scheduled: true,
        os_kof: <?=$osKof?>,
        client_id: <?=$clientId?>,
        client_name: "<?=$clientName?>",
        telecontrol_protocol: "<?=$telecontrolProtocol?>",
        os_telecontrol: "<?=$osTelecontrol?>",
        priority: "<?=$priority?>",
        call_type: "<?=$call_type?>",
        call_type_warranty: "<?=$call_type_warranty?>",
        product: <?=$product["produto"]?>,
        distribution_center: "<?=$distributionCenter?>"
    });

    <?php
    if (!empty($scheduled_date)) {
    ?>
        form_wizard.technical      = <?=$technical_selected?>;
        form_wizard.scheduled_date = "<?=$scheduled_date?>";
    <?php
    }
    ?>

    map.init();
    technical_schedule.init();

    map.technical_selected = <?=$technical_selected?>;
    map.set_client_address(<?=$client_address?>);
    technical_schedule.save_callback = technical_changed;

    $("div.module_actions").prepend("\
        <button type='button' class='btn btn-danger cancel_technical_change_button' >Cancelar</button>\
    ");

    $(".cancel_technical_change_button").on("click", function() {
        window.parent.close_modal_technical_change();
    });

    window.delay = function(fnc) {
        if (navigator.userAgent.match(/OPR/g)) {
            setTimeout(fnc, delay);
        } else {
            window.setTimeout(fnc, 100);
        }
    };

    window.delay(form_wizard.activeFirstTab());
});

</script>