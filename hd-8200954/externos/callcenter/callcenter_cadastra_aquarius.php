<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';

$login_fabrica = 174;

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

function checaCPF(){
    global $_POST, $con, $login_fabrica;	// Para conectar com o banco...

    $cpf_cnpj = $_POST['cpf_cnpj'];
    $cpf_cnpj = preg_replace("/\D/","",$cpf_cnpj);   // Limpa o CPF
	if (!$cpf_cnpj or $cpf_cnpj == '' or (strlen($cpf_cnpj) != 11 and strlen($cpf_cnpj) != 14)) return false;

	if(strlen($cpf_cnpj) > 0){
		$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf_cnpj')");
		if ($res_cpf === false) {
			$cpf_erro = pg_last_error($con);
			if ($use_savepoint) $n = @pg_query($con,"ROLLBACK TO SAVEPOINT checa_CPF");
			throw new Exception("CPF informado inválido");
		}
	}
}

if ($_POST["ajax_enviar"]) {

	$regras = array(
		"notEmpty" => array(
			"nome",
			"cpf_cnpj",
			"telefone",
			"celular",
			"cep",
			"estado",
			"cidade",
			"bairro",
			"endereco",
			"numero",
			"hd_classificacao",
			"mensagem"
		),
		"validaCep" => "cep",
		"validaEstado" => "estado",
		"validaCidade" => "cidade",
		"checaCPF" 	   => "cpf_cnpj"
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
		$nome         		= utf8_decode(trim($_POST["nome"]));
		$cpf_cnpj        	= trim($_POST["cpf_cnpj"]);
		$telefone     		= trim($_POST["telefone"]);
		$celular     		= trim($_POST["celular"]);
		$cep          		= trim($_POST["cep"]);
		$estado       		= trim($_POST["estado"]);
		$cidade       		= utf8_decode($_POST["cidade"]);
		$bairro       		= utf8_decode(trim($_POST["bairro"]));
		$endereco     		= utf8_decode(trim($_POST["endereco"]));
		$numero       		= trim($_POST["numero"]);
		$hd_classificacao 	= utf8_decode($_POST["hd_classificacao"]);
		$mensagem     		= utf8_decode(trim($_POST["mensagem"]));

		$sql = "SELECT email, admin FROM tbl_admin WHERE atendente_callcenter = 't' AND fabrica = $login_fabrica AND ativo IS TRUE";
		$res = pg_query($con, $sql);

		for($i = 0; $i < pg_num_rows($res); $i++){
			$admin_fale_conosco 				= pg_fetch_result($res, $i, 'admin');
			$emails_admins[$admin_fale_conosco] = pg_fetch_result($res, $i, 'email');
		}

		$sqlOrigem = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = $login_fabrica AND descricao = 'Fale Conosco'";
		$resOrigem = pg_query($con, $sqlOrigem);
		$resOrigem = pg_fetch_assoc($resOrigem);

		$admin_fale_conosco = array_rand($emails_admins, 1);

		if (pg_num_rows($res) > 0){
			try {
				pg_query($con, "BEGIN");

				$sql = "INSERT INTO tbl_hd_chamado (
							admin,
							data,
							atendente,
							fabrica_responsavel,
							fabrica,
							titulo,
							status,
							hd_classificacao,
							categoria
						) VALUES (
							{$admin_fale_conosco},
							CURRENT_TIMESTAMP,
							{$admin_fale_conosco},
							{$login_fabrica},
							{$login_fabrica},
							'Fale Conosco',
							'Aberto',
							{$hd_classificacao},
							'reclamacao_produto'
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

				$cep 		= preg_replace("/\D/", "", $cep);
				$cpf_cnpj 	= preg_replace("/\D/", "", $cpf_cnpj);

				$sql = "INSERT INTO tbl_hd_chamado_extra (
							hd_chamado,
							nome,
							fone,
							fone2,
							cep,
							cpf,
							cidade,
							bairro,
							endereco,
							numero,
							origem,
							hd_chamado_origem
						) VALUES (
							{$hd_chamado},
							'{$nome}',
							'{$telefone}',
							'{$celular}',
							'{$cep}',
							'{$cpf_cnpj}',
							{$cidade_id},
							'{$bairro}',
							'{$endereco}',
							'{$numero}',
							'Fale Conosco',
							{$resOrigem['hd_chamado_origem']}
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
							{$admin_fale_conosco},
							'{$mensagem}'
						)";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao abrir o atendimento");
				}

				$headers  = 'From: Fale Conosco - Aquarius <helpdesk@telecontrol.com.br>' . "\r\n";
				$headers .= 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

				if ($_serverEnvironment == "development") {
					$admin_email = "guilherme.monteiro@telecontrol.com.br";
					$mensagem_email = "Foi aberto o atendimento <a href=\"http://novodevel.telecontrol.com.br/~monteiro/Posvenda/admin/callcenter_interativo_new.php?callcenter=$hd_chamado\">$hd_chamado</a> pela página do Fale Conosco Aquarius. Por favor, verificar o Chamado.";
				} else {
					$admin_email = $emails_admins[$admin_fale_conosco];
					$mensagem_email = "Foi aberto o atendimento <a href=\"http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado\">$hd_chamado</a> pela página do Fale Conosco Aquarius. Por favor, verificar o Chamado.";
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
		font-weight: 400;
		font-size: 14px;
		color: #4b4d4d;
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
		border-color: #58b847 !important;
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
		background-color: #58b847;
		color: #ffffff;
		font-weight: 700;
		letter-spacing: 1;
		border-radius: 5px;
		border: medium none;
		padding: 18px 30px;
		width: 100%;
	}

	#enviar:hover {
		transition: background-color 0.25s ease-in-out 0s, color 0.25s ease-in-out 0s, border-color 0.25s ease-in-out 0s;
		color: #ffffff;
		background-color: #47D02F;
	}

	span.loading {
		color: #58b847;
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
		background-color: #58b847 !important;
		color: #FFF;
	}

	#div_produto, #div_familia {
		display: none;
	}

	div.fancy-select {
		font-size: 12px;
		color: #3E3E3D;
		font-weight: 400;
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
		background: #FFF url("imagens_aquarius/icon-caret-down-aquarius.png") no-repeat scroll 0% 0%;
	}

	div.fancy-select div.trigger.open {
		background: #FFFFFF;
		border: 1px solid #58b847;
		color: #58b847;
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
		/*background: #EE6057;*/
		background: #58b847 !important;
		color: #FFFFFF;
	}

	div.fancy-select ul.options li.hover {
		color: #58b847 !important;
	}
	</style>

	<script>

	$(function() {

		$("#cpf_cnpj").mask("999.999.999-99");

		$("select").fancySelect();

		$(".telefone").each(function() {
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

        $("#celular").each(function() {
            if ($(this).val().match(/^\(1\d\) 9/i)) {
                $(this).mask("(00) 00000-0000", $(this).val());
            } else {
                $(this).mask("(00) 0000-0000", $(this).val());
            }
        });

        var phoneMask = function(){
			if($(this).val().match(/^\(0/)){
    			$(this).val('(');
    			return;
        	}
    		if($(this).val().match(/^\([1-9][0-9]\) *[0-8]/)){
    			$(this).mask('(00) 0000-0000');
    		}else{
				$(this).mask('(00) 00000-0000');
        	}
    		$(this).keyup(phoneMask);
    	};

    	$('#celular').keyup(phoneMask);

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
							var indexEstado = $("#estado option").removeAttr('selected').filter('[value="'+results[4]+'"]').index();
							$("#estado option").removeAttr('selected').filter('[value="'+results[4]+'"]').attr('selected', true);
							$('#estado option:eq('+indexEstado+')').prop('selected', true).trigger('change');

							carregaCidades(results[4],results[3]);

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
				url: "callcenter_cadastra_aquarius.php",
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
					$("#estado, #cidade, #hd_classificacao").trigger("update");
				}

				$(document).scrollTop(0);
				$(btn).button("reset");
			});
		});

	});

	function carregaCidades(estado,cidade) {
		var select_cidade = $("#cidade");

		$.ajax({
			url: "callcenter_cadastra_aquarius.php",
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
				if(cidade != undefined){
					var indexCidade = $("#cidade option").removeAttr('selected').filter('[value="'+cidade+'"]').index();
					$('#cidade option:eq('+indexCidade+')').prop('selected', true).trigger('change');
				}
				$("#cidade_label span.loading").remove();
			}

			$(select_cidade).trigger("update");
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
			<label for="email" >CPF/CNPJ<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" />
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="telefone" >Telefone<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="telefone" class="telefone" name="telefone" />
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="telefone" >Celular<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="celular" class="celular" name="celular" />
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="cep" >CEP<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="cep" name="cep" />
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="estado" >Estado<span class="campo_obrigatorio">*</span></label>
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
			<label id="cidade_label" for="cidade" >Cidade<span class="campo_obrigatorio">*</span></label>
			<select class="form-control" id="cidade" name="cidade" >
				<option value="" >Selecione</option>
			</select>
		</div>

		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="bairro" >Bairro<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="bairro" name="bairro" />
		</div>

		<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
			<label for="endereco" >Endereço<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="endereco" name="endereco" />
		</div>

		<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
			<label for="numero" >Número<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control col-lg-2" id="numero" name="numero" />
		</div>
		<!--
		<div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-6" >
			<label for="complemento" >Complemento</label>
			<input type="text" class="form-control" id="complemento" name="complemento" />
		</div>
		-->
		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="hd_classificacao">Classificação<span class="campo_obrigatorio">*</span></label>
				<select name='hd_classificacao' id='hd_classificacao'>
					<option value=''>Escolha</option><?php

					$sqlClassificacao = "SELECT hd_classificacao, descricao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
					$resClassificacao = pg_query($con,$sqlClassificacao);

					for ($i = 0; $i < pg_num_rows($resClassificacao); $i++) {

						$hd_classificacao_aux = pg_fetch_result($resClassificacao,$i,'hd_classificacao');
						$classificacao    = pg_fetch_result($resClassificacao,$i,'descricao');

						echo " <option value='".$hd_classificacao_aux."' ".($hd_classificacao_aux == $hd_classificacao ? "selected='selected'" : '').">$classificacao</option>";

					}?>
			</select>
		</div>

		<!--
		<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
			<label for="assunto" >Assunto<span class="campo_obrigatorio">*</span></label>
			<input type="text" class="form-control" id="assunto" name="assunto" />
		</div>
		-->

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

