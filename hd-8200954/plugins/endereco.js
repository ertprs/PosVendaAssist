function retiraAcentos(palavra){
	var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
	var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var newPalavra = "";

    for(i = 0; i < palavra.length; i++) {
    	if (com_acento.search(palavra.substr(i, 1)) >= 0) {
      		newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
      	} else {
       		newPalavra += palavra.substr(i, 1);
    	}
    }

    return newPalavra.toUpperCase();
}

function buscaCidade (estado, cidade_select) {
	$(cidade_select).find("option").first().nextAll().remove();

	if (estado.length > 0) {
		$.ajax({
			async: false,
			cache: false,
			url: "plugins/endereco.php",
			type: "POST",
			data: { buscaCidade: true, estado: estado },
			beforeSend: function () {
				$(cidade_select).hide();
				$(cidade_select).after("<img name='ajax_loading' src='imagens/loading_img.gif' style='width: 20px; height: 20px;' />");
			},
			complete: function (data) {
				data = $.parseJSON(data.responseText);

				if (data.cidades) {
					var cidades = data.cidades;

					$.each(cidades, function (key, cidade) {
						var option = $("<option></option>", { value: cidade, text: cidade });

						$(cidade_select).append(option);
					});
				}

				$("img[name=ajax_loading]").remove();
				$(cidade_select).show();
			}
		});
	}
}

function buscaCEP(cep, endereco, bairro, cidade, estado) {
	if (cep.length > 0) {
		$.ajax({
			url: "../ajax_cep.php",
			type: "GET",
			data: { cep: cep },
			beforeSend: function () {
				$(endereco).hide();
				$(endereco).after("<img name='ajax_loading' src='imagens/loading_img.gif' style='width: 20px; height: 20px;' />");
				$(bairro).hide();
				$(bairro).after("<img name='ajax_loading' src='imagens/loading_img.gif' style='width: 20px; height: 20px;' />");
				$(cidade).hide();
				$(cidade).after("<img name='ajax_loading' src='imagens/loading_img.gif' style='width: 20px; height: 20px;' />");
				$(estado).hide();
				$(estado).after("<img name='ajax_loading' src='imagens/loading_img.gif' style='width: 20px; height: 20px;' />");
			},
			complete: function (data) {
				data = data.responseText.split(";");

				if (data[0] == "ok") {
					data_estado   = data[4];
					data_cidade   = data[3];
					data_endereco = data[1];
					data_bairro   = data[2];

					if ($(estado).val() != data_estado) {
						$(cidade).next("img[name=ajax_loading]").remove();
						buscaCidade(data_estado, cidade);
					}

					$(estado).val(data_estado);
					$(cidade).val(retiraAcentos(data_cidade).toUpperCase());

					if (data_endereco.length > 0) {
						$(endereco).val(data_endereco);
					}

					if (data_bairro.length > 0) {
						$(bairro).val(data_bairro);	
					}
				} else {
					alert("CEP não encotnrado");
					$(estado).val("");
					$(cidade).val("");
					$(endereco).val("");
					$(bairro).val("");
				}

				$("img[name=ajax_loading]").remove();
				$(endereco).show();
				$(bairro).show();
				$(cidade).show();
				$(estado).show();
			}
		});
	}
}
