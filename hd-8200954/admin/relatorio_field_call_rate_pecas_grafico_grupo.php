<script type="text/javascript" src="https://code.jquery.com/jquery-latest.min.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/data.js"></script>
<script src="https://code.highcharts.com/modules/drilldown.js"></script>

<?
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,auditoria";
include_once 'autentica_admin.php';
$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
//////////////////////////////////////////

if (1 == 1) {
	
	// nome da imagem
	$img = time();
	$image_graph = "png/3_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	
if($tipo=="produto"){
$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca, pecas.qtde AS ocorrencia
		FROM tbl_peca
		JOIN   (SELECT tbl_os_item.peca, COUNT(*) AS qtde
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN   (SELECT tbl_os.os , 
						      (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra USING (extrato)
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   tbl_os.posto = tbl_extrato.posto
						AND   tbl_os.fabrica = $login_fabrica
						AND   tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
						AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5
				) fcr ON tbl_os_produto.os = fcr.os
				join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)  and tbl_servico_realizado.troca_de_peca is true 
				GROUP BY tbl_os_item.peca
		) pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;
}else{
$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca , count (tbl_peca.peca) as ocorrencia
		FROM  (
				SELECT tbl_os.os ,
				CASE WHEN(
					SELECT tbl_servico_realizado.troca_de_peca
					FROM tbl_os_produto
					JOIN tbl_os_item using(os_produto)
					JOIN tbl_servico_realizado using(servico_realizado)
					WHERE tbl_os_produto.os = tbl_os.os limit 1 ) IS TRUE
				THEN 'com' ELSE 'sem' END AS com_sem ,
				( 	SELECT status_os
					FROM tbl_os_status
					WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os_status.os = tbl_os_extra.os
					ORDER BY data DESC LIMIT 1
				) AS status
				FROM tbl_os
				JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os AND tbl_os_extra.i_fabrica=$login_fabrica
				JOIN tbl_extrato  on tbl_extrato.extrato = tbl_os_extra.extrato
				JOIN tbl_produto  on tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica
				JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
				JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
				AND $cond_5
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_produto.referencia_fabrica = '$produto'
				AND tbl_extrato.fabrica = $login_fabrica
		) as fcr 
		JOIN tbl_os_produto ON fcr.os = tbl_os_produto.os
		JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
		JOIN tbl_peca       ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica=$login_fabrica
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL) and fcr.com_sem='com'
		GROUP BY tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca
		ORDER BY ocorrencia DESC" ;

}



//echo nl2br($sql);  echo "<BR>====<BR>";
if($tipo=="produto"){
$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca, pecas.qtde AS ocorrencia
		FROM tbl_peca
		JOIN   (SELECT tbl_os_item.peca, COUNT(*) AS qtde
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN   (SELECT tbl_os.os 
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra USING (extrato)
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
						AND   tbl_os.posto = tbl_extrato.posto
						AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5
						$cond_conversor
				) fcr ON tbl_os_produto.os = fcr.os
				join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				WHERE tbl_servico_realizado.troca_de_peca is true 
				GROUP BY tbl_os_item.peca
		) pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;
}else{
$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca , count (tbl_peca.peca) as ocorrencia
		FROM  (
				SELECT tbl_os.os ,
				CASE WHEN(
					SELECT tbl_servico_realizado.troca_de_peca
					FROM tbl_os_produto
					JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
					JOIN tbl_servico_realizado using(servico_realizado)
					WHERE tbl_os_produto.os = tbl_os.os limit 1 ) IS TRUE
				THEN 'com' ELSE 'sem' END AS com_sem 
				FROM tbl_os
				JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os AND tbl_os_extra.i_fabrica=$login_fabrica
				JOIN tbl_extrato  on tbl_extrato.extrato = tbl_os_extra.extrato
				JOIN tbl_produto  on tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica
				JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
				JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
				AND $cond_5
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_produto.referencia_fabrica = '$produto'
				AND tbl_extrato.fabrica = $login_fabrica
				$cond_conversor
		) as fcr 
		JOIN tbl_os_produto ON fcr.os = tbl_os_produto.os
		JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
		JOIN tbl_peca       ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica=$login_fabrica
		WHERE fcr.com_sem='com'
		GROUP BY tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca
		ORDER BY ocorrencia DESC" ;

}
//echo nl2br($sql);
//exit;

	$yres = pg_query($con,$sql);
	$fim          = pg_num_rows($yres) -1;
	for ($x = 0; $x < pg_num_rows($yres); $x++) {
			$total = $total + pg_fetch_result($yres,$x,ocorrencia);
		}
	$n_ocorrencia_anterior =0;
	$graph_array = array();
	for ($x = 0; $x < pg_num_rows($yres); $x++) {

		$y = pg_fetch_result($yres,$x,ocorrencia);
		$p_ocorrencia = ( $y/ $total ) * 100;

		if ($x==0) {
			$ocorrencia = pg_fetch_result($yres,$x,ocorrencia);
			
			$descricao  = substr(pg_fetch_result($yres,$x,descricao), 0, 35);
			$porc_ocorrencia = $p_ocorrencia;
			$graph_array[] = "['".$descricao."', $p_ocorrencia]";
		}
		elseif ($x>=9){
			$fim          = pg_num_rows($yres) -1;
			$n_ocorrencia = pg_fetch_result($yres,$x,ocorrencia);

			$n_ocorrencia = $n_ocorrencia + $n_ocorrencia_anterior;
//echo "$x Atual: $n_ocorrencia - Anterior: $n_ocorrencia_anterior <br>";
			$n_ocorrencia_anterior = $n_ocorrencia;
			
			
			if($x ==$fim){
				$p_ocorrencia = ( $n_ocorrencia/ $total ) * 100;
				$porc_ocorrencia = $porc_ocorrencia .','.$p_ocorrencia;
				$descricao       = $descricao.', Outros';
//echo "<br><br>Total".$n_ocorrencia;

				$graph_array[] = "['Outros', $p_ocorrencia]";
			}
		}
		else {

			$n_descricao  = substr(pg_fetch_result($yres,$x,descricao), 0, 35);
			$n_descricao  = str_replace(",","",$n_descricao); 
//echo "<BR>$x =>desc: $n_descricao";
			$ocorrencia   = $ocorrencia.','.$n_ocorrencia;

			$descricao  = $descricao.','.$n_descricao;

			$porc_ocorrencia = $porc_ocorrencia .','.$p_ocorrencia;
//echo "<BR>$x descricao: $descricao - ocorrencia: $ocorrencia <br>";
			$graph_array[] = "['".$n_descricao."', $p_ocorrencia]";
		}
	}
	$chart_data = implode(',', $graph_array);
//if ($ip="200.158.65.19") {echo $ocorrencia;} 
	if ($total > 0){
		echo $grafico = "<script>
			$(function () {
			    $('#container').highcharts({
				chart: {
							plotBackgroundColor: null,
							    plotBorderWidth: null,
							plotShadow: false
				},
				title: {
					text: 'Relatório de Field Call Rate'
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

echo '<div id="container" style="width: 700px; margin: 0 auto" ></div>';
	}

//////////////////////////////////////////
}
?>
