<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$linha              = $_POST['linha'];
	$pais 				= $_POST['pais'];

	if (strlen($pais)>0){
	//	$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";
		$sql = "SELECT 	tbl_produto.produto AS produto_codigo,
								tbl_produto.descricao AS produto_descricao,
								tbl_produto.referencia 
						FROM	tbl_produto 
						WHERE	tbl_produto.fabrica_i = $login_fabrica 
					";
						
			if(strlen($linha)>0){
				$sql .="AND		tbl_produto.linha = {$linha}";
			}
			if(strlen($produto_referencia)>0){
				$sql .=" AND	tbl_produto.referencia = '$produto_referencia' ";
				}
				
				$resSubmit = pg_query($con,$sql);
			
				$sqlm =	"SELECT conversao_moeda, 
								unidade_trabalho
						FROM 	tbl_pais
						WHERE pais = '$pais'";
					$resM = pg_query($con,$sqlm);
				
				if (pg_num_rows($resM)>0){
					$unidade_trabalho  = pg_fetch_result($resM,0,'conversao_moeda');
					$conversao_moeda   = pg_fetch_result($resM,0,'unidade_trabalho');
				}
				
		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($resSubmit) > 0) {
				$data = date("d-m-Y-H:i");

				$fileName = "relatorio-mao-obra-pais-{$login_fabrica}-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");
				$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='5' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE MAO DE OBRA PAÍS
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descricao</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Reparação</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>VT</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>MO R$</th>
							</tr>
					</thead>
					<tbody>
				";
			fwrite($file, $thead);
				for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
					$produto			= pg_fetch_result($resSubmit,$i,'produto_codigo');
					$descricao          = trim(pg_fetch_result($resSubmit,$i,'produto_descricao'));
					$referencia         = pg_fetch_result($resSubmit,$i,'referencia');
					
					$sql2 = "SELECT	 tbl_defeito_constatado.descricao,
									 tbl_produto_defeito_constatado.mao_de_obra  ,
									 tbl_produto_defeito_constatado.unidade_tempo
								FROM tbl_produto_defeito_constatado
								JOIN tbl_defeito_constatado ON tbl_produto_defeito_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado 
								AND tbl_defeito_constatado.fabrica = {$login_fabrica} 
								WHERE   produto            = $produto
							";
					$res2 = pg_query ($con,$sql2);
					
					if(pg_num_rows($res2)>0){
						$defeitos = pg_fetch_all($res2);
					}else{
						$unidade_tempo = '1';
					}
					
					
					
					$body .="
							<tr>
								<td nowrap align='center'>&nbsp;{$referencia}</td>
								<td nowrap align='center'>{$descricao}</td>";
					$body .= " <td nowrap aling='left' valign='top'>";
						foreach ($defeitos AS $key => $value ){
							$body .= $value ["descricao"]."<br />";
						}
					$body .="	</td> ";
					
					$body .=" <td nowrap align='right' >";
						foreach ($defeitos AS $key => $value ){
							$body .= $value ["unidade_tempo"]."<br />";
						}
					$body .="	</td>";
					
					$body .="  <td nowrap  align='right' >";
						foreach ($defeitos AS $key => $value ){
									
									if (($unidade_trabalho>0) && ($conversao_moeda>0)){
									$mao_de_obra =  $value["unidade_tempo"] * round( ($unidade_trabalho * $conversao_moeda), 2);
									}else{
										$mao_de_obra = 0;
									}
							
							$body .= number_format($mao_de_obra,2,",",".")."<br />";
						}
					$body .="	</td></tr>";
					
				}
				fwrite($file, $body);
				fwrite($file, "
							<tr>
								<th colspan='5' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
							</tr>
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
	}else{
		$msg_erro["msg"][]    = "País obrigatório";
		$msg_erro["campos"][] = "pais";
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE MÃO OBRA POR PAIS";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
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
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'>Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
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
					<label class='control-label' for='linha'>Linha</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="linha" id="linha">
								<option value=""></option>
								<?php
								$sql = "SELECT linha, nome
										FROM tbl_linha
										WHERE fabrica = $login_fabrica
										AND ativo";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >

										<?php echo $key['nome']?>

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
				<div class='control-group <?=(in_array("pais", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='pais'>País</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="pais" id="pais">
								<option value=""></option>
								<?php

									$sql = "SELECT pais, nome FROM tbl_pais order by nome";
									$res = pg_query($con,$sql);
									foreach (pg_fetch_all($res) as $key) {

										$selected_pais = ( isset($pais) and ($pais == $key['pais']) ) ? "SELECTED" : '' ;

									?>
										<option value="<?php echo $key['pais']?>" <?php echo $selected_pais ?> >
											<?php echo $key['nome']?>
										</option>
									<?php
									}

								?>
							</select>
						</div>
						<div class='span2'></div>
					</div>
				</div>
			</div>
		</div>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>

<?php
if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";

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
		?>
			<table id="resultado_relatorio_mao_obra_pais" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Código</th>
						<th>Descrição</th>
						<th>Reparação</th>
						<th>VT</th>
						<th>Mão Obra</th>
						
					</tr>
				</thead>
				<tbody>
					<?php
						for ($i = 0; $i < $count; $i++) {
						$produto			= pg_fetch_result($resSubmit,$i,'produto_codigo');
						$descricao          = trim(pg_fetch_result($resSubmit,$i,'produto_descricao'));
						$referencia         = pg_fetch_result($resSubmit,$i,'referencia');
						
						$sql2 = "SELECT	 tbl_defeito_constatado.descricao,
										 tbl_produto_defeito_constatado.mao_de_obra  ,
										 tbl_produto_defeito_constatado.unidade_tempo
									FROM tbl_produto_defeito_constatado
									JOIN tbl_defeito_constatado ON tbl_produto_defeito_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado 
									AND tbl_defeito_constatado.fabrica = {$login_fabrica} 
									WHERE   produto            = $produto
								";
						$res2 = pg_query ($con,$sql2);
						
						if(pg_num_rows($res2)>0){
							$defeitos = pg_fetch_all($res2);
						}else{
							$unidade_tempo = '1';
						}
						
						
						
						$body .="
								<tr>
									<td nowrap align='center' valign='top'>{$referencia}</td>
									<td nowrap align='center' valign='top'>{$descricao}</td>";
						$body .= " <td nowrap aling='left' valign='top'>";
							foreach ($defeitos AS $key => $value ){
								$body .= $value ["descricao"]."<br />";
							}
						$body .="	</td> ";
						
						$body .=" <td nowrap class='tar' valign='top'>";
							foreach ($defeitos AS $key => $value ){
								$body .= $value ["unidade_tempo"]."<br />";
							}
						$body .="	</td>";
						
						$body .="  <td nowrap class='tar' valign='top'>";
							foreach ($defeitos AS $key => $value ){
										
										if (($unidade_trabalho>0) && ($conversao_moeda>0)){
										$mao_de_obra =  $value["unidade_tempo"] * round( ($unidade_trabalho * $conversao_moeda), 2);
										}else{
											$mao_de_obra = 0;
										}
								
								$body .= number_format($mao_de_obra,2,",",".")."<br />";
							}
						$body .="	</td></tr>";
						
							
				}
					echo $body;
					?>
				</tbody>
			</table>

			<?php
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_relatorio_mao_obra_pais" });
				</script>
			<?php
			}
			?>

			<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>
		<?php
		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
		}
	}



include 'rodape.php';?>
