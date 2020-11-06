<?php
/**
 *
 * integracao-wevo.php
 *
 * Integração de Pagamento de Postos com Elgin através do endpoint Wevo
 *
 */

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
require dirname(__FILE__) . '/../../class/ComunicatorMirror.php';

if($_serverEnvironment == 'development'){                                                                                                                                                                                                                                  
	define('ENV', 'dev');
	define('EMAIL_LOG', 'ronald.santos@telecontrol.com.br');
	define('API_URL', 'http://homolintegracaoapi.elgin.com.br/api/Integracao/ReceberTituloPagamentoTelecontrol');
	define('API_LOGIN','integracao.elginup');
	define('API_TOKEN', 'B1nwVxPH0i4njsJYhWvQ2');
}else{
	define('ENV', 'producao');
	define('EMAIL_LOG', 'helpdesk@telecontrol.com.br');
	define('API_URL', 'https://sap-integracaoapi.elgin.com.br:2122/api/Integracao/ReceberTituloPagamentoTelecontrol');
	define('API_LOGIN','integracao.telecontrol');
	define('API_TOKEN', 'm7toeFnK06Bfcbeub770');

}

$data_sistema	= Date('Y-m-d');

$arquivo_err = "/tmp/elgin/integracao-wevo-pagamento-psotos-{$data_sistema}.err";
$arquivo_log = "/tmp/elgin/integracao-wevo-pagamento-postos{$data_sistema}.log";
system ("mkdir /tmp/elgin/ 2> /dev/null ; chmod 777 /tmp/elgin/" );

if (ENV == 'producao' ) {
	$vet['dest'] 		= 'helpdesk@telecontrol.com.br';
} else {
	$vet['dest'] 		= 'ronald.santos@telecontrol.com.br';
}


try {


	// Elgin
	$login_fabrica = 117;
	$fabrica_nome = 'elgin';

	$sql = "SELECT fn_retira_especiais(tbl_macro_linha.descricao) AS Linha,
			tbl_extrato_pagamento.data_pagamento AS Data,
			tbl_posto_fabrica.contato_estado AS Uf,
			tbl_posto.cnpj AS Cnpj,
			tbl_posto_fabrica.nomebanco AS Banco,
			tbl_posto_fabrica.agencia AS Agencia,
			tbl_posto_fabrica.conta AS Conta,
			tbl_extrato.extrato AS Extrato,
			COUNT(tbl_os_extra.os) FILTER(WHERE tbl_os_extra.extrato = tbl_extrato_pagamento.extrato) AS Qtde,
			tbl_extrato.total AS Total
		FROM tbl_extrato_pagamento
		JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
		JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_macro_linha_fabrica ON tbl_macro_linha_fabrica.linha = tbl_os_extra.linha AND tbl_macro_linha_fabrica.fabrica = {$login_fabrica}
		JOIN tbl_macro_linha ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
		WHERE tbl_extrato.fabrica = {$login_fabrica}
		AND tbl_extrato.exportado IS NULL
		GROUP BY tbl_macro_linha.descricao,
		tbl_extrato_pagamento.data_pagamento,
		tbl_posto_fabrica.contato_estado,
		tbl_posto.cnpj,
		tbl_posto_fabrica.nomebanco,
		tbl_posto_fabrica.agencia,
		tbl_posto_fabrica.conta,
		tbl_extrato.extrato,
		tbl_extrato.total";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){

		$request = array("Login" => API_LOGIN, "Token" => API_TOKEN);

		while($dados = pg_fetch_assoc($res)){
			
			$request["ExtratoPagamentoTelecontrol"][] = $dados;
			$extratos[] = $dados['extrato'];

		}
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => API_URL,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode($request),
			CURLOPT_HTTPHEADER => array(		    
				"Content-Type: application/json",		    
			),
		));

		$response = curl_exec($curl);		
		$responses[] = $response;
		$response = json_decode($response,1);

		$err = curl_error($curl);
		
		if($err != ""){
			$errors[] = $err;
		}
		
		if(array_key_exists("Message", $response) || $response['Erro'] != ""){
			$errors[] = json_encode($response);
		}

		curl_close($curl);
		
		if (count($errors) > 0) {
			$errors = implode($errors,"\n");		
			file_put_contents($arquivo_log, date("H:i:s")."\n\n".implode("<br>",$errors)."\n", FILE_APPEND);

			$communicatorMirror = new ComunicatorMirror();
			try{
				$res = $communicatorMirror->post($vet['dest'], "Erros Integração Elgin WEVO", $errors);
			}catch(Exception $e){
				echo $e->getMessage();
			}
		}else{
			$sql = "UPDATE tbl_extrato SET exportado = CURRENT_TIMESTAMP WHERE fabrica = {$login_fabrica} AND extrato IN(".implode(",",$extratos).")";
			$res = pg_query($con,$sql);
			$responses = implode($responses,"\n");
			file_put_contents($arquivo_log, date("H:i:s")."\n\n".$responses."\n", FILE_APPEND);
		}

	}
}catch(Exception $e){

	$communicatorMirror = new ComunicatorMirror();
        $res = $communicatorMirror->post($vet['dest'], "Erros Integração Elgin WEVO", $e->getMessage());
        file_put_contents($arquivo_log, date("H:i:s")."\n\n".implode("<br>",$e->getMessage())."\n", FILE_APPEND);

}
