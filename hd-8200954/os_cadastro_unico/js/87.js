function init() {
	set_produto_focus();
	
	//Lista Basica
	$(".itens_os_2_header_label_2 img").click(function() {
		var seq = $(this).attr('rel');
		var produto = $.trim($("#produto_id").val());
		verificaAdicionaLinha();
		//var peca = $.trim($("#peca_referencia" + seq).val());

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
	
	$('input[name*="peca_referencia_descricao"]').keyup(function() {
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

	$('input[name*="item_causador_referencia_descricao"]').keyup(function() {
		var peca = $.trim($(this).val());
		var produto = $.trim($('#produto_id').val());

		if(produto.length > 0) {
			if(peca.length > 2) {
				var seq = $(this).attr("seq");

				if(!$(this).attr("readonly")) {
					buscaItemCausado(seq);
				}
			}
		} else {
			$('input[name*="item_causador_referencia_descricao"]').val('');
			alert('Produto inválido.\nPreencha o campo PRODUTO.');
		}
		return false;
	});
}

function verificaAdicionaLinha() {
	//itens_os_adicionar();

	//var total_linha = $('input[name*="peca_referencia"]').size();
	var preenchido = 0;
	
	$('input[name*="peca_referencia_descricao"]').each(function(indice) {
		if($(this).val().length == 0)
			preenchido += 1;
	});

	if(preenchido <= 2)
		itens_os_adicionar();
}


function buscaPeca(seq) {

	$('#peca_referencia_descricao' + seq).autocomplete('os_cadastro_unico_autocomplete.php?tipo=peca&produto=' + $('#produto_id').val(), {
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

	$('#peca_referencia_descricao' + seq).result(function(event, data, formatted) {
		verificaAdicionaLinha();
		
		var label = data[1] + ' - ' + data[2];
		var value = data[0];

		//$('#os_item' + seq).val(data[0]);
		$('#peca_id' + seq).val(data[0]);
		$('#peca_referencia' + seq).val(data[1]);
		
		$('#peca_referencia_descricao' + seq).val(label);

		atualiza_causa_defeito(seq);
	});
}


function atualiza_causa_defeito(seq){
	var peca_referencia = $("#peca_referencia"+seq).val();

	$.ajax({
		url : 'ajax_os_cadastro_unico.php',
		type : "POST",
		data : "tipo=causa_defeito_peca_referencia&peca_referencia=" + peca_referencia,
		success : function(retorno) {
			$("#defeito"+seq).html(retorno);
			return false;
		}
	});
}

function buscaItemCausado(seq) {

	$('#item_causador_referencia_descricao' + seq).autocomplete('os_cadastro_unico_autocomplete.php?tipo=peca&produto=' + $('#produto_id').val(), {
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

	$('#item_causador_referencia_descricao' + seq).result(function(event, data, formatted) {
		verificaAdicionaLinha();
		
		var label = data[1] + ' - ' + data[2];
		var value = data[0];
		
		$('#item_causador_id' + seq).val(data[0]);
		$('#item_causador_referencia' + seq).val(data[1]);
				
		$('#item_causador_referencia_descricao' + seq).val(label);

		$('#peca_referencia_descricao' + (parseInt(seq) + 1)).focus();
	});
}

function buscaProduto() {

	$('#produto_referencia, #produto_descricao').autocomplete('os_cadastro_unico_autocomplete.php?tipo=produto', {
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

	$('#produto_referencia, #produto_descricao').result(function(event, data, formatted) {
		var produto_anterior = $('#produto_id').val();

		var peca = 0;
		$('input[name*="peca_referencia"]').each(function(indice) {
			if($(this).val().length > 0)
				peca += 1;
		});
		if(peca > 0 && produto_anterior != data[0]) {
			var pergunta = confirm("Deseja alterar o produto selecionado?\n\nATENÇÃO: informações de ANÁLISE DA OS e ITENS DA ORDEM DE SERVIÇO serão perdidas");

			if(pergunta) {
				apagarItemPedido();

				$('#produto_id').val(data[0]);
				$('#produto_referencia').val(data[1]);
				$('#produto_descricao').val(data[2]);

				$('#produto_referencia_last').val(data[1]);
				$('#produto_descricao_last').val(data[2]);

				$('#serie').focus();

				busca_atendimento_produto_familia(data[0]);
				defeito_constatado_pela_familia_produto(data[0]);

			} else {
				$('#produto_referencia').val($('#produto_referencia_last').val());
				$('#produto_descricao').val($('#produto_descricao_last').val());
			}
		} else {
			$('#produto_id').val(data[0]);
			$('#produto_referencia').val(data[1]);
			$('#produto_descricao').val(data[2]);

			$('#produto_referencia_last').val(data[1]);
			$('#produto_descricao_last').val(data[2]);

			$('#serie').focus();

			busca_atendimento_produto_familia(data[0]);
			defeito_constatado_pela_familia_produto(data[0]);
		}

		return false;
	});
}

function apagarItemPedido() {
	$('input[name*="peca_referencia"]').val('');
	$('input[name*="peca_descricao"]').val('');
	$('input[name*="qtde"]').val('');
	$('input[name*="item_causador_referencia"]').val('');
	$('input[name*="item_causador_descricao"]').val('');
}

function verifica_atendimento() {
	var atendimento = $("#tipo_atendimento option:selected").text().toUpperCase();

	if(atendimento.indexOf('ENTREGA') != -1) {
		var label = $("#div_data_abertura").find('label');
		label.html("Data de Entrega");
	} else {
		var label = $("#div_data_abertura").find('label');
		label.html("Data de Abertura");
	}
}

function busca_atendimento_produto_familia(produto) {
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

function itens_os_adicionar() {
	var sequencia = parseInt($("#n_linhas_pecas").val());
	var item = '';

	item += $('<div>').append($("#div_os_item__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	
	item += $('<div>').append($("#div_peca_referencia_descricao__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	
	item += $('<div>').append($("#div_qtde_lb__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_qtde__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);

	item += $('<div>').append($("#div_lista_basica__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);

	item += $('<div>').append($("#div_defeito__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_item_causador_referencia_descricao__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	
	item += $('<div>').append($("#div_peca_id__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_peca_referencia__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);

	item += $('<div>').append($("#div_item_causador_id__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_item_causador_referencia__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	
	item += $('<div>').append($("#div_servico_id__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_servico__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);

	$("#itens_os_corpo").append(item);

	//set_peca_itens_focus();
	//set_qtde_blur();
	//$(".qtde_itens").numeric({ decimal: ',', negative: false });

	$("#n_linhas_pecas").val(sequencia + 1);

	init();
}

function analise_adicionar() {
	var sequencia = parseInt($("#n_linhas_analise").val());
	var item = '';
	item += $('<div>').append($("#div_defeito_constatado__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);
	item += $('<div>').append($("#div_solucao_os__modelo__").clone()).remove().html().replace(/__modelo__/g, sequencia);

	$("#analise_os_corpo").append(item);
	set_defeito_constatado_focus();
	$("#defeito_constatado" + sequencia).focus();

	$("#n_linhas_analise").val(sequencia + 1);
}

function set_peca_itens_focus() {
	$(".peca_itens").each(function() {
		$(this).focus(function() {
			var seq = parseInt($(this).attr('seq'));
			if(seq + 1 == parseInt($("#n_linhas_pecas").val())) {
				itens_os_adicionar();
				$('#peca' + seq).focus();
			}

			if(!$(this).attr("readonly")) {
				$(this).autocomplete('os_cadastro_unico_autocomplete.php?tipo=peca&produto=' + $('#produto_id').val() + '', {
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

				$(this).result(function(event, data, formatted) {
					if(data[0] == '')
						return false;
					$('#' + $(this).attr('id') + '_id').val(data[0]);
					$('#' + $(this).attr('id')).val(data[1] + ' - ' + data[2]);
					$('#' + $(this).attr('id') + '_last').val($('#peca' + seq).val());
					$('#qtde' + seq).numeric();
					$('#qtde_lb' + seq).val(data[3]);
				});
			}
		});
	});
}

function set_qtde_blur() {
	$(".qtde_itens").blur(function() {
		var seq = parseInt($(this).attr('seq'));
		var qtde = parseFloat($(this).val().replace(',', '.'));
		var qtde_lb = parseFloat($("#qtde_lb" + seq).val());

		if(qtde > qtde_lb) {
			alert("A quantidade digitada (" + qtde + ") é maior que a quantidade permitida (" + qtde_lb + ") para a lista básica do produto " + $("#produto").val());
			$(this).val($("#qtde_lb" + seq).val().replace('.', ','));
			$(this).focus();
		}
	});
}

function set_produto_focus() {
	$('#produto').focus(function() {

		if(!$(this).attr("readonly")) {
			$('#produto').autocomplete('os_cadastro_unico_autocomplete.php?tipo=produto', {
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
						
						/*
							$('.peca_itens').val('');
							$('.qtde_itens').val('');
							$('.defeito_itens').attr("selectedIndex", 0);
							$('.servico_itens').attr("selectedIndex", 0);
							$('.defeito_constatado_analise').val('');
							$('.solucao_os_analise').html('');
						*/
						
						busca_atendimento_produto_familia(data[0]);
						defeito_constatado_pela_familia_produto(data[0]);
					} else {
						$('#produto').val($('#produto_last').val());
					}
				}else{
						apagarItemPedido();

						$('#produto_id').val(data[0]);
						$('#produto').val(label);
						$('#produto_last').val(label);
						$('#produto_referencia').val(data[1]);
						
						busca_atendimento_produto_familia(data[0]);
						defeito_constatado_pela_familia_produto(data[0]);
				}
			});
		}
	});
}

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
				})
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
		$("#div_mostra_imagem").html("<img src='" + url + "' />");
		$("#div_mostra_imagem").css("display", "block");
		$("#btn_mostra_nf").val("Fechar imagem");
	} else {
		$("#div_mostra_imagem").css("display", "none");
		$("#btn_mostra_nf").val("Mostrar imagem");
	}
}


$(document).ready(function() {
	init();
	
	verifica_atendimento();
	set_revenda_nome_focus();
	set_revenda_cnpj_focus();
			
	Shadowbox.init();

	$("#defeito_reclamado_descricao").css("width", "298px");
	$("#tipo_atendimento").css("width", "159px");
	$("#defeito_constatado").css("width", "200px");
	$("#horas_trabalhadas").css("width", "104px");
	
	$('#serie').keyup(function() {
		var serie = $.trim($(this).val());

		if(serie.length > 2) {
			if(!$(this).attr("readonly")) {
				$(this).autocomplete('os_cadastro_unico_autocomplete.php?tipo=serie&q=' + $(this).val() + '', {
					minChars : 3,
					delay : 150,
					width : 350,
					matchContains : true,
					formatItem : function(row) {
						return row[4] + ' - ' + row[1] + ' - ' + row[2];
					},
					formatResult : function(row) {
						return row[0];
					}
				});

				$(this).result(function(event, data, formatted) {
					if(data[0] == '')
						return false;

					var produto = $("#produto").val();

					$(this).val(data[4]);

					if(produto.length == 0) {
						var label_produto = data[1]+" - "+data[2];
						
						$('#produto_id').val(data[0]);
						$('#produto').val(label_produto);
						$('#produto_last').val(label_produto);
						$('#produto_referencia').val(data[1]);
					
						busca_atendimento_produto_familia(data[0]);
						defeito_constatado_pela_familia_produto(data[0]);
					}
				});
			}
		}

		return false;
	});

	$('#tipo_atendimento').change(function() {
		verifica_atendimento();
	});

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
	
	$("#revenda_nome").keyup(function () {
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
			buscaCEP(this.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado)
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
		})
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

function formatar(src, mask) {
	var i = src.value.length;
	var saida = mask.substring(0, 1);
	var texto = mask.substring(i)
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
			var pt2 = document.getElementById("consumidor_cep").value;
			pt2 = pt2.replace(/\D/g, '');
		} else if(consumidorNumero != '' && logradouro != '' && cidade != '' && estado != '' && (busca_por == 'endereco' || busca_por == '')) {
			var pt2 = logradouro + ', ' + consumidorNumero + ' ' + complemento + ', ' + cidade + ', ' + estado;
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
			var pt2 = cep;
			pt2 = pt2.replace(/\D/g, '');
		} else if(consumidorNumero != '' && logradouro != '' && cidade != '' && estado != '' && (busca_por == 'endereco' || busca_por == '')) {
			var pt2 = logradouro + ', ' + consumidorNumero + ' ' + complemento + ', ' + cidade + ', ' + estado;
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