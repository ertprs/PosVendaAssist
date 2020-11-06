<style>
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
</style>
<?
echo "<table width='700' border='0' align='center' cellpadding='0' cellspacing='0' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";
echo "<tr height='30'>";
echo "<td height='30' align='left'  align='center' class='Conteudo' bgcolor='#e6eef7' style='font-family: Verdana,Arial;'>";
	echo "<table height='100%' cellspacing='0' cellpadding='5'>";
	echo "<tr>";
	echo "<td style='background-color:#e6eef7 ' class='Conteudo' onmouseover=\"this.style.backgroundColor='#D2DFF2';this.style.cursor='hand';\" onmouseout=\"this.style.backgroundColor='#e6eef7';\" bgcolor='#ffcece'> <a href='loja_completa.php'>Lista de Peças</a></td>";
	echo "<td> | </td>";
	echo "<td style='background-color:#e6eef7 ' class='Conteudo' onmouseover=\"this.style.backgroundColor='#D2DFF2';this.style.cursor='hand';\" onmouseout=\"this.style.backgroundColor='#e6eef7';\" bgcolor='#ffcece'> <a href='loja_carrinho.php'>Meu carrinho de compras</a> </td>";
	echo "</tr>";
	echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table>";
?>