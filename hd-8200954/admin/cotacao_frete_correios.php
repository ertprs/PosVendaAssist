<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';

if($_POST['ajax_frete']){

	$dados["comprimento"] = $_POST['comprimento'];
	$dados["largura"]	  = $_POST['largura'];
	$dados["altura"]	  = $_POST['altura'];
	$dados["volume"]	  = $_POST['volume'];
	$dados["peso"] 		  = $_POST['peso'];
	$dados["valor"]		  = $_POST['valor'];
	$dados["cepDestino"]  = $_POST['cep'];
	$dados["pedidos"] 	  = json_decode($_POST["pedidos"],true);

	$correios = new \Posvenda\Sigep($login_fabrica);

	$tipos = $correios->calculaFrete($dados);
	echo json_encode($tipos);
	exit;
}

if($_POST['ajax_servico']){

	$dados["comprimento"] = $_POST['comprimento'];
	$dados["largura"]     = $_POST['largura'];
	$dados["altura"]      = $_POST['altura'];
	$dados["volume"]      = $_POST['volume'];
	$dados["peso"]        = $_POST['peso'];
	$dados["valor"]       = $_POST['valor'];
	$dados["cepDestino"]  = $_POST['cep'];
	$dados["pedidos"]     = json_decode($_POST["pedidos"],true);
	$dados["codigo"]      = $_POST['codigo'];
	$dados["caixa"]       = $dados["comprimento"]+","+$dados["largura"]+","+$dados["altura"];

	$correios = new \Posvenda\Sigep($login_fabrica);

	try{
		$tipos = $correios->buscaEtiquetaBanco($dados,"pedido");
	}catch(Exception $e){
		exit(utf8_decode($e->getMessage()));	
	}
	
	echo json_encode($tipos);
	exit;

}

$layout_menu = "callcenter";
$title = "COTAR FRETE PEDIDOS";
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
	$pedido          = $_POST['pedido'];
	$data_inicial    = $_POST['data_inicial'];
	$data_final      = $_POST['data_final'];
	$codigo_posto    = $_POST['codigo_posto'];
	$descricao_posto = $_POST['descricao_posto'];

	if(!empty($pedido) AND is_numeric($pedido)){
		$sql_pedido = " AND tbl_pedido.pedido = {$pedido}";
	}else{
		if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){

			
			$sql = "SELECT tbl_posto_fabrica.posto, tbl_tipo_posto.posto_interno
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
					JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto  AND tbl_tipo_posto.fabrica={$login_fabrica}
					WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
					AND (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Posto não encontrado";
				$msg_erro["campos"][] = "posto";
			} else {
				$posto = pg_fetch_result($res, 0, "posto");
				$posto_interno = pg_fetch_result($res, 0, "posto_interno");
				$sql_posto = " AND tbl_pedido.posto = {$posto}";
			}
		}else{
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "posto";
		}

		if ((!strlen($data_inicial) or !strlen($data_final)) && empty($pedido)) {
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "data";
		} else if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);


			if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
				$msg_erro["msg"][]    = "Data InvÃ¡lida";
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
	}
}
?>

<style>
/* Flex */
.flex {
	display: flex;
}
#impressoes_etiquetas{
text-align: center !important;
}

.flex-wrap {
	flex-wrap: wrap;
}

/* Flex Item */
.valor_frete {
	width: 20%;
	padding: 10px;
	margin: 5px;
	background: yellow;
	border: solid 2px #596d9b;
	color: black;
	font-weight: bold;
	text-align: left;
	font-size: 11px;
}

.box-frete {
	max-width: 100%;
	margin: 0 auto;
	border: 1px solid #ccc;
}
</style>

<script type="text/javascript">
	$(function(){
		$("#data_inicial, #data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		// Selecionar todos os checkbox
		$("#item_selecionado_todos").click(function(){
    		$('input:checkbox').not(this).prop('checked', this.checked);
		});

		$(".item_selecionado").click(function(){

			let total_nota = 0;

			$("#pedidos_plp").find("input[class=item_selecionado]:checked").each(function(){

				total_nota = parseFloat($(this).attr("rel")) + parseFloat(total_nota);

			});

			$("#total_nota").val(total_nota);

		});

		$.datepickerLoad(["data_final", "data_inicial"]);
		$.autocompleteLoad(["posto"]);
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("button.gera_plp_selecionados").click(function(){
			let pedidos = new Array();
			let json = {};
			
			$("#pedidos_plp").find("input[class=item_selecionado]:checked").each(function(){
				pedidos.push($(this).val());

			});

			if(pedidos.length > 0){
				json = pedidos;
        		json = JSON.stringify(json);
			}else{
				alert("Nenhum pedido selecionado");
			}
		});


		$(document).on('click','#cotar_frete',function(){
			limpaMensagem();

			if ($("#comprimento").val() == "") {
				alert("Digite o Comprimento");
				$("#comprimento").focus();
				return false;
			}	
						
			if ($("#altura").val() == "") {
				alert("Digite a Altura");
				$("#altura").focus();
				return false;
			}		
			if ($("#largura").val() == "") {
				alert("Digite a Largura");
				$("#largura").focus();
				return false;
			}		
			if ($("#peso").val() == "") {
				alert("Digite o Peso");
				$("#peso").focus();
				return false;
			}		
			if ($("#volume").val() == "") {
				alert("Digite o Volume");
				$("#volume").focus();
				return false;
			}
			$(this).text("Cotando frete...").attr("disabled", true);
			let pedidos = new Array();
			let json = {};
		    let cep_destino;	
			$("#pedidos_plp").find("input[class=item_selecionado]:checked").each(function(){
				cep_destino = $(this).attr('data-cep');
				pedidos.push($(this).val());

			});

			if(pedidos.length > 1) {
				alert('Para cotar frete, selecione apenas 1 pedido ');
				$("#cotar_frete").text("Cotar frete").attr("disabled", false);
				return false;
			}
			if(pedidos.length > 0){
				json = pedidos;
        		json = JSON.stringify(json);
			

				var dataAjax = {
					peso:           $("#peso").val(),
					volume:         $("#volume").val(),
					comprimento:    $("#comprimento").val(),
					largura: 		$("#largura").val(),
					altura: 		$("#altura").val(),
					valor: 			$("#total_nota").val(),
					cep:            cep_destino,
					pedidos: 		json,
					ajax_frete:		true
				};

				$("#prazo_frete").html("");

				$.ajax({
					url: "cotacao_frete_correios.php",
					type: "POST",
					data: dataAjax,
				}).done(function(data){

					data = JSON.parse(data);
					let div = "";

					if(data.resultado){
						$.each(data,function(key, value){

							if(parseFloat(value.valor) > 0){
								div = "<div class='valor_frete'><input type='radio' value='"+value.codigo+"' class='tipo_frete' name='frete_selecionado'> "+value.descricao+"<br>Valor: R$ "+value.valor+"<br>Prazo Entrega: "+value.prazo_entrega+" dia(s)</div>";
								$("#prazo_frete").append(div);
							}
						});	

					} else {
						showMensagem(data.mensagem, "alert-error");
					}

					$("#cotar_frete").text("Cotar frete").attr("disabled", false);
				});
			}else{
				showMensagem("Nenhum pedido selecionado", "alert-error");
				$(this).text("Cotar frete").attr("disabled", false);
			}
		});


		 $(document).on("click","#btn_imprimir", function(){
                        var etiqueta_gerada = $("input[name=etiqueta_gerada]").val();
                        disable_button_loading("#" + this.id, "Imprimindo...", false);

                        if(etiqueta_gerada == ''){
                                showMensagem("Nenhuma etiqueta informada!", "alert-error");
                                disable_button_loading("#" + this.id, "Imprimir Etiqueta", false);
                        } else {

                               window.open("gerar_pdf_etiqueta.php?lista_etiqueta="+etiqueta_gerada+"", "_blank");
                                disable_button_loading("#" + this.id, "Imprimir Etiqueta", false);
                        }
                });

               $("#btn_declaracao_conteudo").on("click", function(){
                        var etiqueta_gerada = $("input[name=etiqueta_gerada]").val();
			disable_button_loading("#" + this.id, "Imprimindo...", false);

                         if(etiqueta_gerada == ''){
                                showMensagem("Nenhuma etiqueta informada!", "alert-error");
                                disable_button_loading("#" + this.id, "Imprimir DeclaraÃÃ£o de ContÃºdo", false);
                        } else {

                                window.open("gerar_declaracao_conteudo.php?lista_etiqueta="+etiqueta_gerada+"", "_blank");
                                disable_button_loading("#" + this.id, "Imprimir DeclaraÃÃo de ConteÃºdo", false);
                        }
                });

		$(document).on('click','#gerar_etiqueta',function(){
			limpaMensagem();
			$(this).text("Gerando Etiqueta...").attr("disabled", false);
			
			if($("input[name=frete_selecionado]:checked").length == 0){
				showMensagem("Selecione um tipo de frete","alert-error");
				$(this).text("Gerar Etiqueta").attr("disabled", false);

			}else{
				if($(".item_selecionado:checked").length == 0){
					showMensagem("Nenhum pedido selecionado","alert-error");
					$(this).text("Gerar Etiqueta").attr("disabled", false);

				}else{

					let pedidos = new Array();
					let json = {};
					
					$("#pedidos_plp").find("input[class=item_selecionado]:checked").each(function(){
						pedidos.push($(this).val());

					});

					if(pedidos.length > 0){
						json = pedidos;
		        		json = JSON.stringify(json);
					

						var dataAjax = {
							peso:           $("#peso").val(),
							volume:         $("#volume").val(),
							comprimento:    $("#comprimento").val(),
							largura: 		$("#largura").val(),
							altura: 		$("#altura").val(),
							valor: 			$("#total_nota").val(),
							cep:            $("#cep_destino").val(),
							codigo: 		$("input[name=frete_selecionado]:checked").val(),
							pedidos: 		json,
							ajax_servico:	true
						};

						$.ajax({
							url: "cotacao_frete_correios.php",
							type: "POST",
							data: dataAjax							
						}).done(function(data){
							data = JSON.parse(data);

							if(data.resultado){
								showMensagem("Etiqueta " + data.etiqueta + " gerada!", "alert-success");
								limparCampo();
								$.each(pedidos, function(key, value){
									$("#pedido_" + value).remove();
								});
								$("input[name=etiqueta_gerada]").val(data.etiqueta);
								$("#impressoes_etiquetas").show();
								$("#gerar_etiqueta").hide();
							} else {
								showMensagem(data.msg, "alert-error");
							}
							$("#gerar_etiqueta").text("Gerar Etiqueta").attr("disabled", false);

						}).fail(function(data){
							showMensagem(data, "alert-error");
							$("#gerar_etiqueta").text("Gerar Etiqueta").attr("disabled", false);
						});
					}
				}
			}
		});
	});

	function limparCampo(){
		$("#peso").val("");
		$("#volume").val("");
		$("#comprimento").val("");
		$("#largura").val("");
		$("#altura").val("");
		$("#total_nota").val("");
	}

	function limpaMensagem(){
		$("#retorno_mensagem").removeClass('alert-error alert-success').html("").hide();
	}

	function showMensagem(mensagem, tipo_mensagem){
		$("#retorno_mensagem").addClass(tipo_mensagem).html("<h4>" + mensagem + "</h4>").show();
	}

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	function disable_button_loading(btn_name, text, disable){
        	$(btn_name).text(text).attr("disabled", disable);
	}


        function getEtiquetaTable(){
                var lista_etiqueta = "";
                $.each($("#table_pedido > tbody > tr"), function(key, value){
                        var etiqueta = value.id.replace("tr_etiqueta_","");
                        if(lista_etiqueta != ""){
                                lista_etiqueta += ",";
                        }
                        lista_etiqueta += etiqueta;
                });
                return lista_etiqueta;
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
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>C&oacute;digo Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
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
							<h5 class='asteristico'>*</h5>
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
		<p><br />
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br />		
</form>
<?php

if((!empty($aux_data_inicial) AND !empty($aux_data_final) OR !empty($pedido)) AND count($msg_erro) == 0) {		

	$orderby = " ORDER BY tbl_posto.nome, tbl_pedido.data";
	$sql = "SELECT  
			tbl_pedido.pedido,
			to_char(tbl_pedido.data,'DD/MM/YYYY') AS data,
			tbl_pedido.total,
			tbl_posto_fabrica.codigo_posto,
			tbl_posto_fabrica.contato_cep,
			tbl_posto.nome
		FROM tbl_pedido
			INNER JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
			INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
				AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		WHERE tbl_pedido.fabrica = {$login_fabrica}
			/*AND   tbl_pedido.exportado IS NOT NULL
			AND tbl_pedido.status_pedido in(2,5)*/
			AND tbl_pedido.etiqueta_servico IS NULL
			{$sql_pedido}
			{$sql_posto}
			{$sql_datas}
			{$orderby}
			";
	$res = pg_query($con, $sql);

	if (isset($res)) {
		if (pg_num_rows($res) > 0) {
			echo "<br />";
	?>
					<table id="pedidos_plp" class='table table-bordered table-hover table-fixed' >
						<thead>
							<tr class='titulo_coluna' >
								<th>
									<!-- <input type="checkbox" id="item_selecionado_todos" name="item_selecionado_todos" /> -->
								</th>
								<th>Pedido</th>
								<th>O.S.</th>
								<th>Data Pedido</th>
								<th>Valor Pedido</th>
								<th>Código do Posto</th>
								<th>Nome do Posto</th>				
							</tr>
						</thead>
						<tbody>
							<?php
							for ($i = 0; $i < pg_num_rows($res); $i++) {
								$data 			= pg_fetch_result($res, $i, "data");
								$pedido 		= pg_fetch_result($res, $i, "pedido");
								$total 			= pg_fetch_result($res, $i, "total");
								$codigo_posto 	= pg_fetch_result($res, $i, "codigo_posto");
								$nome_posto		= pg_fetch_result($res, $i, "nome");
								$cepDestino 	= pg_fetch_result($res, $i, "contato_cep");



								$sqlOS = "SELECT tbl_os_produto.os AS os, tbl_os.consumidor_cep
									FROM tbl_pedido_item 
									JOIN tbl_os_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item AND tbl_os_item.pedido=tbl_pedido_item.pedido 
									JOIN tbl_os_produto ON tbl_os_produto.os_produto=tbl_os_item.os_produto 
									JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
									WHERE tbl_pedido_item.pedido=$pedido";
								$resOs = pg_query($con, $sqlOS);
								if (pg_num_rows($resOs) > 0) { 
									$os 	= pg_fetch_result($resOs, 0, "os");
									$consumidor_cep = pg_fetch_result($resOs,0,'consumidor_cep');
									$cepDestino = ($posto_interno == "t") ? $consumidor_cep : $cepDestino;
								}


							?>
							<tr id="pedido_<?=$pedido?>">
								<td class='tac'>
								<input type="checkbox" class="item_selecionado" value="<?=$pedido?>" rel="<?=$total?>" data-cep=<?=$cepDestino?> />
								</td>
								<td><?=$pedido?></td>
								<td><?=$os?></td>
								<td><?=$data?></td>
								<td class='tar'><?=number_format($total,2,',','.')?></td>
								<td><?=$codigo_posto?></td>
								<td><?=$nome_posto?></td>
							</tr>
							<?php
							}
							?>
						</tbody>		
						<tfoot>
							<tr>
								<td colspan='100%'>
									<div class='row-fluid'>
										<div class='span2'> <input type='hidden' name='cep_destino' id='cep_destino' value='<?=$cepDestino?>'> </div>
										<div class='span8'>
											<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
												<div class='titulo_tabela '>Dimensões da Caixa</div>
												<div class='controls controls-row'>
													<div class='span3'>
														<label class='control-label' for='comprimento'>Comprimento</label>
														<div class='controls controls-row'>
															<div class='span12 input-append'>
																<input type="text" name="comprimento" id="comprimento" maxlength="10" class='span10'>
																<span class='add-on'>cm</span>
															</div>
														</div>
													</div>

													<div class='span3'>
														<label class='control-label' for='altura'>Altura</label>
														<div class='controls controls-row'>
                                                                                                                        <div class='span12 input-append'>

																<input type="text" name="altura" id="altura" maxlength="10" class='span10'>
																<span class='add-on'>cm</span>
															</div>
														</div>
													</div>

													<div class='span3'>
														<label class='control-label' for='largura'>Largura</label>
														<div class='controls controls-row'>
                                                                                                                        <div class='span12 input-append'>

																<input type="text" name="largura" id="largura" maxlength="10" class='span10'>
																<span class='add-on'>cm</span>

															</div>
														</div>
													</div>
													<div class='span3'>
														<label class='control-label' for='peso'>Peso</label>
														<div class='controls controls-row'>
                                                                                                                        <div class='span12 input-append'>

																<input type="text" name="peso" id="peso"  maxlength="10" class='span10'>
																<span class='add-on'>kg</span>
															</div>
														</div>
													</div>
												</div>
												<div class='controls controls-row'>
													<div class='span3'>
														<label class='control-label' for='peso'>Volume</label>
														<input type="text" name="volume" id="volume" maxlength="10" class='span12' value="1">
													</div>

													<div class='span3'>
														<label class='control-label' for='peso'>Total Nota</label>
														<input type="text" name="total_nota" id="total_nota"  maxlength="10" class='span12' readonly="">
													</div>
												</div>
											</div>
										</div>
										<div class='span2'></div>
									</div>

									<div class='row-fluid'>
                                            <div class='span2'></div>
                                            <div class='span8'>
                                                    <center><button class='btn' id="cotar_frete" type="button"><span class="icon-road"></span> Cotar Frete</button></center>
                                            </div>
                                            <div class='span2'></div>
                                    </div>

									<div class="box-frete flex flex-wrap" id="prazo_frete"></div>
									<div class="alert" id="retorno_mensagem" style="display: none;"></div>
								</td>
							</tr>
							<tr class='titulo_coluna'>
								<td colspan='100%'class='tac'>
									<div id="impressoes_etiquetas" style="display:none;margin:top:40px;disply:block;">
                                                                                <input type="hidden" name="etiqueta_gerada">
                                                                                <button type="button" class='btn btn-success' id="btn_imprimir">Imprimir Etiqueta</button>
                                                                                <button type="button" class='btn' id="btn_declaracao_conteudo">Imprimir Declaracao de Conteudo</button>
                                                                        </div>


									<button type='button' class='btn' id='gerar_etiqueta'>Gerar Etiqueta</button>
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
