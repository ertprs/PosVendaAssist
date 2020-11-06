<?php
define('ICONS_PATH', 'imagens/botoes/novos_icones/');

$hidePedido = isFabrica(20, 152, 180, 181, 182) or ($cook_tipo_posto_et == true) or (isFabrica(87) and $pedido_faturado != true);

$hideExtrato = (isFabrica(87, 168) || (isFabrica(24) && $login_posto_interno == "t") || ($LU_extrato === false && $LU_master === false));

$link_comunicado = isFabrica(15) && strlen($comunicado_tabela_de_preco) > 0 ? 'comunicado_mostra.php?comunicado='.$comunicado_tabela_de_preco : 'tabela_precos.php'; /*HD-3841620 23/10/2017*/

$usa_alert = in_array($login_fabrica, array(151)) ? true : false;

$usa_vagas = in_array($login_fabrica, array(1)) ? true : false;

/*HD - 4259917*/
if (isFabrica(20,160) or $replica_einhell) {
    $fabrica_treinamento[] = $login_fabrica;
}

if (isFabrica(175) && !empty($login_unico) && $login_unico_tecnico_posto == 't') {
    $fabrica_treinamento[] = $login_fabrica;
}

if (isFabrica(193)) {
    $fabrica_treinamento[] = $login_fabrica;   
}

if (in_array($login_fabrica, array(1)) || (in_array($login_fabrica, array(178)) && $pa_posto['chat_online'] == 't')) {
	$hideChat = false;
} else {
	$hideChat = true;
}

// Define nomes e links
if ($login_fabrica == 24 && qual_tipo_posto($login_posto) == 696) {
    return array(
        'pedido' => array(
            'hidden' => ($hidePedido || (isFabrica(24) && $login_posto_interno == "t")),
            'links' => array(
                'default' => $novaTelaOs ? 'menu_pedido.php' : 'pedido_cadastro.php',
            ),
            'title' => array(
                'default' => 'pedido',
            ),
            'icon'  => ICONS_PATH.'cadastro_m.png',
        ),
        'sair' => array(
            'links'  => array('default' => 'logout_2.php'),
            'title'  => array('default' => 'sair'),
            'icon'   => ICONS_PATH.'sair_m.png',
        ),
    );
}

if ($login_fabrica == 183 AND ($login_tipo_posto_codigo == "Rep" OR $login_tipo_posto_codigo == "Rev")){
    return array(
        'pedido' => array(
            'hidden' => ($hidePedido || (isFabrica(24) && $login_posto_interno == "t")),
            'links' => array(
                'default' => $novaTelaOs ? 'menu_pedido.php' : 'pedido_cadastro.php',
            ),
            'title' => array(
                19 => 'pecas.de.reposicao',
                80 => 'pedido.de.venda',
                'default' => 'pedido',
            ),
            'icon'  => ICONS_PATH.'cadastro_m.png',
        ),
        'sair' => array(
            'links'  => array('default' => 'logout_2.php'),
            'title'  => array('default' => 'sair'),
            'icon'   => ICONS_PATH.'sair_m.png',
        ),
    );   
}


return array(
    'os' => array(
        'hidden' => isFabrica(87, 168,183),
        'links'  => (isFabrica(24) and $login_posto_interno == "t") ? 'menu_devolucao.php' : 'menu_os.php',
        'title'  => (isFabrica(24) and $login_posto_interno == "t") ? 'cadastro.de.devolucao' : 'ordem.de.servico',
        'icon'   => ICONS_PATH.'ordem_de_servico.png',
    ),
    'pedido' => array(
        'hidden' => ($hidePedido || (isFabrica(24) && $login_posto_interno == "t")),
        'links' => array(
            1  => 'menu_pedido.php',
            14 => 'pedido_relacao.php',
            15 => 'menu_pedido.php',
            19 => 'peca_reposicao_arvore.php',
            87 => 'menu_pedido.php',
            'default' => $novaTelaOs ? 'menu_pedido.php' : 'pedido_cadastro.php',
        ),
        'title' => array(
            19 => 'pecas.de.reposicao',
            80 => 'pedido.de.venda',
            'default' => 'pedido',
        ),
        'icon'  => ICONS_PATH.'cadastro_m.png',
    ),
    'extrato' => array(
        'hidden' => $hideExtrato,
        'links'  => array(101 => 'os_extrato_new.php','default' => 'os_extrato.php'),
        'title'  => array('default' => 'extrato'),
        'icon'   => ICONS_PATH.'extrato_m.png',
    ),
    'cadastro' => array(
        'hidden' => $cook_tipo_posto_et == 't',
        'links'  => array('default' => 'menu_cadastro.php'),
        'title'  => array('default' => 'cadastro'),
        'icon'   => ICONS_PATH.'cadastro_m.png',
    ),
    'dashboard' => array(
        'hidden' => !$usam_dashboard,
        'links'  => (isFabrica(158,169,170,175,178,184,191,193)) ? 'dashboard_novo.php' : 'dashboard.php',
        'title'  => 'dashboard',
        'attr'   => array('target' => '_blank'),
        'icon'   => ICONS_PATH.'dashboard_m.png',
    ),
    'estoque' => array(
        'hidden' => !(isFabrica(30, 134) and $posto_controla_estoque),
        'links' => array('default' => 'estoque_pecas.php'),
        'title' => array('default' => 'estoque.pecas'),
        'icon'  => ICONS_PATH.'estoque_m.png',
    ),
    'lgr' => array(
        'hidden' => !isFabrica(156),
        'links'  => array('default' => 'os_extrato_novo_lgr_os_callcenter.php'),
        'title'  => array('default' => array('extrato','contrato', 'sep' => ' - ')),
        'icon'   => ICONS_PATH.'extrato_m.png',
    ),
    'forum' => array(
        'hidden' => isFabrica(20,87, 136, 138, 139, 140, 142, 143, 168, 161) or $cook_tipo_posto_et == 't' or (isFabrica(24) && $login_posto_interno == "t"),
        'links'  => array(
            42 => 'comunicado_mostra.php?tipo=Informativo+administrativo ',
            'default' => 'forum.php'
        ),
        'title'  => array(
            42 => 'comunicados',
            'default' => 'forum'
        ),
        'icon'   => ICONS_PATH.'forum_m.png',
    ),
    'preco' => array(
        'hidden' => isFabrica(104,148,152,180,181,182) || (isFabrica(24) && $login_posto_interno == "t"),
        'links'  => array(
            19 => 'produtos_arvore.php',
            15 => $link_comunicado,
            'default' => 'tabela_precos.php'
        ),
        'title'  => array('default' => isFabrica(177) ? 'Listagem de peças para emissão de NF garantia' : 'tabela.de.preco'),
        'icon'   => ICONS_PATH.'tabela_de_preco_m.png',
    ),
    'cat_pecas' => array(
        'hidden' => !isFabrica(87),
        'links'  => array('default' => "javascript: buscaPecaCatalogoPecas('$login_cnpj')"),
        'title'  => array('default' => 'catalogo.de.pecas'),
        'icon'   => ICONS_PATH.'catalogo_pecas.png',
    ),
    'vista' => array(
        'hidden' => isFabrica(20, 87),
        'links'  => array(
            3  => 'comunicado_mostra_pesquisa_agrupado.php?tipo=Vista+Explodida',
            11 => 'linha_consulta.php',
            14 => 'comunicado_mostra_pesquisa_agrupado.php?acao=PESQUISAR',
            20 => 'menu_tecnica.php',
            42 => 'comunicado_mostra.php?tipo=Vista+Explodida',
            148 => 'info_tecnica.php',
            172 => 'linha_consulta.php',
	    183 => 'comunicado_mostra.php?tipo=Vista+Explodida',
            'default' => 'info_tecnica_arvore.php',
        ),
        'title'  => array(
            3   => 'documentacao.tecnica',
            11  => array('tabela.de.mao.de.obra', 'e', 'garantia'),
            14  => 'informacoes.tecnicas',
            148 => array('vista.explodida','/','manuais'),
            152 => 'documentacao',
            180 => 'documentacao',
            181 => 'documentacao',
            182 => 'documentacao',
            161 => 'documentos.tecnicos',
            172  => array('tabela.de.mao.de.obra', 'e', 'garantia'),
            'default' => 'vista.explodida',
        ),
        'icon'   => array(
            11 => ICONS_PATH . 'tabela_de_preco_m.png',
            172 => ICONS_PATH . 'tabela_de_preco_m.png',
            'default' => ICONS_PATH.'vista_explodida_m.png',
        ),
    ),
    'makita_msi' => array(
	'hidden' => !isFabrica(42),
        'links'  => 'makita_msi.php',
        'title'  => 'MSI Online',
        'icon'   => ICONS_PATH.'logo_msi_prancheta_1.png',
    ),
    'mo' => array(
        'hidden' => !isFabrica(19),
        'links'  => 'produtos_arvore.php?tipo=Mão-de-obra Produtos',
        'title'  => 'tabela.de.mao.de.obra', 'e', 'garantia',
        'icon'   => ICONS_PATH . 'tabela_de_preco_m.png',
    ),
    'videos' => array(
        'hidden' => !isFabrica(11, 15, 91, 172),
        'links'  => array(
            11 => 'comunicado_mostra.php?tipo=Video',
            91 => 'info_tecnica_arvore.php?tipo_comunicado=video',
            172 => 'comunicado_mostra.php?tipo=Video',
            'default' => 'info_tecnica_arvore.php?tipo_comunicado=video'
        ),
        'title'  => array(
            11 => 'Treinamentos, Dicas e ITs',
            172 => 'Treinamentos, Dicas e ITs',
            'default' => array('videos', 'e', 'treinamentos')
        ),
        'icon'   => ICONS_PATH.'videos_m.png',
    ),
    'informativo' => array(
        // 'hidden' => isFabrica(87, 168),
        'links'  => array(
            3  => 'comunicado_mostra.php?tipo=Informativo&btn_acao=pesquisar',
            //3  => 'comunicado_mostra.php?tipo=Informativo',
            14 => 'procedimento_mostra.php',
            42 => 'comunicado_mostra.php?tipo=Procedimento+de+manuten%E7%E3o',
            87 => 'http://jacto.com.br/default.asp?p=acesso-restrito',
            'default' => 'comunicado_mostra.php?tipo=Informativo+tecnico',
        ),
        'attr'   => array(
            87 => array('target' => '_blank'),
            'default' => ''
        ),
        'title'  => array(
            // 1  => array("informativo.tecnico","informativo.compressores", 'sep'=>"<br>- "),
            14 => 'procedimentos',
            42 => 'Boletins Técnicos',
            30 => 'Informativo',
            'default' => 'informativo.tecnico',
        ),
        'icon'   => ICONS_PATH.'informativo_m.png',
    ),
    'manuais' => array(
        'hidden' => (!isFabrica(160) or $replica_einhell),
        'links'  => array('default' => 'comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=Manual+da+Rede+autorizada'),
        'title'  => array('default' => 'manual.da.rede.autorizada'),
        'icon'   => ICONS_PATH.'manual_servico_m.png',
    ),
    'laudos' => array(
        'hidden' => (!isFabrica(160) or $replica_einhell),
        'links'  => array('default' => 'comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=Laudo'),
        'title'  => array('default' => 'laudo'),
        'icon'   => ICONS_PATH.'laudo.png',
    ),
    'comunicados' => array(
        'hidden' => isFabrica(30,168) || (isFabrica(24) && $login_posto_interno == "t"),
        'links'  => array(
            14 => 'comunicado_mostra_pesquisa.php',
            15 => 'comunicado_mostra.php',
            42 => 'comunicado_mostra.php?tipo=Esquema+El%E9trico',
            3  => 'comunicado_mostra.php?tipo=Comunicado&btn_acao=pesquisar_menu',
            'default' => 'comunicado_mostra.php?tipo=Comunicado&btn_acao=pesquisar'
        ),
        'title'  => array(
            42 => 'Esquemas Elétricos',
            'default' => 'informativo.administrativo'
        ),
        'icon'   => array('default' =>  ICONS_PATH.'comunicado_m.png'),
    ),
    'sat' => array(
        'hidden' => !isFabrica(35, 42),
        'links' => array(
            35 => 'https://suporte-tecnico.zendesk.com/hc/pt-br',
            42 => 'comunicado_mostra.php?tipo=Video'
        ),
		'title' => array(
            35 => traduz('videos.e.materiais.de.apoio.tecnico'),
            42 => 'Vídeos'
        ),
        'icon' => array(
            35 => ICONS_PATH.'sat_cadence_256_m.png',
            42 => ICONS_PATH.'videos_m.png',
        ),
        'attr' => array(
            35 => array('target' => '_blank')
        ),
    ),
    'pesquisa' => array(
        // 'hidden' => !isFabrica(85, 88, 94, 129, 134, 145, 152, 161, 180, 181, 182),
        'hidden' => !isFabrica(88, 94, 134,151),
        'links'  => array(
            1=> 'opiniao_posto_blackedecker.php',
            'default' => 'opiniao_posto.php'
        ),
        'title'     => array('default' => 'pesquisa.de.satisfacao'),
        'icon'      => ICONS_PATH.'pesquisa_satisfacao_m.png',
        'alert'     => $usa_alert,
        'tem_alert' => $temAlert,
    ),
    'treinamentos' => array(
        'hidden' => !$fabrica_treinamento,
        'links'  => array(
            42 => 'menu_treinamento.php',
            175 => 'treinamento_tecnico.php',
            'default' => 'treinamento_agenda.php'
        ),
        'title'  => array('default' => 'treinamentos'),
        'icon'   => ICONS_PATH.'treinamento_m.png',
        'vagas'     => $usa_vagas,
        'tem_vagas' => $temVagas,
    ),
    'chat' => array(
        'hidden' => $hideChat,
        'links'  => array('default' => 'autologin_tchat.php?env=posto'),
        'attr'   => array(
            1 => array('target' => '_blank'),
            178 => array('target' => '_blank'),
            'default' => ''
        ),
        'title'  => array('default' => 'Suporte On-line'),
        'icon'   => ICONS_PATH.'chat_m.png',
    ),
    'at_shop' => array(
        'hidden' => !isFabrica(85), // -3 HD 3394908
        'links'  => array(
            3  => 'loja_completa.php',
            85 => 'lv_completa.php',
            'default' => "lv_completa.php?produto_acabado='t'"),
        'title'  => array('default' => 'AT SHOP'),
        'icon'   => ICONS_PATH.'at_shop_m.png',
    ),
    'at_shop_new' => array(
        'hidden' => !in_array($login_fabrica, $loja_habilitada),
        'links'  => array('default' => ($login_fabrica == 3) ? "tipo_compra.php" : "loja_new.php"),
        'title'  => array('default' => ($login_fabrica != 42) ? ($login_fabrica == 157) ? traduz(['Promoção','de Acessórios e Peças Loja Virtual']) : traduz(['loja','Virtual']) : traduz('Loja Makita')),
        'icon'   => ICONS_PATH.'at_shop_m.png',
    ),
    'conteudo' => array(
        'hidden' => !isFabrica(42),
        'links'  => array('default' => 'comunicado_mostra.php?tipo=Conteudos'),
        'title'  => array('default' => 'Conteúdos'),
        'icon'   => ICONS_PATH . 'requisitos_m.png',
    ),
    'conferencia_recebimento' => array(
        'hidden' => (!isFabrica(160) or $replica_einhell),
        'links'  => array('default' => "conferencia_recebimento.php"),
        'title'  => array('default' => 'Conferência de Recebimento'),
        'icon'   => ICONS_PATH.'laudo.png',
    ),
    'martketplace' => array(
        'hidden' => null,
        'links'  => 'externos/loja',
        'title'  => 'Marketplace',
        'icon'   => ICONS_PATH.'at_shop_m.png',
    ),
    'sair' => array(
        'links'  => array('default' => 'logout_2.php'),
        'title'  => array('default' => 'sair'),
        'icon'   => ICONS_PATH.'sair_m.png',
    ),
);

