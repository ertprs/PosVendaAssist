<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include 'autentica_admin.php';

$os 	= $_REQUEST["os"];
$pedido = $_REQUEST["pedido"];

$sqlRelatorio = "SELECT tbl_os_produto.os_produto,
                        tbl_produto.produto,
                        tbl_produto.referencia,
                        tbl_produto.descricao, 
                        tbl_os.os,
                        tbl_os.sua_os,
                        tbl_os.data_digitacao,
                        tbl_os_item.os_item,
                        tbl_os_item.pedido,
                        tbl_peca.peca,
                        tbl_peca.referencia as referencia_peca,
                        tbl_peca.descricao  as descricao_peca,
                        tbl_peca.produto_acabado
                         FROM tbl_os
                         JOIN tbl_os_produto    	 ON tbl_os_produto.os       		   = tbl_os.os
                         JOIN tbl_os_item       	 ON tbl_os_item.os_produto  		   = tbl_os_produto.os_produto
                         JOIN tbl_peca          	 ON tbl_peca.peca           	       = tbl_os_item.peca
                         JOIN tbl_produto       	 ON tbl_produto.produto     		   = tbl_os_produto.produto
                         WHERE tbl_os.os = {$os}";           

$resRelatorio = pg_query($con, $sqlRelatorio);

$sua_os = pg_fetch_result($resRelatorio, 0, 'sua_os');
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
	</head>
	<body>
		<?
	if (pg_numrows($resRelatorio) > 0) { ?>

	    <table id="relatorio_pecas" class="table table-striped table-bordered table-hover table-fixed">
	        <thead>
	            <tr class="titulo_tabela">
	                <th colspan="4" nowrap>OS <?= $sua_os ?></th>
	            </tr>
	            <tr class="titulo_coluna">
	                <th nowrap>Peça</th>
	                <th nowrap>Nota Fiscal</th>
	                <th nowrap>Emissão</th>
	            </tr>    
	        </thead>        
	        <tbody>    
	        <?
	            for ($x = 0 ; $x < pg_numrows($resRelatorio); $x++){ ;
	                $emissao              = trim(pg_result($resRelatorio,$x,'emissao'));
	                $nota_fiscal          = trim(pg_result($resRelatorio,$x,'nota_fiscal'));
	                $referencia_peca      = trim(pg_result($resRelatorio,$x,'referencia_peca'));
	                $descricao_peca       = trim(pg_result($resRelatorio,$x,'descricao_peca'));

	                ?>    
	                <tr>
	                    <?php 
	                        $sql = "SELECT emissao,nota_fiscal 
	                                FROM tbl_faturamento
	                                JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
	                                WHERE tbl_faturamento_item.pedido = {$pedido}";      
	                        $res = pg_query($con, $sql);
	                        
	                        $nota_fiscal = trim(pg_result($res,0,'nota_fiscal'));  
	                        $emissao     = trim(pg_result($res,0,'emissao'));      
	                    ?>
	                    <td class="tal">
	                        <?= $referencia_peca." - ".$descricao_peca ?>
	                    </td>
	                    <td class="tac">
	                        <?= $nota_fiscal ?>
	                    </td>
	                    <td class="tac">
	                        <?= $emissao ?>
	                    </td>
	                </tr>
	                <?
	            }
	        ?>
	        </tbody>
	    </table>    
	</body>
	<?php } ?>
</html>
