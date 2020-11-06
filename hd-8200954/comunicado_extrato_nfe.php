<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";
?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>
        <style type="text/css">
			p{
				font-size: 14px;
			}
			.fonts{
				font-size: 12px;
			}
		</style>
	</head>

	<body>
		<div id="container" style="overflow-y:auto;">
			<div class="row-fluid" style="margin-top: 30px;">
				<div class="span1"></div>
					<div class='span5'>
						<!-- <img class="espaco" style="width: 200px;" src="imagens/logo_new_telecontrol.png"> -->
						<img class="espaco" style="width: 140px;" src="logos/telecontrol_new_admin1.jpg">
						<br/>
					</div>
					<div class="span5">
						<?php
							if($login_fabrica == 157){
						?>
								<img style="width: 120px;" src="logos/logo_wap.jpg">
						<?php
							}
						?>
					</div>
				<div class="span1"></div>
			</div>
			<br/>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span10">
					<p>
					<?=traduz('Na página "Extrato", os extratos serão classificados por cores:')?>
					</p>
					<p>
						<strong style = "background: #FFFF99"><?=traduz('Amarelo:')?></strong><br/>
						<?=traduz('Extrato com envio de nota pendente.')?>
					</p>
					<p>
						<strong style = "background: #33CCFF">Azul:</strong><br/>
						<?=traduz('Extrato no qual a nota de pagamento foi enviada.')?>
					</p>
					<p>
						<img src="imagens/positec_extrato1.jpg" width="350">
					</p>
					<p>
						<?=traduz('Para envio da nota fiscal, clicar no botão "Detalhar", e clicar no botão "Selecionar Arquivo". Após selecionar o arquivo, clicar no botão "Salvar Arquivo".')?>
					</p>
					<p style="color: red">
						<strong>* <?=traduz('Atenção')?>: <?= ($login_fabrica == 183) ? traduz("Favor Preencher a Nota Fiscal de Mão de obra com o valor da Mão de Obra do extrato") : traduz("Favor preencher a Nota Fiscal de Mão-de-Obra com o mesmo valor que está em \"VALOR TOTAL DO EXTRATO\""); ?>.</strong>
					</p>
					<p>
						<img src="imagens/nfe_print.png" width="350">
					</p>
				</div>
				<div class="span1"></div>
			</div>
		</div>
	</body>
</html>

