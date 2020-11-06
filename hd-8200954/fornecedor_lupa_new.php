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

function caractere_especial_maisculo($texto, $maisculo = true){
    $caractere_especial = array(
        "ã","á","à","â",
        "é","è","ê",
        "í","ì",
        "õ","ó","ò","ô",
        "ú","ù"
    );
    $caractere_especial2 = array(
        "Ã","Á","À","Â",
        "É","È","Ê",
        "Í","Ì",
        "Õ","Ó","Ò","Ô",
        "Ú","Ù"
    );
    if ($maisculo) {
        return str_replace($caractere_especial, $caractere_especial2, $texto);
    }
    return str_replace($caractere_especial2, $caractere_especial, $texto);
}
$valor_aux = strtolower(caractere_especial_maisculo($valor, false));

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
                        <option value="cpf"    <?=($parametro == "cpf")    ? "SELECTED" : ""?> >CPF/CNPJ</option>
                        <option value="codigo" <?=($parametro == "codigo") ? "SELECTED" : ""?> >CÓDIGO</option>
                        <option value="nome"   <?=($parametro == "nome")   ? "SELECTED" : ""?> >RAZÃO SOCIAL</option>
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

            if (strlen($valor) >= 2) {
                switch ($parametro) {
                    case 'cpf':
                    case 'cnpj':
                        $valor = str_replace(array(".", ",", "-", "/", " "), "", $valor);
                        $whereAdc = " tbl_fornecedor.cnpj = '".preg_replace("/[\.\-\/]/", "", $valor)."' ";
                        break;

                    case 'nome':
                        $whereAdc = " UPPER(tbl_fornecedor.nome) ILIKE UPPER('%{$valor_aux}%') ";
                        break;

                    case 'codigo':
                        $whereAdc = " tbl_fornecedor.fornecedor = $valor";
                        break;
                }
                if (isset($whereAdc)) {

                    $sql = "SELECT
                                tbl_fornecedor.fornecedor,
                                tbl_fornecedor.nome,
                                tbl_fornecedor.endereco,
                                tbl_fornecedor.numero,
                                tbl_fornecedor.bairro,
                                tbl_fornecedor.complemento,
                                tbl_cidade.nome AS cidade,
                                tbl_fornecedor.cnpj,
                                tbl_fornecedor.ie,
                                tbl_fornecedor.cep,
                                tbl_fornecedor.fone1,
                                tbl_fornecedor.fone2,
                                tbl_fornecedor.fax,
                                tbl_fornecedor.email,
                                tbl_fornecedor.site,
                                tbl_fornecedor_fabrica.contato,
                                tbl_cidade.estado as uf
                            FROM tbl_fornecedor
                                JOIN tbl_fornecedor_fabrica ON(tbl_fornecedor_fabrica.fornecedor = tbl_fornecedor.fornecedor AND tbl_fornecedor_fabrica.fabrica = {$login_fabrica})
                                JOIN tbl_cidade USING(cidade)
                                
                            WHERE {$whereAdc}";
                    $res = pg_query($con, $sql);

                    $rows = pg_num_rows($res);
                    if ($rows > 0) {

                    ?>
                    <div id="border_table">
                        <table class="table table-striped table-bordered table-hover table-lupa" >
                            <thead>
                                <tr class='titulo_coluna'>
                                    <th>RAZÃO SOCIAL</th>
                                    <th>CPF/CNPJ</th>
                                    <th>CIDADE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                for ($i = 0 ; $i < $rows; $i++) {
                                    $r = array(
                                        "fornecedor"  => pg_fetch_result($res, $i, 'fornecedor'),
                                        "nome"        => str_replace("'", " ", utf8_encode(trim(pg_fetch_result($res, $i, 'nome')))),
                                        "endereco"    => str_replace("'", " ", utf8_encode(trim(pg_fetch_result($res, $i, 'endereco')))),
                                        "numero"      => utf8_encode(trim(pg_fetch_result($res, $i, 'numero'))),
                                        "bairro"      => str_replace("'", " ", utf8_encode(trim(pg_fetch_result($res, $i, 'bairro')))),
                                        "complemento" => str_replace("'", " ", utf8_encode(trim(pg_fetch_result($res, $i, 'complemento')))),
                                        "cidade"      => utf8_encode(trim(pg_fetch_result($res, $i, 'cidade'))),
                                        "cnpj"        => trim(pg_fetch_result($res, $i, 'cnpj')),
                                        "ie"          => trim(pg_fetch_result($res, $i, 'ie')),
                                        "cep"         => trim(pg_fetch_result($res, $i, 'cep')),
                                        "bairro"      => str_replace("'", " ", utf8_encode(trim(pg_fetch_result($res, $i, 'bairro')))),
                                        "fone1"       => utf8_encode(trim(pg_fetch_result($res, $i, 'fone1'))),
                                        "fone2"       => utf8_encode(trim(pg_fetch_result($res, $i, 'fone2'))),
                                        "fax"         => utf8_encode(trim(pg_fetch_result($res, $i, 'fax'))),
                                        "email"       => utf8_encode(trim(pg_fetch_result($res, $i, 'email'))),
                                        "site"        => utf8_encode(trim(pg_fetch_result($res, $i, 'site'))),
                                        "contato"     => utf8_encode(trim(pg_fetch_result($res, $i, 'contato'))),
                                        "estado"      => utf8_encode(trim(pg_fetch_result($res, $i, 'estado'))),
                                        "uf"      => utf8_encode(trim(pg_fetch_result($res, $i, 'uf')))
                                    );

                                    echo "<tr onclick='window.parent.retorna_fornecedor(".json_encode($r)."); window.parent.Shadowbox.close();' >";
                                        echo "<td class='cursor_lupa'>".strtoupper(caractere_especial_maisculo(utf8_decode($r['nome'])))."</td>";
                                        echo "<td class='cursor_lupa'>".$r['cnpj']."</td>";
                                        echo "<td class='cursor_lupa'>".$r['cidade']."</td>";
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
