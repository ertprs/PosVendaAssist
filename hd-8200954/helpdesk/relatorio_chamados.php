<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once '../admin/funcoes.php';

if($login_fabrica<>10) 
	header ("Location: index.php");

$TITULO = "Relatório de Chamados";

if(!empty($_POST)){
/*
Array
(
    $texto_chamado 		= trim($_POST['valor_chamado']);
	$conteudo_chamado 	= trim($_POST['conteudo_chamado']);
	$autor_chamado 		= trim($_POST['autor_chamado']);
	$data_inicial 		= trim($_POST['data_inicial']);
	$data_final 		= trim($_POST['data_final']);
	$hd_agrupar_fabrica = trim($_POST['hd_agrupar_fabrica']);
	
	if(!empty($_POST['tipos_chamados']))
		$tipo_chamados 		= implode(',',$_POST['tipos_chamados']);
		
	if(!empty($_POST['status_chamados'])){
		$status_chamados = implode(',',array_map('pg_escape_string',$_POST['tipos_chamados']));
	}
	
	if(!empty($_POST['fabricas'])){
		$fabricas = implode(',',$_POST['fabricas']);
	}
	
	if(!empty($_POST['atendentes'])){
		$atendentes = implode(',',$_POST['atendentes']);
	}

)
*/
	echo '<pre>';
	print_r($_POST);
	echo '</pre>';
	exit;
}

include "menu.php";
?>
<meta http-equiv="refresh" content="300">

<link rel="stylesheet" href="js/blue/style.css" type="text/css" media="print, projection, screen" />
<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter-2.0.min.js"></script>
<link rel="stylesheet" type="text/css" href="../js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="../js/datePicker.v1.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput.js"></script>

<script>
$(document).ready(function() {

	$("table.relatorio").tablesorter();
	
	$(".relatorio tr").mouseover(function(){
		$(this).addClass("over");
	}).mouseout(function(){
		$(this).removeClass("over");
	});
	
    $('.data').maskedinput("99/99/9999");
	$('.data').datePicker({startDate:'01/01/2000'});

});

function retiraLinha(linha){
	$('#'+linha).remove();
}

var filtrar = {
	chamados: function(filtro){
		
		if(filtro == 'todos'){
			$('.relatorio tr').show();
		}else{
			$('.relatorio tr').hide();
			$('.relatorio tr.header').show();
			
			$('.relatorio tr').each(function(){
				if($(this).hasClass(filtro)){
					$(this).show();
				}
			});
		}
	}
};

var adicionar = {
	tipo:function(){
		
		var selecionado = false;
		
		//Verifica se já inseriu aquele tipo
		$(".tipos_chamados").each(function(){
			if($(this).val() == $('#tipo_chamado').val()){
				selecionado = true;
			}
		});
		//Se não houver já selecionado, insere
		if(selecionado == false && $('#tipo_chamado').val() != ''){
			var div = '\
			<div class="selecionados" id="tipos'+$('#tipo_chamado').val()+'">\
				'+$('#tipo_chamado :selected').text()+'\
				<input class="tipos_chamados" type="hidden" name="tipos_chamados[]" value="'+$('#tipo_chamado').val()+'">\
				<a href="javascript:void(0)" onclick="retiraLinha(\'tipos'+$('#tipo_chamado').val()+'\')" class="remover"> X </a>\
			</div>';
			$("#tipos").append(div);
		}
	},
	status:function(){
		var selecionado = false;
		
		//Verifica se já inseriu aquele tipo
		$(".status_chamados").each(function(){
			if($(this).val() == $('#status_chamado').val()){
				selecionado = true;
			}
		});
		//Se não houver já selecionado, insere
		if(selecionado == false && $('#status_chamado').val() != ''){
			var div = '\
			<div class="selecionados" id="status'+$('#status_chamado').attr('rel')+'">\
				'+$('#status_chamado :selected').text()+'\
				<input class="status_chamados" type="hidden" name="status_chamados[]" value="'+$('#status_chamado').val()+'">\
				<a href="javascript:void(0)" onclick="retiraLinha(\'status'+$('#status_chamado').attr('rel')+'\')" class="remover"> X </a>\
			</div>';
			$("#status").append(div);
		}
	},
	atendente:function(){
		var selecionado = false;
		
		//Verifica se já inseriu aquele tipo
		$(".atendentes").each(function(){
			if($(this).val() == $('#atendente_chamado').val()){
				selecionado = true;
			}
		});
		//Se não houver já selecionado, insere
		if(selecionado == false && $('#atendente_chamado').val() != ''){
			var div = '\
			<div class="selecionados" id="atendente'+$('#atendente_chamado').val()+'">\
				'+$('#atendente_chamado :selected').text()+'\
				<input class="atendentes" type="hidden" name="atendentes[]" value="'+$('#atendente_chamado').val()+'">\
				<a href="javascript:void(0)" onclick="retiraLinha(\'atendente'+$('#atendente_chamado').val()+'\')" class="remover"> X </a>\
			</div>';
			$("#atendentes").append(div);
		}
	},
	fabrica:function(){
		var selecionado = false;
		
		//Verifica se já inseriu aquele tipo
		$(".fabricas").each(function(){
			if($(this).val() == $('#fabrica_chamado').val()){
				selecionado = true;
			}
		});
		//Se não houver já selecionado, insere
		if(selecionado == false && $('#fabrica_chamado').val() != ''){
			var div = '\
			<div class="selecionados" id="atendente'+$('#fabrica_chamado').val()+'">\
				'+$('#fabrica_chamado :selected').text()+'\
				<input class="fabricas" type="hidden" name="fabricas[]" value="'+$('#fabrica_chamado').val()+'">\
				<a href="javascript:void(0)" onclick="retiraLinha(\'atendente'+$('#fabrica_chamado').val()+'\')" class="remover"> X </a>\
			</div>';
			$("#fabricas").append(div);
		}
	}
};

</script>
<style>
	.link_log{
		font-family: Verdana;
		font-size: 11px;
		color: #333399;
	}
	
	.selecionados{
		padding:5px 15px 5px 2px;
		border:1px solid #00c;
		background-color:#efefef;
		width:90%;
		margin:2px;
	}
	
	table.formularioTabela{
		font-family:Verdana;
		font-size:11px;
		color: #666666;
	}
	
	table.formularioTabela td{
		text-align:left;
	}
	
	a.remover{
		width:15px;
		height:15px;
		padding:5px;
		color:#b33;
		text-decoration:none;
	}
	
	a.remover:hover{
		color:#33b;
	}
	
	a.adicionar{
		width:15px;
		height:15px;
		padding:5px;
		color:#00c;
		text-decoration:none;
	}
	
	a.adicionar:hover{
		color:#cc0;
	}

	table.relatorio {
		border-collapse: collapse;
		width: 650px;
		font-size: 1.1em;
		font-family: Verdana;
		font-size: 10px;
	}

	table.relatorio thead {background-color:#d9e8ff}
		
	table.relatorio th.header {
		background-image: url("imagens/bg.gif");
		background-position: right center;
		background-repeat: no-repeat;
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
		cursor: pointer;
		padding-right:20px!important;
	}
	
	table.relatorio th.headerSortDown {
		background: #DBEDFF;
		background-image: url("imagens/desc.gif");
		background-position: right center;
		background-repeat: no-repeat;
		font-size: 11px;
		color:#3e83c9;
	}
	
	table.relatorio th.headerSortUp {
		background: #DBEDFF;
		background-image: url("imagens/asc.gif");
		background-position: right center;
		background-repeat: no-repeat;
		font-size: 11px;
		color:#3e83c9;
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
	.filtros a{
		font-size:16px;
	}

</style>
<?
$status_busca    = trim($_POST['status']);
$atendente_busca = trim($_POST['atendente_busca']);
$autor_busca     = trim($_POST['autor_busca']);
$valor_chamado   = trim($_POST['valor_chamado']);
$fabrica_busca   = trim($_POST['fabrica_busca']);
$sem_prazo       = trim($_POST['sem_prazo']);
$hd_erro         = trim($_POST['hd_erro']);
$hd_producao     = trim($_POST['hd_producao']);
$hd_agrupar_fabrica = trim($_POST['hd_agrupar_fabrica']);
$data_inicio     = trim($_POST['data_inicio']);
$data_fim        = trim($_POST['data_fim']);
$tipo_chamado    = trim($_POST['tipo_chamado']);
$data_inicial	 = trim($_POST['data_inicial']);
$data_final	 	 = trim($_POST['data_final']);
$tipo_chamado	 = trim($_POST['tipo_chamado']);

if(strlen($tipo_chamado) == 0) {
	$tipo_chamado    = trim($_GET['tipo_chamado']);
}

if (strlen($status_busca)    == 0) $status_busca    = trim($_GET['status']);
if (strlen($atendente_busca) == 0) $atendente_busca = trim($_GET['atendente_busca']);
if (strlen($autor_busca)     == 0) $autor_busca     = trim($_GET['autor_busca']);
if (strlen($valor_chamado)   == 0) $valor_chamado   = trim($_GET['valor_chamado']);
if (strlen($data_inicio)   	 == 0) $data_inicio     = trim($_GET['data_inicio']);
if (strlen($data_fim)   	 == 0) $data_fim        = trim($_GET['data_fim']);
if (strlen($fabrica_busca)   == 0) $fabrica_busca   = trim($_GET['fabrica_busca']);
if (strlen($sem_prazo)   	 == 0) $sem_prazo       = trim($_GET['sem_prazo']);
if (strlen($hd_erro)  		 == 0) $hd_erro       	= trim($_GET['hd_erro']);
if (strlen($hd_producao)  	 == 0) $hd_producao     = trim($_GET['hd_producao']);
if (strlen($hd_agrupar_fabrica) == 0) $hd_agrupar_fabrica = trim($_GET['hd_agrupar_fabrica']);
if (strlen($hd_agrupar_fabrica) == 0) $hd_agrupar_fabrica = trim($_GET['hd_agrupar_fabrica']);
if (strlen($data_inicial) 	 == 0) $data_inicial    = trim($_GET['data_inicial']);
if (strlen($data_final) 	 == 0) $data_final      = trim($_GET['data_final']);

###INICIO ESTATITICAS###
if($atendente_busca <> '') {
	$cond1 = " AND tbl_hd_chamado.atendente        = '$atendente_busca' ";
}

if($data_inicial <> '') {
	$cond_data .= " AND tbl_hd_chamado.data_aprovacao_fila >= '$xdata_inicial' ";
}

if($data_final <> '') {
	$cond_data .= " AND tbl_hd_chamado.data_aprovacao_fila <= '$xdata_final' ";
}

if($autor_busca <> '') {
	$cond3 = " AND tbl_hd_chamado.admin            = $autor_busca ";
}

if(strlen($valor_chamado)>0){
	if (is_numeric($valor_chamado)){
		$cond_0 = " AND tbl_hd_chamado.hd_chamado = '$valor_chamado' ";

	}else{
		$valor_chamado = strtoupper($valor_chamado);
		$cond_0 = " AND upper(tbl_hd_chamado.titulo) like '%$valor_chamado%' ";
	}
}

if ($tipo_chamado == '5') {
	$cond_tipo_chamado = " AND tbl_hd_chamado.tipo_chamado = 5 ";
}
if ($tipo_chamado == '0') {
	$cond_tipo_chamado = " AND tbl_hd_chamado.tipo_chamado <> 5 ";
}

###busca###
$sql = "SELECT
			hd_chamado,
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
		AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
		$cond_tipo_chamado
		$cond_0 ";

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

	if(strlen($hd_erro) > 0  and strlen($valor_chamado)==0 ){
		$sql .= " AND tbl_hd_chamado.tipo_chamado=5";
	}

	if(strlen($hd_producao) > 0  and strlen($valor_chamado)==0 ){
		$sql .= " AND tbl_hd_chamado.tipo_chamado<>5";
	}

	if (strlen($valor_chamado) == 0) {
		if (strlen($status_busca) > 0) {
			if ($status_busca == "Novo") {
				$sql .= " AND (tbl_hd_chamado.status='$status_busca' OR tbl_hd_chamado.status IS NULL)";
			}
			else {
				$sql .= " AND tbl_hd_chamado.status='$status_busca' ";
			}
		}
		else {
			$sql .= " AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido' ";
			if (strlen($atendente_busca) == 0) {
				$sql .= " AND (tbl_hd_chamado.status <> 'Aprovação' OR (tbl_hd_chamado.status = 'Aprovação' AND tbl_hd_chamado.atendente<>435))";
			}
			else {
			}
		}
	}
	else {
		$status_busca = "";
	}

	if(strlen($hd_agrupar_fabrica) > 0 ){
		$orderby_fabrica = " tbl_hd_chamado.fabrica ASC,";
	}

$sql .= " ORDER BY $orderby_fabrica tbl_hd_chamado.previsao_termino_interna ASC,tbl_hd_chamado.previsao_termino ASC,tbl_hd_chamado.data DESC";

//echo nl2br($sql);

$res = pg_exec ($con,$sql);

if (@pg_numrows($res) >= 0) {

/*================================TABELA DE ESCOLHA DE STATUS============================*/
	echo "<form method='post' ACTION='$PHP_SELF'>";

	echo "<table width='700' align='center' cellpadding='0' cellspacing='0' border='0' class='formularioTabela'>";
	echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' >";
		echo "	<td><b><CENTER>Pesquisa Chamados</CENTER></b></td>";
	echo "</tr>";
	echo "<tr align='left'  height ='70' valign='top'>";
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td>";

	echo "<table border='0'  cellpadding='2' cellspacing='3' width='700' align='center' style='font-family: verdana ; font-size:11px ; color: #00000'>";

	#Filtros
	echo "<tr >";
		echo "<td width='150'>Buscar Títulos</td>";
		echo "<td width='250' colspan='2'>";
		echo "<input type='text' size='50' maxlength='50' name='valor_chamado' value=''> ";
		echo "</td>";
	echo '</tr>';
	
	echo "<tr >";
		echo "<td width='150'>Buscar Conteúdo</td>";
		echo "<td width='250' colspan='2'>";
		echo "<input type='text' size='50' maxlength='50' name='conteudo_chamado' value=''> ";
		echo "</td>";
		
	echo '</tr>';
	
	echo "<tr >";
		echo "<td width='150'>Tipo Chamado</td>";
		echo "<td width='250'>";

		$sqlTipo = "SELECT tipo_chamado,descricao
					FROM tbl_tipo_chamado;";

		$resTipo = pg_exec ($con,$sqlTipo);

		if (pg_numrows($resTipo) > 0) {
			echo "<select class='frm' style='width:180px;' name='tipo_chamado' id='tipo_chamado'>\n";
			echo "<option value='' ";
			if (strlen ($tipo_chamado) == 0 ) echo " selected ";
			echo ">- TODOS -</option>\n";

			for ($x = 0 ; $x < pg_numrows($resTipo) ; $x++){
				$id_tipo_chamado 	= pg_result($resTipo,$x,'tipo_chamado');
				$descricao_tipo  	= pg_result($resTipo,$x,'descricao');

				echo "<option value='$id_tipo_chamado'";
				if ($tipo_chamado == $id_tipo_chamado ) echo " SELECTED ";
				echo "> $descricao_tipo</option>\n";
			}

			echo "</select>";
			echo '<a href="javascript:void(0)" onclick="adicionar.tipo()" class="adicionar"> + </a>';
		}
		echo "</td>";
		echo "<td>";
		echo '<div id="tipos">';
		
		echo '</div>';
		echo "</td>";
	echo '</tr>';
	
	
	echo "<tr >";
		echo "<td width='150'>Status</td>";
		echo "<td width='250'>";
		?>
		<select class='frm' style='width: 180px;' name='status' id="status_chamado">
			<option rel="" value=''>- TODOS -</option>
			<option rel="1" value='Novo'>Novo</option>
			<option rel="2" value='Requisitos'>Requisitos</option>
			<option rel="3" value='Pré-Análise'>Pré-Análise</option>
			<option rel="4" value='Análise'>Análise</option>
			<option rel="5" value='Orçamento'>Orçamento</option>
			<option rel="6" value='Aprovação'>Aprovação</option>
			<option rel="7" value='Aguard.Execução'>Aguard.Execução</option>
			<option rel="8" value='Agendado'>Agendado</option>
			<option rel="9" value='Efetivação'>Efetivação</option>
			<option rel="10" value='Execução'>Execução</option>
			<option rel="11" value='Teste'>Teste</option>
			<option rel="12" value='Correção'>Correção</option>
			<option rel="13" value='Validação'>Validação</option>
			<option rel="14" value='Resolvido'>Resolvido</option>
			<option rel="15" value='Aguard.Verifica'>Aguard.Verificação</option>
			<option rel="16" value='Aguard.Admin'>Aguard.Admin</option>
			<option rel="17" value='Cancelado'>Cancelado</option>
		</select>
		<?
		echo '<a href="javascript:void(0)" onclick="adicionar.status()" class="adicionar"> + </a>';
		echo "</td>";
		echo "<td>";
		echo '<div id="status">';
		
		echo '</div>';
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
	
		#Atendente
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
			echo "<select class='frm' style='width:180px;' name='atendente_chamado' id='atendente_chamado'>\n";
			echo "<option value='' ";
			if (strlen ($atendente_busca) == 0 ) echo " SELECTED ";
			echo ">- TODOS -</option>\n";

			for ($x = 0 ; $x < pg_numrows($resatendente) ; $x++){
				$n_admin = trim(pg_result($resatendente,$x,admin));
				$nome_atendente  = trim(pg_result($resatendente,$x,nome_completo));

				echo "<option value='$n_admin' $nome_atendente</option>\n";
			}

			echo "</select>";
			echo '<a href="javascript:void(0)" onclick="adicionar.atendente()" class="adicionar"> + </a>';
		}
		
		echo "</td>";
		echo '<td>';
		echo '<div id="atendentes">';
		
		echo '</div>';
		echo '</td>';
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

		echo "<select class='frm' style='width: 180px;' name='autor_chamado'>\n";
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

	echo "<tr>";
	echo "<td width='150'>Fábrica</td>";
	echo "<td width='250'>";
	$sqlfabrica =  "SELECT   fabrica,nome
					FROM     tbl_fabrica
					ORDER BY nome";
	$resfabrica = pg_exec ($con,$sqlfabrica);
	$n_fabricas = pg_numrows($res);

	echo "<select class='frm' style='width:180px;' name='fabrica_chamado' id='fabrica_chamado'>\n";
	echo "<option value=''>- TODOS -</option>\n";
	for ($x = 0 ; $x < pg_numrows($resfabrica) ; $x++){
		$fabrica   = trim(pg_result($resfabrica,$x,fabrica));
		$nome      = trim(pg_result($resfabrica,$x,nome));
		echo "<option value='$fabrica'>$nome</option>\n";
	}
	echo "</select>\n";
	echo '<a href="javascript:void(0)" onclick="adicionar.fabrica()" class="adicionar"> + </a>';
	echo "</td>";
	echo "<td>";
		echo '<div id="fabricas">';
		
		echo '</div>';
	echo "</td>";
	echo "</tr>";
	
	echo "<tr >";
		echo "<td width='150'>Data Inicial</td>";
		echo "<td width='250'>";
		echo '<input type="text" style="width:100px" class="data" name="data_inicial" id="data_inicial">';
		echo "</td>";
	echo '</tr>';
	
	echo "<tr >";
		echo "<td width='150'>Data Final</td>";
		echo "<td width='250'>";
		echo '<input type="text" style="width:100px" class="data" name="data_final" id="data_final">';
		echo "</td>";
	echo '</tr>';
	
	if ($_GET["hd_agrupar_fabrica"]) $checked = "checked"; else $checked = "false";
	echo "<tr>";
	echo "<td colspan='2' align='left'><INPUT $checked TYPE=\"checkbox\" name=\"hd_agrupar_fabrica\" value=\"hd_agrupar_fabrica\"> Agrupar por fábrica";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td colspan='3' style='text-align:center;' align='center'> <INPUT TYPE=\"submit\" value=\"Pesquisar\">";
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

	echo "</form>";

	##### LEGENDAS #####
	echo "<center>
			<font face='verdana' size='1'>
			<b style='border:1px solid #666666;background-color:#FFD5CC;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Prioridade</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "
			<b style='border:1px solid #666666;background-color:#FF3333;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Previsão Vencida</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "
			<b style='border:1px solid #666666;background-color:#FF9966;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Risco de Previsão</b><br>";

	echo "
			<b><font color='#FF0000'>* Analistas acompanhem as previsões de término dos seus chamados, este prazo é visualizado pelo fabricante.</font></b>
			</font>
		</center>
		<br>";


	/*--=============================== TABELA DE LEGENDA DE CORES ========================--*/
	echo "<table width='630' align='center' cellpadding='0' cellspacing='0' border='0' class='filtros'>";
	echo "<tr style='font-family: arial ; color: #666666' align='center'>";
	echo "<td align='left' class='status_vermelho'>";
	echo "<a href='javascript:void(0);' onclick='filtrar.chamados(\"vermelho\")'>";
	echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> Aguardando resposta do cliente";
	echo "</a>";
	echo "</td>";
	echo "</tr>";
	
	echo "<tr style='font-family: arial ; color: #666666' align='center'>";
	echo "<td nowrap align='left' class='status_azul'>";
	echo "<a href='javascript:void(0);' onclick='filtrar.chamados(\"azul\")'>";
	echo "<img src='/assist/admin/imagens_admin/status_azul.gif' valign='absmiddle'> Pendente Telecontrol";
	echo "</a>";
	echo "</td>";
	echo "</tr>";

	echo "<tr style='font-family: arial ; color: #666666' align='center'>";
	echo "<td nowrap align='left' class='status_amarelo'>";
	echo "<a href='javascript:void(0);' onclick='filtrar.chamados(\"amarelo\")'>";
	echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> Aguardando aprovação";
	echo "</a>";
	echo "</td>";
	echo "</tr>";
	
	echo "<tr style='font-family: arial ; color: #666666' align='center'>";
	echo "<td nowrap align='left' class='status_verde'>";
	echo "<a href='javascript:void(0);' onclick='filtrar.chamados(\"verde\")'>";
	echo "<img src='/assist/admin/imagens_admin/status_verde.gif' valign='absmiddle'> Resolvido";
	echo "</a>";
	echo "</td>";
	echo "</tr>";
	
	echo "<tr style='font-family: arial ; color: #666666' align='center'>";
	echo "<td nowrap align='left'>";
	echo "<a href='javascript:void(0);' onclick='filtrar.chamados(\"todos\")'>";
	echo "<img src='/assist/admin/imagens_admin/status_verde.gif' valign='absmiddle' style='visibility:hidden;'> Todos";
	echo "</a>";
	echo "</td>";
	echo "</tr>";
	
	echo "</table>";
	echo "<br>";

	/*--=============================== TABELA DE CHAMADOS ========================--*/
	echo "<table width='100%' align='center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";

	$colunas = 14;
	
	$cabecalho .= "<thead>";
	$cabecalho .= "<tr class='header'>";
	$cabecalho .= "	<th></th>";
	$cabecalho .= "	<th >Nº </th>";
	$cabecalho .= "	<th nowrap>Título<img src='/assist/imagens/pixel.gif' width='5'></th>";
	$cabecalho .= "	<th >Status</th>";
	$cabecalho .= "	<th >Tipo</th>";
	$cabecalho .= "	<th >Data</th>";
	$cabecalho .= "	<th nowrap>Autor </strong></th>";
	$cabecalho .= "	<th nowrap>Fábrica </strong></th>";
	$cabecalho .= "	<th nowrap>Atendente </strong></th>";
	$cabecalho .= "	<th nowrap title='Última interação Telecontrol'>Telecontrol</th>";
	$cabecalho .= "	<th nowrap title='Última interação Telecontrol'>Telec-Admin</th>";
	$cabecalho .= "	<th nowrap title='Última interação Admin'>Admin</th>";
	$cabecalho .= "	<th nowrap >Prazo</th>";
	$cabecalho .= "	<th nowrap>Previsão</th>";
	$cabecalho .= "</tr>";
	$cabecalho .= "</thead>";
	$cabecalho .= "<tbody>";

	echo $cabecalho;

	if (@pg_numrows($res) > 0) {

		//inicio imprime chamados
		$fabrica_anterior             = "";
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$hd_chamado               = pg_result($res,$i,hd_chamado);
			$admin                    = pg_result($res,$i,admin);
			$login                    = pg_result($res,$i,login);
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

			if ($fabrica_anterior != $fabrica_nome && strlen($hd_agrupar_fabrica) > 0 && $i > 0) {
				echo "<tr><td colspan=$colunas height=40>&nbsp;</td></tr>";
				echo $cabecalho;
			}

			$fabrica_anterior = $fabrica_nome;

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
			
			if($status =="Análise" AND $exigir_resposta <> "t"){
				$imagem = "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
				$classe='azul';
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
				$imagem =  "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
				$classe='vermelho';
			}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
				$imagem = "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
				$classe='verde';
			}elseif ($status == "Aprovação") {
				$imagem = "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
				$classe='amarelo';
			}else{
				$imagem = "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
				$classe='azul';
			}
			
			echo "<tr height='25' bgcolor='$cor' class='$classe'>";
			echo "<td nowrap >";
				echo $imagem;
			echo "</td>";
			echo "<td nowrap >";
				echo $hd_chamado;
			echo "</td>";

			echo "<td nowrap  width='150'>";
			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			echo "<acronym title='$titulo'>$interno ";
			echo substr($titulo,0,20)."...</acronym></a></td>";

			if (($status != 'Resolvido') and ($status != 'Cancelado')) {
				$cor_status="#000000";
				if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
				if($status=="Execução")$cor_status="#0000FF";
				if($status=="Aguard.Execução")$cor_status="#339900";
				if($status=="Aguard.Verifica")$cor_status="#ffff00";
				echo "<td nowrap><font color='$cor_status' size='1'><B>$status </B></font><b>$hora_desenvolvimento</b></td>";
			}else{
				echo "<td nowrap>$status <b>$hora_desenvolvimento</b></td>";
			}
			$imagem_erro="";

			if($tipo_chamado_descricao=="Erro em programa"){
				$imagem_erro="<img src='imagem/ico_erro.png' border='0'>";
			}
			
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

			$sql = "
			SELECT
			TO_CHAR(MAX(tbl_hd_chamado_item.data), 'DD/MM/YY')
			
			FROM
			tbl_hd_chamado
			JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_item.hd_chamado
			JOIN tbl_admin ON tbl_hd_chamado_item.admin=tbl_admin.admin AND tbl_admin.fabrica=tbl_hd_chamado.fabrica_responsavel
			
			WHERE
			tbl_hd_chamado.hd_chamado = $hd_chamado
			";
			$res_interacao = pg_query($con, $sql);
			$interacao_telecontrol = pg_result($res_interacao, 0, 0);

			$sql = "
			SELECT
			TO_CHAR(MAX(tbl_hd_chamado_item.data), 'DD/MM/YY'),
			MAX(tbl_hd_chamado_item.hd_chamado_item)
			
			FROM
			tbl_hd_chamado
			JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_item.hd_chamado
			JOIN tbl_admin ON tbl_hd_chamado_item.admin=tbl_admin.admin AND tbl_admin.fabrica=tbl_hd_chamado.fabrica_responsavel
			
			WHERE
			tbl_hd_chamado.hd_chamado = $hd_chamado
			AND tbl_hd_chamado_item.interno IS NOT TRUE
			";
			$res_interacao = pg_query($con, $sql);
			$interacao_telecontrol_admin = pg_result($res_interacao, 0, 0);
			$item_telecontrol = pg_result($res_interacao, 0, 1);

			$sql = "
			SELECT
			TO_CHAR(MAX(tbl_hd_chamado_item.data), 'DD/MM/YY'),
			MAX(tbl_hd_chamado_item.hd_chamado_item)
				
			FROM
			tbl_hd_chamado
			JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_item.hd_chamado
			JOIN tbl_admin ON tbl_hd_chamado_item.admin=tbl_admin.admin AND tbl_admin.fabrica=tbl_hd_chamado.fabrica
			
			WHERE
			tbl_hd_chamado.hd_chamado = $hd_chamado
			AND tbl_hd_chamado_item.data<>tbl_hd_chamado.data
			";
			$res_interacao = pg_query($con, $sql);
			$interacao_admin = pg_result($res_interacao, 0, 0);
			$item_admin = pg_result($res_interacao, 0, 1);

			if (intval($item_admin) > intval($item_telecontrol)) {
				$alerta = "style='background-color:#FF9955'";
			}
			else {
				$alerta = "";
			}

			echo "</font></td>";
			echo "<td nowrap width='80'><font size='1'>$fabrica_nome</font></td>";
			echo "<td nowrap><font size='1'>$xxatendente[0]</font></td>";
			echo "<td nowrap align='center'><font size='1'>$interacao_telecontrol</font></td>";
			echo "<td nowrap align='center'><font size='1'>$interacao_telecontrol_admin</font></td>";
			echo "<td nowrap align='center' $alerta><font size='1'>$interacao_admin</font></td>";
			echo "<td nowrap align='center'><font size='1'>$prazo_horas</font></td>";
			echo "<td width='60'><font size='1'>$previsao_termino</font></td>";

			echo "</tr>";
			$interno='';
		}
		//fim imprime chamados

	}
	echo "</tbody>";
	echo "</table>";

}

include "rodape.php" 
?>
