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
<title>Etiquetas para Endereçamento</title>
</head>

<body>

<? include 'menu.php' ?>


<?


$embarque = $_GET['embarque'];
if (strlen ($embarque) > 0) {
	$sql = "SELECT tbl_posto_fabrica.codigo_posto ,
					tbl_posto.nome ,
					tbl_posto.endereco ,
					tbl_posto.numero ,
					tbl_posto.complemento,
					tbl_posto.bairro,
					tbl_posto.cep,
					tbl_posto.cidade,
					tbl_posto.estado,
					tbl_embarque.embarque
			FROM    tbl_embarque 
			JOIN    tbl_posto    ON tbl_embarque.posto = tbl_posto.posto
			JOIN    tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica IN ".implode(",", $fabricas)."
			WHERE   tbl_embarque.embarque >= $embarque 
			ORDER BY tbl_embarque.embarque 
			LIMIT 6";
	$sqlX = "SELECT tbl_posto_fabrica.codigo_posto ,
					tbl_posto.nome ,
					tbl_posto.endereco ,
					tbl_posto.numero ,
					tbl_posto.complemento,
					tbl_posto.bairro,
					tbl_posto.cep,
					tbl_posto.cidade,
					tbl_posto.estado,
					tbl_embarque.embarque
			FROM    tbl_embarque 
			JOIN    tbl_posto    ON tbl_posto.posto = $login_posto
			JOIN    tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).")
			WHERE   tbl_embarque.embarque >= $embarque 
			ORDER BY tbl_embarque.embarque 
			LIMIT 6";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo "<table width='100%' align='center' border='1' cellpadding='5' cellspacing='0'>";

		echo "<tr>";

		echo "<td colspan='4' align='center'>";
		echo "<font size='+1'> D E S T I N A T Á R I O - Embarque # " . pg_result ($res,$i,embarque) . "</font>";
#		echo "<font size='+1'> R E M E T E N T E " . "</font>";
		echo "</td>";

		echo "</tr>";

		echo "<tr>";

		echo "<td colspan='4'>";
		echo "<font size='+1'>" . pg_result ($res,$i,codigo_posto) . " - " . pg_result ($res,$i,nome) . "</font>";
		echo "</td>";

		echo "</tr>";

		
		echo "<tr>";

		echo "<td colspan='4'>";
		echo "<font size='+1'>" . pg_result ($res,$i,endereco) ;
		if (strlen (trim (pg_result($res,$i,numero))) > 0) echo " n. " . pg_result ($res,$i,numero) ;
		if (strlen (trim (pg_result($res,$i,complemento))) > 0) echo "  - " . pg_result ($res,$i,complemento) ;
		echo "</font>";
		echo "</td>";

		echo "</tr>";

		
		echo "<tr>";

		echo "<td>";
		echo "<font size='+1'>" . pg_result ($res,$i,bairro) . "</font>";
		echo "</td>";

		$cep = pg_result ($res,$i,cep) ;
		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
		echo "<td>";
		echo "<font size='+1'>" . $cep . "</font>";
		echo "</td>";

		
		echo "<td>";
		echo "<font size='+1'>" . pg_result ($res,$i,cidade) . "</font>";
		echo "</td>";

		echo "<td>";
		echo "<font size='+1'>" . pg_result ($res,$i,estado) . "</font>";
		echo "</td>";

		echo "</tr>";

		
		
		
		echo "</table>";
		echo "<p>";
	}


	#echo "<p align='center'>";
	#echo "<a href='$PHP_SELF?posto=$posto&embarque=$embarque&cancelar=S'>Clique aqui CANCELAR este embarque</a>";
	#echo "<p>";

}

?>



<p>

<? #include "rodape.php"; ?>

</body>
</html>
