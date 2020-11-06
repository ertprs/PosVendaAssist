<?php

if (empty($argv[1])) {
	echo "Uso: php " . basename(__FILE__) . " ID_FAMILIA\n";
	die(1);
}

$test_argv = preg_match("(\D)", $argv[1]);

if ($test_argv) {
	echo "Erro: ID_FAMILIA deve ser um inteiro positivo\n";
	die(1);
}

include __DIR__ . '/../../dbconfig.php';
include __DIR__ . '/../../includes/dbconnect-inc.php';
include __DIR__ . '/../../class/aws/s3_config.php';
include __DIR__ . '/../../class/aws/anexaS3.class.php';

$fabrica = 1;
$familia = $argv[1];
$basedir = __DIR__;
$zip_vista_dir = $basedir . '/./zip_vista';

$s3ve  = new anexaS3('ve', $fabrica);

$sql = "SELECT DISTINCT tbl_familia.familia, comunicado, tbl_produto.referencia, extensao
		FROM tbl_comunicado
		JOIN tbl_produto USING(produto)
		JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
		WHERE tbl_comunicado.fabrica = $fabrica
		AND tbl_familia.familia = $familia
		AND tbl_comunicado.extensao IS NOT NULL
		AND tbl_comunicado.ativo = 't'
		AND tbl_comunicado.tipo = 'Vista Explodida'
		union
		SELECT DISTINCT tbl_familia.familia, comunicado, tbl_produto.referencia,  extensao
		FROM tbl_comunicado
		JOIN tbl_comunicado_produto USING(comunicado)
		JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
		JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
		WHERE tbl_comunicado.fabrica = $fabrica
		AND tbl_familia.familia = $familia
		AND tbl_comunicado.extensao IS NOT NULL
		AND tbl_comunicado.ativo = 't'
		AND tbl_comunicado.tipo = 'Vista Explodida' ";
$query = pg_query($con,$sql);

if (pg_num_rows($query) > 0) {
	system("rm -f {$zip_vista_dir}/* 2>/dev/null", $ret_rm);

	if ($ret_rm <> 0) {
		echo "Erro: diretório zip_vista não estava vazio\n";
		die(1);
	}
}

$tmp_zip_dir = '/tmp/' . substr(sha1(getmypid() . date('c') . rand()), 0, 7);

system("mkdir -p $tmp_zip_dir");

while ($comunicado = pg_fetch_assoc($query)) {
	if ($s3ve->temAnexos($comunicado['comunicado']) and !empty($s3ve->url)) {
		$fn = str_replace(' ', '_', $comunicado['referencia']) . '.' . $comunicado['extensao'];

		file_put_contents(
			"{$zip_vista_dir}/{$fn}", 
			file_get_contents($s3ve->url)
		);
	}
}

if (count(glob("{$zip_vista_dir}/*"))) {
	$zipfile = $tmp_zip_dir . '/ve_001_' . $familia . '.zip';
	$zipdestfile = $basedir . '/zip_vista_test/ve_001_' . $familia . '.zip';
	system("cd $basedir && zip -r $zipfile zip_vista 1>/dev/null");
}

if (file_exists($zipfile) and filesize($zipfile) > 0 ) {
	$s3ve->set_tipo_anexoS3("ve_familia");
	$s3ve->uploadFileS3($familia, $zipfile);
	$s3ve->set_tipo_anexoS3('ve');

	unlink($zipfile);
}

rmdir($tmp_zip_dir);

