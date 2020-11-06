<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastro";

include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = $_POST['btn_acao'];


if($btn_acao == "submit"){
	$mes      = $_POST["mes"];
	$ano      = $_POST["ano"];
	$dia_mes  = $_POST["dia_mes"];
	$meta     = $_POST["meta"];
	$meta 	  = array_filter($meta);
	$dia_mes  = array_filter($dia_mes);

	# Validações
	if (!strlen($mes)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "mes";
	}

	if (!strlen($ano)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "ano";
	}

	if (count($dia_mes) == 0) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "dia_mes";
	}

	if (count($meta) == 0) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "meta";
	}

	for ($i=0; $i < count($dia_mes); $i++) {

		if(!checkdate($mes, $dia_mes[$i], $ano)){
			$msg_erro['msg']["obg"] = "Data {$dia_mes[$i]}/{$mes}/{$ano} inválida";
		}

	}

	if(count($msg_erro['msg']) == 0){

		for($i = 0; $i < count($meta); $i++){

			$dia = str_pad($dia_mes[$i], 2, "0",STR_PAD_LEFT);
			$valor_meta[$dia] = $meta[$i];

		}

		$metas[$mes] = $valor_meta;
		$caminho = "metas/".$login_fabrica;
		if(!is_dir($caminho)){
			mkdir($caminho,0700);
		}

		$arquivo = $caminho."/".$ano."_".$login_fabrica.".txt";

		if(file_exists($arquivo)){
			$conteudo = file_get_contents($arquivo);
			$dados_metas = json_decode($conteudo,true);
		}


		if(count($dados_metas) > 0){

			if(array_key_exists($mes, $dados_metas)){

				$array_mes = $dados_metas[$mes];

				foreach ($valor_meta as $key => $value) {
					$array_mes[$key] = $value;
				}

			}else{
				$array_mes = $valor_meta;
			}

			$dados_metas[$mes] = $array_mes;

		}else{
			$dados_metas = $metas;
		}

		$conteudo = json_encode($dados_metas);
		$arquivo_meta = fopen($arquivo, "w");
		fwrite($arquivo_meta, $conteudo);
		fclose($arquivo_meta);
		$metas = $metas[$mes];


		if (is_file($arquivo)) {
            $msg = "Gravado com Sucesso";
        } else {
            $msg_erro['msg'][] = "Não foi possível gravar o arquivo";
        }

	}else{
		for($i = 0; $i < count($meta); $i++){

			$metas[$dia_mes[$i]] = $meta[$i];

		}
	}


}

if($_GET['mes'] && $_GET['ano']){

	$ano = $_GET['ano'];
	$mes = $_GET['mes'];
	$apagar = $_GET['apagar'];

	$arquivo = "metas/{$login_fabrica}/".$ano."_".$login_fabrica.".txt";
	$conteudo = file_get_contents($arquivo);
	$dados_metas = json_decode($conteudo,true);

	if($apagar == "sim"){
		unset($dados_metas[$mes]);

		$conteudo = json_encode($dados_metas);
		$arquivo_meta = fopen($arquivo, "w");
		fwrite($arquivo_meta, $conteudo);
		fclose($arquivo_meta);
		$msg = "Informações apagadas com Sucesso";
	}

	$metas = $dados_metas[$mes];

}

$layout_menu = "cadastro";
$title = "CADASTRO DE METAS REPARO";

include_once "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "mask"
);

include("plugin_loader.php");

$options_mes = array(
				"01" => "Janeiro",
				"02" => "Fevereiro",
				"03" => "Março",
				"04" => "Abril",
				"05" => "Maio",
				"06" => "Junho",
				"07" => "Julho",
				"08" => "Agosto",
				"09" => "Setembro",
				"10" => "Outubro",
				"11" => "Novembro",
				"12" => "Dezembro",
				);
?>

<script type="text/javascript">
	$(function(){

		$(document).on("blur","input[name^=meta]",function(){

			var newLn = "<tr><td class='tac'><input type='text' name='dia_mes[]' class='span1'></td> <td class='tac'><input type='text' name='meta[]' class='span1'></td></tr>";
			$("#tabela_metas > tbody").append(newLn);

			//$("#tabela_metas").find("tr").last().find("input").first().focus();
			$(this).parents("tr").next().find("input").first().focus();

		});

		$(".ano").click(function(){

			var ano = $(this).attr("rel");

			if( $("#"+ano).is(":visible") ){
				$("#"+ano).hide();
			}else{
				$("#"+ano).show();
			}

		});

	});
</script>

<style type="text/css">
	#linha_modelo{
		display: none;
	}
</style>

	<div class="container">

		<?php
		/* Erro */
		if (count($msg_erro["msg"]) > 0) {
		?>
			<div class="alert alert-error">
				<h4><?php echo implode("<br />", $msg_erro["msg"])?></h4>
			</div>
		<?php } ?>

		<? if (strlen($msg) > 0 AND count($msg_erro["msg"])==0) { ?>
		    <div class="alert alert-success">
		        <h4><? echo $msg; ?></h4>
		    </div>
		<? } ?>

		<div class="container">
			<strong class="obrigatorio pull-right"> * Campos obrigatórios </strong>
		</div>

		<form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

			<div class='titulo_tabela'>Parâmetros de Pesquisa</div> <br/>

			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("mes", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='mes'>Mês</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<select name="mes" id="mes">
									<option value=""></option>
									<?php
										foreach ($options_mes as $key => $value) {
											$selected = ($key == $mes) ? "SELECTED" : "";

											echo "<option value='{$key}' {$selected}>{$value}</option>";
										}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='ano'>Ano</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<select name="ano" id="ano">
									<option value=""></option>
									<?php

										for ($i = date('Y'); $i > date('Y') - 3 ; $i--) {
											$selected = ($i == $ano) ? "SELECTED" : "";

											echo "<option value='{$i}' {$selected}>{$i}</option>";
										}
									?>
								</select>
							</div>
							<div class='span2'></div>
						</div>
					</div>
				</div>
			</div>

			<table id="tabela_metas" class="table table-striped table-bordered" style="width:250px; margin:0 auto; table-layout:fixed">
				<thead>
					<tr class='titulo_coluna'>
						<th>Dia Mês</th>
						<th>Meta</th>
					</tr>
				</thead>
				<tbody>

				<?php
					$count_meta = count($metas);

					if($count_meta == 0){
						$metas = array(""=>"");
					}

					foreach ($metas as $key => $value) {

				?>
					<tr>
						<td class="tac">
							<input type="text" name="dia_mes[]" id="dia_mes" class='span1' value="<? echo $key ?>" >
						</td>
						<td class="tac">
							<input type="text" name="meta[]" id="meta" class='span1' value="<? echo $value ?>" >
						</td>
					</tr>

				<?php
					}
				?>
				</tbody>
			</table>

			<p>
				<br/>
				<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
				<?php
					if(!empty($ano) AND count($msg_erro['msg']) == 0){
				?>
						<button class='btn' id="btn_acao" type="button"  onclick="window.location='cadastro_metas_produtividade.php'">Novo</button>

				<?php
					}
				?>

				<?php
					if($_GET['ano'] && $_GET['mes']){
				?>
						<button class='btn btn-danger' id="btn_acao" type="button"  onclick="window.location='cadastro_metas_produtividade.php?apagar=sim&mes=<?=$mes?>&ano=<?=$ano?>'">Apagar</button>

				<?php
					}
				?>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p>

			<br/>

		</form>

	</div>


<?php

	$list = glob("metas/{$login_fabrica}/*_{$login_fabrica}.txt");

	if(count($list) > 0){

		?>
			<table align="center" id="resultado_produtividade_repadro" class='table table-striped table-bordered table-hover' style="width:30% !important;">
				<thead>
					<tr class='titulo_coluna' >
						<th>ANO</th>
						<th>MESES</th>
            		</tr>
                </thead>
				<tbody>
		<?php

		foreach($list AS $files){

			$file = explode("/", $files);
			$arquivo_nome = explode("_", $file[2]);
		?>
			<tr>
				<td class='tac'>
					<a href='javascript: void(0);'><?=$arquivo_nome[0]?></a>
				</td>
				<td class="tac">
					<button type="button" class="btn btn-small btn-primary ano" rel='<?=$arquivo_nome[0]?>'>Mostrar meses</button>
					<ul id="<?=$arquivo_nome[0]?>" style="display:none;" class="nav nav-list">
						<?php
								$metas = file_get_contents($files);
								$meses = json_decode($metas,true);

								foreach ($meses as $key => $value) {
									echo "<li>
											<a href='?mes={$key}&ano={$arquivo_nome[0]}'>{$options_mes[$key]}</a>
										  </li>";
								}
							?>
					</ul>
				</td>
		  	</tr>

			<?php

		}

		echo "</tbody>";
    	echo "</table>";
	}

/* Rodapé */
	include 'rodape.php';
?>
