<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';
include_once 'class/aws/s3_config.php';

if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3 = new anexaS3('os', (int) $login_fabrica);
}

if ($_GET["itens"]) {
	$itens      = $_GET["itens"];

	foreach ($itens as $item) {
		// Ambiente de teste

		/*$files = glob("../osImagem/uploaded/{$item['os']}-{$item['referencia']}-{$item['os_item']}-[0-4].*", GLOB_BRACE);

		if (count($files) > 0) {
			foreach ($files as $file) {
				$thumb = createThumb($file);
				echo "<a href='{$file}' style='margin-right: 10px;' ><img src='{$thumb}' style='cursor: pointer;' /></a>";
			}
		}*/

		if (is_object($s3)) {
			$os         = $item["os"];
			$referencia = $item["referencia"];
			$os_item    = $item["os_item"];
			$qtde_fotos = $item["qtde_fotos"];

			echo $referencia.":";

			for ($k = 0; $k < $qtde_fotos; $k++) { 
				$s3->temAnexos("{$os}-{$referencia}-{$os_item}-{$k}.*", "pcre");

				if ($s3->temAnexo) {
					$url   = $s3->url;
					$thumb = createThumb($url);
					//$thumb = $url;

					echo "<a href='{$url}' style='margin-right: 10px;' ><img src='{$thumb}' style='cursor: pointer;' /></a>";
				}
			}

			echo "<br />";
		}
	}
}

exit;

?>