<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro = "";

if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);

if (strlen($_GET["comunicado"]) > 0) $comunicado = $_GET["comunicado"];

$title = "Comunicados $login_fabrica_nome";
$layout_menu = "tecnica";

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
$sql =	"SELECT tbl_posto_fabrica.codigo_posto                     ,
				tbl_posto_fabrica.pedido_em_garantia               ,
				tbl_posto_fabrica.pedido_faturado                  ,
				tbl_posto.suframa                                  ,
				tbl_posto.nome                       AS posto_nome
		FROM tbl_posto
		JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
								AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_posto.posto = $login_posto;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 1) {
	$codigo_posto       = pg_result($res,0,codigo_posto);
	$pedido_em_garantia = pg_result($res,0,pedido_em_garantia);
	$pedido_faturado    = pg_result($res,0,pedido_faturado);
	$suframa            = pg_result($res,0,suframa);
	$posto_nome         = pg_result($res,0,posto_nome);
}

##### VISUALIZA COMUNICADO #####

if ($acao == "VER" && strlen($comunicado) > 0) {
	$sql =	"SELECT *
			FROM tbl_comunicado_posto_blackedecker
			WHERE fabrica    = $login_fabrica
			AND   posto      = $login_posto
			AND   comunicado = $comunicado;";
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) == 0 && strlen($_GET["antigo"]) == 0) {
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
	
	if (strlen($_GET["antigo"]) == 0) {
		$sql =	"SELECT comunicado                                             ,
						remetente_email                           AS remetente ,
						TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data      ,
						descricao                                 AS assunto   ,
						mensagem
				FROM tbl_comunicado
				WHERE fabrica    = $login_fabrica
				AND   comunicado = $comunicado;";
	}else{
		$sql =	"SELECT comunicado                               ,
						remetente                                ,
						TO_CHAR(data_envio,'dd/mm/yyyy') AS data ,
						assunto                                  ,
						mensagem
				FROM tbl_comunicado_blackedecker
				WHERE comunicado = $comunicado;";
	}
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";

		echo "<tr class='Titulo'>";
		echo "<td colspan='2'>COMUNICADO</td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td>REMETENTE</td>";
		echo "<td>DATA</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td align='center'>" . pg_result($res,0,remetente) . "</td>";
		echo "<td align='center'>" . pg_result($res,0,data) . "</td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td colspan='2'>ASSUNTO</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td colspan='2' align='center'>" . pg_result($res,0,assunto) . "</td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td colspan='2'>MENSAGEM</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='#F1F4FA'>";
		echo "<td colspan='2' align='center'><b>Prezado(a) $posto_nome,</b><br><br>" . nl2br(pg_result($res,0,mensagem)) . "</td>";
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

##### COMUNICADOS DO SISTEMA ANTIGO #####
if ($acao == "ANTIGO") {
	$sql =	"SELECT DISTINCT
					tbl_comunicado_blackedecker.comunicado                               ,
					tbl_comunicado_blackedecker.assunto                                  ,
					tbl_comunicado_blackedecker.data_envio                               ,
					TO_CHAR(tbl_comunicado_blackedecker.data_envio,'DD/MM/YYYY') AS data
			FROM    tbl_comunicado_blackedecker
			WHERE   (tbl_comunicado_blackedecker.destinatario_especifico ILIKE '%$codigo_posto%' OR tbl_comunicado_blackedecker.destinatario = $login_tipo_posto)
			GROUP BY tbl_comunicado_blackedecker.comunicado ,
					 tbl_comunicado_blackedecker.assunto    ,
					 tbl_comunicado_blackedecker.data_envio
			ORDER BY tbl_comunicado_blackedecker.data_envio DESC, tbl_comunicado_blackedecker.comunicado DESC";
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
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='2' align='center'>COMUNICADOS ANTIGOS</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td align='center'>DESCRIÇÃO</td>";
		echo "<td align='center'>DATA</td>";
		echo "</tr>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td><a href='$PHP_SELF?acao=VER&antigo=t&comunicado=" . pg_result($res,$i,comunicado) . "'>" . pg_result($res,$i,assunto) . "</a></td>";
			echo "<td align='center'>" . pg_result($res,$i,data) . "</td>";
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

	echo "<p align='center'><a href='$PHP_SELF'>Voltar</a></p>";
}
##### COMUNICADOS LIDOS #####
if ($acao == "LIDOS") {
/*
	$sql =	"SELECT * FROM (
				(
					SELECT  tbl_comunicado.comunicado                          ,
							tbl_comunicado.tipo                                ,
							tbl_comunicado.descricao                           ,
							TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data  ,
							tbl_comunicado.data                       AS ordem ,
							tbl_comunicado.pedido_em_garantia                  ,
							tbl_comunicado.pedido_faturado                     ,
							tbl_comunicado.suframa
					FROM tbl_comunicado
					JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
					WHERE tbl_comunicado.fabrica     = $login_fabrica
					AND   (tbl_comunicado.destinatario_especifico ILIKE '%$codigo_posto%' OR tbl_comunicado.destinatario = $login_tipo_posto)
				) UNION (
					SELECT  tbl_comunicado.comunicado                          ,
							tbl_comunicado.tipo                                ,
							tbl_comunicado.descricao                           ,
							TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data  ,
							tbl_comunicado.data                       AS ordem ,
							tbl_comunicado.pedido_em_garantia                  ,
							tbl_comunicado.pedido_faturado                     ,
							tbl_comunicado.suframa
					FROM tbl_comunicado
					JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
					WHERE tbl_comunicado.fabrica     = $login_fabrica
					AND   (tbl_comunicado.destinatario_especifico IS NULL OR tbl_comunicado.destinatario IS NULL)
				)
			) AS A
			WHERE (UPPER(A.tipo) <> 'PROCEDIMENTO'";


	if ($pedido_em_garantia == "t") $sql .=	" AND ( A.pedido_em_garantia IS TRUE OR A.pedido_em_garantia IS NULL )";
	if ($pedido_faturado == "t")    $sql .= " AND ( A.pedido_faturado IS TRUE OR A.pedido_faturado IS NULL )";
	if ($suframa == "t")            $sql .= " AND ( A.suframa IS TRUE OR A.suframa IS NULL )";
	$sql .=	" ) ORDER BY A.ordem DESC;";
*/
	$sql = "SELECT  distinct tbl_comunicado.comunicado                          ,
					tbl_comunicado.tipo                                ,
					tbl_comunicado.descricao                           ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data  ,
					tbl_comunicado.data                       AS ordem ,
					tbl_comunicado.pedido_em_garantia                  ,
					tbl_comunicado.pedido_faturado                     ,
					tbl_comunicado.suframa                             
			FROM tbl_comunicado
			JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
			WHERE tbl_comunicado.fabrica     = $login_fabrica
			AND   (tbl_comunicado.destinatario_especifico ILIKE '%$codigo_posto%' OR tbl_comunicado.destinatario = $login_tipo_posto) ";


	if ($pedido_em_garantia == "t") $sql .=	" AND ( tbl_comunicado.pedido_em_garantia IS TRUE OR tbl_comunicado.pedido_em_garantia IS NULL )";
	if ($pedido_faturado == "t")    $sql .= " AND ( tbl_comunicado.pedido_faturado IS TRUE OR tbl_comunicado.pedido_faturado IS NULL )";
	if ($suframa == "t")            $sql .= " AND ( tbl_comunicado.suframa IS TRUE OR tbl_comunicado.suframa IS NULL )";
	$sql .=	" ORDER BY tbl_comunicado.data DESC;";

	$res = pg_exec($con,$sql);
//echo nl2br($sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='2' align='center'>COMUNICADOS LIDOS</td>";
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
	}else{
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td align='center'>COMUNICADOS LIDOS</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' bgcolor='$cor'>";
		echo "<td align='center'>Comunicado não encontrado.</td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
	}
	
	echo "<p align='center'><a href='javascript: history.back(-1);'>Voltar</a></p>";
}

if (strlen($acao) == 0) {

	##### COMUNICADOS NÃO LIDOS #####

		$sql =	"SELECT * FROM (
				(
					SELECT  tbl_comunicado.comunicado                          ,
							tbl_comunicado.tipo                                ,
							tbl_comunicado.descricao                           ,
							TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data  ,
							tbl_comunicado.data                       AS ordem ,
							tbl_comunicado.pedido_em_garantia                  ,
							tbl_comunicado.pedido_faturado                     ,
							tbl_comunicado.suframa
					FROM tbl_comunicado
					LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
					WHERE tbl_comunicado.fabrica     = $login_fabrica
					AND   (tbl_comunicado.destinatario_especifico ILIKE '%$codigo_posto%' OR tbl_comunicado.destinatario = $login_tipo_posto)
					AND   tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL
				) UNION (
					SELECT  tbl_comunicado.comunicado                          ,
							tbl_comunicado.tipo                                ,
							tbl_comunicado.descricao                           ,
							TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data  ,
							tbl_comunicado.data                       AS ordem ,
							tbl_comunicado.pedido_em_garantia                  ,
							tbl_comunicado.pedido_faturado                     ,
							tbl_comunicado.suframa
					FROM tbl_comunicado
					LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
					WHERE tbl_comunicado.fabrica     = $login_fabrica
					AND   (tbl_comunicado.destinatario_especifico IS NULL OR tbl_comunicado.destinatario IS NULL)
					AND   tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL
				)
			) AS A
			WHERE 1 = 1 ";

	$sql = "SELECT  tbl_comunicado.comunicado                          ,
					tbl_comunicado.tipo                                ,
					tbl_comunicado.descricao                           ,
					TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data  ,
					tbl_comunicado.data                       AS ordem ,
					tbl_comunicado.pedido_em_garantia                  ,
					tbl_comunicado.pedido_faturado                     ,
					tbl_comunicado.suframa
			FROM tbl_comunicado
			LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
			WHERE tbl_comunicado.fabrica     = $login_fabrica
			AND   (tbl_comunicado.destinatario_especifico ILIKE '%$codigo_posto%' OR tbl_comunicado.destinatario = $login_tipo_posto)
			AND   tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL ";
	if ($pedido_em_garantia == "t") $sql .=	" AND tbl_comunicado.pedido_em_garantia IS NOT FALSE ";
	if ($pedido_faturado == "t")    $sql .= " AND tbl_comunicado.pedido_faturado IS NOT FALSE ";
	if ($suframa == "t")            $sql .= " AND tbl_comunicado.suframa IS NOT FALSE ";
	$sql .= " ORDER BY comunicado DESC";
//			WHERE A.tipo != 'Procedimento'";
//echo nl2br($sql);
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='2' align='center'>COMUNICADOS NOVOS</td>";
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

	##### 10 COMUNICADOS MAIS RECENTES #####

	$sql =	"SELECT * FROM (
				(
					SELECT  tbl_comunicado.comunicado                             ,
							tbl_comunicado.tipo                                   ,
							tbl_comunicado.descricao                              ,
							to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data     ,
							tbl_comunicado.data                       AS ordem    ,
							tbl_comunicado.pedido_em_garantia                     ,
							tbl_comunicado.pedido_faturado                        ,
							tbl_comunicado.suframa
					FROM    tbl_comunicado
					WHERE   tbl_comunicado.fabrica     = $login_fabrica
					AND     (tbl_comunicado.destinatario_especifico ILIKE '%$codigo_posto%' OR tbl_comunicado.destinatario = $login_tipo_posto)
					AND     tbl_comunicado.tipo IS NULL
				) UNION (
					SELECT  tbl_comunicado.comunicado                             ,
							tbl_comunicado.tipo                                   ,
							tbl_comunicado.descricao                              ,
							to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data     ,
							tbl_comunicado.data                       AS ordem    ,
							tbl_comunicado.pedido_em_garantia                     ,
							tbl_comunicado.pedido_faturado                        ,
							tbl_comunicado.suframa
					FROM    tbl_comunicado
					WHERE   tbl_comunicado.fabrica     = $login_fabrica
					AND     (tbl_comunicado.destinatario_especifico IS NULL OR tbl_comunicado.destinatario IS NULL)
					AND     upper (tbl_comunicado.tipo) NOT IN ('PROCEDIMENTO')
				)
			) AS A
			WHERE  1 = 1";
	if ($pedido_em_garantia == "t") $sql .=	" AND ( A.pedido_em_garantia IS TRUE OR A.pedido_em_garantia IS NULL )";
	if ($pedido_faturado == "t")    $sql .= " AND ( A.pedido_faturado IS TRUE OR A.pedido_faturado IS NULL )";
	if ($suframa == "t")            $sql .= " AND ( A.suframa IS TRUE OR A.suframa IS NULL )";
	$sql .=	" ORDER BY A.ordem DESC LIMIT 10;";
//echo nl2br($sql);
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='2' align='center'>10 COMUNICADOS MAIS RECENTES</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td align='center'>DESCRIÇÃO</td>";
		echo "<td align='center'>DATA</td>";
		echo "</tr>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td><a href='$PHP_SELF?acao=VER&comunicado=" . pg_result($res,$i,comunicado) . "'>" . pg_result($res,$i,descricao) . "</a></td>";
			echo "<td align='center'>" . pg_result($res,$i,data) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
	}

	echo "<p align='center'><a href='$PHP_SELF?acao=LIDOS'>Comunicados Lidos</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$PHP_SELF?acao=ANTIGO'>Comunicados Antigos</a></p>";

}

include "rodape.php";
?>
