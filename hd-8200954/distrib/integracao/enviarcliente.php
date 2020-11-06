<?php


include '../dbconfig.php';
include '../includes/dbconnect-inc.php';




if($_GET['faturamento']) {


	$faturamento = $_GET['faturamento'];
	$tipo = $_GET['tipo'];



	if ($tipo == 'P') {

		$sql = "SELECT tbl_posto.nome,tbl_posto.endereco FROM tbl_posto";

	} else {

		$sql = 'SELECT nome as "NomeFantasia",cpf_cnpj as "CNPJ_CPF",logradouro,numero as "LogradouroNumero",bairro,complemento,cep,municipio as "cidade",uf,fone,\'brasil\' as pais,\'1058\' as "CodigoPais",\'true\' as "Cliente", \'true\' as "PessoaFisica",tbl_ibge.cod_ibge as "CodigoMunicipio", substring(tbl_ibge.cod_ibge::text,1,2) as CodigoUF from tbl_faturamento_destinatario left join tbl_ibge on trim(tbl_ibge.cidade_pesquisa) = trim(tbl_faturamento_destinatario.municipio) and trim(tbl_faturamento_destinatario.uf) = trim(tbl_ibge.estado) where faturamento = '.$faturamento;

	}


//	echo $sql; die;

	$res = pg_query($sql);


	$data = pg_fetch_all($res);


//	echo "<pre>";
//	print_r($data);
//die;/
	//	print_r($data);
	foreach($data[0] as $key => $value) {
		$data[0][$key] = utf8_encode($value); 
   } 
	$dados_envia = json_encode($data[0]); 
 	
//	echo $dados_envia;
//	die;
	$headers = array("Authorization-Token:5bc00e47b1523ccfd4a05c81006d41244a77c67e078c7e3a3dc739185039e7cdf2c856cb955cff8d890a094a70f849b548d4e1bb4403fb9c4812b1c0e2646f076517c22759306d00997ad40a841544a166f3bac548a9b3987987246c274d98030f896535d6a1f89899e965fa429f0624ac95000e99af04823c1438986184feb9","User:valeria@acaciaeletro.com.br","App:AcaciaEletro","Content-Type: application/json; charset=utf-8");

	$uri = "http://api.sigecloud.com.br/request/pessoas/salvar";

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $uri);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$dados_envia);
	echo $dados_envia;
	$response = curl_exec($ch);
	echo $response;
	flush();
	curl_close($ch);

	die;
}
?>
