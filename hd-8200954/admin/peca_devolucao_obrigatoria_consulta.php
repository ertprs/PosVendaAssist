<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$title = "Relação de Peças com Devolução Obrigatória";
include 'cabecalho.php';
?>
<style type="text/css">

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

</style>

<?
$sql = "SELECT  peca                  ,
				referencia            ,
				descricao             ,
				origem                ,
				unidade               ,
				peso                  ,
				garantia_diferenciada ,
				item_aparencia        
		FROM	tbl_peca
		WHERE	fabrica               = $login_fabrica
		AND		devolucao_obrigatoria IS true ";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0){
	echo "<table width='700' align='center' border='0' class='conteudo' cellpadding='0' cellspacing='1'>";
	echo "<tr class='menu_top' height='20'>";
	echo "<td align='center'><b>Referência</b></td>";
	echo "<td align='center'><b>Descrição</b></td>";
	echo "<td align='center'><b>Origem</b></td>";
	echo "<td align='center'><b>Unid</b></td>";
	echo "<td align='center'><b>Peso</b></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$bg = ($i%2 == 0) ? '#fbfbfb' : '#FFFFFF';
		echo "<tr class='table_line' height='18'>";
		echo "<td align='left' bgcolor='$bg'>".pg_result ($res,$i,referencia)."</td>";
		echo "<td align='left' bgcolor='$bg'>".pg_result ($res,$i,descricao)."</td>";
		echo "<td align='center' bgcolor='$bg'>".pg_result ($res,$i,origem)."</td>";
		echo "<td align='center' bgcolor='$bg'>".pg_result ($res,$i,unidade)."</td>";
		echo "<td align='right' bgcolor='$bg' style='padding-right:5px'>".pg_result ($res,$i,peso)."</td>";
		echo "</tr>";
	}
}

echo "</table>";

?>

<p>

<? include "rodape.php"; ?>
