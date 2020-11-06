<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
if(in_array($login_fabrica, [189])) {
	$label_origem = "Depto. Gerador da RRC";
} else {
	$label_origem = "Origem";
}
if ($_POST["btn_acao"] == "excluir") {

	$xorigem_admin = $_POST["origem_admin"];
	$sql = "SELECT hd_origem_admin FROM tbl_hd_origem_admin WHERE hd_origem_admin = {$xorigem_admin} AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		$sql = "DELETE FROM tbl_hd_origem_admin WHERE fabrica = {$login_fabrica} AND hd_origem_admin = {$xorigem_admin}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			echo json_encode(array("retorno" => utf8_encode("success"),"id_origem" => utf8_encode($xorigem_admin)));
		} else {
			echo json_encode(array("retorno" => utf8_encode("Erro ao deletar registro.")));
		}
	}else{
		echo json_encode(array("retorno" => utf8_encode("Registro não encontrado.")));
	}
	exit;
}

if ($_POST["btn_acao"] == "submit") {
	$atendente	= $_POST['atendente'];
	$origem 	= $_POST['origem'];

	if (in_array($login_fabrica, [174, 186])) {
		$queryOrigem = "SELECT
							descricao
						FROM tbl_hd_chamado_origem
						WHERE hd_chamado_origem = {$origem};";
		$result = pg_query($con, $queryOrigem);

		$resultOrigem = pg_fetch_result($result, 0, 'descricao');
		if (strtolower($resultOrigem) === "mercado livre") {
			if (!array_key_exists('referencia_pre', $_POST) || !array_key_exists('referencia_pos', $_POST)) {
				$msg_erro["msg"][] = "Selecione uma referência.";
			} else {
				$referencias = [];
				if (array_key_exists('referencia_pre', $_POST)) {
					$referencias['referencia_pre'] = $_POST['referencia_pre'];
				}

				if (array_key_exists('referencia_pos', $_POST)) {
					$referencias['referencia_pos'] = $_POST['referencia_pos'];
				}
			}
		}
	}

	if(empty($atendente)){
		$msg_erro["msg"][]    = "Selecione um atendente.";
		$msg_erro["campos"][] = "atendente";
	}else{
		$sql = "SELECT admin
				FROM tbl_admin
				WHERE fabrica = $login_fabrica
				AND admin = {$atendente}
				AND (callcenter_supervisor IS TRUE OR atendente_callcenter IS TRUE)
				AND ativo";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$xatendente = pg_fetch_result($res, 0, 'admin');
		}else{
			$msg_erro["msg"][] = "Atendente não encontrado.";
		}
	}


	if(empty($origem)){
		$msg_erro["msg"][]    = "Selecione uma {$label_origem}.";
		$msg_erro["campos"][] = "origem";
	}else{
		$sql = "SELECT hd_chamado_origem
					FROM tbl_hd_chamado_origem
					WHERE fabrica = $login_fabrica
					AND hd_chamado_origem = {$origem}
					AND ativo IS TRUE";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$xorigem = pg_fetch_result($res, 0, 'hd_chamado_origem');
		}else{
			$msg_erro["msg"][] = "Origem não encontrada.";
		}
	}

	$sql = "
		SELECT
			hd_origem_admin
		FROM tbl_hd_origem_admin
		WHERE admin = {$xatendente}
		AND hd_chamado_origem = {$xorigem}
		AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0 && !(in_array($login_fabrica, [174, 186]) && strtolower($resultOrigem) === "mercado livre")) {
		$msg_erro["msg"][] = "Já existe essa Origem cadastrada para esse Atendente.";
	} else {
		if (!count($msg_erro["msg"])) {
			pg_query($con, "BEGIN");
	
			if (count($referencias) >= 1) {
				foreach ($referencias as $key => $val) {
					$sql = "INSERT INTO tbl_hd_origem_admin (
						fabrica,
						admin,
						hd_chamado_origem,
						tipo_venda
					) VALUES (
						{$login_fabrica},
						{$xatendente},
						{$xorigem},
						'{$val}'
					)";

					$res = pg_query($con, $sql);
				}
			} else {
				$sql = "INSERT INTO tbl_hd_origem_admin (
					fabrica,
					admin,
					hd_chamado_origem,
					tipo_venda
				) VALUES (
					{$login_fabrica},
					{$xatendente},
					{$xorigem},
					null
				)";

				$res = pg_query($con, $sql);
			}
			
			if (strlen(pg_last_error()) > 0) {
				pg_query($con, "ROLLBACK");
				$msg_erro["msg"][] = "Erro ao gravar dados.";
			} else {
				pg_query($con, "COMMIT");
				$msg_success = "Gravado com sucesso.";
			}
		}
	}
}

$sql = "SELECT 		tbl_hd_origem_admin.hd_origem_admin,
					tbl_hd_chamado_origem.descricao,
					tbl_admin.nome_completo,
					tbl_hd_origem_admin.tipo_venda,
					tbl_admin.admin
			FROM 	tbl_hd_origem_admin
			JOIN    tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_origem_admin.hd_chamado_origem
			AND 	tbl_hd_chamado_origem.fabrica = $login_fabrica
			JOIN  	tbl_admin ON tbl_admin.admin = tbl_hd_origem_admin.admin AND tbl_admin.fabrica = $login_fabrica
			WHERE 	tbl_hd_origem_admin.fabrica = $login_fabrica ";
$resSubmit = pg_query($con, $sql);

$layout_menu = "cadastro";
$title = "Atendente Callcenter x {$label_origem}";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
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

	$(function() {
		$("select").select2();
	});


	$(document).on('click','button.excluir', function(){
		if (confirm('Deseja excluir o registro ?')) {
			var btn = $(this);
	        var text = $(this).text();
	        var origem_admin = $(btn).data('origem_admin');
	        var obj_datatable = $("#resultado").dataTable()

	        $(btn).prop({disabled: true}).text("Excluindo...");
	        $.ajax({
	            method: "POST",
	            url: "<?=$_SERVER['PHP_SELF']?>",
	            data: { btn_acao: 'excluir', origem_admin: origem_admin},
	            timeout: 8000
	        }).fail(function(){
	        	//$(".btn-small").parent('.tac').html("dsadasdasdas");
	        	alert("Não foi possível excluir o registro, tempo limite esgotado!");
	        }).done(function(data) {
	            data = JSON.parse(data);
	            if (data.retorno == "success") {
	                $(btn).text("Excluido");
	                setTimeout(function(){
	                	$(obj_datatable.fnGetData()).each(function(idx,elem){
	                		if($(elem[2]).data('origem_admin') == origem_admin){
	                			obj_datatable.fnDeleteRow(idx);
	                			return;
	                		}
	                	});
	                }, 1000);
	            }else{
	                $(btn).prop({disabled: false}).text(text);
				}
	        });
	    }else{
	    	return false;
	    }
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

if(strlen($msg_success) > 0){
?>
	<div class="alert alert-success">
		<h4><?=$msg_success?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='atendente'>Atendente</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<select name="atendente" id="atendente">
								<option value=""></option>
								<?php
								$sql = "SELECT admin, nome_completo
										FROM tbl_admin
										WHERE fabrica = $login_fabrica
										AND (callcenter_supervisor IS TRUE OR atendente_callcenter IS TRUE)
										AND ativo";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_atendente = ( isset($atendente) and ($atendente == $key['admin']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['admin']?>" <?php echo $selected_atendente ?> >
										<?php echo $key['nome_completo']?>
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
				<div class='control-group <?=(in_array("origem", $msg_erro["campos"])) ? "error" : ""?>'>
					
					<label class='control-label' for='origem'><?php echo $label_origem;?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<select name="origem" id="origem">
								<option value=""></option>
								<?php

									$sql = "SELECT hd_chamado_origem, descricao FROM tbl_hd_chamado_origem WHERE fabrica = $login_fabrica and ativo IS TRUE order by descricao";
									$res = pg_query($con,$sql);
									foreach (pg_fetch_all($res) as $key) {
										$selected_origem = ( isset($origem) and ($origem == $key['hd_chamado_origem']) ) ? "SELECTED" : '' ;
									?>
										<option value="<?php echo $key['hd_chamado_origem']?>" <?php echo $selected_origem ?> >
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
			</div>
		</div>
		<?php if (in_array($login_fabrica, [174, 186])) { ?>
		<div class="row-fluid ref-ml" style="display:none;">
			<div class="span4"></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="referencia">Referência</label>
					<div class="controls controls-row">
						<div class="span12 alert alert-warning" style="text-align:center">
							<h5 class="asteristico">*</h5>
							<input
								style="margin:0 10px"
								type="checkbox"
								<?= ($_POST['referencia_pre'] == "PRE") ? "checked" : "" ?>
								value="PRE"
								name="referencia_pre"
							> Pré-Venda
							<input
								style="margin:0 10px"
								type="checkbox"
								<?= ($_POST['referencia_pos'] == "POS") ? "checked" : "" ?>
								value="POS"
								name="referencia_pos"
							> Pós-Venda
						</div>
					</div>
				</div>
			</div>
			<div class="span4"></div>
		</div>
		<script type="text/javascript">
			$(function () {
				if ($("#origem option:selected").html().match(/.Mercado Livre.*/)) {
					$(".ref-ml").fadeIn(500);
				}

				$("#origem").on("change", function () {
					if ($("#origem option:selected").html().match(/.Mercado Livre.*/)) {
						$(".ref-ml").fadeIn(500);
					}
				});
			});
		</script>
		<?php } ?> 
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
		</p><br/>
</form>

<?php
if (isset($resSubmit)) {

		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";
			$count = pg_num_rows($resSubmit);
		?>
			<table id="resultado" class='table table-striped table-bordered table-hover table-fixed' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Atendente</th>
						<th><?php echo $label_origem;?></th>
						<?php if(!in_array($login_fabrica, [189])) {?>
						<th>Referência</th>
						<?php }?>
						<th>Ação</th>
                    </tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $count; $i++) {
						$atendente      = pg_fetch_result($resSubmit, $i, 'nome_completo');
						$origem_admin 	= pg_fetch_result($resSubmit, $i, 'hd_origem_admin');
						$tipo_venda  	= pg_fetch_result($resSubmit, $i, 'tipo_venda');
						$descricao      = pg_fetch_result($resSubmit, $i, 'descricao');
						$id_admin  		= pg_fetch_result($resSubmit, $i, 'admin');
					?>
						<tr id='<?=$origem_admin?>'>
								<td style='vertical-align: middle;'><?=$atendente?></td>
								<td style='vertical-align: middle;'><?=$descricao?></td>
								<?php if(!in_array($login_fabrica, [189])) {?>
								<td style='vertical-align: middle;'>
									<?php if ($tipo_venda == "PRE") {
										echo "Pré-Venda";
									} elseif ($tipo_venda == "POS") {
										echo "Pós-Venda";
									} ?>
								</td>
								<?php  }?>
								<td style='vertical-align: middle;' class='tac'>
									<button class='btn btn-danger btn-small excluir' data-origem_admin='<?=$origem_admin?>'>Excluir</button>
								</td>
						</tr>
					<?php
					}
					?>
				</tbody>
			</table>

			<script>
				$.dataTableLoad({ table: "#resultado" });
			</script>
			<br />
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
