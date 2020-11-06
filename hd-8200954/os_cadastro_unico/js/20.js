var COOKIES = document.cookie.split(';');
var idioma = COOKIES[COOKIES.findIndex(function(e) {if (e.indexOf('cook_idioma')>-1) {return true;}})].split('=')[1].toUpperCase().substr(0, 2);

var TRAD = {
  PT: {
    data_compra: 'Data Compra',
    data_reparo: 'Data Reparo'
  },
  ES: {
    data_compra: 'Fecha de Compra/fra',
    data_reparo: 'Fecha Reparación'
  }
};

function init() {
	$("#btn_lbm,.itens_os_bosch_header_label_2 img").click(function() {
		var seq = $(this).attr('rel');
		var produto = $.trim($("#produto_id").val());
		//var peca = $.trim($("#peca_referencia" + seq).val());

        if ($(this).attr('id') === 'btn_lbm') {
          // clicou no botão... pega a primeira linha vazia
          var inputPecas = $("#itens_os .div_input_text input.peca_itens"); // array com os inputs...
          inputPecas.each(function() {
            if ($(this).val() == '' && /\d+/.test($(this).attr('seq'))) {
              seq = $(this).attr('seq');
              return false;
            }
          });
        }

		if(produto.length > 0) {
			Shadowbox.open({
				content : "pesquisa_lista_basica.php?produto=" + produto + "&posicao=" + seq,
				player : "iframe",
				title : "Lista Basica",
				width : 800,
				height : 500
			});

			verificaAdicionaLinha();
		} else {
			alert('Informe um produto');
		}

	});
}

// void function para evitar erro js no retorno da pesquisa da lista básica
function atualiza_causa_defeito() {
  return true;
}



function verificaAdicionaLinha() {
    var preenchido = 0;
	var atendimento = $('#tipo_atendimento').val();

	$('input[name*="peca"].peca_itens').each(function(indice) {
		if($(this).val().length == 0)
			preenchido += 1;
	});
	if(preenchido <= 2 && atendimento != 12 && atendimento != 11)
		itens_os_adicionar();
}

function itens_os_adicionar() {
  var sequencia = parseInt($("#n_linhas_pecas").val());
  var item = '';

  item += $('<div>').append($("#div_peca__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
  item += $('<div>').append($("#div_qtde__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
  item += $('<div>').append($("#div_peca_id__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
  $("#itens_os_corpo").append(item);
  set_peca_itens_focus();
  $(".qtde_itens").numeric({ decimal: ',', negative: false });

  $("#n_linhas_pecas").val(sequencia+1);
  buscaPeca(sequencia);
  init();
}

function buscaPeca(seq) {

  var pecaInput = $("#peca"+seq);
  if (pecaInput.hasClass('ac_input'))
    return;

  var tipo_atendimento = $('select[name=tipo_atendimento] option:selected').val();

  var cond_atendimento = '&tipo_atendimento='+tipo_atendimento;

  var pecas_desconsidera = "";

  pecaInput.autocomplete('os_cadastro_unico_autocomplete.php?tipo=peca&produto=' + $('#produto_id').val() + cond_atendimento, {
    minChars: 3,
    delay:    300,
    width:    350,
    matchContains: true,
    formatItem:    function(row) {
      return row[1] + ' - ' + row[2];
    },
    formatResult:  function(row) {
      return row[0];
    }
  }).result(function(event, data, formatted) {
    let label = data[1] + ' - ' + data[2];
    var value = data[0];

    let ja_inserido = false;

	$('#peca' + seq).val("");

    $(".peca_itens").each(function(){

    	if ($.trim(label) == $.trim($(this).val()) && $.trim($(this).val()) != "") {
    		ja_inserido = true;
    	}

    });

    $(".msg_erro_peca_dupla").remove();
    
    if (ja_inserido) {
    	$('#peca' + seq).val("");
    	$("#itens_os_header_labels").after("<div class='msg_erro_peca_dupla' style='width: 680px;background-color: red;font-weight: bolder;color: white;'>Peça já inserida no formulário. Favor, alterar a quantidade da peça caso necessário.</div>");
    	return;
    }

    if (tipo_atendimento == 11) {
    	$(".qtde_itens").prop("readonly", true).val("1");
    } else if (tipo_atendimento == 12) {
    	$(".qtde_itens").val("1").prop("readonly", false);
    } else {
    	$(".qtde_itens").prop("readonly", false);
    }

    //$('#os_item' + seq).val(data[0]);
    $('#peca' + seq + '_id').val(data[0]);
    $('#peca' + seq + '_last').val(data[0]);
    $('#peca_referencia' + seq).val(data[1]);
    $('#peca' + seq).val(label);

    if (/^\d+$/.test($("#qtde"+seq).val()) === false)
      $("#qtde"+seq).val('1');

    verificaAdicionaLinha();

  });

}

function apagarItemPedido() {
	$('input[name*="peca_referencia"]').val('');
	$('input[name*="peca_descricao"]').val('');
	$('input[name*="qtde"]').val('');
}

function busca_atendimento_produto_familia() {
	var produto = $("#produto_referencia").val();

	$.ajax({
		url : 'ajax_os_cadastro_unico.php',
		type : "POST",
		data : "tipo=atendimento_pela_familia_produto&produto_referencia=" + produto,
		success : function(retorno) {
			$("#tipo_atendimento").html(retorno);
			return false;
		}
	});
}

function defeito_reclamado_pela_linha(){
	var produto = $("#produto_referencia").val();

	if(produto.length > 0){
		$.ajax({
			url : 'ajax_os_cadastro_unico.php',
			type : "POST",
			data : "tipo=defeito_reclamado_pela_linha&produto_referencia=" + produto,
			success : function(retorno) {
				$("#defeito_reclamado").html(retorno);
				return false;
			}
		});
	}
}

function reparo_pelo_produto(){
	var produto = $("#produto_referencia").val();

	if(produto.length > 0){
		$.ajax({
			url : 'ajax_os_cadastro_unico.php',
			type : "POST",
			data : "tipo=defeito_constatado_pelo_produto&produto_referencia=" + produto,
			success : function(retorno) {
				$("#defeito_constatado").html(retorno);
				return false;
			}
		});
	}
}

function defeito_constatado_pela_familia_produto(produto) {
	$.ajax({
		url : 'ajax_os_cadastro_unico.php',
		type : "POST",
		data : "tipo=defeito_constatado_pela_familia_produto&produto=" + produto,
		success : function(retorno) {
			$("#defeito_constatado").html(retorno);
			return false;
		}
	});
}
function set_peca_itens_focus() {
	$(".peca_itens").each(function() {
		$(this).focus(function() {
			var seq = parseInt($(this).attr('seq'));
			/*if(seq + 1 == parseInt($("#n_linhas_pecas").val())) {
				itens_os_adicionar();
				$('#peca' + seq).focus();
			}*/

			if(!$(this).attr("readonly")) {

				var tipo_atendimento   = $('select[name=tipo_atendimento] option:selected').val();
  				var cond_atendimento   = '&tipo_atendimento='+tipo_atendimento;

				$(this).autocomplete('os_cadastro_unico_autocomplete.php?tipo=peca&produto=' + $('#produto_id').val() + '' + cond_atendimento, {
					minChars : 3,
					delay : 350,
					width : 350,
					matchContains : true,
					formatItem : function(row) {
						return row[1] + ' - ' + row[2];
					},
					formatResult : function(row) {
						return row[0];
					}
				});

				$(this).result(function(event, data, formatted) {
					if(data[0] == ''){
						return false;
					}

					let ja_inserido = false;

					let label = data[1] + ' - ' + data[2];

					$('#' + $(this).attr('id')).val("");

				    $(".peca_itens").each(function(){

				    	if ($.trim(label) == $.trim($(this).val()) && $.trim($(this).val()) != "") {
				    		ja_inserido = true;
				    	}

				    });

				    $(".msg_erro_peca_dupla").remove();

				    if (ja_inserido) {
				    	$('#' + $(this).attr('id')).val("");
				    	$("#itens_os_header_labels").after("<div class='msg_erro_peca_dupla' style='width: 680px;background-color: red;font-weight: bolder;color: white;'>Peça já inserida no formulário. Favor, alterar a quantidade da peça caso necessário.</div>");
				    	return;
				    }

				    var tipo_atendimento = $('select[name=tipo_atendimento] option:selected').val();

				    if (tipo_atendimento == 11) {
				    	$(".qtde_itens").prop("readonly", true).val("1");
				    } else if (tipo_atendimento == 12) {
				    	$(".qtde_itens").val("1").prop("readonly", false);
				    } else {
				    	$(".qtde_itens").prop("readonly", false);
				    }

					$('#' + $(this).attr('id') + '_id').val(data[0]);
					$('#' + $(this).attr('id')).val(data[1] + ' - ' + data[2]);
					//$('#' + $(this).attr('id') + '_last').val($('#peca' + seq).val());
					$('#' + $(this).attr('id') + '_last').val(data[0]);
					$('#qtde' + seq).numeric();
					if (tipo_atendimento != 11 && tipo_atendimento != 12) {
						$('#qtde' + seq).val("1");
					}

					$('#qtde_lb' + seq).val(data[3]);

					verificaAdicionaLinha();

				});
			}
		});
	});
}

function identificacao() {

	var produto = $("#produto_referencia").val();
    var id_solucao_os = $("#id_solucao_os").val();
    var tipo_atendimento = $("#tipo_atendimento").val();
	if(produto.length > 0){
		$.ajax({
			url : 'ajax_os_cadastro_unico.php',
			type : "POST",
			data : "tipo=solucao_os&produto_referencia=" + produto+"&id_solucao_os="+id_solucao_os+"&tipo_atendimento="+tipo_atendimento,
			success : function(retorno) {
				$("#solucao_os").html(retorno);
				return false;
			}
		});
	}
}

function identificacao_2(){

	var solucao_os_selecionada = $("#solucao_os").val();
	
    $.ajax({
        url : 'ajax_os_cadastro_unico.php',
        type : "POST",
        data : "tipo=garantia_produto_ou_cortesia",
        success : function(retorno) {

            $("#solucao_os").html(retorno);
            $("#solucao_os").find("option[value="+solucao_os_selecionada+"]").prop("selected", true);

            return false;
        }
    });
}


function set_produto_focus() {
	$('#produto').focus(function() {

		let tipo_pesquisa = $(this).attr("tipo_pesquisa");

		if(!$(this).attr("readonly") && !$(this).hasClass('ac_input')) {
			$('#produto').autocomplete('os_cadastro_unico_autocomplete.php?tipo=produto&tipo_pesquisa='+tipo_pesquisa, {
				minChars : 3,
				delay : 300,
				width : 350,
				matchContains : true,
				formatItem : function(row) {
					return row[1] + ' - ' + row[2];
				},
				formatResult : function(row) {
					return row[0];
				}
			});

			$('#produto').result(function(event, data, formatted) {
				var altera = true;
				var label = data[1] + ' - ' + data[2];
				var value = data[0];
				var produto_anterior = $('#produto_id').val();
				var peca = 0;

				$('input[name*="peca_referencia"]').each(function(indice) {
					if($(this).val().length > 0)
						peca += 1;
				});

				//if($('#produto_last').val() != '' && $('#produto_last').val() != label) {
				if(peca > 0 && produto_anterior != data[0]) {
					var pergunta = confirm("Deseja alterar o produto selecionado?\n\nATENÇÃO: informações de ANÁLISE DA OS e ITENS DA ORDEM DE SERVIÇO serão perdidas");

					if(pergunta) {
						apagarItemPedido();

						$('#produto_id').val(data[0]);
						$('#produto').val(label);
						$('#produto_last').val(label);
						$('#produto_referencia').val(data[1]);
						$('#voltagem').val(data[4]);

						//busca_atendimento_produto_familia(data[0]);
						defeito_constatado_pela_familia_produto(data[0]);
						//defeito_reclamado_pela_linha();
						reparo_pelo_produto();
					} else {
						$('#produto').val($('#produto_last').val());
					}
				}else{
					apagarItemPedido();

					$('#produto_id').val(data[0]);
					$('#produto').val(label);
					$('#produto_last').val(data[0]);
					$('#produto_referencia').val(data[1]);
					$('#voltagem').val(data[4]);

					//busca_atendimento_produto_familia(data[0]);
					defeito_constatado_pela_familia_produto(data[0]);
					//defeito_reclamado_pela_linha();
					reparo_pelo_produto();
					identificacao();
				}
			});
		}
	});
}

/**
function set_revenda_cnpj_focus() {
	$().ready(function() {
		$('#revenda_cnpj').focus(function() {
			$('#revenda_cnpj').autocomplete('os_cadastro_unico_autocomplete.php?tipo=revenda_cnpj', {
				minChars : 8,
				delay : 150,
				width : 350,
				matchContains : true,
				formatItem : function(row) {
					return row[0] + ' - ' + row[1] + ' - ' + row[4] + ' - ' + row[8] + ' - ' + row[9];
				},
				formatResult : function(row) {
					return row[0];
				}
			});

			$('#revenda_cnpj').result(function(event, data, formatted) {
				$('#revenda_cnpj').val(data[0]);
				$('#revenda_nome').val(data[1]);
				$('#revenda_fone').val(data[2]);
				$('#revenda_cep').val(data[3]);
				$('#revenda_endereco').val(data[4]);
				$('#revenda_numero').val(data[5]);
				$('#revenda_complemento').val(data[6]);
				$('#revenda_bairro').val(data[7]);
				$('#revenda_cidade').val(data[8]);
				$('#revenda_estado').val(data[9]);
				$('#revenda_cidade_estado').val(data[8]+' - '+data[9]);
			});
		});
	});
}

function set_revenda_nome_focus() {
	$().ready(function() {
		$('#revenda_nome').focus(function() {
			$('#revenda_nome').autocomplete('os_cadastro_unico_autocomplete.php?tipo=revenda_nome', {
				minChars : 3,
				delay : 150,
				width : 350,
				matchContains : true,
				formatItem : function(row) {
					return row[0] + ' - ' + row[1] + ' - ' + row[4] + ' - ' + row[8] + ' - ' + row[9];
				},
				formatResult : function(row) {
					return row[0];
				}
			});

			$('#revenda_nome').result(function(event, data, formatted) {
				$('#revenda_cnpj').val(data[0]);
				$('#revenda_nome').val(data[1]);
				$('#revenda_fone').val(data[2]);
				$('#revenda_cep').val(data[3]);
				$('#revenda_endereco').val(data[4]);
				$('#revenda_numero').val(data[5]);
				$('#revenda_complemento').val(data[6]);
				$('#revenda_bairro').val(data[7]);
				$('#revenda_cidade').val(data[8]);
				$('#revenda_estado').val(data[9]);
				$('#revenda_cidade_estado').val(data[8]+' - '+data[9]);
			});
		});
	});
}
*/

function set_defeito_constatado_focus() {
	$(".defeito_constatado_analise").each(function() {
		$(this).focus(function() {
			var seq = parseInt($(this).attr('seq'));

			$(this).keyup(function() {
				if($(this).val() == '') {
					$("#solucao_os" + seq).html('');
				}
			});

			$(this).autocomplete('os_cadastro_unico_autocomplete.php?tipo=defeito_constatado&produto=' + $('#produto_id').val() + '', {
				minChars : 3,
				delay : 250,
				width : 350,
				matchContains : true,
				formatItem : function(row) {
					return row[1] + ' - ' + row[2];
				},
				formatResult : function(row) {
					return row[0];
				}
			});

			$(this).result(function(event, data, formatted) {
				if(data[0] == '')
					return false;

				$('#' + $(this).attr('id') + '_id').val(data[0]);
				$(this).val(data[1] + ' - ' + data[2]);
				$('#' + $(this).attr('id') + '_last').val(data[1] + ' - ' + data[2]);
				$("#solucao_os" + seq).html('');

				$.ajax({
					type : "GET",
					url : "os_cadastro_unico_ajax.php",
					data : "tipo=solucao_os&defeito_constatado=" + data[0] + "&produto=" + $("#produto_id").val(),
					dataType : "text/html",
					success : function(html) {
						$("#solucao_os" + seq).html(html);
					}
				});
			    return;
			});
		});
	});
}

function gravar_os() {
	$("#btn_acao").val("gravar");
	$("#frm_os").submit();
}

function mostra_nf(url) {

	if($("#div_mostra_imagem").css("display") == "none") {
		//$("#div_mostra_imagem").html("<a href='" + url + "' rel='shadowbox'><img src='" + url + "' /></a>");
		$("#div_mostra_imagem").css("display", "block");
		$("#btn_mostra_nf").val("Fechar imagem");
	} else {
		$("#div_mostra_imagem").css("display", "none");
		$("#btn_mostra_nf").val("Mostrar imagem");
	}
}

function tipo_atendimento(){
	$("select[name=tipo_atendimento]").change(function() {
		verifica_atendimento();
	});
    $(".imprimir_os").hide();
}

function limpa_valores(){
    $("#serie").val('');
    $("#produto").val('');
    $("#voltagem").val('');
    $("#data_nf").val('');
    $("#nota_fiscal").val('');
    $("#consumidor_nome").val('');
    $("#consumidor_cpf").val('');
    $("#consumidor_fone").val('');
    $("#consumidor_celular").val('');
    $("#consumidor_email").val('');
    $("#solucao_os").val('');
    $("#causa_defeito").val('');
    $("#defeito_constatado").val('');
    $("input[name^='peca']").val('');
    $("input[name^='qtde']").val('');
    $("#obs").val('');
    $("#promotor_treinamento2").val('');
    $("#motivo_ordem").val('');
    $("input[name^='descricao_peca_']").val('');
    $("input[name^='codigo_peca_']").val('');
    $("input[name^='numero_pedido_']").val('');
    $("input[name='linha_medicao']").val('');
    $("input[name='ci_solicitante']").val('');
    $("#input[name='protocolo']").val('');
    $("#input[name='pedido_nao_fornecido']").val('');

    $("#input[name='contato_sac']").val(''); //HD-3200578
    $("#input[name='detalhe']").val(''); //HD-3200578

}

function verifica_atendimento(){
	var tipo_atendimento = $('select[name=tipo_atendimento] option:selected').val();

	//esta regra também é validada no PHP
    $("#aprovacao_reponsavel").hide();
	$("#aprovacao_reponsavel2").hide();
    $("#consumidor_cpf").removeAttr('required');
    $("#div_consumidor_cpf,[for='consumidor_cpf']").removeClass('obrigatorio');

    switch (parseInt(tipo_atendimento)) {
        case 10:
        	if ($("#sua_os").length == 0) {
           	 	limpa_valores();
        	}
            $("#div_data_nf").find('label').text(TRAD[idioma].data_compra);
            $("#div_defeito_constatado").show();
            $("#defeito_constatado").val('');
            $("#area_motivo_ordem").hide();
            $('label[for="nota_fiscal"]').addClass('obrigatorio');
            $('label[for="consumidor_email"]').removeClass('obrigatorio');
            $("#DIVAnexo").find('label').addClass('obrigatorio');
            // $("#serie").val("");
            // $("#produto").val("");
            // $("#produto_id").val("");
            // $("#produto_last").val("");
            // $("#produto_referencia").val("");
            // $("#defeito_constatado").val('');
            $("#div_consumidor_cpf").hide();
            $('#serie').prop('type','text').show();
            $('label[for="serie"]').show();
            $('#produto').prop('type','text');
            $('label[for="produto"]').show();
            $('#voltagem').prop('type','text');
            $('label[for="voltagem"]').show();
            $("#itens_os,#itens_os_adicionar_linha").show();
            // $("#promotor_treinamento2").val('');
            // $("#motivo_ordem").val('');

            identificacao_2();
        break;

        case 11:
            limpa_valores();
            $("#produto").val("0000002 - Garantia de Peças");
            $("#produto_id").val("20568");
            $("#produto_last").val("20568");
            $('label[for="nota_fiscal"]').addClass('obrigatorio');
            $("#DIVAnexo").find('label').addClass('obrigatorio');
            $("#serie").val("999");
            $("#produto_referencia").val("0000002");
            $('#produto').focus();
            $('label[for="data_nf"]').addClass('obrigatorio');
            $('label[for="consumidor_email"]').removeClass('obrigatorio');
            $("#div_data_nf").find('label').html(TRAD[idioma].data_compra);
            if ($("#defeito_constatado option").length == 0) {
                reparo_pelo_produto();
            }
            $("#itens_os,#itens_os_adicionar_linha").show();
            $("#btn_lbm").hide();
            $("#area_motivo_ordem").hide();

            //hd_chamado=2843341
            $("#div_consumidor_cpf").hide();
            $("#dados_produto").show();
            //$('#serie').clone().attr('type','hidden').insertAfter('#serie').prev().remove();
            $('#serie').prop('type','hidden');
            $('label[for="serie"]').hide();

            //$('#produto').clone().attr('type','hidden').insertAfter('#produto').prev().remove();
            $('#produto').prop('type','hidden');
            $('label[for="produto"]').hide();

            //$('#voltagem').clone().attr('type','hidden').insertAfter('#voltagem').prev().remove();
            $('#voltagem').prop('type','hidden');
            $('label[for="voltagem"]').hide();

            $("#promotor_treinamento2").val('');
            $("#motivo_ordem").val('');
            $("#div_defeito_constatado").hide();
            setTimeout(function(){ //hd_chamado=2843341
                $("#defeito_constatado").val('12845');
            }, 500);
            identificacao();
            // fim hd_chamado
        break;

        case 12:
            limpa_valores();
            $("#area_motivo_ordem").hide();
            $("#produto").val("0000001 - Garantia de Acessórios");
            $("#produto_id").val("20567");
            $("#produto_last").val("20567");
            $('label[for="data_nf"]').addClass('obrigatorio');
            $('label[for="consumidor_email"]').removeClass('obrigatorio');
            $("#div_data_nf").find('label').html(TRAD[idioma].data_compra);
            $("#serie").val("999");
            $('label[for="nota_fiscal"]').addClass('obrigatorio');
            $("#DIVAnexo").find('label').addClass('obrigatorio');
            $("#produto_referencia").val("0000001");

            $('#produto').focus();
            if ($("#defeito_constatado option").length == 0) {
                reparo_pelo_produto();
            }
            $("#itens_os").show();
            $("#dados_produto").show();
            $("#itens_os_adicionar_linha").hide();
            identificacao();

            //hd_chamado=2843341
            $("#div_consumidor_cpf").hide();
            //$('#serie').clone().attr('type','hidden').insertAfter('#serie').prev().remove();

            //hd_chamado=3120742
            //$('#serie').prop('type','hidden');
            //$('label[for="serie"]').hide();

            //$('#produto').clone().attr('type','hidden').insertAfter('#produto').prev().remove();
            $('#produto').prop('type','hidden');
            $('label[for="produto"]').hide();

            //$('#voltagem').clone().attr('type','hidden').insertAfter('#voltagem').prev().remove();
            $('#voltagem').prop('type','hidden');
            $('label[for="voltagem"]').hide();
             $("#promotor_treinamento2").val('');
            $("#motivo_ordem").val('');
            $("#div_defeito_constatado").hide();
            $("#btn_lbm").hide();
            setTimeout(function(){ //hd_chamado=2843341
                $("#defeito_constatado").val('12845');
            }, 500);
        break;

        case 13:

        	if ($("#sua_os").length == 0) {
	            limpa_valores();
	            $("#produto").val("");
	            $("#produto_id").val("");
	            $("#produto_last").val("");
	            $("#produto_referencia").val("");
	        }

            $('label[for="nota_fiscal"]').addClass('obrigatorio');
            $("#DIVAnexo").find('label').addClass('obrigatorio');
            $("#div_data_nf").find('label').html(TRAD[idioma].data_compra);
            $("#defeito_constatado").val('');
            $(".div_motivo_ordem").show();
            $('#serie').prop('type','text').show();
            $('label[for="serie"]').show();
            $('#produto').prop('type','text');
            $('label[for="produto"]').show();
            $('#voltagem').prop('type','text');
            $('label[for="voltagem"]').show();

            $("#div_consumidor_cpf").show();
            $('label[for="consumidor_cpf"]').addClass('obrigatorio');
            $("#aprovacao_reponsavel2").show();
	        

            $("#itens_os").hide();

            $("#div_defeito_constatado").hide();
            setTimeout(function(){ //hd_chamado=2843341
                $("#defeito_constatado").val('12845');
            }, 500);

            $('label[for="consumidor_email"]').addClass('obrigatorio');

            identificacao_2();
        break;

        case 14:
            limpa_valores();
            $("#promotor_treinamento2").val('');
            $("#motivo_ordem").val('');
            $("#produto").val("");
            $("#produto_id").val("");
            //$("#div_data_nf").find('label').html(TRAD[idioma].data_reparo);
            $("#produto_last").val("");
            $("#produto_referencia").val("");
            $("#defeito_constatado").val('');
            $('label[for="nota_fiscal"]').removeClass('obrigatorio');
            $("#DIVAnexo").find('label').removeClass('obrigatorio');
                $('#serie').prop('type','text').show();
            $('label[for="serie"]').show();
            $("#div_consumidor_cpf").hide();
                $('#produto').prop('type','text');
            $('label[for="produto"]').show();
                $('#voltagem').prop('type','text');
            $('label[for="voltagem"]').show();
            $('label[for="consumidor_email"]').removeClass('obrigatorio');
            $("#div_defeito_constatado").show();
            $("#defeito_constatado").val('');
            $("#area_motivo_ordem").hide();
            identificacao_2();
        break;

        case 16:
            limpa_valores();
            $("#produto").val("");
            $("#produto_id").val("");
            $("#produto_last").val("");
            $("#div_data_nf").find('label').html(TRAD[idioma].data_compra);
            $("#produto_referencia").val("");
            $("#defeito_constatado").val('');
            $('label[for="nota_fiscal"]').removeClass('obrigatorio');
            $("#DIVAnexo").find('label').removeClass('obrigatorio');
                $('#serie').prop('type','text').show();
            $('label[for="serie"]').show();
            $("#div_consumidor_cpf").hide();
                $('#produto').prop('type','text');
            $('label[for="produto"]').show();
                $('#voltagem').prop('type','text');
            $('label[for="voltagem"]').show();

            $("#div_defeito_constatado").show();
            $("#defeito_constatado").val('');
            $("#aprovacao_reponsavel2").show();
            $(".div_motivo_ordem").hide();
            $("#area_motivo_ordem").hide();
            $('label[for="consumidor_email"]').addClass('obrigatorio');

            identificacao_2();
        break;

        case 66:
            limpa_valores();
            if (/^0{6}[1-3]/.test($("#produto").val())) {
              $("#produto_referencia,#produto_id,#voltagem,#produto_last,#produto,#serie").val('');
            }
            $("#itens_os").hide();
            $("#div_data_nf").find('label').html(TRAD[idioma].data_compra);
            $('label[for="consumidor_email"]').addClass('obrigatorio');

            $("#div_consumidor_cpf").show();
            $('label[for="consumidor_cpf"]').addClass('obrigatorio');
            $('label[for="nota_fiscal"]').removeClass('obrigatorio');
//             $('label[for="data_nf"]').removeClass('obrigatorio');
            $("#DIVAnexo").find('label').removeClass('obrigatorio');
            $("#aprovacao_reponsavel2").show();//alterado para show era hide
            //$("#aprovacao_reponsavel").show();// esta liberado
            $("#dados_produto").show();
            $(".div_motivo_ordem").show();
            $("#div_defeito_constatado").hide();
                setTimeout(function(){ //hd_chamado=2843341
                $("#defeito_constatado").val('12845');
            }, 500);
            identificacao_2();
        break;

        default:
            limpa_valores();
            $("#div_consumidor_cpf").hide();
            $("#aprovacao_reponsavel").hide();
            $("#aprovacao_reponsavel2").hide();
            $("#div_data_nf").find('label').html(TRAD[idioma].data_compra);
            $("#dados_produto").show();
            $("#area_motivo_ordem").hide();
            $("#div_consumidor_cpf").show();
            $('label[for="consumidor_email"]').removeClass('obrigatorio');
            $("#itens_os,#itens_os_adicionar_linha").show();
            if (tipo_atendimento == 15 || tipo_atendimento == 16) {
              $("#aprovacao_reponsavel2").show();
            }
            if (/^0{6}[1-4]/.test($("#produto").val())) {
              $("#produto_referencia,#produto_id,#voltagem,#produto_last,#produto,#serie").val('');
            }
    }
    if ($("#sua_os").length == 0) {
    	$("#serie").blur();
	}
}

function verifica_motivo_ordem(){

    var motivo_ordem = $("#motivo_ordem").val();
    $("#area_motivo_ordem").show();
    if(motivo_ordem != ""){
        if(motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
            $("#peca_nao_disponivel").show();
            $("#nao_existe_pecas").hide();
            $("#procon").hide();
            $("#solicitacao_fabrica").hide();
            $("#pedido_nao_fornecido").hide();
            $("#linha_medicao").hide();
            $("#contato_sac").hide();
            $("#detalhe").hide();
        }
        if(motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
            $("#nao_existe_pecas").show();
            $("#peca_nao_disponivel").hide();
            $("#procon").hide();
            $("#solicitacao_fabrica").hide();
            $("#pedido_nao_fornecido").hide();
            $("#linha_medicao").hide();
            $("#contato_sac").hide();
            $("#detalhe").hide();
        }
        if(motivo_ordem == 'PROCON (XLR)'){
            $("#procon").show();
            $("#peca_nao_disponivel").hide();
            $("#nao_existe_pecas").hide();
            $("#solicitacao_fabrica").hide();
            $("#pedido_nao_fornecido").hide();
            $("#linha_medicao").hide();
            $("#contato_sac").hide();
            $("#detalhe").hide();
        }
        if(motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
            $("#solicitacao_fabrica").show();
            $("#peca_nao_disponivel").hide();
            $("#nao_existe_pecas").hide();
            $("#procon").hide();
            $("#pedido_nao_fornecido").hide();
            $("#linha_medicao").hide();
            $("#contato_sac").hide();
            $("#detalhe").hide();
        }
        if(motivo_ordem == "Linha de Medicao (XSD)"){
            $("#solicitacao_fabrica").hide();
            $("#peca_nao_disponivel").hide();
            $("#nao_existe_pecas").hide();
            $("#procon").hide();
            $("#pedido_nao_fornecido").hide();
            $("#linha_medicao").show();
            $("#contato_sac").hide();
            $("#detalhe").hide();
        }
        if(motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
            $("#solicitacao_fabrica").hide();
            $("#peca_nao_disponivel").hide();
            $("#nao_existe_pecas").hide();
            $("#procon").hide();
            $("#pedido_nao_fornecido").show();
            $("#linha_medicao").hide();
            $("#contato_sac").hide();
            $("#detalhe").hide();
        }

        if(motivo_ordem == 'Contato SAC (XLR)'){ //HD-3200578
        	$("#contato_sac").show();
        	$("#detalhe").hide();
            $("#procon").hide();
            $("#peca_nao_disponivel").hide();
            $("#nao_existe_pecas").hide();
            $("#solicitacao_fabrica").hide();
            $("#pedido_nao_fornecido").hide();
            $("#linha_medicao").hide();
        }

        if(motivo_ordem == 'Bloqueio financeiro (XSS)' || motivo_ordem == 'Ameaca de Procon (XLR)' || motivo_ordem == 'Defeito reincidente (XQR)'){ //HD-3200578
        	$("#detalhe").show();
        	$("#contato_sac").hide();
        	$("#procon").hide();
            $("#peca_nao_disponivel").hide();
            $("#nao_existe_pecas").hide();
            $("#solicitacao_fabrica").hide();
            $("#pedido_nao_fornecido").hide();
            $("#linha_medicao").hide();
        }

    }
}

function oculta_campos(){
    var tipo_atendimento = $('select[name=tipo_atendimento] option:selected').val();
    $("#aprovacao_reponsavel").hide();
    $("#aprovacao_reponsavel2").hide();
    $("#consumidor_cpf").removeAttr('required');
    $("#div_consumidor_cpf,[for='consumidor_cpf']").removeClass('obrigatorio');
    $(".imprimir_os").hide();
    switch (parseInt(tipo_atendimento)) {
        case 10:
            $("#div_defeito_constatado").show();
            $("#area_motivo_ordem").hide();
            $("#div_consumidor_cpf").hide();
            $('#serie').prop('type','text').show();
            $('label[for="serie"]').show();
            $('#produto').prop('type','text');
            $('label[for="produto"]').show();
            $('#voltagem').prop('type','text');
            $('label[for="voltagem"]').show();
            $("#DIVAnexo").find('label').addClass('obrigatorio');
        break;

        case 11:
            $("#produto").val("0000002 - Garantia de Peças");
            $("#DIVAnexo").find('label').addClass('obrigatorio');
            $("#produto_id").val("20568");
            $("#produto_last").val("20568");
            $("#serie").val("999");
            $("#produto_referencia").val("0000002");
            $('#produto').focus();
            if ($("#defeito_constatado option").length == 0) {
                reparo_pelo_produto();
            }
            $("#itens_os,#itens_os_adicionar_linha").show();
            $("#btn_lbm").hide();

            $("#area_motivo_ordem").hide();

            $("#div_consumidor_cpf").hide();
            $("#dados_produto").show();
            $('#serie').prop('type','hidden');
            $('label[for="serie"]').hide();
            $('#produto').prop('type','hidden');
            $('label[for="produto"]').hide();
            $('#voltagem').prop('type','hidden');
            $('label[for="voltagem"]').hide();

            $("#div_defeito_constatado").hide();
            setTimeout(function(){ //hd_chamado=2843341
                $("#defeito_constatado").val('12845');
            }, 500);

        break;

        case 12:
            $("#area_motivo_ordem").hide();
            $("#DIVAnexo").find('label').addClass('obrigatorio');
            $("#produto").val("0000001 - Garantia de Acessórios");
            $("#produto_id").val("20567");
            $("#produto_last").val("20567");
            //hd_chamado=3120742
            //$("#serie").val("999");
            $("#produto_referencia").val("0000001");
            $('#produto').focus();
            if ($("#defeito_constatado option").length == 0) {
                reparo_pelo_produto();
            }
            $("#itens_os").show();
            $("#itens_os_adicionar_linha").hide();
            $("#dados_produto").show();
            identificacao();
            $("#btn_lbm").hide();
            //hd_chamado=2843341
            $("#div_consumidor_cpf").hide();

            //hd_chamado=3120742
            // $('#serie').prop('type','hidden');
            // $('label[for="serie"]').hide();
            $('#produto').prop('type','hidden');
            $('label[for="produto"]').hide();
            $('#voltagem').prop('type','hidden');
            $('label[for="voltagem"]').hide();

            $("#div_defeito_constatado").hide();
            setTimeout(function(){ //hd_chamado=2843341
                $("#defeito_constatado").val('12845');
            }, 500);
        break;

        case 13:
            $('#serie').prop('type','text').show();
            $("#DIVAnexo").find('label').addClass('obrigatorio');
            $('label[for="serie"]').show();
            $('#produto').prop('type','text');
            $('label[for="produto"]').show();
            $('#voltagem').prop('type','text');
            $('label[for="voltagem"]').show();

            $("#div_consumidor_cpf").show();
            $('label[for="consumidor_cpf"]').addClass('obrigatorio');
            $("#aprovacao_reponsavel2").show();
            $("#itens_os").hide();
            $("#div_defeito_constatado").hide();
            setTimeout(function(){ //hd_chamado=2843341
                $("#defeito_constatado").val('12845');
            }, 500);
        break;

        case 14:
            $("#div_consumidor_cpf").hide();
            $('#serie').prop('type','text').show();
            $('label[for="serie"]').show();
            $('#produto').prop('type','text');
            $('label[for="produto"]').show();
            $('#voltagem').prop('type','text');
            $('label[for="voltagem"]').show();
            $("#div_defeito_constatado").show();
            $("#area_motivo_ordem").hide();
            identificacao_2();
        break;

        case 16:
            $("#div_consumidor_cpf").hide();
            $('#serie').prop('type','text').show();
            $('label[for="serie"]').show();
            $('#produto').prop('type','text');
            $('label[for="produto"]').show();
            $('#voltagem').prop('type','text');
            $('label[for="voltagem"]').show();
            $("#div_defeito_constatado").show();
            $("#area_motivo_ordem").hide();
            $("#aprovacao_reponsavel2").show();
            $(".div_motivo_ordem").hide();
        break;

        case 66:
            $("#itens_os").hide();
            $("#div_consumidor_cpf").show();
            $('label[for="consumidor_cpf"]').addClass('obrigatorio');
            $("#aprovacao_reponsavel2").show();//alterado para show era hide
            $("#dados_produto").show();
            $("#div_defeito_constatado").hide();
                setTimeout(function(){ //hd_chamado=2843341
                $("#defeito_constatado").val('12845');
            }, 500);
        break;
        default:
            $("#div_consumidor_cpf").hide();
            $("#aprovacao_reponsavel").hide();
            $("#aprovacao_reponsavel2").hide();
            $("#dados_produto").show();
            $("#area_motivo_ordem").hide();
            $("#itens_os,#itens_os_adicionar_linha").show();
            if (tipo_atendimento == 15 || tipo_atendimento == 16) {
              $("#aprovacao_reponsavel2").show();
            }
            if (/^0{6}[1-4]/.test($("#produto").val())) {
              $("#produto_referencia,#produto_id,#voltagem,#produto_last,#produto,#serie").val('');
            }
    }
}

function bloqueio_campos() {

	try{
        var os = $("#sua_os").val().length;
        var data_hora_fechamento = $("#data_hora_fechamento").val().trim();
        var valida_fone = $("#consumidor_fone").val();
        var valida_cel = $("#consumidor_celular").val();
        var tipo_atendimento = $("#tipo_atendimento").val();
        if(os && os > 0){
            /*
            if(data_hora_fechamento.length > 0){
                $("#data_hora_fechamento").prop("readonly", true);
                $("#btn_gravar_os").hide();
            }
            */
			$('#dados_os input, #dados_os select').addClass('bloqueado');
			$('#dados_produto input, #dados_produto select').addClass('bloqueado');
            $("#tipo_atendimento").removeClass('bloqueado');
            $("#data_nf").prop({readonly: true});
			$('#dados_revenda input, #dados_revenda select').addClass('bloqueado');
			$('#btn_mostra_nf').removeClass();
            $(".imprimir_os").hide();

            if(tipo_atendimento == 10 || tipo_atendimento == 11 || tipo_atendimento == 12 || tipo_atendimento == 13){
                $('label[for="nota_fiscal"]').addClass('obrigatorio');
                $("#DIVAnexo").find('label').addClass('obrigatorio');
            }

            if(tipo_atendimento == 11 || tipo_atendimento == 12){
                $("#div_voltagem").hide();
                $("#btn_lbm").hide();
                $("#itens_os_adicionar_linha").hide();
            }

            if(tipo_atendimento == 16){
                $('.div_motivo_ordem').hide();
                $("#div_consumidor_cpf").hide();
            }

            if(tipo_atendimento == 13 || tipo_atendimento == 66){
                $("#itens_os").hide();
                if ($("#tipo_atendimento_gravado").val() != "13" && $("#tipo_atendimento_gravado").val() != "66") {
                	$("#promotor_treinamento2, #motivo_ordem, #area_motivo_ordem input").removeClass("bloqueado");
            	}
            }

            if(tipo_atendimento == 13 || tipo_atendimento == 16 || tipo_atendimento == 66){
                $('label[for="consumidor_email"]').addClass('obrigatorio');
            }

            if(valida_fone.length > 0){
                $("#div_consumidor_celular").find('label').removeClass();
            }
            if(valida_cel.length > 0 && valida_fone.length > 0){
                $("#div_consumidor_celular").find('label').removeClass();
            }

            if(valida_cel.length > 0 && valida_fone.length == 0){
                $("#div_consumidor_fone").find('label').removeClass();
            }

		}

	} catch(err){
		return false;
    }
}

function verifica_fone(){
    $("#consumidor_fone, #consumidor_celular").change(function() {
        var fone = $("#consumidor_fone").val().replace(/[^\d]+/g,'');
        var celular = $("#consumidor_celular").val().replace(/[^\d]+/g,'');

        if(celular.length > 0){
            $('label[for="consumidor_fone"]').removeClass('obrigatorio');
        }else{
            $('label[for="consumidor_fone"]').addClass('obrigatorio');
        }

        if(fone.length > 0){
            $('label[for="consumidor_celular"]').removeClass('obrigatorio');
        }else{
            $('label[for="consumidor_celular"]').addClass('obrigatorio');
        }

        if(celular.length > 0 && fone.length > 0){
            $('label[for="consumidor_celular"]').addClass('obrigatorio');
            $('label[for="consumidor_fone"]').addClass('obrigatorio');
        }

    });
}

$(document).ready(function() {

  init();

    $("#serie").blur(function() {
        var serie   = $(this).val();
		var pa_foto_serie_produto = $('#msgAnexoSerie').length;
        var tipo_at = parseInt($("#tipo_atendimento").val());

        if (serie == '999' && !(tipo_at == 11 || tipo_at == 12) && pa_foto_serie_produto) {
            $("#div_anexo_serie").show();

			Shadowbox.open({
				content : $("#msgAnexoSerie").html(),
				player : "html",
				title : "Anexo Obrigatório",
				displayNav: false,
				loading: false,
				width : 400,
				height : 200
			});
			$("#sb-body").css('background', '#fff url()');
        } else {
            $("#div_anexo_serie").hide();
        }
    });

  $("#itens_os_corpo").on('keyup', 'input:text[name^="peca"]', function() {
    var peca = $.trim($(this).val());
    var produto = $.trim($('#produto_id').val());

    if(produto.length > 0) {
      if(peca.length > 2) {

        var seq = $(this).attr("seq");

        if(!$(this).attr("readonly")) {
          buscaPeca(seq);
        }
      }
    } else {
      $('input[name*="peca_referencia_descricao"]').val('');
      alert('Produto inválido.\nPreencha o campo PRODUTO.');
    }
    return false;
  });

    $("#consumidor_email").change(function(){
        var tipo_atendimento = $('select[name=tipo_atendimento] option:selected').val();
        var email = $(this).val();
        var emailReg = new RegExp(/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
        var valid = emailReg.test(email);
        if(tipo_atendimento == 13 || tipo_atendimento == 16 || tipo_atendimento == 66){
            if(!valid) {
                $(this).focus();
                alert('Email informado é invalido');
                return false;
            }
        }
      //  '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.(([0-9]{1,3})|([a-zA-Z]{2,3})|(aero|coop|info|museum|name))$/'
    });

    verifica_fone();
    //verifica_atendimento();

	function verificaLinha() {

		var ref = $("#produto").val().split (' ');

		$.get('os_cadastro_unico/fabricas/20.php?acao=getLinha&referencia=' + ref[0], function(data){

			obj = $('#defeito_reclamado').last();

	    	obj.find('option')
	    		.remove()
	    		.end()
	    	.append('<option value=""></option>');

			$(data).appendTo(obj).last();

		});

	}

	$("#produto").blur( function () {

		if ($(this).val() === '') {
			return false;
		}

		verificaLinha();

	});

	bloqueio_campos();

	// set_revenda_nome_focus();
	// set_revenda_cnpj_focus();
	set_produto_focus();

	tipo_atendimento();

	Shadowbox.init();
    $("#serie").numeric();
    $("#voltagem").prop("readonly", true);

    $("#nota_fiscal").keyup(function() {
        var nota_fiscal = $("#nota_fiscal").val().replace(/[^a-zA-Z 0-9]+/g,'');
        $("#nota_fiscal").val(nota_fiscal);
    });

    $("#consumidor_nome").keyup(function() {
        var consumidor_nome = $("#consumidor_nome").val().replace(/[^a-zA-Z 0-9]+/g,'');
        $("#consumidor_nome").val(consumidor_nome);
    });

    //$("#consumidor_cpf").numeric();

	$("#defeito_reclamado_descricao").css("width", "298px");
	$("#tipo_atendimento").css("width", "220px");
	$("#defeito_constatado").css("width", "200px");
	$("#horas_trabalhadas").css("width", "104px");
    $("input[name=foto_nf]").css("width", "360px");
	$("#defeito_reclamado_descricao").keyup(function() {
		somenteMaiusculaSemAcento(document.getElementById($(this).attr("id")));
	});

	$("#consumidor_nome").keyup(function() {
		somenteMaiusculaSemAcento(document.getElementById($(this).attr("id")));
	});
	/*
	$("#consumidor_cidade").keyup(function() {
		somenteMaiusculaSemAcento(document.getElementById($(this).attr("id")));
	});

	$("#revenda_cidade").keyup(function() {
		somenteMaiusculaSemAcento(document.getElementById($(this).attr("id")));
	});
	*/
	$("#consumidor_cep").blur(function() {
		//if($("#consumidor_endereco").val() == "" && $("#consumidor_bairro").val() == "" && $("#consumidor_cidade").val() == "") {
			buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado);
			setTimeout('$("#consumidor_cidade_estado").val($("#consumidor_cidade").val()+" - "+$("#consumidor_estado").val());', 700);
		//}
	});

	$("#revenda_cep").blur(function() {
		//if($("#revenda_endereco").val() == "" && $("#revenda_bairro").val() == "" && $("#revenda_cidade").val() == "") {
			buscaCEP(this.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado);
			setTimeout('$("#revenda_cidade_estado").val($("#revenda_cidade").val()+" - "+$("#revenda_estado").val());', 700);
		//}
	});

	$("#tipo_atendimento").change(function() {
		if($(this).val() == "2") {
			$("#dados_km").css("display", "block");
		} else {
			$("#dados_km").css("display", "none");
		}
	});

	if($("#tipo_atendimento").val() == "2") {
		$("#dados_km").css("display", "block");
	} else {
		$("#dados_km").css("display", "none");
	}

	$(".qtde_itens").numeric({
		decimal : ',',
		negative : false
	});

	$("#btn_vista_explodida").click(function() {
		if($("#produto_referencia").val().length) {
			window.open("comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=Vista Explodida&produto_referencia=" + $("#produto_referencia").val());
		} else {
			alert("Preencha o campo Produto");
		}
	});

    $("#itens_os_adicionar_linha").click(function(){
        itens_os_adicionar();
    });

	$("#btn_lista_basica").click(function() {
		if($("#produto_id").val().length) {
			window.open("peca_consulta_por_produto.php?produto=" + $("#produto_id").val());
		} else {
			alert("Preencha o campo Produto");
		}
	});

	$("#btn_obs_help").click(function() {
		alert("Este campo é para controle do Posto Autorizado, o qual deve se responsabilizar pelos dados inseridos");
	});

	$("#data_abertura").focus();

	if($("#pre-os").val() == "nova" && $("#hd_chamado").val().length > 0) {
		$.ajax({
			type : "GET",
			url : "os_cadastro_unico_ajax.php",
			data : "tipo=pre-os&hd_chamado=" + $("#hd_chamado").val(),
			dataType : "text/html",
			success : function(data) {
				var dados = data.split("|");

				if(dados[0] == "ok") {
					delete dados[0];

					for(i in dados) {
						var campo = dados[i].split("##");
						var id = campo[0];
						var valor = campo[1];

						$("#" + id).val(valor);

						if($("#div_" + id).hasClass("autocomplete")) {
							$("#" + id + "_last").val(valor);
						}
					}
				} else {
					$("#msg_erro").append(dados[1]);
				}
			}
		});
	}

	$('input[name*="consumidor_cidade"]').keyup(function() {
		var cidade = $.trim($(this).val());
		var campo = $(this).attr('rel');
		var estado = $.trim($("#"+campo+"_estado").val());
		var id = $(this).attr('id');

		if(estado.length > 0) {
			if(cidade.length > 0) {
				if(!$(this).attr("readonly")) {
					buscaCidadeEstado(estado, cidade, campo, id);
				}
			}
		}else{
			alert("Informe um estado!");
			$("#consumidor_estado").focus();
			$(this).val('');
		}

		return false;
	});

	$('input[name*="revenda_cidade"]').keyup(function() {
		var cidade = $.trim($(this).val());
		var campo = $(this).attr('rel');
		var estado = $.trim($("#"+campo+"_estado").val());
		var id = $(this).attr('id');

		if(estado.length > 0) {
			if(cidade.length > 0) {
				if(!$(this).attr("readonly")) {
					buscaCidadeEstado(estado, cidade, campo, id);
				}
			}
		}else{
			alert("Informe um estado!");
			$("#"+campo+"_estado").focus();
			$(this).val('');
		}
		return false;
	});

});

function buscaCidadeEstado(estado, cidade, campo, id) {
	$('#'+id).autocomplete('os_cadastro_unico_autocomplete.php?tipo=cidade_estado&cidade=' +cidade +'&estado='+estado, {
		minChars : 3,
		delay : 150,
		width : 350,
		matchContains : true,
		formatItem : function(row) {
			return row[1] + ' - ' + row[2];
		},
		formatResult : function(row) {
			return row[0];
		}
	});

	$('#'+id).result(function(event, data, formatted) {
		if(campo == 'consumidor')
			$('#cod_ibge').val(data[0]);

		$('#'+campo+'_cidade').val(data[1]);
		$('#'+campo+'_estado').val(data[2]);
	});
}
////////// FUNÇÕES DE CÁLCULO DE KM - COPIADAS DE OS_CADASTRO_TUDO.PHP EM 22/08/2011

$(window).load(function(){
	tipo_atend = $("select[name=tipo_atendimento]").val();
    if(tipo_atend != 13 && tipo_atend != 66 && tipo_atend != 16){
        $("#aprovacao_reponsavel2").hide();
    }

});

function formatar(src, mask) {
	var i = src.value.length;
	var saida = mask.substring(0, 1);
	var texto = mask.substring(i);
	if(texto.substring(0, 1) != saida) {
		src.value += texto.substring(0, 1);
	}
}

var map;
var total = 0;
var total_teste = 0;
var verifica_posto = true;

function initialize(busca_por) {
	var pt1, pt2, coordPosto;

	if($("#btn_ver_mapa").val() == "Fechar mapa")
		vermapa();
	$("#btn_ver_mapa").css("display", "none");

	if(GBrowserIsCompatible()) {
		// Carrega o Google Maps
		map = new GMap2(document.getElementById("mapa"));
		map.setCenter(new GLatLng(-25.429722, -49.271944), 11);

		// Cria o objeto de roteamento
		var dir = new GDirections(map);

		GEvent.addListener(dir, "load", function() {

			for(var i = 0; i < dir.getNumRoutes(); i++) {

				var route = dir.getRoute(i);
				var dist = route.getDistance();
				var x = dist.meters * 2 / 1000;
				//IDA E VOLTA
				var y = x.toString().replace(".", ",");
				var valor_calculado = parseFloat(x);

				if(valor_calculado == 0 && busca_por != 'endereco') {
					//alert('Nao encontrou');
					//initialize('endereco');
					//return false;
				}

				document.getElementById('distancia_km_conferencia').value = x;
				document.getElementById('qtde_km').value = y;
				document.getElementById('distancia_km_maps').value = 'maps';
				$("#btn_ver_mapa").css("display", "inline");
			}
		});

		GEvent.addListener(dir, "error", function() {

			if((busca_por == 'endereco' || busca_por == '') && total < 3) {
				total++;
				initialize('cep');
			} else if(busca_por == 'cep' && total < 3) {
				total++;
				initialize('endereco');
			} else if(busca_por != 'coords' && total < 3) {
				total++;
				initialize('coords');
			} else {

				if(!verifica_posto) {//Testa endereço de Origem do Posto
					alert("O endereço do Posto não pôde ser localizado no GoogleMaps. \nIsto pode ter acontecido por o endereço ser muito recente ou estar incompleto ou incorreto, para evitar este tipo de problema altere seu endereço.");
				} else if(dir.getStatus().code == G_GEO_UNKNOWN_ADDRESS) {
					alert("O endereço informado não pôde ser localizado no GoogleMaps. \nIsto pode ter acontecido por o endereço ser muito recente ou estar incompleto ou incorreto.");
				} else if(dir.getStatus().code == G_GEO_SERVER_ERROR) {
					alert("Não foi possível localizar um dos endereços.");
				} else if(dir.getStatus().code == G_GEO_MISSING_QUERY) {
					alert("Não foi informado um dos endereços.");
				} else if(dir.getStatus().code == G_GEO_BAD_KEY) {
					alert("Erro de configuração. Contate a Telecontrol. Obrigado.");
				} else if(dir.getStatus().code == G_GEO_BAD_REQUEST) {
					alert("GoogleMaps não entendeu algum dos endereços fornecidos.");
				} else {
					alert("Erro desconhecido ao consultar o GoogleMaps.");
				}

				document.getElementById('distancia_km_conferencia').value = 0;
				document.getElementById('qtde_km').value = 0;
				document.getElementById('distancia_km_maps').value = 'maps';
				$("#btn_ver_mapa").css("display", "inline");

				return false;

			}

			return false;

		});
		//hd 40389 - Endereço do posto
		if(busca_por == 'cep') {
			pt1 = document.getElementById("cep_posto").value;
			pt1 = pt1.replace(/\D/g, '');
		} else if(coordPosto != '' && busca_por == 'coords') {
			pt1 = document.getElementById("coordPosto").value;
		}

		if((busca_por == 'cep' && pt1.length != 8) || busca_por == 'endereco' || busca_por == undefined || pt1 == undefined || pt1 == '') {
			pt1 = document.getElementById("ponto1").value;
			busca_por = 'endereco';
		}

		//Endereço do consumidor
		var consumidorNumero = document.getElementById("consumidor_numero").value;
		var logradouro = document.getElementById("consumidor_endereco").value;
		var complemento = document.getElementById("consumidor_complemento").value;
		var cidade = document.getElementById("consumidor_cidade").value;
		var estado = document.getElementById("consumidor_estado").value;

		if(document.getElementById("consumidor_cep").value != '' && busca_por == 'cep') {
			pt2 = document.getElementById("consumidor_cep").value;
			pt2 = pt2.replace(/\D/g, '');
		} else if(consumidorNumero != '' && logradouro != '' && cidade != '' && estado != '' && (busca_por == 'endereco' || busca_por == '')) {
			pt2 = logradouro + ', ' + consumidorNumero + ' ' + complemento + ', ' + cidade + ', ' + estado;
		} else {
			alert('Favor preencha o endereço do cliente');
			document.getElementById('distancia_km_conferencia').value = 0;
			document.getElementById('qtde_km').value = 0;
			document.getElementById('distancia_km_maps').value = 'maps';
			document.getElementById('div_mapa_msg').innerHTML = '';
			return false;
		}

		// Carrega os pontos dados os endereços
		if(busca_por == 'cep' && pt1.length == 8) {
			pt1 += ', BR';
		}

		if(pt1 != '' && pt2 != '') {
			// O evento load do GDirections é executado quando chega o resultado do geocoding.
			dir.load("from: " + pt1 + " to: " + pt2 + ', BR', {
				locale : "pt-br",
				getSteps : true
			});
		}

	}

}

//Função para testar o endereço de Origem do Posto
function testaEndOrigem(busca_por) {// HD 268504

	var pt1, pt2, coordPosto;

	if(GBrowserIsCompatible()) {
		// Carrega o Google Maps
		map2 = new GMap2(document.getElementById("mapa"));
		map2.setCenter(new GLatLng(-25.429722, -49.271944), 11);

		// Cria o objeto de roteamento
		var dirTest = new GDirections(map2);

		GEvent.addListener(dirTest, "load", function() {

			for(var i = 0; i < dirTest.getNumRoutes(); i++) {

				var route = dirTest.getRoute(i);
				var dist = route.getDistance();
				var x = dist.meters * 2 / 1000;
				//IDA E VOLTA
				var y = x.toString().replace(".", ",");
				var valor_calculado = parseFloat(x);

				if(x != '' && y != '') {
					return true;
				}

			}

			return true;

		});

		GEvent.addListener(dirTest, "error", function() {

			if((busca_por == 'endereco' || busca_por == '') && total_teste < 3) {
				total_teste++;
				testaEndOrigem('cep');
			} else if(busca_por == 'cep' && total_teste < 3) {
				total_teste++;
				testaEndOrigem('endereco');
			} else if(busca_por != 'coords' && total_teste < 3) {
				total_teste++;
				testaEndOrigem('coords');
			} else {

				if(dirTest.getStatus().code == G_GEO_UNKNOWN_ADDRESS) {
					return false;
				} else if(dirTest.getStatus().code == G_GEO_SERVER_ERROR) {
					return false;
				} else if(dirTest.getStatus().code == G_GEO_MISSING_QUERY) {
					return false;
				} else if(dirTest.getStatus().code == G_GEO_BAD_KEY) {
					return false;
				} else if(dirTest.getStatus().code == G_GEO_BAD_REQUEST) {
					return false;
				} else {
					return false;
				}

			}

		});
		//hd 40389 - Endereço do posto
		if(busca_por == 'cep') {
			pt1 = document.getElementById("cep_posto").value;
			pt1 = pt1.replace(/\D/g, '');
		} else if(coordPosto != '' && busca_por == 'coords') {
			pt1 = document.getElementById("coordPosto").value;
		}

		if((busca_por == 'cep' && pt1.length != 8) || busca_por == 'endereco' || busca_por == undefined || pt1 == undefined || pt1 == '') {
			pt1 = document.getElementById("ponto1").value;
			busca_por = 'endereco';
		}

		/*Endereço válido - Casa do Analista Andreus Timm
		 var consumidorNumero = '1887';
		 var logradouro       = 'R CARLOS BIER';
		 var complemento      = '';
		 var cidade           = 'SAO LEOPOLDO';
		 var estado           = 'RS';
		 var cep              = '93052160';
		 */
		if(cep != '' && busca_por == 'cep') {
			pt2 = cep;
			pt2 = pt2.replace(/\D/g, '');
		} else if(consumidorNumero != '' && logradouro != '' && cidade != '' && estado != '' && (busca_por == 'endereco' || busca_por == '')) {
			pt2 = logradouro + ', ' + consumidorNumero + ' ' + complemento + ', ' + cidade + ', ' + estado;
		}

		// Carrega os pontos dados os endereços
		if(busca_por == 'cep' && pt1.length == 8) {
			pt1 += ', BR';
		}

		if(pt1 != '' && pt2 != '') {
			// O evento load do GDirections é executado quando chega o resultado do geocoding.
			dirTest.load("from: " + pt1 + " to: " + pt2 + ', BR', {
				locale : "pt-br",
				getSteps : true
			});
		}

	}

}

function compara(campo1, campo2) {
	var num1 = campo1.value.replace(".", ",");
	var num2 = campo2.value.replace(".", ",");

	if(num1 != num2) {
		document.getElementById('div_mapa_msg').style.visibility = "visible";
		document.getElementById('div_mapa_msg').innerHTML = 'A distância percorrida pelo técnico estará sujeito a auditoria';
	}
}

function vermapa() {
	if($("#btn_ver_mapa").val() == "Ver mapa") {
		$("#btn_ver_mapa").val("Fechar mapa");
		$("#mapa").css("position", "relative");
		$("#mapa").css("visibility", "visible");
	} else {
		$("#btn_ver_mapa").val("Ver mapa");
		$("#mapa").css("position", "absolute");
		$("#mapa").css("visibility", "hidden");
	}
}

function escondermapa() {
	document.getElementById("mapa").style.position = "absolute";
	document.getElementById("mapa2").style.position = "absolute";
	document.getElementById("mapa").style.visibility = "hidden";
	document.getElementById("mapa2").style.visibility = "hidden";
}

