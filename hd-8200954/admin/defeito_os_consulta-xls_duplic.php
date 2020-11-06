<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

//if($login_fabrica<>19)$admin_privilegios="gerencia";

include "autentica_admin.php";

include "funcoes.php";

$msg = "";

if ($_POST["chk_opt1"])  $chk1  = $_POST["chk_opt1"];
if ($_POST["chk_opt2"])  $chk2  = $_POST["chk_opt2"];
if ($_POST["chk_opt3"])  $chk3  = $_POST["chk_opt3"];
if ($_POST["chk_opt4"])  $chk4  = $_POST["chk_opt4"];
if ($_POST["chk_opt5"])  $chk5  = $_POST["chk_opt5"];
if ($_POST["chk_opt6"])  $chk6  = $_POST["chk_opt6"];
if ($_POST["chk_opt7"])  $chk7  = $_POST["chk_opt7"];
if ($_POST["chk_opt8"])  $chk8  = $_POST["chk_opt8"];
if ($_POST["chk_opt9"])  $chk9  = $_POST["chk_opt9"];
if ($_POST["chk_opt10"]) $chk10 = $_POST["chk_opt10"];
if ($_POST["chk_opt11"]) $chk11 = $_POST["chk_opt11"];
if ($_POST["chk_opt12"]) $chk12 = $_POST["chk_opt12"];
if ($_POST["chk_opt13"]) $chk13 = $_POST["chk_opt13"];
if ($_POST["chk_opt14"]) $chk14 = $_POST["chk_opt14"];
if ($_POST["chk_opt15"]) $chk15 = $_POST["chk_opt15"];
if ($_POST["chk_opt16"]) $chk16 = $_POST["chk_opt16"];
if ($_POST["chk_opt17"]) $chk17 = $_POST["chk_opt17"];
if ($_POST["chk_opt18"]) $chk18 = $_POST["chk_opt18"];
if ($_POST["chk_opt19"]) $chk19 = $_POST["chk_opt19"];
if ($_POST["chk_opt21"]) $chk21 = $_POST["chk_opt21"];

if ($_GET["chk_opt1"])  $chk1  = $_GET["chk_opt1"];
if ($_GET["chk_opt2"])  $chk2  = $_GET["chk_opt2"];
if ($_GET["chk_opt3"])  $chk3  = $_GET["chk_opt3"];
if ($_GET["chk_opt4"])  $chk4  = $_GET["chk_opt4"];
if ($_GET["chk_opt5"])  $chk5  = $_GET["chk_opt5"];
if ($_GET["chk_opt6"])  $chk6  = $_GET["chk_opt6"];
if ($_GET["chk_opt7"])  $chk7  = $_GET["chk_opt7"];
if ($_GET["chk_opt8"])  $chk8  = $_GET["chk_opt8"];
if ($_GET["chk_opt9"])  $chk9  = $_GET["chk_opt9"];
if ($_GET["chk_opt10"]) $chk10 = $_GET["chk_opt10"];
if ($_GET["chk_opt11"]) $chk11 = $_GET["chk_opt11"];
if ($_GET["chk_opt12"]) $chk12 = $_GET["chk_opt12"];
if ($_GET["chk_opt13"]) $chk13 = $_GET["chk_opt13"];
if ($_GET["chk_opt14"]) $chk14 = $_GET["chk_opt14"];
if ($_GET["chk_opt15"]) $chk15 = $_GET["chk_opt15"];
if ($_GET["chk_opt16"]) $chk16 = $_GET["chk_opt16"];
if ($_GET["chk_opt17"]) $chk17 = $_GET["chk_opt17"];
if ($_GET["chk_opt18"]) $chk18 = $_GET["chk_opt18"];
if ($_GET["chk_opt19"]) $chk19 = $_GET["chk_opt19"];
if ($_GET["chk_opt21"]) $chk21 = $_GET["chk_opt21"];

if ($_POST["consumidor_revenda"]) $consumidor_revenda = trim($_POST["consumidor_revenda"]);
if ($_POST["situacao"])           $situacao           = trim($_POST["situacao"]);
if ($_POST["dia_em_aberto"])      $dia_em_aberto      = trim($_POST["dia_em_aberto"]);
if ($_POST["data_inicial"])       $data_inicial       = trim($_POST["data_inicial"]);
if ($_POST["data_final"])         $data_final         = trim($_POST["data_final"]);
if ($_POST["codigo_posto"])       $codigo_posto       = trim($_POST["codigo_posto"]);
if ($_POST["nome_posto"])         $nome_posto         = trim($_POST["nome_posto"]);
if ($_POST["estado_posto"])       $estado_posto       = trim($_POST["estado_posto"]);
if ($_POST["produto_referencia"]) $produto_referencia = trim($_POST["produto_referencia"]);
if ($_POST["produto_nome"])       $produto_nome       = trim($_POST["produto_nome"]);
if ($_POST["servico_realizado"])  $servico_realizado  = trim($_POST["servico_realizado"]);
if ($_POST["defeito"])            $defeito            = trim($_POST["defeito"]);
if ($_POST["defeito_reclamado"])  $defeito_reclamado  = trim($_POST["defeito_reclamado"]);
if ($_POST["defeito_constatado"]) $defeito_constatado = trim($_POST["defeito_constatado"]);
if ($_POST["familia"])            $familia            = trim($_POST["familia"]);
if ($_POST["familia_serie"])      $familia_serie      = trim($_POST["familia_serie"]);
if ($_POST["numero_serie"])       $numero_serie       = trim($_POST["numero_serie"]);
if ($_POST["nome_consumidor"])    $nome_consumidor    = trim($_POST["nome_consumidor"]);
if ($_POST["cidade"])             $cidade             = trim($_POST["cidade"]);
if ($_POST["estado"])             $estado             = trim($_POST["estado"]);
if ($_POST["numero_os"])          $numero_os          = trim($_POST["numero_os"]);
if ($_POST["numero_nf"])          $numero_nf          = trim($_POST["numero_nf"]);

# data da aprovação adicionado por Fábio a pedido da Honorato HD 3096 - 13/07/2007
if ($_POST["extrato_data_inicial"]) $extrato_data_inicial = trim($_POST["extrato_data_inicial"]);
if ($_POST["extrato_data_final"])   $extrato_data_final   = trim($_POST["extrato_data_final"]);

if ($_GET["consumidor_revenda"]) $consumidor_revenda = trim($_GET["consumidor_revenda"]);
if ($_GET["situacao"])           $situacao           = trim($_GET["situacao"]);
if ($_GET["dia_em_aberto"])      $dia_em_aberto      = trim($_GET["dia_em_aberto"]);
if ($_GET["data_inicial"])       $data_inicial       = trim($_GET["data_inicial"]);
if ($_GET["data_final"])         $data_final         = trim($_GET["data_final"]);
if ($_GET["codigo_posto"])       $codigo_posto       = trim($_GET["codigo_posto"]);
if ($_GET["nome_posto"])         $nome_posto         = trim($_GET["nome_posto"]);
if ($_GET["estado_posto"])       $estado_posto       = trim($_GET["estado_posto"]);
if ($_GET["produto_referencia"]) $produto_referencia = trim($_GET["produto_referencia"]);
if ($_GET["produto_nome"])       $produto_nome       = trim($_GET["produto_nome"]);
if ($_GET["servico_realizado"])  $servico_realizado  = trim($_GET["servico_realizado"]);
if ($_GET["defeito"])            $defeito            = trim($_GET["defeito"]);
if ($_GET["defeito_reclamado"])  $defeito_reclamado  = trim($_GET["defeito_reclamado"]);
if ($_GET["defeito_constatado"]) $defeito_constatado = trim($_GET["defeito_constatado"]);
if ($_GET["familia"])            $familia            = trim($_GET["familia"]);
if ($_GET["familia_serie"])      $familia_serie      = trim($_GET["familia_serie"]);
if ($_GET["numero_serie"])       $numero_serie       = trim($_GET["numero_serie"]);
if ($_GET["nome_consumidor"])    $nome_consumidor    = trim($_GET["nome_consumidor"]);
if ($_GET["cidade"])             $cidade             = trim($_GET["cidade"]);
if ($_GET["estado"])             $estado             = trim($_GET["estado"]);
if ($_GET["numero_os"])          $numero_os          = trim($_GET["numero_os"]);
if ($_GET["numero_nf"])          $numero_nf          = trim($_GET["numero_nf"]);

# data da aprovação adicionado por Fábio a pedido da Honorato HD 3096 - 13/07/2007
if ($_GET["extrato_data_inicial"]) $extrato_data_inicial = trim($_GET["extrato_data_inicial"]);
if ($_GET["extrato_data_final"])   $extrato_data_final   = trim($_GET["extrato_data_final"]);

if($login_fabrica==19) $layout_menu="callcenter";
else                   $layout_menu = "gerencia";
$title = "Relação de Ordens de Serviços Lançadas";

include "cabecalho.php";
?>

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

<br>

<?
##### BOTÃO NOVA CONSULTA #####
echo "<table width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<tr class='table_line'>";
echo "<td align='center' background='#D9E2EF'>";
echo "<a href='defeito_os_parametros_duplic.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "<br>";

#### WHERE ############
$qtde_chk = 0;
##### OS Lançadas Hoje #####
if (strlen($chk1) > 0) {
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result($resX,0,0) ;
	$dia_hoje_final  = pg_result($resX,0,0) ;

	$dia_hoje_inicio = pg_result($resX,0,0);
	$dia_hoje_final  = pg_result($resX,0,0);

	$monta_sql .= " AND (data_digitacao BETWEEN '$dia_hoje_inicio 00:00:00' AND '$dia_hoje_final 23:59:59') ";
	$monta_sql2 .= " AND (tbl_os_excluida.data_digitacao BETWEEN '$dia_hoje_inicio 00:00:00' AND '$dia_hoje_final 23:59:59') ";
	$dt = 1;

	$msg .= " OS lançadas hoje ";
$qtde_chk++;
}

##### OS Lançadas Ontem #####
if (strlen($chk2) > 0) {
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result($resX,0,0);
	$dia_ontem_final   = pg_result($resX,0,0);

	$dia_ontem_inicial = pg_result($resX,0,0);
	$dia_ontem_final   = pg_result($resX,0,0);

	$monta_sql .=" AND (tbl_os.data_digitacao BETWEEN '$dia_ontem_inicial 00:00:00' AND '$dia_ontem_final 23:59:59') ";
	$monta_sql2 .=" AND (tbl_os_excluida.data_digitacao BETWEEN '$dia_ontem_inicial 00:00:00' AND '$dia_ontem_final 23:59:59') ";

$dt = 1;

	if (strlen($msg) > 0) $msg .= " e ";
	$msg .= " OS lançados ontem ";
$qtde_chk++;
}

##### OS Lançadas Nesta Semana #####
if (strlen($chk3) > 0) {
	$sqlX = "SELECT to_char (current_date , 'D')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

	$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_inicial = pg_result ($resX,0,0);

	$dia_semana_inicial = pg_result ($resX,0,0);

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_final = pg_result ($resX,0,0);

	$dia_semana_final = pg_result ($resX,0,0);

	$monta_sql  .=" AND (tbl_os.data_digitacao BETWEEN '$dia_semana_inicial 00:00:00' AND '$dia_semana_final 23:59:59') ";
	$monta_sql2 .=" AND (tbl_os_excluida.data_digitacao BETWEEN '$dia_semana_inicial 00:00:00' AND '$dia_semana_final 23:59:59') ";

	$dt = 1;

	if (strlen($msg) > 0) $msg .= " e ";
	$msg .= " OS lançadas nesta semana ";
$qtde_chk++;
}

##### OS Lançadas Neste Mês #####
if (strlen($chk4) > 0) {
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	$monta_sql  .= " AND (tbl_os.data_digitacao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$monta_sql2 .= " AND (tbl_os_excluida.data_digitacao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";


	$dt = 1;

	if (strlen($msg) > 0) $msg .= " e ";
	$msg .= " OS lançadas neste mês ";
$qtde_chk++;
}

##### Situação da OS #####
if (strlen($chk5) > 0) {
	if (strlen($dia_em_aberto) > 0) {
		$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_hoje = pg_result($resX,0,0);

		$sqlX = "SELECT to_char ('$dia_hoje'::date - INTERVAL '$dia_em_aberto days', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_aberto = pg_result($resX,0,0);

		$monta_sql  .= " AND (tbl_os.data_digitacao < '$dia_aberto 00:00:00' AND tbl_os.data_fechamento IS NULL) ";
		$monta_sql2 .= " AND (tbl_os_excluida.data_digitacao < '$dia_aberto 00:00:00' AND tbl_os_excluida.data_fechamento IS NULL) ";

		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas em aberto no período de <i>$dia_em_aberto</i> dias ";
	}
$qtde_chk++;
}

##### Entre Datas #####
if (strlen($chk6) > 0) {
	if ((strlen($data_inicial) == 10) AND (strlen($data_final) == 10)) {

		$x_data_inicial = fnc_formata_data_pg($data_inicial);
		$x_data_final = fnc_formata_data_pg($data_final);
		$x_data_inicial = str_replace("'","",$x_data_inicial);
		$x_data_final   = str_replace("'","",$x_data_final);

		$monta_sql .= " AND (tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00'  AND '$x_data_final 23:59:59') ";
		$monta_sql2 .= " AND (tbl_os_excluida.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59') ";

		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas entre os dias <i>$data_inicial</i> e <i>$data_final</i> ";
	}
$qtde_chk++;
}

##### OS aprovadas #####
if (strlen($chk21) > 0) {
	if ((strlen($extrato_data_inicial) == 10) AND (strlen($extrato_data_final) == 10)) {

		$x_extrato_data_inicial		= fnc_formata_data_pg($extrato_data_inicial);
		$x_extrato_data_final		= fnc_formata_data_pg($extrato_data_final);
		$x_extrato_data_inicial		= str_replace("'","",$x_extrato_data_inicial);
		$x_extrato_data_final		= str_replace("'","",$x_extrato_data_final);

		$dt = 1;

		$sqlX =	"SELECT extrato
				FROM    tbl_extrato
				WHERE   fabrica = $login_fabrica
				AND     aprovado BETWEEN '$x_extrato_data_inicial 00:00:00'  AND '$x_extrato_data_final 23:59:59'
				AND liberado IS NOT NULL";
		$resX = pg_exec($con,$sqlX);
		$extratos = array();
		for ($i = 0 ; $i < pg_numrows ($resX) ; $i++){
			array_push($extratos,trim(pg_result ($resX,$i,extrato)));
		}
		if (count($extratos)>0){
			$extratos = implode(",",$extratos);
			$join_extrato .= " JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.extrato IN ($extratos)";
			#$monta_sql .= " AND tbl_os_extra.extrato IN ($extratos)";
		}else{
			$monta_sql .= " AND 1 = 2 ";
		}
		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " Aprovadas entre os dias <i>$extrato_data_inicial</i> e <i>$extrato_data_final</i> ";
	}
	$qtde_chk++;
}


##### Posto #####
if (strlen($chk7) > 0) {
	if (strlen($codigo_posto) > 0) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
		$monta_sql2 .= " $xsql tbl_os_excluida.codigo_posto = '$codigo_posto' ";

		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas pelo posto <i>$nome_posto</i> ";
	}

	if (strlen($uf_posto) > 0) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql upper(estado) = upper('$estado_posto') ";
		$monta_sql2 .= " $xsql upper(estado) = upper('$estado_posto') ";
		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas pelo posto do estado <i>$estado_posto</i> ";
	}
$qtde_chk++;
}

##### Produto #####
if (strlen($chk8) > 0) {
	$x_produto_referencia = str_replace(".", "", $produto_referencia);
	$x_produto_referencia = str_replace("-", "", $x_produto_referencia);
	$x_produto_referencia = str_replace("/", "", $x_produto_referencia);
	$x_produto_referencia = str_replace(" ", "", $x_produto_referencia);

	if ($x_produto_referencia) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql  .= " $xsql upper(referencia) = upper('$x_produto_referencia') ";
		$monta_sql2 .= " $xsql upper(referencia) = upper('$x_produto_referencia') ";
		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas contendo o produto <i>$produto_referencia</i> ";
	}
$qtde_chk++;
}

##### Serviço Realizado #####
if (strlen($chk9) > 0) {
	if ($servico_realizado) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql servico_realizado = '$servico_realizado' ";
		$dt = 1;

		$sqlX =	"SELECT descricao
				FROM    tbl_servico_realizado
				WHERE   fabrica = $login_fabrica
				AND     servico_realizado = $servico_realizado;";
		$resX = pg_exec($con,$sqlX);

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas contendo peças com serviço realizado <i>" . pg_result($resX,0,0) . "</i> ";
	}
$qtde_chk++;
}

##### Defeito em Peça #####
if (strlen($chk10) > 0) {
	if ($defeito) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql defeito = '$defeito' ";
		$dt = 1;

		$sqlX =	"SELECT descricao
				FROM    tbl_defeito
				WHERE   fabrica = $login_fabrica
				AND     defeito = $defeito;";
		$resX = pg_exec($con,$sqlX);

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas contendo peças com defeito <i>" . pg_result($resX,0,0) . "</i> ";
	}
$qtde_chk++;
}

##### Defeito Reclamado #####
if (strlen($chk11) > 0) {
	if ($defeito_reclamado) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql defeito_reclamado = '$defeito_reclamado' ";
		$dt = 1;

		$sqlX =	"SELECT tbl_defeito_reclamado.descricao
				FROM    tbl_defeito_reclamado
				JOIN    tbl_familia USING (familia)
				WHERE   tbl_familia.fabrica = $login_fabrica
				AND     tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado;";
		$resX = pg_exec($con,$sqlX);

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas contendo produtos com defeito reclamado <i>" . pg_result($resX,0,0) . "</i> ";
	}
$qtde_chk++;
}

##### Defeito Constatado #####
if (strlen($chk12) > 0) {
	if ($defeito_constatado) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= "$xsql defeito_constatado = '$defeito_constatado' ";
		$dt = 1;

		$sqlX =	"SELECT descricao
				FROM    tbl_defeito_constatado
				WHERE   defeito_constatado = $defeito_constatado
				AND     fabrica            = $login_fabrica;";
		$resX = pg_exec($con,$sqlX);

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas contendo produtos com defeito constatado <i>" . pg_result($resX,0,0) ."</i> ";
	}
$qtde_chk++;
}

##### Família #####
if (strlen($chk13) > 0) {
	if (strlen($familia) > 0) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql familia = $familia ";
		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas contendo produtos com família ";
	}
$qtde_chk++;
}

##### Número Série #####
if (strlen($chk14) > 0) {
	if ($dt == 1) $xsql = " AND ";
	else          $xsql = " AND ";

	if (strlen($familia_serie) > 0) $x_numero_serie = $familia_serie;

	$x_data = fnc_formata_data_pg($data_inicial);
	if ($x_data != "'aaaa-mm-dd'") {
		$x_data = str_replace("'", "", $x_data);
		$x_data = str_replace("-", "", $x_data);
		$x_numero_serie .= substr($x_data,2,2).substr($x_data,4,2).substr($x_data,6,2);
	}

	$x_numero_serie .= $numero_serie;

	$monta_sql .= " $xsql upper(serie) LIKE upper('%$x_numero_serie%') ";
	$monta_sql2 .= " $xsql upper(serie) LIKE upper('%$x_numero_serie%') ";
	$dt = 1;

	if (strlen($msg) > 0) $msg .= " e ";
	$msg .= " OS lançadas contendo produtos com número de série <i>$numero_serie</i> ";
$qtde_chk++;
}

##### Nome do Consumidor #####
if (strlen($chk15) > 0) {
	if ($nome_consumidor) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql  .= "$xsql upper(consumidor_nome) LIKE upper('%$nome_consumidor%') ";
		$monta_sql2 .= "$xsql upper(consumidor_nome) LIKE upper('%$nome_consumidor%') ";
		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas para o consumidor <i>$nome_consumidor</i>";
$qtde_chk++;
	}
}

##### CPF/CNPJ do Consumidor #####
if (strlen($chk16) > 0) {
	$x_cpf_consumidor = str_replace(".", "", $cpf_consumidor);
	$x_cpf_consumidor = str_replace("-", "", $x_cpf_consumidor);
	$x_cpf_consumidor = str_replace("/", "", $x_cpf_consumidor);
	$x_cpf_consumidor = str_replace(" ", "", $x_cpf_consumidor);

	if ($cpf_consumidor) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql consumidor_cpf LIKE '%$x_cpf_consumidor%' ";
		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas para o consumidor com CPF/CNPJ <i>$cpf_consumidor</i>";
	}
$qtde_chk++;
}

##### Cidade #####
if (strlen($chk17) > 0) {
	if ($cidade) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql upper(consumidor_cidade) = upper('$cidade') ";
		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas para a cidade <i>$cidade</i>";
	}
$qtde_chk++;
}

##### Estado #####
if (strlen($chk18) > 0) {
	if ($estado) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql upper(consumidor_estado) = upper('$estado') ";
		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas para o estado <i>$estado</i>";
	}
$qtde_chk++;
}

##### Número da OS #####
if (strlen($chk19) > 0) {
	if ($numero_os) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql (tbl_os.sua_os = '$numero_os' OR
								tbl_os.sua_os = '0$numero_os' OR
								tbl_os.sua_os = '00$numero_os' OR
								tbl_os.sua_os = '000$numero_os' OR
								tbl_os.sua_os = '0000$numero_os' OR
								tbl_os.sua_os = '00000$numero_os' OR
								tbl_os.sua_os = '000000$numero_os' OR
								tbl_os.sua_os = '0000000$numero_os' OR
								tbl_os.sua_os = '00000000$numero_os' OR
								tbl_os.sua_os = '000000000$numero_os' OR
								tbl_os.sua_os = '0000000000$numero_os' OR
								tbl_os.sua_os = '$numero_os-01' OR
								tbl_os.sua_os = '$numero_os-02' OR
								tbl_os.sua_os = '$numero_os-03' OR
								tbl_os.sua_os = '$numero_os-04' OR
								tbl_os.sua_os = '$numero_os-05' OR
								tbl_os.sua_os = '$numero_os-06' OR
								tbl_os.sua_os = '$numero_os-07' OR
								tbl_os.sua_os = '$numero_os-08' OR
								tbl_os.sua_os = '$numero_os-09')";
		$monta_sql2 .= " $xsql (tbl_os_excluida.sua_os = '$numero_os' OR
								tbl_os_excluida.sua_os = '0$numero_os' OR
								tbl_os_excluida.sua_os = '00$numero_os' OR
								tbl_os_excluida.sua_os = '000$numero_os' OR
								tbl_os_excluida.sua_os = '0000$numero_os' OR
								tbl_os_excluida.sua_os = '00000$numero_os' OR
								tbl_os_excluida.sua_os = '000000$numero_os' OR
								tbl_os_excluida.sua_os = '0000000$numero_os' OR
								tbl_os_excluida.sua_os = '00000000$numero_os' OR
								tbl_os_excluida.sua_os = '000000000$numero_os' OR
								tbl_os_excluida.sua_os = '0000000000$numero_os' OR
								tbl_os_excluida.sua_os = '$numero_os-01' OR
								tbl_os_excluida.sua_os = '$numero_os-02' OR
								tbl_os_excluida.sua_os = '$numero_os-03' OR
								tbl_os_excluida.sua_os = '$numero_os-04' OR
								tbl_os_excluida.sua_os = '$numero_os-05' OR
								tbl_os_excluida.sua_os = '$numero_os-06' OR
								tbl_os_excluida.sua_os = '$numero_os-07' OR
								tbl_os_excluida.sua_os = '$numero_os-08' OR
								tbl_os_excluida.sua_os = '$numero_os-09') ";
		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas com nº <i>$numero_os</i>";
	}
$qtde_chk++;
}

##### Número da NF de Compra #####
if (strlen($chk20) > 0) {
	if ($numero_nf) {
		if ($dt == 1) $xsql = " AND ";
		else          $xsql = " AND ";

		$monta_sql .= " $xsql nota_fiscal = '$numero_nf' ";
		$monta_sql2 .= " $xsql nota_fiscal = '$numero_nf' ";
		$dt = 1;

		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " OS lançadas com Nº NF $numero_nf";
	}
$qtde_chk++;
}

if (strlen($situacao) > 0) {
	if ($dt == 1) $xsql = " AND ";
	else          $xsql = " AND ";

	$monta_sql  .= " $xsql data_fechamento $situacao ";
	$monta_sql2 .= " $xsql data_fechamento $situacao ";
	$dt = 1;
$qtde_chk++;
}

if (strlen($consumidor_revenda) > 0 AND ($consumidor_revenda == "R" OR $consumidor_revenda == "C")) {
	if ($dt == 1) $xsql = " AND ";
	else          $xsql = " OR ";

	$monta_sql .= " $xsql consumidor_revenda = '$consumidor_revenda' ";
	$dt = 1;

	if (strlen($msg) > 0) $msg .= " e ";
	if($consumidor_revenda == "R") $msg .= " de revendas ";
	if($consumidor_revenda == "C") $msg .= " de consumidores ";
$qtde_chk++;
}

##### CONCATENA O SQL PADRÃO #####
###  WHERE ###########


$sql =	"SELECT * FROM (
				SELECT  distinct    lpad(tbl_os.sua_os,10,'0')                         AS ordem          ,
							tbl_os.os                                                            ,
							tbl_os.sua_os                                                        ,
							to_char(tbl_os.data_digitacao,'DD/MM/YYYY')        AS data           ,
							to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura       ,
							to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento     ,
							to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI:SS') AS finalizada     ,
							tbl_os.data_digitacao                              AS data_consulta  ,
							tbl_os.serie                                                         ,
							tbl_os.excluida                                                      ,
							tbl_os.consumidor_nome                                               ,
							tbl_os.data_fechamento                                               ,
							tbl_os.nota_fiscal                                                   ,
							tbl_os.nota_fiscal_saida                                             ,
							tbl_os.consumidor_cpf                                                ,
							tbl_os.consumidor_cidade                                             ,
							tbl_os.consumidor_estado                                             ,
							tbl_os.consumidor_revenda                                            ,
							tbl_os.revenda_nome                                                  ,
							tbl_os.defeito_reclamado                                             ,
							tbl_os.defeito_constatado                                            ,
							tbl_os.observacao                                                    ,
							tbl_os_item.servico_realizado                                        ,
							tbl_os_item.defeito                                                  ,
							tbl_os.qtde_produtos                                                 ,
							tbl_tipo_os.descricao                           AS tipo_os_descricao ,
							tbl_posto.cnpj                                     AS cnpj_posto     ,
							tbl_posto.nome                                     AS posto_nome     ,
							tbl_posto.cidade                                   AS posto_cidade   ,
							tbl_posto.estado                                                     ,
							tbl_posto_fabrica.codigo_posto                     AS codigo_posto   ,
							tbl_produto.familia                                                  ,
							tbl_produto.referencia_pesquisa                    AS referencia     ,
							tbl_produto.descricao                                                ,
							'$login_login'                                     AS login_login
				FROM		tbl_os
				JOIN		tbl_produto          ON  tbl_os.produto            = tbl_produto.produto
				LEFT JOIN	tbl_os_produto       ON  tbl_os_produto.os         = tbl_os.os
				LEFT JOIN	tbl_os_item          ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN		tbl_posto            ON  tbl_os.posto              = tbl_posto.posto
				JOIN		tbl_posto_fabrica    ON  tbl_posto.posto           = tbl_posto_fabrica.posto
												AND tbl_posto_fabrica.fabrica  = $login_fabrica
				LEFT JOIN	tbl_cliente          ON  tbl_os.cliente            = tbl_cliente.cliente
				LEFT JOIN   tbl_os_status        ON tbl_os_status.os    = tbl_os.os
				$join_extrato
				LEFT JOIN   tbl_tipo_os          ON tbl_tipo_os.tipo_os  = tbl_os.tipo_os
				WHERE       tbl_os.fabrica = $login_fabrica
				AND         tbl_os.excluida IS NOT TRUE
				AND         (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL) ";
			$sql .= $monta_sql;
			/*$sql .= " ) UNION (
				SELECT      lpad(tbl_os_excluida.sua_os,10,'0')                   AS ordem              ,
							tbl_os_excluida.os                                                          ,
							tbl_os_excluida.sua_os                                                      ,
							to_char(tbl_os_excluida.data_digitacao ,'DD/MM/YYYY') AS data               ,
							to_char(tbl_os_excluida.data_abertura  ,'DD/MM/YYYY') AS abertura           ,
							to_char(tbl_os_excluida.data_fechamento,'DD/MM/YYYY') AS fechamento         ,
							NULL                                                  AS finalizada         ,
							tbl_os_excluida.data_digitacao                        AS data_consulta      ,
							tbl_os_excluida.serie                                                       ,
							't'                                                   AS excluida           ,
							tbl_os_excluida.consumidor_nome                                             ,
							tbl_os_excluida.data_fechamento                                             ,
							tbl_os_excluida.nota_fiscal                                                 ,
							NULL                                                  AS nota_fiscal_saida  ,
							NULL                                                  AS consumidor_cpf     ,
							NULL                                                  AS consumidor_cidade  ,
							NULL                                                  AS consumidor_estado  ,
							NULL                                                  AS consumidor_revenda ,
							NULL                                                  AS revenda_nome       ,
							NULL                                                  AS defeito_reclamado  ,
							NULL                                                  AS defeito_constatado ,
							NULL                                                  AS servico_realizado  ,
							NULL                                                  AS defeito ,
							NULL                                                  AS qtde_produtos,
							tbl_posto.nome                                        AS posto_nome         ,
							tbl_posto.estado                                                            ,
							tbl_posto_fabrica.codigo_posto                        AS codigo_posto       ,
							tbl_produto.familia                                                         ,
							tbl_produto.referencia_pesquisa                       AS referencia         ,
							tbl_produto.descricao                                                       ,
							'$login_login'                                        AS login_login
				FROM		tbl_os_excluida
				LEFT JOIN	tbl_produto       USING (produto)
				JOIN		tbl_posto         USING (posto)
				JOIN		tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											  AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE		tbl_os_excluida.fabrica = $login_fabrica";
			$sql .= $monta_sql2;
			$sql .= " )*/
		$sql .=" ) AS a ";

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
	$sql .= " ORDER BY $order_by lpad (a.sua_os,10,'0') DESC, lpad (a.os::text,10,'0') DESC";
}else{
	$sql .= " ORDER BY lpad (a.sua_os,10,'0') DESC, lpad (a.os::text,10,'0') DESC";
}

#if (getenv("REMOTE_ADDR") == "201.42.109.153") echo nl2br($sql);
//echo nl2br($sql); exit;
//HD 106972
if($qtde_chk < 3 and $login_fabrica <> 14 and $login_fabrica <>19 and $login_fabrica <> 43){
	echo "<p style='font-size: 12px; font-family: verdana;'> Por favor, escolha pelo menos 3 filtros para a pesquisa.</p>";
}else{
	$res = pg_exec($con,$sql);
}
	$res = pg_exec($con,$sql);
//echo $sql;
# if ($ip == '201.0.9.216') { echo nl2br($sql); }

if (@pg_numrows($res) == 0) {

	echo "<table width='700' height='50'><tr class='menu_top'><td align='center'>Nenhum resultado encontrado.</td></tr></table>";
}else{
		flush();

		echo "<br><br>";
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";

		flush();

		$data = date ("d/m/Y H:i:s");

		echo `rm /tmp/assist/relatorio-consulta-os-$login_fabrica.xls`;


		$fp = fopen ("/tmp/assist/relatorio-consulta-os-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE ORDENS DE SERVIÇO LANÇADAS - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

	fputs ($fp,"<table align='center' border='1' cellspacing='1' cellpadding='1'>\n");

	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>\n");
	fputs ($fp, "<td colspan='16'><FONT  COLOR='#FFFFFF'>$msg</FONT></td>\n");
	fputs ($fp, "</tr>\n");

	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>OS</FONT></TD>\n");
	if($login_fabrica ==19 ) {
			fputs ($fp, "<TD nowrap>NF CLIENTE</TD>\n");
			fputs ($fp, "<TD nowrap>NF ORIGEM</TD>\n");
		     fputs ($fp, "<TD nowrap>MOTIVO</TD>\n");
	 }
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>SÉRIE</FONT></TD>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>ABERTURA</FONT></td>\n");
	if($login_fabrica ==19 ) {
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>DIGITAÇÃO</FONT></td>\n");
	}
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>FECHAMENTO</FONT></td>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>CONSUMIDOR</FONT></td>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>REVENDA</FONT></td>\n");
	if($login_fabrica ==19 ) {
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>CNPJ POSTO</FONT></td>\n");
	}
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>POSTO</FONT></td>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>PRODUTO</FONT></td>\n");
	if($login_fabrica ==19 ) {
			fputs ($fp, "<TD nowrap>QTDE</TD>\n");
     }
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>DEFEITO CONSTATADO</FONT></td>\n");
	if($login_fabrica==14)fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>POSIÇÃO</FONT></td>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>PEÇA</FONT></td>\n");
	if($login_fabrica==14){	
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>OBSERVAÇÃO</FONT></td>\n");
	}
	/*HD: 123136*/
	if($login_fabrica==43) {
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>CIDADE</FONT></td>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>ESTADO</FONT></td>\n");
	}
	fputs ($fp, "</tr>\n");

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$os                 = trim(pg_result ($res,$i,os));
		$data               = trim(pg_result ($res,$i,data));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$fechamento         = trim(pg_result ($res,$i,fechamento));
		$finalizada         = trim(pg_result ($res,$i,finalizada));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$posto_cidade       = trim(pg_result ($res,$i,posto_cidade));
		$posto_estado       = trim(pg_result ($res,$i,estado));
		$revenda_nome       = trim(pg_result ($res,$i,revenda_nome));
		$nota_fiscal        = trim(pg_result ($res,$i,nota_fiscal));
		$nota_fiscal_saida   = trim(pg_result ($res,$i,nota_fiscal_saida));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		$posto_codigo       = trim(pg_result ($res,$i,codigo_posto));
		$posto_completo     = $posto_codigo . " - " . $posto_nome;
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$excluida           = trim(pg_result ($res,$i,excluida));
		$defeito_constatado = trim(pg_result ($res,$i,defeito_constatado));
		$qtde_produtos      = trim(pg_result ($res,$i,qtde_produtos));
		$cnpj_posto         = trim(pg_result ($res,$i,cnpj_posto));
		$observacao         = trim(pg_result ($res,$i,observacao));
		if($login_fabrica==19){ $consumidor_nome = strtoupper ($consumidor_nome);}
		$tipo_os_descricao  = trim(pg_result ($res,$i,tipo_os_descricao));


		if ($i==0)$os_armazena = $os ;
		else $os_armazena = $os_armazena .','. $os;
		$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		if (strlen(trim($sua_os)) == 0) $sua_os = $os;

		fputs ($fp, "<tr class='table_line' bgcolor='$cor;'>\n");

		if ($login_fabrica == 1) fputs ($fp, "<TD nowrap>$codigo_posto$sua_os</TD>\n");
		else                     fputs ($fp, "<TD nowrap>$sua_os</TD>\n");
	    if($login_fabrica ==19 ) {
			fputs ($fp, "<TD nowrap>$nota_fiscal</TD>\n");
			fputs ($fp, "<TD nowrap>$nota_fiscal_saida</TD>\n");
			fputs ($fp, "<TD nowrap>$tipo_os_descricao</TD>\n");
        }
		fputs ($fp, "<td nowrap>$serie</td>\n");
		fputs ($fp, "<td align='center'><acronym title='Data Abertura Sistema: $abertura'  >$abertura</acronym></td>\n");
		if($login_fabrica ==19 ) {
			fputs ($fp, "<td align='center'><acronym title='Data Abertura Sistema: $data'  >$data</acronym></td>\n");
		}

		fputs ($fp, "<td align='center'><acronym title='Data Fechamento Sistema: $finalizada'  >$fechamento</acronym></td>\n");
		fputs ($fp, "<td nowrap><acronym title='Consumidor: $consumidor_nome'  >" . substr($consumidor_nome,0,15) . "</acronym></td>\n");
		fputs ($fp, "<td nowrap><acronym title='Consumidor: $revenda_nome'  >" . substr($revenda_nome,0,15) . "</acronym></td>\n");
		if($login_fabrica ==19 ) {
			fputs ($fp, "<td nowrap><acronym title='CNPJ: $cnpj_posto'  >$cnpj_posto&nbsp;</acronym></td>\n");
		}
		fputs ($fp, "<td nowrap><acronym title='Código: $codigo_posto\nRazão Social: $posto_nome'  >" . substr($posto_completo,0,30) . "</acronym></td>\n");
		fputs ($fp, "<td nowrap>$produto_referencia - $produto_nome</td>\n");
		if($login_fabrica ==19 ) {
			fputs ($fp, "<TD nowrap>$qtde_produtos</TD>\n");
		}
		if(strlen($defeito_constatado)>0){
			$sql1 = "SELECT descricao from tbl_defeito_constatado where defeito_constatado = $defeito_constatado";
			$res1 = pg_exec($con,$sql1);
			if (pg_numrows($res1)>0)
				$defeito_constatado_descricao = trim(pg_result ($res1,0,descricao));
			else $defeito_constatado_descricao = '';
		}
		fputs ($fp, "<td nowrap>$defeito_constatado_descricao</td>\n");



		$sql2 = "SELECT
						tbl_peca.referencia             AS referencia_peca             ,
						tbl_peca.descricao              AS descricao_peca              ,
						tbl_os_item.posicao
				FROM	tbl_os_produto
				JOIN	tbl_os_item USING (os_produto)
				JOIN	tbl_produto USING (produto)
				JOIN	tbl_peca    USING (peca)
				WHERE   tbl_os_produto.os = $os
				ORDER BY tbl_peca.descricao";
	//if ($ip == '201.0.9.216') echo $sql;
		$res2 = pg_exec ($con,$sql2);
		$total = pg_numrows ($res2);

		if (pg_numrows($res2) > 0) {
			$referencia_peca           = trim(pg_result ($res2,0,referencia_peca));
			$descricao_peca            = trim(pg_result ($res2,0,descricao_peca));
			$posicao            = trim(pg_result ($res2,0,posicao));
		}else{
			$referencia_peca ="";
			$descricao_peca  ="";
			$posicao         ="";
		}
		if($login_fabrica==14)fputs ($fp, "<td nowrap>$posicao</td>\n");
		fputs ($fp, "<td nowrap>$referencia_peca &nbsp; $descricao_peca</td>\n");
		if($login_fabrica==14){	
			fputs ($fp, "<td nowrap>$observacao</td>\n");
		}
		/*HD: 123136*/
		if($login_fabrica ==43) {
			fputs ($fp, "<TD nowrap>$posto_cidade</TD>\n");
			fputs ($fp, "<TD nowrap>$posto_estado</TD>\n");
		}
		fputs ($fp, "</tr>\n");

	}
	fputs ($fp, "</table>\n");
	fputs ($fp, "<br>");
	fputs ($fp, "<table height='20'><tr class='menu_top'><td align='center'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");

		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);


	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-consulta-os-$login_fabrica.$data.xls /tmp/assist/relatorio-consulta-os-$login_fabrica.html`;

	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>RELATÓRIO POR OS<BR>Clique aqui para fazer o </font><a href='xls/relatorio-consulta-os-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";

}

echo "<br>";



//echo $os_armazena;
$sql2 = "SELECT
				tbl_os.sua_os                                                                     ,
				tbl_os.os                                                                         ,
				tbl_os.serie                                                                      ,
				tbl_os.observacao                                                                 ,
				tbl_peca.referencia                                AS referencia_peca             ,
				tbl_peca.descricao                                 AS descricao_peca              ,
				tbl_posto.nome                                     AS posto_nome                  ,
				tbl_posto.estado                                                                  ,
				tbl_posto_fabrica.codigo_posto                     AS codigo_posto                ,
				tbl_produto.familia                                                               ,
				tbl_produto.referencia_pesquisa                    AS referencia                  ,
				tbl_produto.descricao                                                             ,
				tbl_os_item.posicao
		FROM	tbl_os_produto
		JOIN	tbl_os_item          USING (os_produto)
		JOIN	tbl_produto          USING (produto)
		JOIN	tbl_peca             USING (peca)
		JOIN	tbl_os               ON tbl_os.os                  = tbl_os_produto.os
		JOIN	tbl_posto            ON  tbl_os.posto              = tbl_posto.posto
		JOIN	tbl_posto_fabrica    ON  tbl_posto.posto           = tbl_posto_fabrica.posto
		WHERE   tbl_os_produto.os IN ($os_armazena)
		ORDER BY tbl_peca.descricao";
// echo $sql2;

$res2 = pg_exec ($con,$sql2);

$total = pg_numrows ($res2);


if (@pg_numrows($res2) == 0) {
	echo "<table width='700' height='50'><tr class='menu_top'><td align='center'>Nenhum resultado encontrado.</td></tr></table>";
}else{
		flush();

		echo "<br><br>";
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";

		flush();

		$data = date ("d/m/Y H:i:s");

		echo `rm /tmp/assist/relatorio-consulta-os-peca-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/relatorio-consulta-os-peca-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE ORDENS DE SERVIÇO LANÇADAS - $data POR PEÇAS");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

	fputs ($fp,"<table align='center' border='1' cellspacing='1' cellpadding='1'>\n");

	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>\n");
	fputs ($fp, "<td colspan='4'><FONT  COLOR='#FFFFFF'>$msg</FONT></td>\n");
	fputs ($fp, "</tr>\n");

	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>OS</FONT></TD>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>POSTO</FONT></td>\n");
	if ($login_fabrica == 14 or $login_fabrica == 66 or $login_fabrica == 43) {
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>SÉRIE</FONT></td>\n");
	}
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>PRODUTO</FONT></td>\n");
	if($login_fabrica=='14')fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>POSIÇÃO</FONT></td>\n");
	fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>PEÇA</FONT></td>\n");
	if($login_fabrica==14){	
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>OBSERVAÇÃO</FONT></td>\n");
	}
	fputs ($fp, "</tr>\n");

	for ($i = 0 ; $i < pg_numrows ($res2) ; $i++){
		$os                 = trim(pg_result ($res2,$i,os));
		$sua_os             = trim(pg_result ($res2,$i,sua_os));
		$posto_nome         = trim(pg_result ($res2,$i,posto_nome));
		$posto_codigo       = trim(pg_result ($res2,$i,codigo_posto));
		$posto_completo     = $posto_codigo . " - " . $posto_nome;
		$serie              = trim(pg_result ($res2,$i,serie));
		$produto_nome       = trim(pg_result ($res2,$i,descricao));
		$produto_referencia = trim(pg_result ($res2,$i,referencia));
		$referencia_peca    = trim(pg_result ($res2,$i,referencia_peca));
		$descricao_peca     = trim(pg_result ($res2,$i,descricao_peca));
		$posicao            = trim(pg_result ($res2,$i,posicao));
		$observacao            = trim(pg_result ($res2,$i,observacao));

		$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		if (strlen(trim($sua_os)) == 0) $sua_os = $os;

		fputs ($fp, "<tr class='table_line' bgcolor='$cor;'>\n");

		if ($login_fabrica == 1) fputs ($fp, "<TD nowrap>$codigo_posto$sua_os</TD>\n");
		else                     fputs ($fp, "<TD nowrap>$sua_os</TD>\n");

		fputs ($fp, "<td nowrap><acronym title='Código: $codigo_posto\nRazão Social: $posto_nome'  >" . substr($posto_completo,0,30) . "</acronym></td>\n");
		if ($login_fabrica == 14 or $login_fabrica == 66 or $login_fabrica == 43) {
			fputs ($fp, "<td nowrap>$serie</td>\n");
		}
		fputs ($fp, "<td nowrap>$produto_referencia - $produto_nome</td>\n");
		if($login_fabrica=='14')fputs ($fp, "<td nowrap>$posicao</td>\n");
		fputs ($fp, "<td nowrap>$referencia_peca - $descricao_peca</td>\n");
		if($login_fabrica==14){	
			fputs ($fp, "<td nowrap>$observacao</td>\n");
		}
		fputs ($fp, "</tr>\n");

	}
	fputs ($fp, "</table>\n");
	fputs ($fp, "<br>");
	fputs ($fp, "<table height='20'><tr class='menu_top'><td align='center'>Total de " . pg_numrows($res2) . " resultado(s) encontrado(s).</td></tr></table>");

		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);


	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-consulta-os-peca-$login_fabrica.$data.xls /tmp/assist/relatorio-consulta-os-peca-$login_fabrica.html`;

	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>RELATÓRIO POR PEÇA<BR>Clique aqui para fazer o </font><a href='xls/relatorio-consulta-os-peca-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";

}

echo "<br>";
















##### BOTÃO NOVA CONSULTA #####
echo "<table width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<tr class='table_line'>";
echo "<td align='center' background='#D9E2EF'>";
echo "<a href='defeito_os_parametros_duplic.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "<br>";

include "rodape.php";
?>
