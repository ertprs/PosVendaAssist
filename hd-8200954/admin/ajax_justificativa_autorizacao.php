<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";


$plugins = array(
    "jquery3",
    "bootstrap3"
);

include("plugin_loader.php");
?>
<style type="text/css">
    .titulo_tabela {
        background-color: #596d9b;
        font: bold 14px "Arial";
        color: #FFFFFF;
        text-align: center;
        padding: 5px;
    }
</style>
<script type="text/javascript">
    $(function(){
        $(".btn_shadow_gravar").click(function(){
            var os = $(this).data("os");
            var posicao = $(this).data("posicao");
            var txt_justificativa = $("#txt_justificativa").val();
            /*
            if (txt_justificativa == '') {

                alert('Digite uma justificativa.');
                return false;

            } else {*/
                window.parent.enviar_justificativa(os, posicao, txt_justificativa);
                window.parent.Shadowbox.close();
            //}
        });
    });
</script>
<body style="background: #ccc">
    
<div style="text-align: center;" class='titulo_tabela '>
    <label for='justificar' class='titulo_justificar '>Justifique a autorização:</label>
</div>
<div class="container">
    <div id='justificativa'>
        <div style="text-align: center;">
            <br><br>
            <textarea name='txt_justificativa' id='txt_justificativa' class='txt_justificativa' rows='10' cols='60' ></textarea>
        </div>
        <div style="text-align: center;">
            <br><br><br>
            <input type='button' name='btn_shadow_gravar' class='btn btn-success btn_shadow_gravar' value='Gravar' data-os='<?php echo $os;?>'  data-posicao='<?php echo $posicao;?>'>&nbsp;&nbsp;&nbsp;
            <input type='button' name='btn_shadow_cancelar' class='btn btn-danger btn_shadow_cancelar' onClick='window.parent.Shadowbox.close();;' value='Cancelar'>
        </div>
    </div>
</div>



</body>
