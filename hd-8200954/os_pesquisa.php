<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>

<head>
	<title> Pesquisa Ordem de Servi�o </title>
	<meta http-equiv=pragma content=no-cache>
	<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_os_400.gif">

<div id="container" style="width: 484px; background-color: #D0D0D0">
		<h1>Criterios de pesquisa para OS.</h1>
	<div id="contentcenter">
		<div id="contentleft" style="width: 484px; text-align: left;">
			<INPUT TYPE="radio" NAME="">OS Lan�adas Hoje
		</div>
		<div id="contentleft" style="width: 484px; text-align: left;">
			<INPUT TYPE="radio" NAME="">OS Lan�adas Ontem
		</div>
		<div id="contentleft" style="width: 484px; text-align: left;">
			<INPUT TYPE="radio" NAME="">OS Lan�adas Nesta Semana
		</div>
		<div id="contentleft" style="width: 484px; text-align: left;">
			<INPUT TYPE="radio" NAME="">OS Lan�adas Neste M�s
		</div>
		<div id="contentleft" style="width: 484px; text-align: left;">
			<INPUT TYPE="radio" NAME="">OS Lan�adas Nesta M�s pelo Posto <INPUT TYPE="text" NAME=""> (c�digo)
		</div>
		<hr>
	</div>
	<h1>Pesquisa por Per�odos.</h1>
	<div id="contentcenter">
		<div id="contentleft" style="width: 484px; text-align: left;">
Campos com data Inicial e Final para pesquisar OS e campo do codigo do Posto para pesquisa
 
Pesquisar pelo numero de serie do aparelho
 
Pesquisar pelo nome ou CPF do consumidor
 
Pesquisar pelo numero da OS
 
Pesquisar pelo modelo do aparelho
 
Pesquisar pela Cidade/Estado
 
Pesquisar pela NF de Compra
 
 
 


	</div>
</div>



</body>
</html>