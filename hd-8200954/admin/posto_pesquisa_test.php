<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'cabecalho_pop_postos.php';

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Postos Autorizados... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

 <style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}


</style>
</head>

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">

<br>

<?
if($login_fabrica == 50 or $login_fabrica == 7){
	$programa_aberto= $_GET['exibe'];
	
	if($login_fabrica == 50){
		if(preg_match("excecao_cadastro.php", $programa_aberto)){
			$excecao = 't';
		}
	}

	if($login_fabrica == 7){
		if(preg_match("pedido_cadastro", $programa_aberto)){
			$pedido_cadastro = 't';
		}
	}
}
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "nome") {
	$nome = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Pesquisando por <b>Nome do Posto</b>: <i>$nome</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)";
			if ($excecao == 't') {
				$sql .= " LEFT JOIN tbl_excecao_mobra ON tbl_posto_fabrica.posto = tbl_excecao_mobra.posto AND tbl_posto_fabrica.fabrica=tbl_excecao_mobra.fabrica ";
			}
			$sql .= "WHERE    (tbl_posto.nome ilike '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";

	/* HD 47785 */
	if($login_fabrica == 7 AND $pedido_cadastro == 't'){
		$sql = "SELECT DISTINCT *
				FROM (
					SELECT	tbl_posto.posto,
							tbl_posto.nome,
							tbl_posto.cnpj,
							tbl_posto.pais,
							tbl_posto_fabrica.contato_cidade,
							tbl_posto_fabrica.contato_estado,
							tbl_posto.nome_fantasia,
							tbl_posto.nome_fantasia            AS nome_fantasia,
							tbl_excecao_mobra.mao_de_obra,
							tbl_excecao_mobra.adicional_mao_de_obra,
							tbl_excecao_mobra.percentual_mao_de_obra
					FROM     tbl_posto
					JOIN     tbl_posto_fabrica USING (posto)
					LEFT JOIN tbl_excecao_mobra ON tbl_posto_fabrica.posto = tbl_excecao_mobra.posto AND tbl_posto_fabrica.fabrica=tbl_excecao_mobra.fabrica 
					WHERE    (tbl_posto.nome ilike '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
					AND      tbl_posto_fabrica.fabrica = $login_fabrica
					ORDER BY tbl_posto.nome
				) postos
				UNION (
					SELECT	tbl_posto.posto,
							tbl_posto.nome,
							tbl_posto.cnpj,
							tbl_posto.pais,
							tbl_posto.cidade AS contato_cidade,
							tbl_posto.estado AS contato_estado,
							tbl_posto.nome_fantasia,
							tbl_posto.nome_fantasia as nome_fantasia,
							NULL                             AS mao_de_obra,
							NULL                             AS adicional_mao_de_obra,
							NULL                             AS percentual_mao_de_obra
					FROM     tbl_posto
					JOIN     tbl_posto_consumidor USING (posto)
					WHERE    (tbl_posto.nome ilike '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
					AND      tbl_posto_consumidor.fabrica = $login_fabrica
					ORDER BY tbl_posto.nome
				) 
				";
	}
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Posto '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "cnpj") {
	$cnpj = trim (strtoupper($_GET["campo"]));
	$cnpj = str_replace (".","",$cnpj);
	$cnpj = str_replace ("-","",$cnpj);
	$cnpj = str_replace ("/","",$cnpj);
	$cnpj = str_replace (" ","",$cnpj);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do Posto</b>: <i>$cnpj</i></font>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)";
			if ($excecao == 't') {
				$sql .= " LEFT JOIN tbl_excecao_mobra ON tbl_posto_fabrica.posto = tbl_excecao_mobra.posto AND tbl_posto_fabrica.fabrica=tbl_excecao_mobra.fabrica ";
			}
			$sql .= "WHERE    (tbl_posto.cnpj ILIKE '%$cnpj%' OR tbl_posto_fabrica.codigo_posto ILIKE '%$cnpj%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";
	
	/*$sql = "SELECT      tbl_posto.posto ,
						tbl_posto.nome  ,
						tbl_posto.cnpj  ,
						tbl_posto.cidade,
						tbl_posto.estado
			FROM        tbl_posto
			JOIN        tbl_posto_fabrica USING (posto)
			WHERE      (tbl_posto.cnpj ILIKE '%$cnpj%' OR tbl_posto_fabrica.codigo_posto ILIKE '%$cnpj%')
			GROUP BY	tbl_posto.posto ,
						tbl_posto.nome  ,
						tbl_posto.cnpj  ,
						tbl_posto.cidade,
						tbl_posto.estado
			ORDER BY tbl_posto.nome";*/

	/* HD 47785 */
	if($login_fabrica == 7 AND $pedido_cadastro == 't'){
		$sql = "SELECT DISTINCT *
				FROM (
					SELECT	tbl_posto.posto,
							tbl_posto.nome,
							tbl_posto.cnpj,
							tbl_posto.pais,
							tbl_posto_fabrica.contato_cidade,
							tbl_posto_fabrica.contato_estado,
							tbl_posto.nome_fantasia,
							tbl_posto.nome_fantasia            AS nome_fantasia,
							tbl_excecao_mobra.mao_de_obra,
							tbl_excecao_mobra.adicional_mao_de_obra,
							tbl_excecao_mobra.percentual_mao_de_obra
					FROM     tbl_posto
					JOIN     tbl_posto_fabrica USING (posto)
					LEFT JOIN tbl_excecao_mobra ON tbl_posto_fabrica.posto = tbl_excecao_mobra.posto AND tbl_posto_fabrica.fabrica=tbl_excecao_mobra.fabrica 
					WHERE     (tbl_posto.cnpj ILIKE '%$cnpj%' OR tbl_posto_fabrica.codigo_posto ILIKE '%$cnpj%')
					AND      tbl_posto_fabrica.fabrica = $login_fabrica
					ORDER BY tbl_posto.nome
				) postos
				UNION (
					SELECT	tbl_posto.posto,
							tbl_posto.nome,
							tbl_posto.cnpj,
							tbl_posto.pais,
							tbl_posto.cidade AS contato_cidade,
							tbl_posto.estado AS contato_estado,
							tbl_posto.nome_fantasia,
							tbl_posto.nome_fantasia as nome_fantasia,
							NULL                             AS mao_de_obra,
							NULL                             AS adicional_mao_de_obra,
							NULL                             AS percentual_mao_de_obra
					FROM     tbl_posto
					JOIN     tbl_posto_consumidor USING (posto)
					WHERE     (tbl_posto.cnpj ILIKE '%$cnpj%' OR tbl_posto_consumidor.codigo ILIKE '%$cnpj%')
					AND      tbl_posto_consumidor.fabrica = $login_fabrica
					ORDER BY tbl_posto.nome
				) 
				";
	}
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>CNPJ '$cnpj' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		
		exit;
	}
}

$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "codigo") {
	$codigo = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Pesquisando por <b>Código do Posto</b>: $codigo</h4>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    tbl_posto_fabrica.codigo_posto ilike '%$codigo%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Posto '$codigo' não encontrado</h1>";
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

echo "<table width='100%' border='0' class='tabela'>\n";

for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	$posto      = trim(pg_result($res,$i,posto));
	$nome       = trim(pg_result($res,$i,nome));
	$cnpj       = trim(pg_result($res,$i,cnpj));
	$cidade     = trim(pg_result($res,$i,contato_cidade));
	$estado     = trim(pg_result($res,$i,contato_estado));
	$fantasia   = trim(pg_result($res,$i,nome_fantasia)) . " " . trim(pg_result($res,$i,nome_fantasia));
	
	$nome = str_replace ('"','',$nome);
	$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
	$cidade = str_replace ('"','',$cidade);
	$estado = str_replace ('"','',$estado);
	$pais     = trim(pg_result($res,$i,pais));
	if ($excecao == 't') {
	$mobra = trim(pg_result($res,$i,mao_de_obra));
	$adicional_mobra = trim(pg_result($res,$i,adicional_mao_de_obra));
	$percentual_mobra = trim(pg_result($res,$i,percentual_mao_de_obra));
	}

				/*Retira todos usuários do TIME

07/10/2009  comentado porque não estava retornando postos ativos cadastrados na fábrica 10
				$sql = "SELECT *
						FROM  tbl_empresa_cliente
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;
				$sql = "SELECT *
						FROM  tbl_empresa_fornecedor
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;
*/
				$sql = "SELECT *
						FROM  tbl_erp_login
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;



	echo "<tr>\n";
	
	echo "<td nowrap>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
	echo "</td>\n";
	
	echo "<td>\n";
	if ($_GET['forma'] == 'reload') {
		echo "<a href=\"javascript: janela = opener.document.location.href ; posicao = janela.lastIndexOf('.') ; janela = janela.substring(0,posicao+4) ; opener.document.location = janela + '?posto=$posto' ; this.close() ;\" > " ;
	}else{
		echo "<a href=\"javascript: nome.value = '$nome' ; cnpj.value = '$cnpj' ; ";
		if ($excecao == 't') {
			echo "mobra.value = '$mobra' ; adicional_mobra.value = '$adicional_mobra' ; percentual_mobra.value = '$percentual_mobra' ;";
		}
		echo " this.close() ; \" >";
	}
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
	echo "</a>\n";
	if (strlen (trim ($fantasia)) > 0) echo "<br><font color='#808080' size='-1'>$fantasia</font>";
	echo "</td>\n";
	
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cidade</font>\n";
	echo "</td>\n";
	
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$estado</font>\n";
	echo "</td>\n";

	if($login_fabrica == 20){
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#990000'><b>$pais</b></font>\n";
		echo "</td>\n";
	}
	
	echo "</tr>\n";
}
echo "</table>\n";

?>

</body>
</html>
