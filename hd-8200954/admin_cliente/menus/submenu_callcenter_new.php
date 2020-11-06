<?php

$os_cadastro_new = ((isset($novaTelaOs) OR in_array($login_fabrica, array(52))) ? array($login_fabrica) : array());


return array(
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> 'Consulta Ordens de Serviço',
		'titulo'	=> 'Consulta OS',
		'attr'    => "style='cursor:not-allowed'"
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> 'Abre um novo chamado no Callcenter',
		'titulo'	=> 'Abre Chamado',
		'attr'    => "style='cursor:not-allowed'"
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> 'Consulta chamados registrados no Callcenter',
		'titulo'	=> 'Consulta Chamado',
		'attr'    => "style='cursor:not-allowed'"
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> 'Cadastra uma nova ordem de serviço',
		'titulo'	=> 'Abre OS',
		'attr'    => "style='cursor:not-allowed'"
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> 'Cadastra um novo pedido de peças',
		'titulo'	=> 'Pedidos',
		'attr'    => "style='cursor:not-allowed'"
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> 'Consulta dos postos autorizados',
		'titulo'	=> 'Postos',
		'attr'    => "style='cursor:not-allowed'"
	),
	array( // HD 38922
		'fabrica_no'=> array(14, 66, 87,158, 191),
		'link'		=> ($login_fabrica==6)?'cadastra_callcenter.php':'callcenter_interativo_new.php',
		'descr'		=> 'Abre um novo chamado no Callcenter',
		'titulo'	=> 'Abre Chamado'
	),
	array(
		'fabrica_no'=> array(14, 66, 87,158, 191),
		'link'		=> 'callcenter_parametros_new.php',
		'descr'		=> 'Consulta chamados registrados no Callcenter',
		'titulo'	=> 'Consulta Chamado'
	),
	array(
		'fabrica_no'=> array(14, 66, 87, 191),
		'link'		=> 'os_consulta_lite.php',
		'descr'		=> 'Consulta Ordens de Serviço',
		'titulo'	=> 'Consulta OS'
	),
	array(
		'fabrica_no'=> array(14, 66, 87,158, 191),
		'link'		=> (in_array($login_fabrica, $os_cadastro_new)) ? 'cadastro_os.php' : 'os_cadastro.php',
		'descr'		=> 'Cadastra uma nova ordem de serviço',
		'titulo'	=> 'Abre OS'
	),
	array(
		'fabrica_no'=> array(14, 66, 87,158,191),
		'link'		=> 'pedido_parametros.php',
		'descr'		=> ($login_fabrica==1)?'Consulta pedidos de peças / produtos':'Consulta pedidos de peças',
		'titulo'	=> 'Consulta Pedidos'
	),
	array(
		'fabrica_no'=> array(14, 66, 87, 148,152,158,191),
		'link'		=> 'pedido_cadastro.php',
		'descr'		=> 'Cadastra um novo pedido de peças',
		'titulo'	=> 'Pedidos'
	),
	array(
		'fabrica'=> array(148),
		'link'		=> 'http://fvweb.yanmar.com.br/',
		'descr'		=> 'Cadastra um novo pedido de peças',
		'titulo'	=> 'Pedidos',
		"blank" => true
	),
	array(
		'fabrica'=> array(1),
		'link'		=> 'consulta_pergunta_tecnica.php',
		'descr'		=> 'Consulta de Dúvidas Tecnicas',
		'titulo'	=> 'Dúvidas Tecnicas'
	),
	array(
		'fabrica_no'=> array(14, 66, 87,158,191),
		'link'		=> 'posto_consulta.php',
		'descr'		=> 'Consulta dos postos autorizados',
		'titulo'	=> 'Postos'
	),
);

