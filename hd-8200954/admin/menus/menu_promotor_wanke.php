<?php
/* Define os ítens do menu... 
 * HD 684194
 * - Consulta OS:		http://www.telecontrol.com.br/assist/admin/os_consulta_lite.php
 * - Consulta Pedidos:	http://www.telecontrol.com.br/assist/admin/pedido_parametros.php
 * - Abre Chamado:		http://www.telecontrol.com.br/assist/admin/callcenter_interativo_new.php
 * - Consulta Chamado:	http://www.telecontrol.com.br/assist/admin/callcenter_parametros_interativo.php
 * - Cadastrar pedido:	http://www.telecontrol.com.br/assist/admin/pedido_cadastro.php
 * - Consultar posto:	http://www.telecontrol.com.br/assist/admin/posto_consulta.php
 * - Vista Explodida e Comunicados (apenas visualizar, conforme esta na aba Call Center):
 * 						http://www.telecontrol.com.br/assist/admin/comunicado_produto_consulta.php
 */
return array(
	'secao0' => array(
		'secao' => array(
			'link'	=> '#',
			'titulo' => 'MENU DO PROMOTOR ' .  ucfirst($login_login),
			'admin'  => $admin_e_promotor_wanke
		),
		array (
			'fabrica'	=> array(91),
			"icone"		=> $icone["consulta"],
			"titulo"	=> 'Consulta de Ordens de Serviço',
			"link"		=> 'os_consulta_lite.php',
			"descr"		=> 'Consulta OS Lançadas',
			"codigo" => 'WNK-1010'
		),
		array (
			'fabrica'	=> array(91),
			"icone"		=> $icone["consulta"],
			"titulo"	=> 'Consulta Pedidos de Peças',
			"link"		=> 'pedido_parametros.php',
			"descr"		=> 'Consulta pedidos efetuados por postos autorizados.',
			"codigo" => 'WNK-1020'
		),
		array (
			"icone"		=> $icone["cadastro"],
			"titulo"	=> 'Cadastra Atendimento Call-Center',
			"link"		=> 'callcenter_interativo_new.php',
			"descr"		=> 'Cadastro de Atendimento no Call-Center',
			"codigo" => 'WNK-1030'
		),
		array (
			'fabrica'	=> array(91),
			"icone"		=> $icone["consulta"],
			"titulo"	=> 'Consulta Atendimentos Call-Center',
			"link"		=> 'callcenter_parametros_interativo.php',
			"descr"		=> 'Consulta atendimentos já lançados',
			"codigo" => 'WNK-1040'
		),
		array (
			'fabrica'	=> array(91),
			"icone"		=> $icone["cadastro"],
			"titulo"	=> 'Cadastro de Pedidos',
			"link"		=> 'pedido_cadastro.php',
			"descr"		=> 'Cadastra pedidos de peças',
			"codigo" => 'WNK-1050'
		),
		array (
			'fabrica'	=> array(91),
			"icone"		=> $icone["computador"],
			"titulo"	=> 'Consulta Postos',
			"link"		=> 'posto_consulta.php',
			"descr"		=> 'Consulta cadastro de postos autorizados.',
			"codigo" => 'WNK-1060'
		),
		array (
			'fabrica'	=> array(91),
			"icone"		=> $icone["consulta"],
			"titulo"	=> 'Vista Explodida e Comunicados',
			"link"		=> 'comunicado_produto_consulta.php',
			"descr"		=> 'Consulta vista explodida, diagramas, esquemas e comunicados.',
			"codigo" => 'WNK-1070'
		),
	),
);

