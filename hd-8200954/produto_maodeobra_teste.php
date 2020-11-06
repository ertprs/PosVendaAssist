<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

##### GERAR RELATÓRIO - INÍCIO #####
$relatorio = $_GET["relatorio"];

if (strtoupper($relatorio) == "GERAR") {
	$linha = $_GET["linha"];
	 
	##### Mão de Obra PARA LINHA DEWALT #####
	if ($linha == "198" and 1 ==2) {
		$sql =	"SELECT tbl_produto.produto                               ,
						tbl_produto.referencia                     AS produto_referencia,
						tbl_produto.descricao                      AS produto_descricao,
						tbl_produto.Voltagem                       AS produto_Voltagem,
						tbl_produto.mao_de_obra                    AS produto_mao_de_obra            ,
						tbl_defeito_constatado.descricao           AS defeito_constatado                                  ,
						tbl_produto_defeito_constatado.mao_de_obra AS defeito_constatado_mao_de_obra
				FROM      tbl_produto
				JOIN      tbl_linha                      ON tbl_linha.linha = tbl_produto.linha
				LEFT JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.produto            = tbl_produto.produto
				LEFT JOIN tbl_defeito_constatado         ON tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado
				WHERE tbl_linha.fabrica = $login_fabrica
				AND   tbl_linha.linha   = $linha
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
			fputs($arq,"<title>TABELA DE Mão de Obra DOS PRODUTOS - $data</title>");
			fputs($arq,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs($arq,"<meta http-equiv=\"Content-Type\" content=\"text/html; charset=ISO-8859-1\" />");
			fputs($arq,"</head>");
			fputs($arq,"<body>");

			fputs($arq,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");

			for ($j = 0 ; $j < pg_numrows($res) ; $j++) {
				$produto                        = trim(pg_result($res,$j,produto));
				$produto_referencia             = trim(pg_result($res,$j,produto_referencia));
				$produto_descricao              = trim(pg_result($res,$j,produto_descricao));
				$produto_Voltagem               = trim(pg_result($res,$j,produto_Voltagem));
				$produto_mao_de_obra            = number_format(trim(pg_result($res,$j,produto_mao_de_obra)),2,",",".");
				$defeito_constatado             = trim(pg_result($res,$j,defeito_constatado));
				$defeito_constatado_mao_de_obra = number_format(trim(pg_result($res,$j,defeito_constatado_mao_de_obra)),2,",",".");
				
				if ($produto_anterior != $produto) {
					fputs($arq,"<tr>");
					fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>Referência</b></td>");
					fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>Descrição</b></td>");
					fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>Voltagem</b></td>");
					fputs($arq,"<td bgcolor='#E9F3F3' style='text-align:right;'><b>Mão de Obra</b></td>");
					fputs($arq,"</tr>");
					fputs($arq,"<tr>");
					fputs($arq,"<td nowrap align='center'> &nbsp; " . $produto_referencia . " &nbsp; </td>");
					fputs($arq,"<td nowrap align='left'> &nbsp; " . $produto_descricao . " &nbsp; </td>");
					fputs($arq,"<td nowrap align='center'> &nbsp; " . $produto_Voltagem . " &nbsp; </td>");
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
			echo "<p align='center' class='texto_avulso'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b><a href='xls/tabela-produto-maodeobra-$login_fabrica-$data_arquivo.xls' target='_blank'>Clique aqui</a> para fazer o download do arquivo em EXCEL.<br>Você poderá visualizar, imprimir e salvar<br>a tabela para consultas off-line.</b></font></p>";
		}
	}else{
	##### Mão de Obra PARA OUTRAS LINHAS #####
		$sql =	"SELECT tbl_produto.produto     ,
						tbl_produto.referencia  ,
						tbl_produto.descricao   ,
						tbl_produto.Voltagem   ,
						tbl_produto.mao_de_obra
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tbl_linha.fabrica = $login_fabrica
				AND   tbl_linha.linha   = $linha
				AND   tbl_produto.ativo IS TRUE
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
			fputs($arq,"<title>TABELA DE Mão de Obra DOS PRODUTOS - $data</title>");
			fputs($arq,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs($arq,"<meta http-equiv=\"Content-Type\" content=\"text/html; charset=ISO-8859-1\" />");
			fputs($arq,"</head>");
			fputs($arq,"<body>");

			fputs($arq,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
			fputs($arq,"<tr>");
			fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>Referência</b></td>");
			fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>Descrição</b></td>");
			fputs($arq,"<td bgcolor='#E9F3F3' align='center'><b>Voltagem</b></td>");
			fputs($arq,"<td bgcolor='#E9F3F3' style='text-align:right;'><b>Mão de Obra</b></td>");
			fputs($arq,"</tr>");

			for ($j = 0 ; $j < pg_numrows($res) ; $j++) {
				$referencia  = trim(pg_result($res,$j,referencia));
				$descricao   = trim(pg_result($res,$j,descricao));
				$Voltagem    = trim(pg_result($res,$j,Voltagem));
				$mao_de_obra = number_format(trim(pg_result($res,$j,mao_de_obra)),2,",",".");

				fputs($arq,"<tr>");
				fputs($arq,"<td nowrap align='center'> &nbsp; " . $referencia . " &nbsp; </td>");
				fputs($arq,"<td nowrap align='left'> &nbsp; " . $descricao . " &nbsp; </td>");
				fputs($arq,"<td nowrap align='center'> &nbsp; " . $Voltagem . " &nbsp; </td>");
				fputs($arq,"<td nowrap align='center'> &nbsp; R$ " . $mao_de_obra . " &nbsp; </td>");
				fputs($arq,"</tr>");
			}
			fputs($arq,"</table>");
			fputs($arq,"</body>");
			fputs($arq,"</html>");
			fclose($arq);

			flush();

			rename($tmp_xls, "/www/assist/www/xls/tabela-produto-maodeobra-$login_fabrica-$data_arquivo.xls");

			echo "<br>";
			echo "<p align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b><a href='xls/tabela-produto-maodeobra-$login_fabrica-$data_arquivo.xls' target='_blank'>Download</a> em EXCEL.<br>Você poderá visualizar, imprimir e salvar<br>a tabela para consultas off-line.</b></font></p>";
		}
	}
	exit;
}
##### GERAR RELATÓRIO - FIM #####

$erro = "";

if (strlen($_POST["botao"]) > 0) $botao = strtoupper($_POST["botao"]);

if ($botao == "PESQUISAR") {
	if (strlen($_POST["linha"]) > 0) $linha = $_POST["linha"];;
	if (strlen($_GET["linha"]) > 0)  $linha = $_GET["linha"];;

	if (strlen($linha) == 0) $erro = " Selecione a linha para realizar a pesquisa. ";
}

$layout_menu = "os";
$title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";

include "cabecalho.php";
?>

<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.titulo_coluna{
	background-color: #596D9B;
	color: white;
	font: normal normal bold 11px/normal Arial;
	text-align: center;
}
input[type=button]{
	cursor:pointer;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

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
</style>

<script language="JavaScript">
function FuncGerarExcel (linha) {
	var largura = window.screen.width;
	var tamanho = window.screen.height;
	var x = (largura / 2) - 250;
	var y = (tamanho / 2) - 125;
	var link = "<?echo $PHP_SELF?>?relatorio=gerar&linha=" + linha;
	window.open(link, "JANELA", "toolbar=no, location=no, status=yes, scrollbars=yes, menubar=no, directories=no, width=500, height=150, top=" + y + ", left=" + x);
}
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
<table align="center" width="700" cellspacing="1" class="formulario">
	<tr class="titulo_tabela">
		<td>Parâmetros de Pesquisa</td>
	</tr>

	<tr>
		<td align="center">
			<br />
			Linha
			<select name="linha" size="1" class="frm">
				<option value=""></option>
				<option value="198" <? if ($linha == "198") echo "selected"; ?>>DeWalt</option>
				<option value="199" <? if ($linha == "199") echo "selected"; ?>>Eletro</option>
				<option value="200" <? if ($linha == "200") echo "selected"; ?>>Ferramentas Black & Decker</option>
				<option value="467" <? if ($linha == "467") echo "selected"; ?>>Porter Cable</option>
			</select>
			&nbsp;
			<input type="button" value="Pesquisar"  onclick="javascript: if (document.frm_pesquisa.botao.value == '' ) { document.frm_pesquisa.botao.value='PESQUISAR'; document.frm_pesquisa.submit(); }else{ alert ('Aguarde submissão'); }" />	<br />
			<br />
		</td>
	</tr>

</table>
</form>

<?

if (strlen($botao) > 0 && strlen($erro) == 0) {
	##### Mão de Obra PARA LINHA DEWALT #####
	if ($linha == "198" and 1 ==2) {
		$sql =	"SELECT tbl_produto.produto                               ,
						tbl_produto.referencia                     AS produto_referencia,
						tbl_produto.descricao                      AS produto_descricao,
						tbl_produto.Voltagem                       AS produto_Voltagem,
						tbl_produto.mao_de_obra                    AS produto_mao_de_obra            ,
						tbl_defeito_constatado.descricao           AS defeito_constatado                                  ,
						tbl_produto.mao_de_obra AS defeito_constatado_mao_de_obra
				FROM      tbl_produto
				JOIN      tbl_linha                      ON tbl_linha.linha = tbl_produto.linha
				LEFT JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.produto            = tbl_produto.produto
				LEFT JOIN tbl_defeito_constatado         ON tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado
				WHERE tbl_linha.fabrica = $login_fabrica
				AND   tbl_linha.linha   = $linha
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
		
		if (pg_numrows($res) == 0) {
			echo "Não foram encontrados resultados para esta pesquisa";
		}else{
			echo '<table align="center" width="700" cellspacing="1" class="tabela">';
			echo "<tr class='titulo_coluna'>";
			echo "<td nowrap>Referência</td>";
			echo "<td nowrap>Descrição</td>";
			echo "<td nowrap>Voltagem</td>";
			echo "<td nowrap  align='right'>Mão de Obra</td>";
			echo "</tr>";
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$produto                        = trim(pg_result($res,$i,produto));
				$produto_referencia             = trim(pg_result($res,$i,produto_referencia));
				$produto_descricao              = trim(pg_result($res,$i,produto_descricao));
				$produto_Voltagem               = trim(pg_result($res,$i,produto_Voltagem));
				$produto_mao_de_obra            = number_format(trim(pg_result($res,$i,produto_mao_de_obra)),2,",",".");
				$defeito_constatado             = trim(pg_result($res,$i,defeito_constatado));
				$defeito_constatado_mao_de_obra = number_format(trim(pg_result($res,$i,defeito_constatado_mao_de_obra)),2,",",".");

				if ($produto_anterior != $produto) {
					echo "<tr class='subtitulo'>";
					echo "<td nowrap align='center'>&nbsp;" . $produto_referencia . "</td>";
					echo "<td nowrap>&nbsp;" . $produto_descricao . "</td>";
					echo "<td nowrap align='center'>&nbsp;" . $produto_Voltagem . "</td>";
					echo "<td nowrap align='center'>&nbsp;R$ " . $produto_mao_de_obra . "</td>";
					echo "</tr>";
				}
				
				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
				
				echo "<tr bgcolor='$cor'>";
				echo "<td nowrap colspan='3'>&nbsp;" . $defeito_constatado . "</td>";
				echo "<td nowrap align='center'>&nbsp;R$ " . $defeito_constatado_mao_de_obra . "</td>";
				echo "</tr>";
				
				$produto_anterior = $produto;
			}
			echo "</table>";
			echo "<br>";
			echo "<input type='button' value='Clique aqui para gerar arquivo em EXCEL' onclick=\"FuncGerarExcel('$linha')\" />";
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
	##### Mão de Obra PARA OUTRAS LINHAS #####
		$sql =	"SELECT tbl_produto.produto     ,
						tbl_produto.referencia  ,
						tbl_produto.descricao   ,
						tbl_produto.Voltagem    ,
						tbl_produto.mao_de_obra
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tbl_linha.fabrica = $login_fabrica
				AND   tbl_linha.linha   = $linha
				AND   tbl_produto.ativo IS TRUE
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
		
		if (pg_numrows($res) == 0) {
			echo "<label style='font: bold 13px Arial;'>Não foram encontrados resultados para esta pesquisa</label>";
		}else{
			echo '<table align="center" width="700" cellspacing="1" class="tabela">';
			if (strlen($linha) > 0) {
				$sql_linha = "SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica AND linha = $linha;";
				$res_linha = pg_exec($con, $sql_linha);
				if (pg_numrows($res_linha) == 1) {
					$linha_nome = trim(pg_result($res_linha,0,0));
					echo "<tr class='Titulo'>";
					echo "<td colspan='4'>Produtos da Linha " . $linha_nome . "</td>";
					echo "</tr>";
				}
			}
			echo "<tr class='titulo_coluna'>";
			echo "<td nowrap>Referência</td>";
			echo "<td nowrap>Descrição</td>";
			echo "<td nowrap>Voltagem</td>";
			echo "<td nowrap align='right'>Mão de Obra</td>";
			echo "</tr>";
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$referencia  = trim(pg_result($res,$i,referencia));
				$descricao   = trim(pg_result($res,$i,descricao));
				$Voltagem    = trim(pg_result($res,$i,Voltagem));
				$mao_de_obra = number_format(trim(pg_result($res,$i,mao_de_obra)),2,",",".");

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				echo "<tr bgcolor='$cor'>";
				echo "<td nowrap align='center'>&nbsp;" . $referencia . "</td>";
				echo "<td nowrap>&nbsp;" . $descricao . "</td>";
				echo "<td nowrap align='center'>&nbsp;" . $Voltagem . "</td>";
				echo "<td nowrap align='right'>&nbsp;R$ " . $mao_de_obra . "</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";
			echo "<input type='button' value='Download em Excel' onclick=\"FuncGerarExcel('$linha')\" />";
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