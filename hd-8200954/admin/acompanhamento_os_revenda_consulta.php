<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$msg_erro = "";

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
if($_POST["produto_nome"])			$produto_nome       = trim($_POST["produto_nome"]);
if($_POST["numero_os"])				$numero_os          = trim($_POST["numero_os"]);
if($_POST["numero_nf"])				$numero_nf          = trim($_POST["numero_nf"]);
if($_POST["nome_revenda"])			$nome_revenda       = trim($_POST["nome_revenda"]);
if($_POST["cnpj_revenda"])			$cnpj_revenda       = trim($_POST["cnpj_revenda"]);

if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01      = trim($_GET["data_final_01"]);
if($_GET['codigo_posto'])			$codigo_posto       = trim($_GET['codigo_posto']);
if($_GET["produto_referencia"])		$produto_referencia = trim($_GET["produto_referencia"]);
if($_GET["produto_nome"])			$produto_nome       = trim($_GET["produto_nome"]);
if($_GET["numero_os"])				$numero_os          = trim($_GET["numero_os"]);
if($_GET["numero_nf"])				$numero_nf          = trim($_GET["numero_nf"]);
if($_GET["nome_revenda"])			$nome_revenda       = trim($_GET["nome_revenda"]);
if($_GET["cnpj_revenda"])			$cnpj_revenda       = trim($_GET["cnpj_revenda"]);

$layout_menu = "gerencia";
$title = "Acompanhamento de OS´s de revenda";

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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

</style>

<?

	// BTN_NOVA BUSCA
	/*echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='acompanhamento_os_revenda_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";*/

// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
$sql = "SELECT  tbl_os.os                                                   ,
				tbl_os.sua_os                                               ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
				to_char(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada       ,
				tbl_posto_fabrica.codigo_posto AS codigo_posto,
				tbl_posto_fabrica.nome_fantasia,
				tbl_posto.nome as nome_posto,
				tbl_produto.referencia                                      ,
				tbl_produto.descricao                                       ,
				tbl_produto.mao_de_obra                                     ,
				tbl_os.nota_fiscal                                          ,
				tbl_os.serie                                                ,
				tbl_os.revenda_nome                     AS revenda_nome
		FROM    tbl_os
		JOIN    tbl_revenda          ON tbl_revenda.revenda            = tbl_os.revenda
		JOIN    tbl_produto          ON tbl_produto.produto            = tbl_os.produto
		JOIN    tbl_posto            ON tbl_posto.posto                = tbl_os.posto
		JOIN    tbl_posto_fabrica    ON tbl_posto.posto                = tbl_posto_fabrica.posto
									AND tbl_posto_fabrica.fabrica      = $login_fabrica
		WHERE   (tbl_os.sua_os ILIKE '%-%' OR tbl_os.consumidor_revenda = 'R')
		AND     tbl_os.fabrica = $login_fabrica
		AND     (1=2 ";

$msg = "";
$monta_sql = '';

if(strlen($chk1) > 0){
	//dia atual
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje = pg_fetch_result($resX, 0, 0);
	$dia_hoje_inicio = $dia_hoje . ' 00:00:00';
	$dia_hoje_final  = $dia_hoje . ' 23:59:59';

	/*$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
	$resX = pg_exec ($con,$sqlX);*/
	#  $dia_hoje_final = pg_result ($resX,0,0);

	$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " e OS Revenda lançadas hoje";

}

if(strlen($chk2) > 0){
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem = pg_fetch_result($resX, 0, 0);
	$dia_ontem_inicial = $dia_ontem . ' 00:00:00';
	$dia_ontem_final   = $dia_ontem . ' 23:59:59';

	$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";

	if (!empty($chk1)) {
		$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$dia_ontem_inicial' AND '$dia_hoje_final' ) ";
	}

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
	$dia_semana_inicial = pg_result ($resX,0,0);
	$data_semana_inicial = $dia_semana_inicial . ' 00:00:00';

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_final = pg_result ($resX,0,0);
   	$data_semana_final = $dia_semana_final . ' 23:59:59';

	$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$data_semana_inicial' AND '$data_semana_final') ";
	$dt = 1;

	$msg .= " e OS Revenda lançadas nesta semana";

}

if(strlen($chk4) > 0){
	// do mês
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

	$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$dt = 1;

	$msg .= " e OS Revenda lançadas neste mês";

}

if(strlen($chk5) > 0){
	// entre datas
	if((strlen($data_inicial_01) == 10) && (strlen($data_final_01) == 10)){
		$data_inicial = $data_inicial_01;
		$data_final   = $data_final_01;

		//Início Validação de Datas
		if(!$data_inicial OR !$data_final)
			$erro = "Data Inválida";
		if(strlen($erro)==0){
			$dat = explode ("/", $data_inicial );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
		}
		if(strlen($erro)==0){
			$dat = explode ("/", $data_final );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
		}
		if(strlen($erro)==0){
			$d_ini = explode ("/", $data_inicial);//tira a barra
			$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $data_final);//tira a barra
			$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($nova_data_final < $nova_data_inicial){
				$erro = "Data Inválida.";
			}

		}


		if (!empty($chk_opt1)) {
			$data_compara1 = new DateTime($dia_hoje);
			$data_compara2 = new DateTime($nova_data_inicial);

			if ($data_compara1 < $data_compara2) {
				$nova_data_inicial = $dia_hoje;
			}

			$data_compara2 = new DateTime($nova_data_final);

			if ($data_compara1 > $data_compara2) {
				$nova_data_final = $dia_hoje;
			}
		}

		if (!empty($chk_opt2)) {
			$data_compara1 = new DateTime($dia_ontem);
			$data_compara2 = new DateTime($nova_data_inicial);

			if ($data_compara1 < $data_compara2) {
				$nova_data_inicial = $dia_ontem;
			}

			$data_compara2 = new DateTime($nova_data_final);

			if ($data_compara1 > $data_compara2) {
				$nova_data_final = $dia_ontem;
			}
		}

		if (!empty($chk_opt3)) {
			$data_compara1 = new DateTime($dia_semana_inicial);
			$data_compara2 = new DateTime($nova_data_inicial);

			if ($data_compara1 < $data_compara2) {
				$nova_data_inicial = $dia_semana_inicial;
			}

			$data_compara1 = new DateTime($dia_semana_final);
			$data_compara2 = new DateTime($nova_data_final);

			if ($data_compara1 > $data_compara2) {
				$nova_data_final = $dia_semana_final;
			}

		}

		if (!empty($chk_opt4)) {
			$data_compara1 = new DateTime($mes_inicial);
			$data_compara2 = new DateTime($nova_data_inicial);

			if ($data_compara1 < $data_compara2) {
				$nova_data_inicial = $mes_inicial;
			}

			$data_compara1 = new DateTime($mes_final);
			$data_compara2 = new DateTime($nova_data_final);

			if ($data_compara1 > $data_compara2) {
				$nova_data_final = $mes_final;
			}

		}

		$data_inicial = $nova_data_inicial . ' 00:00:00';
		$data_final = $nova_data_final . ' 23:59:59';

		$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final') ";
		$dt = 1;

	 	$msg .= " e OS Revenda lançadas entre os dias $data_inicial e $data_final ";

	}

	else
		$erro = "Data Inválida";
}

if(strlen($chk6) > 0){
	// codigo do posto
	if ($codigo_posto){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_posto_fabrica.codigo_posto = '". $codigo_posto ."' ";
		$dt = 1;

		$msg .= " e OS Revenda lançadas pelo posto $codigo_posto ";

	}
}

if(strlen($chk7) > 0){
	// referencia do produto
	if ($produto_referencia) {
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_produto.referencia = '". $produto_referencia ."' ";
		$dt = 1;

		$msg .= " e OS Revenda lançadas com produto $produto_referencia ";

	}
}

if(strlen($chk8) > 0){
	// nome_revenda
	if ($nome_revenda){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_revenda.nome = '". $nome_revenda ."' ";
		$dt = 1;

	}

	// cnpj_revenda
	if ($cnpj_revenda){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_revenda.cnpj = '". $cnpj_revenda ."' ";
		$dt = 1;

	}

	$msg .= " e OS Revenda lançadas pela revenda $cnpj_revenda - $nome_revenda ";

}

if(strlen($chk9) > 0){
	// numero de serie do produto
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql tbl_os.finalizada ISNULL ";
	$dt = 1;
}

if(strlen($chk10) > 0){
	// numero_os
	if ($numero_os){
		if ($dt == 1) $xsql = "AND ";
		else          $xsql = "OR ";

		$monta_sql .= "$xsql tbl_os.sua_os ilike '". $numero_os ."%' ";
		$dt = 1;

		$msg .= " e OS Revenda lançadas com número $numero_os ";

	}
}

// ordena sql padrao
$sql .= $monta_sql;
$sql .= ")
		ORDER BY lpad(tbl_os.sua_os,20,'0') ASC";
$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

// echo "<br>".nl2br($sql); exit;

// ##### PAGINACAO ##### //
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 10;				// máximo de links à serem exibidos
$max_res   = 100;				// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res_xls = @pg_exec($con, $sql);
if(strlen(pg_errormessage($con))==0){
	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
}

// ##### PAGINACAO ##### //
if (@pg_numrows($res) == 0) {
	if (strlen($erro)>0)
		echo "<TABLE width='700' height='50' align='center'><TR class='msg_erro'><TD>".$erro."</TD></TR></TABLE>";
	else
		echo "<TABLE width='700' height='50' align='center'><TR><TD><center>Nenhum resultado encontrado.</center></TD></TR></TABLE>";
}else{

	//MOSTRA NA TELA ------------------------------------------------------
	echo "<table width='700px' height=\"18\" align='center' class='tabela' cellspacing='2' cellpadding='2'>";
	echo "<tr class='titulo_coluna'>";
	echo "<td><b>OS Revenda<b></td>";
	if ($login_fabrica == 81){
		echo "<td><b>Posto</b></td>";
	}
	echo "<td align='left'><b>Produto</b></td>";
	echo "<td><b>Nota Fiscal</b></td>";
	if($login_fabrica==11) echo "<td><b>Mão de Obra Produto</b></td>";
	echo "<td align='left'><b>Revenda</b></td>";
	if($login_fabrica==11 or $login_fabrica == 81) echo "<td><b>Data Abertura</b></td>";
	echo "<td><b>Finalizada</b></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$os              = trim(pg_result($res,$i,os));
		$sua_os          = trim(pg_result($res,$i,sua_os));
		$data_abertura   = trim(pg_result($res,$i,data_abertura));
		$finalizada      = trim(pg_result($res,$i,finalizada));
		$referencia      = trim(pg_result($res,$i,referencia));
		$descricao       = trim(pg_result($res,$i,descricao));
		$nota_fiscal     = trim(pg_result($res,$i,nota_fiscal));
		$serie           = trim(pg_result($res,$i,serie));
		$revenda         = trim(strtoupper(pg_result($res,$i,revenda_nome)));
		$mao_de_obra     = number_format(pg_result($res,$i,mao_de_obra), 2, ",", ".");

		$descricao_exibe = substr($descricao, 0,15);
		$revenda_exibe   = substr($revenda, 0,15);
		if ($login_fabrica == 81){
			$codigo_posto  = trim(pg_fetch_result($res, $i, 'codigo_posto'));
			if (strlen(trim(pg_fetch_result($res, $i, 'nome_fantasia')))>0){
				$nome_fantasia_exibe = " - ".substr(trim(pg_fetch_result($res, $i, 'nome_fantasia')),0,10);
				$nome_fantasia = " - ".trim(pg_fetch_result($res, $i, 'nome_fantasia'));
			}else{
				$nome_fantasia_exibe = " - ".substr(trim(pg_fetch_result($res, $i, 'nome_posto')),0,10);
				$nome_fantasia = " - ".trim(pg_fetch_result($res, $i, 'nome_posto'));
			}

			$nome_do_posto_exibe = $codigo_posto.$nome_fantasia_exibe;
			$nome_do_posto = $codigo_posto.$nome_fantasia;

		}

		$cor = ($i % 2) ? "#D3E2FF" : "#F1F4FA";

		echo "<tr style='background-color:$cor'>";
		echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></td>";
		if ($login_fabrica==81) echo "<td nowrap><label title='$nome_do_posto' > $nome_do_posto_exibe </td>";
		echo "<td align='left' nowrap><label title='$referencia - $descricao'>$referencia - $descricao_exibe</label></td>";
		echo "<td>$nota_fiscal</td>";
		if($login_fabrica==11) echo "<td>$mao_de_obra</td>";
		echo "<td align='left' nowrap><label title='$revenda'>$revenda_exibe</label></td>";
		if($login_fabrica==11 or $login_fabrica == 81) echo "<td>$data_abertura</td>";
		echo "<td>$finalizada</td>";
		echo "</tr>";

		flush();
	}
	echo "</table>";

	//GERA XLS HD 36302 ----------------------------------------------------------
	if($login_fabrica==11 or $login_fabrica==15){
		flush();

		$data = date ("dmY");

		echo `rm /tmp/assist/acompanhamento_os-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/acompanhamento_os-$login_fabrica.html","w");

		fputs ($fp,"<table width=\"700\" height=\"18\" align='center' class='tabela' cellspacing='1'>");
		fputs ($fp,"<tr class='titulo_coluna'>");
		fputs ($fp,"<td><b>OS Revenda<b></td>");
		fputs ($fp,"<td align='left'><b>Produto</b></td>");
		fputs ($fp,"<td><b>Nota Fiscal</b></td>");
		fputs ($fp,"<td><b>Mão de ObraA</b></td>");
		fputs ($fp,"<td align='left'><b>Revenda</b></td>");
		fputs ($fp,"<td><b>Data Abertura</b></td>");
		fputs ($fp,"<td><b>Finalizada</b></td>");
		fputs ($fp,"</tr>");

		for ($x = 0 ; $x < pg_numrows ($res_xls) ; $x++){
			$os              = trim(pg_result($res_xls,$x,os));
			$sua_os          = trim(pg_result($res_xls,$x,sua_os));
			$data_abertura   = trim(pg_result($res_xls,$x,data_abertura));
			$finalizada      = trim(pg_result($res_xls,$x,finalizada));
			$referencia      = trim(pg_result($res_xls,$x,referencia));
			$descricao       = trim(pg_result($res_xls,$x,descricao));
			$nota_fiscal     = trim(pg_result($res_xls,$x,nota_fiscal));
			$serie           = trim(pg_result($res_xls,$x,serie));
			$revenda         = trim(strtoupper(pg_result($res_xls,$x,revenda_nome)));
			$mao_de_obra     = number_format(pg_result($res_xls,$x,mao_de_obra), 2, ",", ".");

			fputs ($fp,"<tr>");
			fputs ($fp,"<td nowrap>$sua_os</td>");
			fputs ($fp,"<td align='left' nowrap>$referencia - $descricao</td>");
			fputs ($fp,"<td>$nota_fiscal</td>");
			fputs ($fp,"<td>$mao_de_obra</td>");
			fputs ($fp,"<td align='left' nowrap>$revenda</td>");
			fputs ($fp,"<td>$data_abertura</td>");
			fputs ($fp,"<td>$finalizada</td>");
			fputs ($fp,"</tr>");

			flush();
		}
		fputs ($fp,"</table>");
		fclose ($fp);

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/acompanhamento_os-$login_fabrica.$data.xls /tmp/assist/acompanhamento_os-$login_fabrica.html`;
	}
	//--------------------------------------------------------------------------
}

echo "</TABLE>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0' class='formulario'>";
	echo "<TR>";
	echo "<td>";
	echo "<a href='acompanhamento_os_revenda_parametros.php'><input type='button' style='background:url(imagens_admin/btn_nova_busca.gif); width:400px;cursor:pointer;' value='&nbsp;'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

	if($login_fabrica==11 or $login_fabrica==15){
		echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/acompanhamento_os-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}
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
