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
		$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
		$res = @pg_exec ($con,$sql);
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
	
	if(strlen($msg_erro)==0){
		$data_inicial   = trim($_GET["data_inicial_01"]);
		$data_final   = trim($_GET["data_final_01"]);
		list($d, $m, $y) = explode("/", $data_inicial);

			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
		
			list($d, $m, $y) = explode("/", $data_final);

			if(!checkdate($m,$d,$y)) $mes_erro = "Data Inválida";
		
			if(strlen($erro)==0){
				$d_ini = explode ("/", $data_inicial);//tira a barra
				$data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


				$d_fim = explode ("/", $data_final);//tira a barra
				$data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

				if($data_final < $data_inicial){
					$msg_erro = "Data Inválida.";
				}

			}
		
		$aux_data_inicial = mktime(0,0,0,$d_ini[1],$d_ini[0],$d_ini[2]); // timestamp da data inicial
		$aux_data_final = mktime(0,0,0,$d_fim[1],$d_fim[0],$d_fim[2]); // timestamp da data final
		
		if(strlen($msg_erro)==0){
                        if (strtotime($data_inicial.'+1 month') < strtotime($data_final) ) {
                            $msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês';
                        }
                }
	}
	
}

$layout_menu = "callcenter";
$title = "RELAÇÃO DE ORDENS DE SERVIÇO LANÇADAS DO TIPO CORTESIA";

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
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}


table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
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

echo "<table width='700' align='center' height=16 border='0' cellspacing='0' cellpadding='0' background='#FFE1E1'>";
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
$sql = "SELECT DISTINCT
				A.os_revenda         ,
				A.sua_os             ,
				A.tipo_os            ,
				A.tipo_atendimento   ,
				A.explodida          ,
				A.abertura           ,
				A.digitacao          ,
				A.data_fechamento    ,
				A.excluida           ,
				A.serie              ,
				A.codigo_posto       ,
				A.nome_posto         ,
				A.produto_referencia ,
				A.produto_descricao  ,
				A.impressa           ,
				A.extrato            ,
				A.tipo_os_cortesia   ,
				A.admin_nome         ,
				A.consumidor_nome    ,
				A.consumidor_revenda ,
				A.qtde_item          ,
				SUBSTRING(A.sua_os,1,5) as sub_sua_os 
				FROM (
				(
					SELECT  DISTINCT
							tbl_os_revenda.os_revenda                                                ,
							tbl_os_revenda.sua_os                                                    ,
							tbl_os_revenda.tipo_os                                                   ,
							tbl_os_revenda.tipo_atendimento                                          ,
							tbl_os_revenda.explodida                                                 ,
							TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura           ,
							to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS digitacao          ,
							current_date                                       AS data_fechamento    ,
							false                                               AS excluida           ,
							null     as    serie                                         ,
							tbl_posto_fabrica.codigo_posto                                           ,
							tbl_posto.nome                                     AS nome_posto                                    ,
							null                                       AS produto_referencia ,
							null                              AS produto_descricao  ,
							current_date                                       AS impressa           ,
							0                                                  AS extrato            ,
							tbl_os_revenda.tipo_os_cortesia                                          ,
							tbl_admin.login                                    AS admin_nome         ,
							null                                               AS consumidor_nome    ,
							null                                               AS consumidor_revenda ,
							0                                                  AS qtde_item
					FROM      tbl_os_revenda
					JOIN      tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
					JOIN tbl_posto                ON  tbl_posto.posto                = tbl_os_revenda.posto
					JOIN tbl_posto_fabrica        ON  tbl_posto_fabrica.posto        = tbl_posto.posto  AND tbl_posto_fabrica.fabrica      = $login_fabrica
					JOIN tbl_admin on tbl_os_revenda.admin = tbl_admin.admin
					WHERE tbl_os_revenda.fabrica = $login_fabrica 
					AND	  tbl_os_revenda.cortesia IS TRUE ";
$monta_sql = "";
if(strlen($chk1) > 0){
	//dia atual
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

// 	$dia_hoje_inicio = pg_result ($resX,0,0);
// 	$dia_hoje_final  = pg_result ($resX,0,0);

	$monta_sql .= " AND (tbl_os_revenda.digitacao::date BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
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

	$monta_sql .=" AND (tbl_os_revenda.digitacao::date BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
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

	$monta_sql .=" AND (tbl_os_revenda.digitacao::date BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e OS lançadas nesta semana";
}

if(strlen($chk4) > 0){
	// do mês
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	#$monta_sql .= "OR (a.data_consulta BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$monta_sql .= " AND (tbl_os_revenda.digitacao::date BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
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

		$monta_sql .= " AND (tbl_os_revenda.digitacao::date < '$dia_inicial' AND data_fechamento IS NULL) ";
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

		$monta_sql .= " AND (tbl_os_revenda.digitacao::date BETWEEN $data_inicial AND $data_final) ";
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
		$monta_sql .= " and tbl_os_revenda.posto=$posto";
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
		$monta_sql .= " and tbl_os_revenda_item.serie = '". $numero_serie."' ";
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
		$monta_sql .= " and  1=1";
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
		$monta_sql .= " and 1=1";
		$dt = 1;

		$msg .= " e OS lançadas para o consumidor com CPF/CNPJ: $cpf_consumidor";
	}
}

if(strlen($chk12) > 0){
	// numero_os
	if ($login_fabrica == 1) {
		$conteudo = explode("-", $numero_os);
		$os_numero    = $conteudo[0];
		$os_sequencia = $conteudo[1];
		$os_numero1 = substr($os_numero, strlen($os_numero)-5, strlen($os_numero));
		$os_numero2 = substr($os_numero, strlen($os_numero)-6, strlen($os_numero));
	}
	if ($os_numero){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and (tbl_os_revenda.sua_os= '$os_numero2' or tbl_os_revenda.sua_os = '$os_numero1' OR ";
					for ($x=1;$x<=40;$x++) {
						$monta_sql .= " tbl_os_revenda.sua_os = '$os_numero1-$x' OR ";
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
		$monta_sql .= " and tbl_os_revenda_item.nota_fiscal = '".$numero_nf."' ";
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
		$monta_sql .= " and tbl_os_revenda.tipo_os_cortesia = '".$tipo_os_cortesia."' ";
		$dt = 1;

		$msg .= " e OS lançadas com Tipo OS Cortesia $tipo_os_cortesia";
	}
}

if ($situacao){
	/*if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
*/
	$monta_sql .= " and 1=1 ";
	$dt = 1;
}
$sql .= $monta_sql;

				$sql .= " ) UNION (
					SELECT  tbl_os.os                                  AS os_revenda         ,
							tbl_os.sua_os                                                    ,
							tbl_os.tipo_os                                                   ,
							tbl_os.tipo_atendimento                                          ,
							NULL                                        AS explodida         ,
							TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS abertura          ,
							TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao         ,
							tbl_os.data_fechamento                                           ,
							tbl_os.excluida                                                  ,
							tbl_os.serie                                                     ,
							tbl_posto_fabrica.codigo_posto                                   ,
							tbl_posto.nome as posto                                          ,
							tbl_produto.referencia                     AS produto_referencia ,
							tbl_produto.descricao                      AS produto_descricao  ,
							tbl_os_extra.impressa                                            ,
							tbl_os_extra.extrato                                             ,
							tbl_os.tipo_os_cortesia                                          ,
							tbl_admin.login as admin_nome                                    ,
							tbl_os.consumidor_nome                                           ,
							tbl_os.consumidor_revenda                                        ,
							(
								SELECT COUNT(tbl_os_item.*) AS qtde_item
								FROM   tbl_os_item
								JOIN   tbl_os_produto USING (os_produto)
								WHERE  tbl_os_produto.os = tbl_os.os
							)                                          AS qtde_item
					FROM tbl_os
					JOIN tbl_os_extra       ON  tbl_os_extra.os           = tbl_os.os
					JOIN tbl_produto        ON  tbl_produto.produto       = tbl_os.produto
					JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
					JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_admin on tbl_os.admin=tbl_admin.admin
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.cortesia is true ";
// ordena sql padrao
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

}

if(strlen($chk4) > 0){
	// do mês
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	#$monta_sql .= "OR (a.data_consulta BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$monta_sql .= " AND (tbl_os.data_digitacao::date BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
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

		$monta_sql .= " AND (tbl_os.data_digitacao::date < '$dia_inicial' AND data_fechamento IS NULL) ";
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

		$monta_sql .= " AND (tbl_os.data_digitacao::date BETWEEN $data_inicial AND $data_final) ";
		$dt = 1;

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

	}
}

if(strlen($chk10) > 0){
	// nome_consumidor
	if ($nome_consumidor){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and tbl_os.consumidor_nome LIKE '".$nome_consumidor."%' ";
		$dt = 1;

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
	}
}

if(strlen($chk12) > 0){
	// numero_os
	if ($login_fabrica == 1) {
		$conteudo = explode("-", $numero_os);
		$os_numero    = $conteudo[0];
		$os_sequencia = $conteudo[1];
		$os_numero1 = substr($os_numero, strlen($os_numero)-5, strlen($os_numero));
		$os_numero2 = substr($os_numero, strlen($os_numero)-6, strlen($os_numero));
	}
	if ($numero_os){
	/*	if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";
*/
		$monta_sql .= " and (tbl_os.os_numero='$os_numero2' or tbl_os.sua_os = '$os_numero1' OR ";
					for ($x=1;$x<=40;$x++) {
						$monta_sql .= " tbl_os.sua_os = '$os_numero1-$x' OR ";
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
		$monta_sql .= " and tbl_os.nota_fiscal = '".$numero_nf."' ";
		$dt = 1;
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

	}
}

if ($situacao){
	/*if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
*/
	$monta_sql .= " and tbl_os.data_fechamento $situacao ";
	$dt = 1;
}
$sql .= " $monta_sql )
		) AS A
	WHERE (1=1 ) ORDER BY SUBSTRING(A.sua_os,1,5) ASC, A.os_revenda ASC ";


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
//	$sql .= " ORDER BY $order_by lpad (a.sua_os,10,'0') DESC, lpad (a.os,10,'0') DESC";
}else{
	//$sql .= " ORDER BY a.codigo_posto || lpad (a.sua_os,5,'0') ASC, lpad (a.sua_os,10,'0') DESC, lpad (a.os,10,'0') DESC";
}


$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//echo "<br>".$sql."<br>";
//exit;
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
	echo "<TABLE width='700' height='50' align='center'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
}else{
	echo "<TABLE width='100%' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>\n";

	echo "<TR class='titulo_coluna'>\n";
	echo "<TD colspan=13>$msg</TD>\n";
	echo "</TR>\n";
	echo "<TR class='titulo_coluna'>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=os' class='linkTitulo'>OS</a></TD>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=serie' class='linkTitulo'>Série</a></TD>\n";
	echo "<TD width='075'><a href='".$REQUEST_URI."&order=abertura'   class='linkTitulo'>Abertura</a></TD>\n";
	echo "<TD width='075'><a href='".$REQUEST_URI."&order=fechamento' class='linkTitulo'>Fechamento</a></TD>\n";
	echo "<TD width='130'><a href='".$REQUEST_URI."&order=consumidor' class='linkTitulo'>Admin</a></TD>\n";
	echo "<TD width='130'><a href='".$REQUEST_URI."&order=consumidor' class='linkTitulo'>Consumidor</a></TD>\n";
	echo "<TD width='130'><a href='".$REQUEST_URI."&order=posto' class='linkTitulo'>Posto</a></TD>\n";
	echo "<TD><a href='".$REQUEST_URI."&order=produto' class='linkTitulo'>Produto</a></TD>\n";
	echo "<TD NOWRAP><a href='".$REQUEST_URI."&order=tipo_cortesia' class='linkTitulo'>Tipo OS Cortesia</a></TD>\n";
	echo "<TD width='170' colspan='4' align='center'>Ações</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$os                 = trim(pg_result ($res,$i,os_revenda));
		$data               = trim(pg_result ($res,$i,digitacao));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$posto_nome         = trim(pg_result ($res,$i,nome_posto));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$produto_nome       = trim(pg_result ($res,$i,produto_descricao));
		$produto_referencia = trim(pg_result ($res,$i,produto_referencia));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$excluida           = trim(pg_result ($res,$i,excluida));
		$tipo_os_cortesia   = trim(pg_result ($res,$i,tipo_os_cortesia));
		$tipo_os            = trim(pg_result ($res,$i,tipo_os));
		$tipo_atendimento   = trim(pg_result ($res,$i,tipo_atendimento));
		$admin_nome         = trim(pg_result ($res,$i,admin_nome));
		$explodida          = trim(pg_result ($res,$i,explodida));
		$consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));

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
		echo "<TD align='center' nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">".$tipo_os_cortesia."</ACRONYM>";

		if($tipo_os == 13) {
			$sqlt = " SELECT descricao
							FROM tbl_tipo_atendimento
						WHERE tipo_atendimento = $tipo_atendimento ";
			$rest = @pg_query($con,$sqlt);
			if(pg_num_rows($res) > 0){
				echo "<br/>(".@pg_fetch_result($rest,0,0). ")" ;
			}
		}
		echo "</TD>\n";

		echo "<TD>";
		#HD 257239
		if((strlen($explodida) == 0 and strlen($consumidor_revenda)==0)){
			echo "<input type='button' value='Alterar' width='60' height='10' onclick=\"javascript:window.location='os_revenda_cortesia.php?os_revenda=$os'\" target='_blank'>";	
		}elseif(strlen($consumidor_revenda)==0 and strlen($explodida) >0){
			echo "";
		}elseif (($excluida == "f" OR strlen($excluida) == 0)) {
			echo "<input type='button' value='Alterar' width='60' height='10' onclick=\"javascript:window.location='os_cortesia_cadastro.php?os=$os'\" target='_blank'>";
		}

		echo "</TD>\n";

		echo "<TD>";
		if(strlen($consumidor_revenda)==0 and ($excluida == "f" OR strlen($excluida) == 0)){
			echo  "<input type='button' value='Imprimir' width='60' height='10' onclick=\"javascript:window.location='os_revenda_print.php?os_revenda=$os'\" target='_blank'>";
			
		}elseif ($excluida == "f" OR strlen($excluida) == 0) {
			echo "<input type='button' value='Consultar' width='60' height='10' onclick=\"javascript:window.location='os_press.php?os=$os'\">";
		}
		echo "</TD>\n";

		echo "<TD>";
		if (strlen($data_fechamento) == 0 AND ( $excluida == "f" OR strlen($excluida) == 0 ) ) echo "<input type='button' value='Excluir' width='60' height='10' onclick='javascript:Excluir($os)'>";
		echo "</TD>\n";

		echo "<TD>";
		if (strlen($consumidor_revenda) ==0 and strlen($explodida) == 0){ 
		echo "<input type='button' value='Explodir' width='60' height='10' onclick=\"javascript:window.location='os_revenda_finalizada.php?os_revenda=$os&btn_acao=explodir'\" >";
		}else{
			echo "&nbsp;";
		}
		echo "</td>";
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
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='msg_erro'>";
	echo "<td>";
	echo "$msg_erro";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";


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
