<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

#include 'cabecalho_pop_produtos.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<br>

<img src="imagens/pesquisa_produtos.gif">

<?
$tipo = trim (strtolower ($_GET['tipo']));

$peca = $_GET['peca'];

if ($tipo == "referencia") {
	$referencia = trim (strtoupper($_GET["campo"]));
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace (",","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	echo "<p>";

	$sql = "SELECT  DISTINCT
			tbl_produto.produto   ,
			tbl_produto.referencia,
			tbl_produto.descricao ,
			tbl_produto.ativo
	FROM    tbl_produto
	JOIN    tbl_lista_basica USING (produto)
	JOIN    tbl_peca         USING (peca)
	JOIN    tbl_linha        USING (linha)
	WHERE   tbl_linha.fabrica     = $login_fabrica
	AND     tbl_lista_basica.peca = $peca
	ORDER BY tbl_produto.referencia;";
	#echo $sql;
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";

		exit;
	}
}

if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3 = new anexaS3('ve', (int) $login_fabrica);
}

	/*echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";*/

	echo "<table width='100%' border='0'>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto    = trim(pg_result($res,$i,produto));

		$referencia = trim(pg_result($res,$i,referencia));

		$descricao  = trim(pg_result($res,$i,descricao));
		$descricao  = str_replace ('"','',$descricao);

		$ativo      = trim(pg_result($res,$i,ativo));

		if ($ativo == 't') {
			$mativo = "ATIVO";
		}else{
			$mativo = "INATIVO";
		}

		echo "<tr>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</td>\n";

		echo "<td>\n";
			if(strlen($produto)>0){
				$sqlP = "SELECT tbl_comunicado.comunicado, tbl_comunicado.tipo
						FROM tbl_comunicado
						WHERE tbl_comunicado.fabrica = $login_fabrica
						AND   tbl_comunicado.produto = $produto;";
						#echo nl2br($sqlP);
				$resP = pg_exec($con, $sqlP);

				if(pg_numrows($resP)>0){
					$Xcomunicado = pg_result($resP,0,comunicado);

					if ($S3_online) {
						$tipo_s3 = in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
						if ($s3->tipo_anexo != $tipo_s3)
							$s3->set_tipo_anexoS3($tipo_s3);
						$s3->temAnexos($comunicado);

						if ($s3->temAnexo) {
							$com_file = $s3->url;
							echo "<a href='$com_file' target='_blank'>";
						}

					} else {

						$gif = "comunicados/$Xcomunicado.gif";
						$jpg = "comunicados/$Xcomunicado.jpg";
						$pdf = "comunicados/$Xcomunicado.pdf";
						$doc = "comunicados/$Xcomunicado.doc";
						$rtf = "comunicados/$Xcomunicado.rtf";
						$xls = "comunicados/$Xcomunicado.xls";
						$ppt = "comunicados/$Xcomunicado.ppt";
						$zip = "comunicados/$Xcomunicado.zip";
						$txt = "comunicados/$Xcomunicado.txt";

						if (file_exists($gif) == true) echo "<a href='comunicados/$Xcomunicado.gif' target='_blank'>";
						if (file_exists($jpg) == true) echo "<a href='comunicados/$Xcomunicado.jpg' target='_blank'>";
						if (file_exists($cod) == true) echo "<a href='comunicados/$Xcomunicado.cod' target='_blank'>";
						if (file_exists($xls) == true) echo "<a href='comunicados/$Xcomunicado.xls' target='_blank'>";
						if (file_exists($rtf) == true) echo "<a href='comunicados/$Xcomunicado.rtf' target='_blank'>";
						if (file_exists($xls) == true) echo "<a href='comunicados/$Xcomunicado.xls' target='_blank'>";
						if (file_exists($pdf) == true) echo "<a href='comunicados/$Xcomunicado.pdf' target='_blank'>";
						if (file_exists($ppt) == true) echo "<a href='comunicados/$Xcomunicado.ppt' target='_blank'>";
						if (file_exists($zip) == true) echo "<a href='comunicados/$Xcomunicado.zip' target='_blank'>";
						if (file_exists($txt) == true) echo "<a href='comunicados/$Xcomunicado.txt' target='_blank'>";
					}

					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'><b>$descricao</b></font>\n";
					echo "</a>\n";
				}else{
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
				}
			}else{
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>Produto Não encontrado</font>\n";
			}

			echo "</td>\n";

			echo "<td>\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$mativo</font>\n";
			echo "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
?>

</body>
</html>
