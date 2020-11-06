<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$btn_acao = trim(strtolower($_POST['btn_acao']));
if (strlen($_GET['btn_acao']) > 0) $btn_acao = trim(strtolower($_GET['btn_acao']));

if ($btn_acao == 'excluir'){

	if (strlen($_GET['os']) > 0) $os = trim($_GET['os']);

	if (strlen($os) > 0){
		$sql = "select * from tbl_os_status where os=$os;";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$sql = "DELETE FROM tbl_os_status where os=$os";
			$res = pg_exec($con,$sql);
		}

		$sql = "DELETE FROM tbl_os 
				WHERE  os              = $os
				AND    cortesia        IS TRUE
				AND    fabrica         = $login_fabrica 
				AND    data_fechamento ISNULL";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		//echo $sql;
	}
	header("Location: os_cortesia_parametros.php");
	exit;
}

$msg_erro = "";

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
if($_POST["numero_os"])				$numero_os          = trim($_POST["numero_os"]);
if($_POST["numero_nf"])				$numero_nf          = trim($_POST["numero_nf"]);
if($_POST["tipo_os_cortesia"])		$tipo_os_cortesia   = trim($_POST["tipo_os_cortesia"]);
if($_POST["situacao"])				$situacao           = trim($_POST["situacao"]);

if($_GET["dia_em_aberto"])			$dia_em_aberto      = trim($_GET["dia_em_aberto"]);
if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01      = trim($_GET["data_final_01"]);
if($_GET['codigo_posto'])			$codigo_posto       = trim($_GET['codigo_posto']);
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

if(strlen($codigo_posto)==0 AND strlen($chk1) == 0 AND strlen($chk2) == 0 AND strlen($chk3) == 0 AND strlen($chk4) == 0 AND strlen($chk5) == 0) $msg_erro = "Selecione o posto<br>";
else {
	IF(strlen($chk1) == 0 AND strlen($chk2) == 0 AND strlen($chk3) == 0 AND strlen($chk4) == 0 AND strlen($chk5) == 0){
		$ysql = "Select posto from tbl_posto_fabrica where codigo_posto='$codigo_posto' and fabrica=$login_fabrica";
		$yres = pg_exec($con,$ysql);
		if(pg_numrows($yres)==0) $msg_erro = "Escolha um posto válido";
		else $posto = pg_result($yres,0,0);
	}

}

if(strlen($chk6) > 0){
	if ($_GET["data_inicial_01"] == 'dd/mm/aaaa') $msg_erro .= "Favor informar a data inicial para pesquisa<br>";
	if ($_GET["data_final_01"] == 'dd/mm/aaaa')   $msg_erro .= "Favor informar a data final para pesquisa<br>";

	//FAZ TODAS AS VERIFICAÇÕES NA DATA INICIAL
	if (strlen($msg_erro) == 0) {
		$data_inicial   = trim($_GET["data_inicial_01"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro .= pg_errormessage ($con) ;
		}

		if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);

	}

	//FAZ TODAS VERIFICAÇÕES NA DATA FINAL
	if (strlen($msg_erro) == 0) {
		if (strlen($_GET["data_final_01"]) == 0) $msg_erro .= "Favor informar a data final para pesquisa<br>";
		if (strlen($msg_erro) == 0) {
			$data_final   = trim($_GET["data_final_01"]);
			$fnc          = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con) ;
			}
			
			if (strlen($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);

		}
	}
	//VERIFICA SE AS DATAS SÃO MAIORES QUE 30 DIAS
	if(strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
		$sql = "select '$aux_data_final'::date - '$aux_data_inicial'::date ";
		$res = pg_exec($con,$sql);
		if(pg_result($res,0,0)>30)$msg_erro .= "Período não pode ser maior que 30 dias";

	}

}
$layout_menu = "callcenter";
$title = "Relação de Ordens de Serviços Lançadas do Tipo Cortesia";

include "cabecalho.php";

?>

<SCRIPT LANGUAGE="JavaScript">
<!--
function Excluir(os){
	if (confirm('Deseja realmente excluir esse registro?') == true){
		window.location = '<? echo $PHP_SELF; ?>?btn_acao=excluir&os='+os;
	}

}
//-->
</SCRIPT>

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
if(strlen($msg_erro)==0){

echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' background='#FFE1E1'>";
echo "<tr>";
echo "<td align='right'><font size=1><b>OS's marcadas com a cor &nbsp;</b></font></td>";
echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
echo "<td align='left'><font size=1><b>&nbsp; foram excluídas do sistema.</b></font></td>";
echo "</tr>";
echo "</table>";

echo "<br>";

// BTN_NOVA BUSCA
echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<TR class='table_line'>";
echo "<td align='center' background='#D9E2EF'>";
echo "<a href='os_cortesia_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
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
							tbl_posto_fabrica.codigo_posto                AS codigo_posto  ,
							tbl_posto.nome                                AS posto_nome    ,
							tbl_posto.estado                                               ,
							tbl_os.consumidor_nome                                         ,
							tbl_os.data_fechamento                                         ,
							tbl_os.nota_fiscal                                             ,
							tbl_os.consumidor_cpf                                          ,
							tbl_os.consumidor_cidade                                       ,
							tbl_os.consumidor_estado                                       ,
							tbl_os.tipo_os_cortesia                                        ,
							tbl_os.cortesia                                                ,
							tbl_produto.referencia_pesquisa               AS referencia    ,
							tbl_produto.descricao                                          ,
							'$login_login' AS login_login                                  ,
							tbl_admin.login                               AS admin_nome
				FROM		tbl_os
				JOIN		tbl_posto         USING (posto)
				JOIN		tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN		tbl_admin         ON tbl_admin.admin = tbl_os.admin 
				LEFT JOIN	tbl_produto       ON tbl_os.produto = tbl_produto.produto
				LEFT JOIN	tbl_cliente       ON tbl_os.cliente = tbl_cliente.cliente
				WHERE		tbl_os.fabrica = $login_fabrica
				AND			tbl_os.cortesia IS TRUE ";
$monta_sql = "";
if(strlen($chk1) > 0){
	//dia atual
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

// 	$dia_hoje_inicio = pg_result ($resX,0,0);
// 	$dia_hoje_final  = pg_result ($resX,0,0);

	$monta_sql .= " AND (tbl_os.data_digitacao::date BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " e OS lançadas hoje";
}

if(strlen($chk2) > 0) {
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

// 	$dia_ontem_inicial = pg_result ($resX,0,0);
// 	$dia_ontem_final   = pg_result ($resX,0,0);

	$monta_sql .=" AND (tbl_os.data_digitacao::date BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
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

// 	$dia_semana_inicial = pg_result ($resX,0,0);

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

// 	$dia_semana_final = pg_result ($resX,0,0);

	$monta_sql .=" AND (tbl_os.data_digitacao::date BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e OS lançadas nesta semana";
}

if(strlen($chk4) > 0){
	// do mês
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	#$monta_sql .= "OR (a.data_consulta BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$monta_sql .= " AND (tbl_os.data_digitacao::date BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
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

		$monta_sql .= " AND (tbl_os.data_digitacao::date < '$dia_inicial' AND data_fechamento IS NULL) ";
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

		$monta_sql .= " AND (tbl_os.data_digitacao::date BETWEEN $data_inicial AND $data_final) ";
		$dt = 1;

		$msg .= " e OS lançadas entre os dias $data_inicial_01 e $data_final_01 ";
	}
}

//if(strlen($chk7) > 0){
	// codigo do posto
	if (strlen($codigo_posto) > 0){
		/*if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$ysql = "Select posto from tbl_posto_fabrica where codigo_posto='$codigo_posto' and fabrica=$login_fabrica";
		$yres = pg_exec($con,$ysql);
		$posto = pg_result($yres,0,0);
		$monta_sql .= " and tbl_os.posto=$posto";
		$dt = 1;

		$msg .= " e OS lançadas pelo posto $nome_posto";
	}
//}

if(strlen($chk8) > 0){
	// referencia do produto
	if ($produto_referencia) {
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_produto.referencia = '".$produto_referencia."' ";
		$dt = 1;

		$msg .= " e OS lançadas contendo o produto  $produto_referencia";
	}
}

if(strlen($chk9) > 0){
	// numero de serie do produto
	if ($numero_serie) {
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_os.serie = '". $numero_serie."' ";
		$dt = 1;

		$msg .= " e OS lançadas contendo produtos com número de série : $numero_serie";
	}
}

if(strlen($chk10) > 0){
	// nome_consumidor
	if ($nome_consumidor){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_os.consumidor_nome LIKE '%".$nome_consumidor."%' ";
		$dt = 1;

		$msg .= " e OS lançadas para o consumidor $nome_consumidor";
	}
}

if(strlen($chk11) > 0){
	// cpf_consumidor
	if ($cpf_consumidor){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_os.consumidor_cpf LIKE '". $cpf_consumidor."%' ";
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
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and (tbl_os.sua_os = '$numero_os' OR ";
					for ($x=1;$x<=40;$x++) {
						$monta_sql .= " tbl_os.sua_os = '$numero_os-$x' OR ";
					}
		$monta_sql .= " 1=2) ";
		$dt = 1;

		$msg .= " e OS lançadas com Nº $numero_os";
	}
}

if(strlen($chk13) > 0){
	// numero_nf
	if ($numero_nf){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_os.nota_fiscal = '".$numero_nf."' ";
		$dt = 1;

		$msg .= " e OS lançadas com Nº da NF $numero_nf";
	}
}

if(strlen($chk14) > 0){
	// tipo_os_cortesia
	if ($tipo_os_cortesia){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_os.tipo_os_cortesia = '".$tipo_os_cortesia."' ";
		$dt = 1;

		$msg .= " e OS lançadas com Tipo OS Cortesia $tipo_os_cortesia";
	}
}

if ($situacao){
	/*if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
*/
	$monta_sql .= " and tbl_os.data_fechamento $situacao ";
	$dt = 1;
}


$sql .= $monta_sql;


	$sql .=" ) UNION (
				SELECT      lpad (tbl_os_excluida.sua_os,10,'0')                   AS ordem             ,
							tbl_os_excluida.os                                                          ,
							tbl_os_excluida.sua_os                                                      ,
							to_char (tbl_os_excluida.data_digitacao ,'DD/MM/YYYY') AS data              ,
							to_char (tbl_os_excluida.data_abertura  ,'DD/MM/YYYY') AS abertura          ,
							to_char (tbl_os_excluida.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
							tbl_os_excluida.data_digitacao                         AS data_consulta     ,
							tbl_os_excluida.serie                                                       ,
							't'                                                    AS excluida          ,
							tbl_posto_fabrica.codigo_posto                         AS codigo_posto      ,
							tbl_posto.nome                                         AS posto_nome        ,
							tbl_posto.estado                                                            ,
							tbl_os_excluida.consumidor_nome                                             ,
							tbl_os_excluida.data_fechamento                                             ,
							tbl_os_excluida.nota_fiscal                                                 ,
							''                                                     AS consumidor_cpf    ,
							''                                                     AS consumidor_cidade ,
							''                                                     AS consumidor_estado ,
							''                                                     AS tipo_os_cortesia  ,
							'f'                                                    AS cortesia          ,
							tbl_produto.referencia_pesquisa                        AS referencia        ,
							tbl_produto.descricao                                                       ,
							'$login_login'                                         AS login_login       ,
							''                                                     AS admin_nome
				FROM		tbl_os_excluida
				JOIN		tbl_posto         USING (posto)
				JOIN		tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN	tbl_produto       ON tbl_os_excluida.produto = tbl_produto.produto
				WHERE		tbl_os_excluida.fabrica = $login_fabrica 
				AND         tbl_os_excluida.fabrica <> 1 ";
$monta_sql = "";
if(strlen($chk1) > 0){
	//dia atual
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

/*	$dia_hoje_inicio = pg_result ($resX,0,0);
 	$dia_hoje_final  = pg_result ($resX,0,0);*/

	$monta_sql .= " and ( tbl_os_excluida.data_digitacao::date BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;


}

if(strlen($chk2) > 0) {
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

// 	$dia_ontem_inicial = pg_result ($resX,0,0);
// 	$dia_ontem_final   = pg_result ($resX,0,0);

	$monta_sql .=" and ( tbl_os_excluida.data_digitacao::date BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;


}

if(strlen($chk3) > 0){
	// última semana
	$sqlX = "SELECT to_char (current_date , 'D')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

	$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

// 	$dia_semana_inicial = pg_result ($resX,0,0);

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

// 	$dia_semana_final = pg_result ($resX,0,0);

	$monta_sql .=" and ( tbl_os_excluida.data_digitacao::date BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;


}

if(strlen($chk4) > 0){
	// do mês
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	#$monta_sql .= "OR (a.data_consulta BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$monta_sql .= " and ( tbl_os_excluida.data_digitacao::date BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$dt = 1;


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

		$monta_sql .= " and ( tbl_os_excluida.data_digitacao::date < '$dia_inicial' AND data_fechamento IS NULL) ";
		$dt = 1;

	
	}
}

if(strlen($chk6) > 0){
	// entre datas
	if((strlen($data_inicial_01) == 10) && (strlen($data_final_01) == 10)){
		$data_inicial     = $data_inicial_01;
		$data_final       = $data_final_01;
		$data_inicial = fnc_formata_data_pg ($data_inicial);

		$data_final = fnc_formata_data_pg ($data_final);

		$monta_sql .= " and ( tbl_os_excluida.data_digitacao::date BETWEEN $data_inicial AND $data_final) ";
		$dt = 1;


	}
}

if(strlen($chk7) > 0 ){
	// codigo do posto
	if (strlen($codigo_posto) > 0){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$ysql = "Select posto from tbl_posto_fabrica where codigo_posto='$codigo_posto' and fabrica=$login_fabrica";
		$yres = pg_exec($con,$ysql);
		$posto = pg_result($yres,0,0);
		$monta_sql .= " and tbl_os_excluida.posto=$posto";
	//	$monta_sql .= " and tbl_os_excluida.codigo_posto = '". $codigo_posto."' ";
		$dt = 1;


	}
}

if(strlen($chk8) > 0){
	// referencia do produto
	if ($produto_referencia) {
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_produto.referencia = '".$produto_referencia."' ";
		$dt = 1;


	}
}

if(strlen($chk9) > 0){
	// numero de serie do produto
	if ($numero_serie) {
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_os_excluida.serie = '". $numero_serie."' ";
		$dt = 1;


	}
}

if(strlen($chk10) > 0){
	// nome_consumidor
	if ($nome_consumidor){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_os_excluida.consumidor_nome ILIKE '%".$nome_consumidor."%' ";
		$dt = 1;


	}
}


if(strlen($chk12) > 0){
	// numero_os
	if ($login_fabrica == 1) {
		$numero_os = substr($numero_os, strlen($numero_os)-5, strlen($numero_os));
	}
	if ($numero_os){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/

		$monta_sql .= " and (tbl_os_excluida.sua_os = '$numero_os' OR ";
					for ($x=1;$x<=40;$x++) {
						$monta_sql .= " tbl_os_excluida.sua_os = '$numero_os-$x' OR ";
					}
		$monta_sql .= " 1=2) ";


		$dt = 1;


	}
}

if(strlen($chk13) > 0){
	// numero_nf
	if ($numero_nf){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_os_excluida.nota_fiscal = '".$numero_nf."' ";
		$dt = 1;

		
	}
}

// if(strlen($chk14) > 0){
// 	// tipo_os_cortesia
// 	if ($tipo_os_cortesia){
// 	/*	if ($dt == 1) $xsql = "AND ";
// 		else          $xsql = "OR ";
// */
// 		$monta_sql .= "$xsql a.tipo_os_cortesia = '".$tipo_os_cortesia."' ";
// 		$dt = 1;
// 
// 		$msg .= " e OS lançadas com Tipo OS Cortesia $tipo_os_cortesia";
// 	}
// }

if ($situacao){
	/*if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
*/
	$monta_sql .= " and tbl_os_excluida.data_fechamento $situacao ";
	$dt = 1;
}

//$monta_sql .= " AND a.cortesia IS TRUE ";


// ordena sql padrao
$sql .= $monta_sql;
if (strlen($_GET['order']) > 0){
	switch ($_GET['order']){
		case 'os':         $order_by = "a.codigo_posto || a.sua_os ASC, "; break;
		//case 'serie':      $order_by = "tbl_os.serie DESC,"; break;
		//case 'abertura':   $order_by = "tbl_os.data_abertura DESC,"; break;
		//case 'fechamento': $order_by = "tbl_os.data_fechamento DESC,"; break;
		//case 'consumidor': $order_by = "tbl_os.consumidor_nome ASC, tbl_posto.nome ASC,"; break;
		case 'posto':      $order_by = "a.codigo_posto ASC,"; break;
		//case 'produto':    $order_by = "tbl_produto.descricao ASC,"; break;
		//case 'tipo_cortesia': $order_by = "tbl_os.tipo_os_cortesia ASC,"; break;
	}
	$sql .= ") ) as a ORDER BY $order_by lpad (a.sua_os,10,'0') DESC, lpad (a.os,10,'0') DESC";
}else{
	$sql .= ") )as  a ORDER BY a.codigo_posto || lpad (a.sua_os,5,'0') ASC, lpad (a.sua_os,10,'0') DESC, lpad (a.os,10,'0') DESC";
}

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

if ($ip == '201.43.201.204') echo "<br>".$sql."<br>";

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
	echo "<TD colspan=11>$msg</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=os' class='linkTitulo'>OS</a></TD>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=serie' class='linkTitulo'>SÉRIE</a></TD>\n";
	echo "<TD width='075'><a href='".$REQUEST_URI."&order=abertura'   class='linkTitulo'>ABERTURA</a></TD>\n";
	echo "<TD width='075'><a href='".$REQUEST_URI."&order=fechamento' class='linkTitulo'>FECHAM.</a></TD>\n";
	echo "<TD width='130'><a href='".$REQUEST_URI."&order=consumidor' class='linkTitulo'>ADMIN</a></TD>\n";
	echo "<TD width='130'><a href='".$REQUEST_URI."&order=consumidor' class='linkTitulo'>CONSUMIDOR</a></TD>\n";
	echo "<TD width='130'><a href='".$REQUEST_URI."&order=posto' class='linkTitulo'>POSTO</a></TD>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=produto' class='linkTitulo'>PRODUTO</a></TD>\n";
	echo "<TD NOWRAP><a href='".$REQUEST_URI."&order=tipo_cortesia' class='linkTitulo'>TIPO OS CORTESIA</a></TD>\n";
	echo "<TD width='170' colspan='2'>AÇÕES</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$os                 = trim(pg_result ($res,$i,os));
		$data               = trim(pg_result ($res,$i,data));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$fechamento         = trim(pg_result ($res,$i,fechamento));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$excluida           = trim(pg_result ($res,$i,excluida));
		$tipo_os_cortesia   = trim(pg_result ($res,$i,tipo_os_cortesia));
		$admin_nome         = trim(pg_result ($res,$i,admin_nome));

		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}
		
		if ($excluida == "t") $cor = "#FFE1E1";

		if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		if ($login_fabrica == 1) echo "<TD nowrap>$codigo_posto$sua_os</TD>\n";
		else echo "<TD nowrap>$sua_os</TD>\n";
		echo "<TD nowrap>$serie</TD>\n";
		//echo "<TD align='center'>$data</TD>\n";
		echo "<TD align='center'>$abertura</TD>\n";
		echo "<TD align='center'>$fechamento</TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$admin_nome\">".substr($admin_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$codigo_posto - $posto_nome\">".substr($posto_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">".substr($produto_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD align='center' nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">".$tipo_os_cortesia."</ACRONYM></TD>\n";

		echo "<TD>";
		if ($excluida == "f" OR strlen($excluida) == 0) echo "<a href='os_cortesia_cadastro.php?os=$os' target='_blank'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a>";
		echo "</TD>\n";

		echo "<TD>";
		if ($excluida == "f" OR strlen($excluida) == 0) echo "<a href='os_press.php?os=$os'><img src='imagens/btn_consultar_".$btn.".gif'></a>";
		echo "</TD>\n";

		echo "<TD>";
		if (strlen($data_fechamento) == 0 AND ( $excluida == "f" OR strlen($excluida) == 0 ) ) echo "<a href='javascript:Excluir($os)'><img src='imagens/btn_excluir.gif'></a>";
		echo "</TD>\n";

		echo "</TR>\n";

	}
}
	echo "</TABLE>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_cortesia_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
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
}else{
	echo "<center><font color='#990000' size='4'>$msg_erro</font></center>";
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_cortesia_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";
}
include "rodape.php"; 

?>