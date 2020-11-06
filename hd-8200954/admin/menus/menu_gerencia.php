<?php
include_once 'funcoes.php';

msgBloqueioMenu();

// Fabricas que tem distribui��o via DISTRIB Telecontrol
$fabrica_distrib = array(51, 81, 114, 147);

//HD 666788 - Funcionalidades por admin
$sql = "SELECT fabrica
      FROM tbl_funcionalidade
     WHERE fabrica=$login_fabrica OR fabrica IS NULL";
$res = pg_query($con,$sql);
$fabrica_funcionalidades_admin = (pg_num_rows($res)>0);
/*
	hd-1149884 -> Para as f�bricas que tiverem o par�metro adicional fabrica_padrao='t', a tela:
	http://posvenda.telecontrol.com.br/assist/admin/os_consulta_procon.php
	N�o ser�o mais utilizadas.
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
// Se��o CREDENCIAMENTO - Geral

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
			'titulo'     => traduz('Relat�rio Informativo'),
			'fabrica'    => $arrPermissoesAdm["analise_ri"] == "t" ? [169,170] : []
		),
		array(
			'fabrica' => array(169,170),
			'icone'   => $icone["consulta"],
			'link'    => 'consulta_relatorio_informativo.php',
			'titulo'  => traduz('Consulta dos RI\'s'),
			'descr'   => traduz("Consulta dos Relat�rios informativos."),
			"codigo"  => 'GER-18000'
		),
		array(
			'fabrica' => array(169,170),
			'icone'   => $icone["computador"],
			'link'    => 'cadastro_relatorio_informativo.php',
			'titulo'  => traduz('Preenchimento do RI'),
			'descr'   => traduz('Preenchimento do relat�rio informativo'),
			"codigo"  => 'GER-18010'
		),
		array(
			'fabrica' => array(169,170),
			'icone'   => $icone["usuario"],
			'link'    => 'grupo_followup_relatorio_informativo.php',
			'titulo'  => traduz('Grupos de Follow-up'),
			'descr'   => traduz('Amarra��o dos admins com os grupos de follow-up para o relat�rio informativo'),
			"codigo"  => 'GER-18020'
		),
		'link' => 'linha_de_separa��o',
	),
	'secao0' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('CREDENCIAMENTO DE ASSIST�NCIAS T�CNICAS'),
			'fabrica_no' => array(87),
			'fabrica'    => array(24, 25, 47)
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["computador"],
			'link'    => 'credenciamento_suggar.php',
			'titulo'  => traduz('Credenciamento de Assist�ncias T�cnicas'),
			'descr'   => traduz('Credenciamento e Descredenciamento de Assist�ncias T�cnicas.'),
			"codigo"  => 'GER-0010'
		),
		array(
			'fabrica' => array(25),
			'icone'   => $icone["computador"],
			'link'    => '../credenciamento/hbtech/index_.php',
			'titulo'  => traduz('Credenciamento de Assist�ncias T�cnicas'),
			'descr'   => traduz('Credenciamento e Descredenciamento de Assist�ncias T�cnicas.'),
			"codigo"  => 'GER-0020'
		),
		array(
			'fabrica' => array(25),
			'icone'   => $icone["computador"],
			'link'    => '../credenciamento/gera_contrato_crown.php',
			'titulo'  => traduz('Contrato Assist�ncias T�cnicas'),
			'descr'   => traduz('Contrato para Assist�ncias T�cnicas.'),
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
		'link' => 'linha_de_separa��o',
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
            'titulo'  => 'Cadastro Formul�rio de Pesquisa',
            'descr'   => 'Permite o cadastro de um formul�rio de pesquisas din�mico',
            'codigo'  => 'GER-20000'
        ),
	    array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_respostas_pesquisa.php',
			'titulo'  => 'Relat�rio de Respostas da Pesquisa',
			'descr'   => 'Relat�rio detalhado com as respostas da pesquisa',
			"codigo"  => 'GER-20010'
	    ),
		'link' => 'linha_de_separa��o',
	),
	// Se��o CADASTRO DE FABRICANTES - Interno Telecontrol
	'secao1' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('RELAT�RIOS'),
			'fabrica' => array(10)
		),
		array(
			'admin'  => array(398, 432, 435), //S�o admins da f�brica Telecontrol...
			'icone'  => $icone["cadastro"],
			'link'   => 'fabricante_cadastro.php',
			'titulo' => traduz('Cadastro de f�bricas'),
			'descr'  => traduz('Cadastro de fabricantes pela p�gina.'),
			"codigo" => 'GER-1010'
		),
		array(
			'icone'  => $icone["cadastro"],
			'link'   => 'posto_credenciamento.php',
			'titulo' => traduz('Credenciar Autorizada'),
			'descr'  => traduz('Cadastramento da rede autorizada para este fabricante.'),
			"codigo" => 'GER-1020'
		),
		'link' => 'linha_de_separa��o',
	),
	// Se��o assinatura, so black
	'secao4' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('APROVA��ES GER�NCIA'),
			'fabrica' => array(1)
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["computador"],
			'link'    => 'extrato_assinatura.php',
			'titulo'  => traduz('Extratos'),
			'descr'   => traduz('Assinatura eletr�nica de libera��o de extratos para o financeiro.'),
			"codigo"  => 'GER-1430'
		),	
		array(
			'fabrica' => array(1),
			'icone'   => $icone["computador"],
			'link'    => 'aprova_solicitacao_cheque.php',
			'titulo'  => traduz('Solicita��o de Cheque'),
			'descr'   => traduz('Permite consultar, imprimir e aprovar as solicita��es de cheque reembolso'),
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
	// Se��o CONSULTAS - Geral
	'secao2' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('CONSULTAS'),
			'fabrica_no' => array(87, 108, 111)
		),
		array(
			'icone'  => $icone["consulta"],
			'link'   => 'os_parametros.php',
			'titulo' => traduz('Consulta Ordens de Servi�o'),
			'descr'  => traduz('Consulta OS Lan�adas'),
			"codigo" => 'GER-2010'
		),
		array(
			'icone'  => $icone["consulta"],
			'link'   => 'pedido_parametros.php',
			'titulo' => traduz('Consulta Pedidos de Pe�as'),
			'descr'  => traduz('Consulta pedidos efetuados por postos autorizados.'),
			"codigo" => 'GER-2020'
		),
		array(
			'fabrica_no' => array(122),
			'icone'      => $icone["consulta"],
			'link'       => 'acompanhamento_os_revenda_parametros.php',
			'titulo'     => ($login_fabrica == 178)? traduz('Acompanhamento de OS') : traduz('Acompanhamento de OS Revenda'),
			'descr'      => traduz('Consulta OS de Revenda Lan�adas e Finalizadas'),
			"codigo"     => 'GER-2030'
		),
		array(
			'fabrica' => array(43),
			'icone'   => $icone["consulta"],
			'link'    => 'status_os_posto.php',
			'titulo'  => traduz('Acompanhamento de OS em aberto'),
			'descr'   => traduz('Consulta status das Ordens de Servi�o em aberto'),
			"codigo"  => 'GER-2040'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["consulta"],
			'link'    => 'os_enviadas_tectoy.php',
			'titulo'  => traduz('OS com pe�as enviadas a f�brica'),
			'descr'   => traduz('Consulta OSs que o posto enviou pe�as para a f�brica. Autoriza ou n�o o pagamento de metade da m�o-de-obra.'),
			"codigo"  => 'GER-2050'
		),
		array(
			'fabrica' => array(6,91),
			'icone'   => $icone["cadastro"],
			'link'    => 'manutencao_periodo_visualizacao_extrato_posto.php',
			'titulo'  => traduz('Per�odo visualiza��o extrato.'),
			'descr'   => traduz('Visualizar / Alterar o per�odo que � demonstrado para cada posto.'),
			"codigo"  => 'GER-2051'
		),
		array(
			'fabrica' => array(3), // HD 55242
			'icone'   => $icone["consulta"],
			'link'    => 'os_consulta_agrupada.php',
			'titulo'  => traduz('Consulta Ordem de Servi�o Agrupada'),
			'descr'   => traduz('Consulta OS agrupada.'),
			"codigo"  => 'GER-2060'
		),
		array(
			'fabrica' => array(1),
			'admin'   => 236,
			'icone'   => $icone["computador"],
			'link'    => 'os_consulta_lite_etiqueta.php',
			'titulo'  => traduz('Consulta OSs e gera etiquetas'),
			'descr'   => traduz('Transfer�ncia de OS entre postos'),
			"codigo"  => 'GER-2070'
		),
		array(
			'fabrica' => array(14),
			'icone'   => $icone["computador"],
			'link'    => 'os_transferencia.php',
			'titulo'  => traduz('Transfer�ncia de OS'),
			'descr'   => traduz('Transfer�ncia de OS entre postos'),
			"codigo"  => 'GER-2080'
		),
		array(
			'fabrica' => array(7),
			'icone'   => $icone["computador"],
			'link'    => 'os_transferencia_filizola.php',
			'titulo'  => traduz('Transfer�ncia de OS'),
			'descr'   => traduz('Transfer�ncia de OS entre postos'),
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
			'titulo'  => traduz('Relat�rio Estoque de Pe�as - Entrada e Saida'),
			'descr'   => traduz('Consulta de estoque da Pe�as e sua Movimenta��o(Entrada/Saida).'),
			"codigo"  => 'GER-2100'
		),
	array(
			'fabrica' => array(24),
			'icone'   => $icone["consulta"],
			'link'    => 'relatorio_tempo_os_finalizada.php',
			'titulo'  => traduz('Relat�rio tempo OS finalizada'),
			'descr'   => traduz('Consulta tempo OS finalizada'),
			"codigo"  => 'GER-2110'
		),
	array(
			'fabrica' => array(11,24,42,81,172,183),
			'icone'   => $icone["cadastro"],
			'link'    => 'cadastro_processos.php',
			'titulo'  => traduz('Cadastro de Processos'),
			'descr'   => traduz('Cadastramento de processos Jur�dicos.'),
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
		'link' => 'linha_de_separa��o',
	),
	// Se��o RELAT�RIOS - Geral
	'secao3' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('RELAT�RIOS'),
			'fabirca_no' => array(87)
		),
		array(
			'fabrica'    => array(3),
			'icone'      => $icone["relatorio"],
			'background' => '#AAAAAA',
			'link'       => '#relatorio_lancamentos..php',
			'titulo'     => traduz('Lan�amentos'),
			'descr'      => traduz('Postos que est�o lan�ando ordens de servi�o no site.'),
			"codigo"     => 'GER-3010'
		),
		array(//HD 44656
			'fabrica' => array(14,15,43,66),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto.php',
			'titulo'  => traduz('Field Call-Rate - Produtos'),
			'descr'   => traduz('Percentual de quebra de produtos.<br><i>Considera apenas ordem de servi�o fechada, apresentando custos</i>'),
			"codigo"  => 'GER-3020'
		),
		array(
			'fabrica' => array(96),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_grafico_rel_os_finalizada.php',
			'titulo'  => traduz('OS abertas em Garantia e Fora de Garantia'),
			'descr'   => traduz('Este Relat�rio mostra atrav�s de gr�ficos as OS abertas dentro e fora de garantia'),
			"codigo"  => 'GER-3030'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_indice_defeito.php',
			'titulo'  => traduz('Relat�rio de �ndice de Defeito de Campo'),
			'descr'   => traduz('Este relat�rio contempla o �ndice de defeitos de Campo.'),
			"codigo"  => 'GER-3040'
		),
		array(
			'fabrica' => array(94),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_periodo.php',
			'titulo'  => traduz('Relat�rio de OS por Per�odo'),
			'descr'   => traduz('Este relat�rio considera a Data de Fechamento das Ordens de Servi�o.'),
			"codigo"  => 'GER-3050'
		),
		array(
			'fabrica' => array(94),
			'icone'   => $icone["relatorio"],
			'link'    => 'indice_ocorrencia_mensal.php',
			'titulo'  => traduz('Relat�rio de �ndice de Ocorr�ncia Mensal'),
			'descr'   => traduz('Este relat�rio contempla o �ndice de ocorr�ncia de defeitos no intervalo de tempo determinado pelo usu�rio.'),
			"codigo"  => 'GER-3060'
		),
		array(
			'fabrica' => array_merge($arr_fabrica_distrib, [141,174]),
			'icone'   => $icone["bi"],
			'link'    => 'relatorio_status_os_tempo.php',
			'titulo'  => traduz('Relat�rio de Timeline de O.S'),
			'descr'   => traduz('Relat�rio que apresenta o tempo da O.S em cada Status'),
			"codigo"  => 'GER-3069'
		),
		array(
			'fabrica' => array_merge($arr_fabrica_distrib, [141,174]),
			'icone'   => $icone["bi"],
			'link'    => 'relatorio_status_pedido_tempo.php',
			'titulo'  => traduz('Relat�rio de Timeline de Pedido'),
			'descr'   => traduz('Relat�rio que apresenta o tempo da O.S em cada Status'),
			"codigo"  => 'GER-3069'
		),
		array(
			'icone'      => $icone["bi"],
			'link'       => 'bi/fcr_os.php',
			'titulo'     => traduz('BI -Field Call Rate - Produtos'),
			'descr'      => traduz('Percentual de quebra de produtos.<br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i>'),
			"codigo"     => 'GER-3070',
			"fabrica_no" => array(138)
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["bi"],
			'link'    => 'bi/fcr_os_detalhado.php',
			'titulo'  => traduz('BI -Field Call Rate - Detalhado'),
			'descr'   => traduz('Detalhamento do Field Call Rate Produtos para Auditoria.<br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i>'),
			"codigo"  => 'GER-3080'
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["bi"],
			'link'    => 'bi/fcr_os_detalhado_peca.php',
			'titulo'  => traduz('BI -Field Call Rate - Defeitos'),
			'descr'   => traduz('Detalhamento do Field Call Rate Produtos e pe�as com defeito, para Auditoria.<br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i>'),
			"codigo"  => 'GER-3090'
		),
		array(
			'fabrica' => array(3, 24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto2.php',
			'titulo'  => traduz('Field Call Rate - Produtos 2'),
			'descr'   => traduz('Relat�rio do percentual de defeitos das pe�as por produto.'),
			"codigo"  => 'GER-3100'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto3_britania.php',
			'titulo'  => traduz('Field Call Rate - Produtos 3'),
			'descr'   => traduz('Considera OS lan�adas no sistema filtrando pela data da digita��o ou finaliza��o.'),
			"codigo"  => 'GER-3110'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto3.php',
			'titulo'  => traduz('Field Call Rate - Produtos 3'),
			'descr'   => traduz('Considera OS lan�adas no sistema filtrando pela data da digita��o ou finaliza��o.'),
			"codigo"  => 'GER-3120'
		),
		array(
			'fabrica_no' => $fabricas_contrato_lite,
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_field_call_rate_produto_lista_basica.php',
			'titulo'     => traduz('Field Call Rate - Produtos Lista B�sica'),
			'descr'      => traduz('Relat�rio de quebras de pe�as da lista b�sica do produto'),
			"codigo"     => 'GER-3130'
		),
		array(
			'fabrica' => array(66,14),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_posto.php',
			'titulo'  => traduz('Field Call Rate - Postos'),
			'descr'   => traduz('Relat�rio de ocorr�ncia de OS por familia por postos.'),
			"codigo"  => 'GER-3140'
		),
		array(
			'fabrica_no' => array($bi_peca),
			'icone'      => $icone["bi"],
			'link'       => 'bi/fcr_pecas.php',
			'titulo'     => ($login_fabrica==24) ? traduz('Field Call-Rate - Produtos 4') : traduz('BI Field Call-Rate - Pe�as'),
			'descr'      => traduz('Percentual de quebra de pe�as.<br><i>O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!</i>'),
			"codigo"     => 'GER-3150'
		),
		array(
			'fabrica' => array(14, 66),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_defeito_constatado.php',
			'titulo'  => traduz('Field Call Rate - Defeitos Constatados'),
			'descr'   => traduz('Relat�rio de ocorr�ncia de OS por defeitos constatados.'),
			"codigo"  => 'GER-3160'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeitos.php',
			'titulo'  => traduz('Relat�rio de defeitos'),
			'descr'   => traduz('Relat�rio de defeitos de produtos e solu��es a partir da data de digita��o'),
			"codigo"  => 'GER-3170'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_engenharia_serie.php',
			'titulo'  => traduz('Relat�rio de defeitos por N� s�rie'),
			'descr'   => traduz('Relat�rio de defeitos de produtos e solu��es a partir do n�mero de s�rie'),
			"codigo"  => 'GER-3180'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_serie_reoperado.php',
			'titulo'  => traduz('Relat�rio N� s�rie Reoperado'),
			'descr'   => traduz('Relat�rio de n�mero de s�ries reoperados.'),
			"codigo"  => 'GER-3190'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_serie_fabricacao.php',
			'titulo'  => traduz('Field Call-Rate - Produtos N�mero de S�rie'),
			'descr'   => traduz('Relat�rio de quebra dos produtos pela data de fabrica��o.'),
			"codigo"  => 'GER-3200'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto_grupo.php',
			'titulo'  => traduz('Field Call-Rate - Produtos N�mero de S�rie 2'),
			'descr'   => traduz('Relat�rio de quebra dos produtos X n�mero de s�rie.'),
			"codigo"  => 'GER-3210'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto_pecas.php',
			'titulo'  => traduz('Field Call-Rate - M�o-de-obra Produtos X Pe�as'),
			'descr'   => traduz('Relat�rio m�o-de-obra por produto e troca de pe�a espec�ficos.'),
			"codigo"  => 'GER-3220'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_pecas.php',
			'titulo'  => traduz('Relat�rio Troca de Pe�a'),
			'descr'   => traduz('Relat�rio de pe�as trocadas pelo posto autorizado, pe�as trocadas em garantia ou pagas pelos clientes'),
			"codigo"  => 'GER-3230'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_sem_troca_peca.php',
			'titulo'  => traduz('Relat�rio de OS sem troca de Pe�a'),
			'descr'   => traduz('Relat�rio em ordem de posto autorizado com maior �ndice de Ordens de Servi�os sem troca de pe�a.'),
			"codigo"  => 'GER-3240'
		),
		array(
			'fabrica_no' => array(81,114),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_os_peca_sem_pedido.php',
			'titulo'     => traduz('Relat�rio de OS de Pe�a sem Pedido'),
			'descr'      => traduz('Relat�rio em ordem de posto autorizado com maior �ndice de Ordens de Servi�os de pe�a sem pedido.'),
			"codigo"     => 'GER-3250'
		),
		array(
			'fabrica' => array(50,158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_gerencial_oss.php',
			'titulo'  => traduz('Relat�rio Gerencial de OS'),
			'descr'   => traduz('Relat�rio que contem as Ordens de Servi�os pendentes de varios per�odos.'),
			"codigo"  => 'GER-3255'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(175)),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_peca_sem_preco.php',
			'titulo'     => traduz('Relat�rio de Pe�a em OS sem Pre�o'),
			'descr'      => traduz('Relat�rio que mostra as pe�as que est�o cadastradas em uma OS mas n�o possuem pre�o cadastrado.'),
			"codigo"     => 'GER-3260'
		),
		array(
			'fabrica' => array(106,115,116,117,120,201,121,122,127,134,169,170),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_reincidente.php',
			'titulo'  => traduz('Relat�rio de OSs reincidentes'),
			'descr'   => traduz('Relat�rio de Ordens de Servi�o Reincidentes'),
			"codigo"  => 'GER-3270'
		),
		array(
			'fabrica' => array(40,106,111,108),
			'icone'   => $icone["relatorio"],
			'link'    => 'os_mais_tres_pecas.php',
			'titulo'  => traduz('OS com mais de 3 pe�as'),
			'descr'   => traduz('Relat�rio para auditoria dos postos que utilizam mais de 3 pe�as por Ordem de Servi�o.'),
			"codigo"  => 'GER-3280'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(14)),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_quantidade_os.php',
			'titulo'     => traduz('Relat�rio de Quantidade de OSs Aprovadas por LINHA'),
			'descr'      => traduz('Relat�rio que mostra a quantidade de OS aprovadas por postos em determinadas linhas nos �ltimos 3 meses.'),
			"codigo"     => 'GER-3290'
		),
		array(
			'fabrica' => array(86),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_devolucao_obrigatoria.php',
			'titulo'  => traduz('Devolu��o Obrigat�ria'),
			'descr'   => traduz('Pe�as que devem ser devolvidas para a F�brica constando em Ordens de Servi�os.'),
			"codigo"  => 'GER-3300'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_devolucao_obrigatoria_tectoy.php',
			'titulo'  => traduz('Total de Pe�as Devolu��o Obrigat�ria'),
			'descr'   => traduz('Total de pe�as que devem ser devolvidas para a F�brica.'),
			"codigo"  => 'GER-3310'
		),
		/* array(
			'fabrica'   => array(11,172),
			'icone'     => $icone["relatorio"],
			'link'      => 'relatorio_percentual_defeitos.php',
			'titulo'    => 'Percentual de Defeitos',
			'descr'     => 'Relat�rio por per�odo de percentual dos defeitos de produtos.',
			"codigo" => 'GER-3320'
		), */
		array(
			'fabrica_no' => $fabricas_contrato_lite,
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_percentual_defeitos.php',
			'titulo'     => traduz('Percentual de Defeitos'),
			'descr'      => traduz('Relat�rio por per�odo de percentual dos defeitos de produtos.'),
			"codigo"     => 'GER-3330'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_constatado_os_anual.php',
			'titulo'  => traduz('Relat�rio Anual de OS por Defeitos Constatados'),
			'descr'   => traduz('Relat�rio anual detalhando por fam�lia, grupo de defeito e defeito X mensal e total anual a quantidade de OS, bem como valores das mesmas'),
			"codigo"  => 'GER-3340'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_auditoria.php',
			'titulo'  => traduz('Relat�rio de Auditoria'),
			'descr'   => traduz('Relat�rio das Auditorias efetuadas nos postos autorizados'),
			"codigo"  => 'GER-3350'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(158)),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_tempo_conserto_mes.php',
			'titulo'     => traduz('Perman�ncia em conserto no m�s'),
			'descr'      => traduz('Relat�rio que mostra o tempo (dias) de perman�ncia do produto na assist�ncia t�cnica no m�s.'),
			"codigo"     => 'GER-3360'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tempo_os_aberta.php',
			'titulo'  => traduz('Relatorio de OS em abertos em dias'),
			'descr'   => traduz('Relatorio de OS em abertos em dias, considerando a data de abertura para o dia da gera��o do relat�rio.'),
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
		//liberado para Latinatec  30-12-2010 Aut. �bano., solicitado por Rodrigo Torres.
		array(
			'fabrica' => array(8, 11, 15, 20, 30, 43, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tempo_conserto.php',
			'titulo'  => traduz('Perman�ncia em conserto'),
			'descr'   => traduz('Relat�rio que mostra tempo m�dio (dias) de perman�ncia do produto na assist�ncia t�cnica.'),
			"codigo"  => 'GER-3390'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_defeitos_esmaltec.php',
			'titulo'  => traduz('Relat�rio Defeitos OS por Atendimento'),
			'descr'   => traduz('Relat�rio de Defeitos OS x Tipo de Atendimento.'),
			"codigo"  => 'GER-3400'
		),
		array(
			'fabrica'    => array(1,2,3,7,66),
			'icone'      => $icone["relatorio"],
			'background' => '#aaaaaa',
			'link'       => '#relatorio_prazo_atendimento_periodo.php',
			'titulo'     => traduz('Per�odo de atendimento da OS'),
			'descr'      => traduz('Relat�rio de acompanhamento do atendimento por per�odo de OS.'),
			"codigo"     => 'GER-3401'
		),
		array(
			'fabrica' => array(8), //liberado para Ibratele hd 138104
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_prazo_atendimento_periodo.php',
			'titulo'  => traduz('Per�odo de atendimento da OS'),
			'descr'   => traduz('Relat�rio de acompanhamento do atendimento por per�odo de OS.'),
			"codigo"  => 'GER-3410'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_qualidade.php',
			'titulo'  => traduz('Per�odo de atendimento da OS'),
			'descr'   => traduz('Relat�rio de acompanhamento do atendimento por per�odo de OS.'),
			"codigo"  => 'GER-3420'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_perguntas_britania.php',
			'titulo'  => traduz('Relat�rio DVD Fama e Game'),
			'descr'   => traduz('Relat�rio que mostra a quantidade de P. A. participaram do question�rio.'),
			"codigo"  => 'GER-3430'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(24)),
			'icone'      => $icone["relatorio"],
			'link'       => 'produtos_mais_demandados.php',
			'titulo'     => traduz('Produtos mais demandados'),
			'descr'      => traduz('Relat�rio dos produtos mais demandados em Ordens de Servi�os nos �ltimos meses.'),
			"codigo"     => 'GER-3440'
		),
		array(
			'fabrica' => array(5,14,19,43,66),
			'icone'   => $icone["relatorio"],
			'link'    => 'defeito_os_parametros.php',
			'titulo'  => traduz('Relat�rio de Ordens de Servi�o'),
			'descr'   => traduz('Relat�rio de Ordens de Servi�o lan�adas no sistema.'),
			"codigo"  => 'GER-3450'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["consulta"],
			'link'    => 'auditoria_os_fechamento_blackedecker.php',
			'titulo'  => traduz('Auditoria de pe�as trocadas em garantia'),
			'descr'   => traduz('Faz pesquisas nas Ordens de Servi�os previamente aprovadas em extrato.'),
			"codigo"  => 'GER-3460'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_os.php',
			'titulo'  => traduz('Relat�rio de Troca de OS'),
			'descr'   => traduz('Verifica as OS de troca do PA.'),
			"codigo"  => 'GER-3470'
		),
		array(
			'fabrica' => array(2, 3, 11, 24, 172), //liberado para Lenoxx hd 8231
			'icone'   => $icone["relatorio"],
			'link'    => 'pendencia_posto.php',
			'titulo'  => traduz('Pend�ncias do posto'),
			'descr'   => traduz('Relat�rio de pe�as pendentes dos postos.'),
			"codigo"  => 'GER-3480'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produto_defeito_troca.php',
			'titulo'  => traduz('Relat�rio de Troca de Pe�as'),
			'descr'   => traduz('Relat�rio de pe�as trocas os defeitos apresentados, listado por produtos.'),
			"codigo"  => 'GER-3490'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_serie_reincidente.php',
			'titulo'  => traduz('Relat�rio OS S�rie Reincidente'),
			'descr'   => traduz('Relat�rio OS S�rie Reincidente.'),
			"codigo"  => 'GER-3500'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_os_reincidente.php',
			'titulo'  => traduz('Relat�rio Pe�as Os Reincidente'),
			'descr'   => traduz('Relat�rio de pe�as em OS&#39;s Reincidentes.'),
			"codigo"  => 'GER-3510'
		),
		array(
			'disabled' => true,
			'fabrica'  => array(2),
			'icone'    => $icone["relatorio"],
			'link'     => 'extrato_posto_devolucao_controle.php',
			'titulo'   => traduz('Pend�ncias do posto - NF'),
			'descr'    => traduz('Controle de Notas Fiscais de Devolu��o e Pe�as'),
			"codigo"   => 'GER-3510'
		),
		array(
			'fabrica' => array(2, 11, 14, 24,  66, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'os_relatorio.php',
			'titulo'  => traduz('Status das Ordens de Servi�o'),
			'descr'   => traduz('Status das ordens de servi�o'),
			"codigo"  => 'GER-3520'
		),
		array(
			'fabrica'    => array_merge(array(1, 35, 30, 50, 74, 134, 141, 140),$relatorio_os),
			'fabrica_no' => array(147),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_os.php',
			'titulo'     => traduz('Relat�rio de OS'),
			'descr'      => traduz('Status das ordens de servi�o'),
			"codigo"     => 'GER-3520'
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_serie.php',
			'titulo'  => traduz('Relat�rio de N� de S�rie'),
			'descr'   => traduz('Relat�rio de ocorr�ncia de produtos por n� de s�rie.'),
			"codigo"  => 'GER-3530'
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_serie_ano.php',
			'titulo'  => traduz('Relat�rio de N� de S�rie Anual'),
			'descr'   => traduz('Relat�rio de ocorr�ncia de produtos por n� de s�rie no per�odo de 12 meses.'),
			"codigo"  => 'GER-3540'
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_producao_serie.php',
			'titulo'  => traduz('Relat�rio de Produ��o'),
			'descr'   => traduz('Relat�rio de produ��o.'),
			"codigo"  => 'GER-3550'
		),
		array(
			'fabrica' => array(5),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_producao_nova_serie.php',
			'titulo'  => traduz('Relat�rio de Produ��o S�rie Nova'),
			'descr'   => traduz('Relat�rio de produ��o.'),
			"codigo"  => 'GER-3560'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas_faturadas.php',
			'titulo'  => traduz('Relat�rio de Pe�as Faturadas'),
			'descr'   => traduz('Relat�rio de pe�as faturadas.'),
			"codigo"  => 'GER-3570'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_produto_serie.php',
			'titulo'  => traduz('Relat�rio OS com N� de S�rie e Posto'),
			'descr'   => traduz('Relat�rio Ordens de Servi�os lan�adas no sistema por produto e por posto, e com n�mero de s�rie.'),
			"codigo"  => 'GER-3580'
		),
		array(
			'fabrica' => array(3, 24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_produto.php',
			'titulo'  => traduz('Relat�rio Troca de Produto'),
			'descr'   => traduz('Relat�rio de produto trocado na OS.'),
			"codigo"  => 'GER-3590'
		),
		array(
			'fabrica' => array(3, 24, 66, 81,101, 114),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_produto_total.php',
			'titulo'  => ($login_fabrica != 101) ? traduz('Relat�rio Troca de Produto Total') : traduz('Relat�rio Troca de Produto'),
			'descr'   => ($login_fabrica != 101)
			? traduz('Relat�rio de produto trocado e arquivo .XLS')
			: traduz('Relat�rio de informa��es dos produtos trocados e as pe�as que deram origem �s trocas'),
			"codigo"  => 'GER-3600'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_linha.php',
			'titulo'  => traduz('Relat�rio de OS digitadas por linha'),
			'descr'   => traduz('Relat�rio de OS digitadas por linha.'),
			"codigo"  => 'GER-3610'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas_mes.php',
			'titulo'  => traduz('Relat�rio de OS e Pecas digitadas'),
			'descr'   => traduz('Relat�rio contendo a qtde de OS e Pe�as digitadas.'),
			"codigo"  => 'GER-3620'
		),
		array(
			'fabrica' => array(127),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_garantia_faturado.php',
			'titulo'  => traduz('Pe�as faturadas e garantia'),
			'descr'   => traduz('Quantidade de pe�as enviadas em garantia, comparadas com as pe�as faturadas.'),
			"codigo"  => 'GER-3630'
		),
		array(
			'fabrica'    => array(3),
			'icone'      => $icone["relatorio"],
			'background' => '#aaaaaa',
			'link'       => '#relatorio_diario.php',
			'titulo'     => traduz('Relat�rio Di�rio'),
			'descr'      => traduz('Resumo mensal do Relat�rio Di�rio enviado por email.'),
			"codigo"     => 'GER-3640'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_qtde_os.php',
			'titulo'  => traduz('Relat�rio Qtde OS e Pe�as Anual'),
			'descr'   => traduz('Relat�rio Anual de quantidade de OSs e Pe�as por Data Digita��o e Finaliza��o.'),
			"codigo"  => 'GER-3650'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_qtde_os_fabrica.php',
			'titulo'  => traduz('Relat�rio de OS COM PE�AS e SEM PE�AS Anual'),
			'descr'   => traduz('Relat�rio Anual de quantidade de OSs com pe�as e sem pe�as por Data Digita��o e Finaliza��o.'),
			"codigo"  => 'GER-3660'
		),
		array(
			'fabrica' => array(8),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produto_por_posto.php',
			'titulo'  => traduz('Produtos por posto'),
			'descr'   => traduz('Relat�rio de produtos lan�ados em OS por posto em determinado per�odo.'),
			"codigo"  => 'GER-3670'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["relatorio"],
			'link'    => 'rel_visao_mix_total.php',
			'titulo'  => traduz('Vis�o geral por produto'),
			'descr'   => traduz('Relat�rio geral por produto.'),
			"codigo"  => 'GER-3680'
		),
		array(
			'icone'  => $icone["relatorio"],
			'link'   => 'custo_por_os.php',
			'titulo' => traduz('Custo por OS'),
			'descr'  => traduz('Calcula o custo m�dio de cada posto para realizar os consertos em garantia.'),
			"codigo" => 'GER-3690'
		),
		array(
			'fabrica_no' => array(7),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_quebra_familia.php',
			'titulo'     => traduz('Relat�rio de Quebra por Fam�lia'),
			'descr'      => traduz('Este relat�rio cont�m a quantidade de quebra durante os �ltimos 12 meses levando em conta o fechamento do extrato de cada m�s.'),
			"codigo"     => 'GER-3700'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_quebra_linha.php',
			'titulo'  => traduz('Relat�rio de Quebra por Linha'),
			'descr'   => traduz('Este relat�rio cont�m a quantidade de quebra durante os ultimos 12 meses levando em conta o fechamento do extrato de cada m�s.'),
			"codigo"  => 'GER-3710'
		),
		array(
			'fabrica' => array(14, 66),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_constatado_os.php',
			'titulo'  => traduz('Relat�rio de Defeitos Constatados por OS'),
			'descr'   => traduz('Relat�rio de Defeitos Constatados por Ordem de Servi�o.'),
			"codigo"  => 'GER-3720'
		),
		array(
			'fabrica' => array(14, 66),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_field_call_rate_os_sem_peca_intelbras.php',
			'titulo'  => traduz('Relat�rio de OS sem pe�a'),
			'descr'   => traduz('Relat�rio de Ordem de Servi�o sem pe�as e seus respectivos defeitos reclamados, defeitos constatados e solu��o.'),
			"codigo"  => 'GER-3730'
		),
		array(
			'fabrica' => array(14, 66),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_reincidencia.php',
			'titulo'  => traduz('Relat�rio de OS Reincidente'),
			'descr'   => traduz('Relat�rio de Ordem de Servi�o reincidentes X posto autorizado.'),
			"codigo"  => 'GER-3740'
		),
		array(
			'fabrica' => array(94),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_troca_new.php',
			'titulo'  => traduz('Relat�rio de OS de Troca'),
			'descr'   => traduz('Relat�rio de Ordem de Servi�o de Troca.'),
			"codigo"  => 'GER-3750'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_auditoria_os.php',
			'titulo'  => traduz('Relat�rio de OS Auditadas'),
			'descr'   => traduz('Relat�rio de Ordens de Servi�o auditadas por: N�mero de s�rie; Com mais de 3 pe�as; Reincid�ncias; E Ordens de Servi�os que n�o passaram por nenhuma auditoria.'),
			"codigo"  => 'GER-3760'
		),
		array(
			'fabrica_no' => array(14),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_field_call_rate_os_sem_peca.php',
			'titulo'     => traduz('Relat�rio de OS sem pe�a'),
			'descr'      => traduz('Relat�rio de Ordem de Servi�o sem pe�as e seus respectivos defeitos reclamados, defeitos constatados e solu��o.'),
			"codigo"     => 'GER-3770'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(14,115,116,117,122,123,124,114,81,127,128,129)),
			'icone'      => $icone["relatorio"],
			'link'       => 'custo_os_nac_imp.php',
			'titulo'     => traduz('Custo Nacionais x Importados'),
			'descr'      => traduz('An�lise dos custos das Ordens de Servi�os de produtos nacionais <i>versus</i> produtos importados.'),
			"codigo"     => 'GER-3780'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_finalizada_sem_extrato.php',
			'titulo'  => traduz('Relat�rio de OS fechada'),
			'descr'   => traduz('Relat�rio de OS\'s finalizadas que ainda n�o entraram em extrato.'),
			"codigo"  => 'GER-3785'
		),
		array(
			'icone'  => $icone["relatorio"],
			'link'   => 'auditoria_os_sem_peca.php',
			'titulo' => traduz('OSs abertas e sem Lan�amento de Pe�as'),
			'descr'  => traduz('Relat�rio que consta os postos e a quantidade de OSs que est�o abertas e sem lan�amento de pe�as'),
			"codigo" => 'GER-3790'
		),
		array(
			'fabrica' => array(19),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_aberta_sac.php',
			'titulo'  => traduz('Relat�rio de OS aberta pelo SAC'),
			'descr'   => traduz('Relat�rio de OSs abertas pelo SAC.'),
			"codigo"  => 'GER-3800'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["upload"],
			'link'    => 'atualizacao_postos_bosch.php',
			'titulo'  => traduz('Arquivo de Atualiza��o de AT'),
			'descr'   => traduz('Gera o arquivo de atualiza��o para o posto selecionado, ou envia os arquivos atualizados por e-mail.'),
			"codigo"  => 'GER-3810'
		),
		array(
			'fabrica' => array(11, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_credenciamento.php',
			'titulo'  => traduz('Credenciamento de Postos por M�s'),
			'descr'   => traduz('Mostra os postos credenciados por m�s.'),
			"codigo"  => 'GER-3820'
		),
		array(
			'fabrica' => array(11, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_atendida_os_aberta.php',
			'titulo'  => traduz('OSs em aberto a mais de 15 dias que j� foram atendidas'),
			'descr'   => traduz('Mostra OSs que tiveram suas pe�as faturadas pelo fabricante a mais de 15 dias e ainda n�o foram finalizadas pelo posto autorizado.'),
			"codigo"  => 'GER-3830'
		),
		array(
			'fabrica' => array(11, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_posto_produto_atendido.php',
			'titulo'  => traduz('Produtos consertados pelo posto'),
			'descr'   => traduz('Relat�rio de produtos consertados por m�s pelo posto autorizado.'),
			"codigo"  => 'GER-3840'
		),
		array(
			'fabrica' => array(11, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_aberta_fechada.php',
			'titulo'  => traduz('Relat�rio de OSs digitadas'),
			'descr'   => traduz('Relat�rio das OSs digitadas por per�odo'),
			"codigo"  => 'GER-3850'
		),
		array(
			'fabrica' => array(11, 172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produto_os_finalizada.php',
			'titulo'  => traduz('Relat�rio OSs finalizadas por produto'),
			'descr'   => traduz('Mostra a quantidade de OSs finalizadas por modelo de produto.'),
			"codigo"  => 'GER-3860'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_auditoria_previa.php',
			'titulo'  => traduz('Relat�rio de OSs auditadas'),
			'descr'   => traduz('Relat�rio de OSs que sofreram auditoria pr�via.'),
			"codigo"  => 'GER-3870'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'produto_custo_tempo.php',
			'titulo'  => traduz('Relat�rio de Custo Tempo Cadastrado'),
			'descr'   => traduz('Relat�rio que consta o custo tempo cadastrado separados por fam�lia.'),
			"codigo"  => 'GER-3880'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'peca_informacoes_pais.php',
			'titulo'  => traduz('Relat�rio de pe�a e pre�o por pa�s'),
			'descr'   => traduz('Relat�rio que consta as pe�as cadastradas por pa�s.'),
			"codigo"  => 'GER-3890'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_cfa.php',
			'titulo'  => traduz('Relat�rio de Garantia dividido por CFAs'),
			'descr'   => traduz('Relat�rio de gastos por Fam�lia e Origem de fabrica��o.'),
			"codigo"  => 'GER-3900'
		),
		array(
			'fabrica_no' => $fabricas_contrato_lite,
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_posto_peca.php',
			'titulo'     => traduz('Relat�rio de Pe�as Por Posto'),
			'descr'      => traduz('Relat�rio de acordo com a data em que a OS foi finalizada.'),
			"codigo"     => 'GER-3910'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_preco_produto_acabado.php',
			'titulo'  => traduz('Relat�rio de Pre�os de Aparelhos'),
			'descr'   => traduz('Relat�rio de pre�os de produto acabado.'),
			"codigo"  => 'GER-3920'
		),
		array(
			'fabrica' => array(152,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_aceite_contrato.php',
			'titulo'  => traduz('Relat�rio Aceite do Contrato'),
			'descr'   => traduz('Relat�rio Aceite do Contrato.'),
			"codigo"  => 'GER-3920'
		),
		array(
			'fabrica' => array(7),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_garantia.php',
			'titulo'  => traduz('Relat�rio de Pe�as em Garantia'),
			'descr'   => traduz('Relat�rio de pe�as com a classifica��o de OS garantia.'),
			"codigo"  => 'GER-3930'
		),
		array(
			'fabrica' => array(7),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_sla.php',
			'titulo'  => traduz('Relat�rio SLA'),
			'descr'   => traduz('Relat�rio SLA'),
			"codigo"  => 'GER-3940'
		),
		array(
			'fabrica' => array(7),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_back_log.php',
			'titulo'  => traduz('Relat�rio Back-Log'),
			'descr'   => traduz('Relat�rio Back-Log'),
			"codigo"  => 'GER-3950'
		),
		array(
			'fabrica' => array(2, 15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_comunicado.php',
			'titulo'  => traduz('Relat�rio de comunicado lido'),
			'descr'   => traduz('Relat�rio dos postos que confirmaram a leitura de comunicado.'),
			"codigo"  => 'GER-3960'
		),
		array(
			'fabrica' => array(2),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pendencia_codigo_componente.php',
			'titulo'  => traduz('Relat�rio de pend�ncias por C�digo'),
			'descr'   => traduz('Relat�rio de pend�ncias por c�digo de componente com filtro de posto opcional.'),
			"codigo"  => 'GER-3970'
		),
		array(
			'fabrica' => array(51),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_pendente_gama.php',
			'titulo'  => traduz('Relat�rio de Pe�as Pendentes'),
			'descr'   => traduz('Relat�rio de pe�as pendentes nas ordens de servi�o.'),
			"codigo"  => 'GER-3980'
		),
		array(
			'fabrica' => array(91),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_geral_os.php',
			'titulo'  => traduz('Relat�rio Geral de OS'),
			'descr'   => traduz('Relat�r,io geral de ordens de servi�o.'),
			"codigo"  => 'GER-3990'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_com_pedido.php',
			'titulo'  => traduz('Relat�rio de OS com Pedido'),
			'descr'   => traduz('Relat�rio de ordens de servi�o com pedidos.'),
			"codigo"  => 'GER-4000'
		),
		array(
			'fabrica_no' => array(51, 30),
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_peca_pendente.php',
			'titulo'     => traduz('Relat�rio de Pe�as Pendentes'),
			'descr'      => traduz('Relat�rio de pe�as pendentes nas ordens de servi�o e pedidos faturados.'),
			"codigo"     => 'GER-4010'
		),
		array(
			'fabrica' => array(101),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_pendente_faturado.php',
			'titulo'  => traduz('Relat�rio de Pe�as Pendentes Pedido Faturado'),
			'descr'   => traduz('Relat�rio de pe�as pendentes em pedidos faturados.'),
			"codigo"  => 'GER-4020'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_demanda_peca_new.php',
			'titulo'  => traduz('Relat�rio de Demanda de Pe�as'),
			'descr'   => traduz('Relat�rio de demanda de pe�as pelas Assist�ncias T�cnicas.'),
			"codigo"  => 'GER-4030'
		),
		array(
			'fabrica' => array(40),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_revenda_produto.php',
			'titulo'  => traduz('Relat�rio de Revenda por Produto'),
			'descr'   => traduz('Relat�rio de Revenda por Porduto - Controle de Fechamento de OS'),
			"codigo"  => 'GER-4040'
		),
		array(
			'fabrica' => array(40),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_cor_unidade.php',
			'titulo'  => traduz('Relat�rio de OS por Unidade'),
			'descr'   => traduz('Relat�rio de OS - Por cor da unidade'),
			"codigo"  => 'GER-4050'
		),
		array(
			'fabrica' => array(40),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_numero_serie.php',
			'titulo'  => traduz('Relat�rio de OS por N�mero de S�rie'),
			'descr'   => traduz('Relat�rio de OS por N�mero de S�rie'),
			"codigo"  => 'GER-4060'
		),
		array(
			'fabrica' => array(40),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_ordem_servico.php',
			'titulo'  => traduz('Relat�rio de Ordens de Servi�o'),
			'descr'   => traduz('Relat�rio que mostra os dados completos das ordens de servi�o'),
			"codigo"  => 'GER-4070'
		),
		array(
			'fabrica' => array(90),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_serie_custo.php',
			'titulo'  => traduz('Relat�rio de OS - Custo - S�rie'),
			'descr'   => traduz('Relat�rio das O.S Finalizadas no M�s.'),
			"codigo"  => 'GER-4080'
		),
		array(
			'fabrica' => array(85),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_gelopar_posto_interno.php',
			'titulo'  => traduz('Relat�rio de MO (Posto Gelopar)'),
			'descr'   => traduz('Relat�rio que mostra o valor de OS do posto 10641- Gelopar'),
			"codigo"  => 'GER-4090'
		),
		array(
			'fabrica' => array(81, 114),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_scrap.php',
			'titulo'  => traduz('Relat�rio de OS Scrap'),
			'descr'   => traduz('Relat�rio de ordens de servi�os scrapeadas.'),
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
			'titulo'  => traduz('Relat�rio Gerencial'),
			'descr'   => traduz('Relat�rio Gerencial.'),
			"codigo"  => 'GER-4120'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_pecas_os.php',
			'titulo'  => traduz('Relat�rio Pe�as trocadas por Postos'),
			'descr'   => traduz('Relat�rio de pe�as trocadas por posto autorizado, linha e fam�lia'),
			"codigo"  => 'GER-4130'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_constatado_familia_anual_new.php',
			'titulo'  => traduz('Relat�rio Anual de OS - Defeito - Fam�lia'),
			'descr'   => traduz('Relat�rio Anual de OS por defeitos constatados e por fam�lia'),
			"codigo"  => 'GER-4140'
		),
		array(
			'fabrica' => array(51),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_pendente_gama_troca.php',
			'titulo'  => traduz('Pe�as Pendentes Cr�ticas'),
			'descr'   => traduz('Relat�rio de pe�as pendentes Cr�ticas para troca.'),
			"codigo"  => 'GER-4150'
		),
		array(
			'fabrica' => array(80), #HD 260902
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_produto_total.php',
			'titulo'  => traduz('Relat�rio de Troca'),
			'descr'   => traduz('Relat�rio de trocas de produtos.'),
			"codigo"  => 'GER-4160'
		),
		array(
			'fabrica' => array(14, 43),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_status_os.php',
			'titulo'  => traduz('Relat�rio de O.S. por Status'),
			'descr'   => traduz('Relat�rio de O.S. listadas de acordo com a sele��o dos status'),
			"codigo"  => 'GER-4170'
		),
		array(
			'fabrica' => array(10),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pa_todos.php',
			'titulo'  => traduz('Relat�rio de Assist�ncias T�cnicas'),
			'descr'   => traduz('Relat�rio de Assist�ncias T�cnicas no Brasil.'),
			"codigo"  => 'GER-4180'
		),
		array(
			'fabrica' => array(52),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_constatado_familia_anual_detalhado.php',
			'titulo'  => traduz('Relat�rio Anual de OS Detalhado - Defeito - Fam�lia'),
			'descr'   => traduz('Relat�rio Anual de OS Detalhado por defeitos constatados e por fam�lias'),
			"codigo"  => 'GER-4190'
		),
		array(
			'fabrica' => array(35),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_cadence.php',
			'titulo'  => traduz('Relat�rio de Ordem de Servi�o'),
			'descr'   => traduz('Relat�rio de ordem de servi�o, mostrando dados do consumidor, revenda, produto, e pe�as.'),
			"codigo"  => 'GER-4200'
		),
		array(
			'fabrica' => array(35,169,170),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_fechamento_os_posto.php',
			'titulo'  => traduz('Relat�rio de controle de fechamento O.S.'),
			'descr'   => traduz('Consta o tempo m�dio que o posto levou para finalizar uma ordem de servi�o.'),
			"codigo"  => 'GER-4210'
		),
		array(
			'fabrica' => array(45),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_produto.php',
			'titulo'  => traduz('Relat�rio Troca de Produto'),
			'descr'   => traduz('Relat�rio de produto trocado na OS.'),
			"codigo"  => 'GER-4220'
		),
		array(
			'fabrica' => array(45),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produto_qtde.php',
			'titulo'  => traduz('Relat�rio de Ger�ncia'),
			'descr'   => traduz('Relat�rio que mostra total do produto(trocado, utilizaram pe�as) do m�s.'),
			"codigo"  => 'GER-4230'
		),
		array(
			'fabrica' => array(45),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_troca_produto_causa.php',
			'titulo'  => traduz('Relat�rio Troca Produto Causa'),
			'descr'   => traduz('Relat�rio de produto trocado na OS(filtrando por causa).'),
			"codigo"  => 'GER-4240'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_sem_preco_al.php',
			'titulo'  => traduz('Relat�rio de Pe�as sem Pre�o dos Paises da AL'),
			'descr'   => traduz('Relat�rio de Pe�as dos paises da Am�rica Latina que est�o sem pre�o cadastrado.'),
			"codigo"  => 'GER-4250'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_qtde_valor.php',
			'titulo'  => traduz('Relat�rio de quantidade / valor  de OSs'),
			'descr'   => traduz('Relat�rio de quantidade e valor de OSs por tipo de atendimento.'),
			"codigo"  => 'GER-4260'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_cortesia_comercial.php',
			'titulo'  => traduz('Relat�rio de OS Cortesia Comercial'),
			'descr'   => traduz('Relat�rio de OS de Cortesia Comercial.'),
			"codigo"  => 'GER-4270'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas.php',
			'titulo'  => traduz('Relat�rio de Pedidos de Pe�as'),
			'descr'   => traduz('Relat�rio de pe�as pedidas pelo posto autorizado em garantia ou compra.'),
			"codigo"  => 'GER-4280'
		),
		array(
			'fabrica' => array(24),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_revenda_os.php',
			'titulo'  => traduz('Consulta Revenda x Produto'),
			'descr'   => traduz('Relat�rio de OS por revenda e quantidade em um per�odo'),
			"codigo"  => 'GER-4290'
		),
		array(
			'fabrica' => array(24),# HD 24493 - Inclu�do para a Suggar Relat�rio de pe�as exportadas
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_exportada.php',
			'titulo'  => traduz('Relat�rio de Pe�as Exportadas'),
			'descr'   => traduz('Relat�rio de pe�as exportadas pelo posto em um per�odo'),
			"codigo"  => 'GER-4300'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_faturamento_pecas.php',
			'titulo'  => traduz('Relat�rio de Pe�as Faturadas'),
			'descr'   => traduz('Relat�rio de pe�as faturadas para os postos autorizados pela data de emiss�o da nota fiscal.'),
			"codigo"  => 'GER-4310'
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_faturamento_os.php',
			'titulo'  => traduz('Relat�rio de OS Faturadas'),
			'descr'   => traduz('Relat�rio de OS faturadas para os postos autorizados pela data de abertura da OS.'),
			"codigo"  => 'GER-4320'
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tempo_medio_abertura.php',
			'titulo'  => traduz('Relat�rio de tempo m�dio por os'),
			'descr'   => traduz('Relat�rio de tempo de reparo por os  '),
			"codigo"  => 'GER-4330'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_faturamento_garantia_pecas.php',
			'titulo'  => traduz('Relat�rio de Pe�as Atendidas em Garantia'),
			'descr'   => traduz('Relat�rio de pe�as atendidas em garantia para os postos autorizados pela data de emiss�o da nota fiscal.'),
			"codigo"  => 'GER-4340'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_devolucao_pecas_pendentes.php',
			'titulo'  => traduz('Relat�rio de Devolu��o de Pe�as Pendentes'),
			'descr'   => traduz('Relat�rio de pe�as atendidas em garantia para os postos autorizados com devolu��o pendente'),
			"codigo"  => 'GER-4350'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas_terceiros.php',
			'titulo'  => traduz('Relat�rio de Pe�as em Poder de Terceiros'),
			'descr'   => traduz('Relat�rio de pe�as em poder de terceiros com base no LGR.'),
			"codigo"  => 'GER-4360'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_extrato.php',
			'titulo'  => traduz('Relat�rio Anal�tico de Defeito de OS'),
			'descr'   => traduz('Relat�rio que lista OS com detalhes e defeitos constatados nos atendimentos'),
			"codigo"  => 'GER-4370'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pesquisa_satisfacao_new.php',
			'titulo'  => traduz('Relat�rio Pesquisa de Satisfa��o'),
			'descr'   => traduz('Relat�rio Geral da Pesquisa de Satisfa��o'),
			"codigo"  => 'GER-4380'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pesquisa_satisfacao_os.php',
			'titulo'  => traduz('Relat�rio Pesquisa de Satisfa��o - OS'),
			'descr'   => traduz('Relat�rio por OS da Pesquisa de Satisfa��o'),
			"codigo"  => 'GER-4390'
		),
		array(
			'icone'  => $icone["relatorio"],
			'link'   => 'posto_consulta_gerencia.php',
			'titulo' => traduz('Rela��o de Postos Credenciados'),
			'descr'  => traduz('Rela��o de Postos Credenciados'),
			"codigo" => 'GER-4400'
		),
		array(
			'fabrica' => array(175),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_peca_sem_preco.php',
			'titulo'  => traduz('Relat�rio de OS e Pe�as sem pre�o'),
			'descr'   => traduz('Relat�rio de Pe�as sem pre�o lan�adas em ordens de servi�o.'),
			"codigo"  => 'GER-5040'
		),
		array(
			'fabrica' => array(175),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_rastreabilidade_de_pecas.php',
			'titulo'  => traduz('Relat�rio de Rastreabilidade de Pe�as'),
			'descr'   => traduz('Relat�rio de Rastreabilidade de Pe�as.'),
			"codigo"  => 'GER-5050'
		),
		array(
			'fabrica' => array_merge(array(139,141,148,167,169,170,174,178,183,184,191,193,195,198,200,203),$arr_fabrica_distrib),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tma.php',
			'titulo'  => traduz('Relat�rio TMA'),
			'descr'   => traduz('Aging de Ordem de Servi�o.'),
			"codigo"  => 'GER-4410'
		),
		array(
			'fabrica' => array(101),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_cancelada_pedido.php',
			'titulo'  => traduz('Relat�rio Pe�as Canceladas'),
			'descr'   => traduz('Relat�rio das pe�as canceladas dos pedidos que foram faturados'),
			"codigo"  => 'GER-4420'
		),
		array(
			'fabrica' => array(85),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_posto_km.php',
			'titulo'  => traduz('Rela��o de Postos OS x KM'),
			'descr'   => traduz('Rela��o de Postos OS x KM'),
			"codigo"  => 'GER-4430'
		),
		array(
			'fabrica' => array(45),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tempo_defeito_produto.php',
			'titulo'  => traduz('Relat�rio de tempo de defeito produtos'),
			'descr'   => traduz('Relat�rio de tempo de defeito produtos'),
			"codigo"  => 'GER-4440'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_rastreabilidade.php',
			'titulo'  => traduz('Relat�rios Rastreabilidade '),
			'descr'   => traduz('Relat�rios Rastreabilidade de Pe�as'),
			"codigo"  => 'GER-4450'
		),
		array(
			'disabled' => true,
			'fabrica'  => array(50),
			'icone'    => $icone["relatorio"],
			'link'     => 'relatorio_extratificacao_v201408.php',
			'titulo'   => traduz('Relat�rio de Estratifica��o - 2014'),
			'descr'    => traduz('Relat�rio de Estratifica��o'),
			"codigo"   => 'GER-4460'
		),
		array(
			'disabled' => true,
			'fabrica'  => array(24),
			'icone'    => $icone["relatorio"],
			'link'     => 'relatorio_extratificacao_vdnf.php',
			'titulo'   => traduz('Relat�rio de Estratifica��o'),
			'descr'    => traduz('Relat�rio de Estratifica��o'),
			"codigo"   => 'GER-4470'
		),
		array(
			'fabrica' => array(24,50,120,201,175),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_extratificacao_v201505.php',
			'titulo'  => traduz('Relat�rio de Estratifica��o'),
			'descr'   => traduz('Relat�rio de Estratifica��o'),
			"codigo"  => 'GER-4480'
		),
		array(
			'fabrica'  => array(24),
			'icone'    => $icone["relatorio"],
			'link'     => 'relatorio_extratificacao_devolucao.php',
			'titulo'   => traduz('Relat�rio de Estratifica��o Devolu��o'),
			'descr'    => traduz('Relat�rio de Estratifica��o Devolu��o'),
			"codigo"   => 'GER-4490'
		),
		array(
			'fabrica' => array(15), // HD 55355
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_nt_serie.php',
			'titulo'  => traduz('Relat�rio de S�rie da Familia NT'),
			'descr'   => traduz('Relat�rio que mostra o n�mero de s�rie das OSs com produto da familia Lavadora NT e as OSs sem lan�amento de pe�a.'),
			"codigo"  => 'GER-4500'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_defeito_constatado_peca.php',
			'titulo'  => traduz('Relat�rio de Defeito Constatado Pe�a'),
			'descr'   => traduz('Relat�rio que consulta OS,Defeito Constatado e Pe�a.'),
			"codigo"  => 'GER-4510'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_nt_serie_abertura.php',
			'titulo'  => traduz('Relat�rio de S�rie da Familia NT Abertura'),
			'descr'   => traduz('Relat�rio que mostra o n�mero de s�rie das OSs com produto da familia Lavadora NT e as OSs sem lan�amento de pe�a pela data de abertura.'),
			"codigo"  => 'GER-4520'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_km.php',
			'titulo'  => traduz('Relat�rio de OS com Deslocamento'),
			'descr'   => traduz('Relat�rio que mostra os dados das ordens de servi�os com deslocamento.'),
			"codigo"  => 'GER-4530'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_mensal.php',
			'titulo'  => traduz('Relat�rio de Ordem de Servi�o'),
			'descr'   => traduz('Relat�rio que mostra os dados das ordens de servi�os com base na na gera��o do extrato.'),
			"codigo"  => 'GER-4540'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_reincidencia_latinatec.php',
			'titulo'  => traduz('Relat�rio de OS reincid�ntes'),
			'descr'   => traduz('Relat�rio que mostra as reincid�ncias de Ordens de Servi�o.'),
			"codigo"  => 'GER-4550'
		),
		array(
			'fabrica' => array(15),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_estoque_posto.php',
			'titulo'  => traduz('Relat�rio de Estoque dos postos'),
			'descr'   => traduz('Relat�rio que o estoque dos postos'),
			"codigo"  => 'GER-4560'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produto_locacao.php',
			'titulo'  => traduz('Relat�rio de Produtos de Loca��o'),
			'descr'   => traduz('Relat�rio que mostra os produtos de loca��o.'),
			"codigo"  => 'GER-4570'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pecas_lista_basicas.php',
			'titulo'  => traduz('Relat�rio de Pe�as que Constam em Listas B�sicas'),
			'descr'   => traduz('Relat�rio que mostra todas as pe�as que constam em listas b�sicas de Produtos'),
			"codigo"  => 'GER-4580'
		),
		array(
			'fabrica' => array(91), // HD 367935
			'icone'   => $icone["relatorio"],
			'link'    => 'rel_peca_requisitada.php',
			'titulo'  => traduz('Relat�rio de Requisi��o de Pe�as'),
			'descr'   => traduz('Relat�rio que mostra as as pe�as requisitadas'),
			"codigo"  => 'GER-4590'
		),
		array(
			'fabrica' => array(43), // HD 255546
			'icone'   => $icone["relatorio"],
			'link'    => 'ranking_autorizadas.php',
			'titulo'  => traduz('Ranking Postos'),
			'descr'   => traduz('Relat�rio que mostra dados mensais dos Postos gerando um Ranking'),
			"codigo"  => 'GER-4600'
		),
		array(
			'fabrica' => array(91), // HD 2432459
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_cidade_atendida_posto.php',
			'titulo'  => traduz('Relat�rio cidade atendidas pelo Posto'),
			'descr'   => traduz('Relat�rio que mostra cidade atendidas pelo Posto'),
			"codigo"  => 'GER-4610'
		),
		array(
			'fabrica' => array(91), /* HD-3594930*/
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pedido_faturado.php',
			'titulo'  => traduz('Relat�rio de Pedidos Faturados'),
			'descr'   => traduz('Hist�rico geral dos pedidos de VENDA dos postos autorizados. Neste relat�rio consta a rela��o de todos os postos autorizados ativos e seus respectivos pedidos de compra.'),
			"codigo"  => 'GER-4620'
		),
		array(
			'fabrica' => array(74), // HD 673761
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_mensal_atlas.php',
			'titulo'  => traduz('Relat�rio de Informa��es'),
			'descr'   => traduz('Relat�rio que mostra informa��es sobre OS, pe�as, defeitos etc.'),
			"codigo"  => 'GER-4630'
		),
		array(
			'fabrica' => array(74),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_protocolos_atendimento.php',
			'titulo'  => traduz('Relat�rio dos Protocolos de Atendimento'),
			'descr'   => traduz('Relat�rio que mostra informa��es dos Atendimentos'),
			"codigo"  => 'GER-4640'
		),
		array(
			'fabrica' => array(74),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_pedido_pecas.php',
			'titulo'  => traduz('Relat�rio de Pedido de Pe�as'),
			'descr'   => traduz('Relat�rio que mostra os pedidos e suas pe�as'),
			"codigo"  => 'GER-4650'
		),
		array(
			'fabrica' => array(3),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produtos_cadastrados.php',
			'titulo'  => traduz('Relat�rio de Produtos Cadastrados'),
			'descr'   => traduz('Relat�rio que mostra informa��es sobre sobre os produtos cadastrados'),
			"codigo"  => 'GER-4660'
		),
		array(
			'fabrica' => array_merge(array(0,101),$arr_fabrica_distrib),
			'icone'   => $icone["relatorio"],
			'link'    => 'ressarcimento_consulta.php',
			'titulo'  => traduz('Relat�rio de Ressarcimentos'),
			'descr'   => traduz('Relat�rio que mostra informa��es sobre ressarcimentos cadastrados'),
			"codigo"  => 'GER-4670'
		),
		array(
			'fabrica' => array(45),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_gerencial_os.php',
			'titulo'  => traduz('Relat�rio gerencial de OS'),
			'descr'   => traduz('Relat�rio gerencial de OS'),
			"codigo"  => 'GER-4680'
		),
		array(
			'fabrica' => array(81, 114, 122, 123,124,127,128,129, 153),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_atendimento.php',
			'titulo'  => traduz('Relat�rio OS x Atendimento'),
			'descr'   => traduz('Relat�rio de OS por atendimento'),
			"codigo"  => 'GER-4690'
		),
		array(
			'fabrica' => array(153),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_estoque_posto.php',
			'titulo'  => traduz('Relat�rio de Pe�as em Estoque'),
			'descr'   => traduz('Relat�rio de Pe�as em Estoque por OS'),
			"codigo"  => 'GER-4700'
		),
		array(
			'fabrica' => $arr_fabrica_distrib,
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_gf.php',
			'titulo'  => traduz('Relat�rio de Pe�as por Garantia/Faturado'),
			'descr'   => traduz('Relat�rio que mostra informa��es de pedido e OS'),
			"codigo"  => 'GER-4710'
		),
		array(
			'fabrica' => array(81),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_pedido.php',
			'titulo'  => traduz('Relat�rio venda e garantia'),
			'descr'   => traduz('Relat�rio que mostra as pe�as fornecidas em garantia e venda'),
			"codigo"  => 'GER-4720'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"], 
			'link'    => 'relatorio_demanda_peca_posto.php',
			'titulo'  => traduz('Relat�rio de Demanda de Pe�as por Postos '),
			'descr'   => traduz('Relat�rio de demanda de pe�as pelas Assist�ncias T�cnicas.'),
			"codigo"  => 'GER-4730'
		),
		array(
			'fabrica' => array(30),
			'icone'   => $icone["relatorio"],
			'link'    => 'ocorrencia_fornecedor.php',
			'titulo'  => traduz('Relat�rio de Ocorr�ncia x Fornecedor'),
			'descr'   => traduz('Relat�rio de ocorr�ncia x fornecedor'),
			"codigo"  => 'GER-4731'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_mao_obra_pais.php',
			'titulo'  => traduz('Relat�rio de m�o de obra por Pa�s'),
			'descr'   => traduz('Relat�rio de m�o de obra por defeito constatado por produto em rela��o ao Pa�s.'),
			"codigo"  => 'GER-4740'
		),
		array(
			'fabrica' => array(20),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_funcionario_posto.php',
			'titulo'  => traduz('Relat�rio funcion�rios Posto'),
			'descr'   => traduz('Relat�rio dos funcion�rios dos Postos e suas Fun��es.'),
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
			'titulo'  => traduz('Pe�as dispon�veis no Shop Pe�as'),
			'descr'   => traduz('Rela��o de pe�as dispon�veis no Shop Pe�as.'),
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
			'titulo'  => traduz('Relat�rio de OS Fechamento Autom�tico'),
			'descr'   => traduz('Quantidade de OS com Fechamento Autom�tico.'),
			"codigo"  => 'GER-4760'
		),
		array(
			'fabrica' => array(141,144,165),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_produtividade_reparo.php',
			'titulo'  => traduz('Relat�rio de Produtividade de Reparo'),
			'descr'   => traduz('Relat�rio que mede a produtividade de repara das Ordens de Servi�o de acordo com as metas estabelecidas.'),
			"codigo"  => 'GER-4770'
		),
		array(
			'fabrica' => array(141,144,165),
			'icone'   => $icone["relatorio"],
			'link'    => ($login_fabrica != 165) ? 'painel_os_aberta_familia.php' : 'painel_os_aberta_familia_online.php',
			'titulo'  => traduz('Painel OS por Fam�lia'),
			'descr'   => traduz('Produtos aguardando reparo na assist�ncia t�cnica'),
			"codigo"  => 'GER-4780'
		),
		array(
			'fabrica' => array(141,144),
			'icone'   => $icone["relatorio"],
			'link'    => 'painel_os_consertada_familia.php',
			'titulo'  => traduz('Painel OS consertadas por Fam�lia Posto Interno'),
			'descr'   => traduz('Produtos aguardando remanufatura/expedi��o no Posto Interno'),
			"codigo"  => 'GER-4790'
		),
		array(
			'fabrica' => array_merge(array(123,141,151,153,160,174,193), $fabricas_replica_einhell),
			'icone'   => $icone["relatorio"],
			'link'    => 'dashboard_fabrica.php',
			'titulo'  => traduz('Dashboard'),
			'descr'   => traduz('Dashboard de OS abertas por per�odo de 3 ou 6 meses'),
			"codigo"  => 'GER-4800'
		),
		array(
			'fabrica' => array_merge($arr_fabrica_distrib),
			'icone'   => $icone["relatorio"],
			'link'    => 'posto_estoque_distrib.php',
			'titulo'  => traduz('Estoque Distrib'),
			'descr'   => traduz('Relat�rio de estoque no Distrib'),
			"codigo"  => 'GER-4810'
		),
		array(
			'fabrica' => array(153),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_bonus.php',
			'titulo'  => traduz('B�nus'),
			'descr'   => traduz('Relat�rio B�nus'),
			"codigo"  => 'GER-4820'
		),
		array(
			'fabrica' => array(104),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_reprovada.php',
			'titulo'  => traduz('Relat�rio de OSs Reprovadas'),
			'descr'   => traduz('Relat�rio que lista doas as OSs Reprovadas no per�odo de at� 12 meses.'),
			"codigo"  => 'GER-4830'
		),
		array(
			'fabrica' => array(151),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_conferencias_realizadas.php',
			'titulo'  => traduz('Relat�rio de Confer�ncias Realizadas'),
			'descr'   => traduz('Relat�rio de Confer�ncias Realizadas por per�odo de at� 1 ano'),
			"codigo"  => 'GER-4840'
		),
		array(
			'fabrica' => array(153),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_defeito.php',
			'titulo'  => traduz('Relat�rio OS - Defeitos'),
			'descr'   => traduz('Relat�rio de OS x Defeito Constatado e Defeito Reclamado'),
			"codigo"  => 'GER-4850'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_garantia_por_cliente_admin.php',
			'titulo'  => traduz('Relat�rio de Ordens de Servi�o de Garantia'),
			'descr'   => traduz('Relat�rio que mostra as OSs totais por classifica��o, OSs finalizadas por m�s e OSs pendentes separadas por status, com op��o de gerar por clietne admin ou posto autorizado'),
			"codigo"  => 'GER-4860'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_permanencia_tempo_conserto.php',
			'titulo'  => traduz('Relat�rio Perman�ncia em conserto'),
			'descr'   => traduz('Relat�rio que mostra o tempo (dias) de perman�ncia do produto (Ordens de Servi�o de garantia) na assist�ncia t�cnica'),
			"codigo"  => 'GER-4870'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_fora_garantia_centro_distribuidor.php',
			'titulo'  => traduz('Relat�rio de Ordens de Servi�o dentro e fora de garantia origem KOF'),
			'descr'   => traduz('Relat�rio que mostra os indicadores das Ordens de Servi�o fora de garantia'),
			"codigo"  => 'GER-4880'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_oss_corretivas.php',
			'titulo'  => traduz('Relat�rio de Ordens de Servi�o Corretiva Garantia'),
			'descr'   => traduz('Relat�rio que mostra os indicadores das Ordens de Servi�o em garantia'),
			"codigo"  => 'GER-4890'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_oss_sanitizacao.php',
			'titulo'  => traduz('Relat�rio de Ordens de Servi�o de sanitiza��o'),
			'descr'   => traduz('Relat�rio que mostra os indicadores das Ordens de Servi�o de sanitiza��o'),
			"codigo"  => 'GER-4900'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_indicadores_oss_abertas.php',
			'titulo'  => traduz('Indicadores de Ordens de Servi�o Abertas'),
			'descr'   => traduz('Relat�rio que mostra a quantidade de OSs em aberto, tanto de garantia quanto fora de garantia'),
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
			'descr'   => (in_array($login_fabrica, array(169,170))) ? traduz('Dashboard de OS abertas por per�odo de 6 meses') : traduz('Dashboard de OS abertas por per�odo de 3 meses'),
			"codigo"  => 'GER-4930'
		),
		array(
			'fabrica' => array(42),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_cortesia_custos.php',
			'titulo'  => traduz('Relat�rio OS - Custos de Cortesia'),
			'descr'   => traduz('Relat�rio de custos com cortesia de OS'),
			"codigo"  => 'GER-4940'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'log_auto_agendamento.php',
			'titulo'  => traduz('Log do Auto Agendamento'),
			'descr'   => traduz('Relat�rio que mostra as execu��es do auto agendamento e o arquivo de log'),
			"codigo"  => 'GER-4950'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'indicadores_tempo_resposta.php',
			'titulo'  => traduz('Indicadores de Tempo de Resposta'),
			'descr'   => traduz('Indicadores que mostra o tempo de resposta dos atendimento, tempo levado entre a execu��o e finaliza��o do atendimento'),
			"codigo"  => 'GER-4960'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'indicadores_eficiencia_produtividade.php',
			'titulo'  => traduz('Indicadores de Efici�ncia/Produtividade'),
			'descr'   => traduz('Indicadores que mostra o tempo de resposta dos atendimento, a efici�ncia dos atendimentos dentro do sla e a nota de produtividade'),
			"codigo"  => 'GER-4970'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'indicadores_eficiencia_volume.php',
			'titulo'  => traduz('Indicadores SLA/Reincid�ncia'),
			'descr'   => traduz('Indicadores que mostra o tempo de resposta dos atendimento, a efici�ncia dos atendimentos dentro do sla'),
			"codigo"  => 'GER-4980'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'estatistica_os_fechamento.php',
			'titulo'  => traduz('Relat�rio de Fechamento'),
			'descr'   => traduz('Relat�rio que demonstra por onde foi feito a a��o do fechamento da OS'),
			"codigo"  => 'GER-4990'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_faturamento_kof.php',
			'titulo'  => traduz('Relat�rio de Pe�as Utilizadas em OS'),
			'descr'   => traduz('Relat�rio que demonstra  pe�as utilizadas em OS'),
			"codigo"  => 'GER-5000'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_peca_os.php',
			'titulo'  => traduz('Relat�rio de Resumo de Pe�as Utilizadas em OS'),
			'descr'   => traduz('Relat�rio de todas as informa��es referentes �s pe�as consumidas em OS j� Finalizadas.'),
			"codigo"  => 'GER-5010'
		),
		array(
			'fabrica' => $fabrica_relatorio_ratreio,
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_rastreamento.php',
			'titulo'  => traduz('Relat�rio de Rastreamento'),
			'descr'   => traduz('Relat�rio do O.S com data de Recebimento.'),
			"codigo"  => 'GER-5020'
		),
		array(
			'fabrica' => array(169,170),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_dashboard.php',
			'titulo'  => traduz('Relat�rio Dashboard Vis�o dos Postos'),
			'descr'   => traduz('Relat�rio Dashboard Vis�o dos Postos.'),
			"codigo"  => 'GER-5030'
		),
		array(
			'icone'  => $icone["relatorio"],
			'fabrica' => array(35),
			'link'   => 'relatorio_acompanhamento_posto_nps.php',
			'titulo' => traduz('Relat�rio Ranqueamento Rede Autorizada'),
			'descr'  => traduz('Relat�rio detalhado para acompanhamento de NPS dos postos autorizados.'),
			"codigo" => 'GER-5040'
		),
		array(	
			'fabrica'   => array(35),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_visao_geral_de_os.php',
			'titulo'    => traduz('Gerenciador de Ordem de Servi�o'),
			'descr'     => traduz('Relat�rio Vis�o Geral de Ordem de Servi�o'),
			'codigo'    => 'GER-5050'
		),
		array(
            'fabrica'    => ($telecontrol_distrib == 't') ? [$login_fabrica] : "",
            'fabrica_no' => ($telecontrol_distrib != 't') ? [$login_fabrica] : "",
            'icone'      => $icone["relatorio"],
            'link'       => 'relatorio_produtividade_interacoes.php',
            'titulo'     => traduz('Relat�rio Intera��es'),
            'descr'      => traduz('Relat�rio detalhado da produtividade dos atendentes'),
            "codigo"     => 'GER-5050'
        ),
		array(
            'fabrica'   => array(115),
            'icone'     => $icone["relatorio"],
            'link'      =>'relatorio_categoria_posto.php',
            'titulo'    => traduz('Relat�rio de Categoria dos Postos'),
            'descr'     => traduz('Mostra o desempenho dos postos em rela��o � categoria (Classifica��o)'),
            'codigo'    =>'GER-5060'
        ),
        array(
            'fabrica'   => [169,170,183],
            'icone'     => $icone["relatorio"],
            'link'      =>'fluxo_entrada_saida_os.php',
            'titulo'    => traduz('Relat�rio de OS abertas x OS encerradas'),
            'descr'     => traduz('Mostra o desempenho com rela��o � abertura e fechamento das ordens de servi�o por semana'),
            'codigo'    =>'GER-5070'
        ),
        array(
            'fabrica'       => ($telecontrol_distrib == 't') ? [$login_fabrica] : "",
            'fabrica_no'    => ($telecontrol_distrib != 't') ? [$login_fabrica] : "",
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_detalhado_os_peca.php',
            'titulo'        => traduz('Relat�rio Pedidos x Pe�as'),
            'descr'         => traduz('Relat�rio detalhado por Ordem de servi�o, pedido e pe�a'),
            "codigo"    => 'GER-5080'
        ),
		array(
			'fabrica_no'   => (!$pesquisaSatisfacao) ? [$login_fabrica] : "",
    		'icone'     => $icone["relatorio"],
    		'link'      => 'pesquisa_satisfacao_relatorio.php',
    		'titulo'    => traduz('Relat�rio da Pesquisa de Satisfa��o'),
    		'descr'     => traduz('Relat�rio das pesquisas de satisfa��es disparadas.'),
    		"codigo"    => "GER-5090"
		   ),
		array(
			'fabrica'   => [138],
			'icone'     => $icone["relatorio"],
			'link'      => 'relatorio_indicadores.php',
			'titulo'    => 'Relat�rio de Indicadores',
			'descr'     => 'Relat�rio de indicadores de OSs x Chamados (pesquisas de satisfa��o) x Extratos.',
			"codigo"    => "GER-6000"
       	),
        array(
            'fabrica'       => ($telecontrol_distrib == 't') ? [$login_fabrica] : "",
            'fabrica_no'    => ($telecontrol_distrib != 't') ? [$login_fabrica] : "",
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_ofensores.php',
            'titulo'        => traduz('CHECK STATUS'),
            'descr'         => traduz('Motivos pelo qual as Ordens de Servi�o e pedidos faturados permanecem em aberto no sistema'),
            "codigo"    => 'GER-5100'
	    ),
	    array(
            'fabrica'       => ($telecontrol_distrib == 't' || in_array($login_fabrica, [174])) ? [$login_fabrica] : [0],
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_produtividade_atendentes_callcenter.php',
            'titulo'        => traduz('Relat�rio de Produtividade Callcenter'),
            'descr'         => traduz('Relat�rio de produtividade dos atendentes de callcenter'),
            "codigo"    => 'GER-5110'
	    ),
	    array(
            'fabrica'       => ($telecontrol_distrib == 't') ? [$login_fabrica] : [0],
            'icone'         => $icone["relatorio"],
            'link'          => 'tempo_permanencia.php',
            'titulo'        => traduz('Tempo de Perman�ncia'),
            'descr'         => traduz('M�dia do tempo de fechamento das OSs por M�s. Exibe: vis�o geral/por UF/postos ofensores'),
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
            'titulo'        => traduz('Relat�rio Ordem de Servi�os abertas e fechadas durante o m�s'),
            'descr'         => traduz('Exibe relat�rio de Ordem de Servi�os abertas e fechadas durante o m�s'),
            "codigo"    	=> 'GER-5130'
	    ),
	    array(
            'fabrica'       => [158],
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_valores_sta.php',
            'titulo'        => traduz('Relat�rio de valores acordados STAs'),
            'descr'         => traduz('Valores M.O acordados com os postos'),
            "codigo"    	=> 'GER-5140'
	    ),
	    array(
            'fabrica'       => (($telecontrol_distrib || $interno_telecontrol) && $privilegios == "*") ? [$login_fabrica] : [0,189],
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_evolucao_operacional.php',
            'titulo'        => traduz('Relat�rio de Evolu��o Operacional'),
            'descr'         => traduz('Crescimento da demanda operacional das f�bricas'),
            "codigo"    	=> 'GER-5150'
	    ),
	    array(
	    'fabrica'   => [19],
            'icone'     => $icone["relatorio"],
            'link'      =>'relatorio_garantias_adicionais.php',
            'titulo'    =>'Relat�rio de OSs com garantias adicionais',
            'descr'     =>"Dados de OSs com garantias adicionais",
            'codigo'    =>'GER-5160'
	    ),
	    array(
	    'icone'  => $icone["relatorio"],
	    'fabrica' => [91],
	    'link'   => 'ranking_postos.php',
	    'titulo' => 'Relat�rio Ranqueamento Rede Autorizada',
	    'descr'  => 'Relat�rio detalhado para acompanhamento da pontua��o dos postos autorizados.',
	    "codigo" => 'GER-5170'
	    ),
	    array(
            'fabrica'       => (($telecontrol_distrib || $interno_telecontrol) && $privilegios == "*") ? [$login_fabrica] : [0,189],
            'icone'         => $icone["relatorio"],
            'link'          => 'relatorio_telefonia_detalhado_atendentes.php',
            'titulo'        => 'Relat�rio Detalhado Telefonia Atendentes',
            'descr'         => 'Crescimento da demanda operacional das f�bricas',
            "codigo"    	=> 'GER-5180'
	    ),
	    array(
            'fabrica'   => [186],
            'icone'     => $icone["relatorio"],
            'link'      =>'relatorio_os_posto_autorizado_x_interno.php',
            'titulo'    =>'Relat�rio de Indica��es de Posto',
            'descr'     =>"Relat�rio de atendimentos, indica��o de posto direcionado",
            'codigo'    =>'GER-5190'
        ),
	    array(

			'fabrica' => [183],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_primeiro_acesso_posto.php',
			'titulo'  => 'Relat�rio Posto Primeiro Acesso',
			'descr'   => 'Relat�rio de postos que j� fizeram o primeiro acesso no Telecontrol',
			"codigo"  => 'GER-5220'
	    ),
	    array(
			'fabrica' => [183],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_cliente_representante.php',
			'titulo'  => 'Relat�rio de clientes Representantes',
			'descr'   => 'Relat�rio de clientes vinculados a Representantes',
			"codigo"  => 'GER-5230'
	    ),
	    array(
        	'fabrica' => [42],
            'icone'   => $icone['cadastro'],
            'link'    => 'questionario_avaliacao.php',
            'titulo'  => 'Question�rio de Avalia��o do Posto Autorizado',
            'descr'   => 'Cadastro do Question�rio de Avalia��o do t�cnico que Ser� exibido tanto na �rea do posto autorizado no Telecontrol Quanto no aplicativo',
            'codigo'  => 'GER-5240'
        ),
	    array(
	    	'fabrica' => [42],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_respostas_pesquisa.php',
			'titulo'  => 'Relat�rio de Respostas da Pesquisa',
			'descr'   => 'Relat�rio detalhado com as respostas da pesquisa',
			"codigo"  => 'GER-5250'
	    ),
	    array(
			'fabrica' => [183],
			'icone'   => $icone["relatorio"],
			'link'    => 'consulta_nf_venda.php',
			'titulo'  => 'Relat�rio Notas Fiscais de Venda',
			'descr'   => 'Relat�rio Notas Fiscais de Venda',
			"codigo"  => 'GER-5260'
	    ),
	    array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_respostas_atendimento_posto.php',
			'titulo'  => 'Relat�rio Postos Atendendo',
			'descr'   => 'Relat�rio que mostra os Postos que est�o realizando Atendimento ao P�blico',
			"codigo"  => 'GER-5270'
	    ),
	    array(
			'fabrica' => [183],
			'icone'   => $icone["relatorio"],
			'link'    => 'dashboard_evolucao_diaria.php',
			'titulo'  => 'Dashboard Evolu��o Di�ria',
			'descr'   => 'Dashboard Com o Montante das OSs Di�rias X Mensais ',
			"codigo"  => 'GER-5280'
	    ),
	   'link' => 'linha_de_separa��o',
	),

/**********************************

PULEI O 6000, 7000 PARA A SE��O GERAL

***********************************/


	// Se��o OS - Apenas
	'secaoOS' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('ORDENS DE SERVI�O'),
			'fabrica' => array(30,108,111,153)
		),
		array(
			'icone'      => $icone["cadastro"],
			'fabrica_no' => array(30),
			'link'       => 'os_cadastro.php',
			'titulo'     => traduz('Cadastra OS'),
			'descr'      => traduz('Cadastra uma nova ordem de servi�o'),
			"codigo"     => 'GER-8000'
		),
		array(
			'icone'      => $icone["consulta"],
			'fabrica_no' => array(30),
			'link'       => 'os_consulta_lite.php',
			'titulo'     => traduz('Consulta OS'),
			'descr'      => traduz('Consulta Ordens de Servi�o'),
			"codigo"     => 'GER-8010'
		),
		array(
			'icone'      => $icone["consulta"],
			'fabrica_no' => array(30),
			'link'       => 'os_parametros_excluida.php',
			'titulo'     => traduz('Consulta OS Exclu�da'),
			'descr'      => traduz('Consulta Ordens de Servi�o exclu�das do sistema'),
			"codigo"     => 'GER-8020'
		),
		array(
			'icone'      => $icone["relatorio"],
			'fabrica_no' => array(30),
			'link'       => 'os_intervencao.php',
			'titulo'     => traduz('OSs com Interven��es T�cnicas'),
			'descr'      => traduz('OSs com interven��o t�cnica da f�brica. Autoriza ou cancela o pedido de pe�as do posto ou efetua o reparo na f�brica.'),
			"codigo"     => 'GER-8030'
		),
		array(
			'icone'      => $icone["relatorio"],
			'fabrica_no' => array(30),
			'link'       => 'os_sem_pedido.php',
			'titulo'     => traduz('OSs que n�o Geraram Pedidos'),
			'descr'      => traduz('Ordens de Servi�os que n�o geraram pedidos de pe�as.'),
			"codigo"     => 'GER-8040'
		),
		array(
			'icone'      => $icone["cadastro"],
			'fabrica_no' => array(30),
			'link'       => 'os_revenda.php',
			'titulo'     => traduz('Cadastra OS - REVENDA'),
			'descr'      => traduz('Cadastro de Ordem de Servi�os de revenda'),
			"codigo"     => 'GER-8050'
		),
		array(
			'icone'      => $icone["consulta"],
			'fabrica_no' => array(30),
			'link'       => 'os_revenda_parametros.php',
			'titulo'     => traduz('Consulta OS - REVENDA'),
			'descr'      => traduz('Consulta OS Revenda Lan�adas'),
			"codigo"     => 'GER-8060'
		),
		array(
			'icone'   => $icone["cadastro"],
			'fabrica' => array(30,153),
			'link'    => 'parametros_intervencao.php',
			'titulo'  => traduz('Par�metros para Interven��es'),
			'descr'   => traduz('Configura��o de par�metros para entrada em interven��o'),
			"codigo"  => 'GER-8070'
		),
		'link' => 'linha_de_separa��o',
	),
	// Se��o OS - Apenas
	'secaoPD' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('PEDIDOS DE PE�AS'),
			'fabrica' => array(108,111)
		),
		array(
			'icone'  => $icone["cadastro"],
			'link'   => 'pedido_cadastro.php',
			'titulo' => traduz('Cadastra Pedidos'),
			'descr'  => traduz('Cadastra um novo pedido de pe�as'),
			"codigo" => 'GER-9000'
		),
		array(
			'icone'  => $icone["consulta"],
			'link'   => 'pedido_parametros.php',
			'titulo' => traduz('Consulta Pedidos'),
			'descr'  => traduz('Consulta pedidos de pe�as'),
			"codigo" => 'GER-9010'
		),
		array(
			'icone'  => $icone["consulta"],
			'link'   => 'comunicado_produto_consulta.php',
			'titulo' => traduz('Vista Explodida e Comunicados'),
			'descr'  => traduz('Consulta vista explodida, diagramas, esquemas e comunicados.'),
			"codigo" => 'GER-9020'
		),
		'link' => 'linha_de_separa��o',
	),
	// Se��o CALL-CENTER - GERAL
	'secaoCC' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('RELAT�RIOS CALL-CENTER'),
			'fabrica_no' => array_merge(array(87,108,111,115,116,117,122,81,114,124,123,127,128,129,136,138,139,141,142,143,144,145), $fabricas_contrato_lite)
		),
		array(
			'fabrica_no' => $arr_fabrica_padrao,
			'icone'      => $icone["relatorio"],
			'link'       => 'relatorio_callcenter_reclamacao_por_estado.php',
			'titulo'     => traduz('Reclama��es por estado'),
			'descr'      => traduz('Hist�rico de atendimentos por estado.'),
			'codigo'     => 'GER-10000'
		),
		array(
			'fabrica' => ($telecontrol_distrib == 't') ? [$login_fabrica] : [35,80,174,186],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_solicitacoes_postagem.php',
			'titulo'  => traduz('Solicita��es de Postagem'),
			'descr'   => traduz('Relat�rio de solicita��es de postagem por intervalo de datas.'),
			'codigo'  => 'GER-10010'
		),
		array(
			'fabrica' => ($telecontrol_distrib == 't') ? [$login_fabrica] : [186],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_callcenter_reclamacao_por_periodo.php',
			'titulo'  => 'Reclama��es por periodo',
			'descr'   => 'Relat�rio de hist�rico de atendimentos por periodo.',
			'codigo'  => 'GER-10020'
        	),
		array(
			'fabrica' => [189],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_custo_atendimento.php',
			'titulo'  => 'Custos dos Atendimentos',
			'descr'   => 'Relat�rio de custos do atendimento.',
			'codigo'  => 'GER-10030'
		),
		 array(
            'fabrica' => [183,189],
            'icone'   => $icone["relatorio"],
            'link'    => 'acompanhamento_atendimentos.php',
            'titulo'  => 'Acompanhamento dos Atendimentos',
            'descr'   => 'Relat�rio de acompanhamento de prazos dos atendimentos.',
            'codigo'  => 'GER-10040'
        ),
		 array(
            'fabrica' => [186],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_custos_postagens.php',
            'titulo'  => 'Custos de Postagens',
            'descr'   => 'Relat�rio de Custos de postagens.',
            'codigo'  => 'GER-10050'
        ),
		'link' => 'linha_de_separa��o',
	),
	// Se��o RELATORIOS ROCA
	'secaoRC' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => 'RELAT�RIOS ROCA',
			'fabrica'    => [178]
		),
		array(
			'fabrica' => [178],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_os_aberta_fechada_mes.php',
			'titulo'  => traduz('OS Abertas e Fechadas no M�s'),
			'descr'   => traduz('Relat�rio de ordens de servi�os (OS) abertas e fechadas no m�s.'),
			'codigo'  => 'GER-10060'
		),
		array(
			'fabrica' => [178],
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_callcenter_contatos_mes.php',
			'titulo'  => traduz('Contatos Realizados Por Atendimentos Callcenter no M�s'),
			'descr'   => traduz('Relat�rio de contatos feitos por atendimentos callcenter no m�s.'),
			'codigo'  => 'GER-10070'
		),
		'link' => 'linha_de_separa��o',
	),
	// Se��o Gerencia - GERAL
	'secaoTP' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('RELAT�RIOS - TEMPO DE PROCESSOS'),
			'fabrica' => array(152,169,170,180,181,182)
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'abertura_os_faturamento_peca.php',
			'titulo'  => traduz('Abertura da OS X Faturamento da Pe�a'),
			'descr'   => traduz('Contabiliza a data de abertura da OS at� o faturamento da pe�a mostrando a quantidade de dias.'),
			"codigo"  => 'GER-11000'
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'pedido_faturamento_pedido.php',
			'titulo'  => traduz('Gera��o do pedido x faturamento do pedido'),
			'descr'   => traduz('Calculo da data que o pedido foi gerado at� o faturamento das pe�as.'),
			"codigo"  => 'GER-11010'
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'recebimento_analise_garantia.php',
			'titulo'  => traduz('Recebimento x An�lise Garantia'),
			'descr'   => traduz('Contabilizar a data que a OS entrou em auditoria at� a data que a OS foi liberada da auditoria "OS em auditoria de Defeito Constatado".'),
			"codigo"  => 'GER-11020'
		),
		array(
			'fabrica' => array(152,169,170,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'tempo_falha_equipamento.php',
			'titulo'  => traduz('Tempo M�dio de Falha por Equipamento'),
			'descr'   => traduz('Contabilizar a data da compra do produto pelo consumidor (data da NF) at� a data de abertura da OS.'),
			"codigo"  => 'GER-11030'
		),
		array(
			'fabrica' => array(152,180,181,182),
			'icone'   => $icone["relatorio"],
			'link'    => 'faturamento_peca_fechamento_os.php',
			'titulo'  => traduz('Faturamento da Pe�a x Fechamento da OS'),
			'descr'   => traduz('Contabiliza tempo de faturamento da pe�a at� o fechamento da ordem de servi�o.'),
			'codigo'  => 'GER-11040'
		),
		'link' => 'linha_de_separa��o',
	),
	// Se��o QUALIDADE - Apenas Bosch
	'secaoQ' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('RELAT�RIOS - QUALIDADE'),
			'fabrica' => array(20)
		),
		array(
			'icone'  => $icone["relatorio"],
			'link'   => 'extrato_pagamento_peca.php',
			'titulo' => traduz('Pe�a X Custo'),
			'descr'  => traduz('Relat�rio de OSs e seus produtos e valor pagos por pe�a.'),
			"codigo" => "GER-12000"
		),
		array(
			'icone'  => $icone["relatorio"],
			'link'   => 'relatorio_field_call_rate_produto_custo.php',
			'titulo' => traduz('Field Call Rate de Produto X Custo'),
			'descr'  => traduz('Relat�rio de Field Call Rate de Produtos e valor pagos por per�odo.'),
			"codigo" => "GER-12010"
		),
		'link' => 'linha_de_separa��o',
	),
	// Se��o TAREFAS ADMINISTRATIVAS - Geral
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
			'titulo'  => traduz('Log de erros de integra��o'),
			'descr'   => traduz('Verificar erros na integra��o entre Logix e Telecontrol'),
			"codigo"  => 'GER-13010'
		),
		array(
			'fabrica' => array(11,172),
			'icone'   => $icone["usuario"],
			'link'    => 'manutencao_contato.php',
			'titulo'  => traduz('Manuten��o de contatos �teis'),
			'descr'   => traduz('Manuten��o de contatos �teis da �rea do posto.'),
			"codigo"  => 'GER-13020'
		),
		array(
			'fabrica_no' => array(175),
			'icone'  => $icone["consulta"],
			'link'   => "https://ww2.telecontrol.com.br/docs?fabrica={$login_fabrica}&nome={$login_fabrica_nome}&key=".md5($login_fabrica_nome.$login_fabrica),
			'titulo' => traduz('API P�s-Venda DOC'),
			'descr'  => traduz('Documenta��o das APIs da Telecontrol para integra��o'),
			"codigo" => 'GER-13030'
		),
		array(
			'icone'  => $icone["usuario"],
			'link'   => 'admin_senha_n.php',
			'titulo' => traduz('Usu�rios ADMIN'),
			'descr'  => traduz('Cadastro de usu�rios administradores do sistema, com op��o para troca de senha e atribui��o de privil�gios de acesso aos programas.'),
			"codigo" => 'GER-13040'
		),
		array(
			'fabrica' => array(50),
			'icone'   => $icone["usuario"],
			'link'    => 'admin_chat.php',
			'titulo'  => traduz('Usu�rios de Chat'),
			'descr'   => traduz('Administra��o de Usu�rio com acesso ao Chat'),
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
			'titulo'  => traduz('Supervis�o de Chat\'s Ativos'),
			'descr'   => traduz('Painel para visualiza��o e supervis�o de atendimentos ativos'),
			"codigo"  => 'GER-13070'
		),
		array(
			'fabrica' => array(10,86), //Famastil, por enquanto
			'icone'   => $icone["computador"],
			'link'    => 'consulta_auto_credenciamento.php',
			'titulo'  => traduz('Auto-Credenciamento de Postos'),
			'descr'   => traduz('Lista postos que gostariam de ser credenciados da '.$login_fabrica_nome .',<br />').
			'mostra informa��es do posto, localiza no GoogleMaps<br />'.
			'e permite credenciar postos.',
			"codigo"  => 'GER-13080'
		),
		/**
		 * N�O ATIVAR ESTE PROGRAMA PARA MAIS NENHUMA F�BRICA SEM FALAR COMIGO. �BANO
		 **/
		//if(!in_array($login_fabrica,$fabricas_contrato_lite) and $login_fabrica<>72)
		array(
			'fabrica' => array(24,85),
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_usuario_admin.php',
			'titulo'  => traduz('Relat�rio de Acesso'),
			'descr'   => traduz('Relat�rio de Controle de Acessos.'),
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
			'descr'      => traduz('Apaga todas as informa��es do posto de teste, como OS, pedido e extrato'),
			"codigo"     => 'GER-13140'
		),
		array(
			'fabrica' => array(6),
			'icone'   => $icone["computador"],
			'link'    => 'reincidencia_os_cadastro.php',
			'titulo'  => traduz('Remanejamento de reincid�ncias'),
			'descr'   => traduz('Efetua a substitui��o da OS reincidida para a OS principal.'),
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
			'descr'   => traduz('Efetua a carga de dados para atualiza��o do sistema.'),
			"codigo"  => 'GER-13180'
		),
		array(
			'fabrica' => (isset($novaTelaOs)),
			'icone'   => $icone["upload"],
			'link'    => 'verificador_mapa_posto.php',
			'titulo'  => traduz('Localiza��o dos Postos Autorizados'),
			'descr'   => traduz('Atualiza as informa��es dos Postos Autorizados no Mapa.'),
			"codigo"  => 'GER-13190'
		),
		array(
			'fabrica' => array(158),
			'icone'   => $icone["upload"],
			'link'    => 'upload_os_kof.php',
			'titulo'  => traduz('Reenvio OS para KOF'),
			'descr'   => traduz('Atualiza a lista de OS para exporta��o.'),
			"codigo"  => 'GER-13200'
		),
		array(
            'fabrica' => array(169,170),
            'icone'   => $icone["upload"],
            'link'    => 'consulta_pedido_nao_faturado_pecas.php',
            'titulo'  => traduz('Consulta Pedido de Pe�as N�o faturado/Faturado parcialmente'),
            'descr'   => traduz('Realiza Consulta de pedido n�o faturado / faturado parcialmente'),
            "codigo"  => 'GER-13210'
        ),
        array(
            'fabrica' => array(169,170),
            'icone'   => $icone["upload"],
            'link'    => 'upload_faturar_pedido_pecas.php',
            'titulo'  => traduz('Atualiza da de entrega pedido de pe�as'),
            'descr'   => traduz('Atualiza previs�o data de entrega de pedido de pe�as'),
            "codigo"  => 'GER-13220'
        ),
		array(
			'fabrica' => [10, 169, 170],
            'icone'   => $icone["usuario"],
            'link'    => 'admin_restricao_ip.php',
            'titulo'  => traduz('Limitar Acesso por IP'),
		    'descr'   => traduz('Limita o acesso de usu�os com base no endere�o IP, de acordo com as faixas de IP cadastradas.'),
			"codigo"  => 'GER-13230'
		),
		array(

            'fabrica' => [148],
            'icone'   => $icone["computador"],
            'link'    => 'manutencao_email_admin.php',
            'titulo'  => 'Manuten��o dos Emails dos Admins',
            'descr'   => 'Efetua a manuten��o dos emails dos admins no sistema',
            "codigo"  => 'GER-13240'
        ),
		array(		
			'icone'   =>  $icone["consulta"],
			'link'    => 'manutencao_km_posto.php',
			'titulo'  => traduz('Manuten��o valor por KM Postos'),
			'descr'   => traduz('Realizar manuten��o de valor pago por KM para um, v�rios ou todos os Postos.'),
			"codigo"  => 'GER-13250'
		),
			array(		
			'fabrica' => array(186),
			'icone'   =>  $icone["bi"],
			'link'    => 'cotacao_frete_correios.php',
			'titulo'  => 'Cotar Frete Pedidos',
			'descr'   => 'Cota��o de frete e sele��o de tipo de servi�o para enviar pedidos do posto.',
			"codigo"  => 'GER-13260'
		),
			array(		
			'fabrica' => array(186),
			'icone'   =>  $icone["bi"],
			'link'    => 'imprimir_etiqueta_correios.php',
			'titulo'  => 'Imprimir Etiqueta e Declara��o de Conte�do',
			'descr'   => 'Imprimir etiquetas geradas para os pedidos junto com a Declara��o de Conte�do.',
			"codigo"  => 'GER-13270'
		),
			array(		
			'fabrica' => array(186),
			'icone'   =>  $icone["bi"],
			'link'    => 'gerar_plp_pedidos.php',
			'titulo'  => 'Gerar PLP Pedidos',
			'descr'   => 'Gera��o da Pr� Lista de Postagem para as etiquetas geradas.',
			"codigo"  => 'GER-13280'
		),
		array(
			'fabrica' => array(183),		
			'icone'   =>  $icone["cadastro"],
			'link'    => 'regras_parametros_pedido.php',
			'titulo'  => 'Manuten��o regras par�metros pedidos',
			'descr'   => 'Realizar manuten��o nas regras de par�metros para gera��o de pedidos.',
			"codigo"  => 'GER-13290'
		),

		'link' => 'linha_de_separa��o',
	),
	// Se��o PESQUISA DE OPINI�O - Geral
	'secaoO' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('PESQUISA DE OPINI�O'),
			'fabrica'    => (in_array($login_fabrica, array(3,10,151)) or ($login_fabrica > 87 && !$novaTelaOs && !in_array($login_fabrica, array(172)) )),
			'fabrica_no' => array_merge(array(87,91,104,114,115,116,117,120,201,121,122,126,129,132,136,137,138,139,141,142,143,144,145,146), $fabricas_contrato_lite,$novaTelaOs)
		),
		array(
			'icone'  => $icone["cadastro"],
			'link'   => 'opiniao_posto.php',
			'titulo' => traduz('Cadastro do Question�rio'),
			'descr'  => traduz('Cadastro do Question�rio de Opini�o do Posto'),
			"codigo" => 'GER-14000'
		),
		array(
			'fabrica' => (in_array($login_fabrica, array(88,94,134,151))),
			'icone'   => $icone["relatorio"],
			'link'    => 'opiniao_posto_relatorio.php',
			'titulo'  => traduz('Relat�rio de Opini�o dos Postos'),
			'descr'   => traduz('Relat�rio dos question�rios enviados aos Postos'),
			"codigo"  => 'GER-14010'
		),
	 array(
	    'fabrica' => (in_array($login_fabrica, array(10))),
	    'icone'   => $icone["relatorio"],
	    'link'    => 'relatorio_pesquisa_inicial_posto.php',
	    'titulo'  => traduz('Relat�rio de Pesquisa de Opini�o dos Postos'),
	    'descr'   => traduz('Relat�rio dos question�rios enviados para saber a opini�o dos Postos'),
	    "codigo"  => 'GER-14020'
	 ),
		'link' => 'linha_de_separa��o',
	),
	// Se��o CADASTRO DOCUMENTA��O - Geral
	'secaDC' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('DOCUMENTA��O FABRICA'),
			'fabrica' => (in_array($login_fabrica, array(10)))
		),
		array(
			'icone'  => $icone["cadastro"],
			'link'   => 'documentacao_fabricas.php',
			'titulo' => traduz('Cadastro do Documenta��o'),
			'descr'  => traduz('Cadastro de Documenta��o para F�bricas'),
			"codigo" => 'GER-15000'
		),
		'link' => 'linha_de_separa��o',
	),
	// Se��o DISTRIB - Apenas Telecontrol
	'secaoD' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('DISTRIBUI��O TELECONTROL'),
			'fabrica' => $fabrica_distrib
		),
		array(
			'icone'  => $icone["computador"],
			'link'   => 'distrib_pendencia.php',
			'titulo' => traduz('Pend�ncia de Pe�as'),
			'descr'  => traduz('Pend�ncia de Pe�as dos Postos junto ao Distribuidor'),
			"codigo" => "GER-TC10"
		),
		array(
			'admin'  => 586,
			'icone'  => $icone["computador"],
			'link'   => 'distrib_pendencia_estudo.php',
			'titulo' => traduz('Estudo das Pend�ncias de Pe�as'),
			'descr'  => traduz('Estudo das pend�ncias de pe�as e sugest�o de pedido para f�brica (Garantia/Compra)'),
			"codigo" => "GER-TC20"
		),
		'link' => 'linha_de_separa��o',
	),
	// Se��o CONSULTAS - Apenas Jacto
	'secaoJC' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('CONSULTAS'),
			'fabrica' => array(87)
		),
		array(
			'icone'  => $icone["consulta"],
			'link'   => 'pedido_parametros.php',
			'titulo' => traduz('Consulta Pedidos de Pe�as'),
			'descr'  => traduz('Consulta pedidos efetuados por postos autorizados.'),
			"codigo" => "GER-JC10"
		),
		'link' => 'linha_de_separa��o',
	),
	// Se��o ADMN - Apenas Jacto
	'secaoJA' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => traduz('TAREFAS ADMINISTRATIVAS'),
			'fabrica' => array(87)
		),
		array(
			'icone'  => $icone["usuario"],
			'link'   => 'admin_senha_n.php',
			'titulo' => traduz('Usu�rios ADMIN'),
			'descr'  => traduz('Cadastro de usu�rios administradores do sistema, com op��o para troca de senha e atribui��o de privil�gios de acesso aos programas.'),
			"codigo" => "GER-JA10"
		),
		'link' => 'linha_de_separa��o',
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
			'titulo'  => traduz('Relat�rio de OS'),
			'descr'   => traduz('Relat�rio de OS para a Qualidade.'),
			"codigo"  => 'GER-16000'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_producao_venda_qualidade.php',
			'titulo'  => traduz('Relat�rio de Produ��o/Vendas'),
			'descr'   => traduz('Relat�rio de Produ��o e Vendas para a Qualidade.'),
			"codigo"  => 'GER-16010'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_fcr_x_vendas_qualidade.php',
			'titulo'  => traduz('Relat�rios sobre Vendas'),
			'descr'   => traduz('Relat�rios/Gr�ficos sobre vendas para a Qualidade.'),
			"codigo"  => 'GER-9120'
		),
		array(
            'icone'   => $icone["cadastro"],
            'link'    => 'planejamento_cadastro.php',
            'titulo'  => traduz('Planejamento Qualidade'),
	        'descr'   => traduz('Formul�rio de cadastro e revis�es do planejamento da qualidade.'),
			"codigo"  => 'GER-9130'
		),
		array(
            'icone'   => $icone["cadastro"],
            'link'    => 'planejamento_pd_cadastro.php',
            'titulo'  => traduz('Planejamento por PD Qualidade'),
	        'descr'   => traduz('Formul�rio de cadastro e revis�es do planejamento da qualidade para produtos importados por PD.'),
			"codigo"  => 'GER-9140'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_top_five_qualidade.php',
			'titulo'  => traduz('Relat�rio TOP FIVE - QUALIDADE'),
			'descr'   => traduz('Relat�rios/Gr�ficos dos 5 itens com mais quebras para a Qualidade.'),
			"codigo"  => 'GER-9150'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_top_five_produtos_pareto.php',
			'titulo'  => traduz('Relat�rio TOP FIVE - PARETO'),
			'descr'   => traduz('Relat�rios/Gr�ficos TOP Five Pareto'),
			"codigo"  => 'GER-9160'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_performance_prod_escape.php',
			'titulo'  => 'Relat�rios de Performance',
			'descr'   => 'Relat�rios/Gr�ficos sobre performance de falha.',
			"codigo"  => 'GER-9170'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_failure_rate.php',
			'titulo'  => 'Relat�rio de Falhas x Produ��o',
			'descr'   => 'Relat�rio falhas / production por pe�as e produtos',
			"codigo"  => 'GER-9180'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'fcr_production_sales.php',
			'titulo'  => 'Relat�rio de Performance',
			'descr'   => 'FCR Production x Unit Sold',
			"codigo"  => 'GER-9190'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_montanha_falha.php',
			'titulo'  => 'Relat�rio Montanha % Falha',
			'descr'   => 'Relat�rio de quebras por produ��o',
			"codigo"  => 'GER-9200'
		),
		array(
			'icone'   => $icone["relatorio"],
			'link'    => 'relatorio_tempo_falha.php',
			'titulo'  => 'Relat�rio de Falhas por Componente',
			'descr'   => 'Relat�rios/Gr�ficos de falhas por pe�a',
			"codigo"  => 'GER-9210'
		),
		'link' => 'linha_de_separa��o',
	),
	// Se��o GESTAO DE CONTRATOS
	'secaoGC' => array(
		'secao' => array(
			'link'    => '#',
			'titulo'  => 'GEST�O DE CONTRATOS',
			'fabrica' => ($moduloGestaoContrato == 't') ? $login_fabrica : 0
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'dashboard_contratos.php',
			'titulo'     => 'Dashboard Proposta x Contrato',
			'descr'      => 'Relat�rio Dashboard de Proposta x Contrato',
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
			'titulo'     => 'Cadastra Tabela de Pre�o',
			'descr'      => 'Cadastro de Tabela de Pre�o do Contrato',
			"codigo"     => 'GER-16060'
		)/*,
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'relatorio_custo_produto_n_serie.php',
			'titulo'     => 'Relat�rio Custo de Produtos',
			'descr'      => 'Relat�rio Custo de Produtos por N�mero de S�rie',
			"codigo"     => 'GER-16070'
		)*/,
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'relatorio_custo_produto_contrato.php',
			'titulo'     => 'Relat�rio Custo de Produtos por Contrato',
			'descr'      => 'Relat�rio Custo de Produtos por Contrato',
			"codigo"     => 'GER-16080'
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'acompanhamento_os_contrato.php',
			'titulo'     => 'Acompanhamento de Ordem de Servi�os',
			'descr'      => 'Acompanhamento de Ordem de Servi�os',
			"codigo"     => 'GER-16090'
		),
		'link' => 'linha_de_separa��o',
	),

);
