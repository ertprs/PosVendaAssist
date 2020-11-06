<?php
include_once 'funcoes.php';
if($login_fabrica != 162) {
	return array(
		array(
			'fabrica' => 87,
			'link'	  => 'void()',
			'descr'   => traduz('Consulta Ordens de Serviço'),
			'titulo'  => traduz('Consulta OS'),
			'attr'    => "style='cursor:not-allowed'"
		),
		array(
			'fabrica' => 87,
			'link'	  => 'void()',
			'descr'   => traduz('Relatório de quebra de produtos'),
			'titulo'  => traduz('Field-Call Rate - Produtos'),
			'attr'    => "style='cursor:not-allowed'"
		),
		array(
			'fabrica_no'=> 87,
			'link'		=> 'os_consulta_lite.php',
			'descr'		=> traduz('Consulta Ordens de Serviço'),
			'titulo'	=> traduz('Consulta OS'),
		),
		array(
			'fabrica_no'=> 87,
			'link'		=> 'pedido_parametros.php',
			'descr'		=> traduz('Consulta pedidos de peças'),
			'titulo'	=> traduz('Consulta Pedidos'),
		),
		array(
			'fabrica_no'=> 87,
			'link'		=> 'relatorio_field_call_rate_produto.php',
			'descr'		=> traduz('Relatório de quebra de produtos'),
			'titulo'	=> traduz('Field-Call Rate'),
		),

	);
}
