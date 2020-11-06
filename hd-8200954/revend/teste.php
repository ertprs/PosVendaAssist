
<?
require '../dbconfig.php';
require '../includes/dbconnect-inc.php';

$sql = "select produto from tbl_revenda_produto group by produto having count(produto)>1";
$res = @pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
	$produto = pg_result($res,$i,produto);
	$sql2 = "delete from tbl_revenda_produto where revenda_produto in (select revenda_produto from tbl_revenda_produto where revenda_produto <> (select min(revenda_produto) from tbl_revenda_produto where produto=$produto ) and produto IN(select produto from tbl_revenda_produto where produto=$produto))";
//$res2 = @pg_exec ($con,$sql2);
}
