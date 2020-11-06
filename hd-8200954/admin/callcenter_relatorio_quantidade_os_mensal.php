<?
//Callcenter _relatorio_atendimento
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE QUANTIDADE DE OS MENSAIS";

include "cabecalho.php";

?>
<style>
	.msg_erro{
		background-color: #ff0000;
		color: #fff;
		font-weight: bold;
	}
</style>


<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" src="js/jquery_1.js"></script>
<script type="text/javascript" src="js/grafico/highcharts_v3.js"></script>
<script type="text/javascript" src="js/modules/exporting_v3.js"></script>

<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>


<script type="text/javascript">

	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

</script>



<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];

	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		if(strlen($msg_erro) == 0)
			$msg_erro = "Data Inválida, Data Inicial sem valor";
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		if(strlen($msg_erro) == 0)
			$msg_erro = "Data Inválida, Data Final sem valor";
	}

	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		$timestamp_data1 = mktime(0,0,0,$m,$d,$y);
		if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			$timestamp_data2 = mktime(0,0,0,$m,$d,$y);
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}

	if($xdata_inicial > $xdata_final and strlen($msg_erro) == 0)
		$msg_erro = "Data Inválida, Data Inicial maior do que Data Final";

	if(strlen($msg_erro) == 0){
		$segundos_diferenca = $timestamp_data2 - $timestamp_data1;
		$dias_diferenca = $segundos_diferenca / (24*60*60);
		if($dias_diferenca > 31){
			$msg_erro = "Período inválido, Escolha um período de 31 dias";
		}
	}

	if(strlen($msg_erro) == 0){
		if (strlen($btn_acao)>0 and strlen($msg_erro) == 0) {
			$sql = "
			select ca.nome as DR, ca.cliente_admin, lower(ca.estado) as estado,lower(ca.cidade) as cidade,
			(select count(o.os) from tbl_os o left join tbl_hd_chamado_extra x on o.os = x.os inner join tbl_hd_chamado c on x.hd_chamado= c.hd_chamado inner join tbl_cliente_admin a on c.cliente_admin = a.cliente_admin where a.cliente_admin = ca.cliente_admin and c.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59') as qtd_os_aberta ,
			(select count(o.os) from tbl_os o left join tbl_hd_chamado_extra x on o.os = x.os inner join tbl_hd_chamado c on x.hd_chamado= c.hd_chamado inner join tbl_cliente_admin a on c.cliente_admin = a.cliente_admin where a.cliente_admin = ca.cliente_admin and c.status ilike('resolvido') and c.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ) as qtd_os_resolvida,
			(select count(o.os) from tbl_os o left join tbl_hd_chamado_extra x on o.os = x.os inner join tbl_hd_chamado c on x.hd_chamado= c.hd_chamado inner join tbl_cliente_admin a on c.cliente_admin = a.cliente_admin where a.cliente_admin = ca.cliente_admin and (c.status ilike('agendado') or c.status ilike('pendente') ) and c.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ) as qtd_os_pendente
			from tbl_cliente_admin ca  where ca.fabrica = $login_fabrica;
			";

			$res = pg_exec($con,$sql);
			if(pg_num_rows($res)>0){
				$linhas = pg_num_rows($res);
				$iaux = 0;				
				$iaux2 = 0;
				for($i=0;$i<$linhas;$i++){
					$table_lines .= "
					<tr>
					<td>".trim(pg_result($res,$i,dr))."</td>
					<td>".pg_result($res,$i,qtd_os_aberta)."</td>
					<td>".pg_result($res,$i,qtd_os_resolvida)."</td>
					<td>".pg_result($res,$i,qtd_os_pendente)."</td>
					</tr>";

					$google_table .= "['".pg_result($res,$i,dr)."',".pg_result($res,$i,qtd_os_aberta).",".pg_result($res,$i,qtd_os_resolvida).",".pg_result($res,$i,qtd_os_pendente)."],";					
					if($i%10 == 0 && $i > 0){											
						$google_table = preg_replace('/,$/','',$google_table);	
						$arrayGoogle_table[$iaux] = $google_table;
						$google_table = null;
						$iaux += 1;						
					}

					$total_abertas += pg_result($res,$i,qtd_os_aberta);
					$total_finalizadas += pg_result($res,$i,qtd_os_resolvida);
					$total_pendentes += pg_result($res,$i,qtd_os_pendente);

					switch (pg_result($res,$i,estado)) {
						case 'ac':
							$qtd_equipamentos = 1;
							break;
						case 'al':
							$qtd_equipamentos = 10;
							break;
						case 'am':
							$qtd_equipamentos = 7;
							break;
						case 'ap':
							$qtd_equipamentos = 8;
							break;
						case 'ba':
							$qtd_equipamentos = 88;
							break;
						case 'ce':
							$qtd_equipamentos = 4;
							break;
						case 'es':
							$qtd_equipamentos = 21;
							break;
						case 'go':
							$qtd_equipamentos = 43;
							break;
						case 'ma':
							$qtd_equipamentos = 0;
							break;
						case 'mg':
							$qtd_equipamentos = 60;
							break;
						case 'ms':
							$qtd_equipamentos = 1;
							break;
						case 'mt':
							$qtd_equipamentos = 5;
							break;
						case 'pa':
							$qtd_equipamentos = 13;
							break;
						case 'pb':
							$qtd_equipamentos = 4;
							break;
						case 'pe':
							$qtd_equipamentos = 1;
							break;
						case 'pi':
							$qtd_equipamentos = 5;
							break;
						case 'pr':
							$qtd_equipamentos = 13;
							break;
						case 'rj':
							$qtd_equipamentos = 4;
							break;
						case 'rn':
							$qtd_equipamentos = 1;
							break;
						case 'ro':
							$qtd_equipamentos = 0;
							break;
						case 'rr':
							$qtd_equipamentos = 9;
							break;
						case 'rs':
							$qtd_equipamentos = 12;
							break;
						case 'sc':
							$qtd_equipamentos = 22;
							break;
						case 'se':
							$qtd_equipamentos = 0;
							break;
						case 'sp':
							if(pg_result($res,$i,cidade) == 'são paulo'){
								$qtd_equipamentos = 16;
							}else{
								$qtd_equipamentos = 12;
							}
							break;
						case 'to':
							$qtd_equipamentos = 0;
							break;
						default:
							$qtd_equipamentos = 0;
							break;
					}
					$indice_divisao = pg_result($res,$i,qtd_os_aberta) / $qtd_equipamentos;

					if($indice_divisao <= 0 ){
						$indice_divisao = 0;
					}
					$google_table2 .= "['".pg_result($res,$i,dr)."',$indice_divisao],";
					if($i%10 == 0 && $i > 0){											
						$google_table2 = preg_replace('/,$/','',$google_table2);	
						$arrayGoogle_table2[$iaux2] = $google_table2;
						$google_table2 = null;
						$iaux2 += 1;						
					}

				}					

				if($google_table <> null){																			
					$google_table = preg_replace('/,$/','',$google_table);	
					$arrayGoogle_table[$iaux] = $google_table;					
					$google_table = null;
				}

				if($google_table2 <> null){																			
					$google_table2 = preg_replace('/,$/','',$google_table2);	
					$arrayGoogle_table2[$iaux2] = $google_table2;					
					$google_table2 = null;
				}

				$google_table = preg_replace('/,$/','',$google_table);
				$google_table2 = preg_replace('/,$/','',$google_table2);
				$google_table = "['DR', 'OS\'s Abertas', 'OS\'s Finalizadas', 'OS\'s Pendentes'],".$google_table;

				$google_table2 = "['DR', 'Indice de divisão'],".$google_table2;

				$table_lines .= "
									<tr style='background: #e6e6e6'>
										<td>Total</td>
										<td>$total_abertas</td>
										<td>$total_finalizadas</td>
										<td>$total_pendentes</td>
									</tr>";
			}

			if(strlen($table_lines) == 0){
				$table_lines = "";
			}
		}
	}
}

?>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
	<? if(strlen($msg_erro)>0){ ?>
		<tr class='msg_erro'><td><? echo $msg_erro ?></td></tr>
	<? } ?>
	<tr class='titulo_tabela'>
		<td>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>

				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Data Inicial</td>
					<td align='left' nowrap>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
					</td>
					<td align='right' nowrap><font size='2'>Data Final</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
					</td>
					<td width="10">&nbsp;</td>
				</tr>
			</table><br>
			<input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
		</td>
	</tr>
</table>
</FORM>

<br />
	<?php		
		if(count($arrayGoogle_table) > 0){
			for($i=0;$i<count($arrayGoogle_table);$i++){
				echo '
				<div id="div_grafico'.$i.'" style="border-bottom: 2px dotted #e3e3e3;margin: 0 0 20px 0">
				</div>
				';
			}
		}

		if(count($arrayGoogle_table2) > 0){
			for($i=0;$i<count($arrayGoogle_table2);$i++){
				echo '
				<div id="div_grafico'.$i.'_2" style="border-bottom: 2px dotted #e3e3e3;margin: 0 0 20px 0">
				</div>			
				';
			}
		}
	?>

<div id="container" style="width: 700px; min-height: 500px; margin: 0 auto">
	<?php
	if(strlen($table_lines) > 0){

		?>

	    <div id="env_tabela" style="border:1px solid #e6e6e6;margin-bottom:10px">
			<table width="700" border="0" align="center" cellpadding="1" cellspacing="1" class="tabela">
				<tbody>
					<tr class="titulo_coluna">
						<td>DR</td>
						<td>OS's Abertas</td>
						<td>OS's Finalizadas</td>
						<td>OS's Pendentes</td>
					</tr>
					<?php
					echo $table_lines;
					?>
				</tbody>
			</table>
		</div>

		<!-- Grafico -->
		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	    <script type="text/javascript">
	      google.load('visualization', '1', {packages: ['corechart']});
	    </script>
	    <script type="text/javascript">
	      function drawVisualization() {
	      	var options = {
	          title : 'Gráfico de OS\'s mensais',
	          titleTextStyle: {fontSize:15},
	          height: 300,
	          vAxis: {title: "Número de OS\'s"},	          
	          hAxis: {title: "DR's",textStyle:{fontSize: 8}},
	          seriesType: "bars",
	          legend :{position:'top'}
	        };

	        // Some raw data (not necessarily accurate)
	        <?php
	        if(count($arrayGoogle_table)>0){
	        	for($i=0;$i<count($arrayGoogle_table);$i++){
	        		echo "
	        		var data".$i." = google.visualization.arrayToDataTable([
	        					['DR', 'OS\'s Abertas', 'OS\'s Finalizadas', 'OS\'s Pendentes'],
			          			".$arrayGoogle_table[$i]."			         
			        			]);
	        		";

					echo "
					var chart = new google.visualization.ComboChart(document.getElementById('div_grafico".$i."'));
	        		chart.draw(data".$i.", options);
					";
	        	}
	        }
	        ?>

	        // var chart = new google.visualization.ComboChart(document.getElementById('div_grafico'));
	        // chart.draw(data, options);
	      }
	      google.setOnLoadCallback(drawVisualization);
	    </script>

	    <script type="text/javascript">
	      function drawVisualization() {
	        var options = {
	          title : 'Distribuição Geográfica Ponderada',
	          titleTextStyle: {fontSize:15},
	          height: 300,
	          vAxis: {title: "Indice de Divisão Total"},
	          hAxis: {title: "DR's",textStyle:{fontSize: 8}},
	          seriesType: "bars",
	          legend :{position:'top'}

	        };

	        // Some raw data (not necessarily accurate)

	        <?php
	        if(count($arrayGoogle_table2)>0){
	        	for($i=0;$i<count($arrayGoogle_table2);$i++){
	        		echo "
	        		var data_".$i." = google.visualization.arrayToDataTable([			          
	        			['DR', 'Indice de divisão'],
			          	".$arrayGoogle_table2[$i]."			          
			        ]);
	        		";

	        		echo "
	        		var chart = new google.visualization.ComboChart(document.getElementById('div_grafico".$i."_2'));
	        		chart.draw(data_".$i.", options);
	        		";
	        	}
	        }

	        ?>

	        // var chart = new google.visualization.ComboChart(document.getElementById('div_grafico_2'));
	        // chart.draw(data, options);
	      }
	      google.setOnLoadCallback(drawVisualization);
	    </script>



		<?php

	}else{
		if($btn_acao != ""){
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}
	?>
</div>

	<?php
?>

<? include "rodape.php" ?>
