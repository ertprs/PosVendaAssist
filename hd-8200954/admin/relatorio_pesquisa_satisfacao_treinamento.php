<?
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include 'includes/funcoes.php';
    $admin_privilegios="info_tecnica";
    include 'autentica_admin.php';
    $layout_menu = "tecnica";
    $title       = "RELATÓRIO DE PESQUISA DE SATISFAÇÃO DO TREINAMENTO";

    if (isset($_POST['ajax']) && $_POST['ajax'] == "sim") {

        /* ---------- LISTA POSTO ---------- */
        if (isset($_POST['listaPosto']) && $_POST['listaPosto'] == "sim") {
            $select = "<select name='tipo_posto' id='tipo_posto' class='frm' >";
            $select .= "<option value=''>Selecione um posto</option>";
                
            $sql = "SELECT *
                    FROM   tbl_tipo_posto
                    WHERE  tbl_tipo_posto.fabrica = $login_fabrica
                    ORDER BY tbl_tipo_posto.descricao";

            $res = pg_exec ($con,$sql);
            if (pg_numrows($res) > 0) {
                for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
                    $select .= "<option value='" . pg_result ($res,$i,tipo_posto) . "' ";
                    if ($tipo_posto == pg_result ($res,$i,tipo_posto)){
                        $select .= " selected ";
                    }
                    $select .= ">";
                    $select .= pg_result ($res,$i,descricao);
                    $select .= "</option>";
                }
                $select .= "</select>";
                exit(json_encode(array("ok" => utf8_encode($select))));
            }else{
                exit(json_encode(array("erro" => utf8_encode("Não foi possível listar os postos, recarregue a pagina..."))));
            }
        }

        /* ---------- LISTA TREINAMENTO ---------- */
        if (isset($_POST['listaTreinamento']) && $_POST['listaTreinamento'] == "sim") {
            $select = "<select id='treinamento' name='treinamento' class='frm' >";
            $select .= "<option value=''>Selecione um treinamento</option>";
            
            $sql = "SELECT DISTINCT 
                        tbl_treinamento.titulo,
                        tbl_treinamento.treinamento 
                    FROM  tbl_treinamento 
                        JOIN tbl_pesquisa          ON tbl_pesquisa.treinamento          = tbl_treinamento.treinamento
                        JOIN tbl_resposta          ON tbl_resposta.pesquisa             = tbl_pesquisa.pesquisa
                        JOIN tbl_treinamento_posto ON tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                        JOIN tbl_tecnico           ON tbl_tecnico.tecnico               = tbl_treinamento_posto.tecnico
                    WHERE tbl_treinamento.fabrica = {$login_fabrica} 
                          AND tbl_treinamento.ativo         IS TRUE 
                          AND tbl_pesquisa.treinamento      IS NOT NULL
                          AND tbl_treinamento_posto.tecnico IS NOT NULL
                          AND tbl_resposta.pesquisa         IS NOT NULL
                    ORDER BY  tbl_treinamento.titulo";
            $res = pg_exec ($con,$sql);

            if (pg_numrows($res) > 0) {

                for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
                    $select .= "<option value='".pg_result ($res,$i,treinamento)."' ";
                    if ($titulo == pg_result ($res,$i,titulo)){
                        $select .= " selected ";
                    }
                    $select .= ">";
                    $select .= pg_result ($res,$i,titulo);
                    $select .= "</option>";                                
                }
                $select .= "</select>";
                exit(json_encode(array("ok" => utf8_encode($select))));
            }else{
                exit(json_encode(array("erro" => utf8_encode("Erro ao tentar listar os tipos de treinamento, recarregue a pagina..."))));
            }
        }
    }

    include "cabecalho_new.php";

    $plugins = array(
        "autocomplete",
        "datepicker",
        "shadowbox",
        "mask",
        "ajaxform",
        "dataTable"
    );

    include "plugin_loader.php";
    include "javascript_pesquisas.php";

    $inputs = array(
        "data_inicial" => array(
            "span"      => 4,
            "label"     => "Data Inicial",
            "type"      => "input/text",
            "width"     => 10,
            "maxlength" => 10,
            "required"  => true
        ),
        "data_final" => array(
            "span"      => 4,
            "label"     => "Data Final",
            "type"      => "input/text",
            "width"     => 10,
            "maxlength" => 10,
            "required"  => true
        )        
    );

    $inputs['listaEstado'] = array(
        "label"     => "Estado",
        "type"      => "select",
        "option"    => array(),
        "width"     => 10,
        "span"      => 4         
    );

    $inputs['tipo_posto'] = array(
        "label"     => "Tipo de Posto",
        "type"      => "select",
        "option"    => array(),
        "width"     => 10,
        "span"      => 4         
    );

    $inputs['treinamento'] = array(
        "label"     => "Tipo do Treinamento (Título)",
        "type"      => "select",
        "option"    => array(),
        "width"     => 10,
        "span"      => 4         
    );
?>

<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
<style>
.fa-calendar {
    cursor: pointer;
}
.table-respostas {
    /*display: none;*/
    margin-top: 20px;
}
.table-respostas td {
    color: #000;
    font-weight: normal;
}
.table-respostas td.text-info {
    color: #3a87ad;
    font-weight: bold;
    font-size: 14px;
}
.tbody-filtro {
    display: none;
}    
</style>

<form class="form-search form-inline tc_formulario" name="frm_relatorio" id="frm_relatorio">
    <div id="alertaErro" class="alert alert-error" style="display: none;"><h4></h4></div>
    <div id="Alerta" class="alert" style="display: none;"><h4></h4></div>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <? echo montaForm($inputs, $hiddens); ?>
    </br>
    <input type="button" class="btn btn-primary" value="Pesquisar" name='bt_cad_forn' id='bt_cad_forn'>
    <br>
    <br>
    <div id='btn-excel' class="btn_excel" style="display:none;">
        <span><img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' /></span>
        <span class="txt">Gerar Arquivo Excel</span>
    </div>
    <br>
    <br>
</form>
</div>
<div class='container-fluid'>
    <div id='dados2'></div>
</div>
<p>

<? include "rodape.php"; ?>

<script type="text/javascript">
    var campo_erro = [];
    var tela_relatorio = document.location.pathname;

    $(function(){
        $.datepickerLoad(["data_final", "data_inicial"]);

        listaEstado();
        listaTreinamento();
        listaPosto();

        $(document).on('click', '.btn-download-csv', function() {
            let arquivo = $(this).data('arquivo');
            
            window.open('xls/'+arquivo);
        });
    });

    $(document).on("change","#listaEstado", function(){
        var estados_campo = $("#listaEstado").val();
        if (estados_campo !== "") {
            $.ajax({
                method: "GET",
                url: "ajax_treinamento.php",
                data: {ajax: "sim", acao: "consulta_cidades", estados: estados_campo},
                timeout: 8000
            }).fail(function(){
                $('#alertaErro').show().find('h4').text("Ocorreu um erro ao tentar listar as cidades, tempo esgotado! Recarregue a pagina...");
            }).done(function(data){
                data = JSON.parse(data);
                if (data.messageError == undefined) {
                    var option = "<option value=''>Selecione uma cidade</option>";
                    $.each(data,function(index,obj){
                        option += "<option value='"+obj.cidade+"'>"+obj.cidade+"</option>";
                    });
                    $('#cidade').html(option);
                }else{
                    $('#alertaErro').show().find('h4').text("Ocorreu um erro ao tentar listar as cidades! Recarregue a pagina...");
                }                        
            });
        }else{
            $('#cidade').html("<option value=''>Selecione</option>");
        }
    });


    $('#bt_cad_forn').on('click',function(){
        $('#alertaErro').hide().find('h4').text("");
        $('#Alerta').hide().find('h4').text("");
        $("#btn-excel").hide();
        var parametros = $("#frm_relatorio").serialize() + '&ajax=sim&acao=relatorio_pesquisa_treinamento';

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
            $('#alertaErro').show().find('h4').text("Não foi possível gerar relatório, tempo esgotado!");
        }).done(function(data) {
            data = JSON.parse(data);            
            $.each(campo_erro,function(index, valor){
                $('input[id='+valor+']').parents("div.control-group").removeClass("error");
            });
            if (data.ok !== undefined) {
                $('#dados2').html(data.ok);
            } else{                
                $('#alertaErro').show().find('h4').text(data.erro.msg);
                $("#loading-block").hide();
                $("#loading").hide();
                if (data.erro.campos !== undefined) {
                    campo_erro = data.erro.campos;
                    $.each(campo_erro,function(index, valor){
                        $('input[id='+valor+']').parents("div.control-group").addClass("error");
                    });
                }
            }
        });
    });

    $("#btn-excel").on('click',function(){
        var parametros = $("#frm_relatorio").serialize() + '&ajax=sim&excel=true&acao=relatorio&time='+Date();
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

    $(document).on('click', '.btn-resposta', function() {
        Shadowbox.init();

        var acao        = "ver_resposta";
        var posto       = $(this).data('posto');
        var treinamento = $(this).data('treinamento');
        var tecnico     = $(this).data('tecnico');
        var url         = "detalhes_pesquisa_satisfacao_treinamento.php?ajax=sim&acao="+acao+"&posto="+posto+"&treinamento="+treinamento+"&tecnico="+tecnico;

        Shadowbox.open({
            content: url,
            player: 'iframe',
            width: 555,
            height: 666
        });
    });  

    function listaEstado(estado = ""){
        $.ajax({
            method: "GET",
            url: "ajax_treinamento.php",
            data: {ajax: "sim", acao: "consulta_estados", estados: estado},
            timeout: 8000
        }).fail(function(){
            $('#alertaErro').show().find('h4').text("Não foi possível listar os estados, tempo esgotado! Recarregue a pagina...");
        }).done(function(data){            
            data = JSON.parse(data);
            if (data.messageError == undefined) {
                var select = "<select id='estado' name='estado' class='frm'>";
                select += "<option value=''>Selecione um estado</option>";

                $.each(data,function(index,obj){
                    select += "<option value='"+obj.cod_estado+"'>"+obj.estado+"</option>";
                });                
                select += "</select>";
                $('#listaEstado').html(select);
            }else{
                $('#alertaErro').show().find('h4').text("Ocorreu um erro ao tentar listar os estados! Recarregue a pagina...");
            }
        });
    }

    function listaTreinamento(){
        $.ajax({
            method: "POST",
            url: tela_relatorio,
            data: {ajax: "sim", listaTreinamento: "sim"},
            timeout: 8000
        }).fail(function(){
            $('#alertaErro').show().find('h4').text("Não foi possível listar os tipos de treinamento, tempo esgotado! Recarregue a pagina...");
        }).done(function(data){
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                $('#treinamento').html(data.ok);
            }
        });        
    }

    function listaPosto(){
        $.ajax({
            method: "POST",
            url: tela_relatorio,
            data: {ajax: "sim", listaPosto: "sim"},
            timeout: 8000
        }).fail(function(){
            $("#alertaErro").show().find("h4").html("Não foi possível listar os tipos de postos, tempo esgotado! Recarregue a pagina...");
        }).done(function(data){
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                $('#tipo_posto').html(data.ok);
            }else{
                $("#alertaErro").show().find("h4").html(data.erro);
            }            
        });
    }
</script>