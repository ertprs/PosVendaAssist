<?php
//include "/etc/telecontrol.cfg";
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';


include_once('anexaNF_inc.php');

$fabrica 	= $_REQUEST["fabrica"];
$os 		= $_REQUEST["os"];

$controle = 0;

if(isset($_POST["btn_acao"])){
	$arquivo 	= $_FILES["arquivo"];
	$os 		= $_POST["os"];

	$anexou = anexaNF($os, $arquivo);

	if ($anexou !== 0) {
		$msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou;
	}else{
		$sqlVerifica = "SELECT  tbl_os_status.status_os
						FROM    tbl_os_status
						WHERE   os = $os
						AND     status_os IN (189,190,191)
						ORDER BY      os_status DESC
						LIMIT   1";
		$resVerifica = pg_query($con,$sqlVerifica);

		if(pg_fetch_result($resVerifica,0,status_os) != 189){
			$sql = "INSERT INTO tbl_os_status (
							os,
							status_os,
							observacao
						) VALUES (
							$os,
							189,
							'OS em auditoria de nota fiscal, incluída na consulta'
						)";
			$res = pg_query($con, $sql);
		}
		$ok .= "Upload realizado com sucesso. <Br>";
	}

}




?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title><?=traduz('pesquisa.de.produto', $con)?></title>
	<meta name="Author" content="">
	<meta name="Keywords" content="">
	<meta name="Description" content="">
	<meta http-equiv="pragma" content="no-cache">

	<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
	<link rel="stylesheet" type="text/css" href="css/posicionamento.css">
	<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
	<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
	<script src="js/thickbox.js" type="text/javascript"></script>
	<script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>


	<style type="text/css">
				body {
					margin: 0;
					font-family: Arial, Verdana, Times, Sans;
					background: #fff;
				}
	</style>
	<script type='text/javascript'>
		//função para fechar a janela caso a telca ESC seja pressionada!
		/*$(window).keypress(function(e) {
			if(e.keyCode == 27) {
				 window.parent.Shadowbox.close();
			}
		});*/

		$(document).ready(function() {
			$("#gridRelatorio").tablesorter({
			    headers: {
			      5: {sorter: false}
				}
			});
		});
	</script>

</head>

<body>
	<div class="lp_header">
		<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
			<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
		</a>
	</div>
	<div class='lp_nova_pesquisa'>
		<form action='<?=$PHP_SELF?>' method='POST' name='novo_upload' enctype="multipart/form-data">
			<input type='hidden' name='voltagem' 		 value='<?=$voltagem?>' />
			<input type='hidden' name='tipo'     		 value='<?=$tipo?>' />
			<input type='hidden' name='posicao'  		 value='<?=$posicao?>' />
			<input type='hidden' name='tipo_atendimento' value='<?=$tipo_atendimento?>' />

			<table cellspacing='1' cellpadding='2' border='0'>
				<tr>
					<td>
						<label><?=traduz('O.S', $con)?></label>
						<?php echo $os ?>
						<input type="hidden" name="os" value="<?php echo $os ?>">
					</td>
					<td>
						<label><?=traduz('Imagem.da.NF', $con)?></label>
						<input type='file' name='arquivo' value='<?=$arquivo?>' style='width: 370px' maxlength='80' />
					</td>
					<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='<?=traduz('Fazer.Upload', $con)?>' /></td>
				</tr>
			</table>
		</form>
	</div>
	<?php

	if(strlen(trim($ok))>0):
		echo "<div style='color:blue'>$ok</div>";
	endif;

	if(strlen(trim($msg_erro))>0):
		echo "<div style='color:blue'>$msg_erro</div>";
	endif;

	?>

