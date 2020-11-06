<?php 
$pesquisa  = 117;

if ($_POST["responder"] == 1) {
    $erro      = "";
    $success   = "";
    $msg_erro  = array();
    $perguntas = $_POST["perguntas"];
    $campo     = "";
    $valor     = "";

    if (count($perguntas) == 0) {
        $msg_erro["msg"][] = "Responda a Pesquisa";
    }

    $ocultaPerguntas = array();
    if (count($msg_erro["msg"]) == 0) {
        foreach ($perguntas as $numero_pergunta => $id_pergunta) {
            if ($numero_pergunta == 3) {
                foreach ($id_pergunta as $item => $resposta) {
                    if ($resposta["resposta"]["respondida"] == "Sim") {
                        unset($perguntas[4]);
                        unset($perguntas[5]);
                        $ocultaPerguntas = array(4,5);
                    }
                }
            } elseif ($numero_pergunta == 5) {
                foreach ($id_pergunta as $item => $resposta) {
                    if ($resposta["resposta"]["respondida"] == "Não") {
                        unset($perguntas[6]);
                        unset($perguntas[7]);
                        $ocultaPerguntas = array(6,7);
                    }
                }
            }
        }
    }
    $sqlVerificaJaRespondido  = "SELECT * FROM tbl_resposta WHERE posto={$login_posto} AND pesquisa = {$pesquisa}";
    $resVerificaJaRespondido  = pg_query($con, $sqlVerificaJaRespondido);
    
    if (pg_num_rows($resVerificaJaRespondido) > 0) {
        $msg_erro["msg"][] = "Pesquisa já respondida.";
    }

    if (count($msg_erro["msg"]) == 0) {

        pg_prepare($con, 'insere_resposta',"INSERT INTO tbl_resposta (
                                                                        pergunta,
                                                                        observacao,
                                                                        txt_resposta,
                                                                        tipo_resposta_item,
                                                                        pesquisa,
                                                                        posto
                                                                     ) VALUES (
                                                                        $1,
                                                                        $2,
                                                                        $3,
                                                                        $4,
                                                                        $5,
                                                                        $6
                                                                     )");

        $res  = pg_query($con, "BEGIN TRANSACTION");

        foreach ($perguntas as $k => $pergunta) {

            foreach ($pergunta as $id_pergunta => $conteudo) {

                $tipo_resposta_item = $conteudo["resposta"]["id_resposta"];
                $txt_resposta       = $conteudo["resposta"]["respondida"];
                $observacao         = $conteudo["resposta"]["extra"];

                if (empty($txt_resposta) && count($conteudo["resposta"]) != 7) {
                    $msg_erro["campo"][] = $id_pergunta;
                    continue;
                }

                if (count($observacao) > 0) {
                    foreach ($observacao as $key => $extra) {
                        if (strlen($extra) == 0) {
                            continue;
                        }
                        $observacao = $extra;
                    }
                }
                if (is_array($observacao)) {
                    $observacao = "";
                }


                if (count($conteudo["resposta"]) == 7) {

                    foreach ($conteudo["resposta"] as $key => $rows) {

                        if (empty($rows["respondida"])) {
                            $msg_erro["campo"][] = $id_pergunta;
                            continue;
                        }
                        $res = pg_execute($con, 'insere_resposta', array($id_pergunta, '', $rows["respondida"], $rows["id_resposta"], $pesquisa, $login_posto));
                        if (strlen(pg_last_error($con)) > 0) {
                            $msg_erro["msg"][] = pg_last_error($con);
                        }
                    }

                } else {
                    $res = pg_execute($con, 'insere_resposta', array($id_pergunta, $observacao, $txt_resposta, $tipo_resposta_item, $pesquisa, $login_posto));
                    if (strlen(pg_last_error($con)) > 0) {
                        $msg_erro["msg"][] = pg_last_error($con);
                    }
                }
            }
        }
                          
        if (count($msg_erro["msg"]) == 0 && count($msg_erro["campo"]) == 0) {
            $success = "Pesquisa respondida com sucesso";
            echo header("Location: menu_inicial.php");
            pg_query($con, "COMMIT");
        } else {
            if (count($msg_erro["campo"]) == 0) {
                $msg_erro["msg"][] = "Não foi possível responder";
            }
           pg_query($con, "ROLLBACK");
        }

    }
}

$array_abc = array("a", "b", "c", "d", "e", "f", "g", "h");
$plugins = array(
    "price_format",
    "mask",
);

include("plugin_loader.php");
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Pesquisa da TELECONTROL em parceria com a STANLEY BLACK & DECKER</title>
        <meta charset="iso-8859-1">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="description" content="Demo project">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/bootstrap.css" />
        <link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/extra.css" />
        <link media="screen" type="text/css" rel="stylesheet" href="css/tc_css.css" />
        <link media="screen" type="text/css" rel="stylesheet" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
        <link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/ajuste.css" />
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
        <script type="text/javascript" src="plugins/jquery.mask.js"></script>
        <script type="text/javascript" src="plugins/price_format/jquery.price_format.1.7.min.js"></script>
        <script type="text/javascript" src="plugins/price_format/config.js"></script>
        <script type="text/javascript" src="plugins/price_format/accounting.js"></script>
        <script type="text/javascript" src="bootstrap/js/bootstrap.js"></script>
        <style>
            body{background-color: #eeeeee;}
            .container{background-color: #ffffff;}
            .container-dois{padding: 20px;border: 3px solid #373865;font-size: 16px !important;}
            .descricao{font-size: 23px !important;line-height: 23px !important;text-align: center;}
            .subdescricao{line-height: 21px !important;}
            .titulo-box{background-color: #FFD20A;padding: 10px 20px;color: #201F3C;font-size: 18px !important;}
            .conteudo-box{line-height:39px;font-size: 16px !important;border: solid 2px #FFD20A;padding: 20px;}
            .no-margin-bottom{margin-bottom: 0px !important;}
            input{margin-top: 8px;}
            .ajuste{margin-top: 6px;display: inline !important;position: absolute;}
            .input-prepend input{margin-top: 0px;}
            .btn-responder:hover,
            .btn-responder:active,
            .btn-responder.active,
            .btn-responder.disabled,
            .btn-responder[disabled] {
              color: #ffffff;
              background-color: #373865;
              *background-color: #373865;
            }
            label{font-size: 16px !important;display: inline}
            .btn-responder {
                color: #FFD20A;
                text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
                background-color: #373865;
                background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#373865), to(#2b2c50));
                background-image: -webkit-linear-gradient(top, #373865, #2b2c50);
                background-image: -o-linear-gradient(top, #373865, #2b2c50);
                background-image: linear-gradient(to bottom, #373865, #2b2c50);
                background-image: -moz-linear-gradient(top, #373865, #2b2c50);
                background-repeat: repeat-x;
                border-color: #2b2c50;
            }

            .btn-responder:active,
            .btn-responder.active {
              background-color: #373865 \9;
            }
            .campo_require{background-color: #b94a48;color: #ffffff;}
            .campo_xrequire{border-color: #b94a48;}
        </style>
        <script type="text/javascript" charset="utf-8">
            $(function(){
                 $(".preco_format").priceFormat({
                    prefix: '',
                    thousandsSeparator: '',
                    centsSeparator: '.',
                    centsLimit: 2
                });
         
                $(".campo_pergunta_3").on("click", function(){
                    var posto_controla = $(this).val();

                    if (posto_controla == "Sim") {

                        $(".pergunta_4").hide('slow');
                        $(".pergunta_5").hide('slow');

                    } else {

                        $(".pergunta_4").show('slow');
                        $(".pergunta_5").show('slow');
                    }

                });
                $(".campo_pergunta_5").on("click", function(){

                    var estaria_disposto = $(this).val();
                    if (estaria_disposto == "Não") {

                        $(".pergunta_6").hide('slow');
                        $(".pergunta_7").hide('slow');

                    } else {

                        $(".pergunta_6").show('slow');
                        $(".pergunta_7").show('slow');

                    }

                });
            });
        </script>
</head>
<body>

    <form action="" method="post">
        <input type="hidden" name="responder" value="1">
        <div class="container">
            <div class="container-dois">
                <br />
                <div class="row-fluid">
                    <div class="span3"></div>
                    <div class="span3">
                        <img src="logos/telecontrol_new.gif" alt="">
                    </div>
                    <div class="span3">
                        <img src="logos/logo_black_2017.png" style="margin-top: 10px" alt="">
                    </div>
                    <div class="span3"></div>
                </div><br /><br />
                <p class="descricao">A <b>TELECONTROL</b> em parceria com a <b>STANLEY BLACK & DECKER</b> gostaria de conhecer um pouco mais sobre VOCÊ!</p><br />
                <p class="subdescricao">Nosso objetivo é compreender as oportunidades e carências da Rede Autorizada, e claro, buscar contribuir com vantagens e facilitar a gestão da Assistência Técnica.
                Esta Pesquisa será rápida! Levará até 2 minutos para ser concluída.</p><br />

                <?php if (count($msg_erro["msg"]) > 0) {?>
                    <div class="alert alert-danger">
                        <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
                    </div>
                <?php }?>
                <?php if (count($msg_erro["campo"]) > 0) {?>
                    <div class="alert alert-danger">
                        <h4>Responda as perguntas obrigatórias</h4>
                    </div>
                <?php }?>
                <?php if (strlen($success) > 0) {?>
                    <div class="alert alert-success">
                        <h4><?php echo $success;?></h4>
                    </div>
                <?php }?>

                <?php 

                    $sqlPesquisaPrincipal = "SELECT * FROM tbl_pesquisa WHERE fabrica = $login_fabrica AND pesquisa = $pesquisa;";
                    $resPesquisaPrincipal = pg_query($con, $sqlPesquisaPrincipal);

                    if (pg_num_rows($resPesquisaPrincipal) > 0) {
                        $rowsPesquisa = pg_fetch_assoc($resPesquisaPrincipal);

                        $sqlPerguntas = "SELECT tbl_pergunta.pergunta, tbl_pergunta.descricao,
                                                tbl_pergunta.tipo_resposta,
                                                tbl_pesquisa_pergunta.ordem
                                           FROM tbl_pergunta 
                                           JOIN tbl_pesquisa_pergunta USING(pergunta)
                                          WHERE tbl_pergunta.fabrica = $login_fabrica 
                                            AND tbl_pesquisa_pergunta.pesquisa = ".$rowsPesquisa['pesquisa']."
                                       ORDER BY tbl_pesquisa_pergunta.ordem ASC";
                        $resPerguntas = pg_query($con, $sqlPerguntas);

                        
                        while($rowsPerguntas = pg_fetch_assoc($resPerguntas)) {
                            $sqlTipoResposta = "SELECT tbl_tipo_resposta_item.tipo_resposta_item,tbl_tipo_resposta_item.descricao,
                                                       tbl_tipo_resposta.tipo_descricao
                                                  FROM tbl_tipo_resposta 
                                                  JOIN tbl_tipo_resposta_item USING(tipo_resposta)
                                                 WHERE fabrica = $login_fabrica
                                                   AND tipo_resposta =".$rowsPerguntas["tipo_resposta"];
                            $resTipoResposta = pg_query($con, $sqlTipoResposta);
                           
                            if ($rowsPerguntas["ordem"] != 8) {

                                if (in_array($rowsPerguntas["pergunta"], $msg_erro["campo"])) { 
                                    $campoObrigatorio =  "campo_require";
                                    $campoObrigatoriox =  "campo_xrequire";
                                } else {
                                    $campoObrigatorio = "";
                                    $campoObrigatoriox = "";
                                }
                            } else {
                                $campoObrigatorio = "";
                                $campoObrigatoriox = "";
                            }
                ?>

                <div <?php echo (in_array($rowsPerguntas["ordem"], $ocultaPerguntas)) ? "style='display:none !important;'" : "" ;?> class="titulo-box pergunta_<?php echo $rowsPerguntas["ordem"];?> <?php echo $campoObrigatorio;?>">
                    <p class="no-margin-bottom"><?php echo $rowsPerguntas["descricao"];?></p>
                </div>
                <div  <?php echo (in_array($rowsPerguntas["ordem"], $ocultaPerguntas)) ? "style='display:none !important;'" : "" ;?> class="conteudo-box pergunta_<?php echo $rowsPerguntas["ordem"];?> <?php echo $campoObrigatoriox;?>">
                    <input type="hidden" name="perguntas[<?php echo $rowsPerguntas["ordem"];?>]" value="<?php echo $rowsPerguntas["pergunta"];?>">
                    <?php 
                    if (pg_num_rows($resTipoResposta) > 0) {
                        $i = 0;
                        while ($rowsTipoResposta = pg_fetch_array($resTipoResposta)) {
                        $extra = '';
                        $xvalue = '';
                            if ($rowsTipoResposta["tipo_descricao"] == "radio") {
                                $checked =  ($nivel_satisfacao == "Excelente") ? 'checked' : '';
                            
                                if ($rowsPerguntas["ordem"] == 1) {
                                    if ($array_abc[$i] == "e") {

                                        $xvalue = $_POST['perguntas'][$rowsPerguntas["ordem"]][$rowsPerguntas["pergunta"]]['resposta']['extra'];
                                        $extra = ' <input type="text" name="perguntas['.$rowsPerguntas["ordem"].']['.$rowsPerguntas["pergunta"].'][resposta][extra]" value="'.$xvalue.'">';
                                    }
                                }
                                if ($rowsPerguntas["ordem"] == 2) {
                                    if ($array_abc[$i] == "a") {
                                        $xvalue = $_POST['perguntas'][$rowsPerguntas["ordem"]][$rowsPerguntas["pergunta"]]['resposta']['extra'];

                                        $extra = ' <input type="text" name="perguntas['.$rowsPerguntas["ordem"].']['.$rowsPerguntas["pergunta"].'][resposta][extra]" value="'.$xvalue.'">';
                                    }
                                }
                                if ($rowsPerguntas["ordem"] == 4) {
                                    if ($array_abc[$i] == "e") {
                                        $xvalue = $_POST['perguntas'][$rowsPerguntas["ordem"]][$rowsPerguntas["pergunta"]]['resposta']['extra'];
                                        $extra = ' <input type="text" name="perguntas['.$rowsPerguntas["ordem"].']['.$rowsPerguntas["pergunta"].'][resposta][extra]" value="'.$xvalue.'" >';
                                    }
                                }
                                if ($rowsPerguntas["ordem"] == 7) {
                                    if (in_array($array_abc[$i], array("b","c","d"))) {
                                        $xxvalue = $_POST['perguntas'][$rowsPerguntas["ordem"]][$rowsPerguntas["pergunta"]]['resposta']['extra'][$i];
                                        $extra = '
                                            <div class="ajuste">
                                                <div class="input-prepend" style="display: inline">
                                                    <span class="add-on">R$</span>
                                                    <input class="span2 preco_format" value="'.$xxvalue.'" name="perguntas['.$rowsPerguntas["ordem"].']['.$rowsPerguntas["pergunta"].'][resposta][extra]['.$i.']"  type="text">
                                                </div>
                                            </div>';
                                    }
                                }

                                $xxChecked = ($_POST['perguntas'][$rowsPerguntas["ordem"]][$rowsPerguntas["pergunta"]]['resposta']['respondida'] == $rowsTipoResposta["descricao"] ) ? "checked" : "" ;
                                echo '<b>'.$array_abc[$i].'</b> 
                                    <input class="campo_pergunta_'.$rowsPerguntas["ordem"].'" name="perguntas['.$rowsPerguntas["ordem"].']['.$rowsPerguntas["pergunta"].'][resposta][respondida]" type="radio" value="'.$rowsTipoResposta["descricao"].'"  '.$xxChecked .' id="x_'.$rowsPerguntas["pergunta"].'_'.strtolower(retira_acentos($rowsTipoResposta["descricao"])).'"> <label for="x_'.$rowsPerguntas["pergunta"].'_'.strtolower(retira_acentos($rowsTipoResposta["descricao"])).'">'.$rowsTipoResposta["descricao"].'</label>'.$extra.'<br />
                                    <input type="hidden" name="perguntas['.$rowsPerguntas["ordem"].']['.$rowsPerguntas["pergunta"].'][resposta][id_resposta]" value="'.$rowsTipoResposta["tipo_resposta_item"].'" >';



                            }

                            if ($rowsTipoResposta["tipo_descricao"] == "checkbox") {
                                $xxvalue = $_POST['perguntas'][$rowsPerguntas["ordem"]][$rowsPerguntas["pergunta"]]['resposta'][$rowsTipoResposta["tipo_resposta_item"]]['respondida'];

                                echo '<b>'.$array_abc[$i].'</b> 
                                      <label for="x_'.$rowsPerguntas["pergunta"].'_'.strtolower(retira_acentos($rowsTipoResposta["descricao"])).'">'.$rowsTipoResposta["descricao"].': </label>
                                      <input value="'.$xxvalue.'" type="text" name="perguntas['.$rowsPerguntas["ordem"].']['.$rowsPerguntas["pergunta"].'][resposta]['.$rowsTipoResposta["tipo_resposta_item"].'][respondida]" id="x_'.$rowsPerguntas["pergunta"].'_'.strtolower(retira_acentos($rowsTipoResposta["descricao"])).'"><br />
                                    <input type="hidden" name="perguntas['.$rowsPerguntas["ordem"].']['.$rowsPerguntas["pergunta"].'][resposta]['.$rowsTipoResposta["tipo_resposta_item"].'][id_resposta]" value="'.$rowsTipoResposta["tipo_resposta_item"].'" >';
                            }
                            $i++;
                        }
                    } else {
                        $xxxvalue = $_POST['perguntas'][$rowsPerguntas["ordem"]][$rowsPerguntas["pergunta"]]['resposta']['respondida'];
                        echo '<textarea name="perguntas['.$rowsPerguntas["ordem"].']['.$rowsPerguntas["pergunta"].'][resposta][respondida]" class="span9" rows="3">'.$xxxvalue.'</textarea></p>';
                    }

                    ?>
                </div>
                <hr  <?php echo (in_array($rowsPerguntas["ordem"], $ocultaPerguntas)) ? "style='display:none !important;'" : "" ;?> class="pergunta_<?php echo $rowsPerguntas["ordem"];?>">
                
            <?php }?>
    <?php }?>
    <p align="center"><button class="btn btn-responder btn-large" type="submit">Responder Pesquisa</button></p>
    <hr>
    <br /><h3 align="center">OBRIGADO PELA SUA PARTICIPAÇÃO!</h3><br />
</body>
</html>
