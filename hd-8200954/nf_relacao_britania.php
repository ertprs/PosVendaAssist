<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$title = "Relação de Pedido de pecas";
$layout_menu = 'pedido';
include "cabecalho.php";
?>

<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->

<p>

<table width="600" border="0" cellpadding="2" cellspacing="0" align="center">
<form name='frm_nf_consulta' action='<? echo $PHP_SELF; ?>' method='get'>
<input type='hidden' name='btn_acao_pesquisa' value=''>
<tr height="22" bgcolor="#bbbbbb">
	<td nowrap>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Número da NF para Consulta</b></font>
		<input type='text' name='nf' value=''>
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_nf_consulta.btn_acao_pesquisa.value == '' ) { document.frm_nf_consulta.btn_acao_pesquisa.value='continuar' ; document.frm_nf_consulta.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar busca pelo Pedido" border='0' style='cursor: pointer'>
	</td>
	<td align="right" nowrap><a href='<? echo $PHP_SELF."?listar=todas"; ?>'>Listar todos as NF´s</a></td>
</tr>
</form>
</table>

<?
$btn_acao_pesquisa = $_POST['btn_acao_pesquisa'];
if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];

$listar = $_POST['listar'];
if (strlen($_GET['listar']) > 0) $listar = $_GET['listar'];

$nf = $_POST['nf'];
if (strlen($_GET['nf']) > 0) $nf = $_GET['nf'];

if (strlen($nf) > 0) {
	$nf = trim($nf);
	$nf = str_replace (".","",$nf);
	$nf = str_replace ("-","",$nf);
	$nf = str_replace ("/","",$nf);
}

if ((strlen($nf) > 0 AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0){
	$sql = "SELECT  tbl_faturamento.faturamento                                                  ,
					to_char(tbl_faturamento.emissao, 'DD/MM/YYYY')            AS emissao         ,
					to_char(tbl_faturamento.saida, 'DD/MM/YYYY')              AS saida           ,
					to_char(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY')   AS previsao_chegada,
					to_char(tbl_faturamento.cancelada, 'DD/MM/YYYY')          AS cancelada       ,
					trim(tbl_faturamento.nota_fiscal::text)                   AS nota_fiscal     ,
					trim(tbl_faturamento.total_nota::text)                    AS total_nota      ,
					tbl_faturamento.serie                                                        ,
					tbl_faturamento.transp                                                       ,
					tbl_condicao.descricao AS condicao
			FROM         tbl_faturamento
			LEFT JOIN    tbl_condicao       USING (condicao)
			WHERE   tbl_faturamento.posto   = $login_posto
			AND     tbl_faturamento.fabrica = $login_fabrica ";
	
	if (strlen($nf) > 0) $sql .= "AND tbl_faturamento.nota_fiscal ILIKE '%$nf' ";
	
	$sql .= "ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.nota_fiscal DESC";
	
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";
	
	// ##### PAGINACAO ##### //
	require "_class_paginacao.php";
	
	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página
	
	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	
	// ##### PAGINACAO ##### //
	
	if (@pg_numrows($res) > 0) {
		echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff'>";
		echo "<tr>";
		echo "<td><img height='1' width='20' src='imagens/spacer.gif'></td>";
		echo "<td valign='top' align='center'>";
		
		echo "<p>";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$faturamento      = trim(pg_result($res,$i,faturamento));
			$emissao          = trim(pg_result($res,$i,emissao));
			$saida            = trim(pg_result($res,$i,saida));
			$previsao_chegada = trim(pg_result($res,$i,previsao_chegada));
			$cancelada        = trim(pg_result($res,$i,cancelada));
			$nota_fiscal      = trim(pg_result($res,$i,nota_fiscal));
			$total_nota       = trim(pg_result($res,$i,total_nota));
			$serie            = trim(pg_result($res,$i,serie));
			$condicao         = trim(pg_result($res,$i,condicao));
			$transp           = strtoupper (trim(pg_result($res,$i,transp)));
			
			if ($i == 0) {
				echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
				echo "<tr height='20' bgcolor='#999999'>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Nota Fiscal</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Série</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Transp.</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Tipo</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Emissão</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Saída</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Total Nota</b></font></td>";
				echo "</tr>";
			}
			
			if (strlen ($cancelada) > 0) $cor = '#FF6633';

			echo "<tr bgcolor='$cor'>";

			echo "<td align='center'>" ;
			if (strlen ($cancelada) == 0) {
				echo "<a href='nf_detalhe_britania.php?faturamento=$faturamento'>";
				echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal</font>";
				echo "</a>";
			}else{
				echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal (cancelada)</font>";
			}
			echo "</td>";

			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$serie</font></td>";
			echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$transp</font></td>";
			echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$condicao</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$emissao</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$saida</font></td>";
			echo "<td align='right'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>". number_format($total_nota,2,",",".") ."</font></td>";
			echo "</tr>";
		}
		echo "</table>";
		
		echo "</td>";
		echo "<td><img height='1' width='16' src='imagens/spacer.gif'></td>";
		
		echo "</tr>";
		echo "</table>";
		
		// ##### PAGINACAO ##### //
		// links da paginacao
		echo "<br>";
		
		echo "<div>";
		
		if($pagina < $max_links) { 
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}
		
		// paginacao com restricao de links da paginacao
		
		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("strings", "sim");
		
		// função que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);
		
		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}
		
		echo "</div>";
		
		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();
		
		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);
		
		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;
		
		if ($registros > 0){
			echo "<br>";
			echo "<div>";
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}
		// ##### PAGINACAO ##### //
	}else{
		echo "<p>";
		
		echo "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		
		echo "<td valign='top' align='center'>";
		echo "<h4>Não foi encontrado Notas Fiscais.</h4>";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
	}
}
?>

<p>

<? include "rodape.php"; ?>
