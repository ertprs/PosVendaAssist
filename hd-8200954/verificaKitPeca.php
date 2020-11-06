<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$posto = $_GET['posto'];

header('Content-Type: text/html; charset=iso-8859-1');
$title = "Verifica Kit Peça";

$dados = $_GET['dados'];

$arrkit = (json_decode($dados, true));


?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title><?=$title?></title>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>

        <script type="text/javascript">
            
            $(function(){
                
            });

        </script>

		<style>
            .diferente{
                color:red;
            }
			
		</style>
		
	</head>
<body>

	
<?php 

//if (count($dataTable)):
	$tableAttrs = array(
		'tableAttrs'   => ' class="table table-striped table-bordered table-hover table-fixed"',
		'captionAttrs' => ' class="titulo_tabela"',
		'headerAttrs'  => ' class="titulo_coluna"',
	);
?>

	<div class="container">
        <?php if(strlen($msg_erro)>0){ ?>
        <div class="row-fluid">
            <div class="alert alert-danger"><?=$msg_erro?></div>
        </div>
        <?php } if(strlen($ok)>0){ ?>
        <div class="row-fluid">
            <div class="alert alert-success"><?=$ok?></div>
        </div>
        <?php } ?>
		<div class="row-fluid">
		<table class="table table-striped table-bordered table-fixed">
            <tr  class="titulo_coluna">
                <th colspan="4" >Peças Sem Estoque</th>
            </tr>
            <tr class="titulo_coluna">
                <th colspan="2" >Referência</th>
                <th colspan="2" >Descrição</th>
            </tr>
            <?php foreach($arrkit['pecas'] as $peca){ 
            	$sql = "SELECT descricao from tbl_peca where referencia = '$peca' and fabrica = $login_fabrica ";
            	$res = pg_query($con, $sql);
            	if(pg_num_rows($res)>0){
            		$descricao = pg_fetch_result($res, 0, 'descricao');
            ?>
            <tr>
                <td class="tac" colspan="2"><?= $peca ?></td>
                <td class="tac" colspan="2"><?= $descricao ?> </td>
            </tr>
            <?php } } ?>
            <tr  class="titulo_coluna">
                <th colspan="4" >Substituir por Kit</th>
            </tr>
            <tr class="titulo_coluna">
                <th>Referência</th>
                <th>Descrição</th>
                <th>Estoque</th>
                <th>Preço</th>
            </tr>
            <?php foreach($arrkit['kit'] as $chave => $peca){ ?>
            <tr>
                <td class="tac"><?= $peca['referencia'] ?></td>
                <td class="tac"><?= $arrkit['descricao'][$peca['referencia']] ?></td>
                <td class="tac"><?= $arrkit['estoque'][$peca['referencia']]?> </td>
                <td class="tac"><?php 
                	$sql = "SELECT preco from tbl_tabela_item 
                			join tbl_tabela on tbl_tabela_item.tabela = tbl_tabela.tabela and tbl_tabela.fabrica = $login_fabrica 
                			join tbl_peca on tbl_peca.peca = tbl_tabela_item.peca 
                		WHERE tbl_tabela.descricao = 'VENDA' and tbl_tabela.ativa is true and tbl_peca.referencia = '".$peca['referencia']."' "; 
                	$res = pg_query($con, $sql);
                	if(pg_num_rows($res)>0){
                		$preco = pg_fetch_result($res, 0, preco);
                		echo "R$ ".number_format($preco, 2, ',', '');
                	}

                ?>
                 </td>
            </tr>
            <?php } ?>
        </table>
        	<div class="row-fluid">
        		<div class="col-md-12" style="text-align: center">
        			Deseja substituir as peças sem estoque pelos Kit apresentados?<br> 
        			<button type="button" class="btn btn-success" onclick="window.parent.retorno_troca_kit('sim');	window.parent.Shadowbox.close();">Sim</button>
        			<button type="button" class="btn btn-danger"  onclick="window.parent.retorno_troca_kit('nao');	window.parent.Shadowbox.close();">Não</button>
        		</div>
        	</div>

		</div>      
        
	</div>
<?php //endif; ?>
</body>
</html>
