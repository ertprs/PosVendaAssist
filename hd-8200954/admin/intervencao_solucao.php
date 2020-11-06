<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia, auditoria";

include "autentica_admin.php";
include "funcoes.php";

$programa_insert = $_SERVER['PHP_SELF'];

if ($_POST["interagir"] == true) {
	$interacao = utf8_decode(trim($_POST["interacao"]));
	$os        = $_POST["os"];

	if (!strlen($interagir)) {
		$retorno = array("erro" => utf8_encode("Digite a interação"));
	} else if (empty($os)) {
		$retorno = array("erro" => utf8_encode("OS não informada"));
	} else {
		$select = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$result = pg_query($con, $select);

		if (!pg_num_rows($result)) {
			$retorno = array("erro" => utf8_encode("OS não encontrada"));
		} else {
			$insert = "INSERT INTO tbl_os_interacao 
					   (programa,os, admin, fabrica, comentario)
					   VALUES
					   ({'$programa_insert'},{$os}, {$login_admin}, {$login_fabrica}, '{$interacao}')";
			$result = pg_query($con, $insert);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => utf8_encode("Erro ao interagir na OS"));
			} else {
				$retorno = array("ok" => true);
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_POST["recusar"] == true) {
	$motivo = trim($_POST["motivo"]);
	$os     = $_POST["os"];

	if (!strlen($motivo)) {
		$retorno = array("erro" => utf8_encode("Informe o motivo"));
	} else if (empty($os)) {
		$retorno = array("erro" => utf8_encode("OS não informada"));
	} else {
		$select = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$result = pg_query($con, $select);

		if (!pg_num_rows($result)) {
			$retorno = array("erro" => utf8_encode("OS não encontrada"));
		} else {
			pg_query($con, "BEGIN");

			$insert = "INSERT INTO tbl_os_status
					   (os, status_os, observacao, admin)
					   VALUES
					   ({$os}, 210, '{$motivo}', {$login_admin})";
			$result = pg_query($con, $insert);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => utf8_encode("Erro ao recusar"));
			} else {
				$select = "SELECT posto, sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
				$result = pg_query($con, $select);

				$os_posto = pg_fetch_result($result, 0, "posto");
				$sua_os   = pg_fetch_result($result, 0, "sua_os");

				$insert = "INSERT INTO tbl_comunicado (
								fabrica,
								posto,
								obrigatorio_site,
								tipo,
								ativo,
								descricao,
								mensagem
							) VALUES (
								{$login_fabrica},
								{$os_posto},
								true,
								'Com. Unico Posto',
								true,
								'OS {$sua_os} recusada na Intervenção de Solução',
								'{$motivo}'
							)";
				$result = pg_query($con, $insert);

				if (strlen(pg_last_error()) > 0) {
					$retorno = array("erro" => utf8_encode("Erro ao recusar"));
				} else {
					$sql = "SELECT fn_os_excluida($os, $login_fabrica, $login_admin)";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$retorno = array("erro" => utf8_encode(pg_last_error()));
					} else {
						$sql = "UPDATE tbl_os_excluida SET motivo_exclusao = '{$motivo}' WHERE os = {$os} AND fabrica = {$login_fabrica}";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							$retorno = array("erro" => utf8_encode("Erro ao recusar"));
						} else {
							$retorno = array("ok" => true);
						}
					}
				}
			}

			if (isset($retorno["erro"])) {
				pg_query($con, "ROLLBACK");
			} else if (isset($retorno["ok"])) {
				pg_query($con, "COMMIT");
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_POST["aprovar"] == true) {
	$os   = $_POST["os"];

	if (empty($os)) {
		$retorno = array("erro" => utf8_encode("OS não informada"));
	} else {
		$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$retorno = array("erro" => utf8_encode("OS não encontrada"));
		} else {
			$insert = "INSERT INTO tbl_os_status 
					   (os, status_os, observacao, admin)
					   VALUES
					   ({$os}, 209, 'OS aprovada pela fábrica', {$login_admin})";
			$res = pg_query($con, $insert);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => utf8_encode("Erro ao aprovar"));
			} else {
				$retorno = array("ok" => true);
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "submit" || $_POST['btn_acao'] == "listar_todas") {
	$data_inicial    = $_POST['data_inicial'];
	$data_final      = $_POST['data_final'];
	$codigo_posto    = $_POST['codigo_posto'];
	$descricao_posto = $_POST['descricao_posto'];
	$status          = $_POST['status'];
	$listar_todas    = ($_POST['btn_acao'] == "listar_todas") ? "1" : "";

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
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

	if ((!strlen($data_inicial) or !strlen($data_final)) AND empty($listar_todas)) {

		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else if(empty($listar_todas)){
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di} 00:00:00";
			$aux_data_final   = "{$yf}-{$mf}-{$df} 23:59:59";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			} else if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final)) {
				$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 1 mês";
				$msg_erro["campos"][] = "data";
			} else {
				$cond_data = " AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}' ";
			}
		}
	}

	if (empty($status)) {
		$msg_erro["msg"][] = "Selecione um status";
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			#$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		switch ($status) {
			case 'aprovadas':
				$cond_status = 209;
				break;
			
			default:
				$cond_status = 167;
				break;
		}

		$select = "SELECT
						tbl_os.os,
						tbl_os.sua_os,
						TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura
					FROM tbl_os
					INNER JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					WHERE tbl_os.fabrica = {$login_fabrica}
					$cond_data
					AND (
						SELECT tbl_os_status.status_os 
						FROM tbl_os_status 
						WHERE tbl_os_status.os = tbl_os.os 
						AND tbl_os_status.status_os IN(167,209,210) 
						ORDER BY tbl_os_status.data DESC 
						LIMIT 1
					) = {$cond_status}
					{$cond_posto}
					ORDER BY tbl_os.data_abertura ASC";
		$resSubmit = pg_query($con, $select);
	}
}

$layout_menu = "auditoria";
$title = "OS COM INTERVENÇÃO POR SOLUÇÃO";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask"
);

include("plugin_loader.php");

function ultima_interacao($os) {
	global $con, $login_fabrica;

	$select = "SELECT admin, posto FROM tbl_os_interacao WHERE fabrica = {$login_fabrica} AND os = {$os} ORDER BY data DESC LIMIT 1";
	$result = pg_query($con, $select);

	if (pg_num_rows($result) > 0) {
		$admin = pg_fetch_result($result, 0, "admin");
		$posto = pg_fetch_result($result, 0, "posto");

		if (!empty($admin)) {
			$ultima_interacao = "fabrica";
		} else {
			$ultima_interacao = "posto";
		}
	}

	return $ultima_interacao;
}

?>

<style>

.legenda {
	display: inline-block;
	width: 36px;
	height: 18px;
	border-radius: 3px;
}

</style>

<script>

$(function() {
	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("posto"));
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("button[name=interagir]").click(function() {
		var os = $(this).attr("rel");

		if (os != undefined && os.length > 0) {
			Shadowbox.open({
				content: $("#DivInteragir").html().replace(/__NumeroOs__/, os),
				player: "html",
				height: 135,
				width: 400,
				options: {
					enableKeys: false
				}
			});
		}
	});

	$(document).on("click", "button[name=button_interagir]", function() {
		var os        = $(this).attr("rel");
		var interacao = $.trim($("#sb-container").find("textarea[name=text_interacao]").val());

		if (interacao.length == 0) {
			alert("Digite a interação");
		} else if (os != undefined && os.length > 0) {
			$.ajax({
				url: "intervencao_solucao.php",
				type: "post",
				data: { interagir: true, interacao: interacao, os: os },
				beforeSend: function() {
					$("#sb-container").find("div.conteudo").hide();
					$("#sb-container").find("div.loading").show();
				}
			}).always(function(data) {
				data = $.parseJSON(data);

				if (data.erro) {
					alert(data.erro);
				} else {
					$("button[name=interagir][rel="+os+"]").parents("tr").find("td").css({ "background-color": "#FFDC4C" });
					Shadowbox.close();
				}

				$("#sb-container").find("div.loading").hide();
				$("#sb-container").find("div.conteudo").show();
			});
		} else {
			alert("Erro ao interagir na OS");
		}
	});

	$("button[name=recusar]").click(function() {
		var os = $(this).attr("rel");

		if (os != undefined && os.length > 0) {
			Shadowbox.open({
				content: $("#DivRecusar").html().replace(/__NumeroOs__/, os),
				player: "html",
				height: 135,
				width: 400,
				options: {
					enableKeys: false
				}
			});
		}
	});

	$(document).on("click", "button[name=button_recusar]", function() {
		var os     = $(this).attr("rel");
		var motivo = $.trim($("#sb-container").find("textarea[name=text_motivo]").val());

		if (motivo.length == 0) {
			alert("Informe o motivo");
		} else if (os != undefined && os.length > 0) {
			$.ajax({
				url: "intervencao_solucao.php",
				type: "post",
				data: { recusar: true, motivo: motivo, os: os },
				beforeSend: function() {
					$("#sb-container").find("div.conteudo").hide();
					$("#sb-container").find("div.loading").show();
				}
			}).always(function(data) {
				data = $.parseJSON(data);

				if (data.erro) {
					alert(data.erro);
				} else {
					$("button[name=recusar][rel="+os+"]").parents("tr").find("td").last().html("<div class='alert alert-danger tac' style='margin-bottom: 0px;' >OS recusada</div>");
					Shadowbox.close();
				}

				$("#sb-container").find("div.loading").hide();
				$("#sb-container").find("div.conteudo").show();
			});
		} else {
			alert("Erro ao recusar OS");
		}
	});

	$("button[name=aprovar]").click(function() {
		var os = $(this).attr("rel");
		var td = $(this).parent("td");
		var loading = "<div class='loading tac' ><img src='imagens/loading_img.gif' style='height: 18px; width: 18px;' /></div>";

		if (os != undefined && os.length > 0) {
			$.ajax({
				url: "intervencao_solucao.php",
				type: "post",
				data: { aprovar: true, os: os },
				beforeSend: function() {
					$(td).find("button").hide();
					$(td).append(loading);
				}
			}).always(function(data) {
				data = $.parseJSON(data);

				if (data.erro) {
					alert(data.erro);
					$(td).find("div.loading").remove();
					$(td).find("button").show();
				} else {
					$(td).html("<div class='alert alert-success tac' style='margin-bottom: 0px;'>OS aprovada</div>");
				}
			});
		}
	});
});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

</script>

<div id="DivInteragir" style="display: none;" >
	<div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
	<div class="conteudo" >
		<div class="titulo_tabela" >Interagir na OS</div>

		<div class="row-fluid">
			<div class="span12">
				<div class="controls controls-row">
					<textarea name="text_interacao" class="span12"></textarea>
				</div>
			</div>
		</div>
		
		<p><br/>
			<button type="button" name="button_interagir" class="btn btn-primary btn-block" rel="__NumeroOs__" >Interagir</button>
		</p><br/>
	</div>
</div>

<div id="DivRecusar" style="display: none;" >
	<div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
	<div class="conteudo" >
		<div class="titulo_tabela" >Informe o Motivo</div>

		<div class="row-fluid">
			<div class="span12">
				<div class="controls controls-row">
					<textarea name="text_motivo" class="span12"></textarea>
				</div>
			</div>
		</div>
		
		<p><br/>
			<button type="button" name="button_recusar" class="btn btn-block btn-danger" rel="__NumeroOs__" >Recusar</button>
		</p><br/>
	</div>
</div>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_auditoria_os_troca_produto" method="post" action="<?=$_SERVER['PHP_SELF']?>" class="form-search form-inline tc_formulario" style="margin: 0 auto;" >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br />

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
			<label class='control-label'>Status</label>
			<div class='controls controls-row'>
				<div class='span4'>
					 <label class="radio">
				        <input type="radio" name="status" value="aguardando_aprovacao" checked />
				    	Aguardando Aprovação
				    </label>
				</div>
				<div class='span3'>
				    <label class="radio">
				        <input type="radio" name="status" value="aprovadas" <?php if($status == "aprovadas") echo "checked"; ?> />
				        Aprovadas
				    </label>
				</div>
			</div>
		</div>

		<div class='span2'></div>
	</div>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>

		<?php
		$display_listar_todas = (!strlen($status) || in_array($status, array("aprovadas", "aguardando_aprovacao"))) ? "style='display: inline-block;'" : "style='display: none;'";
		?>
		<button class='btn btn-info' id="listar_todas" type="button" <?=$display_listar_todas?> onclick="submitForm($(this).parents('form'),'listar_todas');">Listar Todas</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

</div>

<br />

<?php 
if (isset($resSubmit)) { 
	if (pg_num_rows($resSubmit) > 0) {
		$count = pg_num_rows($resSubmit);
		?>
		<br />

		<table id="resultado" class='table table-striped table-bordered table-hover table-large' style="margin: 0 auto;" >
			<thead>
				<tr>
					<td colspan="6" >
						<span class="legenda" style="background-color: #FFDC4C; vertical-align: middle; margin-right: 5px;" ></span>Fábrica interagiu<br />
						<span class="legenda" style="background-color: #A6D941; vertical-align: middle; margin-right: 5px;" ></span>Posto interagiu<br />
					</td>
				</tr>
				<tr class='titulo_coluna' >
					<th>OS</th>
					<th>Abertura</th>
					<th>Produto</th>
					<th>Solução</th>
					<th>Valor Adicional</th>
					<?php
					if ($status == "aguardando_aprovacao") {
					?>
						<th>Ações</th>
					<?php
					}
					?>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $count; $i++) {
					$os            = pg_fetch_result($resSubmit, $i, "os");
					$sua_os        = pg_fetch_result($resSubmit, $i, "sua_os");
					$data_abertura = pg_fetch_result($resSubmit, $i, "data_abertura");
					$ressarcimento = pg_fetch_result($resSubmit, $i, "ressarcimento");

					$ultima_interacao = ultima_interacao($os);

					switch ($ultima_interacao) {
						case "fabrica":
							$cor = "#FFDC4C";
							break;

						case "posto":
							$cor = "#A6D941";
							break;
						
						default:
							$cor = "#FFFFFF";
							break;
					}

					$sqlOsProduto = "SELECT tbl_produto.descricao AS produto
									 FROM tbl_os_produto
									 INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
									 WHERE tbl_os_produto.os = {$os}";
					$resOsProduto = pg_query($con, $sqlOsProduto);

					$sqlSolucao = "SELECT tbl_solucao.descricao AS solucao
								   FROM tbl_os_defeito_reclamado_constatado
								   INNER JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os_defeito_reclamado_constatado.solucao
								   WHERE tbl_os_defeito_reclamado_constatado.os = {$os}";
					$resSolucao = pg_query($con, $sqlSolucao);

					$sqlValorAdicional = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
					$resValorAdicional = pg_query($con, $sqlValorAdicional);
					?>

					<tr os="<?=$os?>" >
						<td class="tac" style="background-color: <?=$cor?>;" ><a href="os_press.php?os=<?=$os?>" target="_blank" ><?=$sua_os?></a></td>
						<td class="tac" style="background-color: <?=$cor?>;" ><?=$data_abertura?></td>
						<td class="tal" style="background-color: <?=$cor?>;" nowrap >
							<ul style="list-style-type: none;" >
							<?php
							while ($osProduto = pg_fetch_object($resOsProduto)) {
								echo "<li>{$osProduto->produto}</li>";
							}
							?>
							</ul>
						</td>
						<td class="tal" style="background-color: <?=$cor?>;" nowrap>
							<ul style="list-style-type: none;" >
							<?php
							while ($osSolucao = pg_fetch_object($resSolucao)) {
								echo "<li>{$osSolucao->solucao}</li>";
							}
							?>
							</ul>
						</td>
						<td class="tal" style="background-color: <?=$cor?>;" nowrap>
							<ul style="list-style-type: none;" >
							<?php
							$valores_adicionais = pg_fetch_result($resValorAdicional, 0, "valores_adicionais");

							if (!empty($valores_adicionais)) {
								$valores_adicionais = json_decode($valores_adicionais);

								foreach ($valores_adicionais as $valor_adicional) {
									echo "<li>".utf8_decode(key($valor_adicional))."</li>";
								}
							}
							?>
							</ul>
						</td>						
						<?php
						if ($status == "aguardando_aprovacao") {
						?>
							<td class="tac" style="background-color: <?=$cor?>; vertical-align: middle;" nowrap >
								<button type="button" rel="<?=$os?>" name="interagir" class="btn btn-small btn-primary" >Interagir</button>
								<button type="button" rel="<?=$os?>" name="aprovar" class="btn btn-small btn-success" >Aprovar</button>
								<button type="button" rel="<?=$os?>" name="recusar" class="btn btn-small btn-danger" >Recusar</button>
							</td>
						<?php
						}
						?>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>

		<?php
		if (in_array($status, array("confirmadas", "recusadas"))) {
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado", type: "basic" });
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
		}
	} else {
	?>
		<div class="container">
			<div class="alert">
				<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
}

include "rodape.php";
?>
