<?php

    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include "autentica_usuario.php";

    $retorno_codigo_interno = false;
    $box_codigo_interno     = false;
    if($_POST["verifica_codigo"]){

        $codigo_interno = trim($_POST["codigo_interno"]);
        $hd_chamado     = trim($_POST["hd_chamado"]);
        $produto        = trim($_POST["produto"]);

        $valida_codigo_interno = false;

        $valida_codigo_interno = (strlen(trim($codigo_interno)) < 8) ? false : true; 

    if ($valida_codigo_interno === true) {
    
        if(strlen($produto) > 0){
            $cod_interno_bd = "";
            $valida_codigo_interno = false;
            if (strlen(trim($codigo_interno) > 0)) {
                $login_fabrica_ci = (substr(trim($codigo_interno,-1)) == 1) ? 11 : 172;

                $sql_ci = " SELECT JSON_FIELD('codigo_interno',parametros_adicionais) AS cod_interno_bd
                            FROM tbl_produto WHERE produto = $produto AND fabrica_i in (172,11)  AND ativo IS TRUE";

                $res_ci = pg_query($con,$sql_ci);
                $cod_interno_bd = pg_result($res_ci,0,'cod_interno_bd');
                   
                   if (strlen(trim($codigo_interno)) == strlen(trim($cod_interno_bd))){
                        $sub_cod_interno_bd = substr($cod_interno_bd,-1);
                        $sub_codigo_interno = substr($codigo_interno,-1);
                        if ($sub_codigo_interno == $sub_cod_interno_bd){
                            $valida_codigo_interno = true;
                        }
                    }
                            if ($valida_codigo_interno == false){
                                $sql_prod = " SELECT JSON_FIELD('codigo_interno',parametros_adicionais) AS cod_interno_bd
                                            FROM tbl_produto WHERE referencia = (SELECT referencia FROM tbl_produto 
                                            WHERE fabrica_i in (172,11) AND produto = $produto) AND fabrica_i = $login_fabrica_ci  AND ativo IS TRUE";

                                $res_prod = pg_query($con,$sql_prod);
                                if (pg_num_rows($res_prod) > 0) {
                                    $cod_interno_bd = pg_result($res_prod,0,'cod_interno_bd');
                                   
                                    if (strlen(trim($codigo_interno)) == strlen(trim($cod_interno_bd))){
                                        $sub_cod_interno_bd = substr($cod_interno_bd,-1);
                                        $sub_codigo_interno = substr($codigo_interno,-1);
                                        
                                        if ($sub_codigo_interno == $sub_cod_interno_bd) {
                                            $valida_codigo_interno = true;
                                        }else{
                                          $valida_codigo_interno = false;  
                                        }     
                                    }else{
                                        $valida_codigo_interno = false;
                                    }
                                }else{
                                    $valida_codigo_interno = false;
                                }
                            }                  
            }
        }
    }

        if($valida_codigo_interno === true) {

            $codigo_fabricante      = (strlen(trim($codigo_interno)) > 0) ? $codigo_interno[strlen($codigo_interno) - 1] : "";
            $fabrica_codigo_interno = (strlen(trim($codigo_interno)) == 0 || $codigo_fabricante == 1) ? 11 : 172; /* Lenoxx | Pacific */  

            if(strlen(trim($produto)) > 0){

                $sql_produto = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$fabrica_codigo_interno} AND produto = {$produto} and ativo";
                $res_produto = pg_query($con,$sql_produto);

                if(pg_num_rows($res_produto) == 0){

                    $fabrica_produto = ($fabrica_codigo_interno == 172) ? 11 : 172;

                    $sql_produto = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$fabrica_codigo_interno} AND referencia = (SELECT referencia FROM tbl_produto WHERE produto = {$produto} AND fabrica_i = {$fabrica_produto}) and ativo";
                    $res_produto = pg_query($con,$sql_produto);

                    if(pg_num_rows($res_produto) > 0){

                        $produto = pg_fetch_result($res_produto, 0, "produto");

                    }

                }

            }

            if(strlen($hd_chamado) > 0){

                $sql = "SELECT fabrica FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado} AND fabrica = {$fabrica_codigo_interno}";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) == 0){

                    $sql = "SELECT produto FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
                    $res = pg_query($con, $sql);

                    $produto = pg_fetch_result($res, 0, "produto");

                    if(strlen($produto) > 0){

                        $sql = "SELECT 
                                    produto 
                                FROM tbl_produto 
                                WHERE 
                                    referencia IN (SELECT referencia FROM tbl_produto WHERE produto = {$produto}) 
                                    AND  fabrica_i = {$fabrica_codigo_interno}";
                        $res = pg_query($con, $sql);

                        $produto = pg_fetch_result($res, 0, "produto");

                    }

                    $sql = "UPDATE tbl_hd_chamado SET fabrica = {$fabrica_codigo_interno}, fabrica_responsavel = {$fabrica_codigo_interno} WHERE hd_chamado = {$hd_chamado}";
                    $res = pg_query($con, $sql);

                    $sql = "UPDATE tbl_hd_chamado_extra SET produto = {$produto} WHERE hd_chamado = {$hd_chamado}";
                    $res = pg_query($con, $sql);

                }

            }

            $retorno_codigo_interno = true;

        }else{
            $box_codigo_interno = true;
        }

    }

    if($_POST["verifica_codigo_produto"]){

        $referencia_produto = trim($_POST["referencia_produto"]);
        $hd_chamado         = trim($_POST["hd_chamado_input"]);

        $sql_produto = "SELECT produto, fabrica_i FROM tbl_produto WHERE referencia = '{$referencia_produto}' AND fabrica_i IN (11, 172) and ativo ORDER BY fabrica_i ASC";
        $res_produto = pg_query($con, $sql_produto);

        if(pg_num_rows($res_produto) == 0){

            $msg_erro = "Produto não encontrado!";

        }else{

            $box_codigo_interno = true;
            $cont_fabrica       = pg_num_rows($res_produto);
            $codigo_interno_172 = false;

            if($cont_fabrica > 0){

                $fabrica = pg_fetch_result($res_produto, 0, "fabrica_i");
                $produto = pg_fetch_result($res_produto, 0, "produto");

                if($cont_fabrica == 1 && $fabrica == 172){

                    $codigo_interno_172 = true;

                }

            }

            /* $fabrica_codigo_interno = 11; // Lenoxx

            if(strlen($hd_chamado) > 0){

                $sql = "SELECT fabrica FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado} AND fabrica = {$fabrica_codigo_interno}";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) == 0){

                    $sql = "SELECT produto FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
                    $res = pg_query($con, $sql);

                    $produto = pg_fetch_result($res, 0, "produto");

                    if(strlen($produto) > 0){

                        $sql = "SELECT 
                                    produto 
                                FROM tbl_produto 
                                WHERE 
                                    referencia IN (SELECT referencia FROM tbl_produto WHERE produto = {$produto}) 
                                    AND  fabrica_i = {$fabrica_codigo_interno}";
                        $res = pg_query($con, $sql);

                        $produto = pg_fetch_result($res, 0, "produto");

                    }

                    $sql = "UPDATE tbl_hd_chamado SET fabrica = {$fabrica_codigo_interno}, fabrica_responsavel = {$fabrica_codigo_interno} WHERE hd_chamado = {$hd_chamado}";
                    $res = pg_query($con, $sql);

                    $sql = "UPDATE tbl_hd_chamado_extra SET produto = {$produto} WHERE hd_chamado = {$hd_chamado}";
                    $res = pg_query($con, $sql);

                }

            } */

        }

    }

?>

<!DOCTYPE html>
<html>
    <head>
        <title>INSERIR O MODELO DO PRODUTO</title>


        <link type="text/css" rel="stylesheet" media="screen" href="admin/bootstrap/css/bootstrap.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="admin/bootstrap/css/extra.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="admin/css/tc_css.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="admin/css/tooltips.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
        <link type="text/css" rel="stylesheet" media="screen" href="admin/bootstrap/css/ajuste.css" />

        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
        <script src="bootstrap/js/bootstrap.js"></script>
        <script src='plugins/posvenda_jquery_ui/development-bundle/ui/jquery.ui.autocomplete.js'></script>


        <style>
            .box-1 h3{
                margin-top: 100px;
            }
            .body{
                margin: 20px;
            }
        </style>

        <?php if(strlen($fabrica_codigo_interno) > 0 && $retorno_codigo_interno === true){ ?>
        <script>
            window.parent.retorno_codigo_interno(<?php echo $fabrica_codigo_interno; ?>, <?php echo $codigo_interno; ?>, <?php echo $produto; ?>);


        </script>
        <?php
        }
        ?>

        
        <script>

            $(function(){
                $("#referencia_produto").autocomplete({
                    //(arquivo, array, objeto) que irá buscar os resultados
                    source: "autocomplete_produto_referencia.php",
                    //Função de select do autocomplete
                    //Caracteres necessarios para começar a pesquisa
                    minLength: 2,
                    select: function (event, ui) {
                        $("#referencia_produto").val(ui.item["cod"]);
                        //Para a função de select
                        return false;
                    }
                }).data("uiAutocomplete")._renderItem = function (ul, item) {
                    //Função para modificar a forma de mostrar

                    //Joga para dentro da var o que será mostrado
                    var text = item["cod"] + " - " + item["desc"];
                    return $("<li></li>").data("item.autocomplete", item).append("<a>"+text+"</a>").appendTo(ul);
                };
            });

        </script> 
        

    </head>
    <body>

        <div class="box-1" <?php echo ($box_codigo_interno === false) ? "style='display: block;'" : "style='display: none;'"; ?> >

            <h3 class="tac">Inserir o Modelo do Produto</h3>

            <div class="body">

                <?php 
                if(strlen($msg_erro) > 0){
                    echo "<div class='alert alert-danger tgac'> {$msg_erro} </div>";
                }
                ?>

                <div class='row-fluid'>
                    <div class="span4"></div>
                    <div class="span4">
                         <form method="post" action="verifica_codigo_interno.php">
                            <input type="hidden" name="verifica_codigo_produto" value="sim">
                            <input type="hidden" name="hd_chamado_input" value="<?php echo $_REQUEST["hd_chamado"]; ?>">
                            <strong>Referência do Produto</strong> <br />
                            <input id="referencia_produto" type="text" name="referencia_produto" class="span12" value="<?php echo $_REQUEST["referencia_produto"]; ?>"> <br />
                            <button type="submit" class="btn btn-primary btn-block">Verificar Produto</button>
                        </form>
                    </div>
                </div>

                <!-- 
                <div class='row-fluid'>
                    <div class="span3"></div>
                    <div class="span3">
                        <button type="button" class="btn btn-primary btn-block" onclick="mostra_codigo_interno();">Sim</button>
                    </div>
                    <div class="span3">
                         <form method="post" action="verifica_codigo_interno.php">
                            <input type="hidden" name="verifica_codigo_input" value="sim">
                            <input type="hidden" name="hd_chamado_input" value="<?php echo $_GET["hd_chamado"]; ?>">
                            <button type="submit" class="btn btn-danger btn-block">Não</button>
                        </form>
                    </div>
                </div> 
                -->

            </div>

        </div>

        <div class="box-2" <?php echo ($box_codigo_interno === true) ? "style='display: block;'" : "style='display: none;'"; ?>>

            <h3 class="tac">Código Interno</h3>

            <div class="body">

                <?php if (strlen($codigo_interno) > 0 && $valida_codigo_interno === false) { ?>
                <div class="alert alert-danger">
                    <h4>Código Interno Inválido</h4>
                </div>
                <?php } ?>

                <form method="post" action="verifica_codigo_interno.php">

                    <input type="hidden" name="produto" value="<?php echo $produto; ?>">
                    <input type="hidden" name="verifica_codigo" value="sim">
                    <input type="hidden" name="hd_chamado" value="<?php echo $hd_chamado; ?>">

                    <div class='row-fluid'>
                        <div class="span12">
                            <div class="alert alert-warning tac">
                                Para dar continuidade a Ordem de Serviço é necessário pesquisar o código interno do produto.
                            </div>
                        </div>
                    </div>

                    <div class='row-fluid'>
                        <div class="span4"></div>
                        <div class='span4'>
                            <div class='control-group'>
                                <label class='control-label' for='ie'>Código Interno do Produto</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                        <input id="codigo_interno" type="text" name="codigo_interno" class='span12' value="<?php echo $codigo_interno; ?>" maxlength="15">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class='row-fluid'>
                        <div class="span4"></div>
                        <div class='span4'>
                            <div class='control-group'>
                                <input class='btn btn-primary btn-block' type="submit"  name="gravar" value="Inserir Código Interno" />
                            </div>
                        </div>
                    </div>

                    <?php if($codigo_interno_172 === false){ ?>
                    <div class='row-fluid'>
                        <div class="span4"></div>
                        <div class='span4'>
                            <button type="button" class="btn btn-danger btn-block" onclick="window.parent.retorno_codigo_interno(11, '', <?php echo $produto; ?>);"> Não possui Código Interno </button>
                        </div>
                    </div>
                    <?php } ?>

                </form>

            </div>

        </div>

    </body>
</html>
