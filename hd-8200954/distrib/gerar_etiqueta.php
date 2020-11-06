<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'cabecalho.php';
include 'autentica_usuario.php';

if (strlen ($login_posto) == 0) {
    header ("Location: https://www.telecontrol.com.br");
    exit;
}

if (isset($_POST['pesquisaEmbarques']) && !empty($_POST['periodo'])) {

	switch ($_POST['periodo']) {
		case 'semana':
			$condPeriodo = "AND tbl_embarque.data::date BETWEEN current_date - INTERVAL '7 DAYS' AND CURRENT_DATE";
			//$condPeriodo = "AND extract(week FROM tbl_embarque.data) = 44";
			break;
		case 'mes':
			$condPeriodo = "AND tbl_embarque.data::date BETWEEN current_date - INTERVAL '30 DAYS' AND CURRENT_DATE";
			//$condPeriodo = "AND extract(month FROM tbl_embarque.data) = 11";
			break;
		default:
			$condPeriodo = "AND tbl_embarque.data::date = current_date";
			//$condPeriodo = "AND extract(month FROM tbl_embarque.data) = 11 AND extract(day FROM tbl_embarque.data) = 08";
			break;
	}

	$sql = "SELECT tbl_embarque.embarque, tbl_fabrica.nome
			FROM tbl_etiqueta_servico 
			LEFT JOIN tbl_plp_etiqueta USING(etiqueta_servico) 
			JOIN tbl_embarque USING(embarque)
			LEFT JOIN tbl_fabrica ON tbl_embarque.fabrica = tbl_fabrica.fabrica
			WHERE tbl_plp_etiqueta.etiqueta_servico IS NULL
			{$condPeriodo}
			ORDER BY tbl_fabrica.nome DESC";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		$embarques = [];

		while ($dados = pg_fetch_object($res)) {

			$embarques[$dados->embarque] = utf8_encode($dados->nome);

		}

		exit(json_encode($embarques));

	} else {

		exit(json_encode(["erro" => true]));

	}
}

?>
<html>
<head>
	<title>Gerar Etiqueta</title>
	<link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
	<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script src="../bootstrap/js/bootstrap.js"></script>
	<style type="text/css">
		.body {
		font-family : verdana;
		}

		#mensagem-correio{
			color: #b94a48;
			background-color: #FFFF99;
			border-color: #eed3d7;
			padding: 8px 35px 8px 14px;
			margin-bottom: 20px;
			text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
			border: 1px solid #fbeed5;
			border-radius: 4px;
			font-weight: bold;
			font-size: 13px;
			display: block;
		}

		#mensagem-correio-h4{
			margin: 0;
			font-size: 17.5px;
			font-family: inherit;
			font-weight: bold;
			line-height: 20px;
			color: inherit;
			text-rendering: optimizelegibility;
			text-align: center;
			text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
		}
	</style>
	<script>
		function loading(status) {
		    if ( status == 1 )
		        $('#loading').fadeIn();
		    else
		        $('#loading').fadeOut();
		}

		$(function(){

			$("#btn_salvar").click(function(){

				let string_embarques = "";

				$(".check_embarque:checked").each(function(){

					string_embarques += $(this).val()+",";

				});

				$("#numero_embarque").val(string_embarques);

			});

			$(".periodo").click(function(){

				$.ajax({
			        url: window.location,
			        type: 'POST',
			        data: {
			        	pesquisaEmbarques: true,
			        	periodo: $(this).data("periodo")
			        },
			        dataType: "json",
			        success: function(data){

			        	if (data.erro == undefined) {
				        	var tabela_embarque = $("#tabela_embarques tbody");

				        	$(tabela_embarque).html("");

				        	$(".alerta, .alerta_embarque").hide();
				        	$("#tabela_embarques").show();

				        	$.each(data, function( embarque, fabrica ) {
							  
				        		let tr = $("<tr></tr>");

				        		let td_embarque = $("<td></td>", {
				        			text: embarque,
				        			css: {
				        				'text-align': 'center'
				        			}
				        		});

				        		let a_embarque = $("<a></a>", {
				        			href: "embarque_consulta.php?embarque="+embarque+"&btn_acao=pesquisa",
				        			target: "_blank",
				        			text: embarque
				        		});

				        		$(td_embarque).html(a_embarque);

				        		$(tr).append(td_embarque);

				        		let td_fabrica 	= $("<td></td>", {
				        			text: fabrica
				        		});

				        		$(tr).append(td_fabrica);

				        		let td_check 	= $("<td></td>", {
				        			css: {
				        				'text-align': 'center'
				        			}
				        		});

				        		let input_check = $("<input>", {
				        			type: 'checkbox',
				        			value: embarque,
				        			class: 'check_embarque'
				        		});

				        		$(td_check).html(input_check);

				        		$(tr).append(td_check);

				        		$(tabela_embarque).append(tr);

							});
			        	} else {

			        		$(".alerta, #tabela_embarques").hide();
				        	$(".alerta_embarque").show();

			        	}
					}
				});

			});

			$("#marcar_todos").click(function(){

				if ($(this).is(":checked")) {
					$(".check_embarque").prop("checked", true);
				} else {
					$(".check_embarque").prop("checked", false);
				}

			});


		});

		function solicitarEtiqueta(){
			loading(1);
			$("#solicitar_etiqueta").button("loading");

			var dataAjax = {
		        embarque: $("#numero_embarque").val(),
		        funcao: "buscaEmbarque"
		    };

			$.ajax({
		        url: 'funcao_correio.php',
		        type: 'get',
		        data: dataAjax,
		        success: function(data){
		        	var mensagem;
		        	var embarques;
		        	data = JSON.parse(data);
        			$("#solicitar_etiqueta").button("reset");
        			
		        	$.each(data,function(key, value){

		        		if(value.resultado == "false"){
	        				$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
	        				$.each(value.dados,function(key, value){
        						embarques += "<tr> \
										<td nowrap align='center'>"+value.embarque+"</td> \
										<td nowrap align='center'>"+value.descricao+"</td> \
										<td nowrap align='center'>"+value.etiqueta+"</td> \
										<td nowrap align='center'>"+value.peso+"</td> \
										<td nowrap align='center'>"+value.caixa+"</td> \
										<td nowrap align='center'>"+value.prazo_entrega+" dia(s)</td> \
									</tr>";
	        				});
	        				$("#solicitar_etiqueta").button("reset");
		        		}else{
		        			embarques += "<tr> \
									<td nowrap align='center'>"+value.embarque+"</td> \
									<td nowrap align='center'>"+value.descricao+"</td> \
									<td nowrap align='center'>"+value.etiqueta+"</td> \
									<td nowrap align='center'>"+value.peso+"</td> \
									<td nowrap align='center'>"+value.caixa+"</td> \
									<td nowrap align='center'>"+value.prazo_entrega+" dia(s)</td> \
								</tr>";
		        			$("#mensagem").html("");
		        		}
	        			$("#tabela_gerar_etiqueta").html(embarques);
		        	});
				}
			});
			loading(0);
		}

		function gerarEtiqueta(){
			loading(1);
			$("#gerar_lista").button("loading");
			var dataAjax = {
		        embarque: $("#numero_embarque").val(),
		        funcao: "gerarEtiqueta"
			};

			var embarques = $("#numero_embarque").val();

			$.ajax({
		        url: 'funcao_correio.php',
		        type: 'get',
		        data: dataAjax,
		        success: function(data){
		        	window.open("PhpSigep/exemplos/imprimirEtiquetas.php?embarque="+embarques+"&funcao=gerarEtiqueta", "_blank");
		        	// location.href=;
		        	loading(0);
		        	$("#gerar_lista").button("reset");
				}
			});
		}

		function SomenteNumero(e){
			var tecla=new Number();
			if(window.event){
				tecla = e.keyCode;
			}else if(e.which){
				tecla = e.which;
			}else{
				return true;
			}

			if((tecla >= "97") && (tecla <= "127")){
				return false;
			}
		}
	</script>
</head>
<body>
	<div class=noprint>
		<? include 'menu.php' ?>
	</div>
	<img src="js/loadingAnimation.gif" id="loading" style="width:300px; height: 20px; margin-left: -90px;">
	<center style="padding-top: 16px;"><h1>Imprimir Etiqueta</h1></center>
	<div id="mensagem-correio"><h4 id="mensagem-correio-h4">No campo "Números de Embarques" pode ser digitado vários embarque separados por virgula. Exemplo: 123456, 234567, 345678</h4></div>
	<div id="mensagem"></div>
	<center>
		<form class='form-inline ' method='post' name='frm_solicita_etiqueta' action='<?= $PHP_SELF ?>'>
			<div class="row-fluid">
		    	<div class="span5"></div>
		    	<div class="span7">
		    		<button href="#myModal" class="btn btn-primary btn-large" data-toggle="modal">Selecionar Embarques</button>
		     
				    <!-- Modal -->
				    <div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				      <div class="modal-header">
				        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				        <strong>Filtrar Por:</strong><br />
				        <button type="button" class="btn btn-info periodo" data-periodo="dia" style="width: 100px;margin-right: 10px;">Hoje</button>
				        <button type="button" class="btn btn-info periodo" data-periodo="semana" style="width: 100px;margin-right: 10px;">7 dias</button>
				        <button type="button" class="btn btn-info periodo" data-periodo="mes" style="width: 100px;margin-right: 10px;">30 dias</button>
				      </div>
				      <div class="modal-body">
				        <table class="table table-bordered table-striped table-hover" id="tabela_embarques" hidden>
				        	<thead>
				        		<tr class="titulo_coluna">
				        			<th>Embarque<br />(clique para ver detalhes)</th>
				        			<th>Fábrica</th>
				        			<th>
				        				<label><strong>Todos</strong><br /><input type="checkbox" id="marcar_todos" /></label>
				        			</th>
				        		</tr>
				        	</thead>
				        	<tbody>

				        	</tbody>
				        </table>
				        <div class="alert alert-info alerta">
				        	Selecione o Período
				        </div>
				        <div class="alert alert-warning alerta_embarque" hidden>
				        	Nenhum embarque encontrado
				        </div>
				      </div>
				      <div class="modal-footer">
				      	<center>
				        	<button id="btn_salvar" class="btn" data-dismiss="modal" aria-hidden="true">Salvar</button>
				    	</center>
				      </div>
				    </div>
		    	</div>
		    </div>
		    <div class='row-fluid'>
		        <div class='span2'></div>

	               <!-- Button to trigger modal -->
		        <div class='span8'>
		            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
		                <label class='control-label' for='numero_embarque'>Números dos Embarques</label>
		                <div class='controls controls-row'>
		                    <div class='span12 input-append'>
		                        <h5 class='asteristico'>*</h5>
		                            <input type="text" name="numero_embarque" id="numero_embarque" class='span12' onkeypress='return SomenteNumero(event)' value="<?=$numero_embarque?>" >
		                    </div>
		                </div>
		            </div>
		        </div>
		        <div class='span2'></div>
		    </div>
		    <p><br/>
		    	<input class="btn btn-default" type="button" name="solicitar_etiqueta" id="solicitar_etiqueta" value="Enviar" data-loading-text="Enviando" onclick="solicitarEtiqueta()" >
			</p><br/>
			<table border=1 align='center' class='table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class='titulo_coluna'>
						<th nowrap rowspan='2'>Embarque</th>
						<th nowrap rowspan='2'>Serviço de Entrega</th>
						<th nowrap rowspan='2'>Código do Serviço</th>
						<th nowrap rowspan='2'>Peso</th>
						<th nowrap rowspan='2'>Caixa</th>
						<th nowrap rowspan='2'>Prazo de Entrega</th>
					</tr>
				</thead>
				<tbody id="tabela_gerar_etiqueta">
				</tbody>
			</table>
			<p><br/>
		    	<input class="btn btn-default" type="button" name="gerar_lista" id="gerar_lista" value="Imprimir Etiqueta(s)" data-loading-text="Imprimindo..." onclick="gerarEtiqueta()" >
			</p><br/>
		</form>
	</center>
</body>
<?php include'rodape.php'; ?>
