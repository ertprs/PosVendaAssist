<?php
$ocultarNovaTelaOS = (in_array($login_fabrica, array(172))) ? true : $ocultarNovaTelaOS;
$app_ticket = $parametros_adicionais_posto['app_ticket'];

$traduz = 'traduz';
$nameCadastro = traduz('Cadastros');
$namePedido = traduz('Pedidos');
if ($login_fabrica == 175) {
if ($count_ferramentas > 0) {	
	$iconCadastro = "wrench";
	$attrCadastro = array(
		'style'  => "color: #fac814",
		'title'  => $count_ferramentas." ".traduz("Ferramentas com o certificado próximo do vencimento")
	);
	$nameCadastro .= " [$count_ferramentas]";
}

if ($count_pedidos_aguardando_aprovacao > 0) {
	$iconPedido = "exclamation-triangle";
	$attrPedido = array(
		'style' => 'color: #fac814',
		'title' => $count_pedidos_aguardando_aprovacao." ".traduz("Pedidos aguardando aprovação")
	);
	$namePedido .= " [$count_pedidos_aguardando_aprovacao]";
}
}

$bloqueiaOsRevenda = false;

if (in_array($login_fabrica, [169,170]) && $digita_os_revenda != "t") {
   $bloqueiaOsRevenda = true;
}else if ($login_fabrica == 183){
   $bloqueiaOsRevenda = true;
}

/**
* Mostra somente os menus de OS revenda
* Fábrica - Roca (178)
**/
if ($login_fabrica == 178) {
$ocultarNovaTelaOS = true;
}

if ($login_fabrica == 24 && qual_tipo_posto($login_posto) == 696) { 
$MENU_POSTO = array(
	'HOME' => array(
		'link' => 'login.php?menu_inicial=t',
		'icon' => array('default' => 'imagens/icone_telecontrol_branco.png'),
		'name' => '', // sem texto
		'attr' => ['title' => traduz('menu.inicial')],
	),
	'Pedidos' => array(
		'layouts' => array('pedido'),
		'link'    => 'menu_pedido.php',
		'name'    => $namePedido,
		'icon'    => $iconPedido,
		'attr'   => $attrPedido,
		'submenu' => array(
			array(
				'name' => traduz('cadastro.de.pedidos'),
				'link' => array(
					'default' => 'pedido_cadastro.php'
				),
			),
			array(
				'name' => array(
					'default' => traduz('consulta.de.pedidos.de.pecas')
				),
				'link' => 'pedido_relacao.php'
			),
		),
	),
	'Sair' => array(
		'name' => traduz('Sair'),
		'link' => 'logout_2.php',
	)
);
} else if ($login_fabrica == 183 AND ($login_tipo_posto_codigo == 'Rep' OR $login_tipo_posto_codigo == 'Rev') ) {
$MENU_POSTO = array(
	'HOME' => array(
		'link' => 'login.php?menu_inicial=t',
		'icon' => array(87 => 'home', 'default' => 'imagens/icone_telecontrol_branco.png'),
		'name' => '', // sem texto
		'attr' => ['title' => traduz('menu.inicial')],
	),
	'Pedidos' => array(
		'layouts' => array('pedido'),
		'link'    => 'menu_pedido.php',
		'name'    => $namePedido,
		'icon'    => $iconPedido,
		'attr'   => $attrPedido,
		'submenu' => array(
			array(
				'name' => traduz('cadastro.de.pedidos'),
				'link' => array(
					'default' => 'pedido_cadastro.php'
				),
			),
			array(
				'name' => array(
					'default' => traduz('consulta.de.pedidos.de.pecas')
				),
				'link' => 'pedido_relacao_new.php'
			),
		),
	),
	'Info_Tecnica' => array(
		'layouts' => array(
			'lancamentos','manual','informativos','procedimento','promocoes',
			'reposicao','tecnica',
		),
		'hidden'  => isFabrica(87),
		'link'    => 'menu_tecnica.php',
		'name'    => traduz('Info Técnica'),
		'submenu' => array(
			array(
				'hidden' => !isFabrica(1, 42),
				'name'   => array(
					 3 => traduz('comunicados.tecnicos'),
					14 => traduz('comunicados.administrativos'),
					'default' => traduz('comunicados')
				),
				'link' => array(
					42 => 'comunicado_mostra.php',
					'default' => 'comunicado_mostra.php?tipo=Comunicado'
				),
			),
			array(
				'hidden' => isFabrica(1, 3, 11, 14, 42, 151, 172),
				'name' => traduz('boletim'),
				'link' => 'comunicado_mostra.php?tipo=Boletim'
			),
			array(
				'hidden' => isFabrica(1, 11, 14, 42, 172),
				'name' => array(
					 3 => traduz('comunicados.administrativos'),
					'default' => traduz('informativo')
				),
				'link' => 'comunicado_mostra.php?tipo=Informativo'
			),
			array(
				'hidden' => isFabrica(20,42,175),
				'name' => traduz('procedimentos'),
				'link' => 'procedimento_mostra.php'
			),
			array(
				'hidden' => isFabrica(20,42),
				'name' => traduz('lancamentos'),
				'link' => array(
					20 => 'comunicado_mostra.php?tipo=Lançamentos',
					42 => 'comunicado_mostra.php?tipo=Lançamentos',
					'default' => 'comunicado_mostra.php?tipo=Lancamentos',
				),
			),
			array(
				'hidden' => !isFabrica(151),
				'name' => traduz('laudo.tecnico'),
				'link' => 'comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=Laudo+Tecnico'
			),
			array('header' => traduz('documentacao.tecnica')),
			array(
				'name' => traduz('vista.explodida'),
				'link' => array(
					 1 => 'info_tecnica_arvore.php',
					42 => 'info_tecnica_arvore2.php',
					19 => 'info_tecnica_arvore.php',
					157 => 'info_tecnica_arvore.php',
					'default' => isFabrica(161) ? 'info_tecnica_arvore_new.php' : 'comunicado_mostra.php?tipo=Vista+Explodida'
				),
			),
			array(
				'hidden' => !isFabrica(11, 172),
				'name' => traduz('informativo.tecnico'),
				'link' => 'comunicado_mostra.php?tipo=Informativo+Tecnico',
			),
			array(
				'hidden' => !isFabrica(19),
				'name' => traduz('mao.de.obra'),
				'link'  => 'produtos_arvore.php?tipo=Mão-de-obra+Produtos'
			),
			array(
				'hidden' => isFabrica(3,20,42,148),
				'name' => traduz('esquema.eletrico'),
				'link' => isFabrica(161) ? 'info_tecnica_arvore_new.php' : 'comunicado_mostra.php?tipo=Esquema+Eletrico'
			),
			array(
		'hidden' => !isFabrica(148),
		'name' => traduz('manual.de.instrucoes'),
		'link' => 'comunicado_mostra.php?tipo=Manual de Instruções / Operações'
			),
			array(
				'hidden' => isFabrica(3,20,42),
				'name' => isFabrica(148) ? traduz('Boletim Técnico') : traduz('descritivo.tecnico'),
				'link' => array(
					148 => 'comunicado_mostra.php?tipo=Boletim+Técnico',
					'default' => isFabrica(161) ? 'info_tecnica_arvore_new.php' : 'comunicado_mostra.php?tipo=Descritivo+Tecnico'
				)
			),
			array(
				'hidden' => isFabrica(3,20,42),
				'name' => isFabrica(148) ? traduz('Manual Técnico') : traduz('manual'),
				'link' => array(
					148 => 'comunicado_mostra.php?tipo=Manual+Técnico',
					'default' => 'comunicado_mostra.php?tipo=Manual'
				)
			),
			array(
				'fabrica' => [175],
				'name' => traduz('procedimentos'),
				'link' => 'comunicado_mostra.php?tipo=Procedimentos'
			),
			array(
				'hidden' => !isFabrica(3,42),
				'name' => traduz('manual.de.servico'),
				'link' => 'info_tecnica_arvore_manual.php',
				'hint' => traduz('manual.de.servico.para.auxilio.de.conserto'),
			),
			array(
				'hidden' => !isFabrica(42),
				'name' => traduz('procedimentos.de.manutencao'),
				'link' => 'comunicado_mostra.php?tipo=Procedimento+de+manutencao'
			),
			array(
				'hidden' => !isFabrica(11,15,91,172),
				'name' => ucfirst(traduz('videos')),
				'link' => array(
					11 => 'comunicado_mostra.php?tipo=Video',
					172 => 'comunicado_mostra.php?tipo=Video',
					'default' => 'info_tecnica_arvore.php?tipo_comunicado=video'
				)
			),
			array(
				'hidden' => !isFabrica(1,42,160),
				'name' => traduz('treinamentos'),
				'link' => array(
					42 => 'menu_treinamento.php',
					'default' => 'treinamento_agenda.php'
				)
			),
			array(
				'hidden' => !isFabrica(157),
				'name' => traduz('catalogo.de.acessorios'),
				'link' => 'catalogo_de_acessorios.php'
			),
		),
	),
	// 'OS' => array(
	// 	'layouts' => array('os'),
	// 	'link'    => 'menu_os.php',
	// 	'submenu' => array(
	// 		array(
	// 			'name' => traduz('cadastro.os.revenda'),
	// 			'link' => 'cadastro_os_revenda.php',
	// 		),
	// 		array(
	// 			'name' => traduz('consulta.os.revenda'),
	// 			'link' => 'os_consulta_lite.php'
	// 		),
	// 	),
	// ),
	'Sair' => array(
		'name' => traduz('Sair'),
		'link' => 'logout_2.php',
	)
);
} else {

$MENU_POSTO = array(
	// 'TC' => array(
	// 	'link' => 'http://www.telecontrol.com.br',
	// 	'icon' => 'imagens/icone_telecontrol_branco.png',
	// 	'name' => '', // sem texto
	// ),
	'HOME' => array(
		'link' => 'login.php?menu_inicial=t',
		'icon' => array(87 => 'home', 'default' => 'imagens/icone_telecontrol_branco.png'),
		'name' => '', // sem texto
		'attr' => ['title' => traduz('menu.inicial')],
	),
	'OS' => array(
		'layouts' => array('os'),
		'hidden'  => isFabrica(87, 168) || (isFabrica(24) and $login_posto_interno == "t"),
		'link'    => 'menu_os.php',
		'submenu' => array(
			array(
				'hidden' => ($LU_abre_os === false or $bloqAbreFechaOs or $nao_exibe_os or $bloqCadastroOs or $digita_os_consumidor === 'f') || isFabrica(35,139,178,190),
				'name' => traduz('cadastro.os'),
				'link' => $ocultarNovaTelaOS ? 'os_cadastro.php' : 'cadastro_os.php',
			),
			array(
				'hidden' => !isFabrica(35,139),
				'name'   => traduz('cadastro.oS.consumidor'),
				'link'   => $ocultarNovaTelaOS ? 'os_cadastro.php' : 'cadastro_os.php',
			),
			array(
				'hidden' => !isFabrica(35) || isFabrica(138),
				'name'   => traduz('cadastro.os.revenda'),
				'link'   => 'os_revenda.php',
			),
			array(
				'hidden' => !$usaPreOS and !$fabrica_pre_os,
				'name'   => ($login_fabrica == 191 AND $login_posto_interno == "t") ? traduz('consulta.pre.os.aberta.pela.revenda') : traduz('consulta.pre.os.aberta.pelo.call.center'),
				'link'   => 'os_consulta_lite.php?btn_acao_pre_os=Consultar',
				'hint'   => ($login_fabrica == 191 AND $login_posto_interno == "t") ? traduz('consulta.os.chamados    .da.revenda.que.fez.o.cadastro.de.uma.pre.os'): traduz('consulta.os.chamados.de.call.center.que.fez.o.cadastro.de.uma.pre.os'),
			),
			array(
				'hidden' => isFabrica(1),
				'name' => traduz('consulta.os'),
				'link' => 'os_consulta_lite.php'
			),
			array(
				'hidden' => !isFabrica(1) or $LU_abre_os === false,
				'name' => traduz('consulta.os'),
				'link' => 'os_consulta_avancada.php'
			),
			array(
				'hidden' => (isset($novaTelaFechamento) || $ocultarFechamentoOs || $LU_fecha_os === false),
				'name' => traduz('fechamento.de.ordem.de.servico'.$fechaOsRevenda),
				'link' => 'os_fechamento.php'
			),
			array(
		'hidden' => (!isset($novaTelaFechamento) || $ocultarFechamentoOs || $LU_fecha_os === false),
		'name' => traduz('fechamento.de.ordem.de.servico'.$fechaOsRevenda),
		'link' => 'fechamento_os.php'
	    ),
			array(
				'hidden' => isFabrica(20, 35, 42, 52, 114, 151,190) || $LU_abre_os === false || isset($novaTelaOsRevenda),
				'name' => traduz('cadastro.os.revenda'),
				'link' => array(
					15 => 'os_revenda_latina.php',
					80 => 'os_revenda_ajax.php',
					'default' => 'os_revenda.php',
				),
			),
			array(
				'hidden' => !isset($novaTelaOsRevenda) || $LU_abre_os === false || isFabrica(138) || $bloqueiaOsRevenda,
				'name' => isFabrica(178) ? traduz('cadastro.os') : traduz('cadastro.os.revenda'),
				'link' => 'cadastro_os_revenda.php',
			),
			array(

				'hidden' => isFabrica(1, 20, 15, 19, 42, 52, 138, 151, 178,183),
				'name' => traduz('consulta.os.revenda'),
				'link' => 'os_revenda_consulta_lite.php'
			),
			array(
				'hidden' => $app_ticket != true,
				'name'   => traduz('Relátorio de Peça'),
				'link'   => 'relatorio_posto_peca.php',
				'hint'   => 'Relatório de Peças do posto',
			),
			array(
				'hidden' => $app_ticket != true,
				'name'   => traduz('Cockpit - Agendamento de O.S'),
				'link'   => 'cockpit.php',
				'hint'   => 'Agendamento de OS para o aplicativo',
			),
			array(
				'hidden' => $app_ticket != true,
				'name'   => traduz('Aprovação de Ticket - Aplicativo'),
				'link'   => 'aprovacao_ticket_new.php',
				'hint'   => 'Aprovação de OS do aplicativo',
			),
			array(
				'hidden' => !isFabrica(1),
				'name' => traduz('cadastro.de.troca'),
				'link' => 'os_cadastro_troca.php'
			),
			array(
				'hidden' => !isFabrica(1, 15),
				'name' => traduz('consulta.os.revenda'),
				'link' => 'os_consulta_avancada.php'
			),
			array(
				'hidden' => !isFabrica(1, 3, 6, 10, 11, 35, 172),
				'name' => traduz('lotes.de.revenda'),
				'link' => 'revenda_conferencia.php'
			),
			array(
				'hidden' => !isFabrica(152,180,181,182),
				'name' => traduz('cadastro.os.entrega.tecnica'),
				'link' => 'cadastro_os_entrega_tecnica.php'
			),
			array(
				'hidden' => !isFabrica(145),
				'name' => traduz('cadastro.os.revisao'),
				'link' => 'cadastro_os_revisao.php'
			),
		),
	),
	'Devoluções' => array(
		'layouts' => array('devolucao'),
		'hidden'  => !isFabrica(24,169,170) || (isFabrica(24) and $login_posto_interno != "t"),
		'link'    =>  (isfabrica(24))? 'menu_devolucao.php': "#",
		'submenu' => array(
			array(
				'hidden' => !isFabrica(169,170),
				'name' => traduz('solicitação.de.devolução'),
				'link' => 'relatorio_lgr_webservice.php',
			),
			array(
				'hidden' => !isFabrica(169,170),
				'name'   => traduz('histórico.de.devoluções'),
				'link'   => 'relatorio_lgr_webservice_consulta.php',
			),
			array(
				'hidden' => !isFabrica(24),
				'name' => traduz('cadastro.de.devolucao'),
				'link' => 'devolucao_cadastro.php',
			),
			array(
				'hidden' => !isFabrica(24),
				'name'   => traduz('consulta.de.devolucoes'),
				'link'   => 'consulta_devolucoes.php',
			),
		),
	),
	// É mais fácil criar um novo do que ficar remexendo no principal.
	// Também se decidirem retira-lo, só comentar. KISS^2
	'InfoJacto' => array(
		'layouts' => 'info',
		'hidden'  => !isFabrica(87),
		'name'    => traduz('Informativos'),
		'submenu' => array(
			array(
				'name' => traduz('informativo.tecnico'),
				'attr' => array('target' => '_blank'),
				'link' => 'http://jacto.com.br/default.asp?p=acesso-restrito',
			),
			array(
				'name'   => traduz('informativo.administrativo'),
				'link'   => 'comunicado_mostra.php?tipo=comnunicado',
			),
			array(
				'name' => traduz('catalogo.de.pecas'),
	'link' => array('default' => "javascript: buscaPecaCatalogoPecas(\"$login_cnpj\")"),
			),
		),
	),
	'Info_Tecnica' => array(
		'layouts' => array(
			'lancamentos','manual','informativos','procedimento','promocoes',
			'reposicao','tecnica',
		),
		'hidden'  => isFabrica(87),
		'link'    => 'menu_tecnica.php',
		'name'    => traduz('Info Técnica'),
		'submenu' => array(
			array(
				'hidden' => !isFabrica(1, 42),
				'name'   => array(
					 3 => traduz('comunicados.tecnicos'),
					14 => traduz('comunicados.administrativos'),
					'default' => traduz('comunicados')
				),
				'link' => array(
					42 => 'comunicado_mostra.php',
					'default' => 'comunicado_mostra.php?tipo=Comunicado'
				),
			),
			array(
				'hidden' => isFabrica(1, 3, 11, 14, 42, 151, 172),
				'name' => traduz('boletim'),
				'link' => 'comunicado_mostra.php?tipo=Boletim'
			),
			array(
				'hidden' => isFabrica(1, 11, 14, 42, 172),
				'name' => array(
					 3 => traduz('comunicados.administrativos'),
					'default' => traduz('informativo')
				),
				'link' => 'comunicado_mostra.php?tipo=Informativo'
			),
			array(
				'hidden' => isFabrica(20,42,175),
				'name' => traduz('procedimentos'),
				'link' => isFabrica(3) ? 'procedimento_mostra_new.php' : 'procedimento_mostra.php'
			),
			array(
				'hidden' => isFabrica(3,20,42),
				'name' => traduz('lancamentos'),
				'link' => array(
					20 => 'comunicado_mostra.php?tipo=Lançamentos',
					42 => 'comunicado_mostra.php?tipo=Lançamentos',
					'default' => 'comunicado_mostra.php?tipo=Lancamentos',
				),
			),
			array(
				'hidden' => !isFabrica(151),
				'name' => traduz('laudo.tecnico'),
				'link' => 'comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=Laudo+Tecnico'
			),
			array('header' => traduz('documentacao.tecnica')),
			array(
				'name' => traduz('vista.explodida'),
				'link' => array(
					 1 => 'info_tecnica_arvore.php',
					42 => 'info_tecnica_arvore2.php',
					19 => 'info_tecnica_arvore.php',
					157 => 'info_tecnica_arvore.php',
					161 => 'info_tecnica_arvore_new.php',
					'default' => 'comunicado_mostra.php?tipo=Vista+Explodida'
					),
				),
				array(
					'hidden' => !isFabrica(11, 172),
					'name' => traduz('informativo.tecnico'),
					'link' => 'comunicado_mostra.php?tipo=Informativo+Tecnico',
				),
				array(
					'hidden' => !isFabrica(19),
					'name' => traduz('mao.de.obra'),
					'link'  => 'produtos_arvore.php?tipo=Mão-de-obra+Produtos'
				),
				array(
					'hidden' => isFabrica(3,20,42,148),
					'name' => traduz('esquema.eletrico'),
					'link' => isFabrica(161) ? 'info_tecnica_arvore_new.php' : 'comunicado_mostra.php?tipo=Esquema+Eletrico'
				),
				array(
	                'hidden' => !isFabrica(148),
	                'name' => traduz('manual.de.instrucoes'),
	                'link' => 'comunicado_mostra.php?tipo=Manual de Instruções / Operações'
				),
				array(
					'hidden' => isFabrica(3,20,42),
					'name' => isFabrica(148) ? traduz('Boletim Técnico') : traduz('descritivo.tecnico'),
					'link' => array(
						148 => 'comunicado_mostra.php?tipo=Boletim+Técnico',
						'default' => 'comunicado_mostra.php?tipo=Descritivo+Tecnico'
					)
				),
				array(
					'fabrica' => isFabrica(161,169,170),
					'name' => isFabrica(169,170) ? traduz('manual.de.serviço'): traduz('manual'),					
					'link' => array(
						'default' => 'comunicado_mostra.php?tipo=Manual'
					)
				),
				array(
					'hidden' => isFabrica(3,20,42,203),
					'name' => isFabrica(148,161) ? traduz('Manual Técnico') : isFabrica(169) ? traduz('Manual.de.Usuário') : traduz('manual'),
					'link' => array(
						148 => 'comunicado_mostra.php?tipo=Manual+Técnico',
						161 => 'info_tecnica_arvore_new.php',
						169 => "comunicado_mostra.php?tipo=Manual de Usuário",
						'default' => 'comunicado_mostra.php?tipo=Manual'
					)
				),
				array(
					'fabrica' => [175],
					'name' => traduz('procedimentos'),
					'link' => 'comunicado_mostra.php?tipo=Procedimentos'
				),
				array(
					'hidden' => !isFabrica(3,42),
					'name' => traduz('manual.de.servico'),
					'link' => isFabrica(3) ? 'info_tecnica_arvore_new.php' : 'info_tecnica_arvore_manual.php',
					'hint' => traduz('manual.de.servico.para.auxilio.de.conserto'),
				),
				array(
					'hidden' => !isFabrica(42),
					'name' => traduz('procedimentos.de.manutencao'),
					'link' => 'comunicado_mostra.php?tipo=Procedimento+de+manutencao'
				),
				array(
					'hidden' => !isFabrica(11,15,91,172),
					'name' => ucfirst(traduz('videos')),
					'link' => array(
						11 => 'comunicado_mostra.php?tipo=Video',
						172 => 'comunicado_mostra.php?tipo=Video',
						'default' => 'info_tecnica_arvore.php?tipo_comunicado=video'
					)
				),
				array(
					'hidden' => !isFabrica(1,42,160),
					'name' => traduz('treinamentos'),
					'link' => array(
						42 => 'menu_treinamento.php',
						'default' => 'treinamento_agenda.php'
					)
				),
				array(
					'hidden' => !isFabrica(157),
					'name' => traduz('catalogo.de.acessorios'),
					'link' => 'catalogo_de_acessorios.php'
				),
			),
		),
		'Pedidos' => array(
			'layouts' => array('pedido'),
			'hidden'  => isFabrica(19,20,152,180,181,182) or (isFabrica(24) and $login_posto_interno == "t"),
			'link'    => 'menu_pedido.php',
			'name'    => $namePedido,
			'icon'    => $iconPedido,
			'attr'   => $attrPedido,
			'submenu' => array(
				array(
					'hidden' => (isFabrica(15,152,180,181,182) || $LU_compra_peca === false),
					'name' => traduz('cadastro.de.pedidos'),
					'link' => array(
						  1 => 'pedido_blackedecker_cadastro.php',
						  3 => 'pedido_cadastro_normal.php',
						  6 => 'pedido_cadastro_normal.php',
						 30 => 'pedido_cadastro_normal.php',
						 42 => 'pedido_makita_cadastro.php',
						 46 => 'pedido_vista_explodida.php',
						 87 => 'pedido_jacto_cadastro.php', //HD 373202
						 93 => 'pedido_blacktest_cadastro.php',
						148 => 'http://fvweb.yanmar.com.br',
						'default' => 'pedido_cadastro.php'
					),
				),
				array(
					'name' => array(
						 1 => traduz('consulta.de.pedidos'),
						'default' => traduz('consulta.de.pedidos.de.pecas')
					),
					'link' => ($login_fabrica == 183) ? 'pedido_relacao_new.php' : 'pedido_relacao.php'
				),
				array('name' => 'sep'),
				array(

					'hidden' => !isFabrica(42),
					'name'   => traduz('consulta.de.boletos'),
					'link'   => 'consulta_boletos.php',
				),
				array(
					'hidden' => !isFabrica(42),
					'name'   => traduz('consulta.pecas.pendentes'),
					'link'   => 'consulta_pecas_pedido_pendente.php',
				),
					array(
					'hidden' => !isFabrica(42),
					'name'   => traduz('consulta.notas.fiscais'),
					'link'   => 'nf_relacao.php',
				),
				array(
					'hidden' => isFabrica(15,19,20,104,148),
					'name'   => traduz('tabela.de.precos'),
					'link'   => (!isFabrica(42)) ? 'tabela_precos.php' : 'tabela_precos_makita.php',
				),
				array(
					'hidden' => !isFabrica(1),
					'name'   => traduz('nova.tabela.de.precos'),
					'link'   => 'tabela_precos_blackedecker_consulta.php',
				),
				array(
					'hidden' => !isFabrica(175),
					'link' => 'pedidos_aguardando_aprovacao.php',
					'icon' => ($count_pedidos_aguardando_aprovacao > 0) ? 'exclamation-triangle' : '',
					'name' => traduz('Pedidos aguardando aprovação').(($count_pedidos_aguardando_aprovacao > 0) ? " [$count_pedidos_aguardando_aprovacao]" : ""),
					'attr'   => array(
						'style'  => "color:" . ($count_pedidos_aguardando_aprovacao > 0 ? '#fac814':'white'),
						'title'  => ($count_pedidos_aguardando_aprovacao > 0) ? $count_pedidos_aguardando_aprovacao." ".traduz("Pedidos aguardando aprovação") : '',
						'class' => ($count_pedidos_aguardando_aprovacao > 0) ? 'alert-submenu' : ''
					),
				),
				array(
					'hidden' => !isFabrica(42),
					'link' => 'loja_new.php',
					'name' => traduz('Loja Makita'),
				),
				array(
					'hidden' => !isFabrica(42),
					'link' => 'consulta_pedidos_b2b.php',
					'name' => traduz('Consulta de Pedidos B2B'),
				)
			)
		),
		'Cadastro' => array(
			'layouts' => array('cadastro', 'revendas'),
			'hidden'  => isFabrica(19),
			'name'    => $nameCadastro,
			'link'    => 'menu_cadastro.php',
			'icon'    => $iconCadastro,
			'attr'   => $attrCadastro,
			'submenu' => array(
				array(
					'hidden' => !isFabrica(3,59,87,141,144,148,158,165,166,169,170,178,183,190),
					'link'   => 'tecnico_cadastro.php',
					'name'   => traduz('cadastro.de.tecnicos'),
				),
				array(
					'name' => traduz('cadastro.do.posto'),
					'link' => 'posto_cadastro_new.php',
				),
				array(
					'hidden' => isFabrica(28, 24, 87, 138,184,191),
					'link'   => (isFabrica(3)) ? 'revenda_cadastro_new.php' : 'revenda_cadastro.php',
					'name'   => traduz('cadastro.de.revendas'),
				),
				array(
					'hidden'     => !isFabrica(167,203),
					'link'       => 'consulta_contratos_aceitos.php',
					'name'       => traduz('contratos.aceitos'),					
				),				
				array(
					'hidden' => !isFabrica(175),
					'link' => 'ferramenta_cadastro.php',
					'icon' => ($count_ferramentas > 0) ? 'wrench' : '',
					'name' => traduz('Cadastro de Ferramentas').(($count_ferramentas > 0) ? " [$count_ferramentas]" : ""),
					'attr'   => array(
						'style'  => "color:" . ($count_ferramentas > 0 ? '#fac814':'white'),
						'title'  => ($count_ferramentas > 0) ? $count_ferramentas." ".traduz("Ferramentas com o certificado próximo do vencimento") : '',
						'class' => ($count_ferramentas > 0) ? 'alert-submenu' : ''
					),
				)
			),
		),
		// 'Tabela_Preco' => array(
		// 	'hidden' => isFabrica(15,19,20,87,148),
		// 	'name'   => isFabrica(1) ? traduz('Menu Preço') : traduz('Tabela de Preços'),
		// 	'link'   => 'tabela_precos.php',
		// ),
		'Lorenzetti' => array(
			'hidden'   => !isFabrica(19),
			'name'     => traduz('Informativos'),
			'submenu'  => array(
				array(
					'name' => traduz('pecas.de.reposicao'), /*Tradução nova*/
					'link' => 'peca_reposicao_arvore.php'
				),
				array(
					'name' => traduz('produto'),
					'link' => 'produtos_arvore.php'
				),
				array(
					'name' => traduz('lancamentos'),
					'link' => 'lancamentos_arvore.php'
				),
				array(
					'name' => traduz('informativos'), /*Tradução nova*/
					'link' => 'informativos_arvore.php'
				),
				array(
					'name' => traduz('formularios'),
					'link' => 'promocoes_arvore.php'
				),
			)
		),
		'Shop_Pecas' => array(
			'layouts' => 'shop_pecas',
			'hidden'   => !isFabrica(20),
			'name'     => traduz('Shop Peças'),
			'submenu'  => array(
				array(
					'name' => traduz('cadastro.peças.venda'),
					'link' => 'shop_pecas_cadastro.php'
				),
				array(
					'name' => traduz('consulta.peças.venda'),
					'link' => 'shop_pecas_consulta.php'
				),
				array(
					'link'    => 'shop_pecas_acompanha.php',
					'name'  => traduz('acompanhamento.de.compra.e.venda'),
				)
			)
		),
		'HelpDesk' => array(
			'hidden' => !$helpdeskPostoAutorizado || isFabrica(198),
			'icon'   => 'life-saver',
			'link'   => $helpdesk_lista_link,
			'attr'   => array(
				'style'  => "color:" . ($temHDs ? '#fac814':'white'),
				'title'  => traduz('help.desk.atendido.pelo.fabricante'),
				'target' => '_blank'
			),
			'name'   => $helpdesk_lista_titulo,
			'submenu' => array(
				'novoHD' => array(
					'name' => traduz(array('abrir', 'novo', 'HelpDesk')),
					'link' => $helpdesk_cad_link,
				)
			)
		),
		//Está aqui apenas para "reservar" a posição
		'faq' => array('hidden' => true),
		'Sair' => array(
			'name' => traduz('Sair'),
			'link' => 'logout_2.php',
		)
	);
}


if (strlen($login_unico) > 0) {
	$MENU_LU = array(
		'LU'    => array('header' => $login_unico_nome, 'hidden' => !(bool)$login_fabrica),
		'Posto' => array(
			'icon' => 'user-o',
			'link' => 'login_unico.php',
			'name' => traduz('Login Único'),
			'submenu' => array(
				'LU'    => array('header' => $login_unico_nome),
				array(
					'name' => traduz('alterar.senha'),
					'icon' => 'user-secret',
					'link' => 'login_unico_alterar_senha.php'
				),
				array(
					'name' => traduz('alterar.email'), /*Criado essa tradução*/
					'icon' => 'vcard',
					'link' => 'login_unico_alterar_email.php'
				),
				array(
					'hidden' => !$login_master,
					'name' => traduz('cadastro.de.usuarios'), /*Criado essa tradução*/
					'icon' => 'users',
					'link' => 'login_unico_cadastro.php?t=lu'
				),
			),
		),
		'estoque' => array(
			'hidden' => !isPosto(4311, 376542),
			'name'   => traduz('estoque'),
			'icon'   => 'cubes',
			'link'   => 'estoque_consulta.php',
		),
		'Distrib' => array(
			'hidden' => !$LU_distrib_total,
			'name'   => traduz('Distrib'),
			'icon'   => 'truck',
			'link'   => 'distrib/',
		),
		'Consulta_OS' => array(
			'name'      => traduz('consulta.os'),
			'icon'      => 'search',
			'link'      => 'os_consulta_multi.php'
		),
		'Consulta_Pedido' => array(
			'name'   => traduz('consulta.pedidos'),
			'icon'   => 'list',
			'link'   => 'pedido_relacao_multi.php'
		),
		'Sair' => array(
			'name' => traduz('Sair'),
			'icon' => 'sign-out',
			'link' => 'logout_2.php',
		)
	);
	if (!$login_fabrica) {
		return array('MENU_POSTO' => $MENU_LU);
	}
	$MENU_POSTO['HOME']['submenu'] = $MENU_LU;
}
/**
 * À pedido do Túlio:
 * Deixar os elementos do menu "tocáveis" para _mobile_, de forma que seja possível
 * trabalhar com tablets ou uma tela touch qualquer. Assim, os menus deixam de ter
 * link e passa esse link para o primeiro ítem do submenu.
 * É o que faz esta parte do programa.
 */
if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile')) {
	$menuTitles = array(
		'HOME'         => traduz('menu.inicial'),
		'OS'           => traduz('menu.de.ordens.de.servico'),
		'Info_Tecnica' => traduz('menu.de.comunicados.e.informacoes.tecnicas'),
		'Pedidos'      => traduz('menu.de.pedido.de.pecas'),
		'Cadastro'     => traduz('menu.de.cadastramentos'),
	);
	foreach ($MENU_POSTO as $modulo => $menu) {
		if ($modulo == 'Sair')
			break;
		if (!array_key_exists('submenu', $menu))
			continue;
		$submenu = $menu['submenu'];
		$link_menu = array(
			'Menu' => array(
				'name' => $menuTitles[$modulo],
				'link' => $menu['link'],
			),
			array('name' => 'sep'),
		);
		unset($MENU_POSTO[$modulo]['link']);
		$submenu = array_merge($link_menu, $submenu);
		$MENU_POSTO[$modulo]['submenu'] = $submenu;
	}
	// pre_echo($MENU_POSTO, 'MENU', true);
}
if (TELA_MENU) {
	if ($helpdeskPostoAutorizado == true) {
		if (isFabrica(42) && !strpos($_SERVER['PHP_SELF'], '/helpdesk_cadastrar.php')) {
			$MENU_POSTO['faq'] = array(
				'name' => $faq_makita,
				'attr' => array('target' => '_blank'),
				'link' => $link_arquivo
			);
		}
	}
}
return array(
	'MENU_POSTO' => $MENU_POSTO
);
// vim: set noet ts=2 sts=2 sw=0:
