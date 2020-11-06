<?php
/**
 * M�dulo para finalizar OS de troca desde a API de Faturamento.
 * Quando � troca de produto, ao gravar o faturamento, se a OS foi
 * atendida, a OS deve ser fechada, chamando a este programa.
 * Deve receber POST com o Token, ambiente e c�digo (ID) da f�brica
 * para "valida��o" e a OS a ser finalizada.
 *
 * Retorna apenas "true" ou "false" (string), sem c�digo HTTP (201 ou
 * 400), apenas a string.
 **/
require_once '../../../dbconfig.php';
require_once '../../../includes/dbconnect-inc.php';

// valida conte�do
$login_fabrica = $_POST['client_code'];
$client_token  = $_POST['token'];
$environment   = $_POST['environment'];
$os            = $_POST['os'];

// Valida conte�do
if ($login_fabrica != 151) {
	echo "falha na autentica��o";
	exit;
}

if (empty($os)) {
	echo "OS n�o informada";
	exit;
}

if (empty($token)) {
	echo "Token n�o informada";
	exit;
}

if (empty($environment)) {
	echo "Ambiente n�o informado";
	exit;
}

// Valida o token com a f�brica
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
	echo "Falha na autentica��o";
	exit;
}

// Valida OS que seja da f�brica
$check_os = pg_num_rows(
	pg_query(
		$con,
		"SELECT os FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os"
	)
);

if ($check_os == 0) {
	echo "OS n�o encontrada";
	exit;
}

// Finaliza:
try {
	require '../../../classes/Posvenda/Os.php';
	$classOs = new \Posvenda\Os($login_fabrica, $os);
	$classOs->calculaOs();
    $classOs->finaliza($con, true); // 2� par�metro TRUE para fechar sem validar defeito, solu��o e faturamento

    echo "true";
	exit;
} catch(Exception $e) {
	echo $e->getMessage();
	exit;
}