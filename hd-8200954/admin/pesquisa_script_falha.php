<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST['searchProd'])) {
	$familia = $_POST['familia'];

	$queryProd = "SELECT produto,
				  		 descricao
				  FROM tbl_produto
				  WHERE fabrica_i = {$login_fabrica}
				  AND familia = {$familia}
				  AND ativo IS TRUE
				  ORDER BY descricao ASC";
	$result = pg_query($con, $queryProd);
	$response = pg_fetch_all($result);

	$newResponse = array_map(function ($r) {
		return ['produto' => $r['produto'], 'descricao' => iconv('ISO-8859-1', 'UTF-8', $r['descricao'])];
	}, $response);

	echo json_encode($newResponse);
	exit;
}

if ($_POST['ajax'] && $_POST['familia']) {
	$familia = $_POST['familia'];
	$result = array();

	$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao
			FROM 	tbl_defeito_reclamado
			JOIN 	tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
				AND tbl_diagnostico.fabrica = {$login_fabrica}
			WHERE tbl_defeito_reclamado.fabrica = {$login_fabrica}
			AND tbl_defeito_reclamado.ativo IS TRUE
			AND tbl_diagnostico.familia = {$familia} ";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
        for ($i=0; $i < pg_num_rows($res); $i++) {
            $result[] = array("defeito_reclamado" => utf8_encode(pg_fetch_result($res, $i, defeito_reclamado)), "descricao" => utf8_encode(pg_result($res,$i,descricao)));
        }
        exit(json_encode(array("ok" => $result)));
    }else{
        exit(json_encode(array("no" => 'false')));
    }
}

if ($_POST['ajax'] && $_POST['linha']) {
	$linha = $_POST['linha'];
	$result = array();

	$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao
			FROM 	tbl_defeito_reclamado
			JOIN 	tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
				AND tbl_diagnostico.fabrica = {$login_fabrica}
			WHERE tbl_defeito_reclamado.fabrica = {$login_fabrica}
			AND tbl_defeito_reclamado.ativo IS TRUE
			AND tbl_diagnostico.linha = {$linha} ";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
        for ($i=0; $i < pg_num_rows($res); $i++) {
            $result[] = array("defeito_reclamado" => utf8_encode(pg_fetch_result($res, $i, defeito_reclamado)), "descricao" => utf8_encode(pg_result($res,$i,descricao)));
        }
        exit(json_encode(array("ok" => $result)));
    }else{
        exit(json_encode(array("no" => 'false')));
    }
}

if ($_POST['ajax'] && $_POST['deletar']) {
	$script = $_POST['script'];
	$familia = $_POST['familiax'];
	$tipo = $_POST['tipo'];
	$valor = $_POST['valor'];

	if ($tipo == "defeito_reclamado") {
		$cond = " AND tbl_script_falha.defeito_reclamado = {$valor} ";
	} elseif ($tipo == "produto" AND !empty($valor)) {
		$cond = " AND tbl_script_falha.produto = {$valor}";
	} elseif ($tipo == "produto" AND empty($valor)) {
		$cond = " AND tbl_script_falha.produto IS NULL";
	}

	$result = array();

	$sql = "SELECT 	tbl_script_falha.script_falha
			FROM 	tbl_script_falha
			WHERE tbl_script_falha.fabrica = {$login_fabrica}
			AND tbl_script_falha.script_falha = {$script}
			AND tbl_script_falha.familia = {$familia}
			$cond";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
        $sqlDelete = "DELETE FROM tbl_script_falha WHERE fabrica = {$login_fabrica} AND script_falha = {$script}";
		$resDelete = pg_query($con, $sqlDelete);

		if(pg_last_error()) {
	        echo json_encode(array("retorno" => utf8_encode("Erro ao deletar registro.")));
	    }else{
            echo json_encode(array("retorno" => utf8_encode("success"),"id_script" => utf8_encode($script)));
	    }
    }else{
        echo json_encode(array("retorno" => utf8_encode("Erro ao deletar registro.")));
    }
    exit;
}

$fields = "";
$joins  = "";
$conds  = "";

if (!in_array($login_fabrica, [174])) {
	$fields = " , tbl_defeito_reclamado.descricao AS defeito_descricao
			    , tbl_script_falha.defeito_reclamado ";
	$joins  = " JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_script_falha.defeito_reclamado
				AND tbl_defeito_reclamado.fabrica = {$login_fabrica} ";
} else {
	$fields = ", tbl_produto.descricao AS produto_descricao
			   , tbl_produto.referencia AS produto_referencia
			   , tbl_produto.produto ";
	$joins  = " LEFT JOIN tbl_produto ON tbl_script_falha.produto = tbl_produto.produto 
				AND tbl_produto.fabrica_i = {$login_fabrica} ";
}

if ($_POST['btn_acao'] == "submit") {
	$familia 			= $_POST['familia'];
	$defeito_reclamado 	= $_POST['defeito_reclamado'];
	$produto 			= $_POST['produto'];

	if(strlen($familia)) {
		$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Familia não encontrada";
			$msg_erro["campos"][] = "familia";
		}
	}

	if (in_array($login_fabrica, [174]) AND strlen(trim($produto)) == 0 AND strlen(trim($familia)) > 0) {
		$conds = " AND tbl_script_falha.familia = {$familia} ";
	} elseif (in_array($login_fabrica, [174]) AND strlen(trim($produto)) > 0) {
		$conds = " AND tbl_script_falha.familia = {$familia} AND tbl_script_falha.produto = {$produto} ";
	} elseif (!in_array($login_fabrica, [174]) AND strlen(trim($defeito_reclamado)) > 0 AND strlen(trim($familia)) > 0) {
		$conds = " AND tbl_script_falha.familia = {$familia}
				   AND tbl_script_falha.defeito_reclamado = {$defeito_reclamado} ";
	} elseif (!in_array($login_fabrica, [174]) AND strlen(trim($familia)) > 0 AND strlen(trim($defeito_reclamado)) == 0) {
		$conds = " AND tbl_script_falha.familia = {$familia} ";
	}

	if (!in_array($login_fabrica, [174])) {
		if(strlen($defeito_reclamado)){
			$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado,
							tbl_defeito_reclamado.descricao
					FROM 	tbl_defeito_reclamado
					JOIN 	tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
				 		AND tbl_diagnostico.fabrica = {$login_fabrica}
					WHERE 	tbl_defeito_reclamado.fabrica = {$login_fabrica}
					AND 	tbl_defeito_reclamado.ativo IS TRUE
					AND 	tbl_defeito_reclamado.defeito_reclamado = {$defeito_reclamado}";
			$res = pg_query($con, $sql);
			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Defeito Reclamado não encontrado";
				$msg_erro["campos"][] = "defeito_reclamado";
			}
		}
	}

	if (strlen($produto)) {
		$sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$msg_erro['msg'][] = "Produto não encontrado";
			$msg_erro['campos'][] = "produto";
		}
	}
}

$sql = "SELECT tbl_script_falha.script_falha,
			   tbl_script_falha.familia,
			   tbl_script_falha.json_script,
			   tbl_script_falha.json_execucao_script,
			   tbl_familia.descricao AS familia_descricao,
			   TO_CHAR(tbl_script_falha.data_input, 'DD/MM/YYYY') AS data_input,
			   tbl_produto.linha,
			   tbl_linha.nome as linha_descricao
			   $fields
		FROM tbl_script_falha
		JOIN tbl_familia ON tbl_familia.familia = tbl_script_falha.familia
		LEFT JOIN tbl_produto ON tbl_script_falha.produto = tbl_produto.produto
		LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
		$joins
		WHERE tbl_script_falha.fabrica = {$login_fabrica}
		$conds";
$resSubmit = pg_query($con, $sql);

$layout_menu = "cadastro";
$title = "SCRIPT DE FALHA";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"shadowbox",
	"mask",
	"dataTable",
	"select2"
);

include("plugin_loader.php");
?>
<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function () {
		$("select").select2();
	});

	function deletar(script, tipo, valor = null, familia){
	if (confirm('Deseja excluir o Script de falha ?')) {
		var btn_excluir = $("#"+script).find('.btn-danger');
		var btn_alterar = $("#"+script).find('.btn-info');
		var text = $("#"+script).find('.btn-danger').text();
		$(btn_excluir).prop({disabled: true}).text("Excluindo...");
		$(btn_alterar).hide();

		var obj_datatable = $("#resultado_script_falha").dataTable();
		var id_script = $(btn_excluir).data('id_script');

		$.ajax({
            url: 'pesquisa_script_falha.php',
            type: "POST",
            data: {
            	ajax: 'sim',
            	deletar: 'deletar',
            	script: script,
            	tipo: tipo,
            	valor: valor,
            	familiax: familia
            },
            timeout: 8000
        }).fail(function(){
            alert("Não foi possível excluir o registro, tempo limite esgotado!");
        }).done(function(data){
            data = JSON.parse(data);
            if (data.retorno == "success") {
                $(btn_excluir).text("Excluido");
                setTimeout(function(){
                	$(obj_datatable.fnGetData()).each(function(idx,elem){
                		if (tipo == "defeito_reclamado") {
                			var button = $(elem[3])[2];
                		} else if (tipo == "produto") {
                			var button = $(elem[4])[2];
                		}
                		if($(button).data('id_script') == id_script){
                			obj_datatable.fnDeleteRow(idx);
                			return;
                		}
                	});
                }, 1500);
            }else{
                $(btn_excluir).prop({disabled: false}).text(text);
                $(btn_alterar).show();
                alert("Erro ao excluir Script");
			}
        });
	}else{
		return;
	}
}
</script>

<?php if (!in_array($login_fabrica, [174])) { ?>
<script type="text/javascript">
	$(function() {
		$("#familia").change(function(){
			var familia = $(this).val();
			if(familia.length > 0){
				var defeito_reclamado_id = $("#defeito_reclamado_id").val();
				$.ajax({
	                url: window.location,
	                type: "POST",
	                data: {ajax: 'sim', familia: familia},
	                timeout: 7000
	            }).fail(function(){
	                alert('fail');
	            }).done(function(data){
	                data = JSON.parse(data);
	                if (data.ok !== undefined) {
	                    var option = "<option value=''>Escolha o Defeito Reclamado</option>";
	                    $.each(data.ok, function (key, value) {
	                    	if(value.defeito_reclamado == defeito_reclamado_id){
	                           var selecionar = "selected";
	                        }
	                        option += "<option value='"+value.defeito_reclamado+"' "+selecionar+" >"+value.descricao+"</option>";
	                    });

	                    $('#defeito_reclamado').html(option);
	                }
	            });
	        }else{
	        	$("#defeito_reclamado").html('<option>Selecione</option>');
	        }
		});

		$("#linha").change(function(){
			var linha = $(this).val();
			if(linha.length > 0){
				var defeito_reclamado_id = $("#defeito_reclamado_id").val();
				$.ajax({
	                url: window.location,
	                type: "POST",
	                data: {ajax: 'sim', linha: linha},
	                timeout: 7000
	            }).fail(function(){
	                alert('fail');
	            }).done(function(data){
	                data = JSON.parse(data);
	                if (data.ok !== undefined) {
	                    var option = "<option value=''>Escolha o Defeito Reclamado</option>";
	                    $.each(data.ok, function (key, value) {
	                    	if(value.defeito_reclamado == defeito_reclamado_id){
	                           var selecionar = "selected";
	                        }
	                        option += "<option value='"+value.defeito_reclamado+"' "+selecionar+" >"+value.descricao+"</option>";
	                    });

	                    $('#defeito_reclamado').html(option);
	                }
	            });
	        }else{
	        	$("#defeito_reclamado").html('<option>Selecione</option>');
	        }
		});

		$("#defeito_reclamado").change(function(){
			var defeito_reclamado_id = $(this).val();
			$("#defeito_reclamado_id").val(defeito_reclamado_id);
		});

	});
</script>

<?php } else { ?>
<script type="text/javascript">
	$(function () {
		$("#familia").on("change", function () {
			$("#produto").html("<option value=''>Selecione</option>");
			var familia = $(this).val();

			if (familia.length > 0) {
				$.ajax('pesquisa_script_falha.php', {
					async: true,
					type: 'POST',
					data: {
						searchProd: true,
						familia: familia
					}
				}).done(function (response) {
					response = JSON.parse(response);
					$(response).each(function (index, element) {
						var option = $("<option></option>");
						$(option).val(element.produto);
						$(option).text(element.descricao);

						$("#produto").append(option);
					});
				})
			}
		});
	});
</script>
<?php } ?>

<style type="text/css">
	.btn_link{
		text-decoration: none;
		color: #ffffff;
	}
	.btn_link:hover{
		color: #ffffff;
		text-decoration: none;

	}
</style>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<form name='form_script' id="form_script" METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<!-- FAMILIA/DEFEITOS -->
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<?php
				if (in_array($login_fabrica, [175])) { ?>
					<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='linha'>Linha</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<select name="linha" id="linha">
									<option value="">Selecione</option>
									<?php
										$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica and ativo order by nome";
										$res = pg_query($con,$sql);
										foreach(pg_fetch_all($res) as $key) {
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
							<div class='span2'></div>
						</div>
					</div>
				<?php
				} else { ?>
					<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='familia'>Familia</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<select name="familia" id="familia">
									<option value="">Selecione</option>
									<?php
										$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica and ativo order by descricao";
										$res = pg_query($con,$sql);
										foreach(pg_fetch_all($res) as $key) {
											$selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ;
										?>
											<option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >
												<?php echo $key['descricao']?>
											</option>
										<?php
										}
									?>
								</select>
							</div>
							<div class='span2'></div>
						</div>
					</div>
				<?php
				}
				?>
			</div>
			<?php if (!in_array($login_fabrica, [174])) { ?>
			<div class='span4'>
				<div class='control-group <?=(in_array("defeito_reclamado", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='defeito_reclamado'>Defeito Reclamado</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="defeito_reclamado" id="defeito_reclamado">
								<option value="">Selecione</option>
								<?php
									if(strlen(trim($familia)) > 0){
										$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado,
														tbl_defeito_reclamado.descricao
												FROM 	tbl_defeito_reclamado
												JOIN 	tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
													AND tbl_diagnostico.fabrica = {$login_fabrica}
												WHERE tbl_defeito_reclamado.fabrica = {$login_fabrica}
												AND tbl_defeito_reclamado.ativo IS TRUE
												AND tbl_diagnostico.familia = {$familia}";
										$res = pg_query($con,$sql);
										foreach(pg_fetch_all($res) as $key) {
											$selected_defeito_reclamado = ( isset($defeito_reclamado) and ($defeito_reclamado_id == $key['defeito_reclamado']) ) ? "SELECTED" : '' ;
										?>
											<option value="<?php echo $key['defeito_reclamado']?>" <?php echo $selected_defeito_reclamado ?> >
												<?php echo $key['descricao']?>
											</option>
										<?php
										}
									}
									?>
							</select>
						</div>
						<input type="hidden" name="defeito_reclamado_id" id="defeito_reclamado_id" value="<?=$defeito_reclamado_id?>">
					</div>
				</div>
			</div>
			<?php } else { ?>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="produto">Produto</label>
					<div class="controls controls-row">
						<div class="span4">
							<select name="produto" id="produto">
								<option value="">Selecione</option>
								<?php
								if (strlen(trim($familia)) > 0) {
									$sql = "SELECT tbl_produto.produto,
												   tbl_produto.descricao AS produto_descricao,
												   tbl_produto.referencia AS produto_referencia
											FROM tbl_produto
											JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
											WHERE tbl_produto.fabrica_i = {$login_fabrica}
											AND tbl_produto.familia = {$familia}";
									$res = pg_query($con, $sql);
									foreach (pg_fetch_all($res) as $prod) {
										$selected_produto = (isset($produto) AND ($produto == $prod['produto'])) ? "SELECTED" : "";
										?>
										<option value="<?= $prod['produto'] ?>" <?= $selected_produto ?>><?= $prod['produto_descricao'] ?></option>
										<?php
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>
			<div class='span2'></div>
		</div>
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<button type='button' class='btn btn-primary' onclick="window.open('cadastro_script_falha.php');" >Criar Script</button>
		</p><br/>
</form>
<?php
if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
			$count = pg_num_rows($resSubmit);
		?>
			<table id="resultado_script_falha" class='table table-striped table-bordered table-hover table-fixed' >
				<thead>
					<tr class='titulo_coluna' >
						<?php
						if (in_array($login_fabrica, [175])) { ?>
							<th>Linha</th>
						<?php
						} else { ?>
							<th>Família</th>
						<?php
						}
						?>
						<?= 
							!in_array($login_fabrica, [174]) ? 
							"<th>Defeito Reclamado</th>" : 
							"<th>Produto</th>
							<th>Referência" 
						?>
						<th>Data Cadastro</th>
                        <th>Ações</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $count; $i++) {
						$script_falha 			= pg_fetch_result($resSubmit, $i, 'script_falha');
			        	if (!in_array($login_fabrica, [174])) {
			        		$defeito_reclamado 	= pg_fetch_result($resSubmit, $i, 'defeito_reclamado');
			        		$defeito_descricao 		= pg_fetch_result($resSubmit, $i, 'defeito_descricao');
			        	} else {
			        		$produto 			= pg_fetch_result($resSubmit, $i, 'produto');
			        		$produto_desc 		= pg_fetch_result($resSubmit, $i, 'produto_descricao');
			        		$produto_ref 		= pg_fetch_result($resSubmit, $i, 'produto_referencia');
			        	}
			        	$familia 				= pg_fetch_result($resSubmit, $i, 'familia');
						$familia_descricao 		= pg_fetch_result($resSubmit, $i, 'familia_descricao');
						$data_input 			= pg_fetch_result($resSubmit, $i, 'data_input');
						$linha_descricao        = pg_fetch_result($resSubmit, $i, 'linha_descricao');
						$linha        			= pg_fetch_result($resSubmit, $i, 'linha');
			        ?>
			        	<tr style="vertical-align:middle" id="<?=$script_falha?>">

			        		

			        		<td style="vertical-align:middle">
			        			<?php
			        			if (in_array($login_fabrica, [175])) {
			        				echo $linha_descricao;
			        			} else { 
			        				echo $familia_descricao;
				        		}
				        		?>
			        		</td>
			        		<?php if (!in_array($login_fabrica, [174])) { ?>
			        			<td style="vertical-align:middle"><?= $defeito_descricao ?></td>
			        		<?php } else { ?>
			        			<td style="vertical-align:middle"><?= $produto_desc ?></td>
			        			<td style="vertical-align:middle"><?= $produto_ref ?></td>
			        		<?php } ?>
			        		<td class='tac' style="vertical-align:middle"><?=$data_input?></td>
			        		<td class='tac'>
			        			<?php if (!in_array($login_fabrica, [174])) { 	

	        					if (in_array($login_fabrica, [198])) { ?> 
			        				<a href="cadastro_script_falha.php?script_falha=<?=$script_falha?>&familia=<?=$familia?>&defeito_reclamado=<?=$defeito_reclamado?>&linha=<?= $linha ?>&duplicar=true" class="btn_link" target="_blank"><button type="button" class="btn btn-warning btn-small">Duplicar Script</button></a>
			        			<?php } ?>
			        			<a href="cadastro_script_falha.php?script_falha=<?=$script_falha?>&familia=<?=$familia?>&defeito_reclamado=<?=$defeito_reclamado?>&linha=<?= $linha ?>" class='btn_link' target="_blank"><button type='button' class='btn btn-info btn-small'>Alterar Script</button></a>
			        			<button type='button' data-id_script='<?=$script_falha?>' class='btn btn-danger btn-small' onclick="deletar('<?=$script_falha?>', 'defeito_reclamado', '<?=$defeito_reclamado?>', '<?=$familia?>');">Excluir Script</button>
			        			<?php } else { ?>
			        			<a href="cadastro_script_falha.php?script_falha=<?=$script_falha?>&familia=<?=$familia?>&produto=<?=$produto?>" class="btn_link" target="_blank"><button type="button" class="btn btn-info btn-small">Alterar Script</button></a>
			        			<button type='button' data-id_script='<?=$script_falha?>' class='btn btn-danger btn-small' onclick="deletar('<?=$script_falha?>', 'produto', '<?= !empty($produto) ? $produto : null ?>', '<?=$familia?>');">Excluir Script</button>
			        			<?php } ?>
			        		</td>
			        	</tr>
			        <?php
					}
					?>
				</tbody>
			</table>
			<script>
				$.dataTableLoad({ table: "#resultado_script_falha" });
			</script>
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
?>
<?php include 'rodape.php';?>
