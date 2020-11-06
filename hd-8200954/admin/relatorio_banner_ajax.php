<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$msg_erro = array();

/**
 * Buscas de Auto Complete de dados do posto
 */

$charsToRemove = array('/','.','-','(',')');

if (isset($_REQUEST['busca_auto_complete'])){

	//Busca Posto
	if (isset($_REQUEST['tipo_busca']) and $_REQUEST['tipo_busca']=='posto') {
		
		$valor = $_REQUEST['q'];

		//separa as condições SQL pelo tipo da busca
		if ($_REQUEST['busca']=='cnpj'){			
			
			// Se for CNPJ, vai retirar dos valores passados, os caracteres especiais que são definidos no array "$charsToRemove"
			$valor = str_replace($charsToRemove, '', $valor);
			
			$condition = " tbl_posto.cnpj like '$valor%' ";

		}elseif ($_REQUEST['busca'] == 'nome_fantasia') {
			
			$condition = " tbl_posto_fabrica.nome_fantasia ilike '%$valor%' ";

		}

		$sql = "SELECT distinct tbl_posto.cnpj,tbl_posto_fabrica.nome_fantasia 
				FROM tbl_posto 
				JOIN tbl_posto_fabrica using (posto) 
				WHERE $condition 
				";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res)>0) {
			foreach (pg_fetch_all($res) as $key => $value) {
				echo $value['cnpj']."|".$value['nome_fantasia']."\n";
			}
		}

	}

	exit;
}


//Validate action
if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'validateFormPesquisa' ) {
	
	// Get REQUEST data
	$data_inicial = $_REQUEST["data_inicial"];
	$data_final   = $_REQUEST["data_final"];
	$posto_cnpj   = trim($_REQUEST["posto_cnpj"]);
	$posto_nome   = trim($_REQUEST["posto_nome"]);

		// DATE VALIDATION

	list($di, $mi, $yi) = explode("/", $data_inicial);
	if(!checkdate($mi,$di,$yi)) 
	    $msg_erro[] = "Data Incial Inválida";

	list($df, $mf, $yf) = explode("/", $data_final);
	if(!checkdate($mf,$df,$yf)) 
	    $msg_erro[] = "Data Final Inválida";

	$aux_data_inicial = "$yi-$mi-$di";
	$aux_data_final = "$yf-$mf-$df";

	if(strtotime($aux_data_final) < strtotime($aux_data_inicial) 
	or strtotime($aux_data_final) > strtotime('today')){
	    $msg_erro[] = "Data Final menor que a Data Inicial ou maior que hoje.";
	}

	if (count($msg_erro)>0) {
		$erros = implode('<br>', $msg_erro);
		echo "1|$erros";
	}else{
		echo "0|Ok";
	}

	// POSTO VALIDATION


    exit;
  
}

if (isset($_GET['action']) and $_GET['action'] == 'validarDadosPosto') {

	$posto                = $_GET['posto'];
	$posto_alteracao      = $_GET['posto_alteracao'];
	$razao_social         = $_GET[$posto.'_razao_social'];
	$nome_fantasia        = $_GET[$posto.'_nome_fantasia'];
	$email                = $_GET[$posto.'_email'];
	$cnpj                 = str_replace($charsToRemove,'',$_GET[$posto.'_cnpj']);
	$fone                 = str_replace($charsToRemove,'',$_GET[$posto.'_fone']);
	$contato              = $_GET[$posto.'_contato'];
	$endereco             = $_GET[$posto.'_endereco'];
	$numero               = $_GET[$posto.'_numero'];
	$complemento          = $_GET[$posto.'_complemento'];
	$cidade               = $_GET[$posto.'_cidade'];
	$estado               = $_GET[$posto.'_estado'];
	$cep                  = str_replace($charsToRemove,'',$_GET[$posto.'_cep']);
	$fabrica_credenciada  = $_GET[$posto.'_fabrica_credenciada'];
	$marca_ser_autorizada = $_GET[$posto.'_marca_ser_autorizada'];
	$outras_fabricas      = $_GET[$posto.'_outras_fabricas'];
	$observacao           = $_GET[$posto.'_observacao'];

	$res = pg_query($con,"BEGIN TRANSACTION");

	$sql = "UPDATE tbl_posto_alteracao SET 
			razao_social = '$razao_social' ,
			nome_fantasia = '$nome_fantasia',
			email = '$email',
			cnpj = '$cnpj',
			fone = '$fone',
			contato = '$contato',
			endereco = '$endereco',
			numero = '$numero',
			complemento = '$complemento',
			cidade = '$cidade',
			estado = '$estado',
			cep = '$cep',
			fabrica_credenciada = '$fabrica_credenciada',
			marca_ser_autorizada = '$marca_ser_autorizada',
			outras_fabricas = '$outras_fabricas',
			observacao = '$observacao',
			validado = true
			WHERE posto_alteracao = $posto_alteracao
			AND banner is true
			";

	$res = pg_query($con,$sql);

	if (pg_last_error($res)) {
		$msg_erro[] = "Erro ao validar o posto $razao_social";
		$erros = implode("\n", $msg_erro);
		echo "1|$erros";
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}else{
		$res = pg_query($con,"COMMIT TRANSACTION");
		echo "0|Ok";
	}

	exit;
}

if (isset($_GET['action']) and $_GET['action'] == 'marcaEnviado') {

	$posto_alteracao = $_GET['posto_alteracao'];

	$res = pg_query($con,"BEGIN TRANSACTION");

	$sql = "UPDATE tbl_posto_alteracao SET 
			banner_enviado = current_timestamp
			WHERE posto_alteracao = $posto_alteracao
			AND banner is true
			";

	$res = pg_query($con,$sql);

	if (pg_last_error($res)) {
		$msg_erro[] = "Erro ao atualizar o posto: $razao_social";
		$erros = implode("\n", $msg_erro);
		echo "1|$erros";
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}else{
		$data = date("d/m/Y");
		$res = pg_query($con,"COMMIT TRANSACTION");
		echo "0|$erros|$data";
	}

	exit;
}