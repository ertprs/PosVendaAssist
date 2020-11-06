<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";;
include "autentica_admin.php";
include "funcoes.php";

if (isset($_POST["ajax_grava_nota_fiscal"]) == true) {
	try {
		$nota_fiscal = $_POST["nota_fiscal"];
		$faturamento = $_POST["faturamento"];

		if (empty($nota_fiscal)) {
			throw new Exception("Informe a nota fiscal");
		}

		if (empty($faturamento)) {
			throw new Exception("Faturamento não informado");
		}

		$sql = "UPDATE tbl_faturamento SET nota_fiscal = '{$nota_fiscal}' WHERE fabrica = {$login_fabrica} AND faturamento = {$faturamento}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao atualizar número da nota fiscla");
		}

		$retorno = array("nota_fiscal" => $nota_fiscal);
	} catch(Exception $e) {
		$retorno = array("erro" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

if (isset($_POST["btn_finaliza"])) {
	try {

		pg_query($con, "BEGIN");

		$pecas       = $_POST["faturamento_item"];
		$nota_fiscal = $_POST["nota_fiscal"];
		$posto       = $_POST["posto"];

		$parcial = false;

		foreach ($pecas as $faturamento_item => $peca) {
			if ($peca["qtde_inspecionada"] > $peca["qtde"]) {
				throw new Exception("A quantidade conferida não pode ser maior que a quantidade devolvida");
			} else if($peca["qtde_inspecionada"] < $peca["qtde"]) {
				$parcial = true;
			}

			if (!strlen($peca["qtde_inspecionada"])) {
				$peca["qtde_inspecionada"] = 0;
			}

			$update = "UPDATE tbl_faturamento_item SET 
							qtde_inspecionada = {$peca['qtde_inspecionada']}
					   WHERE faturamento = {$faturamento}
					   AND faturamento_item = {$faturamento_item}";
			$res = pg_query($con, $update);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar conferência da nota fiscal de devolução");
			}
		}
		
		$faturamento = $_GET["faturamento"];

		if ($parcial === true) {
			$mensagem_comunicado = "Foi acusado o recebimento parcial de sua NF. n° $nota_fiscal, favor entrar em contato urgente para sua regularização";

			$sql = "INSERT INTO tbl_comunicado 
					(descricao, mensagem, tipo, fabrica, obrigatorio_os_produto, obrigatorio_site, posto, ativo) 
					VALUES 
					('Nota Fiscal de Devolução - LGR', '$mensagem_comunicado', 'LGR', $login_fabrica, 'f', 't', $posto, 't')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar conferência da nota fiscal de devolução");
			}

			$sql = "UPDATE tbl_faturamento SET devolucao_concluida=true
				FROM 
					(
					SELECT 
						sum(qtde) AS qtde, 
						sum(qtde_inspecionada) AS conferida, 
						faturamento 
					FROM 
						tbl_faturamento_item 
					WHERE faturamento={$faturamento}
					GROUP BY faturamento 
					) e
				WHERE e.faturamento = tbl_faturamento.faturamento
				AND qtde > conferida
				AND e.faturamento = {$faturamento};";
			$res = pg_query($con, $sql);

		}

		if (empty($faturamento)) {
			throw new Exception("Faturamento não informado");
		}

		$sql = "UPDATE tbl_faturamento
				SET conferencia = CURRENT_TIMESTAMP
				WHERE fabrica = {$login_fabrica}
				AND faturamento = {$faturamento}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao finalizar conferência da nota fiscal de devolução");
		}

		$sql = "Update tbl_faturamento set devolucao_concluida = true
				from (select sum(qtde) as qtde, sum(qtde_inspecionada) as conferida, faturamento from tbl_faturamento_item where faturamento =$faturamento
				group by faturamento ) e
				where e.faturamento = tbl_faturamento.faturamento
				and qtde = conferida
				and e.faturamento = $faturamento;";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao finalizar conferência da nota fiscal de devolução");
		}
		$msg_sucesso = "Conferência finalizada";

		pg_query($con, "COMMIT");
	} catch(Exception $e) {
		$msg_erro = $e->getMessage();
	}
}

if (isset($_POST["btn_gravar"])) {
	try {
		pg_query($con, "BEGIN");

		$pecas       = $_POST["faturamento_item"];
		$nota_fiscal = $_POST["nota_fiscal"];
		$posto       = $_POST["posto"];

		$parcial = false;

		foreach ($pecas as $faturamento_item => $peca) {
			if ($peca["qtde_inspecionada"] > $peca["qtde"]) {
				throw new Exception("A quantidade conferida não pode ser maior que a quantidade devolvida");
			} else if($peca["qtde_inspecionada"] < $peca["qtde"]) {
				$parcial = true;
			}

			if (!strlen($peca["qtde_inspecionada"])) {
				$peca["qtde_inspecionada"] = 0;
			}

			$update = "UPDATE tbl_faturamento_item SET 
							qtde_inspecionada = {$peca['qtde_inspecionada']}
					   WHERE faturamento = {$faturamento}
					   AND faturamento_item = {$faturamento_item}";
			$res = pg_query($con, $update);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar conferência da nota fiscal de devolução");
			}
		}

		if ($parcial === true) {
			$mensagem_comunicado = "Foi acusado o recebimento parcial de sua NF. n° $nota_fiscal, favor entrar em contato urgente para sua regularização";

			$sql = "INSERT INTO tbl_comunicado 
					(descricao, mensagem, tipo, fabrica, obrigatorio_os_produto, obrigatorio_site, posto, ativo) 
					VALUES 
					('Nota Fiscal de Devolução - LGR', '$mensagem_comunicado', 'LGR', $login_fabrica, 'f', 't', $posto, 't')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar conferência da nota fiscal de devolução");
			}
		}

		$sql = "Update tbl_faturamento set devolucao_concluida = true
				from (select sum(qtde) as qtde, sum(qtde_inspecionada) as conferida, faturamento from tbl_faturamento_item where faturamento =$faturamento
				group by faturamento ) e
				where e.faturamento = tbl_faturamento.faturamento
				and qtde = conferida
				and e.faturamento = $faturamento;";
		$res = pg_query($con, $sql);

		pg_query($con, "COMMIT");
	} catch(Exception $e) {
		$msg_erro = $e->getMessage();

		pg_query($con, "ROLLBACK");
	}
}

?>

<!DOCTYPE html>
<html>
<head>
	<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

	<style>

	label.control-label {
		font-weight: bold;
	}

	table.table-no-margin {
		margin-bottom: 0px;
	}

	#nota_fiscal_input {
		display: none;
	}

	</style>

	<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script src="bootstrap/js/bootstrap.js"></script>
	<script src="plugins/jquery.alphanumeric.js" ></script>

	<script>
	$(function() {
		$("input.numeric").numeric();

		$("#nota_fiscal_text").dblclick(function() {
			$(this).hide();
			$("#nota_fiscal_input").show();
		});

		$("#gravar_nota_fiscal_devolucao").click(function() {
			var nota_fiscal = $.trim($("#nota_fiscal_input > input").val());
			var faturamento = $("#nota_fiscal_text").data("faturamento");

			if (typeof nota_fiscal != "undefined") {
				$.ajax({
					url: "lgr_conferencia.php",
					type: "post",
					data: { ajax_grava_nota_fiscal: true, nota_fiscal: nota_fiscal, faturamento: faturamento },
					beforeSend: function() {
						$("#nota_fiscal_input").hide();
						$("#nota_fiscal_input").after("<div class='alert alert-info' style='display: inline-block; margin: 0px;' >Gravando nota fiscal aguarde...</div>");
					}
				}).always(function(data) {
					data = $.parseJSON(data);

					if (data.erro) {
						alert(data.erro);
						$("#nota_fiscal_input").show();
					} else {
						$("#nota_fiscal_text").data("nota-fiscal", data.nota_fiscal);
						$("#nota_fiscal_text > label").text(data.nota_fiscal);
						$("#nota_fiscal_input > input").val(data.nota_fiscal);
						$("#nota_fiscal_text").show();
					}

					$("#nota_fiscal_input").next("div.alert-info").remove();
				});
			} else {
				alert("Informe o número da nota fiscal");
			}
		});

		$("#cancela_nota_fiscal_devolucao").click(function() {
			var nota_fiscal = $("#nota_fiscal_text").data("nota-fiscal");

			$("#nota_fiscal_text > label").text(nota_fiscal);
			$("#nota_fiscal_input > input").val(nota_fiscal);
			$("#nota_fiscal_input").hide();
			$("#nota_fiscal_text").show();
		});		
	});

	</script>
</head>
<body>
	<div class="container" >
		<?php
		$faturamento = $_REQUEST["faturamento"];

		if (!empty($msg_erro)) {
		?>
			<div class="alert alert-danger"><h4><?=$msg_erro?></h4></div>
		<?php
		}

		if (!empty($msg_sucesso)) {
		?>
			<div class="alert alert-success"><h4><?=$msg_sucesso?></h4></div>
		<?php
		}


		if (empty($faturamento)) {
		?>
			<div class="alert alert-danger"><h4>Faturamento não informado</h4></div>
		<?php
		} else {
		?>
			<div class="page-header"><h4>Conferência da Nota Fiscal de Devolução</h4></div>

			<?php
			$sql = "SELECT
						DISTINCT tbl_faturamento.faturamento,
						tbl_faturamento.cfop,
						tbl_faturamento.nota_fiscal,
						tbl_extrato.extrato,
						TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS data_emissao,
						tbl_posto.nome AS razao_social,
						tbl_posto.cnpj,
						tbl_posto.ie AS inscricao_estadual,
						tbl_posto_fabrica.contato_endereco AS endereco,
						tbl_posto_fabrica.contato_cidade AS cidade,
						tbl_posto_fabrica.contato_estado AS estado,
						tbl_posto_fabrica.contato_cep AS cep,
						tbl_faturamento.conferencia,
						tbl_posto.posto
					FROM tbl_faturamento
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_faturamento.distribuidor
					INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
					INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao OR tbl_extrato.extrato = tbl_faturamento.extrato_devolucao
					WHERE tbl_faturamento.fabrica = {$login_fabrica}
					AND tbl_extrato.fabrica = {$login_fabrica}
					AND tbl_faturamento.faturamento = {$faturamento}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$posto              = pg_fetch_result($res, 0, "posto");
				$nota_fiscal        = pg_fetch_result($res, 0, "nota_fiscal");
				$extrato            = pg_fetch_result($res, 0, "extrato");
				$cfop               = pg_fetch_result($res, 0, "cfop");
				$data_emissao       = pg_fetch_result($res, 0, "data_emissao");
				$razao_social       = pg_fetch_result($res, 0, "razao_social");
				$cnpj               = pg_fetch_result($res, 0, "cnpj");
				$inscricao_estadual = pg_fetch_result($res, 0, "inscricao_estadual");
				$endereco           = pg_fetch_result($res, 0, "endereco");
				$cidade             = pg_fetch_result($res, 0, "cidade");
				$estado             = pg_fetch_result($res, 0, "estado");
				$cep                = pg_fetch_result($res, 0, "cep");
				$conferencia        = pg_fetch_result($res, 0, "conferencia");
				?>

				<h5>Extrato: <?=$extrato?></h5>
				<h5>Nota Fiscal: 
					<span id="nota_fiscal_text" data-nota-fiscal="<?=$nota_fiscal?>" data-faturamento="<?=$faturamento?>" >
						<label style="display: inline"><?=$nota_fiscal?></label>
						&nbsp;<span style="color: #FF0000;">(Para editar o número da nota fiscal dê 2 cliques)</span>
					</span>
					<span id="nota_fiscal_input" style="vertica-align: middle;" >
						<input type="text" name="nota_fiscal_devolucao" value="<?=$nota_fiscal?>" style="margin-bottom: 0px;" maxlength="20" />
						<button type="button" id="gravar_nota_fiscal_devolucao" class="btn btn-success btn-mini" >Gravar</button>
						<button type="button" id="cancela_nota_fiscal_devolucao" class="btn btn-danger btn-mini" >Cancelar</button>
					</span>
				</h5>

				<table class="table table-bordered table-no-margin" >
					<tr class="titulo_coluna" >
						<th class="tal" >Natureza</th>
						<th class="tal" >CFOP</th>
						<th class="tal" >Data de Emissão</th>
					</tr>
					<tr>
						<td>Devolução de Garantia</td>
						<td><?=$cfop?></td>
						<td><?=$data_emissao?></td>
					</tr>
				</table>

				<table class="table table-bordered table-no-margin" >
					<tr class="titulo_coluna" >
						<th class="tal" >Razão Social</th>
						<th class="tal" >CNPJ</th>
						<th class="tal" >Inscrição Estadual</th>
					</tr>
					<tr>
						<td><?=$razao_social?></td>
						<td><?=$cnpj?></td>
						<td><?=$inscricao_estadual?></td>
					</tr>
				</table>

				<table class="table table-bordered table-no-margin" >
					<tr class="titulo_coluna" >
						<th class="tal" >Endereço</th>
						<th class="tal" >Cidade</th>
						<th class="tal" >Estado</th>
						<th class="tal" >CEP</th>
					</tr>
					<tr>
						<td><?=$endereco?></td>
						<td><?=$cidade?></td>
						<td><?=$estado?></td>
						<td><?=$cep?></td>
					</tr>
				</table>

				<form method="post" >
					<table class="table table-bordered table-no-margin" >
						<tr class="titulo_coluna" >
							<th class="tal" >Código</th>
							<th class="tal" >Descrição</th>
							<th class="tal" >Qtde a devolver</th>
							<th class="tal" >Qtde devolvida</th>
							<th class="tal" >Qtde recebida</th>
							<th class="tal" >Preço</th>
							<th class="tal" >Total</th>
							<th class="tal" nowrap >% ICMS</th>
							<th class="tal" nowrap >% IPI</th>
						</tr>
						<tr>
							<?php
							$sqlItem = "SELECT 
											tbl_faturamento_item.faturamento_item,
											tbl_peca.referencia AS codigo,
											tbl_peca.descricao,
											lgr.qtde AS qtde_devolver,
											tbl_faturamento_item.qtde AS qtde_devolvida,
											tbl_faturamento_item.preco,
											(tbl_faturamento_item.preco * tbl_faturamento_item.qtde) AS total,
											tbl_faturamento_item.aliq_icms AS icms,
											tbl_faturamento_item.aliq_ipi AS ipi,
											tbl_faturamento_item.qtde_inspecionada AS qtde_conferida
										FROM tbl_faturamento_item
										INNER JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca AND tbl_peca.fabrica = {$login_fabrica}
										INNER JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato = tbl_faturamento_item.extrato_devolucao AND tbl_extrato_lgr.peca = tbl_faturamento_item.peca
										JOIN tbl_faturamento_item lgr ON lgr.faturamento = tbl_extrato_lgr.faturamento and lgr.os_item = tbl_faturamento_item.os_item
										WHERE tbl_faturamento_item.faturamento = {$faturamento}
										order by tbl_peca.referencia";
							$resItem = pg_query($con, $sqlItem);

							$total_base_icms  = 0;
							$total_valor_icms = 0;
							$total_base_ipi   = 0;
							$total_valor_ipi  = 0;
							$total_nota       = 0;

							if (pg_num_rows($resItem) > 0) {
								while ($item = pg_fetch_object($resItem)) {
									if ($item->icms > 0) {
										$total_base_icms  += $item->total;
										$total_valor_icms += $item->total * $item->icms / 100;
									}

									if ($item->ipi > 0) {
										$total_base_ipi  += $item->total;
										$total_valor_ipi += $item->total * $item->ipi / 100;
									}

									$total_nota += $item->total;
									?>
									<tr>
										<td><?=$item->codigo?></td>
										<td><?=$item->descricao?></td>
										<td class="tar" ><?=$item->qtde_devolver?></td>
										<td class="tar" ><?=$item->qtde_devolvida?></td>
										<td class="tar" >
											<?php
											if (empty($conferencia)) {
											?>
												<input type="hidden" class="span1 numeric tar" name="faturamento_item[<?=$item->faturamento_item?>][qtde]" value="<?=$item->qtde_devolvida?>" />
												<input type="text" class="span1 numeric tar" name="faturamento_item[<?=$item->faturamento_item?>][qtde_inspecionada]" value="<?=$item->qtde_conferida?>" />
											<?php
											} else {
												echo $item->qtde_conferida;
											}
											?>
										</td>
										<td class="tar" ><?=number_format($item->preco, 2, ",", ".")?></td>
										<td class="tar" ><?=number_format($item->total, 2, ",", ".")?></td>
										<td class="tar" ><?=$item->icms?></td>
										<td class="tar" ><?=$item->ipi?></td>
									</tr>
								<?php
								}
							}
							?>
						</tr>
					</table>

					<table class="table table-bordered table-no-margin" >
						<tr class="titulo_coluna" >
							<th class="tal" >Base ICMS</th>
							<th class="tal" >Valor ICMS</th>
							<th class="tal" >Base IPI</th>
							<th class="tal" >Valor IPI</th>
							<th class="tal" >Total da Nota</th>
						</tr>
						<tr>
							<td><?=$total_base_icms?></td>
							<td><?=$total_valor_icms?></td>
							<td><?=$total_base_ipi?></td>
							<td><?=$total_valor_ipi?></td>
							<td><?=$total_nota?></td>
						</tr>
					</table>

					<br />

					<?php
					if (empty($conferencia)) {
					?>
						<p class="tac" >
						<b class="asteristico" style="float: none;">após a finalização da conferência não será mais possível realizar alterações</b>
						<br />
						<input type="hidden" name="nota_fiscal" value="<?=$nota_fiscal?>" />
						<input type="hidden" name="posto" value="<?=$posto?>" />
						<button type="submit" id="btn_gravar" name="btn_gravar" class="btn btn-primary">Gravar</button>
						<button type="submit" id="btn_finaliza" name="btn_finaliza" class="btn btn-success">Finalizar Conferência</button>
					</p>
					<?php
					} else {
					?>
						<div class="alert alert-success"><h4>Conferência finalizada</h4></div>
					<?php
					}
					?>
				</form>

				<br />
			<?php
			} else {
			?>
				<div class="alert alert-danger"><h4>Nota Fiscal de Devolução não encontrada</h4></div>
			<?php
			}
		}
		?>

		<br /><br /><br />
	</div>
</body>
</html>
