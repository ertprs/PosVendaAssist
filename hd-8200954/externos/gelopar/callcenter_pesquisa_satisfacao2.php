<?php
/**
  * @author William Ap. Brandino
  * @description Pesquisa de satisfacao Gelopar - HD 1365720 - Acesso por email
  * @param Int $atendimento - Seleciona qual atendimento o consumidor participou
 */

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';

$atendimento    = $_GET['atendimento'];
$fabrica        = 85;

$sql = "SELECT  tbl_hd_chamado.fabrica
        FROM    tbl_hd_chamado
        WHERE   tbl_hd_chamado.hd_chamado = $atendimento
";
$res = pg_query($con,$sql);

if ($_POST['ajax'] && $_POST['gravaPerguntas']){

    $erro           = array();
    $arrayCheckbox  = array();
    $hdChamado      = $_POST['hdChamado'];
    $qtde_perg      = $_POST['qtde_perg'];
    $pesquisa       = $_POST['pesquisa'];
    $input          = $_POST['input'];
    $textarea       = $_POST['textarea'];

    $pergunta           = explode("&",$input);
    $perguntaTextarea   = explode("&",$textarea);

    foreach($pergunta as $key=>$value){
        $dados = explode("=",$value);
        $valores[$dados[0]] = $dados[1];
    }

    foreach($perguntaTextarea as $key=>$value){
        $dadosT = explode("=",$value);
        $valoresT[$dados[0]] = $dados[1];
    }

    foreach ($valores as $keyPost => $valuePost) {

        $keyExplode = explode("_",$keyPost);

        if($keyExplode[2]=='checkbox'){
            $arrayCheckbox[$keyExplode[3]][] = $keyExplode[5];
        }
    }

    $res = pg_query($con,'BEGIN');

    for ($i=0; $i < $valores['qtde_perg']; $i++) {

        $pergunta = $valores['perg_'.$i];
        $tipo_resposta = $valores['hidden_'.$i];

        $resposta = (isset($valores['perg_opt'.$pergunta])) ? utf8_decode(trim($valores['perg_opt'.$pergunta])) : '';

        if (in_array($tipo_resposta, array('text','range','textarea','date'))) {
            $txt_resposta = htmlentities(str_replace("+"," ",rawurldecode($resposta)),ENT_QUOTES,'UTF-8');
            $resposta = 'null';
        }

        if ( $tipo_resposta == 'checkbox') {
            if(count($arrayCheckbox[$pergunta]) > 0){

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
                                                        hd_chamado          ,
                                                        txt_resposta        ,
                                                        tipo_resposta_item  ,
                                                        pesquisa            ,
                                                        data_input
                                                    )VALUES(
                                                        $pergunta           ,
                                                        $hdChamado          ,
                                                        '$txt_resposta'     ,
                                                        $resposta           ,
                                                        '$pesquisa'         ,
                                                        current_timestamp
                                                    )
                        ";
                    $res = pg_query($con,$sql);
                }
                continue ;
            }else{
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
        }
        $sql = "INSERT INTO tbl_resposta(
                                            pergunta            ,
                                            hd_chamado          ,
                                            txt_resposta        ,
                                            tipo_resposta_item  ,
                                            pesquisa            ,
                                            data_input
                                        )VALUES(
                                            $pergunta           ,
                                            $hdChamado          ,
                                            '$txt_resposta'     ,
                                            $resposta           ,
                                            '$pesquisa'         ,
                                            current_timestamp
                                        )
                ";

        $res = pg_query($con,$sql);

        if (pg_last_error($con)){
            $erro[] = pg_last_error($con) ;
        }

    }
    if (count($erro)>0){
        $erro = implode('<br>ttt', $erro);
        if(strpos($erro, 'syntax erro') > 0 ){
            $erro = "Favor preencher todas as respostas da pesquisa";
        }
        $res = pg_query($con,'ROLLBACK TRANSACTION');
    }else{
        $res = pg_query($con,'COMMIT TRANSACTION');
    }

    if ($erro){
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
<meta http-equiv="content-Type" content="text/html; charset=iso-8859-1">
<title>PESQUISA DE SATISFAÇÃO</title>

<!-- <link href="../../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
 -->

<link href="../../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../../admin/css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="../../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="../../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="../../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="../../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>


<!-- <script src="../../admin/js/jquery-1.8.3.min.js"></script>
 -->
<script type="text/javascript">
$().ready(function(){
    $('#btn_grava_pesquisa').click(function(){

        var curDateTime = new Date();
        var relBtn = $(this).attr('rel');
        var hdChamado = <?=$atendimento?>;

        console.log(hdChamado);
        $.ajax({
            type: "POST",
            url: "<?=$PHP_SELF?>",
            data: {
                ajax:true,
                gravaPerguntas:true,
                pesquisa:relBtn,
                hdChamado:hdChamado,
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
            console.log(http);
            //results = http.responseText;
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

    width: 300px;
}

</style>
</head>

<body>

<div class="row-fluid">
    <div class="span12">
        <div id="topo">
            <div class="container">
                <br />
                <img class="logo_gl" src='../../logos/logo_inst_gelopar.png'/>
            </div>
        </div>
    </div>
</div><br /><br /><hr />

<div class='container'>
    <table  id="pesquisa_satisfacao" class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
            <tr>
                <th colspan="100%" id="ver_pesquisa" class="titulo_tabela">Pesquisa de Satisfação do Cliente </th>
            </tr>
        </thead>
        <tbody>
<?php


                    $sql = "SELECT  tbl_pesquisa_pergunta.ordem     ,
                                    tbl_pergunta.pergunta           ,
                                    tbl_pergunta.descricao          ,
                                    tbl_pergunta.tipo_resposta      ,
                                    tbl_tipo_resposta.tipo_descricao,
                                    tbl_pesquisa.pesquisa
                            FROM    tbl_pesquisa_pergunta
                            JOIN    tbl_pergunta        USING(pergunta)
                            JOIN    tbl_pesquisa        USING(pesquisa)
                       LEFT JOIN    tbl_tipo_resposta   ON tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta
                            WHERE   tbl_pesquisa.categoria  = 'externo'
                            AND     tbl_pesquisa.fabrica    = $fabrica
                            AND     tbl_pergunta.ativo      IS TRUE
                      ORDER BY      tbl_pesquisa_pergunta.ordem";
                    $res = pg_query($con,$sql);

    if (pg_num_rows($res)>0) {
        $pesquisa = pg_fetch_result($res,0,pesquisa);
        $i = 0;
        $respostasPergunta = array();
        //percorre o array da consulta principal 1ª vez para jogar as respostas em um array
        foreach (pg_fetch_all($res) as $key) {
            $sql = "SELECT  pergunta            ,
                            txt_resposta        ,
                            tipo_resposta_item
                    FROM    tbl_resposta
                    WHERE   pergunta    = ".$key['pergunta']."
                    AND     pesquisa    = ".$key['pesquisa']."
                    and     hd_chamado  = $atendimento
                ORDER BY pergunta";
                      #echo nl2br($sql);exit;
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
        //percorre a segunda vez para montar o formulário
        foreach (pg_fetch_all($res) as $key) {


            $html_pesquisa .= "
                    <tr>
                        <td>
                            <input type='hidden' name='perg_".$i."' value='".$key['pergunta']."' placeholder=''>
                            <input type='hidden' name='hidden_$i' value='".$key['tipo_descricao']."' >
                            <label > ".$key['ordem']." </label>
                        </td>
                        <td>
                            ".$key['descricao']."
                        </td>";

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

                        if (in_array($item_tipo_resposta_tipo, array('checkbox','radio'))) {
                            $colspan = "";
                            $width = "";
                        }else{
                            $colspan = "100%";
                        }

                        if((($x+1) == pg_num_rows($res)) && (in_array($item_tipo_resposta_tipo, array('checkbox','radio'))) ){
                            $colspan = "100%";
                        }

                        $html_pesquisa .= '<td align="center" nowrap colspan="'.$colspan.'" >';

                        if ($item_tipo_resposta_tipo == 'radio' or $item_tipo_resposta_tipo == 'checkbox') {
                            $value_resposta = $tipo_resposta_item_id;
                        }else{
                            $value_resposta = $item_tipo_resposta_desc;
                        }

                        switch ($item_tipo_resposta_tipo) {
                            case 'radio':
                                $html_pesquisa .= $item_tipo_resposta_desc;
                                $value_resposta = $tipo_resposta_item_id;
                                if (is_array($respostasPergunta) and !empty($respostasPergunta)) {
                                    if (in_array($tipo_resposta_item_id,$respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
                                        $checked_radio = "checked='CHECKED'";
                                    }
                                }
                                $html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt'.$key['pergunta'].'"   value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';

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
                                $html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt'.$key['pergunta'].'"   value="'.$value_resposta.'" '.$disabled.' />';

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
                                $html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt_checkbox_'.$key['pergunta'].'_'.$i.'_'.$value_resposta.'"   value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';

                                break;
                            case 'textarea':
                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][0];
                                    }
                                }
                                $html_pesquisa .= ' <textarea name="perg_opt'.$key['pergunta'].'"  '.$disabled.' style="width:90%" >'.$value_resposta.'</textarea> ';

                                break;
                            case 'date':
                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][0];
                                    }
                                }
                                $width="";
                                $html_pesquisa .= ' <input  type="text"  style="width:'.$width.'" name="perg_opt'.$key['pergunta'].'"  class="frm date" value="'.$value_resposta.'" '.$disabled.' />';

                                break;
                            default:
                                break;
                        }
                        $html_pesquisa .= '</td>';
                        unset($checked_radio);
                    }
                }

            }else{
                $html_pesquisa .= "<td colspan='3'>&nbsp; </td>";
            }

            $html_pesquisa .= "
                    </tr>";
            $i++;
        }
    }

    if (is_array($respostasPergunta) and empty($respostasPergunta)) {
        $html_pesquisa .= '<tr><td colspan="100%" style="text-align:center">
            <input type="hidden" name="qtde_perg" value="'.$i.'">
            <input type="button" value="Gravar" id="btn_grava_pesquisa" class="btn" rel="'.$pesquisa.'">
            <div class="td_btn_gravar_pergunta"></div>
        </td></tr>';

    }
    echo $html_pesquisa;
?>
        </tbody>
    </table>

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
            EM NOME DA <b>GELOPAR</b>, GOSTARIAMOS DE AGRADECER SUA ATENÇÃO, E DESEJAMOS-LHE UM EXCELENTE DIA.
        </div>
    </div>
</div>
</body>
<html>
