<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';

$treinamento    = $_GET['a'];
$tecnico        = $_GET['b'];

$fabrica        = 1;
$fabrica_nome   = "StanleyBlack&Decker";

$tipo = "posto_sms";



if (empty($treinamento) OR empty($tecnico)) {
    echo "<script>";
    echo "  window.close()";
    echo "</script>";
} else {
    $sql = "SELECT  tbl_treinamento.treinamento, 
                    tbl_treinamento.fabrica, 
                    tbl_treinamento.titulo,
                    tbl_treinamento.data_inicio
            FROM    tbl_treinamento
                JOIN tbl_treinamento_posto USING(treinamento)
            WHERE   tbl_treinamento.treinamento = $treinamento 
                AND tbl_treinamento_posto.tecnico = $tecnico;";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) == 0) {
        echo "<script>";
        echo "  window.close()";
        echo "</script>";    
    } else {
        $data_inicio_treinamento = pg_fetch_result($res, 0, data_inicio);
        $titulo_treinamento = pg_fetch_result($res, 0, titulo);
    }
}

if ($_POST['ajax'] && $_POST['gravaPerguntas']){
    $erro           = array();
    $arrayCheckbox  = array();
    $idTreinamento  = $_POST['idTreinamento'];
    $idTecnico      = $_POST['idTecnico'];
    $qtde_perg      = $_POST['qtde_perg'];
    $pesquisa       = $_POST['pesquisa'];
    $input          = $_POST['input'];
    $textarea       = $_POST['textarea'];

    $pergunta           = explode("&",$input);
    $perguntaTextarea   = explode("&",$textarea);

    $campos_adicionais['treinamento'] = $idTreinamento;
    $campos_adicionais = json_encode($campos_adicionais);

    foreach($pergunta as $key=>$value){
        $dados = explode("=",$value);
        $valores[$dados[0]] = $dados[1];
    }

    foreach($perguntaTextarea as $key=>$value){
        $dadosT = explode("=",$value);
        $valores[$dadosT[0]] = $dadosT[1];
    }

    foreach ($valores as $keyPost => $valuePost) {

        $keyExplode = explode("_",$keyPost);

        if($keyExplode[2]=='checkbox'){
            $arrayCheckbox[$keyExplode[3]][] = $keyExplode[5];
        }
    }

    $sqlObrigatorio = " SELECT obrigatorio
                        FROM tbl_pesquisa
                        WHERE pesquisa = $pesquisa
                        AND fabrica = $fabrica
                        AND ativo IS TRUE ";
    $resObrigatorio = pg_query($con, $sqlObrigatorio);
    if(pg_num_rows($resObrigatorio) > 0){
        $pesquisa_obrigatoria = pg_fetch_result($resObrigatorio, 0, 'obrigatorio');
    }

    $res = pg_query($con,'BEGIN');

    for ($i=0; $i < $valores['qtde_perg']; $i++) {

        $pergunta = $valores['perg_'.$i];
        $tipo_resposta = $valores['hidden_'.$i];
        $obrigatorio = $valores['obrig_'.$i];

        $resposta = (isset($valores['perg_opt'.$pergunta])) ? utf8_decode(trim($valores['perg_opt'.$pergunta])) : '';

        if(empty($resposta) && $obrigatorio == 't' && $tipo_resposta != 'checkbox'){
            $erro[] = "Favor, preencher as respostas obrigatórias";
        }

        if (in_array($tipo_resposta, array('text','range','textarea','date'))) {
            $txt_resposta = htmlentities(str_replace("+"," ",rawurldecode($resposta)),ENT_QUOTES,'UTF-8');
                $resposta = 'null';
        }


        if ( $tipo_resposta == 'checkbox') {
            if(isset($arrayCheckbox[$pergunta])){

                foreach ($arrayCheckbox[$pergunta] as $value) {
                    $resposta = $value;

                    $sqlItens = "   SELECT  tbl_tipo_resposta_item.descricao
                                    FROM    tbl_tipo_resposta_item
                                    WHERE   tipo_resposta_item = ".$value;
                    $resItens = pg_query($con,$sqlItens);

                    if (pg_num_rows($resItens)>0) {

                        $txt_resposta = pg_fetch_result($resItens,0,0);

                    }else{
                        $txt_resposta = '';
                    }

                    $sql = "INSERT INTO tbl_resposta(
                                pergunta            ,
                                txt_resposta        ,
                                tipo_resposta_item  ,
                                pesquisa            ,
                                data_input          , 
                                tecnico             , 
                                campos_adicionais
                            )VALUES(
                                $pergunta           ,
                                '$txt_resposta'     ,
                                $resposta           ,
                                '$pesquisa'         ,
                                current_timestamp   ,
                                $idTecnico, 
                                '$campos_adicionais'
                            )
                        ";
                    $res = pg_query($con,$sql);

                }
                continue ;
            }else{
                if($obrigatorio == 't'){
                    $erro[] = "Favor, preencher as respostas obrigatórias";
                }
                continue ;
            }
        }

        if (!empty($resposta) and $resposta != 'null') {

            $sqlItens = "   SELECT  tbl_tipo_resposta_item.descricao
                            FROM    tbl_tipo_resposta_item
                            WHERE   tipo_resposta_item = $resposta";

            $resItens = pg_query($con,$sqlItens);

            if (pg_num_rows($resItens)>0) {

                $txt_resposta = pg_fetch_result($resItens,0,0);

            }else{
                $txt_resposta = $resposta;
            }
        }else{
            $resposta = 'null';
        }

        $sql = "INSERT INTO tbl_resposta(
                    pergunta            ,
                    txt_resposta        ,
                    tipo_resposta_item  ,
                    pesquisa            ,
                    data_input          ,
                    tecnico, 
                    campos_adicionais 
                )VALUES(
                    $pergunta           ,
                    '$txt_resposta'     ,
                    $resposta           ,
                    $pesquisa         ,
                    current_timestamp, 
                    $idTecnico,
                    '$campos_adicionais'
                ); ";
        $res = pg_query($con,$sql);

        if (pg_last_error($con)){
            $erro[] = pg_last_error($con) ;
        }
    }

    // gravar o descrição e dia do treinamento no campo txt_resposta

    $sql = "SELECT  tbl_treinamento.treinamento, 
                    tbl_treinamento.fabrica, 
                    tbl_treinamento.titulo,
                    tbl_treinamento.data_inicio
            FROM    tbl_treinamento
                JOIN tbl_treinamento_posto USING(treinamento)
            WHERE   tbl_treinamento.treinamento = $idTreinamento 
                AND tbl_treinamento_posto.tecnico = $idTecnico;";
    $res = pg_query($con,$sql);

    $txt_resposta_treinamento = "'".pg_fetch_result($res, 0, data_inicio)." | ".pg_fetch_result($res, 0, titulo)."'";
    $sql = "INSERT INTO tbl_resposta(
                    txt_resposta        ,
                    pesquisa            ,
                    tecnico             ,
                    data_input          ,
                    campos_adicionais
                )VALUES(
                    $txt_resposta_treinamento  ,
                    $pesquisa         ,
                    $idTecnico            ,
                    current_timestamp       , 
                    '$campos_adicionais'
                ) ";
    $res = pg_query($con,$sql);

    if (pg_last_error() ) {
        $erro[] = "Erro ao gravar resultado da pesquisa!";
    }

    if (count($erro)>0){
        $erro = implode('<br>', $erro);
        if(strpos($erro, 'syntax erro') > 0 ){
            $erro = "Favor preencher todas as respostas da pesquisa";
        }elseif(strpos($erro,'preencher')){
            $erro = "Favor, preencher as respostas obrigatórias";
        }
        $res = pg_query($con,'ROLLBACK TRANSACTION');
    }else{
        $res = pg_query($con,'COMMIT TRANSACTION');        
    }

    if (count($erro) > 0){
        echo "1|$erro";
    }else{
        echo "0|Sucesso";
    }
    exit;
}

if(pg_fetch_result($res,0,fabrica) != $fabrica){
    echo "<script>";
    echo "  window.close()";
    echo "</script>";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">

<meta http-equiv="content-Type" content="text/html; charset=iso-8859-1">
<title>PESQUISA DE SATISFAÇÃO</title>

<!-- <link href="../../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
 -->

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">


<script src="../../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
<script src="../../admin/plugins/jquery.maskedinput_new.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<!-- <script src="../../admin/js/jquery-1.8.3.min.js"></script>
 -->


<script type="text/javascript">
$().ready(function(){
    var datepickerOptions = { maxDate: 0, dateFormat: "dd/mm/yy" };

    $(".data").datepicker(datepickerOptions).mask("99/99/9999");

    $('#btn_grava_pesquisa').click(function(){
        var curDateTime = new Date();
        var relBtn = $(this).attr('rel');
        var idTreinamento = <?=$treinamento?>;
        var idTecnico = <?=$tecnico?>;

        $.ajax({
            type: "POST",
            url: "<?=$PHP_SELF?>",
            data: {
                ajax:true,
                gravaPerguntas:true,
                pesquisa:relBtn,
                idTreinamento:idTreinamento,
                idTecnico:idTecnico,
                input:$('#pesquisa_satisfacao').find('input').serialize(),
                textarea:$('#pesquisa_satisfacao').find('textarea').serialize()
            },
            beforeSend: function(){
                $('#btn_grava_pesquisa').hide();
                $('.td_btn_gravar_pergunta').show();
                $('.td_btn_gravar_pergunta').html("&nbsp;&nbsp;Gravando...&nbsp;&nbsp;<br><img src='../../imagens/loading_bar.gif'> ");
                $('.divTranspBlock').show();
            }
        })
        .done(function(http) {
            results = http.split('|');
            if (results[0] == 1){

                $('h4.errorPergunta').html(results[1]);
                $('div.alert-error').show();
                $('.td_btn_gravar_pergunta').hide();
                $('.divTranspBlock').hide();
                $('#btn_grava_pesquisa').show();


            }else{
                $('div.alert-error').hide();
                $('.divTranspBlock').hide();
                $('#pesquisa_satisfacao').find('input').attr('disabled',true);
                $('#pesquisa_satisfacao').find('textarea').attr('disabled',true);
                $('.agradecimentosPesquisa').show();
                $('.td_btn_gravar_pergunta').hide();
            }
        });
    });
});
</script>
<style type="text/css">
.logo_gl{

    width: 210px;
}

.logo-row {
    margin-bottom: 15px;
}
.titulo_tabela{
    background-color: #596d9b;
    font: bold 16px "Arial";
    color: #FFFFFF;
    text-align: center;
    padding: 10px 6px;
}
.bgazul{
    background-color: #596d9b;
}
.subtitulo{
    background: #eeeeee;
    border: solid 1px #dddddd;
}
.subtitulo div{
    padding:10px 20px;
}
.linha{
    border: solid 1px #dddddd;
    padding:10px 20px;
}

.tac{
    text-align: center;
}
</style>
</head>

<body>
    <br>
<div class="container tac">
    <img class="img-responsive" src='images/logo_black_surv.png'/>
</div>
<hr />

<div class='container'>
    <p class="tac">
        <strong>Obs*</strong> Perguntas destacadas em <strong style='color:#F00;'>vermelho</strong> são de resposta obrigatórias.
    </p>
    <?php
    $sql = "SELECT  tbl_pesquisa_pergunta.ordem     ,
                    tbl_pergunta.pergunta           ,
                    tbl_pergunta.descricao          ,
                    tbl_pergunta.tipo_resposta      ,
                    tbl_tipo_resposta.tipo_descricao,
                    tbl_tipo_resposta.obrigatorio,
                    tbl_pesquisa.pesquisa,
                    tbl_pesquisa.descricao AS pesq_desc
            FROM    tbl_pesquisa_pergunta
                JOIN    tbl_pergunta        USING(pergunta)
                JOIN    tbl_pesquisa        USING(pesquisa)
                LEFT JOIN    tbl_tipo_resposta   ON tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta
            WHERE   tbl_pesquisa.categoria  = '$tipo'
                AND     tbl_pesquisa.fabrica    = $fabrica
                AND     tbl_pergunta.ativo      IS TRUE
                AND     tbl_pesquisa.ativo IS TRUE
      ORDER BY      tbl_pesquisa_pergunta.ordem";
    $res = pg_query($con,$sql);

    #echo nl2br($sql);

    if (pg_num_rows($res)>0) {
        $descricao = pg_fetch_result($res, 0, pesq_desc);
        ?>
        <div class="row" id="pesquisa_satisfacao">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-sm-12 bgazul">
                        <div class="titulo_tabela">
                        Pesquisa de Satisfação do Cliente: <?=$descricao?>
                        </div>
                    </div>
                </div>
               
            <?php
                $pesquisa = pg_fetch_result($res,0,pesquisa);
                $i = 0;
                $respostasPergunta = array();

                //Pesquisa se o tecnico já respondeu a pesquisa deste treinamento 
                $sqlTP = "SELECT  to_char(data_input,'YYYY-MM-DD HH24:MI:SS') as data_input
                            FROM    tbl_resposta
                            WHERE   tecnico = {$tecnico}
                            AND     pesquisa    = {$pesquisa}
                            AND     os isnull
                            AND     hd_chamado isnull
                            AND     txt_resposta = '".$data_inicio_treinamento." | ".$titulo_treinamento."'
                            ORDER BY pergunta";
                $resTP = pg_query($con, $sqlTP);
                
                #echo nl2br($sqlTP);exit;
                
                if ( pg_num_rows($resTP) > 0) {
                    
                    $id_data_input = pg_fetch_result($resTP, 0, data_input);

                    //percorre o array da consulta principal 1ª vez para jogar as respostas em um array
                    foreach (pg_fetch_all($res) as $key) {

                        $key_pergunta = $key['pergunta'];
                        $key_pesquisa = $key['pesquisa'];

                        if (empty($key_pergunta)) {
                            continue;
                        }

                        $sql = "SELECT  pergunta            ,
                                        txt_resposta        ,
                                        tipo_resposta_item
                                FROM    tbl_resposta
                                WHERE   pergunta    = {$key_pergunta}
                                AND     pesquisa    = {$key_pesquisa}
                                AND     os isnull
            		            AND     hd_chamado isnull
                                AND     data_input BETWEEN (timestamp'{$id_data_input}' - INTERVAL '5 second' ) AND (timestamp'{$id_data_input}' + INTERVAL '5 second')
                            ORDER BY pergunta";
                        
                        $resRespostas = pg_query($con,$sql);

                        if (pg_num_rows($resRespostas)>0) {
                            foreach (pg_fetch_all($resRespostas) as $keyRespostas) {
                                if (!empty($keyRespostas['tipo_resposta_item'])) {

                                    $respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][] = $keyRespostas['tipo_resposta_item'];

                                }else{
                                    $respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][] = $keyRespostas['txt_resposta'];
                                }
                            }
                        }
                    }
                }
                //percorre a segunda vez para montar o formulário
                foreach (pg_fetch_all($res) as $key) {


                    $html_pesquisa .= "
                            <div class='row subtitulo'>
                                <div  class='col-sm-12' ";
                                if($key['obrigatorio'] == 't') {
                                    $html_pesquisa .= "style='color:#F00;'";
                                }
                                $html_pesquisa .=">
                                    <input type='hidden' name='perg_".$i."' value='".$key['pergunta']."' placeholder=''>
                                    <input type='hidden' name='hidden_$i' value='".$key['tipo_descricao']."' >
                                    <input type='hidden' name='obrig_$i' value='".$key['obrigatorio']."' >
                                    <label > ".$key['ordem']." </label> 

                                    ".$key['descricao']."
                                </div>
                            </div>
                            <div class='row'>";

                    if (!empty($key['tipo_resposta'])) {

                        $sql = "SELECT  tbl_tipo_resposta_item.descricao            ,
                                        tbl_tipo_resposta.label_inicio              ,
                                        tbl_tipo_resposta.label_fim                 ,
                                        tbl_tipo_resposta.label_intervalo           ,
                                        tbl_tipo_resposta.tipo_descricao            ,
                                        tbl_tipo_resposta_item.tipo_resposta_item
                                FROM    tbl_tipo_resposta
                           LEFT JOIN    tbl_tipo_resposta_item using(tipo_resposta)
                                WHERE   tbl_tipo_resposta.tipo_resposta = ".$key['tipo_resposta']."
                                AND     tbl_tipo_resposta.fabrica       = $fabrica
                          ORDER BY      tbl_tipo_resposta_item.ordem ";

                        $res = pg_query($con,$sql);
                        if (pg_num_rows($res)>0) {
                                $html_pesquisa .= '<div class="col-sm-12 linha">';
                            for ($x=0; $x < pg_num_rows($res); $x++) {

                                if (!empty($respostasPergunta)) {
                                    $disabled = 'disabled="DISABLED"';
                                }

                                $item_tipo_resposta_desc            = pg_fetch_result($res, $x, 'descricao');
                                $item_tipo_resposta_tipo            = pg_fetch_result($res, $x, 'tipo_descricao');
                                $item_tipo_resposta_label_inicio    = pg_fetch_result($res, $x, 'label_inicio');
                                $item_tipo_resposta_label_fim       = pg_fetch_result($res, $x, 'label_fim');
                                $item_tipo_resposta_label_intervalo = pg_fetch_result($res, $x, 'label_intervalo');
                                $tipo_resposta_item_id              = pg_fetch_result($res, $x, 'tipo_resposta_item');

                                if ($item_tipo_resposta_tipo == 'radio' or $item_tipo_resposta_tipo == 'checkbox') {
                                    $value_resposta = $tipo_resposta_item_id;
                                }else{
                                    $value_resposta = $item_tipo_resposta_desc;
                                }

                                switch ($item_tipo_resposta_tipo) {
                                    case 'radio':
                                        if(strlen($item_tipo_resposta_desc) > 0){
                                            $html_pesquisa .= $item_tipo_resposta_desc;
                                            $value_resposta = $tipo_resposta_item_id;
                                            if (is_array($respostasPergunta) and !empty($respostasPergunta)) {
                                                if (in_array($tipo_resposta_item_id,$respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
                                                    $checked_radio = "checked='CHECKED'";
                                                }
                                            }
                                            $html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'" name="perg_opt'.$key['pergunta'].'"   value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';
                                        }
                                        break;
                                    case 'text':
                                        $item_tipo_resposta_desc = $key['txt_resposta'];
                                        $disabled_resposta = "disabled='DISABLED'";
                                        $value_resposta = $item_tipo_resposta_desc;
                                        if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                            if (!empty($respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
                                                $value_resposta = $respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][0];
                                            }
                                        }
                                        $html_pesquisa .= ' <input  class="form-control" type="'.$item_tipo_resposta_tipo.'" name="perg_opt'.$key['pergunta'].'"   value="'.$value_resposta.'" '.$disabled.' />';

                                        break;
                                    case 'range':
                                        $value_resposta = $item_tipo_resposta_desc;
                                        for ($z=$item_tipo_resposta_label_inicio; $z <= $item_tipo_resposta_label_fim ; $z+=$item_tipo_resposta_label_intervalo) {
                                            if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                                if (in_array($z,$respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
                                                    $checked_radio = "checked='CHECKED'";
                                                }else{
                                                    $checked_radio = "";
                                                }
                                            }

                                            $html_pesquisa .= $z.' <input type="radio" name="perg_opt'.$key['pergunta'].'" value="'.$z.'" '.$checked_radio.$disabled.' /> &nbsp; &nbsp;';
                                        }

                                        break;
                                    case 'checkbox':
                                        $html_pesquisa .= $item_tipo_resposta_desc;
                                        $value_resposta = $tipo_resposta_item_id;
                                        if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                            if (in_array($tipo_resposta_item_id,$respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
                                                $checked_radio = "checked='CHECKED'";
                                            }
                                        }
                                        $html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'" name="perg_opt_checkbox_'.$key['pergunta'].'_'.$i.'_'.$value_resposta.'"   value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';

                                        break;
                                    case 'textarea':
                                        if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                            if (!empty($respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
                                                $value_resposta = $respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][0];
                                            }
                                        }
                                        $html_pesquisa .= ' <textarea class="form-control" name="perg_opt'.$key['pergunta'].'"  '.$disabled.'  >'.$value_resposta.'</textarea> ';

                                        break;
                                    case 'date':
                                        if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                            if (!empty($respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
                                                $value_resposta = $respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][0];
                                            }
                                        }
                                        $width="";
                                        $html_pesquisa .= ' <input  type="text" class="form-control data" name="perg_opt'.$key['pergunta'].'"  class="frm date" value="'.$value_resposta.'" '.$disabled.' />';

                                        break;
                                    default:
                                        break;
                                }
                                unset($checked_radio);
                            }
                                $html_pesquisa .= '</div>';
                        }

                    }else{
                        $html_pesquisa .= "";
                    }

                    $html_pesquisa .= "</div>";
                    $i++;
                }
    }
$html_pesquisa .= "</div>";
    if (is_array($respostasPergunta) and empty($respostasPergunta)) {
        $html_pesquisa .= '<br >
        <div class="container">
            <div class="row">
                <div class="col-sm-12 tac">
                    <input type="hidden" name="qtde_perg" value="'.$i.'">
                    <input type="button" value="Gravar" id="btn_grava_pesquisa" class="btn btn-block btn-lg btn-success" rel="'.$pesquisa.'">
                    <div class="td_btn_gravar_pergunta"></div>
                </div>
            </div>
        </div><br ><br >';

    }
    echo $html_pesquisa;
?>


    <div class="alert alert-error" style="display: none">
        <h4 class='errorPergunta'></h4>
    </div>

    <div class='divTranspBlock' style='margin-top:57px;margin-left:378px;display:none;background-color:#000;position:absolute; z-index:1;width:900px;height:295px;opacity:0.65;-moz-opacity: 0.65;filter: alpha(opacity=65);'>
    </div>

    <div id="div_pesquisa" style="width:100%;border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px;display:none">
    </div>

    <div class='agradecimentosPesquisa alert alert-success' style='display:none'>
        <div class="alert alert-success">
            <strong>Gravado com Sucesso!</strong><br />
            Em nome da <b><?=$fabrica_nome?></b>, gostaríamos de agradecer a sua atenção, e desejamos-lhe um excelente dia!
        </div>
    </div>
</div>
</body>
<html>
