<?php

$buscar_atendente = "buscarAtendenteFrigelar";

$regras = array(
	"tipo_solicitacao" => array(
		"obrigatorio" => true
	),
	"tipo_solicitacao" => array(
		"function" => 'valida_tipo_solicitacao'
	)
);


$attCfg = array(
	'labels' => array('Anexar'),
	'obrigatorio' => array(0)
);

$fabrica_qtde_anexos = count($attCfg['labels']);
$GLOBALS['attCfg'] = $attCfg;

function buscarAtendenteFrigelar() {
	global $login_admin;

	return (!empty($login_admin)) ? $login_admin : false;
}