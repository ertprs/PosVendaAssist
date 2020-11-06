<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro = "";

if($_GET["acao"]) $acao = strtoupper($_GET["acao"]);

if($_GET["comunicado"]) $comunicado = $_GET["comunicado"];

$title = "Procedimentos $login_fabrica_nome";
$layout_menu = "procedimento";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Arial, Geneva, Helvetica, sans-serif;
	font-size: 11 px;
	font-weight: bold;
	color: #000000;
	background-color: #D9E2EF
}
.Conteudo {
	font-family: Verdana, Tahoma, Arial, Geneva, Helvetica, sans-serif;
	font-size: 11 px;
	font-weight: normal;
}
</style>

<br>

<?
##### COMUNICADO #####

if ($acao == "VER" && strlen($comunicado) > 0) {
	$sql =	"SELECT *
			FROM tbl_comunicado_posto_blackedecker
			WHERE fabrica    = $login_fabrica
			AND   posto      = $login_posto
			AND   comunicado = $comunicado;";
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) == 0) {
		$sql =	"INSERT INTO tbl_comunicado_posto_blackedecker (
					fabrica    ,
					posto      ,
					comunicado
				) VALUES (
					$login_fabrica ,
					$login_posto   ,
					$comunicado
				);";
		$res = pg_exec($con,$sql);
	}
	
	$sql =	"SELECT comunicado                                        ,
					remetente_email                                   ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data ,
					descricao                                         ,
					mensagem
			FROM tbl_comunicado
			WHERE fabrica    = $login_fabrica
			AND   comunicado = $comunicado;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";

		echo "<tr class='Titulo'>";
		echo "<td colspan='2'>PROCEDIMENTO</td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td>REMETENTE</td>";
		echo "<td>DATA</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td>" . pg_result($res,0,remetente_email) . "</td>";
		echo "<td align='center'>" . pg_result($res,0,data) . "</td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td colspan='2'>ASSUNTO</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td colspan='2' align='center'>" . pg_result($res,0,descricao) . "</td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td colspan='2'>MENSAGEM</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td colspan='2' align='center'>";
		if (strlen(trim(pg_result($res,0,mensagem))) > 0) 
			echo "<b>Prezado(a) $posto_nome,</b>";
		echo "<br><br>" . nl2br(pg_result($res,0,mensagem)) . "</td>";
		echo "</tr>";

		$jpg = "/var/www/assist/www/comunicados/$comunicado.jpg";
		$gif = "/var/www/assist/www/comunicados/$comunicado.gif";
		$pdf = "/var/www/assist/www/comunicados/$comunicado.pdf";
		$doc = "/var/www/assist/www/comunicados/$comunicado.doc";
		$xls = "/var/www/assist/www/comunicados/$comunicado.xls";
		
		if (file_exists($jpg) == true)
			$arquivo = "http://www.telecontrol.com.br/assist/comunicados/$comunicado.jpg";
		if (file_exists($gif) == true)
			$arquivo = "http://www.telecontrol.com.br/assist/comunicados/$comunicado.gif";
		if (file_exists($pdf) == true)
			$arquivo = "http://www.telecontrol.com.br/assist/comunicados/$comunicado.pdf";
		if (file_exists($doc) == true)
			$arquivo = "http://www.telecontrol.com.br/assist/comunicados/$comunicado.doc";
		if (file_exists($xls) == true)
			$arquivo = "http://www.telecontrol.com.br/assist/comunicados/$comunicado.xls";

		if (strlen($arquivo) > 0) {
			echo "<tr class='Titulo'>";
			echo "<td colspan='2'>ANEXO</td>";
			echo "</tr>";
			echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
			echo "<td colspan='2' align='center'><a href='$arquivo' target='_blank'>Clique aqui</td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "<br>";
	}
	echo "<p align='center'><a href='javascript: history.back(-1);'>Voltar</a></p>";
}

if ($acao != "VER") {

	##### PROCEDIMENTOS #####

	$sql =	"SELECT tbl_comunicado.comunicado                                        ,
					tbl_comunicado.descricao                                         ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data 
			FROM tbl_comunicado
			WHERE tbl_comunicado.fabrica     = $login_fabrica
			AND   UPPER(tbl_comunicado.tipo) = 'PROCEDIMENTO'
			ORDER BY tbl_comunicado.data DESC";
	
//if($ip=="201.43.28.101"){ echo "$sql";}

	##### PAGINAÇÃO - INÍCIO #####
	$sqlCount  = "SELECT count(*) FROM (" . $sql . ") AS count";

	require "_class_paginacao.php";

	// Definições de variáveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	##### PAGINAÇÃO - FIM #####
	
	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='2' align='center'>PROCEDIMENTOS</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td align='center'>DESCRIÇÃO</td>";
		echo "<td align='center'>DATA</td>";
		echo "</tr>";
		for ($j = 0 ; $j < pg_numrows($res) ; $j++) {
			$cor = ($j % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td><a href='$PHP_SELF?acao=VER&comunicado=" . pg_result($res,$j,comunicado) . "'>" . pg_result($res,$j,descricao) . "</a></td>";
			echo "<td align='center'>" . pg_result($res,$j,data) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
	}
	
	##### PAGINAÇÃO - INÍCIO #####
	
	// Links da paginação
	echo "<br>";
	echo "<div>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

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
		echo " <font color='#CCCCCC' size='1'>(Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)</font>";
		echo "</div>";
	}
	##### PAGINAÇÃO - FIM #####
}

include "rodape.php"; 
?>
