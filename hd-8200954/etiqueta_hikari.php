<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

$os = filter_input(INPUT_GET, 'os', FILTER_SANITIZE_NUMBER_INT);

$sql = "SELECT 
    case when consumidor_revenda = 'R' then tbl_os.revenda_nome else consumidor_nome end as revenda_nome,
    tbl_produto.referencia,
    tbl_produto.descricao,
    COUNT(*) as quantidade,
    TO_CHAR(tbl_os.data_abertura::date, 'DD/MM/YYYY') as data,
    tbl_os.nota_fiscal
FROM tbl_os
JOIN tbl_produto USING(produto)
WHERE tbl_os.sua_os LIKE :os AND tbl_os.fabrica = 144
GROUP BY 
    1,
    tbl_produto.referencia, 
    tbl_produto.descricao,
    tbl_os.data_abertura,
    tbl_os.nota_fiscal";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':os', "{$os}%", PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC)
?>

<style>
	table {
		font-family: sans-serif;
		font-size: 10px;
        	border-collapse: collapse;
	}
    table td {
        border: solid 1px #000000;
        padding: 8px;
    }
    .assinaturas td {
        height: 50px;
    }
</style>

	<table style='width: 793px; margin: 0 auto;'>
        <tr style='text-align: center'>
            <td colspan=8> 
                <img src="logos/logo_hikari.jpg" alt="Hikari" style="max-height:50px; float: left" />
            </td>
            <td> <strong>NF</strong> </td>
            <td> <?= $data[0]['nota_fiscal'] ?> </td>
            <td> <strong>OS</strong> </td>
            <td> <?= $os ?> </td>
        </tr>
        <tr style='text-align: center; height: 40px'>
            <td> CLIENTE </td>
            <td colspan=7> <strong> <?= $data[0]['revenda_nome'] ?> </strong> </td>
            <td> <strong>N.U</strong> </td>
            <td> </td>
            <td> <strong>DATA</strong> </td>
            <td> <?=$data[0]['data'] ?> </td>
        </tr>
        <tr style='text-align: center'>
            <td> <strong>CÓDIGO</strong> </td>
            <td colspan=4> <strong>MODELO</strong> </td>
            <td width="50px"> <strong>QUANT.</strong> </td>
            <td> <strong>GARANTIA</strong> </td>
            <td> <strong>ORÇAMENTO</strong> </td>
            <td> <strong>AG PEÇAS</strong> </td>
            <td> <strong>PRONTO</strong> </td>
            <td colspan=3> <strong>OBSERVAÇÃO</strong> </td>
        </tr>
        
        <?php foreach($data as $item) { ?>
        <tr>
            <td> <?= $item['referencia'] ?> </td>
            <td colspan=4> <?= $item['descricao'] ?> </td>
            <td style='text-align: center'> <?= $item['quantidade'] ?> </td>
            <td style='text-align: center'> 

            </td>
            <td>  </td>
            <td>  </td>
            <td>  </td>
            <td colspan=3>  </td>
        </tr>
        <?php } ?>

        <?php for($i=0; $i<3; $i++) { ?>
        <tr>
            <td> </td>
            <td colspan=4> </td>
            <td> </td>
            <td> </td>
            <td> </td>
            <td> </td>
            <td> </td>
            <td colspan=3> </td>
        </tr>
        <?php }?>
        
        <tr style='text-align: center; font-weight: bold'>
            <td colspan=2> TÉCNICO </td>
            <td colspan=4> DATA AVALIAÇÃO </td>
            <td colspan=2> DATA APROVAÇÃO </td>
            <td colspan=2> DATA MANUTENÇÃO </td>
            <td colspan=2> REPROVAÇÃO </td>
        </tr>
        <tr class="assinaturas">
            <td colspan=2> </td>
            <td colspan=4> </td>
            <td colspan=2> </td>
            <td colspan=2> </td>
            <td colspan=2> </td>
        </tr>
	</table>

<script type="text/javascript">
    window.print();
</script>
