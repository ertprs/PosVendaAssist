<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'includes/funcoes.php';
include 'funcoes.php';
$layout_menu = "gerencia";
$title= "RELATÓRIO DE VISÃO GERAL DE ORDEM DE SERVIÇO";
include "cabecalho_new.php";
$plugins = array(
	"datepicker",
	"mask",
	"dataTable",
	"shadowbox",
	"multiselect"
);
include("plugin_loader.php");
function cmp($a, $b) {
	return $a['f10'] > $b['f10'];
}
?>

<!DOCTYPE html>
<html>
<head>
	<style type="text/css">
		#menu_sidebar {
			margin-left: 1000px;
		}
		.status_checkpoint{
			width:9px;
			height:15px;
			margin:2px 5px;
			padding:0 5px;
			border:1px 
			solid #666;
		}
	</style>
	<script type="text/javascript">
		$(function() {
			$.datepickerLoad(Array("data_final", "data_inicial"));

			Shadowbox.init();

			$("span[rel=lupa]").click(function () {
				$.lupa($(this));
			});
			$('.selecionar_os_posto').on('click', function(){
				if (this.checked == true) {
					valor = $(this).val();
					$('[data-osposto]').each(function(){
						if ($(this).data('osposto') == valor){	
							$(this).prop('checked', true);
						}
					});
				}else{
					valor = $(this).val();
					$('[data-osposto]').each(function(){
						if ($(this).data('osposto') == valor){	
							$(this).prop('checked', false);
						}
					});
				}
			})
			$("#select_all").change(function() {
				if ($(this).is(":checked")) {
					$(".selecionar_os_posto").each(function() {
						$(this)[0].checked = true;
						valor = $(this).val();
						$('[data-osposto]').each(function(){
							if ($(this).data('osposto') == valor){	
								$(this).prop('checked', true);
							}
						});
					});
				} else {
					$(".selecionar_os_posto").each(function() {
						$(this)[0].checked = false;
						valor = $(this).val();
						$('[data-osposto]').each(function(){
							if ($(this).data('osposto') == valor){	
								$(this).prop('checked', false);
							}
						});
					});
				}
			});


		});
		function retorna_posto(retorno){
			$("#codigo_posto").val(retorno.codigo);
			$("#descricao_posto").val(retorno.nome);
		}
	</script>
</head>
<body>
	<?php
	$inicio = str_replace("/","-", $_POST['data_inicial']);
	$final = str_replace("/","-", $_POST['data_final']);
	$data_inicial 	= ($_POST['data_inicial']) ? date('Y-m-d', strtotime($inicio)) : '';
	$data_final		= ($_POST['data_final']) ? date('Y-m-d', strtotime($final)): '';
	$periodo		= ($_POST['periodo']) ? $_POST['periodo'] : 30;
	$posto			= ($_POST['codigo_posto']) ? $_POST['codigo_posto']: '';
	$status			= ($_POST['status']) ? $_POST['status']: '';
	$faixa			= ($_POST['faixa']) ? $_POST['faixa']: '';
	$estado			= ($_POST['estado']) ? $_POST['estado']: '';
	$regiao			= ($_POST['regiao']) ? $_POST['regiao']: '';
	if(!empty($data_inicial) OR !empty($data_final)){		

		if(strlen($msg_erro["msg"])==0){			
			if(strtotime($data_final) < strtotime($data_inicial)){
				$msg_erro["msg"] = "DATA INICIAL MAIOR QUE DATA FINAL.";
			}
		}
		if(strlen($msg_erro["msg"])==0){
			$valida_final = date('Y-m-d', strtotime('+6 months', strtotime($data_inicial)));
			if(strtotime($data_final) > strtotime($valida_final)){
				$msg_erro["msg"] = "AS DATAS DEVEM SER NO MÁXIMO 6 MESES.";
			}
		}
	}
	if (!empty($_POST)){	
		$join = " JOIN tbl_posto po USING(posto)
					JOIN tbl_posto_fabrica pf ON po.posto = pf.posto AND pf.fabrica = $login_fabrica
					JOIN tbl_estado e ON e.estado = po.estado ";	
		if (!empty($posto)) {
			$wherePosto = " AND pf.codigo_posto = '$posto' ";
		}

		if (!empty($status)) {
			$whereStatus = " AND sc.status_checkpoint = $status ";
		}

		if (!empty($estado)) {
			$whereEstado = " AND po.estado = '$estado' ";
		}
		
		if (!empty($regiao)) {			
			$whereRegiao = " AND e.regiao = '$regiao' ";
		}

		if (empty($data_inicial) && empty($data_final)) {
			if (!empty($periodo)) {
				$between = " AND o.data_abertura  between current_date - interval '{$periodo} Days' and current_date ";
			}
		} else {
			if (empty($data_inicial)) {
				$between = " AND o.data_abertura  between to_date('{$data_final}', 'YYYY-MM-DD') - interval '{$periodo} Days' and to_date('{$data_final}', 'YYYY-MM-DD') ";
			} 
			if (empty($data_final)) {
				$between = " AND o.data_abertura  between to_date('{$data_inicial}', 'YYYY-MM-DD') and to_date('{$data_inicial}', 'YYYY-MM-DD') + interval '{$periodo} Days' ";
			}				
		}
	}else {
		$whereFinalizada = " AND finalizada is null ";
	}

	$sqlGrafico = "	SELECT count(os), descricao, sc.cor, 
					(SELECT count(os) 
						FROM tbl_os o
						JOIN tbl_status_checkpoint sc USING(status_checkpoint) 					
						{$join}
						where o.fabrica = {$login_fabrica} 
						{$whereFinalizada}
						{$wherePosto}
						{$whereStatus}
						{$whereEstado}
						{$whereRegiao}
						{$between}) as total
					FROM tbl_os o
					JOIN tbl_status_checkpoint sc USING(status_checkpoint) 					
					{$join}
					WHERE o.fabrica = {$login_fabrica}
					{$whereFinalizada}
					{$wherePosto}
					{$whereStatus}
					{$whereEstado}
					{$whereRegiao}
					{$between}
					GROUP BY descricao, sc.cor;";
	$resGrafico = pg_query($con, $sqlGrafico);

	if (pg_num_rows($resGrafico) == 0) {
		$msg_erro["msg"] = "Nenhum registro encontrado!";
	}
	$graficoGeral = "";
	while ($grafico = pg_fetch_object($resGrafico)) {
		$calculo = number_format((($grafico->count * 100) / $grafico->total), 2);
		$graficoGeral .= "['{$grafico->descricao}', {$calculo}, '{$calculo}%', 'color: {$grafico->cor}'],";
	}	

	?>
  	<?php if (count($msg_erro["msg"]) == 0) {?>
		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	  	<div id="barchart_material" style="width: 900px; height: 300px;"></div>
		<script type="text/javascript">
	    google.charts.load("current", {packages:["corechart"]});
	    google.charts.setOnLoadCallback(drawChart);
	    function drawChart() {
		      var data = google.visualization.arrayToDataTable([
		        ["Element", "Density", {role: 'annotation'} , { role: 'style' } ],
		        <?php echo $graficoGeral ;?>
		      ]);

		       var view = new google.visualization.DataView(data);
	      	   view.setColumns([0, 1, 2, 3]);
		       var options = {
		        title: "",
		        legend: { position: "none" },
		      };
		      var chart = new google.visualization.BarChart(document.getElementById("barchart_material"));
		      chart.draw(view, options);
		  }
		</script>
	<?php } ?>
	<?php if (count($msg_erro["msg"]) > 0) {?>
	    <div class="alert alert-error">
			<h4><?php print_r($msg_erro["msg"]);?></h4>
	    </div>
	<?php } ?>
	<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class ='titulo_tabela'>Parametros de Pesquisa </div>
		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='periodo'>Período</label>
					<div class='controls controls-row'>
						<div class='span10'>							
							<select name="periodo" id="periodo" class='span12'>
								<option value="">Selecione um periodo</option>
								<option value="30">1 mês</option>
								<option value="60">2 mêses</option>
								<option value="90">3 mêses</option>
								<option value="120">4 mêses</option>
								<option value="150">5 mêses</option>
								<option value="180">6 mêses</option>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span8'>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_inicial']?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span8'>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_final']?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='regiao'>Região</label>
					<div class='controls controls-row'>
						<div class='span10'>							
							<select name="regiao" id="regiao" class='span12'>
								<option value="">Selecione uma região</option>
								<option value="CENTRO-OESTE">Centro-Oeste</option>
								<option value="NORDESTE">Nordeste</option>
								<option value="NORTE">Norte</option>
								<option value="SUL">Sul</option>
								<option value="SUDESTE">Sudeste</option>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='estado'>Estado</label>
					<div class='controls controls-row'>
						<div class='span10'>
							<select name="estado" id="estado" class='span12'>
								<option value="">Selecione um estado</option>
							<?php
							$sql_estado = "SELECT
											estado,
											nome
										FROM tbl_estado
										WHERE visivel = true
										ORDER BY nome ";
							$res_estado = pg_query($con, $sql_estado); 
							while ($estado = pg_fetch_object($res_estado)) {
								echo "<option value='{$estado->estado}'>{$estado->nome}</option>";
							}
							?>	
						</select>						
						</div>
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='status'>Status</label>
					<div class='controls controls-row'>
						<div class='span10'>
							<select name="status" id="status" class='span12'>
								<option value="">Selecione um status</option>
							<?php
							$sql_status = "SELECT
											status_checkpoint,
											descricao
										FROM tbl_status_checkpoint
										WHERE status_checkpoint IN (1,2,3,4,8,9,34)
										ORDER BY descricao ";
							$res_status = pg_query($con, $sql_status); 
							while ($checkpoint = pg_fetch_object($res_status)) {
								echo "<option value='{$checkpoint->status_checkpoint}'>{$checkpoint->descricao}</option>";
							}?>
							</select>							
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span1'></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='faixa'>Faixa de tempo</label>
					<div class='controls controls-row'>
						<div class='span10'>							
							<select name="faixa" id="faixa" class='span12'>
								<option value="">Selecione uma faixa de tempo</option>
								<option value="5">até 5 dias</option>
								<option value="6-15">entre 6 a 15 dias</option>
								<option value="16-30">entre 16 a 30 dias</option>
								<option value="31">acima de 30</option>
							</select>
						</div>
					</div>
				</div>
			</div>		
			<div class='span2'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span8 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
    </div>
    <p><br />
    	<button class='btn' id="btn_acao" type="submit"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
    	<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br />
	</form>
	<?php
	if(empty($_POST['btn_acao']))
		exit;
	if (count($msg_erro["msg"]) > 0)
		exit 
	?>
<div align="left" style="position:relative;left:25">
    <h4>Status das OS</h4>
    <table border="0" cellspacing="0" cellpadding="0" width="100%">
        <tbody><tr height="18">
            <td width="18">
                <div class="status_checkpoint" style="background-color:#FF8282">&nbsp;</div>
            </td>
            <td align="left">
                <font size="1">
                    <b>&nbsp;Aguardando Analise</b>
                </font>
            </td>
            <td width="18">
                <div class="status_checkpoint" style="background-color:#FAFF73">&nbsp;</div>
            </td>
            <td align="left">
                <font size="1">
                    <b>&nbsp;Aguardando Peças</b>
                </font>
            </td>
            <td width="18">
                <div class="status_checkpoint" style="background-color:#EF5CFF">&nbsp;</div>
            </td>
            <td align="left">
                <font size="1">
                    <b>&nbsp;Aguardando Conserto</b>
                </font>
            </td>

        </tr>
        <tr height="18">
            <td width="18">
                <div class="status_checkpoint" style="background-color:#8DFF70">&nbsp;</div>
            </td>
            <td align="left">
                <font size="1">
                    <b>&nbsp;Finalizada</b>
                </font>
            </td>
            <td width="18">
                <div class="status_checkpoint" style="background-color:#FF9933">&nbsp;</div>
            </td>
            <td align="left">
                <font size="1">
                    <b>&nbsp;Aguardando Produto</b>
                </font>
            </td>
            <td width="18">
                <div class="status_checkpoint" style="background-color:#3DFAF6">&nbsp;</div>
            </td>
            <td align="left">
                <font size="1">
                    <b>&nbsp;Aguardando Recebimento</b>
                </font>
            </td>

        </tr>
    </tbody></table>
</div>
	<form method='POST' name="frm_lista" action="comunicado_produto.php" target="_blank" >
	<table id="resultado_os" class='table table-striped table-bordered table-hover table-large'>
		<thead>
			<tr class = 'titulo_coluna'>
				<th><input type='checkbox' id='select_all'>Todos</th>
				<th colspan="3">Posto</th>
				<th>Estado</th>
				<th>TMC OS</th>
				<th>Entre 1 e 5</th>
				<th>Entre 6 e 15</th>
				<th>Entre 16 e 30</th>
				<th>Acima de 30</th>
				<th>Total geral</th>
				<th>Abrir Help-Desk</th>
			</tr>
		</thead>
		<tbody>
			<?php

			if (!empty($posto)) {
				$wherePosto = " AND pf.codigo_posto = '$posto' ";
			}

			if (!empty($status)) {
				$whereStatus = " AND sc.status_checkpoint = $status ";
			}

			if (!empty($estado)) {
				$whereEstado = " AND po.estado = '$estado' ";
			}
			
			if (!empty($regiao)) {
				$joinRegiao = " JOIN tbl_estado e ON e.estado = po.estado ";
				$whereRegiao = " AND e.regiao = '$regiao' ";
			}

			$between 		= " between '{$data_inicial}' and '{$data_final}' ";

			if (empty($data_inicial) && empty($data_final)) {
				$between = " between current_date - interval '{$periodo} Days' and current_date ";
			} else {
				if (empty($data_inicial)) {
					$between = " between to_date('{$data_final}', 'YYYY-MM-DD') - interval '{$periodo} Days' and to_date('{$data_final}', 'YYYY-MM-DD') ";
				} 
				if (empty($data_final)) {
					$between = " between to_date('{$data_inicial}', 'YYYY-MM-DD') and to_date('{$data_inicial}', 'YYYY-MM-DD') + interval '{$periodo} Days' ";
				}				
			}

			switch($faixa) {
				case '5':
					$whereFaixa = ' where dias_conserto <= 5 ';
					break;
				case '6-15':
					$whereFaixa = ' where  dias_conserto BETWEEN 6 AND 15 ';
					break;
				case '16-30':
					$whereFaixa = ' where  dias_conserto BETWEEN 16 AND 30 ';
					break;
				case '31':
					$whereFaixa = ' where  dias_conserto > 30';
					break;
			}

			$sqlPrimeira = "SELECT
								JSON_AGG(DISTINCT(
									posto,
									estado,
									status_descricao,
									tmc,
									total_os,
									cinco_dias,
									quinze_dias,
									trinta_dias,
									resto,
									status_checkpoint,
									cor
								)) AS por_status,
								JSON_AGG(por_os) AS por_os,
								posto,
								nome_posto,
								estado,
								referencia_posto,
								ROUND(SUM(tmc) / count(status_descricao)) AS tmc,
								SUM(total_os) AS total_os,
								SUM(cinco_dias) AS cinco,
								SUM(quinze_dias) AS quinze,
								SUM(trinta_dias) AS trinta,
								SUM(resto) AS acima
							FROM (
								SELECT
									JSON_AGG(por_os) AS por_os,
									p.posto ,
									p.nome AS nome_posto,
									estado,
									status_descricao,
									referencia_posto,
									status_checkpoint,
									cor,
									ROUND(SUM(dias_conserto) / SUM(total_os)) AS tmc,
									SUM(total_os) AS total_os,
									SUM(cinco_dias) AS cinco_dias,
									SUM(quinze_dias) AS quinze_dias,
									SUM(trinta_dias) AS trinta_dias,
									SUM(resto) AS resto
								FROM (
									SELECT
										JSON_AGG(DISTINCT(
										posto,
										os,
										faturamento_rastreio,
										faturamento_previsao,
										faturamento_emissao,
										dias_aberto,
										status_descricao,
										pedido_data::DATE,										
										pedido,
										data_abertura::DATE,
										cor)) AS por_os,
										posto,
										dias_conserto,
										status_descricao,
										referencia_posto,
										status_checkpoint,
										cor,
										SUM(dias_aberto) AS dias_aberto,
										COUNT(os) AS total_os,
										CASE WHEN dias_conserto <= 5 THEN COUNT(os) ELSE 0 END AS cinco_dias,
										CASE WHEN dias_conserto BETWEEN 6 AND 15 THEN COUNT(os) ELSE 0 END AS quinze_dias,
										CASE WHEN dias_conserto BETWEEN 16 AND 30 THEN COUNT(os) ELSE 0 END AS trinta_dias,
										CASE WHEN dias_conserto > 30 THEN COUNT(os) ELSE 0 END AS resto
									FROM (
										SELECT DISTINCT
											o.posto,
											o.sua_os as os,
											sc.descricao AS status_descricao,
											f.conhecimento AS faturamento_rastreio,
											f.previsao_chegada AS faturamento_previsao,
											f.emissao AS faturamento_emissao,
											p.data AS pedido_data,
											p.pedido AS pedido,
											sc.status_checkpoint,
											sc.cor,
											pf.codigo_posto AS referencia_posto,
											o.data_abertura AS data_abertura,
											COALESCE(COALESCE(o.data_conserto::DATE, CURRENT_DATE) - o.data_abertura, 0) AS dias_conserto,
											COALESCE(COALESCE(o.data_fechamento, CURRENT_DATE )- o.data_abertura, 0) AS dias_aberto
										FROM tbl_os o
										JOIN tbl_status_checkpoint sc USING(status_checkpoint)
										LEFT JOIN tbl_os_produto op USING(os)
										LEFT JOIN tbl_os_item oi USING(os_produto)
										JOIN tbl_posto po USING(posto)
										JOIN tbl_posto_fabrica pf ON po.posto = pf.posto AND pf.fabrica = $login_fabrica
										LEFT JOIN tbl_faturamento_item fi ON fi.pedido = oi.pedido AND fi.pedido_item = oi.pedido_item
										LEFT JOIN tbl_faturamento f USING(faturamento)
										LEFT JOIN tbl_pedido p ON p.pedido = fi.pedido AND p.fabrica = $login_fabrica
										$joinRegiao
										WHERE o.data_abertura $between
										AND o.fabrica = $login_fabrica
										AND o.excluida IS NOT TRUE
										AND o.posto != 6359
										$whereStatus
										$whereRegiao
										$whereEstado										
										$wherePosto
									) x
									GROUP BY posto, dias_conserto, status_descricao, referencia_posto,status_checkpoint, cor
									ORDER BY posto, status_checkpoint
								) xx
								JOIN tbl_posto p USING(posto)	
								$whereFaixa
							GROUP BY p.posto, estado, status_descricao,nome_posto, referencia_posto, status_checkpoint, cor
							ORDER BY status_checkpoint
							) xxx
							GROUP BY posto, estado, nome_posto, referencia_posto
							order by nome_posto;";
			$resPrimeira = pg_query($con, $sqlPrimeira);
			while ($primeira = pg_fetch_object($resPrimeira)) { 				
				$por_status = json_decode(utf8_encode($primeira->por_status), 1);
				usort($por_status, 'cmp');
				$primeira->por_status = array_map(function($input){
					return [
						'posto' => $input['f1'],
						'estado' => $input['f2'],
						'status_descricao' => utf8_decode($input['f3']),
						'tmc' => $input['f4'],
						'total_os' => $input['f5'],
						'cinco' => $input['f6'],
						'quinze' => $input['f7'],
						'trinta' => $input['f8'],
						'acima' => $input['f9'],
						'status_checkpoint' => $input['f10'],
						'cor' => $input['f11']
					];	
				}, $por_status);				
				$por_os_original = json_decode(utf8_encode($primeira->por_os), 1);
				$por_os = [];
				foreach ($por_os_original as $value) {
					foreach ($value as $key1 => $value1) {
						$por_os = array_merge($por_os, $value1);
					}
				}
				$primeira->por_os = array_map(function($input){
					return [
						'posto' => $input['f1'],
						'os' => $input['f2'],
						'faturamento_rastreio' => $input['f3'],
						'faturamento_previsao' => $input['f4'],
						'faturamento_emissao' => $input['f5'] ? date_create($input['f5']) : '',
						'dias_aberto' => $input['f6'],
						'status_descricao' => utf8_decode($input['f7']),
						'pedido_data' => $input['f8'] ? date_create($input['f8']) : '',
						'pedido' => $input['f9'],
						'data_abertura' => $input['f10'] ? date_create($input['f10']) : '',
						'cor' => $input['f11'],
					];	
				}, $por_os);

			?>
			<tr class="item_posto" data-posto="<?=$primeira->posto?>">
				<td><input type="checkbox" class="selecionar_os_posto" value="<?=$primeira->referencia_posto?>"/></td>
				<td colspan="3"><div onclick="abre(<?=$primeira->posto?>)" id="maismenos_<?=$primeira->posto?>" style="cursor: pointer;">+</div> <?=$primeira->nome_posto?></td>
				<td><?=$primeira->estado?></td>
				<td><?=$primeira->tmc?></td>
				<td><?=$primeira->cinco?></td>
				<td><?=$primeira->quinze?></td>
				<td><?=$primeira->trinta?></td>
				<td><?=$primeira->acima?></td>
				<td><?=$primeira->total_os?></td>
				<td><a href="helpdesk_posto_autorizado_novo_atendimento.php?solicitacao=visaoOS&posto=<?=$primeira->referencia_posto?>" target='_blank'>Abrir</a></td>
			</tr>
			<thead>
			<tr class='titulo_coluna titulo_status' data-posto="<?=$primeira->posto?>">
				<th></th>
				<th colspan="5">Status OS</th>
				<th>Entre 1 e 5</th>
				<th>Entre 6 e 15</th>
				<th>Entre 16 e 30</th>
				<th>Acima de 30</th>
				<th>Total geral</th>
				<th></th>
			</tr>
			</thead>
			<?php foreach($primeira->por_status as $status){ ?>
				<tr class="item_status" data-posto="<?=$primeira->posto?>" data-posto="<?=$primeira->posto?>" data-status="<?=$status['status_descricao']?>">
					<td> <div class="status_checkpoint" style="background-color:<?=$status['cor']?>">&nbsp;</div></td>
					<td colspan="5"><?=$status['status_descricao']?></td>
					<td><?=$status['cinco']?></td>
					<td><?=$status['quinze']?></td>
					<td><?=$status['trinta']?></td>
					<td><?=$status['acima']?></td>
					<td><?=$status['total_os']?></td>	
					<td></td>			
				</tr>
			<?php } ?>
			<thead>
			<tr class='titulo_coluna titulo_os' data-posto="<?=$primeira->posto?>">
				<th></th>
				<th>OS</th>
				<th>Data OS</th>
				<th>Data Pedido</th>
				<th>Pedido</th>
				<th>Data Emissão</th>
				<th>Rastreamento</th>
				<th>Entre 1 e 5</th>
				<th>Entre 6 e 15</th>
				<th>Entre 16 e 30</th>
				<th>Acima de 30</th>				
				<th></th>
			</tr>
			</thead>
			<?php foreach($primeira->por_os as $os){ ?>
				<tr class="item_os" data-posto="<?=$primeira->posto?>" data-status="<?=$os['status_descricao']?>">
					<td>
						<input type="checkbox" name="selecionar[<?=$primeira->referencia_posto?>][]" style="display: none;" data-osposto="<?=$primeira->referencia_posto?>" value="<?=$os['os']?>"/>
						<div class="status_checkpoint" style="background-color:<?=$os['cor']?>">&nbsp;</div>
					</td>
					<td><?=$os['os']?></td>
					<td><? echo $os['data_abertura'] ? date_format($os['data_abertura'], "d/m/Y") : '' ; ?></td>
					<td><? echo $os['pedido_data'] ? date_format($os['pedido_data'], "d/m/Y") : '' ; ?></td>
					<td><?=$os['pedido']?></td>
					<td><? echo $os['faturamento_emissao'] ? date_format($os['faturamento_emissao'], "d/m/Y") : ''; ?></td>
					<td><?=$os['faturamento_rastreio']?></td>
					<?php 
					$i = 0;
					$dias = $os['dias_aberto'];
					while ($i < 4)  {
						if ($i == 0 && $dias <= 5) {
							echo "<td>1</td>";
						} elseif ($i == 1 && $dias <= 15 && $dias > 5) {
							echo "<td>1</td>";
						} elseif ($i == 2 && $dias <= 30 && $dias > 15) {
							echo "<td>1</td>";
						} elseif ($i == 3 && $dias > 30) {
							echo "<td>1</td>";
						} else {
							echo "<td></td>";
						}
						$i++;
					} ?>
				</tr>
			<?php } ?>
		<?php } ?>	
		<thead>
			<tr class='titulo_coluna'>
				<td colspan="12">
					<input type="hidden" name="tipo" value="Comunicado">
					<input class="btn btn-secundary" type="submit" name="enviar_email" id="enviar_email" value="Enviar e-mail">
				</td>
			</tr>
		</thead>	
		</tbody>
	</table>
	</form>
</body>
</html>
<script type="text/javascript">
	$(function(){
		allHide();
	})
	function abre(posto){
		$('#maismenos_'+posto+'').attr('onclick','fecha('+posto+')');
		$('.titulo_status[data-posto='+posto+']').show();
		$('.item_status[data-posto='+posto+']').show();
		$('#maismenos_'+posto+'').html('-');
	}
	function fecha(posto){
		allHide()
		$('#maismenos_'+posto+'').attr('onclick','abre('+posto+')');
		$('#maismenos_'+posto+'').html('+');
	}
	$(".item_status").click(function(){
		osHide();
		var posto = $(this).data('posto');
		var status = $(this).data('status');
		$('.titulo_os[data-posto='+posto+']').show();
		$('.item_os[data-posto='+posto+'][data-status="'+status+'"]').show();
	});
	function allHide(){
		$('.titulo_os').hide();
		$('.item_os').hide();
		$('.titulo_status').hide();
		$('.item_status').hide();
	}
	function osHide(){
		$('.item_os').hide();
	}
</script>
