<?php
header("Content-Type: text/html; charset=ISO-8859-1",true);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once('fn_traducao.php');

$serie   = $_GET['serie'];
$produto = $_GET['produto'];
$fabrica = $_GET['fabrica'];

// HD 3779808 - Valida NS bloqueado
if ($fabrica == 30) {
	$sql_serie = "
		SELECT tbl_produto_serie.produto_serie, tbl_produto_serie.observacao
		  FROM tbl_produto_serie
		 WHERE fabrica = $fabrica
		   AND produto = $produto
		   AND '$serie'::BIGINT
		       BETWEEN tbl_produto_serie.serie_inicial::BIGINT
		           AND tbl_produto_serie.serie_final::BIGINT
	";
	$res_serie = pg_query($con, $sql_serie);
	if (pg_num_rows($res_serie) > 0) {
		$observacao = traduz('Número de Série Bloqueado') .
			"\n" . pg_fetch_result($res_serie, 0, 'observacao');
		die("erro 1|$observacao");
	}
	$sql_valida = "SELECT fn_valida_esmaltec_serie('$serie',$produto,$fabrica)";
}

#HD 260769
if ($fabrica==85) {
	$sql_valida = "SELECT fn_valida_gelopar_serie('$serie',$produto,$fabrica)";
}

if ($sql_valida) {
	$res      = pg_query($con, $sql_valida);
	$msg_erro = pg_last_error($con);
}

if (strlen($msg_erro)==0) {
	$sql="SELECT tbl_numero_serie.numero_serie
		FROM   tbl_numero_serie
		WHERE  tbl_numero_serie.fabrica = $fabrica
		AND    tbl_numero_serie.produto = $produto
		AND    tbl_numero_serie.serie   = '$serie'";
	$res = @pg_exec($con,$sql);
	if (pg_numrows($res)==0) {
		echo 'erro 1|'.$serie;
	}
	else{
		echo 'ok';
	}
}
else{ 
	$msg_erro = str_replace('ERROR: ','',$msg_erro);

	$msg_erro = trim(substr($msg_erro,0,16));

	if ($msg_erro=='Número de série'){
		echo 'erro 1|'.$serie.'|'.$msg_erro;
	}
	else{
		echo 'erro 2|'.$serie.'|'.$msg_erro;
	}
}

// Número de série inválido para o produto 060201520012
// ERROR: Número de série 6xx23456789012 inválido para o produto 060201520012!

