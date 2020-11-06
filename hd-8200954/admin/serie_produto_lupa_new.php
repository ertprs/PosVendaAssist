<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];

$cookie_login = get_cookie_login($token_cookie);

if (strlen($cookie_login["cook_login_posto"]) > 0) {
    include 'autentica_usuario.php';
} else {
    include 'autentica_admin.php';
}

$parametro = $_REQUEST["parametro"];
$valor     = trim($_REQUEST["valor"]);

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
                $.dataTableLupa();
            });
        </script>
    </head>

    <body>
        <div id="container_lupa" style="overflow-y:auto;">
            <div id="topo">
                <img class="espaco" src="imagens/logo_new_telecontrol.png">
                <img class="lupa_img pull-right" src="imagens/lupa_new.png">
            </div>
            <br /><hr />
            <?

            if (strlen($valor) >= 2) {
                switch ($parametro) {

                    case 'serie_produto':
                        $whereAdc = "AND tbl_numero_serie.serie ILIKE '%$valor%'";
                        break;

                }
                if (isset($whereAdc)) {

                    $sql = "SELECT
                                tbl_numero_serie.serie,
                                tbl_produto.referencia,
                                tbl_produto.descricao
                            FROM tbl_numero_serie
                            JOIN tbl_produto USING(produto)
                            WHERE tbl_numero_serie.fabrica = {$login_fabrica}
                            {$whereAdc}";
                    $res = pg_query($con, $sql);

                    $rows = pg_num_rows($res);
                    if ($rows > 0) {

                    ?>
                    <div id="border_table">
                        <table class="table table-striped table-bordered table-hover table-lupa" >
                            <thead>
                                <tr class='titulo_coluna'>
                                    <th>Série</th>
                                    <th>Referência</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                for ($i = 0 ; $i < $rows; $i++) {
                                    $r = array(
                                        "serie"  => pg_fetch_result($res, $i, 'serie'),
                                        "referencia"  => pg_fetch_result($res, $i, 'referencia'),
                                        "descricao"  => pg_fetch_result($res, $i, 'descricao'),
                                    );

                                    echo "<tr onclick='window.parent.retorna_serie_produto(".json_encode($r)."); window.parent.Shadowbox.close();' >";
                                        echo "<td class='cursor_lupa'>".$r['serie']."</td>";
                                        echo "<td class='cursor_lupa'>".$r['referencia']."</td>";
                                        echo "<td class='cursor_lupa'>".$r['descricao']."</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        </div>
                    <?php
                    } else {
                        echo '
                    <div class="alert alert_shadobox">
                        <h4>Nenhum resultado encontrado</h4>
                    </div>';
                    }
                }
            } else {
                echo '
                    <div class="alert alert_shadobox">
                        <h4>Informe toda ou parte da informação para pesquisar!</h4>
                    </div>';
            }
            ?>
    </div>
    </body>
</html>
