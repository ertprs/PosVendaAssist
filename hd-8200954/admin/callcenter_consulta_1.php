<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

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

if($_POST["data_inicial_01"])		$data_inicial_01    = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_01      = trim($_POST["data_final_01"]);
if($_POST['codigo_posto'])			$codigo_posto       = trim($_POST['codigo_posto']);
if($_POST["produto_referencia"])	$produto_referencia = trim($_POST["produto_referencia"]);
if($_POST["produto_nome"])			$produto_nome       = trim($_POST["produto_nome"]);
if($_POST["numero_serie"])			$numero_serie       = trim($_POST["numero_serie"]);
if($_POST["nome_consumidor"])		$nome_consumidor    = trim($_POST["nome_consumidor"]);
if($_POST["cpf_consumidor"])		$cpf_consumidor     = trim($_POST["cpf_consumidor"]);
if($_POST["cidade"])				$cidade             = trim($_POST["cidade"]);
if($_POST["uf"])					$uf                 = trim($_POST["uf"]);
if($_POST["numero_os"])				$numero_os          = trim($_POST["numero_os"]);
if($_POST["nota_fiscal"])			$nota_fiscal        = trim($_POST["nota_fiscal"]);
if($_POST["nota_fiscal"])			$nota_fiscal        = trim($_POST["nota_fiscal"]);
if($_POST["callcenter"])			$callcenter         = trim($_POST["callcenter"]);
if($_POST["situacao"])				$situacao           = trim($_POST["situacao"]);

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
if($_GET["nota_fiscal"])			$nota_fiscal        = trim($_GET["nota_fiscal"]);
if($_GET["callcenter"])				$callcenter         = trim($_GET["callcenter"]);
if($_GET["situacao"])				$situacao           = trim($_GET["situacao"]);
$produto_referencia = str_replace ("." , "" , $produto_referencia);
$produto_referencia = str_replace ("-" , "" , $produto_referencia);
$produto_referencia = str_replace ("/" , "" , $produto_referencia);
$produto_referencia = str_replace (" " , "" , $produto_referencia);


$layout_menu = "callcenter";
$title = "Rela��o de Atendimentos Lan�ados";

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

</style>

<?

	// BTN_NOVA BUSCA
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='callcenter_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
//lpad (tbl_callcenter.sua_os,10,'0')        AS sua_os         ,

$sql = "SELECT  tbl_callcenter.callcenter                                    ,
				to_char (tbl_callcenter.data,'DD/MM/YYYY') AS data           ,
				tbl_produto.referencia                                       ,
				tbl_produto.descricao                                        ,
				tbl_callcenter.serie                                         ,
				tbl_cliente.nome                           AS consumidor_nome,
				tbl_callcenter.sua_os                                        ,
				tbl_posto.nome                             AS posto_nome     ,
				tbl_callcenter.solucionado                                   
		FROM	tbl_callcenter
		LEFT JOIN tbl_produto       USING (produto)
		LEFT JOIN tbl_posto         USING (posto)
		LEFT JOIN tbl_cliente       ON tbl_callcenter.cliente = tbl_cliente.cliente
		LEFT JOIN tbl_posto_fabrica	 ON tbl_posto.posto = tbl_posto_fabrica.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_cidade        ON tbl_cidade.cidade = tbl_cliente.cidade
		WHERE	tbl_callcenter.fabrica = $login_fabrica AND (1=2 ";

if(strlen($chk1) > 0){
	//dia atual
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

	$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
	$resX = pg_exec ($con,$sqlX);
	#  $dia_hoje_final = pg_result ($resX,0,0);

	$monta_sql .= " OR (tbl_callcenter.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " e atendimentos lan�ados hoje";

}

if(strlen($chk2) > 0) {
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_callcenter.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;

	$msg .= " e atendimento lan�ados ontem";

}

if(strlen($chk3) > 0){
	// �ltima semana
	$sqlX = "SELECT to_char (current_date , 'D')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

	$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_callcenter.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e atendimentos lan�ados nesta semana";

}

if(strlen($chk4) > 0){
	// do m�s
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	$monta_sql .= "OR (tbl_callcenter.data BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$dt = 1;

	$msg .= " e atendimentos lan�ados neste m�s ";

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

		$monta_sql .= "OR (tbl_callcenter.data BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59') ";
		$dt = 1;

		$msg .= " e atendimentos lan�ados entre os dias $data_inicial_01 e $data_final_01 ";
	}
}

if(strlen($chk6) > 0){
	// codigo do posto
	if (strlen($codigo_posto) > 0){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_posto_fabrica.codigo_posto = '". $codigo_posto."' ";
		$dt = 1;

		$msg .= " e atendimentos lan�ados para o posto $nome_posto";

	}
}

if(strlen($chk7) > 0){
	// referencia do produto
	if ($produto_referencia) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_produto.referencia_pesquisa = '".$produto_referencia."' ";
		$dt = 1;

		$msg .= " e atendimentos lan�ados contendo o produto $produto_nome";

	}
}

if(strlen($chk8) > 0){
	// numero de serie do produto
	if ($numero_serie) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_callcenter.serie = '". $numero_serie."' ";
		$dt = 1;

		$msg .= " e atendimentos lan�ados contendo produtos com n�mero de s�rie $numero_serie";

	}
}

if(strlen($chk9) > 0){
	// nome_consumidor
	if ($nome_consumidor){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		//$monta_sql .= "$xsql tbl_cliente.nome ilike '%".$cliente."%' ";
		$monta_sql .= "$xsql tbl_cliente.nome ILIKE '%".$nome_consumidor."%' ";
		$dt = 1;

		$msg .= " e atendimentos lan�ados para o consumidor $nome_consumidor";

	}
}

if(strlen($chk10) > 0){
	// cpf_consumidor
	if ($cpf_consumidor){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_cliente.cpf ILIKE '". $cpf_consumidor."%' ";
		$dt = 1;

		$msg .= " e atendimentos lan�ados para o consumidor com CPF/CNPJ: $cpf_consumidor";

	}
}

if(strlen($chk11) > 0){
	// cidade
	if ($cidade){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_posto.cidade ILIKE '%". $cidade."%' ";
		$dt = 1;

		$msg .= " e atendimentos lan�ados para a cidade $cidade";

	}
}

if(strlen($chk12) > 0){
	// uf
	if ($uf){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_posto.estado = '".$uf."' ";
		$dt = 1;

		$msg .= " e atendimentos lan�ados para o estado $estado";

	}
}

if(strlen($chk13) > 0){
	// numero_os
	if ($numero_os){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_callcenter.sua_os ILIKE '".$numero_os."%' ";
		$dt = 1;

		$msg .= " e atendimentos lan�ados com OS n�mero $numero_os";

	}
}

if(strlen($chk14) > 0){
	// nota fiscal
	if ($nota_fiscal){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_callcenter.nota_fiscal ILIKE '".$nota_fiscal."%' ";
		$dt = 1;

		$msg .= " e atendimentos lan�ados com nota fiscal n�mero $nota_fiscal";

	}
}

if(strlen($chk15) > 0){
	// callcenter
	if ($callcenter){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_callcenter.callcenter = ".$callcenter." ";
		$dt = 1;

		$msg .= " e o atendimento de Callcenter n�mero $callcenter";

	}
}

//Estas duas verifica��es s�o para filtrar somente por atendimentos pendentes e solucionados
if ($situacao=="PENDENTES"){
	$monta_sql .="AND       (tbl_providencia.solucionado IS FALSE OR tbl_providencia.callcenter IS NULL) ";
	$msg .= ", e somente os Atendimentos Pendentes";
}
if ($situacao=="SOLUCIONADOS"){
	$monta_sql .="AND       (tbl_providencia.solucionado IS TRUE ) ";
	$msg .= ", e somente os Atendimentos Solucionados";
}



// ordena sql padrao
$sql .= $monta_sql;
$sql .= ") ORDER BY tbl_callcenter.data DESC ";

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//echo "<br>".nl2br($sql)."<br><br>".nl2br($sqlCount)."<br>";

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
	echo "<TD colspan=8>$msg</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD>DATA</TD>\n";
	echo "<TD>PRODUTO</TD>\n";
	echo "<TD>N� CHAMADO</TD>\n";
	echo "<TD>CLIENTE</TD>\n";
	echo "<TD>OS</TD>\n";
	echo "<TD>SOLUCIONADO</TD>\n";
	echo "<TD width='170' colspan='2'>A��ES</TD>\n";
	echo "</TR>\n";
echo "aquiii";
echo "$sql";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$callcenter         = trim(pg_result ($res,$i,callcenter));
		$data               = trim(pg_result ($res,$i,data));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$solucionado        = trim(pg_result ($res,$i,solucionado));

		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}

		if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		echo "<TD align=center nowrap>$data</TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">".substr($produto_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD align=center nowrap>$callcenter</TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17)."</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$posto_nome\">$sua_os</ACRONYM></TD>\n";
		echo "<TD align=center nowrap>";
		if ($solucionado == 't') 
			echo "Solucionado";
		else 
			echo "Em andamento";
		echo "</TD>\n";
		echo "<TD width=85><a href='callcenter_press.php?callcenter=$callcenter'><img src='imagens/btn_consultar_".$btn.".gif'></a></TD>\n";
		echo "<TD width=85>";
		if ($solucionado <> 't') {
			echo "<a href='callcenter_cadastro_1.php?callcenter=$callcenter' target='_blank'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a>";
		}
		echo "</TD>";
		echo "</TR>\n";
	}
	echo "</TABLE>\n";
}

echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<TR class='table_line'>";
echo "<TD align='center' background='#D9E2EF'>";
echo "<a href='callcenter_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
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

if($situacao=="TODOS"){
// echo "ENTRA AQUI";
	$sql = "SELECT  count (tbl_callcenter.callcenter) AS pendentes
			FROM	tbl_callcenter
			LEFT JOIN tbl_produto       USING (produto)
			LEFT JOIN tbl_posto         USING (posto)
			LEFT JOIN tbl_cliente       ON tbl_callcenter.cliente = tbl_cliente.cliente
			LEFT JOIN tbl_posto_fabrica	 ON tbl_posto.posto = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_cidade        ON tbl_cidade.cidade = tbl_cliente.cidade
			LEFT JOIN tbl_providencia        ON tbl_providencia.callcenter = tbl_callcenter.callcenter
			WHERE	tbl_callcenter.fabrica = $login_fabrica AND (1=2 ";

	if(strlen($chk1) > 0){
		//dia atual
		$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
		$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

		$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
		$resX = pg_exec ($con,$sqlX);
		#  $dia_hoje_final = pg_result ($resX,0,0);

		$monta_sql .= " OR (tbl_callcenter.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
		$dt = 1;

	}

	if(strlen($chk2) > 0) {
		// dia anterior
		$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
		$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

		$monta_sql .=" OR (tbl_callcenter.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
		$dt = 1;

	}

	if(strlen($chk3) > 0){
		// �ltima semana
		$sqlX = "SELECT to_char (current_date , 'D')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

		$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

		$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

		$monta_sql .=" OR (tbl_callcenter.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
		$dt = 1;

	}

	if(strlen($chk4) > 0){
		// do m�s
		$mes_inicial = trim(date("Y")."-".date("m")."-01");
		$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

		$monta_sql .= "OR (tbl_callcenter.data BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
		$dt = 1;

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

			$monta_sql .= "OR (tbl_callcenter.data BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59') ";
			$dt = 1;

		}
	}

	if(strlen($chk6) > 0){
		// codigo do posto
		if (strlen($codigo_posto) > 0){
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_posto_fabrica.codigo_posto = '". $codigo_posto."' ";
			$dt = 1;

		}
	}

	if(strlen($chk7) > 0){
		// referencia do produto
		if ($produto_referencia) {
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_produto.referencia_pesquisa = '".$produto_referencia."' ";
			$dt = 1;

		}
	}

	if(strlen($chk8) > 0){
		// numero de serie do produto
		if ($numero_serie) {
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_callcenter.serie = '". $numero_serie."' ";
			$dt = 1;

		}
	}

	if(strlen($chk9) > 0){
		// nome_consumidor
		if ($nome_consumidor){
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			//$monta_sql .= "$xsql tbl_cliente.nome ilike '%".$cliente."%' ";
			$monta_sql .= "$xsql tbl_cliente.nome ILIKE '%".$nome_consumidor."%' ";
			$dt = 1;

		}
	}

	if(strlen($chk10) > 0){
		// cpf_consumidor
		if ($cpf_consumidor){
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_cliente.cpf ILIKE '". $cpf_consumidor."%' ";
			$dt = 1;

		}
	}

	if(strlen($chk11) > 0){
		// cidade
		if ($cidade){
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_posto.cidade ILIKE '%". $cidade."%' ";
			$dt = 1;

		}
	}

	if(strlen($chk12) > 0){
		// uf
		if ($uf){
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_posto.estado = '".$uf."' ";
			$dt = 1;

		}
	}

	if(strlen($chk13) > 0){
		// numero_os
		if ($numero_os){
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_callcenter.sua_os ILIKE '".$numero_os."%' ";
			$dt = 1;

		}
	}

	if(strlen($chk14) > 0){
		// nota fiscal
		if ($nota_fiscal){
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_callcenter.nota_fiscal ILIKE '".$nota_fiscal."%' ";
			$dt = 1;

		}
	}

	if(strlen($chk15) > 0){
		// callcenter
		if ($callcenter){
			if ($dt == 1) $xsql = "AND ";
			else          $xsql = "OR ";

			$monta_sql .= "$xsql tbl_callcenter.callcenter = ".$callcenter." ";
			$dt = 1;

		}
	}
	//Estas duas verifica��es s�o para filtrar somente por atendimentos pendentes 

	$monta_sql .=")AND (tbl_providencia.solucionado IS FALSE OR tbl_providencia.callcenter IS NULL) ";
	
	// ordena sql padrao
	$sql .= $monta_sql;
	
//	echo "<br>".nl2br($sql);
	$res = pg_exec($con,$sql);

	$pendentes         = trim(pg_result ($res,0,0));
	echo "<br><font size='1'>Total de Chamadas Pendentes: <b>".$pendentes;
	//$concluida=$resultado_final-$pendentes;
	//echo "<br></b>Total de Chamadas Conclu�das: <b>".$concluida."</b></font>";
// 	echo "$sql";
}


echo "<br>";

include "rodape.php"; 

?>