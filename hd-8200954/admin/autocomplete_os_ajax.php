<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
if(!$_GET["q"]){
	include "javascript_pesquisas.php";
	include "javascript_calendario.php";
	$cod_produto = $_GET["cod_produto"];
}else{
	// PROCESSO AUTOCOMPLETE PARA LOCALIZAÇÃO DO POSTO
	$busca = $_GET["q"];
	$busca = str_replace(" ", "", $busca);
		$sql = "SELECT
					tbl_os.consumidor_nome,
					tbl_os.consumidor_endereco,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_cpf,
					tbl_os.consumidor_numero,
					tbl_os.consumidor_estado,
					tbl_os.consumidor_cep,
					tbl_os.revenda_nome,
					tbl_os.nota_fiscal,
					tbl_os.data_nf,
					tbl_os.serie,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_produto.produto
				FROM tbl_os
					JOIN tbl_produto USING(produto)
				WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_produto.ativo
					AND tbl_os.serie = '$busca' LIMIT 1;";

		$res = pg_query($con,$sql);
		$resultado = array();
		for ($i=0; $i<pg_num_rows ($res); $i++ ){
			$referencia		= trim(pg_fetch_result($res,$i,referencia));
			$descricao		= trim(pg_fetch_result($res,$i,descricao));
			$consumidor_nome		= trim(pg_fetch_result($res,$i,consumidor_nome));
			$consumidor_endereco		= trim(pg_fetch_result($res,$i,consumidor_endereco));
			$consumidor_cidade		= trim(pg_fetch_result($res,$i,consumidor_cidade));
			$consumidor_cep		= trim(pg_fetch_result($res,$i,consumidor_cep));
			$consumidor_cpf		= trim(pg_fetch_result($res,$i,consumidor_cpf));
			$consumidor_estado		= trim(pg_fetch_result($res,$i,consumidor_estado));
			$consumidor_numero		= trim(pg_fetch_result($res,$i,consumidor_numero));
			$nota_fiscal		= trim(pg_fetch_result($res,$i,nota_fiscal));
			$revenda_nome		= trim(pg_fetch_result($res,$i,revenda_nome));
			$serie		= trim(pg_fetch_result($res,$i,serie));
			$data_nf		= trim(pg_fetch_result($res,$i,data_nf));
			$produto		= trim(pg_fetch_result($res,$i,produto));

			
				$resultado[] = "$serie|$referencia|$descricao|$consumidor_nome|$consumidor_endereco|$consumidor_cidade|
				$consumidor_cpf|$nota_fiscal|$revenda_nome|$consumidor_cep|$consumidor_estado|$consumidor_numero|$data_nf|$produto";

		}
	$resultado = implode("\n", $resultado);
	echo $resultado;
	die;
}
?>
