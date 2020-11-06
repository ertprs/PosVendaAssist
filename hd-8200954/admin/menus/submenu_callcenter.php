<?php

include_once 'funcoes.php';

return array(
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Consulta Ordens de Serviço'),
		'titulo'	=> traduz('Consulta OS'),
		'attr'    => array("style='cursor:not-allowed'",
						   "class='submenu_telecontrol submenu_telecontrol_callcenter'")
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Abre um novo chamado no Callcenter'),
		'titulo'	=> traduz('Abre Chamado'),
		'attr'    => array("style='cursor:not-allowed'",
						   "class='submenu_telecontrol submenu_telecontrol_callcenter'")
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Consulta chamados registrados no Callcenter'),
		'titulo'	=> traduz('Consulta Chamado'),
		'attr'    => array("style='cursor:not-allowed'",
						   "class='submenu_telecontrol submenu_telecontrol_callcenter'")
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Cadastra uma nova ordem de serviço'),
		'titulo'	=> traduz('Abre OS'),
		'attr'    => array("style='cursor:not-allowed'",
						   "class='submenu_telecontrol submenu_telecontrol_callcenter'")
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Cadastra um novo pedido de peças'),
		'titulo'	=> traduz('Pedidos'),
		'attr'    => array("style='cursor:not-allowed'",
						   "class='submenu_telecontrol submenu_telecontrol_callcenter'")
	),
	array(
		'fabrica'	=> 87,
		'link'		=> 'void()',
		'descr'		=> traduz('Consulta dos postos autorizados'),
		'titulo'	=> traduz('Postos'),
		'attr'    => array("style='cursor:not-allowed'",
						   "class='submenu_telecontrol submenu_telecontrol_callcenter'")
	),
	array( // HD 38922
		'fabrica_no'=> array(14, 66, 87),
		'link'		=> ($login_fabrica==6)?'cadastra_callcenter.php':'callcenter_interativo_new.php',
		'descr'		=> traduz('Abre um novo chamado no Callcenter'),
		'titulo'	=> traduz('Abre Chamado'),
		'attr'		=> ' class="submenu_telecontrol submenu_telecontrol_callcenter"'
	),
	array(
		'fabrica_no'=> array(14, 66, 87),
		'link'		=> 'callcenter_parametros_new.php',
		'descr'		=> traduz('Consulta chamados registrados no Callcenter'),
		'titulo'	=> traduz('Consulta Chamado'),
		'attr'		=> ' class="submenu_telecontrol submenu_telecontrol_callcenter"'
	),
	array(
		'fabrica_no'=> array(14, 66, 87,189),
		'link'		=> 'os_consulta_lite.php',
		'descr'		=> traduz('Consulta Ordens de Serviço'),
		'titulo'	=> traduz('Consulta OS'),
		'attr'		=> ' class="submenu_telecontrol submenu_telecontrol_callcenter"'
	),
	array(
		'fabrica_no'=> array_merge(array(14, 52, 66, 87,189), (isset($novaTelaOs) ? array($login_fabrica) : array())),
		'link'		=> 'os_cadastro.php',
		'descr'		=> traduz('Cadastra uma nova ordem de serviço'),
		'titulo'	=> traduz('Abre OS'),
		'attr'		=> ' class="submenu_telecontrol submenu_telecontrol_callcenter"'
	),
	array(
		'fabrica_no'=> array(189),
		'fabrica' => ((isset($novaTelaOs) ) ? array($login_fabrica) : array()),
		'link'    => 'cadastro_os.php',
		'descr'   => traduz('Cadastra uma nova ordem de serviço'),
		'titulo'  => traduz('Abre OS'),
		'attr'    => ' class="submenu_telecontrol submenu_telecontrol_callcenter"'
	),
	array(
		'fabrica_no'=> array(14, 66, 87),
		'link'		=> 'pedido_parametros.php',
		'descr'		=> ($login_fabrica==1)?traduz('Consulta pedidos de peças / produtos'):traduz('Consulta pedidos de peças'),
		'titulo'	=> traduz('Consulta Pedidos'),
		'attr'		=> ' class="submenu_telecontrol submenu_telecontrol_callcenter"'
	),
	array(
		'fabrica_no'=> array(14, 66, 87,148,152,180,181,182),
		'link'		=> 'pedido_cadastro.php',
		'descr'		=> traduz('Cadastra um novo pedido de peças'),
		'titulo'	=> traduz('Pedidos'),
		'attr'		=> ' class="submenu_telecontrol submenu_telecontrol_callcenter"'
	),
	array(
		'fabrica'=> array(148),
		'link'		=> 'http://fvweb.yanmar.com.br/',
		'descr'		=> traduz('Cadastra um novo pedido de peças'),
		'titulo'	=> traduz('Pedidos'),
		'attr'		=> ' class="submenu_telecontrol submenu_telecontrol_callcenter" ',
		"blank" => true
	),
	array(
		'fabrica_no'=> array(14, 66, 87),
		'link'		=> 'posto_consulta.php',
		'descr'		=> traduz('Consulta dos postos autorizados'),
		'titulo'	=> traduz('Postos'),
		'attr'		=> ' class="submenu_telecontrol submenu_telecontrol_callcenter"'
	),
);

