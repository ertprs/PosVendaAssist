<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
    include 'autentica_admin.php';
} else {
    include 'autentica_usuario.php';
}

$parametro = $_REQUEST["parametro"];
$valor     = trim($_REQUEST["valor"]);
if ($parametro == 'referencia') {
    $cond .= ($login_fabrica == 203) ? " AND tbl_produto.referencia ilike '%$valor%' " : " produto_codigo ilike '%$valor%' ";
}

if ($parametro == 'descricao') {
    $cond .= ($login_fabrica == 203) ? " AND tbl_produto.descricao ilike '%$valor%' " : " produto_descricao ilike '%$valor%' ";
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
                $.dataTableLupa();
                $('#resultados').on('click', '.produto-item', function() {
                    var info = JSON.parse($(this).attr('data-produto'));
                    if (typeof(info) == 'object') {
                        window.parent.retorna_produto(info);
                        window.parent.Shadowbox.close();
                    }
                });
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
            <form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >
                <div class="row-fluid">

                    <div class="span1"></div>
                    <div class="span4">
                        <select name="parametro"  >
                            <option value="referencia" <?=($parametro == "referencia") ? "SELECTED" : ""?> >Refêrencia</option>
                            <option value="descricao"  <?=($parametro == "descricao")  ? "SELECTED" : ""?> >Descrição</option>
                        </select>
                    </div>
                    <div class="span4">
                        <input type="text" name="valor" class="span12" value="<?=$valor?>" />
                    </div>
                    <div class="span2">
                        <button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();">Pesquisar</button>
                    </div>
                    <div class="span1"></div>
                </div>
            </form>
            <?php
                if ($login_fabrica == 203) {
                    $sql = "SELECT DISTINCT  tbl_produto.descricao AS produto_descricao, tbl_produto.referencia AS produto_codigo
                        FROM tbl_os
                        LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_os_produto.produto = tbl_os.produto
                        LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                        WHERE tbl_os.fabrica = 167
                        {$cond}";
                        //echo $sql;die;
                } else {
                $sql = "SELECT DISTINCT  produto_descricao, produto_codigo
                    FROM tbl_mondial_os_produto
                    WHERE
                    {$cond}";
                    //echo $sql;die;
                }
                $res = pg_query($con, $sql);
                $rows = pg_num_rows($res);

                if ($rows > 0) {
            ?>
            <div id="border_table">
                <table id="resultados" class="table table-striped table-bordered table-hover table-lupa" >
                    <thead>
                        <tr class='titulo_coluna'>
                            <th>Código</th>
                            <th>Nome</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        for ($i = 0 ; $i < $rows; $i++) {
                            $produto_descricao = pg_fetch_result($res, $i, 'produto_descricao');
                            $produto_codigo    = pg_fetch_result($res, $i, 'produto_codigo');
                            $r = array(
                                    "descricao"  => trim($produto_descricao),
                                    "referencia" => trim($produto_codigo)
                                );
                            echo "
                            <tr class='produto-item'  data-produto='".json_encode($r)."' >
                                <td class='cursor_lupa'>{$produto_codigo}</td>
                                <td class='cursor_lupa'>{$produto_descricao}</td>
                            </tr>";
                        }
                    ?>
                    </tbody>
                </table>
                <?php   
                    } else {
                        echo '
                            <div class="alert alert_shadobox">
                                <h4>Nenhum resultado encontrado</h4>
                            </div>';
                    }
                ?>
            </div>
        </div>
    </body>
</html>
