<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include __DIR__.'/funcoes.php';

use Posvenda\Cockpit;

include 'cockpit/api/persys.php';

$ticket              = $_GET["ticket"];
$osKof               = $_GET["os-kof"];
$osTelecontrol       = $_GET["os-telecontrol"];
$telecontrolProtocol = $_GET["telecontrol-protocol"];
$priority            = $_GET["priority"];
$scheduled           = $_GET["scheduled"];
$clientName          = $_GET["client-name"];

//get json data
$cockpit  = new Cockpit($login_fabrica);

$jsonData = array_map(function($value) {
    return $value;
}, $cockpit->getDadosTicket($ticket));

$client_id           = $jsonData["idCliente"];
$call_type           = $jsonData["tipoOrdem"];
$call_type_warranty  = $jsonData["garantia"];
$distribution_center = $jsonData["centroDistribuidor"];
$product             = $cockpit->getProdutoByRef($jsonData["modeloKof"]);


if(isset($jsonData['codDefeito'])){
     $jsonData['defeitoReclamado'] = $jsonData['codDefeito'];
}

$jsonData = json_encode($jsonData);

array_walk_recursive($array_estados(), function(&$value, $key) {
    if (is_string($value)) {
        $value = iconv('ISO 8859-15', 'UTF-8', $value);
    }
});

$array_estados = array_map(function($x){
	return utf8_encode($x);
},$array_estados());
$arrayEstados = json_encode($array_estados);
$arrayPaises  = json_encode($array_pais);

if (!empty($osTelecontrol)) {
    $sql = "
        SELECT tecnico, data_agendamento
        FROM tbl_tecnico_agenda
        WHERE fabrica = {$login_fabrica}
        AND os = {$osTelecontrol}
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $technical      = pg_fetch_result($res, 0, "tecnico");
        $scheduled_date = pg_fetch_result($res, 0, "data_agendamento");
    }
}

if (!is_numeric($osKof)) {
    $msg_erro = "Número de OS KOF inválido";
}

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
<script src="plugins/leaflet/leaflet.js" ></script>
<script src="plugins/leaflet/map.js?time=<?=time()?>" ></script>
<script src="plugins/mapbox/geocoder.js" ></script>
<script src="plugins/mapbox/polyline.js" ></script>
<script src="cockpit/js/form_wizard.js" ></script>
<script src="cockpit/js/ticket_conference.js" ></script>
<script src="cockpit/js/technical_schedule.js" ></script>
<script src="cockpit/js/map.js?time=<?=time()?>" ></script>
<script src="cockpit/js/cockpit.js" ></script>

<?
$plugins = array(
    "datetimepicker",
    "maskedinput",
    "bootstrap3"
);

include __DIR__.'/plugin_loader.php';

if (strlen($msg_erro) > 0) { ?>
    <div class="alert alert-danger" >
        <h4><?= $msg_erro;?></h4>
    </div>
<? } ?>

<div id="cockpitFormWizard" style="height: 100%;" ></div>
 
<script>

$(window).on("load", function() {
    window.arrayEstados = <?= $arrayEstados; ?>;
    window.arrayPaises = <?= $arrayPaises; ?>;
    window.estadoSel = "";
    window.referencia = "";
    window.defeitoSel = "";
    window.tipoOrdemSel = "";

    $("#cockpitFormWizard").cockpit_form_wizard({
        ticket: <?=$ticket?>,
        scheduled: <?=$scheduled?>,
        os_kof: <?=$osKof?>,
        client_id: "<?=$client_id?>",
        client_name: "<?=$clientName?>",
        telecontrol_protocol: "<?=$telecontrolProtocol?>",
        os_telecontrol: "<?=$osTelecontrol?>",
        priority: "<?=$priority?>",
        call_type: "<?=$call_type?>",
        call_type_warranty: "<?=$call_type_warranty?>",
        product: <?= (empty($product["produto"])? "null": $product["produto"]) ?>,
        distribution_center: "<?=$distribution_center?>"
    });

    <?php
    if (!empty($technical)) {
    ?>
        form_wizard.technical      = <?=$technical?>;
        form_wizard.scheduled_date = "<?=$scheduled_date?>";
    <?php
    }
    ?>

    ticket_conference.init();
    map.init();
    technical_schedule.init();
    form_wizard.activeFirstTab();

    window.delay = function(fnc) {
        if (navigator.userAgent.match(/OPR/g)) {
            setTimeout(fnc, delay);
        } else {
            window.setTimeout(fnc, 100);
        }
    };
    window.delay(function() {
        ticket_conference.load(<?=$jsonData?>, ticket_conference.pop_form_data, ticket_conference.validate);
    });
});

</script>
