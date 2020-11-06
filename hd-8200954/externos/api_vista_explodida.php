<?php
/*
RETORNA LINK VISTA EXPLODIDA
ISSO AQUI É PALETIVO

TOKEN = FABRICA*REFERENCIAPRODUTO - BASE64 
*/
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include_once '../class/aws/s3_config.php';

include_once '../helpdesk/mlg_funciones.php';
include_once '../funcoes.php';
include_once('../class/tdocs.class.php');

if (isset($_GET['token']) && strlen($_GET['token']) > 0) {

	$token = base64_decode($_GET['token']);
	list($fabrica, $referencia) = explode("*", $token);

	$sql = "SELECT tbl_comunicado.* 
		  FROM tbl_comunicado
		  JOIN tbl_produto ON tbl_comunicado.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$fabrica}
		 WHERE trim(tbl_produto.referencia) = trim('$referencia')
		   AND tbl_comunicado.fabrica = $fabrica";
	$res = pg_query ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$comunicado = trim(pg_fetch_result($res,0, 'comunicado'));
		$extensao   = strtolower(trim(pg_result($res,0,'extensao')));
	}

	if ($S3_sdk_OK) {
		include_once S3CLASS;
		$s3 = new anexaS3('ve', (int) $fabrica);
		$S3_online = is_object($s3);
	}


	if (strlen($comunicado) > 0 AND strlen($extensao) > 0) {

		$linkVE = "https://posvenda.telecontrol.com.br/assist/comunicados/$comunicado." . $extensao;

		if ($S3_online /*and !file_exists($linkVE)*/) {
			if (!$s3->temAnexos($comunicado)):
				$linkVE = (file_exists($linkVE)) ? $linkVE:'#'; //Deshabilita o link se não existe local
			else:
				$linkVE = $s3->url;
			endif;
		}
	}

	exit(json_encode(['link' => $linkVE]));
} else {

	exit(json_encode(['erro' => true, 'message' => 'Token inválido']));
}
