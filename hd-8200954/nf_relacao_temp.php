<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$title = traduz("relacao.de.pedido.de.pecas",$con,$cook_idioma);
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
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><? fecho("numero.da.nf.para.consulta",$con,$cook_idioma); ?></b></font>
		<input type='text' name='nf' value=''>
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_nf_consulta.btn_acao_pesquisa.value == '' ) { document.frm_nf_consulta.btn_acao_pesquisa.value='continuar' ; document.frm_nf_consulta.submit() } else { alert ('Aguarde submissão') }"
			 alt="Continuar busca pelo Pedido" style='border:0 solid white;cursor: pointer;padding-top:2px'>
	</td>
	<td align="right" nowrap><a style='font-size:11px;margin-right:4px' href='<? echo $PHP_SELF."?listar=todas"; ?>'><? fecho("listar.todos.as.notas.fiscais",$con,$cook_idioma); ?></a></td>
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
$login_fabrica_aux = $login_fabrica;
if($login_fabrica == '51' or $login_fabrica == '81' or $login_fabrica == '10'){
	$login_fabrica_aux = '51,81,10';
}
if ((strlen($nf) > 0 AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0){
	$sql = "SELECT  to_char(tbl_faturamento.emissao, 'DD/MM/YYYY')            AS emissao         ,
					to_char(tbl_faturamento.saida, 'DD/MM/YYYY')              AS saida           ,
					to_char(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY')   AS previsao_chegada,
					trim(tbl_faturamento.pedido::text)                        AS pedido          ,
					trim(tbl_faturamento.nota_fiscal::text)                   AS nota_fiscal     ,
					trim(tbl_faturamento.total_nota::text)                    AS total_nota      ,
					tbl_faturamento.nf_os,
					TO_CHAR(tbl_faturamento.cancelada, 'DD/MM/YYYY') as cancelada,
					tbl_faturamento.faturamento
			FROM    tbl_faturamento
			WHERE   tbl_faturamento.posto   = $login_posto
			AND     tbl_faturamento.fabrica in ($login_fabrica_aux) ";
	
	if (strlen($nf) > 0) $sql .= "AND tbl_faturamento.nota_fiscal ILIKE '%$nf' ";
	
	$sql .= "ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.total_nota DESC";
	
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
			
			$emissao          = trim(pg_result($res,$i,'emissao'));
			$saida            = trim(pg_result($res,$i,'saida'));
			$previsao_chegada = trim(pg_result($res,$i,'previsao_chegada'));
			$pedido           = trim(pg_result($res,$i,'pedido'));
			$nota_fiscal      = trim(pg_result($res,$i,'nota_fiscal'));
			$total_nota       = trim(pg_result($res,$i,'total_nota'));
			$faturamento      = trim(pg_result($res,$i,'faturamento'));
			$cancelada		  = pg_result($res,$i,'cancelada');
			if ($login_fabrica == 43){
				if (strlen($pedido) == 0) {
					$sql_pedido = "SELECT DISTINCT pedido FROM tbl_faturamento_item WHERE faturamento = $faturamento";
					$res_pedido = @pg_exec($con,$sql_pedido);
					if (@pg_numrows($res_pedido) > 0) {
						$pedido = trim(pg_result($res_pedido,0,'pedido'));
					}
				}
			}

			$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					WHERE  tbl_pedido.pedido  = $pedido
					AND    tbl_pedido.posto   = $login_posto
					AND    tbl_pedido.fabrica = $login_fabrica;";
			$resx = @pg_exec ($con,$sql);
			
			if (@pg_numrows($resx) > 0) {
				$xpedido = trim(pg_result($resx,0,pedido));
			}
			
			if ($i == 0) {
				echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
				echo "<tr height='20' bgcolor='#999999'>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("nota.fiscal",$con,$cook_idioma); echo"</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("emissao",$con,$cook_idioma); echo "</b></font></td>";
				if($login_fabrica == 87) {
					echo "<td align='center' style='font-weight:bold;font-size:13px; font-family: Geneva,Arial,san-serif;'>Status</td>";
				}
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("saida",$con,$cook_idioma); echo "</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("previsao.chegada",$con,$cook_idioma); echo "</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("pedido",$con,$cook_idioma); echo "</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("total.nota",$con,$cook_idioma); echo "</b></font></td>";
				echo "</tr>";
			}
			
			echo "<tr bgcolor='$cor'>";
			
			echo "<td align='center'>";
			if (strlen ($cancelada) == 0 AND strlen($xpedido) > 0) {
				echo "<a href='pedido_finalizado.php?pedido=$pedido'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal</font></a>";
			}elseif (strlen ($cancelada) == 0 AND strlen($faturamento) > 0 AND ($login_fabrica == 51 or $login_fabrica == 81)) {
				echo "<a href='nf_detalhe.php?faturamento=$faturamento'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal</font></a>";
			}else{
				if($login_fabrica==11 or $login_fabrica == 45) { 
					echo "<a href='nf_detalhe.php?faturamento=$faturamento'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal</font></a>";
				} else {
					echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal</font>";
				}
			}
			echo "</td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$emissao</font></td>";
			if($login_fabrica == 87) {
                 echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>"; 
                     echo !empty($cancelada) ? 'Cancelada - ' . $cancelada : '&nbsp;';
                 echo '</font></td>';
            }
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$saida</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$previsao_chegada</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$pedido</font></td>";
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
			fecho("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array("<b>$resultado_inicial</b>","<b>$resultado_final</b>","<b>$registros</b>"));
			echo "<font color='#cccccc' size='1'>";
			fecho("pagina.%.de.%",$con,$cook_idioma,array("<b>$valor_pagina</b>","<b>$numero_paginas</b>"));
			echo "</font>";
			echo "</div>";
		}
		// ##### PAGINACAO ##### //
	}else{
		echo "<p>";
		
		echo "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		
		echo "<td valign='top' align='center'>";
		fecho("nao.foi.encontrado.notas.fiscais",$con,$cook_idioma);
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
	}
}
?>

<p>

<? include "rodape.php"; ?>
