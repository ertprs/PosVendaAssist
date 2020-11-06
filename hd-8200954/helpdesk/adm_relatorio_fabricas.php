<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$TITULO = "Relatórios de fábricas";

?>
<? include "menu.php"; ?>

<link rel="stylesheet" type="text/css" href="css/css/ext-all.css"/>
<script type="text/javascript" src="js/ext-jquery-adapter.js"></script>
<script type="text/javascript" src="js/ext-all.js"></script>
<script type="text/javascript" src="js/chamado_ext.js"></script>
<script>

	Ext.onReady(function(){
		Ext.QuickTips.init();

		<?
		$sql = " SELECT perl,nome,agenda,programa
		FROM tbl_perl
		JOIN tbl_fabrica USING(fabrica) 
		ORDER BY perl";
		$res = pg_query($con,$sql);
		$resultados = pg_fetch_all($res);
		echo "var myData = [ \n";
		$i = 0;
		foreach($resultados as $resultado){
			echo ($i > 0) ? ",\n":"";
			echo "['",$resultado['nome'],"','",$resultado['agenda'],"','",$resultado['programa'];
			$sqls= "SELECT inicio_processo,fim_processo,substr(log,1,100) as log
					FROM tbl_perl_processado
					WHERE inicio_processo::date=CURRENT_DATE
					AND perl=".$resultado['perl']." 
					ORDER BY inicio_processo DESC LIMIT 1
					";
			$ress = @pg_query($con,$sqls);
			echo "','",@pg_fetch_result($ress,0,inicio_processo),"','",@pg_fetch_result($ress,0,fim_processo),"','",str_replace("\r","",str_replace("\n","",@pg_fetch_result($ress,0,log)));
			echo "']";
			$i++;
		}
		echo "\n];";
		?>

	   var reader = new Ext.data.ArrayReader({}, [
			{name: 'fabrica'},
			{name: 'agenda'},
			{name: 'perl'},
			{name: 'inicio', type: 'date', dateFormat: 'c'},
			{name: 'fim', type: 'date', dateFormat: 'c'},
			{name: 'log'}
		]);

		var dados = new Ext.data.GroupingStore({
            reader: reader,
            data: myData,
            groupField:'fabrica'
        });

		var expander = new Ext.ux.grid.RowExpander({
			tpl : new Ext.Template(
				'<p><b>Log:</b>{log} </p>'
			)
		});

		var grid = new Ext.grid.GridPanel({
			store: dados,
			columns: [
				expander,
				{header: 'Fábrica', width: 75,  dataIndex: 'fabrica'},
				{header: 'Agenda', width: 75, sortable: true,  dataIndex: 'agenda'},
				{header: 'Inicio', width: 150, sortable: true, dataIndex: 'inicio',			renderer: Ext.util.Format.dateRenderer('d/m/Y h:i:s')
				},
				{header: 'Fim', width: 150, sortable: true, dataIndex: 'fim',								renderer: Ext.util.Format.dateRenderer('d/m/Y h:i:s')
				},
				{header: 'Perl', width: 300, sortable: true, dataIndex: 'perl',id:'perl'}
			],
			 view: new Ext.grid.GroupingView({
				forceFit:true,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Perls" : "Perl"]})',
				emptyGroupText:'Nenhum resultado',
				hideGroupedColumn:true
			}),
			stripeRows: true,
			autoExpandColumn: 'perl',
			autoHeight:true,
			width: 900,
			title: 'Perls',
			collapsible: true,
			plugins: expander,
			loadMask:{msg:"Carregando"},
			animCollapse: false,
			renderTo: 'resultado'
		});
		
	});


</script>

<center>
<br clear=both>
<br/>
<div id='resultado'></div>
<br/>
</center>

<? include 'rodape.php'; ?>