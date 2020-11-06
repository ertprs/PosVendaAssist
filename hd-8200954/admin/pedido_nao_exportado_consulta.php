<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

// somente Ibratele

$sql = "SELECT COUNT(1) as total FROM tbl_fabrica WHERE fabrica = $login_fabrica AND fatura_manualmente IS TRUE";
$res = pg_query($con,$sql);
if(pg_numrows($res) > 0){
	$total = pg_result($res,0,0);
	if($total == 0){
		header("Location: pedido_parametros.php");
		exit;
	}
}

$fabricas_exportam_excel = array(80,95,108,111);

$btn_acao = trim (strtolower ($_POST['btn_acao']));
$leftJoinLinhas  = "";
$cond_linhas     = "";
$whereLinhas     = "";
$campo_linhas    = "";
if ($login_fabrica == 120) {
	if ($_POST['frm_submit'] == 1) {
		$linha           = $_POST['linha_produto'];
		$campo_linhas    = " tbl_linha.nome AS nome_linha,";
		$cond_linhas     = " AND tbl_pedido.linha={$linha}";
		$leftJoinLinhas  = " LEFT JOIN  tbl_linha ON tbl_pedido.linha=tbl_linha.linha AND tbl_linha.fabrica={$login_fabrica}";
	} else {
		$campo_linhas    = " tbl_linha.nome AS nome_linha,";
		$leftJoinLinhas  = " LEFT JOIN  tbl_linha ON tbl_pedido.linha=tbl_linha.linha AND tbl_linha.fabrica={$login_fabrica}";
		$whereLinhas  = " AND tbl_pedido.linha <> 706";
	}
}
$msg_erro = "";

$layout_menu = "callcenter";
$title = "Relação de Pedidos Lançados e não exportados";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.input-linha{
	width: 100%;
}
</style>
<?php if ($login_fabrica == 120) {?>
<form name="frm_pesquisa" method="post" action="">
	<input type="hidden" name="frm_submit" id="frm_submit" value="1">
	<table width="700" class="formulario" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr>
			<td colspan="4" class="titulo_tabela">Parâmetros de Pesquisa</td>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr>
			<td width="20%">&nbsp;</td>
			<td colspan="2" align="left">
				<label for='linha_produto'>&nbsp;Linha</label> <br />
				<select class="frm input-linha" name="linha_produto" id="linha_produto">
					<option value="" >Escolha uma Linha ...</option>
					<?php
						$sqlLinha = "SELECT linha, nome
								  FROM tbl_linha
								 WHERE fabrica = $login_fabrica
								   AND ativo";
						$resLinha = pg_query($con, $sqlLinha);

						foreach (pg_fetch_all($resLinha) as $key) {
							$selectedLinha = ($linha == $key['linha']) ? "selected='selected'" : '' ;
							if ($key['linha'] <> 706) { 
					?>
					<option value="<?php echo $key['linha']?>" <?php echo $selectedLinha;?>>
						<?php echo $key['nome'];?>
					</option>
					<?php }}?>
				</select>
			</td>
			<td width="20%">&nbsp;</td>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr>
			<td width="20%">&nbsp;</td>
			<td colspan="2" align="center">
				<input type="submit" value="Pesquisar">
			</td>
			<td width="20%">&nbsp;</td>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
	</table>
</form><br />
<?php }?>


<?php

	$dt = 0;


	// BTN_NOVA BUSCA
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='pedido_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

	if($login_fabrica == 40){
		$cond_tipo_garantia  = " AND tbl_pedido.tipo_pedido = (SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = {$login_fabrica} AND descricao = 'Garantia' AND codigo = 'GAR') ";
	}

	$sql = "SELECT  distinct
					tbl_pedido.pedido                                  ,
					tbl_pedido.seu_pedido                              ,
					tbl_pedido.pedido_cliente                          ,
					tbl_posto.nome                    AS posto_nome    ,
					tbl_posto_fabrica.codigo_posto                     ,
					tbl_pedido.fabrica                                 ,
					tbl_pedido.pedido_cliente                          ,
					to_char(tbl_pedido.data,'DD/MM/YYYY') AS data      ,
					to_char(tbl_pedido.data_aprovacao,'DD/MM/YYYY') AS data_aprovacao,
					tbl_tipo_pedido.descricao AS descricao_tipo_pedido ,
					$campo_linhas
					tbl_status_pedido.descricao AS descricao_status_pedido
			FROM tbl_posto
			JOIN tbl_pedido USING (posto)
			JOIN tbl_tipo_pedido USING (tipo_pedido)
			JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
			JOIN tbl_pedido_item USING (pedido)";
			if($login_fabrica == 43) $sql .= "
			JOIN tbl_peca ON tbl_pedido_item.peca        = tbl_peca.peca ";
			if($login_fabrica == 81 OR $login_fabrica == 114) $sql .= "
			JOIN tbl_os_troca ON tbl_pedido.pedido = tbl_os_troca.pedido AND tbl_os_troca.obs_causa = 'troca_lote' ";
			$sql .= "LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_pedido.produto
			LEFT JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
			$leftJoinLinhas
			WHERE tbl_pedido.fabrica = $login_fabrica
			/*AND tbl_pedido.posto <> 6359*/
			AND (tbl_pedido.status_pedido <> 14 OR tbl_pedido.status_pedido IS NULL)
			AND     tbl_pedido.exportado IS NULL 
			$cond_tipo_garantia 
			$cond_linhas
			$whereLinhas
			";

	if($login_fabrica == 43) $sql .= " AND tbl_peca.faturada_manualmente IS NOT TRUE ";

	if($login_fabrica == 88){
		$sql .= " AND exportado_manual IS NOT TRUE ";
	}

	/* Apenas quando pedido não é via DISTRIB */
	if($login_fabrica == 51 or $telecontrol_distrib)	$sql .= " AND     tbl_pedido.pedido_via_distribuidor IS NOT TRUE ";
	$sql .= " ORDER BY tbl_pedido.pedido DESC ";

	// echo nl2br($sql);

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

#echo "<br>".  nl2br($sql)."<br>";

// ##### PAGINACAO ##### //
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 10;				// máximo de links à serem exibidos
$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

// ##### PAGINACAO ##### //

echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD colspan='5'>PEDIDOS NÃO EXPORTADOS</TD>\n";
echo "</TR>\n";
echo "</table>\n";

if (@pg_numrows($res) == 0) {

	echo "<center><h2>Não existem pedidos com estes parâmetros</h2></center>";

}else{

	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	echo "<TR class='menu_top'>\n";
	echo "	<TD >PEDIDO</TD>\n";
	echo "	<TD> ";
	echo ($login_fabrica == 95 or $login_fabrica == 108 or $login_fabrica == 111) ? "SÉRIE" : "PEDIDO CLIENTE" ;
	echo "	</td>";
	if ($login_fabrica == 120) {
	echo "	<TD >LINHA</TD>\n";
	}
	echo "	<TD >TIPO</TD>\n";
	echo "	<TD >STATUS</TD>\n";
	echo "	<TD >DATA</TD>\n";
	echo "	<TD>POSTO</TD>\n";
	if($login_fabrica == 43){
		echo "	<TD>DATA APROVAÇÃO</TD>\n";
	}
	echo "	<TD>AÇÃO</TD>\n";
	if(in_array($login_fabrica,$fabricas_exportam_excel)){
		echo "	<TD>EXPORTAÇÃO</TD>\n";
	}

	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$pedido             = trim(pg_result ($res,$i,pedido));
		$seu_pedido         = trim(pg_result ($res,$i,seu_pedido));
		$pedido_cliente     = trim(pg_result ($res,$i,pedido_cliente));
		$tipo               = trim(pg_result ($res,$i,descricao_tipo_pedido));
		$status             = trim(pg_result ($res,$i,descricao_status_pedido));
		$data               = trim(pg_result ($res,$i,data));
		$data_aprovacao     = trim(pg_result ($res,$i,data_aprovacao));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		
		if ($login_fabrica == 120) {
			$nome_linha     = trim(pg_result ($res,$i,nome_linha));
		}

		$cor = ($i % 2) ?"#F7F5F0":'#F1F4FA';
		$btn = ($i % 2) ?'amarelo':'azul';

		if($login_fabrica == 95 or $login_fabrica == 108 or $login_fabrica == 111) {
			$sqls = " SELECT
						DISTINCT tbl_os.serie
					FROM tbl_os
					JOIN tbl_os_produto ON tbl_os.os=tbl_os_produto.os
					JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
					JOIN tbl_pedido_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item
					WHERE
					tbl_pedido_item.pedido=$pedido";
			$ress = pg_query($con,$sqls);
			if(pg_num_rows($ress) > 0){
				$serie = pg_fetch_result($ress,0,'serie');
			}
		}

		if($login_fabrica == 88){
			list($di, $mi, $yi) = explode("/", $data);
			if(!checkdate($mi,$di,$yi));
			$aux_data = "$yi-$mi-$di";
		}

		$pedido_aux = ($login_fabrica == 88 AND (!empty($seu_pedido))) ? $seu_pedido : $pedido;

		echo "<TR class='table_line' style='background-color: $cor;'>\n";

		echo "	<TD style='padding-left:5px'><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>$pedido_aux</a></TD>\n";

		echo "	<TD style='padding-left:5px'>";
		echo ($login_fabrica  == 95 or $login_fabrica == 108 or $login_fabrica == 111) ? $serie : $pedido_cliente;
		echo "</TD>\n";
		if ($login_fabrica == 120) {
		echo "	<TD style='padding-left:5px'>$nome_linha</TD>\n";
		}
		echo "	<TD style='padding-left:5px'>$tipo</TD>\n";
		echo "	<TD style='padding-left:5px'>$status</TD>\n";
		echo "	<TD align='center'>$data</TD>\n";
		echo "	<TD nowrap >$codigo_posto - <ACRONYM TITLE=\"$posto_nome\">".substr($posto_nome,0,14)."</ACRONYM></TD>\n";

		if($login_fabrica == 43){
			echo "	<TD align='center'>&nbsp; $data_aprovacao</TD>\n";
		}
		if($login_fabrica == 43 and strlen($data_aprovacao) > 0){
			echo "	<TD nowrap width='85'>&nbsp;</TD>\n";
		}else if($login_fabrica == 88){
			echo "	<TD nowrap width='85' align='center'><a href='pedido_nao_exportado.php?pedido=$pedido' target='_blank'>Liberar</a></TD>\n";
		}else{
			echo "	<TD nowrap width='85'><a href='pedido_nao_exportado.php?pedido=$pedido'><img src='imagens/btn_exportar_".$btn.".gif'></a></TD>\n";
			if(in_array($login_fabrica,$fabricas_exportam_excel)){
				echo "
					<TD nowrap width='85' align='center'>
						<a href='exporta_pedido_excel.php?pedido=$pedido' target='_blank'>
							<img src='imagens/excell.gif'>
						</a>
					</TD>
						\n";
			}
		}

		echo "</TR>\n";

	}
}

echo "</TABLE>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='pedido_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";


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

if ($login_fabrica == 59 and pg_numrows($res)>0){ ?>
	<br><br>
	<a href='/assist/admin/relatorio_pecas_nao_exportado.php'>CLIQUE AQUI PARA RELATÓRIO DE PEÇAS - PEDIDOS CONSOLIDADOS</a>
	<br><br>
<?}?>

<p>

<? include "rodape.php"; ?>
