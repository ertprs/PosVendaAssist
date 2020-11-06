<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
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


if(strlen($chk1) == 0 AND strlen($chk2) == 0 AND strlen($chk3) == 0 AND strlen($chk4) == 0){
	$data_inicial = $_GET["data_inicial"];
	$data_final   = $_GET["data_final"];
	if (strlen($data_final) > 0 AND strlen($data_inicial) > 0 and $data_final <> "dd/mm/aaaa" and  $data_inicial <> "dd/mm/aaaa") {
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
		$xdata_inicial = "$xdata_inicial 00:00:00";

		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
		$xdata_final   =  "$xdata_final 23:59:59";
		

	}else $msg_erro = "Selecione a data para fazer a pesquisa";
}
$layout_menu = "callcenter";
$title = "Relação de Atendimentos Lançados";

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

<script language="javascript">

function informacoes2(mesmo_dia,dia1,dias2,dias3,mais_dias,total_ocorrencias) {
	var url = "";
        url = "callcenter_consulta_popup.php?mesmo_dia=" + mesmo_dia + '&dia1=' + dia1 + '&dias2=' + dias2 + '&dias3=' + dias3 + '&mais_dias=' + mais_dias + '&total_ocorrencias=' + total_ocorrencias;
        janela = window.open(url,"_blank","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=500,top=18,left=0");
        janela.focus();
}

function informacoes(total,resp_dada,pendentes) {
	var url = "";
        url = "callcenter_consulta_popup.php?total=" + total + '&resp_dada=' + resp_dada + '&pendentes=' + pendentes;
        janela = window.open(url,"_blank","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=650,height=550,top=18,left=0");
        janela.focus();
}

</script>


<?

	// BTN_NOVA BUSCA
	echo "<TABLE width='600' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='callcenter_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

if(strlen($msg_erro) == 0){
	$cond1 = " 1 = 1 ";
	$cond2 = " 1 = 1 ";
	$cond3 = " 1 = 1 ";
	$cond4 = " 1 = 1 ";
	$cond5 = " 1 = 1 ";
	$cond6 = " 1 = 1 ";
	$cond7 = " 1 = 1 ";
	$cond8 = " 1 = 1 ";
	$cond9 = " 1 = 1 ";
	$cond10 = " 1 = 1 ";
	$cond11 = " 1 = 1 ";
	$cond12 = " 1 = 1 ";
	$cond13 = " 1 = 1 ";
	$cond14 = " 1 = 1 ";
	$cond15 = " 1 = 1 ";

	if ($situacao=="PENDENTES"){
		$cond1 = " tbl_hd_chamado.status <> 'Resolvido'";
	}
	if ($situacao=="SOLUCIONADOS"){
		$cond1 = " tbl_hd_chamado.status = 'Resolvido'";
	}


	if(strlen($chk1) > 0){
		//dia atual
		$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
		$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";
	
		$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
		$resX = pg_exec ($con,$sqlX);
		#  $dia_hoje_final = pg_result ($resX,0,0);
	
		$cond1 = " tbl_hd_chamado.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final' ";
	}
	
	if(strlen($chk2) > 0) {
		// dia anterior
		$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
		$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";
	
		$cond2 =" tbl_hd_chamado.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final' ";	
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
	
		$cond3 =" tbl_hd_chamado.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final' ";
	
	}
	
	if(strlen($chk4) > 0){
		// do mês
		$mes_inicial = trim(date("Y")."-".date("m")."-01");
		$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));
	
		$cond4 = " tbl_hd_chamado.data BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59' ";

	}

	if(strlen($chk1) == 0 AND strlen($chk2) == 0 AND strlen($chk3) == 0 AND strlen($chk4) == 0){
		$cond5 = "  tbl_hd_chamado.data BETWEEN '$xdata_inicial' AND '$xdata_final'  ";
	}


	if(strlen($chk6) > 0){
		// codigo do posto
		if (strlen($codigo_posto) > 0){
			$cond6 = " tbl_posto_fabrica.codigo_posto = '". $codigo_posto."' ";
		}
	}
	
	if(strlen($chk7) > 0){
		// referencia do produto
		if ($produto_referencia) {
			$sql = "Select produto from tbl_produto where referencia_pesquisa = '$produto_referencia' ";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$produto = pg_result($res,0,0);
				$cond7 = " tbl_hd_chamado_extra.produto = $produto ";
			}

		}
	}
	
	if(strlen($chk8) > 0){
		// numero de serie do produto
		if ($numero_serie) {
			$cond8 = " tbl_hd_chamado_extra.serie = '$numero_serie' ";
		}
	}
	
	if(strlen($chk9) > 0){
		// nome_consumidor
		if ($nome_consumidor){
			//$monta_sql .= "$xsql tbl_cliente.nome ilike '%".$cliente."%' ";
			$cond9 = "  tbl_cliente.nome ILIKE '%".$nome_consumidor."%' ";
		}
	}
	
	if(strlen($chk10) > 0){
		// cpf_consumidor
		if ($cpf_consumidor){
			$cond10 = " tbl_cliente.cpf LIKE '". $cpf_consumidor."%' ";
		}
	}
	

	if(strlen($chk13) > 0){
		// numero_os
		if ($numero_os){
			$cond13 = " tbl_os.sua_os ILIKE '".$numero_os."%' ";
		}
	}
	
	if(strlen($chk14) > 0){
		// nota fiscal
		if ($nota_fiscal){
			$cond14 = " tbl_hd_chamado_extra.nota_fiscal ILIKE '".$nota_fiscal."%' ";
		}
	}
	
	if(strlen($chk15) > 0){
		// nota fiscal
		if ($callcenter){
			$cond15 = " tbl_hd_chamado.hd_chamado = $callcenter";
		}
	}

	$sql = "SELECT tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.titulo,
					tbl_hd_chamado.status,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
					tbl_hd_chamado_extra.os,
					tbl_produto.descricao as produto_descricao,
					tbl_produto.referencia as produto_referencia,
					tbl_cliente.nome as cliente_nome,
					tbl_hd_chamado_extra.sua_os,
					tbl_posto_fabrica.codigo_posto
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
			LEFT JOIN tbl_cliente on tbl_cliente.cliente = tbl_hd_chamado_extra.cliente
			LEFT JOIN tbl_cidade on tbl_cidade.cidade = tbl_cliente.cidade
			LEFT JOIN tbl_posto_fabrica on tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
			and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND $cond1
			AND $cond2
			AND $cond3
			AND $cond4
			AND $cond5
			AND $cond6
			AND $cond7
			AND $cond8
			AND $cond9
			AND $cond10
			AND $cond11
			AND $cond12
			AND $cond13
			AND $cond14
			AND $cond15
		
	";
//echo nl2br($sql);

	$sql .= " ORDER BY tbl_hd_chamado.hd_chamado DESC ";
//	echo $sql;
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";
	
	//echo "<br>".nl2br($sql)."<br><br>".nl2br($sqlCount)."<br>";
	
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
		echo "<TABLE width='600' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
	}else{
		echo "<TABLE width='600' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan=7>$msg</TD>\n";
		echo "</TR>\n";
		echo "<TR class='menu_top'>\n";
		echo "<TD>DATA</TD>\n";
		echo "<TD>PRODUTO</TD>\n";
		echo "<TD>Nº CHAMADO</TD>\n";
		echo "<TD>CLIENTE</TD>\n";
		echo "<TD>OS</TD>\n";
		echo "<TD>STATUS</TD>\n";
		echo "<TD width='170'>AÇÃO</TD>\n";
		echo "</TR>\n";
	
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			$callcenter         = trim(pg_result ($res,$i,hd_chamado));
			$data               = trim(pg_result ($res,$i,data));
			$sua_os             = trim(pg_result ($res,$i,sua_os));
			$os                 = trim(pg_result ($res,$i,os));
			$serie              = trim(pg_result ($res,$i,serie));
			$consumidor_nome    = trim(pg_result ($res,$i,cliente_nome));
			$posto_nome         = trim(pg_result ($res,$i,codigo_posto));
			$produto_nome       = trim(pg_result ($res,$i,produto_descricao));
			$produto_referencia = trim(pg_result ($res,$i,produto_referencia));
			$status             = trim(pg_result ($res,$i,status));
	
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
			echo "$status";
			echo "</TD>\n";
			echo "<TD width=85 align=center><a href='cadastra_callcenter.php?callcenter=$callcenter' target='blank'><img src='imagens/btn_consultar_".$btn.".gif'></a></TD>\n";
			echo "</TR>\n";
		}
		echo "</TABLE>\n";
	}
}else{
	echo "<font color='#FF0000' size='3'>$msg_erro</font>";
}
echo "<TABLE width='600' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<TR class='table_line'>";
echo "<TD align='center' background='#D9E2EF'>";
echo "<a href='callcenter_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
echo "</td>";
echo "</TR>";
echo "</TABLE>";

if(strlen($msg_erro)==0){
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
}


include "rodape.php"; 

?>