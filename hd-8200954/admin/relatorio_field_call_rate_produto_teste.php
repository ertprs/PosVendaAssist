<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$msg = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);

##### GERAR RELATÓRIO EXCEL #####
if ($acao == "RELATORIO") {
	$data_inicial = trim($_GET["data_inicial"]);
	$data_final   = trim($_GET["data_final"]);
	$peca         = trim($_GET["peca"]);
	$posto        = trim($_GET["posto"]);
	$situacao     = trim($_GET["situacao"]);
	$ordem        = trim($_GET["ordem"]);
	
	$sql =	"SELECT tbl_os.sua_os                                               ,
					tbl_os.serie                                                ,
					TO_CHAR(tbl_os.data_digitacao,'MM')   AS mes                ,
					TO_CHAR(tbl_os.data_digitacao,'YYYY') AS ano                ,
					tbl_produto.referencia                AS produto_referencia ,
					tbl_produto.descricao                 AS produto_descricao  ,
					tbl_posto_fabrica.codigo_posto        AS posto_codigo       ,
					tbl_posto.nome                        AS posto_nome
			FROM      tbl_os
			LEFT JOIN tbl_os_produto    ON  tbl_os_produto.os         = tbl_os.os
			LEFT JOIN tbl_os_item       ON  tbl_os_item.os_produto    = tbl_os_produto.os_produto
			JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
			JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
			JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";

	if (strlen($produto) > 0) $sql .= " AND tbl_os.produto = $produto";
	if (strlen($peca) > 0) $sql .= " AND tbl_os_item.peca = $peca";
	if (strlen($posto) > 0) $sql .= " AND tbl_os.posto = $posto";

	if ($situacao == "aberta") $sql .= " AND tbl_os.excluida IS FALSE
										 AND tbl_os.data_fechamento IS NULL";
	if ($situacao == "fechada") $sql .= " AND tbl_os.excluida IS FALSE
										  AND tbl_os.data_fechamento IS NOT NULL";
	if ($situacao == "excluida") $sql .= " AND tbl_os.excluida IS TRUE";

	if ($ordem == "OS")    $sql .= " ORDER BY ano, mes, tbl_os.sua_os;";
	if ($ordem == "SERIE") $sql .= " ORDER BY ano, mes, tbl_os.serie;";
	if ($ordem == "POSTO") $sql .= " ORDER BY ano, mes, tbl_posto_fabrica.codigo_posto;";
	
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		flush();

		$data = date("Y_m_d-H_i_s");

		$arq = fopen("/tmp/assist/field-call-rate-produto-serie-$login_fabrica-$data.html","w");
		fputs($arq,"<html>");
		fputs($arq,"<head>");
		fputs($arq,"<title>FIELD CALL-RATE - ".date("d/m/Y H:i:s"));
		fputs($arq,"</title>");
		fputs($arq,"</head>");
		fputs($arq,"<body>");

		fputs($arq,"<table border='1'>");

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$sua_os       = pg_result($res,$i,sua_os);
			$serie        = pg_result($res,$i,serie);
			$mes          = pg_result($res,$i,mes);
			$ano          = pg_result($res,$i,ano);
			$produto_referencia   = pg_result($res,$i,produto_referencia);
			$produto_descricao    = pg_result($res,$i,produto_descricao);
			$posto_codigo = pg_result($res,$i,posto_codigo);
			$posto_nome   = pg_result($res,$i,posto_nome);

			if ($mes <> $mes_anterior) {
				if ($mes{0} == 0) $x_mes = str_replace("0", "", $mes);
				else              $x_mes = $mes;
				fputs($arq,"<tr>");
				fputs($arq,"<td align='center' colspan='4'><b> &nbsp; Aparelho: " . $produto_referencia . " - " . $produto_descricao . " &nbsp; <br> &nbsp; Mês: " . $meses[$x_mes] . "/$ano &nbsp; </b></td>");
				fputs($arq,"</tr>");
				fputs($arq,"<tr>");
				fputs($arq,"<td align='center'><b>OS</b></td>");
				fputs($arq,"<td align='center'><b>Nº DE SÉRIE</b></td>");
				fputs($arq,"<td align='center'><b>POSTO</b></td>");
				fputs($arq,"<td align='center'><b>NOME DO POSTO</b></td>");
				fputs($arq,"</tr>");
			}

			fputs($arq,"<tr>");
			fputs($arq,"<td align='center'>&nbsp;" . $sua_os . "&nbsp;</td>");
			fputs($arq,"<td align='center'>&nbsp;" . $serie . "&nbsp;</td>");
			fputs($arq,"<td align='center'>&nbsp;" . $posto_codigo . "&nbsp;</td>");
			fputs($arq,"<td align='center'>&nbsp;" . $posto_nome . "&nbsp;</td>");
			fputs($arq,"</tr>");

			$mes_anterior = $mes;
		}
		fputs($arq,"</table>");
		fputs($arq,"</body>");
		fputs($arq,"</html>");
		fclose($arq);
	}

	rename("/tmp/assist/field-call-rate-produto-serie-$login_fabrica-$data.html", "/www/assist/www/admin/xls/field-call-rate-produto-serie-$login_fabrica-$data.xls");
//	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/field-call-rate-produto-serie-$login_fabrica-$data.xls /tmp/assist/field-call-rate-produto-serie-$login_fabrica-$data.html`;

	echo "<br>";
	echo "<p align='center'><font face='Verdana, Tahoma, Arial' size='2' color='#000000'><b>Relatório gerado com sucesso!<br><a href='xls/field-call-rate-produto-serie-$login_fabrica-$data.xls' target='_blank'>Clique aqui</a> para fazer o download do arquivo em EXCEL.<br>Você poderá ver, imprimir e salvar a tabela para consultas off-line.</b></font></p>";
	exit;
}

if (strlen($acao) > 0) {

	##### Pesquisa entre datas #####
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {

		if (strlen($x_data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);
			$data_inicial = date("01/m/Y", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
		}else{
			$msg .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($x_data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
			$data_final   = date("t/m/Y", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
		}else{
			$msg .= " Preencha o campo Data Final para realizar a pesquisa. ";
		}
	}else{
		$msg .= " Informe as datas corretas para realizar a pesquisa. ";
	}

	##### Pesquisa de produto #####
	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);

	if (strlen($produto_referencia) > 0 && strlen($produto_descricao) > 0) {
		$sql =	"SELECT tbl_produto.produto    ,
						tbl_produto.referencia ,
						tbl_produto.descricao
				FROM tbl_produto
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE tbl_linha.fabrica = $login_fabrica";
		if (strlen($produto_referencia) > 0) $sql .= " AND tbl_produto.referencia_pesquisa = '$produto_referencia'";

		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$produto            = pg_result($res,0,produto);
			$produto_referencia = pg_result($res,0,referencia);
			$produto_descricao  = pg_result($res,0,descricao);
		}else{
			$msg .= " Produto não encontrado. ";
		}
	}

	##### Pesquisa de peça #####
	$peca_referencia = trim($_POST["peca_referencia"]);
	$peca_descricao  = trim($_POST["peca_descricao"]);

	if (strlen($peca_referencia) > 0 || strlen($peca_descricao) > 0) {
		$sql =	"SELECT tbl_peca.peca    ,
						tbl_peca.referencia ,
						tbl_peca.descricao
				FROM tbl_peca
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
				WHERE tbl_fabrica.fabrica = $login_fabrica";
		if (strlen($peca_referencia) > 0) {
			$peca_referencia = str_replace (".","",$peca_referencia);
			$peca_referencia = str_replace ("-","",$peca_referencia);
			$peca_referencia = str_replace ("/","",$peca_referencia);
			$peca_referencia = str_replace (" ","",$peca_referencia);
			
			$sql .= " AND tbl_peca.referencia_pesquisa = '$peca_referencia'";
		}
		if (strlen($peca_descricao) > 0) $sql .= " AND tbl_peca.descricao ILIKE '%$peca_descricao%'";

		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$peca            = pg_result($res,0,peca);
			$peca_referencia = pg_result($res,0,referencia);
			$peca_descricao  = pg_result($res,0,descricao);
		}else{
			$msg .= " Peça não encontrada. ";
		}
	}
	
	##### Pesquisa de posto #####
	$posto_codigo = trim($_POST["posto_codigo"]);
	$posto_nome   = trim($_POST["posto_nome"]);

	if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
		$sql =	"SELECT tbl_posto_fabrica.posto        ,
						tbl_posto_fabrica.codigo_posto ,
						tbl_posto.nome
				FROM tbl_posto
				JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
				                        AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto        = pg_result($res,0,posto);
			$posto_codigo = pg_result($res,0,codigo_posto);
			$posto_nome   = pg_result($res,0,nome);
		}else{
			$msg .= " Posto não encontrado. ";
		}
	}
	
//	if ((strlen($data_inicial) == 0 && strlen($data_final) == 0) && (strlen($posto) == 0 || strlen($peca) == 0 || strlen($produto) == 0)) {
	if (strlen($peca) == 0 && strlen($produto) == 0) {
		$msg .= " Selecione mais parâmentros para a pesquisa. ";
	}

	$situacao = trim($_POST["situacao"]);
	
	$ordem = trim($_POST["ordem"]);
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE SÉRIE e POSTO";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
</style>

<script language="JavaScript">
function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}

function GerarRelatorio (data_inicial, data_final, produto, peca, posto, situacao, ordem) {
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = '<?echo $PHP_SELF?>?acao=RELATORIO&data_inicial=' + data_inicial + '&data_final=' + data_final + '&produto=' + produto + '&peca=' + peca + '&posto=' + posto + '&situacao=' + situacao + '&ordem=' + ordem;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>

<? include "javascript_pesquisas.php"; ?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<? include "javascript_calendario.php";  // adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="acao">

<table width="400" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr class="Titulo">
		<td colspan="4">PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF"  align='left'>
		<td width="10">&nbsp;</td>
		<td align=''>Data Inicial</td>
		<td>Data Final</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			<!--
			<img src="imagens/lupa.png" align="absmiddle" onclick="javascript: showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
			-->
		</td>
		<td>
			<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			
			<!--
			<img src="imagens/lupa.png" align="absmiddle" onclick="javascript: showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
			-->
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>Referência do Produto</td>
		<td>Descrição do Produto</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="produto_referencia" size="15" value="<?echo $produto_referencia?>" class="frm">
			<img src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'referencia')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>
			<input type="text" name="produto_descricao" size="20" value="<?echo $produto_descricao?>" class="frm">
			<img src="imagens/lupa.png" align="absmiddle" onclick="fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao, 'descricao')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>Referência da Peça</td>
		<td>Descrição da Peça</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="peca_referencia" size="15" value="<?echo $peca_referencia?>" class="frm">
			<img src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_peca (document.frm_relatorio.peca_referencia, document.frm_relatorio.peca_descricao, 'referencia');" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>
			<input type="text" name="peca_descricao" size="20" value="<?echo $peca_descricao?>" class="frm">
			<img src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.peca_referencia, document.frm_relatorio.peca_descricao, 'descricao');" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>Posto</td>
		<td>Nome do Posto</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="posto_codigo" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_relatorio.posto_codigo, document.frm_relatorio.posto_nome, 'codigo');" <? } ?> value="<? echo $posto_codigo ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.posto_codigo, document.frm_relatorio.posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_relatorio.posto_codigo, document.frm_relatorio.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.posto_codigo, document.frm_relatorio.posto_nome, 'nome')">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td width="10">&nbsp;</td>
		<td>
			Situação da OS
		</td>
		<td align="left">
			<select name="situacao" size="1" class="frm">
				<option value="tudo" <? if ($situacao == "tudo") echo "selected"; ?>>Todas</option>
				<option value="aberta" <? if ($situacao == "aberta") echo "selected"; ?>>Abertas</option>
				<option value="fechada" <? if ($situacao == "fechada") echo "selected"; ?>>Fechadas</option>
				<option value="excluida" <? if ($situacao == "excluida") echo "selected"; ?>>Excluidas</option>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td colspan="2">Ordenar por</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>
			<input type="radio" name="ordem" value="OS" class="frm" <? if (strlen($ordem) == 0 || $ordem == "OS") echo "checked"; ?>> OS
		</td>
		<td>
			<input type="radio" name="ordem" value="SERIE" class="frm" <? if (strlen($ordem) == 0 || $ordem == "SERIE") echo "checked"; ?>> Nº de Série
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td colspan="2">
			<input type="radio" name="ordem" value="POSTO" class="frm" <? if (strlen($ordem) == 0 || $ordem == "POSTO") echo "checked"; ?>> Código do Posto
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>
</form>

<br>

<?
flush();
if (strlen($acao) > 0 && strlen($msg) == 0) {
	$sql =	"SELECT distinct(tbl_os.sua_os)                               ,
					tbl_os.serie                                          ,
					TO_CHAR(tbl_os.data_digitacao,'MM')   AS mes          ,
					TO_CHAR(tbl_os.data_digitacao,'YYYY') AS ano          ,
					tbl_posto_fabrica.codigo_posto        AS posto_codigo ,
					tbl_posto.nome                        AS posto_nome
			FROM      tbl_os
			LEFT JOIN tbl_os_produto    ON  tbl_os_produto.os         = tbl_os.os
			LEFT JOIN tbl_os_item       ON  tbl_os_item.os_produto    = tbl_os_produto.os_produto
			JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
			JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";

	if (strlen($produto) > 0) $sql .= " AND tbl_os.produto = $produto";
	if (strlen($peca) > 0) $sql .= " AND tbl_os_item.peca = $peca";
	if (strlen($posto) > 0) $sql .= " AND tbl_os.posto = $posto";

	if ($situacao == "aberta") $sql .= " AND tbl_os.excluida IS FALSE
										 AND tbl_os.data_fechamento IS NULL";
	if ($situacao == "fechada") $sql .= " AND tbl_os.excluida IS FALSE
										  AND tbl_os.data_fechamento IS NOT NULL";
	if ($situacao == "excluida") $sql .= " AND tbl_os.excluida IS TRUE";

	if ($ordem == "OS")    $sql .= " ORDER BY ano, mes, tbl_os.sua_os;";
	if ($ordem == "SERIE") $sql .= " ORDER BY ano, mes, tbl_os.serie;";
	if ($ordem == "POSTO") $sql .= " ORDER BY ano, mes, tbl_posto_fabrica.codigo_posto;";
	
	//if (getenv("REMOTE_ADDR") == "201.13.179.45") echo nl2br($sql); exit;
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$sua_os       = pg_result($res,$i,sua_os);
			$serie        = pg_result($res,$i,serie);
			$mes          = pg_result($res,$i,mes);
			$ano          = pg_result($res,$i,ano);
			$posto_codigo = pg_result($res,$i,posto_codigo);
			$posto_nome   = pg_result($res,$i,posto_nome);

			if ($mes <> $mes_anterior) {
				if ($mes{0} == 0) $x_mes = str_replace("0", "", $mes);
				else              $x_mes = $mes;
				echo "<tr class='Titulo'>";
				echo "<td colspan='4'>Aparelho: $produto_referencia - $produto_descricao<br>";
				echo "Mês: " . $meses[$x_mes] . "/$ano</td>";
				echo "</tr>";
				echo "<tr class='Titulo'>";
				echo "<td>OS</td>";
				echo "<td>Nº de Série</td>";
				echo "<td>Posto</td>";
				echo "<td>Nome do posto</td>";
				echo "</tr>";
			}

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $sua_os . "</td>";
			echo "<td nowrap>" . $serie . "</td>";
			echo "<td nowrap align='center'>" . $posto_codigo . "</td>";
			echo "<td nowrap align='left'>" . $posto_nome . "</td>";
			echo "</tr>";

			$mes_anterior = $mes;
		}
		echo "</table>";

		echo "<br><a href=\"javascript: GerarRelatorio ('$x_data_inicial', '$x_data_final', '$produto', '$peca', '$posto', '$situacao', '$ordem');\"><font size='2'>Clique aqui para gerar arquivo do EXCEL</font></a><br>";
	}
	echo "<br><h2>Encontrado(s) " . pg_numrows($res) . " registro(s).</h2>";
}
echo "<br>";

include "rodape.php";
?>
