<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_pesquisa"] == "pesquisar") {
	$ano_pesquisa 		= $_POST["ano_pesquisa"];

	if (!empty($ano_pesquisa)){
		$cond_ano_pesquisa = " AND tbl_planejamento_qualidade.ano = '$ano_pesquisa' ";
	}
}

if ($_POST["btn_acao"] == "submit" AND isset($_POST)) {
	
	$post 	= $_POST;
	$ano  	= $_POST["ano"];

	$produtos_imp = $post["pd"];
	$dados_planejamento = false;

	foreach ($post["pd_qtde"] as $key => $value) {
		if (empty($value)){
			$post["pd_qtde"][$key] = 0;
		}else{
			$post["pd_qtde"][$key] = $value;
			$dados_planejamento = true;
		}
	}
	$pd_qtde = $post["pd_qtde"];
	$ano_pd = json_encode($pd_qtde);
	
	if (empty($ano)){
		$msg_erro["campos"][] = "ano";
	}
	if (empty($produtos_imp)){
		$msg_erro["campos"][] = "origem";
	}
	if (count($msg_erro["campos"]) > 0){
		$msg_erro["msg"][] = "Preencha os campos obrigatórios";
	}

	if ($dados_planejamento === false AND !empty($produtos_imp)){
		$msg_erro["msg"][] = "Preencha algum dos produtos para continuar";
	}
	if (!count($msg_erro["msg"])) {
		
		$sql = "SELECT revisao, ano, ano_pd
				FROM tbl_planejamento_qualidade 
				WHERE fabrica = {$login_fabrica} 
				AND origem IS NULL
				AND meses_fcr IS NULL
				AND meses_venda IS NULL
				AND meses_os IS NULL
				/*AND ano = '{$ano}'*/
				ORDER BY revisao DESC LIMIT 1";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
			$revisao = pg_fetch_result($res, 0, 'revisao');
			$aux_ano_pd = pg_fetch_result($res, 0, 'ano_pd');
			$aux_ano = pg_fetch_result($res, 0, 'ano');
			$revisao++;
		}else{
			$revisao = 0;
		}
		
		$aux_ano_pd = json_decode($aux_ano_pd, true);

		if ($aux_ano == $ano AND count($aux_ano_pd) == count($pd_qtde)){
			$msg_erro["msg"][] = "Dados já cadastrados";
		}

		if (!count($msg_erro["msg"])) {
			$sql_insert = "
				INSERT INTO tbl_planejamento_qualidade (
					revisao,
					ano,
					ano_pd,
					data_input,
					fabrica,
					admin
				)VALUES(
					{$revisao},
					'{$ano}',
					'{$ano_pd}',
					now(),
					{$login_fabrica},
					$login_admin
				)
			";
			$res_insert = pg_query($con, $sql_insert); 
				
			if (strlen(pg_last_error()) > 0){
				$msg_erro["msg"][] = "Erro ao gravar planejamento para o ano: $ano";
			}else{
				$msg_success["msg"][] = "Planejamento gravado com sucesso.";
			}
			unset($post);
			unset($ano);
			unset($produtos_imp);
			unset($pd_qtde);
			unset($ano_pd);
		}
	}
}

$layout_menu = "gerencia";
$title = "CADASTRO PLANEJAMENTO";
include 'cabecalho_new.php';

$plugins = array(
	"mask",
	"alphanumeric",
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
		
		$("#produto_imp").select2();
		$("#origem_pesquisa").select2();

		$("#adicionar").click(function(){
			var qtde_tabela = $("#qtde_tabela").val();
			var produtos = $("#produto_imp").val();
			var ano = $("#ano").val();
			var cont = 0;
			var dados = "";
			var conteudo = "";
			var tabela = "";
			var input = "";

			if (qtde_tabela > 0){
				if (confirm('Atenção ao adicionar novos produtos os dados atuais serão apagados, deseja continuar ?')) {
					//$("#conteudo").append().html("");
					$("#conteudo").append().html("<input type='hidden' value='' id='qtde_tabela' name='qtde_tabela'>");
				}else{
					return false;
				}
			}
			
			$(produtos).each(function(id,x){
				if (tabela == "" ){
					tabela = monta_tabela();
					$("#conteudo").append(tabela);
				}
				conteudo = "<th>"+x+" <input type='hidden' value='"+x+"' name='pd["+x+"]' </th>";
				input = "<td class='tac'> <input class='span1 numeric' type='text' value='' name='pd_qtde["+x+"]'></td>";
				$(tabela).find(".titulo_coluna").append(conteudo);
				$(tabela).find(".conteudo_body").append(input);
				cont++;
				if(cont % 10 == 0){
					tabela = "";
				}
			});
			$(".numeric").numeric();
			$("#qtde_tabela").val(cont);
		});
	});

	function monta_tabela (){
		dados = "<table class='table table-striped table-bordered table-fixed'>"+
			"<thead>"+
				"<tr class='titulo_coluna'>"+
				"</tr>"+
			"</thead>"+
			"<tbody>"+
			"<tr class='conteudo_body'>"+
			"</tr>"+				
			"</tbody>"+
			"</table>";

		dados = $(dados);
		return dados;
	}
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
									$anoInicial++;
									$selected = ($anoInicial == $ano_pesquisa) ? "selected": ""; 
							?>
									<option value='<?= $anoInicial; ?>' <?= $selected; ?>><?= $anoInicial; ?></option>
							<?php } ?>
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span6'></div>
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
			<div class='control-group <?=(in_array("produto_imp", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_imp'>Produto Importado</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<select name="produto_imp[]" multiple="multiple" id="produto_imp">
							<option value=""></option>
							<?php
								
								$sql = "SELECT DISTINCT JSON_FIELD('pd', parametros_adicionais) AS produtos_imp 
										FROM tbl_produto 
										WHERE fabrica_i = {$login_fabrica} 
										AND ativo IS TRUE 
										AND parametros_adicionais like '%pd%' 
										AND origem = 'IMP' 
										ORDER BY produtos_imp";
								$res = pg_query($con, $sql);
								
								foreach (pg_fetch_all($res) as $key) {
									$selected_pd = (in_array($key['produtos_imp'], $produtos_imp)) ? "selected" : "";
								?>
									<option value="<?php echo $key['produtos_imp']?>" <?php echo $selected_pd ?> >
										<?php echo $key['produtos_imp']; ?>
									</option>
								<?php
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'>
			<div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='ano'>Ano</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<select name="ano" class="span10" id="ano">
							<option value="">Selecione</option>
							<?php
								$anoInicial = date('Y');
								$anoFinal 	= date('Y') + '3 years';
								while($anoInicial <= $anoFinal) {
									$selected = ($anoInicial == $ano) ? "selected": ""; 
							?>
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
		<div class='span4'>
			<div class='control-group <?=(in_array("adicionar", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='adicionar'>&nbsp;</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<button name="adicionar" type="button" class="btn btn-primary" id="adicionar">Adicionar</button>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<br/>
	
	<!-- Unidades vendidas (Un) -->
	<div id="conteudo" style="margin: 4px;">
		<input type="hidden" value="<?=count($pd_qtde)?>" id="qtde_tabela" name="qtde_tabela">
		
		<?php 
			if (!empty($pd_qtde)){

				$contador = 0;
				$th = null;
				$td = null;
				
				foreach ($pd_qtde as $key => $value) {
					$th .= "<th>$key <input type='hidden' value='$key' name='pd[$key]'</th>";
					$td .= "<td class='tac'> <input class='span1 numeric' type='text' value='$value' name='pd_qtde[$key]'></td>";
					$next = next($pd_qtde);
					
					if ($contador == 9 OR is_null($next) OR $next === false){
						echo "
							<table class='table table-striped table-bordered table-fixed'>
								<thead>
									<tr class='titulo_coluna'>
										$th
									</tr>
								</thead>
								<tbody>
									<tr class='conteudo_body'>
										$td
									</tr>
								</tbody>
							</table>";
						$contador = 0;
						$th = null;
						$td = null;
					}else{
						$contador++;
					}
				}
				
			} 
		?>
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
			tbl_planejamento_qualidade.ano,
			tbl_planejamento_qualidade.ano_pd,
			TO_CHAR(tbl_planejamento_qualidade.data_input, 'DD/MM/YYYY') AS data_registro,
			tbl_admin.nome_completo
		FROM tbl_planejamento_qualidade 
		JOIN tbl_admin ON tbl_admin.admin = tbl_planejamento_qualidade.admin AND tbl_admin.fabrica = {$login_fabrica}
		WHERE tbl_planejamento_qualidade.fabrica = {$login_fabrica}
		AND origem IS NULL
		AND meses_fcr IS NULL
		AND meses_venda IS NULL
		AND meses_os IS NULL
		$cond_ano_pesquisa
		ORDER BY tbl_planejamento_qualidade.revisao";
	$res_query = pg_query($con, $sql_query);

	if (pg_num_rows($res_query) > 0){
		echo "<div class='container-fluid'>";
		for ($i=0; $i < pg_num_rows($res_query); $i++) { 
			$revisao 		= pg_fetch_result($res_query, $i, 'revisao');
			$ano_pd 		= pg_fetch_result($res_query, $i, 'ano_pd');
			$ano 			= pg_fetch_result($res_query, $i, 'ano');
			$data_registro 	= pg_fetch_result($res_query, $i, 'data_registro');
			$nome_completo 	= pg_fetch_result($res_query, $i, 'nome_completo');

			$ano_pd 	 = json_decode($ano_pd, true);
?>
			<table class="table table-striped table-bordered table-fixed">
				<thead>
					<tr class='titulo_tabela'><th colspan="2"><?=$ano?></th></tr>
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
			        	<th class="class_th">Produtos</th>
			        	<td style="padding: 1px !important;">
			        		<?php
			        			$contador_pd = 0; 
			        			foreach ($ano_pd as $key => $value) {
			        				$th_pd .= "<th>$key</th>";
									$td_pd .= "<td class='tac'>$value</td>";
									$next_pd = next($ano_pd);
									if ($contador_pd == 9 OR is_null($next_pd) OR $next_pd === false){
										echo "
											<table class='table table-striped table-bordered table-fixed'>
												<thead>
													<tr class='titulo_coluna'>
														$th_pd
													</tr>
												</thead>
												<tbody>
													<tr>
														$td_pd
													</tr>
												</tbody>
											</table>";
										$contador_pd = 0;
										$th_pd = null;
										$td_pd = null;
									}else{
										$contador_pd++;
									}
			        			}
			        		?>
			        	</td>
			        </tr>
			        
			    </tbody>
			</table>
	<?php
		}
		echo "</div>";
	}else{
		echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
	}
?>
<?php include 'rodape.php';?>
