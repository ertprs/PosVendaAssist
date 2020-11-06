<?php

$sql_makita = "SELECT comunicado, tdocs_id, JSON_FIELD('filename', obs) AS arquivo
                 FROM tbl_comunicado
            LEFT JOIN tbl_tdocs ON tbl_tdocs.fabrica = tbl_comunicado.fabrica
                               AND comunicado        = referencia_id
                WHERE tbl_comunicado.fabrica = 42
                  AND LOWER(tipo) = 'faq makita'
                  AND tbl_tdocs.referencia = 'comunicados'
                  AND ativo      IS TRUE;";
$res_makita = pg_query($con, $sql_makita);

#https://api2.telecontrol.com.br/tdocs/document/id/f1be0a34917cd8955061cced69419207b74ed85e7f4282de68e39cb21c53531e/file/Perguntas_Automaticas_Assistencia_Tecnica_2017.pdf

if (pg_num_rows($res_makita)) {
    /* CONFIGURAÇÃO */
    $comunicado_makita = pg_fetch_result($res_makita, 0, "comunicado");
    $tdocs_id          = pg_fetch_result($res_makita, 0, 'tdocs_id');
    $arquivo           = pg_fetch_result($res_makita, 0, 'arquivo');

    if (strpos($_SERVER['PHP_SELF'], '/helpdesk_cadastrar.php')) {
        $img_makita          = "imagens/faq_makita.jpg";
        $class_img_makita    = "img-makita";
        $class_titulo_makita = "titulo-img-makita";
        $faq_makita          = "<img class='{$class_img_makita}' src='{$img_makita}' onclick='javascript: Visualiza_link_makita({$comunicado_makita})'>";

        echo $faq_makita;
    }else{
        #$faq_makita = "<a style='cursor: pointer;' target='_blank' title='FAQ-Makita' onclick='javascript: Visualiza_link_makita({$comunicado_makita})'><img class='{$class_img_makita}' src='{$img_makita}' ><span class='{$class_titulo_makita}'>FAQ Makita</span></a>";

        //.img-makita{ float:right; position: relative; margin-right: 25%; margin-top: -3%; z-index: 15;}
        //.titulo-img-makita{ float: right; position: relative; margin-top: 3%; margin-left:25%; color: #ffffff; font-size: 14px;font-weight: bold; z-index: 15;}
        $faq_makita   = "<span class='fa-stack'><img src='imagens/faq_branco.png' height='24'></span> FAQ Makita";
        $link_arquivo = "https://api2.telecontrol.com.br/tdocs/document/id/$tdocs_id/file/$arquivo";
    }
    #echo $faq_makita;
}
?>
<!--
<script type="text/javascript">
function Visualiza_link_makita(comunicado){
    $.get("verifica_s3_comunicado.php", { comunicado: comunicado, fabrica:"42", tipo: "co"}, function (url) {
        if (url.length > 0) {
            if (typeof(Shadowbox) !== "undefined") {
                Shadowbox.init();
                if(url.search(/.(pdf|xlsx?)/g) != -1){
                    window.open(url, "_blank");
                }else{
                    Shadowbox.open({
                        player  : "html",
                        content : "<div style='overflow-y: scroll; width: 800px; height: 600px'><img src='"+url+"' style='width: 100%;'></div>",
                        height: 600,
                        width: 800
                    });
                }
            }else{
                window.open(url, "_blank");
            }
        } else {
            alert("Arquivo não encontrado!");
        }

    });
}
</script>

-->
