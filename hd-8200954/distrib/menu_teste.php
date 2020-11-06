

<style>

body {
	text-align: center;
	font-family: Arial, Helvetica, sans-serif;
	margin: 0px;
	margin-top:5px;
	padding: 0px;

}
a{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
 	text-decoration: none;
	color: #000099;
	font-weight: bold;
}
a:hover{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #0066ff;
	font-weight: bold;
}

td,th {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
}
h1{
    font-family: Arial, Helvetica, sans-serif;
    background-color: #0099CC;
    color: #FFFFFF
	/*#FFCC00*/

}
h6{
    font-family: Arial, Helvetica, sans-serif;
    background-color: #FFCC00;
    color: #FFFFFF

}
form{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
}


.frm {
	BORDER-RIGHT: #888888 1px solid;
	BORDER-TOP: #888888 1px solid;
	FONT-WEIGHT: bold;
	FONT-SIZE: 8pt;
	BORDER-LEFT: #888888 1px solid;
	BORDER-BOTTOM: #888888 1px solid;
	FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif;
	BACKGROUND-COLOR: #f0f0f0
}
.frm-on {
	BORDER-RIGHT: rgb(70,90,128) 1px solid;
	BORDER-TOP: rgb(70,90,128) 1px solid;
	FONT-WEIGHT: bold;
	FONT-SIZE: 8pt;
	BORDER-LEFT: rgb(70,90,128) 1px solid;
	COLOR: rgb(70,90,128);
	BORDER-BOTTOM: rgb(70,90,128) 1px solid;
	FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif;
	BACKGROUND-COLOR: #FFDF5E
}
.pontilhado{
	margin-bottom: 5px;
	margin-right:  5px;
	margin-left:   5px;
	padding:      10px;
	voice-family: "\"}\"";
	voice-family:inherit;
	border: 1px dotted silver;
}

.vermelho{
    font-family: Arial, Helvetica, sans-serif;
    color:#ff0000;
}

		#menu ul {
			padding:0px;
			margin:0px;
			float: left;
			width: 100%;
			background-color:#EDEDED;
			list-style:none;
			font:80% Tahoma;
		}

		#menu ul li {display: inline; }

		#menu ul li a {
			width:15.6%;
			background-color:#EDEDED;
			color: #333;
			text-decoration: none;
			padding: 2px 5px;
			float:left;
		}

		#menu ul li a:hover {
			background-color:#0099CC;
			color: #fff;
		}

#Cabeca{
	font-family: Verdana, sans-serif;
	font-size: 14pt;
	color: #FFFFFF;
	font-weight: bold;
}
</style>

<?
	if(strlen($login_unico)>0 ){
	echo "<table width='100%' id='Cabeca' cellspacing='0'><tr><td align='left' bgcolor='#879BC0'>&nbsp;DISTRIB - LOGIN ÚNICO</td> <td align='right' bgcolor='#879BC0'>$login_unico_nome | $login_unico_email | <a href='http://www.telecontrol.com.br'>Sair</a>&nbsp;&nbsp;</td></tr></table>";
}
?>

	<div id="menu">
		<ul>
			<li><a href="index.php">MENU</a></li>
			<li><a href="../login_unico.php">LOGIN ÚNICO</a></li>
			<li><a href="estoque_consulta.php">CONSULTA ESTOQUE</a></li>
			<li><a href="embarque_geral_conferencia_novo.php">CONFERENCIA</a></li>
			<li><a href="nf_entrada.php">NF ENTRADA</a></li>
			<!--<li><a href="embarque.php">EMBARQUE</a></li> HD 7889 -->
			<li><a href="pendencia_posto.php">PENDENCIA POSTO</a></li>
		</ul>
	</div>
<?
$sql = "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
$res = pg_exec ($con,$sql);
echo "<center><font size='-2'>" . pg_result ($res,0,nome) . "</font></center>";
?>

