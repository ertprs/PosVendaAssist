<?php


include '../dbconfig.php';
include '../includes/dbconnect-inc.php';




if($_GET['q']) {


	$faturamento = $_GET['q'];

	$sqlFabrica = "SELECT DISTINCT ON (tbl_os.os)
                        tbl_os.os,
                        tbl_os.fabrica,
                        tbl_os.consumidor_celular,
                        tbl_os.consumidor_nome,
                        tbl_etiqueta_servico.etiqueta
                   FROM tbl_faturamento_item
                   JOIN tbl_os ON tbl_faturamento_item.os = tbl_os.os
                   JOIN tbl_faturamento ON tbl_faturamento.faturamento = {$faturamento}
                   JOIN tbl_embarque ON tbl_faturamento.embarque = tbl_embarque.embarque
                   JOIN tbl_etiqueta_servico ON tbl_etiqueta_servico.embarque = tbl_embarque.embarque
                   AND  tbl_etiqueta_servico.etiqueta IS NOT NULL
                   WHERE tbl_faturamento_item.faturamento = {$faturamento}
                   AND   tbl_os.fabrica IN (160)
                   AND   tbl_os.consumidor_celular IS NOT NULL
                   ";
    $resFabrica = pg_query($con, $sqlFabrica);

    if (pg_num_rows($resFabrica) > 0) {

    	include_once "../../class/sms/sms.class.php";

        while ($dados = pg_fetch_object($resFabrica)) {

            $sms = new SMS($dados->fabrica);

            $msg_sms = "OS {$dados->os} EM ANDAMENTO: A EINHELL enviou a(s) peças(s) solicitada(s) pela AUTORIZADA para reparo do seu produto. Cód.rastreio: {$dados->etiqueta}";

            $enviar  = $sms->enviarMensagem($dados->consumidor_celular,$dados->os,' ',$msg_sms);
            if($enviar == false){
                $sms->gravarSMSPendente($dados->os);
            }

        }
    }


	$headers = array("Authorization-Token:5bc00e47b1523ccfd4a05c81006d41244a77c67e078c7e3a3dc739185039e7cdf2c856cb955cff8d890a094a70f849b548d4e1bb4403fb9c4812b1c0e2646f076517c22759306d00997ad40a841544a166f3bac548a9b3987987246c274d98030f896535d6a1f89899e965fa429f0624ac95000e99af04823c1438986184feb9","User:valeria@acaciaeletro.com.br","App:AcaciaEletro","Content-Type: application/json; charset=utf-8");


	$uri = "http://api.sigecloud.com.br/request/pedidos/PesquisarNFe";


	$faturamento_array = json_encode(array('codigo'=>$faturamento));

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $uri);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$faturamento_array);
	$response = curl_exec($ch);
	$response;
	flush();
	curl_close($ch);

	$dados_response = json_decode($response,true);

//	print_r($dados_response);

	$chavenfe    = $dados_response[0]['NotaFiscalChaveAcesso'];
	$mensagem    = $dados_response[0]['NotaFiscalStatus'];
	$emissao     = date('Y-m-d',strtotime($dados_response[0]['NotaFiscalDataEmissao'])) ;
	$nota_fiscal = str_pad($dados_response[0]['NotaFiscalNumero'],6,'0',STR_PAD_LEFT);
	$recibo     = $dados_response[0]['NotaFiscalProtocoloAutorizacao'];

	if($mensagem == 'Autorizado o Uso') {

			$sql = "UPDATE tbl_faturamento set chave_nfe = '$chavenfe', status_nfe = '100', mensagem_nfe = '$mensagem',nota_fiscal = '$nota_fiscal',recibo_nfe = '$recibo', emissao = '$emissao' where faturamento = $faturamento";
			$res = pg_query($sql);
			if(!pg_last_error()) {
				echo "1";
			} else {
				echo "0";
			}
	}	
	 else {
		echo "0";
	}
//	print_r($data);
	
	die;
}
?>
