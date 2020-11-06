<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
    include 'autentica_admin.php';
} else {
    include 'autentica_usuario.php';
}

$contador_ver = "0";

$parametro = trim(utf8_decode($_REQUEST["parametro"]));
$valor     = trim(utf8_decode($_REQUEST["valor"]));

$laudo      = trim(utf8_decode($_REQUEST["devolucao"]));

$usa_rev_fabrica = in_array($login_fabrica, array(3,15,24));
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
            <div class="row-fluid">
                <form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >

                    <div class="span1"></div>
                    <div class="span4">
                        <input type="hidden" name="posicao" class="span12" value='<?=$posicao?>' />
                        <input type="hidden" name="parametro" value='<?=$parametro?>' />
                        CPF/CNPJ:
                        <select name="parametro" class='span6'>
                            <option value="cnpj" <?=($parametro == "cpf_cnpj") ? "SELECTED" : ""?> >CPF / CNPJ</option>
                            <option value="nome_consumidor" <?=($parametro == "nome_consumidor") ? "SELECTED" : ""?> >Nome Consumidor</option>
                        </select>
                    </div>
                    <div class="span4">
                        <input type="text" name="valor" class="span12" value="<?=$valor?>" />
                    </div>
                    <div class="span2">
                        <button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();">Pesquisar</button>
                    </div>
                    <div class="span1"></div>
                </form>
            </div>
            <?
            $msg_confirma = "0";
            if (strlen($valor) >= 3) {
                switch ($parametro) {
                    case 'cpf_cnpj':
                        $valor = str_replace(array(".", ",", "-", "/"), "", $valor);
                        $where_parametro = "AND cpf_cliente LIKE '{$valor}'";
                        break;
                    
                    case 'nome_consumidor':
                        $where_parametro = "AND nome_cliente ILIKE '{$valor}'";
                        break;

                    default:
                        $where_parametro = "";
                        break;

                }

                $sql = "SELECT DISTINCT ON (cpf_cliente) nome_cliente, cpf_cliente, fone_cliente, celular_cliente
                        FROM tbl_os_laudo 
                        WHERE fabrica = $login_fabrica 
                        $where_parametro ";

                $res = pg_query($con, $sql);
                $rows = pg_num_rows($res);

                if ($rows > 0) { ?>
                    <div id="border_table">
                        <table class="table table-striped table-bordered table-hover table-lupa" >
                            <thead>
                                <tr class='titulo_coluna'>
                                    <th>CPF/CNPJ</th>
                                    <th>Nome</th>
                                    <th>Telefone</th>
                                    <th>Celular</th>
                                </tr>
                            </thead>
                            <tbody>
                                <? for ($i = 0; $i < $rows; $i++) {
                                
                                    $resultado[$i]["nome_cliente"] = utf8_encode(pg_fetch_result($res, $i, nome_cliente));
                                    $resultado[$i]["cpf_cliente"] = pg_fetch_result($res, $i, cpf_cliente);
                                    $resultado[$i]["fone_cliente"] = pg_fetch_result($res, $i, fone_cliente);
                                    $resultado[$i]["celular_cliente"] = pg_fetch_result($res, $i, celular_cliente);

                                    $r = $resultado[$i]; ?>

                                    <tr onclick='window.parent.retorna_laudo_os(<?= json_encode($r); ?>); window.parent.Shadowbox.close();'>
                                        <td class='cursor_lupa tac'><?= $resultado[$i]['cpf_cliente']; ?></td>
                                        <td class='cursor_lupa tac'><?= $resultado[$i]['nome_cliente']; ?></td>
                                        <td class='cursor_lupa tac'><?= $resultado[$i]['fone_cliente']; ?></td>
                                        <td class='cursor_lupa tac'><?= $resultado[$i]['celular_cliente']; ?></td>
                                    </tr>
                                <? } ?>
                            </tbody>
                        </table>
                    </div>
                <? } else { ?>
                    <div class="alert alert_shadobox">
                            <h4>Nenhum resultado encontrado</h4>
                    </div>
                <? }
            } else { ?>
                <div class="alert alert_shadobox">
                    <h4>Informe toda ou parte da informação para pesquisar!</h4>
                </div>
            <? } ?>
        </div>
    </body>
</html>

