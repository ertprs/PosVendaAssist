<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

#include 'cabecalho_pop_produtos.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$os_troca = $_GET["os_troca"];

if ($login_fabrica == 14) {
	$sql_familia = " JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia ";
}
if ($login_fabrica == 30) {

	$tipo = trim(strtolower($_GET['tipo']));
	
	if($login_fabrica == 30){
		$join_busca_referencia = 'LEFT JOIN tbl_esmaltec_referencia_antiga ON (tbl_produto.referencia = tbl_esmaltec_referencia_antiga.referencia) '; 
	}

	if ($tipo == "referencia") {

		$referencia = trim(strtoupper($_GET["campo"]));
		$referencia = str_replace(".","",$referencia);
		$referencia = str_replace(",","",$referencia);
		$referencia = str_replace("-","",$referencia);
		$referencia = str_replace("/","",$referencia);
		$referencia = str_replace(" ","",$referencia);
		
		if($login_fabrica == 30){
			$or_busca_referencia = " OR tbl_esmaltec_referencia_antiga.referencia_antiga LIKE '%$referencia%' ";
		}
		
		$sql = "SELECT CASE WHEN tbl_produto.marca = 164 then 't' ELSE 'f' END as itatiaia
				FROM  tbl_produto
				JOIN  tbl_linha   on tbl_produto.linha   = tbl_linha.linha
				$join_busca_referencia
				WHERE (tbl_produto.referencia_pesquisa like '%$referencia%' $or_busca_referencia)
				AND   tbl_produto.fabrica_i = $login_fabrica
				AND   tbl_linha.fabrica = $login_fabrica
				AND   tbl_produto.ativo
				AND   tbl_produto.produto_principal;";

		$res = @pg_exec($con,$sql);

	} else {

		$descricao = trim(strtoupper($_GET["campo"]));

		$sql = "SELECT CASE WHEN tbl_produto.marca = 164 then 't' ELSE 'f' END as itatiaia
				FROM  tbl_produto
				JOIN  tbl_linha   on tbl_produto.linha   = tbl_linha.linha
				WHERE (UPPER(tbl_produto.descricao)      LIKE '%$descricao%' 
				    OR UPPER(tbl_produto.nome_comercial) LIKE '%$descricao%' )
				AND   tbl_produto.fabrica_i = $login_fabrica
				AND   tbl_linha.fabrica = $login_fabrica
				AND   tbl_produto.ativo
				AND   tbl_produto.produto_principal;";

		$res = @pg_exec($con,$sql);

	}

	if (@pg_numrows($res) > 0) {

		$itatiaia = pg_result($res,0,'itatiaia');

		if ($itatiaia == 't') {?>
			<script language="JavaScript">
				function alertaItaitaia() {
					alert('Este produto ITATIAIA não pode ser aberto Ordem de Serviço pelo Posto, somente o CALLCENTER poderá abrir. Favor entrar em contato com o CALLCENTER!');
				}
				alertaItaitaia();
			</script><?php
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',500);";
			echo "</script>";
			exit;
		}

	}

}

if ($login_fabrica == 1) {

	$programa_troca = $_GET['exibe'];

	$limpa = $_GET['limpa'];
	
	if (strpos($programa_troca, 'os_cadastro.') !== false) {
		$troca_obrigatoria_consumidor = 't';
	}

	if (strpos($programa_troca, 'os_revenda.') !== false) {
		$troca_obrigatoria_revenda = 't';
	}

	if (strpos($programa_troca, 'os_cadastro_troca') !== false) {
		$troca_produto = 't';
	}

	if (strpos($programa_troca, 'os_revenda_troca') !== false) {
		$revenda_troca = 't';
	}

}

if ($login_fabrica == 104) {

	$programa_troca = $_GET['exibe'];
	
	if (strpos($programa_troca, 'tabela_precos') !== false) {
		$verifica_linha_produto = 't';
	}

}

if ($login_fabrica == 66) {

	$programa_troca = $_GET['exibe'];

	if (strpos($programa_troca, 'pedido_cadastro') !== false) {
		$subproduto_consulta = 't';
	}

}?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title><?=traduz('pesquisa.de.produto', $con)?></title>
	<meta name="Author" content="">
	<meta name="Keywords" content="">
	<meta name="Description" content="">
	<meta http-equiv=pragma content=no-cache>

	<link rel="stylesheet" type="text/css" href="css/posicionamento.css">
	<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
	<script src="js/jquery-1.3.2.js" type="text/javascript"></script>
	<script src="js/thickbox.js"	 type="text/javascript"></script>
</head>
<!--
<body onblur="javascript: setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
-->
<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<? if ($login_fabrica == 1) {?>
	<script language="JavaScript">
		function alertaTroca() {
			alert('ESTE PRODUTO NÃO É TROCA. SOLICITAR PEÇAS E REALIZAR O REPARO NORMALMENTE. EM CASO DE DÚVIDAS ENTRE EM CONTATO COM O SUPORTE DA SUA REGIÃO.');
		}
		function alertaTrocaSomente() {
			alert('Prezado Posto, este produto é somente para troca. Gentileza cadastrar na o.s de troca específica.');
		}
	</script><?php
}?>

<br />

<img src="imagens/pesquisa_produtos<? if($cook_idioma == "es") echo "_es"; ?>.gif">
<br /><?php

$tipo = trim(strtolower($_GET['tipo']));

if ($tipo == "descricao") {

	$descricao = trim(strtoupper($_GET["campo"]));

	fecho('pesquisando.pela.descricao', $con, $cook_idioma, array($descricao));

	$descricao = strtoupper($descricao);

	if ($login_pais <> 'BR') {
		$cond1 = "";
	}

	$cond_ativo = "tbl_produto.ativo";

	if ($login_posto == 7214) {
		$cond_ativo = "tbl_produto.uso_interno_ativo";
	}

	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			$join_busca_referencia
			LEFT JOIN tbl_produto_idioma using(produto)
			LEFT JOIN tbl_produto_pais   using(produto)
			$sql_familia
			WHERE   (
				   UPPER(tbl_produto.descricao)      LIKE '%$descricao%' 
				OR UPPER(tbl_produto.nome_comercial) LIKE '%$descricao%' 
				OR ( 
					UPPER(tbl_produto_idioma.descricao) LIKE '%$descricao%' 
					AND tbl_produto_idioma.idioma = '$sistema_lingua'
				)
				
			)
			AND      tbl_produto.fabrica_i = $login_fabrica
			AND      tbl_linha.fabrica = $login_fabrica
			AND      $cond_ativo
			AND      tbl_produto.produto_principal ";

#	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS NOT FALSE ";
	if ($login_fabrica == 14 or $login_fabrica == 66) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";
	if ($login_fabrica == 30) $sql .= " AND COALESCE(tbl_produto.marca, 0) <> 164 ";
	
// fabrica intelbras postos BR não exibir produtos importados.
	if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";

	# HD 96688 - Tulio desabilitou. Nao era bem isto...
#	if($login_fabrica == 14 AND $login_posto <> 7214) {
#		$sql .= " AND tbl_produto.linha <> 560 ";
#	}

	$sql .= " ORDER BY tbl_produto.descricao;";

	$res = pg_exec($con,$sql);

	echo "<p>";

#echo nl2br($sql);

	if (@pg_num_rows($res) == 0) {
		echo '<h1>';
		fecho(array('produto','%','nao.encontrado'), $con, $cook_idioma, (array)$descricao);
		echo '</h1>
		<script language="javascript">
		setTimeout("window.close()",2500);
		</script>';
		exit;
	}

	if (@pg_num_rows($res) == 1 and $login_fabrica == 24) {
		$produto    = trim(pg_result($res,0,'produto'));
		$descricao  = trim(pg_result($res,0,'descricao'));
		$voltagem   = trim(pg_result($res,0,'voltagem'));
		$referencia = trim(pg_result($res,0,'referencia'));
		$descricao  = str_replace('"','',$descricao);
		$descricao  = str_replace("'","",$descricao);
		echo "<script language='JavaScript'>
			referencia.value = '$referencia' ;
			descricao.value = '$descricao' ;
			voltagem.value = '$voltagem';
			descricao.focus();
			this.close();
		</script>\n";
	}

}

if ($tipo == "referencia") {

	$referencia = trim(strtoupper($_GET["campo"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace(",","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);
	$referencia = str_replace(" ","",$referencia);

	echo "<h1>";
	fecho('pesquisando.pela.referencia', $con, $cook_idioma, array($referencia));
	echo "</h1>";

	if ($login_fabrica == 104 and $verifica_linha_produto == 't') {
		$sql_linha = "SELECT 
						tbl_linha.linha
						FROM tbl_posto_linha 
						JOIN tbl_linha using (linha)
						 WHERE fabrica=$login_fabrica and posto=$login_posto";
		$res_linha = pg_query($con,$sql_linha);
		$linhas_posto = array();
		for ($i=0; $i < pg_num_rows($res_linha); $i++) { 
			$linhas_posto[] = pg_fetch_result($res_linha, $i, 'linha');
		}

		$linhas_posto = implode(',', $linhas_posto);

		$where_vonder = "
			AND tbl_linha.linha in ($linhas_posto)
			";
	}

	$sql = "SELECT   tbl_produto.*
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			$join_busca_referencia
			LEFT JOIN tbl_produto_pais   using(produto)
			$sql_familia
			WHERE    (tbl_produto.referencia_pesquisa LIKE '%$referencia%' $or_busca_referencia)
			AND      tbl_produto.fabrica_i = $login_fabrica
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			$where_vonder
			AND      tbl_produto.produto_principal ";

	if ($login_fabrica == 20) {

		$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			LEFT JOIN tbl_produto_pais   using(produto)
			WHERE    (tbl_produto.referencia_pesquisa LIKE '%$referencia%' OR tbl_produto.referencia_fabrica LIKE '%$referencia%')
			AND      tbl_produto.fabrica_i = $login_fabrica
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			AND      tbl_produto.produto_principal ";

	}

	if ($login_fabrica == 14 or $login_fabrica == 66) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";
	if ($login_fabrica == 30) $sql .= " AND COALESCE(tbl_produto.marca, 0) <> 164 ";

	// fabrica intelbras postos BR não exibir produtos importados.
	if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";
	
	# HD 96688
	if ($login_fabrica == 14 AND $login_posto <> 7214) {
		$sql .= " AND tbl_produto.linha <> 560 ";
	}

	$sql .= " ORDER BY";
	if ($login_fabrica == 45) $sql .= " tbl_produto.referencia, ";
	$sql .= " tbl_produto.descricao;";
//echo nl2br($sql);

	$res = pg_exec($con,$sql);

	echo "<p>";

	if (pg_numrows($res) == 0) {
		echo '<h1>';
		fecho(array('produto','%','nao.encontrado'), $con, $cook_idioma, (array)$referencia);
		echo "</h1>
		<script type='text/javascript'>
		setTimeout('window.close()',2500);
		</script>\n";
		exit;
	}

	if (@pg_numrows($res) == 1 and $login_fabrica == 24) {
		$produto    = trim(pg_result($res,0,'produto'));
		$descricao  = trim(pg_result($res,0,'descricao'));
		$voltagem   = trim(pg_result($res,0,'voltagem'));
		$referencia = trim(pg_result($res,0,'referencia'));
		$descricao  = str_replace('"','',$descricao);
		$descricao  = str_replace("'","",$descricao);
		echo "<script type='text/javascript'>\n";
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
	
	echo "<table width='100%' border='0'>\n";
	
	for ($i = 0; $i < pg_numrows($res); $i++) {

		if($login_fabrica == 30){
			$referencia_antiga  = trim(pg_result($res, $i, 'referencia_antiga'));
		}
		
		$produto            = trim(pg_result($res, $i, 'produto'));
		$linha              = trim(pg_result($res, $i, 'linha'));
		$descricao          = trim(pg_result($res, $i, 'descricao'));
		$nome_comercial     = trim(pg_result($res, $i, 'nome_comercial'));
		$voltagem           = trim(pg_result($res, $i, 'voltagem'));
		$referencia         = trim(pg_result($res, $i, 'referencia'));
		$referencia_fabrica = trim(pg_result($res, $i, 'referencia_fabrica'));
		$garantia           = trim(pg_result($res, $i, 'garantia'));
		$mobra              = str_replace(".",",",trim(pg_result($res, $i, 'mao_de_obra')));
		$ativo              = trim(pg_result($res, $i, 'ativo'));
		$off_line           = trim(pg_result($res, $i, 'off_line'));
		$capacidade         = trim(pg_result($res, $i, 'capacidade'));

		$valor_troca        = trim(pg_result($res, $i, 'valor_troca'));
		$troca_garantia     = trim(pg_result($res, $i, 'troca_garantia'));
		$troca_faturada     = trim(pg_result($res, $i, 'troca_faturada'));

		$descricao = str_replace('"','',$descricao);
		$descricao = str_replace("'","",$descricao);
		//hd 14624
		$troca_obrigatoria= trim(pg_result($res, $i, 'troca_obrigatoria'));

		if (strlen($produto) > 0) {
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";
	
			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma, 0, 'descricao'));
			}
		}

		$mativo = ($ativo == 't') ?  "ATIVO" : "INATIVO";

		$produto_pode_trocar = 1;

		if ($troca_produto == 't' or $revenda_troca == 't') {

			if ($troca_faturada != 't' AND $troca_garantia != 't') {
				$produto_pode_trocar = 0;
			}

		}

		$produto_so_troca = 1;

		if ($troca_obrigatoria_consumidor == 't' or $troca_obrigatoria_revenda == 't') {

			if ($troca_obrigatoria == 't') {
				$produto_so_troca = 0;
			}

		}

		$cor = ($i % 2 <> 0) ? '#EEEEEE' : '#ffffff';

		echo "<tr bgcolor='$cor'>\n";
		
		if($login_fabrica == 30){
			echo "<td><font size='1'>Ref. Ant.: $referencia_antiga</font> </td>\n";
		}

		echo "<td>\n";

		//HD 14624 Paulo alterou para verificar se o produto é só de troca
		if ($produto_pode_trocar == 0) {
			echo "<a href='javascript:alertaTroca()'><font face='Arial, Verdana, Times, Sans' size='-1' color='#6A6A6A'>$referencia</font></a>\n";
		} else if($produto_so_troca == 0) {
			echo "<a href='javascript:alertaTrocaSomente()'><font face='Arial, Verdana, Times, Sans' size='-1' color='#6A6A6A'>$referencia</font></a>\n";
		} else {
			// hd 115479
			if ($login_fabrica == 11) {
				$num = pg_numrows($res);
				if ($num>1) {
					$msg_confirma = "if (confirm('Atenção \\nEste modelo possui mais de uma versão!\\nVerifique na etiqueta do produto o modelo e\\nCertifique-se que está sendo imputada a versão correta na OS.')==true){";
					$chave = "}";
				}
			}
			if ($login_fabrica == 1 AND $limpa == TRUE) {
				$linha_r = $_GET['linha'];
				echo "<a href=\"javascript: $msg_confirma descricao.value = '$descricao' ; referencia.value = '$referencia' ; voltagem.value = '$voltagem'; if (window.capacidade){ capacidade.value = '$capacidade';} ;window.opener.limpa_troca($linha_r);descricao.focus();this.close(4) ;window.opener.verifica_produtos_troca('$referencia'); $chave \" >";
			} else {
				echo "<a href=\"javascript: $msg_confirma descricao.value = '$descricao' ; referencia.value = '$referencia' ; voltagem.value = '$voltagem'; if (window.capacidade){ capacidade.value = '$capacidade';} descricao.focus();this.close(5) ; $chave \" >";	
			}
			
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$referencia</font>\n";
			echo "</A>";
		}

		echo "</td>\n";

		if ($login_fabrica == 20) {
			echo "<td>\n";
			if (strlen($referencia_fabrica) > 0) {
				echo "<font size='1' color='#AAAAAA'>Bare Tool</font><br />";
			}
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'> $referencia_fabrica </font>\n";
			echo "</td>\n";
		}
		
		if ($login_fabrica == 14 or ($login_fabrica == 66 and $subproduto_consulta)) {
		
				#------------ Pesquisa de Produto Pai para INTELBRÁS -----------
				if ($login_fabrica == 66) {

					$sql = "SELECT DISTINCT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
							   FROM     tbl_produto
							   JOIN     tbl_subproduto ON tbl_subproduto.produto_pai = tbl_produto.produto
							   WHERE    tbl_subproduto.produto_pai = $produto
							   AND      tbl_produto.ativo
							   ORDER BY tbl_produto.descricao";

				} else {

					$sql = "SELECT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
							   FROM     tbl_produto
							   JOIN     tbl_subproduto ON tbl_subproduto.produto_pai = tbl_produto.produto
							   WHERE    tbl_subproduto.produto_filho = $produto
							   AND      tbl_produto.ativo ";
					
					// fabrica intelbras postos BR não exibir produtos importados.
					if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";

					$sql .= "ORDER BY tbl_produto.descricao";

				}

				$resX = pg_exec($con,$sql);

				if (pg_numrows($resX) == 0) {

					echo "<td>\n";
					echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; if (window.voltagem) { voltagem.value = '$voltagem' ; } ;descricao.focus(); this.close() ; \" >";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
					echo "</a>\n";

				} else {

					echo "<td>\n";

				}

				for ($x = 0; $x < pg_numrows($resX); $x++) {

					$produto_pai    = trim(pg_result($resX, $x, 'produto'));
					$descricao_pai  = trim(pg_result($resX, $x, 'descricao'));
					$referencia_pai = trim(pg_result($resX, $x, 'referencia'));

					$descricao_pai = str_replace('"','',$descricao_pai);
					$sql = "SELECT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
								   FROM     tbl_produto
								   JOIN     tbl_subproduto ON tbl_subproduto.produto_filho = tbl_produto.produto
								   WHERE    tbl_subproduto.produto_pai = $produto_pai
								   AND      tbl_produto.ativo
								   ORDER BY tbl_produto.descricao";
					$resZ = pg_exec($con,$sql);
					if (pg_numrows($resZ) == 0) {
						echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'></font>\n";
						echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; if (window.voltagem) { voltagem.value = '$voltagem'} ; if (window.referencia_pai) { referencia_pai.value = '$referencia_pai' } ; if (window.descricao_pai) { descricao_pai.value = '$descricao_pai' } ; descricao.focus();this.close(6) ; \" >";
						echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao </font>\n";
						echo "</a>\n";
					}else{
						echo "<a href=\"javascript: $msg_confirma descricao.value = '$descricao' ; referencia.value = '$referencia' ; voltagem.value = '$voltagem'; if (window.capacidade){ capacidade.value = '$capacidade';} descricao.focus();this.close(7) ; $chave \" >";
						echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
						break;
					}
					//179336 retirado por reclamacao da rammonna
				}
				echo "</td>\n";
		}else{
			echo "<td>\n";

			# Fabio - 19-12-2007 = Coloquei esta if para nw mostrar link quando o produto nw for de troca / somente na tela de cadastro de OS troca
			if($produto_pode_trocar ==0){
				echo "<a href='javascript:alertaTroca()'><font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font></a>\n";
			}elseif($produto_so_troca ==0){
				echo "<a href='javascript:alertaTrocaSomente()'><font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font></a>\n";
			}else{
				// hd 115479
				if ($login_fabrica == 11) {
					$num = pg_numrows($res);
					if ($num>1) {
						$msg_confirma = "if (confirm('Atenção \\nEste modelo possui mais de uma versão!\\nVerifique na etiqueta do produto o modelo e\\nCertifique-se que está sendo imputada a versão correta na OS.')==true){";
						$chave = "}";
					}
				}
				if ($login_fabrica == 1 AND $limpa == TRUE) {
					$linha_r = $_GET['linha'];
					echo "<a href=\"javascript: $msg_confirma descricao.value = '$descricao' ; referencia.value = '$referencia' ; voltagem.value = '$voltagem'; if (window.capacidade){ capacidade.value = '$capacidade';}; window.opener.limpa_troca($linha_r);descricao.focus();this.close(2) ;window.opener.verifica_produtos_troca('$referencia'); $chave \" >";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
					echo "</A>";
				} else {
					echo "<a href=\"javascript: $msg_confirma descricao.value = '$descricao' ; referencia.value = '$referencia' ; voltagem.value = '$voltagem'; if (window.capacidade){ capacidade.value = '$capacidade';}; descricao.focus();this.close(3) ; $chave \" >";
					echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
					echo "</A>";
				}
			}
				echo "</td>\n";
		}
		
		echo "<td>\n";

		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome_comercial</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$voltagem</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$mativo</font>\n";
		echo "</td>\n";
		$imagem = "imagens_produtos/$login_fabrica/pequena/$produto.jpg";
		if ($login_fabrica==3) {
				echo "<td title='$imagem' bgcolor='#FFFFFF' align='center'>\n";
		    if (file_exists("/var/www/assist/www/$imagem")) {
		        $tag_imagem = "<A href='".str_replace("pequena", "media", $imagem)."' class='thickbox'>\n";
				$tag_imagem.= "<IMG src='$imagem' valign='middle' style='border: 2px solid #FFCC00' class='thickbox' height='40'></A>\n";
				echo $tag_imagem;
			}
				echo "</td>\n";
		}
		echo "</tr>\n";

/*
		$produto_pai = $produto ;
		while (strlen ($produto_pai) > 0) {
			$sql = "SELECT tbl_produto.referencia, tbl_produto.descricao, tbl_produto.produto, tbl_subproduto.produto_filho
						FROM	tbl_produto 
						JOIN	tbl_subproduto ON tbl_produto.produto = tbl_subproduto.produto_filho 
						AND		tbl_subproduto.produto_pai = $produto_pai ";
			$resX = pg_exec($con,$sql);
			if (pg_numrows($resX) > 0) {
				for ( $x = 0 ; $x < pg_numrows($resX) ; $x++ ) {
					echo "<tr><td colspan='4'><table width='100%' border='0'>";
					echo "<td><img src='imagens/setinha.gif'></td>";
					echo "<td>" . pg_result ($resX,$x,referencia) . "</td>";
					echo "<td>" . pg_result ($resX,$x,descricao) . "</td>";
					echo "</tr></table></tr>";
					$produto_pai = pg_result ($resX,$x,produto_filho);
				}
			}else{
				$produto_pai = "";
			}
		}
*/
	}
	echo "</table>\n";
?>

</body>
</html>

<?php
// Fechando explicitamente a conexão com o BD
if (is_resource($con)) {
    pg_close($con);
}
?>
