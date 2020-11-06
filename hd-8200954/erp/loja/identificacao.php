<?
include 'dbconfig.php';
include 'dbconnect-inc.php';

if ($_COOKIE['cook_login_posto']){
	header("Location:finaliza.php");
}

include "topo.php";

echo "<table width='750' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td width='182' valign='top'>";
include "menu.php";
echo "<BR>";
echo "</td>";
echo "<td width='568' align='right' valign='top'>";

	echo "<BR>";
	echo "<table border='0'>";
	echo "<form action='login.php' method='post' name='frmlogin'>";
	echo "<tr><td><b>Identificação do Comprador</b></td>";
	echo "<tr><td rowspan='3'align='justify' valign='top'><FONT SIZE='1' COLOR=''>Se você é um usuario do sistema ASSIST da TELECONTROL, basta logar com seu usuário senha que ultilizam no sistema para poder efetuar as compras</font></td><td rowspan='3'align='justify' bgcolor='#999999'></td>";
	echo "<td align='right'><FONT SIZE='1' COLOR=''>Usuário</FONT></td>";
	echo "<td><INPUT TYPE='text' NAME='usuario'></td></tr>";
	echo "<tr>";
	echo "<td align='right'><FONT SIZE='1' COLOR=''>Senha</FONT></td>";
	echo "<td><INPUT TYPE='password' name='senha'></td>";
	echo "</tr>";
	echo "<tr><td colspan='2' align='right'><INPUT TYPE='submit' value='OK'></td></tr>";
	echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table>";
?>