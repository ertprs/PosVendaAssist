<!DOCTYPE html />

<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if($_GET['admin']){
	include "admin/autentica_admin.php";
}else{
	include "autentica_usuario.php";
}

echo "<script type='text/javascript' src='inc_soMAYSsemAcento.js'></script>";
if ($_GET["os"]) {
	$os = $_GET["os"];

	$sql = "SELECT
				tbl_posto.nome AS posto_nome,
				tbl_posto_fabrica.contato_fone_comercial AS posto_fone,
				tbl_posto.fax AS posto_fax,
				tbl_posto.cnpj AS posto_cnpj,
				tbl_posto.ie AS posto_ie,
				tbl_posto.email AS posto_email,
				tbl_os.consumidor_nome,
				tbl_os.consumidor_endereco,
				tbl_os.consumidor_numero,
				tbl_os.consumidor_complemento,
				tbl_os.consumidor_cidade,
				tbl_os.consumidor_bairro,
				tbl_os.consumidor_estado,
				tbl_os.consumidor_fone,
				tbl_os.consumidor_email,
				tbl_produto.referencia AS equipamento_modelo,
				tbl_produto.voltagem AS equipamento_tensao,
				tbl_os.serie AS equipamento_serie,
				tbl_os.data_nf AS equipamento_data_venda
			FROM tbl_os
			JOIN tbl_posto USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto USING(produto)
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.os = $os";
	$res = pg_query($con, $sql);

	$posto_nome             = pg_fetch_result($res, 0, "posto_nome");
	$posto_fone             = pg_fetch_result($res, 0, "posto_fone");
	$posto_fax              = pg_fetch_result($res, 0, "posto_fax");
	$posto_cnpj             = pg_fetch_result($res, 0, "posto_cnpj");
	$posto_ie               = pg_fetch_result($res, 0, "posto_ie");
	$posto_email            = pg_fetch_result($res, 0, "posto_email");
	$cliente_nome           = pg_fetch_result($res, 0, "consumidor_nome");
	$cliente_endereco       = pg_fetch_result($res, 0, "consumidor_endereco");
	$cliente_numero         = pg_fetch_result($res, 0, "consumidor_numero");
	$cliente_complemento    = pg_fetch_result($res, 0, "consumidor_complemento");
	$cliente_cidade         = pg_fetch_result($res, 0, "consumidor_cidade");
	$cliente_bairro         = pg_fetch_result($res, 0, "consumidor_bairro");
	$cliente_estado         = pg_fetch_result($res, 0, "consumidor_estado");
	$cliente_fone           = pg_fetch_result($res, 0, "consumidor_fone");
	$cliente_email          = pg_fetch_result($res, 0, "consumidor_email");
	$equipamento_modelo     = pg_fetch_result($res, 0, "equipamento_modelo");
	$equipamento_tensao     = pg_fetch_result($res, 0, "equipamento_tensao");
	$equipamento_serie      = pg_fetch_result($res, 0, "equipamento_serie");
	$equipamento_data_venda = pg_fetch_result($res, 0, "equipamento_data_venda");
	list($a, $m, $d) = explode("-", $equipamento_data_venda);

	$checklist = array(
		"empresa" => array(
			"nome"  => $posto_nome,
			"fone"  => $posto_fone,
			"fax"   => $posto_fax,
			"cnpj"  => $posto_cnpj,
			"ie"    => $posto_ie,
			"email" => $posto_email,
		),
		"cliente" => array(
			"nome"     => $cliente_nome,
			"endereco" => $cliente_endereco." ".$cliente_numero." ".$cliente_com,
			"cidade"   => $cliente_cidade,
			"bairro"   => $cliente_bairro,
			"estado"   => $cliente_estado,
			"fone"     => $cliente_fone,
			"email"    => $cliente_email,
		),
		"equipamento" => array(
			"modelo"     => $equipamento_modelo,
			"tensao"     => $equipamento_tensao,
			"serie"      => $equipamento_serie,
			"data_venda" => "$d/$m/$a",
		),
	);

	$readonly = array(
		"empresa" => array(
			"nome"  => ((strlen($posto_nome)) ? "readonly" : ""),
			"fone"  => ((strlen($posto_fone)) ? "readonly" : ""),
			"fax"   => ((strlen($posto_fax)) ? "readonly" : ""),
			"cnpj"  => ((strlen($posto_cnpj)) ? "readonly" : ""),
			"ie"    => ((strlen($posto_ie)) ? "readonly" : ""),
			"email" => ((strlen($posto_email)) ? "readonly" : ""),
		),
		"cliente" => array(
			"nome"     => ((strlen($cliente_nome)) ? "readonly" : ""),
			"endereco" => ((strlen($cliente_endereco)) ? "readonly" : ""),
			"cidade"   => ((strlen($cliente_cidade)) ? "readonly" : ""),
			"bairro"   => ((strlen($cliente_bairro)) ? "readonly" : ""),
			"estado"   => ((strlen($cliente_estado)) ? "readonly" : ""),
			"fone"     => ((strlen($cliente_fone)) ? "readonly" : ""),
			"email"    => ((strlen($cliente_email)) ? "readonly" : ""),
		),
		"equipamento" => array(
			"modelo"     => ((strlen($equipamento_modelo)) ? "readonly" : ""),
			"tensao"     => ((strlen($equipamento_tensao)) ? "readonly" : ""),
			"serie"      => ((strlen($equipamento_serie)) ? "readonly" : ""),
			"data_venda" => ((strlen($equipamento_data_venda)) ? "readonly" : ""),
		),
	);
}

if ($_POST["gravar"]) {
	$checklist["empresa"]["nome"]    = $_POST["empresa_nome"];
	$checklist["empresa"]["fone"]    = $_POST["empresa_fone"];
	$checklist["empresa"]["fax"]     = $_POST["empresa_fax"];
	$checklist["empresa"]["cnpj"]    = $_POST["empresa_cnpj"];
	$checklist["empresa"]["ie"]      = $_POST["empresa_ie"];
	$checklist["empresa"]["tecnico"] = $_POST["empresa_tecnico"];
	$checklist["empresa"]["email"]   = $_POST["empresa_email"];

	$checklist["cliente"]["nome"]             = $_POST["cliente_nome"];
	$checklist["cliente"]["endereco"]         = $_POST["cliente_endereco"];
	$checklist["cliente"]["contato"]          = $_POST["cliente_contato"];
	$checklist["cliente"]["cidade"]           = $_POST["cliente_cidade"];
	$checklist["cliente"]["bairro"]           = $_POST["cliente_bairro"];
	$checklist["cliente"]["estado"]           = $_POST["cliente_estado"];
	$checklist["cliente"]["fone"]             = $_POST["cliente_fone"];
	$checklist["cliente"]["local_instalacao"] = $_POST["cliente_local_instalacao"];
	$checklist["cliente"]["email"]            = $_POST["cliente_email"];
	$checklist["cliente"]["responsavel"]      = $_POST["cliente_responsavel"];

	$checklist["equipamento"]["modelo"]           = $_POST["equipamento_modelo"];
	$checklist["equipamento"]["potencia"]         = $_POST["equipamento_potencia"];
	$checklist["equipamento"]["tensao"]           = $_POST["equipamento_tensao"];
	$checklist["equipamento"]["serie"]            = $_POST["equipamento_serie"];
	$checklist["equipamento"]["serie_alternador"] = $_POST["equipamento_serie_alternador"];
	$checklist["equipamento"]["serie_motor"]      = $_POST["equipamento_serie_motor"];
	$checklist["equipamento"]["vendido_por"]      = $_POST["equipamento_vendido_por"];
	$checklist["equipamento"]["data_venda"]       = $_POST["equipamento_data_venda"];

	$checklist["verifica_funcionamento"]["reaperto_parafuso"]         = $_POST["reaperto_parafuso"];
	$checklist["verifica_funcionamento"]["verificacao_escape"]        = $_POST["verificacao_escape"];
	$checklist["verifica_funcionamento"]["verificacao_circulacao_ar"] = $_POST["verificacao_circulacao_ar"];
	$checklist["verifica_funcionamento"]["verificacao_niveis"]        = $_POST["verificacao_niveis"];
	if ($checklist["verifica_funcionamento"]["verificacao_niveis"] == "sim") {
		$checklist["verifica_funcionamento"]["verificacao_niveis_sub"]    = $_POST["verificacao_niveis_sub"];
	}
	$checklist["verifica_funcionamento"]["verificacao_bateria"]       = $_POST["verificacao_bateria"];

	$checklist["verifica_conexao"]["verificacao_cabo_potencia"] = $_POST["verificacao_cabo_potencia"];
	$checklist["verifica_conexao"]["verificacao_cabo_comando"]  = $_POST["verificacao_cabo_comando"];
	$checklist["verifica_conexao"]["funcionamento_motor"]       = $_POST["funcionamento_motor"];
	if ($checklist["verifica_conexao"]["funcionamento_motor"] == "sim") {
		$checklist["verifica_conexao"]["funcionamento_motor_sub"]    = $_POST["funcionamento_motor_sub"];
	}
	$checklist["verifica_conexao"]["testes_protecoes"]          = $_POST["testes_protecoes"];
	$checklist["verifica_conexao"]["inspecao_instalacao"]       = $_POST["inspecao_instalacao"];
	$checklist["verifica_conexao"]["teste_funcao_manual"]       = $_POST["teste_funcao_manual"];
	$checklist["verifica_conexao"]["teste_funcao_automatica"]   = $_POST["teste_funcao_automatica"];
	$checklist["verifica_conexao"]["teste_operacao"]            = $_POST["teste_operacao"];

	$checklist["instrucao"]["documentacao"]             = $_POST["documentacao"];
	$checklist["instrucao"]["verificacao_conformidade"] = $_POST["verificacao_conformidade"];
	if ($checklist["instrucao"]["documentacao"] == "sim") {
		$checklist["instrucao"]["documentacao_sub"]    = $_POST["documentacao_sub"];
	}

	$checklist["consideracoes_finais"]["existe_pendencia"]                  = $_POST["existe_pendencia"];
	$checklist["consideracoes_finais"]["impedem_funcionamento_equipamento"] = $_POST["impedem_funcionamento_equipamento"];
	$checklist["consideracoes_finais"]["observacao"]                        = $_POST["observacao"];

	echo "<pre>";
	foreach ($checklist as $categoria => $itens) {
		foreach ($itens as $key => $value) {
			if ($key == "observacao") {
				continue;
			}

			if (is_array($value)) {
				if (!count($value)) {
					$erro = "Preencha os campos em vermelho para prosseguir!";
					$style[$categoria][$key] = "color: #E00;";
				}
			} else {
				if (!strlen($value)) {
					$erro = "Preencha os campos em vermelho para prosseguir!";
					$style[$categoria][$key] = "color: #E00;";
				}
			}
		}
	}

	if (empty($erro)) {

		$json = json_encode(($checklist));

		echo $json;
		$sql = "INSERT INTO tbl_laudo_tecnico_os (os, observacao, fabrica, titulo) VALUES ({$_GET['os']}, '$json', $login_fabrica, 'Check List')";
		$res = pg_query($con, $sql);

		if (pg_last_error()) {
			$erro = "Ocorreu um erro ao gravar o checklist!";
		} else {
			header("Location: os_item_new.php?os={$_GET['os']}");
		}
	}
}

if ($_GET["imprimir"]) {
	$os = $_GET["imprimir"];

	$sql = "SELECT observacao FROM tbl_laudo_tecnico_os WHERE fabrica = $login_fabrica AND os = $os";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {
		$json = utf8_decode(pg_fetch_result($res, 0, "observacao"));
		$checklist = json_decode($json, true);
	} else {
		//echo "<script>alert('Nenhum check list encontrado para a os $os'); window.close();</script>";
		echo "<script>alert('Nenhum check list encontrado para a os $os');</script>";
	}
}
?>

<html>
	<head>
		<title>Check List <?=$login_fabrica_nome?></title>

		<script src="js/jquery-1.6.2.js"></script>
		<script>
			$(function () {
				$("input,textarea").each(function(){
					if($(this).attr('name') !='cliente_email' && $(this).attr('name') != 'empresa_email'){
					$(this).attr('onkeyup','somenteMaiusculaSemAcento(this)');
					}
				});

				$("input[name=verificacao_niveis]").change(function () {
					if ($(this).val() == "sim") {
						$("input[name^=verificacao_niveis_sub]").each(function () {
							$(this).removeAttr("disabled").next("label").css("color", "#E00");
						});
					} else {
						$("input[name^=verificacao_niveis_sub]").each(function () {
							$(this).attr("disabled", "disabled").removeAttr("checked").next("label").css("color", "#281F20");
						});
					}
				});

				$("input[name=funcionamento_motor]").change(function () {
					if ($(this).val() == "sim") {
						$("input[name^=funcionamento_motor_sub]").each(function () {
							$(this).removeAttr("disabled").next("label").css("color", "#E00");
						});
					} else {
						$("input[name^=funcionamento_motor_sub]").each(function () {
							$(this).attr("disabled", "disabled").removeAttr("checked").next("label").css("color", "#281F20");
						});
					}
				});

				$("input[name=documentacao]").change(function () {
					if ($(this).val() == "sim") {
						$("input[name^=documentacao_sub]").each(function () {
							$(this).removeAttr("disabled").next("label").css("color", "#E00");
						});
					} else {
						$("input[name^=documentacao_sub]").each(function () {
							$(this).attr("disabled", "disabled").removeAttr("checked").next("label").css("color", "#281F20");
						});
					}
				});

				$("input[type=text]").each(function () {
					if ($.trim($(this).val()).length == 0) {
						$(this).css("border-color", "#E00");
					}
				});

				$("input[type=radio] ~ input[type=radio]").each(function () {
					if($(this).siblings().is(":checked") == false && $(this).is(":checked") == false) {
						$(this).parent("td").next("td").css("color", "#E00");
					}
				});

				$("input[type=text]").blur(function () {
					if ($.trim($(this).val()).length > 0) {
						$(this).css("border-color", "#000");
					}
				});

				$("input[type=radio]").change(function () {
					$(this).parent("td").next("td").css("color", "#281F20");
				});

				var array_sub = new Array("verificacao_niveis_sub", "funcionamento_motor_sub", "documentacao_sub");

				for (var i = 0; i < array_sub.length; i++) {
					if($("input[name^=" + array_sub[i] + "]:checked").length == 0 && $("input[name^=" + array_sub[i] + "]").is(":disabled") == false) {
						$("input[name^=" + array_sub[i] + "]").each(function () {
							$(this).next("label").css("color", "#E00");
						});
					} else {
						$("input[name^=" + array_sub[i] + "]").each(function () {
							$(this).next("label").css("color", "#281F20");
						});
					}
				}

				$("input[name^=verificacao_niveis_sub]").change(function () {
					if($("input[name^=verificacao_niveis_sub]:checked").length == 0) {
						$("input[name^=verificacao_niveis_sub]").each(function () {
							$(this).next("label").css("color", "#E00");
						});
					} else {
						$("input[name^=verificacao_niveis_sub]").each(function () {
							$(this).next("label").css("color", "#281F20");
						});
					}
				});

				$("input[name^=funcionamento_motor_sub]").change(function () {
					if($("input[name^=funcionamento_motor_sub]:checked").length == 0) {
						$("input[name^=funcionamento_motor_sub]").each(function () {
							$(this).next("label").css("color", "#E00");
						});
					} else {
						$("input[name^=funcionamento_motor_sub]").each(function () {
							$(this).next("label").css("color", "#281F20");
						});
					}
				});

				$("input[name^=documentacao_sub]").change(function () {
					if($("input[name^=documentacao_sub]:checked").length == 0) {
						$("input[name^=documentacao_sub]").each(function () {
							$(this).next("label").css("color", "#E00");
						});
					} else {
						$("input[name^=documentacao_sub]").each(function () {
							$(this).next("label").css("color", "#281F20");
						});
					}
				});
			});
		</script>

		<style>
			#principal {
				position: relative;
				margin: auto;
				width: 1124px;
			}

			#cabecalho {
				width: 100%;
				height: 100px;
			}

			#cabecalho .logo {
				position: relative;
				float: left;
				left: 50px;
				border: 0px;
				width: 300px;
			}

			#cabecalho h1 {
				position: relative;
				top: 45px;
				margin: auto;
				left: 80px;
				color: #000;
			}

			#conteudo {
				width: 100%;
				color: #281F20;
				text-align: center;
			}

			#rodape {
				width: 100%;
			}

			#rodape .logo {
				position: relative;
				float: right;
				top: 10px;
				right: 10px;
				border: 0px;
				width: 200px;
			}

			#rodape .link {
				position: relative;
				float: left;
				background: #000;
				top: 110px;
				left: 20px;
				font-size: 16px;
				border-radius: 20px;
				line-height: 50px;
				width: 200px;
				text-align: center;
			}

			.link a {
				text-decoration: none;
				color: #FFF;
			}

			hr {
				width:  100%;
			}

			table {
				margin: auto;
				width: 100%;
			}

			td {
				text-align: left;
				font-size: 17px;
			}

			th {
				text-align: left;
				font-size: 18px;
			}

			input[type=text] {
				border-top: 0;
				border-left: 0;
				border-right: 0;
				border-bottom: black solid 1px;
				font-size: 16px;
			}
		</style>
	</head>
	<body>
	<form method="post" >
		<div id="principal">
			<div id="cabecalho">
				<img class="logo" src="logos/toyama5.png" />
				<h1>Check List Para Entrega Técnica</h1>
			</div>

			<br />

			<?php
			if (!$_GET["imprimir"]) {
				echo "<h3 style='color: #e00; text-align: center;'>Preencha todos os campos em vermelho para prosseguir</h3>";
			}
			?>

			<div id="conteudo">
				<h2>Dados da Empresa</h2>

				<table border="0">
					<tr>
						<th style="width: 50px;"><? if(!strlen($checklist['empresa']['nome'])) echo "<b style='color: #e00;'>*</b>" ?><label for="empresa_nome">Nome:</label></th>
						<td><input type="text" id="empresa_nome" name="empresa_nome" value="<?=$checklist['empresa']['nome']?>" style="width: 100%;" <?=$readonly['empresa']['nome']?> /></td>
						<th style="width: 40px;"><label for="empresa_fone">Fone:</label></th>
						<td style="width: 120px;"><input type="text" id="empresa_fone" name="empresa_fone" value="<?=$checklist['empresa']['fone']?>" style="width: 120px;" <?=$readonly['empresa']['fone']?> /></td>
						<th style="width: 30px;"><label for="empresa_fax">Fax:</label></th>
						<td style="width: 120px;"><input type="text" id="empresa_fax" name="empresa_fax" value="<?=$checklist['empresa']['fax']?>" style="width: 120px;" <?=$readonly['empresa']['fax']?> /></td>
					</tr>
				</table>
				<table border="0">
					<tr>
						<th style="width: 30px;"><label for="empresa_cnpj">Cnpj:</label></th>
						<td style="width: 145px;"><input type="text" id="empresa_cnpj" name="empresa_cnpj" value="<?=$checklist['empresa']['cnpj']?>" style="width: 145px;" <?=$readonly['empresa']['cnpj']?> /></td>
						<th style="width: 80px;"><label for="empresa_ie">Insc. Est:</label></th>
						<td style="width: 175px;"><input type="text" id="empresa_ie" name="empresa_ie" value="<?=$checklist['empresa']['ie']?>" style="width: 175px;" <?=$readonly['empresa']['ie']?> /></td>
						<th style="width: 60px;"><label for="empresa_tecnico">Técnico:</label></th>
						<td><input type="text" id="empresa_tecnico" name="empresa_tecnico" value="<?=$checklist['empresa']['tecnico']?>" style="width: 100%;" /></td>
					</tr>
				</table>
				<table border="0">
					<tr>
						<th style="width: 60px;"><label for="empresa_email">E-mail:</label></th>
						<td><input type="text" id="empresa_email" name="empresa_email" value="<?=$checklist['empresa']['email']?>" style="width: 100%;" <?=$readonly['empresa']['email']?> /></td>
					</tr>
				</table>

				<hr color="#000" background-color="#000" size="6" />

				<h2>Dados do Cliente</h2>

				<table border="0">
					<tr>
						<th style="width: 50px;"><label for="cliente_nome">Nome:</label></th>
						<td><input type="text" id="cliente_nome" name="cliente_nome" value="<?=$checklist['cliente']['nome']?>" style="width: 100%;" <?=$readonly['cliente']['nome']?> /></td>
					</tr>
				</table>
				<table border="0">
					<tr>
						<th style="width: 60px;"><label for="cliente_endereco">Endereço:</label></th>
						<td><input type="text" id="cliente_endereco" name="cliente_endereco" value="<?=$checklist['cliente']['endereco']?>" style="width: 100%;" <?=$readonly['cliente']['endereco']?> /></td>
						<th style="width: 50px;"><label for="cliente_contato">Contato:</label></th>
						<td><input type="text" id="cliente_contato" name="cliente_contato" value="<?=$checklist['cliente']['contato']?>" style="width: 100%;" /></td>
					</tr>
				</table>
				<table border="0">
					<tr>
						<th style="width: 50px;"><label for="cliente_cidade">Cidade:</label></th>
						<td><input type="text" id="cliente_cidade" name="cliente_cidade" value="<?=$checklist['cliente']['cidade']?>" style="width: 100%;" <?=$readonly['cliente']['cidade']?> /></td>
						<th style="width: 40px;"><label for="cliente_bairro">Bairro:</label></th>
						<td><input type="text" id="cliente_bairro" name="cliente_bairro" value="<?=$checklist['cliente']['bairro']?>" style="width: 100%;" <?=$readonly['cliente']['bairro']?> /></td>
						<th style="width: 40px;"><label for="cliente_estado">Estado:</label></th>
						<td><input type="text" id="cliente_estado" name="cliente_estado" value="<?=$checklist['cliente']['estado']?>" style="width: 100%;" <?=$readonly['cliente']['estado']?> /></td>
					</tr>
				</table>
				<table border="0">
					<tr>
						<th style="width: 30px;"><label for="cliente_fone">Fone:</label></th>
						<td style="width: 120px;"><input type="text" id="cliente_fone" name="cliente_fone" value="<?=$checklist['cliente']['fone']?>" style="width: 120px;" <?=$readonly['cliente']['fone']?> /></td>
						<th style="width: 180px;"><label for="cliente_local_instalacao">Local de instalação:</label></th>
						<td><input type="text" id="cliente_local_instalacao" name="cliente_local_instalacao" value="<?=$checklist['cliente']['local_instalacao']?>" style="width: 100%;" /></td>
					</tr>
				</table>
				<table border="0">
					<tr>
						<th style="width: 50px;"><label for="cliente_email">E-mail:</label></th>
						<td><input type="text" id="cliente_email" name="cliente_email" value="<?=$checklist['cliente']['email']?>" style="width: 100%;" <?=$readonly['cliente']['email']?> /></td>
					</tr>
				</table>
				<table border="0">
					<tr>
						<th style="width: 80px;"><label for="cliente_responsavel">Responsável:</label></th>
						<td><input type="text" id="cliente_responsavel" name="cliente_responsavel" value="<?=$checklist['cliente']['responsavel']?>" style="width: 100%;" /></td>
					</tr>
				</table>

				<hr color="#000" background-color="#000" size="6" />

				<h2>Dados do Equipamento</h2>

				<table border="0">
					<tr>
						<th style="width: 50px;"><label for="equipamento_modelo">Modelo:</label></th>
						<td><input type="text" id="equipamento_modelo" name="equipamento_modelo" value="<?=$checklist['equipamento']['modelo']?>" style="width: 100%;" <?=$readonly['equipamento']['modelo']?> /></td>
						<th style="width: 50px;"><label for="equipamento_potencia">Potência:</label></th>
						<td><input type="text" id="equipamento_potencia" name="equipamento_potencia" value="<?=$checklist['equipamento']['potencia']?>" style="width: 100%;" /></td>
						<th style="width: 50px;"><label for="equipamento_tensao">Tensão:</label></th>
						<td><input type="text" id="equipamento_tensao" name="equipamento_tensao" value="<?=$checklist['equipamento']['tensao']?>" style="width: 100%;" <?=$readonly['equipamento']['tensao']?> /></td>
					</tr>
				</table>
				<table border="0">
					<tr>
						<th style="width: 210px;"><label for="equipamento_serie">Nº Série Equipamento:</label></th>
						<td><input type="text" id="equipamento_serie" name="equipamento_serie" value="<?=$checklist['equipamento']['serie']?>" style="width: 100%;" <?=$readonly['equipamento']['serie']?> /></td>
						<th style="width: 185px;"><label for="equipamento_serie_alternador">Nº Série Alternador:</label></th>
						<td><input type="text" id="equipamento_serie_alternador" name="equipamento_serie_alternador" value="<?=$checklist['equipamento']['serie_alternador']?>" style="width: 100%;" /></td>
						<th style="width: 140px;"><label for="equipamento_serie_motor">Nº Série Motor:</label></th>
						<td><input type="text" id="equipamento_serie_motor" name="equipamento_serie_motor" value="<?=$checklist['equipamento']['serie_motor']?>" style="width: 100%;" /></td>
					</tr>
				</table>
				<table border="0">
					<tr>
						<th style="width: 120px;"><label for="equipamento_vendido_por">Vendido Por:</label></th>
						<td><input type="text" id="equipamento_vendido_por" name="equipamento_vendido_por" value="<?=$checklist['equipamento']['vendido_por']?>" style="width: 100%;" /></td>
						<th style="width: 110px;"><label for="equipamento_data_venda">Data Venda:</label></th>
						<td style="width: 90px;"><input type="text" id="equipamento_data_venda" name="equipamento_data_venda" value="<?=$checklist['equipamento']['data_venda']?>" style="width: 100px;" <?=$readonly['equipamento']['data_venda']?> /></td>
					</tr>
				</table>

				<hr color="#000" background-color="#000" size="6" />

				<table border="0">
					<tr>
						<th style="text-align: center; width: 50%;">Verifique Antes do Funcionamento</th>
						<th style="text-align: center; width: 50%;">Verifique Conexões Elétricas</th>
					</tr>
					<tr>
						<td style="text-align: left; width: 50%;">
							<table border="0" style="margin: auto; width: 80%;">
								<tr>
									<td>
										sim/não
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="reaperto_parafuso" value="sim" <?=(($checklist["verifica_funcionamento"]["reaperto_parafuso"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="reaperto_parafuso" value="nao" <?=(($checklist["verifica_funcionamento"]["reaperto_parafuso"] == "nao") ? "checked" : "")?>/>
									</td>
									<td>
										Reaperto dos parafusos e verificação do alinhamento, se necessário
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="verificacao_escape" value="sim" <?=(($checklist["verifica_funcionamento"]["verificacao_escape"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="verificacao_escape" value="nao" <?=(($checklist["verifica_funcionamento"]["verificacao_escape"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Verificação do escape (diâmetro, flexível, curvas, comprimento, fixação, isolamento térmico, saida)
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="verificacao_circulacao_ar" value="sim" <?=(($checklist["verifica_funcionamento"]["verificacao_circulacao_ar"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="verificacao_circulacao_ar" value="nao" <?=(($checklist["verifica_funcionamento"]["verificacao_circulacao_ar"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Verificação da circulação de ar
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="verificacao_niveis" value="sim" <?=(($checklist["verifica_funcionamento"]["verificacao_niveis"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="verificacao_niveis" value="nao" <?=(($checklist["verifica_funcionamento"]["verificacao_niveis"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Verificação dos níveis:
									</td>
								</tr>
								<tr>
									<td>
									</td>
									<td>
										<input type="checkbox" name="verificacao_niveis_sub[]" value="agua" <?=(($checklist["verifica_funcionamento"]["verificacao_niveis"] == "nao" or !strlen($checklist["verifica_funcionamento"]["verificacao_niveis"])) ? "disabled" : "")?> <?=((in_array("agua", $checklist["verifica_funcionamento"]["verificacao_niveis_sub"])) ? "checked" : "")?> /> <label>Água</label> <br />
										<input type="checkbox" name="verificacao_niveis_sub[]" value="oleo" <?=(($checklist["verifica_funcionamento"]["verificacao_niveis"] == "nao" or !strlen($checklist["verifica_funcionamento"]["verificacao_niveis"])) ? "disabled" : "")?> <?=((in_array("oleo", $checklist["verifica_funcionamento"]["verificacao_niveis_sub"])) ? "checked" : "")?> /> <label>Óleo</label> <br />
										<input type="checkbox" name="verificacao_niveis_sub[]" value="combustivel" <?=(($checklist["verifica_funcionamento"]["verificacao_niveis"] == "nao" or !strlen($checklist["verifica_funcionamento"]["verificacao_niveis"])) ? "disabled" : "")?> <?=((in_array("combustivel", $checklist["verifica_funcionamento"]["verificacao_niveis_sub"])) ? "checked" : "")?> /> <label>Combustível</label>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="verificacao_bateria" value="sim" <?=(($checklist["verifica_funcionamento"]["verificacao_bateria"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="verificacao_bateria" value="nao" <?=(($checklist["verifica_funcionamento"]["verificacao_bateria"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Verificação da bateria, conexão, lubrificação dos terminais e aperto.
									</td>
								</tr>
							</table>
						</td>
						<td style="text-align: left; width: 50%;">
							<table border="0" style="margin: auto; width: 80%;">
								<tr>
									<td>
										sim/não
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="verificacao_cabo_potencia" value="sim" <?=(($checklist["verifica_conexao"]["verificacao_cabo_potencia"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="verificacao_cabo_potencia" value="nao" <?=(($checklist["verifica_conexao"]["verificacao_cabo_potencia"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Verificação dos cabos de potência (conexão, bitola)
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="verificacao_cabo_comando" value="sim" <?=(($checklist["verifica_conexao"]["verificacao_cabo_comando"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="verificacao_cabo_comando" value="nao" <?=(($checklist["verifica_conexao"]["verificacao_cabo_comando"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Verificação do cabo de comando entre o gerador e painel ats
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="funcionamento_motor" value="sim" <?=(($checklist["verifica_conexao"]["funcionamento_motor"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="funcionamento_motor" value="nao" <?=(($checklist["verifica_conexao"]["funcionamento_motor"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Colocação em funcionamento do motor:
									</td>
								</tr>
								<tr>
									<td>
									</td>
									<td>
										<input type="checkbox" name="funcionamento_motor_sub[]" value="verificacao_tensao" <?=(($checklist["verifica_conexao"]["funcionamento_motor"] == "nao" or !strlen($checklist["verifica_conexao"]["funcionamento_motor"])) ? "disabled" : "")?> <?=((in_array("verificacao_tensao", $checklist["verifica_conexao"]["funcionamento_motor_sub"])) ? "checked" : "")?> /> <label>Verificação da tensão/frequência</label> <br />
										<input type="checkbox" name="funcionamento_motor_sub[]" value="carga_bateria" <?=(($checklist["verifica_conexao"]["funcionamento_motor"] == "nao"  or !strlen($checklist["verifica_conexao"]["funcionamento_motor"])) ? "disabled" : "")?> <?=((in_array("carga_bateria", $checklist["verifica_conexao"]["funcionamento_motor_sub"])) ? "checked" : "")?> /> <label>Carga de bateria</label>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="testes_protecoes" value="sim" <?=(($checklist["verifica_conexao"]["testes_protecoes"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="testes_protecoes" value="nao" <?=(($checklist["verifica_conexao"]["testes_protecoes"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Testes e proteções
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="inspecao_instalacao" value="sim" <?=(($checklist["verifica_conexao"]["inspecao_instalacao"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="inspecao_instalacao" value="nao" <?=(($checklist["verifica_conexao"]["inspecao_instalacao"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Inspeção da instalação
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="teste_funcao_manual" value="sim" <?=(($checklist["verifica_conexao"]["teste_funcao_manual"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="teste_funcao_manual" value="nao" <?=(($checklist["verifica_conexao"]["teste_funcao_manual"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Teste em função manual
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="teste_funcao_automatica" value="sim" <?=(($checklist["verifica_conexao"]["teste_funcao_automatica"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="teste_funcao_automatica" value="nao" <?=(($checklist["verifica_conexao"]["teste_funcao_automatica"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Teste em função automática
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="teste_operacao" value="sim" <?=(($checklist["verifica_conexao"]["teste_operacao"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="teste_operacao" value="nao" <?=(($checklist["verifica_conexao"]["teste_operacao"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Testes em operação com carga de instalação e verificação das correntes
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>

				<table border="0" style="width: 50%; margin: auto;">
					<tr>
						<th style="text-align: center;">Instruções ao cliente</th>
					</tr>
					<tr>
						<td style="text-align: center;">
							<table border="0" style="margin: auto; width: 80%;">
								<tr>
									<td>
										sim/não
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="documentacao" value="sim" <?=(($checklist["instrucao"]["documentacao"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="documentacao" value="nao" <?=(($checklist["instrucao"]["documentacao"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Documentação
									</td>
								</tr>
								<tr>
									<td>
									</td>
									<td>
										<input type="checkbox" name="documentacao_sub[]" value="manual" <?=(($checklist["instrucao"]["documentacao"] == "nao" or !strlen($checklist["instrucao"]["documentacao"])) ? "disabled" : "")?> <?=((in_array("manual", $checklist["instrucao"]["documentacao_sub"])) ? "checked" : "")?> /> <label>Manual</label> <br />
										<input type="checkbox" name="documentacao_sub[]" value="chaves" <?=(($checklist["instrucao"]["documentacao"] == "nao" or !strlen($checklist["instrucao"]["documentacao"])) ? "disabled" : "")?> <?=((in_array("chaves", $checklist["instrucao"]["documentacao_sub"])) ? "checked" : "")?> /> <label>Chaves</label>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<input type="radio" name="verificacao_conformidade" value="sim" <?=(($checklist["instrucao"]["verificacao_conformidade"] == "sim") ? "checked" : "")?> />
										<input type="radio" name="verificacao_conformidade" value="nao" <?=(($checklist["instrucao"]["verificacao_conformidade"] == "nao") ? "checked" : "")?> />
									</td>
									<td>
										Verificação da conformidade da instalação e instruções ao cliente sobre o equipamento
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>

				<hr color="#000" background-color="#000" size="6" />

				<h2>Considerações Finais</h2>

				<table border="0" style="width: 100%;">
					<tr>
						<td>
							sim/não
						</td>
					</tr>
					<tr>
						<td valign="top">
							<input type="radio" name="existe_pendencia" value="sim" <?=(($checklist["consideracoes_finais"]["existe_pendencia"] == "sim") ? "checked" : "")?> />
							<input type="radio" name="existe_pendencia" value="nao" <?=(($checklist["consideracoes_finais"]["existe_pendencia"] == "nao") ? "checked" : "")?> />
						</td>
						<td>
							Existe pendências? em caso afirmativo, listá-las no relatório de pendências e anexá-lo a este documento. as pendências impedem o correto funcionamento do equipamento?
						</td>
					</tr>
					<tr>
						<td valign="top">
							<input type="radio" name="impedem_funcionamento_equipamento" value="sim" <?=(($checklist["consideracoes_finais"]["impedem_funcionamento_equipamento"] == "sim") ? "checked" : "")?> />
							<input type="radio" name="impedem_funcionamento_equipamento" value="nao" <?=(($checklist["consideracoes_finais"]["impedem_funcionamento_equipamento"] == "nao") ? "checked" : "")?> />
						</td>
						<td>
							As pendências impedem o correto funcionamento do equipamento? foram dadas as instruções de operação ao cliente? em caso afirmativo, listar o nome das pessoas treinadas no campo de observações
						</td>
					</tr>
				</table>

				<?php
				if ($_GET["imprimir"]) {
					echo "<br /> <br /> <br />";
				}
				?>

				<table border="0">
					<tr>
						<td style="text-align: left;">
							Observações:
						</td>
					</tr>
					<tr>
						<td>
							<textarea name="observacao" rows="5" style="width: 100%;"><?=$checklist['consideracoes_finais']['observacao']?></textarea>
						</td>
					</tr>
				</table>

				<br />

				<table border="0">
					<tr>
						<td valign="top">
							<input type="checkbox" onclick="$(this).removeAttr('checked');" />
						</td>
						<td valign="top">
							Afírmo ter recebido todas orientações e treinamento técnico, que qualifíca minha equipe acima descrita, e estou apto a operar e manusear o equipamento acima citado quanto ao perfeito funcionamento do equipamento.
						</td>
						<td valign="top">
							<input type="checkbox" onclick="$(this).removeAttr('checked');" />
						</td>
						<td valign="top">
							Considera que as pendências enumeradas e devidamente notifícadas deixam a instalação inadequada para a utilização. Em consequência , recusa-se o aceite do equipamento que está referenciado devido a situação de não funcionamento
						</td>
					</tr>
				</table>

				<br /> <br />

				<table border="0" style="margin-left: 40px; width: 95%;">
					<tr>
						<td>
							______________________________________________________ <br />
							CLIENTE (ASSINATURA)
						</td>
					</tr>
					<tr>
						<td>
							<br />
							______________________________________________________ <br />
							NOME LEGÍVEL
							<br /><br /><br /><br />
						</td>
						<td>
							<br />
							______________________________ <br />
							RG
							<br /><br /><br /><br />
						</td>
					</tr>
					<tr>
						<td>
							______________________________________________________ <br />
							LOCAL
						</td>
					</tr>
					<tr>
						<td>
							<br />
							_____/_____/_________ <br />
							DATA
						</td>
						<td>
							<br />
							____________________________________________________________________ <br />
							TÉCNICO (NOME E ASSINATURA)
						</td>
					</tr>
				</table>

				</br ></br ></br >

				<h5 style='color: #e00; text-align: center;'>
					PREZADO CLIENTE: ANTES DE ASSINAR, CONFIRA SE TODOS OS DADOS ESTÃO CORRETOS. A FALTA OU PERCA DESTE RELATÓRIO IMPLICA NA PERDA DA GARANTIA!
				</h5>

				<?php
				if (!$_GET["imprimir"]) {
				?>
					<input type="submit" name="gravar" value="Gravar" style="width: 90px; height: 30px; font-size: 16px; font-weight: bold;" />

					<br /> <br />
				<?php
				}
				?>
			</div>
			<div id="rodape">
				<div class="link">
					<a href="http://www.toyama.com.br" target="_blank" >www.toyama.com.br</a>
				</div>
				<img class="logo" src="logos/toyama4.png" />
			</div>
		</div>
	</form>

		<?php
		if ($_GET["imprimir"]) {
		?>
			<script>
				window.print();
			</script>
		<?php
		}
		?>
	</body>
</html>
