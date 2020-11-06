<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
    include 'autentica_admin.php';
    $areaAdmin = true;
} else {
    include 'autentica_usuario.php';
    $areaAdmin = false;
}

$contexto     = $_REQUEST["contexto"];
$tempUniqueId = $_REQUEST["tempUniqueId"];

if ($contexto == "extrato") {
    $tituloTabela = "Notas Fiscais de Serviço";
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
        <script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
        <link type="text/css" href="plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">
        <style>
            #div_anexos {
                height: 100%;
            }
        </style>
    </head>
    <body>
        <?php
            $boxUploader = array(
                "div_id" => "div_anexos",
                "prepend" => $anexo_prepend,
                "context" => $contexto,
                "unique_id" => $tempUniqueId,
                "hash_temp" => $anexoNoHash,
                "bootstrap" => true,
                "hidden_button" => true,
                "titulo_tabela" => $tituloTabela
            );
            include "box_uploader.php";
        ?>
    </body>
</html>
