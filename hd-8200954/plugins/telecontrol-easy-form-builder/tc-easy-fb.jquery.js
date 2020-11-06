(function( $ ){	

	var divForm, settings;
	$.fn.easyFormBuilder = function(options, jsonForm = ""){

		
		let defaults = {
			geraGrupo: geraGrupoDefault,
			geraPanelHeader: geraPanelHeaderDefault,
			geraPanelBody: geraPanelBodyDefault,
			geraPanelFooter: geraPanelFooterDefault,
			geraInputTextButton: geraInputTextButtonDefault,
			geraInputCheckboxButton: geraInputCheckboxButtonDefault,
			geraInputRadioButton: geraInputRadioButtonDefault,
			geraOpcoesText: geraOpcoesTextDefault,
			geraOpcoesRadio: geraOpcoesRadioDefault,
			geraOpcoesCheck: geraOpcoesCheckDefault,
			geraDescPergunta: geraDescPerguntaDefault,
			geraBtnAddGrupo: geraBtnAddGrupoDefault,
			geraOptionsTipoCampo: geraOptionsTipoCampoDefault,
			geraTipoCampoText: geraTipoCampoTextDefault,
			geraObrigatorio: geraObrigatorioDefault,
			geraJustificativa: geraJustificativaDefault,
			geraDescMultipla: geraDescMultiplaDefault,
			geraInputsMultiplos: geraInputsMultiplosDefault,
			acaoInserirPergunta: acaoInserirPerguntaDefault,
			acaoInserirPerguntaText: acaoInserirPerguntaTextDefault,
			acaoInserirPerguntaRadio: acaoInserirPerguntaRadioDefault,
			acaoInserirRadio: acaoInserirRadioDefault,
			acaoInserirPerguntaCheckbox: acaoInserirPerguntaCheckboxDefault,
			acaoInserirCheckbox: acaoInserirCheckboxDefault,
			insereTituloGeral: insereTituloGeralDefault,
			insereDescricaoGeral: insereDescricaoGeralDefault,
			carregaConteudo: carregaConteudoDefault
		};

		divForm = $(this);

		settings = $.extend( {}, defaults, options );

		if (jsonForm == "") {

			divForm.append(settings.insereTituloGeral());
			divForm.append(settings.insereDescricaoGeral());

			settings.geraGrupo();
			settings.geraBtnAddGrupo();

		} else {

			jsonForm = JSON.parse(jsonForm);

			settings.carregaConteudo(jsonForm);

		}

	}

	var insereTituloGeralDefault = function(titulo = "") {

		return $("<input>", {
			type: "text",
			name: "easybuilder[titulo]",
			value: titulo,
			class: "form-control",
			placeholder: "Informe o título do formulário",
			css: {
				width: "50%",
				"margin-left": "25%",
				"margin-top": "30px",
				"margin-bottom": "-10px !important",
				"text-align": "center",
				"height": "40px",
				"font-size": "16px"
			}
		});

	};

	var insereDescricaoGeralDefault = function(descricao = "") {

		return $("<textarea>", {
			name: "easybuilder[descricao]",
			text: descricao,
			class: "form-control",
			placeholder: "Descrição Informativa (opcional)",
			css: {
				width: "80%",
				"margin-left": "10%",
				"margin-top": "15px",
				"font-size": "13px"
			},
			rows: 8
		});

	};

	var geraBtnAddGrupoDefault = function() {

		let btn = $("<button>", {
			type: "button",
			class: "btn btn-info",
			text: " Novo Grupo de Perguntas",
			css: {
				"margin-top": "-35px"
			},
			click: function() {
				settings.geraGrupo();
			}
		}).prepend(
			$("<span>", {
				class: "glyphicon glyphicon-plus"
			})
		);

		let p = $("<p>").html(btn);

		divForm.after(p);

	};

	var geraGrupoDefault = function() {

		let panel = $("<div>", {
			class: "panel panel-primary",
			css: {
				margin: "50px"
			},
			"data-posicao": $(".panel").length
		});

		let panelHeader = settings.geraPanelHeader();
		let panelBody   = settings.geraPanelBody();
		let panelFooter = settings.geraPanelFooter();

		panel.append(panelHeader);
		panel.append(panelBody);
		panel.append(panelFooter);

		divForm.append(panel);

	};

	var geraPanelHeaderDefault = function() {

		let div = $("<div>", {
			class: "panel-heading"
		});

		let index = $(".panel").length;

		let input = $("<input />", {
			name: "easybuilder[formulario]["+index+"][titulo_grupo]",
			class: "titulo_grupo form-control",
			placeholder: "Informe o titulo do grupo de perguntas",
			css: {
				width: "93%",
				"margin-right": "2%"
			}
		}).prop("disabled", false);

		let btnExcluir = $("<button>", {
			type: "button",
			class: "btn btn-danger",
			width: "5%",
			click: function() {

				$(this).closest(".panel-primary").remove();

			}
		}).prepend($("<span>", {
			class: "glyphicon glyphicon-remove"
		}));

		let textarea = $("<textarea>", {
			name: "easybuilder[formulario]["+index+"][descricao_grupo]",
			class: "descricao_grupo form-control",
			placeholder: "Descrição do Grupo (opcional)",
			css: {
				width: "100%",
				"margin-right": "2%",
				"margin-top": "1.5%"
			},
			rows: 5
		}).prop("disabled", false);

		div.append(input);
		div.append(btnExcluir);
		div.append(textarea);

		return div;

	};

	var geraPanelFooterDefault = function() {

		let btnText  = settings.geraInputTextButton();
		let btnCheck = settings.geraInputCheckboxButton();
		let btnRadio = settings.geraInputRadioButton();

		let panelFooter = $("<div>", {
			class: "panel-footer",
			css: {
				padding: "15px",
				margin: 0,
				display: "inline-block",
				width: "100%"
			}
		});

		let rowBtn = $("<div>", {
			class: "row row-fluid"
		});

		rowBtn.append(btnText);
		rowBtn.append(btnCheck);
		rowBtn.append(btnRadio);

		panelFooter.append(rowBtn);

		panelFooter.prepend($("<h4>", {
			text: "Nova Pergunta:",
			css: {
				"font-weight": "bolder",
				"margin-top": "-5px",
				"font-family": "Sans-serif"
			}
		}));

		let divFormContent = $("<div>", {
			css: {
				margin: "10px",
				width: "100%",
			},
			class: "form-options"
		});

		divFormContent.append(settings.geraOpcoesText);
		divFormContent.append(settings.geraOpcoesCheck);
		divFormContent.append(settings.geraOpcoesRadio);

		panelFooter.append(divFormContent);

		return panelFooter;

	};

	var geraPanelBodyDefault = function() {

		return $("<div>", {
			class: "panel-body",
			"data-contador": 0,
			css: {
				padding: "15px"
			}
		});

	};

	var geraDescPerguntaDefault = function(){

		let inputGroup = $("<div>", {
			class: "form-group",
			css: {
				"margin-right": "15px"
			}
		});

		inputGroup.append($("<label>", {
			text: "Descrição da pergunta"
		}));

		inputGroup.append($("<br />"));

		inputGroup.append($("<input />", {
			placeholder: "",
			class: "desc-pergunta form-control",
			width: "300px"
		}));

		return inputGroup;

	};

	var geraDescMultiplaDefault = function() {

		let inputGroup = $("<div>", {
			class: "form-group multiplas-opcoes",
			css: {
				"margin-left": "180px"
			}
		});

		inputGroup.append($("<label>", {
			text: "Campos Para Marcação"
		}));

		inputGroup.append(settings.geraInputsMultiplos());

		return inputGroup;

	};

	var geraInputsMultiplosDefault = function() {

		let divInputsMulti = $("<div>", {
			class: "div-multi-input",
			css: {
				width: "100%"
			}
		});

		let input = $("<input>", {
			class: "desc-marcacao form-control",
			css: {
				height: "30px",
				"margin-bottom": "2px"
			}
		});

		divInputsMulti.append(input);
		divInputsMulti.append($("<br />"));

		let btnAdd = $("<button>", {
			class: "btn btn-info btn-xs",
			text: " Nova Opção",
			type: "button",
			css: {
				"margin-top": "5px"
			},
			click: function() {
				$(this).before($("<input>", {
					class: "desc-marcacao form-control",
					css: {
						height: "30px",
						"margin-bottom": "2px"
					}
				}));

				$(this).before($("<button>", {
					type: "button",
					class: "btn btn-danger btn-xs",
					width: "30px",
					height: "25px",
					click: function() {
						$(this).next("br").remove();
						$(this).prev("input").remove();
						$(this).remove();

					}
				}).prepend($("<span>", {
					class: "glyphicon glyphicon-remove"
				})));

				$(this).before($("<br />"));

			}
		}).prepend($("<span>", {
			class: "glyphicon glyphicon-plus"
		}));

		divInputsMulti.append(btnAdd);

		return divInputsMulti;

	};

	var geraTipoCampoTextDefault = function() {

		let inputGroup = $("<div>", {
			class: "form-group"
		});

		let select = $("<select>", {
			class: "tipo-campo form-control"
		});

		let options = settings.geraOptionsTipoCampo();

		let optgroupPadrao = $("<optgroup>", {
			label: "Padrão"
		});

		$.each(options["padrao"], function(index, value ){
		    select.append(optgroupPadrao.append(value));
		});

		let optgroupPersonalizado = $("<optgroup>", {
			label: "Personalizado"
		});

		$.each(options["personalizado"], function(index, value ){
		    select.append(optgroupPersonalizado.append(value));
		});
		
		inputGroup.append($("<label>", {
			text: "Tipo do Campo"
		}));

		inputGroup.append($("<br />"));
		inputGroup.append(select);

		return inputGroup;

	};

	var geraOptionsTipoCampoDefault = function() {

		let optionArray = {
			padrao: [],
			personalizado: []
		};

		optionArray["padrao"].push($("<option>", {
			value: "text",
			text: "Texto Simples",
			selected: true
		}));

		optionArray["padrao"].push($("<option>", {
			value: "number",
			text: "Numérico"
		}));

		optionArray["padrao"].push($("<option>", {
			value: "textarea",
			text: "Caixa de Texto"
		}));

		optionArray["personalizado"].push($("<option>", {
			value: "telefone",
			text: "Fone/Celular"
		}));

		optionArray["personalizado"].push($("<option>", {
			value: "email",
			text: "E-mail"
		}));

		return optionArray;

	};

	var acaoInserirPerguntaDefault = function(that, tipo) {

		let panelBody = $(that).closest(".panel").find(".panel-body");

		var conteudoFinal = null;

		if (tipo == "text") {

			conteudoFinal = settings.acaoInserirPerguntaText(that);

		} else if (tipo == "radio") {

			conteudoFinal = settings.acaoInserirPerguntaRadio(that);

		} else if (tipo == "checkbox") {

			conteudoFinal = settings.acaoInserirPerguntaCheckbox(that);

		}

		let contador = $(panelBody).data("contador") + 1;
		$(panelBody).data("contador", contador);

		panelBody.append(conteudoFinal);

	};

	var acaoInserirPerguntaRadioDefault = function(that) {

		let formInput   = $(that).closest(".form-input-radio");
		let descricao   = formInput.find(".desc-pergunta").val();
		let obrigatorio = formInput.find(".obrigatorio").is(":checked");
		let justificar  = formInput.find(".justificativa").is(":checked");
		let index       = $(that).closest(".panel").data("posicao");
		let indexRadio  = $(that).closest(".panel").find(".panel-body").data("contador");

		formInput.find(".alert-danger").remove();

		if (descricao == "") {

			formInput.prepend($("<div>", {
				class: "alert alert-danger",
				text: "Informe a descrição da pergunta",
				css: {
					"font-weight": "bolder"
				}
			}));

		}

		let panelBody = $(that).closest(".panel").find(".panel-body");

		let row = $("<div>", {
			class: "row row-fluid row-radio",
			css: {
				display: "flex",
				"align-items": "center",
				"padding-left": "5%",
				width: "100%"
			}
		});

		let btnRemoveLinha = $("<div>", {
			class: "form-group"
		}).html($("<button>", {
			type: "button",
			class: "btn btn-danger btn-xs",
			width: "30px",
			height: "25px",
			css: {
				"margin-right": "20px"
			},
			click: function() {
				$(this).closest(".row-radio").remove();
			}
		}).prepend($("<span>", {
			class: "glyphicon glyphicon-remove"
		})));

		row.append(btnRemoveLinha);

		let labelLinha = $("<label>", {
			text: descricao,
			css: {
				"font-size": "14px"
			}
		});

		if (obrigatorio) {
			labelLinha.append(
				$("<span>", {
					text: " *",
					css: {
						color: "red"
					}
				})
			);
		}

		let inputGroup = $("<div>", {
			class: "form-group",
			css: {
				"width": "25%"
			}
		}).html(labelLinha);

		inputGroup.append($("<input>", {
			type: "hidden",
			name: "easybuilder[formulario]["+index+"][perguntas]["+indexRadio+"][radio][descricao]",
			class: "radio-desc",
			value: descricao
		}));

		inputGroup.append($("<input>", {
			type: "hidden",
			name: "easybuilder[formulario]["+index+"][perguntas]["+indexRadio+"][obrigatorio]",
			value: (obrigatorio) ? "t" : "f"
		}));

		inputGroup.append($("<input>", {
			type: "hidden",
			name: "easybuilder[formulario]["+index+"][perguntas]["+indexRadio+"][justificar]",
			value: (justificar) ? "t" : "f"
		}));

		row.append(inputGroup);

		row.append(settings.acaoInserirRadio(index, indexRadio, formInput));

		if (justificar) {

			row.append($("<input>", {
				placeholder: "Justificativa",
				type: "text",
				disabled: true
			}));

		}

		panelBody.append(row);

	};

	var acaoInserirRadioDefault = function(index, indexRadio, formInput) {

		var inputGroupInput = $("<div>", {
			class: "form-group",
			css: {
				width: "70%"
			}
		});

		$(formInput).find(".desc-marcacao").each(function(){

			if ($(this).val() != "") {
				inputGroupInput.append($("<label>", {
					text: $(this).val(),
					css: {
						"font-size": "14px"
					}
				}))

				inputGroupInput.append($("<input>", {
					class: "form-control",
					css: {
						"margin-left": "5px",
						"margin-right": "20px"
					},
					type: "radio",
					disabled: true
				}));

				inputGroupInput.append($("<input>", {
					type: "hidden",
					name: "easybuilder[formulario]["+index+"][perguntas]["+indexRadio+"][radio][options][]",
					value: $(this).val()
				}));
			}
			
		});

		return inputGroupInput;

	}

	var acaoInserirPerguntaCheckboxDefault = function(that) {

		let formInput   = $(that).closest(".form-input-check");
		let descricao   = formInput.find(".desc-pergunta").val();
		let obrigatorio = formInput.find(".obrigatorio").is(":checked");
		let index       = $(that).closest(".panel").data("posicao");
		let indexCheck  = $(that).closest(".panel").find(".panel-body").data("contador");

		formInput.find(".alert-danger").remove();

		if (descricao == "") {

			formInput.prepend($("<div>", {
				class: "alert alert-danger",
				text: "Informe a descrição da pergunta",
				css: {
					"font-weight": "bolder"
				}
			}));

		}

		let panelBody = $(that).closest(".panel").find(".panel-body");

		let row = $("<div>", {
			class: "row row-fluid row-checkbox",
			css: {
				display: "flex",
				"align-items": "center",
				"padding-left": "5%",
				width: "100%"
			}
		});

		let btnRemoveLinha = $("<div>", {
			class: "form-group"
		}).html($("<button>", {
			type: "button",
			class: "btn btn-danger btn-xs",
			width: "30px",
			height: "25px",
			css: {
				"margin-right": "20px"
			},
			click: function() {
				$(this).closest(".row-checkbox").remove();

			}
		}).prepend($("<span>", {
			class: "glyphicon glyphicon-remove"
		})));

		row.append(btnRemoveLinha);

		let labelLinha = $("<label>", {
			text: descricao,
			css: {
				"font-size": "14px"
			}
		});

		if (obrigatorio) {
			labelLinha.append(
				$("<span>", {
					text: " *",
					css: {
						color: "red"
					}
				})
			);
		}

		let inputGroup = $("<div>", {
			class: "form-group",
			css: {
				"width": "25%"
			}
		}).html(labelLinha);

		inputGroup.append($("<input>", {
			type: "hidden",
			name: "easybuilder[formulario]["+index+"][perguntas]["+indexCheck+"][checkbox][descricao]",
			class: "radio-desc",
			value: descricao
		}));

		inputGroup.append($("<input>", {
			type: "hidden",
			name: "easybuilder[formulario]["+index+"][perguntas]["+indexCheck+"][obrigatorio]",
			value: (obrigatorio) ? "t" : "f"
		}));

		row.append(inputGroup);

		row.append(settings.acaoInserirCheckbox(index, indexCheck, formInput));

		panelBody.append(row);

	};

	var acaoInserirCheckboxDefault = function(index, indexCheck, formInput) {

		var inputGroupInput = $("<div>", {
			class: "form-group",
			css: {
				width: "70%"
			}
		});

		$(formInput).find(".desc-marcacao").each(function(){

			if ($(this).val() != "") {
				inputGroupInput.append($("<label>", {
					text: $(this).val(),
					css: {
						"font-size": "14px"
					}
				}));

				inputGroupInput.append($("<input>", {
					class: "form-control",
					css: {
						"margin-left": "5px",
						"margin-right": "20px"
					},
					type: "checkbox",
					disabled: true
				}));

				inputGroupInput.append($("<input>", {
					type: "hidden",
					name: "easybuilder[formulario]["+index+"][perguntas]["+indexCheck+"][checkbox][options][]",
					value: $(this).val()
				}));
			}

		});

		return inputGroupInput;

	};

	var geraOpcoesTextDefault = function() {

		let inlineForm = $("<div>", {
			class: "form-input-text",
			css: {
				display: "none"
			}
		}).append(settings.geraDescPergunta());

		inlineForm.append(settings.geraTipoCampoText());
		inlineForm.append(settings.geraObrigatorio());

		inlineForm.append($("<div>", {
			class: "row row-fluid",
			css: {
				"text-align": "center",
				"margin-top": "10px"
			}
		}).append(
			$("<button>", {
				class: "insere-pergunta btn btn-default",
				type: "button",
				text: "Inserir Pergunta",
				css: {
					"margin-top": "10px"
				},
				click: function(){
					settings.acaoInserirPergunta($(this), "text");
				}
			})
		));

		return inlineForm;

	};

	var geraObrigatorioDefault = function() {

		let inputGroup = $("<div>", {
			class: "form-group",
			css: {
				"margin-left": "20px"
			}
		});

		let inputRadio = $("<input>", {
			class: "obrigatorio form-control",
			type: "checkbox",
			checked: true
		});
		
		let lbl = $("<label>", {
			text: " Obrigatório "
		});

		lbl.append(inputRadio);
		inputGroup.append(lbl);

		return inputGroup;

	};

	var geraJustificativaDefault = function() {

		let inputGroup = $("<div>", {
			class: "form-group",
			css: {
				"margin-left": "20px"
			}
		});

		let inputRadio = $("<input>", {
			class: "justificativa form-control",
			type: "checkbox",
			checked: false
		});
		
		let lbl = $("<label>", {
			text: " Justificar Resposta "
		});

		lbl.append(inputRadio);
		inputGroup.append(lbl);

		return inputGroup;

	};

	var acaoInserirPerguntaTextDefault = function(that) {

		let formInput   = $(that).closest(".form-input-text");
	 	let descricao   = formInput.find(".desc-pergunta").val();
		let tipo        = formInput.find(".tipo-campo option:selected").val();
		let obrigatorio = formInput.find(".obrigatorio").is(":checked");
		let index       = $(that).closest(".panel").data("posicao");
		let indexText   = $(that).closest(".panel").find(".panel-body").data("contador");

		formInput.find(".alert-danger").remove();

		if (descricao == "") {

			formInput.prepend($("<div>", {
				class: "alert alert-danger",
				text: "Informe a descrição da pergunta",
				css: {
					"font-weight": "bolder"
				}
			}));

		}

		let row = $("<div>", {
			class: "row row-fluid row-text",
			css: {
				display: "flex",
				"align-items": "center",
				"padding-left": "5%",
				width: "100%"
			}
		});

		let btnRemoveLinha = $("<div>", {
			class: "form-group"
		}).html($("<button>", {
			type: "button",
			class: "btn btn-danger btn-xs",
			width: "30px",
			height: "25px",
			css: {
				"margin-right": "20px"
			},
			click: function() {
				$(this).closest(".row-text").remove();

			}
		}).prepend($("<span>", {
			class: "glyphicon glyphicon-remove"
		})));

		row.append(btnRemoveLinha);

		let labelLinha = $("<label>", {
			text: descricao,
			css: {
				"font-size": "14px"
			}
		});

		if (obrigatorio) {
			labelLinha.append(
				$("<span>", {
					text: " *",
					css: {
						color: "red"
					}
				})
			);
		}

		let inputGroup = $("<div>", {
			class: "form-group",
			css: {
				"width": "25%"
			}
		}).html(labelLinha);

		row.append(inputGroup);

		if (tipo == "text" || tipo == "telefone" || tipo == "email") {

			let placeholder = "";

			if (tipo == "telefone") {
				placeholder = "(00) 00000-0000";
			} else if (tipo == "email") {
				placeholder = "nome@exemplo.com";
			}

			var inputGroupInput = $("<div>", {
				class: "form-group",
				css: {
					width: "70%"
				}
			}).html($("<input>", {
				class: "form-control",
				css: {
					width: "250px",
					"margin-left": "20px"
				},
				type: "text",
				placeholder: placeholder,
				disabled: true
			}));

			inputGroupInput.append($("<input>", {
				type: "hidden",
				value: descricao,
				name: "easybuilder[formulario]["+index+"][perguntas]["+indexText+"]["+tipo+"]"
			}));

		} else if (tipo == "number") {

			var inputGroupInput = $("<div>", {
				class: "form-group",
				css: {
					width: "70%"
				}
			}).html($("<input>", {
				class: "form-control",
				css: {
					width: "90px",
					"margin-left": "20px"
				},
				type: "number",
				min: 0,
				disabled: true
			}));

			inputGroupInput.append($("<input>", {
				type: "hidden",
				value: descricao,
				name: "easybuilder[formulario]["+index+"][perguntas]["+indexText+"][number]"
			}));

		} else if (tipo == "textarea") {

			var inputGroupInput = $("<div>", {
				class: "form-group",
				css: {
					width: "70%"
				}
			}).html($("<textarea>", {
				class: "form-control",
				css: {
					width: "250px",
					"margin-left": "20px"
				},
				rows: "5",
				disabled: true
			}));

			inputGroupInput.append($("<input>", {
				type: "hidden",
				value: descricao,
				name: "easybuilder[formulario]["+index+"][perguntas]["+indexText+"][textarea]"
			}));
		}

		inputGroupInput.append($("<input>", {
			type: "hidden",
			name: "easybuilder[formulario]["+index+"][perguntas]["+indexText+"][obrigatorio]",
			value: (obrigatorio) ? "t" : "f"
		}));

		row.append(inputGroupInput);

		return row;

	};

	var geraOpcoesRadioDefault = function() {

		let inlineForm = $("<div>", {
			class: "form-input-radio",
			css: {
				display: "none"
			}
		}).append(settings.geraDescPergunta());

		inlineForm.append(settings.geraDescMultipla());
		inlineForm.append(settings.geraObrigatorio());
		inlineForm.append(settings.geraJustificativa());

		inlineForm.append($("<div>", {
			class: "row row-fluid",
			css: {
				"text-align": "center",
				"margin-top": "10px"
			}
		}).append(
			$("<button>", {
				class: "insere-pergunta btn btn-default",
				type: "button",
				text: "Inserir Pergunta",
				css: {
					"margin-top": "10px"
				},
				click: function() {

					settings.acaoInserirPergunta($(this), "radio");

				}
			})
		));

		return inlineForm;

	};

	var geraOpcoesCheckDefault = function() {

		let inlineForm = $("<div>", {
			class: "form-input-check",
			css: {
				display: "none"
			}
		});

		inlineForm.append(settings.geraDescPergunta());
		inlineForm.append(settings.geraDescMultipla());

		inlineForm.append(settings.geraObrigatorio());

		inlineForm.append($("<div>", {
			class: "row row-fluid",
			css: {
				"text-align": "center",
				"margin-top": "10px"
			}
		}).append(
			$("<button>", {
				class: "insere-pergunta btn btn-default",
				type: "button",
				text: "Inserir Pergunta",
				css: {
					"margin-top": "10px"
				},
				click: function() {

					settings.acaoInserirPergunta($(this), "checkbox");

				}
			})
		));

		return inlineForm;

	};

	var geraInputTextButtonDefault = function() {

		let divBtn = $("<div>", {
			css: {
				float: "left"
			}
		});

		let button = $("<button>", {
			class: "btn btn-primary",
			text: " Campo Texto",
			title: "Pergunta aberta para digitação",
			type: "button",
			css: {
				"margin-left": "10px"
			},
			click: function() {

				let pFooter = $(this).closest(".panel-footer");

				pFooter.find(".form-input-text, .form-input-check, .form-input-radio").hide("fast");
				pFooter.find(".form-input-text").show("fast");

			}
		}).prepend(
			$("<span>", {
				class: "glyphicon glyphicon-pencil"
			})
		);

		divBtn.append(button);

		return divBtn;

	};

	var geraInputCheckboxButtonDefault = function() {

		let divBtn = $("<div>", {
			css: {
				float: "left"
			}
		});

		let button = $("<button>", {
			class: "btn btn-primary",
			text: " Marcação Múltipla",
			title: "Pergunta do tipo checável",
			type: "button",
			css: {
				"margin-left": "10px"
			},
			click: function() {

				let pFooter = $(this).closest(".panel-footer");

				pFooter.find(".form-input-text, .form-input-check, .form-input-radio").hide("fast");
				pFooter.find(".form-input-check").show("fast");

			}
		}).prepend(
			$("<span>", {
				class: "glyphicon glyphicon-check"
			})
		);

		divBtn.append(button);

		return divBtn;

	};

	var geraInputRadioButtonDefault = function() {

		let divBtn = $("<div>", {
			css: {
				float: "left"
			}
		});

		let button = $("<button>", {
			class: "btn btn-primary",
			text: " Marcação Única",
			title: "Pergunta do tipo marcação",
			type: "button",
			css: {
				"margin-left": "10px"
			},
			click: function() {

				let pFooter = $(this).closest(".panel-footer");

				pFooter.find(".form-input-text, .form-input-check, .form-input-radio").hide("fast");
				pFooter.find(".form-input-radio").show("fast");

			}
		}).prepend(
			$("<span>", {
				class: "glyphicon glyphicon-record"
			})
		);

		divBtn.append(button);

		return divBtn;

	};

	var carregaConteudoDefault = function(jsonForm) {

		let titulo 	  = jsonForm.titulo;
		let descricao = jsonForm.descricao;

		divForm.append(settings.insereTituloGeral(titulo));
		divForm.append(settings.insereDescricaoGeral(descricao));

		$.each(jsonForm.formulario, function(indexGrupo, objGrupo){

			settings.geraGrupo();

			let grupoAtual = $(".panel:last");
			
			grupoAtual.find(".titulo_grupo").val(objGrupo["tituloGrupo"]);
			grupoAtual.find(".descricao_grupo").val(objGrupo["descricaoGrupo"]);

			$.each(objGrupo.perguntas, function(indexCampo, objCampo){

				if (objCampo["text"] != undefined || 
					objCampo["number"] != undefined || 
					objCampo["textarea"] != undefined ||
					objCampo["telefone"] != undefined ||
					objCampo["email"] != undefined
				) {

					let btnInsere = $(grupoAtual).find(".form-input-text").find(".insere-pergunta");

					var valorCampoText = "";
					var tipoCampoText  = "";

					if (objCampo["text"] != undefined) {

						tipoCampoText = "text";
						valorCampoText = objCampo["text"];

					} else if (objCampo["number"] != undefined) {

						tipoCampoText = "number";
						valorCampoText = objCampo["number"];

					} else if (objCampo["textarea"] != undefined) {

						tipoCampoText = "textarea";
						valorCampoText = objCampo["textarea"];

					} else if (objCampo["telefone"] != undefined) {

						tipoCampoText = "telefone";
						valorCampoText = objCampo["telefone"];

					} else if (objCampo["email"] != undefined) {

						tipoCampoText = "email";
						valorCampoText = objCampo["email"];

					}

					if (objCampo["obrigatorio"] == "t") {
						grupoAtual.find(".obrigatorio").prop("checked", true);
					} else {
						grupoAtual.find(".obrigatorio").prop("checked", false);
					}

					grupoAtual.find(".desc-pergunta").val(valorCampoText);
					grupoAtual.find(".tipo-campo option[value="+tipoCampoText+"]").prop("selected", true);

					settings.acaoInserirPergunta(btnInsere, "text");

				} else if (objCampo["checkbox"] != undefined) {

					let formCheck = $(grupoAtual).find(".form-input-check");
					let btnInsere = formCheck.find(".insere-pergunta");

					grupoAtual.find(".desc-pergunta").val(objCampo["checkbox"]["descricao"]);

					formCheck.find(".div-multi-input > button.btn-danger").click();

					if (objCampo["obrigatorio"] == "t") {
						grupoAtual.find(".obrigatorio").prop("checked", true);
					} else {
						grupoAtual.find(".obrigatorio").prop("checked", false);
					}

					$.each(objCampo["checkbox"]["options"], function(indexOption, valueOption){

						formCheck.find(".div-multi-input > .desc-marcacao:last").val(valueOption);
						formCheck.find(".div-multi-input > button.btn-info").click();

					});

					settings.acaoInserirPergunta(btnInsere, "checkbox");

				} else if (objCampo["radio"] != undefined) {

					let formRadio = $(grupoAtual).find(".form-input-radio");
					let btnInsere = formRadio.find(".insere-pergunta");

					grupoAtual.find(".desc-pergunta").val(objCampo["radio"]["descricao"]);
					
					formRadio.find(".div-multi-input > button.btn-danger").click();

					if (objCampo["obrigatorio"] == "t") {
						grupoAtual.find(".obrigatorio").prop("checked", true);
					} else {
						grupoAtual.find(".obrigatorio").prop("checked", false);
					}

					if (objCampo["justificar"] == "t") {
						grupoAtual.find(".justificativa").prop("checked", true);
					} else {
						grupoAtual.find(".justificativa").prop("checked", false);
					}

					$.each(objCampo["radio"]["options"], function(indexOption, valueOption){

						formRadio.find(".div-multi-input > .desc-marcacao:last").val(valueOption);
						formRadio.find(".div-multi-input > button.btn-info").click();

					});

					settings.acaoInserirPergunta(btnInsere, "radio");

				}

			});

			$(grupoAtual).find(".div-multi-input > button.btn-danger").click();
			$(grupoAtual).find(".desc-pergunta, .desc-marcacao").val("");

		});

		settings.geraBtnAddGrupo();

	}

})( jQuery )