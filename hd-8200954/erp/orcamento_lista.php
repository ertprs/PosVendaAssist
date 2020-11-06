<?
include 'index.php';
include ("bdtc.php");
//session_start(); 
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

<head>

	<title>CADASTRO DE COTAÇÃO</title>

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

function fnc_pesquisa_produto (campo, tipo) {
	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.descricao = document.frm_produto.descricao;
		janela.referencia= document.frm_produto.referencia;
		//janela.linha     = document.frm_produto.linha;
		//janela.familia   = document.frm_produto.familia;
		janela.focus();
	}
}
</script>


<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' >

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
<?
//$usuario=$_COOKIE['usuario'];
$conexao = new bdtc();

//$sql= "SELECT * FROM TBL_USUARIO WHERE usuario = $usuario";
//$res= $conexao->consultaArray($sql);
//$nome_usuario= trim(pg_result($res,0,nome));			

?>

<table class='table_line' width='700' border='0' cellspacing='1' cellpadding='2'>
<FORM name='frm_produto' action="<? $PHP_SELF ?>" METHOD='POST'>
  <tr >
	<td nowrap align='right'>
		 <?echo "<a href='orcamento.php?orcamento=novo'><font color='#0000ff'>Novo Orçamento</font></a>";?>
	</td>
  </tr>
  <tr>
    <td colspan='3'>

  <table width='100%' align='left'>
	<tr bgcolor='#596D9B'>	
	  <td colspan='6' nowrap class='menu_top' align='left'>Orçamento</td>
	</tr>
	<tr bgcolor='#596D9B'>	
	  <td nowrap class='titulo' width='100px' align='center'>Nº Orçamento</td>
	  <td nowrap class='titulo' align='center'>Data</td>
	  <td nowrap class='titulo' align='center'>Usuário</td>
	  <td nowrap class='titulo' align='center'>Qtd de Itens</td>
	  <td nowrap class='titulo' align='center'>Status</td>
	</tr>
<?

$sql= "select orcamento, 
		to_char(DATA_ORCAMENTO,'DD/MM/YYYY') as DATA_ORCAMENTO, 
			TBL_ORCAMENTO.STATUS AS STATUS,
			COUNT(ORCAMENTO_ITEM) AS QTD
		FROM TBL_ORCAMENTO
		JOIN TBL_ORCAMENTO_ITEM USING (ORCAMENTO)
		GROUP BY ORCAMENTO, DATA_ORCAMENTO, TBL_ORCAMENTO.STATUS";

$res= $conexao->consultaArray($sql);
if(@pg_numrows($res)>0){
	$c=1;
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$orcamento	=trim(pg_result($res,$i,orcamento));
		$data_orcamento	=trim(pg_result($res,$i,data_orcamento));
		$qtd=trim(pg_result($res,$i,qtd));
		$status		=trim(pg_result($res,$i,status));

		if ($c==2){ 
			echo "<tr bgcolor='#eeeeff'>"; 
			$c=0;
		}else {
			echo "<tr bgcolor='#fafafa'>";
		}
		  echo "<td nowrap align='center'>";
  		  echo "<a href='orcamento.php?orcamento=$orcamento'><font color='#0000ff'>$orcamento</font></a>";
		  echo "</td>";
		  echo "<td nowrap align='center'> $data_orcamento</td>";
		  echo "<td nowrap align='center' >$qtd</td>";
		  echo "<td nowrap align='center' >$status</td>";
		  echo "</tr>";
	  $c++;
	}
}else{
			echo "<tr bgcolor='#ff5555'><td colspan='5'> Sem requisições cadastradas&nbsp;</td></tr>"; 
}
?>  
   </table>
  </td>
  </tr>
  </form>
  <tr>
   <td>
   <table width='100%' align='left'>
	<tr>
      <td nowrap class='table_line' align='left'>&nbsp;
	  <!--<input type='button' onClick="abrir();" name='enviar' value='Gravar'>--></td>
      <td nowrap class='table_line' align='right'>&nbsp;
	  <!--<input type='submit' name='enviar' value='Cancelar'>--></td>
	</tr>
   </table>
	</td>
   </tr>
		
</table>
</body>
</html>
