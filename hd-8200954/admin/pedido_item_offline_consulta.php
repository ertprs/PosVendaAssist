<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
#include 'funcoes.php';

?>

<html>
<head>
<title>Telecontrol - Itens do Pedido OFFLINE</title>
</head>

<body bgcolor="#EEEEEE" text="#000000" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" link="#333333">

<hr>

<center>
<b><font face="Geneva, Arial, Helvetica, san-serif">:: Itens do Pedido OFFLINE ::</font></b>
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
	<td bgcolor="#FFCC00" align="center"><font size="2" face="Geneva, Arial" color="#000000">Referência</font></td>
	<td bgcolor="#FFCC00" align="center"><font size="2" face="Geneva, Arial" color="#000000">Descrição</font></td>
	<td bgcolor="#FFCC00" align="center"><font size="2" face="Geneva, Arial" color="#000000">Qtde</font></td>
	<td bgcolor="#FFCC00" align="center"><font size="2" face="Geneva, Arial" color="#000000">Preço</font></td>
</tr>

<?
$pedido = $HTTP_GET_VARS['pedido'];
$posto  = $HTTP_GET_VARS['posto'];

$sql = "SELECT off_pedido_item.* , tbl_peca.descricao FROM off_pedido_item JOIN off_pedido ON off_pedido_item.pedido = off_pedido.pedido AND off_pedido_item.posto = off_pedido.posto JOIN tbl_peca ON off_pedido_item.peca = tbl_peca.referencia AND off_pedido.fabrica = tbl_peca.fabrica WHERE off_pedido_item.pedido = $pedido AND off_pedido_item.posto = $posto ";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<tr>";
	echo "<td>" . pg_result ($res,$i,peca) . "</td>";
	echo "<td>" . pg_result ($res,$i,descricao) . "</td>";
	echo "<td>" . pg_result ($res,$i,qtde) . "</td>";
	echo "<td>" . "</td>";
	echo "</tr>";
}

echo "</table>";

?>



<p>



<br>

</body>
</html>