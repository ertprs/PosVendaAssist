<?
$dbhost    = "postgres.olivier.com.br";
$dbbanco   = "postgres";
$dbport    = 5432;
$dbusuario = "olivier";
$dbsenha   = "p0stg43s";
$dbnome    = "olivier";

include 'includes/dbconnect-inc.php';
?>

<p>

<table>

<?

$sql = "select tbl_produto.referencia, tbl_montagem_item.qtde
from tbl_montagem 
join tbl_montagem_item on tbl_montagem.montagem = tbl_montagem_item.montagem 
join tbl_produto on tbl_montagem_item.produto = tbl_produto.produto 
where tbl_montagem_item.montagem = 17416 order by tbl_produto.referencia asc ;";
$res = pg_exec($con,$sql);

#echo "<br>".nl2br($sql)."<br><br>";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
	$referencia  = trim(pg_result ($res,$i,referencia));
	$qtde  = trim(pg_result ($res,$i,qtde));
	for ($x=0; $x<$qtde; $x++){
		echo "<TR>\n";
		echo "	<TD>$referencia</TD>\n";
		echo "</TR>\n";
	}
}

?>
</table>
