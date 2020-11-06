
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>

<?php

$plugins = array(
    "datepicker",
    "mask"
);

include("plugin_loader.php");
?>

<style type="text/css">
    #msg_sucesso,#msg_erro {display:none; width:700px; margin:auto;}
    .nota{cursor:pointer;}

    #sem_resposta {display:none;}

</style>

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();
    $(function() {
        $.datepickerLoad(Array("data_inicial"));
    });


</script>

<div><?php

    if ($login_fabrica != 51) {
        if(in_array($login_fabrica, array(94))){
            $sql_desc = "SELECT descricao FROM tbl_pesquisa WHERE pesquisa = $pesquisa_selecionada AND fabrica = $login_fabrica";
            $res_desc = pg_query($con, $sql_desc);

            if(pg_num_rows($res_desc) > 0){
                $desc_pesquisa = pg_fetch_result($res_desc, 0, 'descricao');
                $desc_pesquisa = "- $desc_pesquisa";
            }

        }
    ?>

    <div id="msg_sucesso" class="sucesso"></div>
    <div class="alert alert-success" style="display: none">
        <h4 class='successPergunta'></h4>
    </div>

    <div class="alert alert-error" style="display: none">
        <h4 class='errorPergunta'></h4>
    </div>
        <form action="<?=$PHP_SELF . '?' . CAMPO_PESQUISA . "=$value"?>" method="POST" id="frm_pesquisa"><?php

    }
    if (in_array($login_fabrica,array(35,129,145))) {
?>
    <div style="padding-top: 5px; margin-left: 7px;">
        <strong>Obs*</strong> Pergunta destacada em <strong style='color:#F00;'>vermelho</strong> é de resposta obrigatória.
    </div>
<?php
    }

    if($login_fabrica == 94){

        if(!empty($pesquisa_selecionada)){
            $condPesquisa = "AND tbl_pesquisa.pesquisa = $pesquisa_selecionada";
        }
    }
    $sql = "SELECT  tbl_pesquisa_pergunta.ordem     ,
                    tbl_pergunta.pergunta           ,
                    tbl_pergunta.descricao          ,
                    tbl_pergunta.tipo_resposta      ,
                    tbl_tipo_resposta.tipo_descricao,
                    tbl_tipo_resposta.obrigatorio,
                    tbl_pesquisa.descricao AS pesquisa_desc,
                    tbl_pesquisa.pesquisa
            FROM    tbl_pesquisa_pergunta
            JOIN    tbl_pergunta        USING(pergunta)
            JOIN    tbl_pesquisa        USING(pesquisa)
       LEFT JOIN    tbl_tipo_resposta   ON tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta
            WHERE   tbl_pesquisa.categoria  = '$local_pesquisa'
            AND     tbl_pesquisa.fabrica    = $login_fabrica
            AND     tbl_pesquisa.ativo      IS TRUE
            AND     tbl_pergunta.ativo      IS TRUE
            $condPesquisa
      ORDER BY      tbl_pesquisa_pergunta.ordem";

    pg_prepare($con, 'sql_principal', $sql);
    $res = pg_execute($con, 'sql_principal', array());
    if (pg_num_rows($res)>0) {

        $desc_pesquisa = pg_fetch_result($res,0,pesquisa_desc);
        ?>

        <table id="pesquisa_satisfacao" class="table table-striped table-bordered table-hover table-fixed" cellspacing="1" cellpadding="2">
        <thead>
            <tr>
                <th colspan="100%" class="titulo_tabela">
                    Pesquisa de Satisfação: <?=$desc_pesquisa?>
                </th>
            </tr>
        </thead>
        <tbody>
        <?php
        $pesquisa = pg_fetch_result($res,0,pesquisa);
        $i = 0;
        $respostasPergunta = array();
        //percorre o array da consulta principal 1ª vez para jogar as respostas em um array
        for ($i=0; $i < pg_num_rows($res) ; $i++) {
            $key_ordem = pg_fetch_result($res, $i, ordem);
            $key_pergunta = pg_fetch_result($res, $i, pergunta);
            $key_descricao = pg_fetch_result($res, $i, descricao);
            $key_tipo_resposta = pg_fetch_result($res, $i, tipo_resposta);
            $key_tipo_descricao = pg_fetch_result($res, $i, tipo_descricao);
            $key_obrigatorio = pg_fetch_result($res, $i, obrigatorio);
            $key_pesquisa_desc = pg_fetch_result($res, $i, pesquisa_desc);
            $key_pesquisa = pg_fetch_result($res, $i, pesquisa);

            $cond_callcenter = (empty($callcenter) OR $callcenter == "null") ? " hd_chamado isnull " : " hd_chamado  = $callcenter " ;
            $cond_os = (empty($os) OR $os == "null") ? " os isnull " : " os  = $os " ;

            $sql = "SELECT  pergunta            ,
                            txt_resposta        ,
                            tipo_resposta_item
                    FROM    tbl_resposta
                    WHERE   pergunta    = {$key_pergunta}
                    AND     pesquisa    = $key_pesquisa
                    and     $cond_callcenter
                    and     $cond_os
                ORDER BY pergunta";
            $resRespostas = pg_query($con,$sql);

            if (pg_num_rows($resRespostas)>0) {
                // foreach (pg_fetch_all($resRespostas) as $keyRespostas) {
                for ($j=0; $j < pg_num_rows($resRespostas); $j++) {
                    $keyRespostas_pergunta = pg_fetch_result($resRespostas, $j, pergunta);
                    $keyRespostas_txt_resposta = pg_fetch_result($resRespostas, $j, txt_resposta);
                    $keyRespostas_tipo_resposta_item = pg_fetch_result($resRespostas, $j, tipo_resposta_item);

                    if (!empty($keyRespostas_tipo_resposta_item)) {

                        $respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'][] = $keyRespostas_tipo_resposta_item;

                    }else{
                        $respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'][] = $keyRespostas_txt_resposta;
                    }
                }
            }
        }
        //percorre a segunda vez para montar o formulário
        $resX = pg_execute($con, 'sql_principal', array());

        // foreach (pg_fetch_all($res) as $key) {
        for ($i=0; $i < pg_num_rows($resX) ; $i++) {
            $key_ordem = pg_fetch_result($resX, $i, ordem);
            $key_pergunta = pg_fetch_result($resX, $i, pergunta);
            $key_descricao = pg_fetch_result($resX, $i, descricao);
            $key_tipo_resposta = pg_fetch_result($resX, $i, tipo_resposta);
            $key_tipo_descricao = pg_fetch_result($resX, $i, tipo_descricao);
            $key_obrigatorio = pg_fetch_result($resX, $i, obrigatorio);
            $key_pesquisa_desc = pg_fetch_result($resX, $i, pesquisa_desc);
            $key_pesquisa = pg_fetch_result($resX, $i, pesquisa);

            $html_pesquisa .= "
                    <tr>
                        <td>
                            <input type='hidden' name='perg_".$i."' value='".$key_pergunta."' placeholder=''>
                            <input type='hidden' name='hidden_$i' value='".$key_tipo_descricao."' >
                            <input type='hidden' name='obrig_$i' value='".$key_obrigatorio."' >
                            <label > ".$key_ordem." </label>
                        </td>
                        <td ";
                        if($key_obrigatorio == 't') {
                            $html_pesquisa .= "style='color:#F00;'";
                        }
                        $html_pesquisa .=">
                            ".$key_descricao."
                        </td>";

            if (!empty($key_tipo_resposta)) {

                $sql = "SELECT  tbl_tipo_resposta_item.descricao            ,
                                tbl_tipo_resposta.label_inicio              ,
                                tbl_tipo_resposta.label_fim                 ,
                                tbl_tipo_resposta.label_intervalo           ,
                                tbl_tipo_resposta.tipo_descricao            ,
                                tbl_tipo_resposta_item.tipo_resposta_item
                        FROM    tbl_tipo_resposta
                   LEFT JOIN    tbl_tipo_resposta_item using(tipo_resposta)
                        WHERE   tbl_tipo_resposta.tipo_resposta = ".$key_tipo_resposta."
                        AND     tbl_tipo_resposta.fabrica       = $login_fabrica
                  ORDER BY      tbl_tipo_resposta_item.ordem ";

                $resx = pg_query($con,$sql);
                if (pg_num_rows($resx)>0) {
                    for ($x=0; $x < pg_num_rows($resx); $x++) {

                        if (!empty($respostasPergunta)) {
                            $disabled = 'disabled="DISABLED"';
                        }

                        $item_tipo_resposta_desc            = pg_fetch_result($resx, $x, 'descricao');
                        $item_tipo_resposta_tipo            = pg_fetch_result($resx, $x, 'tipo_descricao');
                        $item_tipo_resposta_label_inicio    = pg_fetch_result($resx, $x, 'label_inicio');
                        $item_tipo_resposta_label_fim       = pg_fetch_result($resx, $x, 'label_fim');
                        $item_tipo_resposta_label_intervalo = pg_fetch_result($resx, $x, 'label_intervalo');
                        $tipo_resposta_item_id              = pg_fetch_result($resx, $x, 'tipo_resposta_item');

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
                                if(strlen($item_tipo_resposta_desc) > 0){
                                    $html_pesquisa .= $item_tipo_resposta_desc;
                                    $value_resposta = $tipo_resposta_item_id;
                                    if (is_array($respostasPergunta) and !empty($respostasPergunta)) {
                                        if (in_array($tipo_resposta_item_id,$respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'])) {
                                            $checked_radio = "checked='CHECKED'";
                                        }
                                    }
                                    $html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt'.$key_pergunta.'"   value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';
                                }
                                break;
                            case 'text':
                                $item_tipo_resposta_desc = $key_txt_resposta;
                                $disabled_resposta = "disabled='DISABLED'";
                                $value_resposta = $item_tipo_resposta_desc;
                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'][0];
                                    }
                                }
                                $html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt'.$key_pergunta.'"   value="'.$value_resposta.'" '.$disabled.' />';

                                break;
                            case 'range':
                                $value_resposta = $item_tipo_resposta_desc;
                                for ($z=$item_tipo_resposta_label_inicio; $z <= $item_tipo_resposta_label_fim ; $z+=$item_tipo_resposta_label_intervalo) {
                                    if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                        if (in_array($z,$respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'])) {
                                            $checked_radio = "checked='CHECKED'";
                                        }else{
                                            $checked_radio = "";
                                        }
                                    }

                                    $html_pesquisa .= $z.' <input type="radio" name="perg_opt'.$key_pergunta.'" value="'.$z.'" '.$checked_radio.$disabled.' /> &nbsp; &nbsp;';
                                }

                                break;
                            case 'checkbox':
                                $html_pesquisa .= $item_tipo_resposta_desc;
                                $value_resposta = $tipo_resposta_item_id;
                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (in_array($tipo_resposta_item_id,$respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'])) {
                                        $checked_radio = "checked='CHECKED'";
                                    }
                                }
                                $html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt_checkbox_'.$key_pergunta.'_'.$i.'_'.$value_resposta.'"   value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';

                                break;
                            case 'textarea':
                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'][0];
                                    }
                                }
                                $html_pesquisa .= ' <textarea name="perg_opt'.$key_pergunta.'"  '.$disabled.' style="width:90%" >'.$value_resposta.'</textarea> ';

                                break;
                            case 'date':
                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$key_pesquisa][$key_pergunta]['respostas'][0];
                                    }
                                }
                                $width="";
                                $html_pesquisa .= ' <input  type="text"  style="width:'.$width.'" name="perg_opt'.$key_pergunta.'" id="data_inicial" class="frm date" value="'.$value_resposta.'" '.$disabled.'/>';

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
        }
    }

    if (is_array($respostasPergunta) and empty($respostasPergunta)) {
        $html_pesquisa .= '<tr><td colspan="100%" style="text-align:center">
            <div class="td_btn_gravar_pergunta" style="width: 100%; text-align: center;">
                <input type="hidden" name="qtde_perg" value="'.$i.'">
                <input type="button" value="Gravar" id="btn_grava_pesquisa" class="btn" rel="'.$pesquisa.'">';
        if($login_fabrica == 145){
            $html_pesquisa .= '
                <input type="button" value="Sair sem Responder" id="btn_sem_resposta" class="btn" rel="'.$pesquisa.'">
            ';
        }
        $html_pesquisa .= '
            </div>
        </td></tr>';

    }

    echo $html_pesquisa;
?>
        </tbody>
    </table><?php

    if ($login_fabrica != 51) {?>
        </form><?php
    }?>

</div>
