<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include "../funcoes.php";

$msg_erro = '';

$btn_baixa = $_POST['consultar'];

$nome            = $_POST['nome'];
$cnpj            = $_POST['cnpj'];
$fornecedor      = $_POST['fornecedor'];
$data_inicial    = $_POST['data_inicial'];
$data_final      = $_POST['data_final'];

if($btn_baixa == 'Consultar'){

	if(strlen($fornecedor) == 0) $fornecedor = "null";

	if (strlen ($data_inicial) == 0) {
		//$msg_erro = "Digite a data inicial.";
	}else{
		$data_inicial = formata_data($data_inicial);
	}

	if (strlen ($data_final) == 0) {
		//$msg_erro = "Digite a data final.";
	}else{
		$data_final = formata_data($data_final);
	}

//	echo "$fornecedor - $nome - $cnpj - $data_inicial - $data_final";

}

?>

<style type="text/css">
input.botao {
	background:#D9E2EF;
	color:#000000;
	border:2px solid #ffffff;
}

.borda {
	border-width: 2px;
	border-style: dotted;
	border-color: #000000;
}

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}
.border {
	border: 1px solid #D9E2EF;
}
</style>

<?
include 'menu.php';
?>

<script language='javascript'>

function MostraEsconde(dados)
{
	if (document.getElementById)
	{
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}


function fnc_pesquisa_fornecedor(campo, tipo) {
	var xcampo = campo;

	if (xcampo.value != "") {
		var url = "";
		url = "fornecedor_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=503, height=400, top=18, left=0");
		janela.fornecedor	= document.frm_pagar.fornecedor;
		janela.nome			= document.frm_pagar.nome;
		janela.cnpj			= document.frm_pagar.cnpj;
		janela.focus();
	}
}

</script>


<body>




<?
if(strlen($msg_erro) > 0){
?>
	<BR>
	<TABLE align='center' style='font-family: verdana; font-size: 13px; color: #FFFFFF; font-weight: bold' bgcolor='#FF0000' >
	<TR>
		<TD><? echo $msg_erro; ?></TD>
	</TR>
	</TABLE>
<? $msg_erro = '';
}else{
	echo "<BR>";
}
?>



<BR>
<FORM METHOD=POST ACTION="<? $PHP_SELF ?>" NAME='frm_pagar'>
<table align='center' width='400' class='table_line' border='1' cellspacing='1' cellpadding='1'>
<tr >
	<td bgcolor='#D9E2EF'>
	<table align='center' width='100%' class='table_line' border='0' cellspacing='0' cellpadding='0'>
	<tr>
		<td nowrap colspan='3' align='right'></td>
	</tr>
	<tr bgcolor='#596D9B'>
		<td nowrap colspan='3' class='menu_top' align='center' height='30'>
			<font size='3' >Pesquisar Contas</font>
		<? echo "<input type='hidden' name='fornecedor' value='$fornecedor'>"; ?>
		</td>
	</tr>
	</table>
	<table align='center'>
	<tr>
		<td align='left' colspan='2' bgcolor='#D9E2EF'>
			<font size='2'>Documento</font><br>
			<input type='text' name='documento' value='<?echo $documento;?>' size='15' maxlength='30'> 
		</td>
	</tr>
	<tr>
		<td nowrap align='left' bgcolor='#D9E2EF'>
			<font size='2'>CNPJ</font><br>
			<input type='text' name='cnpj' value='<?echo $cnpj;?>' size='15' maxlength='30'> 
			<img src="../imagens/btn_lupa.gif" border='0' style="cursor: pointer" onclick="javascript: fnc_pesquisa_fornecedor (document.frm_pagar.cnpj,'cnpj2')">
		</td>
		<td nowrap align='left' bgcolor='#D9E2EF'>
			<font size='2'>Fornecedor</font><br>
			<input type='text' name='nome' value='<?echo $nome;?>' size='30' maxlength=60>
			<img src="../imagens/btn_lupa.gif" border='0' style="cursor: pointer" onclick="javascript: fnc_pesquisa_fornecedor (document.frm_pagar.nome,'nome2')">
		</td>
	</tr>
	</table>
	<table align='center'>
	<tr>
		<? $data_inicial = mostra_data($data_inicial); ?>
		<td nowrap bgcolor='#D9E2EF' align='center'>
			<font size='2'>Data Inicial</font><br>
			<input type='text' name='data_inicial' <? if(strlen($data_inicial) > 0) {?> value="<? echo $data_inicial; ?>" <? } ?> size='10' maxlength='30'> 
		</td>
		<td colspan='2' nowrap bgcolor='#D9E2EF' align='center'>
			<font size='2'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<? $data_final = mostra_data($data_final); ?>
		<td nowrap bgcolor='#D9E2EF' align='center'>
			<font size='2'>Data Final</font><br>
			<input type='text' name='data_final' <? if(strlen($data_final) > 0 ) {?> value="<? echo $data_final; ?>" <? } ?> size='10' maxlength='30'> 
		</td>
	</tr>
	<tr>
		<td colspan='2' nowrap bgcolor='#D9E2EF' align='center'>
			<font size='2'>&nbsp;</font>
		</td>
	</tr>
	</table>
	<table align='center'>
	<tr >
		<td  align='center' bgcolor='#D9E2EF'><INPUT TYPE="submit"  value='Consultar' name='consultar' class='botao'></TD>
	</tr>
	</table>
	</td>
</tr>
</table>
</FORM>
<?

if($btn_baixa == 'Consultar'){

	$fornecedor      = trim($_POST['fornecedor']);
	$data_inicial    = trim($_POST['data_inicial']);
	$data_final      = trim($_POST['data_final']);

	$xdata_inicial = trim(formata_data($data_inicial));
	$xdata_final   = trim(formata_data($data_final));

	$sql = "SELECT fornecedor         ,
					vencimento        ,
					valor             ,
					faturamento
				FROM tbl_pagar 
				JOIN tbl_fornecedor using(fornecedor)
			WHERE posto = $login_posto ";
		if(strlen($data_inicial) > 0 AND strlen($data_inicial) > 0){
			$sql .= " AND vencimento BETWEEN '$xdata_inicial' AND '$xdata_final' ";
			$xdata_inicial = '';
			$xdata_final = '';
		}
		if(strlen($fornecedor) > 0){
			$sql .= " AND fornecedor = '$fornecedor' ";
		}
		if(strlen($documento) > 0){
			$sql .= " AND faturamento = '$documento' ";
		}
	$res = pg_exec($con,$sql);
echo "$sql<br><br>";
	if(pg_numrows($res) > 0){
		for($i=0;$i<pg_numrows($res);$i++){
			echo pg_result($res,$i,faturamento);
			echo " - ";
			echo pg_result($res,$i,fornecedor);
			echo " - ";
				$data_vencimento = mostra_data (pg_result($res,$i,vencimento));
			echo trim($data_vencimento);
			echo "<br>";
		}
	}
}

?>

</BODY>
</HTML>