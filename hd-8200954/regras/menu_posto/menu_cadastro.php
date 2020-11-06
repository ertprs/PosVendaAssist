<?php
/**
 * Define o menu cadastro
 **/
$tipoPA = json_decode($json_info_posto, true);

$showCadVenda = isFabrica(148) and (($tipoPA['locadora'] or $tipoPA['tipo_revenda']));
return array(
	'title' => traduz("menu.de.cadastramentos"),
	array(
		'disabled' => true,
		'icone'    => 'marca25.gif',
		'link'     => 'consumidor_cadastro.php',
		'titulo'   => traduz('cadastro.de.consumidores'),
		'descr'    => traduz('cadastro.de.consumidores')
	),
	array(
		'disabled' => !$showCadVenda,
		'icone'    => 'tela25.gif',
		'link'     => 'cadastro_venda.php',
		'titulo'   => traduz('cadastro.venda.de.produto'),
		'descr'    => traduz('cadastro.de.venda.de.produtos')
	),
	array(
		'disabled' => !$showCadVenda,
		'icone'    => 'tela25.gif',
		'link'     => 'consulta_venda.php',
		'titulo'   => traduz('consulta.de.venda.de.produto'),
		'descr'    => traduz('consulta.as.vendas.cadastradas')
	),
	array(
		'fabrica' => array(3,59,87,141,144,158,165,169,170,171,174,178,190,195,193,198,$mostra_cad_tecnico),
		'icone'   => 'tela25.gif',
		'link'    => 'tecnico_cadastro.php',
		'titulo'  => traduz('cadastro.de.tecnicos'),
		'descr'   => traduz('cadastro.de.tecnicos')
	),
	array(
		'fabrica' => array(141),
		'posto'   => array(376163),
		'icone'   => 'tela25.gif',
		'link'    => 'tecnico_cadastro.php',
		'titulo'  => traduz('cadastro.de.tecnicos'),
		'descr'   => traduz('cadastro.de.tecnicos')
	),
	array(
		'fabrica_no' => array(28, 87,24,184,191),
		'icone'      => 'tela25.gif',
		'link'       => 'revenda_cadastro.php',
		'titulo'     => traduz('cadastro.de.revendas'),
		'descr'      => traduz('cadastramento.de.revendas')
	),
	array(
		'fabrica' => array(20,158),
		'icone'   => 'tela25.gif',
		'link'    => 'cadastro_funcionario.php',
		'titulo'  => traduz('cadastro.de.funcionario'),
		'descr'   => traduz('cadastramento.de.funcionario')
	),
	array(
		'icone'  => 'tela25.gif',
		'link'   => 'consulta_lista_basica_produto.php',
		'titulo' => traduz('Consulta Lista Básica'),
		'descr'  => traduz('Consulta a Lista Básica de um Produto')
	),
	array(
		'disabled' => (in_array($login_fabrica, [175])),
		'icone'  => 'tela25.gif',
		'link'   => 'consulta_lista_basica_peca.php',
		'titulo' => traduz('Consulta Peças'),
		'descr'  => traduz('Consulta de peça que mostra a relação de produtos em que a peça consta na lista básica')
	),
	array(
		'icone'  => 'marca25.gif',
		'link'   => 'posto_cadastro_new.php',
		'titulo' => traduz('informacoes.do.posto'),
		'descr'  => traduz('altera.dados.e.senha.do.posto')
	),
	array(
		'fabrica'    => array(1),
		'icone'      => 'tela25.gif',
		'so_testes'  => in_array($login_fabrica, array(1)),
		'link'       => 'posto_cadastro_atualiza.php',
		'background' => '',
		'titulo'     => traduz('atualizacao.de.informacoes.do.posto.teste'),
		'descr'      => traduz('atualizacao.de.informacoes.do.posto.teste')
	),
	array(
		'icone'  => 'tela25.gif',
		'posto'  => 4311,
		'link'   => 'contas_pagar.php',
		'titulo' => traduz('contas.a.pagar'),
		'descr'  => traduz('contas.a.pagar')
	),
	array(
		'fabrica' => array(15,153),
		'icone'   => 'tela25.gif',
		'link'    => 'estoque_posto_cadastro.php',
		'titulo'  => traduz('estoque.do.posto'),
		'descr'   => traduz('descricao.estoque.do.posto')
	),
	array(
		'disabled' => true,
		'fabrica'  => array(45),
		'posto'    => 4311,
		'icone'    => 'tela25.gif',
		'link'     => 'nf_entrada_teste.php',
		'titulo'   => traduz('conferencia.de.nf.de.entrada'),
		'descr'    => traduz('conferencia.de.nf.de.entrada')
	),
	array(
		'disabled' => true,
		'fabrica'  => array(45),
		'posto'    => 4311,
		'icone'    => 'tela25.gif',
		'link'     => 'estoque_consulta.php',
		'titulo'   => traduz('estoque.de.pecas'),
		'descr'    => traduz('estoque.de.pecas')
	),
	array(
		'disabled' => true,
		'fabrica'  => array(45),
		'posto'    => 4311,
		'icone'    => 'tela25.gif',
		'link'     => 'peca_localizacao.php',
		'titulo'   => traduz('mudar.localizacao.de.pecas'),
		'descr'    => traduz('mudar.localizacao.de.pecas')
	),
	array(
		'fabrica' => array(175),
		'icone' => 'tela25.gif',
		'link' => 'ferramenta_cadastro.php',
		'titulo' => traduz('cadastro de ferramentas'),
		'descr' => traduz('cadastro de ferramentas')
	),
	array('link' => 'linha_de_separacao'),
);

