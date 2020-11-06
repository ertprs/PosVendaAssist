<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

##### GERAR RELATÓRIO - INÍCIO #####
$relatorio = $_GET["relatorio"];

if (strtoupper($relatorio) == "GERAR") {
	$linha = $_GET["linha"];
	$linha = str_replace("-", ",", $linha);
	
	##### MÃO DE OBRA PARA LINHA DEWALT #####
	if ($linha == "198" and 1 ==2) {

		$sql =	"SELECT tbl_produto.produto                               ,
						tbl_produto.referencia                     AS produto_referencia,
						tbl_produto.descricao                      AS produto_descricao,
						tbl_produto.voltagem                       AS produto_voltagem,
						tbl_produto.mao_de_obra                    AS produto_mao_de_obra            ,
						tbl_defeito_constatado.descricao           AS defeito_constatado                                  ,
						tbl_produto_defeito_constatado.mao_de_obra AS defeito_constatado_mao_de_obra
				FROM      tbl_produto
				JOIN      tbl_linha                      ON tbl_linha.linha = tbl_produto.linha
				LEFT JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.produto            = tbl_produto.produto
				LEFT JOIN tbl_defeito_constatado         ON tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado
				WHERE tbl_linha.fabrica = $login_fabrica
				AND   tbl_linha.linha   IN ($linha)
				AND   tbl_produto.ativo IS TRUE
				AND   tbl_produto_defeito_constatado.mao_de_obra IS NOT NULL
				AND   tbl_defeito_constatado.descricao IS NOT NULL
				ORDER BY tbl_produto.produto    ,
						 tbl_produto.referencia ,
						 tbl_produto.descricao";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			flush();

			$data         = date("d/m/Y H:i:s");
			$data_arquivo = date("Y_m_d-H_i_s");

			$tmp_xls	  = "/tmp/assist/tabela-produto-maodeobra-$login_fabrica-$data_arquivo.html";

			$arq = fopen($tmp_xls,"w");

			fputs($arq,"<html>");
			fputs($arq,"<head>");
			fputs($arq,"<title>TABELA DE MÃO DE OBRA DOS PRODUTOS - $data</title>");
			fputs($arq,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs($arq,"</head>");
			fputs($arq,"<body>");

			fputs($arq,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");

			for ($j = 0 ; $j < pg_numrows($res) ; $j++) {
				$produto                        = trim(pg_result($res,$j,produto));
				$produto_referencia             = trim(pg_result($res,$j,produto_referencia));
				$produto_descricao              = trim(pg_result($res,$j,produto_descricao));
				$produto_voltagem               = trim(pg_result($res,$j,produto_voltagem));
				$produto_mao_de_obra            = number_format(trim(pg_result($res,$j,produto_mao_de_obra)),2,",",".");
				$defeito_constatado             = trim(pg_result($res,$j,defeito_constatado));
				$defeito_constatado_mao_de_obra = number_format(trim(pg_result($res,$j,defeito_constatado_mao_de_obra)),2,",",".");
				
				if ($produto_anterior != $produto) {
					fputs($arq,"<tr>");
					fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>REFERÊNCIA</b></td>");
					fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>DESCRIÇÃO</b></td>");
					fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>VOLTAGEM</b></td>");
					fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>MÃO DE OBRA</b></td>");
					fputs($arq,"</tr>");
					fputs($arq,"<tr>");
					fputs($arq,"<td nowrap align='center'> &nbsp; " . $produto_referencia . " &nbsp; </td>");
					fputs($arq,"<td nowrap align='left'> &nbsp; " . $produto_descricao . " &nbsp; </td>");
					fputs($arq,"<td nowrap align='center'> &nbsp; " . $produto_voltagem . " &nbsp; </td>");
					fputs($arq,"<td nowrap align='center'> &nbsp; R$ " . $produto_mao_de_obra . " &nbsp; </td>");
					fputs($arq,"</tr>");
				}
				
				fputs($arq,"<tr>");
				fputs($arq,"<td nowrap align='left' colspan='3'> &nbsp; <b>DEFEITO:</b> " . $defeito_constatado . " &nbsp; </td>");
				fputs($arq,"<td nowrap align='center'> &nbsp; R$ " . $defeito_constatado_mao_de_obra . " &nbsp; </td>");
				fputs($arq,"</tr>");
				
				$produto_anterior = $produto;
			}
			fputs($arq,"</table>");
			fputs($arq,"</body>");
			fputs($arq,"</html>");
			fclose($arq);

			flush();

			rename($tmp_xls, "/www/assist/www/xls/tabela-produto-maodeobra-$login_fabrica-$data_arquivo.xls");

			echo "<br>";
			echo "<p align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b><a href='xls/tabela-produto-maodeobra-$login_fabrica-$data_arquivo.xls' target='_blank'>Clique aqui</a> para fazer o download do arquivo em EXCEL.<br>Você poderá visualizar, imprimir e salvar<br>a tabela para consultas off-line.</b></font></p>";
		}
	}else{
	##### MÃO DE OBRA PARA OUTRAS LINHAS #####
		$sql =	"SELECT tbl_produto.produto     ,
						tbl_produto.referencia  ,
						tbl_produto.descricao   ,
						tbl_produto.voltagem   ,
						to_date(tbl_produto.parametros_adicionais::JSON->>'data_descontinuado', 'DD/MM/YYYY') as data_descontinuado,
						tbl_produto.mao_de_obra
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tbl_linha.fabrica = $login_fabrica
				AND   tbl_linha.linha IN($linha)
				AND   tbl_produto.ativo IS TRUE
				AND (to_date(tbl_produto.parametros_adicionais::JSON->>'data_descontinuado', 'DD/MM/YYYY') > (current_date - interval '5 year') OR  tbl_produto.parametros_adicionais::JSON->>'data_descontinuado' is null )
				ORDER BY tbl_produto.referencia, tbl_produto.descricao";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			flush();

			$data         = date("d/m/Y H:i:s");
			$data_arquivo = date("Y_m_d-H_i_s");
			$tmp_xls	  = "/tmp/assist/tabela-produto-maodeobra-$login_fabrica-$data_arquivo.html";

			$arq = fopen($tmp_xls,"w");

			fputs($arq,"<html>");
			fputs($arq,"<head>");
			fputs($arq,"<title>TABELA DE MÃO DE OBRA DOS PRODUTOS - $data</title>");
			fputs($arq,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs($arq,"</head>");
			fputs($arq,"<body>");

			fputs($arq,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
			fputs($arq,"<tr>");
			fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>REFERÊNCIA</b></td>");
			fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>DESCRIÇÃO</b></td>");
			fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>VOLTAGEM</b></td>");
			fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>MÃO DE OBRA</b></td>");
			fputs($arq,"</tr>");

			for ($j = 0 ; $j < pg_numrows($res) ; $j++) {
				$referencia  = trim(pg_result($res,$j,referencia));
				$descricao   = trim(pg_result($res,$j,descricao));
				$voltagem    = trim(pg_result($res,$j,voltagem));
				$mao_de_obra = number_format(trim(pg_result($res,$j,mao_de_obra)),2,",",".");

				fputs($arq,"<tr>");
				fputs($arq,"<td nowrap align='center'> &nbsp; " . $referencia . " &nbsp; </td>");
				fputs($arq,"<td nowrap align='left'> &nbsp; " . $descricao . " &nbsp; </td>");
				fputs($arq,"<td nowrap align='center'> &nbsp; " . $voltagem . " &nbsp; </td>");
				fputs($arq,"<td nowrap align='center'> &nbsp; R$ " . $mao_de_obra . " &nbsp; </td>");
				fputs($arq,"</tr>");
			}
			fputs($arq,"</table>");
			fputs($arq,"</body>");
			fputs($arq,"</html>");
			fclose($arq);

			flush();

			rename($tmp_xls, "./xls/tabela-produto-maodeobra-$login_fabrica-$data_arquivo.xls");

			echo "<br>";
			echo "<p align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b><a href='xls/tabela-produto-maodeobra-$login_fabrica-$data_arquivo.xls' target='_blank'>Clique aqui</a> para fazer o download do arquivo em EXCEL.<br>Você poderá visualizar, imprimir e salvar<br>a tabela para consultas off-line.</b></font></p>";
		}
	}
	exit;
}
##### GERAR RELATÓRIO - FIM #####

$erro = "";

if (strlen($_POST["botao"]) > 0) $botao = strtoupper($_POST["botao"]);

if ($botao == "PESQUISAR") {

	if (count($_POST["linha"]) > 0){
		$linha = $_POST["linha"];
		$linha = implode(",", $linha);
	}
	
	if (strlen($_GET["linha"]) > 0) {
		 $linha = $_GET["linha"];
		 $linha = str_replace("-", ",", $linha);
	}
	
	if (count($linha) == 0) $erro = " Selecione a linha para realizar a pesquisa. ";

}

$layout_menu = "os";
$title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#ffffff;
	background-color: #596D9B
}
.Conteudo {
	text-align: left;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

li{
	text-align: left !important;
}
</style>

<?php
    //include_once 'js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<script type="text/javascript" src="./js/jquery-1.7.2.js"></script>

<script src="admin/js/jquery.multiple.select.js"></script>
<link rel="stylesheet" href="admin/css/multiple-select.css" />

<script language="JavaScript">
function FuncGerarExcel (linha) {
	var largura = window.screen.width;
	var tamanho = window.screen.height;
	var x = (largura / 2) - 250;
	var y = (tamanho / 2) - 125;
	var link = "<?echo $PHP_SELF?>?relatorio=gerar&linha=" +linha;
	window.open(link, "JANELA", "toolbar=no, location=no, status=yes, scrollbars=yes, menubar=no, directories=no, width=500, height=150, top=" + y + ", left=" + x);
}

$(function(){
    $('#linha').multipleSelect();
});

</script>
<br>

<? if (strlen($erro) > 0) { ?>
<table width="450" border="0" cellspacing="0" cellpadding="2" align="center" class="error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="botao">
<table width="450" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr class="Titulo">
		<td colspan="3">SELECIONE A LINHA PARA REALIZAR A PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="3">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align="center" >Linha
			<?php $sql = "SELECT tbl_posto_linha.linha, tbl_linha.nome FROM tbl_posto_linha
						INNER JOIN tbl_linha USING(linha)
						WHERE tbl_linha.fabrica = $login_fabrica and tbl_posto_linha.posto = $login_posto";
				 $res = pg_query($con, $sql);
			  ?>
			<select name="linha[]" id="linha" multiple='multiple' style='width:180px; text-align: left'>
				<!--<option value="198" <? if ($linha == "198") echo "selected"; ?>>DeWalt</option>
				<option value="199" <? if ($linha == "199") echo "selected"; ?>>Eletro</option>
				<option value="200" <? if ($linha == "200") echo "selected"; ?>>Ferramentas Black & Decker</option>
				<option value="467" <? if ($linha == "467") echo "selected"; ?>>Porter Cable</option>-->
				<?php 
					for($i=0; $i<pg_num_rows($res); $i++){
					 	$linha_valor = pg_fetch_result($res, $i, 'linha');
					 	$nome = pg_fetch_result($res, $i, 'nome');

					 	echo "<option value='$linha_valor'>$nome</option>";
					 }
				?>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="3">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="3" align="center"><img src="imagens/btn_pesquisar_400.gif" onclick="javascript: if (document.frm_pesquisa.botao.value == '' ) { document.frm_pesquisa.botao.value='PESQUISAR'; document.frm_pesquisa.submit(); }else{ alert ('Aguarde submissão'); }" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>
</form>

<?

if (strlen($botao) > 0 && strlen($erro) == 0) {
	##### MÃO DE OBRA PARA LINHA DEWALT #####
	if ($linha == "198" and 1 ==2) {
		$sql =	"SELECT tbl_produto.produto                               ,
						tbl_produto.referencia                     AS produto_referencia,
						tbl_produto.descricao                      AS produto_descricao,
						tbl_produto.voltagem                       AS produto_voltagem,
						tbl_produto.mao_de_obra                    AS produto_mao_de_obra            ,
						tbl_defeito_constatado.descricao           AS defeito_constatado                                  ,
						tbl_produto.mao_de_obra AS defeito_constatado_mao_de_obra
				FROM      tbl_produto
				JOIN      tbl_linha                      ON tbl_linha.linha = tbl_produto.linha
				LEFT JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.produto            = tbl_produto.produto
				LEFT JOIN tbl_defeito_constatado         ON tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado
				WHERE tbl_linha.fabrica = $login_fabrica
				AND   tbl_linha.linha IN($linha)
				AND   tbl_produto.ativo IS TRUE
				AND   tbl_produto_defeito_constatado.mao_de_obra IS NOT NULL
				AND   tbl_defeito_constatado.descricao IS NOT NULL
				ORDER BY tbl_produto.produto    ,
						 tbl_produto.referencia ,
						 tbl_produto.descricao";

		##### PAGINAÇÃO - INÍCIO #####
		$sqlCount = "SELECT count(*) FROM (" . $sql . ") AS count";

		require "_class_paginacao.php";

		# DEFINIÇÕES DAS VARIÁVEIS
		$max_links = 11;					# Máximo de links à serem exibidos
		$max_res   = 50;					# Máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();		# Cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res;	# Define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
		##### PAGINAÇÃO - FIM #####
		
		if (pg_numrows($res) > 0) {
			echo "<table width='500' border='0' cellspacing='1' cellpadding='2' align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td nowrap>REFERÊNCIA</td>";
			echo "<td nowrap>DESCRIÇÃO</td>";
			echo "<td nowrap>VOLTAGEM</td>";
			echo "<td nowrap>MÃO DE OBRA</td>";
			echo "</tr>";
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$produto                        = trim(pg_result($res,$i,produto));
				$produto_referencia             = trim(pg_result($res,$i,produto_referencia));
				$produto_descricao              = trim(pg_result($res,$i,produto_descricao));
				$produto_voltagem               = trim(pg_result($res,$i,produto_voltagem));
				$produto_mao_de_obra            = number_format(trim(pg_result($res,$i,produto_mao_de_obra)),2,",",".");
				$defeito_constatado             = trim(pg_result($res,$i,defeito_constatado));
				$defeito_constatado_mao_de_obra = number_format(trim(pg_result($res,$i,defeito_constatado_mao_de_obra)),2,",",".");

				if ($produto_anterior != $produto) {
					echo "<tr class='Titulo'>";
					echo "<td nowrap align='center'>" . $produto_referencia . "</td>";
					echo "<td nowrap>" . $produto_descricao . "</td>";
					echo "<td nowrap align='center'>" . $produto_voltagem . "</td>";
					echo "<td nowrap align='center'>R$ " . $produto_mao_de_obra . "</td>";
					echo "</tr>";
				}
				
				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
				
				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td nowrap colspan='3'>" . $defeito_constatado . "</td>";
				echo "<td nowrap align='center'>R$ " . $defeito_constatado_mao_de_obra . "</td>";
				echo "</tr>";
				
				$produto_anterior = $produto;
			}
			echo "</table>";
			echo "<br>"; 
			$linha_excel = str_replace(",", ".", $linha);
			echo "<a href=\"javascript: FuncGerarExcel('$linha_excel');\"><font size='2'>Clique aqui para gerar arquivo em EXCEL</font></a>";
		}
		
		##### PAGINAÇÃO - INÍCIO #####
		# Links da paginação
		echo "<br><br>";
		echo "<div>";
		if($pagina < $max_links) $paginacao = pagina + 1;
		else                     $paginacao = pagina;

		# Paginação com restrição de links da paginação
		# Pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links = $mult_pag->Construir_Links("strings", "sim");

		# Função que limita a quantidade de links no rodapé
		$links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0 ; $n < count($links_limitados) ; $n++) {
			echo "<font color='#DDDDDD'>" . $links_limitados[$n] . "</font>&nbsp;&nbsp;";
		}

		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ($pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0) {
			echo "<br>";
			echo "<div>";
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}
		##### PAGINAÇÃO - FIM #####
		
	}else{
	##### MÃO DE OBRA PARA OUTRAS LINHAS #####
		$sql =	"SELECT tbl_produto.produto     ,
						tbl_produto.referencia  ,
						tbl_produto.descricao   ,
						tbl_produto.voltagem    ,
						to_date(tbl_produto.parametros_adicionais::JSON->>'data_descontinuado', 'DD/MM/YYYY') as data_descontinuado,
						tbl_produto.mao_de_obra
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tbl_linha.fabrica = $login_fabrica
				AND   tbl_linha.linha  IN($linha)
				AND   tbl_produto.ativo IS TRUE
				AND (to_date(tbl_produto.parametros_adicionais::JSON->>'data_descontinuado', 'DD/MM/YYYY') > (current_date - interval '5 year') OR  tbl_produto.parametros_adicionais::JSON->>'data_descontinuado' is null )
				ORDER BY tbl_produto.referencia,
						 tbl_produto.descricao ";

		##### PAGINAÇÃO - INÍCIO #####
		$sqlCount = "SELECT count(*) FROM (" . $sql . ") AS count";

		require "_class_paginacao.php";

		# DEFINIÇÕES DAS VARIÁVEIS
		$max_links = 11;					# Máximo de links à serem exibidos
		$max_res   = 30;					# Máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();		# Cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res;	# Define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
		##### PAGINAÇÃO - FIM #####
		
		if (pg_numrows($res) > 0) {
			echo "<table width='500' border='0' cellspacing='1' cellpadding='2' align='center'>";
			if (strlen($linha) > 0) {
				$sql_linha = "SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica AND linha = $linha;";
				$res_linha = pg_exec($con, $sql_linha);
				if (pg_numrows($res_linha) == 1) {
					$linha_nome = trim(pg_result($res_linha,0,0));
					echo "<tr class='Titulo'>";
					echo "<td colspan='4'>PRODUTOS DA LINHA " . $linha_nome . "</td>";
					echo "</tr>";
				}
			}
			echo "<tr class='Titulo'>";
			echo "<td nowrap>REFERÊNCIA</td>";
			echo "<td nowrap>DESCRIÇÃO</td>";
			echo "<td nowrap>VOLTAGEM</td>";
			echo "<td nowrap>MÃO DE OBRA</td>";
			echo "</tr>";
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$referencia  = trim(pg_result($res,$i,referencia));
				$descricao   = trim(pg_result($res,$i,descricao));
				$voltagem    = trim(pg_result($res,$i,voltagem));
				$mao_de_obra = number_format(trim(pg_result($res,$i,mao_de_obra)),2,",",".");

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td nowrap align='center'>" . $referencia . "</td>";
				echo "<td nowrap>" . $descricao . "</td>";
				echo "<td nowrap align='center'>" . $voltagem . "</td>";
				echo "<td nowrap align='center'>R$ " . $mao_de_obra . "</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";
			$linha_excel = str_replace(",", "-", $linha);
			echo "<a href=\"javascript: FuncGerarExcel('$linha_excel');\"><font size='2'>Clique aqui para gerar arquivo em EXCEL</font></a>";
		}

		##### PAGINAÇÃO - INÍCIO #####
		# Links da paginação
		echo "<br><br>";
		echo "<div>";
		if($pagina < $max_links) $paginacao = pagina + 1;
		else                     $paginacao = pagina;

		# Paginação com restrição de links da paginação
		# Pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links = $mult_pag->Construir_Links("strings", "sim");

		# Função que limita a quantidade de links no rodapé
		$links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0 ; $n < count($links_limitados) ; $n++) {
			echo "<font color='#DDDDDD'>" . $links_limitados[$n] . "</font>&nbsp;&nbsp;";
		}

		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ($pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0) {
			echo "<br>";
			echo "<div>";
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}
		##### PAGINAÇÃO - FIM #####
	}
}
include "rodape.php"
?>
