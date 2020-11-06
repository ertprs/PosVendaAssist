Ext.onReady(function(){
	Ext.QuickTips.init();


	function linkOs(valor, p,record){
		return String.format(
			'<b><a href="os_press.php?os={1}&lu_os=sim&lu_fabrica={2}" target="_blank">{0}</a></b>',valor, record.id,record.data.fabrica
		);
	}

	var os = new Ext.FormPanel({
		labelAlign: 'top',
		frame:true,
		title: 'Consulta OS',
		bodyStyle:'padding:5px 5px 0',
		width: 500,
		items: [{
			layout:'column',
			items:[{
				columnWidth:.4,
				layout: 'form',
				items: [{
					xtype:'textfield',
					fieldLabel: 'Número da OS',
					name: 'os',
					anchor:'95%',
					id: 'os',
					tabIndex:1
				}, {
				}]
			},{
				columnWidth:.3,
				layout: 'form',
				items: [{
				},{
				}]
			},{
				columnWidth:.3,
				layout: 'form',
				items: [{
				}]
			}]
		},{
			buttons: [{
				text: 'Pesquisar',
				handler: function(){

					if ( Ext.get('os').dom.value != "" && Ext.get('os').dom.value.length < 4) {
						Ext.Msg.alert("Erro","Digite pelo menos 4 letras para pesquisar pelo número de OS");
						Ext.get('sua_os').focus;
						return false;
					}

					var valores= os.form.getValues();
					var store = new Ext.data.Store({
						url: 'consultas_ext.php?pesquisa=sim&'+Ext.urlEncode(valores),
						reader: new Ext.data.JsonReader({
							root: 'resultado',
							totalProperty: 'total',
							idProperty: 'os',
							successProperty: "sucesso"
						}, ['fabrica','fabrica_nome','sua_os','serie','nota_fiscal',{name: 'data_abertura', type: 'date',dateFormat: 'Y-m-d'},{name: 'data_fechamento', type: 'date',dateFormat: 'Y-m-d'},'consumidor','produto'])
					});

					store.load({params:{start:0, limit:30},callback: function(r,options,success){
						if(success==false){
							Ext.Msg.alert("Mensagem","Nenhum resultado encontrado");
						}
					}});
					
					var resultado = new Ext.grid.GridPanel({
						store: store,
						autoDestroy: true,
						columns: [
							{
								header: "Fábrica",
								width: 130,
								sortable: true,
								dataIndex: 'fabrica_nome'
							},{
								id:'os',
								header: "OS",
								width: 130,
								sortable: true,
								dataIndex: 'sua_os',
								renderer: linkOs
							},{
								header: "SÉRIE",
								width: 100,
								align:'center',
								sortable: true,
								dataIndex: 'serie'
							},{
								header: "NF",
								width: 80,
								align:'center',
								sortable: true,
								dataIndex: 'nota_fiscal'
							},{
								id:'data_abertura',
								header: "AB",
								align:'center',
								width: 50,
								sortable: true,
								dataIndex: 'data_abertura',
								renderer: Ext.util.Format.dateRenderer('d/m')
							},{
								header: "FC",
								width: 50,
								align:'center',
								sortable: true,
								dataIndex: 'data_fechamento',
								renderer: Ext.util.Format.dateRenderer('d/m')
							},{
								header: "CONSUMIDOR",
								width: 160,
								sortable: true,
								dataIndex: 'consumidor'
							},{
								header: "PRODUTO",
								width: 210,
								sortable: true,
								dataIndex: 'produto',
								id:'produto'
							}
						],
						closable: true,
						stripeRows: true,
						autoExpandColumn: 'produto',
						autoHeight:true,
						width:900,
						disableSelection:true,
						title:'Resultado',
						footer: true,
						viewConfig: {
							forceFit:true,
							enableRowBody:true
						},
						
						bbar: new Ext.PagingToolbar({
							pageSize: 30,
							store: store,
							displayInfo: true,
							displayMsg: 'Total de Resultado : {2}',
							emptyMsg: "Nenhum resultado encontrado"
						})
					});
					resultado.render('resultado');
					resultado.render('resultado');
				}
			}]
		}]
	});

	var tab_consulta = new Ext.TabPanel({
		renderTo:'consulta',
		resizeTabs:true,
		enableTabScroll:true,
		width:1000,
		defaults: {autoScroll:true,autoHeight:true},
		activeTab: 0,
		frame:true,
		items:[os]
	});

});


