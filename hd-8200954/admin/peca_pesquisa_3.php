<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_pecas.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

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

table.tabela tr td{
	font-family: verdana;
	font: bold 11px "Arial";
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>
</head>

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">

<br>

<?
$erro    = "";
$campo   = $_GET["campo"];
$tipo    = $_GET["tipo"];
$linha   = $_GET["linha"];
$familia = $_GET["familia"];

if (strlen($linha) == 0) $erro .= " Informe a Linha para pesquisa ";
if (strlen($familia) == 0) $erro .= " Informe a Família para pesquisa ";

if (strlen($erro) == 0) {
	$sql =	"SELECT tbl_peca.peca       ,
					tbl_peca.referencia ,
					tbl_peca.descricao
			FROM tbl_produto
			JOIN tbl_linha  ON  tbl_linha.linha     = tbl_produto.linha
							AND tbl_linha.fabrica   = $login_fabrica
			JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
							AND tbl_familia.fabrica = $login_fabrica
			JOIN tbl_lista_basica ON tbl_lista_basica.produto = tbl_produto.produto
			JOIN tbl_peca         ON tbl_peca.peca = tbl_lista_basica.peca
								 AND tbl_peca.fabrica = $login_fabrica
			WHERE tbl_linha.linha = $linha
			AND   tbl_familia.familia = $familia
			AND   tbl_peca.ativo IS TRUE";
	if ($tipo == "REFERENCIA") {
		$peca_pesquisa = str_replace (".","",$campo);
		$peca_pesquisa = str_replace ("-","",$peca_pesquisa);
		$peca_pesquisa = str_replace ("/","",$peca_pesquisa);
		$peca_pesquisa = str_replace (" ","",$peca_pesquisa);
		$sql .= " AND tbl_peca.referencia_pesquisa ILIKE '%$peca_pesquisa%'";
	}
	if ($tipo == "DESCRICAO") $sql .= " AND tbl_peca.descricao ILIKE '%$campo%'";
	$sql .=	"GROUP BY tbl_peca.peca       ,
					 tbl_peca.referencia ,
					 tbl_peca.descricao
			ORDER BY tbl_peca.descricao;";
	
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) == 0) {
		echo "<h1>Peça '$campo' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

	if (@pg_numrows($res) == 1) {
		$peca       = trim(pg_result($res,0,peca));
		$referencia = trim(pg_result($res,0,referencia));
		$descricao  = htmlentities(trim(pg_result($res,0,descricao)));
		echo "<script language='JavaScript'>";
		echo "peca.value='$peca'; referencia.value='$referencia'; descricao.value='$descricao'; this.close();";
		echo "</script>";
		exit;
	}
	
	if (@pg_numrows($res) > 0) {
		echo "<table width='100%' border='0' class='tabela' cellspancing='1'>\n";
		echo "<tr class='titulo_coluna'>";
		echo "<td>Código</td><td>Descrição</td>";
		echo "</tr>";
		for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
			$peca       = trim(pg_result($res,$i,peca));
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao  = htmlentities(trim(pg_result($res,$i,descricao)));

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
			echo "<tr bgcolor='$cor'>\n";
			echo "<td>\n";
			echo "<a href=\"javascript: peca.value='$peca'; referencia.value='$referencia'; descricao.value='$descricao'; this.close();\">";
			echo $referencia;
			echo "</a>\n";
			echo "</td>\n";
			echo "<td>\n";
			echo "<a href=\"javascript: peca.value='$peca'; referencia.value='$referencia'; descricao.value='$descricao'; this.close();\">";
			echo $descricao;
			echo "</a>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
	}
}else{
	echo "<table width='100%' border='0' align='center' class='error'>\n";
	echo "<tr>\n";
	echo "<td align='center'>" . $erro . "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}
?>

</body>
</html>
