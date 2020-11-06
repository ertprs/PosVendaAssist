<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';


?>

<html>
<head>
<title>Telecontrol - Postos para Acerto de Contas</title>
</head>


<body bgcolor="#EEEEEE" text="#000000" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" link="#333333">

<hr>

<form name="frm_encontro" method="post" action="<? $PHP_SELF ?>">

<center>
<b><font face="Geneva, Arial, Helvetica, san-serif">:: Postos para Acerto de Contas ::</font></b>
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

<table width="300" border="0" cellspacing="5" cellpadding="2" bgcolor="#FFCCCC" align="center">
<tr>
	<td valign="middle"><div id="font_form">Data Vencto. Final</div><input type="text" name="data_final" size="12" value="<? echo $data_final ?>"></td>

	<td valign="middle">&nbsp;<br>
		<? $tipo_relatorio = $HTTP_POST_VARS['tipo_relatorio'] ?>
		<select name='tipo_relatorio' size='1'>
		<option value='50-maiores-devedores' <? if ($tipo_relatorio == '50-maiores-devedores') echo " selected " ?> >50 maiores devedores</option>
		<option value='50-maiores-credores' <? if ($tipo_relatorio == '50-maiores-credores') echo " selected " ?> >50 maiores credores</option>
		</select></td>
</tr>

<tr>
	<td valign="middle" align="center" colspan="2"><hr><input type="submit" name="btn_acao" value="Pesquisar"><hr></td>
</tr>
</table>

</form>







<!-- ----------------- RELATORIO  EM ABERTO --------------------- -->

<?
$tipo_relatorio = $HTTP_POST_VARS['tipo_relatorio'];

if (strlen ($tipo_relatorio) > 0 ) { 

	if (strlen($HTTP_POST_VARS["data_final"]) > 0) {
		$data_final = trim($HTTP_POST_VARS["data_final"]);
		$data_final = str_replace ("-","",$data_final);
		$data_final = str_replace ("/","",$data_final);
		$data_final = str_replace (" ","",$data_final);
		$data_final = str_replace (".","",$data_final);
		$xdata_final = substr($data_final,4,4) ."-". substr($data_final,2,2) ."-". substr($data_final,0,2);
	}

	echo "<br>\n";

	$sql = "CREATE TEMP TABLE tmp_posto_saldo (posto int4, codigo_posto varchar (10) , nome varchar (50) , saldo float)";
	$res = pg_exec ($con,$sql);

	$sql = "INSERT INTO tmp_posto_saldo (posto, codigo_posto, nome) (SELECT tbl_posto.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome FROM tbl_posto_fabrica JOIN tbl_posto USING (posto) WHERE tbl_posto_fabrica.fabrica = $login_fabrica)";
	$res = pg_exec ($con,$sql);

	$sql = "UPDATE tmp_posto_saldo SET saldo = (SELECT SUM (valor_saldo) FROM tbl_conta_corrente WHERE tbl_conta_corrente.posto = tmp_posto_saldo.posto AND tbl_conta_corrente.fabrica = $login_fabrica AND tbl_conta_corrente.data_vencimento <= '$xdata_final' AND trim(tbl_conta_corrente.tipo) IN ('AT','AU','AL') AND (trim(tbl_conta_corrente.representante) = '870' OR tbl_conta_corrente.representante IS NULL) ) ";
	$res = pg_exec ($con,$sql);
	
	$sql = "UPDATE tmp_posto_saldo SET saldo = 0 WHERE saldo is null";
	$res = pg_exec ($con,$sql);

	$sql = "UPDATE tmp_posto_saldo SET saldo = saldo - (SELECT SUM (valor_saldo) FROM tbl_conta_corrente WHERE tbl_conta_corrente.posto = tmp_posto_saldo.posto AND tbl_conta_corrente.fabrica = $login_fabrica AND tbl_conta_corrente.data_vencimento <= '$xdata_final' AND trim(tbl_conta_corrente.tipo) IN ('DP','IM') AND (trim(tbl_conta_corrente.representante) = '870' OR tbl_conta_corrente.representante IS NULL) ) ";
	$res = pg_exec ($con,$sql);
	

	if ($tipo_relatorio == '50-maiores-devedores' ) { 
		$sql = "SELECT * FROM tmp_posto_saldo WHERE saldo < 0 ORDER BY saldo LIMIT 50";
	}

	if ($tipo_relatorio == '50-maiores-credores' ) { 
		$sql = "SELECT * FROM tmp_posto_saldo WHERE saldo > 0 ORDER BY saldo DESC LIMIT 50";
	}

	$res = pg_exec ($con,$sql);


	echo "<table width='600' align='center' border='1'>";
	echo "<tr>";
	echo "<td align='center' bgcolor='#FF6666'><b>Posto</b></div></td>";
	echo "<td align='center' bgcolor='#FF6666'><b>Nome</b></div></td>";
	echo "<td align='center' bgcolor='#FF6666'><b>Saldo</b></div></td>";
	echo "</tr>";

	flush();

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		echo "<tr>";
		echo "<td align='left'>" . pg_result ($res,$i,codigo_posto) . "</td>";
		echo "<td align='left'>" . pg_result ($res,$i,nome) . "</td>";
		echo "<td align='right'>" . number_format (pg_result ($res,$i,saldo),2,",",".") . "</td>";
		echo "</tr>";

	}

	echo "</table>";

	
	echo "<p><center><h2>Final de Relatório</h2></center>";

}

?>




<p>


</body>
</html>