<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$retorno = trim($HTTP_GET_VARS["retorno"]);
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Produtos... </title>
<meta http-equiv=pragma content=no-cache>
</head>


<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_produtos.gif">


<script language="JavaScript">

<? 	if ($retorno == "/assist/tabela_precos.php") { ?>
	function retorno(refer,descr,tabel) {
		referencia.value = refer;
		descricao.value  = descr;
		tabela.value     = tabel;
		opener.document.frm_tabela.descricao_produto.focus();
		window.close();
	}

<?}?>

<? if ($retorno == "/assist/pedido_cadastro.php") { ?>

function retorno(prod,refer,descr) {
	produto.value    = prod;
	referencia.value = refer;
	descricao.value  = descr;
	opener.document.frm_pedido.peca_referencia_0.focus();
	window.close();
}

<? } ?>

<? if ($retorno == "/assist/os_cadastro_meteor.php") { ?>

function retorno(refer,descr) {
	referencia.value = refer;
	descricao.value  = descr;
	opener.document.frm_os.produto_serie.focus();
	window.close();
}

<? } ?>

<? if ($retorno == "/assist/os_cadastro_britania.php") { ?>

function retorno(refer,descr) {
	referencia.value = refer;
	descricao.value  = descr;
	opener.document.frm_os.produto_serie.focus();
	window.close();
}

<? } ?>

<? if ($retorno == "/assist/os_cadastro_dynacom.php") { ?>

function retorno(refer,descr) {
	referencia.value = refer;
	descricao.value  = descr;
	opener.document.frm_os.produto_serie.focus();
	window.close();
}

<? } ?>


<? if ($retorno == "/assist/os_cadastro_mondial.php") { ?>

function retorno(refer,descr) {
	referencia.value = refer;
	descricao.value  = descr;
	opener.document.frm_os.produto_serie.focus();
	window.close();
}

<? } ?>

</script>



<br>

<?
if (strlen($HTTP_GET_VARS["referencia"]) == 0 and strlen($HTTP_GET_VARS["descricao"]) == 0) {
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando produto</font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_produto.produto   ,
						tbl_produto.referencia,
						tbl_produto.descricao ,
						tbl_tabela.tabela
			FROM        tbl_produto
			JOIN        tbl_linha       ON tbl_produto.linha     = tbl_linha.linha
			JOIN        tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
			JOIN        tbl_tabela      ON tbl_tabela.tabela     = tbl_posto_linha.tabela
			WHERE       tbl_posto_linha.posto = $login_posto
			ORDER BY    tbl_produto.descricao;";

	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto não encontrado</h1>";
		echo "<script language='javascript'>";
		if ($retorno == "/assist/tabela_precos.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",tabela.value=\"\",2500);";
		}
		if ($retorno == "/assist/pedido_cadastro.php") {
			echo "setTimeout(produto.value=\"\",'referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_meteor.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_britania.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_dynacom.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_mondial.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}

		echo "</script>";
		exit;
	}
}

if (strlen($HTTP_GET_VARS["referencia"]) > 0) {
	$referencia = strtoupper($HTTP_GET_VARS["referencia"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_produto.produto   ,
						tbl_produto.referencia,
						tbl_produto.descricao ,
						tbl_tabela.tabela
			FROM        tbl_produto
			JOIN        tbl_linha       ON tbl_produto.linha     = tbl_linha.linha
			JOIN        tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
			JOIN        tbl_tabela      ON tbl_tabela.tabela     = tbl_posto_linha.tabela
			WHERE       (tbl_produto.referencia ilike '%$referencia%' OR tbl_produto.descricao ilike '%$referencia%')
			AND         tbl_posto_linha.posto = $login_posto
			ORDER BY    tbl_produto.descricao;";
//if ($ip == "200.228.76.116") echo $sql;

	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		if ($retorno == "/assist/tabela_precos.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",tabela.value=\"\",2500);";
		}
		if ($retorno == "/assist/pedido_cadastro.php") {
			echo "setTimeout(produto.value=\"\",'referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_meteor.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_britania.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_dynacom.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_mondial.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		echo "</script>";
		exit;
	}
}

if (strlen($HTTP_GET_VARS["descricao"]) > 0) {
	$descricao = strtoupper($HTTP_GET_VARS["descricao"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição do produto</b>: <i>$descricao</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_produto.produto   ,
						tbl_produto.referencia,
						tbl_produto.descricao ,
						tbl_tabela.tabela
			FROM        tbl_produto
			JOIN        tbl_linha       ON tbl_produto.linha     = tbl_linha.linha
			JOIN        tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
			JOIN        tbl_tabela      ON tbl_tabela.tabela     = tbl_posto_linha.tabela
			WHERE       (tbl_produto.referencia ilike '%$descricao%' OR tbl_produto.descricao ilike '%$descricao%')
			AND         tbl_posto_linha.posto = $login_posto
			ORDER BY    tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Descrição '$descricao' não encontrado</h1>";
		echo "<script language='javascript'>";
		if ($retorno == "/assist/tabela_precos.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",tabela.value=\"\",2500);";
		}
		if ($retorno == "/assist/pedido_cadastro.php") {
			echo "setTimeout(produto.value=\"\",'referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_meteor.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_britania.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_dynacom.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		if ($retorno == "/assist/os_cadastro_mondial.php") {
			echo "setTimeout('referencia.value=\"\",descricao.value=\"\",2500);";
		}
		echo "</script>";
		exit;
	}
}

if (@pg_numrows ($res) == 1 ) {
	$produto    = trim(pg_result($res,0,produto));
	$referencia = trim(pg_result($res,0,referencia));
	$descricao  = trim(pg_result($res,0,descricao));
	$tabela     = trim(pg_result($res,0,tabela));
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	if ($retorno == "/assist/tabela_precos.php") {
		echo "referencia.value = '$referencia';\n";
		echo "descricao.value  = '$descricao';\n";
		echo "tabela.value     = '$tabela';\n";
		echo "opener.document.frm_tabela.descricao_produto.focus();";
	}
	if ($retorno == "/assist/pedido_cadastro.php") {
		echo "produto.value    = '$produto';\n";
		echo "referencia.value = '$referencia';\n";
		echo "descricao.value  = '$descricao';\n";
		echo "opener.document.frm_tabela.peca_referencia_0.focus();";
	}
	if ($retorno == "/assist/os_cadastro_meteor.php") {
		echo "referencia.value = '$referencia';\n";
		echo "descricao.value  = '$descricao';\n";
		echo "opener.document.frm_os.produto_serie.focus();";
	}
	if ($retorno == "/assist/os_cadastro_britania.php") {
		echo "referencia.value = '$referencia';\n";
		echo "descricao.value  = '$descricao';\n";
		echo "opener.document.frm_os.produto_serie.focus();";
	}
	if ($retorno == "/assist/os_cadastro_dynacom.php") {
		echo "referencia.value = '$referencia';\n";
		echo "descricao.value  = '$descricao';\n";
		echo "opener.document.frm_os.produto_serie.focus();";
	}
	if ($retorno == "/assist/os_cadastro_mondial.php") {
		echo "referencia.value = '$referencia';\n";
		echo "descricao.value  = '$descricao';\n";
		echo "opener.document.frm_os.produto_serie.focus();";
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
		$produto    = trim(pg_result($res,$i,produto));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$tabela     = trim(pg_result($res,$i,tabela));
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$referencia</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		if ($retorno == "/assist/tabela_precos.php") {
			echo "<a href=\"javascript: retorno('$referencia','$descricao','$tabela')\">\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
		}
		if ($retorno == "/assist/pedido_cadastro.php") {
			echo "<a href=\"javascript: retorno('$produto','$referencia','$descricao')\">\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
		}
		if ($retorno == "/assist/os_cadastro_meteor.php") {
			echo "<a href=\"javascript: retorno('$referencia','$descricao')\">\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
		}
		if ($retorno == "/assist/os_cadastro_britania.php") {
			echo "<a href=\"javascript: retorno('$referencia','$descricao')\">\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
		}
		if ($retorno == "/assist/os_cadastro_mondial.php") {
			echo "<a href=\"javascript: retorno('$referencia','$descricao')\">\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
		}
		if ($retorno == "/assist/os_cadastro_dynacom.php") {
			echo "<a href=\"javascript: retorno('$referencia','$descricao')\">\n";
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