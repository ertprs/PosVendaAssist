<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios = "cadastros";
include "autentica_admin.php";

if ($_REQUEST['gravar']) {
    $codigo_posto = $_REQUEST['codigo_posto'];
    $descricao_posto = $_REQUEST['descricao_posto'];

    $postos = $_REQUEST['postos'];
    unset($postos['__modelo__']);

    if (empty($codigo_posto) || empty($descricao_posto)) {
        $msg_erro["msg"]["campo_obrigatorio"] = traduz("Preencha todos os campos obrigatórios");
        $msg_erro["campos"][] = "posto";
    } else {
        $sql = "
            SELECT p.posto
            FROM tbl_posto_fabrica pf
            JOIN tbl_posto p USING(posto)
            WHERE pf.fabrica = {$login_fabrica}
            AND pf.codigo_posto = '{$codigo_posto}'
            AND fn_retira_especiais(p.nome) = '{$descricao_posto}';
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            $msg_erro["msg"][] = traduz("Posto não encontrado #001");
            $msg_erro["campos"][] = "posto";
        }

        if (pg_num_rows($res) > 0) {
            $posto_id = pg_fetch_result($res, 0, "posto");
        }
    }

    if (empty($posto_id) && count($msg_erro['msg']) == 0) {
        $msg_erro["msg"][] = traduz("Posto não encontrado #002");
        $msg_erro["campos"][] = "posto";
    }

    $nenhumaFilial = true;
    foreach ($postos as $key => $posto) {
        if (!empty($posto['id'])) {
            $nenhumaFilial = false;
        }
    }

    if ($nenhumaFilial === true && count($msg_erro['msg']) == 0) {
        $msg_erro["msg"][] = traduz("Ao menos uma filial é necessária para o cadastro");
    }

    if (count($msg_erro['msg']) == 0) {
        try {
            pg_query($con,"BEGIN;");
            foreach ($postos as $key => $posto) {
                if (!empty($posto['id'])) {
                    $sql = "SELECT posto_filial FROM tbl_posto_filial WHERE filial_posto = {$posto['id']} AND fabrica = {$login_fabrica};";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Ocorreu um erro gravando os dados #001");
                    }

                    if (pg_num_rows($res) > 0) {
                        throw new Exception("O Posto {$posto['codigo_posto']} - {$posto['descricao_posto']} já está cadastrado para uma Matriz");
                    }

                    if (!empty($posto['id'])) {
                        $ist = "INSERT INTO tbl_posto_filial (fabrica,posto,filial_posto) VALUES ({$login_fabrica},{$posto_id},{$posto['id']});";
                        pg_query($con, $ist);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Ocorreu um erro gravando os dados #002");
                        }
                    }
                }
            }

            $msg_sucesso = "Filial(is) cadastrada(s) com sucesso";
            unset($_RESULT, $_POST);
            pg_query($con,"COMMIT;");
        } catch(Exception $e) {
            $msg_erro['msg'][] = $e->getMessage();
            pg_query($con,"ROLLBACK;");
        }
    }
}

if (isset($_REQUEST['ajax_delete_filial'])) {
    $posto_filial = $_REQUEST['posto_filial'];

    if (!empty($posto_filial)) {
        $del = "DELETE FROM tbl_posto_filial WHERE posto_filial = {$posto_filial} AND fabrica = {$login_fabrica};";
        pg_query($con, $del);

        if((pg_last_error()) > 0) {
            $retorno = array("error" => utf8_encode("Ocorreu um erro excluindo a filial"));
        } else {
            $retorno = array("success" => utf8_encode("Filial excluida com sucesso"));
        }
    } else {
        $retorno = array("error" => utf8_encode("Filial não informada para efetuar a exclusão"));
    }

     exit(json_encode($retorno));
}

$layout_menu = "cadastro";
$title = "CADASTRO DE FILIAIS POR MATRIZ";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "maskedinput",
    "alphanumeric"
);

include "plugin_loader.php"; ?>

<style type="text/css">
#modelo_posto { display:none; }
</style>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error"><h4><?= implode("<br />", $msg_erro["msg"]); ?></h4></div>
<? } else {
    if (!empty($msg_sucesso)) { ?>
        <div class="alert"><h4><?= $msg_sucesso; ?></h4></div>
    <? } else { ?>
        <br />
    <? } 
} ?>

<form class='form-search form-inline tc_formulario' name='frm_cadastro' method='post'>
	<div class='titulo_tabela'>Selecione uma Matriz <small>(retorna somente postos marcados como matriz)</small></div>
	<br />
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span4" >
            <div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="codigo_posto">Código Posto</label>
                <div class="controls controls-row" >
                    <div class="span7 input-append" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="codigo_posto" id="codigo_posto" class="span12" value="<?= getValue('codigo_posto'); ?>" />
                        <span class="add-on" rel="lupa_posto" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" matriz="t" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="descricao_posto">Nome Posto</label>

                <div class="controls controls-row" >
                    <div class="span12 input-append" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="descricao_posto" id="descricao_posto" class="span12" value="<?= getValue('descricao_posto'); ?>" />
                        <span class="add-on" rel="lupa_posto" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" matriz="t" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <br />
    <div class='titulo_tabela'>Adicione suas filiais</div>
    <?
    $postosAdicionados = getValue('postos');
    unset($postosAdicionados['__modelo__']);

    $linhasPostos = count($postosAdicionados);

    if ($linhasPostos == 0) {
        $linhasPostos = 1;
    } ?>
    <br />
    <div id="modelo_posto">
        <div class="row-fluid" name="posto___modelo__">
            <div class="span2">
                <div class='control-group'>
                    <br />
                    <div class="controls controls-row">
                        <div class="span12 tac">
                            <input type="hidden" name="postos[__modelo__][id]" rel="posto_id" value="" />
                            <button type="button" class="btn btn-mini btn-danger" name="remove_posto" rel="__modelo__" style="display:none;" >X</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="codigo_posto">Código Posto</label>
                    <div class="controls controls-row" >
                        <div class="span7 input-append" >
                            <input type="text" name="postos[__modelo__][codigo_posto]" class="span12" />
                            <span class="add-on" rel="lupa_posto" ><i class="icon-search" ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" matriz="f" posicao="__modelo__" parametro="codigo" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for="descricao_posto">Nome Posto</label>
                    <div class="controls controls-row" >
                        <div class="span12 input-append" >
                            <input type="text" name="postos[__modelo__][descricao_posto]" class="span12" />
                            <span class="add-on" rel="lupa_posto" ><i class="icon-search" ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" matriz="f" posicao="__modelo__" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="linhasInputPostos">
        <? for ($i = 0; $i < $linhasPostos; $i++) {
            $hideLupa = '';
            $readonlyInput = '';
            $hideBtnRemoverPst = 'style="display:none;"';
            if (!empty($postosAdicionados[$i]['id'])) {
                $hideLupa = 'style="display:none;"';
                $readonlyInput = 'readonly="readonly"';
                $hideBtnRemoverPst = '';
            } ?>
            <div class="row-fluid" name="posto_<?= $i; ?>">
                <div class="span2">
                    <div class='control-group'>
                        <br />
                        <div class="controls controls-row">
                            <div class="span12 tac">
                                <input type="hidden" name="postos[<?= $i; ?>][id]" rel="posto_id" value="<?= $postosAdicionados[$i]['id'] ?>" />
                                <button type="button" class="btn btn-mini btn-danger" name="remove_posto" rel="<?= $i; ?>" <?= $hideBtnRemoverPst; ?>>X</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="codigo_posto">Código Posto</label>
                        <div class="controls controls-row" >
                            <div class="span7 input-append" >
                                <input type="text" name="postos[<?= $i; ?>][codigo_posto]" class="span12" value="<?= $postosAdicionados[$i]['codigo_posto'] ?>" <?= $readonlyInput; ?> />
                                <span class="add-on" rel="lupa_posto" <?= $hideLupa; ?> ><i class="icon-search"></i></span>
                                <input type="hidden" name="lupa_config" tipo="posto" matriz="f" posicao="<?= $i; ?>" parametro="codigo" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="descricao_posto">Nome Posto</label>
                        <div class="controls controls-row" >
                            <div class="span12 input-append" >
                                <input type="text" name="postos[<?= $i; ?>][descricao_posto]" class="span12" value="<?= $postosAdicionados[$i]['descricao_posto'] ?>" <?= $readonlyInput; ?> />
                                <span class="add-on" rel="lupa_posto" <?= $hideLupa; ?> ><i class="icon-search" ></i></span>
                                <input type="hidden" name="lupa_config" tipo="posto" matriz="f" posicao="<?= $i; ?>" parametro="nome" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
        <? } ?>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span8 tar">
            <button type="button" class="btn btn-small btn-primary" name="adicionar_linha">Adicionar</button>
        </div>
        <div class="span2"></div>
    </div>
    <div class="row tac">
        <input type='hidden' name="gravar" />
        <input class="btn" type='button' id="posto_filial_form_submit" value="Gravar" data-submit="">
    </div>
	<br />
</form>
<?php
$sql = "
    SELECT
        pfl.posto_filial,
        p_mtz.posto AS posto_id_matriz,
        pf_mtz.codigo_posto AS posto_codigo_matriz,
        fn_retira_especiais(p_mtz.nome) AS posto_descricao_matriz,
        p_pfl.posto AS posto_id_filial,
        pf_pfl.codigo_posto AS posto_codigo_filial,
        fn_retira_especiais(p_pfl.nome) AS posto_descricao_filial
    FROM tbl_posto_filial pfl
    JOIN tbl_posto_fabrica pf_mtz ON pf_mtz.posto = pfl.posto AND pf_mtz.fabrica = {$login_fabrica}
    JOIN tbl_posto p_mtz ON p_mtz.posto = pf_mtz.posto
    JOIN tbl_posto_fabrica pf_pfl ON pf_pfl.posto = pfl.filial_posto AND pf_pfl.fabrica = {$login_fabrica}
    JOIN tbl_posto p_pfl ON p_pfl.posto = pf_pfl.posto
    WHERE pfl.fabrica = {$login_fabrica}
    ORDER BY pf_mtz.codigo_posto, pf_pfl.codigo_posto DESC;
";

$res = pg_query($con, $sql);

$countFiliais = pg_num_rows($res);

if ($countFiliais > 0) { ?>
    <table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-large'>
        <thead>
            <tr class='titulo_coluna'>
                <th class="tac">Matriz</th>
                <th class="tac">Filial</th>
                <th class="tac">Ações</th>
            </tr>
        </thead>
        <tbody>
            <? for($i = 0; $i < $countFiliais; $i++) {
                $res_postoFilial = pg_fetch_result($res, $i, "posto_filial");
                $res_postoIdMatriz = pg_fetch_result($res, $i, "posto_id_matriz");
                $res_postoCodigoMatriz = pg_fetch_result($res, $i, "posto_codigo_matriz");
                $res_postoDescricaoMatriz = pg_fetch_result($res, $i, "posto_descricao_matriz");
                $res_postoIdFilial = pg_fetch_result($res, $i, "posto_id_filial");
                $res_postoCodigoFilial = pg_fetch_result($res, $i, "posto_codigo_filial");
                $res_postoDescricaoFilial = pg_fetch_result($res, $i, "posto_descricao_filial"); ?>
                <tr id="posto_filial_<?= $res_postoFilial; ?>">
                    <td class="tac"><?= $res_postoCodigoMatriz." - ".$res_postoDescricaoMatriz; ?></td>
                    <td class="tac"><?= $res_postoCodigoFilial." - ".$res_postoDescricaoFilial; ?></td>
                    <td class="tac"><button type="button" class="btn btn-small btn-danger" name="excluir_filial" rel="<?= $res_postoFilial; ?>">Excluir</button></td>
                </tr>
            <? } ?>
        </tbody>        
    </table>
<? } ?>

<script type="text/javascript">

$(function(){

    /**
     * Inicia o shadowbox, obrigatório para a lupa funcionar
     */
    Shadowbox.init();

    $(document).on("click", "span[rel=lupa_posto]", function() {
        var parametros_lupa_posto = ["matriz", "posicao"];
        $.lupa($(this), parametros_lupa_posto);
    });

    /**
     * Evento que adiciona uma nova linha de posto
     */
    $("button[name=adicionar_linha]").click(function() {
        var nova_linha = $("#modelo_posto").clone();
        var posicao = $("div.row-fluid[name^=posto_][name!=posto___modelo__]").length;
        $("#linhasInputPostos").append($(nova_linha).html().replace(/__modelo__/g, posicao).replace(/disabled\=['"]disabled['"]/g, ""));
        $("div.row-fluid[name=posto_"+posicao+"]").find(".numeric").numeric();
    });

    $("#posto_filial_form_submit").on("click", function(e) {
        e.preventDefault();

        var submit = $(this).data("submit");
        if (submit.length == 0) {
            $(this).data({ submit: true });
            $("input[name=gravar]").val('Gravar');
            $(this).parents("form").submit();
        } else {
           alert("Não clique no botão voltar do navegador, utilize somente os botões da tela");
        }
    });

    $(document).on("click", "button[name=remove_posto]", function() {
        var posicao = $(this).attr("rel");

        $("input[name='postos["+posicao+"][id]']").val("");
        $("input[name='postos["+posicao+"][codigo_posto]']").val("").removeAttr("readonly");
        $("input[name='postos["+posicao+"][descricao_posto]']").val("").removeAttr("readonly");
        $("div[name=posto_"+posicao+"]").find("span[rel=lupa_posto]").show();

        $(this).hide();
    });

    $(document).on("click", "button[name=excluir_filial]", function() {
        var that = $(this);
        var posto_filial = $(this).attr("rel");

        $.ajax({
            url: "posto_cadastro_filial.php",
            type: "POST",
            data: { ajax_delete_filial: true, posto_filial: posto_filial },
            beforeSend: function() {
                if ($(that).next("img").length == 0) {
                    $(that).hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                }
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                    $(that).show().prop("disabled", false);
                } else {
                    alert(data.success);
                    $("#posto_filial_"+posto_filial).remove();
                }

                $(that).next().remove();

                if(!$('#resultado_pesquisa tbody tr').length){
                    $('#resultado_pesquisa').remove();
                }
            }
        });
    });
    
});

function retorna_posto(retorno){
    if (verifica_posto_lancado(retorno.posto)) {
        alert("Posto já lançado");
        return false;
    }

    if (typeof retorno.posicao != "undefined") {
        $("input[name='postos["+retorno.posicao+"][id]']").val(retorno.posto);
        $("input[name='postos["+retorno.posicao+"][codigo_posto]']").val(retorno.codigo).attr({ readonly: "readonly" });
        $("input[name='postos["+retorno.posicao+"][descricao_posto]']").val(retorno.nome).attr({ readonly: "readonly" });
        $("div[name=posto_"+retorno.posicao+"]").find("span[rel=lupa_posto]").hide();
        $("div[name=posto_"+retorno.posicao+"]").find("button[name=remove_posto]").show();
    } else {
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }
}

function verifica_posto_lancado(posto, elemento) {
    var retorno = false;

    if (elemento == undefined || elemento == null) {
        elemento = "input[rel=posto_id]";
    }

    $(elemento).each(function() {
        if (posto != undefined && posto != null) {
            if ($(this).val().length > 0 && $(this).val() == posto) {
                retorno = true;
            }
        }
    });

    return retorno;
}

</script>

<? include "rodape.php"; ?>