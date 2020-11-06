<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php'; 
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

<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
<FORM name='frm_produto' action="<? $PHP_SELF ?>" METHOD='POST'>
  <tr bgcolor='#596D9B'>	
    <td colspan='5' nowrap class='menu_top' align='left' background='imagens/azul.gif'><font size='3'><b>Alterar Requisição</b></font></td>
  </tr>
  <tr bgcolor='#596D9B'>	
    <td nowrap class='titulo' width='100px' align='center'>Nº Requisição</td>
    <td nowrap class='titulo' align='center'>Data</td>
    <td nowrap class='titulo' align='center'>Usuário</td>
    <td nowrap class='titulo' align='center'>Qtd de Itens</td>
    <td nowrap class='titulo' align='center'>Status</td>
  </tr>

<?

$sql= "SELECT 
			tbl_requisicao.requisicao, 
			tbl_requisicao.status,
			TO_CHAR(data,'DD/MM/YYYY') as DATA, 
			pessoa_empregado.nome,
			COUNT(requisicao_item) as qtd
		FROM tbl_requisicao
		JOIN tbl_requisicao_item using (requisicao)
		JOIN tbl_empregado on tbl_empregado.empregado = tbl_requisicao.empregado
		JOIN tbl_pessoa as pessoa_empregado on pessoa_empregado.pessoa = tbl_empregado.pessoa
		WHERE tbl_requisicao.status='aberto' 
			AND tbl_empregado.empregado = $login_empregado  
		GROUP BY tbl_requisicao.requisicao, 
				 tbl_requisicao.data, 
				 tbl_requisicao.status, 
 				 pessoa_empregado.nome,
				 pessoa_empregado.pessoa";

$res= pg_exec($con, $sql);
if(@pg_numrows($res)>0){

	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$requisicao	=trim(pg_result($res,$i,requisicao));
		$data		=trim(pg_result($res,$i,data));
		$nome		=trim(pg_result($res,$i,nome));
		$status		=trim(pg_result($res,$i,status));
		$qtd		=trim(pg_result($res,$i,qtd));

		if ($cor== "#fafafa")			$cor="#eeeeff";
		else							$cor="#fafafa";

		echo "<tr bgcolor='$cor'>";
		echo "<td nowrap align='center'>";
		echo "<a href='requisicao.php?requisicao=$requisicao'><font color='#0000ff'>$requisicao</font></a>";
		echo "</td>";
		echo "<td nowrap align='center'> $data</td>";
		echo "<td nowrap align='center' >$nome</td>";
		echo "<td nowrap align='center' >$qtd</td>";
		echo "<td nowrap align='center' >$status</td>";
		echo "</tr>";
	}
}else{
	echo "<tr bgcolor='#ffffff'><td colspan='5' align='center'> <font color='#0000ff'><b>Sem requisições em aberto!</font></b></td></tr>"; 
}
?>  
  </form>
</table>
</body>
</html>
