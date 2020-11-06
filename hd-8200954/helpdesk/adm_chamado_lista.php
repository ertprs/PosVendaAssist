<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once '../admin/funcoes.php';

$lista_novo_processo = '(1,2,3,5,6,7,8,10,11,14,15,19,20,24,30,35,40,42,43,45,46,47,50,51,52,59,61,66,72,75,76,77,78,80,81,85,86,87,88,89)';

if($login_fabrica<>10) header ("Location: index.php");


$status_busca       = trim($_POST['status']);
$atendente_busca    = trim($_POST['atendente_busca']);
$autor_busca        = trim($_POST['autor_busca']);
$valor_chamado      = trim($_POST['valor_chamado']);
$fabrica_busca      = trim($_POST['fabrica_busca']);
$sem_prazo          = trim($_POST['sem_prazo']);
$hd_erro            = trim($_POST['hd_erro']);
$hd_producao        = trim($_POST['hd_producao']);
$hd_agrupar_fabrica = trim($_POST['hd_agrupar_fabrica']);
$data_inicio        = trim($_POST['data_inicio']);
$data_fim           = trim($_POST['data_fim']);
$tipo_chamado       = trim($_POST['tipo_chamado']);
$data_pesquisa      = $_GET['data_pesquisa'];
$data_inicial       = $_GET['data_inicial'];
$data_final         = $_GET['data_final'];

if (strlen($tipo_chamado)       == 0) $tipo_chamado       = trim($_GET['tipo_chamado']);
if (strlen($status_busca)       == 0) $status_busca       = trim($_GET['status']);
if (strlen($atendente_busca)    == 0) $atendente_busca    = trim($_GET['atendente_busca']);
if (strlen($autor_busca)        == 0) $autor_busca        = trim($_GET['autor_busca']);
if (strlen($valor_chamado)      == 0) $valor_chamado      = trim($_GET['valor_chamado']);
if (strlen($data_inicio)        == 0) $data_inicio        = trim($_GET['data_inicio']);
if (strlen($data_fim)           == 0) $data_fim           = trim($_GET['data_fim']);
if (strlen($fabrica_busca)      == 0) $fabrica_busca      = trim($_GET['fabrica_busca']);
if (strlen($sem_prazo)          == 0) $sem_prazo          = trim($_GET['sem_prazo']);
if (strlen($hd_erro)            == 0) $hd_erro            = trim($_GET['hd_erro']);
if (strlen($hd_producao)        == 0) $hd_producao        = trim($_GET['hd_producao']);
if (strlen($hd_agrupar_fabrica) == 0) $hd_agrupar_fabrica = trim($_GET['hd_agrupar_fabrica']);



if (strlen($data_inicial) > 0) {
	list($did, $dim, $dia) = explode("/", $data_inicial);
	if(!checkdate($dim, $did, $dia)) {
		$msg_erro .= 'Data inicial inválida';
	} else {
		$aux_data_inicial = "{$dia}-{$dim}-{$did}";
	}
}
if (strlen($data_final) > 0) {
	list($dfd, $dfm, $dfa) = explode("/", $data_final);
	if(!checkdate($dfm, $dfd, $dfa)) {
		$msg_erro .= 'Data final inválida';
	} else {
		$aux_data_final = "{$dfa}-{$dfm}-{$dfd}";
	}
}


if ((strlen($msg_erro)== 0) AND (strlen($aux_data_inicial)>0) AND  (strlen($aux_data_final)>0)) {
	if (strtotime($aux_data_final) < strtotime($aux_data_inicial."- 6 MOUNTH" )) {
    	$msg_erro .= " Data inicial não pode ser maior que a data final. ";
    }
}

/**
 * Status que irão pesquisar a data em campos que são diferentes da data de abertura.
 *
 * Caso o valor do campo estiver vazio, irá buscar a data na última interação.
 */
$status_periodo = array("Resolvido"	=> "data_resolvido",
						"Aprovação"	=> "data_aprovacao");
$status_periodo = array_merge($status_periodo, array_fill_keys(array("Cancelado",
																	 "Análise",
																	 "Orçamento",
																	 "Efetivação",
																	 "Execução",
																	 "Teste",
																	 "Correção",
																	 "Validação",
																	 "Aguard.Verificação",
																	 "Aguard.Admin",
																	 "Aguard.Execução"), ""));



###busca###


$sqlpes = "SELECT
			tbl_hd_chamado.hd_chamado,
			tbl_hd_chamado.hd_chamado_anterior,
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
		";
if (($data_pesquisa == 'aprovacao')OR($data_pesquisa == 'finalizacao')){
	$sqlpes .=" JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
			";
}
	$sqlpes .=" WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			";

if(!empty($aux_data_inicial) && !count($msg_erro) && empty($data_pesquisa) ) {

	if(!is_array($status_periodo[$status])) {
		$sqlpes .= " AND   ( tbl_hd_chamado." . ((isset($status_periodo[$status]) && !empty($status_periodo[$status])) ? $status_periodo[$status] : "data") .
					" BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59')
					";
	} else {
		foreach ($status_periodo[$status] as $key => $value) {
			$sqlpes .= " AND tbl_hd_chamado.$key " . ($value === false ? "IS NOT NULL" : ($value === true ? "IS NULL" : "= $value
					")) . "\n";
		}
	}
}
$sqlpes .= " AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
			";

if (!count($msg_erro) && isset($status_periodo[$status]) && empty($status_periodo[$status]) && !empty($aux_data_inicial) && empty($status_busca)) {

$sqlpes .= " AND tbl_hd_chamado.hd_chamado IN (
					SELECT chamado.hd_chamado
					FROM (
					SELECT
					ultima.hd_chamado,
					(SELECT data
					FROM tbl_hd_chamado_item
					WHERE tbl_hd_chamado_item.hd_chamado = ultima.hd_chamado
					ORDER BY data DESC LIMIT 1) AS ultimo_interacao
					FROM (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado WHERE status='$status') ultima
					) chamado
					WHERE ( chamado.ultimo_interacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' )
				)";
}


if ($atendente_busca <> '' and strlen($valor_chamado)==0 ){
	$sqlpes .= " AND tbl_hd_chamado.atendente = $atendente_busca ";
}

if ($autor_busca <> '' and strlen($valor_chamado)==0 ){
	$sqlpes .= " AND tbl_hd_chamado.admin = $autor_busca ";
}

if($fabrica_busca <> '' and strlen($valor_chamado)==0 ){
	$sqlpes .= " AND tbl_fabrica.nome = '$fabrica_busca' ";
}

if(strlen($sem_prazo) > 0  and strlen($valor_chamado)==0 ){
	$sqlpes .= " AND tbl_hd_chamado.prazo_horas ISNULL ";
}

if(strlen($hd_erro) > 0  ){
	$sqlpes .= " AND tbl_hd_chamado.tipo_chamado=5 ";
}

if(strlen($hd_producao) > 0 ){
	$sqlpes .= " AND tbl_hd_chamado.tipo_chamado<>5";
}

if($fabrica_busca <> '' ){
    $sqlpes .= " AND tbl_fabrica.nome = '$fabrica_busca' ";
}
if (strlen($aux_data_inicial) > 0 ){
    if ($data_pesquisa == 'abertura'){
		$sqlpes .=" AND (tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59') ";
	}
}
if(strlen($data_pesquisa)>0){
	if ($data_pesquisa == 'aprovacao'){
		$sqlpes .=" AND (tbl_hd_chamado_item.comentario ilike('%ESTE CHAMADO FOI ABERTO EM%')
						AND tbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59')
					 ";
		}
	if ($data_pesquisa == 'finalizacao'){
		$sqlpes .="
					AND (tbl_hd_chamado_item.termino BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
							AND tbl_hd_chamado_item.status_item = 'Resolvido')
					";
	}
}

if (strlen($valor_chamado) == 0) {
	if (strlen($status_busca) > 0) {
		if ($status_busca == "Novo") {
			$sqlpes .= " AND (tbl_hd_chamado.status='Novo'
							OR tbl_hd_chamado.status IS NULL) ";
		}
		else {
			$sqlpes .= " AND (tbl_hd_chamado.status = '$status_busca') ";
		}
	}
	// else{
	// 	$sqlpes .= " AND tbl_hd_chamado.status <> 'Novo' " ;
	// }
	else {
		if (strlen($hd_erro) == 0 and strlen($hd_producao) == 0  ){
			$sqlpes .= " AND tbl_hd_chamado.status <> 'Cancelado'
							AND tbl_hd_chamado.status <> 'Resolvido' ";
		}
		if (strlen($atendente_busca) == 0) {
			$sqlpes .= " AND (tbl_hd_chamado.status <> 'Aprovação'
							OR (tbl_hd_chamado.status = 'Aprovação'
							AND tbl_hd_chamado.atendente <> 435)) ";
		}
	}
}
if ($atendente_busca <> '') {
	$sqlpes ." AND tbl_hd_chamado.atendente        = '$atendente_busca' ";
}
if ($autor_busca <> '') {
	$sqlpes .= " AND tbl_hd_chamado.admin            = $autor_busca ";
}
if (strlen($valor_chamado)>0){
	if (is_numeric($valor_chamado)){
		$sqlpes .= "AND tbl_hd_chamado.hd_chamado = '$valor_chamado' ";
	}else{
		$valor_chamado = strtoupper($valor_chamado);
		$sqlpes .= "AND upper(tbl_hd_chamado.titulo) like '%$valor_chamado%' ";
	}
}
if ($tipo_chamado == '5') {
	$sqlpes .= "AND tbl_hd_chamado.tipo_chamado = 5 ";
}
if ($tipo_chamado == '0') {
	$sqlpes .= "AND tbl_hd_chamado.tipo_chamado <> 5 ";
}

if($grupo_admin <> 11){
	$sqlpes .= " AND tbl_hd_chamado.status NOT IN('Parado')";
}

$sqlpes .= " ORDER BY ";
if(strlen($hd_agrupar_fabrica) > 0 ){
	$sqlpes .= " tbl_hd_chamado.fabrica ASC, ";
}
$sqlpes .= " 	  tbl_hd_chamado.previsao_termino_interna ASC,
		  	  tbl_hd_chamado.previsao_termino ASC,
		  	  tbl_hd_chamado.data DESC ";
  //echo nl2br($sqlpes);//exit;

$resPES = pg_query($con,$sqlpes);

$TITULO = "Lista de Chamados";

include "menu.php";
?>
<script>
$(document).ready(function() {

	function changeData(obj) {
		var data_inicial = $("#data_inicial").val().split("/");
		var data_final   = $("#data_final").val().split("/");

		data_inicial = new Date(data_inicial[2], data_inicial[1]-1, data_inicial[0]);
		data_final   = new Date(data_final[2], data_final[1]-1, data_final[0]);

		if(data_inicial > data_final) {
			// Se alterou a data inicial
			if(obj.attr('id') == "data_inicial") {
				// Altera a data final e iguala
				$("#data_final").val($("#data_inicial").val());
			} else {
				$("#data_inicial").val($("#data_final").val());
			}
		}
	}

	$('#data_inicial').datepick({startDate:'01/01/2000',
		onSelect: function() {
			changeData($("#data_inicial"));
	}});

	$('#data_final').datepick({startDate:'01/01/2000',
		onSelect: function() {
			changeData($("#data_final"));
	}});

	$("#data_inicial").mask("99/99/9999");
	$("#data_final").mask("99/99/9999");

	$("#data_inicial, #data_final").on("keyup", function() {

		if($(this).val().length == 10) {
			changeData($(this));
		}
	});
});

</script>
<style>
	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
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


<?php

###INICIO ESTATITICAS###


$sqllog ="SELECT *
		FROM	tbl_change_log
		LEFT jOIN tbl_fabrica ON tbl_fabrica.fabrica=tbl_change_log.fabrica
		LEFT join tbl_change_log_admin On tbl_change_log.change_log=tbl_change_log_admin.change_log AND tbl_change_log_admin.admin = $login_admin
		WHERE tbl_change_log_admin.data IS NULL
		AND   tbl_change_log.admin <> $login_admin";

$reslog =pg_query($con,$sqllog);

if(pg_num_rows($reslog) >0) {
	echo "<br>";
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr valign='middle' align='center'><td class='change_log'><a href='change_log_mostra.php' target='_blank' class='link_log'>Existem CHANGE LOG para ser lido. Clique aqui para visualizar</a></td></tr>
	</table><BR>";
}




if (pg_num_rows($resPES) >= 0) {

/*================================TABELA DE ESCOLHA DE STATUS============================*/


	echo "<FORM METHOD='GET' ACTION='$PHP_SELF'>";

	//hd 17668
	echo "<INPUT TYPE=\"hidden\" name=\"tipo_chamado\" value=\"$tipo_chamado\">";

	echo "<table width = '450' align = 'center' cellpadding='0' cellspacing='0' border='0' style='font-family: verdana ; font-size:11px ; color: #666666'>";
	if(count($msg_erro)) {
		foreach($msg_erro as $erro) {
			echo "<tr class='msg_erro'><td colspan='2'>$erro</td></tr>";
		}
	}
	echo "<tr>";
	echo "	<td background='./imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='imagens/pixel.gif' width='9'></td>";
	echo "	<td background='./imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
	echo "	<td background='./imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' >";
	echo "	<td><b><CENTER>Pesquisa Chamados</CENTER></b></td>";
	echo "</tr>";
	echo "<tr align='left'  height ='70' valign='top'>";
	echo "	<td background='./imagem/fundo_tabela_centro_esquerdo.gif' ><img src='imagens/pixel.gif' width='9'></td>";
	echo "<td>";

	echo "<table border='0'  cellpadding='2' cellspacing='3' width='400' align='center' style='font-family: verdana ; font-size:11px ; color: #00000'>";

	echo "<tr >";
	echo "<td width='150'>Buscar</td>";
	echo "<td width='250'>";
	echo "<input type='text' size='26' maxlength='26' name='valor_chamado' value=''> ";
	echo "</td>";
	echo "</tr>";
	echo "	<tr>
				<td>
					Data inicial
				</td>
				<td>
					<input id='data_inicial' name='data_inicial' type='text' size='7' value='$data_inicial'/>
				</td>
			</tr>
			<tr>
				<td>
					Data final
				</td>
				<td>
					<input id='data_final' name='data_final' type='text' size='7' value='$data_final'/>
				</td>
			</tr>";

	echo "<td width='150'>Status</td>";
	echo "<td width='250'>";
	echo "<select class='frm' style='width: 180px;' name='status'>\n";
	?>
	<option value=''></option>
	<?/*<option value='Novo'      <? if($status_busca=='Novo')      echo ' SELECTED '?> >Novo</option>
	<option value='Requisitos'   <? if($status_busca=='Requisitos')   echo ' SELECTED '?> >Requisitos</option>
	<option value='Pré-Análise'   <? if($status_busca=='Pré-Análise')   echo ' SELECTED '?> >Pré-Análise</option>
	<option value='Análise'   <? if($status_busca=='Análise')   echo ' SELECTED '?> >Análise</option>
	<option value='Orçamento'   <? if($status_busca=='Orçamento')   echo ' SELECTED '?> >Orçamento</option>
	<option value='Aprovação' <? if($status_busca=='Aprovação') echo ' SELECTED '?> >Aprovação</option>
	<option value='Aguard.Execução'  <? if($status_busca=='Aguard.Execução')  echo ' SELECTED '?> >Aguard.Execução</option>
	<option value='Agendado'  <? if($status_busca=='Agendado')  echo ' SELECTED '?> >Agendado</option>
	<option value='Efetivação'  <? if($status_busca=='Efetivação')  echo ' SELECTED '?> >Efetivação</option>
	<option value='Execução'  <? if($status_busca=='Execução')  echo ' SELECTED '?> >Execução</option>
	<option value='Teste'  <? if($status_busca=='Teste')  echo ' SELECTED '?> >Teste</option>
	<option value='Correção'  <? if($status_busca=='Correção')  echo ' SELECTED '?> >Correção</option>
	<option value='Validação'  <? if($status_busca=='Validação')  echo ' SELECTED '?> >Validação</option>
	<option value='Resolvido' <? if($status_busca=='Resolvido') echo ' SELECTED '?> >Resolvido</option>
	<option value='Aguard.Verifica' <? if($status_busca=='Aguard.Verifica') echo ' SELECTED '?> >Aguard.Verificação</option>
	<option value='Aguard.Admin'  <? if($status_busca=='Aguard.Admin')  echo ' SELECTED '?> >Aguard.Admin</option>
	<option value='Cancelado' <? if($status_busca=='Cancelado') echo ' SELECTED '?> >Cancelado</option>*/?>

	<option value='Novo'             <? if($status_busca=='Novo')            echo ' SELECTED '?> >Novo</option>
	<option value='Requisitos'       <? if($status_busca=='Requisitos')      echo ' SELECTED '?> >Requisitos</option>
	<option value='Orçamento'        <? if($status_busca=='Orçamento')       echo ' SELECTED '?> >Orçamento</option>
	<option value='Análise'          <? if($status_busca=='Análise')         echo ' SELECTED '?> >Análise</option>
	<option value='Aguard.Execução'  <? if($status_busca=='Aguard.Execução') echo ' SELECTED '?> >Aguard.Execução</option>
	<option value='Execução'         <? if($status_busca=='Execução')        echo ' SELECTED '?> >Execução</option>
	<option value='Validação'        <? if($status_busca=='Validação')       echo ' SELECTED '?> >Validação</option>
	<option value='EfetivaçãoHomologação'       <? if($status_busca=='EfetivaçãoHomologação')      echo ' SELECTED '?> >Efetivação Homologação</option>
	<option value='ValidaçãoHomologação'        <? if($status_busca=='ValidaçãoHomologação')       echo ' SELECTED '?> >Validação Homologação</option>
	<option value='Efetivação'       <? if($status_busca=='Efetivação')      echo ' SELECTED '?> >Efetivação</option>
	<option value='Correção'         <? if($status_busca=='Correção')        echo ' SELECTED '?> >Correção</option>
	<option value='Parado'           <? if($status_busca=='Parado' )         echo ' SELECTED '?> >Parado</option>
	<option value='Impedimento'      <? if($status_busca=='Impedimento' )    echo ' SELECTED '?> >Impedimento</option>
	<option value='Suspenso'         <? if($status_busca=='Suspenso' )       echo ' SELECTED '?> >Suspenso </option>
	<option value='Aguard.Admin'     <? if($status_busca=='Aguard.Admin')    echo ' SELECTED '?> >Aguard.Admin</option>
	<option value='Resolvido'        <? if($status_busca=='Resolvido')       echo ' SELECTED '?> >Resolvido</option>
	<option value='Cancelado'        <? if($status_busca=='Cancelado')       echo ' SELECTED '?> >Cancelado</option>
	<?
	echo "</td>";
	echo "</tr>";
	echo "<td width='150'>Procura por </td>";
	echo "<td width='250'>";
	echo "<select class='frm' style='width: 180px;' name='data_pesquisa' id='data_pesquisa' >\n";
	?>
	<option value='abertura' <?php if($data_pesquisa=='abertura')  echo ' SELECTED ' ?> >Data de Abertura</option>
	<option value='aprovacao' <?php if($data_pesquisa=='aprovacao')  echo ' SELECTED ' ?> >Data de Aprovação</option>
	<option value='finalizacao' <?php if($data_pesquisa=='finalizacao')  echo ' SELECTED ' ?> >Data de Finalização</option>
	<?
	echo "</td>";
	echo "</tr>";
	echo "<tr >";
	echo "<td width='150'>Atendente</td>";
	echo "<td width='250'>";

	$sqlatendente = "SELECT nome_completo,
							admin
					   FROM    tbl_admin
                      WHERE tbl_admin.fabrica =  10
					    AND ativo             IS TRUE
					    AND grupo_admin       IS NOT NULL
					    AND nome_completo	  IS NOT NULL
					  ORDER BY tbl_admin.nome_completo;";

	$resatendente = pg_query($con,$sqlatendente);

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



	echo "<tr >";
	echo "<td width='150'>Fábrica</td>";
	echo "<td width='250'>";
	$sqlfabrica =  "SELECT   *
					FROM     tbl_fabrica
					WHERE ativo_fabrica IS TRUE
					ORDER BY nome";
	$resfabrica = pg_query($con,$sqlfabrica);
	$n_fabricas = pg_num_rows($resfabrica);


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

	if ($_GET["sem_prazo"]) $checked = "checked"; else $checked = "false";
	echo "<tr>";
	echo "<td colspan='2' align='left'><INPUT $checked TYPE=\"checkbox\" name=\"sem_prazo\" value=\"sem_prazo\"> Chamados sem prazo";
	echo "</td>";
	echo "</tr>";

	if ($_GET["hd_erro"]) $checked = "checked"; else $checked = "false";
	echo "<tr>";
	echo "<td colspan='2' align='left'><INPUT $checked TYPE=\"checkbox\" name=\"hd_erro\" value=\"hd_erro\"> Chamados de erro";
	echo "</td>";
	echo "</tr>";

	if ($_GET["hd_producao"]) $checked = "checked"; else $checked = "false";
	echo "<tr>";
	echo "<td colspan='2' align='left'><INPUT $checked TYPE=\"checkbox\" name=\"hd_producao\" value=\"hd_producao\"> Chamados de produção";
	echo "</td>";
	echo "</tr>";

	if ($_GET["hd_agrupar_fabrica"]) $checked = "checked"; else $checked = "false";
	echo "<tr>";
	echo "<td colspan='2' align='left'><INPUT $checked TYPE=\"checkbox\" name=\"hd_agrupar_fabrica\" value=\"hd_agrupar_fabrica\"> Agrupar por fábrica";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td colspan='2' align='center'> <INPUT TYPE=\"submit\" value=\"Pesquisar\">";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "</td>";
	echo "<td background='./imagem/fundo_tabela_centro_direito.gif' ><img src='imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td background='./imagem/fundo_tabela_baixo_esquerdo.gif'><img src='imagens/pixel.gif' width='9'></td>";
	echo "	<td background='./imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
	echo "	<td background='./imagem/fundo_tabela_baixo_direito.gif'><img src='imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";




#===========================


	echo "</table>";
	echo "</FORM>";

	echo "<div" . (count($msg_erro) ? " style='display: none'" : "") . ">";

	##### LEGENDAS #####
	echo "<CENTER><font face='verdana' size='1'><b style='border:1px solid #666666;background-color:#FFD5CC;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Prioridade</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666; background-color:#B8B8B8;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Reincidentes</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF3333;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Previsão Vencida</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
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
	echo "<img src='../admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> Aguardando aprovação";
	echo "</td>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='../admin/imagens_admin/status_verde.gif' valign='absmiddle'> Resolvido";
	echo "</td>";


	echo "</tr>";

	echo "</table>";
	echo "<br>";


	/*--=============================== TABELA DE CHAMADOS ========================--*/

	echo "<table width = '100%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";

	$colunas = 13;

	$cabecalho = "<thead>";
	$cabecalho .= "<tr>";
	$cabecalho .= "	<th >Nº </th>";
	$cabecalho .= "	<th nowrap>Título<img src='imagens/pixel.gif' width='5'></th>";
	$cabecalho .= "	<th >Status</th>";
	$cabecalho .= "	<th >Tipo</th>";
	$cabecalho .= "	<th >Data</th>";
	$cabecalho .= "	<th nowrap>Autor </strong></th>";
	$cabecalho .= "	<th nowrap>Fábrica </strong></td>";
	$cabecalho .= "	<th nowrap>Atendente </strong></th>";
	$cabecalho .= "	<th nowrap title='Última interação Telecontrol'>Telecontrol</td>";
	$cabecalho .= "	<th nowrap title='Última interação Telecontrol'>Telec-Admin</td>";
	$cabecalho .= "	<th nowrap title='Última interação Admin'>Admin</td>";
//	$cabecalho .= "	<th nowrap title='Horas Trabalhadas'>Trab.</th>";
	$cabecalho .= "	<th nowrap >Prazo</th>";
	$cabecalho .= "	<th nowrap>Previsão</td>";
	$cabecalho .= "</tr>";
	$cabecalho .= "</thead>";

	echo $cabecalho;

	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	//echo nl2br($sqlpes);
	$sqlCount .= $sqlpes;
	$sqlCount .= ") AS count";


	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 300;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página
	//echo nl2br($sqlpes);
	$resPES = $mult_pag->executar($sqlpes, $sqlCount, $con, "otimizada", "pgsql");
	// ##### PAGINACAO ##### //

	if (@pg_num_rows($resPES) > 0) {
	//inicio imprime chamados
		$fabrica_anterior             = "";
		for ($i = 0 ; $i < pg_num_rows ($resPES) ; $i++) {
			$hd_chamado               = pg_fetch_result($resPES,$i,'hd_chamado');
			$hd_chamado_anterior      = pg_fetch_result($resPES,$i,'hd_chamado_anterior');
			$admin                    = pg_fetch_result($resPES,$i,'admin');
			$login                    = pg_fetch_result($resPES,$i,'login');
	//		$posto                    = pg_fetch_result($resPES,$i,'posto');
			$data                     = pg_fetch_result($resPES,$i,'data');
			$titulo                   = pg_fetch_result($resPES,$i,'titulo');
			$status                   = pg_fetch_result($resPES,$i,'status');
			$atendente                = pg_fetch_result($resPES,$i,'atendente');
			$exigir_resposta          = pg_fetch_result($resPES,$i,'exigir_resposta');
			$nome_completo            = trim(pg_fetch_result($resPES,$i,'nome_completo'));
			$fabrica_nome             = trim(pg_fetch_result($resPES,$i,'fabrica_nome'));
			$previsao_termino         = trim(pg_fetch_result($resPES,$i,'previsao_termino'));
			$previsao                 = trim(pg_fetch_result($resPES,$i,'previsao'));
			$previsao_termino_interna = trim(pg_fetch_result($resPES,$i,'previsao_termino_interna'));
			$atrasou                  = trim(pg_fetch_result($resPES,$i,'atrasou'));
			$atrasou_interno          = trim(pg_fetch_result($resPES,$i,'atrasou_interno'));
			$cobrar                   = trim(pg_fetch_result($resPES,$i,'cobrar'));
			$hora_desenvolvimento     = trim(pg_fetch_result($resPES,$i,'hora_desenvolvimento'));
			$tipo_chamado_descricao   = trim(pg_fetch_result($resPES,$i,'tipo_chamado_descricao'));
			$prazo_horas              = pg_fetch_result($resPES,$i,'prazo_horas');
			$prioridade               = pg_fetch_result($resPES,$i,'prioridade');

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

			if ($atrasou == 0 AND $chamados_atrasados==1){
				//break;
			}


			$sql2 = "SELECT nome_completo, admin
				     FROM	tbl_admin
					 WHERE	admin='$atendente'";
			//echo $sql2;
			$res2 = pg_exec ($con,$sql2);
			$xatendente            = pg_result($res2,0,nome_completo);
			$xxatendente = explode(" ", $xatendente);

			$cor='#F2F7FF';
			if ($i % 2 == 0) $cor = '#FFFFFF';

			if ($atrasou_interno == '1'){
				$cor='#F8FBB3';
			}

			if ($hd_chamado == $hd_chamado_anterior){
				$cor='#B8B8B8';
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
				echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
				echo "<img src='../admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
			}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
					echo "<img src='../admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
				}elseif ($status == "Aprovação") {
					echo "<img src='../admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
				}else{
					echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
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
				if($status=="Aguard.Verifica")$cor_status="#ffff00";
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
	//echo "<td><font size='1' nowrap>&nbsp;$previsao_termino_interna&nbsp;</font></td>";
			echo "<td width='60'><font size='1'>$previsao_termino</font></td>";

			echo "</tr>";
			$interno='';
		}
	//fim imprime chamados

			echo "</tbody>";

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

		}else{

		 echo "<center><h3><font color='FF0000' >NENHUM CHAMADO</font></h3></center>";
		}
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}




////////////////////////APENAS O RODRIGO ///////////////////////////////////////////////
if($login_admin==1222 or $login_admin==586 or $login_admin == 432 || $login_admin==913 ){
		//$lista_novo_processo = '(3,42,15,5,47 ,89,86,7,51,85,8,87,11,19,72,40,66,5,45,61,88,77,76,78,75,81,59,6)';

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
				$cond_status
				AND tbl_hd_chamado.fabrica in $lista_novo_processo
				AND tbl_hd_chamado.status <> 'Resolvido'
				AND tbl_hd_chamado.status <> 'Cancelado'
				AND tbl_hd_chamado.status <> 'Aprovação'
				AND tbl_hd_chamado.tipo_chamado <> 5
				ORDER BY tbl_hd_chamado.previsao_termino_interna ASC,tbl_hd_chamado.previsao_termino ASC,tbl_hd_chamado.data DESC";

		//echo nl2br($sql); exit;

		$res = pg_exec ($con,$sql);

		if (@pg_numrows($res) > 0) {

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
			echo "<img src='../admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> Aguardando aprovação";
			echo "</td>";

			echo "<td width='50%' nowrap align='left'>";
			echo "<img src='../admin/imagens_admin/status_verde.gif' valign='absmiddle'> Resolvido";
			echo "</td>";


			echo "</tr>";

			echo "</table>";
			echo "<br>";


			/*--=============================== TABELA DE CHAMADOS ========================--*/

			echo "<table width = '100%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";

			echo "<thead>";
			echo "<tr>";
			echo "	<th >Nº </th>";
			echo "	<th nowrap>Título<img src='imagens/pixel.gif' width='5'></th>";
			echo "	<th >Status</th>";
			echo "	<th >Tipo</th>";
			echo "	<th >Data</th>";
			echo "	<th nowrap>Autor </strong></th>";
			echo "	<th nowrap>Fábrica </strong></td>";
			echo "	<th nowrap>Atendente </strong></th>";
			echo "	<th nowrap title='Horas Trabalhadas'>Trab.</th>";
			echo "	<th nowrap >Prazo</th>";
		//	echo "	<th nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Prazo Interno</strong></td>";
			echo "	<th nowrap>Previsão Cliente</td>";
			echo "  <th nowrap>Previsão Interna</td>";
			echo "</tr>";

			echo "</thead>";
			// ##### PAGINACAO ##### //
			$sqlCount  = "SELECT count(*) FROM (";
			$sqlCount .= $sql;
			$sqlCount .= ") AS count";


			//require "_class_paginacao.php";

			// definicoes de variaveis
			$max_links = 11;				// máximo de links à serem exibidos
			$max_res   = 100;				// máximo de resultados à serem exibidos por tela ou pagina
			$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
			$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

			$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

			// ##### PAGINACAO ##### //

			if (@pg_numrows($res) > 0) {

			//	echo $sql;

			//inicio imprime chamados
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					$hd_chamado               = pg_result($res,$i,hd_chamado);
					$hd_chamado_anterior	  = pg_result($res,$i,hd_chamado_anterior);
					$admin                    = pg_result($res,$i,admin);
					$login                    = pg_result($res,$i,login);
			//		$posto                    = pg_result($res,$i,posto);
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
			//		echo $sql2;
					$res2 = pg_exec ($con,$sql2);
					$xatendente            = pg_result($res2,0,nome_completo);
					$xxatendente = explode(" ", $xatendente);

					$cor='#F2F7FF';
					if ($i % 2 == 0) $cor = '#FFFFFF';

					if ($atrasou_interno == '1'){
						$cor='#F8FBB3';
					}

					if ($hd_chamado == $hd_chamado_anterior){
						$cor='#B8B8B8';
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
						echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
					}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
						echo "<img src='../admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
					}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
							echo "<img src='../admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
						}elseif ($status == "Aprovação") {
							echo "<img src='../admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
						}else{
							echo "<img src='../admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
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
						if($status=="Aguard.Verifica")$cor_status="#ffff00";
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
					echo "<td width='60'><font size='1'>$previsao_termino_interna</font></td>";

					echo "</tr>";
					$interno='';
				}

			//fim imprime chamados

					echo "</tbody>";

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

}
?>
</div>
<?
include "rodape.php"
?>
