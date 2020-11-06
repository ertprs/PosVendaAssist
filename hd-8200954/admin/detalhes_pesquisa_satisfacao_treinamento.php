<?php
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include 'includes/funcoes.php';
    $admin_privilegios="info_tecnica";
    include 'autentica_admin.php';
    $layout_menu = "tecnica";
    $title       = "DETALHES DE PESQUISA DE SATISFAÇÃO DO TREINAMENTO";
?>

<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<?php
    $plugins = array(
        "autocomplete",
        "datepicker",
        "shadowbox",
        "mask",
        "ajaxform",
        "dataTable"
    );

    include "plugin_loader.php";
    include "javascript_pesquisas.php";
?>

<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/bootstrap.css" />
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/extra.css" />
<link media="screen" type="text/css" rel="stylesheet" href="css/tc_css.css" />
<link media="screen" type="text/css" rel="stylesheet" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/ajuste.css" />
<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">

<style>
.gold {
    color: #daa520;
}
.gray {
    color: #808080;
}
.fa-calendar {
    cursor: pointer;
}
.table-respostas {
    /*display: none;*/
    margin-top: 20px;
}
.table-respostas td {
    color: #000;
    font-weight: normal;
}
.table-respostas td.text-info {
    color: #3a87ad;
    font-weight: bold;
    font-size: 14px;
}
.tbody-filtro {
    display: none;
}    
</style>

<!-- MOSTRA RESPOSTAS DE UM POSTO -->
<?php
if (isset($_GET['ajax']) && isset($_GET['acao']) && $_GET['ajax'] == 'sim' && $_GET['acao'] == 'ver_resposta') {
    $treinamento = addslashes($_GET['treinamento']);
    $posto       = addslashes($_GET['posto']);
    $tecnico     = addslashes($_GET['tecnico']);

    $sql_busca   = "SELECT DISTINCT
                         p.treinamento,
                         tr.titulo,
                         pst.estado,
                         p.descricao,
                         p.texto_ajuda,
                         r.txt_resposta,
                         pst.nome,
                         pst.posto,
                         t.nome AS tecnico_nome
                FROM tbl_pesquisa p
                    JOIN tbl_resposta r USING(pesquisa)
                    JOIN tbl_tecnico  t USING(tecnico)
                    JOIN tbl_posto_fabrica pf ON pf.posto       = r.posto AND pf.fabrica = {$login_fabrica}
                    JOIN tbl_posto pst        ON pst.posto      = pf.posto
                    JOIN tbl_treinamento tr   ON tr.treinamento = p.treinamento
                WHERE p.fabrica       = {$login_fabrica}
                    AND p.treinamento = {$treinamento}
                    AND pst.posto     = {$posto}
                    AND r.tecnico     = {$tecnico};";
                  //  exit($sql_busca);
    $res_busca   = pg_query($con, $sql_busca);

    $array_perguntas = array();

    /******* MONTA TABLE *******/
    if (pg_num_rows($res_busca) > 0) {
        $array_perguntas = json_decode(pg_fetch_result($res_busca, 0, 'texto_ajuda'), true);
        $perguntas       = array();
       
        foreach ($array_perguntas AS $x_pergunta) {
            if (utf8_decode($x_pergunta['main_title']) == "Comentários") {
                continue;
            }

            if (!array_key_exists($x_pergunta['main_title'], $perguntas)) {
                $perguntas[$x_pergunta['main_title']] = array();
            }

            foreach ($x_pergunta['itens'] AS $x_pergunta_item) {
                $total_perguntas_item = count($x_pergunta['itens']);
                $perguntas[$x_pergunta['main_title']][$x_pergunta_item] = array(
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0
                );
            }
        }

        $txt_respostas       = json_decode(pg_fetch_result($res_busca, 0, 'txt_resposta'), true);;
        $treinamento         = pg_fetch_result($res_busca, 0, 'treinamento');
        $titulo              = pg_fetch_result($res_busca, 0, 'titulo');
        $posto_id            = pg_fetch_result($res_busca, 0, 'posto');
        $posto_nome          = pg_fetch_result($res_busca, 0, 'nome');
        $descricao           = pg_fetch_result($res_busca, 0, 'descricao');
        $tecnico_nome        = pg_fetch_result($res_busca, 0, 'tecnico_nome');
        $posto_nome          = (!empty($posto_nome)) ? $posto_nome : 'Convidado';

        $print .= "<table class='table table-striped table-bordered table-large table-center table-respostas'>
                        <thead>
                            <tr class='titulo_coluna'>
                                <th colspan='2' style='width: 388px; font-size: 15px;'><b>".$tecnico_nome." - ".$posto_nome."</b></th>
                            </tr>
                        </thead>
                    </tbody>
                </table><br /><br />";

        foreach ($txt_respostas AS $x_resposta) {
            if (utf8_decode($x_resposta['main_title']) == "Comentários") {
                continue;
            }
            foreach ($x_resposta['itens'] AS $x_resposta_item) {
                $perguntas[$x_resposta['main_title']][$x_resposta_item['ask']][$x_resposta_item['val']] += 1;
            }
        }

        $count               = count($perguntas);
        $count_array_foreach = 0;

        foreach ($perguntas AS $titulo => $x_pergunta_item) {
            $count_array_foreach++;
            $print .= "<table id='respostas-pesquisa-<?=$id?>' class='table table-striped table-bordered table-large table-center table-respostas' >
                    <thead>
                        <tr>
                            <th colspan='2'>".utf8_decode($titulo)."</th>
                        </tr>
                        <tr class='titulo_coluna' >
                            <th colspan='2' >Respostas</th>
                        </tr>
                    </thead>
                    <tbody>";
                        foreach ($x_pergunta_item AS $titulo_pergunta => $array_nota) {
                            $print .= "<tr class='titulo_coluna'>
                                        <th style='vertical-align: middle; width: 300px;'>".utf8_decode($titulo_pergunta)."</th>
                                        <td>
                                            <table class='table table-bordered' style='margin-bottom: 0px; table-layout: fixed;'>
                                                    <tr>";
                                                        $total = array_sum($array_nota);
                                                        foreach ($array_nota AS $nota => $escolhido) {
                                                            if ($escolhido != 1) {
                                                                continue;
                                                            } 
                                                            switch ($nota) {
                                                                case '1':
                                                                    $estrelas = "<i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gray'></i>
                                                                                <i class='fa fa-star gray'></i>
                                                                                <i class='fa fa-star gray'></i>
                                                                                <i class='fa fa-star gray'></i>";
                                                                break;
                                                                case '2':
                                                                    $estrelas = "<i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gray'></i>
                                                                                <i class='fa fa-star gray'></i>
                                                                                <i class='fa fa-star gray'></i>";
                                                                break;
                                                                case '3':
                                                                    $estrelas = "<i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gray'></i>
                                                                                <i class='fa fa-star gray'></i>";
                                                                break;
                                                                case '4':
                                                                    $estrelas = "<i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gray'></i>";
                                                                break;
                                                                case '5':
                                                                    $estrelas = "<i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gold'></i>
                                                                                <i class='fa fa-star gold'></i>";
                                                                break;
                                                                default: $estrelas = "@#!";
                                                            }
                                                            $print   .= $estrelas;
                                                        }
                            $print  .=  "   
                                                    </tr>
                                            </table>
                                        </td>
                                    </tr>";
                    }
            $print     .= "</tbody>";
            $print     .= "</table>";
        }

        echo $print;
        exit();
    }
}
include "rodape.php"; 
?>