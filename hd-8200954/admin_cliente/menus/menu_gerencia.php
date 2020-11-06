<?php
if ($fabrica_padrao=='t'){
    $arr_fabrica_padrao = array($login_fabrica);
}

if ($telecontrol_distrib=='t'){
    $arr_fabrica_distrib = array($login_fabrica);
}else{
     $arr_fabrica_distrib = array(0);
}

$relatorio_os = ($novaTelaOs) ? array($login_fabrica) : array(0);

return array(
    // Seção RELATÓRIOS - Geral
    'secao0' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => 'RELATÓRIOS',
            'fabirca_no'=> array(87)
        ),
        array(
            'fabrica' => array(158),
            'icone'   => $icone['bi'],
            'link'    => 'fcr_os.php',
            'titulo'  => 'BI-Field Call Rate - Produtos',
            'descr'   => 'Percentual de quebra de produtos.<br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i>',
            // 'codigo'     => 'GER-3070',
        ),
        array(
            'fabrica' => array(5,14,19,43,66),
            'icone'   => $icone['relatorio'],
            'link'    => 'defeito_os_parametros.php',
            'titulo'  => 'Relatório de Ordens de Serviço',
            'descr'   => 'Relatório de Ordens de Serviço lançadas no sistema.',
            // 'codigo'  => 'GER-3450',
        ),
        array(
            'fabrica' => array(158),
            'icone'   => $icone['relatorio'],
            'link'    => 'fcr_pecas.php',
            'titulo'  => 'BI-Field Call Rate - Peças',
            'descr'   => 'Percentual de quebra de peças.<br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i>',
            // 'codigo'  => 'GER-3450',
        ),
/*
        array(
            'fabrica' => array(85),
            'icone'   => $icone['relatorio'],
            'link'    => '../admin/relatorio_os_gelopar_posto_interno.php',
            'titulo'  => 'Relatório de MO (Posto Gelopar)',
            'descr'   => 'Relatório que mostra o valor de OS do posto 10641- Gelopar',
            // 'codigo'  => 'GER-3070'
        ),
*/
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["relatorio"],
            'link'      => 'indicadores_eficiencia_volume.php',
            'titulo'    => 'Indicadores SLA/Reincidência',
            'descr'     => 'Indicadores que mostram o tempo de resposta dos atendimento e a eficiência dos atendimentos dentro do SLA',
            // 'codigo'    => 'GER-3434'
        ),
        
        array(
            'fabrica' => array(167),
            'icone' => $icone["relatorio"],
            'link' => 'os_consulta_lite.php',
            'titulo' => 'Consulta Ordens de Serviço',
            'descr' => 'Consulta OS Lançadas',
            'codigo' => 'GER-2010'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["consulta"],
            'link'    => 'pedido_parametros.php',
            'titulo'  => 'Consulta Pedidos de Peças',
            'descr'   => 'Consulta pedidos efetuados por postos autorizados.',
            "codigo" => 'GER-2020'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["consulta"],
            'link'    => 'acompanhamento_os_revenda_parametros.php',
            'titulo'  => 'Acompanhamento de OS Revenda',
            'descr'   => 'Consulta OS de Revenda Lançadas e Finalizadas',
            "codigo"  => 'GER-2030'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_peca_sem_preco.php',
            'titulo'  => 'Relatório de Peça em OS sem Preço',
            'descr'   => 'Relatório que mostra as peças que estão cadastradas em uma OS mas não possuem preço cadastrado.',
            "codigo"  => 'GER-3260'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_quantidade_os.php',
            'titulo'  => 'Relatório de Quantidade de OSs Aprovadas por LINHA',
            'descr'   => 'Relatório que mostra a quantidade de OS aprovadas por postos em determinadas linhas nos últimos 3 meses.',
            "codigo"  => 'GER-3290'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_percentual_defeitos.php',
            'titulo'  => 'Percentual de Defeitos',
            'descr'   => 'Relatório por período de percentual dos defeitos de produtos.',
            "codigo"  => 'GER-3330'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["relatorio"],
            'link'    => 'produtos_mais_demandados.php',
            'titulo'  => 'Produtos mais demandados',
            'descr'   => 'Relatório dos produtos mais demandados em Ordens de Serviços nos últimos meses.',
            "codigo"  => 'GER-3440'
        ),

        array(
            'fabrica'    => [167],
            'icone'      => $icone["relatorio"],
            'link'       => 'relatorio_os.php',
            'titulo'     => 'Relatório de OS',
            'descr'      => 'Status das ordens de serviço',
            "codigo"     => 'GER-3520'
        ),

        array(
            'fabrica' => [167],
            'icone'  => $icone["relatorio"],
            'link'   => 'custo_por_os.php',
            'titulo' => 'Custo por OS',
            'descr'  => 'Calcula o custo médio de cada posto para realizar os consertos em garantia.',
            "codigo" => 'GER-3690'
        ),

        array(
            'fabrica' => [167],
            'icone'  => $icone["relatorio"],
            'link'   => 'posto_consulta_gerencia.php',
            'titulo' => 'Relação de Postos Credenciados',
            'descr'  => 'Relação de Postos Credenciados',
            "codigo" => 'GER-4400'
        ),

        'link' => 'linha_de_separação',
    ),
);

