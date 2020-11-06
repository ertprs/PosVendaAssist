<?php
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include 'autentica_usuario_assist.php';

#include 'cabecalho_pop_produtos.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<? if ($sistema_lingua=='ES') { ?>
	<title> Busca producto... </title>
<? } else { ?>
	<title> Pesquisa Produto... </title>
<? } ?>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<!--
<body onblur="javascript: setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
-->

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>


<br>

<img src="imagens/pesquisa_produtos<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">
<BR>
<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));

	if($sistema_lingua == "ES") { 
		echo "<h4>Buscando por <B>referencia del producto</b>:";
	}else{ 
		echo "<h4>Pesquisando por <b>descrição do produto</b>:";
	}
	echo "<i>$descricao</i></h4>";

	echo "<p>";
	$descricao = strtoupper($descricao);

	if($login_pais<>'BR'){
	$cond1="";
	}
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			LEFT JOIN tbl_produto_idioma using(produto)
			LEFT JOIN tbl_produto_pais   using(produto)
			WHERE   (
				   UPPER(tbl_produto.descricao)      LIKE '%$descricao%' 
				OR UPPER(tbl_produto.nome_comercial) LIKE '%$descricao%' 
				OR ( 
					UPPER(tbl_produto_idioma.descricao) LIKE '%$descricao%' 
					AND tbl_produto_idioma.idioma = '$sistema_lingua'
				)
				
			)
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			AND      tbl_produto.produto_principal ";
#	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS NOT FALSE ";
	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";
	$sql .= " ORDER BY tbl_produto.descricao;";

	//echo $sql;exit;
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		/*HD 3192 Quando ocorre um erro na máquina inseridan aparece "Producto xxxxxxxxxx no encuentrado" deveria ser "Producto xxxxxxxxxx no encontrado".*/
		if ($sistema_lingua=='ES') echo "<h1>Producto '$descricao' no encontrado</h1>";
		else echo "<h1>Produto '$descricao' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
	/* takashi alterou 05-04-2007 hd1819*/
	if (@pg_numrows ($res) == 1 and $login_fabrica==24) {
			$produto    = trim(pg_result($res,0,produto));
			$descricao  = trim(pg_result($res,0,descricao));
			$voltagem   = trim(pg_result($res,0,voltagem));
			$referencia = trim(pg_result($res,0,referencia));
			$descricao = str_replace ('"','',$descricao);
			$descricao = str_replace ("'","",$descricao);
			echo "<script language='JavaScript'>\n";
			echo "referencia.value = '$referencia' ;";
			echo "descricao.value = '$descricao' ;";
			echo "voltagem.value = '$voltagem';";
			echo "descricao.focus();";
			echo "this.close();";
			echo "</script>\n";

	}
}


if ($tipo == "referencia") {
	$referencia = trim (strtoupper($_GET["campo"]));
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace (",","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);

	/*echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";*/
	
	if($sistema_lingua == "ES") { 
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por <B>referencia del producto</b>:";
	}else{ 
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição do produto</b>:";
	}
	echo "<i>$referencia</i></font>";
echo "<p>";

	
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			LEFT JOIN tbl_produto_pais   using(produto)
			WHERE    tbl_produto.referencia_pesquisa LIKE '%$referencia%'
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			AND      tbl_produto.produto_principal ";

	if($login_fabrica == 20){
		$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			LEFT JOIN tbl_produto_pais   using(produto)
			WHERE    (tbl_produto.referencia_pesquisa LIKE '%$referencia%' OR tbl_produto.referencia_fabrica LIKE '%$referencia%')
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			AND      tbl_produto.produto_principal ";
	}

#	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS NOT FALSE ";
    if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";
	$sql .= " ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
			/*HD 3192 Quando ocorre um erro na máquina inseridan aparece "Producto xxxxxxxxxx no encuentrado" deveria ser "Producto xxxxxxxxxx no encontrado".*/
		if ($sistema_lingua=='ES') echo "<h1>Producto '$referencia' no encontrado</h1>";
		else echo "<h1>Produto '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		
		exit;
	}
		/* takashi alterou 05-04-2007 hd1819*/
	if (@pg_numrows ($res) == 1 and $login_fabrica==24) {
			$produto    = trim(pg_result($res,0,produto));
			$descricao  = trim(pg_result($res,0,descricao));
			$voltagem   = trim(pg_result($res,0,voltagem));
			$referencia = trim(pg_result($res,0,referencia));
			$descricao = str_replace ('"','',$descricao);
			$descricao = str_replace ("'","",$descricao);
			echo "<script language='JavaScript'>\n";
			echo "referencia.value = '$referencia' ;";
			echo "descricao.value = '$descricao' ;";
			echo "voltagem.value = '$voltagem';";
			echo "descricao.focus();";
			echo "this.close();";
			echo "</script>\n";

	}
}


	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto    = trim(pg_result($res,$i,produto));
		$linha      = trim(pg_result($res,$i,linha));
		$descricao  = trim(pg_result($res,$i,descricao));
		$nome_comercial = trim(pg_result($res,$i,nome_comercial));
		$voltagem   = trim(pg_result($res,$i,voltagem));
		$referencia = trim(pg_result($res,$i,referencia));
		$referencia_fabrica = trim(pg_result($res,$i,referencia_fabrica));
		$garantia   = trim(pg_result($res,$i,garantia));
		$mobra      = str_replace(".",",",trim(pg_result($res,$i,mao_de_obra)));
		$ativo      = trim(pg_result($res,$i,ativo));
		$off_line   = trim(pg_result($res,$i,off_line));
		
		$descricao = str_replace ('"','',$descricao);
		$descricao = str_replace ("'","",$descricao);

		//--=== Tradução para outras linguas ============================= Raphael HD:1212
		$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";
	
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$descricao  = trim(@pg_result($res_idioma,0,descricao));
		}
		//--=== Tradução para outras linguas ================================================

		if ($ativo == 't') {
			$mativo = "ATIVO";
		}else{
			$mativo = "INATIVO";
		}

		$cor = '#ffffff';
		if ($i % 2 <> 0) $cor = '#EEEEEE';

		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>\n";
		//takashi 06/07/2006 chamado 300 help desk
		echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; voltagem.value = '$voltagem' ; descricao.focus();this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</A>";
		echo "</td>\n";

		if($login_fabrica == 20){
			echo "<td>\n";
			if(strlen($referencia_fabrica)>0){
			echo "<font size='1' color='#AAAAAA'>Bare Tool</font><br>";
			}
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'> $referencia_fabrica </font>\n";
			echo "</td>\n";
		}
		
		if ($login_fabrica == 14) {

			#------------ Pesquisa de Produto Pai para INTELBRÁS -----------
			$sql = "SELECT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
				           FROM     tbl_produto
				           JOIN     tbl_subproduto ON tbl_subproduto.produto_pai = tbl_produto.produto
				           WHERE    tbl_subproduto.produto_filho = $produto
				           AND      tbl_produto.ativo
				           ORDER BY tbl_produto.descricao";
			$resX = pg_exec ($con,$sql);

			if (pg_numrows ($resX) == 0) {
				echo "<td>\n";
				echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; if (window.voltagem) { voltagem.value = '$voltagem' ; } ;descricao.focus(); this.close() ; \" >";
				echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
				echo "</a>\n";
			}else{
				echo "<td>\n";
				echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$descricao</font>\n";
			}

			for ( $x = 0 ; $x < pg_numrows ($resX) ; $x++ ) {
				$produto_pai    = trim(pg_result($resX,$x,produto));
				$descricao_pai  = trim(pg_result($resX,$x,descricao));
				$referencia_pai = trim(pg_result($resX,$x,referencia));
				
				$descricao_pai = str_replace ('"','',$descricao_pai);

				$sql = "SELECT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
							   FROM     tbl_produto
							   JOIN     tbl_subproduto ON tbl_subproduto.produto_pai = tbl_produto.produto
							   WHERE    tbl_subproduto.produto_filho = $produto_pai
							   AND      tbl_produto.ativo
							   ORDER BY tbl_produto.descricao";
				$resZ = pg_exec ($con,$sql);

				if (pg_numrows ($resZ) == 0) {
					echo "<br>";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>&nbsp;&nbsp;&nbsp;|___> </font>\n";
					echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; if (window.voltagem) { voltagem.value = '$voltagem'} ; if (window.referencia_pai) { referencia_pai.value = '$referencia_pai' } ; if (window.descricao_pai) { descricao_pai.value = '$descricao_pai' } ; descricao.focus();this.close() ; \" >";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao_pai </font>\n";
					echo "</a>\n";
				}else{
					echo "<br>";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>&nbsp;&nbsp;&nbsp;|___> </font>\n";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$descricao_pai</font>\n";
				}

				for ( $z = 0 ; $z < pg_numrows ($resZ) ; $z++ ) {
					$produto_avo    = trim(pg_result($resZ,$z,produto));
					$descricao_avo  = trim(pg_result($resZ,$z,descricao));
					$referencia_avo = trim(pg_result($resZ,$z,referencia));
					
					$descricao_avo = str_replace ('"','',$descricao_avo);
					echo "<br>";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|___> </font>\n";
					echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; if (window.voltagem) { voltagem.value = '$voltagem'} ; if (window.referencia_pai) { referencia_pai.value = '$referencia_pai' } ; if (window.descricao_pai) { descricao_pai.value = '$descricao_pai' } ; if (window.referencia_avo) { referencia_avo.value = '$referencia_avo' } ; if (window.descricao_avo) { descricao_avo.value = '$descricao_avo' } ; descricao.focus();this.close() ; \" >";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao_avo </font>\n";
					echo "</a>\n";
				}
			}
			echo "</td>\n";

		}else{
			echo "<td>\n";
			echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia';  voltagem.value = '$voltagem'; descricao.focus(); this.close();\" >";
//			echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; if (window.voltagem) { voltagem.value = '$voltagem' ; } ; this.close() ; \" >";
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

/*
		$produto_pai = $produto ;
		while (strlen ($produto_pai) > 0) {
			$sql = "SELECT tbl_produto.referencia, tbl_produto.descricao, tbl_produto.produto, tbl_subproduto.produto_filho
						FROM	tbl_produto 
						JOIN	tbl_subproduto ON tbl_produto.produto = tbl_subproduto.produto_filho 
						AND		tbl_subproduto.produto_pai = $produto_pai ";
			$resX = pg_exec ($con,$sql);
			if (pg_numrows ($resX) > 0) {
				for ( $x = 0 ; $x < pg_numrows ($resX) ; $x++ ) {
					echo "<tr><td colspan='4'><table width='100%' border='0'>";
					echo "<td><img src='imagens/setinha.gif'></td>";
					echo "<td>" . pg_result ($resX,$x,referencia) . "</td>";
					echo "<td>" . pg_result ($resX,$x,descricao) . "</td>";
					echo "</tr></table></tr>";
					$produto_pai = pg_result ($resX,$x,produto_filho);
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
