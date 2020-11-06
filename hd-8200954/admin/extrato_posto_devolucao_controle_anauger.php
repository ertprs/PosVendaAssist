<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "gravar" AND $_POST["liberar_extrato"] == "sim"){
	$extrato = $_POST["extrato"];
	
	if (!empty($extrato)){
		$sql = "SELECT admin_lgr FROM tbl_extrato WHERE extrato = {$extrato} AND fabrica = {$login_fabrica} AND admin_lgr IS NOT NULL";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
			$update = "UPDATE tbl_extrato SET admin_lgr = NULL WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";
			$res_up = pg_query($con, $update);

			if (!pg_last_error()) {
				echo json_encode(
					array(
						"retorno" => utf8_encode("success"),
						"acao" => "liberado"
					)
				);
			}
		}else{
			$update = "UPDATE tbl_extrato SET admin_lgr = {$login_admin} WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";
			$res_up = pg_query($con, $update);

			if (!pg_last_error()) {
				echo json_encode(
					array(
						"retorno" => utf8_encode("success"),
						"acao" => "bloqueado"
					)
				);
			}
		}
		
	}else{
		echo json_encode(array("retorno" => utf8_encode("Extrato não encontrado.")));
	}
	exit;
}

if ($_POST["btn_acao"] == "gravar" AND $_POST["gravar_solicitacao_coleta"] == "sim"){
	$data_solicitacao_coleta = $_POST["data_solicitacao_coleta"];
	$faturamentos = $_POST["faturamentos"];

	$error = false;
	if (empty($data_solicitacao_coleta)) {
		echo json_encode(array("retorno" => utf8_encode("Selecione data de solicitação da coleta")));
		$error = true;
	} else {
		list($di, $mi, $yi) = explode("/", $data_solicitacao_coleta);
		if (!checkdate($mi, $di, $yi)) {
			echo json_encode(array("retorno" => utf8_encode("Data selecionada inválida")));
			$error = true;
		} else {
			$aux_data_solicitacao_coleta = "{$yi}-{$mi}-{$di}";
			$data_atual = date('Y-m-d');

			/*if (strtotime($aux_data_solicitacao_coleta) < strtotime($data_atual)) {
				echo json_encode(array("retorno" => utf8_encode("Data selecionada não pode ser menor que a data atual")));
				$error =  true;
			}*/
		}
	}

	if (empty($faturamentos) OR count($faturamentos) == 0){
		echo json_encode(array("retorno" => utf8_encode("Nenhum extrato selecionado")));
		$error =  true;
	}

	if ($error === false){
		
		$faturamentos_erro = array();
		$faturamentos_success = array();
		foreach ($faturamentos as $key => $value) {
			unset($info_extra);
			
			$sql = "SELECT info_extra FROM tbl_faturamento WHERE faturamento = {$value} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0){
				$info_extra = pg_fetch_result($res, 0, 'info_extra');
				if (!empty($info_extra)){
					$info_extra = json_decode($info_extra, true);
					$info_extra["coleta_solicitada"] = "$aux_data_solicitacao_coleta";
				}else{
					$info_extra["coleta_solicitada"] = "$aux_data_solicitacao_coleta";
				}
			}else{
				$info_extra["coleta_solicitada"] = "$aux_data_solicitacao_coleta";
			}
			
			$info_extra = json_encode($info_extra);
			
			$sql = "UPDATE tbl_faturamento SET info_extra = '$info_extra' WHERE faturamento = {$value} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			if (!pg_last_error()) {
				$faturamentos_success[] = $value;
			} else {
				$faturamentos_erro[] = $value;
			}
		}
		echo json_encode(
			array(
				"retorno" => utf8_encode("success"),
				"faturamentos_success" => $faturamentos_success,
				"faturamentos_erro" => $faturamentos_erro
			)
		);
	}

	exit;
}

if ($_POST["btn_acao"] == "gravar" AND $_POST["gravar_realizacao_coleta"] == "sim"){
	$data_realizacao_coleta = $_POST["data_realizacao_coleta"];
	$faturamentos = $_POST["faturamentos"];

	$error = false;
	if (empty($data_realizacao_coleta)) {
		echo json_encode(array("retorno" => utf8_encode("Selecione data de solicitação da coleta")));
		$error = true;
	} else {
		list($di, $mi, $yi) = explode("/", $data_realizacao_coleta);
		if (!checkdate($mi, $di, $yi)) {
			echo json_encode(array("retorno" => utf8_encode("Data selecionada inválida")));
			$error = true;
		} else {
			$aux_data_realizacao_coleta = "{$yi}-{$mi}-{$di}";
			$data_atual = date('Y-m-d');

			/*if (strtotime($aux_data_realizacao_coleta) < strtotime($data_atual)) {
				echo json_encode(array("retorno" => utf8_encode("Data selecionada não pode ser menor que a data atual")));
				$error =  true;
			}*/
		}
	}

	if (empty($faturamentos) OR count($faturamentos) == 0){
		echo json_encode(array("retorno" => utf8_encode("Nenhum extrato selecionado")));
		$error =  true;
	}

	if ($error === false){
		
		$faturamentos_erro = array();
		$faturamentos_success = array();
		foreach ($faturamentos as $key => $value) {
			unset($info_extra);
			
			$sql = "SELECT info_extra FROM tbl_faturamento WHERE faturamento = {$value} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0){
				$info_extra = pg_fetch_result($res, 0, 'info_extra');
				if (!empty($info_extra)){
					$info_extra = json_decode($info_extra, true);
					$info_extra["coleta_realizada"] = "$aux_data_realizacao_coleta";
				}else{
					$info_extra["coleta_realizada"] = "$aux_data_realizacao_coleta";
				}
			}else{
				$info_extra["coleta_realizada"] = "$aux_data_realizacao_coleta";
			}
			
			$info_extra = json_encode($info_extra);
			
			$sql = "UPDATE tbl_faturamento SET info_extra = '$info_extra' WHERE faturamento = {$value} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			if (!pg_last_error()) {
				$faturamentos_success[] = $value;
			} else {
				$faturamentos_erro[] = $value;
			}
		}
		echo json_encode(
			array(
				"retorno" => utf8_encode("success"),
				"faturamentos_success" => $faturamentos_success,
				"faturamentos_erro" => $faturamentos_erro
			)
		);
	}

	exit;
}

if ($_POST["btn_acao"] == "gravar" AND $_POST["gravar_chegada_pedido"] == "sim"){
	$data_chegada_pedido = $_POST["data_chegada_pedido"];
	$faturamentos = $_POST["faturamentos"];

	$error = false;
	if (empty($data_chegada_pedido)) {
		echo json_encode(array("retorno" => utf8_encode("Selecione data de solicitação da coleta")));
		$error = true;
	} else {
		list($di, $mi, $yi) = explode("/", $data_chegada_pedido);
		if (!checkdate($mi, $di, $yi)) {
			echo json_encode(array("retorno" => utf8_encode("Data selecionada inválida")));
			$error = true;
		} else {
			$aux_data_chegada_pedido = "{$yi}-{$mi}-{$di}";
			$data_atual = date('Y-m-d');

			/*if (strtotime($aux_data_chegada_pedido) < strtotime($data_atual)) {
				echo json_encode(array("retorno" => utf8_encode("Data selecionada não pode ser menor que a data atual")));
				$error =  true;
			}*/
		}
	}

	if (empty($faturamentos) OR count($faturamentos) == 0){
		echo json_encode(array("retorno" => utf8_encode("Nenhum extrato selecionado")));
		$error =  true;
	}

	if ($error === false){
		
		$faturamentos_erro = array();
		$faturamentos_success = array();
		foreach ($faturamentos as $key => $value) {
			unset($info_extra);
			
			$sql = "SELECT info_extra FROM tbl_faturamento WHERE faturamento = {$value} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0){
				$info_extra = pg_fetch_result($res, 0, 'info_extra');
				if (!empty($info_extra)){
					$info_extra = json_decode($info_extra, true);
					$info_extra["chegada_pedido"] = "$aux_data_chegada_pedido";
				}else{
					$info_extra["chegada_pedido"] = "$aux_data_chegada_pedido";
				}
			}else{
				$info_extra["chegada_pedido"] = "$aux_data_chegada_pedido";
			}
			
			$info_extra = json_encode($info_extra);
			
			$sql = "UPDATE tbl_faturamento SET info_extra = '$info_extra' WHERE faturamento = {$value} AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			if (!pg_last_error()) {
				$faturamentos_success[] = $value;
			} else {
				$faturamentos_erro[] = $value;
			}
		}
		echo json_encode(
			array(
				"retorno" => utf8_encode("success"),
				"faturamentos_success" => $faturamentos_success,
				"faturamentos_erro" => $faturamentos_erro
			)
		);
	}

	exit;
}

$coleta_atrasada = $_GET["coleta_atrasada"];
$entrega_atrasada = $_GET["entrega_atrasada"];

if (!empty($coleta_atrasada) AND strlen(trim($coleta_atrasada)) == 3 AND $coleta_atrasada == "sim"){
	$_POST["btn_acao"] = "submit";
}

if (!empty($entrega_atrasada) AND strlen(trim($entrega_atrasada)) == 3 AND $entrega_atrasada == "sim"){
	$_POST["btn_acao"] = "submit";
}

if ($_POST["btn_acao"] == "submit") {

	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$checks 			= $_POST["checks"];
	$nota_devolucao 	= $_POST["nota_devolucao"];

	if ($coleta_atrasada == "sim"){
		$checks[] = "asc";
	}

	if ($entrega_atrasada == "sim"){
		$checks[] = "acp";
	}

	if (empty($checks) OR count($checks) == 0){
		$msg_erro["msg"][]    = "Selecione um dos status da nota de devolução";
		$msg_erro["campos"][] = "checks";
	}
	
	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (!strlen($data_inicial) or !strlen($data_final)) {
		foreach ($checks as $key => $value) {
			if ($value == "c"){
				$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
				$msg_erro["campos"][] = "data";
			}
		}
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (!count($msg_erro["msg"])) {
		
		if (!empty($posto)){
			$cond_posto = " AND f.distribuidor = {$posto} ";
		}

		if (!empty($nota_devolucao)){
			$cond_nota = " AND f.nota_fiscal = $nota_fiscal ";
		}

		$cond_status = array();
		foreach ($checks as $key => $value) {
			if ($value == "asc"){
				if ($coleta_atrasada == "sim"){
					$cond_status[] = " (f.info_extra->'coleta_solicitada' IS not NULL  and f.info_extra->'coleta_realizada' IS NULL AND (CURRENT_DATE - f.emissao) >= 10 ) ";
				}else{
					$cond_status[] = " (f.info_extra->'coleta_solicitada' IS NULL) ";
				}
			}else if ($value == "arc"){
				$cond_status[] = " (f.info_extra->'coleta_solicitada' IS NOT NULL AND f.info_extra->'coleta_realizada' IS NULL) ";
			}else if ($value == "acp"){
				if ($entrega_atrasada == "sim"){
					$cond_status[] = " (f.info_extra->'chegada_pedido' IS NULL AND (CURRENT_DATE - (f.info_extra->>'coleta_realizada')::date >= 20)) ";
				}else{
					$cond_status[] = " (f.info_extra->'coleta_realizada' IS NOT NULL AND f.info_extra->'chegada_pedido' IS NULL) ";
				}
			}else if ($value == "ac"){
				$cond_status[] = " (f.info_extra->'chegada_pedido' IS NOT NULL AND f.info_extra->'conferencia' IS NULL) ";
			}else if ($value == "c"){
				$cond_status[] = " (f.info_extra->'conferencia' IS NOT NULL AND (f.emissao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59')) ";
			}
		}
		
		$status = implode('OR', $cond_status);

		$sql = "
			SELECT 
				f.nota_fiscal, 
				f.faturamento,
				TO_CHAR(f.emissao, 'DD/MM/YYYY') as emissao, 
				pf.codigo_posto, 
				p.nome, 
				f.extrato_devolucao, 
				e.admin_lgr,
				a.nome_completo,
				f.info_extra,
	            CASE WHEN f.info_extra->'coleta_solicitada' IS NULL THEN
	            'Aguardando solicitação da coleta'
	            WHEN f.info_extra->'coleta_realizada' IS NULL THEN
	            'Aguardando realização da coleta'
	            WHEN f.info_extra->'chegada_pedido' IS NULL THEN
	            'Aguardando chegada do pedido'
	            WHEN f.conferencia IS NULL THEN
	            'Aguardando conferência'
	            ELSE
	            'Conferida'
	            END AS status
            FROM tbl_faturamento f
            INNER JOIN tbl_posto_fabrica pf ON f.distribuidor = pf.posto AND pf.fabrica = $login_fabrica
            INNER JOIN tbl_posto p ON p.posto = pf.posto
            INNER JOIN tbl_fabrica fb ON fb.fabrica = f.fabrica AND fb.posto_fabrica = f.posto AND fb.fabrica = $login_fabrica
            INNER JOIN tbl_extrato e ON e.extrato = f.extrato_devolucao AND e.fabrica = $login_fabrica
            LEFT JOIN tbl_admin a ON a.admin = e.admin_lgr AND a.fabrica = $login_fabrica
            WHERE (
            	{$status}
            )
            $cond_posto
            $cond_nota";

        	$resSubmit = pg_query($con, $sql);
	}
}

$layout_menu = "auditoria";
$title = "Controle de Notas de Devolução - LGR";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));

		$("#data_solicitacao_coleta").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

		$("#data_realizacao_coleta").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");

		$("#data_chegada_pedido").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");

		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		// asc -> aguardando solicitação coleta
			$(".select_all_asc_coleta").change(function(){ 
			    $(".asc_coleta").prop('checked', $(this).prop("checked"));
			});
			$('.asc_coleta').change(function(){ 
			    if(false == $(this).prop("checked")){
			        $(".select_all_asc_coleta").prop('checked', false);
			    }
			    if ($('.asc_coleta:checked').length == $('.asc_coleta').length ){
			        $(".select_all_asc_coleta").prop('checked', true);
			    }
			});
		//

		// arc -> aguardando realização coleta
			$(".select_all_arc_coleta").change(function(){ 
			    $(".arc_coleta").prop('checked', $(this).prop("checked"));
			});

			$('.arc_coleta').change(function(){ 
			    if(false == $(this).prop("checked")){
			        $(".select_all_arc_coleta").prop('checked', false);
			    }
			    if ($('.arc_coleta:checked').length == $('.arc_coleta').length ){
			        $(".select_all_arc_coleta").prop('checked', true);
			    }
			});
		//

		// acp -> aguardando chegada do pedido
			$(".select_all_acp_coleta").change(function(){
			    $(".acp_coleta").prop('checked', $(this).prop("checked"));
			});

			$('.acp_coleta').change(function(){ 
			    if(false == $(this).prop("checked")){
			        $(".select_all_acp_coleta").prop('checked', false);
			    }
			    if ($('.acp_coleta:checked').length == $('.acp_coleta').length ){
			        $(".select_all_acp_coleta").prop('checked', true);
			    }
			});
		
		$(".anexo").click(function(){
			var faturamento = $(this).data('faturamento');
			Shadowbox.open({
				content:"anexos_lgr_anauger.php?faturamento="+faturamento,
	            player: "iframe",
	            width:  900,
	            height: 400
	        });
		});

	
		/*$(".btn-alterar").click(function(){
			var pos = $(this).data("posicao");
			$(this).hide();
			$(".nota_fiscal_"+pos).prop('readonly', false);
			$(".gravar_"+pos).show();
			alert("teste "+pos);
		});

		$(".btn_gravar").click(function(){
			alert("gravar");
			var faturamento = $(this).data("faturamento");
		});*/


	});

	$(document).on("click",".post_gravar_asc",function() {
	    var btn = $(this);
	    var text = $(this).text();
	    var tabela = $(this).parents("#asc");
	    var data_solicitacao = $(tabela).find("#data_solicitacao_coleta").val();
	    var faturamentos = [];
	    $(tabela).find('.asc_coleta:checked').each(function(){
	    	if ($(this).val() != "" && $(this).val() != undefined){
				faturamentos.push($(this).val());
			}
		});

	    if (faturamentos.length == 0){
	    	alert("Selecione um extrato para continuar");
	    	return false;
	    }

	    if (data_solicitacao.length < 10){
	    	alert("Preencha a data solicitação coleta");
	    	return false;
	    }
	    
	    if (confirm('Deseja gravar para todos os extratos selecionados ?')) {
			$(btn).prop({disabled: true}).text("Gravando...");
	        $(tabela).find("#data_solicitacao_coleta").prop({disabled: true});
	        $.ajax({
	            method: "POST",
	            url: "<?=$_SERVER['PHP_SELF']?>",
	            data: { btn_acao: 'gravar', gravar_solicitacao_coleta: "sim", data_solicitacao_coleta: data_solicitacao, faturamentos: faturamentos},
	            timeout: 8000
	        }).fail(function(){
	        	alert("Não foi possível excluir o registro, tempo limite esgotado!");
	        }).done(function(data) {
	            data = JSON.parse(data);
	            $(btn).prop({disabled: false}).text(text);
	        	$(tabela).find("#data_solicitacao_coleta").prop({disabled: false});
	   			if (data.retorno == "success") {

	   				if (data.faturamentos_success.length > 0){
	   					$(data.faturamentos_success).each(function(i,x){
	   						$("#"+x).css("background-color", "#9ee09e");
	   						$("#"+x).find(".asc_coleta").prop('checked', false).hide();
	   						$("#"+x).find(".asc_coleta").val("");
	   					});
	   				}
	   				if (data.faturamentos_erro.length > 0){
	   					$(data.faturamentos_erro).each(function(i,x){
	   						$("#"+x).css("background-color", "#ffb0b0");
	   					});
	   				}
	   				location.reload();
	   			}else{
	                $(btn).prop({disabled: false}).text(text);
				}
	        });
	    }else{
	    	return false;
	    }
	});

	$(document).on("click",".post_gravar_arc",function() {
	    var btn = $(this);
	    var text = $(this).text();
	    var tabela = $(this).parents("#arc");
	    var data_realizacao_coleta = $(tabela).find("#data_realizacao_coleta").val();
	    var faturamentos = [];
	    $(tabela).find('.arc_coleta:checked').each(function(){
	    	if ($(this).val() != "" && $(this).val() != undefined){
				faturamentos.push($(this).val());
			}
		});

	    if (faturamentos.length == 0){
	    	alert("Selecione um extrato para continuar");
	    	return false;
	    }

	    if (data_realizacao_coleta.length < 10){
	    	alert("Preencha a data da realização da coleta");
	    	return false;
	    }
	    
	    if (confirm('Deseja gravar para todos os extratos selecionados ?')) {
			$(btn).prop({disabled: true}).text("Gravando...");
	        $(tabela).find("#data_realizacao_coleta").prop({disabled: true});
	        $.ajax({
	            method: "POST",
	            url: "<?=$_SERVER['PHP_SELF']?>",
	            data: { btn_acao: 'gravar', gravar_realizacao_coleta: "sim", data_realizacao_coleta: data_realizacao_coleta, faturamentos: faturamentos},
	            timeout: 8000
	        }).fail(function(){
	        	alert("Não foi possível excluir o registro, tempo limite esgotado!");
	        }).done(function(data) {
	            data = JSON.parse(data);
	            $(btn).prop({disabled: false}).text(text);
	        	$(tabela).find("#data_realizacao_coleta").prop({disabled: false});
	   			if (data.retorno == "success") {

	   				if (data.faturamentos_success.length > 0){
	   					$(data.faturamentos_success).each(function(i,x){
	   						$("#"+x).css("background-color", "#9ee09e");
	   						$("#"+x).find(".arc_coleta").prop('checked', false).hide();
	   						$("#"+x).find(".arc_coleta").val("");
	   					});
	   				}
	   				if (data.faturamentos_erro.length > 0){
	   					$(data.faturamentos_erro).each(function(i,x){
	   						$("#"+x).css("background-color", "#ffb0b0");
	   					});
	   				}
	   				location.reload();
	   			}else{
	                $(btn).prop({disabled: false}).text(text);
				}
	        });
	    }else{
	    	return false;
	    }
	});
	
	$(document).on("click",".post_gravar_acp",function() {
	    var btn = $(this);
	    var text = $(this).text();
	    var tabela = $(this).parents("#acp");
	    var data_chegada_pedido = $(tabela).find("#data_chegada_pedido").val();
	    var faturamentos = [];
	    $(tabela).find('.acp_coleta:checked').each(function(){
	    	if ($(this).val() != "" && $(this).val() != undefined){
				faturamentos.push($(this).val());
			}
		});

	    if (faturamentos.length == 0){
	    	alert("Selecione um extrato para continuar");
	    	return false;
	    }

	    if (data_chegada_pedido.length < 10){
	    	alert("Preencha a de chegada do pedido");
	    	return false;
	    }
	    
	    if (confirm('Deseja gravar para todos os extratos selecionados ?')) {
			$(btn).prop({disabled: true}).text("Gravando...");
	        $(tabela).find("#data_chegada_pedido").prop({disabled: true});
	        $.ajax({
	            method: "POST",
	            url: "<?=$_SERVER['PHP_SELF']?>",
	            data: { btn_acao: 'gravar', gravar_chegada_pedido: "sim", data_chegada_pedido: data_chegada_pedido, faturamentos: faturamentos},
	            timeout: 8000
	        }).fail(function(){
	        	alert("Não foi possível excluir o registro, tempo limite esgotado!");
	        }).done(function(data) {
	            data = JSON.parse(data);
	            $(btn).prop({disabled: false}).text(text);
	        	$(tabela).find("#data_chegada_pedido").prop({disabled: false});
	   			if (data.retorno == "success") {

	   				if (data.faturamentos_success.length > 0){
	   					$(data.faturamentos_success).each(function(i,x){
	   						$("#"+x).css("background-color", "#9ee09e");
	   						$("#"+x).find(".acp_coleta").prop('checked', false).hide();
	   						$("#"+x).find(".acp_coleta").val("");
	   					});
	   				}
	   				if (data.faturamentos_erro.length > 0){
	   					$(data.faturamentos_erro).each(function(i,x){
	   						$("#"+x).css("background-color", "#ffb0b0");
	   					});
	   				}
					location.reload();	   				
	   			}else{
	                $(btn).prop({disabled: false}).text(text);
				}
	        });
	    }else{
	    	return false;
	    }
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

    function verNota(nota_fiscal){
		Shadowbox.open({
			content:"extrato_posto_devolucao_controle.php?pop_up=sim&nota_fiscal=" + nota_fiscal,
            player: "iframe",
            width:  680,
            height: 500
        });
	}

	function btn_acoes(extrato) {
		if (extrato != "" && extrato != undefined){
			if (confirm('Deseja continuar ?')) {
				$.ajax({
		            method: "POST",
		            url: "<?=$_SERVER['PHP_SELF']?>",
		            data: { btn_acao: 'gravar', liberar_extrato: "sim", extrato: extrato},
		            timeout: 8000
		        }).fail(function(){
		        	alert("Não foi possível liberar o extrato, tempo limite esgotado!");
		        }).done(function(data) {
		            data = JSON.parse(data);
		            console.log(data);
		            if (data.retorno == "success") {
		   				if (data.acao == "bloqueado"){
		   					$("#btnLiberar"+extrato).hide();
		   					$("#btnBloquear"+extrato).show();
		   				}else if (data.acao == "liberado"){
		   					$("#btnLiberar"+extrato).show();
		   					$("#btnBloquear"+extrato).hide();
		   				}		
		   			}else{
		                
					}
		        });
		    }else{
		    	return false;
		    }
		}
	}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios somente para notas já conferidas</b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
			
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("nota_devolucao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='nota_devolucao'>Nota Devolução</label>
					<div class='controls controls-row'>
						<div class='span8'>
							<input type="text" name="nota_devolucao" id="nota_devolucao" class='span12' value="<? echo $nota_devolucao ?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span6'></div>
		</div>

		<br/>
		<div class='row-fluid' style="min-height: initial !important;">
			<div class='span2'></div>
			<div class='span8 tac'>
				<span class="label label-info">Status</span>
				<h5 class='asteristico' style="margin-left: -60px !important; float: none; margin-top: -18px !important">*</h5>
			</div>
			<div class="span2"></div>
		</div>

		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span8">
        		<div class="control-group <?=(in_array("checks", $msg_erro["campos"])) ? "error" : ""?> ">
                	<div class="controls controls-row">
	                    <div class="span  ">
	                        <label class="checkbox" style="width: 220px;"><input type="checkbox" name="checks[]" value="asc" <?=(in_array("asc", $checks)) ? "checked":""?> > Aguardando solicitação da coleta </label>
	                        <label class="checkbox" style="width: 220px;"><input type="checkbox" name="checks[]" value="arc" <?=(in_array("arc", $checks)) ? "checked":""?>> Aguardando realização da coleta</label>
	                        <label class="checkbox" style="width: 220px;"><input type="checkbox" name="checks[]" value="acp" <?=(in_array("acp", $checks)) ? "checked":""?>> Aguardando chegada do pedido</label>
	                        <label class="checkbox" style="width: 220px;"><input type="checkbox" name="checks[]" value="ac" <?=(in_array("ac", $checks)) ? "checked":""?>> Aguardando conferência</label>
	                        <label class="checkbox" style="width: 220px;"><input type="checkbox" name="checks[]" value="c" <?=(in_array("c", $checks)) ? "checked":""?>> Conferidas</label>
	                    </div>
                	</div>
            	</div>
            </div>
            <div class="span2"></div>
        </div>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>

<?php
if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
			
		$result = pg_fetch_all($resSubmit);
		
		$array_group = array();

		foreach ($result as $key => $value) {
	
			$array_group[$value['status']][] = $value;

		}
		
		foreach ($array_group as $key => $value) { 
			switch ($key) {
				case 'Aguardando solicitação da coleta':
					$id = "asc";
					break;
				case 'Aguardando realização da coleta':
					$id = "arc";
					break;
				case 'Aguardando chegada do pedido':
					$id = "acp";
					break;
				case 'Aguardando conferência':
					$id = "ac";
					break;
				case 'Conferidas':
					$id= "c";
					break;
			}
		?>
		<div class="container-fluid">
			<div class="row">
				<div style="float: right; margin-right: 10px;"><span style="background: #9ee09e; padding: 0px 7px 0px 10px;">&nbsp; </span> Cadastrado com sucesso</div>
				<div style="float: right; margin-right: 50px;"><span style="background: #ffb0b0; padding: 0px 7px 0px 10px;">&nbsp; </span> Não cadastrado</div>
			</div>
			<table id="<?=$id?>" class='table table-bordered table-fixed' >
				<thead>
				<tr>
					<th colspan="15" class='titulo_tabela'><?=$key?></th>
				</tr>
				<tr>
					<?php if ($key == "Aguardando solicitação da coleta"){ ?>
					<th class='titulo_coluna' ><input type="checkbox" class="select_all_asc_coleta" style="margin-bottom: 7px;"> Selecionar todos</th>
					<?php }else if ($key == "Aguardando realização da coleta"){ ?>
					<th class='titulo_coluna' ><input type="checkbox" class="select_all_arc_coleta" style="margin-bottom: 7px;"> Selecionar todos</th>
					<?php }else if ($key == "Aguardando chegada do pedido"){ ?>
					<th class='titulo_coluna' ><input type="checkbox" class="select_all_acp_coleta" style="margin-bottom: 7px;"> Selecionar todos</th>
					<?php } ?>
					<th class='titulo_coluna' >Cod.Posto</th>
					<th class='titulo_coluna'>Nome Posto</th>
					<th class='titulo_coluna'>Extrato Devolução</th>
					<th class='titulo_coluna'>Nota Fiscal</th>
					<th class='titulo_coluna'>Emissão</th>
					<th class='titulo_coluna'>Status</th>
					<th class='titulo_coluna'>Admin</th>
					<?php if ($login_fabrica == 177 && in_array("c", $checks)) {  ?>
						<th class='titulo_coluna'>Coleta Realizada</th>
						<th class='titulo_coluna'>Coleta Solicitada</th>
						<th class='titulo_coluna'>Chegada Pedido</th>
						<th class='titulo_coluna'>Data de Conferência</th>
					<?php } ?>
					<?php if ($key == "Aguardando conferência"){ ?>
					<th class='titulo_coluna' colspan="2">Ações</th>
					<?php }else if ($key == "Aguardando solicitação da coleta" OR $key == "Aguardando realização da coleta" OR $key == "Aguardando chegada do pedido"){ ?>
					<th class='titulo_coluna' colspan="2">Ações</th>
					<?php } if($key == "Conferida"){ ?>
						<th class='titulo_coluna'>Ações</th>
					<?php } ?>
				</tr>
				</thead>		
				<tbody>

		<?php 
		$pos = 0; 
		foreach ($value as $key_p => $value_p) { ?>
					<tr id="<?=$value_p["faturamento"]?>">
						<?php if ($key == "Aguardando solicitação da coleta"){ ?>
						<td class="tac"><input type="checkbox" class='asc_coleta' name="asc_coleta[]" value="<?=$value_p["faturamento"]?>"></td>
						<?php }else if ($key == "Aguardando realização da coleta"){?>
						<td class="tac"><input type="checkbox" class='arc_coleta' name="arc_coleta[]" value="<?=$value_p["faturamento"]?>"></td>
						<?php }else if ($key == "Aguardando chegada do pedido"){ ?>
						<td class="tac"><input type="checkbox" class='acp_coleta' name="acp_coleta[]" value="<?=$value_p["faturamento"]?>"></td>
						<?php } ?>
						<td><?=$value_p["codigo_posto"]?></td>
						<td><?=$value_p["nome"]?></td>
						<td>	
							<?=$value_p["extrato_devolucao"]?>
						</td>
						<td>
							<?=$value_p["nota_fiscal"]?>
							<!-- <input type='text' name='nota_fiscal' class="nota_fiscal_<?=$pos?>" style='width: 70px;' value="" readonly="true" >
							<button type="button" class="btn btn-small btn-primary btn-alterar" data-posicao="<?=$pos?>">Alterar</button>
							<button type="button" style='display: none;' class="btn btn-small btn-primary btn-gravar gravar_<?=$pos?>" data-faturamento='<?=$value_p["faturamento"]?>' >Gravar</button> -->
						</td>
						<td><?=$value_p["emissao"]?></td>
						<td><?=$value_p["status"]?></td>
						<td><?=$value_p["nome_completo"]?></td>
						<?php if ($login_fabrica == 177 && in_array("c", $checks))  {  
							$infoExtra = json_decode($value_p['info_extra'], true);
						?>
							<td><?= mostra_data($infoExtra['coleta_realizada']); ?></td>
							<td><?= mostra_data($infoExtra['coleta_solicitada']); ?></td>
							<td><?= mostra_data($infoExtra['chegada_pedido']); ?></td>
							<td><?= mostra_data($infoExtra['data_conferencia']); ?></td>
						<?php } ?>
						<?php if ($key == "Aguardando conferência"){ ?>
						<td class='tac'>
							<button class='btn btn-small btn-primary' onclick="verNota('<?=$value_p["faturamento"]?>')">Conferir</button>
						</td>
						<?php }else if ($key == "Aguardando solicitação da coleta" OR $key == "Aguardando realização da coleta" OR $key == "Aguardando chegada do pedido"){ ?>
						<td class="tac">
							<?php 
								if (!empty($value_p["admin_lgr"])){
									$disp_liberar = "style='display:none;'";
									$disp_bloquear = "";
								}else{
									$disp_liberar = "";
									$disp_bloquear = "style='display:none'";
								}
							?>
							<button <?=$disp_liberar?> class='btn btn-small btn-primary' id='btnLiberar<?=$value_p["extrato_devolucao"]?>' onclick="btn_acoes('<?=$value_p["extrato_devolucao"]?>')" >Liberar provisoriamente</button>
							<button <?=$disp_bloquear?> class='btn btn-small btn-danger' id='btnBloquear<?=$value_p["extrato_devolucao"]?>' onclick="btn_acoes('<?=$value_p["extrato_devolucao"]?>')">Bloquear provisoriamente</button>
						</td>
						<?php } ?>
						<td>
							<button type="button" class="btn btn-small btn-primary anexo"  data-faturamento="<?=$value_p["faturamento"]?>"> Anexos </button>
						</td>
					</tr>
				
		<?php $pos++; } ?>
				</tbody>
				<tfoot>
					<tr>
						<td class="titulo_coluna tac" colspan="15">
						<?php if ($key == "Aguardando solicitação da coleta"){ ?>
							<label class='titulo_coluna' style="display: inline;">Data solicitação coleta</label>
							&nbsp;
							<input type="text" name="data_solicitacao_coleta" id="data_solicitacao_coleta" size="12" maxlength="10" style="width: 75px; margin-bottom: 1px">
							&nbsp;&nbsp;&nbsp;
							<button class='btn btn-primary post_gravar_asc'> Gravar </button>
						<?php }else if ($key == "Aguardando realização da coleta"){ ?>
							<label class='titulo_coluna' style="display: inline;">Data da realização da coleta</label>
							&nbsp;
							<input type="text" name="data_realizacao_coleta" id="data_realizacao_coleta" size="12" maxlength="10" style="width: 75px; margin-bottom: 1px">
							&nbsp;&nbsp;&nbsp;
							<button class='btn btn-primary post_gravar_arc'> Gravar </button>
						<?php }else if ($key == "Aguardando chegada do pedido"){ ?>
							<label class='titulo_coluna' style="display: inline;">Data de chegada do pedido</label>
							&nbsp;
							<input type="text" name="data_chegada_pedido" id="data_chegada_pedido" size="12" maxlength="10" style="width: 75px; margin-bottom: 1px">
							&nbsp;&nbsp;&nbsp;
							<button class='btn btn-primary post_gravar_acp'> Gravar </button>
						<?php } ?>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
		<br/>
		<?php } ?>
		<?php
		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
		}
	}
include 'rodape.php';?>
