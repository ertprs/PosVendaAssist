<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$extrato = filter_input(INPUT_GET,'extrato',FILTER_VALIDATE_INT);

$sqlBuscaPecas = "
    SELECT  tbl_os.os,
            tbl_os.sua_os,
            CASE WHEN tbl_os.consumidor_revenda = 'C'
                    THEN 'Consumidor'
                    ELSE 'Revenda'
            END AS consumidor_revenda,
            tbl_os_produto.mao_de_obra,
            tbl_peca.peca,
            tbl_peca.referencia,
            tbl_peca.descricao,
            tbl_os_item.qtde,
            (tbl_os_item.qtde * tbl_os_item.custo_peca) AS valor_peca
    FROM    tbl_os
    JOIN    tbl_os_extra USING (os)
    JOIN    tbl_os_produto USING (os)
    JOIN    tbl_os_item USING (os_produto)
    JOIN    tbl_peca USING(peca)
    WHERE   tbl_os.fabrica = $login_fabrica
    AND     tbl_os_extra.extrato = $extrato
";
$resBuscaPecas = pg_query($con,$sqlBuscaPecas);

$resultado = pg_fetch_all($resBuscaPecas);

?>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<style type="text/css">
.main-content-sbx{
  overflow: auto;
  height: 500px;
}
</style>

<body class="container" style="background-color: #FFFFFF; overflow: hidden" >
<div class="main-content-sbx" />
<table id="callcenter_relatorio_atendimento" class='table table-striped table-bordered table-hover table-large'>
    <thead>
        <tr class="titulo_coluna">
            <th colspan="6">Peças relacionadas às OS do extrato <?=$extrato?></th>
        </tr>
        <tr class="titulo_coluna">
            <th>OS</th>
            <th>REV / CONS</th>
            <th>Mão-de-Obra</th>
            <th>Peça</th>
            <th>Qtde</th>
            <th>Valor Peça</th>
        </tr>
    <thead>
    <tbody>
<?php
foreach ($resultado as $i=>$valor) {
?>
        <tr>
            <td><?=$valor['sua_os']?></td>
            <td><?=$valor['consumidor_revenda']?></td>
            <td class="tar">R$ <?=number_format($valor['mao_de_obra'],2,',','')?></td>
            <td><?=$valor['referencia']?> - <?=$valor['descricao']?></td>
            <td class="tar"><?=$valor['qtde']?></td>
            <td class="tar">R$ <?=number_format($valor['valor_peca'],2,',','')?></td>
        </tr>
<?php
}
?>
    </tbody>
</table>
</body>