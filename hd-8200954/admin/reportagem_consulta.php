
<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

$title = "CONSULTA DE REPORTAGENS";
$msg_erro = array();

include_once "cabecalho.php";
require_once("../telecontrol_oo.class.php");

//Apaga os módulos em branco

$sql = "
DELETE FROM tbl_reportagem
WHERE 
(titulo IS NULL or titulo='')
AND (texto IS NULL or texto='')";
@$res = pg_query($con, $sql);

echo "<link href='../telecontrol_oo.css' rel='stylesheet' type='text/css'>";

echo "<script type='text/javascript' src='reportagem_consulta.js'></script>";
echo "<link href='reportagem_consulta.css' rel='stylesheet' type='text/css'>";
$grupo_novo = new grupo ("novo", $campos_telecontrol[$login_fabrica], "Adicionar nova reportagem");
	
$grupo_novo->set_html_before("<input id='btn_nova_reportagem' name='btn_nova_reportagem' class='btn_nova_reportagem'  type='button' value='Nova Reportagem' onclick=window.open('reportagem.php')>");

$grupo_reportagem = new grupo ("reportagem", $campos_telecontrol[$login_fabrica], "Consulta de Reportagens");

$sql = "SELECT titulo, reportagem FROM tbl_reportagem WHERE fabrica={$login_fabrica} ORDER BY reportagem DESC";

	@$res = pg_query($con, $sql);
	if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao listar reportagens. <erro msg='".pg_last_error($con)."'>");
	//print_r(pg_fetch_array($res));
		
		$tabela .= "
		<table align='center' width='690' cellspacing='1'class='tabela'>
		<tr align='center'>
		<th class='th_titulo'>Título</th>
		<th align='center' class='th_acoes'>Ações</th>
		</tr>";
	
	while ($linha = pg_fetch_array($res)){ 

		//$titulo = $linha['titulo'];
		
		if($cont%2==0)
		$cor = "#F7F5F0";
		else
		$cor= "#F1F4FA";

		
		$tamanho_texto = 70;
		$texto = strip_tags($linha['titulo']);
		if($texto==""){
		$texto ="Sem Título";
		}
		$sinal = strlen($texto) > $tamanho_texto ? "..." : "";
		$texto = substr($texto, 0, $tamanho_texto) . $sinal;
		
		
		
		$tabela .= "
		<tr bgcolor='{$cor}'>
		<td  class='td_titulo'>{$texto}</td>
		
		<td align='right' class='td_acoes'>
		<input id='btn_editar_informativo' name='btn_editar_informativo' class='btn_tamanho' type='button' value='Editar' onclick=window.open('reportagem.php?reportagem={$linha['reportagem']}')>  
		
		<input id='btn_visualizar_informativo' name='btn_visualizar_informativo' class='btn_tamanho' type='button' value='Visualizar' onclick=window.open('reportagem_visualiza.php?reportagem={$linha['reportagem']}')>		
		
		<input id='btn_excluir_reportagem' name='btn_excluir_reportagem' class='btn_excluir_reportagem btn_tamanho' id_reportagem='{$linha['reportagem']}' type='button' value='Excluir'>
		</td>
		</tr>";
		$cont++;
	} 
		$tabela .= "</table>";

	$grupo_reportagem->set_html_after($tabela);

	if(is_array($msg_erro) > 0) {
	$msg_erro = implode('<br>', $msg_erro);
	}

echo "<center>";
	
echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";

$grupo_novo->draw(); 
$grupo_reportagem->draw();

include_once "rodape.php";
