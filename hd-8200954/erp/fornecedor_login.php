<?
include 'index.php';
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../admin/autentica_admin.php';
//session_start(); 


//redirecionar com javascript
//<script language="JavaScript">
	//window.location= 'requisicao.php';
//</script>



?>

<head>

	<title>ENTRADA DE PRODUTOS</title>

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
</style>
<body>

<table width='700px' class='table_line' border='0' cellspacing='1' cellpadding='2'>
<FORM ACTION='fornecedor_lista_cotacao.php' METHOD='POST'>
  <tr bgcolor='#596D9B'>
	<td nowrap colspan='3' class='menu_top' align='center'><font size='4'>Acesso de Fornecedor</font></td>
  </tr>
  <tr bgcolor='#596D9B'>
	<td colspan='3' class='menu_top' nowrap align='left'>Selecione o Fornecedor</td>
  </tr>
  <tr bgcolor='#fcfcfc'>
  	<td nowrap align='right'>Nome :</td>
	<td nowrap colspan='2' align='left'>
<?

$sql= " SELECT * 
		FROM tbl_posto 
		JOIN tbl_posto_fabrica USING(posto) 
		WHERE fabrica = $login_fabrica 
		ORDER BY nome limit 30";

$res= pg_exec($con, $sql);

if(@pg_numrows($res)>0){

	echo "<select name='fornecedor'>";
	echo "<option value=''>Selecionar";
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

		$fornecedor= trim(pg_result($res,$i,posto));	
		$nome_fornecedor= trim(pg_result($res,$i,nome));	
		echo "<option value='$fornecedor'>$nome_fornecedor";
	}
	echo "</select>";
}else{
	echo "nenhum fornecedor encontrado-$sql";
}
?>
    </td>
  </tr>
  <tr bgcolor='#fafafa'>
	<td nowrap colspan='2' align='right'>
	<?if(strlen($_GET['mensagem'])>0)echo "<font size='3' color='#ff0000'>".$_GET['mensagem']."</font>";?> 
    <input type='hidden' name='requisicao' value='nova'>
  </td>
  </tr>
  <tr bgcolor='#fafafa'>
	<td nowrap width='100' align='right'></td>
	<td nowrap colspan='2' width='200' align='left'>

		<input type='submit' name='enviar' value='Entrar'>
	</td>
  </tr>
  <tr><td> </td>
  </tr> 
  </form>
</table>

</body>
</html>
