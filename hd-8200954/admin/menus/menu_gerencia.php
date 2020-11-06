<?php
include_once 'funcoes.php';

msgBloqueioMenu();

// Fabricas que tem distribuição via DISTRIB Telecontrol
$fabrica_distrib = array(51, 81, 114, 147);

//HD 666788 - Funcionalidades por admin
$sql = "SELECT fabrica
      FROM tbl_funcionalidade
     WHERE fabrica=$login_fabrica OR fabrica IS NULL";
$res = pg_query($con,$sql);
$fabrica_funcionalidades_admin = (pg_num_rows($res)>0);
/*
	hd-1149884 -> Para as fábricas que tiverem o parâmetro adicional fabrica_padrao='t', a tela:
	http://posvenda.telecontrol.com.br/assist/admin/os_consulta_procon.php
	Não serão mais utilizadas.
*/

if ($fabrica_padrao=='t') {
	$arr_fabrica_padrao = array($login_fabrica);
}
if ($telecontrol_distrib=='t') {
	$arr_fabrica_distrib = array($login_fabrica);
	$fabrica_relatorio_ratreio = array($login_fabrica);
}else{
     $arr_fabrica_distrib = array(0);
    $fabrica_relatorio_ratreio = array(0);
}
$relatorio_os = ($novaTelaOs) ? array($login_fabrica) : array(0);
// Seção CREDENCIAMENTO - Geral

if($replica_einhell){
	$fabricas_replica_einhell[] = $login_fabrica;
}else{
	$fabricas_replica_einhell[] = array();
}

if (in_array($login_fabrica, [169,170])) {
		
	$riMirror = new \Mirrors\Ri\RiMirror($login_fabrica,$login_admin);

	$arrPermissoesAdm = $riMirror->getAdminPermissoes();

}

return array(
	'secaoRI' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('Relatório Informativo'),
			'fabrica'    => $arrPermissoesAdm["analise_ri"] == "t" ? [169,170] : []
		),
		array(
			'fabrica' => array(169,170),
			'icone'   => $icone["consulta"],
			'link'    => 'consulta_relatorio_informativo.php',
			'titulo'  => traduz('Consulta dos RI\'s'),
			'descr'   => traduz("Consulta dos Relatórios informativos."),
			"codigo"  => 'GER-18000'
		),
		array(
			'fabrica' => array(169,170),
			'icone'   => $icone["computador"],
			'link'    => 'cadastro_relatorio_informativo.php',
			'titulo'  => traduz('Preenchimento do RI'),
			'descr'   => traduz('Preenchimento do relatório informativo'),
			"codigo"  => 'GER-18010'
		),
		array(
			'fabrica' => array(169,170),
			'icone'   => $icone["usuario"],
			'link'    => 'grupo_followup_relatorio_informativo.php',
			'titulo'  => traduz('Grupos de Follow-up'),
			'descr'   => traduz('Amarração dos admins com os grupos de follow-up para o relatório informativo'),
			"codigo"  => 'GER-18020'
		),
		'link' => 'linha_de_separação',
	),
	'secao0' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('CREDENCIAMENTO DE ASSISTÊNCIAS TÉCNICAS'),
			'fabrica_no' => array(87),
			'fabrica'    => array(24, 25, 47)
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["computador"],
			'link'    => 'credenciamento_suggar.php',
			'titulo'  => traduz('Credenciamento de Assistências Técnicas'),
			'descr'   => traduz('Credenciamento e Descredenciamento de Assistências Técnicas.'),
			"codigo"  => 'GER-0010'
		),
		array(
			'fabrica' => array(25),
			'icone'   => $icone["computador"],
			'link'    => '../credenciamento/hbtech/index_.php',
			'titulo'  => traduz('Credenciamento de Assistências Técnicas'),
			'descr'   => traduz('Credenciamento e Descredenciamento de Assistências Técnicas.'),
			"codigo"  => 'GER-0020'
		),
		array(
			'fabrica' => array(25),
			'icone'   => $icone["computador"],
			'link'    => '../credenciamento/gera_contrato_crown.php',
			'titulo'  => traduz('Contrato Assistências Técnicas'),
			'descr'   => traduz('Contrato para Assistências Técnicas.'),
			"codigo"  => 'GER-0030'
		),
		array(
			'fabrica' => array(25),
			'icone'   => $icone["computador"],
			'link'    => 'credenciamento_lista.php',
			'titulo'  => traduz('Acompanhamento do recadastramento'),
			'descr'   => traduz('Listagem dos postos que receberam o e-mail de recadastramento.'),
			"codigo"  => 'GER-0040'
		),
		'link' => 'linha_de_separação',
	),
	'secaoPesquisa' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('PESQUISAS DO FABRICANTE'),
			'fabrica'    => ($usaEasyBuilder && $login_fabrica != 42) ? [$login_fabrica] : []
		),
		array(
            'icone'   => $icone['cadastro'],
            'link'    => 'questionario_avaliacao.php',
            'titulo'  => 'Cadastro Formulário de Pesquisa',
            'descr'   => 'Permite o cadastro de um formulário de pesquisas dinâmico',
            'codigo'  => 'GER-20000'
        ),
	    array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_respostas_pesquisa.php',
			'titulo'  => 'Relatório de Respostas da Pesquisa',
			'descr'   => 'Relatório detalhado com as respostas da pesquisa',
			"codigo"  => 'GER-20010'
	    ),
		'link' => 'linha_de_separação',
	),
	// Seção CADASTRO DE FABRICANTES - Interno Telecontrol
	'secao1' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('RELATÓRIOS'),
			'fabrica' => array(10)
		),
		array(
			'admin'  => array(398, 432, 435), //São admins da fábrica Telecontrol...
			'icone'  => $icone["cadastro"],
			'link'   => 'fabricante_cadastro.php',
			'titulo' => traduz('Cadastro de fábricas'),
			'descr'  => traduz('Cadastro de fabricantes pela página.'),
			"codigo" => 'GER-1010'
		),
		array(
			'icone'  => $icone["cadastro"],
			'link'   => 'posto_credenciamento.php',
			'titulo' => traduz('Credenciar Autorizada'),
			'descr'  => traduz('Cadastramento da rede autorizada para este fabricante.'),
			"codigo" => 'GER-1020'
		),
		'link' => 'linha_de_separação',
	),
	// Seção assinatura, so black
	'secao4' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('APROVAÇÕES GERÊNCIA'),
			'fabrica' => array(1)
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["computador"],
			'link'    => 'extrato_assinatura.php',
			'titulo'  => traduz('Extratos'),
			'descr'   => traduz('Assinatura eletrônica de liberação de extratos para o financeiro.'),
			"codigo"  => 'GER-1430'
		),	
		array(
			'fabrica' => array(1),
			'icone'   => $icone["computador"],
			'link'    => 'aprova_solicitacao_cheque.php',
			'titulo'  => traduz('Solicitação de Cheque'),
			'descr'   => traduz('Permite consultar, imprimir e aprovar as solicitações de cheque reembolso'),
			"codigo"  => 'GER-2350'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["computador"],
			'link'    => 'aprova_contrato_online.php',
			'titulo'  => traduz('Cadastro de Posto Autorizado'),
			'descr'   => traduz('Cadastro de Posto Autorizado'),
			"codigo"  => 'GER-2360'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["computador"],
			'link'    => 'aprova_cancelamento.php',
			'titulo'  => traduz('Descredenciamento de Posto autorizado'),
			'descr'   => traduz('Descredenciamento de Posto autorizado'),
			"codigo"  => 'GER-2370'
		),
	),
	// Seção CONSULTAS - Geral
	'secao2' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('CONSULTAS'),
			'fabrica_no' => array(87, 108, 111)
		),
		array(
			'icone'  => $icone["consulta"],
			'link'   => 'os_parametros.php',
			'titulo' => traduz('Consulta Ordens de Serviço'),
			'descr'  => traduz('Consulta OS Lançadas'),
			"codigo" => 'GER-2010'
		),
		array(
			'icone'  => $icone["consulta"],
			'link'   => 'pedido_parametros.php',
			'titulo' => traduz('Consulta Pedidos de Peças'),
			'descr'  => traduz('Consulta pedidos efetuados por postos autorizados.'),
			"codigo" => 'GER-2020'
		),
		array(
			'fabrica_no' => array(122),
			'icone'      => $icone["consulta"],
			'link'       => 'acompanhamento_os_revenda_parametros.php',
			'titulo'     => ($login_fabrica == 178)? traduz('Acompanhamento de OS') : traduz('Acompanhamento de OS Revenda'),
			'descr'      => traduz('Consulta OS de Revenda Lançadas e Finalizadas'),
			"codigo"     => 'GER-2030'
		),
		array(
			'fabrica' => array(43),
			'icone'   => $icone["consulta"],
			'link'    => 'status_os_posto.php',
			'titulo'  => traduz('Acompanhamento de OS em aberto'),
			'descr'   => traduz('Consulta status das Ordens de Serviço em aberto'),
			"codigo"  => 'GER-2040'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["consulta"],
			'link'    => 'os_enviadas_tectoy.php',
			'titulo'  => traduz('OS com peças enviadas a fábrica'),
			'descr'   => traduz('Consulta OSs que o posto enviou peças para a fábrica. Autoriza ou não o pagamento de metade da mão-de-obra.'),
			"codigo"  => 'GER-2050'
		),
		array(
			'fabrica' => array(6,91),
			'icone'   => $icone["cadastro"],
			'link'    => 'manutencao_periodo_visualizacao_extrato_posto.php',
			'titulo'  => traduz('Período visualização extrato.'),
			'descr'   => traduz('Visualizar / Alterar o período que é demonstrado para cada posto.'),
			"codigo"  => 'GER-2051'
		),
		array(
			'fabrica' => array(3), // HD 55242
			'icone'   => $icone["consulta"],
			'link'    => 'os_consulta_agrupada.php',
			'titulo'  => traduz('Consulta Ordem de Serviço Agrupada'),
			'descr'   => traduz('Consulta OS agrupada.'),
			"codigo"  => 'GER-2060'
		),
		array(
			'fabrica' => array(1),
			'admin'   => 236,
			'icone'   => $icone["computador"],
			'link'    => 'os_consulta_lite_etiqueta.php',
			'titulo'  => traduz('Consulta OSs e gera etiquetas'),
			'descr'   => traduz('Transferência de OS entre postos'),
			"codigo"  => 'GER-2070'
		),
		array(
			'fabrica' => array(14),
			'icone'   => $icone["computador"],
			'link'    => 'os_transferencia.php',
			'titulo'  => traduz('Transferência de OS'),
			'descr'   => traduz('Transferência de OS entre postos'),
			"codigo"  => 'GER-2080'
		),
		array(
			'fabrica' => array(7),
			'icone'   => $icone["computador"],
			'link'    => 'os_transferencia_filizola.php',
			'titulo'  => traduz('Transferência de OS'),
			'descr'   => traduz('Transferência de OS entre postos'),
			"codigo"  => 'GER-2090'
		),
		array(
			'fabrica' => array(51),
			'icone'   => $icone["consulta"],
			'link'    => 'estoque_consulta.php',
			'titulo'  => traduz('Consulta de estoque'),
			'descr'   => traduz('Consulta de estoque da Telecontrol.'),
			"codigo"  => 'GER-2100'
		),
		array(
			'fabrica' => array(128),
			'icone'   => $icone["consulta"],
			'link'    => 'relatorio_estoque_pecas_entrada_saida.php',
			'titulo'  => traduz('Relatório Estoque de Peças - Entrada e Saida'),
			'descr'   => traduz('Consulta de estoque da Peças e sua Movimentação(Entrada/Saida).'),
			"codigo"  => 'GER-2100'
		),
	array(
			'fabrica' => array(24),
			'icone'   => $icone["consulta"],
			'link'    => 'relatorio_tempo_os_finalizada.php',
			'titulo'  => traduz('Relatório tempo OS finalizada'),
			'descr'   => traduz('Consulta tempo OS finalizada'),
			"codigo"  => 'GER-2110'
		),
	array(
			'fabrica' => array(11,24,42,81,172,183),
			'icone'   => $icone["cadastro"],
			'link'    => 'cadastro_processos.php',
			'titulo'  => traduz('Cadastro de Processos'),
			'descr'   => traduz('Cadastramento de processos Jurídicos.'),
			"codigo"  => 'GER-2111'
		),
	array(
			'fabrica' => array(11,24,42,81,172,183),
			'icone'   => $icone["consulta"],
			'link'    => 'consulta_processos.php',
			'titulo'  => traduz('Consulta de Processos'),
			'descr'   => traduz('Consulta os Processos cadastrados.'),
			"codigo"  => 'GER-2112'
		),
		'link' => 'linha_de_separação',
	),
	// Seção RELATÓRIOS - Geral
	'secao3' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('RELATÓRIOS'),
			'fabirca_no' => array(87)
		),
		array(
			'fabrica'    => array(3),
			'icone'      => $icone["relatorio"],
			'background' => '#AAAAAA',
			'link'       => '#relatorio_lancamentos..php',
			'titulo'     => traduz('Lançamentos'),
			'descr'      => traduz('Postos que estão lançando ordens de serviço no site.'),
			"codigo"     => 'GER-3010'
		),
		array(//HD 44656
			'fabrica' => array(14,15,43,66),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto.php',
			'titulo'  => traduz('Field Call-Rate - Produtos'),
			'descr'   => traduz('Percentual de quebra de produtos.<br><i>Considera apenas ordem de serviço fechada, apresentando custos</i>'),
			"codigo"  => 'GER-3020'
		),
		array(
			'fabrica' => array(96),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_grafico_rel_os_finalizada.php',
			'titulo'  => traduz('OS abertas em Garantia e Fora de Garantia'),
			'descr'   => traduz('Este Relatório mostra através de gráficos as OS abertas dentro e fora de garantia'),
			"codigo"  => 'GER-3030'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_indice_defeito.php',
			'titulo'  => traduz('Relatório de Índice de Defeito de Campo'),
			'descr'   => traduz('Este relatório contempla o índice de defeitos de Campo.'),
			"codigo"  => 'GER-3040'
		),
		array(
			'fabrica' => array(94),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_periodo.php',
			'titulo'  => traduz('Relatório de OS por Período'),
			'descr'   => traduz('Este relatório considera a Data de Fechamento das Ordens de Serviço.'),
			"codigo"  => 'GER-3050'
		),
		array(
			'fabrica' => array(94),
			'icone'   => $icone["relatorio"],
			'link'    => 'indice_ocorrencia_mensal.php',
			'titulo'  => traduz('Relatório de Índice de Ocorrência Mensal'),
			'descr'   => traduz('Este relatório contempla o índice de ocorrência de defeitos no intervalo de tempo determinado pelo usuário.'),
			"codigo"  => 'GER-3060'
		),
		array(
			'fabrica' => array_merge($arr_fabrica_distrib, [141,174]),
			'icone'   => $icone["bi"],
			'link'    => 'relatorio_status_os_tempo.php',
			'titulo'  => traduz('Relatório de Timeline de O.S'),
			'descr'   => traduz('Relatório que apresenta o tempo da O.S em cada Status'),
			"codigo"  => 'GER-3069'
		),
		array(
			'fabrica' => array_merge($arr_fabrica_distrib, [141,174]),
			'icone'   => $icone["bi"],
			'link'    => 'relatorio_status_pedido_tempo.php',
			'titulo'  => traduz('Relatório de Timeline de Pedido'),
			'descr'   => traduz('Relatório que apresenta o tempo da O.S em cada Status'),
			"codigo"  => 'GER-3069'
		),
		array(
			'icone'      => $icone["bi"],
			'link'       => 'bi/fcr_os.php',
			'titulo'     => traduz('BI -Field Call Rate - Produtos'),
			'descr'      => traduz('Percentual de quebra de produtos.<br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i>'),
			"codigo"     => 'GER-3070',
			"fabrica_no" => array(138)
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["bi"],
			'link'    => 'bi/fcr_os_detalhado.php',
			'titulo'  => traduz('BI -Field Call Rate - Detalhado'),
			'descr'   => traduz('Detalhamento do Field Call Rate Produtos para Auditoria.<br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i>'),
			"codigo"  => 'GER-3080'
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["bi"],
			'link'    => 'bi/fcr_os_detalhado_peca.php',
			'titulo'  => traduz('BI -Field Call Rate - Defeitos'),
			'descr'   => traduz('Detalhamento do Field Call Rate Produtos e peças com defeito, para Auditoria.<br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i>'),
			"codigo"  => 'GER-3090'
		),
		array(
			'fabrica' => array(3, 24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto2.php',
			'titulo'  => traduz('Field Call Rate - Produtos 2'),
			'descr'   => traduz('Relatório do percentual de defeitos das peças por produto.'),
			"codigo"  => 'GER-3100'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto3_britania.php',
			'titulo'  => traduz('Field Call Rate - Produtos 3'),
			'descr'   => traduz('Considera OS lançadas no sistema filtrando pela data da digitação ou finalização.'),
			"codigo"  => 'GER-3110'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto3.php',
			'titulo'  => traduz('Field Call Rate - Produtos 3'),
			'descr'   => traduz('Considera OS lançadas no sistema filtrando pela data da digitação ou finalização.'),
			"codigo"  => 'GER-3120'
		),
		array(
			'fabrica_no' => $fabricas_contrato_lite,
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_field_call_rate_produto_lista_basica.php',
			'titulo'     => traduz('Field Call Rate - Produtos Lista Básica'),
			'descr'      => traduz('Relatório de quebras de peças da lista básica do produto'),
			"codigo"     => 'GER-3130'
		),
		array(
			'fabrica' => array(66,14),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_posto.php',
			'titulo'  => traduz('Field Call Rate - Postos'),
			'descr'   => traduz('Relatório de ocorrência de OS por familia por postos.'),
			"codigo"  => 'GER-3140'
		),
		array(
			'fabrica_no' => array($bi_peca),
			'icone'      => $icone["bi"],
			'link'       => 'bi/fcr_pecas.php',
			'titulo'     => ($login_fabrica==24) ? traduz('Field Call-Rate - Produtos 4') : traduz('BI Field Call-Rate - Peças'),
			'descr'      => traduz('Percentual de quebra de peças.<br><i>O BI é atualizado com as informações do dia anterior, portanto tem um dia de atraso!</i>'),
			"codigo"     => 'GER-3150'
		),
		array(
			'fabrica' => array(14, 66),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_defeito_constatado.php',
			'titulo'  => traduz('Field Call Rate - Defeitos Constatados'),
			'descr'   => traduz('Relatório de ocorrência de OS por defeitos constatados.'),
			"codigo"  => 'GER-3160'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeitos.php',
			'titulo'  => traduz('Relatório de defeitos'),
			'descr'   => traduz('Relatório de defeitos de produtos e soluções a partir da data de digitação'),
			"codigo"  => 'GER-3170'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_engenharia_serie.php',
			'titulo'  => traduz('Relatório de defeitos por Nº série'),
			'descr'   => traduz('Relatório de defeitos de produtos e soluções a partir do número de série'),
			"codigo"  => 'GER-3180'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_serie_reoperado.php',
			'titulo'  => traduz('Relatório Nº série Reoperado'),
			'descr'   => traduz('Relatório de número de séries reoperados.'),
			"codigo"  => 'GER-3190'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_serie_fabricacao.php',
			'titulo'  => traduz('Field Call-Rate - Produtos Número de Série'),
			'descr'   => traduz('Relatório de quebra dos produtos pela data de fabricação.'),
			"codigo"  => 'GER-3200'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto_grupo.php',
			'titulo'  => traduz('Field Call-Rate - Produtos Número de Série 2'),
			'descr'   => traduz('Relatório de quebra dos produtos X número de série.'),
			"codigo"  => 'GER-3210'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto_pecas.php',
			'titulo'  => traduz('Field Call-Rate - Mão-de-obra Produtos X Peças'),
			'descr'   => traduz('Relatório mão-de-obra por produto e troca de peça específicos.'),
			"codigo"  => 'GER-3220'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_pecas.php',
			'titulo'  => traduz('Relatório Troca de Peça'),
			'descr'   => traduz('Relatório de peças trocadas pelo posto autorizado, peças trocadas em garantia ou pagas pelos clientes'),
			"codigo"  => 'GER-3230'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_sem_troca_peca.php',
			'titulo'  => traduz('Relatório de OS sem troca de Peça'),
			'descr'   => traduz('Relatório em ordem de posto autorizado com maior índice de Ordens de Serviços sem troca de peça.'),
			"codigo"  => 'GER-3240'
		),
		array(
			'fabrica_no' => array(81,114),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_os_peca_sem_pedido.php',
			'titulo'     => traduz('Relatório de OS de Peça sem Pedido'),
			'descr'      => traduz('Relatório em ordem de posto autorizado com maior índice de Ordens de Serviços de peça sem pedido.'),
			"codigo"     => 'GER-3250'
		),
		array(
			'fabrica' => array(50,158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_gerencial_oss.php',
			'titulo'  => traduz('Relatório Gerencial de OS'),
			'descr'   => traduz('Relatório que contem as Ordens de Serviços pendentes de varios períodos.'),
			"codigo"  => 'GER-3255'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(175)),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_peca_sem_preco.php',
			'titulo'     => traduz('Relatório de Peça em OS sem Preço'),
			'descr'      => traduz('Relatório que mostra as peças que estão cadastradas em uma OS mas não possuem preço cadastrado.'),
			"codigo"     => 'GER-3260'
		),
		array(
			'fabrica' => array(106,115,116,117,120,201,121,122,127,134,169,170),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_reincidente.php',
			'titulo'  => traduz('Relatório de OSs reincidentes'),
			'descr'   => traduz('Relatório de Ordens de Serviço Reincidentes'),
			"codigo"  => 'GER-3270'
		),
		array(
			'fabrica' => array(40,106,111,108),
			'icone'   => $icone["relatorio"],
			'link'    => 'os_mais_tres_pecas.php',
			'titulo'  => traduz('OS com mais de 3 peças'),
			'descr'   => traduz('Relatório para auditoria dos postos que utilizam mais de 3 peças por Ordem de Serviço.'),
			"codigo"  => 'GER-3280'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(14)),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_quantidade_os.php',
			'titulo'     => traduz('Relatório de Quantidade de OSs Aprovadas por LINHA'),
			'descr'      => traduz('Relatório que mostra a quantidade de OS aprovadas por postos em determinadas linhas nos últimos 3 meses.'),
			"codigo"     => 'GER-3290'
		),
		array(
			'fabrica' => array(86),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_devolucao_obrigatoria.php',
			'titulo'  => traduz('Devolução Obrigatória'),
			'descr'   => traduz('Peças que devem ser devolvidas para a Fábrica constando em Ordens de Serviços.'),
			"codigo"  => 'GER-3300'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_devolucao_obrigatoria_tectoy.php',
			'titulo'  => traduz('Total de Peças Devolução Obrigatória'),
			'descr'   => traduz('Total de peças que devem ser devolvidas para a Fábrica.'),
			"codigo"  => 'GER-3310'
		),
		/* array(
			'fabrica'   => array(11,172),
			'icone'     => $icone["relatorio"],
			'link'      => 'relatorio_percentual_defeitos.php',
			'titulo'    => 'Percentual de Defeitos',
			'descr'     => 'Relatório por período de percentual dos defeitos de produtos.',
			"codigo" => 'GER-3320'
		), */
		array(
			'fabrica_no' => $fabricas_contrato_lite,
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_percentual_defeitos.php',
			'titulo'     => traduz('Percentual de Defeitos'),
			'descr'      => traduz('Relatório por período de percentual dos defeitos de produtos.'),
			"codigo"     => 'GER-3330'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_constatado_os_anual.php',
			'titulo'  => traduz('Relatório Anual de OS por Defeitos Constatados'),
			'descr'   => traduz('Relatório anual detalhando por família, grupo de defeito e defeito X mensal e total anual a quantidade de OS, bem como valores das mesmas'),
			"codigo"  => 'GER-3340'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_auditoria.php',
			'titulo'  => traduz('Relatório de Auditoria'),
			'descr'   => traduz('Relatório das Auditorias efetuadas nos postos autorizados'),
			"codigo"  => 'GER-3350'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(158)),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_tempo_conserto_mes.php',
			'titulo'     => traduz('Permanência em conserto no mês'),
			'descr'      => traduz('Relatório que mostra o tempo (dias) de permanência do produto na assistência técnica no mês.'),
			"codigo"     => 'GER-3360'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tempo_os_aberta.php',
			'titulo'  => traduz('Relatorio de OS em abertos em dias'),
			'descr'   => traduz('Relatorio de OS em abertos em dias, considerando a data de abertura para o dia da geração do relatório.'),
			"codigo"  => 'GER-3370'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_atendimento_os.php',
			'titulo'  => traduz('Acompanhamento de Atendimento x OS'),
			'descr'   => traduz('Relatorio de atendimentos em abertos em dias, considerando a data de abertura do atendimento.'),
			"codigo"  => 'GER-3380'
		),
		//liberado para Lenoxx hd 8231
		//liberado para Bosch hd 13373
		//liberado para Ibratele hd 138104
		//liberado para Esmaltec hd 149835
		//liberado para Nova Computadores hd 203875
		//liberado para Latinatec  30-12-2010 Aut. Ébano., solicitado por Rodrigo Torres.
		array(
			'fabrica' => array(8, 11, 15, 20, 30, 43, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tempo_conserto.php',
			'titulo'  => traduz('Permanência em conserto'),
			'descr'   => traduz('Relatório que mostra tempo médio (dias) de permanência do produto na assistência técnica.'),
			"codigo"  => 'GER-3390'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_defeitos_esmaltec.php',
			'titulo'  => traduz('Relatório Defeitos OS por Atendimento'),
			'descr'   => traduz('Relatório de Defeitos OS x Tipo de Atendimento.'),
			"codigo"  => 'GER-3400'
		),
		array(
			'fabrica'    => array(1,2,3,7,66),
			'icone'      => $icone["relatorio"],
			'background' => '#aaaaaa',
			'link'       => '#relatorio_prazo_atendimento_periodo.php',
			'titulo'     => traduz('Período de atendimento da OS'),
			'descr'      => traduz('Relatório de acompanhamento do atendimento por período de OS.'),
			"codigo"     => 'GER-3401'
		),
		array(
			'fabrica' => array(8), //liberado para Ibratele hd 138104
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_prazo_atendimento_periodo.php',
			'titulo'  => traduz('Período de atendimento da OS'),
			'descr'   => traduz('Relatório de acompanhamento do atendimento por período de OS.'),
			"codigo"  => 'GER-3410'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_qualidade.php',
			'titulo'  => traduz('Período de atendimento da OS'),
			'descr'   => traduz('Relatório de acompanhamento do atendimento por período de OS.'),
			"codigo"  => 'GER-3420'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_perguntas_britania.php',
			'titulo'  => traduz('Relatório DVD Fama e Game'),
			'descr'   => traduz('Relatório que mostra a quantidade de P. A. participaram do questionário.'),
			"codigo"  => 'GER-3430'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(24)),
			'icone'      => $icone["relatorio"],
			'link'       => 'produtos_mais_demandados.php',
			'titulo'     => traduz('Produtos mais demandados'),
			'descr'      => traduz('Relatório dos produtos mais demandados em Ordens de Serviços nos últimos meses.'),
			"codigo"     => 'GER-3440'
		),
		array(
			'fabrica' => array(5,14,19,43,66),
			'icone'   => $icone["relatorio"],
			'link'    => 'defeito_os_parametros.php',
			'titulo'  => traduz('Relatório de Ordens de Serviço'),
			'descr'   => traduz('Relatório de Ordens de Serviço lançadas no sistema.'),
			"codigo"  => 'GER-3450'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["consulta"],
			'link'    => 'auditoria_os_fechamento_blackedecker.php',
			'titulo'  => traduz('Auditoria de peças trocadas em garantia'),
			'descr'   => traduz('Faz pesquisas nas Ordens de Serviços previamente aprovadas em extrato.'),
			"codigo"  => 'GER-3460'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_os.php',
			'titulo'  => traduz('Relatório de Troca de OS'),
			'descr'   => traduz('Verifica as OS de troca do PA.'),
			"codigo"  => 'GER-3470'
		),
		array(
			'fabrica' => array(2, 3, 11, 24, 172), //liberado para Lenoxx hd 8231
			'icone'   => $icone["relatorio"],
			'link'    => 'pendencia_posto.php',
			'titulo'  => traduz('Pendências do posto'),
			'descr'   => traduz('Relatório de peças pendentes dos postos.'),
			"codigo"  => 'GER-3480'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produto_defeito_troca.php',
			'titulo'  => traduz('Relatório de Troca de Peças'),
			'descr'   => traduz('Relatório de peças trocas os defeitos apresentados, listado por produtos.'),
			"codigo"  => 'GER-3490'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_serie_reincidente.php',
			'titulo'  => traduz('Relatório OS Série Reincidente'),
			'descr'   => traduz('Relatório OS Série Reincidente.'),
			"codigo"  => 'GER-3500'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_os_reincidente.php',
			'titulo'  => traduz('Relatório Peças Os Reincidente'),
			'descr'   => traduz('Relatório de peças em OS&#39;s Reincidentes.'),
			"codigo"  => 'GER-3510'
		),
		array(
			'disabled' => true,
			'fabrica'  => array(2),
			'icone'    => $icone["relatorio"],
			'link'     => 'extrato_posto_devolucao_controle.php',
			'titulo'   => traduz('Pendências do posto - NF'),
			'descr'    => traduz('Controle de Notas Fiscais de Devolução e Peças'),
			"codigo"   => 'GER-3510'
		),
		array(
			'fabrica' => array(2, 11, 14, 24,  66, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'os_relatorio.php',
			'titulo'  => traduz('Status das Ordens de Serviço'),
			'descr'   => traduz('Status das ordens de serviço'),
			"codigo"  => 'GER-3520'
		),
		array(
			'fabrica'    => array_merge(array(1, 35, 30, 50, 74, 134, 141, 140),$relatorio_os),
			'fabrica_no' => array(147),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_os.php',
			'titulo'     => traduz('Relatório de OS'),
			'descr'      => traduz('Status das ordens de serviço'),
			"codigo"     => 'GER-3520'
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_serie.php',
			'titulo'  => traduz('Relatório de Nº de Série'),
			'descr'   => traduz('Relatório de ocorrência de produtos por nº de série.'),
			"codigo"  => 'GER-3530'
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_serie_ano.php',
			'titulo'  => traduz('Relatório de Nº de Série Anual'),
			'descr'   => traduz('Relatório de ocorrência de produtos por nº de série no período de 12 meses.'),
			"codigo"  => 'GER-3540'
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_producao_serie.php',
			'titulo'  => traduz('Relatório de Produção'),
			'descr'   => traduz('Relatório de produção.'),
			"codigo"  => 'GER-3550'
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_producao_nova_serie.php',
			'titulo'  => traduz('Relatório de Produção Série Nova'),
			'descr'   => traduz('Relatório de produção.'),
			"codigo"  => 'GER-3560'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas_faturadas.php',
			'titulo'  => traduz('Relatório de Peças Faturadas'),
			'descr'   => traduz('Relatório de peças faturadas.'),
			"codigo"  => 'GER-3570'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto_serie.php',
			'titulo'  => traduz('Relatório OS com Nº de Série e Posto'),
			'descr'   => traduz('Relatório Ordens de Serviços lançadas no sistema por produto e por posto, e com número de série.'),
			"codigo"  => 'GER-3580'
		),
		array(
			'fabrica' => array(3, 24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_produto.php',
			'titulo'  => traduz('Relatório Troca de Produto'),
			'descr'   => traduz('Relatório de produto trocado na OS.'),
			"codigo"  => 'GER-3590'
		),
		array(
			'fabrica' => array(3, 24, 66, 81,101, 114),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_produto_total.php',
			'titulo'  => ($login_fabrica != 101) ? traduz('Relatório Troca de Produto Total') : traduz('Relatório Troca de Produto'),
			'descr'   => ($login_fabrica != 101)
			? traduz('Relatório de produto trocado e arquivo .XLS')
			: traduz('Relatório de informações dos produtos trocados e as peças que deram origem às trocas'),
			"codigo"  => 'GER-3600'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_linha.php',
			'titulo'  => traduz('Relatório de OS digitadas por linha'),
			'descr'   => traduz('Relatório de OS digitadas por linha.'),
			"codigo"  => 'GER-3610'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas_mes.php',
			'titulo'  => traduz('Relatório de OS e Pecas digitadas'),
			'descr'   => traduz('Relatório contendo a qtde de OS e Peças digitadas.'),
			"codigo"  => 'GER-3620'
		),
		array(
			'fabrica' => array(127),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_garantia_faturado.php',
			'titulo'  => traduz('Peças faturadas e garantia'),
			'descr'   => traduz('Quantidade de peças enviadas em garantia, comparadas com as peças faturadas.'),
			"codigo"  => 'GER-3630'
		),
		array(
			'fabrica'    => array(3),
			'icone'      => $icone["relatorio"],
			'background' => '#aaaaaa',
			'link'       => '#relatorio_diario.php',
			'titulo'     => traduz('Relatório Diário'),
			'descr'      => traduz('Resumo mensal do Relatório Diário enviado por email.'),
			"codigo"     => 'GER-3640'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_qtde_os.php',
			'titulo'  => traduz('Relatório Qtde OS e Peças Anual'),
			'descr'   => traduz('Relatório Anual de quantidade de OSs e Peças por Data Digitação e Finalização.'),
			"codigo"  => 'GER-3650'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_qtde_os_fabrica.php',
			'titulo'  => traduz('Relatório de OS COM PEÇAS e SEM PEÇAS Anual'),
			'descr'   => traduz('Relatório Anual de quantidade de OSs com peças e sem peças por Data Digitação e Finalização.'),
			"codigo"  => 'GER-3660'
		),
		array(
			'fabrica' => array(8),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produto_por_posto.php',
			'titulo'  => traduz('Produtos por posto'),
			'descr'   => traduz('Relatório de produtos lançados em OS por posto em determinado período.'),
			"codigo"  => 'GER-3670'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["relatorio"],
			'link'    => 'rel_visao_mix_total.php',
			'titulo'  => traduz('Visão geral por produto'),
			'descr'   => traduz('Relatório geral por produto.'),
			"codigo"  => 'GER-3680'
		),
		array(
			'icone'  => $icone["relatorio"],
			'link'   => 'custo_por_os.php',
			'titulo' => traduz('Custo por OS'),
			'descr'  => traduz('Calcula o custo médio de cada posto para realizar os consertos em garantia.'),
			"codigo" => 'GER-3690'
		),
		array(
			'fabrica_no' => array(7),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_quebra_familia.php',
			'titulo'     => traduz('Relatório de Quebra por Família'),
			'descr'      => traduz('Este relatório contém a quantidade de quebra durante os últimos 12 meses levando em conta o fechamento do extrato de cada mês.'),
			"codigo"     => 'GER-3700'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_quebra_linha.php',
			'titulo'  => traduz('Relatório de Quebra por Linha'),
			'descr'   => traduz('Este relatório contém a quantidade de quebra durante os ultimos 12 meses levando em conta o fechamento do extrato de cada mês.'),
			"codigo"  => 'GER-3710'
		),
		array(
			'fabrica' => array(14, 66),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_constatado_os.php',
			'titulo'  => traduz('Relatório de Defeitos Constatados por OS'),
			'descr'   => traduz('Relatório de Defeitos Constatados por Ordem de Serviço.'),
			"codigo"  => 'GER-3720'
		),
		array(
			'fabrica' => array(14, 66),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_os_sem_peca_intelbras.php',
			'titulo'  => traduz('Relatório de OS sem peça'),
			'descr'   => traduz('Relatório de Ordem de Serviço sem peças e seus respectivos defeitos reclamados, defeitos constatados e solução.'),
			"codigo"  => 'GER-3730'
		),
		array(
			'fabrica' => array(14, 66),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_reincidencia.php',
			'titulo'  => traduz('Relatório de OS Reincidente'),
			'descr'   => traduz('Relatório de Ordem de Serviço reincidentes X posto autorizado.'),
			"codigo"  => 'GER-3740'
		),
		array(
			'fabrica' => array(94),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_troca_new.php',
			'titulo'  => traduz('Relatório de OS de Troca'),
			'descr'   => traduz('Relatório de Ordem de Serviço de Troca.'),
			"codigo"  => 'GER-3750'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_auditoria_os.php',
			'titulo'  => traduz('Relatório de OS Auditadas'),
			'descr'   => traduz('Relatório de Ordens de Serviço auditadas por: Número de série; Com mais de 3 peças; Reincidências; E Ordens de Serviços que não passaram por nenhuma auditoria.'),
			"codigo"  => 'GER-3760'
		),
		array(
			'fabrica_no' => array(14),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_field_call_rate_os_sem_peca.php',
			'titulo'     => traduz('Relatório de OS sem peça'),
			'descr'      => traduz('Relatório de Ordem de Serviço sem peças e seus respectivos defeitos reclamados, defeitos constatados e solução.'),
			"codigo"     => 'GER-3770'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(14,115,116,117,122,123,124,114,81,127,128,129)),
			'icone'      => $icone["relatorio"],
			'link'       => 'custo_os_nac_imp.php',
			'titulo'     => traduz('Custo Nacionais x Importados'),
			'descr'      => traduz('Análise dos custos das Ordens de Serviços de produtos nacionais <i>versus</i> produtos importados.'),
			"codigo"     => 'GER-3780'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_finalizada_sem_extrato.php',
			'titulo'  => traduz('Relatório de OS fechada'),
			'descr'   => traduz('Relatário de OS\'s finalizadas que ainda não entraram em extrato.'),
			"codigo"  => 'GER-3785'
		),
		array(
			'icone'  => $icone["relatorio"],
			'link'   => 'auditoria_os_sem_peca.php',
			'titulo' => traduz('OSs abertas e sem Lançamento de Peças'),
			'descr'  => traduz('Relatório que consta os postos e a quantidade de OSs que estão abertas e sem lançamento de peças'),
			"codigo" => 'GER-3790'
		),
		array(
			'fabrica' => array(19),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_aberta_sac.php',
			'titulo'  => traduz('Relatório de OS aberta pelo SAC'),
			'descr'   => traduz('Relatório de OSs abertas pelo SAC.'),
			"codigo"  => 'GER-3800'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["upload"],
			'link'    => 'atualizacao_postos_bosch.php',
			'titulo'  => traduz('Arquivo de Atualização de AT'),
			'descr'   => traduz('Gera o arquivo de atualização para o posto selecionado, ou envia os arquivos atualizados por e-mail.'),
			"codigo"  => 'GER-3810'
		),
		array(
			'fabrica' => array(11, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_credenciamento.php',
			'titulo'  => traduz('Credenciamento de Postos por Mês'),
			'descr'   => traduz('Mostra os postos credenciados por mês.'),
			"codigo"  => 'GER-3820'
		),
		array(
			'fabrica' => array(11, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_atendida_os_aberta.php',
			'titulo'  => traduz('OSs em aberto a mais de 15 dias que já foram atendidas'),
			'descr'   => traduz('Mostra OSs que tiveram suas peças faturadas pelo fabricante a mais de 15 dias e ainda não foram finalizadas pelo posto autorizado.'),
			"codigo"  => 'GER-3830'
		),
		array(
			'fabrica' => array(11, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_posto_produto_atendido.php',
			'titulo'  => traduz('Produtos consertados pelo posto'),
			'descr'   => traduz('Relatório de produtos consertados por mês pelo posto autorizado.'),
			"codigo"  => 'GER-3840'
		),
		array(
			'fabrica' => array(11, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_aberta_fechada.php',
			'titulo'  => traduz('Relatório de OSs digitadas'),
			'descr'   => traduz('Relatório das OSs digitadas por período'),
			"codigo"  => 'GER-3850'
		),
		array(
			'fabrica' => array(11, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produto_os_finalizada.php',
			'titulo'  => traduz('Relatório OSs finalizadas por produto'),
			'descr'   => traduz('Mostra a quantidade de OSs finalizadas por modelo de produto.'),
			"codigo"  => 'GER-3860'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_auditoria_previa.php',
			'titulo'  => traduz('Relatório de OSs auditadas'),
			'descr'   => traduz('Relatório de OSs que sofreram auditoria prévia.'),
			"codigo"  => 'GER-3870'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'produto_custo_tempo.php',
			'titulo'  => traduz('Relatório de Custo Tempo Cadastrado'),
			'descr'   => traduz('Relatório que consta o custo tempo cadastrado separados por família.'),
			"codigo"  => 'GER-3880'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'peca_informacoes_pais.php',
			'titulo'  => traduz('Relatório de peça e preço por país'),
			'descr'   => traduz('Relatório que consta as peças cadastradas por país.'),
			"codigo"  => 'GER-3890'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_cfa.php',
			'titulo'  => traduz('Relatório de Garantia dividido por CFAs'),
			'descr'   => traduz('Relatório de gastos por Família e Origem de fabricação.'),
			"codigo"  => 'GER-3900'
		),
		array(
			'fabrica_no' => $fabricas_contrato_lite,
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_posto_peca.php',
			'titulo'     => traduz('Relatório de Peças Por Posto'),
			'descr'      => traduz('Relatório de acordo com a data em que a OS foi finalizada.'),
			"codigo"     => 'GER-3910'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_preco_produto_acabado.php',
			'titulo'  => traduz('Relatório de Preços de Aparelhos'),
			'descr'   => traduz('Relatório de preços de produto acabado.'),
			"codigo"  => 'GER-3920'
		),
		array(
			'fabrica' => array(152,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_aceite_contrato.php',
			'titulo'  => traduz('Relatório Aceite do Contrato'),
			'descr'   => traduz('Relatório Aceite do Contrato.'),
			"codigo"  => 'GER-3920'
		),
		array(
			'fabrica' => array(7),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_garantia.php',
			'titulo'  => traduz('Relatório de Peças em Garantia'),
			'descr'   => traduz('Relatório de peças com a classificação de OS garantia.'),
			"codigo"  => 'GER-3930'
		),
		array(
			'fabrica' => array(7),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_sla.php',
			'titulo'  => traduz('Relatório SLA'),
			'descr'   => traduz('Relatório SLA'),
			"codigo"  => 'GER-3940'
		),
		array(
			'fabrica' => array(7),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_back_log.php',
			'titulo'  => traduz('Relatório Back-Log'),
			'descr'   => traduz('Relatório Back-Log'),
			"codigo"  => 'GER-3950'
		),
		array(
			'fabrica' => array(2, 15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_comunicado.php',
			'titulo'  => traduz('Relatório de comunicado lido'),
			'descr'   => traduz('Relatório dos postos que confirmaram a leitura de comunicado.'),
			"codigo"  => 'GER-3960'
		),
		array(
			'fabrica' => array(2),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pendencia_codigo_componente.php',
			'titulo'  => traduz('Relatório de pendências por Código'),
			'descr'   => traduz('Relatório de pendências por código de componente com filtro de posto opcional.'),
			"codigo"  => 'GER-3970'
		),
		array(
			'fabrica' => array(51),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_pendente_gama.php',
			'titulo'  => traduz('Relatório de Peças Pendentes'),
			'descr'   => traduz('Relatório de peças pendentes nas ordens de serviço.'),
			"codigo"  => 'GER-3980'
		),
		array(
			'fabrica' => array(91),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_geral_os.php',
			'titulo'  => traduz('Relatório Geral de OS'),
			'descr'   => traduz('Relatór,io geral de ordens de serviço.'),
			"codigo"  => 'GER-3990'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_com_pedido.php',
			'titulo'  => traduz('Relatório de OS com Pedido'),
			'descr'   => traduz('Relatório de ordens de serviço com pedidos.'),
			"codigo"  => 'GER-4000'
		),
		array(
			'fabrica_no' => array(51, 30),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_peca_pendente.php',
			'titulo'     => traduz('Relatório de Peças Pendentes'),
			'descr'      => traduz('Relatório de peças pendentes nas ordens de serviço e pedidos faturados.'),
			"codigo"     => 'GER-4010'
		),
		array(
			'fabrica' => array(101),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_pendente_faturado.php',
			'titulo'  => traduz('Relatório de Peças Pendentes Pedido Faturado'),
			'descr'   => traduz('Relatório de peças pendentes em pedidos faturados.'),
			"codigo"  => 'GER-4020'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_demanda_peca_new.php',
			'titulo'  => traduz('Relatório de Demanda de Peças'),
			'descr'   => traduz('Relatório de demanda de peças pelas Assistências Técnicas.'),
			"codigo"  => 'GER-4030'
		),
		array(
			'fabrica' => array(40),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_revenda_produto.php',
			'titulo'  => traduz('Relatório de Revenda por Produto'),
			'descr'   => traduz('Relatório de Revenda por Porduto - Controle de Fechamento de OS'),
			"codigo"  => 'GER-4040'
		),
		array(
			'fabrica' => array(40),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_cor_unidade.php',
			'titulo'  => traduz('Relatório de OS por Unidade'),
			'descr'   => traduz('Relatório de OS - Por cor da unidade'),
			"codigo"  => 'GER-4050'
		),
		array(
			'fabrica' => array(40),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_numero_serie.php',
			'titulo'  => traduz('Relatório de OS por Número de Série'),
			'descr'   => traduz('Relatório de OS por Número de Série'),
			"codigo"  => 'GER-4060'
		),
		array(
			'fabrica' => array(40),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_ordem_servico.php',
			'titulo'  => traduz('Relatório de Ordens de Serviço'),
			'descr'   => traduz('Relatório que mostra os dados completos das ordens de serviço'),
			"codigo"  => 'GER-4070'
		),
		array(
			'fabrica' => array(90),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_serie_custo.php',
			'titulo'  => traduz('Relatório de OS - Custo - Série'),
			'descr'   => traduz('Relatório das O.S Finalizadas no Mês.'),
			"codigo"  => 'GER-4080'
		),
		array(
			'fabrica' => array(85),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_gelopar_posto_interno.php',
			'titulo'  => traduz('Relatório de MO (Posto Gelopar)'),
			'descr'   => traduz('Relatório que mostra o valor de OS do posto 10641- Gelopar'),
			"codigo"  => 'GER-4090'
		),
		array(
			'fabrica' => array(81, 114),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_scrap.php',
			'titulo'  => traduz('Relatório de OS Scrap'),
			'descr'   => traduz('Relatório de ordens de serviços scrapeadas.'),
			"codigo"  => 'GER-4100'
		),
		array(
			'fabrica' => array(81, 114),
			'icone'   => $icone["cadastro"],
			'link'    => 'extrato_os_scrap.php?posto_telecontrol=sim',
			'titulo'  => traduz('Cadastro OS Scrap'),
			'descr'   => traduz('Permite cadastrar Scrap da OS Telecontrol.'),
			"codigo"  => 'GER-4110'
		),
		array(
			'fabrica' => array_merge(array(24, 35, 51, 81, 85, 106, 114,122,123,125,128,129,147,152,153,160,169,170,180,181,182), $fabricas_replica_einhell),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_gerencial_diario.php',
			'titulo'  => traduz('Relatório Gerencial'),
			'descr'   => traduz('Relatório Gerencial.'),
			"codigo"  => 'GER-4120'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_pecas_os.php',
			'titulo'  => traduz('Relatório Peças trocadas por Postos'),
			'descr'   => traduz('Relatório de peças trocadas por posto autorizado, linha e família'),
			"codigo"  => 'GER-4130'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_constatado_familia_anual_new.php',
			'titulo'  => traduz('Relatório Anual de OS - Defeito - Família'),
			'descr'   => traduz('Relatório Anual de OS por defeitos constatados e por família'),
			"codigo"  => 'GER-4140'
		),
		array(
			'fabrica' => array(51),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_pendente_gama_troca.php',
			'titulo'  => traduz('Peças Pendentes Críticas'),
			'descr'   => traduz('Relatório de peças pendentes Críticas para troca.'),
			"codigo"  => 'GER-4150'
		),
		array(
			'fabrica' => array(80), #HD 260902
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_produto_total.php',
			'titulo'  => traduz('Relatório de Troca'),
			'descr'   => traduz('Relatório de trocas de produtos.'),
			"codigo"  => 'GER-4160'
		),
		array(
			'fabrica' => array(14, 43),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_status_os.php',
			'titulo'  => traduz('Relatório de O.S. por Status'),
			'descr'   => traduz('Relatório de O.S. listadas de acordo com a seleção dos status'),
			"codigo"  => 'GER-4170'
		),
		array(
			'fabrica' => array(10),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pa_todos.php',
			'titulo'  => traduz('Relatório de Assistências Técnicas'),
			'descr'   => traduz('Relatório de Assistências Técnicas no Brasil.'),
			"codigo"  => 'GER-4180'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_constatado_familia_anual_detalhado.php',
			'titulo'  => traduz('Relatório Anual de OS Detalhado - Defeito - Família'),
			'descr'   => traduz('Relatório Anual de OS Detalhado por defeitos constatados e por famílias'),
			"codigo"  => 'GER-4190'
		),
		array(
			'fabrica' => array(35),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_cadence.php',
			'titulo'  => traduz('Relatório de Ordem de Serviço'),
			'descr'   => traduz('Relatório de ordem de serviço, mostrando dados do consumidor, revenda, produto, e peças.'),
			"codigo"  => 'GER-4200'
		),
		array(
			'fabrica' => array(35,169,170),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_fechamento_os_posto.php',
			'titulo'  => traduz('Relatório de controle de fechamento O.S.'),
			'descr'   => traduz('Consta o tempo médio que o posto levou para finalizar uma ordem de serviço.'),
			"codigo"  => 'GER-4210'
		),
		array(
			'fabrica' => array(45),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_produto.php',
			'titulo'  => traduz('Relatório Troca de Produto'),
			'descr'   => traduz('Relatório de produto trocado na OS.'),
			"codigo"  => 'GER-4220'
		),
		array(
			'fabrica' => array(45),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produto_qtde.php',
			'titulo'  => traduz('Relatório de Gerência'),
			'descr'   => traduz('Relatório que mostra total do produto(trocado, utilizaram peças) do mês.'),
			"codigo"  => 'GER-4230'
		),
		array(
			'fabrica' => array(45),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_produto_causa.php',
			'titulo'  => traduz('Relatório Troca Produto Causa'),
			'descr'   => traduz('Relatório de produto trocado na OS(filtrando por causa).'),
			"codigo"  => 'GER-4240'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_sem_preco_al.php',
			'titulo'  => traduz('Relatório de Peças sem Preço dos Paises da AL'),
			'descr'   => traduz('Relatório de Peças dos paises da América Latina que estão sem preço cadastrado.'),
			"codigo"  => 'GER-4250'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_qtde_valor.php',
			'titulo'  => traduz('Relatório de quantidade / valor  de OSs'),
			'descr'   => traduz('Relatório de quantidade e valor de OSs por tipo de atendimento.'),
			"codigo"  => 'GER-4260'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_cortesia_comercial.php',
			'titulo'  => traduz('Relatório de OS Cortesia Comercial'),
			'descr'   => traduz('Relatório de OS de Cortesia Comercial.'),
			"codigo"  => 'GER-4270'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas.php',
			'titulo'  => traduz('Relatório de Pedidos de Peças'),
			'descr'   => traduz('Relatório de peças pedidas pelo posto autorizado em garantia ou compra.'),
			"codigo"  => 'GER-4280'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_revenda_os.php',
			'titulo'  => traduz('Consulta Revenda x Produto'),
			'descr'   => traduz('Relatório de OS por revenda e quantidade em um período'),
			"codigo"  => 'GER-4290'
		),
		array(
			'fabrica' => array(24),# HD 24493 - Incluído para a Suggar Relatório de peças exportadas
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_exportada.php',
			'titulo'  => traduz('Relatório de Peças Exportadas'),
			'descr'   => traduz('Relatório de peças exportadas pelo posto em um período'),
			"codigo"  => 'GER-4300'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_faturamento_pecas.php',
			'titulo'  => traduz('Relatório de Peças Faturadas'),
			'descr'   => traduz('Relatório de peças faturadas para os postos autorizados pela data de emissão da nota fiscal.'),
			"codigo"  => 'GER-4310'
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_faturamento_os.php',
			'titulo'  => traduz('Relatório de OS Faturadas'),
			'descr'   => traduz('Relatório de OS faturadas para os postos autorizados pela data de abertura da OS.'),
			"codigo"  => 'GER-4320'
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tempo_medio_abertura.php',
			'titulo'  => traduz('Relatório de tempo médio por os'),
			'descr'   => traduz('Relatório de tempo de reparo por os  '),
			"codigo"  => 'GER-4330'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_faturamento_garantia_pecas.php',
			'titulo'  => traduz('Relatório de Peças Atendidas em Garantia'),
			'descr'   => traduz('Relatório de peças atendidas em garantia para os postos autorizados pela data de emissão da nota fiscal.'),
			"codigo"  => 'GER-4340'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_devolucao_pecas_pendentes.php',
			'titulo'  => traduz('Relatório de Devolução de Peças Pendentes'),
			'descr'   => traduz('Relatório de peças atendidas em garantia para os postos autorizados com devolução pendente'),
			"codigo"  => 'GER-4350'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas_terceiros.php',
			'titulo'  => traduz('Relatório de Peças em Poder de Terceiros'),
			'descr'   => traduz('Relatório de peças em poder de terceiros com base no LGR.'),
			"codigo"  => 'GER-4360'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_extrato.php',
			'titulo'  => traduz('Relatório Analítico de Defeito de OS'),
			'descr'   => traduz('Relatório que lista OS com detalhes e defeitos constatados nos atendimentos'),
			"codigo"  => 'GER-4370'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pesquisa_satisfacao_new.php',
			'titulo'  => traduz('Relatório Pesquisa de Satisfação'),
			'descr'   => traduz('Relatório Geral da Pesquisa de Satisfação'),
			"codigo"  => 'GER-4380'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pesquisa_satisfacao_os.php',
			'titulo'  => traduz('Relatório Pesquisa de Satisfação - OS'),
			'descr'   => traduz('Relatório por OS da Pesquisa de Satisfação'),
			"codigo"  => 'GER-4390'
		),
		array(
			'icone'  => $icone["relatorio"],
			'link'   => 'posto_consulta_gerencia.php',
			'titulo' => traduz('Relação de Postos Credenciados'),
			'descr'  => traduz('Relação de Postos Credenciados'),
			"codigo" => 'GER-4400'
		),
		array(
			'fabrica' => array(175),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_peca_sem_preco.php',
			'titulo'  => traduz('Relatório de OS e Peças sem preço'),
			'descr'   => traduz('Relatório de Peças sem preço lançadas em ordens de serviço.'),
			"codigo"  => 'GER-5040'
		),
		array(
			'fabrica' => array(175),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_rastreabilidade_de_pecas.php',
			'titulo'  => traduz('Relatório de Rastreabilidade de Peças'),
			'descr'   => traduz('Relatório de Rastreabilidade de Peças.'),
			"codigo"  => 'GER-5050'
		),
		array(
			'fabrica' => array_merge(array(139,141,148,167,169,170,174,178,183,184,191,193,195,198,200,203),$arr_fabrica_distrib),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tma.php',
			'titulo'  => traduz('Relatório TMA'),
			'descr'   => traduz('Aging de Ordem de Serviço.'),
			"codigo"  => 'GER-4410'
		),
		array(
			'fabrica' => array(101),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_cancelada_pedido.php',
			'titulo'  => traduz('Relatório Peças Canceladas'),
			'descr'   => traduz('Relatório das peças canceladas dos pedidos que foram faturados'),
			"codigo"  => 'GER-4420'
		),
		array(
			'fabrica' => array(85),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_posto_km.php',
			'titulo'  => traduz('Relação de Postos OS x KM'),
			'descr'   => traduz('Relação de Postos OS x KM'),
			"codigo"  => 'GER-4430'
		),
		array(
			'fabrica' => array(45),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tempo_defeito_produto.php',
			'titulo'  => traduz('Relatório de tempo de defeito produtos'),
			'descr'   => traduz('Relatório de tempo de defeito produtos'),
			"codigo"  => 'GER-4440'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_rastreabilidade.php',
			'titulo'  => traduz('Relatórios Rastreabilidade '),
			'descr'   => traduz('Relatórios Rastreabilidade de Peças'),
			"codigo"  => 'GER-4450'
		),
		array(
			'disabled' => true,
			'fabrica'  => array(50),
			'icone'    => $icone["relatorio"],
			'link'     => 'relatorio_extratificacao_v201408.php',
			'titulo'   => traduz('Relatório de Estratificação - 2014'),
			'descr'    => traduz('Relatório de Estratificação'),
			"codigo"   => 'GER-4460'
		),
		array(
			'disabled' => true,
			'fabrica'  => array(24),
			'icone'    => $icone["relatorio"],
			'link'     => 'relatorio_extratificacao_vdnf.php',
			'titulo'   => traduz('Relatório de Estratificação'),
			'descr'    => traduz('Relatório de Estratificação'),
			"codigo"   => 'GER-4470'
		),
		array(
			'fabrica' => array(24,50,120,201,175),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_extratificacao_v201505.php',
			'titulo'  => traduz('Relatório de Estratificação'),
			'descr'   => traduz('Relatório de Estratificação'),
			"codigo"  => 'GER-4480'
		),
		array(
			'fabrica'  => array(24),
			'icone'    => $icone["relatorio"],
			'link'     => 'relatorio_extratificacao_devolucao.php',
			'titulo'   => traduz('Relatório de Estratificação Devolução'),
			'descr'    => traduz('Relatório de Estratificação Devolução'),
			"codigo"   => 'GER-4490'
		),
		array(
			'fabrica' => array(15), // HD 55355
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_nt_serie.php',
			'titulo'  => traduz('Relatório de Série da Familia NT'),
			'descr'   => traduz('Relatório que mostra o número de série das OSs com produto da familia Lavadora NT e as OSs sem lançamento de peça.'),
			"codigo"  => 'GER-4500'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_constatado_peca.php',
			'titulo'  => traduz('Relatório de Defeito Constatado Peça'),
			'descr'   => traduz('Relatório que consulta OS,Defeito Constatado e Peça.'),
			"codigo"  => 'GER-4510'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_nt_serie_abertura.php',
			'titulo'  => traduz('Relatório de Série da Familia NT Abertura'),
			'descr'   => traduz('Relatório que mostra o número de série das OSs com produto da familia Lavadora NT e as OSs sem lançamento de peça pela data de abertura.'),
			"codigo"  => 'GER-4520'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_km.php',
			'titulo'  => traduz('Relatório de OS com Deslocamento'),
			'descr'   => traduz('Relatório que mostra os dados das ordens de serviços com deslocamento.'),
			"codigo"  => 'GER-4530'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_mensal.php',
			'titulo'  => traduz('Relatório de Ordem de Serviço'),
			'descr'   => traduz('Relatório que mostra os dados das ordens de serviços com base na na geração do extrato.'),
			"codigo"  => 'GER-4540'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_reincidencia_latinatec.php',
			'titulo'  => traduz('Relatório de OS reincidêntes'),
			'descr'   => traduz('Relatório que mostra as reincidências de Ordens de Serviço.'),
			"codigo"  => 'GER-4550'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_estoque_posto.php',
			'titulo'  => traduz('Relatório de Estoque dos postos'),
			'descr'   => traduz('Relatório que o estoque dos postos'),
			"codigo"  => 'GER-4560'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produto_locacao.php',
			'titulo'  => traduz('Relatório de Produtos de Locação'),
			'descr'   => traduz('Relatório que mostra os produtos de locação.'),
			"codigo"  => 'GER-4570'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas_lista_basicas.php',
			'titulo'  => traduz('Relatório de Peças que Constam em Listas Básicas'),
			'descr'   => traduz('Relatório que mostra todas as peças que constam em listas básicas de Produtos'),
			"codigo"  => 'GER-4580'
		),
		array(
			'fabrica' => array(91), // HD 367935
			'icone'   => $icone["relatorio"],
			'link'    => 'rel_peca_requisitada.php',
			'titulo'  => traduz('Relatório de Requisição de Peças'),
			'descr'   => traduz('Relatório que mostra as as peças requisitadas'),
			"codigo"  => 'GER-4590'
		),
		array(
			'fabrica' => array(43), // HD 255546
			'icone'   => $icone["relatorio"],
			'link'    => 'ranking_autorizadas.php',
			'titulo'  => traduz('Ranking Postos'),
			'descr'   => traduz('Relatório que mostra dados mensais dos Postos gerando um Ranking'),
			"codigo"  => 'GER-4600'
		),
		array(
			'fabrica' => array(91), // HD 2432459
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_cidade_atendida_posto.php',
			'titulo'  => traduz('Relatório cidade atendidas pelo Posto'),
			'descr'   => traduz('Relatório que mostra cidade atendidas pelo Posto'),
			"codigo"  => 'GER-4610'
		),
		array(
			'fabrica' => array(91), /* HD-3594930*/
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pedido_faturado.php',
			'titulo'  => traduz('Relatório de Pedidos Faturados'),
			'descr'   => traduz('Histórico geral dos pedidos de VENDA dos postos autorizados. Neste relatório consta a relação de todos os postos autorizados ativos e seus respectivos pedidos de compra.'),
			"codigo"  => 'GER-4620'
		),
		array(
			'fabrica' => array(74), // HD 673761
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_mensal_atlas.php',
			'titulo'  => traduz('Relatório de Informações'),
			'descr'   => traduz('Relatório que mostra informações sobre OS, peças, defeitos etc.'),
			"codigo"  => 'GER-4630'
		),
		array(
			'fabrica' => array(74),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_protocolos_atendimento.php',
			'titulo'  => traduz('Relatório dos Protocolos de Atendimento'),
			'descr'   => traduz('Relatório que mostra informações dos Atendimentos'),
			"codigo"  => 'GER-4640'
		),
		array(
			'fabrica' => array(74),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pedido_pecas.php',
			'titulo'  => traduz('Relatório de Pedido de Peças'),
			'descr'   => traduz('Relatório que mostra os pedidos e suas peças'),
			"codigo"  => 'GER-4650'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produtos_cadastrados.php',
			'titulo'  => traduz('Relatório de Produtos Cadastrados'),
			'descr'   => traduz('Relatório que mostra informações sobre sobre os produtos cadastrados'),
			"codigo"  => 'GER-4660'
		),
		array(
			'fabrica' => array_merge(array(0,101),$arr_fabrica_distrib),
			'icone'   => $icone["relatorio"],
			'link'    => 'ressarcimento_consulta.php',
			'titulo'  => traduz('Relatório de Ressarcimentos'),
			'descr'   => traduz('Relatório que mostra informações sobre ressarcimentos cadastrados'),
			"codigo"  => 'GER-4670'
		),
		array(
			'fabrica' => array(45),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_gerencial_os.php',
			'titulo'  => traduz('Relatório gerencial de OS'),
			'descr'   => traduz('Relatório gerencial de OS'),
			"codigo"  => 'GER-4680'
		),
		array(
			'fabrica' => array(81, 114, 122, 123,124,127,128,129, 153),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_atendimento.php',
			'titulo'  => traduz('Relatório OS x Atendimento'),
			'descr'   => traduz('Relatório de OS por atendimento'),
			"codigo"  => 'GER-4690'
		),
		array(
			'fabrica' => array(153),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_estoque_posto.php',
			'titulo'  => traduz('Relatório de Peças em Estoque'),
			'descr'   => traduz('Relatório de Peças em Estoque por OS'),
			"codigo"  => 'GER-4700'
		),
		array(
			'fabrica' => $arr_fabrica_distrib,
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_gf.php',
			'titulo'  => traduz('Relatório de Peças por Garantia/Faturado'),
			'descr'   => traduz('Relatório que mostra informações de pedido e OS'),
			"codigo"  => 'GER-4710'
		),
		array(
			'fabrica' => array(81),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_pedido.php',
			'titulo'  => traduz('Relatório venda e garantia'),
			'descr'   => traduz('Relatório que mostra as peças fornecidas em garantia e venda'),
			"codigo"  => 'GER-4720'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"], 
			'link'    => 'relatorio_demanda_peca_posto.php',
			'titulo'  => traduz('Relatório de Demanda de Peças por Postos '),
			'descr'   => traduz('Relatório de demanda de peças pelas Assistências Técnicas.'),
			"codigo"  => 'GER-4730'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'ocorrencia_fornecedor.php',
			'titulo'  => traduz('Relatório de Ocorrência x Fornecedor'),
			'descr'   => traduz('Relatório de ocorrência x fornecedor'),
			"codigo"  => 'GER-4731'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_mao_obra_pais.php',
			'titulo'  => traduz('Relatório de mão de obra por País'),
			'descr'   => traduz('Relatório de mão de obra por defeito constatado por produto em relação ao País.'),
			"codigo"  => 'GER-4740'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_funcionario_posto.php',
			'titulo'  => traduz('Relatório funcionários Posto'),
			'descr'   => traduz('Relatório dos funcionários dos Postos e suas Funções.'),
			"codigo"  => 'GER-4741'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_compras_vendas_postos.php',
			'titulo'  => traduz('Compras e vendas entre postos'),
			'descr'   => traduz('Acompanhamento de compras e vendas entre postos.'),
			"codigo"  => 'GER-4742'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas_disponiveis_shop_pecas.php',
			'titulo'  => traduz('Peças disponíveis no Shop Peças'),
			'descr'   => traduz('Relação de peças disponíveis no Shop Peças.'),
			"codigo"  => 'GER-4743'
		),
		array(
			'fabrica' => array(40),
			'icone'   => $icone["relatorio"],
			'link'    => 'pesquisa_posto_os_reincidente.php',
			'titulo'  => traduz('Postos com OS Reincidente'),
			'descr'   => traduz('Quantidade de OS Reincidente por Posto.'),
			"codigo"  => 'GER-4750'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["relatorio"],
			'link'    => 'os_fechamento_automatico_tectoy.php',
			'titulo'  => traduz('Relatório de OS Fechamento Automático'),
			'descr'   => traduz('Quantidade de OS com Fechamento Automático.'),
			"codigo"  => 'GER-4760'
		),
		array(
			'fabrica' => array(141,144,165),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produtividade_reparo.php',
			'titulo'  => traduz('Relatório de Produtividade de Reparo'),
			'descr'   => traduz('Relatório que mede a produtividade de repara das Ordens de Serviço de acordo com as metas estabelecidas.'),
			"codigo"  => 'GER-4770'
		),
		array(
			'fabrica' => array(141,144,165),
			'icone'   => $icone["relatorio"],
			'link'    => ($login_fabrica != 165) ? 'painel_os_aberta_familia.php' : 'painel_os_aberta_familia_online.php',
			'titulo'  => traduz('Painel OS por Família'),
			'descr'   => traduz('Produtos aguardando reparo na assistência técnica'),
			"codigo"  => 'GER-4780'
		),
		array(
			'fabrica' => array(141,144),
			'icone'   => $icone["relatorio"],
			'link'    => 'painel_os_consertada_familia.php',
			'titulo'  => traduz('Painel OS consertadas por Família Posto Interno'),
			'descr'   => traduz('Produtos aguardando remanufatura/expedição no Posto Interno'),
			"codigo"  => 'GER-4790'
		),
		array(
			'fabrica' => array_merge(array(123,141,151,153,160,174,193), $fabricas_replica_einhell),
			'icone'   => $icone["relatorio"],
			'link'    => 'dashboard_fabrica.php',
			'titulo'  => traduz('Dashboard'),
			'descr'   => traduz('Dashboard de OS abertas por período de 3 ou 6 meses'),
			"codigo"  => 'GER-4800'
		),
		array(
			'fabrica' => array_merge($arr_fabrica_distrib),
			'icone'   => $icone["relatorio"],
			'link'    => 'posto_estoque_distrib.php',
			'titulo'  => traduz('Estoque Distrib'),
			'descr'   => traduz('Relatório de estoque no Distrib'),
			"codigo"  => 'GER-4810'
		),
		array(
			'fabrica' => array(153),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_bonus.php',
			'titulo'  => traduz('Bônus'),
			'descr'   => traduz('Relatório Bônus'),
			"codigo"  => 'GER-4820'
		),
		array(
			'fabrica' => array(104),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_reprovada.php',
			'titulo'  => traduz('Relatório de OSs Reprovadas'),
			'descr'   => traduz('Relatório que lista doas as OSs Reprovadas no período de até 12 meses.'),
			"codigo"  => 'GER-4830'
		),
		array(
			'fabrica' => array(151),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_conferencias_realizadas.php',
			'titulo'  => traduz('Relatório de Conferências Realizadas'),
			'descr'   => traduz('Relatório de Conferências Realizadas por período de até 1 ano'),
			"codigo"  => 'GER-4840'
		),
		array(
			'fabrica' => array(153),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_defeito.php',
			'titulo'  => traduz('Relatório OS - Defeitos'),
			'descr'   => traduz('Relatório de OS x Defeito Constatado e Defeito Reclamado'),
			"codigo"  => 'GER-4850'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_garantia_por_cliente_admin.php',
			'titulo'  => traduz('Relatório de Ordens de Serviço de Garantia'),
			'descr'   => traduz('Relatório que mostra as OSs totais por classificação, OSs finalizadas por mês e OSs pendentes separadas por status, com opção de gerar por clietne admin ou posto autorizado'),
			"codigo"  => 'GER-4860'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_permanencia_tempo_conserto.php',
			'titulo'  => traduz('Relatório Permanência em conserto'),
			'descr'   => traduz('Relatório que mostra o tempo (dias) de permanência do produto (Ordens de Serviço de garantia) na assistência técnica'),
			"codigo"  => 'GER-4870'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_fora_garantia_centro_distribuidor.php',
			'titulo'  => traduz('Relatório de Ordens de Serviço dentro e fora de garantia origem KOF'),
			'descr'   => traduz('Relatório que mostra os indicadores das Ordens de Serviço fora de garantia'),
			"codigo"  => 'GER-4880'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_oss_corretivas.php',
			'titulo'  => traduz('Relatório de Ordens de Serviço Corretiva Garantia'),
			'descr'   => traduz('Relatório que mostra os indicadores das Ordens de Serviço em garantia'),
			"codigo"  => 'GER-4890'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_oss_sanitizacao.php',
			'titulo'  => traduz('Relatório de Ordens de Serviço de sanitização'),
			'descr'   => traduz('Relatório que mostra os indicadores das Ordens de Serviço de sanitização'),
			"codigo"  => 'GER-4900'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_indicadores_oss_abertas.php',
			'titulo'  => traduz('Indicadores de Ordens de Serviço Abertas'),
			'descr'   => traduz('Relatório que mostra a quantidade de OSs em aberto, tanto de garantia quanto fora de garantia'),
			"codigo"  => 'GER-4910'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'indicadores_os_finalizada.php',
			'titulo'  => traduz('Indicadores de OSs Finalizadas'),
			'descr'   => traduz('Indicadores de OSs finalizadas por Ano x Estado'),
			"codigo"  => 'GER-4920'
		),
		array(
			'fabrica' => array(158,167,169,170,175,183,184,186,190,191,195,198,200,203),
			'icone'   => $icone["relatorio"],
			'link'    => 'dashboard_novo.php',
			'titulo'  => traduz('Dashboard'),
			'descr'   => (in_array($login_fabrica, array(169,170))) ? traduz('Dashboard de OS abertas por período de 6 meses') : traduz('Dashboard de OS abertas por período de 3 meses'),
			"codigo"  => 'GER-4930'
		),
		array(
			'fabrica' => array(42),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_cortesia_custos.php',
			'titulo'  => traduz('Relatório OS - Custos de Cortesia'),
			'descr'   => traduz('Relatório de custos com cortesia de OS'),
			"codigo"  => 'GER-4940'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'log_auto_agendamento.php',
			'titulo'  => traduz('Log do Auto Agendamento'),
			'descr'   => traduz('Relatório que mostra as execuções do auto agendamento e o arquivo de log'),
			"codigo"  => 'GER-4950'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'indicadores_tempo_resposta.php',
			'titulo'  => traduz('Indicadores de Tempo de Resposta'),
			'descr'   => traduz('Indicadores que mostra o tempo de resposta dos atendimento, tempo levado entre a execução e finalização do atendimento'),
			"codigo"  => 'GER-4960'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'indicadores_eficiencia_produtividade.php',
			'titulo'  => traduz('Indicadores de Eficiência/Produtividade'),
			'descr'   => traduz('Indicadores que mostra o tempo de resposta dos atendimento, a eficiência dos atendimentos dentro do sla e a nota de produtividade'),
			"codigo"  => 'GER-4970'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'indicadores_eficiencia_volume.php',
			'titulo'  => traduz('Indicadores SLA/Reincidência'),
			'descr'   => traduz('Indicadores que mostra o tempo de resposta dos atendimento, a eficiência dos atendimentos dentro do sla'),
			"codigo"  => 'GER-4980'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'estatistica_os_fechamento.php',
			'titulo'  => traduz('Relatório de Fechamento'),
			'descr'   => traduz('Relatório que demonstra por onde foi feito a ação do fechamento da OS'),
			"codigo"  => 'GER-4990'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_faturamento_kof.php',
			'titulo'  => traduz('Relatório de Peças Utilizadas em OS'),
			'descr'   => traduz('Relatório que demonstra  peças utilizadas em OS'),
			"codigo"  => 'GER-5000'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_os.php',
			'titulo'  => traduz('Relatório de Resumo de Peças Utilizadas em OS'),
			'descr'   => traduz('Relatório de todas as informações referentes às peças consumidas em OS já Finalizadas.'),
			"codigo"  => 'GER-5010'
		),
		array(
			'fabrica' => $fabrica_relatorio_ratreio,
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_rastreamento.php',
			'titulo'  => traduz('Relatório de Rastreamento'),
			'descr'   => traduz('Relatório do O.S com data de Recebimento.'),
			"codigo"  => 'GER-5020'
		),
		array(
			'fabrica' => array(169,170),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_dashboard.php',
			'titulo'  => traduz('Relatório Dashboard Visão dos Postos'),
			'descr'   => traduz('Relatório Dashboard Visão dos Postos.'),
			"codigo"  => 'GER-5030'
		),
		array(
			'icone'  => $icone["relatorio"],
			'fabrica' => array(35),
			'link'   => 'relatorio_acompanhamento_posto_nps.php',
			'titulo' => traduz('Relatório Ranqueamento Rede Autorizada'),
			'descr'  => traduz('Relatório detalhado para acompanhamento de NPS dos postos autorizados.'),
			"codigo" => 'GER-5040'
		),
		array(	
			'fabrica'   => array(35),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_visao_geral_de_os.php',
			'titulo'    => traduz('Gerenciador de Ordem de Serviço'),
			'descr'     => traduz('Relatório Visão Geral de Ordem de Serviço'),
			'codigo'    => 'GER-5050'
		),
		array(
            'fabrica'    => ($telecontrol_distrib == 't') ? [$login_fabrica] : "",
            'fabrica_no' => ($telecontrol_distrib != 't') ? [$login_fabrica] : "",
            'icone'      => $icone["relatorio"],
            'link'       => 'relatorio_produtividade_interacoes.php',
            'titulo'     => traduz('Relatório Interações'),
            'descr'      => traduz('Relatório detalhado da produtividade dos atendentes'),
            "codigo"     => 'GER-5050'
        ),
		array(
            'fabrica'   => array(115),
            'icone'     => $icone["relatorio"],
            'link'      =>'relatorio_categoria_posto.php',
            'titulo'    => traduz('Relatório de Categoria dos Postos'),
            'descr'     => traduz('Mostra o desempenho dos postos em relação à categoria (Classificação)'),
            'codigo'    =>'GER-5060'
        ),
        array(
            'fabrica'   => [169,170,183],
            'icone'     => $icone["relatorio"],
            'link'      =>'fluxo_entrada_saida_os.php',
            'titulo'    => traduz('Relatório de OS abertas x OS encerradas'),
            'descr'     => traduz('Mostra o desempenho com relação à abertura e fechamento das ordens de serviço por semana'),
            'codigo'    =>'GER-5070'
        ),
        array(
            'fabrica'       => ($telecontrol_distrib == 't') ? [$login_fabrica] : "",
            'fabrica_no'    => ($telecontrol_distrib != 't') ? [$login_fabrica] : "",
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_detalhado_os_peca.php',
            'titulo'        => traduz('Relatório Pedidos x Peças'),
            'descr'         => traduz('Relatório detalhado por Ordem de serviço, pedido e peça'),
            "codigo"    => 'GER-5080'
        ),
		array(
			'fabrica_no'   => (!$pesquisaSatisfacao) ? [$login_fabrica] : "",
    		'icone'     => $icone["relatorio"],
    		'link'      => 'pesquisa_satisfacao_relatorio.php',
    		'titulo'    => traduz('Relatório da Pesquisa de Satisfação'),
    		'descr'     => traduz('Relatório das pesquisas de satisfações disparadas.'),
    		"codigo"    => "GER-5090"
		   ),
		array(
			'fabrica'   => [138],
			'icone'     => $icone["relatorio"],
			'link'      => 'relatorio_indicadores.php',
			'titulo'    => 'Relatório de Indicadores',
			'descr'     => 'Relatório de indicadores de OSs x Chamados (pesquisas de satisfação) x Extratos.',
			"codigo"    => "GER-6000"
       	),
        array(
            'fabrica'       => ($telecontrol_distrib == 't') ? [$login_fabrica] : "",
            'fabrica_no'    => ($telecontrol_distrib != 't') ? [$login_fabrica] : "",
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_ofensores.php',
            'titulo'        => traduz('CHECK STATUS'),
            'descr'         => traduz('Motivos pelo qual as Ordens de Serviço e pedidos faturados permanecem em aberto no sistema'),
            "codigo"    => 'GER-5100'
	    ),
	    array(
            'fabrica'       => ($telecontrol_distrib == 't' || in_array($login_fabrica, [174])) ? [$login_fabrica] : [0],
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_produtividade_atendentes_callcenter.php',
            'titulo'        => traduz('Relatório de Produtividade Callcenter'),
            'descr'         => traduz('Relatório de produtividade dos atendentes de callcenter'),
            "codigo"    => 'GER-5110'
	    ),
	    array(
            'fabrica'       => ($telecontrol_distrib == 't') ? [$login_fabrica] : [0],
            'icone'         => $icone["relatorio"],
            'link'          => 'tempo_permanencia.php',
            'titulo'        => traduz('Tempo de Permanência'),
            'descr'         => traduz('Média do tempo de fechamento das OSs por Mês. Exibe: visão geral/por UF/postos ofensores'),
            "codigo"    => 'GER-5120'
	    ),
	   	array(
            'fabrica'       => [176],
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_consumidores.php',
            'titulo'        => traduz('Dados consumidores'),
            'descr'         => traduz('Dados dos consumidores que tiveram OS e atendimentos abertos'),
            "codigo"    	=> 'GER-5130'
        ),

	    array(
            'fabrica'       => array(178),
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_os_abertas_fechadas_mes.php',
            'titulo'        => traduz('Relatório Ordem de Serviços abertas e fechadas durante o mês'),
            'descr'         => traduz('Exibe relatório de Ordem de Serviços abertas e fechadas durante o mês'),
            "codigo"    	=> 'GER-5130'
	    ),
	    array(
            'fabrica'       => [158],
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_valores_sta.php',
            'titulo'        => traduz('Relatório de valores acordados STAs'),
            'descr'         => traduz('Valores M.O acordados com os postos'),
            "codigo"    	=> 'GER-5140'
	    ),
	    array(
            'fabrica'       => (($telecontrol_distrib || $interno_telecontrol) && $privilegios == "*") ? [$login_fabrica] : [0,189],
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_evolucao_operacional.php',
            'titulo'        => traduz('Relatório de Evolução Operacional'),
            'descr'         => traduz('Crescimento da demanda operacional das fábricas'),
            "codigo"    	=> 'GER-5150'
	    ),
	    array(
	    'fabrica'   => [19],
            'icone'     => $icone["relatorio"],
            'link'      =>'relatorio_garantias_adicionais.php',
            'titulo'    =>'Relatório de OSs com garantias adicionais',
            'descr'     =>"Dados de OSs com garantias adicionais",
            'codigo'    =>'GER-5160'
	    ),
	    array(
	    'icone'  => $icone["relatorio"],
	    'fabrica' => [91],
	    'link'   => 'ranking_postos.php',
	    'titulo' => 'Relatório Ranqueamento Rede Autorizada',
	    'descr'  => 'Relatório detalhado para acompanhamento da pontuação dos postos autorizados.',
	    "codigo" => 'GER-5170'
	    ),
	    array(
            'fabrica'       => (($telecontrol_distrib || $interno_telecontrol) && $privilegios == "*") ? [$login_fabrica] : [0,189],
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_telefonia_detalhado_atendentes.php',
            'titulo'        => 'Relatório Detalhado Telefonia Atendentes',
            'descr'         => 'Crescimento da demanda operacional das fábricas',
            "codigo"    	=> 'GER-5180'
	    ),
	    array(
            'fabrica'   => [186],
            'icone'     => $icone["relatorio"],
            'link'      =>'relatorio_os_posto_autorizado_x_interno.php',
            'titulo'    =>'Relatório de Indicações de Posto',
            'descr'     =>"Relatório de atendimentos, indicação de posto direcionado",
            'codigo'    =>'GER-5190'
        ),
	    array(

			'fabrica' => [183],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_primeiro_acesso_posto.php',
			'titulo'  => 'Relatório Posto Primeiro Acesso',
			'descr'   => 'Relatório de postos que já fizeram o primeiro acesso no Telecontrol',
			"codigo"  => 'GER-5220'
	    ),
	    array(
			'fabrica' => [183],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_cliente_representante.php',
			'titulo'  => 'Relatório de clientes Representantes',
			'descr'   => 'Relatório de clientes vinculados a Representantes',
			"codigo"  => 'GER-5230'
	    ),
	    array(
        	'fabrica' => [42],
            'icone'   => $icone['cadastro'],
            'link'    => 'questionario_avaliacao.php',
            'titulo'  => 'Questionário de Avaliação do Posto Autorizado',
            'descr'   => 'Cadastro do Questionário de Avaliação do técnico que Será exibido tanto na Área do posto autorizado no Telecontrol Quanto no aplicativo',
            'codigo'  => 'GER-5240'
        ),
	    array(
	    	'fabrica' => [42],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_respostas_pesquisa.php',
			'titulo'  => 'Relatório de Respostas da Pesquisa',
			'descr'   => 'Relatório detalhado com as respostas da pesquisa',
			"codigo"  => 'GER-5250'
	    ),
	    array(
			'fabrica' => [183],
			'icone'   => $icone["relatorio"],
			'link'    => 'consulta_nf_venda.php',
			'titulo'  => 'Relatório Notas Fiscais de Venda',
			'descr'   => 'Relatório Notas Fiscais de Venda',
			"codigo"  => 'GER-5260'
	    ),
	    array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_respostas_atendimento_posto.php',
			'titulo'  => 'Relatório Postos Atendendo',
			'descr'   => 'Relatório que mostra os Postos que estão realizando Atendimento ao Público',
			"codigo"  => 'GER-5270'
	    ),
	    array(
			'fabrica' => [183],
			'icone'   => $icone["relatorio"],
			'link'    => 'dashboard_evolucao_diaria.php',
			'titulo'  => 'Dashboard Evolução Diária',
			'descr'   => 'Dashboard Com o Montante das OSs Diárias X Mensais ',
			"codigo"  => 'GER-5280'
	    ),
	   'link' => 'linha_de_separação',
	),

/**********************************

PULEI O 6000, 7000 PARA A SEÇÃO GERAL

***********************************/


	// Seção OS - Apenas
	'secaoOS' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('ORDENS DE SERVIÇO'),
			'fabrica' => array(30,108,111,153)
		),
		array(
			'icone'      => $icone["cadastro"],
			'fabrica_no' => array(30),
			'link'       => 'os_cadastro.php',
			'titulo'     => traduz('Cadastra OS'),
			'descr'      => traduz('Cadastra uma nova ordem de serviço'),
			"codigo"     => 'GER-8000'
		),
		array(
			'icone'      => $icone["consulta"],
			'fabrica_no' => array(30),
			'link'       => 'os_consulta_lite.php',
			'titulo'     => traduz('Consulta OS'),
			'descr'      => traduz('Consulta Ordens de Serviço'),
			"codigo"     => 'GER-8010'
		),
		array(
			'icone'      => $icone["consulta"],
			'fabrica_no' => array(30),
			'link'       => 'os_parametros_excluida.php',
			'titulo'     => traduz('Consulta OS Excluída'),
			'descr'      => traduz('Consulta Ordens de Serviço excluídas do sistema'),
			"codigo"     => 'GER-8020'
		),
		array(
			'icone'      => $icone["relatorio"],
			'fabrica_no' => array(30),
			'link'       => 'os_intervencao.php',
			'titulo'     => traduz('OSs com Intervenções Técnicas'),
			'descr'      => traduz('OSs com intervenção técnica da fábrica. Autoriza ou cancela o pedido de peças do posto ou efetua o reparo na fábrica.'),
			"codigo"     => 'GER-8030'
		),
		array(
			'icone'      => $icone["relatorio"],
			'fabrica_no' => array(30),
			'link'       => 'os_sem_pedido.php',
			'titulo'     => traduz('OSs que não Geraram Pedidos'),
			'descr'      => traduz('Ordens de Serviços que não geraram pedidos de peças.'),
			"codigo"     => 'GER-8040'
		),
		array(
			'icone'      => $icone["cadastro"],
			'fabrica_no' => array(30),
			'link'       => 'os_revenda.php',
			'titulo'     => traduz('Cadastra OS - REVENDA'),
			'descr'      => traduz('Cadastro de Ordem de Serviços de revenda'),
			"codigo"     => 'GER-8050'
		),
		array(
			'icone'      => $icone["consulta"],
			'fabrica_no' => array(30),
			'link'       => 'os_revenda_parametros.php',
			'titulo'     => traduz('Consulta OS - REVENDA'),
			'descr'      => traduz('Consulta OS Revenda Lançadas'),
			"codigo"     => 'GER-8060'
		),
		array(
			'icone'   => $icone["cadastro"],
			'fabrica' => array(30,153),
			'link'    => 'parametros_intervencao.php',
			'titulo'  => traduz('Parâmetros para Intervenções'),
			'descr'   => traduz('Configuração de parâmetros para entrada em intervenção'),
			"codigo"  => 'GER-8070'
		),
		'link' => 'linha_de_separação',
	),
	// Seção OS - Apenas
	'secaoPD' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('PEDIDOS DE PEÇAS'),
			'fabrica' => array(108,111)
		),
		array(
			'icone'  => $icone["cadastro"],
			'link'   => 'pedido_cadastro.php',
			'titulo' => traduz('Cadastra Pedidos'),
			'descr'  => traduz('Cadastra um novo pedido de peças'),
			"codigo" => 'GER-9000'
		),
		array(
			'icone'  => $icone["consulta"],
			'link'   => 'pedido_parametros.php',
			'titulo' => traduz('Consulta Pedidos'),
			'descr'  => traduz('Consulta pedidos de peças'),
			"codigo" => 'GER-9010'
		),
		array(
			'icone'  => $icone["consulta"],
			'link'   => 'comunicado_produto_consulta.php',
			'titulo' => traduz('Vista Explodida e Comunicados'),
			'descr'  => traduz('Consulta vista explodida, diagramas, esquemas e comunicados.'),
			"codigo" => 'GER-9020'
		),
		'link' => 'linha_de_separação',
	),
	// Seção CALL-CENTER - GERAL
	'secaoCC' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('RELATÓRIOS CALL-CENTER'),
			'fabrica_no' => array_merge(array(87,108,111,115,116,117,122,81,114,124,123,127,128,129,136,138,139,141,142,143,144,145), $fabricas_contrato_lite)
		),
		array(
			'fabrica_no' => $arr_fabrica_padrao,
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_callcenter_reclamacao_por_estado.php',
			'titulo'     => traduz('Reclamações por estado'),
			'descr'      => traduz('Histórico de atendimentos por estado.'),
			'codigo'     => 'GER-10000'
		),
		array(
			'fabrica' => ($telecontrol_distrib == 't') ? [$login_fabrica] : [35,80,174,186],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_solicitacoes_postagem.php',
			'titulo'  => traduz('Solicitações de Postagem'),
			'descr'   => traduz('Relatório de solicitações de postagem por intervalo de datas.'),
			'codigo'  => 'GER-10010'
		),
		array(
			'fabrica' => ($telecontrol_distrib == 't') ? [$login_fabrica] : [186],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_callcenter_reclamacao_por_periodo.php',
			'titulo'  => 'Reclamações por periodo',
			'descr'   => 'Relatório de histórico de atendimentos por periodo.',
			'codigo'  => 'GER-10020'
        	),
		array(
			'fabrica' => [189],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_custo_atendimento.php',
			'titulo'  => 'Custos dos Atendimentos',
			'descr'   => 'Relatório de custos do atendimento.',
			'codigo'  => 'GER-10030'
		),
		 array(
            'fabrica' => [183,189],
            'icone'   => $icone["relatorio"],
            'link'    => 'acompanhamento_atendimentos.php',
            'titulo'  => 'Acompanhamento dos Atendimentos',
            'descr'   => 'Relatório de acompanhamento de prazos dos atendimentos.',
            'codigo'  => 'GER-10040'
        ),
		 array(
            'fabrica' => [186],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_custos_postagens.php',
            'titulo'  => 'Custos de Postagens',
            'descr'   => 'Relatório de Custos de postagens.',
            'codigo'  => 'GER-10050'
        ),
		'link' => 'linha_de_separação',
	),
	// Seção RELATORIOS ROCA
	'secaoRC' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => 'RELATÓRIOS ROCA',
			'fabrica'    => [178]
		),
		array(
			'fabrica' => [178],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_aberta_fechada_mes.php',
			'titulo'  => traduz('OS Abertas e Fechadas no Mês'),
			'descr'   => traduz('Relatório de ordens de serviços (OS) abertas e fechadas no mês.'),
			'codigo'  => 'GER-10060'
		),
		array(
			'fabrica' => [178],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_callcenter_contatos_mes.php',
			'titulo'  => traduz('Contatos Realizados Por Atendimentos Callcenter no Mês'),
			'descr'   => traduz('Relatório de contatos feitos por atendimentos callcenter no mês.'),
			'codigo'  => 'GER-10070'
		),
		'link' => 'linha_de_separação',
	),
	// Seção Gerencia - GERAL
	'secaoTP' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('RELATÓRIOS - TEMPO DE PROCESSOS'),
			'fabrica' => array(152,169,170,180,181,182)
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'abertura_os_faturamento_peca.php',
			'titulo'  => traduz('Abertura da OS X Faturamento da Peça'),
			'descr'   => traduz('Contabiliza a data de abertura da OS até o faturamento da peça mostrando a quantidade de dias.'),
			"codigo"  => 'GER-11000'
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'pedido_faturamento_pedido.php',
			'titulo'  => traduz('Geração do pedido x faturamento do pedido'),
			'descr'   => traduz('Calculo da data que o pedido foi gerado até o faturamento das peças.'),
			"codigo"  => 'GER-11010'
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'recebimento_analise_garantia.php',
			'titulo'  => traduz('Recebimento x Análise Garantia'),
			'descr'   => traduz('Contabilizar a data que a OS entrou em auditoria até a data que a OS foi liberada da auditoria "OS em auditoria de Defeito Constatado".'),
			"codigo"  => 'GER-11020'
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'tempo_falha_equipamento.php',
			'titulo'  => traduz('Tempo Médio de Falha por Equipamento'),
			'descr'   => traduz('Contabilizar a data da compra do produto pelo consumidor (data da NF) até a data de abertura da OS.'),
			"codigo"  => 'GER-11030'
		),
		array(
			'fabrica' => array(152,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'faturamento_peca_fechamento_os.php',
			'titulo'  => traduz('Faturamento da Peça x Fechamento da OS'),
			'descr'   => traduz('Contabiliza tempo de faturamento da peça até o fechamento da ordem de serviço.'),
			'codigo'  => 'GER-11040'
		),
		'link' => 'linha_de_separação',
	),
	// Seção QUALIDADE - Apenas Bosch
	'secaoQ' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('RELATÓRIOS - QUALIDADE'),
			'fabrica' => array(20)
		),
		array(
			'icone'  => $icone["relatorio"],
			'link'   => 'extrato_pagamento_peca.php',
			'titulo' => traduz('Peça X Custo'),
			'descr'  => traduz('Relatório de OSs e seus produtos e valor pagos por peça.'),
			"codigo" => "GER-12000"
		),
		array(
			'icone'  => $icone["relatorio"],
			'link'   => 'relatorio_field_call_rate_produto_custo.php',
			'titulo' => traduz('Field Call Rate de Produto X Custo'),
			'descr'  => traduz('Relatório de Field Call Rate de Produtos e valor pagos por período.'),
			"codigo" => "GER-12010"
		),
		'link' => 'linha_de_separação',
	),
	// Seção TAREFAS ADMINISTRATIVAS - Geral
	'secaoA' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('TAREFAS ADMINISTRATIVAS'),
			'fabrica_no' => array(87)
		),
		array(
			'fabrica' => array(2),
			'icone'   => $icone["acesso"],
			'link'    => 'posto_login.php',
			'titulo'  => traduz('Logar como Posto'),
			'descr'   => traduz('Acesse o sistema como se fosse o posto autorizado'),
			"codigo"  => 'GER-13000'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["computador"],
			'link'    => 'log_erro_integracao.php',
			'titulo'  => traduz('Log de erros de integração'),
			'descr'   => traduz('Verificar erros na integração entre Logix e Telecontrol'),
			"codigo"  => 'GER-13010'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["usuario"],
			'link'    => 'manutencao_contato.php',
			'titulo'  => traduz('Manutenção de contatos úteis'),
			'descr'   => traduz('Manutenção de contatos úteis da área do posto.'),
			"codigo"  => 'GER-13020'
		),
		array(
			'fabrica_no' => array(175),
			'icone'  => $icone["consulta"],
			'link'   => "https://ww2.telecontrol.com.br/docs?fabrica={$login_fabrica}&nome={$login_fabrica_nome}&key=".md5($login_fabrica_nome.$login_fabrica),
			'titulo' => traduz('API Pós-Venda DOC'),
			'descr'  => traduz('Documentação das APIs da Telecontrol para integração'),
			"codigo" => 'GER-13030'
		),
		array(
			'icone'  => $icone["usuario"],
			'link'   => 'admin_senha_n.php',
			'titulo' => traduz('Usuários ADMIN'),
			'descr'  => traduz('Cadastro de usuários administradores do sistema, com opção para troca de senha e atribuição de privilégios de acesso aos programas.'),
			"codigo" => 'GER-13040'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["usuario"],
			'link'    => 'admin_chat.php',
			'titulo'  => traduz('Usuários de Chat'),
			'descr'   => traduz('Administração de Usuário com acesso ao Chat'),
			"codigo"  => 'GER-13050'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["computador"],
			'link'    => 'fila_chat.php',
			'titulo'  => traduz('Fila de Chat'),
			'descr'   => traduz('Fila de atendimento pendente - Chat'),
			"codigo"  => 'GER-13060'
		  ),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["computador"],
			'link'    => 'chat_supervisao.php',
			'titulo'  => traduz('Supervisão de Chat\'s Ativos'),
			'descr'   => traduz('Painel para visualização e supervisão de atendimentos ativos'),
			"codigo"  => 'GER-13070'
		),
		array(
			'fabrica' => array(10,86), //Famastil, por enquanto
			'icone'   => $icone["computador"],
			'link'    => 'consulta_auto_credenciamento.php',
			'titulo'  => traduz('Auto-Credenciamento de Postos'),
			'descr'   => traduz('Lista postos que gostariam de ser credenciados da '.$login_fabrica_nome .',<br />').
			'mostra informações do posto, localiza no GoogleMaps<br />'.
			'e permite credenciar postos.',
			"codigo"  => 'GER-13080'
		),
		/**
		 * NÃO ATIVAR ESTE PROGRAMA PARA MAIS NENHUMA FÁBRICA SEM FALAR COMIGO. ÉBANO
		 **/
		//if(!in_array($login_fabrica,$fabricas_contrato_lite) and $login_fabrica<>72)
		array(
			'fabrica' => array(24,85),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_usuario_admin.php',
			'titulo'  => traduz('Relatório de Acesso'),
			'descr'   => traduz('Relatório de Controle de Acessos.'),
			"codigo"  => 'GER-13090'
		),
		array(
			'fabrica' => $fabrica_funcionalidades_admin,
			'icone'   => $icone["cadastro"],
			'link'    => 'funcionalidades_cadastro.php',
			'titulo'  => traduz('Cadastro de Funcionalidades'),
			'descr'   => traduz('Cadastro de Funcionalidades por Admin'),
			"codigo"  => 'GER-13100'
		),
		array(
			'fabrica' => array(10,25),
			'icone'   => $icone["email"],
			'link'    => 'envio_email_new.php',
			'titulo'  => traduz('Envio de e-mail'),
			'descr'   => traduz('Envia mensagens via e-mail para os Postos'),
			"codigo"  => 'GER-13110'
		),
		array(
			'fabrica' => array(14),
			'icone'   => $icone["email"],
			'link'    => 'comunicado_intelbras.php',
			'titulo'  => traduz('Envio de e-mail'),
			'descr'   => traduz('Envia mensagens via e-mail para os Postos'),
			"codigo"  => 'GER-13120'
		),
		array(
			'fabrica_no' => array(10,14,25),
			'icone'      => $icone["email"],
			'link'       => 'envio_email.php',
			'titulo'     => traduz('Envio de e-mail'),
			'descr'      => traduz('Envia mensagens via e-mail para os Postos'),
			"codigo"     => 'GER-13130'
		),
		array(
			'fabrica_no' => array(81, 114),
			'icone'      => $icone["limpar"],
			'link'       => 'limpa_dados.php',
			'titulo'     => traduz('Limpar Dados de Teste'),
			'descr'      => traduz('Apaga todas as informações do posto de teste, como OS, pedido e extrato'),
			"codigo"     => 'GER-13140'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["computador"],
			'link'    => 'reincidencia_os_cadastro.php',
			'titulo'  => traduz('Remanejamento de reincidências'),
			'descr'   => traduz('Efetua a substituição da OS reincidida para a OS principal.'),
			"codigo"  => 'GER-13150'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["computador"],
			'link'    => 'libera_os_item_pedido.php',
			'titulo'  => traduz('Liberar Item em Garantia'),
			'descr'   => traduz('Libera os itens das OSs para gerarem pedidos.'),
			"codigo"  => 'GER-13160'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["computador"],
			'link'    => 'libera_os_item_faturado.php',
			'titulo'  => traduz('Liberar Item de Vendas'),
			'descr'   => traduz('Libera os itens do Pedido Faturado.'),
			"codigo"  => 'GER-13170'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["upload"],
			'link'    => 'upload_importa.php',
			'titulo'  => traduz('Upload para Carga de Dados'),
			'descr'   => traduz('Efetua a carga de dados para atualização do sistema.'),
			"codigo"  => 'GER-13180'
		),
		array(
			'fabrica' => (isset($novaTelaOs)),
			'icone'   => $icone["upload"],
			'link'    => 'verificador_mapa_posto.php',
			'titulo'  => traduz('Localização dos Postos Autorizados'),
			'descr'   => traduz('Atualiza as informações dos Postos Autorizados no Mapa.'),
			"codigo"  => 'GER-13190'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["upload"],
			'link'    => 'upload_os_kof.php',
			'titulo'  => traduz('Reenvio OS para KOF'),
			'descr'   => traduz('Atualiza a lista de OS para exportação.'),
			"codigo"  => 'GER-13200'
		),
		array(
            'fabrica' => array(169,170),
            'icone'   => $icone["upload"],
            'link'    => 'consulta_pedido_nao_faturado_pecas.php',
            'titulo'  => traduz('Consulta Pedido de Peças Não faturado/Faturado parcialmente'),
            'descr'   => traduz('Realiza Consulta de pedido não faturado / faturado parcialmente'),
            "codigo"  => 'GER-13210'
        ),
        array(
            'fabrica' => array(169,170),
            'icone'   => $icone["upload"],
            'link'    => 'upload_faturar_pedido_pecas.php',
            'titulo'  => traduz('Atualiza da de entrega pedido de peças'),
            'descr'   => traduz('Atualiza previsão data de entrega de pedido de peças'),
            "codigo"  => 'GER-13220'
        ),
		array(
			'fabrica' => [10, 169, 170],
            'icone'   => $icone["usuario"],
            'link'    => 'admin_restricao_ip.php',
            'titulo'  => traduz('Limitar Acesso por IP'),
		    'descr'   => traduz('Limita o acesso de usuáos com base no endereço IP, de acordo com as faixas de IP cadastradas.'),
			"codigo"  => 'GER-13230'
		),
		array(

            'fabrica' => [148],
            'icone'   => $icone["computador"],
            'link'    => 'manutencao_email_admin.php',
            'titulo'  => 'Manutenção dos Emails dos Admins',
            'descr'   => 'Efetua a manutenção dos emails dos admins no sistema',
            "codigo"  => 'GER-13240'
        ),
		array(		
			'icone'   =>  $icone["consulta"],
			'link'    => 'manutencao_km_posto.php',
			'titulo'  => traduz('Manutenção valor por KM Postos'),
			'descr'   => traduz('Realizar manutenção de valor pago por KM para um, vários ou todos os Postos.'),
			"codigo"  => 'GER-13250'
		),
			array(		
			'fabrica' => array(186),
			'icone'   =>  $icone["bi"],
			'link'    => 'cotacao_frete_correios.php',
			'titulo'  => 'Cotar Frete Pedidos',
			'descr'   => 'Cotação de frete e seleção de tipo de serviço para enviar pedidos do posto.',
			"codigo"  => 'GER-13260'
		),
			array(		
			'fabrica' => array(186),
			'icone'   =>  $icone["bi"],
			'link'    => 'imprimir_etiqueta_correios.php',
			'titulo'  => 'Imprimir Etiqueta e Declaração de Conteúdo',
			'descr'   => 'Imprimir etiquetas geradas para os pedidos junto com a Declaração de Conteúdo.',
			"codigo"  => 'GER-13270'
		),
			array(		
			'fabrica' => array(186),
			'icone'   =>  $icone["bi"],
			'link'    => 'gerar_plp_pedidos.php',
			'titulo'  => 'Gerar PLP Pedidos',
			'descr'   => 'Geração da Pré Lista de Postagem para as etiquetas geradas.',
			"codigo"  => 'GER-13280'
		),
		array(
			'fabrica' => array(183),		
			'icone'   =>  $icone["cadastro"],
			'link'    => 'regras_parametros_pedido.php',
			'titulo'  => 'Manutenção regras parâmetros pedidos',
			'descr'   => 'Realizar manutenção nas regras de parâmetros para geração de pedidos.',
			"codigo"  => 'GER-13290'
		),

		'link' => 'linha_de_separação',
	),
	// Seção PESQUISA DE OPINIÃO - Geral
	'secaoO' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('PESQUISA DE OPINIÃO'),
			'fabrica'    => (in_array($login_fabrica, array(3,10,151)) or ($login_fabrica > 87 && !$novaTelaOs && !in_array($login_fabrica, array(172)) )),
			'fabrica_no' => array_merge(array(87,91,104,114,115,116,117,120,201,121,122,126,129,132,136,137,138,139,141,142,143,144,145,146), $fabricas_contrato_lite,$novaTelaOs)
		),
		array(
			'icone'  => $icone["cadastro"],
			'link'   => 'opiniao_posto.php',
			'titulo' => traduz('Cadastro do Questionário'),
			'descr'  => traduz('Cadastro do Questionário de Opinião do Posto'),
			"codigo" => 'GER-14000'
		),
		array(
			'fabrica' => (in_array($login_fabrica, array(88,94,134,151))),
			'icone'   => $icone["relatorio"],
			'link'    => 'opiniao_posto_relatorio.php',
			'titulo'  => traduz('Relatório de Opinião dos Postos'),
			'descr'   => traduz('Relatório dos questionários enviados aos Postos'),
			"codigo"  => 'GER-14010'
		),
	 array(
	    'fabrica' => (in_array($login_fabrica, array(10))),
	    'icone'   => $icone["relatorio"],
	    'link'    => 'relatorio_pesquisa_inicial_posto.php',
	    'titulo'  => traduz('Relatório de Pesquisa de Opinião dos Postos'),
	    'descr'   => traduz('Relatório dos questionários enviados para saber a opinião dos Postos'),
	    "codigo"  => 'GER-14020'
	 ),
		'link' => 'linha_de_separação',
	),
	// Seção CADASTRO DOCUMENTAÇÃO - Geral
	'secaDC' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('DOCUMENTAÇÃO FABRICA'),
			'fabrica' => (in_array($login_fabrica, array(10)))
		),
		array(
			'icone'  => $icone["cadastro"],
			'link'   => 'documentacao_fabricas.php',
			'titulo' => traduz('Cadastro do Documentação'),
			'descr'  => traduz('Cadastro de Documentação para Fábricas'),
			"codigo" => 'GER-15000'
		),
		'link' => 'linha_de_separação',
	),
	// Seção DISTRIB - Apenas Telecontrol
	'secaoD' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('DISTRIBUIÇÃO TELECONTROL'),
			'fabrica' => $fabrica_distrib
		),
		array(
			'icone'  => $icone["computador"],
			'link'   => 'distrib_pendencia.php',
			'titulo' => traduz('Pendência de Peças'),
			'descr'  => traduz('Pendência de Peças dos Postos junto ao Distribuidor'),
			"codigo" => "GER-TC10"
		),
		array(
			'admin'  => 586,
			'icone'  => $icone["computador"],
			'link'   => 'distrib_pendencia_estudo.php',
			'titulo' => traduz('Estudo das Pendências de Peças'),
			'descr'  => traduz('Estudo das pendências de peças e sugestão de pedido para fábrica (Garantia/Compra)'),
			"codigo" => "GER-TC20"
		),
		'link' => 'linha_de_separação',
	),
	// Seção CONSULTAS - Apenas Jacto
	'secaoJC' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('CONSULTAS'),
			'fabrica' => array(87)
		),
		array(
			'icone'  => $icone["consulta"],
			'link'   => 'pedido_parametros.php',
			'titulo' => traduz('Consulta Pedidos de Peças'),
			'descr'  => traduz('Consulta pedidos efetuados por postos autorizados.'),
			"codigo" => "GER-JC10"
		),
		'link' => 'linha_de_separação',
	),
	// Seção ADMN - Apenas Jacto
	'secaoJA' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('TAREFAS ADMINISTRATIVAS'),
			'fabrica' => array(87)
		),
		array(
			'icone'  => $icone["usuario"],
			'link'   => 'admin_senha_n.php',
			'titulo' => traduz('Usuários ADMIN'),
			'descr'  => traduz('Cadastro de usuários administradores do sistema, com opção para troca de senha e atribuição de privilégios de acesso aos programas.'),
			"codigo" => "GER-JA10"
		),
		'link' => 'linha_de_separação',
	),
	// Apenas Midea
	'secaoMQL' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('QUALIDADE'),
			'fabrica' => array(169,170)
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_qualidade.php',
			'titulo'  => traduz('Relatório de OS'),
			'descr'   => traduz('Relatório de OS para a Qualidade.'),
			"codigo"  => 'GER-16000'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_producao_venda_qualidade.php',
			'titulo'  => traduz('Relatório de Produção/Vendas'),
			'descr'   => traduz('Relatório de Produção e Vendas para a Qualidade.'),
			"codigo"  => 'GER-16010'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_fcr_x_vendas_qualidade.php',
			'titulo'  => traduz('Relatórios sobre Vendas'),
			'descr'   => traduz('Relatórios/Gráficos sobre vendas para a Qualidade.'),
			"codigo"  => 'GER-9120'
		),
		array(
            'icone'   => $icone["cadastro"],
            'link'    => 'planejamento_cadastro.php',
            'titulo'  => traduz('Planejamento Qualidade'),
	        'descr'   => traduz('Formulário de cadastro e revisões do planejamento da qualidade.'),
			"codigo"  => 'GER-9130'
		),
		array(
            'icone'   => $icone["cadastro"],
            'link'    => 'planejamento_pd_cadastro.php',
            'titulo'  => traduz('Planejamento por PD Qualidade'),
	        'descr'   => traduz('Formulário de cadastro e revisões do planejamento da qualidade para produtos importados por PD.'),
			"codigo"  => 'GER-9140'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_top_five_qualidade.php',
			'titulo'  => traduz('Relatório TOP FIVE - QUALIDADE'),
			'descr'   => traduz('Relatórios/Gráficos dos 5 itens com mais quebras para a Qualidade.'),
			"codigo"  => 'GER-9150'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_top_five_produtos_pareto.php',
			'titulo'  => traduz('Relatório TOP FIVE - PARETO'),
			'descr'   => traduz('Relatórios/Gráficos TOP Five Pareto'),
			"codigo"  => 'GER-9160'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_performance_prod_escape.php',
			'titulo'  => 'Relatórios de Performance',
			'descr'   => 'Relatórios/Gráficos sobre performance de falha.',
			"codigo"  => 'GER-9170'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_failure_rate.php',
			'titulo'  => 'Relatório de Falhas x Produção',
			'descr'   => 'Relatório falhas / production por peças e produtos',
			"codigo"  => 'GER-9180'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'fcr_production_sales.php',
			'titulo'  => 'Relatório de Performance',
			'descr'   => 'FCR Production x Unit Sold',
			"codigo"  => 'GER-9190'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_montanha_falha.php',
			'titulo'  => 'Relatório Montanha % Falha',
			'descr'   => 'Relatório de quebras por produção',
			"codigo"  => 'GER-9200'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tempo_falha.php',
			'titulo'  => 'Relatório de Falhas por Componente',
			'descr'   => 'Relatórios/Gráficos de falhas por peça',
			"codigo"  => 'GER-9210'
		),
		'link' => 'linha_de_separação',
	),
	// Seção GESTAO DE CONTRATOS
	'secaoGC' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => 'GESTÃO DE CONTRATOS',
			'fabrica' => ($moduloGestaoContrato == 't') ? $login_fabrica : 0
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'dashboard_contratos.php',
			'titulo'     => 'Dashboard Proposta x Contrato',
			'descr'      => 'Relatório Dashboard de Proposta x Contrato',
			"codigo"     => 'GER-16070'
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'tipo_de_contrato.php',
			'titulo'     => 'Cadastra Tipo de Contrato',
			'descr'      => 'Cadastra um Tipo de Contrato',
			"codigo"     => 'GER-16020'
		),
		array(
			'icone'      => $icone["consulta"],
			'link'       => 'consulta_contrato.php',
			'titulo'     => 'Consulta Contratos',
			'descr'      => 'Consulta Contratos',
			"codigo"     => 'GER-16030'
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_contrato.php?tipo=proposta',
			'titulo'     => 'Cadastro de Contratos',
			'descr'      => 'Cadastro de Contratos',
			"codigo"     => 'GER-16040'
		),
		array(
			'icone'      => $icone["relatorio"],
			'link'       => 'auditoria_contrato.php',
			'titulo'     => 'Auditoria de Contrato',
			'descr'      => 'Aprova ou reprova auditoria do contrato.',
			"codigo"     => 'GER-16050'
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_tabela_preco.php',
			'titulo'     => 'Cadastra Tabela de Preço',
			'descr'      => 'Cadastro de Tabela de Preço do Contrato',
			"codigo"     => 'GER-16060'
		)/*,
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'relatorio_custo_produto_n_serie.php',
			'titulo'     => 'Relatório Custo de Produtos',
			'descr'      => 'Relatório Custo de Produtos por Número de Série',
			"codigo"     => 'GER-16070'
		)*/,
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'relatorio_custo_produto_contrato.php',
			'titulo'     => 'Relatório Custo de Produtos por Contrato',
			'descr'      => 'Relatório Custo de Produtos por Contrato',
			"codigo"     => 'GER-16080'
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'acompanhamento_os_contrato.php',
			'titulo'     => 'Acompanhamento de Ordem de Serviços',
			'descr'      => 'Acompanhamento de Ordem de Serviços',
			"codigo"     => 'GER-16090'
		),
		'link' => 'linha_de_separação',
	),

);
