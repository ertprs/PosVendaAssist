<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';


$msg_erro = "";

if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];
else                                        $btn_acao_pesquisa = $_POST['btn_acao_pesquisa'];

if (strlen($_GET['listar']) > 0) $listar = $_GET['listar'];
else                             $listar = $_POST['listar'];

if (strlen($_GET['tipo_consulta']) > 0) $tipo_consulta = $_GET['tipo_consulta'];
else                                    $tipo_consulta = $_POST['tipo_consulta'];

if (strlen(trim($_GET['numero_nf'])) > 0) $numero_nf = trim($_GET['numero_nf']);
else                                      $numero_nf = trim($_POST['numero_nf']);

if (strlen(trim($_GET['numero_pedido'])) > 0) $numero_pedido = trim($_GET['numero_pedido']);
else                                          $numero_pedido = trim($_POST['numero_pedido']);

if (strlen($numero_pedido) == 0 AND strlen($numero_nf) == 0 AND $btn_acao_pesquisa == 'consultar')
$msg_erro .= "Digite o número da Nota Fiscal ou o número do Pedido. ";

$title = "Relação de Pedido de pecas";
$layout_menu = 'pedido';
include "cabecalho.php";
?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}
</style>

<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->

<br><br>

<? if (strlen($msg_erro) > 0) { ?>
<table width="500" border="0" cellpadding="2" cellspacing="2" align="center" bgcolor="#FF0000">
<tr>
	<td align="center"><font face="Verdana, Tahoma, Arial" size="2" color="#FFFFFF"><b><? echo $msg_erro ?></b></font></td>
</tr>
</table>
<? } ?>

<form name='frm_nf_consulta' action='<? echo $PHP_SELF; ?>' method='get'>

<table width="350" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td class="menu_top" colspan='3'>Selecione o tipo da consulta e preencha o campo</td>
</tr>
<tr class="table_line1" bgcolor="#F1F4FA">
	<td>Número da Nota Fiscal</td>
	<td><input type="text" name="numero_nf" value="<? echo $numero_nf ?>"></td>
</tr>
<tr class="table_line1" bgcolor="#F1F4FA">
	<td>Número do Pedido</td>
	<td><input type="text" name="numero_pedido" value="<? echo $numero_pedido ?>"></td>
</tr>
</table>

<br>

<input type='hidden' name='btn_acao_pesquisa' value=''>

<table width="250" border="0" cellpadding="2" cellspacing="0" align="center">
	<tr>
		<td bgcolor="#F0F0F0" align="center" nowrap onmouseover="this.style.backgroundColor='#BBBBBB';this.style.cursor='pointer'" onmouseout="this.style.backgroundColor='#F0F0F0';this.style.cursor='normal'">
			<a href="javascript: if (document.frm_nf_consulta.btn_acao_pesquisa.value == '' ) { document.frm_nf_consulta.btn_acao_pesquisa.value='consultar' ; document.frm_nf_consulta.submit() } else { alert ('Aguarde submissão') }" title="Consultar Pedidos">
			<font face="Verdana, Tahoma, Arial" size="2" color="#000000"><b>CONSULTAR</b></font>
			</a>
		</td>
		<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td bgcolor="#F0F0F0" align="center" nowrap onmouseover="this.style.backgroundColor='#BBBBBB';this.style.cursor='pointer'" onmouseout="this.style.backgroundColor='#F0F0F0';this.style.cursor='normal'">
			<a href='<? echo $PHP_SELF."?listar=todos"; ?>' title="Listar todas as Notas Fiscais">
			<font face="Verdana, Tahoma, Arial" size="2" color="#000000"><b>LISTAR TODOS</b></font>
			</a>
		</td>
	</tr>
</table>

</form>

<?
if ((strlen($msg_erro) == 0 AND $btn_acao_pesquisa == 'consultar') OR strlen($listar) > 0){
	$sql = "SELECT  to_char(tbl_faturamento.emissao, 'DD/MM/YYYY')            AS emissao         ,
					to_char(tbl_faturamento.saida, 'DD/MM/YYYY')              AS saida           ,
					to_char(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY')   AS previsao_chegada,
					trim(tbl_faturamento.pedido_fabricante)                   AS pedido          ,
					trim(tbl_faturamento.nota_fiscal)                         AS nota_fiscal     ,
					trim(tbl_faturamento.total_nota)                          AS total_nota      ,
					tbl_faturamento.nf_os
			FROM    tbl_faturamento
			JOIN    tbl_pedido ON tbl_pedido.pedido = tbl_faturamento.pedido
			WHERE   tbl_faturamento.posto   = $login_posto
			AND     tbl_faturamento.fabrica = $login_fabrica
			AND     tbl_faturamento.pedido NOTNULL ";
	
	if (strlen($numero_nf) > 0) {
		$numero_nf = str_replace (".","",$numero_nf);
		$numero_nf = str_replace ("-","",$numero_nf);
		$numero_nf = str_replace ("/","",$numero_nf);
		
		$sql .= "AND tbl_faturamento.nota_fiscal ILIKE '%$numero_nf' ";
	}
	if (strlen($numero_pedido) > 0) {
		$sql .= "AND tbl_faturamento.pedido_fabricante ILIKE '%$numero_pedido%' ";
	}

	$sql .= "GROUP BY   tbl_faturamento.emissao          ,
						tbl_faturamento.saida            ,
						tbl_faturamento.previsao_chegada ,
						tbl_faturamento.pedido_fabricante,
						tbl_faturamento.nota_fiscal      ,
						tbl_faturamento.total_nota       ,
						tbl_faturamento.nf_os
			ORDER BY    tbl_faturamento.emissao DESC     ,
						tbl_faturamento.nota_fiscal DESC";
	
	//if ($ip == "201.0.9.216") echo $sql;
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

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$emissao           = trim(pg_result($res,$i,emissao));
			$saida             = trim(pg_result($res,$i,saida));
			$previsao_chegada  = trim(pg_result($res,$i,previsao_chegada));
			$pedido            = trim(pg_result($res,$i,pedido));
			$nota_fiscal       = trim(pg_result($res,$i,nota_fiscal));
			$total_nota        = trim(pg_result($res,$i,total_nota));
			$pedido_fabricante = substr($pedido,4,strlen($pedido));
			
			$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					WHERE  tbl_pedido.pedido_blackedecker  = $pedido_fabricante::numeric
					AND    tbl_pedido.posto   = $login_posto
					AND    tbl_pedido.fabrica = $login_fabrica;";
			$resx = @pg_exec ($con,$sql);
			
			if (@pg_numrows($resx) > 0) {
				$xpedido = trim(pg_result($resx,0,pedido));
			}
			
			if ($i == 0) {
				echo "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
				echo "<tr class='menu_top'>";
				echo "<td>Nota Fiscal</td>";
				echo "<td>Emissão</td>";
				echo "<td>Saída</td>";
				echo "<td>Previsão Chegada</td>";
				echo "<td>Pedido</td>";
				echo "<td>Total Nota</td>";
				echo "</tr>";
			}
			
			echo "<tr class='table_line1' bgcolor='$cor'>";
			
			echo "<td align='center'>";
			if (strlen($xpedido) > 0) {
				echo "<a href='pedido_finalizado.php?pedido=$xpedido'>$nota_fiscal</a>";
			}else{
				echo "$nota_fiscal";
			}
			echo "</td>";
			echo "<td align='center'>$emissao</td>";
			echo "<td align='center'>$saida</td>";
			echo "<td align='center'>$previsao_chegada</td>";
			echo "<td align='center'>$pedido</td>";
			echo "<td align='right'>". number_format($total_nota,2,",",".") ."</td>";
			echo "</tr>";
		}
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
		echo "<table width='350' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
		echo "<tr>\n";
		echo "<td class='menu_top'>Não foi encontrado Notas Fiscais.</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}
}
?>

<br><br>

<? include "rodape.php"; ?>
