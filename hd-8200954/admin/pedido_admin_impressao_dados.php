<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include_once "../class/tdocs.class.php";

?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>Impressão do Pedido</title>
    <meta http-equiv=pragma content=no-cache>
    <style>
        body {
            font-family: segoe ui,arial,helvetica,verdana,sans-serif;
            font-size: 12px;
            margin:0px;
        }
        table {
            font-size: 12px;
        }
        a {
            text-decoration: none;
            color: #000000;
        }
        a:hover {
            text-decoration: underline;
        }

        table, th, td {
          border: 1px solid black;
        }

        table {
            border-collapse: collapse;
            width: 80%;
            text-align:center; 
            margin-left:auto; 
            margin-right:auto; 
        }

        .assinaturas {
            text-align: center;
        }

    </style>
</head>

<body>


<?php

if (isset($_GET['pedido'])) {

    $pedido = $_GET['pedido'];

    $sql = "SELECT  PE.pedido, TPE.descricao AS tipo_pedido, PST.nome AS posto, PST.cnpj, PST.fone,
                    PI.pedido_item, PC.peca,PC.referencia,PC.descricao, C.descricao AS condicao_pagamento,
                    (PI.qtde - PI.qtde_faturada - PI.qtde_faturada_distribuidor) as qtde,
                    PI.preco,
                    PE.posto, PE.distribuidor, PE.data, T.descricao AS tabela, PC.ipi,
                    PE.valores_adicionais
            FROM    tbl_pedido       PE
            JOIN    tbl_pedido_item  PI  ON PI.pedido         = PE.pedido
            JOIN    tbl_peca         PC  ON PC.peca           = PI.peca
            JOIN    tbl_posto        PST ON PST.posto         = PE.posto
            JOIN    tbl_tipo_pedido  TPE ON TPE.tipo_pedido   = PE.tipo_pedido
            JOIN    tbl_condicao     C   ON C.condicao        = PE.condicao
            JOIN    tbl_tabela       T   ON T.tabela          = PE.tabela
            WHERE   PI.pedido      = $pedido
            AND     PE.fabrica     = $login_fabrica";

    $res = pg_query($con, $sql); ?>

    <br>

    <div>
        <table>
            <thead>
                <th>DADOS DO PEDIDO</th>
            </thead>
        </table>

        <table>
            <thead>
                <th>Pedido</th>
                <th>Data</th>
                <th>Tabela</th>
                <th>Condicao Pagamento</th>
                <th>Tipo Pedido</th>
            </thead>
            <tbody>
            <?php  for ($i = 0; $i < pg_num_rows($res); $i++) { 

                $pedido             = pg_fetch_result($res, $i, 'pedido'); 
                $data               = pg_fetch_result($res, $i, 'data'); 
                $data               = date("d/m/Y", strtotime($data));
                $tabela             = pg_fetch_result($res, $i, 'tabela'); // preciso pegar
                $condicao_pagamento = pg_fetch_result($res, $i, 'condicao_pagamento');
                $tipo_pedido        = pg_fetch_result($res, $i, 'tipo_pedido'); 
            ?>
                <tr>
                    <td><?= $pedido ?></td>
                    <td><?= $data ?></td>
                    <td><?= $tabela ?></td>
                    <td><?= $condicao_pagamento ?></td>
                    <td><?= $tipo_pedido ?></td>
                <tr>
            <?php } ?>
            </tbody>
        </table>

        <table>
            <thead>
                <th>CNPJ</th>
                <th>Posto</th>
                <th>Fone</th>
            </thead>
            <tbody>
            <?php  for ($i = 0; $i < pg_num_rows($res); $i++) { 

                $cnpj  = pg_fetch_result($res, $i, 'cnpj'); 
                $posto = pg_fetch_result($res, $i, 'posto'); 
                $fone  = pg_fetch_result($res, $i, 'fone');
            ?>
                <tr>
                    <td><?= $cnpj ?></td>
                    <td><?= $posto ?></td>
                    <td><?= $fone ?></td>
                <tr>
            <?php } ?>
            </tbody>
        </table>

        <table>
            <thead>
                <th>Registro</th>
                <th>Departamento</th>
                <th>Nome Funcionário</th>
            </thead>
            <tbody>
            <?php  

            for ($i = 0; $i < pg_num_rows($res); $i++) { 

                $registro          = pg_fetch_result($res, $i, 'cnpj');   
                $departamento      = pg_fetch_result($res, $i, 'posto');
                $nome_funcionario  = pg_fetch_result($res, $i, 'nome_funcionario'); 

                $valores_adicionais = pg_fetch_result($res, $i, 'valores_adicionais');
                $valores_adicionais = json_decode($valores_adicionais);
            ?>
                <tr>
                    <td><?= $valores_adicionais->registro_funcionario ?></td>
                    <td><?= $valores_adicionais->departamento_funcionario ?></td>
                    <td><?= $valores_adicionais->nome_funcionario ?></td>
                <tr>
            <?php } ?>
            </tbody>
        </table>

        <table>
          <thead>
            <tr>
              <th>Referencia</th>
              <th>Descricao</th>
              <th>Quantidade</th>
              <th>Preco</th>
              <th>Total de Itens</th>
            </tr>
          </thead>
          <tbody>


        <?php  
        $total_ipi = 0;
        for ($i = 0; $i < pg_num_rows($res); $i++) { 

            $referencia   = pg_fetch_result($res, $i, 'referencia');
            $descricao    = pg_fetch_result($res, $i, 'descricao');
            $quantidade   = pg_fetch_result($res, $i, 'qtde');
            $preco        = pg_fetch_result($res, $i, 'preco');
            $preco_string = "R$ " . $preco;
            $total_item   = $preco * $quantidade;
            //$ipi          = pg_fetch_result($res, $i, 'ipi');
            //$total_imposto = ($total_item * $ipi) / 100;
            //$total_item_imposto = $total_item + $total_imposto;
        ?>
            <tr>
              <td><?= $referencia ?></td>
              <td><?= $descricao ?></td>
              <td><?= $quantidade ?></td>
              <td><?= number_format($preco, 2, ',', '') ?></td>
              <td><?= number_format($total_item, 2, ',', '') ?></td>
            </tr>
        <?php
            $total_ipi += $total_item;
            //$total_ipi += $total_item_imposto;
        } ?>
          </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Total </th>
                    <th><?= number_format($total_ipi, 2, ',', '') ?></th>
                </tr>
            </thead>
        </table>

    </div>

    <br><br><br>

    <div class="assinaturas">
        ___________________________________________________
        <br><br>           
                    <p>Assinatura do Responsável</p>
        <br><br>
        ___________________________________________________
        <br><br>
                   <p>Assinatura do Funcionário</p>

    </div>

    <body onload="window.print();">

<?php } else { 
    echo "404. Página não encontrada";
} ?>

</body>
</html>