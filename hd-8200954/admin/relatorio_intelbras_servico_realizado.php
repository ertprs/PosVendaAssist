<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


$sql = "SELECT tbl_os.sua_os          ,
	tbl_os_extra.extrato  ,
	tbl_peca.descricao    ,
	tbl_servico_realizado.descricao AS servico_realizado_descricao 
FROM tbl_os_item 
JOIN tbl_os_produto USING(os_produto) 
JOIN tbl_peca USING(peca) 
JOIN tbl_os_extra using(os)
JOIN tbl_os using(os)
JOIN tbl_extrato using(extrato)
JOIN tbl_servico_realizado USING(servico_realizado) 
WHERE servico_realizado = 83 
AND tbl_peca.descricao NOT ILIKE '%placa%' 
AND tbl_extrato.data_geracao between '2007-03-26 00:00:00' AND '2007-03-26 23:59:59'
AND tbl_extrato.fabrica = 14
ORDER BY tbl_os_extra.extrato, tbl_os_extra.os; ";

//$res = pg_exec($con, $sql);
?>

<table style='font-size: 10px; font-family: verdana'>

<?
for($i=0;$i<pg_numrows($res);$i++){
	$sua_os            = pg_result($res,$i,sua_os);
	$extrato           = pg_result($res,$i,extrato);
	$peca_descricao    = pg_result($res,$i,descricao);
	$servico_realizado = pg_result($res,$i,servico_realizado_descricao);
?>

	<tr>
	<td><? echo $sua_os ?></td>
	<td><? echo $extrato ?></td>
	<td><? echo $peca_descricao ?></td>
	<td><? echo $servico_realizado ?></td>
	</tr>
<?}?>
</table>