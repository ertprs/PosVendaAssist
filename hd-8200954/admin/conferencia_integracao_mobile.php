<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "call_center";
include 'autentica_admin.php';
include 'funcoes.php';

date_default_timezone_set("America/Sao_Paulo");

$btn_acao = $_REQUEST['btn_acao'];

if (isset($btn_acao)) {
    if ($btn_acao == 'pesquisar') {
        $familia          = $_REQUEST['familia'];
        $unidade_negocio  = $_REQUEST['unidade_negocio'];
        $tipo_atendimento = $_REQUEST['tipo_atendimento'];
        $prioridade       = $_REQUEST['prioridade'];
        $os               = $_REQUEST['os'];

        if (empty($os)) {
            if (empty($familia)) {
                $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][] = "familia";
            }

            if (empty($unidade_negocio)) {
                $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][] = "unidade_negocio";
            }
        }

        if (count($msg_erro['msg']) == 0) {
            if (empty($os)) {
                $whereFamilia        = "AND f.familia = {$familia}";
                $whereUnidadeNegocio = "AND JSON_FIELD('unidadeNegocio', oce.campos_adicionais) = '{$unidade_negocio}'";

                if (strlen($tipo_atendimento) > 0) {
                    $whereTipoAtendimento = "AND o.tipo_atendimento = {$tipo_atendimento}";
                }

                if (strlen($prioridade) > 0) {
                    $wherePrioridade = "AND hccp.hd_chamado_cockpit_prioridade = {$prioridade}";
                }
            } else {
                $whereOs = "AND om.os = {$os}";
            }

            $sql = "
                SELECT
                    om.os_mobile, om.os, om.dados, om.data_input, om.status_os_mobile, om.conferido,
                    hccp.descricao AS prioridade_descricao, hccp.cor AS prioridade_cor,
                    f.descricao AS familia,
                    un.descricao AS unidade_negocio,
                    ta.descricao AS tipo_atendimento,
                    rsle.contents, rsle.error_message
                FROM tbl_os_mobile om
                JOIN tbl_os o USING(os,fabrica)
                JOIN tbl_os_produto op ON op.os = o.os
                JOIN tbl_os_campo_extra oce ON oce.os = o.os
                JOIN (
                    SELECT DISTINCT ds.unidade_negocio, c.nome AS descricao
                    FROM tbl_distribuidor_sla ds
                    JOIN tbl_cidade c ON c.cidade = ds.cidade
                    WHERE ds.fabrica = {$login_fabrica}
                ) AS un ON un.unidade_negocio = JSON_FIELD('unidadeNegocio', oce.campos_adicionais)
                JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
                JOIN tbl_hd_chamado hc ON hc.hd_chamado = o.hd_chamado AND hc.fabrica = {$login_fabrica}
                JOIN tbl_hd_chamado_cockpit hcc ON hcc.hd_chamado = hc.hd_chamado AND hcc.fabrica = {$login_fabrica}
                JOIN tbl_hd_chamado_cockpit_prioridade hccp ON hccp.hd_chamado_cockpit_prioridade = hcc.hd_chamado_cockpit_prioridade AND hccp.fabrica = {$login_fabrica}
                JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
                JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
                JOIN tbl_routine_schedule_log_error rsle ON rsle.line_number = om.os_mobile
                JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = rsle.routine_schedule_log
                JOIN tbl_routine_schedule rs ON rs.routine_schedule = rsl.routine_schedule
                JOIN tbl_routine r ON r.routine = rs.routine AND r.context LIKE 'OS Mobile'
                WHERE om.fabrica = {$login_fabrica}
                AND om.conferido IS NOT TRUE
                {$whereFamilia}
                {$whereUnidadeNegocio}
                {$wherePrioridade}
                {$whereTipoAtendimento}
                {$whereOs}
                ORDER BY data_input ASC
            ";
            $res = pg_query($con, $sql);

            $erros_integracao = array();

            if (pg_num_rows($res) > 0) {
                $erros_integracao = pg_fetch_all($res);

                $sqlTotais = "
                    SELECT
                        COUNT(o.os) AS total,
                        f.descricao AS familia,
                        un.descricao AS unidade_negocio,
                        hccp.descricao AS prioridade,
                        hccp.cor AS prioridade_cor,
                        ta.descricao AS tipo_atendimento
                    FROM (
                        SELECT DISTINCT ON (om2.os)
                            om2.os_mobile, om2.fabrica, om2.conferido, om2.os
                        FROM tbl_os_mobile om2
                        WHERE om2.fabrica = {$login_fabrica}
                        AND om2.conferido IS NOT TRUE
                    ) AS om
                    JOIN tbl_os o ON o.os = om.os AND o.fabrica = {$login_fabrica}
                    JOIN tbl_os_produto op ON op.os = o.os
                    JOIN tbl_os_campo_extra oce ON oce.os = o.os
                    JOIN (
                        SELECT DISTINCT ds.unidade_negocio, c.nome AS descricao
                        FROM tbl_distribuidor_sla ds
                        JOIN tbl_cidade c ON c.cidade = ds.cidade
                        WHERE ds.fabrica = {$login_fabrica}
                    ) AS un ON un.unidade_negocio = JSON_FIELD('unidadeNegocio', oce.campos_adicionais)
                    JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
                    JOIN tbl_hd_chamado hc ON hc.hd_chamado = o.hd_chamado AND hc.fabrica = {$login_fabrica}
                    JOIN tbl_hd_chamado_cockpit hcc ON hcc.hd_chamado = hc.hd_chamado AND hcc.fabrica = {$login_fabrica}
                    JOIN tbl_hd_chamado_cockpit_prioridade hccp ON hccp.hd_chamado_cockpit_prioridade = hcc.hd_chamado_cockpit_prioridade AND hccp.fabrica = {$login_fabrica}
                    JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
                    JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
                    JOIN tbl_routine_schedule_log_error rsle ON rsle.line_number = om.os_mobile
                    JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = rsle.routine_schedule_log
                    JOIN tbl_routine_schedule rs ON rs.routine_schedule = rsl.routine_schedule
                    JOIN tbl_routine r ON r.routine = rs.routine AND r.context LIKE 'OS Mobile'
                    GROUP BY f.descricao, un.descricao, hccp.descricao, hccp.cor, ta.descricao
                    ORDER BY familia ASC, unidade_negocio ASC, tipo_atendimento ASC, prioridade ASC
                ";
                $resTotais = pg_query($con, $sqlTotais);

                echo pg_last_error();
            }
        }
    } else if ($btn_acao == 'conferir') {
        $os_mobile     = $_REQUEST['osMobile'];
        $os            = $_REQUEST["os"];
        $dados         = utf8_decode($_REQUEST["dados"]);
        $status_codigo = $_REQUEST['statusCodigo'];

        if (!empty($os_mobile)) {
            pg_query($con, "BEGIN");

            if (!empty($status_codigo)) {
                $up = "UPDATE tbl_os_mobile SET conferido = 't', status_os_mobile = '{$status_codigo}' WHERE os_mobile = {$os_mobile}";
            } else {
                $up = "UPDATE tbl_os_mobile SET conferido = 't' WHERE os_mobile = {$os_mobile}";
            }

            $qr = pg_query($con, $up);

            $interacao = "
                 INSERT INTO tbl_os_interacao
                (os, data, admin, comentario, fabrica)
                VALUES
                ({$os}, CURRENT_TIMESTAMP, {$login_admin}, '<h5>Integração Mobile x Web, conferência realizada</h5>{$dados}', {$login_fabrica})
            ";
            $qr = pg_query($con, $interacao);

            if (strlen(pg_last_error()) > 0) {
                pg_query($con, "ROLLBACK");

                $return = array("msg" => utf8_encode("Ocorreu um ero durante a conferência da integração"), "param" => 0);
            } else {

                pg_query($con, "COMMIT");

                $return = array("msg" => utf8_encode("Integração marcada como conferida"), "param" => 1);
            }
        } else {
            $return = array("msg" => utf8_encode("Integração não informada para fazer a conferência"), "param" => 0);
        }

        echo json_encode($return);
        exit;
    }
}

$title       = "Monitor de Interface Mobile/Web";
$layout_menu = "callcenter";

include "cabecalho_new.php";

$plugins = array(
    "alphanumeric"
);

include __DIR__.'/plugin_loader.php';
?>

<script>

$(function(){
    $("#os").numeric();

    $(document).on("click", ".conferir", function() {
        var osMobile     = $(this).data("os-mobile");
        var os           = $(this).data("os");
        var dados        = $(this).parents("tr").find("div.os-mobile-dados").html();
        var statusCodigo = $(this).data("status-codigo");
        var that         = $(this);

        $(that).html("<i class='icon-check icon-white'></i> Conferindo...").prop({ disabled: true });

        $.ajax({
            type: "POST",
            url: "conferencia_integracao_mobile.php",
            data: { btn_acao: 'conferir', osMobile: osMobile, statusCodigo: statusCodigo, os: os, dados: dados },
        }).done(function (retorno) {
            retorno = JSON.parse(retorno);

            if (retorno.param == 1) {
                $(that).parent("td").html("<label class='label label-success'>Conferido</label>");
            } else {
                $(that).html("<i class='icon-check icon-white'></i> Conferir").prop({ disabled: false });
                alert(retorno.msg);
            }
        });
    });
});

</script>

<?php 
if (count($msg_erro["msg"]) > 0) { 
?>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"]); ?></h4>
    </div>
<?php 
} 
?>

<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>

<form name="frm_busca_os_mobile" method="POST" action='<?= $PHP_SELF; ?>' align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
    <br />
    <div class='row-fluid'>
        <div class='span1'></div>
        <div class='span3'>
            <div class="control-group <?= (in_array("familia", $msg_erro["campos"])) ? "error" : ""; ?>">
                <label class="control-label" for="familia">Família</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico" >*</h5>
                        <select id="familia" name="familia" class="span12">
                            <option value="">Selecione</option>
                            <?php
                            $sql = "SELECT 
                                        familia, 
                                        descricao
                                    FROM tbl_familia 
                                    WHERE fabrica = {$login_fabrica} 
                                    AND ativo IS TRUE
                                    ORDER BY descricao";
                            $res = pg_query($con, $sql);

                            foreach (pg_fetch_all($res) as $res) { 
                                $familia   = $res['familia'];
                                $descricao = $res['descricao'];
                                $selected = ($familia == $_POST["familia"]) ? "SELECTED" : ""; 
                                ?>

                                <option value="<?=$familia?>" <?=$selected?> ><?=$descricao?></option>
                            <?php
                            } 
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span3'>
            <div class="control-group <?= (in_array("unidade_negocio", $msg_erro["campos"])) ? "error" : ""; ?>">
                <label class="control-label" for="unidade_negocio">Unidade de Negócio</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico" >*</h5>
                        <select id="unidade_negocio" name="unidade_negocio" class="span12">
                            <option value="">Selecione</option>
                            <?php
                            $sql = "SELECT DISTINCT
                                        ds.unidade_negocio, 
                                        c.nome
                                    FROM tbl_distribuidor_sla ds
                                    JOIN tbl_cidade c ON c.cidade = ds.cidade
                                    WHERE ds.fabrica = {$login_fabrica} 
                                    ORDER BY c.nome";
                            $res = pg_query($con, $sql);

                            foreach (pg_fetch_all($res) as $res) { 
                                $unidade_negocio   = $res['unidade_negocio'];
                                $nome = $res['nome'];
                                $selected = ($unidade_negocio == $_POST["unidade_negocio"]) ? "SELECTED" : ""; 
                                ?>

                                <option value="<?=$unidade_negocio?>" <?=$selected?> ><?=$nome?></option>
                            <?php
                            } 
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span3'>
            <div class="control-group">
                <label class="control-label" for="prioridade">Prioridade</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <select id="prioridade" name="prioridade" class="span12">
                            <option value="">Selecione</option>
                            <?php
                            $sql = "SELECT
                                        hd_chamado_cockpit_prioridade AS prioridade,
                                        descricao,
                                        peso
                                    FROM tbl_hd_chamado_cockpit_prioridade
                                    WHERE fabrica = {$login_fabrica} 
                                    AND ativo IS TRUE
                                    ORDER BY peso DESC";
                            $res = pg_query($con, $sql);

                            foreach (pg_fetch_all($res) as $res) { 
                                $prioridade   = $res['prioridade'];
                                $descricao = $res['descricao'];
                                $selected = ($prioridade == $_POST["prioridade"]) ? "SELECTED" : ""; 
                                ?>

                                <option value="<?=$prioridade?>" <?=$selected?> ><?=$descricao?></option>
                            <?php
                            } 
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>
    <div class='row-fluid'>
        <div class='span1'></div>
        <div class='span3'>
            <div class="control-group">
                <label class="control-label" for="tipo_atendimento">Tipo Atendimento</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <select id="tipo_atendimento" name="tipo_atendimento" class="span12">
                            <option value="">Selecione</option>
                            <?php
                            $sql = "SELECT 
                                        tipo_atendimento, 
                                        descricao AS tipo_atendimento_desc 
                                    FROM tbl_tipo_atendimento 
                                    WHERE fabrica = {$login_fabrica} 
                                    AND (grupo_atendimento != 'P' OR grupo_atendimento IS NULL)
                                    ORDER BY descricao";
                            $res = pg_query($con, $sql);

                            foreach (pg_fetch_all($res) as $dados_tipo_atendimento) { 
                                $xtipo_atendimento       = $dados_tipo_atendimento['tipo_atendimento'];
                                $tipo_atendimento_xdesc  = $dados_tipo_atendimento['tipo_atendimento_desc'];
                                $select_tipo_atendimento = ($tipo_atendimento == $xtipo_atendimento) ? "SELECTED" : ""; 
                                ?>

                                <option value="<?= $xtipo_atendimento; ?>" <?= $select_tipo_atendimento; ?>><?= $tipo_atendimento_xdesc; ?></option>
                            <?php
                            } 
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class="control-group">
                <label class="control-label" for="os">Número de OS</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <input type="text" name="os" id="os" value="<?=$_POST['os']?>" class="span12 numeric" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>
    <div class="row-fluid tac">
    <input type="hidden" name="btn_acao" value="" />
    <button type="button" class="btn btn-default" onclick="if ($('input[name=btn_acao]').val() == '') { $('input[name=btn_acao]').val('pesquisar'); $('form[name=frm_busca_os_mobile]').submit(); } else { alert('Aguarde o processamento do formulário!'); }">Pesquisar</button>
    </div>
</form>

<?php
if (isset($_POST['btn_acao'])) {
    if (count($msg_erro['msg']) == 0) {
        if (count($erros_integracao) > 0) { 
        ?>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="titulo_coluna" >
                        <th colspan="5" >Total de OSs com erros de integração</th>
                    </tr>
                    <tr class="titulo_coluna" >
                        <th>Unidade de Negócio</th>
                        <th>Família</th>
                        <th>Tipo de Atendimento</th>
                        <th>Prioridade</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($total = pg_fetch_object($resTotais)) {
                        echo "
                            <tr>
                                <td>
                                    {$total->unidade_negocio}
                                </td>
                                <td>
                                    {$total->familia}
                                </td>
                                <td>
                                    {$total->tipo_atendimento}
                                </td>
                                <th style='background-color: #{$total->prioridade_cor};' >
                                    {$total->prioridade}
                                </th>
                                <th>
                                    {$total->total}
                                </th>
                            </tr>
                        ";
                    }
                    ?>
                </tbody>
            </table>

            <hr />

            <?php
            $sqlServicos = "
                SELECT servico_realizado AS id, descricao FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica}
            ";
            $resServicos = pg_query($con, $sqlServicos);

            $servico_realizado = array();

            while ($row = pg_fetch_object($resServicos)) {
                $servico_realizado[$row->id] = $row->descricao;
            }

            $oss_erro_integracao = array();

            array_map(function($r) {
                global $oss_erro_integracao;

                $oss_erro_integracao[$r["os"]][] = $r;
            }, $erros_integracao);

            foreach ($oss_erro_integracao as $os => $erros_integracao) {
                $prioridade_cor       = $erros_integracao[0]["prioridade_cor"];
                $prioridade_descricao = $erros_integracao[0]["prioridade_descricao"];
                $tipo_atendimento     = $erros_integracao[0]["tipo_atendimento"];
                $unidade_negocio      = $erros_integracao[0]["unidade_negocio"];
                ?>
                <table id="resultados_log_erros" class="table table-striped table-bordered table-hover table-fixed">
                    <thead>
                        <tr>
                            <th colspan="5" style="background-color: #<?=$prioridade_cor?>;" >
                                <span class="pull-left" style="color: #FFF;">
                                    <a href="os_press.php?os=<?=$os?>" target="_blank" style="color: #FFF;" >OS <?=$os?></a> / <?=$unidade_negocio?> - <?=$tipo_atendimento?> - <?=$prioridade_descricao?>
                                </span>
                            </th>
                        </tr>
                        <tr class="titulo_coluna">
                            <th>Data</th>
                            <th>Status</th>
                            <th>Erro</th>
                            <th>Dados</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($erros_integracao as $key => $log) {
                            $os_mobile     = $log['os_mobile'];
                            $contents      = utf8_decode($log['contents']);
                            $error_message = utf8_decode($log['error_message']);
                            $conferido     = $log["conferido"];

                            $json_dados    = json_decode($log['dados'], true);

                            if (!empty($json_dados['status'])) {
                                $status_codigo  = $json_dados['status']['codigo'];
                                $status_nome    = utf8_decode($json_dados['status']['nome']);
                                $status_message = $status_codigo." - ".$status_nome;
                            } else {
                                $status_message = "";
                            }
                            ?>
                            <tr id="<?=$os_mobile?>" >
                                <td class="tac"><?=date("d/m/Y H:i", strtotime($log["data_input"]))?></td>
                                <td class="tac"><?=$status_message?></td>
                                <td class="tac text-error" nowrap ><?=$contents?></td>
                                <td class="tac" >
                                    <a href="#dados-<?=$os?>" role="button" class="btn btn-link btn-small" data-toggle="modal" >Visualizar</a>

                                    <div id="dados-<?=$os?>" class="modal hide fade" tabindex="-1" role="dialog" >
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true" >×</button>
                                        </div>
                                        <div class="modal-body os-mobile-dados" style="width: 94%;" >
                                            <table class="table table-bordered" style="width: 100%;" >
                                                <tbody>
                                                    <tr>
                                                        <th class="text-info tar" >Ordem de Serviço</th>
                                                        <td><?=$os?></td>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-info tar" >Status</th>
                                                        <td><?=$status_message?></td>
                                                    </tr>
                                                    <?php
                                                    if (count($json_dados["defeitosConstatados"]) > 0) {
                                                    ?>
                                                        <tr>
                                                            <th class="text-info tar" >Defeitos Constatados</th>
                                                            <td>
                                                                <ul>
                                                                    <li><strong>Código</strong></li>
                                                                    <?php
                                                                    foreach ($json_dados["defeitosConstatados"] as $defeito_constatado) {
                                                                        list($codigo, $a, $b) = explode("_", $defeito_constatado);

                                                                        echo "<li>{$codigo}</li>";
                                                                    }
                                                                    ?>
                                                                </ul>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                    }

                                                    if (count($json_dados["solucao"]) > 0) {
                                                    ?>
                                                        <tr>
                                                            <th class="text-info tar" >Soluções</th>
                                                            <td>
                                                                <ul>
                                                                    <li><strong>Código</strong></li>
                                                                    <?php
                                                                    foreach ($json_dados["solucao"] as $solucao) {
                                                                        list($codigo, $a, $b) = explode("_", $solucao);

                                                                        echo "<li>{$codigo}</li>";
                                                                    }
                                                                    ?>
                                                                </ul>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                    }

                                                    if (count($json_dados["pecas"]) > 0) {
                                                    ?>
                                                        <tr>
                                                            <th class="text-info tar" >Peças</th>
                                                            <td>
                                                                <ul>
                                                                    <li>
                                                                        <strong>Referência</strong> - <strong>Qtde</strong> - <strong>Serviço</strong>
                                                                    </li>
                                                                    <?php
                                                                    foreach ($json_dados["pecas"] as $peca) {
                                                                        echo "
                                                                            <li>
                                                                                {$peca['referencia']} - {$peca['qtde']} - {$servico_realizado[$peca['servicoRealizado']]}
                                                                            </li>
                                                                        ";
                                                                    }
                                                                    ?>
                                                                </ul>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                    }

                                                    $observacao = "";

                                                    if (count($json_dados["anexos"]) > 0) {
                                                    ?>
                                                        <tr>
                                                            <th class="text-info tar" >Anexos</th>
                                                            <td>
                                                                <ul>
                                                                    <?php
                                                                    foreach ($json_dados["anexos"] as $i => $anexo) {
                                                                        $link = "http://telecontrol.eprodutiva.com.br/api/ordem/anexo/{$anexo['id']}/imagem?x=1000&y=1000";

                                                                        echo "
                                                                            <li>
                                                                                <a href='{$link}' target='_blank' >Anexo ".++$i."</a>
                                                                            </li>
                                                                        ";

                                                                        $observacao .= utf8_decode($anexo["descricao"])."<br />";
                                                                    }
                                                                    ?>
                                                                </ul>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                    }

                                                    if (!empty($observacao)) {
                                                    ?>
                                                        <tr>
                                                            <th class="text-info tar" >Observação</th>
                                                            <td><?=$observacao?></td>
                                                        </tr>
                                                    <?php
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                                <td class="tac" nowrap >
                                    <?php
                                    echo "<button type='button' class='btn btn-success btn-small conferir' data-os-mobile='{$os_mobile}' data-os='{$os}' data-erro='{$error_message}' data-status-codigo='{$status_codigo}' ><i class='icon-check icon-white'></i> Conferir</button>";
                                    ?>

                                    <a href="cadastro_os.php?os_id=<?= $os; ?>" target="blank" class="btn btn-primary btn-small"><i class='icon-edit icon-white'></i> Alterar OS</a>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>

                <hr />
            <?php
            }
        } else { 
        ?>
            <div class="alert">
                <h4>Nenhum resultado encontrado</h4>
            </div>
        <?php
        }
    }
}

include "rodape.php"; 
?>
