<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Shopping Telecontrol - Peças para Assistência Técnica </title>
<meta name="author" content="telecontrol">
<meta name="keywords" content="telecontrol, peça">
<meta name="description" content="">

<script language="javascript">
<!--
function testa_cookie() {
  var resposta;
  // Esta funcao testa se os cookies sao aceitos
  // Tenta escrever um cookie.
  document.cookie = 'aceita_cookie=sim';
  // Checa se conseguiu
  if(document.cookie == '') {
	alert("Foi detectado que a opção 'Cookies' no seu browser não está habilitada. Se prosseguir essa loja não irá funcionar para você. Por favor habilite-os.");
  }
  // Apaga o cookie.
  document.cookie = 'aceita_cookie=sim; expires=Fri, 13-Apr-1970 00:00:00 GMT';
  return true;
}

//-->
</script>

<style type="text/css">
td {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
}

a.linkMenuTop {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#FF6600
}
</style>


</head>

<body bgcolor="#ffffff" bgproperties="fixed" link="#000000" vlink="#000000" alink="#000000" text="#000000" topmargin="0" leftmargin="0">

<table border="0" cellspacing="0" cellpadding="0" width='780'>
<tr>
	<td>
		<table border="0" cellspacing="0" cellpadding="0" width='100%'>
		<tr>
			<td>
				<table border="0" cellspacing="0" cellpadding="0" width='100%'>
				<tr>
					<td height='70'><img src='imagens/logo_telecontrol.gif' border='0'></td>
					<td width='150'>&middot; <a href="index.php" class='linkMenuTop'><B>Home/Produtos</B></a></td>
					<td width='80'>&middot; <a href="pedido.php" class='linkMenuTop'><B>Pedido</B></a></td>
<!-- 
					<td width='90'>&middot; <a href="cadastro.php" class='linkMenuTop'><B>Cadastro</B></a></td>
					<td width='70'>&middot; <a href="login.php" class='linkMenuTop'><B>Login</B></a></td>
 -->
				</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor="#f5f5f5">
			<td align='right' height='30'>
<?
if (strlen($cookie_login['cook_pedido']) > 0){
	$sql = "SELECT COUNT(*) AS total_itens, SUM (qtde * preco) as valor_pedido FROM tbl_pedido_item WHERE pedido = ".$cookie_login['cook_pedido'];
	$res = pg_exec($con,$sql);

	$total_pedido_cabecalho = pg_result($res,0,1) + $cookie_login['valor_cep'];

	if (pg_numrows($res) > 0) echo "Pedido com <B>".pg_result($res,0,0)." item(ns)</B>. Valor total R$ <B>".number_format($total_pedido_cabecalho,2,",",".")."</B>";
	else                      echo "<B>Pedido vazio</B>.";
}else{
	echo "<B>Pedido vazio</B>.";
}
?>
			</td>
		</tr>
<!-- CABECALHO -->
