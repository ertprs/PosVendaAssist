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


$fornecedor= $_POST["fornecedor"];
if(strlen($fornecedor)==0){
	$fornecedor = $_GET["fornecedor"];
}

$res="";

if(strlen($fornecedor)==0){
	$fornecedor = $login_loja;
}

if(strlen($fornecedor)>0){
	$sql= "	SELECT tbl_pessoa.nome
			FROM tbl_pessoa
			WHERE pessoa= $fornecedor";
	$res= pg_exec($con, $sql);
	if(pg_numrows($res)>0){
		$nome_fornecedor= trim(pg_result($res,0,nome));			
	}
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
  <tr >
	<td nowrap align='left'><b>Fornecedor: <?echo $nome_fornecedor;?></b></td>
  </tr>
  <tr bgcolor='#596D9B'>
	<td nowrap class='menu_top' align='left' background='imagens/azul.gif'>
		<font align='left' size='3'>Cotacão
	</td>
  </tr>
  <tr>
	<td >

  <table width='100%' class='table_line' border='0' cellspacing='1' cellpadding='2'>
	<tr class='titulo'>	
	  <td nowrap class='titulo' width='20%' align='center'>Cód. Cotação</td>
	  <td nowrap class='titulo' width='30%' align='center'>Data de Abertura</td>
  	  <td nowrap class='titulo' width='30%' align='center'>Data de Fechamento</td>
	  <td nowrap class='titulo' width='20%' align='center'>Status</td>
	</tr>

<?


if(strlen($fornecedor)>0){
	$sql= "	SELECT 
				tbl_cotacao_fornecedor.cotacao_fornecedor, 
				to_char(tbl_cotacao.data_abertura,'dd/mm/yyyy') as data_abertura,
				to_char(tbl_cotacao.data_fechamento,'dd/mm/yyyy') as data_fechamento,
				tbl_cotacao_fornecedor.status,
				tbl_cotacao.cotacao, 
				tbl_pessoa.pessoa as fornecedor, 
				tbl_pessoa.nome
			FROM tbl_cotacao_fornecedor
			JOIN tbl_cotacao USING(cotacao)
			JOIN tbl_pessoa on tbl_pessoa.pessoa = tbl_cotacao_fornecedor.pessoa_fornecedor
			JOIN tbl_pessoa_fornecedor on tbl_pessoa_fornecedor.pessoa = tbl_pessoa.pessoa
			WHERE tbl_pessoa.empresa = $login_empresa AND tbl_pessoa_fornecedor.pessoa= $fornecedor
			ORDER BY tbl_cotacao_fornecedor.cotacao_fornecedor desc";
	//echo "sql:$sql";
	$res= pg_exec($con, $sql);
	if(pg_numrows($res)>0){
		$nome_fornecedor= trim(pg_result($res,0,nome));			
	}
}

if(strlen($fornecedor)>0){
	if(pg_numrows($res)>0){
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$cotacao_fornecedor=trim(pg_result($res,$i,cotacao_fornecedor));
			$cotacao=trim(pg_result($res,$i,cotacao));
			$fornecedor=trim(pg_result($res,$i,fornecedor));
			$nome_fornecedor=trim(pg_result($res,$i,nome));
			$data_abertura=trim(pg_result($res,$i,data_abertura));
			$data_fechamento=trim(pg_result($res,$i,data_fechamento));
			$status=trim(pg_result($res,$i,status));

			if ($cor=="#eeeeff")	$cor = "#fafafa";
			else					$cor = "#eeeeff";

			echo "<tr bgcolor='$cor'>"; 
			echo "<td nowrap align='center'>";
			echo "<a href='fornecedor_cotacao.php?cotacao_fornecedor=$cotacao_fornecedor'>
				<font color='blue'>$cotacao</font></a>";
			echo "</td>";
			echo "<td nowrap align='center'>$data_abertura </td>";
			echo "<td nowrap align='center'>$data_fechamento</td>";
			echo "<td nowrap align='center' >";
			if ($status=="aberta") echo "<font color='#ff0000'> $status </font>"; else echo "<font color='#000000'> $status </font>";
			echo "</td>";
			echo "</tr>";
		}
	}else{
		echo "<tr bgcolor='#fafafa'>";
		echo "<td nowrap colspan='4' align='center'>";
		echo "<font color='#0000ff'>Nenhuma cotação em aberto para este Fornecedor!</font>"; 
		echo "</td>";
		echo "</tr>";	
	}
}else{
	echo "<tr bgcolor='#fafafa'>";
	echo "<td nowrap colspan='4' align='center'>";
	echo "<font color='#ff0000'>Fornecedor não encontrado!</font>"; 
	echo "</td>";
	echo "</tr>";	
}
?>  
  </table>
	 </td>
   </tr>
</table>