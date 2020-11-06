<?php
    $areaAdmin = isset($_REQUEST['area_admin']) ? true : false;
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include_once "class/tdocs.class.php";

    if ($areaAdmin === true and !empty($area_admin)) {
        $admin_privilegios = "call_center";
        include __DIR__.'/admin/autentica_admin.php';
    } else {
        include __DIR__.'/autentica_usuario.php';
    }
    $tDocs   = new TDocs($con, $login_fabrica);
    $extrato = $_GET["extrato"];

    if(isset($_POST["gravar_nf_servico"])){

        $nota_servico = $_POST["nota_servico"];
        $serie        = $_POST["serie"];
        $data_emissao = $_POST["data_emissao"];
        $extrato      = $_POST["extrato"];
        $anexo        = $_FILES["anexo"];
        $msg_erro = "";
        $msg      = "";

        if (empty($nota_servico)) {
            $msg_erro .= "Por favor insira a Nota Fiscal de Serviço <br />";
        }

        if (empty($serie)){
            $msg_erro .= "Por favor informe a Série da Nota Fiscal de Serviço <br />";
        }

        if (empty($data_emissao)) {
            $msg_erro .= "Por favor insira a Data de Emissão <br />";
        }

        if (!empty($anexo)) {
            $ext = strtolower(preg_replace("/.+\./", "", $anexo["name"]));
            if (!in_array($ext, array("pdf", "doc", "gif", "png", "jpg", "jpeg"))) {
                $msg_erro .=  "Tipo de arquivo não permitido";
            }
        }
 
        list($dia, $mes, $ano) = explode("/", $data_emissao);

        //monteiro
        if (!checkdate($mes, $dia, $ano)) {
            $msg_erro .= "Data Inválida <br/ >";
        }
        if(strlen(trim($ano)) < 4 OR strlen(trim($mes)) < 2 OR strlen(trim($dia)) < 2){
            $msg_erro .= "Data Inválida <br/ >";
        }

        if (strlen(trim($msg_erro)) == 0) {
            $data_emissao = $ano."-".$mes."-".$dia;


            if ($login_fabrica == 151) {
                $sql_agrupado = "SELECT codigo FROM tbl_extrato_agrupado WHERE extrato = $extrato";
                $res_agrupado = pg_query($con, $sql_agrupado);
                if (pg_num_rows($res_agrupado) > 0) {
                    $codigo = pg_fetch_result($res_agrupado, 0, 'codigo');
                    $sql_extratos = "SELECT extrato FROM tbl_extrato_agrupado WHERE codigo = '$codigo'";
                    $res_extratos = pg_query($con, $sql_extratos);
                    $extratos = pg_fetch_all($res_extratos);

                    foreach ($extratos as $key => $value) {
                        $extrato = $value['extrato'];
                        $sql = "SELECT extrato_pagamento FROM tbl_extrato_pagamento WHERE extrato = {$extrato}";
                        $res = pg_query($con, $sql);

                        if(pg_num_rows($res) == 0){
                            $sql = "INSERT INTO tbl_extrato_pagamento (extrato, nf_autorizacao, serie_nf, data_nf) VALUES ({$extrato}, '{$nota_servico}', '{$serie}', '{$data_emissao}')";
                        }else{
                            $sql = "UPDATE tbl_extrato_pagamento SET nf_autorizacao = '{$nota_servico}', serie_nf = '{$serie}', data_nf = '{$data_emissao}' WHERE extrato = {$extrato}";
                        }

                        $res = pg_query($con, $sql);
                        if (strlen(pg_last_error()) > 0) {
                            $msg_erro .= "Erro ao inserir a NF de Serviço ao extrato - $extrato <br>";
                        } else {
                            if (!empty($anexo)) {
                                $anexoID = $tDocs->uploadFileS3($anexo, $extrato, true, "lgr", "nfservico");
                                if (!$anexoID) {
                                    $msg_erro .= 'Erro ao fazer upload do arquivo! <br>';
                                }
                            }
                        }
                    }

                    if (empty($msg_erro)) {
                        $msg .= "NF de Serviço inserida com Sucesso";
                        echo '
                                <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
                                <script type="text/javascript">
                                    $(function(){
                                        window.parent.insere_nf_servico('.$extrato.', '.$nota_servico.');
                                        setTimeout(function(){ parent.location.reload(); }, 2000);
                                    });
                                </script>';
                    }
                } else {
                    $sql = "SELECT extrato_pagamento FROM tbl_extrato_pagamento WHERE extrato = {$extrato}";
                    $res = pg_query($con, $sql);

                    if(pg_num_rows($res) == 0){
                        $sql = "INSERT INTO tbl_extrato_pagamento (extrato, nf_autorizacao, serie_nf, data_nf) VALUES ({$extrato}, '{$nota_servico}', '{$serie}', '{$data_emissao}')";
                    }else{
                        $sql = "UPDATE tbl_extrato_pagamento SET nf_autorizacao = '{$nota_servico}', serie_nf = '{$serie}', data_nf = '{$data_emissao}' WHERE extrato = {$extrato}";
                    }

                    $res = pg_query($con, $sql);
                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro .= "Erro ao inserir a NF de Serviço";
                    } else {
                        if (!empty($anexo)) {
                            $anexoID = $tDocs->uploadFileS3($anexo, $extrato, true, "lgr", "nfservico");
                            if (!$anexoID) {
                                $msg_erro .= 'Erro ao fazer upload do arquivo!';
                            }
                        }

                        if (empty($msg_erro)) {
                            $msg .= "NF de Serviço inserida com Sucesso";
                            echo '
                                    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
                                    <script type="text/javascript">
                                            $(function(){
                                                window.parent.insere_nf_servico('.$extrato.', '.$nota_servico.');
                                                setTimeout(function(){ parent.location.reload(); }, 2000);
                                            });
                                       </script>';
                        }
                    }
                }
            } else {
                
                $sql = "SELECT extrato_pagamento FROM tbl_extrato_pagamento WHERE extrato = {$extrato}";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) == 0){
                    $sql = "INSERT INTO tbl_extrato_pagamento (extrato, nf_autorizacao, serie_nf, data_nf) VALUES ({$extrato}, '{$nota_servico}', '{$serie}', '{$data_emissao}')";
                }else{
                    $sql = "UPDATE tbl_extrato_pagamento SET nf_autorizacao = '{$nota_servico}', serie_nf = '{$serie}', data_nf = '{$data_emissao}' WHERE extrato = {$extrato}";
                }

                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro .= "Erro ao inserir a NF de Serviço";
                } else {
                    if (!empty($anexo)) {
                        $anexoID = $tDocs->uploadFileS3($anexo, $extrato, true, "lgr", "nfservico");
                        if (!$anexoID) {
                            $msg_erro .= 'Erro ao fazer upload do arquivo!';
                        }
                    }

                    $msg .= "NF de Serviço inserida com Sucesso";
                    echo '
                            <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
                            <script>
                                $(function(){
                                    window.parent.insere_nf_servico('.$extrato.', '.$nota_servico.');
                                    setTimeout(function(){ parent.location.reload(); }, 2000);
                                });
                           </script>';
                }
            }
        }
    }


    if(strlen($extrato) > 0){

        $sql = "SELECT nf_autorizacao, serie_nf, data_nf FROM tbl_extrato_pagamento WHERE extrato = {$extrato}";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $nf_autorizacao = pg_fetch_result($res, 0, "nf_autorizacao");
            $serie_nf       = pg_fetch_result($res, 0, "serie_nf");
            $data_nf        = pg_fetch_result($res, 0, "data_nf");

            list($ano, $mes, $dia) = explode("-", $data_nf);
            $data_nf = $dia."/".$mes."/".$ano;
        }
    }
?>

<!DOCTYPE html>

<html>
    <hed>
        <title>Inserir número de Nota de Serviço</title>
        <style>

            @import "plugins/jquery/datepick/telecontrol.datepick.css";

            .container{
                background-color: #ffffff;
            }
            .form{
                font-size: 14px;
                padding: 20px;
                font-family: arial;
                height: 200px;
            }
            .form input{
                width: 100%;
                padding-top: 8px;
                padding-bottom: 8px;
                border: 1px solid #999;
                border-radius: 5px;
            }
            .form button{
                padding: 8px;
                width: 150px;
                text-align: center;

            }
            .box-success{
                background-color: #3ADF00;
                border: 1px solid #3ADF00;
                width: 93%;
                padding: 10px;
                color: #ffffff;
                text-align: center;
                font-weight: bold;
                border-radius: 5px;
            }
            .box-erro{
                background-color: #d90000;
                border: 1px solid #d90000;
                width: 93%;
                padding: 10px;
                color: #ffffff;
                text-align: center;
                font-weight: bold;
                border-radius: 5px;
            }
            .form > table > tr > td{
                padding: 10px;
            }
            .thumb{
                width: 90px;
                border: solid 1px #cccccc;
                padding: 5px;
            }
            #ZoomBox{
                background-color: #ffffff !important;
                z-index: 9999999;
            }
        </style>

        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>

        <script src='plugins/jquery.mask.js'></script>

        <script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
        <script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
        <script type='text/javascript' src='js/FancyZoom.js'></script>
        <script type='text/javascript' src='js/FancyZoomHTML.js'></script>
        <script>
            $(function(){
                $('.data_emissao').datepick({startDate:'01/01/2000'});
                $(".data_emissao").mask("99/99/9999");

            });
        </script>

    </hed>
    <body onload="setupZoom()" style="background-color: #ffffff;">
        <div class="container">
            <div class="form">
                <?php if (strlen($msg_erro) > 0) {?>
                    <div class="box-erro"><?php echo $msg_erro;?></div>
                <?php }?>
                <?php if (strlen($msg) > 0) {?>
                    <div class="box-success"><?php echo $msg;?></div>
                <?php }?>

                <form METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' >
                    <input type="hidden" name="gravar_nf_servico" value="true">
                    <input type="hidden" name="extrato" value="<?=$extrato;?>">
                    <input type="hidden" name="area_admin" value="<?=$areaAdmin;?>">

                    <table style="width: 440px;" cellspacing="10">
                        <tr>
                            <td colspan="2">

                                <strong>Nota Fiscal de Serviço</strong> <br />
                                <input type="text" name="nota_servico" class="nota_servico" maxlength="9" value="<?=$nf_autorizacao?>" />

                            </td>
                        </tr>
                        <tr>
                            <td>

                                <strong>Série</strong> <br />
                                <input type="text" name="serie" class="serie" maxlength="3" value="<?=$serie_nf?>" />

                            </td>
                            <td>

                                <strong>Data de Emissão</strong> <br />
                                <input type="text" name="data_emissao" class="data_emissao" maxlength="20" value="<?=$data_nf?>" />

                            </td>
                        </tr>
                        <?php if ($login_fabrica == 101) {?>
                            <tr>
                                <td colspan="2" align="center">
                                    <strong>Anexo</strong> <br />
                                    <input type="file" name="anexo" class="anexo" />
                                </td>
                            </tr>
                            <?php 
                                $xxAnexo = $tDocs->getDocumentsByRef($extrato, "lgr", "nfservico")->url;
                                if (!empty($xxAnexo)) {
                            ?>
                            <tr>
                                <td colspan="2" align="center">
                                    <strong>Anexo Atual</strong> <br />
                                    <?php echo '<a href="'.$xxAnexo.'"><img class="thumb" src="'.$xxAnexo.'" /></a>';?>
                                </td>
                            </tr>
                            <?php }?>
                        <?php }?>
                        <tr>
                            <td colspan="2" align="center">
                                <button type="submit" class="gravar" style="cursor: pointer;">Gravar</button>
                            </td>
                        </tr>
                    </table>

                </form>
            </div>
        </div>
    </body>
</html>
