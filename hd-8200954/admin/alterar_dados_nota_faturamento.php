<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include "funcoes.php";

header('Content-Type: text/html; charset=iso-8859-1');
$title = "Dados do Pedido";

if(isset($_POST['btnacao'])){

    $qtde = $_POST['qtde'];
    pg_query($con, "BEGIN");
    for($a=0; $a<$qtde; $a++){
        $faturamento = $_POST['faturamento'][$a];
        $docnum         = $_POST['docnum'][$a];
        $pedido_item    = $_POST['pedido_item'][$a];
        
        $nota_fiscal = $_POST["nota_fiscal"][$a];
        $emissao = formata_data($_POST["emissao"][$a]);
        $nota_fiscal_anterior = $_POST["nota_fiscal_anterior"][$a];
        $emissao_anterior = $_POST["emissao_anterior"][$a];

        $dados_alterados['admin'] = $login_admin;
        $dados_alterados['data'] = date('d-m-Y');
        $dados_alterados['nota_fiscal_anterior'] = $nota_fiscal_anterior;
        $dados_alterados['emissao_anterior'] = $emissao_anterior;

        $info_extra = json_encode($dados_alterados);

        $sqlDocNum = "UPDATE tbl_pedido_item SET serie_locador = '$docnum' WHERE pedido_item = $pedido_item";
        $resDocNum = pg_query($con, $sqlDocNum);
        if(strlen(pg_last_error($con))>0){
            $msg_erro .= pg_last_error($con);
        }            
        
        
        if($faturamento != $faturamento_anterior){
            $sqlChecaNota = "SELECT * FROM tbl_faturamento WHERE nota_fiscal = '{$nota_fiscal}' AND fabrica = {$login_fabrica};";
            $resChecaNota = pg_query($con, $sqlChecaNota);

            $jsonAntigoFat = pg_fetch_assoc($resChecaNota);
            foreach ($jsonAntigoFat as $key => $value) {
                $jsonAntigoFat[$key] = utf8_decode($value);
            }

            $jsonAntigoFat = pg_escape_string(json_encode($jsonAntigoFat));

            if (pg_num_rows($resChecaNota) > 0){
                pg_query($con, 'BEGIN');

                $notaFabrica = pg_fetch_result($resChecaNota, 0, fabrica);
                $notaSaida = pg_fetch_result($resChecaNota, 0, saida);
                $notaPosto = pg_fetch_result($resChecaNota, 0, posto);
                $notaCfop = pg_fetch_result($resChecaNota, 0, cfop);
                $notaTotal = pg_fetch_result($resChecaNota  , 0, total_nota);

				if(!empty($notaPosto)) {
					$sqlNovoFat = "INSERT INTO tbl_faturamento (fabrica, emissao, saida, posto, cfop, total_nota, nota_fiscal, info_extra) 
						VALUES ({$notaFabrica}, '{$emissao}', '{$notaSaida}', {$notaPosto}, '{$notaCfop}', {$notaTotal}, '{$nota_fiscal}', '{$jsonAntigoFat}') RETURNING faturamento";

					$resNovoFat = pg_query($con, $sqlNovoFat); 
					echo $sqlNovoFat;
					$notaFaturamento = pg_fetch_result($resChecaNota, 0, faturamento); 

					$sqlAntigoFat = "SELECT * FROM tbl_faturamento_item WHERE faturamento = {$notaFaturamento};";
					echo $sqlAntigoFat;
					$resAntigoFat = pg_query($con, $sqlAntigoFat); 

					$faturamentoId = pg_fetch_result($resNovoFat, 0, faturamento);
					$faturamentoPeca = pg_fetch_result($resAntigoFat, 0, peca);
					$faturamentoPreco = pg_fetch_result($resAntigoFat, 0, preco);
					$faturamentoQtde = pg_fetch_result($resAntigoFat, 0, qtde);
					$faturamentoPedidoItem = pg_fetch_result($resAntigoFat, 0, pedido_item);

					$sqlFatItem = "INSERT INTO tbl_faturamento_item (faturamento, peca, qtde, preco, pedido, pedido_item) VALUES ({$faturamentoId}, '{$faturamentoPeca}', {$faturamentoQtde}, {$faturamentoPreco}, {$pedido}, '{$faturamentoPedidoItem}')";

					$resFatItem = pg_query($con, $sqlFatItem); 
				}
                if(strlen(pg_last_error($con))>0){
                    pg_query($con, 'ROLLBACK');
                }

            } else {
                $sqlUpd = "UPDATE tbl_faturamento SET info_extra = '$info_extra', emissao = '$emissao', nota_fiscal = '$nota_fiscal' WHERE faturamento = $faturamento ";
                $resUpd = pg_query($con, $sqlUpd);
                if(strlen(pg_last_error($con))>0){
                    $msg_erro .= pg_last_error($con);
                }
            }
        }
        $faturamento_anterior = $faturamento;
    }
    if(strlen(trim($msg_erro))==0){
        pg_query($con, "COMMIT");
        $ok = "Dados Atualizado com sucesso.";
    }else{
        pg_query($con, "ROLLBACK");    
        $msg_erro = "Falha ao atualizar dados.";
    }
}

$pedido = $_REQUEST["pedido"];

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title><?=$title?></title>
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />
		<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="../bootstrap/js/bootstrap.js"></script>
        <script type="text/javascript" src="js/jquery.mask.js"></script>        
        <script type="text/javascript">            
            $(function(){                
                $('#emissao').mask("99/99/9999");
            });
        </script>
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
        <?php  

            $sqlItens = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_pedido_item.pedido_item, tbl_faturamento.emissao, tbl_faturamento.nota_fiscal, tbl_faturamento.faturamento, tbl_pedido_item.serie_locador 
                                FROM tbl_pedido_item 
                                JOIN tbl_peca on tbl_peca.peca = tbl_pedido_item.peca and tbl_peca.fabrica = $login_fabrica
                                JOIN tbl_faturamento_item on tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item 
                                JOIN tbl_faturamento on tbl_faturamento_item.faturamento = tbl_faturamento.faturamento and tbl_faturamento.fabrica = $login_fabrica
                                WHERE tbl_faturamento_item.pedido = $pedido
                                 ";
                $resItens = pg_query($con, $sqlItens); 

                $qtde = pg_num_rows($resItens); 

            if($qtde > 0){

         ?>
		<div class="row-fluid">
		<table class="table table-striped table-bordered table-hover table-fixed" width="700">
            <thead  class="titulo_coluna">
                <tr>
                    <th colspan="4">Dados do Pedido <?=$pedido?></th>
                </tr>
                <tr>
                    <th>Peça</th>
                    <th>Emissão</th>
                    <th>Nota Fiscal</th>
                    <th>DOCNUM</th>
                </tr>
            </thead>
            <body>  
            <form name="frm_docnum" method="POST" action="alterar_dados_nota_faturamento.php">
            <?php 
                for($i=0; $i<$qtde; $i++){
                    $pedido_item    = pg_fetch_result($resItens, $i, 'pedido_item');
                    $referencia     = pg_fetch_result($resItens, $i, 'referencia');
                    $descricao      = pg_fetch_result($resItens, $i, 'descricao');
                    $emissao        = mostra_data(pg_fetch_result($resItens, $i, 'emissao'));
                    $nota_fiscal    = pg_fetch_result($resItens, $i, 'nota_fiscal');
                    $faturamento    = pg_fetch_result($resItens, $i, "faturamento");
                    $docnum = pg_fetch_result($resItens, $i, "serie_locador");

                    $dados_docnum = (strlen(trim($_POST['docnum'][$i]))>0)? $_POST['docnum'][$i] : $docnum;
            ?>
                <tr>
                    <td><?="$referencia - $descricao"; ?></td>
                    <td style="text-align: center">
                        <input style="width: 100px" type='text'  data-provide="datepicker" name='emissao[]' id="emissao" maxlength="10"  value='<?=$emissao?>' > 
                        <input style="width: 100px" type='hidden' name='emissao_anterior[]' maxlength="10"  value='<?=$emissao?>'>
                    </td>
                    <td style="text-align: center">
                        <input style="width: 100px" type='text' name='nota_fiscal[]' value='<?=$nota_fiscal?>'>
                        <input style="width: 100px" type='hidden' name='nota_fiscal_anterior[]' value='<?=$nota_fiscal?>'>

                    </td>
                    <td style="text-align: center">
                        <input style="width: 100px" type='text' name='docnum[]' value='<?=$dados_docnum?>'>
                        <input type="hidden" name="pedido_item[]" value="<?=$pedido_item?>">
                        <input type="hidden" name="faturamento[]" value="<?=$faturamento?>">
                        
                        
                    </td>
                </tr>                
            <?php 
                    $fat_ant = $faturamento;
                    } ?>
            </body>
            <tr>
                <td colspan="4" style="text-align: center">
                    <input type="submit" class="btn btn-primary" name="btnacao" value="Gravar">
                    <input type="hidden" name="qtde" value="<?=$qtde?>">
                    <input type="hidden" name="pedido" value="<?=$pedido?>">
                </td>
            </tr>
        </table>
		</div>
        <?php } ?>
	</div>
<?php //endif; ?>
</body>
</html>
