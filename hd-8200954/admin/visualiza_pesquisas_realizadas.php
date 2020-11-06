<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$posto    = $_REQUEST["posto"];

try {

	$easyBuilderMirror = new \Mirrors\EasyBuilderMirror($login_fabrica, 'questionario_avaliacao', $login_admin);


	$dadosPesquisa = $easyBuilderMirror->getAll((object)[
		"posto" => $posto
	], false);

	$arrFormulario = json_decode($dadosPesquisa["campos"][0]["formulario"], true);
	$arrFormulario = array_map_recursive("utf8_decode", $arrFormulario);


} catch(\Exception $e){

    $msg_erro["msg"][] = utf8_decode($e->getMessage());

}
?>
<html>
	<head>
		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
		<?php
		$plugins = array(
		   "bootstrap3",
		   "dataTableAjax",
		   "datepicker",
		   "mask"
		);

		include "plugin_loader.php";

		?>
	</head>
<body>

</body>
</html>