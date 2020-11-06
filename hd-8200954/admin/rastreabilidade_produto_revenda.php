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

$revenda_referencia = $_GET['cnpj'];
$data_inicio = $_GET['dti'];
$data_fim	= $_GET['dtf'];
$fornecedor_p = $_GET['fon'];
$familia_p = $_GET['fa'];
$produto_p = $_GET['pro'];
$peca_p = $_GET['pec'];
$posto_p = $_GET['pos'];

// echo "<pre>";
// print_r($_GET);
// echo "</pre>";
if (strlen($data_inicio) > 0 AND strlen($data_fim) >0 AND strlen($produto_p) > 0) {

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


	if (strlen($revenda_referencia) > 0) {
		$query_revenda = " AND tbl_revenda.cnpj = '$revenda_referencia' ";
		$join_revenda = " JOIN tbl_revenda on tbl_numero_serie.cnpj = tbl_revenda.cnpj ";
	}else{
		$query_revenda = "";
		$join_revenda = "";
	}

	if (strlen($produto_p)) {
		$query_produto = " AND tbl_numero_serie.produto in ($produto_p) ";
	}else{
		$query_produto = "";
	}

	if (strlen($peca_p)) {
	 	$query_peca = " AND tbl_ns_fornecedor.peca in ($peca_p) ";
	}else{
		$query_peca = "";
	}

	$sql_r = "	SELECT 	tbl_peca.peca,
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_ns_fornecedor.nome_fornecedor,
						count(distinct serie) as qtd_peca
						FROM tbl_numero_serie
						JOIN tbl_produto ON tbl_numero_serie.produto = tbl_produto.produto
						LEFT JOIN tbl_ns_fornecedor ON  tbl_ns_fornecedor.numero_serie = tbl_numero_serie.numero_serie
						LEFT JOIN tbl_ns_fornecedor_peca ON tbl_ns_fornecedor_peca.ns_fornecedor_peca = tbl_ns_fornecedor.ns_fornecedor_peca
						JOIN tbl_peca on tbl_ns_fornecedor.peca = tbl_peca.peca
							AND tbl_peca.fabrica = $login_fabrica
						JOIN tbl_revenda on tbl_numero_serie.cnpj = tbl_revenda.cnpj
					WHERE tbl_numero_serie.fabrica = $login_fabrica
						AND tbl_numero_serie.data_venda BETWEEN '$data_inicio' AND '$data_fim'
						$query_posto
						$query_fornecedor
						$query_familia
						$query_revenda
						$query_produto
						$query_peca
				GROUP BY 	tbl_peca.peca,
							tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_ns_fornecedor.nome_fornecedor
				ORDER BY 2,5;";
	$res_r = pg_query($con,$sql_r);



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
		<td >Peça</td>
		<td >Fornecedor</td>
		<td>Total</TD>
    </tr>
    </thead>
    <?php

    $valor_total = 0;

    for($x=0;pg_numrows($res_r)>$x;$x++){
    	$produto = pg_fetch_result($res_r, $x, peca);
    	$referencia = pg_fetch_result($res_r, $x, referencia);
    	$descricao = pg_fetch_result($res_r, $x, descricao);
    	$nome_fornecedor = pg_fetch_result($res_r, $x, nome_fornecedor);
    	$total_prod = pg_fetch_result($res_r, $x, qtd_peca);

    	$valor_total = $valor_total + $total_prod;
    	?>
    	<tr>
		<?
		echo "<td>$referencia - $descricao</td>";
		echo "<td>$nome_fornecedor</td>";
		echo "<td>$total_prod</td>";
		?>
		</tr>
		<?
	}
	?>
	<tr>
		<td colspan="2"><b> Valor Total:</b></td>
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
