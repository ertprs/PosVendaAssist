<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

$title = "CADASTRO DE INFORMATIVOS";
$msg_erro = array();
$campos_telecontrol = array();

$informativo= strlen($informativo) == 0 && isset($_GET["informativo"]) ? $_GET["informativo"] : $informativo;
$informativo= strlen($informativo) == 0 && isset($_POST["informativo"]) ? $_POST["informativo"] : $informativo;

// verifica o informativo que foi submetido para edicao
if (strlen($informativo) > 0) {
	try {
		$informativo = intval($informativo);
		
		$sql = "SELECT informativo, fabrica FROM tbl_informativo WHERE informativo={$informativo} AND fabrica={$login_fabrica} ";
		@$res = pg_query($con, $sql);
		if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar informativo <erro msg='".pg_last_error($con)."'>");
		
		if (pg_num_rows($res) == 0) {
			unset($_POST["btn_acao"]);
			unset($informativo);
			throw new Exception("Informativo não encontrado");
		}
		$dados_validados = pg_fetch_array($res);
	}
	catch(Exception $e) {
		unset($_POST["btn_acao"]);
		unset($informativo);
		unset($dados_validados);
		$msg_erro[] = $e->getMessage();	
	}
}
	
include_once "cabecalho.php";
require_once("../telecontrol_oo.class.php");

echo "<script type='text/javascript' src='../js/jquery-1.3.2.js'></script>";
echo "<script type='text/javascript' src='../js/date.js'></script>";
echo "<link rel='stylesheet' type='text/css' href='../js/datePicker-2.css' title='default' media='screen' />";
echo "<script type='text/javascript' src='../js/jquery.datePicker-2.js'></script>";
echo "<script type='text/javascript' src='../js/jquery.maskedinput2.js'></script>";
echo "<script type='text/javascript' src='../js/jquery.numeric.js'></script>";

echo "<link href='../js/jquery.autocomplete.css' rel='stylesheet' type='text/css'>";
echo "<link href='../telecontrol_oo.css' rel='stylesheet' type='text/css'>";
echo "<script type='text/javascript' src='../js/jquery.bgiframe.min.js'></script>";
echo "<script type='text/javascript' src='../js/jquery.autocomplete.1.1.js'></script>";

echo "<script type='text/javascript' src='../ajax.js'></script>";
echo "<script type='text/javascript' src='../ajax_cep.js'></script>";

echo "<link href='informativo_edicao.css' rel='stylesheet' type='text/css'>";


echo "<script type='text/javascript' src='informativo_consulta.js'></script>";

$campos_telecontrol[$login_fabrica]['tbl_informativo']['titulo']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo']['titulo']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_informativo']['titulo']['obrigatorio'] = 1;
$campos_telecontrol[$login_fabrica]['tbl_informativo']['titulo']['label'] = 'Título';
$campos_telecontrol[$login_fabrica]['tbl_informativo']['titulo']['tamanho'] = 150;

$campos_telecontrol[$login_fabrica]['tbl_informativo']['data_inicial']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo']['data_inicial']['tipo_dados'] = 'date';
$campos_telecontrol[$login_fabrica]['tbl_informativo']['data_inicial']['obrigatorio'] = 1;
$campos_telecontrol[$login_fabrica]['tbl_informativo']['data_inicial']['label'] = 'Data Inicial';
$campos_telecontrol[$login_fabrica]['tbl_informativo']['data_inicial']['tamanho'] = 10;

$campos_telecontrol[$login_fabrica]['tbl_informativo']['data_final']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_informativo']['data_final']['tipo_dados'] = 'date';
$campos_telecontrol[$login_fabrica]['tbl_informativo']['data_final']['obrigatorio'] = 1;
$campos_telecontrol[$login_fabrica]['tbl_informativo']['data_final']['label'] = 'Data Final';
$campos_telecontrol[$login_fabrica]['tbl_informativo']['data_final']['tamanho'] = 10;

// recuperando os dados do banco de dados
	if (isset($_POST['btn_acao'])) {
		foreach($campos_telecontrol[$login_fabrica]['tbl_informativo'] as $campo => $configuracoes) {
		$campos_telecontrol[$login_fabrica]['tbl_informativo'][$campo]['valor'] = $_POST[$campo];
		}
				
	}elseif (strlen($informativo) > 0) {
		try {
			$sql = "SELECT titulo, data_inicial, data_final FROM tbl_informativo WHERE informativo={$informativo}";
		
		@$res = pg_query($con, $sql);
		if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar informativo <erro msg='".pg_last_error($con)."'>");
		
		$dados_informativo = pg_fetch_array($res);
	
		foreach($campos_telecontrol[$login_fabrica]['tbl_informativo'] as $campo => $configuracoes){
			if($configuracoes['tipo_dados']=="date"){ 
					$dados_informativo[$campo] = implode('/', array_reverse(explode('-', $dados_informativo[$campo])));
					}	
		$campos_telecontrol[$login_fabrica]['tbl_informativo'][$campo]['valor'] = $dados_informativo[$campo];
		}				
	}
		catch (Exception $e) {
			unset($_POST["btn_acao"]);
			unset($informativo);
			unset($dados_informativo);
			$msg_erro[] = $e->getMessage();
		}
}	

//Previne de bloquear campos obrigatórios não preenchidos
foreach($campos_telecontrol[$login_fabrica]['tbl_informativo'] as $campo => $configuracoes) {
	if ($configuracoes['bloqueia_edicao'] == 1 && $configuracoes['obrigatorio'] == 1 && strlen($configuracoes['valor']) == 0) {
		$campos_telecontrol[$login_fabrica]['tbl_informativo'][$campo]['bloqueia_edicao'] = 0;
	}
}

switch ($_POST["btn_acao"]) {
	case "gravar":
		try {	
			$informativo_dados = array();
			$contador_data=0;
			foreach($campos_telecontrol[$login_fabrica]['tbl_informativo'] as $campo => $configuracoes) {
			
				$valor = $configuracoes['valor'];
				
				if ($configuracoes['obrigatorio'] == 1 && strlen($valor) == 0) {
					switch($campo){
						case "titulo":
						$msg_erro["{$campo}|obrigatorio"] = "O campo {$configuracoes["label"]} é obrigatório";
						break;
					
						case "data_inicial":
							if($contador_data<1){
							$contador_data=1;
							$msg_erro[]="Data Inválida";
							}
						break;

						case "data_final":
							if($contador_data<1){
								$msg_erro[]="Data Inválida";
								$contador_data=1;
							}
						break;
					}
				}

				switch($configuracoes['tipo_dados']) {

					case "date":
						
						if (strlen($valor) > 0) {
							$valor = implode('-', array_reverse(explode('/', $valor)));
							$sql = "SELECT '{$valor}'::date";
							@$res = pg_query($con, $sql);
							if (strlen(pg_errormessage()) > 0) {
							$msg_erro[] = "Data inválida";
							}
							$valor = "'{$valor}'";
						}else {
							$valor = "NULL";
							}
					break;
			
					default:
						if (strlen($valor) > 0) {
						if ($configuracoes['tamanho'] > 0) $valor = substr($valor, 0, $configuracoes['tamanho']);
						}
						$valor = "'{$valor}'";
				}
			
				
				$$campo = $valor;
			}
			if (count($msg_erro) > 0) throw new Exception("<erro msg='".pg_last_error($con)."'>");
			
			if (($data_inicial > $data_final)){
				if($contador_data<1){
				$msg_erro[]= "Data Inválida";
				}
				
			}
				elseif (strlen($informativo) > 0) {
				$sql="
					UPDATE 
					tbl_informativo 
					
					SET
					titulo = {$titulo},
					data_inicial = {$data_inicial},
					data_final = {$data_final}
					
					WHERE
					informativo = {$informativo}
					
					AND
					fabrica = {$login_fabrica}
					";
					
					@$res = pg_query($con,$sql);
					if (pg_last_error($con)>0) {throw new Exception("Falha ao atualizar registro.<erro msg='".pg_last_error($con)."'>");}
					
					else {$msg_sucesso["mensagem_sucesso"]= "Gravado com Sucesso!";}
					foreach($campos_telecontrol[$login_fabrica]["tbl_informativo"] as $campo => $dados) {
						unset($campos_telecontrol[$login_fabrica]["tbl_informativo"][$campo]["valor"]);
					}
			
			} else{
					$sql="INSERT INTO tbl_informativo(
					fabrica,
					titulo,
					data_inicial,
					data_final,
					admin_enviar)
					VALUES(
					{$login_fabrica},
					{$titulo},
					{$data_inicial}, 
					{$data_final},
					{$login_admin})";
					
					@$res = pg_query($con,$sql);
					if (pg_last_error($con)>0) throw new Exception("Falha ao inserir registro<erro msg='".pg_last_error($con)."'>");
					
					else {$msg_sucesso["mensagem_sucesso"]= "Gravado com Sucesso!";}
				
					foreach($campos_telecontrol[$login_fabrica]["tbl_informativo"] as $campo => $dados) {
						unset($campos_telecontrol[$login_fabrica]["tbl_informativo"][$campo]["valor"]);
					}
				}
				
		}catch (Exception $e) {
			$msg_erro[] = $e->getMessage();
			unset($_POST["btn_acao"]);
			unset($informativo);
			unset($dados_informativo);
			}
		break;	
}
$grupo_edicoes = new grupo("dados_edicao", $campos_telecontrol[$login_fabrica], "Cadastramento");
	$grupo_edicoes->add_field("tbl_informativo", "titulo");
	$grupo_edicoes->add_field("tbl_informativo", "data_inicial");
	$grupo_edicoes->add_field("tbl_informativo", "data_final");
	$grupo_edicoes->set_html_after("
	<input type='hidden' id='btn_acao' name='btn_acao' value=''>
	<input id='btn_gravar_informativo' name='btn_gravar_informativo' type='button' value='Gravar' onclick='gravar_informativo();'>
	");


// listagem de informativo

$grupo_listar = new grupo ("listar", $campos_telecontrol[$login_fabrica], "Consulta");
		
		$sql="SELECT  tbl_informativo.informativo, 
		tbl_informativo.titulo, 
		TO_CHAR(tbl_informativo.data_inicial, 'DD/MM/YYYY') AS data_inicial, 
		TO_CHAR(tbl_informativo.data_final, 'DD/MM/YYYY') AS data_final, 
		TO_CHAR(tbl_informativo.publicar, 'DD/MM/YYYY HH24:MI') AS publicar, 
		TO_CHAR(tbl_informativo.enviar, 'DD/MM/YYYY HH24:MI') AS enviar, 
		tbl_admin.nome_completo 
		FROM tbl_informativo 
		LEFT JOIN tbl_admin 
		ON tbl_informativo.admin_enviar = tbl_admin.admin 
		WHERE tbl_informativo.fabrica>0
		ORDER BY tbl_informativo.data_final DESC";
		
		@$res = pg_query($con, $sql);
		$registros = pg_num_rows($res);
		if (pg_last_error($con)>0){
			throw new Exception("Falha ao consultar informativos.<erro msg='".pg_last_error($con)."'>");
		}
		
	while ($linha = pg_fetch_array($res)){

		$titulo = $linha['titulo'];
		$periodo = "Data Inicial:{$linha['data_inicial']} -  Data Final:{$linha['data_final']}";
		$publicar = ($linha['publicar']);
		$enviar = ($linha['enviar']);
		$admin_enviar = ($linha['nome_completo']);
		
		$tabela .= "<p>
		<table id='tabela' align='center' width='690' cellspacing='1' cellpadding='4' class='tabela' id='table_{$linha['informativo']}'>
		<tr align='center' bgcolor='#F7F5F0'>
		<td colspan='2' align='center'>{$titulo}</td>
		</tr>
		<tr align='center' bgcolor='#F1F4FA'>
		<td align='center' >Período</td>
		<td align='left' class='td_tamanho'>{$periodo}</td>
		</tr>
		<tr align='center' bgcolor='#F7F5F0'>
		<td align='center' >Publicar</td>
		<td align='left' class='td_tamanho' id='publicar{$linha['informativo']}'>{$publicar}</td>
		</tr>
		<tr align='center' bgcolor='#F1F4FA'>
		<td align='center'>Enviar</td>
		<td align='left' class='td_tamanho' id='enviar{$linha['informativo']}'>{$enviar}</td>
		</tr>
		<tr align='center' bgcolor='#F7F5F0'>
		<td align='center'>Admin</td>
		<td align='left' class='td_tamanho' id='admin_enviar{$linha['informativo']}'>{$admin_enviar}</td>
		</tr>
		<tr align='center' bgcolor='#F1F4FA'>
		<td align='center' >Ações</td>
		
		<td align='center' class='td_tamanho'>
		<input id='btn_ediar_informativo' name='btn_editar_informativo' class='btn_tamanho' type='button' value='Editar' onClick=self.location.href='informativo_edicao.php?informativo={$linha['informativo']}'>
	
		  
		<input id='btn_modulos_informativo' name='btn_modulos_informativo' class='btn_tamanho' type='button' value='Modulos' onclick=window.open('informativo_modulo_consulta.php?informativo={$linha['informativo']}')>
		
		<input id='btn_visualizar_informativo' name='btn_visualizar_informativo' class='btn_tamanho' type='button' value='Visualizar' onclick=window.open('informativo_email.php?informativo={$linha['informativo']}')>
		
		<input id='btn_testar_informativo' name='btn_testar_informativo' id_informativo='{$linha['informativo']}' class='btn_testar_informativo btn_tamanho' type='button' value='Testar'>
		
		<input id='btn_publicar_informativo' name='btn_publicar_informativo'  class='btn_publicar_informativo btn_tamanho' id_informativo='{$linha['informativo']}' type='button' value='Publicar'>
		
		<input id='btn_desativar_informativo' name='btn_desativar_informativo' class='btn_desativar_informativo btn_tamanho' id_informativo='{$linha['informativo']}' type='button' value='Desativar'>
		
		<input id='btn_enviar_informativo' name='btn_enviar_informativo' class='btn_enviar_informativo btn_tamanho' id_informativo='{$linha['informativo']}' type='button' value='Enviar'>
		
		<input id='btn_excluir_informativo' name='btn_excluir_informativo' class='btn_excluir_informativo btn_tamanho' id_informativo='{$linha['informativo']}' type='button' value='Excluir'></td>
		</tr>";
		$cont++;
	} 
		$tabela .= "</p></table>";

	$grupo_listar->set_html_after($tabela);

if(is_array($msg_erro) > 0) {
	$msg_erro = implode('<br>', $msg_erro);
}
if(is_array($msg_sucesso) > 0) {
	$msg_sucesso = implode('<br>', $msg_sucesso);
}
echo "<center>";

echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";
echo "<div id='msg_sucesso' name='msg_sucesso' class='msg_sucesso'>{$msg_sucesso}</div>";

echo "<form id='frm_informativo' name='frm_informativo' method='post' enctype='multipart/form-data' >";

$grupo_edicoes->draw();

if($registros>0){
$grupo_listar->draw();
}

echo "</form>";

include_once "rodape.php";

?>