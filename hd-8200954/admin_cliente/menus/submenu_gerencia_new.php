<?php
if($login_fabrica != 162) {
	return array(
		array(
			'fabrica' => 87,
			'link'	  => 'void()',
			'descr'   => 'Consulta Ordens de Servi�o',
			'titulo'  => 'Consulta OS',
			'attr'    => "style='cursor:not-allowed'"
		),
		array(
			'fabrica' => 87,
			'link'	  => 'void()',
			'descr'   => 'Relat�rio de quebra de produtos',
			'titulo'  => 'Field-Call Rate - Produtos',
			'attr'    => "style='cursor:not-allowed'"
		),
		array(
			'fabrica_no'=> [87,158,167],
			'link'		=> 'os_consulta_lite.php',
			'descr'		=> 'Consulta Ordens de Servi�o',
			'titulo'	=> 'Consulta OS',
		),
		array(
			'fabrica_no'=> [87,158],
			'link'		=> 'pedido_parametros.php',
			'descr'		=> 'Consulta pedidos de pe�as',
			'titulo'	=> 'Consulta Pedidos',
		),
		array(
			'fabrica_no'=> [87,158,167],
			'link'		=> 'relatorio_field_call_rate_produto.php',
			'descr'		=> 'Relat�rio de quebra de produtos',
			'titulo'	=> 'Field-Call Rate',
		),
		array(
			'fabrica'=> [158],
			'link'		=> 'fcr_os.php',
			'descr'		=> 'Percentual de quebra de produtos.',
			'titulo'	=> 'Field-Call Rate Produtos',
		),
		array(
			'fabrica'=> [158],
			'link'		=> 'fcr_pecas.php',
			'descr'		=> 'Percentual de quebra de pe�as.',
			'titulo'	=> 'Field-Call Rate Pe�as',
		),
		array(
			'fabrica'=> [158],
			'link'		=> 'indicadores_eficiencia_volume.php',
			'descr'		=> 'Indicadores SLA/Reincid�ncia',
			'titulo'	=> 'SLA/Reincid�ncia',
		),
	);
}
