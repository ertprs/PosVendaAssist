<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$parametro = $_REQUEST["parametro"];
$valor     = utf8_decode(trim($_REQUEST["valor"]));

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
                    <select name="parametro" >
                        <option value="referencia" <?=($parametro == "cnpj") ? "SELECTED" : ""?> >CNPJ</option>
                        <option value="descricao" <?=($parametro == "nome") ? "SELECTED" : ""?> >NOME</option>
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

            if (strlen($valor) >= 3) {
                switch ($parametro) {
                    case 'cnpj':
                        $valor = str_replace(array(".", ",", "-", "/", " "), "", $valor);
                        $whereAdc = " tbl_cliente_admin.cnpj = '".preg_replace("/[\.\-\/]/", "", $valor)."' ";
                        break;

                    case 'nome':
                        $whereAdc = " UPPER(tbl_cliente_admin.nome) ILIKE UPPER('%{$valor}%') ";
                        break;
                }
                if (isset($whereAdc)) {

                    $sql = "SELECT tbl_cliente_admin.cliente_admin,
                                tbl_cliente_admin.codigo,
                                tbl_cliente_admin.nome,
                                tbl_cliente_admin.cnpj,
                                tbl_cliente_admin.endereco,
                                tbl_cliente_admin.numero,
                                tbl_cliente_admin.complemento,
                                tbl_cliente_admin.bairro,
                                tbl_cliente_admin.cep,
                                tbl_cliente_admin.cidade,
                                tbl_cliente_admin.estado,
                                tbl_cliente_admin.email,
                                tbl_cliente_admin.fone,
                                tbl_cliente_admin.celular,
                                tbl_cliente_admin.contato,
                                tbl_cliente_admin.ie,
                                tbl_cliente_admin.marca,
                                tbl_cliente_admin.abre_os_admin,
                                tbl_cliente_admin.codigo_representante
                            FROM tbl_cliente_admin
                            WHERE {$whereAdc}
                            AND fabrica = $login_fabrica
                            ORDER BY tbl_cliente_admin.nome ";
                    $res = pg_query($con, $sql);

                    $rows = pg_num_rows($res);
                    if ($rows > 0) {

                    ?>
                    <div id="border_table">
                        <table class="table table-striped table-bordered table-hover table-lupa" >
                            <thead>
                                <tr class='titulo_coluna'>
                                    <th>Cliente nome</th>
                                    <th>CNPJ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                for ($i = 0 ; $i <$rows; $i++) {
                                    $cliente_admin          = trim(pg_fetch_result($res,$i,cliente_admin));
                                    $codigo                 = trim(pg_fetch_result($res,$i,codigo));
                                    $nome                   = trim(pg_fetch_result($res,$i,nome));
                                    $cnpj                   = trim(pg_fetch_result($res,$i,cnpj));
                                    $endereco               = trim(pg_fetch_result($res,$i,endereco));
                                    $numero                 = trim(pg_fetch_result($res,$i,numero));
                                    $complemento            = trim(pg_fetch_result($res,$i,complemento));
                                    $bairro                 = trim(pg_fetch_result($res,$i,bairro));
                                    $cep                    = trim(pg_fetch_result($res,$i,cep));
                                    $cidade                 = trim(pg_fetch_result($res,$i,cidade));
                                    $estado                 = trim(pg_fetch_result($res,$i,estado));
                                    $email                  = trim(pg_fetch_result($res,$i,email));
                                    $fone                   = trim(pg_fetch_result($res,$i,fone));
                                    $celular                = trim(pg_fetch_result($res,$i,celular));
                                    $contato                = trim(pg_fetch_result($res,$i,contato));
                                    $ie                     = trim(pg_fetch_result($res,$i,ie));
                                    $marca                  = trim(pg_fetch_result($res,$i,marca));
                                    $abre_os_admin          = trim(pg_fetch_result($res,$i,abre_os_admin));
                                    $codigo_representante   = trim(pg_fetch_result($res,$i,codigo_representante));
                                    $r = array(
                                        "cliente_admin"         => utf8_encode($cliente_admin)  ,
                                        "cnpj"                  => utf8_encode($cnpj)  ,
                                        "codigo"                => utf8_encode($codigo)  ,
                                        "nome"                  => utf8_encode($nome)  ,
                                        "endereco"              => utf8_encode($endereco)  ,
                                        "numero"                => utf8_encode($numero)  ,
                                        "complemento"           => utf8_encode($complemento),
                                        "bairro"                => utf8_encode($bairro)  ,
                                        "cep"                   => utf8_encode($cep)  ,
                                        "cidade"                => utf8_encode($cidade)  ,
                                        "estado"                => utf8_encode($estado)  ,
                                        "cidade"                => utf8_encode($cidade)  ,
                                        "estado"                => utf8_encode($estado),
                                        "email"                 => utf8_encode($email)  ,
                                        "fone"                  => utf8_encode($fone)  ,
                                        "celular"               => utf8_encode($celular)  ,
                                        "contato"               => utf8_encode($contato)  ,
                                        "ie"                    => utf8_encode($ie),
                                        "marca"                 => utf8_encode($marca),
                                        "abre_os_admin"         => utf8_encode($abre_os_admin),
                                        "codigo_representante"  => utf8_encode($codigo_representante)
                                    );

                                    echo "<tr onclick='window.parent.retorna_cliente(".json_encode($r)."); window.parent.Shadowbox.close();' >";
                                        echo "<td class='cursor_lupa'>".strtoupper($nome)."</td>";
                                        echo "<td class='cursor_lupa'>".utf8_encode($cnpj)."</td>";
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
