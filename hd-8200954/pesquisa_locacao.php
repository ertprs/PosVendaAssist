<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

#include 'cabecalho_pop_produtos.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto Locação... </title>
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

<img src="imagens/pesquisa_produtos.gif">

<?php

$serie 				= trim (strtolower ($_GET['serie']));
$faturado 			= trim (strtolower ($_GET['faturado']));
$produto_referencia = trim (strtolower ($_GET['referencia']));


if(strlen($serie) > 0){
	// Dados informando o Número de Série do Produto
	$titulo 		= "<h4>Pesquisando pela <b>série do produto</b>: <i>{$serie}</i></h4>";
	$cond_sql_linha = "tbl_locacao.serie = '{$serie}'";
	$cond_sql_1 	= "tbl_locacao.serie = '$serie'";
	$msg_erro_tela 	= "<h1>Produto com a série '$serie' não encontrado</h1>";
}

if(strlen($produto_referencia) > 0){
	// Dados informando a Referência do Produto
	$titulo 		= "<h4>Pesquisando pela <b>série do produto</b>: <i>{$produto_referencia}</i></h4>";
	$cond_sql_linha = "tbl_produto.referencia ILIKE '%{$produto_referencia}%'";
	$cond_sql_1 	= "tbl_produto.referencia ILIKE '%{$produto_referencia}%'";
	$msg_erro_tela 	= "<h1>Produto com a referÊncia '$produto_referencia' não encontrado</h1>";
}

if (strlen($serie)>0 OR strlen($produto_referencia)>0) {
	echo $titulo;
	echo "<p>";
	$linha = 0;
	$sql_linha = "SELECT tbl_produto.linha FROM tbl_produto
					JOIN tbl_linha USING (linha)
					JOIN tbl_locacao ON tbl_locacao.produto = tbl_produto.produto
					JOIN tbl_posto ON tbl_locacao.posto = tbl_posto.posto
					WHERE {$cond_sql_linha}
					AND tbl_posto.posto = $login_posto
					AND tbl_linha.fabrica = $login_fabrica";
	
	//die(nl2br($sql_linha));
	$query_linha = pg_query($con, $sql_linha);

	if (pg_num_rows($query_linha) > 0) {
		$linha = pg_fetch_result($query_linha, 0, 'linha');
	}

	if ($linha == 687) {
		$prazo_garantia = '1 year';
	} else {
		$prazo_garantia = '6 months';
	}

	// HD 59576
	$sql = "SELECT  tbl_produto.produto,
					tbl_locacao.serie,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_produto.voltagem,
					tbl_locacao.nota_fiscal,
					tbl_locacao.type,
					to_char(tbl_locacao.data_emissao,'DD/MM/YYYY')    as data_emissao   ,
					to_char(tbl_locacao.data_vencimento,'DD/MM/YYYY') as data_vencimento,
					case when tbl_locacao.data_emissao+interval'$prazo_garantia' >= current_date then 'nao' else 'sim' end as fora_garantia,
					locador
			FROM tbl_locacao
			JOIN tbl_produto    ON tbl_locacao.produto = tbl_produto.produto
			JOIN tbl_posto      ON tbl_locacao.posto   = tbl_posto.posto
			WHERE tbl_posto.posto             = $login_posto
			AND   {$cond_sql_1}
			ORDER by tbl_produto.descricao;";

	//die(nl2br($sql));
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo $msg_erro_tela;
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}else{
		$fora_garantia   = pg_result($res,0,fora_garantia);
		$referencia      = pg_result($res,0,referencia);
		$nota_fiscal     = pg_result($res,0,nota_fiscal);
		$data_emissao    = pg_result($res,0,data_emissao);
		$data_vencimento = pg_result($res,0,data_vencimento);
		$locador         = pg_result($res,0,locador);

		if($locador =='f') {
			echo "<h1>O produto $referencia número de série $serie não está incluso no programa de garantia DEWALT Rental</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',4500);";
			echo "</script>";
			exit;
		}
		if($fora_garantia =='sim' and empty($faturado)) {
			echo "<h1>A garantia do produto $referencia número de série $serie N.F. $nota_fiscal de $data_emissao está vencida desde $data_vencimento</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',4500);";
			echo "</script>";
			exit;
		}
	}
}

echo "<script language='JavaScript'>\n";
echo "<!--\n";
echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";

?>
	<style type="text/css">
		.cabecalho { text-align: center; font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 12px; font-weight: bold; border: 0px solid; color:#ffffff; background-color: #596D9B; }
		.corpo_tabela { font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 11px; font-weight: normal; border: 0px solid; }
	</style>
<?

echo "<table width='100%' border='0 cellpadding='2' cellspacing='2'>\n";


?>
	<!-- Cabecalho da tabela -->

	<thead>
		<tr class='cabecalho'>
			<td>Número Série</td>
			<td>Referência</td>			
			<td>Descrição</td>
			<td>Voltagem</td>
			<td>Tipo</td>
			<td>Nota Fiscal</td>
			<td>Data Emissão</td>
		</tr>
	</thead>
	<tbody>

	<!-- FIM -->

<?
	$contador_res = pg_numrows($res);

	for ($i = 0; $i < $contador_res; $i++) {
		$produto      		= trim(pg_result($res,$i,produto));
		$num_serie   		= trim(pg_result($res,$i,serie));
		$referencia   		= trim(pg_result($res,$i,referencia));
		$descricao    		= trim(pg_result($res,$i,descricao));
		$voltagem     		= trim(pg_result($res,$i,voltagem));
		$tipo         		= trim(pg_result($res,$i,type));
		$nota_fiscal  		= trim(pg_result($res,$i,nota_fiscal));
		$data_emissao 		= trim(pg_result($res,$i,data_emissao));

		$produto_excel      = trim(pg_result($res,$i,'produto'));
		$referencia_excel   = trim(pg_result($res,$i,referencia));
		$descricao_excel    = trim(pg_result($res,$i,descricao));
		$voltagem_excel     = trim(pg_result($res,$i,voltagem));
		$tipo_excel         = trim(pg_result($res,$i,type));
		$nota_fiscal_excel  = trim(pg_result($res,$i,nota_fiscal));
		$data_emissao_excel = trim(pg_result($res,$i,data_emissao));


		$descricao = str_replace("'","",$descricao);

		if($i%2 == 0){
			$bgcolor = '#FFFFFF';
		} else {
			$bgcolor = '#F1F4FA';
		}
		echo "<tr class='corpo_tabela'>\n";
		echo "<td>\n";

		echo "</tr>\n";

		echo "<tr class='corpo_tabela' bgcolor='". $bgcolor ."'>\n";
			echo "<td>$num_serie\n";
			echo "<td>$referencia</td>\n";			

			echo "<td>\n";
			echo "<a href=\"javascript: num_serie.value = '$num_serie'; produto.value = $produto; descricao.value = '$descricao' ; referencia.value = '$referencia' ; voltagem.value = '$voltagem' ; tipo.value = '$tipo' ; nota_fiscal.value = '$nota_fiscal' ; data_emissao.value = '$data_emissao';";
			echo !empty($faturado) ? "window.opener.upload_excel.produto_excel.value = $produto_excel; window.opener.upload_excel.referencia_excel.value = '$referencia_excel';window.opener.upload_excel.descricao_excel.value = '$descricao_excel';window.opener.upload_excel.voltagem_excel.value = '$voltagem_excel';window.opener.upload_excel.tipo_excel.value = '$tipo_excel';window.opener.upload_excel.nota_fiscal_excel.value = '$nota_fiscal_excel';window.opener.upload_excel.data_emissao_excel.value = '$data_emissao_excel';window.opener.upload_excel.serie_excel.value = '$serie';" : ""; 
			echo " this.close() ; \" >";
			echo "<font color='#0000FF'>$descricao</font>\n";
			echo "</a>\n";
			echo "</td>\n";

			echo "<td nowrap align='center'>$voltagem\n";
			echo "<td nowrap align='center'>$tipo\n";
			echo "<td nowrap align='center'>$nota_fiscal\n";
			echo "<td nowrap align='center'>$data_emissao\n";
		echo "</tr>\n";
	}
echo "</tbody></table>\n";
?>

</body>
</html>
