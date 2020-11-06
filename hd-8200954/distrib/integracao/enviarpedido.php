<?php


include '../dbconfig.php';
include '../includes/dbconnect-inc.php';




if($_GET['faturamento']) {


	$faturamento = $_GET['faturamento'];
	$tipo        = $_GET['tipo'];






	if($tipo == 'P') {
		$sql = "SELECT 	faturamento as Codigo, 
		'Venda Direta' as OrigemVenda,
		 '1' as Deposito,
		 CASE WHEN tbl_faturamento.cfop like '%949' 
			then 'Pedido' 
		 else 'Pedido não Faturado' 
		END as StatusSistema, 
		CASE WHEN tbl_faturamento.cfop like '%949' 
			then 'Garantia' 
		 else 'Venda' 
		END as Categoria, 
		'ACACIAELETRO PAULISTA - EIRELI - EPP' as Empresa, 
		'' as Cliente, 
		tbl_posto.cnpj as ClienteCNPJ,
		emissao as DataEnvio, 
		'false' as Enviado, 
		'0' as NumeroParcelas, 
		'0' as ValorFrete, 
		'false' as FreteContaEmitente, 
		'0' as ValorSeguro, 
		'0' as OutrasDespesas, 
		'false' as Finalizado, 
		'false' as Lancado, 
		total_nota as ValorFinal 
		FROM tbl_faturamento left join tbl_posto using(posto) where faturamento = $faturamento ;";
	} else {

		$sql = "SELECT 	faturamento as Codigo, 
		'Venda Direta' as OrigemVenda,
		 '1' as Deposito,
		 CASE WHEN tbl_faturamento.cfop like '%949' 
			then 'Pedido' 
		 else 'Pedido não Faturado' 
		END as StatusSistema, 
		CASE WHEN tbl_faturamento.cfop like '%949' 
			then 'Garantia' 
		 else 'Venda' 
		END as Categoria, 
		'ACACIAELETRO PAULISTA - EIRELI - EPP' as Empresa, 
		'' as Cliente, 
		tbl_faturamento_destinatario.cpf_cnpj as ClienteCNPJ,
		emissao as DataEnvio, 
		'false' as Enviado, 
		'0' as NumeroParcelas, 
		'0' as ValorFrete, 
		'false' as FreteContaEmitente, 
		'0' as ValorSeguro, 
		'0' as OutrasDespesas, 
		'false' as Finalizado, 
		'false' as Lancado, 
		total_nota as ValorFinal 
		FROM tbl_faturamento left join tbl_faturamento_destinatario using(faturamento) where faturamento = $faturamento ;";
	}
	
//	echo $sql; die;

	$res = pg_query($sql);


	$data = pg_fetch_all($res);

	$data[0]['dataenvio'] = date('c',strtotime($data[0]['dataenvio']));
	foreach($data[0] as $key => $value) {
		$data[0][$key] = utf8_encode($value); 
   } 

//	echo date('c',strtotime('2014-10-15'));

//	echo "<pre>";
//	print_r($data);
//die;
 	$sqlpeca = "SELECT tbl_peca.referencia as Codigo, 'UN' as Unidade, tbl_peca.descricao as Descricao,tbl_faturamento_item.preco as ValorUnitario, sum(tbl_faturamento_item.qtde) as Quantidade, sum((tbl_faturamento_item.preco * tbl_faturamento_item.qtde)) as Valortotal from tbl_faturamento_item join tbl_peca on tbl_faturamento_item.peca = tbl_peca.peca WHERE faturamento = $faturamento group by tbl_peca.referencia, unidade, tbl_peca.descricao,tbl_faturamento_item.preco";

//	echo $sqlpeca; die; 
	$respeca = pg_query($sqlpeca);

	$datapeca = pg_fetch_all($respeca);

	foreach($datapeca as $key => $value) {
		foreach($value AS $chave => $valor){
			$valor = str_replace("\"","\\\"",$valor);
			$datapeca[$key][$chave] = utf8_encode($valor); 
		}
   } 

	$data[0]["items"] =$datapeca ;

//	print_r($data);
	$dados_envia = json_encode($data[0]); 
#	echo $dados_envia; exit;
	$headers = array("Authorization-Token:5bc00e47b1523ccfd4a05c81006d41244a77c67e078c7e3a3dc739185039e7cdf2c856cb955cff8d890a094a70f849b548d4e1bb4403fb9c4812b1c0e2646f076517c22759306d00997ad40a841544a166f3bac548a9b3987987246c274d98030f896535d6a1f89899e965fa429f0624ac95000e99af04823c1438986184feb9","User:valeria@acaciaeletro.com.br","App:AcaciaEletro","Content-Type: application/json; charset=utf-8");


	$uri = "http://api.sigecloud.com.br/request/pedidos/salvar";

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $uri);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$dados_envia);
	$response = curl_exec($ch);
	echo $response;
	flush();
	curl_close($ch);

	die;
}
?>
