<?php
/************************************************************
 * Restrição de acesso à partes do sistema pelo Login Único  *
 * Arrays e funções a serem usadas para fazer a restrição	*
 * de acesso nas telas do ASSIST quando o usuário entra pelo *
 * Login Único.                                              *
 *                                                           *
 * Cada array contém o nome das telas que são proibidas, se  *
 * for criada uma nova tela à ser restrita, tem que adicionar*
 * no array que corresponda.                                 *
 ************************************************************/

$LUres = array (
    'abre_os' => array(
        "blackdecker_cad_os_solicitacao",
        "blackdecker_new_os_cadastro",
        "cadastro_os",
        "consumidor_cadastro",
        "os_cadastro",
        "os_compressor_cadastro",
        "os_consumidor_consulta",
        "os_cortesia_cadastro",
        "os_defeito",
        "os_manutencao",
        "os_manutencao_explodida",
        "os_revenda",
        "os_revenda_alterar",
        "os_revenda_ant",
        "os_revenda_ant2",
        "os_revenda_blackedecker",
        "os_revenda_blackedecker_new",
        "os_revenda_explodida",
        "os_revenda_explodida_blackedecker",
        "os_revenda_latina",
        "os_revenda_new","os_revenda-new",
        "os_risco",
        "revenda_cadastro",
        "revenda_cadastro_Britania",
        "revenda_cadastro_Britania_new",
        "revenda_cadastro_new",
        "sedex_cadastro_complemento",
        "sedex_cadastro_complemento-bkp",
        "sedex_cadastro_complemento-new",
        "tabela_os_upload_xls"
    ),
    'item_os' => array(
        "os_item",
        "os_item_Mondial",
        "os_item_britania",
        "os_item_compressor",
        "os_item_dynacom",
        "os_item_meteor",
        "os_item_new",
        "os_item_new_igor",
        "os_item_new_lorenzetti",
        "os_item_new_mondial",
        "os_revenda_troca",
        "os_troca",
    ),
    'fecha_os' => array(
        "os_revenda_fechamento",
        "os_revenda_fechamento_ant",
        "os_fechamento_posto_intelbras",
        "os_fechamento",
    ),
    'compra_peca' => array(
        "loja_carrinho",
        "loja_completa",
        "loja_detalhe",
        "loja_finaliza",
        "loja_inicial",
        "loja_liquidacao",
        "lv_carrinho",
        "lv_completa",
        "lv_detalhe",
        "lv_pedido",
        "cadastro_pedido",
        "pedido_blackedecker_cadastro",
        "pedido_blackedecker_cadastro2",
        "pedido_blackedecker_cadastro_acessorio",
        "pedido_blackedecker_cadastro_garantia",
        "pedido_blackedecker_cadastro_garantia_ant",
        "pedido_blackedecker_cadastro_garantia_tk",
        "pedido_blackedecker_upload",
        "pedido_cadastro","pedido_cadastro_",
        "pedido_cadastro_novo",
        "pedido_post",
        "pedido_posto_adicional_cadastro",
        "pedido_posto_cadastro",
        "pedido_cadastro_normal",
        "pedido_posto_cadastro_adicional",
        "pedido_posto_faturamento",
	"pedido_upload",
	"pedido_makita_cadastro"
        /* A pedido de alguns postos, poder acessar a tabela de preços, mas não poder fazer pedidos
        "tabela_precos",
        "tabela_precos_blackedecker_consulta",
        "tabela_precos_intelbras",
        "tabela_precos_pecas",
        "tabela_precos_senha",
        "tabela_precos_senha_preco",
        "tabela_precos_tectoy",
        "tabela_precos_xls"
         */
    ),
    'extrato' => array(
        "extrato_posto_lgr_itens",
        "nf_entrada",
        "nf_entrada_item",
        "os_extrato",
        "os_extrato_blackedecker",
        "os_extrato_detalhe",
        "os_extrato_devolucao_suggar",
        "os_extrato_notafiscal_blackedecker",
        "os_extrato_novo_lgr",
        "os_extrato_pecas_latina",
        "os_extrato_pecas_retornaveis",
        "os_extrato_pecas_retornaveis_dynacom",
        "os_extrato_pecas_retornaveis_suggar",
        "os_extrato_pecas_retornaveis_suggar_lgr",
        "os_extrato_pecas_retornaveis_tectoy",
        "os_extrato_print_blackedecker",
        "os_extrato_senha_financeiro",
        "os_faturamento_lote_filizola",
        "os_filizola_faturamento",
        "posicao_financeira",
        "posicao_financeira_telecontrol",
    ),
);

$area_restrita = false;

if ($login_unico_master != 't') {
    $prog = explode(".",basename($_SERVER['PHP_SELF']));
    
    foreach ($LUres as $permissao => $telas) {
	    $area_restrita = (${"login_unico_$permissao"} == 'f' and in_array($prog[0], $telas));

	    if($area_restrita) break;
    }
    if ($login_unico_distrib_total == 't' and strpos($_SERVER['PHP_SELF'], 'distrib/'))
        $area_restrita = false;
}

if ($area_restrita) {
    $desabilita_tela  = traduz(array(
        'prezado.%.voce.nao.tem.acesso.a.esta.area.do.sistema.de.pos.venda',
        'consulte.seu.administrador.do.sistema', ' obrigado',
));

    $desabilita_tela = str_replace('%',$login_unico_nome,$desabilita_tela);
}
