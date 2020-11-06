<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

include 'funcoes.php';

$msg_erro = "";

if($login_fabrica != 1 ) {
	header("Location: menu_callcenter.php");
	exit;
}

$cookget = @explode("?", $REQUEST_URI);		// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookget", $cookget[1]);			/* expira qdo fecha o browser */

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

if($_POST["data_inicial_01"])		$data_inicial_01    = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_01      = trim($_POST["data_final_01"]);
if($_POST['codigo_posto'])			$codigo_posto       = trim($_POST['codigo_posto']);
if($_POST["produto_referencia"])	$produto_referencia = trim($_POST["produto_referencia"]);
if($_POST["produto_voltagem"])		$produto_voltagem   = trim($_POST["produto_voltagem"]);
if($_POST["produto_nome"])			$produto_nome       = trim($_POST["produto_nome"]);
if($_POST["numero_os"])				$numero_os          = trim($_POST["numero_os"]);
if($_POST["numero_serie"])			$numero_serie       = trim($_POST["numero_serie"]);
if($_POST["nome_revenda"])			$nome_revenda       = trim($_POST["nome_revenda"]);
if($_POST["cnpj_revenda"])			$cnpj_revenda       = trim($_POST["cnpj_revenda"]);

if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01      = trim($_GET["data_final_01"]);
if($_GET['codigo_posto'])			$codigo_posto       = trim($_GET['codigo_posto']);
if($_GET["produto_referencia"])		$produto_referencia = trim($_GET["produto_referencia"]);
if($_GET["produto_voltagem"])		$produto_voltagem   = trim($_GET["produto_voltagem"]);
if($_GET["produto_nome"])			$produto_nome       = trim($_GET["produto_nome"]);
if($_GET["numero_os"])				$numero_os          = trim($_GET["numero_os"]);
if($_GET["numero_serie"])			$numero_serie       = trim($_GET["numero_serie"]);
if($_GET["nome_revenda"])			$nome_revenda       = trim($_GET["nome_revenda"]);
if($_GET["cnpj_revenda"])			$cnpj_revenda       = trim($_GET["cnpj_revenda"]);

$layout_menu = "callcenter";
$title = "Relação de Ordens de Serviços da Revenda";

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

<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>
	<tr>
		<td align='center' width='10' bgcolor='#FFE1E1'>&nbsp;</td>
		<td align='left'><font size='1'>&nbsp; Excluídas do sistema</font></td>
	</tr>
	<tr>
		<td align='center' width='10' bgcolor='#91C8FF'>&nbsp;</td>
		<td align='left'><font size='1'>&nbsp; OSs sem fechamento há mais de 20 dias, informar "Motivo"</font></td>
	</tr>
	<tr>
		<td align='center' width='10' bgcolor='#FFCC66'>&nbsp;</td>
		<td align='left'><font size='1'>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</font></td>
	</tr>
	<tr>
		<td align='center' width='10' bgcolor='#FF0000'>&nbsp;</td>
		<td align='left'><font size='1'>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar "Motivo"</font></td>
	</tr>
</table>

<br>

<?
	// BTN_NOVA BUSCA
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_revenda_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
$sql = "SELECT * FROM (
			(
				SELECT		DISTINCT ON (tbl_os_revenda.os_revenda)
							tbl_os_revenda.os_revenda                                                ,
							tbl_os_revenda.sua_os                                                    ,
							tbl_os_revenda.explodida                                                 ,
							to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data               ,
							tbl_os_revenda.digitacao                           AS digitacao          ,
							tbl_posto.nome                                     AS posto_nome         ,
							tbl_posto_fabrica.codigo_posto                                           ,
							tbl_os_revenda.revenda                                                   ,
							tbl_revenda.nome                                   AS revenda_nome       ,
							tbl_revenda.cnpj                                   AS revenda_cnpj       ,
							NULL                                               AS consumidor_revenda ,
							current_date                                       AS data_fechamento    ,
							TRUE                                               AS excluida           ,
							tbl_os_revenda_item.serie                          AS serie              ,
							tbl_produto.referencia                             AS produto_referencia ,
							tbl_produto.voltagem                               AS produto_voltagem   ,
							0                                                  AS qtde_item          
				FROM		tbl_os_revenda
				JOIN		tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
				JOIN		tbl_revenda USING (revenda)
				JOIN		tbl_posto   USING (posto)
				JOIN		tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											  AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN		tbl_produto USING (produto)
				WHERE		tbl_os_revenda.fabrica = $login_fabrica
			) UNION (
				SELECT	tbl_os.os                                    AS os_revenda         ,
						tbl_os.sua_os                                                      ,
						NULL                                         AS explodida          ,
						to_char (tbl_os.data_abertura ,'DD/MM/YYYY') AS data               ,
						tbl_os.data_digitacao                        AS digitacao          ,
						tbl_posto.nome                               AS posto_nome         ,
						tbl_posto_fabrica.codigo_posto                                     ,
						NULL                                         AS revenda            ,
						tbl_os.revenda_nome                                                ,
						tbl_os.revenda_cnpj                                                ,
						tbl_os.consumidor_revenda                                          ,
						tbl_os.data_fechamento                                             ,
						tbl_os.excluida                                                    ,
						tbl_os.serie                                                       ,
						tbl_produto.referencia                       AS produto_referencia ,
						tbl_produto.voltagem                         AS produto_voltagem   ,
						(
							SELECT COUNT (tbl_os_item.*) AS qtde_item
							FROM   tbl_os_item
							JOIN   tbl_os_produto USING (os_produto)
							WHERE  tbl_os_produto.os = tbl_os.os
						)                                            AS qtde_item    
				FROM    tbl_os
				JOIN    tbl_os_extra USING (os)
				JOIN    tbl_posto USING (posto)
				JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
										  AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN	tbl_produto USING (produto)
				WHERE   tbl_os.consumidor_revenda = 'R'
				AND     tbl_os.fabrica            = $login_fabrica
			)
		) AS a
		WHERE (1=2 ";

$msg = "";

if(strlen($chk1) > 0){
	//dia atual
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

	$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
	$resX = pg_exec ($con,$sqlX);
	#  $dia_hoje_final = pg_result ($resX,0,0);

	$monta_sql .=" OR (a.digitacao BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " e OS Revenda lançadas hoje";
}

if(strlen($chk2) > 0){
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (a.digitacao BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;

	$msg .= " e OS Revenda lançadas ontem";

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

	$monta_sql .=" OR (a.digitacao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e OS Revenda lançadas nesta semana";

}

if(strlen($chk4) > 0){
	// do mês
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	$monta_sql .= "OR (a.digitacao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$dt = 1;

	$msg .= " e OS Revenda lançadas neste mês";

}

if(strlen($chk5) > 0){
	// entre datas
	if((strlen($data_inicial_01) == 10) && (strlen($data_final_01) == 10)){
		$data_inicial = $data_inicial_01;
		$data_final   = $data_final_01;

		$monta_sql .= "OR (a.digitacao BETWEEN fnc_formata_data('$data_inicial') AND fnc_formata_data('$data_final')) ";
		$dt = 1;

	 	$msg .= " e OS Revenda lançadas entre os dias $data_inicial e $data_final ";

	}
}

if(strlen($chk6) > 0){
	// codigo do posto
	if ($codigo_posto){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.codigo_posto = '". $codigo_posto ."' ";
		$dt = 1;

		$msg .= " e OS Revenda lançadas pelo posto $codigo_posto ";

	}
}

if(strlen($chk7) > 0){
	// referencia do produto
	if ($produto_referencia) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.produto_referencia = '". $produto_referencia ."' AND a.produto_voltagem = '". $produto_voltagem ."'";
		$dt = 1;

		$msg .= " e OS Revenda lançadas com produto $produto_referencia ";

	}
}

if(strlen($chk8) > 0){
	// nome_revenda
	if ($nome_revenda){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.revenda_nome = '". $nome_revenda ."' ";
		$dt = 1;

	}

	// cnpj_revenda
	if ($cnpj_revenda){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.revenda_cnpj = '". $cnpj_revenda ."' ";
		$dt = 1;

	}

	$msg .= " e OS Revenda lançadas pela revenda $cnpj_revenda - $nome_revenda ";

}

if(strlen($chk9) > 0){
	// numero de serie do produto
	if ($numero_serie){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.serie = '". $numero_serie ."' ";
		$dt = 1;

		$msg .= " e OS Revenda lançadas com produto série $numero_serie ";

	}
}

if(strlen($chk10) > 0){
	// numero_os
	if ($numero_os){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql a.sua_os = '". $numero_os ."' ";
		$dt = 1;

		$msg .= " e OS Revenda lançadas com número $numero_os ";

	}
}

// ordena sql padrao
$sql .= $monta_sql;
$sql .= ") ORDER BY substring(a.sua_os,1,5) ASC, a.os_revenda ASC";

# echo nl2br($sql);

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

// ##### PAGINACAO ##### //
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 10;				// máximo de links à serem exibidos
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
	echo "	<TD colspan=8>$msg</TD>\n";
	echo "</TR>\n";

	echo "<TR class='menu_top'>\n";
	echo "	<TD width='090'>OS</TD>\n";
	echo "	<TD width='070'>DATA</TD>\n";
	echo "	<TD width='180'>CONSUMIDOR</TD>\n";
	echo "	<TD>POSTO</TD>\n";
	echo "	<TD>ITEM</TD>\n";
	echo "	<TD width='170' colspan='2'>AÇÕES</TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++)
	{
		$os_revenda         = trim(pg_result ($res,$i,os_revenda));
		$data               = trim(pg_result ($res,$i,data));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$revenda_nome       = trim(pg_result ($res,$i,revenda_nome));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		$explodida          = trim(pg_result ($res,$i,explodida));
		$consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
		$data_fechamento    = trim(pg_result($res,$i,data_fechamento));
		$excluida           = trim(pg_result($res,$i,excluida));
		$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
		$qtde_item          = trim(pg_result($res,$i,qtde_item));

		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}

		if (strlen($consumidor_revenda) > 0) {

			if ($excluida == "t") $cor = "#FFE1E1";

			// verifica se nao possui itens com 5 dias de lancamento...
			$aux_data_abertura = fnc_formata_data_pg($data);

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
					WHERE tbl_os.os = $os_revenda
					AND tbl_os.data_abertura::date >= '$data_consultar'";
			$resItem = pg_exec($con,$sql);

			$itens = pg_result($resItem,0,total_item);

			if ($itens == 0 and $data_consultar > $data_hj_mais_5) $cor = "#FFCC66";

			$mostra_motivo = 2;

			// verifica se está sem fechamento ha 20 dias ou mais da data de abertura...
			if (strlen($data_fechamento) == 0 AND $mostra_motivo == 2 AND $login_fabrica == 1) {
				$aux_data_abertura = fnc_formata_data_pg($data);

				$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '20 days', 'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$data_consultar = pg_result($resX,0,0);

				$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$data_atual = pg_result ($resX,0,0);

				if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
					$mostra_motivo = 1;
					$cor = "#91C8FF";
				}
			}

			// Se estiver acima dos 30 dias, nao exibira os botoes...
			if (strlen($data_fechamento) == 0 AND $login_fabrica == 1) {
				$aux_data_abertura = fnc_formata_data_pg($data);

				$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '30 days', 'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$data_consultar = pg_result($resX,0,0);

				$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$data_atual = pg_result ($resX,0,0);

				if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
					$mostra_motivo = 1;
					$cor = "#ff0000";
				}
			}

		}

		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		echo "<TD nowrap>$codigo_posto$sua_os</TD>\n";
		echo "<TD>$data</TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$revenda_nome\">".substr($revenda_nome,0,25)."</ACRONYM></TD>\n";
		echo "<TD nowrap><ACRONYM TITLE=\"$posto_nome\">".substr($posto_nome,0,25)."</ACRONYM></TD>\n";

		if (strlen($consumidor_revenda) == 0) {
			echo "<TD>&nbsp;</TD>";

			echo "<TD width=85>";
			if (strlen($explodida) == 0) echo "<a href='os_revenda.php?os_revenda=$os_revenda'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a>";
			echo "</TD>\n";

			echo "<TD width=85><a href='os_revenda_print.php?os_revenda=$os_revenda' target='_new'><img src='imagens_admin/btn_imprimir_".$btn.".gif'></a></TD>\n";
			echo "</TR>\n";
		}else{
			echo "<TD align='center'>";
			if ($qtde_item > 0) echo"<img src='imagens/img_ok.gif' alt='OS já foi impressa'>";
			else                echo"&nbsp;";
			echo "</TD>";

			echo "<TD width=85>";
			if ($excluida == "f" OR strlen($excluida) == 0) echo "<a href='os_cadastro.php?os=$os_revenda' target='_blank'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a>";
			echo "</TD>\n";

			echo "<TD width=85>";
			if ($excluida == "f" OR strlen($excluida) == 0) echo "<a href='os_press.php?os=$os_revenda'><img src='imagens/btn_consultar_".$btn.".gif'></a>";
			echo "</TD>\n";
		}
	}
}

echo "</TABLE>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='os_revenda_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
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
?>

<br>

<? include "rodape.php"; ?>