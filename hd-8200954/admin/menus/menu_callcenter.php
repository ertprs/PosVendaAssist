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

//define se ir� usar a nova tela para abertura de ordem de servi�o
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
    hd-1149884 -> Para as f�bricas que tiverem o par�metro adicional fabrica_padrao='t', a tela:
        admin/os_consulta_procon.php
    N�o ser�o mais utilizadas.
*/

if ($fabrica_padrao=='t'){
    $arr_fabrica_padrao = array($login_fabrica);
}

// Menu CALLCENTER

/*
 *
 * Bloqueio de tela, Callcenter (Implanta��o Mondial)
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
 * Bloqueio de tela, Callcenter (Implanta��o Mondial)
 * Data: 20/11/2015
 * FIM
 *
 */

/**
* Intera��o na Ordem de Servi�o
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
* FIM Intera��o na Ordem de Servi�o
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
            "titulo"    => traduz('Consulta Ordens de Servi�o'),
            "link"      => 'os_consulta_lite.php',
            "descr"     => traduz('Consulta OS Lan�adas'),
            "codigo" => 'CCT-0010'
        ),
        array(
            'disabled'  => (!$admin_consulta_os),
            "link"      => 'linha_de_separa��o',
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
            'descr'     => traduz('Consulta atendimentos j� lan�ados'),
            "codigo" => 'CCT-0030'
        ),
        array(
            'fabrica'   => array(25),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_pendente_interativo.php',
            'titulo'    => traduz('Pend�ncia Call-Center'),
            'descr'     => traduz('Exibe todos os atendimentos do Call-Center com pend�ncia.'),
            "codigo" => 'CCT-0040'
        ),
        array(
            'fabrica'   => array(25),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_consulta_atendimento.php',
            'titulo'    => traduz('Relat�rio Call-Center'),
            'descr'     => traduz('Relat�rio de callcenter simples (permite baixar o relat�rio em XLS).'),
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
            'titulo'    => traduz('Monitor de Ordens de Servi�o'),
            'descr'     => traduz('Monitor de Ordens de Servi�o'),
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
            'titulo'    => traduz('Cadastro de PR� OS'),
            'descr'     => traduz('Cadastrar Pr� Ordem de servi�o para Posto Autorizado'),
            "codigo" => 'CCT-0080'
        ),
        array(
            'fabrica_no'=> array(25, 95),
            'icone'     => $icone["consulta"],
            'link'      => 'callcenter_parametros_new.php',
            'titulo'    => traduz('Consulta Atendimentos Call-Center'),
            'descr'     => traduz('Consulta atendimentos j� lan�ados'),
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
            'descr'     => traduz('Para que as frases padr�es do callcenter sejam alteradas.'),
            "codigo" => 'CCT-0110'
        ),
        array(
            'fabrica_no'=> array(25, 95),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_pendente.php',
            'titulo'    => traduz('Pend�ncia Call-Center'),
            'descr'     => traduz('Exibe todos os atendimentos do Call-Center com pend�ncia.'),
            "codigo" => 'CCT-0120'
        ),
        // HD 674943
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["computador"],
            'link'      => 'hd_chamado_postagem.php',
            'titulo'    => traduz('Autoriza��o de Postagem'),
            'descr'     => traduz('Consulta, Autoriza��o e Reprova��o de postagens solicitadas pelos atendentes do CallCenter'),
            "codigo" => 'CCT-0150'
        ),
        array(
            'fabrica'   => array(14,43,66),
            'icone'     => $icone["computador"],
            'link'      => 'pre_os_cadastro_sac.php',
            'titulo'    => traduz('Abertura de Pr�-Os - SAC'),
            'descr'     => traduz('Abre Pr� OS para um Posto Autorizado.'),
            "codigo" => 'CCT-0160'
        ),
        array(
            'fabrica'   => array(6),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_pendente_procon.php',
            'titulo'    => traduz('Pend�ncia Call-Center (Procon / Jec)'),
            'descr'     => traduz('Exibe todos os atendimentos do Call-Center com pend�ncia.'),
            "codigo" => 'CCT-0170'
        ),
        array(
            'fabrica'   => array(2),
            'icone'     => $icone["computador"],
            'link'      => 'pesquisa_acompanhamento.php',
            'titulo'    => traduz('Acompanhamento de Assist�ncia T�cnica'),
            'descr'     => traduz('Acompanhamento de situa��o do posto autorizado.'),
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
            'titulo'    => traduz('Cadastro de Perguntas T�cnicas'),
            'descr'     => traduz('Cadastro de perguntas t�cnicas no callcenter.'),
            "codigo"    => 'CCT-0210'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'consulta_pergunta_tecnica.php',
            'titulo'    => traduz('D�vidas T�cnicas'),
            'descr'     => traduz('Consulta de d�vidas t�cnicas e respostas.'),
            "codigo"    => 'CCT-0220'
        ),
        array(
            'fabrica'   => array_merge(array(30, 114, 151,160,169,170, 174, 183), $fabricas_replica_einhell),
            'icone'     => $icone["relatorio"],
            'link'      => 'manutencao_hd_chamado_lote.php',
            'titulo'    => traduz('Manuten��o de Atendimentos em Lote'),
            'descr'     => ($login_fabrica == 30) ? traduz('Realizar transfer�ncia de atendimentos') : traduz('Realizar, transfer�ncia de atendimentos, altera��o de situa��o, altera��o de proced�ncia e intera��o em atendimentos.'),
            "codigo" => 'CCT-0200'
        ),
        array(
            'fabrica'   => $fabrica_atendimentos,
            'icone'     => $icone["consulta"],
            'link'      => 'callcenter_atendimento.php',
            'titulo'    => traduz('Acompanhamento de Atendimentos Abertos'),
            'descr'     => traduz('Mostra os atendimentos que est�o sendo realizados pelos atendentes.'),
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
            'titulo'    => traduz('Solicita��o de Cheque'),
            'descr'     => traduz('Permite Cadastrar e Consultar as solicita��es de cheque'),
            "codigo"    => 'CCT-2470'
        ),
        array(
            'fabrica' => [1],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_solicitacoes_postagem.php',
            'titulo'  => traduz('Solicita��es de Postagem'),
            'descr'   => traduz('Relat�rio de solicita��es de postagem por intervalo de datas.'),
            'codigo'  => 'CCT-5100'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["relatorio"],
            'link'      => 'monitor_tecnico.php',
            'titulo'    => traduz('Monitor de t�cnicos'),
            'descr'     => traduz('Monitor de t�cnicos'),
            "codigo" => 'CCT-0230'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["relatorio"],
            'link'      => 'conferencia_integracao_mobile.php',
            'titulo'    => traduz('Monitor de Interface Mobile/Web'),
            'descr'     => traduz('Mostra OSs que retornaram erro de integra��o entre Mobile/Web'),
            "codigo" => 'CCT-0240'
        ),
        array(
            'fabrica'   => array(158,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'calendario_rotina.php',
            'titulo'    => ($login_fabrica != 158) ? traduz('Monitor de rotinas automatizadas') : traduz('Monitor Interfaces de Ordens de Servi�o'),
            'descr'     => (($login_fabrica != 158) ? traduz('Monitor de rotinas automatizadas') : traduz('Monitor Interfaces de Ordens de Servi�o')).traduz(' agendadas e demonstrativo de resultados das execu��es'),
            "codigo"    => 'CCT-0250'
        ),
        array(
            'fabrica'   => array(169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_acompanhamento.php',
            'titulo'    => traduz('Acompanhamento de Ordens de Servi�o'),
            'descr'     => traduz('Acompanhamento de exporta��o de ordens de servi�o para o ERP cliente'),
            "codigo"    => 'CCT-0260'
        ),
        array(
            'fabrica'   => ($atendimentoFacebook && $atendimentoIG) ? array($login_fabrica) : array(),
            'icone'     => $icone['computador'],
            'link'      => 'dashboard_social.php',
            'titulo'    => traduz('Dashboard M�dias Sociais'),
            'descr'     => traduz('Monitoramento de intera��es em M�dias Sociais'),
            'codigo'    => 'CCT-0270'
        ),
        array(
            'fabrica'   => ($arrPermissoesAdm["suporte_tecnico"] == "t") ? [169,170] : [],
            'icone'     => $icone["cadastro"],
            'link'      => 'helpdesk_posto_autorizado_novo_atendimento.php',
            'titulo'    => "Cadastro Help-Desk Suporte T�cnico",
            'descr'     => traduz('Cadastro dos Help-desks de suporte t�cnico.'),
            "codigo" => 'CCT-0280'
        ),
        array(
            'fabrica'   => ($arrPermissoesAdm["suporte_tecnico"] == "t") ? [169,170] : [],
            'icone'     => $icone["consulta"],
            'link'      => 'helpdesk_posto_autorizado_listar.php',
            'titulo'    => "Consulta Help-Desk Suporte T�cnico",
            'descr'     => traduz('Consulta dos Help-desks de suporte t�cnico.'),
            "codigo" => 'CCT-0290'
        ),
        'link' => 'linha_de_separa��o'
    ),

    /* Se��o INFORMATIVO MENSAL, apenas BLACK */
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
            'titulo'    => traduz('Edi��o de Informativos'),
            'descr'     => traduz('Edi��o de Informativos'),
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
            'titulo'    => traduz('Destinat�rios'),
            'descr'     => traduz('Destinat�rios'),
            "codigo" => 'CCT-1040'
        ),
        'link' => 'linha_de_separa��o'
    ),

    /**
     * RELAT�RIOS RELATIVOS AO CALL-CENTER. GERAL.
     **/
    'secao2' => array(
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('CALL-CENTER RELAT�RIOS'),
            'fabrica_no' => array(25, 95)
        ),
        array(
            'fabrica_no'=> array(25, 95),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_atendimento.php',
            'titulo'    => traduz('Relat�rio de Atendimentos'),
            'descr'     => traduz('Relat�rio de quantidade de atendimento e o status.'),
            "codigo" => 'CCT-2010'
        ),
        array(
            'fabrica'=> array(81),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimentos_solucoes.php',
            'titulo'    => traduz('Relat�rio Atendimentos X Solu��es'),
            'descr'     => traduz('Relat�rio Atendimentos X Solu��es'),
            "codigo" => 'CCT-2011'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimento_orientacao_uso.php',
            'titulo'    => traduz('Relat�rio de Orienta��o de Uso'),
            'descr'     => traduz('Relat�rio de Atendimentos x Orienta��o de Uso.'),
            "codigo" => 'CCT-2020'
        ),
        array(
            'fabrica'   => array(52),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_atendimento.php',
            'titulo'    => traduz('Relat�rio de Atendimentos por POSTO'),
            'descr'     => traduz('Relat�rio que exibe a quantidade de atendimentos <br /> por posto selecionado no per�odo filtrado.'),
            "codigo" => 'CCT-2030'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pre_os_britania_simplificado.php',
            'titulo'    => traduz('Relat�rio de Pr� OS'),
            'descr'     => traduz('Relat�rio Pr� Ordem de servi�o para Posto Autorizado.'),
            "codigo" => 'CCT-2040'
        ),
        array(
            'fabrica_no'=> array(24, 25, 52, 95,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_atendimento_atendente.php',
            'titulo'    => traduz('Relat�rio de Atendimentos por Atendente'),
            'descr'     => traduz('Relat�rio de quantidade de atendimento por atendente.'),
            "codigo" => 'CCT-2050'
        ),
        array(
            'fabrica'=> array(169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_atendimento_atendente.php',
            'titulo'    => traduz('Relat�rio de atendimento x intera��es'),
            'descr'     => traduz('Relat�rio de intera��es efetuadas e atendimentos abertos por atendente.'),
            "codigo" => 'CCT-2050'
        ),
        array(
            'fabrica_no'=> array(25, 52, 95),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_periodo_atendimento.php',
            'titulo'    => traduz('Relat�rio Per�odo de Atendimentos'),
            'descr'     => traduz('Relat�rio de Per�odo de Atendimento, informa a quantidade de dias que o atendimento levou para ser resolvido.'),
            "codigo" => 'CCT-2060'
        ),
        array(
            'fabrica_no'=> array(24, 25, 52, 95,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_defeito.php',
            'titulo'    => traduz('Relat�rio de Reclama��es'),
            'descr'     => traduz('Relat�rio com os 10 defeitos mais reclamados.'),
            "codigo" => 'CCT-2070'
        ),
        array(
            'fabrica'=> array(169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_defeito.php',
            'titulo'    => traduz('Relat�rio de Defeitos Reclamados'),
            'descr'     => traduz('Relat�rio com os 10 defeitos mais reclamados.'),
            "codigo" => 'CCT-2070'
        ),
        array(
            'fabrica_no'=> array(25, 95),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_defeito_produto.php',
            'titulo'    => traduz('Relat�rio de Reclama��es X Produtos'),
            'descr'     => traduz('Relat�rio de reclama��es por produtos.'),
            "codigo" => 'CCT-2080'
        ),
        array(
            'fabrica'   => array($fabrica_upload_preco),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_produto_defeito_reclamado.php',
            'titulo'    => traduz('Relat�rio Produto X Defeito Reclamado'),
            'descr'     => traduz('Relat�rio de produtos por defeito reclamado'),
            "codigo" => 'CCT-2090'
        ),
        array(
            'fabrica'=> array(162),
            'icone'     => $icone["relatorio"],
            'link'      => 'novos_relatorios_callcenter.php',
            'titulo'    => traduz('Novos Relat�rios Callcenter'),
            'descr'     => traduz('Novos Relat�rios Callcenter'),
            "codigo" => 'CCT-2091'
        ),
        array(
            'fabrica'   => array(85),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisa_satisfacao.php',
            'titulo'    => traduz('Relat�rio de Pesquisa de Satisfa��o'),
            'descr'     => traduz('Relat�rio de Satisfa��o dos Clientes Atendidos pelo SAC.'),
            "codigo" => 'CCT-2100'
        ),
        array(
            'fabrica'   => array(85,94),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimento_pesquisa_satisfacao.php',
            'titulo'    => traduz('Relat�rio Atendimentos x Pesquisa Satisfa��o'),
            'descr'     => traduz('Relat�rio Total de Atendimentos x Atendimentos<br /> com Pesquisa de Satisfa��o'),
            "codigo" => 'CCT-2110'
        ),
        array(
            'fabrica_no'=> array(24, 25, 52, 95),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_defeito_familia.php',
            'titulo'    => traduz('Relat�rio de Reclama��es X Fam�lia'),
            'descr'     => traduz('Relat�rio de reclama��es por fam�lia de produtos.'),
            "codigo" 	=> 'CCT-2120'
        ),
        array(
            'fabrica_no'=> array(24, 25, 52, 95,169,170,184,200),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_produto_natureza.php',
            'titulo'    => traduz('Relat�rio de Produtos X Natureza'),
            'descr'     => traduz('Relat�rio de natureza por produtos.'),
            "codigo" 	=> 'CCT-2130'
        ),
        array(
            'fabrica'	=> array(94),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_posto_natureza.php',
            'titulo'    => traduz('Relat�rio de Posto X Natureza'),
            'descr'     => traduz('Relat�rio de posto por produtos.'),
            "codigo" 	=> 'CCT-2140'
        ),

        array(
            'fabrica_no'=> array_merge(array(25, 95), $fabricas_contrato_lite),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_interacoes.php',
            'titulo'    => traduz('Relat�rio maior tempo entre intera��es'),
            'descr'     => traduz('Relat�rio que exibe o maior periodo sem intera��es<BR> com o consumidor.'),
            "codigo" => 'CCT-2150'
		),
        array(
            'fabrica_no'=> array_merge(array(25, 95), $fabricas_contrato_lite),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_natureza.php',
            'titulo'    => traduz('Relat�rio de Natureza de Chamado'),
            'descr'     => traduz('Relat�rio que exibe a quantidade de atendimento<BR> por Natureza.'),
            "codigo" => 'CCT-2160'
		),
		array(
			'fabrica' => array(30),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_tempo_atendente.php',
            'titulo'    => traduz('Relat�rio tempo atendente'),
            'descr'     => traduz('Relat�rio que exibe o tempo de cada atendente ficou respons�vel por atendimento.'),
            "codigo" => 'CCT-2170'
        ),
        array(
            'fabrica'   => array(5),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_indicacao_posto.php',
            'titulo'    => traduz('Relat�rio de Indica��o de Posto'),
            'descr'     => traduz('Relat�rio que exibe a quantidade de Indica��o de Posto.'),
            "codigo" => 'CCT-2170'
        ),
        array(
            'fabrica'   => array(5),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_historico_csv.php',
            'titulo'    => traduz('Hist�rico do Call-Center'),
            'descr'     => traduz('Relat�rio com atendimentos e hist�rico, em formato texto.'),
            "codigo" => 'CCT-2180'
        ),
        array(
            'disabled'  => true, //HD 684395
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'call_center_relatorio_posto_indicacao_suggar.php',
            'titulo'    => traduz('Relat�rio de Indica��o de Posto'),
            'descr'     => traduz('Relat�rio que exibe a quantidade de Indica��o de Posto.'),
            "codigo" => 'CCT-2190'
        ),
        array(
            'fabrica_no'=> array_merge(array(25, 95), $fabricas_contrato_lite),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_atendente.php',
            'titulo'    => traduz('Relat�rio por Atendentes'),
            'descr'     => traduz('Relat�rio que exibe a quantidade de atendimentos por atendente'),
            "codigo" => 'CCT-2200'
        ),
        array(
            'fabrica'   => array(80),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimento_procon.php',
            'titulo'    => traduz('Relat�rio Procon'),
            'descr'     => traduz('Relat�rio dos atendimentos de Procon.'),
            "codigo" => 'CCT-2210'
        ),
        array(
            'fabrica_no'=> array(25, 95,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_mailing.php',
            'titulo'    => traduz('Relat�rio de Mailing'),
            'descr'     => traduz('Relat�rio que exibe nome e e-mail dos consumidores cadastrados no atendimento do SAC'),
            "codigo" => 'CCT-2220'
        ),
        array(
            'fabrica'=> array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_quantidade_os_mensal.php',
            'titulo'    => traduz('Relat�rio de Quantidade de OS Mensal/DR'),
            'descr'     => traduz('Relat�rio de Quantidade de OS Mensal/DR'),
            "codigo" => 'CCT-2221'
        ),
        array(
            'fabrica'=> array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_remessa_ect_xls.php',
            'titulo'    => traduz('Relat�rio de Remessa ECT'),
            'descr'     => traduz('Relat�rio de Remessa ECT'),
            "codigo" => 'CCT-2222'
        ),
        array(
            'fabrica'=> array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_evolucao_contratual.php',
            'titulo'    => traduz('Relat�rio de Evolu��o Contratual'),
            'descr'     => traduz('Relat�rio de Evolu��o Contratual'),
            "codigo" => 'CCT-2223'
        ),
        array(
            'fabrica'=> array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_os_finalizada.php',
            'titulo'    => traduz('Relat�rio de OS\'s Finalizadas'),
            'descr'     => traduz('Relat�rio de OS\'s Finalizadas'),
            "codigo" => 'CCT-2224'
        ),
        array(
            'fabrica'=> array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_qualidade_atendimento.php',
            'titulo'    => traduz('Relat�rio de Qualidade de Atendimento'),
            'descr'     => traduz('Relat�rio de Qualidade de Atendimento'),
            "codigo" => 'CCT-2224'
        ),
        array(
            'fabrica'   => array(52),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_atendimento_familia.php',
            'titulo'    => traduz('Relat�rio de Atendimento por Fam�lia'),
            'descr'     => traduz('Relat�rio de Atendimento por Fam�lia'),
            "codigo" => 'CCT-2230'
        ),
        /*array(
            'fabrica'   => array(52),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_chamado.php',
            'titulo'    => 'Relat�rio de Pesquisas em Atendimentos',
            'descr'     => 'Relat�rio das Pesquisas que foram feitas com os Clientes atrav�s de Atendimentos.',
            "codigo" => 'CCT-2240'
        ),*/
        array(
            'fabrica'   => array(30,52,85,94,129,138,145,151,152,161,180,181,182),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_chamado_new.php',
            'titulo'    => traduz('Relat�rio de Pesquisas em Atendimentos'),
            'descr'     => traduz('Relat�rio das Pesquisas que foram feitas com os Clientes atrav�s de Atendimentos.'),
            "codigo" => 'CCT-2250'
        ),
        array(
            'fabrica'   => array_merge(array(160), $fabricas_replica_einhell),
			'fabrica_no' => [35],
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_chamado_new.php',
            'titulo'    => traduz('Relat�rio de Pesquisas'),
            'descr'     => traduz('Relat�rio das Pesquisas'),
            "codigo" => 'CCT-2250'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_chamado_new_black.php',
            'titulo'    => traduz('Novo Relat�rio de Pesquisas em Atendimentos'),
            'descr'     => traduz('Novo Relat�rio das Pesquisas que foram feitas com os Clientes atrav�s de Atendimentos.'),
            "codigo" => 'CCT-2250'
        ),
        array(
            'fabrica'   => array(15),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_mailing_os.php',
            'titulo'    => traduz('Relat�rio de Mailing - OS'),
            'descr'     => traduz('Relat�rio que exibe nome e e-mail dos consumidores de OSs abertas'),
            "codigo" => 'CCT-2260'
        ),
        array(
            'fabrica'   => array(51),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_troca_coleta_postagem.php',
            'titulo'    => traduz('Relat�rio de OSs Troca de Produto'),
            'descr'     => traduz('Relat�rio que exibe as OS de troca com N� de Coleta/Postagem'),
            "codigo" => 'CCT-2270'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_enviadas_laudo_tecnico.php',
            'titulo'    => traduz('Relat�rio de Pesquisa de Satisfa��o enviada por e-mail'),
            'descr'     => traduz('Relat�rio que exibe as pesquisas de satisfa��o enviadas por e-mail'),
            "codigo" => 'CCT-2270'
        ),
        array(
            'fabrica'   => array(51),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_perfil_consumidor.php',
            'titulo'    => traduz('Relat�rio de Perfil do Consumidor'),
            'descr'     => traduz('Relat�rio baseado na Pesquisa sobre Perfil do Consumidor'),
            "codigo" => 'CCT-2280'
        ),
        array(
            'fabrica'   => array(72),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_nf_troca.php',
            'titulo'    => traduz('Relat�rio de OS por status da nota'),
            'descr'     => traduz('Relat�rio que exibe as OS por status da nota'),
            "codigo" => 'CCT-2290'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_posto_atualizacao.php',
            'titulo'    => traduz('Relat�rio de Atualiza��o de Postos'),
            'descr'     => traduz('Relat�rio com rela��o de postos com dados cadastrais Atualizados'),
            "codigo" => 'CCT-2300'
        ),
        array(
            'fabrica'   => array(24,151,169,170),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_estatisticas.php',
            'titulo'    => traduz('Estatisticas de Callcenter'),
            'descr'     => traduz('Estatisticas com vis�o geral de atendimentos'),
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
            'descr'     => traduz('Gera arquivo de backup em formato <span title="Dados separados por ponto e v�rgula (;)">CSV</span> para ser exportado para Access.'),
            "codigo" => 'CCT-2330'
        ),
        array(
            'fabrica'   => array(2),
            'icone'     => $icone["relatorio"],
            'link'      => 'acompanhamento_consulta.php',
            'titulo'    => traduz('Relat�rio Situa��o das Assist�ncias'),
            'descr'     => traduz('Relat�rio que exibe o hist�rico de acompanhamento<br>das assist�ncias.'),
            "codigo" => 'CCT-2340'
        ),
        array(
            'fabrica'   => array(11, 172),//HD 56947
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_at_procon.php',
            'titulo'    => traduz('Relat�rio Classifica��o Posto'),
            'descr'     => traduz('Relat�rio que mostra as classifica��es dos<br>postos no atendimento(AT/Procon).'),
            "codigo" => 'CCT-2350'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_duvidas.php',
            'titulo'    => traduz('Relat�rio D�vidas'),
            'descr'     => traduz('Relat�rio que mostra as as d�vidas <br/> de produtos registradas em chamados.'),
            "codigo" => 'CCT-2360'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_motivo_callcenter.php',
            'titulo'    => traduz('Relat�rio Motivo Atendimento'),
            'descr'     => traduz('Relat�rio que mostra os motivos <br/> dos atendimentos abertos.'),
            "codigo" => 'CCT-2370'
        ),
        array(
            'fabrica'   => array(94),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_chamados_callcenter.php',
            'titulo'    => traduz('Relat�rio Chamados Call-Center'),
            'descr'     => traduz('Relat�rio de Chamados do Call-Center.'),
            "codigo" => 'CCT-2380'
        ),
        array(
            'fabrica'   => array(115,116,117,122,81,114,124,123,126,127,128,129),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_reclamacao_por_estado.php',
            'titulo'    => traduz('Reclama��es por estado'),
            'descr'     => traduz('Hist�rico de atendimentos por estado.'),
            "codigo" => 'CCT-2390'
        ),
        array(
            'fabrica'   => array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_atendimento_fora_garantia.php',
            'titulo'    => traduz('Atendimentos fora de garantia'),
            'descr'     => traduz('Relat�rio dos atendimentos que foram abertos para produtos fora de garantia'),
            "codigo" => 'CCT-2410'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_somente_os_revenda.php',
            'titulo'    => traduz('Relat�rio OS Revenda'),
            'descr'     => traduz('Relat�rio de OS de Revenda'),
            "codigo" => 'CCT-2420'
        ),
        array(
            'fabrica'   => array(74, 11, 162, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_produtividade.php',
            'titulo'    => traduz('Relat�rio de produtividade'),
            'descr'     => traduz('Relat�rio de produtividade por atendente'),
            "codigo" => 'CCT-2430'
        ),
        array(
            'fabrica'   => array(74, 11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_registro_processo.php',
            'titulo'    => traduz('Relat�rio de registro de processo'),
            'descr'     => traduz('Relat�rio de registro de processo'),
            "codigo" => 'CCT-2440'
        ),
        array(
            'fabrica'   => array(85),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_ambev.php',
            'titulo'    => traduz('Relat�rio AMBEV'),
            'descr'     => traduz('Relat�rio AMBEV'),
            "codigo" => 'CCT-2450'
        ),
        array(
            'fabrica'   => array(85,88,94,129,145,151,161),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pesquisas_enviadas.php',
            'titulo'    => traduz('Relat�rio Pesquisas Enviadas'),
            'descr'     => traduz('Relat�rio de pesquisas de satisfa��o enviadas ao consumidor'),
            "codigo"    => 'CCT-2470'
        ),
        array(
            'fabrica_no'=> array_merge(array(25, 52, 95), $fabricas_contrato_lite),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_produto.php',
            'titulo'    => traduz('Relat�rio de Atendimento por produto'),
            'descr'     => traduz('Relat�rio de atendimento por produtos'),
            "codigo"    => 'CCT-2480'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_total_garantia.php',
            'titulo'    => traduz('Relat�rio Total de Garantia'),
            'descr'     => traduz('Relat�rio total de OS de garantia'),
            "codigo"    => 'CCT-2490'
        ),
        array(
            'fabrica' => array(164),
            'icone'   => $icone["relatorio"],
            'link'    => 'callcenter_perfil_consumidor.php',
            'titulo'  => traduz('Relat�rio de Perfil do Consumidor'),
            'descr'   => traduz('Relat�rio que exibe as informa��es de Perfil do Consumidor por data e regi�o.'),
            "codigo"  => 'CCT-2490'
        ),
        array(
            'fabrica'   => array_merge(array(59,160), $fabricas_replica_einhell),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_origem.php',
            'titulo'    => traduz('Relat�rio Call-Center X Origem'),
            'descr'     => traduz('Relat�rio de Call-Center por Origem'),
            "codigo"    => 'CCT-9280'
        ),
        array(
            'fabrica'   => array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_consulta_lite_interativo.php?fale_conosco=true',
            'titulo'    => traduz('Atendimentos Fale Conosco'),
            'descr'     => traduz('Relat�rio de callcenter com informa do Fale Conosco'),
            "codigo"    => 'CCT-2490'
        ),

        array(
            'fabrica'   => array(35),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_troca_produtos.php',
            'titulo'    => traduz('Relat�rio de OS de Troca de Produtos'),
            'descr'     => traduz('Relat�rio de OS que foram efetuadas troca de produto'),
            "codigo"    => 'CCT-2495'
        ),
        // array( /*HD - 3956227*/
        //     'fabrica'   => array(3,11,80,101,104,151,169,170,172),
        //     'icone'     => $icone["relatorio"],
        //     'link'      => 'relatorio_sms.php',
        //     'titulo'    => 'Relat�rio de Envio SMS',
        //     'descr'     => 'Relat�rio de Envio SMS',
        //     "codigo"    => 'CCT-2500'
        // ),
        array(
            'fabrica'   => array(1,3,11,35,80,101,104,123,151,157,160,167,169,172,174,186,203),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_sms_detalhado.php',
            'titulo'    => traduz('Relat�rio de Envio SMS e Respostas'),
            'descr'     => traduz('Relat�rio mostra detalhadamento o envio de SMS e se teve respostas via SMS'),
            "codigo"    => 'CCT-2501'
        ),
        array(
            'fabrica'   => array(81),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_digitadas.php',
            'titulo'    => traduz('Relat�rio OS Digitadas'),
            'descr'     => traduz('Relat�rio de OSs Digitadas, filtrando somente pela data de digita��o.'),
            "codigo"    => 'CCT-2510'
        ),
        array(
            'fabrica'   => array(151),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_sigep.php',
            'titulo'    => traduz('Relat�rio SIGEP'),
            'descr'     => traduz('Relat�rio com informa��es do consumidor para enviar ao correios.'),
            "codigo"    => 'CCT-2510'
        ),
        array(
            'fabrica'   => ($telecontrol_distrib == 't') ? [$login_fabrica] : array(151,169,170,174),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_visao_geral_atendimentos.php',
            'titulo'    => traduz('Relat�rio Vis�o Geral'),
            'descr'     => traduz('Relat�rio mostra uma vis�o geral dos atendimentos por admin e provid�ncias.'),
            "codigo"    => 'CCT-2520'
	    ),
        array(
            'fabrica'   => (isset($novaTelaOs)) ? array($login_fabrica) : array(104,139),
            'fabrica_no'   => array(189),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_produtos_trocados.php',
            'titulo'    => traduz('Relat�rio Produtos Trocados'),
            'descr'     => traduz('Relat�rio Produtos Trocados por O.S'),
            "codigo"    => 'CCT-2560'
        ),
        array(
            'fabrica'   => array(152,180,181,182),
            'icone'     => $icone["relatorio"],
            'link'      => 'distribuicao_atendimento_categoria.php',
            'titulo'    => traduz('Distribui��o de Atendimentos por Categoria'),
            'descr'     => traduz('Consulta de Atendimentos Distribu�dos por Categoria'),
            "codigo"    => 'CCT-2561'
        ),
        array(
            'fabrica'   => array(160),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_intencao_compra.php',
            'titulo'    => traduz('Relat�rio de Inten��o de Compra'),
            'descr'     => traduz('Relat�rio de pe�as com inten��o de compra - Pedido'),
            "codigo"    => 'CCT-2562'
        ),
        array(
            'fabrica'   => array(151,174),
            'icone'     => $icone["relatorio"],
            'link'      => $link_historioco,
            'titulo'    => traduz('Consulta hist�rico de Atendimentos'),
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
            'titulo'    => traduz('Relat�rio do Helpdesk do Posto Autorizado'),
            'descr'     => traduz('Relat�rio dos atendimentos abertos no Helpdesk do Posto Autorizado'),
            "codigo"    => 'CCT-2550'
        ),
        array(
            "fabrica"   => array(1),
            "icone"     => $icone['relatorio'],
            "link"      => "relatorio_pesquisa_grafico.php",
            "titulo"    => traduz("Gr�ficos de Pesquisa de Satisfa��o"),
            "descr"     => traduz("Gr�ficos e amostras dos resultados das Pesquisas de Satisfa��o"),
            "codigo"    => "CCT-2560"
        ),
        array(
            'fabrica'=> array(81),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_relatorio_produto_marca.php',
            'titulo'    => traduz('Relat�rio de Atendimento por produto ou marca'),
            'descr'     => traduz('Relat�rio de atendimento por produtos ou marcas'),
            "codigo" => 'CCT-2570'
        ),
        array(
            'fabrica'=> array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_tempo_medio_atendimento.php',
            'titulo'    => traduz('Tempo M�dio de Atendimento'),
            'descr'     => traduz('Tempo M�dio de Atendimento.'),
            "codigo" => 'CCT-2580'
        ),
        array(
            'fabrica'=> array(74),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_tempo_status.php',
            'titulo'    => traduz('Tempo Entre Status de Atendimento'),
            'descr'     => traduz('Relat�rio de medi��o de quanto tempo o atendimento ficou em um certo status.'),
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
            'titulo'    => traduz('Relat�rio B�nus Posto'),
            'descr'     => traduz('Relat�rio B�nus Posto'),
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
            'titulo'    => traduz('Relat�rio de Devolu��es'),
            'descr'     => traduz('Relat�rio das devolu��es'),
            "codigo" => 'CCT-2630'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["relatorio"],
            'link'      => 'consulta_devolucoes.php',
            'titulo'    => traduz('Consulta Devolu��es F�brica'),
            'descr'     => traduz('Consulta de devolu��es'),
            "codigo" => 'CCT-2640'
        ),
        array(

            'fabrica'   => array(85),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_servico_diferenciado.php',
            'titulo'    => traduz('Relat�rio de Bonifica��o'),
            'descr'     => traduz('Consulta de bonifica��es por servi�o diferenciado'),
            "codigo" => 'CCT-2650'
        ),
        array(
            'fabrica'   => array(85),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_garantia_contratual.php',
            'titulo'    => traduz('Relat�rio de Garantia Contratual'),
            'descr'     => traduz('Relat�rio de Atendimentos de Garantia Contratual'),
            "codigo" => 'CCT-2680'
        ),
        array(
            'fabrica'   => array(169, 170),
            'icone'     => $icone["relatorio"],
            'link'      => 'pesquisa_nps_tracksale.php',
            'titulo'    => traduz('Pesquisa NPS Tracksale'),
            'descr'     => traduz('Relat�rio de atendimentos finalizados para pesquisa de satisfa��o'),
            "codigo" => 'CCT-2660'
        ),
        array(
            'fabrica'   => array(169, 170),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_tecnicos_cadastrados.php',
            'titulo'    => traduz('Relat�rio de T�cnicos Cadastrados'),
            'descr'     => traduz('Relat�rio de T�cnicos de Postos'),
            "codigo" => 'CCT-2661'
        ),
        array(
            'fabrica'   => array(90),
            'icone'     => $icone["relatorio"],
            'link'      => 'callcenter_tempo_medio_atendimento.php',
            'titulo'    => traduz('Dashboard para Tempo M�dio de Atendimento'),
            'descr'     => traduz('Dashboard para Tempo M�dio de Atendimento'),
            "codigo" => 'CCT-2670'
        ),

        array(
            'fabrica'   => array(174),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_sla_callcenter.php',
            'titulo'    => traduz('Relat�rio SLA callcenter'),
            'descr'     => traduz('Relat�rio detalhado dos atendimentos callcenter'),
            "codigo" => 'CCT-2680'
        ),
        $integracaoTelefonia == true ? array(
            'fabrica'   => array($login_fabrica),
            'icone'     => $icone['relatorio'],
            'link'      => 'relatorio_atendentes_telefonia.php',
            'titulo'    => traduz('Rel�torio de Atendimentos Telefonia'),
            'descr'     => traduz('Relat�rio de tempo de atendimentos da Telefonia'),
            'codigo'    => 'CCT-2690'
            ) : array(),        
         array(
             'fabrica'   => ($integracaoTelefonia) ? [$login_fabrica] : [],
             'icone'     => $icone["relatorio"],
             'link'      => 'relatorio_fila_telefonia.php',
             'titulo'    => traduz('Relat�rio Telefonia por Fila'),
             'descr'     => traduz('Relat�rio de atendimentos da Telefonia por Fila'),
             "codigo" => 'CCT-2700'
         ),
        array(
            'fabrica'   => ($telecontrol_distrib == 't') ? [$login_fabrica] : [],
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_interacoes.php',
            'titulo'    => traduz('Relat�rio Intera��es'),
            'descr'     => traduz('Relat�rio detalhado das intera��es'),
            "codigo" => 'CCT-2710'
        ),
        array(
            'fabrica'   => array(183),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_agendamentos_cancelados.php',
            'titulo'    => 'Relat�rio Agendamento Cancelado',
            'descr'     => 'Relat�rio de OS com agendamento cancelado',
            "codigo" => 'CCT-2720'
        ),
        array(
            'fabrica'   => array(183),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_agendamentos_pendentes.php',
            'titulo'    => 'Relat�rio Agendamentos Pendentes',
            'descr'     => 'Relat�rio de OS com agendamento pendente',
            "codigo" => 'CCT-2730'
        ),
        array(
            'fabrica' => [1],
            'icone'   => $icone["relatorio"],
            'link'    => 'relatorio_dashboard_helpdesk_posto.php',
            'titulo'  => 'Relat�rio Dashboard HelpDesk Posto',
            'descr'   => 'Relat�rio Dashboard de HelpDesk Posto x F�brica',
            "codigo"  => 'CCT-2740'
        ),
        array(
            'fabrica' => [1],
            'icone'   => $icone["relatorio"],
            'link'    => 'dashboard_helpdesk_posto.php',
            'titulo'  => 'Dashboard HelpDesk Posto',
            'descr'   => 'Dashboard de HelpDesk Posto x F�brica',
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
            'link'      => 'linha_de_separa��o',
        ),
        
    ),

    /**
     * Se��o de ORDENS DE SERVI�O. GERAL.
     **/
    'secao3' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('ORDENS DE SERVI�O'),
            'fabrica_no' => array(25,95,189)
        ),
        array(
            'fabrica'   => array(($login_fabrica != 14 || in_array($login_admin, array(260,261,262,263)))),
            'fabrica_no'=> array(25,95),
            'icone'     => $icone["cadastro"],
            'link'      => $link3010,
            'titulo'    => traduz('Cadastra Ordens de Servi�o'),
            'descr'     => traduz('Cadastro de Ordem de Servi�os, no modo ADMIN'),
            "codigo"    => 'CCT-3010'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'upload_pesquisa_satisfacao.php',
            'titulo'    => traduz('Upload de Pesquisa (Outros Pa�ses)'),
            'descr'     => traduz('Upload de Pesquisa de satisfa��o'),
            "codigo"    => 'CCT-3011'
        ),
        array(
            'fabrica'   => array(152,180,181,182),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_os_entrega_tecnica.php',
            'titulo'    => traduz('Cadastra Ordens de Servi�o - Entrega T�cnica'),
            'descr'     => traduz('Cadastro de Ordem de Servi�os - Entrega T�cnica, no modo ADMIN'),
            "codigo"    => 'CCT-3482'
        ),
        array(
            'fabrica'   => $os_cadastro_revisao,
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_os_revisao.php',
            'titulo'    => traduz('Cadastra Ordens de Servi�o - Revis�o'),
            'descr'     => traduz('Cadastro de Ordem de Servi�os - Revis�o, no modo ADMIN'),
            "codigo"    => 'CCT-3480'
        ),
        array(
            'fabrica'   => $fabrica_admin_anexaNF,
            'icone'     => $icone["anexo"],
            'link'      => 'nota_foto_cadastro.php',
            'titulo'    => traduz('Anexa NF �s Ordens de Servi�o'),
            'descr'     => traduz('Permite anexar arquivos �s Ordens de Servi�o'),
            "codigo" => 'CCT-3020'
        ),
        array(
            'fabrica'   => $fabrica_relatorio_os_aberto,
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_aberta.php',
            'titulo'    => traduz('Relat�rio de Ordens de Servi�o em aberto'),
            'descr'     => traduz('Mostra as Ordens de Servi�o que est�o em aberto.'),
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
            'titulo'    => traduz('Interven��o de KM'),
            'descr'     => traduz('OS para aprova��o de KM do posto autorizado ao consumidor'),
            "codigo" => 'CCT-3050'
        ),
        array(
            'fabrica'   => array(3,25,81,95,114),
            'fabrica_no'=> array_merge($fabricas_contrato_lite, array(50,86,81,114,124,123,124,127,128,129)),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_atendimento_domicilio.php',
            'titulo'    => traduz('Interven��o de KM'),
            'descr'     => traduz('OS para aprova��o de KM do posto autorizado ao consumidor'),
            "codigo" => 'CCT-3060'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(3),
            'icone'     => $icone["consulta"],
            'link'      => 'os_parametros.php',
            'titulo'    => traduz('Consulta ANTIGA'),
            'descr'     => traduz('Liberado at� �s 15 horas de hoje. Problemas de performance no site est�o relacionados com pesquisas muito extensas.'),
            "codigo" => 'CCT-3070'
        ),
        array(
            'fabrica_no'=> array(25,95),
            'icone'     => $icone["consulta"],
            'link'      => iif(($login_fabrica == 1),
            'os_consumidor_consulta.php',
            'os_consulta_lite.php'),
            'titulo'    => traduz('Consulta Ordens de Servi�o'),
            'descr'     => traduz('Consulta OS Lan�adas'),
            "codigo" => 'CCT-3080'
        ),
        array(
            "fabrica" => (in_array($login_fabrica, $array_interacao_os) || $interacaoOsPosto),
            'fabrica_no'=> array(193),
            "icone"  => $icone["relatorio"],
            "link"   => "relatorio_interacao_os.php",
            "titulo" => traduz("Intera��es em Ordem de Servi�o"),
            "descr"  => traduz("Relat�rio de intera��es em Ordem de Servi�o: Novas Intera��es do Posto Autorizado, OS com �ltima intera��o do PA e OS com �ltima intera��o da F�brica"),
            "codigo" => "CCT-3540"
        ),
        array(
            'fabrica'   => array(7,45),
            'icone'     => $icone["computador"],
            'link'      => 'os_fechamento.php',
            'titulo'    => traduz('Fechamento de Ordem de Servi�o'),
            'descr'     => traduz('Fechamento de Ordem de Servi�o'),
            "codigo" => 'CCT-3090'
        ),
        array(
            'fabrica_no'=> array(25,95),
            'icone'     => $icone["consulta"],
            'link'      => (isset($novaTelaOs)) ? 'relatorio_os_excluida.php' : 'os_parametros_excluida.php',
            'titulo'    => traduz('Consulta OS Exclu�da'),
            'descr'     => traduz('Consulta Ordens de Servi�o exclu�das do sistema'),
            "codigo" => 'CCT-3100'
        ),
        array(
            'fabrica' => array(164),
            'icone'   => $icone["relatorio"],
            'link'    => 'tempo_atendimento_os.php',
            'titulo'  => traduz('Tempo de Atendimento de OS'),
            'descr'   => traduz('Consulta o tempo de atendimento das OSs por per�odos espec�ficos'),
            "codigo"  => 'CCT-3200'
        ),
        array(
            'fabrica'=> array(42,3),
            'icone'     => $icone["consulta"],
            'link'      => 'os_consulta_procon.php',
            'titulo'    => traduz('Consulta OS Procon'),
            'descr'     => traduz('Consulta Ordens de Servi�o do Procon'),
            "codigo" => 'CCT-3110'
        ),
        array(
            'fabrica'   => array(35),
            'icone'     => $icone["computador"],
            'link'      => 'produto_troca_lote.php',
            'titulo'    => traduz('Troca de Produtos Criticos em Lote'),
            'descr'     => traduz('Troca de produto de OS de produtos cr�ticos'),
            "codigo" => 'CCT-3120'
        ),
        array(
            'fabrica'   => $verifica_ressarcimento_troca,
            'icone'     => $icone["consulta"],
            'link'      => 'consulta_os_troca_ressarcimento.php',
            'titulo'    => traduz('Consulta OS - Troca em Lote'),
            'descr'     => traduz('Consulta Ordens de Servi�o - Troca em Lote'),
            "codigo" => 'CCT-3130'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_os_cortesia.php',
            'titulo'    => traduz('Aprova OS de Cortesia'),
            'descr'     => traduz('Aprova��o das OS de Cortesia pelos Promotores'),
            "codigo" => 'CCT-3140'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_troca_os.php',
            'titulo'    => traduz('Aprova OS de Troca'),
            'descr'     => traduz('Aprova��o das OS de Troca pelos Promotores'),
            "codigo" => 'CCT-3150'
        ),
        /*array(
            'fabrica'   => (((in_array($login_fabrica,array(2,3,6,11,25,45,51,14,52,19,85,80)) or $login_fabrica > 87) or in_array($login_fabrica,$fabricas_contrato_lite))),
            'fabrica_no'=> array(114,126,127,131,132,134,136,137,138,140), // HD 907550, Bestway n�o est�, Comimex tb n�o
            'icone'     => $icone["computador"],
            'link'      => 'os_intervencao.php',
            'titulo'    => 'OS com Interven��o T�cnica',
            'descr'     => 'OSs com interven��o t�cnica da f�brica. Autoriza ou cancela o pedido de pe�as do posto ou efetua o reparo na f�brica.',
            "codigo" => 'CCT-3160'
        ),*/
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_intervencao_juridica.php',
            'titulo'    => traduz('Interven��o de OS Bloqueada'),
            'descr'     => traduz('Interven��o de OS Bloqueada (Jur�dica)'),
            "codigo" => 'CCT-3170'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'os_intervencao_sap.php',
            'titulo'    => traduz('OS com Interven��o T�cnica Garantia'),
            'descr'     => traduz('OSs com interven��o t�cnica para pe�as bloqueadas em garantia. Autoriza ou cancela o pedido de pe�as do posto.'),
            "codigo" => 'CCT-3180'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["computador"],
            'link'      => 'os_intervencao_sap.php',
            'titulo'    => traduz('OS com Interven��o SAP'),
            'descr'     => traduz('OSs com interven��o do SAP. Autoriza ou cancela o pedido de pe�as do posto ou efetua o reparo na f�brica.'),
            "codigo" => 'CCT-3190'
        ),
        array(
            'fabrica'   => array(3), /* 35521 69916 */
            'icone'     => $icone["computador"],
            'link'      => 'os_intervencao_carteira.php',
            'titulo'    => traduz('OS com Interven��o de Carteira'),
            'descr'     => traduz('OSs com interven��o de Carteira. Autoriza ou cancela o pedido de pe�as do posto / Troca ou Altera��o da OS'),
            "codigo" => 'CCT-3200'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["computador"],
            'link'      => 'cancela_pre_os.php',
            'titulo'    => traduz('Pr�-OS Callcenter'),
            'descr'     => traduz('Pr�-OS cadastrado no Callcenter. Consulta e cancela Pr�-OS'),
            "codigo" => 'CCT-3210'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_consulta_lite_off_britania.php',
            'titulo'    => traduz('Altera OS off-line e Nota Fiscal'),
            'descr'     => traduz('Altera��o da OS off-line e n�mero da nota fiscal nas OSs'),
            "codigo" => 'CCT-3220'
        ),
        array(
            'fabrica'   => array(1,11,172),
            'icone'     => $icone["computador"],
            'link'      => 'os_intervencao_suprimentos.php',
            'titulo'    => traduz('OS com Interven��o Suprimentos'),
            'descr'     => traduz('OSs com interven��o de Suprimentos. Autoriza ou cancela o pedido de pe�as do posto.'),
            "codigo" => 'CCT-3230'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["computador"],
            'link'      => 'configuracoes.php',
            'titulo'    => traduz('E-mail do DAT (TESTE)'),
            'descr'     => traduz('Configura��o do e-mail do DAT'),
            "codigo" => 'CCT-3240'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_interacao_pendente.php',
            'titulo'    => traduz('OSs Pendentes (TESTE)'),
            'descr'     => traduz('Relat�rio das OSs pendentes para o fabricante'),
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
            'titulo'    => traduz('Relat�rio de Ordens de Servi�o'),
            'descr'     => traduz('Relat�rio de Ordens de Servi�o lan�adas no sistema.'),
            "codigo" => 'CCT-3270'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_cortesia_cadastro.php',
            'titulo'    => traduz('Cadastro Cortesia Ordens de Servi�o'),
            'descr'     => traduz('Cadastro de Cortesia de Ordem de Servi�os, no modo ADMIN'),
            "codigo" => 'CCT-3280'
        ),
        array(
            'fabrica'   => array(1,117),
            'icone'     => $icone["consulta"],
            'link'      => 'os_cortesia_parametros.php',
            'titulo'    => traduz('Consulta Cortesia Ordens de Servi�o'),
            'descr'     => traduz('Consulta OS Cortesia Lan�adas'),
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
            'titulo'    => traduz('Relat�rio OS Troca'),
            'descr'     => traduz('Relat�rio de Ordem de Servi�o de Troca.'),
            "codigo" => 'CCT-3320'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_cortesia.php',
            'titulo'    => traduz('Relat�rio de Cortesia OS'),
            'descr'     => traduz('Relat�rio de OS Cortesia em determinado m�s.'),
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
            'titulo'    => traduz('Cadastro Cortesia OS de Metais Sanit�rios'),
            'descr'     => traduz('Cadastro de Cortesia de OS de Metais Sanit�rios, no modo ADMIN'),
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
            'titulo'    => traduz('Fechamento de Ordem de Servi�o'),
            'descr'     => traduz('Fechamento das Ordens de Servi�os'),
            "codigo" => 'CCT-3370'
        ),
        array(
            'fabrica'   => $fabricas_contrato_lite,
            'icone'     => $icone["consulta"],
            'link'      => 'os_revenda_parametros.php',
            'titulo'    => traduz('Consulta OS - REVENDA'),
            'descr'     => traduz('Consulta OS Revenda Lan�adas'),
            "codigo" => 'CCT-3380'
        ),
        // Telas espec�ficas da Filizola
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_manutencao.php',
            'titulo'    => traduz('Cadastrar OS de Manuten��o'),
            'descr'     => traduz('Lan�amento de OS de Manuten��o, com v�rios equipamentos por OS.'),
            "codigo" => 'CCT-3390'
        ),
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["consulta"],
            'link'      => 'os_manutencao_consulta_lite.php',
            'titulo'    => traduz('Consulta OS de Manuten��o'),
            'descr'     => traduz('Consulta OS de Manuten��o lan�adas'),
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
            'descr'     => traduz('Lan�amento de Lotes de OS'),
            "codigo" => 'CCT-3420'
        ),
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["computador"],
            'link'      => 'lote_conferencia_filizola.php',
            'titulo'    => traduz('Confer�ncia de Lote'),
            'descr'     => traduz('Realiza a confer�ncia da capa de Lote.'),
            "codigo" => 'CCT-3430'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_advertencia_bo.php',
            'titulo'    => traduz('Cadastro de advert�ncia / boletim de ocorr�ncia'),
            'descr'     => traduz('Cadastro de advert�ncia e/ou boletim de ocorr�ncia.'),
            "codigo" => 'CCT-3440'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_advertencia_bo.php',
            'titulo'    => traduz('Relat�rio de advert�ncia / boletim de ocorr�ncia'),
            'descr'     => traduz('Relat�rio de advert�ncia e/ou boletim de ocorr�ncia.'),
            "codigo" => 'CCT-3450'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_laudo_tecnico.php',
            'titulo'    => traduz('Relat�rio Laudo T�cnico'),
            'descr'     => traduz('Relat�rio que mostra as Ordens de Servi�o que possuem Laudo T�cnico.'),
            'codigo'    => 'CCT-3460'
        ),
        array(
            'fabrica'   => array(86),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_garantia_estendida.php',
            'titulo'    => traduz('Relat�rio de OS\'s com produtos de garantia estendida'),
            'descr'     => traduz('Relat�rio que mostra as Ordens de Servi�o que possuem produtos com garantia estendida.'),
            'codigo'    => 'CCT-3470'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_foto_serial.php',
            'titulo'    => traduz('Relat�rio OS com Fotos e Serial de LCD'),
            'descr'     => traduz('relat�rio para as OS\'s com upload de fotos e para OS\'s com serial de LCD.'),
            "codigo" => 'CCT-3480'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'troca_em_massa_new.php',
            'titulo'    => traduz('Troca em Massa'),
            'descr'     => traduz('Troca de Ordem de Servi�o em Massa.'),
            "codigo" => 'CCT-3490'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'cancelar_os.php',
            'titulo'    => traduz('Cancelar O.S'),
            'descr'     => traduz('Cancelamento de Ordem de Servi�o'),
            "codigo" => 'CCT-3491'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'cancelamento_aprovar.php',
            'titulo'    => traduz('Aprovar Cancelamento O.S'),
            'descr'     => traduz('Aprova��o e consulta de O.S em cancelamento'),
            "codigo" => 'CCT-3492'
        ),
        array(
            'fabrica'   => array(141),
            'icone'     => $icone["relatorio"],
            'link'      => 'consulta_historico_os.php',
            'titulo'    => traduz('Consulta hist�rico da OS'),
            'descr'     => traduz('Consulta de Ordens de Servi�o importadas para o Telecontrol'),
            "codigo" => 'CCT-3500'
        ),
        array(
            'fabrica'   => array(141,144),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_os_em_aberto.php',
            'titulo'    => traduz('Consulta OSs em Aberto'),
            'descr'     => traduz('Consulta de Ordens de Servi�o n�o finalizadas'),
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
            'titulo'    => traduz('Relat�rio Produtos de Ativa��o Autom�tica'),
            'descr'     => traduz('Consulta os Produtos de Ativa��o Autom�tica'),
            "codigo" => 'CCT-3550'
        ),
        array(
            'fabrica'   => ($telecontrol_distrib == 't') ? [$login_fabrica] : array(35, 151, 174),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_visao_geral_os.php',
            'titulo'    => traduz('Relat�rio Vis�o Geral Ordens de Servi�o'),
            'descr'     => traduz('Relat�rio Vis�o Geral Ordens de Servi�o'),
            "codigo"    => 'CCT-3560'
        ),
        array(
            'fabrica'   => array(151,203),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_historico_os_mondial.php',
            'titulo'    => traduz('Consulta Ordens de Servi�o Antigas'),
            'descr'     => traduz("Relat�rio das Ordens de Servi�o Antigas Importadas do ERP Mondial. OS 's de 2001 a 2015"),
            "codigo"    => 'CCT-3570'
        ),
        array(
            'fabrica'   => array(151),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_congeladas.php',
            'titulo'    => traduz('Consulta Ordens de Servi�o Congeladas'),
            'descr'     => traduz("Relat�rio das Ordens de Servi�o Congeladas"),
            "codigo"    => 'CCT-3580'
        ),
        array(
            'fabrica'   => array(165,178),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_historico_os_legado.php',
            'titulo'    => traduz('Consulta Ordens de Servi�o Antigas'),
            'descr'     => traduz("Relat�rio das Ordens de Servi�o Antigas Importadas"),
            "codigo"    => 'CCT-3590'
    	),
   	array(
            'fabrica'   => array(183),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_historico_os_itatiaia.php',
            'titulo'    => traduz('Consulta Ordens de Servi�o Legadas'),
            'descr'     => traduz("Relat�rio das Ordens de Servi�o Legadas"),
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
            'titulo'    => traduz('Relat�rio de Desempenho do Posto'),
            'descr'     => traduz('Acompanhamento do desempenho do posto autorizado, de acordo com a satisfa��o do consumidor com o servi�o'),
            "codigo"    => 'CCT-3620'
        ),
        array(
            'fabrica'   => array(175),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_jornada.php',
            'titulo'    => traduz('Relat�rio de Jornadas da Ordem de Servi�o'),
            'descr'     => traduz('Acompanhamento de Jornadas da Ordem de Servi�o'),
            "codigo"    => 'CCT-3630'
        ),
        array(
            'fabrica'   => array(72),
            'icone'     => $icone["relatorio"],
            'link'      => 'blacklist_serie.php',
            'titulo'    => traduz('Relat�rio Blacklist S�rie'),
            'descr'     => traduz('Lista de s�ries bloqueadas para garantia'),
            "codigo"    => 'CCT-3640'
        ),
        'link' => 'linha_de_separa��o'
    ),

    /**
     * Se��o de ORDENS DE SERVI�O DE REVENDA. GERAL.
     **/
    'secao4' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('REVENDAS - ORDENS DE SERVI�O'),
            'fabrica_no' => array_merge(array(7,14,25,95,122,189), $fabricas_contrato_lite)
        ),
        array(
            'disabled'  => isset($novaTelaOsRevenda),
            'fabrica_no'=> array(1,15,122),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_revenda.php',
            'titulo'    => traduz('Cadastra OS - REVENDA'),
            'descr'     => traduz('Cadastro de Ordem de Servi�o de revenda'),
            "codigo" => 'CCT-4010'
        ),
        array(
            'disabled'  => !isset($novaTelaOsRevenda),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_os_revenda.php',
            'titulo'    => ($login_fabrica == 178) ? traduz('Cadastra OS') : traduz('Cadastra OS - REVENDA'),
            'descr'     => traduz('Cadastro de Ordem de Servi�o de revenda'),
            "codigo" => 'CCT-4010'
        ),
        array(
            'fabrica'=> array(1),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_revenda_blackedecker.php',
            'titulo'    => traduz('Cadastra OS - REVENDA'),
            'descr'     => traduz('Cadastro de Ordem de Servi�o de revenda'),
            "codigo" => 'CCT-4020'
        ),
        array(
            'fabrica'   => array(15),
            'icone'     => $icone["cadastro"],
            'link'      => 'os_revenda_latina.php',
            'titulo'    => traduz('Cadastra OS - REVENDA'),
            'descr'     => traduz('Cadastro de Ordem de Servi�o de revenda'),
            "codigo" => 'CCT-4030'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'os_revenda_parametros.php',
            'titulo'    => ($login_fabrica == 178) ? traduz('Consulta OS') : traduz('Consulta OS - REVENDA'),
            'descr'     => traduz('Consulta OS Revenda Lan�adas'),
            "codigo" => 'CCT-4040'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'os_metais_consulta_lite.php',
            'titulo'    => traduz('Consulta OS - Metais Sanit�rios'),
            'descr'     => traduz('Consulta OS Metais Sanit�rios'),
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
            'titulo'    => traduz('Libera�ao de OS para troca'),
            'descr'     => traduz('Libera�ao de troca de OS apos negocia��o com consumidor'),
            'codigo'    => 'CCT-4070'
        ),
        'link' => 'linha_de_separa��o'
    ),

    /**
     * Se��o ATENDIMENTO T�CNICO - Apenas LENOXX
     **/
    'secao5' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('ATENDIMENTO T�CNICO'),
            'fabrica' => array(11, 172)
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'atendimento_tecnico_cadastra.php',
            'titulo'    => traduz('Cadastra Atendimento T�cnico'),
            'descr'     => traduz('Cadastro de Atendimento T�cnico'),
            "codigo" => 'CCT-5010'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'atendimento_tecnico_consulta.php',
            'titulo'    => traduz('Consulta Atendimento T�cnico'),
            'descr'     => traduz('Consulta Atendimento T�cnico'),
            "codigo" => 'CCT-5020'
        ),
        'link' => 'linha_de_separa��o'
    ),

    /**
     * Se��o SEDEX - Apenas B&D (e HBTech, mas est� inativa)
     **/
    'secao6' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('SEDEX - ORDENS DE SERVI�O'),
            'fabrica' => array(1, 25)
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'sedex_cadastro.php',
            'titulo'    => traduz('Cadastra OS SEDEX'),
            'descr'     => traduz('Cadastro de Ordem de Servi�os de SEDEX'),
            "codigo" => 'CCT-6010'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'sedex_parametros.php',
            'titulo'    => traduz('Consulta OS SEDEX'),
            'descr'     => traduz('Consulta OS Sedex Lan�adas'),
            "codigo" => 'CCT-6020'
        ),
        'link' => 'linha_de_separa��o'
    ),

    /**
     * Se��o de PEDIDOS - GERAL
     **/
    'secao7' => array (
        'secao' => array(
            'link'      => '#',
            'titulo'    => traduz('PEDIDOS DE PE�AS') . iif(($login_fabrica== 1),traduz("/PRODUTOS")),
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["computador"],
            'link'      => 'pedido_altera_permissao.php',
            'titulo'    => traduz('Permiss�o de Cadastro de Pedido'),
            'descr'     => traduz('Permite selecionar o admin que poder� fazer exclus�o no pedido.'),
            "codigo" => 'CCT-7010'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["cadastro"],
            'link'      => 'pedido_cadastro_altera.php',
            'titulo'    => traduz('Cadastro de Pedidos'),
            'descr'     => traduz('Cadastra pedidos de pe�as'),
            "codigo" => 'CCT-7020'
        ),
        array(
            'disabled'  => ($login_fabrica == 1 and !in_array($login_admin,array(112,232,245))),
            'fabrica_no'=> array_merge($fabricas_contrato_lite, array(11,14,43,66,148,152,172,180,181,182)),
            'icone'     => $icone["cadastro"],
            'link'      => 'pedido_cadastro.php',
            'titulo'    => traduz('Cadastro de Pedidos'),
            'descr'     => traduz('Cadastra pedidos de pe�as'),
            "codigo" => 'CCT-7030'
        ),
        array(
            'fabrica'=> array(148),
            'icone'     => $icone["cadastro"],
            'link'      => 'http://fvweb.yanmar.com.br/',
            'titulo'    => traduz('Cadastro de Pedidos'),
            'descr'     => traduz('Cadastra pedidos de pe�as'),
            "codigo" => 'CCT-7030',
            "blank" => true
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'pedido_cadastro_blackedecker.php',
            'titulo'    => traduz('Cadastro de Pedidos (em TESTE)'),
            'descr'     => traduz('Cadastra pedidos de pe�as (em TESTE)'),
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
            'descr'     => traduz('Bloqueia o site para os postos n�o fazerem pedidos por um per�odo. Op��o para cadastrar per�odo fiscal. Op��o para cadastrar per�odo de pedido de promo��o.'),
            "codigo" => 'CCT-7060'
        ),
        array(
            'icone'     => $icone["consulta"],
            'link'      => 'pedido_parametros.php',
            'titulo'    => traduz('Consulta Pedidos de Pe�as').iif(($login_fabrica==1),traduz('/Produtos')),
            'descr'     => traduz('Consulta pedidos efetuados por postos autorizados.'),
            "codigo" => 'CCT-7070'
        ),
        array(
            'fabrica'   => array(171),
            'icone'     => $icone["consulta"],
            'link'      => 'peca_nao_cadastra.php',
            'titulo'    => traduz('Relat�rio de pe�as n�o cadastradas'),
            'descr'     => traduz('Relat�rio de pe�as marcadas como n�o cadastradas na FN que foram lan�adas em Ordem de Servi�o e est�o pendente de pedido'),
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
            'descr'     => traduz('Consulta pedidos em aberto listando as pe�as.'),
            "codigo" => 'CCT-7090'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["upload"],
            'link'      => 'pedido_nao_importado.php',
            'titulo'    => traduz('Pedidos n�o importados'),
            'descr'     => traduz('Permite o envio de um arquivo contendo os pedidos que n�o foram importados por alguma inconsist�ncia, fazendo com que eles sejam marcados como "n�o-importados" permitindo sua altera��o.'),
            "codigo" => 'CCT-7100'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'pedido_relatorio.php',
            'titulo'    => traduz('Pedidos da Loja Virtual'),
            'descr'     => traduz('Este relat�rio exibe as informa��es dos pedidos feito na loja virtual e os admins respons�veis.'),
            "codigo" => 'CCT-7110'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'pedido_relatorio_shop.php',
            'titulo'    => traduz('Pedidos da AT-SHOP'),
            'descr'     => traduz('Este relat�rio exibe as informa��es dos pedidos feito na AT-SHOP'),
            "codigo" => 'CCT-7120'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["cadastro"],
            'link'      => 'lv_inicial.php',
            'titulo'    => traduz('Criar Pedido da Loja Virtual'),
            'descr'     => traduz('Permite que um admin crie um pedido para o posto na Loja Virtual, sendo respons�vel pelo mesmo.'),
            "codigo" => 'CCT-7130'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'peca_loja_virtual.php',
            'titulo'    => traduz('Pe�as da loja virtual'),
            'descr'     => traduz('Relat�rio de pe�as da loja virtual disponibiliza a pe�a, quantidade, valor, e Obs.'),
            "codigo" => 'CCT-7140'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["consulta"],
            'link'      => 'pedido_cancelado_consulta.php',
            'titulo'    => traduz('Consulta Pedidos Cancelados'),
            'descr'     => traduz('Consulta pe�as canceladas automaticamente em pedidos, devido ao fechamento da Ordem de Servi�o antes do faturamento das pe�as.'),
            "codigo" => 'CCT-7150'
        ),
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_pedidos_filizola.php',
            'titulo'    => traduz('Relat�rio de Pedidos por OS'),
            'descr'     => traduz('Relat�rio de pedidos referentes a OS de um determinado periodo, com valor de pe�as, m�o-de-obra e mais.'),
            "codigo" => 'CCT-7160'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'pedido_parametros_blackedecker_acessorio.php',
            'titulo'    => traduz('Consulta Pedidos de Acess�rios'),
            'descr'     => traduz('Consulta pedidos de Acess�rios efetuados por PA autorizados.'),
            "codigo" => 'CCT-7170'
        ),
        array(
            'disabled'  => true,
            'fabrica'   => array(1),
            'icone'     => $icone["upload"],
            'link'      => 'faturamento_importa_blackedecker_new.php',
            'titulo'    => traduz('Importar Faturamento'),
            'descr'     => traduz('Importa��o dos arquivos de faturamento (retorno).'),
            "codigo" => 'CCT-7180'
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["upload"],
            'link'      => 'faturamento_importa_estoque.php',
            'titulo'    => traduz('Importar Estoque'),
            'descr'     => traduz('Importa��o dos arquivos de pe�as faturadas. Faturamento<br /> das pe�as de ESTOQUE.'),
            "codigo" => 'CCT-7190'
        ),
        array(
            'disabled'  => !$fabrica_fatura_manualmente,
            'icone'     => $icone["computador"],
            'link'      => 'pedido_peca_fatura_manual_consulta.php',
            'titulo'    => traduz('Faturar Pedido Manualmente'),
            'descr'     => traduz('Faturamento de pedidos com pe�as marcadas como<br> Faturar Manualmente'),
            "codigo" => 'CCT-7200'
        ),
        array(
            'disabled'  => !$fabrica_fatura_manualmente,
            'icone'     => $icone["upload"],
            'link'      => 'pedido_peca_fatura_manual_exportar_consulta.php',
            'titulo'    => traduz('Exportar Pedido Manualmente'),
            'descr'     => traduz('Exportacao de pedidos com pe�as marcadas como <br>Faturar Manualmente'),
            "codigo" => 'CCT-7210'
        ),
        array(
            'fabrica'   => array(10),
            'icone'     => $icone["computador"],
            'link'      => '#',
            'titulo'    => traduz('Pend�ncia de Pe�as'),
            'descr'     => '',
            "codigo" => 'CCT-7220'
        ),
        array(
            'fabrica'   => array(30),
            'icone'     => $icone["upload"],
            'link'      => 'atualiza_pedido.php',
            'titulo'    => traduz('Atualiza��o de pedidos'),
            'descr'     => traduz('Atualiza pedidos atrav�s do upload de arquivo'),
            "codigo" => 'CCT-7230'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["computador"],
            'link'      => 'pedido_gera_manual.php',
            'titulo'    => traduz('Pedidos de Pe�as Remessa e NTP'),
            'descr'     => traduz('Gerar/Exporta pedidos NTP'),
            "codigo" => 'CCT-7240'
        ),
        array(
            'fabrica' => $fabrica_cancela_pedido_massivo,
            'icone'   => $icone['computador'],
            'link'    => 'pedido_cancela_multiplo.php',
            'titulo'  => traduz('Cancelar Pedidos'),
            'descr'   => traduz('Cancelamento de pedidos de pe�as, troca de produto, etc. por data, posto ou c�digo de pe�a.'),
            'codigo' => 'CCT-7250'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["computador"],
            'link'      => 'relatorio_uso_pecas.php',
            'titulo'    => traduz('Invent�rio de Pe�as para Bonifica��o'),
            'descr'     => traduz('Invent�rio de Pe�as por posto para gera��o de pedido bonificado'),
            "codigo"    => 'CCT-7260'
        ),
        array(
            "fabrica" => array(169, 170),
            "icone"   => $icone["consulta"],
            "link"    => "relatorio_pedidos_pendentes_pecas_faltantes.php",
            "titulo"  => traduz("Pedidos pendentes com pe�a faltante"),
            "descr"   => traduz("Relat�rio de pedidos pendentes com pe�a faltante"),
            "codigo"  => "CCT-7270"
        ),
        array(
            "fabrica" => array(151),
            "icone"   => $icone["consulta"],
            "link"    => "exportar_pedidos_lote.php",
            "titulo"  => "Exportar pedidos em lote",
            "descr"   => "Relat�rio para exportar pedidos em lote",
            "codigo"  => "CCT-7280"
        ),
        'link' => 'linha_de_separa��o'
    ),

    /**
     * Se��o PE�AS - Apenas INTELBRAS
     **/
    'secao8' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('INFORMA��ES SOBRE PE�AS'),
            'fabrica' => array(14)
        ),
        array(
            'fabrica'   => array(1),
            'icone'     => $icone["consulta"],
            'link'      => 'peca_consulta_dados.php',
            'titulo'    => traduz('Dados Cadastrais da Pe�a'),
            'descr'     => traduz('Consulta todos os dados cadastrais da pe�a.'),
            "codigo" => "CAD-5495"
        ),
        'link' => 'linha_de_separa��o'
    ),

    /**
     * Se��o DIVERSOS - Menos INTELBRAS
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
            'titulo'    => traduz('Rela��o de T�cnico Posto'),
            'descr'     => traduz('Rela��o dos t�cnicos cadastrados pelo posto'),
            "codigo" => 'CCT-9030'
        ),
        array(
            'fabrica'   => array(20),
            'icone'     => $icone["consulta"],
            'link'      => 'posto_consulta_pais.php',
            'titulo'    => traduz('Consulta Postos por Pa�s'),
            'descr'     => traduz('Consulta dos dados de postos da Am�rica Latina.'),
            "codigo" => 'CCT-9040'
        ),
        array(
            'fabrica_no' => array(189),
            'icone'     => $icone["consulta"],
            'link'      => iif(($login_fabrica == 1),
            'tabela_precos_blackedecker_consulta.php',
            'preco_consulta.php'),
            'titulo'    => traduz('Tabela de Pre�os'),
            'descr'     => traduz('Consulta tabela de pre�os de pe�as'),
            "codigo" => 'CCT-9050'
        ),
        array(
            'fabrica_no' => array(189),
            'fabrica'   => array($fabrica_upload_preco),
            'icone'     => $icone["upload"],
            'link'      => 'upload_tabela_preco.php',
            'titulo'    => traduz('Importa Tabela de Pre�os'),
            'descr'     => traduz('Atualiza��o da Tabela de Pre�os.'),
            "codigo" => 'CCT-9060'
        ),
        array(
            'fabrica'   => array(147),
            'icone'     => $icone["upload"],
            'link'      => 'upload_peca.php',
            'titulo'    => traduz('Importa Pe�as de-para '),
            'descr'     => traduz('Upload de Pe�as de-para.'),
            "codigo" => 'CCT-9071'
        ),
        array(
            'fabrica'   => $fabrica_upload_lista_basica,
            'icone'     => $icone["upload"],
            'link'      => 'upload_lista_basica.php',
            'titulo'    => traduz('Importa Lista B�sica'),
            'descr'     => traduz('Atualiza��o da Lista B�sica.'),
            "codigo" => 'CCT-9061'
        ),
        array(
            'fabrica_no' => array(189),
            'icone'     => $icone["consulta"],
            'link'      => 'lbm_consulta.php',
            'titulo'    => traduz('Lista B�sica'),
            'descr'     => traduz('Consulta lista b�sica de pe�as por produto.'),
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
            'descr'     => traduz('Consulta PE�AS com').(' De &raquo; Para'),
            "codigo" => 'CCT-9100'
        ),
        array(
		'icone'     => $icone["consulta"],
		'fabrica_no' => array(163,177,189),
            'link'      => 'peca_fora_linha_consulta.php',
            'titulo'    => traduz('Pe�as fora de linha'),
            'descr'     => traduz('Consulta as PE�AS que est�o fora de linha.'),
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
            'titulo'    => traduz('Dados Cadastrais da Pe�a'),
            'descr'     => traduz('Consulta todos os dados cadastrais da pe�a.'),
            "codigo" => 'CCT-9130'
        ),
        array(
            'fabrica_no' => array(189),
            'icone'     => $icone["relatorio"],
            'link'      => 'os_sem_pedido.php',
            'titulo'    => traduz('OS n�o geraram pedidos'),
            'descr'     => traduz('Ordens de Servi�os que n�o geraram pedidos de pe�as.'),
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
            'titulo'    => traduz('Devolu��o Obrigat�ria'),
            'descr'     => traduz('Pe�as que devem ser devolvidas para a F�brica constando em Ordens de Servi�o.'),
            "codigo" => 'CCT-9170'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["consulta"],
            'link'      => 'pesquisa_suggar.php',
            'titulo'    => traduz('Pesquisa Satisfa��o'),
            'descr'     => traduz('Pesquisa de Satisfa��o do Cliente (Controle de qualidade).'),
            "codigo" => 'CCT-9180'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["consulta"],
            'link'      => 'pesquisa_suggar_consulta.php',
            'titulo'    => traduz('Consulta Pesquisa Satisfa��o'),
            'descr'     => traduz('Resultado da pesquisa de qualidade.'),
            "codigo" => 'CCT-9190'
        ),
        array(
            'fabrica'   => array(24),
            'icone'     => $icone["upload"],
            'link'      => 'upload_importa_suggar.php',
            'titulo'    => traduz('Atualiza��o de Faturamento'),
            'descr'     => traduz('Envio do arquivo de faturamento de pedidos.'),
            "codigo" => 'CCT-9200'
        ),
        #HD 159888
        array(
            'fabrica'   => $fabrica_movimiento_estoque_posto,
            'icone'     => $icone["consulta"],
            'link'      => 'estoque_posto_movimento.php',
            'titulo'    => traduz('Movimenta��o Estoque'),
            'descr'     => traduz('Visualiza��o da movimenta��o do estoque do posto autorizado.'),
            "codigo" => 'CCT-9210'
        ),
        array(
            'fabrica'   => $fabrica_estoque_cfop,
            'icone'     => $icone["cadastro"],
            'link'      => 'estoque_cfop.php',
            'titulo'    => traduz('Estoque CFOP'),
            'descr'     => traduz('Tipos de nota (CFOP) que ser�o utilizadas para alimentar o estoque.'),
            "codigo" => 'CCT-9220'
        ),
        array(
            'fabrica'   => array(15),
            'icone'     => $icone["cadastro"],
            'link'      => 'estoque_minimo.php',
            'titulo'    => traduz('Estoque M�nimo'),
            'descr'     => traduz('Cadastro de Coeficiente de estoque m�nimo por estado.'),
            "codigo" => 'CCT-9230'
        ),
        array(
            'fabrica'   => array(7,24),
            'icone'     => $icone["cadastro"],
            'link'      => 'peca_inventario.php',
            'titulo'    => traduz('Invent�rio de Pe�as'),
            'descr'     => traduz('Cadastro do invent�rio de pe�as do posto autorizado'),
            "codigo" => 'CCT-9240'
        ),
        array(
            'fabrica'   => array(7,10,43,66),
            'icone'     => $icone["computador"],
            'link'      => 'aprova_pedido.php',
            'titulo'    => traduz('Aprova��o de Pedido'),
            'descr'     => traduz('Aprova��o de Pedidos de Cliente'),
            "codigo" => 'CCT-9250'
        ),
        array(
            'fabrica'   => array(7),
            'icone'     => $icone["upload"],
            'link'      => 'gera_pedido_cliente.php',
            'titulo'    => traduz('Gera��o de Pedido'),
            'descr'     => traduz('Gera��o de Pedidos de Cliente'),
            "codigo" => 'CCT-9260'
        ),
        array(
            'fabrica'   => array(25, 50, 51, 59),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_posto_fabrica.php',
            'titulo'    => traduz('Relat�rio de Postos Autorizados'),
            'descr'     => traduz('Relat�rio que exibe todos os postos autorizados'),
            "codigo" => 'CCT-9270'
        ),
        array(
            'fabrica'   => array(51),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_peca_pendente_gama.php',
            'titulo'    => traduz('Relat�rio de Pe�as Pendentes'),
            'descr'     => traduz('Relat�rio de pe�as pendentes nas ordens de servi�os.'),
            "codigo" => 'CCT-9280'
        ),
        array(
            'fabrica'   => array(45),
            'icone'     => $icone["consulta"],
            'link'      => 'relatorio_peca_bloqueada.php',
            'titulo'    => traduz('Pe�as Bloqueadas Para Garantia'),
            'descr'     => traduz('Consulta de Pe�as Bloqueadas para Garantia.'),
            "codigo" => 'CCT-9290'
        ),
        array(
            'fabrica'   => array(2),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_cliente_garantia_estendida.php',
            'titulo'    => traduz('Relat�rio garantia estendida'),
            'descr'     => traduz('Consulta de clientes que cadastraramm produto para garantia estendida.'),
            "codigo" => 'CCT-9300'
        ),
        array(
            'fabrica'   => array(11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'peca_produto.php',
            'titulo'    => traduz('Consulta de Pe�as'),
            'descr'     => traduz('Consulta por uma pe�a e traz todos os produtos em que a pe�a � utilizada.'),
            "codigo" => 'CCT-9310'
        ),
        array(
            'fabrica'   => array(15),
            'icone'     => $icone["relatorio"],
            'link'      => 'estoque_seguranca_manual.php',
            'titulo'    => traduz('Estoque Seguran�a Manual'),
            'descr'     => traduz('Cadastro e Controle de estoque de seguran�a manual.'),
            "codigo" => 'CCT-9320'
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'callcenter_pergunta_cadastro.php',
            'titulo'    => traduz('Cadastro de Perguntas do Callcenter'),
            'descr'     => traduz('Para que as frases padr�es do callcenter sejam alteradas.'),
            "codigo" => 'CCT-9330'
        ),
        array(
            'fabrica'   => array(141,144),
            'icone'     => $icone["consulta"],
            'link'      => 'estoque_posto.php',
            'titulo'    => traduz('Estoque de pe�as do Posto Autorizado'),
            'descr'     => traduz('Cadastro e Consulta de estoque de pe�as do Posto Autorizado'),
            "codigo"    => 'CCT-9340'
        ),
        array(
            'fabrica'   => (in_array($login_fabrica, $posto_estoque)) ? array($login_fabrica) : array(),
            'fabrica_no'=> array(161,164,191,193),
            'icone'     => $icone["consulta"],
            'link'      => 'posto_estoque.php',
            'titulo'    => traduz('Estoque de pe�as do Posto Autorizado'),
            'descr'     => traduz('Cadastro e Consulta de estoque de pe�as do Posto Autorizado'),
            "codigo"    => 'CCT-9350'
        ),
        array(
            'fabrica'   => array(158),
            'icone'     => $icone["consulta"],
            'link'      => 'relatorio_movimentacao_estoque.php',
            'titulo'    => traduz('Relat�rio de movimenta��o de estoque'),
            'descr'     => traduz('Consulta da movimenta��o de estoque do posto autorizado'),
            "codigo"    => 'CCT-9360'
        ),
        array(
            'fabrica'   => array(101),
            'icone'     => $icone["cadastro"],
            'link'      => 'upload_codigo_rastreio.php',
            'titulo'    => traduz('Upload C�digo Rastreio'),
            'descr'     => traduz('Cadastro de C�digo de Rastreio'),
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
            'titulo'    => traduz('Relat�rio de erros Mercado Livre'),
            'descr'     => traduz('Erros da integra��o com Mercado Livre'),
            'codigo'    => 'CCT-9390'
    	),
    	array( 
	    'icone'   => $icone["relatorio"], 
	    'link'    => 'relatorio_respostas_atendimento_posto.php', 
	    'titulo'  => 'Relat�rio Postos Atendendo', 
	    'descr'   => 'Relat�rio que mostra os Postos que est�o realizando Atendimento ao P�blico', 
	    "codigo"  => 'CCT-9400' 
	    ),
        'link' => 'linha_de_separa��o'
    ),

    /**
     * Se��o RELAT�RIOS CALL-CENTER (�?)
     **/
    'secaoA' => array (
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('RELAT�RIOS CALL-CENTER'),
            'fabrica'     => array(6,114,11,3, 172),
        ),
        array(
            'fabrica'   => array(6,114),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_callcenter_reclamacao_por_estado.php',
            'titulo'    => traduz('Reclama��es por estado'),
            'descr'     => traduz('Hist�rico de atendimentos por estado.'),
            "codigo" => 'CCT-A010'
        ),
        // array(
        //  'fabrica_no'    => array(122,81,114,124,123),
        //  'icone'     => $icone["cadastro"],
        //  'link'      => 'callcenter_pergunta_cadastro.php',
        //  'titulo'    => 'Cadastro de Perguntas do Callcenter',
        //  'descr'     => 'Para que as frases padr�es do callcenter sejam alteradas.',
        //  "codigo" => 'CCT-A020'
        // ),
        array(
            'fabrica'   => array(3, 6, 11, 172),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_intervencao.php',
            'titulo'    => traduz('Relat�rio de Interven��o'),
            'descr'     => traduz('OS com interven��o da Assist�ncia T�cnica da F�brica / SAP'),
            "codigo" => 'CCT-A030'
        ),
        array(
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_produto_serie_mascara.php',
            'titulo'    => traduz('Relat�rio de M�scara de N�mero de S�rie'),
            'descr'     => traduz('Relat�rio de M�scara de N�mero de S�rie.'),
            "codigo" => 'CCT-A040'
        ),
        array(
            'fabrica_no' => array(141,144),
            'fabrica'   => array(3),
            'icone'     => $icone["relatorio"],
            'link'      => 'relatorio_intervencao_km.php',
            'titulo'    => traduz('Relat�rio de Interven��o de KM'),
            'descr'     => traduz('OS com interven��o de deslocamento (KM).'),
            "codigo" => 'CCT-A050'
        ),
        'link' => 'linha_de_separa��o'
    ),

    /**
     * Se��o GERENCIAMENTO DE REVENDAS - Apenas Brit�nia
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
            'descr'     => traduz('Relat�rio com Ordens de Servi�os em aberto, listando pelas 20 maiores revendas que abriram Ordens de Servi�os.'),
            "codigo" => 'CCT-B020'
        ),
        'link' => 'linha_de_separa��o'
    ),
);

