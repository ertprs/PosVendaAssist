<?php
include_once 'funcoes.php';

msgBloqueioMenu();

// Menu INFORMA��ES FINANCEIRAS
if($inf_valores_adicionais){
    $fabrica_valores_adicionais = array($login_fabrica);
}else{
    $fabrica_valores_adicionais = array(0);
}

return array(
    // Sec�o INFORMA��ES FINANCEIRAS - Brit�nia
    'secao0' => array(
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('INFORMA��ES FINANCEIRAS'),
            'fabrica'   => array(3)
        ),
        array(
            'fabrica_no' => array(140,141,144),
            'icone'     => $icone["consulta"],
            'link'      => 'devolucao_cadastro.php',
            'titulo'    => traduz('Notas de Devolu��o'),
            'descr'     => traduz('Consulta as Notas de Devolu��o.'),
            "codigo" => 'FIN-0010'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'acerto_contas.php',
            'titulo'    => traduz('Encontro de Contas'),
            'descr'     => traduz('Realiza o encontro de contas.'),
            "codigo" => 'FIN-0020'
        ),
        'link' => 'linha_de_separa��o',
    ),
    'gerencia' => array(
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('APROVA��ES GER�NCIA'),
            'fabrica'   => array(1)
        ),
        array(
            'fabrica' => array(1),
            'icone'   => $icone["computador"],
            'link'    => 'aprova_protocolo.php',
            'titulo'  => traduz('Aprova��o Ger�ncia Protocolo de Extratos'),
            'descr'   => traduz('Tela de aprova��o / reprova��o de protocolos de extratos.'),
            'codigo'  => 'FIN-5030'
        ),
        array(
            //contas receber bloqueado por enquanto
            'fabrica' => array(0),
            'icone'   => $icone["computador"],
            'link'    => 'contas_a_receber.php',
            'titulo'  => traduz('Aprova��o Analista Contas a Receber'),
            'descr'   => traduz('Visualiza��o/Aprova��o dos protocolos enviados pelo analista de p�s-vendas.'),
            'codigo'  => 'FIN-5040'
        ),
        array(
            'fabrica' => array(1),
            'icone'   => $icone["computador"],
            'link'    => 'contas_a_pagar.php',
            'titulo'  => traduz('Aprova��o Analista Contas a Pagar'),
            'descr'   => traduz('Visualiza��o/Aprova��o dos protocolos enviados pelo gerente de contas a receber.'),
            'codigo'  => 'FIN-5050'
        ),
        array(
            'fabrica' => array(1),
            'icone'   => $icone["computador"],
            'link'    => 'relatorio_status_protocolo.php',
            'titulo'  => traduz('Relat�rio Status Protocolo'),
            'descr'   => traduz('Visualiza��o dos status dos protocolos pendentes de aprova��o.'),
            'codigo'  => 'FIN-5060'
        ),
        'link' => 'linha_de_separa��o',
    ),

    // Sec�o MANUTEN��ES EM EXTRATOS - Geral
    'secao1' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('MANUTEN��ES EM EXTRATOS'),
        ),
        array(
            'fabrica'   => 8,
            'icone'     => $icone["computador"],
            'link'      => 'os_extrato_pre.php',
            'titulo'    => traduz('Pr� Fechamento de Extratos'),
            'descr'     => traduz('Pr� fechamento de extratos para visualiza��o da quantidade de OS do posto at� a data limite e o valor de m�o-de-obra.'),
            "codigo" => 'FIN-1010'
        ),
        array(
            'fabrica'   => array(11, 25, 50, 172),
            'icone'     => $icone["computador"],
            'link'      => 'os_extrato_por_posto.php',
            'titulo'    => (in_array($login_fabrica, array(11,172))) ? traduz('Pr�-Fechamento de Extratos') : traduz('Fechamento de Extratos'),
            'descr'     => (in_array($login_fabrica, array(11,172))) ?
                traduz('Pr� fechamento de extratos para visualiza��o da quantidade de OS do posto at� a data limite e o valor de m�o-de-obra.') :
                traduz('Fecha o extrato de cada posto, totalizando o que cada um tem a receber de m�o-de-obra, suas pe�as de devolu��o obrigat�ria, e demais informa��es de fechamento.'),
            "codigo" => 'FIN-1020'
        ),
        array(
            'fabrica'   => array(2, 6),
            'icone'     => $icone["computador"],
            'link'      => 'os_extrato_new.php',
            'titulo'    => traduz('Fechamento de Extratos'),
            'descr'     => traduz('Fecha o extrato de cada posto, totalizando o que cada um tem a receber de m�o-de-obra, suas pe�as de devolu��o obrigat�ria, e demais informa��es de fechamento.') .  iif(($login_fabrica==6), "<a href='os_extrato_por_posto.php' class='menu'>".traduz('Por Posto (em Teste).')."</a>"),
            "codigo" => 'FIN-1030'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'extrato_consulta.php',
            'titulo'    => traduz('Manuten��o de Extratos'),
            'descr'     => traduz('Permite retirar ordens de servi�os de um extrato, recalcular o extrato, e dar baixa em seu pagamento.'),
            "codigo" => 'FIN-1050'
        ),
        array(
            'fabrica'   => array(156),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_consulta_os_callcenter.php',
            'titulo'    => traduz('Manuten��o de Extratos - Contratos'),
            'descr'     => traduz('Permite retirar ordens de servi�os de um extrato, recalcular o extrato, e dar baixa em seu pagamento.'),
            "codigo" => 'FIN-1051'
        ),
        array(
            'fabrica'   => array(20,30),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_liberado.php',
            'titulo'    => traduz('Libera��o de Extrato'),
            'descr'     => traduz('Libera extratos para aprova��o.'),
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
            'descr'     => traduz('Consulta e Manuten��o de Extratos Enviados ao Financeiro.'),
            "codigo" => 'FIN-1080'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'extrato_eletronico_consulta.php',
            'titulo'    => traduz('Extratos Eletr�nicos Finalizados'),
            'descr'     => traduz('Consulta de Extratos Eletr�nicos Finalizados.'),
            "codigo" => 'FIN-1330'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_custo_pecas.php',
            'titulo'    => traduz('Custo das Pe�as'),
            'descr'     => traduz('Digita��o manual dos custos das pe�as, quando n�o for encontrado o �ltimo faturamento respectivo.'),
            "codigo" => 'FIN-1090'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'acumular_extratos.php',
            'titulo'    => traduz('Acumular Extratos'),
            'descr'     => traduz('Admin informa um valor e sistema acumula os extratos menores que este valor, desde que este extrato n�o tenha OS fechada a mais de 30 dias'),
            "codigo" => 'FIN-1100'
        ),
        array(
            'fabrica'   => array(3,74),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_avulso_cadastro.php',
            'titulo'    => traduz('Lan�amento Avulso / Extratos'),
            'descr'     => traduz('Permite gerar um novo lan�amento avulso, com isto, um novo extrato tamb�m � gerado.'),
            "codigo" => 'FIN-1120'
        ),
        array(
            'fabrica_no'=> array(3,74),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_avulso.php',
            'titulo'    => traduz('Lan�amento Avulso / Extratos'),
            'descr'     => ($login_fabrica < 81) ? traduz('Permite gerar um novo lan�amento avulso, com isto, um novo extrato tamb�m � gerado.') : traduz('Cadastro dos Lan�amentos Avulsos ao Extrato'),
            "codigo" => 'FIN-1130'
        ),
        array(
            'fabrica'=> array(74,144),
            'icone'     => $icone["computador"],
            'link'      => 'extrato_avulso_consulta.php',
            'titulo'    => traduz('Consulta Avulso Sem extratos'),
            'descr'     => traduz('Permite consultar e alterar um lan�amento avulso, desde que n�o haja extratos vinculados.'),
            "codigo" => 'FIN-1135'
        ),
        array(
            'fabrica'   => array(6,59),
            'icone'     => $icone["cadastro"],
            'link'      => 'lancamentos_avulsos_cadastro.php',
            'titulo'    => traduz('Cadastro Lan�amentos Avulsos'),
            'descr'     => traduz('Cadastro dos Lan�amentos Avulsos ao Extrato'),
            "codigo" => 'FIN-1140'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'movimentacao_postos_lenoxx.php',
            'titulo'    => traduz('Movimenta��o do Posto Autorizado'),
            'descr'     => traduz('Relat�rio de Movimenta��o do Posto Autorizado entre per�odos.'),
            "codigo" => 'FIN-1150'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'movimentacao_revenda_lenoxx.php',
            'titulo'    => traduz('Movimenta��o da Revenda'),
            'descr'     => traduz('Relat�rio de Movimenta��o da Revenda entre per�odos.'),
            "codigo" => 'FIN-1160'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'os_excluir.php',
            'titulo'    => traduz('Excluir Ordem de Servi�o'),
            'descr'     => traduz('Exclua Ordens de Servi�o do Posto'),
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
            'titulo'    => traduz('Consulta Detalhada de Nota de Devolu��o'),
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
            'titulo'    => traduz('Manuten��o de Logistica Reversa'),
            'descr'     => traduz('Permite excluir e alterar n�mero da nota fiscal de devolu��o.'),
            "codigo" => 'FIN-1230'
        ),
        array(
            'fabrica'   => in_array($login_fabrica,array(11,24,25,43,72,125,153)) or $login_fabrica > 80,
            'fabrica_no'=> array_merge($fabricas_contrato_lite,array(136,137,138,139,140,142,143,145,148,150,164,169,170,191)),
            'icone'     => $icone["consulta"],
            'link'      => 'extrato_posto_devolucao_controle.php',
            'titulo'    => traduz('Controle de Notas de Devolu��o'),
            'descr'     => traduz('Consulta ou confirme notas fiscais de devolu��o.'),
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
            'titulo'    => traduz('Controle de Implanta��o'),
            'descr'     => traduz('Controle de Implanta��o'),
            "codigo" => 'FIN-1260'
        ),
        array(
            'fabrica'   => array(10),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_controle_de_implantacao.php',
            'titulo'    => traduz('Relat�rio de Implanta��o'),
            'descr'     => traduz('Relat�rio de Implanta��o'),
            "codigo" => 'FIN-1270'
        ),
        array(
            'fabrica'   => array(74,120,201),
            'icone'     => $icone["computador"],
            'link'      => 'manutencao_nota_extrato.php',
            'titulo'    => traduz('Manuten��o de Notas Fiscais de Extrato'),
            'descr'     => traduz('Manuten��o para as notas que o posto digita e envia pela presta��o de servi�os e/ou devolu��o de pe�as (LGR).'),
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
            'descr'     => traduz('Atualiza o site Telecontrol com a previs�o de pagamento de extrato.'),
            "codigo" => 'FIN-1300'
        ),
        array(
            'fabrica'   => array(1,3,7),
            'icone'     => $icone["consulta"],
            'link'      => 'estoque_posto_movimento.php',
            'titulo'    => traduz('Movimenta��o Estoque'),
            'descr'     => traduz('Visualiza��o da movimenta��o do estoque do posto autorizado.'),
            "codigo" => 'FIN-1310'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'movimentacao_estoque_posto.php',
            'titulo'    => traduz('Transferir Estoque'),
            'descr'     => traduz('Transfer�ncia do estoque de um posto para outro.'),
            "codigo" => 'FIN-1320'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_extrato_detalhe.php',
            'titulo'    => traduz('Relat�rio Extratos de POSTOS'),
            'descr'     => traduz('Relat�rio para visualizar detalhe dos extratos dos postos.'),
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
            'descr'     => traduz('Permite consultar o n�mero de circular interna em pdf dos extratos liberados.'),
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
            'titulo'     => traduz('Lan�amentos Avulsos'),
            'descr'      => traduz('Cadastro dos Lan�amentos Avulsos ao Extrato'),
            "codigo"     => "FIN-1390"
        ),
        array(
            'fabrica' => array(183),
            'icone'      => $icone["cadastro"],
            'link'       => 'dashboard_financeiro.php',
            'titulo'     => traduz('Gr�fico de Valores de Extrato Mensais'),
            'descr'      => traduz('Apresenta o gr�fico dos valores dos extratos por m�s'),
            "codigo"     => "FIN-1391"
        ),
        array(
            'fabrica'   => array(101,151),
            'icone'     => $icone["computador"],
            'link'      => 'relatorio_nota_fiscal_servico.php',
            'titulo'    => traduz('Confer�ncia de Nota Fiscal de Servi�o'),
            'descr'     => traduz('Permite consultar e alterar os extratos com NF de Servi�o.'),
            "codigo"    => 'FIN-1400'
        ),
        array(
            'fabrica'   => array(120,201),
            'icone'     => $icone["computador"],
            'link'      => 'data_geracao_extrato_cadastro.php',
            'titulo'    => traduz('Data Gera��o Extrato'),
            'descr'     => traduz('Definir a data de gera��o de extrato para um determinado posto ou toda a rede autorizada.'),
            "codigo"    => 'FIN-1410'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["cadastro"],
            'link'      => 'lancamentos_os_prestacao_servico.php',
            'titulo'    => traduz('Libera��o de OS'),
            'descr'     => traduz('OS(s) oriundas de postos cadastrados como Presta��o de Servi�o.'),
            "codigo"    => 'FIN-1420'
        ),
        array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["computador"],
            'link'      => 'relatorio_lgr_webservice.php',
            'titulo'    => traduz('Verificar Itens Pendentes de Devolu��o'),
            'descr'     => traduz('Lista todas os itens pendentes de devolu��o por parte dos postos autorizados.'),
            "codigo"    => 'FIN-1430'
        ),
        array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["computador"],
            'link'      => 'relatorio_lgr_webservice_consulta.php',
            'titulo'    => traduz('Consulta de Devolu��es de Pe�as'),
            'descr'     => traduz('Consulta os lan�amentos de Devolu��o efetuada pelos postos autorizados.'),
            "codigo"    => 'FIN-1440'
        ),
	array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["computador"],
            'link'      => 'executa_rotina_manual.php',
            'titulo'    => 'Gerar Extrato Manual',
            'descr'     => 'Gera��o de extrato.',
            "codigo"    => 'FIN-1450'
        ),
        'link' => 'linha_de_separa��o',
    ),

    // Sec�o RELAT�RIOS DE EXTRATOS - Geral
    'secao2' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('RELAT�RIOS'),
        ),
        array(
            'fabrica'=> array(81),
            'icone'     => $icone["upload"],
            'link'      => 'relatorio_ressarcimento.php',
            'titulo'    => traduz('Baixar Ressarcimento'),
            'descr'     => traduz('Baixar Ressarcimento de Ordem de Servi�o.'),
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
            'descr'     => traduz('Relat�rio dos extratos baixados.'),
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
            'titulo'    => traduz('Dados Banc�rios para Pagamento'),
            'descr'     => traduz('Todas as informa��es banc�rias para pagamentos dos postos autorizados'),
            "codigo" => 'FIN-2050'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["print"],
            'link'      => 'etiqueta_posto.php',
            'titulo'    => traduz('Etiquetas de Endere�o'),
            'descr'     => traduz('Imprime etiquetas com o endere&ccedil;o dos postos selecionados.'),
            "codigo" => 'FIN-2060'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_custo_tempo.php',
            'titulo'    => traduz('Custo Tempo de Extratos'),
            'descr'     => traduz('Neste relat�rio cont�m as OS e seus respectivos Custo Tempo por um determinado per�odo.'),
            "codigo" => 'FIN-2070'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_extrato_aprovado.php',
            'titulo'    => traduz('Tempo de An�lise de Extratos'),
            'descr'     => traduz('Informa a quantidade de tempo para an�lise do extrato'),
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
            'descr'     => traduz('Relat�rio de OSs e seus produtos e valor pagos por per�odo.'),
            "codigo" => 'FIN-2100'
        ),
        array(
            'fabrica_no'=> array(20,121,147),   //retirado a pedido de Andre chamado 2254
            'icone'     => $icone["relatorio"],
            'link'      => 'extrato_pagamento_peca.php',
            'titulo'    => traduz('Pe�a X Custo'),
            'descr'     => traduz('Relat�rio de OSs e seus produtos e valor pagos por pe�a.'),
            "codigo" => 'FIN-2110'
        ),
        array(
            'fabrica'   => array(14),
            'icone'     => $icone["relatorio"],
            'link'      => 'faturamento_posto_peca.php',
            'titulo'    => traduz('Faturamento Produto'),
            'descr'     => traduz('Relat�rio de faturamento por produto, fam�lia e per�odo.'),
            "codigo" => 'FIN-2120'
        ),
        array(
            'fabrica_no'=> array(20,121),   //retirado a pedido de Andre chamado 2254
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_field_call_rate_produto_custo.php',
            'titulo'    => traduz('Field Call Rate de Produto X Custo'),
            'descr'     => traduz('Relat�rio de Field Call Rate de Produtos e valor pagos por per�odo.'),
            "codigo" => 'FIN-2130'
        ),
        array(
            'fabrica_no'=> array(20,121),   //retirado a pedido de Andre chamado 2254
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_field_call_rate_familia_custo.php',
            'titulo'    => traduz('Field Call Rate Fam�lia de Produto X Custo'),
            'descr'     => traduz('Relat�rio de Field Call Rate de Fam�lia e valor pagos por per�odo.'),
            "codigo" => 'FIN-2140'
        ),
        array(
            'fabrica_no'=> array(121),
            'icone'     => $icone["relatorio"],
            'link'      => 'posto_extrato_ano.php',
            'titulo'    => traduz('Comparativo Anual de M�dia de Extrato'),
            'descr'     => traduz('Valor mensal dos extratos do posto num per�odo de 12 meses.'),
            "codigo" => 'FIN-2150'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_extrato_semestral_bosch.php',
            'titulo'    => traduz('Controle de Garantia Semestral'),
            'descr'     => traduz('Relat�rio semestral com: total de OSs, total de pe�as, total de m�o de obra, total pago e m�dia por os.'),
            "codigo" => 'FIN-2160'
        ),
        //  24472 - Francisco Ambrozio (4/8/08) - Inclu�do link Relat�rio O,
        //          Conferidas por Linha - Britania.
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_conferida_linha.php',
            'titulo'    => traduz('Relat�rio de OSs Conferidas'),
            'descr'     => traduz('Relat�rio de ordens de servi�o conferidas por linha.'),
            "codigo" => 'FIN-2170'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_fluxo_os.php',
            'titulo'    => traduz('Relat�rio Fluxo de OSs'),
            'descr'     => traduz('Relat�rio de fluxo de OS.'),
            "codigo" => 'FIN-2180'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_field_call_rate_gastos_postos.php',
            'titulo'    => traduz('Relat�rio de M�o-de-obra'),
            'descr'     => traduz('Relat�rio de pagamento de m�o-de-obra por posto, per�odo e produto.'),
            "codigo" => 'FIN-2190'
        ),
        array(
            'fabrica'   => array(30),/*hd: 91609*/
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_extrato_detalhado_esmaltec.php',
            'titulo'    => traduz('Relat�rio de Extrato Detalhado'),
            'descr'     => traduz('Valor dos extratos com filtro de fam�lia e como resultado os detalhes de valor de m�o de obra, pe�as e Km.'),
            "codigo" => 'FIN-2200'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(2),
            'icone'     => $icone["consulta"],
            'link'      => 'extrato_posto_devolucao_controle.php',
            'titulo'    => traduz('Controle de Notas de Devolu��o'),
            'descr'     => '<strong>'.traduz('EM TESTE').'</strong>'.traduz('Consulta ou confirme notas fiscais de devolu��o.').'',
            "codigo" => 'FIN-2210'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pgto_mo.php',
            'titulo'    => traduz('Relat�rio de M�o-de-Obra'),
            'descr'     => traduz('Relat�rio de pagamento de m�o-de-obra por posto, per�odo e produto.'),
            "codigo" => 'FIN-2220'
        ),
        array(
            'fabrica'   => array(5),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_mobra_relacao.php',
            'titulo'    => traduz('Relat�rio Custo x Posto'),
            'descr'     => traduz('Relat�rio do total de produto e m�o-de-obra pagos por posto nas rela��es ME, MK, ML/MC.'),
            "codigo" => 'FIN-2230'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_parametros_finalizada.php',
            'titulo'    => traduz('Relat�rio OS Finalizada + M�o-de-Obra'),
            'descr'     => traduz('Relat�rio Ordens de Servi�o finalizadas com m�o-de-obra e pe�as aplicadas'),
            "codigo" => 'FIN-2240'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'extrato_peca_retorno_obrigatorio.php',
            'titulo'    => traduz('Relat�rio Devolu��o Obrigat�ria'),
            'descr'     => traduz('Relat�rio de Pe�as de Retorno Obrigat�rio'),
            "codigo" => 'FIN-2250'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'extrato_documento_consulta.php',
            'titulo'    => traduz('Relat�rio de Pend�ncia de Documento'),
            'descr'     => traduz('Relat�rio de Todas as Pend�ncias Lan�adas nos Extratos.'),
            "codigo" => 'FIN-2260'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(14),
            'icone'     => $icone["relatorio"],
            'link'      => 'extrato_excluido.php',
            'titulo'    => traduz('Relat�rio dos extratos exclu�dos'),
            'descr'     => traduz('Relat�rio que mostram os extratos exclu�dos.'),
            "codigo" => 'FIN-2270'
        ),
        array(
            'fabrica'   => array(14),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_recusada.php',
            'titulo'    => traduz('Relat�rio das OSs Recusadas'),
            'descr'     => traduz('Relat�rio que mostram a quantidade das OSs recusada do extrato.'),
            "codigo" => 'FIN-2280'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(14),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_sem_extrato.php',
            'titulo'    => traduz('Relat�rio de OS sem extrato'),
            'descr'     => traduz('Relat�rio de Ordens de servi�o que n�o entraram em nenhum extrato por algum motivo (ex. os pedidos s�o inferior a R$ 3,00).'),
            "codigo" => 'FIN-2290'
        ),
        array(
            'fabrica'   => array(5,45),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_lancamento_avulso.php',
            'titulo'    => traduz('Relat�rio dos Lan�amentos Avulsos'),
            'descr'     => traduz('Relat�rio que mostram os lan�amentos avulsos.'),
            "codigo" => 'FIN-2300'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_defeito_constatado_mo.php',
            'titulo'    => traduz('Relat�rio de M�o-de-Obra DEWALT'),
            'descr'     => traduz('Relat�rio que mostra a m�o-de-obra por defeito constatado da linha Dewalt.'),
            "codigo" => 'FIN-2310'
        ),
        array(
            'fabrica'   => array(80),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_sem_extrato_new.php',
            'titulo'    => traduz('Relat�rio de Previs�o de M�o de Obra'),
            'descr'     => traduz('Relat�rio de OS Finalizadas e mostrando o valor de M�o-de-Obra antes de entrar no extrato.'),
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
            'titulo'    => traduz('Relat�rio Car�ncia 90 dias'),
            'descr'     => traduz('Relat�rio de Os com car�ncia de 90 dias.'),
            'codigo'    => 'FIN-2340'

        ),
        array(
            'fabrica'   => array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_km_mo.php',
            'titulo'    => traduz('Relat�rio KM/M�o de Obra'),
            'descr'     => traduz('Mostra por postos valores de KM e M�o de Obra.'),
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
            'descr'   => traduz('Relat�rio para confer�ncia das informa��es dos Extratos'),
            "codigo"  => "FIN-2350"
        ),
        array(
            'fabrica' => array(175),
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_extrato_pago_x_pendente_pagamento.php',
            'titulo'  => traduz('Relat�rio de extratos pagos x pendente de pagamento'),
            'descr'   => traduz('Relat�rio para confer�ncia dos extratos que j� foram pagos e os extratos que ainda est�o pendente de pagamento.'),
            "codigo"  => "FIN-2360"
        ),
        array(
            'fabrica' => [3],
            'icone'   => $icone["relatorio"],
            'link'    => 'acompanhamento_conferencia_lgr.php',
            'titulo'  => traduz('Painel de confer�ncia de MO'),
            'descr'   => traduz('Acompanhamento confer�ncia LGR dos postos'),
            "codigo"  => "FIN-2370"
        ),
        array(
            'fabrica' => [158],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_extrato_os_atendida.php',
            'titulo'  => traduz('Extrato por OS Atendida'),
            'descr'   => traduz('Relat�rio de extrato por OS atendida'),
            "codigo"  => "FIN-2380"
        ),
	 array(
            'icone'   => $icone["relatorio"],
            'link'    => 'previsao_proximo_extrato.php',
            'titulo'  => 'Previs�o Pr�ximo Extrato',
            'descr'   => 'Relat�rio de previs�o de valores a serem pagos no pr�ximo extrato',
            "codigo"  => "FIN-2390"
        ),
        'link' => 'linha_de_separa��o',
    ),

    // Sec�o NOVO EXTRATO - Brit�nia
    'secao3' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('NOVO SISTEMA DE EXTRATO'),
            'fabrica'   => array(3)
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'extrato_posto_britania_novo_processo.php',
            'titulo'    => traduz('Confer�ncia de Extratos de POSTOS'),
            'descr'     => traduz('Permite visualizar os extratos dos postos e realizar a confer�ncia das OSs.'),
            "codigo" => 'FIN-3010'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'lancamento_nf_avulsa.php',
            'titulo'    => traduz('Lan�amento de Nota Fiscal Avulsa'),
            'descr'     => traduz('Permite lan�ar um nota fiscal para o posto autorizado. '),
            "codigo" => 'FIN-3011'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'sinalizador_os.php',
            'titulo'    => traduz('Sinalizador'),
            'descr'     => traduz('Gerencia o status e op��es para sinalizar as OSs.'),
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
            'titulo'    => traduz('Lan�amento nota fiscal'),
            'descr'     => traduz('Lan�a dados da nota fiscal emitida pelo posto e dados de pagamento.'),
            "codigo" => 'FIN-3050'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_conferida_linha_novo.php',
            'titulo'    => traduz('Relat�rio de OSs Conferidas'),
            'descr'     => traduz('Relat�rio de ordens de servi�o conferidas por linha.'),
            "codigo" => 'FIN-3060'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_fechamento_automatico.php',
            'titulo'    => traduz('Fechamento Autom�tico de OS'),
            'descr'     => traduz('Relat�rio para consulta de OS fechadas automaticamente pelo sistema.'),
            "codigo" => 'FIN-3070'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'aprova_pag_mo.php',
            'titulo'    => traduz('Aprova��o t�cnica pagamento de m�o de obra'),
            'descr'     => traduz('Aprovar ou reprovar os extratos agrupados para pagamento de m�o de obra.'),
            "codigo" => 'FIN-3080'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pdf_extrato_gerados.php',
            'titulo'    => traduz('Relatorio de PDF Gerados'),
            'descr'     => traduz('Relat�rio para os PDFs gerados no relatorio FIN-3010.'),
            "codigo" => 'FIN-3090'
        ),        
        'link' => 'linha_de_separa��o',
    ),

    // Sec�o COBRAN�A - Brit�nia
    'secao4' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('COBRAN�A'),
            'fabrica'   => array(3)
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'cobranca_busca.php',
            'titulo'    => traduz('Cobran�a'),
            'descr'     => traduz('Lista notas para a cobran�a.'),
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
            'titulo'    => traduz('D�bito detalhado'),
            'descr'     => traduz('Gerencia tipos de d�bito.'),
            "codigo" => 'FIN-4030'
        ),
        'link' => 'linha_de_separa��o',
    ),

    // Sec�o CADASTRO - Britania
    'secao5' => array (
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('CADASTRO'),
            'fabrica'   => array(3)
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'acrescimo_mo_prazo_cadastro.php',
            'titulo'    => traduz('Cadastro de m�o-de-obra diferenciada'),
            'descr'     => traduz('Cadastro de m�o-de-obra diferenciada por prazo de atendimento.'),
            "codigo" => 'FIN-5010'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_contas_postos.php',
            'titulo'    => traduz('Cadastro de Contas dos Postos'),
            'descr'     => traduz('Manuten��o de contas dos postos.'),
            "codigo" => 'FIN-5020'
        ),

        'link' => 'linha_de_separa��o',
    ),
);

