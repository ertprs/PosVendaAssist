<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


$os = $HTTP_GET_VARS['os_revenda'];

$title = "Ordem de Serviço Revenda - Impressão de Etiquetas";

?>

<html>
<head>
	<title><? echo $title ?></title>
	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">
	<link type="text/css" rel="stylesheet" href="css/css_press.css">
</head>

<style type="text/css">

body {
	margin: 0px;
}

.borda {
	border: solid 1px #c0c0c0;
}

.etiqueta {
	width: 110px;
	font:50% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	text-align: center
}

</style>

<body>

<?
#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os_revenda.sua_os     ,
					tbl_cliente.nome          ,
					tbl_cliente.fone          ,
					tbl_os_revenda_item.serie ,
					tbl_produto.referencia    ,
					tbl_produto.descricao     
			FROM    tbl_os_revenda
			JOIN    tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
			JOIN    tbl_cliente USING (cliente)
			JOIN    tbl_produto USING (produto)
			WHERE   tbl_os_revenda.os_revenda = $os
			AND     tbl_os_revenda.fabrica    = $login_fabrica";
echo $sql;
	$res = pg_exec ($con,$sql);
}

if (pg_numrows ($res) > 0) {

	for($i=0; $i < pg_numrows($res); $i++){
		$sua_os				= pg_result ($res,$i,sua_os);
		$referencia			= pg_result ($res,$i,referencia);
		$descricao			= pg_result ($res,$i,descricao);
		$serie				= pg_result ($res,$i,serie);
		$consumidor_nome	= pg_result ($res,$i,nome);
		$consumidor_fone	= pg_result ($res,$i,fone);
?>
<TABLE width="650px" border="1" cellspacing="2" cellpadding="0">
<TR>
	<TD class="etiqueta" align="center">
		<? echo "<b>OS n. ".$sua_os." - Ref. ".$referencia."</b> <br> ".$descricao."<br>N.Série ".$serie."<br>".$consumidor_nome."<br>".$consumidor_fone ?>
	</TD>
	<TD class="etiqueta" align="center">
		<? echo "<b>OS n. ".$sua_os." - Ref. ".$referencia."</b> <br> ".$descricao."<br>N.Série ".$serie."<br>".$consumidor_nome."<br>".$consumidor_fone ?>
	</TD>
	<TD class="etiqueta" align="center">
		<? echo "<b>OS n. ".$sua_os." - Ref. ".$referencia."</b> <br> ".$descricao."<br>N.Série ".$serie."<br>".$consumidor_nome."<br>".$consumidor_fone ?>
	</TD>
	<TD class="etiqueta" align="center">
		<? echo "<b>OS n. ".$sua_os." - Ref. ".$referencia."</b> <br> ".$descricao."<br>N.Série ".$serie."<br>".$consumidor_nome."<br>".$consumidor_fone ?>
	</TD>
	<TD class="etiqueta" align="center">
		<? echo "<b>OS n. ".$sua_os." - Ref. ".$referencia."</b> <br> ".$descricao."<br>N.Série ".$serie."<br>".$consumidor_nome."<br>".$consumidor_fone ?>
	</TD>
</TR>
</TABLE>
<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<?
	} // fim do for
}
?>



<script language="JavaScript">
	window.print();
</script>


</BODY>
</html>