<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";

if ($_GET["ajax_bonificacao"]) {
    try {
        $posto = $_GET["posto"];

        if (empty($posto)) {
            throw new Exception("Erro ao carregar bonificações, Posto não informado");
        }

        $sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0) {
            throw new Exception("Erro ao carregar bonificações, Posto não encontrado");
        }

        $parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");

        if (strlen($parametros_adicionais) > 0) {
            $parametros_adicionais = json_decode($parametros_adicionais, true);
        } else {
            $parametros_adicionais = array();
        }

        if (!$parametros_adicionais["bonificacoes"]) {
            $parametros_adicionais["bonificacoes"] = array();
        }

        exit(json_encode(array("bonificacoes" => $parametros_adicionais["bonificacoes"])));
    } catch(Exception $e) {
        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_POST["acao"] == "gravar") {
    $msg_erro = array("msg" => array(), "campos" => array());

    $posto = $_POST["posto_id"];

    if (empty($posto)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "posto";
    } else {
        $bonificacoes = array();

        $b_range = array();

        foreach ($_POST["de"] as $i => $de) {
            $ate   = $_POST["ate"][$i];
            $valor = $_POST["valor"][$i];

            if (strlen($de) > 0 && strlen($ate) > 0 && !empty($valor)) {
                $bonificacoes[] = array(
                    "de"    => $de,
                    "ate"   => $ate,
                    "valor" => $valor
                );

                $b_range[] = range($de, $ate);
            }
        }

        if (count($b_range) > 0) {
            foreach ($b_range as $i => $range) {
                $erro      = false;
                $aux_range = $b_range;
                
                unset($aux_range[$i]);

                foreach ($aux_range as $arange) {
                    if (count(array_diff($range, $arange)) != count($range)) {
                        $erro = true;
                        break;
                    }
                }

                if ($erro) {
                    break;
                }
            }
        }

        if (!$erro) {
            $sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
            $res = pg_query($con, $sql);

            if (!pg_num_rows($res)) {
                $msg_erro["msg"]["obg"] = "Ocorreu um erro ao gravar as bonificações";
            } else {
                $parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");

                if (!empty($parametros_adicionais)) {
                    $parametros_adicionais = json_decode($parametros_adicionais, true);
                }

                $parametros_adicionais["bonificacoes"] = $bonificacoes;
                $parametros_adicionais = json_encode($parametros_adicionais);

                pg_query($con, "BEGIN");

                $upd = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '{$parametros_adicionais}' WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
                $res = pg_query($con, $upd);

                if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
                    $msg_erro["msg"]["obg"] = "Ocorreu um erro ao gravar as bonificações";
                    pg_query($con, "ROLLBACK");
                } else {
                    $sucesso = true;
                    pg_query($con, "COMMIT");
                }
            }
        } else {
            $msg_erro["msg"][] = "Não pode existir uma bonificação no mesmo intervalo de outra bonificação";
        }
    }
}

if ($_GET["posto"]) {
    $posto = filter_input(INPUT_GET, "posto");

    $sql = "
        SELECT pf.posto, pf.codigo_posto, p.nome, pf.parametros_adicionais 
        FROM tbl_posto_fabrica pf
        INNER JOIN tbl_posto p ON p.posto = pf.posto
        WHERE pf.fabrica = {$login_fabrica} 
        AND pf.posto = {$posto}
    ";
    $res = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
        $msg_erro["msg"][] = "Posto não encontrado";
    } else {
        $_RESULT = array(
            "posto_id"     => pg_fetch_result($res, 0, "posto"),
            "posto_nome"   => pg_fetch_result($res, 0, "nome"),
            "posto_codigo" => pg_fetch_result($res, 0, "codigo_posto"),
            "de"           => array(),
            "ate"          => array(),
            "valor"        => array()
        );

        $parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");

        if (strlen($parametros_adicionais) > 0) {
            $parametros_adicionais = json_decode($parametros_adicionais, true);

            foreach($parametros_adicionais["bonificacoes"] as $i => $b) {
                $_RESULT["de"][]    = $b["de"];
                $_RESULT["ate"][]   = $b["ate"];
                $_RESULT["valor"][] = $b["valor"];
            }
        }
    }
}

$layout_menu = "cadastro";
$title       = "CADASTRO DE BONIFICAÇÃO POR POSTO AUTORIZADO";

include "cabecalho_new.php";

$plugins = array(
   "shadowbox",
   "alphanumeric",
   "price_format",
   "dataTable"
);

include "plugin_loader.php";

?>

<style>

span.add-on {
    cursor: pointer;
}

div.bonificacao-alert {
    display: none;
}

</style>

<?php
if ($sucesso) {
?>
    <br />

    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4>Bonificação gravada com sucesso</h4>
    </div>

    <br />
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <br />

    <div class="alert alert-error">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>

    <br />
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario" action="cadastro_bonificacao_posto.php" >
    <div class="titulo_tabela" >Posto Autorizado</div>

    <br />

    <?php
    if (strlen(getValue("posto_id")) > 0) {
        $posto_input_readonly     = "readonly";
        $posto_span_rel           = "trocar_posto";
        $posto_input_append_icon  = "remove";
        $posto_input_append_title = "title='Trocar Posto'";
    } else {
        $posto_input_readonly     = "";
        $posto_span_rel           = "lupa";
        $posto_input_append_icon  = "search";
        $posto_input_append_title = "title='Buscar Posto'";
    }
    ?>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span3" >
            <div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="posto_codigo" >Código</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <h5 class='asteristico'>*</h5>
                        <input id="posto_codigo" name="posto_codigo" class="span12" type="text" value="<?=getValue('posto_codigo')?>" <?=$posto_input_readonly?> />
                        <span class="add-on" rel="<?=$posto_span_rel?>" >
                            <i class="icon-<?=$posto_input_append_icon?>" <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        <input type="hidden" id="posto_id" name="posto_id" value="<?=getValue('posto_id')?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="posto_nome" >Nome</label>
                <div class="controls controls-row" >
                    <div class="span10 input-append" >
                        <h5 class='asteristico'>*</h5>
                        <input id="posto_nome" name="posto_nome" class="span12" type="text" value="<?=getValue('posto_nome')?>" <?=$posto_input_readonly?> />
                        <span class="add-on" rel="<?=$posto_span_rel?>" >
                            <i class="icon-<?=$posto_input_append_icon?>" <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br />

    <div class="titulo_tabela" >Bonificações</div>

    <br />

    <div class="alert alert-info bonificacao-alert">
        Carregando bonificações aguarde...
    </div>

    <?php
    for ($i = 0; $i < 3; $i++) {
        unset($de, $ate, $valor);

        if ($_POST["acao"] == "gravar") {
            $de    = $_POST["de"][$i];
            $ate   = $_POST["ate"][$i];
            $valor = $_POST["valor"][$i];

            if (!strlen($de) || !strlen($ate) || empty($valor)) {
                unset($de, $ate, $valor);
            }
        } else if ($_GET["posto"]) {
            $de    = $_RESULT["de"][$i];
            $ate   = $_RESULT["ate"][$i];
            $valor = $_RESULT["valor"][$i];

            if (!strlen($de) || !strlen($ate) || empty($valor)) {
                unset($de, $ate, $valor);
            }
        }
        ?>
        <div class="row-fluid bonificacao" >
            <div class="span1" ></div>
            <div class="span2" >
                <div class="control-group" >
                    <label class="control-label" >De <span class="text-error">(número de dias)</span></label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input name="de[]" class="span12 numeric de" type="text" value="<?=$de?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2" >
                <div class="control-group" >
                    <label class="control-label" >Até <span class="text-error">(número de dias)</span></label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input name="ate[]" class="span12 numeric ate" type="text" value="<?=$ate?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3" >
                <div class="control-group" >
                    <label class="control-label" >Valor</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input name="valor[]" class="span12 price-format valor" type="text" value="<?=$valor?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
    ?>

    <br />

    <p>
        <button class="btn" type="submit" name="acao" value="gravar" >Gravar</button>
        <button class="btn btn-primary" type="submit" name="acao" value="listar" >Listar Postos</button>
    </p>

    <br />
</form>

<?php
if ($_POST["acao"] == "listar") {
    $sql = "
        SELECT pf.posto, p.nome, pf.codigo_posto, pf.parametros_adicionais 
        FROM tbl_posto_fabrica pf 
        INNER JOIN tbl_posto p ON p.posto = pf.posto 
        WHERE pf.fabrica = {$login_fabrica} 
        AND pf.credenciamento = 'CREDENCIADO' 
        ORDER BY p.nome ASC
    ";
    $res = pg_query($con, $sql);
    ?>
    <table id="resultado" class="table table-bordered table-hover table-striped" >
        <thead>
            <tr class="titulo_coluna" >
                <th colspan="4">Postos Autorizados</th>
            </tr>
            <tr class="titulo_coluna" >
                <th>Código</th>
                <th>Razão Social</th>
                <th>Bonificação</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($r = pg_fetch_object($res)) {
                $r->parametros_adicionais = json_decode($r->parametros_adicionais, true);
                ?>
                <tr>
                    <td><?=$r->codigo_posto?></td>
                    <td><?=$r->nome?></td>
                    <td nowrap >
                        <ul>
                            <?php
                            foreach ($r->parametros_adicionais["bonificacoes"] as $i => $b) {
                                echo "<li>De {$b['de']} Até {$b['ate']} - R$ ".number_format($b["valor"], 2, ",", ".")."</li>";
                            }
                            ?>
                        </ul>
                    </td>
                    <td class="text-center" >
                        <button type="button" class="btn btn-info btn-mini alterar" data-id="<?=$r->posto?>" >Alterar</button>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
<?php
}
?>

<script>
    
Shadowbox.init();

$("input.numeric").numeric();

$("input.price-format").priceFormat({
    prefix: "",
    thousandsSeparator: "",
    centsSeparator: ".",
    centsLimit: 2
});

$(document).on("click", "span[rel=lupa]", function() {
    $.lupa($(this));
});

function retorna_posto(retorno) {
    $("#posto_id").val(retorno.posto);
    $("#posto_codigo").val(retorno.codigo);
    $("#posto_nome").val(retorno.nome);

    $("#posto_codigo, #posto_nome")
    .prop({ readonly: true })
    .next("span[rel=lupa]")
    .attr({ rel: "trocar_posto" })
    .find("i")
    .removeClass("icon-search")
    .addClass("icon-remove")
    .attr({ title: "Trocar Posto" });

    $.ajax({
        async: true,
        url: window.location,
        type: "get",
        data: {
            ajax_bonificacao: true,
            posto: retorno.posto
        },
        timeout: 60000,
        beforeSend: function() {
            $("button[type=submit]").hide();
            $("div.bonificacao-alert").show();
        }
    }).fail(function(res) {
        alert("Erro ao carregar bonificações do posto autorizado, tempo limite esgotado");
        $("span[rel=trocar_posto]").trigger("click");
        $("button[type=submit]").show();
        $("div.bonificacao-alert").hide();
    }).done(function(res) {
        res = JSON.parse(res);

        if (res.erro) {
            alert(res.erro);
            $("span[rel=trocar_posto]").trigger("click");
        } else if (res.bonificacoes.length > 0) {
            var bonificacoes = $("div.bonificacao");

            res.bonificacoes.forEach(function(v, i) {
                var bonificacao = bonificacoes[i];

                var de    = $(bonificacao).find("input.de");
                var ate   = $(bonificacao).find("input.ate");
                var valor = $(bonificacao).find("input.valor");

                $(de).val(v["de"]);
                $(ate).val(v["ate"]);
                $(valor).val(parseFloat(v["valor"]).toFixed(2));
            });
        }

        $("button[type=submit]").show();
        $("div.bonificacao-alert").hide();
    });
}

$(document).on("click", "span[rel=trocar_posto]", function() {
    $("#posto_id, #posto_codigo, #posto_nome").val("");

    $("#posto_codigo, #posto_nome")
    .prop({ readonly: false })
    .next("span[rel=trocar_posto]")
    .attr({ rel: "lupa" })
    .find("i")
    .removeClass("icon-remove")
    .addClass("icon-search")
    .attr({ title: "Buscar Posto" });

    $("div.bonificacao input").val("");
});

if ($("#resultado").length > 0) {
    $.dataTableLoad({ table: "#resultado", type: "full" });

    $(document).on("click", "button.alterar", function() {
        var id = $(this).data("id");

        window.location = window.location+"?posto="+id;
    });
}

</script>

<?php

include "rodape.php";