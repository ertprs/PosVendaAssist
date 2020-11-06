<?php
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

$msg_erro = "";

$leftJoinLinhas  = "";
$cond_linhas     = "";
$whereLinhas     = "";
$campo_linhas    = "";
if ($login_fabrica == 120) {
	if ($_REQUEST['btn_acao'] == "Pesquisar") {
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

if (filter_input(INPUT_POST,'btn_acao')) {
    $data_final    = filter_input(INPUT_POST,'data_final');
    $data_inicial  = filter_input(INPUT_POST,'data_inicial');
    $pedido        = filter_input(INPUT_POST,"pedido");

    $condTipo = "";
    if ($login_fabrica == 101){
        $tipo_pedido = filter_input(INPUT_POST,'tipo_pedido');
        if (!empty($tipo_pedido)) {
            $condTipo = " AND tbl_pedido.tipo_pedido = ".$tipo_pedido;
        }
    }
	$cond_data = "";

	if(strlen($pedido) > 0){
		$cond_data .= " AND tbl_pedido.pedido = {$pedido} ";
	}

	if(strlen($data_inicial) > 0 || strlen($data_final) > 0){

		list($di, $mi, $yi) = explode("/", $data_inicial_01);
		list($df, $mf, $yf) = explode("/", $data_final_01);

		if(strlen($msg_erro)==0){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi))
				$msg_erro = "Data Inicial Inválida";
		}

		if(strlen($msg_erro)==0){
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf))
				$msg_erro = "Data Final Inválida";
		}

		if(strlen($msg_erro)==0){
		    $aux_data_inicial = "$yi-$mi-$di";
		    $aux_data_final = "$yf-$mf-$df";
		}

		if(strlen($msg_erro)==0){
		    if(strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_final) > strtotime('today')){
				$msg_erro = "Data final não pode ser menor que data inicial ou maior que a data atual.";
		    }
		}

		if(strlen($msg_erro)==0){
			$aux_data_inicial = "$yi-$mi-$di 00:00:00";
			$aux_data_final = "$yf-$mf-$df 23:59:59";

			$cond_data .= " AND tbl_pedido.data BETWEEN '$aux_data_inicial' and '$aux_data_final' ";
		}

	}


} else if (filter_input(INPUT_GET,'btn_acao')) {
    $data_final    = filter_input(INPUT_GET,'data_final');
    $data_inicial  = filter_input(INPUT_GET,'data_inicial');
    $pedido        = filter_input(INPUT_GET,"pedido");

    if(strlen($pedido) > 0){
        $cond_data .= " AND tbl_pedido.pedido = {$pedido} ";
    }

    list($di, $mi, $yi) = explode("/", $data_inicial);
    list($df, $mf, $yf) = explode("/", $data_final);

    $aux_data_inicial = "$yi-$mi-$di 00:00:00";
    $aux_data_final = "$yf-$mf-$df 23:59:59";

    $cond_data .= " AND tbl_pedido.data BETWEEN '$aux_data_inicial' and '$aux_data_final' ";
} else {
	$cond_data = " AND tbl_pedido.data BETWEEN CURRENT_TIMESTAMP - interval '1 year' and CURRENT_TIMESTAMP ";
}

$layout_menu = "callcenter";
$title = "Relação de Pedidos Lançados";

include "cabecalho.php";
include '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.msg_erro{
	background-color:#FF0000;
	text-align:center;
}

</style>

<script type="text/javascript">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>
	<table width="700" border="0" cellspacing="5" cellpadding="0" class='texto_avulso' align='center'>
                <tr>
                    <td nowrap align='center'>
                    <b>Atenção:&nbsp;</b>Os pedidos listados automaticamente são referentes ao último ano.<br>Para ver pedidos de datas anteriores utilize o formulário de pesquisa abaixo.
                    </td>
                </tr>
        </table> <br /><br />

	<?php
		if(strlen($msg_erro) > 0){
	?>
			<table width="700" align="center" border="0">
				<tr class="msg_erro">
					<td align="center"><?=$msg_erro?></td>
				</tr>
			</table>
	<?php
		}
	?>
	<form method="post" action="">
		<table width="700" align="center" border="0" class="formulario">
			<caption class="titulo_tabela"> Parâmetros de Pesquisa</caption>
			<tr>
				<td width="200">&nbsp;</td>
				<td>
					Nº Pedido <br />
					<input type="text" name="pedido" value="<?=$pedido?>" size="10" class="frm" />
				</td>
				<td width="100">
<?php
        if ($login_fabrica == 120) {
?>
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
<?php
        }
        if ($login_fabrica == 101){
            $sql = "SELECT  tbl_tipo_pedido.tipo_pedido,
                            tbl_tipo_pedido.descricao
                    FROM    tbl_tipo_pedido
                    WHERE   tbl_tipo_pedido.fabrica = $login_fabrica
              ORDER BY      tbl_tipo_pedido.descricao;
            ";
            $res = pg_query($con,$sql);
?>
                    <label for='tipo_pedido'>&nbsp;Tipo Pedido</label> <br />
                    <select class="frm input-tipo" name="tipo_pedido" id="tipo_pedido">
                        <option value=''>Todos os Pedidos</option>
<?php
            while ($tipo_pedidos = pg_fetch_object($res)) {
?>
                        <option value="<?=$tipo_pedidos->tipo_pedido?>" <?=($tipo_pedido == $tipo_pedidos->tipo_pedido ? "selected" : "")?>><?=$tipo_pedidos->descricao?></option>
<?php
            }
?>
                    </select>
<?php
        }
?>
				</td>
			<tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
					<td width="200">&nbsp;</td>
				<td>
					Data Inicial <br />
					<input type="text" name="data_inicial" id="data_inicial" size="10" value="<?=$data_inicial?>" class="frm" />
				</td>
				<td>
					Data Final <br />
					<input type="text" name="data_final" id="data_final" size="10" value="<?=$data_final?>" class="frm" />
				</td>
				<td width="100">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="4" align="center"> <br /> <input type="submit" name="btn_acao" value="Pesquisar" /> </td>
			</tr>
		</table> <br />
	</form>
<?
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

	$sql = "SELECT  tbl_pedido.pedido                                  ,
					tbl_pedido.seu_pedido                                  ,
					tbl_pedido.pedido_cliente                          ,
					tbl_posto.nome                    AS posto_nome    ,
					tbl_posto_fabrica.codigo_posto                     ,
					tbl_pedido.fabrica                                 ,
					tbl_pedido.pedido_cliente                          ,
					to_char(tbl_pedido.data,'DD/MM/YYYY') AS data      ,
					tbl_tipo_pedido.descricao AS descricao_tipo_pedido ,
					$campo_linhas
					tbl_status_pedido.descricao AS descricao_status_pedido
			FROM tbl_posto
			JOIN tbl_pedido USING (posto)
			JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica
			JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			$leftJoinLinhas
			";
			/*if($login_fabrica == 81 OR $login_fabrica == 114) $sql .= "
			JOIN  tbl_os_troca ON tbl_pedido.pedido = tbl_os_troca.pedido AND tbl_os_troca.obs_causa = 'troca_lote' ";*/
			if($login_fabrica == 43) $sql .= " JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido-item.pedido
			JOIN tbl_peca            ON tbl_pedido_item.peca        = tbl_peca.peca ";
			$sql .= " JOIN tbl_status_pedido  ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
			WHERE tbl_pedido.fabrica = $login_fabrica
			AND     tbl_pedido.exportado IS NOT NULL
			$cond_data
			$cond_tipo_garantia
			$cond_linhas
			$whereLinhas
			$condTipo
			";
	if($login_fabrica == 43) $sql .= " AND     tbl_peca.faturada_manualmente IS NOT TRUE ";

		$sql .= " AND tbl_pedido.status_pedido NOT IN(4,13,14)  ORDER BY tbl_pedido.data DESC";

// echo nl2br($sql);

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//echo "<br>".$sql."<br>".$sqlCount."<br>";
if(strlen($msg_erro) == 0){
	// ##### PAGINACAO ##### //
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 10;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
}
// ##### PAGINACAO ##### //

if (@pg_numrows($res) == 0) {
	echo "<center><h2>Não existem pedidos com estes parâmetros</h2></center>";
}else{

	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<TR class='menu_top'>\n";
	echo "	<TD>PEDIDO</TD>\n";
	echo "	<TD> ";
	echo ($login_fabrica == 95) ? "SÉRIE" : "PEDIDO CLIENTE" ;
	echo "</TD>\n";
	if ($login_fabrica == 120) {
	echo "	<TD >LINHA</TD>\n";
	}
	echo "	<TD>TIPO</TD>\n";
	echo "	<TD>STATUS</TD>\n";
	echo "	<TD>DATA</TD>\n";
	echo "	<TD>POSTO</TD>\n";
	echo "	<TD>AÇÃO</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$pedido             = trim(pg_result ($res,$i,pedido));
		$seu_pedido         = trim(pg_result ($res,$i,seu_pedido));
		$pedido_cliente     = trim(pg_result ($res,$i,pedido_cliente));
		$tipo               = trim(pg_result ($res,$i,descricao_tipo_pedido));
		$status             = trim(pg_result ($res,$i,descricao_status_pedido));
		$data               = trim(pg_result ($res,$i,data));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		if ($login_fabrica == 120) {
			$nome_linha     = trim(pg_result ($res,$i,nome_linha));
		}

		if($login_fabrica == 40){

			$sql_qtde_pecas = "SELECT SUM(qtde) AS total
								FROM tbl_os_item
								WHERE
									pedido_item IN (SELECT pedido_item FROM tbl_pedido_item WHERE pedido = {$pedido})
									AND fabrica_i = {$login_fabrica}";
			$res_qtde_pecas = pg_query($con, $sql_qtde_pecas);
			$total_pecas = (strlen(pg_fetch_result($res_qtde_pecas, 0, "total")) > 0) ? pg_fetch_result($res_qtde_pecas, 0, "total") : 0;

			$sql_qtde_cancelada = "SELECT SUM(qtde) AS total
									FROM tbl_pedido_cancelado
									WHERE
										pedido_item IN (SELECT pedido_item FROM tbl_pedido_item WHERE pedido = {$pedido})
										AND fabrica = {$login_fabrica}";
			$res_qtde_cancelada = pg_query($con, $sql_qtde_cancelada);
			$total_canceladas = (strlen(pg_fetch_result($res_qtde_cancelada, 0, "total")) > 0) ? pg_fetch_result($res_qtde_cancelada, 0, "total") : 0;

			$sql_qtde_faturada = "SELECT SUM(qtde) AS total
									FROM tbl_faturamento_item
									WHERE
										pedido_item IN (SELECT pedido_item FROM tbl_pedido_item WHERE pedido = {$pedido})
										AND pedido = {$pedido}";
			$res_qtde_faturada = pg_query($con, $sql_qtde_faturada);
			$total_faturadas = (strlen(pg_fetch_result($res_qtde_faturada, 0, "total")) > 0) ? pg_fetch_result($res_qtde_faturada, 0, "total") : 0;

			$total_canceladas_faturadas = $total_faturadas + $total_canceladas;

			if($total_canceladas_faturadas == $total_pecas){
				continue;
			}

		}

		$cor = ($i % 2) ?"#F7F5F0":'#F1F4FA';
		$btn = ($i % 2) ?'amarelo':'azul';

		if($login_fabrica == 95) {
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

		$pedido_aux = ($login_fabrica == 88 AND (!empty($seu_pedido))) ? $seu_pedido : $pedido;

		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		echo "	<TD style='padding-left:5px'>$pedido_aux</TD>\n";
		echo "	<TD style='padding-left:5px'>";
		echo ($login_fabrica  == 95) ? $serie : $pedido_cliente;
		echo "</TD>\n";
		if ($login_fabrica == 120) {
		echo "	<TD style='padding-left:5px'>$nome_linha</TD>\n";
		}
		echo "	<TD style='padding-left:5px'>$tipo</TD>\n";
		echo "	<TD style='padding-left:5px' nowrap>$status</TD>\n";
		echo "	<TD align='center'>$data</TD>\n";
		echo "	<TD nowrap >$codigo_posto - <ACRONYM TITLE=\"$posto_nome\">".substr($posto_nome,0,14)."</ACRONYM></TD>\n";
		echo "<TD nowrap width='85'><a href='pedido_nao_faturado_cadastro.php?pedido=$pedido'><img src='imagens/btn_faturar_".$btn.".gif'></a></TD>\n";
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

if(strlen($msg_erro) == 0){
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
}
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

?>

<p>

<? include "rodape.php"; ?>
