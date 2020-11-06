<?php
$formulario_interacao   = true;
$envia_comunicado_posto = false;

// HD 4073340 - Mallory
// Fabricante determina qual usuário admin atende as interações
// de um posto determinado utilizando o campo `admin_sap`.
$fabrica_interacao_admin_sap = in_array($login_fabrica, [72]);

if ($areaAdmin === true) {
	$inputs_interacao = array(
		"interacao_interna",
		"interacao_email",
		"interacao_transferir"
	);

	if ($login_fabrica == 148) {
        $inputs_interacao = array(
           "interacao_interna",
           "interacao_email",
           "interacao_transferir",
           "parecer_final"
        );              
    }
} else {
	$posto_legendas = array(
		"recebeu_email"
	);
}

$insertInteracao = "insertInteracao";
