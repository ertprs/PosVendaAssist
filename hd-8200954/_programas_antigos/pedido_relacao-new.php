<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "funcoes.php";

if (strlen($_GET["excluir"]) > 0) $excluir = $_GET["excluir"];

if (strlen($excluir) > 0) {
	$sql =	"SELECT pedido
			FROM tbl_pedido
			WHERE tbl_pedido.fabrica = $login_fabrica
			AND   tbl_pedido.posto   = $login_posto
			AND   tbl_pedido.pedido  = $excluir
			AND   tbl_pedido.exportado IS NULL;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$sql =	"DELETE FROM tbl_pedido
				WHERE tbl_pedido.pedido  = $excluir
				AND   tbl_pedido.posto   = $login_posto
				AND   tbl_pedido.fabrica = $login_fabrica
				AND   tbl_pedido.exportado IS NULL;";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			header("Location: $PHP_SELF?listar=todas");
			exit;
		}
	}
}

$title = "Relação de Pedido de pecas";
$layout_menu = 'pedido';
include "cabecalho.php";
?>

<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->

<p>

<? if ($login_fabrica == 1) { ?>
<font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#FF0000"><b>PREZADO ASSISTENTE: Quando existir um pedido feito pelo pessoal da Black & Decker, 
irá aparecer na coluna Black o nome do usuário que o efetuou, 
caso contrário foi um pedido feito pela própria Assistência.</b></font>
<br><br><br>
<font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#FF0000"><b>Pedidos não finalizados devem ser cancelados ou finalizados para que sejam faturados.
Estes pedidos não devem ficar em aberto no sistema, para evitarmos transtornos futuros.
Caso queria finalizar o pedido ou excluir o mesmo, clique no número do mesmo e delete ou finalize.</b></font>
<br><br><br>
<? } ?>

<table width="600" border="0" cellpadding="2" cellspacing="0" align="center">
<form name='frm_pedido_consulta' action='<? echo $PHP_SELF; ?>' method='get'>
<input type='hidden' name='btn_acao_pesquisa' value=''>
<tr height="22" bgcolor="#bbbbbb">
	<td nowrap>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Número do Pedido para Consulta</b></font>
	</td>
	<td nowrap>
		<input type='text' name='pedido' value=''>
	</td>
	<td rowspan=2 align="center" valign='middle' nowrap><img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_pedido_consulta.btn_acao_pesquisa.value == '' ) { document.frm_pedido_consulta.btn_acao_pesquisa.value='continuar' ; document.frm_pedido_consulta.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar busca pelo Pedido" border='0' style='cursor: pointer'></td>
</tr>
<tr height="22" bgcolor="#bbbbbb">
	<td nowrap>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Consulta pelo código da peça</b></font>
	</td>
	<td nowrap>
		<input type='text' name='referencia' value=''>
	</td>
</tr>
<tr height="22" bgcolor="#bbbbbb">
	<td colspan=3 align="center" nowrap><a href='<? echo $PHP_SELF."?listar=todas"; ?>'>Listar todos os Pedidos</a></td>
</tr>
</form>
</table>

<?
$btn_acao_pesquisa = $_POST['btn_acao_pesquisa'];
if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];

$listar = $_POST['listar'];
if (strlen($_GET['listar']) > 0) $listar = $_GET['listar'];

$pedido = $_POST['pedido'];
if (strlen($_GET['pedido']) > 0) $pedido = $_GET['pedido'];

$referencia = $_POST['referencia'];
if (strlen($_GET['referencia']) > 0) $referencia = $_GET['referencia'];

if (( (strlen($pedido) > 0 OR strlen($referencia) > 0) AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0){
	$sql = "SELECT  tbl_pedido.pedido                                                  ,
					lpad(tbl_pedido.pedido_blackedecker,5,0)    AS pedido_blackedecker ,
					tbl_pedido.data                                                    ,
					TO_CHAR(tbl_pedido.finalizado,'DD/MM/YYYY') AS finalizado          ,
					tbl_pedido.exportado                                               ,
					tbl_pedido.total                                                   ,
					tbl_tipo_pedido.descricao AS tipo_pedido_descricao                 ,
					tbl_linha.nome			  AS linha_descricao                       ,
					(
						SELECT tbl_status.descricao AS status
						FROM   tbl_pedido_status
						JOIN   tbl_status USING (status)
						WHERE  tbl_pedido_status.pedido = tbl_pedido.pedido
						ORDER BY tbl_pedido_status.data DESC
						LIMIT 1
					) AS pedido_status                                            ,
					tbl_status_pedido.descricao AS xstatus_pedido                 , ";
if ($login_fabrica <> 1) $sql .= "to_char(SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco * ((tbl_peca.ipi / 100)+1)),999999990.99 )::float AS preco_ipi ";
else					 $sql .= "to_char(SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco),999999990.99 )::float  AS preco_ipi ";
	$sql .= "FROM    tbl_pedido
			JOIN    tbl_tipo_pedido     USING (tipo_pedido)
			JOIN    tbl_pedido_item     USING (pedido)
			JOIN    tbl_peca            USING (peca)
			LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			LEFT JOIN tbl_linha         USING (linha)
			WHERE   tbl_pedido.posto   = $login_posto
			AND     tbl_pedido.fabrica = $login_fabrica ";
	
	if ($login_fabrica == 1) $sql .= " AND tbl_pedido.pedido_acessorio IS FALSE ";
	
	if (strlen($pedido) > 0 AND $login_fabrica == 1) $sql .= "AND tbl_pedido.pedido_blackedecker = $pedido ";
	
	if (strlen($pedido) > 0 AND $login_fabrica <> 1) $sql .= "AND tbl_pedido.pedido = $pedido ";
	
	if (strlen($referencia) > 0) $sql .= "AND tbl_peca.referencia = '$referencia' ";
	
	$sql .= "GROUP BY tbl_pedido.pedido           ,
					tbl_pedido.pedido_blackedecker,
					tbl_pedido.data               ,
					tbl_pedido.finalizado         ,
					tbl_pedido.total              ,
					tbl_tipo_pedido.descricao     ,
					tbl_status_pedido.descricao   ,
					tbl_pedido.exportado          ,
					tbl_linha.nome
			ORDER BY tbl_pedido.data DESC";
	$res = pg_exec ($con,$sql);

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
		
		if (strlen($referencia) > 0){
			echo "<table width='600' border='0' cellspacing='0' cellpadding='0' align='center' bgcolor='#f1f1f1'>";
			echo "<tr height='25'>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Pedidos com a peça $referencia</b></font></td>";
			echo "</tr>";
			echo "</table>";
		}
		echo "<p>";
		
		echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
		echo "<tr height='20' bgcolor='#999999'>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Pedido</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Data</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Finalizado</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Status</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Tipo Pedido</b></font></td>";
		if ($login_fabrica <> 1){
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Linha</b></font></td>";
		}
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Valor Total</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Ação</b></font></td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$total                 = pg_result($res,$i,preco_ipi);
			$pedido                = trim(pg_result($res,$i,pedido));
			$pedido_blackedecker   = trim(pg_result($res,$i,pedido_blackedecker));
			$data                  = trim(pg_result($res,$i,data));
			$finalizado            = trim(pg_result($res,$i,finalizado));
			if ($login_fabrica == 2)
				$pedido_status     = "OK";
			else
				$pedido_status     = trim(pg_result($res,$i,pedido_status));
			$status_pedido         = trim(pg_result($res,$i,xstatus_pedido));
			$tipo_pedido_descricao = trim(pg_result($res,$i,tipo_pedido_descricao));
			$linha                 = trim(pg_result($res,$i,linha_descricao));
			$exportado             = trim(pg_result($res,$i,exportado));

			echo "<tr bgcolor='$cor'>";
			if ($login_fabrica <> 1) {
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='pedido_finalizado.php?pedido=$pedido'>$pedido</a></font></td>";
			}else{
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='pedido_finalizado.php?pedido=$pedido'>$pedido_blackedecker</a></font></td>";
			}
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>". mostra_data ($data) ."</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>". $finalizado ."</font></td>";
			
			if (strlen($pedido_status) > 0) {
				echo "<td nowrap><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$pedido_status</font></td>";
			}else{
				echo "<td nowrap><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$status_pedido</font></td>";
			}
			
			echo "<td><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$tipo_pedido_descricao</font></td>";
			if ($login_fabrica <> 1){
				echo "<td><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$linha</font></td>";
			}
			echo "<td align='right'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>". number_format($total,2,",",".") ."</font></td>";
			echo "<td align='center'>";
			if (strlen ($exportado) == 0) {
				echo "<a href='$PHP_SELF?excluir=$pedido'><img border='0' src='imagens/btn_excluir.gif'></a>";
			}
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
		
		echo "</td>";
		echo "<td><img height='1' width='16' src='imagens/spacer.gif'></td>";
		
		echo "</tr>";
		//echo "<tr>";
		
		//echo "<td height='27' valign='middle' align='center' colspan='3' bgcolor='#FFFFFF'>";
		//echo "<a href='pedido_cadastro.php'><img src='imagens/btn_lancarnovopedido.gif'></a>";
		//echo "</td>";
		
		//echo "</tr>";
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
		echo "<h4>Não foi(am) encontrado(s) pedido(s).</h4>";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
	}
}
?>

<p>

<? include "rodape.php"; ?>
