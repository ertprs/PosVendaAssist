<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include_once __DIR__.'/funcoes.php';

if ($_POST['excluir_pedido'] == "true" AND !empty($_POST['pedido'])){

	$pedido = $_POST["pedido"];

	$sql = "
		SELECT 
			pedido,
			tipo_pedido,
			exportado
		FROM tbl_pedido
		WHERE tbl_pedido.fabrica = {$login_fabrica}
		AND tbl_pedido.posto = {$login_posto}
		AND tbl_pedido.pedido = {$pedido}
		AND tbl_pedido.exportado IS NULL";
	$res = pg_query($con,$sql);

	if (pg_numrows($res) == 1) {
		$tipo_pedido = trim(pg_result($res,0,tipo_pedido));
		$exportado   = trim(pg_result($res,0, exportado));
		
		if (strlen($exportado)==0){
			$res = @pg_query($con,"BEGIN TRANSACTION");

			$sql = "UPDATE tbl_pedido_item
					SET qtde_cancelada = tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada
					WHERE pedido = {$pedido}";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			$sqlx = "
				SELECT 
					tbl_pedido.posto,
					tbl_pedido.fabrica,
					tbl_pedido_item.pedido,
					tbl_pedido_item.qtde_cancelada,
					tbl_pedido_item.pedido_item,
					tbl_peca.peca,
					tbl_os.os
				FROM tbl_pedido
				JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
				JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
				LEFT JOIN tbl_os_item ON tbl_os_item.peca = tbl_pedido_item.peca AND tbl_os_item.pedido = tbl_pedido.pedido
				LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
				WHERE tbl_pedido_item.pedido = {$pedido}";
			$resx = pg_query($con,$sqlx);

			for ($i = 0 ; $i < pg_numrows ($resx) ; $i++) {
				$posto       = pg_fetch_result($resx, $i, 'posto');
				$fabrica     = pg_fetch_result($resx, $i, 'fabrica');
				$pedido      = pg_fetch_result($resx, $i, 'pedido');
				$pedido_item = pg_fetch_result($resx, $i, 'pedido_item');
				$qtde        = pg_fetch_result($resx, $i, 'qtde_cancelada');
				$peca        = pg_fetch_result($resx, $i, 'peca');
				$os          = pg_fetch_result($resx, $i, 'os');

				if(strlen($os)== 0) $os = "null";

				$sql = "INSERT INTO tbl_pedido_cancelado(
							pedido  ,
							posto   ,
							fabrica ,
							os      ,
							peca    ,
							qtde    ,
							motivo  ,
							data    ,
							pedido_item
							)values(
							'$pedido',
							'$posto',
							'$fabrica',
							$os,
							'$peca',
							'$qtde',
							'Pedido cancelado pelo posto em ('||current_timestamp||')',
							current_date,
							$pedido_item
						)";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}

			$sql = "UPDATE tbl_pedido SET status_pedido = 14
				WHERE pedido = {$pedido};
				SELECT fn_atualiza_status_pedido($login_fabrica,$pedido)";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);

			if ($login_fabrica == 183){
				$sql = "DELETE FROM tbl_nf_produto_pedido_item WHERE pedido_item IN (SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido)";
				$res = pg_query($con, $sql);
			}

			if (strlen ($msg_erro) == 0) {
				$res = @pg_query($con,"COMMIT TRANSACTION");
			}else{
				$res = @pg_query($con,"ROLLBACK TRANSACTION");
			}
		}else{
			$sql =	"UPDATE tbl_pedido
					    SET fabrica = 0
					 WHERE tbl_pedido.pedido  = {$pedido}
                       AND tbl_pedido.posto   = {$login_posto}
                       AND tbl_pedido.fabrica = {$login_fabrica}
                       AND tbl_pedido.exportado IS NULL;";
			$res = @pg_query($con,$sql);
		}
		$msg_erro = pg_last_error($con);
		
		if (strlen($msg_erro) == 0) {
			exit(json_encode(array("ok" => "success", "msg" => "Pedido excluido com sucesso")));
		}else{
			exit(json_encode(array("ok" => "error", "msg" => "Erro ao excluir pedido")));
		}
	}
	exit;
}

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$peca_referencia    = $_POST['peca_referencia'];
	$peca_descricao     = $_POST['peca_descricao'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$numero_pedido 		= $_POST['numero_pedido'];

	if (empty($numero_pedido) AND (!strlen($data_inicial) or !strlen($data_final))) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		if (strlen(trim($data_inicial)) > 0 AND strlen(trim($data_final)) > 0){
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

				$cond_data = " AND tbl_pedido.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
			}
		}
	}

	if (strlen(trim($peca_referencia)) > 0 or strlen(trim($peca_descricao)) > 0){
		$sql = "
			SELECT peca
			FROM tbl_peca
			WHERE fabrica = {$login_fabrica}
			AND (
			    (UPPER(referencia) = UPPER('{$peca_referencia}'))
	        OR
        		(UPPER(descricao) = UPPER('{$peca_descricao}'))
    		)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Peça não encontrada";
			$msg_erro["campos"][] = "peca";
		} else {
			$peca = pg_fetch_result($res, 0, "peca");
			$cond_peca = " AND tbl_peca.peca = $peca ";
		}
	}

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "
			SELECT tbl_posto_fabrica.posto
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
			$cond_posto = " AND tbl_pedido.filial_posto = {$posto} ";
		}
	}

	if (strlen(trim($numero_pedido)) > 0){
		$cond_pedido = " AND tbl_pedido.pedido = {$numero_pedido} ";
	}

	if (!count($msg_erro["msg"])) {
		
		$sql = "
			SELECT 
				tbl_pedido.pedido,
				tbl_status_pedido.status_pedido AS id_status,
				tbl_status_pedido.descricao AS xstatus_pedido,
				tbl_pedido.data::date AS data,
				TO_CHAR(tbl_pedido.finalizado, 'DD/MM/YYYY') AS finalizado,
				TO_CHAR(tbl_pedido.aprovado_cliente, 'DD/MM/YYYY') AS aprovado_cliente,
				TO_CHAR(tbl_pedido.recebido_posto, 'DD/MM/YYYY') AS recebido_posto,
				tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
				tbl_linha.nome AS linha_descricao,
				tbl_pedido.exportado,
				tbl_pedido.distribuidor,
				tbl_pedido.total,
				tbl_pedido.pedido_sedex,
				tbl_pedido.pedido_loja_virtual,
				tbl_pedido.pedido_cliente,
				NULL AS pedido_status,
				tbl_pedido.obs,
				tbl_pedido.seu_pedido,
				tbl_pedido.tabela,
				tbl_classe_pedido.classe,
				tbl_pedido.permite_alteracao, 
				SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco * ((tbl_peca.ipi / 100)+1))::numeric AS preco_ipi , 
				tbl_posto_fabrica.nome_fantasia AS filial_nome_fantasia,
				tbl_posto_fabrica.codigo_posto
			FROM tbl_pedido
			JOIN tbl_tipo_pedido USING (tipo_pedido)
			JOIN tbl_pedido_item USING (pedido)
			JOIN tbl_peca USING (peca)
			LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_pedido.linha
			LEFT JOIN tbl_classe_pedido ON tbl_classe_pedido.classe_pedido = tbl_pedido.classe_pedido
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.filial_posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
			WHERE tbl_pedido.posto = {$login_posto} AND tbl_pedido.finalizado is not null
			AND tbl_pedido.fabrica = {$login_fabrica}
			$cond_pedido
			$cond_posto
			$cond_peca
			$cond_data
			GROUP BY tbl_pedido.pedido,
			tbl_pedido.pedido_blackedecker,
			tbl_pedido.data,
			tbl_pedido.aprovado_cliente,
			tbl_pedido.finalizado,
			tbl_pedido.recebido_posto,
			tbl_pedido.total,
			tbl_tipo_pedido.descricao,
			tbl_status_pedido.status_pedido,
			tbl_status_pedido.descricao,
			tbl_pedido.exportado,
			tbl_pedido.distribuidor,
			tbl_pedido.pedido_sedex,
			tbl_linha.nome,
			tbl_pedido.valores_adicionais ,
			tbl_pedido.pedido_loja_virtual,
			tbl_pedido.pedido_cliente,
			tbl_pedido.obs,
			tbl_pedido.seu_pedido,
			tbl_pedido.tabela,
			tbl_pedido.tabela,
			tbl_pedido.permite_alteracao,
			tbl_classe_pedido.classe
			, tbl_posto_fabrica.posto_fabrica
			ORDER BY tbl_pedido.data DESC";
		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_pedidos-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='9' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE PEDIDOS
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido SAP</th>";
						if (in_array($login_tipo_posto_codigo, array("Rep", "Rev"))){
							$thead .="<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido Representante</th>
								      <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente</th>";
						}
						$thead .="<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Finalizado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo Pedido</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total</th>
						</tr>
					</thead>
					<tbody>
			";
			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				$pedido                    = trim(pg_fetch_result($resSubmit, $i, 'pedido'));
                $seu_pedido                = trim(pg_fetch_result($resSubmit, $i, 'seu_pedido'));
                $tabela                    = trim(pg_fetch_result($resSubmit, $i, 'tabela'));
                $pedido_blackedecker       = trim(pg_fetch_result($resSubmit, $i, 'pedido_blackedecker'));
                $data                      = trim(pg_fetch_result($resSubmit, $i, 'data'));
                $aprovado_cliente          = trim(pg_fetch_result($resSubmit, $i, 'aprovado_cliente'));
                $finalizado                = trim(pg_fetch_result($resSubmit, $i, 'finalizado'));
                $pedido_sedex              = trim(pg_fetch_result($resSubmit, $i, 'pedido_sedex'));
                $pedido_loja_virtual       = trim(pg_fetch_result($resSubmit, $i, 'pedido_loja_virtual'));
                $id_status                 = trim(pg_fetch_result($resSubmit, $i, 'id_status'));
                $pedido_status             = trim(pg_fetch_result($resSubmit, $i, 'pedido_status'));
                $status_pedido             = trim(pg_fetch_result($resSubmit, $i, 'xstatus_pedido'));
                $tipo_pedido_descricao     = trim(pg_fetch_result($resSubmit, $i, 'tipo_pedido_descricao'));
                $linha                     = trim(pg_fetch_result($resSubmit, $i, 'linha_descricao'));
                $exportado                 = trim(pg_fetch_result($resSubmit, $i, 'exportado'));
                $distribuidor              = trim(pg_fetch_result($resSubmit, $i, 'distribuidor'));
                $recebido_posto            = trim(pg_fetch_result($resSubmit, $i, 'recebido_posto'));
                $obs                       = trim(pg_fetch_result($resSubmit, $i, 'obs'));
                $classe                    = trim(pg_fetch_result($resSubmit, $i, 'classe'));
                $seu_pedido                = trim(pg_fetch_result($resSubmit, $i, 'seu_pedido'));
                $permite_alteracao         = trim(pg_fetch_result($resSubmit, $i, 'permite_alteracao'));
                $pedido_cliente_2          = trim(pg_fetch_result($resSubmit, $i, 'pedido_cliente'));
                $marca                     = trim(pg_fetch_result($resSubmit, $i, 'marca'));
                $desconto                  = trim(pg_fetch_result($resSubmit, $i, 'desconto'));
                $pedido_valores_adicionais = trim(pg_fetch_result($resSubmit, $i,'valores_adicionais'));
				$total                     = trim(pg_fetch_result($resSubmit, $i, 'total'));
				$filial 				   = trim(pg_fetch_result($resSubmit, $i, "filial_nome_fantasia"));
				$codigo_posto			   = trim(pg_fetch_result($resSubmit, $i, "codigo_posto"));
				$obs 					   = json_decode($obs, true);
				
				$xmostar_data = mostra_data($data);
				$xtotal = number_format($total,2,",",".");

				$body .="
						<tr>
							<td nowrap align='center' valign='top'>{$pedido}</td>
							<td nowrap align='center' valign='top'>{$pedido_cliente_2}</td>";

						if (in_array($login_tipo_posto_codigo, array("Rep", "Rev"))){
						    $body .="<td nowrap align='center' valign='top'>$seu_pedido</td>
							    <td nowrap align='center' valign='top'>$codigo_posto - $filial</td>";
			            }

				$body .="	<td nowrap align='center' valign='top'>{$xmostar_data}</td>
							<td nowrap align='center' valign='top'>{$finalizado}</td>
							<td nowrap align='center' valign='top'>{$status_pedido}</td>
							<td nowrap align='center' valign='top'>{$tipo_pedido_descricao}</td>
							<td nowrap align='left' valign='top'>{$xtotal}</td>
						</tr>";
			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='9' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
						</tr>
					</tbody>
				</table>
			");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}
		exit;
	}
}

$layout_menu = ($areaAdmin) ? 'callcenter' : 'pedido';

$title = traduz("relacao.de.pedido.de.pecas",$con,$cook_idioma);

if ($areaAdmin === true) {
    include __DIR__.'/admin/cabecalho_new.php';
} else {
    include __DIR__.'/cabecalho_new.php';
}

$plugins = array(
	"datepicker",
	"shadowbox",
	"maskedinput",
	"alphanumeric",
	"ajaxform",
	"fancyzoom",
	"price_format",
	"tooltip",
	"select2",
	"leaflet"
);
include __DIR__.'/admin/plugin_loader.php';
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		var loadingCount = 0;

    		var zindexSelector = '.ui-widget';

    		var subZIndex = function(){
    			$(zindexSelector).each(function(){
    				var oldZindex = $(this).css('z-index');
    				$(this).attr('old-z-index',oldZindex);
    				$(this).css('z-index',1);
    			});
    		};

    		var returnZIndex = function(){
    			$('[old-z-index]').each(function(){
    				var oldZindex = $(this).attr('old-z-index');
    				$(this).removeAttr('old-z-index');
    				$(this).css('z-index',oldZindex);
    			});
    		};


    		var funcLoading = function(display){

	    		switch (display) {
	    			case true:
	    			case "show":
	    				loadingCount += 1;
	    				if(loadingCount != 1)
	    					return;
	    				subZIndex();
	    				$("#loading").show();
	    				$("#loading-block").show();
						$("#loading_action").val("t");
	    				break;
	    			case false:
	    			case "hide":
	    				if(loadingCount >0)
	    					 loadingCount-= 1;
	    				if(loadingCount != 0)
	    					return;
	    				$("#loading").hide();
						$("#loading_action").val("f");
						$("#loading-block").hide();
						returnZIndex();
	    				break;
	    		}
    		};

    		window.loading = funcLoading;

		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();
		$("#numero_pedido").numeric();
		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	

		$(document).on("click", "span[rel=lupa_posto]", function() {
	        var parametros_lupa_produto = ["parametro", "page"];
	        $.lupa($(this), parametros_lupa_produto);
	    });

		$(document).on("click", ".btn_excluir", function(){
			let pedido = $(this).data("pedido");
			let btn = $(this);

			$.ajax({
				url: "pedido_relacao_new.php",
				type: "post",
				dataType: "JSON",
				data:{"excluir_pedido": true, "pedido": pedido},
				beforeSend: function(){
					$(btn).prop({ disabled: true }).text("Excluindo...");
				},
				async: false,
				timeout: 10000
			}).fail(function(res){
				alert("Ocorreu um erro ao excluir o pedido");
				$(btn).prop({ disabled: false }).text("Excluir");
			}).done(function(res){
				if (res.ok == "success") {
					$("#tr_"+pedido).remove();
		            alert(res.msg);
		        } else {
	        	    alert(res.msg);
		        }
		        $(btn).prop({ disabled: false }).text("Excluir");
		    });
		});

		$("#gerar_excel, .gerar_excel").click(function () {
			if (ajaxAction()) {

				if ($(this).hasClass("gerar_excel")) {
					var json = $.parseJSON($(this).find(".jsonPOST").val());
				} else {
					var json = $.parseJSON($("#jsonPOST").val());
				}
				
				json["gerar_excel"] = true;

    			$.ajax({
    				url: "<?=$_SERVER['PHP_SELF']?>",
    				type: "POST",
    				data: json,
    				beforeSend: function () {
    					loading("show");
    				},
    				complete: function (data) {
    					window.open(data.responseText, "_blank");
    					loading("hide");
    				}
    			});
			}
		});
	});

	function ajaxAction () {
		if ($("#loading_action").val() == "t") {
			alert("Espere o processo atual terminar!");
			return false;
		} else {
			return true;
		}
	}

	function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
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
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_referencia'>Ref. Peças</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_descricao'>Descrição Peça</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	
	<?php if (in_array($login_tipo_posto_codigo, array("Rep", "Rev"))){ ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Cliente</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa_posto"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" page="pedido_relacao_new" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Cliente</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >
							<span class='add-on' rel="lupa_posto"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" page="pedido_relacao_new" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	<?php } ?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("numero_pedido", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='numero_pedido'>Número Pedido</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="numero_pedido" id="numero_pedido" class='span12' value="<? echo $numero_pedido ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span6'></div>
	</div>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
</div>
<div class="container-fluid">
<?php
	if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
		?>
			<table id="resultado" class='table table-striped table-bordered table-hover table-fixed' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Pedido</th>
						<th>Pedido SAP</th>
						<?php if (in_array($login_tipo_posto_codigo, array("Rep", "Rev"))){ ?>
						<th>Pedido Representante</th>
                        <th>Cliente</th>
                    	<?php } ?>
                        <th>Data</th>
						<th>Finalizado</th>
						<th>Status</th>
						<th>Tipo Pedido</th>
						<th>Total</th>
						<th>Ação</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
						$pedido                    = trim(pg_fetch_result($resSubmit, $i, 'pedido'));
		                $seu_pedido                = trim(pg_fetch_result($resSubmit, $i, 'seu_pedido'));
		                $tabela                    = trim(pg_fetch_result($resSubmit, $i, 'tabela'));
		                $pedido_blackedecker       = trim(pg_fetch_result($resSubmit, $i, 'pedido_blackedecker'));
		                $data                      = trim(pg_fetch_result($resSubmit, $i, 'data'));
		                $aprovado_cliente          = trim(pg_fetch_result($resSubmit, $i, 'aprovado_cliente'));
		                $finalizado                = trim(pg_fetch_result($resSubmit, $i, 'finalizado'));
		                $pedido_sedex              = trim(pg_fetch_result($resSubmit, $i, 'pedido_sedex'));
		                $pedido_loja_virtual       = trim(pg_fetch_result($resSubmit, $i, 'pedido_loja_virtual'));
		                $id_status                 = trim(pg_fetch_result($resSubmit, $i, 'id_status'));
		                $pedido_status             = trim(pg_fetch_result($resSubmit, $i, 'pedido_status'));
		                $status_pedido             = trim(pg_fetch_result($resSubmit, $i, 'xstatus_pedido'));
		                $tipo_pedido_descricao     = trim(pg_fetch_result($resSubmit, $i, 'tipo_pedido_descricao'));
		                $linha                     = trim(pg_fetch_result($resSubmit, $i, 'linha_descricao'));
		                $exportado                 = trim(pg_fetch_result($resSubmit, $i, 'exportado'));
		                $distribuidor              = trim(pg_fetch_result($resSubmit, $i, 'distribuidor'));
		                $recebido_posto            = trim(pg_fetch_result($resSubmit, $i, 'recebido_posto'));
		                $obs                       = trim(pg_fetch_result($resSubmit, $i, 'obs'));
		                $classe                    = trim(pg_fetch_result($resSubmit, $i, 'classe'));
		                $seu_pedido                = trim(pg_fetch_result($resSubmit, $i, 'seu_pedido'));
		                $permite_alteracao         = trim(pg_fetch_result($resSubmit, $i, 'permite_alteracao'));
		                $pedido_cliente_2          = trim(pg_fetch_result($resSubmit, $i, 'pedido_cliente'));
		                $marca                     = trim(pg_fetch_result($resSubmit, $i, 'marca'));
		                $desconto                  = trim(pg_fetch_result($resSubmit, $i, 'desconto'));
		                $pedido_valores_adicionais = trim(pg_fetch_result($resSubmit, $i,'valores_adicionais'));
						$total                     = trim(pg_fetch_result($resSubmit, $i, 'total'));
						$filial 				   = trim(pg_fetch_result($resSubmit, $i, "filial_nome_fantasia"));
						$codigo_posto			   = trim(pg_fetch_result($resSubmit, $i, "codigo_posto"));
						$obs 					   = json_decode($obs, true);
					?>	
						<tr id='tr_<?=$pedido?>'>
							<td class='tac'><a href="pedido_finalizado.php?pedido=<?=$pedido?>" target='_blank'><?=$pedido?></a></td>
							<td class='tac'><?=$pedido_cliente_2?></td>
							<?php if (in_array($login_tipo_posto_codigo, array("Rep", "Rev"))){ ?>
							    <td class='tac'><?=$seu_pedido?></td>
							    <td><?=$codigo_posto?> - <?=$filial?></td>
			                <?php } ?>
			                <td class='tac'><?=mostra_data($data)?></td>
			                <td class='tac'><?=$finalizado?></td>
			                <td class='tac'><?=$status_pedido?></td>
			                <td><?=$tipo_pedido_descricao?></td>
			                <td class='tac'><?=number_format($total,2,",",".")?></td>
			                <td class='tac'>
			                	<?php if (strlen ($exportado) == 0 AND strlen ($distribuidor) == 0 AND $id_status <> 14) { ?>
			                		<button type="button" data-pedido='<?=$pedido?>' class='btn btn-danger btn-small btn_excluir'>Excluir</button>
			               		<?php } ?>
			                </td>
						</tr>
					<?php
					}
					?>
				</tbody>
			</table>

			<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>
		<?php
		}else{
			echo "<div class='alert'><h4>Nenhum pedido encontrado</h4></div>";
		}
	}
?>
</div>
<?php include "rodape.php"; ?>
