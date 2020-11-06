<?
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

$msg_erro = "";
$msg_debug = "";

$pesquisa   = $_GET['pesquisa'];
$os         = $_GET['os'];
$familia    = $_GET['familia'];

if($pesquisa == 673) $login_fabrica = 10;

if(empty($pesquisa)){
    $cond = "";
    if(in_array($login_fabrica, array(85,94,129,138,145,152,161,180,181,182))){
        $cond = "AND     categoria = 'posto'";
    }
    if($login_fabrica == 88){
        if (strlen($familia) > 0){
            $sqlFamilia = "
                SELECT  pesquisa
                FROM    tbl_familia_laudo
                WHERE   familia = $familia
            ";
            $resFamilia = pg_query($con,$sqlFamilia);
            $pesquisa = pg_fetch_result($resFamilia,0,pesquisa);

            $cond = "AND pesquisa = $pesquisa";
        }
    }
    $sqlX = "   SELECT  pesquisa,
                        resposta_obrigatoria
                FROM    tbl_pesquisa
        WHERE   ativo IS TRUE
        AND     fabrica = $login_fabrica
                $cond
          ORDER BY      pesquisa DESC
                LIMIT   1 ";
    $res = @pg_exec ($con,$sqlX);
    if(pg_num_rows($res) > 0) {
        $pesquisa   = pg_result ($res,0,0) ;
        $obg        = pg_result ($res,0,1) ;
    }
}
$title = "Pesquisa" ;

if(filter_input(INPUT_POST,'sem_resposta')){

    $pesquisa   = filter_input(INPUT_POST,'pesquisa');


    $sqlBuscaPerguntas = "
        SELECT  pergunta
        FROM    tbl_pesquisa_pergunta
        WHERE   pesquisa = $pesquisa
  ORDER BY      ordem
    ";
    $resBuscaPerguntas = pg_query($con,$sqlBuscaPerguntas);
    $perguntasSemRespostas = pg_fetch_all_columns($resBuscaPerguntas,0);

    $res = pg_query($con,'BEGIN TRANSACTION');

    foreach($perguntasSemRespostas as $pergunta){
        $sql = "
            INSERT INTO tbl_resposta (
                pergunta            ,
                pesquisa            ,
                data_input          ,
                posto               ,
                sem_resposta
            ) VALUES (
                $pergunta,
                $pesquisa,
                CURRENT_TIMESTAMP,
                $login_posto,
                TRUE
            )
        ";
        $res = pg_query($con,$sql);
        if (pg_last_error($con)){
            $erro[] = pg_last_error($con) ;
        }
    }
// print_r($erro);exit;
    if(count($erro) > 0){
        $res = pg_query($con,'ROLLBACK TRANSACTION');
        echo "1|$erro";
    }else{
        $res = pg_query($con,'COMMIT TRANSACTION');
//         echo pg_last_error($con);
        echo "0|sucesso";
    }
    exit;
}
if ($_POST['ajax'] && $_POST['gravaPerguntas'] ){
    $erro           = array();
    $arrayCheckbox  = array();
    $qtde_perg      = $_POST['qtde_perg'];
    $pesquisa       = $_POST['pesquisa'];
    $input          = $_POST['input'];
    $textarea       = $_POST['textarea'];
    $os             = (!empty($_POST['os'])) ? $_POST['os'] : "null";
    $pergunta           = explode("&",$input);
    $perguntaTextarea   = explode("&",$textarea);

    foreach($pergunta as $key=>$value){
        $dados = explode("=",$value);
        $valores[$dados[0]] = $dados[1];
    }
    foreach($perguntaTextarea as $key=>$value){
        $dadosT = explode("=",$value);
        $valoresT[$dadosT[0]] = $dadosT[1];
    }

    $valores = array_merge($valores, $valoresT);
    foreach ($valores as $keyPost => $valuePost) {

        $keyExplode = explode("_",$keyPost);
        if($keyExplode[2]=='checkbox'){
            $arrayCheckbox[$keyExplode[3]][] = $keyExplode[5];
        }
    }

    if(in_array($login_fabrica,array(129,152,180,181,182))){
        $sqlObrigatorio = " SELECT resposta_obrigatoria
                            FROM tbl_pesquisa
                            WHERE pesquisa = $pesquisa
                            AND fabrica = $login_fabrica
                            AND ativo IS TRUE ";
        $resObrigatorio = pg_query($con, $sqlObrigatorio);

        if(pg_num_rows($resObrigatorio) > 0){
            $pesquisa_obrigatoria = pg_fetch_result($resObrigatorio, 0, 'resposta_obrigatoria');
        }
    }

    $res = pg_query($con,'BEGIN');

    for ($i=0; $i < $valores['qtde_perg']; $i++) {

        $pergunta       = $valores['perg_'.$i];
        $tipo_resposta  = $valores['hidden_'.$i];
        $obrigatorio    = $valores['obrig_'.$i];

        $resposta = (isset($valores['perg_opt'.$pergunta])) ? utf8_decode(trim($valores['perg_opt'.$pergunta])) : '';

        if(strlen($resposta) == 0 && $obrigatorio == 't' && $tipo_resposta != "checkbox"){
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

                    $sqlItens = "SELECT tbl_tipo_resposta_item.descricao FROM tbl_tipo_resposta_item where tipo_resposta_item = ".$value;
                    $resItens = pg_query($con,$sqlItens);

                    if (pg_num_rows($resItens)>0) {

                        $txt_resposta = pg_fetch_result($resItens,0,0);

                    }else{
                        $txt_resposta = '';
                    }

                    if ($login_fabrica == 88) {
                        $sql = "INSERT INTO tbl_resposta (
                                pergunta,
                                txt_resposta,
                                tipo_resposta_item,
                                pesquisa,
                                data_input,
                                os,
                                posto
                            )VALUES(
                                $pergunta,
                                '$txt_resposta',
                                $resposta,
                                '$pesquisa',
                                current_timestamp,
                                $os,
                                $login_posto
                            )
                            ";
                    } else {
                         $sql = "INSERT INTO tbl_resposta (
                                pergunta,
                                txt_resposta,
                                tipo_resposta_item,
                                pesquisa,
                                data_input,
                                posto
                            )VALUES(
                                $pergunta,
                                '$txt_resposta',
                                $resposta,
                                '$pesquisa',
                                current_timestamp,
                                $login_posto
                            )
                            ";
                    }
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

            $sqlItens = "SELECT tbl_tipo_resposta_item.descricao
                            FROM tbl_tipo_resposta_item
                            where tipo_resposta_item = $resposta";
            $resItens = pg_query($con,$sqlItens);

            if (pg_num_rows($resItens)>0) {

                $txt_resposta = pg_fetch_result($resItens,0,0);

            }else{

                $txt_resposta = $resposta;

            }

        }elseif($login_fabrica == 88 AND empty($resposta)){
            $resposta = 'null';
        }

        if ($login_fabrica == 88) {
            $sql = "INSERT INTO tbl_resposta (
                        pergunta,
                        txt_resposta,
                        tipo_resposta_item,
                        pesquisa,
                        data_input,
                        os,
                        posto
                    )VALUES(
                        $pergunta,
                        '$txt_resposta',
                        $resposta,
                        '$pesquisa',
                        current_timestamp,
                        $os,
                        $login_posto
                    )
                    ";
        } else {
            $sql = "INSERT INTO tbl_resposta (
                        pergunta,
                        txt_resposta,
                        tipo_resposta_item,
                        pesquisa,
                        data_input,
                        posto
                    )VALUES(
                        $pergunta,
                        '$txt_resposta',
                        $resposta,
                        '$pesquisa',
                        current_timestamp,
                        $login_posto
                    )
                    ";
        }

        $res = pg_query($con,$sql);
        if (pg_last_error($con)){
            $erro[] = pg_last_error($con) ;
        }

    }
    if($login_fabrica == 88){
        $sql    = "INSERT INTO tbl_laudo_tecnico_os (os, fabrica, titulo) VALUES ($os, $login_fabrica, 'Laudo Técnico')";
        $res    = pg_query($con, $sql);
        $erro[] = pg_last_error($con) ;
    }
    if($login_fabrica <> 129){
        $erro = array_filter($erro);
    }
    if (count($erro)>0){
        $erro = implode('<br>ttt', $erro);
        if(strpos($erro, 'syntax erro') > 0 ){
            $erro = "Favor preencher todas as respostas da pesquisa";
        }elseif(strpos($erro,'preencher')){
            $erro = "Favor, preencher as respostas obrigatórias";
        }elseif($login_fabrica == 129){
            $erro = "Favor, preencher as respostas obrigatórias";
        }

        $res = pg_query($con,'ROLLBACK');
    }else{
        $res = pg_query($con,'COMMIT');
    }

    if ($erro){
        echo "1|$erro";
    }else{
        echo "0|Sucesso";
    }

    exit;

}

include_once 'funcoes.php';


?>
<link href="admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="admin/css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
<script src="admin/plugins/jquery.maskedinput_new.js"></script>


<link rel="stylesheet" type="text/css" href="plugins/jquery/datepick/telecontrol.datepick.css" />

<script>

$(function(){

    var datepickerOptions = { maxDate: 0, dateFormat: "dd/mm/yy" };

    $(".data").datepicker(datepickerOptions).mask("99/99/9999");

    $('#btn_grava_pesquisa').click(function(){
        var curDateTime = new Date();
        var relBtn = $(this).attr('rel');
        var login_fabrica = <?=$login_fabrica?>;

        if (login_fabrica == 88) {
            var os = "<?=$os?>";
        }

        var data = {
            ajax:true,
            gravaPerguntas:true,
            pesquisa:relBtn,
            input:$('#pesquisa_satisfacao').find('input').serialize(),
            textarea:$('#pesquisa_satisfacao').find('textarea').serialize()
        }

        if (login_fabrica == 88) {
            data.os = os;
        }

        $.ajax({
            type: "POST",
            url: "opiniao_posto_new.php",
            data: data,
            beforeSend: function(){

                $('input[name=pesquisa]').attr('disabled',true);
                $('#btn_grava_pesquisa').hide();
                $('#btn_sem_resposta').hide();
                $('.td_btn_gravar_pergunta').show();
                $('.td_btn_gravar_pergunta').html("&nbsp;&nbsp;Gravando...&nbsp;&nbsp;<br><img src='imagens/loading_bar.gif'> ");
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
                $('#btn_sem_resposta').show();


            }else{
                $('div.alert-error').hide();
                $('.divTranspBlock').hide();
                $('#pesquisa_satisfacao').find('input').attr('disabled',true);
                $('#pesquisa_satisfacao').find('textarea').attr('disabled',true);
                $('.agradecimentosPesquisa').show();
                $('.td_btn_gravar_pergunta').hide();
                setTimeout(function(){
                    if(login_fabrica == 88){
                        window.location = 'os_item_new.php?os='+os;
                    }else{
                        window.location = "./login.php";
                    }
                },2000);
            }
        });

    });

    $("#btn_sem_resposta").click(function(e){
        var relBtn          = $(this).attr('rel');

        if(confirm("Deseja marcar como SEM RESPOSTA a pesquisa?")){
            $.ajax({
                type:"POST",
                url: "opiniao_posto_new.php",
                data:{
                    ajax:true,
                    sem_resposta:true,
                    pesquisa:relBtn
                },
                beforeSend: function(){
                    $('#btn_grava_pesquisa').hide();
                    $('#btn_sem_resposta').hide();
                    $('.td_btn_gravar_pergunta').show();
                    $('.td_btn_gravar_pergunta').html("&nbsp;&nbsp;Gravando...&nbsp;&nbsp;<br><img src='../../imagens/loading_bar.gif'> ");

                    $('.divTranspBlock').show();
                }

            })
            .done(function(http){
                results = http.split('|');
                if (results[0] == 1){
                    $('h4.errorPergunta').html(results[1]);
                    $('div.alert-error').show();
                    $('.td_btn_gravar_pergunta').show();
                    $('.divTranspBlock').hide();
                    $('#btn_grava_pesquisa').show();
                    $('#btn_sem_resposta').show();
                }else{
                    $('div.alert-error').hide();
                    $('.divTranspBlock').hide();
                    $('#pesquisa_satisfacao').find('input').attr('disabled',true);
                    $('#pesquisa_satisfacao').find('textarea').attr('disabled',true);
                    $('.agradecimentosPesquisa').show();
                    $('.td_btn_gravar_pergunta').hide();
                    setTimeout(function(){
                        window.location.reload();
                    },2000);
                }

                var height = $("body").outerHeight();
                parent.SetIFrameHeight(height);
            });
        }
    });

});
</script>

<?

    if($obg == 'f'){
        include "cabecalho.php";
    }

    /*PEGA O TEXTO DE AJUDA na tbl_pesquisa.texto_ajuda*/
    $sql = "SELECT tbl_pesquisa.texto_ajuda FROM tbl_pesquisa WHERE pesquisa = $pesquisa and fabrica=$login_fabrica";
    $res = pg_query($con,$sql);
    $texto_ajuda = (pg_num_rows($res) > 0) ? pg_fetch_result($res,0,0) : '' ;
   /* $leftJoin = "";
    if ($login_fabrica == 30) {
        $leftJoin = "LEFT ";
    }*/
    $sql = "SELECT  tbl_pesquisa_pergunta.ordem,
                    tbl_pergunta.pergunta,
                    tbl_pergunta.descricao,
                    tbl_pergunta.tipo_resposta,
                    tbl_tipo_resposta.tipo_descricao,
                    tbl_tipo_resposta.obrigatorio,
                    tbl_pesquisa.pesquisa,
                    tbl_tipo_pergunta.descricao as tipo_pergunta_descricao,
                    tbl_tipo_pergunta.tipo_pergunta
            FROM    tbl_pesquisa_pergunta
            JOIN    tbl_pergunta        USING(pergunta)
            JOIN    tbl_pesquisa        USING(pesquisa)
		left JOIN    tbl_tipo_pergunta   USING(tipo_pergunta)
       LEFT JOIN    tbl_tipo_resposta   ON tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta
            WHERE   tbl_pesquisa.fabrica  = $login_fabrica
            AND     tbl_pesquisa.pesquisa = $pesquisa
            AND     tbl_pergunta.ativo IS TRUE
      ORDER BY      tbl_pesquisa_pergunta.ordem";
    $res = pg_query($con,$sql);

    $html_pesquisa .= '<br/><div class="container-fluid">';
    if(in_array($login_fabrica,[129,138])){
        $html_pesquisa.='<div style="padding-top: 5px; margin-left: 7px;">
            <strong>Obs*</strong> Pergunta destacada em <strong style="color:#F00;">vermelho</strong> é de resposta obrigatória.
        </div>';
    }
    $html_pesquisa .= '<table id="pesquisa_satisfacao" class="table table-striped table-bordered table-hover table-fixed table_perguntas_pesquisa" >';
    //exibe o texto de ajuda
    $html_pesquisa .= '
            <thead>
            <tr>
                <th colspan="100%" style="text-align:center;" class="titulo_tabela">
                    '.nl2br($texto_ajuda).'
                </th>
            </tr>
            </thead>
            <tbody>';
    if (pg_num_rows($res)>0) {
        $pesquisa = pg_fetch_result($res,0,pesquisa);
        $i = 0;
        $respostasPergunta = array();
        //percorre o array da consulta principal 1ª vez para jogar as respostas em um array

        if(strlen($hd_chamado) > 0){
            $cond_hd_chamado = "and     hd_chamado  = $hd_chamado";
        }

        if(strlen($os) > 0){
            $cond_hd_chamado = "and os  = $os";
        }

        foreach (pg_fetch_all($res) as $key) {
            $sql = "SELECT  pergunta            ,
                            txt_resposta        ,
                            tipo_resposta_item
                    FROM    tbl_resposta
                    WHERE   pergunta    = ".$key['pergunta']."
                    AND     pesquisa    = ".$key['pesquisa']."
                    AND posto = $login_posto
                    $cond_hd_chamado
                ORDER BY pergunta";
            $resRespostas = pg_query($con,$sql);
            $erro[] = pg_last_error($con) ;
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
                        <td style='vertical-align:middle'>
                            <input type='hidden' name='perg_".$i."' value='".$key['pergunta']."' placeholder=''>
                            <input type='hidden' name='hidden_$i' value='".$key['tipo_descricao']."' >
                            <input type='hidden' name='obrig_$i' value='".$key['obrigatorio']."' >
                            <label > ".$key['ordem']." </label>
                        </td>
                        <td style='";
                        if($key['obrigatorio'] == 't') {
                            $html_pesquisa .= "color:#F00;";
                        }
                        $html_pesquisa .= "vertical-align:middle;'";
                        $html_pesquisa .= ">
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
                        AND     tbl_tipo_resposta.fabrica       = $login_fabrica
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
                            $nowrap = "";

                            if (in_array($login_fabrica, [151]) && $x + 1 == pg_num_rows($res)) {
                                $colspan = "100%";
                            }

                            $nowrap = "nowrap";
                        } else {
                            $colspan = "100%";
                            $nowrap = "nowrap";
                        }


                        // if((($x+1) == pg_num_rows($res)) && (in_array($item_tipo_resposta_tipo, array('checkbox','radio'))) ){
                        //     $colspan = "100%";
                        // }

                        $html_pesquisa .= '<td align="center" ' . $nowrap . ' colspan="'.$colspan.'" style="vertical-align:middle;">';
                        
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
                                $html_pesquisa .= ' <input class="data"  type="text"  style="width:'.$width.'" name="perg_opt'.$key['pergunta'].'"  class="frm date" value="'.$value_resposta.'" '.$disabled.' />';

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
            <input type="button" value="Gravar" id="btn_grava_pesquisa" class="btn" rel="'.$pesquisa.'">';
        if($login_fabrica == 145){
            $html_pesquisa .= '
            <input type="button" value="Sair sem Responder" id="btn_sem_resposta" class="btn" rel="'.$pesquisa.'">
            ';
        }
            $html_pesquisa .= '<div class="td_btn_gravar_pergunta"></div>
        </td></tr>';

    }
    $html_pesquisa .= "</tbody>";
    $html_pesquisa .= "</table>";
    $html_pesquisa .= "</div>";
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
        <p >
            <?=traduz('Gravado com Sucesso')?>
        </p>
    </div>
<?
 include "rodape.php";

