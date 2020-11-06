<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$cpf_cnpj = $_GET["cpf_cnpj"];

?>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<div class="row-fluid" style="position: absolute;top: 20%;">
	<div class="span2"></div>
	<div class="alert span8">
		<h5><div style="text-align: center;font-size: 14pt;">Existe um atendimento com o CPF <strong><?= $cpf_cnpj ?></strong></div><br /><br />Você pode clicar na lupa do CPF para localizar o atendimento e dar continuidade</h5>
	</div>
</div>