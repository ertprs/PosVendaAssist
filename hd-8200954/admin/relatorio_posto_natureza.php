<?php

	/* RelatÃƒÂ³rio de Posto x Natureza */

	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	$admin_privilegios = "";
	include "autentica_admin.php";
	include "funcoes.php";

	function formatCNPJ($string){
	    $output = preg_replace("[' '-./ t]", '', $string);
	    $size = (strlen($output) -2);
	    if ($size != 9 && $size != 12) return false;
	    $mask = '##.###.###/####-##';
	    $index = -1;
	    for ($i = 0; $i < strlen($mask); $i++){
	        if ($mask[$i]=='#') $mask[$i] = $output[++$index];
	    }
	    return $mask;
	}

	
	/* ---------------------- */

	if ($_POST["btn_acao"] == "submit") {

		$data_inicial       	= $_POST['data_inicial'];
		$data_final         	= $_POST['data_final'];
		$tipo_data				= $_POST['tipo_data'];
		$natureza				= $_POST['natureza'];

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

			if ($tipo_data) {
				$cond_status = ($tipo_data == "F") ? " AND tbl_hd_chamado.status = 'Resolvido' " : " AND tbl_hd_chamado.status = 'Aberto' ";
			}

			if(strlen($natureza) > 0){
				$cond_natureza = " AND tbl_hd_chamado.categoria = '$natureza' ";
			}

			$sql = "SELECT DISTINCT 
						tbl_posto.posto,
						tbl_posto.cnpj,
						tbl_posto.nome,
						tbl_hd_chamado.categoria,
						tbl_hd_chamado.status, 
						count(tbl_hd_chamado.hd_chamado) as total_chamados 
					FROM tbl_hd_chamado 
					JOIN tbl_hd_chamado_extra USING(hd_chamado)
					JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
					AND tbl_hd_chamado.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
					$cond_status 
					$cond_natureza 
					GROUP BY tbl_posto.posto, tbl_posto.cnpj, tbl_posto.nome, tbl_hd_chamado.categoria, tbl_hd_chamado.status  
					ORDER BY tbl_posto.posto, tbl_hd_chamado.categoria, tbl_hd_chamado.status
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
							<th colspan='5' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE POSTO X NATUREZA
							</th>
						</tr>
						<tr>

							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Natureza</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Situação</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total Chamados</th>

						</tr>
					</thead>
					<tbody>
			");

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {

				$posto 			= pg_fetch_result($resSubmit,$i,'posto');
				$cnpj 			= formatCNPJ(pg_fetch_result($resSubmit,$i,'cnpj'));
				$nome 			= pg_fetch_result($resSubmit,$i,'nome');
				$natureza 		= pg_fetch_result($resSubmit,$i,'categoria');
				$situacao 		= pg_fetch_result($resSubmit,$i,'status');
				$total_chamados = pg_fetch_result($resSubmit,$i,'total_chamados');

				fwrite($file, "
					<tr class='tac' style='text-align:center'>

						<td>$cnpj</td>
						<td>$nome</td>
						<td>$natureza</td>
						<td>$situacao</td>
						<td>$total_chamados</td>

					</tr>"
				);
			}

			fwrite($file, "
						<tr>
							<th colspan='5' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
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
	$title 			= "RELATÓRIO DE POSTO X NATUREZA";
	
	include 'cabecalho_new.php';
	
	$plugins = array(
		"mask",
		"datepicker",
		"shadowbox",
	);

	include "plugin_loader.php";
	
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
		"tipo_data" => array(
			"span"      => 4,
			"label"     => "Situação",
			"type"      => "radio",
			"radios"  => array(
				""  => "Ambos",
				"A" => "Aberto",
				"F" => "Fechado",
			)
		),
		"natureza" => array(
			"span"      => 4,
			"label"     => "Natureza",
			"type"      => "select",
			"options"  => array(
				"reclamacao_produto" 	=> "Reclamação",
				"reclamacao_empresa" 	=> "Recl. Empresa",
				"reclamacao_at" 		=> "Reclamação A.T.",
				"duvida_produto" 		=> "Dúvida Produto",
				"sugestao" 				=> "Sugestão",
				"procon" 				=> "Procon/Judicial",
				"onde_comprar" 			=> "Onde Comprar",
				"indicacao_rev" 		=> "Indicação Revenda",
				"indicacao_at" 			=> "Indicação A.T",
			)
		)
	);

?>

	<script>
		$(function(){
		
			Shadowbox.init();
		
			$.datepickerLoad(Array("data_final", "data_inicial"));

			$('#data_inicial').mask("99/99/9999");
			$('#data_final').mask("99/99/9999");

		});
		
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
	
	<?php

	if(isset($resSubmit)){

		if (pg_num_rows($resSubmit) > 0) {

			$naturezas = array(
				"reclamacao_produto" 	=> "Reclamação",
				"reclamacao_empresa" 	=> "Recl. Empresa",
				"reclamacao_at" 		=> "Reclamação A.T.",
				"duvida_produto" 		=> "Dúvida Produto",
				"sugestao" 				=> "Sugestão",
				"procon" 				=> "Procon/Judicial",
				"onde_comprar" 			=> "Onde Comprar",
				"indicacao_rev" 		=> "Indicação Revenda",
				"indicacao_at" 			=> "Indicação A.T"
			);

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

			echo "<div class='tal' style='padding-rigth: 5px !important;'>";

				echo "
					<table id='resultado' class=\"table table-striped table-bordered table-hover table-large\" style='margin: 0 auto;' >
						<thead>
							<tr class='titulo_coluna'>
								<th>CNPJ</th>
								<th>Nome</th>
								<th>Natureza</th>
								<th>Situação</th>
								<th>Total Chamados</th>
							</tr>
						</thead>
						<tbody>
				";

				for ($i = 0; $i < $count; $i++) {

					$posto 			= pg_fetch_result($resSubmit,$i,'posto');
					$cnpj 			= formatCNPJ(pg_fetch_result($resSubmit,$i,'cnpj'));
					$nome 			= pg_fetch_result($resSubmit,$i,'nome');
					$natureza 		= pg_fetch_result($resSubmit,$i,'categoria');
					$situacao 		= pg_fetch_result($resSubmit,$i,'status');
					$total_chamados = pg_fetch_result($resSubmit,$i,'total_chamados');

					echo "<tr style='text-align:center'>
						<td>$cnpj</td>
						<td>$nome</td>
						<td>".$naturezas[$natureza]."</td>
						<td style='text-align: center;'><strong>$situacao</strong></td>
						<td style='text-align: center;'><button type='button' class='btn-link' onclick='listarChamados({posto : \"$posto\", data_inicial : \"$aux_data_inicial\", data_final : \"$aux_data_final\", categoria : \"$natureza\", situacao : \"$situacao\"})'>$total_chamados</button></td>
					</tr>";
					;
				}

				echo "
						</tbody>

					</table>
				";

				echo "<br />";

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
