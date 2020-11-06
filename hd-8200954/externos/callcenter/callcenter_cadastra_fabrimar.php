<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';

$login_fabrica = 145;

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
		$endereco = CEP::consulta($cep);

		if (!is_array($endereco)) {
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
			"telefone",
			"cep",
			"estado",
			"cidade",
			"bairro",
			"endereco",
			"numero",
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

					if (in_array($input, array("familia", "produto")) && strtolower($_POST["assunto"]) != "assistência técnica") {
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
		$nome        = utf8_decode(trim($_POST["nome"]));
		$email       = trim($_POST["email"]);
		$telefone    = trim($_POST["telefone"]);
		$cep         = trim($_POST["cep"]);
		$estado      = $_POST["estado"];
		$cidade      = utf8_decode($_POST["cidade"]);
		$bairro      = utf8_decode(trim($_POST["bairro"]));
		$endereco    = utf8_decode(trim($_POST["endereco"]));
		$numero      = trim($_POST["numero"]);
		$complemento = utf8_decode(trim($_POST["complemento"]));
		$profissao   = utf8_decode($_POST["profissao"]);
		$assunto     = utf8_decode($_POST["assunto"]);
		$familia     = $_POST["familia"];
		$produto     = $_POST["produto"];
		$mensagem    = utf8_decode(trim($_POST["mensagem"]));

		switch (strtolower($assunto)) {
			case "comercial":
				$enviar_email = "sav@fabrimar.com.br";
				break;

			case "compras":
				$enviar_email = "compras@fabrimar.com.br";
				break;

			case "exportação":
				$enviar_email = "exportacao@fabrimar.com.br";
				break;

			case "irrigação":
				$enviar_email = "irrigacao@fabrimar.com.br";
				break;

			case "outros":
				$enviar_email = "marketing@fabrimar.com.br";
				break;
			
			case "assistência técnica":
				$callcenter = true;
				break;
		}

		if (isset($enviar_email)) {
			$assunto_email = "Fale Conosco - Fabrimar";

			$mensagem_email = "
				<table style='border-collapse: collapse;' >
					<tbody>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Nome</th>
							<td>{$nome}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >E-mail</th>
							<td>{$email}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Telefone</th>
							<td>{$telefone}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >CEP</th>
							<td>{$cep}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Estado</th>
							<td>{$estado}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Cidade</th>
							<td>{$cidade}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Bairro</th>
							<td>{$bairro}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Endereço</th>
							<td>{$endereco}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Número</th>
							<td>{$numero}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Complemento</th>
							<td>{$complemento}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Profissão</th>
							<td>{$profissao}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Assunto</th>
							<td>{$assunto}</td>
						</tr>
						<tr>
							<th style='background-color: #007D8C; color: #FFFFFF;' >Mensagem</th>
							<td>{$mensagem}</td>
						</tr>
					</tbody>
				</table>
			";

			$headers  = 'From: Fale Conosco - Fabrimar <helpdesk@telecontrol.com.br>' . "\r\n";
			$headers .= 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

			if ($_serverEnvironment == "development") {
				$enviar_email = "flavio.zequin@telecontrol.com.br";
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
							7533,
							CURRENT_TIMESTAMP, 
							7533, 
							145, 
							145, 
							'Atemdimento Fale Conosco',
							'Aberto'
						) RETURNING hd_chamado";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao abrir o atendimento 1");
				}

				$hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

				$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = '{$cidade}' AND UPPER(estado) = UPPER('{$estado}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cidade_id = pg_fetch_result($res, 0, "cidade");
				} else {
					$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = '{$cidade}' AND UPPER(estado) = UPPER('{$estado}')";
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
							throw new Exception("Erro ao abrir o atendimento 2");
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
					throw new Exception(utf8_encode("Erro ao abrir o atendimento 3".pg_last_error()));
				}

				$sql = "INSERT INTO tbl_hd_chamado_item (
							hd_chamado, 
							admin, 
							comentario
						) VALUES (
							{$hd_chamado},
							7533,
							'{$mensagem}'
						)";	
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao abrir o atendimento 4");
				}

				$headers  = 'From: Fale Conosco - Fabrimar <helpdesk@telecontrol.com.br>' . "\r\n";
				$headers .= 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

				$mensagem_email = "Foi aberto o atendimento <a href=\"http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado\">$hd_chamado</a>pela página do Fale Conosco Fabrimar. Por favor, verificar o Chamado.";

				if ($_serverEnvironment == "development") {
					$admin_email = "flavio.zequin@telecontrol.com.br";
				} else {
					$admin_email = "fabrimar@central24horas.com.br";
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

	<script type="text/javascript" src="../../js/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="../../admin/js/jquery.mask.js"></script>
	<script type="text/javascript" src="../../admin/js/jquery.alphanumeric.js"></script>

	<style>

	html {
		font-family: sans-serif;
		font-size: 12px;
		line-height: 1.4;
		color: #737373;
		max-width: 754px;
		padding-left: 10px;
		padding-right: 10px;
	}

	a {
		color: #0D8392;
	}

	div.pagina_titulo > h2 {
		margin: 0px;
		font-weight: normal;
	}

	.campo_obrigatorio {
		font-size: 0.8em;
		color: #ED1C24;
		display: inline-block;
		margin-top: 8px;
	}

	input {
		width: 218px;
		height: 26px;
		font-size: 12px;
		color: #737373;
		font-weight: 400;
	}

	span.select {
		display: inline-block;
		width: 184px;
		height: 26px;
		vertical-align: middle;
	}

	textarea {
		width: 218px;
		height: 74px;
		color: #737373;
		font-size: 12px;
		font-weight: 400;
	}

	div.input input, div.input span.select, div.input textarea {
		background-color: #FFFFFF;
		padding-left: 16px;
		padding-right: 16px;
		border: 1px solid #00CFE6;
		margin-bottom: 7px;
		border-radius: 15px;
		vertical-align: top;
	}

	div.input.erro input, div.input.erro span.select, div.input.erro textarea {
		border-color: #ED1C24;
	}

	#enviar {
		font-size: 18px;
		background-color: #002B30;
		color: #FFFFFF;
		cursor: pointer;
		width: 218px;
		height: 31px;
		border: 0px;
		border-radius: 15px;
	}

	#enviar:hover {
		background-color: #00CFE6;
	}

	span.select {
		cursor: pointer;
	}

	span.select > span.select_option_default, span.select > span.select_option_selected {
		font-size: 12px;
		line-height: 27px;
		display: inline-block;
		float: left;
		color: #737373;
	}

	span.select > span.select_option_default {
		color: #9F9F9F;
	}

	span.select > span.select_option_selected {
		display: none;
	}

	span.select > span.select_seta {
		font-size: 12px;
		line-height: 27px;
		float: right;
		color: #00CFE6;
	}

	span.select > div.select_options {
		display: none;
		max-height: 140px;
		width: 184px;
		overflow: hidden;
		background-color: #FFFFFF;
		padding-left: 16px;
		padding-right: 16px;
		border: 1px solid #00CFE6;
		border-radius: 15px 15px 15px 15px;
		margin-top: 27px;
		margin-left: -17px;
		position: absolute;
	}

	span.select > div.select_options > ul {
		list-style: none;
		width: 100%;
		padding-left: 0px;
		max-height: 130px;
		overflow-y: auto;
		overflow-x: none;
		margin-top: 5px;
		margin-bottom: 0px;
		font-size: 13px;
		line-height: 24px;
	}

	span.select > div.select_options > ul > li:hover {
		color: #00CFE6;
	}

	span.loading {
		color: #00CFE6;	
		font-size: 13px;
		line-height: 24px;
	}

	#msg_erro {
		background-color: #ED1C24;
		color: #FFFFFF;
		text-align: left;
		width: 204px;
		display: none;
		padding: 7px;
		margin-bottom: 10px;
	}

	#msg_sucesso {
		background-color: #00CFE5;
		color: #FFFFFF;
		text-align: left;
		width: 204px;
		display: none;
		padding: 7px;
		margin-bottom: 10px;
	}

	#produto, #familia {
		display: none;
	}

	</style>

	<script>

	$(function() {
		$(".telefone").each(function() {
            if ($(this).val().match(/^\(1\d\) 9/i)) {
                $(this).mask("(00) 00000-0000", $(this).val());
            } else {
                $(this).mask("(00) 0000-0000", $(this).val());
            }
        });

        $(".telefone").keypress(function() {
            if ($(this).val().match(/^\(1\d\) 9/i)) {
                $(this).mask("(00) 00000-0000");
            } else {
               $(this).mask("(00) 0000-0000");
            }
        });

        $(".cep").mask("99999-999");
        $(".numero").numeric();

        $("input, textarea").blur(function() {
        	var valor = $.trim($(this).val());

        	if (valor.length > 0) {
        		if ($(this).parents("div.input").hasClass("erro")) {
        			$(this).parents("div.input").removeClass("erro");
        		}
        	}
        });

		$("span.select").click(function() {
			if ($(this).find("div.select_options").is(":visible")) {
				$(this).find("span.select_seta").html("&darr;");
				$(this).find("div.select_options").hide();
			} else {
				if ($("div.select_options:visible").length > 0) {
					$("div.select_options").parents("span.select").find("span.select_seta").html("&darr;");
					$("div.select_options:visible").hide();
				}

				$(this).find("span.select_seta").html("&uarr;");
				$(this).find("div.select_options").show();
			}
		});

		$(document).on("click", "div.select_options > ul > li", function() {
			var span_select    = $(this).parents("span.select");
			var value          = $(this).data("value");
			var label          = $(this).data("label");
			var select         = $(this).data("select");
			var value_selected = $(span_select).find("input").val()

			if (value != value_selected) {
				$(span_select).find("input").val(value);
				$(span_select).find("span.select_option_default").hide();
				$(span_select).find("span.select_option_selected").text(label).show();
				$(span_select).find("span.select").click();

				if ($(span_select).parents("div.input").hasClass("erro")) {
					$(span_select).parents("div.input").removeClass("erro");
				}

				if (select == "assunto") {
					var callcenter = $(this).data("callcenter");

					if (callcenter == true) {
						$("#familia, #produto").show();
					} else {
						$("#familia, #produto").hide();
					}
				}

				if (select == "estado") {
					carregaCidades(value);
				}

				if (select == "familia") {
					carregaProdutos(value);
				}
			}
		});

		$("#enviar").click(function() {
			var btn      = $(this);
			var formData = $("#form_fale_conosco").serializeArray();

			var data = {};

			$("#form_fale_conosco").find("input, textarea").each(function() {
				var name  = $(this).attr("name");
				var value = $(this).val();

				data[name] = value;
			});

			data.ajax_enviar = true;

			$.ajax({
				url: "callcenter_cadastra_fabrimar.php",
				type: "post",
				data: data,
				beforeSend: function() {
					$("div.input.erro").removeClass("erro");
					$("#msg_erro").html("").hide();
					$("#msg_sucesso").hide();
					$(btn).text("Enviando...");
					$(btn)[0].disabled = true;
					$(btn).css({ "background-color": "#00CFE6" });
				}
			}).done(function(data) {
				data = JSON.parse(data);

				if (data.erro) {
					var msg_erro = [];

					$.each(data.erro.msg, function(key, value) {
						msg_erro.push(value);
					});

					$("#msg_erro").html("<h4>NÃO FOI POSSÍVEL ENVIAR</h4>"+msg_erro.join("<br />"));

					data.erro.campos.forEach(function(input) {
						$("input[name="+input+"], textarea[name="+input+"]").parents("div.input").addClass("erro");
					});

					$("#msg_erro").show();
				} else {
					if (typeof data.hd_chamado != "undefined") {
						$("#msg_sucesso").html("<h4>MENSAGEM ENVIADA COM SUCESSO</h4>Entraremos em contato em breve<br />Protocolo: "+data.hd_chamado).show();
					} else {
						$("#msg_sucesso").html("<h4>MENSAGEM ENVIADA COM SUCESSO</h4>Entraremos em contato em breve").show();
					}

					$("div.input").find("input, textarea").val("");
					$("div.input > span.select > span.select_option_selected").text("").hide();
					$("div.input > span.select > span.select_option_default").show();
					$("div.input > span.select > span.select_options > ul > li").remove();
				}

				$(document).scrollTop(240);
				$(btn).text("Enviar");
				$(btn)[0].disabled = false;
				$(btn).css({ "background-color": "#002B30" });
			});
		});
	});

	function carregaCidades(estado) {
		var select_cidade = $("#cidade");
		var ul            = $(select_cidade).find("ul");

		$.ajax({
			url: "callcenter_cadastra_fabrimar.php",
			type: "get",
			data: { ajax_carrega_cidades: true, estado: estado },
			beforeSend: function() {
				$(ul).find("li").remove();
				$(select_cidade).append("<span class='loading' >carregando...</span>")
			},
			complete: function(data) {
				data = JSON.parse(data.responseText);

				if (data.erro) {
					alert(data.erro);
				} else {
					$(select_cidade).find("input").val("");
					$(select_cidade).find("span.select_option_selected").text("").hide();
					$(select_cidade).find("span.select_option_default").show();

					data.cidades.forEach(function(cidade) {
						var li = $("<li></li>");
						$(li).text(cidade).data("value", cidade).data("label", cidade);
						$(ul).append(li);
					});

					$(ul).scrollTop(0);
					$(select_cidade).find("span.loading").remove();
				}
			}
		});
	}

	function carregaProdutos(familia) {
		var select_produto = $("#produto");
		var ul             = $(select_produto).find("ul");

		$.ajax({
			url: "callcenter_cadastra_fabrimar.php",
			type: "get",
			data: { ajax_carrega_produtos: true, familia: familia },
			beforeSend: function() {
				$(ul).find("li").remove();
				$(select_produto).append("<span class='loading' >carregando...</span>")
			},
			complete: function(data) {
				data = JSON.parse(data.responseText);

				if (data.erro) {
					alert(data.erro);
				} else {
					$(select_produto).find("input").val("");
					$(select_produto).find("span.select_option_selected").text("").hide();
					$(select_produto).find("span.select_option_default").show();

					data.produtos.forEach(function(produto) {
						var li = $("<li></li>");
						$(li).text(produto.descricao).data("value", produto.id).data("label", produto.descricao);
						$(ul).append(li);
					});

					$(ul).scrollTop(0);
					$(select_produto).find("span.loading").remove();
				}
			}
		});
	}

	</script>

</head>
<body>

	<form id="form_fale_conosco" method="post" >

		<p>
		<h3>BEM VINDO AO FALE CONOSCO FABRIMAR</h3>
		Deseja comprar um produto Fabrimar? Acesse <a href="http://www.fabrimar.com.br/onde_comprar">Onde Comprar</a> e consulte a loja mais próxima.<br />
		<br />
		Se você é revendedor e deseja comprar diretamente da Fabrimar, faça contato com o <a href="http://www.fabrimar.com.br/servicos/escritorios_de_venda">escritório de vendas</a> que atende a sua região ou com:<br />
		<strong>Serviço de Apoio a Vendas</strong><br />
		(0800) 7032237 - ligação gratuita. Atendimento: de 2ª a 6ª feira, das 7h às 16h48min.<br />
		<a href="mailto:sav@fabrimar.com.br">sav@fabrimar.com.br</a><br />
		<strong>Serviço de Atendimento ao Consumidor</strong><br />
		(0800) 022 1362 - ligação gratuita de 2ª a 6ª das 08h às 18h.<br />
		<strong>Serviço de Exportação</strong><br />
		<a href="mailtto:exportacao@fabrimar.com.br">exportacao@fabrimar.com.br</a><br />
		</p>

		<div>
			<span style="width: 48%; display: inline-block; vertical-align: top;" >
				<h4>MANDE SUA MENSAGEM</h4>
				Ainda não encontrou o que precisa? Mande um e-mail para <a href="mailto:faleconosco@fabrimar.com.br">faleconosco@fabrimar.com.br</a> ou preencha o formulário.<br />
				Para agilizar o atendimento, preencha o campo TELEFONE.<br />
				Caso solicite o nosso catálogo, não deixe de preencher o campo ENDEREÇO.<br />
			</span>
			<span style="width: 48%; display: inline-block;" >
				<br />

				<div id="msg_erro" ></div>
				<div id="msg_sucesso" >
					<h4>MENSAGEM ENVIADA COM SUCESSO</h4>
					Entraremos em contato em breve
				</div>

				<div class="campo_obrigatorio" >
					* Campos obrigatórios
				</div>

				<br /><br />

				<div class="input" >
					<input type="text" name="nome" placeholder="Nome" />
					<span class="campo_obrigatorio">*</span>
				</div>

				<div class="input" >
					<input type="text" name="email" placeholder="E-mail" />
				</div>

				<div class="input" >
					<input class="telefone" type="text" name="telefone" placeholder="Telefone" />
					<span class="campo_obrigatorio">*</span>
				</div>

				<div class="input" >
					<input class="cep" type="text" name="cep" placeholder="CEP" />
					<span class="campo_obrigatorio">*</span>
				</div>

				<div class="input" >
					<span class="select">
						<input type="hidden" name="estado" />
						<span class="select_option_default" >Estado</span>
						<span class="select_option_selected" ></span>
						<span class="select_seta" >&darr;</span>
						<div class="select_options" >
							<ul>
								<?php
								foreach ($array_estado as $sigla => $nome) {
									echo "<li data-value='{$sigla}' data-label='{$nome}' data-select='estado' >{$nome}</li>";
								}
								?>
							</ul>
						</div>
					</span>
					<span class="campo_obrigatorio">*</span>
				</div>

				<div id="cidade" class="input" >
					<span class="select" >
						<input type="hidden" name="cidade" />
						<span class="select_option_default" >Cidade</span>
						<span class="select_option_selected" ></span>
						<span class="select_seta" >&darr;</span>
						<div class="select_options" >
							<ul>
							</ul>
						</div>
					</span>
					<span class="campo_obrigatorio">*</span>
				</div>

				<div class="input" >
					<input type="text" name="bairro" placeholder="Bairro" />
					<span class="campo_obrigatorio">*</span>
				</div>

				<div class="input" >
					<input type="text" name="endereco" placeholder="Endereço" />
					<span class="campo_obrigatorio">*</span>
				</div>

				<div class="input" >
					<input class="numero" type="text" name="numero" placeholder="Número" />
					<span class="campo_obrigatorio">*</span>
				</div>

				<div class="input" >
					<input type="text" name="complemento" placeholder="Complemento" />
				</div>

				<div class="input" >
					<span class="select" >
						<input type="hidden" name="profissao" />
						<span class="select_option_default" >Profissão</span>
						<span class="select_option_selected" ></span>
						<span class="select_seta" >&darr;</span>
						<div class="select_options" >
							<ul>
								<?php
								$array_profissao = array("Arquiteto", "Consumidor", "Construtor", "Comprador", "Designer", "Engenheiro", "Estudante", "Instalador Hidráulico", "Revendedor", "Síndico", "Outros");

								foreach ($array_profissao as $profissao) {
									echo "<li data-value='{$profissao}' data-label='{$profissao}' >{$profissao}</li>";
								}
								?>
							</ul>
						</div>
					</span>
				</div>

				<div class="input" >
					<span class="select" >
						<input type="hidden" name="assunto" />
						<span class="select_option_default" >Assunto</span>
						<span class="select_option_selected" ></span>
						<span class="select_seta" >&darr;</span>
						<div class="select_options" >
							<ul>
								<?php
								$array_assunto = array(
									array("nome" => "Assistência Técnica", "callcenter" => true),
									array("nome" => "Comercial", "callcenter" => false),
									array("nome" => "Compras", "callcenter" => false),
									array("nome" => "Exportação", "callcenter" => false),
									array("nome" => "Irrigação", "callcenter" => false),
									array("nome" => "Outros", "callcenter" => false)
								);

								foreach ($array_assunto as $assunto) {
									echo "<li data-value='{$assunto['nome']}' data-label='{$assunto['nome']}' data-select='assunto' data-callcenter='{$assunto['callcenter']}' >{$assunto['nome']}</li>";
								}
								?>
							</ul>
						</div>
					</span>
					<span class="campo_obrigatorio">*</span>
				</div>

				<div id="familia" class="input" >
					<span class="select" >
						<input type="hidden" name="familia" />
						<span class="select_option_default" >Família</span>
						<span class="select_option_selected" ></span>
						<span class="select_seta" >&darr;</span>
						<div class="select_options" >
							<ul>
								<?php
								$sqlFamilia = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo IS TRUE";
								$resFamilia = pg_query($con, $sqlFamilia);

								while ($familia = pg_fetch_object($resFamilia)) {
									echo "<li data-value='{$familia->familia}' data-label='{$familia->descricao}' data-select='familia' >{$familia->descricao}</li>";
								}
								?>
							</ul>
						</div>
					</span>
					<span class="campo_obrigatorio">*</span>
				</div>

				<div id="produto" class="input" >
					<span class="select" >
						<input type="hidden" name="produto" />
						<span class="select_option_default" >Produto</span>
						<span class="select_option_selected" ></span>
						<span class="select_seta" >&darr;</span>
						<div class="select_options" >
							<ul>
							</ul>
						</div>
					</span>
					<span class="campo_obrigatorio">*</span>
				</div>

				<div class="input" >
					<textarea name="mensagem" placeholder="Mensagem" ></textarea>
					<span class="campo_obrigatorio">*</span>
				</div>

				<button type="button" id="enviar" >ENVIAR</button>
			</span>
		</div>

		<br /><br />

	</form>
		
</body>
</html>

