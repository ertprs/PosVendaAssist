<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';

$login_fabrica = 149;

$array_estado = array(
	'AC' => 'Acre',
	'AL' => 'Alagoas',
	'AM' => 'Amazonas',
	'AP' => 'Amapá',
	'BA' => 'Bahia',
	'CE' => 'Ceara',
	'DF' => 'Distrito Federal',
	'ES' => 'Espírito Santo',
	'GO' => 'Goiás',
	'MA' => 'Maranhão',
	'MG' => 'Minas Gerais',
	'MS' => 'Mato Grosso do Sul',
	'MT' => 'Mato Grosso',
	'PA' => 'Pará',
	'PB' => 'Paraíba',
	'PE' => 'Pernambuco',
	'PI' => 'Piauí­',
	'PR' => 'Paraná',
	'RJ' => 'Rio de Janeiro',
	'RN' => 'Rio Grande do Norte',
	'RO' => 'Rondônia',
	'RR' => 'Roraima',
	'RS' => 'Rio Grande do Sul',
	'SC' => 'Santa Catarina',
	'SE' => 'Sergipe',
	'SP' => 'São Paulo',
	'TO' => 'Tocantins'
);

function validaCep() {
	global $_POST;

	$cep = $_POST["cep"];

	if (!empty($cep)) {
		try {
			$endereco = CEP::consulta($cep);

			if (!is_array($endereco)) {
				throw new Exception("CEP inválido");
			}
		} catch (Exception $e) {
			throw new Exception("CEP inválido");
		}
	}
}

function validaEstado() {
	global $array_estado, $_POST;

	$estado = strtoupper($_POST["estado"]);

	if (!empty($estado) && !in_array($estado, array_keys($array_estado))) {
		throw new Exception("Estado inválido");
	}
}

function validaCidade() {
	global $con, $_POST;

	$cidade = utf8_decode($_POST["cidade"]);
	$estado = strtoupper($_POST["estado"]);

	if (!empty($cidade) && !empty($estado)) {
		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' AND UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}'))";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Cidade não encontrada".$sql);
		}
	}
}

function validaFamilia() {
	global $con, $login_fabrica, $_POST;

	$familia = $_POST["familia"];

	if (!empty($familia)) {
		$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia} AND ativo IS TRUE";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Família inválida");
		}
	}
}

function validaProduto() {
	global $con, $login_fabrica, $_POST;

	$produto = $_POST["produto"];
	$familia = $_POST["familia"];

	if (!empty($produto) && !empty($familia)) {
		$sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto} AND familia = {$familia} AND ativo IS TRUE";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Produto inválido");
		}
	}
}

function validaEmail() {
	global $_POST;

	$email = $_POST["email"];

	if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		throw new Exception("Email inválido");
	}
}

if ($_POST["ajax_enviar"]) {
	$regras = array(
		"notEmpty" => array(
			"nome",
			"email",
			"telefone",
			"departamento",
			"assunto",
			"familia",
			"produto",
			"mensagem"
		),
		"validaCep" => "cep",
		"validaEstado" => "estado",
		"validaCidade" => "cidade",
		"validaFamilia" => "familia",
		"validaProduto" => "produto",
		"validaEmail" => "email"
	);

	$msg_erro = array(
		"msg"    => array(),
		"campos" => array()
	);

	foreach ($regras as $regra => $campo) {
		switch ($regra) {
			case "notEmpty":
				foreach($campo as $input) {
					$valor = trim($_POST[$input]);

					if (in_array($input, array("familia", "produto")) && strtolower($_POST["departamento"]) != "assistência técnica") {
						continue;
					}

					if (empty($valor)) {
						$msg_erro["msg"]["obg"] = utf8_encode("Preencha todos os campos obrigatórios");
						$msg_erro["campos"][]   = $input;
					}
				}
				break;

			default:
				$valor = trim($_POST[$campo]);

				if (!empty($valor)) {
					try {
						call_user_func($regra);
					} catch(Exception $e) {
						$msg_erro["msg"][]    = utf8_encode($e->getMessage());
						$msg_erro["campos"][] = $campo;
					}
				}
				break;
		}
	}

	if (count($msg_erro["msg"]) > 0) {
		$retorno = array("erro" => $msg_erro);
	} else {
		$nome         = utf8_decode(trim($_POST["nome"]));
		$email        = trim($_POST["email"]);
		$telefone     = trim($_POST["telefone"]);
		$cep          = trim($_POST["cep"]);
		$estado       = $_POST["estado"];
		$cidade       = utf8_decode($_POST["cidade"]);
		$bairro       = utf8_decode(trim($_POST["bairro"]));
		$endereco     = utf8_decode(trim($_POST["endereco"]));
		$numero       = trim($_POST["numero"]);
		$complemento  = utf8_decode(trim($_POST["complemento"]));
		$departamento = utf8_decode($_POST["departamento"]);
		$assunto      = utf8_decode($_POST["assunto"]);
		$familia      = $_POST["familia"];
		$produto      = $_POST["produto"];
		$mensagem     = utf8_decode(trim($_POST["mensagem"]));

		switch (strtolower($departamento)) {
			case "fale conosco":
				$enviar_email = "cortag@cortag.com.br";
				break;

			case "trabalhe conosco":
				$enviar_email = "rh@cortag.com.br";
				break;

			case "assistência técnica":
				$callcenter = true;
				break;
		}

		if (isset($enviar_email)) {
			$assunto_email = "Fale Conosco - Cortag - {$assunto}";

			$mensagem_email = "
				<table style='border-collapse: collapse;' >
					<tbody>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Nome</th>
							<td>{$nome}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >E-mail</th>
							<td>{$email}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Telefone</th>
							<td>{$telefone}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >CEP</th>
							<td>{$cep}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Estado</th>
							<td>{$estado}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Cidade</th>
							<td>{$cidade}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Bairro</th>
							<td>{$bairro}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Endereço</th>
							<td>{$endereco}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Número</th>
							<td>{$numero}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Complemento</th>
							<td>{$complemento}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Departamento</th>
							<td>{$departamento}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Assunto</th>
							<td>{$assunto}</td>
						</tr>
						<tr>
							<th style='background-color: #ED333A; color: #FFFFFF;' >Mensagem</th>
							<td>{$mensagem}</td>
						</tr>
					</tbody>
				</table>
			";

			$headers  = 'From: Fale Conosco - Cortag <helpdesk@telecontrol.com.br>' . "\r\n";
			$headers .= 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

			if ($_serverEnvironment == "development") {
				$admin_email = "rafael.macedo@telecontrol.com.br";
			}

			if (!mail($enviar_email, $assunto_email, $mensagem_email, $headers)) {
				$msg_erro["msg"][] = "Erro ao enviar mensagem";
				$retorno = array("erro" => $msg_erro);
			} else {
				$retorno = array("sucesso" => true);
			}
		} else {
			try {
				pg_query($con, "BEGIN");

				$sql = "INSERT INTO tbl_hd_chamado (
							admin,
							data,
							atendente,
							fabrica_responsavel,
							fabrica,
							titulo,
							status
						) VALUES (
							7203,
							CURRENT_TIMESTAMP,
							7203,
							{$login_fabrica},
							{$login_fabrica},
							'Atemdimento Fale Conosco',
							'Aberto'
						) RETURNING hd_chamado";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao abrir o atendimento");
				}

				$hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

				$cidade = retira_acentos($cidade);

				$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER('{$cidade}') AND UPPER(estado) = UPPER('{$estado}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cidade_id = pg_fetch_result($res, 0, "cidade");
				} else {
					$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER('{$cidade}') AND UPPER(estado) = UPPER('{$estado}')";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
						$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

						$sql = "INSERT INTO tbl_cidade (
									nome, estado
								) VALUES (
									'{$cidade_ibge}', '{$cidade_estado_ibge}'
								) RETURNING cidade";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							throw new Exception("Erro ao abrir o atendimento");
						}

						$cidade_id = pg_fetch_result($res, 0, "cidade");
					}
				}

				$cep = preg_replace("/\D/", "", $cep);

				$sql = "INSERT INTO tbl_hd_chamado_extra (
							hd_chamado,
							nome,
							email,
							fone,
							cep,
							cidade,
							bairro,
							endereco,
							numero,
							complemento,
							produto
						) VALUES (
							{$hd_chamado},
							'{$nome}',
							'{$email}',
							'{$telefone}',
							'{$cep}',
							{$cidade_id},
							'{$bairro}',
							'{$endereco}',
							'{$numero}',
							'{$complemento}',
							{$produto}
						)";

				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception(utf8_encode("Erro ao abrir o atendimento"));
				}

				$sql = "INSERT INTO tbl_hd_chamado_item (
							hd_chamado,
							admin,
							comentario
						) VALUES (
							{$hd_chamado},
							7203,
							'{$assunto} {$mensagem}'
						)";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao abrir o atendimento");
				}

				$headers  = 'From: Fale Conosco - Cortag <helpdesk@telecontrol.com.br>' . "\r\n";
				$headers .= 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

				$mensagem_email = "Foi aberto o atendimento <a href=\"http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado\">$hd_chamado</a> pela página do Fale Conosco Cortag. Por favor, verificar o Chamado.";

				if ($_serverEnvironment == "development") {
					$admin_email = "rafael.macedo@telecontrol.com.br";
				} else {
					$admin_email = "murilo@cortag.com.br";
				}

				mail($admin_email, "Atendimento aberto pelo fale conosco", $mensagem_email, $headers);

				pg_query($con, "COMMIT");

				$retorno = array("sucesso" => true, "hd_chamado" => $hd_chamado);
			} catch (Exception $e) {
				$msg_erro["msg"][] = $e->getMessage();
				$retorno = array("erro" => $msg_erro);
				pg_query($con, "ROLLBACK");
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_GET["ajax_carrega_cidades"]) {
	$estado = strtoupper(trim($_GET["estado"]));

	if (empty($estado)) {
		$retorno = array("erro" => utf8_encode("Estado não informado"));
	} else {
		$sql = "SELECT DISTINCT nome FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' ORDER BY nome ASC";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$retorno = array("erro" => "Erro ao carregar cidades");
		} else {
			$retorno = array("cidades" => array());

			while ($cidade = pg_fetch_object($res)) {
				$retorno["cidades"][] = utf8_encode(strtoupper($cidade->nome));
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_GET["ajax_carrega_produtos"]) {
	$familia = strtoupper(trim($_GET["familia"]));

	if (empty($familia)) {
		$retorno = array("erro" => utf8_encode("Família não informada"));
	} else {
		$sql = "SELECT produto AS id, descricao FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND familia = {$familia} AND ativo IS TRUE";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$retorno = array("erro" => "Erro ao carregar produtos");
		} else {
			$retorno = array("produtos" => array());

			while ($produto = pg_fetch_object($res)) {
				$retorno["produtos"][] = array(
					"id" => $produto->id,
					"descricao" => utf8_encode($produto->descricao)
				);
			}
		}
	}

	exit(json_encode($retorno));
}

?>

<!DOCTYPE html />
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<meta name="language" content="pt-br" />

	<!-- jQuery -->
	<script type="text/javascript" src="plugins/jquery-1.11.3.min.js" ></script>

	<!-- Bootstrap -->
	<script type="text/javascript" src="plugins/bootstrap/js/bootstrap.min.js" ></script>
	<link rel="stylesheet" type="text/css" href="plugins/bootstrap/css/bootstrap.min.css" />

	<!-- Plugins Adicionais -->
	<script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
	<script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
	<script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
	<link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css" />

	<style>

	html {
		font-family: "Open Sans", sans-serif;
		font-size: 14px;
		font-weight: 300;
		line-height: 1.42857;
		color: #3E3E3D;
	}

	div.container {
		max-width: 595px;
	}

	legend {
		font-size: 18px;
		border: medium none;
		margin-bottom: 40px;
	}

	.campo_obrigatorio {
		color: #ED333A;
	}

	label {
		font-weight: 500;
		font-size: 14px;
	}

	input, select, textarea {
		border-radius: 3px;
		font-size: 12px;
		color: #3E3E3D !important;
		height: 44px !important;
		padding: 10px 15px;
		border-color: #E2E0DF !important;
		box-shadow: 0px 0px 0px transparent;
	}

	textarea {
		height: auto !important;
	}

	input:focus, select:focus, textarea:focus {
		border-color: #ED333A !important;
	}

	div.has-error label {
		color: #ED333A !important;
	}

	div.has-error input, div.has-error textarea, div.has-error select, div.has-error div.trigger {
		border-color: #ED333A !important;
	}

	#msg_erro, #msg_sucesso {
		display: none;
	}

	#enviar {
		transition: background-color 0.25s ease-in-out 0s, color 0.25s ease-in-out 0s, border-color 0.25s ease-in-out 0s;
		font-size: 14px;
		background-color: #ECE9E6;
		color: #ED333A;
		font-weight: 700;
		letter-spacing: 1;
		border-radius: 30px;
		border: medium none;
		padding: 18px 30px;
		width: 100%;
	}

	#enviar:hover {
		transition: background-color 0.25s ease-in-out 0s, color 0.25s ease-in-out 0s, border-color 0.25s ease-in-out 0s;
		color: #ECE9E6;
		background-color: #ED333A;
	}

	span.loading {
		color: #ED333A;
		margin-left: 20px;
	}

	.alert {
		border: medium none;
		font-weight: 300;
		padding: 15px 20px;
		border-radius: 3px;
	}

	.alert-danger {
		background-color: #EE6057;
		color: #FFF;
	}

	.alert-success {
		background-color: #B6D334;
		color: #FFF;
	}

	#div_produto, #div_familia {
		display: none;
	}

	div.fancy-select {
		font-size: 12px;
		color: #3E3E3D;
	}

	div.fancy-select select:focus + div.trigger {
		box-shadow: 0px 0px 0px transparent;
	}

	div.fancy-select div.trigger {
		cursor: pointer;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		position: relative;
		background: #FFFFFF;
		border: 1px solid #E2E0DF;
		width: 100%;
		height: 44px !important;
		padding: 10px 15px;
		border-radius: 3px;
		font-size: 12px;
		color: #3E3E3D;
		box-shadow: 0px 0px 0px transparent;

		transition: all 240ms ease-out;
		-webkit-transition: all 240ms ease-out;
		-moz-transition: all 240ms ease-out;
		-ms-transition: all 240ms ease-out;
		-o-transition: all 240ms ease-out;
	}

	div.fancy-select div.trigger::after {
		content: "";
		display: block;
		position: absolute;
		width: 28px;
		height: 12px;
		top: 50%;
		right: 15px;
		margin-top: -5px;
		border: 0px;
		background: #FFF url("imagens_cortag/icon-caret-down.png") no-repeat scroll 0% 0%;
	}

	div.fancy-select div.trigger.open {
		background: #FFFFFF;
		border: 1px solid #ED333A;
		color: #3E3E3D;
		box-shadow: none;
	}

	div.fancy-select div.trigger.open:after {
		border-top-color: #E2E0DF;
	}

	div.fancy-select ul.options {
		list-style: none;
		margin: 0;
		position: absolute;
		top: 40px;
		left: 0;
		visibility: hidden;
		opacity: 0;
		z-index: 50;
		max-height: 200px;
		overflow: auto;
		background: #FFFFFF;
		border-radius: 4px;
		border: 1px solid #E2E0DF;
		box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.4);
		padding-left: 0px;

		transition: opacity 300ms ease-out, top 300ms ease-out, visibility 300ms ease-out;
		-webkit-transition: opacity 300ms ease-out, top 300ms ease-out, visibility 300ms ease-out;
		-moz-transition: opacity 300ms ease-out, top 300ms ease-out, visibility 300ms ease-out;
		-ms-transition: opacity 300ms ease-out, top 300ms ease-out, visibility 300ms ease-out;
		-o-transition: opacity 300ms ease-out, top 300ms ease-out, visibility 300ms ease-out;
	}

	div.fancy-select ul.options li {
		padding: 8px 12px;
		color: #3E3E3D;
		cursor: pointer;
		white-space: nowrap;

		transition: all 150ms ease-out;
		-webkit-transition: all 150ms ease-out;
		-moz-transition: all 150ms ease-out;
		-ms-transition: all 150ms ease-out;
		-o-transition: all 150ms ease-out;
	}

	div.fancy-select ul.options li.selected {
		background: #EE6057;
		color: #FFFFFF;
	}

	div.fancy-select ul.options li.hover {
		color: #EE6057;
	}
	</style>

	<script>

	$(function() {
		$("select").fancySelect();

		$("#telefone").each(function() {
            if ($(this).val().match(/^\(1\d\) 9/i)) {
                $(this).mask("(00) 00000-0000", $(this).val());
            } else {
                $(this).mask("(00) 0000-0000", $(this).val());
            }
        });

        $("#telefone").keypress(function() {
            if ($(this).val().match(/^\(1\d\) 9/i)) {
                $(this).mask("(00) 00000-0000");
            } else {
               $(this).mask("(00) 0000-0000");
            }
        });

        $("#cep").mask("99999-999");

        $("#cep").on("blur", function(){
        	$("#mensagem_erro").removeClass("alert alert-erro");
        	var cep = $(this).val();
        	var method = "webservice";

			if (cep.length > 0) {

				$.ajax({
					async: true,
					url: "../../admin/ajax_cep.php",
					type: "GET",
					data: { cep: cep, method: method },
					error: function(xhr, status, error) {
                        $("#mensagem_erro").addClass("alert alert-erro");
                        $("#mensagem_erro").html("<h4>CEP errado.</h4>");

                    },
					success: function(data) {
						results = data.split(";");

						if (results[0] != "ok") {
							alert(results[0]);
						} else {

							$("#estado option").removeAttr('selected').filter('[value="'+results[4]+'"]').attr('selected', true);
							// $("#estado").val(results[4]);

							carregaCidades(results[4]);

							// $("#cidade").val(results[3]);

							if (results[2].length > 0) {
								$("#bairro").val(results[2]);
							}

							if (results[1].length > 0) {
								$("#endereco").val(results[1]);
							}
						}

						if ($("#bairro").val().length == 0) {
							$("#bairro").focus();

						} else if ($("#endereco").val().length == 0) {
							$("#endereco").focus();

						} else if ($("#numero").val().length == 0) {
							$("#numero").focus();

						}
					}
				});
			}
        });

        $("#numero").numeric();

        $("input, textarea, select").blur(function() {
        	var valor = $.trim($(this).val());

        	if (valor.length > 0) {
        		if ($(this).parents("div.form-group").hasClass("has-error")) {
        			$(this).parents("div.form-group").removeClass("has-error");
        		}
        	}
        });

        $("#estado").on("change.fs", function() {
        	$(this).trigger("change.$");
        });

		$("#estado").change(function() {
			var value = $(this).val();

			if (value.length > 0) {
				carregaCidades(value);
			} else {
				$("#cidade").find("option:first").nextAll().remove();
				$("#cidade").trigger("update");
			}
		});

		$("#departamento").on("change.fs", function() {
        	$(this).trigger("change.$");
        });

		$("#departamento").change(function() {
			var value = $(this).val();

			if (value == "Assistência Técnica") {
				$("#div_produto, #div_familia").show();
			} else {
				$("#div_produto, #div_familia").hide();
			}
		});

		$("#familia").on("change.fs", function() {
        	$(this).trigger("change.$");
        });

		$("#familia").change(function() {
			var value = $(this).val();

			if (value.length > 0) {
				carregaProdutos(value);
			} else {
				$("#produto").find("option:first").nextAll().remove();
				$("#produto").trigger("update");
			}
		});

		$("#enviar").click(function() {
			var btn      = $(this);
			var formData = $("#form_fale_conosco").serializeArray();

			var data = {};

			$("#form_fale_conosco").find("input, textarea, select").each(function() {
				var name  = $(this).attr("name");
				var value = $(this).val();

				data[name] = value;
			});

			data.ajax_enviar = true;

			$.ajax({
				url: "callcenter_cadastra_cortag.php",
				type: "post",
				data: data,
				beforeSend: function() {
					$("div.input.erro").removeClass("erro");
					$("#msg_erro").html("").hide();
					$("#msg_sucesso").hide();
					$(btn).button("loading");
				}
			}).done(function(data) {
				data = JSON.parse(data);

				if (data.erro) {
					var msg_erro = [];

					$.each(data.erro.msg, function(key, value) {
						msg_erro.push(value);
					});

					$("#msg_erro").html("<span style='font-weight: bold;' >Desculpe!</span><br />"+msg_erro.join("<br />"));

					data.erro.campos.forEach(function(input) {
						$("input[name="+input+"], textarea[name="+input+"], select[name="+input+"]").parents("div.form-group").addClass("has-error");
					});

					$("#msg_erro").show();
				} else {
					if (typeof data.hd_chamado != "undefined") {
						$("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.<br />Protocolo: "+data.hd_chamado).show();
					} else {
						$("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.").show();
					}

					$("div.form-group").find("input, textarea, select").val("");
					$("#estado, #cidade, #departamento, #familia, #produto").trigger("update");
					$("#div_familia, #div_produto").hide();
				}

				$(document).scrollTop(0);
				$(btn).button("reset");
			});
		});
	});

	function carregaCidades(estado) {
		var select_cidade = $("#cidade");

		$.ajax({
			url: "callcenter_cadastra_cortag.php",
			type: "get",
			data: { ajax_carrega_cidades: true, estado: estado },
			beforeSend: function() {
				$(select_cidade).find("option:first").nextAll().remove();
				$("#cidade_label").append("<span class='loading' >carregando...</span>")
			}
		}).done(function(data) {
			data = JSON.parse(data);

			if (data.erro) {
				alert(data.erro);
			} else {
				data.cidades.forEach(function(cidade) {
					var option = $("<option></option>", {
						value: cidade,
						text: cidade
					});

					$(select_cidade).append(option);
				});

				$("#cidade_label span.loading").remove();
			}

			$(select_cidade).trigger("update");
		});
	}

	function carregaProdutos(familia) {
		var select_produto = $("#produto");

		$.ajax({
			url: "callcenter_cadastra_cortag.php",
			type: "get",
			data: { ajax_carrega_produtos: true, familia: familia },
			beforeSend: function() {
				$(select_produto).find("option:first").nextAll().remove();
				$("#produto_label").append("<span class='loading' >carregando...</span>")
			}
		}).done(function(data) {
			data = JSON.parse(data);

			if (data.erro) {
				alert(data.erro);
			} else {
				data.produtos.forEach(function(produto) {
					var option = $("<option></option>", {
						value: produto.id,
						text: produto.descricao
					});

					$(select_produto).append(option);
				});

				$("#produto_label span.loading").remove();
			}

			$(select_produto).trigger("update");
		});
	}

	</script>

</head>
<body>

<div class="container" >
	<form id="form_fale_conosco" method="post" >
		<div id="mensagem_erro" style="display:none"></div>
		<legend>Preencha o formulário abaixo para entrar em contato.</legend>

		<div id="msg_erro" class="alert alert-danger" ></div>

		<div id="msg_sucesso" class="alert alert-success" ></div>

		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12 campo_obrigatorio" >
			* Campos obrigatórios
		</div>

		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
			<label for="nome" >Nome<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="nome" name="nome" />
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="email" >E-mail<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="email" name="email" />
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="telefone" >Telefone<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="telefone" class="telefone" name="telefone" />
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="cep" >CEP</label>
			<input type="text" class="form-control" id="cep" name="cep" />
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="estado" >Estado</label>
			<select class="form-control" id="estado" name="estado" >
				<option value="" >Selecione</option>
				<?php
				foreach ($array_estado as $sigla => $nome) {
					echo "<option value='{$sigla}' >{$nome}</option>";
				}
				?>
			</select>
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label id="cidade_label" for="cidade" >Cidade</label>
			<select class="form-control" id="cidade" name="cidade" >
				<option value="" >Selecione</option>
			</select>
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="bairro" >Bairro</label>
			<input type="text" class="form-control" id="bairro" name="bairro" />
		</div>

		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
			<label for="endereco" >Endereço</label>
			<input type="text" class="form-control" id="endereco" name="endereco" />
		</div>

		<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
			<label for="numero" >Número</label>
			<input type="text" class="form-control col-lg-2" id="numero" name="numero" />
		</div>

		<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
			<label for="complemento" >Complemento</label>
			<input type="text" class="form-control" id="complemento" name="complemento" />
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="departamento" >Departamento<span class="campo_obrigatorio">*</span></label>
			<select class="form-control" id="departamento" name="departamento" >
				<option value="" >Selecione</option>
				<option>Assistência Técnica</option>
				<option>Trabalhe Conosco</option>
				<option>Fale Conosco</option>
			</select>
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="assunto" >Assunto<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="assunto" name="assunto" />
		</div>

		<div id="div_familia" class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="familia" >Família<span class="campo_obrigatorio">*</span></label>
			<select class="form-control" id="familia" name="familia" >
				<option value="" >Selecione a Família</option>
				<?php
				$sqlFamilia = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo IS TRUE";
				$resFamilia = pg_query($con, $sqlFamilia);

				while ($familia = pg_fetch_object($resFamilia)) {
					echo "<option value='{$familia->familia}' >{$familia->descricao}</option>";
				}
				?>
			</select>
		</div>

		<div id="div_produto" class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label id="produto_label" for="produto" >Produto<span class="campo_obrigatorio">*</span></label>
			<select class="form-control" id="produto" name="produto" >
				<option value="" >Selecione o Produto</option>
			</select>
		</div>

		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
			<label for="mensagem" >Mensagem<span class="campo_obrigatorio">*</span></label>
			<textarea class="form-control" name="mensagem" rows="6" ></textarea>
		</div>

		<div class="col-xs-12 col-sm-4 col-sm-offset-8 col-md-4 col-md-offset-8 col-lg-4 col-lg-offset-8" >
			<button type="button" id="enviar" class="btn btn-lg" data-loading-text="ENVIANDO..." >ENVIAR</button>
		</div>
	</form>
</div>

<br /><br />

</body>
</html>

