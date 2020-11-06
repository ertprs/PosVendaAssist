<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include 'autentica_admin.php';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
</style>

<?
$sql = "
	SELECT tbl_posto.cnpj,tbl_posto.posto, tbl_posto.nome, count(os) AS qtde
	FROM tbl_os 
	JOIN tbl_posto USING(posto)
	JOIN tbl_fabrica ON tbl_os.fabrica = tbl_fabrica.fabrica 
	WHERE produto in(SELECT produto FROM tbl_produto WHERE linha IN(259,491,448,212,252,243,41,43,251,249,203,404,334,32,10,3,472))
	AND data_digitacao > '2008-03-01'
	AND tbl_os.fabrica <> 0
	GROUP BY tbl_posto.cnpj, tbl_posto.posto, tbl_posto.nome;
";
$res = pg_exec($con,$sql);
echo "<table border='1'>";
echo "<tr>";
	echo "<td>CNPJ</td>";
	echo "<td>RAZÂO SOCIAL</td>";
	echo "<td>QTDE OS</td>";
echo "</tr>";
for($i=0;$i<pg_numrows($res);$i++){
	$posto      = pg_result($res,$i,posto);
	$cnpj       = pg_result($res,$i,cnpj);
	$posto_nome = pg_result($res,$i,nome);
	$posto_qtde = pg_result($res,$i,qtde);
	echo "</tr>";
		echo "<td>$cnpj</td>";
		echo "<td>$posto_nome</td>";
		echo "<td>$posto_qtde</td>";
		$sql2 = "select distinct tbl_fabrica.nome from tbl_posto_linha join tbl_linha using(linha) join tbl_fabrica using(fabrica) where linha in(448,203,334,3,472) and posto = $posto;";
		$res2 = pg_exec($con,$sql2);
		for($x=0;$x<pg_numrows($res2);$x++){
			$fabrica_nome = pg_result($res2,$x,nome);
			echo "<td>$fabrica_nome</td>";
		}
	echo "</tr>";
}
echo "</table>";


?>