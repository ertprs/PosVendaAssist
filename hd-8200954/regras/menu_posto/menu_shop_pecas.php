<?php
// Define o menu SHOP PEÇAS
return array(
	'title' => traduz('menu.de.compra.e.venda.de.pecas.entre.postos'),
	array (
		'fabrica' => array(20),
		'icone'   => 'marca25.gif',
		'link'    => 'shop_pecas_cadastro.php',
		'titulo'  => traduz('cadastro.de.pecas.para.venda'),
		'descr'   => traduz('clique.aqui.para.inserir.uma.nova.peca')
	),
	array (
		'fabrica' => array(20),
		'icone'   => 'marca25.gif',
		'link'    => 'shop_pecas_consulta.php',
		'titulo'  => traduz('compra.e.venda.de.pecas'),
		'descr'   => traduz('clique.aqui.para.comprar.ou.vender.uma.peca')
	),
	array (
		'fabrica' => array(20),
		'icone'   => 'marca25.gif',
		'link'    => 'shop_pecas_acompanha.php',
		'titulo'  => traduz('acompanhamento.de.compra.e.venda'),
		'descr'   => traduz('clique.aqui.para.acompanhar.a.compra.ou.venda.das.pecas')
	),
);

