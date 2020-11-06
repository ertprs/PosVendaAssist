<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";
?>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff;
}
</style>

<?
$title = "TABELA DE MÃO DE OBRA E PRAZO DE GARANTIA";
$layout_menu = "tecnica";

include 'cabecalho.php';

###CARREGA AS LINHAS

$cond_1=" AND tbl_produto.ativo IS TRUE ";
$ordenacao = " ORDER BY tbl_produto.referencia ";


###CARREGA OD PRODUTOS DA LINHA
if(strlen($linhas) == 0){

	$cond_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_linha.fabrica IN (11,172) " : " tbl_linha.fabrica = $login_fabrica ";

	$sql = "SELECT    tbl_linha.nome AS nome_linha ,
					  tbl_produto.produto          ,
					  tbl_produto.referencia       ,
					  tbl_produto.descricao        ,
					  tbl_produto.garantia         ,
					  tbl_produto.ativo            ,
					  tbl_produto.mao_de_obra
			FROM      tbl_produto
			LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE     {$cond_fabrica} 
			$cond_1
			$ordenacao;";
	$resZ = @pg_exec ($con,$sql);

	$titulo = "Todas as Linhas e Famílias";
}

if(strlen($familia) > 0 OR strlen($linha) > 0 OR strlen($linhas) == 0){
	if(@pg_numrows($resZ) > 0){

		echo "<TABLE width = '650' align = 'center' border = '0' cellspacing='1' cellpadding='1' class='tabela_resultado>";
		echo "<thead>";
		echo "<TR>";
		echo "<TD class='menu_top' colspan='5'>&nbsp;</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='menu_top'>Referência</TD>";
		echo "<TD class='menu_top'>Descrição</TD>";
		echo "<TD class='menu_top'>Garantia</TD>";
		echo "<TD class='menu_top'>MÃO-DE-OBRA</TD>";
		echo "</TR>";
		echo "</thead>";

		$data = date('Ymd');
		$arquivo_nome     = "linha_X_familia_produto_xls-$login_fabrica-$data.xls";
		$arquivo ="/var/www/assist/www/admin/xls/linha_X_familia_produto_xls-$login_fabrica-$data.xls";
		$fp = fopen($arquivo, "w");

		fputs($fp, "<TABLE width = '650' align = 'center' border = '0' cellspacing='1' cellpadding='1'>");
		fputs($fp, "<TR>");
		fputs($fp, "<TD class='menu_top' colspan='4' align='center' bgcolor='#7B7BF0'>.:: Produtos na $titulo ::.</TD>");
		fputs($fp, "</TR>");
		fputs($fp, "<TR>");
		fputs($fp, "<TD class='menu_top' align='center' bgcolor='#CCCCCC'>Referência</TD>");
		fputs($fp, "<TD class='menu_top' align='center' bgcolor='#CCCCCC'>Descrição</TD>");
		fputs($fp, "<TD class='menu_top' align='center' bgcolor='#CCCCCC'>Garantia</TD>");
		fputs($fp, "<TD class='menu_top' align='center' bgcolor='#CCCCCC'>MÃO-DE-OBRA</TD>");
		fputs($fp, "</TR>");

		echo "<tbody>";
		for ($i = 0 ; $i < @pg_numrows($resZ) ; $i++){
			$produto           = trim(@pg_result($resZ,$i,produto));
			$referencia        = trim(@pg_result($resZ,$i,referencia));
			$descricao         = trim(@pg_result($resZ,$i,descricao));
			$garantia          = trim(@pg_result($resZ,$i,garantia));
			$mao_de_obra       = trim(@pg_result($resZ,$i,mao_de_obra));



			echo "<TR rel='$bolinha'>";
			echo "<TD align='left'><font size='1'>$referencia</font></TD>";
			echo "<TD align='left'><font size='1'>$descricao</font></TD>";
			echo "<TD align='center'><font size='1'>$garantia meses</font></TD>";
			echo "<TD align='right'><font size='1' ";
			if($mao_de_obra <= 0) echo "color=#ff0000";
			echo ">".number_format($mao_de_obra,2,",",".")."</font></TD>";
			echo "</TR>";
			fputs($fp, "<TR>");
			fputs($fp, "<TD align='left' bgcolor='#FFFFFF'><font size='1'>$referencia</font></TD>");
			fputs($fp, "<TD align='left' bgcolor='#FFFFFF'><font size='1'><a href='produto_consulta.php?produto=$produto'>$descricao</a></font></TD>");
			fputs($fp, "<TD align='center' bgcolor='#FFFFFF'><font size='1'>$garantia meses</font></TD>");
			fputs($fp, "<TD align='right' bgcolor='#FFFFFF'><font size='1' ");
			if($mao_de_obra <= 0) fputs($fp, "color=#ff0000");
			fputs($fp, ">".number_format($mao_de_obra,2,",",".")."</font></TD>");
			fputs($fp, "</TR>");
		}
		echo "</tbody>";
		echo "</TABLE>";
		fputs($fp, "</TABLE>");
		if (isFabrica(11)) {
			echo "<br/>";
		}
	}else{
		echo "<font size='2' face='verdana' color='#63798D'><b>PRODUTOS NÃO CADASTRADOS</b></font>";
	}
}
include("rodape.php");
?>
