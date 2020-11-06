<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

?>


<?
$referencia = trim($HTTP_POST_VARS["referencia"]);
$descricao  = trim($HTTP_POST_VARS["descricao"]);
?>

<html>
<head>
<title>Telecontrol - Menu Financeiro</title>
</head>

<style type="text/css">
<!--

#externo {
	position: relative;
	width: 650px;
	height: 20px;
	left: 12%;
	border-width: thin;
	border-color: #000000
}

#referencia {
	position: absolute;
	top: 0;
	left: 0;
	width: 50px;
	text-align: center;
	background-color: #EFF5F5;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#descricao {
	position: absolute;
	top: 0;
	left: 55;
	width: 350px;
	background-color: #EFF5F5;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#almoxarifado {
	position: absolute;
	top: 0;
	left: 410;
	width: 50px;
	background-color: #EFF5F5;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#qtde {
	position: absolute;
	top: 0;
	left: 465;
	width: 50px;
	background-color: #EFF5F5;
	text-align: right;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#font_form {
	font:60% Tahoma, Verdana, Arial, Helvetica, Sans-Serif

}
-->
</style>

<body bgcolor="#FFFFFF" text="#000000" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" link="#333333">

<table width="760" align="center" border="0" cellspacing="0" cellpadding="0">
<tr>
	<form name="frm_estoque" method="post" action="<? $PHP_SELF ?>">
	
	<td width="46" bgcolor="#E8E3E3" valign="top">&nbsp;</td>
	
	<td width="668" valign="top">
		<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#8AC4A2">
			<tr>
				<td bgcolor="#E8E3E3"><hr></td>
			</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#8AC4A2">
			<tr>
				<td align="center"><b><font face="Geneva, Arial, Helvetica, san-serif">:: Relatório de Estoque ::</font></b> 
			</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="#8AC4A2">
		<tr>
			<td width="30%">&nbsp;</td>
			<td valign="middle"><div id="font_form">Código da Peça</div><input type="text" name="referencia" size="10" value="<? echo $referencia ?>"></td>
			<td valign="middle"><div id="font_form">ou</div></td>
			<td valign="middle"><div id="font_form">Descrição</div><input type="text" name="descricao" size="30" value="<? echo $descricao ?>"></td>
			<td width="30%">&nbsp;</td>
		</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="#8AC4A2">
		<tr>
			<td align="center" valign="middle"><input type="submit" name="btnAcao" value="Enviar"><br></td>
		</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#8AC4A2">
			<tr>
				<td bgcolor="#E8E3E3"><hr></td>
			</tr>
		</table>
		
		<?
		if (strlen($referencia) > 0 OR strlen($descricao) > 0) {
			echo "<br>\n";
			
			if (strlen($referencia) > 0) {
				$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, 
							   tbl_estoque.almoxarifado, tbl_estoque.qtde
						FROM   tbl_peca
						JOIN   tbl_estoque USING (peca)
						WHERE  tbl_peca.referencia = LPAD (TRIM ('$referencia'),6,'0')
						AND    tbl_peca.fabrica    = $login_fabrica
						ORDER BY tbl_peca.referencia, tbl_estoque.almoxarifado ";
				$res = pg_exec ($con,$sql);
			}
			
			if (strlen($descricao) > 0) {
				$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, 
							   tbl_estoque.almoxarifado, tbl_estoque.qtde
						FROM   tbl_peca
						JOIN   tbl_estoque USING (peca)
						WHERE  tbl_peca.descricao ILIKE '%$descricao%'
						AND    tbl_peca.fabrica    = $login_fabrica
						ORDER BY tbl_peca.referencia, tbl_estoque.almoxarifado ";
				$res = pg_exec ($con,$sql);
			}
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<div id='externo'>\n" ;
				
				echo "<div id='referencia'><b>\n" ;
				echo pg_result($res,$i,referencia);
				echo "</b></div>\n";
				
				echo "<div id='descricao'><b>\n" ;
				echo pg_result($res,$i,descricao);
				echo "</b></div>\n";
				
				echo "<div id='almoxarifado'><b>\n" ;
				echo pg_result($res,$i,almoxarifado);
				echo "</b></div>\n";
				
				echo "<div id='qtde'><b>\n" ;
				echo pg_result($res,$i,qtde);
				echo "</b></div>\n";
				
				echo "</div>\n";
			}
		}
		?>
	</td>
	
	<td width="46" bgcolor="#8AC4A2">&nbsp;</td>
	</form>
</tr>
</table>

</body>
</html>