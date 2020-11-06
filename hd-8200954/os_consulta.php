<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


#::::::::::::::::::::::::::::::::::::::::
header("Location: os_consulta_lite.php");
exit;
#::::::::::::::::::::::::::::::::::::::::


if($login_fabrica == 1) {
	include("os_consulta_blackedecker.php");
	exit;
}

include 'funcoes.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$os = $_GET['excluir'];

if (strlen ($os) > 0) {
/*
	$sql = "DELETE FROM tbl_os 
			WHERE  os      = $os 
			AND    posto   = $login_posto
			AND    fabrica = $login_fabrica";

	$sql = "UPDATE tbl_os SET excluida = 't'
			WHERE  tbl_os.os      = $os
			AND    tbl_os.posto   = $login_posto
			AND    tbl_os.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);
*/

	if ($login_fabrica == 3) {
		$sql = "UPDATE tbl_os SET excluida = 't'
				WHERE  tbl_os.os      = $os
				AND    tbl_os.posto   = $login_posto
				AND    tbl_os.fabrica = $login_fabrica;";
		$res = @pg_exec ($con,$sql);
	}else{
		$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
		$res = @pg_exec ($con,$sql);
	}
	$msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		header("Location: os_parametros.php");
		exit;
	}

}

$cookget = @explode("?", $REQUEST_URI);		// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookget", $cookget[1]);			/* expira qdo fecha o browser */

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
if($_POST["consumidor_estado"])		$uf                 = trim($_POST["consumidor_estado"]);
if($_POST["numero_os"])				$numero_os          = trim($_POST["numero_os"]);
if($_POST["numero_nf"])				$numero_nf          = trim($_POST["numero_nf"]);
if($_POST["situacao"])				$situacao           = trim($_POST["situacao"]);
if($_POST["consumidor_revenda"])	$consumidor_revenda = trim($_POST["consumidor_revenda"]);

if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01      = trim($_GET["data_final_01"]);
if($_GET["produto_referencia"])		$produto_referencia = trim($_GET["produto_referencia"]);
if($_GET["numero_serie"])			$numero_serie       = trim($_GET["numero_serie"]);
if($_GET["nome_consumidor"])		$nome_consumidor    = trim($_GET["nome_consumidor"]);
if($_GET["cpf_consumidor"])			$cpf_consumidor     = trim($_GET["cpf_consumidor"]);
if($_GET["cidade"])					$cidade             = trim($_GET["cidade"]);
if($_GET["consumidor_estado"])		$uf                 = trim($_GET["consumidor_estado"]);
if($_GET["numero_os"])				$numero_os          = trim($_GET["numero_os"]);
if($_GET["numero_nf"])				$numero_nf          = trim($_GET["numero_nf"]);
if($_GET["situacao"])				$situacao           = trim($_GET["situacao"]);
if($_GET["consumidor_revenda"])		$consumidor_revenda = trim($_GET["consumidor_revenda"]);

$produto_referencia = str_replace ("." , "" , $produto_referencia);
$produto_referencia = str_replace ("-" , "" , $produto_referencia);
$produto_referencia = str_replace ("/" , "" , $produto_referencia);
$produto_referencia = str_replace (" " , "" , $produto_referencia);


$layout_menu = "os";
$title = "Relação de Ordens de Serviços Lançadas";
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
echo "<table width='700' border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr height='16'>";
echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
echo "<td align='left'><font size=1><b>&nbsp; Excluídas do sistema</b></font></td>";
echo "</tr>";
echo "<tr height='3'><td colspan='2'></td></tr>";
echo "<tr height='16'>";
echo "<td align='center' width='16' bgcolor='#D7FFE1'>&nbsp;</td>";
echo "<td align='left'><font size=1><b>&nbsp; Reincidências</b></font></td>";
echo "</tr>";
echo "<tr height='3'><td colspan='2'></td></tr>";
if ($login_fabrica == 14) {
	echo "<tr height='16'>";
	echo "<td align='center' width='16' bgcolor='#91C8FF'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp; OSs abertas há mais de 3 dias sem data de fechamento</b></font></td>";
	echo "</tr>";
	echo "<tr height='3'><td colspan='2'></td></tr>";
	echo "<tr height='16'>";
	echo "<td align='center' width='16' bgcolor='#FF0000'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp; OSs abertas há mais de 5 dias sem data de fechamento</b></font></td>";
	echo "</tr>";
}else{
	echo "<tr height='16'>";
	echo "<td align='center' width='16' bgcolor='#91C8FF'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp; OSs abertas há mais de 25 dias sem data de fechamento</b></font></td>";
	echo "</tr>";
}
echo "</table>";

	echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0'>";
	if (strlen($msg_erro) > 0){
		echo "<tr>";
		echo "<td colspan='3' class='error'>$msg_erro</td>";
		echo "</tr>";
	}
	echo "</table>";

	echo "<br>";

	// BTN_NOVA BUSCA
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_parametros.php'><img src='imagens/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
$sql = "SELECT * FROM (
			(
				SELECT  lpad (tbl_os.sua_os,20,'0')                  AS ordem         ,
						tbl_os.os                                                     ,
						tbl_os.sua_os                                                 ,
						tbl_os.data_digitacao                        AS data_consulta ,
						to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS data          ,
						to_char (tbl_os.data_abertura ,'DD/MM/YYYY') AS abertura      ,
						tbl_os.serie                                                  ,
						tbl_os.data_fechamento                                        ,
						tbl_os.consumidor_nome                                        ,
						tbl_os.excluida                                               ,
						tbl_os.nota_fiscal                                            ,
						tbl_os.motivo_atraso                                          ,
						tbl_os.consumidor_revenda                                     ,
						tbl_os.revenda_nome                                           ,
						tbl_posto.nome                               AS posto_nome    ,
						tbl_produto.referencia                                        ,
						tbl_produto.descricao                                         ,
						tbl_produto.referencia_pesquisa                               ,
						tbl_os_extra.impressa                                         ,
						tbl_os_extra.extrato                                          ,
						tbl_os_extra.os_reincidente                                   ,
						(
							SELECT	MAX (tbl_os_item.pedido) AS pedido 
							FROM	tbl_os_produto 
							JOIN	tbl_os_item USING (os_produto) 
							WHERE	tbl_os_produto.os = tbl_os.os
						) AS pedido
				FROM 	tbl_os
				JOIN 	tbl_posto USING (posto)
				JOIN 	tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_os_extra USING (os)
				LEFT JOIN tbl_produto USING (produto)
				LEFT JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha AND tbl_os.posto = tbl_posto_linha.posto
				LEFT JOIN tbl_cliente    ON tbl_os.cliente    = tbl_cliente.cliente
				WHERE		tbl_os.fabrica = $login_fabrica 
				AND         (tbl_os.posto = $login_posto OR tbl_os.digitacao_distribuidor = $login_posto OR tbl_posto_linha.distribuidor = $login_posto)
			) UNION (
				SELECT  lpad (tbl_os_excluida.sua_os,20,'0')                  AS ordem              ,
						tbl_os_excluida.os                                                          ,
						tbl_os_excluida.sua_os                                                      ,
						tbl_os_excluida.data_digitacao                        AS data_consulta      ,
						to_char (tbl_os_excluida.data_digitacao,'DD/MM/YYYY') AS data               ,
						to_char (tbl_os_excluida.data_abertura ,'DD/MM/YYYY') AS abertura           ,
						tbl_os_excluida.serie                                                       ,
						tbl_os_excluida.data_fechamento                                             ,
						tbl_os_excluida.consumidor_nome                                             ,
						't'                                                   AS excluida           ,
						NULL                                                  AS nota_fiscal        ,
						NULL                                                  AS motivo_atraso      ,
						NULL                                                  AS consumidor_revenda ,
						NULL                                                  AS revenda_nome       ,
						tbl_posto.nome                                        AS posto_nome         ,
						tbl_produto.referencia                                                      ,
						tbl_produto.descricao                                                       ,
						tbl_produto.referencia_pesquisa                                             ,
						tbl_os_extra.impressa                                                       ,
						tbl_os_extra.extrato                                                        ,
						NULL                                                  AS os_reincidente     ,
						NULL                                                  AS pedido             
				FROM 	tbl_os_excluida
				JOIN 	tbl_posto USING (posto)
				JOIN 	tbl_posto_fabrica 	 ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_os_extra USING (os)
				LEFT JOIN tbl_produto USING (produto)
				LEFT JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha AND tbl_os_excluida.posto = tbl_posto_linha.posto
				WHERE	tbl_os_excluida.fabrica = $login_fabrica 
				AND    (tbl_os_excluida.posto = $login_posto OR tbl_posto_linha.distribuidor = $login_posto)
			)
		) AS a
		WHERE (1=2 ";

if(strlen($chk1) > 0){
	//dia atual
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

	$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
	$resX = pg_exec ($con,$sqlX);
	#  $dia_hoje_final = pg_result ($resX,0,0);

	$monta_sql .= " OR (a.data_consulta BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " e OS lançadas hoje";

}

if(strlen($chk2) > 0) {
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (a.data_consulta BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
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

	$monta_sql .=" OR (a.data_consulta BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e OS lançadas nesta semana";

}

if(strlen($chk4) > 0){
	// do mês
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	$monta_sql .= "OR (a.data_consulta BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
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

		$monta_sql .= "OR (a.data_consulta BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59') ";
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

		$monta_sql .= "$xsql a.referencia_pesquisa = '".$produto_referencia."' ";
		$dt = 1;

		$msg .= " e OS lançadas contendo o produto  $produto_nome";

	}
}

if(strlen($chk8) > 0){
	// numero de serie do produto
	if ($numero_serie) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.serie = '". $numero_serie."' ";
		$dt = 1;

		$msg .= " e OS lançadas contendo produtos com número de série : $numero_serie";

	}
}

if(strlen($chk9) > 0){
	// nome_consumidor
	if ($nome_consumidor){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.consumidor_nome ILIKE '%".$nome_consumidor."%' ";
		$dt = 1;

		$msg .= " e OS lançadas para o consumidor $nome_consumidor";

	}
}

if(strlen($chk10) > 0){
	// cpf_consumidor
	if ($cpf_consumidor){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_cliente.cpf = '$cpf_consumidor' ";
		$dt = 1;

		$msg .= " e OS lançadas para o consumidor com CFP/CNPJ: $cpf_consumidor";

	}
}

if(strlen($chk11) > 0){
	// cidade
	if ($cidade){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.consumidor_cidade = '". $cidade."' ";
		$dt = 1;

		$msg .= " e OS lançadas para a cidade $cidade";

	}
}

if(strlen($chk12) > 0){
	// uf
	if ($uf){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.consumidor_estado = '".$uf."' ";
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
			$monta_sql .= "$xsql (trim(a.sua_os) like '".$numero_os."%' OR trim(a.os) = '$numero_os') ";
		else
			$monta_sql .= "$xsql trim(a.sua_os) like '".$numero_os."%' ";

		$dt = 1;

		$msg .= " e OS lançadas com Nº $numero_os";

	}
}

if(strlen($chk14) > 0){
	// numero_nf
	if ($numero_nf) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.nota_fiscal = '".$numero_nf."' ";
		$dt = 1;

		$msg .= " e OS lançadas com Nº NF $numero_nf";
	}
}

if (strlen($situacao) > 0){
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql (a.data_fechamento $situacao AND a.excluida <> 't') ";
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
$sql .= ") ORDER BY lpad (a.sua_os,20,'0') DESC , lpad(a.os,20,'0') DESC";

//if ($ip == '201.0.9.216') { echo nl2br($sql); exit; }

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

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
	echo "<TD colspan='11'>$msg</TD>\n";
	echo "</TR>\n";

	echo "<TR class='menu_top'>\n";
	echo "<TD>OS</TD>\n";
	echo "<TD>SÉRIE</TD>\n";
	//echo "<TD width='075'>DIGITAÇÃO</TD>\n";
	echo "<TD width='40'>AB</TD>\n";
	echo "<TD width='40'>FC</TD>\n";
	if ($login_e_distribuidor == 't') {
		echo "<TD width='130'>POSTO</TD>\n";
	}
	echo "<TD width='130'>CONSUMIDOR</TD>\n";
	echo "<TD >PRODUTO</TD>\n";
	echo "<TD >&nbsp;</TD>\n";
	echo "<TD colspan='4'>AÇÕES</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res); $i++){
		$os                 = trim(pg_result ($res,$i,os));
		$data               = trim(pg_result ($res,$i,data));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$revenda_nome       = trim(pg_result ($res,$i,revenda_nome));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		$excluida           = trim(pg_result ($res,$i,excluida));
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$pedido             = trim(pg_result ($res,$i,pedido));
		$impressa           = trim(pg_result ($res,$i,impressa));
		$extrato            = trim(pg_result ($res,$i,extrato));
		$motivo_atraso      = trim(pg_result ($res,$i,motivo_atraso));
		$os_reincidente     = trim(pg_result ($res,$i,os_reincidente));
		$consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));

		$cor = "#F7F5F0";
		$btn = "amarelo";
		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}

		if ($excluida == "t")            $cor = "#FFE1E1";
		if (strlen($os_reincidente) > 0) $cor = "#D7FFE1";

		if (strlen($data_fechamento) == 0 AND $excluida != "t" AND $login_fabrica != 14) {
			$aux_data_abertura = fnc_formata_data_pg($abertura);

			$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '25 days', 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_consultar = pg_result($resX,0,0);

			$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_atual = pg_result ($resX,0,0);
			if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
				$cor = "#91C8FF";
			}
		}

		##### CONDIÇÕES PARA INTELBRAS - INÍCIO #####
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
		##### CONDIÇÕES PARA INTELBRAS - FIM #####

		if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

		echo "<TR class='table_line' style='background-color: $cor;' height='25'>\n";
		echo "<TD nowrap>$sua_os</TD>\n";
		echo "<TD nowrap>$serie</TD>\n";
		//echo "<TD nowrap align='center'>$data</TD>\n";
		echo "<TD nowrap align='center'>" . substr($abertura,0,5) . "</TD>\n";
		echo "<TD nowrap align='center'>";
		if (strlen($data_fechamento) > 0)
			echo substr($data_fechamento,8,10) . "/" . substr($data_fechamento,5,2);
		echo "</TD>\n";

		if ($login_e_distribuidor == 't') {
			echo "<TD nowrap>\n";
			echo "<ACRONYM TITLE='$posto_nome'>" . substr($posto_nome,0,15) . "</ACRONYM>";
			echo "</TD>\n";
		}

		
		echo "<TD nowrap>";
		if ($consumidor_revenda == "C" AND strlen($consumidor_nome) > 0)
			echo "<ACRONYM TITLE='$consumidor_nome'>" . substr($consumidor_nome,0,15) . "</ACRONYM>";
		else
			echo "<ACRONYM TITLE='$revenda_nome'>" . substr($revenda_nome,0,15) . "</ACRONYM>";
		echo "</TD>\n";

		echo "<TD nowrap><ACRONYM TITLE='$produto_referencia - $produto_nome'>" . substr($produto_nome,0,17) . "</ACRONYM></TD>\n";
		echo "<TD nowrap>";
		if (strlen($impressa) > 0){
			echo"<img src='imagens/img_ok.gif' alt='OS já foi impressa'>";
		}else{
			echo"<img src='imagens/img_impressora.gif' alt='Imprimir OS'>";
		}
		echo "</TD>\n";
		
		echo "<TD width='57'>";
		if ($excluida == "f" OR strlen($excluida) == 0) echo "<a href='os_press.php?os=$os'><img src='imagens/btn_consulta.gif'></a>"; 
		echo "</TD>\n";
		
		echo "<TD width='57'>";
		if ($excluida == "f" OR strlen($excluida) == 0) echo "<a href='os_print.php?os=$os' target='_blank'><img src='imagens/btn_imprime.gif'></a>";
		echo "</TD>\n";

		echo "<TD nowrap>";
		if (($login_fabrica == 3 OR $login_fabrica == 6) AND strlen ($data_fechamento) == 0) {
			if ($excluida == "f" OR strlen($excluida) == 0) {
				echo "<a href='os_item.php?os=$os'><img src='imagens/btn_lanca.gif'></a>";
			}
		}elseif ($login_fabrica == 1 and strlen ($data_fechamento) == 0 ) { 
			if ($excluida == "f" or strlen($excluida) == 0) {
				echo "<a href='os_item.php?os=$os'><img src='imagens/btn_lanca.gif'></a> &nbsp; <a href='os_motivo_atraso.php?os=$os'>Motivo</a>";
			}
		}elseif ($login_fabrica == 7 and strlen ($data_fechamento) == 0 ) { 
			echo "<a href='os_filizola_valores.php?os=$os'><img src='imagens/btn_lanca.gif'></a>";
		}elseif (strlen($data_fechamento) == 0 ) {
			if ($excluida == "f" OR strlen($excluida) == 0) {
				echo "<a href='os_item.php?os=$os'><img src='imagens/btn_lanca.gif'></a>";
			}
		}elseif (strlen($data_fechamento) > 0 and strlen($extrato) == 0) {
			if ($excluida == "f" OR strlen($excluida) == 0) {
				echo "<a href='os_item.php?os=$os&reabrir=ok' ><img src='imagens/btn_reabriros.gif'></a>";
			}
		}else{
			echo "&nbsp;";
		}
		echo "</TD>\n";
		
		echo "<TD width='56'>";
		if (strlen($data_fechamento) == 0 AND strlen($pedido) == 0 AND $login_fabrica <> 7 ) { 
			if ($excluida == "f" or strlen($excluida) == 0) {
				echo "<A HREF=\"javascript: if (confirm ('Deseja realmente excluir OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os' }\"><img src='imagens/btn_excluir.gif'></A>";
			}
		}else{
			echo "&nbsp;";
		}
		echo "</TD>\n";
		
		if ($login_fabrica == 7) {
			echo "<TD width='56'>";
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