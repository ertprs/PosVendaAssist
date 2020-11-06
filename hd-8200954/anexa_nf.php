<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

include_once 'class/aws/s3_config.php';
include_once S3CLASS;

$s3 = new AmazonTC('os', (int) $login_fabrica);

$os = $_REQUEST["os"];

if ($_POST["anexar_nf"]) {
	if (is_array($_FILES['arquivo_nf']) && $_FILES['arquivo_nf']['name'] != '') {
		$file = $_FILES["arquivo_nf"];

		$sql = "SELECT 
					DATE_PART('MONTH', data_abertura) AS mes_os,
					DATE_PART('YEAR', data_abertura) AS ano_os
				FROM tbl_os
				WHERE fabrica = {$login_fabrica}
				AND os = {$os}";
		$res = pg_query($con, $sql);

		$mes_os = pg_fetch_result($res, 0, "mes_os");
		$ano_os = pg_fetch_result($res, 0, "ano_os");

		$s3->upload("{$os}", $file, $ano_os, $mes_os);
  		
  		$sql = "INSERT INTO tbl_os_status (os, status_os, observacao) VALUES ($os, 189, 'OS em auditoria de nota fiscal')";
  		$res = pg_query($con, $sql);

  		$msg_ok = true;
	} else {
		$msg_erro = "SELECIONE UM ARQUIVO";
	}
}

?>

<html>
	<body style="background-color: #FFFFFF;" >
		<?php
		if (!isset($msg_ok)) {
		?>
			<br />
			<form method="POST" enctype="multipart/form-data" >
				SELECIONE O ARQUIVO DA NOTA FISCAL <br />
				<br />
				<input type="hidden" name="os" value="<?=$os?>" />
				<input type="file" name="arquivo_nf" />
				<input type="submit" name="anexar_nf" value="Anexar Nota Fiscal" />
			</form>

			<?php
			if (isset($msg_erro)) {
				echo "<div style='width: 100%; background-color: #EE3333; color: #FFFFFF; text-align: center;' >
					{$msg_erro}
				</div>";
			}
		} else {
		?>
			<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
			<script>
				$(function () {
					$(window.parent.document).find("button[name=anexar_nf][rel=<?=$os?>]").parents("tr").css({ "background-color": "#FFFFFF" });
					$(window.parent.document).find("button[name=anexar_nf][rel=<?=$os?>]").remove();

					setTimeout(function () {	
						window.parent.Shadowbox.close();
					}, 3000);
				});
			</script>

			<div style='width: 100%; background-color: #007F00; color: #FFFFFF; text-align: center;' >
				NOTA FISCAL ANEXADA
			</div>
		<?php
		}
		?>
		<br />
	</body>
</html>