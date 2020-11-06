<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao       = $_POST['btn_acao'];


if($btn_acao == "submit"){
	$data_inicial		= $_POST["data_inicial"];
	$data_final			= $_POST["data_final"];
	$atendente          = $_POST["atendente"];

	# Validações
	if ((!strlen($data_inicial) || !strlen($data_final)) && !strlen($hd_chamado)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "data";
	}


	if(!count($msg_erro["msg"])){

		if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

			list($d,$m,$y) = explode("/", $data_inicial);

			if(!checkdate($m, $d, $y)){
				$msg_erro['msg'] = "Data inicial inválida";
			}else{
				$data_inicial_formatada = "$y-$m-$d 00:00:00";
			}


			list($d,$m,$y) = explode("/", $data_final);

			if(!checkdate($m, $d, $y)){
				$msg_erro['msg'] = "Data final inválida";
			}else{
				$data_final_formatada = "$y-$m-$d 23:59:59";
			}

		}

	}

	if (!empty($atendente)) {

		$cond_atendente_resolvido   = " AND HDI.admin = $atendente";
		$cond_atendente_protocolo 	= "AND (
										SELECT COUNT(1) FROM tbl_hd_chamado_item
										WHERE tbl_hd_chamado_item.hd_chamado = HD.hd_chamado
										AND tbl_hd_chamado_item.admin = $atendente
									) > 0";
		$cond_atendente_interacoes  = " AND HDI.admin = $atendente";
	}

	if(count($msg_erro['msg']) == 0){

		$sqlConsulta = "CREATE TEMP TABLE IF NOT EXISTS marketplace_data (
							origem TEXT,
							dia DATE,
							motivo TEXT,
							protocolos INTEGER,
							interacoes  INTEGER,
							resolvidos INTEGER,
							 media NUMERIC(10, 2) -- de tempo de resposta
						);


							DELETE FROM marketplace_data;
							WITH 
							dias AS (
								SELECT O.descricao AS origem, d.dia, a.descricao AS clas
								FROM tbl_hd_chamado_origem AS O
								LEFT JOIN (
								SELECT DISTINCT descricao
								FROM tbl_hd_classificacao AS cl
								WHERE cl.fabrica = {$login_fabrica}
							) a ON TRUE
							LEFT JOIN (
								SELECT dia::DATE
								FROM GENERATE_SERIES('$data_inicial_formatada'::DATE, '$data_final_formatada'::DATE, '1 DAY') AS dia
							) d ON TRUE
							WHERE O.fabrica = {$login_fabrica}
							ORDER BY O.descricao, d.dia, a.descricao
							),
							protocolos AS (
								SELECT HD.data::DATE AS dia, X.origem, HDC.descricao AS clas, COUNT(HD.status) AS total
								FROM tbl_hd_chamado HD
								JOIN tbl_hd_classificacao HDC USING (hd_classificacao)
								JOIN tbl_hd_chamado_extra X USING (hd_chamado)
								WHERE fabrica_responsavel = {$login_fabrica}
								AND data BETWEEN '$data_inicial_formatada' and '$data_final_formatada'
								AND status = 'Aberto'
								{$cond_atendente_protocolo}
								GROUP BY X.origem, HD.data::DATE, HDC.descricao
							),
							resolvidos AS (
							SELECT HDI.data::DATE AS dia, X.origem, HDC.descricao AS clas, 
								COUNT(DISTINCT hd_chamado) AS total,
								( AVG(EXTRACT( EPOCH FROM (
													   SELECT DHDI.data
                                                       FROM tbl_hd_chamado_item DHDI
                                                       WHERE DHDI.hd_chamado = HDI.hd_chamado
                                                       AND DHDI.status_item = 'Resolvido'
                                                       ORDER BY DHDI.data DESC
                                                       LIMIT 1) - HD.data
												)) / 3600) AS tma
								FROM tbl_hd_chamado_item HDI
								JOIN tbl_hd_chamado HD USING (hd_chamado)
								JOIN tbl_hd_chamado_extra X USING (hd_chamado)
								JOIN tbl_hd_classificacao HDC USING (hd_classificacao)
								WHERE HD.fabrica_responsavel = {$login_fabrica}
								AND HDI.status_item = 'Resolvido'
								AND HDI.data BETWEEN '$data_inicial_formatada' and '$data_final_formatada'
								{$cond_atendente_resolvido}
								GROUP BY X.origem, HDI.data::DATE, HDC.descricao
							),
							interacoes AS (
								SELECT HDI.data::DATE AS dia, X.origem, HDC.descricao AS clas, 
								COUNT(HDI.status_item) AS total, 
								SUM(HD.data_resolvido - HD.data) AS duracao
								FROM tbl_hd_chamado_item HDI
								JOIN tbl_hd_chamado HD USING (hd_chamado)
								JOIN tbl_hd_chamado_extra X USING (hd_chamado)
								JOIN tbl_hd_classificacao HDC USING (hd_classificacao)
								WHERE HD.fabrica_responsavel = {$login_fabrica}
								AND HDI.data BETWEEN '$data_inicial_formatada' and '$data_final_formatada'
								{$cond_atendente_interacoes}
								GROUP BY X.origem, HDI.data::DATE, HDC.descricao
							)
							INSERT INTO marketplace_data (
								SELECT
								dias.origem,
								dias.dia,
								dias.clas AS classificacao,
								(
									SELECT total FROM protocolos 
									WHERE protocolos.dia = dias.dia 
									AND protocolos.clas = dias.clas
									AND protocolos.origem = dias.origem
								) AS protocolos,
								(
									SELECT total FROM interacoes 
									WHERE interacoes.dia = dias.dia 
									AND interacoes.clas = dias.clas
									AND interacoes.origem = dias.origem
								) AS interacoes,
								(
									SELECT total FROM resolvidos
									WHERE resolvidos.dia = dias.dia 
									AND resolvidos.clas = dias.clas
									AND resolvidos.origem = dias.origem
								) AS resolvidos,
								(
									SELECT tma FROM resolvidos
									WHERE resolvidos.dia = dias.dia
									AND resolvidos.clas = dias.clas
									AND resolvidos.origem = dias.origem
								) AS tma
								FROM
								dias
							);

							DELETE FROM marketplace_data 
							WHERE protocolos IS NULL
							AND interacoes IS NULL
							AND resolvidos IS NULL;
						";
		$resConsulta = pg_query($con,$sqlConsulta);
		
		$sqlConsulta = "SELECT origem,
							   dia,
							   motivo,
							   COALESCE(protocolos, 0) protocolos,
							   COALESCE(interacoes, 0) interacoes,
							   COALESCE(resolvidos, 0) resolvidos,
							   COALESCE(media, 0) media
						FROM marketplace_data
						ORDER BY dia ASC";
		$resSubmit   = pg_query($con, $sqlConsulta);

	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {

			$data = date("d-m-Y-H:i");

			$tipo_excel = $_POST['tipo_excel'];

			$fileName = "relatorio_historico-atendimentos-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");

			$thead = "
			<table align='center' id='resultado_os' class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Classificação</th>
						<th>Origem</th>
						<th>Dia</th>
						<th>Protocolos</th>
						<th>Interações</th>
						<th>Resolvidos</th>
						<th>TMA</th>
            		</tr>
                </thead>
				<tbody>
			";
			fwrite($file, $thead);

			for($j = 0; $j < pg_num_rows($resSubmit); $j++){

					$classificacao 		= pg_fetch_result($resSubmit,$j,'motivo');
					$origem 		    = pg_fetch_result($resSubmit,$j,'origem');
					$qtde_protocolo     = pg_fetch_result($resSubmit,$j,'protocolos');
					$qtde_interacoes    = pg_fetch_result($resSubmit,$j,'interacoes');
					$resolvidos         = pg_fetch_result($resSubmit,$j,'resolvidos');
					$tma    			= pg_fetch_result($resSubmit,$j,'media');
					$dia                = pg_fetch_result($resSubmit,$j,'dia');

					$tbody .= "<tr>
								<td>$classificacao</td>
								<td>$origem</td>
								<td>".mostra_data($dia)."</td>
								<td nowrap class='tac'>$qtde_protocolo</td>
								<td class='tac'>$qtde_interacoes</td>
								<td>$resolvidos</td>
								<td class='tac'>$tma</td>
							   </tr>";

			}

			fwrite($file, $tbody);
			fwrite($file, "
					</tbody>
				</table>
			");


			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}

		}

		exit;
	}
}

$layout_menu = "callcenter";
$title = "RELATÓRIO SLA CALLCENTER";

include_once "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "mask",
    "dataTable"
);

include("plugin_loader.php");

?>

	<script>
		$(function(){

			Shadowbox.init();

			$.datepickerLoad(Array("data_final", "data_inicial"));

			$('#data_inicial').mask("99/99/9999");
			$('#data_final').mask("99/99/9999");

			$("span[rel=descricao_posto],span[rel=codigo_posto],span[rel=produto_referencia],span[rel=produto_descricao]").click(function () {
				$.lupa($(this));
			});

			$('.detalhes_protocolos').click(function(){

				var tipo = $(this).data("tipo");
				var data_inicial = $("#data_inicial").val();
				var data_final   = $("#data_final").val();

				Shadowbox.open({
	                content: "protocolos_relatorio_sla.php?tipo="+tipo+"&data_inicial="+data_inicial+"&data_final="+data_final,
	                player: "iframe",
	                width: 900,
	                height: 450
	            });

			});
			
		});

		function retorna_posto(retorno){
	        $("#codigo_posto").val(retorno.codigo);
			$("#descricao_posto").val(retorno.nome);
	    }

	    function retorna_produto (retorno) {
			$("#produto_referencia").val(retorno.referencia);
			$("#produto_descricao").val(retorno.descricao);
		}
	</script>

	<div class="container">

		<?php
		/* Erro */
		if (count($msg_erro["msg"]) > 0) {
		?>
			<div class="alert alert-error">
				<h4><?php echo implode("<br />", $msg_erro["msg"])?></h4>
			</div>
		<?php } ?>

		<div class="container">
			<strong class="obrigatorio pull-right"> * Campos obrigatórios </strong>
		</div>

		<form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

			<div class='titulo_tabela'>Parâmetros de Pesquisa</div> <br/>

			<div class='row-fluid'>
				<div class='span2'></div>
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
						<label class='control-label' for='data_final'>Data Final</label>
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
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='linha'>Atendentes</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<select name="atendente" id="atendente">
									<option value=""></option>
									<?php
									$sql = "SELECT admin,nome_completo
											FROM tbl_admin
											WHERE fabrica = $login_fabrica
											AND ativo IS TRUE";
									$res = pg_query($con,$sql);

									foreach (pg_fetch_all($res) as $key) {
										$selected_atendente = (($atendente == $key['admin']) ) ? "SELECTED" : '' ;

									?>
										<option value="<?php echo $key['admin']?>" <?php echo $selected_atendente ?> >

											<?php echo $key['nome_completo'] ?>

										</option>
									<?php
									}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<p>
				<br/>
				<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p>

			<br/>

		</form>

	</div>
</div>
<div class="container" style="width: 90%;">
        <?php
          if (pg_num_rows($resSubmit) > 0) {

			$count = pg_num_rows($resSubmit);

			?>
			<table align="center" id="resultado_callcenter" class='table table-striped table-bordered table-hover table-fixed' >
				<thead>
					<tr>
						<th colspan="7" class="titulo_tabela">Relatório agrupado por classificação/origem <?= $nome_atendente ?> <?= $data_inicial ?> - <?= $data_final?></th>
					</tr>
					<tr class='titulo_coluna' >
						<th>Classificação</th>
						<th>Origem</th>
						<th class="date_column">Dia</th>
						<th>Protocolos Abertos</th>
						<th>Interações</th>
						<th>Resolvidos</th>
						<th>Tempo Médio para interação (horas)</th>
            		</tr>
                </thead>
				<tbody>
				<?php

				for($i = 0; $i < pg_num_rows($resSubmit); $i++){

					$classificacao 		= pg_fetch_result($resSubmit,$i,'motivo');
					$origem 		    = pg_fetch_result($resSubmit,$i,'origem');
					$qtde_protocolo     = pg_fetch_result($resSubmit,$i,'protocolos');
					$qtde_interacoes    = pg_fetch_result($resSubmit,$i,'interacoes');
					$resolvidos         = pg_fetch_result($resSubmit,$i,'resolvidos');
					$tma    			= pg_fetch_result($resSubmit,$i,'media');
					$dia                = pg_fetch_result($resSubmit,$i,'dia');

					echo "<tr>
							<td>$classificacao</td>
							<td>$origem</td>
							<td class='tac'>".mostra_data($dia)."</td>
							<td nowrap class='tac'>$qtde_protocolo</td>
							<td class='tac'>$qtde_interacoes</td>
							<td class='tac'>$resolvidos</td>
							<td class='tac'>$tma</td>
						   </tr>";

				} ?>
				</tbody>
			</table>
				<?php

				?>

					<script>
						var tds = $('#resultado_callcenter').find(".titulo_coluna");

        				var colunas = [];

				        $(tds).find("th").each(function(){
				            if ($(this).attr("class") == "date_column") {
				                colunas.push({"sType":"date"});
				            } else if ($(this).attr("class") == "money_column") {
				                colunas.push({"sType":"numeric"});
				            } else {
				                colunas.push(null);
				            }
				        });

						$.dataTableLoad({ table: "#resultado_callcenter",aoColumns:colunas });
					</script>

				<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div class="btn_excel gerar_excel">
				<input type="hidden" class="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>

		<?php
		}else if ($btn_acao == "submit") { ?>
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
		<?php
		}
		?>
</div>
<?php
/* Rodapé */
	include 'rodape.php';
?>
