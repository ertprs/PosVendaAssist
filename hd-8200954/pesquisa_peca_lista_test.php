<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$seq = trim($HTTP_GET_VARS["seq"]);
?>

<html>
<head>
<title> Pesquisa Peças pela Lista Basica... </title>
<meta http-equiv=pragma content=no-cache>
</head>


<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_pecas.gif">

<script language="JavaScript">
<? if ($retorno == "/assist/os_item_britania.php") { ?>
<!--
function retorno(referencia,valor) {
	peca.value  = referencia;
	preco.value = valor;
	opener.document.frm_os.qtde_<?echo $seq?>.focus();
	window.close();
}
// -->
<? } ?>

<? if ($retorno == "/assist/os_item_meteor.php") { ?>
<!--
function retorno(referencia,valor) {
	peca.value  = referencia;
	preco.value = valor;
	opener.document.frm_os.qtde_<?echo $seq?>.focus();
	window.close();
}
// -->
<? } ?>

<? if ($retorno == "/assist/os_item_dynacom.php") { ?>
<!--
function retorno(referencia,valor) {
	peca.value  = referencia;
	preco.value = valor;
	opener.document.frm_os.defeito_<?echo $seq?>.focus();
	window.close();
}
// -->
<? } ?>

<? if ($retorno == "/assist/pedido_cadastro.php") { ?>
<!--
function retorno(referencia,descr,valor,qtd) {
	peca.value      = referencia;
	descricao.value = descr;
	preco.value     = valor;
	qtde.value      = qtd;
	opener.document.frm_pedido.qtde_<?echo $seq?>.focus();
	window.close();
}
// -->
<? } ?>
</script>

<br>

<?
$linha = $_GET['linha'];

if (strlen($HTTP_GET_VARS["peca"]) == 0 and strlen($HTTP_GET_VARS["descricao"]) == 0) {
	$produto_referencia = strtoupper($HTTP_GET_VARS["produto"]);

	$sql = "SELECT  tbl_produto.produto,
					tbl_produto.descricao
			FROM    tbl_produto
			JOIN    tbl_linha USING (linha)
			WHERE   tbl_produto.referencia = '$produto_referencia'
			AND     tbl_linha.fabrica      = $login_fabrica;";
	$res = @pg_exec ($con,$sql);

	$produto   = @pg_result ($res,0,0) ;
	$descricao = @pg_result ($res,0,1) ;

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando peca na lista básica do produto <b>$descricao</b></font>";
	echo "<p>";
	flush();

	$sql = "SELECT      DISTINCT
						tbl_peca.referencia AS peca,
						tbl_peca.descricao         ,
						tbl_tabela_item.preco      ,
						tbl_lista_basica.qtde
			FROM        tbl_tabela
			JOIN        tbl_tabela_item  using (tabela)
			JOIN        tbl_posto_linha  using (tabela)
			JOIN        tbl_lista_basica using (peca)
			JOIN        tbl_peca         using (peca)
			WHERE       tbl_posto_linha.posto    = $login_posto
			AND         tbl_lista_basica.produto = $produto
			AND         tbl_tabela.fabrica       = $login_fabrica;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça não encontrada na Lista Básica do $descricao </h1>";
		echo "<script language='javascript'>";
		if ($retorno == "/assist/os_item_britania.php") {
			echo "setTimeout('descricao.value=\"\"',2500);";
		}
		if ($retorno == "/assist/os_item_meteor.php") {
			echo "setTimeout('descricao.value=\"\"',2500);";
		}
		if ($retorno == "/assist/os_item_dynacom.php") {
			echo "setTimeout('peca.value=\"\"',2500);";
		}
		if ($retorno == "/assist/pedido_cadastro.php") {
			echo "setTimeout('descricao.value=\"\"',2500);";
		}
		echo "</script>";
		exit;
	}
}

if (strlen($HTTP_GET_VARS["peca"]) > 0) {

	$produto_referencia = strtoupper($HTTP_GET_VARS["produto"]);
	$peca               = strtoupper($HTTP_GET_VARS["peca"]);

	$sql = "SELECT  tbl_produto.produto,
					tbl_produto.descricao
			FROM    tbl_produto
			JOIN    tbl_linha USING (linha)
			WHERE   tbl_produto.referencia = '$produto_referencia'
			AND     tbl_linha.fabrica      = $login_fabrica;";
	$res = @pg_exec ($con,$sql);

	$produto   = @pg_result ($res,0,0) ;
	$descricao = @pg_result ($res,0,1) ;

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando peca por <b>$peca</b> na lista básica do produto <b>$descricao</b></font>";
	echo "<p>";
	flush();

	$sql = "SELECT      DISTINCT
						tbl_peca.referencia AS peca,
						tbl_peca.descricao         ,
						tbl_tabela_item.preco      ,
						tbl_lista_basica.qtde
			FROM        tbl_tabela
			JOIN        tbl_tabela_item  using (tabela)
			JOIN        tbl_posto_linha  using (tabela)
			JOIN        tbl_lista_basica using (peca)
			JOIN        tbl_peca         using (peca)
			WHERE       (tbl_peca.referencia_pesquisa ilike '%$peca%' or tbl_peca.descricao ilike '%$peca%')
			AND         tbl_posto_linha.posto    = $login_posto
			AND         tbl_lista_basica.produto = $produto
			AND         tbl_tabela.fabrica       = $login_fabrica;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$peca' não encontrada na Lista Básica do $descricao </h1>";
		echo "<script language='javascript'>";
		if ($retorno == "/assist/os_item_britania.php") {
			echo "setTimeout('peca.value=\"\"',2500);";
		}
		if ($retorno == "/assist/os_item_meteor.php") {
			echo "setTimeout('peca.value=\"\"',2500);";
		}
		if ($retorno == "/assist/os_item_dynacom.php") {
			echo "setTimeout('peca.value=\"\"',2500);";
		}
		if ($retorno == "/assist/pedido_cadastro.php") {
			echo "setTimeout('peca.value=\"\"',2500);";
		}
		echo "</script>";
		exit;
	}
}

if (strlen($HTTP_GET_VARS["descricao"]) > 0) {
	$produto_referencia = strtoupper($HTTP_GET_VARS["produto"]);
	$descr_peca         = strtoupper($HTTP_GET_VARS["descricao"]);

	$sql = "SELECT  tbl_produto.produto,
					tbl_produto.descricao
			FROM    tbl_produto
			JOIN    tbl_linha USING (linha)
			WHERE   tbl_produto.referencia_pesquisa = '$produto_referencia'
			AND     tbl_linha.fabrica      = $login_fabrica;";
	$res = @pg_exec ($con,$sql);

	$produto   = @pg_result ($res,0,0) ;
	$descricao = @pg_result ($res,0,1) ;

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando peca por <b>$descr_peca</b> na lista básica do produto <b>$descricao</b></font>";
	echo "<p>";
	flush();

	$sql = "SELECT      DISTINCT
						tbl_peca.referencia AS peca,
						tbl_peca.descricao         ,
						tbl_tabela_item.preco      ,
						tbl_lista_basica.qtde
			FROM        tbl_tabela
			JOIN        tbl_tabela_item  using (tabela)
			JOIN        tbl_posto_linha  using (tabela)
			JOIN        tbl_lista_basica using (peca)
			JOIN        tbl_peca         using (peca)
			WHERE       (tbl_peca.referencia_pesquisa ilike '%$descr_peca%' or tbl_peca.descricao ilike '%$descr_peca%')
			AND         tbl_posto_linha.posto    = $login_posto
			AND         tbl_lista_basica.produto = $produto
			AND         tbl_tabela.fabrica       = $login_fabrica;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Descrição '$descr_peca' não encontrada na Lista Básica do $descricao </h1>";
		echo "<script language='javascript'>";
		if ($retorno == "/assist/os_item_britania.php") {
			echo "setTimeout('descricao.value=\"\"',2500);";
		}
		if ($retorno == "/assist/os_item_meteor.php") {
			echo "setTimeout('descricao.value=\"\"',2500);";
		}
		if ($retorno == "/assist/os_item_dynacom.php") {
			echo "setTimeout('peca.value=\"\"',2500);";
		}
		if ($retorno == "/assist/pedido_cadastro.php") {
			echo "setTimeout('descricao.value=\"\"',2500);";
		}
		echo "</script>";
		exit;
	}
}

if (@pg_numrows ($res) == 1 ) {
	$peca      = trim(pg_result($res,0,peca));
	$descricao = trim(pg_result($res,0,descricao));
	$qtde      = trim(pg_result($res,0,qtde));
	$preco     = trim(pg_result($res,0,preco));
	$preco     = number_format($preco,2,",",".");

	if (strlen($preco) == 0) $preco = 0;
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	if ($retorno == "/assist/os_item_britania.php") {
		echo "peca.value  = '$peca'; \n";
		echo "preco.value = '$preco'; \n";
		echo "opener.document.frm_os.qtde_$seq.focus();";
	}
	if ($retorno == "/assist/os_item_meteor.php") {
		echo "peca.value  = '$peca'; \n";
		echo "preco.value = '$preco'; \n";
		echo "opener.document.frm_os.qtde_$seq.focus();";
	}
	if ($retorno == "/assist/os_item_dynacom.php") {
		echo "peca.value  = '$peca'; \n";
		echo "preco.value = '$preco'; \n";
		echo "opener.document.frm_os.defeito_$seq.focus();";
	}
	if ($retorno == "/assist/pedido_cadastro.php") {
		echo "peca.value       = '$peca'; \n";
		echo "descricao.value  = '$peca'; \n";
		echo "preco.value      = '$preco'; \n";
		echo "qtde.value       = '$qtde'; \n";
		echo "opener.document.frm_pedido.qtde_$seq.focus();";
	}
	echo "window.close();\n";
	echo "// -->\n";
	echo "</script>\n";
}else{
	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$peca      = trim(pg_result($res,$i,peca));
		$descricao = trim(pg_result($res,$i,descricao));
		$qtde      = trim(pg_result($res,$i,qtde));
		$preco     = trim(pg_result($res,$i,preco));
		$preco     = number_format($preco,2,",",".");

		if (strlen($preco) == 0) $preco = 0;

		echo "<tr>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$peca</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		if ($retorno == "/assist/os_item_britania.php") {
			echo "<a href=\"javascript: retorno('$peca','$preco')\">\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
		}
		if ($retorno == "/assist/os_item_meteor.php") {
			echo "<a href=\"javascript: retorno('$peca','$preco')\">\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
		}
		if ($retorno == "/assist/os_item_dynacom.php") {
			echo "<a href=\"javascript: retorno('$peca','$preco')\">\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
		}
		if ($retorno == "/assist/pedido_cadastro.php") {
			echo "<a href=\"javascript: retorno('$peca','$descricao','$preco','$qtde')\">\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
		}
		echo "</td>\n";

		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>