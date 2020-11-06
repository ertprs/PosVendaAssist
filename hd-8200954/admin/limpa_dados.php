<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'funcoes.php';
$admin_privilegios="gerencia";
include "autentica_admin.php";


$layout_menu = "gerencia";
$title = traduz("DADOS DO POSTO DE TESTE");

include "cabecalho.php";

if(isset($_POST["acao"])){
	$res = @pg_exec($con,"BEGIN TRANSACTION");
	$sql = "select fn_limpa_posto(6359,$login_fabrica);";
	$res = @pg_exec($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if (strlen($msg_erro)>0) $res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	else                     $res = @pg_exec ($con,"COMMIT TRANSACTION");
}

?>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:14px Arial;
}


table.tabela tr td{
font-family: verdana;
font-size: 12px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>

<?
$sql = "SELECT codigo_posto,nome,cnpj 
	FROM tbl_posto
	JOIN tbl_posto_fabrica USING(posto)
	WHERE fabrica = $login_fabrica
	AND   posto   = 6359";
		$res = pg_exec($con,$sql);
if (pg_numrows($res) == 1) {
	$codigo_posto    = pg_result($res,0,codigo_posto);
	$cnpj            = pg_result($res,0,cnpj);
	$nome            = pg_result($res,0,nome);
}
?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="acao" value='ok'>
<table width="700" border="0" cellspacing="0" cellpadding="2" align="center" class="formulario">
	<tr class="titulo_tabela">
		<td colspan="4"><?php echo traduz("Limpar Área de Teste");?></td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="2"><?php echo traduz("Código"); ?>: <b><?=$codigo_posto?></b>&nbsp;&nbsp;&nbsp;&nbsp; <?php echo traduz("Nome");?>: <b><?=$nome?></b></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4"><input type='button' name='limpar' value='Limpar todas informações' onclick="javascript:
			if (confirm ('<?php echo traduz("Deseja mesmo excluir os pedidos e as OS do posto:");?>' + '<? echo "$codigo_posto - $nome";?>') == true ) {document.frm_relatorio.submit();
			} "></td>
	</tr>
</table>
</form>

<br>

<?
echo "<table align='center'>";
echo "<tr>";
echo "<td valign='top'>";

$sql = "SELECT  count(*)                        AS total    ,
		to_char(data_digitacao,'MM/YY') AS mes_ano  ,
		to_char(data_digitacao,'YY-MM') AS ordenador
	FROM tbl_os
	WHERE posto   = 6359
	AND   fabrica = $login_fabrica
	GROUP BY mes_ano,
		 ordenador
	ORDER BY ordenador;";
$res = pg_exec($con,$sql);
$total_geral = 0;

if (pg_numrows($res) > 0) {
	echo "<table border='0' cellspacing='1' cellpadding='2' width='350' class='tabela'>";
	echo "<tr class='titulo_tabela'><td colspan='2'>" . traduz("OS digitadas") . "</td></tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td>" . traduz("Mês") . "</td>";
	echo "<td>" . traduz("Total") . "</td>";
	echo "</tr>";


	echo "<tbody>";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$mes_ano    = pg_result($res,$i,mes_ano);
		$total      = pg_result($res,$i,total);

		$total_geral += $total;
		if ($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		echo "<tr bgcolor='$cor'>";
		echo "<td>$mes_ano</td>";
		echo "<td>$total</td>";
		echo "</tr>";

	}
	echo "</tbody>";

	echo "<tfoot>";
	echo "<tr bgcolor='#596d9b'>";
	echo "<td>" . traduz("Total") . "</td>";
	echo "<td>$total_geral</td>";
	echo "</tr>";
	echo "</tfoot>";

	echo "</table>";
}else{
	echo traduz("Nenhuma OS no sistema");
}

echo "</td>";
echo "<td valign='top'>";

$sql = "SELECT  count(*)              AS total    ,
		to_char(data,'MM/YY') AS mes_ano  ,
		to_char(data,'YY-MM') AS ordenador
	FROM tbl_pedido
	WHERE posto   = 6359
	AND   fabrica = $login_fabrica
	GROUP BY mes_ano,
		 ordenador
	ORDER BY ordenador;";
$res = pg_exec($con,$sql);
$total_geral = 0;
if (pg_numrows($res) > 0) {
 	echo "<table border='0' cellspacing='1' cellpadding='2' width='350' class='tabela'>";
	echo "<tr class='titulo_tabela'><td colspan='2'>" . traduz("Pedido Digitado") . "</td></tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td>" . traduz("Mês") . "</td>";
	echo "<td>" . traduz("Total") . "</td>";
	echo "</tr>";


	echo "<tbody>";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$mes_ano    = pg_result($res,$i,mes_ano);
		$total      = pg_result($res,$i,total);

		$total_geral += $total;
		if ($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		echo "<tr bgcolor='$cor'>";
		echo "<td>$mes_ano</td>";
		echo "<td>$total</td>";
		echo "</tr>";

	}
	echo "</tbody>";

	echo "<tfoot>";
	echo "<tr bgcolor='#596d9b'>";
	echo "<td>" . traduz("Total") . "</td>";
	echo "<td>$total_geral</td>";
	echo "</tr>";
	echo "</tfoot>";

	echo "</table>";
}else{
	echo traduz("Nenhum pedido no sistema");
}
echo "</td>";
echo "</tr>";
echo "</table>";
echo "<br>";

include "rodape.php";
?>
