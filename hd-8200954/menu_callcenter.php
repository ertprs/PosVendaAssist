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
                  0 => 'Cadastro Atendimento Pr�-OS',
                  6 => 'Cadastra Atendimento Pr�-OS NOVO',
                  7 => 'Cadastra Atendimento OS',
		 85 => 'Cadastra Atendimento Call-Center',
                 30 => 'Cadastra Atendimento OS',
                156 => 'Abre Chamado',
                158 => 'Cadastro Pr�-atendimento',
            ]),
            'descr'    => getValorFabrica([
                  0 => 'Cadastro de Pr�-OS para Postos Autorizados',
                  0 => 'Cadastro de Pr�-OS para Postos Autorizados ( NOVO )',
                  7 => 'Cadastro de OS para Postos Autorizados',
                 30 => 'Cadastro de OS para Postos Autorizados',
		 85 => 'Cadastro de Atendimento para o Call-Center',
                156 => 'Cadastro de Atendimento para o Call-Center',
                158 => 'Cadastro de Pr�-Atendimento para o Garantia',
            ]),
            // 'codigo'   => 'CCT-0160',
        ),
        array(
            'fabrica'  => [7, 30],
            'disabled' => in_array($login_admin, [3032, 3033]),
            'icone'    => $icone['consulta'],
            'link'     => 'callcenter_pendente_interativo.php',
            'titulo'   => getValorFabrica([
                0 => 'Pend�ncia de Atendimentos',
                7 => 'Consulta de Chamados',
            ]),
            'descr'    => getValorFabrica([
                0 => 'Consulta a pend�ncia de atendimentos em aberto',
                7 => 'Consulta de chamados pendentes'
            ]),
            // 'codigo'   => 'CCT-0040',
        ),
        array(
            'icone'    => $icone['relatorio'],
            'disabled' => !in_array($login_cliente_admin, [31461]) or $marca !== 'AMBEV',
            'titulo'   => 'Relat�rio AMBEV',
            'link'     => 'relatorio_ambev.php',
            'descr'    => 'Relat�rio de Atendimentos',
            // 'codigo'   => 'CCT-2450'
        ),
        array(
            'fabrica'  => [7, 30],
            'disabled' => $marca !== 'AMBEV',
            'icone'    => $icone['relatorio'],
            'link'     => 'chamados_ambev.php',
            'titulo'   => 'Atendimentos AMBEV',
            'descr'    => 'Verifica Atendimentos integrados ALERT � Telecontrol',
            // 'codigo'   => 'CCT-2464'
        ),
        array(
            'fabrica'  => [7, 30],
            'disabled' => $marca !== 'AMBEV',
            'icone'    => $icone['relatorio'],
            'link'     => 'defeitos_ambev.php',
            'titulo'   => 'Defeitos em OS AMBEV',
            'descr'    => 'Quantidade de defeitos nas OS durante o �ltimo ano',
            // 'codigo'   => 'CCT-2465'
        ),
        array(
            'fabrica'  => [7, 30],
            'disabled' => $marca !== 'AMBEV',
            'icone'    => $icone['relatorio'],
            'link'     => 'pecas_ambev.php',
            'titulo'   => 'Defeitos em OS AMBEV',
            'descr'    => 'Quantidade de pe�as utilizadas nas OS durante o �ltimo ano',
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
        array('link' => 'linha_de_separa��o'),
    ),
    'secao3' => array( // ORDENS DE SERVI�O
        'secao' => array(
            'fabrica_no' => [85,156,191],
            'disabled'   => in_array($login_admin, [3032, 3033]),
            'link'       => '#',
            'titulo'     => 'ORDENS DE SERVI�O',
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
            'descr'  => 'Consulta Ordens de Servi�o Lan�adas',
            // 'codigo' => 'CCT-0010'
        ),
        array(
            'fabrica_no' => [7, 52, 96, 158, 191],
            'icone'      => $icone['relatorio'],
            'link'       => 'relatorio_tempo_conserto_mes.php',
            'titulo'     => 'Perman�ncia em conserto no m�s',
            'descr'      => 'Relat�rio que mostra o tempo (dias) de perman�ncia do produto na assist�ncia t�cnica no m�s.',
            // 'codigo'     => 'GER-3360',
        ),
        array(
            'fabrica' => [52],
            'icone'   => $icone['relatorio'],
            'link'    => 'relatorio_tempo_os_aberta.php',
            'titulo'  => 'Relat�rio de OS em abertos em dias',
            'descr'   => 'Relat�rio de OS em abertos em dias, considerando a data de abertura para o dia da gera��o do relat�rio',
            // 'codigo'  => 'GER-3370'
        ),
        array(
            'fabrica_no' => [7, 52, 96, 158, 191],
            'icone'      => $icone['relatorio'],
            'link'       => 'relatorio_callcenter_atendimento.php',
            'titulo'     => 'Relat�rio dos atendimentos por posto',
            'descr'      => 'Relat�rio que mostra as OS atendidas de acordo com os filtros empregados',
            // 'codigo'     => 'CCT-2030'
        ),
    ),
    'secao4' => array( // USU�RIO
        'secao' => array(
            'fabrica_no' => [158,191],
            'link'       => '#',
            'titulo'     => 'GERENCIAR USU�RIO',
        ),
        array(
            'icone'  => $icone['usuario'],
            'link'   => 'altera_senha.php',
            'titulo' => 'Alterar Senha',
            'descr'  => 'Permite alterar a senha do seu usu�rio no sistema'
        ),
        array('link' => 'linha_de_separa��o'),
    ),
    'secao5' => array( // USU�RIO
        'secao' => array(
            'fabrica' => [191],
            'link'       => '#',
            'titulo'     => '',
        ),
        array(
            'icone'    => $icone['cadastro'],
            'link'     => 'pre_os_cadastro_sac_new.php',
            'titulo'   => 'Cadastrar OS',
            'descr'    => 'Abertura de Ordem de Servi�o para a F�brica',
        ),
         array(
            'icone'  => $icone['consulta'],
            'link'   => 'os_consulta_lite_resumida.php',
            'titulo' => 'Consultar OS',
            'descr'  => 'Consulta Ordens de Servi�o Lan�adas',
        ),
        array(
            'icone'  => $icone['usuario'],
            'link'   => 'altera_senha.php',
            'titulo' => 'Alterar Senha',
            'descr'  => 'Permite alterar a senha do seu usu�rio no sistema'
        ),
        array('link' => 'linha_de_separa��o'),
    ),
);

