<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';

$cachebypass=md5(time());

$btn_acao 	= $_POST['acao'];
$data_inicial 	= $_POST['data_inicial_01'];
$data_final 	= $_POST['data_final_01'];
$num_os = $_GET['os'];
$data_atual = $_GET['data_atual'];

if(!empty($data_atual)){
	$data_inicial = $data_atual;
	$data_final = $data_atual;
}

$resultado = false;


//https://codepen.io/pen/?editors=0010 - grafico

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
	if((!$data_inicial OR !$data_final) AND empty($num_os)) {
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
		$msg_erro["campos"][] = "os";
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

if((strlen($btn_acao) > 0) && (count($msg_erro["msg"]) == 0)) {

	$num_os = $_POST['os'];

	if(empty($os)){
		$condOs = " and tbl_os.data_abertura between '$nova_data_inicial 00:00:00' and '$nova_data_final 23:59:59' ";
		
	}else{
		$condOs = "and tbl_os.sua_os = '$os'";
	}

	$sqlTemp = "SELECT 
				tbl_os.fabrica, 
				tbl_os.os, 
				tbl_os.data_abertura, 
				tbl_os.data_fechamento 
				into temp temp_os
				FROM tbl_os 
				WHERE fabrica = $login_fabrica  
				$condOs ";
	$resTmep = pg_query($con, $sqlTemp);

	//union para buscar status do callcenter
	if (in_array($login_fabrica, [141])) {

		$sqlTimeline = "SELECT sub_timeline.*
						 INTO TEMP timeline
							FROM (
								SELECT tbl_os_historico_checkpoint.*
								FROM tbl_os_historico_checkpoint	
								WHERE  tbl_os_historico_checkpoint.tg_grava ~ 'fn_os' 
								and tbl_os_historico_checkpoint.fabrica = $login_fabrica 
								and tbl_os_historico_checkpoint.os in(select os from temp_os)
								UNION
								SELECT 1 as os_historico_checkpoint,
										tbl_hd_chamado.fabrica,
								 	    tbl_hd_chamado_extra.os,
								 	    0 as status_checkpoint,
								 	    'fn_os' as tg_grava,
								 	    tbl_hd_chamado.data as data_input
								 FROM tbl_hd_chamado
								 JOIN tbl_hd_chamado_extra USING(hd_chamado)
								 WHERE tbl_hd_chamado_extra.os in(
								 	SELECT os FROM temp_os
								 )
								 AND tbl_hd_chamado.fabrica = {$login_fabrica}
							) sub_timeline
							ORDER BY sub_timeline.data_input
						 	    ";
	} else {
		$sqlTimeline = " SELECT tbl_os_historico_checkpoint.*
				into temp timeline
				FROM tbl_os_historico_checkpoint
				WHERE  tbl_os_historico_checkpoint.tg_grava ~ 'fn_os'
				and tbl_os_historico_checkpoint.fabrica = $login_fabrica
				and tbl_os_historico_checkpoint.os in(select os from temp_os)
				ORDER BY os_historico_checkpoint";
	}

	$resTimeline = pg_query($con, $sqlTimeline);

	$sqlLimpa = "DELETE from timeline using timeline t1 where t1.os = timeline.os and t1.data_input = timeline.data_input and timeline.os_historico_checkpoint < t1.os_historico_checkpoint";
	$resLimpa = pg_query($con, $sqlLimpa);


	$sql = "with timeline as (
				select 
				timeline.os_historico_checkpoint,
				timeline.os, 
				timeline.status_checkpoint, 
				temp_os.data_abertura, 
				temp_os.data_fechamento,

				data_input as data 
				from timeline 
				join temp_os on temp_os.os = timeline.os and temp_os.fabrica = $login_fabrica
				where 
				timeline.tg_grava ~ 'fn_os'
				
				order by timeline.os, timeline.status_checkpoint

				), 
				timelineSeq as (
				select *, row_number() over ( order by os, os_historico_checkpoint ) from timeline
				),
				timelineSeq2 as (
				select *, row_number() over ( order by os, os_historico_checkpoint ) from timeline
			)

			select t1.data_abertura, t1.data_fechamento, t1.os, t1.status_checkpoint, 
			
			((case when t2.data is null and t1.status_checkpoint <> 9 then now() else t2.data end ) - t1.data) as intervalo 
			
			into temp tempo_timeline from timelineSeq as t1
left join timelineSeq as t2 on t1.os = t2.os and t1.row_number = t2.row_number -1 ";

	$res = pg_query($con, $sql);

	if (in_array($login_fabrica, [141])) {
		$cabecalho = array (
						  'Aberta Call-Center' => '',
						  'Aguardando Analise' => '',
						  'Aguardando Auditoria' => '',
						  'Aguardando Peças' => '',
						  'Aguardando Faturamento' => '',
						  'Aguardando Código de Rastreio' => '',
						  'Aguardando Remanufatura' => '',
						  'Aguardando Conserto' => '',
						  'Aguardando Retirada' => '',
						  'Finalizada' => '',
						);
	} else {
		$cabecalho = array (
						  'Aguardando Analise' => '',
						  'Em auditoria' => '',
						  'Aguard. Abastecimento Estoque' => '',
						  'Produto Trocado' => '',
						  'Aguardando Peças' => '',
						  'Em transito' => '',
						  'Aguardando Conserto' => '',
						  'Aguardando Retirada' => '',
						  'Finalizada' => '',
						);
	}

	if(isset($_POST['gerar_excel'])){

		$sql = "select tempo_timeline.data_abertura, tempo_timeline.data_fechamento,  tempo_timeline.os, 
tbl_status_checkpoint.descricao as nome_status_checkpoint, 
tempo_timeline.status_checkpoint, justify_hours(sum(intervalo)) as intervalo2
from tempo_timeline 
inner join tbl_status_checkpoint on tbl_status_checkpoint.status_checkpoint = tempo_timeline.status_checkpoint 
group by tempo_timeline.data_abertura, tempo_timeline.data_fechamento, tempo_timeline.os, tbl_status_checkpoint.status_checkpoint , tempo_timeline.status_checkpoint order by status_checkpoint ";

		$res_consulta = pg_query($con, $sql);

		for($i=0; $i<pg_num_rows($res_consulta); $i++){
			$os 				= pg_fetch_result($res_consulta, $i, os);

			$sqlSuaOs = "SELECT sua_os FROM tbl_os WHERE os = {$os}";
			$resSuaOs = pg_query($con, $sqlSuaOs);

			$sua_os 		    = pg_fetch_result($resSuaOs, 0, 'sua_os');

			$nome_status_checkpoint 	= pg_fetch_result($res_consulta, $i, nome_status_checkpoint);
			$status_checkpoint 	= pg_fetch_result($res_consulta, $i, status_checkpoint);
			$intervalo2  		= pg_fetch_result($res_consulta, $i, intervalo2);

			$intervalos = explode(".", $intervalo2);
			
			$intervalo2 = $intervalos[0];

			$intervalo2 		= str_replace('day', 'dia', $intervalo2);

			if($nome_status_checkpoint == 'Finalizada'){
				$data_abertura = pg_fetch_result($res_consulta, $i, data_abertura);
				$data_fechamento = pg_fetch_result($res_consulta, $i, data_fechamento);
				$data1 = new DateTime( "$data_fechamento 00:00:00" );
				$data2 = new DateTime( "$data_abertura 00:00:00" );

				$intervalo2 = $data1->diff( $data2 )->days." dia(s)";				
			}

			if($intervalo2 == "00:00:00"){
				$intervalo2 = "";
			}
			$dados[$sua_os][$nome_status_checkpoint] = $intervalo2;			
		}
		
		$file     = "xls/relatorio-timeline-{$login_fabrica}.csv";
        $fileTemp = "/tmp/relatorio-timeline-{$login_fabrica}.csv" ;
        $fp     = fopen($fileTemp,'w');

        $head = "OS;";
        foreach($cabecalho as $chave => $valor){
			$head .= $chave.";";
		}		
        
        fwrite($fp, $head);
        $tbody = "";

		foreach($dados as $os => $status){
			$tbody .= "\n\r";	
			$tbody .= "$os;";
			foreach($cabecalho as $key => $value){
				$tbody .= "$status[$key];";
			}
		}

//excell
	}

	$sql_grafico = "select  (SELECT count( distinct os )from tempo_timeline where status_checkpoint = tbl_status_checkpoint.status_checkpoint ) as qtde_os,  tbl_status_checkpoint.cor, tbl_status_checkpoint.descricao as nome_status_checkpoint, tempo_timeline.status_checkpoint,  justify_hours(sum(intervalo)), 
					justify_hours(sum(intervalo) / (SELECT count( distinct os )from tempo_timeline where status_checkpoint = tbl_status_checkpoint.status_checkpoint)) as media1, 
					EXTRACT(epoch FROM 
						justify_hours(sum(intervalo) / (SELECT count( distinct os )from tempo_timeline where status_checkpoint = tbl_status_checkpoint.status_checkpoint )))/3600  as media_horas  
					from tempo_timeline 
					inner join tbl_status_checkpoint on tbl_status_checkpoint.status_checkpoint = tempo_timeline.status_checkpoint 
					group by tbl_status_checkpoint.cor, tbl_status_checkpoint.status_checkpoint , tempo_timeline.status_checkpoint ";
	$res_grafico = pg_query($con, $sql_grafico);

	for($i=0; $i<pg_num_rows($res_grafico); $i++){
		$qtde_os 				= pg_fetch_result($res_grafico, $i, qtde_os);
		$nome_status_checkpoint 	= pg_fetch_result($res_grafico, $i, nome_status_checkpoint);
		$cor 				= pg_fetch_result($res_grafico, $i, cor);
		$media1 =  explode('.', pg_fetch_result($res_grafico, $i, media1));
		$media1 			= substr($media1[0],0,-3);
		$media2 			= substr(pg_fetch_result($res_grafico, $i, media_horas),0,5);

		$media2 = $media2 /24;

		if(empty($media2)){
			$media2 = 0; 
		}

		$media1 = str_replace('day', 'dia', $media1);

		$dadosTotal[$nome_status_checkpoint]['tempo'] = $media1;
		$dadosTotal[$nome_status_checkpoint]['media2'] = $media2;
		$dadosTotal[$nome_status_checkpoint]['qtde_os'] = $qtde_os;
		$dadosTotal[$nome_status_checkpoint]['cor'] = $cor;
	}

	foreach($cabecalho as $chave => $valor){
		$media1 = $dadosTotal["$chave"]['tempo'];
		$cor    = $dadosTotal["$chave"]['cor'];
		$media2 = $dadosTotal["$chave"]['media2']; 

		if(empty($media2)){
			$media2 = 0; 
		}
		$qtde_os = $dadosTotal["$chave"]['qtde_os'];
		
		if($chave != "Finalizada" ){
			$series = "{
					    name: '$chave $media1',
					    info: '$media1',
					    color: '$cor',
					    data: [
					      [ '<br><b> Quantidade de O.S</b> $qtde_os', $media2]
					    ]
					  }, ". $series;
		}		
	}	

	if(isset($_POST['gerar_excel'])){	
		unset($cabecalho['Finalizada']);
		$tbody .= "\r\n";
		$tbody .= "Média;";
		foreach($cabecalho as $key => $value){		
			$tbody .= $dadosTotal["$key"]['tempo'].";";
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
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE TIMELINE";
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
                text: 'Timeline'
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
							<input type="text" name="data_inicial_01" id="data_inicial" size="12" maxlength="10" class='span12' value= "<? echo $data_inicial?>">
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
							<input type="text" name="data_final_01" id="data_final" size="12" maxlength="10" class='span12' value="<? echo $data_final?>" >
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
				<label class='control-label' for='codigo_posto'>OS</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="os" name="os" class='span8' maxlength="12" value="<? echo $num_os ?>" >
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
