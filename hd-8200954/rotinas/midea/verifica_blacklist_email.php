<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

define("URL_API", "http://api2.telecontrol.com.br/communicator/emailBlackList");
define("FABRICA", 169);

print "Iniciando verificação de emails na blacklist para a fabrica 169 (Midea)\n";

// Seleciona todos os email para verificação
$sql = "SELECT posto_fabrica, contato_email FROM tbl_posto_fabrica WHERE fabrica = ". FABRICA ." AND credenciamento = 'CREDENCIADO'";
$stmt = $pdo->query($sql);

if ($stmt === FALSE) {
	print "Falha ao selecionar emails da fabrica\n";
	return;
}

$listaDeEmail = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($listaDeEmail)) {
	print "Nenhum email encontrado\n";
	return;
}

// Configurações da requisição
$context = stream_context_create([
	"http" => [
		"method" => "GET",
		'timeout' => 30,
		'protocol_version' => 1.1,
		'ignore_errors' => true,
		'max_redirects' => 30,
		'header' => [
			"access-application-key: 701c59e0eb73d5ffe533183b253384bd52cd6973",
			"access-env: PRODUCTION",
			"cache-control: no-cache",
			"Content-Type: application/json"
		]
	]
]);

print "Iniciando requisições blacklist...\n";

$listaDeEmailBlackList = [];
foreach ($listaDeEmail as $email) {
	$response = file_get_contents(URL_API . "?email={$email['contato_email']}", 0, $context);

	$response = json_decode($response, true);
	if (isset($response['exception']) OR empty($response['email'])) {
		continue;
	}

	print "Email encontrado: {$response['email']}\n";
	$listaDeEmailBlackList[$email['posto_fabrica']] = $email['contato_email'];
}

// Altera o status do campo de blacklist no parametros_adicionais
$stmtPrepare = $pdo->prepare("SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto_fabrica = ?");
foreach ($listaDeEmailBlackList as $postoFabrica => $email) {
	$stmtPrepare->bindValue(1, $postoFabrica);

	if (!$stmtPrepare->execute() OR $stmtPrepare->rowCount() == 0) {
		print "Falha ao selecionar registro {$postoFabrica}\n";
		continue;
	}

	$parametrosAdicionais = $stmtPrepare->fetch()['parametros_adicionais'];

	if ( empty($parametrosAdicionais) ) {
		$data = [];
		$data['blacklist'] = true;
		$dataEncoded = json_encode($data);

		if( $dataEncoded === FALSE ){
			print "Falha ao codificar JSON (Formato inválido)\n";
			continue;
		}

		$sql = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '{$dataEncoded}' WHERE posto_fabrica = {$postoFabrica}";
		$stmt = $pdo->query($sql);

		if ($stmt === FALSE OR $stmt->rowCount() == 0) {
			print "Falha ao atualizar o campo parametros_adicionais do registro: {$postoFabrica} \n";
		}
		
		print "Email bloqueado com sucesso: {$email}\n";
		continue;
	}

	$parametrosAdicionais = json_decode($parametrosAdicionais, true);
	if ($parametrosAdicionais === FALSE) {
		print "Falha ao decodificar JSON (Formato inválido)\n";
		continue;
	}

	if ($parametrosAdicionais['blacklist'] === true) {
		print "Email já estava bloqueado: {$email}\n";
		continue;
	}

	$parametrosAdicionais['blacklist'] = true;
	$parametrosAdicionais = json_encode($parametrosAdicionais);

	if ($parametrosAdicionais === FALSE) {
		print "Falha ao codificar JSON (Formato inválido)\n";
		continue;
	}

	$sql = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '{$parametrosAdicionais}' WHERE posto_fabrica = {$postoFabrica}";
	$stmt = $pdo->query($sql);

	if ($stmt === FALSE OR $stmt->rowCount() == 0) {
		print "Falha ao atualizar o campo parametros_adicionais do registro: {$postoFabrica} \n";
		continue;
	}

	print "Email bloqueado com sucesso: {$email}\n";
}
