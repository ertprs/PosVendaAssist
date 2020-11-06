<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
    include 'autentica_admin.php';
} else {
    include 'autentica_usuario.php';
}
?>
<!DOCTYPE html />
<html>
    <head>
        <meta http-equiv=pragma content=no-cache>
        <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="bootstrap/js/bootstrap.js"></script>
        <script src="plugins/dataTable.js"></script>
        <script src="plugins/resize.js"></script>
        <script src="plugins/shadowbox_lupa/lupa.js"></script>

        <script>
            $(function () {
                $("#selecionar").click(function () {
                    if( $(this).is(':checked') ){
                        $("#botao").show();
                    }else{
                        $("#botao").hide();
                    }
                });

                $("#botao").click(function () {
                    window.parent.Shadowbox.close();
                });
            });
        </script>
    </head>

    <body>
        <div id="container_lupa" style="overflow-y:auto;">
            <div class="hero-unit" style="margin-bottom:0px; height: 204px;">
                <h3>Comunicado</h3>
                <p>Para inserir a peça no sistema, favor entrar em contato com a fábrica através do número 0800 770 8541</p>

                <label class="checkbox">
                    <input type="checkbox" id='selecionar' value='true'> Confirmar Leitura
                </label>
                <br/>
                <p style="display: none;" id='botao'>
                    <a class="btn btn-primary" id='fechar'>
                      Fechar
                    </a>
                </p>
            </div>
        </div>

    </body>
</html>
