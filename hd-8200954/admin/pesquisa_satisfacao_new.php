<?php

/**
* @author Brayan L. Rastelli
* @description Pesquisa de satisfacao - HD 408341
* Precisa ter jQuery na página em que irá incluir, NAO inclua nesse arquivo.
* @todo Comentar includes de conexao ao por em produção
*/
$areaAdmin = preg_match('/\/admin\//',$_SERVER['HTTP_REFERER']) > 0 ? true : false;

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
if ($areaAdmin === true) {
    include_once __DIR__.'/autentica_admin.php';
} else {
    include_once __DIR__.'/../autentica_usuario.php';
}

include_once 'pesquisa_satisfacao_config.php';

$callcenter = (!empty($_GET['callcenter'])) ? $_GET['callcenter'] : "null";
$os = (!empty($_GET['os'])) ? $_GET['os'] : "null";
$local_pesquisa = (!empty($_GET['local'])) ? $_GET['local'] : 'callcenter';

$pesquisa_selecionada = $_GET['pesquisa_selecionada'];

if (isset($_POST['enviar'])) {// AJAX, nao colocar output antes disso.

    $res = pg_query($con, 'BEGIN;');

    include 'pesquisa_satisfacao_post.php';

    if (empty($msg_erro)) {
        $res = pg_query($con, 'COMMIT;');
        echo 't';

    } else {
        $res = pg_query($con, 'ROLLBACK;');
        echo 'f|';

    }

    exit;

}

include 'pesquisa_satisfacao_form.php';?>
<script type="text/javascript">
    var respondido = false;
<?php

    if (in_array($login_fabrica, array(1,30,35,85,94,129,138,145,161))) {
?>

        $("#btn_grava_pesquisa").click(function(e) {

            var curDateTime = new Date();
            var relBtn = $(this).attr('rel');
            var hdChamado = <?=$callcenter?>;
            var login_fabrica = <?=$login_fabrica?>;
            var login_admin = <?=(!empty($login_admin)) ? $login_admin : 0?>;
            var login_posto = <?=(!empty($login_posto)) ? $login_posto : 0?>;
            var os = <?=$os?>;

            $.ajax({
                type: "POST",
                url: "pesquisa_satisfacao_post.php",
                data: {
                    ajax:true,
                    gravaPerguntas:true,
                    pesquisa:relBtn,
                    hdChamado:hdChamado,
                    login_fabrica:login_fabrica,
                    login_admin:login_admin,
                    login_posto:login_posto,
                    os:os,
                    input:$('#pesquisa_satisfacao').find('input').serialize(),
                    textarea:$('#pesquisa_satisfacao').find('textarea').serialize()
                }
            })
            .done(function(http) {
                //results = http.responseText;
                results = http.split('|');
                if (results[0] == 1){

                    $('h4.errorPergunta').html(results[1]);
                    $('div.alert-error').show();
                    $('.td_btn_gravar_pergunta').show();
                    $('.divTranspBlock').hide();
                    $('#btn_grava_pesquisa').show();
                }else{

                    $('h4.successPergunta').html(results[1]);
                    $('div.alert-success').show();

                    $('#pesquisa_satisfacao').find('input').attr('disabled',true);
                    $('#pesquisa_satisfacao').find('textarea').attr('disabled',true);
                    $('.td_btn_gravar_pergunta').hide();
                    $('#seleciona_pesquisa', window.parent.document).hide();
                    if (typeof window.parent.Shadowbox != "undefined") {
                        window.parent.Shadowbox.close();
                    }
                }

                var height = $("body").outerHeight();
                window.parent.SetIFrameHeight(height);
            });
        });
    <?php
    }

    if($login_fabrica == 145){ ?>

        $("#btn_sem_resposta").click(function(e){
            var relBtn          = $(this).attr('rel');
            var hdChamado       = <?=$callcenter?>;
            var login_fabrica   = <?=$login_fabrica?>;
            var os              = <?=$os?>;

            if(confirm("Deseja marcar como SEM RESPOSTA a pesquisa?")){
                $.ajax({
                    type:"POST",
                    url: "pesquisa_satisfacao_post.php",
                    data:{
                        ajax:true,
                        sem_resposta:true,
                        pesquisa:relBtn,
                        hdChamado:hdChamado,
                        login_fabrica:login_fabrica,
                        os:os
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
                    }else{
                        $('#pesquisa_satisfacao').find('input').attr('disabled',true);
                        $('#pesquisa_satisfacao').find('textarea').attr('disabled',true);
                        $('.td_btn_gravar_pergunta').hide();
                        $('#seleciona_pesquisa', window.parent.document).hide();
                        if (typeof window.parent.Shadowbox != "undefined") {
                            window.parent.Shadowbox.close();
                        }
                    }

                    var height = $("body").outerHeight();
                    window.parent.SetIFrameHeight(height);
                });
            }
        });
    <?php } ?>

</script><?php

