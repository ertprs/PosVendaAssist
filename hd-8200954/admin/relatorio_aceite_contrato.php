<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $codigo_posto       = $_POST['codigo_posto'];
    $descricao_posto    = $_POST['descricao_posto'];
    $tipo_pesquisa          = $_POST['tipo_pesquisa'];

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
            $msg_erro["msg"][]    = traduz("Posto não encontrado");
            $msg_erro["campos"][] = "posto";
        } else {
            $posto = pg_fetch_result($res, 0, "posto");
        }
    }


    if (!strlen($data_inicial) or !strlen($data_final)) {
        $msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
        $msg_erro["campos"][] = "data";
    } else {
        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
            $msg_erro["msg"][]    = traduz("Data Inválida");
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_inicial = "{$yi}-{$mi}-{$di}";
            $aux_data_final   = "{$yf}-{$mf}-{$df}";

            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = traduz("Data Final não pode ser menor que a Data Inicial");
                $msg_erro["campos"][] = "data";
            }
        }
    }

    if (!count($msg_erro["msg"])) {

        switch ($tipo_pesquisa) {
            case 'aceito':
                $campo_tipo_pesquisa = " AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";
                $campos_tabela = 'true';
                break;
            case 'nao_aceito':
                $campo_tipo_pesquisa = " AND tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO' ";
                $campos_tabela = 'true';
                break;
            default:
                $campo_tipo_pesquisa = "UNION
                                            SELECT DISTINCT tbl_posto.nome AS nome_posto,
                                            tbl_posto.cidade AS cidade,
                                            tbl_posto.estado AS estado,
                                            tbl_posto_fabrica.credenciamento AS status_credenciamento,
                                            TO_CHAR(tbl_comunicado_posto_blackedecker.data_confirmacao, 'DD/MM/YYYY') AS data_confirmacao,
                                            tbl_posto_fabrica.contato_endereco
                                            FROM tbl_posto
                                            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                            LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_posto_fabrica.posto = tbl_comunicado_posto_blackedecker.posto AND tbl_comunicado_posto_blackedecker.fabrica = {$login_fabrica}
                                            WHERE tbl_comunicado_posto_blackedecker.posto IS NULL
                                        ";
                $cond_data = " AND tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL ";
                break;
        }


        if (!empty($posto)) {
            $cond_posto = " AND tbl_comunicado_posto_blackedecker.posto = {$posto} ";
        }else{
            $cond_posto = "";
        }

        $limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

        $sql = "SELECT DISTINCT tbl_posto.nome AS nome_posto,
                        tbl_posto.cidade AS cidade,
                        tbl_posto.estado AS estado,
                        tbl_posto_fabrica.credenciamento AS status_credenciamento,
                        TO_CHAR(tbl_comunicado_posto_blackedecker.data_confirmacao, 'DD/MM/YYYY') AS data_confirmacao,
                        tbl_comunicado_posto_blackedecker.leitor
                    FROM tbl_comunicado_posto_blackedecker
                    JOIN tbl_comunicado ON tbl_comunicado.comunicado  = tbl_comunicado_posto_blackedecker.comunicado
                                        AND tbl_comunicado.fabrica = {$login_fabrica}
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_comunicado_posto_blackedecker.posto
                                        AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                    JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                    WHERE tbl_comunicado_posto_blackedecker.fabrica = {$login_fabrica}
                    AND tbl_comunicado.tipo = 'Contrato'
                    AND tbl_comunicado.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
                    $cond_posto
                    $cond_data
                    $campo_tipo_pesquisa
                    $cond_posto
                    $cond_data
                ";
        $resSubmit = pg_query($con, $sql);
    }

    if ($_POST["gerar_excel"]) {
        if (pg_num_rows($resSubmit) > 0) {
            $data = date("d-m-Y-H:i");

            $fileName = "relatorio_aceite_contrato-{$data}.xls";

            $file = fopen("/tmp/{$fileName}", "w");
            $thead = "
                <table border='1'>
                    <thead>
                        <tr>
                            <th colspan='5' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                                RELATÓRIO ACEITE DO CONTRATO
                            </th>
                        </tr>
                        <tr>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>" . traduz("Status do Posto") . "</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>" . traduz("Nome Posto") . "</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>" . traduz("Cidade") . "</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>" . traduz("Estado") . "</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>" . traduz("Data da confirmação") . "</th>";
            if($campos_tabela == 'true'){
                $thead .= "
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>" . traduz("Nome") . "</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>" . traduz("CPF") . "</th>
                ";
            }

            $thead.="
                        </tr>
                    </thead>
                    <tbody>
            ";
            fwrite($file, $thead);

            for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
                $nome_posto             = pg_fetch_result($resSubmit, $i, 'nome_posto');
                $cidade                 = pg_fetch_result($resSubmit, $i, 'cidade');
                $estado                 = pg_fetch_result($resSubmit, $i, 'estado');
                $status_credenciamento  = pg_fetch_result($resSubmit, $i, 'status_credenciamento');
                $data_confirmacao       = pg_fetch_result($resSubmit, $i, 'data_confirmacao');
                $leitor                 = pg_fetch_result($resSubmit, $i, 'leitor');

                $leitor = json_decode($leitor, true);

                if(array_key_exists("nome_contrato",$leitor)){
                    $nome_contrato = $leitor['nome_contrato'];
                }

                if(array_key_exists("cpf_contrato",$leitor)){
                    $cpf_contrato = $leitor['cpf_contrato'];
                }

                $body .="
                    <tr>
                        <td nowrap align='center' valign='top'>{$status_credenciamento}</td>
                        <td nowrap align='center' valign='top'>{$nome_posto}</td>
                        <td nowrap align='center' valign='top'>{$cidade}</td>
                        <td nowrap align='center' valign='top'>{$estado}</td>
                        <td nowrap align='center' valign='top'>{$data_confirmacao}</td>
                    ";
                    if($campos_tabela == 'true'){
                        $body .= "
                        <td nowrap align='center' valign='top'>{$nome_contrato}</td>
                        <td nowrap align='center' valign='top'>{$cpf_contrato}</td>
                        ";
                    }

            }

            fwrite($file, $body);
            fwrite($file, "
                        <tr>
                            <th colspan='7' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >" . traduz("Total de").pg_num_rows($resSubmit). traduz("registros") . "</th>
                        </tr>
                    </tbody>
                </table>
            ");

            fclose($file);

            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }
        }

        exit;
    }
}

$layout_menu = "gerencia";
$title = traduz("RELATÓRIO Aceite do Contrato");
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "peca", "posto"));
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

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios"); ?></b>
</div>

<form name='frm_relatorio_aceite' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '><?php echo traduz("Parâmetros de Pesquisa"); ?></div>
        <br/>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'><?php echo traduz("Data Inicial"); ?></label>
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
                    <label class='control-label' for='data_final'><?php echo traduz("Data Final"); ?></label>
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
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto"); ?></label>
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
                    <label class='control-label' for='descricao_posto'><?php echo traduz("Nome Posto"); ?></label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
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

            <div class='span3'>
                 <label class="radio">
                    <input type="radio" name="tipo_pesquisa" value="todos" checked>
                    <?php echo traduz("Aguardando Resposta"); ?>
                </label>
            </div>
            <div class='span3'>
                <label class="radio">
                    <input type="radio" name="tipo_pesquisa" value="aceito" <?php if($tipo_pesquisa == "aceito") echo "checked"; ?> >
                    <?php echo traduz("Aceito"); ?>
                </label>
            </div>
            <div class='span2'>
                <label class="radio">
                    <input type="radio" name="tipo_pesquisa" value="nao_aceito" <?php if($tipo_pesquisa == "nao_aceito") echo "checked"; ?> >
                    <?php echo traduz("Não aceito"); ?>
                </label>
            </div>
            <div class='span2'></div>
        </div>

        <p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?php echo traduz("Pesquisar"); ?></button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
</form>
</div>

<?php
if (isset($resSubmit)) {
        if (pg_num_rows($resSubmit) > 0) {
            echo "<br />";

            if (pg_num_rows($resSubmit) > 500) {
                $count = 500;
                ?>
                <div id='registro_max'>
                    <h6><?php echo traduz("Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela."); ?></h6>
                </div>
            <?php
            } else {
                $count = pg_num_rows($resSubmit);
            }
        ?>
            <table id="resultado_aceite_contrato" class='table table-striped table-bordered table-hover table-fixed' >
                <thead>
                    <tr class='titulo_coluna' >
                        <th><?php echo traduz("Status do Posto"); ?></th>
                        <th><?php echo traduz("Nome Posto"); ?></th>
                        <th><?php echo traduz("Cidade"); ?></th>
                        <th><?php echo traduz("Estado"); ?></th>
                        <th><?php echo traduz("Data da confirmação"); ?></th>
                        <?php
                            if($campos_tabela == 'true'){
                                echo "
                                <th>" . traduz("Nome") . "</th>
                                <th>" . traduz("CPF") . "</th>
                                ";
                            }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for ($i = 0; $i < $count; $i++) {
                        $nome_posto             = pg_fetch_result($resSubmit, $i, 'nome_posto');
                        $cidade                 = pg_fetch_result($resSubmit, $i, 'cidade');
                        $estado                 = pg_fetch_result($resSubmit, $i, 'estado');
                        $status_credenciamento  = pg_fetch_result($resSubmit, $i, 'status_credenciamento');
                        $data_confirmacao       = pg_fetch_result($resSubmit, $i, 'data_confirmacao');
                        $leitor                 = pg_fetch_result($resSubmit,  $i, 'leitor');
                        $leitor = json_decode($leitor, true);

                        if(array_key_exists("nome_contrato",$leitor)){
                            $nome_contrato = $leitor['nome_contrato'];
                        }

                        if(array_key_exists("cpf_contrato",$leitor)){
                            $cpf_contrato = $leitor['cpf_contrato'];
                        }

                        $body = "<tr>
                                    <td class='tac' style='vertical-align: middle;'>{$status_credenciamento}</td>
                                    <td class='tac' style='vertical-align: middle;'>{$nome_posto}</td>
                                    <td class='tal' style='vertical-align: middle;'>{$cidade}</td>
                                    <td class='tac' style='vertical-align: middle;'>{$estado}</td>
                                    <td class='tac' style='vertical-align: middle;'>{$data_confirmacao}</td>
                                    ";
                        if($campos_tabela == 'true'){
                            $body .= "
                                        <td class='tac' style='vertical-align: middle;'>{$nome_contrato}</td>
                                        <td class='tac' style='vertical-align: middle;'>{$cpf_contrato}</td>
                                    ";
                        }
                        $body .='</tr>';
                        echo $body;
                    }
                    ?>
                </tbody>
            </table>

            <?php
            if ($count > 50) {
            ?>
                <script>
                    $.dataTableLoad({ table: "#resultado_aceite_contrato" });
                </script>
            <?php
            }
            ?>

            <br />

            <?php
                $jsonPOST = excelPostToJson($_POST);
            ?>

            <div id='gerar_excel' class="btn_excel">
                <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
                <span><img src='imagens/excel.png' /></span>
                <span class="txt"><?php echo traduz("Gerar Arquivo Excel"); ?></span>
            </div>
        <?php
        }else{
            echo '
            <div class="container">
            <div class="alert">
                    <h4>' . traduz("Nenhum resultado encontrado") . '</h4>
            </div>
            </div>';
        }
    }



include 'rodape.php';?>
