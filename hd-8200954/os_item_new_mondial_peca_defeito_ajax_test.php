<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

/*HD 16027 Produto acabado, existia algumas selects sem a validação*/

#include 'cabecalho_pop_pecas.php';
#header("Expires: 0");
#header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
#header("Pragma: no-cache, public");

$defeito            = $_GET['defeito'];
$peca_selecionada   = $_GET['peca_selecionada'];
$produto_referencia = $_GET['produto'];
$peca_pai           = $_GET['peca_pai'];


if (strlen($peca_pai)==0) {
	$peca_pai = 'null';
}
if(strlen($defeito)>0){

	$cond_1 = " 1 = 1 ";
	if(strlen($produto_referencia)>0){
		$sql = "select produto from tbl_produto where referencia = '$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_produto.produto = $produto";
		}
	}else{
		exit;
	}

	$sql = "SELECT tbl_peca.peca,
					tbl_peca.descricao,
					tbl_peca.referencia
					FROM tbl_peca
					JOIN tbl_lista_basica ON tbl_peca.peca       = tbl_lista_basica.peca    AND tbl_lista_basica.fabrica = $login_fabrica
					JOIN tbl_produto      ON tbl_produto.produto = tbl_lista_basica.produto 
					JOIN tbl_peca_defeito ON tbl_peca.peca       = tbl_peca_defeito.peca 
					JOIN tbl_defeito      ON tbl_defeito.defeito = tbl_peca_defeito.defeito  AND tbl_defeito.fabrica= $login_fabrica
					WHERE tbl_peca.fabrica = $login_fabrica
						AND ( tbl_lista_basica.peca_pai is null or tbl_lista_basica.peca_pai = $peca_pai)
						AND tbl_peca_defeito.defeito = $defeito
						AND tbl_produto.produto = $produto
						AND tbl_peca.peca_pai is not true
						AND tbl_peca_defeito.ativo IS TRUE
						AND tbl_peca.ativo IS TRUE
						AND tbl_produto.ativo IS TRUE
					ORDER BY descricao ";
		
#	echo nl2br($sql);
#echo "<br><br>";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res) > 0){
		//echo "<option value=''></option>";
		for($i=0; $i<pg_numrows($res); $i++){
			$peca = pg_result($res,$i,peca);
			$descricao         = pg_result($res,$i,descricao);
			$referencia= pg_result($res,$i,referencia);
			$selected=" ";
			if($referencia == $peca_selecionada){
				$selected=" selected ";
			}
			echo "<option value='$referencia' $selected>$referencia - ".$descricao." </option>";
		}
	}else{
		echo "<option value=''>Não encontrado.</option>";
	}
}else{
	echo "<option value=''>";
//	print_r($_GET);
	echo "Não encontrado.</option>";
}
exit;
?>