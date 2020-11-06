var tokenDelimiter = ':';
var chamado_detalhe_grid = "";
var hd_chamado_grid = "";
var analise_grid = "";
var tab_chamado ;

function interno(valor, p,record){
	if(record.data.interno=='t'){
		return String.format(
		'<div style="width:100%; background-color:#d2e4fc;text-align:center;" class="interno">INTERNO</div>'
		)
	}
}

function mostraChamado(chamado,anexo){
	var mostraAnexo = "";
	if (anexo){
		mostraAnexo = "sim";
	}
	var item = new Ext.data.Store({
		url: 'adm_atendimento_lista_ext.php',
		reader: new Ext.data.JsonReader({
			root: 'chamados',
			idProperty: 'hd_chamado_item'
		}, [
			'hd_chamado_item',{name: 'data', mapping: 'data', type: 'date', dateFormat: 'c'},'autor','interno','fone','anexo'
		])
	});

	item.setDefaultSort('data', 'desc');
	item.load({
		params:{
			hd_chamado:chamado,
			anexo:mostraAnexo
		}
	});

	var chamadoStore = new Ext.data.Store({
		url: 'adm_atendimento_lista_ext.php',
		reader: new Ext.data.JsonReader({
			root: 'chamados',
			idProperty: 'hd_chamado'
		}, ['hd_chamado']
		)
	});

	chamado_detalhe_grid = new Ext.grid.GridPanel({
		width:980,
		autoHeight:true,
		store: item,
		trackMouseOver:false,
		sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
		loadMask:{msg:"Carregando"},
		stripeRows: true,
		frame:true,
		viewConfig: {
			forceFit:true,
			enableRowBody:true,
			showPreview:true,
			getRowClass : function(record, rowIndex, p, store){
				if(this.showPreview){
					var item = record.data.hd_chamado_item;
					var text = $.ajax({
						type: "GET",
						url: 'adm_atendimento_lista_ext.php',
						data: 'comentario='+item,
						cache: false,
						async: false
					 }).responseText;

					if (record.data.interno == 't'){
						var comentario = '<div style="color: #3300CC;width: 100%; height:100%; font-size:20px;padding:10px 5px 10px 10px; line-height:25px; ">'+text +'</div>';
					}else{
						var comentario = '<div style="width: 100%; height:100%; font-size:20px;padding:10px 5px 10px 10px; line-height:25px;">'+text+'</div>';
					}
					p.body = '<p style="font-size:20px">'+comentario+'</p>';
					return 'x-grid3-row-expanded';
				}
				return 'x-grid3-row-collapsed';
			},
			templates: {
				 cell: new Ext.Template(
					'<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} x-selectable {css}" style="{style}" tabIndex="0" {cellAttr}>',
					'<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
					'</td>'
				 )
			}
		},
		tbar: new Ext.ux.StatusBar({
			forceLayout: true,
			items: [
				'-'
			]
		}),
		columns:[
		{
			header: "Data",
			dataIndex: 'data',
			width: 100,
			renderer: Ext.util.Format.dateRenderer('d/m/Y H:i'),
			sortable:true
		},{
			header: "Autor",
			dataIndex: 'autor',
			width: 100
		},{
			header: "Fone",
			dataIndex: 'fone',
			width: 100
		},{
			header: "Interno",
			dataIndex: 'interno',
			width: 100,
			renderer:interno
		},{
			header: "Anexo",
			width: 100,
			dataIndex: 'anexo',
			align: 'center',
			sortable:true
		}]
	});

	tab_chamado.add({
		title: 'HD '+chamado,
		iconCls: 'tabs',
		tabWidth:135,
		items:[chamado_detalhe_grid],
		closable:true
		}).show();
};

function analiseChamado(hd_chamado,status){
	var analise = new Ext.data.Store({
		url: 'adm_atendimento_lista_ext.php?hd_analise='+hd_chamado,
		reader: new Ext.data.JsonReader({
		 root: 'chamados',
			idProperty: 'interacao'
		}, [
		'analise','interacao','hd_chamado_analise'
		])
	});

	analise.setDefaultSort('interacao', 'asc');

	var hd_analise = new Ext.grid.GridPanel({
		width:950,
		autoHeight:true,
		store: analise,
		loadMask:{msg:"Carregando"},
		split: true,
		style:'x-box-layout-ct tamanhoLetra',
		region: 'north',
		sm: new Ext.grid.RowSelectionModel({singleSelect: true}),
		viewConfig: {
			forceFit: true
		},
		columns:[
		{
			header: "Interação",
			dataIndex: 'interacao',
			width: 90,
			sortable:false
		},{
			header: "Análise",
			dataIndex: 'analise',
			width: 860,
			sortabel:false
		}]
	});

	var detalhe = [
		'{analise}'
	];
	var analiseDetalhe = new Ext.Template(detalhe);

	var textarea = new Ext.form.TextArea({
			name      : 'analises',
			fieldLabel: 'Análise',
			id        : 'analises',
			width     : 800,
			height    : 205
	})

	analise_grid = new Ext.Panel({
		frame: true,
		width: 980,
		autoHeight:true,
		layout: 'anchor',
		items: [
			hd_analise,
			{
			id: 'detalhe_analise',
			region: 'center',
			bodyStyle: {
			background: '#ffffcc',
			padding: '10px',
			fontSize:'22px'
			},
			html: 'Selecione a interação para visualizar a análise toda'
			}
		]
	})

	hd_analise.getSelectionModel().on('rowselect', function(sm, rowIdx, r) {
		var detalhe_analise = Ext.getCmp('detalhe_analise');
		analiseDetalhe.overwrite(detalhe_analise.body, r.data);
	});

	if(status =='S/Análise'){
		analise_grid.add(textarea);
	}
		analise.load();
		tab_chamado.add({
				title: 'Análise '+hd_chamado,
				iconCls: 'tabs',
				tabWidth:300,
				animCollapse:true,
				draggable: true,
				items:[analise_grid],
				fbar: new Ext.Toolbar({
						items: [{
							text:'Adicionar Análise',
							scale: 'large',
							handler:function(){
								var analises = Ext.get('analises').dom.value;
								Ext.Ajax.request({
									url: 'adm_atendimento_lista_ext.php',
									method: 'POST',
									params:  {
										analise : hd_chamado,
										valor   : analises
									},
									success:function(response){
										if(response.responseText=='ok'){
											hd_analise.store.reload();
											textarea.setValue('');
										}else{
											Ext.Msg.alert('Erro','Verifique os dados digitados');
										}
									}
								})
							}
						}
				]}),
				closable:true
		}).show();
}

function passaTrabalho(hd_chamado){
	var devStore = new Ext.data.Store({
		sortInfo: {field:'nome_completo', direction:'ASC'},
		url: 'adm_atendimento_lista_ext.php?admin_combo=desenvolvedor',
		reader: new Ext.data.JsonReader({
			root: 'admins',
			idProperty: 'admin'
		}, [
		'admin','nome_completo'
		])
	});

	var desenvolvedor = new Ext.form.ComboBox({
		store: devStore,
		displayField:'nome_completo',
		hiddenName:'desenvolvedor',
		name:'desenvolvedor',
		width: 130,
		id:'desenvolvedor',
		valueField:'admin',
		typeAhead: true,
		triggerAction: 'all',
		forceSelection:true,
		emptyText:'Desenvolvedor',
		allowBlank: false
	});

	var suporteStore = new Ext.data.Store({
		url: 'adm_atendimento_lista_ext.php?admin_combo=suporte',
		sortInfo: {field:'nome_completo', direction:'ASC'},
		reader: new Ext.data.JsonReader({
			root: 'admins',
			idProperty: 'admin'
		}, [
		'admin','nome_completo'
		])
	});

	var suporte = new Ext.form.ComboBox({
		store: suporteStore,
		displayField:'nome_completo',
		hiddenName:'suporte',
		name:'suporte',
		width: 130,
		id:'suporte',
		valueField:'admin',
		typeAhead: true,
		triggerAction: 'all',
		forceSelection:true,
		emptyText:'Suporte'
	});

    var spot = new Ext.ux.Spotlight({
	        easing: 'easeOut',
	        duration: .3
    });

	var prioridade = new Ext.form.ComboBox({
			typeAhead: true,
			triggerAction: 'all',
			lazyRender:true,
			mode: 'local',
			forceSelection:true,
			hiddenName:'prioridade',
			name:'prioridade',
			id:'prioridade',
			emptyText:'Prioridade',
			width: 90,
			store: new Ext.data.ArrayStore({
						id: 0,
						fields: [
							'id'
						],
						data: [[1],[2],[3],[4],[5],[6],[7],[8],[9],[10]]
					}),
			valueField: 'id',
			displayField: 'id'
	});

	var janela, popup;
	if(!janela){
		popup = new Ext.FormPanel({
			width: 500,
			height:130,
			buttonAlign: 'center',
			items:  [{
				layout:'column',
				items:[{
					columnWidth:.5,
					layout: 'form',
					items: [{
						xtype: 'numberfield',
						name: 'horas_analisadas',
						id: 'horas_analisadas',
						width: '50',
						allowBlank: false,
						fieldLabel: 'Horas Analisadas'
					},
					prioridade,
					desenvolvedor
					]
				},{
					columnWidth:.5,
					layout: 'form',
					items: [
					{
					xtype: 'checkbox',
					name: 'faturado',
					fieldLabel: 'Faturado',
					id:'faturado',
					value:'true',
					allowBlank: false
					},
					suporte
					]
				}]
			}],
			buttons: [{
				text:'Salvar',
				handler: function(){
					var sup_valor =suporte.getValue();
					var dev_valor = desenvolvedor.getValue();
					var hr_anal = Ext.get('horas_analisadas').dom.value;
					var prioridade = Ext.get('prioridade').dom.value;
					var fat  = Ext.getCmp('faturado').getValue();
					if(popup.getForm().isValid()){
						Ext.Ajax.request({
							url: 'adm_atendimento_lista_ext.php',
							method: 'POST',
							params:  {
								passar: hd_chamado,
								suporte:sup_valor,
								dev: dev_valor,
								hr_anal: hr_anal,
								prioridade: prioridade,
								fat:fat
							},
							success:function(response){
								hd_chamado_grid.store.reload();
								janela.close();
								spot.hide();
							}
						})
					}
				}
			},{
				text: 'Cancelar',
				handler: function(){
					janela.close();
					spot.hide();
				}
			}]
		});
	}
	janela = new Ext.Window({
		width: 500,
		height:230,
		id:'janela',
		autoDestroy:true,
		title:'Chamado '+hd_chamado,
		closable:false,
		items:[popup]
	})
	janela.show(this);
	spot.show('janela');
}

function listaHd(tipo, id){
	Ext.History.add(''+tokenDelimiter+tipo+'|'+id+tokenDelimiter+''+tokenDelimiter+'')
}


function fixed(hd_chamado){
	var suporteStore = new Ext.data.Store({
		url: 'adm_atendimento_lista_ext.php?admin_combo=suporte',
		sortInfo: {field:'nome_completo', direction:'ASC'},
		reader: new Ext.data.JsonReader({
			root: 'admins',
			idProperty: 'admin'
		}, [
		'admin','nome_completo'
		])
	});

	var suporte = new Ext.form.ComboBox({
		store: suporteStore,
		displayField:'nome_completo',
		hiddenName:'suporte',
		name:'suporte',
		width: 130,
		id:'suporte',
		valueField:'admin',
		typeAhead: true,
		triggerAction: 'all',
		forceSelection:true,
		emptyText:'Suporte'
	});

    var spot = new Ext.ux.Spotlight({
	        easing: 'easeOut',
	        duration: .3
    });

	var janela, popup;
	if(!janela){
		popup = new Ext.FormPanel({
			width: 500,
			autoHeight: true,
			buttonAlign: 'center',
			items:  [{
				layout:'column',
				items:[{
					columnWidth:.5,
					layout: 'fit',
					items: [{
						xtype: 'textarea',
						hideLabel: true,
						name: 'texto',
						id: 'texto',
				        width: 30,
				        height: 30,
						flex: 1
					}]
				},{
					columnWidth:.5,
					layout: 'form',
					items: [
					suporte
					]
				}]
			}],
			buttons: [{
				text:'Salvar',
				handler: function(){
					var valores=popup.getForm().getValues(true);
					if(popup.getForm().isValid()){
						Ext.Ajax.request({
							url: 'adm_atendimento_lista_ext.php',
							method: 'POST',
							params:  {
								fixed: hd_chamado,
								valores:valores
							},
							success:function(response){
								hd_chamado_grid.store.reload();
								janela.close();
								spot.hide();
							}
						})
					}
				}
			},{
				text: 'Cancelar',
				handler: function(){
					janela.close();
					spot.hide();
				}
			}]
		});
	}
	janela = new Ext.Window({
		width: 500,
		height:120,
		id:'janela',
		autoDestroy:true,
		title:'Chamado '+hd_chamado,
		closable:false,
		items:[popup]
	})
	janela.show(this);
	spot.show('janela');

}

function deployed(hd_chamado){
	var adminStore = new Ext.data.Store({
		url: 'adm_atendimento_lista_ext.php?admin_combo=all',
		sortInfo: {field:'nome_completo', direction:'ASC'},
		reader: new Ext.data.JsonReader({
			root: 'admins',
			idProperty: 'admin'
		}, [
		'admin','nome_completo'
		])
	});

	var all = new Ext.form.ComboBox({
		store: adminStore,
		displayField:'nome_completo',
		hiddenName:'suporte',
		name:'suporte',
		width: 130,
		id:'suporte',
		valueField:'admin',
		typeAhead: true,
		triggerAction: 'all',
		forceSelection:true,
		emptyText:'Suporte'
	});

    var spot = new Ext.ux.Spotlight({
	        easing: 'easeOut',
	        duration: .3
    });

	var janela, popup;
	if(!janela){
		popup = new Ext.FormPanel({
			width: 420,
			autoHeight: true,
			buttonAlign: 'center',
			items:  [{
				layout:'column',
				items:[{
					columnWidth:.8,
					layout: 'fit',
					items: [{
					            xtype: 'radiogroup',
								name:'texto',
					            id: 'texto',
					            items: [
					                {boxLabel: 'Validar', name: 'texto', inputValue: 'Validar chamado em staging'},
					                {boxLabel: 'Efetivado', name: 'texto', inputValue: 'Efetivado', checked: true}]
					}]
				},{
					columnWidth:.8,
					layout: 'form',
					items: [
					all
					]
				}]
			}],
			buttons: [{
				text:'Salvar',
				handler: function(){
					var valores=popup.getForm().getValues(true);
					if(popup.getForm().isValid()){
						Ext.Ajax.request({
							url: 'adm_atendimento_lista_ext.php',
							method: 'POST',
							params:  {
								deploy: hd_chamado,
								valores:valores
							},
							success:function(response){
								hd_chamado_grid.store.reload();
								janela.close();
								spot.hide();
							}
						})
					}
				}
			},{
				text: 'Cancelar',
				handler: function(){
					janela.close();
					spot.hide();
				}
			}]
		});
	}
	janela = new Ext.Window({
		width: 400,
		height:150,
		id:'janela',
		autoDestroy:true,
		title:'Chamado '+hd_chamado,
		closable:false,
		items:[popup]
	})
	janela.show(this);
	spot.show('janela');


}

Ext.onReady(function(){
	Ext.History.init();

	function statusChamado(valor, p,record){
		var status = record.data.status;
		var hd_chamado= record.data.hd_chamado;
		var cor;
		switch (status){
				case 'S/Análise':
				case 'S/Requisitos':
				case 'Correção':
				case 'Venceu PRAZO':
					cor = "#FF0000";
					break;
				case 'Execução':
				case 'C/Análise':
				case 'C/Requisitos':
					cor = '#0000FF';
					break;
				case 'Aguard.Execução':
				case 'S/Orçamento':
					cor = '#FFA500';
					break;
				case 'C/Orçamento':
					cor = '#A52A2A';
					break;
				case 'Aguard.Admin':
					cor = '#F710F4';
					break;
				case 'Efetivação':
				case 'EfetivaçãoHomologação':
					cor = '#40F209';
					break;
				case 'Documentação':
					cor='#ffb3ff';
					break;
				case 'Parado':
				case 'Suspenso':
				case 'Novo':
					cor = '#999999';
					break;
				case 'Validação':
				case 'ValidaçãoHomologação':
					cor = '#999966';
					break;
				default:
					cor = '#000000';
					break;
		}

		return String.format(
			"<span style='color:"+cor+"'>{0}</span>",valor
		)
	}

	function prioridadeChamado(valor, p,record){
		var prioridade = record.data.prioridade;
		var cor;
		switch (prioridade){
				case 'Alta':
				case '1':
				case '2':
				case '3':
					cor = '#'+Math.random().toString(16).substr(-6);
					break;
				case 'Média':
				case '4':
				case '5':
				case '6':
					cor = '#'+Math.random().toString(16).substr(-6);
					break;
				case 'Baixa':
				case '7':
				case '8':
				case '9':
					cor = '#'+Math.random().toString(16).substr(-6);
					break;
				default:
					cor = '#'+Math.random().toString(16).substr(-6);
					break;
		}

		return String.format(
			"<span style='color:"+cor+"'>{0}</span>",valor
		)
	}


	function linkChamado(valor, p,record){
		var bola = null;
		var prior = "";
		var inicio = "";
		var deploy ="";
		var status     = record.data.status;
		var nome_incial = record.data.nome_inicial;
		var resposta   = record.data.exigir_resposta ;
		var prioridade = record.data.prioridade ;
		var trabalho   = record.data.trabalho;
		var atendente  = record.data.atendente;
		var login_admin= record.data.login_admin;
		var tipo_chamado = record.data.tipo_chamado;
		var bgcolor, fontcolor;


		if(status == 'Efetivação' || status == 'EfetivaçãoHomologação'){
			deploy  = "<a href='javascript:deployed({0})'><img src='imagem/deploy.png' align='absmiddle' width='25' border='0' ></a>";
		}

		if (status =='C/Análise' || status=='Aguard.Execução'){
			if ( atendente == login_admin){
					inicio= "<span id='{0}'><a href='javascript: passaTrabalho({0})'><img src='imagem/continue.gif' align='absmiddle' width='26' border='0'></a></span>";
			}
		}

		if(status =="S/Análise"){
			bola = "<img src='/assist/admin/imagens_admin/analyse.gif' align='absmiddle' width='12'> ";
		}else{
				if (status == "C/Orçamento" || status=='S/Orçamento') {
						bola = "<img src='imagem/money.png' align='absmiddle'  width='12'> ";
				}else if (resposta == "t" && status !='Cancelado' && status != "Resolvido" ) {
					bola = "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
				}else{
						if (status == "Resolvido" || status == "Cancelado") {
								bola = "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
						}else{
								bola = "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
						}
				}
		}

		bgcolor = '#373865';
		fontcolor = '#FAC81A';
		var hd = (valor == trabalho) ? "{0}<p style='content: attr(data-letters);display:inline-block;width:1.9em; height:1.9em;  line-height:1.9em;  text-align:center;   border-radius:50%;  background:"+bgcolor+";   vertical-align:middle;  margin-right:1em; color:"+fontcolor+"; font-weight:bold' >{2}</p>": "{0}";
		return String.format(
			bola+'<a href="javascript: mostraChamado({0}); analiseChamado({0},\'{1}\')">'+hd+'</a>'+prior+inicio+deploy,valor,status,nome_incial
		);
	}

	function previsaoCliente(valor, p, record) {
		var passou_pc = record.data.passou_pc ;
		if(passou_pc == 't') {
			var data = new Date(valor);
			var previsao = data.format('d/m/y');
			return String.format("<span style='color:red'>{0} </span>",previsao);
		}else{
			if(valor) {
			var data = new Date(valor);
			return data.format('d/m/y');
			}else{
				return String.format('');
			}
		}
	}

	function previsaoInterna(valor, p, record) {
		var passou_pi = record.data.passou_pi ;
		if(passou_pi == 't') {
			var data = new Date(valor);
			var previsao = data.format('d/m/y');
			return String.format("<span style='color:red'>{0} </span>",previsao);
		}else{
			if(valor) {
			var data = new Date(valor);
			return data.format('d/m/y');
			}else{
				return String.format('');
			}
		}
	}
	function abreChamado(valor, p,record){
		return String.format(
			'<a href="adm_chamado_detalhe.php?hd_chamado={1}" target="_blank">{0}</a>',valor,record.data.hd_chamado
		);
	}

	function adminHd(valor, p,record){
		return String.format(
			'<a href="javascript:listaHd(\'admin\',{1})">{0}</a>',valor,record.data.atendente
		);
	}

	function suporteHd(valor, p,record){
		return String.format(
			'<a href="javascript:listaHd(\'suporte\',{1})">{0}</a>',valor,record.data.suporte_id
		);
	}
	function tipoChamado(valor, p,record){
		if (record.data.tipo_chamado == '5'){
			return String.format(
			'<a href="javascript:listaHd(\'tipo_hd\',{1})" style="font-weight:bold; color:red"><img src="imagem/error.png" border="0" width="15">{0}</a>',valor,record.data.tipo_chamado
			);
		}else{
			return String.format(
			'<a href="javascript:listaHd(\'tipo_hd\',{1})">{0}</a>',valor,record.data.tipo_chamado
			);
		}
	}

	function logo(valor, p,record){
		switch(record.data.fabrica_id){
			case '1':
			return String.format(
			'<a href="javascript: listaHd(\'fabrica\',{0})"><img src="../logos/black_admin1.jpg" border="0" width="60" height="20"></a>',record.data.fabrica_id
			);
			break;
			default:
			if(record.data.logo == '') {
				return String.format(
					'{0}',valor
					);

			}else{
				return String.format(
					'<a href="javascript: listaHd(\'fabrica\',{1})"><img src="../logos/{0}" border="0" width="60" height="20"></a>',record.data.logo,record.data.fabrica_id
				);
			}
			break;
		}
	}

	var filters = new Ext.ux.grid.GridFilters({
		encode: false,
		local: true,
		filters: [{
			type: 'date',
			dataIndex: 'data'
		},{
			type:'string',
			dataIndex:'fabrica'
		},{
			type:'string',
			dataIndex:'status'
		},{
			type:'string',
			dataIndex:'tipo'
		},{
			type:'string',
			dataIndex:'autor'
		},{
			type:'string',
			dataIndex:'titulo'
		},{
			type:'numeric',
			dataIndex:'hd_chamado'
		}]
	});

	var store = new Ext.data.GroupingStore({
		sortInfo: {field:'data', direction:'ASC'},
		url: 'adm_atendimento_lista_ext.php',
		groupField:'status',
		reader: new Ext.data.JsonReader(
		{
			root: 'chamados',
			totalProperty: 'total',
			idProperty: 'hd_chamado'
		},
		['hd_chamado','titulo','suporte','suporte_id', 'fabrica',{name: 'data', mapping: 'data', type: 'date', dateFormat: 'c'},'status', 'tipo','tipo_chamado','prazo','exigir_resposta','horas_analisadas','http','atendente','prioridade','trabalho','login_admin','atendente_nome','fabrica_id','logo','desenvolvedor',{name:'horas_cobradas',type:'float'},'nome_inicial',{name:'previsao' , type:'date', dateFormat:'c'},{name:'previsao_interna', mapping:'previsao_interna',  type:'date' , dateFormat: 'c' }, 'passou_pc', 'passou_pi', {name:'valor_desconto',type:'float'}, {name:'valor_total',type:'float'}, 'equipe', 'impacto_financeiro', 'clasPrioriddade','pre_etapa']
		)
	});

	var expander = new Ext.ux.grid.RowExpander({
		tpl : new Ext.Template(
			'<p><b>Tela:</b>{http} </p>'
		)
	});

	var tipoStore = new Ext.data.ArrayStore({
		fields: ['valor', 'descricao'],
		data : [
		['fabrica','Fábrica'],
		['admin','Admin'],
		['tipo_hd','Tipo Chamado'],
		['status','Status'],
		['suporte','Suporte'],
		['equipe','Equipe']
		]
	});

	var tipoCombo = new Ext.form.ComboBox({
		store: tipoStore,
		displayField:'descricao',
		hiddenName:'valor',
		width: 120,
		name:'valor',
		id:'combo_tipo',
		valueField:'valor',
		typeAhead: true,
		triggerAction: 'all',
		emptyText:'Tipo',
		mode:'local',
		forceSelection:true
	});

	tipoCombo.on('select', function(){
		var combo_tipo = tipoCombo.getValue();
		tipoItemCombo.reset();
		tipoItemCombo.store.load({params:{combo_tipo: combo_tipo}});
	});

	var tipoItemStore = new Ext.data.Store({
		sortInfo: {field:'descricao', direction:'ASC'},
		url: 'adm_atendimento_lista_ext.php?pesquisa_tipo=sim',
		reader: new Ext.data.JsonReader({
		 root: 'tipoItems',
			idProperty: 'valor'
		}, [
			'valor','descricao'
		])
	});

	var tipoItemCombo = new Ext.form.ComboBox({
		store: tipoItemStore,
		displayField:'descricao',
		hiddenName:'valor',
		name:'valor',
		width: 130,
		id:'tipoItemCombo',
		valueField:'valor',
		typeAhead: true,
		triggerAction: 'all',
		emptyText:'Selecione o tipo',
		mode:'local',
		forceSelection:true
	});


	tipoItemCombo.on('select',function(pesquisa,record){
		var valor = tipoCombo.getValue()+'|'+record.data.valor;
		if (record.data.valor == 'Resolvido'){
			var janela='';
			var fabricaStore = '';
			var fabricaCombo = '';
			fabricaStore = new Ext.data.Store({
				sortInfo: {field:'descricao', direction:'ASC'},
				url: 'adm_atendimento_lista_ext.php?pesquisa_tipo=sim',
				reader: new Ext.data.JsonReader({
				 root: 'tipoItems',
					idProperty: 'valor'
				}, [
					'valor','descricao'
				])
			});

			 fabricaCombo = new Ext.form.ComboBox({
				store: fabricaStore,
				displayField:'descricao',
				hiddenName:'fabrica',
				name:'fabrica',
				width: 130,
				id:'fabricaCombo',
				valueField:'valor',
				typeAhead: true,
				triggerAction: 'all',
				mode:'local',
				forceSelection:true
			});

			fabricaCombo.reset();
			fabricaCombo.store.load({params:{combo_tipo: 'fabrica'}});


			if(!janela){
				janela = new Ext.Window({
					layout:'fit',
					width:200,
					height:200,
					plain: true,
					labelWidth: 125,
					autoDestroy:true,
					closeAction: 'close',
					items:  [{
						fieldLabel: 'Data Inicial',
						name: 'data_inicio',
						id: 'data_inicio',
						xtype: 'datefield',
						allowBlank:false,
						editable:false,
						format: 'd/m/Y'
					},{
						fieldLabel: 'Data Final',
						name: 'data_fim',
						id: 'data_fim',
						xtype: 'datefield',
						allowBlank:false,
						editable:false,
						format: 'd/m/Y'
					},
					fabricaCombo
					],

					buttons: [{
					text:'Pesquisar',
					handler: function(){
						var data_inicial = Ext.get('data_inicio').dom.value;
						var data_final = Ext.get('data_fim').dom.value;
						var fabricaValor = fabricaCombo.getValue();
						if (data_inicial && data_final){
							Ext.History.add(''+tokenDelimiter+'status|Resolvido!'+data_inicial+'!'+data_final+'!'+fabricaValor+tokenDelimiter+''+tokenDelimiter+'')
							janela.close();
						}else{
							Ext.Msg.alert('Erro','Selecione um intervalo de tempo para pesquisa');
						}
					}
					},{
						text: 'Cancelar',
						handler: function(){
							janela.close();
						}
					}]
				});
			}
			janela.show(this);
		}else{
		}
		Ext.History.add(''+tokenDelimiter+valor+tokenDelimiter+''+tokenDelimiter+'');
			if(record.data.descricao=='DESENVOLVEDOR') {
				hd_chamado_grid.getStore().groupBy('atendente_nome');
			}else{
				hd_chamado_grid.getStore().groupBy('status');
			}

	})

		var chamadoStore = new Ext.data.Store({
				url: 'adm_atendimento_lista_ext.php',
				reader: new Ext.data.JsonReader({
						root: 'chamados',
						idProperty: 'hd_chamado'
				}, [
						'hd_chamado'
				])
		});

		var total = new Ext.Toolbar.TextItem('');

		var notas = new Ext.Button({
				id:"sticky",
				tooltip:"Notas",
				tooltipType:"title",
				type:"button",
				cls:"x-btn-text-icon",
				icon:"imagem/sticky_thumb.png",
				scale:'large',
				listeners: {
						click:function(){
								if ($('.stickyWidget').length > 0) {
										$('.stickyWidget').slideToggle('');
								}else{
										$('#bloco').append('<span class="widget stickyWidget" ></span>');
										$('.stickyWidget').append('<textarea></textarea>')
										$('.widget').append('<span class="closeWidget" id="closeWidget"><img src="imagem/closebox.png" alt=""/></span>').draggable();
										$('.closeWidget').click(function(){
											Ext.get('closeWidget').parent().switchOff({
													easing:'easeOut',
													duration:1,
													remove: true,
													useDisplay:false
											});
										});
								}
						}
				}
	});
		

var tipoData = new Ext.CycleButton({
	showText: true,
	id:'tipo_data',
	items: [{
		text:'Aberto',
		checked:true
	},{
		text:'Resolvido'
	},{
		text:'Requisito Aprovado'
	},{
		text:'Orçamento Aprovado'
	}]
	});

    var summary = new Ext.ux.grid.GroupSummary();

	
	Ext.QuickTips.init();
	Ext.apply(Ext.QuickTips.getQuickTip(), {
		maxWidth: 200,
		minWidth: 100,
		showDelay: 50,      // Show 50ms after entering target
		trackMouse: true
	});
	hd_chamado_grid = new Ext.grid.GridPanel({
		autoWidth:true,
		autoHeight:true,
		title:'Lista de Chamados',
		store: store,
		footer: true,
		layout:'accordion',
		plugins:[expander,filters,summary],
		enableRowBody:true,
		stripeRows: true,
		collapsible: true,
		stateful:true,
		cls:'font-size: 18px;',
		stateId:'hd_chamado',
		bbar:{},
		tbar: {                        // configured using the anchor layout
			xtype    : 'container',
			layout   : 'anchor',
			height   : 48 * 2,
			defaults : { height : 48, anchor : '100%' },
			items: [
				new Ext.Toolbar({
				items: [
				{
					text:'Meus',
					scale: 'medium',
					handler:function(){
						Ext.History.add('Meus'+tokenDelimiter+''+tokenDelimiter+''+tokenDelimiter+'');
						hd_chamado_grid.getStore().groupBy('status');
					}
				},
				{
					text:'Todos',
					scale: 'medium',
					handler:function(){
						Ext.History.add('todos'+tokenDelimiter+''+tokenDelimiter+''+tokenDelimiter+'');
						hd_chamado_grid.getStore().groupBy('atendente_nome').sort('previsao', 'ASC');
					}
				},
				{
					text:'Erro',
					scale: 'medium',
					handler:function(){
						Ext.History.add('2'+tokenDelimiter+''+tokenDelimiter+''+tokenDelimiter+'');
						hd_chamado_grid.getStore().groupBy('status');
					}
				},
				{
					text:'Aberto',
					scale: 'medium',
					handler:function(){
						Ext.History.add('aberto'+tokenDelimiter+''+tokenDelimiter+''+tokenDelimiter+'');	
						hd_chamado_grid.getStore().groupBy('status');
					}
				},
				{
					text:'Resolvido',
					scale: 'medium',
					handler:function(){
						Ext.History.add('resolvido'+tokenDelimiter+''+tokenDelimiter+''+tokenDelimiter+'');
						hd_chamado_grid.getStore().groupBy('equipe');
					}
				},
				{
					text:'Prazo',
					scale: 'medium',
					handler:function(){
						Ext.History.add('prazo'+tokenDelimiter+''+tokenDelimiter+''+tokenDelimiter+'');
						hd_chamado_grid.getStore().groupBy('previsao').sort('previsao', 'ASC');
					}
				},
				{
					text:'Prazo Interno',
					scale: 'medium',
					handler:function(){
						Ext.History.add('prazo_interno'+tokenDelimiter+''+tokenDelimiter+''+tokenDelimiter+'');
						hd_chamado_grid.getStore().groupBy('previsao_interna').sort('previsao_interna','ASC');
					}
				},
				{
					text:'Interno',
					scale: 'medium',
					handler:function(){
						Ext.History.add('interno'+tokenDelimiter+''+tokenDelimiter+''+tokenDelimiter+'');
						hd_chamado_grid.getStore().groupBy('atendente_nome');
					}
				},
				{
					icon:'imagem/atualiza.gif',
					scale: 'large',
					handler:function(){
						store.reload({
							callback:function(){
							Ext.Ajax.request({
								url: 'adm_atendimento_lista_ext.php',
								method: 'POST',
								params:  {
								total_chamado: 'sim'
								},
								success:function(response){
								var resultado = response.responseText.split('||')
								Ext.mensagem.msg('Total: ','Total Suporte: ',resultado[0],resultado[1],'Total Erro: ',resultado[2],'Total Aberto: ',resultado[3],'Total Resolvido: ',resultado[4]);
								Ext.mensagem.msg2(resultado[5]);
								}
							});
							Ext.fly(total.getEl()).update('Total HD: ' + this.getTotalCount());
							}
						});
					}
				},
				tipoCombo,
				{xtype: 'tbspacer',width:10},
				tipoItemCombo,
				{xtype: 'tbspacer',width:10},
				'-',
				total,
				{xtype: 'tbspacer',width:10},
				{
					scale: 'large',
					cls:'x-btn-text-icon',
					id:'calendar',
					handler:function(){
						window.open('calendario.php');
					}

				}

				]
			}),
			new Ext.Toolbar({
				items:  [
					{xtype: 'tbspacer',width:10},
					{
						name: 'data_inicial',
						id: 'data_inicial',
						xtype: 'datefield',
						emptyText:'Data Inicial',
						allowBlank:false,
						editable:false,
						format: 'd/m/Y'
					},
					{xtype: 'tbspacer',width:10},
					{
						emptyText:'Data Final',
						name: 'data_final',
						id: 'data_final',
						xtype: 'datefield',
						allowBlank:false,
						editable:false,
						format: 'd/m/Y'
					},
				'-',
					tipoData,
					new Ext.Button ({
						type:"button",
						text:'Pesquisar',
						scale:'medium',
						listeners: {
							click:function(){
								this.disable();
								var data_inicial = Ext.get('data_inicial').dom.value;
								var data_final = Ext.get('data_final').dom.value;
								var data = tipoData.getText();
								if(!data) { data='aberto'; }
								
								if (data_inicial && data_final){
									Ext.History.add(''+tokenDelimiter+'status|'+data+'!'+data_inicial+'!'+data_final+'!'+tokenDelimiter+''+tokenDelimiter+'')
								}else{
									Ext.Msg.alert('Erro','Selecione um intervalo de tempo para pesquisa');
								}
								this.enable();
							}
						}
					}),
					{xtype: 'tbspacer',width:5},
					notas,
					{xtype: 'tbspacer',width:10},
					new Ext.ux.form.SearchField({
							store: store,
							width:150,
							height:40,
							allowBlank: false,
							emptyText:'Pesquisar HD',
							listeners: {
								render: function(c) {
									Ext.QuickTips.register({
										target: this.getEl(),
										title:'Dica Pesquisa',
										dismissDelay:10000,
										text: 'Pesquisa por titulo, digite mais de 5 letras, e por numero mais de 3 numeros. Clique no X apos a consulta para voltar.'
									});
								}
							}
						})

				]
			})
		]},
		loadMask: {msg:'Carregando...'},
		cm: new Ext.grid.ColumnModel({
			columns:[expander,
			{
				header: "Nº",
				dataIndex: 'hd_chamado',
				sortable:true,
				width: 100,
				renderer:linkChamado,
				filter:{
					type:'numeric'
				}
			},{
				header: "Título",
				dataIndex: 'titulo',
				width: 200,
				sortable: true,
				renderer:abreChamado,
				filter:{
					type:'string'
				}
			},{
				header: "Prioridade",
				dataIndex: 'prioridade',
				width: 50,
				hidden: true, 
				sortable: true,
				renderer: prioridadeChamado,
				filter:{
					type:'string'
				}
            },{
                header: 'Horas',
                dataIndex: 'horas_cobradas',
                width: 50,
                sortable: true,
				hidden: true,
                filter: {
                  type: 'numeric'
                },
				summaryType : 'sum'
            },{
				header: "Status",
				dataIndex: 'status',
				width: 140,
				sortable: true,
				renderer:statusChamado,
				filter:{
					type:'string'
				}
			},{
				header: "Tipo",
				dataIndex: 'tipo',
				width: 120,
				align: 'center',
				sortable:true,
				renderer:tipoChamado,
				filter:{
					type:'string'
				}
			},{
				id: 'data',
				header: "Data",
				dataIndex: 'data',
				width: 90,
				renderer: Ext.util.Format.dateRenderer('d/m/Y'),
				sortable: true,
				filter:{
					type:'date'
				}
			},{
				header: "Fábrica",
				dataIndex: 'fabrica',
				width:90,
				sortable: true,
				renderer:logo,
				filter:{
					type:'string'
				}
			},{
				header: "Fábrica Nome",
				dataIndex: 'fabrica',
				width:90,
				sortable: true,
				hidden: true,
				filter:{
					type:'string'
				}
			},{
				header: "Atend. Resp.",
				dataIndex: 'suporte',
				sortable: true,
				hidden: true,
				width: 100,
				renderer: suporteHd,
				filter:{
					type:'string'
				}
			},{
				header: "Atendente",
				dataIndex: 'atendente_nome',
				renderer:adminHd,
				sortable: true
			},{
				header: "Programador",
				dataIndex: 'desenvolvedor',
				hidden: true,
				sortable: true
			},{
				header: "Pre. Cli",
				dataIndex: 'previsao',
				renderer: previsaoCliente,
				sortable: true,
				filter:{
					type:'date'
				},
				hidden: true
			},{
				header: "Pre. Int",
				dataIndex: 'previsao_interna',
				renderer: previsaoInterna, 
				sortable: true,
				filter:{
					type:'date'
				}
			},{
                header: 'Valor',
                dataIndex: 'valor_total',
                sortable: true,
				hidden: true,
				summaryType: 'sum',
                summaryRenderer: Ext.util.Format.brMoney,
                filter: {
                  type: 'float'
                }
            },{
                header: 'Desconto',
                dataIndex: 'valor_desconto',
                sortable: true,
				hidden: true,
                filter: {
                  type: 'numeric'
                }
            },{
                header: 'Equipe',
                dataIndex: 'equipe',
                sortable: true,
				hidden: true
            },{
                header: 'Impacto Financeiro',
                dataIndex: 'impacto_financeiro',
                sortable: true,
				hidden: true
            },{
                header: 'Class. SLA',
                dataIndex: 'clasPrioriddade',
                sortable: true,
				hidden: true
			},{
                header: 'Pre. Etapa',
                dataIndex: 'pre_etapa',
                sortable: true,
				hidden: true
            }]
		}),
		view: new Ext.grid.GroupingView({
			forceFit:true,
			groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Chamados" : "Chamado"]})',
			emptyGroupText:'Nenhum resultado',
			hideGroupedColumn:true,
			templates: {
				cell: new Ext.Template(
					'<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} x-selectable {css}" style="{style}" tabIndex="0" {cellAttr}>',
					'<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
					'</td>'
				)
			}
		})
	});

	store.load({
		params:{tipo:'Meus'},
		callback:function(){
			Ext.fly(total.getEl()).update('Total HD: ' + this.getTotalCount());
		}
	});

	Ext.History.on('change', function(token){
		if(token){
			var parts = token.split(tokenDelimiter);
			var tipos= parts[0];
			var combo_tipo = parts[1];
			var hd_chamado = parts[2];

			if (tipos){
				store.load({
				params:{tipo:tipos},
				callback:function(){
					Ext.fly(total.getEl()).update('Total HD: ' + this.getTotalCount());
				}});
			}else{
				if (combo_tipo){
					combo_tipo = combo_tipo.split('|');
					if (combo_tipo[0]=='admin') {
						store.load({
						params:{admin:combo_tipo[1]},
						callback:function(){
						Ext.fly(total.getEl()).update('Total HD: ' + this.getTotalCount());
						}});
					}

					if (combo_tipo[0]=='fabrica') {
						store.load({
						params:{fabrica:combo_tipo[1]},
						callback:function(){
						Ext.fly(total.getEl()).update('Total HD: ' + this.getTotalCount());
						}});
					}

					if (combo_tipo[0]=='tipo_hd') {
						store.load({
						params:{tipo_chamado:combo_tipo[1]},
						callback:function(){
						Ext.fly(total.getEl()).update('Total HD: ' + this.getTotalCount());
						}});
					}

					if (combo_tipo[0]=='status') {
						store.load({
						params:{status:combo_tipo[1]},
						callback:function(){
						Ext.fly(total.getEl()).update('Total HD: ' + this.getTotalCount());
						}});
					}

					if (combo_tipo[0]=='suporte') {
						store.load({
						params:{suporte:combo_tipo[1]},
						callback:function(){
						Ext.fly(total.getEl()).update('Total HD: ' + this.getTotalCount());
						}});
					}
					if (combo_tipo[0]=='equipe') {
						store.load({
						params:{equipe:combo_tipo[1]},
						callback:function(){
						Ext.fly(total.getEl()).update('Total HD: ' + this.getTotalCount());
						}});
					}
				}

				if (hd_chamado){
					store.load({
					params:{query:hd_chamado},
					callback:function(){
					Ext.fly(total.getEl()).update('Total HD: ' + this.getTotalCount());
					}});
				}
			}
		}else{
			Ext.History.add('Meus');
		}
	});

	var exportButton = new Ext.ux.Exporter.Button({
		component: hd_chamado_grid,
		text     : "Gerar Excel"
    });
    hd_chamado_grid.getBottomToolbar().add(exportButton);

	tab_chamado = new Ext.TabPanel({
		renderTo:'chamados',
		resizeTabs:true,
		enableTabScroll:true,
		autoWidth:true,
		defaults: {autoScroll:true,autoHeight:true},
		activeTab: 0,
		frame:true,
		plugins: new Ext.ux.TabScrollerMenu(),
		items:[hd_chamado_grid], 
		listeners:{
			render: {
				fn: function(){
					Ext.TaskMgr.start({
						run: function(){
							store.reload();
						},
						interval: 360000
					});
				},
				delay: 360000
			}
		}
	});

	Ext.get('calendar').update(new Date().getDate()).addClass('calendar');
});
