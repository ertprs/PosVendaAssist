<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';

$language = $_POST['language'];
if($language == 'pt'){
    header("Location:pesquisa_email.php");
}

$title      = ($language == 'en') ? "Satisfaction Survey" : "Encuesta de Satisfacción";
$imgHeader  = ($language == 'en') ? "images/topo_en.png" : "images/topo_es.png";
$comecar    = ($language == 'en') ? "Start" : "Comenzar";

if($_POST['ajax']){
    $ajaxType = $_POST['ajaxType'];
    switch($ajaxType){
        case "buscaCidade":
            $pais = $_POST['pais'];
            if($pais == 'BR'){
                $sql = "
                    SELECT  DISTINCT
                            tbl_posto_fabrica.contato_cidade AS cidade
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto = tbl_posto.posto
                                                AND tbl_posto_fabrica.fabrica = 1
                                                AND tbl_posto.pais = '$pais'
              ORDER BY      tbl_posto_fabrica.contato_cidade
                ";
            }else{
                $sql = "
                    SELECT  DISTINCT
                            tbl_posto.cidade
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto = tbl_posto.posto
                                                AND tbl_posto_fabrica.fabrica = 1
                                                AND tbl_posto.pais = '$pais'
              ORDER BY      tbl_posto.cidade
                ";

            }
            $res = pg_query($con,$sql);
            $conta = pg_num_rows($res);
            for($c = 0; $c < $conta; $c++){
                $cidade = pg_fetch_result($res,$c,cidade);
                $cidade = htmlentities($cidade);
                $retorno[] = array("cidade" => $cidade);
            }
        break;
        case "buscaPosto":
            $cidade = utf8_decode($_POST['cidade']);
            $sql = "
                SELECT  DISTINCT
                        tbl_posto.nome
                FROM    tbl_posto_fabrica
                JOIN    tbl_posto   ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                                    AND tbl_posto_fabrica.fabrica   = 1
                                    AND (
                                            tbl_posto.cidade                    = '$cidade'
                                        OR  tbl_posto_fabrica.contato_cidade    = '$cidade'
                                        )
          ORDER BY      tbl_posto.nome
            ";
            $res = pg_query($con,$sql);
            $conta = pg_num_rows($res);
            for($c = 0; $c < $conta; $c++){
                $posto = pg_fetch_result($res,$c,nome);
                $posto = htmlentities($posto);
                $retorno[] = array("nome" => $posto);
            }
        break;
    }

    echo json_encode($retorno);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Language" content="<?=$language?>">
<link href="../../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../../plugins/bootstrap/js/bootstrap.min.js"></script>
<script src="../../plugins/tooltip.js"></script>

<script type="text/javascript">
$(function(){
    $("#comecar").click(function(event){
        event.preventDefault();
        $("#comecar").hide();
        $("main").show();
        $("header h1").css({
            "background": "url('images/logo_black_surv.png') no-repeat scroll 0 0 /100% auto",
            "height":"105px",
            "width":"100%"
        });
    });

    $("#pais").change(function(){
        var pais = $(this).val();
        var retorno = [];
        var nomeCorrigido;

        $.ajax({
            type:"POST",
            url:"<?=$PHP_SELF?>",
            dataType:"json",
            data:{
                ajax:true,
                ajaxType:"buscaCidade",
                pais:pais
            }
        })
        .done(function(data){
            retorno.push("<option value=''></option>");
            $(data).each(function(key,val){
                nomeCorrigido = $('<div/>').html(val.cidade).text();
                retorno.push("<option value='"+nomeCorrigido+"'>"+nomeCorrigido+"</option>");
            });
            $("#cidade").html(retorno);
        });
    });

    $("#cidade").change(function(){
        var cidade = $(this).val();
        var retorno = [];
        $.ajax({
            type:"POST",
            url:"<?=$PHP_SELF?>",
            dataType:"json",
            data:{
                ajax:true,
                ajaxType:"buscaPosto",
                cidade:cidade
            }
        })
        .done(function(data){
            retorno.push("<option value=''></option>");
            $(data).each(function(key,val){
                retorno.push("<option value='"+val.nome+"'>"+val.nome+"</option>");
            });
            $("#posto").html(retorno);
        });
    });

    $("#gravar").click(function(event){
        event.preventDefault();
        $.ajax({
            type:"POST",
            url:"respostas_pesquisa.php",
            dataType:"json",
            data:{
                ajax:true,
                input:$("#frm_pesquisa").find("input").serialize(),
                textarea:$("#frm_pesquisa").find("textarea").serialize(),
                select:$("#frm_pesquisa").find("select").serialize()
            },
            beforeSend:function(){
                $('#gravar').hide();
                $('.alert-error').hide();
                $('.td_btn_gravar_pergunta').show();
                $('.td_btn_gravar_pergunta').html("Gravando...<br><img src='../../imagens/loading_bar.gif'> ")
                .css({
                    "text-align":"center",
                    "font-size":"12"
                });
            },
        })
        .done(function(data){
            if(data.status == "ok"){
                $("#frm_pesquisa").find("input").attr('disabled',true);
                $("#frm_pesquisa").find("textarea").attr('disabled',true);
                $("#frm_pesquisa").find("select").attr('disabled',true);

                $('.agradecimentosPesquisa').show();
                $('.td_btn_gravar_pergunta').hide();
            }else{
                var motivo = "";
                if(data.language == 'en'){
                    motivo = "Except question 8, all questions are compulsory answers!";
                }else if(data.language == 'es'){
                    motivo = "Excepto la pregunta 8, todas las preguntas son respuestas obligatorias!";
                }

                $('.alert-error').show().append(motivo);
                $('.td_btn_gravar_pergunta').hide();
                $('#gravar').show();
            }
        });
    });
});
</script>

<style type="text/css">
     body{
        margin: 0;
        padding:0;
    }

    #tudo{
        width:800px;
        margin:0 auto;
    }

    header h1{
        background: url("<?=$imgHeader?>") no-repeat scroll 0 0 /100% auto;
        display:block;
        height:310px;
        width:695px;
        margin:0;
        text-align:center;
    }

    .td_radio{
        text-align:center;
        vertical-align:middle;
    }

    .container{
        width:100%;
    }
    .container table{
        margin-left:60px;
    }
    .bd{
        font-weight:bold;
    }
    main{
        display:none;
    }

    #comecar{
        margin-left:530px;
        margin-top:255px;
    }

</style>
<title><?=$title?> - Black&Decker</title>
</head>
<body>
<div id="tudo">
    <header>
        <h1>
            <button class='btn btn-primary' id="comecar" /><?=$comecar?></button>
        </h1>
    </header>
    <main>
        <form id="frm_pesquisa">
            <input type="hidden" name="language" value="<?=$language?>" />
            <div class='' style="width: 900px;">
                <div id="conteudo">
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span5">
                            <div class='control-group'>
                                <label class='control-label' for='pais'>1 - <?=($language == 'en') ? "Select your country" : "Selecciona tu pais" ;?></label>
                                <select id="pais" name="pais">
                                    <option value="">&nbsp;</option>
<?
    $sql = "
        SELECT  DISTINCT
                tbl_pais.nome,
                tbl_pais.pais
        FROM    tbl_pais
        JOIN    tbl_posto           ON  tbl_posto.pais = tbl_pais.pais
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto = tbl_posto.posto
                                    AND tbl_posto_fabrica.fabrica = 1
  ORDER BY      tbl_pais.nome;
    ";
    $res = pg_query($con,$sql);
    $paises = pg_fetch_all($res);
    foreach($paises as $pais){
?>
                                        <option value="<?=$pais['pais']?>"><?=$pais['nome']?></option>
<?
    }
?>
                                </select>
                            </div>
                        </div>
                        <div class="span5">
                            <div class='control-group'>
                                <label class='control-label' for='cidade'>2 - <?=($language == 'en') ? "Select your city" : "Selecciona ciudad" ;?></label>
                                <select id="cidade" name="cidade">
                                    <option value="">&nbsp;</option>
                                </select>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <br />
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span5">
                            <div class='control-group'>
                                <label class='control-label' for='posto'>3 - <?=($language == 'en') ? "Technical Assistance" : "Centro de Servicio" ;?></label>
                                <select id="posto" name="posto">
                                    <option value="">&nbsp;</option>
                                </select>
                            </div>
                        </div>
                        <div class="span5">
                            <div class='control-group'>
                                <label class='control-label' for='os'>4 - <?=($language == 'en') ? "Service Order" : "Orden de Reparación" ;?></label>
                                <div class='input-append'>
                                    <input type='text' name='os' id='os' value=""/>
                                    <span class="add-on">
                                        <i id="btnPopover" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="<?=($language == 'en') ? "SERVICE ORDER" : "ORDEN DE REPARACIÓN" ;?>" data-content="<?=($language == 'en') ? "It is the number that appears in the report of the repair that was given to you along with the machine" : "Es el numero que aparece en el reporte de la reparación que fue entregado a usted junto con la maquina"?>" class="icon-question-sign"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <br />
                    <div class="container">
                        <div class='row-fluid'>
                            <div class="span1"></div>
                            <div class="span5">
                                <div class='control-group'>
                                    5 - <?=($language == 'en') ? "Equipment" : "Equipo que se reparó" ;?>
                                    <br />
<?
    if($language == 'en'){
?>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="hammer" />Hammer<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="drill_cordless" />Drill / Cordless<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="metalworking" />Metalworking<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="woodworking" />Woodworking<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="saws" />Saws<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="lawn_garden" />Lawn + Garden<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="gasoline_explosion" />Gasoline / Explosion<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="pneumatic" />Pneumatic<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="other" />Other<br />
                                        </label>
                                    </div>
<?
    }else{
?>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="martillos" />Martillos<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="inalambrico" />Taladros / H. Inalámbricas<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="metalmecanica" />Metalmecánica<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="madera" />Madera<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="estacionaria" />H. Estacionaria<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="jardin" />Jardín<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="gasolina_explosion" />Gasolina / Explosión<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="neumatica" />Neumática<br />
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                        <input type="radio" name="equipamento" value="other" />Otra<br />
                                        </label>
                                    </div>
<?
    }
?>
                                </div>
                            </div>
                            <div class="span5">
                                <div class='control-group'>
                                    <label class='control-label' for='marca'><?=($language == 'en') ? "Brand" : "Marca" ;?></label>
                                    <select id="marca" name="marca">
                                        <option value="">&nbsp;</option>
<?
    $sql = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = 1
        AND     marca <> 238
    ";
    $res = pg_query($con,$sql);
    $resultado = pg_fetch_all($res);
    foreach($resultado as $valor){
?>
                                        <option value="<?=$valor['marca']?>"><?=$valor['nome']?></option>
<?
    }
?>
                                    </select>
                                    <br />
                                    <label class='control-label' for='produto'><?=($language == 'en') ? "Product" : "Producto" ;?></label>
                                    <div class='controls controls-row'>
                                        <input type='text' name='produto' id='produto' value=""/>
                                    </div>
                                </div>
                            </div>
                            <div class="span1"></div>
                        </div>
                    </div>
                    <br />
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span10">
                            <label class='control-label' for='os'><?=($language == 'en') ? "Which" : "Cual" ;?></label>
                            <div class='controls controls-row'>
                                <input type='text' name='outro_qual' id='outro_qual' value=""/>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span10">
                            <div class='control-group'>
                                6 - <?=($language == 'en') ? "Based in your customer service experience, would you recommend <span class='bd'>Stanley Black & Decker</span> to your family and friends?" : "Basado en la experiencia con la reparación de tu producto, de 0 a 10, que tan probable es que recomiendes el servicio postventa <span class='bd'>Stanley Black and Decker</span>, a tus colegas, familiares o amigos?" ;?>
                                <span class="add-on">
                                    <i id="btnPopover6" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="<?=($language == 'en') ? "RECOMENDATION" : "RECOMENDACIÓN"?>" data-content="<?=($language == 'en') ? "Choose a number between 10 and 0. Where 10 means it is 100% certain that you recommend to your colleagues, friends and family after sales sevice Stanley Black & Decker" : "Escoge un numero entre el 10 y 0. Donde 10 significa que es 100% seguro que le recomiendes a tus colegas, amigos y familiares el sevicio postventa de Stanley Black&Decker"?>" class="icon-question-sign"></i>
                                </span>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span10">
                            <div class='control-group'>
                                <table border="0">
                                    <tr>
                                        <td rowspan="2" style="vertical-align:bottom;" nowrap>
                            <?=($language == 'en') ? "Very Likely" : "Muy Probable";?>
                                        </td>
                                        <td style="text-align:center;width:25px;">10</td>
                                        <td style="text-align:center;width:25px;">9</td>
                                        <td style="text-align:center;width:25px;">8</td>
                                        <td style="text-align:center;width:25px;">7</td>
                                        <td style="text-align:center;width:25px;">6</td>
                                        <td style="text-align:center;width:25px;">5</td>
                                        <td style="text-align:center;width:25px;">4</td>
                                        <td style="text-align:center;width:25px;">3</td>
                                        <td style="text-align:center;width:25px;">2</td>
                                        <td style="text-align:center;width:25px;">1</td>
                                        <td style="text-align:center;width:25px;">0</td>
                                        <td rowspan="2" style="vertical-align:bottom;" nowrap>
                            <?=($language == 'en') ? "Very Unlikely" : "Nada Probable";?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="10"/></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="9" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="8" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="7" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="6" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="5" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="4" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="3" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="2" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="1" /></td>
                                        <td style="text-align:center;"><input type="radio" name="recomendacao" value="0" /></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <br />
                    <div class="container">
                        <div class='row-fluid'>
                            <div class="span1"></div>
                                <div class="span10">
                                    <div class='control-group'>
                                        7 - <?=($language == 'en') ? "Select the main reason for your score" : "Selecciona el principal motivo de tu calificación" ;?>
                                        <span class="add-on">
                                            <i id="btnPopover7" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="<?=($language == 'en') ? "REASON FOR SCORING" : "MOTIVO DE PUNTUACIÓN"?>" data-content="<?=($language == 'en') ? "Select only one who has been most important to you when you answered the previous question" : "Selecciona solo uno, el que haya sido mas importante para ti cuando respondiste la pregunta anterior"?>" class="icon-question-sign"></i>
                                        </span>
                                            <br />
                                        <?php
                                        if(date("Y") <= "2015"){
                                            ?>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="servico_assistencia" /><?=($language == 'en') ? "Technical assistance service" : "Servicio del centro de raparación" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="suporte"             /><?=($language == 'en') ? "Support and attention" : "Soporte y atención (Recepción)" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="tempo_resposta"      /><?=($language == 'en') ? "Response Time" : "Tiempo de respuesta" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="padrao_servico"      /><?=($language == 'en') ? "Quality standards and service" : "Estándares de calidad y servicio" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="qualidade_reparo"    /><?=($language == 'en') ? "Repair quality" : "Calidade de la reparación" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="tempo_reparo"        /><?=($language == 'en') ? "Repair time" : "Tiempo de reparación" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="falha_equipamento"   /><?=($language == 'en') ? "Early equipment failure" : "Falla Temprana del equipo" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="qualidade_produto"   /><?=($language == 'en') ? "Product Quality" : "Calidade del producto" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="atencao_suporte"     /><?=($language == 'en') ? "Support attention (via phone)" : "Atención y soporte" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="orcamento"           /><?=($language == 'en') ? "Cost budget and / or repair" : "Costo y/ó presupuesto de reparación" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="acompanhamento"      /><?=($language == 'en') ? "Monitoring repair" : "Seguimiento de la reparación" ;?><br />
                                                </label>
                                            </div>
                                            <?php
                                        }else{ // NOVA PERGUNTA 7 DISPONÍVEL A PARTIR DE 2016
                                            ?>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="atencao_suporte_telefonico"/><?=($language == 'en') ? "Support and care (Telephone)" : "Atención y soporte (Telefónica)" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="atencao_suporte_recepcao"/><?=($language == 'en') ? "Support and care (Technical Assistance)" : "Atención y soporte (Recepción del centro de servicio)" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="falha_precoce_ferramenta"/><?=($language == 'en') ? "Premature tool failure" : "Falla temprana de la herramienta" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="qualidade_produto"/><?=($language == 'en') ? "Product quality" : "Calidad del producto" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="tempo_de_resposta"/><?=($language == 'en') ? "Lead time" : "Tiempo de respuesta" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="custo_orcamento_reparo"/><?=($language == 'en') ? "Cost and/or budget repair" : "Costo y/ó presupuesto de reparación" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="rastreamento_reparacao"/><?=($language == 'en') ? "Repair tracking" : "Seguimiento de la reparación" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="tempo_repado"/><?=($language == 'en') ? "Repair time" : "Tiempo de reparación" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="qualidade_reparacao"/><?=($language == 'en') ? "Repair quality" : "Calidad de la reparación" ;?><br />
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                <input type="radio" name="razao_pontuacao" value="servico_prestado_centro"/><?=($language == 'en') ? "Technical Assistance Service" : "Servicio prestado por el centro de servicio" ;?><br />
                                                </label>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            <div class="span1"></div>
                        </div>
                    </div>
                    <br />
                    <div class='row-fluid'>
                        <div class="span1"></div>
                        <div class="span10">
                            <div class='control-group'>
                                8 - <?=($language == 'en') ? "Do you want to expand your score?" : "Quieres ampliar tu calificación?" ;?>
                                <span class="add-on">
                                    <i id="btnPopover8" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="<?=($language == 'en') ? "OTHER REASONS" : "OTRAS RAZONES"?>" data-content="<?=($language == 'en') ? "If options in the previous question were not enough, please use your own words to describe the reason for your rating" : "Si las opciones de la pregunta anterior no fueron suficientes, por favor usa tus propias palabras para describir el motivo de tu calificación"?>" class="icon-question-sign"></i>
                                </span>
                                <div class='controls controls-row'>
                                    <textarea name='complemento_classificacao' id='complemento_classificacao' value="" style="width:600px;" ></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>
                    <br />
                    <div class="container">
                        <div class='row-fluid'>
                            <div class="span10">
                                <div class='control-group'>
                                    <table style="border: 1px solid #CCC" id="tabela">
                                        <tr>
                                            <th>
                                                <?=($language == 'en') ? "What is your level of satisfaction with the following aspects" : "Cual es tu nivel de satisfacción al respecto de los siguientes aspectos:" ;?>
                                            </th>
                                            <td style="text-align:center"><?=($language == 'en') ? "Fully Satisfied" : "Totalmente satisfecho" ;?></td>
                                            <td style="text-align:center"><?=($language == 'en') ? "Very Satisfied" : "Bastante Satisfecho" ;?></td>
                                            <td style="text-align:center"><?=($language == 'en') ? "Neutral" : "Neutral" ;?></td>
                                            <td style="text-align:center"><?=($language == 'en') ? "Shortly Satisfied" : "Poco Satisfecho" ;?></td>
                                            <td style="text-align:center"><?=($language == 'en') ? "Unfulfilled" : "Nada Satisfecho" ;?></td>
                                            <td style="text-align:center;vertical-align:bottom;"><?=($language == 'en') ? "Number of days in attendance" : "Numero de días en el centro de servicio" ;?>
                                                <span class="add-on">
                                                    <i id="btnPopover9" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="<?=($language == 'en') ? "DAYS IN ATTENDANCE" : "DÍAS EN EL CENTRO DE SERVICIO"?>" data-content="<?=($language == 'en') ? "Choose from the list the number of days that lasted repair" : "Escoge de la lista el número de días que duro la reparación"?>" class="icon-question-sign"></i>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td nowrap>9 - <?=($language == 'en') ? "Repair time" : "Tiempo de la reparación" ;?></td>
                                            <td class="td_radio"><input type="radio" name="nota_tempo_reparo" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_tempo_reparo" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_tempo_reparo" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_tempo_reparo" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_tempo_reparo" value="insatisfeito"            /></td>
                                            <td rowspan="7" style="vertical-align:top;">
                                                <select id="numero_dias" name="numero_dias" style="width:55px;">
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                    <option value="5">5</option>
                                                    <option value="6">6</option>
                                                    <option value="7">7</option>
                                                    <option value="8">8</option>
                                                    <option value="mais"> > 8</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td nowrap>10 - <?=($language == 'en') ? "Repair price" : "Precio de la reparación" ;?></td>
                                            <td class="td_radio"><input type="radio" name="nota_preco_reparo" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_preco_reparo" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_preco_reparo" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_preco_reparo" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_preco_reparo" value="insatisfeito"            /></td>
                                        </tr>
                                        <tr>
                                            <td nowrap>11 - <?=($language == 'en') ? "Repair quality" : "Calidad de la reparación" ;?></td>
                                            <td class="td_radio"><input type="radio" name="nota_qualidade_reparo" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_qualidade_reparo" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_qualidade_reparo" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_qualidade_reparo" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_qualidade_reparo" value="insatisfeito"            /></td>
                                        </tr>
                                        <tr>
                                            <td nowrap>12 - <?=($language == 'en') ? "Attendant's attention" : "Actitud del personal" ;?></td>
                                            <td class="td_radio"><input type="radio" name="nota_atencao" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_atencao" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_atencao" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_atencao" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_atencao" value="insatisfeito"            /></td>
                                        </tr>
                                        <tr>
                                            <td nowrap>13 - <?=($language == 'en') ? "Repair's explanation " : "Explicación de la reparación" ;?>
                                                <span class="add-on">
                                                    <i id="btnPopover13" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="<?=($language == 'en') ? "EXPLANATION " : "EXPLICACIÓN" ;?>" data-content="<?=($language == 'en') ? "The person who presented the tool, explain the work that was done?" : "La persona que te hizo entrega de la herramienta, explico la labor que se hizo?" ;?>" class="icon-question-sign"></i>
                                                </span>
                                            </td>
                                            <td class="td_radio"><input type="radio" name="nota_explicacao" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_explicacao" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_explicacao" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_explicacao" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_explicacao" value="insatisfeito"            /></td>
                                        </tr>
                                        <tr>
                                            <td nowrap>14 - <?=($language == 'en') ? "Visual aspect Assistance" : "Aspecto de las instalaciones de servicio" ;?></td>
                                            <td class="td_radio"><input type="radio" name="nota_aspecto" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_aspecto" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_aspecto" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_aspecto" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_aspecto" value="insatisfeito"            /></td>
                                        </tr>
                                        <tr>
                                            <td nowrap>15 - <?=($language == 'en') ? "Overall satisfaction" : "Satisfacción General" ;?></td>
                                            <td class="td_radio"><input type="radio" name="nota_geral" value="plenamente_satisfeito"   /></td>
                                            <td class="td_radio"><input type="radio" name="nota_geral" value="muito_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_geral" value="satisfeito"              /></td>
                                            <td class="td_radio"><input type="radio" name="nota_geral" value="pouco_satisfeito"        /></td>
                                            <td class="td_radio"><input type="radio" name="nota_geral" value="insatisfeito"            /></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="span1"></div>
                        </div>
                    </div>
                </div>
                <?php
                if (!$_GET["imprimir"]) {
                    $button = ($language == 'en') ? "Submit" : "Registro";
                ?>
                    <br />
                    <p class="tac" style="margin-bottom: 0px;">
                        <button class='btn btn-primary' id="gravar" /><?=$button?></button>
                        <div class="td_btn_gravar_pergunta"></div>
                        <div class='agradecimentosPesquisa alert alert-success' style='display:none'>
                            <div class="alert alert-success">
                                <strong><?=($language == 'en') ? "Successfully saved!" : "Guardado correctamente!";?></strong><br />
                                <?=($language == 'en') ? "ON BEHALF OF THE <span class='bd'>Stanley Black & Decker</span> WOULD LIKE TO THANK YOU FOR YOUR ATTENTION AND WE WISH YOU A GOOD DAY." : "EN NOMBRE DE LA <span class='bd'>Stanley Black & Decker</span> ME GUSTARÍA DARLE LAS GRACIAS POR SU ATENCIÓN Y LE DESEAMOS UN BUEN DÍA."?>
                            </div>
                        </div>
                        <div class='erroPesquisa alert alert-error' style='display:none'>
                        </div>
                    </p>
                    <br />
                <?php
                }
                ?>
            </div>
        </form>
    </main>
    <footer>
    </footer>
</div>
<script type="text/javascript">
    $('#btnPopover').popover();
    $('#btnPopover6').popover();
    $('#btnPopover7').popover();
    $('#btnPopover8').popover();
    $('#btnPopover9').popover();
    $('#btnPopover13').popover();
</script>
</body>
</html>