<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
 
$admin_privilegios = "financeiro, gerencia, auditoria";

include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["aprovar"] == true) {
	$os = $_POST["os"];

	if (!empty($os)) {
		$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin) values ({$os}, 19, current_timestamp, 'OS com produto crítico, aprovada', {$login_admin})";
            $res = pg_query ($con, $sql);

            if (strlen(pg_last_error()) > 0) {
            	$retorno = array("erro" => "Erro ao aprovar OS");
            } else {
            	$retorno = array("aprovado" => true);
            }
		} else {
			$retorno = array("erro" => utf8_encode("OS não encontrada"));
		}
	} else {
		$retorno = array("erro" => "Nenhuma OS selecionada");
	}

	echo json_encode($retorno);
	exit;
}

if ($_POST["reprovar"] == true) {
	$os             = $_POST["os"];
	$motivo_reprova = utf8_decode($_POST["motivo_reprova"]);
	$msgErro = "";

	if (!empty($os) && strlen($motivo_reprova) > 0) {
		$sql = "SELECT posto, sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$posto = pg_fetch_result($res, 0, "posto");
			$sua_os = pg_fetch_result($res, 0, "sua_os");

			$res = pg_query($con, "BEGIN");

			$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin) values ({$os}, 13, current_timestamp, 'Os em intervenção de produto crítico foi recusada. Motivo: {$motivo_reprova}', {$login_admin})";
            $res = pg_query ($con, $sql);

            if (strlen(pg_last_error()) > 0) {
            	$retorno = array("erro" => "Erro ao reprovar OS");
            	$msgErro = "erro";
            	$res = pg_query($con, "ROLLBACK");
            } else {

            	if ($login_fabrica == 91) {
            		$sql = "UPDATE tbl_os SET data_fechamento = current_timestamp WHERE os = $os AND fabrica = $login_fabrica";
            		$res = pg_query($con, $sql);
            		if (!pg_last_error()) {
	            		$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
	                    $res = pg_query($con,$sql);
	                    if (pg_last_error()) {
	                    	$retorno = array("erro" => "Erro ao finalizar OS ! Verificar se a mesma não está pendente de outras Auditorias.");
	                    	$msgErro = "erro";
	                    	$res = pg_query($con, "ROLLBACK");
	                    }
            		} else {
        				$retorno = array("erro" => "Erro ao finalizar OS");
        				$msgErro = "erro";
                    	$res = pg_query($con, "ROLLBACK");
            		}
            	}

            	if (empty($msgErro)) {
	            	$sql = "INSERT INTO tbl_comunicado (
								fabrica,
								posto,
								obrigatorio_site,
								tipo,
								ativo,
								descricao,
								mensagem
							) VALUES (
								{$login_fabrica},
								{$posto},
								true,
								'Com. Unico Posto',
								true,
								'Os {$sua_os} que estava em intervenção de produto crítico foi recusada.',
								'{$motivo_reprova}'
							)";
					$res = pg_query($con, $sql);

	            	$retorno = array("reprovado" => true);
	            	$res = pg_query($con, "COMMIT");
            	}
            }
		} else {
			$retorno = array("erro" => utf8_encode("OS não encontrada"));
		}
	} else {
		$retorno = array("erro" => "Nenhuma OS selecionada");
	}

	echo json_encode($retorno);
	exit;
}

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$os                 = $_POST['os'];

	if (strlen($codigo_posto) > 0 || strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (empty($os)) {
		if (!strlen($data_inicial) || !strlen($data_final)) {
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "data";
		} else {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);

			if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
				$msg_erro["msg"][]    = "Data Inválida";
				$msg_erro["campos"][] = "data";
			} else {
				$aux_data_inicial = "{$yi}-{$mi}-{$di}";
				$aux_data_final   = "{$yf}-{$mf}-{$df}";

				if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
					$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
					$msg_erro["campos"][] = "data";
				} else {
					if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final) ) {
						$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 1 mês";
						$msg_erro["campos"][] = "data";
					} 
				}
			}
		}
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			//$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		if (!empty($os)) {
			$cond_os = " AND tbl_os.sua_os = '{$os}' ";
		}

		if (empty($os)) {
            if($login_fabrica != 91){
                $cond_data = " AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
			}else{
                $cond_data = " AND tbl_os.data_digitacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
            }
		}

		$sql = "SELECT
					tbl_os.os,
					tbl_os.sua_os,
					TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS posto,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto
				FROM tbl_os
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
				WHERE tbl_os.fabrica = {$login_fabrica}
				{$cond_data}
				{$cond_posto}
				{$cond_os}
				AND tbl_os.os IN (
					SELECT interv_reinc.os
					FROM (
						SELECT
						ultima_reinc.os,
						(
							SELECT status_os 
							FROM tbl_os_status 
							WHERE tbl_os_status.os = ultima_reinc.os 
							AND   tbl_os_status.fabrica_status = $login_fabrica
							AND   status_os IN (178, 13, 19)
							ORDER BY os_status DESC LIMIT 1
						) AS ultimo_reinc_status

						FROM (
							SELECT DISTINCT os 
							FROM tbl_os_status 
							WHERE tbl_os_status.fabrica_status = $login_fabrica
							AND status_os IN (178, 13, 19)
						) ultima_reinc
					) interv_reinc
					WHERE interv_reinc.ultimo_reinc_status IN (178)
				)";
		$resSubmit = pg_query($con, $sql);
	}
}

$layout_menu = "auditoria";
$title       = "AUDITORIA DE OS COM PRODUTO CRÍTICO";

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"mask",
	"shadowbox"
);

include("plugin_loader.php");
?>

<script>
	$(function () {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init({
			enableKeys: false
		});

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("button[name=aprovar]").click(function () {
			var td = $(this).parents("td");
			var os = $(td).find("input[name=os]").val();


			$.ajax({
				async: true,
				url: "auditoria_os_produto_critico.php",
				type: "POST",
				data: { aprovar: true, os: os },
				beforeSend: function () {
					$(td).find("div[name=aprovar_reprovar]").hide();
					$(td).find("div[name=loading]").show();
				},
				complete: function (data) {
					data = $.parseJSON(data.responseText);

					if (data.erro) {
						alert(data.erro);
						$(td).find("div[name=loading]").hide();
						$(td).find("div[name=aprovar_reprovar]").show();
					} else {
						$(td).html("<h5 style='color: #468847; margin-top: 0px; margin-bottom: 0px;' >Aprovado</h5>");
					}
				}
			});
		});

		var td;
		var sua_os;
		var os;

		$("button[name=reprovar]").click(function () {
			td     = $(this).parents("td");
			os     = $(td).find("input[name=os]").val();
			sua_os = $(td).find("input[name=sua_os]").val();

			reprova();
		});

		$(document).on("click", "button[name=cancelar_reprovacao]", function () {
			Shadowbox.close();
			Shadowbox.options.modal = false;
			$("#sb-nav").css({ display: "block" });
		});

		$(document).on("click", "button[name=prosseguir_reprovacao]", function () {
			var motivo_reprova = $.trim($("textarea[name=motivo_reprova]").val());

			if (motivo_reprova.length == 0) {
				alert("Digite um motivo para a reprovação da OS");
			} else {
				$.ajax({
					async: true,
					url: "auditoria_os_produto_critico.php",
					type: "POST",
					data: { reprovar: true, os: os, motivo_reprova: motivo_reprova },
					beforeSend: function () {
						Shadowbox.close();
						Shadowbox.options.modal = false;
						$("#sb-nav").css({ display: "block" });

						$(td).find("div[name=aprovar_reprovar]").hide();
						$(td).find("div[name=loading]").show();
					},
					complete: function (data) {
						data = $.parseJSON(data.responseText);

						if (data.erro) {
							alert(data.erro);
							$(td).find("div[name=loading]").hide();
							$(td).find("div[name=aprovar_reprovar]").show();
						} else {
							$(td).html("<h5 style='color: #B94A48; margin-top: 0px; margin-bottom: 0px;' >Reprovada</h5>");
						}
					}
				});

			}
		});

		function reprova () {
			var div_motivo_reprova = $("#motivo_reprova").clone();
			$(div_motivo_reprova).find("span[name=os]").text(sua_os);
			$(div_motivo_reprova).find("textarea[name=motivo_reprova_model]").attr({ name: "motivo_reprova" });

			Shadowbox.options.modal = true;

			Shadowbox.open({
				content: $(div_motivo_reprova).html(),
				player: "html",
				width: 350,
				height: 150
			});

			$("#sb-nav").css({ display: "none" });
		}
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
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

<div id="motivo_reprova" style="display: none;" >
	<div style="margin: 0 auto; margin-top: 15px; width: 300px;">
		<b>Digite o motivo da reprovação da OS: <span name="os"></span></b><br />
		<textarea name="motivo_reprova_model" style="width: 300px; height: 50px;"></textarea><br />
		<span style="text-align: center; width: 100%; display: inline-block;">
			<button type="button" name="prosseguir_reprovacao" class="btn btn-danger" >Confirmar</button>
			<button type="button" name="cancelar_reprovacao" class="btn btn-warning" >Cancelar</button>
		</span>
	</div>
</div>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
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
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>Código Posto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
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
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span8'>
			<div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='os'>Número da OS</label>
				<div class='controls controls-row'>
					<div class='span4'>
							<input type="text" name="os" id="os" size="12" maxlength="10" class='span12' value= "<?=$os?>">
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<?php

if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
	?>
		<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class='titulo_coluna' >
					<th>OS</th>
					<th nowrap >Data de Abertura</th>
					<th>Posto</th>
					<th>Produto</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$rows = pg_num_rows($resSubmit);

				for ($i = 0; $i < $rows; $i++) {
					$os            = pg_fetch_result($resSubmit, $i, "os");
					$sua_os        = pg_fetch_result($resSubmit, $i, "sua_os");
					$data_abertura = pg_fetch_result($resSubmit, $i, "data_abertura");
					$posto         = pg_fetch_result($resSubmit, $i, "posto");
					$produto       = pg_fetch_result($resSubmit, $i, "produto");
					?>

					<tr>
						<td class="tac"><a href="os_press.php?os=<?=$os?>" target="_blank" ><?=$sua_os?></a></td>
						<td class="tac"><?=$data_abertura?></td>
						<td class="tac"><?=$posto?></td>
						<td class="tac"><?=$produto?></td>
						<td class="tac" nowrap >
							<input type="hidden" name="os" value="<?=$os?>" />
							<input type="hidden" name="sua_os" value="<?=$sua_os?>" />
							<div name="aprovar_reprovar" >
								<button type="button" class="btn btn-small btn-success" name="aprovar" >Aprovar</button>
								<button type="button" class="btn btn-small btn-danger" name="reprovar" >Reprovar</button>
							</div>
							<div name="loading" style="display: none;" >
								<img src="imagens/loading_img.gif" style="width: 32px; height: 32px;" />
							</div>
						</td>
					</tr>

				<?php
				}
				?>
			</tbody>
		</table>
	<?php
	} else {
	?>
		<div class="container" >
			<div class="alert" >
				<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
}

include 'rodape.php';
?>
