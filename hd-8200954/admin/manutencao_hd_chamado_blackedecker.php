<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';
include "../helpdesk.inc.php";

$arrayEstados = array("AC" => "Acre",           "AL" => "Alagoas",          "AM" => "Amazonas",
                 "AP" => "Amapá",           "BA" => "Bahia",            "CE" => "Ceará",
                 "DF" => "Distrito Federal","ES" => "Espírito Santo",   "GO" => "Goiás",
                 "MA" => "Maranhão",        "MG" => "Minas Gerais",     "MS" => "Mato Grosso do Sul",
                 "MT" => "Mato Grosso",     "PA" => "Pará",             "PB" => "Paraíba",
                 "PE" => "Pernambuco",      "PI" => "Piauí",            "PR" => "Paraná",
                 "RJ" => "Rio de Janeiro",  "RN" => "Rio Grande do Norte","RO"=>"Rondônia",
                 "RR" => "Roraima",         "RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
                 "SE" => "Sergipe",         "SP" => "São Paulo",        "TO" => "Tocantins");

if(isset($_POST['acao']) == 'transferir'){

    $chamados    = str_replace("\\", "", $_POST['hd_chamado']);
    $chamados    = json_decode($chamados,true);
    $transferir = $_POST['transferir'];

    $res = pg_query($con,"BEGIN");

    if(!empty($transferir)){

        $sql = "SELECT login from tbl_admin where admin = $login_admin";
        $res = pg_query($con, $sql);

        $nome_ultimo_atendente = pg_fetch_result($res, 0, 'login');

        $sql = "SELECT login,email from tbl_admin where admin = $transferir";
        $res = pg_query($con, $sql);

        $nome_atendente  = pg_fetch_result($res,0,'login');
        $email_atendente = pg_fetch_result($res,0,'email');

        $sql = "INSERT INTO tbl_hd_chamado_item(
                    hd_chamado   ,
                    data         ,
                    comentario   ,
                    admin        ,
                    interno      ,
                    status_item
                )
                SELECT  tbl_hd_chamado.hd_chamado,
                NOW(),
                E'Atendimento transferido por <b>$login_login</b> de <b>' || tbl_admin.login || '</b> para <b>$nome_atendente</b>',
                $login_admin,
                't',
                tbl_hd_chamado.status
                FROM tbl_hd_chamado
                JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin AND tbl_admin.fabrica = {$login_fabrica}
                WHERE tbl_hd_chamado.hd_chamado IN(".implode(",",$chamados).")
                AND tbl_hd_chamado.fabrica = {$login_fabrica}";

        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE tbl_hd_chamado set atendente = $transferir
                WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
                and tbl_hd_chamado.hd_chamado IN(".implode(",",$chamados).")";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

    }

    if(strlen(trim($msg_erro))>0){
        $res = pg_query($con,"ROLLBACK");
        $retorno = array('retorno' => "erro");
    }else{
        $res = pg_query($con,"COMMIT");
        $retorno = array('retorno' => "ok");
    }

    echo json_encode($retorno);

    exit;
}

if($_POST["btn_acao"] == "submit"){
    $data_inicial           = $_POST['data_inicial'];
    $data_final             = $_POST['data_final'];
    $admin                  = $_POST['admin'];
    $status                 = $_POST["status"];
    $tipo_solicitacao       = $_POST["tipo_solicitacao"];
    $atendente              = $_POST["atendente"];
    $categoria_posto        = $_POST["categoria_posto"];
    $tipo_posto             = $_POST["tipo_posto"];
    $estado                 = $_POST['estado'];
    $cidade                 = mb_strtoupper(retira_acentos($_POST['cidade']));

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

            if (strtotime($aux_data_inicial."+6 months" ) < strtotime($aux_data_final)) {
                $msg_erro["msg"][]    = "Intervalo de pesquisa não pode ser maior do que seis meses.";
                $msg_erro["campos"][] = "data";
            }
        }
    }


    if(strlen(trim($tipo_solicitacao))>0){
        $cond_tipo_solicitacao = " AND tbl_hd_chamado.categoria = '$tipo_solicitacao' ";
    }

    if(strlen($atendente) > 0){
        $cond_atendente = " AND tbl_hd_chamado.atendente = $atendente ";
    }

    if(strlen(trim($status))>0){
        $cond_status = " AND tbl_hd_chamado.status = '$status' ";
    }

    if(strlen(trim($categoria_posto))>0){
        $cond_categoria_posto = " AND tbl_posto_fabrica.categoria = '$categoria_posto'  ";
    }

    if(strlen(trim($tipo_posto))>0){
        $cond_tipo_posto = " AND tbl_posto_fabrica.tipo_posto = $tipo_posto ";   
    }

    if(strlen(trim($estado))>0){
        $cond_estado = " AND tbl_posto_fabrica.contato_estado = '$estado' ";
    }

    if(strlen(trim($cidade))>0){
        $cond_cidade = " AND tbl_posto_fabrica.contato_cidade = '$cidade' ";
    }


    if(count($msg_erro['msg']) == 0){
        $sql = "SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado.status, tbl_hd_chamado.data, tbl_hd_chamado.categoria, tbl_hd_chamado.tipo_solicitacao, tbl_hd_chamado.data_resolvido, tbl_posto_fabrica.codigo_posto, tbl_posto.nome as descricao_posto, tbl_posto_fabrica.categoria as categoria_posto, tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.contato_cidade, tbl_admin.nome_completo as atendente, tbl_tipo_posto.descricao as tipo_posto_descricao, tbl_hd_chamado.protocolo_cliente from tbl_hd_chamado 
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_hd_chamado.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                INNER JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
                INNER JOIN tbl_tipo_posto on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                INNER JOIN tbl_admin on tbl_admin.admin = tbl_hd_chamado.atendente AND tbl_admin.fabrica = $login_fabrica

                WHERE tbl_hd_chamado.fabrica = $login_fabrica 
                $cond_tipo_solicitacao
                $cond_atendente
                $cond_status
                $cond_categoria_posto
                $cond_tipo_posto
                $cond_estado
                $cond_cidade
                AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
        $resSubmit = pg_query($con, $sql);
        $count = pg_num_rows($resSubmit);
    }
}
$layout_menu = "callcenter";
$title= "MANUTENÇÃO HELP-DESK EM LOTE";
include "cabecalho_new.php";
$plugins = array(
    "datepicker",
    "mask",
    "dataTable",
    "ajaxform"
);
include("plugin_loader.php");
?>
<script type="text/javascript">
    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));

        $("#todos").click(function(){

            $(".check").each(function(){

                if($(this).is(':checked')){
                    $(this).prop({"checked":false});
                }else{
                    $(this).prop({"checked":true});
                }

            });

        });

        var cod_ibge = "<?=$cidade?>";
        var fabrica = "<?=$login_fabrica?>";

        $("select[name=estado]").change(function () {
            
            $("select[name=cidade]").find("option[rel!=default]").remove();

            if ($(this).val().length > 0) {
                if (ajaxAction()) {
                    $.ajax({
                        url: "atendente_cadastro.php",
                        type: "POST",
                        data: { buscaCidade: true, estado: $(this).val() },
                        beforeSend: function () {
                            loading("show");
                        },
                        complete: function (data) {
                            data = data.responseText;

                            if (data.length > 0) {
                                data = $.parseJSON(data);

                                $.each(data, function (key, value) {
                                    var option = $("<option></option>");

                                    //option.val(value.cod_ibge);
                                    option.val(value.cidade);
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

        $("#gravar").click(function(){

            var hd_chamado = "";
            var array_chamado = new Array();
            var json = {};
            var transferir  = $("#atendente").val();

            $("input[class='check']:checked").each(function(){
                hd_chamado = $(this).val();
                array_chamado.push(hd_chamado);
            });

            if(array_chamado.length == 0){
                alert("Por favor marcar o(s) chamado(s) que deseja transferir.");
                return false;
            }

            json = array_chamado;
            json = JSON.stringify(json);

            $.ajax({
                url: "manutencao_hd_chamado_blackedecker.php",
                async:false,
                type: "POST",
                data: {
                        acao        :"transferir",
                        hd_chamado  :json,
                        transferir  :transferir
                    },
                beforeSend: function(){
                    $("#gravar").prop("disabled",true);
                    $("#processando").html('<em>Processando...</em>');
                },
                complete: function(retorno){
                    var resposta = JSON.parse(retorno.responseText);
                    //var statuss = resposta.statuss;
                    var msg = resposta.mensagem;

                    $("#gravar").prop("disabled",false);
                    $("#processando").html('');

                    if(resposta.retorno == 'erro'){
                        $('#mensagem').html("<div class='alert alert-error'><h4>Falha ao transferir chamado</h4></div>");
                    }else{
                        alert("Transferência realizada com sucesso.");
                        window.location.reload();
                    }
                }
            });
        });

        $("form[name=form_anexo]").ajaxForm({
            beforeSend: function(){
                $("#upload").prop("disabled",true);
                $("#processando").html('<em>Processando...</em>');
            },
            complete: function(retorno) {
                var resposta = JSON.parse(retorno.responseText);
                var statuss = resposta.statuss;
                var msg = resposta.mensagem;

                if(statuss == 'error'){
                    $('#msg_upload').html("<div class='alert alert-error'><h4>"+msg+"</h4></div>");
                }

                if(statuss == 'ok'){

                    $('#msg_upload').html("<div class='alert alert-success'><h4>"+msg+"</h4></div>");
                    $("#upload").prop("disabled",false);
                    $("#processando em").detach();
                    $("#downloadResp a").attr("href",resposta.caminho).show();
                }
            }
        });
    });
</script>

<style type="text/css">
    #downloadResp {
        text-align:center;
    }

    #downloadResp a{
        font-weight:bold;
        display:none;
    }
</style>

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
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

<!--form-->
    <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <div class ='titulo_tabela'>Parametros de Pesquisa </div>
        <br/>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span3'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_final'>Data Final</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12 text-center' value= "<?=$data_final?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

                <div class='row-fluid'>
                    <div class='span2'></div>
                    <div class='span3'>
                        <div class='control-group <?=(in_array("admin", $msg_erro["campos"])) ? "error" : ""?>'>
                            <label class='control-label' for='admin'>Atendente</label>
                            <div class='controls controls-row'>
                                <div class='span12'>

                                   <select name='atendente' class='span12' >
                                        <option></option>
                                        <?php 
                                        $sql = "SELECT admin, nome_completo
                                                FROM tbl_admin
                                                WHERE fabrica = {$login_fabrica}
                                                AND (admin_sap or fale_conosco)
                                                AND ativo IS TRUE
                                                ORDER BY nome_completo";
                                        $res = pg_query($con, $sql);

                                        if (pg_num_rows($res) > 0) {
                                            $value = getValue("atendente");

                                            for ($i = 0; $i < pg_num_rows($res); $i++) {
                                                $admin = pg_fetch_result($res, $i, "admin");
                                                $nome_completo = pg_fetch_result($res, $i, "nome_completo");

                                                $selected = ($admin == $value) ? "selected" : "";
                                                
                                                echo "<option value='{$admin}' {$selected}>{$nome_completo}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='span3'>
                        <div class='control-group <?=(in_array("admin", $msg_erro["campos"])) ? "error" : ""?>'>
                            <label class='control-label' for='admin'>Tipo Solicitação</label>
                            <div class='controls controls-row'>
                                <div class='span12'>
                                   <select name='tipo_solicitacao' class='span12' >
                                        <option value=''></option>  <?
                                            ksort($categorias);
                                            foreach ($categorias as $categoria => $config) {
                                                if ($config['no_fabrica']) {
                                                    if (in_array($login_fabrica, $config['no_fabrica'])) {
                                                        continue;
                                                    }
                                                }

                                                echo CreateHTMLOption($categoria, $config['descricao'], $_POST['categoria']);
                                            } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='span2'>
                        <div class='control-group <?=(in_array("admin", $msg_erro["campos"])) ? "error" : ""?>'>
                            <label class='control-label' for='admin'>Categoria Posto</label>
                            <div class='controls controls-row'>
                                <div class='span12'>
                                   <select name='categoria_posto' class='span12' >
                                        <option value=""></option>
                                            <option value="Autorizada" <?=(strtolower($categoria_posto) == 'autorizada')          ? " SELECTED " : ""; ?>>Autorizada</option>
                                            <option value="Locadora" <?=(strtolower($categoria_posto) == 'locadora')            ? "SELECTED" : "";?>>Locadora</option>
                                            <option value="Locadora Autorizada" <?=(strtolower($categoria_posto) == 'Locadora Autorizada')            ? "SELECTED" : "";?>>Locadora Autorizada</option>
                                             <option value="mega projeto" <?=(strtolower($categoria_posto) == 'mega projeto')            ? "SELECTED" : "";?>>Industria/Mega Projeto</option>
                                            <option value="Pr&eacute; Cadastro" <?=(strtolower($categoria_posto) == 'pré cadastro')            ? "SELECTED" : "";?>>Pré Cadastro</option>
                                           
                                    </select>
                                </div>
                            </div>
                        </div>
                        </div>
                    
                    <div class='span2'></div>
                </div>
                <div class='row-fluid'>
                    <div class='span2'></div>
                    
                        
                    <div class='span3'>
                        <div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
                            <label class='control-label' for='estado'>Estado</label>
                            <div class='controls controls-row'>
                                <div class='span12'>
                                    <?php
                                    if (!in_array($login_fabrica,array(30,151)) AND !$moduloProvidencia) {
                                    ?>
                                        <h5 class='asteristico'>*</h5>
                                    <?php
                                    }
                                    ?>
                                    <select name="estado" id="estado" class='span12'>
                                        <option></option>
                                        <?php
                                        $value = getValue("estado");

                                        foreach ($arrayEstados as $sigla => $nome) {
                                            $selected  = ($sigla == $value)  ? "selected" : "";

                                            echo "<option value='{$sigla}' {$selected}>{$nome}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='span3'>
                        <div class='control-group'>
                            <label class='control-label' for='cidade'>Cidade</label>
                            <div class='controls controls-row'>
                                <div class='span12'>
                                    <select name="cidade" id="cidade" class='span12'>
                                        <option rel="default"></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='span2'>
                            <div class='control-group <?=(in_array("admin", $msg_erro["campos"])) ? "error" : ""?>'>
                                <label class='control-label' for='admin'>Tipo Posto</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                       <select name='tipo_posto' class='span12' >
                                            <option value="">Tipo de Posto</option>
                                                <?php 
                                                    $sql = "SELECT tipo_posto, descricao
                                                        FROM   tbl_tipo_posto
                                                        WHERE  tbl_tipo_posto.fabrica = $login_fabrica
                                                        AND tbl_tipo_posto.ativo = 't'
                                                        ORDER BY tbl_tipo_posto.descricao";
                                                    $res = pg_query($con, $sql);
                                                    for($i=0; $i<pg_num_rows($res); $i++){
                                                        $tipo_posto_db = pg_fetch_result($res, $i, 
                                                            tipo_posto);
                                                        $descricao = pg_fetch_result($res, $i, descricao);

                                                        if($tipo_posto_db == $tipo_posto){
                                                            $selected = " selected ";
                                                        }else{
                                                            $selected = "";
                                                        }

                                                        echo "<option value='$tipo_posto_db' $selected >$descricao</option>";
                                                    }

                                                ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <div class='span2'></div>
                </div>
                <div class='row-fluid'>
                    <div class='span2'></div>
                    
                    <div class='span3'>
                            <div class='control-group <?=(in_array("admin", $msg_erro["campos"])) ? "error" : ""?>'>
                                <label class='control-label' for='admin'>Status</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                       <select name='status' class='span12' >
                                            <option value=""></option>
                                            <?php
                                                $sql = "SELECT DISTINCT status
                                                            FROM tbl_hd_chamado
                                                            WHERE fabrica_responsavel = $login_fabrica
                                                            ORDER BY status";
                                                $res = pg_query($con,$sql);

                                                if(pg_num_rows($res) > 0){
                                                    for($i = 0; $i < pg_num_rows($res); $i++){
                                                        $staus_desc = pg_result($res,$i,'status');
                                                        $staus_value = pg_result($res,$i,'status');

                                                        switch($staus_desc) {
                                                            case ('Ag. Posto') :   $staus_desc    = "Aguardando Posto"; break;
                                                            case ('Ag. Fábrica') : $staus_desc ="Aguardando Fábrica"; break;
                                                            case ('Em Acomp.') : $staus_desc ="Em Acompanhamento"; break;
                                                            case ('Resp.Conslusiva') : $staus_desc ="Resposta Conclusiva"; break;
                                                        }

                                                        if($staus_value == $status ){
                                                            $selected = " selected ";
                                                        }else{
                                                            $selected = " ";
                                                        }
                                                        echo "<option value='$staus_value' $selected >$staus_desc</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <div class='span2'></div>
                </div>
        
        <p><br />
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br />
    </form>
</div>
    
<?php
    if(strlen ($msg_erro["msg"]) == 0  AND pg_num_rows($resSubmit) > 0){
?>
            <table id="resultado_atendimentos" class = 'table table-striped table-bordered table-hover table-large'>
                <thead>
                    <tr class = 'titulo_coluna'>
                        <td><input type="checkbox" name="todos" id="todos">
                        <th>Atendente</th>
                        <th>Posto</th>
                        <th>Abertura</th>
                        <th>Fechamento</th>
                        <th>Atendimento</th>
                        <th>Tipo Solicitação</th>
                        <th>Tipo Posto</th>
                        <th>Categoria Posto</th>
                        <th>Cidade</th>
                        <th>Estado</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        for($i = 0; $i < $count; $i++){
                            $os             = pg_fetch_result($resSubmit, $i, 'os');
                            $status         = pg_fetch_result($resSubmit, $i, 'status');
                            $atendente      = pg_fetch_result($resSubmit, $i, 'atendente');
                            $data           = mostra_data( substr(pg_fetch_result($resSubmit, $i, 'data'),0 ,10 ));
                            $fechamento           = mostra_data( substr(pg_fetch_result($resSubmit, $i, 'fechamento'),0 ,10 ));
                            $atendimento    = pg_fetch_result($resSubmit, $i, 'hd_chamado');
                            $id_atendimento    = pg_fetch_result($resSubmit, $i, 'hd_chamado');
                            $status         = pg_fetch_result($resSubmit, $i, 'status');
                            $cidade         = pg_fetch_result($resSubmit, $i, 'contato_cidade');
                            $estado         = pg_fetch_result($resSubmit, $i, 'contato_estado');
                            $descricao_posto        = pg_fetch_result($resSubmit, $i, 'descricao_posto');
                            $codigo_posto        = pg_fetch_result($resSubmit, $i, 'codigo_posto');
                            $tipo_solicitacao    = pg_fetch_result($resSubmit, $i, 'tipo_solicitacao');
                            $tipo_posto_descricao    = pg_fetch_result($resSubmit, $i, 'tipo_posto_descricao');
                            $categoria_posto= pg_fetch_result($resSubmit, $i, 'categoria_posto');
                            $protocolo_cliente= pg_fetch_result($resSubmit, $i, 'protocolo_cliente');
                            $categoria      = pg_fetch_result($resSubmit, $i, 'categoria');

                            //$categoria      = str_replace("_", " ", $categoria);
                            //$categoria_chamado      = ucwords($categoria);

                            $categoria_chamado = $categorias["$categoria"];

                            $data_resolvido = mostra_data( substr(pg_fetch_result($resSubmit, $i, 'data_resolvido'),0 ,10 ));
							$atendimento = (strlen($protocolo_cliente) > 0 ) ?$protocolo_cliente:$atendimento;
                        $body .= "<tr>
                                    <td><input type='checkbox' value='{$id_atendimento}' class='check'>
                                    <td class= 'tac'>{$atendente}</td>
                                    <td nowrap class= 'tac'>$codigo_posto - $descricao_posto</td>
                                    <td class= 'tac'>{$data}</td>
                                    <td class= 'tac'>{$data_resolvido}</td>
                                    <td class= 'tac'><a href='helpdesk_cadastrar.php?hd_chamado={$id_atendimento}' target='_blank'>$atendimento</a></td>
                                    <td class= 'tac'>{$categoria_chamado['descricao']}</td>
                                    <td class= 'tac'>{$tipo_posto_descricao}</td>
                                    <td class= 'tac'>{$categoria_posto}</td>
                                    <td class= 'tac'>{$cidade}</td>
                                    <td class= 'tac'>{$estado}</td>
                                    <td class= 'tac'>{$status}</td>
                                </tr>";
                            }
                                echo $body;
                    ?>
                </tbody>
            </table>
            <script>
                $.dataTableLoad({ table: "#resultado_atendimentos" });
            </script>
            <br />

            <div class="container" id="mensagem"></div>

            <div class="container" >
            <form name='frm_relatorio' METHOD='POST' align='center' class='form-search form-inline tc_formulario' >
                <div class ='titulo_tabela'>Parametros de alteração</div>
                <br/>

                <div class='row-fluid'>
                    <div class='span4'></div>
                    <div class='span4'>
                        <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                            <label class='control-label' for='data_inicial'>Transferir p/</label>
                            <div class='controls controls-row'>
                                <div class='span12'>

                                    <select name="atendente" id="atendente">
                                        <option value=""></option>
                                        <?php
                                            $sql = "SELECT admin, nome_completo
                                                    FROM tbl_admin
                                                    WHERE fabrica = {$login_fabrica}
                                                    AND (admin_sap IS TRUE or fale_conosco)
                                                    AND ativo IS TRUE
                                                    ORDER BY nome_completo";
                                            $res = pg_query($con, $sql);

                                            if (pg_num_rows($res) > 0) {
                                                $value = getValue("atendente");

                                                for ($i = 0; $i < pg_num_rows($res); $i++) {
                                                    $admin_db = pg_fetch_result($res, $i, "admin");
                                                    $nome_completo = pg_fetch_result($res, $i, "nome_completo");

                                                    $selected = ($admin_db == $value) ? "selected" : "";
                                                    
                                                    echo "<option value='{$admin_db}' {$selected}>{$nome_completo}</option>";
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='span2'></div>
                </div>                
                <p><br />
                    <button class='btn' id="gravar" type="button">Transferir</button>
                    <span id="processando"></span>
                </p><br />

<?php
    }elseif($count == 0 AND $_POST["btn_acao"] == "submit"){
        echo " <div class='container'>  <div class='alert alert-warning'><h4>Nenhum registro encontrado.</h4></div> </div>";        
    }
?>
</div>
<?php
include 'rodape.php';
?>
