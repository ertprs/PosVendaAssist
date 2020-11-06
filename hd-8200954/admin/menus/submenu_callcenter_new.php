<?php
include_once 'funcoes.php';

$os_cadastro_new = ((isset($novaTelaOs) OR in_array($login_fabrica, array(52))) ? array($login_fabrica) : array());


return array(
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Consulta Ordens de Serviço'),
		'titulo'	=> traduz('Consulta OS'),
		'attr'    => "style='cursor:not-allowed'"
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Abre um novo chamado no Callcenter'),
		'titulo'	=> traduz('Abre Chamado'),
		'attr'    => "style='cursor:not-allowed'"
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Consulta chamados registrados no Callcenter'),
		'titulo'	=> traduz('Consulta Chamado'),
		'attr'    => "style='cursor:not-allowed'"
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Cadastra uma nova ordem de serviço'),
		'titulo'	=> traduz('Abre OS'),
		'attr'    => "style='cursor:not-allowed'"
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Cadastra um novo pedido de peças'),
		'titulo'	=> traduz('Pedidos'),
		'attr'    => "style='cursor:not-allowed'"
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Consulta dos postos autorizados'),
		'titulo'	=> traduz('Postos'),
		'attr'    => "style='cursor:not-allowed'"
	),
	array( // HD 38922
		'fabrica_no'=> array(14, 66, 87),
		'link'		=> ($login_fabrica==6)?'cadastra_callcenter.php':'callcenter_interativo_new.php',
		'descr'		=> traduz('Abre um novo chamado no Callcenter'),
		'titulo'	=> traduz('Abre Chamado')
	),
	array(
		'fabrica_no'=> array(14, 66, 87),
		'link'		=> 'callcenter_parametros_new.php',
		'descr'		=> traduz('Consulta chamados registrados no Callcenter'),
		'titulo'	=> traduz('Consulta Chamado')
	),
	array(
		'fabrica_no'=> array(14, 66, 87,189),
		'link'		=> 'os_consulta_lite.php',
		'descr'		=> traduz('Consulta Ordens de Serviço'),
		'titulo'	=> traduz('Consulta OS')
	),
	array(
		'fabrica_no'=> array(14, 66, 87 ,178,189),
		'link'		=> (in_array($login_fabrica, $os_cadastro_new)) ? 'cadastro_os.php' : 'os_cadastro.php',
		'descr'		=> traduz('Cadastra uma nova ordem de serviço'),
		'titulo'	=> traduz('Abre OS')
	),
	array(
		'fabrica'=> array(178),
		'link'		=> 'cadastro_os_revenda.php',
		'descr'		=> traduz('Cadastra uma nova ordem de serviço'),
		'titulo'	=> traduz('Abre OS')
	),
	array(
		'fabrica_no'=> array(14, 66, 87),
		'link'		=> 'pedido_parametros.php',
		'descr'		=> ($login_fabrica==1)?'Consulta pedidos de peças / produtos': traduz('Consulta pedidos de peças'),
		'titulo'	=> traduz('Consulta Pedidos')
	),
	array(
		'fabrica_no'=> array(14,66,87,148,152,180,181,182,189),
		'link'		=> 'pedido_cadastro.php',
		'descr'		=> traduz('Cadastra um novo pedido de peças'),
		'titulo'	=> traduz('Pedidos')
	),
	array(
		'fabrica'=> array(148),
		'link'		=> 'http://fvweb.yanmar.com.br/',
		'descr'		=> traduz('Cadastra um novo pedido de peças'),
		'titulo'	=> traduz('Pedidos'),
		"blank" => true
	),
	array(
		'fabrica'=> array(1),
		'link'		=> 'consulta_pergunta_tecnica.php',
		'descr'		=> traduz('Consulta de Dúvidas Tecnicas'),
		'titulo'	=> traduz('Dúvidas Tecnicas')
	),
	array(
		'fabrica_no'=> array(14, 66, 87),
		'link'		=> 'posto_consulta.php',
		'descr'		=> ($login_fabrica == 189) ? traduz('Consulta dos Representantes/Revendas') : traduz('Consulta dos postos autorizados'),
		'titulo'	=> ($login_fabrica == 189) ? traduz('Representantes/Revendas') : traduz('Postos')
	),
);

