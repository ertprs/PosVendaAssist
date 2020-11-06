<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include '../admin/funcoes.php';
include('phpHtmlChart.php');
$suporte = 432;

if($login_fabrica<>10) header ("Location: index.php");


$TITULO = "Lista de Chamados";

include "menu.php";
//<meta http-equiv="refresh" content="300">
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$("#data_inicio").maskedinput("99/99/9999");
		$("#data_fim").maskedinput("99/99/9999");
	});
</script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();
});


 $(document).ready(function(){
   $(".relatorio tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
   //$(".relatorio tr:even").addClass("alt");
   });
   

$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio_erro").tablesorter();
});


 $(document).ready(function(){
   $(".relatorio_erro tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
   //$(".relatorio tr:even").addClass("alt");
   });



</script>
<style>
	table.relatorio {
		border-collapse: collapse;
		font-size: 1.1em;
		font-family: Verdana;
		font-size: 10px;
	}

	table.relatorio th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
		font-family: Verdana;
		font-size: 10px;
	}

	table.relatorio td {
		padding: 1px 5px;
		border-bottom: 1px solid #95bce2;
		font-family: Verdana;
		font-size: 10px;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.relatorio tr.alt td {
		background: #ecf6fc;
	}

	table.relatorio tr.over td {
		background: #bcd4ec;
	}
	table.relatorio tr.clicado td {
		background: #FF9933;
	}
	table.relatorio tr.sem_defeito td {
		background: #FFCC66;
	}
	table.relatorio tr.mais_30 td {
		background: #FF0000;
	}
	table.relatorio tr.erro_post td {
		background: #99FFFF;
	}
	
	</style>




	<style>
	table.relatorio_erro {
		border-collapse: collapse;
		font-size: 1.1em;
		font-family: Verdana;
		font-size: 10px;
	}

	table.relatorio_erro th {
		background: #FE7676;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
		font-family: Verdana;
		font-size: 10px;
	}

	table.relatorio_erro td {
		padding: 1px 5px;
		border-bottom: 1px solid #95bce2;
		font-family: Verdana;
		font-size: 10px;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.relatorio_erro tr.alt td {
		background: #ecf6fc;
	}

	table.relatorio_erro tr.over td {
		background: #bcd4ec;
	}
	table.relatorio_erro tr.clicado td {
		background: #FF9933;
	}
	table.relatorio_erro tr.sem_defeito td {
		background: #FFCC66;
	}
	table.relatorio_erro tr.mais_30 td {
		background: #FF0000;
	}
	table.relatorio_erro tr.erro_post td {
		background: #99FFFF;
	}
	
	</style>
<?

$sql="SELECT *
	FROM	tbl_change_log
	LEFT jOIN tbl_fabrica ON tbl_fabrica.fabrica=tbl_change_log.fabrica
	LEFT join tbl_change_log_admin On tbl_change_log.change_log=tbl_change_log_admin.change_log AND tbl_change_log_admin.admin = $login_admin
	WHERE tbl_change_log_admin.data IS NULL ";

$res = pg_exec ($con,$sql);
if(pg_numrows($res) >0) {
	echo "<br>";
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr valign='middle' align='center'><td class='change_log'><a href='change_log_mostra.php' target='_blank'>Existem CHANGE LOG para ser lido. Clique aqui para visualizar</a></td></tr>
	</table><BR>";
}

$sql="  SELECT * 
		FROM tbl_hd_chamado
		WHERE (atendente = 399) 
		AND   (status ILIKE 'novo' OR status ILIKE '$status' OR status IS NULL)";

$res = pg_exec ($con,$sql);
$chamados_nao_atendidos = pg_numrows($res);
if (@pg_numrows($res) > 0) $msg = 'Existem '.$chamados_nao_atendidos.' chamada(s) não atendida(s)';

$status_busca    = trim($_POST['status']);
$atendente_busca = trim($_POST['atendente_busca']);
$autor_busca     = trim($_POST['autor_busca']);
$valor_chamado   = trim($_POST['valor_chamado']);
$fabrica_busca   = trim($_POST['fabrica_busca']);
$sem_prazo       = trim($_POST['sem_prazo']);
$data_inicio     = trim($_POST['data_inicio']);
$data_fim        = trim($_POST['data_fim']);
$tipo_chamado    = trim($_POST['tipo_chamado']);
//if (strlen($tipo_chamado) == 0) {
//	$tipo_chamado    = trim($_GET['tipo_chamado']);
//}

if (strlen($status_busca)    == 0) $status_busca    = trim($_GET['status']);
if (strlen($atendente_busca) == 0) $atendente_busca = trim($_GET['atendente_busca']);
if (strlen($autor_busca)     == 0) $autor_busca     = trim($_GET['autor_busca']);
if (strlen($valor_chamado)   == 0) $valor_chamado   = trim($_GET['valor_chamado']);
if (strlen($data_inicio)   == 0)   $data_inicio     = trim($_GET['data_inicio']);
if (strlen($data_fim)   == 0)      $data_fim        = trim($_GET['data_fim']);
if (strlen($fabrica_busca)   == 0) $fabrica_busca   = trim($_GET['fabrica_busca']);
if (strlen($sem_prazo)   == 0)     $sem_prazo       = trim($_GET['sem_prazo']);

###INICIO ESTATITICAS###
if($atendente_busca <> '') {
	$cond1 = " AND tbl_hd_chamado.atendente        = '$atendente_busca' ";
}
if($autor_busca <> '') {
	$cond3 = " AND tbl_hd_chamado.admin            = $autor_busca ";
}
$cond_0 = " 1=1 ";
if(strlen($valor_chamado)>0){
	if (is_numeric($valor_chamado)){
		$cond_0 = " tbl_hd_chamado.hd_chamado = '$valor_chamado' ";

	}else{
		$valor_chamado = strtoupper($valor_chamado);
		$cond_0 = " upper(tbl_hd_chamado.titulo) like '%$valor_chamado%' ";
	}
}

//$cond_tipo_chamado = "1 = 1 ";
//if ($tipo_chamado == '5') {
//	$cond_tipo_chamado = "tbl_hd_chamado.tipo_chamado = 5 ";
//}
//if ($tipo_chamado == '0') {
//	$cond_tipo_chamado = "tbl_hd_chamado.tipo_chamado <> 5 ";
//}


###busca erros###
$sql = "SELECT  hd_chamado,
				tbl_hd_chamado.admin    ,
				tbl_admin.nome_completo ,
				tbl_admin.login         ,
				to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
				tbl_hd_chamado.previsao_termino AS previsao,
				to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
				to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
				tbl_hd_chamado.titulo,
				tbl_hd_chamado.status,
				tbl_hd_chamado.atendente,
				tbl_hd_chamado.exigir_resposta,
				tbl_hd_chamado.cobrar,
				tbl_hd_chamado.prioridade,
				tbl_hd_chamado.hora_desenvolvimento,
				tbl_hd_chamado.prazo_horas,
				tbl_fabrica.nome AS fabrica_nome,
				tbl_tipo_chamado.descricao as tipo_chamado_descricao,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino 
					THEN 1
					ELSE 0
				END AS atrasou,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino_interna 
					THEN 1
					ELSE 0
				END AS atrasou_interno
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND    tbl_hd_chamado.tipo_chamado = 5
		AND    tbl_hd_chamado.atendente IN (432,435)
		and $cond_0 ";

	if (strlen($data_inicio)>0 AND strlen($data_fim)>0){
		$status_busca = 'r';

		$aux_data_inicio = formata_data($data_inicio);
		$aux_data_fim    = formata_data($data_fim);

		$sql .= " AND tbl_hd_chamado.hd_chamado IN (
					SELECT chamado.hd_chamado
					FROM (
					SELECT 
					ultima.hd_chamado, 
					(SELECT data 
					FROM tbl_hd_chamado_item 
					WHERE tbl_hd_chamado_item.hd_chamado = ultima.hd_chamado 
					ORDER BY data DESC LIMIT 1) AS ultimo_interacao
					FROM (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado WHERE status='Resolvido') ultima
					) chamado
					WHERE chamado.ultimo_interacao BETWEEN '$aux_data_inicio 00:00:01' AND '$aux_data_fim 23:59:59'
				)";
	}

	if ($atendente_busca <> '' and strlen($valor_chamado)==0 ){
		$sql .= " AND tbl_hd_chamado.atendente = $atendente_busca";
	}

	if ($autor_busca <> '' and strlen($valor_chamado)==0 ){
		$sql .= " AND tbl_hd_chamado.admin = $autor_busca";
	}

	if($fabrica_busca <> '' and strlen($valor_chamado)==0 ){
		$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";
	}

	if(strlen($sem_prazo) > 0  and strlen($valor_chamado)==0 ){
		$sql .= " AND tbl_hd_chamado.prazo_horas ISNULL";
	}

	if ($status_busca=="p" and strlen($valor_chamado)==0){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Aprovação' ";
	}

	if ($status_busca=="av" and strlen($valor_chamado)==0){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Verifica' ";
	}

	if ($status_busca=="a" and strlen($valor_chamado)==0){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Análise' ";
	}

	if ($status_busca=="r" and strlen($valor_chamado)==0){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Resolvido' ";
	}

	if ($status_busca=="e" and strlen($valor_chamado)==0){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Execução' ";
	}

	if ($status_busca=="ae" and strlen($valor_chamado)==0){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Execução' ";
	}

	if ($status_busca=="c" and strlen($valor_chamado)==0){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Cancelado' ";
	}

	if ($status_busca=="n" and strlen($valor_chamado)==0){
		$sql .= " AND ((tbl_hd_chamado.status ILIKE 'Novo') OR (tbl_hd_chamado.status IS NULL))";
	}

	if ($status_busca=='' and strlen($valor_chamado)==0){
		$sql .= " AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
	}


$sql .= " ORDER BY  tbl_hd_chamado.data ASC";


$res = pg_exec ($con,$sql);

/*================================TABELA DE ESCOLHA DE STATUS============================*/
if (@pg_numrows($res) >= 0) {
	echo "<FORM METHOD='GET' ACTION='$PHP_SELF'>";

	echo "<table width = '450' align = 'center' cellpadding='0' cellspacing='0' border='0' style='font-family: verdana ; font-size:11px ; color: #666666'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' >";
	echo "	<td><b><CENTER>Pesquisa Chamados</CENTER></b></td>";
	echo "</tr>";
	echo "<tr align='left'  height ='70' valign='top'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td>";

	echo "<table border='0'  cellpadding='2' cellspacing='3' width='400' align='center' style='font-family: verdana ; font-size:11px ; color: #00000'>";

	echo "<tr >";
	echo "<td width='150'>Buscar</td>";
	echo "<td width='250'>";
	echo "<input type='text' size='26' maxlength='26' name='valor_chamado' value=''> ";
	echo "</td>";
	echo "<tr >";
/*	echo "<tr >";
	echo "<td width='150'>Data Inicial</td>";
	echo "<td width='250'>";
	echo "<input type='text' size='10' maxlength='10' name='data_inicial' value='$data_inicial'> ";
	echo "</td>";
	echo "<td width='150'>Data Final</td>";
	echo "<td width='250'>";
	echo "<input type='text' size='10' maxlength='10' name='data_final' value='$data_final'> ";
	echo "</td>";
*/	echo "<tr >";
	echo "<td width='150'>Status</td>";
	echo "<td width='250'>";
	echo "<select class='frm' style='width: 180px;' name='status'>\n";
	echo "<option value=''></option>\n";
	echo "<option value='n'>Novo</option>\n";
	echo "<option value='a'>Análise</option>\n";
	echo "<option value='ae'>Aguardando Execução</option>\n";
	echo "<option value='e'>Execução</option>\n";
	echo "<option value=''>Em Aberto</option>\n";
	echo "<option value='r'>Resolvido</option>\n";
	echo "<option value='p'>Aprovação</option>\n";
	echo "<option value='av'>Aguardando Verificação</option>\n";
	echo "<option value='c'>Cancelado</option>\n";
	echo "</td>";
	echo "</tr>";
	echo "<tr >";
	echo "<td width='150'>Atendente</td>";
	echo "<td width='250'>";

	$sqlatendente = "SELECT nome_completo,
							admin
					FROM    tbl_admin
					WHERE   tbl_admin.fabrica = 10
					AND tbl_admin.ativo is true
					AND nome_completo notnull
					ORDER BY tbl_admin.nome_completo;";
	$resatendente = pg_exec ($con,$sqlatendente);

	$atendente_busca = trim($_POST['atendente_busca']);
	if (strlen($atendente_busca)==0){
		$atendente_busca = trim($_GET['atendente_busca']);
	}

	if (pg_numrows($resatendente) > 0) {
		echo "<select class='frm' style='width: 180px;' name='atendente_busca'>\n";
		echo "<option value='' ";
		if (strlen ($atendente_busca) == 0 ) echo " SELECTED "; 
		echo ">- TODOS -</option>\n";

		for ($x = 0 ; $x < pg_numrows($resatendente) ; $x++){
			$n_admin = trim(pg_result($resatendente,$x,admin));
			$nome_atendente  = trim(pg_result($resatendente,$x,nome_completo));

			echo "<option value='$n_admin'"; 
			//if ($login_admin == $n_admin AND strlen ($atendente_busca) == 0 ) echo " SELECTED "; 
			if ($atendente_busca == $n_admin ) echo " SELECTED "; 
			echo "> $nome_atendente</option>\n";
		}
		echo "</select>";
	}

	echo "</td>";
	echo "</tr>";

	echo "<tr >";
	echo "<td width='150'>Autor</td>";
	echo "<td width='250'>";

	$sqlatendente = "SELECT nome_completo,
							admin
					 FROM    tbl_admin
					 WHERE   tbl_admin.fabrica = 10
					 and tbl_admin.ativo is true
					 ORDER BY tbl_admin.nome_completo;";
	$resatendente = pg_exec ($con,$sqlatendente);

	$autor_busca = trim($_POST['autor_busca']);
	if (strlen($autor_busca)==0){
		$autor_busca = trim($_GET['autor_busca']);
	}

	if (pg_numrows($resatendente) > 0) {
		echo "<select class='frm' style='width: 180px;' name='autor_busca'>\n";
		echo "<option value='' ";
		if (strlen ($atendente_busca) == 0 ) echo " SELECTED "; 
		echo ">- TODOS -</option>\n";

		for ($x = 0 ; $x < pg_numrows($resatendente) ; $x++){
			$n_admin = trim(pg_result($resatendente,$x,admin));
			$nome_atendente  = trim(pg_result($resatendente,$x,nome_completo));

			echo "<option value='$n_admin'"; 
			//if ($login_admin == $n_admin AND strlen ($autor_busca) == 0 ) echo " SELECTED "; 
			if ($autor_busca == $n_admin ) echo " SELECTED "; 
			echo "> $nome_atendente</option>\n";
		}
		
		echo "</select>";
	}

	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td width='150'>Fábrica</td>";
	echo "<td width='250'>";
	$sqlfabrica =  "SELECT   * 
					FROM     tbl_fabrica 
					ORDER BY nome";
	$resfabrica = pg_exec ($con,$sqlfabrica);
	$n_fabricas = pg_numrows($res);

	echo "<select class='frm' style='width: 180px;' name='fabrica_busca'>\n";
	echo "<option value=''></option>\n";
	
	for ($x = 0 ; $x < pg_numrows($resfabrica) ; $x++){
		$fabrica   = trim(pg_result($resfabrica,$x,fabrica));
		$nome      = trim(pg_result($resfabrica,$x,nome));
		echo "<option value='$nome'"; if ($fabrica_busca == $nome) echo " SELECTED "; echo ">$nome</option>\n";
	}

	echo "</select>\n";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='2' align='left'><INPUT TYPE=\"checkbox\" name=\"sem_prazo\" value=\"sem_prazo\"> Chamados sem prazo";
	echo "</td>";
	echo "<tr>";
	echo "<td colspan='2' align='center'> <INPUT TYPE=\"submit\" value=\"Pesquisar\">";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
#===========================



	echo "</table>";
	echo "</FORM>";

	##### LEGENDAS #####
	echo "<CENTER><font face='verdana' size='1'><b style='border:1px solid #666666;background-color:#FFD5CC;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Prioridade</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF3333;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Previsão Vencida</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF9966;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Risco de Previsão</b>";

	echo "<br><br><b><font color='#FF0000'>* Analistas acompanhem as previsões de término dos seus chamados, este prazo é visualizado pelo fabricante.</font></b></CENTER><br>";	


	/*--===============================TABELA DE CHAMADOS========================--*/
	echo "<table width='650' align='center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr style='font-family: arial ; color: #666666' align='center'>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> Aguardando resposta do cliente";
	echo "</td>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_azul.gif' valign='absmiddle'> Pendente Telecontrol";
	echo "</td>";

	echo "</tr>";

	echo "<tr style='font-family: arial ; color: #666666' align='center'>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> Aguardando aprovação";
	echo "</td>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_verde.gif' valign='absmiddle'> Resolvido";
	echo "</td></tr>";
	echo "</table>";
	echo "<br>";

	echo "<table width = '95%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio_erro' id='relatorio_erro' class='relatorio_erro'>";

	echo "<thead>";
	echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>CHAMADOS DE ERRO</CENTER></th></tr>";
	echo "<tr bgcolor='#D9E8FF' >";
	echo "	<th >Nº </th>";
	echo "	<th nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></th>";
	echo "	<th ><strong>Status</strong></th>";
	echo "	<th ><strong>Tipo</strong></th>";
	echo "	<th ><strong>Data</strong></th>";
	echo "	<th nowrap><strong>Autor </strong></th>";
	echo "	<th nowrap><strong>Fábrica </strong></td>";
	echo "	<th nowrap><strong>Atendente </strong></th>";
	echo "	<th nowrap ><acronym title='Horas Trabalhadas'><strong>Trab.</strong></acronym></th>";
	echo "	<th nowrap ><strong>Prazo</strong></th>";
	//	echo "	<th nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Prazo Interno</strong></td>";
	echo "	<th nowrap><strong>Previsão</strong></td>";
	echo "</tr>";
	
	echo "</thead>";
	
	if (@pg_numrows($res) > 0) {
		//inicio imprime chamados
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$hd_chamado               = pg_result($res,$i,hd_chamado);
			$admin                    = pg_result($res,$i,admin);
			$login                    = pg_result($res,$i,login);
			//$posto                    = pg_result($res,$i,posto);
			$data                     = pg_result($res,$i,data);
			$titulo                   = pg_result($res,$i,titulo);
			$status                   = pg_result($res,$i,status);
			$atendente                = pg_result($res,$i,atendente);
			$exigir_resposta          = pg_result($res,$i,exigir_resposta);
			$nome_completo            = trim(pg_result($res,$i,nome_completo));
			$fabrica_nome             = trim(pg_result($res,$i,fabrica_nome));
			$previsao_termino         = trim(pg_result($res,$i,previsao_termino));
			$previsao                 = trim(pg_result($res,$i,previsao));
			$previsao_termino_interna = trim(pg_result($res,$i,previsao_termino_interna));
			$atrasou                  = trim(pg_result($res,$i,atrasou));
			$atrasou_interno          = trim(pg_result($res,$i,atrasou_interno));
			$cobrar                   = trim(pg_result($res,$i,cobrar));
			$hora_desenvolvimento     = trim(pg_result($res,$i,hora_desenvolvimento));
			$tipo_chamado_descricao   = trim(pg_result($res,$i,tipo_chamado_descricao));
			$prazo_horas              = pg_result($res,$i,prazo_horas);
			$prioridade               = pg_result($res,$i,prioridade);
			
			$wsql ="select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) 
					from tbl_hd_chamado_atendente 
					join tbl_admin using(admin)
					where hd_chamado = $hd_chamado
					and   responsabilidade in ('Analista de Help-Desk','Programador')";
			$wres = pg_exec($con, $wsql);
			
			if(pg_numrows($wres)>0)
			$horas= pg_result ($wres,0,0);	
			if(strlen($horas)>0){
				$xhoras = explode(":",$horas);
				$horas = $xhoras[0].":".$xhoras[1];
			}

			if ($status == 'Aprovação' OR $status == 'Resolvido' OR $status == 'Cancelado'){
				$atrasou = 0;
			}

			if ($atrasou == 0 AND $chamados_atrasados==1){
				//break;
			}
		
			$sql2 = "SELECT nome_completo, admin
				FROM	tbl_admin
				WHERE	admin='$atendente'";
			$res2 = pg_exec ($con,$sql2);	
			$xatendente            = pg_result($res2,0,nome_completo);
			$xxatendente = explode(" ", $xatendente);

			$cor='#F2F7FF';
			if ($i % 2 == 0) $cor = '#FFFFFF';

			if ($atrasou_interno == '1'){
				$cor='#F8FBB3';
			}

			if ($prioridade == 't'){
				$cor='#FFD5CC';
			}

			if (strlen($previsao) > 0) {
				$sqlp = "SELECT ('$previsao' - current_timestamp) > interval'1 day';";
				$resp = pg_exec($con, $sqlp);
				if (pg_result($resp, 0, 0) == 'f') {
					$cor='#FF9966';
				}
			}

			if ($atrasou == '1'){
				$chamados_atrasados = 1;
				$cor='#FF3333';
			}

			if (strlen($hora_desenvolvimento)>0){
				$hora_desenvolvimento = "(".$hora_desenvolvimento." h)";
			}

			for($r = 0 ; $r < count($chamado_interno); $r++){
				if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' width='10' title='Contém chamado interno' border='0'>";
			}
			echo "<tbody>";
			echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<td nowrap >";
			
			if($status =="Análise" AND $exigir_resposta <> "t"){
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
				echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
			}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
					echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}

			echo "$hd_chamado</td>";

			echo "<td nowrap  width='150'>";

			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<acronym title='$titulo'>$interno ";
			echo substr($titulo,0,20)."...</acronym></a></td>";
			
			if (($status != 'Resolvido') and ($status != 'Cancelado')) {
				$cor_status="#000000";
				if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
				if($status=="Execução")$cor_status="#0000FF";
				if($status=="Aguard.Execução")$cor_status="#339900";
				echo "<td nowrap><font color='$cor_status' size='1'><B>$status </B></font><b>$hora_desenvolvimento</b></td>";
			}else{
				echo "<td nowrap>$status <b>$hora_desenvolvimento</b></td>";
			}

			$imagem_erro="";
			
			if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
			echo "<td nowrap width='130' >$imagem_erro <font size='1'><strong>$tipo_chamado_descricao</strong></font></td>";
			
			echo "<td width='80'><font size='1'>$data</font></td>";
			echo "<td nowrap><font size='1'>";
			if (strlen ($nome_completo) > 0) {
				$nome_completo2 = explode (' ',$nome_completo);
				$nome_completo2 = $nome_completo2[0];
				echo $nome_completo2;
				
			}else{
				echo $login;
			}
			echo "</font></td>";
			echo "<td nowrap width='80'><font size='1'>$fabrica_nome</font></td>";
			echo "<td nowrap><font size='1'>$xxatendente[0]</font></td>";
			echo "<td nowrap align='center'><font size='1'>$horas</font></td>";
			echo "<td nowrap align='center'><font size='1'>$prazo_horas</font></td>";
			//echo "<td><font size='1' nowrap>&nbsp;$previsao_termino_interna&nbsp;</font></td>";
			echo "<td width='60'><font size='1'>$previsao_termino</font></td>";
			
			echo "</tr>"; 
			$interno='';
		}

		echo "</tbody>";
		echo "</table>"; 
	}
}

echo "<table border='0' align='center' width = '95%'>";
echo "<tr><td width='100%' align='right'>";

$total_erros = pg_numrows($res);
echo "<font size='2'><b>Total $total_erros chamados</b></font>";
echo "</td></tr>";

echo "</table>";
echo "<BR><BR>";


###busca todos APROVADOR PARA DESENVOLVIMENTO (COM HORAS) menos erros###
$sql = "SELECT  hd_chamado,
				tbl_hd_chamado.admin    ,
				tbl_admin.nome_completo ,
				tbl_admin.login         ,
				to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
				tbl_hd_chamado.previsao_termino AS previsao,
				to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
				to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
				tbl_hd_chamado.titulo,
				tbl_hd_chamado.status,
				tbl_hd_chamado.atendente,
				tbl_hd_chamado.exigir_resposta,
				tbl_hd_chamado.cobrar,
				tbl_hd_chamado.prioridade,
				tbl_hd_chamado.hora_desenvolvimento,
				tbl_hd_chamado.prazo_horas,
				tbl_fabrica.nome AS fabrica_nome,
				tbl_tipo_chamado.descricao as tipo_chamado_descricao,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino 
					THEN 1
					ELSE 0
				END AS atrasou,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino_interna 
					THEN 1
					ELSE 0
				END AS atrasou_interno,
				hora_utilizada / (hora_franqueada + saldo_hora) as percentual_atendido
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		LEFT JOIN tbl_hd_franquia ON tbl_hd_chamado.fabrica = tbl_hd_franquia.fabrica 
								 AND tbl_hd_franquia.mes = to_char(current_date,'MM')::integer
								 AND tbl_hd_franquia.ano = to_char(current_date,'YYYY')::integer
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND    tbl_hd_chamado.tipo_chamado <> 5
		AND    tbl_hd_chamado.atendente IN (432,435,399)
		AND    tbl_hd_chamado.data_aprovacao is NOT NULL
		and $cond_0 ";

if (strlen($data_inicio)>0 AND strlen($data_fim)>0){
	$status_busca = 'r';

	$aux_data_inicio = formata_data($data_inicio);
	$aux_data_fim    = formata_data($data_fim);

	$sql .= " AND tbl_hd_chamado.hd_chamado IN (
				SELECT chamado.hd_chamado
				FROM (
				SELECT 
				ultima.hd_chamado, 
				(SELECT data 
				FROM tbl_hd_chamado_item 
				WHERE tbl_hd_chamado_item.hd_chamado = ultima.hd_chamado 
				ORDER BY data DESC LIMIT 1) AS ultimo_interacao
				FROM (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado WHERE status='Resolvido') ultima
				) chamado
				WHERE chamado.ultimo_interacao BETWEEN '$aux_data_inicio 00:00:01' AND '$aux_data_fim 23:59:59'
			)";
}

if ($atendente_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.atendente = $atendente_busca";
}

if ($autor_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.admin = $autor_busca";
}

if($fabrica_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";
}

if(strlen($sem_prazo) > 0  and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.prazo_horas ISNULL";
}

if ($status_busca=="p" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aprovação' ";
}

if ($status_busca=="a" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Análise' ";
}

if ($status_busca=="r" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Resolvido' ";
}

if ($status_busca=="e" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Execução' ";
}

if ($status_busca=="ae" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Execução' ";
}

if ($status_busca=="c" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Cancelado' ";
}

if ($status_busca=="n" and strlen($valor_chamado)==0){
	$sql .= " AND ((tbl_hd_chamado.status ILIKE 'Novo') OR (tbl_hd_chamado.status IS NULL))";
}

if ($status_busca=="av" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Verifica' ";
}


if ($status_busca=='' and strlen($valor_chamado)==0){
	$sql .= " AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
}


$sql .= " ORDER BY tbl_hd_chamado.previsao_termino,
		hora_utilizada / (hora_franqueada + saldo_hora) ASC, 
		tbl_hd_franquia.hora_franqueada DESC,
		tbl_hd_chamado.data ASC;";
$res = pg_exec ($con,$sql);


/*--===============================TABELA DE CHAMADOS========================--*/
if (@pg_numrows($res) >= 0) {
	echo "<table width = '95%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
	
	echo "<thead>";
	echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>CHAMADOS DE DESENVOLVIMENTO APROVADO PELO FABRICANTE - FALTA PASSAR PARA ANALISTA E PREVISÃO DE TÉRMINO</CENTER></th></tr>";
	echo "<tr bgcolor='#D9E8FF' >";
	echo "	<th >Nº </th>";
	echo "	<th nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></th>";
	echo "	<th ><strong>Status</strong></th>";
	echo "	<th ><strong>Tipo</strong></th>";
	echo "	<th ><strong>Data</strong></th>";
	echo "	<th nowrap><strong>Autor </strong></th>";
	echo "	<th nowrap><strong>Fábrica </strong></td>";
	echo "	<th nowrap><strong>Atendente </strong></th>";
	echo "	<th nowrap ><acronym title='Horas Trabalhadas'><strong>Trab.</strong></acronym></th>";
	echo "	<th nowrap ><strong>Prazo</strong></th>";
	//	echo "	<th nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Prazo Interno</strong></td>";
	echo "	<th nowrap><strong>Previsão</strong></td>";
	echo "</tr>";
	
	echo "</thead>";
	$total_prazo_horas =0;
	if (@pg_numrows($res) > 0) {
		//inicio imprime chamados
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$hd_chamado               = pg_result($res,$i,hd_chamado);
			$admin                    = pg_result($res,$i,admin);
			$login                    = pg_result($res,$i,login);
			//$posto                    = pg_result($res,$i,posto);
			$data                     = pg_result($res,$i,data);
			$titulo                   = pg_result($res,$i,titulo);
			$status                   = pg_result($res,$i,status);
			$atendente                = pg_result($res,$i,atendente);
			$exigir_resposta          = pg_result($res,$i,exigir_resposta);
			$nome_completo            = trim(pg_result($res,$i,nome_completo));
			$fabrica_nome             = trim(pg_result($res,$i,fabrica_nome));
			$previsao_termino         = trim(pg_result($res,$i,previsao_termino));
			$previsao                 = trim(pg_result($res,$i,previsao));
			$previsao_termino_interna = trim(pg_result($res,$i,previsao_termino_interna));
			$atrasou                  = trim(pg_result($res,$i,atrasou));
			$atrasou_interno          = trim(pg_result($res,$i,atrasou_interno));
			$cobrar                   = trim(pg_result($res,$i,cobrar));
			$hora_desenvolvimento     = trim(pg_result($res,$i,hora_desenvolvimento));
			$tipo_chamado_descricao   = trim(pg_result($res,$i,tipo_chamado_descricao));
			$prazo_horas              = pg_result($res,$i,prazo_horas);
			$prioridade               = pg_result($res,$i,prioridade);
			$percentual_atendido      = pg_result($res,$i,percentual_atendido);
			$percentual_atendido = number_format(($percentual_atendido * 100),1,',','.')."%";

			$wsql ="select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) 
					from tbl_hd_chamado_atendente 
					join tbl_admin using(admin)
					where hd_chamado = $hd_chamado
					and   responsabilidade in ('Analista de Help-Desk','Programador')";
			$wres = pg_exec($con, $wsql);
			if(pg_numrows($wres)>0)
			$horas= pg_result ($wres,0,0);	
			if(strlen($horas)>0){
				$xhoras = explode(":",$horas);
				$horas = $xhoras[0].":".$xhoras[1];
			}

			if ($status == 'Aprovação' OR $status == 'Resolvido' OR $status == 'Cancelado'){
				$atrasou = 0;
			}

			if ($atrasou == 0 AND $chamados_atrasados==1){
				//break;
			}
		
			$sql2 = "SELECT nome_completo, admin
				FROM	tbl_admin
				WHERE	admin='$atendente'";
	//		echo $sql2;
			$res2 = pg_exec ($con,$sql2);	
			$xatendente            = pg_result($res2,0,nome_completo);
			$xxatendente = explode(" ", $xatendente);
			
			$cor='#F2F7FF';
			if ($i % 2 == 0) $cor = '#FFFFFF';

			if ($atrasou_interno == '1'){
				$cor='#F8FBB3';
			}

			if ($prioridade == 't'){
				$cor='#FFD5CC';
			}

			if (strlen($previsao) > 0) {
				$sqlp = "SELECT ('$previsao' - current_timestamp) > interval'1 day';";
				$resp = pg_exec($con, $sqlp);
				if (pg_result($resp, 0, 0) == 'f') {
					$cor='#FF9966';
				}
			}

			if ($atrasou == '1'){
				$chamados_atrasados = 1;
				$cor='#FF3333';
			}

			if (strlen($hora_desenvolvimento)>0){
				$hora_desenvolvimento = "(".$hora_desenvolvimento." h)";
			}

			for($r = 0 ; $r < count($chamado_interno); $r++){
				if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' width='10' title='Contém chamado interno' border='0'>";
			}
			echo "<tbody>";
			echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<td nowrap >";
			
			if($status =="Análise" AND $exigir_resposta <> "t"){
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
				echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
			}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
					echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}
			
			echo "$hd_chamado</td>";

			echo "<td nowrap  width='150'>";

			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<acronym title='$titulo'>$interno ";
			echo substr($titulo,0,20)."...</acronym></a></td>";
			
			if (($status != 'Resolvido') and ($status != 'Cancelado')) {
				$cor_status="#000000";
				if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
				if($status=="Execução")$cor_status="#0000FF";
				if($status=="Aguard.Execução")$cor_status="#339900";
				echo "<td nowrap><font color='$cor_status'><p style='font-size:7px'><B>$status </B></font><b>$hora_desenvolvimento</b></p></td>";
			}else{
				echo "<td nowrap><p style='font-size:7px'>$status <b>$hora_desenvolvimento</b></p></td>";
			}
			
			$imagem_erro="";
			
			if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
			echo "<td nowrap width='100' >$imagem_erro <p style='font-size:7px'><strong>$tipo_chamado_descricao</strong></p></td>";
			
			echo "<td width='80'><font size='1'>$data</font></td>";
			echo "<td nowrap><font size='1'>";
			if (strlen ($nome_completo) > 0) {
				$nome_completo2 = explode (' ',$nome_completo);
				$nome_completo2 = $nome_completo2[0];
				echo $nome_completo2;
				
			}else{
				echo $login;
			}
			
			echo "</font></td>";
			echo "<td nowrap ><font size='1'>$fabrica_nome ($percentual_atendido)</font></td>";
			echo "<td nowrap><font size='1'>$xxatendente[0]</font></td>";
			echo "<td nowrap align='center'><font size='1'>$horas</font></td>";
			echo "<td nowrap align='center'><font size='1'>$prazo_horas</font></td>";
			//echo "<td><font size='1' nowrap>&nbsp;$previsao_termino_interna&nbsp;</font></td>";
			echo "<td width='60'><font size='1'>$previsao_termino</font></td>";
			
			echo "</tr>"; 
			$interno='';
			$total_prazo_horas = $total_prazo_horas + $prazo_horas;
		}
		
		echo "</tbody>";
		echo "</table>"; 
	}
}

echo "<table border='0' align='center' width = '95%'>";
echo "<tr><td width='95%' align='right'>";

$total_outros = pg_numrows($res);
echo "<font size='2'><b>Total $total_outros chamados - Total: $total_prazo_horas horas</b></font>";
echo "</td></tr>";
echo "</table>";

echo "<BR><BR>";



###BUSCA TODOS QUE ESTÃO PARA A FÁBRICA APROVADOR E QUE JÁ ESTÃO (COM HORAS) MENOS ERROS###
$sql = "SELECT  hd_chamado,
				tbl_hd_chamado.admin    ,
				tbl_admin.nome_completo ,
				tbl_admin.login         ,
				to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
				tbl_hd_chamado.previsao_termino AS previsao,
				to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
				to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
				tbl_hd_chamado.titulo,
				tbl_hd_chamado.status,
				tbl_hd_chamado.atendente,
				tbl_hd_chamado.exigir_resposta,
				tbl_hd_chamado.cobrar,
				tbl_hd_chamado.prioridade,
				tbl_hd_chamado.hora_desenvolvimento,
				tbl_hd_chamado.prazo_horas,
				tbl_fabrica.nome AS fabrica_nome,
				tbl_tipo_chamado.descricao as tipo_chamado_descricao,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino 
					THEN 1
					ELSE 0
				END AS atrasou,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino_interna 
					THEN 1
					ELSE 0
				END AS atrasou_interno,
				hora_utilizada / (hora_franqueada + saldo_hora) as percentual_atendido
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		LEFT JOIN tbl_hd_franquia ON tbl_hd_chamado.fabrica = tbl_hd_franquia.fabrica 
								 AND tbl_hd_franquia.mes = to_char(current_date,'MM')::integer
								 AND tbl_hd_franquia.ano = to_char(current_date,'YYYY')::integer
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND    tbl_hd_chamado.tipo_chamado <> 5
		AND    tbl_hd_chamado.atendente IN (432,435,399)
		AND    tbl_hd_chamado.data_aprovacao is null
		AND    tbl_hd_chamado.prazo_horas is not null
		and $cond_0 ";

if (strlen($data_inicio)>0 AND strlen($data_fim)>0){
	$status_busca = 'r';

	$aux_data_inicio = formata_data($data_inicio);
	$aux_data_fim    = formata_data($data_fim);

	$sql .= " AND tbl_hd_chamado.hd_chamado IN (
				SELECT chamado.hd_chamado
				FROM (
				SELECT 
				ultima.hd_chamado, 
				(SELECT data 
				FROM tbl_hd_chamado_item 
				WHERE tbl_hd_chamado_item.hd_chamado = ultima.hd_chamado 
				ORDER BY data DESC LIMIT 1) AS ultimo_interacao
				FROM (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado WHERE status='Resolvido') ultima
				) chamado
				WHERE chamado.ultimo_interacao BETWEEN '$aux_data_inicio 00:00:01' AND '$aux_data_fim 23:59:59'
			)";
}

if ($atendente_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.atendente = $atendente_busca";
}

if ($autor_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.admin = $autor_busca";
}

if($fabrica_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";
}

if(strlen($sem_prazo) > 0  and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.prazo_horas ISNULL";
}

if ($status_busca=="p" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aprovação' ";
}

if ($status_busca=="a" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Análise' ";
}

if ($status_busca=="r" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Resolvido' ";
}

if ($status_busca=="e" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Execução' ";
}


if ($status_busca=="av" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Verifica' ";
}

if ($status_busca=="ae" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Execução' ";
}

if ($status_busca=="c" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Cancelado' ";
}

if ($status_busca=="n" and strlen($valor_chamado)==0){
	$sql .= " AND ((tbl_hd_chamado.status ILIKE 'Novo') OR (tbl_hd_chamado.status IS NULL))";
}

if ($status_busca=='' and strlen($valor_chamado)==0){
	$sql .= " AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
}


$sql .= " ORDER BY tbl_hd_chamado.previsao_termino,
		hora_utilizada / (hora_franqueada + saldo_hora) ASC, 
		tbl_hd_franquia.hora_franqueada DESC,
		tbl_hd_chamado.data ASC;";
$res = pg_exec ($con,$sql);


/*--===============================TABELA DE CHAMADOS========================--*/
if (@pg_numrows($res) >= 0) {
	echo "<table width = '95%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
	
	echo "<thead>";
	echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>CHAMADOS DE DESENVOLVIMENTO QUE ESTÃO COM HORAS DE DESENVOLVIMENTO - FALTA PASSAR PARA ANALISTA E PREVISÃO DE TÉRMINO</CENTER></th></tr>";
	echo "<tr bgcolor='#D9E8FF' >";
	echo "	<th >Nº </th>";
	echo "	<th nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></th>";
	echo "	<th ><strong>Status</strong></th>";
	echo "	<th ><strong>Tipo</strong></th>";
	echo "	<th ><strong>Data</strong></th>";
	echo "	<th nowrap><strong>Autor </strong></th>";
	echo "	<th nowrap><strong>Fábrica </strong></td>";
	echo "	<th nowrap><strong>Atendente </strong></th>";
	echo "	<th nowrap ><acronym title='Horas Trabalhadas'><strong>Trab.</strong></acronym></th>";
	echo "	<th nowrap ><strong>Prazo</strong></th>";
	//	echo "	<th nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Prazo Interno</strong></td>";
	echo "	<th nowrap><strong>Previsão</strong></td>";
	echo "</tr>";
	
	echo "</thead>";
	$total_prazo_horas =0;
	if (@pg_numrows($res) > 0) {
		//inicio imprime chamados
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$hd_chamado               = pg_result($res,$i,hd_chamado);
			$admin                    = pg_result($res,$i,admin);
			$login                    = pg_result($res,$i,login);
			//$posto                    = pg_result($res,$i,posto);
			$data                     = pg_result($res,$i,data);
			$titulo                   = pg_result($res,$i,titulo);
			$status                   = pg_result($res,$i,status);
			$atendente                = pg_result($res,$i,atendente);
			$exigir_resposta          = pg_result($res,$i,exigir_resposta);
			$nome_completo            = trim(pg_result($res,$i,nome_completo));
			$fabrica_nome             = trim(pg_result($res,$i,fabrica_nome));
			$previsao_termino         = trim(pg_result($res,$i,previsao_termino));
			$previsao                 = trim(pg_result($res,$i,previsao));
			$previsao_termino_interna = trim(pg_result($res,$i,previsao_termino_interna));
			$atrasou                  = trim(pg_result($res,$i,atrasou));
			$atrasou_interno          = trim(pg_result($res,$i,atrasou_interno));
			$cobrar                   = trim(pg_result($res,$i,cobrar));
			$hora_desenvolvimento     = trim(pg_result($res,$i,hora_desenvolvimento));
			$tipo_chamado_descricao   = trim(pg_result($res,$i,tipo_chamado_descricao));
			$prazo_horas              = pg_result($res,$i,prazo_horas);
			$prioridade               = pg_result($res,$i,prioridade);
			$percentual_atendido      = pg_result($res,$i,percentual_atendido);
			$percentual_atendido = number_format(($percentual_atendido * 100),1,',','.')."%";

			$wsql ="select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) 
					from tbl_hd_chamado_atendente 
					join tbl_admin using(admin)
					where hd_chamado = $hd_chamado
					and   responsabilidade in ('Analista de Help-Desk','Programador')";
			$wres = pg_exec($con, $wsql);
			if(pg_numrows($wres)>0)
			$horas= pg_result ($wres,0,0);	
			if(strlen($horas)>0){
				$xhoras = explode(":",$horas);
				$horas = $xhoras[0].":".$xhoras[1];
			}

			if ($status == 'Aprovação' OR $status == 'Resolvido' OR $status == 'Cancelado'){
				$atrasou = 0;
			}

			if ($atrasou == 0 AND $chamados_atrasados==1){
				//break;
			}
		
			$sql2 = "SELECT nome_completo, admin
				FROM	tbl_admin
				WHERE	admin='$atendente'";
	//		echo $sql2;
			$res2 = pg_exec ($con,$sql2);	
			$xatendente            = pg_result($res2,0,nome_completo);
			$xxatendente = explode(" ", $xatendente);
			
			$cor='#F2F7FF';
			if ($i % 2 == 0) $cor = '#FFFFFF';

			if ($atrasou_interno == '1'){
				$cor='#F8FBB3';
			}

			if ($prioridade == 't'){
				$cor='#FFD5CC';
			}

			if (strlen($previsao) > 0) {
				$sqlp = "SELECT ('$previsao' - current_timestamp) > interval'1 day';";
				$resp = pg_exec($con, $sqlp);
				if (pg_result($resp, 0, 0) == 'f') {
					$cor='#FF9966';
				}
			}

			if ($atrasou == '1'){
				$chamados_atrasados = 1;
				$cor='#FF3333';
			}

			if (strlen($hora_desenvolvimento)>0){
				$hora_desenvolvimento = "(".$hora_desenvolvimento." h)";
			}

			for($r = 0 ; $r < count($chamado_interno); $r++){
				if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' width='10' title='Contém chamado interno' border='0'>";
			}
			echo "<tbody>";
			echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<td nowrap >";
			
			if($status =="Análise" AND $exigir_resposta <> "t"){
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
				echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
			}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
					echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}
			
			echo "$hd_chamado</td>";

			echo "<td nowrap  width='150'>";

			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<acronym title='$titulo'>$interno ";
			echo substr($titulo,0,20)."...</acronym></a></td>";
			
			if (($status != 'Resolvido') and ($status != 'Cancelado')) {
				$cor_status="#000000";
				if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
				if($status=="Execução")$cor_status="#0000FF";
				if($status=="Aguard.Execução")$cor_status="#339900";
				echo "<td nowrap><font color='$cor_status'><p style='font-size:7px'><B>$status </B></font><b>$hora_desenvolvimento</b></p></td>";
			}else{
				echo "<td nowrap><p style='font-size:7px'>$status <b>$hora_desenvolvimento</b></p></td>";
			}
			
			$imagem_erro="";
			
			if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
			echo "<td nowrap width='100' >$imagem_erro <p style='font-size:7px'><strong>$tipo_chamado_descricao</strong></p></td>";
			
			echo "<td width='80'><font size='1'>$data</font></td>";
			echo "<td nowrap><font size='1'>";
			if (strlen ($nome_completo) > 0) {
				$nome_completo2 = explode (' ',$nome_completo);
				$nome_completo2 = $nome_completo2[0];
				echo $nome_completo2;
				
			}else{
				echo $login;
			}
			
			echo "</font></td>";
			echo "<td nowrap ><font size='1'>$fabrica_nome ($percentual_atendido)</font></td>";
			echo "<td nowrap><font size='1'>$xxatendente[0]</font></td>";
			echo "<td nowrap align='center'><font size='1'>$horas</font></td>";
			echo "<td nowrap align='center'><font size='1'>$prazo_horas</font></td>";
			//echo "<td><font size='1' nowrap>&nbsp;$previsao_termino_interna&nbsp;</font></td>";
			echo "<td width='60'><font size='1'>$previsao_termino</font></td>";
			
			echo "</tr>"; 
			$interno='';
			$total_prazo_horas = $total_prazo_horas + $prazo_horas;
		}
		
		echo "</tbody>";
		echo "</table>"; 
	}
}

echo "<table border='0' align='center' width = '95%'>";
echo "<tr><td width='95%' align='right'>";

$total_outros = pg_numrows($res);
echo "<font size='2'><b>Total $total_outros chamados - Total: $total_prazo_horas horas</b></font>";
echo "</td></tr>";
echo "</table>";

echo "<BR><BR>";



###busca todos aprovados para desenvolvimento pelo fabricante menos erros###
$sql = "SELECT  hd_chamado,
				tbl_hd_chamado.admin    ,
				tbl_admin.nome_completo ,
				tbl_admin.login         ,
				to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
				tbl_hd_chamado.previsao_termino AS previsao,
				to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
				to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
				tbl_hd_chamado.titulo,
				tbl_hd_chamado.status,
				tbl_hd_chamado.atendente,
				tbl_hd_chamado.exigir_resposta,
				tbl_hd_chamado.cobrar,
				tbl_hd_chamado.prioridade,
				tbl_hd_chamado.hora_desenvolvimento,
				tbl_hd_chamado.prazo_horas,
				tbl_fabrica.nome AS fabrica_nome,
				tbl_tipo_chamado.descricao as tipo_chamado_descricao,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino 
					THEN 1
					ELSE 0
				END AS atrasou,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino_interna 
					THEN 1
					ELSE 0
				END AS atrasou_interno,
				hora_utilizada / (hora_franqueada + saldo_hora) as percentual_atendido
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		LEFT JOIN tbl_hd_franquia ON tbl_hd_chamado.fabrica = tbl_hd_franquia.fabrica 
								 AND tbl_hd_franquia.mes = to_char(current_date,'MM')::integer
								 AND tbl_hd_franquia.ano = to_char(current_date,'YYYY')::integer
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND    tbl_hd_chamado.tipo_chamado <> 5
		AND    tbl_hd_chamado.atendente IN (432,435,399)
		AND    tbl_hd_chamado.data_aprovacao is null
		AND    tbl_hd_chamado.prazo_horas is null
		AND    tbl_hd_chamado.exigir_resposta is not true
		and $cond_0 ";

if (strlen($data_inicio)>0 AND strlen($data_fim)>0){
	$status_busca = 'r';

	$aux_data_inicio = formata_data($data_inicio);
	$aux_data_fim    = formata_data($data_fim);

	$sql .= " AND tbl_hd_chamado.hd_chamado IN (
				SELECT chamado.hd_chamado
				FROM (
				SELECT 
				ultima.hd_chamado, 
				(SELECT data 
				FROM tbl_hd_chamado_item 
				WHERE tbl_hd_chamado_item.hd_chamado = ultima.hd_chamado 
				ORDER BY data DESC LIMIT 1) AS ultimo_interacao
				FROM (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado WHERE status='Resolvido') ultima
				) chamado
				WHERE chamado.ultimo_interacao BETWEEN '$aux_data_inicio 00:00:01' AND '$aux_data_fim 23:59:59'
			)";
}

if ($atendente_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.atendente = $atendente_busca";
}

if ($autor_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.admin = $autor_busca";
}

if($fabrica_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";
}

if(strlen($sem_prazo) > 0  and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.prazo_horas ISNULL";
}

if ($status_busca=="p" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aprovação' ";
}

if ($status_busca=="a" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Análise' ";
}

if ($status_busca=="r" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Resolvido' ";
}

if ($status_busca=="e" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Execução' ";
}

if ($status_busca=="ae" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Execução' ";
}

if ($status_busca=="c" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Cancelado' ";
}

if ($status_busca=="n" and strlen($valor_chamado)==0){
	$sql .= " AND ((tbl_hd_chamado.status ILIKE 'Novo') OR (tbl_hd_chamado.status IS NULL))";
}

if ($status_busca=='' and strlen($valor_chamado)==0){
	$sql .= " AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
}

if ($status_busca=="av" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Verifica' ";
}



$sql .= " ORDER BY tbl_hd_chamado.previsao_termino,
		hora_utilizada / (hora_franqueada + saldo_hora) ASC, 
		tbl_hd_franquia.hora_franqueada DESC,
		tbl_hd_chamado.data ASC;";
$res = pg_exec ($con,$sql);


/*--===============================TABELA DE CHAMADOS========================--*/
if (@pg_numrows($res) >= 0) {
	echo "<table width = '95%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
	
	echo "<thead>";
	echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>CHAMADOS DE DESENVOLVIMENTO QUE ESTÃO SEM PRAZO NEM DE HORAS DE DESENVOLVIMENTO</CENTER></th></tr>";
	echo "<tr bgcolor='#D9E8FF' >";
	echo "	<th >Nº </th>";
	echo "	<th nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></th>";
	echo "	<th ><strong>Status</strong></th>";
	echo "	<th ><strong>Tipo</strong></th>";
	echo "	<th ><strong>Data</strong></th>";
	echo "	<th nowrap><strong>Autor </strong></th>";
	echo "	<th nowrap><strong>Fábrica </strong></td>";
	echo "	<th nowrap><strong>Atendente </strong></th>";
	echo "	<th nowrap ><acronym title='Horas Trabalhadas'><strong>Trab.</strong></acronym></th>";
	echo "	<th nowrap ><strong>Prazo</strong></th>";
	//	echo "	<th nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Prazo Interno</strong></td>";
	echo "	<th nowrap><strong>Previsão</strong></td>";
	echo "</tr>";
	
	echo "</thead>";

	if (@pg_numrows($res) > 0) {
		//inicio imprime chamados
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$hd_chamado               = pg_result($res,$i,hd_chamado);
			$admin                    = pg_result($res,$i,admin);
			$login                    = pg_result($res,$i,login);
			//$posto                    = pg_result($res,$i,posto);
			$data                     = pg_result($res,$i,data);
			$titulo                   = pg_result($res,$i,titulo);
			$status                   = pg_result($res,$i,status);
			$atendente                = pg_result($res,$i,atendente);
			$exigir_resposta          = pg_result($res,$i,exigir_resposta);
			$nome_completo            = trim(pg_result($res,$i,nome_completo));
			$fabrica_nome             = trim(pg_result($res,$i,fabrica_nome));
			$previsao_termino         = trim(pg_result($res,$i,previsao_termino));
			$previsao                 = trim(pg_result($res,$i,previsao));
			$previsao_termino_interna = trim(pg_result($res,$i,previsao_termino_interna));
			$atrasou                  = trim(pg_result($res,$i,atrasou));
			$atrasou_interno          = trim(pg_result($res,$i,atrasou_interno));
			$cobrar                   = trim(pg_result($res,$i,cobrar));
			$hora_desenvolvimento     = trim(pg_result($res,$i,hora_desenvolvimento));
			$tipo_chamado_descricao   = trim(pg_result($res,$i,tipo_chamado_descricao));
			$prazo_horas              = pg_result($res,$i,prazo_horas);
			$prioridade               = pg_result($res,$i,prioridade);
			$percentual_atendido      = pg_result($res,$i,percentual_atendido);
			$percentual_atendido = number_format(($percentual_atendido * 100),1,',','.')."%";

			$wsql ="select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) 
					from tbl_hd_chamado_atendente 
					join tbl_admin using(admin)
					where hd_chamado = $hd_chamado
					and   responsabilidade in ('Analista de Help-Desk','Programador')";
			$wres = pg_exec($con, $wsql);
			if(pg_numrows($wres)>0)
			$horas= pg_result ($wres,0,0);	
			if(strlen($horas)>0){
				$xhoras = explode(":",$horas);
				$horas = $xhoras[0].":".$xhoras[1];
			}

			if ($status == 'Aprovação' OR $status == 'Resolvido' OR $status == 'Cancelado'){
				$atrasou = 0;
			}

			if ($atrasou == 0 AND $chamados_atrasados==1){
				//break;
			}
		
			$sql2 = "SELECT nome_completo, admin
				FROM	tbl_admin
				WHERE	admin='$atendente'";
	//		echo $sql2;
			$res2 = pg_exec ($con,$sql2);	
			$xatendente            = pg_result($res2,0,nome_completo);
			$xxatendente = explode(" ", $xatendente);
			
			$cor='#F2F7FF';
			if ($i % 2 == 0) $cor = '#FFFFFF';

			if ($atrasou_interno == '1'){
				$cor='#F8FBB3';
			}

			if ($prioridade == 't'){
				$cor='#FFD5CC';
			}

			if (strlen($previsao) > 0) {
				$sqlp = "SELECT ('$previsao' - current_timestamp) > interval'1 day';";
				$resp = pg_exec($con, $sqlp);
				if (pg_result($resp, 0, 0) == 'f') {
					$cor='#FF9966';
				}
			}

			if ($atrasou == '1'){
				$chamados_atrasados = 1;
				$cor='#FF3333';
			}

			if (strlen($hora_desenvolvimento)>0){
				$hora_desenvolvimento = "(".$hora_desenvolvimento." h)";
			}

			for($r = 0 ; $r < count($chamado_interno); $r++){
				if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' width='10' title='Contém chamado interno' border='0'>";
			}
			echo "<tbody>";
			echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<td nowrap >";
			
			if($status =="Análise" AND $exigir_resposta <> "t"){
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
				echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
			}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
					echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}
			
			echo "$hd_chamado</td>";

			echo "<td nowrap  width='150'>";

			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<acronym title='$titulo'>$interno ";
			echo substr($titulo,0,20)."...</acronym></a></td>";
			
			if (($status != 'Resolvido') and ($status != 'Cancelado')) {
				$cor_status="#000000";
				if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
				if($status=="Execução")$cor_status="#0000FF";
				if($status=="Aguard.Execução")$cor_status="#339900";
				echo "<td nowrap><font color='$cor_status'><p style='font-size:7px'><B>$status </B></font><b>$hora_desenvolvimento</b></p></td>";
			}else{
				echo "<td nowrap><p style='font-size:7px'>$status <b>$hora_desenvolvimento</b></p></td>";
			}
			
			$imagem_erro="";
			
			if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
			echo "<td nowrap width='100' >$imagem_erro <p style='font-size:7px'><strong>$tipo_chamado_descricao</strong></p></td>";
			
			echo "<td width='80'><font size='1'>$data</font></td>";
			echo "<td nowrap><font size='1'>";
			if (strlen ($nome_completo) > 0) {
				$nome_completo2 = explode (' ',$nome_completo);
				$nome_completo2 = $nome_completo2[0];
				echo $nome_completo2;
				
			}else{
				echo $login;
			}
			
			echo "</font></td>";
			echo "<td nowrap ><font size='1'>$fabrica_nome ($percentual_atendido)</font></td>";
			echo "<td nowrap><font size='1'>$xxatendente[0]</font></td>";
			echo "<td nowrap align='center'><font size='1'>$horas</font></td>";
			echo "<td nowrap align='center'><font size='1'>$prazo_horas</font></td>";
			//echo "<td><font size='1' nowrap>&nbsp;$previsao_termino_interna&nbsp;</font></td>";
			echo "<td width='60'><font size='1'>$previsao_termino</font></td>";
			
			echo "</tr>"; 
			$interno='';
		}
		
		echo "</tbody>";
		echo "</table>"; 
	}
}

echo "<table border='0' align='center' width = '95%'>";
echo "<tr><td width='95%' align='right'>";

$total_outros = pg_numrows($res);
echo "<font size='2'><b>Total $total_outros chamados</b></font>";
echo "</td></tr>";
echo "</table>";

echo "<BR><BR>";



###busca todos chamados pendentes com a fábrica (exigir resposta)###
$sql = "SELECT  hd_chamado,
				tbl_hd_chamado.admin    ,
				tbl_admin.nome_completo ,
				tbl_admin.login         ,
				to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
				tbl_hd_chamado.previsao_termino AS previsao,
				to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
				to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
				tbl_hd_chamado.titulo,
				tbl_hd_chamado.status,
				tbl_hd_chamado.atendente,
				tbl_hd_chamado.exigir_resposta,
				tbl_hd_chamado.cobrar,
				tbl_hd_chamado.prioridade,
				tbl_hd_chamado.hora_desenvolvimento,
				tbl_hd_chamado.prazo_horas,
				tbl_fabrica.nome AS fabrica_nome,
				tbl_tipo_chamado.descricao as tipo_chamado_descricao,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino 
					THEN 1
					ELSE 0
				END AS atrasou,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino_interna 
					THEN 1
					ELSE 0
				END AS atrasou_interno,
				hora_utilizada / (hora_franqueada + saldo_hora) as percentual_atendido
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		LEFT JOIN tbl_hd_franquia ON tbl_hd_chamado.fabrica = tbl_hd_franquia.fabrica 
								 AND tbl_hd_franquia.mes = to_char(current_date,'MM')::integer
								 AND tbl_hd_franquia.ano = to_char(current_date,'YYYY')::integer
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND    tbl_hd_chamado.tipo_chamado <> 5
		AND    tbl_hd_chamado.atendente IN (432,435,399)
		AND    tbl_hd_chamado.data_aprovacao is null
		AND    tbl_hd_chamado.prazo_horas is null
		AND    tbl_hd_chamado.exigir_resposta is true
		and $cond_0 ";

if (strlen($data_inicio)>0 AND strlen($data_fim)>0){
	$status_busca = 'r';

	$aux_data_inicio = formata_data($data_inicio);
	$aux_data_fim    = formata_data($data_fim);

	$sql .= " AND tbl_hd_chamado.hd_chamado IN (
				SELECT chamado.hd_chamado
				FROM (
				SELECT 
				ultima.hd_chamado, 
				(SELECT data 
				FROM tbl_hd_chamado_item 
				WHERE tbl_hd_chamado_item.hd_chamado = ultima.hd_chamado 
				ORDER BY data DESC LIMIT 1) AS ultimo_interacao
				FROM (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado WHERE status='Resolvido') ultima
				) chamado
				WHERE chamado.ultimo_interacao BETWEEN '$aux_data_inicio 00:00:01' AND '$aux_data_fim 23:59:59'
			)";
}

if ($atendente_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.atendente = $atendente_busca";
}

if ($autor_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.admin = $autor_busca";
}

if($fabrica_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";
}

if(strlen($sem_prazo) > 0  and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.prazo_horas ISNULL";
}

if ($status_busca=="p" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aprovação' ";
}

if ($status_busca=="a" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Análise' ";
}

if ($status_busca=="r" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Resolvido' ";
}

if ($status_busca=="e" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Execução' ";
}

if ($status_busca=="ae" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Execução' ";
}

if ($status_busca=="c" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Cancelado' ";
}

if ($status_busca=="n" and strlen($valor_chamado)==0){
	$sql .= " AND ((tbl_hd_chamado.status ILIKE 'Novo') OR (tbl_hd_chamado.status IS NULL))";
}

if ($status_busca=='' and strlen($valor_chamado)==0){
	$sql .= " AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
}

if ($status_busca=="av" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Verifica' ";
}


$sql .= " ORDER BY tbl_hd_chamado.previsao_termino,
		hora_utilizada / (hora_franqueada + saldo_hora) ASC, 
		tbl_hd_franquia.hora_franqueada DESC,
		tbl_hd_chamado.data ASC;";
$res = pg_exec ($con,$sql);


/*--===============================TABELA DE CHAMADOS========================--*/
if (@pg_numrows($res) >= 0) {
	echo "<table width = '95%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
	
	echo "<thead>";
	echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>CHAMADOS QUE ESTÃO PEDENTES PARA O FABRICANTE E SEM PRAZO</CENTER></th></tr>";
	echo "<tr bgcolor='#D9E8FF' >";
	echo "	<th >Nº </th>";
	echo "	<th nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></th>";
	echo "	<th ><strong>Status</strong></th>";
	echo "	<th ><strong>Tipo</strong></th>";
	echo "	<th ><strong>Data</strong></th>";
	echo "	<th nowrap><strong>Autor </strong></th>";
	echo "	<th nowrap><strong>Fábrica </strong></td>";
	echo "	<th nowrap><strong>Atendente </strong></th>";
	echo "	<th nowrap ><acronym title='Horas Trabalhadas'><strong>Trab.</strong></acronym></th>";
	echo "	<th nowrap ><strong>Prazo</strong></th>";
	//	echo "	<th nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Prazo Interno</strong></td>";
	echo "	<th nowrap><strong>Previsão</strong></td>";
	echo "</tr>";
	
	echo "</thead>";

	if (@pg_numrows($res) > 0) {
		//inicio imprime chamados
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$hd_chamado               = pg_result($res,$i,hd_chamado);
			$admin                    = pg_result($res,$i,admin);
			$login                    = pg_result($res,$i,login);
			//$posto                    = pg_result($res,$i,posto);
			$data                     = pg_result($res,$i,data);
			$titulo                   = pg_result($res,$i,titulo);
			$status                   = pg_result($res,$i,status);
			$atendente                = pg_result($res,$i,atendente);
			$exigir_resposta          = pg_result($res,$i,exigir_resposta);
			$nome_completo            = trim(pg_result($res,$i,nome_completo));
			$fabrica_nome             = trim(pg_result($res,$i,fabrica_nome));
			$previsao_termino         = trim(pg_result($res,$i,previsao_termino));
			$previsao                 = trim(pg_result($res,$i,previsao));
			$previsao_termino_interna = trim(pg_result($res,$i,previsao_termino_interna));
			$atrasou                  = trim(pg_result($res,$i,atrasou));
			$atrasou_interno          = trim(pg_result($res,$i,atrasou_interno));
			$cobrar                   = trim(pg_result($res,$i,cobrar));
			$hora_desenvolvimento     = trim(pg_result($res,$i,hora_desenvolvimento));
			$tipo_chamado_descricao   = trim(pg_result($res,$i,tipo_chamado_descricao));
			$prazo_horas              = pg_result($res,$i,prazo_horas);
			$prioridade               = pg_result($res,$i,prioridade);
			$percentual_atendido      = pg_result($res,$i,percentual_atendido);
			$percentual_atendido = number_format(($percentual_atendido * 100),1,',','.')."%";

			$wsql ="select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) 
					from tbl_hd_chamado_atendente 
					join tbl_admin using(admin)
					where hd_chamado = $hd_chamado
					and   responsabilidade in ('Analista de Help-Desk','Programador')";
			$wres = pg_exec($con, $wsql);
			if(pg_numrows($wres)>0)
			$horas= pg_result ($wres,0,0);	
			if(strlen($horas)>0){
				$xhoras = explode(":",$horas);
				$horas = $xhoras[0].":".$xhoras[1];
			}

			if ($status == 'Aprovação' OR $status == 'Resolvido' OR $status == 'Cancelado'){
				$atrasou = 0;
			}

			if ($atrasou == 0 AND $chamados_atrasados==1){
				//break;
			}
		
			$sql2 = "SELECT nome_completo, admin
				FROM	tbl_admin
				WHERE	admin='$atendente'";
	//		echo $sql2;
			$res2 = pg_exec ($con,$sql2);	
			$xatendente            = pg_result($res2,0,nome_completo);
			$xxatendente = explode(" ", $xatendente);
			
			$cor='#F2F7FF';
			if ($i % 2 == 0) $cor = '#FFFFFF';

			if ($atrasou_interno == '1'){
				$cor='#F8FBB3';
			}

			if ($prioridade == 't'){
				$cor='#FFD5CC';
			}

			if (strlen($previsao) > 0) {
				$sqlp = "SELECT ('$previsao' - current_timestamp) > interval'1 day';";
				$resp = pg_exec($con, $sqlp);
				if (pg_result($resp, 0, 0) == 'f') {
					$cor='#FF9966';
				}
			}

			if ($atrasou == '1'){
				$chamados_atrasados = 1;
				$cor='#FF3333';
			}

			if (strlen($hora_desenvolvimento)>0){
				$hora_desenvolvimento = "(".$hora_desenvolvimento." h)";
			}

			for($r = 0 ; $r < count($chamado_interno); $r++){
				if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' width='10' title='Contém chamado interno' border='0'>";
			}
			echo "<tbody>";
			echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<td nowrap >";
			
			if($status =="Análise" AND $exigir_resposta <> "t"){
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
				echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
			}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
					echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}
			
			echo "$hd_chamado</td>";

			echo "<td nowrap  width='150'>";

			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<acronym title='$titulo'>$interno ";
			echo substr($titulo,0,20)."...</acronym></a></td>";
			
			if (($status != 'Resolvido') and ($status != 'Cancelado')) {
				$cor_status="#000000";
				if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
				if($status=="Execução")$cor_status="#0000FF";
				if($status=="Aguard.Execução")$cor_status="#339900";
				echo "<td nowrap><font color='$cor_status'><p style='font-size:7px'><B>$status </B></font><b>$hora_desenvolvimento</b></p></td>";
			}else{
				echo "<td nowrap><p style='font-size:7px'>$status <b>$hora_desenvolvimento</b></p></td>";
			}
			
			$imagem_erro="";
			
			if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
			echo "<td nowrap width='100' >$imagem_erro <p style='font-size:7px'><strong>$tipo_chamado_descricao</strong></p></td>";
			
			echo "<td width='80'><font size='1'>$data</font></td>";
			echo "<td nowrap><font size='1'>";
			if (strlen ($nome_completo) > 0) {
				$nome_completo2 = explode (' ',$nome_completo);
				$nome_completo2 = $nome_completo2[0];
				echo $nome_completo2;
				
			}else{
				echo $login;
			}
			
			echo "</font></td>";
			echo "<td nowrap ><font size='1'>$fabrica_nome ($percentual_atendido)</font></td>";
			echo "<td nowrap><font size='1'>$xxatendente[0]</font></td>";
			echo "<td nowrap align='center'><font size='1'>$horas</font></td>";
			echo "<td nowrap align='center'><font size='1'>$prazo_horas</font></td>";
			//echo "<td><font size='1' nowrap>&nbsp;$previsao_termino_interna&nbsp;</font></td>";
			echo "<td width='60'><font size='1'>$previsao_termino</font></td>";
			
			echo "</tr>"; 
			$interno='';
		}
		
		echo "</tbody>";
		echo "</table>"; 
	}
}

echo "<table border='0' align='center' width = '95%'>";
echo "<tr><td width='95%' align='right'>";

$total_outros = pg_numrows($res);
echo "<font size='2'><b>Total $total_outros chamados</b></font>";
echo "</td></tr>";
echo "</table>";

echo "<BR><BR>";




###busca todos com atendente###
$sql = "SELECT  hd_chamado,
				tbl_hd_chamado.admin    ,
				tbl_admin.nome_completo ,
				tbl_admin.login         ,
				to_char (tbl_hd_chamado.data,'DD/MM/YY HH24:MI') AS data,
				tbl_hd_chamado.previsao_termino AS previsao,
				to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
				to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
				tbl_hd_chamado.titulo,
				tbl_hd_chamado.status,
				tbl_hd_chamado.atendente,
				tbl_hd_chamado.exigir_resposta,
				tbl_hd_chamado.cobrar,
				tbl_hd_chamado.prioridade,
				tbl_hd_chamado.hora_desenvolvimento,
				tbl_hd_chamado.prazo_horas,
				tbl_fabrica.nome AS fabrica_nome,
				tbl_tipo_chamado.descricao as tipo_chamado_descricao,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino 
					THEN 1
					ELSE 0
				END AS atrasou,
				CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino_interna 
					THEN 1
					ELSE 0
				END AS atrasou_interno,
				hora_utilizada / (hora_franqueada + saldo_hora) as percentual_atendido
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		LEFT JOIN tbl_hd_franquia ON tbl_hd_chamado.fabrica = tbl_hd_franquia.fabrica 
								 AND tbl_hd_franquia.mes = to_char(current_date,'MM')::integer
								 AND tbl_hd_franquia.ano = to_char(current_date,'YYYY')::integer
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND    tbl_hd_chamado.atendente NOT IN (432,435)
		and $cond_0 ";

if (strlen($data_inicio)>0 AND strlen($data_fim)>0){
	$status_busca = 'r';

	$aux_data_inicio = formata_data($data_inicio);
	$aux_data_fim    = formata_data($data_fim);

	$sql .= " AND tbl_hd_chamado.hd_chamado IN (
				SELECT chamado.hd_chamado
				FROM (
				SELECT 
				ultima.hd_chamado, 
				(SELECT data 
				FROM tbl_hd_chamado_item 
				WHERE tbl_hd_chamado_item.hd_chamado = ultima.hd_chamado 
				ORDER BY data DESC LIMIT 1) AS ultimo_interacao
				FROM (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado WHERE status='Resolvido') ultima
				) chamado
				WHERE chamado.ultimo_interacao BETWEEN '$aux_data_inicio 00:00:01' AND '$aux_data_fim 23:59:59'
			)";
}

if ($atendente_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.atendente = $atendente_busca";
}

if ($autor_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.admin = $autor_busca";
}

if($fabrica_busca <> '' and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";
}

if(strlen($sem_prazo) > 0  and strlen($valor_chamado)==0 ){
	$sql .= " AND tbl_hd_chamado.prazo_horas ISNULL";
}

if ($status_busca=="p" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aprovação' ";
}

if ($status_busca=="a" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Análise' ";
}

if ($status_busca=="r" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Resolvido' ";
}

if ($status_busca=="e" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Execução' ";
}

if ($status_busca=="ae" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Execução' ";
}

if ($status_busca=="c" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Cancelado' ";
}

if ($status_busca=="n" and strlen($valor_chamado)==0){
	$sql .= " AND ((tbl_hd_chamado.status ILIKE 'Novo') OR (tbl_hd_chamado.status IS NULL))";
}

if ($status_busca=='' and strlen($valor_chamado)==0){
	$sql .= " AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
}

if ($status_busca=="av" and strlen($valor_chamado)==0){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Verifica' ";
}


$sql .= " ORDER BY tbl_hd_chamado.previsao_termino;";
$res = pg_exec ($con,$sql);


/*--===============================TABELA DE CHAMADOS========================--*/
if (@pg_numrows($res) >= 0) {
	echo "<table width = '95%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
	
	echo "<thead>";
	echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>CHAMADOS PENDENTES COM ANALISTAS</CENTER></th></tr>";
	echo "<tr bgcolor='#D9E8FF' >";
	echo "	<th >Nº </th>";
	echo "	<th nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></th>";
	echo "	<th ><strong>Status</strong></th>";
	echo "	<th ><strong>Tipo</strong></th>";
	echo "	<th ><strong>Data</strong></th>";
	echo "	<th nowrap><strong>Autor </strong></th>";
	echo "	<th nowrap><strong>Fábrica </strong></td>";
	echo "	<th nowrap><strong>Atendente </strong></th>";
	echo "	<th nowrap ><acronym title='Horas Trabalhadas'><strong>Trab.</strong></acronym></th>";
	echo "	<th nowrap ><strong>Prazo</strong></th>";
	//	echo "	<th nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Prazo Interno</strong></td>";
	echo "	<th nowrap><strong>Previsão</strong></td>";
	echo "</tr>";
	
	echo "</thead>";
	$total_prazo_horas =0;
	if (@pg_numrows($res) > 0) {
		//inicio imprime chamados
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$hd_chamado               = pg_result($res,$i,hd_chamado);
			$admin                    = pg_result($res,$i,admin);
			$login                    = pg_result($res,$i,login);
			//$posto                    = pg_result($res,$i,posto);
			$data                     = pg_result($res,$i,data);
			$titulo                   = pg_result($res,$i,titulo);
			$status                   = pg_result($res,$i,status);
			$atendente                = pg_result($res,$i,atendente);
			$exigir_resposta          = pg_result($res,$i,exigir_resposta);
			$nome_completo            = trim(pg_result($res,$i,nome_completo));
			$fabrica_nome             = trim(pg_result($res,$i,fabrica_nome));
			$previsao_termino         = trim(pg_result($res,$i,previsao_termino));
			$previsao                 = trim(pg_result($res,$i,previsao));
			$previsao_termino_interna = trim(pg_result($res,$i,previsao_termino_interna));
			$atrasou                  = trim(pg_result($res,$i,atrasou));
			$atrasou_interno          = trim(pg_result($res,$i,atrasou_interno));
			$cobrar                   = trim(pg_result($res,$i,cobrar));
			$hora_desenvolvimento     = trim(pg_result($res,$i,hora_desenvolvimento));
			$tipo_chamado_descricao   = trim(pg_result($res,$i,tipo_chamado_descricao));
			$prazo_horas              = pg_result($res,$i,prazo_horas);
			$prioridade               = pg_result($res,$i,prioridade);
			$percentual_atendido      = pg_result($res,$i,percentual_atendido);
			$percentual_atendido = number_format(($percentual_atendido * 100),1,',','.')."%";

			$wsql ="select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) 
					from tbl_hd_chamado_atendente 
					join tbl_admin using(admin)
					where hd_chamado = $hd_chamado
					and   responsabilidade in ('Analista de Help-Desk','Programador')";
			$wres = pg_exec($con, $wsql);
			if(pg_numrows($wres)>0)
			$horas= pg_result ($wres,0,0);	
			if(strlen($horas)>0){
				$xhoras = explode(":",$horas);
				$horas = $xhoras[0].":".$xhoras[1];
			}

			if ($status == 'Aprovação' OR $status == 'Resolvido' OR $status == 'Cancelado'){
				$atrasou = 0;
			}

			if ($atrasou == 0 AND $chamados_atrasados==1){
				//break;
			}
		
			$sql2 = "SELECT nome_completo, admin
				FROM	tbl_admin
				WHERE	admin='$atendente'";
	//		echo $sql2;
			$res2 = pg_exec ($con,$sql2);	
			$xatendente            = pg_result($res2,0,nome_completo);
			$xxatendente = explode(" ", $xatendente);
			
			$cor='#F2F7FF';
			if ($i % 2 == 0) $cor = '#FFFFFF';

			if ($atrasou_interno == '1'){
				$cor='#F8FBB3';
			}

			if ($prioridade == 't'){
				$cor='#FFD5CC';
			}

			if (strlen($previsao) > 0) {
				$sqlp = "SELECT ('$previsao' - current_timestamp) > interval'1 day';";
				$resp = pg_exec($con, $sqlp);
				if (pg_result($resp, 0, 0) == 'f') {
					$cor='#FF9966';
				}
			}

			if ($atrasou == '1'){
				$chamados_atrasados = 1;
				$cor='#FF3333';
			}

			if (strlen($hora_desenvolvimento)>0){
				$hora_desenvolvimento = "(".$hora_desenvolvimento." h)";
			}

			for($r = 0 ; $r < count($chamado_interno); $r++){
				if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' width='10' title='Contém chamado interno' border='0'>";
			}
			echo "<tbody>";
			echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<td nowrap >";
			
			if($status =="Análise" AND $exigir_resposta <> "t"){
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
				echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
			}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
					echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}
			
			echo "$hd_chamado</td>";

			echo "<td nowrap  width='150'>";

			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<acronym title='$titulo'>$interno ";
			echo substr($titulo,0,20)."...</acronym></a></td>";
			
			if (($status != 'Resolvido') and ($status != 'Cancelado')) {
				$cor_status="#000000";
				if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
				if($status=="Execução")$cor_status="#0000FF";
				if($status=="Aguard.Execução")$cor_status="#339900";
				echo "<td nowrap><font color='$cor_status'><p style='font-size:7px'><B>$status </B></font><b>$hora_desenvolvimento</b></p></td>";
			}else{
				echo "<td nowrap><p style='font-size:7px'>$status <b>$hora_desenvolvimento</b></p></td>";
			}
			
			$imagem_erro="";
			
			if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
			echo "<td nowrap width='100' >$imagem_erro <p style='font-size:7px'><strong>$tipo_chamado_descricao</strong></p></td>";
			
			echo "<td width='80'><font size='1'>$data</font></td>";
			echo "<td nowrap><font size='1'>";
			if (strlen ($nome_completo) > 0) {
				$nome_completo2 = explode (' ',$nome_completo);
				$nome_completo2 = $nome_completo2[0];
				echo $nome_completo2;
				
			}else{
				echo $login;
			}
			
			echo "</font></td>";
			echo "<td nowrap ><font size='1'>$fabrica_nome ($percentual_atendido)</font></td>";
			echo "<td nowrap><font size='1'>$xxatendente[0]</font></td>";
			echo "<td nowrap align='center'><font size='1'>$horas</font></td>";
			echo "<td nowrap align='center'><font size='1'>$prazo_horas</font></td>";
			//echo "<td><font size='1' nowrap>&nbsp;$previsao_termino_interna&nbsp;</font></td>";
			echo "<td width='60'><font size='1'>$previsao_termino</font></td>";
			
			echo "</tr>"; 
			$interno='';
			$total_prazo_horas = $total_prazo_horas + $prazo_horas;
		}
		
		echo "</tbody>";
		echo "</table>"; 

		echo "<table border='0' align='center' width = '95%'>";
		echo "<tr><td width='100%' align='right'>";

		$total_outros = pg_numrows($res);
		//echo "<font size='2'><b>Total $total_outros chamados</b></font>";
		echo "<font size='2'><b>Total $total_outros chamados - Total: $total_prazo_horas horas</b></font>";
		echo "</td></tr>";
		echo "</table>";
	}
}

echo "<BR>";


	###busca todos ABERTOS###
	$sql = "
			SELECT  count(tbl_hd_chamado.hd_chamado)as qtde,
				tbl_fabrica.nome
			FROM tbl_hd_chamado 
			JOIN tbl_fabrica    ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
			WHERE data  BETWEEN  (current_date||' 00:00:00')::timestamp AND (current_date||' 23:59:00')::timestamp
				AND tbl_hd_chamado.fabrica_responsavel = 10
			GROUP by tbl_fabrica.nome
			ORDER BY COUNT(tbl_hd_chamado.hd_chamado) DESC";

	$res = pg_exec ($con,$sql);


	/*--===============================TABELA DE CHAMADOS========================--*/
	if (@pg_numrows($res) > 0) {
		echo "<table width = '300' align = 'center' cellpadding='2' cellspacing='1' border='0' class='relatorio'>";
		echo "<thead>";
		echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>CHAMADOS ABERTOS</CENTER></th></tr>";
		echo "<tr bgcolor='#D9E8FF' >";
		echo "	<th ><strong>Fabrica</strong></th>";
		echo "	<th >QTDE</th>";
		echo "</tr>";
		
		echo "</thead>";
		$qtde_total = 0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$qtde = pg_result($res,$i,qtde);
			$nome                    = pg_result($res,$i,nome);
			$qtde_total = $qtde_total+ $qtde ;
			echo "<tbody>";
			echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
			echo "<td nowrap ><font size='1'>$nome </font></td>";
			echo "<td nowrap  >$qtde</td>";
			echo "</tr>"; 
			echo "</tbody>";
		}
		echo "<tr><td width='100%' align='right'>";
		echo "<font size='2'><b>Total $qtde_total chamados Abertos</b></font>";
		echo "</td></tr>";
		echo "</table>"; 
		$total_aberto = $qtde_total;
	}
	echo "<BR>";

	###busca todos RESOLVIDOS###
	$sql = "
			SELECT  count(tbl_hd_chamado.hd_chamado)as qtde,
				tbl_fabrica.nome
			FROM tbl_hd_chamado 
			JOIN tbl_fabrica    ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
			WHERE data_resolvido BETWEEN  (current_date||' 00:00:00')::timestamp AND (current_date||' 23:59:00')::timestamp
				AND tbl_hd_chamado.status = 'Resolvido'
			GROUP by tbl_fabrica.nome
			ORDER BY COUNT(tbl_hd_chamado.hd_chamado) DESC";

	$res = pg_exec ($con,$sql);


	/*--===============================TABELA DE CHAMADOS RESOLVIDOS========================--*/
	if (@pg_numrows($res) > 0) {
		echo "<table width = '300' align = 'center' cellpadding='2' cellspacing='1' border='0' class='relatorio'>";
		echo "<thead>";
		echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>CHAMADOS RESOLVIDOS HOJE</CENTER></th></tr>";
		echo "<tr bgcolor='#D9E8FF' >";
		echo "	<th ><strong>Fabrica</strong></th>";
		echo "	<th >QTDE</th>";
		echo "</tr>";
		
		echo "</thead>";
		$qtde_total = 0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$qtde = pg_result($res,$i,qtde);
			$nome                    = pg_result($res,$i,nome);
			$qtde_total = $qtde_total+ $qtde ;
			echo "<tbody>";
			echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
			echo "<td nowrap ><font size='1'>$nome </font></td>";
			echo "<td nowrap  >$qtde</td>";
			echo "</tr>"; 
			echo "</tbody>";

		}
		echo "<tr><td width='100%' align='right'>";
		echo "<font size='2'><b>Total $qtde_total chamados Resolvidos</b></font>";
		echo "</td></tr>";
		echo "</table>"; 
		$total_resolvido= $qtde_total;
		
	}

	echo "<BR>";


	###busca todos RESOLVIDOS -TELEFONE###
	$sql = "
			SELECT  count(tbl_hd_chamado.hd_chamado)as qtde,
				tbl_fabrica.nome
			FROM tbl_hd_chamado 
			JOIN tbl_fabrica    ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
			WHERE data  BETWEEN  (current_date||' 00:00:00')::timestamp AND (current_date||' 23:59:00')::timestamp
				AND tbl_hd_chamado.fabrica_responsavel = 10
				AND tbl_hd_chamado.categoria= 'Suporte Telefone'
			GROUP by tbl_fabrica.nome
			ORDER BY COUNT(tbl_hd_chamado.hd_chamado) DESC";


	$res = pg_exec ($con,$sql);


	/*--===============================TABELA DE CHAMADOS========================--*/
	if (@pg_numrows($res) > 0) {
		echo "<table width = '300' align = 'center' cellpadding='2' cellspacing='1' border='0' class='relatorio'>";
		echo "<thead>";
		echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>CHAMADOS DE TELEFONE ABERTOS HOJE </CENTER></th></tr>";
		echo "<tr bgcolor='#D9E8FF' >";
		echo "	<th ><strong>Fabrica</strong></th>";
		echo "	<th >QTDE</th>";
		echo "</tr>";
		
		echo "</thead>";
		$qtde_total = 0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$qtde = pg_result($res,$i,qtde);
			$nome                    = pg_result($res,$i,nome);
			$qtde_total = $qtde_total+ $qtde ;
			echo "<tbody>";
			echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
			echo "<td nowrap ><font size='1'>$nome </font></td>";
			echo "<td nowrap  >$qtde</td>";
			echo "</tr>"; 
			echo "</tbody>";

		}
		echo "<tr><td width='100%' align='right'>";
		echo "<font size='2'><b>Total $qtde_total chamados Abertos</b></font>";
		echo "</td></tr>";
		echo "</table>"; 
		$total_fone = $qtde_total;
	}

echo "<BR>";

	$aGraphData = Array
		(array('Chamados Aberto', $total_aberto, ' HD'),
		 array('Chamados Resolvido', $total_resolvido, ' HD'),
		 array('Chamados Fone', $total_fone, ' HD')
		);

	echo phpHtmlChart($aGraphData, 'H', 'Chamados', 'Números de Chamados', '8pt', 400, 'px', 15, 'px');
?>

<? include "rodape.php" ?>