<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastro";
include 'autentica_admin.php';
?>
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
	color: #FFFFFF;
	background:#B5CDE8;/**/
}
.Conteudo{
	font-family: Arial;
	font-size: 11px;
	color: #333333;
}
</style>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<?


$title = "Administrador da Loja Vitual";

include "cabecalho.php";

echo "<table width='700' align='center' style=' border:#B5CDE8 1px solid;background:#DFE7F2;' >";
echo "<tr  bgcolor='#596D9B' >";
echo "<td align='left' colspan='4'><font size='2' color='#ffffff'>Administrador da Loja Virtual</font></td>";
echo "</tr>";
echo "<tr>";
echo "<td width='300'>";
	echo "<table style='background:#EEEEEE;' class='Conteudo'>";
	echo "<tr>";
	echo "<td colspan='2' class='Titulo'>Menu</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td><a href='loja_completa.php'><img src='imagens/icone_cadastrar.gif'></a></td>";
	echo "<td align='left'><a href='linha_cadastro.php?semcab=yes&keepThis=true&TB_iframe=true&height=450&width=750' class=\"thickbox\">Linha da Loja Virtual</a></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td><a href='loja_completa.php'><img src='imagens/icone_cadastrar.gif'></a></td>";
	echo "<td align='left'><a href='familia_cadastro.php?semcab=yes&keepThis=true&TB_iframe=true&height=450&width=750' class=\"thickbox\">Família da Loja Virtual</a></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td><a href='#'><img src='imagens/hardware.png'></a></td>";
	echo "<td align='left'><a href='peca_cadastro.php?semcab=yes&keepThis=true&TB_iframe=true&height=450&width=750' class=\"thickbox\">Colocar peça em Promoção na Loja Virtual</a></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td><a href='#'><img src='imagens/sale.png'></a></td>";
	echo "<td align='left'><a href='#'>Preço de peça em Promoção</a></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td><a href='manutencao_valormin.php'><img src='imagens/icone_cadastrar.gif'></a></td>";
	echo "<td align='left'><a href='manutencao_valormin.php?semcab=yes&keepThis=true&TB_iframe=true&height=450&width=700' target='_blank' class=\"thickbox\">Manutenção da Loja</a></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td><a href='loja_completa.php'><img src='imagens/house.png'></a></td>";
	echo "<td align='left'><a href='loja_completa.php?semcab=yes&keepThis=true&TB_iframe=true&height=450&width=750' class=\"thickbox\">Verificar Loja Virtual</a></td>";
	echo "</tr>";

	echo "</table>";
echo "</td>";
echo "<td valign='top'>";
$sql = "SELECT count(*) AS total,
		tbl_linha.linha,
		tbl_linha.nome
	FROM tbl_peca
	JOIN tbl_linha ON tbl_linha.linha = tbl_peca.linha_peca
	WHERE tbl_peca.fabrica = $login_fabrica
	GROUP BY tbl_linha.linha,
		 tbl_linha.nome";
$res = pg_exec ($con,$sql);
if(pg_numrows($res)>0){
	echo "<table  width='100%' class='Conteudo'>";
	echo "<caption><b><font color='#009900'>Produtos/Linha disponíveis na Loja Virtual</b></caption>";
	echo "<thead>";
	echo "<tr>";
	echo "<td><b>Linha</b></td>";
	echo "<td><b>Total de Produtos</b></td>";
	echo "</tr>";

	echo "</thead>";
	echo "<tbody>";
	for($i=0;$i < pg_numrows($res);$i++){
		$linha_total = pg_result($res,$i,total);
		$linha_nome  = pg_result($res,$i,nome);
		$total      += $linha_total;
		echo "<tr>";
		echo "<td>$linha_nome</td>";
		echo "<td>$linha_total</td>";
		echo "</tr>";

	}
	echo "</tbody>";
	echo "<tfoot>";
	echo "<tr>";
	echo "<td><b>TOTAL</b></td>";
	echo "<td><b>$total</b></td>";
	echo "</tr>";
	echo "</tfoot>";
	echo "</table>";
}

echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='2'>Informações";
echo "</td>";
echo "</tr>";
echo "</table>";
include "rodape.php";

?>
