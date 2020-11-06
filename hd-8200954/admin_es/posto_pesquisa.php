<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
// include 'cabecalho_pop_postos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title> Busca Servicio... </title>
	<meta name="Author" content="">
	<meta name="Keywords" content="">
	<meta name="Description" content="">
	<meta http-equiv=pragma content=no-cache>
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
	<style type="text/css">
	body,img {margin:0;padding:0}
	h1 {font-size: 15px}
	h2,h3 {font-size: 14px}
	h4 {font-size: 13px}
	table tr td a {color: blue; font-weight: bold;}
	table tr td span {color: #808080;font-size:11px;font-style: italic;}
    </style>
</head>
<body onblur="setTimeout('window.close()',3000);"> <!---->
<img src="../imagens/pesquisa_posto_es.gif">
<br>

<?
$tipo = trim(strtolower($_GET['tipo']));

if ($tipo == 'nome') {
	$nome = trim (strtoupper($_GET['campo']));

	echo "<h4>Buscando por <b>nombre del Servicio</b>: <i>$nome</i></h4>";

	$sem_res	= "<h1>Servicio '$nome' no localizado</h1>";
	$sql_where ="WHERE    (tbl_posto.nome ILIKE '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			AND      tbl_posto.pais            = '$login_pais'
			ORDER BY tbl_posto.nome";
}

if ($tipo == "cnpj") {
	$cnpj = preg_replace('/\W/', '', trim(strtoupper($_GET["campo"])));
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por <b>Identificación del servicio</b>: <i>$cnpj</i></font>";

	$sem_res	= "<h1>ID Fiscal '$cnpj' no localizada.</h1>";
	$sql_where	= "WHERE    (tbl_posto.cnpj ILIKE '%$cnpj%' OR tbl_posto_fabrica.codigo_posto ILIKE '%$cnpj%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			AND      tbl_posto.pais            = '$login_pais'
			ORDER BY tbl_posto.nome";
}

if ($tipo == "codigo") {
	$codigo = trim (strtoupper($_GET["campo"]));

	echo "<h4>Buscando por el <b>Código del Servicio</b>: <i>$codigo</i></h4>";

	$sem_res	= "<h1>Servicio '$codigo' no localizado</h1>";
	$sql_where	= "WHERE    tbl_posto_fabrica.codigo_posto ILIKE '%$codigo%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			AND      tbl_posto.pais            = '$login_pais'
			ORDER BY tbl_posto.nome";
}

$sql = "SELECT   tbl_posto.posto,
				 tbl_posto.nome,
				 tbl_posto.cnpj,
				 tbl_posto_fabrica.contato_cidade AS cidade,
				 tbl_posto_fabrica.contato_estado AS estado,
				 tbl_posto_fabrica.nome_fantasia
		FROM     tbl_posto
		JOIN     tbl_posto_fabrica USING (posto)
		$sql_where";
	$res = pg_query($con, $sql);
	if (is_resource($res)) $tot = pg_num_rows($res);

	if ($tot == 0) {?>
	<?=$sem_res?>
	<script language='javascript'>
		setTimeout('window.close()', 3000);
	</script>
</body>
</html>
<?		exit;
	}
?>
<script language='JavaScript'>
	this.focus();
</script>

<table width='100%' border='1' style='margin:auto;width:98%;font-family:Arial, Verdana, Times, Sans serif;font-size:12px;color:black'>
<?
	for ( $i = 0 ; $i < $tot; $i++ ) {
	  	extract(pg_fetch_assoc($res, $i));
		$nome		= str_replace('"', '', $nome);
		$cidade		= str_replace('"', '', $cidade);
		$estado		= str_replace('"', '', $estado);
?>
	<tr>
		<td nowrap><?=$cnpj?></td>
		<td>
<?	if ($_GET['forma'] == 'reload') {
		echo "<a href=\"javascript: janela = opener.document.location.href ; posicao = janela.lastIndexOf('.') ; janela = janela.substring(0,posicao+4) ; opener.document.location = janela + '?posto=$posto' ; this.close() ;\" > " ;
	}else{
		echo "<a href=\"javascript: nome.value = '$nome' ; cnpj.value = '$cnpj' ; this.close() ; \" >";
	}
	echo $nome.'</a>';
	if (strlen (trim ($nome_fantasia)) > 0) echo "<br><span>$nome_fantasia</span>";
?>
		</td>
		<td><?=$cidade?></td>
<?/*
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$estado</font>\n";
	echo "</td>\n";
*/?>
	</tr>
<?	}?>
</table>
</body>
</html>
