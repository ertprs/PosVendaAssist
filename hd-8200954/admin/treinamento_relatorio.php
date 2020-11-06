<?
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include 'includes/funcoes.php';
    include __DIR__ . '/admin/funcoes.php';
    $admin_privilegios="info_tecnica";
    include 'autentica_admin.php';
    $layout_menu = "tecnica";
    if($login_fabrica == 138){
        $title = "RELATÓRIO DE TÉCNICOS POR TREINAMENTO";
    }else{
        $title = "RELATÓRIO DE TREINAMENTOS";
    }

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
            $select = "<select id='titulo' name='titulo' class='frm' >";
            $select .= "<option value=''>Selecione um treinamento</option>";
            
            $sql = "SELECT distinct titulo FROM tbl_treinamento WHERE fabrica = $login_fabrica ORDER BY titulo";
            $res = pg_exec ($con,$sql);

            if (pg_numrows($res) > 0) {

                for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
                    $select .= "<option value='".pg_result ($res,$i,titulo)."' ";
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

        /* ---------- LISTA STATUS ---------- */
        if (isset($_POST['listaStatus']) && $_POST['listaStatus'] == "sim") {
            $select = "<select name='status' id='status' class='frm' >";
            $select .= "<option>Selecione um status</option>";

            $sql_r = "SELECT status_roteiro, descricao FROM tbl_status_roteiro;";
            $res_r = pg_query($con,$sql_r);

            if (pg_num_rows($res_r) > 0) {
                for ($w=0; $w < pg_num_rows($res_r) ; $w++) { 
                    $select .= "<option value='".pg_fetch_result($res_r,$w,status_roteiro)."' ";
                    if ($titulo == pg_fetch_result($res_r,$w,status_roteiro)){
                        $select .= " selected ";
                    }
                    $select .= ">";
                    $select .= pg_fetch_result($res_r,$w,descricao);
                    $select .= "</option>";
                }
                $select .= "</select>";
                exit(json_encode(array("ok" => utf8_encode($select))));
            }else{                
                exit(json_encode(array("erro" => utf8_encode("Ocorreu um erro ao tentar listar os status, recarregue a pagina..."))));
            }
        }

        /* ---------- LISTA LINHA ---------- */
        if (isset($_POST['listaLinha']) && $_POST['listaLinha'] == "sim") {
            $select = "<select name='linha' id='linha' class='frm' >";
            $select .= "<option value=''>Selecione uma linha</option>";
            
            $sql_linha = "SELECT linha, nome FROM tbl_linha WHERE ativo IS TRUE AND fabrica = {$login_fabrica} AND linha IN (697, 698, 699, 700) ORDER BY nome ASC";
            $res_linha = pg_query($con, $sql_linha);

            if(pg_num_rows($res_linha) > 0){
                for($i = 0; $i < pg_num_rows($res_linha); $i++){
                    $lin = pg_fetch_result($res_linha, $i, "linha");
                    $nome = pg_fetch_result($res_linha, $i, "nome");
                    $selected = (in_array($linha,$lin)) ? "SELECTED" : "";

                    $select .= "<option value='".$lin."' {$selected}>".$nome."</option>";
                }
                $select .= "</select>";                
                exit(json_encode(array("ok" => utf8_encode($select))));
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
            "label"     => traduz("Data Inicial"),
            "type"      => "input/text",
            "width"     => 10,
            "maxlength" => 10,
            "required"  => true
        ),
        "data_final" => array(
            "span"      => 4,
            "label"     => traduz("Data Final"),
            "type"      => "input/text",
            "width"     => 10,
            "maxlength" => 10,
            "required"  => true
        )        
    );
    if ($login_fabrica==20){
        $inputs['escritorio'] = array(
            "label"     => "Escritório",
            "type"      => "select",
            "option"    => array(),
            "width"     => 10,
            "span"      => 4         
        );        
    }
    if($login_fabrica == 117){
        $inputs['listaRegiao'] = array(
            "label"     => "Regiões",
            "type"      => "select",
            "option"    => array(),
            "width"     => 10,
            "span"      => 4         
        );        
    }
    $inputs['listaEstado'] = array(
        "label"     => "Estado",
        "type"      => "select",
        "option"    => array(),
        "width"     => 10,
        "span"      => 4         
    );
    if($login_fabrica == 117){
        $inputs['cidade'] = array(
            "label"     => "Cidade",
            "type"      => "select",
            "option"    => array(),
            "width"     => 10,
            "span"      => 4         
        );
        $inputs['listaStatus'] = array(
            "label"     => "Status",
            "type"      => "select",
            "option"    => array(),
            "width"     => 10,
            "span"      => 4         
        );
        $inputs['listaLinha'] = array(
            "label"     => "Linha",
            "type"      => "select",
            "option"    => array(),
            "width"     => 10,
            "span"      => 4         
        );
    }else{
        $inputs['tipo_posto'] = array(
            "label"     => "Tipo de Posto",
            "type"      => "select",
            "option"    => array(),
            "width"     => 10,
            "span"      => 4         
        );
    }
    $inputs['titulo'] = array(
        "label"     => "Tipo do Treinamento (Título)",
        "type"      => "select",
        "option"    => array(),
        "width"     => 10,
        "span"      => 4         
    );
?>
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

        listaRegiao();
        //listaEstado();
        listaStatus();
        listaLinha();
        listaTreinamento();
        CarregaEscritorio();
        listaPosto();
    });

    $(document).on("change","#listaRegiao", function(){
        var regiao_campo = $("#listaRegiao").val();
        if (regiao_campo !== "") {
            listaEstado(regiao_campo);
        }else{
            listaEstado();
            $('#cidade').html("<option value=''>Selecione</option>");
        }
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
        var parametros = $("#frm_relatorio").serialize() + '&ajax=sim&acao=relatorio';

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
                var table = new Object();
                table['table'] = '#tbtreinamento';
                $.dataTableLoad(table);
                $("#btn-excel").show();
            }else if (data.nenhum !== undefined) {
                $('#Alerta').show().find('h4').text(data.nenhum);
                $('#dados2').html("");
            }else{                
                $('#alertaErro').show().find('h4').text(data.erro.msg);
                campo_erro = data.erro.campos;
                $.each(campo_erro,function(index, valor){
                    $('input[id='+valor+']').parents("div.control-group").addClass("error");
                });
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

    function listaRegiao(){
        if ($('#listaRegiao').length !== 0) {
            $.ajax({
                method: "GET",
                url: "ajax_treinamento.php",
                data: {ajax: "sim", acao: "consulta_regiao"},
                timeout: 8000
            }).fail(function(){
                $('#alertaErro').show().find('h4').text("Não foi possível listar as regiões, tempo esgotado! Recarregue a pagina...");
            }).done(function(data){
                data = JSON.parse(data);
                if (data.ok !== undefined) {
                    $('#listaRegiao').html(data.ok);
                }else{
                    $('#alertaErro').show().find('h4').text(data.erro);
                }        
            });
        }
    }

/*    function listaEstado(estado = ""){
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
    }*/

    function listaStatus(){
        $.ajax({
            method: "POST",
            url: tela_relatorio,
            data: {ajax: "sim", listaStatus: "sim"},
            timeout: 8000
        }).fail(function(){
            $('#alertaErro').show().find('h4').text("Não foi possível listar os status, tempo esgotado! Recarregue a pagina...");
        }).done(function(data){
            data = JSON.parse(data);
            if (data.ok !== undefined) {                
                $('#listaStatus').html(data.ok);
            }else{
                $('#alertaErro').show().find('h4').text(data.erro);
            }            
        });
    }

    function listaLinha(){
        if ($('#listaLinha').length !== 0) {
            $.ajax({
                method: "POST",
                url: tela_relatorio,
                data: {ajax: "sim", listaLinha: "sim"},
                timeout: 8000
            }).fail(function(){
                $('#alertaErro').show().find('h4').text("Não foi possível listar as linhas de produtos, tempo esgotado! Recarregue a pagina...");
            }).done(function(data){
                data = JSON.parse(data);
                if (data.ok !== undefined) {
                    $('#listaLinha').html(data.ok);
                }
            });
        }
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
                $('#titulo').html(data.ok);
            }
        });        
    }

    function CarregaEscritorio(){
        $.ajax({
            method: "GET",
            url: "ajax_treinamento.php",
            data: {ajax: "sim", acao: "listaEscritorio"},
            timeout: 8000
        }).fail(function(){
            $("#alertaErro").show().find("h4").html("Erro ao verificar a lista de escritórios, tempo limite esgotado! Recarregue a pagina...");
        }).done(function(data){
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                $('#escritorio').html(data.ok);
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

        function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    var map = {"â":"a","Â":"A","à":"a","À":"A","á":"a","Á":"A","ã":"a","Ã":"A","ê":"e","Ê":"E","è":"e","È":"E","é":"e","É":"E","î":"i","Î":"I","ì":"i","Ì":"I","í":"i","Í":"I","õ":"o","Õ":"O","ô":"o","Ô":"O","ò":"o","Ò":"O","ó":"o","Ó":"O","ü":"u","Ü":"U","û":"u","Û":"U","ú":"u","Ú":"U","ù":"u","Ù":"U","ç":"c","Ç":"C","ñ":"n"};

    function removerAcentos(string) { 
        return string.replace(/[\W\[\] ]/g,function(a) {
            return map[a]||a}) 
    };

    /** select de provincias/estados */
    $(function() {

        $("#listaEstado option").remove();
        
        $("#listaEstado optgroup").remove();

        $("#listaEstado").append("<option value=''>TODOS OS ESTADOS</option>");
        
        var post = "<?php echo $_POST['listaEstado']; ?>";

        <?php if (in_array($login_fabrica,[181])) { ?> 

            $("#listaEstado").append('<optgroup label="Provincias">');
        
            var select = "";
            
            <?php 

                $provincias_CO = getProvinciasExterior("CO");

                foreach ($provincias_CO as $provincia) { ?>

                    var provincia = '<?= $provincia ?>';
                    console.log(provincia);
                    var semAcento = removerAcentos(provincia);

                    if (post == semAcento) {

                        select = "selected";
                    }

                    var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";
                    console.log(option);
                    $("#listaEstado").append(option);

                    select = "";

            <?php } ?>

                $("#listaEstado").append('</optgroup>');

        <?php } ?>

        <?php if (in_array($login_fabrica,[182])) { ?>

            $("#listaEstado").append('<optgroup label="Provincias">');
                
            var select = "";
            
            <?php 
             
            $provincias_PE = getProvinciasExterior("PE");

            foreach ($provincias_PE as $provincia) { ?>

                var provincia = '<?= $provincia ?>';

                var semAcento = removerAcentos(provincia);

                if (post == semAcento) {

                    select = "selected";
                }

                var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                $("#listaEstado").append(option);

                select = "";

            <?php } ?>

                $("#listaEstado").append('</optgroup>');
        <?php } ?>

        <?php if (in_array($login_fabrica,[180])) {  ?>

            $("#listaEstado").append('<optgroup label="Provincias">');
                
            var select = "";
    
            <?php 

                $provincias_AR = getProvinciasExterior("AR");

                foreach ($provincias_AR as $provincia) { ?>

                    var provincia = '<?= $provincia ?>';

                    var semAcento = removerAcentos(provincia);

                    if (post == semAcento) {

                        select = "selected";
                    }

                    var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                    $("#listaEstado").append(option);

                    select = "";

            <?php } ?>

                $("#listaEstado").append('</optgroup>');

        <?php } ?>  
        
    });

</script>