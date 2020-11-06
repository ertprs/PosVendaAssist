<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$title = "Consulta de Pedido";
$aba = 6;
	$sql = "SELECT tbl_fabrica.fabrica
			FROM tbl_fabrica
			JOIN tbl_posto_fabrica USING(fabrica)
			WHERE  (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')
			AND  fabrica <> 0
			AND posto = $cook_posto ORDER BY fabrica";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		for($i =0;$i<pg_num_rows($res);$i++) {
			$fabricas .= ($i > 0) ? ",".pg_fetch_result($res,$i,'fabrica'): pg_fetch_result($res,$i,'fabrica');
		}
	}

if(isset($_REQUEST['pesquisa']) > 0) {
	$pedido       = (strlen($_GET['pedido']) > 0) ? $_GET['pedido'] : $_POST['pedido'];
	$data_inicial = (strlen($_GET['data_inicial']) > 0) ? $_GET['data_inicial'] : $_POST['data_inicial'];
	$data_final   = (strlen($_GET['data_final']) > 0) ? $_GET['data_final'] :$_POST['data_final'];
	$referencia   = (strlen($_GET['referencia']) > 0) ? $_GET['referencia'] : $_POST['referencia'];

	if(strlen($data_inicial)>0 and strlen($data_final)>0 and strlen($pedido) == 0) {

		$fnc  = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");
		$erro = pg_errormessage ($con) ;

		if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);

		$fnc  = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
		$erro = pg_errormessage ($con) ;

		if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		$add_1 = " AND tbl_pedido.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
	}

	if (strlen($pedido) > 0 ) {
		$add_2 .= " AND (substr(tbl_pedido.seu_pedido,4) like '%$pedido' OR tbl_pedido.seu_pedido = '$pedido' or tbl_pedido.pedido = $pedido) ";
	}

	if (strlen($referencia) > 0) {
		$add_3 .= " AND tbl_peca.referencia LIKE '%$referencia%' ";
	}
	
	$sql = "SELECT  count(DISTINCT pedido)
			FROM    tbl_pedido
			JOIN    tbl_tipo_pedido     USING (tipo_pedido)
			JOIN    tbl_pedido_item     USING (pedido)
			JOIN    tbl_peca            USING (peca)
			LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			LEFT JOIN tbl_linha         ON tbl_linha.linha = tbl_pedido.linha
			WHERE   tbl_pedido.posto   = $login_posto
			AND     tbl_pedido.fabrica in ($fabricas)
			$add_1 $add_2 $add_3 ";
	$res = pg_query($con,$sql);
	$qtde = (pg_num_rows($res) > 0) ? pg_fetch_result($res,0,0) : 0;

	$sql = "SELECT  tbl_pedido.pedido                                                  ,
					case
						when tbl_pedido.pedido_blackedecker > 499999 then
							lpad ((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 399999 then
							lpad ((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 299999 then
							lpad ((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 199999 then
							lpad ((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 99999 then
							lpad ((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
					else
						lpad ((tbl_pedido.pedido_blackedecker)::text,5,'0')
					end                                          AS pedido_blackedecker,
					tbl_pedido.seu_pedido                                              ,
					to_char(tbl_pedido.data,'YYYY-MM-DD') as data                      ,
					to_char(tbl_pedido.finalizado,'YYYY-MM-DD') as finalizado          ,
					tbl_pedido.total                                                   ,
					tbl_pedido.pedido_loja_virtual                                     ,
					tbl_tipo_pedido.descricao AS tipo_pedido_descricao                 ,
					tbl_linha.nome			  AS linha_descricao                       ,
					tbl_status_pedido.status_pedido AS id_status                       ,
					tbl_status_pedido.descricao AS xstatus_pedido                      ,
					tbl_fabrica.nome          AS fabrica_nome                          ,
					tbl_pedido.fabrica        
			FROM    tbl_pedido
			JOIN    tbl_fabrica         USING (fabrica)
			JOIN    tbl_tipo_pedido     USING (tipo_pedido)
			JOIN    tbl_pedido_item     USING (pedido)
			JOIN    tbl_peca            USING (peca)
			LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			LEFT JOIN tbl_linha         ON tbl_linha.linha = tbl_pedido.linha
			WHERE   tbl_pedido.posto   = $login_posto
			AND     tbl_pedido.fabrica in ($fabricas)
			$add_1 $add_2 $add_3 
			GROUP BY	tbl_pedido.pedido           ,
						tbl_pedido.pedido_blackedecker,
						tbl_pedido.seu_pedido         ,
						tbl_pedido.data               ,
						tbl_pedido.finalizado         ,
						tbl_pedido.total              ,
						tbl_tipo_pedido.descricao     ,
						tbl_status_pedido.status_pedido,
						tbl_status_pedido.descricao   ,
						tbl_linha.nome,
						tbl_pedido.pedido_loja_virtual,
						tbl_fabrica.nome              ,
						tbl_pedido.fabrica            
			ORDER BY tbl_pedido.fabrica,tbl_pedido.data DESC";
	if(strlen($_POST['start']) > 0 and strlen($_POST['limit']) > 0) {
		$sql .= " offset ".$_POST['start'] ." limit ".$_POST['limit'];
	}
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$resultados       = pg_fetch_all($res);
		$i = 0;
		echo "{'total':'".$qtde."','resultado': [";
		foreach($resultados as $resultado) {
			$pedido                = $resultado['pedido'];
			$pedido_blackedecker   = $resultado['pedido_blackedecker'];
			$seu_pedido            = $resultado['seu_pedido'];
			$data                  = $resultado['data'];
			$finalizado            = $resultado['finalizado'];
			$total                 = $resultado['total'];
			$xstatus_pedido        = $resultado['xstatus_pedido'];
			$id_status             = $resultado['id_status'];
			$tipo_pedido_descricao = $resultado['tipo_pedido_descricao'];
			$linha_descricao       = $resultado['linha_descricao'];
			$pedido_loja_virtual   = $resultado['pedido_loja_virtual'];
			$codigo_posto          = $resultado['codigo_posto'];
			$fabrica_nome          = $resultado['fabrica_nome'];
			$fabrica               = $resultado['fabrica'];

			$total = number_format($total,2,",",".");

			if (strlen($seu_pedido)>0){
				$pedido_blackedecker = fnc_so_numeros($seu_pedido);
			}

			$pedido_mostra = ($fabrica == 1) ? $pedido_blackedecker : $pedido;
			
			if ($fabrica==3 AND $pedido_loja_virtual=='t'){
				$tipo_pedido_descricao = "Loja Virtual";
			}

			echo ($i >0) ? ",": "";
			echo "{'fabrica':'$fabrica','fabrica_nome':'$fabrica_nome','pedido':'$pedido','pedido_mostra': '$pedido_mostra','data':'$data','finalizado':'$finalizado','valor_total':'$total','status':'$xstatus_pedido','tipo_pedido':'$tipo_pedido_descricao','linha':'$linha_descricao'}";
			$i++;
		}
		echo "] }";
	}else{
		echo "{'total':'0','sucesso':'false'}";
	}
	
	exit;
}

?>


<script>
/*!
 * Ext JS Library 3.0.3
 * Copyright(c) 2006-2009 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
* @class Ext.ux.ProgressBarPager
* @extends Object 
* Plugin (ptype = 'tabclosemenu') for displaying a progressbar inside of a paging toolbar instead of plain text
* 
* @ptype progressbarpager 
* @constructor
* Create a new ItemSelector
* @param {Object} config Configuration options
* @xtype itemselector 
*/
Ext.ux.ProgressBarPager  = Ext.extend(Object, {
	/**
 	* @cfg {Integer} progBarWidth
 	* <p>The default progress bar width.  Default is 225.</p>
	*/
	progBarWidth   : 225,
	/**
 	* @cfg {String} defaultText
	* <p>The text to display while the store is loading.  Default is 'Loading...'</p>
 	*/
	defaultText    : 'Carregando...',
    	/**
 	* @cfg {Object} defaultAnimCfg 
 	* <p>A {@link Ext.Fx Ext.Fx} configuration object.  Default is  { duration : 1, easing : 'bounceOut' }.</p>
 	*/
	defaultAnimCfg : {
		duration   : 1,
		easing     : 'bounceOut'	
	},												  
	constructor : function(config) {
		if (config) {
			Ext.apply(this, config);
		}
	},
	//public
	init : function (parent) {
        
        if(parent.displayInfo){
            this.parent = parent;
            var ind  = parent.items.indexOf(parent.displayItem);
            parent.remove(parent.displayItem, true);
            this.progressBar = new Ext.ProgressBar({
                text    : this.defaultText,
                width   : this.progBarWidth,
                animate :  this.defaultAnimCfg
            });                 
           
            parent.displayItem = this.progressBar;
            
            parent.add(parent.displayItem); 
            parent.doLayout();
            Ext.apply(parent, this.parentOverrides);        
            
            this.progressBar.on('render', function(pb) {
                pb.mon(pb.getEl().applyStyles('cursor:pointer'), 'click', this.handleProgressBarClick, this);
            }, this, {single: true});
                        
        }
          
    },
	// private
	// This method handles the click for the progress bar
	handleProgressBarClick : function(e){
		var parent = this.parent;
		var displayItem = parent.displayItem;
		
		var box = this.progressBar.getBox();
		var xy = e.getXY();
		var position = xy[0]-box.x;
		var pages = Math.ceil(parent.store.getTotalCount()/parent.pageSize);
		
		var newpage = Math.ceil(position/(displayItem.width/pages));
		parent.changePage(newpage);
	},
	
	// private, overriddes
	parentOverrides  : {
		// private
		// This method updates the information via the progress bar.
		updateInfo : function(){
			if(this.displayItem){
				var count   = this.store.getCount();
				var pgData  = this.getPageData();
				var pageNum = this.readPage(pgData);
				
				var msg    = count == 0 ?
					this.emptyMsg :
					String.format(
						this.displayMsg,
						this.cursor+1, this.cursor+count, this.store.getTotalCount()
					);
					
				pageNum = pgData.activePage; ;	
				
				var pct	= pageNum / pgData.pages;	
				
				this.displayItem.updateProgress(pct, msg, this.animate || this.defaultAnimConfig);
			}
		}
	}
});
Ext.preg('progressbarpager', Ext.ux.ProgressBarPager);



Ext.onReady(function(){
	Ext.QuickTips.init();

	function linkPedido(valor, p,record){
		return String.format(
			'<b><a href="pedido_finalizado.php?pedido={1}&lu_pedido=sim&lu_fabrica={2}" target="_blank">{0}</a></b>',valor, record.id,record.data.fabrica
		);
	}

	var pesquisa = new Ext.FormPanel({
		labelAlign: 'top',
		frame:true,
		title: 'PESQUISA DE PEDIDO',
		bodyStyle:'padding:5px 5px 0',
		width: 400,
		items: [{
			layout:'column',
			items:[{
				columnWidth:1,
				layout: 'form',
				items: [{
					xtype:'textfield',
					fieldLabel: 'Número do Pedido para Consulta',
					name: 'pedido',
					id:   'pedido',
					anchor:'70%'
				}, {
					xtype:'textfield',
					fieldLabel: 'Consulta pelo código da peça',
					name: 'codigo_peca',
					id:   'codigo_peca',
					anchor:'70%'
				},{
						xtype:'datefield',
						name:'data_inicial',
						id:'data_inicial',
						format:'d/m/Y',
						invalidText : "{0} não é uma data válida,o formato é DD/MM/AAAA",
						fieldLabel:'Data Inicial',
						width:150
				},{
						xtype:'datefield',
						name:'data_final',
						id:'data_final',
						format:'d/m/Y',
						invalidText : "{0} não é uma data válida,o formato é DD/MM/AAAA",
						value: new Date(),
						fieldLabel:'Data Final',
						width:150
				}]
			}]
		},{
			buttons: [{
				text: 'Pesquisar',
				handler: function(){
					if ( Ext.get('data_inicial').dom.value == "" && Ext.get('data_final').dom.value == "" && Ext.get('pedido').dom.value == "" && Ext.get('codigo_peca').dom.value == "" ) {
						Ext.Msg.alert("Erro","Preenche os parametros para fazer pesquisa");
						Ext.get('mes').focus;
						return false;
					}

					
					var valores= pesquisa.form.getValues();
					$('#resultado').html('');
					var store = new Ext.data.Store({
						url: '<?$PHP_SELF?>?pesquisa=sim&'+Ext.urlEncode(valores),
						reader: new Ext.data.JsonReader({
							root: 'resultado',
							totalProperty: 'total',
							idProperty: 'pedido',
							successProperty: "sucesso"
						}, ['fabrica','fabrica_nome','pedido','pedido_mostra',{name: 'data', type: 'date',dateFormat: 'Y-m-d'},{name: 'finalizado', type: 'date',dateFormat: 'Y-m-d'},'status','tipo_pedido','linha','valor_total'])
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
								width: 100,
								sortable: true,
								dataIndex: 'fabrica_nome'
							},{
								id:'pedido',
								header: "Pedido",
								width: 100,
								sortable: true,
								dataIndex: 'pedido_mostra',
								renderer: linkPedido
							},{
								header: "Data",
								width: 80,
								align:'center',
								sortable: true,
								dataIndex: 'data',
								renderer: Ext.util.Format.dateRenderer('d/m/Y')
							},{
								header: "Finalizado",
								width: 80,
								align:'center',
								sortable: true,
								dataIndex: 'finalizado',
								renderer: Ext.util.Format.dateRenderer('d/m/Y')

							},{
								header: "Status",
								align:'center',
								width: 150,
								sortable: true,
								dataIndex: 'status',
								id: 'status'
							},{
								header: "Tipo",
								width: 80,
								align:'center',
								sortable: true,
								dataIndex: 'tipo_pedido'
							},{
								header: "Linha",
								width: 140,
								sortable: true,
								dataIndex: 'linha'
							},{
								header: "Valor Total",
								width: 80,
								sortable: true,
								align:'right',
								dataIndex: 'valor_total'
							}
						],
						stripeRows: true,
						autoExpandColumn: 'status',
						autoHeight:true,
						frame:true,
						width:900,
						title:'Resultado',
						footer: true,
						bbar: new Ext.PagingToolbar({
							pageSize: 30,
							store: store,
							displayInfo: true,
							displayMsg: 'De {0} a {1} Total de Resultado : {2}',
							emptyMsg: "Nenhum resultado encontrado",
							plugins: new Ext.ux.ProgressBarPager()
						})
					});
					resultado.render('resultado');
				}
			}]
		}]
	});

	pesquisa.render('consulta');

});


</script>
<center>
<br clear=both>
<div id="tabs">
	<ul>
	<li class="tab spacer">&nbsp;</li>
	<li class="tab selectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="tab unselectedtab" style="display:block" onclick="document.location='login_unico.php'">
		<span id="tab1_view_title">Início</span>
	</li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="tab unselectedtab" style="display:block" onclick="document.location='fluxo-r/rg_recebimento.php'">
		<span id="tab1_view_title">Lote de Revenda</span>
	</li>

	<?  if($login_posto==4311){ ?>

		<li class="tab selectedtab_r">&nbsp;</li>
		<li class="tab unselectedtab_l">&nbsp;</li>

		<li id="tab1_view" class="tab unselectedtab" style="display:block" onclick="document.location='estoque_consulta.php'">
			<span id="tab1_view_title">Estoque</span>
		</li>

		<li class="tab selectedtab_r">&nbsp;</li>
		<li class="tab unselectedtab_l">&nbsp;</li>

		<li id="tab1_view" class="tab unselectedtab" style="display:block" onclick="document.location='distrib/'">
			<span id="tab1_view_title">Distrib</span>
		</li>

	<? }?>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="tab unselectedtab" style="display:block" onclick="document.location='os_consulta_multi.php'">
		<span id="tab1_view_title">Consulta OS </span>
	</li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li id="tab1_view" class="tab selectedtab" style="display:block" onclick="document.location='pedido_relacao_multi.php'">
		<span id="tab1_view_title">Consulta Pedido</span>
	</li>

	<li class="tab selectedtab_r">&nbsp;</li>
	<li class="tab unselectedtab_l">&nbsp;</li>

	<li class="tab unselectedtab_r">&nbsp;</li>
	<li class="tab addtab">&nbsp;&nbsp;</li>
	<li class="tab" id="addstuff"></li>
</ul>
</div>
<br/>
<div id='consulta'></div>
<br/>
<div id='resultado'></div>
<br/>
</center>

