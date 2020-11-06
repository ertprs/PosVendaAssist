<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$sql = "SELECT DISTINCT estado,cidade, nome, endereco, bairro, numero, complemento, fone, cnpj, email, contato
			FROM tbl_posto 
			JOIN tbl_posto_linha USING(posto) 
			JOIN tbl_posto_extra USING(posto)
		WHERE tbl_posto_linha.linha IN(385,372,374,335,4) 
		AND tbl_posto.posto in (select posto from tbl_posto_fabrica where fabrica = 25)";


/*
$sql = "SELECT DISTINCT cnpj, nome, fantasia, endereco, numero, complemento, cidade, estado, email, fone , contato
			FROM tbl_posto
			JOIN tbl_posto_fabrica using(posto)
		WHERE tbl_posto_fabrica.fabrica = '25' 
		AND credenciamento = 'CREDENCIADO' ;";


$sql = "SELECT DISTINCT cnpj, nome, fantasia, endereco, numero, complemento, cidade, estado, email, fone , contato
			FROM tbl_posto_extra 
			JOIN tbl_posto using(posto) 
			JOIN tbl_posto_linha using(posto)
		WHERE (tbl_posto_extra.fabricantes IS NULL AND tbl_posto_extra.descricao IS NULL)
		AND tbl_posto_linha.linha in (385,372,374,335,4) 
		AND tbl_posto.posto not in (select posto from tbl_posto_fabrica where fabrica = 25)";

$sql = "SELECT DISTINCT cnpj, nome, fantasia, endereco, numero, complemento, cidade, estado, email, fone , contato
			FROM tbl_posto_extra 
			JOIN tbl_posto using(posto) 
			JOIN tbl_posto_linha using(posto)
			WHERE tbl_posto_linha.linha in(335,374)
			AND tbl_posto.cidade ilike '%Porto Alegre%';";
*/
$res = pg_exec($con,$sql);

echo "<table border='0'>";

for($i = 0; $i < pg_numrows($res); $i++){

	$email_posto  = pg_result($res, $i, email);
	$cnpj         = pg_result($res, $i, cnpj);
	$nome_posto   = pg_result($res, $i, nome);
	$endereco     = pg_result($res, $i, endereco);
	$bairro       = pg_result($res, $i, bairro);
	$numero       = pg_result($res, $i, numero);
	$complemento  = pg_result($res, $i, complemento);
	$cidade       = pg_result($res, $i, cidade);
	$estado       = pg_result($res, $i, estado);
	$telefone     = pg_result($res, $i, fone);
	$contato      = pg_result($res, $i, contato);

	
	echo "<tr>";
	echo "<td nowrap>"; 
		if(strlen($estado) > 0) echo $estado; else echo "&nbsp;";
	echo "</td>";
	echo "<td nowrap>"; 
		if(strlen($cidade) > 0) echo $cidade; else echo "&nbsp;";
	echo "</td>";
	echo "<td nowrap>"; 
		if(strlen($nome_posto) > 0) echo $nome_posto; else echo "&nbsp;";
	echo "</td>";
	echo "<td nowrap>"; 
		if(strlen($endereco) > 0) echo $endereco; else echo "&nbsp;";
	echo "</td>";
	echo "<td nowrap>"; 
		if(strlen($numero) > 0) echo $bairro; else echo "&nbsp;";
	echo "</td>";
	echo "<td nowrap>"; 
		if(strlen($numero) > 0) echo $numero; else echo "&nbsp;";
	echo "</td>";
	echo "<td nowrap>"; 
		if(strlen($complemento) > 0) echo $complemento; else echo "&nbsp;";
	echo "</td>";
	echo "<td nowrap>";
		if(strlen($telefone) > 0) echo $telefone; else echo "&nbsp;";
	echo "</td>";
	echo "<td nowrap>" ;
		if(strlen($cnpj) > 0) echo $cnpj; else echo "&nbsp;";
	echo "</td>";
	echo "<td nowrap>";
		if(strlen($email_posto) > 0) echo $email_posto; else echo "&nbsp;";
	echo "</td>";
	echo "<td nowrap>";
		if(strlen($contato) > 0) echo $contato; else echo "&nbsp;";
	echo "</td>";
	echo "</tr>";
}

echo "</table>";

?>
