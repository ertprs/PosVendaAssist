<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

if($_POST['ajax_gera_plp']){
	$etiquetas 		 = $_POST['etiquetas'];
	$cartao_postagem = $_POST['cartao_postagem'];

	$correios = new \Posvenda\Sigep($login_fabrica);
	$plp      = $correios->gerarPLP($etiquetas,$cartao_postagem);
	echo json_encode($plp[0]);
	exit;
}

if($_POST['ajax_grava_plp']){
	$etiquetas 			= $_POST['etiquetas'];
	$cartao_postagem 	= $_POST['cartao_postagem'];
	$idplp 				= $_POST['plp'];

	$correios = new \Posvenda\Sigep($login_fabrica);
	$result   = $correios->gravaPLP($idplp,$etiquetas,$cartao_postagem);

	echo json_encode($result);
	exit;
}

if($_POST['ajax_imprimir_plp']){
	$idplp = $_POST['plp'];

	$correios = new \Posvenda\Sigep($login_fabrica);
	$result   = $correios->imprimirPLP($idplp);
}

$layout_menu = "callcenter";
$title = "GERAR PRÉ LISTA DE POSTAGEM DOS PEDIDOS";
include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

if($_POST) {
	$pedido 		= $_POST['pedido'];
	$data_inicial 		= $_POST['data_inicial'];
	$data_final 		= $_POST['data_final'];
	$codigo_posto 		= $_POST['codigo_posto'];
	$descricao_posto 	= $_POST['descricao_posto'];

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
				";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
			$sql_posto = " AND tbl_pedido.posto = {$posto}";
		}
	}

	if ((!strlen($data_inicial) or !strlen($data_final)) && empty($pedido)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);


		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di} 00:00:00";
			$aux_data_final   = "{$yf}-{$mf}-{$df} 23:59:59";

			$sql_datas = " AND tbl_pedido.data BETWEEN '$aux_data_inicial' and '$aux_data_final' ";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			} 
		}		
	}

	if(!empty($pedido) AND is_numeric($pedido)){
		$sql_pedido = " AND tbl_pedido.pedido = {$pedido}";
	}
}
?>

<style>
.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

table#resultado tr:nth-child(2n + 1) td {
    border-top-width: 4px !important;
    border-top-color: grey !important;

}
</style>

<script type="text/javascript">
	$(function(){
		$("#data_inicial, #data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		// Selecionar todos os checkbox
		$("#item_selecionado_todos").click(function(){
    		$('input:checkbox').not(this).prop('checked', this.checked);
		});

		$.datepickerLoad(["data_final", "data_inicial"]);
		$.autocompleteLoad(["posto"]);
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("button.gera_plp_selecionados").click(function(){
			$(".gera_plp_selecionados").text("Gerando PLP...").attr("disabled", true);
			let pedidos = new Array();
			let json    = {};
			
			$("#pedidos_plp").find("input[class=item_selecionado]:checked").each(function(){
				pedidos.push($(this).val());

			});

			if(pedidos.length > 0){
				json = pedidos;
        		json = JSON.stringify(json);
				gerarPLP(json);
			}else{
				alert("Nenhuma etiqueta selecionada");
			}
		});

		$("button.imprimir_plp").click(function(){
			let plp = $(this).data("plp");
			window.open("imprimir_plp.php?idplp=" + plp + "", "_blank");	
		});

	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	function gerarPLP(pedidos){
	    var etiqueta_lancada = "";
	    var cartao_postagem = $(".gera_plp_selecionados").attr("data-cartao");

	    if(typeof cartao_postagem != "undefined" && cartao_postagem.length > 0){
	    	
	    	pedidos = JSON.parse(pedidos);
		    $.each(pedidos, function (key, item) {
		    	if(etiqueta_lancada == ""){
		    		etiqueta_lancada = item;
		    	}else{
		    		etiqueta_lancada = etiqueta_lancada+","+item;
		    	}
		    });

		    if(etiqueta_lancada != ""){

		    	var plp = $("#plp").val();

		    	var dataAjax = {
			        etiquetas: etiqueta_lancada,
			        cartao_postagem: cartao_postagem,
			        ajax_gera_plp: true
			    };

			    // window.open("imprimir_plp.php?etiquetas="+etiqueta_lancada+"&cartao_postagem="+cartao_postagem+"", "_blank");

			    // window.open("../distrib/PhpSigep/exemplos/imprimirPlp.php?etiquetas="+etiqueta_lancada+"&cartao_postagem="+cartao_postagem+"&idplp=1321321", "_blank");

				$.ajax({
			        url: 'gerar_plp_pedidos.php',
			        type: 'POST',
			        data: dataAjax,
			        success: function(data){
			        	var dados = JSON.parse(data);

			        	$("#mensagem").html('');

			        	if(dados.resultado){
		        			$(".gera_plp_selecionados").text("Gravando PLP gerada...").attr("disabled", true);
		        			gravaPLP(dados.idplp,etiqueta_lancada,cartao_postagem);
		        		}else{
		        			$(".gera_plp_selecionados").text("Gerar PLP Pedidos Selecionados").attr("disabled", false);
	        				$("#mensagem").html('<div class="alert alert-error"><h4>'+dados.msg+'</h4> </div>');
		        		}
					}
				});
			}else{
				$(".gera_plp_selecionados").text("Gerar PLP Pedidos Selecionados").attr("disabled", false);
				$("#mensagem").html('');
				$("#mensagem").html('<div class="alert alert-error"><h4>Não há etiqueta lançada!</h4> </div>');
			}
		}else{
			$(".gera_plp_selecionados").text("Gerar PLP Pedidos Selecionados").attr("disabled", false);
			$("#mensagem").html('');
			$("#mensagem").html('<div class="alert alert-error"><h4>Não foi encontrado o número do cartão de postagem!</h4> </div>');
		}
	}

	function gravaPLP(plp, etiqueta_lancada, cartao_postagem){

		var dataAjax = {
	        plp: plp,
	        etiquetas: etiqueta_lancada,
	        cartao_postagem: cartao_postagem,
	        ajax_grava_plp: true
	    };

	    $.ajax({
	    		url: "gerar_plp_pedidos.php",
	    		type: "POST",
	    		data: dataAjax,
	    		success: function(data){
	    			var dados = JSON.parse(data);
		        	$("#mensagem").html('');

	        		if(dados.resultado){
	        			$(".gera_plp_selecionados").text("Gerar PLP Pedidos Selecionados").attr("disabled", false);
	        			window.open("imprimir_plp.php?idplp=" + dados.idplp + "", "_blank");
						// window.open("PhpSigep/exemplos/imprimirPlp.php?etiquetas="+etiqueta_lancada+"&cartao_postagem="+cartao_postagem+"&idplp="+value.idplp, "_blank");
						// loading(0);
						// window.location.reload();

	        		}else{
	        			$(".gera_plp_selecionados").text("Gerar PLP Pedidos Selecionados").attr("disabled", false);
        				$("#mensagem").html('<div class="alert alert-error"><h4>'+dados.mensagem+'</h4> </div>');
        				loading(0);
	        		}
	    		}
	    });
	}

	function fechaPLP(plp, etiqueta_lancada, cartao_postagem){
		    var dataAjax = {
		        plp: plp,
		        etiquetas: etiqueta_lancada,
		        cartao_postagem: cartao_postagem
		    };

			$.ajax({
		        url: '',
		        type: 'get',
		        data: dataAjax,
		        success: function(data){
		        	var cont = 0;
		        	var dados = JSON.parse(data);
		        	$("#mensagem").html('');

		        	$.each(dados,function(key, value){
		        		if(value.resultado == "false"){
	        				$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');	        				
		        		}else{
		        			if(cont == 0){
		        				cont = 1;
		        				dataAjax = "";
		        				dataAjax = { 
		        					idplp: value.idplp, 
		        					etiquetas: etiqueta_lancada,
		        					funcao: "gravaIdPLP"
		        				};
		        				
								$.ajax({
							        url: 'funcao_correio.php',
							        type: 'get',
							        data: dataAjax,
							        success: function(data){
							        	var cont = 0;
							        	var dados = JSON.parse(data);
							        	$("#mensagem").html('');
							        	$.each(dados,function(key, value){
							        		if(value.resultado == "false"){
						        				$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
						        				loading(0);
							        		}else{
							        			if(cont == 0){
							        				cont = 1;
						 							window.open("../distrib/PhpSigep/exemplos/imprimirPlp.php?etiquetas="+etiqueta_lancada+"&cartao_postagem="+cartao_postagem+"&idplp="+value.idplp, "_blank");
						 							loading(0);
						 							window.location.reload();
								        		}
							        		}
							        	});
									}
								});
			        		}else{
			        			loading(0);
			        		}
		        		}
		        	});
				}
			});
		}

	</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error" id="mensagem">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>					

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
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
				<div class='control-group <?=(in_array("pedido", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='pedido'>Pedido</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="pedido" id="pedido" class='span12' value="<? echo $pedido ?>" >
						</div>
					</div>
				</div>
			</div>
		</div>		
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>">
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
		<p><br />
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br />		
</form>
<?php

if((!empty($aux_data_inicial) AND !empty($aux_data_final) OR !empty($pedido)) AND count($msg_erro) == 0) {		
		
			$sql = "SELECT cartao FROM tbl_fabrica_correios WHERE fabrica = {$login_fabrica}";
			$res = pg_query($con,$sql);
			$cartao_postagem = pg_fetch_result($res, 0, 'cartao');
		
			$sql = "SELECT array_to_string (array_agg(tbl_pedido.pedido), ',') AS pedido,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome,
							tbl_etiqueta_servico.etiqueta,
							tbl_plp_etiqueta.lista_postagem
					FROM tbl_pedido
					INNER JOIN tbl_etiqueta_servico USING(etiqueta_servico,fabrica)
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
					INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					LEFT JOIN tbl_plp_etiqueta ON tbl_etiqueta_servico.etiqueta_servico = tbl_plp_etiqueta.etiqueta_servico
					WHERE tbl_etiqueta_servico.fabrica = $login_fabrica
					--AND tbl_plp_etiqueta.etiqueta_servico IS NULL
				{$sql_pedido}
				{$sql_posto}
				{$sql_datas}
				GROUP BY tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome,
					tbl_etiqueta_servico.etiqueta,
					tbl_plp_etiqueta.lista_postagem
				ORDER BY tbl_posto.nome ";
			 //echo nl2br($sql);
		$res = pg_query($con, $sql);

	if (isset($res)) {
		if (pg_num_rows($res) > 0) {
			echo "<br />";
	?>
					<table id="pedidos_plp" class='table table-bordered table-hover table-fixed' >
						<thead>
							<tr class='titulo_coluna' >
								<th>
									<input type="checkbox" id="item_selecionado_todos" name="item_selecionado_todos" />
								</th>
								<th>Código do Posto</th>
								<th>Nome do Posto</th>
								<th>Pedido</th>
								<th>Etiqueta</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody>

							<?php
							for ($i = 0; $i < pg_num_rows($res); $i++) {
								$pedidos 		= pg_fetch_result($res, $i, "pedido");
								$data 			= pg_fetch_result($res, $i, "data");
								$codigo_posto 	= pg_fetch_result($res, $i, "codigo_posto");
								$nome_posto		= pg_fetch_result($res, $i, "nome");
								$etiqueta		= pg_fetch_result($res, $i, "etiqueta");
								$plp			= pg_fetch_result($res, $i, "lista_postagem");

								$pedidos = explode(",",$pedidos);
							?>
							<tr>
								<td class="tac">
									<?php if(empty($plp)){ ?>
										<input type="checkbox" class="item_selecionado" value="<?=$etiqueta?>" />
									<?php } ?>
								</td>
								<td><?=$codigo_posto?></td>
								<td><?=$nome_posto?></td>
								<td class="tac">
								<?php
								foreach($pedidos AS $pedido){
									?>
									<a href="pedido_admin_consulta.php?pedido=<?=$pedido?>" target="_blank"><?=$pedido?></a></br>
									<?php
								}
								?>
								</td>
								<td class="tac"><?=$etiqueta?></td>
								<td class="tac">
									<?php if(!empty($plp)){ ?>
										<button type='button' class='btn imprimir_plp' data-plp='<?=$plp?>'>Imprimir PLP</button>
									<?php } ?>
								</td>
							</tr>
							<?php
							}
							?>
						</tbody>		
						<tfoot>
							<tr class='titulo_coluna'>
								<td colspan='7'class='tac'>
									<button type='button' class='btn gera_plp_selecionados' data-cartao='<?=$cartao_postagem?>'>Gerar PLP Pedidos Selecionados</button>
								</td>
							</tr>
						</tfoot>
					</table>
			<?php				
		}else{
		?>
			<div class="container">
			<div class="alert"><h4>Nenhum resultado encontrado</h4></div>
			</div>
		<?php
		}
	}
}
?>				

<?php include "rodape.php"; ?>
