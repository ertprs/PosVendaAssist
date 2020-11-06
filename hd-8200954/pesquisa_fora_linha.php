<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peça Fora de Linha... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
</head>

<body>

<br>

<?

if (strlen($HTTP_GET_VARS["nome"]) > 0) {
	$nome  = strtoupper(trim($HTTP_GET_VARS["nome"]));
	$seq   = trim($HTTP_GET_VARS["seq"]);
	$achou = "nao";
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$nome</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_fora_linha.referencia
			FROM     tbl_fora_linha
			WHERE    trim(tbl_fora_linha.referencia) = trim('$nome');";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) > 0) {
		$achou = "sim";
		
		echo "<h2>PEÇA '$nome' OBSOLETA, ITEM NÃO É MAIS FORNECIDO.</h2>";
		echo "<script language=\"JavaScript\">\n";
		echo "setTimeout('opener.window.document.frmpedido.referencia$seq.value=\"\",opener.window.document.frmpedido.referencia$seq.focus()',1500);";
		echo "</script>";
		exit;
	}
	
	$sql = "SELECT   tbl_depara.para
			FROM     tbl_depara
			WHERE    trim(tbl_depara.de) = trim('$nome');";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) > 0) {
		$achou = "sim";
		$para = trim(pg_result($res,0,para));
		
		$sql = "SELECT fnc_depara('$nome');";
		$res = @pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$achou = "sim";
			$para  = trim(pg_result($res,0,0));
			
			if ($para == "FALSO") $achou = "nao";
		}
		
		if ($achou == "sim") {
			echo "<h2>PEÇA '$nome' SUBSTITUÍDA PELA '$para'</h2>";
			echo "<script language=\"JavaScript\">\n";
			echo "setTimeout('opener.window.document.frmpedido.referencia$seq.value=\"$para\",opener.window.document.frmpedido.qtde$seq.focus()',1500);";
			echo "</script>";
			exit;
		}
	}
	
	$sql = "SELECT   tbl_peca_analise.referencia
			FROM     tbl_peca_analise
			WHERE    trim(tbl_peca_analise.referencia) = trim('$nome');";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) > 0) {
		$achou = "sim";
		
		echo "<h2>PEÇA '$nome' em Análise</h2>";
		echo "<script language=\"JavaScript\">\n";
		echo "setTimeout('opener.window.document.frmpedido.referencia$seq.value=\"\",opener.window.document.frmpedido.qtde$seq.focus()',1500);";
		echo "</script>";
		exit;
	}
	
	if ($achou == "nao") {
		$xreferencia = str_replace(" ","",$nome);
		$xreferencia = str_replace("-","",$xreferencia);
		$xreferencia = str_replace(".","",$xreferencia);
		$xreferencia = str_replace("/","",$xreferencia);

		$sql = "SELECT   tbl_peca.referencia
				FROM     tbl_peca
				WHERE    trim(tbl_peca.referencia_pesquisa) = trim('$xreferencia');";
		$res = @pg_exec ($con,$sql);
		
		if (@pg_numrows ($res) == 0) {
			$achou = "sim";
			echo "<h2>PEÇA '$nome' não existe. Favor informar uma referência válida.</h2>";
			echo "<script language=\"JavaScript\">\n";
			echo "setTimeout('opener.window.document.frmpedido.referencia$seq.value=\"\",opener.window.document.frmpedido.referencia$seq.focus()',1500);";
			echo "</script>";
			exit;
		}
	}
	
	if ($achou == "nao") {
		if ($nome == 'DE0245-QW' or
			$nome == 'DW0245'    or
			$nome == 'DW0247'    or
			$nome == 'DW9116-BR' or
			$nome == 'DW9116-B2' or
			$nome == 'DW9057'    or
			$nome == 'DW9061'    or
			$nome == 'DW9071'    or
			$nome == 'DW9091'    or
			$nome == 'DW9096'    or
			$nome == 'DW0240') {
			
			$achou = "sim";
			echo "<h2>Item '$nome' deverá ser solicitado via e-mail.</h2>";
			echo "<script language=\"JavaScript\">\n";
			echo "setTimeout('opener.window.document.frmpedido.referencia$seq.value=\"\",opener.window.document.frmpedido.referencia$seq.focus()',1500);";
			echo "</script>";
			exit;
		}
	}
	
	if ($achou == "nao") {
		echo "<script language=\"JavaScript\">\n";
		echo "window.close();\n";
		echo "</script>";
		exit;
	}
}
?>

</body>
</html>