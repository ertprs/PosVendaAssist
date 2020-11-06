<?php

$fabrica = 1;

include __DIR__ . '/../../dbconfig.php';
include __DIR__ . '/../../includes/dbconnect-inc.php';

$basedir = __DIR__;

if(!empty($argv[1])) {
	$produtos = $argv[1];
	$cond = " AND produto in ({$produtos}) ";
}

$sql = "SELECT DISTINCT tbl_familia.familia
			FROM tbl_comunicado
			JOIN tbl_produto USING(produto)
			JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
			WHERE tbl_comunicado.fabrica = $fabrica
			AND tbl_comunicado.extensao IS NOT NULL
			AND tbl_comunicado.ativo = 't'
			AND tbl_comunicado.tipo = 'Vista Explodida'
			$cond

		UNION
		
		SELECT DISTINCT tbl_familia.familia
			FROM tbl_comunicado
			JOIN tbl_comunicado_produto USING(comunicado)
			JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
			JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
			WHERE tbl_comunicado.fabrica = $fabrica
			AND tbl_comunicado.extensao IS NOT NULL
			AND tbl_comunicado.ativo = 't'
			AND tbl_comunicado.tipo = 'Vista Explodida';";
$res = pg_query($con,$sql);
$i = 0;

while ($familia = pg_fetch_assoc($res)) {
	$f = $familia['familia'];

	system("php {$basedir}/gera_zip_vista_explodida-familia.php $f");
}

