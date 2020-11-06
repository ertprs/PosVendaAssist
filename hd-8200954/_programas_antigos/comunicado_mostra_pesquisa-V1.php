<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$title = "Comunicados $login_fabrica_nome";
$layout_menu = "tecnica";

include 'cabecalho.php';
include "javascript_pesquisas.php";

?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<br>

<?
if (strlen($_GET["acao"]) > 0) $acao = $_GET["acao"];

if($_GET["data_inicial"])       $data_inicial       = $_GET["data_inicial"];
if($_GET["data_final"])         $data_final         = $_GET["data_final"];
if($_GET["tipo"])               $tipo               = $_GET["tipo"];
if($_GET["descricao"])          $descricao          = $_GET["descricao"];
if($_GET["produto_referencia"]) $produto_referencia = $_GET["produto_referencia"];
if($_GET["produto_descricao"])  $produto_descricao  = $_GET["produto_descricao"];
if($_GET["produto_voltagem"])   $produto_voltagem   = $_GET["produto_voltagem"];

if ($acao == "PESQUISAR") {
	$ok = "";
	$sql = "SELECT  tbl_comunicado.comunicado                                       ,
					tbl_produto.referencia                    AS produto_referencia ,
					tbl_produto.descricao                     AS produto_descricao  ,
					tbl_produto.voltagem                      AS produto_voltagem   ,
					tbl_comunicado.descricao                                        ,
					tbl_comunicado.mensagem                                         ,
					tbl_comunicado.tipo                                             ,
					TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data
			FROM      tbl_comunicado
			LEFT JOIN tbl_produto USING (produto)
			WHERE tbl_comunicado.fabrica = $login_fabrica";
			
	if ($data_inicial != "dd/mm/aaaa" && strlen($data_inicial) == 10 && $data_final != "dd/mm/aaaa" && strlen($data_final) == 10) {
		list($dia, $mes, $ano) = explode("/", $data_inicial);
		$data_inicial = $ano . "-" . $mes . "-" . $dia;
		list($dia, $mes, $ano) = explode("/", $data_final);
		$data_final = $ano . "-" . $mes . "-" . $dia;
		
		$sql .= " AND tbl_comunicado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
		$ok = "ok";
	}
	if (strlen($tipo) > 0) {
		$sql .= " AND tbl_comunicado.tipo = '$tipo'";
		$ok = "ok";
	}
	if (strlen($descricao) > 0) {
		$sql .= " AND tbl_comunicado.descricao ILIKE '%$descricao%'";
		$ok = "ok";
	}
	if (strlen($produto_referencia) > 0) {
		$sql .= " AND tbl_produto.referencia ILIKE '%$produto_referencia%'";
		$ok = "ok";
	}
	if (strlen($produto_descricao) > 0) {
		$sql .= " AND tbl_produto.descricao ILIKE '%$produto_descricao%'";
		$ok = "ok";
	}
	if (strlen($produto_voltagem) > 0) {
		$sql .= " AND tbl_produto.voltagem ILIKE '%$produto_voltagem%'";
		$ok = "ok";
	}
	
	$sql .= " ORDER BY tbl_comunicado.data DESC";

	if (strlen($ok) == "ok") {
		echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr bgcolor='#D9E2EF' height='15'>";
		echo "<td colspan='4' align='center'><a href='comunicado_mostra.php'><img src='imagens/btn_nova_busca.gif'></a></td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
		echo "<td colspan='4' align='center'>&nbsp;<br><b>INFORME OS PARÂMETROS CORRETOS PARA CONSULTA</b><br>&nbsp;</td>";
		echo "</tr>";
		echo "<tr bgcolor='#D9E2EF' height='15'>";
		echo "<td colspan='4' align='center'><a href='comunicado_mostra.php'><img src='imagens/btn_nova_busca.gif'></a></td>";
		echo "</tr>";
		echo "</table>";
	}else{

	#	if (getenv("REMOTE_ADDR") == "201.0.9.216") { echo nl2br($sql); }

		// ##### PAGINACAO ##### //
		$sqlCount  = "SELECT count(*) FROM (" . $sql . ") AS count";

		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// máximo de links à serem exibidos
		$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		// ##### PAGINACAO ##### //

		echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr bgcolor='#D9E2EF' height='15'>";
		echo "<td colspan='4' align='center'><a href='comunicado_mostra.php'><img src='imagens/btn_nova_busca.gif'></a></td>";
		echo "</tr>";

		if (pg_numrows($res) > 0) {
			echo "<tr class='Titulo' height='15'>";
			echo "<td colspan='4'>CLIQUE NA LINHA PARA VISUALIZAR O COMUNICADO</td>";
			echo "</tr>";
			echo "<tr class='Titulo' height='15'>";
			echo "<td>PRODUTO</td>";
			echo "<td>DESCRIÇÃO</td>";
			echo "<td>TIPO</td>";
			echo "<td>DATA</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$comunicado         = trim(pg_result($res,$i,comunicado));
				$produto_referencia = trim(pg_result($res,$i,produto_referencia));
				$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
				$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));
				$produto_completo   = $produto_referencia . " - " . $produto_descricao;
				$descricao          = trim(pg_result($res,$i,descricao));
				$tipo               = trim(pg_result($res,$i,tipo));
				$data               = trim(pg_result($res,$i,data));

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				echo "<tr class='Conteudo' height='15' bgcolor='$cor' onclick=\"javascript: location.href='$PHP_SELF?comunicado=$comunicado';\">";
				echo "<td nowrap><acronym title='REFERÊNCIA: $produto_referencia\nDESCRIÇÃO: $produto_descricao\nVOLTAGEM: $produto_voltagem' style='cursor: help;'>" . substr($produto_completo,0,25) . "</acronym></td>";
				echo "<td nowrap><acronym title='DESCRIÇÃO: " . nl2br($descricao) . "' style='cursor: help;'>" . substr($descricao,0,25) . "</td>";
				echo "<td nowrap align='center'>" . $tipo . "</td>";
				echo "<td nowrap align='center'>" . $data . "</td>";
				echo "</tr>";
			}
		}else{
			echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
			echo "<td colspan='4' align='center'>&nbsp;<br><b>NENHUM RESULTADO ENCONTRADO</b><br>&nbsp;</td>";
			echo "</tr>";
		}
		echo "<tr bgcolor='#D9E2EF' height='15'>";
		echo "<td colspan='4' align='center'><a href='comunicado_mostra.php'><img src='imagens/btn_nova_busca.gif'></a></td>";
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
	}
	
	$comunicado = "";
}

if (strlen($_GET["comunicado"]) > 0) $comunicado = $_GET["comunicado"];

if (strlen($comunicado) > 0) {
	$sql =	"SELECT tbl_produto.referencia                    AS produto_referencia ,
					tbl_produto.descricao                     AS produto_descricao  ,
					tbl_produto.voltagem                      AS produto_voltagem   ,
					tbl_comunicado.descricao                                        ,
					tbl_comunicado.mensagem                                         ,
					tbl_comunicado.tipo                                             ,
					TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data
			FROM      tbl_comunicado
			LEFT JOIN tbl_produto USING (produto)
			WHERE tbl_comunicado.fabrica    = $login_fabrica
			AND   tbl_comunicado.comunicado = $comunicado";
	$res = pg_exec($con,$sql);
	
	echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr bgcolor='#D9E2EF' height='15'>";
	echo "<td colspan='4' align='center'><a href='comunicado_mostra.php'><img src='imagens/btn_nova_busca.gif'></a></td>";
	echo "</tr>";
	
	if (pg_numrows($res) == 1) {
		$produto_referencia = trim(pg_result($res,$i,produto_referencia));
		$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
		$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));
		$produto_completo   = $produto_referencia . " - " . $produto_descricao;
		$descricao          = trim(pg_result($res,$i,descricao));
		$tipo               = trim(pg_result($res,$i,tipo));
		$data               = trim(pg_result($res,$i,data));

		echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
		echo "<td>";
		echo "&nbsp;";
		echo "<p align='center'><img border='0' src='imagens/cab_comunicado.gif'></p>";
		echo "<p align='center'>$tipo  -  $data</p>";
		
		if (strlen($descricao) > 0) {
			echo "<table width='550' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
			echo "<td>" . nl2br($descricao) . "</td>";
			echo "</tr>";
			echo "</table>";
		}
		if (strlen($comunicado_mensagem) > 0) {
			echo "<table width='550' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
			echo "<tr class='Conteudo' bgcolor='#D9E2EF' height='15'>";
			echo "<td>" . nl2br($comunicado_mensagem) . "</td>";
			echo "</tr>";
			echo "</table>";
		}
		
		$gif = "comunicados/$comunicado.gif";
		$jpg = "comunicados/$comunicado.jpg";
		$pdf = "comunicados/$comunicado.pdf";
		$doc = "comunicados/$comunicado.doc";
		$rtf = "comunicados/$comunicado.rtf";
		$xls = "comunicados/$comunicado.xls";

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
			echo "<p align='center'>Para visualizar o arquivo, <a href='comunicados/$comunicado.pdf' target='_blank'>clique aqui</a>.<br>";
			echo "<font size='1' color='#A02828'>Se você não possui o Acrobat Reader&reg;, <a href='http://www.adobe.com/products/acrobat/readstep2.html'>instale agora</a>.</font></p>";
		}
		echo "&nbsp;";
		echo "</td>";
		echo "</tr>";
	}else{
	}
	
	echo "<tr bgcolor='#D9E2EF' height='15'>";
	echo "<td colspan='4' align='center'><a href='comunicado_mostra.php'><img src='imagens/btn_nova_busca.gif'></a></td>";
	echo "</tr>";
	echo "</table>";
}

include "rodape.php";
?>
