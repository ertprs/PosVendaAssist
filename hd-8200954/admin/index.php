<?php
header('Location: menu_cadastro.php');

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
?>
<html>
<head>
	<title>_.·´¯) Assistência Técnica - Área Administratica (¯`·._</title>
	<META HTTP-EQUIV="Expires" CONTENT="0">
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache, public">
	<META HTTP-EQUIV="Cache-control" CONTENT="no-cache, public, must-revalidate, post-check=0, pre-check=0">
</head>
<body>
	<frameset rows="50,*" frameborder="0" border="0" framespacing="0">
	  <frame name="superior" src="cabecalho.php" scrolling="auto" noresize marginwidth="0" marginheight="0">
	  <frame name="inferior" src="menu-os.php"   scrolling="auto" noresize marginwidth="0" marginheight="0">
	</frameset>
</body>
</html>
