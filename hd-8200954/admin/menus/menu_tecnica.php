<?php

include_once 'funcoes.php';

msgBloqueioMenu();

// Fábricas que podem inserir comunicado na tela inicial
$fabrica_comunicado_tela_ini = (in_array($login_fabrica, array(1, 2, 3, 11, 19, 20, 24, 25, 35, 43, 46, 51, 66, 74)) or $login_fabrica > 80);

// Fábricas que oferecem treinamento aos PAs
$fabrica_treinamento = array(1, 10, 20, 42, 117,129,122,138,145,148,152,160,169,170,171,175,180,181,182,193);
if($replica_einhell) $fabrica_treinamento[] = $login_fabrica;

$titulo_vista_explodida = array(
    11 => traduz('Vistas Explodidas, Esquemas Elétricos e Informativos Técnicos'),
    14 => traduz('Informações Técnicas'),
    15 => traduz('Vistas Explodidas, Esquemas Elétricos <br>e Vídeos Treinamentos'),
    66 => traduz('Material de Apoio'),
    148 => traduz('Vistas Explodidas e Manual de Instruções'),
    172 => traduz('Vistas Explodidas, Esquemas Elétricos e Informativos Técnicos'),
);
$titulo_ve = in_array($login_fabrica, array_keys($titulo_vista_explodida)) ?
    $titulo_vista_explodida[$login_fabrica] :
    traduz('Vistas Explodidas e Esquemas Elétricos');

$descr_vista_explodida = array(
    0 => traduz("Insira as vistas explodidas e esquemas eletricos da <b>").$login_fabrica_nome."</b>".traduz(" para os postos"),
    11 => traduz("Insira as vistas explodidas, esquemas eletricos e informativos tecnicos da <b>").$login_fabrica_nome."</b>".traduz(" para os postos"),
    19 => traduz("Insira as vistas explodidas, esquemas eletricos, tabela de preço e mão-de-obra da <b>").$login_fabrica_nome."</b>".traduz(" para os postos"),
    14 => traduz('Informações técnicas dos produtos, vistas explodidas, esquemas, manuais, informativos, etc...'),
    15 => traduz('Insira as vistas explodidas, esquemas elétricos e vídeos de treinamento da latinatec para os postos'),
    148 => traduz("Insira as vistas explodidas e manuais de instruções da <b>").$login_fabrica_nome."</b>".traduz(" Para os postos"),
    172 => traduz("Insira as vistas explodidas, esquemas eletricos e informativos tecnicos da <b>").$login_fabrica_nome."</b>".traduz(" para os postos"),
);
$descr_ve = in_array($login_fabrica, array_keys($descr_vista_explodida)) ?
    $descr_vista_explodida[$login_fabrica] :
    $descr_vista_explodida[0];

// Menu INFORMAÇÕES TÉCNICAS
// Secão INFORMAÇÕES TÉCNICAS - Geral
return array(
    'secao0' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('INFORMAÇÕES TÉCNICAS'),
            //'noexpand'  => true
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'comunicado_produto.php',
            'titulo'    => traduz('Comunicados'),
            'descr'     => traduz("Insira os comunicados da <b>").$login_fabrica_nome."</b>".traduz(" para os postos"),
            "codigo" => 'TEC-0010'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_comunicado.php',
            'titulo'    => traduz('Relatório de comunicado lido'),
            'descr'     => traduz('Relatório dos postos que confirmaram a leitura de comunicado.'),
            "codigo" => 'TEC-0020'
        ),
        array(
            'fabrica_no'=> array(87), // Deshabilitado para a JACTO
            'icone'     => $icone["cadastro"],
            'link'      => 'vista_explodida_cadastro.php',
            'titulo'    => $titulo_ve,
            'descr'     => $descr_ve,
            "codigo" => 'TEC-0030'
        ),
        array(
            'fabrica'   => $fabrica_comunicado_tela_ini,
            'icone'     => $icone["cadastro"],
            'link'      => 'comunicado_inicial.php',
            'titulo'    => traduz('Mensagem na Tela Inicial de Posto'),
            'descr'     => traduz("Insira as mensagens da tela inicial da <b>").$login_fabrica_nome."</b>".traduz(", para os postos possam ver quando entrarem no sistema"),
            "codigo" => 'TEC-0040'
        ),
        array(
            'fabrica'   => array(19),
            'icone'     => $icone["computador"],
            'link'      => 'confirmacao_comunicado_leitura.php',
            'titulo'    => traduz('Postos e Comunicados'),
            'descr'     => traduz('Acompanhamento da leitura dos relatórios na entrada do site pelos postos.'),
            "codigo" => 'TEC-0050'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_interacao_pendente.php',
            'titulo'    => traduz('Suporte Técnico'),
            'descr'     => traduz('Solicitações de suporte técnico pendente por produtos feita pelos postos autorizados.'),
            "codigo" => 'TEC-0060'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_interacao_resolvida.php',
            'titulo'    => traduz('Relatório Suporte Técnico'),
            'descr'     => traduz('Espaço reservado para enviar/responder as dúvidas e comentários dos postos.'),
            "codigo" => 'TEC-0070'
        ),
        array(
            'fabrica_no'    => array(74,87,94,136,137,138,139,140,142,143,144,145),
            'icone'     => $icone["computador"],
            'link'      => 'forum.php',
            'titulo'    => traduz('Fórum'),
            'descr'     => traduz('Espaço reservado para enviar/responder as dúvidas e comentários dos postos'),
            "codigo" => 'TEC-0080'
        ),
        array(
            'fabrica_no'    => array(74,87,94,136,137,138,139,140,142,143,144,145),
            'icone'     => $icone["computador"],
            'link'      => 'forum_moderado.php',
            'titulo'    => traduz('Fórum Moderado'),
            'descr'     => traduz('Aprovação de conteúdo das mensagens inseridas no Fórum. Os postos apenas irão ver as mensagens após aprovação.'),
            "codigo" => 'TEC-0090'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'produto_fornecedor_lista_basica.php',
            'titulo'    => traduz('Lista Básica para Fornecedores'),
            'descr'     => traduz('Lista Básica para Fornecedores.'),
            "codigo" => 'TEC-0100'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["consulta"],
            'link'      => 'pesquisa_comunicado.php',
            'titulo'    => traduz('Pesquisa Comunicado'),
            'descr'     => traduz('Consulta Comunicados Cadastrados.'),
            "codigo" => 'TEC-0110'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'produto_fornecedor_lista_basica.php?idioma=EN',
            'titulo'    => traduz('Suppliers - Spare Parts'),
            'descr'     => traduz('Suppliers - Spare Parts.'),
            "codigo" => 'TEC-0120'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["consulta"],
            'link'      => 'info_tecnica_arvore_manual.php',
            'titulo'    => traduz('Downloads por mês Manual de Serviço'),
            'descr'     => traduz('Consulta quantidade de downloads baixados por produto'),
            "codigo" => 'TEC-0130'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_visualizacao_documentacao_tecnica.php',
            'titulo'    => traduz('Relatório de Visualização de Documentação Técnica'),
            'descr'     => traduz('Consulta quantidade de documentos técnicos visualizados por posto autorizado.'),
            "codigo" => 'TEC-0140'
        ),
         array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'cadastro_defeitos_solucoes.php',
            'titulo'    => traduz('Cadastro de Defeitos / Soluções'),
            'descr'     => traduz('Cadastro de Defeitos / Soluções de produtos.'),
            "codigo"    => 'TEC-0150'
        ),
        'link' => 'linha_de_separação',
    ),

    //Menu TREINAMENTOS
    'secao1' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('TREINAMENTOS'),
            'fabrica'   => $fabrica_treinamento
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'treinamento_cadastro.php',
            'titulo'    => traduz('Cadastro de Treinamentos'),
            'descr'     => traduz('Agendamento, alteração e visualização dos treinamentos.'),
            "codigo" => 'TEC-1010'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'treinamento_realizados.php',
            'titulo'    => traduz('Relatório de Treinamentos Realizados'),
            'descr'     => traduz('Controle de treinamentos já realizados e controle de presença.'),
            "codigo" => 'TEC-1020'
        ),
        array(
            'fabrica_no' => array(169,170,175,193),
            'icone'     => $icone["cadastro"],
            'link'      => 'treinamento_agenda.php',
            'titulo'    => traduz('Cadastrar Técnico ao Treinamento'),
            'descr'     => traduz('Cadastro de técnicos ao treinamento.'),
            "codigo" => 'TEC-1030'
        ),
        array(
            'fabrica_no' => array(171,175),
            'icone'     => $icone["cadastro"],
            'link'      => 'treinamento_promotor.php',
            'titulo'    => traduz('Cadastrar Promotor'),
            'descr'     => traduz('Cadastro de promotores que irão divulgar o treinamento por região.'),
            "codigo" => 'TEC-1040'
        ),
        array(
            'fabrica_no' => array(175),
            'icone'     => $icone["relatorio"],
            'link'      => 'treinamento_relatorio.php',
            'titulo'    => traduz('Relatório de Treinamentos'),
            'descr'     => traduz('Relatório de treinamentos por região.'),
            "codigo" => 'TEC-1050'
        ),
        array(
            'fabrica'   => array(171),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_cliente_treinamento.php',
            'titulo'    => traduz('Cadastro de Cliente ao Treinamento'),
            'descr'     => traduz('Cadastro de Cliente ao Treinamento.'),
            "codigo" => 'TEC-1060'
        ),
        array(
            'fabrica'   => array(171),
            'icone'     => $icone["cadastro"],
            'link'      => 'relatorio_participacao.php',
            'titulo'    => traduz('Relatório de Participação'),
            'descr'     => traduz('Relatório dos clientes / técnicos que participaram de treinamentos.'),
            "codigo" => 'TEC-1070'
        ),
        array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_convidado.php',
            'titulo'    => traduz('Cadastrar Convidado'),
            'descr'     => traduz('Cadastro de convidados que irão participar de um treinamento.'),
            "codigo" => 'TEC-1070'
        ),
        array(
            'fabrica'   => array(175),
            'icone'     => $icone["computador"],
            'link'      => 'treinamentos_online.php',
            'titulo'    => traduz('Relatório de Treinamentos Online Realizados'),
            'descr'     => traduz('Controle de treinamentos online já realizados e controle de presença.'),
	    'codigo'    => 'TEC-1080'
       ),
       array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisa_satisfacao_treinamento.php',
            'titulo'    => traduz('Relatório de Pesquisa do Treinamento'),
            'descr'     => traduz('Relatório de pesquisa de satisfação do treinamento.'),
            "codigo" => 'TEC-1090'
        ),
        array(
            'fabrica'   => array(175),
            'icone'     => $icone["computador"],
            'link'      => 'treinamento_avaliacao.php',
            'titulo'    => traduz('Avaliação dos Técnicos'),
            'descr'     => traduz('Avaliação dos Técnicos.'),
            "codigo"    => 'TEC-2000'
        ),
        'link' => 'linha_de_separação',
    ),

    //Menu RELATÓRIOS - Apenas SUGGAR
    'secao2' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('RELATÓRIOS SUGGAR'),
            'fabrica'   => array(24)
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'formulario_consulta_suggar_new.php',
            'titulo'    => traduz('Consulta de relatórios'),
            'descr'     => traduz('Consulta relatórios já cadastrados.'),
            "codigo" => "TEC-SG11"
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'formulario_consulta_suggar.php',
            'titulo'    => traduz('Consulta de relatórios - Antigo'),
            'descr'     => traduz('Consulta relatórios já cadastrados.'),
            "codigo" => "TEC-SG10"
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'rg-gat-001_suggar.php',
            'titulo'    => traduz('Relatório Visita ao Posto Autorizado'),
            'descr'     => traduz('Cadastra o Relatório Visita ao Posto Autorizado.'),
            "codigo" => "TEC-SG20"
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'rg-gat-002_suggar.php',
            'titulo'    => traduz('Relatório Mensal Inspeção Técnica'),
            'descr'     => traduz('Cadastra o Relatório Mensal Inspeção Técnica.'),
            "codigo" => "TEC-SG30"
        ),
        'link' => 'linha_de_separação',
    ),
    //FIM Menu TECNICA

    //Menu Roteiro - Apenas Elgin
    'secao3' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('Informações / Cadastro - Roteiros'),
            'fabrica'   => array(42, 117)
        ),
        array(
            'fabrica'   => array(42, 117),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_tecnico.php',
            'titulo'    => traduz('Cadastro de Técnico'),
            'descr'     => traduz('Cadastro de técnicos da fábrica para realização do Roteiro.'),
            'codigo'    => 'TEC-0135'
        ),
        array(
            'fabrica'   => array(42, 117),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_roteiro_tecnico.php',
            'titulo'    => traduz('Cadastro de Roteiro'),
            'descr'     => traduz('Cadastra o roteiro a ser realizado por técnicos da fábrica.'),
            'codigo'    => 'TEC-0136'
        ),
        array(
            'fabrica'   => array(42),
            'icone'     => $icone["cadastro"],
            'link'      => 'relatorio_visitas.php',
            'titulo'    => traduz('Relatório de Visitas'),
            'descr'     => traduz('Consulta de relatório de visitas.'),
            'codigo'    => 'TEC-0137'
        ),
        array(
            'fabrica'   => array(42),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_assunto_roteiro.php',
            'titulo'    => traduz('Cadastro de Assuntos'),
            'descr'     => traduz('Cadastra os assuntos referentes aos roteiros.'),
            'codigo'    => 'TEC-0138'
        ),
        array(
            'fabrica'   => array(42),
            'icone'     => $icone["cadastro"],
            'link'      => 'listagem_roteiros.php',
            'titulo'    => traduz('Listagem de Roteiros'),
            'descr'     => traduz('Listagem de Roteiros cadastrados.'),
            'codigo'    => 'TEC-0139'
        ),
        array(
            'fabrica'   => array(42),
            'icone'     => $icone["cadastro"],
            'link'      => 'mapa_visitas.php',
            'titulo'    => traduz('Mapa de Visitas'),
            'descr'     => traduz('Mapa de visitas.'),
            'codigo'    => 'TEC-0141'
        ),
        'link' => 'linha_de_separação',
    ),
    //FIM Menu TECNICA
);

