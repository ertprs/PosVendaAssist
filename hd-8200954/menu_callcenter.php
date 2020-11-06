<?php
return array(
    'secao0'     => array( // CALLCENTER
        'secao'      => array(
            'fabrica_no' => [25, 52,191],
            'disabled'   => in_array($login_cliente_admin, [31461]),
            'link'       => '#',
            'titulo'     => getValorFabrica(['CALL-CENTER', 6 => 'CALL-CENTER NOVO']),
        ),
        array(
            // 'fabrica'  => [7, 30, 156],
            'disabled' => in_array($login_admin, [3032, 3033]),
            'icone'    => $icone['cadastro'],
            'link'     => getValorFabrica([
                 0 => 'pre_os_cadastro_sac.php',
                 7 => 'pre_os_cadastro_sac_filizola.php',
                30 => 'pre_os_cadastro_sac_esmaltec.php',
                158 => 'pre_os_cadastro_sac_imbera.php',
            ]),
            'titulo'   => getValorFabrica([
                  0 => 'Cadastro Atendimento Pré-OS',
                  6 => 'Cadastra Atendimento Pré-OS NOVO',
                  7 => 'Cadastra Atendimento OS',
		 85 => 'Cadastra Atendimento Call-Center',
                 30 => 'Cadastra Atendimento OS',
                156 => 'Abre Chamado',
                158 => 'Cadastro Pré-atendimento',
            ]),
            'descr'    => getValorFabrica([
                  0 => 'Cadastro de Pré-OS para Postos Autorizados',
                  0 => 'Cadastro de Pré-OS para Postos Autorizados ( NOVO )',
                  7 => 'Cadastro de OS para Postos Autorizados',
                 30 => 'Cadastro de OS para Postos Autorizados',
		 85 => 'Cadastro de Atendimento para o Call-Center',
                156 => 'Cadastro de Atendimento para o Call-Center',
                158 => 'Cadastro de Pré-Atendimento para o Garantia',
            ]),
            // 'codigo'   => 'CCT-0160',
        ),
        array(
            'fabrica'  => [7, 30],
            'disabled' => in_array($login_admin, [3032, 3033]),
            'icone'    => $icone['consulta'],
            'link'     => 'callcenter_pendente_interativo.php',
            'titulo'   => getValorFabrica([
                0 => 'Pendência de Atendimentos',
                7 => 'Consulta de Chamados',
            ]),
            'descr'    => getValorFabrica([
                0 => 'Consulta a pendência de atendimentos em aberto',
                7 => 'Consulta de chamados pendentes'
            ]),
            // 'codigo'   => 'CCT-0040',
        ),
        array(
            'icone'    => $icone['relatorio'],
            'disabled' => !in_array($login_cliente_admin, [31461]) or $marca !== 'AMBEV',
            'titulo'   => 'Relatório AMBEV',
            'link'     => 'relatorio_ambev.php',
            'descr'    => 'Relatório de Atendimentos',
            // 'codigo'   => 'CCT-2450'
        ),
        array(
            'fabrica'  => [7, 30],
            'disabled' => $marca !== 'AMBEV',
            'icone'    => $icone['relatorio'],
            'link'     => 'chamados_ambev.php',
            'titulo'   => 'Atendimentos AMBEV',
            'descr'    => 'Verifica Atendimentos integrados ALERT × Telecontrol',
            // 'codigo'   => 'CCT-2464'
        ),
        array(
            'fabrica'  => [7, 30],
            'disabled' => $marca !== 'AMBEV',
            'icone'    => $icone['relatorio'],
            'link'     => 'defeitos_ambev.php',
            'titulo'   => 'Defeitos em OS AMBEV',
            'descr'    => 'Quantidade de defeitos nas OS durante o último ano',
            // 'codigo'   => 'CCT-2465'
        ),
        array(
            'fabrica'  => [7, 30],
            'disabled' => $marca !== 'AMBEV',
            'icone'    => $icone['relatorio'],
            'link'     => 'pecas_ambev.php',
            'titulo'   => 'Defeitos em OS AMBEV',
            'descr'    => 'Quantidade de peças utilizadas nas OS durante o último ano',
            // 'codigo'   => 'CCT-2466'
        ),
        array(
            'fabrica' => [156],
            'icone'   => $icone['consulta'],
            'link'    => 'consulta_atendimento_cliente_admin.php',
            'titulo'  => 'Consulta de Atendimentos',
            'descr'   => 'Consulta de atendimentos para o Call-Center',
            // 'codigo'  => 'CCT-0031',
        ),
	array(
            'fabrica' => [85],
            'icone'   => $icone['consulta'],
            'link'    => 'callcenter_parametros_new.php',
            'titulo'  => 'Consulta de Atendimentos',
            'descr'   => 'Consulta de atendimentos para o Call-Center',
            // 'codigo'  => 'CCT-0031',
        ),
        array('link' => 'linha_de_separação'),
    ),
    'secao3' => array( // ORDENS DE SERVIÇO
        'secao' => array(
            'fabrica_no' => [85,156,191],
            'disabled'   => in_array($login_admin, [3032, 3033]),
            'link'       => '#',
            'titulo'     => 'ORDENS DE SERVIÇO',
        ),
        array(
            'icone'  => $icone['consulta'],
            'link'   => getValorFabrica([
                  0 => 'cliente_admin_os_consulta_lite.php',
                  1 => 'os_consumidor_consulta.php',
                 52 => 'os_consulta_lite.php',
                 96 => 'os_consulta_lite.php',
                158 => 'os_consulta_lite.php',
                191 => 'os_consulta_lite_resumida.php',
            ]),
            'titulo' => 'Consulta de OS',
            'descr'  => 'Consulta Ordens de Serviço Lançadas',
            // 'codigo' => 'CCT-0010'
        ),
        array(
            'fabrica_no' => [7, 52, 96, 158, 191],
            'icone'      => $icone['relatorio'],
            'link'       => 'relatorio_tempo_conserto_mes.php',
            'titulo'     => 'Permanência em conserto no mês',
            'descr'      => 'Relatório que mostra o tempo (dias) de permanência do produto na assistência técnica no mês.',
            // 'codigo'     => 'GER-3360',
        ),
        array(
            'fabrica' => [52],
            'icone'   => $icone['relatorio'],
            'link'    => 'relatorio_tempo_os_aberta.php',
            'titulo'  => 'Relatório de OS em abertos em dias',
            'descr'   => 'Relatório de OS em abertos em dias, considerando a data de abertura para o dia da geração do relatório',
            // 'codigo'  => 'GER-3370'
        ),
        array(
            'fabrica_no' => [7, 52, 96, 158, 191],
            'icone'      => $icone['relatorio'],
            'link'       => 'relatorio_callcenter_atendimento.php',
            'titulo'     => 'Relatório dos atendimentos por posto',
            'descr'      => 'Relatório que mostra as OS atendidas de acordo com os filtros empregados',
            // 'codigo'     => 'CCT-2030'
        ),
    ),
    'secao4' => array( // USUÁRIO
        'secao' => array(
            'fabrica_no' => [158,191],
            'link'       => '#',
            'titulo'     => 'GERENCIAR USUÁRIO',
        ),
        array(
            'icone'  => $icone['usuario'],
            'link'   => 'altera_senha.php',
            'titulo' => 'Alterar Senha',
            'descr'  => 'Permite alterar a senha do seu usuário no sistema'
        ),
        array('link' => 'linha_de_separação'),
    ),
    'secao5' => array( // USUÁRIO
        'secao' => array(
            'fabrica' => [191],
            'link'       => '#',
            'titulo'     => '',
        ),
        array(
            'icone'    => $icone['cadastro'],
            'link'     => 'pre_os_cadastro_sac_new.php',
            'titulo'   => 'Cadastrar OS',
            'descr'    => 'Abertura de Ordem de Serviço para a Fábrica',
        ),
         array(
            'icone'  => $icone['consulta'],
            'link'   => 'os_consulta_lite_resumida.php',
            'titulo' => 'Consultar OS',
            'descr'  => 'Consulta Ordens de Serviço Lançadas',
        ),
        array(
            'icone'  => $icone['usuario'],
            'link'   => 'altera_senha.php',
            'titulo' => 'Alterar Senha',
            'descr'  => 'Permite alterar a senha do seu usuário no sistema'
        ),
        array('link' => 'linha_de_separação'),
    ),
);

