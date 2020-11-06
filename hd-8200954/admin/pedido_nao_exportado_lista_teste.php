<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

// somente Ibratele
if(($login_fabrica <> 8) AND ($login_fabrica <> 15) AND ($login_fabrica <> 51) AND ($login_fabrica <> 59) AND ($login_fabrica <> 65) AND ($login_fabrica <> 43)){
	header("Location: pedido_parametros.php");
	exit;
}

$btn_acao = trim (strtolower ($_POST['btn_acao']));

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

</style>

<?
echo $login_fabrica;
	$dt = 0;
	
	// BTN_NOVA BUSCA
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='pedido_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

	$sql = "SELECT  distinct
					tbl_pedido.pedido                                  ,
					tbl_pedido.pedido_cliente                          ,
					tbl_posto.nome                    AS posto_nome    ,
					tbl_posto_fabrica.codigo_posto                     ,
					tbl_pedido.fabrica                                 ,
					tbl_pedido.pedido_cliente                          ,
					to_char(tbl_pedido.data,'DD/MM/YYYY') AS data      ,
					to_char(tbl_pedido.data_aprovacao,'DD/MM/YYYY') AS data_aprovacao,
					tbl_tipo_pedido.descricao AS descricao_tipo_pedido ,
					tbl_status_pedido.descricao AS descricao_status_pedido
			FROM	tbl_posto
			JOIN	tbl_pedido USING (posto)
			JOIN	tbl_tipo_pedido USING (tipo_pedido)
			JOIN	tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
			JOIN	tbl_pedido_item USING (pedido) 
			LEFT JOIN tbl_produto		 ON tbl_produto.produto             = tbl_pedido.produto
			LEFT JOIN tbl_status_pedido  ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
			WHERE	tbl_pedido.fabrica = $login_fabrica 
			AND     tbl_pedido.exportado IS NULL ";
	/* Apenas quando pedido não é via DISTRIB */
	if($login_fabrica == 51)	$sql .= " AND     tbl_pedido.pedido_via_distribuidor IS NOT TRUE ";
	$sql .= " ORDER BY tbl_pedido.pedido DESC ";

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//echo "<br>".$sql."<br>";

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
	echo "	<TD >PEDIDO CLIENTE</TD>\n";
	echo "	<TD >TIPO</TD>\n";
	echo "	<TD >STATUS</TD>\n";
	echo "	<TD >DATA</TD>\n";
	echo "	<TD>POSTO</TD>\n";
	if($login_fabrica == 43){
		echo "	<TD>DATA APROVAÇÃO</TD>\n";	
	}
	echo "	<TD>AÇÃO</TD>\n";
	
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$pedido             = trim(pg_result ($res,$i,pedido));
		$pedido_cliente     = trim(pg_result ($res,$i,pedido_cliente));
		$tipo               = trim(pg_result ($res,$i,descricao_tipo_pedido));
		$status             = trim(pg_result ($res,$i,descricao_status_pedido));
		$data               = trim(pg_result ($res,$i,data));
		$data_aprovacao     = trim(pg_result ($res,$i,data_aprovacao));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));

		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}

	echo "<TR class='table_line' style='background-color: $cor;'>\n";
	
	if($login_fabrica == 43){
		echo "	<TD style='padding-left:5px'><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'><font color='#000000'>$pedido</font></a></TD>\n";
	}else{
		echo "	<TD style='padding-left:5px'>$pedido</TD>\n";
	}

	echo "	<TD style='padding-left:5px'>$pedido_cliente</TD>\n";
	echo "	<TD style='padding-left:5px'>$tipo</TD>\n";
	echo "	<TD style='padding-left:5px'>$status</TD>\n";
	echo "	<TD align='center'>$data</TD>\n";
	echo "	<TD nowrap >$codigo_posto - <ACRONYM TITLE=\"$posto_nome\">".substr($posto_nome,0,14)."</ACRONYM></TD>\n";

	if($login_fabrica == 43){
		echo "	<TD align='center'>&nbsp; $data_aprovacao</TD>\n";
	}
	if($login_fabrica == 43 and strlen($data_aprovacao) > 0){
		echo "	<TD nowrap width='85'>&nbsp;</TD>\n";
	}else{
		echo "	<TD nowrap width='85'><a href='pedido_nao_exportado.php?pedido=$pedido' target='_blank'><img src='imagens/btn_exportar_".$btn.".gif'></a></TD>\n";
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

?>

<p>

<? include "rodape.php"; ?>
