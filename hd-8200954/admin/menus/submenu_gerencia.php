<?php
include_once 'funcoes.php';
return array(
	array(
		'fabrica' => 87,
		'link'	  => 'void()',
		'descr'   => traduz('Consulta Ordens de Serviço'),
		'titulo'  => traduz('Consulta OS'),
		'attr'    => array("style='cursor:not-allowed'",
						   "class=submenu_telecontrol")
	),
	array(
		'fabrica' => 87,
		'link'	  => 'void()',
		'descr'   => traduz('Relatório de quebra de produtos'),
		'titulo'  => traduz('Field-Call Rate'),
		'attr'    => array("style='cursor:not-allowed'",
						   "class=submenu_telecontrol")
	),
	array(
		'fabrica_no'=> 87,
		'link'		=> BI_BACK.'os_consulta_lite.php',
		'descr'		=> traduz('Consulta Ordens de Serviço'),
		'titulo'	=> traduz('Consulta OS'),
		'attr'		=> ' class="submenu_telecontrol"'
	),
	array(
		'fabrica_no'=> 87,
		'link'		=> BI_BACK.'pedido_parametros.php',
		'descr'		=> traduz('Consulta pedidos de peças'),
		'titulo'	=> traduz('Consulta Pedidos'),
		'attr'		=> ' class="submenu_telecontrol"'
	),
	array(
		'fabrica_no'=> 87,
		'link'		=> BI_BACK.'relatorio_field_call_rate_produto.php',
		'descr'		=> traduz('Relatório de quebra de produtos'),
		'titulo'	=> traduz('Field-Call Rate'),
		'attr'		=> ' class="submenu_telecontrol"'
	),

);

