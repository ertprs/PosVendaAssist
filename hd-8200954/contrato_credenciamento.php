<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$codigo_posto = $_GET['codigo_posto'];
$fabrica      = $_GET['fabrica'];


$sql = "SELECT DISTINCT tbl_posto_fabrica.codigo_posto, tbl_posto.estado,tbl_posto.cidade, tbl_posto.nome, tbl_posto.endereco, tbl_posto.bairro, tbl_posto.numero, tbl_posto.complemento, tbl_posto.fone, tbl_posto.cnpj, tbl_posto.email, tbl_posto.contato, tbl_posto.cep
	FROM tbl_posto_fabrica 
	JOIN tbl_posto using(posto)
	WHERE fabrica = 25
	AND codigo_posto = '$codigo_posto'";

$res = pg_exec($con,$sql);

for($i = 0; $i < pg_numrows($res); $i++){

	$cnpj         = pg_result($res, $i, cnpj);
	$cep          = pg_result($res, $i, cep);
	$nome_posto   = pg_result($res, $i, nome);
	$endereco     = pg_result($res, $i, endereco);
	$bairro       = pg_result($res, $i, bairro);
	$numero       = pg_result($res, $i, numero);
	$complemento  = pg_result($res, $i, complemento);
	$cidade       = pg_result($res, $i, cidade);
	$estado       = pg_result($res, $i, estado);
	$codigo_posto = pg_result($res, $i, codigo_posto);

echo "$codigo_posto - $nome_posto";

}
?>