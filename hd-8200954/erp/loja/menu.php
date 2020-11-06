<?
include 'dbconfig.php';
include 'dbconnect-inc.php';
include 'configuracao.php';

echo "<table width='182' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
	echo "<td colspan='3' width='182'  height='13'><IMG SRC='menu1.jpg' width='182'  height='13'>";
	echo "</td>";
echo "</tr>";
echo "<tr><td colspan='3'><a href='carrinho.php'><img src='icone_shop.gif'border='0'align='middle'> <B>Carrinho</B> </a></td></tr>";
/*echo "<tr>";
	echo "<td colspan='3'  width='182' bgcolor='#f6f6f6'><BR>&nbsp;&nbsp;<B>Area do Cliente</B>
	<BR>&nbsp;&nbsp;<a href='cadastro.php'>Cadastro</a>
	<BR>&nbsp;&nbsp;<a href='index.php'>Destaque</a>
	<BR>&nbsp;&nbsp;<a href='index.php'>Promoções</a>";
echo "</td>";
echo "</tr>";*/
echo "<tr>";
	echo "<td colspan='2'  width='182' bgcolor='#f6f6f6'>&nbsp;";
	echo "</td>";
	echo "<td width='11'  height='15'><IMG SRC='menu2.jpg' width='11'  height='15'>";
	echo "</td>";
echo "</tr>";
echo "<tr width='182' height='42'>";
	echo "<td width='9'  height='42'><IMG SRC='menu_cat_1.jpg' width='10'  height='42'>";
	echo "</td>";
	echo "<td width='162' background='menu_cat_2.jpg' align='center'> <font size='2' color='#ffffff'>Categorias</font>";
	echo "</td>";
	echo "<td width='11'  height='42'><IMG SRC='menu_cat_3.jpg' width='11'  height='42'>";
	echo "</td>";
echo "</tr>";

$sql = "select familia, descricao FROM tbl_familia where fabrica=$login_empresa AND linha=414";
$res = pg_exec ($con,$sql);
for ($i = 0 ; $i < pg_numrows($res); $i++){
		$familia         = trim(pg_result ($res,$i,familia));
		$descricao_menu  = trim(pg_result ($res,$i,descricao));
		$cor='#eceae6';
		if($i%2==0) $cor='#f6f6f6';

	echo "<tr width='182'>";
	echo "<td width='182' height='23' colspan='3' bgcolor='$cor'>";
	echo "&nbsp;&nbsp;<a href='categoria.php?cat=$familia'>$descricao_menu</A>";
	echo "</td>";
	echo "</tr>";
}

echo "<tr width='182'>";
	echo "<td colspan='3' width='182'  height='13'><IMG SRC='menu_cat_baixo.jpg' width='182'  height='13'>";
	echo "</td>";
echo "</tr>";
echo "</table>";
?>
