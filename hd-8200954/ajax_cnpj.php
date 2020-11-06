<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$cnpj = $_GET ['cnpj'];
$cnpj = str_replace (".","",$cnpj);
$cnpj = str_replace ("-","",$cnpj);
$cnpj = str_replace (" ","",$cnpj);

if (strlen ($cnpj) == 14) {
	$sql = "SELECT 	tbl_revenda.nome,
					tbl_revenda.endereco,
					tbl_revenda.numero,
					tbl_revenda.complemento,
					tbl_revenda.bairro,
					tbl_revenda.cep,
					tbl_cidade.nome as cidade,
					tbl_cidade.estado,
					tbl_revenda.cnpj, 
					tbl_revenda.fone,
					tbl_revenda.email
			FROM tbl_revenda
			JOIN tbl_cidade using(cidade)
			WHERE cnpj = '$cnpj'";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$nome        = trim (pg_result ($res,0,nome));
		$endereco    = trim (pg_result ($res,0,endereco));
		$numero      = trim (pg_result ($res,0,numero));
		$complemento = trim (pg_result ($res,0,complemento));
		$bairro      = trim (pg_result ($res,0,bairro));
		$cep         = trim (pg_result ($res,0,cep));
		$cidade      = trim (pg_result ($res,0,cidade));
		$estado      = trim (pg_result ($res,0,estado));
		$cnpj        = trim (pg_result ($res,0,cnpj));
		$fone        = trim (pg_result ($res,0,fone));
		$email       = trim (pg_result ($res,0,email));
		//nome,fone,cep,endereco,numero,complemento,bairro,cidade,estado
		echo $nome . ";" . $fone . ";" . $cep . ";" . $endereco . ";" . $numero . ";" . $complemento . ";" . $bairro . ";" . $cidade . ";" . $estado . ";" .$email;
	}
}

?>
