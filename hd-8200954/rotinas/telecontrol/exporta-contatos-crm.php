<?php

/**
 * Exporta os dados dos postos para os contatos no CRM, do grupo Postos Autorizados. HD 909885
 * @author Brayan
 * @example Rodar no CRON: php exporta-postos-contatos.php <tipo_contato>
 */ 
include dirname(__FILE__) . '/../../dbconfig.php';
require_once dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

/**
 * Array $exclude ID das fabricas que não deverão ser exportados postos que as atendem 
 */
$include = array(1,3,101,81,72,98,45,11,35,80,99);

define('APP','Exporta postos - Contatos CRM');
define('ENV','testes');
define('WEBSERVICE', 'http://apicrm.telecontrol.com.br/crm/contato');

$vet['fabrica'] = 'telecontrol';
$vet['tipo']    = 'exporta-posto-contato';
$vet['dest']    = ENV == 'testes' ? 'brayan@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
$vet['log']     = 1;

function sendRequest($data) {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, WEBSERVICE);
	$dados = array( 'data' => json_encode ($data) );

	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('CRM: CRM-a340cec5900bbdbf93019e2d994c323349a6877b'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $dados);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );

	if ( ($result = curl_exec($ch)) == false ) {

		throw new Exception("Erro na requisição ao webservice: " . curl_error($ch) );

	}
	echo curl_error($ch);
	echo $result;
	//var_dump (curl_getinfo($ch));
}

try {

	$tipo = $argv[1];

	if (empty($tipo)) {

		throw new Exception("Passe o tipo de contato por parâmetro");

	}

	switch ($tipo) {

		case 'posto':

			$sql = "SELECT  DISTINCT tbl_posto.nome, 
						tbl_posto.email, 
						/*fone as telefone, 
						fax, */
						 
						pais, 
						19 as contato_grupo
					INTO tmp_p
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
					AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					WHERE tbl_posto.posto IN (SELECT posto
								  FROM tbl_posto_fabrica
								  WHERE fabrica not IN (20,87)
								 )
						AND tbl_posto.email IS NOT NULL AND TRIM(tbl_posto.email) <> ''
					AND tbl_posto.nome  IS NOT NULL AND TRIM(tbl_posto.nome) <> ''
					ORDER BY nome ;

			select nome,  email,  contato_grupo from tmp_p;";

		$sql = "SELECT nome, contato_email as email, 27 as contato_grupo from tmp_email_loja_virtual_mondial";
			break;

		case 'admin':
			$sql = "SELECT  DISTINCT nome ,
				        email, 
					68 as contato_grupo
					FROM tmp_e order by nome offset 0 limit 1000					";
			break;
		
		default:
			throw new Exception("Parâmetro Inválido");

	}

	$res = pg_query($con, $sql);

	if (pg_errormessage($con) || pg_num_rows($res) == 0) {

		$msg = (pg_errormessage($con)) ? pg_errormessage($con) : 'Nenhum registro encontrado';
		throw new Exception("Falha ao selecionar os postos: " . $msg);		

	}

	$data = array();

	while ( $row = pg_fetch_array($res, NULL, PGSQL_ASSOC) ) {

		if (isset($row['cnpj']))
			unset($row['cnpj']);

		$data[] = $row;

	}

	$chunked = array_chunk($data, 500);
	//echo count($data); exit;
	
	foreach ($chunked as $send) {
		sendRequest($send);
	}

} catch (Exception $e) {

	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	if (ENV == 'producao')
		Log::envia_email($vet,APP, $msg );
	else
		echo $msg;

}
	
curl_close($ch);
