<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	##FABRICA: 105 - DWT ##

	$login_fabrica = 105;

	$sql = "SELECT  tbl_produto.referencia,
		       	tbl_produto.descricao,
			tbl_linha.codigo_linha,
			CASE WHEN tbl_produto.ativo THEN
				'A'
			ELSE
				'I'
			END AS ativo
		FROM    tbl_produto
		JOIN    tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = tbl_linha.fabrica
		WHERE   tbl_linha.fabrica = $login_fabrica
		ORDER BY tbl_produto.referencia";
	$res = pg_query($con,$sql);

	if(pg_numrows($res) > 0){

		$arquivo = "dwt-ret-produtos.ret";
		$fp = fopen ("$arquivo","w");

		for($i = 0; $i < pg_numrows($res); $i++){
			$referencia   = trim(pg_fetch_result($res,$i,'referencia'));
			$descricao    = trim(pg_fetch_result($res,$i,'descricao'));
			$codigo_linha = trim(pg_fetch_result($res,$i,'codigo_linha'));
			$ativo        = trim(pg_fetch_result($res,$i,'ativo'));

			fputs($fp,"$referencia;");

			fputs($fp,"$descricao;");

			fputs($fp,"$codigo_linha;");

			fputs($fp,"$ativo\n");

		}

		fclose ($fp);

		if(file_exists($arquivo)) {
			system("mv $arquivo /home/vonder/telecontrol-dwt/");
		}

	}
} catch (Exception $e) {

	echo $e->getMessage();

}?>

