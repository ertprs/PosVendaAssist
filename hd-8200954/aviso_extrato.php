<?php

    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include "autentica_usuario.php";

    $fabrica = $_GET["fabrica"];
    $url     = $_GET["url"];

    $nome_fabrica = ($fabrica == 11) ? "Aulik" : "Pacific"

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Atenção!!!</title>

        <link href="admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
        <script src="js/jquery-1.8.3.min.js"></script>

        <style>
            .body{
                margin: 20px;
            }
        </style>

        <script>
            window.parent.retorno_pagina_redireciona(<?php echo $url; ?>)
        </script>

    </head>
    <body>

        <div class="body">

            <h3 class="tac">Atenção!!!</h3>

            <form method="post" action="verifica_codigo_interno.php">

                <input type="hidden" name="verifica_codigo" value="sim">

                <div class='row-fluid'>
                    <div class="span12 tac">
                        O extrato que vai detalhado pertence á fabrica <strong><?php echo $nome_fabrica; ?></strong>, favor se atentar aos dados de faturamento no cabeçalho do extrtao.
                    </div>
                </div>

                <div class='row-fluid'>
                    <div class="span2"></div>
                    <div class="span4">
                        <div class='control-group'>
                            <button type="button" class="btn btn-block btn-primary" onclick="window.parent.retorno_pagina_redireciona('<?php echo $url; ?>')"> OK </button>
                        </div>
                    </div>
                    <div class='span4'>
                        <div class='control-group'>
                            <button type="button" class="btn btn-block btn-default" onclick="window.parent.Shadowbox.close();"> Permanecer na página! </button>
                        </div>
                    </div>
                </div>

            </form>

        </div>

    </body>
</html>