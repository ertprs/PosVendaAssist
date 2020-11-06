<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';

$btn_acao 	= $_REQUEST['acao'];
$data_inicial 	= $_REQUEST['data_inicial'];
$data_final 	= $_REQUEST['data_final'];

$num_pedido = $_REQUEST['pedido'];
$msg_erro = array();
$msgErrorPattern01 = "Informe um parametro para pesquisa.";
$msgErrorPattern02 = "A data de consulta deve ser no máximo de 6 meses.";

function ConverterParaSegundo($arr){
	$total = ((($arr[0]*24)*60)*60);
	$total += (($arr[1]*60)*60);
	$total += ($arr[2]*60);
	return $total; 
}

function ConverterParaHoras($TotalSegundos){
	$horas = round($TotalSegundos / 3600);
	$minutos = round(($TotalSegundos - ($horas * 3600)) / 60);
	$segundos = round($TotalSegundos % 60);
	return "$horas.$minutos";
}

function ConverterParaDiasHoras($TotalSegundos){
	$dias = floor($TotalSegundos / (3600 *24));
	$d1 = (($dias * 24) * 3600);
	$horas = floor(($TotalSegundos - $d1) / 3600);
	$minutos = floor(( ($TotalSegundos-$d1) - ($horas * 3600)) / 60);
	$segundos = floor($TotalSegundos % 60);
	return "$dias $horas.$minutos";
}

##INCIO DA VALIDACAO DE DATAS
if(strlen($btn_acao)>0) {
	if((!$data_inicial OR !$data_final) AND empty($num_pedido)) {
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
		$msg_erro["campos"][] = "pedido";
	}

	##TIRA A BARRA
	if(count($msg_erro["msg"]) == 0 and (strlen($data_inicial) > 0 )  ) {
		$dat = explode ("/", $data_inicial );
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(count($msg_erro["msg"]) == 0 and ( strlen($data_final) > 0  ) ) {
		$dat = explode ("/", $data_final );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(count($msg_erro["msg"]) == 0 AND (strlen($data_inicial)>0  OR strlen($data_final) > 0 )) {
		$d_ini = explode ("/", $data_inicial);//tira a barra
		$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		$d_fim = explode ("/", $data_final);//tira a barra
		$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($nova_data_final < $nova_data_inicial) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}

		##Fim Validação de Datas
		if(count($msg_erro["msg"]) == 0) {
			$sql = "SELECT '$nova_data_final'::date - INTERVAL '6 MONTHS' > '$nova_data_inicial'::date ";
			$res = pg_query ($con,$sql);
			if (pg_fetch_result($res,0,0) == 't') {
				$msg_erro["msg"][]    = $msgErrorPattern02;
				$msg_erro["campos"][] = "data";
			}
		}
	}
}
	if(empty($num_pedido)){
		$cond = " and tbl_pedido.data between '$nova_data_inicial 00:00:00' and '$nova_data_final 23:59:59' ";
	}else{
		$cond = "and tbl_pedido.pedido in($num_pedido) ";		
	}

	$sqlTempPedido = "with timeline as ( 
				select 
					tbl_pedido_status.pedido, 
					tbl_pedido_status.data, 
					tbl_pedido.data as data_abertura_pedido,
					tbl_pedido_status.status, 
					tbl_status_pedido.descricao as descricao_status_pedido 

				from tbl_pedido_status 

				Join tbl_pedido on tbl_pedido_status.pedido = tbl_pedido.pedido 
				join tbl_status_pedido on tbl_status_pedido.status_pedido = tbl_pedido_status.status  

				WHERE tbl_pedido.fabrica = $login_fabrica  

				$cond
				ORDER BY tbl_pedido_status.pedido, tbl_pedido_status.data 

				), 
				timelineSeq as (
				select *, row_number() over ( order by pedido) from timeline
				),
				timelineSeq2 as (
				select *, row_number() over ( order by pedido) from timeline
			)

			select t1.data as data1, t2.data as data2, t1.pedido, t1.descricao_status_pedido, t1.status as status1, t2.status as status2,  t1.data_abertura_pedido, 
			((case when t2.data is null  and (t1.status = 31 OR t1.status = 14) then  t1.data - t1.data_abertura_pedido else t2.data - t1.data end )) as intervalo	

			into temp tempo_timeline_pedido_2
			from timelineSeq as t1
			left join timelineSeq as t2 
					on t1.pedido= t2.pedido 
					and t1.row_number = t2.row_number -1 ";

	$res = pg_query($con, $sqlTempPedido);

	$sql = "select * from tempo_timeline_pedido_2 ";
	$res = pg_query($con, $sql);

	for($i=0; $i<pg_num_rows($res); $i++){
		$pedido 					= pg_fetch_result($res, $i, 'pedido');
		$descricao_status_pedido 	= pg_fetch_result($res, $i, 'descricao_status_pedido');
		$intervalo 					= pg_fetch_result($res, $i, 'intervalo');

		$intervalos = explode(".", $intervalo);
			
		$intervalo2 = $intervalos[0];

		$intervalo2 = str_replace("day", "dia", $intervalo2);

		$dados[$pedido][$descricao_status_pedido] = $intervalo2;	
	}

	//pre_echo($dados);

	$cabecalho = array (
						  'Aguardando aprovação' => '',
						  'Aguardando Estoque' => '',
						  'Aguardando Separação' => '',
						  'Em separação' => '',
						  'Faturado Integral' => '',
						  'Faturado Parcial' => '',
						  'Em Trânsito' => '',
						  'Entregue' => '',
						  'Cancelado Total' => '',
						);

	$file     = "xls/relatorio-timeline-pedido-{$login_fabrica}.csv";
    $fileTemp = "/tmp/relatorio-timeline-pedido-{$login_fabrica}.csv" ;
    $fp     = fopen($fileTemp,'w');
if(isset($_POST['gerar_excel'])){	
    $head = "Pedido;";
    foreach($cabecalho as $chave => $valor){
		$head .= $chave.";";
	}    
    fwrite($fp, $head);
    $tbody = "";   

    foreach($dados as $pedido => $status){
		$tbody .= "\n\r";	
		$tbody .= "$pedido;";
		foreach($cabecalho as $key => $value){
			$xkey = $status[$key];			
			$tbody .= "$xkey;";
		}
	}

	fwrite($fp, $tbody);
	fclose($fp);
	if(file_exists($fileTemp)){
	    system("mv $fileTemp $file");

	    if(file_exists($file)){
	        echo $file;
	    }
	}
	exit;
}

$sql_grafico = "select 
	descricao_status_pedido, 
	(sum(intervalo)/ (select count(distinct pedido) from tempo_timeline_pedido_2 where status1 = tbl_status_pedido.status_pedido ) ) as media1, 
	(select count(distinct pedido) from tempo_timeline_pedido_2 where status1 = tbl_status_pedido.status_pedido ) as qtde_pedido, 
	EXTRACT(epoch FROM justify_hours(sum(intervalo) /  (select count(distinct pedido) from tempo_timeline_pedido_2 where status1 = tbl_status_pedido.status_pedido ) ))/3600  as media_horas 
	from tempo_timeline_pedido_2 	
	join tbl_status_pedido on tbl_status_pedido.status_pedido = tempo_timeline_pedido_2.status1 
	GROUP BY descricao_status_pedido, qtde_pedido order by descricao_status_pedido ";
$res_grafico = pg_query($con, $sql_grafico);

for($a=0; $a<pg_num_rows($res_grafico); $a++){
	$descricao_status_pedido = pg_fetch_result($res_grafico, $a, 'descricao_status_pedido');
	$media1 = pg_fetch_result($res_grafico, $a, 'media1');
	$media2 = substr(pg_fetch_result($res_grafico, $a, 'media_horas'),0,5);
	$qtde_pedido = pg_fetch_result($res_grafico, $a, 'qtde_pedido');

	$media1 = str_replace("day", "dia", $media1);

	$media1 = explode(".", $media1);

	$media1 = $media1[0];

	$media2 = $media2 /24;

	$series = "{
					    name: '$descricao_status_pedido $media1',
					    info: '$descricao_status_pedido',
					    data: [
					      [ '<br><b> Quantidade de Pedidos</b> $qtde_pedido', $media2]
					    ]
					  }, ". $series;
}



//}


$layout_menu = "gerencia";
$title = "RELATÓRIO DE TIMELINE DE PEDIDO";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);

include("plugin_loader.php");

?>

<script type="text/javascript" charset="utf-8">

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		function formatItem(row) { return row[0] + " - " + row[1];}

		function formatResult(row) { return row[0]; }
	});

</script>
<script src="https://code.highcharts.com/highcharts.js"></script>

<script>
    var chart;
    $(document).ready(function() {
        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'grafico',
                type: 'bar'
            },
            title: {
                text: 'Timeline de Pedidos'
            },
            subtitle: {
			    text: ''
			  },
            xAxis: {
			    categories: ['Status']
			  },
			  yAxis: {
			    min: 0,
			    tickInterval:1,
			    endOnTick: false,
			    title: {
			      text: 'Dias'
			    }
			  },
			  tooltip: {
			    formatter: function() {
			        return '<b>' + this.series.name  + '</b> '+ this.key ; 
			    }
			},
			  legend: {
			    reversed: true
			  },
			  plotOptions: {
			    series: {
			      stacking: 'normal',
			      dataLabels: {
			        enabled: true,
			        formatter: function() {
				        return '<b>' + this.series.userOptions.info  + '</b> ' ; 
				    },
			        color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white'
			      }
			    },
			  },

			  series: [
			  	  <?=$series?>
			  ]
        });
    });
    </script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php } ?>

<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form class="form-search form-inline tc_formulario" name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="container tc_container">
		<div class='row-fluid'>
			<div class='span3'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	</div>

	<div class='row-fluid'>
		<div class='span3'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?> '>
				<label class='control-label' for='codigo_posto'>Pedido</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="pedido" name="pedido" class='span8' maxlength="8" value="<? echo $num_pedido ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<br />
	<center>
		<input type="button" class='btn' value="Pesquisar" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar">
		<input type="hidden" name="acao">
	</center>
	<br />
</form>
<?php if($resultado == true){?>
<div class="container">
	<div class='alert '><h4>Nenhum resultado encontrado</h4></div>
</div>
<?php } ?>

<?php if(pg_num_rows($res_grafico)>0){ ?>
	<div id="grafico" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
	<div class="excel">
		<?php
			$jsonPOST = excelPostToJson($_POST);
		?>
		
		 <div id='gerar_excel' class="btn_excel">
	        <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
	        <span><img src='imagens/excel.png' /></span>
	        <span class="txt">Gerar Arquivo Excel</span>
	    </div>
	</div>
<?php
 }
include "rodape.php" ;
?>
