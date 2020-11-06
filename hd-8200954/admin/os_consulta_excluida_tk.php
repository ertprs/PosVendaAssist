<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

/*
$cookget = @explode("?", $REQUEST_URI);		// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookget", $cookget[1]);			// expira qdo fecha o browser
*/

// recebe as variaveis
if($_POST["sua_os"])				$sua_os                = trim($_POST["sua_os"]);
if($_POST["codigo_posto"])			$codigo_posto          = trim($_POST["codigo_posto"]);
if($_POST["produto_referencia"])	$produto_referencia    = trim($_POST["produto_referencia"]);
if($_POST["numero_serie"])			$numero_serie          = trim($_POST["numero_serie"]);
if($_POST["nota_fiscal"])			$nota_fical            = trim($_POST["nota_fical"]);
if($_POST["nome_consumidor"])		$nome_consumidor       = trim($_POST["nome_consumidor"]);
if($_POST["data_inicial_01"])		$data_inicial_abertura = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_abertura   = trim($_POST["data_final_01"]);
//if($_POST["data_inicial_exclusao"])	$data_inicial__exclusao = trim($_POST["data_inicial_exclusao"]);
//if($_POST["data_final_exclusao"])	$data_final__exclusao   = trim($_POST["data_final_exclusao"]);

if($_GET["sua_os"])					$sua_os                = trim($_GET["sua_os"]);
if($_GET["codigo_posto"])			$codigo_posto          = trim($_GET["codigo_posto"]);
if($_GET["produto_referencia"])		$produto_referencia    = trim($_GET["produto_referencia"]);
if($_GET["numero_serie"])			$numero_serie          = trim($_GET["numero_serie"]);
if($_GET["nota_fiscal"])			$nota_fiscal           = trim($_GET["nota_fiscal"]);
if($_GET["nome_consumidor"])		$nome_consumidor       = trim($_GET["nome_consumidor"]);
if($_GET["data_inicial_01"])		$data_inicial_abertura = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_abertura   = trim($_GET["data_final_01"]);
//if($_GET["data_inicial_exclusao"])	$data_inicial__exclusao = trim($_GET["data_inicial_exclusao"]);
//if($_GET["data_final_exclusao"])	$data_final__exclusao   = trim($_GET["data_final_exclusao"]);

$produto_referencia = str_replace ("." , "" , $produto_referencia);
$produto_referencia = str_replace ("-" , "" , $produto_referencia);
$produto_referencia = str_replace ("/" , "" , $produto_referencia);
$produto_referencia = str_replace (" " , "" , $produto_referencia);


$layout_menu = "callcenter";
$title = "Relação de Ordens de Serviços Excluídas";

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

a.linkTitulo {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 0px solid;
	color: #ffffff
}

</style>

<?

// BTN_NOVA BUSCA
echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<TR class='table_line'>";
echo "<td align='center' background='#D9E2EF'>";
echo "<a href='os_parametros_excluida.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
echo "</td>";
echo "</TR>";
echo "</TABLE>";

// INICIO DA SQL PADRAO PARA TODAS AS OPCOES

$sql = "SELECT  tbl_admin.login                                       AS admin_nome       ,
				tbl_os_excluida.sua_os                                                    ,
				tbl_os_excluida.codigo_posto                                              ,
				tbl_posto.nome                                        AS posto_nome       ,
				tbl_os_excluida.referencia_produto                                        ,
				tbl_produto.descricao                                 AS produto_descricao,
				to_char(tbl_os_excluida.data_digitacao,'DD/MM/YYYY')  AS data_digitacao   ,
				to_char(tbl_os_excluida.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
				to_char(tbl_os_excluida.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
				tbl_os_excluida.serie                                                     ,
				tbl_os_excluida.nota_fiscal                                               ,
				to_char(tbl_os_excluida.data_nf,'DD/MM/YYYY')         AS data_nf          ,
				tbl_os_excluida.consumidor_nome                                           ,
				to_char(tbl_os_excluida.data_exclusao,'DD/MM/YYYY')   AS data_exclusao
		FROM 	tbl_os_excluida
		JOIN 	tbl_posto USING (posto)
		JOIN 	tbl_posto_fabrica 	 ON tbl_posto.posto = tbl_posto_fabrica.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_produto ON tbl_produto.referencia = tbl_os_excluida.referencia_produto
		LEFT JOIN tbl_admin   USING (admin)
		WHERE	tbl_os_excluida.fabrica = $login_fabrica 
		AND		(1=2 ";


$sql = "SELECT * FROM (
			(
			SELECT	tbl_admin.login                                       AS admin_nome        ,
					tbl_os_excluida.fabrica                                                    ,
					tbl_os_excluida.sua_os                                                     ,
					tbl_os_excluida.codigo_posto                                               ,
					tbl_posto.nome                                        AS posto_nome        ,
					tbl_os_excluida.referencia_produto                                         ,
					tbl_produto.descricao                                 AS produto_descricao ,
					to_char(tbl_os_excluida.data_digitacao,'DD/MM/YYYY')  AS data_digitacao    ,
					to_char(tbl_os_excluida.data_abertura,'DD/MM/YYYY')   AS data_abertura     ,
					tbl_os_excluida.data_abertura                         AS data_consulta     ,
					to_char(tbl_os_excluida.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
					tbl_os_excluida.serie                                                      ,
					tbl_os_excluida.nota_fiscal                                                ,
					to_char(tbl_os_excluida.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_os_excluida.consumidor_nome                                            ,
					to_char(tbl_os_excluida.data_exclusao,'DD/MM/YYYY')   AS data_exclusao     
			FROM      tbl_os_excluida
			JOIN      tbl_posto USING (posto)
			JOIN      tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_produto       ON  tbl_produto.referencia    = tbl_os_excluida.referencia_produto
			LEFT JOIN tbl_admin         ON  tbl_admin.admin           = tbl_os_excluida.admin
			WHERE tbl_os_excluida.fabrica =  $login_fabrica

			) UNION (

			SELECT	tbl_admin.login                              AS admin_nome         ,
					tbl_os.fabrica                                                     ,
					tbl_os.sua_os                                                      ,
					tbl_posto_fabrica.codigo_posto                                     ,
					tbl_posto.nome                               AS posto_nome         ,
					tbl_produto.referencia                       AS referencia_produto ,
					tbl_produto.descricao                        AS produto_descricao  ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao     ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura      ,
					tbl_os.data_abertura                         AS data_consulta      ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento    ,
					tbl_os.serie                                                       ,
					tbl_os.nota_fiscal                                                 ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf            ,
					tbl_os.consumidor_nome                                             ,
					NULL                                         AS data_exclusao      
			FROM	tbl_os
			JOIN	tbl_posto USING (posto)
			JOIN      tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
			LEFT JOIN tbl_admin         ON  tbl_admin.admin           = tbl_os.admin
			WHERE tbl_os.excluida IS TRUE
			AND   tbl_os.fabrica =  $login_fabrica
			)
		) AS a
		WHERE 1=2 ";

if($data_inicial_abertura <> 'dd/mm/aaaa' AND $data_final_abertura <> 'dd/mm/aaaa'){
	if(strlen($data_inicial_abertura) > 0 AND strlen($data_final_abertura) > 0){
	// entre datas
		$data_inicial     = $data_inicial_abertura;
		$data_final       = $data_final_abertura;

		$data_inicial = str_replace ("/","",$data_inicial);
		$data_inicial = str_replace ("-","",$data_inicial);
		$data_inicial = str_replace (".","",$data_inicial);
		$data_inicial = str_replace (" ","",$data_inicial);
		$data_inicial = substr ($data_inicial,4,4) . "-" . substr ($data_inicial,2,2) . "-" . substr ($data_inicial,0,2);

		$data_final = str_replace ("/","",$data_final);
		$data_final = str_replace ("-","",$data_final);
		$data_final = str_replace (".","",$data_final);
		$data_final = str_replace (" ","",$data_final);
		$data_final = substr ($data_final,4,4) . "-" . substr ($data_final,2,2) . "-" . substr ($data_final,0,2);

		$monta_sql .= "OR (a.data_consulta BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59') ";
		$dt = 1;

		$msg .= " e datas de abertura de OSs excluídas entre os dias $data_inicial_abertura e $data_final_abertura ";
	}
}
/*
if(strlen($data_inicial_exclusao) > 0 AND strlen($data_final_exclusao) > 0){
// entre datas
	$data_inicial     = $data_inicial_exclusao;
	$data_final       = $data_final_exclusao;

	$data_inicial = str_replace ("/","",$data_inicial);
	$data_inicial = str_replace ("-","",$data_inicial);
	$data_inicial = str_replace (".","",$data_inicial);
	$data_inicial = str_replace (" ","",$data_inicial);
	$data_inicial = substr ($data_inicial,4,4) . "-" . substr ($data_inicial,2,2) . "-" . substr ($data_inicial,0,2);

	$data_final = str_replace ("/","",$data_final);
	$data_final = str_replace ("-","",$data_final);
	$data_final = str_replace (".","",$data_final);
	$data_final = str_replace (" ","",$data_final);
	$data_final = substr ($data_final,4,4) . "-" . substr ($data_final,2,2) . "-" . substr ($data_final,0,2);

	$monta_sql .= "OR (tbl_os_excluida.data_exclusao BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59') ";
	$dt = 1;

	$msg .= " e datas de exclusão de OSs excluídas entre os dias $data_inicial_exclusao e $data_final_exclusao ";
}
*/

// codigo do posto
if (strlen($codigo_posto) > 0){
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql a.codigo_posto = '". $codigo_posto."' ";
	$dt = 1;

	$msg .= " e OS lançadas pelo posto $nome_posto";

}

// referencia do produto
if (strlen($produto_referencia) > 0) {
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql a.referencia_produto = '".$produto_referencia."' ";
	$dt = 1;

	$msg .= " e OS lançadas contendo o produto $produto_nome";

}

if (strlen($numero_serie) > 0) {
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql a.serie = '". $numero_serie."' ";
	$dt = 1;

	$msg .= " e OS lançadas contendo produtos com número de série : $numero_serie";

}

if (strlen($nome_consumidor) > 0){
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql a.consumidor_nome ILIKE '%".$nome_consumidor."%' ";
	$dt = 1;

	$msg .= " e OS lançadas para o consumidor $nome_consumidor";
}

if (strlen($sua_os) > 0){
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql a.sua_os ilike '%".$sua_os."%' ";
	$dt = 1;

	$msg .= " e OS lançadas com Nº $sua_os";
}

if (strlen($nota_fiscal) > 0){
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql a.nota_fiscal ilike '%".$nota_fiscal."%' ";
	$dt = 1;

	$msg .= " e OS lançadas com nota fiscal Nº $nota_fiscal";
}

// ordena sql padrao
$sql .= $monta_sql;

if (strlen($_GET['order']) > 0){
	switch ($_GET['order']){
		case 'os':         $order_by = ""; break;
		case 'serie':      $order_by = "tbl_os.serie DESC,"; break;
		case 'abertura':   $order_by = "tbl_os.data_abertura DESC,"; break;
		case 'fechamento': $order_by = "tbl_os.data_fechamento DESC,"; break;
		case 'consumidor': $order_by = "tbl_os.consumidor_nome ASC, tbl_posto.nome ASC,"; break;
		case 'posto':      $order_by = "tbl_posto.nome ASC,"; break;
		case 'produto':    $order_by = "tbl_produto.descricao ASC,"; break;
	}
	$sql .= " ORDER BY $order_by lpad (tbl_os_excluida.sua_os,10,'0') DESC";
}else{
	$sql .= " ORDER BY lpad (a.sua_os,10,'0') DESC";
}

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

echo "<br>".nl2br($sql)."<br>";

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
	echo "<TD colspan=9>$msg</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD>OS</TD>\n";
	echo "<TD>SÉRIE</TD>\n";
	echo "<TD width='075'>ABERTURA</TD>\n";
	echo "<TD width='130'>CONSUMIDOR</TD>\n";
	echo "<TD width='130'>POSTO</TD>\n";
	echo "<TD>PRODUTO</TD>\n";
	echo "<TD>NOTA FISCAL</TD>\n";
	echo "<TD colspan='2'>EXCLUÍDA</TD>\n";
	echo "</TR>\n";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		//$os_excluida        = trim(pg_result ($res,$i,os_excluida));
		$admin_nome         = trim(pg_result ($res,$i,admin_nome));
		if (strlen($admin_nome) == 0) $admin_nome = "Posto";
		else                          $admin_nome = ucfirst($admin_nome);
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$data_digitacao     = trim(pg_result ($res,$i,data_digitacao));
		$data_abertura      = trim(pg_result ($res,$i,data_abertura));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		$referencia_produto = trim(pg_result ($res,$i,referencia_produto));
		$produto_descricao  = trim(pg_result ($res,$i,produto_descricao));
		$serie              = trim(pg_result ($res,$i,serie));
		$nota_fiscal        = trim(pg_result ($res,$i,nota_fiscal));
		$data_nf            = trim(pg_result ($res,$i,data_nf));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$data_exclusao      = trim(pg_result ($res,$i,data_exclusao));
		
		$cor = "#F7F5F0"; 
		$btn = "amarelo";
		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}
		
		if (strlen (trim ($sua_os)) == 0) $sua_os = $os;
		
		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		echo "<TD nowrap>$sua_os</TD>\n";
		echo "<TD nowrap>$serie</TD>\n";
		echo "<TD align='center'><ACRONYM TITLE=\"Digitação: $data_digitacao | Fechamento: $data_fechamento\">$data_abertura</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$codigo_posto - $posto_nome\">".substr($posto_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$referencia_produto - $produto_descricao\">".substr($produto_descricao,0,17)."</ACRONYM></TD>\n";
		echo "<TD align='center' nowrap><ACRONYM TITLE=\"Data da NF: $data_nf\">$nota_fiscal</ACRONYM></TD>\n";
		echo "<TD align='center' nowrap>$data_exclusao</TD>\n";
		echo "<TD nowrap>$admin_nome</TD>\n";
		echo "</TR>\n";
	
	}
}
	echo "</TABLE>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_parametros_excluida.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
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