<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$TITULO = "Estatísticas - Telecontrol Hekp-Desk";


/*
|========================================
| Operações de Ajax
|
*/
$sql  = "SELECT array_to_string(array_agg(distinct status), ', ') as status
	FROM tbl_hd_chamado
	WHERE fabrica_responsavel = $login_fabrica
	AND status not in ('Resolvido','Cancelado','Suspenso','Novo','Parado')
	AND tipo_chamado NOTNULL
	AND tbl_hd_chamado.titulo <> 'Atendimento interativo'";
$res = pg_query($con,$sql);
$statuss = pg_fetch_result($res,0,'status');
$status = explode(',',$statuss);


$sql  = "SELECT array_to_string(array_agg(distinct tc.tipo_chamado), ', ') as tipo_chamado
	FROM tbl_hd_chamado
	JOIN tbl_tipo_chamado tc using(tipo_chamado)
	WHERE fabrica_responsavel = $login_fabrica
	AND status not in ('Resolvido','Cancelado','Suspenso','Novo','Parado')
	AND tbl_hd_chamado.titulo <> 'Atendimento interativo'";
$res = pg_query($con,$sql);
$tipos = pg_fetch_result($res,0,'tipo_chamado');
$tipo_chamado = explode(',',$tipos);

if ($_POST['btn_acao'] == "grafico_ano"){
	$sql = "SELECT hd_chamado, tipo_chamado, data,data_resolvido
		into temp tmp_chamados
		from tbl_hd_chamado
		where (to_char(data,'YYYY') = '".date('Y')."' or  to_char(data_resolvido,'YYYY') ='".date('Y')."')
		AND  status not in ('Cancelado')
		and fabrica_responsavel = $login_fabrica ;

		SELECT  count(1) as total,
			extract(month from data) as month
		FROM  tmp_chamados WHERE to_char(data,'YYYY') = '".date('Y')."'
		GROUP BY extract(month from data)
		ORDER BY month" ;
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0 ) {
		$grafico_x   = array();
		$total_hd    = array();
		$erro_aberto = array();
		$aberto = array();
		$resolvido   = array();
		$erro_resolvido = array();

		for($i=0;$i< pg_num_rows($res);$i++) {
			$total = pg_fetch_result($res,$i,'total');
			$month = pg_fetch_result($res,$i,'month');

			$grafico_x[] = "'$month'";
			$total_hd[] = $total;
			$sqle = "SELECT count(1) as erro 
				FROM tmp_chamados
				WHERE extract(month from data) = '$month'
				and to_char(data,'YYYY') = '".date('Y')."'
				AND tipo_chamado = 5";
			$rese = pg_query($con,$sqle);
			if(pg_num_rows($rese) > 0 ){
				$erro_aberto[] = pg_fetch_result($rese,0,0);
			}

			$sqle = "SELECT count(1)
				FROM tmp_chamados
				WHERE extract(month from data) = '$month'
				and to_char(data,'YYYY') = '".date('Y')."'
				AND tipo_chamado <> 5";
			$rese = pg_query($con,$sqle);
			if(pg_num_rows($rese) > 0 ){
				$aberto[] = pg_fetch_result($rese,0,0);
			}

			$sqle = "SELECT count(1) as erro 
				FROM tmp_chamados
				WHERE extract(month from data_resolvido) = '$month'
				and to_char(data_resolvido,'YYYY') = '".date('Y')."'
				AND tipo_chamado = 5";
			$rese = pg_query($con,$sqle);
			if(pg_num_rows($rese) > 0 ){
				$erro_resolvido[] = pg_fetch_result($rese,0,0);
			}

			$sqle = "SELECT count(1)
				FROM tmp_chamados
				WHERE extract(month from data_resolvido) = '$month'
				and to_char(data_resolvido,'YYYY') = '".date('Y')."'
				AND tipo_chamado <> 5";
			$rese = pg_query($con,$sqle);
			if(pg_num_rows($rese) > 0 ){
				$resolvido[] = pg_fetch_result($rese,0,0);
			}

		}

		$grafico_xs = implode(',', $grafico_x);
		$total_hds  = implode(',',$total_hd);
		$erro_abertos  = implode(',',$erro_aberto);
		$abertos  = implode(',',$aberto);
		$erro_resolvidos  = implode(',',$erro_resolvido);
		$resolvidos  = implode(',',$resolvido);

	}

}
if ($_POST['btn_acao'] == "grafico_mes"){
	$sql = "SELECT hd_chamado, tipo_chamado, data,data_resolvido
		into temp tmp_chamados
		from tbl_hd_chamado
		where (to_char(data,'MM/YYYY') = '".date('m/Y')."' or  to_char(data_resolvido,'MM/YYYY') ='".date('m/Y')."')
		AND  status not in ('Cancelado')
		and fabrica_responsavel = $login_fabrica ;

		SELECT  count(1) as total,
			min(to_char(data,'DD/MM')) as min,
			max(to_char(data,'DD/MM')) as max,
			extract(week from data) as week
		FROM  tmp_chamados WHERE to_char(data,'MM/YYYY') = '".date('m/Y')."'
		GROUP BY extract(week from data)
		ORDER BY week" ;
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0 ) {
		$grafico_x   = array();
		$total_hd    = array();
		$erro_aberto = array();
		$aberto = array();
		$resolvido   = array();
		$erro_resolvido = array();

		for($i=0;$i< pg_num_rows($res);$i++) {
			$total = pg_fetch_result($res,$i,'total');
			$min = pg_fetch_result($res,$i,'min');
			$max = pg_fetch_result($res,$i,'max');
			$week = pg_fetch_result($res,$i,'week');

			$grafico_x[] = "'$min - $max'";
			$total_hd[] = $total;
			$sqle = "SELECT count(1) as erro 
				FROM tmp_chamados
				WHERE extract(week from data) = '$week'
				and to_char(data,'MM/YYYY') = '".date('m/Y')."'
				AND tipo_chamado = 5";
			$rese = pg_query($con,$sqle);
			if(pg_num_rows($rese) > 0 ){
				$erro_aberto[] = pg_fetch_result($rese,0,0);
			}

			$sqle = "SELECT count(1)
				FROM tmp_chamados
				WHERE extract(week from data) = '$week'
				and to_char(data,'MM/YYYY') = '".date('m/Y')."'
				AND tipo_chamado <> 5";
			$rese = pg_query($con,$sqle);
			if(pg_num_rows($rese) > 0 ){
				$aberto[] = pg_fetch_result($rese,0,0);
			}

			$sqle = "SELECT count(1) as erro 
				FROM tmp_chamados
				WHERE extract(week from data_resolvido) = '$week'
				and to_char(data_resolvido,'MM/YYYY') = '".date('m/Y')."'
				AND tipo_chamado = 5";
			$rese = pg_query($con,$sqle);
			if(pg_num_rows($rese) > 0 ){
				$erro_resolvido[] = pg_fetch_result($rese,0,0);
			}

			$sqle = "SELECT count(1)
				FROM tmp_chamados
				WHERE extract(week from data_resolvido) = '$week'
				and to_char(data_resolvido,'MM/YYYY') = '".date('m/Y')."'
				AND tipo_chamado <> 5";
			$rese = pg_query($con,$sqle);
			if(pg_num_rows($rese) > 0 ){
				$resolvido[] = pg_fetch_result($rese,0,0);
			}

		}

		$grafico_xs = implode(',', $grafico_x);
		$total_hds  = implode(',',$total_hd);
		$erro_abertos  = implode(',',$erro_aberto);
		$abertos  = implode(',',$aberto);
		$erro_resolvidos  = implode(',',$erro_resolvido);
		$resolvidos  = implode(',',$resolvido);

	}

}
/*
|=======================================================
|
| Sql's que irão alimentar os gráficos e os dados na tela
|
*/

	/*
	|====================================
	|
	| Chamados novos
	|
	 */
	$data = array();
	$sql = "SELECT 	count(*) AS total
		FROM 	tbl_hd_chamado
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.titulo <> 'Atendimento interativo'
AND tipo_chamado NOTNULL
		AND status not in ('Resolvido','Cancelado','Suspenso','Novo','Parado')
					";

		if(!empty($atendente_busca))
		{

			$sql .= " AND atendente = '$atendente_busca'";

		}

		if(!empty($fabrica_busca))
		{

			$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";

		}

	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0) {
		$total = pg_fetch_result($res,0,'total');
	}
	$cont = 0 ;
	$th .= "<tr style='border:0px solid white'><td style='border:0px solid white'><br>Status Chamado <br></td></tr><tr class='titulo_coluna'>";
	$td .= "<tr style='background-color:#F7F5F0'>";
	foreach($status as $status_valor) {
		$status_valor = trim($status_valor);
		$sql_status = $sql ;
		$sql_status.=" and (status = '$status_valor' ) ";
		$res = pg_query($con,$sql_status);
		if(pg_num_rows($res) > 0) {
			$$status_valor = pg_fetch_result($res,0,'total');
			$valor = round( ($$status_valor / $total) * 100,2);

			if($cont%5 ==0 and $cont > 0 ) {
				$th .= "</tr>";

				$th .= $td . "</tr><tr class='titulo_coluna'>";
				$td = "<tr style='background-color:#F7F5F0'>";
			}
			$th .="<th>$status_valor</th>";

			$td .="<td>".$$status_valor." - $valor %</td>";
			$cont++;
			$data[] = "['".$status_valor."', $valor]";
		}
	}

	$th .= "</tr>";
	$td .= "</tr>";

	$sql = "SELECT 	count(*) AS total,tc.descricao
		FROM 	tbl_hd_chamado
		JOIN tbl_tipo_chamado tc USING(tipo_chamado)
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.titulo <> 'Atendimento interativo'
		AND status not in ('Resolvido','Cancelado','Suspenso','Novo','Parado')
					";

		if(!empty($atendente_busca))
		{

			$sql .= " AND atendente = '$atendente_busca'";

		}

		if(!empty($fabrica_busca))
		{

			$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";

		}


	$tth .= "<tr style='border:0px solid white'><td style='border:0px solid white'><br>Tipo Chamado <br></td></tr><tr class='titulo_coluna'>";
	$ttd .= "<tr style='background-color:#F7F5F0'>";
	foreach($tipo_chamado as $tc_valor) {
		$tc_valor = trim($tc_valor);
		$sql_status = $sql ;
		$sql_status.=" and (tipo_chamado = $tc_valor ) group by tc.descricao";
		$res = pg_query($con,$sql_status);
		if(pg_num_rows($res) > 0) {
			$$tc_valor = pg_fetch_result($res,0,'total');
			$descricao = pg_fetch_result($res,0,'descricao');
			$valor = round( ($$tc_valor / $total) * 100,2);

			if($contt%4 ==0 and $contt > 0 ) {
				$tth .= "</tr>";

				$tth .= $ttd . "</tr><tr class='titulo_coluna'>";
				$ttd = "<tr style='background-color:#F7F5F0'>";
			}
			$tth .="<th>$descricao</th>";

			$ttd .="<td>".$$tc_valor." - $valor %</td>";
			$contt++;
			$data_tc[] = "['".$descricao."', $valor]";
		}
	}

	$tth .= "</tr>";
	$ttd .= "</tr>";
	$nome = "Telecontrol";

include "menu.php";
?>

<style type="text/css">

	.formulario{
	    background-color:#D9E2EF;
	    font:11px Arial;
	    text-align:left;
	}

	table.tabela tr td{
	    font-family: verdana;
	    font-size: 11px;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}

	.titulo_tabela{
	    background-color:#596d9b;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}

	.titulo_coluna{
	    background-color:#596d9b;
	    font: bold 12px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}

	div#chart_container{
		width:100%;
		border:1px solid;
		padding:-50px;
		margin:auto;
	}

</style>

<script type="text/javascript" src="https://code.jquery.com/jquery-latest.min.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/data.js"></script>
<script src="https://code.highcharts.com/modules/drilldown.js"></script>


<form name='frm_graficos' action="<?php echo $PHP_SELF ?>" method="post" >

<table align="center" width="700px" cellpadding="0" cellspacing="0" border="0" class="formulario">
	<tr class='titulo_tabela'>
		<td>Estatísticas de chamados da <?php echo $nome ?></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>
			<table width="650px" align="center" cellspacing="1" cellpadding="1" class="tabela">
			 		<?=$th?>
			 		<?=$td?>
			 		<?=$tth?>
			 		<?=$ttd?>
				<tr><td style='border: 0px'><br></td></tr>
			 	<tr class="titulo_coluna">
			 		<td colspan="100%">
			 			<input type="hidden" name="btn_acao">
						<input type="button" value="Gráfico" name="gerar_grafico" id="btn_gera_grafico" onclick='javascript: if(frm_graficos.btn_acao.value == ""){ frm_graficos.btn_acao.value = "gera_grafico" ;frm_graficos.submit(); } else { alert("Aguarde Submissão");} ' >
						<input type="button" value="Gráfico Tipo Chamado" name="gerar_grafico_tc" id="btn_gera_grafico" onclick='javascript: if(frm_graficos.btn_acao.value == ""){ frm_graficos.btn_acao.value = "gera_grafico_tc" ;frm_graficos.submit(); } else { alert("Aguarde Submissão");} ' >
						<input type="button" value="Gráfico do mês <?=date('m/Y')?>" name="grafico_mes" id="btn_grafico_mes" onclick='javascript: if(frm_graficos.btn_acao.value == ""){ frm_graficos.btn_acao.value = "grafico_mes" ;frm_graficos.submit(); } else { alert("Aguarde Submissão");} ' >
						<input type="button" value="Gráfico do ano <?=date('Y')?>" name="grafico_ano" id="btn_grafico_ano" onclick='javascript: if(frm_graficos.btn_acao.value == ""){ frm_graficos.btn_acao.value = "grafico_ano" ;frm_graficos.submit(); } else { alert("Aguarde Submissão");} ' >


			 		</td>
			 	</tr>
			 </table>
		</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td align="center">
			<input type="button" name="btn_grafico_resolvidos_atendentes" id="btn_grafico_resolvidos_atendentes" value="Chamados resolvidos por Atendente" onclick='javascript: if(frm_graficos.btn_acao.value == ""){ frm_graficos.btn_acao.value = "gera_grafico_resolvidos_atendentes" ;frm_graficos.submit(); } else { alert("Aguarde Submissão");} '>
			<input type="button" name="btn_grafico_atendentes"            id="btn_grafico_atendentes" 			value="Chamados por Atendentes" onclick='javascript: if(frm_graficos.btn_acao.value == ""){ frm_graficos.btn_acao.value = "gera_grafico_atendentes" ;frm_graficos.submit(); } else { alert("Aguarde Submissão");} '>
			<button onclick='javascript: if(frm_graficos.btn_acao.value == ""){ frm_graficos.btn_acao.value = "chamados_abertos_30" ;frm_graficos.submit(); } else { alert("Aguarde Submissão");}' type='button'>Chamados abertos nos últimos 30 dias</button> 
		</td>
	</tr>
	<tr>
		<td></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
</table>
</form>
<br>
<div id="container" style="width: 700px; margin: 0 auto" ></div>
<?php
if ($_POST['btn_acao']){

	$btn_acao = $_POST['btn_acao'];

	if ($btn_acao == 'gera_grafico'){

		$chart_title = "Gráfico de chamados da fábrica: $nome";

		$graph_array = $data;
		$chart_data = implode(',', $graph_array);

	}

	if ($btn_acao == 'gera_grafico_tc'){

		$chart_title = "Gráfico de chamados da fábrica: $nome";

		$graph_array = $data_tc;
		$chart_data = implode(',', $graph_array);

	}
	if ($_POST['btn_acao'] == 'gera_grafico_resolvidos_atendentes'){

		$sql="
			SELECT count(*)  AS total_pessoal,
					nome_completo AS atendente
			FROM       tbl_hd_chamado
			JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
			WHERE      status ILIKE 'resolvido'
			AND        tbl_admin.ativo
			AND        tbl_admin.admin not in (24,435)
			AND        tbl_admin.grupo_admin notnull
			and tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			GROUP BY nome_completo
			ORDER BY total_pessoal DESC";


		$res_individual = pg_query($con,$sql);
		$total = 0;
		for ($i=0; $i < pg_num_rows($res_individual); $i++) {
			$total_pessoal = pg_result($res_individual,$i,'total_pessoal');
			$total += $total_pessoal;
		}

		for ($i=0; $i < pg_num_rows($res_individual); $i++) {
			$atendente = pg_result($res_individual,$i,'atendente');
			$total_pessoal = pg_result($res_individual,$i,'total_pessoal');

			$total_pessoal = round(($total_pessoal / $total)*100, 2);
			$graph_array[] = "['".$atendente."', $total_pessoal]";
		}

		$chart_data = implode(',', $graph_array);
		$chart_title = "Chamados Resolvidos por Atendente";

	}

	if ($_POST['btn_acao'] == 'gera_grafico_atendentes'){

		$sql="
			SELECT count(hd_chamado)  AS total_pessoal,
					nome_completo as atendente
			FROM       tbl_hd_chamado
			JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
			WHERE      tbl_admin.ativo
			AND        tbl_admin.admin not in (24,435)
			AND        tbl_admin.grupo_admin notnull
			and tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			GROUP BY nome_completo
			ORDER BY total_pessoal DESC";

		$res_individual = pg_query($con,$sql);

		$total = 0;
		for ($i=0; $i < pg_num_rows($res_individual); $i++) {
			$total_pessoal = pg_result($res_individual,$i,'total_pessoal');
			$total += $total_pessoal;
		}

		for ($i=0; $i < pg_num_rows($res_individual); $i++) {
			$atendente = pg_result($res_individual,$i,'atendente');
			$total_pessoal = pg_result($res_individual,$i,'total_pessoal');

			$total_pessoal = round(($total_pessoal / $total)*100, 2);
			$graph_array[] = "['".$atendente."', $total_pessoal]";
		}

		$chart_data = implode(',', $graph_array);
		$chart_title = "Chamados por Atendente";

	}

	if($btn_acao == "chamados_abertos_30") {
		$sql = "SELECT count(1) qtde, tbl_tipo_chamado.descricao, tbl_fabrica.nome
			INTO temp tmp_chamados_$login_admin
			FROM tbl_hd_chamado
			JOIN tbl_tipo_chamado USING(tipo_chamado)
			JOIN tbl_fabrica USING(fabrica)
			WHERE data between CURRENT_TIMESTAMP - interval '30 days' and CURRENT_TIMESTAMP group by tbl_tipo_chamado.descricao, tbl_fabrica.nome; 

		SELECT sum(qtde) total, nome 
			FROM tmp_chamados_$login_admin group by nome order by 1 desc limit 10 ; ";
		$res = pg_query($con,$sql);
		for ($i=0; $i < pg_num_rows($res); $i++) {
			$total = pg_result($res,$i,'total');
			$nome = pg_result($res,$i,'nome');

			$total_pessoal = round(($total_pessoal / $total)*100, 2);
			$graph_array[] = "{name: '".$nome."', y:$total, drilldown: '".$nome."'}";
			$sqle = "select qtde, descricao from tmp_chamados_$login_admin where nome = '$nome' order by 1 desc ";
			$rese = pg_query($con,$sqle);
				$graph_array2 = array();
			for($j=0;$j<pg_num_rows($rese);$j++) {
				$qtde = pg_fetch_result($rese, $j, 'qtde');
				$descricao = pg_fetch_result($rese, $j, 'descricao');
				$graph_array2[] =	"['".$descricao."',$qtde] "; 
			}
			$char_data_fabricas = implode(',',$graph_array2); 
			$graph_array3[] = "{
				name: '$nome',
				id: '$nome',
					data: [ $char_data_fabricas ]
				}		";

		}

		$chart_data_chamado = implode(',', $graph_array);
		$chart_data_fabrica = implode(',', $graph_array3);

	}

	if ($chart_data){
		echo $grafico = "<script>
			$(function () {
			    $('#container').highcharts({
				chart: {
							plotBackgroundColor: null,
							    plotBorderWidth: null,
							plotShadow: false
				},
				title: {
					text: '$chart_title'
				},
				tooltip: {
					formatter: function() {
						return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.y, 2, '.') +' %';
					}
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true,
							color: '#000000',
							connectorColor: '#000000',
							formatter: function() {
								return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.y, 2, '.') +' %';
							}
						}
					}
				},
				series: [{
					type: 'pie',
					name: 'Browser share',
					data: [
						$chart_data
					]
				}]
			});
		});

					</script>";
	}


	if(!empty($total_hd) ){
		echo "<script>$(function () {
			        $('#container').highcharts({
					title: {
						text: 'Chamados do mês ".date('m/Y')."',
						x: -20 //center
					},
					xAxis: {
						categories:[$grafico_xs]
					},
				        yAxis: {
						title: {
							text: 'Qtde de chamados'
						},
						plotLines: [{
							value: 0,
							width: 1,
							color: '#808080'
						}]
					},
					legend: {
						layout: 'vertical',
						align: 'right',
						verticalAlign: 'middle',
						borderWidth: 0
					},
					series: [{
							name: 'Total Abertos',
							data: [$total_hds]
						}, {
							name: 'Erro Abertos',
							data: [$erro_abertos]
						}, {
							name: 'Normal Abertos',
							data: [$abertos]
						}, {
							name: 'Erro resolvidos',
							data: [$erro_resolvidos]
						}, {
							name: 'Normal resolvidos',
							data: [$resolvidos]
						}



					]
															});
			    }); </script>";

	}

	echo "<script>Highcharts.chart('container', {
	chart: {
	type: 'column'
	},
		title: {
		text: 'Chamados abertos nos últimos 30 dias'
	},
		subtitle: {
		text: 'Clique na coluna para ver com mais detalhes'
	},
		xAxis: {
		type: 'category'
	},
		yAxis: {
		title: {
		text: 'Total por fábricas'
	}

	},
		legend: {
		enabled: false
	},
	plotOptions: {
	series: {
	borderWidth: 0,
		dataLabels: {
		enabled: true,
			format: '{point.y}'
	}
	}
	},

	tooltip: {
	headerFormat: '<span style=\"font-size:11px\">{series.name}</span><br>',
		pointFormat: '<span style=\"color:{point.color}\">{point.name}</span>: <b>{point.y}</b> Chamados<br/>'
	},

		series: [{
		name: 'Fábricas',
			colorByPoint: true,
			data: [$chart_data_chamado]
	}],
		drilldown: {
		series: [$chart_data_fabrica]
	}
});
</script>";	
}


?>
