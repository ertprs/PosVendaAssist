<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia, auditoria";

include "autentica_admin.php";
include "funcoes.php";

$env = ($_serverEnvironment == 'development') ? 'teste' : 'producao';

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
			$insert = "INSERT INTO tbl_os_status
					   (os, status_os, observacao, admin)
					   VALUES
					   ({$os}, 194, '{$motivo}', {$login_admin})";
			$result = pg_query($con, $insert);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => utf8_encode("Erro ao recusar troca de produto da OS"));
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
								'OS {$sua_os} teve a troca de produto recusada',
								'{$motivo}'
							)";
				$result = pg_query($con, $insert);

				if (in_array($login_fabrica, array(101,141,144))) {
					$sql = "UPDATE tbl_os SET defeito_constatado = NULL WHERE fabrica = {$login_fabrica} AND os = {$os}";
					$res = pg_query($con, $sql);

	                $sqlStatus = "SELECT fn_os_status_checkpoint_os({$os}) AS status;";
	                $resStatus = pg_query($con, $sqlStatus);

	                $statusCheckpoint = pg_fetch_result($resStatus, 0, "status");

	                $updateStatus = "UPDATE tbl_os SET status_checkpoint = {$statusCheckpoint} WHERE fabrica = {$login_fabrica} AND os = {$os}";
	                $resStatus = pg_query($con, $updateStatus);
	            }

				if (strlen(pg_last_error()) > 0) {
					$retorno = array("erro" => utf8_encode("Erro ao recusar troca de produto da OS"));
				} else {
					$retorno = array("ok" => true);
				}
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
			$obs = ($login_fabrica == 141) ? "Troca ou ressarcimento de produto aprovada pela fábrica" : "Troca de produto aprovada pela fábrica";
			$insert = "INSERT INTO tbl_os_status 
					   (os, status_os, observacao, admin)
					   VALUES
					   ({$os}, 193, '{$obs}', {$login_admin})";
			$res = pg_query($con, $insert);

			if (in_array($login_fabrica, array(101,141,144))) {
                $sqlStatus = "SELECT fn_os_status_checkpoint_os({$os}) AS status;";
                $resStatus = pg_query($con, $sqlStatus);

                $statusCheckpoint = pg_fetch_result($resStatus, 0, "status");

                $updateStatus = "UPDATE tbl_os SET status_checkpoint = {$statusCheckpoint} WHERE fabrica = {$login_fabrica} AND os = {$os}";
                $resStatus = pg_query($con, $updateStatus);
            }

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => utf8_encode("Erro ao aprovar troca/ressarcimento"));
			} else {
				if (in_array($login_fabrica, array(141)) && $login_admin != 6553) {
					$header  = "MIME-Version: 1.0 \r\n";
                    $header .= "Content-type: text/html; charset=iso-8859-1 \r\n";
                    $header .= "To: alberico@unicoba.com.br \r\n";
                    $header .= "From: helpdesk@telecontrol.com.br\r\n";

                    $msg = utf8_encode("O admin {$login_login} aprovou a troca de produto da os {$os}");

                    mail("alberico@unicoba.com.br", utf8_encode("Telecontrol - Aprovação de troca de produto"), $msg, $header);
				}

				if (in_array($login_fabrica, array(141))) {
                    $header  = "MIME-Version: 1.0 \r\n";
                    $header .= "Content-type: text/html; charset=iso-8859-1 \r\n";
                    $header .= "To: dafne.lima@unicoba.com.br, fabiana.silva@unicoba.com.br, aline.arruda@unicoba.com.br, damares.silva@unicoba.com.br \r\n";
                    $header .= "From: helpdesk@telecontrol.com.br\r\n";

                    $msg = utf8_encode("OS {$os} teve a troca de produto aprovada https://posvenda.telecontrol.com.br/assist/admin/auditoria_os_troca_produto.php");

                    mail("dafne.lima@unicoba.com.br, fabiana.silva@unicoba.com.br, aline.arruda@unicoba.com.br, damares.silva@unicoba.com.br", utf8_encode("Telecontrol - Aprovação de troca de produto"), $msg, $header);

				}

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
	} else if ($status == "aprovadas") {
		$sub_status = $_POST["sub_status"];

		if (empty($sub_status)) {
			$msg_erro["msg"][] = "Selecione uma opção de pendência";
		}

	}

	if (!count($msg_erro["msg"])) {
		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			$cond_posto = ($env == "teste") ? "" : " AND tbl_os.posto <> 6359 ";
		}

		switch ($status) {
			case 'aprovadas':
				switch ($sub_status) {
					case 'pendencia_troca_ressarcimento':
						$cond_status = 193;
						$cond_troca = " AND tbl_os_troca.os_troca IS NULL ";
						break;

					case 'pendencia_ressarcimento_confirmacao':
						$cond_status = 193;
						$cond_troca = " AND tbl_os_troca.os_troca IS NOT NULL AND tbl_os_troca.ressarcimento IS TRUE ";
						break;
				}
				break;

			case 'recusadas':
				$cond_status = 194;
				break;

			case 'confirmadas':
				$cond_status = 202;

				$sub_status_confirmadas = $_POST["sub_status_confirmadas"];

				if (!empty($sub_status_confirmadas)) {
					switch ($sub_status_confirmadas) {
						case 'troca':
							$cond_troca = " AND tbl_os_troca.ressarcimento IS NOT TRUE ";
							break;
						
						case 'ressarcimento':
							$cond_troca = " AND tbl_os_troca.ressarcimento IS TRUE ";
							break;
					}
				}
				break;
			
			default:
				$cond_status = 192;
				break;
		}

		$select = "SELECT
						tbl_os.os,
						tbl_os.sua_os,
						TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
						tbl_os.serie,
						'<b>' || tbl_produto.referencia || '</b> - ' || tbl_produto.descricao AS produto,
						tbl_defeito_constatado.descricao AS defeito,
						tbl_os_troca.ressarcimento
					FROM tbl_os
					INNER JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
					INNER JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
					LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
					WHERE tbl_os.fabrica = {$login_fabrica}
					$cond_data
					AND (
						SELECT tbl_os_status.status_os 
						FROM tbl_os_status 
						WHERE tbl_os_status.os = tbl_os.os 
						AND tbl_os_status.status_os IN(192,193,194,202) 
						ORDER BY tbl_os_status.data DESC 
						LIMIT 1
					) = {$cond_status}
					{$cond_troca}
					{$cond_posto}
					ORDER BY tbl_os.data_abertura ASC";
		$resSubmit = pg_query($con, $select);

		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($resSubmit) > 0) {
				$data = date("d-m-Y-H:i");

				$titulo_excel = (in_array($login_fabrica, array(101))) ? "RELATÓRIO DE AUDITORIA DE TROCA DE PRODUTO" : "RELATÓRIO DE AUDITORIA DE TROCA DE PRODUTO/RESSARCIMENTO";

				$fileName = "relatorio_auditoria_troca_produto-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");
				$thead = "
					<table border='1'>
						<thead>
							<tr>
								<th colspan='6' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									{$titulo_excel}
								</th>
							</tr>
							<tr>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Abertura</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Série</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ação</th>
							</tr>
						</thead>
						<tbody>
				";
				fwrite($file, $thead);

				for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
					$os                 = pg_fetch_result($resSubmit, $i, 'os');
					$sua_os             = pg_fetch_result($resSubmit, $i, 'sua_os');
					$data_abertura      = pg_fetch_result($resSubmit, $i, 'data_abertura');
					$serie              = pg_fetch_result($resSubmit, $i, 'serie');
					$produto            = pg_fetch_result($resSubmit, $i, 'produto');
					$defeito            = pg_fetch_result($resSubmit, $i, 'defeito');
					$ressarcimento            = pg_fetch_result($resSubmit, $i, 'ressarcimento');

					$body .= "<tr>
							<td nowrap align='center' valign='top'>{$sua_os}</td>
							<td nowrap align='center' valign='top'>{$data_abertura}</td>
							<td nowrap align='center' valign='top'>{$serie}</td>
							<td nowrap align='left' valign='top'>{$produto}</td>
							<td nowrap align='left' valign='top'>{$defeito}</td>";

					if ($status == "confirmadas") {
						$body .= "<td nowrap align='left' valign='top'>".(($ressarcimento == "t") ? "Ressarcimento confirmado" : "Troca de Produto confirmada" )."</td>";
					} else if ($status == "recusadas") {
						$body .= "<td nowrap align='left' valign='top'>Troca recusada</td>";
					} else if ($status == "aprovadas") {
						$body .= "<td nowrap align='left' valign='top'>Troca Aprovada</td>";
					} else if ($status == "aguardando_auditoria") {
						$body .= "<td nowrap align='left' valign='top'>Auditoria Pendente</td>";
					} 
							
					$body .= "</tr>";
				}

				fwrite($file, $body);
				fwrite($file, "
							<tr>
								<th colspan='6' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
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
	}
}

$layout_menu = "auditoria";
$title = "Auditoria de OS com troca de produto";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
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

	$("input[name=status]").change(function() {
		var status = $("input[name=status]:checked").val();

		if (status == "aprovadas") {
			$("#sub_status").show();
		} else {
			$("#sub_status").hide();
		}

		if (status == "confirmadas") {
			$("#sub_status_confirmadas").show();
		} else {
			$("#sub_status_confirmadas").hide();
		}
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
				url: "auditoria_os_troca_produto.php",
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
				url: "auditoria_os_troca_produto.php",
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
					$("button[name=recusar][rel="+os+"]").parents("tr").find("td").last().html("<div class='alert alert-danger tac' style='margin-bottom: 0px;' >Troca recusada</div>");
					Shadowbox.close();
				}

				$("#sb-container").find("div.loading").hide();
				$("#sb-container").find("div.conteudo").show();
			});
		} else {
			alert("Erro ao recusar troca de produto da OS");
		}
	});

	$("button[name=aprovar]").click(function() {
		var os = $(this).attr("rel");
		var td = $(this).parent("td");
		var loading = "<div class='loading tac' ><img src='imagens/loading_img.gif' style='height: 18px; width: 18px;' /></div>";

		if (os != undefined && os.length > 0) {
			$.ajax({
				url: "auditoria_os_troca_produto.php",
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
					$(td).html("<div class='alert alert-success tac' style='margin-bottom: 0px;'>Troca aprovada</div>");
				}
			});
		}
	});

	$("button[name=confirmar_ressarcimento]").click(function() {
		var os = $(this).attr("rel");

		if (os != undefined && os.length > 0) {
			Shadowbox.open({
				content: "confirmacao_ressarcimento.php?os="+os,
				player: "iframe",
				width: 400,
				height: 155
			});
		}
	});

	$("input[name=status]").change(function() {
		var status = $("input[name=status]:checked").val();

		if (status == "aguardando_auditoria" || status == "aprovadas") {
			$("#listar_todas").show();
		} else {
			$("#listar_todas").hide();
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
			<label class='control-label' for='descricao_posto'>Status</label>
			<div class='controls controls-row'>
				<?php
				$sql = "SELECT privilegios FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$login_admin}";
				$res = pg_query($con, $sql);

				$privilegios = pg_fetch_result($res, 0, "privilegios");

				if (preg_match("/\*/", $privilegios)) {
				?>
					<div class='span3'>
						 <label class="radio">
					        <input type="radio" name="status" value="aguardando_auditoria" checked />
					        Auditoria pendente
					    </label>
					</div>
				<?php
				}
				?>
				<div class='span3'>
				    <label class="radio">
				        <input type="radio" name="status" value="aprovadas" <?php if($status == "aprovadas") echo "checked"; ?> />
				        Aprovadas
				    </label>
				</div>
				<?php if(!in_array($login_fabrica, array(101))){ ?>
				<div class='span3'>
				    <label class="radio">
				        <input type="radio" name="status" value="confirmadas" <?php if($status == "confirmadas") echo "checked"; ?> />
				        Confirmadas
				    </label>
				</div>
				<?php } ?>
				<div class='span3'>
				    <label class="radio">
				        <input type="radio" name="status" value="recusadas" <?php if($status == "recusadas") echo "checked"; ?> />
				        Recusadas
				    </label>
				</div>
			</div>
		</div>

		<div class='span2'></div>
	</div>

	<div class='row-fluid' id ="sub_status" style="display: <?=($status == 'aprovadas') ? 'block' : 'none'?>;" >
		<div class='span2'></div>

		<div class='span8 tac'>
			<div class='controls controls-row tac'>
				<div class='span12 tac'>
				    <label class="radio">
				        <input type="radio" name="sub_status" value="pendencia_troca_ressarcimento" <?php if($sub_status == "pendencia_troca_ressarcimento") echo "checked"; ?> />
				        Pendência de troca <?php echo (!in_array($login_fabrica, array(101))) ? "/ ressarcimento" : ""; ?>
				    </label>
				    <?php if(!in_array($login_fabrica, array(101))){ ?>
				     &nbsp; 
				    <label class="radio">
				        <input type="radio" name="sub_status" value="pendencia_ressarcimento_confirmacao" <?php if($sub_status == "pendencia_ressarcimento_confirmacao") echo "checked"; ?> />
				        Ressarcimento aguardando confirmação
				    </label>
				    <?php } ?>
				</div>
			</div>
		</div>

		<div class='span2'></div>
	</div>

	<div class='row-fluid' id ="sub_status_confirmadas" style="display: <?=($status == 'confirmadas') ? 'block' : 'none'?>;" >
		<div class='span2'></div>

		<div class='span8 tac'>
			<div class='controls controls-row tac'>
				<div class='span12 tac'>
				    <label class="radio">
				        <input type="radio" name="sub_status_confirmadas" value="troca" <?php if($sub_status_confirmadas == "troca") echo "checked"; ?> />
				        Troca
				    </label>
				     &nbsp; 
				    <label class="radio">
				        <input type="radio" name="sub_status_confirmadas" value="ressarcimento" <?php if($sub_status_confirmadas == "ressarcimento") echo "checked"; ?> />
				        Ressarcimento
				    </label>
				     &nbsp; 
				    <label class="radio">
				        <input type="radio" name="sub_status_confirmadas" value="" <?php if(!strlen($sub_status_confirmadas)) echo "checked"; ?> />
				        Ambos
				    </label>
				</div>
			</div>
		</div>

		<div class='span2'></div>
	</div>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>

		<?php
		$display_listar_todas = (!strlen($status) || in_array($status, array("aprovadas", "aguardando_auditoria"))) ? "style='display: inline-block;'" : "style='display: none;'";
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
					<th>Série</th>
					<th>Produto</th>
					<th>Defeito</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $count; $i++) {
					$os            = pg_fetch_result($resSubmit, $i, "os");
					$sua_os        = pg_fetch_result($resSubmit, $i, "sua_os");
					$data_abertura = pg_fetch_result($resSubmit, $i, "data_abertura");
					$serie         = pg_fetch_result($resSubmit, $i, "serie");
					$produto       = pg_fetch_result($resSubmit, $i, "produto");
					$defeito       = pg_fetch_result($resSubmit, $i, "defeito");
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
					?>

					<tr os="<?=$os?>" >
						<td class="tac" style="background-color: <?=$cor?>;" ><a href="os_press.php?os=<?=$os?>" target="_blank" ><?=$sua_os?></a></td>
						<td class="tac" style="background-color: <?=$cor?>;" ><?=$data_abertura?></td>
						<td class="tac" style="background-color: <?=$cor?>;" ><?=$serie?></td>
						<td class="tal" style="background-color: <?=$cor?>;" ><?=$produto?></td>
						<td class="tal" style="background-color: <?=$cor?>;" ><?=$defeito?></td>
						<td class="tac" style="background-color: <?=$cor?>;" nowrap >
							<?php

							if (in_array($login_fabrica, [141])) {
								$linkTroca = "os_troca_subconjunto.php?os={$os}";
							} else {
								$linkTroca = "os_cadastro.php?os={$os}&osacao=trocar";
							}

							if ($status == "aguardando_auditoria") {
							?>
								<?php if(!in_array($login_fabrica, array(101))){ ?>
									<button type="button" rel="<?=$os?>" name="interagir" class="btn btn-small btn-primary" >Interagir</button>
								<?php } ?>
								<button type="button" rel="<?=$os?>" name="aprovar" class="btn btn-small btn-success" >Aprovar</button>
								<button type="button" rel="<?=$os?>" name="recusar" class="btn btn-small btn-danger" >Recusar</button>
							<?php
							} else if ($status == "aprovadas" && $sub_status == "pendencia_troca_ressarcimento") {
							?>
								<a href="<?= $linkTroca ?>" target="_blank"><button type="button" rel="<?=$os?>" name="trocar" class="btn btn-small btn-success" >Trocar</button></a>
							<?php
							} else if ($status == "aprovadas" && $sub_status == "pendencia_ressarcimento_confirmacao") {
							?>
									<button type="button" rel="<?=$os?>" name="confirmar_ressarcimento" class="btn btn-small btn-info" >Confirmar Ressarcimento</button>
							<?php
							} else if ($status == "confirmadas") {
								if ($ressarcimento == "t") {
								?>
									<div class="alert alert-success tac" style="margin-bottom: 0px;" >
										Ressarcimento confirmado
								    </div>
								<?php
								} else {
								?>
									<div class="alert alert-success tac" style="margin-bottom: 0px;" >
										Troca confirmada
								    </div>
								<?php
								}
							} else if ($status == "recusadas") {
							?>
								<div class="alert alert-danger tac" style="margin-bottom: 0px;" >
									Troca recusada
							    </div>
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
		if (in_array($status, array("confirmadas", "recusadas")) || $login_fabrica == 101) {
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

			<br />

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
