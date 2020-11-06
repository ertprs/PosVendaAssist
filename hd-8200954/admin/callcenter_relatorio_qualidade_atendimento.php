<?
//Callcenter _relatorio_atendimento
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE QUALIDADE DE ATENDIMENTO";

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
<script type="text/javascript" src="js/grafico/highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

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

<? //include "javascript_pesquisas.php" ?>

<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];	
	$cliente_admin 		= $_POST['cliente_admin'];

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

			if(strlen($cliente_admin) > 0){
				$and_aux = " and c.cliente_admin = $cliente_admin ";
			}else{
				$and_aux = "";
			}


			$sql = "
			select a.nome,c.hd_chamado, c.data, c.resolvido, ci.nome, case when cs.hora is null then 20 else cs.hora end from tbl_hd_chamado c 
			left join tbl_hd_chamado_extra e on c.hd_chamado = e.hd_chamado
			left join tbl_cidade ci on e.cidade = ci.cidade 
			left join tbl_cidade_sla cs on ci.nome = cs.cidade 
			left join tbl_cliente_admin a on a.cliente_admin = c.cliente_admin
			where c.cliente_admin is not null and c.fabrica = $login_fabrica and c.data is not null and c.resolvido is not null and c.data between '$data_inicial 00:00:00' and '$data_final 23:59:00' $and_aux;
			";

			$res = pg_exec($con,$sql);
			if(pg_num_rows($res)>0){	
				$dentroprazo = 0;
				$foraprazo = 0;
				$linhas = pg_num_rows($res);
			 	for($i=0;$i<$linhas;$i++){

			 		$xhd_chamado = pg_result($res,$i,hd_chamado);
			 		$xnome = pg_result($res,$i,nome);
			 		$data = pg_result($res,$i,data);
			 		$resolvido = pg_result($res,$i,resolvido);
			 		$horas_sla = pg_result($res,$i,hora);


			 		$time_inicial = $data;
			 		$resx = "";
			 		$horax = "";
			 		$sair = "";
			 		$qtd_horas = 0;

			 		$hora_i = $data;
			 		$hora_f = $resolvido;
			 		$sair = 'nao';

			 		$minutos_total = calculaMinutos($data, $resolvido);

			 		$qtd_horas = (int) ($minutos_total/60);

			 	 	$total += 1;			 	 	
			 	 	if($qtd_horas > $horas_sla){
			 	 		$foraprazo += 1;				 	 		
			 	 	}else{
			 	 		$dentroprazo += 1;
			 	 	}
			 	 	// echo $xhd_chamado." - ".$xnome." - ".$data." - ".$resolvido." - ".$qtd_horas."<br>";
			 	 	 
			 	}		 	
			 	
			 	$google_table = "
				['Dentro do Prazo',  $dentroprazo],
	          	['Fora do Prazo',  $foraprazo]
			 	";			 	
			}
			
			if(strlen($google_table) == 0){
				$google_table = "";
			}
		}
	}
}

/* select dr's */

$sql = "select cliente_admin,nome from tbl_cliente_admin where fabrica = $login_fabrica";
$res_cliente_admin = pg_exec($sql);	
if(pg_num_rows($res_cliente_admin)>0){
	$linhas = pg_num_rows($res_cliente_admin);	
	for($i=0;$i<$linhas;$i++){
		$xcliente_admin = pg_result($res_cliente_admin,$i,cliente_admin);
		$xnome = pg_result($res_cliente_admin,$i,nome);	
		if($cliente_admin == $xcliente_admin){
			$selected = "selected";
		}else{
			$selected = "";
		}
		$option_drs .= "<option $selected value='$xcliente_admin'>$xnome</option>";
	}
}else{
	$option_drs = "";
}

/*-------------*/

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
					</td>
					<td align='right' nowrap><font size='2'>Data Final</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">						
					</td>					
					<td align='right' nowrap><font size='2'>DR's</td>
					<td align='left' nowrap>
						<select style='width:180px' name="cliente_admin">
							<option></option>
							<?php
							echo $option_drs;
							?>
						</select>
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

<div id="container" style="width: 700px; height: 400px; margin: 0 auto">
	<?php
	if(strlen($google_table) > 0){

		?>

	    <!-- Grafico -->
		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	    <script type="text/javascript">
	      google.load('visualization', '1', {packages: ['corechart']});
	    </script>
	    <script type="text/javascript">
	      function drawVisualization() {
	        // Some raw data (not necessarily accurate)
	        var data = google.visualization.arrayToDataTable([
	          ['Status', 'Os\'s'],
	          <?php
	          echo $google_table;
	          ?>
	        ]);

	        var options = {
	          title : 'Qualidade de Atendimento á Ordens de Serviço',
	          colors : ['#0ca803',"#ff8400"],
	          height: 400
	        };

	        var chart = new google.visualization.PieChart(document.getElementById('div_grafico'));
	        chart.draw(data, options);
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
	<div id="div_grafico">
	
	</div>
</div>

	<?php
?>

<? include "rodape.php" ?>


<?php
#Função de Calculo de Horas

function calculaMinutos($datainicial,$datafinal){


	$arr_datainicial = getdate(strtotime($datainicial));
	$arr_datafinal = getdate(strtotime($datafinal));


	$inicial_ob = new DateTime($arr_datainicial['year'].'-'.$arr_datainicial['mon'].'-'.$arr_datainicial['mday'].' '.$arr_datainicial['hours'].':'.$arr_datainicial['minutes']);

	$final_ob = new DateTime($arr_datafinal['year'].'-'.$arr_datafinal['mon'].'-'.$arr_datafinal['mday'].' '.$arr_datafinal['hours'].':'.$arr_datafinal['minutes']);

	$dia_ini = $inicial_ob->format('d');
	$hora_ini = $inicial_ob->format('H');
	$minuto_ini = $inicial_ob->format('i');

	$dia_fim = $final_ob->format('d');
	$hora_fim = $final_ob->format('H');
	$minuto_fim = $final_ob->format('i');



	if($inicial_ob->format('H') <= 7 and $inicial_ob->format('i') < 30){
		$inicial_ob->setTime(7,30);
	}
	
	if($final_ob->format('D') == 'Sat'){
		if($final_ob->format('H') > 16 and $final_ob->format('i') > 00){
			$final_ob->setTime(16,00);
		}
	}else{		
		if($final_ob->format('H') > 20 and $final_ob->format('i') >= 00){
			$final_ob->setTime(20,00);
		}
	}

	// echo $inicial_ob->format('d-m-y H:i')."<br>";
	// echo $final_ob->format('d-m-y H:i')."<br><br>";
	$ano_ok = false;
	$mes_ok = false;
	$dia_ok = false;
	$minutos = 0;	
	$minutos_aux = 0;
	if($inicial_ob->format('d') != $final_ob->format('d')){
		if($inicial_ob->format('D') == 'Sat'){
			$hora_final_diaria = 16;
		}else{
			$hora_final_diaria = 20;	
		}
		
		$minuto_final_diario = 00;
	}else{
		$hora_final_diaria = $final_ob->format('H');
		$minuto_final_diario = $final_ob->format('i');
	}

	$dias_validos = 0;
	$minutos_validos = 0;
	$minutos_validos_sabado = 0;

	$hora_final_diaria = 24;
	$minuto_final_diario = 61;
	if($inicial_ob->format('m') != $final_ob->format('m')){			
		$mf = $final_ob->format('m');			
		for($m = $inicial_ob->format('m');$m<=$mf;$m++){				
			if($inicial_ob->format('m') != $final_ob->format('m')){
				$df = date("d",mktime(0, 0, 0, ($inicial_ob->format('m') + 1), 0, $inicial_ob->format('Y')));	
			}else{
				$df = $final_ob->format('d');
			}
			// echo "df $df <br>";
			//$df = $final_ob->format('d');		
			for($d = $inicial_ob->format('d');$d<=$df;$d++){
				// echo "*".$d."<br>";
				for($h = $inicial_ob->format('H');$h<=$hora_final_diaria;$h++){
					// echo $h."-(";
					if($h == $hora_final_diaria){
						$xminuto_final_diario = $minuto_final_diario;
					}else{
						$xminuto_final_diario = 61;
					}											
					for($i=$inicial_ob->format('i');$i<$xminuto_final_diario;$i++){							
						$minutos += 1;	
						$minutos_aux +=1;							
						//$inicial_ob->add(new DateInterVal('PT1M'));		
						if($inicial_ob->format('D') != 'Sun'){
							#diff de sabado
							if($inicial_ob->format('D') != 'Sat'){
								#se hora atual > 7 e < 20 (periodo de trabalho)
								if($inicial_ob->format('H') >= 7 and $inicial_ob->format('H') < 20){		
									#se dia atual == dia de inicio ou dia de fim
									if($inicial_ob->format('d') == $dia_ini || $inicial_ob->format('d') == $dia_fim){
										#se hora atual >= hora inicial e <= a hora final 
										if($inicial_ob->format('d') == $dia_ini){
											if($inicial_ob->format('H') == $hora_ini ){
												if($inicial_ob->format('i')>=$minuto_ini){	
													// echo $i."a ";
													$minutos_validos +=1;	
												}
											}else{
												// echo $i."b ";	
												$minutos_validos +=1;	
											}
										}
										if($inicial_ob->format('d') == $dia_fim){
											if($inicial_ob->format('H') == $hora_fim ){
												if($inicial_ob->format('i')<=$minuto_fim){
													// echo $i."c' ";	
													$minutos_validos +=1;	
												}
											}else{				
												// echo $i."d ";	
												$minutos_validos +=1;	
											}
										}
									}else{										
										if($inicial_ob->format('H') == 7 and $inicial_ob->format('i') >= 30){
											// echo $i."e ";
											$minutos_validos +=1;
										}elseif ($inicial_ob->format('H') > 7 and $inicial_ob->format('H') < 20 ) {
											// echo $i."f ";
											$minutos_validos +=1;
										}							 	
									}
								}
							}else{
								if($inicial_ob->format('H') >= 7 and $inicial_ob->format('H') <= 16){
									if($inicial_ob->format('H') == 7 and $inicial_ob->format('i') >= 30){
										// echo $i."g ";
										$minutos_validos_sabado +=1;
									}elseif ($inicial_ob->format('H') > 7) {
										// echo $i."h ";
										$minutos_validos_sabado +=1;
									}							 
								}
							}
							$dias_validos +=1;
						}
						$inicial_ob->add(new DateInterVal('PT1M'));			 	
					}
						// echo ") <br>";
				}				 	
			}
		}
	}else{			
		$df = $final_ob->format('d');
		$hora_final_diaria = 24;
		$minuto_final_diario = 61;
		for($d = $inicial_ob->format('d');$d<=$df;$d++){
			// echo "*".$d."<br>";
			for($h = $inicial_ob->format('H');$h<=$hora_final_diaria;$h++){
				// echo $h."-(";
				if($h == $hora_final_diaria){
					$xminuto_final_diario = $minuto_final_diario;
				}else{
					$xminuto_final_diario = 61;
				}							
				for($i=$inicial_ob->format('i');$i<$xminuto_final_diario;$i++){							
					$minutos += 1;	
					$minutos_aux +=1;							
					// $inicial_ob->add(new DateInterVal('PT1M'));		
					if($inicial_ob->format('D') != 'Sun'){	
							#diff de sabado
						if($inicial_ob->format('D') != 'Sat'){
								#se hora atual > 7 e < 20 (periodo de trabalho)
							if($inicial_ob->format('H') >= 7 and $inicial_ob->format('H') < 20){
								if($dia_ini != $dia_fim){
										#se dia atual == dia de inicio ou dia de fim
									if($inicial_ob->format('d') == $dia_ini || $inicial_ob->format('d') == $dia_fim){
											#se hora atual >= hora inicial e <= a hora final 
										if($inicial_ob->format('d') == $dia_ini){
											if($inicial_ob->format('H') == $hora_ini ){
												if($inicial_ob->format('i')>=$minuto_ini){	
													// echo $i."a ";
													$minutos_validos +=1;	
												}
											}else{
												// echo $i."b ";	
												$minutos_validos +=1;	
											}
										}
										if($inicial_ob->format('d') == $dia_fim){
											if($inicial_ob->format('H') == 7){
												if($inicial_ob->format('i')>=30){
													// echo $i."c1' ";	
													$minutos_validos +=1;	
												}
											}elseif($inicial_ob->format('H') == $hora_fim ){
												if($inicial_ob->format('i')<=$minuto_fim){
													// echo $i."c' ";	
													$minutos_validos +=1;	
												}
											}else{	
												if($inicial_ob->format('H') < $hora_fim){
													// echo $i."d ";	
													$minutos_validos +=1;		
												}				
											}
										}
									}else{										
										if($inicial_ob->format('H') == 7 and $inicial_ob->format('i') >= 30){
											// echo $i."e ";
											$minutos_validos +=1;
										}elseif ($inicial_ob->format('H') > 7 and $inicial_ob->format('H') < 20 ) {
											// echo $i."f ";
											$minutos_validos +=1;
										}							 	
									}	
								}else{									
									if($inicial_ob->format('H') == $hora_ini ){
										if($inicial_ob->format('i')>=$minuto_ini){	
											// echo $i."i ";
											$minutos_validos +=1;	
										}
									}elseif($inicial_ob->format('H') == $hora_fim ){
										if($inicial_ob->format('i')<=$minuto_fim){
											// echo $i."k' ";	
											$minutos_validos +=1;	
										}
									}else{	
										if($inicial_ob->format('H') >= $hora_ini and $inicial_ob->format('H') <= $hora_fim){
											// echo $i."m ";	
											$minutos_validos +=1;		
										}	
										
									}
								}
							}

						}
					}else{
						if($inicial_ob->format('H') >= 7 and $inicial_ob->format('H') <= 16){
							if($inicial_ob->format('H') == 7 and $inicial_ob->format('i') >= 30){
								// echo $i."g ";
								$minutos_validos_sabado +=1;
							}elseif ($inicial_ob->format('H') > 7) {
								// echo $i."h ";
								$minutos_validos_sabado +=1;
							}							 
						}
					}
					$dias_validos +=1;
					$inicial_ob->add(new DateInterVal('PT1M'));
				}					
				// $inicial_ob->add(new DateInterVal('PT1M'));						
				// echo ") <br>";
			}				
		}				 	
	}

	return $minutos_validos+$minutos_validos_sabado;

}


?>
