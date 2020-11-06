<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';


$btn_acao = $HTTP_POST_VARS['btn_acao'];

if (strtoupper ($btn_acao) == 'GRAVAR') {

	$transportadora_padrao = strtoupper ($HTTP_POST_VARS['transportadora_padrao']);
	$estado                = strtoupper ($HTTP_POST_VARS['estado']);
	$capital_interior      = $HTTP_POST_VARS['capital_interior'];
	$valor_frete           = $HTTP_POST_VARS['valor_frete'];
	$transportadora        = $HTTP_POST_VARS['transportadora'];

	$valor_frete = str_replace (",",".",$valor_frete);

	if (strlen ($transportadora_padrao) == 0) {
		$sql = "INSERT INTO tbl_transportadora_padrao (fabrica, estado, capital_interior, valor_frete, transportadora) VALUES ($login_fabrica, '$estado', '$capital_interior',$valor_frete, $transportadora)";
		$res = @pg_exec ($con,$sql);
	}else{
		$sql = "UPDATE tbl_transportadora_padrao SET estado = '$estado', capital_interior = '$capital_interior' , valor_frete = $valor_frete, transportadora = $transportadora WHERE tbl_transportadora_padrao.transportadora_padrao = $transportadora_padrao AND fabrica = $login_fabrica";
		$res = @pg_exec ($con,$sql);
	}

	if (strlen (pg_errormessage ($con)) == 0) {
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$erro = pg_errormessage ($con);
	}
}


#--------------------- Le base de dados --------------
$transportadora_padrao = $HTTP_GET_VARS['transportadora_padrao'];
if (strlen ($transportadora_padrao) > 0) {
	$sql = "SELECT * FROM tbl_transportadora_padrao WHERE transportadora_padrao = $transportadora_padrao";
	$res = pg_exec ($con,$sql);
	$estado           = trim (pg_result ($res,0,estado));
	$capital_interior = trim (pg_result ($res,0,capital_interior));
	$valor_frete      = pg_result ($res,0,valor_frete);
	$transportadora   = pg_result ($res,0,transportadora);
}


?>

<html>
<head>
<title>Telecontrol - Transportadora Padrão</title>

</head>

<body bgcolor="#FFFFFF" text="#000000" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" link="#333333">


<? if (strlen ($erro) > 0) { ?>
<table width='400' align='center' border='0' bgcolor='#FF0000'>
<tr>
	<td>
		<font face='arial' size='+1' color='#ffffff'><? echo $erro ?></font>
	</td>
</tr>
</table>
<? } ?>



<form name="frm_transp" method="post" action="<? $PHP_SELF ?>">
<input type='hidden' name='transportadora_padrao' value='<? echo $transportadora_padrao ?>'>


<table width='450' align='center'>
<tr>
	<td align='center' bgcolor='#FFCC99'>
		<font face='arial' size='+1'><b>Transportadora Padrão</b></font>
	</td>
</tr>
</table>


<table width='450' align='center'>
<tr>
	<td>
		<font face='arial' size='+0'><b>Estado</b></font>
	</td>

	<td>
		<input type='text' name='estado' size='2' maxlength='2' value='<? echo $estado ?>'>
	</td>
<tr>
<tr>
	<td>
		<font face='arial' size='+0'><b>Capital/Interior</b></font>
	</td>

	<td>
		<select size='1' name='capital_interior'>
		<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo " SELECTED " ?>
>Capital</option>
		<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo " SELECTED " ?>>Interior</option>
		</select>
	</td>
<tr>
<tr>
	<td>
		<font face='arial' size='+0'><b>Transportadora</b></font>
	</td>

	<td>
		<select size='1' name='transportadora'>
		<?
		$sql = "SELECT tbl_transportadora.* FROM tbl_transportadora JOIN tbl_transportadora_fabrica USING (transportadora) WHERE tbl_transportadora_fabrica.fabrica = $login_fabrica ORDER BY tbl_transportadora.nome";
		$res = pg_exec ($con,$sql);
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			echo "<option value='" . pg_result ($res,$i,transportadora) . "' ";
			if ($transportadora == pg_result ($res,$i,transportadora) ) echo " SELECTED ";
			echo ">";
			echo pg_result ($res,$i,nome);
			echo "</option>";
		}
		?>
		</select>
	</td>
<tr>
<tr>
	<td>
		<font face='arial' size='+0'><b>Valor Frete</b></font>
	</td>

	<td>
		<input type='text' name='valor_frete' size='10' maxlength='10' value='<? echo number_format ($valor_frete,2,",",",") ?>'>
	</td>
<tr>
</table>

<center>

<input type='submit' name='btn_acao' value='Gravar'>

</form>


<?
$sql = "SELECT tbl_transportadora_padrao.*, tbl_transportadora.nome, tbl_transportadora_fabrica.codigo_interno FROM tbl_transportadora_padrao JOIN tbl_transportadora USING (transportadora) JOIN tbl_transportadora_fabrica USING (transportadora) WHERE tbl_transportadora_padrao.fabrica = $login_fabrica ORDER BY tbl_transportadora_padrao.estado, tbl_transportadora_padrao.capital_interior";

$res = pg_exec ($con,$sql);

echo "<table width='300' align='center' border='0'>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<tr>";
	echo "<td>" . pg_result ($res,$i,estado) . "</td>";
	echo "<td>" . pg_result ($res,$i,capital_interior) . "</td>";
	echo "<td><a href='$PHP_SELF?transportadora_padrao=" . pg_result ($res,$i,transportadora_padrao) . "'>" . pg_result ($res,$i,nome) . "</a></td>";
	echo "<td align='right'>" . number_format (pg_result ($res,$i,valor_frete),2,",",".") . "</td>";
	echo "</tr>";
}
echo "</table>";
?>


</body>
</html>