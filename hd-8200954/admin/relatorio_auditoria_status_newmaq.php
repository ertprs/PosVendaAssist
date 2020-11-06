<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "auditoria";
include "autentica_admin.php";
include __DIR__.'/funcoes.php';

function ultima_interacao($os) {
	global $con, $login_fabrica;

	$select = "SELECT admin, posto FROM tbl_os_interacao WHERE fabrica = {$login_fabrica} AND os = {$os} ORDER BY data DESC LIMIT 1";
	$result = pg_query($con, $select);

	if (pg_num_rows($result) > 0) {
		$admin = pg_fetch_result($result, 0, "admin");
		$posto = pg_fetch_result($result, 0, "posto");

		if (!empty($admin)) {
			$ultima_interacao = "fabrica";
		} else {
			$ultima_interacao = "posto";
		}
	}

	return $ultima_interacao;
}

function os_excluida($os) {
	global $con, $login_fabrica;

	$select = "SELECT os_excluida FROM tbl_os_excluida WHERE fabrica = {$login_fabrica} AND os = {$os}";
	$result = pg_query($con, $select);

	if (pg_num_rows($result) > 0) {
		return true;
	}
	return false;
}

function os_status($os, $auditoria_os) {
	global $con, $login_fabrica;
	$mensagem = "";

	$select = "SELECT * FROM tbl_auditoria_os WHERE os = {$os} AND auditoria_os = $auditoria_os";
	$result = pg_query($con, $select);

	if (pg_num_rows($result) > 0) {
		$liberada      = pg_fetch_result($result, 0, "liberada");
		$paga_mao_obra = pg_fetch_result($result, 0, "paga_mao_obra");
		$justificativa = pg_fetch_result($result, 0, "justificativa");
		$reprovada     = pg_fetch_result($result, 0, "reprovada");
		$cancelada     = pg_fetch_result($result, 0, "cancelada");

		if(!empty($liberada)){
			if($paga_mao_obra == 't'){
				$mensagem = "Aprovado";
			}else if($paga_mao_obra == 'f'){
				$mensagem = "Aprovado Sem MO";
			}
		}else{
			if(!empty($cancelada)){
				$mensagem = "Cancelado OS";
			}else if(empty($reprovada)){
				$mensagem = "Reprovado OS";
			}
		}
	}
	return $mensagem;
}

if(isset($_POST['btn_acao']) && !empty($_POST['btn_acao'])){
	$btn_acao = $_POST['btn_acao'];
}else if(isset($_POST['btn_listar_auditoria']) && !empty($_POST['btn_listar_auditoria'])){
	$btn_acao = "submit";
}

if($btn_acao == 'consultaStatus'){
	$os           = $_POST['os'];
	$auditoria_os = $_POST['auditoria_os'];

	if(os_excluida($os)){
		$resultado = array("tipoStatus" => "danger", "descricao" => "OS cancelada");
	}else{
		$status = os_status($os, $auditoria_os);
		if($status == "Aprovado"){
			$resultado = array("tipoStatus" => "success", "descricao" => $status);

		}else if($status == "Aprovado Sem MO"){
			$resultado = array("tipoStatus" => "warning", "descricao" => $status);

		}else if($status == "Reprovado OS"){
			$resultado = array("tipoStatus" => "inverse", "descricao" => $status);
		}
	}
	echo json_encode($resultado); exit;
}


if ($btn_acao == "inserirKM") {
	$os = trim($_POST['os']);
	$novo_km = trim($_POST['km']);

	pg_query($con, "BEGIN");

	$sql = "UPDATE tbl_os SET qtde_km = $novo_km WHERE tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica";
	pg_query($con, $sql);

	if(strlen(pg_last_error()) > 0){
		pg_query($con, "ROLLBACK");
		$resposta = array("resultado" => false, "mensagem", "Erro ao atualizar a quantidade de KM da os $os.");
	}else{
		pg_query($con, "COMMIT");
		$resposta = array("resultado" => true);
	}

	echo json_encode($resposta); exit;
}

if ($btn_acao == "aprovaOS") {
	$os           = trim($_POST['os']);
	$mao_obra     = trim($_POST['mao_obra']);
	$auditoria_os = trim($_POST['auditoria_os']);
	$condicao     = "";

	pg_query($con, "BEGIN");

	if($mao_obra == "true"){
		$condicao = ", paga_mao_obra = 'f'";
	}

	$sql = "UPDATE tbl_auditoria_os SET liberada = current_timestamp,
				admin = $login_admin $condicao
		WHERE tbl_auditoria_os.os = $os AND auditoria_os = $auditoria_os;";
	pg_query($con, $sql);

	$sql = "SELECT posto FROM tbl_os WHERE os = {$os};";
	$resPosto = pg_query($con,$sql);

	if (in_array($login_fabrica, array(156))) {

		$sql = "SELECT
				oi.os_item,
				oi.qtde,
				p.referencia as peca_referencia,
				p.referencia||' - '||p.descricao as peca_descricao,
				oi.custo_peca
			FROM tbl_os_item oi
			JOIN tbl_peca p USING(peca)
			WHERE oi.fabrica_i = {$login_fabrica}
			AND oi.os_produto IN (SELECT
							os_produto
						FROM tbl_os_produto
						WHERE os = {$os});
			";

		$resPeca = pg_query($con,$sql);
		$count_k = pg_num_rows($resPeca);
	}

	$sql = "SELECT
			tbl_auditoria_status.descricao
		FROM tbl_auditoria_os
		INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
		WHERE tbl_auditoria_os.os = {$os}
		AND tbl_auditoria_os.auditoria_os = {$auditoria_os}";

	$resAud = pg_query($con, $sql);

	if(pg_num_rows($resPosto) > 0){
		$posto = pg_fetch_result($resPosto, 0, "posto");
		$auditoria_desc = utf8_encode(pg_fetch_result($resAud, 0, "descricao"));

		if ($count_k > 0) {
			$tablePecas = "<br /><br />";
			$tablePecas .= "<table border=1>";
			$tablePecas .= "<thead>";
			$tablePecas .= "<tr>";
			$tablePecas .= "<th><b>Código</b></th>";
			$tablePecas .= "<th><b>Descrição</b></th>";
			$tablePecas .= "<th><b>Qtde</b></th>";
			$tablePecas .= "<th><b>Valor</b></th>";
			$tablePecas .= "</tr>";
			$tablePecas .= "</thead>";
			$tablePecas .= "<tbody>";
			for ($k = 0; $k < $count_k; $k++) {
				$c_os_item                = pg_fetch_result($resPeca, $k, os_item);
				$c_peca_referencia    = pg_fetch_result($resPeca, $k, peca_referencia);
				$c_peca_descricao     = pg_fetch_result($resPeca, $k, peca_descricao);
				$c_qtde                       = pg_fetch_result($resPeca, $k, qtde);
				$c_custo_peca             = pg_fetch_result($resPeca, $k, custo_peca);
				$tablePecas .= "<tr>";
				$tablePecas .= "<td>".$c_peca_referencia."</td>";
				$tablePecas .= "<td>".$c_peca_descricao."</td>";
				$tablePecas .= "<td>".$c_peca_descricao."</td>";
				$tablePecas .= "<td>".$c_qtde."</td>";
				$tablePecas .= "<td>".$c_custo_peca."</td>";
				$tablePecas .= "</tr>";
			}
			$tablePecas .= "</tbody>";
                		$tablePecas .= "</table>";

                		$tablePecas = utf8_encode($tablePecas);
		}

		$sql = "INSERT INTO tbl_comunicado 
					(fabrica,posto, obrigatorio_site, tipo, ativo, descricao, mensagem) 
			VALUES (
					{$login_fabrica},{$posto}, true, 'Com. Unico Posto', true,
					'Auditoria',
					'OS {$os} foi aprovada na {$auditoria_desc}{$tablePecas}');";
		
		pg_query($con, $sql);
	}

	if(strlen(pg_last_error()) > 0){
		pg_query($con, "ROLLBACK");
		$resposta = array("resultado" => false, "mensagem", "Erro ao aprovar a os $os.");
	}else{
		pg_query($con, "COMMIT");
		$resposta = array("resultado" => true);
	}
	echo json_encode($resposta); exit;
}

if ($btn_acao == "reprovaOS") {
	$os            = trim($_POST['os']);
	$justificativa = trim($_POST['justificativa']);
	$auditoria_os  = trim($_POST['auditoria_os']);

	pg_query($con, "BEGIN");

	$sql = "UPDATE tbl_auditoria_os SET  reprovada = current_timestamp,
			admin = $login_admin,
			justificativa = '$justificativa'
		WHERE tbl_auditoria_os.os = $os AND auditoria_os = $auditoria_os";
	pg_query($con, $sql);

	$sql = "SELECT posto FROM tbl_os WHERE os = {$os}";
	$resPosto = pg_query($con,$sql);

	$sql = "SELECT
			tbl_auditoria_status.descricao
		FROM tbl_auditoria_os
		INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
		WHERE tbl_auditoria_os.os = {$os}
		AND tbl_auditoria_os.auditoria_os = {$auditoria_os}";
	$resAud = pg_query($con, $sql);

	if(pg_num_rows($resPosto) > 0){
		$posto = pg_fetch_result($resPosto, 0, "posto");
		$auditoria_desc = pg_fetch_result($resAud, 0, "descricao");

		$sql = "INSERT INTO tbl_comunicado 
					(fabrica,posto, obrigatorio_site, tipo, ativo, descricao, mensagem) 
			VALUES (
					{$login_fabrica},{$posto}, true, 'Com. Unico Posto', true,
					'Auditoria',
					'OS {$os} foi reprovada na {$auditoria_desc}');";
		pg_query($con, $sql);
	}

	if(strlen(pg_last_error()) > 0){
		pg_query($con, "ROLLBACK");
		$resposta = array("resultado" => false, "mensagem", "Erro ao reprovar a os $os.");
	}else{
		pg_query($con, "COMMIT");
		$resposta = array("resultado" => true);
	}
	echo json_encode($resposta); exit;
}

if ($btn_acao == "cancelaOS") {
	$os            = trim($_POST['os']);
	$justificativa = trim($_POST['justificativa']);
	$auditoria_os  = trim($_POST['auditoria_os']);

	pg_query($con, "BEGIN");

	$sql = "SELECT fn_os_excluida($os, $login_fabrica, $login_admin)";
	pg_query($con, $sql);

	if(strlen(pg_last_error()) > 0){
		pg_query($con, "ROLLBACK");
		$resposta = array("resultado" => false, "mensagem", "Erro ao cancelar a os $os.");
	}else{
		$sql = "UPDATE tbl_auditoria_os SET  cancelada = current_timestamp,
			admin         = $login_admin,
			justificativa = '$justificativa'
		WHERE tbl_auditoria_os.os = $os AND auditoria_os = $auditoria_os";
		pg_query($con, $sql);

		if(strlen(pg_last_error()) > 0){
			pg_query($con, "ROLLBACK");
			$resposta = array("resultado" => false, "mensagem", "Os $os cancelada. Mas ocorreu um erro ao alterar os status na auditoria.");
		}else{
			$sql = "SELECT posto FROM tbl_os WHERE os = {$os}";
			$resPosto = pg_query($con,$sql);

			if (in_array($login_fabrica, array(156))) {
				$sql = "SELECT
						oi.os_item,
						oi.qtde,
						p.referencia as peca_referencia,
						p.referencia||' - '||p.descricao as peca_descricao,
						oi.custo_peca
					FROM tbl_os_item oi
					JOIN tbl_peca p USING(peca)
					WHERE oi.fabrica_i = {$login_fabrica}
					AND oi.os_produto IN (SELECT
									os_produto
								FROM tbl_os_produto
								WHERE os = {$os});
					";

				$resPeca = pg_query($con,$sql);
				$count_k = pg_num_rows($resPeca);
			}

			$sql = "SELECT tbl_auditoria_status.descricao FROM tbl_auditoria_os INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status WHERE tbl_auditoria_os.os = {$os} AND tbl_auditoria_os.auditoria_os = {$auditoria_os}";
			$resAud = pg_query($con, $sql);

			if(pg_num_rows($resPosto) > 0){
				$posto = pg_fetch_result($resPosto, 0, "posto");
				$auditoria_desc = utf8_encode(pg_fetch_result($resAud, 0, "descricao"));

				if ($count_k > 0) {
					$tablePecas = "<br /><br />";
					$tablePecas .= "<table border=1>";
					$tablePecas .= "<thead>";
					$tablePecas .= "<tr>";
					$tablePecas .= "<th><b>Código</b></th>";
					$tablePecas .= "<th><b>Descrição</b></th>";
					$tablePecas .= "<th><b>Qtde</b></th>";
					$tablePecas .= "<th><b>Valor</b></th>";
					$tablePecas .= "</tr>";
					$tablePecas .= "</thead>";
					$tablePecas .= "<tbody>";
						for ($k = 0; $k < $count_k; $k++) {
						$c_os_item                = pg_fetch_result($resPeca, $k, os_item);
						$c_peca_referencia    = pg_fetch_result($resPeca, $k, peca_referencia);
						$c_peca_descricao     = pg_fetch_result($resPeca, $k, peca_descricao);
						$c_qtde                       = pg_fetch_result($resPeca, $k, qtde);
						$c_custo_peca             = pg_fetch_result($resPeca, $k, custo_peca);
						$tablePecas .= "<tr>";
						$tablePecas .= "<td>".$c_peca_referencia."</td>";
						$tablePecas .= "<td>".$c_peca_descricao."</td>";
						$tablePecas .= "<td>".$c_peca_descricao."</td>";
						$tablePecas .= "<td>".$c_qtde."</td>";
						$tablePecas .= "<td>".$c_custo_peca."</td>";
						$tablePecas .= "</tr>";
					}
					$tablePecas .= "</tbody>";
					$tablePecas .= "</table>";

					$tablePecas = utf8_encode($tablePecas);
				}

				$sql = "INSERT INTO tbl_comunicado 
						(fabrica,posto, obrigatorio_site, tipo, ativo, descricao, mensagem) 
					VALUES (
						{$login_fabrica},{$posto}, true, 'Com. Unico Posto', true,
						'Auditoria',
						'OS {$os} foi cancelada na {$auditoria_desc}{$tablePecas}')";
				pg_query($con, $sql);

				if(pg_last_error() > 0){
					pg_query($con,"ROLLBACK");
					$resposta = array("resultado" => false, "mensagem", "Erro ao gerar comunicado para o posto.");
				}else{
					pg_query($con,"COMMIT");
					$resposta = array("resultado" => true);
				}
			}else{
				pg_query($con, "COMMIT");
				$resposta = array("resultado" => true);
			}
		}
	}
	echo json_encode($resposta); exit;
}

if ($btn_acao == "submit") {
	$data_inicial         = trim($_POST["data_inicial"]);
	$data_final           = trim($_POST["data_final"]);
	$status_auditoria     = trim($_POST["status_auditoria"]);
	$posto_codigo         = trim($_POST["posto"]["codigo"]);
	$posto_nome           = trim($_POST["posto"]["nome"]);
	$estado               = trim($_POST["estado"]);
	$auditoria_aprovado   = trim($_POST["auditoria_aprovado"]);
	$auditoria_reprovado  = trim($_POST["auditoria_reprovado"]);
	$os_pesquisa          = trim($_POST["os_pesquisa"]);
	$btn_listar_auditoria = false;

	if(isset($_POST['btn_listar_auditoria']) && !empty($_POST['btn_listar_auditoria'])){
		$btn_listar_auditoria = true;
	}

	if (empty($os_pesquisa) && $btn_listar_auditoria == false && (empty($data_inicial) || empty($data_final))) {
		$msg_erro['msg']["obg"] = "Preencha os campos obrigatórios";
		$msg_erro['campos'][]   = "data_inicial";
		$msg_erro['campos'][]   = "data_final";
	}

	if (empty($status_auditoria)) {
		$msg_erro['msg']["obg"] = "Preencha os campos obrigatórios";
		$msg_erro['campos'][]   = "status_auditoria";
	}


	if (!count($msg_erro["msg"])) {
		$condicao = "";

		if(empty($os_pesquisa) && !$btn_listar_auditoria){
			try {
				validaData($data_inicial, $data_final, 3);

				list($dia, $mes, $ano) = explode("/", $data_inicial);
                                $aux_data_inicial      = $ano."-".$mes."-".$dia;

                                list($dia, $mes, $ano) = explode("/", $data_final);
                                $aux_data_final        = $ano."-".$mes."-".$dia;

                                $condicao = " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
			} catch (Exception $e) {
				$msg_erro["msg"][] = $e->getMessage();
				$msg_erro["campos"][] = "data_inicial";
				$msg_erro["campos"][] = "data_final";
			}
		}else if (!empty($os_pesquisa) && !$btn_listar_auditoria) {
			$condicao = " AND tbl_os.os = {$os_pesquisa}";
		}

		if(count($msg_erro['msg']) == 0){

			if (strlen($posto_codigo) > 0 or strlen($posto_nome) > 0){
				$sql = "SELECT tbl_posto_fabrica.posto FROM tbl_posto
							JOIN tbl_posto_fabrica USING(posto)
						WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
							AND ((UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_codigo}')) OR
						(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$posto_nome}'), 'LATIN-9'))
						)";
				$res = pg_query($con ,$sql);

				if (!pg_num_rows($res)) {
					$msg_erro["msg"][]   .= "Posto não encontrado";
					$msg_erro["campos"][] = "posto";
				} else {
					$posto = pg_fetch_result($res, 0, "posto");
				}
			}

			if(strlen($estado) > 0){
				if(!in_array($login_fabrica, array(152)) && !isset($array_estado[$estado])){
					$msg_erro["msg"][]   .= "Estado não encontrado";
					$msg_erro["campos"][] = "estado";
				}
			}

			if(count($msg_erro["msg"]) == 0){
				if($auditoria_aprovado == "1"){
					$condicao .= " AND tbl_auditoria_os.liberada IS NOT NULL";
				}else if($auditoria_reprovado == "1"){
					$condicao .= " AND tbl_auditoria_os.reprovada IS NOT NULL";
				}else{
					$condicao .= " AND tbl_auditoria_os.liberada IS NULL AND tbl_auditoria_os.reprovada IS NULL";
				}

				if (strlen($posto_codigo) > 0 or strlen($posto_nome) > 0){
					$condicao .= " AND tbl_posto_fabrica.posto = $posto";
				}

				if(strlen($estado) > 0){
					if(in_array($login_fabrica, array(152))){
						$estado = str_replace(",", "','",$estado);
					}
					$condicao .= " AND tbl_posto.estado IN ('$estado')";
				}

				$sql = "SELECT tbl_os.os,
						tbl_os.sua_os,
						tbl_os.data_abertura,
						tbl_os.serie,
						tbl_os_extra.os_reincidente,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto,
						tbl_os.qtde_km,
						tbl_os.produto,
						tbl_os.tipo_os,
						tbl_auditoria_os.auditoria_os,
						tbl_auditoria_os.paga_mao_obra,
						tbl_auditoria_os.liberada,
						tbl_auditoria_os.reprovada,
						tbl_auditoria_os.observacao,
						tbl_auditoria_status.fabricante,
						tbl_auditoria_status.descricao AS descricao_auditoria,
						tbl_tipo_atendimento.km_google
					FROM tbl_os
					LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
					LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
					INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					INNER JOIN tbl_auditoria_os ON tbl_auditoria_os.os = tbl_os.os
					INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
					WHERE tbl_os.fabrica = {$login_fabrica}
					AND tbl_auditoria_status.auditoria_status = $status_auditoria
					{$condicao}
					ORDER BY tbl_os.os";
				$resConsulta = pg_query($con,$sql);
				
				// É atribuído novamente o valor original do POST para a variável utilizar no elemento select
				$estado = trim($_POST["estado"]);
			}
		}
	}
}

$layout_menu = "auditoria";
$title = "AUDITORIA DE ORDEM DE SERVIÇO";

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "price_format",
   "select2"
);

include __DIR__."/plugin_loader.php";

?>
<style>
#mensagem_justificativa{
	margin-left: 80px;
}

.admin {
	background-color: #FF00FF;
}

.posto {
	background-color: #FFFF00;
}

a {
	cursor: pointer;
}
</style>
<script type="text/javascript">
$(function() {
	$("#data_inicial").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	$("select").select2();

	/**
	 * Inicia o shadowbox, obrigatório para a lupa funcionar
	 */
	Shadowbox.init();

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	$("#resultado_posto > tbody > tr > td[id^=peca_]").click(function(){
		if ( $("#"+this.id).html().length > 20 ){
			Shadowbox.open({content: $("#div_"+this.id).html(), player: "html", width: 800, heigth: 600 });
		}
	});

	$("#resultado_posto > tbody > tr > td > a[id^=produto_]").click(function(){
		if ( $("#"+this.id).html().length > 20 ){
			Shadowbox.open({content: $("#div_"+this.id).html(), player: "html", width: 800, heigth: 600 });
		}
	});

	$("#resultado_posto > tbody > tr > td[id^=defeito_]").click(function(){
		if ( $("#"+this.id).html().length > 20 ){
			Shadowbox.open({content: $("#div_"+this.id).html(), player: "html", width: 800, heigth: 600 });
		}
	});

	$("button[id^=btReprovado_]").click(function(){
		var linha = this.id.replace(/\D/g, "");
		var os    = $("#os_"+linha).val();
		var auditoria_os = $("#auditoria_os_"+linha).val();

		$("input.numero_linha").val(linha);
		$("input.numero_os").val(os);
		$("input.acao_justificativa").val("reprovaOS");
		$("input.numero_auditoria_os").val(auditoria_os);
		// var divClone = $(".div_justificativa").clone();

		Shadowbox.open({
			content: $(".div_justificativa").html(),
			player: "html",
			width: 800,
			heigth: 600,
			options: {
				enableKeys: false
			}
		});
	});

	$("button[id^=btCancelado_]").click(function(){
		var linha        = this.id.replace(/\D/g, "");
		var os           = $("#os_"+linha).val();
		var auditoria_os = $("#auditoria_os_"+linha).val();

		$("input.numero_linha").val(linha);
		$("input.numero_os").val(os);
		$("input.acao_justificativa").val("cancelaOS");
		$("input.numero_auditoria_os").val(auditoria_os);
		// var divClone = $(".div_justificativa").clone();

		Shadowbox.open({
			content: $(".div_justificativa").html(),
			player: "html",
			width: 800,
			heigth: 600,
			options: {
				enableKeys: false
			}
		});
	});

	$("input[id^=qtde_km_]").priceFormat({
		prefix: '',
        thousandsSeparator: '',
        centsSeparator: '.',
        ca: 2
	});

	$("button[id^=btInteragir_]").click(function(){
		var linha = this.id.replace(/\D/g, "");

		var os = $("#os_"+linha).val();
		Shadowbox.open({
			content: "interacao_os.php?os="+os,
			player: "iframe",
			width: 700,
			options: {
				enableKeys: false
			}
		});
	});

	<? if ($auditoria_aprovado == "1" || $auditoria_reprovado == "1") { ?>
		$("input.btn_listar_auditoria").hide();
	<? } else { ?>
		$("input.btn_listar_auditoria").show();
	<? } ?>

	$("input.auditoria_aprovado").on("click",function(){
		if($(this).is(":checked")){
			$("input.btn_listar_auditoria").hide();
			$("input.auditoria_reprovado").attr("checked", false);
		}else{
			$("input.btn_listar_auditoria").show();
		}
	});

	$("input.auditoria_reprovado").on("click",function(){
		if($(this).is(":checked")){
			$("input.btn_listar_auditoria").hide();
			$("input.auditoria_aprovado").attr("checked", false);
		}else{
			$("input.btn_listar_auditoria").show();
		}
	});

	<? if (in_array($login_fabrica, array(156))) { ?>
		$("button[id^=btEditarVal_]").click(function () {
			var linha = this.id.replace(/\D/g, "");
			var os = $("#os_"+linha).val();
			Shadowbox.open({
				content: "editar_valor_pecas.php?os="+os,
				player: "iframe",
				width: 800,
				options: {
					enableKeys: false
				}
			});
		});
	<? } ?>
});

/**
 * Função de retorno da lupa do posto
 */
function retorna_posto(retorno) {
	/**
	 * A função define os campos código e nome como readonly e esconde o botão
	 * O posto somente pode ser alterado quando clicar no botão trocar_posto
	 * O evento do botão trocar_posto remove o readonly dos campos e dá um show nas lupas
	 */
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo).attr({ readonly: "readonly" });
	$("#posto_nome").val(retorno.nome).attr({ readonly: "readonly" });
	$("#div_trocar_posto").show();
	$("#div_informacoes_posto").find("span[rel=lupa]").hide();
}

function salvarKM(linha, os){
	$("button[id^=btKM_"+linha+"]").button('loading');
	var novo_km = document.querySelector("#qtde_km_"+linha);
	var dataAjax = {
        os: os,
        km: novo_km.value,
        btn_acao: "inserirKM"
    };

	$.ajax({
        url: "<?= $_SERVER['PHP_SELF']; ?>",//'relatorio_auditoria_status.php',
        type: "POST",
        data: dataAjax,
    }).done( function(data){
        	var mensagem;
        	data = JSON.parse(data);
        	if(data.resultado == false){
        		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
        	}else{
        		$("#mensagem_km_"+linha).addClass("alert alert-success");
        		$("#mensagem_km_"+linha).html("Gravado");
        	}
        	$("button[id^=btKM_"+linha+"]").button('reset');
	}).fail(function(){
		var mensagem;
    	data = JSON.parse(data);
    	if(data.resultado == false){
    		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
    	}
    	$("button[id^=btKM_"+linha+"]").button('reset');
	});
}

function aprovarOS(linha, os, auditoria_os, mao_obra){
	if(confirm("Deseja realmente aprovar a OS?")){
		if(mao_obra != ""){
			$("button[id=btMaoObrao_"+linha+"]").button('loading');
		}else{
			$("button[id=btAprovado_"+linha+"]").button('loading');
		}
		var novo_km = document.querySelector("#qtde_km_"+linha);
		var dataAjax = {
			os: os,
			btn_acao: "aprovaOS",
			mao_obra: mao_obra,
			auditoria_os: auditoria_os
		}

		$.ajax({
			url: "<?= $_SERVER['PHP_SELF']; ?>", //'relatorio_auditoria_status.php',
			type: "POST",
			data: dataAjax,
		}).done( function(data){
			data = JSON.parse(data);
			console.log(data);
			if(data.resultado == false){
				$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4></div>');
			}
			if(mao_obra != ""){
				$("button[id=btMaoObrao_"+linha+"]").button('reset');
			}else{
				$("button[id=btAprovado_"+linha+"]").button('reset');
			}
			atualizaStatus(linha,os);
		});
	}
}

function reprovarOS(){
	var resposta;
	var bt = $("input.acao_justificativa").val();

	if(bt == "reprovaOS"){
		resposta = confirm("Deseja realmente Reprovar a OS?");
	}else{
		resposta = confirm("Deseja realmente Cancelar a OS?");
	}

	if(resposta){
		var linha = $("input.numero_linha").val();

		if(bt == "reprovaOS"){
			$("button[id=btReprovado_"+linha+"]").button('loading');
		}else{
			$("button[id=btCancelado_"+linha+"]").button('loading');
		}

		var dataAjax = {
	        os: $("input.numero_os").val(),
	        justificativa: $.trim($("#sb-container").find("textarea#justificativa").val()),
	        btn_acao: $("input.acao_justificativa").val(),
	        auditoria_os: $("input.numero_auditoria_os").val()
	    };

		$.ajax({
	        url: "<?=$_SERVER['PHP_SELF']?>",//'relatorio_auditoria_status.php',
	        type: "POST",
	        data: dataAjax,
	    }).done( function(data){
	    	var mensagem;
	    	data = JSON.parse(data);

	    	if(data.resultado == false){
	    		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
	    	}

			if(bt == "reprovaOS"){
				$("button[id=btReprovado_"+linha+"]").button('reset');
			}else{
				$("button[id=btCancelado_"+linha+"]").button('reset');
			}

			atualizaStatus(linha,$("input.numero_os").val());
			Shadowbox.close();

		}).fail(function(data){
			if(data.resultado == false){
	    		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
	    	}

			if(bt == "reprovaOS"){
				$("button[id=btReprovado_"+linha+"]").button('reset');
			}else{
				$("button[id=btCancelado_"+linha+"]").button('reset');
			}

			atualizaStatus(linha,$("input.numero_os").val());
			Shadowbox.close();
		});
	}
}

function atualizaStatus(linha, os){
	var dataAjax = {
		os: os,
		auditoria_os: $("#auditoria_os_"+linha).val(),
		btn_acao: "consultaStatus"
	};

	$.ajax({
		url: "<?= $_SERVER['PHP_SELF']; ?>",//'relatorio_auditoria_status.php',
		type: "POST",
		data: dataAjax,
	}).done( function(data){
		data = JSON.parse(data);
		$("#resultado_posto > tbody > tr > td[id=status_"+linha+"]").html('<label class="label label-'+data.tipoStatus+'">'+data.descricao+'</label>');
	}).fail(function(data){
		if(data.resultado == false){
			$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
		}
	});
}

</script>
<? if (count($msg_erro['msg']) > 0) { ?>
	<br/>
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
	<br/>
<? } ?>
<div class="div_justificativa" style="display:none; margin: 5px; padding-right: 20px;">
	<div id="mensagem_justificativa">
		<br/>
		<label>Justificativa</label>
		<textarea id="justificativa" name="justificativa" rows="10" cols="10" style="margin: 0px 0px 10px; width: 603px; height: 200px;"></textarea>
		<br/>
		<button type="button" style="position:rigth" class="btn btn-primary btn-sucess" data-loading-text="Salvando..." id="btJustificativa" onclick="reprovarOS();">Salvar</button>
	</div>
</div>
<div id="DivInteragir" style="display: none;" >
	<div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
	<div class="conteudo" >
		<div class="titulo_tabela" >Interagir na OS</div>
		<div class="row-fluid">
			<div class="span12">
				<div class="controls controls-row">
					<textarea name="text_interacao" class="span12"></textarea>
				</div>
			</div>
		</div>
		<p><br/>
			<button type="button" name="button_interagir" class="btn btn-primary btn-block" rel="__NumeroOs__" >Interagir</button>
		</p>
		<br/>
	</div>
</div>
<div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios</b>
</div>

<form method="POST" action="<?echo $PHP_SELF?>" name="frm_relatorio_auditoria" align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<input type="hidden" class="numero_os" value="" />
		<input type="hidden" class="numero_linha" value="" />
		<input type="hidden" class="numero_auditoria_os" value="" />
		<input type="hidden" class="acao_justificativa" value="" />

		<div class='row-fluid'>
			<div class="span1"></div>
			<div class="span2">
				<div class='control-group'>
					<label class="control-label" for="os_pesquisa">Número da OS</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="os_pesquisa" name="os_pesquisa" class="span12" type="text" value="<?=$os_pesquisa?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_inicial">Data Inicial</label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="data_inicial" name="data_inicial" class="span12" type="text" value="<?=$data_inicial?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('data_final', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_final">Data Final</label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="data_final" name="data_final" class="span12" type="text" value="<?=$data_final?>" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array('status_auditoria', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class='control-label' for='status_auditoria'>Tipo de Auditoria</label>
					<div class='controls controls-row'><h5 class='asteristico'>*</h5>
						<select name="status_auditoria" class="span12">
							<option value=""></option>
							<?php
								$sql = "SELECT * FROM tbl_auditoria_status";
								$res = pg_query($con, $sql);
                                $auditorias['peca']=true;
								if(pg_num_rows($res) > 0){
									while ($auditoria_status = pg_fetch_object($res)) {
										$liberado = false;
										if(($auditorias['reincidente']) && $auditoria_status->reincidente == 't'){
											$auditoria_reincidente = $auditoria_status->auditoria_status;
											$liberado = true;

										}else if(($auditorias['km']) && $auditoria_status->km == 't'){
											$auditoria_km = $auditoria_status->auditoria_status;
											$liberado = true;

										}else if(($auditorias['produto']) && $auditoria_status->produto == 't'){
											$liberado = true;

										}else if(($auditorias['peca']) && $auditoria_status->peca == 't'){
											$liberado = true;

										}else if(($auditorias['numero_serie']) && $auditoria_status->numero_serie == 't'){
											$auditoria_numero_serie = $auditoria_status->auditoria_status;
											$liberado = true;

										}else if(($auditorias['fabricante']) && $auditoria_status->fabricante == 't'){
											$auditoria_fabrica = $auditoria_status->auditoria_status;
											$liberado = true;

										}

										if($liberado){
											$selected = ($auditoria_status->auditoria_status == $status_auditoria) ? "selected" : "";
										?>
											<option value="<?=$auditoria_status->auditoria_status?>" <?=$selected?> ><?=$auditoria_status->descricao?></option>
									<?php
										}
									}
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span1'></div>
			<input type="hidden" id="posto" name="posto" value="<?=$posto?>" />
			<div class="span2">
				<div class='control-group' >
						<label class="control-label" for="posto_codigo">Código do Posto</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=getValue('posto[codigo]')?>" <?=$posto_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>
						</div>
					</div>
			</div>

			<div class="span4">
				<div class='control-group' >
						<label class="control-label" for="posto_nome">Nome do Posto</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=getValue('posto[nome]')?>" <?=$posto_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</div>
						</div>
					</div>
			</div>
			<div class='span1'></div>
		</div>
		<div class='row-fluid'>
			<div class='span1'></div>
			<div class="span6">
				<div class="control-group">
					<label class="control-label" for="estado" >Estado/Região</label>
					<div class="controls control-row">
						<select id="estado" name="estado" class="span12" >
							<option value="" ></option>
							<?php
							if ($login_fabrica == 152) {
								$array_regioes = array(
									"AC,AM,RR,PA,AP,MA,TO,PI,CE,RN,PB,PE,AL,SE,BA,SP",
									"RO,MT,GO,DF,MG",
									"RJ,ES,MS,PR,SC,RS"
								);
							}

							if (count($array_regioes) > 0) {
							?>
								<optgroup label="Regiões" >
									<?php
									foreach ($array_regioes as $regiao) {
										$selected = ($estado == $regiao) ? "selected" : "";
										echo "<option value='{$regiao}'  {$selected} >{$regiao}</option>";
									}
									?>
								</optgroup>
								<optgroup label="Estados" >
							<?
							}

							foreach ($array_estados() as $sigla => $estado_nome) {
								$selected = ($estado == $sigla) ? "selected" : "";

								echo "<option value='{$sigla}' {$selected} >{$estado_nome}</option>";
							}

							if (count($array_regioes) > 0) { ?>
								</optgroup>
							<? } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="row-fluid" >
			<div class="span1" ></div>
			<div class='span4'>
				<div class="control-group">
					<div class="controls" >
						<label class="checkbox label label-success" >
							<input type='checkbox' class='auditoria_aprovado' name="auditoria_aprovado" value='1' <?php if($_POST["auditoria_aprovado"] == '1'){ echo "CHECKED"; }?> /> Auditorias Aprovadas
						</label>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class="control-group">
					<div class="controls" >
						<label class="checkbox label label-important" >
							<input type='checkbox' class='auditoria_reprovado' name="auditoria_reprovado" value='1' <?php if($_POST["auditoria_reprovado"] == '1'){ echo "CHECKED"; }?> /> Auditorias Reprovadas
						</label>
					</div>
				</div>
			</div>
		</div>
		<p>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<input type="submit" class='btn btn-primary btn_listar_auditoria' name="btn_listar_auditoria" id="btn_listar_auditoria" value="Listar Todas">
		</p>
		<br />
</form>
<br>
<?
if ($btn_acao == "submit") {
	if (pg_num_rows($resConsulta) > 0) { ?>
		<table border='0' cellspacing='0' cellpadding='5' style="margin: 0 auto;">
		    <tr>
			    <td style="width:10px; background-color: #dff0d8; border-color: #d6e9c6"></td>
			    <td><b>Interação Admin</b></td>
		    </tr>
		    <tr>
			    <td style="width:10px; background-color: #f2dede; border-color: #d6e9c6"></td>
			    <td><b>Interação Posto</b></td>
		    </tr>
	    </table>
	    <br/>
	    <?php
	    $count = pg_num_rows($resConsulta);

		for ($i = 0 ; $i < $count; $i++) {
			$os	= pg_fetch_result($resConsulta,$i,'os');

			$sql = "SELECT tbl_peca.referencia ,
							tbl_peca.descricao,
							tbl_peca.peca_critica,
							tbl_os_item.qtde,
							tbl_servico_realizado.descricao AS servico_realizado
					FROM tbl_peca
						JOIN tbl_os_item USING (peca)
						JOIN tbl_os_produto USING (os_produto)
						LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
					WHERE tbl_os_produto.os = $os";
			$resPeca = pg_query($con,$sql);

			if(pg_num_rows($resPeca) > 0){
				$count_k = pg_num_rows($resPeca);

				?>
				<div id='div_peca_<?=$i?>' style="display:none">
					<h4 style="margin: 10px;">OS: <?=$os?></h4>
					<table id="resultado_peca_<?=$i?>" class='table table-striped table-bordered table-hover table-fixed' style="margin: 10px; padding-right: 20px;">
						<thead>
							<tr class='titulo_coluna'>
								<th>Código</th>
								<th>Descrição</th>
								<th>Peça Critica</th>
								<th>Qtde</th>
								<th>Serviço Realizado</th>
							</tr>
						</thead>
						<tbody>
							

				<?

				for($k=0; $k<$count_k; $k++){
					$codigo_peca       = pg_fetch_result($resPeca,$k,'referencia');
					$descricao_peca    = pg_fetch_result($resPeca,$k,'descricao');
					$peca_critica      = pg_fetch_result($resPeca,$k,'peca_critica');
					$qtde              = pg_fetch_result($resPeca,$k,'qtde');
					$servico_realizado = pg_fetch_result($resPeca,$k,'servico_realizado');

					if($peca_critica == 't'){
						$peca_critica = "Sim";
					}else{
						$peca_critica = "Não";
					}
					?>
								<tr>
									<td class="tac"><?=$codigo_peca?></td>
									<td class="tac"><?=$descricao_peca?></td>
									<td class="tac"><?=$peca_critica?></td>
									<td class="tac"><?=$qtde?></td>
									<td class="tac"><?=$servico_realizado?></td>
								</tr>
							
				<?php
				}
				?>
						</tbody>
					</table>
				</div>
				<?
				$peca[$i] = "LISTAR";
				$hiddenPeca = "";
			}else{
				$peca[$i] = "";
				$hiddenPeca = "hidden";
			}

			if ($login_fabrica == 152 and $status_auditoria == 6 ) {

				$sql_def = "SELECT tbl_defeito_constatado.descricao ,
									tbl_os_defeito_reclamado_constatado.tempo_reparo,
									tbl_diagnostico.tempo_estimado
							FROM tbl_os_defeito_reclamado_constatado
							INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado
							INNER JOIN tbl_diagnostico ON tbl_diagnostico.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado
							WHERE os = {$os}
							AND tbl_diagnostico.fabrica = {$login_fabrica}
							AND tbl_defeito_constatado.fabrica = {$login_fabrica} ";
				$res_def = pg_query($con, $sql_def);

				$count_def = pg_num_rows($res_def);

				if ($count_def > 0) {
					?>
					<div id="div_defeito_<?=$i?>"  style="display:none">
							<h4 style="margin: 10px;">OS: <?=$os?></h4>
							<table id="resultado_defeito_<?=$i?>" class='table table-striped table-bordered table-hover table-fixed' style="margin: 10px; padding-right: 20px;">
								<thead>
									<tr class='titulo_coluna'>
										<th>Defeito</th>
										<th>Horas Técnicas</th>
										<th>Tempo Estimado</th>
									</tr>
								</thead>
								<tbody>

					<?
					for ($d = 0 ; $d < $count_def; $d++) {

						$descricao	= pg_fetch_result($res_def,$d,'descricao');
						$tempo_reparo	= pg_fetch_result($res_def,$d,'tempo_reparo');
						$tempo_estimado	= pg_fetch_result($res_def,$d,'tempo_estimado');
						?>
									<tr>
										<td class="tac"><?=$descricao?></td>
										<td class="tac"><?=$tempo_reparo?></td>
										<td class="tac"><?=$tempo_estimado?></td>
									</tr>
						<?php
						}
						?>

							</tbody>
						</table>
					</div>
					<?php
						$defeito[$i] = "LISTAR";
						$hiddenDefeito = "";
				}else{
					$defeito[$i] = "";
					$hiddenDefeito = "hidden";
				}

				$sql = "SELECT tbl_tipo_atendimento.tipo_atendimento FROM tbl_tipo_atendimento
					INNER JOIN tbl_os ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND tbl_tipo_atendimento.entrega_tecnica IS TRUE
					WHERE tbl_os.os = {$os}";
				$resEntregaTecnica = pg_query($con,$sql);

				if(pg_num_rows($resEntregaTecnica) > 0){
					$os_entrega_tecnica[] = $os;

					$sql = "SELECT tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_os_produto.capacidade
						FROM tbl_os_produto
							INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
						WHERE tbl_os_produto.os = {$os}";
					$resProduto = pg_query($con,$sql);

					if(pg_num_rows($resProduto) > 0){
					?>
						<div id='div_produto_<?=$i?>' style="display:none">
							<h4 style="margin: 10px;">OS: <?=$os?></h4>
							<table id="resultado_produto_<?=$i?>" class='table table-striped table-bordered table-hover table-fixed' style="margin: 10px; padding-right: 20px;">
								<thead>
									<tr class='titulo_coluna'>
										<th>Referência</th>
										<th>Descrição</th>
										<th>Qtde</th>
									</tr>
								</thead>
								<tbody>
									<?php
										$count_produto = pg_num_rows($resProduto);
										for($k = 0; $k<$count_produto; $k++){
									?>
									<tr>
										<td class="tac"><?=pg_fetch_result($resProduto, $k, "referencia")?></td>
										<td class="tac"><?=pg_fetch_result($resProduto, $k, "descricao")?></td>
										<td class="tac"><?=pg_fetch_result($resProduto, $k, "capacidade")?></td>
									</tr>
									<?php
										}
									?>
								</tbody>
							</table>
						</div>
					<?php
					}
				}
			}
		}
		?></div>
		<table id="resultado_posto" class='table table-striped table-bordered table-large' style="margin: 0 auto;" >
			<thead>
				<tr class='titulo_coluna'>
					<th>OS</th>
					<th>Data</th>

					<?php if($auditoria_aprovado == "1"){ ?>
						<th>Aprovado em</th>
					<?php } ?>

					<th>POSTO</th>
					<th>Produto</th>
					<th>Auditoria</th>
					<th>Observação</th>
					<th>Peça</th>
					<?php if($auditoria_km == $status_auditoria || $auditoria_fabrica == $status_auditoria){ ?>
						<th>Qtde KM</th>
					<? } ?>
					<?php if($auditoria_reincidente == $status_auditoria){ ?>
						<th>Reincidente</th>
					<? } ?>
					<?php if($auditoria_numero_serie == $status_auditoria){ ?>
						<th>Núm. de Série</th>
					<? } ?>
					<?php if($login_fabrica == 152 and $status_auditoria == 6 ){ ?>
						<th>Defeitos e Horas</th>
					<? } ?>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$count_i = pg_num_rows($resConsulta);
				for ($i = 0 ; $i < $count_i; $i++) {
					$auditoria_os               = pg_fetch_result($resConsulta,$i,'auditoria_os');
					$os                         = pg_fetch_result($resConsulta,$i,'os');
					$sua_os                     = pg_fetch_result($resConsulta,$i,'sua_os');
					$data_abertura              = pg_fetch_result($resConsulta,$i,'data_abertura');
					$posto                      = pg_fetch_result($resConsulta,$i,'nome');
					$codigo_posto               = pg_fetch_result($resConsulta,$i,'codigo_posto');
					$paga_mao_obra              = pg_fetch_result($resConsulta,$i,'paga_mao_obra');
					$aprovada                   = pg_fetch_result($resConsulta,$i,'liberada');
					$reprovada                  = pg_fetch_result($resConsulta,$i,'reprovada');
					$auditoria                  = pg_fetch_result($resConsulta,$i,'descricao_auditoria');
					$observacao_auditoria       = pg_fetch_result($resConsulta,$i,'observacao');
					$tipo_atendimento_km_google = pg_fetch_result($resConsulta,$i,'km_google');
					$tipo_os                    = pg_fetch_result($resConsulta,$i,'tipo_os');

					$data_format = explode("-",$data_abertura);
					$data_abertura = $data_format[2]."/".$data_format[1]."/".$data_format[0];

					if(($auditoria_km == $status_auditoria || $auditoria_fabrica == $status_auditoria) && empty($aprovada) && empty($reprovada)){
						$qtde_km = 100 * pg_fetch_result($resConsulta,$i,'qtde_km');
					} else {
						$qtde_km = pg_fetch_result($resConsulta, $i, "qtde_km");
					}
					if($auditoria_reincidente == $status_auditoria){
						$reincidente = pg_fetch_result($resConsulta,$i,'os_reincidente');
					}
					if($auditoria_numero_serie == $status_auditoria){
						$numero_serie = pg_fetch_result($resConsulta,$i,'serie');
					}

					if(ultima_interacao($os) == "fabrica"){
						$color = "#dff0d8";
						$border_color = "#d6e9c6";
					}else if(ultima_interacao($os) == "posto"){
						$color = "#f2dede";
						$border_color = "#eed3d7";
					}else{
						$color = "";
						$border_color = "";
					}
					$produto_referencia = "";
					$produto_descricao  = "";

					if(in_array($os, $os_entrega_tecnica)){
						$produto = '<a id="produto_'.$i.'">Listar Produtos</a>';
					}else{
						$sql = "SELECT tbl_produto.referencia,
								tbl_produto.descricao AS descricao_produto
							FROM tbl_produto
								INNER JOIN tbl_os ON tbl_os.produto = tbl_produto.produto AND tbl_os.os = {$os}";
						$resProduto = pg_query($con,$sql);

						$produto_referencia   = pg_fetch_result($resProduto,0,'referencia');
						$produto_descricao    = pg_fetch_result($resProduto,0,'descricao_produto');
						$produto = $produto_referencia." - ".$produto_descricao;
					}

					?>
					<tr>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>">
							<input type="hidden" id="os_<?=$i?>" value="<?=$os?>">
							<input type="hidden" id="auditoria_os_<?=$i?>" value="<?=$auditoria_os?>">
							<a href='os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></a><br/>
							<button type="button" class="btn btn-primary btn-mini" id="btInteragir_<?=$i?>">Interagir</button>
							<?if($login_fabrica==148 and $tipo_os ==17) echo '<span class="label label-important">OS Fora <br />  de Garantia</span>' ;?>
						</td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" class="tac"><?=$data_abertura?></td>
						<?php
						if($auditoria_aprovado == "1"){
							$dias_aprovado = strtotime(pg_fetch_result($resConsulta,$i,'data_abertura')) - strtotime($aprovada);
							$dias_aprovado = ((int) floor($dias_aprovado / (60 * 60 * 24))*(-1));
							$dias_aprovado .= " dias";
						?>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" class="tac"><?=$dias_aprovado?></td>
						<?php
						}
						?>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$posto?></td>

						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" id="produto_<?=$i?>"><?=$produto?></td>

						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" class="tac"><?=$auditoria?></td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$observacao_auditoria?></td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" id="peca_<?=$i?>" class="tac"><a id="peca_<?=$i?>"><?=$peca[$i]?></a></td>

						<?php 
						if($auditoria_km == $status_auditoria || $auditoria_fabrica == $status_auditoria){
							if($tipo_atendimento_km_google == "t"){ ?>
							<td nowrap style="background-color: <?=$color?>; border-color: <?=$border_color?>" >
								<?php
								if (empty($aprovada) && empty($reprovada)) {
									?>
									<input type="text" style="height: 30px !important; padding: 4px 6px; width: 70px;" id="qtde_km_<?=$i?>" value="<?=$qtde_km?>" />
									<button type="button" class="btn btn-primary btn-small" style="vertical-align: top; margin-top: 2px;" data-loading-text="Salvando..." id="btKM_<?=$i?>" onclick="salvarKM(<?=$i?>,<?=$os?>);">Salvar</button>
									<div id="mensagem_km_<?=$i?>" style="width:25px; height:20x"></div>
									<?php
								} else {
                                    						echo $qtde_km;
								}
								?>
							</td>
						<?php } else if($auditoria_km == $status_auditoria || $auditoria_fabrica == $status_auditoria) { ?>
							<td>&nbsp;</td>
						<?php } 
						}?>

						<?php if($auditoria_reincidente == $status_auditoria){ ?>
							<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" ><?=$reincidente?></td>
						<?php } ?>

						<?php if($auditoria_numero_serie == $status_auditoria){ ?>
							<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$numero_serie?></td>
						<?php } ?>
						<?php
						if($login_fabrica == 152 and $status_auditoria == 6 ){ ?>
							<td style="background-color: <?=$color?>; border-color: <?=$border_color?>" id="defeito_<?=$i?>"><a id="defeito_<?=$i?>"><?=$defeito[$i]?></a></td>
						<?php } ?>

						<td nowrap style="background-color: <?=$color?>; border-color: <?=$border_color?>" id="status_<?=$i?>" >
						<?php if($aprovada == "" && empty($reprovada) && os_excluida($os) == false) { ?>
						<button type="button" class="btn btn-success btn-small" data-loading-text="Salvando..." id="btAprovado_<?=$i?>" onclick="aprovarOS(<?=$i?>,<?=$os?>,<?=$auditoria_os?>,'');">Aprovar</button>
						<? 
						if (in_array($login_fabrica, array(120,201))) { ?>
								<button type="button" class="btn btn-danger btn-small" data-loading-text="Salvando..." id="btReprovado_<?=$i?>">Reprovar</button>
						<?php 	
						} ?>

						<?php }else if($aprovada != ""){ ?>
							<label class="label label-success">Aprovado</label>
						<?php }else if($reprovada != ""){ ?>
							<label class="label label-important">Reprovada</label>
						<?php } ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php }else{ ?>
		<div class="container">
			<div class="alert">
				<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
}
include "rodape.php";
?>
