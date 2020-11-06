<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';
$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
$layout_menu = "tecnica";
$title = "TREINAMENTOS REALIZADOS";

if (!empty($_POST['ajax'])) {
    switch ($_POST['ajax']) {
        case 'loadCities':
            $estado = $_POST['estado'];

            $qCidades = "
                SELECT
                    cidade,
                    nome
                FROM tbl_cidade
                WHERE estado = '{$estado}'
                ORDER BY nome;
            ";
            $rCidades = pg_query($con, $qCidades);
            $cidades  = pg_fetch_all($rCidades);

            $cidades = array_map(function ($r) {
                $r['nome'] = utf8_encode(ucwords(strtolower($r['nome'])));
                return $r;
            }, $cidades);

            echo json_encode($cidades);
            break;
    }
    exit;
}

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "ajaxform",
    "dataTable",
    "multiselect",
    "font_awesome"
);

include "plugin_loader.php";
include "javascript_pesquisas.php";
?>

<div id="Alerta" class="alert" style="display: none;"><h4></h4></div>
<div id="alertaErro" class="alert alert-error" style="display: none;"><h4></h4></div>

<?php if (in_array($login_fabrica, [169, 170])) { ?>
<div class="row-fluid" style="margin-bottom:20px">
    <div class="span12" style="background-color:#596D9B;color:#FFF;text-align:center;">
        <h5>Parâmetros de Pesquisa</h5>
    </div>
    <form style="background-color:#D9E2EF;padding:60px 0 20px 0" id="form-parametros">
        <div class="row-fluid">
            <div class="span3"></div>
            <div class="span3 control-group">
                <label for="data-inicial" class="control-label"><?=traduz('Data Inicial')?>:</label>
                <div class="controls">
                    <div class="input-append">
                        <input name="data_inicial" id="data-inicial" type="text" class="span10" autocomplete="off">
                        <span class="add-on"><i style="margin:0 4px;" class="fas fa-calendar"></i></span>
                    </div>
                </div>
            </div>
            <div class="span3 control-group">
                <label for="data-final" class="control-label"><?=traduz('Data Final')?>:</label>
                <div class="controls">
                    <div class="input-append">
                        <input name="data_final" id="data-final" type="text" class="span10" autocomplete="off">
                        <span class="add-on"><i style="margin:0 4px;" class="fas fa-calendar"></i></span>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span3"></div>
            <div class="span6 control-group">
                <label for="titulo" class="control-label"><?=traduz('Título da Pesquisa')?>:</label>
                <input name="titulo" id="titulo" type="text" class="span12">
            </div>
        </div>
        <div class="row-fluid">
            <div class="span3"></div>
            <div class="span3 control-group">
                <label for="estado" class="control-label"><?=traduz('Estado')?>:</label>
                <?php
                    $qEstados = "
                        SELECT
                            estado,
                            nome
                        FROM tbl_estado
                        WHERE pais = 'BR'
                        AND estado NOT IN ('EX')
                        ORDER BY nome;
                    ";
                    $rEstados = pg_query($con, $qEstados);
                    $estados = pg_fetch_all($rEstados);
                ?>
                <select name="estado" id="estado" class="span12">
                    <option value=""><?=traduz('Selecione')?></option>
                    <?php foreach ($estados as $estado) { ?>
                    <option value="<?= $estado['estado'] ?>"><?= $estado['nome'] ?>
                    <?php } ?>
                </select>
            </div>
            <div class="span3 control-group">
                <label for="cidade" class="control-label"><?=traduz('Cidade')?>:</label>
                <select name="cidade" id="cidade" class="span12">
                    <option value=""><?=traduz('Selecione')?>...</option>
                </select>
            </div>
            <div class="span3"></div>
        </div>
        <div class="row-fluid">
            <div class="span3"></div>
            <div class="span3 control-group">
                <label for="instrutor" class="control-label"><?=traduz('Instrutor')?>:</label>
                <?php
                    $qInstrutor = "
                        SELECT
                            promotor_treinamento,
                            nome
                        FROM tbl_promotor_treinamento
                        WHERE fabrica = {$login_fabrica}
                        AND ativo IS TRUE
                        ORDER BY nome;
                    ";
                    $rInstrutor  = pg_query($con, $qInstrutor);
                    $instrutores = pg_fetch_all($rInstrutor);

                    $instrutores = array_map(function ($r) {
                        $r['nome'] = ucwords(strtolower($r['nome']));
                        return $r;
                    }, $instrutores);
                ?>
                <select name="instrutor" id="instrutor" class="span12">
                    <option value=""><?=traduz('Selecione')?>...</option>
                    <?php foreach ($instrutores as $instrutor) { ?>
                        <option value="<?= $instrutor['promotor_treinamento'] ?>"><?= $instrutor['nome'] ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="span3 control-group">
                <label for="tipo" class="control-label"><?=traduz('Tipo')?>:</label>
                <?php
                    $qTipo = "
                        SELECT
                            treinamento_tipo,
                            nome
                        FROM tbl_treinamento_tipo
                        WHERE fabrica = {$login_fabrica}
                    ";
                    $rTipo = pg_query($con, $qTipo);
                    $tipos = pg_fetch_all($rTipo);
                ?>
                <select name="tipo" id="tipo" class="span12">
                    <option value=""><?=traduz('Selecione')?>...</option>
                    <?php foreach ($tipos as $tipo) { ?>
                    <option value="<?= $tipo['treinamento_tipo'] ?>"><?= $tipo['nome'] ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="span3"></div>
        </div>
        <div class="row-fluid" style="margin-top:15px">
            <div class="span4"></div>
            <div class="span4" style="text-align:center;">
                <button type="button" class="btn btn-primary" id="search"><i class="fa fa-search" style="margin:0 5px"></i><?=traduz('Pesquisar')?></button>
            </div>
            <div class="span4"></div>
        </div>
    </form>
</div>
<?php } ?>

</div>
<div class="container-fluid">
<div id='dados'></div>
<?php if (in_array($login_fabrica, array(169,170))){ ?>
    <div id='btn-excel' class="btn_excel" style="display:none;">
            <span><img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' /></span>
            <span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
        </div>
    </div>
<?php } ?>
<p>

<script type="text/javascript">
	$(function(){
        var fabrica = "<?= $login_fabrica ?>";
        
        if (fabrica != 169 && fabrica != 170) {
            let data = { 
                ajax: 'sim',
                todos: 'sim',
                acao: 'ver',
                treinamentos_realizado: 'sim'
            };
            loadTrainings(data, function (response) {
                if (response.ok !== undefined) {
                    $('#dados').html(response.ok);
                    var table = new Object();
                    table['table'] = '#tblTreinamento';
                    $.dataTableLoad(table);

                    $("#btn-excel").show();
                } else {
                    $('#Alerta').show().find('h4').text(response.nenhum);
                    $("#btn-excel").hide();
                }
            });
        }

        $("#data-inicial").datepicker({dateFormat: "dd/mm/yy"}).mask("99/99/9999");
        $("#data-final").datepicker({dateFormat: "dd/mm/yy"}).mask("99/99/9999");
        
        $("#estado").on("change", function () {
            $("#cidade").find("option").first().nextAll().remove();

            let estado = $(this).val();
            $.ajax({
                url: window.location,
                type: 'POST',
                async: true,
                data: {
                    ajax: 'loadCities',
                    estado: estado
                }
            }).fail(function () {
                alert("Falha ao buscar cidades, tente novamente em instantes.");
            }).done(function(response) {
                response = JSON.parse(response);

                $.each(response, function (index, element) {
                    let option = $("<option></option>", {
                        text: element.nome,
                        val: element.cidade
                    });

                    $("#cidade").append(option);
                });
            })
        })

        Shadowbox.init();
        
        $("#search").on("click", function () {
            $.each($(".control-group"), function (index, element) {
                $(element).removeClass("error");
            });
            
            $("#tblTreinamento_wrapper").remove();
            $("#Alerta").css("display", "none")
            $("#alertaErro").css("display", "none");

            if (($("#data-inicial").val().length > 0 && $("#data-final").val().length == 0) || ($("#data-final").val().length > 0 && $("#data-inicial").val().length == 0)) {
                $("#data-inicial").parents(".control-group").addClass("error");
                return $("#data-final").parents(".control-group").addClass("error");
            }

            let data = {};

            if (formCheck()) {
                data = {
                    ajax:               "sim",
                    acao:               "ver",
                    todos:              "sim",
                    treinamentos_realizado: "sim",
                    data_inicial:       $("#data-inicial").val(),
                    data_final:         $("#data-final").val(),
                    titulo_pesquisa:    $("#titulo").val(),
                    estado_pesquisa:    $("#estado").val(),
                    cidade_pesquisa:    $("#cidade").val(),
                    instrutor_pesquisa: $("#instrutor").val(),
                    tipo_pesquisa:      $("#tipo").val()
                };
            } else {
                data = { 
                    ajax: 'sim',
                    todos: 'sim',
                    acao: 'ver',
                    treinamentos_realizado: 'sim'
                };
            }

            loadTrainings(data, function (response) {
                if (response.ok !== undefined) {
                    $('#dados').html(response.ok);
                    var table = new Object();
                    table['table'] = '#tblTreinamento';
                    $.dataTableLoad(table);

                    $("#btn-excel").show();
                } else {
                    $('#Alerta').show().find('h4').text(response.nenhum);
                    $("#btn-excel").hide();
                }
            });
        });

        $(document).on('click', 'a.shadow_treinamento', function(){////HD-3261932
            var url = $(this).data('url');
            Shadowbox.open({
                content: url,
                player: 'iframe',
                width: 1024,
                height: 600
            });
        });
	});

    function loadTrainings(data, callback) {
        $.ajax({
            method: "GET",
            url: "ajax_treinamento.php",
            data: data
        }).fail(function(){
            $('#alertaErro').show().find('h4').text("Ocorreu um erro ao tentar listar os treinamentos realizados, tempo esgotado! Recarregue a pagina...");
        }).done(function(response) {
            response = JSON.parse(response);
            callback(response);
        });
    }

    function formCheck() {
        let formParams = $("#form-parametros");
        let inputs = $(formParams).find("input");
        let selects = $(formParams).find("select");

        $.each(inputs, function (index, element) {
            if (typeof element !== "undefined" && $(element).val().length > 0) return false;
        });

        $.each(selects, function (index, element) {
            if (typeof element !== "undefined" && $(element).val().length > 0) return false;
        });

        return true;
    }

    $(document).on('click','button.seleciona-treinamento', function(){
        var btn = $(this);
        var text = $(this).text();
        var treinamento = $(btn).data('treinamento');
        $(btn).prop({disabled: true}).text("Espere...");
        $('#alertaErro').hide().find('h4').text("");

        $.ajax({
            method: "GET",
            url: "ajax_treinamento.php",
            data: { ajax: 'sim', acao: 'ativa_desativa', treinamento: treinamento, id: 0},
            timeout: 8000
        }).fail(function(){
            $('#alertaErro').show().find('h4').text("Ocorreu um erro ao tentar cancelar/confirmar um treinamento, tempo esgotado! Recarregue a pagina...");
        }).done(function(data) {
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                $(btn).prop({disabled: false}).text(data.ok);
                if (data.ok == 'Cancelado') {
                    $(btn).removeClass('btn-primary');
                    $(btn).addClass('btn-danger');
                    $(btn).parent("td").prev("td").find("img").attr({ src: "imagens_admin/status_vermelho.gif" });
                }else{
                    $(btn).addClass('btn-primary');
                    $(btn).removeClass('btn-danger');
                    $(btn).parent("td").prev("td").find("img").attr({ src: "imagens_admin/status_verde.gif" });
                }
            }else{
                $(btn).prop({disabled: false}).text(text);
                $('#alertaErro').show().find('h4').text(data.erro);
            }
        });
    });

    $("#btn-excel").on('click',function(){

        var parametros = '&ajax=sim&todos=sim&treinamentos_realizado=sim&excel=true&acao=ver';
        $.ajax({
            method: "GET",
            url: "ajax_treinamento.php",
            data: parametros,
            beforeSend : function() {
                $("#loading-block").show();
                $("#loading").show();
            },
            complete : function(){
                $("#loading-block").hide();
                $("#loading").hide();
            }          
        }).fail(function(){
            $('#alertaErro').show().find('h4').text("Não foi possível gerar relatório em excel, tempo esgotado!");
        }).done(function(data) {
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                window.open(data.ok);
            }else{
                $('#alertaErro').show().find('h4').text("Ocorreu um erro ao tentar gerar o relatório em excel!");
            }
        });
    });

</script>

<? include "rodape.php"; ?>

