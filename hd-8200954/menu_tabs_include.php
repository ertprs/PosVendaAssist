<?php
	//Caso seja um posto interno da suggar
	if ($login_fabrica == 24 && verifica_tipo_posto_geral("posto_interno","TRUE",$login_posto)) {
		$abas = array(
		'largura' => $tabsWidth . 'px',
		'abas' => array(
			'inicio' => array(
				'imagem'  => 'inicio',
				'link'    => 'menu_inicial.php',
				'titulo'  => traduz('inicio', $con),
				'descr'   => traduz('inicio', $con)
			),
			// ABA Menu OS
			'os' => array(
				//'posto'     => ($digita_os == 't'),
				'imagem'    => 'dev',
				'link'      => 'menu_os.php',
				'titulo'    => substr(traduz('ordem.de.servico', $con), 0, 1) . '.' .
							   end(str_word_count(traduz('ordem.de.servico', $con), 2))
				//'descr' => "O flag DigitaOS está como $digita_os"
			),
			// ABA Menu Info Técnica
			'tecnica' => array(
				'imagem'     => 'info_tecnico',
				'titulo'     => traduz('info.tecnica', $con),
				'link'       => "menu_tecnica.php"
			),
			'cadastro' => array(
				'imagem'     => 'cadastro',
				'titulo'     => traduz('cadastro', $con),
				'link'       => 'menu_cadastro.php'
			),
			'sair' => array(
				'nome'   => 'inicio',
				'imagem' => 'sair',
				'link'   => 'logout_2.php',
				'titulo' => traduz('sair', $con),
				'descr'  => traduz('sair.do.sistema', $con)
			),
		)
	);
	} else {
	$abas = array(
		'largura' => $tabsWidth . 'px',
		'abas' => array(
			'inicio' => array(
				'fabrica' => array(87),
				'imagem'  => 'inicio',
				'link'    => 'menu_inicial.php',
				'titulo'  => traduz('inicio', $con),
				'descr'   => traduz('inicio', $con)
			),
			// ABA Menu OS
			'os' => array(
				'disabled'  => ($login_fabrica == 87 and $digita_os != 't'),
				'fabrica_no'=> array(87, 168),
				//'posto'     => ($digita_os == 't'),
				'imagem'    => 'os',
				'link'      => 'menu_os.php',
				'titulo'    => substr(traduz('ordem.de.servico', $con), 0, 1) . '.' .
							   end(str_word_count(traduz('ordem.de.servico', $con), 2))
				//'descr' => "O flag DigitaOS está como $digita_os"
			),
			// ABA Menu Info Técnica
			'tecnica' => array(
				'fabrica_no' => array(87),
				'imagem'     => 'info_tecnico',
				'titulo'     => traduz('info.tecnica', $con),
				'link'       => iif(($login_fabrica == 19), "info_tecnica_arvore.php", "menu_tecnica.php")
			),
			'pedido' => array(
				'disabled'   => ($login_fabrica == 87 and $pedido_faturado != 't'),
				'fabrica_no' => array(19, 20, 87),
				'imagem'     => 'pedidos',
				'titulo'     => reset(str_word_count(traduz('pedidos.com.a.peca', $con), 2)), // 'Pedidos', primeira palavra da tradução
				'link'       => 'menu_pedido.php'
			),
			'cadastro' => array(
				'fabrica_no' => array(19, 87),
				'imagem'     => 'cadastro',
				'titulo'     => traduz('cadastro', $con),
				'link'       => 'menu_cadastro.php'
			),
			'preco' => array(
				'fabrica_no' => array(15,19,20,87,148,152,180,181,182), // Implantação 148 YANMAR - sem tabela de preço
				'imagem'     => 'tabela_preco',
				'titulo'     => traduz('tabela.de.precos', $con), // criar nova entrada de tradução com o texto certo...
				'link'       => 'menu_preco.php'
			),
			'shop_pecas'    => array(
                'fabrica'   => array(20),
                'imagem'    => traduz('shop.peças', $con),
                'imagem'    => 'shop_pecas',
                'link'      => 'menu_shop_pecas.php'
            ),
			'reposicao'    => array('fabrica' => array(19), 'imagem' => 'peca_reposicao', 'link' => 'peca_reposicao_arvore.php'),
			'produtos'     => array('fabrica' => array(19), 'imagem' => 'produtos',       'link' => 'produtos_arvore.php'),
			'lancamentos'  => array('fabrica' => array(19), 'imagem' => 'lancamentos',    'link' => 'lancamentos_arvore.php'),
			'informativos' => array('fabrica' => array(19), 'imagem' => 'informativos',   'link' => 'informativos_arvore.php'),
            'promocoes'    => array('fabrica' => array(19), 'imagem' => 'promocoes',      'link' => 'promocoes_arvore.php'),
			'sair' => array(
				'nome'   => 'inicio',
				'imagem' => 'sair',
				'link'   => 'logout_2.php',
				'titulo' => traduz('sair', $con),
				'descr'  => traduz('sair.do.sistema', $con)
			),
		)
	);
}
?>
