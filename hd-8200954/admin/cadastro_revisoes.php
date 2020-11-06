<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

$layout_menu = 'cadastro';
$title = 'MANUTENÇÃO DE REVISÕES';

if ($_POST["ajax_rev_auditadas"]) {
	$produto = $_POST["produto"];

	$sqlPA = "SELECT parametros_adicionais FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $produto ";
	$resPA = pg_query($con, $sqlPA);
	
	if (pg_last_error()) {
		echo json_encode(["error"=>"erro"]);
		exit();
	}

	if (pg_num_rows($resPA) > 0) {
		$parametros_adicionais = json_decode(pg_fetch_result($resPA, 0, 'parametros_adicionais'), true);
		$parametros_adicionais_rev = $parametros_adicionais["auditoria_revisao"];
	}

	if (count($parametros_adicionais_rev) > 0) {
		echo json_encode(["success"=>json_encode($parametros_adicionais_rev)]);
		exit();
	} else {
		echo json_encode(["sem_auditoria"=>""]);
		exit();
	}

	echo json_encode(["sem_auditoria"=>""]);
	exit();
}

if ($_POST["ajax_ativa_linha"]) {
	$linha = $_POST["linha"];
	$acao  = $_POST["acao"];

	$AuditorLog = new AuditorLog;

	$sqlLog = " SELECT CASE WHEN ((campos_adicionais)::json->>'auditada')::boolean IS TRUE 
						THEN 'Ativo' 
						ELSE 'Inativo'
						END AS situacao
				FROM tbl_linha
				WHERE linha = $linha
				AND fabrica = $login_fabrica ";

	$AuditorLog->RetornaDadosSelect($sqlLog);

	if ($acao == 'ativar') {
		$auditada = json_encode(["auditada"=>true]);
		$sql = " UPDATE tbl_linha SET campos_adicionais = COALESCE(campos_adicionais, '{}') || '$auditada' WHERE linha = $linha AND fabrica = $login_fabrica";
	} else {
		$auditada = json_encode(["auditada"=>false]);
		$sql = " UPDATE tbl_linha SET campos_adicionais = COALESCE(campos_adicionais, '{}') || '$auditada' WHERE linha = $linha AND fabrica = $login_fabrica";
	}

	$res = pg_query($con, $sql);
	if (pg_last_error()) {
		echo json_encode(["error"=>"Erro ao Executar Atualização da Linha"]);
		exit();
	}

	$AuditorLog->RetornaDadosSelect()->EnviarLog('UPDATE', 'tbl_linha',"$login_fabrica*$linha");
	echo json_encode(["success"=>"Linha Atualizada com Sucesso"]);
	exit();
}

if ($_POST["ajax_pecas"]) {
	$pecas = $_POST["pecas"];

	$pecasArray = array();

	foreach ($pecas as $key => $peca_id) {
		if (!in_array($peca_id, array_keys($pecasArray))) {
			$sql = "SELECT descricao || ' - ' || referencia AS peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca_id}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$pecasArray[$peca_id] = utf8_encode(pg_fetch_result($res, 0, "peca"));
			}
		}
	}

	exit(json_encode(array("pecas" => $pecasArray)));
}

if (isset($_POST["gravar_revisao"])) {
	$cadastrar_revisao = $_POST["cadastrar_revisao"];
	$revisao           = $_POST["revisao"];

	if (empty($revisao["primeira"]["horas"])) {
		$msg_erro["msg"][] = "Informe as horas da primeira revisão";
	}

	if (empty($revisao["intervalo"]["horas"])) {
		$msg_erro["msg"][] = "Informe as horas do intervalo de tempo das revisões";
	}

	if (count($revisao["excecao"]) > 0) {
		foreach ($revisao["excecao"] as $horas => $excecaoArray) {
			if (!count($excecaoArray["pecas"])) {
				$msg_erro["msg"][] = "Informe as peças do intervalo de revisão de {$horas} horas";
			}
		}
	}

	if (!count($msg_erro["msg"])) {
		pg_query($con, "BEGIN");

		$AuditorLog = new AuditorLog;

		switch ($cadastrar_revisao) {
			case "familia":
				$familia = $_POST["familia"];

				$sqlLog = " SELECT 	referencia || ' - ' || descricao AS produto,
									valores_adicionais::jsonb->'revisao'->>'tolerancia' AS tolerancia,
								   	valores_adicionais::jsonb->'revisao'->'primeira'->>'horas' AS primeira_revisao,
								   	valores_adicionais::jsonb->'revisao'->'intervalo'->>'horas' AS intervalo
							FROM tbl_produto
							WHERE produto = $familia
							AND fabrica_i = $login_fabrica";

				$AuditorLog->RetornaDadosSelect($sqlLog);

				$selectProdutos = "SELECT produto, valores_adicionais FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND familia = {$familia}";
				$resProdutos    =  pg_query($con, $selectProdutos);

				if (pg_num_rows($resProdutos) > 0) {
					while ($produto = pg_fetch_object($resProdutos)) {
						$valores_adicionais = json_decode($produto->valores_adicionais, true);
						$valores_adicionais["revisao"] = $revisao;
						$valores_adicionais = json_encode($valores_adicionais);

						$update = "UPDATE tbl_produto SET valores_adicionais = '{$valores_adicionais}' WHERE fabrica_i = {$login_fabrica} AND produto = {$produto->produto}";
						$resUpdate = pg_query($con, $update);

						if (strlen(pg_last_error()) > 0) {
							$msg_erro["msg"][] = "Erro ao cadastrar revisão";
							break;
						}
					}
				}

				if (count($msg_erro) == 0) {
					$AuditorLog->RetornaDadosSelect()->EnviarLog('UPDATE', 'tbl_produto',"$login_fabrica*$familia");
				}

				break;
			
			case "produto":
				$produto = $_POST["produto_id"];

				$sqlLog = " SELECT 	referencia || ' - ' || descricao AS produto,
									valores_adicionais::jsonb->'revisao'->>'tolerancia' AS tolerancia,
								   	valores_adicionais::jsonb->'revisao'->'primeira'->>'horas' AS primeira_revisao,
								   	valores_adicionais::jsonb->'revisao'->'intervalo'->>'horas' AS intervalo,
								   	parametros_adicionais::jsonb->>'auditoria_revisao' AS revisoes_auditadas_horas
							FROM tbl_produto
							WHERE produto = $produto
							AND fabrica_i = $login_fabrica";

				$AuditorLog->RetornaDadosSelect($sqlLog);

				$selectProduto = "SELECT produto, valores_adicionais, parametros_adicionais FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
				$resProduto    =  pg_query($con, $selectProduto);

				if (pg_num_rows($resProduto) > 0) {
					$valores_adicionais = json_decode(pg_fetch_result($res, 0, "valores_adicionais"), true);
					$valores_adicionais["revisao"] = $revisao;
					$valores_adicionais = json_encode($valores_adicionais);

					$update = "UPDATE tbl_produto SET valores_adicionais = '{$valores_adicionais}' WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
					$resUpdate = pg_query($con, $update);

					if (strlen(pg_last_error()) > 0) {
						$msg_erro["msg"][] = "Erro ao cadastrar revisão";
						break;
					}
				
					if (!empty(pg_fetch_result($resProduto, 0, "parametros_adicionais"))) {
						$parametros_adicionais = json_decode(pg_fetch_result($resProduto, 0, "parametros_adicionais"), true);
						unset($parametros_adicionais["auditoria_revisao"]);
						
						if ($_POST["todas_rev_lancadas"]) {
							$todas_rev_lancadas = explode(", ", $_POST["todas_rev_lancadas"]);
							sort($todas_rev_lancadas);

							$todas_rev = [];

							for ($i=1; $i<=count($todas_rev_lancadas); $i++) {
								$parametros_adicionais["auditoria_revisao"]["revisao".$i] = $todas_rev_lancadas[$i-1];
							}
						}

						if (empty($parametros_adicionais)) {
							$sql = "UPDATE tbl_produto SET parametros_adicionais = null WHERE produto = $produto AND fabrica_i = $login_fabrica ";
						} else {
							$parametros_adicionais = json_encode($parametros_adicionais);
							$sql = "UPDATE tbl_produto SET parametros_adicionais = '$parametros_adicionais' WHERE produto = $produto AND fabrica_i = $login_fabrica ";
						}

						$res = pg_query($con, $sql);
						if (strlen(pg_last_error()) > 0) {
							$msg_erro["msg"][] = "Erro ao cadastrar revisão";
							break;
						}
					} else {
						$parametros_adicionais = '';
						
						if ($_POST["todas_rev_lancadas"]) {
							$todas_rev_lancadas = explode(", ", $_POST["todas_rev_lancadas"]);
							sort($todas_rev_lancadas);

							$todas_rev = [];

							for ($i=1; $i<=count($todas_rev_lancadas); $i++) {
								$parametros_adicionais["auditoria_revisao"]["revisao".$i] = $todas_rev_lancadas[$i-1];
							}
						}

						if (empty($parametros_adicionais)) {
							$sql = "UPDATE tbl_produto SET parametros_adicionais = null WHERE produto = $produto AND fabrica_i = $login_fabrica ";
						} else {
							$parametros_adicionais = json_encode($parametros_adicionais);
							$sql = "UPDATE tbl_produto SET parametros_adicionais = '$parametros_adicionais' WHERE produto = $produto AND fabrica_i = $login_fabrica ";
						}

						$res = pg_query($con, $sql);
						if (strlen(pg_last_error()) > 0) {
							$msg_erro["msg"][] = "Erro ao cadastrar revisão";
							break;
						}
					}
				}

				if (count($msg_erro) == 0) {
					$AuditorLog->RetornaDadosSelect()->EnviarLog('UPDATE', 'tbl_produto',"$login_fabrica*$produto");
				}

				break;
		}

		if (count($msg_erro["msg"]) > 0) {
			pg_query($con, "ROLLBACK");
		} else {
			pg_query($con, "COMMIT");
			unset($_POST);
			$msg_sucesso = "Revisão gravada com sucesso";
		}
	}
}

if ($_POST["btn-listar-revisao"] == 'sim') {
	$sqlRev = "SELECT 	tbl_linha.nome AS nome_linha,
					  	tbl_familia.descricao AS nome_familia,
					  	tbl_produto.referencia || ' - ' || tbl_produto.descricao AS desc_prod,
					  	tbl_produto.valores_adicionais::jsonb->'revisao'->'primeira'->>'horas' AS primeira_revisao,
					  	tbl_produto.valores_adicionais::jsonb->'revisao'->'intervalo'->>'horas' AS intervalo_revisao,
					  	tbl_produto.valores_adicionais::jsonb->'revisao'->>'excecao' AS excecao,
					  	tbl_produto.valores_adicionais,
					  	tbl_produto.parametros_adicionais::jsonb->>'auditoria_revisao' AS parametros_adicionais
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				LEFT JOIN tbl_familia USING(familia)
				WHERE fabrica_i = $login_fabrica
				AND tbl_produto.valores_adicionais::jsonb->>'revisao' NOTNULL 
				ORDER BY tbl_linha.nome ASC 
				LIMIT 500";
	$resRev = pg_query($con, $sqlRev);

	if (pg_last_error()) {
		$msg_erro = "Erro ao realizar consulta";
	} else if (pg_num_rows($resRev) > 0) {
		$dadosTabela = pg_fetch_all($resRev);
	}
}

if (isset($_POST["gerar_excel"])) {

	$sqlRev = "SELECT 	tbl_linha.nome AS nome_linha,
					  	tbl_familia.descricao AS nome_familia,
					  	tbl_produto.referencia || ' - ' || tbl_produto.descricao AS desc_prod,
					  	tbl_produto.valores_adicionais::jsonb->'revisao'->'primeira'->>'horas' AS primeira_revisao,
					  	tbl_produto.valores_adicionais::jsonb->'revisao'->'intervalo'->>'horas' AS intervalo_revisao,
					  	tbl_produto.valores_adicionais::jsonb->'revisao'->>'excecao' AS excecao,
					  	tbl_produto.valores_adicionais,
					  	tbl_produto.parametros_adicionais::jsonb->>'auditoria_revisao' AS parametros_adicionais
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				LEFT JOIN tbl_familia USING(familia)
				WHERE fabrica_i = $login_fabrica
				AND tbl_produto.valores_adicionais::jsonb->>'revisao' NOTNULL 
				ORDER BY tbl_linha.nome ASC ";
	$resRev = pg_query($con, $sqlRev);

	if (pg_last_error()) {
		$msg_erro = "Erro ao realizar consulta";
	} else if (pg_num_rows($resRev) > 0) {
		$dadosTabela = pg_fetch_all($resRev);

		$filename = "relatorio-todas-revisoes-".date('Ydm').".csv";
		$file     = fopen("/tmp/{$filename}", "w");

		$thead = "Linha;Familia;Produto;1ª Revisão;Intervalo das Revisões;Exceções de Intervalos;Intervalos Auditadas";
		fwrite($file, "$thead\n");
		
		foreach ($dadosTabela as $key => $value) {
			$ppA = [];
			$parametros_adicionais = "";
			if (!empty($value["parametros_adicionais"])) {
				foreach (json_decode($value["parametros_adicionais"], true) as $chave => $valor) {
					$ppA[] = $valor." Horas";
				}

				$parametros_adicionais = implode(" / ", $ppA);
			}

			$ex = [];
			$excecao = "";

			if (!empty($value["excecao"])) {
				foreach (json_decode($value["excecao"], true) as $k => $v) {
					$ex[] = $k;
				}

				$excecao = implode(" / ", $ex);
			}

			
			fwrite($file, $value["nome_linha"].";".$value["nome_familia"].";".$value["desc_prod"].";".$value["primeira_revisao"]." Horas;".$value["intervalo_revisao"]." Horas;$excecao;$parametros_adicionais\n");
		}

		fclose($file);

		if (file_exists("/tmp/{$filename}")) {
			system("mv /tmp/{$filename} xls/{$filename}");

			echo "xls/{$filename}";
		}
	}
	exit;
}

function getLinhasAtivas() {
	global $con, $login_fabrica;

	$sql = "SELECT linha, nome, codigo_linha, campos_adicionais FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo ORDER BY nome ASC";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {
		return pg_fetch_all($res);
	} else {
		return '';
	}
}

function getPecaReferenciaDescricao($peca) {
	global $con, $login_fabrica;

	if (empty($peca)) {
		return false;
	}

	$sql = "SELECT referencia || ' - ' || descricao AS peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca}";
	$res = pg_query($con, $sql);

	if (!pg_num_rows($res)) {
		return false;
	} else {
		return pg_fetch_result($res, 0, "peca");
	}
}

function getServicoDescricao($servico) {
	global $con, $login_fabrica;

	if (empty($servico)) {
		return false;
	}

	$sql = "SELECT descricao FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$servico}";
	$res = pg_query($con, $sql);

	if (!pg_num_rows($res)) {
		return false;
	} else {
		return pg_fetch_result($res, 0, "descricao");
	}
}

include 'cabecalho_new.php';

$plugins = array(
  	'shadowbox',
  	'alphanumeric',
  	'dataTable'
);

include('plugin_loader.php');

?>

<style>

div.row-produto, div.row-familia, #div-revisoes, #div-alterar_produto {
	display: none;
}

table.table {
	width: 100% !important;
}

</style>

<script>

$(function() {
	Shadowbox.init({
		onClose: function() {
			$("select").css({ visibility: "visible" });
		}	
	});

	$.dataTableLoad({ table: "#table_result_planilha" });

	$(".numeric").numeric();

	$(".btn-linha-acao").click(function() {
		let vlThis = $(this);
		let linha  = $(this).data("linha");
		let acao   = $(this).attr("data-acao"); 

		$.ajax({
			async: false,
			url: "cadastro_revisoes.php",
			type: "post",
			data: { ajax_ativa_linha: true, linha: linha, acao: acao },
			beforeSend:function(){
				if (acao == 'ativar') {
                    $(vlThis).html("Ativando...");
				} else {
					$(vlThis).html("Inativando...");
				}
            }
		})
		.done(function(data) {
			data = JSON.parse(data);
			if (data.error) {
				if (acao == 'ativar') {
					$(vlThis).html("Inativo");
				} else {
					$(vlThis).html("Ativo");
				}
				alert(data.error)
			} else if (data.success) {
				if (acao == 'ativar') {
					$(vlThis).html("Ativo");
					$(vlThis).removeClass('btn-danger').addClass('btn-success');
					$(vlThis).attr('data-acao', 'inativar');
				} else {
					$(vlThis).html("Inativo");
					$(vlThis).removeClass('btn-success').addClass('btn-danger');
					$(vlThis).attr('data-acao', 'ativar');
				}
				alert(data.success)
			}
		});
	});

	$("#cadastrar_revisao").change(function() {
		var value = $(this).val();

		switch(value){
			case "produto":
				$("div.row-produto").show();
				$("div.row-familia").hide();
				$("div.row-linha").hide();
				$("div.div-btn-listar-revisao").hide();
				$("#btn-listar-revisao").val('');
				$(".planilha").hide();
				$("#table_revisoes_auditadas").show();

				if ($("#produto_id").val().length == 0) {
					$("#div-revisoes").hide();
				}
				break;

			case "familia":
				$("div.row-familia").show();
				$("div.row-produto").hide();
				$("div.row-linha").hide();
				$("div.div-btn-listar-revisao").hide();
				$("#btn-listar-revisao").val('');
				$(".planilha").hide();
				$("#table_revisoes_auditadas").hide();

				if ($("#familia").val().length == 0) {
					$("#div-revisoes").hide();
				}

				$("#familia").change();
				break;

			case "linha":
				$("div.row-linha").show();
				$("div.row-produto").hide();
				$("div.row-familia").hide();
				$("#div-revisoes").hide();
				$("div.div-btn-listar-revisao").hide();
				$("#btn-listar-revisao").val('');
				$(".planilha").hide();
				$("#table_revisoes_auditadas").hide();
				break;

			default:
				$("div.row-familia").hide();
				$("div.row-produto").hide();
				$("#div-revisoes").hide();
				$("div.row-linha").hide();
				$("div.div-btn-listar-revisao").show();
				$(".planilha").show();
				$("#table_revisoes_auditadas").hide();
				$("#cadastrar_revisao").val('');
				$("#familia").val('');
				$("#btn-listar-revisao").val('sim');
				break;
		}

		$("#produto_id, #produto_referencia, #produto_descricao").val("");
		$("#produto_referencia, #produto_descricao").prop({ readonly: false });
		$("span[rel=lupa_produto]").show();
		$("#div-alterar_produto").hide();
		$("#div-excecoes_revisoes").find("table, hr").remove();
		$("button.remove_peca").click();
		$("#primeira_revisao_horas, #intervalo_revisao_horas, #tolerancia").val("");
		$("input[tipo=lista_basica]").attr({ produto: "", familia: "" });
	});

	$("#familia").change(function() {
		if ($(this).val().length > 0) {
			$("#div-revisoes").show();
			$("input[tipo=lista_basica]").attr({ produto: "", familia: $(this).val() });
		} else {
			$("#div-revisoes").hide();
			$("input[tipo=lista_basica]").attr({ produto: "", familia: "" });
		}
	});

	$("span[rel=lupa_produto]").click(function() {
		$.lupa($(this), ["valores-adicionais"]);
	});

	$(document).on("click", "span[rel=lupa_peca]", function() {
		$.lupa($(this), ["revisao", "produto", "familia"]);
	});

	$("#alterar_produto").click(function() {
		$("#produto_id, #produto_referencia, #produto_descricao").val("");
		$("#produto_referencia, #produto_descricao").prop({ readonly: false });
		$("span[rel=lupa_produto]").show();
		$("#div-revisoes").hide();
		$("#div-alterar_produto").hide();
		$("#div-excecoes_revisoes").find("table, hr").remove();
		$("button.remove_peca").click();
		$("#primeira_revisao_horas, #intervalo_revisao_horas, #tolerancia").val("");
		$("input[tipo=lista_basica]").attr({ produto: "", familia: "" });
	});

	$(document).on("keyup", "#peca_primeira_revisao, #peca_intervalo_revisao, input[id^=peca_revisao_excecao_]", function() {
		var value = $.trim($(this).val());

		if (value.length == 0) {
			$(this).data({
				id: "",
				referencia: "",
				descricao: ""
			});
		}
	});

	$("#adicionar_peca_primeira_revisao").click(function() {
		var peca_id = $("#peca_primeira_revisao").data("id");

		if (peca_id.length > 0 && $("#servico_primeira_revisao").val().length > 0) {
			if ($("input[name='revisao[primeira][pecas][]'][value="+peca_id+"]").length > 0) {
				alert("Peça já selecionada");
				return false;
			}

			var peca_referencia   = $("#peca_primeira_revisao").data("referencia");
			var peca_descricao    = $("#peca_primeira_revisao").data("descricao");
			var servico           = $("#servico_primeira_revisao").val();
			var servico_descricao = $("#servico_primeira_revisao > option:selected").text();

			$("#table_primeira_revisao > tbody").append("\
				<tr>\
					<td colspan='2' >"+peca_referencia+" - "+peca_descricao+"</td>\
					<td>"+servico_descricao+"</td>\
					<td class='tac' >\
						<input type='hidden' name='revisao[primeira][pecas][]' value='"+peca_id+"' />\
						<input type='hidden' name='revisao[primeira][servicos][]' value='"+servico+"' />\
						<button type='button' class='btn btn-danger btn-small remove_peca' >Remover</button>\
					</td>\
				</tr>\
			");

			$("#peca_primeira_revisao").val("").data({
				id: "",
				referencia: "",
				descricao: ""
			});

			$("#servico_primeira_revisao").val("");
		} else {
			alert("Selecione a Peça e o Serviço");
		}
	});

	$("#adicionar_peca_intervalo_revisao").click(function() {
		var peca_id = $("#peca_intervalo_revisao").data("id");

		if (peca_id.length > 0 && $("#servico_intervalo_revisao").val().length > 0) {
			if ($("input[name='revisao[intervalo][pecas][]'][value="+peca_id+"]").length > 0) {
				alert("Peça já selecionada");
				return false;
			}

			var peca_referencia   = $("#peca_intervalo_revisao").data("referencia");
			var peca_descricao    = $("#peca_intervalo_revisao").data("descricao");
			var servico           = $("#servico_intervalo_revisao").val();
			var servico_descricao = $("#servico_intervalo_revisao > option:selected").text();

			$("#table_intervalo_revisao > tbody").append("\
				<tr>\
					<td colspan='2' >"+peca_referencia+" - "+peca_descricao+"</td>\
					<td>"+servico_descricao+"</td>\
					<td class='tac' >\
						<input type='hidden' name='revisao[intervalo][pecas][]' value='"+peca_id+"' />\
						<input type='hidden' name='revisao[intervalo][servicos][]' value='"+servico+"' />\
						<button type='button' class='btn btn-danger btn-small remove_peca' >Remover</button>\
					</td>\
				</tr>\
			");

			$("#peca_intervalo_revisao").val("").data({
				id: "",
				referencia: "",
				descricao: ""
			});

			$("#servico_intervalo_revisao").val("");
		} else {
			alert("Selecione a Peça e o Serviço");
		}
	});

	$(document).on("click", "button.adicionar_peca_revisao_excecao", function() {
		var revisao = $(this).data("revisao");
		var peca_id = $("#peca_revisao_excecao_"+revisao).data("id");

		if (peca_id.length > 0 && $("#servico_revisao_excecao_"+revisao).val().length > 0) {
			if ($("input[name='revisao[excecao]["+revisao+"][pecas][]'][value="+peca_id+"]").length > 0) {
				alert("Peça já selecionada");
				return false;
			}

			var peca_referencia   = $("#peca_revisao_excecao_"+revisao).data("referencia");
			var peca_descricao    = $("#peca_revisao_excecao_"+revisao).data("descricao");
			var servico           = $("#servico_revisao_excecao_"+revisao).val();
			var servico_descricao = $("#servico_revisao_excecao_"+revisao+" > option:selected").text();

			$("#table_revisao_excecao_"+revisao+" > tbody").append("\
				<tr>\
					<td>"+peca_referencia+" - "+peca_descricao+"</td>\
					<td>"+servico_descricao+"</td>\
					<td class='tac' >\
						<input type='hidden' name='revisao[excecao]["+revisao+"][pecas][]' value='"+peca_id+"' />\
						<input type='hidden' name='revisao[excecao]["+revisao+"][servicos][]' value='"+servico+"' />\
						<button type='button' class='btn btn-danger btn-small remove_peca' >Remover</button>\
					</td>\
				</tr>\
			");

			$("#peca_revisao_excecao_"+revisao).val("").data({
				id: "",
				referencia: "",
				descricao: ""
			});

			$("#servico_revisao_excecao_"+revisao).val("");
		} else {
			alert("Selecione a Peça e o Serviço");
		}
	});

	$(document).on("click", "button.alterar_horas_excecao", function() {
		var nova_revisao    = prompt();
		var antiga_revisao  = $(this).data("revisao");
		var horas_intervalo = $("#intervalo_revisao_horas").val();

		if (nova_revisao != null) {
			var horas_intervalo = $("#intervalo_revisao_horas").val();

			if (nova_revisao % horas_intervalo != 0) {
				alert("As horas de uma exceção deve ser múltiplo do intervalo de tempo das revisões");
			} else if ($("#table_revisao_excecao_"+nova_revisao).length > 0) {
				alert("Exceção já cadastrada");
			} else {
				$("#table_revisao_excecao_"+antiga_revisao).find("thead th").html("INTERVALO DE "+nova_revisao+" HORAS <button type='button' class='btn btn-info btn-mini alterar_horas_excecao' data-revisao='"+nova_revisao+"' >Alterar Horas</button> <button type='button' class='btn btn-danger btn-mini remover_horas_excecao' data-revisao='"+nova_revisao+"' >Excluir Exceção</button>");
				$("#peca_revisao_excecao_"+antiga_revisao).attr({ id: "peca_revisao_excecao_"+nova_revisao });
				$("#table_revisao_excecao_"+antiga_revisao).find("input[name=lupa_config]").attr({ revisao: nova_revisao });
				$("#servico_revisao_excecao_"+antiga_revisao).attr({ id: "servico_revisao_excecao_"+nova_revisao });
				$("#table_revisao_excecao_"+antiga_revisao).find("button.adicionar_peca_revisao_excecao").data({ revisao: nova_revisao });
				$("#table_revisao_excecao_"+antiga_revisao).find("input[name='revisao[excecao]["+antiga_revisao+"][pecas][]']").attr({ name: "revisao[excecao]["+nova_revisao+"][pecas][]" });
				$("#table_revisao_excecao_"+antiga_revisao).find("input[name='revisao[excecao]["+antiga_revisao+"][servicos][]']").attr({ name: "revisao[excecao]["+nova_revisao+"][servicos][]" });
				$("#table_revisao_excecao_"+antiga_revisao).attr({ id: "table_revisao_excecao_"+nova_revisao });
			}
		}
	});

	$(document).on("click", "button.remover_horas_excecao", function() {
		var revisao  = $(this).data("revisao");

		if (confirm("Deseja realmente excluir o intervalo?")) {
			$("#table_revisao_excecao_"+revisao).remove();
		}
	});

	$("#adicionar_excecao").click(function() {
		var horas_intervalo = $("#intervalo_revisao_horas").val();
		var horas_excecao   = $("#horas_excecao").val();

		var produto_id = $("#produto_id").val();

		if (typeof produto_id == "undefined") {
			produto_id = "";
		}

		var familia_id = $("#familia").val();

		if (typeof familia_id == "undefined") {
			familia_id = "";
		}

		if (horas_excecao.length == 0) {
			alert("É necessário informar as horas");
		} else if (!(horas_intervalo > 0)) {
			alert("É necessário primeiro definir o intervalo de tempo das revisões para adicionar uma exceção");
		} else if (horas_excecao % horas_intervalo != 0) {
			alert("As horas de uma exceção deve ser múltiplo do intervalo de tempo das revisões");
		} else if ($("#table_revisao_excecao_"+horas_excecao).length > 0) {
			alert("Exceção já cadastrada");
		} else {
			$("#div-excecoes_revisoes").append("\
				<table id='table_revisao_excecao_"+horas_excecao+"' class='table table-bordered' >\
					<thead>\
						<tr>\
							<th class='titulo_coluna' colspan='3' >\
								<input type='hidden' name='revisao[excecao]["+horas_excecao+"][horas]' value='"+horas_excecao+"' />\
								INTERVALO DE "+horas_excecao+" HORAS \
								<button type='button' class='btn btn-info btn-mini alterar_horas_excecao' data-revisao='"+horas_excecao+"' >Alterar Horas</button> <button type='button' class='btn btn-danger btn-mini remover_horas_excecao' data-revisao='"+horas_excecao+"' >Excluir Exceção</button>\
							</th>\
						</tr>\
					</thead>\
					<tbody>\
						<tr>\
							<th class='titulo_coluna' colspan='3' >Peças de lançamento obrigatório</th>\
						</tr>\
						<tr>\
							<td>\
								Peça<br />\
								<div class='input-append'>\
			                		<input type='text' id='peca_revisao_excecao_"+horas_excecao+"' placeholder='Digite a referência ou descrição para pesquisar' data-id='' data-descricao='' data-referencia='' />\
			                		<span class='add-on' rel='lupa_peca' ><i class='icon-search' ></i></span>\
			                		<input type='hidden' name='lupa_config' disabled='disabled' tipo='lista_basica' produto='"+produto_id+"' familia='"+familia_id+"' parametro='referencia_descricao' revisao='"+horas_excecao+"' />\
			                	</div>\
							</td>\
							<td>\
								Serviço<br />\
								<div class='servico_select'>\
									<select id='servico_revisao_excecao_"+horas_excecao+"' >\
										<option value='' ></option>\
									</select>\
			                	</div>\
							</td>\
							<td class='tac' >\
								<br />\
								<button type='button' class='btn btn-success adicionar_peca_revisao_excecao' data-revisao='"+horas_excecao+"' >Adicionar</button>\
							</td>\
						</tr>\
						<tr>\
							<th class='titulo_coluna' colspan='3' >Peças Selecionadas</th>\
						</tr>\
						<tr>\
							<th class='titulo_coluna' >Peça</th>\
							<th class='titulo_coluna' >Serviço</th>\
							<th class='titulo_coluna' >Ação</th>\
						</tr>\
					</tbody>\
				</table>\
				<hr />\
			");

			var select_servico = $("#servico_primeira_revisao > option").clone();

			//$(select_servico).attr({ id: "servico_revisao_excecao_"+horas_excecao }).val("");

			$("#servico_revisao_excecao_"+horas_excecao).append(select_servico);
		}
	});

	$(document).on("click", "button.remove_peca", function() {
		$(this).parents("tr").remove();
	});

	$("#adicionar_revisoes_auditadas").click(function() {
		var intervalo_id = $("#intervalo_revisao_auditadas").val();

		if (intervalo_id.length > 0) {
			if ($("input[name='revisao_auditadas_lancadas_"+intervalo_id).length > 0) {
				alert("Intervalo de Revisão já Lançado");
				return false;
			}

			let todas_rev = '';
			let rev_p     = '';

			$('.rev_lancadas').each(function(index, element) {
			  rev_p =  $(element).val();

			  if (todas_rev.length > 0) {
					todas_rev += ", "+rev_p;
				}  else {
					todas_rev = rev_p;
				}
			});
			
			if (todas_rev.length > 0) {
				todas_rev += ", "+intervalo_id;
			}  else {
				todas_rev = intervalo_id;
			}

			$("#table_revisoes_auditadas > tbody").append("\
				<tr class='linha_auditadas_"+intervalo_id+"'>\
					<td style='text-align: center;' ><b>"+intervalo_id+" Horas</b></td>\
					<td class='tac' >\
						<input type='hidden' class='rev_lancadas' name='revisao_auditadas_lancadas_"+intervalo_id+"' value='"+intervalo_id+"' />\
						<button type='button' data-auditadas='"+intervalo_id+"' class='btn btn-info btn-small altera_revisao_auditada' >Alterar Horas</button>\
						<button type='button' data-auditadas='"+intervalo_id+"' class='btn btn-danger btn-small remove_revisao_auditada' >Excluir Exceção</button>\
					</td>\
				</tr>\
			");

			$("#adicionar_revisoes_auditadas").val("");
			$("#todas_rev_lancadas").val(todas_rev);

		} else {
			alert("Informe as Horas");
		}
	});

	$(document).on("click", "button.altera_revisao_auditada", function() {
		let intervalo_id = $(this).data('auditadas');

		$("#intervalo_revisao_auditadas").val(intervalo_id);
		$(".linha_auditadas_"+intervalo_id).remove();
	});

	$(document).on("click", "button.remove_revisao_auditada", function() {
		let intervalo_id = $(this).data('auditadas');

		$(".linha_auditadas_"+intervalo_id).remove();
	});

	$("span.regra_revisao").click(function() {
        var acao     = $(this).data("revisao-acao");
        var revisao  = $("#intervalo_revisao_auditadas").val();
        let dt       = $("#valores_adicionais_prod").val();
        let revisoes = '';
        if (dt != undefined) {
        	dt = JSON.parse(dt)
        	revisoes = dt.revisao
        } 

        if (!isNaN(revisao)) {
            revisao = parseInt(revisao);
        }

        switch(acao){
            case "-":
                if (!isNaN(revisao) && revisao > 0) {
                    if (revisao == revisoes.primeira.horas) {
                        return false;
                    } else {
                        var nova_revisao = revisao - parseInt(revisoes.intervalo.horas);

                        $("#intervalo_revisao_auditadas").val(nova_revisao);
                    }
                }
                break;

            case "+":
                if (!isNaN(revisao) && revisao > 0) {

                    var nova_revisao = revisao + parseInt(revisoes.intervalo.horas);

                    $("#intervalo_revisao_auditadas").val(nova_revisao);

                } else {
                    $("#intervalo_revisao_auditadas").val(revisoes.primeira.horas);
                }
                break;
        }

        carregaRegrasRevisao();
    });

	$("#visualizar_log").click(function() {

		let url = '';
		let log_fab = '<?=$login_fabrica?>';

		if ($("#cadastrar_revisao option:selected").val() == 'familia') {
			url = "relatorio_log_alteracao_new.php?parametro=tbl_produto&id="+log_fab+"*"+$("#familia option:selected").val();
		} else if ($("#cadastrar_revisao option:selected").val() == 'produto') {
			url = "relatorio_log_alteracao_new.php?parametro=tbl_produto&id="+log_fab+"*"+$("#produto_id").val();
		}

		 Shadowbox.open({
            content: url,
            player: "iframe",
            title:  "Visualizar Log",
            width:  1500,
            height: 800
        });
	});

});

$(document).ready(function() {
	setTimeout(function() {
		$(".alert").hide('slow');
	}, 3000);
});

function carregaRevisoesAuditadas(produto) {
	$.ajax({
			async: false,
			url: "cadastro_revisoes.php",
			type: "POST",
			data: { ajax_rev_auditadas: true, produto: produto }
		})
		.done(function(data) {
			data = JSON.parse(data);
			if (data.success) {
				let rev_data = JSON.parse(data.success);

				let todas_rev = '';

				$.each(rev_data, function(desc_rev, hora) {
					
					if (todas_rev.length > 0) {
						todas_rev += ", "+hora;
					}  else {
						todas_rev = hora;
					}

					$("#table_revisoes_auditadas > tbody").append("\
						<tr class='linha_auditadas_"+hora+"'>\
							<td style='text-align: center;' ><b>"+hora+" Horas</b></td>\
							<td class='tac' >\
								<input type='hidden' class='rev_lancadas' name='revisao_auditadas_lancadas_"+hora+"' value='"+hora+"' />\
								<button type='button' data-auditadas='"+hora+"' class='btn btn-info btn-small altera_revisao_auditada' >Alterar Horas</button>\
								<button type='button' data-auditadas='"+hora+"' class='btn btn-danger btn-small remove_revisao_auditada' >Excluir Exceção</button>\
							</td>\
						</tr>\
					");
				});

				$("#todas_rev_lancadas").val(todas_rev);
			}
		});
}

function carregaRegrasRevisao() {
    var revisao = $("#intervalo_revisao_auditadas").val();
    //var div     = $("div.div_regra_revisao");
    var revisao_regra_pecas;
    var revisao_regra_servicos;
    var horas_excecao = 0;
    var i = 0;

    let dt       = $("#valores_adicionais_prod").val();
    let revisoes = '';
    if (dt != undefined) {
    	dt = JSON.parse(dt)
    	revisoes = dt.revisao
    }

    if (revisoes.primeira.horas == revisao) {
        revisao_regra_pecas    = revisoes.primeira.pecas;
        revisao_regra_servicos = revisoes.primeira.servicos;
    } else {
        if (typeof revisoes.excecao != "undefined") {
            $.each(revisoes.excecao, function(horas, array) {
                if (revisao % horas == 0) {
                    var x = revisao / horas;

                    if (i == 0 || x < i) {
                        horas_excecao = horas;
                    }
                }
            });
        }

        if (horas_excecao > 0) {
            revisao_regra_pecas    = revisoes.excecao[horas_excecao].pecas;
            revisao_regra_servicos = revisoes.excecao[horas_excecao].servicos;
        } else {
            if (typeof revisoes.intervalo.pecas != "undefined") {
                revisao_regra_pecas    = revisoes.intervalo.pecas;
                revisao_regra_servicos = revisoes.intervalo.servicos;
            }
        }
    }

   /* if (typeof revisao_regra_pecas == "undefined") {
        $("div.div_regra_revisao").hide();
        $("div.div_regra_revisao > table > tbody > tr").remove();
    } else {
        $("div.div_regra_revisao > table > tbody").html("");
        $.each(revisao_regra_pecas, function(key, peca) {
            var servico = revisao_regra_servicos[key];

            $("div.div_regra_revisao > table > tbody").append("\
                <tr>\
                    <td style='color: #000;' >"+pecas_ajax[peca]+"</td>\
                    <td style='color: #000;' >"+servicos_ajax[servico]+"</td>\
                </tr>\
            ");
            $("div.div_regra_revisao strong.regra_revisao").text(revisao);
            $("div.div_regra_revisao").show();
        });
    }*/
}

function retorna_produto(data) {
	$("#produto_id").val(data.produto);
	$("#produto_referencia").val(data.referencia);
	$("#produto_descricao").val(data.descricao);
	$("#valores_adicionais_prod").val(data.valores_adicionais);

	$("#produto_referencia, #produto_descricao").prop({ readonly: true });
	$("span[rel=lupa_produto]").hide();
	$("#div-alterar_produto").show();

	if (typeof data.valores_adicionais == "undefined" || data.valores_adicionais.length == 0 || !data.valores_adicionais.match(/^{.+}$/)) {
		var revisao = {};
	} else {
		var revisao = JSON.parse(data.valores_adicionais);
	}
	carregaRevisaoProduto(revisao);

	$("#div-revisoes").show();
	$("input[tipo=lista_basica]").attr({ produto: data.produto, familia: "" });
	carregaRevisoesAuditadas(data.produto);
}

var servicos_ajax = {};
var pecas_ajax = {};

function buscaPecasAjax(pecasArray) {
	if (pecasArray.length > 0) {
		$.ajax({
			async: false,
			url: "cadastro_revisoes.php",
			type: "post",
			data: { ajax_pecas: true, pecas: pecasArray }
		})
		.done(function(data) {
			data = JSON.parse(data);

			if (typeof data.pecas != "undefined") {
				$.each(data.pecas, function(id, descricao) {
					pecas_ajax[id] = descricao;
				});
			}
		});
	}
}

function carregaRevisaoProduto(revisao) {
	$("#servico_primeira_revisao option").each(function() {
		servicos_ajax[$(this).val()] = $.trim($(this).text());
	});

	var pecasArray = [];
	
	if (typeof revisao == "object" && typeof revisao.revisao != "undefined") {
		revisao = revisao.revisao;
	}

	if (typeof revisao.primeira != "undefined" && typeof revisao.primeira.pecas != "undefined") {
		pecasArray = pecasArray.concat(revisao.primeira.pecas);
	}

	if (typeof revisao.intervalo != "undefined" && typeof revisao.intervalo.pecas != "undefined") {
		pecasArray = pecasArray.concat(revisao.intervalo.pecas);
	}

	if (typeof revisao.excecao != "undefined" && typeof revisao.excecao != "undefined") {
		$.each(revisao.excecao, function(horas, excecao_object) {
			if (excecao_object.pecas.length > 0) {
				pecasArray = pecasArray.concat(excecao_object.pecas);
			}
		});
	}

	buscaPecasAjax(pecasArray);

	if (typeof revisao.tolerancia != "undefined") {
		$("#tolerancia").val(revisao.tolerancia);
	}

	//Primeira revisão
	if (typeof revisao.primeira != "undefined" && typeof revisao.primeira.horas != "undefined") {
		$("#primeira_revisao_horas").val(revisao.primeira.horas);
	}

	if (typeof revisao.primeira != "undefined" && typeof revisao.primeira.pecas != "undefined") {
		$.each(revisao.primeira.pecas, function(key, peca) {
			var servico = revisao.primeira.servicos[key];

			$("#table_primeira_revisao > tbody").append("\
				<tr>\
					<td colspan='2' >"+pecas_ajax[peca]+"</td>\
					<td>"+servicos_ajax[servico]+"</td>\
					<td class='tac' >\
						<input type='hidden' name='revisao[primeira][pecas][]' value='"+peca+"' />\
						<input type='hidden' name='revisao[primeira][servicos][]' value='"+servico+"' />\
						<button type='button' class='btn btn-danger btn-small remove_peca' >Remover</button>\
					</td>\
				</tr>\
			");
		});
	}

	//Intervalo de tempo das revisões
	if (typeof revisao.intervalo != "undefined" && typeof revisao.intervalo.horas != "undefined") {
		$("#intervalo_revisao_horas").val(revisao.intervalo.horas);
	}

	if (typeof revisao.intervalo != "undefined" && typeof revisao.intervalo.pecas != "undefined") {
		$.each(revisao.intervalo.pecas, function(key, peca) {
			var servico = revisao.intervalo.servicos[key];

			$("#table_intervalo_revisao > tbody").append("\
				<tr>\
					<td colspan='2' >"+pecas_ajax[peca]+"</td>\
					<td>"+servicos_ajax[servico]+"</td>\
					<td class='tac' >\
						<input type='hidden' name='revisao[intervalo][pecas][]' value='"+peca+"' />\
						<input type='hidden' name='revisao[intervalo][servicos][]' value='"+servico+"' />\
						<button type='button' class='btn btn-danger btn-small remove_peca' >Remover</button>\
					</td>\
				</tr>\
			");
		});
	}

	//Exceção de regra ao intervalo de tempo das revisões
	var produto_id = $("#produto_id").val();

	if (typeof produto_id == "undefined") {
		produto_id = "";
	}

	var familia_id = $("#familia").val();

	if (typeof familia_id == "undefined") {
		familia_id = "";
	}

	if (typeof revisao.excecao != "undefined" && typeof revisao.excecao != "undefined") {
		$.each(revisao.excecao, function(horas, excecao_object) {
			$("#div-excecoes_revisoes").append("\
				<table id='table_revisao_excecao_"+horas+"' class='table table-bordered' >\
					<thead>\
						<tr>\
							<th class='titulo_coluna' colspan='3' >\
								<input type='hidden' name='revisao[excecao]["+horas+"][horas]' value='"+horas+"' />\
								INTERVALO DE "+horas+" HORAS \
								<button type='button' class='btn btn-info btn-mini alterar_horas_excecao' data-revisao='"+horas+"' >Alterar Horas</button> <button type='button' class='btn btn-danger btn-mini remover_horas_excecao' data-revisao='"+horas+"' >Excluir Exceção</button>\
							</th>\
						</tr>\
					</thead>\
					<tbody>\
						<tr>\
							<th class='titulo_coluna' colspan='3' >Peças de lançamento obrigatório</th>\
						</tr>\
						<tr>\
							<td>\
								Peça<br />\
								<div class='input-append'>\
			                		<input type='text' id='peca_revisao_excecao_"+horas+"' placeholder='Digite a referência ou descrição para pesquisar' data-id='' data-descricao='' data-referencia='' />\
			                		<span class='add-on' rel='lupa_peca' ><i class='icon-search' ></i></span>\
			                		<input type='hidden' name='lupa_config' disabled='disabled' tipo='lista_basica' produto='"+produto_id+"' familia='"+familia_id+"' parametro='referencia_descricao' revisao='"+horas+"' />\
			                	</div>\
							</td>\
							<td>\
								Serviço<br />\
								<div class='servico_select'>\
									<select id='servico_revisao_excecao_"+horas+"' >\
										<option value='' ></option>\
									</select>\
			                	</div>\
							</td>\
							<td class='tac' >\
								<br />\
								<button type='button' class='btn btn-success adicionar_peca_revisao_excecao' data-revisao='"+horas+"' >Adicionar</button>\
							</td>\
						</tr>\
						<tr>\
							<th class='titulo_coluna' colspan='3' >Peças Selecionadas</th>\
						</tr>\
						<tr>\
							<th class='titulo_coluna' >Peça</th>\
							<th class='titulo_coluna' >Serviço</th>\
							<th class='titulo_coluna' >Ação</th>\
						</tr>\
					</tbody>\
				</table>\
				<hr />\
			");

			var select_servico = $("#servico_primeira_revisao").clone();

			$(select_servico).attr({ id: "servico_revisao_excecao_"+horas }).val("");

			$("#table_revisao_excecao_"+horas).find("div.servico_select").html(select_servico);

			$.each(excecao_object.pecas, function(key, peca) {
				var servico = excecao_object.servicos[key];

				$("#table_revisao_excecao_"+horas+" > tbody").append("\
					<tr>\
						<td>"+pecas_ajax[peca]+"</td>\
						<td>"+servicos_ajax[servico]+"</td>\
						<td class='tac' >\
							<input type='hidden' name='revisao[excecao]["+horas+"][pecas][]' value='"+peca+"' />\
							<input type='hidden' name='revisao[excecao]["+horas+"][servicos][]' value='"+servico+"' />\
							<button type='button' class='btn btn-danger btn-small remove_peca' >Remover</button>\
						</td>\
					</tr>\
				");
			});

		});
	}
}

function retorna_pecas(data) {
	data = data[0];
	switch(data.revisao){
		case "primeira":
			$("#peca_primeira_revisao").val(data.referencia+" - "+data.descricao).data({
				id: data.peca,
				referencia: data.referencia,
				descricao: data.descricao
			});
			break;

		case "intervalo":
			$("#peca_intervalo_revisao").val(data.referencia+" - "+data.descricao).data({
				id: data.peca,
				referencia: data.referencia,
				descricao: data.descricao
			});
			break;

		default:
			$("#peca_revisao_excecao_"+data.revisao).val(data.referencia+" - "+data.descricao).data({
				id: data.peca,
				referencia: data.referencia,
				descricao: data.descricao
			});
			break;
	}
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

if(!empty($msg_sucesso)){
?>
	<div class="alert alert-success">
		<h4><?=$msg_sucesso?></h4>
    </div>
<?php
}
?>

<form name='frm_relatorio' method='POST' class='form-search form-inline tc_formulario'>

	<div class='titulo_tabela'>Manutenção de Revisões</div>

	<br />

	<div class="row-fluid" >
		<div class="span1"></div>
        <div class="span5">
            <div class="control-group <?=(in_array("cadastrar_revisao", $msg_erro["campos"])) ? "error" : ""?>">
                <label class="control-label" for="cadastrar_revisao">Selecione para quem será cadastrado a revisão</label>
                <div class='controls controls-row'>
					<div class='span12'>
		                <select id="cadastrar_revisao" name="cadastrar_revisao" >
		                	<option value=""></option>
		                	<option value="familia" <?=(getValue("cadastrar_revisao") == "familia") ? "selected" : "";?> >Familia</option>
		                	<?php if ($login_fabrica == 148) { ?>
		                		<option value="linha" <?=(getValue("cadastrar_revisao") == "linha") ? "selected" : "";?> >Linha</option>
		                	<?php } ?>
		                	<option value="produto" <?=(getValue("cadastrar_revisao") == "produto") ? "selected" : "";?> >Produto</option>
		                	
		                </select>
		            </div>
		        </div>
            </div>
        </div>
	</div>
	
	<?php 
		if (getValue("cadastrar_revisao") == "linha") {
			$display_row_produto = "style='display: inline-block;'";
		}
	?>

	<?php
	if (getValue("cadastrar_revisao") == "produto") {
		$display_row_produto = "style='display: inline-block;'";
	}

	if (strlen(getValue("produto_id")) > 0) {
		$produto_readonly = "readonly='readonly'";
		$produto_esconde_lupa = "style='display: none;'";
		$display_alterar_produto = "style='display: inline-block;'";
	}
	?>

	<div class='row-fluid row-produto' <?=$display_row_produto?> >
		<div class='span1'></div>
		<div class='span5'>
			<div class='control-group <?=(in_array("produto_referencia", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_referencia'>Referência Produto</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="hidden" id="produto_id" name="produto_id" value="<?=getValue('produto_id')?>" />
						<input type="text" id="produto_referencia" name="produto_referencia" <?=$produto_readonly?> maxlength="20" value="<?=getValue('produto_referencia')?>" >
						<span class='add-on' rel="lupa_produto" <?=$produto_esconde_lupa?> ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" disabled='disabled' tipo="produto" parametro="referencia" valores-adicionais="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span5'>
			<div class='control-group <?=(in_array("produto_descricao", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_descricao'>Descrição Produto</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" id="produto_descricao" name="produto_descricao" <?=$produto_readonly?> value="<?=getValue('produto_descricao')?>" >
						<span class='add-on' rel="lupa_produto" <?=$produto_esconde_lupa?> ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" disabled='disabled' tipo="produto" parametro="descricao" valores-adicionais="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span1'></div>
	</div>

	<div id="div-alterar_produto" class='row-fluid' <?=$display_alterar_produto?> >
		<div class="span1" ></div>
		<div class='control-group span2' >
			<button id="alterar_produto" type="button" class="btn btn-danger" >Alterar Produto</button>
		</div>
	</div>

	<?php
	$display_row_linha = "style='display: none;'";
	if (getValue("cadastrar_revisao") == "linha") {
		$display_row_linha = "style='display: inline-block;'";	
	}

	$dadosLinhas = getLinhasAtivas();
	?>

	<br />
	<br />
	<br />
	<br />	

	<div class="row-fluid row-linha" <?=$display_row_linha?> >
	<?php if (empty($dadosLinhas)) { ?>
			<div>
				<h4 class="alert">Nenhum Resultado Encontrado</h4>
			</div>
	<?php } else { ?>
			<table style="width: 100% !important;" id="table_linhas_ativas" class="table table-bordered table-striped table-hover table-large" >
				<thead>
					<tr>
						<th class="titulo_tabela" colspan="4" >Linhas de Produtos que Serão Auditadas na Revisão</th>
					</tr>
					<tr>
						<th class="titulo_coluna" >Código</th>
						<th class="titulo_coluna" >Descrição</th>
						<th class="titulo_coluna" >Status</th>
						<th class="titulo_coluna" >Log</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($dadosLinhas as $key => $value) { ?>
							<tr>
								<td class="tac"><?=$value["codigo_linha"]?></td>
								<td class="tac"><?=$value["nome"]?></td>

								<?php   if (!empty($value["campos_adicionais"])) { 
											$campos_adicionais = json_decode($value["campos_adicionais"], true);
											if ($campos_adicionais["auditada"]) {
												$btn = "<button type='button' class='btn btn-success btn-linha-acao' data-acao='inativar' data-linha='".$value['linha']."'>Ativo</button>";
											} else {
												$btn = "<button type='button' class='btn btn-danger btn-linha-acao' data-acao='ativar' data-linha='".$value['linha']."'>Inativo</button>";
											}
									    } else { 
									    	$btn = "<button type='button' class='btn btn-danger btn-linha-acao' data-acao='ativar' data-linha='".$value['linha']."'>Inativo</button>";
										} 
								?>
								<td class="tac"><?=$btn?></td>
								<td class="tac"><a class="btn btn-info" rel='shadowbox' name="btnAuditorLog" href="relatorio_log_alteracao_new.php?parametro=tbl_linha&id=<?php echo "$login_fabrica*".$value["linha"];?>">Ver</a></td>
							</tr>
					<?php } ?>
				</tbody>
			</table>
	<?php } ?>
	</div>


	<div class="row-fluid row-familia" <?=$display_row_familia?> >
		<div class="span1"></div>
        <div class="span5">
            <div class="control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>">
                <label class="control-label" for="familia">Família</label>
                <div class='controls controls-row'>
					<div class='span12'>
                		<select id="familia" name="familia" >
                			<option value=""></option>
                			<?php
                			$selectFamilia = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC ";
                			$resFamilia = pg_query($con, $selectFamilia);

                			while ($familia = pg_fetch_object($resFamilia)) {
                				$selected = (getValue("familia") == $familia->familia) ? "selected" : "";

                				echo "<option value='{$familia->familia}' {$selected} >{$familia->descricao}</option>";
                			}
                			?>
                		</select>
                	</div>
               	</div>
            </div>
        </div>
	</div>

	<?php
	if (strlen(getValue("familia")) > 0 || strlen(getValue("produto_id")) > 0) {
		$display_revisoes = "style='display: block;'";
	}
	?>

	<div id="div-revisoes" <?=$display_revisoes?> >
		<div class='titulo_tabela'>Revisões</div>

		<br />

		<div class="row-fluid" >
			<div class="span1"></div>
	        <div class="span2">
	            <div class="control-group <?=(in_array("tolerancia", $msg_erro["campos"])) ? "error" : ""?>">
	                <label class="control-label" for="tolerancia">Tolerância</label>
	                <div class='controls controls-row'>
						<div class='span12 input-append'>
	                		<input type="text" id="tolerancia" class="span6 numeric" name="revisao[tolerancia]" value="<?=getValue('revisao[tolerancia]')?>" />
	                		<span class='add-on' >%</span>
	                	</div>
	               	</div>
	            </div>
	        </div>
		</div>

		<?php
		$selectServico = "SELECT servico_realizado, descricao FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND ativo IS TRUE";
		$resServico = pg_query($con, $selectServico);
		?>

		<table id="table_primeira_revisao" class="table table-bordered" >
			<thead>
				<tr>
					<th class="titulo_coluna" colspan="4" >PRIMEIRA REVISÃO</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th class="titulo_coluna" >Horas</th>
					<th class="titulo_coluna" colspan="3" >Peças de lançamento obrigatório</th>
				</tr>
				<tr>
					<td class="tac" style="vertical-align: middle;" >
						<input type="text" id="primeira_revisao_horas" class="numeric" name="revisao[primeira][horas]" style="width: 50px;" value="<?=getValue('revisao[primeira][horas]')?>" />
					</td>
					<td>
						Peça<br />
						<div class='input-append'>
	                		<input type="text" id="peca_primeira_revisao" placeholder="Digite a referência ou descrição para pesquisar" data-id="" data-descricao="" data-referencia="" />
	                		<span class='add-on' rel="lupa_peca" ><i class='icon-search' ></i></span>
	                		<input type="hidden" name="lupa_config" disabled='disabled' tipo="lista_basica" produto="<?=getValue('produto_id')?>" familia="<?=getValue('familia')?>" parametro="referencia_descricao" revisao="primeira" />
	                	</div>
					</td>
					<td>
						Serviço<br />
						<div>
							<select id="servico_primeira_revisao" >
								<option value="" ></option>
								<?php
								while ($servico = pg_fetch_object($resServico)) {
									echo "<option value='{$servico->servico_realizado}' >{$servico->descricao}</option>";
								}
								?>
							</select>
	                	</div>
					</td>
					<td class="tac" >
						<br />
						<button type="button" id="adicionar_peca_primeira_revisao" class="btn btn-success" >Adicionar</button>
					</td>
				</tr>
				<tr>
					<th class="titulo_coluna" colspan="4" >Peças Selecionadas</th>
				</tr>
				<tr>
					<th class="titulo_coluna" colspan="2" >Peça</th>
					<th class="titulo_coluna" >Serviço</th>
					<th class="titulo_coluna" >Ação</th>
				</tr>
				<?php
				$pecas    = getValue("revisao[primeira][pecas]");

				if (count($pecas) > 0) {
					$servicos = getValue("revisao[primeira][servicos]");

					foreach ($pecas as $key => $peca) {
						$servico = $servicos[$key];

						$peca_referencia_descricao = getPecaReferenciaDescricao($peca);
						$servico_descricao         = getServicoDescricao($servico);

						echo "
							<tr>
								<td colspan='2' >{$peca_referencia_descricao}</td>
								<td>{$servico_descricao}</td>
								<td class='tac' >
									<input type='hidden' name='revisao[primeira][pecas][]' value='{$peca}' />
									<input type='hidden' name='revisao[primeira][servicos][]' value='{$servico}' />
									<button type='button' class='btn btn-danger btn-small remove_peca' >Remover</button>
								</td>
							</tr>
						";
					}
				}
				?>
			</tbody>
		</table>

		<hr />

		<table id="table_intervalo_revisao" class="table table-bordered" >
			<thead>
				<tr>
					<th class="titulo_coluna" colspan="4" >INTERVALO DE TEMPO DAS REVISÕES</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th class="titulo_coluna" >Horas</th>
					<th class="titulo_coluna" colspan="3" >Peças de lançamento obrigatório</th>
				</tr>
				<tr>
					<td class="tac" style="vertical-align: middle;" >
						<input type="text" id="intervalo_revisao_horas" class="numeric" name="revisao[intervalo][horas]" style="width: 50px;" value="<?=getValue('revisao[intervalo][horas]')?>" />
					</td>
					<td>
						Peça<br />
						<div class='input-append'>
	                		<input type="text" id="peca_intervalo_revisao" placeholder="Digite a referência ou descrição para pesquisar" data-id="" data-descricao="" data-referencia="" />
	                		<span class='add-on' rel="lupa_peca" ><i class='icon-search' ></i></span>
	                		<input type="hidden" name="lupa_config" disabled='disabled' tipo="lista_basica" produto="<?=getValue('produto_id')?>" familia="<?=getValue('familia')?>" parametro="referencia_descricao" revisao="intervalo" />
	                	</div>
					</td>
					<td>
						Serviço<br />
						<div>
							<select id="servico_intervalo_revisao" >
								<option value="" ></option>
								<?php
								pg_result_seek($resServico, 0);

								while ($servico = pg_fetch_object($resServico)) {
									echo "<option value='{$servico->servico_realizado}' >{$servico->descricao}</option>";
								}
								?>
							</select>
	                	</div>
					</td>
					<td class="tac" >
						<br />
						<button type="button" id="adicionar_peca_intervalo_revisao" class="btn btn-success" >Adicionar</button>
					</td>
				</tr>
				<tr>
					<th class="titulo_coluna" colspan="4" >Peças Selecionadas</th>
				</tr>
				<tr>
					<th class="titulo_coluna" colspan="2" >Peça</th>
					<th class="titulo_coluna" >Serviço</th>
					<th class="titulo_coluna" >Ação</th>
				</tr>
				<?php
				$pecas = getValue("revisao[intervalo][pecas]");

				if (count($pecas) > 0) {
					$servicos = getValue("revisao[intervalo][servicos]");

					foreach ($pecas as $key => $peca) {
						$servico = $servicos[$key];

						$peca_referencia_descricao = getPecaReferenciaDescricao($peca);
						$servico_descricao         = getServicoDescricao($servico);

						echo "
							<tr>
								<td colspan='2' >{$peca_referencia_descricao}</td>
								<td>{$servico_descricao}</td>
								<td class='tac' >
									<input type='hidden' name='revisao[intervalo][pecas][]' value='{$peca}' />
									<input type='hidden' name='revisao[intervalo][servicos][]' value='{$servico}' />
									<button type='button' class='btn btn-danger btn-small remove_peca' >Remover</button>
								</td>
							</tr>
						";
					}
				}
				?>
			</tbody>
		</table>

		<hr />

		<div class="row-fluid" >
			<div class="span1"></div>
	        <div class="span11">
	        	<h5>Adicionar exceções de regra ao intervalo de tempo das revisões</h5>
	        </div>
		</div>

		<br />
		
		<div class="row-fluid" >
			<div class="span1"></div>
	        <div class="span6">
	            <div class="control-group" >
	                <label class="control-label" >Horas</label>
	                <div class='controls controls-row'>
						<div class='span12'>
	                		<input type="text" id="horas_excecao" class="span3 numeric" />
	                		<button type="button" id="adicionar_excecao" class="btn btn-success" >Adicionar Exceção</button>
	                	</div>
	               	</div>
	            </div>
	        </div>
		</div>

		<br />

		<?php
		$excecoes = getValue("revisao[excecao]");

		if (count($excecoes) > 0) {
			$display_excecoes_revisoes = "style='display: block;'";
		}
		?>
		<div id="div-excecoes_revisoes" <?=$display_excecoes_revisoes?> >
			<?php
			if (count($excecoes) > 0) {
				foreach ($excecoes as $revisao => $revisaoData) {
					echo "
						<table id='table_revisao_excecao_{$revisao}' class='table table-bordered' >
							<thead>
								<tr>
									<th class='titulo_coluna' colspan='3' >
										<input type='hidden' name='revisao[excecao][{$revisao}][horas]' value='{$revisao}' />
										INTERVALO DE {$revisao} HORAS 
										<button type='button' class='btn btn-info btn-mini alterar_horas_excecao' data-revisao='{$revisao}' >Alterar Horas</button> <button type='button' class='btn btn-danger btn-mini remover_horas_excecao' data-revisao='{$revisao}' >Excluir Exceção</button>
									</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<th class='titulo_coluna' colspan='3' >Peças de lançamento obrigatório</th>
								</tr>
								<tr>
									<td>
										Peça<br />
										<div class='input-append'>
					                		<input type='text' id='peca_revisao_excecao_{$revisao}' placeholder='Digite a referência ou descrição para pesquisar' data-id='' data-descricao='' data-referencia='' />
					                		<span class='add-on' rel='lupa_peca' ><i class='icon-search' ></i></span>
					                		<input type='hidden' name='lupa_config' disabled='disabled' tipo='lista_basica' produto='".getValue("produto_id")."' familia='".getValue("familia")."' parametro='referencia_descricao' revisao='{$revisao}' />
					                	</div>
									</td>
									<td>
										Serviço<br />
										<div class='servico_select'>
											<select id='servico_revisao_excecao_{$revisao}' >
												<option value='' ></option>
											</select>
					                	</div>
									</td>
									<td class='tac' >
										<br />
										<button type='button' class='btn btn-success adicionar_peca_revisao_excecao' data-revisao='{$revisao}' >Adicionar</button>
									</td>
								</tr>
								<tr>
									<th class='titulo_coluna' colspan='3' >Peças Selecionadas</th>
								</tr>
								<tr>
									<th class='titulo_coluna' >Peça</th>
									<th class='titulo_coluna' >Serviço</th>
									<th class='titulo_coluna' >Ação</th>
								</tr>
					";

					if (count($revisaoData["pecas"]) > 0) {
						foreach ($revisaoData["pecas"] as $key => $peca) {
							$servico = $revisaoData["servicos"][$key];

							$peca_referencia_descricao = getPecaReferenciaDescricao($peca);
							$servico_descricao         = getServicoDescricao($servico);

							echo "
								<tr>
									<td>{$peca_referencia_descricao}</td>
									<td>{$servico_descricao}</td>
									<td class='tac' >
										<input type='hidden' name='revisao[intervalo][pecas][]' value='{$peca}' />
										<input type='hidden' name='revisao[intervalo][servicos][]' value='{$servico}' />
										<button type='button' class='btn btn-danger btn-small remove_peca' >Remover</button>
									</td>
								</tr>
							";
						}
					}

					echo "
							</tbody>
						</table>
						<hr />
					";
				}
			}
			?>
		</div>

		<br />
		<hr />

		<table id="table_revisoes_auditadas" class="table table-bordered" >
			<input type="hidden" name="valores_adicionais_prod" id="valores_adicionais_prod" value="" />
			<input type="hidden" name="todas_rev_lancadas" id="todas_rev_lancadas" value="" />
			<thead>
				<tr>
					<th class="titulo_coluna" colspan="2" >REVISÕES AUDITADAS</th>
				</tr>
				<tr>
					<td colspan="2" style="text-align: center;">
						<span>*Informe e Adicione as Revisões que Deverão ser Auditadas.</span>
					</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th class="titulo_coluna" >Horas</th>
					<th class="titulo_coluna" width="30%" >Ação</th>
				</tr>
				<tr>
					<td class="tac" style="vertical-align: middle;" >
						<div class="input-prepend input-append">
							<span class="add-on regra_revisao" data-revisao-acao="-" style="cursor: pointer;"><strong>-</strong></span>
							<input type="text" id="intervalo_revisao_auditadas" class="numeric" name="revisao_auditadas" style="width: 50px;" value="" readonly="readonly" />
							<span class="add-on regra_revisao" data-revisao-acao="+" style="cursor: pointer;"><strong>+</strong></span>
						</div>
					</td>
					<td style="text-align: center;">
						<button type="button" class="btn btn-success" id="adicionar_revisoes_auditadas">Adicionar</button>
					</td>
				</tr>
				<tr>
					<th class="titulo_coluna" colspan="2" >Intervalos Selecionados</th>
				</tr>
				<tr>
					<th class="titulo_coluna" >Horas</th>
					<th class="titulo_coluna" >Ação</th>
				</tr>
				<?php
				$prod_id = $_POST["produto_id"];
				if (count($prod_id) > 0) {

					$sqlPA = "SELECT parametros_adicionais FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $prod_id ";
					$resPA = pg_query($con, $sqlPA);
					if (pg_num_rows($resPA) > 0) {
						$parametros_adicionais = json_decode(pg_fetch_result($resPA, 0, 'parametros_adicionais'), true);
						$parametros_adicionais_rev = $parametros_adicionais["auditoria_revisao"];
					}

					if (count($parametros_adicionais_rev) > 0) {
						$i = 1;
						foreach ($parametros_adicionais_rev as $key => $rev) {
							echo "
								<tr class='linha_auditadas_".$rev["revisao".$i]."'>
									<td style='text-align: center;' ><b>".$rev["revisao".$i]." Horas</b></td>
									<td class='tac' >
										<input type='hidden' class='rev_lancadas' name='revisao_auditadas_lancadas_".$rev["revisao".$i]."' value='".$rev["revisao".$i]."' />\
										<button type='button' data-auditadas='".$rev["revisao".$i]."' class='btn btn-info btn-small altera_revisao_auditada' >Alterar Horas</button>\
										<button type='button' data-auditadas='".$rev["revisao".$i]."' class='btn btn-danger btn-small remove_revisao_auditada' >Excluir Exceção</button>\
									</td>
								</tr>
							";

							$i++;
						}
					}
				}
				?>
			</tbody>
		</table>

		<br />
		<hr />

		<div class="div_log tac">
			<button type="submit" class="btn" name="gravar_revisao" >Gravar Regra de Revisão</button>
			<button type="button" class="btn btn-info" name="visualizar_log" id="visualizar_log">Visualizar Log</button>
		</div>
	</div>

	<br />

	<div class="row-fluid div-btn-listar-revisao">
		<div class="span12 tac">
        	<button class="btn btn-primary" id="btn-listar-revisao" name="btn-listar-revisao" value="sim">Listar Todas as Revisões</button>
        </div>
	</div>

</form>

<div class="planilha">
<?php

if (count($dadosTabela) > 0 && $_POST["btn-listar-revisao"] == 'sim' && empty($msg_erro)) {
?>

	<table id="table_result_planilha" class="table table-striped table-bordered table-hover table-fixed" >
		<thead>
			<tr class="titulo_coluna">
				<th style="text-align: center;">Linha</th>
				<th style="text-align: center;">Familia</th>
				<th style="text-align: center;">Produto</th>
				<th style="text-align: center;">1ª Revisão</th>
				<th style="text-align: center;">Intervalo das Revisões</th>
				<th style="text-align: center;">Exceções de Intervalos</th>
				<th style="text-align: center;">Intervalos Auditados</th>
			</tr>
		</thead>
		<tbody>
<?php 
			$y = 0;
			foreach ($dadosTabela as $key => $value) {
				$ppA = [];
				$parametros_adicionais = "";
				if (!empty($value["parametros_adicionais"])) {
					foreach (json_decode($value["parametros_adicionais"], true) as $chave => $valor) {
						$ppA[] = $valor." Horas";
					}

					$parametros_adicionais = implode(" / ", $ppA);
				}

				$ex = [];
				$excecao = "";

				if (!empty($value["excecao"])) {
					foreach (json_decode($value["excecao"], true) as $k => $v) {
						$ex[] = $k;
					}

					$excecao = implode(" / ", $ex);
				}
?>
				<tr>
					<td class="tac"><?=$value["nome_linha"]?></td>
					<td class="tac"><?=$value["nome_familia"]?></td>
					<td class="tac"><?=$value["desc_prod"]?></td>
					<td class="tac" nowrap><?=$value["primeira_revisao"]?> Horas</td>
					<td class="tac"><?=$value["intervalo_revisao"]?> Horas</td>
					<td class="tac"><?=$excecao?></td>
					<td class="tac"><?=$parametros_adicionais?></td>
				</tr>
<?php 
			$y++;
			}
?>
		</tbody>
	</table>
<?php 
	$jsonPOST = excelPostToJson($_POST);
?>

	<div id='gerar_excel' class="btn_excel planilha">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
	</div>
<?php
} else if ($_POST["btn-listar-revisao"] == 'sim' && count($dadosTabela) == 0) {
?>
	<div class="tac">
		<h4 class="alert">Nenhum Resultado Encontrado</h4>
	</div>
<?php
} else if (!empty($msg_erro) && $_POST["btn-listar-revisao"] == 'sim') {
?>
	<div class="tac">
		<h4 class="alert alert-error">Erro ao Realizar Consulta</h4>
	</div>
<?php 
}
?>

</div>

<?php

include "rodape.php";

?>
