<?php
include_once 'helper.php';
include_once 'funcoes.php';

if (!isset($arr_fabrica_padrao)) {
    $arr_fabrica_padrao = array();
}

/************************************************************
 * Par�metros de configura��o do menu cadastro admin est�o  *
 * no script admin/menu_cadastro.php, � partir da linha 230 *
 * aprox.                                                   *
 * Alterar par�metros apenas nesse script, usar aqui se     *
 * poss�vel apenas vari�veis.                               *
 ************************************************************/

$fabricasPecaExcendenteLB = array();
if ($pecasExcedenteLB == true) {
	$fabricasPecaExcendenteLB = array($login_fabrica);
}

$vet_tipo_pedido_dellar = $fabricas_contrato_lite;
unset($vet_tipo_pedido_dellar[1]);

if ($fabrica_padrao=='t') {
    $arr_fabrica_padrao = array($login_fabrica);
}

$usaFluxoAtendimento = [189];

$arr_fabrica_defeito =  array(106,120,201,11,80,3,131,96,87,46,15,30,121,50,40,19,109,35,52,20,85,81,1,10,42,90,137,59,117,86,24,91,45,88,74,6,102,72,151,153,169,170,172,183);
$arr_fabrica_solucao = array(1,3,6,10,11,15,19,24,30,35,40,42,45,46,50,52,59,72,74,80,81,85,88,90,91,96,98,102,109,114,116,117,120,201,127,138,145,148,149,158,172,183,191);

$array_fabrica_not_providencia = array(74,101,163,165,175,177,178,198);

if (in_array($login_fabrica, array(30,50,90,101,120,201,136,139,144))) { //HD-3282875 adicionada a f�brica 50
    $fabrica_seleciona_defeito_reclamado = true;
}
$array_fabrica_providencia = array();

// Lin disse p/ substituir $classificacaoCallcenter por $classificacaoHD
$array_fabrica_hdclassificacao = array();
if ($classificacaoHD || $moduloProvidencia) {
    $array_fabrica_hdclassificacao[] = $login_fabrica;
    $array_fabrica_providencia[] = $login_fabrica;
    $cadastroProvidencia = true;
}

if (in_array($login_fabrica, array(171,175))) {
    $cadastroProvidencia = false;
}

if ($login_fabrica == 35 || $login_fabrica >= 131) {
$sql = "
    SELECT admin
     FROM tbl_admin
     WHERE admin = '$login_admin'
    AND responsavel_postos = 't' ";

    $resPosto = pg_exec($con, $sql);

    if (pg_num_rows($resPosto) > 0){
        $responsavel_posto = $login_fabrica;
    }else{
        $responsavel_posto = '';
    }
}else{
    $responsavel_posto = '';
}

/*
    hd-1149884 -> Para as f�bricas que tiverem o par�metro adicional fabrica_padrao='t', as telas:
        admin/tipo_posto_condicao_cadastro.php
        admin/peca_analise_cadastro.php
        admin/peca_represada_cadastro.php
        admin/defeito_cadastro.php
        admin/solucao_cadastro.php
        admin/revenda_cadastro.php
        admin/consumidor_cadastro.php
        admin/fornecedor_cadastro.php
        admin/feriado_cadastra.php

    N�o ser�o mais utilizadas.
*/
$sqlLojaVirtual = "SELECT fabrica FROM tbl_loja_b2b WHERE ativo = 't'";
$resLojaVirtual = pg_query($con, $sqlLojaVirtual);

if (pg_num_rows($resLojaVirtual) > 0) {
    foreach (pg_fetch_all($resLojaVirtual) as $kLoja => $vLoja) {
        $loja_habilitada[] = $vLoja["fabrica"];
    }
} else {
    $loja_habilitada = array();
}

$fabrica_cadastra_origem = array();
if ($usaOrigemCadastro || in_array($login_fabrica, array(160,169,170,174,177,198)) || $replica_einhell) {
	$fabrica_cadastra_origem = array($login_fabrica);
}

if ($usaLaudoTecnicoOs) {
    $fabricaLaudoTecnicoOs = array($login_fabrica);
} else {
    $fabricaLaudoTecnicoOs = array(0);
}

if ($pesquisaSatisfacao) {
    $fabricaPesquisaSatisfacao = array($login_fabrica);
} else {
    $fabricaPesquisaSatisfacao = array(0);
}

if (in_array($login_fabrica, array(166,169,170)) || $usaScriptFalha == 't') {
	$array_script_falha = array($login_fabrica);
} else {
	$array_script_falha = array(0);
}

if ($login_fabrica == 151){
	$titulo_9260 = traduz("Atendente Callcenter");
}else if ($login_fabrica == 183){
	$titulo_9260 = traduz("Atendente Provid�ncia");
}else{
	$titulo_9260 = traduz("Atendente Manuten��o");
}

if (in_array($login_fabrica, array(30,35,151,169,170,183))){
	$descricao_9260 = "Manunten��o de Atendente do Callcenter.";
}else if ($login_fabrica == 183){
	$descricao_9260 = "Manunten��o de Atendente de Provid�ncia do Callcenter.";
}else{
	$descricao_9260 = "Manuten��o de Atendente de Help-Desk por Estado.";
}

/** COME�A A DEFINI��O DO ARRAY DO MENU **/
// Menu CADASTRO
return array(
    // Sec�o CADASTROS REFERENTES A PE�AS JACTO
    'jacto_pecas' => array(
        'secao' => array(
            'link'    => '#',
            'titulo'  => in_array($login_fabrica, $fabricas_contrato_lite) ? traduz('CADASTROS DE PE�AS') : traduz('CADASTROS REFERENTES A PEDIDOS DE PE�AS'),
            'fabrica' => array(87) // Habilitado para a JACTO
        ),
        array(
            'icone'   => $icone["cadastro"],
            'link'    => 'transportadora_cadastro.php',
            'titulo'  => traduz('Cadastro de Transportadora'),
            'descr'   => traduz('Consulta - Inclus�o - Exclus�o de Transportadoras.'),
            "codigo"  => "CAD-1010"
        ),
        array(
            'icone'   => $icone["cadastro"],
            'link'    => 'peca_cadastro.php',
            'titulo'  => traduz('Cadastro de Pe�as'),
            'descr'   => traduz('Consulta - Inclus�o - Exclus�o de Componentes utilizados pela f�brica.'),
            "codigo"  => "CAD-1020"
        ),
        array(
            'icone'   => $icone["cadastro"],
            'link'    => 'preco_cadastro.php',
            'titulo'  => traduz('Pre�os de Pe�as'),
            'descr'   => traduz('Cadastramento e altera��o em pre�os de pe�as.'),
            "codigo"  => "CAD-1030"
        ),
        array(
            'icone'   => $icone["cadastro"],
            'link'    => 'depara_cadastro.php',
            'titulo'  => 'De &raquo; Para',
            'descr'   => traduz('Cadastro de pe�as DE-PARA (altera��o em c�digos de pe�as).'),
            "codigo"  => "CAD-1040"
        ),
        array(
            'icone'   => $icone["cadastro"],
            'link'    => 'classe_pedido.php',
            'titulo'  => traduz('Consulta Classe de Pedidos'),
            'descr'   => traduz('Relat�rio de consulta das classes de pedidos.'),
            "codigo"  => "CAD-1050"
        ),
    ),

    // Menu Cadastro Postos para a JACTO, evita colocar regra de exclus�o em quase tudo
    'jacto_postos' => array(
        'secao' => array(
            'link'     => '#',
            'titulo'    => traduz('MANUTEN��O DE POSTOS AUTORIZADOS'),
            'fabrica'   => array(87) // Habilitado para a JACTO
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'posto_cadastro.php',
            'titulo'    => traduz('Postos Autorizados'),
            'descr'     => traduz('Cadastramento de postos autorizados'),
            "codigo" 	=> "CAD-2000"
        ),
    ),

    'brother_postos' => array(
	    'secao' => array(
	        'link'     	=> '#',
	        'titulo'    => 'MANUTEN��O DE POSTOS AUTORIZADOS',
	        'fabrica'   => array(167,203) // Habilitado para a JACTO
	    ),
	    array(
	        'icone'     => $icone["cadastro"],
	        'link'      => 'manutencao_postos_autorizados.php',
	        'titulo'    => 'Contrato Postos Autorizados',
	        'descr'     => 'Cadastramento de contratos para postos autorizados',
	        "codigo"	=> "CAD-9300"
	    ),
	),

    // SE��O de INTEGRIDADE E RELACIONAMENTO DE DEFEITOS
    'jacto_integridade' => array(
        'secao' => array(
            'disabled' => true, // N�o est�o usando...
            'link'     => '#',
            'titulo'   => traduz('CADASTROS DE DEFEITOS - EXCE��ES'),
            'fabrica'  => false
        ),
        array(
            //'disabled' => true, //Pertence � se��o seguinte (Integridade)
            'icone'    => $icone["computador"],
            'link'     => 'tipo_os_por_familia_cadastro.php',
            'titulo'   => traduz('Manuten��o de Tipo de OS X Fam�lia'),
            'descr'    => traduz('Integridade - Tipo de OS X Fam�lia'),
            "codigo"   => "CAD-3000"
        ),
        array(
            //'disabled' => true, //Pertence � se��o seguinte (Integridade)
            'icone'    => $icone["cadastro"],
            'link'     => 'tipo_atendimento_cadastro.php',
            'titulo'   => traduz('Cadastro de Tipos de Atendimento'),
            'descr'    => traduz('Manuten��o do cadastro dos Tipos de Atendimentos que ser�o utilizados nas Ordens de Servi�o'),
            "codigo"   => "CAD-3010"
        ),
    ), // FIM Menus JACTO

	// Menu CADASTRO (parte II)
	'secao0' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => in_array($login_fabrica, $fabricas_contrato_lite) ? traduz('CADASTROS DE PE�AS') : traduz('CADASTROS REFERENTES A PEDIDOS DE PE�AS'),
			'fabrica_no' => array(87) // Deshabilitado para a JACTO
		),

		array(
			'icone'   => $icone["cadastro"],
			'link'    => 'transportadora_cadastro.php',
			'titulo'  => traduz('Cadastro de Transportadora'),
			'descr'   => traduz('Consulta - Inclus�o - Exclus�o de Transportadoras.'),
			"codigo"  => "CAD-4000",
			"fabrica" => array(35,88,94,120,201,143,157,161,163,169,170,175,177,189)
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'marca_cadastro.php',
			'titulo'     => traduz('Cadastro de Marca'),
			'descr'      => traduz('Cadastro de Marca.'),
			"codigo"     => "CAD-4009"
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'peca_cadastro.php',
			'titulo'     => traduz('Cadastro de Pe�as'),
			'descr'      => traduz('Consulta - Inclus�o - Exclus�o de Componentes utilizados pela f�brica.'),
			"codigo"     => "CAD-4010"
		),
		array(
			'fabrica'    => array(30,151,158),
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastrar_familia_peca.php',
			'titulo'     => traduz('Cadastro Fam�lia da Pe�a'),
			'descr'      => traduz('Cadastro de Fam�lia de Pe�a'),
			"codigo"     => "CAD-4020"
		),
		array(
			'fabrica'    => array(24),
			'icone'      => $icone["computador"],
			'link'       => 'peca_amarracao.php',
			'titulo'     => traduz('Amarra��o de Pe�as'),
			'descr'      => traduz('Ferramenta de amarra��o de pe�as. Quando lan�ar uma pe�a � obrigado a lan�ar a pe�a amarrada'),
			"codigo"     => "CAD-4030"
		),
		array(
			'fabrica'    => array(6),
			'icone'      => $icone["cadastro"],
			'link'       => 'peca_amarracao_lista.php',
			'titulo'     => traduz('Lista Pe�a X Pe�a'),
			'descr'      => traduz('Cadastro e exclus�o de pe�a e subpe�a da lista b�sica.'),
			"codigo"     => "CAD-4040"
		),
	   array(
			'fabrica'   => array(152,165,180,181,182),
			'icone'     => $icone["cadastro"],
			'link'      => (in_array($login_fabrica, array(152,180,181,182))) ? 'cadastro_valor_mao_obra.php' : 'cadastro_valor_mao_obra_servico.php',
			'titulo'    => traduz('Cadastro de valores de m�o de obra'),
			'descr'     => (in_array($login_fabrica, array(152,180,181,182))) ? traduz('Cadastro de Funcionalidades por Admin') : traduz('Cadastro de m�o-de-obra, relacionados entre fam�lia, servi�o e dias de OS Aberta'),
			"codigo" => 'CAD-4050'
		),
		array(
			'fabrica'   => array(167,195,203),
			'icone'     => $icone["cadastro"],
			'link'      => 'cadastro_mao_obra_new.php',
			'titulo'    => traduz('Cadastro de valores de m�o de obra'),
			'descr'     => traduz('Cadastro de m�o-de-obra'),
			"codigo" => 'CAD-4050'
		),
		array(
			'fabrica_no' => array_merge($fabricas_contrato_lite, array(189)),
			'icone'      => $icone["cadastro"],
			'link'       => 'lbm_cadastro.php',
			'titulo'     => traduz('Lista B�sica'),
			'descr'      => traduz('Estrutura de pe�as aplicadas a cada produto'),
			"codigo"     => "CAD-4060"
		),
		array(
			'fabrica' => array(158),
			'icone'      => $icone["cadastro"],
			'link'       => 'lbm_cadastro_subitem.php',
			'titulo'     => 'Lista B�sica de Subitem',
			'descr'      => 'Estrutura de subitem para a pe�a',
			"codigo"     => "CAD-4070"
		),
		array(
			'fabrica'    => array(42),
			'icone'      => $icone["upload"],
			'link'       => 'lbm_excel.php',
			'titulo'     => traduz('Lista B�sica Upload'),
			'descr'      => traduz('Upload de arquivo xls para atualiza��o da lista b�sica'),
			"codigo"     => "CAD-4070"
        ),
        array(
            'fabrica'    => array(35),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_revenda_bloqueadas.php',
            'titulo'     => traduz('Cadastro de Revendas Bloqueadas'),
            'descr'      => traduz('Cadastro de Revendas Bloqueadas'),
            "codigo"     => "CAD-4061"
        ),
		array(
			'fabrica_no' => array(189),
			'icone'      => $icone["cadastro"],
			'link'       => 'condicao_cadastro.php',
			'titulo'     => traduz('Condi��es de Pagamento'),
			'descr'      => traduz('Cadastramento de condi��es de pagamentos para pedidos de pe�as'),
			"codigo"     => "CAD-4080"
		),
		array(
			'fabrica' => array(80,3,46,30,101,1,10,42,90,24,72),
			'icone'      => $icone["cadastro"],
			'link'       => 'tipo_posto_condicao_cadastro.php',
			'titulo'     => traduz('Condi��es de Pagamento por Tipo de Posto'),
			'descr'      => traduz('Cadastramento de condi��es de pagamentos para pedidos de pe�as espec�fica para tipos de postos'),
			"codigo"     => "CAD-4090"
		),
		array(
			'fabrica'    => array(30, 42, 138,151),
			'icone'      => $icone["cadastro"],
			'link'       => 'condicao_pagamento_posto_cadastro.php',
			'titulo'     => traduz('Condi��es de Pagamento para Postos'),
			'descr'      => traduz('Cadastramento de condi��es de pagamentos para pedidos de pe�as espec�fica para postos'),
			"codigo"     => "CAD-4100"
		),
		array(
			'fabrica'    => array(7),
			'icone'      => $icone["computador"],
			'link'       => 'tabela_vigencia.php',
			'titulo'     => traduz('Vig�ncia das Tabela Promocionais'),
			'descr'      => traduz('Altera a vig�ncia das tabelas promocionais'),
			"codigo"     => "CAD-4110"
		),
		array(
			'fabrica'    => array(7),
			'icone'      => $icone["cadastro"],
			'link'       => 'desconto_pedido_cadastro.php',
			'titulo'     => traduz('Cadastro de Descontos'),
			'descr'      => traduz('Cadastro de desconto em pedidos, com data de vig�ncia.'),
			"codigo"     => "CAD-4120"
		),
		array(
			'fabrica'    => array(7),
			'icone'      => $icone["cadastro"],
			'link'       => 'capacidade_manutencao.php',
			'titulo'     => traduz('Valores por Capacidade'),
			'descr'      => traduz('Define os valores de regulagem e certificado por capacidade'),
			"codigo"     => "CAD-4130"
		),
		//PARA BLACK - ADICIONADO DIA 30-03-2007 IGOR - HD:1666
		array(
			'fabrica'    => array(1,72),
			'icone'      => $icone["cadastro"],
			'link'       => 'condicao_pagamento_manutencao.php',
			'titulo'     => traduz('Altera��o de Condi��es de Pagamento'),
			'descr'      => traduz('Altera��o  de condi��es de pagamentos dos postos'),
			"codigo"     => "CAD-4140"
		),
		array(
			'fabrica_no' => array(189),
			'fabrica'    => array_merge(array(52,81,114,175),$tabela_preco),
			'icone'      => $icone["cadastro"],
			'link'       => 'tabela_preco.php',
			'titulo'     => traduz('Tabela de Pre�o'),
			'descr'      => traduz('Cadastro e manunten��o de pe�as e tabelas'),
			"codigo"     => "CAD-4150"
		),
		array(
			'fabrica'    => array(1),
			'icone'      => $icone["upload"],
			'link'       => 'upload_tabela_acessorios.php',
			'titulo'     => traduz('Upload da Tabela de Acess�rios'),
			'descr'      => traduz('Upload da tabela de acess�rios via XLS'),
			"codigo"     => "CAD-4160"
		),
		array(
			'fabrica'    => array(1),
			'icone'      => $icone["upload"],
			'link'       => 'upload_mo.php',
			'titulo'     => traduz('Upload de Tabela de m�o-de-obra'),
			'descr'      => traduz('Upload de tabela de m�o-de-obra via TXT'),
			"codigo"     => "CAD-4170"
		),
		array(
			'fabrica'    => array(52),
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_tabela_servico.php',
			'titulo'     => traduz('Tabela de Servi�o'),
			'descr'      => traduz('Cadastro de Tabela de Servi�o para M�o de Obra.'),
			"codigo"     => "CAD-4180"
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'preco_cadastro.php',
			'titulo'     => traduz('Pre�os de Pe�as'),
			'descr'      => traduz('Cadastramento e altera��o em pre�os de pe�as.'),
			"codigo"     => "CAD-4190"
		),
		array(
			'fabrica'    => array(1,141,144),
			'icone'      => $icone["upload"],
			'link'       => 'preco_upload.php',
			'titulo'     => traduz('Atualiza��o de Pre�os de Acess�rios'),
			'descr'      => traduz('Atualiza pre�o de pe�a Acess�rios para pedido Acess�rio e Loja Virtual.'),
			"codigo"     => "CAD-4200"
		),
		array(
			'fabrica'    => 3,
			'icone'      => $icone["cadastro"],
			'link'       => 'fator_multiplicacao.php',
			'titulo'     => traduz('Pre�os Sugeridos'),
			'descr'      => traduz('Cadastro de pre�os sugeridos para que o PA se baseie para vender ao consumidor.'),
			"codigo"     => "CAD-4210"
		),
		array(
			'fabrica'    => 40,
			'icone'      => $icone["cadastro"],
			'link'       => 'upload_importa_masterfrio.php',
			'titulo'     => traduz('Atualiza��o de Pre�os(Via Upload)'),
			'descr'      => traduz('Cadastramento e altera��o em pre�os de pe�as via upload pelo arquivo XLS.'),
			"codigo"     => "CAD-4220"
		),
		array(
			'fabrica_no' => array_merge($vet_tipo_pedido_dellar,array(169,170,189)),
			'icone'      => $icone["cadastro"],
			'link'       => 'tipo_pedido.php',
			'titulo'     => traduz('Tipo do Pedido'),
			'descr'      => traduz('Cadastro de Tipo de Pedidos'),
			"codigo"     => "CAD-4230"
		),
        array(
        	'fabrica_no' => array(189),
            'icone'      => $icone["cadastro"],
            'link'       => 'depara_cadastro.php',
            'titulo'     => 'De &raquo; Para',
            'descr'      => traduz("Cadastro de pe�as ")."De &raquo; Para ".traduz('(altera��o em c�digos de pe�as).')."<img src='imagens/help.png' title='".traduz('Aqui, poder� ser cadastrada uma pe�a que ir� substituir uma pe�a que n�o ser� mais utilizada ou  que est� indispon�vel no momento. Quando o Posto Autorizado precisar lan�ar essa pe�a em uma Ordem de Servi�o ou em um Pedido de Venda, poder� colocar a refer�ncia da antiga pe�a que o sistema ir� trazer autom�ticamente a refer�ncia da nova pe�a. Esse ')."DE�>PARA ".traduz('pode ser feito por um per�odo que ser� determinado pelo Admin.')."'>",
            "codigo"     => "CAD-4240"
        ),
        array(
            'fabrica'    => array_merge(array(3,7,8,10,11,17,20,30,43,45,104,122,125,147,160,169,170,172,184,187,194,200),((isset($telecontrol_distrib)) ? array($login_fabrica) : array()) ),
            'icone'      => $icone["cadastro"],
            'link'       => 'peca_alternativa_cadastro.php',
            'titulo'     => traduz('Pe�as Alternativas'),
            'descr'      => traduz('Cadastro de pe�as ALTERNATIVAS.'),
            "codigo"     => "CAD-4250"
        ),
        array(
            'fabrica_no' => array(164,175,176,178,191,193),
            'icone'      => $icone["cadastro"],
            'link'       => 'peca_fora_linha_cadastro.php',
            'titulo'     => traduz('Pe�as Fora de Linha'),
            'descr'      => traduz("Cadastro de pe�as FORA DE LINHA")." <img src='imagens/help.png' title='".traduz('Aqui, poder� ser cadastradas as pe�as que sa�ram de linha e n�o poder�o ser mais lan�adas em um Pedido, poder� ser apenas em pedidos de garantia caso seja marcada a op��o de Liberado para garantia, nesse caso se a f�brica tiver a pe�a em estoque o Posto Autorizado poder� lan�ar essa pe�a na Ordem de servi�o para realizar o conserto do produto.')."'>",
            "codigo"     => "CAD-4260"
        ),
        array(
            'fabrica_no' => array_merge($fabricas_contrato_lite, array(115,116,117,120,201,122,81,114,123,124,126,128,129,141,144,137,134,132,136,143,131,138,140,142,143,145,146),$arr_fabrica_padrao,((isset($novaTelaOs)) ? array($login_fabrica) : array())),
            'icone'      => $icone["cadastro"],
            'link'       => 'peca_analise_cadastro.php',
            'titulo'     => traduz('Pe�as em An�lise'),
            'descr'      => traduz('Cadastro de pe�as em AN�LISE'),
            "codigo"     => "CAD-4270"
        ),
        array(
            'icone'      => $icone["cadastro"],
            'link'       => 'peca_acerto.php',
            'titulo'     => traduz('Acerto de Pe�as'),
            'descr'      => traduz('Lista todas as pe�as e seus dados para acerto comum.'),
            "codigo"     => "CAD-4280"
        ),
        array(
            //'disabled'  => true, // � referente a produtos, deveria estar com os produtos
            'icone'      => $icone["cadastro"],
            'link'       => 'produto_acerto_linha.php',
            'titulo'     => traduz('Acerto de Produtos'),
            'descr'      => traduz('Lista todos os produtos e seus dados para acerto comum.'),
            "codigo"     => "CAD-4290"
        ),

        array(
            'fabrica'    => array(81),
            'icone'      => $icone["cadastro"],
            'link'       => 'solucao_marca.php',
            'titulo'     => traduz('Solu��o X Marcas'),
            'descr'      => traduz('Lista todas as solu��o x marcas.'),
            "codigo"     => "CAD-4291"
        ),

        array(
            'fabrica'    => array(169),
            'icone'      => $icone["cadastro"],
            'link'       => 'escritorio_venda.php',
            'titulo'     => 'Escrit�rio de Venda',
            'descr'      => 'Cadastra os escrit�rios de venda utilizados nos pedidos de venda.',
            "codigo"     => "CAD-4292"
        ),
        array(
            'fabrica'    => array(169),
            'icone'      => $icone["cadastro"],
            'link'       => 'equipe_venda.php',
            'titulo'     => 'Equipe de Venda',
            'descr'      => 'Cadastra as equipes de venda utilizadas nos pedidos de venda.',
            "codigo"     => "CAD-4293"
        ),
        array(
            'fabrica_no' => array_merge($fabricas_contrato_lite, array(115,116,117,122,81,114,123,124,126,129,141,144,137,134,132,136,143,131,138,139,140,142,143,145,146), ((isset($novaTelaOs)) ? array($login_fabrica) : array())),
            'icone'      => $icone["cadastro"],
            'link'       => 'peca_previsao_entrega.php',
            'titulo'     => traduz('Previs�o de Entrega de Pe�as'),
            'descr'      => traduz('Cadastra a previs�o de entrega de pe�as com abastecimento cr�tico. Os postos ser�o informados da previs�o, e pode-se consultar as pend�ncias destas pe�as para tomada de provid�ncias.'),
            "codigo"     => "CAD-4300"
        ),
        array(
            'fabrica' => array(6,46,30,1,3),
            'icone'   => $icone["cadastro"],
            'link'    => 'peca_represada_cadastro.php',
            'titulo'  => traduz('Pe�as Utilizadas do Estoque do Distribuidor'),
            'descr'   => traduz('Cadastro de Pe�as que o distribuidor n�o vai mais receber automaticamente. As pe�as ir�o gerar cr�dito.<br /><i>A finalidade deste processo � permitir que o distribuidor possa abaixar o estoque de determinadas pe�as.</i>'),
            "codigo"  => "CAD-4310"
        ),
       array(
            'fabrica'    => array(1),
            'icone'      => $icone["cadastro"],
            'link'       => 'acrescimo_tributario.php',
            'titulo'     => traduz('Acr�scimo Tribut�rio por Estado'),
            'descr'      => traduz('Cadastro de Acr�scimo Tribut�rio definido para cada Estado.'),
            "codigo"     => "CAD-4320"
        ),
        array(
            'fabrica'    => $usam_kit_pecas,
            'icone'      => $icone["cadastro"],
            'link'       => 'kit_pecas_cadastro.php',
            'titulo'     => traduz('Kit Pe�as'),
            'descr'      => traduz('Cadastro de Kit de Pe�as.'),
            "codigo"     => "CAD-4330"
        ),
        array(
            'fabrica'    => array(125),
            'icone'      => $icone["cadastro"],
            'link'       => 'peca_kit.php',
            'titulo'     => traduz('Kit Pe�as'),
            'descr'      => traduz('Cadastro de Kit de Pe�as.'),
            "codigo"     => "CAD-4330"
        ),
        array(
            'fabrica'    => array(5),
            'icone'      => $icone["cadastro"],
            'link'       => 'producao_cadastro.php',
            'titulo'     => traduz('Cadastro de Itens de Produ��o'),
            'descr'      => traduz('Cadastro de itens produzidos.'),
            "codigo"     => "CAD-4340"
        ),
        array(
            'fabrica'    => array(30),
            'icone'      => $icone["cadastro"],
            'link'       => 'gera_pedido_dia.php',
            'titulo'     => traduz('Cadastro de Dia de Gera��o de Pedido'),
            'descr'      => traduz('Cadastro dos dias para gerar pedido.'),
            "codigo"     => "CAD-4350"
        ),
        array(
            'fabrica'    => array(11,172),
            'icone'      => $icone["cadastro"],
            'link'       => 'upload_importacao_objeto.php',
            'titulo'     => traduz('Upload Arquivo do N�mero Objeto'),
            'descr'      => traduz('Upload arquivo txt N�mero Objeto de Faturamento.'),
            "codigo"     => "CAD-4360"
        ),
		array(
		'fabrica'    => array(6,15,20,24,30,74,91),
		'icone'      => $icone["cadastro"],
		'link'       => 'ns_analise.php',
		'titulo'  => (in_array($login_fabrica,[6,91])) ? traduz('Cadastro de Garantia por Intervalo de NS') : traduz('Cadastro de N�meros de S�rie para An�lise'),
		'descr'   => (in_array($login_fabrica,[6,91])) ? traduz('Cadastro de Garantia por Intervalo de NS') : traduz('Cadastro de N�meros de S�rie para An�lise'),
		"codigo"     => "CAD-4370"
		),
        array(
            'fabrica'    => array(74),
            'icone'      => $icone["cadastro"],
            'link'       => 'relatorio_ns_analise.php',
            'titulo'     => traduz('Relat�rio de NS'),
            'descr'      => traduz('RELAT�RIO DE NS PARA AN�LISE'),
            "codigo"     => "CAD-4380"
        ),
        array(
            'fabrica'    => array(24),
            'icone'      => $icone["cadastro"],
            'link'       => 'numero_serie_cadastro.php',
            'titulo'     => traduz('Cadastro de N�mero de S�rie'),
            'descr'      => traduz('Cadastro e Manuten��o de N�mero de S�rie'),
            "codigo"     => "CAD-4400"
        ),
        array(
            'fabrica'    => $fabrica_cadastra_serie_pecas,
            'icone'      => $icone["cadastro"],
            'link'       => 'manutencao_numero_serie_peca.php',
            'titulo'     => traduz('Inserir Componentes em Produtos'),
            'descr'      => traduz('Inserir Componentes em Produtos para lan�ento de itens na Ordem de  Servico'),
            "codigo"     => "CAD-4410"
        ),
        array(
            'fabrica'    => array(20),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_peca_devolucao.php',
            'titulo'     => traduz('Pe�as para devolu��o'),
            'descr'      => traduz('Cadastro de pe�as para devolu��o obrigat�ria direcionada para regi�es'),
            "codigo"     => "CAD-4420"
        ),
        array(
            'fabrica'    => array(164),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_valor_frete_estado.php',
            'titulo'     => traduz('Valor de Frete por Estado'),
            'descr'      => traduz('Permite o cadastro de valores de frete para cada estado do Brasil.'),
            "codigo"     => "CAD-4300"
        ),
		array(
			'fabrica'    => array(1, 11, 104, 172),
			'icone'      => $icone["cadastro"],
			'link'       => 'upload_demanda.php',
			'titulo'     => traduz('Upload de Demanda'),
			'descr'      => traduz('Permite o Upload de Demanda.'),
			"codigo"     => "CAD-4440"
		),
		array(
			'fabrica'    => [91],
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_vinculo_pecas.php',
			'titulo'     => traduz('Vincular pe�as'),
			'descr'      => traduz('Vincular pe�as nas quais ja pertence em um pe�a acabada.'),
			"codigo"     => "CAD-4450"
		),
		array(
            'fabrica'    => array(35),
            'icone'      => $icone["cadastro"],
            'link'       => 'helpdesk_motivo_reclamacao_cadastro.php',
            'titulo'     => traduz('Motivo Reclama��o Help-Desk Posto'),
            'descr'      => traduz('Motivo Reclama��o Help-Desk Posto.'),
            "codigo"     => "CAD-4460"
        ),
        array(
			'fabrica'    => array(1),
			'icone'      => $icone["cadastro"],
			'link'       => 'upload_garantia_estendida.php',
			'titulo'     => 'Upload Garantia Estendida',
			'descr'      => 'Permite o Upload e Consulta dos Clientes com Direito a Garantia Estendida.',
			"codigo"     => "CAD-4470"
		),
        array(
            'icone'   => $icone["cadastro"],
            'link'    => 'integracao_parts.php',
            'titulo'  => 'Integra��o Servi�o de Venda de Pe�as',
            'descr'   => 'Exporta��o de pe�as e condi��es de pagamento para servi�o de venda de pe�as.',
            'codigo'  => 'CAD-4310',
            'fabrica' => $integracaoParts ? array($login_fabrica) : array()
        ),
        'link' => 'linha_de_separa��o'
    ),

    //Menu LOCA��O - Apenas Black&Decker
    'secaoLocacao' => array(
        'secao'   => array(
            'link'    => '#',
            'titulo'  => traduz('LOCA��O'),
            'fabrica' => array(1) // Apenas Black&Decker
        ),
        array(
            'icone'   => $icone["cadastro"],
            'link'    => 'os_cadastro_locacao.php',
            'titulo'  => traduz('Cadastro de Produtos Loca��o'),
            'descr'   => traduz('Produtos liberados para Loca��o'),
            "codigo"  => "CAD-5000"
        ),
        array(
            'icone'   => $icone["consulta"],
            'link'    => 'pedido_consulta_locacao.php',
            'titulo'  => traduz('Consulta de Produtos Loca��o'),
            'descr'   => traduz('Consulta Produtos liberados para Loca��o'),
            "codigo"  => "CAD-5010"
        ),
        'link' => 'linha_de_separa��o'
    ),

    'secaoClienteContratual' => array(
        'secao'   => array(
            'link'    => '#',
            'titulo'  => traduz('CLIENTE GARANTIA CONTRATUAL'),
            'fabrica' => array(85) // Apenas Black&Decker
        ),
        array(
            'icone'   => $icone["cadastro"],
            'link'    => 'cliente_garantia_contratual.php',
            'titulo'  => traduz('Cadastro de cliente garantia contratual'),
            'descr'   => traduz('Clientes contratuais'),
            "codigo"  => "CAD-5020"
        ),
        'link' => 'linha_de_separa��o'
    ),

    // SE��O de INTEGRIDADE E RELACIONAMENTO DE DEFEITOS
    'secao1' => array(
        'secao' => array(
            'link'       => '#',
            'titulo'     => in_array($login_fabrica, $fabricas_contrato_lite) ? traduz('CADASTROS DE DEFEITOS') : traduz('CADASTROS DE DEFEITOS - EXCE��ES'),
            'fabrica_no' => array(87)
        ),

        array(
            'fabrica'    => array(30),
            'icone'      => $icone["upload"],
            'link'       => 'indice_defeito_campo.php',
            'titulo'     => traduz('Upload Defeito Campo'),
            'descr'      => traduz('Importa��o do relat�rio de �ndice de defeito de campo.'),
            "codigo"     => "CAD-6000"
        ),
        array(
            'fabrica'    => array(52),
            'icone'      => $icone["cadastro"],
            'link'       => 'motivo_reincidencia.php',
            'titulo'     => traduz('Motivo da Reincid�ncia'),
            'descr'      => traduz('Cadastro de Motivos de Reincid�ncia'),
            "codigo"     => "CAD-6010"
        ),
        array(
            'fabrica'    => array(52),
            'icone'      => $icone["cadastro"],
            'link'       => 'motivo_atraso_fechamento.php',
            'titulo'     => traduz('Motivos de atendimentos fora do prazo'),
            'descr'      => traduz('Cadastro de Motivos de atendimentos fora do prazo'),
            "codigo"     => "CAD-6020"
        ),
        array(
            'disabled'   => !$fabrica_seleciona_defeito_reclamado,            
            'icone'      => $icone["cadastro"],
            'link'       => 'defeito_reclamado_cadastro.php',
            'titulo'     => traduz('Defeitos Reclamados'),
            'descr'      => traduz('Tipos de defeitos reclamados pelo CLIENTE'),
            "codigo"     => "CAD-6030"
        ),
        array(
            'fabrica'    => array(136),
            'icone'      => $icone["cadastro"],
            'link'       => 'defeito_reclamado_produto.php',
            'titulo'     => traduz('Defeitos Reclamados por Produto'),
            'descr'      => traduz('Rela��o de poss�veis defeitos relcamados para cada produto. Usado no CallCenter.'),
            "codigo"     => "CAD-6040"
        ),
        array(
            'fabrica'    => array(25),
            'icone'      => $icone["cadastro"],
            'link'       => 'defeito_reclamado_cadastro_callcenter.php',
            'titulo'     => traduz('Defeitos Reclamados Call Center'),
            'descr'      => traduz('Cadastro de defeitos reclamados no CallCenter'),
            "codigo"     => "CAD-6041"
        ),
        array(
            'fabrica'    => array(11,172),
            'icone'      => $icone["cadastro"],
            'link'       => 'callcenter_motivo_ligacao_cadastro.php',
            'titulo'     => traduz('Motivo Liga��o Call-Center'),
            'descr'      => traduz('Cadastro de motivos das liga��es no Call-Center'),
            "codigo"     => "CAD-6050"
        ),
        array(
            'disabled'    => !$moduloProvidencia && !$classificacaoHD && !in_array($login_fabrica, array(52)),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_classificacao_atendimento.php',
            'titulo'     => ($login_fabrica == 189) ? traduz('Cadastro de Registro Ref. a') : traduz('Classifica��o Call-Center'),
            'descr'      => ($login_fabrica == 189) ? traduz('Cadastro de Registro Ref. a do Call-Center') : traduz('Cadastro de classifica��o do Call-Center'),
            "codigo"     => "CAD-6060"
        ),
        array(
            'fabrica_no' => [175,177,191,193],
            'disabled'    => !$cadastroProvidencia,
            'icone'      => $icone["cadastro"],
            'link'       => 'callcenter_motivo_ligacao_cadastro.php',
            'titulo'     => ($login_fabrica == 189) ? traduz('Cadastro de A��o') : traduz('Provid�ncia Call-Center'),
            'descr'      => ($login_fabrica == 189) ? traduz('Cadastro de A��o do Call-Center') : traduz('Cadastro de provid�ncias do Call-Center'),
            "codigo"     => "CAD-6070"
        ),
        array(
            'fabrica' => [169,170],
            'icone'      => $icone["cadastro"],
            'link'       => 'motivo_ligacao_cadastro_nivel_3.php',
            'titulo'     => traduz('Provid�ncia Call-Center n�vel 3'),
            'descr'      => traduz('Cadastro de um novo n�vel de provid�ncias'),
            "codigo"     => "CAD-6730"
        ),
        array(
            'fabrica' => [169,170],
            'icone'      => $icone["cadastro"],
            'link'       => 'contato_callcenter.php',
            'titulo'     => traduz('Cadastro de Contato callcenter'),
            'descr'      => traduz('Cadastro contatos no callcenter'),
            "codigo"     => "CAD-6750"
        ),
        array(
            'fabrica'    => array(74),
            'icone'      => $icone["cadastro"],
            'link'       => 'callcenter_motivo_ligacao_cadastro.php',
            'titulo'     => traduz('Classe Atendimento Call-Center'),
            'descr'      => traduz('Cadastro de Classe de Atendimento no Call-Center'),
            "codigo"     => "CAD-6080"
        ),
        array(
            'fabrica'    => array(50),
            'icone'      => $icone["cadastro"],
            'link'       => 'callcenter_motivo_ligacao_cadastro.php',
            'titulo'     => traduz('Tipo Atendimento Call-Center'),
            'descr'      => traduz('Cadastro de Tipo Atendimento no Call-Center'),
            "codigo"     => "CAD-6080"
        ),
        array(
            'fabrica'    => array(30),
            'icone'      => $icone["cadastro"],
            'link'       => 'callcenter_motivo_situacao_cadastro.php',
            'titulo'     => traduz('Motivo de Chamado em Aberto'),
            'descr'      => traduz('Cadastro de Motivo de Chamado em Aberto'),
            "codigo"     => "CAD-6090"
        ),
        array( //HD-3352176
            'fabrica'    => array(162,151),
            'icone'      => $icone["cadastro"],
            'link'       => 'callcenter_motivo_situacao_cadastro_new.php',
            'titulo'     => traduz('Cadastro de Motivos da Transfer�ncia'),
            'descr'      => traduz('Cadastro de Motivos da Transfer�ncia'),
            "codigo"     => "CAD-6090"
        ),
        array(
        	'fabrica_no' => array(189),
            'icone'      => $icone["cadastro"],
            'link'       => 'defeito_constatado_cadastro_novo.php',
            'titulo'     => traduz('Defeitos Constatados'),
            'descr'      => traduz('Tipos de defeitos constatados pelo T�CNICO'),
            "codigo"     => "CAD-6100"
        ),
        array(
            'fabrica'    => array_merge(array(42,50,19,90,101,120,201), $fabrica_integridade_familia_reclamado),
            "fabrica_no" => array(175),
            'icone'      => $icone["computador"],
            'link'       => 'familia_integridade_reclamado.php',
            'titulo'     => traduz('Fam�lia - Defeito Reclamado'),
            'descr'      => traduz('Relacionamento/Integridade - Fam�lia - Defeito Reclamado'),
            "codigo"     => "CAD-6110"
        ),
        array(
            'fabrica'    => $fabrica_integridade_familia_constatado,
            'fabrica_no' => array(158,175,189),
            'icone'      => $icone["computador"],
            'link'       => 'familia_integridade_constatado.php',
            'titulo'     => traduz('Fam�lia - Defeito Constatado'),
            'descr'      => traduz('Relacionamento/Integridade - Fam�lia - Defeito Constatado'),
            "codigo"     => "CAD-6120"
        ),

        array(
            'fabrica'    => ($fabrica_integridade_linha_reclamado),
            'fabrica_no' => [19,139,177,178,183,184,186,190,191,193,194,195,198,200,201,203],
            'icone'      => $icone["computador"],
            'link'       => 'linha_integridade_reclamado.php',
            'titulo'     => traduz('Linha - Defeito Reclamado'),
            'descr'      => traduz('Relacionamento/Integridade - Linha - Defeito Reclamado'),
            "codigo"     => "CAD-6740"
        ),

        array(
            'fabrica'    => ($fabrica_integridade_linha_constatado),
            'fabrica_no' => [19,139,177,178,183,184,186,189,190,191,193,194,195,198,200,201,203],
            'icone'      => $icone["computador"],
            'link'       => ($usa_linha_defeito_constatado == 't') ? 'linha_integridade_constatado_new.php' : 'linha_integridade_constatado.php',
            'titulo'     => traduz('Linha - Defeito Constatado'),
            'descr'      => traduz('Relacionamento/Integridade - Linha - Defeito Constatado'),
            "codigo"     => "CAD-6130"
        ),

        array(
            'fabrica'    => array(158),
            'icone'      => $icone["cadastro"],
            'link'       => 'reclamado_integridade_constatado.php',
            'titulo'     => traduz('Defeito Reclamado x Defeito Constatado'),
            'descr'      => traduz('Integridade de defeito reclamado x defeito constatado'),
            "codigo"     => "CAD-6140"
        ),
        array(
            'fabrica'    => array(52,175,178),
            'icone'      => $icone["cadastro"],
            'link'       => 'grupo_defeito_constatado_cadastro_fricon.php',
            'titulo'     => traduz('Grupo de Defeitos Constatados'),
            'descr'      => traduz('Cadastro/Manuten��o nos grupos de defeitos constatados pelo T�CNICO'),
            "codigo"     => "CAD-6150"
        ),
        array(
            'fabrica'    => array(52),
            'icone'      => $icone["cadastro"],
            'link'       => 'manutencao_mao_de_obra_linha_defeito.php',
            'titulo'     => traduz('Manuten��o m�o-de-obra'),
            'descr'      => traduz('Cadastro/Manuten��o de valores m�o de obra'),
            "codigo"     => "CAD-6160"
        ),
        array(//chamado 2977
        	'fabrica_no' => array(139,178,189),
            'fabrica' => array(131,177),
            'icone'      => $icone["cadastro"],
            'link'       => 'causa_defeito_cadastro.php',
            'titulo'     => ($login_fabrica == 177) ? traduz('Defeitos Constatados Gen�ricos') : traduz('Causa de Defeitos'),
            'descr'      => ($login_fabrica == 177) ? traduz('Cadastro de defeitos constatados gen�ricos') : traduz('Causas de defeitos constatados pelo T�CNICO'),
            "codigo"     => "CAD-6170"
        ),
        array(//chamado 6220900
        	'fabrica_no' => array(139,178,184,191,200),
            'fabrica'    => $fabricasPecaExcedenteLB,
            'icone'      => $icone["cadastro"],
            'link'       => 'causa_defeito_cadastro.php',
            'titulo'     => ($login_fabrica == 183) ? traduz('C�digo de utilizacao') : traduz('Cadastro de Motivos'),
            'descr'      => ($login_fabrica == 183) ? traduz('Cadastro dos c�digos de utiliza��o para integra��o pedidos') : traduz('Cadastro de Motivos para a Segunda Solicita��o de Pe�as Pelo Posto Autorizado'),
            "codigo"     => "CAD-6170"
        ),
	array(
            'fabrica_no' => array_merge($fabricas_contrato_lite, array(86,138,165,189,191)),
            'icone'      => $icone["cadastro"],
            'link'       => 'excecao_cadastro.php',
            'titulo'     => ($login_fabrica == 183) ? traduz('Cadastro de Bonifica��o') : traduz('Exce��o de m�o-de-obra'),
            'descr'      => ($login_fabrica == 183) ? traduz('Cadastro de Bonifica��o') : traduz('Cadastro das exce��es de m�o-de-obra'),
            "codigo"     => "CAD-6180"
        ),
        array(
            'fabrica'    => array(101),
            'icone'      => $icone["cadastro"],
            'link'       => 'excecao_cadastro.php',
            'titulo'     => traduz('Exce��o de m�o-de-obra'),
            'descr'      => traduz('Cadastro das exce��es de m�o-de-obra'),
            "codigo"     => "CAD-6180"
        ),
        array(
            'fabrica'    => array(15),
            'icone'      => $icone['cadastro'],
            'link'       => 'excecao_cadastro_new.php',
            'titulo'     => traduz('Manuten��o Exce��o de m�o-de-obra'),
            'descr'      => traduz('Manuten��o das exce��es de m�o-de-obra'),
            "codigo"     => "CAD-6190"
        ),
        array(
            'fabrica'    => 0,
            'icone'      => $icone["cadastro"],
            'link'       => 'excecao_cadastro_black.php',
            'titulo'     => traduz('Exce��o de m�o-de-obra(Nova Tela)'),
            'descr'      => traduz('Cadastro das exce��es de m�o-de-obra'),
            "codigo"     => "CAD-6200"
        ),
        array(
            'fabrica'    => array(45, 80),
            'icone'      => $icone["cadastro"],
            'link'       => 'extrato_lancamento_mensal.php',
            'titulo'     => traduz('Valor fixo mensal para postos'),
            'descr'      => traduz('Cadastro de valores que ser�o inclu�dos todos os meses ao extrato'),
            "codigo"     => "CAD-6210"
        ),
        array(
            'fabrica'    => array(20),
            'icone'      => $icone["cadastro"],
            'link'       => 'servico_realizado_cadastro.php',
            'titulo'     => traduz('Cadastro de Identifica��o'),
            'descr'      => traduz('Cadastro de Identifica��o, terceiro c�digo de falha'),
            "codigo"     => "CAD-6220"
        ),
        array(
            'fabrica_no' => (isset($novaTelaOs) && $login_fabrica <> 148) ? array($login_fabrica) : array(20),
            'icone'      => $icone["cadastro"],
            'link'       => 'servico_realizado_cadastro.php',
            'titulo'     => traduz('Servi�os'),
            'descr'      => traduz('Cadastro de servi�os realizados'),
            "codigo"     => "CAD-6230"
        ),
        array(
            'fabrica'    => array(14),
            'icone'      => $icone["cadastro"],
            'link'       => 'servico_realizado_tipo_posto.php',
            'titulo'     => traduz('Cadastro de Servi�os Realizados x Tipos de Postos'),
            'descr'      => traduz('Cadastro de servi�os realizados x tipos de postos e cadastro de exce��o por posto'),
            "codigo"     => "CAD-6240"
        ),
        array(
            'fabrica'    => array_merge($fabrica_integridade_reclamado_constatado,array(131,125)),
            'icone'      => $icone["cadastro"],
            'link'       => 'defeito_causa_defeito_cadastro.php',
            'titulo'     => traduz('Defeitos x Causa do Defeito'),
            'descr'      => traduz('Cadastro da rela��o entre os defeitos e suas causas poss�veis'),
            "codigo"     => "CAD-6250"
        ),
        array(
            'fabrica'    => $fabrica_integridade_reclamado_constatado,
            'icone'      => $icone["cadastro"],
            'link'       => 'defeito_reclamado_defeito_constatado.php',
            'titulo'     => traduz('Defeito Constatado x Reclamado'),
            'descr'      => traduz('Cadastro da rela��o entre os defeitos reclamados e seus poss�veis defeitos constatados'),
            "codigo"     => "CAD-6260"
        ),
        array(
            'fabrica' => $arr_fabrica_defeito,
            'icone'   => $icone["cadastro"],
            'link'    => 'defeito_cadastro.php',
            'titulo'  => traduz('Defeito em Pe�as'),
            'descr'   => traduz('Cadastro de defeitos que podem ocorrer nas pe�as'),
            "codigo"  => "CAD-6270"
        ),
        array(
            'fabrica' => array(30,151),
            'icone'   => $icone["cadastro"],
            'link'    => 'cadastro_familia_defeito_peca.php',
            'titulo'  => traduz('Rela��o Defeito da Pe�a X Fam�lia'),
            'descr'   => traduz('Cadastro da rela��o do defeito da pe�a com fam�lia'),
            "codigo"  => "CAD-6280"
        ),
        array(
            'fabrica' => array(151,158),
            'icone'   => $icone["cadastro"],
            'link'    => 'cadastro_familia_defeito_constatado_peca.php',
            'titulo'  => traduz('Rela��o Defeito Constatado X Fam�lia de Pe�a'),
            'descr'   => traduz('Cadastro da rela��o do defeito constatado com fam�lia da pe�a'),
            "codigo"  => "CAD-6290"
        ),
        array(
            'fabrica' => $arr_fabrica_solucao,
            'icone'   => $icone["cadastro"],
            'link'    => 'solucao_cadastro.php',
            'titulo'  => ($login_fabrica == 191) ? traduz('Servi�o Realizado') : traduz('Solu��o'),
            'descr'   => ($login_fabrica == 191) ? traduz('Cadastro de Servi�o Realizado de um defeito') : traduz('Cadastro de Solu��o de um defeito'),
            "codigo"  => "CAD-6300"
        ),
        array(
            'fabrica'    => array(52,158),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_defeitos_solucoes.php',
            'titulo'     => ($login_fabrica == 158) ? traduz('Integridade Fam�lia/Defeito Constatado/Solu��o') : traduz('Integridade Solu��o e Defeitos Constatados'),
            'descr'      => ($login_fabrica == 158) ? traduz('Cadastro de integridade de fam�lia, defeito constatado e solu��o') : traduz('Cadastro de integridade de Solu��o x Defeitos Constatados'),
            "codigo"     => "CAD-6310"
        ),
        array(
            'fabrica'    => array(74,117,145),
            'icone'      => $icone["cadastro"],
            'link'       => 'solucao_familia_cadastro.php',
            'titulo'     => traduz('Integridade Fam�lia e Solu��o'),
            'descr'      => traduz('Cadastro de integridade de Solu��o x Fam�lia'),
            "codigo"     => "CAD-6320"
        ),
        array(
            'fabrica'    => 1,
            'icone'      => $icone["cadastro"],
            'link'       => 'linha_solucao_cadastro.php',
            'titulo'     => traduz('Linha x Solu��o'),
            'descr'      => traduz('Cadastro de Solu��o de um defeito para cada linha (Objetivo � para o posto digitar a solu��o somente da linha)'),
            "codigo"     => "CAD-6330"
        ),
        array( //Volta o menu para LeaderShip HD 731929
            'fabrica'    => 95,
            'icone'      => $icone["computador"],
            'link'       => 'relacionamento_diagnostico.php',
            'titulo'     => traduz('Relacionamento de Integridade'),
            'descr'      => traduz('Relacionamento de Linha, Familia, Defeito Reclamado, Defeito Constatado e Solu��o para o Diagn�stico'),
            "codigo"     => "CAD-6340"
        ),
        array(
            'fabrica'    => array(1,3,6,10,11,19,24,59,80,88,90,114,116,172),
            'icone'      => $icone["computador"],
            'link'       => 'relacionamento_diagnostico.php',
            'titulo'     => traduz('Relacionamento de Integridade'),
            'descr'      => traduz('Relacionamento de Linha, Familia, Defeito Reclamado, Defeito Constatado e Solu��o para o Diagn�stico'),
            "codigo"     => "CAD-6350"
        ),
        array(
            'fabrica'    => $fabrica_usa_rel_diag_new,
            'fabrica_no' => array_merge(array(59,66,131,139,141,176,193), ((isset($novaTelaOs) and $login_fabrica <> 35) ? array($login_fabrica) : array())),
            'icone'      => $icone["computador"],
            'link'       => 'relacionamento_diagnostico_new.php',
            'titulo'     => traduz('Relacionamento de Integridade'),
            'descr'      => traduz('Relacionamento de Linha, Familia, Defeito Reclamado, Defeito Constatado e Solu��o para o Diagn�stico'),
            "codigo"     => "CAD-6360"
        ),
        array(
            'fabrica'    => array(15),
            'icone'      => $icone["computador"],
            'link'       => 'os_acerto_defeito.php',
            'titulo'     => traduz('Acertos de OSs cadastradas'),
            'descr'      => traduz('Acerto dos cadastro dos defeitos das OSs.'),
            "codigo"     => "CAD-6370"
        ),
        array(
            'fabrica'    => $fabrica_integridade_peca,
            'icone'      => $icone["cadastro"],
            'link'       => 'peca_integridade.php',
            'titulo'     => traduz('Integridade de Pe�as'),
            'descr'      => traduz('Cadastro de integridade de pe�as'),
            "codigo"     => "CAD-6380"
        ),
        array(
            'fabrica'    => array(20),
            'icone'      => $icone["cadastro"],
            'link'       => 'produto_custo_tempo_cadastro.php',
            'titulo'     => traduz('Cadastro de Custo Tempo'),
            'descr'      => traduz('Cadastro e atuliza��o de custo tempo por produtos'),
            "codigo"     => "CAD-6390"
        ),
        array(
        	'fabrica_no' => array(189),
            'icone'      => $icone["cadastro"],
            'link'       => 'causa_troca_cadastro_new.php',
            'titulo'     => traduz('Cadastro de Causa de Troca'),
            'descr'      => traduz('Cadastro das causas da troca do produto'),
            "codigo"     => "CAD-6400"
        ),
        array(
            'fabrica'    => array(0,189),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_subclassificacoes.php',
            'titulo'     => ($login_fabrica == 189) ? traduz('Cadastro de Especifica��o de Refer�ncia de Registro') : traduz('Subclassifica��es Call-Center'),
            'descr'      => ($login_fabrica == 189) ? traduz('Cadastro de Especifica��o de Refer�ncia de Registro  Call-Center') : traduz('Cadastro de Subclassifica��es Call-Center'),
            "codigo"     => "CAD-6410"
        ),
        array(
            'fabrica'    => array(15),
            'icone'      => $icone["cadastro"],
            'link'       => 'rel_area_atuacao_familia.php',
            'titulo'     => traduz('Relacionamento Area Atua��o X Fam�lia'),
            'descr'      => traduz('Cadastro dos relacionamentos das �reas de atua��o com fam�lias de produtos'),
            "codigo"     => "CAD-6410"
        ),
        array(
            'fabrica'    => array(6),
            'icone'      => $icone["cadastro"],
            'link'       => 'causa_troca_item_cadastro.php',
            'titulo'     => traduz('Cadastro dos Itens de Causa de Troc'),
            'descr'      => traduz('Cadastro dos Itens das causas da troca do produto'),
            "codigo"     => "CAD-6420"
        ),
        array(
			'fabrica'    => array(169,170,183),
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_blacklist_cep.php',
			'titulo'     => traduz('Blacklist de Cep'),
			'descr'      => traduz('Cadastro de blacklist de CEP'),
			"codigo"     => "CAD-6420"
		),
        array(
            'fabrica'    => $array_script_falha,
            'fabrica_no' => [177],
            'icone'      => $icone["cadastro"],
            'link'       => 'pesquisa_script_falha.php',
            'titulo'     => traduz('Script de falha'),
            'descr'      => traduz('Tela para Pesquisar/Cadastrar o script de falha por defeito reclamado x fam�lia ou defeito reclamado x produto'),
            "codigo"     => "CAD-6430"
        ),
        array(
            'fabrica'    => $fabrica_pede_laudo_tecnico,
            'icone'      => $icone["cadastro"],
            'link'       => 'laudo_tecnico_cadastro.php',
            'titulo'     => traduz('Cadastro de question�rio'),
            'descr'      => ($login_fabrica==19)?
                traduz('Cadastro de question�rio por linha de produto para atendimento em domic�lio'):
                traduz('Cadastro dos Laudos T�nicos por Produto ou Fam�lia'),
            "codigo"     => "CAD-6440"
        ),
        array(
            'fabrica'    => array(30,92),
            'icone'      => $icone["cadastro"],
            'link'       => ($login_fabrica == 30) ? 'cadastro_item_servico_new.php' : 'cadastro_item_servico.php',
            'titulo'     => traduz('Cadastro de Itens de Servi�o'),
            'descr'      => traduz('Cadastro de Itens de Servi�o'),
            "codigo"     => "CAD-6450"
        ),
        array(
            'fabrica'    => array(74,91,120,201,131,157),
            'icone'      => $icone["cadastro"],
            'link'       => 'integridade_peca_defeito_cadastro.php',
            'titulo'     => ($login_fabrica == 157) ? traduz("Cadastro de Integridade Pe�a Motivo") : traduz('Cadastro de Integridade Pe�a Defeito'),
            'descr'      => ($login_fabrica == 157) ? traduz("Cadastro de Integridade entre Pe�as e Motivos") : traduz('Cadastro de Integridade entre Pe�as e Defeitos'),
            "codigo"     => "CAD-6460"
        ),
        array(
            'fabrica'    => array(15,74,131),
            'icone'      => $icone["cadastro"],
            'link'       => 'servico_realizado_integridade_cadastro.php',
            'titulo'     => traduz('Cadastro de Integridade de Servi�o e Defeito'),
            'descr'      => traduz('Cadastro de Integridade de Servi�o Realizado e Defeitos'),
            "codigo"     => "CAD-6470"
        ),
        array(
            'fabrica'    => array(15,74),
            'icone'      => $icone["cadastro"],
            'link'       => 'produto_serie_integridade.php',
            'titulo'     => traduz('Cadastro de Integridade de Produto e S�rie'),
            'descr'      => traduz('Cadastro da Integridade de Produtos com N�mero de S�ries para controle de OS.'),
            "codigo"     => "CAD-6480"
        ),
        array(
            'fabrica'    => array(74,52),
            'icone'      => $icone["cadastro"],
            'link'       => 'hd_status_cadastro.php',
            'titulo'     => traduz('Cadastro de status Call-Center'),
            'descr'      => traduz('Cadastro de status do atendimento Call-Center.'),
            "codigo"     => "CAD-6490"
        ),
        array(
            'fabrica'    => array(131),
            'icone'      => $icone["cadastro"],
            'link'       => 'mobra_servico_realizado_hora.php',
            'titulo'     => traduz('Cadastro de M�o de Obra por Servi�o'),
            'descr'      => traduz('Cadastro de M�o de Obra por Servi�o'),
            "codigo"     => "CAD-6500"
        ),
        array(
            'fabrica'    => array(162),
            'icone'      => $icone["cadastro"],
            'link'       => 'mobra_servico_realizado.php',
            'titulo'     => traduz('Cadastro de M�o de Obra por Servi�o'),
            'descr'      => traduz('Cadastro de M�o de Obra por Servi�o'),
            "codigo"     => "CAD-6510"
        ),
		array(
            'fabrica'    => array(19,40,134,157,165),

            'icone'      => $icone["cadastro"],
            'link'       => 'defeito_constatado_peca_cadastro.php',
            'titulo'     => traduz('Defeito Constatado Por Pe�a'),
            'descr'      => traduz('Cadastro de Defeito Constatado por Pe�as'),
            "codigo"     => "CAD-6520"
        ),
        array(
            'fabrica'    => array(131),
            'icone'      => $icone["cadastro"],
            'link'       => 'servico_realizado_grupo_cadastro.php',
            'titulo'     => traduz('Cadastro de Grupos de Servi�os'),
            'descr'      => traduz('Cadastro de Grupos de Servi�os'),
            "codigo"     => "CAD-6530"
        ),
        array(
            'fabrica'    => array(131),
            'icone'      => $icone["cadastro"],
            'link'       => 'servico_realizado_codigo_cadastro.php',
            'titulo'     => traduz('Agrupamento de Servi�os'),
            'descr'      => traduz('Agrupamento de Servi�os'),
            "codigo"     => "CAD-6540"
        ),
        array(
            'fabrica'    => array(138,148,149,191),
            'icone'      => $icone["cadastro"],
            'link'       => 'integridade_familia_solucao_mo.php',
            'titulo'     => (in_array($login_fabrica,[138,148])) ? traduz('Fam�lia - Solu��o - M�o de obra') : ($login_fabrica == 191) ? traduz('Fam�lia - Servi�o Realizado - M�o de obra') : traduz('Relacionamento Linha X Solu��o'),
            'descr'      => (in_array($login_fabrica,[138,148])) ? traduz('Relacionamento de Fam�lia x Solu��o x M�o de obra') : ($login_fabrica == 191) ? traduz('Relacionamento de Fam�lia x Servi�o Realizado x M�o de obra') : traduz('Relacionamento de Linha x Solu��o'),
            "codigo"     => "CAD-6550"
        ),
        array(
            'fabrica'    => array(148),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_revisoes.php',
            'titulo'     => traduz('Manuten��o de Revis�es'),
            'descr'      => traduz('Manuten��o de revis�es de pe�as por produto'),
            "codigo"     => "CAD-6560"
        ),
        array(
            'fabrica' => array(158),
            'icone'      => $icone["cadastro"],
            'link'       => 'classificacao_cadastro.php',
            'titulo'     => traduz('Classifica��o'),
            'descr'      => traduz('Cadastro de Classifica��o'),
            "codigo"     => "CAD-6570"
        ),
        array(
            'fabrica' => array(72),
            'icone'      => $icone["cadastro"],
            'link'       => 'falhas_em_potencial.php',
            'titulo'     => traduz('Falhas em Potencial'),
            'descr'      => traduz('Cadastro de Falhas em Potencial'),
            "codigo"     => "CAD-6580"
        ),
        array(
            'fabrica' => array(72),
            'icone'      => $icone["cadastro"],
            'link'       => 'defeito_constatado_com_falha_em_potencial.php',
            'titulo'     => traduz('Defeito Constatado com Falha em Potencial'),
            'descr'      => traduz('Cadastro de Defeito Constatado com Falha em Potencial'),
            "codigo"     => "CAD-6590"
        ),
         array(
            'fabrica'    => array(24),
            'icone'      => $icone["cadastro"],
            'link'       => 'motivo_sintetico.php',
            'titulo'     => traduz('Cadastro de Motivos Sint�ticos'),
            'descr'      => traduz('Cadastro de motivos sint�ticos espec�ficos para o posto interno'),
            "codigo"     => "CAD-6600"
        ),
        array(
            'fabrica'    => array(24),
            'icone'      => $icone["cadastro"],
            'link'       => 'motivo_analitico.php',
            'titulo'     => traduz('Cadastro de Motivos Anal�ticos'),
            'descr'      => traduz('Cadastro de motivos anal�ticos espec�ficos para o posto interno'),
            "codigo"     => "CAD-6610"
        ),
        array(
            'fabrica'    => array(24),
            'icone'      => $icone["cadastro"],
            'link'       => 'analise_produto.php',
            'titulo'     => traduz('Cadastro de �nalise do Produto'),
            'descr'      => traduz('Cadastra as �nalises padr�o das devolu��es de produtos. Posto interno'),
            "codigo"     => "CAD-6620"
        ),
        array(
            'fabrica'    => array(169,170,175),
            'icone'      => $icone["cadastro"],
            'link'       => 'jornada_cadastro.php',
            'titulo'	 => ($login_fabrica == 175) ? traduz('Jornada da Ordem de Servi�o') : traduz('Jornada Callcenter'),
            'descr'      => ($login_fabrica == 175) ? traduz('Tela para cadastrar a regra de acompanhamento das Ordens de Servi�o') : traduz('Tela para cadastrar a regra de atendimentos que devem ter a vida da Ordem de Servi�o acompanhada pelo Callcenter'),
            "codigo"     => "CAD-6630"
        ),
        array(
            'fabrica'    => array(169,170),
            'icone'      => $icone["cadastro"],
            'link'       => 'relatorio_defeito_constatado_defeito_peca.php',
            'titulo'     => traduz('Relatorio Defeito Constatado X Defeito Pe�a'),
            'descr'      => traduz('Tela para pesquisar os Defeitos de Pe�as relacionados com Defeito Constatado.'),
            "codigo"     => "CAD-6640"
        ),
        array(
            'fabrica'    => array_merge($fabrica_cadastra_num_serie, array(169,170,175,176,183)),
            'icone'      => $icone["cadastro"],
            'link'       => 'manutencao_numero_serie.php',
            'titulo'     => traduz('Cadastro de N�mero de S�rie'),
            'descr'      => traduz('Cadastro e Manuten��o de N�mero de S�rie'),
            "codigo"     => "CAD-6670"
        ),
        array(
            'fabrica'    => array(24),
            'icone'      => $icone["cadastro"],
            'link'       => 'numero_serie_cadastro.php',
            'titulo'     => traduz('Cadastro de N�mero de S�rie'),
            'descr'      => traduz('Cadastro e Manuten��o de N�mero de S�rie'),
            "codigo"     => "CAD-6680"
        ),
        array(
            'fabrica'    => $fabrica_cadastra_serie_pecas,
            'icone'      => $icone["cadastro"],
            'link'       => 'manutencao_numero_serie_peca.php',
            'titulo'     => traduz('Inserir Componentes em Produtos'),
            'descr'      => traduz('Inserir Componentes em Produtos para lan�ento de itens na Ordem de  Servico'),
            "codigo"     => "CAD-6690"
        ),
        array(
            'fabrica'    => array(20),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_peca_devolucao.php',
            'titulo'     => traduz('Pe�as para devolu��o'),
            'descr'      => traduz('Cadastro de pe�as para devolu��o obrigat�ria direcionada para regi�es'),
            "codigo"     => "CAD-6700"
        ),
        array(
            'fabrica'    => array(164),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_valor_frete_estado.php',
            'titulo'     => traduz('Valor de Frete por Estado'),
            'descr'      => traduz('Permite o cadastro de valores de frete para cada estado do Brasil.'),
            "codigo"     => "CAD-6710"
        ),
        array(
            'fabrica'    => array(175),
            'icone'      => $icone["cadastro"],
            'link'       => 'manutencao_numero_serie_peca_new.php',
            'titulo'     => traduz('Cadastro de N�mero de S�rie de Pe�as'),
            'descr'      => traduz('Cadastro e Manuten��o de N�mero de S�rie de Pe�as'),
            'codigo'     => "CAD-6720"
        ),
        array(
            'fabrica'    => array(158),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_tabela_garantia.php',
            'titulo'     => traduz('Cadastro de Tabela de Garantia'),
            'descr'      => traduz('Cadastro de Tabela de Garantia'),
            'codigo'     => 'CAD-6730'
        ),
        array(
            'fabrica'    => array(148),
            'icone'      => $icone["cadastro"],
            'link'       => 'mo_categoria_posto.php',
            'titulo'     => 'M�o-de-Obra por Categoria de Posto',
            'descr'      => 'Cadastro de M�o-de-Obra por categoria de posto',
            'codigo'     => 'CAD-6740'
        ),
        array(
            'fabrica'    => array(157),
            'icone'      => $icone["cadastro"],
            'link'       => 'defeito_constatado_produto.php',
            'titulo'     => 'Defeitos Constatados por produto',
            'descr'      => 'Rela��o de poss�veis defeitos constatados para cada produto',
            "codigo"     => "CAD-6750"
        ),
        'link' => 'linha_de_separa��o'
    ),

    //Menu LOCA��O - Apenas Black&Decker
    'secaoLocacao' => array(
        'secao'   => array(
            'link'    => '#',
            'titulo'  => traduz('LOCA��O'),
            'fabrica' => array(1) // Apenas Black&Decker
        ),
        array(
            'icone'   => $icone["cadastro"],
            'link'    => 'os_cadastro_locacao.php',
            'titulo'  => traduz('Cadastro de Produtos Loca��o'),
            'descr'   => traduz('Produtos liberados para Loca��o'),
            "codigo"  => "CAD-5000"
        ),
        array(
            'icone'   => $icone["consulta"],
            'link'    => 'pedido_consulta_locacao.php',
            'titulo'  => traduz('Consulta de Produtos Loca��o'),
            'descr'   => traduz('Consulta Produtos liberados para Loca��o'),
            "codigo"  => "CAD-5010"
        ),
        'link' => 'linha_de_separa��o'
    ),

    'secao9' => array(
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('AN�LISE DE PE�AS'),
            'fabrica'    => array(129)
        ),
        array(
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_tecnico.php',
            'titulo'     => traduz('T�cnicos'),
            'descr'      => traduz('Cadastro de T�cnicos'),
            "codigo"     => "CAD-7000"
        ),
        array(
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_status_analise_peca.php',
            'titulo'     => 'Posi��o de An�lise',
            'descr'      => 'Cadastro da Posi��o da An�lise',
            "codigo"     => "CAD-7010"
        ),
        array(
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_origem_recebimento.php',
            'titulo'     => traduz('Origem de Recebimento'),
            'descr'      => traduz('Cadastro de Origem de Recebimento'),
            "codigo"     => "CAD-7020"
        ),
        array(
            'icone'      => $icone["cadastro"],
            'link'       => 'analise_pecas.php',
            'titulo'     => traduz('An�lise de Pe�as'),
            'descr'      => traduz('Cadastro de An�lise de Pe�as'),
            "codigo"     => "CAD-7030"
        ),
        array(
            'icone'      => $icone["computador"],
            'link'       => 'relatorio_analise_pecas.php',
            'titulo'     => traduz('Relat�rio de An�lise de Pe�as'),
            'descr'      => traduz('Relat�rio de An�lise de Pe�as cadastradas'),
            "codigo"     => "CAD-7040"
        ),
        'link' => 'linha_de_separa��o'
    ),

	'secao8' => array(
		'secao' => array(
			'link'       => '#',
			'titulo'     => traduz('AN�LISE DE PE�AS'),
			'fabrica'    => array(128)
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_tecnico.php',
			'titulo'     => traduz('T�cnicos'),
			'descr'      => traduz('Cadastro de T�cnicos'),
			"codigo"     => "CAD-7001"
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_status_analise_peca.php',
			'titulo'     => traduz('Posi��o de An�lise'),
			'descr'      => traduz('Cadastro da Posi��o da An�lise'),
			"codigo"     => "CAD-7011"
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'cadastro_origem_recebimento.php',
			'titulo'     => traduz('Origem de Recebimento'),
			'descr'      => traduz('Cadastro de Origem de Recebimento'),
			"codigo"     => "CAD-7021"
		),
		array(
			'icone'      => $icone["cadastro"],
			'link'       => 'analise_pecas.php',
			'titulo'     => traduz('An�lise de Pe�as'),
			'descr'      => traduz('Cadastro de An�lise de Pe�as'),
			"codigo"     => "CAD-7031"
		),
		array(
			'icone'      => $icone["computador"],
			'link'       => 'relatorio_analise_pecas.php',
			'titulo'     => traduz('Relat�rio de An�lise de Pe�as'),
			'descr'      => traduz('Relat�rio de An�lise de Pe�as cadastradas'),
			"codigo"     => "CAD-7041"
		),
		'link' => 'linha_de_separa��o'
	),

    // SE��O de EXTRATOS
    'secao2' => array(
        'secao'      => array(
            'link'       => '#',
            'titulo'     => traduz('CADASTROS REFERENTES AO EXTRATO'),
            'fabrica_no' => array(87,189)
        ),
        array(
            'icone'      => $icone["cadastro"],
            'link'       => 'lancamentos_avulsos_cadastro.php',
            'titulo'     => traduz('Lan�amentos Avulsos'),
            'descr'      => traduz('Cadastro dos Lan�amentos Avulsos ao Extrato'),
            "codigo"     => "CAD-8000"
        ),
        array(
            'fabrica'    => array(50),
            'icone'      => $icone["email"],
            'link'       => 'colormaq_email_devolucao_cad.php',
            'titulo'     => traduz('E-mail de NF de Devolu��o'),
            'descr'      => traduz('Cadastro do e-mail enviado aos postos cobrando a NF de devolu��o'),
            "codigo"     => "CAD-8010"
        ),
        array(
            'fabrica'    => array(3),
            'icone'      => $icone["cadastro"],
            'link'       => 'tipo_nota_cadastro.php',
            'titulo'     => traduz('Tipo de Nota'),
            'descr'      => traduz('Cadastro de tipo de nota para o extrato'),
            "codigo"     => "CAD-8020"
        ),
        array(
            'fabrica'    => array(35),
            'icone'      => $icone["cadastro"],
            'link'       => 'motivo_recusa_cadastro_mao_obra.php',
            'titulo'     => traduz('Motivo Cancelamento'),
            'descr'      => traduz('Cadastro para motivos de Cancelamento de extratos'),
            "codigo"     => "CAD-8030"
        ),
        array(
            'fabrica'    => array(145),
            'icone'      => $icone["cadastro"],
            'link'       => 'analise_peca_lgr.php',
            'titulo'     => traduz('An�lise de Pe�a'),
            'descr'      => traduz('Cadastra as an�lises das pe�as para confer�ncia do LGR'),
            "codigo"     => "CAD-8040"
        ),
        array(
            'fabrica'    => array(1),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_intervalo_extrato.php',
            'titulo'     => traduz('Manuten��o Gera��o Extrato'),
            'descr'      => traduz('Cadastra os intervalos de gera��o de extratos por regi�o'),
            "codigo"     => "CAD-8050"
        ),
        array(
            'fabrica'    => array(85),
            'icone'      => $icone["cadastro"],
            'link'       => 'servico_diferenciado.php',
            'titulo'     => traduz('Bonifica��o por Servi�o Diferenciado'),
            'descr'      => traduz('Cadastro de valores de bonifica��o por servi�o diferenciado.'),
            "codigo"     => "CAD-8060"
		),
		array(
            'fabrica'    => array(178),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_valor_qtde_os.php',
            'titulo'     => traduz('Cadastro de valores para m�o de obra'),
            'descr'      => traduz('Cadastro de valores para m�o de obra.'),
            "codigo"     => "CAD-8070"
        ),
        'link' => 'linha_de_separa��o'
    ),
    'secao7' => array(
        'secao'      => array(
            'link'       => '#',
            'titulo'     => traduz('CADASTROS REFERENTES � UNIDADES DE NEG�CIO'),
            'fabrica' => array(158)
        ),
        array(
            'fabrica'    => array(158),
            'icone'      => $icone["cadastro"],
            'link'       => 'responsavel_unidade_negocio.php',
            'titulo'     => traduz('Unidades de Neg�cios x E-mails'),
            'descr'      => traduz('Cadastro de admins que receber�o e-mails informando intera��es na Ordem de Servi�o.'),
            "codigo"     => "CAD-8500"
        ),
        'link' => 'linha_de_separa��o'
    ),
    // SE��O DE MANUTEN��O DE POSTOS AUTORIZADOS
    'secao3' => array(
        'secao' => array(
            'link'       => '#',
            'titulo'     => (!isset($novaTelaOs)) ? traduz('MANUTEN��O DE POSTOS AUTORIZADOS') : traduz('DIVERSOS'),
            'fabrica_no' => array(87)
        ),
        array(
	    'fabrica'    => $fabrica_tem_clientes_admin,
	    'fabrica_no' => array(191),
            'icone'      => $icone["cadastro"],
            'link'       => 'cliente_admin_cadastro.php',
            'titulo'     => ($login_fabrica==96)?traduz('Cadastro de Clientes'):traduz('Clientes Admin'),
            'descr'      => traduz('Cadastro de Clientes que ter�o acesso a abertura de Pr�-OS'),
            "codigo"     => "CAD-9000"
        ),
        array(
            'icone'      => $icone["cadastro"],
            'link'       => 'posto_cadastro.php',
            'titulo'     => ($login_fabrica == 189) ? traduz('Representantes/Revendas') : traduz('Postos Autorizados'),
            'descr'      => ($login_fabrica == 189) ? traduz('Cadastro de Representantes/Revendas') : traduz('Cadastro de postos autorizados'),
            "codigo"     => "CAD-9010"
        ),
        array(
            'fabrica'    => array(0),
            'icone'      => $icone["computador"],
            'link'       => 'controle_salton.php',
            'titulo'     => traduz('Controle Boaz Credenciamento'),
            'descr'      => traduz('Controle dos postos que responderam o email de auto-credenciamento.'),
            "codigo"     => "CAD-9020"
        ),
        array(
            'fabrica'    => array(15),
            'icone'      => $icone["consulta"],
            'link'       => 'relatorio_atualizacao_dados_posto.php',
            'titulo'     => traduz('Consulta Atualiza��o Cadastro Postos'),
            'descr'      => traduz('Consulta a atualiza��o cadastral obrigat�ria dos postos.'),
            "codigo"     => "CAD-9030"
        ),
        array(
            'icone'      => $icone["computador"],
            'link'       => 'credenciamento.php',
            'titulo'     => ($login_fabrica == 189) ? traduz('Credenciamento de Representantes/Revendas') : traduz('Credenciamento de Postos'),
            'descr'      => ($login_fabrica == 189) ? traduz('Credenciamento de Representantes/Revendas') : traduz('Credenciamento de postos autorizados.'),
            "codigo"     => "CAD-9040"
        ),
        array(
            'fabrica'    => array(15),
            'icone'      => $icone["cadastro"],
            'link'       => 'valor_km_posto.php',
            'titulo'     => traduz('Cadastro de Valor de KM por Posto'),
            'descr'      => traduz('Cadastro de Valor de KM por Posto Autorizado.'),
            "codigo"     => "CAD-9050"
        ),
        array(
            'fabrica_no' => array_merge(array($fabricas_contrato_lite),array(122,81,114,124,81,114,123,124,166,189,193),$arr_fabrica_padrao),
            'icone'      => $icone["cadastro"],
            'link'       => 'revenda_cadastro.php',
            'titulo'     => traduz('Revendas'),
            'descr'      => traduz('Cadastro de Revendedores'),
            "codigo"     => "CAD-9060"
        ),
        array(
        	'fabrica_no' => array(193),
            'fabrica'    => array(117),
            'icone'      => $icone["cadastro"],
            'link'       => 'modalidade_cadastro.php',
            'titulo'     => traduz('Modalidades'),
            'descr'      => traduz('Cadastro de Modalidades'),
            "codigo"     => "CAD-9680"
        ),
        array(
            'fabrica'    => array(138,129),
            'icone'      => $icone["cadastro"],
            'link'       => 'revenda_cadastro.php',
            'titulo'     => traduz('Revendas'),
            'descr'      => traduz('Cadastro de Revendedores'),
            "codigo"     => "CAD-9070"
        ),
        array(
            'fabrica'    => 7,
            'icone'      => $icone["consulta"],
            'link'       => 'cliente_consulta.php',
            'titulo'     => traduz('Clientes'),
            'descr'      => traduz('Consulta de Clientes'),
            "codigo"     => "CAD-9080"
        ),
        array(
            'fabrica'    => 7,
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_representante_posto.php',
            'titulo'     => traduz('Representante Posto'),
            'descr'      => traduz('Cadastro de Representantes por Posto'),
            "codigo"     => "CAD-9090"
        ),
        array(
            'fabrica' => 1,
            'icone'      => $icone["cadastro"],
            'link'       => 'upload_representante.php',
            'titulo'     => traduz('Representante'),
            'descr'      => traduz('Upload de Representante'),
            "codigo"     => "CAD-9100"
        ),
        array(
            'fabrica_no' => array_merge(array(7,115,116,117,122,81,114,123,124,126,148), $fabricas_contrato_lite, $arr_fabrica_padrao, ((isset($novaTelaOs)) ? array($login_fabrica) : array())),
            'icone'      => $icone["cadastro"],
            'link'       => 'consumidor_cadastro.php',
            'titulo'     => traduz('Consumidores'),
            'descr'      => traduz('Cadastro de Consumidores'),
            "codigo"     => "CAD-9110"
        ),
        array(
            'fabrica_no' => array_merge(array(122,81,114,124,81,114,123,124,126,148),$fabricas_contrato_lite,$arr_fabrica_padrao, ((isset($novaTelaOs)) ? array($login_fabrica) : array())),
            'icone'      => $icone["cadastro"],
            'link'       => 'fornecedor_cadastro.php',
            'titulo'     => traduz('Fornecedores'),
            'descr'      => traduz('Cadastro de Fornecedores'),
            "codigo"     => "CAD-9120"
        ),
        array(
            'fabrica_no' => array_merge(array(1),$fabricas_contrato_lite),
            'icone'      => $icone["cadastro"],
            'link'       => 'faq_situacao.php',
            'titulo'     => traduz('Perguntas Frequentes'),
            'descr'      => traduz('Cadastro de  perguntas e respostas sobre um determinado produto'),
            "codigo"     => "CAD-9130"
        ),
        array(
            'fabrica'    => array(101),
            'icone'      => $icone["cadastro"],
            'link'       => 'faq_situacao.php',
            'titulo'     => traduz('Perguntas Frequentes'),
            'descr'      => traduz('Cadastro de  perguntas e respostas sobre um determinado produto'),
            "codigo"     => "CAD-9140"
        ),
        array(
            'fabrica'    => 1,
            'icone'      => $icone["email"],
            'link'       => 'comunicado_blackedecker.php',
            'titulo'     => traduz('Comunicados por E-mail'),
            'descr'      => traduz('Envie comunicados por e-mail para os postos'),
            "codigo"     => "CAD-9150"
        ),
        array(
            'fabrica'    => array(3),
            'icone'      => $icone["computador"],
            'link'       => 'distribuidor_posto_relatorio.php',
            'titulo'     => traduz('Distribuidor e seus postos'),
            'descr'      => traduz('Rela��o para confer�ncia da Distribui��o'),
            "codigo"     => "CAD-9160"
        ),
        array(
            'fabrica'    => array(3),
            'admin'      => array(258, 852),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_km.php',
            'titulo'     => traduz('Quilometragem'),
            'descr'      => traduz('Cadastro do valor pago por Quilometragem para Ordens de Servi�os com atendimento em Domicilio.'),
            "codigo"     => "CAD-9170"
        ),
        array(
            'fabrica'    => array(3),
            'fabrica_no' => array(81,114,123,124),
            'admin'      => array(258, 852),
            'icone'      => $icone["computador"],
            'link'       => 'aprova_atendimento_domicilio.php',
            'titulo'     => traduz('Aprovar OS Domicilio (EM TESTE)'),
            'descr'      => traduz('Aprova��o de Ordens de Servi�os que tenham atendimento em domicilio.'),
            "codigo"     => "CAD-9180"
        ),
        array(
            'fabrica'    => array(0),
            'icone'      => $icone["upload"],
            'link'       => 'upload_importacao_serie.php',
            'titulo'     => traduz('Upload de N�mero de S�rie'),
            'descr'      => traduz('Upload de Arquivo de N�mero de S�rie'),
            "codigo"     => "CAD-9190"
        ),
        array(
            'fabrica_no' => array_merge(array(86,115,116,117,122,81,114,123,124,126,142), $fabricas_contrato_lite,$arr_fabrica_padrao),
            'icone'      => $icone["cadastro"],
            'link'       => 'feriado_cadastra.php',
            'titulo'     => traduz('Cadastro de Feriado'),
            'descr'      => traduz('Cadastro de feriados no sistema'),
            "codigo"     => "CAD-9200"
        ),
        array(
            'fabrica_no' => array_merge($fabricas_contrato_lite, $arr_fabrica_padrao),
            'icone'      => $icone["cadastro"],
            'link'       => 'callcenter_pergunta_cadastro.php',
            'titulo'     => traduz('Cadastro de Perguntas do Callcenter'),
            'descr'      => traduz('Para que as frases padr�es do callcenter sejam alteradas'),
            "codigo"     => "CAD-9210"
        ),
         array(
            'fabrica' => array($responsavel_posto),
            'icone'      => $icone["computador"],
            'link'       => 'em_descredenciamento.php',
            'titulo'     => traduz('Postos em Descredenciamento'),
            'descr'      => traduz('Vencimento do prazo dos Postos em Descredenciamento'),
            "codigo"     => "CAD-9220"
        ),
        array(
            'fabrica'    => array(20),
            'icone'      => $icone["cadastro"],
            'link'       => 'escritorio_regional_cadastro.php',
            'titulo'     => traduz('Cadastro de Escrit�rios Regionais'),
            'descr'      => traduz('Faz o cadastramento e manuten��o de escrit�rios regionais.'),
            "codigo"     => "CAD-9230"
        ),
        array(
            'fabrica'    => array(30),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_tipo_pedido_condicao.php',
            'titulo'     => traduz('Cadastro de Tipo de Pedido X Condi��o'),
            'descr'      => traduz('Faz o cadastro de varias Condi��es para um Pedido.'),
            "codigo"     => "CAD-9240"
        ),
        array(
            'fabrica'    => array(20),
            'icone'      => $icone["upload"],
            'link'       => 'upload_importacao.php',
            'titulo'     => traduz('Upload de Arquivos'),
            'descr'      => traduz('Faz o Upload de pe�as, pre�o, produto, lista b�sica do Brasil e Am�rica Latina.'),
            "codigo"     => "CAD-9250"
        ),
        array(
            'fabrica_no' => array_merge($array_fabrica_not_providencia,array(189)),
            'fabrica'    => array_merge(array(1,3,11,30,45,151,169,170,172,183),$array_fabrica_providencia, $array_fabrica_hdclassificacao),
            'icone'      => $icone["computador"],
            'link'       => 'atendente_cadastro.php',
            'titulo'     => $titulo_9260,
            'descr'      => $descricao_9260,
            "codigo"     => "CAD-9260"
        ),
        array(
            'fabrica'    => array(1),
            'icone'      => $icone["computador"],
            'link'       => 'cadastro_atendente_categoria_posto.php',
            'titulo'     => traduz('Atendentes por Categoria/Tipo Posto'),
            'descr'      => traduz('Manuten��o de Atendente Por Categoria/Tipo do Posto Autorizado'),
            "codigo"     => "CAD-9270"
        ),
        array(
            'fabrica'    => array(1),
            'icone'      => $icone["computador"],
            'link'       => 'fale_conosco_cadastro.php',
            'titulo'     => traduz('Fale Conosco Manuten��o'),
            'descr'      => traduz('Manuten��o de Fale Conosco na Tela do Posto.'),
            "codigo"     => "CAD-9280"
        ),

        array(
            'fabrica' => 7,
            'icone'   => $icone["cadastro"],
            'link'    => 'classificacao_os_cadastro.php',
            'titulo'  => traduz('Classifica��o de OS'),
            'descr'   => traduz('Cadastro de Clasifica��o de Ordem de Servi�o.'),
            "codigo"  => "CAD-9290"
        ),
        array(
            'fabrica' => 7,
            'icone'   => $icone["cadastro"],
            'link'    => 'contrato_cadastro.php',
            'titulo'  => traduz('Contrato'),
            'descr'   => traduz('Cadastro de Contrato.'),
            "codigo"  => "CAD-9300"
        ),
        array(
            'fabrica' => 7,
            'icone'   => $icone["cadastro"],
            'link'    => 'grupo_empresa_cadastro.php',
            'titulo'  => traduz('Grupo de Empresa'),
            'descr'   => traduz('Cadastro Grupo de empresa.'),
            "codigo"  => "CAD-9310"
        ),
        array(
            'fabrica' => array(3),
            'icone'   => $icone["cadastro"],
            'link'    => 'dias_intervencao_cadastro.php',
            'titulo'  => traduz('Dias para entrar na interven��o'),
            'descr'   => traduz('Altera��o de quantidade de dias para OS entrar na interven��o.'),
            "codigo"  => "CAD-9320"
        ),
    /*
        array(
            'fabrica' => $fabrica_usa_mascara_serie, // HD 86636 HD 264560
        'fabroca_no' => array(101,151,153),
            'icone'   => $icone["cadastro"],
            'link'    => 'produto_serie_mascara.php',
            'titulo'  => 'Cadastro de M�scara de N�mero de S�rie',
            'descr'   => 'Manuten��o de M�scara de N�mero de S�rie.',
            "codigo"  => "CAD-9330"
    ),*/
        array(
            'fabrica' => array(3,153,157),
            'icone'   => $icone["cadastro"],
            'link'    => 'cadastro_garantia_estendida_new.php',
            'titulo'  => traduz('Cadastro de Garantia Estendida'),
            'descr'   => traduz('Cadastro de Garantia Estendida.'),
            "codigo"  => "CAD-9340"
    ),
        /*array(
            'fabrica' => array(153),
            'icone'   => $icone["cadastro"],
            'link'    => 'cadastro_garantia_estendida.php',
            'titulo'  => 'Cadastro de Garantia Estendida',
            'descr'   => 'Cadastro de Garantia Estendida.',
            "codigo"  => "CAD-9350"
        ),*/
        array(
            'fabrica' => array(153),
            'icone'   => $icone["cadastro"],
            'link'    => 'lista_peca_recall.php',
            'titulo'  => traduz('Cadastro Recall'),
            'descr'   => traduz('Cadastro de Pe�as para Recall.'),
            "codigo"  => "CAD-9360"
        ),
        array(
            'fabrica' => array(91),
            'icone'   => $icone["cadastro"],
            'link'    => 'cadastro_garantia_estendida_new.php',
            'titulo'  => traduz('Cadastro de Produtos Inativados'),
            'descr'   => traduz('Cadastro de Produtos Inativados.'),
            "codigo"  => "CAD-9370"
        ),
        array(
            'fabrica' => array(3),
            'icone'   => $icone["cadastro"],
            'link'    => 'prospeccao_cadastro.php',
            'titulo'  => traduz('Cadastro de Prospec��o de Postos'),
            'descr'   => traduz('Cadastro de Prospec��o de Postos'),
            "codigo"  => "CAD-9380"
        ),
        array(
            'fabrica' => array(50),
            'icone'   => $icone["computador"],
            'link'    => 'posto_familia_cadastro.php',
            'titulo'  => traduz('Posto X Deslocamento'),
            'descr'   => traduz('Autoriza deslocamento para familia de produto.'),
            "codigo"  => "CAD-9390"
        ),
        array(
            'fabrica' => array(43), // HD34210
            'icone'   => $icone["cadastro"],
            'link'    => 'indicadores_cadastro.php',
            'titulo'  => traduz('Cadastro Indicadores Ranking'),
            'descr'   => traduz('Cadastro de notas de corte, peso de cada nota e meta para o ranking dos postos.'),
            "codigo"  => "CAD-9400"
        ),
        array(
            'fabrica' => array(45), // HD34210
            'icone'   => $icone["cadastro"],
            'link'    => 'upload_representante_comercial.php',
            'titulo'  => traduz('Upload de arquivo de representante comercial'),
            'descr'   => traduz('Upload de arquivo de representante comercial.'),
            "codigo"  => "CAD-9410"
        ),
        array(

			'fabrica'   => $array_hd_posto,
			'icone'     => $icone['computador'],
			'link'      => 'hd_posto_tipo_cadastro.php',
			'titulo'    => traduz('Cadastro de Tipos de Solicita��o'),
			'descr'     => traduz('Manuten��o dos tipos de solicita��o (categorias de chamados) que o Posto Autorizado pode abrir.'),
			'codigo'    => 'CAD-9420',
			'fabrica_no'    => array(1,3,42,194)
		),
		array(
			'fabrica'   => $array_hd_posto,
			'icone'     => $icone["computador"],
			'link'      => 'atendente_solicitacao_cadastro.php',
			'titulo'    => traduz('Atendente Helpdesk Posto Autorizado'),
			'descr'     => traduz('Manuten��o de Atendente do Helpdesk do Posto Autorizado.'),
			'codigo'    => 'CAD-9430',
			'fabrica_no' => array(194,198)
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone["computador"],
			'link'      => 'manutencao_hd_chamado_blackedecker.php',
			'titulo'    => traduz('Manuten��o Help-Desk em Lote'),
			'descr'     => traduz('Transfer�ncia de chamados Help-Desk em lote'),
			"codigo" => 'CAD-9440'
		),
		array(
			'fabrica'   => array(1),
			'icone'     => $icone["computador"],
			'link'      => 'posto_uploads_contratos.php',
			'titulo'    => traduz('Posto Autorizado x Uploads de Contratos'),
			'descr'     => traduz('Relat�rio de Posto Autorizado x Uploads de Contratos.'),
			'codigo'    => 'CAD-9450'
		),
		array(
			'fabrica'   => $fabrica_cadastra_origem,
			'icone'     => $icone["cadastro"],
			'link'      => 'cadastro_origem_atendimento.php',
			'titulo'    => ($login_fabrica == 189) ? traduz('Cadastro de Depto. Gerador da RRC') : traduz('Cadastro de Origens'),
			'descr'     => ($login_fabrica == 189) ? traduz('Manuten��o de Depto. Gerador da RRC') : traduz('Manuten��o de origens de atendimentos do callcenter.'),
			'codigo'    => 'CAD-9460'
		),
		array(
			'fabrica'   => $fabrica_cadastra_origem,
			'icone'     => $icone["cadastro"],
			'link'      => 'atendente_callcenter_origem.php',
			'titulo'    => ($login_fabrica == 189) ? traduz('Atendente Callcenter x Depto. Gerador da RRC') : traduz('Atendente Callcenter x Origem'),
			'descr'     => ($login_fabrica == 189) ? traduz('Manuten��o de Atendente de Callcenter x Depto. Gerador da RRC de atendimento.') : traduz('Manuten��o de Atendente de Callcenter x Origem de atendimento.'),
			'codigo'    => 'CAD-9470',
			'fabrica_no'    => array(160)
		),
		array(
			'fabrica'   => array(164),
			'icone'     => $icone["cadastro"],
			'link'      => 'cadastro_destinacao.php',
			'titulo'    => traduz('Cadastro de Destina��o'),
			'descr'     => traduz('Cadastro da op��o de destina��o para o cdastro de OS no Posto Autorizado.'),
			'codigo'    => 'CAD-9480'
		),
		array(
			'fabrica'   => array(7),
			'icone'     => $icone["cadastro"],
			'link'      => 'cidade_cadastro.php',
			'titulo'    => traduz('Cadastro de cidades'),
			'descr'     => traduz('Manuten��o de cidades e horas'),
			'codigo'    => 'CAD-9490'
		 ),
		array(


			'fabrica'   => array(128),
			'icone'     => $icone["upload"],
			'link'      => 'upload_garantia_estendida.php',
			'titulo'    => traduz('Upload de Garantia Estendida'),
			'descr'     => traduz('Upload de arquivo CSV com informa��es de garantia estendida'),
			'codigo'    => 'CAD-9500'
		 ),
		array(
			'fabrica'   => array(141,144,165),
			'icone'     => $icone["cadastro"],
			'link'      => 'cadastro_metas_produtividade.php',
			'titulo'    => traduz('Cadastro de metas para reparo'),
			'descr'     => traduz('Cadastro de metas para reparo de Ordens de Servi�o'),
			'codigo'    => 'CAD-9510'
		 ),
		array(
			'fabrica'   => array(117,138),
			'icone'     => $icone["cadastro"],
			'link'      => 'regiao.php',
			'titulo'    => traduz('Cadastro de Regi�es'),
			'descr'     => traduz('Manuten��o de Regi�es'),
			'codigo'    => 'CAD-9520'
		),
		array(
			'fabrica'   => array(91),
			'icone'     => $icone["cadastro"],
			'link'      => 'integridade_peca_fornecedor_cadastro.php',
			'titulo'    => traduz('Pe�a X Fornecedor'),
			'descr'     => traduz('Cadastro de Integridade de pe�as e fornecedores'),
			'codigo'    => 'CAD-9530'
        ),
         array(
            'fabrica'   => array(148,151,158,171),

            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_cliente.php',
            'titulo'    => traduz('Cadastro de Clientes'),
            'descr'     => traduz('Cadastro de Clientes'),
            'codigo'    => 'CAD-9540'
        ),
        array(
            'fabrica'   => array(158,171),
            'icone'     => $icone["cadastro"],
            'link'      => 'grupo_cliente_cadastro.php',
            'titulo'    => traduz('Cadastro de Grupo de Clientes'),
            'descr'     => traduz('Cadastro de Grupo de Clientes'),
            'codigo'    => 'CAD-9550'
        ),
         array(
            'fabrica'   => array(158),
            'icone'     => $icone["cadastro"],
            'link'      => 'distribuidor_cadastro.php',
            'titulo'    => traduz('Cadastro de Distribuidores'),
            'descr'     => traduz('Cadastro de Distribuidores'),
            'codigo'    => 'CAD-9560'
        ),
         array(
            'fabrica'   => array(158,169,170,183),
            'icone'     => $icone["cadastro"],
            'link'      => 'manutencao_cep_posto.php',
            'titulo'    => traduz('Manuten��o de CEP - Blacklist'),
            'descr'     => traduz('Manuten��o de CEP - Blacklist'),
            'codigo'    => 'CAD-9570'
        ),
        array(
            'fabrica'   => array(169),
            'icone'     => $icone["cadastro"],
            'link'      => 'blacklist_email.php',
            'titulo'    => 'Manuten��o de Email - Blacklist',
            'descr'     => 'Manuten��o de Email - Blacklist',
            'codigo'    => 'CAD-9571'
        ),
        array(
            'fabrica' => array(42,81,169,170,174,186,198),
            'icone'     => $icone["cadastro"],
            'link'      => 'manutencao_email_atendimento.php',
            'titulo'    => traduz('Cadastro de Emails'),
            'descr'     => traduz('Cadastro de emails para leitura e cria��o de atendimentos'),
            'codigo'    => 'CAD-9580'
        ),
        array(
            'fabrica'   => array(169,170,174),
            'icone'     => $icone["cadastro"],
            'link'      => 'supervisor_atendente.php',
            'titulo'    => traduz('Supervisor Callcenter x Atendente Callcenter'),
            'descr'     => traduz('Tela para amarrar o supervisor do callcenter aos atendentes do callcenter'),
            'codigo'    => 'CAD-9590'
        ),
        array(
            'fabrica'   => array(90,169,170, 174,186),
            'icone'     => $icone["cadastro"],
            'link'      => 'frases_callcenter.php',
            'titulo'    => traduz('Frases Callcenter'),
            'descr'     => traduz('Tela para manuten��o de frases do callcenter'),
            'codigo'    => 'CAD-9600'
        ),
         array(
            'fabrica'   => array(151),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_codigo_postagem.php',
            'titulo'    => traduz('Cadastro de Postagem X UF'),
            'descr'     => traduz('Cadastro de Postagem X UF'),
            'codigo'    => 'CAD-9610'
        ),
        array(
            'fabrica' => array(30,35,72,160),
            'icone'   => $icone['cadastro'],
            'link'    => 'cadastro_atendente_posto.php',
            'titulo'  => traduz('Atendente espec�fico por posto'),
            'descr'   => traduz('Associa��o de atendentes por posto autorizado.'),
            'codigo'  => 'CAD-9620'
        ),
        array(
            'fabrica'    => array(165),
            'icone'      => $icone["cadastro"],
            'link'       => 'cadastro_tecnico.php',
            'titulo'     => traduz('Cadastro de Instaladores'),
            'descr'      => traduz('Cadastro de Instaladores para indica��o do callcenter'),
            "codigo"     => "CAD-9630"
        ),
        array(
            'fabrica' => array(74),
            'icone'   => $icone['cadastro'],
            'link'    => 'cadastro_bonificacao_posto.php',
            'titulo'  => traduz('Cadastro de Bonifica��o por Posto Autorizado'),
            'descr'   => traduz('Cadastro de bonifica��o de m�o de obra por posto autorizado.'),
            'codigo'  => 'CAD-9640'
        ),
        array(
            'fabrica' => array(158),
            'icone'   => $icone['cadastro'],
            'link'    => 'cadastro_posto_preco_fixo.php',
            'titulo'  => traduz('Cadastro de Posto X Pre�o Fixo por Unidade de Neg�cio'),
    	    'descr'   => traduz('Cadastro de Posto X Pre�o Fixo de Extrato Fora de Garantia por Unidade de Neg�cio'),
    	    'codigo'  => 'CAD-9650'
        ),
        array(
            'fabrica' => array(169,170),
            'icone'   => $icone['cadastro'],
            'link'    => 'posto_cadastro_filial.php',
            'titulo'  => traduz('Cadastro de Filiais por Matriz'),
            'descr'   => traduz('Cadastro de filiais para os postos que est�o marcados como matriz'),
            'codigo'  => 'CAD-9660'
        ),
        array(
            'fabrica' => array(175),
            'icone'   => $icone['cadastro'],
            'link'    => 'grupo_ferramenta_cadastro.php',
            'titulo'  => traduz('Grupo de Ferramentas'),
            'descr'   => traduz('Cadastro de Grupo de Ferramentas'),
            'codigo'  => 'CAD-9670'
        ),
        /*HD - 4258409*/
        array(
            'fabrica' => array(85),
            'icone'   => $icone['cadastro'],
            'link'    => 'cadastro_tecnico_esporadico.php',
            'titulo'  => traduz('Cadastro de Tecnicos Espor�dicos'),
            'descr'   => traduz('Cadastro e visualiza��o dos tecnicos esporadicos'),
            'codigo'  => 'CAD-9670'
        ),array(
        	'fabrica_no' => array(175,193),
            'fabrica' => array($responsavel_posto),
            'icone'   => $icone['cadastro'],
            'link'    => 'aprova_credenciamento_posto.php',
            'titulo'  => traduz('Aprova��o de Credenciamento'),
            'descr'   => traduz('Aprova��o de Credenciamento de Postos Autorizados.'),
            'codigo'  => 'CAD-9680'
        ),array(
            'fabrica' => array(177),
            'icone'   => $icone['cadastro'],
            'link'    => 'cadastro_motivo_recusa_conclusao.php',
            'titulo'  => traduz('Motivo de Recusa x Conclus�o'),
            'descr'   => traduz('Cadastro de Motivo de Recusa x Conclus�o.'),
            'codigo'  => 'CAD-9690'
        ),
        array(
            'fabrica' => $fabricaLaudoTecnicoOs,
            'icone'   => $icone['cadastro'],
            'link'    => 'os_laudo_tecnico_cadastro.php',
            'titulo'  => traduz('Cadastro de Laudo T�cnico'),
            'descr'   => traduz('Cadastro de Laudo T�cnico para gera��o do Certificado de Calibra��o'),
            'codigo'  => 'CAD-9700'
        ),
        array(
            'fabrica' => array(35),
            'icone'   => $icone['cadastro'],
            'link'    => 'cadastro_justificativa.php',
            'titulo'  => traduz('Cadastro de Justificativa'),
            'descr'   => traduz('Cadastro e listagem das justificativas de visitas dos agentes de Postos Autorizados'),
            'codigo'  => 'CAD-9710'
        ),
        array(
            'fabrica' => array(186),
            'icone'   => $icone['cadastro'],
            'link'    => 'postos_em_credenciamento.php',
            'titulo'  => 'Postos em Credenciamento',
            'descr'   => 'Listagem de Postos em credenciamento',
            'codigo'  => 'CAD-9720'
        ),
	array(
            'fabrica' => array(183),
            'icone'   => $icone['cadastro'],
            'link'    => 'upload_os_calculo_km.php',
            'titulo'  => 'Carga de OS�s para Calculo de KM',
            'descr'   => 'Carga de OS�s para Calculo de KM',
            'codigo'  => 'CAD-9730'
        ),
        array(
            'fabrica' => $usaFluxoAtendimento,
            'icone'   => $icone['cadastro'],
            'link'    => 'fluxo_atendimento.php',
            'titulo'  => 'Fluxo de Atendimento',
            'descr'   => 'Relacionamento de Fluxo de Atendimento',
            'codigo'  => 'CAD-9740'
        ),
        array(
            'fabrica' => [189],
            'icone'   => $icone['cadastro'],
            'link'    => 'cadastro_mercado_gerencia.php',
            'titulo'  => 'Cadastro de Mercado Gerencia',
            'descr'   => 'Cadastro de Mercado Gerencia de Atendimento',
            'codigo'  => 'CAD-9750'
        ),
        array(
            'fabrica' => [148,195],
            'icone'   => $icone['cadastro'],
            'link'    => 'categoria_posto_cadastro.php',
            'titulo'  => ($login_fabrica == 195) ? 'Cadastro de Crit�rios do Ranking de Postos' : 'Categorias de Posto',
            'descr'   => ($login_fabrica == 195) ? 'Cadastro de Crit�rios do Ranking de Postos' : 'Cadastro de categorias de postos autorizados',
            'codigo'  => 'CAD-9760'
        ),
        array(
            'fabrica' => [190],
            'icone'   => $icone['cadastro'],
            'link'    => 'cadastro_representante_admin.php',
            'titulo'  => 'Cadastro de Representantes',
            'descr'   => 'Cadastro de Representantes',
            'codigo'  => 'CAD-9770'
        ),
        array(
            'fabrica' => [183],
            'icone'   => $icone['cadastro'],
            'link'    => 'supervisor_qrcode_google.php',
            'titulo'  => 'Cadastra token Google Autenticator',
            'descr'   => 'Cadastra token Google Autenticator',
            'codigo'  => 'CAD-9780'
        ),

	array(
            'fabrica' => array(151),
            'icone'   => $icone['cadastro'],
            'link'    => 'cadastro_voucher.php',
            'titulo'  => traduz('Cadastro de Vouchers'),
            'descr'   => traduz('Cadastro e importa��o de Vouchers.'),
            'codigo'  => 'CAD-9790'
        ),
        array(
            'fabrica' => [169,170],
            'icone'   => $icone['cadastro'],
            'link'    => 'cadastro_tipo_protocolo.php',
            'titulo'  => 'Cadastra Tipo de Protocolo',
            'descr'   => 'Cadastra tipo de protocolo para o atendimento Call-Center',
	    'codigo'  => 'CAD-9800'
	),
        'link' => 'linha_de_separa��o'
	),

    // SE��O de PESQUISA DE SATISFA��O - Apenas Esmaltec
    'secao4' => array(
        'secao'    => array(
            'link'     => '#',
            'titulo'   => traduz('PESQUISA DE SATISFA��O'),
            'fabrica'  => array(1,10,30,35,52,85,88,94,129,145,151,152,161,169,170,180,181,182)
        ),
        array(
            'icone'    => $icone["cadastro"],
            'link'     => 'cadastro_pergunta.php',
            'titulo'   => traduz('Cadastro de Pergunta'),
            'descr'    => traduz('Cadastro de Perguntas para a Pesquisa de Satisfa��o.'),
            'fabrica'  => array(30),
            "codigo"   => "CAD-10000"
        ),
        array(
            'icone'    => $icone["cadastro"],
            'link'     => 'tipo_pergunta_cadastro.php',
            'titulo'   => traduz('Cadastro de Tipo de Pergunta/Requisito'),
            'descr'    => traduz('Cadastro de Tipo de Pergunta para a pesquisa de satisfa��o/Auditoria.'),
            'fabrica'  => array(1,35,52,85,88,94,129,138,145,151,152,161,169,170,180,181,182),
            "codigo"   => "CAD-10010"
        ),
        array(
            'icone'    => $icone["cadastro"],
            'link'     => 'cadastro_tipo_resposta.php',
            'titulo'   => traduz('Cadastro de Tipo de Respostas'),
            'descr'    => traduz('Cadastro de Tipos de Respostas para as perguntas da Pesquisa de Satisfa��o.'),
            "codigo"   => "CAD-10020"
        ),
        array(
            'icone'    => $icone["cadastro"],
            'link'     => 'pergunta_cadastro_new.php',
            'titulo'   => traduz('Cadastro de Pergunta'),
            'descr'    => traduz('Cadastro de Perguntas para a Pesquisa de Satisfa��o.'),
            'fabrica'  => array(1,35,52,85,88,94,129,138,145,151,152,161,169,170,180,181,182),
            "codigo"   => "CAD-10030"
        ),
        array(
            'disabled' => (!$helper->login->hasPermission('inspetor')),
            'icone'    => $icone["cadastro"],
            'link'     => 'cadastro_auditoria.php',
            'titulo'   => traduz('Cadastro de Auditoria'),
            'descr'    => traduz('Cadastro de Auditoria do posto autorizado.'),
            'fabrica'  => array(52),
            "codigo"   => "CAD-10040"
        ),
        array(
            'icone'    => $icone["cadastro"],
            'link'     => 'cadastro_pesquisa.php',
            'titulo'   => traduz('Cadastro de Pesquisa'),
            'descr'    => traduz('Cadastro de Pesquisa de Satisfa��o.'),
            'fabrica'  => array(30,52),
            "codigo"   => "CAD-10050"
        ),
        array(
            'icone'    => $icone["cadastro"],
            'link'     => 'cadastro_valor_minimo_lgr.php',
            'titulo'   => traduz('Cadastro de Valor M�nimo LGR'),
            'descr'    => traduz('Cadastro de Valor M�nimo de extrato para entrar em LGR.'),
            'fabrica'  => array(94),
            "codigo"   => "CAD-1350"
        ),
        'link' => 'linha_de_separa��o'
    ),


	// SE��O de LOJA VIRTUAL - Apenas Brit�nia, Cadence, Gelopar e Telecontrol
	//retirar fabrica 3 hd 3394908
    'secao5' => array(
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('CONSULTA LOJA VIRTUAL'),
            'fabrica'    => array( 10, 35,85)
        ),
        array(
            'fabrica_no' => array(35),
            'icone'      => $icone["computador"],
            'link'       => 'loja_completa.php',
            'titulo'     => traduz('Listas de Produtos'),
            'descr'      => traduz('Listas dos Produtos Promo��o Loja Virtual.'),
            "codigo"     => "CAD-11000"
        ),
        array(
            'icone'      => $icone["computador"],
            'link'       => 'manutencao_valormin.php',
            'titulo'     => traduz('Manuten��o'),
            'descr'      => traduz('Manuten��o do Valor Minimo de Compra.'),
            "codigo"     => "CAD-11010"
        ),
        array(
            'fabrica_no' => array(35,85),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_lojas_virtuais.php',
            'titulo'    => traduz('Loja Virtual'),
            'descr'     => traduz('Habilita��o e bloqueio de Lojas Virtuais.'),
            "codigo"    => "CAD-11020"
        ),
        'link' => 'linha_de_separa��o'
    ),

    // SE��O de AM�RICA LATINA - Apenas Bosch
    'secao6' => array(
        'secao'   => array(
            'link'    => '#',
            'titulo'  => traduz('INFORMA��ES CADASTRAIS DA AM�RICA LATINA'),
            'fabrica' => array(20),
        ),
        array(
            'icone'   => $icone["computador"],
            'link'    => 'peca_informacoes_pais.php',
            'titulo'  => traduz('Tabela de Pre�os Am�rica Latina'),
            'descr'   => traduz('Todas tabelas de pre�o da Am�rica Latina.'),
            "codigo"  => "CAD-12000"
        ),
        array(
            'icone'   => $icone["computador"],
            'link'    => 'produto_informacoes_pais.php',
            'titulo'  => traduz('Produtos por Pa�s'),
            'descr'   => traduz('Todos os produtos cadastrados pelos pa�ses da Am�rica Latina.'),
            "codigo"  => "CAD-12010"
        ),
        array(
            'icone'   => $icone["computador"],
            'link'    => 'informacoes_pais.php',
            'titulo'  => traduz('Dados Pa�ses da Am�rica Latina'),
            'descr'   => traduz('Dados de convers�o de moeda e desconto de cada pa�s <br>usado na integra��o com a Alemanha.'),
            "codigo"  => "CAD-12020"
        ),
        array(
            'icone'   => $icone["computador"],
            'link'    => 'categoria_produto.php',
            'titulo'  => traduz('Categoria de M�o-de-Obra'),
            'descr'   => traduz('Valores de m�o-de-obra divididos por categorias e pa�ses.'),
            "codigo"  => "CAD-12030"
        ),
        'link' => 'linha_de_separa��o'
    ),

	// Menu Cadastro Postos para a JACTO, evita colocar regra de exclus�o em quase tudo
	'secao07' => array(
		'secao' => array(
			'link'     => '#',
			'titulo'    => traduz('MANUTEN��O DE F�BRICAS'),
			'fabrica'   => array(10) // Habilitado para a JACTO
		),
		array(
			'icone'     => $icone["cadastro"],
			'link'      => 'cadastro_parametros_adicionais.php',
			'titulo'    => traduz('Par�metros Adicionais'),
			'descr'     => traduz('Cadastro de par�metros adicionais por f�brica'),
			"codigo" => "CAD-13000"
		),
	),
    'secao5' => array(
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('MANUTEN��O DA LOJA VIRTUAL'),
            'fabrica'    => $loja_habilitada
        ),
        array(
            'fabrica'   => $loja_habilitada,
            'icone'     => $icone["computador"],
            'link'      => 'consulta_pedido_b2b.php',
            'titulo'    => traduz('Consultar Pedido B2B'),
            'descr'     => traduz('Consulta pedidos efetuados por postos autorizados pela loja B2B.'),
            "codigo"    => "CAD-16020"
        ),
        array(
            'fabrica'   => $loja_habilitada,
            'icone'     => $icone["cadastro"],
            'link'      => 'loja_produto.php',
            'titulo'    => traduz('Consulta de Produtos'),
            'descr'     => traduz('Consulta de produtos da loja.'),
            "codigo"    => "CAD-15000"
        ),
        array(
            'fabrica'   => [42],
            'icone'     => $icone["cadastro"],
            'link'      => 'loja_fornecedor.php',
            'titulo'    => traduz('Fornecedores'),
            'descr'     => traduz('Cadastros e manuten��o de fornecedores da loja.'),
            "codigo"    => "CAD-16030"
       ),
       array(
       		'fabrica'   => $loja_habilitada,
            'icone'     => $icone["cadastro"],
            'link'      => 'loja_cadastra_produto.php',
            'titulo'    => traduz('Cadastro de Produtos'),
            'descr'     => traduz('Cadastros de produtos da loja.'),
            "codigo"    => "CAD-16010"
        ),
        array(
            'fabrica' => $loja_habilitada,
            'icone'   => $icone["computador"],
            'link'    => 'loja_categoria_produto.php',
            'titulo'  => traduz('Categoria de Produtos'),
            'descr'   => traduz('Manuten��o de categorias de produtos da loja.'),
            "codigo"  => "CAD-15010"
        ),
        array(
            'fabrica'   => $loja_habilitada,
            'icone'     => $icone["cadastro"],
            'link'      => 'loja_avise_me.php',
            'titulo'    => traduz('Avise - me'),
            'descr'     => traduz('Listagem de todas as solicita��es de avise-me na loja.'),
            "codigo"    => "CAD-15020"
        ),/*
        array(
            'fabrica'   => $loja_habilitada,
            'icone'      => $icone["cadastro"],
            'link'       => 'loja_cupom_desconto.php',
            'titulo'     => 'Cupom de Desconto',
            'descr'      => 'Cadastros e gerenciamento de cupom de desconto.',
            "codigo"     => "CAD-15030"
        ),*/
        array(
            'fabrica'   => $loja_habilitada,
            'icone'      => $icone["cadastro"],
            'link'       => 'loja_banner.php',
            'titulo'     => traduz('Banners'),
            'descr'      => traduz('Cadastros e gerenciamento de banners.'),
            "codigo"     => "CAD-15040"
        ),
        array(
            'fabrica'   => array(10),
            'icone'     => $icone["computador"],
            'link'      => 'loja_configuracao_forma_pagamento.php',
            'titulo'    => traduz('Formas de Pagamento'),
            'descr'     => traduz('Configura��o forma de pagamento'),
            "codigo"    => "CAD-15050"
        ),
        array(
            'fabrica'   => array(10),
            'icone'     => $icone["computador"],
            'link'      => 'loja_configuracao_forma_envio.php',
            'titulo'    => traduz('Formas de Envio'),
            'descr'     => traduz('Configura��o formas de envio'),
            "codigo"    => "CAD-15060"
        ),
        array(
            'fabrica'   => $loja_habilitada,
            'icone'     => $icone["computador"],
            'link'      => 'loja_b2b_kit_peca.php',
            'titulo'    => traduz('Kit Pe�as'),
            'descr'     => traduz('Cadastro de Kit de Pe�as.'),
            "codigo"    => "CAD-16020"
        ),
        array(
            'fabrica'   => $loja_habilitada,
            'icone'     => $icone["computador"],
            'link'      => 'loja.php',
            'titulo'    => traduz('Dados gerais'),
            'descr'     => traduz('Configura��es e layout da loja.'),
            "codigo"    => "CAD-15070"
        ),
        array(
            'fabrica'   => $loja_habilitada,
            'icone'     => $icone["computador"],
            'link'      => 'loja_tabela_preco.php',
            'titulo'    => traduz('Tabela de Pre�o'),
            'descr'     => traduz('Importa��o de tabela de pre�o com impostos e estoque.'),
            "codigo"    => "CAD-15080"
        ),array(
            'fabrica'   => array(10),
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_lojas_virtuais.php',
            'titulo'    => traduz('Loja Virtual'),
            'descr'     => traduz('Habilita��o e bloqueio de Lojas Virtuais.'),
            "codigo"    => "CAD-15090"
        ),array(
        	'fabrica_no' => array(198),
            'fabrica'   => $loja_habilitada,
            'icone'     => $icone["cadastro"],
            'link'      => 'alteracoes_em_massa_loja.php',
            'titulo'    => traduz('Altera��es em Massa'),
            'descr'     => traduz('Altera��o em massa de categorias, produtos etc...'),
            "codigo"    => "CAD-16000"
        ),array(
        	'fabrica_no' => array(198),
            'fabrica'   => $loja_habilitada,
            'icone'     => $icone["cadastro"],
            'link'      => 'loja_movimenta_estoque.php',
            'titulo'    => traduz('Carga de Estoque'),
            'descr'     => traduz('Carga de estoque para loja b2b'),
            "codigo"    => "CAD-16010"
        ),
        'link' => 'linha_de_separa��o'
    ),
    'secaoPesquisaSatisfacao' => array(
        'secao' => array(
            'link'       => '#',
            'titulo'     => traduz('MANUTEN��O DA PESQUISA DE SATISFA��O'),
            'fabrica'    => $fabricaPesquisaSatisfacao
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'pesquisa_satisfacao_cadastro.php',
            'titulo'    => traduz('Cadastro de Pesquisa de Satisfa��o'),
            'descr'     => traduz('Cadastrar uma nova pesquisa de satisfa��o e relat�rio de pesquisas j� cadastradas.'),
            "codigo"    => "CAD-16000"
        ),
        array(
            'icone'     => $icone["relatorio"],
            'link'      => 'pesquisa_satisfacao_relatorio.php',
            'titulo'    => traduz('Relat�rio da Pesquisa de Satisfa��o'),
            'descr'     => traduz('Relat�rio das pesquisas de satisfa��es disparadas.'),
            "codigo"    => "CAD-16010"
        ),
	'link' => 'linha_de_separa��o'
    ),
	(!$atendimentoML && !$atendimentoFacebook && !$atendimentoIG) ? array() :
	'secao6' => array( //integracoes
		'secao' => array(
			'link'	    => '#',
			'titulo'    => traduz('INTEGRA��ES'),
			'fabrica'   => array($login_fabrica)
		),array(
			'fabrica'   => (!$atendimentoML) ? array() : array($login_fabrica),
			'icone'     => $icone['computador'],
			'link'      => 'autentica_ml.php',
			'titulo'    => traduz('Cadastro do Mercado Livre'),
			'descr'     => traduz('Monitoramento de quest�es e conversas'),
			'codigo'    => 'CAD-17000'
		),array(
			'fabrica'	=> (!$atendimentoML) ? array() : array($login_fabrica),
			'icone'		=> $icone['cadastro'],
			'link'		=> 'relaciona_produtos_ml.php',
			'titulo'	=> traduz('Relacionamento de Pe�as com Mercado Livre'),
			'descr'		=> traduz('Relacionamento de pe�as do sistema com produtos do Mercado Livre'),
			'codigo'	=> 'CAD-17010'
		),array(
			'fabrica' 	=> (!$atendimentoFacebook) ? array() : array($login_fabrica),
			'icone' 	=> $icone['computador'],
			'link' 		=> 'autentica_facebook.php',
			'titulo' 	=> traduz('Cadastro de P�gina do Facebook'),
			'descr' 	=> traduz('Monitoramento do Chat de p�gina do Facebook'),
			'codigo' 	=> 'CAD-17020'
		),array(
			'fabrica'	=> (!$atendimentoIG) ? array() : array($login_fabrica),
			'icone'		=> $icone['computador'],
			'link'		=> 'autentica_instagram.php',
			'titulo'	=> 'Cadastro de Instagram Business Account',
			'descr'		=> 'Monitoramento de men��es e coment�rios do Instagram',
			'codigo'	=> 'CAD-17030'
		),
		'link' => 'linha_de_separa��o'
	),
    'secaoModuloJuridico' => array(
        'secao' => array(
            'link'       => '#',
            'titulo'     => 'CADASTROS REFERENTES AO M�DULO JUR�DICO',
            'fabrica'    => [11,24,42,81,172,183]
        ),
	array(
            'fabrica_no' => [24,81],
            'icone'      => $icone["cadastro"],
            'link'       => 'tipo_documento.php',
            'titulo'     => 'Tipo de Documento',
            'descr'      => 'Cadastro de Tipo de Documento',
            "codigo"     => "CAD-18000"
        ),
        array(
            'fabrica_no' => [24,81],
            'icone'      => $icone["cadastro"],
            'link'       => 'pedido_cliente.php',
            'titulo'     => 'Pedido do Cliente',
            'descr'      => 'Cadastro de Pedido do Cliente',
            "codigo"     => "CAD-18010"
        ),
        array(
	    'fabrica_no' => [24,81],
            'icone'      => $icone["cadastro"],
            'link'       => 'status_processo.php',
            'titulo'     => 'Status do Processo',
            'descr'      => 'Cadastro de Status do Processo',
            "codigo"     => "CAD-18020"
        ),
        array(
	    'fabrica_no' => [24,81],
            'icone'      => $icone["cadastro"],
            'link'       => 'fase_processual.php',
            'titulo'     => 'Fase Processual',
            'descr'      => 'Cadastro de Fases Processuais',
            "codigo"     => "CAD-18030"
        ),
        array(
	    'fabrica_no' => [24,81],
            'icone'      => $icone["cadastro"],
            'link'       => 'proposta_acordo.php',
            'titulo'     => 'Proposta de Acordo',
            'descr'      => 'Cadastro de Proposta de Acordo',
            "codigo"     => "CAD-18040"
        ),
        array(
            'icone'     => $icone["cadastro"],
            'link'      => 'cadastro_motivo_principal.php',
            'titulo'    => 'Motivo Principal',
            'descr'     => 'Cadastro dos Motivos Principais de Processos',
            "codigo"    => "CAD-18050"
        ),
    )
);

