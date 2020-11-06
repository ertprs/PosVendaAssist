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
	header("Location: sedex_parametros.php");
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

if($_GET['chk_opt1'])  $chk1  = $_GET['chk_opt1']; 
if($_GET['chk_opt2'])  $chk2  = $_GET['chk_opt2']; 
if($_GET['chk_opt3'])  $chk3  = $_GET['chk_opt3']; 
if($_GET['chk_opt4'])  $chk4  = $_GET['chk_opt4']; 
if($_GET['chk_opt5'])  $chk5  = $_GET['chk_opt5']; 
if($_GET['chk_opt6'])  $chk6  = $_GET['chk_opt6']; 
if($_GET['chk_opt7'])  $chk7  = $_GET['chk_opt7']; 
if($_GET['chk_opt8'])  $chk8  = $_GET['chk_opt8']; 

if($_POST["data_inicial_01"])		$data_inicial_01      = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_01        = trim($_POST["data_final_01"]);
if($_POST["posto_origem"])			$posto_origem_codigo  = trim($_POST["posto_origem"]);
if($_POST["nome_posto_origem"])		$posto_origem_nome    = trim($_POST["nome_posto_origem"]);
if($_POST["posto_destino"])			$posto_destino_codigo = trim($_POST["posto_destino"]);
if($_POST["nome_posto_destino"])	$posto_destino_nome   = trim($_POST["nome_posto_destino"]);
if($_POST["numero_os"])				$numero_os            = trim($_POST["numero_os"]);

if($_GET["data_inicial_01"])		$data_inicial_01      = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01        = trim($_GET["data_final_01"]);
if($_GET["posto_origem"])			$posto_origem_codigo  = trim($_GET["posto_origem"]);
if($_GET["nome_posto_origem"])		$posto_origem_nome    = trim($_GET["nome_posto_origem"]);
if($_GET["posto_destino"])			$posto_destino_codigo = trim($_GET["posto_destino"]);
if($_GET["nome_posto_destino"])		$posto_destino_nome   = trim($_GET["nome_posto_destino"]);
if($_GET["numero_os"])				$numero_os            = trim($_GET["numero_os"]);

$layout_menu = "os";
$title = "Relação de OS de Sedex Lançadas";
#$body_onload = "javascript: document.frm_os.condicao.focus()";

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
	font-size: 10px;
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
	echo "<a href='sedex_parametros.php'><img src='imagens/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
$sql = "SELECT  tbl_os_sedex.os_sedex                                      ,
				tbl_os_sedex.sua_os_origem                                 ,
				tbl_os_sedex.sua_os_destino                                ,
				to_char (tbl_os_sedex.data_digitacao,'DD/MM/YYYY') AS data ,
				tbl_os_sedex.finalizada                                    ,
				tbl_os_sedex.extrato                                       ,
				posto_origem.codigo_posto  AS posto_origem                 ,
				posto_destino.codigo_posto AS posto_destino                ,
				dados_posto_origem.nome    AS nome_origem                  ,
				dados_posto_destino.nome   AS nome_destino                 ,
				tbl_admin.login                                            
		FROM 	tbl_os_sedex
		JOIN	tbl_admin USING (admin)
		JOIN	tbl_posto_fabrica AS posto_origem  ON tbl_os_sedex.posto_origem  = posto_origem.posto  AND posto_origem.fabrica  = $login_fabrica
		JOIN	tbl_posto_fabrica AS posto_destino ON tbl_os_sedex.posto_destino = posto_destino.posto AND posto_destino.fabrica = $login_fabrica
		JOIN	tbl_posto AS dados_posto_origem    ON tbl_os_sedex.posto_origem  = dados_posto_origem.posto
		JOIN	tbl_posto AS dados_posto_destino   ON tbl_os_sedex.posto_destino = dados_posto_destino.posto
		WHERE		tbl_os_sedex.fabrica = $login_fabrica 
		AND         (tbl_os_sedex.posto_origem = $login_posto OR tbl_os_sedex.posto_destino = $login_posto)
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

	$monta_sql .= " OR (tbl_os_sedex.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " e OS Sedex lançadas hoje";
}

if(strlen($chk2) > 0) {
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_os_sedex.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;

	$msg .= " e OS Sedex lançadas ontem";

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

	$monta_sql .=" OR (tbl_os_sedex.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e OS Sedex lançadas nesta semana";

}

if(strlen($chk4) > 0){
	// do mês
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	$monta_sql .= "OR (tbl_os_sedex.data BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$dt = 1;

	$msg .= " e OS Sedex lançadas neste mês ";

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

		$monta_sql .= "OR (tbl_os_sedex.data BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59') ";
		$dt = 1;

	 	$msg .= " e OS Sedex lançadas entre os dias $data_inicial_01 e $data_final_01 ";
	}
}

if(strlen($chk6) > 0){
	// referencia do produto
	if (strlen($posto_origem_codigo) > 0){
		$sqlZ = "SELECT	tbl_posto.posto,
						tbl_posto.nome 
				FROM	tbl_posto
				JOIN	tbl_posto_fabrica USING(posto)
				WHERE	tbl_posto_fabrica.codigo_posto = $posto_origem_codigo";
		$resZ = pg_exec ($con,$sqlZ);

		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_os_sedex.posto_origem = '".pg_result($resZ,0,0)."' ";
		$dt = 1;

		$msg .= " e Posto Origem ".pg_result($resZ,0,1);

	}
}

if(strlen($chk7) > 0){
	// referencia do produto
	if (strlen($posto_destino_codigo) > 0){
		$sqlZ = "SELECT	tbl_posto.posto,
						tbl_posto.nome 
				FROM	tbl_posto
				JOIN	tbl_posto_fabrica USING(posto)
				WHERE	tbl_posto_fabrica.codigo_posto = $posto_destino_codigo";
		$resZ = pg_exec ($con,$sqlZ);

		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_os_sedex.posto_destino = '".pg_result($resZ,0,posto)."' ";
		$dt = 1;

		$msg .= " e Posto Destino ".pg_result($resZ,0,nome);

	}
}

if(strlen($chk8) > 0){
	// numero_os
	if ($numero_os){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		if (strpos($numero_os,"-") === false)
			$monta_sql .= "$xsql tbl_os_sedex.sua_os_destino ILIKE '%".$numero_os."%' ";
		else
			$monta_sql .= "$xsql tbl_os_sedex.sua_os_destino ILIKE '%".$numero_os."%' ";

		$xnumero_os = substr($numero_os,strlen($numero_os) - 5,strlen($numero_os));
		$monta_sql .= "$xsql tbl_os_sedex.os_sedex = '".intval($xnumero_os)."' ";

		$dt = 1;

		$msg .= " e OS Sedex lançadas com Nº $numero_os";

	}
}

// ordena sql padrao
$sql .= $monta_sql;
$sql .= ") ORDER BY lpad (tbl_os_sedex.sua_os_destino,10,'0') DESC ";

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//if ($ip == '201.0.9.216') echo "<br>".nl2br($sql)."<br><br>".nl2br($sqlCount)."<br><BR>";

// ##### PAGINACAO ##### //
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 11;				// máximo de links à serem exibidos
$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página
//echo $sql;
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
	echo "<TD>ABERTURA</TD>\n";
	echo "<TD>POSTO ORIGEM</TD>\n";
#	echo "<TD>OS</TD>\n";
	echo "<TD>POSTO DESTINO</TD>\n";
	echo "<TD>OS ORIGEM</TD>\n";
	echo "<TD>OS DESTINO</TD>\n";
	echo "<TD>SOLICITANTE</TD>\n";
	echo "<TD>SITUAÇÃO</TD>\n";
	echo "<TD>&nbsp;</TD>\n";
	echo "<TD>&nbsp;</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res); $i++){
		$os_sedex             = trim(pg_result($res,$i,os_sedex));
		$sua_os_origem        = trim(pg_result($res,$i,sua_os_origem));
		$sua_os_destino       = trim(pg_result($res,$i,sua_os_destino));
		$data                 = trim(pg_result($res,$i,data));
		$posto_origem_codigo  = trim(pg_result($res,$i,posto_origem));
		$posto_destino_codigo = trim(pg_result($res,$i,posto_destino));
		$posto_origem_nome    = trim(pg_result($res,$i,nome_origem));
		$posto_destino_nome   = trim(pg_result($res,$i,nome_destino));
		$finalizada           = trim(pg_result($res,$i,finalizada));
		$extrato              = trim(pg_result($res,$i,extrato));
		$solicitante          = trim(pg_result($res,$i,login));

		$xos_sedex = "00000".$os_sedex;
		$xos_sedex = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
		$xos_sedex = $posto_origem_codigo.$xos_sedex;

		if(strlen($extrato) > 0){
			$status = "Extrato";
		}elseif(strlen($finalizada) > 0){
			$status = "Finalizada";
		}else{
			$status = "Nova";
		}

		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}

		if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

		if($sua_os_destino == 'CR'){
			$sql2   = "SELECT sua_os FROM tbl_os WHERE os = '$sua_os_origem' AND tbl_os.fabrica = '$login_fabrica'; ";
			$res2   = pg_exec($con, $sql2);
			$cr_sua_os = pg_result($res2, 0, sua_os);
			$cr_sua_os = $posto_origem_codigo.$cr_sua_os;
		}

		echo "<TR class='table_line' bgcolor='$cor' height='25'>\n";
		echo "<TD nowrap align='center'>$data</TD>\n";
		echo "<TD nowrap align='center'><ACRONYM TITLE=\"$posto_origem_nome\">$posto_origem_codigo</ACRONYM></TD>\n";
#		echo "<TD nowrap align='center'>$sua_os_origem</TD>\n";
		echo "<TD nowrap align='center'><ACRONYM TITLE=\"$posto_destino_nome\">$posto_destino_codigo</ACRONYM></TD>\n";
		if($sua_os_destino == 'CR'){
			echo "<TD nowrap align='center'>$cr_sua_os</TD>\n";
		}else{
			echo "<TD nowrap align='center'>$xos_sedex</TD>\n";
		}
		echo "<TD nowrap align='center'>$sua_os_destino</TD>\n";
		echo "<TD nowrap align='center'>$solicitante</TD>\n";
		echo "<TD nowrap align='center'>$status</TD>\n";
		if($login_fabrica == 1 AND $sua_os_destino == 'CR'){
			$sql_sedex = "SELECT sua_os_origem FROM tbl_os_sedex WHERE fabrica = $login_fabrica and os_sedex = $os_sedex ";
			$res_sedex = pg_exec($con,$sql_sedex);
			$sua_os_sedex = pg_result($res_sedex,0,0);
			echo "<TD width='57'><a href='carta_registrada.php?os=$sua_os_sedex'><img src='imagens/btn_consulta.gif'></a></TD>\n";
		}else{
			echo "<TD width='57'><a href='sedex_cadastro_complemento.php?os_sedex=$os_sedex'><img src='imagens/btn_consulta.gif'></a></TD>\n";
		}
		echo "<TD width='57'><a href='sedex_print.php?os_sedex=$os_sedex' target=\"_blank\"><img src='imagens/btn_imprime.gif'></a></TD>\n";
		echo "</TR>\n";

	}
}
	echo "</TABLE>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='sedex_parametros.php'><img src='imagens/btn_nova_busca.gif'></a>";
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