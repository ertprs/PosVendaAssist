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
<!-- <script type="text/javascript" src="../js/jquery-1.3.2.js"></script>
 -->
<script type="text/javascript" src="../js/jquery-1.7.2.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter-2.0.min.js"></script>

<style>
	.link_log{
		font-family: Verdana;
		font-size: 11px;
		color: #333399;
	}

	table.relatorio {
		border-collapse: collapse;
		font-family: Verdana;
		font-size: 10px;
		width: 1200px;
	}

	table.relatorio thead {
		background-color:#d9e8ff;
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
		cursor: default;
		text-align: center;
	}

	table.relatorio td {
		padding: 1px 5px;
		border-bottom: 1px solid #95bce2;
		font-family: Verdana;
	}

	table.relatorio tr.alt td {
		background: #ecf6fc;

	}

	table.relatorio tr.over td {
		/*background: #bcd4ec;*/
		background: #000;

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
	td>span.ellip {
		display: inline-block;
		white-space: no-wrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.totalHDs{
		font-weight:bold;
		font-size: 13px !important;
	}
	h2{
		font-size:2.5em !important;
	}

	div.helpdesk_pendencia {
		float: right;
		margin-right: 20%;
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
if (strlen($projeto)    	 == 0) $projeto     	= trim($_GET['projeto']);
if (strlen($atendente_responsavel) == 0) $atendente_responsavel = trim($_GET['atendente_responsavel']);

?>

<script>
$(document).ready(function() {

	if("<?=$status_busca?>" != "") {
		$("select[name=status] option[value=<?=$status_busca?>]").attr('selected', 'selected');
	}

	if("<?=$projeto?>" != "- ESCOLHA -") {
		$("select[name=projeto] option[value=<?=$projeto?>]").attr('selected', 'selected');
	}

	$("input[name=valor_chamado]").val("<?=$valor_chamado?>");

	$.tablesorter.defaults.widgets = ['zebra'];
	$("table#relatorio").tablesorter({textExtraction: 'complex'});
	// $(".relatorio tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
});
</script>

<?
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

if($projeto != '- ESCOLHA -' && !empty($projeto)) {
	$cond_projeto = " AND tbl_hd_chamado.projeto = $projeto";
}

if($status_busca == "Novo"){
	$cond_status = " AND    tbl_hd_chamado.status = 'Novo'";
}
$sql = "SELECT
			distinct tbl_hd_chamado.hd_chamado,
			tbl_hd_chamado.admin    ,
			tbl_admin.nome_completo ,
			tbl_admin.login         ,
			CASE WHEN data < DATE_TRUNC('YEAR', CURRENT_DATE)
				 THEN TO_CHAR(tbl_hd_chamado.data,'DD/MM/YY HH24:MI')
				 ELSE TO_CHAR(tbl_hd_chamado.data,'DD/MM HH24:MI')
			END AS data,
			tbl_hd_chamado.previsao_termino AS previsao,
			CASE WHEN previsao_termino < DATE_TRUNC('YEAR', CURRENT_DATE)
				 THEN TO_CHAR(tbl_hd_chamado.previsao_termino,'DD/MM/YY HH24:MI')
				 ELSE TO_CHAR(tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI')
			END AS previsao_termino,
			CASE WHEN previsao_termino_interna < DATE_TRUNC('YEAR', CURRENT_DATE)
				 THEN TO_CHAR(previsao_termino_interna,'DD/MM/YY HH24:MI')
				 ELSE TO_CHAR(previsao_termino_interna,'DD/MM HH24:MI')
			END AS previsao_termino_interna,
			tbl_hd_chamado.titulo,
			tbl_hd_chamado.status,
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
			END AS atrasou_interno,
			(SELECT nome_completo
				FROM	tbl_admin
				WHERE	admin=tbl_hd_chamado.atendente) as nome_atendente,
			(SELECT nome_completo
				FROM	tbl_admin
				WHERE	admin=tbl_hd_chamado.login_admin) as nome_atendente_responsavel,
			(SELECT responsabilidade
				FROM	tbl_admin
				WHERE	admin=tbl_hd_chamado.atendente) as celula,

			(SELECT grupo_admin
				FROM	tbl_admin
				WHERE	admin=tbl_hd_chamado.atendente) as grupo_admin,
			(SELECT CASE WHEN grupo_admin = 6 THEN 1
					WHEN grupo_admin = 9 THEN 2
					WHEN grupo_admin = 2 THEN 3
					WHEN grupo_admin = 1 THEN 3
					WHEN grupo_admin = 7 THEN 3
					WHEN grupo_admin = 4 THEN 4
					WHEN grupo_admin = 3 THEN 5
					WHEN grupo_admin = 5 THEN 5
				END
				FROM	tbl_admin
				WHERE	admin=tbl_hd_chamado.atendente) as ordem_grupo
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado

		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		left JOIN tbl_hd_chamado_requisito ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_requisito.hd_chamado
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		$cond_status
		AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
		and $cond_tipo_chamado
		and $cond_0
		$cond_projeto ";

	if (strlen($data_inicio)>0 AND strlen($data_fim)>0){
		$status_busca = 'Resolvido';

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

	if ($atendente_busca <> ''){
		$sql .= " AND tbl_hd_chamado.atendente = $atendente_busca";
	}

	if ($autor_busca <> ''){
		$sql .= " AND tbl_hd_chamado.admin = $autor_busca";
	}

	if($fabrica_busca <> ''){
		$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";
	}

	if (strlen($status_busca) > 0) {	
		$sql .= "AND tbl_hd_chamado.status = '$status_busca' ";
	}

	if($login_fabrica == 10){

		if(strlen($atendente_responsavel) > 0){
			$sql .= " AND tbl_hd_chamado.login_admin = {$atendente_responsavel} ";
		}

	}else{

		if ($atendente_responsavel <> ''){
	 		$sql .= "AND (tbl_hd_chamado.login_admin = $atendente_responsavel  OR tbl_hd_chamado_requisito.admin = $atendente_responsavel) ";
		}

	}

	/*if ($status_busca=="p"){
		$sql .= " AND tbl_hd_chamado.status = 'Aprovação' ";
	}

	if ($status_busca=="a"){
		$sql .= " AND tbl_hd_chamado.status = 'Análise' ";
	}

	if ($status_busca=="r"){
		$sql .= " AND tbl_hd_chamado.status = 'Resolvido' ";
	}

	if ($status_busca=="av"){
		$sql .= " AND tbl_hd_chamado.status = 'Aguard.Verifica' ";
	}

	if ($status_busca=="e"){
		$sql .= " AND tbl_hd_chamado.status = 'Execução' ";
	}

	if ($status_busca=="ae"){
		$sql .= " AND tbl_hd_chamado.status = 'Aguard.Execução' ";
	}

	if ($status_busca=="c"){
		$sql .= " AND tbl_hd_chamado.status = 'Cancelado' ";
	}

	if ($status_busca=="o"){
		$sql .= " AND tbl_hd_chamado.status = 'Orçamento' ";
	}*/

	if ($status_busca == "Novo"){
		$sql .= " AND (tbl_hd_chamado.status = 'Novo' OR tbl_hd_chamado.status IS NULL)";
	} else  {
	}

	if ($status_busca==''){
		if(strlen($ordenar_por) > 0 and $ordenar_por == 5){
				$sql .= " AND tbl_hd_chamado.status IN('Cancelado')
						AND hora_desenvolvimento > 0 ";
		}elseif(strlen($ordenar_por) > 0 and $ordenar_por == 2){
			$sql .= " AND (tbl_hd_chamado.status NOT IN('Cancelado','Resolvido') ) ";
		}else{
			$sql .= " AND (tbl_hd_chamado.status NOT IN('Aprovação','Cancelado','Resolvido','Suspenso','Orçamento') " .
			   		"     or (tbl_hd_chamado.status = 'Orçamento')) ";
		}
	}

	$ordemby = " ORDER BY previsao_termino_interna ASC,previsao_termino ASC,data DESC";
	if(strlen($ordenar_por) > 0 and $ordenar_por == 1){		
		// $ordemby = " ORDER BY tbl_hd_chamado.atendente, tbl_hd_chamado.previsao_termino_interna ASC,tbl_hd_chamado.previsao_termino ASC,tbl_hd_chamado.data DESC";
		$ordemby = " ORDER BY ordem_grupo, nome_atendente, previsao_termino_interna ASC,previsao_termino ASC,data DESC";

	}
	if(strlen($ordenar_por) > 0 and $ordenar_por == 2){
		// $ordemby = " ORDER BY tbl_hd_chamado.fabrica, tbl_hd_chamado.previsao_termino_interna ASC,tbl_hd_chamado.previsao_termino ASC,tbl_hd_chamado.data DESC";
		$ordemby = " ORDER BY fabrica_nome, previsao_termino_interna ASC,previsao_termino ASC,data DESC";
	}
	if(strlen($ordenar_por) > 0 and $ordenar_por == 3){
		$ordemby = " ORDER BY tbl_tipo_chamado.descricao, fabrica, previsao_termino_interna ASC,previsao_termino ASC,data DESC";
	}

	if($ordenar_por == 4){
		$ordemby = " ORDER BY celula, fabrica, previsao_termino_interna ASC,previsao_termino ASC,data DESC";
	}

	if($ordenar_por == 6){
		$ordemby = " ORDER BY nome_atendente_responsavel, previsao_termino_interna ASC,previsao_termino ASC,data DESC";
		//$ordemby = " ORDER BY ordem_grupo, nome_atendente, tbl_hd_chamado.previsao_termino_interna ASC,tbl_hd_chamado.previsao_termino ASC,tbl_hd_chamado.data DESC";
	}

$sql .= $ordemby;

$res = pg_exec ($con,$sql);
if (@pg_num_rows($res) >= 0) {

/*================================TABELA DE ESCOLHA DE STATUS============================*/

	$style_form = "";
	if (in_array($login_admin, array(5205,4789,8527)) || ($analista_hd == 'sim' && $login_admin != 586) || !in_array($grupo_admin, array(3,4,5,7,8))) {
		include_once("icone_pendencia_helpdesk.php");
		$style_form = "style='margin-left: 27%;'"; 	
	}
	echo "<form method='get' action='$PHP_SELF' $style_form>";
	//hd 17668
	echo "<INPUT TYPE=\"hidden\" name=\"tipo_chamado\" value=\"$tipo_chamado\">";

	echo "<table width = '800' align = 'center' cellpadding='0' cellspacing='0' border='0' style='font-family: verdana ; font-size:11px ; color: #666666'>";
	echo "<tr>";
	echo "	<td background='imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='../imagens/pixel.gif' width='9'></td>";
	echo "	<td background='imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
	echo "	<td background='imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='../imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' >";
	echo "	<td><b><CENTER>Pesquisa Chamados</CENTER></b></td>";
	echo "</tr>";
	echo "<tr align='left'  height ='70' valign='top'>";
	echo "	<td background='../imagens/fundo_tabela_centro_esquerdo.gif' ><img src='../imagens/pixel.gif' width='9'></td>";
	echo "<td>";

	echo "<table border='0'  cellpadding='2' cellspacing='3' width='700' align='center' style='font-family: verdana ; font-size:11px ; color: #00000'>";

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
	/*echo "<option value='n'>Novo</option>\n";
	echo "<option value='a'>Análise</option>\n";
	echo "<option value='o'>Orçamento</option>\n";
	echo "<option value='ae'>Aguardando Execução</option>\n";
	echo "<option value='e'>Execução</option>\n";
	echo "<option value=''>Em Aberto</option>\n";
	echo "<option value='r'>Resolvido</option>\n";
	echo "<option value='p'>Aprovação</option>\n";
	echo "<option value='av'>Aguardando Verificação</option>\n";
	echo "<option value='c'>Cancelado</option>\n";*/

	echo "<option value='Novo' >Novo</option>
	<option value='Requisitos' >Requisitos</option>
	<option value='Orçamento' >Orçamento</option>
	<option value='Análise' >Análise</option>
	<option value='Aguard.Execução' >Aguard.Execução</option>
	<option value='Execução' >Execução</option>
	<option value='Validação' >Validação</option>
	<option value='EfetivaçãoHomologação' >Efetivação Homologação</option>
	<option value='ValidaçãoHomologação' >Validação Homologação</option>
	<option value='Efetivação' >Efetivação</option>
	<option value='Correção' >Correção</option>
	<option value='Parado' >Parado</option>
	<option value='Impedimento' >Impedimento</option>
	<option value='Suspenso' >Suspenso </option>
	<option value='Aguard.Admin' >Aguard.Admin</option>
	<option value='Resolvido' >Resolvido</option>
	<option value='Cancelado' >Cancelado</option>
	<option value='Aprovação' >Aprovação</option>
	</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr >";
	echo "<td width='150'>Atendente Responsável</td>";
	echo "</td>";
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
					 AND tbl_admin.ativo IS TRUE
							AND nome_completo NOTNULL
					 ORDER BY tbl_admin.nome_completo;";

	$resatendente = pg_exec ($con,$sqlatendente);

	$atendente_busca = trim($_POST['atendente_busca']);
	if (strlen($atendente_busca)==0){
		$atendente_busca = trim($_GET['atendente_busca']);
	}

	if (pg_num_rows($resatendente) > 0) {
		echo "<select class='frm' style='width: 180px;' name='atendente_busca'>\n";
		echo "<option value='' ";
		if (strlen ($atendente_busca) == 0 ) echo " SELECTED ";
		echo ">- TODOS -</option>\n";

		for ($x = 0 ; $x < pg_num_rows($resatendente) ; $x++){
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

	if (pg_num_rows($resatendente) > 0) {

		echo "<select class='frm' style='width: 180px;' name='autor_busca'>\n";
		echo "<option value='' ";
		if (strlen ($atendente_busca) == 0 ) echo " SELECTED ";
		echo ">- TODOS -</option>\n";

		for ($x = 0 ; $x < pg_num_rows($resatendente) ; $x++){
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
					WHERE ativo_fabrica IS TRUE
					ORDER BY nome";
	$resfabrica = pg_exec ($con,$sqlfabrica);
	$n_fabricas = pg_num_rows($res);


	echo "<select class='frm' style='width: 180px;' name='fabrica_busca'>\n";
	echo "<option value=''></option>\n";
	for ($x = 0 ; $x < pg_num_rows($resfabrica) ; $x++){
		$fabrica   = trim(pg_result($resfabrica,$x,fabrica));
		$nome      = trim(pg_result($resfabrica,$x,nome));
		echo "<option value='$nome'"; if ($fabrica_busca == $nome) echo " SELECTED "; echo ">$nome</option>\n";
	}
	echo "</select>\n";
	echo "</td>";
	echo "</tr>";
?>

<tr>
	<td>Projeto</td>
	<td>
		<select name="projeto" style='width: 180px;'>
			<option>- ESCOLHA -</option>
			<?
				$sql_p = "SELECT *
						  FROM tbl_projeto
						  WHERE projeto_principal IS NOT NULL";

				$res_p = pg_query($con, $sql_p);

				while($projeto_r = pg_fetch_object($res_p))
					echo "<option value='$projeto_r->projeto'>$projeto_r->nome</option>";
			?>
		</select>
		<div id='result3' style='position:absolute; display:none; border: 1px solid #949494;background-color: #F1F0E7;width:150px;'>
		</div>
	</td>
</tr>

<?
	echo "<tr>";
	echo "<td colspan='2' align='left'>
		<INPUT TYPE=\"radio\" name=\"ordenar_por\" id='r_at' value=\"1\"> <label for='r_at'>Atendente</label>
		<INPUT TYPE=\"radio\" name=\"ordenar_por\" id='r_atr' value=\"6\"> <label for='r_atr'>Atendente Responsável</label>
		<INPUT TYPE=\"radio\" name=\"ordenar_por\" id='r_fa' value=\"2\"> <label for='r_fa'>Fábrica</label>
		<INPUT TYPE=\"radio\" name=\"ordenar_por\" id='r_hd' value=\"3\"> <label for='r_hd'>Tipo Chamado</label>
		<INPUT TYPE=\"radio\" name=\"ordenar_por\" id='r_cel' value=\"4\"> <label for='r_cel'>Célula</label>
		<INPUT TYPE=\"radio\" name=\"ordenar_por\" id='' value=\"5\"> <label for='r_cel'>Orçamento Cancelado</label>
		";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='2' align='center'> <INPUT TYPE=\"submit\" value=\"Pesquisar\">";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "</td>";
	echo "<td background='../imagens/fundo_tabela_centro_direito.gif' ><img src='../imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td background='../imagens/fundo_tabela_baixo_esquerdo.gif'><img src='../imagens/pixel.gif' width='9'></td>";
	echo "	<td background='../imagens/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
	echo "	<td background='../imagens/fundo_tabela_baixo_direito.gif'><img src='../imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</table>";
	echo "</FORM>";


	##### LEGENDAS #####
	echo "<CENTER><font face='verdana' size='1'><b style='border:1px solid #666666;background-color:#FFD5CC;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Prioridade</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF3333;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Previsão Vencida</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#F8FBB3;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Previsão Interna Vencida</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF9966;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Risco de Previsão</b>";

	echo "<br><br><b><font color='#FF0000'>* Analistas acompanhem as previsões de término dos seus chamados, este prazo é visualizado pelo fabricante.</font></b></CENTER><br>";


	/*--=============================== TABELA DE LEGENDA DE CORES ========================--*/

	echo "<table width='630' align='center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr style='font-family: arial ; color: #666666' align='center'>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='../admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> Aguardando resposta do cliente";
	echo "</td>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='../admin/imagens_admin/status_azul.gif' valign='absmiddle'> Pendente Telecontrol";
	echo "</td>";

	echo "</tr>";

	echo "<tr style='font-family: arial ; color: #666666' align='center'>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='../admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> Orçamento";
	echo "</td>";

	/* echo "<td width='50%' nowrap align='left'>";
	echo "<img src='../admin/imagens_admin/status_verde.gif' valign='absmiddle'> Resolvido";
	echo "</td>"; */


	echo "</tr>";

	echo "</table>";
	echo "<br>";


	/*--=============================== TABELA DE CHAMADOS ========================--*/

	echo "<table align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
// quando não houver parametro para ordenação
	if(empty($ordenar_por)) {
		echo "<thead>";
		echo "<tr>";
		echo "	<th nowrap >Nº </th>";
		echo "	<th >Título<img src='../imagens/pixel.gif' width='5'></th>";
		echo "	<th nowrap title='Prioridade do Chamado, estabelecida pelo Supervisor da Fábrica'>Pri.</th>";
		echo "	<th nowrap >Status</th>";
		echo "	<th nowrap >Tipo</th>";
		echo "	<th nowrap >Data</th>";
		echo "	<th nowrap >Autor </strong></th>";
		echo "	<th >Fábrica </strong></td>";
		echo "	<th >Atendente Responsável </strong></th>";
		echo "	<th >Atendente </strong></th>";
		echo "	<th title='Horas Trabalhadas'>Trab.</th>";
		echo "	<th >Prazo</th>";
		echo "	<th >Cobrar</th>";
		echo "	<th >Previsão Cliente</td>";
		echo "  <th >Previsão Interna</td>";
		echo "</tr>";

		echo "</thead>";
	}
	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";


	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 300;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //

	$fabrica_anterior   = "";
	$atendente_anterior = "";
	$nome_atendente_responsavel_anterior = "";
	$quantidade_chamados = 0;
	$at_ant = null;
	$numRows   = pg_num_rows($res);
	if($numRows<=0) {
		echo "<tr><td colspan='15' style='text-align: center; color: #FF0000; font-weight: bold;'>Nenhum resultado encontrado</td></tr>";
	} else {		
		for ($i = 0 ; $i < $numRows ; $i++) {


			switch($ordenar_por) {
				case 1:
					$lastAtendente = $xatendente;
					$lastGrupoAdmin = $ordem_grupo;
					break;
				case 2:
					$lastFabricaNome = $fabrica_nome;
					break;
				case 6:
					$lastAtendenteResponsavel = $nome_atendente_responsavel;
					break;
				case 3:
					$lastTipoChamado= $tipo_chamado_descricao;
					break;
				case 4: $lastCelula = $celula;break;
			}
			$hd_chamado               = pg_result($res,$i,hd_chamado);
			$admin                    = pg_result($res,$i,admin);
			$login                    = pg_result($res,$i,login);
			$data                     = pg_result($res,$i,data);
			$titulo                   = pg_result($res,$i,titulo);
			$status                   = pg_result($res,$i,status);
			$atendente                = pg_result($res,$i,atendente);
			$exigir_resposta          = pg_result($res,$i,exigir_resposta);
			$nome_completo            = trim(pg_result($res,$i,nome_completo));
			$fabrica                  = pg_result($res,$i,fabrica);
			$fabrica_nome             = trim(pg_result($res,$i,fabrica_nome));
			$previsao_termino         = trim(pg_result($res,$i,previsao_termino));
			$previsao                 = trim(pg_result($res,$i,previsao));
			$previsao_termino_interna = trim(pg_result($res,$i,previsao_termino_interna));
			$atrasou                  = trim(pg_result($res,$i,atrasou));
			$atrasou_interno          = trim(pg_result($res,$i,atrasou_interno));
			$celula                  = trim(pg_result($res,$i,celula));
			$cobrar                   = trim(pg_result($res,$i,cobrar));
			$hora_desenvolvimento     = trim(pg_result($res,$i,hora_desenvolvimento));
			$tipo_chamado_descricao   = trim(pg_result($res,$i,tipo_chamado_descricao));
			$prazo_horas              = pg_result($res,$i,prazo_horas);
			$prioridade               = pg_result($res,$i,prioridade);
			$prioridade_supervisor    = pg_result($res,$i,prioridade_supervisor);
			$nome_atendente    		  = pg_result($res,$i,nome_atendente);
			$nome_atendente_responsavel = pg_result($res,$i,nome_atendente_responsavel);
			$grupo_admin    		  = pg_result($res,$i,grupo_admin);
			$ordem_grupo    		  = pg_result($res,$i,ordem_grupo);
			if(!empty($atendente)) {
				$sql2 = "SELECT nome_completo, admin
					FROM	tbl_admin
					WHERE	admin='$atendente'";
				$res2 = pg_exec ($con,$sql2);
			}

			$xatendente            = pg_result($res2,0,nome_completo);
			//$xxatendente = explode(" ", $xatendente);

			####hd_chamado=2728371####
			$sqlReq = "SELECT tbl_hd_chamado_requisito.hd_chamado_requisito
						FROM tbl_hd_chamado_requisito
						WHERE tbl_hd_chamado_requisito.hd_chamado = $hd_chamado
						AND tbl_hd_chamado_requisito.excluido IS FALSE
						";
			$resReq = pg_query($con, $sqlReq);
			$requisito_id = null;
			if(pg_num_rows($resReq) > 0){
				$requisito_id = pg_fetch_result($resReq, 0, 'hd_chamado_requisito');
			}
			####FIM-hd_chamado=2728371####
			if(strlen($ordenar_por)>0){
				$imprime_cab = 0;
				if($ordenar_por == 1 and $atendente_anterior <> $atendente){
					$atendente_anterior = $atendente;
					$imprime_cab = 1;
				}
				if($ordenar_por == 6 and $nome_atendente_responsavel_anterior <> $nome_atendente_responsavel){
					$nome_atendente_responsavel_anterior = $nome_atendente_responsavel;
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
				if($ordenar_por == 4 and $celula_anterior <> $celula){
					$celula_anterior = $celula;
					$imprime_cab = 1;
				}

				if($imprime_cab == 1){
					if($quantidade_chamados != 0){
						echo "<TR >";
						if($ordenar_por==1){
							echo "<td class='totalHDs' colspan='2'>$lastAtendente</td>";
						}else if($ordenar_por==2){
							echo "<td class='totalHDs' colspan='2'>$lastFabricaNome</td>";
						}else if($ordenar_por==3){
							echo "<td class='totalHDs' colspan='2'>$lastTipoChamado</td>";
						}else if($ordenar_por==4){
							echo "<td class='totalHDs' colspan='2'>$lastCelula</td>";
						}else if($ordenar_por==6){
							echo "<td class='totalHDs' colspan='2'>$lastAtendenteResponsavel</td>";
						}

						echo "<td class='totalHDs' colspan='15'>Total HD: $quantidade_chamados</td>";

						echo "</tr>";
						$quantidade_chamados = 0;
					}

					echo "<tr><td style='border-bottom:0px;' colspan='13'><br><br></td></tr>";
					if(($lastGrupoAdmin != $ordem_grupo) && ($ordenar_por == 1)){
						echo "<tr><td style='border-bottom:0px;' colspan =13> <h2>";
						//switch para definir os grupos
						switch($ordem_grupo){
							case 1:
								echo "Suporte";
								// echo "Analista Master";
							break;
							case 2:
							        echo "Melhoria";
							break;
							case 3:
								echo "Analistas";

							break;
							case 4:
								echo "Desenvolvedores";
								// echo "Tester Master";
							break;
							case 5:
								echo "Testers";
								// echo "Desenvolvedores";
							break;
						}
						echo "</h2>";

						echo 		"</td>";
						echo 	"</tr>";

					}
					if($i==0){
						// echo "<table align = 'center' cellpadding='2' cellspacing='1' border='1' name='relatorio' id='relatorio' class='relatorio'>";
					}

					echo "<tr>";
					echo "	<th nowrap >Nº </th>";
					echo "	<th >Título<img src='../imagens/pixel.gif' width='5'></th>";
					echo "	<th nowrap title='Prioridade do Chamado, estabelecida pelo Supervisor da Fábrica'>Pri.</th>";
					echo "	<th nowrap >Status</th>";
					echo "	<th nowrap >Tipo</th>";
					echo "	<th nowrap >Data</th>";
					echo "	<th nowrap >Autor </strong></th>";
					echo "	<th >Fábrica </strong></td>";
					echo "	<th >Atendente Responsável </strong></th>";
					echo "	<th >Atendente </strong></th>";
					echo "	<th title='Horas Trabalhadas'>Trab.</th>";
					echo "	<th >Prazo</th>";
					echo "	<th >Cobrar</th>";
					echo "	<th >Previsão Cliente</td>";
					echo "  <th >Previsão Interna</td>";
					echo "</tr>";
				}
			}


			if ($status == 'Aprovação' OR $status == 'Resolvido' OR $status == 'Cancelado'){
				$atrasou = 0;
			}

			if ($atrasou == 0 AND $chamados_atrasados==1){
				//break;
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
			if(($atendente <> $at_ant or empty($at_ant)) and $ordenar_por == 1 /* and $grupo_admin == 6 */ ) {
				
				if($login_fabrica == 10 && $ordenar_por == "1"){

					$cond_tipo_atendente = " 
											CASE WHEN tbl_hd_chamado.atendente != {$atendente} THEN 1 ELSE 2 END AS tipo_atendente, 
											CASE WHEN tbl_hd_chamado.login_admin != {$atendente} THEN 1 ELSE 2 END AS tipo_atendente_responsavel, ";
					$cond_atendente      = " (tbl_hd_chamado.login_admin = $atendente OR tbl_hd_chamado.atendente = $atendente) ";
					$cond_order_by       = " tipo_atendente ASC, tipo_atendente_responsavel ASC, tbl_hd_chamado.status DESC ";

				}else{

					$cond_tipo_atendente = "";
					$cond_atendente      = " (login_admin = $atendente OR tbl_hd_chamado_requisito.admin = $atendente) AND atendente <> $atendente ";
					$cond_order_by       = " tbl_hd_chamado.status DESC ";

				}

				$sqlhd = "SELECT DISTINCT hd_chamado,
						tbl_hd_chamado.admin    ,
						tbl_admin.nome_completo ,
						tbl_admin.login         ,
						CASE WHEN data < DATE_TRUNC('YEAR', CURRENT_DATE)
							THEN TO_CHAR(tbl_hd_chamado.data,'DD/MM/YY HH24:MI')
							ELSE TO_CHAR(tbl_hd_chamado.data,'DD/MM HH24:MI')
						END AS data,
						tbl_hd_chamado.previsao_termino AS previsao,
						CASE WHEN previsao_termino < DATE_TRUNC('YEAR', CURRENT_DATE)
							THEN TO_CHAR(tbl_hd_chamado.previsao_termino,'DD/MM/YY HH24:MI')
							ELSE TO_CHAR(tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI')
						END AS previsao_termino,
						CASE WHEN previsao_termino_interna < DATE_TRUNC('YEAR', CURRENT_DATE)
							THEN TO_CHAR(previsao_termino_interna,'DD/MM/YY HH24:MI')
							ELSE TO_CHAR(previsao_termino_interna,'DD/MM HH24:MI')
						END AS previsao_termino_interna,
						tbl_hd_chamado.titulo,
						tbl_hd_chamado.status,
						tbl_hd_chamado.atendente,
						tbl_hd_chamado.exigir_resposta,
						tbl_hd_chamado.cobrar,
						tbl_hd_chamado.prioridade,
						tbl_hd_chamado.hora_desenvolvimento,
						tbl_hd_chamado.prazo_horas,
						tbl_hd_chamado.prioridade_supervisor,
						tbl_hd_chamado.fabrica,
						tbl_fabrica.nome AS fabrica_nome,
						tbl_tipo_chamado.descricao AS tipo_chamado_descricao,
						{$cond_tipo_atendente}
						CASE WHEN CURRENT_TIMESTAMP > tbl_hd_chamado.previsao_termino
							THEN 1
							ELSE 0
						END AS atrasou,
						CASE WHEN CURRENT_TIMESTAMP > tbl_hd_chamado.previsao_termino_interna
							THEN 1
							ELSE 0
						END AS atrasou_interno,
						(SELECT nome_completo FROM tbl_admin WHERE admin = tbl_hd_chamado.login_admin) AS nome_atendente_responsavel,
						(SELECT nome_completo FROM tbl_admin WHERE admin = tbl_hd_chamado.atendente) AS nome_atendente 
					FROM tbl_hd_chamado
					INNER JOIN tbl_admin using(admin)
					INNER JOIN tbl_tipo_chamado using(tipo_chamado)
					INNER JOIN tbl_fabrica on tbl_admin.fabrica = tbl_fabrica.fabrica
					LEFT JOIN tbl_hd_chamado_requisito using(hd_chamado)
					WHERE 
						{$cond_atendente}
						AND tbl_hd_chamado.status NOT IN('Cancelado','Resolvido')
				    ORDER BY {$cond_order_by}";

				$reshd = pg_query($con,$sqlhd);

				echo "<tbody>";

				if(pg_num_rows($reshd) > 0) {

					for($h=0;$h<pg_num_rows($reshd);$h++) {
						
						$at_hd_chamado               = pg_result($reshd,$h,hd_chamado);
						$at_admin                    = pg_result($reshd,$h,admin);
						$at_login                    = pg_result($reshd,$h,login);
						$at_data                     = pg_result($reshd,$h,data);
						$at_titulo                   = pg_result($reshd,$h,titulo);
						$at_status                   = pg_result($reshd,$h,status);
						$at_atendente                = pg_result($reshd,$h,atendente);
						$at_exigir_resposta          = pg_result($reshd,$h,exigir_resposta);
						$at_nome_completo            = trim(pg_result($reshd,$h,nome_completo));
						$at_fabrica                  = pg_result($reshd,$h,fabrica);
						$at_fabrica_nome             = trim(pg_result($reshd,$h,fabrica_nome));
						$at_previsao_termino         = trim(pg_result($reshd,$h,previsao_termino));
						$at_previsao                 = trim(pg_result($reshd,$h,previsao));
						$at_previsao_termino_interna = trim(pg_result($reshd,$h,previsao_termino_interna));
						$at_atrasou                  = trim(pg_result($reshd,$h,atrasou));
						$at_atrasou_interno          = trim(pg_result($reshd,$h,atrasou_interno));
						$at_cobrar                   = trim(pg_result($reshd,$h,cobrar));
						$at_hora_desenvolvimento     = trim(pg_result($reshd,$h,hora_desenvolvimento));
						$at_tipo_chamado_descricao   = trim(pg_result($reshd,$h,tipo_chamado_descricao));
						$at_prazo_horas              = pg_result($reshd,$h,prazo_horas);
						$at_prioridade               = pg_result($reshd,$h,prioridade);
						$at_prioridade_supervisor    = pg_result($reshd,$h,prioridade_supervisor);
						$at_xatendente               = pg_result($reshd,$h,'nome_atendente');
						$at_nome_atendente_responsavel  = pg_result($reshd,$h,'nome_atendente_responsavel');

						$wsql ="SELECT DATE_TRUNC('min',
							SUM(
								AGE(
							   CASE
							   WHEN data_termino IS NULL THEN CURRENT_TIMESTAMP
							   ELSE data_termino
							   END , data_inicio)))
						  FROM tbl_hd_chamado_atendente
						  JOIN tbl_admin
						 USING (admin)
						 WHERE hd_chamado = $at_hd_chamado
						   AND grupo_admin in (2,4)";

						$wres = pg_exec($con, $wsql);
						if(pg_num_rows($wres)>0)
						$horas= pg_result ($wres,0,0);
			/*
						if(strlen($horas)>0){
						$xhoras = explode(":", $horas);
						$horas = $xhoras[0].":".$xhoras[1];
						}
			 */
						//$horas = substr($horas, 0, -3);
						$horas = preg_replace('/(\d{2}):(\d{2}):00/', '$1h $2\'', $horas);
						$horas = str_replace(array('day', 'week',   'mon ', 'months', 'year'),
											 array('dia', 'semana', 'mês ', 'meses',  'ano'), $horas);

			
						if ($at_status == 'Aprovação' OR $at_status == 'Resolvido' OR $at_status == 'Cancelado'){
							$atrasou = 0;
						}

						if ($atrasou == 0 AND $chamados_atrasados==1){
							//break;
						}

						$cor2='#F2F7FF';
						if ($i % 2 == 0) $cor2 = '#FFFFFF';

						if ($at_atrasou_interno == '1'){
							$cor2='#F8FBB3';
						}

						if ($at_prioridade == 't'){
							$cor2='#FFD5CC';
						}

						if (strlen($at_previsao) > 0) {
							$sqlp = "SELECT ('$at_previsao' - current_timestamp) > interval'1 day';";
							$resp = pg_exec($con, $sqlp);
							if (pg_result($resp, 0, 0) == 'f') {
								$cor2='#FF9966';
							}
						}

						if ($at_atrasou == '1'){
							$chamados_atrasados = 1;
							$cor2='#FF3333';
						}
						echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor2'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor2'\"  nowrap>";

						echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

						echo "<td nowrap align='center'>";
						if($at_status =="Análise" AND $at_exigir_resposta <> "t"){
							echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
						}elseif($at_exigir_resposta == "t" AND $at_status<>'Cancelado' AND $at_status <> "Resolvido" ) {
							echo "<img src='../admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
						}elseif (($at_status == "Resolvido" ) OR $at_status == "Cancelado") {
							echo "<img src='../admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
						}elseif ($at_status == "Aprovação") {
							echo "<img src='../admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
						}elseif ($at_status == "Orçamento") {
							echo "<img src='../admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
						}
						else{
							echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
						}
						echo "$at_hd_chamado</td>";

						echo "<td nowrap style='width:250px'>";

						echo "<a href='adm_chamado_detalhe.php?hd_chamado=$at_hd_chamado' title='$at_titulo'>$at_interno ";
						echo "<span class='ellip'>$at_titulo</span></a></td>";

						echo "<td align='center' >$at_prioridade_supervisor</td>";

						if (($at_status != 'Resolvido') and ($at_status != 'Cancelado')) {
							$at_cor_status="#000000";
							if($at_status=="Novo" or $at_status =="Análise")$at_cor_status="#FF0000";
							if($at_status=="Execução")$at_cor_status="#0000FF";
							if($at_status=="Aguard.Execução")$at_cor_status="#339900";
							if($at_status=="Aguard.Verifica")$at_cor_status="#ffff00";
							echo "<td align='left' ><font color='$at_cor_status' size='1'><B>$at_status </B></font></td>";
						}else{
							echo "<td align='left'>$at_status <b>$at_hora_desenvolvimento</b></td>";
						}
						$at_imagem_erro="";

						if($at_tipo_chamado_descricao=="Erro em programa"){$at_imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
						echo "<td width='130'  align='left'>$at_imagem_erro <font size='1'><strong> $at_tipo_chamado_descricao</strong></font></td>";

						echo "<td width='80' align='center'><font size='1' >$at_data</font></td>";
						echo "<td align='left'><span class='ellip'>";
						if (strlen ($at_nome_completo) > 0) {
							$at_nome_completo2 = explode (' ',$at_nome_completo);
							$at_nome_completo = $at_nome_completo2[0];
							echo $at_nome_completo;

						}else{
							echo $at_login;
						}
						echo "</span></td>";
						echo "<td align='left' width='80'><font size='1'>$at_fabrica_nome</font></td>";
						echo "<td align='left' width='80'><font size='1'>$at_nome_atendente_responsavel</font></td>";

						echo "<td align='left'><span class='ellip'>$at_xatendente</span></td>";
						echo "<td align='center'><font size='1'>$horas</font></td>";
						echo "<td align='center'><font size='1'>$at_prazo_horas</font></td>";
						echo "<td align='center'><font size='1'>$at_hora_desenvolvimento</font></td>";
						echo "<td width='64' align='center'><font size='1'>$at_previsao_termino</font></td>";
						echo "<td width='64' align='center'><font size='1'>$at_previsao_termino_interna</font></td>";

						echo "</a></tr>";
						$quantidade_chamados++;
					}
				}
			}

			if($ordenar_por != "1"){

				echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";

				 echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

				echo "<td nowrap align='center'>";
				if($status =="Análise" AND $exigir_resposta <> "t"){
					echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
				}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
					echo "<img src='../admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
				}elseif(strlen($requisito_id) > 0 AND $status == "Requisitos"){
					echo "<img src='../admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
				}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
					echo "<img src='../admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
				}elseif ($status == "Aprovação") {
					echo "<img src='../admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
				}elseif ($status == "Orçamento") {
					echo "<img src='../admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
				}
				else{
					echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
				}
				echo "$hd_chamado</td>";

				echo "<td nowrap style='width:250px'>";

				echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado' title='$titulo'>$interno ";
				echo "<span class='ellip'>$titulo</span></a></td>";

				if ($tipo_chamado_descricao == "Erro em programa" && $prioridade == 1) {
					echo "<td align='center' >{$prioridade}</td>";
				} elseif ($tipo_chamado_descricao == "Erro em programa" && $prioridade == 2) {
					echo "<td align='center' >{$prioridade}</td>";
				} else {
							echo "<td align='center' >$prioridade_supervisor</td>";
				}
				if (($status != 'Resolvido') and ($status != 'Cancelado')) {
					$cor_status="#000000";
					if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
					if($status=="Execução")$cor_status="#0000FF";
					if($status=="Aguard.Execução")$cor_status="#339900";
					if($status=="Aguard.Verifica")$cor_status="#ffff00";
					echo "<td align='left' ><font color='$cor_status' size='1'><B>$status </B></font></td>";
				}else{
					echo "<td align='left'>$status <b>$hora_desenvolvimento</b></td>";
				}
				$imagem_erro="";

				if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
				echo "<td width='130'  align='left'>$imagem_erro <font size='1'><strong> $tipo_chamado_descricao</strong></font></td>";

				echo "<td width='80' align='center'><font size='1' >$data</font></td>";
				echo "<td align='left'><span class='ellip'>";
				if (strlen ($nome_completo) > 0) {
					$nome_completo2 = explode (' ',$nome_completo);
					$nome_completo = $nome_completo2[0];
					echo $nome_completo;

				}else{
					echo $login;
				}
				echo "</span></td>";
				echo "<td align='left' width='80'><font size='1'>$fabrica_nome</font></td>";
				echo "<td align='left' width='80'><font size='1'>$nome_atendente_responsavel</font></td>";

				echo "<td align='left'><span class='ellip'>$xatendente</span></td>";
				echo "<td align='center'><font size='1'>$horas</font></td>";
				echo "<td align='center'><font size='1'>$prazo_horas</font></td>";
				echo "<td align='center'><font size='1'>$hora_desenvolvimento</font></td>";
				echo "<td width='64' align='center'><font size='1'>$previsao_termino</font></td>";
				echo "<td width='64' align='center'><font size='1'>$previsao_termino_interna</font></td>";

				echo "</a></tr>";

			}

			$interno='';
			
			$at_ant = $atendente;
		}
		if($quantidade_chamados != 0){

			echo "<TR  >";

				if($ordenar_por==1){
					echo "<td class='totalHDs' colspan='2'>$xatendente</td>";
				}else if($ordenar_por==2){
					echo "<td class='totalHDs' colspan='2'>$fabrica_nome</td>";
				}else if($ordenar_por==3){
					echo "<td class='totalHDs' colspan='2'>$tipo_chamado_descricao</td>";
				}else if($ordenar_por==4){
					echo "<td class='totalHDs' colspan='2'>$celula</td>";
				}else if($ordenar_por==6){
					echo "<td class='totalHDs' colspan='2'>$nome_atendente_responsavel</td>";
				}


			echo "<td class='totalHDs' colspan='11'>Total HD: $quantidade_chamados</td>";

			echo "</tr>";
			$quantidade_chamados = 0;
		}

		echo "</tbody>";

	### PÉ PAGINACAO###

		if ($chamados_atrasados == 1){
			echo "<tr><td style='border-bottom:0px; text-align:center;' colspan='13'><h3>Chamados atrasados! Concluir com URGÊNCIA.</h3></td></tr>";
		}



		echo "<tr>";
		echo "<td colspan='10' align='center'>";
			// ##### PAGINACAO ##### //

		// links da paginacao
		echo "<br>";

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
		}
		// ##### PAGINACAO ##### //

		}

		echo "</td>";
		echo "</tr>";
echo "</table>";
	}
include "rodape.php"
?>
