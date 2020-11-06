<?php
if (isFabrica(87, 140) or (isset($novaTelaOs) and isFabrica(87,136,138,139,142,143))) {
	$oculta_forum_postos[] = $login_fabrica;
}

$pesquisa_satisfacao_nao_usa = array(1,20,140);
if (isset($novaTelaOs) && $login_fabrica != 145) {
	$pesquisa_satisfacao_nao_usa[] = $login_fabrica;
}

$fabrica_treinamento = in_array($login_fabrica, array(1,10,20,42,117,138,148)) and $cook_idioma == 'pt-br' and $login_tipo_posto != 151;

$winOpts  = 'toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0';
$winURL   = 'configuracao.php'  . iif(($cook_idioma == 'es'), '?sistema_lingua=ES', '');
$link_lng = ($cook_idioma == 'es') ? 'es-es' : $cook_idioma; // Para o Site do IE, 'es' não serve.

$menu_tecnica = array(
	'title' => traduz("menu.de.comunicados.e.informacoes.tecnicas",$con),
	array (
		'disabled' => true,
		'fabrica'  => array(1),
		'icone'    => 'tela25.gif',
		'link'     => 'http://www.blackdecker.com.br/eventos_bd.php',
		'titulo'   => traduz('eventos e treinamentos', $con),
		'descr'    => traduz('centro.de.treinamentos.e.eventos', $con)
	),
	array (
		'disabled' => true,
		'fabrica'  => array(1),
		'icone'    => 'tela25.gif',
		'link'     => 'agendamento_blackedecker.php',
		'titulo'   => traduz('programacao.do.treinamento', $con),
		'descr'    => traduz('agende.e.programe.treinamentos', $con)
	),
	array (
		'fabrica_no' => array(2,4,6,7,8,9,11,12,13,14,15,16,17,18,20,45,148,172),
		"icone"      => 'tela25.gif',
		"link"       => isFabrica(42) ? 'info_tecnica_arvore2.php' : 'info_tecnica_arvore.php',
		"titulo"     => isFabrica(167) ? 'Vista Explodida / Download' : traduz('vista.explodida',$con),
		"descr"      => traduz('mostra.relacao.de.pecas.e.desenho.da.vista.explodida.dos.produtos',$con),
	),
	array (
		'fabrica'    => array(148),
		"icone"      => 'tela25.gif',
		"link"       => 'info_tecnica.php',
		"titulo"     => traduz('Vista Explodida'),
		"descr"      => traduz('mostra.relacao.de.pecas.e.desenho.da.vista.explodida.dos.produtos',$con),
	),
	array (
		'fabrica' => array(3),
		'icone'   => 'tela25.gif',
		'titulo'  => traduz('atualizacao.de.software',$con),
		'link'    => 'info_tecnica_arvore_atualizacao.php',
		'descr'   => traduz('mostra.relacao.de.atualizacoes.para.o.software.no.sistema',$con)
	),
	/* $menu_tecnica[] = array (
		'fabrica' => array(1),
		'icone'   => 'marca25.gif',
		'link'    => 'comunicado_mostra_blackedecker.php',
		'titulo'  => traduz('comunicados.boletins.informativos',$con),
		'descr'   => traduz('apresenta.os.comunicados.boletins.e.informativos.do.fabricante',$con)
	); */
	array (
		'fabrica_no' => array(14,42,20),
		'icone'      => 'marca25.gif',
		'link'       => 'comunicado_produto_consulta.php',
		'titulo'     => traduz('comunicados.produto',$con),
		'descr'      => traduz('consulta.dos.comunicados.cadastrados.por.produto.pela.fabrica',$con)
	),
	array (
		'fabrica'    => array(20),
		'icone'      => 'marca25.gif',
		'link'       => 'comunicado_mostra.php',
		'titulo'     => traduz('comunicados.produto',$con),
		'descr'      => traduz('consulta.dos.comunicados.cadastrados.por.produto.pela.fabrica',$con)
	),
	array (
		'fabrica' => array(1,42),
		'icone'   => 'marca25.gif',
		'link'    => 'comunicado_mostra.php',
		'titulo'  => traduz('comunicados',$con),
		'descr'   => traduz('consulta.dos.comunicados.cadastrados.pela.fabrica',$con)
	),
	array (
		'fabrica' => array(14),
		'icone'   => 'marca25.gif',
		'link'    => 'comunicado_mostra.php',
		'titulo'  => traduz('comunicados.diversos',$con),
		'descr'   => traduz('consulta.dos.comunicados.cadastrados.pela.fabrica',$con)
	),
	array (
		'fabrica'                                         => array(2),
		'icone'                                           => 'marca25.gif',
		'link'                                            => array(
		'comunicado_mostra.php?tipo=Esquema+Elé%E9trico',
			'comunicado_mostra.php?tipo=Descritivo+té%E9cnico',
			'comunicado_mostra.php?tipo=Manual',
			'comunicado_mostra.php?tipo=Vista+Explodida'),
			'titulo'                           => array(
			traduz('esquemas.eletricos',$con),
			traduz('descritivo.tecnico',$con),
			traduz('manual',$con),
			traduz('vistas.explodidas',$con)),
			'descr' => traduz('exibicao.dos.esquemas.eletricos.dos.produtos',$con)
	),
	array (
		'fabrica' => array(14),
		'icone'   => 'marca25.gif',
		'link'    => 'comunicado_mostra_pesquisa_agrupado.php?acao=PESQUISAR',
		'titulo'  => traduz('informacoes.tecnicas',$con),
		'descr'   => traduz('informacoes.tecnicas.descricao',$con)
	),
	array (
		'disabled' => true,
		'icone'    => 'marca25.gif',
		'link'     => 'comunicado_mostra.php?tipo=Esquema+El%E9trico',
		'titulo'   => traduz('produtos',$con),
		'descr'    => 'Guia do usuário / caracteristicas técnicas dos produtos'
	),
	array (
		'disabled' => true,
		'fabrica'    => array(42),
		'icone'    => 'marca25.gif',
		'link'     => 'procedimento_mostra.php',
		'titulo'   => traduz('procedimentos', $con),
		'descr'    => traduz('apresenta.os.procedimentos.do.fabricante', $con)
	),
	array (
		'fabrica'  => array(42),
		'icone'  => 'marca25.gif',
		'link'   => 'comunicado_mostra.php?tipo=Procedimento+de+manuten%E7%E3o',
		'titulo' => traduz('procedimentos.de.manutenção', $con),
		'descr'  => traduz('apresenta.os.procedimentos.do.fabricante', $con)
	),
	array (
		'fabrica' => array(45,11,172),
		"icone"   => 'tela25.gif',
		"link"    => 'info_tecnica_arvore.php',
		"titulo"  => traduz('vistas.explodidas',$con),
		"descr"   => traduz('apresenta.as.vistas.explodidas.do.fabricante',$con)
	),
	array (
		'fabrica' => array(14),
		'icone'   => 'marca25.gif',
		'link'    => 'comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=Comunicado%20administrativo',
		'titulo'  => traduz('comunicados.administrativos', $con),
		'descr'   => traduz('comunicados.administrativos.descricao', $con)
	),
	array (
		'fabrica' => array(24),
		'icone'   => 'marca25.gif',
		'link'    => 'comunicado_mostra_pesquisa?acao=PESQUISAR&tipo=Treinamento%20de%20Produto',
		'titulo'  => traduz('treinamento.de.produtos', $con),
		'descr'   => traduz('manual.de.treinamento.de.produtos', $con)
	),
	array (
		'fabrica_no' => array(14,20),
		'icone'      => 'tela25.gif',
		'link'       => 'relatorio_peca.php',
		'titulo'     => traduz('relatorio.de.pecas', $con),
		'descr'      => traduz('relatorio.de.de.para.pecas.alternativas.e.pecas.fora.de.linha', $con)
	),
	array (
		'fabrica' => array(158),
		'icone'   => 'tela25.gif',
		'link'    => 'relatorio_movimentacao_estoque.php',
		'titulo'  => traduz('relatorio.de.movimentação.de.estoque.(analítico)', $con),
		'descr'   => traduz('relatorio.de.movimentação.de.estoque', $con)
	),
	array (
		'fabrica' => array(158),
		'icone'   => 'tela25.gif',
		'link'    => 'relatorio_qtde_pecas_estoque.php',
		'titulo'  => traduz('relatorio.de.movimentação.de.estoque.(sintético)', $con),
		'descr'   => traduz('relatorio.de.qtde.de.pecas.estoque', $con)
	),
	array (
		'fabrica' => array(1),
		'icone'   => 'tela25.gif',
		'link'    => 'peca_consulta_dados.php',
		'titulo'  => traduz('consulta.dados.da.peca', $con),
		'descr'   => traduz('consulta.os.dados.cadastrais.da.peca', $con)
	),
	array ( // HD 384011
		'disabled' => (!$posto_controla_estoque),
		'fabrica'  => array(3,15,30,74,178),
		'icone'    => 'tela25.gif',
		'link'     => 'estoque_posto_movimento.php',
		'titulo'   => traduz('movimentacao.estoque', $con),
		'descr'    => traduz('visualizacao.da.movimentacao.do.estoque', $con)
	),
	array (
		'disabled' => !$mostra_vista_expodida_auto,
		'icone'    => 'marca25.gif',
		'link'     => 'vista_explodida_relatorio.php',
		'titulo'   => traduz('vista.explodida',$con),
		'descr'    => traduz('mostra.relacao.de.pecas.e.desenho.da.vista.explodida.dos.produtos',$con)
	),
	array (
		'icone'      => 'tela25.gif',
		'fabrica_no' => $oculta_forum_postos,
		'link'       => 'forum.php',
		'disabled'   => $login_fabrica == 161,
		'titulo'     => traduz('forum.telecontrol',$con),
		'descr'      => traduz('espaco.reservado.para.duvidas.e.comentarios.dos.postos.autorizados',$con)
	),
	array (
		'fabrica_no' => $pesquisa_satisfacao_nao_usa,
		'icone'      => 'tela25.gif',
		'titulo'     => traduz('pesquisa.de.satisfacao',$con),
		'link'       => 'opiniao_posto.php',
		'descr'      => traduz('responda.a.pesquisa.de.satisfacao.dos.postos.autorizados',$con)
	),
	array (
		'fabrica' => array(1),
		'icone'   => 'tela25.gif',
		'titulo'  => traduz('pesquisa.de.satisfacao',$con),
		'link'    => 'opiniao_posto_blackedecker.php',
		'descr'   => traduz('responda.a.pesquisa.de.satisfacao.dos.postos.autorizados',$con)
	),
	array (
		'disabled' => true, // Removido por Thiago Contardi HD 341188 - Não era utilizado mais - 10/12/2010
		'fabrica'  => array(1),
		'icone'    => 'tela25.gif',
		'titulo'   => traduz('treinamentos',$con),
		'link'     => 'treinamento.php',
		'descr'    => traduz('linhas.de.ferramentas.eletricas.dewalt.hammers.e.compressores',$con)
	),
	array ( //HD 3342 - NÃO MOSTRAR TREINAMENTO PARA LOCADORAS (COD: 151,
		'disabled'  => ($cook_idioma!='pt-br' or $login_tipo_posto == 151),
		'fabrica'   => $fabrica_treinamento,
		'icone'     => 'tela25.gif',
		'link'      => 'treinamento_agenda.php',
		'titulo'    => traduz('treinamentos',$con),
		'descr'     => traduz('agenda.de.treinamento.para.postos.autorizados',$con)
	),
	array (
		'disabled' => ($cook_idioma!='pt-br' or $login_tipo_posto == 151),
		'fabrica'  => array(1,42),
		'icone'    => 'tela25.gif',
		'link'     => 'treinamento_realizado.php',
		'titulo'   => traduz('treinamentos.realizados',$con),
		'descr'    => traduz('agenda.de.treinamento.realizados.para.postos.autorizados',$con)
	),
	array (
		'disabled' => true, // até mudar o link... IE8!!
		'icone'  => 'tela25.gif',
		'titulo' => traduz('requisitos.do.sistema',$con),
		'link'   => "javascript:window.open(\"$winURL\",\"janela\",\"$winOpts\")",
		'descr'  => traduz('para.um.melhor.aproveitamento.dos.recursos.do.sistema.recomendamos.o.uso.dos.navegadores.browsers',$con) .
		":<br /><a href='http://windows.microsoft.com/$link_lng/internet-explorer/downloads/ie' target='_blank'>Internet Explorer 8</a> ".
		traduz('ou', $con, $cook_idioma),
		" <a href='https://www.google.com/chrome/?hl=$cook_idioma' target='_blank'>Google Chrome 15</a> ".
		traduz('ou.superior',$con)
	),
	array (
		'fabrica' => array(151,163),
		'icone'   => 'marca25.gif',
		'link'    => 'comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=Laudo+Tecnico',
		'titulo'  => ucwords(mb_strtolower(traduz('laudo.tecnico',$con))),
		'descr'   => traduz('laudo.tecnico.descricao',$con)
	),
	array (   //  Deveria estar acima junto com a Intelbras, mas por enquanto querem ter as duas telas disponíveis para os postos
		'fabrica' => array(3),
		'icone'   => 'tela25.gif',
		'link'    => 'comunicado_mostra_pesquisa_agrupado.php?acao=PESQUISAR',
		'titulo'  => traduz("informacoes.tecnicas",$con),
		'descr'   => traduz('informacoes.tecnicas.descricao',$con)
	),
	array (   
		'fabrica' => array(157),
		'icone'   => 'tela25.gif',
		'link'    => 'catalogo_de_acessorios.php',
		'titulo'  => traduz("catalogo.de.acessorios",$con),
		'descr'   => traduz('catalogo.de.acessorios',$con)
	),
	'linha_de_separação'
);

return $menu_tecnica;

