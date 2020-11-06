<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if($_GET['admin']){
    include "admin/autentica_admin.php";
}else{
    include "autentica_usuario.php";
}

if ($_POST["gravar"]) {
    $checklist["teste_funcional"]["conexao_padrao"]                         = $_POST["conexao_padrao"];
    $checklist["teste_funcional"]["conexao_energia"]                        = $_POST["conexao_energia"];
    $checklist["teste_funcional"]["inicio"]                                 = $_POST["inicio"];
    $checklist["teste_funcional"]["teste_tss"]                              = $_POST["teste_tss"];
    $checklist["teste_funcional"]["reinicio"]                               = $_POST["reinicio"];
    $checklist["teste_funcional"]["falha_tss_cabo"]                         = $_POST["falha_tss_cabo"];
    $checklist["teste_funcional"]["falha_tss_desconecta_plugue_principal"]  = $_POST["falha_tss_desconecta_plugue_principal"];
    $checklist["teste_funcional"]["falha_tss_abertura"]                     = $_POST["falha_tss_abertura"];
    $checklist["teste_funcional"]["falha_tss_desconecta_plugue_tss"]        = $_POST["falha_tss_desconecta_plugue_tss"];
    $checklist["teste_funcional"]["falha_tss_novo_teste"]                   = $_POST["falha_tss_novo_teste"];
    $checklist["teste_funcional"]["pistoes"]                                = $_POST["pistoes"];

    foreach ($checklist as $categoria => $itens) {
        foreach ($itens as $key => $value) {
            if(($key == "inicio" && $value == "nao") || ($key == "reinicio" && $value == "sim")){
                $erro = "";
                break 2;
            }else{
                if (!strlen($value)) {
                    $erro = "Preencha os campos em vermelho para prosseguir!";
                    $style[$categoria][$key] = "color: #E00;";
                }
            }
        }
    }

    if (empty($erro)) {
        $json = json_encode(($checklist));

        $sql = "INSERT INTO tbl_laudo_tecnico_os (os, observacao, fabrica, titulo) VALUES ({$_GET['os']}, '$json', $login_fabrica, 'Check List')";
        $res = pg_query($con, $sql);
    }
    if (pg_last_error()) {
        $erro = "Ocorreu um erro ao gravar o checklist!";
    } else {
	header("Location: os_item_new.php?os={$_GET['os']}");
    }
}

if ($_GET["imprimir"]) {
    $os = $_GET["imprimir"];

    $sql = "SELECT observacao FROM tbl_laudo_tecnico_os WHERE fabrica = $login_fabrica AND os = $os";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res)) {
        $json = utf8_decode(pg_fetch_result($res, 0, "observacao"));
        $checklist = json_decode($json, true);
        echo "<script>window.print();</script>";
    } else {
        //echo "<script>alert('Nenhum check list encontrado para a os $os'); window.close();</script>";
        echo "<script>alert('Nenhum check list encontrado para a os $os');</script>";
    }
}
?>
<html>
<head>
<title>Check List <?=$login_fabrica_nome?></title>
<link href="admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<script src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript">
$(function () {
    $("input[name^=conexao_]").click(function(){
        if($("input[name^=conexao_]:checked").length == 2){
            $("input[name=inicio]").attr("disabled",false);
        }else{
            $("input[name=inicio]").attr("disabled",true);
            $("input[name=teste_tss]").attr("disabled",true);
            $("input[name=reinicio]").attr("disabled",true);
            $("input[name^=falha_tss_]").attr("disabled",true);
            $("input[name=pistoes]").attr("disabled",true);

            $("input[name=inicio]").attr("checked",false);
            $("input[name=teste_tss]").attr("checked",false);
            $("input[name=reinicio]").attr("checked",false);
            $("input[name^=falha_tss_]").attr("checked",false);
            $("input[name=pistoes]").attr("checked",false);
        }
    });

    $("input[name=inicio]").change(function(){
        if($(this).val() == "sim"){
            $("input[name=teste_tss]").attr("disabled",false);
        }else{
            $("input[name=teste_tss]").attr("disabled",true);
            $("input[name=teste_tss]").attr("disabled",true);
            $("input[name=reinicio]").attr("disabled",true);
            $("input[name^=falha_tss_]").attr("disabled",true);
            $("input[name=pistoes]").attr("disabled",true);

            $("input[name=teste_tss]").attr("checked",false);
            $("input[name=reinicio]").attr("checked",false);
            $("input[name^=falha_tss_]").attr("checked",false);
            $("input[name=pistoes]").attr("checked",false);
        }
    });
    $("input[name=teste_tss]").click(function(){
        if($("input[name=teste_tss]:checked").length == 1){
            $("input[name=reinicio]").attr("disabled",false);
        }else{
            $("input[name=reinicio]").attr("disabled",true);
            $("input[name^=falha_tss_]").attr("disabled",true);
            $("input[name=pistoes]").attr("disabled",true);

            $("input[name=reinicio]").attr("checked",false);
            $("input[name^=falha_tss_]").attr("checked",false);
            $("input[name=pistoes]").attr("checked",false);
        }
    });
    $("input[name=reinicio]").change(function(){
        if($(this).val() == "nao"){
            $("input[name^=falha_tss_]").attr("disabled",false);
        }else{
            $("input[name^=falha_tss_]").attr("disabled",true);
            $("input[name=pistoes]").attr("disabled",true);

            $("input[name^=falha_tss_]").attr("checked",false);
            $("input[name=pistoes]").attr("checked",false);
        }
    });
    $("input[name=falha_tss_novo_teste]").click(function(){
        if($("input[name=falha_tss_novo_teste]:checked").length == 1){
            $("input[name=pistoes]").attr("disabled",false);
        }else{
            $("input[name=pistoes]").attr("disabled",true);
            $("input[name=pistoes]").attr("checked",false);
        }
    });

});
</script>
<style type="text/css">
    #principal {
        position: relative;
        margin: auto;
        width: 800px;
        height:500px;
    }

    #cabecalho {
        width: 100%;
        height: 100px;
    }

    #cabecalho h1 {
        position: relative;
        top: 15px;
        margin: auto;
        left: 400px;
        color: #000;
        font-family: Arial;
    }
    h2 {
        top: 45px;
        left: 80px;
        color: #000;
        font-family: Arial;
    }

    label {
        padding-top: 5px !important;
    }

    #rodape {
        width: 100%;
    }

    #rodape .logo {
        position: relative;
        float: right;
        top: 10px;
        right: 10px;
        border: 0px;
        width: 200px;
    }

    div.row-fluid {
        height: auto !important
    }

    label.control-label {
        margin-top: 10px;
    }

    fieldset {
        border: 1px solid #e5e5e5;
        border-radius: 4px;
    }

    legend {
        width: auto;
        line-height: normal;
        border: 0px;
    }
</style>
</head>
<body style="background-color:#FFF;">
<?
if(strlen($erro) > 0){
    echo "<div>$erro</div>";
}
?>
<form method="post" >
    <div class='container tc_container' style="width: 775px;">
        <div id="cabecalho">
            <span class="pull-left">
                <img class="logo" src="logos/cobimex_admin1.jpg" />
                <img class="logo" src="logos/cobimex_admin2.jpg" />
            </span>
            <span class="pull-right" style="margin-top: 12px;">
                <h2>Check List <?=$login_fabrica_nome?></h2>
            </span>
        </div>
        <br />
        <div id="conteudo">
            <fieldset>
                <legend>Teste Funcional</legend>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class='span11'>
                        <div class='control-group'>
                            <div class='controls controls-row'>
                                <label class="checkbox">
                                    <input <?=$_GET['imprimir'] ? "disabled='disabled'" : ""?>type="checkbox" name="conexao_padrao" value="sim" <?=(($checklist["teste_funcional"]["conexao_padrao"] == "sim") ? "checked" : "")?> /> Conecte Acessórios Padrões: Mangueira, gatilho e lança
                                </label>
                                <label class="checkbox">
                                    <input <?=$_GET['imprimir'] ? "disabled='disabled'" : ""?>type="checkbox" name="conexao_energia" value="sim" <?=(($checklist["teste_funcional"]["conexao_energia"] == "sim") ? "checked" : "")?> /> Conecte eletricidade e o suplemento de água
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class='span11'>
                        <div class='control-group'>
                            <label class='control-label'>A máquina iniciou?</label>
                            <div class='controls controls-row form-inline'>
                                <label class="radio">
                                    <input disabled="disabled" type="radio" name="inicio" value="sim" <?=(($checklist["teste_funcional"]["inicio"] == "sim") ? "checked" : "")?> /> Sim
                                </label>
                                <label class="radio">
                                    <input disabled="disabled" type="radio" name="inicio" value="nao" <?=(($checklist["teste_funcional"]["inicio"] == "nao") ? "checked" : "")?> /> Não
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class='span11'>
                        <div class='control-group'>
                            <label class='control-label'></label>
                            <div class='controls controls-row'>
                                <label class="checkbox">
                                    <input disabled="disabled" type="checkbox" name="teste_tss" value="sim" <?=(($checklist["teste_funcional"]["teste_tss"] == "sim") ? "checked" : "")?> /> Aperte e solte repetidamente o gatilho para testar o Sistema de Parada Total - TSS
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class='span11'>
                        <div class='control-group'>
                            <label class='control-label'>A máquina iniciou e, após o primeiro fechamento do gatilho, reiniciou?</label>
                            <div class='controls controls-row form-inline'>
                                <label class="radio">
                                    <input disabled="disabled" type="radio" name="reinicio" value="sim" <?=(($checklist["teste_funcional"]["reinicio"] == "sim") ? "checked" : "")?> /> Sim
                                </label>
                                <label class="radio">
                                    <input disabled="disabled" type="radio" name="reinicio" value="nao" <?=(($checklist["teste_funcional"]["reinicio"] == "nao") ? "checked" : "")?> /> Não
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class='span11'>
                        <div class='control-group'>
                            <label class='control-label'>Verifique se o cabo foi cortado ou está derretido</label>
                            <div class='controls controls-row form-inline'>
                                <label class="radio">
                                    <input disabled="disabled" type="radio" name="falha_tss_cabo" value="normal" <?=(($checklist["teste_funcional"]["falha_tss_cabo"] == "normal") ? "checked" : "")?> /> Normal
                                </label>
                                <label class="radio">
                                    <input disabled="disabled" type="radio" name="falha_tss_cabo" value="cortado" <?=(($checklist["teste_funcional"]["falha_tss_cabo"] == "cortado") ? "checked" : "")?> /> Cortado
                                </label>
                                <label class="radio">
                                    <input disabled="disabled" type="radio" name="falha_tss_cabo" value="derretido" <?=(($checklist["teste_funcional"]["falha_tss_cabo"] == "derretido") ? "checked" : "")?> /> Derretido
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class='span11'>
                        <div class='control-group'>
                            <label class='control-label'></label>
                            <div class='controls controls-row'>
                                <label class="checkbox">
                                    <input disabled="disabled" type="checkbox" name="falha_tss_desconecta_plugue_principal" value="sim" <?=(($checklist["teste_funcional"]["falha_tss_desconecta_plugue_principal"] == "sim") ? "checked" : "")?> /> Desligue a máquina e desconecte os plugues das entradas principais
                                </label>
                                <label class="checkbox">
                                    <input disabled="disabled" type="checkbox" name="falha_tss_abertura" value="sim" <?=(($checklist["teste_funcional"]["falha_tss_abertura"] == "sim") ? "checked" : "")?> /> Abra a máquina, para ter acesso ao sistema de TSS
                                </label>
                                <label class="checkbox">
                                    <input disabled="disabled" type="checkbox" name="falha_tss_desconecta_plugue_tss" value="sim" <?=(($checklist["teste_funcional"]["falha_tss_desconecta_plugue_tss"] == "sim") ? "checked" : "")?> /> Desconecte e levante a caixa do interruptor TSS
                                </label>
                                <label class="checkbox">
                                    <input disabled="disabled" type="checkbox" name="falha_tss_novo_teste" value="sim" <?=(($checklist["teste_funcional"]["falha_tss_novo_teste"] == "sim") ? "checked" : "")?> /> Teste a máquina novamente
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='row-fluid'>
                    <div class="span1"></div>
                    <div class='span11'>
                        <div class='control-group'>
                            <label class='control-label'>Qual o comportamento dos pistões do TSS após o novo teste?</label>
                            <div class='controls controls-row form-inline'>
                                <label class="radio">
                                    <input disabled="disabled" type="radio" name="pistoes" value="moveis" <?=(($checklist["teste_funcional"]["pistoes"] == "moveis") ? "checked" : "")?> /> Se moveram
                                </label>
                                <label class="radio">
                                    <input disabled="disabled" type="radio" name="pistoes" value="parados" <?=(($checklist["teste_funcional"]["pistoes"] == "parados") ? "checked" : "")?> /> Ficaram parados
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                if (!$_GET["imprimir"]) {
                ?>
                    <br />
                    <p class="tac" style="margin-bottom: 0px;">
                        <input class='btn btn-primary' type="submit"  name="gravar" value="Gravar" />
                    </p>
                    <br />
                <?php
                }
                ?>
            </fieldset>
        </div>
    </div>
</form>
</body>
</html>
