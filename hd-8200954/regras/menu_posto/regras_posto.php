<?php
/**
 * Regras que determinam diversos aspectos do posto ou login único logado,
 * relevantes para a navbar, cabeçalho ou menus:
 * - mostrar ítens da barra de navegação, submenus
 * - acesso a determinadas áreas do sistema
 */

if (!function_exists('isFabrica')) {
    
	include_once APP_DIR . 'funcoes.php';
    // function isFabrica() {
    //     global $login_fabrica;
    //     return in_array($login_fabrica,func_get_args());
    // }
}

if (!function_exists('isPosto')) {
    function isPosto() {
        global $login_posto;
        return in_array($login_posto,func_get_args());
    }
}

//Verifica se tem loja criada e ativa, exibe o link da mesma
$sqlLojaVirtual = "SELECT fabrica FROM tbl_loja_b2b WHERE ativo = 't'";
$resLojaVirtual = pg_query($con, $sqlLojaVirtual);

if (pg_num_rows($resLojaVirtual) > 0) {
    foreach (pg_fetch_all($resLojaVirtual) as $kLoja => $vLoja) {
        $loja_habilitada[] = $vLoja["fabrica"];
    }
    if($login_fabrica == 15 AND $loja_b2b != "t"){
            $loja_habilitada = array();
    }
} else {    
    $loja_habilitada = array();

}

if ($login_fabrica == 151) {

    $questionarios_respondido = array();    
    $questionarios_nao_respondido = array();    
    $xsqlQuestionario = "SELECT opiniao_posto, ativo, cabecalho, validade
                          FROM tbl_opiniao_posto 
                         WHERE fabrica = $login_fabrica 
                           AND ativo IS TRUE;";
    $xresQuestionario  = pg_query($con, $xsqlQuestionario);
    $xquestionarios    = pg_fetch_all($xresQuestionario);

    foreach ($xquestionarios as $key => $row) {

        if (!empty($row['validade']) && ($row['validade'] < date("Y-m-d"))) {
            continue;
        }

        $sqlRespondido = "SELECT DISTINCT tbl_opiniao_posto_resposta.posto
                            FROM tbl_opiniao_posto_resposta
                            JOIN tbl_opiniao_posto_pergunta USING(opiniao_posto_pergunta)
                            JOIN tbl_opiniao_posto USING(opiniao_posto)
                           WHERE tbl_opiniao_posto_pergunta.opiniao_posto = ".$row['opiniao_posto']."
                             AND tbl_opiniao_posto.fabrica = $login_fabrica 
                             AND tbl_opiniao_posto_resposta.posto = $login_posto;";
        $resRespondido   = pg_query($con, $sqlRespondido);
        if (pg_num_rows($resRespondido) > 0) {
            $questionarios_respondido[] = 1;
        } else {
            $ativo = $row['ativo'];
            if ($ativo == 't') {
                $questionarios_nao_respondido[] = 1;
            }
        }

    }
    $temAlert = array_sum($questionarios_nao_respondido);
}

if (in_array($login_fabrica, [1])) {
    unset($posto_atende_marca);
    unset($posto_atende_familia);
    unset($posto_atende_linha);

    $sqlInfoMarca = " SELECT  DISTINCT tbl_marca.marca AS atende_marca
                        FROM tbl_posto_linha 
                        JOIN tbl_produto ON tbl_produto.linha = tbl_posto_linha.linha
                        JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                        JOIN tbl_posto_fabrica ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
                        WHERE tbl_posto_linha.posto = {$login_posto}
                        AND tbl_posto_linha.ativo IS TRUE
                        AND tbl_produto.fabrica_i = {$login_fabrica}";
    $res_sqlInfoMarca = pg_query($con, $sqlInfoMarca);
    if (pg_num_rows($res_sqlInfoMarca) > 0) {                
        for ($s=0; $s < pg_num_rows($res_sqlInfoMarca); $s++) { 
            $posto_atende_marca[] = pg_fetch_result($res_sqlInfoMarca, $s, 'atende_marca');
        }
    }
    $sqlInfoLinha = " SELECT  DISTINCT tbl_posto_linha.linha AS atende_linha
                                FROM tbl_posto_linha 
                                JOIN tbl_produto ON tbl_produto.linha = tbl_posto_linha.linha
                                JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                JOIN tbl_posto_fabrica ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
                                WHERE tbl_posto_linha.posto = {$login_posto}
                                AND tbl_posto_linha.ativo IS TRUE
                                AND tbl_produto.fabrica_i = {$login_fabrica} "; 
    $res_sqlInfoLinha = pg_query($con, $sqlInfoLinha);
    if (pg_num_rows($res_sqlInfoLinha) > 0) {
        for ($s=0; $s < pg_num_rows($res_sqlInfoLinha); $s++) { 
            $posto_atende_linha[] = pg_fetch_result($res_sqlInfoLinha, $s, 'atende_linha');
        }
    }

    $sqlInfoFamilia = "SELECT  DISTINCT tbl_produto.familia AS atende_familia
                                FROM tbl_posto_linha 
                                JOIN tbl_produto ON tbl_produto.linha = tbl_posto_linha.linha
                                JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                                JOIN tbl_posto_fabrica ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
                                WHERE tbl_posto_linha.posto = {$login_posto}
                                AND tbl_posto_linha.ativo IS TRUE
                                AND tbl_produto.fabrica_i = {$login_fabrica}  "; 
    $res_sqlInfoFamilia = pg_query($con, $sqlInfoFamilia);
    if (pg_num_rows($res_sqlInfoFamilia) > 0) {
        for ($s=0; $s < pg_num_rows($res_sqlInfoFamilia); $s++) { 
            $posto_atende_familia[] = pg_fetch_result($res_sqlInfoFamilia, $s, 'atende_familia');
        }
    }

    $sqlTipoPostoCategoria = "SELECT tipo_posto, categoria FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
    $resTipoPostoCategoria = pg_query($con, $sqlTipoPostoCategoria);
    $posto_tipo_posto = pg_fetch_result($resTipoPostoCategoria, 0, 'tipo_posto');
    $posto_categoria_posto = strtolower(pg_fetch_result($resTipoPostoCategoria, 0, 'categoria')); 
        
    if (isset($posto_atende_marca)) {
        $consulta_marca_arr = [];
        foreach ($posto_atende_marca as $pam) {
            $consulta_marca_arr[] = "(tbl_treinamento.parametros_adicionais->'marca' ? '{$pam}')";
        }
        $consulta_marca = ' AND ((tbl_treinamento.parametros_adicionais->\'marca\' IS NULL) OR (' . implode(' OR ', $consulta_marca_arr) . '))';
    } else {
        $consulta_marca = ' AND tbl_treinamento.parametros_adicionais->\'marca\' IS NULL ';
    }

    if (isset($posto_atende_linha)) {
        $consulta_linha_arr = [];
        foreach ($posto_atende_linha as $pal) {
            $consulta_linha_arr[] = "(tbl_treinamento.parametros_adicionais->'linha' ? '{$pal}')";
        }
        $consulta_linha = ' AND ((tbl_treinamento.parametros_adicionais->\'linha\' IS NULL) OR (' . implode(' OR ', $consulta_linha_arr) . '))';
    } else {
        $consulta_linha = ' AND tbl_treinamento.parametros_adicionais->\'linha\' IS NULL ';
    }
   
    if (isset($posto_atende_familia)) {
        $consulta_familia_arr = [];
        foreach ($posto_atende_familia as $paf) {
            $consulta_familia_arr[] = "(tbl_treinamento.parametros_adicionais->'familia' ? '{$paf}')";
        }
        $consulta_familia = ' AND ((tbl_treinamento.parametros_adicionais->\'familia\' IS NULL) OR  (' . implode(' OR ', $consulta_familia_arr) . '))';
    } else {
        $consulta_familia = ' AND tbl_treinamento.parametros_adicionais->\'familia\' IS NULL ';
    }

    if (!empty($posto_tipo_posto)) {
        $consulta_tipo_posto = " AND ((tbl_treinamento.parametros_adicionais->'tipo_posto' IS NULL) OR (tbl_treinamento.parametros_adicionais->'tipo_posto' ? '{$posto_tipo_posto}' ))";
    } else {
        $consulta_tipo_posto = ' AND tbl_treinamento.parametros_adicionais->\'tipo_posto\' IS NULL ';
    }

    if (!empty($posto_categoria_posto)) {
        $consulta_categoria_posto = " AND ((tbl_treinamento.parametros_adicionais->'categoria_posto' IS NULL) OR  (tbl_treinamento.parametros_adicionais->'categoria_posto' ? '{$posto_categoria_posto}' ))";
    } else {
        $consulta_categoria_posto = ' AND tbl_treinamento.parametros_adicionais->\'categoria_posto\' IS NULL ';
    }

    $sql2="SELECT   tbl_treinamento.treinamento,
                    tbl_treinamento.vagas - (
                        SELECT COUNT(treinamento_posto) AS qtd_inscritos_posto
                            FROM tbl_treinamento_posto
                            WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                AND tbl_treinamento_posto.tecnico IS NOT NULL
                                AND tbl_treinamento_posto.ativo is true
                    ) as vagas_geral,
                    tbl_treinamento.vaga_posto,
                    tbl_treinamento.vaga_posto - (
                        SELECT COUNT(treinamento_posto) AS qtd_inscritos_posto
                            FROM tbl_treinamento_posto
                            WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                AND tbl_treinamento_posto.tecnico IS NOT NULL
                                AND tbl_treinamento_posto.ativo is true
                                AND tbl_treinamento_posto.posto = {$login_posto}
                    ) as vaga_por_posto,
                    tbl_treinamento.marca AS linha_nome, 
                    tbl_treinamento.tipo_posto as tipo_posto_treinamento,  
                    tbl_treinamento.categoria AS treinamento_categoria
                FROM tbl_treinamento
                WHERE tbl_treinamento.fabrica = {$login_fabrica}
                    $consulta_marca
                    $consulta_linha
                    $consulta_familia
                    $consulta_tipo_posto
                    $consulta_categoria_posto
                    AND tbl_treinamento.data_fim > CURRENT_DATE group by tbl_treinamento.treinamento,tbl_treinamento.vaga_posto;";
    //$res2 = pg_query($con,$sql2);

    $sql2 = "SELECT     tbl_treinamento.treinamento,
                        tbl_treinamento.vagas - (
                        SELECT COUNT(treinamento_posto) AS qtd_inscritos_posto
                                FROM tbl_treinamento_posto
                                WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                    AND tbl_treinamento_posto.tecnico IS NOT NULL
                                    AND tbl_treinamento_posto.ativo is true
                        ) as vagas_geral,
                        tbl_treinamento.vaga_posto,
                        tbl_treinamento.vaga_posto - (
                        SELECT COUNT(treinamento_posto) AS qtd_inscritos_posto
                                FROM tbl_treinamento_posto
                                WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                    AND tbl_treinamento_posto.tecnico IS NOT NULL
                                    AND tbl_treinamento_posto.ativo is true
                                    AND tbl_treinamento_posto.posto = {$login_posto}
                        ) as vaga_por_posto,
                        array_to_string(array_agg( DISTINCT tbl_marca.nome),', ') AS marca_nome, 
                        array_to_string(array_agg( DISTINCT tbl_linha.nome),', ') AS linha_nome,
                        array_to_string(array_agg( DISTINCT tbl_familia.descricao),', ') AS familia_descricao,
                        tbl_treinamento.tipo_posto as tipo_posto_treinamento,  
                        tbl_treinamento.categoria AS treinamento_categoria
                    FROM tbl_treinamento
                        JOIN tbl_admin       USING(admin)
                        LEFT JOIN tbl_produto on (tbl_produto.linha = tbl_treinamento.linha OR tbl_produto.marca = tbl_treinamento.marca) 
                            AND tbl_produto.fabrica_i = {$login_fabrica}
                        LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
                        LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
                        LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
                    WHERE tbl_treinamento.fabrica     = $login_fabrica
                        AND tbl_treinamento.ativo       IS TRUE
                        AND tbl_treinamento.data_inicio >= CURRENT_DATE
                        AND tbl_treinamento.data_finalizado IS NULL
                        $consulta_marca
                        $consulta_linha
                        $consulta_familia
                        $consulta_tipo_posto
                        $consulta_categoria_posto
                    GROUP BY 
                        tbl_treinamento.treinamento,
                        vagas_geral,
                        tbl_treinamento.vaga_posto,
                        vaga_por_posto,
                        tipo_posto_treinamento,
                        treinamento_categoria ;";
    $res2 = pg_query($con,$sql2);
    //echo nl2br($sql2); exit;
    if (pg_num_rows($res2) > 0) {
        $temVagas = "";
        for ($l=0; $l < pg_num_rows($res2); $l++) { 
            unset($cod_posto);
            $posto_especifico = false;
            $treinamento_bd = pg_fetch_result($res2, $l, treinamento);
            $sql_treinamento = "SELECT tbl_treinamento_posto.posto, tbl_treinamento_posto.tecnico 
                                FROM tbl_treinamento 
                                JOIN tbl_treinamento_posto ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento 
                                WHERE tbl_treinamento.treinamento = $treinamento_bd
                                AND tbl_treinamento.fabrica = $login_fabrica
                                AND tbl_treinamento_posto.ativo IS TRUE ";
            $res_treinamento = pg_query($con, $sql_treinamento);
            if (pg_num_rows($res_treinamento) > 0) {
                for ($t=0; $t < pg_num_rows($res_treinamento); $t++) { 
                    $cod_tecnico = pg_fetch_result($res_treinamento, $t, 'tecnico');
                    if (empty($cod_tecnico)) {
                        $posto_especifico = true;
                    } 
                    $cod_posto[] = pg_fetch_result($res_treinamento, $t, 'posto'); 
                }
            }
            if (isset($cod_posto)) {
                if ($posto_especifico == true && !in_array($login_posto, $cod_posto)) {
                    continue;
                }
            }
            $vagas_geral = pg_fetch_result($res2, $l, vagas_geral);
            $vaga_por_posto = pg_fetch_result($res2, $l, vaga_por_posto);
            $valida_vaga_posto = pg_fetch_result($res2, $l, vaga_posto);

            if (empty($valida_vaga_posto)) {
                if ($vagas_geral > 0) {
                    $temVagas = "Há Vagas!";
                }
            } else {
                if ($vagas_geral > 0 AND $vaga_por_posto > 0) {
                    $temVagas = "Há Vagas!";
                }
            } 
        }
    }
}

if (in_array($login_fabrica, array(175))) {
	$sqlFerramentas = "
		SELECT COUNT(*)
		FROM tbl_posto_ferramenta
        WHERE fabrica = {$login_fabrica}
        AND posto = {$login_posto}
        AND ativo IS TRUE
        AND aprovado IS NOT NULL
		AND ((validade_certificado - CURRENT_DATE) <= 60)
	";
	$resFerramentas = pg_query($con, $sqlFerramentas);
    $count_ferramentas = pg_fetch_result($resFerramentas, 0, 0);
    
    $sqlPedidosAguardandoAprovacao = "
        SELECT COUNT(pedido)
        FROM tbl_pedido
        WHERE fabrica = {$login_fabrica}
        AND posto = {$login_posto}
        AND status_pedido = 18
    ";
    $resPedidosAguardandoAprovacao = pg_query($con, $sqlPedidosAguardandoAprovacao);
    $count_pedidos_aguardando_aprovacao = pg_fetch_result($resPedidosAguardandoAprovacao, 0, 0);
}

// INI Regras submenu OS
// Regras por Tipo de Posto
$tipo_posto      = $tipo_posto ? : $cook_tipo_posto;
$e_posto_interno = $login_posto_interno; // autentica_usuario

// Lepono: Mostra Cadastro de tecnico para posto interno
$mostra_cad_tecnico = (isFabrica(184,191,193) && $e_posto_interno) ? $login_fabrica : 0;

// Mondial: Mostra OS Revenda para posto interno ou de revenda
$mostra_revenda = (isFabrica(151) and ($e_posto_interno or $cook_tipo_posto_revenda == 't')) ? $login_fabrica : 0;
// Imbera: apenas posto interno pode abrir OS
$bloqCadastroOs = (isFabrica(52) or (isFabrica(158) and !$e_posto_interno)) ? $login_fabrica : null;
// Esmaltec: bloqueia postos do NE para abrir ou fechar OS, mas libera fechamento OS revenda
if (isFabrica(30) && ($digita_os_consumidor =='f' or empty($digita_os_consumidor)) ) {
    $bloqAbreFechaOs = $login_fabrica;
    $fechaOsRevenda = '.de.revenda';
}

if (isFabrica(183)) {
    $nao_exibe_os = $login_fabrica;
}

// if (isFabrica(173) AND $login_posto_interno == false) {
//     $os_fechamento[] = $login_fabrica;
//     $bloqAbreFechaOs = $login_fabrica;
// }

// Regras por fabricante
$usaNovaTelaOs   = ($novaTelaOs or isFabrica(52))  ? $login_fabrica : null;
$usaTelaAntigaOs = !$novaTelaOs ? $login_fabrica : null;
$ocultarTelaAntigaOS = (isFabrica(28,52,138,142,143,145,146, $bloqAbreFechaOs, $bloqCadastroOs, $usaNovaTelaOs) || $digita_os_consumidor == 'f');
$ocultarNovaTelaOS   = (isFabrica($bloqCadastroOs, $bloqAbreFechaOs, $usaTelaAntigaOs, $nao_exibe_os) || $digita_os_consumidor == 't');
//$ocultarFechamentoOs = (isFabrica(20, 35) || $bloqAbreFechaOs || $LU_fecha_os === false); /*HD-3853582 27/10/2017*/
$ocultarFechamentoOs = (isFabrica(20,35) || $bloqAbreFechaOs);

$ocultarOsRevenda    = isFabrica(7, 20, 28, 42) and !$mostra_revenda;

// INI Regras cabeçalho NEW //
$existe_pendencia_tc = ($telecontrol_distrib=='t' and $login_bloqueio_pedido == 't');

// HBFlex: Desabilitar para fabricantes suspensos.
$deshabilita_tela = (isFabrica(25) and !isPosto(6359)) ? "Serviço desabilitado pelo fabricante" : '';

define('TELA_MENU', (strpos($PHP_SELF, 'menu_')!==false));  // Define se a tela atual ? algum menu

if ($helpdeskPostoAutorizado == true) {
    include_once 'helpdesk.inc.php';
    $helpdesk_lista_link   = in_array($login_fabrica, $libera_hd_posto_new)
        ? 'helpdesk_posto_autorizado_listar.php'
        : 'helpdesk_listar.php';
    $helpdesk_cad_link     = in_array($login_fabrica, $libera_hd_posto_new)
        ? 'helpdesk_posto_autorizado_novo_atendimento.php'
        : 'helpdesk_cadastrar.php';
	$temHDs                = hdPossuiChamadosPendentesQtde();
    $helpdesk_lista_titulo = 'Help-Desk' . ($temHDs ? " [ $temHDs ]" : '');

    if (isFabrica(42) && !strpos($_SERVER['PHP_SELF'], '/helpdesk_cadastrar.php')) {
        include_once('faq_makita.php');
    }
}

// FIM regras cabeçalho NEW //

