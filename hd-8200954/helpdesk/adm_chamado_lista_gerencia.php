<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once '../admin/funcoes.php';

if($login_fabrica<>10) header ("Location: index.php");

$TITULO = "Lista de Chamados";

include "menu.php";
?>
<meta http-equiv="refresh" content="300">
<link rel="stylesheet" href="js/blue/style.css" type="text/css" media="print, projection, screen" />
<script type="text/javascript" src="../js/jquery-1.3.2.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter-2.0.min.js"></script>
<script type="text/javascript" charset="utf-8" src="../js/jquery.alphanumeric.js"></script>
<script>
$(document).ready(function() {
	$.tablesorter.defaults.widgets = ['zebra'];
	$("table#relatorio").tablesorter({textExtraction: 'complex'});
	$(".relatorio tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
	$("#dias").numeric();
   });

</script>
<style>
	.link_log{
		font-family: Verdana;
		font-size: 11px;
		color: #333399;
	}

	table.relatorio {
		border-collapse: collapse;
		width: 650px;
		font-size: 1.1em;
		font-family: Verdana;
		font-size: 10px;
	}

	table.relatorio thead {background-color:#d9e8ff}
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
		cursor: default;
	}

	table.relatorio td {
		padding: 1px 5px;
		border-bottom: 1px solid #95bce2;
		font-family: Verdana;
		font-size: 10px;
	}

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
<?

$status_busca    = trim($_POST['status']);
$atendente_busca = trim($_POST['atendente_busca']);
$autor_busca     = trim($_POST['autor_busca']);
$valor_chamado   = trim($_POST['valor_chamado']);
$fabrica_busca   = trim($_POST['fabrica_busca']);
$ordenar_por     = trim($_POST['ordenar_por']);
$data_inicio     = trim($_POST['data_inicio']);
$data_fim        = trim($_POST['data_fim']);
$tipo_chamado    = trim($_POST['tipo_chamado']);
$atendente_responsavel = $_POST['atendente_responsavel'];

$tipo_resultado = $_POST['tipo_resultado'];
$dias = $_POST['dias'];

if (strlen($tipo_chamado) == 0) {
	$tipo_chamado    = trim($_GET['tipo_chamado']);
}

if (strlen($status_busca)    == 0) $status_busca    = trim($_GET['status']);
if (strlen($atendente_busca) == 0) $atendente_busca = trim($_GET['atendente_busca']);
if (strlen($autor_busca)     == 0) $autor_busca     = trim($_GET['autor_busca']);
if (strlen($valor_chamado)   == 0) $valor_chamado   = trim($_GET['valor_chamado']);
if (strlen($data_inicio)     == 0) $data_inicio     = trim($_GET['data_inicio']);
if (strlen($data_fim)        == 0) $data_fim        = trim($_GET['data_fim']);
if (strlen($fabrica_busca)   == 0) $fabrica_busca   = trim($_GET['fabrica_busca']);
if (strlen($ordenar_por)     == 0) $ordenar_por     = trim($_GET['ordenar_por']);
if (strlen($atendente_responsavel) == 0) $atendente_responsavel = trim($_GET['atendente_responsavel']);

if (empty($tipo_resultado)) {
	$tipo_resultado = $_GET['tipo_resultado'];
}

if (empty($dias)) {
	$dias = $_GET['dias'];
}

if (empty($ordenar_por)) {
	// Se não houver ordenação sempre será analítico
	$tipo_resultado = 'analitico';
}

###INICIO ESTATITICAS###
$cond_0 = " 1=1 ";
if(strlen($valor_chamado)>0){
	if (is_numeric($valor_chamado)){
		$cond_0 = " tbl_hd_chamado.hd_chamado = '$valor_chamado' ";
	}else{
		$valor_chamado = strtoupper($valor_chamado);
		$cond_0 = " upper(tbl_hd_chamado.titulo) ilike '%$valor_chamado%' ";
	}
}

$cond_tipo_chamado = "1 = 1 ";
if ($tipo_chamado == '5') {
	$cond_tipo_chamado = "tbl_hd_chamado.tipo_chamado = 5 ";
}
if ($tipo_chamado == '0') {
	$cond_tipo_chamado = "tbl_hd_chamado.tipo_chamado <> 5 ";
}

###busca###

$sql = "SELECT
			DISTINCT tbl_hd_chamado.hd_chamado,
			tbl_hd_chamado.admin    ,
			tbl_admin.nome_completo ,
			tbl_admin.login         ,
			to_char (tbl_hd_chamado.data,'DD/MM/YY HH24:MI') AS data,
			tbl_hd_chamado.previsao_termino AS previsao,
			to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
			to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
			tbl_hd_chamado.data AS data_order,
			tbl_hd_chamado.previsao_termino AS previsao_termino_order,
			tbl_hd_chamado.previsao_termino_interna AS previsao_termino_interna_order,
			tbl_hd_chamado.titulo,
			tbl_hd_chamado.status,
			tbl_hd_chamado.login_admin,
			CASE
				WHEN tbl_hd_chamado.status = 'Requisitos' AND tbl_hd_chamado_requisito.hd_chamado_requisito IS NOT NULL THEN 'com_requisito'
				WHEN tbl_hd_chamado.status = 'Requisitos' AND tbl_hd_chamado_requisito.hd_chamado_requisito IS NULL THEN 'sem_requisito'
				WHEN tbl_hd_chamado.status = 'Orçamento' AND tbl_hd_chamado.hora_desenvolvimento IS NOT NULL THEN 'com_orcamento'
				WHEN tbl_hd_chamado.status = 'Orçamento' AND tbl_hd_chamado.hora_desenvolvimento IS NULL THEN 'sem_orcamento'
				WHEN tbl_hd_chamado.status = 'Análise' AND tbl_hd_chamado.analise IS NOT NULL THEN 'com_analise'
				WHEN tbl_hd_chamado.status = 'Análise' AND tbl_hd_chamado.analise IS NULL THEN 'sem_analise'
				ELSE tbl_hd_chamado.status
			END AS status_ordem,
			tbl_hd_chamado.atendente,
			tbl_hd_chamado.exigir_resposta,
			tbl_hd_chamado.cobrar,
			tbl_hd_chamado.prioridade,
			tbl_hd_chamado.hora_desenvolvimento,
			tbl_hd_chamado.prazo_horas,
			tbl_hd_chamado.prioridade_supervisor,
			tbl_hd_chamado.fabrica,
			tbl_fabrica.nome AS fabrica_nome,
			tbl_tipo_chamado.descricao as tipo_chamado_descricao,
			CASE WHEN CURRENT_TIMESTAMP > tbl_hd_chamado.previsao_termino
				THEN 1
				ELSE 0
			END AS atrasou,
			CASE WHEN CURRENT_TIMESTAMP > tbl_hd_chamado.previsao_termino_interna
				THEN 1
				ELSE 0
			END AS atrasou_interno
		INTO TEMP tmp_chamado_lista_gerencia_$login_admin
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		LEFT JOIN tbl_hd_chamado_requisito ON tbl_hd_chamado_requisito.hd_chamado = tbl_hd_chamado.hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
		and $cond_tipo_chamado
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

	if ($atendente_responsavel <> ''){
 		$sql .= " AND tbl_hd_chamado.login_admin = $atendente_responsavel ";
	}
	
	if ($autor_busca <> '' and strlen($valor_chamado)==0 ){
		$sql .= " AND tbl_hd_chamado.admin = $autor_busca";
	}

	if($fabrica_busca <> '' and strlen($valor_chamado)==0 ){
		$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";
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

	if ($status_busca=="av" and strlen($valor_chamado)==0){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Verifica' ";
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
		if (!empty($dias)) {
			$i_dias = (int) $dias;
			$sql.= "AND (tbl_hd_chamado.status = 'Novo' OR tbl_hd_chamado.status = 'Suspenso')
					AND (current_date - tbl_hd_chamado.data::date) >= $dias";
		} else {
			if(strlen($ordenar_por) > 0 and $ordenar_por == 2 and strlen($valor_chamado)==0 ){
				$sql .= " AND ((tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
			} else {
				$sql .= " AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido' AND tbl_hd_chamado.status <> 'Suspenso' )) ";
			}
		}
	}

	if (!empty($dias)) {
		$ordemby = " ORDER BY data_order";
	} else {
		$ordemby = " ORDER BY previsao_termino_interna_order ASC, previsao_termino_order ASC, data_order DESC";
	}

	if ($tipo_resultado == 'sintetico') {
		$common_order = '';
		switch ($ordenar_por) {
			case '1':
				$aux_campo = 'atendente';
				break;
			case '2':
				$aux_campo = 'fabrica';
				break;
			case '3':
				$aux_campo = 'tipo_chamado_descricao';
				break;
			case '4':
				$aux_campo = 'status_ordem';
				break;
		}
	} else {
		$common_order = ", previsao_termino_interna_order ASC, previsao_termino_order ASC, data_order DESC";
	}

	if (!empty($ordenar_por) and empty($valor_chamado)) {
		switch ($ordenar_por) {
			case '1':
				$ordemby = " ORDER BY atendente";
				break;
			case '2':
				$ordemby = " ORDER BY fabrica";
				break;
			case '3':
				$ordemby = " ORDER BY tipo_chamado_descricao";
				break;
			case '4':
				$ordena_por_status = 1;
				$ordemby = '';
				break;
		}
	}

	if (empty($dias)) {
		if ($ordenar_por == 3 and $tipo_resultado == 'analitico') {
			$ordemby.= ', fabrica';
		}
		$ordemby.= $common_order;
	} else {
		if (!empty($ordenar_por) and $tipo_resultado == 'analitico') {
			$ordemby.= ', data_order';
		}
	}

#$sql .= $ordemby;
$res = pg_exec ($con,$sql);

if (!pg_last_error()) {
	switch ($tipo_resultado) {
		case 'analitico':
			$campos = '*';
			$join = '';
			$group_by = '';
			break;
		case 'sintetico':
			$campos = 'count(hd_chamado) as total, ';
			$group_by = 'GROUP BY ' . $aux_campo;

		  	switch ($aux_campo) {
				case 'atendente':
					$campos.= 'tbl_admin.nome_completo';
					$join = "JOIN tbl_admin ON tbl_admin.admin = tmp_chamado_lista_gerencia_$login_admin.atendente";
					$ordemby = " ORDER BY tbl_admin.nome_completo";
					$group_by = " GROUP BY tbl_admin.nome_completo";
					break;
				case 'fabrica':
					$campos.= 'tbl_fabrica.nome';
					$join = "JOIN tbl_fabrica ON tbl_fabrica.fabrica = tmp_chamado_lista_gerencia_$login_admin.fabrica";
					$ordemby = " ORDER BY tbl_fabrica.nome";
					$group_by = " GROUP BY tbl_fabrica.nome";
					break;
		  		default:
					$campos.= $aux_campo;
					$join = '';
		  	}

			break;
		default:
			$campos = '*';
			$join = '';
			$group_by = '';
	}

	$n_sql = "SELECT $campos FROM tmp_chamado_lista_gerencia_$login_admin $join $group_by";

	if ($ordena_por_status) {
		$n_sql.= " ORDER BY CASE
							WHEN status_ordem = 'Novo' THEN 0
							WHEN status_ordem = 'sem_requisito' THEN 1
							WHEN status_ordem = 'com_requisito' THEN 2
							WHEN status_ordem = 'sem_orcamento' THEN 3
							WHEN status_ordem = 'com_orcamento' THEN 4
							WHEN status_ordem = 'sem_analise' THEN 5
							WHEN status_ordem = 'com_analise' THEN 6
							WHEN status_ordem = 'Execução' THEN 7
							WHEN status_ordem = 'Teste' THEN 8
							WHEN status_ordem = 'Correção' THEN 9
							WHEN status_ordem = 'Validação' THEN 10
							WHEN status_ordem = 'Efetivação' THEN 11
							WHEN status_ordem = 'Pré-Análise' THEN 12
							WHEN status_ordem = 'Aguard.Execução' THEN 13
							WHEN status_ordem = 'Aguard.Verifica' THEN 14
							WHEN status_ordem = 'Aguard.Admin' THEN 15
							END";

		pg_query($con, "CREATE INDEX tmp_idx_chamado_lista_gerencia_$login_admin ON tmp_chamado_lista_gerencia_$login_admin (status_ordem)");

		if ($tipo_resultado != 'sintetico') {
			$n_sql.= ', fabrica, previsao_termino_interna ASC, previsao_termino ASC, data DESC';
		}
	}
	$n_sql.= $ordemby;
}

//echo nl2br($sql . "<br/><br/>" . $n_sql);

$res = pg_query($con, $n_sql);

if (@pg_numrows($res) > 0) {

/*================================TABELA DE ESCOLHA DE STATUS============================*/


	echo "<FORM METHOD='GET' ACTION='$PHP_SELF'>";

	//hd 17668
	echo "<INPUT TYPE=\"hidden\" name=\"tipo_chamado\" value=\"$tipo_chamado\">";

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
	echo "<td width='150'>Buscar (número ou parte do título)</td>";
	echo "<td width='250'>";
	echo "<input type='text' size='26' maxlength='26' name='valor_chamado' value=''> ";
	echo "</td>";
	echo "<tr >";
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
	echo "<td width='150'>Atendente Responsável</td>";
	echo "<td width='250'>";
		$sqlAtendResponsavel = "SELECT nome_completo, admin
						 FROM    tbl_admin
						 WHERE   tbl_admin.fabrica = 10
						 AND tbl_admin.ativo IS TRUE
						 AND nome_completo NOTNULL
						 ORDER BY tbl_admin.nome_completo;";

		$resAtendResponsavel = pg_query($con, $sqlAtendResponsavel);

		$atendente_responsavel_busca = trim($_POST['atendente_responsavel']);
		if (strlen($atendente_responsavel_busca)==0){
			$atendente_responsavel_busca = trim($_GET['atendente_responsavel']);
		}

		if (pg_num_rows($resAtendResponsavel) > 0) {
			$selectedZero = (strlen($atendente_responsavel_busca) == 0 ) ? 'selected' : '';
			echo '<select class="frm" style="width: 180px;" name="atendente_responsavel">';
			echo '<option value="" $selectedZero>- TODOS -</option>';
			for ($x = 0 ; $x < pg_num_rows($resAtendResponsavel) ; $x++){
				$n_admin = trim(pg_result($resAtendResponsavel,$x,admin));
				$nome_atendente  = trim(pg_result($resAtendResponsavel,$x,nome_completo));
				$selected = ($atendente_responsavel_busca == $n_admin) ? 'selected' : '';

				echo '<option '.$selected .' value="'.$n_admin.'">'.$nome_atendente.'</option>';
			}
			echo '</select>';
		}
	echo "</td>";
	echo "</tr>";



	echo "<tr >";
	echo "<td width='150'>Atendente</td>";
	echo "<td width='250'>";

	$sqlatendente = "SELECT nome_completo,
							admin
					 FROM    tbl_admin
					 WHERE   tbl_admin.fabrica = 10
					 and tbl_admin.ativo is true
							and nome_completo notnull
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
			if ($autor_busca == $n_admin ) echo " SELECTED ";
			echo "> $nome_atendente</option>\n";
		}

		echo "</select>";
	}
	echo "</td>";
	echo "</tr>";

	echo "<tr >";
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
	echo '<tr>';
		echo '<td width="150">Dias</td>';
		echo '<td>';
			echo '<input type="text" name="dias" id="dias" value="' , $dias , '" />';
		echo '</td>';
	echo '</tr>';
	switch ($tipo_resultado) {
		case 'analitico':
			$selected_analitico = ' selected="SELECTED" ';
			$selected_sintetico = '';
			break;
		case 'sintetico':
			$selected_analitico = '';
			$selected_sintetico = ' selected="SELECTED" ';
			break;
		default:
			$selected_analitico = ' selected="SELECTED" ';
			$selected_sintetico = '';
	}
	echo '<tr>';
		echo '<td width="150">Tipo resultado</td>';
		echo '<td>';
			echo '<select class="frm" style="width: 180px" name="tipo_resultado" >';
				echo '<option value="analitico" ' , $selected_analitico  , '>Analítico</option>';
				echo '<option value="sintetico" ' , $selected_sintetico  , '>Sintético</option>';
		echo '</td>';
	echo "<tr>";
	echo "<td colspan='2' align='left'>
		<INPUT TYPE=\"radio\" name=\"ordenar_por\" value=\"1\"> Atendente
		<INPUT TYPE=\"radio\" name=\"ordenar_por\" value=\"2\"> Fábrica
		<INPUT TYPE=\"radio\" name=\"ordenar_por\" value=\"3\"> Tipo Chamado
		<input type='radio' name='ordenar_por' value='4'> Status
		";
	echo "</td>";
	echo "</tr>";
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
	echo "</table>";
	echo "</FORM>";


	##### LEGENDAS #####
	echo "<CENTER><font face='verdana' size='1'><b style='border:1px solid #666666;background-color:#FFD5CC;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Prioridade</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF3333;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Previsão Vencida</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF9966;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Risco de Previsão</b>";

	echo "<br><br><b><font color='#FF0000'>* Analistas acompanhem as previsões de término dos seus chamados, este prazo é visualizado pelo fabricante.</font></b></CENTER><br>";


	/*--=============================== TABELA DE LEGENDA DE CORES ========================--*/

	echo "<table width='630' align='center' cellpadding='0' cellspacing='0' border='0'>";
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
	echo "</td>";


	echo "</tr>";

	echo "</table>";
	echo "<br>";


	/*--=============================== TABELA DE CHAMADOS ========================--*/

	echo "<table width = '100%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";

	if (empty($ordenar_por)) {
		echo "<thead>";
		echo "<tr>";
		echo "	<th >Nº </th>";
		echo "	<th nowrap colspan='2'>Título<img src='/assist/imagens/pixel.gif' width='5'></th>";
		echo "	<th >Status</th>";
		echo "	<th >Tipo</th>";
		echo "	<th >Data</th>";
		echo "	<th nowrap>Autor </strong></th>";
		echo "	<th nowrap>Fábrica </strong></td>";
		echo "	<th nowrap>Responsável</strong></th>";
		echo "	<th nowrap>Atendente </strong></th>";
		echo "	<th nowrap title='Horas Trabalhadas'>Trab.</th>";
		echo "	<th nowrap >Prazo</th>";
		echo "	<th nowrap >Cobrar</th>";
		echo "	<th nowrap>Previsão</td>";
		echo "</tr>";

		echo "</thead>";
	}

	// ##### PAGINACAO ##### //
/*	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $n_sql;
	$sqlCount .= ") AS count";


	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 300;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($n_sql, $sqlCount, $con, "otimizada", "pgsql");*/

	// ##### PAGINACAO ##### //

	$fabrica_anterior   = "";
	$atendente_anterior = "";
	$status_anterior = "";
	$quantidade_chamados = 0;

	if (@pg_numrows($res) > 0) {
		if ($tipo_resultado != 'sintetico')  {

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$hd_chamado               = pg_result($res,$i,hd_chamado);
				$admin                    = pg_result($res,$i,admin);
				$login                    = pg_result($res,$i,login);
				$data                     = pg_result($res,$i,data);
				$titulo                   = pg_result($res,$i,titulo);
				$status                   = pg_result($res,$i,status);
				$status_ordem             = pg_fetch_result($res, $i, 'status_ordem');
				$atendente                = pg_result($res,$i,atendente);
				$atendenteresponsavel     = pg_result($res,$i,login_admin);
				$exigir_resposta          = pg_result($res,$i,exigir_resposta);
				$nome_completo            = trim(pg_result($res,$i,nome_completo));
				$fabrica                  = pg_result($res,$i,fabrica);
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
				$prioridade_supervisor    = pg_result($res,$i,prioridade_supervisor);

				if(strlen($ordenar_por)>0){
					$imprime_cab = 0;
					if($ordenar_por == 1 and $atendente_anterior <> $atendente){
						$atendente_anterior = $atendente;
						$imprime_cab = 1;
					}
					if($ordenar_por == 2 and $fabrica_anterior <> $fabrica){
						$fabrica_anterior = $fabrica;
						$imprime_cab = 1;
					}
					if($ordenar_por == 3 and $tipo_chamado_descricao_anterior <> $tipo_chamado_descricao){
						$tipo_chamado_descricao_anterior = $tipo_chamado_descricao;
						$imprime_cab = 1;
					}
					if ($ordenar_por == 4 and $status_anterior <> $status_ordem) {
						$status_anterior = $status_ordem;
						$imprime_cab = 1;
					}
					if($imprime_cab == 1){
						if($quantidade_chamados != 0){
							echo "<TR>";
							echo "<td colspan='2'></td>";
							echo "<td>Total HD</td>";
							echo "<td>$quantidade_chamados</td>";
							echo "<td colspan='8'></td>";
							echo "</tr>";
							$quantidade_chamados = 0;
						}
						echo "</table>";
						echo "<br><br>";

						if ($ordena_por_status) {
							switch ($status_ordem) {
							case 'com_requisito':
								$cab_status = 'Com requisitos';
								break;
							case 'sem_requisito':
								$cab_status = 'Sem requisitos';
								break;
							case 'com_orcamento':
								$cab_status = 'Com orçamento';
								break;
							case 'sem_orcamento':
								$cab_status = 'Sem orçamento';
								break;
							case 'sem_analise':
								$cab_status = 'Sem análise';
								break;
							case 'com_analise':
								$cab_status = 'Com análise';
								break;
							default:
								$cab_status = $status_ordem;

							}

							echo '<div style="margin: 0 auto; font-weight: bold; width: 900px; margin-bottom: 8px;"> -&gt; ' , $cab_status , '</div>';
						}

						echo "<table width = '100%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";

						echo "<thead>";
						echo "<tr>";
						echo "	<th >Nº </th>";
						echo "	<th nowrap>Título<img src='/assist/imagens/pixel.gif' width='5'></th>";
						echo "	<th nowrap width='4' title='Prioridade Supervisor'>Pri.</th>";
						echo "	<th >Status</th>";
						echo "	<th >Tipo</th>";
						echo "	<th >Data</th>";
						echo "	<th nowrap>Autor </strong></th>";
						echo "	<th nowrap>Fábrica </strong></td>";
						echo "	<th nowrap>Responsável</strong></th>";
						echo "	<th nowrap>Atendente </strong></th>";
						echo "	<th nowrap title='Horas Trabalhadas'>Trab.</th>";
						echo "	<th nowrap >Prazo</th>";
						echo "	<th nowrap >Cobrar</th>";
						echo "	<th nowrap>Previsão</td>";
						echo "</tr>";

						echo "</thead>";
					}
				}
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

				if(!empty($atendente)) {
					$sql2 = "SELECT nome_completo, admin
						FROM	tbl_admin
						WHERE	admin=$atendente";
					$res2 = pg_exec ($con,$sql2);
					$xatendente            = pg_result($res2,0,nome_completo);
				}
				if(!empty($atendenteresponsavel)) {
					$sqlAtendenteResponsavel = "SELECT nome_completo
						FROM	tbl_admin
						WHERE	admin=$atendenteresponsavel";
					$resAtendenteResponsavel = pg_query($con, $sqlAtendenteResponsavel);
					$xatendenteResponsavel   = pg_result($resAtendenteResponsavel,0,nome_completo);
				}
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

				for($r = 0 ; $r < count($chamado_interno); $r++){
					if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' width='10' title='Contém chamado interno' border='0'>";
				}
				echo "<tbody>";
				$quantidade_chamados++;
				echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";

				//			 echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

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

				echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' title='$titulo'>$interno ";
				echo substr($titulo,0,20)."...</a></td>";

				echo "<td align='right' width='4'>$prioridade_supervisor</td>";

				if (($status != 'Resolvido') and ($status != 'Cancelado')) {
					$cor_status="#000000";
					if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
					if($status=="Execução")$cor_status="#0000FF";
					if($status=="Aguard.Execução")$cor_status="#339900";
					if($status=="Aguard.Verifica")$cor_status="#ffff00";
					echo "<td nowrap><font color='$cor_status' size='1'><B>$status </B></font></td>";
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
				echo "<td nowrap width='80'><font size='1'>$xatendenteResponsavel</font></td>";
				echo "<td nowrap><font size='1'>$xatendente</font></td>";
				echo "<td nowrap align='center'><font size='1'>$horas</font></td>";
				echo "<td nowrap align='center'><font size='1'>$prazo_horas</font></td>";
				echo "<td nowrap align='center'><font size='1'>$hora_desenvolvimento</font></td>";
				echo "<td width='60'><font size='1'>$previsao_termino</font></td>";

				echo "</tr>";
				$interno='';
			}
			if($quantidade_chamados != 0){
				echo "<TR>";
				echo "<td colspan='2'></td>";
				echo "<td>Total HD</td>";
				echo "<td>$quantidade_chamados</td>";
				echo "<td colspan='8'></td>";
				echo "</tr>";
				$quantidade_chamados = 0;
			}
			echo "</tbody>";
		} else {
			switch ($aux_campo) {
				case 'tipo_chamado_descricao':
					$xx_cab = 'Tipo Chamado';
					break;
				case 'status_ordem':
					$xx_cab = 'Status';
					break;
				default:
					$xx_cab = ucfirst($aux_campo);
					break;
			}
			echo '<thead>';
				echo '<tr>';
					echo '<th style="width: 80px;">Chamados</th>';
					echo '<th>' , $xx_cab , '</th>';
				echo '</tr>';
			echo '</thead>';
			echo '</tbody>';
			$t_total = 0;
			$ctl = 1;
			while ($fetch = pg_fetch_array($res)) {
				$total = $fetch[0];
				$group = $fetch[1];
				$t_total = $t_total + $total;

				switch ($group) {
					case 'sem_requisito':
						$xx_group = 'Sem requisitos';
						break;
					case 'com_requisito':
						$xx_group = 'Com requisitos';
						break;
					case 'sem_orcamento':
						$xx_group = 'Sem orçamento';
						break;
					case 'com_orcamento':
						$xx_group = 'Com orçamento';
						break;
					case 'sem_analise':
						$xx_group = 'Sem análise';
						break;
					case 'com_analise':
						$xx_group = 'Com análise';
						break;
					default:
						$xx_group = $group;
				}

				if (($ctl % 2) == 0) {
					$bgcolor = 'background: #F2F7FF';
				} else {
					$bgcolor = '';
				}

				$ctl++;

				echo '<tr style="height: 25px; ' , $bgcolor , '">';
					echo '<td style="width: 60px; padding-left: 20px;">' , $total , '</td>';
					echo '<td>' , $xx_group , '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '<tfoot>';
				echo '<tr style="height: 25px; font-weight: bold;">';
					echo '<td style="width: 60px; padding-left: 20px;">' , $t_total , '</td>';
					echo '<td>Total</td>';
				echo '</tr>';
			echo '</tfoot>';
		}


	echo "</table>";
	### PÉ PAGINACAO###

		if ($chamados_atrasados == 1){
			echo "<center><h3>Chamados atrasados! Concluir com URGÊNCIA.</h3><center>";
		}


		echo "<table border='0' align='center'>";
		echo "<tr>";
		echo "<td colspan='10' align='center'>";
			// ##### PAGINACAO ##### //

		// links da paginacao
		echo "<br>";

		/*if($pagina < $max_links) {
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



		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<font size='2'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.</font>";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}*/
		// ##### PAGINACAO ##### //

		}

		echo "</td>";
		echo "</tr>";

		echo "</table>";

	}
include "rodape.php"
?>
