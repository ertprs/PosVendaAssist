<?php
include_once 'funcoes.php';

msgBloqueioMenu();

$fabrica_audita_todas_os     = array(14, 43);
$fabrica_audita_os_aberta    = array(1,3,45,80,40);
$fabrica_auditoria_previa    = array(3,  51, 80);
$fabrica_interv_serie        = array(30,85,120,201,136,137,141,149,150);

$fabrica_interv_tecnica      = array(6,35,43,72,80,81,85,98,104,105,106,108,111,114,115,116,117,120,201,122,123,124,125,126,127,128,129,131,132,134,136,137,138,140,142,143,145);
$fabrica_lgr_bateria         = array(1,42);
$fabrica_lgr_residuos        = array(1);
$fabrica_vistoria_lgr        = array(3,43);// HD 73410 - Tamb�m mostra a vistoria de Pe�as!
$fabricas_autocredenciamento = array(10,30,81,114,122,123,124,125,126,128,129,136,151,153,160);
if($replica_einhell) $fabricas_autocredenciamento[] = $login_fabrica;
$fabrica_auditoria_outros    = array(20,50,74,94,120,201,127,132,137,139,141,142,143,144,145,146,147);
$fabrica_auditoria_km        = array(19,30,46,50,72);
if ( isset($novaTelaOs) and !in_array($login_fabrica, array(138,147,151,153,160)) and !$replica_einhell ) $fabrica_auditoria_outros = array($login_fabrica);

$vet_km                      = array(15,30,35,46,50,72,87,91,94,120,201,138,140,141,142,143,145,146,149);
$vet_os_reincidente          = array(11,14,24,52,72,90,91,94,101,104,105,115,116,117,120,201,122,126,129,131,134,136,139,155,172);

$fabrica_troca               = array(1,51,81,114);
$fabrica_valores_adicionais = ($login_fabrica == 139 || $inf_valores_adicionais) ? array($login_fabrica) : array(0); // 139 temporario
$fabrica_os_intervencao = (((in_array($login_fabrica,array(2,3,6,11,14,19,24,25,30,35,45,50,51,52,72,74,80,81,85)) or $login_fabrica > 87) or in_array($login_fabrica, $fabricas_contrato_lite)) and !$replica_einhell) ? array($login_fabrica): array(0);

if(in_array($login_fabrica, array(149))){
	$titulo_auditoria_reincidente_excente = traduz("Auditoria de OS Reincedentes/Pe�as Excedentes");
}else{
	$titulo_auditoria_reincidente_excente = traduz("Auditoria da OS");
}

global $auditoria_unica;

if (in_array($login_fabrica,array(11, 35, 81, 104, 114, 122, 123, 125, 131, 139, 144, 147, 157, 158, 160, 164, 165, 167, 172, 203)) or $replica_einhell) {
	unset($auditoria_unica);
	$nova_auditoria_unica = true;
}

if ($os_auditoria_unica) {
	unset($auditoria_unica);
	$nova_auditoria_unica = true;
}

if($auditoria_unica){
    $fabrica_auditoria_unica[] = $login_fabrica;
}

if($login_fabrica == 35) {
	unset($fabrica_auditoria_unica);
}

if (in_array($login_fabrica, array(144,167,175,177,178,184,186,190,191,193,194,198,200,203))){
	$fabrica_auditoria_unica = [144,167,175,177,178,184,186,190,191,193,194,195,198,200,203];
}

if($login_fabrica == 147 AND $login_admin == 9135){
	$fabricas_autocredenciamento[] = $login_fabrica;
	$key = array_search('147', $fabrica_auditoria_outros);
	unset($fabrica_auditoria_outros["$key"]);
}

if($login_fabrica == 146 AND $login_admin == 9140){
	$fabricas_autocredenciamento[] = $login_fabrica;
	$key = array_search('146', $fabrica_auditoria_outros);
	unset($fabrica_auditoria_outros["$key"]);
}

return array(
	'secao0' => array(
		'secao' => array(
			'link'     => '#',
			'titulo'   => traduz('AUDITORIA POSTOS'),
			//'noexpand' => true
		),
		array(
			'icone'  => $icone['acesso'],
			'link'   => 'posto_login.php',
			'titulo' => traduz('Logar como Posto'),
			'descr'  => traduz('Permite acesso ao sistema com privil�gios de um posto credenciado.'),
			'codigo' => 'AUD-0010'
		),
		array(
			'icone'  => $icone['consulta'],
			'link'   => 'consulta_posto_cadastro.php',
			'titulo' => traduz('Consulta de Postos Autorizados'),
			'descr'  => traduz('Consulta de postos autorizados por localiza��o, tipo e linhas.'),
			'codigo' => 'AUD-0020'
		),
		array(
			'fabrica' => array(1),
			'icone'   => $icone['relatorio'],
			'link'    => 'atualizacao_postos_black.php',
			'titulo'  => traduz('Relat�rio de Atualiza��o do Cadastro'),
			'descr'   => traduz('Relat�rio e consulta de dados de atualiza��o dos postos com base ao<br> formul�rio de preenchimento obrigat�rio.'),
			'codigo'  => 'AUD-0030'
		),
		array(
			'icone'   => $icone['relatorio'],
			'link'    => 'posto_linha.php',
			'titulo'  => traduz('Rela��o de Postos e Linhas'),
			'descr'   => traduz('Relat�rio de Postos e suas respectivas linhas.'),
			'codigo'  => 'AUD-0040'
		),
		array(
			'fabrica' => array(19),
			'icone'   => $icone['relatorio'],
			'link'    => 'postos_usando-lorenzetti.php',
			'titulo'  => traduz('Postos Usando'),
			'descr'   => traduz('Postos que utilizam o sistema.'),
			'codigo'  => 'AUD-0050'
		),
		array(
			'fabrica_no'=> array(19),
			'icone'     => $icone['bi'],
			'link'      => 'bi/postos_usando.php',
			'titulo'    => traduz('Postos Usando'),
			'descr'     => traduz('Relat�rio por per�odo para os postos que utilizam o sistema pela data de abertura').'<br>'.
			'<i>'.traduz('O BI � atualizado com as informa��es do dia anterior, portanto tem um dia de atraso!').'</i>',
			'codigo'    => 'AUD-0060'
		),
		array(
			'fabrica'   => array(24),
			'icone'     => $icone['relatorio'],
			'link'      => 'postos_digita_os.php',
			'titulo'    => traduz('Postos Usando Total'),
			'descr'     => traduz('Postos que j� lan�aram OS no Telecontrol.'),
			'codigo'    => 'AUD-0070'
		),
		array(
			'icone'     => $icone['relatorio'],
			'link'      => 'postos_nao_usando.php',
			'titulo'    => traduz('Postos N�O Usando'),
			'descr'     => traduz('Postos que ainda n�o lan�aram OS no sistema.'),
			'codigo'    => 'AUD-0080'
		),
		array(
			'fabrica' 	 => array(101),
			'icone'      => $icone['consulta'],
			'link'       => 'extrato_posto_devolucao_controle.php',
			'titulo'     => traduz('Controle de Notas de Devolu��o - LGR'),
			'descr'      => traduz('Consulte ou confirme Notas Fiscais de Devolu��o.'),
			'codigo'     => 'AUD-0090'
		),
        array(
            'fabrica' 	 => array(101),
            'icone'      => $icone["relatorio"],
            'link'       => 'relatorio_lgr.php',
            'titulo'     => traduz("Relat�rio do N�o Preenchimento do LGR"),
            'descr'      => traduz('Relat�rio dos Postos que n�o preencheram o LGR.'),
            'codigo'     => 'AUD-1000'
        ),
		array(
			'fabrica'   => array(161),
			'icone'     => $icone['relatorio'],
			'link'      => 'devolucao_pendente.php',
			'titulo'    => traduz('Devolu��o Pendente mais de 90 dias'),
			'descr'     => traduz('Devolu��o Pendente mais de 90 dias'),
			'codigo'    => 'AUD-0090'
		),
		array(
			'fabrica'   => array(19),
			'icone'     => $icone['relatorio'],
			'link'      => 'postos_nao_usando_sac.php',
			'titulo'    => traduz('Postos N�O abriram OS pelo SAC'),
			'descr'     => traduz('Postos que n�o abriram OS pelo SAC (admin).'),
			'codigo'    => 'AUD-0090'
		),
		array(
			'fabrica'   => array(3, 11, 145, 151, 172),
			'icone'     => $icone['bi'],
			'link'      => 'bi_medias_postos.php',
			'titulo'    => traduz('Relat�rio de indicadores de postos autorizados'),
			'descr'     => traduz('Relat�rio de indicadores de postos autorizados.'),
			'codigo'    => 'AUD-0100'
		),
		array(
			'fabrica'   => array(1,24),
			'icone'     => $icone['relatorio'],
			'link'      => 'posto_bloqueado.php',
			'titulo'    => traduz('Postos Bloqueados'),
			'descr'     => traduz('Consulta de PAs bloqueados com OS abertas a mais de ').((in_array($login_fabrica, array(1))) ? '60 ' : '180 ').traduz('dias.'),
			'codigo'    => 'AUD-0110'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_produto_peca_posto.php',
			'titulo'    => traduz('Relat�rio de Pe�a por Posto e por Per�odo'),
			'descr'     => traduz('Relat�rio de pe�a por posto e por per�odo.'),
			'codigo'    => 'AUD-0120'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_pesquisa_posto_blackedecker.php',
			'titulo'    => traduz('Relat�rio de Pesquisa de Posto'),
			'descr'     => traduz('Relat�rio e consulta de dados da pesquisa de posto.'),
			'codigo'    => 'AUD-0130'
		),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone['relatorio'],
            'link'      => 'posto_bloqueado_pedido_faturado.php',
            'titulo'    => traduz('Postos Bloqueados no Cr�dito'),
            'descr'     => traduz('Consulta de PAs bloqueados com OS abertas a mais de 60 dias'),
            'codigo'    => 'AUD-0140'
        ),
		array(
			'fabrica'   => array(74),
			'icone'     => $icone['relatorio'],
			'link'      => 'inspecao_posto.php',
			'titulo'    => traduz('Inspe��o de Posto Autorizado'),
			'descr'     => traduz('Cadastra inspe��o para posto Autorizado.'),
			'codigo'    => 'AUD-0150'
		),
		 array(
			'fabrica'   => array(74),
			'icone'     => $icone['relatorio'],
			'link'      => 'formulario_consulta_inspecao_posto.php',
			'titulo'    => traduz('Relat�rio de Inspe��o de Posto Autorizado'),
			'descr'     => traduz('Relat�rio da  inspe��o do posto autorizado.'),
			'codigo'    => 'AUD-0160'
		),
		 array(
			'fabrica'   => array(3),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_auditoria_posto.php',
			'titulo'    => traduz('Auditoria de Postos'),
			'descr'     => traduz('Painel de auditoria de postos autorizados.'),
			'codigo'    => 'AUD-0170'
		),
		array(
			'fabrica' 	=> array(175),
			'icone' 	=> $icone['relatorio'],
			'link' 		=> 'auditoria_ferramentas.php',
			'titulo' 	=> traduz('Ferramentas'),
			'descr' 	=> traduz('Relat�rio de ferramentas pendente de auditoria ou de um Posto Autorizado espec�fico '),
			'codigo' 	=> 'AUD-0180'
		),
		 array(
			'fabrica'   => array(120,201),
			'icone'     => $icone['computador'],
			'link'      => 'auditoria_top_posto_pecas.php',
			'titulo'    => traduz('Auditoria top 10 pecas por Postos'),
			'descr'     => traduz('Auditoria, sobre a quantidade de pe�as que os posto fazem pedido. top 10.'),
			'codigo'    => 'AUD-0200'
		),
		array(
			'fabrica'   => [177],
			'icone'     => $icone['computador'],
			'link'      => 'check_list_visita.php',
			'titulo'    => traduz('Checklist de Visita'),
			'descr'     => traduz('Cadastro de Checklist de Visita'),
			'codigo'    => 'AUD-0210'
		),
		array(
			'fabrica'   => [177],
			'icone'     => $icone['computador'],
			'link'      => 'relatorio_checklist_visita.php',
			'titulo'    => traduz('Relat�rio Checklist de Visita'),
			'descr'     => traduz('Consulta e impress�o dos checklists cadastrados'),
			'codigo'    => 'AUD-0220'
		),
        'link' => 'linha_de_separa��o'
    ),
    'secao2' => array(
        'secao' => array(
            'link'     => '#',
			'link' => 'linha_de_separa��o'
		)
	),
	'secao1' => array(
		'secao' => array(
			'link'     => '#',
			'titulo'   => traduz('AUDITORIA RELAT�RIO OS'),
			//'noexpand' => true
		),
		array(
			'fabrica'   => array(6),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_aberta_tectoy.php',
			'titulo'    => traduz('Relat�rio de Ordens de Servi�o em aberto'),
			'descr'     => traduz('Mostra as Ordens de Servi�o que est�o em aberto.'),
			'codigo'    => 'AUD-1010'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_regiao.php',
			'titulo'    => traduz('Relat�rio de OS por Regi�o'),
			'descr'     => traduz('Relat�rio de Ordens de Servi�o por Regi�o.'),
			'codigo'    => 'AUD-1020'
		),
		array(
			'fabrica'   => array(6),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_regiao.php',
			'titulo'    => traduz('Relat�rio de OS por Estado'),
			'descr'     => traduz('Relat�rio de Ordens de Servi�o por Estado dos Postos Autorizados.'),
			'codigo'    => 'AUD-1030'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_aberta_geral.php',
			'titulo'    => traduz('Relat�rio Geral de Ordens de Servi�o'),
			'descr'     => traduz('Mostra as Ordens de Servi�o abertas pelo posto - Crit�rio de Abertura.'),
			'codigo'    => 'AUD-1040'
		),
		array(
			'fabrica_no'=> array(1,11,172), // �, estava assim no arquivo original...
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'rel_os_por_posto.php',
			'titulo'    => traduz('Ordens de Servi�o aberta por Posto'),
			'descr'     => traduz('Mostra as Ordens de Servi�o que est�o abertas por posto.'),
			'codigo'    => 'AUD-1050'
		),
		array(
			//'disabled'  => true,
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => '#',
			'titulo'    => traduz('Ordens de Servi�o aberta por Posto (INATIVO)'),
			'descr'     => traduz('Mostra as Ordens de Servi�o que est�o abertas por posto. (INATIVO).'),
			'codigo'    => 'AUD-1060'
		),
		array(
			'disabled'  => true,
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => '#',
			'titulo'    => traduz('Ordens de Servi�o aberta por Posto'),
			'descr'     => traduz('Mostra as Ordens de Servi�o que est�o abertas por posto.'),
			'codigo'    => 'AUD-1070'
		),
		array(
			'fabrica'   => array(11,172),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_aberta_lenoxx.php',
			'titulo'    => traduz('Relat�rio de Ordens de Servi�o em aberto'),
			'descr'     => traduz('Mostra as Ordens de Servi�o que est�o em aberto.'),
			'codigo'    => 'AUD-1080'
		),
		array(
			'fabrica'   => $fabrica_audita_os_aberta,
			'fabrica_no'=> array(11,172),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_aberta.php',
			'titulo'    => traduz('Relat�rio de Ordens de Servi�o em aberto'),
			'descr'     => traduz('Mostra as Ordens de Servi�o que est�o em aberto.'),
			'codigo'    => 'AUD-1090'
		),
		array(
			'fabrica'   => array_merge($fabrica_audita_os_aberta, array(11,172)),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_aberta_completo.php',
			'titulo'    => traduz('Relat�rio de Ordens de Servi�o em aberto Completo'),
			'descr'     => traduz('Mostra as Ordens de Servi�o que est�o em aberto e suas pe�as e defeitos.'),
			'codigo'    => 'AUD-1100'
		),
		array(
			'fabrica'   => array(11,172),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_peca_lenoxx.php',
			'titulo'    => traduz('Pedido de Pe�a > 15 dias'),
			'descr'     => traduz('Relat�rio de Ordem de Servi�o com pedido de pe�as com mais de 15 dias.'),
			'codigo'    => 'AUD-1110'
		),
		array(
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_por_posto_peca.php',
			'titulo'    => traduz('Relat�rio de OSs digitadas'),
			'descr'     => traduz('Mostra as Ordens de Servi�o digitadas no sistema.'),
			'codigo'    => 'AUD-1120'
		),

		array(
			'fabrica'   => array(101,115,116),
			'icone'     => $icone['relatorio'],
			'link'      => 'os_mais_tres_pecas.php',
			'titulo'    => traduz('OS com 5 pe�as ou mais'),
			'descr'     => traduz('Relat�rio para auditoria dos postos que utilizam 5 pe�as ou mais por Ordem de Servi�o.'),
			'codigo'    => 'AUD-1130'
		),
		array(
            'fabrica_no'=> ((isset($novaTelaOs)) ? array($login_fabrica) : array(71,101,114,115,116,122,127,131,136,137,140,141,144)),
			'icone'     => $icone['relatorio'],
			'link'      => 'os_mais_tres_pecas.php',
			'titulo'    => traduz('OS com 3 pe�as ou mais'),
			'descr'     => traduz('Relat�rio para auditoria dos postos que utilizam 3 pe�as ou mais por Ordem de Servi�o.'),
			'codigo'    => 'AUD-1140'
		),
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_exlcuida_90_150_dias.php',
			'titulo'    => traduz('Relat�rio de OSs Exclu�das sem Pe�as maior que 90 e 150 dias'),
			'descr'     => traduz('Relat�rio de OSs exclu�das sem pe�as maior que 90 dias para consumidor e 150 dias para revenda.'),
			'codigo'    => 'AUD-1150'
		),
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_por_posto_peca_britania.php',
			'titulo'    => traduz('Relat�rio Mensal de Ordens de Servi�o'),
			'descr'     => traduz('Donwload do Relat�rio de Ordens de Servi�os Digitadas.'),
			'codigo'    => 'AUD-1160'
		),
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_finalizada_por_posto_peca_britania.php',
			'titulo'    => traduz('Relat�rio Mensal de Ordens de Servi�o Finalizadas'),
			'descr'     => traduz('Donwload do Relat�rio de Ordens de Servi�os Finalizadas dentro de um m�s.'),
			'codigo'    => 'AUD-1170'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_codigo_fabricacao.php',
			'titulo'    => traduz('Relat�rio de C�digo de fabrica��o'),
			'descr'     => traduz('Relat�rio de OSs lan�adas filtrando pelo c�digo de fabrica��o, per�odo e fam�lia.'),
			'codigo'    => 'AUD-1180'
		),
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_auditoria_os_aberta_90.php',
			'titulo'    => traduz('Relat�rio de OS Aberta (90 dias)'),
			'descr'     => traduz('Relat�rio de Auditoria de OSs abertas a mais de 90 dias.'),
			'codigo'    => 'AUD-1190'
		),
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_auditoria_os_aberta_45.php',
			'titulo'    => traduz('Relat�rio de OS Aberta (45 dias)'),
			'descr'     => traduz('Relat�rio de Auditoria de OSs abertas a mais de 45 dias.'),
			'codigo'    => 'AUD-1200'
		),
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_aberta_detalhado_britania.php',
			'titulo'    => traduz('Relat�rio de OS Aberta Detalhado INFO'),
			'descr'     => traduz('Relat�rio de OSs abertas por linha.'),
			'codigo'    => 'AUD-1210'
		),
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_grafico_os_parada_x_os_aberto.php',
			'titulo'    => traduz('Relat�rio de OS em aberto x Demanda de OS'),
			'descr'     => traduz('Comparativa das OS sem interven��o do posto (s� abertas, sem an�lise) h� mais de 10 dias com as OS abertas durante a semana.'),
			'codigo'    => 'AUD-1220'
		),
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['computador'],
			'link'      => 'auditoria_os_aberta_90.php',
			'titulo'    => traduz('Auditoria OS Aberta (90 dias) &ndash; INFO'),
			'descr'     => traduz('Auditoria de OSs abertas a mais de 90 dias.'),
			'codigo'    => 'AUD-1230'
		),
		array(
			'fabrica'   => array(114),
			'icone'     => $icone['computador'],
			'link'      => 'auditoria_os_pedido.php',
			'titulo'    => traduz('Auditoria OS Abertura Pedido'),
			'descr'     => traduz('Auditoria de OSs para gera��o de pedidos.'),
			'codigo'    => 'AUD-1230'
		),
		array(
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_os_sem_peca.php',
			'titulo'    => traduz('OSs abertas e sem Lan�amento de Pe�as'),
			'descr'     => traduz('Relat�rio de quantidade de OSs abertas e sem lan�amento de pe�as.'),
			'codigo'    => 'AUD-1240'
		),
		array(
			'fabrica'   => array(40),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_os_com_peca.php',
			'titulo'    => traduz('OSs abertas e com Lan�amento de Pe�as'),
			'descr'     => traduz('Relat�rio que consta os postos e a quantidade de OSs que est�o abertas e com lan�amento de pe�as.'),
			'codigo'    => 'AUD-1250'
		),
		array(
			'fabrica_no'=> $fabricas_contrato_lite,
			'icone'     => $icone['relatorio'],
			'link'      => 'os_relatorio.php',
			'titulo'    => traduz('Status Ordem de Servi�o'),
			'descr'     => traduz('Relat�rio de status de OS por per�odo.'),
			'codigo'    => 'AUD-1260'
		),
		array(
			'fabrica'   => array(104),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_auditoria_pedido_sedex.php',
			'titulo'    => traduz('Relat�rio de Pedidos n�o Sedex'),
			'descr'     => traduz('Mostra os pedidos que n�o s�o pedido sedex.'),
			'codigo'    => 'AUD-1450'
		),
		array(
			'fabrica'   => array(15),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_em_aberto.php',
			'titulo'    => traduz('Relat�rio de OSs em Aberto'),
			'descr'     => traduz('Relat�rio OSs em aberto por per�odo.'),
			'codigo'    => 'AUD-1460'
		),
		array(
			'fabrica'   => array(80),
			'icone'     => $icone['chart'],
			'link'      => 'relatorio_grafico_os_aberto.php',
			'titulo'    => traduz('Relat�rio Gr�fico de OS em Aberto'),
			'descr'     => traduz('Gr�fico resumo das OS ainda em aberto ap�s 5, 15,').'<br>'.traduz('25 e mais de 25 dias, com filtro por posto ou produto'),
			'codigo'    => 'AUD-1270'
		),
		array(
			//HD 138788
			'fabrica'   => $fabrica_auditoria_km,
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_km_os.php',
			'titulo'    => traduz('Relat�rio de OS com KM solicitada'),
			'descr'     => traduz('Relat�rio que exibe as OS finalizadas e com KM solicitada.'),
			'codigo'    => 'AUD-1280'
		),
		array(
			'fabrica'   => array(30),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_esmaltec.php',
			'titulo'    => traduz('Relat�rio de OS'),
			'descr'     => traduz('Relat�rio de Ordem de Servi�o.'),
			'codigo'    => 'AUD-1290'
		),
		array(
			'fabrica'   => array(11,172),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_status_posto.php',
			'titulo'    => traduz('Relat�rio de status das OSs abertas'),
			'descr'     => traduz('Relat�rio que consta as status das OSs abertas por posto.'),
			'codigo'    => 'AUD-1300'
		),
		array(
			'fabrica'   => array(2),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_aberta_dynacom.php',
			'titulo'    => traduz('Relat�rio OS Aberta'),
			'descr'     => traduz('Relat�rio mostra OSs em aberto no posto e o motivo, OSs sem pe�as, com pe�as pedentes, etc.'),
			'codigo'    => 'AUD-1310'
		),
		array(
			'fabrica'   => array(87),
			'icone'     => $icone['consulta'],
			'link'      => 'auditoria_os_aberta.php',
			'titulo'    => traduz('Auditoria de OS Abertas'),
			'descr'     => traduz('Consulta de relat�rio de OS abertas a mais de 30 ou 70 dias e de OS sem n�mero de s�rie do produto.'),
			'codigo'    => 'AUD-1320'
		),
		array(
			'fabrica'   => array(74),
			'icone'     => $icone['consulta'],
			'link'      => 'auditoria_os_aberta_70_dias.php',
			'titulo'    => traduz('Auditoria OS 30/70 dias e N� S�rie'),
			'descr'     => traduz('Consulta de relat�rio de OS abertas a mais de 30 ou 70 dias e de OS reincidente pelo n�mero de s�rie do produto.'),
			'codigo'    => 'AUD-1330'
		),
		array(
			'fabrica'   => array(91,101),
			'icone'     => $icone['consulta'],
			'link'      => 'auditoria_os_aberta_70_dias.php',
			'titulo'    => traduz('Auditoria de N� S�rie com Autoriza��o'),
			'descr'     => traduz('Consulta de relat�rio de OS abertas de n�mero de s�rie com autoriza��o.'),
			'codigo'    => 'AUD-1340'
		),
		array(
			'fabrica'   => array(87),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_os_soaf.php',
			'titulo'    => traduz('Auditoria OS SOAF'),
			'descr'     => traduz('Relat�rio para auditoria das Ordens de Servi�o que tem SOAF.'),
			'codigo'    => 'AUD-1350'
		),
		array(
			'fabrica'   => array(24),
			'icone'     => $icone['cadastro'],
			'link'      => 'auditoria_online_suggar.php',
			'titulo'    => traduz('Auditoria Online'),
			'descr'     => traduz('Cadastrar relat�rio de Auditoria Online.'),
			'codigo'    => 'AUD-1360'
		),
		array(
			'fabrica'   => array(24),
			'icone'     => $icone['consulta'],
			'link'      => 'auditoria_online_consulta.php',
			'titulo'    => traduz('Consulta Auditoria Online'),
			'descr'     => traduz('Consulta de relat�rio de Auditoria Online.'),
			'codigo'    => 'AUD-1370'
		),
		array(
			'fabrica'   => array(50),
			'icone'     => $icone['consulta'],
			'link'      => 'auditoria_24_hrs.php',
			'titulo'    => traduz('Auditoria de OS 24 horas'),
			'descr'     => traduz('Consulta de relat�rio de OS abertas a mais de 24 horas.'),
			'codigo'    => 'AUD-1380'
		),
		array(
			'fabrica'   => array(74),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_desassociada.php',
			'titulo'    => traduz('Auditoria de OS desassociada'),
			'descr'     => traduz('Consulta de relat�rio de OS desassociada de atendimento, para definir se ir� entrar em extrato.'),
			'codigo'    => 'AUD-1390'
		),
		array(
			'fabrica'   => array(6),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_nota_fiscal.php',
			'titulo'    => traduz('Auditoria de OS com Nota Fiscal'),
			'descr'     => traduz('Relat�rio de OS com Nota Fiscal anexada: pendentes de aprova��o, aprovadas ou recusadas.'),
			'codigo'    => 'AUD-1400'
		),
		array(
			'fabrica'   => array(114),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_reincidente.php',
			'titulo'    => traduz('Relat�rio de OS reincidente'),
			'descr'     => traduz('Relat�rio de OS reincidente.'),
			'codigo'    => 'AUD-1410'
		),
		array(
			'fabrica'   => array(85),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_os_finalizadas.php',
			'titulo'    => traduz('Auditoria de OS finalizadas'),
			'descr'     => traduz('Auditoria de OS finalizadas.'),
			'codigo'    => 'AUD-1420'
		),
		array(
			'fabrica'   => array(30),
			'icone'     => $icone['relatorio'],
			'link'      => 'aprova_laudo_os_troca.php',
			'titulo'    => traduz('Auditoria Laudo Troca OS'),
			'descr'     => traduz('Auditoria de laudo de troca de OS.'),
			'codigo'    => 'AUD-1420'
		),
		array(
		    	'fabrica'=> array(85),
		    	'icone'     => $icone["relatorio"],
		    	'link'      => 'relatorio_liberacao_os.php',
		    	'titulo'    => traduz('Hist�rico de Libera��o de OS'),
		    	'descr'     => traduz('Relat�rio de Ordens de Servi�o Liberadas para extrato.'),
		    	"codigo" => 'AUD-1430'
        		),
		array(
			'fabrica'=> array(1, 3, 35, 117, 124, 169, 170),
			'icone'     => $icone["relatorio"],
			'link'      => 'relatorio_consulta_os_auditor.php',
			'titulo'    => traduz('Hist�rico de Log de Consulta de OS'),
			'descr'     => traduz('Relat�rio de Los de Consulta de OS a partir do site institucional.'),
			"codigo" => 'AUD-1430'
		),
		array(
			'fabrica'=> (($reparoNaFabrica)? array($login_fabrica):array() ),
			'icone'     => $icone["relatorio"],
			'link'      => 'solicitacao_reparo_fabrica.php',
			'titulo'    => traduz('Solicita��o de Reparo na F�brica'),
			'descr'     => traduz('Relat�rio de consulta as solicita��es de OS para reparo na F�brica.'),
			"codigo" => 'AUD-1440'
		),
		array(
			'fabrica'   => array(35,158),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_reincidente_novo.php',
			'titulo'    => traduz('Relat�rio de OS reincidente'),
			'descr'     => traduz('Relat�rio de OS reincidente.'),
			'codigo'    => 'AUD-1450'
		),
		array(
			'fabrica'   => array(11,172),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_produto_inspecao.php',
			'titulo'    => traduz('Relat�rio de Inspe��o de Produtos e Pe�as enviadas em Garantia'),
			'descr'     => traduz('Inspe��o de Produtos e Pe�as que foram enviadas em Garantia'),
			'codigo'    => 'AUD-1470'
		),	
		array(
			'fabrica'   => array(24),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_produtos_trocados_os.php',
			'titulo'    => traduz('Produtos Trocados na OS'),
			'descr'     => traduz('Produtos Trocados na OS'),
			'codigo'    => 'AUD-1480'
		),		
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['relatorio'],
			'link'      => 'integracao_power_bi.php',
			'titulo'    => traduz('Integra��o para Power BI'),
			'descr'     => traduz('Donwload do Relat�rio de Integra��o para power BI.'),
			'codigo'    => 'AUD-1480'
		),
		'link' => 'linha_de_separa��o'
	),
	'secao2' => array(
		'secao' => array(
			'link'     => '#',
			'titulo'   => traduz('AUDITORIA INTERVEN��O'),
			'fabrica_no' => array(20),
			//'noexpand' => true
		),
		array(
			'fabrica'   => array_merge(array($auditoria_unica), array(24, 161)),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_auditoria_status.php',
			'titulo'    => traduz('Auditoria de Ordem de Servi�o'),
			'descr'     => traduz('Ordem de servi�o aguardando auditoria.'),
			'codigo'    => 'AUD-1130'
		),
		array(

			'fabrica'   => ($nova_auditoria_unica) ? array($login_fabrica) : array(),
			'icone'     => $icone['relatorio'],
			'link'      => 'os_auditoria_unica.php',
			'titulo'    => traduz('Auditoria de Ordem de Servi�o'),
			'descr'     => traduz('Ordem de servi�o aguardando auditoria.'),
			'codigo'    => 'AUD-1140'
		),
		array(
			'fabrica'   => array(134),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_auditoria_status_os.php',
			'titulo'    => traduz('Auditoria de Ordem de Servi�o'),
			'descr'     => traduz('Ordem de servi�o aguardando auditoria.'),
			'codigo'    => 'AUD-2020'
        ),
        array(
            'fabrica' => array(120,201),
            'icone'     => $icone['relatorio'],
            'link'      => 'relatorio_auditoria_status_newmaq.php',
            'titulo'    => traduz('Auditoria de Ordem de Servi�o'),
            'descr'     => traduz('Ordem de servi�o aguardando auditoria.'),
            'codigo'    => 'AUD-2021'
        ),
		array(
			'fabrica'   => $fabrica_audita_todas_os,
			'icone'     => $icone['computador'],
			'link'      => 'os_auditar.php',
			'titulo'    => traduz('Auditoria Pr�via de OS'),
			'descr'     => traduz('Auditoria pr�via das OS para que possam ser analisadas antes de liberar as pe�as para o posto.'),
			'codigo'    => 'AUD-2010'
		),
		array(
			'fabrica'   => array(86),
			'icone'     => $icone['consulta'],
			'link'      => 'os_intervencao.php',
			'titulo'    => traduz('OS em Interven��o'),
			'descr'     => traduz('Consulta de relat�rio de OS em Interven��o.'),
			'codigo'    => 'AUD-2020'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_nf_reincidente.php',
			'titulo'    => traduz('Relat�rio de NF Retroativa a 60 dias'),
			'descr'     => traduz('Relat�rio de Nota Fiscal Retroativa a 60 dias.'),
			'codigo'    => 'AUD-2030'
		),
		array(
			'fabrica'   => $fabrica_os_intervencao,
			'fabrica_no' => $fabrica_auditoria_unica,
			'icone'     => $icone['computador'],
			'link'      => 'os_intervencao.php',
			'titulo'    => traduz('OS com Interven��o T�cnica'),
			'descr'     => traduz('OSs com interven��o t�cnica da f�brica. Autoriza ou cancela o pedido de pe�as do posto ou efetua a troca do produto.'),
			'codigo'    => 'AUD-2040'
		),
		array(
			'fabrica'   => array(50), // $vet_km
			'icone'     => $icone['computador'],
			'link'      => 'aprova_sem_peca_e_reincidente.php',
			'titulo'    => traduz('Auditoria da OS'),
			'descr'     => traduz('Auditoria de OS reincidente, sem pe�as ou com mais de 3 pe�as.'),
			'codigo'    => 'AUD-2050'
		),
		array(
			'fabrica'   => array(30,127,131,132,136,137,138,140,141,142,143,145,146,147,149,150,151), // $vet_km
			'icone'     => $icone['computador'],
			'link'      => 'aprova_sem_peca_e_reincidente.php',
			'titulo'    => $titulo_auditoria_reincidente_excente,
			'descr'     => traduz('Auditoria de OS\'s reincidentes ou pe�as excedentes.'),
			'codigo'    => 'AUD-2050'
		),
		array(
			'fabrica'   => array(104),
			'icone'     => $icone['computador'],
			'link'      => 'aprova_auditoria_geral.php',
			'titulo'    => traduz('Auditoria Geral de OSs'),
			'descr'     => traduz('Consultar ou auditar em uma �nica tela todas as OS que est�o<br />em interven��o ou auditoria.'),
			'codigo'    => 'AUD-2150'
		),
		array(
			'fabrica'   => array(30),
			'icone'     => $icone['computador'],
			'link'      => 'aprova_auditoria_geral.php',
			'titulo'    => traduz('Auditoria De Lista B�sica e Car�ncia de 90 dias'),
			'descr'     => traduz('Auditoria De Pe�as Maior do que a Lista B�sica e Car�ncia de 90 dias'),
			'codigo'    => 'AUD-2150'
		),
		array(
			'fabrica'   => array(42),
			'icone'     => $icone['computador'],
			'link'      => 'aprova_auditoria_geral.php',
			'titulo'    => traduz('Auditoria De Cortesia Comercial'),
			'descr'     => traduz('Auditoria De OS de produtos marcadas como cortesia'),
			'codigo'    => 'AUD-2150'
		),
		array(
			'fabrica'   => $fabrica_auditoria_previa,
			'icone'     => $icone['computador'],
			'link'      => 'auditoria_previa_posto.php',
			'titulo'    => traduz('Auditoria pr�via'),
			'descr'     => traduz('Auditoria pr�via para libera��o de pe�as em garantia.'),
			'codigo'    => 'AUD-2060'
		),
		array(
			'fabrica'   => $vet_os_reincidente,
			'fabrica_no' => array(104, 131, 132,136),
			'icone'     => $icone['computador'],
			'link'      => 'aprova_os_reincidente.php',
			'titulo'    => traduz('Auditoria de OS Reincidente'),
			'descr'     => traduz('Auditoria de OS Reincidente.'),
			'codigo'    => 'AUD-2070'
		),
		array(
			// HD 131735
			'fabrica'   => array(15),
			'icone'     => $icone['computador'],
			'link'      => 'auditoria_os_aberta_90_aprova.php',
			'titulo'    => traduz('Auditoria da OS aberta'),
			'descr'     => traduz('Auditoria de OS aberta mais de 60 dias.'),
			'codigo'    => 'AUD-2080'
		),
		array(
			'fabrica'   => array(30),
			'icone'     => $icone['computador'],
			'link'      => 'aprova_garantia_estendida.php',
			'titulo'    => traduz('Auditoria de OS com LGI'),
			'descr'     => traduz('Auditoria das OS com garantia estendida - LGI.'),
			'codigo'    => 'AUD-2090'
		),
		array(
			'fabrica'   => $vet_km,
			'fabrica_no'=> (isset($novaTelaOs)) ? array($login_fabrica) : array(30,91,120,201,138,141,142,143,144,145,146),
			'icone'     => $icone['computador'],
			'link'      => 'aprova_serie.php',
			'titulo'    => traduz('Auditoria na S�rie da OS'),
			'descr'     => traduz('Aprova ou reprova o n�mero de s�rie do produto e da OS.'),
			'codigo'    => 'AUD-2100'
		),
		array(
			'fabrica'   => array_merge($vet_km, array(114,115,116,117,128,129,131)),
			'fabrica_no'=> array(15,137,147),
			'icone'     => $icone['computador'],
			'link'      => 'aprova_km.php',
			'titulo'    => traduz('Auditoria de KM'),
			'descr'     => traduz('OS para aprova��o de KM do posto autorizado ao consumidor.'),
			'codigo'    => 'AUD-2110'
		),
		array(
			'fabrica'   => $fabrica_interv_serie,
			'icone'     => $icone['computador'],
			'link'      => 'aprova_serie.php',
			'titulo'    => traduz('Auditoria de OS por N�mero de S�rie'),
			'descr'     => traduz('Aprova ou reprova OS em Interven��o por n�mero de s�rie.'),
			'codigo'    => 'AUD-2120'
		),
		array(
			'fabrica'   => array(30),
			'icone'     => $icone['computador'],
			'link'      => 'aprova_reincidencia_nf.php',
			'titulo'    => traduz('Auditoria de OS com reincid�ncia'),
			'descr'     => traduz('Auditoria das OSs com reincid�ncia.'),
			'codigo'    => 'AUD-2130'
		),
		array(
			'fabrica'   => array(40,134),
			'icone'     => $icone['relatorio'],
			'link'      => 'os_intervencao_tres_ou_mais_pecas.php',
			'titulo'    => traduz('OSs com Interven��es com 3 pe�as ou mais'),
			'descr'     => traduz('OSs com interven��o com 3 pe�as ou mais.'),
			'codigo'    => 'AUD-2140'
		),
		array(
			'fabrica'   => array(72,114),
			'icone'     => $icone['relatorio'],
			'link'      => 'os_intervencao_tres_ou_mais_pecas.php',
			'titulo'    => traduz('OSs com Interven��es de pe�as excedentes'),
			'descr'     => traduz('OSs com Interven��es de pe�as excedentes.'),
			'codigo'    => 'AUD-2140'
		),
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_reincidente_britania.php',
			'titulo'    => traduz('Relat�rio de OSs reincidentes'),
			'descr'     => traduz('Relat�rio de Ordens de Servi�o Reincidentes.'),
			'codigo'    => 'AUD-2150'
		),
		array(
			'fabrica'       => array(30),
			'icone'         => $icone['computador'],
			'link'          => 'auditoria_os_judicial_troca.php',
			'titulo'        => traduz('Auditoria OS com Troca ou Processo Judicial'),
			'descr'         => traduz('Auditoria OS com Troca de Produto ou Processo Judicial.'),
			'codigo'    => 'AUD-2160'
		),
		array(
			'fabrica'   => array(151),
			'icone'     => $icone['computador'],
			'link'      => 'auditoria_peca_estoque.php',
			'titulo'    => traduz('OS com Interven��o de Troca de Pe�a'),
			'descr'     => traduz('OSs com interven��o de troca de pe�a. Autoriza ou cancela a auditoria da pe�a.'),
			'codigo'    => 'AUD-2160'
		),
		array(
			'fabrica'   => array(85,146),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_pedidos.php',
			'titulo'    => traduz('Auditoria de OS com Pedido em Garantia'),
			'descr'     => traduz('Aprova ou reprova OS em Interven��o com pedido em garantia.'),
			'codigo'    => 'AUD-2170'
		),
		array(
			'fabrica'   => $fabrica_valores_adicionais,
			'fabrica_no'=> ($login_fabrica != 139 && (isset($auditoria_unica) OR isset($fabrica_usa_valor_adicional)) ? array($login_fabrica) : array(125,152,158,163,180,181,182,203)), // 139 temporario
			'icone'     => $icone['computador'],
			'link'      => 'auditoria_os_valores_adicionais.php',
			'titulo'    => traduz('Auditoria de Valores Adicionais'),
			'descr'     => traduz('OS para aprova��o de valores adicionais na os informados pelo Posto Autorizado.'),
			'codigo'    => 'AUD-2180'
		),
		array(
			'fabrica'   => array(151),
			'icone'     => $icone['computador'],
			'link'      => 'auditoria_conferencia_recebimento.php',
			'titulo'    => traduz('Auditoria de Confer�ncia de Recebimento'),
			'descr'     => traduz('OS com pedido faturado que aguarda aprova��o/reprova��o da auditoria.'),
			'codigo'    => 'AUD-2190'
		),
		array(
			'fabrica'   => array(122),
			'icone'     => $icone['relatorio'],
			'link'      => 'os_intervencao_tres_ou_mais_pecas.php',
			'titulo'    => traduz('OSs com Interven��es com 5 pe�as ou mais'),
			'descr'     => traduz('OSs com interven��o com 5 pe�as ou mais.'),
			'codigo'    => 'AUD-2190'
		),
		array(
			'fabrica'   => array(91),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_os_produto_critico.php',
			'titulo'    => traduz('OS com interven��o de produto cr�tico'),
			'descr'     => traduz('OS com interven��o de produto cr�tico.'),
			'codigo'    => 'AUD-2200'
		),
		array(
			'fabrica'   => array(101,141,144),
			'icone'     => $icone['computador'],
			'link'      => 'auditoria_os_troca_produto.php',
			'titulo'    => traduz('OS com troca de produto'),
			'descr'     => traduz('Auditoria de OSs em que o posto informou que necessita da troca de produto.'),
			'codigo'    => 'AUD-2210'
		),
		array(
			'fabrica'   => array(15),
			'icone'     => $icone['computador'],
			'link'      => 'aprova_km_posto.php',
			'titulo'    => traduz('Auditoria de KM por Posto'),
			'descr'     => traduz('Auditoria de KM em OSs separadas por Posto.'),
			'codigo'    => 'AUD-2220'
		),
		array(
			'fabrica'   => array(145),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_solicitacao_peca_produto.php',
			'titulo'    => traduz('Auditoria de Solicita��o de Pe�a/Produto '),
			'descr'     => traduz('Auditoria de OS com Solicita��o de Pe�a/Produto.'),
			'codigo'    => 'AUD-2230'
		),
		array(
			'fabrica'   => array(138),
			'icone'     => $icone['computador'],
			'link'      => 'intervencao_solucao.php',
			'titulo'    => traduz('OS com Interven��o por solu��o'),
			'descr'     => traduz('OSs com interven��o de outros atendimentos com carga de g�s.'),
			'codigo'    => 'AUD-2240'
		),
		array(
			'fabrica'   => array(138),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_os_fora_garantia.php',
			'titulo'    => traduz('Auditoria de OS fora de garantia'),
			'descr'     => traduz('Auditoria de OS de visita t�cnica com produto fora de garantia.'),
			'codigo'    => 'AUD-2250'
		),
		array(
			'fabrica'   => array(145),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_os_construtora.php',
			'titulo'    => traduz('Auditoria de OS com Construtora '),
			'descr'     => traduz('Auditoria de OS com Construtora.'),
			'codigo'    => 'AUD-2260'
		),
		array(
			'fabrica'   => array(131),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_pedido_peca_produto.php',
			'titulo'    => traduz('Auditoria de Pedido de Pe�a/Produto '),
			'descr'     => traduz('Auditoria de Pedido de Pe�as/Produto.'),
			'codigo'    => 'AUD-2270'
		),
        array(			/* HD-3574824 Liberado essa tela para a f�brica 122*/
            'fabrica'   => (in_array($login_fabrica, array( 2,3,6,11,14,25,35,45,51,72)) OR $login_fabrica > 79),
            'fabrica_no'=> array_merge($fabricas_contrato_lite, array(117,121,120,201,124,124,126,127,128,129,131,132,136,137,138,140,141,142,143,144,145,146,148,149,150,151,152,154,156,157,158,166,167,171,175,176,177,178,180,181,182,190,193,195,198)),
            'icone'     => $icone['computador'],
            'link'      => 'pedido_intervencao.php',
            'titulo'    => traduz('Pedido com Interven��o'),
            'descr'     => (in_array($login_fabrica, [35])) ? 
            				traduz('Autorizar ou Cancelar Pedidos de pe�as dos Postos.') 
							: traduz('Pedido com pe�as cr�ticas. Autoriza ou Cancela Pedidos de Venda dos Postos.'),
            'codigo'    => 'AUD-2030'
        ),
		'link' => 'linha_de_separa��o'
	),
	'secaoLGR' => array(
		'secao' => array(
			'link'       => '#',
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(81,136,143,148,150,160,164,176,191)),
			'titulo'     => traduz('AUDITORIA PE�AS / LGR'),
			//'noexpand' => true
		),
		array(
			// HD 138788
			'fabrica'   => array(104,105,117,124,141,144,146),
			'icone'     => $icone['computador'],
			'link'      => 'pedido_intervencao.php',
			'titulo'    => traduz('Pedido de Pe�a com Interven��o'),
			'descr'     => traduz('Autoriza ou Cancela Pedidos de Venda dos Postos.'),
			'codigo'    => 'AUD-3010'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'auditoria_os_fechamento_blackedecker.php',
			'titulo'    => traduz('Auditoria de pe�as trocadas em garantia'),
			'descr'     => traduz('Faz pesquisas nas Ordens de Servi�os previamente aprovadas em extrato.'),
			'codigo'    => 'AUD-3030'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_field_call_rate_pecas_defeitos.php',
			'titulo'    => traduz('Field Call Rate de Pe�as'),
			'descr'     => traduz('Relat�rio de quebras por defeitos das pe�as.'),
			'codigo'    => 'AUD-3040'
		),
		array(
			'fabrica'   => array(11,172),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_devolucao_obrigatoria.php',
			'titulo'    => traduz('Devolu��o Obrigat�ria'),
			'descr'     => traduz('Pe�as que devem ser devolvidas para a F�brica constando em Ordens de Servi�os.'),
			'codigo'    => 'AUD-3050'
		),
		array(
			'disabled'  => true,
			'fabrica'   => array(81,114),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_devolucao_obrigatoria_novo.php',
			'titulo'    => traduz('Relat�rio de Devolu��o Obrigat�ria'),
			'descr'     => traduz('Pe�as que devem ser devolvidas para a F�brica constando em Ordens de Servi�os.'),
			'codigo'    => 'AUD-3060'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_peca_devolvida.php',
			'titulo'    => traduz('Devolu��o de Pe�as dos Postos'),
			'descr'     => traduz('Relat�rio de confer�ncia das pe�as devolvidas pelos postos.'),
			'codigo'    => 'AUD-3070'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(3,20,81,137,145,149,177,191)),
			'icone'      => $icone['consulta'],
			'link'       => 'extrato_posto_devolucao_controle.php',
			'titulo'     => traduz('Controle de Notas de Devolu��o - LGR'),
			'descr'      => traduz('Consulte ou confirme Notas Fiscais de Devolu��o.'),
			'codigo'     => 'AUD-3080'
		),
		array(
			'fabrica' 	 => array(177),
			'icone'      => $icone['consulta'],
			'link'       => 'extrato_posto_devolucao_controle_anauger.php',
			'titulo'     => traduz('Controle de Notas de Devolu��o - LGR'),
			'descr'      => traduz('Consulte ou confirme Notas Fiscais de Devolu��o.'),
			'codigo'     => 'AUD-3080'
		),
		array(
			'fabrica' => array(3),
			'icone'      => $icone['consulta'],
			'link'       => 'extrato_posto_devolucao_controle_novo_lgr.php',
			'titulo'     => traduz('Controle de Notas de Devolu��o - LGR'),
			'descr'      => traduz('Consulte ou confirme Notas Fiscais de Devolu��o.'),
			'codigo'     => 'AUD-3080'
		),
        //HD 138788
        array(
            'fabrica_no' => array_merge($fabricas_contrato_lite, array(20,81,137,149,191)),
            'icone'      => $icone["relatorio"],
            'link'       => 'relatorio_lgr.php',
            'titulo'     => ($login_fabrica == 91) ? traduz("Relat�rio de Extratos Pendentes") : traduz("Relat�rio do N�o Preenchimento do LGR"),
            'descr'      => traduz('Relat�rio dos Postos que n�o preencheram o LGR.'),
            'codigo'     => 'AUD-3090'
        ),
        array(
            'fabrica' => $fabrica_lgr_bateria,
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_devolucao_bateria.php',
            'titulo'  => traduz('Relat�rio de Devolu��o das baterias'),
            'descr'   => traduz('Relat�rio de Devolu��o das baterias.'),
            'codigo'  => 'AUD-3100'
        ),
        // HD 318173
        array(
            'fabrica' => array(51,98),
            'icone'   => $icone["relatorio"],
            'link'    => 'lgr_vistoria_itens_new.php',
            'titulo'  => traduz('Relat�rio de Pe�as Retorn�veis'),
            'descr'   => traduz('Relat�rio que indica as Pe�as Reton�veis do Extrato.'),
            'codigo'  => 'AUD-3110'
		),

        // HD 708844
        array(
            'fabrica' => array(94,104,105,106,115,116,117,142,149,151),
            'icone'   => $icone["relatorio"],
            'link'    => 'lgr_vistoria_itens_new.php',
            'titulo'  => traduz('Relat�rio de Pe�as para Inspe��o'),
            'descr'   => traduz('Relat�rio que indica as Pe�as que precisam de inspe��o no posto autorizado.'),
            'codigo'  => 'AUD-3120'
        ),
        array(
            'fabrica'   => array(11,24,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'auditoria_pecas_pendentes.php',
            'titulo'    => traduz('Rela��o de Pe�as Pendentes aos postos'),
            'descr'     => traduz('Relat�rio de pe�as de pedidos que ainda n�o foram atendidas pelo fabricante.(por posto).'),
            'codigo'    => 'AUD-3130'
        ),
        array(
            'fabrica'   => array(11,172),
            'icone'     => $icone["relatorio"],
            'link'      => 'auditoria_pecas_pendentes_pecas.php',
            'titulo'    => traduz('Pe�as Pendentes por Estoque'),
            'descr'     => traduz('Relat�rio de pe�as que ainda nao foram atendidas (por pe�as).'),
            'codigo'    => 'AUD-3140'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_pedido_anistia.php',
            'titulo'    => traduz('Relat�rio de OSs abertas h� mais de 150 dias com pedidos de pe�as atendidos'),
            'descr'     => traduz('OS abertas h� mais de 150 dias com pedidos de pe�as atendidos h� mais de 20 dias.'),
            'codigo'    => 'AUD-3150'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_pedido_anistia_comunicados.php',
            'titulo'    => traduz('Relat�rio de OSs abertas h� mais de 150 dias com comunicado ao posto'),
            'descr'     => traduz('OS abertas h� mais de 150 dias, com pedidos de pe�as atendidos e com comunicado ao posto.'),
            'codigo'    => 'AUD-3160'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_pedido_anistia_excluidas.php',
            'titulo'    => traduz('Relat�rio de OSs abertas h� mais de 150 dias exclu�das'),
            'descr'     => traduz('OS abertas h� mais de 150 dias, com pedidos de pe�as atendidos e exclu�das.'),
            'codigo'    => 'AUD-3170'
        ),
        array(
            'fabrica'   => $fabrica_vistoria_lgr,
            'icone'     => $icone["relatorio"],
            'link'      => 'lgr_vistoria.php',
            'titulo'    => traduz('Vistoria de Pe�as'),
            'descr'     => traduz('Vistoria de pe�as dos postos em um per�odo de 90 dias.'),
            'codigo'    => 'AUD-3180'
        ),
        array(
            'fabrica'   => array_merge($fabrica_vistoria_lgr, array(137)),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_vistoria_pecas.php',
            'titulo'    => traduz('Relat�rio de Vistoria de Pe�as'),
            'descr'     => traduz('Relat�rio de vistoria de pe�as por per�odo.'),
            'codigo'    => 'AUD-3190'
        ),
        array(
            'fabrica' => array(145),
            'icone'   => $icone["consulta"],
            'link'    => 'lgr_conferencia_pesquisa.php',
            'titulo'  => traduz('Controle de Notas de Devolu��o - LGR'),
            'descr'   => traduz('Consulte ou confirme Notas Fiscais de Devolu��o.'),
            'codigo'  => 'AUD-3200'
        ),
        array(
            'fabrica' => array(145),
            'icone'   => $icone["consulta"],
            'link'    => 'lgr_parecer_tecnico_pesquisa.php',
            'titulo'  => traduz('Parecer T�cnico - LGR'),
            'descr'   => traduz('Parecer t�cnico  das Notas Fiscais de Devolu��o.'),
            'codigo'  => 'AUD-3210'
        ),
        array(
            'fabrica' => array(145),
            'icone'   => $icone["consulta"],
            'link'    => 'relatorio_lgr_conferencia.php',
            'titulo'  => traduz('Relat�rio de parecer t�cnico das notas de devolu��es - LGR'),
            'descr'   => traduz('Relat�rio de parecer t�cnico das notas de devolu��es - LGR.'),
            'codigo'  => 'AUD-3220'
        ),
        array(
            'fabrica' => array(20),
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_pecas_devolucao.php',
            'titulo'  => traduz('Relat�rio de pe�as para devolu��es'),
            'descr'   => traduz('Relat�rio de verifica��o regional por amostragem de pe�as marcadas para devolu��o obrigat�ria.'),
            'codigo'  => 'AUD-3220'
        ),
        array(
            'fabrica' => array(94),
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_peca_pendente_lgr.php',
            'titulo'  => traduz('Relat�rio de pe�as pendentes para LGR'),
            'descr'   => traduz('Relat�rio de pe�as que est�o pendentes para entrar no LGR.'),
            'codigo'  => 'AUD-3230'
        ),

        'link' => 'linha_de_separa��o'
    ),
    'secao4' => array(
        'secao' => array(
            'link'     => '#',
            'titulo'   => traduz('AUDITORIA FINANCEIRO'),
            'fabrica'   => array(1,24)
            //'noexpand' => true
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_media_pagamento.php',
            'titulo'    => traduz('Rela��o de pagamentos'),
            'descr'     => traduz('Relat�rio demostrativo de dias para pagamento de notas de cr�dito.'),
            'codigo'    => 'AUD-4010'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_media_pagamento_pecas.php',
            'titulo'    => traduz('Relat�rio de 1,5 %'),
            'descr'     => traduz('Relat�rio demostrativo de dias para pagamento de notas de cr�dito com valor de pe�as.'),
            'codigo'    => 'AUD-4020'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'gasto_por_revenda.php',
            'titulo'    => traduz('Gastos por Revenda'),
            'descr'     => traduz('Mostra as revendas com maiores e menores gastos em garantia.'),
            'codigo'    => 'AUD-4030'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'gasto_por_posto_todos_blackedecker.php',
            'titulo'    => traduz('Gastos por Posto (todos)'),
            'descr'     => traduz('Mostra os todos os gastos em garantia de todos os postos.'),
            'codigo'    => 'AUD-4040'
        )
    ),
    'secao5' => array(
        'secao' => array(
            'link'     => '#',
            'titulo'   => traduz('AUDITORIA OUTROS'),
            'fabrica_no' => $fabrica_auditoria_outros
		),
		array(
			'fabrica'   => array(168),
			'icone'     => $icone['relatorio'],
			'link'      => 'cadastro_posto_bloqueado.php',
			'titulo'    => traduz('Cadastro de Posto com Bloqueios'),
			'descr'     => traduz('Relat�rio de Postos Bloqueiados/Desbloqueados'),
			'codigo'    => 'AUD-5190'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'black_quebra_acumulado.php',
			'titulo'    => traduz('Vis�o geral por produto e M.O.'),
			'descr'     => traduz('Relat�rio de vis�o geral por produto com valores de pe�as e m�o-de-obra.'),
			'codigo'    => 'AUD-5010'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'rel_codigo_fabricacao.php',
			'titulo'    => traduz('Relat�rio do C�digo de Fabrica��o'),
			'descr'     => traduz('Ocorr�ncias de codigo de fabrica��o em OS.'),
			'codigo'    => 'AUD-5020'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'rel_visao_mix_total.php',
			'titulo'    => traduz('Vis�o geral por produto'),
			'descr'     => traduz('Relat�rio geral por produto.'),
			'codigo'    => 'AUD-5030'
		),
		array(
			'fabrica'   => $fabricas_autocredenciamento,
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_autocredenciamento.php',
			'titulo'    => traduz('Relat�rio Auto-Credenciamento'),
			'descr'     => traduz('Relat�rio de Postos cadastrados no Auto-Credenciamento.'),
			'codigo'    => 'AUD-5040'
		),
		array(
			'fabrica'   => array(3),
			'icone'     => $icone['computador'],
			'link'      => 'distribuidor_desempenho.php',
			'titulo'    => traduz('Desempenho Distribuidores'),
			'descr'     => traduz('Avalia��o de desempenho dos principais distribuidores.'),
			'codigo'    => 'AUD-5050'
		),
		array(
			'fabrica'   => array(14),
			'icone'     => $icone['cadastro'],
			'link'      => 'documento_cadastro.php',
			'titulo'    => traduz('Cadastro de Documentos de Supervisor T�cnico'),
			'descr'     => traduz('Mostra todos os documentos cadastrados para os Supervisores T�cnicos.'),
			'codigo'    => 'AUD-5060'
		),
		array(
			'fabrica'   => array(14),
			'icone'     => $icone['relatorio'],
			'link'      => 'documento_consulta.php',
			'titulo'    => traduz('Documentos de Supervisor T�cnico'),
			'descr'     => traduz('Mostra todos os documentos dos supervisores que est�o cadastrados.'),
			'codigo'    => 'AUD-5070'
		),
		array(
			'fabrica'   => array(81,114),
			'icone'     => $icone['consulta'],
			'link'      => 'troca_revenda_baixa.php',
			'titulo'    => traduz('Autoriza��es de Devolu��es de Vendas'),
			'descr'     => traduz('Consulta e Baixas das Autoriza��es de Devolu��es de Vendas aprovadas por falta de pe�as.'),
			'codigo'    => 'AUD-5080'
		),
		array(
			'fabrica'   => array(11,72,172),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_troca_produto_total.php',
			'titulo'    => traduz('Relat�rio Troca de Produto'),
			'descr'     => traduz('Relat�rio de produto trocado e arquivo .XLS.'),
			'codigo'    => 'AUD-5090'
		),
		//HD 138788
		array(
			'fabrica'   => array(81,114),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_atendimento_sac.php',
			'titulo'    => traduz('Relat�rio dos Atendimentos'),
			'descr'     => traduz('Relat�rio que indica todos os atendimentos efetuados pelo CALL-CENTER (independente do meio de comunica��o).'),
			'codigo'    => 'AUD-5100'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'gera_txt_peca_garantia_black.php',
			'titulo'    => traduz('Gera TXT de Garantia'),
			'descr'     => traduz('Relat�rio em TXT para Engenharia de OSs em garantia.'),
			'codigo'    => 'AUD-5110'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['consulta'],
			'link'      => 'os_troca_parametros.php',
			'titulo'    => traduz('Consulta OS Troca'),
			'descr'     => traduz('Consulta de Ordem de Servi�o de Troca.'),
			'codigo'    => 'AUD-5120'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_serie_locadora.php',
			'titulo'    => traduz('Relat�rio OS N�mero de S�rie'),
			'descr'     => traduz('Relat�rio que mostra OS Cortesia com s�rie da locadora.'),
			'codigo'    => 'AUD-5130'
		),
		array(
			'fabrica'   => array(10),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_banner.php',
			'titulo'    => traduz('Relat�rio de Banner'),
			'descr'     => traduz('Relat�rio de Postos cadastrados no pedido de banner.'),
			'codigo'    => 'AUD-5140'
		),
		array(
			'fabrica'   => array(86),
			'icone'     => $icone['consulta'],
			'link'      => 'relatorio_os_qtde.php',
			'titulo'    => traduz('Quantidade OS digitada'),
			'descr'     => traduz('Relat�rio que mostra a quantidade de OS digitadas por per�odo.'),
			'codigo'    => 'AUD-5150'
		),
		array(
			// HD 138788
			'fabrica'   => array(140,142),
			'icone'     => $icone['computador'],
			'link'      => 'pedido_intervencao.php',
			'titulo'    => traduz('Pedido de Pe�a com Interven��o'),
			'descr'     => traduz('Autoriza ou Cancela Pedidos de Venda dos Postos.'),
			'codigo'    => 'AUD-5170'
		),
		array(
			// HD 138788
			'fabrica'   => array(138,173),
			'icone'     => $icone['computador'],
			'link'      => 'pedido_intervencao_new.php',
			'titulo'    => traduz('Pedido de Pe�a com Interven��o'),
			'descr'     => traduz('Autoriza ou Cancela Pedidos de Venda dos Postos.'),
			'codigo'    => 'AUD-5170'
		),
		array(
			// HD 1939119
			'fabrica'   => array(35),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_peca_extrato.php',
			'titulo'    => traduz('Relat�rio de Pe�as X Extratos'),
			'descr'     => traduz(' Acesso a Extratos de acordo com a Pe�a.'),
			'codigo'    => 'AUD-5180'
		),
		'link' => 'linha_de_separa��o'
	),
	'secaoBOSCH' => array(
		'secao' => array(
			'link'     => '#',
			'fabrica'  => array(20),
			'titulo'   => 'AUDITORIA AL',
			//'noexpand' => true
		),
		/* Bosch */
		array(
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_valor_pecas_mobra.php',
			'titulo'    => traduz('Qtde OS, valor de Pe�as e M�o de Obra'),
			'descr'     => traduz('Relat�rio que consta as quantidade de OSs digitadas, o valor de pe�as e m�o de obra filtrado por pa�s.'),
			'codigo'    => 'AUD-AL10'
		),
		array(
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_colombia.php',
			'titulo'    => traduz('Relat�rio Col�mbia'),
			'descr'     => traduz('Relat�rio que consta as quantidade de OSs digitadas, o valor de pe�as e m�o de obra.'),
			'codigo'    => 'AUD-AL20'
		),
		array(
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_garantias.php',
			'titulo'    => traduz('Relat�rio Garant�as'),
			'descr'     => traduz('Relat�rio Garant�as por pa�s das OSs em extrato, que consta o total de pe�as e m�o de obra.'),
			'codigo'    => 'AUD-AL30'
		),
		array(
			'fabrica'   => array(20),
			'icone'     => $icone['relatorio'],
			'link'      => 'relatorio_os_status.php',
			'titulo'    => traduz('Relat�rio de OS Recusadas e Acumuladas'),
			'descr'     => traduz('Relat�rio de ordem de servi�os que foram recusadas ou acumuladas do extrato.'),
			'codigo'    => 'AUD-AL40'
		),
	),
    'secaoBlack' => array(
        'secao' => array(
            'link'     => '#',
            'titulo'   => traduz('AUDITORIA OS'),
            'fabrica'   => array(1),
            //'noexpand' => true
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_os_troca.php',
            'titulo'    => traduz('Aprova��o de OS Troca'),
            'descr'     => traduz('Manuten��o de Ordem de Servi�o de Troca.'),
            "codigo"    => 'AUD-6100'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_os_troca_interna.php',
            'titulo'    => traduz('Aprova��o de OS Troca (Interno)'),
            'descr'     => traduz('Manuten��o de Ordem de Servi�o de Troca internamente.'),
            "codigo"    => 'AUD-6110'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_exclusao.php',
            'titulo'    => traduz('Aprova��o de OS Exclu�da'),
            'descr'     => traduz('Aprove de Ordem de Servi�o Exclu�da.'),
            "codigo"    => 'AUD-6120'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone['consulta'],
            'link'      => 'aprova_pedido_sedex.php',
            'titulo'    => traduz('Aprova Pedido SEDEX'),
            'descr'     => traduz('Relat�rio que mostra os pedidos SEDEX e tem a op��o de aprovar ou reprovar.'),
            'codigo'    => 'AUD-6130'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone['relatorio'],
            'link'      => 'relatorio_auditoria_pedido_sedex.php',
            'titulo'    => traduz('Aprova Pedido Acima da Demanda'),
            'descr'     => traduz('Mostra os pedidos em que os itens ultrapassaram a Demanda'),
            'codigo'    => 'AUD-6140'
        ),
        array(
			// HD 4140484
			'fabrica'   => array(1),
			'icone'     => $icone['relatorio'],
			'link'      => 'pedidos_dewalt_rental.php',
			'titulo'    => traduz('Auditoria de Pedidos Dewalt Rental'),
			'descr'     => traduz('Aprova��o/Reprova��o de pedidos Dewalt Rental.'),
			'codigo'    => 'AUD-6150'
		),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone['relatorio'],
            'link'      => 'aprova_auditoria_custo_reparo.php',
            'titulo'    => traduz('Auditoria de Pe�as X Custo Produto'),
            'descr'     => traduz('Auditoria da % de Pe�as x Custo Produto.'),
            'codigo'    => 'AUD-6160'
        ),
        array(
            'fabrica' => array(1),
            'icone'   => $icone["computador"],
            'link'    => 'auditoria_garantia_peca.php',
            'titulo'  => traduz('Aprova��o de OS de devolu��o de pe�as'),
            'descr'   => traduz('Aprova��o de Ordens de Servi�o abertas para devolu��o de pe�as que apresentaram problema de fabrica��o dentre os 90 dias ap�s a venda.'),
            'codigo'  => 'AUD-6170'
        ),
	),
	'link' => 'linha_de_separa��o'
);

