<?php
// O arquivo não pode ser chamado diretamente
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	header('HTTP/1.1 403 Forbidden');
	die();
}

$userName = "<span class='visible-md-inline visible-lg-inline'>$login_login</span>";

$user_data = strlen($avatar) > 0 ?
	"<span><img src='$avatar' height='24' /> $userName </span>" :
	"<span><span class='img-initials'>$iniciais</span>$userName</span>";

if ($login_fabrica == 10) {
	// if (DEBUG === true and count($meus_HDs))
	// 	pre_echo($meus_HDs, $sql, true);

	function hdLink($hd, array $actions=array()) {
		$link = $GLOBALS['prefixo'] . 'chamado_detalhe.php?';
		if (!is_numeric($hd))
			return 'adm_chamado_lista.php';
		if (!array_key_exists('hd_chamado', $actions))
			$actions['hd_chamado'] = (int)$hd;
		return $link .= http_build_query($actions);
	}

	foreach ($meus_HDs as $meuHD) {
		$lnkHD    = hdLink($meuHD['hd_chamado']);
		$lnkHDini = $lnkHD . "&inicio_trabalho=1";
		$hdStatus = $meuHD['status'];
		$lnkHint  =  "title='{$meuHD['fabricante']} » {$meuHD['titulo']}'";
		if ($show_current_hd and $meuHD['hd_chamado'] != $hd_chamado_atual)
		    $lnkHD .= '&consultar=sim';

		$listaHDs[] = array(
			'name' => "<span class='linked' $lnkHint data-linktype='detalhe' data-hd='{$meuHD['hd_chamado']}'>".
					  "<strong>{$meuHD['fabricante']}</strong> &ndash; HD {$meuHD['hd_chamado']}</span>".
					  "&nbsp<small class='text-muted'>$hdStatus</small>".$actions.
					  "<span data-linktype='inicio' data-hd='{$meuHD['hd_chamado']}' title='Iniciar Trabalho' class='linked pull-right label label-info'><i class='glyphicon glyphicon-play-circle'></i></span>",
			'link' => null
		);
	}


	if (DEBUG === true and $login_admin == 1375 and pg_last_error($con)) {
		pre_echo($sql, pg_last_error($con));
		pre_echo($meus_HDs,'HDs do banco');
		pre_echo($listaHDs, "Meus HDs", true);
	}

	$show_hd_actions = ((strpos($_SERVER['PHP_SELF'], 'chamado_detalhe') or $ajax_hdd_page)
		and !in_array($status, array('Resolvido', 'Suspenso', 'Parado', 'Cancelado'))
		and $hd_chamado_atual != $hd_chamado
	);

	$HDLEFT = array(
		'Chamados' => array(
			'hidden' => is_null($grupo_admin),
			'submenu' => array(
				array(
					'name' => traduz('meus.chamados'),
					'link' => "$prefixo{$atende}_lista.php"
				),
				array(
					'name' => traduz('todos.os.chamados'),
					'link' => 'adm_chamado_lista.php'
				),
				array(
					'name' => traduz('Dashboard'),
					'link' => 'dashboard_desenvolvimento.php'
				),
				array('name' => 'sep'),
				array(
					'name' => traduz('chamados.de.erro'),
					'link' => 'adm_relatorio_hd_erro.php'
					// 'link' => 'relatorio_chamados_erro.php'
				),
				array(
					'name' => traduz('chamados.cancelados'),
					'link' => 'relatorio_chamados_cancelados.php'
				),
				array(
					'hidden' => true,
					'name' => traduz('melhorias.em.programas'),
					'link' => 'hd_chamado_melhoria.php',
				),
				array(
					'hidden' => true,
					'name' => traduz('regras.internas'),
					'link' => 'hd_chamado_regra_interna.php'
				),
				array('name' => 'sep'),
				array(
					'name' => traduz('posição.suporte'),
					'link' => 'adm_chamado_lista_suporte.php'
				),
				array(
					'name' => traduz('posição.gerencial'),
					'link' => 'adm_chamado_lista_gerencia.php'
				),
				array('name' => 'sep'),
				array('header' => traduz('BackLog')),
				array(
					'name' => 'BackLog',
					'link' => '../admin/backlog_cadastro.php',
				),
				array(
					'name' => 'Kanban',
					'link' => 'adm_painel.php',
				),
			),
		),
		'Supervisor' => array(
			'hidden' => in_array($grupo_admin, array(3, 5, 8)),
			'submenu' => array(
				array(
					'name' => traduz('Supervisor'),
					'link' => 'supervisor.php',
				),
				array(
					'hidden' => !($grupo_admin == 1 or $login_admin == 586),
					'name' => traduz('Ausência Desenvolvedores'),
					'link' => 'desenvolvedor_ausencia.php',
				),
				array(
					'hidden' => !in_array($grupo_admin, array(1,2,5,6)),
					'name' => traduz('cadastrar fila'),
					'link' => 'janela_helpdesk.php',
				),
				array(
					'hidden' => !in_array($grupo_admin, array(1,2,5,6,9,10)),
					'name' => traduz('Relatório de Horas Faturadas'),
					'link' => 'adm_relatorio_horas_faturadas.php',
					'hint' => 'Relatório de horas cobradas da franquia de cada fabricante. '.
						'São considerados os chamados aprovados dentro do mês.'
				),
				array('name' => 'sep'),
				array(
					'name' => traduz('Relatório BackLog'),
					'link' => 'relatorio_backlog.php',
				),
				array(
					'name' => traduz('Relatório Chamado'),
					'link' => 'relatorio_chamado.php'
				),
			)
		),
		'Suporte' => array(
			'name' => 'Suporte',
			'hint' => 'Ferramentas de Atendimento',
			'submenu' => array(
				array(
					'name' => 'Atendimentos em Aberto',
					'link' => 'adm_chamado_lista.php',
					'icon' => 'list',
				),
				array(
					'name' => 'Senhas de Postos Autorizados',
					'link' => 'adm_senhas.php',
					'icon' => 'lock'
				),
				array(
					'name' => 'Alterar Razão Social/IE do Posto',
					'link' => 'adm_altera_dados_posto.php',
					'icon' => 'edit',
				),
				array (
					'name' => 'Fábricas Telecontrol',
					'link' => 'adm_clientes_tc.php',
					'icon' => 'edit',
				),
				array (
					'name' => 'Unidades de Negócio',
					'link' => 'adm_unidades_negocio.php',
					'icon' => 'list'
				),
				array(
					'hidden' => !in_array($login_admin, [586, 1375, 4789, 5205]),
					'name' => 'sep'
				),
				array(
					'hidden' => !in_array($login_admin, [586, 1375, 4789, 5205]),
					'name' => 'Login Multifábrica ADMIN',
					'link' => 'adm_admin_igual.php',
					'icon' => 'transfer',
					'hint' => 'Manutenção de acesso multifábrica para usuários ADMIN.'
				),
				array('name' => 'sep'),
				array(
					'name' => traduz('Estatísticas'),
					'link' => 'adm_estatistica_new.php',
					'icon' => 'stats'
				),
				array(
					'name' => traduz('Rotinas PHP'),
					'link' => 'rotinas_php_fabricas.php',
					'icon' => 'cog'
				),
			),
		),
		'Relatórios' => array(
			// 'hidden' => !in_array($grupo_admin, array(1,2,4,7)),
			'submenu' => array(
				array('header' => traduz('Atendente')),
				array(
					'name' => traduz('Relatório Diário'),
					'link' => 'adm_relatorio_diario.php',
				),
				array(
					'hidden' => !in_array($grupo_admin, array(2,9,1)),
					'name' => traduz('Relatório de Produtividade Help-Desk'),
					'link' => 'adm_dashboard_produtividade.php',
					'hint' => 'Relatório comparativo de horas orçadas x trabalhadas'
				),
				array(
					'name' => traduz('Relatório Hora Trabalhada Atendente'),
					'link' => 'adm_producao_fabrica_adm.php',
					'hint' => 'São consideradas todas as interações de cada atendente cobrado ou não de cada fabricante',
				),
				array(
					'name' => traduz('Relatório Gerencial de Chamados'),
					'link' => 'adm_chamado_atraso.php'
				),
				array(
					'name' => 'Relatório Analítico de Atividades',
					'hint' => 'Relatório de horas efetivas (Início - Fim) por Atendente',
					'link' => 'adm_rae.php'
				),
				array('name' => 'sep'),
				array('header' => traduz('Fabricante')),
				array(
					'name' => traduz('Relatório de Hora Cobrada'),
					'link' => 'adm_producao_horas_cobradas.php',
					'hint' => 'Relatório de horas cobradas da franquia de cada fabricante. São considerados os chamados aprovados dentro do mês.',
				),
				array(
					'name' => traduz('Relatório Hora Trabalhada Fábrica'),
					'link' => 'adm_producao_fabrica.php',
					'hint' => 'São considerados todas as interações de cada atendente cobrado ou não de cada fabricante',
				),
				array(
					'name' => traduz('Relatório Horas Utilizadas de Fábricas'),
					'link' => 'adm_horas_utilizadas_fabricas.php',
					'hint' => 'Consulta as horas utlizadas de fabricas do Mês atual',
				),
				array(
					'name' => 'Relatório de SMS e Respostas',
					'link' => '../admin/relatorio_sms_detalhado.php',
					'hint' => 'Relatório de SMS e Respostas'
				),
				array(
					'name' => 'Sintético Produtividade por Chamado',
					'link' => 'adm_sla_fabrica.php',
					'hint' => 'Relatório sintético de tempo de atividade por HD'
				),
				array('name' => 'sep'),
				array('header' => traduz('Monitoramento')),
				array(
					'name' => traduz('Relatório PERLs'),
					'link' => 'adm_relatorio_fabricas.php',
					'hint' => 'Mostra os PERLs (rotinas) rodados',
				),
				array(
					'name' => traduz('Monitorar Rotinas'),
					'hint' => 'Monitoramento de execução de rotinas agendadas',
					'link' => 'monitoracron.php'
				),
				array(
					/*'hidden' => !in_array($login_admin, [586, 4789, 5205, 8820, 1097, 8527, 6835, 57, 758]),*/
					'name' => traduz('Acompanhamento de Fabricantes'),
					'link' => 'adm_acompanhamento_fabricante.php',
					'hint' => 'Relatório de visualização dos históricos dos clientes Telecontrol'
				),
			),
		),
		'Change LOG' => array(
			'hidden' => true,
			'submenu' => array(
				array(
					'name' => traduz('Novo <i>Change Log</i>'),
					'link' => 'change_log_insere.php',
					'hint' => 'Inserir uma nova entrada no ChangeLog',
				),
				array(
					'name' => traduz('<i>ChangeLog</i> não lidos'),
					'link' => 'change_log_lida.php',
					'hint' => 'Mostra os logs ainda não lidos',
				),
				array(
					'name' => traduz('Consultar Change Log'),
					'link' => 'change_log_mostra.php',
					'hint' => 'Consulta o Change Log do PósVenda',
				),
			)
		),
		'Abrir Atendimento' => array(
			'name' => 'Novo Atendimento',
			'hint' => 'Abertura rápida de novo atendimento Telefone',
			'link' => 'adm_chamado_telefone.php?acao=INICIAR_ATENDIMENTO'
		),
		'Idioma' => array(
			'submenu' => array(
				array(
					'name' => traduz('Tradução (no HelpDesk)'),
					'link' => 'idioma.php',
					'hint' => 'Inserir u alterar a tradução de frases, dentro do ambiente do Help-Desk',
				),
				array(
					'name' => traduz('Tradução PósVenda (no WW2)'),
					'link' => '//ww2.telecontrol.com.br/mlg/adm_idioma.php',
					'hint' => 'CRUD Tradução do PósVenda, no ambiente do programa de Consultas',
				),
			)
		),
	);

	$HDRIGHT = array(
		'BuscarHD' => true,
		'notif' => array(
			'name' => '<i class="glyphicon glyphicon-question-sign" id="notIcon" title="Notificações do Help-Desk"></i>',
			'link' => 'javascript:NotificationTC.toggleNotifications()',
			'iAtt' => 'visible-md-inline visible-lg-inline',
		),
		'userActions' => array(
			'name' => $user_data,
			'attr' => array('style' => 'height:50px'),
			'submenu' => array(
				'hdAtual' => array(
					'header' => $show_current_hd ? 'HD ATUAL - '.$hd_chamado_atual : 'SEM HD INICIADO',
				),
				'fimtrabalho' => array(
					'hidden' => !$show_current_hd,
					'name' => 'Término de Trabalho',
					'link' => hdLink($hd_chamado_atual, array('termino_trabalho'=>1)),
					'attr' => array('class' => 'text-danger'),
					'icon' => 'off'
				),
				'abrirhdatual' => array(
					'hidden' => !$show_current_hd or $hd_chamado_atual == $hd_chamado,
					'name' => 'Mostrar HD',
					'link' => hdLink($hd_chamado_atual),
					'icon' => 'share'
				),
				array('name' => 'sep', 'hidden' => !$show_hd_actions),
				'hdAberto' => array(
					'hidden' => !$show_hd_actions,
					'header' => 'HD '.$hd_chamado,
				),
				array(
					'hidden' => !$show_hd_actions,
					'name' => $hd_chamado_atual ? 'Mudar para este HD' : 'Dar Início',
					'link' => hdLink($hd_chamado, array('inicio_trabalho'=>1)),
					'hint' => 'Dar início de trabalho neste HD',
					'icon' => 'play-circle',
				),
				array('name'=>'sep', 'hidden' => !count($listaHDs)),
				'meusHDs' => array(
					'hidden' => !count($listaHDs),
					'name' => 'Meus Chamados <span class="badge">'.count($listaHDs).'</span>',
					'submenu' => $listaHDs
				),
				array('name' => 'sep'),
				'Voltar' => array(
					'name' => traduz('Voltar ao Pós-Venda'),
					'link' => $admin_link_inicial ? : '../admin/menu_callcenter.php',
					'icon' => 'menu-left',
					'attr' => array(
						'id' => 'btn-back'
					),
				),
				'Sair'   => array(
					'name' => 'Logout',
					'link' => '../admin/logout.php',
					'icon' => 'log-out',
					'attr' => array(
						'id' => 'btn-sair'
					),
				)
			)
		)
	);
	return array(
		'LEFT' => $HDLEFT,
		'RIGHT' => $HDRIGHT
	);
}

return array(
	'LEFT' => array(
		'lista' => array(
			'name' => traduz('Lista de Chamados'),
			'link' => $supervisor ?
			"{$prefixo}{$atendente}_lista.php" :
			"{$prefixo}{$atendente}_lista.php?status=Análise&exigir_resposta=t"
		),
		'novoHD' => array(
			'name' => traduz('Novo Chamado'),
			'link' => 'chamado_detalhe.php',
			'hint' => 'Cadastrar um novo Atendimento de HelpDesk.'
		),
		'Supervisor' => array(
			'link' => 'supervisor.php',
			'hidden' => !$supervisor
		)
	),
	'RIGHT' => array(
		'BuscarHD' => $supervisor,
		'name' => $user_data,
		'submenu' => array(
			'Voltar' => array(
				'name' => traduz('Voltar ao Pós-Venda'),
				'link' => $admin_link_inicial,
				'hidden' => in_array($login_fabrica, $fabricasPW),
			),
			'Sair'   => array(
				'hidden' => in_array($login_fabrica, $fabricasPW),
				'icon' => 'log-out',
				'attr' => array(
					'class' => 'btn navbar-btn btn-info btn-sm',
					'id' => 'btn-sair'
				),
				'link' => '../admin/logout.php'
			),
			'Sair' => array(
				'hidden' => !in_array($login_fabrica, $fabricasPW),
				'name' => 'Voltar ao Pedido-Web',
				'icon' => 'log-out',
				'attr' => array(
					'class' => 'btn navbar-btn btn-info btn-sm',
					'id' => 'btn-sair'
				),
				'link' => 'logout_pedidoweb.php'
			),
		),
	)
);

