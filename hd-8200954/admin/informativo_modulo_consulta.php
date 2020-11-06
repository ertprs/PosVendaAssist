<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

$title = "CONSULTA DE MÓDULOS";
$msg_erro = array();

$informativo= strlen($informativo) == 0 && isset($_GET["informativo"]) ? $_GET["informativo"] : $informativo;
$informativo= strlen($informativo) == 0 && isset($_POST["informativo"]) ? $_POST["informativo"] : $informativo;

// verifica o informativo que foi submetido para edicao
if (strlen($informativo) > 0) {
	try {
		$informativo = intval($informativo);
		
		$sql = "SELECT informativo, fabrica, titulo FROM tbl_informativo WHERE informativo={$informativo} AND fabrica={$login_fabrica} ";
		@$res = pg_query($con, $sql);
		if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar informativo <erro msg='".pg_last_error($con)."'>");
		
		if (pg_num_rows($res) == 0) {
			unset($_POST["btn_acao"]);
			unset($informativo);
			throw new Exception("Informativo não encontrado");
		}
		$resultado = pg_fetch_array($res);
		$titulo_informativo = $resultado['titulo'];
		
	}
	catch(Exception $e) {
		unset($_POST["btn_acao"]);
		unset($informativo);
		unset($dados_validados);
		$msg_erro[] = $e->getMessage();	
	}
}

//Apaga os módulos em branco
$sql = "
DELETE FROM tbl_informativo_modulo
WHERE
tbl_informativo_modulo.informativo={$informativo}
AND (tbl_informativo_modulo.imagem_fundo IS NULL OR tbl_informativo_modulo.imagem_fundo='')
AND (tbl_informativo_modulo.imagem_direita IS NULL OR tbl_informativo_modulo.imagem_direita='')
AND (tbl_informativo_modulo.imagem_esquerda IS NULL OR tbl_informativo_modulo.imagem_esquerda='')
AND (SELECT COUNT(*) FROM tbl_informativo_modulo_texto WHERE tbl_informativo_modulo_texto.informativo_modulo=tbl_informativo_modulo.informativo_modulo) = 0
";
@$res = pg_query($con, $sql);

include_once "cabecalho.php";
require_once("../telecontrol_oo.class.php");

echo "<link href='../telecontrol_oo.css' rel='stylesheet' type='text/css'>";
echo "<script type='text/javascript' src='../js/jquery-1.3.2.js'></script>";
echo "<script type='text/javascript' src='informativo_modulo_consulta.js'></script>";
echo "<link href='informativo_modulo_consulta.css' rel='stylesheet' type='text/css'>";

echo "<link rel='stylesheet' type='text/css' href='fancybox/jquery.fancybox-1.3.4.css' media='screen' />";
echo "<script type='text/javascript' src='fancybox/jquery.fancybox-1.3.4.pack.js'></script>";

	
	$grupo_novo = new grupo ("novo", $campos_telecontrol[$login_fabrica], "Adicionar novo módulo no informativo - {$titulo_informativo}");
	
	$grupo_novo->set_html_before("<input href='informativo_modulo.php?informativo={$informativo}&fancybox=1' id='btn_novo_modulo' name='btn_novo_modulo' class='btn_novo_modulo'  type='button' value='Novo Módulo'> <input type='button' value='Visualizar' id='btn_visualizar' name='btn_visualizar' href='informativo_email.php?informativo={$informativo}'>"); 

	
	$grupo_listar_modulos= new grupo ("Módulos", $campos_telecontrol[$login_fabrica], "Consulta dos Módulos do informativo - {$titulo_informativo}");
	
	if($informativo>1){

	$sql = "
	SELECT
	tbl_informativo_modulo.informativo_modulo,
	(
		SELECT
		tbl_informativo_modulo_texto.texto 

		FROM 
		tbl_informativo_modulo_texto 
		
		WHERE 
		tbl_informativo_modulo_texto.informativo_modulo=tbl_informativo_modulo.informativo_modulo 
		AND tbl_informativo_modulo_texto.texto<>'' 

		ORDER BY tbl_informativo_modulo_texto.ordem ASC
		
		
		LIMIT 1
	) AS texto

	FROM 
	tbl_informativo_modulo
	
	WHERE 
	tbl_informativo_modulo.informativo={$informativo} 
	
	ORDER BY
	tbl_informativo_modulo.ordem
	";

	@$res = pg_query($con, $sql);
	
	if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao listar modulos.<erro msg='".pg_last_error($con)."'>");
	
	$tabela .= "
		<table align='center' width='690' cellspacing='1' class='tabela'>
		<tr align='center'>
		<th class='th_titulo'>Texto do Módulo</th>
		<th align='center' class='th_acoes'>Ações</th>
		</tr>";
		
	while ($linha = pg_fetch_array($res) ){
	
		if($cont%2==0)
		$cor = "#F7F5F0";
		else
		$cor= "#F1F4FA";
		
		$tamanho_texto = 70;
		$texto = strip_tags($linha['texto']);
		if($texto==""){
		$texto ="Sem Título";
		}
		$sinal = strlen($texto) > $tamanho_texto ? "..." : "";
		$texto = substr($texto, 0, $tamanho_texto) . $sinal;
		
		$tabela .= "
		<tr align='center' bgcolor='{$cor}'>
		<td align='left'  class='td_texto'>{$texto}</td>
		
		<td align='right' class='td_acoes'>
		
		<input href='informativo_modulo.php?informativo={$informativo}&informativo_modulo={$linha['informativo_modulo']}&fancybox=1' id='btn_editar_modulo' name='btn_editar_modulo' class='btn_editar_modulo btn_tamanho'  type='button' value='Editar'>
			
		
		<input id='btn_excluir_modulo' name='btn_excluir_modulo' class='btn_excluir_modulo btn_tamanho' id_informativo_modulo='{$linha['informativo_modulo']}' type='button' value='Excluir'></td>
		</tr>";
		$cont++; 
	} 
		$tabela .= "</table>";

	$grupo_listar_modulos->set_html_after($tabela);

}else $msg_erro[] = "Não existe módulos para esse informativo";

echo "<center>";

if(count($msg_erro) > 0) {
	$msg_erro = implode('<br>', $msg_erro);
	echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";
}

$grupo_novo->draw(); 
$grupo_listar_modulos->draw();

include_once "rodape.php";

/*<input id='btn_ediar_informativo' name='btn_editar_informativo' class='btn_tamanho' type='button' value='Editar' onclick=window.open('informativo_modulo.php?informativo={$informativo}&informativo_modulo={$linha['informativo_modulo']}')>	
<input id='btn_editar_modulo' name='btn_editar_modulo' class='btn_editar_modulo btn_tamanho' informativo='{$informativo}' id_informativo_modulo='{$linha['informativo_modulo']}' type='button' value='Editar'>
*/
?>
