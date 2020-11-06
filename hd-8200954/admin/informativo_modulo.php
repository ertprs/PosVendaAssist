<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

$titulo = "Cadastro de Módulos do Informativo";
$n_linhas_texto = 2; //Quantidade de linhas para lanÃ§amento de textos
$msg_erro = array();
$campos_telecontrol = array();



$informativo_modulo = strlen($informativo_modulo) == 0 && isset($_GET["informativo_modulo"]) ? $_GET["informativo_modulo"] : $informativo_modulo;
$informativo_modulo = strlen($informativo_modulo) == 0 && isset($_POST["informativo_modulo"]) ? $_POST["informativo_modulo"] : $informativo_modulo;

$informativo = strlen($informativo) == 0 && isset($_GET["informativo"]) ? $_GET["informativo"] : $informativo;
$informativo = strlen($informativo) == 0 && isset($_POST["informativo"]) ? $_POST["informativo"] : $informativo;

if ($_GET["fancybox"] == 1) {
	echo "<input type='hidden' id='fancybox' value='1' />";
}

if (strlen($informativo) > 0) {
	try {
		$informativo = intval($informativo);
		
		$sql = "
		SELECT
		titulo,
		data_inicial,
		data_final
		
		FROM
		tbl_informativo
		
		WHERE
		informativo={$informativo}
		AND fabrica={$login_fabrica}
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".pg_last_error($con)."'>", 1);
		
		if (pg_num_rows($res) == 0) {
			unset($_POST["btn_acao"]);
			unset($informativo);
			unset($informativo_modulo);
			throw new Exception("Informativo não encontrado");
		}
		
		$dados_informativo = pg_fetch_array($res);
		
		if (strlen($informativo_modulo) > 0) {
			try {
				$informativo_modulo = intval($informativo_modulo);
				
				$sql = "
				SELECT
				informativo_modulo
				
				FROM
				tbl_informativo_modulo
				
				WHERE
				informativo_modulo={$informativo_modulo}
				AND informativo={$informativo}
				";
				@$res = pg_query($con, $sql);
				
				if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".pg_last_error($con)."'>", 1);
				
				if (pg_num_rows($res) == 0) {
					unset($_POST["btn_acao"]);
					unset($informativo_modulo);
					throw new Exception("Módulo não encontrado para o informativo {$informativo} - {$dados_informativo["titulo"]}");
				}
			}
			catch(Exception $e) {
				unset($_POST["btn_acao"]);
				$msg_erro[] = ($e->getCode() == 1 ? "Falha ao localizar módulo do informativo" : "") . $e->getMessage();
			}
		}
		else {
			try {
				$sql = "
				INSERT INTO tbl_informativo_modulo (
				informativo
				)
				
				VALUES (
				{$informativo}
				)
				
				RETURNING
				informativo_modulo
				";
				@$res = pg_query($con, $sql);
				
				if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".pg_last_error($con)."'>", 1);
				
				$fancybox = $_GET["fancybox"] == "1" ? "&fancybox=1" : "";
				
				$informativo_modulo = pg_fetch_result($res, 0, 0);
				header("location:{$PHP_SELF}?informativo={$informativo}&informativo_modulo={$informativo_modulo}{$fancybox}");
				die;
			}
			catch (Exception $e) {
				unset($_POST["btn_acao"]);
				$msg_erro[] = ($e->getCode() == 1 ? "Falha ao cadastrar novo módulo" : "") . $e->getMessage();
			}
		}
	}
	catch(Exception $e) {
		unset($_POST["btn_acao"]);
		$msg_erro[] = ($e->getCode() == 1 ? "Falha ao localizar informativo" : "") . $e->getMessage();
	}
}
else {
	$msg_erro[] = "Informativo não informado, impossível continuar";
}

//include_once "cabecalho.php";
$header = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Módulos do Informativo</title>
</head>

<body>
';
echo $header;
echo '<link type="text/css" rel="stylesheet" href="css/css.css">';
require_once("../telecontrol_oo.class.php");

echo "<script type='text/javascript' src='../js/jquery-1.3.2.js'></script>";

echo "<script type='text/javascript' src='../js/mColorPicker_min.js'></script>";

echo "<script type='text/javascript' src='../js/date.js'></script>";

echo "<link rel='stylesheet' type='text/css' href='../js/datePicker-2.css' title='default' media='screen' />";

echo "<script type='text/javascript' src='../js/jquery.datePicker-2.js'></script>";

echo "<script type='text/javascript' src='../js/jquery.maskedinput2.js'></script>";

echo "<script type='text/javascript' src='../js/jquery.numeric.js'></script>";


echo "<link href='../js/jquery.autocomplete.css' rel='stylesheet' type='text/css'>";
echo "<script type='text/javascript' src='../js/jquery.bgiframe.min.js'></script>";
echo "<script type='text/javascript' src='../js/jquery.autocomplete.1.1.js'></script>";

echo "<link href='../js/valums_upload/client/fileuploader.css' rel='stylesheet' type='text/css' />";
echo "<script src='../js/valums_upload/client/fileuploader.js' type='text/javascript'></script>";

echo "<link href='../telecontrol_oo.css' rel='stylesheet' type='text/css'>";
echo "<link href='informativo_modulo.css' rel='stylesheet' type='text/css'>";
echo "<script type='text/javascript' src='informativo_modulo.js'></script>";

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_fundo']['tipo'] = 'ajax_upload';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_fundo']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_fundo']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_fundo']['label'] = 'Imagem de Fundo';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_fundo']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_direita']['tipo'] = 'ajax_upload';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_direita']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_direita']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_direita']['label'] = 'Imagem à  Direita';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_direita']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_esquerda']['tipo'] = 'ajax_upload';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_esquerda']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_esquerda']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_esquerda']['label'] = 'Imagem à  Esquerda';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['imagem_esquerda']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['link']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['link']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['link']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['link']['label'] = 'Link';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['link']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['altura']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['altura']['tipo_dados'] = 'int';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['altura']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['altura']['label'] = 'Altura (pixels)';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['altura']['tamanho'] = 5;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['ordem']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['ordem']['tipo_dados'] = 'int';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['ordem']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['ordem']['label'] = 'ordem';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['ordem']['tamanho'] = 5;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['borda']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['borda']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['borda']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['borda']['label'] = 'Borda';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['borda']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['fonte']['tipo'] = 'select';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['fonte']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['fonte']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['fonte']['label'] = 'Fonte';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['fonte']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['tamanho']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['tamanho']['tipo_dados'] = 'int';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['tamanho']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['tamanho']['label'] = 'Tamanho';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['tamanho']['tamanho'] = 3;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['cor']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['cor']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['cor']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['cor']['label'] = 'Cor';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['cor']['tamanho'] = 7;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['alinhamento']['tipo'] = 'select';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['alinhamento']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['alinhamento']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['alinhamento']['label'] = 'Alinhamento';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['alinhamento']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['ordem']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['ordem']['tipo_dados'] = 'int';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['ordem']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['ordem']['label'] = 'Ordem';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['ordem']['tamanho'] = 3;

$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['texto']['tipo'] = 'textarea';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['texto']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['texto']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['texto']['label'] = 'Texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['texto']['tamanho'] = 0;



//Bloqueio de campos
if (strlen($informativo_modulo) > 0) {
//	$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo']['sua_os']['bloqueia_edicao'] = 1;
}

if (isset($_POST['btn_acao'])) {
	foreach($campos_telecontrol[$login_fabrica]['tbl_informativo_modulo'] as $campo => $configuracoes) {
		$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo'][$campo]['valor'] = $_POST[$campo];
	}

	$n_linhas_texto = intval($_POST["n_linhas_texto"]);
}
elseif (strlen($informativo_modulo) > 0) {
	try {
		$sql = "
		SELECT
		tbl_informativo_modulo.imagem_fundo,
		tbl_informativo_modulo.imagem_direita,
		tbl_informativo_modulo.imagem_esquerda,
		tbl_informativo_modulo.link,
		tbl_informativo_modulo.altura,
		tbl_informativo_modulo.ordem,
		tbl_informativo_modulo.borda

		FROM
		tbl_informativo_modulo

		WHERE
		tbl_informativo_modulo.informativo_modulo={$informativo_modulo}
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar o módulo do informativo <erro msg='".pg_last_error($con)."'>");
		
		$dados_modulo_informativo = pg_fetch_array($res);

		foreach($campos_telecontrol[$login_fabrica]['tbl_informativo_modulo'] as $campo => $configuracoes) {
			switch($configuracoes['tipo_dados']) {
				case "date":
					$dados_os[$campo] = implode('/', array_reverse(explode('-', $dados_os[$campo])));
				break;
				
				case "float":
					$dados_os[$campo] = str_replace(".", ",", $dados_os[$campo]);
				break;
			}

			$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo'][$campo]['valor'] = $dados_modulo_informativo[$campo];
		}

		$sql = "
		SELECT
		tbl_informativo_modulo_texto.informativo_modulo_texto,
		tbl_informativo_modulo_texto.fonte,
		tbl_informativo_modulo_texto.tamanho,
		tbl_informativo_modulo_texto.cor,
		tbl_informativo_modulo_texto.alinhamento,
		tbl_informativo_modulo_texto.ordem,
		tbl_informativo_modulo_texto.texto
		
		FROM
		tbl_informativo_modulo_texto
		
		WHERE
		tbl_informativo_modulo_texto.informativo_modulo = {$informativo_modulo}
		
		ORDER BY
		ordem
		";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar módulo do informativo <erro msg='".pg_last_error($con)."'>");

		for($i = 0; $i < pg_num_rows($res); $i++) {
			extract(pg_fetch_array($res));

			$itens_texto[$i]['informativo_modulo_texto'] = $informativo_modulo_texto;
			$itens_texto[$i]['fonte'] = $fonte;
			$itens_texto[$i]['tamanho'] = $tamanho;
			$itens_texto[$i]['cor'] = $cor;
			$itens_texto[$i]['alinhamento'] = $alinhamento;
			$itens_texto[$i]['ordem'] = $ordem;
			$itens_texto[$i]['texto'] = $texto;
		}

		$n_linhas_texto = count($itens_texto) + 1;
	}
	catch (Exception $e) {
		unset($dados_modulo_informativo);
		unset($itens_texto);
		$msg_erro[] = $e->getMessage();
	}
}

//Previne de bloquear campos obrigatórios não preenchidos
foreach($campos_telecontrol[$login_fabrica]['tbl_informativo_modulo'] as $campo => $configuracoes) {
	if ($configuracoes['bloqueia_edicao'] == 1 && $configuracoes['obrigatorio'] == 1 && strlen($configuracoes['valor']) == 0) {
		$campos_telecontrol[$login_fabrica]['tbl_informativo_modulo'][$campo]['bloqueia_edicao'] = 0;
	}
}

switch ($_POST["btn_acao"]) {
	case "gravar":
		try {
			$itens_texto = array();

			for($i = 0; $i < $n_linhas_texto; $i++) {
				$informativo_modulo_texto = intval($_POST["informativo_modulo_texto{$i}"]);
				$fonte = $_POST["fonte{$i}"];
				$tamanho = intval($_POST["tamanho{$i}"]) > 0 ? intval($_POST["tamanho{$i}"]) : 10;
				$cor = $_POST["cor{$i}"];
				$alinhamento = $_POST["alinhamento{$i}"];
				$ordem = intval($_POST["ordem{$i}"]);
				$texto = $_POST["texto{$i}"];
				
				if ($informativo_modulo_texto > 0) {
					$sql = "
					SELECT
					tbl_informativo_modulo_texto.informativo_modulo_texto
					
					FROM
					tbl_informativo_modulo_texto
					
					WHERE
					tbl_informativo_modulo_texto.informativo_modulo_texto = {$informativo_modulo_texto}
					AND tbl_informativo_modulo_texto.informativo_modulo={$informativo_modulo}
					";
					@$res = pg_query($con, $sql);
					if (pg_num_rows($res) == 0) $informativo_modulo_texto = 0;
				}
				
				if (strlen($texto) > 0 || $informativo_modulo_texto > 0) {
					$itens_texto[$i]['informativo_modulo_texto'] = $informativo_modulo_texto;
					$itens_texto[$i]['fonte'] = $fonte;
					$itens_texto[$i]['tamanho'] = $tamanho;
					$itens_texto[$i]['cor'] = $cor;
					$itens_texto[$i]['alinhamento'] = $alinhamento;
					$itens_texto[$i]['ordem'] = $ordem;
					$itens_texto[$i]['texto'] = $texto;
				}
			}

			foreach($campos_telecontrol[$login_fabrica]['tbl_informativo_modulo'] as $campo => $configuracoes) {
				$valor = $configuracoes['valor'];

				if ($configuracoes['obrigatorio'] == 1 && strlen($valor) == 0) {
					$msg_erro["{$campo}|obrigatorio"] = "O campo {$configuracoes["label"]} é obrigatório";
				}

				switch($campo) {
					case "link":
						if (strlen($valor) > 0) {
							$valor = substr($valor, 0, 7) == "http://" ? $valor : "http://" . $valor;
							echo 123;
						}
					break;
				}

				switch($configuracoes['tipo_dados']) {
					case "int":
						$valor = intval(preg_replace( '[^0-9]+', '', $valor));
					break;
					
					case "float":
						$valor = floatval(str_replace(",", ".", $valor));
					break;

					case "date":
						if (strlen($valor) > 0) {
							$valor = implode('-', array_reverse(explode('/', $valor)));
							$sql = "SELECT '{$valor}'::date";
							@$res = pg_query($con, $sql);

							if (strlen(pg_errormessage()) > 0) {
								$msg_erro["{$campo}|invalido"] = "Data invÃ¡lida para o campo {$configuracoes["label"]}";
							}
							$valor = "'{$valor}'";
						}
						else {
							$valor = "NULL";
						}
					break;

					default:
						if (strlen($valor) > 0) {
							if ($configuracoes['tamanho'] > 0) $valor = substr($valor, 0, $configuracoes['tamanho']);
						}
						$valor = "'{$valor}'";
				}

				//Declara uma variável com nome do conteúdo da variável $campo e atribui $valor a ele
				//Ex: Sendo $campo igual a "consumidor_nome" a linha abaixo resultará em uma variável
				//    $consumidor_nome contendo o conteúdo de $valor
				$$campo = $valor;
			}
			
			if (count($msg_erro) > 0) {
				throw new Exception('Falha na validação dos dados do módulo do informativo');
			}

			@$res = pg_query($con, "BEGIN");
			if (strlen(pg_last_error($con)) > 0) throw new Exception('Falha ao iniciar transação');
			
			$novo_informativo_modulo = false;

			if (strlen($informativo_modulo) > 0) {
				$campos_update = array();
				$campos_update["imagem_fundo"]=$imagem_fundo;
				$campos_update["imagem_direita"]=$imagem_direita;
				$campos_update["imagem_esquerda"]=$imagem_esquerda;
				$campos_update["link"]=$link;
				$campos_update["altura"]=$altura;
				$campos_update["borda"]=$borda;
				$campos_update["ordem"]=$ordem;

				foreach($campos_update as $campo => $valor) {
					if ($campos_telecontrol[$login_fabrica]['tbl_informativo_modulo'][$campo]['bloqueia_edicao'] == 1) {
						unset($campos_update[$campo]);
					}
				}

				foreach($campos_update as $campo => $valor) {
					$campos_update[$campo] = "{$campo}={$valor}";
				}
				$update_string = implode(",", $campos_update);

				$sql = "
				UPDATE tbl_informativo_modulo SET
				{$update_string}

				WHERE
				tbl_informativo_modulo.informativo_modulo={$informativo_modulo}
				";
				@$res = pg_query($con, $sql);

				if (strlen(pg_last_error($con)) > 0) {
					throw new Exception('Falha ao atualizar dados do módulo do informativo <erro msg=' . pg_last_error($con) . '>');
				}
			}
			else {
				$sql = "
				INSERT INTO tbl_informativo_modulo(
				informativo,
				imagem_fundo,
				imagem_direita,
				imagem_esquerda,
				link,
				altura,
				ordem,
				borda
				)

				VALUES(
				{$informativo},
				{$imagem_fundo},
				{$imagem_direita},
				{$imagem_esquerda},
				{$link},
				{$altura},
				{$ordem},
				{$borda}
				)

				RETURNING informativo_modulo
				";
				@$res = pg_query($con, $sql);

				if (strlen(pg_last_error($con)) > 0) {
					$erro = pg_last_error($con);

					throw new Exception("Falha ao cadastrar módulo do informativo <erro msg='{$erro}'>");
				}

				$informativo_modulo = pg_fetch_result($res, 0, "informativo_modulo");
				$novo_informativo_modulo = true;
			}

			foreach($itens_texto as $seq => $dados) {
				extract($dados);
				
				$fonte = "'{$fonte}'";
				$cor = str_replace("'", "\'", $cor);
				$cor = "'{$cor}'";
				$alinhamento = str_replace("'", "\'", $alinhamento);
				$alinhamento = "'{$alinhamento}'";
				$texto = strlen($texto) > 0 ? "'{$texto}'" : $texto;
				
				if ($informativo_modulo_texto == 0) {
					$sql = "
					INSERT INTO tbl_informativo_modulo_texto (
					informativo_modulo,
					fonte,
					tamanho,
					cor,
					alinhamento,
					ordem,
					texto
					)
					
					VALUES (
					{$informativo_modulo},
					{$fonte},
					{$tamanho},
					{$cor},
					{$alinhamento},
					{$ordem},
					{$texto}
					)
					
					RETURNING
					informativo_modulo_texto
					";
					@$res = pg_query($con, $sql);
					if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao cadastrar texto para o módulo do informativo <erro msg='{".pg_last_error($con)."}'>");
					
					$informativo_modulo_texto = pg_fetch_result($res, 0, "informativo_modulo_texto");
					$itens_texto[$i]['informativo_modulo_texto'] = $informativo_modulo_texto;
				}
				else {
					if (strlen($texto) > 0) {
						$sql = "
						UPDATE tbl_informativo_modulo_texto SET
						fonte = {$fonte},
						tamanho = {$tamanho},
						cor = {$cor},
						alinhamento = {$alinhamento},
						ordem = {$ordem},
						texto = {$texto}
						
						WHERE
						informativo_modulo_texto = {$informativo_modulo_texto}
						";
						@$res = pg_query($con, $sql);
						
						if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao cadastrar texto para o módulo do informativo <erro msg='{".pg_last_error($con)."}'>");
					}
					else {
						$sql = "
						DELETE FROM tbl_informativo_modulo_texto
						WHERE informativo_modulo_texto = {$informativo_modulo_texto}
						";
						
						@$res = pg_query($con, $sql);
						
						if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao cadastrar texto para o módulo do informativo <erro msg='{".pg_last_error($con)."}'>");
					}
				}
			}

			@$res = pg_query($con, "COMMIT");
			header("location:{$PHP_SELF}?informativo={$informativo}&informativo_modulo={$informativo_modulo}");
			die;
		}
		catch (Exception $e) {
			@$res = pg_query($con, "ROLLBACK");
			if ($nova_os) unset($os);
			if (count($msg_erro) == 0) $msg_erro[] = $e->getMessage();
		}
	break;
}

$grupo_modulo = new grupo("dados_modulo", $campos_telecontrol[$login_fabrica], "Dados do Módulo #{$informativo_modulo} - Informativo #{$informativo} - {$dados_informativo["titulo"]}");
	$grupo_modulo->add_element(new input_hidden("informativo", '', $informativo));
	$grupo_modulo->add_element(new input_hidden("informativo_modulo", '', $informativo_modulo));
	$grupo_modulo->add_field("tbl_informativo_modulo", "imagem_esquerda");
	$grupo_modulo->add_field("tbl_informativo_modulo", "imagem_fundo");
	$grupo_modulo->add_field("tbl_informativo_modulo", "imagem_direita");
	$grupo_modulo->add_field("tbl_informativo_modulo", "link");
	$grupo_modulo->add_field("tbl_informativo_modulo", "ordem");
	$grupo_modulo->add_field("tbl_informativo_modulo", "altura");
	$grupo_modulo->add_field("tbl_informativo_modulo", "borda");
	$grupo_modulo->campos["borda"]->enable_color_picker();
	$grupo_modulo->campos["borda"]->set_attr("data-hex='true'");

$grupo_textos = new grupo("textos_modulo", $campos_telecontrol[$login_fabrica], "Textos do Módulo");
	$grupo_textos->set_html_before("<div id='itens_os_corpo'>");
	$grupo_textos->set_html_after("</div><div class='toolbar'><input id='textos_adicionar_texto' name='textos_adicionar_texto' type='button' value='Adicionar Novo Texto'></div>");

	$grupo_textos->add_element(new input_hidden("n_linhas_texto", '', $n_linhas_texto));

	for ($i = -1; $i < $n_linhas_texto; $i++) {
		$seq = $i == -1 ? "__modelo__" : $i;

		$grupo_textos->add_element(new input_hidden("informativo_modulo_texto{$seq}", '', $itens_texto[$seq]["informativo_modulo_texto"]));

		$grupo_textos->add_field("tbl_informativo_modulo", "fonte", "fonte{$seq}");
			$grupo_textos->campos["fonte{$seq}"]->set_attr("seq='{$seq}'");
			$grupo_textos->campos["fonte{$seq}"]->set_value($itens_texto[$seq]["fonte"]);
			$grupo_textos->campos["fonte{$seq}"]->add_css_class("fonte_textos", "input");
			
			$fontes = array("Arial", "Times", "Verdana");
			foreach($fontes as $fonte) {
				$grupo_textos->campos["fonte{$seq}"]->add_option($fonte, $fonte);
			}

		$grupo_textos->add_field("tbl_informativo_modulo", "tamanho", "tamanho{$seq}");
			$grupo_textos->campos["tamanho{$seq}"]->set_attr("seq='{$seq}'");
			$grupo_textos->campos["tamanho{$seq}"]->set_value($itens_texto[$seq]["tamanho"]);
			$grupo_textos->campos["tamanho{$seq}"]->add_css_class("tamanho_textos", "input");

		$grupo_textos->add_field("tbl_informativo_modulo", "cor", "cor{$seq}");
			$grupo_textos->campos["cor{$seq}"]->set_attr("seq='{$seq}'");
			$grupo_textos->campos["cor{$seq}"]->set_value($itens_texto[$seq]["cor"]);
			$grupo_textos->campos["cor{$seq}"]->enable_color_picker();
			$grupo_textos->campos["cor{$seq}"]->set_attr("data-hex='true'");
			$grupo_textos->campos["cor{$seq}"]->add_css_class("cor_textos", "input");

		$grupo_textos->add_field("tbl_informativo_modulo", "alinhamento", "alinhamento{$seq}");
			$grupo_textos->campos["alinhamento{$seq}"]->set_attr("seq='{$seq}'");
			$grupo_textos->campos["alinhamento{$seq}"]->set_value($itens_texto[$seq]["alinhamento"]);
			$grupo_textos->campos["alinhamento{$seq}"]->add_css_class("alinhamento_textos", "input");
			
			$alinhamentos = array("left" => "Esquerda", "right" => "Direita", "center" => "Centro", "justify" => "Justificado");
			foreach($alinhamentos as $valor => $alinhamento) {
				$grupo_textos->campos["alinhamento{$seq}"]->add_option($valor, $alinhamento);
			}

		$grupo_textos->add_field("tbl_informativo_modulo", "ordem", "ordem{$seq}");
			$grupo_textos->campos["ordem{$seq}"]->set_attr("seq='{$seq}'");
			$grupo_textos->campos["ordem{$seq}"]->set_value($itens_texto[$seq]["ordem"]);
			$grupo_textos->campos["ordem{$seq}"]->add_css_class("ordem_textos", "input");

		$grupo_textos->add_field("tbl_informativo_modulo", "texto", "texto{$seq}");
			$grupo_textos->campos["texto{$seq}"]->set_attr("seq='{$seq}'");
			$grupo_textos->campos["texto{$seq}"]->set_value($itens_texto[$seq]["texto"]);
			$grupo_textos->campos["texto{$seq}"]->add_css_class("texto_textos", "input");

		if ($seq == "__modelo__") {
			$grupo_textos->campos["fonte{$seq}"]->add_css_class($seq, "div");
			$grupo_textos->campos["tamanho{$seq}"]->add_css_class($seq, "div");
			$grupo_textos->campos["cor{$seq}"]->add_css_class($seq, "div");
			$grupo_textos->campos["alinhamento{$seq}"]->add_css_class($seq, "div");
			$grupo_textos->campos["ordem{$seq}"]->add_css_class($seq, "div");
			$grupo_textos->campos["texto{$seq}"]->add_css_class($seq, "div");
		}
	}

$grupo_acoes = new grupo("acoes_os", $campos_telecontrol[$login_fabrica], "Ações");
	$grupo_acoes->set_html_before("<div class='toolbar toolbar_acoes_os'><input type='hidden' id='btn_acao' name='btn_acao' value=''><input id='btn_gravar_informativo' name='btn_gravar_informativo' type='button' value='Gravar'></div>");

if(count($msg_erro) > 0) {
	$msg_erro = implode('<br>', $msg_erro);
	echo "<center>";
	echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";
}


echo "<form id='frm_informativo_modulo' name='frm_informativo_modulo' method='post' enctype='multipart/form-data' >";

$grupo_modulo->draw();
$grupo_textos->draw();
$grupo_acoes->draw();

echo "</form>";

//include_once "rodape.php";
echo '
</body>
</html>
';

?>
