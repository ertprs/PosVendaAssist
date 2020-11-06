<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
use Lojavirtual\Produto;
$objProduto  = new Produto();

if ($_REQUEST["ajax_kit_peca"] == true) {

    $ajax_kit_peca   = $_REQUEST["ajax_kit_peca"];
    $peca_referencia = $_REQUEST["peca_referencia"];
    $peca_descricao  = $_REQUEST["peca_descricao"];
    $tipo            = $_REQUEST["tipo"];
    $posicao         = $_REQUEST["posicao"];

    $condi["descricao_peca"]  = $peca_descricao;
    $condi["referencia_peca"] = $peca_referencia;
    $condi["kit_peca"]        = true;

    $dadosProduto = $objProduto->getAll($condi);
}

if ($_REQUEST["ajax_busca_peca"] == true) {


    $ajax_busca_peca   = $_REQUEST["ajax_busca_peca"];
    $referencia_peca = $_REQUEST["referencia_peca"];
    $descricao_peca  = $_REQUEST["descricao_peca"];
    $tipo            = $_REQUEST["tipo"];

    $condi["descricao_peca"]  = $descricao_peca;
    $condi["referencia_peca"] = $referencia_peca;

    $dadosProduto = $objProduto->getAll($condi);
    unset($dadosProduto["kits"]);

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
    <style>
        .tabela_itens th, .table td {
            vertical-align: middle !important;
        }
        .tabela_itens{
            border-color: #eee;
            background-color: #ffffff;
        }
        .tabela_itens tr td{
            padding:5px;
        }
        .titulo_tabela {
            background-color: #596d9b;
            font: bold 16px "Arial";
            color: #FFFFFF;
            text-align: center;
            padding: 5px 0 0 0;
        }
    </style>
    <script>
        $(function () {
            $.dataTableLupa();
        });
    </script>
</head>
<body>
<div id="container_lupa" style="overflow-y:auto;">
    <?php if (count($dadosProduto) > 0) {?>
    <div id="border_table">
        <table class="table table-striped table-bordered table-hover table-lupa" >
            <thead>
                <tr class='titulo_coluna'>
                    <th>Referência</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($dadosProduto as $key => $rows) {
                    $r = array(
                            "loja_b2b_peca"      => $rows["codigo_peca"],
                            "descricao" => utf8_encode($rows["nome_peca"]),
                            "referencia" => utf8_encode($rows["ref_peca"]),
                            "preco" => $rows["preco"],
                            "posicao" => $posicao
                        );
                    echo "<tr style='cursor:pointer;' onclick='window.parent.retorna_peca(".json_encode($r)."); window.parent.Shadowbox.close();' >";
                ?>
                        <td><?php echo $rows["ref_peca"];?></td>
                        <td><?php echo $rows["nome_peca"];?></td>
                    </tr>
                <?php }?>
            </tbody>
        </table>
    </div>
    <?php } else {?>
        <div class="alert alert_shadobox">
            <h4>Nenhum resultado encontrado</h4>
        </div>
    <?php }?>
</div>
</body>
</html>