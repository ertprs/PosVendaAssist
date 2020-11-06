<?php
$modulos = array(
	'pecas'    => array(
		'displayStr'  => 'Pe�as',
		'key_field'   => 'referencia',
		'RESTallowed' => array('PUT', 'POST', 'DELETE'),
		'PUTifExists' => false,
		'POSTnotGET'  => false,
		//'disabled'    => true,
	),
	'produtos' => array(
		'displayStr'  => 'Produtos',
		'key_field'   => 'referencia',
		'RESTallowed' => array('PUT', 'POST'),
		'PUTifExists' => false,
		'POSTnotGET'  => true,
		//'disabled'    => true,
	),
	'postos' => array(
		'displayStr'  => 'Postos Autorizados',
		'key_field'   => 'cnpj',
		'RESTallowed' => array('PUT', 'POST'),
		'PUTifExists' => false,
		'POSTnotGET'  => false,
		'disabled'    => false,
	),
	'familias' => array(
		'displayStr'  => 'Familias de Produtos',
		'key_field'   => 'codigo',
		'RESTallowed' => array('PUT', 'POST', 'DELETE'),
		'PUTifExists' => true,
		'POSTnotGET'  => false,
		//'disabled'    => true,
	),
	'listas_basicas' => array(
		'displayStr'  => 'Lista B�sica de Materiais',
		'key_field'   => 'lista_basica',
		'RESTallowed' => array('POST', 'DELETE'),
		'PUTifExists' => true,
		'POSTnotGET'  => false,
		//'disabled'    => true,
	),	
	'tabela_preco' => array(
		'displayStr'  => 'Tabelas de Pre�os',
		'key_field'   => 'tabela',
		'RESTallowed' => array('POST', 'DELETE'),
		'PUTifExists' => true,
		'POSTnotGET'  => false,
		'disabled'    => false,
	),
);

$RestActionsHints = array(
	'GET'     => array('hint'=>"Consulta de dados, listagem ou de um registro"),
	'POST'    => array('hint'=>"Inserir novo(s) registro(s)"),
	'PUT'     => array('hint'=>"Atualizar dados do(s) registro(s)"),
	'DELETE'  => array('hint'=>"Excluir (ou desativar) registro(s)"),
	'HEAD'    => array('hint'=>"Consultar um registro (n�o usado)"),
	'OPTIONS' => array('hint'=>"Informa��es sobre as op��es de comunica��o dispon�veis"),
);
