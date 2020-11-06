<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";


$posto        = $_POST['posto'];
$codigo_posto = $_POST['codigo_posto'];
$nome_posto   = $_POST['nome_posto'];
$total        = $_POST['total'];
$emissao      = $_POST['emissao'];
$nota_fiscal  = $_POST['nota_fiscal'];

$faturamento_fatura = $_POST['faturamento_fatura'];

if (strlen ($codigo_posto) > 0) {
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica IN (".implode(",", $fabricas).")";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$posto = pg_result ($res,0,0);
	}
}


if (strlen ($nome_posto) > 0) {
	$sql = "SELECT tbl_posto.posto FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).") WHERE tbl_posto.nome ILIKE '%$nome_posto%'";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$posto = pg_result ($res,0,0);
	}
	if (pg_numrows ($res) > 1) {
		$listar_postos = true;
	}
}

#-------------- Gravando -----------------#
if (strlen ($posto) > 0) {
	$nota_fiscal = str_replace (".","",$nota_fiscal);
	$nota_fiscal = str_replace (" ","",$nota_fiscal);
	$nota_fiscal = str_replace ("-","",$nota_fiscal);
	$nota_fiscal = str_replace (",","",$nota_fiscal);

	$total = str_replace (",",".",$total);
	$emissao = substr ($emissao,6,4) . "-" . substr ($emissao,3,2) . "-" . substr ($emissao,0,2);

	$sql = "INSERT INTO tbl_distrib_devolucao (distribuidor, posto, nota_fiscal, emissao, total) VALUES ($login_posto, $posto, LPAD (TRIM ('$nota_fiscal'),6,'0'),'$emissao',$total)";
	$res = pg_exec ($con,$sql);

	header ("Location: $PHP_SELF");
	exit;
}


?>

<html>
<head>
<title>Notas de Devolução</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<style type="text/css">
<!--
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

-->
</style>
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Notas de Devolução para Gerar Crédito</h1></center>

<p>

<center>
<form name='frm_devolucao' action='<? echo $PHP_SELF ?>' method='post'>
<input type='hidden' name='distrib_devolucao' value='<? echo $distrib_devolucao ?>'>

<? if ($listar_postos) {
	$sql = "SELECT tbl_posto.posto, tbl_posto.nome, tbl_posto.cidade FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).") WHERE tbl_posto.nome ILIKE '%$nome_posto%'";
	$res = pg_exec ($con,$sql);

	echo "<input type='hidden' name='posto'>";
	echo "<center>";
	echo "Escolha um dos postos abaixo para efetuar o crédito";
	echo "</center>";

	echo "<table>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo "<tr>";

		echo "<td>";
		echo "<a href=\"javascript: document.frm_devolucao.posto.value='" . pg_result ($res,$i,posto) . "'; document.frm_devolucao.btn_acao.value='gravar' ; document.frm_devolucao.submit() \">";
		echo pg_result ($res,$i,nome);
		echo "</a>";
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,cidade);
		echo "</td>";

		echo "</tr>";
	}
	echo "</table>";

}else{
?>
Código do Posto <input type='text' class='frm' size='10' name='codigo_posto' value='<? echo $codigo_posto ?>'>
Nome do Posto <input type='text' class='frm' size='25' name='nome_posto' value='<? echo $nome_posto ?>'>
<?
}
?>

<br>

Valor do Crédito <input type='text' class='frm' size='10' name='total' value='<? echo $total ?>'>
Nota Fiscal <input type='text' class='frm' size='10' name='nota_fiscal' value='<? echo $nota_fiscal ?>'>
Emissão <input type='text' class='frm' size='10' name='emissao' value='<? echo $emissao ?>'>

<br>

<input type='submit' name='btn_acao' value='Gravar'>

</form>
</center>

<?
	$sql="	SELECT 	tbl_distrib_devolucao.distrib_devolucao,
					tbl_posto.nome ,
					tbl_posto_fabrica.codigo_posto,
					tbl_distrib_devolucao.nota_fiscal,
					TO_CHAR(tbl_distrib_devolucao.emissao,'DD/MM/YYYY')AS emissao,
					tbl_distrib_devolucao.total,
					tbl_distrib_devolucao.faturamento_fatura
			FROM 	tbl_distrib_devolucao
			JOIN 	tbl_posto ON tbl_posto.posto = tbl_distrib_devolucao.posto
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).")
			WHERE 	tbl_distrib_devolucao.distribuidor = $login_posto
			ORDER	BY tbl_distrib_devolucao.emissao DESC;";


	$res = pg_exec ($con,$sql);

	if(pg_numrows($res)>0){
		echo '<table width="800" border="0" cellpadding="2" cellspacing="2" align="center">';
		echo '<tr align="center" class="Titulo">';

		echo '<td bgcolor="#D9E2EF">Posto';
		echo '</td>';
		echo '<td bgcolor="#D9E2EF" class="Titulo">Nota Fiscal';
		echo '</td>';
		echo '<td bgcolor="#D9E2EF">Emissão';
		echo '</td>';
		echo '<td bgcolor="#D9E2EF">Total';
		echo '</td>';
 		echo '<td bgcolor="#D9E2EF">Faturamento';
		echo '</td>';
		echo '</tr>';
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$nome			= pg_result($res,$x,nome);
			$posto			= pg_result($res,$x,codigo_posto);
			$nota_fiscal 	= pg_result($res,$x,nota_fiscal);
			$emissao		= pg_result($res,$x,emissao);
			$total			= pg_result($res,$x,total);
			$faturamento	=pg_result($res,$x,faturamento_fatura);

			$total	=number_format($total, 2, ',', ' ');
			
			$cor = '#EFF5F5';

			if ($x % 2 == 0) $cor = '#B6DADA';

			echo "<tr class='Conteudo'>";

			echo "<td bgcolor='$cor' align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo $posto.' - '.$nome;
			echo "</font>";
			echo "</td>";

			echo "<td bgcolor='$cor' align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo $nota_fiscal;
			echo "</font>";
			echo "</td>";

			echo "<td bgcolor='$cor' align='center' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2' >";
			echo $emissao;
			echo "</font>";
			echo "</td>";

			echo "<td bgcolor='$cor' align='right' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo $total;
			echo "</font>";
			echo "</td>";

			echo "<td bgcolor='$cor' align='center' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo $faturamento;
			echo "</font>";
			echo "</td>";
			echo "</tr>";
		}

		echo "</tr>";
		echo "</table>";
	}else{
		echo "Não foram encontrados registros";
	}

?>


<? #include "rodape.php"; ?>

</body>
</html>
<?
include'rodape.php';
?>
