<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'includes/funcoes.php';

$admin_privilegios = "cadastros";
include_once "autentica_admin.php";
include_once "../helpdesk.inc.php";
include_once '../helpdesk/mlg_funciones.php';

$usa_atendente_regiao = (in_array($login_fabrica, $fabrica_at_regiao) OR $helpdeskPostoAutorizado);

if ($_POST['buscaCidade'] == 'true') {
    $estado = strtoupper(getPost('estado'));
    $arrayCidades = array();

    if (strlen($estado) > 0) {
        $sql = "SELECT cod_ibge, cidade FROM tbl_ibge WHERE UPPER(estado) = '{$estado}'";
        $res = pg_query($con, $sql);
        $rows = pg_num_rows($res);

        if ($rows > 0) {
            for ($i = 0; $i < $rows; $i++) {
                $arrayCidades[] = array(
                    'cod_ibge' => pg_fetch_result($res, $i, 'cod_ibge'),
                    'cidade'   => utf8_encode(pg_fetch_result($res, $i, 'cidade'))
                );
            }
        }
    }
    die(json_encode($arrayCidades));
}

if ($_POST['apagarAtendente'] == 'true') {
    $admin_atendente_estado = $_POST['admin_atendente_estado'];

    if (strlen($admin_atendente_estado) > 0) {

        $sql = "SELECT tbl_admin_atendente_estado.admin, tbl_admin_atendente_estado.estado, upper( fn_retira_especiais(tbl_ibge.cidade) ) as cidade, tbl_admin.nome_completo
                FROM tbl_admin_atendente_estado
                LEFT JOIN tbl_ibge ON tbl_admin_atendente_estado.cod_ibge = tbl_ibge.cod_ibge
                INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
                WHERE admin_atendente_estado = {$admin_atendente_estado}
                and tbl_admin_atendente_estado.fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {

            $admin_a    = pg_fetch_result($res, 0, "admin");
            $estado   = strtoupper(pg_fetch_result($res, 0, "estado"));
            $cidade = pg_fetch_result($res, 0, "cidade");
            $tp_sol_a = pg_fetch_result($res, 0, "tipo_solicitacao");
            $nome_completo_anterior = pg_fetch_result($res, 0, "nome_completo");

            pg_query($con,'BEGIN');

            $sql = "DELETE FROM tbl_admin_atendente_estado
                    WHERE fabrica = {$login_fabrica}
                    AND admin_atendente_estado = {$admin_atendente_estado}";
            $res = pg_query($con, $sql);

            if ($login_fabrica == 1) {

                if (strlen($estado)> 0) {
                    $cond_uf = "AND tbl_posto_fabrica.contato_estado = '$estado'";
                }
                if (strlen($cidade) > 0) {
                    $cond_cidade = "AND tbl_posto_fabrica.contato_cidade = '$cidade'";
                }
                if (strlen($tp_sol_a) > 0) {
                    $cond_tps = "AND tbl_hd_chamado.tipo_solicitacao = $tp_sol_a";
                }

                $sql_d = "SELECT DISTINCT tbl_hd_chamado.categoria,
                                          tbl_hd_chamado.posto,
                                          tbl_hd_chamado.hd_chamado,
                                          tbl_hd_chamado.status
                            FROM tbl_hd_chamado
                            JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                            WHERE tbl_hd_chamado.fabrica = $login_fabrica
                                AND tbl_hd_chamado.atendente = $admin_a
                                $cond_uf
                                $cond_cidade
                                $cond_tps
                                AND status not in ('Resolvido','Resolvido Posto','Cancelado');";
                $res_d = pg_query($con,$sql_d);

                if (pg_num_rows($res_d) > 0) {

                    for ($i=0; $i < pg_num_rows($res_d) ; $i++) {
                        $posto_u = pg_fetch_result($res_d, $i, posto);
                        $categoria_u = pg_fetch_result($res_d, $i, categoria);
                        $hd_chamado_u = pg_fetch_result($res_d, $i, hd_chamado);
                        $status_u = pg_fetch_result($res_d, $i, status);

                        $atendente_u = $categorias[$categoria_u]['atendente'];
                        $atendente_u = (is_numeric($atendente_u)) ? $atendente_u : hdBuscarAtendentePorPosto($posto_u,$categoria_u);

                        if ($admin_a != $atendente_u) {

                            $sql_nome_novo_atendente = "select nome_completo from tbl_admin where admin = $atendente_u";
                            $res_nome_novo_atendente = pg_query($con, $sql_nome_novo_atendente);
                            if(pg_num_rows($res_nome_novo_atendente) > 0 ){
                                $nome_completo_novo = pg_fetch_result($res_nome_novo_atendente, 0, 'nome_completo');
                            }

                            $sql_u = "UPDATE tbl_hd_chamado SET
                                    atendente = {$atendente_u}
                                    WHERE atendente = {$admin_a}
                                        AND fabrica = {$login_fabrica}
                                        AND posto = {$posto_u}
                                        AND hd_chamado = {$hd_chamado_u}
                                        AND status not in ('Resolvido','Resolvido Posto','Cancelado');";
                            $res_u = pg_query($con,$sql_u);

                            if(strlen(trim(pg_last_error($con)))==0){
                                $frase_transferencia = "Chamado transferido automaticamente: de ". $nome_completo_anterior ." para ". $nome_completo_novo ." <br>Atendente anterior excluído!";
                            }

                            $hd_chamado_item_u = hdCadastrarResposta($hd_chamado_u, $frase_transferencia,true, $status_u, $login_fabrica);
                        }
                    }
                }
            }

            if (strlen(pg_last_error()) > 0) {
                pg_query($con,'ROLLBACK');
            }else{
                pg_query($con,'COMMIT');
                exit;
            }
        }
    }
    echo 'erro';
    exit;
}

$msg_erro = array();

if ($_POST["btn_acao"] == "submit") {

    $admin_atendente_estado = getPost('admin_atendente_estado');
    $atendente              = getPost('atendente');
    $categoria              = getPost('categoria');
    $estado                 = strtoupper(getPost('estado'));
    $cidade                 = getPost('cidade') ? : 'null';
    $patams_filiais         = getPost('patams_filiais_makita');

    if ($login_fabrica == 1 AND empty($categoria) AND empty($admin_atendente_estado)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'categoria';
    }

        if (empty($categoria) && (!in_array($login_fabrica, array(1,30,151)) OR !$helpdeskPostoAutorizado )) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'categoria';
    }

    if (empty($atendente)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'atendente';
    }

    if ($categoria === 'patam_filiais_makita' AND empty($patams_filiais)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'patams_filiais_makita';
    }

    if (count($msg_erro) == 0) {

        pg_query($con, "BEGIN");

        $sqlUpdate = (!empty($admin_atendente_estado)) ? " AND admin_atendente_estado NOT IN ({$admin_atendente_estado}) " : "";

        if ($login_fabrica == 42) {
            $sqlWhere = " AND admin = {$atendente} ";

            if ($categoria === 'patam_filiais_makita') {
                $sqlWhere .= "
                       AND posto_filial = {$patams_filiais}";
            }

        }

        if ($usa_atendente_regiao AND (!in_array($login_fabrica, array(151)) OR !$helpdeskPostoAutorizado)) {
            if ($cidade != "null" && strlen($estado) > 0) {
                $sqlWhere = " AND (cod_ibge = {$cidade} AND UPPER(estado) = '{$estado}' AND categoria = '{$categoria}') ";
            } else if ($cidade == "null" && strlen($estado) > 0) {
                $sqlWhere = " AND (cod_ibge IS NULL AND UPPER(estado) = '{$estado}' AND categoria = '{$categoria}') ";
            } else {
                $sqlWhere = " AND (cod_ibge IS NULL AND (estado IS NULL OR LENGTH(estado) = 0) AND categoria = '{$categoria}') ";
            }
        }

        if (!in_array($login_fabrica, array(1, 30,151)) OR !$helpdeskPostoAutorizado) {
            $sql = "SELECT categoria
                    FROM tbl_admin_atendente_estado
                    WHERE categoria = '$categoria'
                    AND fabrica = $login_fabrica
                    $sqlUpdate
                    $sqlWhere";
            $res = pg_query($con, $sql);
        }

        if ((in_array($login_fabrica, array(1,30,151,153)) OR $helpdeskPostoAutorizado) || !pg_num_rows($res)) {
            if (in_array($login_fabrica, array(30,151,153)) OR $helpdeskPostoAutorizado ) {
                if (empty($categoria)) {
                    $categoria = "null";
                }

                $arrayColumns = array(
                    "admin"     => $atendente,
                    "tipo_solicitacao" => "{$categoria}",
                    "fabrica"   => $login_fabrica
                );
            } else {
                $arrayColumns = array(
                    "admin"     => $atendente,
                    "categoria" => "'{$categoria}'",
                    "fabrica"   => $login_fabrica
                );
            }
            if ($usa_atendente_regiao) {
                $arrayColumns['estado'] = "'$estado'";
                $arrayColumns['cod_ibge'] = $cidade;
                }
            if (!empty($patams_filiais)) {
                $arrayColumns['posto_filial'] = "'$patams_filiais'";
            }

            if (empty($admin_atendente_estado)) {
                $sql = "INSERT INTO tbl_admin_atendente_estado
                            (".implode(", ", array_keys($arrayColumns)).")
                        VALUES
                            (".implode(", ", $arrayColumns).")";
                $res = pg_query($con, $sql);
            } else {
                $columnsUpdate = array();

                foreach ($arrayColumns as $key => $value) {
                    if ($login_fabrica == 35) {
                        if (!in_array($key, array('estado', 'cod_ibge'))) {
                            $columnsUpdate[] = "{$key} = {$value}";
                        }
                    } else {
                        $columnsUpdate[] = "{$key} = {$value}";
                    }
                }

                $sql = "UPDATE tbl_admin_atendente_estado
                        SET ".implode(", ", $columnsUpdate)."
                        WHERE admin_atendente_estado = {$admin_atendente_estado}
                        AND fabrica = {$login_fabrica}";
                $res = pg_query($con, $sql);

                if ($login_fabrica == 1 AND strlen(pg_last_error()) == 0 ) {

                    $sql_at = " SELECT  admin,
                                        tbl_admin_atendente_estado.tipo_solicitacao,
                                        tbl_admin_atendente_estado.estado,
                                        upper( fn_retira_especiais(tbl_ibge.cidade) ) as cidade
                                FROM tbl_admin_atendente_estado
                                    LEFT JOIN tbl_ibge ON tbl_admin_atendente_estado.cod_ibge = tbl_ibge.cod_ibge
                                WHERE admin_atendente_estado = {$admin_atendente_estado}
                                AND fabrica = {$login_fabrica};";
                    $res_at = pg_query($con,$sql_at);

                    if (pg_num_rows($res_at) > 0) {
                        $atendente_atual = pg_fetch_result($res_at, 0, admin);
                        $estado_atual    = pg_fetch_result($res_at, 0, estado);
                        $cidade_atual    = pg_fetch_result($res_at, 0, cidade);
                        $tp_sol_a        = pg_fetch_result($res_at, 0, tipo_solicitacao);

                        if (strlen($estado)> 0) {
                            $cond_uf = "AND tbl_posto_fabrica.contato_estado = '$estado_atual'";
                        }
                        if (strlen($cidade) > 0) {
                            $cond_cidade = "AND tbl_posto_fabrica.contato_cidade = '$cidade_atual'";
                        }
                        if (strlen($tp_sol_a) > 0) {
                            $cond_tps = "AND tbl_hd_chamado.tipo_solicitacao = $tp_sol_a";
                        }

                        $sql_d = "SELECT DISTINCT tbl_hd_chamado.categoria,
                                                  tbl_hd_chamado.posto
                                    FROM tbl_hd_chamado
                                    JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                    WHERE tbl_hd_chamado.fabrica = $login_fabrica
                                        AND tbl_hd_chamado.atendente = $atendente_atual
                                        $cond_uf
                                        $cond_cidade
                                        $cond_tps
                                        AND status not in ('Resolvido','Resolvido Posto','Cancelado');";
                        $res_d = pg_query($con,$sql_d);

                        if (pg_num_rows($res_d) > 0) {
                            for ($i=0; $i < pg_num_rows($res_d) ; $i++) {
                                $posto_u = pg_fetch_result($res_d, $i, posto);
                                $categoria_u = pg_fetch_result($res_d, $i, categoria);

                                $atendente_u = $categorias[$categoria_u]['atendente'];
                                $atendente_u = (is_numeric($atendente_u)) ? $atendente_u : hdBuscarAtendentePorPosto($posto_u,$categoria_u);

                                if ($atendente_atual != $atendente_u) {

                                    $sql_u = "UPDATE tbl_hd_chamado SET
                                            atendente = {$atendente_u}
                                            WHERE atendente = {$atendente_atual}
                                                AND fabrica = {$login_fabrica}
                                                AND posto = {$posto_u}
                                                AND categoria = '{$categoria_u}'
                                                AND status not in ('Resolvido','Resolvido Posto','Cancelado');";
                                    $res_u = pg_query($con,$sql_u);
                                }
                            }
                        }
                    }

                    if (strlen(pg_last_error($con)) > 0) {
                        $msg_erro["msg"][] = "Erro ao Atualizar Atendente nos Chamados !";
                    }
                }
            }


            if (!pg_last_error()) {
                pg_query($con, "COMMIT");
                $msg_success = true;
                unset($_POST);
                unset($admin_atendente_estado);
            } else {
                $msg_erro["msg"][] = "Erro ao gravar atendente";
                pg_query($con, "ROLLBACK");
            }
        } else {
            if ($login_fabrica == 42) {
                $msg_erro["msg"][] = "Tipo de solicitação já cadastrada para este atendente";
            } else {
                $msg_erro["msg"][] = "Tipo de solicitação já cadastrada para outro atendente";
            }
        }
    }
}

if (!empty($_GET['admin_atendente_estado'])) {
    $_RESULT['admin_atendente_estado'] = $_GET['admin_atendente_estado'];

    $sql = "SELECT admin, categoria, estado, cod_ibge, posto_filial, tipo_solicitacao
            FROM tbl_admin_atendente_estado
            WHERE fabrica = {$login_fabrica}
            AND admin_atendente_estado = {$_RESULT['admin_atendente_estado']}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $_RESULT['atendente'] = pg_fetch_result($res, 0, 'admin');
        if (in_array($login_fabrica,array(30,151,153)) OR $helpdeskPostoAutorizado) {
            $_RESULT['categoria'] = pg_fetch_result($res, 0, 'tipo_solicitacao');
        } else {
            $_RESULT['categoria'] = pg_fetch_result($res, 0, 'categoria');
        }

        $_RESULT['estado']    = pg_fetch_result($res, 0, 'estado');
        $_RESULT['cidade']    = pg_fetch_result($res, 0, 'cod_ibge');
        $_RESULT['patams_filiais_makita'] = pg_fetch_result($res, 0, 'posto_filial');
    }
}

$layout_menu = "cadastro";
$title = 'ATENDENTE MANUTENÇÃO';

$title_page  = 'Cadastro';
if ($_GET['admin_atendente_estado']) {
    $title_page = 'Alteração de Cadastro';
}

include 'cabecalho_new.php';

?>

<script>
    $(function () {
        var admin_atendente_uf = $("#admin_atendente_estado").val();
        if (admin_atendente_uf.length > 0 ) {
            //$("#cidade").selectreadonly(true);
            $("#cidade").attr({"disabled" : true});
            //$("#estado").selectreadonly(true);
            $("#estado").attr({"disabled" : true});
        }
        var cod_ibge = "<?=getValue('cidade')?>";
        $("select[name=estado]").change(function () {
            $("select[name=cidade]").find("option[rel!=default]").remove();

            if ($(this).val().length > 0) {
                if (ajaxAction()) {
                    $.ajax({
                        url: window.location.pathname,
                        type: 'POST',
                        data: { buscaCidade: true, estado: $(this).val() },
                        beforeSend: function () {
                            loading('show');
                        },
                        complete: function (data) {
                            data = data.responseText;

                            if (data.length > 0) {
                                data = $.parseJSON(data);

                                $.each(data, function (key, value) {
                                    var option = $("<option></option>");
                                    option.val(value.cod_ibge);
                                    option.text(value.cidade);

                                    if (value.cod_ibge == cod_ibge) {
                                        option.attr({ "selected": "selected" });
                                    }

                                    $("select[name=cidade]").append(option);
                                });
                            }
                            loading("hide");
                        }
                    });
                }
            }
        });

        $("select[name=estado]").change();

        $("button[name=apagar]").click(function () {
            var tr                     = $(this).parents("tr");
            var admin_atendente_estado = $(this).parent("td").find("input[name=admin_atendente_estado_resultado]").val();

			if (admin_atendente_estado.length > 0) {
                if (ajaxAction()) {
                    $.ajax({
                        url: "atendente_solicitacao_cadastro.php",
                        type: "POST",
                        data: { apagarAtendente: true, admin_atendente_estado: admin_atendente_estado },
                        beforeSend: function () {
                            loading("show");
                        },
                        complete: function (data) {
                            if (data.responseText == "erro") {
                                alert("Erro ao deletar o atendente");
                            } else {
                                $(tr).remove();
                                alert("Atendente apagado com sucesso");
                            }

                            loading("hide");
                        }
                    });
                }
            }
        });

        $('#categoria').change(function () {
			var fabrica = <?php echo $login_fabrica; ?>;
            var status;
            var novo_valor = $(this).val();//alert(novo_valor);

            if (fabrica == 42) {
                if(novo_valor == 'patam_filiais_makita'){
                    $('#patam_filiais_makita').show();
                }else{
                    $('#patam_filiais_makita').hide();
                }
            } else {
                $('#patam_filiais_makita').hide();
            }
            status = (novo_valor == 'patam_filiais_makita') ? 'block'   : 'none';
            $('#patam_filiais_makita').css('display',status);

        });
    });
</script>

<?php
if ($msg_success) {
?>
    <div class="alert alert-success">
        <h4>Atendente gravado com sucesso</h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_atendente_manutencao' METHOD='POST' ACTION='<?=$PHP_SELF?>' class='form-search form-inline tc_formulario'>
    <input type="hidden" name="admin_atendente_estado" id="admin_atendente_estado" value="<?=getValue('admin_atendente_estado')?>" />

    <div class='titulo_tabela '><?=$title_page?></div>

    <br />

    <div class='row-fluid'>
        <?php
        $spanAdmin  = $usa_atendente_regiao ? 'offset1 span2' : 'offset2 span3';
        $spanTipo   = $usa_atendente_regiao ? 'span3' : 'span4';
        ?>

        <div class='<?=$spanAdmin?>'>
            <div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='atendente'>Atendente</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <?php
                            $cond = (in_array($login_fabrica, [151])) ? " AND callcenter_supervisor IS TRUE " : " AND admin_sap IS TRUE ";
                            $cond = ($login_fabrica == 163) ? " AND responsavel_postos IS TRUE " : $cond;
                            $sql = "SELECT admin, nome_completo
                                    FROM tbl_admin
                                    WHERE fabrica = {$login_fabrica}
									AND ativo
                                    $cond
                                    ORDER BY login";
                            echo array2select('atendente', 'att', pg_fetch_pairs($con, $sql), getValue('atendente'), ' class="span12"', ' ', true);
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <?
        if ($_POST['patams_filiais_makita'] === 'patam_filiais_makita' OR getValue('categoria') === 'patam_filiais_makita' ) {
            $dplay = 'block';
        }else{
            $dplay = 'none';
        }
        ?>
            <div class='<?=$spanTipo?>'>
                <div class='control-group <?=in_array('categoria', $msg_erro['campos']) ? 'error' : ''?>'>
                    <label class='control-label' for='tipo_solicitacao'>Tipo Solicitação</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <?php
                            if (in_array($login_fabrica,array(30,151,153)) OR $helpdeskPostoAutorizado) { ?>
                            <h5 class='asteristico'>*</h5>
                                <select name="categoria" class="span12" >
                                    <option value="" >Selecione</option>
                                    <?php
                                    $sqlTS = "SELECT tipo_solicitacao, descricao
                                              FROM tbl_tipo_solicitacao
                                              WHERE fabrica = {$login_fabrica}
                                              AND ativo IS TRUE
                                              ORDER BY descricao";
                                    $resTS = pg_query($con, $sqlTS);

                                    if (pg_num_rows($resTS)) {
                                        while ($tipo_solicitacao = pg_fetch_object($resTS)) {
                                            $selected = ($_RESULT['categoria'] == $tipo_solicitacao->tipo_solicitacao) ? "selected" : "";

                                            echo "<option value='{$tipo_solicitacao->tipo_solicitacao}' {$selected} >{$tipo_solicitacao->descricao}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <?php
                                } else {
                                    echo "<h5 class='asteristico'>*</h5>";
                                    foreach($categorias as $cod=>$info) {
                                        if ($info['no_fabrica']) {
                                            if (in_array($login_fabrica, $info['no_fabrica'])) {
                                                continue;
                                            }
                                        }
                                        $lista_categorias[$cod] = $info['descricao'];
                                    }
                                        echo array2select('categoria', 'categoria', $lista_categorias, getValue('categoria'), "class='span12'", ' ', true);
                                }
                                ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        if ($usa_atendente_regiao) {
        ?>
            <div class='span2'>
                <div class='control-group <?=in_array('estado', $msg_erro['campos']) ? 'error' : ''?>'>
                    <label class='control-label' for='estado'>Estado</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                        <?=array2select('estado', 'estado', $estadosBrasil, getValue('estado'), 'class="span12"', 'Todos Estados', true);?>
                        </div>
                    </div>
                </div>
            </div>

            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label' for='cidade'>Cidade</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select id="cidade" name="cidade" class='span12' >
                                <option rel="default"></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
<?php
if ($login_fabrica == 42) { ?>
    <div class='row-fluid' style='position:relative;display:<?=$dplay?>' id='patam_filiais_makita'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array('patams_filiais_makita', $msg_erro['campos'])) ? 'error' : ''?>'>
                    <label class='control-label' for='patams_filiais_makita'>Informar a Filial</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                                <?php

                                $value_p = getValue('patams_filiais_makita');

                                $sql_f = "SELECT posto,nome_fantasia
                                            FROM tbl_posto_fabrica
                                            WHERE fabrica = $login_fabrica
                                            AND posto <> 6359
                                             AND filial IS TRUE ";
                                $res_f = pg_query($con,$sql_f);

                                echo array2select('patams_filiais_makita', 'patams_filiais_makita', $res_f, $value_p, 'class="span12"', '', true);
                                ?>
                        </div>
                    </div>
                </div>
            </div>
        <div class='span6'></div>
    </div>
<?php
}
?>

    <br />

    <p><br/>
        <button class="btn" id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
        <input type="hidden" id="btn_click" name="btn_acao" value="" />
        <?php
        if (strlen($_GET['admin_atendente_estado']) > 0) { ?>
            <button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
        <?php
        }
        ?>
    </p><br/>
</form>

<br />

<table id="atendente_cadastrados" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class="titulo_coluna" >
            <th>Atendente</th>
            <th>Tipo Solicitação</th>

            <?php
            if ($usa_atendente_regiao) {
            ?>
                <th>Estado</th>
                <th>Cidade</th>
            <?php
            }
            ?>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $cond = "";
        if ($login_fabrica == 151) {
            $cond = "AND admin_at.hd_classificacao IS NULL";
        }

		if(in_array($login_fabrica,array(30,151,153)) OR $helpdeskPostoAutorizado) {
                $cond = " AND (admin_at.tipo_solicitacao notnull)";
		}else{
                $cond = " AND (admin_at.categoria is not null OR admin_at.categoria <> '')";
		}
        $sql = "SELECT
                    admin_at.*,
                    tbl_ibge.cidade,
                    tbl_admin.nome_completo,
                    pf.nome_fantasia,
                    tbl_tipo_solicitacao.descricao AS tipo_solicitacao_descricao,
                    tbl_admin.nao_disponivel as disponibilidade
                FROM tbl_admin
                JOIN tbl_admin_atendente_estado AS admin_at USING(admin)
                LEFT JOIN tbl_ibge USING(cod_ibge)
                LEFT JOIN tbl_posto_fabrica AS pf ON pf.fabrica = tbl_admin.fabrica AND pf.posto = admin_at.posto_filial
                LEFT JOIN tbl_tipo_solicitacao ON tbl_tipo_solicitacao.tipo_solicitacao = admin_at.tipo_solicitacao AND tbl_tipo_solicitacao.fabrica = {$login_fabrica}
                WHERE tbl_admin.fabrica = {$login_fabrica}
				{$cond}
				order by tbl_admin.nome_completo";
        $res = pg_query($con, $sql);
        $rows = pg_num_rows($res);

        if ($rows > 0) {
            for ($i = 0; $i < $rows; $i++) {
                $admin_atendente_estado = pg_fetch_result($res, $i, "admin_atendente_estado");
                $login                  = strtoupper(pg_fetch_result($res, $i, "nome_completo"));
                $categoria              = $categorias[pg_fetch_result($res, $i, "categoria")]["descricao"];
                $tipo_solicitacao       = pg_fetch_result($res, $i, "tipo_solicitacao_descricao");
                $estado                 = $arrayEstados[pg_fetch_result($res, $i, "estado")];
                $cidade                 = pg_fetch_result($res, $i, "cidade");
                $posto_filial_t         = pg_fetch_result($res, $i, "posto_filial");
                $disponibilidade        = pg_fetch_result($res, $i, "disponibilidade");
				if(!empty($posto_filial_t)) {
					$sql_t = "SELECT nome_fantasia
						FROM tbl_posto_fabrica
						WHERE fabrica = $login_fabrica
						AND posto = $posto_filial_t; ";
					$res_t = pg_query($con,$sql_t);
					$patams_filiais_t = pg_fetch_result($res_t, 0, "nome_fantasia");
				}
				$ferias = null;
                if(!empty($disponibilidade)){
                    $ferias = "(indisponível)";
                }

                ?>

                <tr>
                    <td><a href="<?=$_SERVER['PHP_SELF']?>?admin_atendente_estado=<?=$admin_atendente_estado?>" ><?=$login?></a>  <?=$ferias?></td>
                    <?php
                    if ($login_fabrica == 42) {
                        if (count($patams_filiais_t) > 0) {
                        ?>
                            <td><?=$categoria.' - '.$patams_filiais_t?></td>
                        <?php
                        } else{
                        ?>
                            <td><?=$categoria?></td>
                        <?php
                        }
                                        } else if (in_array($login_fabrica,array(30,151,153)) OR $helpdeskPostoAutorizado) {
                        ?>
                            <td><?=$tipo_solicitacao?></td>
                        <?php
                    } else{
                    ?>
                        <td><?=$categoria?></td>
                    <?php
                    }

                    if ($login_fabrica == 1 OR $login_fabrica == 151 OR $helpdeskPostoAutorizado) {
                    ?>
                        <td><?=$estado?></td>
                        <td><?=$cidade?></td>
                    <?php
                    }
                    ?>
                    <td class="tac" >
                        <input type="hidden" name="admin_atendente_estado_resultado" value="<?=$admin_atendente_estado?>" />
                        <button type='button' name='apagar' class='btn btn-small btn-danger' title='Apagar o atendente' >Apagar</button>
                    </td>
                </tr>
            <?php
            }
        }
        ?>
    </tbody>
</table>

<?
include "rodape.php";
?>
