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
					   ('{$programa_insert}',{$os}, {$login_admin}, {$login_fabrica}, '{$interacao}')";
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
	$motivo         = trim($_POST["motivo"]);
	$os             = $_POST["os"];

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
					   ({$os}, 215, 'Ordem de Serviço recusada pela Fábrica', {$motivo}', {$login_admin})";
			$result = pg_query($con, $insert);

			if (!strlen(pg_last_error())) {
				$sql = "SELECT servico_realizado 
						FROM tbl_servico_realizado 
						WHERE fabrica = {$login_fabrica} 
						AND UPPER(descricao) = 'CANCELADO'";
				$res = pg_query($con, $sql);

				$servico_cancelado = pg_fetch_result($res, 0, "servico_realizado");

				$upate = "UPDATE tbl_os_item SET servico_realizado = {$servico_cancelado}
						  FROM tbl_os_produto
						  WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
						  AND tbl_os_produto.os = {$os}";
				$resUpdate = pg_query($con, $upate);

				if (strlen(pg_last_error()) > 0) {
					pg_query($con, "ROLLBACK");

					$retorno = array("erro" => utf8_encode("Erro ao recusar OS"));
				} else {
					$retorno = array("erro" => utf8_encode("Erro ao recusar OS"));
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
									'OS {$sua_os} - Ordem de Serviço recusada pela Fábrica',
									'{$motivo}'
								)";
					$result = pg_query($con, $insert);

					if (strlen(pg_last_error()) > 0) {
						pg_query($con, "ROLLBACK");

						$retorno = array("erro" => utf8_encode("Erro ao recusar OS"));
					} else {
						pg_query($con, "COMMIT");

						$retorno = array("ok" => true);
					}
				}
			} else {
				pg_query($con, "ROLLBACK");

				$retorno = array("erro" => utf8_encode("Erro ao recusar OS"));
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_POST["aprovar"] == true) {
	$os             = $_POST["os"];

	if (empty($os)) {
		$retorno = array("erro" => utf8_encode("OS não informada"));
	} else {
		$select = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$result = pg_query($con, $select);

		if (!pg_num_rows($result)) {
			$retorno = array("erro" => utf8_encode("OS não encontrada"));
		} else {
			$insert = "INSERT INTO tbl_os_status
					   (os, status_os, observacao, admin)
					   VALUES
					   ({$os}, 214, 'Ordem de Serviço aprovada pela Fábrica', {$login_admin})";
			$result = pg_query($con, $insert);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => utf8_encode("Erro ao aprovar OS"));
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
	$status          = $_POST["status"];
	$listar_todas    = ($_POST['btn_acao'] == "listar_todas") ? true: false;

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

	if ((!strlen($data_inicial) || !strlen($data_final)) && $listar_todas === false) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else if($listar_todas === false){
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			} else if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final)) {
				$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 1 mês";
				$msg_erro["campos"][] = "data";
			} else {
				$aux_data_inicial = "{$yi}-{$mi}-{$di} 00:00:00";
				$aux_data_final   = "{$yf}-{$mf}-{$df} 23:59:59";
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
			//$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		switch ($status) {
			case 'aprovadas':
				$cond_status = 214;
				break;

			case 'reprovadas':
				$cond_status = 215;
				break;
			
			default:
				$cond_status = 213;
				break;
		}

		$select = "SELECT
						tbl_os.os,
						tbl_os.sua_os,
						TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
						'<b>' || tbl_produto.referencia || '</b> - ' || tbl_produto.descricao AS produto,
						tbl_os.revenda_nome,
						tbl_os.revenda_cnpj
					FROM tbl_os
					INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					INNER JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
					INNER JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					WHERE tbl_os.fabrica = {$login_fabrica}
					$cond_data
					AND (
						SELECT tbl_os_status.status_os 
						FROM tbl_os_status 
						WHERE tbl_os_status.os = tbl_os.os 
						AND tbl_os_status.status_os IN(213,214,215) 
						ORDER BY tbl_os_status.data DESC 
						LIMIT 1
					) = {$cond_status}
					{$cond_posto}";
		$resSubmit = pg_query($con, $select);
	}
}

$layout_menu = "auditoria";
$title = "AUDITORIA DE OS COM CONSTRUTORA";
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
				url: "auditoria_os_construtora.php",
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
		var os             = $(this).attr("rel");

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
		var os             = $(this).attr("rel");
		var motivo         = $.trim($("#sb-container").find("textarea[name=text_motivo]").val());

		if (motivo.length == 0) {
			alert("Informe o motivo");
		} else if (os != undefined && os.length > 0) {
			$.ajax({
				url: "auditoria_os_construtora.php",
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
					$("button[name=recusar][rel="+os+"]").parents("tr").find("td").last().html("<div class='alert alert-danger tac' style='margin-bottom: 0px;' >Recusado</div>");
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
		var os             = $(this).attr("rel");

		if (os != undefined && os.length > 0) {
			var loading = "<div class='loading tac' ><img src='imagens/loading_img.gif' style='width: 18px; height: 18px;' /></div>";
			var td      = $(this).parent("td");

			$.ajax({
				url: "auditoria_os_construtora.php",
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

					$(td).find("button").show();
					$(td).find("div.loading").remove();
				} else {
					$("button[name=recusar][rel="+os+"]").parents("tr").find("td").last().html("<div class='alert alert-success tac' style='margin-bottom: 0px;' >Aprovado</div>");
				}
			});
		}
	});

	if ($("input[name=status]:checked").val() == "pendente") {
		$("#listarTodas").show();
	} else {
		$("#listarTodas").hide();
	}

	$("input[name=status]").change(function() {
		if ($("input[name=status]:checked").val() == "pendente") {
			$("#listarTodas").show();
		} else {
			$("#listarTodas").hide();
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

<form name="frm_auditoria_os_construtora" method="post" action="<?=$_SERVER['PHP_SELF']?>" class="form-search form-inline tc_formulario" style="margin: 0 auto;" >
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
				<label class='control-label' for='status'>Status</label>
				<div class='controls controls-row'>
					<div class='span4'>
						 <label class="radio">
					        <input type="radio" name="status" value="pendente" checked>
					        Aguardando auditoria
					    </label>
					</div>
					<div class='span4'>
					    <label class="radio">
					        <input type="radio" name="status" value="aprovadas" <?php if($status == "aprovadas") echo "checked"; ?> />
					        Aprovadas
					    </label>
					</div>
					<div class='span4'>
					    <label class="radio">
					        <input type="radio" name="status" value="reprovadas" <?php if($status == "reprovadas") echo "checked"; ?> />
					        Recusadas
					    </label>
					</div>
				</div>
			</div>

			<div class='span2'></div>
		</div>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<button class='btn btn-info' id="listarTodas" type="button"  onclick="submitForm($(this).parents('form'),'listar_todas');">Listar Todas</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

</div>

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
					<th>Construtora Nome</th>
					<th>Construtora CNPJ</th>
					<?php
					if ($status == "pendente") {
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
					$produto       = pg_fetch_result($resSubmit, $i, "produto");
					$revenda_nome  = pg_fetch_result($resSubmit, $i, "revenda_nome");
					$revenda_cnpj  = pg_fetch_result($resSubmit, $i, "revenda_cnpj");

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
					?>

					<tr>
						<td class="tac" style="background-color: <?=$cor?>;" ><a href="os_press.php?os=<?=$os?>" target="_blank" ><?=$sua_os?></a></td>
						<td class="tac" style="background-color: <?=$cor?>;" ><?=$data_abertura?></td>
						<td class="tal" style="background-color: <?=$cor?>;" ><?=$produto?></td>
						<td class="tal" style="background-color: <?=$cor?>;" ><?=$revenda_nome?></td>
						<td class="tal" style="background-color: <?=$cor?>;" ><?=$revenda_cnpj?></td>
						<?php
						if ($status == "pendente") {
						?>
							<td class="tac" style="background-color: <?=$cor?>;" nowrap >
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

		<br />
	<?php
	} else {
	?>
		<div class="container">
			<div class="alert">
				<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>

		<br />
	<?php
	}
}

include "rodape.php";
?>
