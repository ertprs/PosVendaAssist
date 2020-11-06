<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Selecione os Postos</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<?
$btn_acao = trim($_POST['btn_acao']);

if (strlen($btn_acao) == 0){

?>
<? include 'menu.php' ?>


<center><h1>Selecione os Postos</h1></center>

<p>

<?
	$sql = "SELECT	tbl_posto_fabrica.codigo_posto, 
					tbl_posto.posto,
					tbl_posto.nome,
					tbl_posto.numero
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
							  AND tbl_posto_fabrica.fabrica IN (SELECT DISTINCT fabrica FROM tbl_posto_linha WHERE distribuidor = $login_posto )
		JOIN (SELECT DISTINCT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto) linha ON tbl_posto.posto = linha.posto
		WHERE tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).")
		ORDER BY tbl_posto.nome ";
#echo $sql;
#exit ;

	$res = pg_exec ($con,$sql);
	$total = pg_numrows ($res);

	echo "<table border='1' cellspacing='0' align='center' width='500'>";
	echo "<form name='frm_estoque' action='$PHP_SELF' method='post'>";
	for ($i = 0 ; $i < $total; $i++) {
		echo "<tr bgcolor='#eeeeee' align='center' style='font-weight:bold'>";
		echo "<td align='center' nowrap>";
		echo "<input type='checkbox' name='posto_$i' value=".pg_result ($res,$i,posto).">";
		echo "</td>";
		echo "<td align='LEFT' nowrap>";
		echo pg_result ($res,$i,codigo_posto);
		echo "</td>";
		echo "<td align='LEFT' nowrap>";
		echo pg_result ($res,$i,nome);
		echo "</td>";
		echo "</tr>";
	}
	echo "<tr bgcolor='#eeeeee' align='center' style='font-weight:bold'>";
	echo "<td align='center' nowrap colspan='3'>";
	echo "<input type='hidden' name='total' value='$total'>";
	echo "<input type='submit' name='btn_acao' value='Etiquetas'>";
	echo "</td>";
	echo "</tr>";

	echo "</form>";
	echo "</table>";
}else{

	// monta array dos postos selecionados
	$total = trim($_POST['total']);

	$array = "0";
	for ($x=0;$x<$total;$x++){
		$posto = trim($_POST['posto_'.$x]);
		if (strlen($posto) > 0)
			$array .= ", $posto";
	}
	// exibe os postos
	$sql = "SELECT	tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome,
					tbl_posto.endereco,
					tbl_posto.numero,
					tbl_posto.complemento,
					tbl_posto.bairro,
					tbl_posto.cidade,
					tbl_posto.estado,
					tbl_posto.cep,
					tbl_posto.numero
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
							  AND tbl_posto_fabrica.fabrica IN (SELECT DISTINCT fabrica FROM tbl_posto_linha WHERE distribuidor = $login_posto )
		JOIN (SELECT DISTINCT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto) linha ON tbl_posto.posto = linha.posto
		WHERE tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).")
		AND   tbl_posto_fabrica.posto in ($array)
		ORDER BY tbl_posto.nome ";
	$res = pg_exec ($con,$sql);

	$total = pg_numrows ($res);

	for ($i = 0 ; $i < $total; $i++) {
		echo "<table border='0' cellspacing='0' align='center' width='500'>";
		echo "<tr bgcolor='#ffffff'>";
		echo "<td align='left' nowrap>Código</td>";
		echo "<td align='left' nowrap>Razão Social</td>";
		echo "</tr>";
		echo "<tr bgcolor='#ffffff' style='font-weight:bold'>";
		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,codigo_posto);
		echo "</td>";
		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,nome);
		echo "</td>";
		echo "</tr>";
		echo "<tr bgcolor='#ffffff'>";
		echo "<td align='left' nowrap>Endereço</td>";
		echo "</tr>";
		echo "<tr bgcolor='#ffffff' align='left' style='font-weight:bold'>";
		echo "<td align='left' nowrap colspan=2>";
		echo pg_result ($res,$i,endereco);
		echo ", ";
		echo pg_result ($res,$i,numero);
		echo " ";
		//echo pg_result ($res,$i,complemento);
		echo "</td>";
		echo "</tr>";
		echo "<tr bgcolor='#ffffff'>";
		echo "<td align='left' nowrap colspan=2>CEP</td>";
		echo "</tr>";
		echo "<tr bgcolor='#ffffff'>";
		echo "<td align='left' nowrap colspan=2 style='font-weight:bold'>";
		echo pg_result ($res,$i,cep);
		echo "</td>";
		echo "</tr>";
		echo "<tr bgcolor='#ffffff'>";
		echo "<td align='left' nowrap>Bairro</td>";
		echo "<td align='left' nowrap>Cidade/Estado</td>";
		echo "</tr>";
		echo "<tr bgcolor='#ffffff' style='font-weight:bold'>";
		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,bairro);
		echo "</td>";
		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,cidade)." / ".pg_result ($res,$i,estado);
		echo "</td>";
		echo "</tr>";
		echo "</table>";

		echo "<br><br>";

	}


}

?>

<? #include "rodape.php"; ?>

</body>
</html>
