<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';

if ($_REQUEST) {

    $data_inicial       = $_REQUEST['data_inicial'];
    $data_final         = $_REQUEST['data_final'];
    $codigo_posto       = $_REQUEST['codigo_posto'];
    $descricao_posto    = $_REQUEST['descricao_posto'];

    if ((!strlen($data_inicial) || !strlen($data_final))) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data";
    } else if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_inicial = "{$yi}-{$mi}-{$di}";
            $aux_data_final   = "{$yf}-{$mf}-{$df}";

            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                $msg_erro["campos"][] = "data";
            }
        }
    }

    if (!empty($codigo_posto) || !empty($descricao_posto)) {

        if (!empty($codigo_posto)) {
            $whereCodPosto = "AND tbl_posto_fabrica.codigo_posto = '{$codigo_posto}'";
        }

        if (!empty($descricao_posto)) {
            $whereDescPosto = "AND TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))";
        }

        $sql = "
            SELECT
                tbl_posto_fabrica.posto
            FROM tbl_posto
            JOIN tbl_posto_fabrica USING(posto)
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            {$whereCodPosto}
            {$whereDescPosto};
        ";

        $res = pg_query($con ,$sql);

        if (pg_num_rows($res) == 0) {
            $msg_erro["msg"][]    = "Posto não encontrado";
            $msg_erro["campos"][] = "posto";
        } else {
            $posto = pg_fetch_result($res, 0, posto);
        }
    }


    if (count($msg_erro['msg']) == 0) {

        if (!empty($posto)) {
            $wherePosto = "AND hce.posto = {$posto}";
        }

        $sql = "
            SELECT
                hc.hd_chamado,
                hce.serie,
                hce.nota_fiscal,
                TO_CHAR(hc.data, 'DD/MM/YYYY HH24:MI:SS') AS data_abertura,
                hce.consumidor_revenda,
                hce.revenda_nome,
                hce.nome AS consumidor_nome,
                pdt.referencia||' - '||pdt.descricao AS produto,
                pf.codigo_posto||' - '||pst.nome AS posto
            FROM tbl_hd_chamado hc
            JOIN tbl_hd_chamado_extra hce USING(hd_chamado)
            JOIN tbl_produto pdt ON pdt.produto = hce.produto AND pdt.fabrica_i = {$login_fabrica}
            JOIN tbl_posto_fabrica pf ON pf.posto = hce.posto AND pf.fabrica = {$login_fabrica}
            JOIN tbl_posto pst ON pst.posto = pf.posto
            WHERE hc.fabrica = {$login_fabrica}
            AND hc.fabrica_responsavel = {$login_fabrica}
            AND hce.abre_os IS TRUE
            AND hce.os IS NULL
            AND hc.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
            {$wherePosto};
        ";

        $resSubmit = pg_query($con,$sql);
        $count = pg_num_rows($resSubmit);
    }

}

$layout_menu = "callcenter";
$title = "RELATÓRIO DE PRÉ ORDENS DE SERVIÇOS";
include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php"); ?>

<script type="text/javascript">
    $(function() {
        $.datepickerLoad(["data_final", "data_inicial"]);
        $.autocompleteLoad(["posto"]);
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }
</script>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<? } ?>

<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?= $PHP_SELF; ?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
    <br/>

    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?= (in_array("data", $msg_erro["campos"])) ? "error" : ""; ?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span6'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?= $data_inicial; ?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?= (in_array("data", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span6'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?= $data_final; ?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?= (in_array("posto", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?= $codigo_posto; ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?= (in_array("posto", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?= $descricao_posto; ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid tac">
        <button class='btn' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </div>
</form>

<? if ($_POST && count($msg_erro['msg']) == 0) {
    if ($count > 0) { ?>
        <table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-large'>
            <thead>
                <tr class="titulo_coluna">
                    <th colspan="9">Pesquisando período de <?= $data_inicial; ?> até <?= $data_final; ?></th>
                </tr>
                <tr class='titulo_coluna'>
                    <th>Pré Ordem</th>
                    <th>Data Abertura</th>
                    <th>Serie</th>
                    <th>Nota Fiscal</th>
                    <th>C/R</th>
                    <th>Consumidor</th>
                    <th>Revenda</th>
                    <th>Produto</th>
                    <th>Posto</th>
                </tr>
            </thead>
            <tbody>
                <? for ($i = 0; $i < $count; $i++) {
                    $res_hd_chamado = pg_fetch_result($resSubmit, $i, hd_chamado);
                    $res_serie = pg_fetch_result($resSubmit, $i, serie);
                    $res_nota_fiscal = pg_fetch_result($resSubmit, $i, nota_fiscal);
                    $res_data_abertura = pg_fetch_result($resSubmit, $i, data_abertura);
                    $res_consumidor_revenda = pg_fetch_result($resSubmit, $i, consumidor_revenda);
                    $res_revenda_nome = pg_fetch_result($resSubmit, $i, revenda_nome);
                    $res_consumidor_nome = pg_fetch_result($resSubmit, $i, consumidor_nome);
                    $res_produto = pg_fetch_result($resSubmit, $i, produto);
                    $res_posto = pg_fetch_result($resSubmit, $i, posto); ?>
                    <tr>
                        <td><a href="cadastro_os.php?preos=<?= $res_hd_chamado; ?>" target="blank"><?= $res_hd_chamado; ?></a></td>
                        <td><?= $res_data_abertura; ?></td>
                        <td><?= $res_serie; ?></td>
                        <td><?= $res_nota_fiscal; ?></td>
                        <td><?= $res_consumidor_revenda; ?></td>
                        <td><?= $res_revenda_nome; ?></td>
                        <td><?= $res_consumidor_nome; ?></td>
                        <td><?= $res_produto; ?></td>
                        <td><?= $res_posto; ?></td>
                    </tr>
                <? } ?>
            </tbody>
        </table>
    <? } else { ?>
        <div class="container">
            <div class="alert">
                <h4>Nenhum resultado encontrado</h4>
            </div>
        </div>
    <? }
}
include "rodape.php"; ?>