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

if ($parametro == 'nome') {
    $cond .= ($login_fabrica == 203) ? " AND tbl_os.consumidor_nome ilike '%$valor%' " : " nome_razao ilike '%$valor%' ";
}

if ($parametro == 'cpf') {
    $cond .= ($login_fabrica == 203) ? " AND tbl_os.consumidor_cpf ilike '%$valor%' " : " consumidor_cpf_cnpj ilike '%$valor%' ";
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
                            <option value="cpf"  <?=($parametro == "cpf")  ? "SELECTED" : ""?> >CPF</option>
                            <option value="nome"  <?=($parametro == "nome")  ? "SELECTED" : ""?> >Nome</option>
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
                    $sql = "SELECT DISTINCT  tbl_os.consumidor_nome AS nome_razao,tbl_os.consumidor_cpf AS consumidor_cpf_cnpj
                        FROM tbl_os
                        WHERE tbl_os.fabrica = 167
                        {$cond}";
                        //echo $sql;die;
                } else {
                $sql = "SELECT DISTINCT  nome_razao,consumidor_cpf_cnpj
                    FROM tbl_mondial_os
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
                            <th>Nome</th>
                            <th>CPF</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        for ($i = 0 ; $i < $rows; $i++) {
                            $nome_razao          = pg_fetch_result($res, $i, 'nome_razao');
                            $consumidor_cpf_cnpj = pg_fetch_result($res, $i, 'consumidor_cpf_cnpj');
                            $nome_razao = ($nome_razao == 'NULL') ? '' : utf8_decode($nome_razao);
                            $consumidor_cpf_cnpj = ($consumidor_cpf_cnpj == 'NULL') ? '' : $consumidor_cpf_cnpj;
                            $r = array(
                                "nome" => utf8_encode(trim($nome_razao)),
                                "cpf" => utf8_encode(trim($consumidor_cpf_cnpj)),
                            );
                            echo "
                            <tr onclick='window.parent.retorna_cliente(".json_encode($r)."); window.parent.Shadowbox.close();' >
                                <td class='cursor_lupa'>{$nome_razao}</td>
                                <td class='cursor_lupa'>{$consumidor_cpf_cnpj}</td>
                            </tr>";
                        }
                    ?>
                    </tbody>
                </table>
                <?php   
                    } else {
                        echo '<div class="alert alert_shadobox"><h4>Nenhum resultado encontrado</h4></div>';
                    }
                ?>
            </div>
        </div>
    </body>
</html>

