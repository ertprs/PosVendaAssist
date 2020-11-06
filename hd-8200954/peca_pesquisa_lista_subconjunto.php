<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

# verifica se posto pode ver pecas de itens de aparencia
$sql = "SELECT   tbl_posto_fabrica.item_aparencia
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

if (strlen(trim($_GET['produto'])) > 0) {
	$produto_referencia = trim($_GET['produto']);
	$produto_referencia = str_replace(".","",$produto_referencia);
	$produto_referencia = str_replace(",","",$produto_referencia);
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);

	$sql =	"SELECT tbl_produto.produto    ,
					tbl_produto.referencia ,
					tbl_produto.descricao  
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_produto.referencia_pesquisa = UPPER('$produto_referencia')
			AND    tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";
	$res = pg_exec ($con,$sql);
//if ($ip == '201.0.9.216') echo nl2br($sql)."<br><br>";
// echo "$sql";
	if (pg_numrows($res) == 1) {
		$produto            = pg_result ($res,0,produto);
		$produto_referencia = pg_result ($res,0,referencia);
		$produto_descricao  = pg_result ($res,0,descricao);
	}
}



if (strlen($produto_referencia) > 0 AND strlen($produto_descricao) > 0) {
	echo "<h4>Pesquisando toda a lista básica do produto: <br><i>$produto_referencia - $produto_descricao</i></h4>";

	echo "<br><br>";

	if (strlen($produto) > 0) {
		$sql =	"SELECT DISTINCT
						tbl_peca.referencia      ,
						tbl_peca.descricao       ,
						tbl_lista_basica.posicao 
				FROM    tbl_lista_basica
				JOIN    tbl_peca USING (peca)
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto 
				AND     tbl_peca.ativo IS NOT FALSE";
		if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
				if ($login_fabrica == 14) $sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
		$sql .= " ORDER BY tbl_peca.referencia;";
	}
	$res = pg_exec ($con,$sql);

	if (@pg_numrows($res) == 0) {
		echo "<h1>Nenhuma lista básica de peças encontrada para este produto</h1>";
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

echo "<table width='80%' border='0'>\n";

echo "<tr>\n";

echo "<td align='center'>";
echo "<strong>Posição</strong>";
echo "</td>\n";

echo "<td align='center'>";
echo "<strong>Código</strong>";
echo "</td>\n";

echo "<td align='left'>";
echo "<strong>Descrição</strong>";
echo "</td>\n";

echo "</tr>\n";

for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
	$peca_referencia = trim(@pg_result($res,$i,referencia));
	$peca_descricao  = trim(@pg_result($res,$i,descricao));
	$posicao         = trim(@pg_result($res,$i,posicao));

	$peca_descricao_js = strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;'));
	
	echo "<tr>\n";

	echo "<td nowrap align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$posicao</font>";
	echo "</td>\n";

	echo "<td nowrap align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_referencia</font>";
	echo "</td>\n";

	echo "<td nowrap>";
	echo "<a href=\"javascript: posicao.value='$posicao'; referencia.value='$peca_referencia'; descricao.value='$peca_descricao_js'; this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
	echo "</td>\n";

	echo "</tr>\n";
}

echo "</table>\n";
?>

</body>
</html>