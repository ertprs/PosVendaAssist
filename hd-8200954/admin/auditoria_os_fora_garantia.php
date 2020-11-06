<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia,auditoria";

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
					   ('$programa_insert',{$os}, {$login_admin}, {$login_fabrica}, '{$interacao}')";
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
			$res = pg_query($con,"BEGIN TRANSACTION");
			$insert = "INSERT INTO tbl_os_status
					   (os, status_os, observacao, admin)
					   VALUES
					   ({$os}, 208, '{$motivo}', {$login_admin})";
			$result = pg_query($con, $insert);

			if(pg_query($con, "SELECT fn_os_excluida($os, $login_fabrica, $login_admin)")){

				$sql_motivo = "UPDATE tbl_os_excluida SET motivo_exclusao = '{$motivo}' WHERE os = {$os} AND fabrica = {$login_fabrica}";
            	$res_motivo = pg_query($con, $sql_motivo);

			}
			
			if (strlen(pg_last_error()) > 0) {
                $res = pg_query($con,"ROLLBACK TRANSACTION");
				$retorno = array("erro" => utf8_encode("Erro ao recusar OS"));
			} else {
				$select = "SELECT posto, sua_os FROM tbl_os WHERE os = {$os}";
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
								'OS {$sua_os} foi recusada pela fábrica',
								'{$motivo}'
							)";
				$result = pg_query($con, $insert);

				if (strlen(pg_last_error()) > 0) {
					$res = pg_query($con,"ROLLBACK TRANSACTION");
					$retorno = array("erro" => utf8_encode("Erro ao recusar OS"));
				} else {
					$res = pg_query($con,"COMMIT TRANSACTION");
					$retorno = array("ok" => true);
				}
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_POST["aprovar"] == true) {
	$os     = $_POST["os"];
	$valor  = $_POST["valor"];
	$valor = str_replace(".", "", $valor);
	$valor = str_replace(",", ".", $valor);

	if(strlen($valor) == 0) {
		$retorno = array("erro" => utf8_encode("Informe o valor à pagar"));
	}
	if (empty($os)) {
		$retorno = array("erro" => utf8_encode("OS não informada"));
	} elseif(empty($retorno)) {
		$select = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$result = pg_query($con, $select);

		if (!pg_num_rows($result)) {
			$retorno = array("erro" => utf8_encode("OS não encontrada"));
		} else {
			$res = pg_query($con,"BEGIN TRANSACTION");
			$insert = "INSERT INTO tbl_os_status
					   (os, status_os, observacao, admin)
					   VALUES
					   ({$os}, 207, 'OS aprovada pela fábrica', {$login_admin})";
			$result = pg_query($con, $insert);

			$update = "UPDATE tbl_os SET mao_de_obra = {$valor} , data_conserto = CURRENT_TIMESTAMP WHERE os = $os";
			$result = pg_query($con,$update);

			if (strlen(pg_last_error()) > 0) {
                $res = pg_query($con,"ROLLBACK TRANSACTION");
				$retorno = array("erro" => utf8_encode("Erro ao aprovar OS"));
			} else{
                $res = pg_query($con,"COMMIT TRANSACTION");
				$retorno = array("ok" => true);
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "submit") {
	$os              = $_POST["os"];
	$data_inicial    = $_POST["data_inicial"];
	$data_final      = $_POST["data_final"];
	$codigo_posto    = $_POST["codigo_posto"];
	$descricao_posto = $_POST["descricao_posto"];
	$status          = $_POST["status"];

	if (empty($status)) {
		$msg_erro["msg"][] = "Selecione o status";
		$msg_erro["campos"][] = "status";
	}

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

	if (empty($os)) {
		if (!strlen($data_inicial) or !strlen($data_final)) {
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "data";
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
			}
		}
	}

	if (empty($msg_erro["msg"])) {
		if (empty($os)) {
			$whereData = " AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
		} else {
			$whereOs = " AND tbl_os.os = {$os} ";
		}

		if (!empty($posto)) {
			$wherePosto = " AND tbl_os.posto = {$posto} ";
		}

		$sql = "SELECT
					tbl_os.os,
					tbl_os.sua_os,
					TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data,
					tbl_posto.nome AS posto,
					tbl_os.mao_de_obra
				FROM tbl_os
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = {$login_fabrica} AND tbl_posto_fabrica.posto = tbl_os.posto
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN(206,207,208) ORDER BY data DESC LIMIT 1) = $status
				{$whereData}
				{$whereOs}
				{$wherePosto}
				ORDER BY tbl_os.data_abertura ASC";

		$resSubmit = pg_query($con, $sql);

	}
}

$layout_menu = "auditoria";
$title = "AUDITORIA DE OS FORA DE GARANTIA";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"price_format"
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
	$.datepickerLoad(["data_final", "data_inicial"]);
	$.autocompleteLoad(["posto"]);
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
				height: 150,
				width: 500,
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
				url: "auditoria_os_fora_garantia.php",
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
				height: 150,
				width: 500,
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
				url: "auditoria_os_fora_garantia.php",
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
		var valor = $("#valor_"+os).val();

		if (os != undefined && os.length > 0) {
			var loading = "<div class='loading tac' ><img src='imagens/loading_img.gif' /></div>";
			var td      = $(this).parent("td");

			$.ajax({
				url: "auditoria_os_fora_garantia.php",
				type: "post",
				data: { aprovar: true, os: os, valor: valor },
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
					$("button[name=recusar][rel="+os+"]").parents("tr").find("td").last().html("<div class='alert alert-success tac' style='margin-bottom: 0px;' >OS aprovada</div>");
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
		<br/>
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
		<br/>
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

<form method="post" class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span10'>
			<div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='os'>Número da Ordem de Serviço</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<input type="text" name="os" id="os" class='span2' value= "<?=$os?>">
						<span style="color: #B94A48;" >Caso informe o número da OS os campos de datas não são obrigatórios</span>
					</div>
				</div>
			</div>
		</div>
	</div>

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
			<div class='control-group <?=(in_array("status", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='status'>Status</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>

					    <label class="radio inline">
						   	<input type="radio" name="status" value="206" <?=($status == 206) ? 'checked' : ''?> /> Aguardando Aprovação
						</label>

					    <label class="radio inline">
					    	<input type="radio" name="status" value="207" <?=($status == 207) ? 'checked' : ''?> /> Aprovadas
					    </label>
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
	$rows = pg_num_rows($resSubmit);

	if ($rows > 0) {
	?>
		<table class="table table-bordered">
			<thead>
				<tr>
					<td colspan="4" >
						<span class="legenda" style="background-color: #FFDC4C; vertical-align: middle; margin-right: 5px;" ></span>Fábrica interagiu<br />
						<span class="legenda" style="background-color: #A6D941; vertical-align: middle; margin-right: 5px;" ></span>Posto interagiu<br />
					</td>
				</tr>
				<tr class="titulo_coluna" >
					<th>OS</th>
					<th>Data</th>
					<th>Posto</th>
					<?php
						if($status == 207){
							echo "<th>Valor Pago</th>";
						}else{
							echo "<th>Valor à Pagar</th>";
						}
					?>					
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $rows; $i++) { 
					$os        	   = pg_fetch_result($resSubmit, $i, "os");
					$sua_os        = pg_fetch_result($resSubmit, $i, "sua_os");
					$data          = pg_fetch_result($resSubmit, $i, "data");
					$posto         = pg_fetch_result($resSubmit, $i, "posto");
					$mao_de_obra   = pg_fetch_result($resSubmit, $i, "mao_de_obra");

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
						<td class="tac" style="background-color: <?=$cor?>;"><a href="os_press.php?os=<?=$os?>" target="_blank" ><?=$sua_os?></a></td>
						<td class="tac" style="background-color: <?=$cor?>;"><?=$data?></td>
						<td style="background-color: <?=$cor?>;"><?=$posto?></td>
						<td class="tac" style="background-color: <?=$cor?>;">	
						<?php
							if($status == 207){	
								echo number_format($mao_de_obra,2,",",".");
							}else{
						?>
							<input type="text" name="valor_<?=$os?>" id="valor_<?=$os?>" style="width:60px;height:20px;" price="true" >										
						<?php
							}
						?>
						</td>
						<td class="tac" style="background-color: <?=$cor?>;">	
							<button type="button" rel="<?=$os?>" name="interagir" class="btn btn-small btn-primary" >Interagir</button>						
							<?php
							if($status != 207){
							?>
								<button type="button" rel="<?=$os?>" name="aprovar" class="btn btn-small btn-success" >Aprovar</button>
								<button type="button" rel="<?=$os?>" name="recusar" class="btn btn-small btn-danger" >Recusar</button>
							<?php
							}
							?>
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
		<div class="alert alert-error"><h4>Nenhum resultado encontrado</h4></div>
	<?php
	}
}

include "rodape.php";
?>
