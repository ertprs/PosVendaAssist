<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';


header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

if($login_fabrica == 1){
	$programa_troca = $_GET['exibe'];
	
	if(preg_match("os_cadastro_troca.php", $programa_troca)){
		$troca_produto = 't';
	}
	if(preg_match("os_revenda_troca.php", $programa_troca)){
		$revenda_troca = 't';
	}
	if(preg_match("os_cadastro.php", $programa_troca)){
		$troca_obrigatoria_consumidor = 't';
	}
	if(preg_match("os_revenda.php", $programa_troca)){
		$troca_obrigatoria_revenda = 't';
	}
}
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
	font: bold 11px "Arial";
	border-collapse: collapse;
	border:1px solid #596d9b;
}


</style>

</head>

<!--
<body onblur="javascript: setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
-->

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<? if ($login_fabrica==1) { ?>
	<script language="JavaScript">
		function alertaTroca(){
			alert('ESTE PRODUTO NÃO É TROCA. SOLICITAR PEÇAS E REALIZAR O REPARO NORMALMENTE. EM CASO DE DÚVIDAS ENTRE EM CONTATO COM O SUPORTE DA SUA REGIÃO.');
		}
		function alertaTrocaSomente(){
			alert('Prezado Posto, este produto é somente para troca. Gentileza cadastrar na o.s de troca específica.');
		}
	</script>
<? } ?>

<br>

<img src="imagens/pesquisa_produtos<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">
<BR>
<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));

	/*if($sistema_lingua == "ES") { 
		echo "<h4>Buscando por <B>referencia del producto</b>:";
	}else{ 
		echo "<h4>Pesquisando por <b>descrição do produto</b>:";
	}
	echo "<i>$descricao</i></h4>";

	echo "<p>";*/
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
			AND      tbl_produto.produto_principal 
			AND      tbl_linha.linha = 494 ";
		/* FIXO PARA LINHA DE GEO METAIS: 494*/


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
	
	/*if($sistema_lingua == "ES") { 
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por <B>referencia del producto</b>:";
	}else{ 
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição do produto</b>:";
	}
	echo "<i>$referencia</i></font>";
echo "<p>";*/

	
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			LEFT JOIN tbl_produto_pais   using(produto)
			WHERE    tbl_produto.referencia_pesquisa LIKE '%$referencia%'
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			AND      tbl_produto.produto_principal 
			AND      tbl_linha.linha = 494 ";
		/* FIXO PARA LINHA DE GEO METAIS: 494*/

#	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS NOT FALSE ";
    if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";
	$sql .= " ORDER BY";
	if ($login_fabrica == 45) $sql .= " tbl_produto.referencia, ";
	$sql .= " tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);
//	if($login_posto == 6359){
//		echo "sql: $sql";
//	}
	if (pg_numrows ($res) == 0) {
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
	
	echo "<table width='100%' border='0' class='tabela' cellspacing='1'>\n";

	if($tipo=="descricao"){
		echo "<tr class='titulo_tabela'><td colspan='5'><font style='font-size:14px;'>";

		if($sistema_lingua == "ES") { 
			echo "Buscando por <B>referencia del producto</b>:";
		}else{ 
			echo "Pesquisando por <b>descrição do produto</b>:";
		}
		echo "$descricao";
		echo "</font></td></tr>";
	}

	if($tipo=="referencia"){
		echo "<tr class='titulo_tabela'><td colspan='100%'><font style='font-size:14px;'>";
		if($sistema_lingua == "ES") { 
			echo "Buscando por <B>referencia del producto</b>:";
		}else{ 
			echo "Pesquisando por <b>descrição do produto</b>:";
		}
		echo "$referencia";

		echo "</font></td></tr>";
	}
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
		$capacidade = trim(pg_result($res,$i,capacidade));

		$valor_troca    = trim(pg_result($res,$i,valor_troca));
		$troca_garantia = trim(pg_result($res,$i,troca_garantia));
		$troca_faturada = trim(pg_result($res,$i,troca_faturada));

		$descricao = str_replace ('"','',$descricao);
		$descricao = str_replace ("'","",$descricao);
		
		//hd 14624
		$troca_obrigatoria= trim(pg_result($res,$i,troca_obrigatoria));

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

		$produto_pode_trocar = 1;
		if ($troca_produto == 't' or $revenda_troca == 't'){

			if ($troca_faturada != 't' AND $troca_garantia != 't'){
				$produto_pode_trocar = 0;
			}
		}
		
		$produto_so_troca=1;
		if($troca_obrigatoria_consumidor == 't' or $troca_obrigatoria_revenda == 't'){
			if($troca_obrigatoria=='t'){
				$produto_so_troca=0;
			}
		}
		
		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";

		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>\n";

		//takashi 06/07/2006 chamado 300 help desk
		# Fabio - 19-12-2007 = Coloquei esta if para nw mostrar link quando o produto nw for de troca / somente na tela de cadastro de OS troca
		//HD 14624 Paulo alterou para verificar se o produto é só de troca
		if($produto_pode_trocar ==0){
				echo "<a href='javascript:alertaTroca()'>$referencia</a>\n";
		}elseif($produto_so_troca ==0){
					echo "<a href='javascript:alertaTrocaSomente()'>$referencia</a>\n";
		}else{
			echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; voltagem.value = '$voltagem'; if (window.capacidade){ capacidade.value = '$capacidade';};";
			
			echo " descricao.focus();this.close() ; \" >";
			echo "$referencia\n";
			echo "</A>";
		}

		echo "</td>\n";

		if($login_fabrica == 20){
			echo "<td>\n";
			if(strlen($referencia_fabrica)>0){
			echo "<font size='1' color='#AAAAAA'>Bare Tool</font><br>";
			}
			echo "$referencia_fabrica\n";
			echo "</td>\n";
		}
		
		echo "<td>\n";

		# Fabio - 19-12-2007 = Coloquei esta if para nw mostrar link quando o produto nw for de troca / somente na tela de cadastro de OS troca
		if($produto_pode_trocar ==0){
				echo "<a href='javascript:alertaTroca()'>$descricao</a>\n";
		}elseif($produto_so_troca ==0){
					echo "<a href='javascript:alertaTrocaSomente()'>$descricao</a>\n";
		}else{
			echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; voltagem.value = '$voltagem'; if (window.capacidade){ capacidade.value = '$capacidade';}; ";
			
			echo " descricao.focus();this.close() ; \" >";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
			echo "</A>";
		}
		echo "</td>\n";

		
		echo "<td>\n";

		echo "$nome_comercial\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "$voltagem\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "$mativo\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
?>

</body>
</html>
