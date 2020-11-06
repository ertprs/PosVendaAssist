<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/AuditorLog.php";

if (isset($_POST['tipo_data'])) {

    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $codigo_posto       = $_POST['codigo_posto'];
    $descricao_posto    = $_POST['descricao_posto'];
    $tipo_data          = $_POST['tipo_data'];
    $numero_serie       = $_POST['numero_serie'];

    if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
        $sql = "SELECT produto
                FROM tbl_produto
                WHERE fabrica_i = {$login_fabrica}
                AND (
                    (UPPER(referencia) = UPPER('{$produto_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$produto_descricao}'))
                )
                ";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Produto não encontrado";
            $msg_erro["campos"][] = "produto";
        } else {
            $produto = pg_fetch_result($res, 0, "produto");
            $cond[] = "tbl_os.produto = {$produto} ";
        }
    }

    if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
        $sql = "SELECT tbl_posto_fabrica.posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND (
                    (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
                    OR
                    (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
                )";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Posto não encontrado";
            $msg_erro["campos"][] = "posto";
        } else {
            $posto = pg_fetch_result($res, 0, "posto");
            $cond[] = "tbl_os.posto = {$posto} ";
        }

    }

    if (empty($numero_serie)) {
        if (!strlen($data_inicial) or !strlen($data_final)) {
            $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
            $msg_erro["campos"][] = "data";
        } else {
            list($di, $mi, $yi) = explode("/", $data_inicial);
            list($df, $mf, $yf) = explode("/", $data_final);

            if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
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
    } else {
        $cond[] = "tbl_os.serie = '{$numero_serie}'";
    }

    if (count($msg_erro) == 0) {

        if (empty($numero_serie)) {
            if ($tipo_data == "abertura") {
                $cond[] = "tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}' ";
            } else if ($tipo_data == "fechamento") {
                $cond[] = "tbl_os.data_fechamento BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}' ";
            } else {
                $cond[] = "tbl_os_troca.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
            }
        }

        $sqlPesquisa = "SELECT tbl_numero_serie.serie,
                               TO_CHAR(tbl_os.data_abertura, 'dd/mm/yyyy') as data_abertura,
                               TO_CHAR(tbl_os.data_fechamento, 'dd/mm/yyyy') as data_fechamento,
                               TO_CHAR(tbl_os_troca.data, 'dd/mm/yyyy') as data_troca,
                               tbl_produto.referencia || ' - ' || tbl_produto.descricao as nome_produto,
                               tbl_posto.nome as nome_posto,
                               tbl_os.os
                        FROM tbl_os
                        JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
                        AND tbl_os_troca.fabric = {$login_fabrica}
                        JOIN tbl_numero_serie ON tbl_numero_serie.serie = tbl_os.serie
                        AND tbl_numero_serie.fabrica = {$login_fabrica}
                        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                        JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                        WHERE tbl_os.fabrica = {$login_fabrica}
                        AND ".implode(" AND ", $cond);
        $resPesquisa = pg_query($con, $sqlPesquisa);

        if ($_POST["gerar_excel"]) {

            $data = date("d-m-Y-H:i");

            $fileName = "relatorio-mo-categoria-{$data}.xls";

            $file = fopen("/tmp/{$fileName}", "w");
            $thead = "
                <table border='1'>
                    <thead>
                        <tr>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Série</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Abertura</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Fechamento</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Troca</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Produto</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Posto</th>
                        </tr>
                    </thead>
                    <tbody>";
            fwrite($file, $thead);


            $body = "";
            while ($dados = pg_fetch_object($resPesquisa)) {

                $body .= "<tr>
                            <td nowrap align='center' valign='top'>{$dados->serie}</td>
                            <td nowrap align='center' valign='top'>{$dados->os}</td>
                            <td nowrap align='center' valign='top'>{$dados->data_abertura}</td>
                            <td nowrap align='center' valign='top'>{$dados->data_fechamento}</td>
                            <td nowrap align='center' valign='top'>{$dados->data_troca}</td>
                            <td nowrap align='center' valign='top'>{$dados->nome_produto}</td>
                            <td nowrap align='center' valign='top'>{$dados->nome_posto}</td>
                        </tr>";

            }

            fwrite($file, $body);

            fclose($file);

            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }

            exit;
        }
    }
}

$layout_menu = "cadastro";
$title = "Relatorio Blacklist Série";

include "cabecalho_new.php";

$plugins = array(
   "select2",
   "shadowbox",
   "dataTable",
   "mask",
   "datepicker",
   "multiselect",
   "price_format"
);

include "plugin_loader.php";
?>
<script>

     $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
    });

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

</script>
<?php

if (!empty($msg_success)) { ?>
    <div class="alert alert-success">
        <h4><?= $msg_success ?></h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-danger">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Parâmetros de Cadastro</div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>

        <div class='span3'>
             <label class="radio">
                <input type="radio" name="tipo_data" value="abertura" checked>
                Data Abertura
            </label>
        </div>
        <div class='span3'>
            <label class="radio">
                <input type="radio" name="tipo_data" value="fechamento" <?php if($tipo_data == "fechamento") echo "checked"; ?> >
                Data Fechamento
            </label>
        </div>
        <div class='span3'>
            <label class="radio">
                <input type="radio" name="tipo_data" value="troca" <?php if($tipo_data == "troca") echo "checked"; ?> >
                Data Troca
            </label>
        </div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Série</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="numero_serie" id="numero_serie" class='span12' value="<?= $numero_serie ?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <br />
    <div class="row-fluid">
        <p class="tac">
            <button class='btn' id="btn_acao">Pesquisar</button>
        </p>
    </div>
</form>
<?php
if (pg_num_rows($resPesquisa) > 0) { ?>
   <table class="table table-bordered table-hover table-fixed tbl_pesquisa">
        <thead>
            <tr class="titulo_coluna">
                <th >Série</th>
                <th >OS</th>
                <th >Data Abertura</th>
                <th>Data Fechamento</th>
                <th>Data Troca</th>
                <th>Nome Produto</th>
                <th>Nome Posto</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($dados = pg_fetch_object($resPesquisa)) {
            ?>
                <tr>
                    <td class="tac"><?= $dados->serie ?></td>
                    <td class="tac">
                        <a href="os_press.php?os=<?= $dados->os ?>" target="_blank"><?= $dados->os ?></a>
                    </td>
                    <td class="tac"><?= $dados->data_abertura ?></td>
                    <td class="tac"><?= $dados->data_fechamento ?></td>
                    <td class="tac"><?= $dados->data_troca ?></td>
                    <td class="tac"><?= $dados->nome_produto ?></td>
                    <td class="tac"><?= $dados->nome_posto ?></td>
                </tr>
            <?php
            } ?>
        </tbody>
    </table>
    <br />
    <?php
        $jsonPOST = excelPostToJson($_POST);
    ?>
    <div id='gerar_excel' class="btn_excel">
        <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Gerar Arquivo Excel</span>
    </div>
    <br />
    <script>
        $.dataTableLoad({
            table: ".tbl_pesquisa"
        });
    </script>
<?php
} else { ?>
    <div class="alert alert-warning">
        <h4>Nenhum resultado encontrado</h4>
    </div>
<?php
}

include "rodape.php";
?>