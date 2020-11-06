<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../admin/autentica_admin.php';

include 'menu.php';

$pedido= $_POST['pedido'];
if(strlen($pedido)==0)
	$pedido= $_GET['pedido'];

$faturamento= $_POST['faturamento'];
if(strlen($faturamento)==0)
	$faturamento= $_GET['faturamento'];

if(strlen($pedido)>0){
	$sql= "	SELECT  tbl_faturamento.faturamento
			FROM tbl_faturamento 
			WHERE pedido=$pedido";
	$res= pg_exec($con, $sql);	

	// SE O PEDIDO AINDA NAO GEROU NOTA FISCAL, ENTAO INSERE OS DADOS DO PEDIDO
	if(@pg_numrows($res)==0){
		$res= pg_exec($con, "begin;");	

		$sql= "INSERT INTO 
					tbl_faturamento
					(
						 pedido, 
						 fabrica,
						 posto,

					)
					(
					SELECT 
						pedido, 
						$login_empresa,
						valor_frete, 
						(SELECT 
							fornecedor 
						 FROM tbl_pedido
						 JOIN tbl_cotacao_fornecedor USING(cotacao_fornecedor)
						 WHERE pedido=$pedido	
						), 
					'nao conferida'
					FROM tbl_pedido
					WHERE pedido = $pedido
					);";

		$sql.= " 
		INSERT INTO TBL_FATURAMENTO_ITEM 
			(FATURAMENTO, 
			 PRODUTO, 
			 QTDE,
			 PRECO, 
			 ALIQ_IPI)
			(
			SELECT 
				( SELECT CURRVAL ('TBL_FATURAMENTO_FATURAMENTO_SEQ') AS FATURAMENTO ), 
				PRODUTO, 
				QUANTIDADE, 
				VALOR_UNITARIO, 
				PERCENTUAL_IPI
			FROM TBL_ITEM_PEDIDO
			WHERE PEDIDO=$pedido)";

		//echo " sql:".$sql;
	//	$res= $res= pg_exec($con, $sql);
	}
}
	
//AQUI FAZ UPDATE 
if($_POST['botao']=='Conferir'){

	$sql= "SELECT  *
			FROM TBL_FATURAMENTO
			JOIN TBL_FATURAMENTO_ITEM USING(FATURAMENTO)
			WHERE pedido=$pedido";
	$res= $res= pg_exec($con, $sql);	
	$preco			= trim(pg_result($res,0,preco));
	
	//FATURAMENTO
	if(strlen($_POST['faturamento'])>0)	$faturamento = $_POST['faturamento'];
	else								$faturamento = "null";

	//PEDIDO
	if(strlen($_POST['pedido'])>0)		$pedido		 = $_POST['pedido'];
	else								$pedido		 = "null";
		
	//DATA DE EMISSAO
	if(strlen($_POST['emissao'])>0)		$emissao	 = "'".$conexao->formataData($_POST['emissao'])."'";
	else								$emissao	 = "null";

	//DATA DE CONFERENCIA
	if(strlen($_POST['conferencia'])>0)	$conferencia = "'".$conexao->formataData($_POST['conferencia'])."'";
	else								$conferencia = "current_date";

	//CFOP
	if(strlen($_POST['cfop'])>0)		$cfop		 = $_POST['cfop'];
	else								$cfop		 = "null";

	//TRANSPORTADORA
	if(strlen($_POST['transportadora'])>0) $transportadora	= $_POST['transportadora'];
	else								   $transportadora	= "null";
			
	//VALOR TOTAL DA NOTA
	$tot=$_POST['total_nota'];
	if(strlen($_POST['total_nota'])>0)	$total_nota	= number_format(str_replace( '.', '', $tot), 2, '.','');	else								$total_nota	= "null";


	
	//NOTA FISCAL
	if(strlen($_POST['nota_fiscal'])>0){
		
		$nota_fiscal = $_POST['nota_fiscal'];
		
		$sql= "UPDATE TBL_FATURAMENTO
				SET PEDIDO= $pedido, 
				NOTA_FISCAL= $nota_fiscal,
				EMISSAO= $emissao, 
				CONFERENCIA= $conferencia, 
				CFOP= $cfop, 
				TRANSPORTADORA= $transportadora, 
				TOTAL_NOTA= $total_nota,
				status='conferida'
				WHERE FATURAMENTO= $faturamento;";
		//$res= $res= pg_exec($con, $sql);
			echo " sql1:".$sql;

		for ( $i = 0 ; $i < count($_POST['faturamento_item']);$i++){

			if(strlen($_POST['faturamento_item'][$i])>0)  $faturamento_item= $_POST['faturamento_item'][$i];
			else										  $faturamento_item= "null";

			if(strlen($_POST['qtde_estoque'][$i])>0)	  $qtde_estoque= $_POST['qtde_estoque'][$i];
			else										  $qtde_estoque= "null";
		
			if(strlen($_POST['qtde_quebrada'][$i])>0)	  $qtde_quebrada= $_POST['qtde_quebrada'][$i];
			else										  $qtde_quebrada= "null";

			if(strlen($_POST['preco_conferencia'][$i])>0) $preco_conferencia= $_POST['preco_conferencia'][$i];
			else										  $preco_conferencia= "null";

			if(strlen($_POST['aliq_ipi'][$i])>0)		  $aliq_ipi= $_POST['aliq_ipi'][$i];
			else										  $aliq_ipi= "null";

			if(strlen($_POST['aliq_icms'][$i])>0)		  $aliq_icms	= $_POST['aliq_icms'][$i];
			else										  $aliq_icms= "null";

			//COMPARAR SE ALIQ_IPI Ñ ESTÁ VAZIO, E ENTAO CALCULAR O VALOR
			if(strlen($_POST['aliq_ipi'][$i])>0)		  $valor_ipi= ($_POST['aliq_ipi'][$i] * $preco);
			else										  $valor_ipi= "null";

			//COMPARAR SE ALIQ_ICMS Ñ ESTÁ VAZIO, E ENTAO CALCULAR O VALOR
			if(strlen($_POST['aliq_icms'][$i])>0)		  $valor_icms	= $_POST['aliq_icms'][$i];
			else										  $valor_icms	= "null";

			$sql= " 
				UPDATE TBL_FATURAMENTO_ITEM
				SET 
				QTDE_ESTOQUE		= $qtde_estoque, 
				QTDE_QUEBRADA		= $qtde_quebrada, 
				PRECO_CONFERENCIA	= $preco_conferencia, 
				ALIQ_ICMS			= $aliq_icms, 
				ALIQ_IPI			= $aliq_ipi, 
				VALOR_ICMS			= $valor_icms, 
				VALOR_IPI			= $valor_ipi
				where faturamento_item= $faturamento_item";

			//echo " <BR>sql2:".$sql;
			//$res= $res= pg_exec($con, $sql);

			echo "<font color='#0000ff'>Salvo com sucesso!</font>";	
		}
	}else{
		echo "<font color='#ff0000'>É necessário inserir o número da Nota Fiscal</font>";	
	}
	
}

if( ($faturamento > 0) or ($pedido > 0) ){
	if(strlen($pedido)>0)
		$whr= "pedido= ".$pedido;
	else
		$whr= "faturamento= ".$faturamento;
//ADD>>>	TBL_FATURAMENTO.STATUS,	
	$sql= "
	SELECT  TBL_FATURAMENTO.FATURAMENTO,
	TBL_FATURAMENTO.PEDIDO,
	TBL_FATURAMENTO.NOTA_FISCAL,
	TO_CHAR(TBL_FATURAMENTO.EMISSAO,'DD/MM/YYYY') AS EMISSAO,
	TO_CHAR(TBL_FATURAMENTO.CONFERENCIA,'DD/MM/YYYY') AS CONFERENCIA,
	TO_CHAR(TBL_FATURAMENTO.CANCELADA,'DD/MM/YYYY') AS CANCELADA,
	TBL_FATURAMENTO.CFOP,
	TBL_FATURAMENTO.TRANSPORTADORA,
	REPLACE(CAST(CAST(TBL_FATURAMENTO.VALOR_FRETE AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') as VALOR_FRETE,
	REPLACE(CAST(CAST(TBL_FATURAMENTO.TOTAL_NOTA AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') as TOTAL_NOTA,
	TBL_FATURAMENTO_ITEM.FATURAMENTO_ITEM,
	TBL_FATURAMENTO_ITEM.peca,
	TBL_FATURAMENTO_ITEM.QTDE,
	TBL_FATURAMENTO_ITEM.PRECO,
	TBL_FATURAMENTO_ITEM.QTDE_ESTOQUE,
	TBL_FATURAMENTO_ITEM.ALIQ_ICMS,
	TBL_FATURAMENTO_ITEM.ALIQ_IPI,
	TBL_FATURAMENTO_ITEM.ALIQ_REDUCAO,
	TBL_FATURAMENTO_ITEM.QTDE_QUEBRADA,
	TBL_FATURAMENTO_ITEM.BASE_ICMS,
	TBL_FATURAMENTO_ITEM.VALOR_ICMS,
	TBL_FATURAMENTO_ITEM.BASE_IPI,
	TBL_FATURAMENTO_ITEM.VALOR_IPI,
	TBL_POSTO.NOME AS NOME_FORNECEDOR,
	TBL_PECA.DESCRICAO AS NOME_PRODUTO
	FROM TBL_FATURAMENTO
	JOIN TBL_FATURAMENTO_ITEM USING(FATURAMENTO)
	JOIN TBL_POSTO on tbl_faturamento.posto = tbl_posto.posto
	JOIN TBL_peca USING(peca)
	WHERE $whr";
	//ECHO "<br>$sql<br>";

	$res= $res= pg_exec($con, $sql);

	if(@pg_numrows($res)==0){
		$faturamento	 = "";
		$pedido			 = "";
		echo  "<font color='#0000ff'>SEM PRODUTOS</font>";
		$nota_fiscal	 = "";
		$conferencia	 = "";
		$cancelada		 = "";
		$cfop			 = "";
		$transportadora	 = "";
		$total_nota		 = "";
		$faturamento_item= "";
		$produto		= "";
		$qtde			= "";
		$preco			= "";
		$qtde_estoque	= "";
		$aliq_icms		= "";
		$aliq_ipi		= "";
		$aliq_reducao	= "";
		$qtde_quebrada	= "";
		$base_icms		= "";
		$valor_icms		= "";
		$base_ipi		= "";
		$valor_ipi		= "";
		$valor_frete	= "";
		$status			= "";

	}else{
		$faturamento	 = trim(pg_result($res,0,faturamento));
		$nota_fiscal	 = trim(pg_result($res,0,nota_fiscal));
		$pedido			 = trim(pg_result($res,0,pedido));
		$emissao		 = trim(pg_result($res,0,emissao));
		$conferencia	 = trim(pg_result($res,0,conferencia));
		$cancelada		 = trim(pg_result($res,0,cancelada));
		$cfop			 = trim(pg_result($res,0,cfop));
		$transportadora	 = trim(pg_result($res,0,transportadora));
		$total_nota		 = trim(pg_result($res,0,total_nota));
		$faturamento_item= trim(pg_result($res,0,faturamento_item));
		$peca			= trim(pg_result($res,0,peca));
		$qtde			= trim(pg_result($res,0,qtde));
		$preco			= trim(pg_result($res,0,preco));
		$qtde_estoque	= trim(pg_result($res,0,qtde_estoque));
		$aliq_icms		= trim(pg_result($res,0,aliq_icms));
		$aliq_ipi		= trim(pg_result($res,0,aliq_ipi));
		$aliq_reducao	= trim(pg_result($res,0,aliq_reducao));
		$qtde_quebrada	= trim(pg_result($res,0,qtde_quebrada));
		$base_icms		= trim(pg_result($res,0,base_icms));
		$valor_icms		= trim(pg_result($res,0,valor_icms));
		$base_ipi		= trim(pg_result($res,0,base_ipi));
		$valor_ipi		= trim(pg_result($res,0,valor_ipi));
		$valor_frete	= trim(pg_result($res,0,valor_frete));
		//$status			= trim(pg_result($res,0,status));
		$nome_fornecedor= trim(pg_result($res,0,nome_fornecedor));
		$nome_produto	= trim(pg_result($res,0,nome_produto));
  }
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

<head>

	<title>NOTA FISCAL DE ENTRADA</title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<script language="JavaScript">

function fnc_pesquisa_nf_entrada(){
	
		var url = "";
		url = "pedido_pesquisa.php";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=0, left=0");
		//janela.focus();
}
</script>



<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' >
<table width='700px' class='table_line' border='0' cellspacing='1' cellpadding='2'>

<FORM name='frm_nf_entrada' ACTION='nf_entrada_item.php' METHOD='POST'>

  <tr bgcolor='#596D9B'>
	<td nowrap colspan='4' class='menu_top' align='left'>
	  <font size='3'>Nota Fiscal de Entrada</font>
  	  <?
	  echo "<input type='hidden' name='faturamento' value='$faturamento'>
		    <input type='hidden' name='pedido' value='$pedido'>
	  ";
	  ?>
	</td>
	<td nowrap colspan='3' class='menu_top' align='left'>
  	  <?
	  echo "<a href='pedido.php?pedido=$pedido'><font color='#ddddff'>Ver pedido</font></a>";
	  ?>
	</td>

  </tr>
  <tr>
	<td nowrap colspan='7' bgcolor='#ced7e7' align='left'>	  
	  <?echo "Fornecedor:<b>".substr($nome_fornecedor,0,25)."</b> | ";?>
	  Pedido: <b><?echo $pedido;?></b>
	</td>
  </tr>
  <tr bgcolor='#fafafa'>
		<a href='#'>
			  <img src="imagens/lupa.png" border='0' onclick="javascript: fnc_pesquisa_nf_entrada()">
		</a>Importar Nota de Pedido

	<td nowrap align='center'>Nota Fiscal Nº<br>
		<input type='text' name='nota_fiscal' value='<?echo $nota_fiscal;?>' size=10 maxlength=30> 
	</td>
	<td nowrap colspan='1' align='center'>Data Emissão<br>
		<input type='text' name='emissao' value='<?echo $emissao;?>' size='7' maxlength='30'> 
	</td>
	<td nowrap colspan='1' align='center'>ALIQ. ICMS<br>
		<input type='text' name='aliq_icms' value='<?echo $aliq_icms;?>' size='7' maxlength='30'> 
	</td>
	<td nowrap colspan='1' align='center'>Valor ICMS<br>
		<input type='text' name='valor_icms' value='<?echo $valor_icms;?>' size='7' maxlength='30'> 
	</td>
	<td nowrap colspan='1' align='center'>Valor Frete<br>
		<input type='text' name='valor_frete' value='<?echo $valor_frete;?>' size='7' maxlength='30'> 
	</td>
	<td nowrap colspan='1' align='center'>CFOP<br>
<?	$sql= "SELECT 
				CFOP,
				NATUREZA_OPERACAO
		   FROM TBL_CFOP
		   ORDER BY NATUREZA_OPERACAO";
	//$res_sel= $res= pg_exec($con, $sql);

	if(@pg_numrows($res_sel)>0){
		echo "<select name='cfop'>";
		echo "<option value=''>Selecionar";
		for ( $i = 0 ; $i < @pg_numrows ($res_sel) ; $i++ ) {
			$selected="";
			$num_cfop= trim(pg_result($res_sel,$i,cfop));	
			$natureza_operacao= trim(pg_result($res_sel,$i,natureza_operacao));	
			if($num_cfop==$cfop)
				$selected= "selected";
			echo "<option value='$num_cfop' $selected>$num_cfop - $natureza_operacao";
		}
		echo "</select>";
	}
?>
	</td>
		<td nowrap colspan='1' align='center'>Status <br>
		<?
		if ($status=='conferida')
			echo "<font color='#0000ff'>$status </font>";
		else 
			echo "<font color='#ff0000'>$status </font>";
		
		
		?>
	</td>

  </tr>
  <tr>
    <td colspan='7'>
  <table width='100%' align='left' border='0' cellspacing='1' cellpadding='1'>
	<tr bgcolor='#596D9B'>	
	  <td nowrap class='titulo' colspan='2'  align='center'>Produto</td>
	  <td nowrap class='titulo' colspan='3' align='center'>Quantidade</td>
	  <td nowrap class='titulo' colspan='3' align='center'>Preços</td>
	  <td nowrap class='titulo' colspan='2' align='center'>Valor IPI</td>
	  <td nowrap class='titulo' colspan='2' align='center'>Valor ICMS</td>
	  <td nowrap class='titulo' colspan='1' align='center'>Total</td>
	</tr>
	<tr bgcolor='#596D9B'>	
	  <td nowrap class='titulo' width='5%' align='center'>Cód.</td>
	  <td nowrap class='titulo' width='5%' align='center' >Descrição</td>
	  <td nowrap class='titulo' width='5%' align='center'>Qtde</td>
	  <td nowrap class='titulo' width='70%' align='center'>Qtde Estoque</td>
	  <td nowrap class='titulo' width='5%' align='center'>Qtde Quebrada</td>
	  <td nowrap class='titulo' width='5%' align='center'>Frete</td>
	  <td nowrap class='titulo' width='5%' align='center'>Preço Un.</td>
	  <td nowrap class='titulo' width='5%' align='center'>Preço Conf.</td>
	  <td nowrap class='titulo' width='5%' align='center'>%IPI</td>
	  <td nowrap class='titulo' width='5%' align='center'>Valor IPI</td>
	  <td nowrap class='titulo' width='5%' align='center'>%ICMS</td>
	  <td nowrap class='titulo' width='5%' align='center'>Valor ICMS</td>
	  <td nowrap class='titulo' width='5%' align='center'>Valor Total</td>
	</tr>
<?
$c=1;
if(@pg_numrows($res)>0){
	
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$faturamento	 = trim(pg_result($res,$i,faturamento));
		$pedido			 = trim(pg_result($res,$i,pedido));
		$emissao		 = trim(pg_result($res,$i,emissao));
		$nota_fiscal	 = trim(pg_result($res,$i,nota_fiscal));
		$conferencia	 = trim(pg_result($res,$i,conferencia));
		$cancelada		 = trim(pg_result($res,$i,cancelada));
		$cfop			 = trim(pg_result($res,$i,cfop));
		$transportadora	 = trim(pg_result($res,$i,transportadora));
		$total_nota		 = trim(pg_result($res,$i,total_nota));
		$faturamento_item= trim(pg_result($res,$i,faturamento_item));
		$peca			= trim(pg_result($res,$i,peca));
		$qtde			= trim(pg_result($res,$i,qtde));
		$preco			= trim(pg_result($res,$i,preco));
		$qtde_estoque	= trim(pg_result($res,$i,qtde_estoque));
		$aliq_icms		= trim(pg_result($res,$i,aliq_icms));
		$aliq_ipi		= trim(pg_result($res,$i,aliq_ipi));
		$aliq_reducao	= trim(pg_result($res,$i,aliq_reducao));
		$qtde_quebrada	= trim(pg_result($res,$i,qtde_quebrada));
		$base_icms		= trim(pg_result($res,$i,base_icms));
		$valor_icms		= trim(pg_result($res,$i,valor_icms));
		$base_ipi		= trim(pg_result($res,$i,base_ipi));
		$valor_ipi		= trim(pg_result($res,$i,valor_ipi));
		$valor_frete	= trim(pg_result($res,$i,valor_frete));
		//$status			= trim(pg_result($res,$i,status));	
		$nome_produto	= trim(pg_result($res,$i,nome_produto));	
	
	if ($c==2){ 
		echo "<tr bgcolor='#eeeeff'>"; 
		$c=0;
	}else {
		echo "<tr bgcolor='#fafafa'>";
	}

	echo "<td nowrap align='center'>$produto
	<input type='hidden' name='faturamento_item[]' value='$faturamento_item'>
	</td>";
	echo "<td nowrap align='left'>$nome_produto</td>";
	echo "<td nowrap align='center'>$qtde</td>";
	echo "<td nowrap align='center' >";
	echo "<input type='text' size='4' name='qtde_estoque[]' value='$qtde_estoque'>";
	echo "</td>";	
	echo "<td nowrap align='center' >";
	echo "<input type='text' size='4' name='qtde_quebrada[]' value='$qtde_quebrada'>";
	echo "</td>";	
	echo "<td nowrap align='center'>$valor_frete</td>";
	echo "<td nowrap align='center'>$preco</td>";
	echo "<td nowrap align='center' >";
	echo "<input type='text' size='4' name='preco_conferencia[]' value='$preco_conferencia'>";
	echo "</td>";	
	echo "<td nowrap align='center'>";
	echo "<input type='text' size='4' name='aliq_ipi[]' value='$aliq_ipi'>";
	echo "</td>";	
	echo "<td nowrap align='center'>$valor_ipi</td>";
	echo "<td nowrap align='center'>";
	echo "<input type='text' size='4' name='aliq_icms[]' value='$aliq_icms'>";
	echo "</td>";	
	echo "<td nowrap align='center'>$valor_icms</td>";
	echo "<td nowrap align='center'>".(($valor_total * $valor_icms)/100)."</td>";
	echo "<td nowrap align='center'>$valor_total</td>";
	echo "</tr>";
	$c++;
	}
}else{
echo "nao mesmo";
}
?>
</table>
  </td>
  </tr>

  <tr >	
    <td colspan='5' nowrap align='left'>
		<input type='submit' name='botao' value='Conferir'>
	</td>
  </tr>
  </FORM>
</table>
</body>
</html>
