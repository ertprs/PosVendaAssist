<?php

/* 
	Esse arquivo foi criado em substituição do arquivo produto_serie_pesquisa_fricon,
	pois outras fábricas necessitam usar esse programa também.
*/
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_produtos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css">
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
	<script src="js/jquery-1.3.2.js"	type="text/javascript"></script>
	<script src="js/thickbox.js"		type="text/javascript"></script>
</head>
<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<br>

<?

$mapa_linha = trim (strtolower ($_GET['mapa_linha']));
$tipo       = trim (strtolower ($_GET['tipo']));

if ($tipo == "ordem") {
	$num_ativo = trim(strtoupper($_GET["campo"]));


	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>número do ativo </b>: <i>$num_ativo</i></font>";
	echo "<p>";

	$sql = "SELECT	tbl_numero_serie.serie,
					tbl_numero_serie.produto,
					tbl_produto.referencia  ,
					tbl_produto.descricao   ,
					tbl_produto.linha       ,
					tbl_produto.voltagem    ,
					tbl_numero_serie.ordem  ,
					tbl_produto.ativo
			FROM     tbl_numero_serie
 			JOIN     tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
			WHERE    tbl_numero_serie.ordem ILIKE '%$num_ativo%'
			AND      tbl_numero_serie.fabrica = $login_fabrica limit 30";
	//echo nl2br($sql);
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows($res) == 1) {

		echo "<script language='JavaScript'>\n";
	if (strlen($_GET['lbm']) > 0) {
		echo "produto.value = '".trim(pg_result($res,0,produto))."';";
	}
		echo "descricao.value  = '".str_replace ('"','',trim(pg_result($res,0,descricao)))."';";

		echo "referencia.value  = '".str_replace ('"','',trim(pg_result($res,0,referencia)))."';";

		echo "mapa_linha.value  = '".str_replace ('"','',trim(pg_result($res,0,linha)))."';";

		echo "serie.value  = '".str_replace ('"','',trim(pg_result($res,0,serie)))."';";

	echo "this.close();";
	echo "</script>\n";
	exit;
} else{

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='1'>\n";
	
	for($i = 0; $i < pg_numrows($res); $i++){
		$mativo = pg_result($res,$i,ativo);

		$mativo = ($mativo == "t") ? "Ativo" : "Inativo";

		echo "<tr>\n";
			
			echo "<td>\n";

			echo "<a href=\"javascript: ";
			if (strlen($_GET['lbm']) > 0) {
				echo "produto.value = '".trim(pg_result($res,$i,produto))."'; ";
			}
			
				echo "descricao.value = '".trim(pg_result($res,$i,descricao))."'; ";
			
			echo "referencia.value = '".str_replace ('"','',trim(pg_result($res,$i,referencia)))."'; ";
			if($mapa_linha =='t'){
				echo " mapa_linha.value = '".str_replace ('"','',trim(pg_result($res,$i,linha)))."'; ";
			}
			
			echo " serie.value = '".str_replace ('"','',trim(pg_result($res,$i,serie)))."'; ";

			echo " ordem.value = '".str_replace ('"','',trim(pg_result($res,$i,ordem)))."'; ";

			echo "this.close() ; \" >";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>".pg_result($res,$i,ordem)."</font>\n";
			echo "</a>\n";
			echo "</td>\n";


			echo "<td>\n";

			echo "<a href=\"javascript: ";
			if (strlen($_GET['lbm']) > 0) {
				echo "produto.value = '".trim(pg_result($res,$i,produto))."'; ";
			}
				echo "descricao.value = '".trim(pg_result($res,$i,descricao))."'; ";

			echo "referencia.value = '".str_replace ('"','',trim(pg_result($res,$i,referencia)))."'; ";
			if($mapa_linha =='t'){
				echo " mapa_linha.value = '".str_replace ('"','',trim(pg_result($res,$i,linha)))."'; ";
			}

			echo " serie.value = '".str_replace ('"','',trim(pg_result($res,$i,serie)))."'; ";

			echo " ordem.value = '".str_replace ('"','',trim(pg_result($res,$i,ordem)))."'; ";

			echo "this.close() ; \" >";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>".pg_result($res,$i,referencia)." </font>\n";
			echo "</a>\n";
			echo "</td>\n";
				
			echo "<td>\n";
			echo "<a href=\"javascript: ";
			if (strlen($_GET['lbm']) > 0) {
				echo "produto.value = '".trim(pg_result($res,$i,produto))."'; ";
			}
				echo "descricao.value = '".trim(pg_result($res,$i,descricao))."'; ";

			echo "referencia.value = '".str_replace ('"','',trim(pg_result($res,$i,referencia)))."'; ";

			if($mapa_linha =='t'){
				echo " mapa_linha.value = '".str_replace ('"','',trim(pg_result($res,$i,linha)))."'; ";
			}

			echo " serie.value = '".str_replace ('"','',trim(pg_result($res,$i,serie)))."'; ";

			echo " ordem.value = '".str_replace ('"','',trim(pg_result($res,$i,ordem)))."'; ";
			
			echo "this.close() ; \" >";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>".pg_result($res,$i,descricao)."</font>\n";
			echo "</a>\n";
			echo "</td>\n";
				
			echo "<td>\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>".pg_result($res,$i,voltagem)."</font>\n";
			echo "</td>\n";
				
			echo "<td>\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$mativo</font>\n";
			echo "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>