<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

$titulo = "Cadastro de Reportagem";
$msg_erro = array();
$n_imagens = 0;
$campos_telecontrol = array();

$reportagem = strlen($reportagem) == 0 && isset($_GET["reportagem"]) ? $_GET["reportagem"] : $reportagem;
$reportagem = strlen($reportagem) == 0 && isset($_POST["reportagem"]) ? $_POST["reportagem"] : $reportagem;

if (strlen($reportagem) > 0) {
	try {
		$reportagem = intval($reportagem);
		
		$sql = "
		SELECT
		reportagem
		
		FROM
		tbl_reportagem
		
		WHERE
		reportagem={$reportagem}
		AND fabrica={$login_fabrica}
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".pg_last_error($con)."'>", 1);
		
		if (pg_num_rows($res) == 0) {
			unset($_POST["btn_acao"]);
			unset($reportagem);
			throw new Exception("Reportagem não encontrado");
		}
	}
	catch(Exception $e) {
		unset($_POST["btn_acao"]);
		$msg_erro[] = ($e->getCode() == 1 ? "Falha ao localizar reportagem" : "") . $e->getMessage();
	}
}
else {
	try {
		$sql = "
		INSERT INTO tbl_reportagem (
		fabrica,
		titulo
		)
		
		VALUES (
		{$login_fabrica},
		''
		)
		
		RETURNING
		reportagem
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".pg_last_error($con)."'>", 1);
		
		$reportagem = pg_fetch_result($res, 0, 0);
		header("location:{$PHP_SELF}?reportagem={$reportagem}");
		die;
	}
	catch (Exception $e) {
		unset($_POST["btn_acao"]);
		$msg_erro[] = ($e->getCode() == 1 ? "Falha ao cadastrar nova reportagem" : "") . $e->getMessage();
	}
}

include_once "cabecalho.php";
require_once("../telecontrol_oo.class.php");

echo "<script type='text/javascript' src='../js/jquery-1.3.2.js'></script>";
echo "<script type='text/javascript' src='http://meta100.github.com/mColorPicker/javascripts/mColorPicker_min.js' charset='UTF-8'></script>";
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
echo "<link href='reportagem.css' rel='stylesheet' type='text/css'>";
echo "<script type='text/javascript' src='reportagem.js'></script>";

echo "<script src='../js/ckeditor/ckeditor.js' type='text/javascript'></script>";

$campos_telecontrol[$login_fabrica]['tbl_reportagem']['titulo']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['titulo']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['titulo']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['titulo']['label'] = 'Título';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['titulo']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_reportagem']['texto']['tipo'] = 'textarea';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['texto']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['texto']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['texto']['label'] = 'Texto: para inserir as "Fotos da Reportagem" no texto, veja o "Código" da foto e insira no texto entre colchetes, sem espaços dentro dos mesmos. Ex: se o código for 26, deve-se colocar "[26]" no local desejado para a foto. As fotos que tiverem a posição "galeria" devem ser representadas por "[GALERIA]" e não poderão ser representadas por seu código';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['texto']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_reportagem']['reportagem_foto']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['reportagem_foto']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['reportagem_foto']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['reportagem_foto']['label'] = 'Código';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['reportagem_foto']['tamanho'] = 3;
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['reportagem_foto']['bloqueia_edicao'] = 1;

$campos_telecontrol[$login_fabrica]['tbl_reportagem']['posicao']['tipo'] = 'select';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['posicao']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['posicao']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['posicao']['label'] = 'Posição';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['posicao']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_reportagem']['legenda']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['legenda']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['legenda']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['legenda']['label'] = 'Legenda';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['legenda']['tamanho'] = 0;

$campos_telecontrol[$login_fabrica]['tbl_reportagem']['imagem']['tipo'] = 'ajax_upload';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['imagem']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['imagem']['obrigatorio'] = 0;
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['imagem']['label'] = 'Imagem';
$campos_telecontrol[$login_fabrica]['tbl_reportagem']['imagem']['tamanho'] = 0;

//Bloqueio de campos
if (strlen($reportagem) > 0) {
//	$campos_telecontrol[$login_fabrica]['tbl_reportagem']['sua_os']['bloqueia_edicao'] = 1;
}

if (isset($_POST['btn_acao'])) {
	foreach($campos_telecontrol[$login_fabrica]['tbl_reportagem'] as $campo => $configuracoes) {
		$campos_telecontrol[$login_fabrica]['tbl_reportagem'][$campo]['valor'] = $_POST[$campo];
	}

	$n_imagens = intval($_POST["n_imagens"]);
}
elseif (strlen($reportagem) > 0) {
	try {
		$sql = "
		SELECT
		tbl_reportagem.titulo,
		tbl_reportagem.texto

		FROM
		tbl_reportagem

		WHERE
		tbl_reportagem.reportagem={$reportagem}
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar a reportagem <erro msg='".pg_last_error($con)."'>");
		
		$dados_reportagem = pg_fetch_array($res);

		foreach($campos_telecontrol[$login_fabrica]['tbl_reportagem'] as $campo => $configuracoes) {
			switch($configuracoes['tipo_dados']) {
				case "date":
					$dados_os[$campo] = implode('/', array_reverse(explode('-', $dados_os[$campo])));
				break;
				
				case "float":
					$dados_os[$campo] = str_replace(".", ",", $dados_os[$campo]);
				break;
			}

			$campos_telecontrol[$login_fabrica]['tbl_reportagem'][$campo]['valor'] = $dados_reportagem[$campo];
		}

		$sql = "
		SELECT
		tbl_reportagem_foto.reportagem_foto,
		tbl_reportagem_foto.posicao,
		tbl_reportagem_foto.legenda,
		tbl_reportagem_foto.imagem
		
		FROM
		tbl_reportagem_foto
		
		WHERE
		tbl_reportagem_foto.reportagem = {$reportagem}
		
		ORDER BY
		reportagem_foto
		";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar as fotos da reportagem <erro msg='".pg_last_error($con)."'>");

		for($i = 0; $i < pg_num_rows($res); $i++) {
			extract(pg_fetch_array($res));

			$imagens[$i]['reportagem_foto'] = $reportagem_foto;
			$imagens[$i]['posicao'] = $posicao;
			$imagens[$i]['legenda'] = $legenda;
			$imagens[$i]['imagem'] = $imagem;
		}

		$n_imagens = count($imagens);
	}
	catch (Exception $e) {
		unset($dados_reportagem);
		unset($imagens);
		$msg_erro[] = $e->getMessage();
	}
}

//Previne de bloquear campos obrigatórios não preenchidos
foreach($campos_telecontrol[$login_fabrica]['tbl_reportagem'] as $campo => $configuracoes) {
	if ($configuracoes['bloqueia_edicao'] == 1 && $configuracoes['obrigatorio'] == 1 && strlen($configuracoes['valor']) == 0) {
		$campos_telecontrol[$login_fabrica]['tbl_reportagem'][$campo]['bloqueia_edicao'] = 0;
	}
}

switch ($_POST["btn_acao"]) {
	case "gravar":
		try {
			$imagens = array();

			for($i = 0; $i < $n_imagens; $i++) {
				$reportagem_foto = intval($_POST["reportagem_foto{$i}"]);
				$posicao = $_POST["posicao{$i}"];
				$legenda = $_POST["legenda{$i}"];
				$imagem = $_POST["imagem{$i}"];
				
				if ($reportagem_foto > 0) {
					$sql = "
					SELECT
					tbl_reportagem_foto.reportagem_foto
					
					FROM
					tbl_reportagem_foto
					
					WHERE
					tbl_reportagem_foto.reportagem_foto = {$reportagem_foto}
					AND tbl_reportagem_foto.reportagem={$reportagem}
					";
					@$res = pg_query($con, $sql);
					if (pg_num_rows($res) == 0) $reportagem_foto = 0;
				}
				
				if (strlen($imagem) > 0 || $reportagem_foto > 0) {
					$imagens[$i]['reportagem_foto'] = $reportagem_foto;
					$imagens[$i]['posicao'] = $posicao;
					$imagens[$i]['legenda'] = $legenda;
					$imagens[$i]['imagem'] = $imagem;
				}
			}

			foreach($campos_telecontrol[$login_fabrica]['tbl_reportagem'] as $campo => $configuracoes) {
				$valor = $configuracoes['valor'];

				if ($configuracoes['obrigatorio'] == 1 && strlen($valor) == 0) {
					$msg_erro["{$campo}|obrigatorio"] = "O campo {$configuracoes["label"]} Ã© obrigatório";
				}

				switch($configuracoes['tipo_dados']) {
					case "int":
						$valor = intval(preg_replace( '/[^0-9]+/', '', $valor));
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
				throw new Exception('Falha na validação dos dados da reportagem');
			}

			@$res = pg_query($con, "BEGIN");
			if (strlen(pg_last_error($con)) > 0) throw new Exception('Falha ao iniciar transação');
			
			$nova_reportagem = false;

			if (strlen($reportagem) > 0) {
				$campos_update = array();
				$campos_update["titulo"]=$titulo;
				$campos_update["texto"]=$texto;

				foreach($campos_update as $campo => $valor) {
					if ($campos_telecontrol[$login_fabrica]['tbl_reportagem'][$campo]['bloqueia_edicao'] == 1) {
						unset($campos_update[$campo]);
					}
				}

				foreach($campos_update as $campo => $valor) {
					$campos_update[$campo] = "{$campo}={$valor}";
				}
				$update_string = implode(",", $campos_update);

				$sql = "
				UPDATE tbl_reportagem SET
				{$update_string}

				WHERE
				tbl_reportagem.reportagem={$reportagem}
				";
				@$res = pg_query($con, $sql);

				if (strlen(pg_last_error($con)) > 0) {
					throw new Exception('Falha ao atualizar dados da reportagem <erro msg=' . pg_last_error($con) . '>');
				}
			}
			else {
				$sql = "
				INSERT INTO tbl_reportagem(
				titulo,
				texto
				)

				VALUES(
				{$titulo},
				{$texto}
				)

				RETURNING reportagem
				";
				@$res = pg_query($con, $sql);

				if (strlen(pg_last_error($con)) > 0) {
					$erro = pg_last_error($con);

					throw new Exception("Falha ao cadastrar reportagem <erro msg='{$erro}'>");
				}

				$reportagem = pg_fetch_result($res, 0, "reportagem");
				$nova_reportagem = true;
			}

			foreach($imagens as $seq => $dados) {
				extract($dados);
				
				$posicao = "'{$posicao}'";
				$legenda = str_replace("'", "\'", $legenda);
				$legenda = "'{$legenda}'";
				$imagem = str_replace("'", "\'", $imagem);
				$imagem = strlen($imagem) > 0 ? "'{$imagem}'" : "";
				
				if ($reportagem_foto == 0) {
					$sql = "
					INSERT INTO tbl_reportagem_foto (
					reportagem,
					posicao,
					legenda,
					imagem
					)
					
					VALUES (
					{$reportagem},
					{$posicao},
					{$legenda},
					{$imagem}
					)
					
					RETURNING
					reportagem_foto
					";
					@$res = pg_query($con, $sql);
					
					if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao cadastrar foto para a reportagem <erro msg='{$erro}'>");
					
					$reportagem_foto = pg_fetch_result($res, 0, "reportagem_foto");
					$itens_texto[$i]['reportagem_foto'] = $reportagem_foto;
				}
				else {
					if (strlen($imagem) > 0) {
						$sql = "
						UPDATE tbl_reportagem_foto SET
						posicao = {$posicao},
						legenda = {$legenda},
						imagem = {$imagem}
						
						WHERE
						reportagem_foto = {$reportagem_foto}
						";
						@$res = pg_query($con, $sql);
						
						if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao atualizar foto para a reportagem <erro msg='{$erro}'>");
					}
					else {
						$sql = "
						DELETE FROM tbl_reportagem_foto
						WHERE reportagem_foto = {$reportagem_foto}
						";
						
						@$res = pg_query($con, $sql);
						
						if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao excluir foto da reportagem <erro msg='{$erro}'>");
					}
				}
			}

			@$res = pg_query($con, "COMMIT");
			header("location:{$PHP_SELF}?reportagem={$reportagem}");
			die;
		}
		catch (Exception $e) {
			@$res = pg_query($con, "ROLLBACK");
			if ($nova_reportagem) unset($reportagem);
			if (count($msg_erro) == 0) $msg_erro[] = $e->getMessage();
		}
	break;
}

$grupo_reportagem = new grupo("dados_reportagem", $campos_telecontrol[$login_fabrica], "Dados da Reportagem #{$reportagem}");
	$grupo_reportagem->add_element(new input_hidden("reportagem", '', $reportagem));
	$grupo_reportagem->add_field("tbl_reportagem", "titulo");
	$grupo_reportagem->add_field("tbl_reportagem", "texto");

	
$grupo_imagens = new grupo("grupo_imagens", $campos_telecontrol[$login_fabrica], "Fotos da Reportagem");
	$grupo_imagens->set_html_before("<div id='itens_imagens'>");
	$grupo_imagens->set_html_after("</div><div class='toolbar'><input id='imagens_adicionar' name='imagens_adicionar' type='button' value='Adicionar Nova Foto'></div>");
	
	$grupo_imagens->add_element(new input_hidden("n_imagens", '', $n_imagens));
	
	for ($i = -1; $i < $n_imagens; $i++) {
		$seq = $i == -1 ? "__modelo__" : $i;
		
		$grupo_imagens->add_field("tbl_reportagem", "reportagem_foto", "reportagem_foto{$seq}");
			$grupo_imagens->campos["reportagem_foto{$seq}"]->set_attr("seq='{$seq}'");
			$grupo_imagens->campos["reportagem_foto{$seq}"]->set_value($imagens[$seq]["reportagem_foto"]);
			$grupo_imagens->campos["reportagem_foto{$seq}"]->add_css_class("reportagem_foto_fotos", "input");
			$grupo_imagens->campos["reportagem_foto{$seq}"]->set_read_only();
			
		$grupo_imagens->add_field("tbl_reportagem", "posicao", "posicao{$seq}");
			$grupo_imagens->campos["posicao{$seq}"]->set_attr("seq='{$seq}'");
			$grupo_imagens->campos["posicao{$seq}"]->set_value($imagens[$seq]["posicao"]);
			$grupo_imagens->campos["posicao{$seq}"]->add_css_class("posicao_fotos", "input");
			
			$opcoes = array("direita", "esquerda", "centro", "galeria");
			foreach($opcoes as $opcao) {
				$grupo_imagens->campos["posicao{$seq}"]->add_option($opcao, $opcao);
			}

		$grupo_imagens->add_field("tbl_reportagem", "legenda", "legenda{$seq}");
			$grupo_imagens->campos["legenda{$seq}"]->set_attr("seq='{$seq}'");
			$grupo_imagens->campos["legenda{$seq}"]->set_value($imagens[$seq]["legenda"]);
			$grupo_imagens->campos["legenda{$seq}"]->add_css_class("legenda_fotos", "input");
			
		$grupo_imagens->add_field("tbl_reportagem", "imagem", "imagem{$seq}");
			$grupo_imagens->campos["imagem{$seq}"]->set_attr("seq='{$seq}'");
			$grupo_imagens->campos["imagem{$seq}"]->set_value($imagens[$seq]["imagem"]);
			$grupo_imagens->campos["imagem{$seq}"]->add_css_class("imagem_fotos", "input");
			$grupo_imagens->campos["imagem{$seq}"]->set_attr("seq='{$seq}'");

		if ($seq == "__modelo__") {
			$grupo_imagens->campos["reportagem_foto{$seq}"]->add_css_class($seq, "div");
			$grupo_imagens->campos["posicao{$seq}"]->add_css_class($seq, "div");
			$grupo_imagens->campos["legenda{$seq}"]->add_css_class($seq, "div");
			$grupo_imagens->campos["imagem{$seq}"]->add_css_class($seq, "div");
		}
	}

$grupo_acoes = new grupo("acoes_reportagem", $campos_telecontrol[$login_fabrica], "Ações");
	$grupo_acoes->set_html_before("<div class='toolbar toolbar_acoes_os'><input type='hidden' id='btn_acao' name='btn_acao' value=''><input id='btn_gravar_reportagem' name='btn_gravar_reportagem' type='button' value='Gravar'></div>");

if(is_array($msg_erro) > 0) {
	$msg_erro = implode('<br>', $msg_erro);
}

echo "<center>";

echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";

echo "<form id='frm_reportagem' name='frm_reportagem' method='post' enctype='multipart/form-data' >";

$grupo_reportagem->draw();
$grupo_imagens->draw();
$grupo_acoes->draw();

echo "</form>";

include_once "rodape.php";

?>
