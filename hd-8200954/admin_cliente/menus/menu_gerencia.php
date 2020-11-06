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
    // Se��o RELAT�RIOS - Geral
    'secao0' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => 'RELAT�RIOS',
            'fabirca_no'=> array(87)
        ),
        array(
            'fabrica' => array(158),
            'icone'   => $icone['bi'],
            'link'    => 'fcr_os.php',
            'titulo'  => 'BI-Field Call Rate - Produtos',
            'descr'   => 'Percentual de quebra de produtos.<br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i>',
            // 'codigo'     => 'GER-3070',
        ),
        array(
            'fabrica' => array(5,14,19,43,66),
            'icone'   => $icone['relatorio'],
            'link'    => 'defeito_os_parametros.php',
            'titulo'  => 'Relat�rio de Ordens de Servi�o',
            'descr'   => 'Relat�rio de Ordens de Servi�o lan�adas no sistema.',
            // 'codigo'  => 'GER-3450',
        ),
        array(
            'fabrica' => array(158),
            'icone'   => $icone['relatorio'],
            'link'    => 'fcr_pecas.php',
            'titulo'  => 'BI-Field Call Rate - Pe�as',
            'descr'   => 'Percentual de quebra de pe�as.<br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i>',
            // 'codigo'  => 'GER-3450',
        ),
/*
        array(
            'fabrica' => array(85),
            'icone'   => $icone['relatorio'],
            'link'    => '../admin/relatorio_os_gelopar_posto_interno.php',
            'titulo'  => 'Relat�rio de MO (Posto Gelopar)',
            'descr'   => 'Relat�rio que mostra o valor de OS do posto 10641- Gelopar',
            // 'codigo'  => 'GER-3070'
        ),
*/
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["relatorio"],
            'link'      => 'indicadores_eficiencia_volume.php',
            'titulo'    => 'Indicadores SLA/Reincid�ncia',
            'descr'     => 'Indicadores que mostram o tempo de resposta dos atendimento e a efici�ncia dos atendimentos dentro do SLA',
            // 'codigo'    => 'GER-3434'
        ),
        
        array(
            'fabrica' => array(167),
            'icone' => $icone["relatorio"],
            'link' => 'os_consulta_lite.php',
            'titulo' => 'Consulta Ordens de Servi�o',
            'descr' => 'Consulta OS Lan�adas',
            'codigo' => 'GER-2010'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["consulta"],
            'link'    => 'pedido_parametros.php',
            'titulo'  => 'Consulta Pedidos de Pe�as',
            'descr'   => 'Consulta pedidos efetuados por postos autorizados.',
            "codigo" => 'GER-2020'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["consulta"],
            'link'    => 'acompanhamento_os_revenda_parametros.php',
            'titulo'  => 'Acompanhamento de OS Revenda',
            'descr'   => 'Consulta OS de Revenda Lan�adas e Finalizadas',
            "codigo"  => 'GER-2030'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_peca_sem_preco.php',
            'titulo'  => 'Relat�rio de Pe�a em OS sem Pre�o',
            'descr'   => 'Relat�rio que mostra as pe�as que est�o cadastradas em uma OS mas n�o possuem pre�o cadastrado.',
            "codigo"  => 'GER-3260'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_quantidade_os.php',
            'titulo'  => 'Relat�rio de Quantidade de OSs Aprovadas por LINHA',
            'descr'   => 'Relat�rio que mostra a quantidade de OS aprovadas por postos em determinadas linhas nos �ltimos 3 meses.',
            "codigo"  => 'GER-3290'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_percentual_defeitos.php',
            'titulo'  => 'Percentual de Defeitos',
            'descr'   => 'Relat�rio por per�odo de percentual dos defeitos de produtos.',
            "codigo"  => 'GER-3330'
        ),

        array(
            'fabrica' => [167],
            'icone'   => $icone["relatorio"],
            'link'    => 'produtos_mais_demandados.php',
            'titulo'  => 'Produtos mais demandados',
            'descr'   => 'Relat�rio dos produtos mais demandados em Ordens de Servi�os nos �ltimos meses.',
            "codigo"  => 'GER-3440'
        ),

        array(
            'fabrica'    => [167],
            'icone'      => $icone["relatorio"],
            'link'       => 'relatorio_os.php',
            'titulo'     => 'Relat�rio de OS',
            'descr'      => 'Status das ordens de servi�o',
            "codigo"     => 'GER-3520'
        ),

        array(
            'fabrica' => [167],
            'icone'  => $icone["relatorio"],
            'link'   => 'custo_por_os.php',
            'titulo' => 'Custo por OS',
            'descr'  => 'Calcula o custo m�dio de cada posto para realizar os consertos em garantia.',
            "codigo" => 'GER-3690'
        ),

        array(
            'fabrica' => [167],
            'icone'  => $icone["relatorio"],
            'link'   => 'posto_consulta_gerencia.php',
            'titulo' => 'Rela��o de Postos Credenciados',
            'descr'  => 'Rela��o de Postos Credenciados',
            "codigo" => 'GER-4400'
        ),

        'link' => 'linha_de_separa��o',
    ),
);

