<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

$msg_erro = array();
$prefixo_link = "http://posvenda.telecontrol.com.br/assist/admin/";

$informativo = strlen($informativo) == 0 && isset($_GET["informativo"]) ? $_GET["informativo"] : $informativo;
$informativo = strlen($informativo) == 0 && isset($_POST["informativo"]) ? $_POST["informativo"] : $informativo;

$estilo_td = "text-align: center; font-weight:bold; font-family: verdana; font-size: 11px; border-collapse: collapse; border:1px solid #000000;color:#FFFFFF;";
$estilo_tr_coluna = "background-color:#000000; font: bold 11px 'Arial'; color:#FFFFFF; text-align:center;";
$estilo_link = "color:#000000;";
$espaco_entre_modulos = "5px";

if (strlen($informativo) > 0) {
	try {
		echo '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html>
		<head>
		<title>Informativo</title>
		</head>

		<body style="background-image:url('.$prefixo_link.'reportagem_imagens/fundo.jpg);">
		<center>
		';

		$informativo = intval($informativo);
		
		$sql = "
		SELECT
		*
		
		FROM
		tbl_informativo
		
		WHERE
		informativo={$informativo}
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".pg_last_error($con)."'>", 1);
		
		if (pg_num_rows($res) == 0) {
			throw new Exception("Informativo não encontrado");
		}
		$dados_informativo = pg_fetch_array($res);
		
		echo "
			<table height='200' style='width: 600px; border: 2px solid #FFCC0E; margin-bottom: {$espaco_entre_modulos}; background-repeat: no-repeat; background-color: #000000; background-image: url({$prefixo_link}informativo_imagens/informativo_modulo_cabecalho_blackedecker.jpg)'>
				<tr>
					<td height='175'>
						&nbsp;
					</td>
				</tr>
				<tr>
					<td height='25' style='border-top: 2px solid #FFCC0E; color: #FFFFFF; font-family: Arial; font-size: 11pt; font-weight: bold; text-align: center;'>
						{$dados_informativo["titulo"]}
					</td>
				</tr>
			</table>
		";
		
		$sql = "
		SELECT
		*
		
		FROM
		tbl_informativo_modulo
		
		WHERE
		tbl_informativo_modulo.informativo={$informativo}
		
		ORDER BY
		COALESCE(ordem, 0) ASC
		";
		$res = pg_query($con, $sql);
		$n_modulos = pg_num_rows($res);
		
		for($i = 0; $i < $n_modulos; $i++) {
			$dados_modulo = pg_fetch_array($res);
			$dados_modulo["altura"] = intval($dados_modulo["altura"]);
			
			$sql = "
			SELECT
			*
			
			FROM
			tbl_informativo_modulo_texto
			
			WHERE
			tbl_informativo_modulo_texto.informativo_modulo={$dados_modulo["informativo_modulo"]}
			AND tbl_informativo_modulo_texto.texto IS NOT NULL
			AND tbl_informativo_modulo_texto.texto <> ''
			
			ORDER BY
			ordem,
			informativo_modulo_texto
			";
			$res_textos = pg_query($con, $sql);
			$n_textos = pg_num_rows($res_textos);

			$estilo_modulo = array();
			$estilo_modulo[] = "width: 600px";
			$estilo_modulo[] = "margin-bottom: {$espaco_entre_modulos}";
			
			if (strlen($dados_modulo["imagem_fundo"]) > 0 && file_exists($dados_modulo["imagem_fundo"])) {
				$imagem_fundo = "background='{$prefixo_link}{$dados_modulo["imagem_fundo"]}'";
			}
			else  {
				$imagem_fundo = "";
			}
			
			if (strlen($dados_modulo["imagem_direita"]) > 0 && file_exists($dados_modulo["imagem_direita"])) {
				$imagem_direita = "<img src='{$prefixo_link}{$dados_modulo["imagem_direita"]}' style='float: right; margin-left: 5px;' />";
			}
			else {
				$imagem_direita = "";
			}
			
			if (strlen($dados_modulo["imagem_esquerda"]) > 0 && file_exists($dados_modulo["imagem_esquerda"])) {
				$imagem_esquerda = "<img src='{$prefixo_link}{$dados_modulo["imagem_esquerda"]}' style='float: left; margin-right: 5px;' />";
			}
			else {
				$imagem_esquerda = "";
			}
			
			if (strlen($dados_modulo["link"]) > 0) {
				$abre_link = "<a style='{$estilo_link}' href='{$dados_modulo["link"]}' target='_blank'>";
				$fecha_link = "</a>";
				$onclick_link = "onclick='window.open(\"{$dados_modulo["link"]}\"); return false;'";
				$style_link = "cursor:pointer;";
			}
			else {
				$abre_link = "";
				$fecha_link = "";
				$onclick_link = "";
				$style_link = "";
			}
			
			if ($dados_modulo["altura"] > 0) {
				$altura_modulo = "height='{$dados_modulo["altura"]}'";
			}
			else {
				$altura_modulo = "";
			}
			
			if (strlen($dados_modulo["borda"]) > 0) {
				$estilo_modulo[] = "border: 1px solid {$dados_modulo["borda"]}";
			}
			else {
				$estilo_modulo[] = "border: none";
			}
			
			$estilo_modulo = implode("; ", $estilo_modulo);
			
			echo "
			{$abre_link}
			<table {$altura_modulo} style='{$style_link}{$estilo_modulo}' {$imagem_fundo} {$onclick_link}>
				<tr>
					<td>
						{$imagem_esquerda}{$imagem_direita}
			";
			
			$textos_formatados = array();
			
			for ($j = 0; $j < $n_textos; $j++) {
				$dados_texto = pg_fetch_array($res_textos);
				$dados_texto["texto"] = nl2br($dados_texto["texto"]);
				
				$estilo_texto = array();
				$estilo_texto[] = "display:table-row; margin-bottom: 0px; padding-bottom: 0px;";
				
				if (strlen($dados_texto["fonte"]) > 0) {
					$estilo_texto[] = "font-family: {$dados_texto["fonte"]}";
				}
				
				if (strlen($dados_texto["tamanho"]) > 0) {
					$estilo_texto[] = "font-size: {$dados_texto["tamanho"]}pt";
				}
				
				if (strlen($dados_texto["cor"]) > 0) {
					$estilo_texto[] = "color: {$dados_texto["cor"]}";
				}
				
				if (strlen($dados_texto["alinhamento"]) > 0) {
					$estilo_texto[] = "text-align: {$dados_texto["alinhamento"]}";
				}
				
				$estilo_texto = implode("; ", $estilo_texto);
				
				$textos_formatados[] = "<p style='{$estilo_texto}'>{$dados_texto["texto"]}</p>";
			}
			
			echo implode("", $textos_formatados);

			echo "
					</td>
				</tr>
			</table>
			{$fecha_link}
			";
		}

		$altura_ultimas = "height='30'";
		$estilo_ultimas = "background-color:#FFFFFF;";
		
		echo "
		<table width=600 cellspacing='0' style='border: 2px solid #FFCC0E'>
		<tr style='{$estilo_tr_coluna}'><td colspan=5 style='{$estilo_td}' {$altura_ultimas}>Últimas Atualizações</td></tr>
		<tr style=''>
			  <td style='{$estilo_ultimas}{$estilo_td}' {$altura_ultimas} width='33%'><a style='{$estilo_link}' target=_blank href='informativo_resumos.php?tipo=informativotecnico&informativo={$informativo}'>
		Informativos Técnicos<a></td>
			  <td style='{$estilo_ultimas}{$estilo_td}' {$altura_ultimas} width='33%'><a style='{$estilo_link}' target=_blank href='informativo_resumos.php?tipo=novosparceitos&informativo={$informativo}'>
		Novos Parceiros</a></td>
			  <td style='{$estilo_ultimas}{$estilo_td}' {$altura_ultimas} width='33%'><a style='{$estilo_link}' target=_blank href='informativo_resumos.php?tipo=chamadosporregiao&informativo={$informativo}'>
		Chamados por Região</a></td>
		</tr>
		";

		echo '
		</body>
		</html>
		';
	}		
	catch(Exception $e) {
		unset($_POST["btn_acao"]);
		$msg_erro[] = ($e->getCode() == 1 ? "Falha ao localizar informativo" : "") . $e->getMessage();
	}
}
else {
	$msg_erro[] = "Informativo não informado, impossível continuar";
}

if(count($msg_erro) > 0) {
	$msg_erro = implode('<br>', $msg_erro);
	echo "<link href='../telecontrol_oo.css' rel='stylesheet' type='text/css'>";
	echo "<center>";
	echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";
}
