<?php
if (!isFabrica(1)) {
	$tipos_posto = json_decode($json_info_posto, true);
	$distr       = array_filter(array_column($tipo_posto, 'distribuidor'));
	if (count($distr)) {
		$dist_link  = 'pedido_posto_relacao.php';
		$dist_title = 'consulta.de.pedidos.dos.postos';
		$dist_descr = 'consulta.os.pedidos.de.pecas.que.os.postos.de.sua.regiao.fizeram.para.voce';
	}

	// Apenas para postos que são atendidos por distribuidor e que não seja
	// Telecontrol (4311)
	if (isFabrica(3)) {
		if (count($distr) and !in_array(4311, $distr)) {
			$dist_link  = 'nf_relacao_britania_distribuidor.php';
			$dist_title = 'consulta.notas.fiscais.do.distribuidor';
			$dist_descr = 'consulta.nfs.emitidas.pelo.distribuidor';
		}
	}
}

// Mostra opção 'Posição financeira Telecontrol'
$mostra_posicao_financeira = isFabrica(51, 81, 114)
	or !($login_posto_estado == 'SP' and $login_fabrica == 3);

$login_pede_peca_garantia = $login_pede_peca_garantia == 't';

$nao_consulta_notas_fiscais = array(1);

if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
	$nao_consulta_notas_fiscais = array(183);
}

if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
	$nao_consulta_tabela_precos = array(183);
}

if($login_fabrica == 104){
	$nao_consulta_tabela_precos[] = 104;
}


// Começa o array do Menu:
return array(
    array(
		'disabled' => !$login_pede_peca_garantia,
		'fabrica'   => array(1,93),
		'icone'     => 'marca25.gif',
		'titulo'    => traduz('pedido.de.pecas.em.garantia.dewalt.rental'),
		'link'      => array(
			 1 => "pedido_blackedecker_cadastro_garantia.php",
			93 => "pedido_blacktest_cadastro_garantia.php"
		),
		'descr'     => traduz('insira.seu.pedido.em.garantia.para.compra.de.pecas')
	),
	array(
		'fabrica'   => array(148),
		'icone'     => 'marca25.gif',
		'link'      => 'http://fvweb.yanmar.com.br/',
		'titulo'    => traduz('cadastro.de.pedidos'),
		'descr'     => traduz('insira.seu.pedido.para.compra.de.pecas'),
		"blank"     => true
	),
	array(
		'disabled'  => ($LU_compra_peca === false),
		'fabrica_no'=> array(14,15,152),
		'icone'     => 'marca25.gif',
		# solicitado por Adriana - Fabio 31/08/2007 HD3637
		'link'      => 'pedido_cadastro.php',
		'titulo'    => isFabrica(1, 87)
			?  traduz('pedido.de.pecas')
			: traduz('cadastro.de.pedidos'),
		'descr'     => traduz('insira.seu.pedido.para.compra.de.pecas')
	),
	array(
		'icone'     => 'tela25.gif',
		'link'      => ($login_fabrica == 183) ? 'pedido_relacao_new.php' : 'pedido_relacao.php',
		'titulo'    => isFabrica(1)
			? traduz('consulta.de.pedidos')
			: traduz('consulta.de.pedidos.de.pecas'),
		'descr'     => isFabrica(87)
			? traduz('consulte.seus.pedidos.de.compra')
			: traduz('consulte.seus.pedidos.de.compra.garantia.de.pecas.a.fabrica')
	),
	array(
		'fabrica'   => array(3, 24, 35),
		'icone'     => 'tela25.gif',
		'link'      => 'peca_consulta_dados.php',
		'titulo'    => traduz('consulta.dados.da.peca', $con),
		'descr'     => traduz('consulta.os.dados.cadastrais.da.peca', $con)
	),
	array(
		'fabrica'   => array(1),
		'icone'     => 'marca25.gif',
		'link'      => 'pedido_blackedecker_cadastro_acessorio.php',
		'titulo'    => traduz('Pedido Acessórios/Ferramentas Manuais', $con),
		'descr'     => traduz('insira.seu.pedido.de.acessorio', $con)
	),
	array(
		'fabrica'   => array(1),
		'icone'     => 'tela25.gif',
		'link'      => 'pedido_relacao_blackedecker_acessorio.php',
		'titulo'    => traduz('Consulta Acessórios/Ferramentas Manuais', $con),
		'descr'     => traduz('consulte.seus.pedidos.de.acessorios', $con)
	),
	array(
		'fabrica_no'=> ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep")) ? array(183) : null),
		'icone'     => 'tela25.gif',
		'link'      => 'consulta_pecas_pedido_pendente.php',
		'titulo'    => traduz('consulta.pecas.pendente', $con),
		'descr'     => traduz('consulte.suas.pecas.pendentes', $con)
	),
	array(
		'fabrica_no'=> $nao_consulta_notas_fiscais,
		'icone'     => 'tela25.gif',
		'link'      => array(
			  3 => 'nf_relacao_britania.php',
			'default' => 'nf_relacao.php',
		),
		'titulo'    => traduz('consulta.notas.fiscais', $con),
		'descr'     => traduz('consulta.nfs.emitidas.pelo.fabricante', $con)
	),
	array(
		'fabrica'   => array(42),
		'icone'     => 'tela25.gif',
		'link'      => 'consulta_boletos.php',
		'titulo'    => traduz('consulta.de.boletos', $con),
		'descr'     => traduz('Consulta 2º via de boletos de pedidos', $con)
	),
	array(
		'fabrica'   => array(6, 45),
		'icone'     => 'tela25.gif',
		'link'      => isFabrica(45) ? 'lbm_consulta.php':'lista_basica_consulta.php',
		'titulo'    => traduz('consulta.de.lista.basica', $con),
		'descr'     => traduz('consulte.listas.basica.de.produtos', $con)
	),
	array(
		'fabrica_no' => $nao_consulta_tabela_precos,
		'icone'     => 'marca25.gif',
		'link'      =>($login_fabrica!=1)
				? 'tabela_precos.php'
				: 'tabela_precos_blackedecker_consulta.php',
		'titulo'    => traduz('consulta.tabela.de.precos', $con),
		'descr'     => traduz('clique.aqui.para.consultar.a.tabela.de.precos', $con)
	),
	array(
		'fabrica_no' => array(1),
		'disabled'   => !isset($dist_link),
		'icone'      => 'tela25.gif',
		'link'       => $dist_link,
		'titulo'     => traduz($dist_title, $con),
		'descr'      => traduz($dist_descr, $con)
	),
	array(
		'disabled'  => true, // Não usa mais
		'fabrica'   => array(24),
		'so_testes' => true,     // Mostrar apenas para o Posto de Testes
		'icone'     => 'tela25.gif',
		'link'      => 'peca_inventario.php',
		'titulo'    => traduz('inventario.de.pecas', $con),
		'descr'     => traduz('informe.o.inventario.de.pecas', $con)
	),
	array(
		'fabrica_no' => array_merge(array(1,6,30,42),((isset($novaTelaOs)) ? array($login_fabrica) : array())),
		'icone'      => 'tela25.gif',
		'link'       => 'pendencia_relacao.php',
		'titulo'     => traduz('consulta.pendencia.de.pecas', $con),
		'descr'      => traduz('consulta.pendencia.de.pecas.solicitadas.ao.fabricante', $con)
	),
	array(
		'fabrica' => array(30,72,151,164),
		'icone'   => 'tela25.gif',
		'link'    => 'conferencia_recebimento.php',
		'titulo'  => "Conferência de Recebimento de Peça",
		'descr'   => "Conferência de recebimento de peça por nota fiscal"
	),
	array(
		'disabled'  => true,        //Opção bloqueada, não mostra
		'fabrica'   => array(1),
		'icone'     => 'tela25.gif',
		'link'      => 'relatorio_pendencia.php',
		'titulo'    => traduz('pendencia.postos', $con),
		'descr'     => traduz('pendencia.de.pecas.de.postos', $con)
	),
	array(
		'disabled'  => true,        //Opção bloqueada, não mostra
		'fabrica'   => array(1),
		'icone'     => 'tela25.gif',
		'link'      => 'xls/revisao_tabela_bd.xls',
		'titulo'    => traduz('alteracoes.de.precos"', $con),
		'descr'     => traduz('consulte.aqui.os.itens.que.sofreram.alteracao.na.tabela.de.precos', $con)
	),
	array(
		'fabrica'   => array(1,  35, 79),
		'icone'     => 'tela25.gif',
		'so_testes' => in_array($login_fabrica, array(1, 3 , 35)),
		'link'      => 'lv_completa.php',
		'background'=> '#ECFFB3',
		'titulo'    => traduz('loja.virtual', $con),
		'descr'     => traduz('loja.virtual', $con)
	),
	array(
		'disabled'  => $mostra_posicao_financeira,
		'fabrica'   => array(3, 51, 81, 114),
		'icone'     => 'tela25.gif',
		'link'      => 'posicao_financeira_telecontrol.php',
		'titulo'    => traduz('posicao.financeira.telecontrol', $con),
		'descr'     => traduz('verifique.sua.posicao.financeira.boletos.emitidos.notas.fiscais.de.venda.e.credito.de.pecas', $con)
	),
	array(
		'fabrica'    => $loja_habilitada,
		'icone'     => 'tela25.gif',
		'link'      => ($login_fabrica == 3) ? "tipo_compra.php" : "loja_new.php",
		'titulo'    => ($login_fabrica == 42) ? 'Loja Makita' : traduz(['loja','b2b']),
		'descr'     => traduz('Acesso a compra de Produtos para Assistências Técnicas')
	),
	array(
		'fabrica'    => $loja_habilitada,
		'icone'     => 'tela25.gif',
		'link'      => 'consulta_pedidos_b2b.php',
		'titulo'    => traduz(['consulta.de.pedidos', 'b2b']),
		'descr'     => traduz('consulte.seus.pedidos')
	),
);

// vim: set noet sw=0 ts=2:
