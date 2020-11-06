<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

// O Campo origem em tbl_produto.origem só aceita 3 caracteres, por isso a necessidade de criar o De Para
$deParaOrigem = array(
	"MNS" => "Manaus",
	"IMP" => "Importado",
	"CNS" => "Canoas"
);

if ($_POST["btn_pesquisa"] == "pesquisar") {
	$origem_pesquisa 	= $_POST["origem_pesquisa"];
	$ano_pesquisa 		= $_POST["ano_pesquisa"];

	if (!empty($origem_pesquisa)){
		$dados_origem = array();
		foreach ($origem_pesquisa as $key => $value) {
			$dados_origem[] = "'".$value."'";
		}
		$dados_origem = implode(",", $dados_origem);
		$cond_origem_pesquisa = " AND tbl_planejamento_qualidade.origem IN ($dados_origem) ";
	}
	
	if (!empty($ano_pesquisa)){
		$cond_ano_pesquisa = " AND tbl_planejamento_qualidade.ano = '$ano_pesquisa' ";
	}
}

if ($_POST["btn_acao"] == "submit") {
	
	$post 	= $_POST;
	$ano  	= $_POST["ano"];
	$origem = $_POST["origem"];
	$qtdeOrigem = count($origem);
	$dados_planejamento = false;

	// FCR //
		foreach ($post["fcr"] as $key => $value) {
			if (empty($value)){
				$post["fcr"][$key] = 0;
			}else{
				$value = str_replace(",", ".", str_replace(".", "", $value));
				$post["fcr"][$key] = $value;
				$dados_planejamento = true;
			}
		}
		$meses_fcr = json_encode($post["fcr"]);
	
	// OS //
		foreach ($post["os"] as $key => $value) {
			if (empty($value)){
				$post["os"][$key] = 0;
			}else{
				$value = str_replace(",", ".", str_replace(".", "", $value));
				$post["os"][$key] = $value;
				$dados_planejamento = true;
			}
		}
		$meses_os = json_encode($post["os"]);
		
	// UN //
		foreach ($post["un"] as $key => $value) {
			if (empty($value)){
				$post["un"][$key] = 0;
			}else{
				$value = str_replace(",", ".", str_replace(".", "", $value));
				$post["un"][$key] = $value;
				$dados_planejamento = true;
			}
		}
		$meses_venda = json_encode($post["un"]);
		
	if (empty($ano)){
		$msg_erro["campos"][] = "ano";
	}
	if (count($msg_erro["campos"]) > 0){
		$msg_erro["msg"][] = "Preencha os campos obrigatórios";
	}

	if ($dados_planejamento === false){
		$msg_erro["msg"][] = "Preencha algum dos meses do planejamento";
	}
	if (!count($msg_erro["msg"])) {

		if ($qtdeOrigem == 0 && $dados_planejamento === true) {
			$qtdeOrigem = 1;
		}
		
		for($i = 0; $i < $qtdeOrigem; $i++) {

			$sql = "
				SELECT revisao, ano, meses_fcr, meses_os, meses_venda 
				FROM tbl_planejamento_qualidade 
				WHERE fabrica = {$login_fabrica} 
				AND ano_pd IS NULL 
				AND origem = '$origem[$i]'
				AND ano = '$ano'
				ORDER BY revisao DESC LIMIT 1";
			$res = pg_query($con, $sql);
			
			if (pg_num_rows($res) > 0) {
				$revisao 		 = pg_fetch_result($res, 0, 'revisao');
				$aux_ano 		 = pg_fetch_result($res, 0, 'ano');
				$aux_meses_fcr 	 = pg_fetch_result($res, 0, 'meses_fcr');
				$aux_meses_os 	 = pg_fetch_result($res, 0, 'meses_os');
				$aux_meses_venda = pg_fetch_result($res, 0, 'meses_venda');
				$revisao++;
			} else {
				$revisao = 0;
			}

			$aux_meses_fcr	 = json_decode($aux_meses_fcr, true);
			$aux_meses_os	 = json_decode($aux_meses_os, true);
			$aux_meses_venda = json_decode($aux_meses_venda, true);

			if (!empty($aux_meses_fcr) AND !empty($aux_meses_os) AND !empty($aux_meses_venda) AND $ano == $aux_ano){
				$valida_fcr   = array_diff($aux_meses_fcr, $post["fcr"]);
				$valida_os    = array_diff($aux_meses_os, $post["os"]);
				$valida_venda = array_diff($aux_meses_venda, $post["un"]);

				if (empty($valida_fcr) AND empty($valida_os) AND empty($valida_venda)){
					$msg_erro["msg"][] = "Dados já cadastrado.";
				}
			}
			
			switch ($origem[$i]) {
				case 'MNS':
					$labelOrigem = "Manaus";
					break;
				case 'IMP':
					$labelOrigem = "Importado";
					break;
				case 'CNS':
					$labelOrigem = "Canoas";
					break;
			}

			if (!count($msg_erro["msg"])) {
				$sql_insert = "
					INSERT INTO tbl_planejamento_qualidade (
						revisao,
						origem,
						ano,
						meses_fcr,
						meses_os,
						meses_venda,
						data_input,
						fabrica,
						admin
					)VALUES(
						{$revisao},
						'{$origem[$i]}',
						'{$ano}',
						'{$meses_fcr}',
						'{$meses_os}',
						'{$meses_venda}',
						now(),
						{$login_fabrica},
						$login_admin
					)
				";
				$res_insert = pg_query($con, $sql_insert); 
				
				if (strlen(pg_last_error()) > 0) {
					$msg_erro["msg"][] = "Erro ao gravar planejamento para origem: $labelOrigem";
				} else {
					$msg_success["msg"][] = "Planejamento gravado com sucesso.";
				}
				
			} else {
				extract($post["fcr"]);
				extract($post["os"]);
				extract($post["un"]);
			}
			
		}
		if (count($msg_success["msg"])){
			unset($post);
			unset($ano);
			unset($origem);
		}
	}
}

$layout_menu = "gerencia";
$title = "CADASTRO PLANEJAMENTO";
include 'cabecalho_new.php';

$plugins = array(
	"mask",
	"alphanumeric",
	"price_format",
	"select2"
);

include("plugin_loader.php");
?>
<style type="text/css">
	.hero_ajuste {
	    padding-top: 2px !important;
	    padding-right: 0px !important;
	    padding-bottom: 22px !important;
	    padding-left: 20px !important;
	}

	.class_th{
		width: 160px !important;
		background: #596D9B !important;
		text-align: left !important;
		color: #ffffff;
	}

	<?php if (count($msg_erro["msg"]) > 0 AND in_array("origem", $msg_erro["campos"])){ ?>
		.select2-selection--multiple{
			border-color: #b94a48 !important;
		}
	<?php } ?>
</style>
<script type="text/javascript">
	$(function() {
		$(".numeric").numeric();
		
		$("#origem").select2();
		$("#origem_pesquisa").select2();

		var ano = $("#ano").val();

		if (ano != "" || ano != undefined){
			$(".label_ano").append().text(ano);
		}

		$("#ano").change(function(){
			$(".label_ano").append().text($(this).val());
		});
	});
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
if (count($msg_success["msg"]) > 0){
?>
	<div class="alert alert-success">
		<h4><?=$msg_success["msg"][0]?></h4>
    </div>
<?php
}
?>

<form name='frm_pesquisa' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("origem_pesquisa", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='origem_pesquisa'>Origem</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="origem_pesquisa[]" multiple="multiple" id="origem_pesquisa">
							<option value=""></option>
							<?php
								$origem_pesquisa = $_POST['origem_pesquisa'];
								$sql = "
									SELECT DISTINCT origem 
									FROM tbl_produto 
									WHERE fabrica_i = {$login_fabrica} 
									AND ativo IS TRUE 
									AND TRIM(origem) IS NOT NULL 
									ORDER BY origem";
								$res = pg_query($con, $sql); 
								
								foreach (pg_fetch_all($res) as $key) {
									$selected_origem = (in_array($key['origem'], $origem_pesquisa)) ? "selected" : "";
								?>
									<option value="<?php echo $key['origem']?>" <?php echo $selected_origem ?> >
										<?php echo $deParaOrigem[$key['origem']]; ?>
									</option>
								<?php
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("ano_pesquisa", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='ano_pesquisa'>Ano</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="ano_pesquisa" id="ano_pesquisa">
							<option value="">Selecione</option>
							<?php
								$ano_pesquisa = $_POST["ano_pesquisa"];
								$anoInicial = date('Y');
								$anoFinal 	= date('Y') + '3 years';
								while($anoInicial <= $anoFinal) {
									$selected = ($anoInicial == $ano_pesquisa) ? "selected": ""; ?>
									<option value='<?= $anoInicial; ?>' <?= $selected; ?>><?= $anoInicial; ?></option>
								<?php
									$anoInicial++;
								} ?>
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<br/>
	
	<p><br/>
		<button type='submit' id="btn_pesquisa" class="btn" name='btn_pesquisa' value='pesquisar' />Pesquisar</button>
	</p><br/>
</form>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Cadastro</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='origem'>Origem</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="origem[]" multiple="multiple" id="origem">
							<option value=""></option>
							<?php
							$origem = $_POST['origem'];
							$sql = "
								SELECT DISTINCT origem 
								FROM tbl_produto 
								WHERE fabrica_i = {$login_fabrica} 
								AND ativo IS TRUE 
								AND TRIM(origem) IS NOT NULL 
								ORDER BY origem";
							$res = pg_query($con, $sql); 
							
							foreach (pg_fetch_all($res) as $key) {
								$selected_origem = (in_array($key['origem'], $origem)) ? "selected" : "";
							?>
								<option value="<?php echo $key['origem']?>" <?php echo $selected_origem ?> >
									<?php echo $deParaOrigem[$key['origem']]; ?>
								</option>
							<?php
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
							<option value="">Selecione</option>
							<?php
							$ano = $_POST["ano"];
							$anoInicial = date('Y');
							$anoFinal 	= date('Y') + '3 years';
							while($anoInicial <= $anoFinal) {
								$selected = ($anoInicial == $ano) ? "selected": ""; ?>
								<option value='<?= $anoInicial; ?>' <?= $selected; ?>><?= $anoInicial; ?></option>
							<?php
							$anoInicial++;
							} ?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<br/>
	<!-- FCR -->
	<div class="hero-unit hero_ajuste">
		<h3>FCR <span class='label_ano'></span></h3>
		<div class="row-fluid">
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_janeiro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_janeiro'>Janeiro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[1]" id="fcr_janeiro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["1"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_fevereiro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_fevereiro'>Fevereiro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[2]" id="fcr_fevereiro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["2"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_marco", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_marco'>Março <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[3]" id="fcr_marco" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["3"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_abril", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_abril'>Abril <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[4]" id="fcr_abril" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["4"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_maio", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_maio'>Maio <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[5]" id="fcr_maio" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["5"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_junho", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_junho'>Junho <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[6]" id="fcr_junho" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["6"]?>" >
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_julho", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_julho'>Julho <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[7]" id="fcr_julho" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["7"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_agosto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_agosto'>Agosto <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[8]" id="fcr_agosto" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["8"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_setembro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_setembro'>Setembro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[9]" id="fcr_setembro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["9"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_outubro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_outubro'>Outubro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[10]" id="fcr_outubro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["10"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_novembro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_novembro'>Novembro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[11]" id="fcr_novembro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["11"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("fcr_dezembro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fcr_dezembro'>Dezembro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="fcr[12]" id="fcr_dezembro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["fcr"]["12"]?>" >
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- OS -->
	<div class="hero-unit hero_ajuste">
		<h3>Ordens de Serviço (OS) <span class='label_ano'></span></h3>
		<div class="row-fluid">
			<div class='span2'>
				<div class='control-group <?=(in_array("os_janeiro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_janeiro'>Janeiro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[1]" id="os_janeiro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["1"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("os_fevereiro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_fevereiro'>Fevereiro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[2]" id="os_fevereiro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["2"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("os_marco", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_marco'>Março <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[3]" id="os_marco" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["3"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("os_abril", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_abril'>Abril <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[4]" id="os_abril" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["4"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("os_maio", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_maio'>Maio <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[5]" id="os_maio" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["5"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("os_junho", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_junho'>Junho <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[6]" id="os_junho" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["6"]?>" >
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<div class='span2'>
				<div class='control-group <?=(in_array("os_julho", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_julho'>Julho <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[7]" id="os_julho" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["7"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("os_agosto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_agosto'>Agosto <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[8]" id="os_agosto" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["8"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("os_setembro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_setembro'>Setembro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[9]" id="os_setembro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["9"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("os_outubro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_outubro'>Outubro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[10]" id="os_outubro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["10"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("os_novembro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_novembro'>Novembro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[11]" id="os_novembro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["11"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("os_dezembro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os_dezembro'>Dezembro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="os[12]" id="os_dezembro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["os"]["12"]?>" >
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Unidades vendidas (Un) -->
	<div class="hero-unit hero_ajuste">
		<h3>Unidades vendidas (UN) <span class='label_ano'></span></h3>
		<div class="row-fluid">
			<div class='span2'>
				<div class='control-group <?=(in_array("un_janeiro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_janeiro'>Janeiro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[1]" id="un_janeiro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["1"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("un_fevereiro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_fevereiro'>Fevereiro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[2]" id="un_fevereiro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["2"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("un_marco", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_marco'>Março <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[3]" id="un_marco" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["3"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("un_abril", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_abril'>Abril <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[4]" id="un_abril" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["4"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("un_maio", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_maio'>Maio <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[5]" id="un_maio" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["5"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("un_junho", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_junho'>Junho <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[6]" id="un_junho" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["6"]?>" >
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<div class='span2'>
				<div class='control-group <?=(in_array("un_julho", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_julho'>Julho <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[7]" id="un_julho" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["7"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("un_agosto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_agosto'>Agosto <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[8]" id="un_agosto" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["8"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("un_setembro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_setembro'>Setembro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[9]" id="un_setembro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["9"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("un_outubro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_outubro'>Outubro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[10]" id="un_outubro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["10"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("un_novembro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_novembro'>Novembro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[11]" id="un_novembro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["11"]?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'>
				<div class='control-group <?=(in_array("un_dezembro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='un_dezembro'>Dezembro <span class='label_ano'></span></label>
					<div class='controls controls-row'>
						<div class='span10'>
							<input type="text" name="un[12]" id="un_dezembro" size="12" price="true" maxlength="10" class='span12 numeric' value="<?=$post["un"]["12"]?>" >
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
</div>

<?php 
$sql_query = "
	SELECT 
		tbl_planejamento_qualidade.revisao,
		tbl_planejamento_qualidade.origem ,
		tbl_planejamento_qualidade.ano,
		tbl_planejamento_qualidade.meses_fcr,
		tbl_planejamento_qualidade.meses_os,
		tbl_planejamento_qualidade.meses_venda,
		TO_CHAR(tbl_planejamento_qualidade.data_input, 'DD/MM/YYYY') AS data_registro,
		tbl_admin.nome_completo
	FROM tbl_planejamento_qualidade 
	JOIN tbl_admin ON tbl_admin.admin = tbl_planejamento_qualidade.admin AND tbl_admin.fabrica = {$login_fabrica}
	WHERE tbl_planejamento_qualidade.fabrica = {$login_fabrica}
	AND tbl_planejamento_qualidade.ano_pd IS NULL
	{$cond_origem_pesquisa}
	{$cond_ano_pesquisa}
	ORDER BY tbl_planejamento_qualidade.revisao";
$res_query = pg_query($con, $sql_query);

if (pg_num_rows($res_query) > 0) { ?>
	<div class='container-fluid'>
	<? for ($i=0; $i < pg_num_rows($res_query); $i++) { 
		$revisao 		= pg_fetch_result($res_query, $i, 'revisao');
		$origem 		= pg_fetch_result($res_query, $i, 'origem');
		$ano 			= pg_fetch_result($res_query, $i, 'ano');
		$meses_fcr 		= pg_fetch_result($res_query, $i, 'meses_fcr');
		$meses_os		= pg_fetch_result($res_query, $i, 'meses_os');
		$meses_venda 	= pg_fetch_result($res_query, $i, 'meses_venda');
		$data_registro 	= pg_fetch_result($res_query, $i, 'data_registro');
		$nome_completo 	= pg_fetch_result($res_query, $i, 'nome_completo');

		switch ($origem) {
			case 'MNS':
				$labelOrigem = "Manaus";
				break;
			case 'IMP':
				$labelOrigem = "Importado";
				break;
			case 'CNS':
				$labelOrigem = "Canoas";
				break;
			default:
				$labelOrigem = "Geral";
				break;
		}

		$meses_fcr 	 = json_decode($meses_fcr, true);
		$meses_os 	 = json_decode($meses_os, true);
		$meses_venda = json_decode($meses_venda, true); ?>
		<table class="table table-striped table-bordered table-fixed">
			<thead>
				<tr class='titulo_tabela'><th colspan="2"><?=$labelOrigem?></th></tr>
			</thead>
		    <tbody>
		        <tr>
			        <th class="class_th">Revisão</th>
		            <td><?=$revisao?></td>
		        </tr>
		        <tr>
		          	<th class="class_th">Ano</th>
	          	    <td><?=$ano?></td>
		        </tr>
		        <tr>
		        	<th class="class_th">Data Cadastro</th>
		        	<td><?=$data_registro?></td>
		        </tr>
		        <tr>
		        	<th class="class_th">Admin</th>
		        	<td><?=$nome_completo?></td>
		        </tr>
		        <tr>
		        	<th class="class_th">Meses</th>
		        	<td style="padding: 1px !important;">
		        		<table class='table table-bordered' style="margin-bottom: 0px !important;">
		        			<tr class='titulo_coluna'>
		        				<th style="width: 55px;">Janeiro</th><th style="width: 55px;">Fevereiro</th><th style="width: 55px;">Março</th><th style="width: 55px;">Abril</th><th style="width: 55px;">Maio</th><th style="width: 55px;">Junho</th><th style="width: 55px;">Julho</th>
		        				<th style="width: 55px;">Agosto</th><th style="width: 55px;">Setembro</th><th style="width: 55px;">Outubro</th><th style="width: 55px;">Novembro</th><th style="width: 55px;">Dezembro</th>
		        			</tr>
		        		</table>
		        	</td>
		        </tr>
		        <tr>
		          	<th class="class_th">FCR</th>
	          	    <td style="padding: 1px !important;">
	          	    	<table class='table table-bordered' style="margin-bottom: 0px !important;">
	          	    		<tr>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["1"]?></td>
	          	    			<td class="tac" style="width: 55px;"><?=$meses_fcr["2"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["3"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["4"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["5"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["6"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["7"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["8"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["9"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["10"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["11"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_fcr["12"]?></td>
	          	    		</tr>
	          	    	</table>
		          	  </td>
		        </tr>
		        <tr>
		          	<th class="class_th">Ordens de Serviço (OS)</th>
	          	    <td style="padding: 1px !important;">
	          	    	<table class='table table-bordered' style="margin-bottom: 0px !important;">
	          	    		<tr>
								<td class="tac" style="width: 55px;"><?=$meses_os["1"]?></td>
	          	    			<td class="tac" style="width: 55px;"><?=$meses_os["2"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_os["3"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_os["4"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_os["5"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_os["6"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_os["7"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_os["8"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_os["9"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_os["10"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_os["11"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_os["12"]?></td>
	          	    		</tr>
	          	    	</table>
		          	  </td>
		        </tr>
		        <tr>
		          	<th class="class_th">Unidades Vendidas (UN)</th>
	          	    <td style="padding: 1px !important;">
	          	    	<table class='table table-bordered' style="margin-bottom: 0px !important;">
	          	    		<tr>
								<td class="tac" style="width: 55px;"><?=$meses_venda["1"]?></td>
	          	    			<td class="tac" style="width: 55px;"><?=$meses_venda["2"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_venda["3"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_venda["4"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_venda["5"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_venda["6"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_venda["7"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_venda["8"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_venda["9"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_venda["10"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_venda["11"]?></td>
								<td class="tac" style="width: 55px;"><?=$meses_venda["12"]?></td>
	          	    		</tr>
	          	    	</table>
		          	  </td>
		        </tr>
		    </tbody>
		</table>
	<?php } ?>
	</div>
<?php }
include 'rodape.php';?>
