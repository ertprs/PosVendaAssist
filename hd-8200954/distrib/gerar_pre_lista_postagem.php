<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'cabecalho.php';
include 'autentica_usuario.php';

if (strlen ($login_posto) == 0) {
    header ("Location: http://www.telecontrol.com.br");
    exit;
}

?>
<html>
<head>
	<title>Pré-Lista de Postagem (PLP)</title>
	<link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
	<style type="text/css">
		.body {
		font-family : verdana;
		}
	</style>
	<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script>
		function loading(status) {
		    if ( status == 1 )
		        $('#loading').fadeIn();
		    else
		        $('#loading').fadeOut();
		}

		$(function() {
			loading(0);
			$(document).on("click", "span[rel=lupa]", function () {
				loading(1);
				var busca_plp = $("#busca_plp").val();

				$.ajax({
			        url: 'funcao_correio.php',
			        type: 'get',
			        data: {
				        plp: 	busca_plp,
				        funcao: "consultarPLP"
				    },
			        success: function(data){
			        	var mensagem;
			        	data = JSON.parse(data);
			        	$("#mensagem").html('');
			        	$('.tbody').html('');
			        	$.each(data,function(key, value){

			        		if(value.resultado == "false"){
		        				$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
			        		}else{
		        				$("#plp").val(value.plp);
		        				$("#cartao_postagem").val(value.cartao_postagem);

			        			var linha = "";
								linha += "<tr id='"+value.etiqueta+"'> \
										<td nowrap align='center'>"+value.etiqueta+"</td> \
										<td nowrap align='center'>"+value.cep+"</td> \
										<td nowrap align='center'>"+value.peso+"</td> \
										<td nowrap align='center'>"+value.preco+"</td> \
										<td nowrap align='center'>"+value.nota_fiscal+"</td> \
										<td nowrap align='center'>"+value.descricao+"</td> \
										<td nowrap align='center'>"+value.embarque+"</td> \
										<td nowrap align='center'></td></tr>";
								$('.tbody').append(linha);

			        		}
			        		$("#numero_objeto").val("");
		        			$("#numero_objeto").focus();
		        			loading(0);
			        	});
					}
				});
			});

			$("#numero_objeto").focus();

			$("#btn_gerar_plp").on("click",function(){
				$("#btn_gerar_plp").button("loading");
				if($("#plp").val() == "1"){
					$("#confirmacao_gera_plp").click();
				}else{
					imprimirPLP();
				}
			});

			$("#nao").on("click",function(){
				$("#btn_gerar_plp").button("reset");
			});

		});

		function inserirObjeto(){
			var plp = "";
		    var etiqueta_lancada = 0;
		    loading(1);
		    $("#salvar_objeto").button("loading");

		    if(($("#plp").val() == 1 || $("#plp").val() == "") && $('#numero_objeto').val() != ""){
			    $('.tbody > tr').each(function(key, value){
			    	if(value.id == $("#numero_objeto").val()){
			    		etiqueta_lancada = 1;
			    	}
			    });

			    if(etiqueta_lancada == 0){
			    	if($("#plp").val() != ""){
						plp = $("#plp").val();
					}else{
						plp = "INSERT";
					}

					$.ajax({
				        url: 'funcao_correio.php',
				        type: 'get',
				        data: {
					        plp: plp,
					        objeto: $("#numero_objeto").val(),
					        cartao_postagem: $("#cartao_postagem").val(),
					        funcao: "inserirObjetoPLP"
					    },
				        success: function(data){
				        	var mensagem;
				        	data = JSON.parse(data);
				        	$("#mensagem").html('');
				        	$.each(data,function(key, value){

				        		if(value.resultado == "false"){
			        				$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
				        		}else{
				        			if($("#plp").val() == ""){
				        				$("#plp").val(value.plp);
				        			}

				        			if($("#cartao_postagem").val() == ""){
				        				$("#cartao_postagem").val(value.cartao_postagem);
				        			}

				        			var linha = "";
									linha += "<tr id='"+value.etiqueta+"'> \
											<td nowrap align='center'>"+value.etiqueta+"</td> \
											<td nowrap align='center'>"+value.cep+"</td> \
											<td nowrap align='center'>"+value.peso+"</td> \
											<td nowrap align='center'>"+value.preco+"</td> \
											<td nowrap align='center'>"+value.nota_fiscal+"</td> \
											<td nowrap align='center'>"+value.descricao+"</td> \
											<td nowrap align='center'>"+value.embarque+"</td> \
											<td nowrap align='center'> \
												<input class='btn btn-default' type='button' name='excluir_objeto"+value.embarque+"' id='excluir_objeto"+value.embarque+"' value='Excluir' onclick=excluirObjeto('"+value.etiqueta+"') > \
											</td> \
										</tr>";
				        			$('.tbody').append(linha);

				        		}
				        		$("#numero_objeto").val("");
			        			$("#numero_objeto").focus();
								loading(0);
				        	});
							$("#salvar_objeto").button("reset");
						}
					});
			    }else{
			    	$("#mensagem").html('');
			    	$("#mensagem").html('<div class="alert alert-error"><h4>Etiqueta já lançada!</h4> </div>');
			    	$("#numero_objeto").val("");
	    			$("#numero_objeto").focus();
	    			loading(0);
	    			$("#salvar_objeto").button("reset");
			    }
			}else{
				$("#mensagem").html('');
			$("#mensagem").html('<div class="alert alert-error"><h4>Informar numero do objeto ou Essa PLP já está fechada, não pode ser lançado nova postagem na lista.</h4> </div>');
		    	$("#numero_objeto").val("");
    			$("#numero_objeto").focus();
    			loading(0);
    			$("#salvar_objeto").button("reset");
			}
		}

		function excluirObjeto(etiqueta){
			$('.tbody tr[id='+etiqueta+']').remove();
			$("#numero_objeto").focus();
		}

		function validarEmbarque(){
		    var etiqueta_lancada = "";
		    var cartao_postagem = $("#cartao_postagem").val();

		    if(typeof cartao_postagem != "undefined" && cartao_postagem.length > 0){
		    	loading(1);
			    $('.tbody > tr').each(function(key, value){
			    	if(etiqueta_lancada == ""){
			    		etiqueta_lancada = value.id;
			    	}else{
			    		etiqueta_lancada = etiqueta_lancada+","+value.id;
			    	}
			    });

			    if(etiqueta_lancada != ""){
			    	var plp = $("#plp").val();

					$.ajax({
				        url: 'funcao_correio.php',
				        type: 'get',
				        data: {
					        plp: plp,
					        etiquetas: etiqueta_lancada,
					        funcao: "validarEmbarque",
					        cartao_postagem: cartao_postagem 
					    },
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
					        			geraPlpAntesFechamento(plp, etiqueta_lancada, cartao_postagem);
					        		}
				        		}
				        	});
							$("#btn_gerar_plp").button("reset");
						}
					});
				}else{
					$("#mensagem").html('');
					$("#mensagem").html('<div class="alert alert-error"><h4>Não há etiqueta lançada!</h4> </div>');
					$("#btn_gerar_plp").button("reset");
				}
			}else{
				$("#mensagem").html('');
				$("#mensagem").html('<div class="alert alert-error"><h4>Não foi encontrado o número do cartão de postagem!</h4> </div>');
				$("#btn_gerar_plp").button("reset");
			}
		}

		function imprimirPLP(){
		    var etiqueta_lancada = "";
		    loading(1);

		    var cartao_postagem = $("#cartao_postagem").val();

		    $('.tbody > tr').each(function(key, value){
		    	if(etiqueta_lancada == ""){
		    		etiqueta_lancada = value.id;
		    	}else{
		    		etiqueta_lancada = etiqueta_lancada+","+value.id;
		    	}
		    });

		    if(etiqueta_lancada != ""){
		    	window.open("PhpSigep/exemplos/imprimirPlp.php?etiquetas="+etiqueta_lancada+"&cartao_postagem="+cartao_postagem+"&idplp="+$("#plp").val(), "_blank");
    			loading(0);
				$("#btn_gerar_plp").button("reset");
			}else{
				$("#mensagem").html('');
				$("#mensagem").html('<div class="alert alert-error"><h4>Não há etiqueta lançada!</h4> </div>');
				loading(0);
				$("#btn_gerar_plp").button("reset");
			}
		}

		function geraPlpAntesFechamento(plp, etiqueta_lancada, cartao_postagem){
			$.ajax({
		        url: 'PhpSigep/exemplos/fechaPlpVariosServicos.php',
		        type: 'post',
		        data: {
			        plp: plp,
			        etiquetas: etiqueta_lancada,
			        cartao_postagem: cartao_postagem
			    },
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
		        				
								$.ajax({
							        url: 'funcao_correio.php',
							        type: 'get',
							        data: { 
			        					idplp: value.idplp, 
			        					etiquetas: etiqueta_lancada,
			        					funcao: "gravaIdPLP"
			        				},
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
						 							window.open("PhpSigep/exemplos/imprimirPlp.php?etiquetas="+etiqueta_lancada+"&cartao_postagem="+cartao_postagem+"&idplp="+value.idplp, "_blank");
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
		function abrirJanela(){
			window.open ("https://www.google.com.br/webhp?sourceid=chrome-instant&ion=1&espv=2&ie=UTF-8#q=abrir%20nova%20janela%20javascript", "pareto", "height=640,width=1020,scrollbars=1");
		}
	</script>
	<script src="../bootstrap/js/bootstrap.js"></script>
</head>
<body>
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
		    <div class="modal-content">
		    	<div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			        <h4 class="modal-title">Tem certeza que gostaria de fechar a PLP?</h4>
		    	</div>
			    <div class="modal-body">
			      Uma vez fechada, não poderá mais lançar novos objetos na lista!
			    </div>
			    <div class="modal-footer">
			        <button type="button" class="btn btn-primary" id="sim" onclick="validarEmbarque()" data-dismiss="modal">Sim</button>
			        <button type="button" class="btn btn-default" id="nao" data-dismiss="modal">Não</button>
			    </div>
		    </div>
		</div>
	</div>
	<div class=noprint>
		<? include 'menu.php' ?>
	</div>
	<center style="padding-top: 16px;"><h1>Pré-Lista de Postagem (PLP)</h1></center>
	<center>
		<img src="js/loadingAnimation.gif" id="loading" style="width:300px; height: 20px; margin-left: -90px;">
	    <div id="mensagem"></div>
		<form class='form-inline ' method='post' name='frm_plp_etiqueta' action='<?= $PHP_SELF ?>'>
  			<a data-toggle="modal" hidden href="#myModal" id="confirmacao_gera_plp"></a>
  			<input type="hidden" id="cartao_postagem" name="cartao_postagem">
		    <div class='row-fluid'>
		        <div class='span2'></div>
		        <div class='span3'>
		            <div class='control-group'>
		                <label class='control-label' for='busca_plp'>Buscar PLP(Informe nº da PLP ou Cód. de Rastreio)</label>
		                <div class='controls controls-row'>
		                    <div class='span8 input-append'>
	                            <input type="text" name="busca_plp" id="busca_plp" class='span12' value="<?=$busca_plp?>" >
	                            <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
		                    </div>
		                </div>
		            </div>
		        </div>
		        <div class='span1'></div>
		        <div class='span2'>
		            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
		                <label class='control-label' for='numero_objeto'>Nº do Objeto</label>
		                <div class='controls controls-row'>
		                    <div class='span12 input-append'>
		                        <h5 class='asteristico'>*</h5>
	                            <input type="text" name="numero_objeto" id="numero_objeto" class='span12' onkeypress="if (window.event.keyCode==13) inserirObjeto()" value="<?=$numero_objeto?>" >
	                            <input type="hidden" name="plp" id="plp" class='span12' value="">
	                            <input type="hidden" name="etiqueta_plp" id="etiqueta_plp" class='span12' value="">
		                    </div>
		                </div>
		            </div>
		        </div>
		        <div class='span2'>
		            <div class='control-group'>
		                <div class='controls controls-row'>
		                    <div class='span12 input-append' style="margin-top: 20px;">
                            	<input class="btn btn-default" type="button" name="salvar_objeto" id="salvar_objeto" value="Salvar Objeto" data-loading-text="Salvando" onclick="inserirObjeto()" >
		                    </div>
		                </div>
		            </div>
		        </div>
		        <div class='span2'></div>
		    </div>
			<table border=1 align='center' class='table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class='titulo_coluna' id='titulo_coluna'>
						<th nowrap rowspan='2'>Nº do Objeto</th>
						<th nowrap rowspan='2'>CEP</th>
						<th nowrap rowspan='2'>Peso</th>
						<th nowrap rowspan='4'>Valor Declarado</th>
						<th nowrap rowspan='4'>Nota Fiscal</th>
						<th nowrap rowspan='4'>Tipo Serviço</th>
						<th nowrap rowspan='4'>Embarque</th>
						<th nowrap rowspan='2'></th>
					</tr>
				</thead>
				<tbody class="tbody"></tbody>
			</table>
			<div class='row-fluid'>
		        <div class='span4'></div>
		        <div class='span1'></div>
		        <div class='span2'>
		            <div class='control-group'>
		                <div class='controls controls-row'>
		                    <div class='span12 input-append' style="margin-top: 20px;">
                            	<input class="btn btn-default" type="button" name="btn_gerar_plp" id="btn_gerar_plp" data-loading-text="Gerando Lista" value="Gerar e Imprimir Lista" >
		                    </div>
		                </div>
		            </div>
		        </div>
		        <div class='span2'></div>
		    </div>
		</form>
	</center>
</body>
<?php include'rodape.php'; ?>
