<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


#::::::::::::::::::::::::::::::::::::::::
#header("Location: os_consulta_lite.php");
#exit;
#::::::::::::::::::::::::::::::::::::::::


$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

if($login_fabrica == 1) {
	include("os_consulta_blackedecker.php");
	exit;
}

include 'funcoes.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

/*
$cookget = @explode("?", $REQUEST_URI);	// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookget", $cookget[1]);		// expira qdo fecha o browser
*/

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
if($_POST['chk_opt15']) $chk15 = $_POST['chk_opt15'];

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
if($_GET['chk_opt15']) $chk15 = $_GET['chk_opt15'];

if($_POST["dia_em_aberto"])			$dia_em_aberto      = trim($_POST["dia_em_aberto"]);
if($_POST["data_inicial_01"])		$data_inicial_01    = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_01      = trim($_POST["data_final_01"]);
if($_POST['codigo_posto'])			$codigo_posto       = trim($_POST['codigo_posto']);
if($_POST['uf_posto'])				$uf_posto           = trim($_POST['uf_posto']);
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

if($_GET["dia_em_aberto"])			$dia_em_aberto      = trim($_GET["dia_em_aberto"]);
if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01      = trim($_GET["data_final_01"]);
if($_GET['codigo_posto'])			$codigo_posto       = trim($_GET['codigo_posto']);
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


$layout_menu = "callcenter";
$title = "Rela��o de Ordens de Servi�os Lan�adas";
#$body_onload = "javascript: document.frm_os.condicao.focus()";

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
echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr>";
echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
echo "<td align='left'><font size=1><b>&nbsp; Exclu�das do sistema</b></font></td>";
echo "</tr>";
echo "<tr height='3'><td colspan='2'></td></tr>";
echo "<tr height='16'>";
echo "<td align='center' width='16' bgcolor='#D7FFE1'>&nbsp;</td>";
echo "<td align='left'><font size=1><b>&nbsp; Reincid�ncias</b></font></td>";
echo "</tr>";
echo "<tr height='3'><td colspan='2'></td></tr>";
if ($login_fabrica == 14) {
	echo "<tr height='16'>";
	echo "<td align='center' width='16' bgcolor='#91C8FF'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp; OSs abertas h� mais de 3 dias sem data de fechamento</b></font></td>";
	echo "</tr>";
	echo "<tr height='3'><td colspan='2'></td></tr>";
	echo "<tr height='16'>";
	echo "<td align='center' width='16' bgcolor='#FF0000'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp; OSs abertas h� mais de 5 dias sem data de fechamento</b></font></td>";
	echo "</tr>";
}else{
	echo "<tr height='16'>";
	echo "<td align='center' width='16' bgcolor='#91C8FF'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp; OSs abertas h� mais de 25 dias sem data de fechamento</b></font></td>";
	echo "</tr>";
}
echo "</table>";

echo "<br>";

// BTN_NOVA BUSCA
echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<TR class='table_line'>";
echo "<td align='center' background='#D9E2EF'>";
echo "<a href='os_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
echo "</td>";
echo "</TR>";
echo "</TABLE>";

// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
/*
$sql = "SELECT      lpad (tbl_os.sua_os,10,'0')                  AS ordem   ,
					tbl_os.os                                               ,
					tbl_os.sua_os                                           ,
					to_char (tbl_os.data_digitacao ,'DD/MM/YYYY') AS data    ,
					to_char (tbl_os.data_abertura  ,'DD/MM/YYYY') AS abertura,
					to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
					tbl_os.serie                                            ,
					tbl_posto.nome  AS posto_nome                           ,
					tbl_posto_fabrica.codigo_posto  AS codigo_posto         ,
					tbl_os.consumidor_nome                                  ,
					tbl_os.data_fechamento                                  ,
					tbl_produto.referencia                                  ,
					tbl_produto.descricao                                   ,
					'$login_login' AS login_login
		FROM		tbl_os
		LEFT JOIN	tbl_produto       USING (produto)
		JOIN		tbl_posto         USING (posto)
		JOIN		tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN	tbl_cliente       ON tbl_os.cliente = tbl_cliente.cliente
		WHERE		tbl_os.fabrica = $login_fabrica AND (tbl_os.excluida IS NULL OR tbl_os.excluida IS FALSE) AND (1=2 ";
*/
$sql = "SELECT * FROM (
			(
				SELECT      lpad (tbl_os.sua_os,10,'0')                       AS ordem         ,
							tbl_os.os                                                          ,
							tbl_os.sua_os                                                      ,
							to_char (tbl_os.data_digitacao ,'DD/MM/YYYY')     AS data          ,
							to_char (tbl_os.data_abertura  ,'DD/MM/YYYY')     AS abertura      ,
							to_char (tbl_os.data_fechamento,'DD/MM/YYYY')     AS fechamento    ,
							to_char (tbl_os.finalizada,'DD/MM/YYYY HH24:MI:SS') AS finalizada    ,
							tbl_os.data_digitacao                             AS data_consulta ,
							tbl_os.serie                                                       ,
							tbl_os.excluida                                                    ,
							tbl_posto.nome                                    AS posto_nome    ,
							tbl_posto.estado                                                   ,
							tbl_posto_fabrica.codigo_posto                    AS codigo_posto  ,
							tbl_os.consumidor_nome                                             ,
							tbl_os.data_fechamento                                             ,
							tbl_os.nota_fiscal                                                 ,
							tbl_os.consumidor_cpf                                              ,
							tbl_os.consumidor_cidade                                           ,
							tbl_os.consumidor_estado                                           ,
							tbl_os.consumidor_revenda                                          ,
							tbl_os.revenda_nome                                                ,
							tbl_produto.referencia_pesquisa                   AS referencia    ,
							tbl_produto.descricao                                              ,
							'$login_login'                                    AS login_login   ,
							tbl_os.os_reincidente                             AS os_reincidente
				FROM		tbl_os
				LEFT JOIN	tbl_produto       USING (produto)
				JOIN		tbl_posto         USING (posto)
				JOIN		tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											  AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN	tbl_cliente       ON  tbl_os.cliente            = tbl_cliente.cliente
				WHERE		tbl_os.fabrica = $login_fabrica
			) UNION (
				SELECT      lpad (tbl_os_excluida.sua_os,10,'0')                   AS ordem             ,
							tbl_os_excluida.os                                                          ,
							tbl_os_excluida.sua_os                                                      ,
							to_char (tbl_os_excluida.data_digitacao ,'DD/MM/YYYY') AS data              ,
							to_char (tbl_os_excluida.data_abertura  ,'DD/MM/YYYY') AS abertura          ,
							to_char (tbl_os_excluida.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
							''                                                     AS finalizada        ,
							tbl_os_excluida.data_digitacao                         AS data_consulta     ,
							tbl_os_excluida.serie                                                       ,
							't'                                                    AS excluida          ,
							tbl_posto.nome                                         AS posto_nome        ,
							tbl_posto.estado                                                            ,
							tbl_posto_fabrica.codigo_posto                         AS codigo_posto      ,
							tbl_os_excluida.consumidor_nome                                             ,
							tbl_os_excluida.data_fechamento                                             ,
							tbl_os_excluida.nota_fiscal                                                 ,
							''                                                     AS consumidor_cpf    ,
							''                                                     AS consumidor_cidade ,
							''                                                     AS consumidor_estado ,
							''                                                     AS consumidor_revenda,
							NULL                                                   AS revenda_nome      ,
							tbl_produto.referencia_pesquisa                        AS referencia        ,
							tbl_produto.descricao                                                       ,
							'$login_login'                                         AS login_login       ,
							null                                                   AS os_reincidente
				FROM		tbl_os_excluida
				LEFT JOIN	tbl_produto       USING (produto)
				JOIN		tbl_posto         USING (posto)
				JOIN		tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											  AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE		tbl_os_excluida.fabrica = $login_fabrica
			)
		) AS a
		WHERE (1=2 ";

if ($login_fabrica == 1) {
	if (strlen($numero_os) > 5) {
		$chk6 = '1';
		$codigo_posto = substr($numero_os, 0, strlen($numero_os)-5);
	}
	$numero_os = substr($numero_os, strlen($numero_os)-5, strlen($numero_os));
}

if(strlen($chk1) > 0){
	//dia atual
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

	$dia_hoje_inicio = pg_result ($resX,0,0);
	$dia_hoje_final  = pg_result ($resX,0,0);

	#$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
	#$resX = pg_exec ($con,$sqlX);
	#  $dia_hoje_final = pg_result ($resX,0,0);

	$monta_sql .= " OR (a.data_consulta::date BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " e OS lan�adas hoje";

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

	$msg .= " e OS lan�ados ontem";

}

if(strlen($chk3) > 0){
	// �ltima semana
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

	$msg .= " e OS lan�adas nesta semana";

}

if(strlen($chk4) > 0){
	// do m�s
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	#$monta_sql .= "OR (a.data_consulta BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$monta_sql .= "OR (a.data_consulta::date BETWEEN '$mes_inicial' AND '$mes_final') ";
	$dt = 1;

	$msg .= " e OS lan�adas neste m�s ";

}

if(strlen($chk5) > 0){
	// entre datas
	if((strlen($data_inicial_01) == 10) && (strlen($data_final_01) == 10)){
		$data_inicial     = $data_inicial_01;
		$data_final       = $data_final_01;

/*		$data_inicial = str_replace ("/","",$data_inicial);
		$data_inicial = str_replace ("-","",$data_inicial);
		$data_inicial = str_replace (".","",$data_inicial);
		$data_inicial = str_replace (" ","",$data_inicial);
		$data_inicial = substr ($data_inicial,4,4) . "-" . substr ($data_inicial,2,2) . "-" . substr ($data_inicial,0,2);*/
		$data_inicial = fnc_formata_data_pg ($data_inicial);

/*		$data_final = str_replace ("/","",$data_final);
		$data_final = str_replace ("-","",$data_final);
		$data_final = str_replace (".","",$data_final);
		$data_final = str_replace (" ","",$data_final);
		$data_final = substr ($data_final,4,4) . "-" . substr ($data_final,2,2) . "-" . substr ($data_final,0,2);*/
		$data_final = fnc_formata_data_pg ($data_final);

		#$monta_sql .= "OR (a.data_consulta BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59') ";
		$monta_sql .= "OR (a.data_consulta::date BETWEEN $data_inicial AND $data_final) ";
		$dt = 1;

	 	$msg .= " e OS lan�adas entre os dias $data_inicial_01 e $data_final_01 ";
	}
}

if(strlen($chk6) > 0){

	// codigo do posto
	if (strlen($codigo_posto) > 0){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.codigo_posto = '". $codigo_posto."' ";
		$dt = 1;

		$msg .= " e OS lan�adas pelo posto $nome_posto";

	}

	if (strlen($uf_posto) > 0){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.estado = '". $uf_posto."' ";
		$dt = 1;

		$msg .= " e OS lan�adas pelo posto do estado $uf_posto";

	}
}

if(strlen($chk7) > 0){
	// referencia do produto
	if ($produto_referencia) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.referencia = '".$produto_referencia."' ";
		$dt = 1;

		$msg .= " e OS lan�adas contendo o produto  $produto_referencia";

	}
}

if(strlen($chk8) > 0){
	// numero de serie do produto
	if ($numero_serie) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.serie = '". $numero_serie."' ";
		$dt = 1;

		$msg .= " e OS lan�adas contendo produtos com n�mero de s�rie : $numero_serie";

	}
}

if(strlen($chk9) > 0){
	// nome_consumidor
	if ($nome_consumidor){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.consumidor_nome ILIKE '%".$nome_consumidor."%' ";
		$dt = 1;

		$msg .= " e OS lan�adas para o consumidor $nome_consumidor";

	}
}

if(strlen($chk10) > 0){
	// cpf_consumidor
	if ($cpf_consumidor){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.consumidor_cpf ILIKE '". $cpf_consumidor."%' ";
		$dt = 1;

		$msg .= " e OS lan�adas para o consumidor com CPF/CNPJ: $cpf_consumidor";

	}
}

if(strlen($chk11) > 0){
	// cidade
	if ($cidade){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.consumidor_cidade = '". $cidade."' ";
		$dt = 1;

		$msg .= " e OS lan�adas para a cidade $cidade";

	}
}

if(strlen($chk12) > 0){
	// uf
	if ($uf){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.consumidor_estado = '".$uf."' ";
		$dt = 1;

		$msg .= " e OS lan�adas para o estado $estado";

	}
}

if(strlen($chk13) > 0){
	// numero_os
	if ($numero_os){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.sua_os ilike '%".$numero_os."%' ";
		$dt = 1;

		$msg .= " e OS lan�adas com N� $numero_os";

	}
}

if(strlen($chk14) > 0){
	// numero_nf
	if ($numero_nf){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.nota_fiscal = '".$numero_nf."' ";
		$dt = 1;

		$msg .= " e OS lan�adas com N� NF $numero_nf";

	}
}

if(strlen($chk15) > 0){
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

		$msg .= " e OS lan�adas em aberto no per�odo de $dia_em_aberto dias";
	}
}

if ($situacao){
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql a.data_fechamento $situacao ";
	$dt = 1;
}

if (strlen($consumidor_revenda) > 0 AND ($consumidor_revenda == 'R' OR $consumidor_revenda == 'C')){
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql a.consumidor_revenda = '$consumidor_revenda' ";
	$dt = 1;
	if($consumidor_revenda == 'R') $msg .= " de revendas ";
	if($consumidor_revenda == 'C') $msg .= " de consumidores ";
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
	$sql .= ") ORDER BY $order_by lpad (a.sua_os,10,'0') DESC, lpad (a.os::text,10,'0') DESC";
}else{
	$sql .= ") ORDER BY lpad (a.sua_os,10,'0') DESC, lpad (a.os::text,10,'0') DESC";
}

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//if ($ip == '201.0.9.216') { echo $sql; exit; }

// ##### PAGINACAO ##### //
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 11;				// m�ximo de links � serem exibidos
$max_res   = 30;				// m�ximo de resultados � serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o n�mero de pesquisas (detalhada ou n�o) por p�gina

$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

// ##### PAGINACAO ##### //

if (@pg_numrows($res) == 0) {
	echo "<TABLE width='700' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
}else{
	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	echo "<TR class='menu_top'>\n";
	echo "<TD colspan='10'>$msg</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
/*	echo "<TD><a href='".$REQUEST_URI."&order=os' class='linkTitulo'>OS</a></TD>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=serie' class='linkTitulo'>S�RIE</a></TD>\n";
	echo "<TD width='075'><a href='".$REQUEST_URI."&order=abertura'   class='linkTitulo'>ABERTURA</a></TD>\n";
	echo "<TD width='075'><a href='".$REQUEST_URI."&order=fechamento' class='linkTitulo'>FECHAM.</a></TD>\n";
	echo "<TD width='130'><a href='".$REQUEST_URI."&order=consumidor' class='linkTitulo'>CONSUMIDOR</a></TD>\n";
	echo "<TD width='130'><a href='".$REQUEST_URI."&order=posto' class='linkTitulo'>POSTO</a></TD>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=produto' class='linkTitulo'>PRODUTO</a></TD>\n";*/
	echo "<TD>OS</TD>\n";
	echo "<TD>S�RIE</TD>\n";
	echo "<TD width='075'>ABERTURA</TD>\n";
	echo "<TD width='075'>FECHAM.</TD>\n";
	echo "<TD width='130'>CONSUMIDOR</TD>\n";
	echo "<TD width='130'>REVENDA</TD>\n";
	echo "<TD width='130'>POSTO</TD>\n";
	echo "<TD>PRODUTO</TD>\n";
	echo "<TD width='170' colspan='2'>A��ES</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$os                 = trim(pg_result ($res,$i,os));
		$data               = trim(pg_result ($res,$i,data));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$fechamento         = trim(pg_result ($res,$i,fechamento));
		$finalizada         = trim(pg_result ($res,$i,finalizada));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$revenda_nome       = trim(pg_result ($res,$i,revenda_nome));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$excluida           = trim(pg_result ($res,$i,excluida));
		$os_reincidente     = trim(pg_result ($res,$i,os_reincidente));
		$consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
		
		$cor = "#F7F5F0";
		$btn = "amarelo";
		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}
		
		if ($excluida == "t")       $cor = "#FFE1E1";
		if ($os_reincidente == 't') $cor = "#D7FFE1";
		
		if (strlen($fechamento) == 0 AND $excluida != "t" AND $login_fabrica != 14) {

			$aux_data_abertura = fnc_formata_data_pg($abertura);

			$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '25 days', 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_consultar = pg_result($resX,0,0);

			$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_atual = pg_result ($resX,0,0);
			if ($data_consultar < $data_atual) {
				$cor = "#91C8FF";
			}
		}

		##### CONDI��ES PARA INTELBRAS - IN�CIO #####
		if (strlen($data_fechamento) == 0 AND $excluida != "t" AND $login_fabrica == 14) {
			$aux_data_abertura = fnc_formata_data_pg($abertura);

			$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '3 days', 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_consultar = pg_result($resX,0,0);

			$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_atual = pg_result ($resX,0,0);

			if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) $cor = "#91C8FF";

			$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '5 days', 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_consultar = pg_result($resX,0,0);

			$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_atual = pg_result ($resX,0,0);

			if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) $cor = "#FF0000";
		}
		##### CONDI��ES PARA INTELBRAS - FIM #####

		if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		if ($login_fabrica == 1) echo "<TD nowrap>$codigo_posto$sua_os</TD>\n";
		else echo "<TD nowrap>$sua_os</TD>\n";
		echo "<TD nowrap>$serie</TD>\n";
		//echo "<TD align='center'>$data</TD>\n";
		echo "<TD align='center'>$abertura</TD>\n";
		echo "<TD align='center'><acronym title='Data Fechamento Sistema: $finalizada'>$fechamento</acronym></td>\n";
		if ($consumidor_revenda == "C" && strlen($consumidor_nome) > 0) {
			echo "<td nowrap><acronym title='$consumidor_nome'>" . substr($consumidor_nome,0,15) . "</acronym></td>\n";
			echo "<td nowrap><acronym title='$revenda_nome'>" . substr($revenda_nome,0,15) . "</acronym></td>\n";
		}else{
			echo "<td nowrap colspan='2' align='center'><acronym title='$revenda_nome'>" . substr($revenda_nome,0,30) . "</acronym></td>\n";
		}
		echo "<td nowrap><acronym title='$codigo_posto - $posto_nome'>" . substr($posto_nome,0,15) . "</acronym></td>\n";
		echo "<td nowrap><acronym title='$produto_referencia - $produto_nome'>" . substr($produto_nome,0,17) . "</acronym></td>\n";

		echo "<TD>";
		if ($excluida == "f" OR strlen($excluida) == 0) echo "<a href='os_cadastro.php?os=$os' target='_blank'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a>";
		echo "</TD>\n";

		echo "<TD>";
		if ($excluida == "f" OR strlen($excluida) == 0) echo "<a href='os_press.php?os=$os'><img src='imagens/btn_consultar_".$btn.".gif'></a>";
		echo "</TD>\n";

		echo "</TR>\n";

	}
	echo "</TABLE>\n";
}

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
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

// pega todos os links e define que 'Pr�xima' e 'Anterior' ser�o exibidos como texto plano
$todos_links		= $mult_pag->Construir_Links("strings", "sim");

// fun��o que limita a quantidade de links no rodape
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
	echo " (P�gina <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
	echo "</font>";
	echo "</div>";
}

// ##### PAGINACAO ##### //

echo "<br>";

include "rodape.php"; 

?>