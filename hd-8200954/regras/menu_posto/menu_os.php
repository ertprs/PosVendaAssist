<?php

$posto_libera_os_extrato = \Posvenda\Regras::get("posto_libera_os_extrato", "extrato", $login_fabrica);
$app_ticket = $parametros_adicionais_posto['app_ticket'];

$titulo_fecha_OS = "fechamento.de.ordem.de.servico".$fechaOsRevenda;
$descr_fecha_OS  = "fechamento.de.ordem.de.servico".$fechaOsRevenda;

/**
 * Mostra Cadastro de OS e Cadastro de OS Revenda somente se o posto não for interno
 * Fábrica - Imbera (158)
 **/
if (isFabrica(158)) {
    if (!$e_posto_interno) {
        $bloqCadastroOs = $login_fabrica;
    } else {
        $ocultarNovaTelaOS = false;
        $bloqCadastroOs    = -1;
    }
}

/**
 * Mostra somente os menus de OS revenda
 * Fábrica - Roca (178)
 **/
if ($login_fabrica == 178) {
    $ocultarNovaTelaOS = true;
}

/**
 * - Bloqueio de postos da ESMALTEC
 * situados no estado do CEARÁ
 * de abrir e fechar OS
 */
if (isFabrica(35)) {
	$os_fechamento[] = $login_fabrica;
}
$array_menus_os = array(1,24,28,35,52);
if (isFabrica(30)) {
    array_push($array_menus_os,$bloqAbreFechaOs);

    $sql = "
      SELECT posto
        FROM tbl_posto_fabrica
       WHERE fabrica   = {$login_fabrica}
         AND tipo_posto IN(
              SELECT tipo_posto
                FROM tbl_tipo_posto
               WHERE descricao = 'SAC'
         AND fabrica   = {$login_fabrica})";

    $res = pg_query($con, $sql);
    for ($i=0; $i < pg_num_rows($res); $i++) {
        if ($login_posto == pg_fetch_result($res, $i, 'posto')) {
            array_push($array_menus_os, 30);
            $bloqCadastroOs = 30;
            break;
        }
    }
}

if (in_array($login_fabrica, [169,170]) && $digita_os_revenda != "t") {
    $mostra_revenda = $login_fabrica;
}

if (isFabrica(169,170)){

	$sql = "
		SELECT
			posto
		FROM tbl_posto_fabrica pf
		JOIN tbl_posto_linha pl USING(posto)
		JOIN tbl_linha l ON l.linha = pl.linha AND l.fabrica = {$login_fabrica}
		JOIN tbl_produto p ON p.linha = l.linha AND p.fabrica_i = {$login_fabrica}
		JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
		WHERE pf.fabrica = {$login_fabrica}
		AND pl.ativo IS TRUE
		AND f.codigo_validacao_serie = 'true'
		AND pf.posto = {$login_posto};
	";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$posto_rpi = $login_posto;
	}
}

if (isFabrica(161)) {
    $sql = "
		SELECT posto 
		FROM tbl_posto_linha 
		JOIN tbl_linha USING(linha)
		WHERE tbl_posto_linha.posto = {$login_posto}
		AND tbl_linha.nome = 'INTERNACIONAL';
	";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
    	$linhaInternacional = $login_posto;
    }
}

// Incluir aqui a da B&D quando sair do teste...
$linkOSrevenda = array(
    15 => 'os_revenda_latina.php',
    80 => 'os_revenda_ajax.php',
);

if (isFabrica(177) && $contrato == 't') {
    $certificado = "SELECT tdocs_id FROM tbl_tdocs WHERE fabrica = $login_fabrica and referencia_id = $login_posto and contexto = 'certificado_parceria' order by tdocs DESC limit 1 ";
    $certificado = pg_query($con, $certificado);
    $certificado = pg_fetch_result($certificado, 0, 'tdocs_id');
}

/** Começa o menu de OS **/
$menu_os = array(
    'title' => traduz('menu.de.ordens.de.servico'),
    array (
        'disabled'   => ($ocultarTelaAntigaOS || $LU_abre_os === false),
        'fabrica_no' => $array_menus_os,
        'icone'      => 'marca25.gif',
        'link'       => 'os_cadastro.php',
        'titulo'     => traduz('abertura.de.ordem.de.servico'),
        'descr'      => traduz('clique.aqui.para.inserir.uma.nova.ordem.de.servico')
    ),
    array (
        'disabled'   => ($ocultarTelaAntigaOS || $LU_abre_os === false),
        'fabrica'    => array(35),
        'icone'      => 'marca25.gif',
        'link'       => 'os_cadastro.php',
        'titulo'     => traduz('abertura.de.oS.consumidor'),
        'descr'      => traduz('clique.aqui.para.inserir.uma.nova.ordem.de.servico')
    ),
    array (
        'disabled'  => isset($novaTelaOsRevenda) || $LU_abre_os === false,
        'fabrica'   => array(35),
        'icone'     => 'marca25.gif',
        'posto'     => $digita_os,
        'link'      => $linkOSrevenda[$login_fabrica] ? : 'os_revenda.php',
        'titulo'    => traduz('abertura.de.oS.revenda'),
        'descr'     => traduz('clique.aqui.para.incluir.uma.nova.ordem.de.servico.de.revenda')
    ),
    array (
        'disabled'   => ($ocultarNovaTelaOS || $LU_abre_os===false),
        'fabrica_no' => array(52,190, $bloqCadastroOs),
        'icone'      => 'marca25.gif',
        'link'       => 'cadastro_os.php',
        'titulo'     => traduz('abertura.de.ordem.de.servico'),
        'descr'      => traduz('clique.aqui.para.inserir.uma.nova.ordem.de.servico')
    ),
    array (
        'disabled'  => ($novaTelaOs || $digita_os_consumidor == 'f' || $LU_abre_os===false),
        'fabrica'   => array(1,24),
        'icone'     => 'marca25.gif',
        'link'      => 'os_cadastro.php',
        'titulo'    => traduz('abertura.de.ordem.de.servico.de.consumidor'),
        'descr'     => traduz('clique.aqui.para.inserir.uma.nova.ordem.de.servico.de.consumidor')
    ),
    array (
        'fabrica'   => array(145),
        'icone'     => 'marca25.gif',
        'link'      => 'cadastro_os_revisao.php',
        'titulo'    => traduz('abertura.de.ordem.de.servico.de.revisao'),
        'descr'     => traduz('clique.aqui.para.inserir.uma.nova.ordem.de.servico.de.revisao')
    ),
    array (
        'fabrica'   => array(152,180,181,182),
        'icone'     => 'marca25.gif',
        'link'      => 'cadastro_os_entrega_tecnica.php',
        'titulo'    => traduz('abertura.de.ordem.de.servico.de.entrega.tecnica'),
        'descr'     => traduz('clique.aqui.para.inserir.uma.nova.ordem.de.servico.de.entrega.tecnica')
    ),
    array (
        'icone'     => 'tela25.gif',
        'link'      => 'os_consulta_lite.php',
        'titulo'    => traduz('consulta.de.ordem.de.servico'),
        'descr'     => traduz('ordens.de.servicos.de.revenda.para.consulta,.impressao.ou.alteracao')
    ),
    array (
        'fabrica'   => array(1),
        'icone'     => 'tela25.gif',
        'link'      => 'relatorio_os_finalizada_sem_extrato.php',
        'titulo'    => traduz('consulta.de.os.finalizada.sem.extrato'),
        'descr'     => traduz('ordens.de.servico.finalizadas.ainda.sem.extrato')
    ),
    array (
        'fabrica'   => array(114,122,125,128,136),
        'icone'     => 'tela25.gif',
        'link'      => 'relatorio_mao_obra.php',
        'titulo'    => "Relatorio de mão de obra",
        'descr'     => "Consulta relatorio de mão de obra e adicionais"
    ),
    array (
        'disabled'  => true,
        'fabrica'   => array(14),
        'icone'     => 'marca25.gif',
        'link'      => 'os_consulta_intervencao.php',
        'titulo'    => traduz(array('consulta','os.em.intervencao')),
        'descr'     => traduz('consulta.ordens.de.servico.que.estao.em.intervencao')
    ),
    array (
        'disabled'  => (isset($novaTelaFechamento) || $LU_fecha_os === false), /*HD-3853582 26/10/2017*/
        'fabrica_no'=> $os_fechamento,
        'icone'     => 'marca25.gif',
        'link'      => 'os_fechamento.php',
        'titulo'    => traduz($titulo_fecha_OS),
        'descr'     => traduz($descr_fecha_OS,  $con)
    ),
    array (
        'disabled'  => (!isset($novaTelaFechamento) || $LU_fecha_os === false), /*HD-3853582 26/10/2017*/
        'fabrica_no'=> $os_fechamento,
        'icone'     => 'marca25.gif',
        'link'      => 'fechamento_os.php',
        'titulo'    => traduz($titulo_fecha_OS),
        'descr'     => traduz($descr_fecha_OS,  $con)
    ),
    array (
        'disabled'  => true,
        'fabrica'   => array(1),
        'icone'     => 'tela25.gif',
        'link'      => 'os_cortesia_parametros.php',
        'titulo'    => traduz('consulta.de.ordem.de.servico.cortesia'),
        'descr'     => traduz('ordens.de.servicos.de.revenda.para.consulta,.impressao.ou.alteracao')
    ),
    array (
        'icone'     => 'tela25.gif',
        'link'      => 'os_relatorio.php',
        'titulo'    => isFabrica(169, 170) ? traduz('relacao.de.status.da.ordem.de.servico') : traduz('status.da.ordem.de.servico'),
        'descr'     => traduz('relacao.de.status.da.ordem.de.servico')
    ),
    array (
        'fabrica'   => array(14),
        'icone'     => 'tela25.gif',
        'link'      => 'os_relatorio.php?acao=PESQUISAR&recusar=sim',
        'titulo'    => traduz('ordens.de.servico.recusadas'),
        'descr'     => traduz('ordens.de.servico.recusadas') .
        ' (' . traduz('ultimos.%.meses', $con,null,array('3')) . ')'
    ),
    array (
        'disabled'  => true,
        'fabrica'   => array(7),
        'icone'     => 'marca25.gif',
        'link'      => 'os_filizola_relatorio.php',
        'titulo'    => 'Faturamento - Valores da OS',
        'descr'     => 'Consulta as OS com valores'
    ),
    array (
        'disabled'  => true,
        'fabrica'   => array(7),
        'icone'     => 'tela25.gif',
        'link'      => 'os_preventiva.php',
        'titulo'    => 'Preventiva',
        'descr'     => 'Ordens de serviços de manutenções preventivas'
    ),
    array (
        'disabled'  => ($LU_abre_os === false),
        'fabrica'   => array(7),
        'icone'     => 'tela25.gif',
        'link'      => 'os_manutencao.php',
        'titulo'    => traduz('abertura.os.de.manutencao'),
        'descr'     => traduz('clique.aqui.para.inserir.uma.nova.ordem.de.servico.de.manutencao')
    ),
    array (
        'fabrica'   => array(7),
        'icone'     => 'tela25.gif',
        'link'      => 'os_consulta_avancada.php',
        'titulo'    => traduz('consulta.de.os.de.manutencao'),
        'descr'     => traduz('ordens.de.servicos.de.manutencao.para.consulta,.impressao.ou.lancamento')
    ),
    array (
        'disabled'  => isset($novaTelaOsRevenda) || $LU_abre_os === false || $login_fabrica == 138,
        'fabrica_no'=> array(7, 20, 28, 35, 42,190, $mostra_revenda, $bloqCadastroOs),
        'icone'     => 'marca25.gif',
        'posto'     => $digita_os,
        'link'      => $linkOSrevenda[$login_fabrica] ? : 'os_revenda.php',
        'titulo'    => traduz('abertura.de.os.de.revenda'),
        'descr'     => traduz('clique.aqui.para.incluir.uma.nova.ordem.de.servico.de.revenda')
    ),
    array (
        'disabled'  => (!isset($novaTelaOsRevenda) || $LU_abre_os === false || $login_fabrica == 138),
        'fabrica_no'=> array(7, 20, 28, 42, $mostra_revenda, $bloqCadastroOs),
        'icone'     => 'marca25.gif',
        'posto'     => $digita_os,
        'link'      => 'cadastro_os_revenda.php',
        'titulo'    => isFabrica(178) ? traduz('abertura.de.os') : traduz('abertura.de.os.de.revenda'),
        'descr'     => isFabrica(178) ? traduz('clique.aqui.para.incluir.uma.nova.ordem.de.servico') : traduz('clique.aqui.para.incluir.uma.nova.ordem.de.servico.de.revenda')
    ),
    // HD 160957
    array (
        'disabled'  => ($linha_metais != 494 || $LU_abre_os===false),
        'fabrica'   => array(1),
        'icone'     => 'marca25.gif',
        'link'      => 'os_cadastro_metais_sanitario_new.php',
        'titulo'    => traduz('abertura.de.os.metais.sanitarios'),
        'descr'     => traduz('cadastro.de.os.metais.sanitarios')
    ),
    array (
        'disabled'  => ($linha_metais != 494),
        'fabrica'   => array(1),
        'icone'     => 'tela25.gif',
        'link'      => 'os_consulta_metais.php',
        'titulo'    => traduz('consulta.os.metais.sanitarios'),
        'descr'     => traduz('pesquisa.os.metais.sanitarios')
    ),
    array (
        'fabrica_no'=> array(1, 7, 20, 28, 42, 52, 169, 170, 178, $mostra_revenda),
        'posto'     => $login_fabrica != 138,
        'icone'     => 'tela25.gif',
        'link'      => 'os_revenda_consulta_lite.php',
        'titulo'    => traduz('consulta.os.revenda'),
        'descr'     => traduz('ordens.de.servicos.de.revenda.para.consulta,.impressao.ou.alteracao')
    ),
    array (
        'fabrica'   => array(87),
        'icone'     => 'tela25.gif',
        'link'      => 'os_soaf.php',
        'titulo'    => traduz('consulta.os.soaf'),
        'descr'     => traduz('consulta.os.soaf.descricao')
    ),
    array (
        'disabled'  => ($LU_fecha_os === false),
        'fabrica'   => array(19),
        'icone'     => 'tela25.gif',
        'link'      => 'os_revenda_fechamento.php',
        'titulo'    => traduz('fechamento.de.os.revenda'),
        'descr'     => traduz('fechamento.das.ordens.de.servicos.de.revenda')
    ),
    // o parâmetro 'posto' não foi pensado para isso, mas funciona (mlg_gambiarra... :D)
    // se a condição der false, não vai mostrar
    //<!-- Ordem de serviço de informatica para Britânia -->
    array (
        'disabled'  => ($LU_abre_os === false),
        'fabrica_no'=> array(80,162),
        'icone'     => 'marca25.gif',
        'posto'     => (($linhainf=='t' || $login_fabrica == 59) AND $login_fabrica != 3),
        'link'      => 'os_cadastro.php?pre_os=t',
        'titulo'    => traduz('cadastro.os.informatica'),
        'descr'     => traduz('abre.os.linha.informatica')
    ),
    array (
        'fabrica_no'=> array(156,171),
        'posto'     => $fabrica_usa_preOS,
        'icone'     => 'tela25.gif',
        'link'      => 'os_consulta_lite.php?btn_acao_pre_os=Consultar',
        'titulo'    => ($login_fabrica == 191 and $e_posto_interno) ? traduz('consulta.pre.os.aberta.pela.revenda') : traduz('consulta.pre.os.aberta.pelo.call.center'),
        'descr'     => ($login_fabrica == 191 and $e_posto_interno) ? traduz('Consulta os Chamados de Revenda que fez o cadastro de uma Pré-OS.') : traduz('Consulta os Chamados de Call-Center que fez o cadastro de uma Pré-OS.')
    ),
    array (
        'fabrica'   => array(1,6,25),
        'so_testes' => ($login_fabrica==6),
        'icone'     => 'marca25.gif',
        'link'      => 'sedex_parametros.php',
        'titulo'    => 'Consulta OS SEDEX',
        'descr'     => 'Consulta OS Sedex Lançadas'
    ),
    array (
        'disabled'  => !$coleta_peca,
        'fabrica'   => array(1,25),
        'so_testes' => ($login_fabrica==6),
        'icone'     => 'marca25.gif',
        'link'      => 'relatorio_devolucao_pecas.php',
        'titulo'    => 'Emissão de NF para devolução de peças',
        'descr'     => 'Relatório mensal para emissão da Nota fiscal de devolução de peças para a fábrica'
    ),
    array (
        'disabled'  => !$coleta_peca,
        'fabrica'   => array(1),
        'icone'     => 'marca25.gif',
        'link'      => 'relatorio_separacao_pecas_devolucao.php',
        'titulo'    => 'Separação das peças para devolução',
        'descr'     => 'Relatório que deverá ser utilizado para separação das peças que serão devolvidas para a fábrica. Mostra todas as OS geradas mensalmente com suas respectivas peças'
    ),
    array (
        'fabrica'   => array(59),
        'icone'     => 'tela25.gif',
        'link'      => 'relatorio_os_por_posto_peca.php',
        'titulo'    => "Relatório OS Digitadas",
        'descr'     => "Mostra as Ordens de Serviço digitadas no sistema. "
    ),
    array (
        'fabrica'   => array(1),
        'icone'     => 'tela25.gif',
        'link'      => 'controle_devolucao_bateria.php',
        'titulo'    => traduz('relatorio.de.devolucao.das.baterias'),
        'descr'     => traduz('relatorio.de.devolucao.das.baterias')
    ),
    array (
        'disabled'  => $LU_abre_os === false,
        'fabrica'   => array(1),
        'icone'     => 'tela25.gif',
        'link'      => 'os_cadastro_troca.php',
        'titulo'    => traduz('abertura.de.os.de.troca'),
        'descr'     => traduz('abre.ordem.de.servico.de.troca')
    ),
    array (
        'fabrica'   => array(52),
        'icone'     => 'tela25.gif',
        'link'      => 'relatorio_tempo_conserto_mes.php',
        'titulo'    => traduz('permanencia.em.conserto.no.mes'),
        'descr'     => traduz('relatorio.que.mostra.o.tempo.de.permanencia.do.produto.no.mes')
    ),
    array(
        'fabrica'   => $app_ticket == true,
        'disabled'  => $app_ticket != true,
        'icone'     => 'tela25.gif',
        'titulo'    => traduz('Relátorio de Peça'),
        'link'      => 'relatorio_posto_peca.php',
        'descr'     => 'Relatório de Peças do posto',
    ),
    array (
        'fabrica'   => $app_ticket == true,
        'icone'     => 'tela25.gif',
        'link'      => 'cockpit.php',
        'titulo'    => traduz('CockPit'),
        'descr'     => traduz('Agendamento.de.OS.por.técnico')
    ),
    array(
        'fabrica'  => $app_ticket == true,
        'disabled' => $app_ticket != true,
        'icone'    => 'tela25.gif',
        'titulo'   => traduz('Aprovação de Ticket - Aplicativo'),
        'link'     => 'aprovacao_ticket_new.php',
        'descr'    => 'Aprovação de OS do aplicativo',
    ),
    array (
        'fabrica_no'=> array(101),
        'disabled'  => $LU_extrato === false || $login_posto == $linhaInternacional,
        'icone'     => 'tela25.gif',
        'link'      => 'os_extrato.php',
        'titulo'    => traduz('extrato'),
        'descr'     => traduz('consulta.de.extratos')
    ),
    array (
        'fabrica'   => array(183),
        'icone'     => 'tela25.gif',
        'link'      => 'relatorio_agendamentos_cancelados.php',
        'titulo'    => traduz('Agendamentos Cancelados'),
        'descr'     => traduz('Consulta de agendamentos cancelados')
    ),
    array (
        'fabrica'   => array(101),
        'icone'     => 'tela25.gif',
        'link'      => 'os_extrato_new.php',
        'titulo'    => traduz('extrato'),
        'descr'     => traduz('consulta.de.extratos')
    ),
    array (
        'fabrica'   => array(3),
        'icone'     => 'tela25.gif',
        'link'      => 'lgr_vistoria_itens.php',
        'titulo'    => traduz('Vistoria'),
        'descr'     => traduz('Extrato de peças vistoria (90 dias)')
    ),
    array (
        'posto'     => array($posto_rpi) ,
        'icone'     => 'marca25.gif',
        'link'      => 'cadastro_rpi.php',
        'titulo'    => 'Cadastro RPI',
        'descr'     => 'Cadastro RPI'
    ),
    array (
        'posto'     => array($posto_rpi) ,
        'icone'     => 'tela25.gif',
        'link'      => 'consulta_rpi.php',
        'titulo'    => 'Consulta RPI',
        'descr'     => 'Consulta RPI'
    ),
    array (
        'fabrica'   => array(50,151),
        'icone'     => 'tela25.gif',
        'link'      => 'os_pendente_pagamento.php',
        'titulo'    => 'OS Pendente de Pagamento',
        'descr'     => 'Ordens de serviços aguardando conferencia de LGR (Devolução de Pecas)'
    ),
    array (
        'fabrica'   => array(50,151),
        'icone'     => 'tela25.gif',
        'link'      => 'pesquisa_nf_devolucao.php',
        'titulo'    => 'Consulta Nota Fiscal de Devolução',
        'descr'     => 'Notas Fiscais preenchidas'
    ),
    array (
        'disabled'  => $LU_extrato === false,
        'fabrica'   => array(156),
        'icone'     => 'tela25.gif',
        'link'      => 'os_extrato_novo_lgr_os_callcenter.php',
        'titulo'    => 'Extrato - Contratos',
        'descr'     => 'Consulta de Extratos - Contratos'
    ),
    array(
        'disabled' => (!$posto_libera_os_extrato || $LU_extrato===false),
        "icone"  => "tela25.gif",
        "link"   => "liberar_os_extrato.php",
        "titulo" => "Gerar Extrato",
        "descr"  => "Liberar/Bloquear Ordem de Serviço para entrar no extrato"
    ),
    array (
        'fabrica'   => array(30),
        'disabled' => !$controle_estoque,
        'icone'     => 'tela25.gif',
        'link'      => 'estoque_pecas.php',
        'titulo'    => traduz('estoque.de.peças'),
        'descr'     => traduz('consulta.o.estoque.de.peças.para.venda.e.garantia.do.posto')
    ),
    array (
        'disabled'   => (!$UploadOSHabilitado),
        'icone'     => 'tela25.gif',
        'link'      => 'os_upload.php',
        'titulo'    => traduz('upload.de.os'),
        'descr'     => traduz('upload.de.os.descricao')
    ),
    array (
        'fabrica'=> (($reparoNaFabrica)? array($login_fabrica):array() ),
        'icone'     => 'fix-24.png',
        'link'      => 'solicitacao_reparo_fabrica.php',
        'titulo'    => 'Solicitação de Reparo na Fábrica',
        'descr'     => 'Relatório de consulta as solicitações de OS para reparo na Fábrica.',
    ),
    array (
        'fabrica'   => array(3),
        'icone'     => 'tela25.gif',
        'link'      => 'tecnico_estatistica.php',
        'titulo'    => traduz('relatorio.tecnico.os'),
        'descr'     => traduz('estatisticas.dos.tecnicos.em.os')
    ),
    array (
        'fabrica'   => array(3),
        'icone'     => 'tela25.gif',
        'link'      => 'relatorio_lgr_posto_fabrica.php',
        'titulo'    => 'Relatório LGR',
        'descr'     => 'Notas Pendentes, Recebidas e Extratos Aguardando Emissão'
    ),
    array (
        'fabrica'   => array(14),
        'icone'     => 'tela25.gif',
        'link'      => 'produto_consulta_detalhe.php',
        'titulo'    => traduz('estrutura.do.produto'),
        'descr'     => traduz('consulta.dados.da.estrutura.do.produto.(produto.>.subconjunto.>.pecas)')
    ),
    array (
        'fabrica'   => array(1),
        'icone'     => 'tela25.gif',
        'link'      => 'produto_maodeobra.php',
        'titulo'    => traduz('tabela.de.mao.de.obra'),
        'descr'     => traduz('tabela.de.precos.da.mao.de.obra.do.produto')
    ),
    array (
        'fabrica'   => array(19),
        'icone'     => 'marca25.gif',
        'link'      => 'posto_cadastro.php',
        'titulo'    => traduz('informacoes.do.posto'),
        'descr'     => traduz('altera.dados.e.senha.do.posto')
    ),
    array (
        'disabled'  => true,
        'fabrica'   => array(3),
        'icone'     => 'tela25.gif',
        'link'      => 'britania_posicao_extrato.php',
        'titulo'    => 'Extrato (site antigo)',
        'descr'     => 'Consulta posição dos Extratos'
    ),
    array (
        'disabled'  => true,
        'posto'     => ($login_tipo_posto == 2 and $pedido_via_distribuidor == 't'),
        'icone'     => 'tela25.gif',
        'link'      => 'relatorio_saldo_pecas.php',
        'titulo'    => traduz('relatorio.de.saldo'),
        'descr'     => traduz('relatorio.de.saldo.de.pecas.em.os')
    ),
    array (
        'fabrica'   => array(7),
        'icone'     => 'marca25.gif',
        'link'      => 'os_print_filizola.php?branco=sim',
        'blank'     => true, //Abre o link em outra aba/janela
        'titulo'    => traduz('imprime.os.em.branco'),
        'descr'     => traduz('impressao.de.ordens.de.servicos.em.branco')
    ),
    array (
        'fabrica'   => $extrato_fechamento,
        'icone'     => 'tela25.gif',
        'link'      => 'extrato_fechamento.php',
        'titulo'    => traduz('fechamento.de.extrato/lote'),
        'descr'     => traduz('selecione.as.os.que.deseja.para.criacao.do.extrato/lote.para.enviar.ao.fabricante')
    ),
    array (
        'disabled'  => (!$usa_lote_revenda),
        'icone'     => 'tela25.gif',
        'link'      => 'revenda_conferencia.php',
        'titulo'    => traduz('lotes.de.revenda'),
        'descr'     => traduz('controle.de.lotes.de.revenda.para.reparo')
    ),
    array (
        'fabrica'   => array(3),
        'icone'     => 'tela25.gif',
        'link'      => 'produto_consulta_dados.php',
        'titulo'    => traduz('dados.cadastrais.do.produto'),
        'descr'     => traduz('consulta.dados.cadastrais.do.produto')
    ),
    array (
        'fabrica'   => array(3,51),
        'icone'     => 'tela25.gif',
        'link'      => 'lgr_vistoria_itens.php',
        'titulo'    => traduz('vistoria.de.pecas'),
        'descr'     => traduz('consulta.de.pecas.para.a.vistoria')
    ),
    array (
        'fabrica'   => array(2),
        'posto'     => array(6359,21574),
        'icone'     => 'tela25.gif',
        'link'      => 'os_intervencao_teste.php',
        'titulo'    => traduz('os.com.intervencao'),
        'descr'     => traduz('consulta.ordens.de.servico.que.estao.em.intervencao')
    ),
    array (
        'fabrica'   => array(1),
        'icone'     => 'tela25.gif',
        'link'      => 'helpdesk_listar.php',
        'titulo'    => traduz('cadastro.e.consulta.de.chamados'),
        'descr'     => traduz('abertura.e.consulta.de.chamados.para.o.seu.suporte')
    ),
    array (
        'fabrica'   => array(158),
        'icone'     => 'tela25.gif',
        'link'      => 'indicadores_eficiencia_volume.php',
        'titulo'    => "Indicadores SLA/Reincidência",
        'descr'     => "Indicadores que mostra o tempo de resposta dos atendimento, a eficiência dos atendimentos dentro do sla"
    ),
    array (
        'disabled'  => $login_posto_atendimento != 'n',
        'fabrica'   => array(20),
        'icone'     => 'tela25.gif',
        'link'      => 'upload_os.php',
        'titulo'    => traduz('novo.upload.de.os'),
        'descr'     => traduz('envio.de.arquivo.para.o.site.contendo.suas.ordens.de.servico.em.formato.texto')
    ),
    array (
        'fabrica'  => array(177),
        'disabled' => $contrato != 't',
        'link'     => $certificado,
        'titulo'   => traduz('certificado.de.credenciamento'),
        'descr'    => traduz('Download')
    )
);

return $menu_os;

