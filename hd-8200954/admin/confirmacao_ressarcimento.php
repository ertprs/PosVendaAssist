<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include_once "class/aws/s3_config.php";
include_once S3CLASS;

$s3 = new AmazonTC("os", (int) $login_fabrica);

if (!$_GET["os"] || !strlen($_GET["os"])) {
	exit("OS não informada");
}

?>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>

<?php

if ($_POST) {
	$os = $_POST["os"];
	$file = $_FILES;

	if (empty($os)) {
		$msg_erro[] = "OS não informada";
	} else {
		$sql = "SELECT 
					DATE_PART('MONTH', data_abertura) AS mes, 
					DATE_PART('YEAR', data_abertura) AS ano 
				FROM tbl_os 
				WHERE fabrica = {$login_fabrica}
				AND os = {$os}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$msg_erro[] = "OS não encontrada";
		} else {
			$mes = pg_fetch_result($res, 0, "mes");
			$ano = pg_fetch_result($res, 0, "ano");
		}
	}

	if (!count($file) || !strlen($file["comprovante"]["name"])) {
		$msg_erro[] = "Anexe o comprovante de ressarcimento";
	}

	if (!count($msg_erro)) {
		$types = array("png", "jpg", "jpeg", "bmp", "pdf");
		$type  = strtolower(preg_replace("/.+\//", "", $file["comprovante"]["type"]));

		if ($type == "jpeg") {
			$type = "jpg";
		}

		if (strlen($file["comprovante"]["tmp_name"]) > 0 && $file["comprovante"]["size"] > 0) {
			if (!in_array($type, $types)) {
				$msg_erro[] = "Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp";
			} else {
				$insert = "INSERT INTO tbl_os_status 
					   (os, status_os, observacao, admin)
					   VALUES
					   ({$os}, 202, 'Ressarcimento confirmado pela fábrica', {$login_admin})";
				$res = pg_query($con, $insert);

				if (!pg_last_error()) {
					$sql = "SELECT fn_os_status_checkpoint_os({$os})";
					$res = pg_query($con, $sql);

					if (!strlen(pg_last_error())) {
						$s3->upload("comprovante_ressarcimento_{$os}", $file["comprovante"], $ano, $mes);
						?>
						<script>
							$(window.parent.document).find("button[rel=<?=$os?>]").parent("td").html("<div class='alert alert-success tac' style='margin-bottom: 0px;'>Ressarcimento confirmado</div>");
							window.parent.Shadowbox.close();
						</script>
					<?php
					} else {
						$msg_erro[] = "Erro ao anexar comprovante";
					}
				} else {
					$msg_erro[] = "Erro ao anexar comprovante";
				}
			}
		} else {
			$msg_erro[] = "Erro ao fazer o upload do arquivo";
		}
	}
}

?>

<script>
$(function() {
	$("button").click(function() {
		$(this).button("loading");
	});
});
</script>

<form method="post" enctype="multipart/form-data" >
	<input type="hidden" name="os" value="<?=$os?>" />
	<div class="conteudo" >
		<div class="titulo_tabela" >Anexar comprovante de ressarcimento</div>

		<div class="alert alert-error" style="margin-bottom: 0px; display: <?=(count($msg_erro) > 0) ? 'block' : 'none' ?>;">
			<?=implode("<br />", $msg_erro)?>
    	</div>

    	<br />

		<div class="row-fluid">
			<div class="span12">
				<div class="controls controls-row">
					<input type="file" name="comprovante" />
				</div>
			</div>
		</div>
		
		<p style="bottom: -11px; display: block; position: fixed; width: 100%;"><br />
			<button type="submit" data-loading-text="Anexando..." class="btn btn-info btn-block" >Anexar</button>
		</p><br/>
	</div>
</form>
