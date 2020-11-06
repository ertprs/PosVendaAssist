<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
 
function close () {
	echo "<script> window.close(); </script>";
}

if (isset($_GET["os"]) && !empty($_GET["os"])) {
	$os = $_GET["os"];
	$i  = $_GET["posicao"];

	$sql = "SELECT os FROM tbl_os WHERE fabrica = $login_fabrica AND os = {$os}";
	$res = pg_query($con, $sql);

	if (!pg_num_rows($res)) {
		close();
	}
} else {
	close();
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Fechar OS - <?=$os?></title>

		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
		<style>
			.lp_nova_pesquisa {
				padding-top: 10px;
				padding-bottom: 10px;
				text-align: center;
			}

			#motivo_sem_pagamento {
				display: none;
			}

			#motivo_sem_pagamento b {
				color: #FF0000;
				font-size: 12px;
			}
		</style>

		<script src="js/jquery-1.6.2.js"></script>
		<script>
			$(function () {
				$("#fecha_os_com_pagamento").click(function () {
					window.opener.fecha_os_30_dias($("#os").val(), $("#i").val());
					window.close();
				});

				$("#fecha_os_sem_pagamento").click(function () {
					$("#buttons").hide();
					$("#motivo_sem_pagamento").show();

					$("#continuar").bind("click", function() {
						if ($.trim($("#motivo").val()).length > 0) {
							window.opener.fecha_os_30_dias($("#os").val(), $("#i").val(), $.trim($("#motivo").val()), true);
							window.close();
						} else {
							alert("Digite um motivo");
						}
					});
				});
			});
		</script>
	</head>
	<body>
		<div class="lp_header">
		</div>
		<div class="lp_nova_pesquisa">
			<input type="hidden" id="os" value="<?=$os?>" />
			<input type="hidden" id="i" value="<?=$i?>" />
			<div id="buttons">
				<button type="button" id="fecha_os_com_pagamento" >Fechar OS com pagamento</button>
				<button type="button" id="fecha_os_sem_pagamento" >Fechar OS sem pagamento</button>
			</div>
			<div id="motivo_sem_pagamento">
				<b>Informe o motivo para prosseguir com o fechamento da os sem pagamento</b>
				<br />
				<textarea id="motivo" rows="5" cols="40" ></textarea>
				<br />
				<button type="button" id="continuar">Continuar</button>
			</div>
		</div>
	</body>
</html>