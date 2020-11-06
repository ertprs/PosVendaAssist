<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

use Posvenda\Regras;
use Posvenda\Pedido;
use Posvenda\Os;
use Posvenda\Fabricas\_158\ExportaPedido;
use Posvenda\Fabricas\_158\PedidoBonificacao;

$oOS = new Os($login_fabrica);
$pedidoClass = new Pedido($login_fabrica);
$oExportaPedido = new ExportaPedido($pedidoClass, $oOS, $login_fabrica);
$oPedidoBonificacao = new PedidoBonificacao($pedidoClass);

$ajax_estoque_movimento = $_REQUEST['ajax_estoque_movimento'];
$ajax_pedido_bonificado = $_REQUEST['ajax_pedido_bonificado'];
$ajax_exporta_pedido    = $_REQUEST['ajax_exporta_pedido'];
$ajax_exporta_pedidos   = $_REQUEST['ajax_exporta_pedidos'];

if (isset($ajax_pedido_bonificado)) {

    $posto = $_REQUEST['posto'];

    if (!$oPedidoBonificacao->verificaDistribuidor($posto)) {
        $retorno = array('status' => 'error', 'msg' => utf8_encode('Posto não pertence à nenhum distribuidor'));
    }
    
    if (!isset($retorno)) {
        $acao = $oPedidoBonificacao->abasteceEstoque($posto, $login_fabrica);
        $acaoAumentoKit = $oPedidoBonificacao->pedidoAumentoEstoque($posto, $login_fabrica);
        if (count($acaoAumentoKit) > 0) {
            $retorno = $acao + $acaoAumentoKit;
        }
    }

    if (!isset($retorno)) {
        $retorno = $acao;
    }

    echo json_encode($retorno);
    exit;

} else if (isset($ajax_exporta_pedido)) {

    $estoque = $_REQUEST['dados'];
    $estoque = json_decode(stripslashes($estoque), true);
    $posto = $_REQUEST['posto'];
    $os = $_REQUEST['os'];

    $pedido = key($estoque);
    $dados_exporta[$os] = $estoque;

    if (strtotime("today") > strtotime("2017-11-30 00:00:00")) {
    	$oExportaPedido->pedidoIntegracaoSemDeposito($dados_exporta);
    } else {
        if (empty($estoque[$pedido]['codigo_tipo_atendimento'])) {
            $oExportaPedido->pedidoIntegracao($dados_exporta, "aumento_kit");
        } else {
            $dados_exporta = $oPedidoBonificacao->adicionaNotaFiscal($dados_exporta, $posto, $login_fabrica);
            $oPedidoBonificacao->pedidoBonificadoIntegracao($dados_exporta, $posto, $login_fabrica);
        }
    }

    $sql = "SELECT exportado FROM tbl_pedido WHERE pedido = {$pedido} AND fabrica = {$login_fabrica};";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) == 0) {
        $res_pedido_exportado = pg_fetch_result($res, 0, exportado);
        if (!empty($res_pedido_exportado)) {
            $retorno = array("msg" => utf8_encode("Pedido Exportado com sucesso"), "param" => 1);
        } else {
            $retorno = array("msg" => utf8_encode("Pedido não foi exportado, clique no pedido para saber mais detalhes"), "param" => 0);
        }
    } else {
        $retorno = array("msg" => utf8_encode("Ocorreu um erro durante o processamento"), "param" => 0);
    }

    echo json_encode($retorno);
    exit;

} else if (isset($ajax_exporta_pedidos)) {

    $estoque = $_REQUEST['dados'];
    $estoque = json_decode(stripslashes($estoque), true);
    $posto = $_REQUEST['posto'];
    
    $dados_exporta = $oPedidoBonificacao->adicionaNotaFiscal($estoque, $posto, $login_fabrica);
    $oPedidoBonificacao->pedidoBonificadoIntegracao($dados_exporta, $posto, $login_fabrica);

    $retorno = array("msg" => utf8_encode("Processamento finalizado, clique no(s) pedido(s) para saber mais detalhes"));

    echo json_encode($retorno);
    exit;

} else if (isset($ajax_estoque_movimento)) {

    $posto = $_REQUEST['posto'];
    $peca = $_REQUEST['peca'];
    $fabrica = $_REQUEST['fabrica'];

    $posto_interno_nao_gera = \Posvenda\Regras::get("posto_interno_nao_gera", "pedido_garantia", $fabrica);

    if ($posto_interno_nao_gera == true) {
        $wherePostoInterno = "AND tp.posto_interno IS NOT TRUE";
    }

    $sql = "
        SELECT
            TO_CHAR(epm.data_digitacao, 'DD/MM/YYYY HH24:MI') AS data_digitacao,
            epm.qtde_saida,
            epm.nf,
            to_char(epm.data, 'DD/MM/YYYY') AS data_nf,
            p.pedido,
            p.exportado AS pedido_exportado,
            epm.obs
        FROM tbl_estoque_posto_movimento epm
        JOIN tbl_posto_fabrica pf ON pf.posto = epm.posto AND pf.fabrica = {$login_fabrica}
        JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
        JOIN tbl_os o ON o.os = epm.os
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento
        JOIN tbl_os_item oi ON oi.os_item = epm.os_item
        JOIN tbl_peca pc ON pc.peca = oi.peca AND pc.fabrica = {$login_fabrica}
        LEFT JOIN tbl_pedido p ON p.pedido = oi.pedido AND p.fabrica = {$login_fabrica} AND p.posto = {$posto}
        WHERE epm.fabrica = {$login_fabrica}
        AND epm.posto = {$posto}
        AND epm.peca = {$peca}
        AND epm.qtde_saida IS NOT NULL
        {$wherePostoInterno}
        AND (ta.fora_garantia IS TRUE OR tp.tecnico_proprio IS TRUE)
        AND (p.status_pedido = 1 OR oi.pedido IS NULL);
    ";

    $res = pg_query($con, $sql);
    $movimentos = pg_fetch_all($res);

    /*if ($_REQUEST['gerar_excel'] && $ex_count > 0) {

        $data = date("d-m-Y-H:i");

        $arquivo_nome       = "relatorio-qtde-os-por-posto-periodo-$data.xls";
        $path                       = "xls/";
        $path_tmp           = "/tmp/";

        $arquivo_completo       = $path.$arquivo_nome;
        $arquivo_completo_tmp   = $path_tmp.$arquivo_nome;

        $fp = fopen($arquivo_completo_tmp,"w");

        $table = "<table border='1'>";
        $table .= "<thead>";
        $table .= "<tr>";
        $table .= "<th>Linha: ".$descLinha."</th>";
        $table .= "<th colspan='3'>Posto: ".$descPosto."</th>";
        $table .= "</tr>";
        $table .= "<tr>";
        $table .= "<th>OS</th>";
        $table .= "<th>Abertura</th>";
        $table .= "<th>Fechamento</th>";
        $table .= "<th>Produto</th>";
        $table .= "</tr>";
        $table .= "</thead>";
        $table .= "<tbody>";

        foreach ($vistaExplodida as $ex_os) {
            $table .= "<tr>";
            $table .= "<td>".$ex_os['sua_os']."</td>";
            $table .= "<td>".$ex_os['data_digitacao']."</td>";
            $table .= "<td>".$ex_os['data_fechamento']."</td>";
            $table .= "<td>".$ex_os['produto']."</td>";
            $table .= "</tr>";
        }

        $table .= "</tbody>";
        $table .= "</table>";

        fwrite($fp, $table);

        fclose($fp);

        if (file_exists($arquivo_completo_tmp)) {
            system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
            echo $arquivo_completo;
        }

        exit;
    }*/
}
?>
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>

<? if (count($movimentos) > 0) { ?>
    <div style="overflow-y:scroll;height:570px;">
        <table id='resultado_vista_explodida' class='table table-striped table-bordered table-hover table-large'>
            <thead>
                <tr class="titulo_coluna">
                    <th colspan="100%">Movimentações de Saída</th>
                </tr>
                <tr class="titulo_coluna">
                    <th>Data</th>
                    <th>Quantidade</th>
                    <th>Nota Fiscal</th>
                    <th>Data NF</th>
                    <th>Pedido</th>
                    <th>Exportado</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                <? foreach ($movimentos as $i => $movimento) {
                    $m_data_digitacao = $movimento['data_digitacao'];
                    $m_qtde_saida = $movimento['qtde_saida'];
                    $m_nf = $movimento['nf'];
                    $m_data_nf = $movimento['data_nf'];
                    $m_pedido = $movimento['pedido'];
                    $m_pedido_exportado = $movimento['pedido_exportado'];
                    $m_obs = $movimento['obs'];

                    if (strlen($m_pedido) > 0) {
                        $img_exportado = "<img name='exportado' src='imagens/".(!empty($m_pedido_exportado) ? 'status_verde.png' : 'status_vermelho.png')."' title='".(!empty($m_pedido_exportado) ? 'Exportado' : 'Pendente')."' />";
                    } else {
                        $img_exportado = "";
                    } ?>
                    <tr>
                        <td class="tac"><?= $m_data_digitacao; ?></td>
                        <td class="tac"><?= $m_qtde_saida; ?></td>
                        <td class="tac"><?= $m_nf; ?></td>
                        <td class="tac"><?= $m_data_nf; ?></td>
                        <td class="tac"><?= (strlen($m_pedido) > 0) ? "<a href='pedido_admin_consulta.php?pedido=".$m_pedido."' target='_blank'>".$m_pedido."</a>" : ""; ?></td>
                        <td class="tac"><?= $img_exportado; ?></td>
                        <td class="tac"><?= $m_obs; ?></td>
                    </tr>
                <? } ?>
            </tbody>
        </table>
        <? /*$jsonPOST = excelPostToJson($_REQUEST); ?>
        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?= $jsonPOST; ?>' />
            <span><img src="imagens/excel.png" /></span>
            <span class="txt">Gerar Arquivo Excel</span>
        </div>*/ ?>
    </div>
<? } ?>
