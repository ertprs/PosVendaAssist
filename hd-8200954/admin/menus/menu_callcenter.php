<?php
include_once 'funcoes.php';
//error_reporting(E_ERROR);

if ($admin_e_promotor_wanke) { //HD 685194
    return include(__DIR__ . '/menu_promotor_wanke.php');
}

$fabrica_admin_anexaNF            = array(43, 45, 80); // Acesso a nota_foto_cadastro
$fabrica_relatorio_os_aberto      = array(43, 45, 80);
$verifica_ressarcimento_troca     = array(81, 114);
$fabrica_callcenter_deshabilitado = array();
if($replica_einhell){
	$fabricas_replica_einhell[] = $login_fabrica;
}else{
	$fabricas_replica_einhell[] = array();
}

// MLG movi $fabrica_hd_posto e $fabrica_at_regiao ao autentica_admin

// 159888
$fabrica_movimiento_estoque_posto = array(15,24,30,52,120,201,134);
$fabrica_estoque_cfop             = array(3, 15, 30);
$fabrica_cancela_pedido_massivo   = array(151);

//define se irá usar a nova tela para abertura de ordem de serviço
$fabrica_nova_OS = isset($novaTelaOs) ? array($login_fabrica) : array();
$os_cadastro_new = (count($fabrica_nova_OS) or in_array($login_fabrica, array(52))) ? array($login_fabrica) : array();
$posto_estoque   = ($usaEstoquePosto == true) ? array($login_fabrica) : array(35,50,72);

$os_cadastro_revisao  = array(145);

if (in_array($login_fabrica,array(6,50,88,121,124)) || ($login_fabrica >= 129 && !in_array($login_fabrica, array(163, 172))) || $telecontrol_distrib == 't') {
	$fabrica_upload_preco = $login_fabrica;
}

$fabrica_NAO_aprova_KM = array_merge(
    $fabricas_contrato_lite,
    array(3,25,50,81,86,95,114,122,123,124,126,127,128,129,134,131,132,136,137,139,140,141,144),
    $fabrica_nova_OS
);

$titulo_CCT_0190 = 'Help-Desk Posto Autorizado';
$desc_CCT_0190   = traduz('Consulta de Chamados Abertos por Posto.');

if (in_array($login_fabrica, [198])) {
    $titulo_CCT_0190 = traduz('Help-Desk Interno'); 
    $desc_CCT_0190   = traduz('Consulta de Chamados Abertos Internamente.');
} else if ((in_array($login_fabrica, [30,35]) || $login_fabrica > 150 and $login_fabrica <> 172) || ($login_fabrica == 72 && $helpdeskPostoAutorizado)) {
    $titulo_CCT_0190 = traduz('Help-Desk Posto Autorizado'); 
} else {
    $titulo_CCT_0190 = traduz('Consulta Chamados');
}

$fabrica_upload_lista_basica = array(147, 149, 152, 160,180,181,182);
if($replica_einhell) $fabrica_upload_lista_basica[] = $login_fabrica;

if (!isset($novaTelaOs)){
	// HD 706867
	$sql = "SELECT fabrica
        	FROM tbl_fabrica
		WHERE fabrica = $login_fabrica
        	AND fatura_manualmente";
	$res = pg_query($con, $sql);

	$fabrica_fatura_manualmente = (pg_num_rows($res)>0);
}

// Aviso comunicado
if ($login_fabrica ==50) { // HD 58256
    $sql = "SELECT comunicado
              FROM tbl_comunicado
             WHERE fabrica = $login_fabrica
               AND data > CURRENT_DATE - INTERVAL '3 DAYS' ;";
    $com_style = (pg_num_rows(@pg_query($con, $sql))>0) ? ' style="color:red"':'';
}

/*
    hd-1149884 -> Para as fábricas que tiverem o parâmetro adicional fabrica_padrao='t', a tela:
        admin/os_consulta_procon.php
    Não serão mais utilizadas.
*/

if ($fabrica_padrao=='t'){
    $arr_fabrica_padrao = array($login_fabrica);
}

// Menu CALLCENTER

/*
 *
 * Bloqueio de tela, Callcenter (Implantação Mondial)
 * Data: 20/11/2015
 * INICIO
 *
 */

$fabrica_atendimentos = array();

if (in_array($login_fabrica, array(151,169,170))) {

    $sqlAdmIntervensor = "SELECT intervensor,callcenter_supervisor FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$login_admin};";
    $resAdminIntervensor = pg_query($con, $sqlAdmIntervensor);

    $intervensor = pg_fetch_result($resAdminIntervensor, 0, intervensor);
    $callcenter_supervisor = pg_fetch_result($resAdminIntervensor, 0, callcenter_supervisor);
    if ($login_fabrica == 151) {
        $fabrica_atendimentos = array($login_fabrica);
    }else if($callcenter_supervisor == "t" AND in_array($login_fabrica, array(169,170))){
        $fabrica_atendimentos = array($login_fabrica);
    }
}

 /*
 *
 * Bloqueio de tela, Callcenter (Implantação Mondial)
 * Data: 20/11/2015
 * FIM
 *
 */

/**
* Interação na Ordem de Serviço
*/

$array_interacao_os = array(11,14,24,30,35,40,45,50,51,52,72,74,80,81,85,86,90,91,96,101,104,114,123,126,127,131,132,136,172);

if ($login_fabrica >= 137) {
    $array_interacao_os = array($login_fabrica);
}

if ($login_fabrica == 3) {
    if (preg_match("/info|\*/", $login_privilegios)) {
        $array_interacao_os = array($login_fabrica);
    }
}

if (!in_array($login_fabrica, $os_cadastro_new)) {
    $link3010 = 'os_cadastro.php';
} else if ($login_fabrica == 178) {
    $link3010 = 'cadastro_os_revenda.php';
} else {
    $link3010 = 'cadastro_os.php';
}

/**
* FIM Interação na Ordem de Serviço
*/
$link_historioco = ($login_fabrica == 151) ? "consulta_historico_atendimento.php" : "consulta_atendimento_aquarius.php";

return array(
    'secao0' => array(
        'secao' => array(
            'link'  => '#',
            'titulo' => strtoupper(traduz('CALL-CENTER')) . iif(($login_fabrica == 6), traduz(' NOVO'))
        ),
        array(
            'disabled'  => (!$admin_consulta_os),
            "icone"     => $icone["consulta"],
            "titulo"    => traduz('Consulta Ordens de Serviço'),
            "link"      => 'os_consulta_lite.php',
            "descr"     => traduz('Consulta OS Lançadas'),
            "codigo" => 'CCT-0010'
        ),
        array(
            'disabled'  => (!$admin_consulta_os),
            "link"      => 'linha_de_separação',
        ),
        array(
            'fabrica'   => array(25),
            'icone'     => $icone["cadastro"],
            'link'      => 'callcenter_interativo_new.php',
            'titulo'    => traduz('Atendimento Interativo'),
            'descr'     => traduz('Cadastro de atendimento do Call-Center Interativo'),
            "codigo" => 'CCT-0020'
        ),
        array(
            'fabrica'   => array(25),
            'icone'     => $icone["consulta"],
            'link'      => 'callcenter_parametros_interativo.php',
            'titulo'    => traduz('Consulta Atendimentos Call-Center'),
            'descr'     => traduz('Consulta atendimentos já lançados'),
            "codigo" => 'CCT-0030'
        ),
        array(
            'fabrica'   => array(25),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_pendente_interativo.php',
            'titulo'    => traduz('Pendência Call-Center'),
            'descr'     => traduz('Exibe todos os atendimentos do Call-Center com pendência.'),
            "codigo" => 'CCT-0040'
        ),
        array(
            'fabrica'   => array(25),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_consulta_atendimento.php',
            'titulo'    => traduz('Relatório Call-Center'),
            'descr'     => traduz('Relatório de callcenter simples (permite baixar o relatório em XLS).'),
            "codigo"    => 'CCT-0050'
        ),
        array(
            'fabrica'   => array(6),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastra_callcenter.php',
            'titulo'    => traduz('Cadastra Atendimento Call-Center'),
            'descr'     => traduz('Cadastro de atendimento do Call-Center'),
            "codigo" => 'CCT-0060'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["cadastro"],
            'link'      => 'conferencia_integracao.php',
            'titulo'    => traduz('Monitor de Ordens de Serviço'),
            'descr'     => traduz('Monitor de Ordens de Serviço'),
            "codigo" => 'CCT-0065'
        ),
        array(
            'fabrica_no'=> array(25, 95),
            'icone'     => $icone["cadastro"],
            'link'      => 'callcenter_interativo_new.php',
            "codigo"    => 'CCT-0070',
            'titulo'    => traduz('Cadastra Atendimento Call-Center') .  iif(($login_fabrica == 6), traduz(' NOVO')),
            'descr'     => traduz('Cadastro de atendimento do Call-Center')
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'pre_os_britania_simplificada.php',
            'titulo'    => traduz('Cadastro de PRÉ OS'),
            'descr'     => traduz('Cadastrar Pré Ordem de serviço para Posto Autorizado'),
            "codigo" => 'CCT-0080'
        ),
        array(
            'fabrica_no'=> array(25, 95),
            'icone'     => $icone["consulta"],
            'link'      => 'callcenter_parametros_new.php',
            'titulo'    => traduz('Consulta Atendimentos Call-Center'),
            'descr'     => traduz('Consulta atendimentos já lançados'),
            "codigo" => 'CCT-0090'
        ),
        array(
            'fabrica'   => array($fabricas_contrato_lite),
            'icone'     => $icone["cadastro"],
            'link'      => 'faq_situacao.php',
            'titulo'    => traduz('Perguntas Frequentes - FAQ'),
            'descr'     => traduz('Cadastro de  perguntas e respostas sobre um determinado produto'),
            "codigo" => 'CCT-0100'
        ),
        array(
            'fabrica'   => array($fabricas_contrato_lite),
            'icone'     => $icone["cadastro"],
            'fabrica_no' => array(137),
            'link'      => 'callcenter_pergunta_cadastro.php',
            'titulo'    => traduz('Cadastro de Perguntas do Callcenter'),
            'descr'     => traduz('Para que as frases padrões do callcenter sejam alteradas.'),
            "codigo" => 'CCT-0110'
        ),
        array(
            'fabrica_no'=> array(25, 95),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_pendente.php',
            'titulo'    => traduz('Pendência Call-Center'),
            'descr'     => traduz('Exibe todos os atendimentos do Call-Center com pendência.'),
            "codigo" => 'CCT-0120'
        ),
        // HD 674943
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["computador"],
            'link'      => 'hd_chamado_postagem.php',
            'titulo'    => traduz('Autorização de Postagem'),
            'descr'     => traduz('Consulta, Autorização e Reprovação de postagens solicitadas pelos atendentes do CallCenter'),
            "codigo" => 'CCT-0150'
        ),
        array(
            'fabrica'   => array(14,43,66),
            'icone'     => $icone["computador"],
            'link'      => 'pre_os_cadastro_sac.php',
            'titulo'    => traduz('Abertura de Pré-Os - SAC'),
            'descr'     => traduz('Abre Pré OS para um Posto Autorizado.'),
            "codigo" => 'CCT-0160'
        ),
        array(
            'fabrica'   => array(6),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_pendente_procon.php',
            'titulo'    => traduz('Pendência Call-Center (Procon / Jec)'),
            'descr'     => traduz('Exibe todos os atendimentos do Call-Center com pendência.'),
            "codigo" => 'CCT-0170'
        ),
        array(
            'fabrica'   => array(2),
            'icone'     => $icone["computador"],
            'link'      => 'pesquisa_acompanhamento.php',
            'titulo'    => traduz('Acompanhamento de Assistência Técnica'),
            'descr'     => traduz('Acompanhamento de situação do posto autorizado.'),
            "codigo" => 'CCT-0180'
        ),
        array(
            'fabrica'   => $fabrica_hd_posto,
            'icone'     => $icone["consulta"],
            'link'      => (in_array($login_fabrica,array(30,151,153)) OR $helpdeskPostoAutorizado) ? 'helpdesk_posto_autorizado_listar.php' : 'helpdesk_listar.php',
            'titulo'    => $titulo_CCT_0190,
            'descr'     => $desc_CCT_0190,
            "codigo" => 'CCT-0190'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'callcenter_cadastro_pergunta_tecnica.php',
            'titulo'    => traduz('Cadastro de Perguntas Técnicas'),
            'descr'     => traduz('Cadastro de perguntas técnicas no callcenter.'),
            "codigo"    => 'CCT-0210'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'consulta_pergunta_tecnica.php',
            'titulo'    => traduz('Dúvidas Técnicas'),
            'descr'     => traduz('Consulta de dúvidas técnicas e respostas.'),
            "codigo"    => 'CCT-0220'
        ),
        array(
            'fabrica'   => array_merge(array(30, 114, 151,160,169,170, 174, 183), $fabricas_replica_einhell),
            'icone'     => $icone["relatorio"],
            'link'      => 'manutencao_hd_chamado_lote.php',
            'titulo'    => traduz('Manutenção de Atendimentos em Lote'),
            'descr'     => ($login_fabrica == 30) ? traduz('Realizar transferência de atendimentos') : traduz('Realizar, transferência de atendimentos, alteração de situação, alteração de procedência e interação em atendimentos.'),
            "codigo" => 'CCT-0200'
        ),
        array(
            'fabrica'   => $fabrica_atendimentos,
            'icone'     => $icone["consulta"],
            'link'      => 'callcenter_atendimento.php',
            'titulo'    => traduz('Acompanhamento de Atendimentos Abertos'),
            'descr'     => traduz('Mostra os atendimentos que estão sendo realizados pelos atendentes.'),
            "codigo"    => 'CCT-0210'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_posto_cadastrado_new.php',
            'titulo'    => traduz('Rede autorizada Online'),
            'descr'     => traduz('Rede autorizada Online'),
            "codigo" => 'CCT-2460'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'solicitacao_cheque.php',
            'titulo'    => traduz('Solicitação de Cheque'),
            'descr'     => traduz('Permite Cadastrar e Consultar as solicitações de cheque'),
            "codigo"    => 'CCT-2470'
        ),
        array(
            'fabrica' => [1],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_solicitacoes_postagem.php',
            'titulo'  => traduz('Solicitações de Postagem'),
            'descr'   => traduz('Relatório de solicitações de postagem por intervalo de datas.'),
            'codigo'  => 'CCT-5100'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["relatorio"],
            'link'      => 'monitor_tecnico.php',
            'titulo'    => traduz('Monitor de técnicos'),
            'descr'     => traduz('Monitor de técnicos'),
            "codigo" => 'CCT-0230'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["relatorio"],
            'link'      => 'conferencia_integracao_mobile.php',
            'titulo'    => traduz('Monitor de Interface Mobile/Web'),
            'descr'     => traduz('Mostra OSs que retornaram erro de integração entre Mobile/Web'),
            "codigo" => 'CCT-0240'
        ),
        array(
            'fabrica'   => array(158,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'calendario_rotina.php',
            'titulo'    => ($login_fabrica != 158) ? traduz('Monitor de rotinas automatizadas') : traduz('Monitor Interfaces de Ordens de Serviço'),
            'descr'     => (($login_fabrica != 158) ? traduz('Monitor de rotinas automatizadas') : traduz('Monitor Interfaces de Ordens de Serviço')).traduz(' agendadas e demonstrativo de resultados das execuções'),
            "codigo"    => 'CCT-0250'
        ),
        array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_acompanhamento.php',
            'titulo'    => traduz('Acompanhamento de Ordens de Serviço'),
            'descr'     => traduz('Acompanhamento de exportação de ordens de serviço para o ERP cliente'),
            "codigo"    => 'CCT-0260'
        ),
        array(
            'fabrica'   => ($atendimentoFacebook && $atendimentoIG) ? array($login_fabrica) : array(),
            'icone'     => $icone['computador'],
            'link'      => 'dashboard_social.php',
            'titulo'    => traduz('Dashboard Mídias Sociais'),
            'descr'     => traduz('Monitoramento de interações em Mídias Sociais'),
            'codigo'    => 'CCT-0270'
        ),
        array(
            'fabrica'   => ($arrPermissoesAdm["suporte_tecnico"] == "t") ? [169,170] : [],
            'icone'     => $icone["cadastro"],
            'link'      => 'helpdesk_posto_autorizado_novo_atendimento.php',
            'titulo'    => "Cadastro Help-Desk Suporte Técnico",
            'descr'     => traduz('Cadastro dos Help-desks de suporte técnico.'),
            "codigo" => 'CCT-0280'
        ),
        array(
            'fabrica'   => ($arrPermissoesAdm["suporte_tecnico"] == "t") ? [169,170] : [],
            'icone'     => $icone["consulta"],
            'link'      => 'helpdesk_posto_autorizado_listar.php',
            'titulo'    => "Consulta Help-Desk Suporte Técnico",
            'descr'     => traduz('Consulta dos Help-desks de suporte técnico.'),
            "codigo" => 'CCT-0290'
        ),
        'link' => 'linha_de_separação'
    ),

    /* Seção INFORMATIVO MENSAL, apenas BLACK */
    //if ($login_fabrica == 1) {
    'secao1'=> array(
        'secao' => array(
            'link'  => '#',
            'titulo' => traduz('INFORMATIVO MENSAL'),
            'fabrica'=> array(1)
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'informativo_publicado.php',
            'titulo'    => traduz('Informativos Publicados'),
            'descr'     => traduz('Informativos Publicados'),
            "codigo" => 'CCT-1010'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'informativo_edicao.php',
            'titulo'    => traduz('Edição de Informativos'),
            'descr'     => traduz('Edição de Informativos'),
            "codigo" => 'CCT-1020'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'reportagem_consulta.php',
            'titulo'    => traduz('Reportagens'),
            'descr'     => traduz('Reportagens'),
            "codigo" => 'CCT-1030'
        ),
        array(
            'icone'     => $icone["computador"],
            'link'      => 'destinatario.php',
            'titulo'    => traduz('Destinatários'),
            'descr'     => traduz('Destinatários'),
            "codigo" => 'CCT-1040'
        ),
        'link' => 'linha_de_separação'
    ),

    /**
     * RELATÓRIOS RELATIVOS AO CALL-CENTER. GERAL.
     **/
    'secao2' => array(
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('CALL-CENTER RELATÓRIOS'),
            'fabrica_no' => array(25, 95)
        ),
        array(
            'fabrica_no'=> array(25, 95),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_atendimento.php',
            'titulo'    => traduz('Relatório de Atendimentos'),
            'descr'     => traduz('Relatório de quantidade de atendimento e o status.'),
            "codigo" => 'CCT-2010'
        ),
        array(
            'fabrica'=> array(81),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimentos_solucoes.php',
            'titulo'    => traduz('Relatório Atendimentos X Soluções'),
            'descr'     => traduz('Relatório Atendimentos X Soluções'),
            "codigo" => 'CCT-2011'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimento_orientacao_uso.php',
            'titulo'    => traduz('Relatório de Orientação de Uso'),
            'descr'     => traduz('Relatório de Atendimentos x Orientação de Uso.'),
            "codigo" => 'CCT-2020'
        ),
        array(
            'fabrica'   => array(52),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_atendimento.php',
            'titulo'    => traduz('Relatório de Atendimentos por POSTO'),
            'descr'     => traduz('Relatório que exibe a quantidade de atendimentos <br /> por posto selecionado no período filtrado.'),
            "codigo" => 'CCT-2030'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pre_os_britania_simplificado.php',
            'titulo'    => traduz('Relatório de Pré OS'),
            'descr'     => traduz('Relatório Pré Ordem de serviço para Posto Autorizado.'),
            "codigo" => 'CCT-2040'
        ),
        array(
            'fabrica_no'=> array(24, 25, 52, 95,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_atendimento_atendente.php',
            'titulo'    => traduz('Relatório de Atendimentos por Atendente'),
            'descr'     => traduz('Relatório de quantidade de atendimento por atendente.'),
            "codigo" => 'CCT-2050'
        ),
        array(
            'fabrica'=> array(169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_atendimento_atendente.php',
            'titulo'    => traduz('Relatório de atendimento x interações'),
            'descr'     => traduz('Relatório de interações efetuadas e atendimentos abertos por atendente.'),
            "codigo" => 'CCT-2050'
        ),
        array(
            'fabrica_no'=> array(25, 52, 95),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_periodo_atendimento.php',
            'titulo'    => traduz('Relatório Período de Atendimentos'),
            'descr'     => traduz('Relatório de Período de Atendimento, informa a quantidade de dias que o atendimento levou para ser resolvido.'),
            "codigo" => 'CCT-2060'
        ),
        array(
            'fabrica_no'=> array(24, 25, 52, 95,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_defeito.php',
            'titulo'    => traduz('Relatório de Reclamações'),
            'descr'     => traduz('Relatório com os 10 defeitos mais reclamados.'),
            "codigo" => 'CCT-2070'
        ),
        array(
            'fabrica'=> array(169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_defeito.php',
            'titulo'    => traduz('Relatório de Defeitos Reclamados'),
            'descr'     => traduz('Relatório com os 10 defeitos mais reclamados.'),
            "codigo" => 'CCT-2070'
        ),
        array(
            'fabrica_no'=> array(25, 95),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_defeito_produto.php',
            'titulo'    => traduz('Relatório de Reclamações X Produtos'),
            'descr'     => traduz('Relatório de reclamações por produtos.'),
            "codigo" => 'CCT-2080'
        ),
        array(
            'fabrica'   => array($fabrica_upload_preco),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_produto_defeito_reclamado.php',
            'titulo'    => traduz('Relatório Produto X Defeito Reclamado'),
            'descr'     => traduz('Relatório de produtos por defeito reclamado'),
            "codigo" => 'CCT-2090'
        ),
        array(
            'fabrica'=> array(162),
            'icone'     => $icone["relatorio"],
            'link'      => 'novos_relatorios_callcenter.php',
            'titulo'    => traduz('Novos Relatórios Callcenter'),
            'descr'     => traduz('Novos Relatórios Callcenter'),
            "codigo" => 'CCT-2091'
        ),
        array(
            'fabrica'   => array(85),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisa_satisfacao.php',
            'titulo'    => traduz('Relatório de Pesquisa de Satisfação'),
            'descr'     => traduz('Relatório de Satisfação dos Clientes Atendidos pelo SAC.'),
            "codigo" => 'CCT-2100'
        ),
        array(
            'fabrica'   => array(85,94),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimento_pesquisa_satisfacao.php',
            'titulo'    => traduz('Relatório Atendimentos x Pesquisa Satisfação'),
            'descr'     => traduz('Relatório Total de Atendimentos x Atendimentos<br /> com Pesquisa de Satisfação'),
            "codigo" => 'CCT-2110'
        ),
        array(
            'fabrica_no'=> array(24, 25, 52, 95),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_defeito_familia.php',
            'titulo'    => traduz('Relatório de Reclamações X Família'),
            'descr'     => traduz('Relatório de reclamações por família de produtos.'),
            "codigo" 	=> 'CCT-2120'
        ),
        array(
            'fabrica_no'=> array(24, 25, 52, 95,169,170,184,200),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_produto_natureza.php',
            'titulo'    => traduz('Relatório de Produtos X Natureza'),
            'descr'     => traduz('Relatório de natureza por produtos.'),
            "codigo" 	=> 'CCT-2130'
        ),
        array(
            'fabrica'	=> array(94),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_posto_natureza.php',
            'titulo'    => traduz('Relatório de Posto X Natureza'),
            'descr'     => traduz('Relatório de posto por produtos.'),
            "codigo" 	=> 'CCT-2140'
        ),

        array(
            'fabrica_no'=> array_merge(array(25, 95), $fabricas_contrato_lite),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_interacoes.php',
            'titulo'    => traduz('Relatório maior tempo entre interações'),
            'descr'     => traduz('Relatório que exibe o maior periodo sem interações<BR> com o consumidor.'),
            "codigo" => 'CCT-2150'
		),
        array(
            'fabrica_no'=> array_merge(array(25, 95), $fabricas_contrato_lite),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_natureza.php',
            'titulo'    => traduz('Relatório de Natureza de Chamado'),
            'descr'     => traduz('Relatório que exibe a quantidade de atendimento<BR> por Natureza.'),
            "codigo" => 'CCT-2160'
		),
		array(
			'fabrica' => array(30),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_tempo_atendente.php',
            'titulo'    => traduz('Relatório tempo atendente'),
            'descr'     => traduz('Relatório que exibe o tempo de cada atendente ficou responsável por atendimento.'),
            "codigo" => 'CCT-2170'
        ),
        array(
            'fabrica'   => array(5),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_indicacao_posto.php',
            'titulo'    => traduz('Relatório de Indicação de Posto'),
            'descr'     => traduz('Relatório que exibe a quantidade de Indicação de Posto.'),
            "codigo" => 'CCT-2170'
        ),
        array(
            'fabrica'   => array(5),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_historico_csv.php',
            'titulo'    => traduz('Histórico do Call-Center'),
            'descr'     => traduz('Relatório com atendimentos e histórico, em formato texto.'),
            "codigo" => 'CCT-2180'
        ),
        array(
            'disabled'  => true, //HD 684395
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'call_center_relatorio_posto_indicacao_suggar.php',
            'titulo'    => traduz('Relatório de Indicação de Posto'),
            'descr'     => traduz('Relatório que exibe a quantidade de Indicação de Posto.'),
            "codigo" => 'CCT-2190'
        ),
        array(
            'fabrica_no'=> array_merge(array(25, 95), $fabricas_contrato_lite),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_atendente.php',
            'titulo'    => traduz('Relatório por Atendentes'),
            'descr'     => traduz('Relatório que exibe a quantidade de atendimentos por atendente'),
            "codigo" => 'CCT-2200'
        ),
        array(
            'fabrica'   => array(80),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimento_procon.php',
            'titulo'    => traduz('Relatório Procon'),
            'descr'     => traduz('Relatório dos atendimentos de Procon.'),
            "codigo" => 'CCT-2210'
        ),
        array(
            'fabrica_no'=> array(25, 95,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_mailing.php',
            'titulo'    => traduz('Relatório de Mailing'),
            'descr'     => traduz('Relatório que exibe nome e e-mail dos consumidores cadastrados no atendimento do SAC'),
            "codigo" => 'CCT-2220'
        ),
        array(
            'fabrica'=> array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_quantidade_os_mensal.php',
            'titulo'    => traduz('Relatório de Quantidade de OS Mensal/DR'),
            'descr'     => traduz('Relatório de Quantidade de OS Mensal/DR'),
            "codigo" => 'CCT-2221'
        ),
        array(
            'fabrica'=> array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_remessa_ect_xls.php',
            'titulo'    => traduz('Relatório de Remessa ECT'),
            'descr'     => traduz('Relatório de Remessa ECT'),
            "codigo" => 'CCT-2222'
        ),
        array(
            'fabrica'=> array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_evolucao_contratual.php',
            'titulo'    => traduz('Relatório de Evolução Contratual'),
            'descr'     => traduz('Relatório de Evolução Contratual'),
            "codigo" => 'CCT-2223'
        ),
        array(
            'fabrica'=> array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_os_finalizada.php',
            'titulo'    => traduz('Relatório de OS\'s Finalizadas'),
            'descr'     => traduz('Relatório de OS\'s Finalizadas'),
            "codigo" => 'CCT-2224'
        ),
        array(
            'fabrica'=> array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_qualidade_atendimento.php',
            'titulo'    => traduz('Relatório de Qualidade de Atendimento'),
            'descr'     => traduz('Relatório de Qualidade de Atendimento'),
            "codigo" => 'CCT-2224'
        ),
        array(
            'fabrica'   => array(52),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimento_familia.php',
            'titulo'    => traduz('Relatório de Atendimento por Família'),
            'descr'     => traduz('Relatório de Atendimento por Família'),
            "codigo" => 'CCT-2230'
        ),
        /*array(
            'fabrica'   => array(52),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_chamado.php',
            'titulo'    => 'Relatório de Pesquisas em Atendimentos',
            'descr'     => 'Relatório das Pesquisas que foram feitas com os Clientes através de Atendimentos.',
            "codigo" => 'CCT-2240'
        ),*/
        array(
            'fabrica'   => array(30,52,85,94,129,138,145,151,152,161,180,181,182),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_chamado_new.php',
            'titulo'    => traduz('Relatório de Pesquisas em Atendimentos'),
            'descr'     => traduz('Relatório das Pesquisas que foram feitas com os Clientes através de Atendimentos.'),
            "codigo" => 'CCT-2250'
        ),
        array(
            'fabrica'   => array_merge(array(160), $fabricas_replica_einhell),
			'fabrica_no' => [35],
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_chamado_new.php',
            'titulo'    => traduz('Relatório de Pesquisas'),
            'descr'     => traduz('Relatório das Pesquisas'),
            "codigo" => 'CCT-2250'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_chamado_new_black.php',
            'titulo'    => traduz('Novo Relatório de Pesquisas em Atendimentos'),
            'descr'     => traduz('Novo Relatório das Pesquisas que foram feitas com os Clientes através de Atendimentos.'),
            "codigo" => 'CCT-2250'
        ),
        array(
            'fabrica'   => array(15),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_mailing_os.php',
            'titulo'    => traduz('Relatório de Mailing - OS'),
            'descr'     => traduz('Relatório que exibe nome e e-mail dos consumidores de OSs abertas'),
            "codigo" => 'CCT-2260'
        ),
        array(
            'fabrica'   => array(51),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_troca_coleta_postagem.php',
            'titulo'    => traduz('Relatório de OSs Troca de Produto'),
            'descr'     => traduz('Relatório que exibe as OS de troca com Nº de Coleta/Postagem'),
            "codigo" => 'CCT-2270'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_enviadas_laudo_tecnico.php',
            'titulo'    => traduz('Relatório de Pesquisa de Satisfação enviada por e-mail'),
            'descr'     => traduz('Relatório que exibe as pesquisas de satisfação enviadas por e-mail'),
            "codigo" => 'CCT-2270'
        ),
        array(
            'fabrica'   => array(51),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_perfil_consumidor.php',
            'titulo'    => traduz('Relatório de Perfil do Consumidor'),
            'descr'     => traduz('Relatório baseado na Pesquisa sobre Perfil do Consumidor'),
            "codigo" => 'CCT-2280'
        ),
        array(
            'fabrica'   => array(72),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_nf_troca.php',
            'titulo'    => traduz('Relatório de OS por status da nota'),
            'descr'     => traduz('Relatório que exibe as OS por status da nota'),
            "codigo" => 'CCT-2290'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_posto_atualizacao.php',
            'titulo'    => traduz('Relatório de Atualização de Postos'),
            'descr'     => traduz('Relatório com relação de postos com dados cadastrais Atualizados'),
            "codigo" => 'CCT-2300'
        ),
        array(
            'fabrica'   => array(24,151,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_estatisticas.php',
            'titulo'    => traduz('Estatisticas de Callcenter'),
            'descr'     => traduz('Estatisticas com visão geral de atendimentos'),
            "codigo" => 'CCT-2310'
        ),
        array(
            'fabrica'   => array(59),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_troca.php',
            'titulo'    => traduz('Call-Center Ressarcimento/SEDEX Reverso'),
            'descr'     => traduz('Chamados de Ressarcimento/SEDEX Reverso'),
            "codigo" => 'CCT-2320'
        ),
        array(
            'fabrica'   => array(59),
            'icone'     => $icone["upload"],
            'link'      => 'callcenter_backup_parametros.php',
            'titulo'    => traduz('Call-Center Backup'),
            'descr'     => traduz('Gera arquivo de backup em formato <span title="Dados separados por ponto e vírgula (;)">CSV</span> para ser exportado para Access.'),
            "codigo" => 'CCT-2330'
        ),
        array(
            'fabrica'   => array(2),
            'icone'     => $icone["relatorio"],
            'link'      => 'acompanhamento_consulta.php',
            'titulo'    => traduz('Relatório Situação das Assistências'),
            'descr'     => traduz('Relatório que exibe o histórico de acompanhamento<br>das assistências.'),
            "codigo" => 'CCT-2340'
        ),
        array(
            'fabrica'   => array(11, 172),//HD 56947
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_at_procon.php',
            'titulo'    => traduz('Relatório Classificação Posto'),
            'descr'     => traduz('Relatório que mostra as classificações dos<br>postos no atendimento(AT/Procon).'),
            "codigo" => 'CCT-2350'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_duvidas.php',
            'titulo'    => traduz('Relatório Dúvidas'),
            'descr'     => traduz('Relatório que mostra as as dúvidas <br/> de produtos registradas em chamados.'),
            "codigo" => 'CCT-2360'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_motivo_callcenter.php',
            'titulo'    => traduz('Relatório Motivo Atendimento'),
            'descr'     => traduz('Relatório que mostra os motivos <br/> dos atendimentos abertos.'),
            "codigo" => 'CCT-2370'
        ),
        array(
            'fabrica'   => array(94),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_chamados_callcenter.php',
            'titulo'    => traduz('Relatório Chamados Call-Center'),
            'descr'     => traduz('Relatório de Chamados do Call-Center.'),
            "codigo" => 'CCT-2380'
        ),
        array(
            'fabrica'   => array(115,116,117,122,81,114,124,123,126,127,128,129),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_reclamacao_por_estado.php',
            'titulo'    => traduz('Reclamações por estado'),
            'descr'     => traduz('Histórico de atendimentos por estado.'),
            "codigo" => 'CCT-2390'
        ),
        array(
            'fabrica'   => array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_atendimento_fora_garantia.php',
            'titulo'    => traduz('Atendimentos fora de garantia'),
            'descr'     => traduz('Relatório dos atendimentos que foram abertos para produtos fora de garantia'),
            "codigo" => 'CCT-2410'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_somente_os_revenda.php',
            'titulo'    => traduz('Relatório OS Revenda'),
            'descr'     => traduz('Relatório de OS de Revenda'),
            "codigo" => 'CCT-2420'
        ),
        array(
            'fabrica'   => array(74, 11, 162, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_produtividade.php',
            'titulo'    => traduz('Relatório de produtividade'),
            'descr'     => traduz('Relatório de produtividade por atendente'),
            "codigo" => 'CCT-2430'
        ),
        array(
            'fabrica'   => array(74, 11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_registro_processo.php',
            'titulo'    => traduz('Relatório de registro de processo'),
            'descr'     => traduz('Relatório de registro de processo'),
            "codigo" => 'CCT-2440'
        ),
        array(
            'fabrica'   => array(85),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_ambev.php',
            'titulo'    => traduz('Relatório AMBEV'),
            'descr'     => traduz('Relatório AMBEV'),
            "codigo" => 'CCT-2450'
        ),
        array(
            'fabrica'   => array(85,88,94,129,145,151,161),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_enviadas.php',
            'titulo'    => traduz('Relatório Pesquisas Enviadas'),
            'descr'     => traduz('Relatório de pesquisas de satisfação enviadas ao consumidor'),
            "codigo"    => 'CCT-2470'
        ),
        array(
            'fabrica_no'=> array_merge(array(25, 52, 95), $fabricas_contrato_lite),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_produto.php',
            'titulo'    => traduz('Relatório de Atendimento por produto'),
            'descr'     => traduz('Relatório de atendimento por produtos'),
            "codigo"    => 'CCT-2480'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_total_garantia.php',
            'titulo'    => traduz('Relatório Total de Garantia'),
            'descr'     => traduz('Relatório total de OS de garantia'),
            "codigo"    => 'CCT-2490'
        ),
        array(
            'fabrica' => array(164),
            'icone'   => $icone["relatorio"],
            'link'    => 'callcenter_perfil_consumidor.php',
            'titulo'  => traduz('Relatório de Perfil do Consumidor'),
            'descr'   => traduz('Relatório que exibe as informações de Perfil do Consumidor por data e região.'),
            "codigo"  => 'CCT-2490'
        ),
        array(
            'fabrica'   => array_merge(array(59,160), $fabricas_replica_einhell),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_origem.php',
            'titulo'    => traduz('Relatório Call-Center X Origem'),
            'descr'     => traduz('Relatório de Call-Center por Origem'),
            "codigo"    => 'CCT-9280'
        ),
        array(
            'fabrica'   => array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_consulta_lite_interativo.php?fale_conosco=true',
            'titulo'    => traduz('Atendimentos Fale Conosco'),
            'descr'     => traduz('Relatório de callcenter com informa do Fale Conosco'),
            "codigo"    => 'CCT-2490'
        ),

        array(
            'fabrica'   => array(35),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_troca_produtos.php',
            'titulo'    => traduz('Relatório de OS de Troca de Produtos'),
            'descr'     => traduz('Relatório de OS que foram efetuadas troca de produto'),
            "codigo"    => 'CCT-2495'
        ),
        // array( /*HD - 3956227*/
        //     'fabrica'   => array(3,11,80,101,104,151,169,170,172),
        //     'icone'     => $icone["relatorio"],
        //     'link'      => 'relatorio_sms.php',
        //     'titulo'    => 'Relatório de Envio SMS',
        //     'descr'     => 'Relatório de Envio SMS',
        //     "codigo"    => 'CCT-2500'
        // ),
        array(
            'fabrica'   => array(1,3,11,35,80,101,104,123,151,157,160,167,169,172,174,186,203),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_sms_detalhado.php',
            'titulo'    => traduz('Relatório de Envio SMS e Respostas'),
            'descr'     => traduz('Relatório mostra detalhadamento o envio de SMS e se teve respostas via SMS'),
            "codigo"    => 'CCT-2501'
        ),
        array(
            'fabrica'   => array(81),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_digitadas.php',
            'titulo'    => traduz('Relatório OS Digitadas'),
            'descr'     => traduz('Relatório de OSs Digitadas, filtrando somente pela data de digitação.'),
            "codigo"    => 'CCT-2510'
        ),
        array(
            'fabrica'   => array(151),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_sigep.php',
            'titulo'    => traduz('Relatório SIGEP'),
            'descr'     => traduz('Relatório com informações do consumidor para enviar ao correios.'),
            "codigo"    => 'CCT-2510'
        ),
        array(
            'fabrica'   => ($telecontrol_distrib == 't') ? [$login_fabrica] : array(151,169,170,174),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_visao_geral_atendimentos.php',
            'titulo'    => traduz('Relatório Visão Geral'),
            'descr'     => traduz('Relatório mostra uma visão geral dos atendimentos por admin e providências.'),
            "codigo"    => 'CCT-2520'
	    ),
        array(
            'fabrica'   => (isset($novaTelaOs)) ? array($login_fabrica) : array(104,139),
            'fabrica_no'   => array(189),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_produtos_trocados.php',
            'titulo'    => traduz('Relatório Produtos Trocados'),
            'descr'     => traduz('Relatório Produtos Trocados por O.S'),
            "codigo"    => 'CCT-2560'
        ),
        array(
            'fabrica'   => array(152,180,181,182),
            'icone'     => $icone["relatorio"],
            'link'      => 'distribuicao_atendimento_categoria.php',
            'titulo'    => traduz('Distribuição de Atendimentos por Categoria'),
            'descr'     => traduz('Consulta de Atendimentos Distribuídos por Categoria'),
            "codigo"    => 'CCT-2561'
        ),
        array(
            'fabrica'   => array(160),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_intencao_compra.php',
            'titulo'    => traduz('Relatório de Intenção de Compra'),
            'descr'     => traduz('Relatório de peças com intenção de compra - Pedido'),
            "codigo"    => 'CCT-2562'
        ),
        array(
            'fabrica'   => array(151,174),
            'icone'     => $icone["relatorio"],
            'link'      => $link_historioco,
            'titulo'    => traduz('Consulta histórico de Atendimentos'),
            'descr'     => traduz('Consulta de Atendimentos importadas para o Telecontrol'),
            "codigo"    => 'CCT-2530'
        ),
        array(
            'fabrica'   => array_merge(array(81,114,122,123,125,134,147,151,160,169,170,174,189), $fabricas_replica_einhell),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_completo_callcenter.php',
            'titulo'    => traduz('Consulta Geral de Atendimentos'),
            'descr'     => traduz('Consulta Geral de Atendimentos '),
            "codigo"    => 'CCT-2540'
        ),
        array(
            'fabrica'   => array(151),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_helpdesk_posto_autorizado.php',
            'titulo'    => traduz('Relatório do Helpdesk do Posto Autorizado'),
            'descr'     => traduz('Relatório dos atendimentos abertos no Helpdesk do Posto Autorizado'),
            "codigo"    => 'CCT-2550'
        ),
        array(
            "fabrica"   => array(1),
            "icone"     => $icone['relatorio'],
            "link"      => "relatorio_pesquisa_grafico.php",
            "titulo"    => traduz("Gráficos de Pesquisa de Satisfação"),
            "descr"     => traduz("Gráficos e amostras dos resultados das Pesquisas de Satisfação"),
            "codigo"    => "CCT-2560"
        ),
        array(
            'fabrica'=> array(81),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_produto_marca.php',
            'titulo'    => traduz('Relatório de Atendimento por produto ou marca'),
            'descr'     => traduz('Relatório de atendimento por produtos ou marcas'),
            "codigo" => 'CCT-2570'
        ),
        array(
            'fabrica'=> array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_tempo_medio_atendimento.php',
            'titulo'    => traduz('Tempo Médio de Atendimento'),
            'descr'     => traduz('Tempo Médio de Atendimento.'),
            "codigo" => 'CCT-2580'
        ),
        array(
            'fabrica'=> array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_tempo_status.php',
            'titulo'    => traduz('Tempo Entre Status de Atendimento'),
            'descr'     => traduz('Relatório de medição de quanto tempo o atendimento ficou em um certo status.'),
            "codigo" => 'CCT-2590'
        ),
        array(
            'fabrica'=> array(20,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_reincidente.php',
            'titulo'    => traduz('OSs Reincidentes'),
            'descr'     => traduz('Relatorio de OSs Reincidentes.'),
            "codigo" => 'CCT-2600'
        ),
        array(
            'fabrica'=> array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_bonificacao.php',
            'titulo'    => traduz('Relatório Bônus Posto'),
            'descr'     => traduz('Relatório Bônus Posto'),
            "codigo" => 'CCT-2610'
        ),
        array(
            'fabrica'   => array(151),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_completo_callcenter_anual.php',
            'titulo'    => traduz('Consulta Geral de Atendimentos Anual'),
            'descr'     => traduz('Consulta Geral de Atendimentos Anual '),
            "codigo"    => 'CCT-2620'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_devolucoes.php',
            'titulo'    => traduz('Relatório de Devoluções'),
            'descr'     => traduz('Relatório das devoluções'),
            "codigo" => 'CCT-2630'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'consulta_devolucoes.php',
            'titulo'    => traduz('Consulta Devoluções Fábrica'),
            'descr'     => traduz('Consulta de devoluções'),
            "codigo" => 'CCT-2640'
        ),
        array(

            'fabrica'   => array(85),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_servico_diferenciado.php',
            'titulo'    => traduz('Relatório de Bonificação'),
            'descr'     => traduz('Consulta de bonificações por serviço diferenciado'),
            "codigo" => 'CCT-2650'
        ),
        array(
            'fabrica'   => array(85),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_garantia_contratual.php',
            'titulo'    => traduz('Relatório de Garantia Contratual'),
            'descr'     => traduz('Relatório de Atendimentos de Garantia Contratual'),
            "codigo" => 'CCT-2680'
        ),
        array(
            'fabrica'   => array(169, 170),
            'icone'     => $icone["relatorio"],
            'link'      => 'pesquisa_nps_tracksale.php',
            'titulo'    => traduz('Pesquisa NPS Tracksale'),
            'descr'     => traduz('Relatório de atendimentos finalizados para pesquisa de satisfação'),
            "codigo" => 'CCT-2660'
        ),
        array(
            'fabrica'   => array(169, 170),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_tecnicos_cadastrados.php',
            'titulo'    => traduz('Relatório de Técnicos Cadastrados'),
            'descr'     => traduz('Relatório de Técnicos de Postos'),
            "codigo" => 'CCT-2661'
        ),
        array(
            'fabrica'   => array(90),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_tempo_medio_atendimento.php',
            'titulo'    => traduz('Dashboard para Tempo Médio de Atendimento'),
            'descr'     => traduz('Dashboard para Tempo Médio de Atendimento'),
            "codigo" => 'CCT-2670'
        ),

        array(
            'fabrica'   => array(174),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_sla_callcenter.php',
            'titulo'    => traduz('Relatório SLA callcenter'),
            'descr'     => traduz('Relatório detalhado dos atendimentos callcenter'),
            "codigo" => 'CCT-2680'
        ),
        $integracaoTelefonia == true ? array(
            'fabrica'   => array($login_fabrica),
            'icone'     => $icone['relatorio'],
            'link'      => 'relatorio_atendentes_telefonia.php',
            'titulo'    => traduz('Relátorio de Atendimentos Telefonia'),
            'descr'     => traduz('Relatório de tempo de atendimentos da Telefonia'),
            'codigo'    => 'CCT-2690'
            ) : array(),        
         array(
             'fabrica'   => ($integracaoTelefonia) ? [$login_fabrica] : [],
             'icone'     => $icone["relatorio"],
             'link'      => 'relatorio_fila_telefonia.php',
             'titulo'    => traduz('Relatório Telefonia por Fila'),
             'descr'     => traduz('Relatório de atendimentos da Telefonia por Fila'),
             "codigo" => 'CCT-2700'
         ),
        array(
            'fabrica'   => ($telecontrol_distrib == 't') ? [$login_fabrica] : [],
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_interacoes.php',
            'titulo'    => traduz('Relatório Interações'),
            'descr'     => traduz('Relatório detalhado das interações'),
            "codigo" => 'CCT-2710'
        ),
        array(
            'fabrica'   => array(183),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_agendamentos_cancelados.php',
            'titulo'    => 'Relatório Agendamento Cancelado',
            'descr'     => 'Relatório de OS com agendamento cancelado',
            "codigo" => 'CCT-2720'
        ),
        array(
            'fabrica'   => array(183),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_agendamentos_pendentes.php',
            'titulo'    => 'Relatório Agendamentos Pendentes',
            'descr'     => 'Relatório de OS com agendamento pendente',
            "codigo" => 'CCT-2730'
        ),
        array(
            'fabrica' => [1],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_dashboard_helpdesk_posto.php',
            'titulo'  => 'Relatório Dashboard HelpDesk Posto',
            'descr'   => 'Relatório Dashboard de HelpDesk Posto x Fábrica',
            "codigo"  => 'CCT-2740'
        ),
        array(
            'fabrica' => [1],
            'icone'   => $icone["relatorio"],
            'link'    => 'dashboard_helpdesk_posto.php',
            'titulo'  => 'Dashboard HelpDesk Posto',
            'descr'   => 'Dashboard de HelpDesk Posto x Fábrica',
            "codigo"  => 'CCT-2750'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimentos_cliente_admin.php',
            'titulo'    => traduz('Consulta de Atendimentos Cliente Admin'),
            'descr'     => traduz('Consulta de Atendimentos Cliente Admin'),
            "codigo"    => 'CCT-2760'
        ),
        array(
            'fabrica_no'=> array(25,95),
            'link'      => 'linha_de_separação',
        ),
        
    ),

    /**
     * Seção de ORDENS DE SERVIÇO. GERAL.
     **/
    'secao3' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('ORDENS DE SERVIÇO'),
            'fabrica_no' => array(25,95,189)
        ),
        array(
            'fabrica'   => array(($login_fabrica != 14 || in_array($login_admin, array(260,261,262,263)))),
            'fabrica_no'=> array(25,95),
            'icone'     => $icone["cadastro"],
            'link'      => $link3010,
            'titulo'    => traduz('Cadastra Ordens de Serviço'),
            'descr'     => traduz('Cadastro de Ordem de Serviços, no modo ADMIN'),
            "codigo"    => 'CCT-3010'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'upload_pesquisa_satisfacao.php',
            'titulo'    => traduz('Upload de Pesquisa (Outros Países)'),
            'descr'     => traduz('Upload de Pesquisa de satisfação'),
            "codigo"    => 'CCT-3011'
        ),
        array(
            'fabrica'   => array(152,180,181,182),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_os_entrega_tecnica.php',
            'titulo'    => traduz('Cadastra Ordens de Serviço - Entrega Técnica'),
            'descr'     => traduz('Cadastro de Ordem de Serviços - Entrega Técnica, no modo ADMIN'),
            "codigo"    => 'CCT-3482'
        ),
        array(
            'fabrica'   => $os_cadastro_revisao,
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_os_revisao.php',
            'titulo'    => traduz('Cadastra Ordens de Serviço - Revisão'),
            'descr'     => traduz('Cadastro de Ordem de Serviços - Revisão, no modo ADMIN'),
            "codigo"    => 'CCT-3480'
        ),
        array(
            'fabrica'   => $fabrica_admin_anexaNF,
            'icone'     => $icone["anexo"],
            'link'      => 'nota_foto_cadastro.php',
            'titulo'    => traduz('Anexa NF às Ordens de Serviço'),
            'descr'     => traduz('Permite anexar arquivos às Ordens de Serviço'),
            "codigo" => 'CCT-3020'
        ),
        array(
            'fabrica'   => $fabrica_relatorio_os_aberto,
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_aberta.php',
            'titulo'    => traduz('Relatório de Ordens de Serviço em aberto'),
            'descr'     => traduz('Mostra as Ordens de Serviço que estão em aberto.'),
            "codigo" => 'CCT-3030'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["cadastro"],
            'link'      => 'aprova_os_troca.php',
            'titulo'    => traduz('Troca de Produto na OS'),
            'descr'     => traduz('Cadastro da troca de produto na OS'),
            "codigo" => 'CCT-3040'
        ),
        array(
            'fabrica_no'=> $fabrica_NAO_aprova_KM,
            'icone'     => $icone["relatorio"],
            'link'      => 'aprova_km.php',
            'titulo'    => traduz('Intervenção de KM'),
            'descr'     => traduz('OS para aprovação de KM do posto autorizado ao consumidor'),
            "codigo" => 'CCT-3050'
        ),
        array(
            'fabrica'   => array(3,25,81,95,114),
            'fabrica_no'=> array_merge($fabricas_contrato_lite, array(50,86,81,114,124,123,124,127,128,129)),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_atendimento_domicilio.php',
            'titulo'    => traduz('Intervenção de KM'),
            'descr'     => traduz('OS para aprovação de KM do posto autorizado ao consumidor'),
            "codigo" => 'CCT-3060'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(3),
            'icone'     => $icone["consulta"],
            'link'      => 'os_parametros.php',
            'titulo'    => traduz('Consulta ANTIGA'),
            'descr'     => traduz('Liberado até às 15 horas de hoje. Problemas de performance no site estão relacionados com pesquisas muito extensas.'),
            "codigo" => 'CCT-3070'
        ),
        array(
            'fabrica_no'=> array(25,95),
            'icone'     => $icone["consulta"],
            'link'      => iif(($login_fabrica == 1),
            'os_consumidor_consulta.php',
            'os_consulta_lite.php'),
            'titulo'    => traduz('Consulta Ordens de Serviço'),
            'descr'     => traduz('Consulta OS Lançadas'),
            "codigo" => 'CCT-3080'
        ),
        array(
            "fabrica" => (in_array($login_fabrica, $array_interacao_os) || $interacaoOsPosto),
            'fabrica_no'=> array(193),
            "icone"  => $icone["relatorio"],
            "link"   => "relatorio_interacao_os.php",
            "titulo" => traduz("Interações em Ordem de Serviço"),
            "descr"  => traduz("Relatório de interações em Ordem de Serviço: Novas Interações do Posto Autorizado, OS com última interação do PA e OS com última interação da Fábrica"),
            "codigo" => "CCT-3540"
        ),
        array(
            'fabrica'   => array(7,45),
            'icone'     => $icone["computador"],
            'link'      => 'os_fechamento.php',
            'titulo'    => traduz('Fechamento de Ordem de Serviço'),
            'descr'     => traduz('Fechamento de Ordem de Serviço'),
            "codigo" => 'CCT-3090'
        ),
        array(
            'fabrica_no'=> array(25,95),
            'icone'     => $icone["consulta"],
            'link'      => (isset($novaTelaOs)) ? 'relatorio_os_excluida.php' : 'os_parametros_excluida.php',
            'titulo'    => traduz('Consulta OS Excluída'),
            'descr'     => traduz('Consulta Ordens de Serviço excluídas do sistema'),
            "codigo" => 'CCT-3100'
        ),
        array(
            'fabrica' => array(164),
            'icone'   => $icone["relatorio"],
            'link'    => 'tempo_atendimento_os.php',
            'titulo'  => traduz('Tempo de Atendimento de OS'),
            'descr'   => traduz('Consulta o tempo de atendimento das OSs por períodos específicos'),
            "codigo"  => 'CCT-3200'
        ),
        array(
            'fabrica'=> array(42,3),
            'icone'     => $icone["consulta"],
            'link'      => 'os_consulta_procon.php',
            'titulo'    => traduz('Consulta OS Procon'),
            'descr'     => traduz('Consulta Ordens de Serviço do Procon'),
            "codigo" => 'CCT-3110'
        ),
        array(
            'fabrica'   => array(35),
            'icone'     => $icone["computador"],
            'link'      => 'produto_troca_lote.php',
            'titulo'    => traduz('Troca de Produtos Criticos em Lote'),
            'descr'     => traduz('Troca de produto de OS de produtos críticos'),
            "codigo" => 'CCT-3120'
        ),
        array(
            'fabrica'   => $verifica_ressarcimento_troca,
            'icone'     => $icone["consulta"],
            'link'      => 'consulta_os_troca_ressarcimento.php',
            'titulo'    => traduz('Consulta OS - Troca em Lote'),
            'descr'     => traduz('Consulta Ordens de Serviço - Troca em Lote'),
            "codigo" => 'CCT-3130'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_os_cortesia.php',
            'titulo'    => traduz('Aprova OS de Cortesia'),
            'descr'     => traduz('Aprovação das OS de Cortesia pelos Promotores'),
            "codigo" => 'CCT-3140'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_troca_os.php',
            'titulo'    => traduz('Aprova OS de Troca'),
            'descr'     => traduz('Aprovação das OS de Troca pelos Promotores'),
            "codigo" => 'CCT-3150'
        ),
        /*array(
            'fabrica'   => (((in_array($login_fabrica,array(2,3,6,11,25,45,51,14,52,19,85,80)) or $login_fabrica > 87) or in_array($login_fabrica,$fabricas_contrato_lite))),
            'fabrica_no'=> array(114,126,127,131,132,134,136,137,138,140), // HD 907550, Bestway não está, Comimex tb não
            'icone'     => $icone["computador"],
            'link'      => 'os_intervencao.php',
            'titulo'    => 'OS com Intervenção Técnica',
            'descr'     => 'OSs com intervenção técnica da fábrica. Autoriza ou cancela o pedido de peças do posto ou efetua o reparo na fábrica.',
            "codigo" => 'CCT-3160'
        ),*/
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_intervencao_juridica.php',
            'titulo'    => traduz('Intervenção de OS Bloqueada'),
            'descr'     => traduz('Intervenção de OS Bloqueada (Jurídica)'),
            "codigo" => 'CCT-3170'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'os_intervencao_sap.php',
            'titulo'    => traduz('OS com Intervenção Técnica Garantia'),
            'descr'     => traduz('OSs com intervenção técnica para peças bloqueadas em garantia. Autoriza ou cancela o pedido de peças do posto.'),
            "codigo" => 'CCT-3180'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["computador"],
            'link'      => 'os_intervencao_sap.php',
            'titulo'    => traduz('OS com Intervenção SAP'),
            'descr'     => traduz('OSs com intervenção do SAP. Autoriza ou cancela o pedido de peças do posto ou efetua o reparo na fábrica.'),
            "codigo" => 'CCT-3190'
        ),
        array(
            'fabrica'   => array(3), /* 35521 69916 */
            'icone'     => $icone["computador"],
            'link'      => 'os_intervencao_carteira.php',
            'titulo'    => traduz('OS com Intervenção de Carteira'),
            'descr'     => traduz('OSs com intervenção de Carteira. Autoriza ou cancela o pedido de peças do posto / Troca ou Alteração da OS'),
            "codigo" => 'CCT-3200'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'cancela_pre_os.php',
            'titulo'    => traduz('Pré-OS Callcenter'),
            'descr'     => traduz('Pré-OS cadastrado no Callcenter. Consulta e cancela Pré-OS'),
            "codigo" => 'CCT-3210'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_consulta_lite_off_britania.php',
            'titulo'    => traduz('Altera OS off-line e Nota Fiscal'),
            'descr'     => traduz('Alteração da OS off-line e número da nota fiscal nas OSs'),
            "codigo" => 'CCT-3220'
        ),
        array(
            'fabrica'   => array(1,11,172),
            'icone'     => $icone["computador"],
            'link'      => 'os_intervencao_suprimentos.php',
            'titulo'    => traduz('OS com Intervenção Suprimentos'),
            'descr'     => traduz('OSs com intervenção de Suprimentos. Autoriza ou cancela o pedido de peças do posto.'),
            "codigo" => 'CCT-3230'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["computador"],
            'link'      => 'configuracoes.php',
            'titulo'    => traduz('E-mail do DAT (TESTE)'),
            'descr'     => traduz('Configuração do e-mail do DAT'),
            "codigo" => 'CCT-3240'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_interacao_pendente.php',
            'titulo'    => traduz('OSs Pendentes (TESTE)'),
            'descr'     => traduz('Relatório das OSs pendentes para o fabricante'),
            "codigo" => 'CCT-3250'
        ),
        array(
            'fabrica'   => array(19),
            'icone'     => $icone["consulta"],
            'link'      => 'os_consulta_sac.php',
            'titulo'    => traduz('Consulta OS SAC'),
            'descr'     => traduz('Consulta Ordens de Servido do SAC'),
            "codigo" => 'CCT-3260'
        ),
        array(
            'fabrica'   => array(19),
            'icone'     => $icallcentercone["relatorio"],
            'link'      => 'defeito_os_parametros.php',
            'titulo'    => traduz('Relatório de Ordens de Serviço'),
            'descr'     => traduz('Relatório de Ordens de Serviço lançadas no sistema.'),
            "codigo" => 'CCT-3270'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_cortesia_cadastro.php',
            'titulo'    => traduz('Cadastro Cortesia Ordens de Serviço'),
            'descr'     => traduz('Cadastro de Cortesia de Ordem de Serviços, no modo ADMIN'),
            "codigo" => 'CCT-3280'
        ),
        array(
            'fabrica'   => array(1,117),
            'icone'     => $icone["consulta"],
            'link'      => 'os_cortesia_parametros.php',
            'titulo'    => traduz('Consulta Cortesia Ordens de Serviço'),
            'descr'     => traduz('Consulta OS Cortesia Lançadas'),
            "codigo" => 'CCT-3290'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_cadastro_troca_black.php',
            'titulo'    => traduz('Cadastro OS Troca de Consumidor'),
            'descr'     => traduz('Cadastro de Troca interna p/ Consumidores (garantia/faturada ou cortesia)'),
            "codigo" => 'CCT-3300'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_revenda_troca.php',
            'titulo'    => traduz('Cadastro OS Troca de Revenda'),
            'descr'     => traduz('Cadastro de Troca interna p/ Revendas (garantia/faturada ou cortesia)'),
            "codigo" => 'CCT-3310'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_troca.php',
            'titulo'    => traduz('Relatório OS Troca'),
            'descr'     => traduz('Relatório de Ordem de Serviço de Troca.'),
            "codigo" => 'CCT-3320'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_cortesia.php',
            'titulo'    => traduz('Relatório de Cortesia OS'),
            'descr'     => traduz('Relatório de OS Cortesia em determinado mês.'),
            "codigo" => 'CCT-3330'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_revenda_cortesia.php',
            'titulo'    => traduz('Cadastro Cortesia OS de Revenda'),
            'descr'     => traduz('Cadastro de Cortesia de OS de Revenda, no modo ADMIN'),
            "codigo" => 'CCT-3340'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_cadastro_metais_sanitario_cortesia.php',
            'titulo'    => traduz('Cadastro Cortesia OS de Metais Sanitários'),
            'descr'     => traduz('Cadastro de Cortesia de OS de Metais Sanitários, no modo ADMIN'),
            "codigo" => 'CCT-3350'
        ),
        array(
            'fabrica'   => array(6),
            'icone'     => $icone["consulta"],
            'link'      => 'os_relatorio_aberta.php',
            'titulo'    => traduz('Consulta OS Aberta'),
            'descr'     => traduz('Consulta OS aberta a mais de 10 dias'),
            "codigo" => 'CCT-3360'
        ),
        array(
            'fabrica'   => array(6),
            'icone'     => $icone["computador"],
            'link'      => 'os_fechamento.php',
            'titulo'    => traduz('Fechamento de Ordem de Serviço'),
            'descr'     => traduz('Fechamento das Ordens de Serviços'),
            "codigo" => 'CCT-3370'
        ),
        array(
            'fabrica'   => $fabricas_contrato_lite,
            'icone'     => $icone["consulta"],
            'link'      => 'os_revenda_parametros.php',
            'titulo'    => traduz('Consulta OS - REVENDA'),
            'descr'     => traduz('Consulta OS Revenda Lançadas'),
            "codigo" => 'CCT-3380'
        ),
        // Telas específicas da Filizola
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_manutencao.php',
            'titulo'    => traduz('Cadastrar OS de Manutenção'),
            'descr'     => traduz('Lançamento de OS de Manutenção, com vários equipamentos por OS.'),
            "codigo" => 'CCT-3390'
        ),
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["consulta"],
            'link'      => 'os_manutencao_consulta_lite.php',
            'titulo'    => traduz('Consulta OS de Manutenção'),
            'descr'     => traduz('Consulta OS de Manutenção lançadas'),
            "codigo" => 'CCT-3400'
        ),
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["consulta"],
            'link'      => 'os_filizola_relatorio.php',
            'titulo'    => traduz('Faturamento - Valores da OS'),
            'descr'     => traduz('Consulta as OS com valores'),
            "codigo" => 'CCT-3410'
        ),
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["cadastro"],
            'link'      => 'lote_filizola.php',
            'titulo'    => traduz('Lotes de OS'),
            'descr'     => traduz('Lançamento de Lotes de OS'),
            "codigo" => 'CCT-3420'
        ),
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["computador"],
            'link'      => 'lote_conferencia_filizola.php',
            'titulo'    => traduz('Conferência de Lote'),
            'descr'     => traduz('Realiza a conferência da capa de Lote.'),
            "codigo" => 'CCT-3430'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_advertencia_bo.php',
            'titulo'    => traduz('Cadastro de advertência / boletim de ocorrência'),
            'descr'     => traduz('Cadastro de advertência e/ou boletim de ocorrência.'),
            "codigo" => 'CCT-3440'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_advertencia_bo.php',
            'titulo'    => traduz('Relatório de advertência / boletim de ocorrência'),
            'descr'     => traduz('Relatório de advertência e/ou boletim de ocorrência.'),
            "codigo" => 'CCT-3450'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_laudo_tecnico.php',
            'titulo'    => traduz('Relatório Laudo Técnico'),
            'descr'     => traduz('Relatório que mostra as Ordens de Serviço que possuem Laudo Técnico.'),
            'codigo'    => 'CCT-3460'
        ),
        array(
            'fabrica'   => array(86),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_garantia_estendida.php',
            'titulo'    => traduz('Relatório de OS\'s com produtos de garantia estendida'),
            'descr'     => traduz('Relatório que mostra as Ordens de Serviço que possuem produtos com garantia estendida.'),
            'codigo'    => 'CCT-3470'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_foto_serial.php',
            'titulo'    => traduz('Relatório OS com Fotos e Serial de LCD'),
            'descr'     => traduz('relatório para as OS\'s com upload de fotos e para OS\'s com serial de LCD.'),
            "codigo" => 'CCT-3480'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'troca_em_massa_new.php',
            'titulo'    => traduz('Troca em Massa'),
            'descr'     => traduz('Troca de Ordem de Serviço em Massa.'),
            "codigo" => 'CCT-3490'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'cancelar_os.php',
            'titulo'    => traduz('Cancelar O.S'),
            'descr'     => traduz('Cancelamento de Ordem de Serviço'),
            "codigo" => 'CCT-3491'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'cancelamento_aprovar.php',
            'titulo'    => traduz('Aprovar Cancelamento O.S'),
            'descr'     => traduz('Aprovação e consulta de O.S em cancelamento'),
            "codigo" => 'CCT-3492'
        ),
        array(
            'fabrica'   => array(141),
            'icone'     => $icone["relatorio"],
            'link'      => 'consulta_historico_os.php',
            'titulo'    => traduz('Consulta histórico da OS'),
            'descr'     => traduz('Consulta de Ordens de Serviço importadas para o Telecontrol'),
            "codigo" => 'CCT-3500'
        ),
        array(
            'fabrica'   => array(141,144),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_em_aberto.php',
            'titulo'    => traduz('Consulta OSs em Aberto'),
            'descr'     => traduz('Consulta de Ordens de Serviço não finalizadas'),
            "codigo" => 'CCT-3510'
        ),
        array(
            'fabrica'   => array(148, 161),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_venda.php',
            'titulo'    => traduz('Cadastro de Venda de Produto'),
            'descr'     => traduz('Cadastro de Venda de Produto'),
            "codigo"    => 'CCT-3520'
        ),
        array(
            'fabrica'   => array(148, 161),
            'icone'     => $icone["cadastro"],
            'link'      => 'consulta_venda.php',
            'titulo'    => traduz('Consulta de Venda de Produto'),
            'descr'     => traduz('Consulta de Venda de Produto'),
            "codigo"    => 'CCT-3530'
    	),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_produto_ativacao_automatica.php',
            'titulo'    => traduz('Relatório Produtos de Ativação Automática'),
            'descr'     => traduz('Consulta os Produtos de Ativação Automática'),
            "codigo" => 'CCT-3550'
        ),
        array(
            'fabrica'   => ($telecontrol_distrib == 't') ? [$login_fabrica] : array(35, 151, 174),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_visao_geral_os.php',
            'titulo'    => traduz('Relatório Visão Geral Ordens de Serviço'),
            'descr'     => traduz('Relatório Visão Geral Ordens de Serviço'),
            "codigo"    => 'CCT-3560'
        ),
        array(
            'fabrica'   => array(151,203),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_historico_os_mondial.php',
            'titulo'    => traduz('Consulta Ordens de Serviço Antigas'),
            'descr'     => traduz("Relatório das Ordens de Serviço Antigas Importadas do ERP Mondial. OS 's de 2001 a 2015"),
            "codigo"    => 'CCT-3570'
        ),
        array(
            'fabrica'   => array(151),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_congeladas.php',
            'titulo'    => traduz('Consulta Ordens de Serviço Congeladas'),
            'descr'     => traduz("Relatório das Ordens de Serviço Congeladas"),
            "codigo"    => 'CCT-3580'
        ),
        array(
            'fabrica'   => array(165,178),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_historico_os_legado.php',
            'titulo'    => traduz('Consulta Ordens de Serviço Antigas'),
            'descr'     => traduz("Relatório das Ordens de Serviço Antigas Importadas"),
            "codigo"    => 'CCT-3590'
    	),
   	array(
            'fabrica'   => array(183),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_historico_os_itatiaia.php',
            'titulo'    => traduz('Consulta Ordens de Serviço Legadas'),
            'descr'     => traduz("Relatório das Ordens de Serviço Legadas"),
            "codigo"    => 'CCT-3600'
        ),
        array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["computador"],
            'link'      => 'consulta_rpi.php',
            'titulo'    => traduz('Consulta RPI'),
            'descr'     => traduz("Consulta RPI"),
            "codigo"    => 'CCT-3610'
        ),
        array(
            'fabrica'   => array(160,174),
            'icone'     => $icone["relatorio"],
            'link'      => 'desempenho_posto.php',
            'titulo'    => traduz('Relatório de Desempenho do Posto'),
            'descr'     => traduz('Acompanhamento do desempenho do posto autorizado, de acordo com a satisfação do consumidor com o serviço'),
            "codigo"    => 'CCT-3620'
        ),
        array(
            'fabrica'   => array(175),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_jornada.php',
            'titulo'    => traduz('Relatório de Jornadas da Ordem de Serviço'),
            'descr'     => traduz('Acompanhamento de Jornadas da Ordem de Serviço'),
            "codigo"    => 'CCT-3630'
        ),
        array(
            'fabrica'   => array(72),
            'icone'     => $icone["relatorio"],
            'link'      => 'blacklist_serie.php',
            'titulo'    => traduz('Relatório Blacklist Série'),
            'descr'     => traduz('Lista de séries bloqueadas para garantia'),
            "codigo"    => 'CCT-3640'
        ),
        'link' => 'linha_de_separação'
    ),

    /**
     * Seção de ORDENS DE SERVIÇO DE REVENDA. GERAL.
     **/
    'secao4' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('REVENDAS - ORDENS DE SERVIÇO'),
            'fabrica_no' => array_merge(array(7,14,25,95,122,189), $fabricas_contrato_lite)
        ),
        array(
            'disabled'  => isset($novaTelaOsRevenda),
            'fabrica_no'=> array(1,15,122),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_revenda.php',
            'titulo'    => traduz('Cadastra OS - REVENDA'),
            'descr'     => traduz('Cadastro de Ordem de Serviço de revenda'),
            "codigo" => 'CCT-4010'
        ),
        array(
            'disabled'  => !isset($novaTelaOsRevenda),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_os_revenda.php',
            'titulo'    => ($login_fabrica == 178) ? traduz('Cadastra OS') : traduz('Cadastra OS - REVENDA'),
            'descr'     => traduz('Cadastro de Ordem de Serviço de revenda'),
            "codigo" => 'CCT-4010'
        ),
        array(
            'fabrica'=> array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_revenda_blackedecker.php',
            'titulo'    => traduz('Cadastra OS - REVENDA'),
            'descr'     => traduz('Cadastro de Ordem de Serviço de revenda'),
            "codigo" => 'CCT-4020'
        ),
        array(
            'fabrica'   => array(15),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_revenda_latina.php',
            'titulo'    => traduz('Cadastra OS - REVENDA'),
            'descr'     => traduz('Cadastro de Ordem de Serviço de revenda'),
            "codigo" => 'CCT-4030'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'os_revenda_parametros.php',
            'titulo'    => ($login_fabrica == 178) ? traduz('Consulta OS') : traduz('Consulta OS - REVENDA'),
            'descr'     => traduz('Consulta OS Revenda Lançadas'),
            "codigo" => 'CCT-4040'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'os_metais_consulta_lite.php',
            'titulo'    => traduz('Consulta OS - Metais Sanitários'),
            'descr'     => traduz('Consulta OS Metais Sanitários'),
            "codigo" => 'CCT-4050'
        ),
        array(
            'fabrica'   => $usa_sistema_de_revenda,
            'icone'     => $icone["computador"],
            'link'      => 'revenda_inicial.php',
            'titulo'    => traduz('SISTEMA DE REVENDA'),
            'descr'     => traduz('Sistema para controle de Revendas'),
            "codigo" => 'CCT-4060'
        ),

        array(
            'fabrica'   => array(30),
            'icone'     => $icone['consulta'],
            'link'      => 'libera_troca_os.php',
            'titulo'    => traduz('Liberaçao de OS para troca'),
            'descr'     => traduz('Liberaçao de troca de OS apos negociação com consumidor'),
            'codigo'    => 'CCT-4070'
        ),
        'link' => 'linha_de_separação'
    ),

    /**
     * Seção ATENDIMENTO TÉCNICO - Apenas LENOXX
     **/
    'secao5' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('ATENDIMENTO TÉCNICO'),
            'fabrica' => array(11, 172)
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'atendimento_tecnico_cadastra.php',
            'titulo'    => traduz('Cadastra Atendimento Técnico'),
            'descr'     => traduz('Cadastro de Atendimento Técnico'),
            "codigo" => 'CCT-5010'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'atendimento_tecnico_consulta.php',
            'titulo'    => traduz('Consulta Atendimento Técnico'),
            'descr'     => traduz('Consulta Atendimento Técnico'),
            "codigo" => 'CCT-5020'
        ),
        'link' => 'linha_de_separação'
    ),

    /**
     * Seção SEDEX - Apenas B&D (e HBTech, mas está inativa)
     **/
    'secao6' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('SEDEX - ORDENS DE SERVIÇO'),
            'fabrica' => array(1, 25)
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'sedex_cadastro.php',
            'titulo'    => traduz('Cadastra OS SEDEX'),
            'descr'     => traduz('Cadastro de Ordem de Serviços de SEDEX'),
            "codigo" => 'CCT-6010'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'sedex_parametros.php',
            'titulo'    => traduz('Consulta OS SEDEX'),
            'descr'     => traduz('Consulta OS Sedex Lançadas'),
            "codigo" => 'CCT-6020'
        ),
        'link' => 'linha_de_separação'
    ),

    /**
     * Seção de PEDIDOS - GERAL
     **/
    'secao7' => array (
        'secao' => array(
            'link'      => '#',
            'titulo'    => traduz('PEDIDOS DE PEÇAS') . iif(($login_fabrica== 1),traduz("/PRODUTOS")),
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["computador"],
            'link'      => 'pedido_altera_permissao.php',
            'titulo'    => traduz('Permissão de Cadastro de Pedido'),
            'descr'     => traduz('Permite selecionar o admin que poderá fazer exclusão no pedido.'),
            "codigo" => 'CCT-7010'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["cadastro"],
            'link'      => 'pedido_cadastro_altera.php',
            'titulo'    => traduz('Cadastro de Pedidos'),
            'descr'     => traduz('Cadastra pedidos de peças'),
            "codigo" => 'CCT-7020'
        ),
        array(
            'disabled'  => ($login_fabrica == 1 and !in_array($login_admin,array(112,232,245))),
            'fabrica_no'=> array_merge($fabricas_contrato_lite, array(11,14,43,66,148,152,172,180,181,182)),
            'icone'     => $icone["cadastro"],
            'link'      => 'pedido_cadastro.php',
            'titulo'    => traduz('Cadastro de Pedidos'),
            'descr'     => traduz('Cadastra pedidos de peças'),
            "codigo" => 'CCT-7030'
        ),
        array(
            'fabrica'=> array(148),
            'icone'     => $icone["cadastro"],
            'link'      => 'http://fvweb.yanmar.com.br/',
            'titulo'    => traduz('Cadastro de Pedidos'),
            'descr'     => traduz('Cadastra pedidos de peças'),
            "codigo" => 'CCT-7030',
            "blank" => true
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'pedido_cadastro_blackedecker.php',
            'titulo'    => traduz('Cadastro de Pedidos (em TESTE)'),
            'descr'     => traduz('Cadastra pedidos de peças (em TESTE)'),
            "codigo" => 'CCT-7040'
        ),
        array(
            'fabrica'   => array(3,80),
            'icone'     => $icone["consulta"],
            'link'      => 'nf_relacao.php',
            'titulo'    => traduz('Consulta de Notas Fiscais'),
            'descr'     => traduz('Listar as Notas Fiscais dos Postos Autorizados'),
            "codigo" => 'CCT-7050'
        ),
        array(
            'fabrica'   => array(1,30),
            'icone'     => $icone["computador"],
            'link'      => 'pedido_bloqueio.php',
            'titulo'    => traduz('Personalizar tela de pedido'),
            'descr'     => traduz('Bloqueia o site para os postos não fazerem pedidos por um período. Opção para cadastrar período fiscal. Opção para cadastrar período de pedido de promoção.'),
            "codigo" => 'CCT-7060'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'pedido_parametros.php',
            'titulo'    => traduz('Consulta Pedidos de Peças').iif(($login_fabrica==1),traduz('/Produtos')),
            'descr'     => traduz('Consulta pedidos efetuados por postos autorizados.'),
            "codigo" => 'CCT-7070'
        ),
        array(
            'fabrica'   => array(171),
            'icone'     => $icone["consulta"],
            'link'      => 'peca_nao_cadastra.php',
            'titulo'    => traduz('Relatório de peças não cadastradas'),
            'descr'     => traduz('Relatório de peças marcadas como não cadastradas na FN que foram lançadas em Ordem de Serviço e estão pendente de pedido'),
            "codigo" => 'CCT-7071'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["consulta"],
            'link'      => 'callcenter_relatorio_pedido.php',
            'titulo'    => traduz('Consulta Pedidos Pendentes').iif(($login_fabrica==1),traduz('/Produtos')),
            'descr'     => traduz('Consulta pedidos em aberto.'),
            "codigo" => 'CCT-7080'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["consulta"],
            'link'      => 'callcenter_relatorio_pedido_peca.php',
            'titulo'    => traduz('Consulta Pedidos Pendentes Detalhado').iif(($login_fabrica==1),traduz('/Produtos')),
            'descr'     => traduz('Consulta pedidos em aberto listando as peças.'),
            "codigo" => 'CCT-7090'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["upload"],
            'link'      => 'pedido_nao_importado.php',
            'titulo'    => traduz('Pedidos não importados'),
            'descr'     => traduz('Permite o envio de um arquivo contendo os pedidos que não foram importados por alguma inconsistência, fazendo com que eles sejam marcados como "não-importados" permitindo sua alteração.'),
            "codigo" => 'CCT-7100'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'pedido_relatorio.php',
            'titulo'    => traduz('Pedidos da Loja Virtual'),
            'descr'     => traduz('Este relatório exibe as informações dos pedidos feito na loja virtual e os admins responsáveis.'),
            "codigo" => 'CCT-7110'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'pedido_relatorio_shop.php',
            'titulo'    => traduz('Pedidos da AT-SHOP'),
            'descr'     => traduz('Este relatório exibe as informações dos pedidos feito na AT-SHOP'),
            "codigo" => 'CCT-7120'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'lv_inicial.php',
            'titulo'    => traduz('Criar Pedido da Loja Virtual'),
            'descr'     => traduz('Permite que um admin crie um pedido para o posto na Loja Virtual, sendo responsável pelo mesmo.'),
            "codigo" => 'CCT-7130'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'peca_loja_virtual.php',
            'titulo'    => traduz('Peças da loja virtual'),
            'descr'     => traduz('Relatório de peças da loja virtual disponibiliza a peça, quantidade, valor, e Obs.'),
            "codigo" => 'CCT-7140'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["consulta"],
            'link'      => 'pedido_cancelado_consulta.php',
            'titulo'    => traduz('Consulta Pedidos Cancelados'),
            'descr'     => traduz('Consulta peças canceladas automaticamente em pedidos, devido ao fechamento da Ordem de Serviço antes do faturamento das peças.'),
            "codigo" => 'CCT-7150'
        ),
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pedidos_filizola.php',
            'titulo'    => traduz('Relatório de Pedidos por OS'),
            'descr'     => traduz('Relatório de pedidos referentes a OS de um determinado periodo, com valor de peças, mão-de-obra e mais.'),
            "codigo" => 'CCT-7160'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'pedido_parametros_blackedecker_acessorio.php',
            'titulo'    => traduz('Consulta Pedidos de Acessórios'),
            'descr'     => traduz('Consulta pedidos de Acessórios efetuados por PA autorizados.'),
            "codigo" => 'CCT-7170'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(1),
            'icone'     => $icone["upload"],
            'link'      => 'faturamento_importa_blackedecker_new.php',
            'titulo'    => traduz('Importar Faturamento'),
            'descr'     => traduz('Importação dos arquivos de faturamento (retorno).'),
            "codigo" => 'CCT-7180'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["upload"],
            'link'      => 'faturamento_importa_estoque.php',
            'titulo'    => traduz('Importar Estoque'),
            'descr'     => traduz('Importação dos arquivos de peças faturadas. Faturamento<br /> das peças de ESTOQUE.'),
            "codigo" => 'CCT-7190'
        ),
        array(
            'disabled'  => !$fabrica_fatura_manualmente,
            'icone'     => $icone["computador"],
            'link'      => 'pedido_peca_fatura_manual_consulta.php',
            'titulo'    => traduz('Faturar Pedido Manualmente'),
            'descr'     => traduz('Faturamento de pedidos com peças marcadas como<br> Faturar Manualmente'),
            "codigo" => 'CCT-7200'
        ),
        array(
            'disabled'  => !$fabrica_fatura_manualmente,
            'icone'     => $icone["upload"],
            'link'      => 'pedido_peca_fatura_manual_exportar_consulta.php',
            'titulo'    => traduz('Exportar Pedido Manualmente'),
            'descr'     => traduz('Exportacao de pedidos com peças marcadas como <br>Faturar Manualmente'),
            "codigo" => 'CCT-7210'
        ),
        array(
            'fabrica'   => array(10),
            'icone'     => $icone["computador"],
            'link'      => '#',
            'titulo'    => traduz('Pendência de Peças'),
            'descr'     => '',
            "codigo" => 'CCT-7220'
        ),
        array(
            'fabrica'   => array(30),
            'icone'     => $icone["upload"],
            'link'      => 'atualiza_pedido.php',
            'titulo'    => traduz('Atualização de pedidos'),
            'descr'     => traduz('Atualiza pedidos através do upload de arquivo'),
            "codigo" => 'CCT-7230'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["computador"],
            'link'      => 'pedido_gera_manual.php',
            'titulo'    => traduz('Pedidos de Peças Remessa e NTP'),
            'descr'     => traduz('Gerar/Exporta pedidos NTP'),
            "codigo" => 'CCT-7240'
        ),
        array(
            'fabrica' => $fabrica_cancela_pedido_massivo,
            'icone'   => $icone['computador'],
            'link'    => 'pedido_cancela_multiplo.php',
            'titulo'  => traduz('Cancelar Pedidos'),
            'descr'   => traduz('Cancelamento de pedidos de peças, troca de produto, etc. por data, posto ou código de peça.'),
            'codigo' => 'CCT-7250'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["computador"],
            'link'      => 'relatorio_uso_pecas.php',
            'titulo'    => traduz('Inventário de Peças para Bonificação'),
            'descr'     => traduz('Inventário de Peças por posto para geração de pedido bonificado'),
            "codigo"    => 'CCT-7260'
        ),
        array(
            "fabrica" => array(169, 170),
            "icone"   => $icone["consulta"],
            "link"    => "relatorio_pedidos_pendentes_pecas_faltantes.php",
            "titulo"  => traduz("Pedidos pendentes com peça faltante"),
            "descr"   => traduz("Relatório de pedidos pendentes com peça faltante"),
            "codigo"  => "CCT-7270"
        ),
        array(
            "fabrica" => array(151),
            "icone"   => $icone["consulta"],
            "link"    => "exportar_pedidos_lote.php",
            "titulo"  => "Exportar pedidos em lote",
            "descr"   => "Relatório para exportar pedidos em lote",
            "codigo"  => "CCT-7280"
        ),
        'link' => 'linha_de_separação'
    ),

    /**
     * Seção PEÇAS - Apenas INTELBRAS
     **/
    'secao8' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('INFORMAÇÕES SOBRE PEÇAS'),
            'fabrica' => array(14)
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'peca_consulta_dados.php',
            'titulo'    => traduz('Dados Cadastrais da Peça'),
            'descr'     => traduz('Consulta todos os dados cadastrais da peça.'),
            "codigo" => "CAD-5495"
        ),
        'link' => 'linha_de_separação'
    ),

    /**
     * Seção DIVERSOS - Menos INTELBRAS
     **/
    'secao9' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('DIVERSOS'),
            'fabrica_no' => array(14)
        ),
        array(
            'fabrica_no'=> array(2,189),
            'icone'     => $icone["acesso"],
            'link'      => 'posto_login.php',
            'titulo'    => traduz('Logar como Posto'),
            'descr'     => traduz('Acesse o sistema como se fosse o Posto Autorizado'),
            "codigo" => 'CCT-9010'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'posto_consulta.php',
            'titulo'    => ($login_fabrica == 189) ? traduz('Consulta de Representantes/Revendas') : traduz('Consulta Postos'),
            'descr'     => ($login_fabrica == 189) ? traduz('Consulta cadastro de Representantes/Revendas') : traduz('Consulta cadastro de postos autorizados.'),
            "codigo" => 'CCT-9020'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_tecnico_posto.php',
            'titulo'    => traduz('Relação de Técnico Posto'),
            'descr'     => traduz('Relação dos técnicos cadastrados pelo posto'),
            "codigo" => 'CCT-9030'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["consulta"],
            'link'      => 'posto_consulta_pais.php',
            'titulo'    => traduz('Consulta Postos por País'),
            'descr'     => traduz('Consulta dos dados de postos da América Latina.'),
            "codigo" => 'CCT-9040'
        ),
        array(
            'fabrica_no' => array(189),
            'icone'     => $icone["consulta"],
            'link'      => iif(($login_fabrica == 1),
            'tabela_precos_blackedecker_consulta.php',
            'preco_consulta.php'),
            'titulo'    => traduz('Tabela de Preços'),
            'descr'     => traduz('Consulta tabela de preços de peças'),
            "codigo" => 'CCT-9050'
        ),
        array(
            'fabrica_no' => array(189),
            'fabrica'   => array($fabrica_upload_preco),
            'icone'     => $icone["upload"],
            'link'      => 'upload_tabela_preco.php',
            'titulo'    => traduz('Importa Tabela de Preços'),
            'descr'     => traduz('Atualização da Tabela de Preços.'),
            "codigo" => 'CCT-9060'
        ),
        array(
            'fabrica'   => array(147),
            'icone'     => $icone["upload"],
            'link'      => 'upload_peca.php',
            'titulo'    => traduz('Importa Peças de-para '),
            'descr'     => traduz('Upload de Peças de-para.'),
            "codigo" => 'CCT-9071'
        ),
        array(
            'fabrica'   => $fabrica_upload_lista_basica,
            'icone'     => $icone["upload"],
            'link'      => 'upload_lista_basica.php',
            'titulo'    => traduz('Importa Lista Básica'),
            'descr'     => traduz('Atualização da Lista Básica.'),
            "codigo" => 'CCT-9061'
        ),
        array(
            'fabrica_no' => array(189),
            'icone'     => $icone["consulta"],
            'link'      => 'lbm_consulta.php',
            'titulo'    => traduz('Lista Básica'),
            'descr'     => traduz('Consulta lista básica de peças por produto.'),
            "codigo" => 'CCT-9070'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'linha_consulta.php',
            'titulo'    => traduz('Linhas de produtos'),
            'descr'     => traduz('Consulta as linhas de produtos'),
            "codigo" => 'CCT-9080'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'produto_consulta.php',
            'titulo'    => traduz('Produtos'),
            'descr'     => traduz('Consulta os produtos cadastrados.'),
            "codigo" => 'CCT-9090'
        ),
        array(
            'fabrica_no' => array(189),
            'icone'     => $icone["consulta"],
            'link'      => 'depara_consulta.php',
            'titulo'    => 'De &raquo; Para',
            'descr'     => traduz('Consulta PEÇAS com').(' De &raquo; Para'),
            "codigo" => 'CCT-9100'
        ),
        array(
		'icone'     => $icone["consulta"],
		'fabrica_no' => array(163,177,189),
            'link'      => 'peca_fora_linha_consulta.php',
            'titulo'    => traduz('Peças fora de linha'),
            'descr'     => traduz('Consulta as PEÇAS que estão fora de linha.'),
            "codigo" => 'CCT-9110'
        ),
        array(
            'fabrica_no' => array(189),
            'icone'     => $icone["consulta"],
            'link'      => 'comunicado_produto_consulta.php',
            'titulo'    => traduz('Vista Explodida e Comunicados'),
            'TITLEattrs'=> $com_style,
            'descr'     => traduz('Consulta vista explodida, diagramas, esquemas e comunicados.'),
            "codigo" => 'CCT-9120'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'peca_consulta_dados.php',
            'titulo'    => traduz('Dados Cadastrais da Peça'),
            'descr'     => traduz('Consulta todos os dados cadastrais da peça.'),
            "codigo" => 'CCT-9130'
        ),
        array(
            'fabrica_no' => array(189),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_sem_pedido.php',
            'titulo'    => traduz('OS não geraram pedidos'),
            'descr'     => traduz('Ordens de Serviços que não geraram pedidos de peças.'),
            "codigo" => 'CCT-9140'
        ),
        array(
            'fabrica'   => array(80),
            'icone'     => $icone["consulta"],
            'link'      => 'relatorio_extrato.php',
            'titulo'    => traduz('Extratos de Posto Autorizado'),
            'descr'     => traduz('Consulta de extrato de posto autorizado.'),
            "codigo" => 'CCT-9150'
        ),
        array(
            'fabrica'   => array(81,114),
            'icone'     => $icone["upload"],
            'link'      => 'venda_upload.php',
            'titulo'    => traduz('Upload de Venda Produto'),
            'descr'     => traduz('Upload de arquivo de venda de produto.'),
            "codigo" => 'CCT-9160'
        ),
        array(
            'fabrica'   => array(40),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_devolucao_obrigatoria.php',
            'titulo'    => traduz('Devolução Obrigatória'),
            'descr'     => traduz('Peças que devem ser devolvidas para a Fábrica constando em Ordens de Serviço.'),
            "codigo" => 'CCT-9170'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["consulta"],
            'link'      => 'pesquisa_suggar.php',
            'titulo'    => traduz('Pesquisa Satisfação'),
            'descr'     => traduz('Pesquisa de Satisfação do Cliente (Controle de qualidade).'),
            "codigo" => 'CCT-9180'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["consulta"],
            'link'      => 'pesquisa_suggar_consulta.php',
            'titulo'    => traduz('Consulta Pesquisa Satisfação'),
            'descr'     => traduz('Resultado da pesquisa de qualidade.'),
            "codigo" => 'CCT-9190'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["upload"],
            'link'      => 'upload_importa_suggar.php',
            'titulo'    => traduz('Atualização de Faturamento'),
            'descr'     => traduz('Envio do arquivo de faturamento de pedidos.'),
            "codigo" => 'CCT-9200'
        ),
        #HD 159888
        array(
            'fabrica'   => $fabrica_movimiento_estoque_posto,
            'icone'     => $icone["consulta"],
            'link'      => 'estoque_posto_movimento.php',
            'titulo'    => traduz('Movimentação Estoque'),
            'descr'     => traduz('Visualização da movimentação do estoque do posto autorizado.'),
            "codigo" => 'CCT-9210'
        ),
        array(
            'fabrica'   => $fabrica_estoque_cfop,
            'icone'     => $icone["cadastro"],
            'link'      => 'estoque_cfop.php',
            'titulo'    => traduz('Estoque CFOP'),
            'descr'     => traduz('Tipos de nota (CFOP) que serão utilizadas para alimentar o estoque.'),
            "codigo" => 'CCT-9220'
        ),
        array(
            'fabrica'   => array(15),
            'icone'     => $icone["cadastro"],
            'link'      => 'estoque_minimo.php',
            'titulo'    => traduz('Estoque Mínimo'),
            'descr'     => traduz('Cadastro de Coeficiente de estoque mínimo por estado.'),
            "codigo" => 'CCT-9230'
        ),
        array(
            'fabrica'   => array(7,24),
            'icone'     => $icone["cadastro"],
            'link'      => 'peca_inventario.php',
            'titulo'    => traduz('Inventário de Peças'),
            'descr'     => traduz('Cadastro do inventário de peças do posto autorizado'),
            "codigo" => 'CCT-9240'
        ),
        array(
            'fabrica'   => array(7,10,43,66),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_pedido.php',
            'titulo'    => traduz('Aprovação de Pedido'),
            'descr'     => traduz('Aprovação de Pedidos de Cliente'),
            "codigo" => 'CCT-9250'
        ),
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["upload"],
            'link'      => 'gera_pedido_cliente.php',
            'titulo'    => traduz('Geração de Pedido'),
            'descr'     => traduz('Geração de Pedidos de Cliente'),
            "codigo" => 'CCT-9260'
        ),
        array(
            'fabrica'   => array(25, 50, 51, 59),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_posto_fabrica.php',
            'titulo'    => traduz('Relatório de Postos Autorizados'),
            'descr'     => traduz('Relatório que exibe todos os postos autorizados'),
            "codigo" => 'CCT-9270'
        ),
        array(
            'fabrica'   => array(51),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_peca_pendente_gama.php',
            'titulo'    => traduz('Relatório de Peças Pendentes'),
            'descr'     => traduz('Relatório de peças pendentes nas ordens de serviços.'),
            "codigo" => 'CCT-9280'
        ),
        array(
            'fabrica'   => array(45),
            'icone'     => $icone["consulta"],
            'link'      => 'relatorio_peca_bloqueada.php',
            'titulo'    => traduz('Peças Bloqueadas Para Garantia'),
            'descr'     => traduz('Consulta de Peças Bloqueadas para Garantia.'),
            "codigo" => 'CCT-9290'
        ),
        array(
            'fabrica'   => array(2),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_cliente_garantia_estendida.php',
            'titulo'    => traduz('Relatório garantia estendida'),
            'descr'     => traduz('Consulta de clientes que cadastraramm produto para garantia estendida.'),
            "codigo" => 'CCT-9300'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'peca_produto.php',
            'titulo'    => traduz('Consulta de Peças'),
            'descr'     => traduz('Consulta por uma peça e traz todos os produtos em que a peça é utilizada.'),
            "codigo" => 'CCT-9310'
        ),
        array(
            'fabrica'   => array(15),
            'icone'     => $icone["relatorio"],
            'link'      => 'estoque_seguranca_manual.php',
            'titulo'    => traduz('Estoque Segurança Manual'),
            'descr'     => traduz('Cadastro e Controle de estoque de segurança manual.'),
            "codigo" => 'CCT-9320'
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'callcenter_pergunta_cadastro.php',
            'titulo'    => traduz('Cadastro de Perguntas do Callcenter'),
            'descr'     => traduz('Para que as frases padrões do callcenter sejam alteradas.'),
            "codigo" => 'CCT-9330'
        ),
        array(
            'fabrica'   => array(141,144),
            'icone'     => $icone["consulta"],
            'link'      => 'estoque_posto.php',
            'titulo'    => traduz('Estoque de peças do Posto Autorizado'),
            'descr'     => traduz('Cadastro e Consulta de estoque de peças do Posto Autorizado'),
            "codigo"    => 'CCT-9340'
        ),
        array(
            'fabrica'   => (in_array($login_fabrica, $posto_estoque)) ? array($login_fabrica) : array(),
            'fabrica_no'=> array(161,164,191,193),
            'icone'     => $icone["consulta"],
            'link'      => 'posto_estoque.php',
            'titulo'    => traduz('Estoque de peças do Posto Autorizado'),
            'descr'     => traduz('Cadastro e Consulta de estoque de peças do Posto Autorizado'),
            "codigo"    => 'CCT-9350'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["consulta"],
            'link'      => 'relatorio_movimentacao_estoque.php',
            'titulo'    => traduz('Relatório de movimentação de estoque'),
            'descr'     => traduz('Consulta da movimentação de estoque do posto autorizado'),
            "codigo"    => 'CCT-9360'
        ),
        array(
            'fabrica'   => array(101),
            'icone'     => $icone["cadastro"],
            'link'      => 'upload_codigo_rastreio.php',
            'titulo'    => traduz('Upload Código Rastreio'),
            'descr'     => traduz('Cadastro de Código de Rastreio'),
            "codigo"    => 'CCT-9370'
        ),
        array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["cadastro"],
            'link'      => 'agenda_bloqueio.php',
            'titulo'    => traduz('Bloqueio de Agendamento'),
            'descr'     => traduz('Cadastro bloqueio de agendamento'),
            "codigo"    => 'CCT-9380'
        ),
        array(
            'fabrica'   => $atendimentoML != true ? array() : array($login_fabrica),
            'icone'     => $icone['consulta'],
            'link'      => 'relatorio_erros_integracao.php',
            'titulo'    => traduz('Relatório de erros Mercado Livre'),
            'descr'     => traduz('Erros da integração com Mercado Livre'),
            'codigo'    => 'CCT-9390'
    	),
    	array( 
	    'icone'   => $icone["relatorio"], 
	    'link'    => 'relatorio_respostas_atendimento_posto.php', 
	    'titulo'  => 'Relatório Postos Atendendo', 
	    'descr'   => 'Relatório que mostra os Postos que estão realizando Atendimento ao Público', 
	    "codigo"  => 'CCT-9400' 
	    ),
        'link' => 'linha_de_separação'
    ),

    /**
     * Seção RELATÓRIOS CALL-CENTER (¿?)
     **/
    'secaoA' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('RELATÓRIOS CALL-CENTER'),
            'fabrica'     => array(6,114,11,3, 172),
        ),
        array(
            'fabrica'   => array(6,114),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_reclamacao_por_estado.php',
            'titulo'    => traduz('Reclamações por estado'),
            'descr'     => traduz('Histórico de atendimentos por estado.'),
            "codigo" => 'CCT-A010'
        ),
        // array(
        //  'fabrica_no'    => array(122,81,114,124,123),
        //  'icone'     => $icone["cadastro"],
        //  'link'      => 'callcenter_pergunta_cadastro.php',
        //  'titulo'    => 'Cadastro de Perguntas do Callcenter',
        //  'descr'     => 'Para que as frases padrões do callcenter sejam alteradas.',
        //  "codigo" => 'CCT-A020'
        // ),
        array(
            'fabrica'   => array(3, 6, 11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_intervencao.php',
            'titulo'    => traduz('Relatório de Intervenção'),
            'descr'     => traduz('OS com intervenção da Assistência Técnica da Fábrica / SAP'),
            "codigo" => 'CCT-A030'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_produto_serie_mascara.php',
            'titulo'    => traduz('Relatório de Máscara de Número de Série'),
            'descr'     => traduz('Relatório de Máscara de Número de Série.'),
            "codigo" => 'CCT-A040'
        ),
        array(
            'fabrica_no' => array(141,144),
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_intervencao_km.php',
            'titulo'    => traduz('Relatório de Intervenção de KM'),
            'descr'     => traduz('OS com intervenção de deslocamento (KM).'),
            "codigo" => 'CCT-A050'
        ),
        'link' => 'linha_de_separação'
    ),

    /**
     * Seção GERENCIAMENTO DE REVENDAS - Apenas Britânia
     **/
    'secaoB' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('GERENCIAMENTO DE REVENDAS'),
            'fabrica'    => array(3)
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'os_revenda_pesquisa.php',
            'titulo'    => traduz('Pesquisa de OS Revenda'),
            'descr'     => traduz('Pesquisa as OS em aberto em uma revenda, pelo seu CNPJ.'),
            "codigo" => 'CCT-B010'
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_revenda.php',
            'titulo'    => traduz('OS em Aberto por Revenda'),
            'descr'     => traduz('Relatório com Ordens de Serviços em aberto, listando pelas 20 maiores revendas que abriram Ordens de Serviços.'),
            "codigo" => 'CCT-B020'
        ),
        'link' => 'linha_de_separação'
    ),
);

