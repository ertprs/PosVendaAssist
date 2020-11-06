<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,auditoria";
include 'autentica_admin.php';
$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
//////////////////////////////////////////
if (1 == 1) {

	
	// nome da imagem
	$img = time();
	$image_graph = "/tmp/3_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca, pecas.qtde AS ocorrencia
		FROM tbl_peca
		JOIN   (SELECT tbl_os_item.peca, COUNT(*) AS qtde
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN   (SELECT tbl_os.os , 
						      (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= "AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   tbl_posto.pais = '$login_pais'
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
				) fcr ON tbl_os_produto.os = fcr.os
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				GROUP BY tbl_os_item.peca
		) pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;
	$res = pg_exec ($con,$sql);

	
	$res = pg_exec($con,$sql);
	for ($x = 0; $x < pg_numrows($res); $x++) {
			$total = $total + pg_result($res,$x,ocorrencia);
		}
	$n_ocorrencia_anterior =0;
	for ($x = 0; $x < pg_numrows($res); $x++) {

		$y = pg_result($res,$x,ocorrencia);
		$p_ocorrencia = ( $y/ $total ) * 100;

		if ($x==0) {
			$ocorrencia = pg_result($res,$x,ocorrencia);

			$peca = pg_result($res,$x,peca);
			$descricao  = substr(pg_result($res,$x,descricao), 0, 35);

			$sql_idioma = "SELECT * FROM tbl_peca_idioma
				WHERE peca = $peca
				AND upper(idioma)     = 'ES'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			$descricao  = substr(($descricao), 0, 35);
			$porc_ocorrencia = $p_ocorrencia;


		}elseif ($x>=9){
			$fim          = pg_numrows($res) -1;
			$n_ocorrencia = pg_result($res,$x,ocorrencia);

			$n_ocorrencia = $n_ocorrencia + $n_ocorrencia_anterior;
			//echo "Atual: $n_ocorrencia - Anterior: $n_ocorrencia_anterior <br>";
			$n_ocorrencia_anterior = $n_ocorrencia;


			if($x ==$fim){
				$p_ocorrencia = ( $n_ocorrencia/ $total ) * 100;
				$porc_ocorrencia = $porc_ocorrencia .','.$p_ocorrencia;
				$descricao       = $descricao.', Otros';
				//echo "<br><br>Total".$n_ocorrencia;
			}
		}else {
			$peca        = pg_result($res,$x,peca);
			$descricao_i = pg_result($res,$x,descricao);

			$sql_idioma = "SELECT * FROM tbl_peca_idioma
				WHERE peca = $peca
				AND upper(idioma)     = 'ES'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao_i  = trim(@pg_result($res_idioma,0,descricao));
			}
			$n_descricao  = substr(($descricao_i), 0, 35);

			$ocorrencia   = $ocorrencia.','.$n_ocorrencia;

			$descricao  = $descricao.','.$n_descricao;

			$porc_ocorrencia = $porc_ocorrencia .','.$p_ocorrencia;

		}
	}

	$descricao = explode(',',$descricao); 
	$ocorrencia = explode(',',$porc_ocorrencia); 
	foreach($descricao as $chave => $valor) {
		$dados_chart[] = "['$valor',". $ocorrencia[$chave] . "]";
	}	

}

?>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);
function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'Peça');
	data.addColumn('number', 'Ocorrência');
	data.addRows([
		<?echo implode(",",$dados_chart) ; ?>
	]);

	var options = {'title':'Reporte de Field Call Rate',
		'width':700,
		'height':500};

	// Instantiate and draw our chart, passing in some options.
	var chart = new google.visualization.PieChart(document.getElementById('grafico1'));
	chart.draw(data, options);
 }
</script>
<center>
<div class="container">
    <div class='row'>
        <div id="grafico1"></div>
    </div>
</div>  
</center>  
