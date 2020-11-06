<head>
<title><?=$title?></title>
</head>
<style type="text/css">
input {
	font:verdana;
	BORDER-RIGHT: #000000 1px solid; 
	BORDER-TOP: #000000 1px solid; 
	FONT-WEIGHT: bold; 
	FONT-SIZE: 8pt; 
	BORDER-LEFT: #000000 1px solid; 
	BORDER-BOTTOM: #000000 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
a:link {
	color: #555555;
	font:Arial;
	text-decoration: none;
}

a:visited {
	color: #555555;
	text-decoration: none;
}

a:hover {
	color: #000000;
	text-decoration: none;
}

a:active {
	color: #555555;
	text-decoration: none;
}
.destaque:link{
	
	color:#3399FF;
}
.Tabela{
	border:1px solid #d2e4fc;
	background-color:#485989;
	}
.titulo {
	text-align: right;
	color: #000000;
	background: #ced7e7;
}

.conteudo {
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}
.inicio {
	font-weight: bold;
	text-align: left;
	color: #FFFFFF;
}
</style>
<BODY TOPMARGIN=0>
<?
/*include 'dbconfig.php';
include 'dbconnect-inc.php';
include 'configuracao.php';*/

echo "<table width='750' border='0' align='center' cellpadding='0' cellspacing='0'>";
echo "<tr>";
	echo "<td><IMG SRC='topo1.jpg'>";
	echo "</td>";
	echo "<td><IMG SRC='topo2.jpg'>";
	echo "</td>";
	echo "<td><IMG SRC='topo3.jpg'>";
	echo "</td>";
echo "</tr>";
echo "</table>";

echo "<table width='750' height='38' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";

	echo "<td width='10' height='38'><IMG SRC='topob1.jpg'>";
	echo "</td>";
	echo "<td width='162' valign='middle' height='38' background='topob2.jpg' bgcolor='#dddee3'>Procurar: <input type='text' size='5' maxlength='20' name='busca' value=''> <input type='submit' name='btn_buscar' value='Ok' class='botao'>";
	echo "</td>";
	echo "<td height='38' background='topob2.jpg'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<a href='index.php' class='destaque'>Home</A>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<a href='empresa.php'>Empresa</A>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;
	<a href='cadastro.php'>Cadastro</A>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;
	<a href='index.php'>Promoções</A>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;
	<a href='#.php'>Fale Conosco</A>";
	echo "</td>";
	echo "<td width='13' height='38'><IMG SRC='topob3.jpg'>";
	echo "</td>";
echo "</tr>";
echo "</table>";


?>