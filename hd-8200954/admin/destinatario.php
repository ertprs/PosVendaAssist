<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

$title = "CADASTRO DE DESTINATÁRIO";
$msg_erro = array();
$msg_sucesso = array();
$destinatario_dados = array();
$campos_telecontrol = array();
//Definir aki o numero de registros por pagina para paginação
$registros_por_pagina = 20;

if(!isset($_GET["pagina"])){
	$pagina_atual = 1;
	
	}
else{
	$pagina_atual = intval($_GET["pagina"]);
}


if ($_POST["btn_pesquisar"] == "pesquisar") {
	$string_get = array();
	foreach($_POST as $campo => $valor) {
		$string_get[] = "{$campo}={$valor}";
	}
	$string_get = implode("&", $string_get);
	
	header("location:{$PHP_SELF}?pagina=1&{$string_get}");
	die;
}

$destinatario= strlen($destinatario) == 0 && isset($_GET["destinatario"]) ? $_GET["destinatario"] : $destinatario;
$destinatario= strlen($destinatario) == 0 && isset($_POST["destinatario"]) ? $_POST["destinatario"] : $destinatario;

// verifica o destinatario que foi submetido para edicao
if (strlen($destinatario) > 0) {
	try {
		$destinatario = intval($destinatario);
		
		$sql = "SELECT nome, email, fabrica FROM tbl_destinatario WHERE destinatario={$destinatario} AND fabrica={$login_fabrica} ";
		@$res = pg_query($con, $sql);
		if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar destinatario <erro msg='".pg_last_error($con)."'>");
		
		if (pg_num_rows($res) == 0) {
			unset($_POST["btn_acao"]);
			unset($destinatario);
			throw new Exception("Destinatario não encontrado");
		}
		$dados_validados = pg_fetch_array($res);
	}
	catch(Exception $e) {
		unset($_POST["btn_acao"]);
		unset($destinatario);
		unset($dados_validados);
		$msg_erro[] = $e->getMessage();	
	}
}

include_once "cabecalho.php";
require_once("../telecontrol_oo.class.php");

echo "<link href='../telecontrol_oo.css' rel='stylesheet' type='text/css'>";
echo "<script type='text/javascript' src='destinatario.js'></script>";
echo "<link href='destinatario.css' rel='stylesheet' type='text/css'>";

$campos_telecontrol[$login_fabrica]['tbl_destinatario']['nome']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['nome']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['nome']['obrigatorio'] = 1;
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['nome']['label'] = 'Nome';
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['nome']['tamanho'] = 150;

$campos_telecontrol[$login_fabrica]['tbl_destinatario']['email']['tipo'] = 'texto';
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['email']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['email']['obrigatorio'] = 1;
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['email']['label'] = 'E-mail';
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['email']['tamanho'] = 150;

$campos_telecontrol[$login_fabrica]['tbl_destinatario']['ativo']['tipo'] = 'select';
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['ativo']['tipo_dados'] = 'text';
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['ativo']['obrigatorio'] = 1;
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['ativo']['label'] = 'Ativo';
$campos_telecontrol[$login_fabrica]['tbl_destinatario']['ativo']['tamanho'] = 1;



// recuperando os dados do banco de dados
if (($_POST['btn_acao'])) {
		foreach($campos_telecontrol[$login_fabrica]['tbl_destinatario'] as $campo => $configuracoes) {
		$campos_telecontrol[$login_fabrica]['tbl_destinatario'][$campo]['valor'] = $_POST[$campo];
		}
				
	}elseif (strlen($destinatario) > 0) {
		try {
			$sql = "SELECT nome, email, ativo FROM tbl_destinatario WHERE destinatario={$destinatario}";
		
		@$res = pg_query($con, $sql);
		if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao consultar destinatario <erro msg='".pg_last_error($con)."'>");
		
		$dados_destinatario = pg_fetch_array($res);
	
		foreach($campos_telecontrol[$login_fabrica]['tbl_destinatario'] as $campo => $configuracoes){
			if($configuracoes['tipo_dados']=="date"){ 
					$dados_destinatario[$campo] = implode('/', array_reverse(explode('-', $dados_destinatario[$campo])));
					}	
		$campos_telecontrol[$login_fabrica]['tbl_destinatario'][$campo]['valor'] = $dados_destinatario[$campo];
		}				
	}
		catch (Exception $e) {
			unset($_POST["btn_acao"]);
			unset($destinatario);
			unset($dados_destinatario);
			$msg_erro[] = $e->getMessage();
		}
}

//Previne de bloquear campos obrigatórios não preenchidos
foreach($campos_telecontrol[$login_fabrica]['tbl_destinatario'] as $campo => $configuracoes) {
	if ($configuracoes['bloqueia_edicao'] == 1 && $configuracoes['obrigatorio'] == 1 && strlen($configuracoes['valor']) == 0) {
		$campos_telecontrol[$login_fabrica]['tbl_destinatario'][$campo]['bloqueia_edicao'] = 0;
	}
}
if ((isset($_POST['btn_acao']))) {

		try {	

			foreach($campos_telecontrol[$login_fabrica]['tbl_destinatario'] as $campo => $configuracoes) {
			
				$valor = $configuracoes['valor'];

				if ($configuracoes['obrigatorio'] == 1 && strlen($valor) == 0) {
					$msg_erro["{$campo}|obrigatorio"] = "O campo {$configuracoes["label"]} é obrigatório";	
				}

				switch($campo) {
					case "email":
					if (strlen($valor) > 0) {
							if (preg_match (								"/^[A-Za-z0-9]+([_.-][A-Za-z0-9]+)*@[A-Za-z0-9]+([_.-][A-Za-z0-9]+)*\\.[A-Za-z0-9]{2,4}$/", $email)) {
							$valor = "'{$valor}'";
							}
							else{
							$msg_erro["{$campo}|invalido"] = "E-mail Inválido {$configuracoes["label"]}";
							$valor = "NULL";
							}
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
			
				 elseif (strlen($destinatario) > 0) {
				$sql="
					UPDATE 
					tbl_destinatario 
					
					SET
					nome = {$nome},
					email = {$email},
					ativo = {$ativo}
					
					WHERE
					destinatario = {$destinatario}
					
					AND
					fabrica = {$login_fabrica}
					";
					
					@$res = pg_query($con,$sql);
					if (pg_last_error($con)>0) {throw new Exception("Falha ao atualizar destinatario.<erro msg='".pg_last_error($con)."'>");}
					
					else {$msg_sucesso["mensagem_sucesso"]= "Gravado com Sucesso!";}
					foreach($campos_telecontrol[$login_fabrica]["tbl_destinatario"] as $campo => $dados) {
						unset($campos_telecontrol[$login_fabrica]["tbl_destinatario"][$campo]["valor"]);
					}
			
			} else{
					$nome=strtoupper($nome);
					$sql="INSERT INTO tbl_destinatario(
					fabrica,
					nome,
					email,
					ativo)
					VALUES(
					{$login_fabrica},
					{$nome},
					{$email}, 
					{$ativo})";
					
					@$res = pg_query($con,$sql);
					if (pg_last_error($con)>0) throw new Exception("Falha ao inserir destinatario<erro msg='".pg_last_error($con)."'>");
					
					else {$msg_sucesso["mensagem_sucesso"]= "Gravado com Sucesso!";}
				
					foreach($campos_telecontrol[$login_fabrica]["tbl_destinatario"] as $campo => $dados) {
						unset($campos_telecontrol[$login_fabrica]["tbl_destinatario"][$campo]["valor"]);
					}
				}
				
		}catch (Exception $e) {
			$msg_erro[] = $e->getMessage();
			unset($_POST["btn_acao"]);
			unset($destinatario);
			unset($dados_destinatario);
			}

}

$grupo_edicao = new grupo ("dados_edicao", $campos_telecontrol[$login_fabrica], "Cadastro de destinatários");

	$grupo_edicao->add_field("tbl_destinatario", "nome");
	$grupo_edicao->add_field("tbl_destinatario", "email");
	$grupo_edicao->add_field("tbl_destinatario", "ativo");
	$grupo_edicao->campos["ativo"]->add_option("t", "Sim");
	$grupo_edicao->campos["ativo"]->add_option("f", "Não");
	
	$grupo_edicao->set_html_after("<div class='btn_tamanho btn_acoes'>
	
	<input type='hidden' id='btn_pesquisar' name='btn_pesquisar' value=''>
	<input id='btn_pesquisar_destinatario' name='btn_pesquisar_destinatario' type='button' value='Pesquisar' onclick='pesquisar_destinatario();'>
	
	<input type='hidden' id='btn_acao' name='btn_acao' value=''>
	<input id='btn_gravar_destinatario' name='btn_gravar_destinatario' type='button' value='Gravar' onclick='gravar_destinatario();'></div>
	");
	
// Lista destinatarios 
$grupo_listar_destinatarios = new grupo ("Destinatarios", $campos_telecontrol[$login_fabrica], "Consulta de Destinatários");

if(strlen($btn_pesquisar) > 0){

	try{
		$nome = strlen($nome) == 0 && isset($_GET["nome"]) ? $_GET["nome"] : $nome;
		$nome = strlen($nome) == 0 && isset($_POST["nome"]) ? $_POST["nome"] : $nome;
		
		$email = strlen($email) == 0 && isset($_GET["email"]) ? $_GET["email"] : $email;
		$email = strlen($email) == 0 && isset($_POST["email"]) ? $_POST["email"] : $email;
		
		$ativo = strlen($ativo) == 0 && isset($_GET["ativo"]) ? $_GET["ativo"] : $ativo;
		$ativo = strlen($ativo) == 0 && isset($_POST["ativo"]) ? $_POST["ativo"] : $ativo;
		
		$nome = strtoupper($nome);
		
		
		$condicoes = array();
		$condicoes_paginacao = array();
		
		if (strlen($nome) > 0) {
			$condicoes[] = "nome LIKE '{$nome}%'";
			$condicoes_paginacao[] = "nome={$nome}";
		}
		
		if (strlen($email) > 0) {
		$condicoes[] = "email LIKE '{$email}%'";
		$condicoes_paginacao[] = "email={$email}";
		}
		
		if (strlen($ativo) > 0) {
		$condicoes[] = "ativo='{$ativo}'";
		$condicoes_paginacao[] = "ativo={$ativo}";
		}
		
		if (count($condicoes) == 0) throw new Exception("erro preencher campos");
		
		$condicoes = "AND " . implode(' AND ', $condicoes);
		$condicoes_paginacao = '&btn_pesquisar=Pesquisar&' . implode('&', $condicoes_paginacao);
		
		

	}catch (Exception $e) {
		$msg_erro[] = $e->getMessage();
		$condicoes = "";
		unset($_POST["btn_pesquisar"]);
		unset($destinatario);
		unset($dados_destinatario);
		}

}
	$base_sql = "
	SELECT
	[campos]
	
	FROM
	tbl_destinatario
	
	WHERE
	fabrica={$login_fabrica}
	{$condicoes}
	";
	
	// conta qunatidade de registro para paginação
	$sql_cont = str_replace("[campos]", "COUNT(*)", $base_sql);
	$res = pg_query($con, $sql_cont);
	$total_registros = pg_fetch_result($res, 0, 0);
	$total_paginas = ceil($total_registros / $registros_por_pagina);
	//if($total_pagina>0){
	if ($pagina_atual <= 0) $pagina_atual = 1;
	if ($pagina_atual > $total_paginas) $pagina_atual = $total_paginas;
	$offset = $registros_por_pagina * ($pagina_atual-1);
	if($offset<0){
	$msg_erro[] = "Não foram encontrados resultados para essa pesquisa.";
	$offset=0;
	}
	
	//busca os registros
	$sql = str_replace("[campos]", "destinatario, nome, email, ativo", $base_sql);
	$sql .= "ORDER BY nome LIMIT 20 OFFSET {$offset}";
	@$res = pg_query($con, $sql);
	if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao listar destinatários.<erro msg='".pg_last_error($con)."'>");
	

	$tabela .= "
		<table align='center' width='690' cellspacing='1' class='tabela'>
		<tr align='center'>
		<th align='left'>Nome</th>
		<th align='left'>E-mail</th>
		<th align='left'>Ativo</th>
		<th align='center'>Ações</th>
		</tr>";
		
	while ($linha = pg_fetch_array($res) ){
	
		if($cont%2==0)
		$cor = "#F7F5F0";
		else
		$cor= "#F1F4FA";
	
		$email = str_replace(";", "<br/>", $linha['email']);
		$ativo = strtoupper($linha['ativo']);
		
		if($ativo=="T"){
			$ativo= "S";
			$situacao="Desativar";
			}
		else{
			$ativo="N";
			$situacao="Ativar";
			}
			
			
		$tabela .= "
		<tr align='left' bgcolor='{$cor}'>
		<td align='left'class='td_nome'>{$linha['nome']}</td>
		<td align='left'class='td_email'>{$email}</td>
		<td align='center'class='td_ativo' id='situacao{$linha['destinatario']}'>{$ativo}</td>
		
		<td align='right' class='td_acoes'>
	
		<input id='btn_ediar_destinatario' name='btn_editar_destinatario'  type='button' class='btn_tamanho'value='Editar' onClick=self.location.href='destinatario.php?destinatario={$linha['destinatario']}'>	
		
		
		<input id_ativar_desativar_destinatario='{$linha['destinatario']}' name='btn_ativar_desativar_destinatario' class='btn_ativar_desativar_destinatario btn_tamanho' type='button' value='{$situacao}' id='btn_ativar_desativar_destinatario{$linha['destinatario']}'></td>
		</tr>";
		$cont++;
	} 
		$tabela .= "</table>";

	$grupo_listar_destinatarios->set_html_before($tabela);
	
	

if(is_array($msg_erro) > 0) {
	$msg_erro = implode('<br>', $msg_erro);
}
if(is_array($msg_sucesso) > 0) {
	$msg_sucesso = implode('<br>', $msg_sucesso);
}
echo "<center>";

echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";
echo "<div id='msg_sucesso' name='msg_sucesso' class='msg_sucesso'>{$msg_sucesso}</div>";

echo "<form id='frm_destinatario' name='frm_destinatario' method='post' enctype='multipart/form-data' >";

$grupo_edicao->draw();
$grupo_listar_destinatarios->draw();

echo "</form>";

//exibe a paginação se o resultado for > 20
if($total_registros>20){
	if($pagina_atual<=1){
		echo "Anterior ";
	}else{
		$anterior = $pagina_atual-1;
		echo "<a href='destinatario.php?pagina={$anterior}{$condicoes_paginacao}'>Anterior </a> ";
		}
	if ($total_paginas > 1){
		for ($i=1;$i<=$total_paginas;$i++){ 
		if ($pagina_atual == $i){
			echo $pagina_atual . " "; 
		}else{ 
			echo "<a href='destinatario.php?pagina={$i}{$condicoes_paginacao}'>" . $i . "</a> "; 
			}	
		}
	}
	if($pagina_atual==$total_paginas){
		echo " Próxima";
	}else{
		$proxima = $pagina_atual+1;
		echo "<a href='destinatario.php?pagina={$proxima}{$condicoes_paginacao}'> Próxima</a> ";
		}
}else{
		echo "";
		}
		
include_once "rodape.php";

?>
