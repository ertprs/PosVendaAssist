<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

# verifica se posto pode ver pecas de itens de aparencia
$sql = "SELECT   tbl_posto.item_aparencia
		FROM     tbl_posto
		JOIN     tbl_posto_fabrica USING(posto)
		WHERE    tbl_posto.posto           = $login_posto
		AND      tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	$item_aparencia = pg_result($res,0,item_aparencia);
}

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças pela Lista Básica ... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
</head>

<body leftmargin="0" onblur="setTimeout('window.close()',5500);">

<br>

<img src="imagens/pesquisa_pecas.gif">

<?
$tipo = trim (strtolower ($_GET['tipo']));

if (strlen($_GET['produto']) > 0) {
	$produto_referencia = trim($_GET['produto']);
	$produto_referencia = str_replace(".","",$produto_referencia);
	$produto_referencia = str_replace(",","",$produto_referencia);
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);

	$voltagem = trim(strtoupper($_GET["voltagem"]));

	$sql = "SELECT tbl_produto.produto, tbl_produto.descricao
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia') ";

	if (strlen($voltagem) > 0 AND $login_fabrica == "1" ) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER('$voltagem') ";

	$sql .= "AND    tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";
	$res = pg_exec ($con,$sql);
//if ($ip == '201.0.9.216') echo nl2br($sql)."<br><br>";

	if (pg_numrows($res) > 0) {
		$produto_descricao = pg_result ($res,0,descricao);
		$produto = pg_result ($res,0,produto);
	}
}

if ($tipo == "tudo") {
	$descricao = trim(strtoupper($_GET["descricao"]));

	echo "<h4>Pesquisando toda a lista básica do produto: <br><i>$produto_referencia - $produto_descricao</i></h4>";

	echo "<br><br>";

	$res = pg_exec ($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result($res,0,0);

	if ($qtde > 0 AND strlen($produto) > 0) {
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.type                                ,
						z.posicao                             ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao  
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.type               ,
								y.posicao            ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para 
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.type                                      ,
										x.posicao                                   ,
										tbl_peca_fora_linha.peca AS peca_fora_linha 
								FROM (
										SELECT  tbl_peca.peca            ,
												tbl_peca.referencia      ,
												tbl_peca.descricao       ,
												tbl_lista_basica.type    ,
												tbl_lista_basica.posicao 
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										JOIN tbl_produto      USING (produto)
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_produto.produto = $produto
										AND   tbl_peca.ativo IS TRUE
										AND   tbl_produto.ativo IS TRUE";
		if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(tbl_peca.referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";
		if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	}else{
		$sql = "SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao  
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para 
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										tbl_peca_fora_linha.peca AS peca_fora_linha 
								FROM (
										SELECT  tbl_peca.peca       ,
												tbl_peca.referencia ,
												tbl_peca.descricao  
										FROM tbl_peca
										WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";
		if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";
		if ($item_aparencia == 'f') $sql .= " AND item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	}
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Nenhuma lista básica de peças encontrada para este produto</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "descricao") {
	$descricao = trim(strtoupper($_GET["descricao"]));
	
	echo "<h4>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></h4>";
	echo "<p>";
	
	$res = pg_exec ($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result ($res,0,0);
	
	if ($qtde > 0 AND strlen($produto) > 0 ) {
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.type                                ,
						z.posicao                             ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao  
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.type               ,
								y.posicao               ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para 
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.type                                      ,
										x.posicao                                   ,
										tbl_peca_fora_linha.peca AS peca_fora_linha 
								FROM (
										SELECT  tbl_peca.peca            ,
												tbl_peca.referencia      ,
												tbl_peca.descricao       ,
												tbl_lista_basica.type    ,
												tbl_lista_basica.posicao 
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										JOIN tbl_produto      USING (produto)
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_produto.produto = $produto
										AND   tbl_peca.ativo IS TRUE
										AND   tbl_produto.ativo IS TRUE";
		if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%'))";
		if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	}else{
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao  
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para 
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										tbl_peca_fora_linha.peca AS peca_fora_linha 
								FROM (
										SELECT  tbl_peca.peca       ,
												tbl_peca.referencia ,
												tbl_peca.descricao  
										FROM tbl_peca
										WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";
		if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(descricao)) ILIKE UPPER(TRIM('%$descricao%'))";
		if ($item_aparencia == 'f') $sql .= " AND item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	}
	$res = pg_exec($con,$sql);
//if ($ip == '201.0.9.216') echo nl2br($sql);
	if (@pg_numrows($res) == 0) {
		if ($login_fabrica == 1) {
			echo "<h2>Item '$descricao' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
		}else{
			echo "<h1>Peça '$descricao' não encontrada<br>para o produto $produto_referencia</h1>";
		}
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',3000);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "referencia") {
	$referencia = trim(strtoupper($_GET["peca"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace(",","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);
	$referencia = str_replace(" ","",$referencia);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";
	echo "<br><br>";
	
	$res = pg_exec ($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result ($res,0,0);
	
	if ($qtde > 0 and strlen($produto) > 0) {
//if ($ip == '201.0.9.216') echo "Xii<br>";
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.type                                ,
						z.posicao                             ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao  
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.type               ,
								y.posicao            ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para 
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.type                                      ,
										x.posicao                                   ,
										tbl_peca_fora_linha.peca AS peca_fora_linha 
								FROM (
										SELECT  tbl_peca.peca            ,
												tbl_peca.referencia      ,
												tbl_peca.descricao       ,
												tbl_lista_basica.type    ,
												tbl_lista_basica.posicao 
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										JOIN tbl_produto      USING (produto)
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_produto.produto = $produto
										AND   tbl_peca.ativo IS TRUE
										AND   tbl_produto.ativo IS TRUE";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))";
		if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	}else{
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao  
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para 
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										tbl_peca_fora_linha.peca AS peca_fora_linha 
								FROM (
										SELECT  tbl_peca.peca       ,
												tbl_peca.referencia ,
												tbl_peca.descricao  
										FROM tbl_peca
										WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))";
		if ($item_aparencia == 'f') $sql .= " AND item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	}
	$res = @pg_exec($con,$sql);
//if ($ip == '201.0.9.216') echo nl2br($sql);

	if (@pg_numrows($res) == 0) {
		if ($login_fabrica == 1) {
			echo "<h2>Item '$referencia' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
		}else{
			echo "<h1>Peça '$referencia' não encontrada<br>para o produto $produto_referencia</h1>";
		}
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',3000);";
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

for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
	$peca            = trim(@pg_result($res,$i,peca));
	$peca_referencia = trim(@pg_result($res,$i,peca_referencia));
	$peca_descricao  = trim(@pg_result($res,$i,peca_descricao));
	$peca_descricao  = str_replace ('"','',$peca_descricao);
	$type            = trim(@pg_result($res,$i,type));
	$posicao         = trim(@pg_result($res,$i,posicao));
	$peca_fora_linha = trim(@pg_result($res,$i,peca_fora_linha));
	$peca_para       = trim(@pg_result($res,$i,peca_para));
	$para            = trim(@pg_result($res,$i,para));
	$para_descricao  = trim(@pg_result($res,$i,para_descricao));
	
	$resT = pg_exec($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");
	if (pg_numrows($resT) == 1) {
		$tabela = pg_result ($resT,0,0);
		if (strlen($para) > 0) {
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
		}else{
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
		}
		$resT = pg_exec($con,$sqlT);
		if (pg_numrows($resT) == 1) {
			$preco = number_format (pg_result($resT,0,0),2,",",".");
		}else{
			$preco = "";
		}
	}else{
		$preco = "";
	}
	
	echo "<tr>\n";

	if ($login_fabrica == 14) {
		echo "<td nowrap>";
		echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$posicao</font>";
		echo "</td>\n";
	}

	echo "<td nowrap>";
	echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_referencia</font>";
	echo "</td>\n";

	echo "<td nowrap>";
	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
		echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_descricao</font>";
	}else{
		echo "<a href=\"javascript: referencia.value='$peca_referencia'; descricao.value='$peca_descricao';";
		if ($login_fabrica == 14) echo " posicao.value='$posicao';";
		else                      echo " preco.value='$preco';";
		echo " this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
	}
	echo "</td>\n";

	if ($login_fabrica == 1) {
		echo "<td nowrap>";
		echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$type</font>";
		echo "</td>\n";
	}

	echo "<td nowrap>";
	if (strlen($peca_fora_linha) > 0) {
		echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
		if ($login_fabrica == 1) echo "É obsoleta,<br>não é mais fornecida";
		else                     echo "Fora de linha";
		echo "</b></font>";
	}else{
		if (strlen($para) > 0) {
			echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>Mudou Para:</b></font>";
			echo " <a href=\"javascript: referencia.value='$para'; descricao.value='$para_descricao'; preco.value='$preco'; this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$para</font></a>";
		}else{
			echo "&nbsp;";
		}
	}
	echo "</td>\n";

	echo "</tr>\n";
}

echo "</table>\n";
?>

</body>
</html>
