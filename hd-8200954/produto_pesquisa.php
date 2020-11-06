<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

#include 'cabecalho_pop_produtos.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
	<meta name="Author" content="">
	<meta name="Keywords" content="">
	<meta name="Description" content="">
	<meta http-equiv=pragma content=no-cache>
	<title> Pesquisa Produto... </title>
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
</head>

<!--
<body onblur="javascript: setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
-->

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>


<br>

<img src="imagens/pesquisa_produtos.gif">

<?
$tipo = trim (strtolower ($_GET['tipo']));

if($login_fabrica == 30){
	$join_busca_referencia = 'LEFT JOIN tbl_esmaltec_referencia_antiga ON (tbl_produto.referencia = tbl_esmaltec_referencia_antiga.referencia) '; 
}

if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));

	echo "<h4>Pesquisando por <b>descrição do produto</b>: <i>$descricao</i></h4>";
	echo "<p>";

	$descricao = str_replace("\'", "", $descricao);
	$sql       = "SELECT fn_retira_especiais('$descricao') AS descricao";
	$res       = pg_query($con,$sql);
	$descricao = pg_fetch_result($res, 0, "descricao");

	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    (fn_retira_especiais(tbl_produto.descricao) ilike '%$descricao%'
				     OR fn_retira_especiais(tbl_produto.nome_comercial) ilike '%$descricao%')
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			AND      tbl_produto.produto_principal
			ORDER BY tbl_produto.descricao;";
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) == 0) {
		echo "<h1>Produto '$descricao' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "referencia") {
	$referencia = trim (strtoupper($_GET["campo"]));
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace (",","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);

	if($login_fabrica == 30){
		$or_busca_referencia = " OR tbl_esmaltec_referencia_antiga.referencia_antiga LIKE '%$referencia%' ";
	}

	$linha = $_GET['linha'];

	if(strlen($linha)>0 AND $login_fabrica==19){
		$cond_linha = " AND      tbl_produto.linha = 261 ";
	}

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	echo "<p>";

	$sql = "SELECT   *
			FROM     tbl_produto
			$join_busca_referencia
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    (tbl_produto.referencia_pesquisa ILIKE '%$referencia%' $or_busca_referencia)
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			AND      tbl_produto.produto_principal
			$cond_linha
			ORDER BY tbl_produto.descricao;";
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";

		exit;
	}
}


	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	
		if($login_fabrica == 30){
			$referencia_antiga  = trim(pg_fetch_result($res, $i, 'referencia_antiga'));
		}
	
		$produto    = trim(pg_fetch_result($res,$i,produto));
		$linha      = trim(pg_fetch_result($res,$i,linha));
		$descricao  = trim(pg_fetch_result($res,$i,descricao));
		$nome_comercial = trim(pg_fetch_result($res,$i,nome_comercial));
		$voltagem   = trim(pg_fetch_result($res,$i,voltagem));
		$referencia = trim(pg_fetch_result($res,$i,referencia));
		$garantia   = trim(pg_fetch_result($res,$i,garantia));
		$mobra      = str_replace(".",",",trim(pg_fetch_result($res,$i,mao_de_obra)));
		$ativo      = trim(pg_fetch_result($res,$i,ativo));
		$off_line   = trim(pg_fetch_result($res,$i,off_line));

		$descricao = str_replace (array('"',"'"),'',$descricao);

		if ($ativo == 't') {
			$mativo = "ATIVO";
		}else{
			$mativo = "INATIVO";
		}
		echo "<tr>\n";

		if($login_fabrica == 30){
			echo "<td><font size='1'>Ref. Ant.: $referencia_antiga</font> </td>\n";
		}
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</td>\n";

		if($login_fabrica==19 AND strlen($linha)>0){
			$Xcomunicado = "";
			if(strlen($produto)>0){
				$sqlx = "SELECT comunicado, tbl_comunicado.tipo
						   FROM tbl_comunicado
						  WHERE tbl_comunicado.produto = $produto
						    AND tbl_comunicado.fabrica = $login_fabrica";
				#	echo nl2br($sqlx);
				$resx = pg_query($con, $sqlx);
				if(pg_num_rows($resx)>0) {
					$Xcomunicado = pg_fetch_result($resx,0,comunicado);
					$com_tipo    = pg_fetch_result($resx, 0, 'tipo');
					echo "<td>\n";

					if ($S3_online) {
						$tipo_s3 = in_array($com_tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
						if ($s3->tipo_anexo != $tipo_s3)
							$s3->set_tipo_anexoS3($tipo_s3);
						$s3->temAnexos($Xcomunicado);

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

					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
					echo "</a>";
					echo "</td>\n";
				}else{
					echo "<td>\n";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
					echo "</td>\n";
				}
			}
		}else{
			echo "<td>\n";
			echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; if (window.voltagem) { voltagem.value = '$voltagem' ; } ; this.close() ; \" >";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
			echo "</td>\n";
		}

		echo "<td>\n";

		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome_comercial</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$voltagem</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$mativo</font>\n";
		echo "</td>\n";

		echo "</tr>\n";

		$produto_pai = $produto ;
/*
		while (strlen ($produto_pai) > 0) {
			$sql = "SELECT tbl_produto.referencia, tbl_produto.descricao, tbl_produto.produto, tbl_subproduto.produto_filho
						FROM	tbl_produto
						JOIN	tbl_subproduto ON tbl_produto.produto = tbl_subproduto.produto_filho
						AND		tbl_subproduto.produto_pai = $produto_pai ";
			$resX = pg_query ($con,$sql);
			if (pg_numrows ($resX) > 0) {
				for ( $x = 0 ; $x < pg_numrows ($resX) ; $x++ ) {
					echo "<tr><td colspan='4'><table width='100%' border='0'>";
					echo "<td><img src='imagens/setinha.gif'></td>";
					echo "<td>" . pg_fetch_result ($resX,$x,referencia) . "</td>";
					echo "<td>" . pg_fetch_result ($resX,$x,descricao) . "</td>";
					echo "</tr></table></tr>";
					$produto_pai = pg_fetch_result ($resX,$x,produto_filho);
				}
			}else{
				$produto_pai = "";
			}
		}
*/
	}
	echo "</table>\n";
?>

</body>
</html>

<?php
// Fechando explicitamente a conexão com o BD
if (is_resource($con)) {
    pg_close($con);
}
?>
