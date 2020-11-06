<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';


?>

<html>
<head>
<title>Telecontrol - Consulta Pedidos OFFLINE</title>
</head>

<body bgcolor="#EEEEEE" text="#000000" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" link="#333333">

<hr>

<center>
<b><font face="Geneva, Arial, Helvetica, san-serif">:: Consulta Pedidos OFFLINE ::</font></b>
</center>

<?
if (strlen ($msg_erro) > 0) {
	echo "<p>";
	echo "<center>";
	echo "<b><font face='arial' size='+1' color='#CC3333'>$msg_erro</font></b>";
	echo "</center>";
}
?>

<p>




<!-- ----------------- RELATORIO --------------------- -->

<table width="400" border="0" cellspacing="3" cellpadding="0" align="center">
<tr height='30'>
	<td bgcolor="#FFCC00" align="center"><font size="2" face="Geneva, Arial" color="#000000">Pedido</font></td>
	<td bgcolor="#FFCC00" align="center"><font size="2" face="Geneva, Arial" color="#000000">Posto</font></td>
	<td bgcolor="#FFCC00" align="center"><font size="2" face="Geneva, Arial" color="#000000">Condição</font></td>
	<td bgcolor="#FFCC00" align="center"><font size="2" face="Geneva, Arial" color="#000000">Total</font></td>
</tr>

<?
$sql = "SELECT off_pedido.* , tbl_posto.nome, (SELECT sum (qtde) FROM off_pedido_item WHERE off_pedido.pedido = off_pedido_item.pedido AND off_pedido.posto = off_pedido_item.posto) AS total FROM off_pedido JOIN tbl_posto_fabrica ON off_pedido.posto = tbl_posto_fabrica.codigo_posto AND off_pedido.fabrica = tbl_posto_fabrica.fabrica JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto WHERE off_pedido.fabrica = $login_fabrica ";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<tr>";
	echo "<td><a href='pedido_item_offline_consulta.php?posto=" . pg_result ($res,$i,posto) . "&pedido=" . pg_result ($res,$i,pedido) . "' target='_new'>" . pg_result ($res,$i,pedido) . "</a></td>";
	echo "<td>" . pg_result ($res,$i,posto) . " - " . pg_result ($res,$i,nome) . "</td>";
	echo "<td>" . pg_result ($res,$i,condicao) . "</td>";
	echo "<td align='right'>" . pg_result ($res,$i,total) . "</td>";
	echo "</tr>";
}

echo "</table>";

?>



<p>



<br>

</body>
</html>