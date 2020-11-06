<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = strtolower($_POST["btn_acao"]);

if (strlen($_POST["linha"]) > 0) $linha = $_POST["linha"];

if($_POST['chk_opt1']) $chk1 = $_POST['chk_opt1'];
if($_POST['chk_opt2']) $chk2 = $_POST['chk_opt2'];
if($_POST['chk_opt3']) $chk3 = $_POST['chk_opt3'];
if($_POST['chk_opt4']) $chk4 = $_POST['chk_opt4'];

if($_GET['chk_opt1'])  $chk1 = $_GET['chk_opt1'];
if($_GET['chk_opt2'])  $chk2 = $_GET['chk_opt2'];
if($_GET['chk_opt3'])  $chk3 = $_GET['chk_opt3'];
if($_GET['chk_opt4'])  $chk4 = $_GET['chk_opt4'];

if($_POST["data_inicial_01"])		$data_inicial_01    = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_01      = trim($_POST["data_final_01"]);
if($_POST["produto_referencia"])	$produto_referencia = trim($_POST["produto_referencia"]);
if($_POST["produto_nome"])			$produto_nome       = trim($_POST["produto_nome"]);
if($_POST["linha"])					$linha              = trim($_POST["linha"]);
if($_POST["tipo"])					$tipo               = trim($_POST["tipo"]);

if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01      = trim($_GET["data_final_01"]);
if($_GET["produto_referencia"])		$produto_referencia = trim($_GET["produto_referencia"]);
if($_GET["produto_nome"])			$produto_nome       = trim($_GET["produto_nome"]);
if($_GET["linha"])					$linha              = trim($_GET["linha"]);
if($_GET["tipo"])					$tipo               = trim($_GET["tipo"]);

$title = "Comunicados $login_fabrica_nome";
$layout_menu = "tecnica";

include 'cabecalho.php';
include "javascript_pesquisas.php";

?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

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

.tipo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	background-color: #D9E2EF
}

.descricao {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	background-color: #FFFFFF
}

.mensagem {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #FFFFFF
}

.txt10Normal {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<br>

<!-- MONTA ÁREA PARA EXPOSICAO DE COMUNICADO SELECIONADO -->
<?
if (strlen($comunicado) > 0) {
	$sql = "SELECT  tbl_comunicado.comunicado                        ,
					tbl_produto.referencia AS prod_referencia        ,
					tbl_produto.descricao  AS prod_descricao         ,
					tbl_comunicado.descricao                         ,
					tbl_comunicado.mensagem                          ,
					tbl_comunicado.tipo                              ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
			FROM    tbl_comunicado
			LEFT JOIN tbl_produto USING (produto)
			LEFT JOIN tbl_linha   on tbl_linha.linha = tbl_produto.linha
			WHERE   tbl_comunicado.fabrica    = $login_fabrica
			AND     tbl_comunicado.comunicado = $comunicado";
//echo $sql;
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) == 0) {
		$msg_erro = "Comunicado inexistente";
	}else{
		$comunicado           = trim(pg_result($res,0,comunicado));
		$referencia           = trim(pg_result($res,0,prod_referencia));
		$descricao            = trim(pg_result($res,0,prod_descricao));
		$comunicado_descricao = trim(pg_result($res,0,descricao));
		$comunicado_tipo      = trim(pg_result($res,0,tipo));
		$comunicado_mensagem  = trim(pg_result($res,0,mensagem));
		$comunicado_data      = trim(pg_result($res,0,data));
		
		$gif = "comunicados/$comunicado.gif";
		$jpg = "comunicados/$comunicado.jpg";
		$pdf = "comunicados/$comunicado.pdf";
		$doc = "comunicados/$comunicado.doc";
		$rtf = "comunicados/$comunicado.rtf";
		$xls = "comunicados/$comunicado.xls";

	}
}

if ((strlen($comunicado) > 0) && (pg_numrows($res) > 0)) {

	echo "<table class='table' width='400'>";
	echo "<tr>";
	echo "	<td align='left'><img src='imagens/cab_comunicado.gif'></td>";
	echo "</tr>";
	echo "<tr>";
	echo	"<td align='center' class='tipo'><b>$comunicado_tipo</b>&nbsp;&nbsp;-&nbsp;&nbsp;$comunicado_data</td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center' class='descricao'><b>$descricao</b></td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center' class='mensagem'>".nl2br($comunicado_mensagem)."</td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center'>&nbsp;</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='left' >";
	if (file_exists($gif) == true) {
		echo "	<img src='comunicados/$comunicado.gif'>";
	}

	if (file_exists($jpg) == true) {
		echo "<img src='comunicados/$comunicado.jpg'>";
	}

	if (file_exists($doc) == true) {
		echo "Para visualizar o arquivo, <a href='comunicados/$comunicado.doc' target='_blank'>clique aqui</a>.";
	}

	if (file_exists($rtf) == true) {
		echo "Para visualizar o arquivo, <a href='comunicados/$comunicado.rtf' target='_blank'>clique aqui</a>.";
	}

	if (file_exists($xls) == true) {
		echo "Para visualizar o arquivo, <a href='comunicados/$comunicado.xls' target='_blank'>clique aqui</a>.";
	}

	if (file_exists($pdf) == true) {
		echo "<div class='txt10Normal'><font color='#A02828'>Se você não possui o Acrobat Reader&reg;</font> , <a href='http://www.adobe.com/products/acrobat/readstep2.html'>instale agora</a>.</div>";
		echo "<br>";
		echo "Para visualizar o arquivo, <a href='comunicados/$comunicado.pdf' target='_blank'>clique aqui</a>.";
	}
	echo "	</td>";
	echo "</tr>";
	echo "</table>";

	echo "<br><br>";

	echo "<hr>";
}
?>

<!-- ------------------- Todos comunicados de um tipo -------------- -->

<?
$tipo       = $_GET ['tipo'];
$comunicado = $_GET ['comunicado'];

if (strlen ($comunicado) > 0) {
	$sql = "SELECT tipo FROM tbl_comunicado WHERE comunicado = $comunicado";
	$res = pg_exec ($con,$sql);
	$tipo = pg_result ($res,0,0);
}

if (strlen ($tipo) > 0) {
	$tipo = urldecode ($tipo);

	$sql = "SELECT	tbl_comunicado.comunicado, 
					tbl_comunicado.descricao, 
					tbl_produto.referencia, 
					tbl_produto.descricao AS descricao_produto, 
					to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data 
			FROM	tbl_comunicado 
			LEFT JOIN tbl_produto USING (produto) 
			LEFT JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha 
			WHERE	tbl_comunicado.fabrica = $login_fabrica 
			AND		tbl_comunicado.tipo = '$tipo' 
			ORDER BY tbl_produto.descricao" ;
	$res = pg_exec ($con,$sql);

	echo "<table width='400' align='center' border='0'>";
	echo "<tr bgcolor='#0099ff'>";
	echo "<td align='center' colspan='3'><font color='#ffffff' size='+1'><b>$tipo</b></font></td>";
	echo "</tr>";

	echo "<tr bgcolor='#0099ff'>";
	echo "<td align='center'><font color='#ffffff'><b>Produto</b></font></td>";
	echo "<td align='center'><font color='#ffffff'><b>Descrição</b></font></td>";
	echo "<td align='center'><font color='#ffffff'><b>Data</b></font></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = "#ffffff";
		if ($i % 2 == 0) $cor = '#eeeeff';

		echo "<tr bgcolor='$cor'>";

		echo "<td nowrap>";
		echo "<a href='$PHP_SELF?comunicado=" . urlencode (pg_result ($res,$i,comunicado)) . "'>";
		echo "<font size='-1'>";
		echo pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao_produto) ; 
		echo "</font>";
		echo "</a>";
		echo "</td>";

		echo "<td nowrap>";
		echo "<a href='$PHP_SELF?comunicado=" . urlencode (pg_result ($res,$i,comunicado)) . "'>";
		echo "<font size='-1'>";
		echo pg_result ($res,$i,descricao);
		echo "</font>";
		echo "</a>";
		echo "</td>";

		echo "<td align='right'>";
		echo "<font size='-1'>";
		echo pg_result ($res,$i,data);
		echo "</font>";
		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";

	echo "<hr>";
}
?>

<!-- ------------------- Tipos de Comunicados Disponíveis -------------- -->

<?
$sql = "SELECT	tbl_comunicado.tipo,
				count(tbl_comunicado.*) AS qtde
		FROM	tbl_comunicado
		LEFT JOIN tbl_produto USING (produto)
		LEFT JOIN tbl_linha   on tbl_produto.linha = tbl_linha.linha
		WHERE	tbl_comunicado.fabrica = $login_fabrica
		GROUP BY tbl_comunicado.tipo ORDER BY tbl_comunicado.tipo";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

	echo "<table width='400' align='center' border='0'>";
	echo "<tr bgcolor='#FF9900'>";
	echo "<td align='center' colspan='2'><font color='#ffffff' size='+1'><b>Tipos de Comunicados Disponíveis</b></font></td>";
	echo "</tr>";

	echo "<tr bgcolor='#FF9900'>";
	echo "<td align='center'><font color='#ffffff'><b>Tipo</b></font></td>";
	echo "<td align='center'><font color='#ffffff'><b>Qtde</b></font></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$cor = "#ffffff";
		if ($i % 2 == 0) $cor = '#ffeecc';

		echo "<tr bgcolor='$cor'>";

		echo "<td nowrap>";
		echo "<a href='$PHP_SELF?tipo=" . urlencode (pg_result ($res,$i,tipo)) . "'>";
		echo pg_result ($res,$i,tipo);
		echo "</a>";
		echo "</td>";

		echo "<td align='right'>";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";

	echo "<hr>";
}else{
	echo "<table width='400' align='center' border='0'>";
	echo "<tr bgcolor='#FF9900'>";
	echo "<td align='center' colspan='2'><font color='#ffffff' size='+1'><b>Não há Comunicados Disponíveis</b></font></td>";
	echo "</tr>";
	echo "</table>";
}
?>

<!-- ------------------- 10 Comunicados mais recentes -------------- -->

<?
$sql = "SELECT	tbl_comunicado.comunicado, 
				tbl_comunicado.descricao, 
				tbl_produto.referencia, 
				tbl_produto.descricao AS descricao_produto, 
				to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data , 
				tbl_comunicado.tipo 
		FROM	tbl_comunicado 
		LEFT JOIN tbl_produto USING (produto) 
		LEFT JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha
		WHERE	tbl_comunicado.fabrica = $login_fabrica 
		ORDER BY tbl_comunicado.data DESC LIMIT 10" ;
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table width='400' align='center' border='0'>";
	echo "<tr bgcolor='#669900'>";
	echo "<td align='center' colspan='3'><font color='#ffffff' size='+1'><b>10 Comunicados mais recentes</b></font></td>";
	echo "</tr>";

	echo "<tr bgcolor='#669900'>";
	echo "<td align='center'><font color='#ffffff'><b>Produto</b></font></td>";
	echo "<td align='center'><font color='#ffffff'><b>Descrição</b></font></td>";
	echo "<td align='center'><font color='#ffffff'><b>Data</b></font></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$cor = "#ffffff";
		if ($i % 2 == 0) $cor = '#ccffcc';

		echo "<tr bgcolor='$cor'>";

		echo "<td nowrap>";
		echo "<a href='$PHP_SELF?comunicado=" . urlencode (pg_result ($res,$i,comunicado)) . "'>";
		echo "<font size='-1'>";
		echo pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao_produto) ; 
		echo "</font>";
		echo "</a>";
		echo "</td>";

		echo "<td nowrap>";
		echo "<a href='$PHP_SELF?comunicado=" . urlencode (pg_result ($res,$i,comunicado)) . "'>";
		echo "<font size='-1'>";
		echo pg_result ($res,$i,descricao);
		echo "</font>";
		echo "</a>";
		echo "</td>";

		echo "<td align='right'>";
		echo "<font size='-1'>";
		echo pg_result ($res,$i,data);
		echo "</font>";
		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";
}
?>



<!-- MOSTRA RESULTADO DE BUSCA OU 5 PRIMEIRO REGISTROS -->
<?
if (1==2 and strlen($comunicado) == 0){
	if ($btn_acao == "pesquisar") {
		$sql = "SELECT  tbl_comunicado.comunicado                        ,
						tbl_produto.referencia AS prod_referencia        ,
						tbl_produto.descricao  AS prod_descricao         ,
						tbl_comunicado.descricao                         ,
						tbl_comunicado.mensagem                          ,
						tbl_comunicado.tipo                              ,
						to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
				FROM    tbl_comunicado
				LEFT JOIN    tbl_produto USING (produto)
				LEFT JOIN    tbl_linha   USING (linha)
				WHERE   tbl_comunicado.fabrica         = $login_fabrica AND ( 1=2 ";

		// por linha de produto
		if(strlen($chk1) > 0){
			if (strlen($linha) > 0) {
				$monta_sql .= "OR tbl_linha.linha = $linha ";
				$dt = 1;
			}
		}

		// por tipo de comunicado
		if(strlen($chk4) > 0){
			if (strlen($tipo) > 0) {
				$monta_sql .= "OR tbl_comunicado.tipo = '$tipo' ";
				$dt = 1;
			}
		}

		// entre datas
		if(strlen($chk2) > 0){
			if((strlen($data_inicial_01) == 10) && (strlen($data_final_01) == 10)){
				$monta_sql .= "OR (tbl_comunicado.data BETWEEN fnc_formata_data('$data_inicial_01') AND fnc_formata_data('$data_final_01')) ";
				$dt = 1;
			}
		}

		// referencia do produto
		if(strlen($chk3) > 0){
			if ($produto_referencia) {
				if ($dt == 1) $xsql = "AND ";
				else          $xsql = "OR ";

				$monta_sql .= "$xsql tbl_produto.referencia = '". $produto_referencia ."' ";
				$dt = 1;
			}
		}

		$monta_sql .= ") GROUP BY 
					tbl_comunicado.comunicado,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_comunicado.descricao,
					tbl_comunicado.mensagem,
					tbl_comunicado.tipo,
					tbl_comunicado.data ";
				if($login_fabrica == 3)
					$monta_sql .= "ORDER BY tbl_produto.descricao ASC";
				else
					$monta_sql .= "ORDER BY tbl_comunicado.data DESC";

		// ordena sql padrao
		$sql .= $monta_sql;

		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		//echo "<br>".nl2br($sql)."<br><br>".nl2br($sqlCount)."<br><BR>";

		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// máximo de links à serem exibidos
		$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		// ##### PAGINACAO ##### //

	}else{

		// seleciona os 5 ultimos
		$sql = "SELECT  tbl_comunicado.comunicado                        ,
						tbl_produto.referencia AS prod_referencia        ,
						tbl_produto.descricao  AS prod_descricao         ,
						tbl_comunicado.descricao                         ,
						tbl_comunicado.mensagem                          ,
						tbl_comunicado.tipo                              ,
						to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
				FROM    tbl_comunicado
				LEFT JOIN tbl_produto USING (produto)
				LEFT JOIN tbl_linha   USING (linha)
				WHERE   tbl_comunicado.fabrica         = $login_fabrica
				ORDER BY tbl_comunicado.data DESC 
				LIMIT 5 OFFSET 0 ";

		$sqlCount = "";
		$res = pg_exec($con,$sql);
	}

	if (pg_numrows($res) > 0) {
		echo "<table class='table' width='400' >";
		echo "<tr>";
		echo "<td align='left'><img src='imagens/cab_outrosregistrosreferentes.gif'></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br>";

		echo "<table class='table' width='500' border=0>";
		for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
			$comunicado           = trim(pg_result($res,$x,comunicado));
			$referencia           = trim(pg_result($res,$x,prod_referencia));
			$descricao            = trim(pg_result($res,$x,prod_descricao));
			$comunicado_descricao = trim(pg_result($res,$x,descricao));
			$comunicado_tipo      = trim(pg_result($res,$x,tipo));
			$comunicado_mensagem  = trim(pg_result($res,$x,mensagem));
			$comunicado_data      = trim(pg_result($res,$x,data));

			echo "<tr>\n";
			echo "	<td class='txt10Normal'>$comunicado_data</td>\n";
			echo "	<td><a href='$PHP_SELF?comunicado=$comunicado'>$comunicado_tipo</a></td>\n";
			echo "	<td class='txt10Normal'>$descricao</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
	}else{
		echo "Não há registro para esta opção.";
	}


	if (strlen($btn_acao) > 0) {

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
	}
}

include "rodape.php"; 

?>