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

if ($parametro == 'razao') {
    $cond .= " cliente_razao ILIKE '%$valor%' ";
}

if ($parametro == 'codigo') {
    $cond .= " cliente_codigo ILIKE '%$valor%' ";
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
                            <option value="codigo" <?=($parametro == "codigo")  ? "SELECTED" : ""?> >Código</option>
                            <option value="razao" <?=($parametro == "razao")  ? "SELECTED" : ""?> >Razão Social</option>
                        </select>
                    </div>
                    <div class="span4">
                        <input type="text" name="valor" class="span12" value="<?= $valor; ?>" />
                    </div>
                    <div class="span2">
                        <button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();">Pesquisar</button>
                    </div>
                    <div class="span1"></div>
                </div>
            </form>
            
            <? $sql = "
                SELECT DISTINCT
                    cliente_codigo,
                    cliente_razao
                FROM tbl_os_tecvoz
                WHERE {$cond};
            ";

            $res = pg_query($con, $sql);
            $rows = pg_num_rows($res); ?>

            <div id="border_table">
                <? if ($rows > 0) { ?>
                    <table id="resultados" class="table table-striped table-bordered table-hover table-lupa" >
                        <thead>
                            <tr class='titulo_coluna'>
                                <th>Código</th>
                                <th>Razão Social</th>
                            </tr>
                        </thead>
                        <tbody>
                            <? for ($i = 0 ; $i < $rows; $i++) {

                                $cliente_codigo     = pg_fetch_result($res, $i, cliente_codigo);
                                $cliente_razao      = pg_fetch_result($res, $i, cliente_razao);
                                
                                $cliente_codigo = ($cliente_codigo == 'NULL') ? '' : utf8_decode($cliente_codigo);
                                $clinte_razao = ($cliente_razao == 'NULL') ? '' : utf8_decode($cliente_razao);
                                
                                $r = array(
                                    "cliente_codigo" => utf8_encode(trim($cliente_codigo)),
                                    "cliente_razao" => utf8_encode(trim($cliente_razao)),
                                ); ?>
                                
                                <tr onclick='window.parent.retorna_cliente(<?= json_encode($r); ?>); window.parent.Shadowbox.close();' >
                                    <td class='cursor_lupa'><?= $cliente_codigo; ?></td>
                                    <td class='cursor_lupa'><?= $cliente_razao; ?></td>
                                </tr>

                            <? } ?>
                        </tbody>
                    </table>
                <? } else { ?>
                    <div class="alert alert_shadobox">
                        <h4>Nenhum resultado encontrado</h4>
                    </div>
                <? } ?>
            </div>
        </div>
    </body>
</html>

