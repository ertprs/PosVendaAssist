<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';

include 'funcoes.php';

$title = "Relatório de Inspeção";

include 'cabecalho_new.php';
$plugins = array(
	"mask",
	"datepicker",
	"shadowbox"

);

include("plugin_loader.php");

$pesqusa_id = "22";


$sql = "select admin, nome_completo, privilegios from tbl_admin where fabrica = $login_fabrica and (privilegios ilike('%inspetor%') or privilegios ilike('%*%'))";

$res = pg_query($con,$sql);
$admins = pg_fetch_all($res);
$tabelaArray = array();

if(isset($_POST["pesquisar"])){

	$data_inicial = $_POST['data_inicial'];
	$data_final = $_POST['data_final'];
	if(trim($data_inicial) != "" && trim($data_final)){

		$admin = $_POST['admin'];
		$estado = $_POST['estado'];

		
		$sql = "SELECT tbl_pergunta.descricao as pergunta,tbl_resposta.pergunta as idpergunta,
				count(tbl_resposta.tipo_resposta_item),tbl_resposta.tipo_resposta_item,
				tbl_tipo_resposta_item.descricao
				from tbl_auditoria_online 
				inner join tbl_posto on tbl_auditoria_online.posto = tbl_posto.posto 
				inner join tbl_resposta on tbl_resposta.auditoria_online = tbl_auditoria_online.auditoria_online   
				inner join tbl_pergunta on tbl_resposta.pergunta = tbl_pergunta.pergunta
				inner join tbl_tipo_resposta_item on tbl_tipo_resposta_item.tipo_resposta_item = tbl_resposta.tipo_resposta_item
				inner join tbl_tipo_resposta on tbl_tipo_resposta.tipo_resposta = tbl_tipo_resposta_item.tipo_resposta
				where tbl_auditoria_online.fabrica = $login_fabrica
				and tbl_auditoria_online.pesquisa = $pesqusa_id
				and tbl_resposta.tipo_resposta_item is not null
				and tbl_tipo_resposta.descricao not ilike('%Sim,%')
				and tbl_auditoria_online.data_visita between '$data_inicial' and '$data_final'";
				


		if($admin != ""){
			$where = " and tbl_auditoria_online.admin = $admin";
		}

		if($estado){
			$where .= " and tbl_posto.estado = '$estado'";
		}	

		$order = " group by tbl_resposta.tipo_resposta_item,tbl_pergunta.descricao,tbl_resposta.pergunta,
				tbl_tipo_resposta.descricao,tbl_tipo_resposta_item.descricao
				order by tbl_resposta.pergunta;";

		$sql .= $where.$order;	
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) >0 ){
			$auditorias = pg_fetch_all($res);	
		}else{
			$auditorias = null;
		}
		



		$sql = "SELECT visita_posto from tbl_auditoria_online
				where fabrica = $login_fabrica
				and concorda_relatorio = true
			   	and pesquisa = $pesqusa_id
				and tbl_auditoria_online.data_visita between '$data_inicial' and '$data_final'";
		
		$resCount = pg_query($con,$sql);
		$visita_auditoria_array = pg_fetch_all($resCount);

		

		$visita_auditoria_count['visita'] = 0;
		$visita_auditoria_count['auditoria'] = 0;

		for ($i=0; $i < count($visita_auditoria_array); $i++) { 
			if($visita_auditoria_array[$i]['visita_posto'] == "t"){
				$visita_auditoria_count['visita'] += 1; 
			}else{
				$visita_auditoria_count['auditoria'] += 1;
			}
		}



		$sql = "SELECT tbl_auditoria_online.admin, tbl_admin.nome_completo,tbl_auditoria_online.data_visita as data_pesquisa,
		tbl_auditoria_online.conclusao_auditoria,tbl_auditoria_online.tipo_auditoria,tbl_posto.nome,
		tbl_auditoria_online.visita_posto , tbl_auditoria_online.auditoria_online, concorda_relatorio
		from tbl_auditoria_online 
		inner join tbl_posto on tbl_auditoria_online.posto = tbl_posto.posto 
		inner join tbl_admin on tbl_admin.admin = tbl_auditoria_online.admin 
		where tbl_auditoria_online.fabrica = $login_fabrica 
		and tbl_auditoria_online.data_visita between '$data_inicial' and '$data_final'
		and tbl_auditoria_online.pesquisa = $pesqusa_id";
		
		if($admin != ""){
			$where = " and tbl_auditoria_online.admin = $admin";
		}

		if($estado){
			$where .= " and tbl_posto.estado = '$estado'";
		}



		$order = " order by nome_completo,visita_posto , tipo_auditoria,data_pesquisa, conclusao_auditoria;";

		$sql = $sql.$where.$order;
	


		$resTabela = pg_query($sql);

		if(pg_num_rows($resTabela) > 0){
			$tabelaArray = pg_fetch_all($resTabela);
		}

	}else{
		$msg_erro = "Preencha as datas iniciais e finais";
	}

	

	
	
}


?>

<script type="text/javascript" src="js/grafico/highcharts_v3.js"></script>

<style type="text/css">

.titulo-tabela{
	background: #3C425F ;
	color: #fff;
}

.aprovado{
	background: #CBFAC7 ;
}

.aprovado-parcial{
	background: #FFFDCF ;
}

.reprovado{
	background: #FCDCDC ;
}

.name-section{
	background: #E4E4E4 ;
}

.sub-menu-section{
	cursor: pointer;
}

.data-row{
	display: none;
}

</style>


<?php
	
	if($msg_erro != ""){

		?>
		<div class="container">
			<div class="row-fluid">
				<div class="span12">
					<div class="alert alert-error">
						<p><b><?php echo $msg_erro ?></b></p>
					</div>
				</div>
			</div>

		</div>
		<?php

	}
?>


<form name="frm_inspecao" id='frm_inspecao' method="post" action="<?=$PHP_SELF?>"  enctype="multipart/form-data" class="form-inline tc_formulario">
	<div class="titulo_tabela ">Consulta Inspeção de Postos Autorizados</div>
	<div class="row">
		<div class='span12'>&nbsp</div>		
	</div>
	<div class="row">
		<div class='span12'>&nbsp</div>		
	</div>

	<div class="row-fluid">
		<div class='span2'></div>		
		<div class='span4'>
			<div class='control-group tac'>
				<label class='control-label' for='codigo_posto'>Data Inicial</label>
				<div class='controls controls-row tac'>
					<div class='input-append'>
						<input type="text" name="data_inicial" id="data_inicial" class="tac">
					</div>
				</div>
			</div>
		</div>		
		<div class='span4'>
			<div class='control-group tac'>
					<label class='control-label' for='descricao_posto'>Data Final</label>
					<div class='controls controls-row tac'>
						<div class='input-append'>
							<input type="text" name="data_final" id="data_final"  class="tac">
						</div>
					</div>
				</div>
		</div>		
		<div class='span2'></div>		
	</div>	

	<div class="row-fluid">
		<div class='span2'></div>		
		<div class='span4'>
			<div class='control-group tac'>
				<label class='control-label' for='codigo_posto'>Inspetor</label>
				<div class='controls controls-row tac'>
					<div class='input-append'>

						<select name="admin">
							<?php
							echo "<option value=''></option>";
							foreach ($admins as $value) {
								echo "<option value='".$value['admin']."'>".$value['nome_completo']."</option>";
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>		
		<div class='span4'>
			<div class='control-group tac'>
					<label class='control-label' for='descricao_posto'>Estado</label>
					<div class='controls controls-row tac'>
						<div class='input-append'>
							<select name="estado">
								<?php
								echo "<option value=''></option>";
								foreach ($array_estados() as  $key => $value) {
									echo "<option value=".$key.">".$value."</option>";
								}

								?>
							</select>
						</div>
					</div>
				</div>
		</div>		
		<div class='span2'></div>		
	</div>	
	<div class="row-fluid">
		<div class="span12">&nbsp</div>
	</div>
	<div class="row-fluid">
		<div class='span2'></div>				
		<div class='span8 tac'>
			<input class="btn" name="pesquisar" type="submit" value="Pesquisar">
		</div>		
		<div class='span2'></div>		
	</div>

</form>

</div>

<?php


if($auditorias != null){
?>
	<div class="container" style="border: 1px solid #e2e2e2">
		<?php	
		# Agrupar graficos
		$pergunta = "";	

		for ($i=0; $i < count($auditorias); $i++) { 
			
			if($auditorias[$i]['idpergunta'] == $pergunta){
				$grupos[$pergunta][] = $auditorias[$i];
			}else{
				$pergunta = $auditorias[$i]['idpergunta'];			
				$grupos[$pergunta][] = $auditorias[$i];
			}
		}

		$add = 0;
		$rows = (int) (count($grupos)/2);
		$add =  (int) (count($grupos)%2);
		if($add > 0){
			$rows = $row + 1;
		}

		
		$auditoria_visita_flag = false;
		$keys = array_keys($grupos);	

		for ($i=0; $i < count($keys); $i++) { 
			echo '<div class="row">
					<div class="span5" id="graf'.$keys[$i].'"></div>';
			if(isset($keys[$i+1])){
				echo '<div class="span5" id="graf'.$keys[$i+1].'"></div>';
				$i++;
			}else{
				echo '<div class="span5" id="auditoria_visita"></div>';
				$auditoria_visita_flag = true;
			}
			echo "</div>";
		}

		if($auditoria_visita_flag == false){
			echo '<div class="row">
					<div class="span10" id="auditoria_visita"></div>
				</div>';
		}




		foreach ($grupos as $key => $value) {
			for($i=0;$i<count($value);$i++){
				$grupos[$key][$i]['pergunta'] = utf8_encode($grupos[$key][$i]['pergunta']);
				$grupos[$key][$i]['descricao'] = utf8_encode($grupos[$key][$i]['descricao']);
			}
		}
		?>
	</div>
<?php
}else{
	if(count($tabelaArray) > 0){
	?>
		<div class="container">
			<div class="row-fluid">
				<div class="span12">
					<div class="alert">
						<p>Os gráficos estárão amostra assim que algum posto aprovar alguma inspeção</p>
					</div>			
				</div>
			</div>
		</div>
	<?php
	}
}


?>




<div class="container">
	<div class="row">
		<div class="span12">&nbsp</div>
	</div><div class="row">
		<div class="span12">&nbsp</div>
	</div>

	
	<?php
if(count($tabelaArray) > 0){


		?>
		<div class="row-fluid">
			<div class="span3" style="border: 1px solid #333;padding: 5px">
				<ul class="unstyled">
					<li><span><b>Legenda</b></li>
					<li><span class="label label-success">&nbsp</span> Aprovado</li>
					<li><span class="label label-warning">&nbsp</span> Aprovado Parcialmente</li>
					<li><span class="label label-important">&nbsp</span> Reprovado</li>
				</ul>
			</div>		
		</div>

		<table id="tbl-inspecoes" class="table table-bordered  table-hover">
			<thead>
				<tr class="titulo-tabela">
					<th>Inspetor</th>
					<th style="width: 65px; text-align: center">Data</th>
					<th>Posto</th>
					<th>Conclusão da Auditoria</th>
					<th>Tipo de Auditoria</th>				
				</tr>
			</thead>
			<tbody>
				<?php
				$inspetorAux = "";
				$visitaAux = "";

				for ($i=0; $i < count($tabelaArray); $i++) { 				

					switch (trim($tabelaArray[$i]['tipo_auditoria'])) {
						case 'rotina':
							$tabelaArray[$i]['tipo_auditoria'] = "Rotina";
							break;

						case 'resultado':
							$tabelaArray[$i]['tipo_auditoria'] = "Resultado";
							break;											
					}

					switch ($tabelaArray[$i]['concorda_relatorio']) {
						case 't':
							$tabelaArray[$i]['conclusao_auditoria'] = "Aprovado";
							$tabelaArray[$i]['class'] = "aprovado";
							break;

						case 'f':
							$tabelaArray[$i]['conclusao_auditoria'] = "Reprovado - Descredencimento do posto";
							$tabelaArray[$i]['class'] = "reprovado";
							break;						

						default:
							$tabelaArray[$i]['conclusao_auditoria'] = "Aprovado parcialmente";
							$tabelaArray[$i]['class'] = "aprovado-parcial";
							break;						
						
					}


					$classAux = $tabelaArray[$i]['class'];


					if($tabelaArray[$i]['nome_completo'] != $inspetorAux){
						$inspetorAux = $tabelaArray[$i]['nome_completo'];
						$visitaAux = $tabelaArray[$i]['visita_posto'];

						?>
						<tr class="sub-menu name-section">
							<td><b><?php echo $tabelaArray[$i]['nome_completo'] ?></b></td>
							<td colspan='4'></td>							
						</tr>						
						<?php
						if($visitaAux == 't'){
							?>
							<tr>
								<td></td>
								<td colspan='4' class="sub-menu-section"><b>Visita</b></td>
							</tr>
							<?php
						}else{
							?>
							<tr class="sub-menu">
								<td></td>
								<td colspan='4' class="sub-menu-section"><b>Auditoria</b></td>
							</tr>
							<?php
						}
					}else{
						if($visitaAux != $tabelaArray[$i]['visita_posto']){
							$visitaAux = $tabelaArray[$i]['visita_posto'];

							if($visitaAux == 't'){
								?>
								<tr class="sub-menu">
									<td></td>
									<td colspan='4' class="sub-menu-section"><b>Visita</b></td>
								</tr>
								<?php
							}else{
								?>
								<tr class="sub-menu">
									<td></td>
									<td colspan='4' class="sub-menu-section"><b>Auditoria</b></td>
								</tr>
								<?php
							}
						}
					}
					?>
					<tr class="data-row <?php echo $classAux ?>">
						<td style="text-align: center">
							<?php
							if($classAux == "reprovado"){
								?>
								<a class="btn" target="_BLANK" href="rg-gat-001_suggar_editar.php?inspecao=<?php echo $tabelaArray[$i]['auditoria_online']; ?>"><i class="icon-pencil"></i> Editar</a>
								<?php
							}
							?>
						</td>
						<td><?php echo date('d-m-Y',strtotime($tabelaArray[$i]['data_pesquisa'])) ?></td>
						<td><a target="_BLANK" href="visualiza_relatorio_visita_tecnico.php?auditoria_online=<?php echo $tabelaArray[$i]['auditoria_online']  ?>"><?php echo $tabelaArray[$i]['nome'] ?></a></td>																			
						<td><?php echo $tabelaArray[$i]['conclusao_auditoria'] ?></td>
						<td><?php echo $tabelaArray[$i]['tipo_auditoria'] ?></td>											
					</tr>					
					<?php					
				}
				?>
			</tbody>
		</table>
		<?php

	}

	?>
	
</div>








<script type="text/javascript">

	/*

	<?php

	print_r($grupos);

	?>

	*/

	<?php
	if(count($grupos) > 0){
	?>
	var grupos = <?php echo json_encode($grupos); ?>;
	var auditoria_visita = <?php echo json_encode($visita_auditoria_count) ?>;

	var teste;

	$(function(){
		
		for(var k in grupos) {
			data1 = new Array();

			var pergunta = grupos[k][0]['pergunta'];			
   			for (var i = 0; i < grupos[k].length; i++) {
   				data1.push(Array(grupos[k][i]['descricao'],parseFloat(grupos[k][i]['count'])));
   			};


   			$("#graf"+k).highcharts({
				chart: {
	                plotBackgroundColor: null,
	                plotBorderWidth: null,
	                plotShadow: false
	            },
				title: { 
					text: pergunta
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true,
                   					 format: '<b>{point.name}</b>: {point.percentage:.1f} %'
						},
						showInLegend: true
					}
				},
				series: [{
					type: 'pie',
					name: pergunta,
					data: data1
				}]
			});

		}

		console.log(auditoria_visita);

		auditoria_visita_data = new Array();			
		auditoria_visita_data.push(Array("Visita",auditoria_visita.visita));
		auditoria_visita_data.push(Array("Auditoria",auditoria_visita.auditoria));

		$("#auditoria_visita").highcharts({
				chart: {
	                plotBackgroundColor: null,
	                plotBorderWidth: null,
	                plotShadow: false
	            },
				title: { 
					text: 'Visita/Auditoria'
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true,
                    					format: '<b>{point.name}</b>: {point.percentage:.1f} %'
						},
						showInLegend: true
					}
				},
				series: [{
					type: 'pie',
					name: 'Visita/Auditoria',
					data: auditoria_visita_data
				}]
			});
	});

<?php
	}	
?>

	$("#data_inicial").datepicker().mask("99/99/9999");    
	$("#data_final").datepicker().mask("99/99/9999");    

	$(".sub-menu-section").click(function(){
		
		if($("#tbl-inspecoes").find('tr[class=sub-menu]').last().index() == $(this).parent('tr').prevAll('tr[class=sub-menu]').first().index()){			
			$(this).parent('tr').nextAll('.data-row').fadeIn(500);
		}else{
			$(this).parent('.sub-menu').nextUntil(".sub-menu").fadeIn(500);	
		}
	});


</script>


<?php

include 'rodape.php';

?>
