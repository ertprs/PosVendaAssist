<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';
include "menu.php";
//ACESSO RESTRITO AO USUARIO MASTER 
if (strpos ($login_privilegios,'financeiro') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}
?>
<style>
a{
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color:#3399FF;
}
.Label{
	font-family: Verdana;
	font-size: 10px;
}
.tabela{
	font-family: Verdana;
	font-size: 12px;
}
.tabela_reduzida{
	font-size: 10px;
}


.Titulo_Tabela{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color:#FFF;
}
.Titulo_Colunas{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}

img{
	border:0;
}


caption{
	BACKGROUND-COLOR: #FFF;
	font-size:12px;
	font-weight:bold;
	text-align:center;
}
.Titulo_Tabela_Menor{
	background-color:#FFF0D2;
	border-bottom:1px solid #FFDE9B;
	font-weight:bold;
	font-size:10px;
}
tr.linha td {
	border-bottom: 1px solid #EDEDE9; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
}

</style>

<div class='error'>
	<? echo $msg_erro; ?>
</div>
<?
echo "<br>";

if(!isset($_GET["mostra"]))
{
	?>
  	<form method="GET" action="financeiro_fornecedor.php">
		<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	  <tr>
  	<td valign="top" align="left">

		<table style=' border:#484789 1px solid; background-color: #EAEEF7' align='center' width='450' border='0'>
		<tr bgcolor='lightsteelblue'>
			<td class='Titulo_Tabela' align='center' colspan='2'>Ficha financeira de fornecedor</td>
		</tr>
		<tr></tr>	
  	<tr>
  	<td class='label' align='right'>fornecedor:</td>
    <td>
    <select size="1" name="pessoa">
  	<?
		$sql = "select tbl_pessoa_fornecedor.pessoa, tbl_pessoa.nome 
			from tbl_pessoa_fornecedor 
			join tbl_pessoa on tbl_pessoa.pessoa = tbl_pessoa_fornecedor.pessoa and 
			tbl_pessoa_fornecedor.empresa = '$login_empresa' ;";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) == 0){
			echo '<p algin="center">Não existe fornecedor cadastrado</p>';
		}
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$pessoa		= trim(pg_result($res, $i, pessoa));
			$nome       = trim(pg_result($res, $i, nome));
			echo "<option value=\"$pessoa\">$nome</option>\n";
		}
		?>
  		</select><br>
  		</td>
  		<tr></tr>
  		<tr>
  		<td colspan='2' align='center'>
		<input type="hidden" name="mostra" value="$pessoa">
		<input type="submit" value="Mostrar ficha financeira do fornecedor" name="pesquisar"></p>
  		</td></tr>
  	</table>

	</td>
  </tr>
  </table>
</form>

<?
} else{
	$pessoa = $_GET["pessoa"];
	$sql2="SELECT nome 
		FROM tbl_pessoa join tbl_pessoa_fornecedor on tbl_pessoa.pessoa = tbl_pessoa_fornecedor.pessoa and tbl_pessoa.pessoa = $pessoa";
	$res = pg_exec($con, $sql2);
	$nome = trim(pg_result($res, nome));
?> 
  <table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
  <tr>
  <td valign="top" align="left">

  <div align="center">
  <center>
	<table style=' border:#484789 1px solid; background-color: #EAEEF7' align='center' width='780' border='0'>
			<tr bgcolor='lightsteelblue'>
			<td class='Titulo_Tabela' align='center' colspan='9'>Ficha financeira do fornecedor: <? echo $nome; ?></td>
		</tr>
		<tr></tr>

  <tr>
	<td class="label" bgcolor="#EEEEEE" align="right">Docto</td>
	<td class="label" bgcolor="#EEEEEE" align="center">Vencimento</td>
	<td class="label" bgcolor="#EEEEEE" align="right">Valor Bruto</td>
	<td class="label" bgcolor="#EEEEEE" align="right">Valor Multa</td>
	<td class="label" bgcolor="#EEEEEE" align="right">Mora diária</td>
	<td class="label" bgcolor="#EEEEEE" align="right">Desconto</td>
	<td class="label" bgcolor="#EEEEEE" align="center">Pagamento</td>
	<td class="label" bgcolor="#EEEEEE" align="right">Valor Pago</td>
  </tr>
  <?
	$sql = "SELECT documento, pagar, fornecedor, vencimento, valor, valor_multa, valor_juros_dia, valor_desconto, valor_pago, pagamento from tbl_pagar where pessoa_fornecedor = $pessoa and empresa = $login_empresa
				ORDER BY vencimento";
//	echo $sql;
	$res = pg_exec($con, $sql);
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$documento		= pg_result($res, $i, documento);
		$pagar			= pg_result($res, $i, pagar);
		$fornecedor		= pg_result($res, $i, fornecedor);
		$vencimento		= pg_result($res, $i, vencimento);
		$valor			= pg_result($res, $i, valor);
		$valor_multa	= pg_result($res, $i, valor_multa);
		$valor_juros_dia = pg_result($res, $i, valor_juros_dia);
		$valor_desconto		= pg_result($res, $i, valor_desconto);
		$valor_pago		= pg_result($res, $i, valor_pago);
		$pagamento		= pg_result($res, $i, pagamento);
		$wvencimento = substr ($vencimento,8,2) . "/" . substr ($vencimento,5,2) . "/" . substr ($vencimento,0,4);
		$wpagamento = substr ($pagamento,8,2) . "/" . substr ($pagamento,5,2) . "/" . substr ($pagamento,0,4);

		
		if($cor == '#E4EBFB') {$cor = '#CFD8E0';} else { $cor = '#E4EBFB';}
		echo "<tr bgcolor='$cor'>";
		echo "<td class='label' align='center' nowrap>$documento</td>";
		if($vencimento < date("Y-m-d") and $pagamento == null ) {
			echo "<td class='label' title='Documento em aberto e vencido!' align='center' bgcolor='#FF9933'>$wvencimento</td>";
		} else {
			echo "<td class='label'  align='center'>$wvencimento</td>";
		}
		echo "<td class='label' align='right' nowrap>&nbsp;" . number_format ($valor,2,",",".") . "</td>";
		echo "<td class='label' align='right' nowrap>&nbsp;" . number_format ($valor_multa,2,",",".") . "</td>";
		echo "<td class='label' align='right' nowrap>&nbsp;" . number_format ($valor_juros_dia,2,",",".") . "</td>";
		echo "<td class='label' align='right' nowrap>&nbsp;" . number_format ($valor_desconto,2,",",".") . "</td>";
		echo "<td class='label' align='center'>&nbsp;$wpagamento&nbsp</td>";
		echo "<td class='label' align='right' nowrap>&nbsp;" . number_format ($valor_pago,2,",",".") . "</td>";
	}
}
?>
</font>
</table>
</table>
</center>
</div>
