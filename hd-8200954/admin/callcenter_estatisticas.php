<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = $_POST['btn_acao'];

$meses_anos_grafico = array();
$fale_qtde_grafico = array();
$atendimento_qtde_grafico = array();

if(strlen($btn_acao)>0){

	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$natureza_chamado   = $_POST['natureza_chamado'];
	$status             = $_POST['status'];
	$atendente          = $_POST['atendente'];


	$cond_1 = " 1 = 1 ";
	$cond_2 = " AND 1 = 1 ";
	$cond_3 = " AND 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	$cond_5 = " 1 = 1 ";

	if(in_array($login_fabrica, array(169,170))){
		$origem = $_POST['origem'];
	}

	if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi))
            $msg_erro = "Data Inválida";
    }
    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf))
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $xdata_inicial = "$yi-$mi-$di";
        $xdata_final = "$yf-$mf-$df";
        if(strtotime($xdata_final) < strtotime($xdata_inicial)){
            $msg_erro = "Data Inválida.";
        }
    }

}

$layout_menu = "callcenter";
$title = "ESTATISTICAS DE PERÍODO DE ATENDIMENTO";

include "cabecalho.php";
?>
<style>
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
.espaco{
	padding-left:80px;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>



<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,atendente){
	if (typeof atendente == 'undefined') {
		atendente = '';
	}
	janela = window.open("callcenter_relatorio_periodo_atendimento_callcenter_ebano.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&atendente="+atendente, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}

/* POP-UP IMPRIMIR */
function abrir(URL) {
	var width = 700;
	var height = 600;
	var left = 90;
	var top = 90;

	window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
}
</script>
<script type="text/javascript" src="js/jquery_1.js"></script>
<script type="text/javascript" src="js/grafico/highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>
<script language='javascript' src='../ajax.js'></script>
<? include "javascript_calendario.php";?>

<script type="text/javascript" charset="utf-8">

	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

</script>


<? include "javascript_pesquisas.php" ?>

<?php if(strlen($msg_erro)>0){?>
	<div align="center">
		<div style="width:700px" class="msg_erro"><?php echo $msg_erro;?></div>
	</div>
<?php }?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
	<table align="center" class="formulario" width="700" border="0">
		<tr>
			<td class="titulo_tabela" colspan="5">Parâmetros de Pesquisa</td>
		</tr>
		<tr>
			<td width="10" class="espaco">&nbsp;</td>
			<td align="left">
				Data Inicial
				<br>
				<input type="text" class="frm" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<?echo (strlen($data_inicial) > 0) ? $data_inicial : null;?>">
			</td>
			<td align="left">
				Data Final
				<br>
				<input type="text" class="frm" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? echo (strlen($data_final) > 0) ? $data_final : null;?>">
			</td>
			<td align="left">
				Status<br>
				<select name="status" size="1" class="frm">
					<option value=""></option>

					<?php
						$sql = " SELECT status FROM tbl_hd_status where fabrica=$login_fabrica ";
						$res = pg_query($con,$sql);

						for ($i = 0; $i < pg_num_rows($res);$i++){

							$status_hd = pg_result($res,$i,0);

							$selected_status = ($status_hd == $status_interacao) ? "SELECTED" : null;
					?>
							<option value="<?=$status_hd?>" <?echo $selected_status?> ><?echo $status_hd?></option>
					<?
						}
					?>
				</select>
			</td>
		</tr>
		<?php if(in_array($login_fabrica, array(169,170))){ ?>
		<tr>
			<td width="10" class="espaco">&nbsp;</td>
			<td align="left">
				Origem<br>

				<select name="origem" size="1" class="frm">
					<option value=""></option>
					<?php
						$sql = "SELECT hd_chamado_origem, descricao
									FROM tbl_hd_chamado_origem
									WHERE fabrica = $login_fabrica
									AND ativo IS TRUE
									ORDER BY descricao";
						$res = pg_query($con,$sql);
						$xorigem = pg_fetch_all($res);
						for ($i = 0; $i < pg_num_rows($res);$i++){

							$id_origem = pg_fetch_result($res,$i,'hd_chamado_origem');
							$desc_origem = pg_fetch_result($res, $i, 'descricao');

							$selected_origem = ($id_origem == $origem) ? "SELECTED" : null;
					?>
							<option value="<?=$id_origem?>" <?echo $selected_origem?> ><?echo $desc_origem?></option>
					<?
						}
					?>
				</select>
			</td>
		</tr>
		<?php } ?>
		<tr>
			<td colspan="5">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5" align="center">
				<input type='submit' style="cursor:pointer" name='btn_acao' value='Pesquisar'>
			</td>
		</tr>
		<tr>
			<td colspan="5">&nbsp;</td>
		</tr>
	</table>
</form>
<?

if(strlen($btn_acao)>0 && strlen($msg_erro)==0){

	$tem_resultado = false;

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		}
	}

	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}

	if(strlen($status)>0){
		$cond_3 = " AND tbl_hd_chamado.status = '$status'  ";
	}

	if(in_array($login_fabrica, array(169,170)) AND strlen(trim($origem)) > 0){
		$join_origem = "JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = $login_fabrica";
		$cond_6 = " AND tbl_hd_chamado_extra.hd_chamado_origem = $origem ";
	}else{
		$join_origem = "";
		$cond_6 = "";
	}

	if($login_fabrica==6){
		$cond_4 = " tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if(strlen($atendente)>0){
		$cond_5 = " tbl_hd_chamado.atendente = '$atendente'  ";
	}

	if($login_fabrica==2){
		$condicoes = $produto . ";" . $natureza_chamado . ";" . $status . ";" . $posto . ";" . $xdata_inicial . ";" .$xdata_final;
	}

	$sql = "SELECT
				count(CASE WHEN
							tbl_hd_chamado.admin = 2473 then 'fale' ELSE 'atendimento'
					  END) AS qtde,
				CASE WHEN
						tbl_hd_chamado.admin = 2473 then 'fale' ELSE 'atendimento'
				END AS tipo
			INTO TEMP tmp_hd_chamado_estatisticas_$login_admin
			FROM tbl_hd_chamado
			$join_origem
			WHERE fabrica = $login_fabrica and data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
			$cond_3
			$cond_6
		GROUP BY CASE WHEN tbl_hd_chamado.admin = 2473 then 'fale' ELSE 'atendimento' END;

		SELECT
		tmp_hd_chamado_estatisticas_$login_admin.tipo,
		tmp_hd_chamado_estatisticas_$login_admin.qtde,
		CASE WHEN tmp_hd_chamado_estatisticas_$login_admin.tipo = 'atendimento' then
			(SELECT qtde FROM tmp_hd_chamado_estatisticas_$login_admin WHERE tipo = 'atendimento')/(SELECT SUM(qtde) FROM tmp_hd_chamado_estatisticas_$login_admin)*100
		ELSE
			(SELECT qtde FROM tmp_hd_chamado_estatisticas_$login_admin WHERE tipo = 'fale')/(SELECT SUM(qtde) FROM tmp_hd_chamado_estatisticas_$login_admin)*100
		END AS PORC,
		(SELECT SUM(qtde) FROM tmp_hd_chamado_estatisticas_$login_admin) AS total
		FROM tmp_hd_chamado_estatisticas_$login_admin;
		";

	//if($ip == '200.228.76.102') echo $sql;

	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){

		$tem_resultado = true;

		echo '<br>';
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo '<tr class="titulo_coluna">'."\n";
		echo "<td colspan='3'>Visão Geral</TD>\n";
		echo "</TR>\n";

		for ($i=0;$i<pg_num_rows($res);$i++) {
			$tipo  = pg_result($res,$i,tipo);
			$qtde  = pg_result($res,$i,qtde);
			$porc  = pg_result($res,$i,porc);
			$total = pg_result($res,$i,total);

			if($tipo=='fale')        $total_fale = $qtde;
			if($tipo=='atendimento') $total_aten = $qtde;
			$xtotal = $total;

			echo "<tr bgcolor='#F7F5F0'>";
			echo "<td align='left'>";
			if($tipo=='fale')        echo "Fale Conosco";
			if($tipo=='atendimento') echo "Chamado";
			echo "</td>";
				echo "<td>";
				echo $qtde;
				echo "</td>";
				$porc1 = number_format($porc,'2','.','.');
				echo "<td>$porc1 %</td>";
			echo "</tr>";
		}

		echo "<tr class='titulo_coluna'>";
		echo "<td align='left'>Total</td>";
		echo "<td>$total</td>";
		echo "<td>100%</td>";
		echo "</tr>";
		echo "</table>";
		echo "<BR><BR>";
	}

	//HD 272763 inicio
	$sql_mes_grafico = "
		SELECT distinct extract( month from data) as mes_data,
		 extract( year  from data) as ano_data
		FROM tbl_hd_chamado
		$join_origem
		WHERE fabrica = $login_fabrica
		$cond_3
		$cond_6
		and data between '$xdata_inicial 00:00:00'
		and '$xdata_final 23:59:59' order by ano_data, mes_data ;
	";
	$res_mes_grafico = pg_query($con,$sql_mes_grafico);

	for ($q=0; $q < pg_num_rows($res_mes_grafico); $q++) {
		$mes_grafico = pg_result($res_mes_grafico,$q,0);
		$ano_grafico = pg_result($res_mes_grafico,$q,1);
		switch ($mes_grafico) {
			case '1':
				$meses_grafico[] = "'Jan - $ano_grafico'";
				break;
			case '2':
				$meses_grafico[] = "'Fev - $ano_grafico'";
				break;
			case '3':
				$meses_grafico[] = "'Mar - $ano_grafico'";
				break;
			case '4':
				$meses_grafico[] = "'Abr - $ano_grafico'";
				break;
			case '5':
				$meses_grafico[] = "'Mai - $ano_grafico'";
				break;
			case '6':
				$meses_grafico[] = "'Jun - $ano_grafico'";
				break;
			case '7':
				$meses_grafico[] = "'Jul - $ano_grafico'";
				break;
			case '8':
				$meses_grafico[] = "'Ago - $ano_grafico'";
				break;
			case '9':
				$meses_grafico[] = "'Set - $ano_grafico'";
				break;
			case '10':
				$meses_grafico[] = "'Out - $ano_grafico'";
				break;
			case '11':
				$meses_grafico[] = "'Nov - $ano_grafico'";
				break;
			case '12':
				$meses_grafico[] = "'Dez - $ano_grafico'";
				break;
		}
	}

	$meses_ano_grafico = implode(',', $meses_grafico);

	if(!in_array($login_fabrica, array(169,170))){
		$sql_grafico = "SELECT extract( month from data) as mes_data,
								extract( year  from data) as ano_data,

						count(CASE WHEN
						tbl_hd_chamado.admin = 2473 then 'fale' ELSE 'atendimento'
						END) AS qtde,

						CASE WHEN
						tbl_hd_chamado.admin = 2473 then 'fale' ELSE 'atendimento'
						END AS tipo

						from tbl_hd_chamado
						$join_origem
						WHERE fabrica = $login_fabrica
						and data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'

						$cond_3
						$cond_6
						group by
						CASE WHEN tbl_hd_chamado.admin = 2473 then 'fale' ELSE 'atendimento' END,ano_data, mes_data

						order by ano_data,mes_data ;";
		$res_grafico = pg_query($con,$sql_grafico);

		for ($z=0; $z < pg_num_rows($res_grafico); $z++) {
			$tipo_grafico = pg_result($res_grafico,$z,'tipo');
			switch ($tipo_grafico) {
				case 'fale':
					$fale_qtde_grafico[] = pg_result($res_grafico,$z,'qtde');
					break;

				case 'atendimento':
					$atendimento_qtde_grafico[] = pg_result($res_grafico,$z,'qtde');
					break;
			}
		}

		$fale_qtde_grafico = implode(',', $fale_qtde_grafico);
		$atendimento_qtde_grafico = implode(',', $atendimento_qtde_grafico);

		$grafico = "<script>
						var chart;
						$(document).ready(function() {
						   chart = new Highcharts.Chart({
						      chart: {
						         renderTo: 'container',
						         defaultSeriesType: 'column'
						      },
						      title: {
						         text: 'Visão Geral'
						      },
						      xAxis: {
						         categories: [
						           $meses_ano_grafico
						         ],
						         title: {
						            text: 'Meses'
						         }
						      },
						      yAxis: {
						         min: 0,
						         title: {
						            text: 'Qtde'
						         }
						      },
						      legend: {
						         layout: 'vertical',
						         backgroundColor: '#FFFFFF',
						         align: 'left',
						         verticalAlign: 'top',
						         x: 100,
						         y: 70,
						         floating: true,
						         shadow: true
						      },
						      tooltip: {
						         formatter: function() {
						            return ''+
						               this.series.name +': '+ this.y ;
						         }
						      },
						      plotOptions: {
						         column: {
						            pointPadding: 0.2,
						            borderWidth: 0
						         }
						      },
						      series: [{
						         name: 'Chamado',
						         data: [$atendimento_qtde_grafico]

						      }, {
						         name: 'Fale Conosco',
						         data: [$fale_qtde_grafico]

						      }]
						   });


						});

					</script>";
		echo $grafico;
	}
	if(in_array($login_fabrica, array(169,170))){
		$sql_grafico2 = "SELECT extract( month from data) as mes_data,
							extract( year  from data) as ano_data,
					count(tbl_hd_chamado.hd_chamado) AS qtde,
					tbl_hd_chamado_origem.descricao
					from tbl_hd_chamado
					JOIN tbl_hd_chamado_extra USING (hd_chamado)
					JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_chamado_extra.hd_chamado_origem
						AND tbl_hd_chamado_origem.fabrica = $login_fabrica
					WHERE tbl_hd_chamado.fabrica = $login_fabrica
					and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
					$cond_3
					$cond_6
					group by
					ano_data, mes_data, tbl_hd_chamado_origem.descricao
					order by ano_data,mes_data ;";
		$res2 = pg_query($con,$sql_grafico2);

		$meses = array_map(function($r){
			return $r["mes_data"].'-'.$r["ano_data"];
		},pg_fetch_all($res2));

		$meses_unique = array_unique($meses);
		$dados = array();

		$meses = array();
		foreach ($meses_unique as $key => $value) {
			$meses[] = $value;
		}

		while ($row = pg_fetch_object($res2)) {
			if (!isset($grafico_valores[$row->descricao]["data"])) {
		        $grafico_valores[$row->descricao]["data"] = array_fill(0, count($meses), 0);
	    	}

	    	$chave = array_search($row->mes_data.'-'.$row->ano_data, $meses);
	    	$grafico_valores[$row->descricao]["name"] = $row->descricao;
			$grafico_valores[$row->descricao]["data"][$chave] += (int) $row->qtde;
		}

		$grafico_valores_series = array();
		foreach ($grafico_valores as $key => $value) {
		    $grafico_valores_series[] = $value;
		}
		$grafico_valores = json_encode($grafico_valores_series);

		$grafico = "<script>
					var chart;
					$(document).ready(function() {
					   chart = new Highcharts.Chart({
					      chart: {
					         renderTo: 'container',
					         defaultSeriesType: 'column'
					      },
					      title: {
					         text: 'ESTATISTICAS DE PERÍODO DE ATENDIMENTO'
					      },
					      xAxis: {
					         categories: [
					           $meses_ano_grafico
					         ],
					         title: {
					            text: 'Meses'
					         }
					      },
					      yAxis: {
					         min: 0,
					         title: {
					            text: 'Qtde'
					         }
					      },
					      legend: {
					         layout: 'vertical',
					         backgroundColor: '#FFFFFF',
					         align: 'left',
					         verticalAlign: 'top',
					         x: 100,
					         y: 70,
					         floating: true,
					         shadow: true
					      },
					      tooltip: {
					         formatter: function() {
					            return ''+
					               this.series.name +': '+ this.y ;
					         }
					      },
					      plotOptions: {
					         column: {
					            pointPadding: 0.2,
					            borderWidth: 0
					         }
					      },
				   		series: $grafico_valores
					   });
					});
				</script>";
				echo $grafico;
	}
	?>
	<div id="container" style="width: 700px; height: 400px; margin: 0 auto"></div>
	<?

	//HD 272763 FIM

	######################PARTE 2##################################

	$sql = "SELECT
				count(*),tbl_admin.login
			FROM tbl_hd_chamado
			$join_origem
			JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
			WHERE tbl_hd_chamado.fabrica = $login_fabrica and data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
			$cond_3
			$cond_6
			group by tbl_admin.login;
			";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){

		$tem_resultado = true;

		echo '<br>';
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo '<tr class="titulo_coluna">'."\n";
		echo "<td colspan='3'>Estatísticas da Visão Geral</u></TD>\n";
		echo "</tr>\n";

		$total = '';
		for ($i=0;$i<pg_num_rows($res);$i++) {
			$count       = pg_result($res,$i,0);
			$login      = pg_result($res,$i,1);

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			echo "
			<tr bgcolor='$cor'>
			<td align='left'>$login</td>
			<td>$count</td>";
			$porc2 = ($count/$xtotal)*100;
			$porc2 = number_format($porc2,'2','.','.');
			echo "<td>$porc2 %</td>";
			echo "<tr>";
			echo "</tr>";
		}
		echo '<tr class="titulo_coluna">';
		echo "<td align='left'>Total</td>";
		echo "<td>$xtotal</td>";
		echo "<td>100%</td>";
		echo "</tr>";

		echo "</table>";

		echo "<BR><BR>";

	}

	######################PARTE 3##################################
	$sql = "SELECT
				count(*),tbl_admin.login
			FROM tbl_hd_chamado
			$join_origem
			JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
			WHERE tbl_hd_chamado.fabrica = $login_fabrica and data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'  and tbl_hd_chamado.admin = 2473
			$cond_3
			$cond_6
			group by tbl_admin.login";

	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$tem_resultado = true;
		echo '<br>';
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<tr class='titulo_coluna'>\n";
		echo "<td colspan=3>Estatísticas do Fale Conosco</u></TD>\n";
		echo "</TR >\n";

		for ($i=0;$i<pg_num_rows($res);$i++) {
			$count = pg_result($res,$i,0);
			$login = pg_result($res,$i,1);

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>
			<td align='left'>$login</td>
			<td>$count</td>";
			$porc2 = ($count/$total_fale)*100;
			$porc2 = number_format($porc2,'2','.','.');
			echo "<td>$porc2 %</td>";
			echo "</tr>";
		}

		echo "<tr class='titulo_coluna'>";
		echo "<td align='left'>Total</td>";
		echo "<td>$total_fale</td>";
		echo "<td>100%</td>";
		echo "</tr>";


		echo "</table>";
		echo "<BR><BR>";

	}

	######################PARTE 4##################################
	$sql = "SELECT
				count(*),tbl_admin.login
			FROM tbl_hd_chamado
			$join_origem
			JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
			WHERE tbl_hd_chamado.fabrica = $login_fabrica and data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'  and tbl_hd_chamado.admin <> 2473
			$cond_3
			$cond_6
			group by tbl_admin.login";
	//	echo $sql;
	//		if($ip == '200.228.76.102') echo $sql;

	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){

		$tem_resultado = true;

		echo '<br>';
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<tr class='titulo_coluna'>\n";
		echo "<td colspan=3>Estatísticas do Chamado</u></TD>\n";
		echo "</TR >\n";

		for ($i=0;$i<pg_num_rows($res);$i++) {
			$count = pg_result($res,$i,0);
			$login = pg_result($res,$i,1);

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>
			<td align='left'>$login</td>
			<td>$count</td>";
			$porc2 = ($count/$total_aten)*100;
			$porc2 = number_format($porc2,'2','.','.');
			echo "<td>$porc2 %</td>";

			echo "</tr>";
		}

		echo "<tr class='titulo_coluna'>";
		echo "<td align='left'>Total</td>";
		echo "<td>$total_aten</td>";
		echo "<td>100%</td>";
		echo "</tr>";

		echo "</table>";

		echo "<BR><BR>";

	}

	if($tem_resultado == false){
		echo '<br>';
		echo '<div align="center">Não foram Encontrados Resultados para esta Pesquisa</div>';
	}
}

?>
<br />
<? include "rodape.php" ?>
