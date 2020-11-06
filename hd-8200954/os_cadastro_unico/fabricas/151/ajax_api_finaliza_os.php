<?php
/**
 * Módulo para finalizar OS de troca desde a API de Faturamento.
 * Quando à troca de produto, ao gravar o faturamento, se a OS foi
 * atendida, a OS deve ser fechada, chamando a este programa.
 * Deve receber POST com o Token, ambiente e código (ID) da fábrica
 * para "validação" e a OS a ser finalizada.
 *
 * Retorna apenas "true" ou "false" (string), sem código HTTP (201 ou
 * 400), apenas a string.
 **/
require_once '../../../dbconfig.php';
require_once '../../../includes/dbconnect-inc.php';

// valida conteúdo
$login_fabrica = $_POST['client_code'];
$client_token  = $_POST['token'];
$environment   = $_POST['environment'];
$os            = $_POST['os'];

// Valida conteúdo
if ($login_fabrica != 151) {
	echo "falha na autenticação";
	exit;
}

if (empty($os)) {
	echo "OS não informada";
	exit;
}

if (empty($token)) {
	echo "Token não informada";
	exit;
}

if (empty($environment)) {
	echo "Ambiente não informado";
	exit;
}

// Valida o token com a fábrica
$token_field = 'api_secret_key_' .
	($environment === 'production' ? $environment : 'tests');

$check_token = pg_num_rows(
	pg_query(
		$con,
		"SELECT fabrica
		   FROM tbl_fabrica
		  WHERE fabrica = 151
		    AND $token_field = '$client_token'"
	)
);

if ($check_token == 0) {
	echo "Falha na autenticação";
	exit;
}

// Valida OS que seja da fábrica
$check_os = pg_num_rows(
	pg_query(
		$con,
		"SELECT os FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os"
	)
);

if ($check_os == 0) {
	echo "OS não encontrada";
	exit;
}

// Finaliza:
try {
	require '../../../classes/Posvenda/Os.php';
	$classOs = new \Posvenda\Os($login_fabrica, $os);
	$classOs->calculaOs();
    $classOs->finaliza($con, true); // 2º parâmetro TRUE para fechar sem validar defeito, solução e faturamento

    echo "true";
	exit;
} catch(Exception $e) {
	echo $e->getMessage();
	exit;
}