<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COME�A O HTML DO MENU -->

<head>

	<title>Cobran�a</title>

		<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
		<meta http-equiv="Expires"       content="0">
		<meta http-equiv="Pragma"        content="no-cache, public">

		<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
		<meta name      ="Author"        content="Telecontrol Networking Ltda">
		<meta name      ="Generator"     content="na m�o...">
		<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assist�ncia T�cnica e Fabricantes.">
		<meta name      ="KeyWords"      content="Assist�ncia T�cnica, Postos, Manuten��o, Internet, Webdesign, Or�amento, Comercial, J�ias, Callcenter">

		<meta name="robots" content="noindex, nofollow">
		<link href="../sample.css" rel="stylesheet" type="text/css" >
	</head>
	<body>


		
<?php

if ( isset( $_POST ) )
   $postArray = &$_POST ;			// 4.1.0 or later, use $_POST
else
   $postArray = &$HTTP_POST_VARS ;	// prior to 4.1.0, use HTTP_POST_VARS

foreach ( $postArray as $sForm => $value )
{
	if ( get_magic_quotes_gpc() )
		$postedValue = htmlspecialchars( stripslashes( $value ) ) ;
	else
		$postedValue = htmlspecialchars( $value ) ;

}
$texto= str_replace ('\"',"'",$FCKeditor1);


$destinatario = "$email";
$assunto = "$assunto";
$mensagem = "<html xmlns=http://www.w3.org/1999/xhtml><head><title>Cobran�a Brit�nia</title>
		<meta http-equiv=content-Type  content=text/html; charset=iso-8859-1>
		<meta http-equiv=Expires       content=0>
		<meta http-equiv=Pragma        content=no-cache, public>
		<meta http-equiv=Cache-control content=no-cache, public, must-revalidate, post-check=0, pre-check=0>
		<meta name      =Author        content=Telecontrol Networking Ltda>
		<meta name      =Generator     content=na m�o...>
		<meta name      =Description   content=Sistema de gerenciamento para Postos de Assist�ncia T�cnica e Fabricantes.>
		<meta name      =KeyWords      content=Assist�ncia T�cnica, Postos, Manuten��o, Internet, Webdesign, Or�amento, Comercial, J�ias, Callcenter>
		<meta name=robots content=noindex, nofollow>
	</head>
	<body>$texto</body>
</html>
";


$header .= "Content-type: text/html; charset=iso-8859-1\n"; 
$header .= "From: $from \n";
if ($destinatario==""){
echo "<h2><br><br>&nbsp;&nbsp;&nbsp;O campo E-mail deve ser preenchido<br><br>&nbsp;&nbsp;&nbsp;<a href='javascript:history.back()'>voltar</a></h2>";
}else{
mail($destinatario, $assunto, $mensagem, $header);
?>
<h2><br><br>&nbsp;&nbsp;&nbsp;E-mail enviado com sucesso<br><br>
&nbsp;&nbsp;&nbsp;<a href="../../../cobranca_tela.php?posto=<?=$posto?>">Voltar para a tela de cobran�a do posto</a></h2>
<?
}
?>
	</body>
</html>
