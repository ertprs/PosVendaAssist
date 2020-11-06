<?php

	/* Relatorio de Posto x Natureza */

	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	$admin_privilegios = "*";
	include "autentica_admin.php";
	include "funcoes.php";

	function formatDataAbertura($data){

		list($ano, $mes, $dia) = explode("-", $data);
		return $dia."/".$mes."/".$ano;

	}

	function formatDataDigitacao($data){

		list($parte1, $parte2) = explode(" ", $data);
		list($ano, $mes, $dia) = explode("-", $parte1);
		return $dia."/".$mes."/".$ano;

	}

	if ($_POST["btn_acao"] == "submit") {

		$data_inicial       	= $_POST['data_inicial'];
		$data_final         	= $_POST['data_final'];
		$posto					= $_POST['posto'];
		$estado					= $_POST['estado'];

		/* Validação datas */
		if(!strlen($data_inicial) or !strlen($data_final)){
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
		} else {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);

			if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
				$msg_erro["msg"][]    = "Data Inválida";
				$msg_erro["campos"][] = "data";
			} else {
				$aux_data_inicial = "{$yi}-{$mi}-{$di}";
				$aux_data_final   = "{$yf}-{$mf}-{$df}";

				if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
					$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
					$msg_erro["campos"][] = "data";
				}
				
				$sqlX = "SELECT '$aux_data_inicial'::date + interval '12 months' > '$aux_data_final'";
				$resSubmitX = pg_query($con,$sqlX);
				$periodo_6meses = pg_fetch_result($resSubmitX,0,0);
				if($periodo_6meses == 'f'){
					$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo 12 meses";
				}
			}
		}

		if (!count($msg_erro["msg"])) {


			$cond_posto 	= (strlen($posto) > 0) ? " AND tbl_posto_fabrica.codigo_posto = '$posto' " : "";
			$cond_estado 	= (strlen($estado) > 0) ? " AND tbl_posto_fabrica.contato_estado = '$estado' " : "";

			$sql = "
				SELECT 
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_estado,
					tbl_os.os,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_estado,
					tbl_os.data_abertura,
					tbl_os.data_digitacao,
					tbl_os.qtde_km,
					tbl_os.pedagio,
					tbl_os_extra.extrato 
				FROM tbl_os 
				JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os 
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto 
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
				WHERE tbl_os.finalizada IS NOT NULL 
					AND tbl_os.fabrica = $login_fabrica 
					AND tbl_os.qtde_km > 0 
					AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' 
					$cond_posto 
					$cond_estado 
			";
			$resSubmit = pg_query($con, $sql);

		}

		if(isset($_POST['gerar_excel'])){

			$data = date("d-m-Y-H:i");

			$filename = "relatorio-os-{$data}.xls";

			$file = fopen("/tmp/{$filename}", "w");

			fwrite($file, "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='12' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE POSTO OS X KM
							</th>
						</tr>
						<tr>

							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>UF Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>UF OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Abertura OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Atendimento OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>KM OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedágio</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Extrato</th>

						</tr>
					</thead>
					<tbody>
			");

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {

				$posto 				= pg_fetch_result($resSubmit, $i, 'codigo_posto');
				$nome 				= pg_fetch_result($resSubmit, $i, 'nome');
				$cidade_posto 		= pg_fetch_result($resSubmit, $i, 'contato_cidade');
				$cidade_estado 		= pg_fetch_result($resSubmit, $i, 'contato_estado');
				$os 				= pg_fetch_result($resSubmit, $i, 'os');
				$consumidor_cidade 	= pg_fetch_result($resSubmit, $i, 'consumidor_cidade');
				$consumidor_estado 	= pg_fetch_result($resSubmit, $i, 'consumidor_estado');
				$data_abertura 		= pg_fetch_result($resSubmit, $i, 'data_abertura');
				$data_digitacao 	= pg_fetch_result($resSubmit, $i, 'data_digitacao');
				$qtde_km 			= pg_fetch_result($resSubmit, $i, 'qtde_km');
				$pedagio 			= pg_fetch_result($resSubmit, $i, 'pedagio');
				$extrato 			= pg_fetch_result($resSubmit, $i, 'extrato');

				$pedagio 		= "R$ ".number_format($pedagio, 2, ",", ".");
				$data_abertura 	= formatDataAbertura($data_abertura);
				$data_digitacao = formatDataDigitacao($data_digitacao);

				fwrite($file, "
					<tr class='tac' style='text-align:center'>

						<td>$posto</td>
						<td>$nome</td>
						<td>$cidade_posto</td>
						<td>$cidade_estado</td>
						<td>$os</td>
						<td>$consumidor_cidade</td>
						<td>$consumidor_estado</td>
						<td>$data_abertura</td>
						<td>$data_digitacao</td>
						<td>$qtde_km KM</td>
						<td>$pedagio</td>
						<td>$extrato</td>

					</tr>"
				);
			}

			fwrite($file, "
						<tr>
							<th colspan='12' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
						</tr>
					</tbody>
				</table>
			");

			// fwrite($file, $conteudo);

			fclose($file);

			if (file_exists("/tmp/{$filename}")) {
				system("mv /tmp/{$filename} xls/{$filename}");

				echo "xls/{$filename}";
			}

			exit;
		}

	}

	/* ---------------------- */
	
	$layout_menu 	= "gerencia";
	$title 			= "RELATÓRIO DE POSTO OS X KM";
	
	include 'cabecalho_new.php';
	
	$plugins = array(
		"mask",
		"datepicker",
		"shadowbox",
		"dataTable",
	);

	include "plugin_loader.php";

	$estados = array(
				"AC" => "Acre",			
				"AL" => "Alagoas",			
				"AM" => "Amazonas",
				"AP" => "Amapá",			
				"BA" => "Bahia",			
				"CE" => "Ceará",
				"DF" => "Distrito Federal",
				"ES" => "Espírito Santo",	
				"GO" => "Goiás",
				"MA" => "Maranhão",		
				"MG" => "Minas Gerais",		
				"MS" => "Mato Grosso do Sul",
				"MT" => "Mato Grosso",		
				"PA" => "Pará",				
				"PB" => "Paraíba",
				"PE" => "Pernambuco",		
				"PI" => "Piauí",			
				"PR" => "Paraná",
				"RJ" => "Rio de Janeiro",	
				"RN" => "Rio Grande do Norte",
				"RO" => "Rondônia",
				"RR" => "Roraima",			
				"RS" => "Rio Grande do Sul",
				"SC" => "Santa Catarina",
				"SE" => "Sergipe",			
				"SP" => "São Paulo",		
				"TO" => "Tocantins"
			);

	$form = array(
		"data_inicial" => array(
			"span"      => 4,
			"label"     => "Data Início",
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"maxlength" => 10
		),
		"data_final" => array(
			"span"      => 4,
			"label"     => "Data Final",
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"maxlength" => 10
		),
		"posto" => array(
			"span"      => 4,
			"label"     => "Código Posto",
			"type"      => "input/text",
			"width"     => 10,
			"lupa"		=> array(
					"name"   	=> "codigo_posto",
					"tipo"   	=> "posto",
					"parametro" => "codigo"
				)
		),
		"descricao_posto" => array(
			"span"      => 4,
			"label"     => "Posto",
			"type"      => "input/text",
			"width"     => 10,
			"lupa"		=> array(
					"name"   	=> "descricao_posto",
					"tipo"   	=> "posto",
					"parametro" => "nome"
				)
		),
		"estado" => array(
			"span"      => 4,
			"label"     => "Estado",
			"type"      => "select",
			"width"     => 10,
			"options"   => $estados
		)
	);

?>

	<script>
		$(function(){
		
			Shadowbox.init();
		
			$.datepickerLoad(Array("data_final", "data_inicial"));

			$('#data_inicial').mask("99/99/9999");
			$('#data_final').mask("99/99/9999");

			$("span[rel=descricao_posto").click(function () {
				$.lupa($(this));
			});

			$("span[rel=codigo_posto").click(function () {
				$.lupa($(this));
			});

		});

		function retorna_posto(retorno){
	        $("#posto").val(retorno.codigo);
			$("#descricao_posto").val(retorno.nome);
	    }
		
		function listarChamados(params){
		
			Shadowbox.open({
				content: "listar_atendimento_posto_natureza.php?params="+JSON.stringify(params),
				player: "iframe",
				width: 1280,
				height: 800
			});
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
			
			<? echo montaForm($form,null);?>

			<p>
				<br/>
				<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p>
			
			<br/>
			
		</form>
		
	</div>

</div>
	
	<?php

	if(isset($resSubmit)){

		if (pg_num_rows($resSubmit) > 0) {

			if (pg_num_rows($resSubmit) > 500) {
				$count = 500;
			?>
				<div id='registro_max'>
					<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
				</div>
			<?php
			} else {
				$count = pg_num_rows($resSubmit);
			}

			echo "<div class='tal' style='padding-rigth: 5px !important; width: 1050px; margin: 0 auto;'>";

				echo "
					<table id='resultado' class=\"table table-striped table-bordered table-hover table-fixed\" style='margin: 0 auto;'>
						<thead>
							<tr class='titulo_coluna'>
								<th>Código Posto</th>
								<th>Nome</th>
								<th>Cidade Posto</th>
								<th>UF Posto</th>
								<th>OS</th>
								<th>Cidade OS</th>
								<th>UF OS</th>
								<th>Data Abertura OS</th>
								<th>Data Atendimento OS</th>
								<th>KM OS</th>
								<th>Pedágio</th>
								<th>Extrato</th>
							</tr>
						</thead>
						<tbody>
				";

				for ($i = 0; $i < $count; $i++) {

					$posto 				= pg_fetch_result($resSubmit, $i, 'codigo_posto');
					$nome 				= pg_fetch_result($resSubmit, $i, 'nome');
					$cidade_posto 		= pg_fetch_result($resSubmit, $i, 'contato_cidade');
					$cidade_estado 		= pg_fetch_result($resSubmit, $i, 'contato_estado');
					$os 				= pg_fetch_result($resSubmit, $i, 'os');
					$consumidor_cidade 	= pg_fetch_result($resSubmit, $i, 'consumidor_cidade');
					$consumidor_estado 	= pg_fetch_result($resSubmit, $i, 'consumidor_estado');
					$data_abertura 		= pg_fetch_result($resSubmit, $i, 'data_abertura');
					$data_digitacao 	= pg_fetch_result($resSubmit, $i, 'data_digitacao');
					$qtde_km 			= pg_fetch_result($resSubmit, $i, 'qtde_km');
					$pedagio 			= pg_fetch_result($resSubmit, $i, 'pedagio');
					$extrato 			= pg_fetch_result($resSubmit, $i, 'extrato');

					$pedagio = "R$ ".number_format($pedagio, 2, ",", ".");

					if(strlen($extrato) > 0){
						$extrato = "<a href='extrato_consulta_os.php?extrato={$extrato}' target='_blank'>{$extrato}</a>";
					}

					$os = "<a href='os_press.php?os={$os}' target='_blank'>{$os}</a>";

					$data_abertura 	= formatDataAbertura($data_abertura);
					$data_digitacao = formatDataDigitacao($data_digitacao);

					echo "<tr style='text-align:center'>
						<td>$posto</td>
						<td>$nome</td>
						<td>$cidade_posto</td>
						<td>$cidade_estado</td>
						<td>$os</td>
						<td>$consumidor_cidade</td>
						<td>$consumidor_estado</td>
						<td>$data_abertura</td>
						<td>$data_digitacao</td>
						<td>$qtde_km KM</td>
						<td>$pedagio</td>
						<td>$extrato</td>
					</tr>";
				}

				echo "
						</tbody>

					</table>
				";

				echo "<br />";

				if($count > 50){
					?>
						<script>
							$.dataTableLoad({ table: "#resultado" });
						</script>
					<?php
				}

				$jsonPOST = excelPostToJson($_POST);

				?>

				<br />

				<div id='gerar_excel' class="btn_excel">
					<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
					<span><img src='imagens/excel.png' /></span>
					<span class="txt">Gerar Arquivo Excel</span>
				</div>

				<?php

			echo "</div>";

		}else{
			echo '
			<div class="container">
				<div class="alert">
						<h4>Nenhum resultado encontrado</h4>
				</div>
			</div>';
		}

	}

	/* Rodapé */
	include 'rodape.php';

	?>
