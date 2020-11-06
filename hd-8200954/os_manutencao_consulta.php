<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";


$os = $_GET['excluir'];
if (strlen ($os) > 0) {
	$sql = "DELETE FROM tbl_os 
			WHERE  os      = $os 
			AND    posto   = $login_posto
			AND    fabrica = $login_fabrica";
	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage ($con);
	header("Location: os_parametros.php");
	exit;
}

// recebe as variaveis
if($_POST['chk_opt1'])  $chk1  = $_POST['chk_opt1']; 
if($_POST['chk_opt2'])  $chk2  = $_POST['chk_opt2']; 
if($_POST['chk_opt3'])  $chk3  = $_POST['chk_opt3']; 
if($_POST['chk_opt4'])  $chk4  = $_POST['chk_opt4']; 
if($_POST['chk_opt5'])  $chk5  = $_POST['chk_opt5']; 
if($_POST['chk_opt6'])  $chk6  = $_POST['chk_opt6']; 
if($_POST['chk_opt7'])  $chk7  = $_POST['chk_opt7']; 
if($_POST['chk_opt8'])  $chk8  = $_POST['chk_opt8']; 
if($_POST['chk_opt9'])  $chk9  = $_POST['chk_opt9']; 
if($_POST['chk_opt10']) $chk10 = $_POST['chk_opt10']; 
if($_POST['chk_opt11']) $chk11 = $_POST['chk_opt11']; 
if($_POST['chk_opt12']) $chk12 = $_POST['chk_opt12']; 
if($_POST['chk_opt13']) $chk13 = $_POST['chk_opt13']; 
if($_POST['chk_opt14']) $chk14 = $_POST['chk_opt14']; 

if($_GET['chk_opt1'])  $chk1  = $_GET['chk_opt1']; 
if($_GET['chk_opt2'])  $chk2  = $_GET['chk_opt2']; 
if($_GET['chk_opt3'])  $chk3  = $_GET['chk_opt3']; 
if($_GET['chk_opt4'])  $chk4  = $_GET['chk_opt4']; 
if($_GET['chk_opt5'])  $chk5  = $_GET['chk_opt5']; 
if($_GET['chk_opt6'])  $chk6  = $_GET['chk_opt6']; 
if($_GET['chk_opt7'])  $chk7  = $_GET['chk_opt7']; 
if($_GET['chk_opt8'])  $chk8  = $_GET['chk_opt8']; 
if($_GET['chk_opt9'])  $chk9  = $_GET['chk_opt9']; 
if($_GET['chk_opt10']) $chk10 = $_GET['chk_opt10']; 
if($_GET['chk_opt11']) $chk11 = $_GET['chk_opt11']; 
if($_GET['chk_opt12']) $chk12 = $_GET['chk_opt12']; 
if($_GET['chk_opt13']) $chk13 = $_GET['chk_opt13']; 
if($_GET['chk_opt14']) $chk14 = $_GET['chk_opt14']; 

if($_POST["data_inicial_01"])		$data_inicial_01    = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_01      = trim($_POST["data_final_01"]);
if($_POST["produto_referencia"])	$produto_referencia = trim($_POST["produto_referencia"]);
if($_POST["produto_nome"])			$produto_nome       = trim($_POST["produto_nome"]);
if($_POST["numero_serie"])			$numero_serie       = trim($_POST["numero_serie"]);
if($_POST["nome_consumidor"])		$nome_consumidor    = trim($_POST["nome_consumidor"]);
if($_POST["cpf_consumidor"])		$cpf_consumidor     = trim($_POST["cpf_consumidor"]);
if($_POST["cidade"])				$cidade             = trim($_POST["cidade"]);
if($_POST["uf"])					$uf                 = trim($_POST["uf"]);
if($_POST["numero_os"])				$numero_os          = trim($_POST["numero_os"]);
if($_POST["numero_nf"])				$numero_nf          = trim($_POST["numero_nf"]);
if($_POST["situacao"])				$situacao           = trim($_POST["situacao"]);

if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01      = trim($_GET["data_final_01"]);
if($_GET["produto_referencia"])		$produto_referencia = trim($_GET["produto_referencia"]);
if($_GET["numero_serie"])			$numero_serie       = trim($_GET["numero_serie"]);
if($_GET["nome_consumidor"])		$nome_consumidor    = trim($_GET["nome_consumidor"]);
if($_GET["cpf_consumidor"])			$cpf_consumidor     = trim($_GET["cpf_consumidor"]);
if($_GET["cidade"])					$cidade             = trim($_GET["cidade"]);
if($_GET["uf"])						$uf                 = trim($_GET["uf"]);
if($_GET["numero_os"])				$numero_os          = trim($_GET["numero_os"]);
if($_GET["numero_nf"])				$numero_nf          = trim($_GET["numero_nf"]);
if($_GET["situacao"])				$situacao           = trim($_GET["situacao"]);

$produto_referencia = str_replace ("." , "" , $produto_referencia);
$produto_referencia = str_replace ("-" , "" , $produto_referencia);
$produto_referencia = str_replace ("/" , "" , $produto_referencia);
$produto_referencia = str_replace (" " , "" , $produto_referencia);


$layout_menu = "os";
$title = "Relação de Ordens de Serviços de Manutenção Lançadas";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.titulo_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

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

	// BTN_NOVA BUSCA
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_parametros.php'><img src='imagens/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
//				tbl_os.serie                                            ,
$sql = "SELECT  lpad (tbl_os.sua_os,10,'0')                  AS ordem   ,
				tbl_os.os                                               ,
				tbl_os.sua_os                                           ,
				to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS data    ,
				to_char (tbl_os.data_abertura ,'DD/MM/YYYY') AS abertura,
				tbl_os.data_fechamento                                  ,
				tbl_os.consumidor_nome                                  ,
				tbl_produto.referencia                                  ,
				tbl_produto.descricao                                   ,
				tbl_os_extra.impressa                                   ,
				(
					SELECT	MAX (tbl_os_item.pedido) AS pedido 
					FROM	tbl_os_produto 
					JOIN	tbl_os_item USING (os_produto) 
					WHERE	tbl_os_produto.os = tbl_os.os
				) AS pedido
		FROM 	tbl_os
		LEFT JOIN tbl_os_extra USING (os)
		LEFT JOIN tbl_produto USING (produto)
		JOIN 	tbl_posto USING (posto)
		JOIN 	tbl_posto_fabrica 	 ON tbl_posto.posto = tbl_posto_fabrica.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_cliente    ON tbl_os.cliente    = tbl_cliente.cliente
		WHERE		tbl_os.fabrica  = $login_fabrica 
		AND         tbl_posto.posto = $login_posto
		AND         (1=2 ";

if(strlen($chk1) > 0){
	//dia atual
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

	$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
	$resX = pg_exec ($con,$sqlX);
	#  $dia_hoje_final = pg_result ($resX,0,0);

	$monta_sql .= " OR (tbl_os.data_digitacao BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " e OS lançadas hoje";

}

if(strlen($chk2) > 0) {
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_os.data_digitacao BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;

	$msg .= " e OS lançadas ontem";

}

if(strlen($chk3) > 0){
	// última semana
	$sqlX = "SELECT to_char (current_date , 'D')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

	$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_os.data_digitacao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e OS lançadas nesta semana";

}

if(strlen($chk4) > 0){
	// do mês
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	$monta_sql .= "OR (tbl_os.data_digitacao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$dt = 1;

	$msg .= " e OS lançadas neste mês ";

}

if(strlen($chk5) > 0){
	// entre datas
	if((strlen($data_inicial_01) == 10) && (strlen($data_final_01) == 10)){
		$data_inicial     = $data_inicial_01;
		$data_final       = $data_final_01;

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

		$monta_sql .= "OR (tbl_os.data_digitacao BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59') ";
		$dt = 1;

	 	$msg .= " e OS lançadas entre os dias $data_inicial_01 e $data_final_01 ";
	}
}

/*if(strlen($chk6) > 0){
	// codigo do posto
	if (strlen($codigo_posto) > 0){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_posto_fabrica.codigo_posto = '". $codigo_posto."' ";
		$dt = 1;

		$msg .= " e OS lançadas pelo posto $nome_posto";

	}
}
*/
if(strlen($chk7) > 0){
	// referencia do produto
	if ($produto_referencia) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_produto.referencia_pesquisa = '".$produto_referencia."' ";
		$dt = 1;

		$msg .= " e OS lançadas contendo o produto  $produto_nome";

	}
}

if(strlen($chk8) > 0){
	// numero de serie do produto
	if ($numero_serie) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_os.serie = '". $numero_serie."' ";
		$dt = 1;

		$msg .= " e OS lançadas contendo produtos com número de série : $numero_serie";

	}
}

if(strlen($chk9) > 0){
	// nome_consumidor
	if ($nome_consumidor){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_os.consumidor_nome ILIKE '%".$nome_consumidor."%' ";
		$dt = 1;

		$msg .= " e OS lançadas para o consumidor $nome_consumidor";

	}
}

if(strlen($chk10) > 0){
	// cpf_consumidor
	if ($cpf_consumidor){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_cliente.cpf ILIKE '%". $cpf_consumidor."' ";
		$dt = 1;

		$msg .= " e OS lançadas para o consumidor com CFP/CNPJ: $cpf_consumidor";

	}
}

if(strlen($chk11) > 0){
	// cidade
	if ($cidade){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_os.consumidor_cidade = '". $cidade."' ";
		$dt = 1;

		$msg .= " e OS lançadas para a cidade $cidade";

	}
}

if(strlen($chk12) > 0){
	// uf
	if ($uf){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_os.consumidor_estado = '".$uf."' ";
		$dt = 1;

		$msg .= " e OS lançadas para o estado $estado";

	}
}

if(strlen($chk13) > 0){
	// numero_os
	if ($numero_os){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		if (strpos($numero_os,"-") === false)
			$monta_sql .= "$xsql (tbl_os.sua_os ilike '%".$numero_os."%' OR tbl_os.os = '$numero_os') ";
		else
			$monta_sql .= "$xsql tbl_os.sua_os ilike '%".$numero_os."%' ";

		$dt = 1;

		$msg .= " e OS lançadas com Nº $numero_os";

	}
}

if(strlen($chk14) > 0){
	// numero_nf
	if ($numero_nf){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_os.nota_fiscal = '".$numero_nf."' ";
		$dt = 1;

		$msg .= " e OS lançadas com Nº NF $numero_nf";

	}
}

if ($situacao){
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql tbl_os.data_fechamento $situacao ";
	$dt = 1;
}

// ordena sql padrao
$sql .= $monta_sql;
$sql .= ") ORDER BY lpad (tbl_os.sua_os,10,'0') DESC , lpad(tbl_os.os,10,'0') DESC";

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//if ($ip == '192.168.0.55') echo "<br>".nl2br($sql)."<br><br>".nl2br($sqlCount)."<br><BR>";

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

	echo "<TR class='titulo_top'>\n";
	echo "<TD colspan=9>$msg</TD>\n";
	echo "</TR>\n";

	echo "<TR class='menu_top'>\n";
	echo "<TD >OS</TD>\n";
	//echo "<TD >SÉRIE</TD>\n";
	//echo "<TD width='075'>DIGITAÇÃO</TD>\n";
	echo "<TD width='075'>ABERTURA</TD>\n";
	echo "<TD>CONSUMIDOR</TD>\n";
	//echo "<TD >PRODUTO</TD>\n";
	echo "<TD width='20'>&nbsp;</TD>\n";
	echo "<TD width='240' colspan='4'>AÇÕES</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res); $i++){
		$os                 = trim(pg_result ($res,$i,os));
		$data               = trim(pg_result ($res,$i,data));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		//$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		//$produto_nome       = trim(pg_result ($res,$i,descricao));
		//$produto_referencia = trim(pg_result ($res,$i,referencia));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$pedido             = trim(pg_result ($res,$i,pedido));
		$impressa           = trim(pg_result ($res,$i,impressa));

		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}

		if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

		echo "<TR class='table_line' style='background-color: $cor;' height='25'>\n";
		echo "<TD nowrap>$sua_os</TD>\n";
		//echo "<TD nowrap>$serie</TD>\n";
		//echo "<TD nowrap align='center'>$data</TD>\n";
		echo "<TD nowrap align='center'>$abertura</TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,36)."</ACRONYM></TD>\n";
		//echo "<TD nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">".substr($produto_nome,0,18)."</ACRONYM></TD>\n";
		echo "<TD aling='center'>";
		if (strlen($impressa) > 0){
			echo"<img src='imagens/img_ok.gif'>";
		}else{
			echo"<img src='imagens/img_impressora.gif'>";
		}
		echo "</TD>\n";
		
		echo "<TD width='57'><a href='os_manutencao.php?os=$os'><img src='imagens/btn_consulta.gif'></a></TD>\n";
		
		echo "<TD width='57'><a href='os_print.php?os=$os";
		if ($login_fabrica == 7) echo "&modo=1";
		echo "' target='_blank'><img src='imagens/btn_imprime.gif'></a></TD>\n";
		echo "<TD width='62' align='center'>";
		echo "<a href='os_filizola_valores.php?sua_os=$sua_os'>Fechar OS</a>";
		echo "</TD>\n";

		if ($login_fabrica == 7) {
			echo "<TD width='56' align='center'>";
			echo "<A HREF='os_matricial.php?os=$os'>Matricial</A>";
			echo "</TD>\n";
		}

		echo "</TR>\n";

	}
}
	echo "</TABLE>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_parametros.php'><img src='imagens/btn_nova_busca.gif'></a>";
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

include "rodape.php"; 

?>