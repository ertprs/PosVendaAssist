<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$layout_menu = "callcenter";
$title = "Relação de Atendimentos Pendentes";

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
$sql = "SELECT  tbl_callcenter.callcenter                                        ,
					to_char (tbl_callcenter.data,'DD/MM/YYYY') AS data               ,
					tbl_produto.referencia                                           ,
					tbl_produto.descricao                                            ,
					tbl_callcenter.serie                                             ,
					tbl_cliente.nome                           AS consumidor_nome    ,
					tbl_callcenter.sua_os                                            ,
					tbl_posto.nome                             AS posto_nome         ,
					tbl_providencia.enderecada                                       ,
					tbl_providencia.ja_retirou_produto                               ,
					to_char (tbl_providencia.realizar_em,'DD/MM/YYYY') AS realizar_em
		FROM        tbl_callcenter
		LEFT JOIN   tbl_produto          USING (produto)
		LEFT JOIN   tbl_posto            USING (posto)
		LEFT JOIN   tbl_cliente          ON tbl_callcenter.cliente     = tbl_cliente.cliente
		LEFT JOIN   tbl_posto_fabrica    ON tbl_posto.posto            = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica  = $login_fabrica
		LEFT JOIN tbl_cidade             ON tbl_cidade.cidade          = tbl_cliente.cidade
		LEFT JOIN tbl_providencia        ON tbl_providencia.callcenter = tbl_callcenter.callcenter
		WHERE     tbl_callcenter.fabrica = $login_fabrica
		AND       (tbl_providencia.solucionado IS FALSE OR tbl_providencia.callcenter IS NULL)
		ORDER BY tbl_callcenter.data ASC";

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//echo "<br>".nl2br($sql)."<br><br>".nl2br($sqlCount)."<br>";

// ##### PAGINACAO ##### //
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 11;				// máximo de links à serem exibidos
$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

// ##### PAGINACAO ##### //

if (@pg_numrows($res) == 0) {
	echo "<TABLE width='700' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
}else{
	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	echo "<TR class='menu_top'>\n";
	echo "<TD colspan=8>$msg</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD>CHAMADO</TD>\n";
	echo "<TD>DATA</TD>\n";
	echo "<TD>PRODUTO</TD>\n";
	echo "<TD>CLIENTE</TD>\n";
	echo "<TD>OS</TD>\n";
	echo "<TD>PENDÊNCIA PARA</TD>\n";
	echo "<TD>REALIZAR EM</TD>\n";
	echo "<TD>&nbsp;</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$callcenter         = trim(pg_result ($res,$i,callcenter));
		$data               = trim(pg_result ($res,$i,data));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$enderecada         = trim(pg_result ($res,$i,enderecada));
		$realizar_em        = trim(pg_result ($res,$i,realizar_em));
		$ja_retirou_produto = trim(pg_result ($res,$i,ja_retirou_produto));

		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}

		if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

		$callcenter_X = $callcenter;
		if ($ja_retirou_produto == 't') $callcenter_X .= "-Ret";

		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		echo "<TD align=center nowrap><a href='callcenter_press.php?callcenter=$callcenter_X ' target='_blank'>$callcenter_X</a></TD>\n";
		echo "<TD align=center nowrap>$data</TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">".substr($produto_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$posto_nome\">$sua_os</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$enderecada\">".substr($enderecada,0,17)."</ACRONYM></TD>\n";
		echo "<TD align=center nowrap>$realizar_em</TD>\n";
		echo "<TD width=85>";
		if ($solucionado <> 't') {
			echo "<a href='callcenter_cadastro_3.php?callcenter=$callcenter' target='_blank'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a>";
		}
		echo "</TD>";
		echo "</TR>\n";
	}
	echo "</TABLE>\n";
}

echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<TR class='table_line'>";
echo "<TD align='center' background='#D9E2EF'>";
echo "<a href='callcenter_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
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

echo "<br>";

include "rodape.php"; 

?>