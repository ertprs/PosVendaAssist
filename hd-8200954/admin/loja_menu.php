<script src="../js/jquery-1.1.2.js"          type="text/javascript"></script>

<link rel="stylesheet" href="../js/jquery.tooltip.css" />
<script src="../js/jquery.bgiframe.js"          type="text/javascript"></script>
<script src="../js/jquery.dimensions.tootip.js" type="text/javascript"></script>
<script src="../js/chili-1.7.pack.js"           type="text/javascript"></script>
<script src="../js/jquery.tooltip.js"           type="text/javascript"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<script type="text/javascript" src="../js/excanvas.js"></script>
<script type="text/javascript" src="../js/jquery.corner.js"></script>

<script type="text/javascript">
	$(function() {
		$("a[@rel='regra']").Tooltip({
			track: true,
			delay: 0,
			showURL: false,
			opacity: 0.85,
			fixPNG: true,
			showBody: " - ",
			extraClass: "regra"
		});
		$('div.top').corner();
//		$("div[@rel='box_content']").corner("20px tr bl");
	});
</script>

<style>
.Titutlo2{
	font-family: Arial;
	font-size: 12px;
	font-weight:bold;
	color: #333;
}
.Titulo{
	font-family: Arial;
	font-size: 14px;
	font-weight:bold;
	color: #333;
}
.Conteudo{
	font-family: Arial;
	font-size: 11px;
	color: #333333;
}

.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Erro{
	font-family: Verdana;
	font-size: 14px;
	color:#FFF;
	border:#485989 1px solid; 
	background-color: #990000;
}

.contenedorfoto {
    float:left;
    width:110px;
    height:100px;
    margin:3px;
    padding:5px;
    background-color:#f5f7f9;
    border-right: #a5a7aa solid 1px;
    border-bottom: #a5a7aa solid 1px;
    text-align:center;
}

.contenedorfoto a {
    text-decoration: none;
}

.contenedorfoto span {
    color:#515151;
    font-family: Trebuchet MS;
    font-size: 9pt;
}
.content_box {
	float: left;
	width: 155px;
	height: 175px;
	margin: 10px 10px 10px 5px;
	padding: 15px;
	/*border: 2px solid Black;*/
	background: #E0E8F1;
	color: #000;
	font-size: 12px;
}

#menuver {
	width: 180px;
	padding: 0; 
	margin: 0;
	font: 10px Verdana, sans-serif;
	background: #DFE7F2; 
	border-top: 3px solid #B5CDE8; 
	border-bottom: 3px solid #B5CDE8;
	font-weight: normal;
}
#menuver ul{
	margin:0;padding:0;
}
#menuver li {
	list-style: none;
	color: #3A6FC5;
	margin: 0.5em 0 0.5em 0.5em; 
	font-weight: normal;
}
#menuver li a {
	margin:0; 
	padding:0;
	text-decoration:none;
	color: #3A6FC5;
	font-weight: normal;
}
#menuver li a:visited {
	margin:0; 
	padding:0;
	color: #3A6FC5;
	font-weight: normal;
}
#menuver li a:hover { 
	margin:0; 
	padding:0;
	background: #DFE7F2;
	color: #223384;
	font-weight: bold;
}
#menuver li a:active { 
	margin:0; 
	padding:0;
	background: #D8E4F3;
	color: #3A6FC5; 
	font-weight: normal;
}

b, strong {
   text-transform: uppercase;
}


</style>
<?
echo "<table width='98%' border='0' align='center' cellpadding='0' cellspacing='3' bgcolor='#e6eef7' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";
echo "<tr >";
echo "<td align='left'  align='center' class='Conteudo'>";
	echo "<table cellspacing='4' cellpadding='0' border='0'>";
	echo "<tr>";
	echo "<td style='background-color:#e6eef7 ' class='Conteudo' onmouseover=\"this.style.backgroundColor='#D2DFF2';this.style.cursor='hand';\" onmouseout=\"this.style.backgroundColor='#e6eef7';\" bgcolor='#ffcece'>";
		echo " &nbsp;&nbsp;<a href='loja_completa.php'><font color='#494949'>Lista de Peças</font></a> ";
	echo "</td>";

	echo "</tr>";
	echo "</table>";
echo "</td>";
echo "<form method='POST' name=\"Pesquisar\" action=\"loja_completa.php\">";
echo "<td align='right'>";
	echo "<table height='100%' cellspacing='0' cellpadding='3' border='0'>";
	echo "<tr>";
	echo "<td  bgcolor='#e6eef7' class='Conteudo'>";
		echo "<strong><font color='#494949'>Busca: </font></strong>";
		echo "&nbsp;&nbsp;<input name=\"busca\" size=\"15\" value=\"$busca\" title=\"Pesquisar\" type=\"text\" 	>";
		echo "&nbsp;&nbsp;<input name=\"btnG\" value=\"OK\" type=\"submit\">";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
echo "</td>";
echo "</form>";
echo "</tr>";
echo "</table>";
?>