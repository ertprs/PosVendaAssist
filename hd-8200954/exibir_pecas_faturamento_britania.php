 <?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'fn_logoResize.php';

$faturamento = $_GET["faturamento"];
$nota_fiscal = $_GET["nota"];

$sql = "SELECT DISTINCT ON (tbl_faturamento_item.peca)
			tbl_faturamento_item.peca,
            tbl_faturamento.extrato_devolucao,
			tbl_faturamento_item.preco as valor_unitario,
			tbl_peca.referencia,
			tbl_peca.descricao
		FROM tbl_faturamento_item
		JOIN tbl_peca        USING(peca)
        JOIN tbl_faturamento USING(faturamento)
		WHERE tbl_faturamento_item.faturamento = {$faturamento}";
$res = pg_query($con, $sql);	

?>
<link type="text/css" rel="stylesheet" media="screen" href="admin/bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="admin/bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="admin/css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="admin/css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<script src="admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="admin/bootstrap/js/bootstrap.js"></script>
<script src='admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>

 <table class="table table-bordered table-fixed" id="nf_recebidas">
        <thead>
            <tr class="titulo_tabela">
                <th colspan="100%">Informações da Nota Fiscal <?= $nota_fiscal ?></th>
            </tr>    
            <tr class="titulo_coluna">
                <th>Peca</th>
                <th>Preço Unitário</th>
                <th>Quantidade</th>
                <th>Qtde. Devolvida</th>
            </tr>
        </thead>
        <tbody>
<?php 
        if (pg_num_rows($res) > 0) {  
                for ($i=0;$i < pg_num_rows($res);$i++) {
                    $peca               = pg_fetch_result($res, $i, 'peca');
                    $extrato_devolucao  = pg_fetch_result($res, $i, 'extrato_devolucao');
                    $referencia         = pg_fetch_result($res, $i, 'referencia');
                    $descricao          = pg_fetch_result($res, $i, 'descricao');
                    $preco              = pg_fetch_result($res, $i, 'valor_unitario');

                    $sql_lgr = "SELECT qtde_nf 
                                FROM tbl_extrato_lgr
                                WHERE peca  = {$peca}
                                AND extrato = {$extrato_devolucao}";
                    $res_lgr = pg_query($con, $sql_lgr);

                    $qtde_nf = pg_fetch_result($res_lgr, 0, 'qtde_nf');  

                    $sql_qtde = " SELECT SUM(tbl_faturamento_item.qtde) as qtde
                                FROM tbl_faturamento_item
                                JOIN tbl_peca        USING(peca)
                                JOIN tbl_faturamento USING(faturamento)
                                WHERE tbl_faturamento_item.faturamento = {$faturamento}
                                AND tbl_faturamento_item.peca          = {$peca}";      
                    $res_qtde = pg_query($con, $sql_qtde);

                    $qtde     = pg_fetch_result($res_qtde, 0, 'qtde');          
        ?>    
                    <tr>
                        <td><?= $referencia." - ".$descricao ?></td>
                        <td class="tac"><?= $preco ?></td>
                        <td class="tac"><?= $qtde ?></td>
                        <td class="tac"><?= $qtde_nf ?></td>
                    </tr>
        <?php 
                }

        }
?>
	</tbody>
</table>	
