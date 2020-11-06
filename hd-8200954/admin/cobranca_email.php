<?php
include("editor_texto/fckeditor.php") ;

$email = $_POST["email"];
$corpo_email = $_POST["corpo_email"];
$posto = $_POST["posto"];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

<head>

	<title>Cobrança</title>

		<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
		<meta http-equiv="Expires"       content="0">
		<meta http-equiv="Pragma"        content="no-cache, public">

		<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
		<meta name      ="Author"        content="Telecontrol Networking Ltda">
		<meta name      ="Generator"     content="na mão...">
		<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
		<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">
		<meta name="robots" content="noindex, nofollow">
		<link href="../sample.css" rel="stylesheet" type="text/css" />
	</head>
	<body><form action="editor_texto/paginas/php/cobranca_envia.php" method="post">
		<TABLE border='0' width='100%'>
		<TR>
			<TD width='70' style="font-size:10px; font-family:Verdana, Arial, Helvetica, sans-serif">&nbsp;E-mail:&nbsp;</TD>
			<TD><input type="text" name="email" value="<?=$email?>" size='50'></TD>
		</TR>
		<TR>
			<TD width='70' style="font-size:10px; font-family:Verdana, Arial, Helvetica, sans-serif">&nbsp;Assunto:&nbsp;</TD>
			<TD>
				<input type="text" name="assunto" value="Relação de notas em aberto" size='50'></TD>
		</TR>
			<TD width='70' style="font-size:10px; font-family:Verdana, Arial, Helvetica, sans-serif">&nbsp;De:&nbsp;</TD>
			<TD>
				<input type="text" name="from" value="sirlei.grubert@britania.com.br;francys.santos@britania.com.br" size='50'><input type="hidden" name="posto" value="<?=$posto?>"></TD>
		</TR>
		<TR>
			<TD colspan='2'>
			<?
			include("editor_texto/paginas/php/cobranca_editor.php") ;
			?></TD>
		</TR>
		<TR>
			<TD colspan='2'><input type="submit" value="Enviar E-mail"></TD>
		</TR>
		</TABLE>

		</form>
	</body>
</html>