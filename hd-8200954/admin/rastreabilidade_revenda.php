<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

// $admin_privilegios	= "gerencia";
// $layout_menu 		= "gerencia";
// $title 				= "Rastreabilidade Revenda";

// include "cabecalho_new.php";
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);

include("plugin_loader.php");
// echo "<pre>";
// print_r($_GET);
// echo "</pre>";
?>
<script type="text/javascript">
function rastreabilidade(data_in, data_f, cnpj, produto, familia, fornecedor, posto) {

        if (!fornecedor) {
            fornecedor = '';
        }

        if (!data_in) {
            data_in = '';
        }

        if (!data_f) {
            data_f = '';
        }

        if (!familia) {
            familia = '';
        }

        if (!produto) {
            produto = '';
        }

        if (!posto) {
            posto = '';
        }

        if (!cnpj) {
            cnpj = '';
        }

		var url="rastreabilidade_produto_revenda.php?dti=" + data_in + "&dtf=" + data_f + "&fon=" + fornecedor + "&fa=" + familia + "&pro=" + produto + "&pos=" + posto + "&cnpj=" + cnpj;


		window.open (url, "rastreabilidade_produto_revenda", "height=320,width=640,scrollbars=1");
	}
</script>
<?

$revenda_referencia = $_GET['cnpj'];
$data_inicio = $_GET['dti'];
$data_fim	= $_GET['dtf'];
$fornecedor_p = $_GET['fon'];
$familia_p = $_GET['fa'];
$produto_p = $_GET['pro'];
$peca_p = $_GET['pec'];
$posto_p = $_GET['pos'];


if (strlen($revenda_referencia) > 0 AND strlen($data_inicio) > 0 AND strlen($data_fim) >0 ) {

	if (strlen($familia_p)>0) {

		$query_familia = " AND tbl_produto.familia = $familia_p ";
	}else{

		$query_familia = '';
	}

	if (strlen($fornecedor_p)>0) {

		$query_fornecedor = " AND tbl_ns_fornecedor.nome_fornecedor = '$fornecedor_p' ";
	}else{
		$query_fornecedor = "";
	}
		$join_fornecedor = "LEFT JOIN tbl_ns_fornecedor on tbl_ns_fornecedor.numero_serie = tbl_numero_serie.numero_serie
				  		LEFT JOIN tbl_ns_fornecedor_peca on tbl_ns_fornecedor_peca.ns_fornecedor_peca = tbl_ns_fornecedor.ns_fornecedor_peca ";


	if (strlen($revenda_referencia) > 0) {
		$query_revenda = " AND tbl_revenda.cnpj = '$revenda_referencia' ";
		$join_revenda = " JOIN tbl_revenda on tbl_numero_serie.cnpj = tbl_revenda.cnpj ";
	}else{
		$query_revenda = "";
		$join_revenda = "";
	}

	if (strlen($produto_p)>0) {
		$query_produto = " AND tbl_produto.produto in ($produto_p) ";
	}else{
		$query_produto = "";
	}

	if (strlen($peca_p)>0) {
	 	$query_peca = " AND tbl_ns_fornecedor.peca in ($peca_p) ";
	}else{
		$query_peca = "";
		$join_peca = "";
	}


	$sql_r = "	SELECT 	tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_produto.produto,
						count(distinct serie) as qtd_prod
					FROM tbl_numero_serie
						JOIN tbl_produto on tbl_produto.produto = tbl_numero_serie.produto
						$join_revenda
						$join_fornecedor
						$join_peca
					WHERE tbl_numero_serie.fabrica = $login_fabrica
						AND tbl_numero_serie.data_venda BETWEEN '$data_inicio' AND '$data_fim'
						$query_posto
						$query_fornecedor
						$query_familia
						$query_revenda
						$query_produto
						$query_peca
				GROUP BY tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao;";
	$res_r = pg_query($con,$sql_r);

	//echo nl2br($sql_r);
}?>
<head>
	<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
	<script src="bootstrap/js/bootstrap.js"></script>
<?
if (pg_num_rows($res_r) > 0) {
	?>
	<script type="text/javascript" charset="utf-8">

	$(function() {
		$.dataTableLoad();
	});
	</script>


	<div id="DataTables_Table_0_wrapper" class="dataTables_wrapper form-inline" >
	<table class='table table-striped table-bordered table-hover table-fixed'>
	<thead>
	<tr class='titulo_coluna'>
		<td >Produto</td>
		<td>Total</TD>
    </tr>
    </thead>
    <?php
    $valor_total = 0;
    for($x=0;pg_numrows($res_r)>$x;$x++){
    	$produto = pg_fetch_result($res_r, $x, produto);
    	$referencia = pg_fetch_result($res_r, $x, referencia);
    	$descricao = pg_fetch_result($res_r, $x, descricao);
    	$total_prod = pg_fetch_result($res_r, $x, qtd_prod);
    	$valor_total = $total_prod + $valor_total;
    	?>
    	<tr>
		<?
		//echo "<td><a href='rastreabilidade_produto_revenda.php?revenda=$revenda_cnpj&data_inicio=$data_inicio&data_fim=$data_fim&produto=$produto' target='_blank'>$referencia - $descricao</a></td>";
																//data_in, 				data_f, 					cnpj, 						produto, 				familia, 				fornecedor, 			posto, 				peca
		echo '<td style="cursor: pointer" onClick="rastreabilidade(\'' . $data_inicio . '\', \'' . $data_fim . '\', \'' . $revenda_referencia . '\', \'' . $produto . '\', \'' . $familia_p . '\', \'' . $fornecedor_p . '\', \'' . $posto_p . '\')">'.$referencia.' - '.$descricao.'</td>';
		echo "<td>$total_prod</td>";
		?>
		</tr>
		<?
	}
	?>
	<tr>
		<td><b> Valor Total:</b></td>
		<td><?php echo $valor_total?></td>
	</tr>
	</table>
	</div>
<?
}else{
	?>
		<div class="alert alert-warning"> <h4>Nenhum resultado encontrado.</h4> </div>
		<?
}
?>
</head>
