<?

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

include 'menu.php';
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'compra') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>

<style type="text/css">
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

.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}
</style>

<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
<FORM ACTION='#' METHOD='GET'>
  <tr >
	<td nowrap colspan='7' align='left' background='imagens/azul.gif'>
		<font size='3' color='#ffffff'>Nota Fiscal</font>
	</td>
  </tr>
  <tr>	
    <td nowrap colspan='7' align='left'><font color='#0000ff' size= '1px'>Selecione a Nota</font></td>
  </tr>

<?/*
	$status=$_GET['status'];
	if($status=="conferida"){
		$checked1="checked";
	}else{
		if($status=="nao conferida")	$checked2="checked";
		else							$checked3="checked";
	}
	echo "<input type='radio' name='status' value='conferida' $checked1 onClick= \"location.href='nf_entrada.php?pesquisar=sim&status=conferida'\">conferida";		
	echo "<input type='radio' name='status' value='nao conferida' $checked2 onClick= \"location.href='nf_entrada.php?pesquisar=sim&status=nao conferida'\"> nao conferida	";		
	echo "<input type='radio' name='status' value='' $checked3 onClick= \"location.href='nf_entrada.php?pesquisar=sim&status='\">todas";
	*/
?>

	<tr class='titulo'>	
	  <td nowrap width='20%' align='center'>Nota Fiscal</td>
  	  <td nowrap width='20%' align='center'>Data Emissão</td>
	  <td nowrap width='30%' align='center'>Fornecedor</td>
	  <td nowrap width='20%' align='center'>Total Nota</td>
	</tr>
<?

if ((strlen($_GET['pesquisar'])>0)and (strlen($status)>0)){
	if(strlen($status)>0){
$sql= "
	SELECT  
		tbl_faturamento.faturamento,
		tbl_faturamento.pedido,
		tbl_faturamento.nota_fiscal,
		to_char(tbl_faturamento.emissao,'dd/mm/yyyy') as emissao,
		to_char(tbl_faturamento.conferencia,'dd/mm/yyyy') as conferencia,
		to_char(tbl_faturamento.cancelada,'dd/mm/yyyy') as cancelada,
		tbl_faturamento.cfop,
		tbl_faturamento.transportadora,
		replace(cast(cast(tbl_faturamento.total_nota as numeric(12,2)) as varchar(14)),'.', ',') as total_nota,
		0 as diferenca_total,
		tbl_pessoa.nome as nome_fornecedor
	FROM tbl_faturamento
	JOIN tbl_pessoa on tbl_faturamento.pessoa_fornecedor = tbl_pessoa.pessoa
	WHERE tbl_faturamento.fabrica = $login_empresa
		AND tbl_faturamento.posto = $login_loja
		AND tbl_faturamento.movimento = 'E';";}
}else{
	$sql= "
	SELECT  
		tbl_faturamento.faturamento,
		tbl_faturamento.pedido,
		tbl_faturamento.nota_fiscal,
		to_char(tbl_faturamento.emissao,'dd/mm/yyyy') as emissao,
		to_char(tbl_faturamento.conferencia,'dd/mm/yyyy') as conferencia,
		to_char(tbl_faturamento.cancelada,'dd/mm/yyyy') as cancelada,
		tbl_faturamento.cfop,
		tbl_faturamento.transportadora,
		replace(cast(cast(tbl_faturamento.total_nota as numeric(12,2)) as varchar(14)),'.', ',') as total_nota,
		CASE WHEN tbl_faturamento.total_nota = tbl_pedido.total 
			THEN 1 
			ELSE 0 
		END AS diferenca_total,
		replace(cast(cast(tbl_pedido.total as numeric(12,2)) as varchar(14)),'.', ',') as total_pedido,
		tbl_pessoa.nome as nome_fornecedor
	FROM tbl_faturamento
	JOIN tbl_pedido on tbl_faturamento.pedido = tbl_pedido.pedido
	JOIN tbl_pessoa on tbl_faturamento.pessoa_fornecedor = tbl_pessoa.pessoa
	WHERE tbl_faturamento.fabrica = $login_empresa
		AND tbl_faturamento.posto = $login_loja
		AND tbl_faturamento.movimento = 'E';";
}
$res = pg_exec($con, $sql);

if(@pg_numrows($res)>0){
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$faturamento	 = trim(pg_result($res,$i,faturamento));
		$nota_fiscal	 = trim(pg_result($res,$i,nota_fiscal));
		$pedido			 = trim(pg_result($res,$i,pedido));
		$emissao		 = trim(pg_result($res,$i,emissao));
		$conferencia	 = trim(pg_result($res,$i,conferencia));
		$cancelada		 = trim(pg_result($res,$i,cancelada));
		$cfop			 = trim(pg_result($res,$i,cfop));
		$transportadora	 = trim(pg_result($res,$i,transportadora));
		$total_nota		 = trim(pg_result($res,$i,total_nota));
		$total_pedido    = trim(pg_result($res,$i,total_pedido));
		$nome_fornecedor = trim(pg_result($res,$i,nome_fornecedor));
		$diferenca_total = trim(pg_result($res,0,diferenca_total));

		$pg= "recebimento_confirma.php?faturamento=";

		if ($cor=="#fafafa")	$cor= "#eeeeff";
		else					$cor= "#fafafa";

		$diferenca="";
		if($total_nota <> $total_pedido){
			$diferenca = "<span class='text_curto'> <a href='#' title='Diferença na nota - Tot Nota: R$ $total_nota  - Tot Pedido: R$ $total_pedido' class='ajuda'>?</a></span>";
		}

		echo "<tr bgcolor='$cor' >"; 
		//echo "<td nowrap align='center'><font color='#0000ff'>$faturamento</font></td>";
		echo "<td nowrap align='left' style='cursor: pointer;' onClick= \"location.href='$pg$faturamento'\"><font color='#0000ff'><a href='$pg$faturamento' >$nota_fiscal</a></font> $diferenca</td>";
		//echo "<td nowrap align='center'>$pedido</td>";
		echo "<td nowrap align='center'> $emissao</td>";
		echo "<td nowrap align='left' >$nome_fornecedor</td>";
		echo "<td nowrap align='right' >$total_nota</td>";
		echo "</tr>";
	}
}
?>  
 </FORM>
</table>
</body>
</html>
