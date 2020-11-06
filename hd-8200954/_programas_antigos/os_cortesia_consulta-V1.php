<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$btn_acao = trim(strtolower($_POST['btn_acao']));

$msg_erro = "";

$os = $_GET['excluir'];

if (strlen ($os) > 0) {
	$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		header("Location: os_parametros.php");
		exit;
	}
}

// recebe as variaveis
if($_POST['chk_opt1'])  $chk1  = $_POST['chk_opt1'];
if($_POST['chk_opt2'])  $chk2  = $_POST['chk_opt2'];
if($_POST['chk_opt3'])  $chk3  = $_POST['chk_opt3'];
if($_POST['chk_opt4'])  $chk4  = $_POST['chk_opt4'];
if($_POST['chk_opt5'])  $chk5  = $_POST['chk_opt5'];
if($_POST['chk_opt6'])  $chk6  = $_POST['chk_opt6'];
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
if($_GET['chk_opt8'])  $chk8  = $_GET['chk_opt8'];
if($_GET['chk_opt9'])  $chk9  = $_GET['chk_opt9'];
if($_GET['chk_opt10']) $chk10 = $_GET['chk_opt10'];
if($_GET['chk_opt11']) $chk11 = $_GET['chk_opt11'];
if($_GET['chk_opt12']) $chk12 = $_GET['chk_opt12'];
if($_GET['chk_opt13']) $chk13 = $_GET['chk_opt13'];
if($_GET['chk_opt14']) $chk14 = $_GET['chk_opt14'];

if($_POST["dia_em_aberto"])			$dia_em_aberto      = trim($_POST["dia_em_aberto"]);
if($_POST["data_inicial_01"])		$data_inicial_01    = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_01      = trim($_POST["data_final_01"]);
if($_POST["produto_referencia"])	$produto_referencia = trim($_POST["produto_referencia"]);
if($_POST["produto_nome"])			$produto_nome       = trim($_POST["produto_nome"]);
if($_POST["numero_serie"])			$numero_serie       = trim($_POST["numero_serie"]);
if($_POST["nome_consumidor"])		$nome_consumidor    = trim($_POST["nome_consumidor"]);
if($_POST["cpf_consumidor"])		$cpf_consumidor     = trim($_POST["cpf_consumidor"]);
if($_POST["numero_os"])				$numero_os          = trim($_POST["numero_os"]);
if($_POST["numero_nf"])				$numero_nf          = trim($_POST["numero_nf"]);
if($_POST["tipo_os_cortesia"])		$tipo_os_cortesia   = trim($_POST["tipo_os_cortesia"]);
if($_POST["situacao"])				$situacao           = trim($_POST["situacao"]);

if($_GET["dia_em_aberto"])			$dia_em_aberto      = trim($_GET["dia_em_aberto"]);
if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01      = trim($_GET["data_final_01"]);
if($_GET["produto_referencia"])		$produto_referencia = trim($_GET["produto_referencia"]);
if($_GET["numero_serie"])			$numero_serie       = trim($_GET["numero_serie"]);
if($_GET["nome_consumidor"])		$nome_consumidor    = trim($_GET["nome_consumidor"]);
if($_GET["cpf_consumidor"])			$cpf_consumidor     = trim($_GET["cpf_consumidor"]);
if($_GET["numero_os"])				$numero_os          = trim($_GET["numero_os"]);
if($_GET["numero_nf"])				$numero_nf          = trim($_GET["numero_nf"]);
if($_GET["tipo_os_cortesia"])		$tipo_os_cortesia   = trim($_GET["tipo_os_cortesia"]);
if($_GET["situacao"])				$situacao           = trim($_GET["situacao"]);

$produto_referencia = str_replace ("." , "" , $produto_referencia);
$produto_referencia = str_replace ("-" , "" , $produto_referencia);
$produto_referencia = str_replace ("/" , "" , $produto_referencia);
$produto_referencia = str_replace (" " , "" , $produto_referencia);


$layout_menu = "os";
$title = "Relação de Ordens de Serviços Lançadas do Tipo Cortesia";

include "cabecalho.php";
include "funcoes.php";
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
echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
echo "<tr>";
echo "<td align='center' width='10' bgcolor='#FFE1E1'>&nbsp;</td>";
echo "<td align='left'><font size=1>&nbsp; Excluídas do sistema</font></td>";
echo "</tr>";
echo "<tr>";
echo "<td align='center' width='10' bgcolor='#91C8FF'>&nbsp;</td>";
echo "<td align='left'><font size=1>&nbsp; OSs sem fechamento há mais de 20 dias, informar \"Motivo\"</font></td>";
echo "</tr>";
if ($login_fabrica == 1){
	echo "<tr>";
	echo "<td align='center' width='10' bgcolor='#FFCC66'>&nbsp;</td>";
	echo "<td align='left'><font size=1>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</font></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center' width='10' bgcolor='#FF0000'>&nbsp;</td>";
	echo "<td align='left'><font size=1>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar \"Motivo\"</font></td>";
	echo "</tr>";
}
echo "</table>";

echo "<br>";

// BTN_NOVA BUSCA
echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<TR class='table_line'>";
echo "<td align='center' background='#D9E2EF'>";
echo "<a href='os_cortesia_parametros.php'><img src='imagens/btn_nova_busca.gif'></a>";
echo "</td>";
echo "</TR>";
echo "</TABLE>";

// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
$sql = "SELECT * FROM (
			(
				SELECT      lpad (tbl_os.sua_os,10,'0')                   AS ordem         ,
							tbl_os.os                                                      ,
							tbl_os.sua_os                                                  ,
							to_char (tbl_os.data_digitacao ,'DD/MM/YYYY') AS data          ,
							to_char (tbl_os.data_abertura  ,'DD/MM/YYYY') AS abertura      ,
							to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento    ,
							tbl_os.data_digitacao                         AS data_consulta ,
							tbl_os.serie                                                   ,
							tbl_os.excluida                                                ,
							tbl_os.consumidor_nome                                         ,
							tbl_os.data_fechamento                                         ,
							tbl_os.nota_fiscal                                             ,
							tbl_os.consumidor_cpf                                          ,
							tbl_os.tipo_os_cortesia                                        ,
							tbl_os.cortesia                                                ,
							tbl_produto.referencia_pesquisa               AS referencia    ,
							tbl_produto.descricao                                          ,
							tbl_posto_fabrica.codigo_posto                                 ,
							'$login_login' AS login_login                                  ,
							tbl_os_extra.impressa                                          ,
							tbl_os.motivo_atraso                                           
				FROM		tbl_os
				LEFT JOIN	tbl_os_extra USING (os)
				LEFT JOIN	tbl_produto       USING (produto)
				JOIN		tbl_posto         USING (posto)
				JOIN		tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN	tbl_cliente       ON tbl_os.cliente = tbl_cliente.cliente
				WHERE		tbl_os.fabrica = $login_fabrica
				AND         tbl_posto.posto = $login_posto
				AND			tbl_os.cortesia IS TRUE
			) UNION (
				SELECT      lpad (tbl_os_excluida.sua_os,10,'0')                   AS ordem             ,
							tbl_os_excluida.os                                                          ,
							tbl_os_excluida.sua_os                                                      ,
							to_char (tbl_os_excluida.data_digitacao ,'DD/MM/YYYY') AS data              ,
							to_char (tbl_os_excluida.data_abertura  ,'DD/MM/YYYY') AS abertura          ,
							to_char (tbl_os_excluida.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
							tbl_os_excluida.data_digitacao                         AS data_consulta     ,
							tbl_os_excluida.serie                                                       ,
							't'                                                    AS excluida          ,
							tbl_os_excluida.consumidor_nome                                             ,
							tbl_os_excluida.data_fechamento                                             ,
							tbl_os_excluida.nota_fiscal                                                 ,
							''                                                     AS consumidor_cpf    ,
							''                                                     AS tipo_os_cortesia  ,
							'f'                                                    AS cortesia          ,
							tbl_produto.referencia_pesquisa                        AS referencia        ,
							tbl_produto.descricao                                                       ,
							tbl_posto_fabrica.codigo_posto                                              ,
							'$login_login'                                         AS login_login       ,
							tbl_os_extra.impressa                                                       ,
							NULL                                                   AS motivo_atraso     
				FROM		tbl_os_excluida
				LEFT JOIN	tbl_os_extra USING (os)
				LEFT JOIN	tbl_produto       USING (produto)
				JOIN		tbl_posto         USING (posto)
				JOIN		tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE		tbl_os_excluida.fabrica = $login_fabrica
				AND         tbl_posto.posto = $login_posto
			)
		) AS a
		WHERE (1=2 ";

if(strlen($chk1) > 0){
	//dia atual
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

	$dia_hoje_inicio = pg_result ($resX,0,0);
	$dia_hoje_final  = pg_result ($resX,0,0);

	$monta_sql .= " OR (a.data_consulta::date BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " e OS lançadas hoje";
}

if(strlen($chk2) > 0) {
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

	$dia_ontem_inicial = pg_result ($resX,0,0);
	$dia_ontem_final   = pg_result ($resX,0,0);

	$monta_sql .=" OR (a.data_consulta::date BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;

	$msg .= " e OS lançados ontem";
}

if(strlen($chk3) > 0){
	// última semana
	$sqlX = "SELECT to_char (current_date , 'D')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

	$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

	$dia_semana_inicial = pg_result ($resX,0,0);

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

	$dia_semana_final = pg_result ($resX,0,0);

	$monta_sql .=" OR (a.data_consulta::date BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e OS lançadas nesta semana";
}

if(strlen($chk4) > 0){
	// do mês
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	#$monta_sql .= "OR (a.data_consulta BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$monta_sql .= "OR (a.data_consulta::date BETWEEN '$mes_inicial' AND '$mes_final') ";
	$dt = 1;

	$msg .= " e OS lançadas neste mês ";
}

if(strlen($chk5) > 0){
	// dia em aberto
	if (strlen($dia_em_aberto) > 0) {
		if (strlen($dia_em_aberto) == 1) $dia_em_aberto = "0".$dia_em_aberto;

		$dia_hoje = date("Y-m-d");

		$sqlX = "SELECT to_char ('$dia_hoje'::date - INTERVAL '$dia_em_aberto days', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_inicial = pg_result($resX,0,0)." 00:00:00";

		$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$dia_final = pg_result($resX,0,0) . " 23:59:59";

		$data_inicial = fnc_formata_data_pg ($dia_inicial);
		$data_final   = fnc_formata_data_pg ($dia_final);

		$monta_sql .= "OR (a.data_consulta::date < '$dia_inicial' AND data_fechamento IS NULL) ";
		$dt = 1;

		$msg .= " e OS lançadas em aberto no período de $dia_em_aberto dias";
	}
}

if(strlen($chk6) > 0){
	// entre datas
	if((strlen($data_inicial_01) == 10) && (strlen($data_final_01) == 10)){
		$data_inicial     = $data_inicial_01;
		$data_final       = $data_final_01;
		$data_inicial = fnc_formata_data_pg ($data_inicial);
		$data_final = fnc_formata_data_pg ($data_final);

		$monta_sql .= "OR (a.data_consulta::date BETWEEN $data_inicial AND $data_final) ";
		$dt = 1;

		$msg .= " e OS lançadas entre os dias $data_inicial_01 e $data_final_01 ";
	}
}

if(strlen($chk8) > 0){
	// referencia do produto
	if ($produto_referencia) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.referencia = '".$produto_referencia."' ";
		$dt = 1;

		$msg .= " e OS lançadas contendo o produto  $produto_referencia";
	}
}

if(strlen($chk9) > 0){
	// numero de serie do produto
	if ($numero_serie) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.serie = '". $numero_serie."' ";
		$dt = 1;

		$msg .= " e OS lançadas contendo produtos com número de série : $numero_serie";
	}
}

if(strlen($chk10) > 0){
	// nome_consumidor
	if ($nome_consumidor){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.consumidor_nome ILIKE '%".$nome_consumidor."%' ";
		$dt = 1;

		$msg .= " e OS lançadas para o consumidor $nome_consumidor";
	}
}

if(strlen($chk11) > 0){
	// cpf_consumidor
	if ($cpf_consumidor){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.consumidor_cpf ILIKE '". $cpf_consumidor."%' ";
		$dt = 1;

		$msg .= " e OS lançadas para o consumidor com CPF/CNPJ: $cpf_consumidor";
	}
}

if(strlen($chk12) > 0){
	// numero_os
	if ($login_fabrica == 1) {
		$numero_os = substr($numero_os, strlen($numero_os)-5, strlen($numero_os));
	}
	if ($numero_os){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.sua_os ilike '%".$numero_os."%' ";
		$dt = 1;

		$msg .= " e OS lançadas com Nº $numero_os";
	}
}

if(strlen($chk13) > 0){
	// numero_nf
	if ($numero_nf){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.nota_fiscal = '".$numero_nf."' ";
		$dt = 1;

		$msg .= " e OS lançadas com Nº da NF $numero_nf";
	}
}

if(strlen($chk14) > 0){
	// tipo_os_cortesia
	if ($tipo_os_cortesia){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.tipo_os_cortesia = '".$tipo_os_cortesia."' ";
		$dt = 1;

		$msg .= " e OS lançadas com Tipo OS Cortesia $tipo_os_cortesia";
	}
}

if ($situacao){
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql a.data_fechamento $situacao ";
	$dt = 1;
}

$monta_sql .= " AND a.cortesia IS TRUE ";

// ordena sql padrao
$sql .= $monta_sql;
if (strlen($_GET['order']) > 0){
	switch ($_GET['order']){
		case 'os':         $order_by = ""; break;
		case 'serie':      $order_by = "tbl_os.serie DESC,"; break;
		case 'abertura':   $order_by = "tbl_os.data_abertura DESC,"; break;
		case 'consumidor': $order_by = "tbl_os.consumidor_nome ASC, tbl_posto.nome ASC,"; break;
		case 'produto':    $order_by = "tbl_produto.descricao ASC,"; break;
		case 'tipo_cortesia': $order_by = "tbl_os.tipo_os_cortesia ASC,"; break;
	}
	$sql .= ") ORDER BY $order_by lpad (a.sua_os,10,'0') DESC, lpad (a.os,10,'0') DESC";
}else{
	$sql .= ") ORDER BY lpad (a.sua_os,10,'0') DESC, lpad (a.os,10,'0') DESC";
}

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//if ($ip == '201.0.9.216') echo "<br>".$sql."<br>";

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
	echo "<TD colspan='11'>$msg</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=os' class='linkTitulo'>OS</a></TD>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=serie' class='linkTitulo'>SÉRIE</a></TD>\n";
	echo "<TD width='075'><a href='".$REQUEST_URI."&order=abertura'   class='linkTitulo'>ABERTURA</a></TD>\n";
#	echo "<TD width='075'><a href='".$REQUEST_URI."&order=fechamento' class='linkTitulo'>FECHAM.</a></TD>\n";
	echo "<TD width='130'><a href='".$REQUEST_URI."&order=consumidor' class='linkTitulo'>CONSUMIDOR</a></TD>\n";
#	echo "<TD width='130'><a href='".$REQUEST_URI."&order=posto' class='linkTitulo'>POSTO</a></TD>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=produto' class='linkTitulo'>PRODUTO</a></TD>\n";
	echo "<TD NOWRAP><a href='".$REQUEST_URI."&order=tipo_cortesia' class='linkTitulo'>TIPO OS CORTESIA</a></TD>\n";
	echo "<TD>&nbsp;</TD>\n";
	echo "<TD colspan='4'>AÇÕES</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$os                 = trim(pg_result ($res,$i,os));
		$data               = trim(pg_result ($res,$i,data));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$fechamento         = trim(pg_result ($res,$i,fechamento));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$excluida           = trim(pg_result ($res,$i,excluida));
		$tipo_os_cortesia   = trim(pg_result ($res,$i,tipo_os_cortesia));
		$impressa           = trim(pg_result ($res,$i,impressa));
		$motivo_atraso      = trim(pg_result ($res,$i,motivo_atraso));

		$cor = "#F7F5F0";
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}

		if ($excluida == "t") $cor = "#FFE1E1";

		// verifica se nao possui itens com 5 dias de lancamento...
		if($login_fabrica == 1){
			$aux_data_abertura = fnc_formata_data_pg($abertura);

			$sqlX = "SELECT to_char (current_date + INTERVAL '5 days', 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_hj_mais_5 = pg_result($resX,0,0);

			$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '5 days', 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_consultar = pg_result($resX,0,0);

			$sql = "SELECT COUNT(tbl_os_item.*) as total_item
					FROM tbl_os_item 
					JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto 
					JOIN tbl_os on tbl_os.os = tbl_os_produto.os 
					WHERE tbl_os.os = $os 
					AND tbl_os.data_abertura::date >= '$data_consultar'";
			$resItem = pg_exec($con,$sql);

			$itens = pg_result ($resItem,0,total_item);

			if ($itens == 0 and $data_consultar > $data_hj_mais_5) $cor = "#FFCC66";

			$mostra_motivo = 2;
		}

		// verifica se está sem fechamento ha 20 dias ou mais da data de abertura...
		if (strlen($data_fechamento) == 0 AND $mostra_motivo == 2 AND $login_fabrica == 1) {
			$aux_data_abertura = fnc_formata_data_pg($abertura);

			$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '20 days', 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_consultar = pg_result($resX,0,0);

			$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_atual = pg_result ($resX,0,0);

			if ($consumidor_revenda <> 'R'){
				if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
					$mostra_motivo = 1;
					$cor = "#91C8FF";
				}
			}
		}

		// Se estiver acima dos 30 dias, nao exibira os botoes...
		if (strlen($data_fechamento) == 0 AND $login_fabrica == 1) {
			$aux_data_abertura = fnc_formata_data_pg($abertura);

			$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '30 days', 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_consultar = pg_result($resX,0,0);

			$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_atual = pg_result ($resX,0,0);

			if ($consumidor_revenda <> 'R'){
				if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
					$mostra_motivo = 1;
					$cor = "#ff0000";
				}
			}
		}
		
		if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		if ($login_fabrica == 1) echo "<TD nowrap>$codigo_posto$sua_os</TD>\n";
		else echo "<TD nowrap>$codigo_posto$sua_os</TD>\n";
		echo "<TD nowrap>$serie</TD>\n";
		echo "<TD align='center'>$abertura</TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">".substr($produto_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD align='center' nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">".$tipo_os_cortesia."</ACRONYM></TD>\n";

		echo "<TD nowrap>";
		if (strlen($impressa) > 0) echo"<img src='imagens/img_ok.gif' alt='OS já foi impressa'>";
		else echo"<img src='imagens/img_impressora.gif' alt='Imprimir OS'>";
		echo "</TD>\n";

		echo "<TD>";
		if ($excluida == "f" OR strlen($excluida) == 0) echo "<a href='os_press.php?os=$os'><img src='imagens/btn_consulta.gif'></a>"; 
		echo "</TD>\n";
		
		echo "<TD>";
		if ($excluida == "f" OR strlen($excluida) == 0) echo "<a href='os_print.php?os=$os' target='_blank'><img src='imagens/btn_imprime.gif'></a>";
		echo "</TD>\n";

		echo "<TD>";
		if (($excluida == "f" OR strlen($excluida) == 0) AND $tipo_os_cortesia == 'Promotor' AND strlen($data_fechamento) == 0)
			echo "<a href='os_cortesia_cadastro.php?os=$os' ><img src='imagens/btn_lanca.gif'></a>";
		if ($mostra_motivo == 1)
			echo "&nbsp; <a href='os_motivo_atraso.php?os=$os'>Motivo</a>";
		echo "</TD>";

		echo "<TD>";
		if (strlen($data_fechamento) == 0 AND strlen($pedido) == 0) { 
			if ($excluida == "f" or strlen($excluida) == 0) {
				echo "<A HREF=\"javascript: if (confirm('Deseja realmente excluir a OS $codigo_posto$sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os' }\"><img src='imagens/btn_excluir.gif'></A>";
			}
		}
		echo "</TD>\n";

		echo "</TR>\n";

	}
}
	echo "</TABLE>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_cortesia_parametros.php'><img src='imagens/btn_nova_busca.gif'></a>";
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