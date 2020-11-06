<?php
include_once 'funcoes.php';

msgBloqueioMenu();

// Menu INFORMAÇÕES FINANCEIRAS
if($inf_valores_adicionais){
    $fabrica_valores_adicionais = array($login_fabrica);
}else{
    $fabrica_valores_adicionais = array(0);
}

return array(
    // Secão INFORMAÇÕES FINANCEIRAS - Britânia
    'secao0' => array(
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('INFORMAÇÕES FINANCEIRAS'),
            'fabrica'   => array(3)
        ),
        array(
            'fabrica_no' => array(140,141,144),
            'icone'     => $icone["consulta"],
            'link'      => 'devolucao_cadastro.php',
            'titulo'    => traduz('Notas de Devolução'),
            'descr'     => traduz('Consulta as Notas de Devolução.'),
            "codigo" => 'FIN-0010'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'acerto_contas.php',
            'titulo'    => traduz('Encontro de Contas'),
            'descr'     => traduz('Realiza o encontro de contas.'),
            "codigo" => 'FIN-0020'
        ),
        'link' => 'linha_de_separação',
    ),
    'gerencia' => array(
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('APROVAÇÕES GERÊNCIA'),
            'fabrica'   => array(1)
        ),
        array(
            'fabrica' => array(1),
            'icone'   => $icone["computador"],
            'link'    => 'aprova_protocolo.php',
            'titulo'  => traduz('Aprovação Gerência Protocolo de Extratos'),
            'descr'   => traduz('Tela de aprovação / reprovação de protocolos de extratos.'),
            'codigo'  => 'FIN-5030'
        ),
        array(
            //contas receber bloqueado por enquanto
            'fabrica' => array(0),
            'icone'   => $icone["computador"],
            'link'    => 'contas_a_receber.php',
            'titulo'  => traduz('Aprovação Analista Contas a Receber'),
            'descr'   => traduz('Visualização/Aprovação dos protocolos enviados pelo analista de pós-vendas.'),
            'codigo'  => 'FIN-5040'
        ),
        array(
            'fabrica' => array(1),
            'icone'   => $icone["computador"],
            'link'    => 'contas_a_pagar.php',
            'titulo'  => traduz('Aprovação Analista Contas a Pagar'),
            'descr'   => traduz('Visualização/Aprovação dos protocolos enviados pelo gerente de contas a receber.'),
            'codigo'  => 'FIN-5050'
        ),
        array(
            'fabrica' => array(1),
            'icone'   => $icone["computador"],
            'link'    => 'relatorio_status_protocolo.php',
            'titulo'  => traduz('Relatório Status Protocolo'),
            'descr'   => traduz('Visualização dos status dos protocolos pendentes de aprovação.'),
            'codigo'  => 'FIN-5060'
        ),
        'link' => 'linha_de_separação',
    ),

    // Secão MANUTENÇÕES EM EXTRATOS - Geral
    'secao1' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('MANUTENÇÕES EM EXTRATOS'),
        ),
        array(
            'fabrica'   => 8,
            'icone'     => $icone["computador"],
            'link'      => 'os_extrato_pre.php',
            'titulo'    => traduz('Pré Fechamento de Extratos'),
            'descr'     => traduz('Pré fechamento de extratos para visualização da quantidade de OS do posto até a data limite e o valor de mão-de-obra.'),
            "codigo" => 'FIN-1010'
        ),
        array(
            'fabrica'   => array(11, 25, 50, 172),
            'icone'     => $icone["computador"],
            'link'      => 'os_extrato_por_posto.php',
            'titulo'    => (in_array($login_fabrica, array(11,172))) ? traduz('Pré-Fechamento de Extratos') : traduz('Fechamento de Extratos'),
            'descr'     => (in_array($login_fabrica, array(11,172))) ?
                traduz('Pré fechamento de extratos para visualização da quantidade de OS do posto até a data limite e o valor de mão-de-obra.') :
                traduz('Fecha o extrato de cada posto, totalizando o que cada um tem a receber de mão-de-obra, suas peças de devolução obrigatória, e demais informações de fechamento.'),
            "codigo" => 'FIN-1020'
        ),
        array(
            'fabrica'   => array(2, 6),
            'icone'     => $icone["computador"],
            'link'      => 'os_extrato_new.php',
            'titulo'    => traduz('Fechamento de Extratos'),
            'descr'     => traduz('Fecha o extrato de cada posto, totalizando o que cada um tem a receber de mão-de-obra, suas peças de devolução obrigatória, e demais informações de fechamento.') .  iif(($login_fabrica==6), "<a href='os_extrato_por_posto.php' class='menu'>".traduz('Por Posto (em Teste).')."</a>"),
            "codigo" => 'FIN-1030'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'extrato_consulta.php',
            'titulo'    => traduz('Manutenção de Extratos'),
            'descr'     => traduz('Permite retirar ordens de serviços de um extrato, recalcular o extrato, e dar baixa em seu pagamento.'),
            "codigo" => 'FIN-1050'
        ),
        array(
            'fabrica'   => array(156),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_consulta_os_callcenter.php',
            'titulo'    => traduz('Manutenção de Extratos - Contratos'),
            'descr'     => traduz('Permite retirar ordens de serviços de um extrato, recalcular o extrato, e dar baixa em seu pagamento.'),
            "codigo" => 'FIN-1051'
        ),
        array(
            'fabrica'   => array(20,30),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_liberado.php',
            'titulo'    => traduz('Liberação de Extrato'),
            'descr'     => traduz('Libera extratos para aprovação.'),
            "codigo" => 'FIN-1060'
        ),
        array(
            'fabrica'   => array(1,120,201),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_aprovado_consulta.php',
            'titulo'    => traduz('Extratos Aprovados'),
            'descr'     => traduz('Permite enviar um extrato para o financeiro.'),
            "codigo" => 'FIN-1070'
        ),

        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'extrato_financeiro_consulta.php',
            'titulo'    => traduz('Extratos Enviados ao Financeiro'),
            'descr'     => traduz('Consulta e Manutenção de Extratos Enviados ao Financeiro.'),
            "codigo" => 'FIN-1080'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'extrato_eletronico_consulta.php',
            'titulo'    => traduz('Extratos Eletrônicos Finalizados'),
            'descr'     => traduz('Consulta de Extratos Eletrônicos Finalizados.'),
            "codigo" => 'FIN-1330'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_custo_pecas.php',
            'titulo'    => traduz('Custo das Peças'),
            'descr'     => traduz('Digitação manual dos custos das peças, quando não for encontrado o último faturamento respectivo.'),
            "codigo" => 'FIN-1090'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'acumular_extratos.php',
            'titulo'    => traduz('Acumular Extratos'),
            'descr'     => traduz('Admin informa um valor e sistema acumula os extratos menores que este valor, desde que este extrato não tenha OS fechada a mais de 30 dias'),
            "codigo" => 'FIN-1100'
        ),
        array(
            'fabrica'   => array(3,74),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_avulso_cadastro.php',
            'titulo'    => traduz('Lançamento Avulso / Extratos'),
            'descr'     => traduz('Permite gerar um novo lançamento avulso, com isto, um novo extrato também é gerado.'),
            "codigo" => 'FIN-1120'
        ),
        array(
            'fabrica_no'=> array(3,74),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_avulso.php',
            'titulo'    => traduz('Lançamento Avulso / Extratos'),
            'descr'     => ($login_fabrica < 81) ? traduz('Permite gerar um novo lançamento avulso, com isto, um novo extrato também é gerado.') : traduz('Cadastro dos Lançamentos Avulsos ao Extrato'),
            "codigo" => 'FIN-1130'
        ),
        array(
            'fabrica'=> array(74,144),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_avulso_consulta.php',
            'titulo'    => traduz('Consulta Avulso Sem extratos'),
            'descr'     => traduz('Permite consultar e alterar um lançamento avulso, desde que não haja extratos vinculados.'),
            "codigo" => 'FIN-1135'
        ),
        array(
            'fabrica'   => array(6,59),
            'icone'     => $icone["cadastro"],
            'link'      => 'lancamentos_avulsos_cadastro.php',
            'titulo'    => traduz('Cadastro Lançamentos Avulsos'),
            'descr'     => traduz('Cadastro dos Lançamentos Avulsos ao Extrato'),
            "codigo" => 'FIN-1140'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'movimentacao_postos_lenoxx.php',
            'titulo'    => traduz('Movimentação do Posto Autorizado'),
            'descr'     => traduz('Relatório de Movimentação do Posto Autorizado entre períodos.'),
            "codigo" => 'FIN-1150'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'movimentacao_revenda_lenoxx.php',
            'titulo'    => traduz('Movimentação da Revenda'),
            'descr'     => traduz('Relatório de Movimentação da Revenda entre períodos.'),
            "codigo" => 'FIN-1160'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'os_excluir.php',
            'titulo'    => traduz('Excluir Ordem de Serviço'),
            'descr'     => traduz('Exclua Ordens de Serviço do Posto'),
            "codigo" => 'FIN-1180'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'os_finalizada_sem_extrato.php',
            'titulo'    => traduz('OS\'s Finalizadas Sem Extrato'),
            'descr'     => traduz('OS\'s Finalizadas Sem Extrato'),
            "codigo" => 'FIN-1195'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["consulta"],
            'link'      => 'extrato_posto_britania.php?somente_consulta=sim',
            'titulo'    => traduz('Consulta de Extratos de POSTOS'),
            'descr'     => traduz('Permite visualizar os extratos dos postos.'),
            "codigo" => 'FIN-1200'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["consulta"],
            'link'      => 'extrato_posto_devolucao_controle.php',
            'titulo'    => traduz('Consulta Detalhada de Nota de Devolução'),
            'descr'     => traduz('Permite visualizar os extratos de forma detalhada com os comprovantes de LGR'),
            "codigo" => 'FIN-1211'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["consulta"],
            'link'      => 'extrato_distribuidor.php',
            'titulo'    => traduz('Consulta de Extratos de DISTRIBUIDOR'),
            'descr'     => traduz('Permite visualizar os extratos dos distribuidores.'),
            "codigo" => 'FIN-1220'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'manutencao_logistica_reversa.php',
            'titulo'    => traduz('Manutenção de Logistica Reversa'),
            'descr'     => traduz('Permite excluir e alterar número da nota fiscal de devolução.'),
            "codigo" => 'FIN-1230'
        ),
        array(
            'fabrica'   => in_array($login_fabrica,array(11,24,25,43,72,125,153)) or $login_fabrica > 80,
            'fabrica_no'=> array_merge($fabricas_contrato_lite,array(136,137,138,139,140,142,143,145,148,150,164,169,170,191)),
            'icone'     => $icone["consulta"],
            'link'      => 'extrato_posto_devolucao_controle.php',
            'titulo'    => traduz('Controle de Notas de Devolução'),
            'descr'     => traduz('Consulta ou confirme notas fiscais de devolução.'),
            "codigo" => 'FIN-1240'
        ),
        array(
            'fabrica_no'=> array(6,24,35),
            'icone'     => $icone["cadastro"],
            'link'      => 'motivo_recusa_cadastro.php',
            'titulo'    => traduz('Motivo de Recusa'),
            'descr'     => traduz('Cadastro de Motivo de Recusa de OS dos Extratos.'),
            "codigo" => 'FIN-1250'
        ),
        array(
            'fabrica'   => array(10),
            'icone'     => $icone["computador"],
            'link'      => 'controle_de_implantacao.php',
            'titulo'    => traduz('Controle de Implantação'),
            'descr'     => traduz('Controle de Implantação'),
            "codigo" => 'FIN-1260'
        ),
        array(
            'fabrica'   => array(10),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_controle_de_implantacao.php',
            'titulo'    => traduz('Relatório de Implantação'),
            'descr'     => traduz('Relatório de Implantação'),
            "codigo" => 'FIN-1270'
        ),
        array(
            'fabrica'   => array(74,120,201),
            'icone'     => $icone["computador"],
            'link'      => 'manutencao_nota_extrato.php',
            'titulo'    => traduz('Manutenção de Notas Fiscais de Extrato'),
            'descr'     => traduz('Manutenção para as notas que o posto digita e envia pela prestação de serviços e/ou devolução de peças (LGR).'),
            "codigo" => 'FIN-1280'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_baixa.php',
            'titulo'    => traduz('Pagamento de Extratos'),
            'descr'     => traduz('Permite efetuar o pagamento de extratos gerados.'),
            "codigo" => 'FIN-1290'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["upload"],
            'link'      => 'upload_importa_black.php',
            'titulo'    => traduz('UPLOAD Arquivo Pagamento'),
            'descr'     => traduz('Atualiza o site Telecontrol com a previsão de pagamento de extrato.'),
            "codigo" => 'FIN-1300'
        ),
        array(
            'fabrica'   => array(1,3,7),
            'icone'     => $icone["consulta"],
            'link'      => 'estoque_posto_movimento.php',
            'titulo'    => traduz('Movimentação Estoque'),
            'descr'     => traduz('Visualização da movimentação do estoque do posto autorizado.'),
            "codigo" => 'FIN-1310'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'movimentacao_estoque_posto.php',
            'titulo'    => traduz('Transferir Estoque'),
            'descr'     => traduz('Transferência do estoque de um posto para outro.'),
            "codigo" => 'FIN-1320'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_extrato_detalhe.php',
            'titulo'    => traduz('Relatório Extratos de POSTOS'),
            'descr'     => traduz('Relatório para visualizar detalhe dos extratos dos postos.'),
            "codigo" => 'FIN-1330'
        ),
        array(
            'fabrica'   => array(30),
            'icone'     => $icone["cadastro"],
            'link'      => 'gera_circular.php',
            'titulo'    => traduz('Cadastro Circular Interna'),
            'descr'     => traduz('Permite gerar uma circular interna em PDF dos extratos liberados.'),
            "codigo" => 'FIN-1340'
        ),
        array(
            'fabrica'   => array(30),
            'icone'     => $icone["consulta"],
            'link'      => 'consulta_circular.php',
            'titulo'    => traduz('Consulta Circular Interna'),
            'descr'     => traduz('Permite consultar o número de circular interna em pdf dos extratos liberados.'),
            "codigo" => 'FIN-1350'
        ),
        array(
            'fabrica'   => array(81,114,122,123,125,128),
            'icone'     => $icone["consulta"],
            'link'      => 'conferencia_lote.php',
            'titulo'    => traduz('Pagamento de Extrato'),
            'descr'     => traduz('Permite consultar os extratos pelo LOTE e informar a data de pagamento'),
            "codigo" => 'FIN-1370'
        ),
        array(
            'icone'      => $icone["cadastro"],
            'link'       => 'lancamentos_avulsos_cadastro.php',
            'titulo'     => traduz('Lançamentos Avulsos'),
            'descr'      => traduz('Cadastro dos Lançamentos Avulsos ao Extrato'),
            "codigo"     => "FIN-1390"
        ),
        array(
            'fabrica' => array(183),
            'icone'      => $icone["cadastro"],
            'link'       => 'dashboard_financeiro.php',
            'titulo'     => traduz('Gráfico de Valores de Extrato Mensais'),
            'descr'      => traduz('Apresenta o gráfico dos valores dos extratos por mês'),
            "codigo"     => "FIN-1391"
        ),
        array(
            'fabrica'   => array(101,151),
            'icone'     => $icone["computador"],
            'link'      => 'relatorio_nota_fiscal_servico.php',
            'titulo'    => traduz('Conferência de Nota Fiscal de Serviço'),
            'descr'     => traduz('Permite consultar e alterar os extratos com NF de Serviço.'),
            "codigo"    => 'FIN-1400'
        ),
        array(
            'fabrica'   => array(120,201),
            'icone'     => $icone["computador"],
            'link'      => 'data_geracao_extrato_cadastro.php',
            'titulo'    => traduz('Data Geração Extrato'),
            'descr'     => traduz('Definir a data de geração de extrato para um determinado posto ou toda a rede autorizada.'),
            "codigo"    => 'FIN-1410'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["cadastro"],
            'link'      => 'lancamentos_os_prestacao_servico.php',
            'titulo'    => traduz('Liberação de OS'),
            'descr'     => traduz('OS(s) oriundas de postos cadastrados como Prestação de Serviço.'),
            "codigo"    => 'FIN-1420'
        ),
        array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["computador"],
            'link'      => 'relatorio_lgr_webservice.php',
            'titulo'    => traduz('Verificar Itens Pendentes de Devolução'),
            'descr'     => traduz('Lista todas os itens pendentes de devolução por parte dos postos autorizados.'),
            "codigo"    => 'FIN-1430'
        ),
        array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["computador"],
            'link'      => 'relatorio_lgr_webservice_consulta.php',
            'titulo'    => traduz('Consulta de Devoluções de Peças'),
            'descr'     => traduz('Consulta os lançamentos de Devolução efetuada pelos postos autorizados.'),
            "codigo"    => 'FIN-1440'
        ),
	array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["computador"],
            'link'      => 'executa_rotina_manual.php',
            'titulo'    => 'Gerar Extrato Manual',
            'descr'     => 'Geração de extrato.',
            "codigo"    => 'FIN-1450'
        ),
        'link' => 'linha_de_separação',
    ),

    // Secão RELATÓRIOS DE EXTRATOS - Geral
    'secao2' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('RELATÓRIOS'),
        ),
        array(
            'fabrica'=> array(81),
            'icone'     => $icone["upload"],
            'link'      => 'relatorio_ressarcimento.php',
            'titulo'    => traduz('Baixar Ressarcimento'),
            'descr'     => traduz('Baixar Ressarcimento de Ordem de Serviço.'),
            "codigo" => 'FIN-2010'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_extrato_avulso.php',
            'titulo'    => traduz('Avulsos Pagos em Extrato'),
            'descr'     => traduz('Todos os pagamentos avulsos pagos em extrato.'),
            "codigo" => 'FIN-2020'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_extrato_pago.php',
            'titulo'    => traduz('Extrato Baixados'),
            'descr'     => traduz('Relatório dos extratos baixados.'),
            "codigo" => 'FIN-2030'
        ),
        array(
            'fabrica'   => array(30,50),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_gasto_km.php',
            'titulo'    => traduz('Gasto com km Pagos em Extrato'),
            'descr'     => traduz('Valores pagos em extrato pelo deslocamento no atendimento do posto autorizado.'),
            "codigo" => 'FIN-2040'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'posto_dados_pagamento.php',
            'titulo'    => traduz('Dados Bancários para Pagamento'),
            'descr'     => traduz('Todas as informações bancárias para pagamentos dos postos autorizados'),
            "codigo" => 'FIN-2050'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["print"],
            'link'      => 'etiqueta_posto.php',
            'titulo'    => traduz('Etiquetas de Endereço'),
            'descr'     => traduz('Imprime etiquetas com o endere&ccedil;o dos postos selecionados.'),
            "codigo" => 'FIN-2060'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_custo_tempo.php',
            'titulo'    => traduz('Custo Tempo de Extratos'),
            'descr'     => traduz('Neste relatório contém as OS e seus respectivos Custo Tempo por um determinado período.'),
            "codigo" => 'FIN-2070'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_extrato_aprovado.php',
            'titulo'    => traduz('Tempo de Análise de Extratos'),
            'descr'     => traduz('Informa a quantidade de tempo para análise do extrato'),
            "codigo" => 'FIN-2080'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'extrato_pagamento.php',
            'titulo'    => traduz('Valores de Extratos'),
            'descr'     => traduz('Informa todos os valores de extratos dos postos.'),
            "codigo" => 'FIN-2090'
        ),
        array(
            'fabrica_no'=> array(20,121),   //retirado a pedido de Andre chamado 2254
            'icone'     => $icone["relatorio"],
            'link'      => 'extrato_pagamento_produto.php',
            'titulo'    => traduz('Produto X Custo'),
            'descr'     => traduz('Relatório de OSs e seus produtos e valor pagos por período.'),
            "codigo" => 'FIN-2100'
        ),
        array(
            'fabrica_no'=> array(20,121,147),   //retirado a pedido de Andre chamado 2254
            'icone'     => $icone["relatorio"],
            'link'      => 'extrato_pagamento_peca.php',
            'titulo'    => traduz('Peça X Custo'),
            'descr'     => traduz('Relatório de OSs e seus produtos e valor pagos por peça.'),
            "codigo" => 'FIN-2110'
        ),
        array(
            'fabrica'   => array(14),
            'icone'     => $icone["relatorio"],
            'link'      => 'faturamento_posto_peca.php',
            'titulo'    => traduz('Faturamento Produto'),
            'descr'     => traduz('Relatório de faturamento por produto, família e período.'),
            "codigo" => 'FIN-2120'
        ),
        array(
            'fabrica_no'=> array(20,121),   //retirado a pedido de Andre chamado 2254
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_field_call_rate_produto_custo.php',
            'titulo'    => traduz('Field Call Rate de Produto X Custo'),
            'descr'     => traduz('Relatório de Field Call Rate de Produtos e valor pagos por período.'),
            "codigo" => 'FIN-2130'
        ),
        array(
            'fabrica_no'=> array(20,121),   //retirado a pedido de Andre chamado 2254
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_field_call_rate_familia_custo.php',
            'titulo'    => traduz('Field Call Rate Família de Produto X Custo'),
            'descr'     => traduz('Relatório de Field Call Rate de Família e valor pagos por período.'),
            "codigo" => 'FIN-2140'
        ),
        array(
            'fabrica_no'=> array(121),
            'icone'     => $icone["relatorio"],
            'link'      => 'posto_extrato_ano.php',
            'titulo'    => traduz('Comparativo Anual de Média de Extrato'),
            'descr'     => traduz('Valor mensal dos extratos do posto num período de 12 meses.'),
            "codigo" => 'FIN-2150'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_extrato_semestral_bosch.php',
            'titulo'    => traduz('Controle de Garantia Semestral'),
            'descr'     => traduz('Relatório semestral com: total de OSs, total de peças, total de mão de obra, total pago e média por os.'),
            "codigo" => 'FIN-2160'
        ),
        //  24472 - Francisco Ambrozio (4/8/08) - Incluído link Relatório O,
        //          Conferidas por Linha - Britania.
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_conferida_linha.php',
            'titulo'    => traduz('Relatório de OSs Conferidas'),
            'descr'     => traduz('Relatório de ordens de serviço conferidas por linha.'),
            "codigo" => 'FIN-2170'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_fluxo_os.php',
            'titulo'    => traduz('Relatório Fluxo de OSs'),
            'descr'     => traduz('Relatório de fluxo de OS.'),
            "codigo" => 'FIN-2180'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_field_call_rate_gastos_postos.php',
            'titulo'    => traduz('Relatório de Mão-de-obra'),
            'descr'     => traduz('Relatório de pagamento de mão-de-obra por posto, período e produto.'),
            "codigo" => 'FIN-2190'
        ),
        array(
            'fabrica'   => array(30),/*hd: 91609*/
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_extrato_detalhado_esmaltec.php',
            'titulo'    => traduz('Relatório de Extrato Detalhado'),
            'descr'     => traduz('Valor dos extratos com filtro de família e como resultado os detalhes de valor de mão de obra, peças e Km.'),
            "codigo" => 'FIN-2200'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(2),
            'icone'     => $icone["consulta"],
            'link'      => 'extrato_posto_devolucao_controle.php',
            'titulo'    => traduz('Controle de Notas de Devolução'),
            'descr'     => '<strong>'.traduz('EM TESTE').'</strong>'.traduz('Consulta ou confirme notas fiscais de devolução.').'',
            "codigo" => 'FIN-2210'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pgto_mo.php',
            'titulo'    => traduz('Relatório de Mão-de-Obra'),
            'descr'     => traduz('Relatório de pagamento de mão-de-obra por posto, período e produto.'),
            "codigo" => 'FIN-2220'
        ),
        array(
            'fabrica'   => array(5),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_mobra_relacao.php',
            'titulo'    => traduz('Relatório Custo x Posto'),
            'descr'     => traduz('Relatório do total de produto e mão-de-obra pagos por posto nas relações ME, MK, ML/MC.'),
            "codigo" => 'FIN-2230'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_parametros_finalizada.php',
            'titulo'    => traduz('Relatório OS Finalizada + Mão-de-Obra'),
            'descr'     => traduz('Relatório Ordens de Serviço finalizadas com mão-de-obra e peças aplicadas'),
            "codigo" => 'FIN-2240'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'extrato_peca_retorno_obrigatorio.php',
            'titulo'    => traduz('Relatório Devolução Obrigatória'),
            'descr'     => traduz('Relatório de Peças de Retorno Obrigatório'),
            "codigo" => 'FIN-2250'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'extrato_documento_consulta.php',
            'titulo'    => traduz('Relatório de Pendência de Documento'),
            'descr'     => traduz('Relatório de Todas as Pendências Lançadas nos Extratos.'),
            "codigo" => 'FIN-2260'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(14),
            'icone'     => $icone["relatorio"],
            'link'      => 'extrato_excluido.php',
            'titulo'    => traduz('Relatório dos extratos excluídos'),
            'descr'     => traduz('Relatório que mostram os extratos excluídos.'),
            "codigo" => 'FIN-2270'
        ),
        array(
            'fabrica'   => array(14),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_recusada.php',
            'titulo'    => traduz('Relatório das OSs Recusadas'),
            'descr'     => traduz('Relatório que mostram a quantidade das OSs recusada do extrato.'),
            "codigo" => 'FIN-2280'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(14),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_sem_extrato.php',
            'titulo'    => traduz('Relatório de OS sem extrato'),
            'descr'     => traduz('Relatório de Ordens de serviço que não entraram em nenhum extrato por algum motivo (ex. os pedidos são inferior a R$ 3,00).'),
            "codigo" => 'FIN-2290'
        ),
        array(
            'fabrica'   => array(5,45),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_lancamento_avulso.php',
            'titulo'    => traduz('Relatório dos Lançamentos Avulsos'),
            'descr'     => traduz('Relatório que mostram os lançamentos avulsos.'),
            "codigo" => 'FIN-2300'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_defeito_constatado_mo.php',
            'titulo'    => traduz('Relatório de Mão-de-Obra DEWALT'),
            'descr'     => traduz('Relatório que mostra a mão-de-obra por defeito constatado da linha Dewalt.'),
            "codigo" => 'FIN-2310'
        ),
        array(
            'fabrica'   => array(80),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_sem_extrato_new.php',
            'titulo'    => traduz('Relatório de Previsão de Mão de Obra'),
            'descr'     => traduz('Relatório de OS Finalizadas e mostrando o valor de Mão-de-Obra antes de entrar no extrato.'),
            "codigo" => 'FIN-2320'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'gasto_por_posto.php',
            'titulo'    => traduz('Gastos por Posto'),
            'descr'     => traduz('Mostra os postos com maiores e menores gastos em garantia.'),
            'codigo'    => 'FIN-2330'

        ),
        array(
            'fabrica'   => array(30),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_carencia_90_dias.php',
            'titulo'    => traduz('Relatório Carência 90 dias'),
            'descr'     => traduz('Relatório de Os com carência de 90 dias.'),
            'codigo'    => 'FIN-2340'

        ),
        array(
            'fabrica'   => array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_km_mo.php',
            'titulo'    => traduz('Relatório KM/Mão de Obra'),
            'descr'     => traduz('Mostra por postos valores de KM e Mão de Obra.'),
            'codigo'    => 'FIN-2350'
        ),
        array(
            'fabrica'   => array(85,138),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_finalizadas_sem_extrato.php',
            'titulo'    => traduz('OSs Finalizadas Sem Extrato'),
            'descr'     => traduz('OSs Finalizadas Sem Extrato'),
            'codigo' => 'FIN-2360'
        ),
        array(
            'fabrica' => array(30),
            'icone'   => $icone["relatorio"],
            'link'    => 'extrato_consulta_detalhe.php',
            'titulo'  => traduz('Detalhamento de Extratos'),
            'descr'   => traduz('Relatório para conferência das informações dos Extratos'),
            "codigo"  => "FIN-2350"
        ),
        array(
            'fabrica' => array(175),
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_extrato_pago_x_pendente_pagamento.php',
            'titulo'  => traduz('Relatório de extratos pagos x pendente de pagamento'),
            'descr'   => traduz('Relatório para conferência dos extratos que já foram pagos e os extratos que ainda estão pendente de pagamento.'),
            "codigo"  => "FIN-2360"
        ),
        array(
            'fabrica' => [3],
            'icone'   => $icone["relatorio"],
            'link'    => 'acompanhamento_conferencia_lgr.php',
            'titulo'  => traduz('Painel de conferência de MO'),
            'descr'   => traduz('Acompanhamento conferência LGR dos postos'),
            "codigo"  => "FIN-2370"
        ),
        array(
            'fabrica' => [158],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_extrato_os_atendida.php',
            'titulo'  => traduz('Extrato por OS Atendida'),
            'descr'   => traduz('Relatório de extrato por OS atendida'),
            "codigo"  => "FIN-2380"
        ),
	 array(
            'icone'   => $icone["relatorio"],
            'link'    => 'previsao_proximo_extrato.php',
            'titulo'  => 'Previsão Próximo Extrato',
            'descr'   => 'Relatório de previsão de valores a serem pagos no próximo extrato',
            "codigo"  => "FIN-2390"
        ),
        'link' => 'linha_de_separação',
    ),

    // Secão NOVO EXTRATO - Britânia
    'secao3' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('NOVO SISTEMA DE EXTRATO'),
            'fabrica'   => array(3)
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'extrato_posto_britania_novo_processo.php',
            'titulo'    => traduz('Conferência de Extratos de POSTOS'),
            'descr'     => traduz('Permite visualizar os extratos dos postos e realizar a conferência das OSs.'),
            "codigo" => 'FIN-3010'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'lancamento_nf_avulsa.php',
            'titulo'    => traduz('Lançamento de Nota Fiscal Avulsa'),
            'descr'     => traduz('Permite lançar um nota fiscal para o posto autorizado. '),
            "codigo" => 'FIN-3011'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'sinalizador_os.php',
            'titulo'    => traduz('Sinalizador'),
            'descr'     => traduz('Gerencia o status e opções para sinalizar as OSs.'),
            "codigo" => 'FIN-3020'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'agrupa_extrato_posto_geral.php',
            'titulo'    => traduz('Agrupar Extratos'),
            'descr'     => traduz('Agrupa todos os extratos conferidos.'),
            "codigo" => 'FIN-3030'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_avulso_extrato.php',
            'titulo'    => traduz('Aprovar Avulso Extrato'),
            'descr'     => traduz('Agrupa os avulsos dos extrato.'),
            "codigo"    => 'FIN-3031'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_agrupado.php',
            'titulo'    => traduz('Extratos Pagos ao Posto'),
            'descr'     => traduz('Extratos pagos aos postos.'),
            "codigo" => 'FIN-3040'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'nota_fiscal_pagamento_britania.php',
            'titulo'    => traduz('Lançamento nota fiscal'),
            'descr'     => traduz('Lança dados da nota fiscal emitida pelo posto e dados de pagamento.'),
            "codigo" => 'FIN-3050'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_conferida_linha_novo.php',
            'titulo'    => traduz('Relatório de OSs Conferidas'),
            'descr'     => traduz('Relatório de ordens de serviço conferidas por linha.'),
            "codigo" => 'FIN-3060'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_fechamento_automatico.php',
            'titulo'    => traduz('Fechamento Automático de OS'),
            'descr'     => traduz('Relatório para consulta de OS fechadas automaticamente pelo sistema.'),
            "codigo" => 'FIN-3070'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'aprova_pag_mo.php',
            'titulo'    => traduz('Aprovação técnica pagamento de mão de obra'),
            'descr'     => traduz('Aprovar ou reprovar os extratos agrupados para pagamento de mão de obra.'),
            "codigo" => 'FIN-3080'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pdf_extrato_gerados.php',
            'titulo'    => traduz('Relatorio de PDF Gerados'),
            'descr'     => traduz('Relatório para os PDFs gerados no relatorio FIN-3010.'),
            "codigo" => 'FIN-3090'
        ),        
        'link' => 'linha_de_separação',
    ),

    // Secão COBRANÇA - Britânia
    'secao4' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('COBRANÇA'),
            'fabrica'   => array(3)
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'cobranca_busca.php',
            'titulo'    => traduz('Cobrança'),
            'descr'     => traduz('Lista notas para a cobrança.'),
            "codigo" => 'FIN-4010'
        ),
        array(
            'icone'     => $icone["upload"],
            'link'      => 'cobranca_envia_arquivo.php',
            'titulo'    => traduz('Incluir arquivo'),
            'descr'     => traduz('Incluiu o arquivo TXT no banco de dados.'),
            "codigo" => 'FIN-4020'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'cobranca_debito.php',
            'titulo'    => traduz('Débito detalhado'),
            'descr'     => traduz('Gerencia tipos de débito.'),
            "codigo" => 'FIN-4030'
        ),
        'link' => 'linha_de_separação',
    ),

    // Secão CADASTRO - Britania
    'secao5' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('CADASTRO'),
            'fabrica'   => array(3)
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'acrescimo_mo_prazo_cadastro.php',
            'titulo'    => traduz('Cadastro de mão-de-obra diferenciada'),
            'descr'     => traduz('Cadastro de mão-de-obra diferenciada por prazo de atendimento.'),
            "codigo" => 'FIN-5010'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_contas_postos.php',
            'titulo'    => traduz('Cadastro de Contas dos Postos'),
            'descr'     => traduz('Manutenção de contas dos postos.'),
            "codigo" => 'FIN-5020'
        ),

        'link' => 'linha_de_separação',
    ),
);

