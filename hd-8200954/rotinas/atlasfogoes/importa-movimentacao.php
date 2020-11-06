<?php

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

$fabrica       = "74" ;
$login_fabrica = $fabrica ;
$arquivos      = "/home/atlas/atlas-telecontrol";

function limpa_string($dados){
	$retirar = array(".", "/", "*");
	$dados = str_replace($retirar, "", $dados);
	return $dados;
}

function validaData($data){
	$data = explode('-', $data);

	if(checkdate($data[1], $data[2], $data[0])){
		return 1;
	}
	else{
		return 0;
	}
}

function formata_data_banco($data) {
	$aux_ano  = substr ($data,6,4);
	$aux_mes  = substr ($data,3,2);
	$aux_dia  = substr ($data,0,2);
	return $aux_ano."-".$aux_mes."-".$aux_dia;
}

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();


if(file_exists("$arquivos/movimentos.txt")){
	$conteudo_movimentos	= file("$arquivos/movimentos.txt");

	$sql = "DROP TABLE atlas_movimentacao";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0 ){
		$msg_erro_interno .= "$sql ";
	}

	$sql = "CREATE TABLE atlas_movimentacao (data text,titulo text,posto text,valor text,total_pago text,saldo text,tipo text,serie text,parcela text)";
	$res = pg_query($con, $sql);
	if(strlen(trim(pg_last_error($con)))>0 ){
		$msg_erro_interno .= "$sql ";
	}
	foreach ($conteudo_movimentos as $linha) {
		$valores = explode("\t", $linha);

		$data 			= trim(limpa_string($valores[0]));
		$titulo			= trim(limpa_string($valores[1]));
		$posto 			= trim(limpa_string($valores[2]));	
		$valor 			= trim(limpa_string($valores[3]));
		$total_pago		= trim(limpa_string($valores[4]));
		$saldo 			= trim(limpa_string($valores[5]));
		$tipo 			= trim(limpa_string($valores[6]));
		$serie 			= trim(limpa_string($valores[7]));
		$parcela 		= trim(limpa_string($valores[8]));

		if(validaData($data)){
			$log .= "Data informada no arquivos nуo щ vсlida - $data, $titulo, $posto, $valor, $total_pago, $saldo, $tipo, $serie, $parcela \n";
			$erro_log = "ok";
		}

		$sql_posto = "select cnpj from tbl_posto where cnpj = '$posto'";
		$res_posto = pg_query($con, $sql_posto);
		if(pg_num_rows($res_posto)==0){
			$log .= "\n O Posto $posto nуo foi encontrado. \n\n";
			$erro_log = "ok";
		}

		$data_banco = formata_data_banco($data);

		if($erro_log == ""){
			$sql = "INSERT INTO atlas_movimentacao (
				data,
				titulo,
				posto,
				valor,
				total_pago,
				saldo,
				tipo,
				serie,
				parcela
				) values (
					'$data',
					'$titulo',
					'$posto',
					'$valor',
					'$total_pago',
					'$saldo',
					'$tipo',
					'$serie',
					'$parcela'
				)";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno = "Erro ao inserir na atlas_movimentacao. ". $sql ." - ". pg_last_error($con);
			}

		

			$sql = "UPDATE atlas_movimentacao SET 
				posto        = tbl_posto.posto
				FROM tbl_posto
				WHERE atlas_movimentacao.posto = tbl_posto.cnpj
				 ";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno = "Erro ao atualizar posto na atlas_movimentacao. ". $sql ." - ". pg_last_error($con);
			}
		}
	}
	#---------------------- INSERINDO MOVIMENTAЧеES -------------

	if (empty($msg_erro_interno)) {
		$sql = "INSERT INTO tbl_movimento_financeiro 
				(fabrica,
				posto,
				titulo,
				data,
				serie,
				parcela,
				valor,
				tipo,
				total_pago,
				saldo)  
				(SELECT 
					  $fabrica,
					  posto::integer,
					  titulo,
					  data::timestamp,
					  serie,
					  parcela,
					  valor::float,
					  tipo::integer,
					  total_pago::float,
					  saldo::float 
					FROM atlas_movimentacao) ";
		$res = pg_query($con, $sql);
		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro_interno = "Erro ao atualizar posto na atlas_movimentacao. ". $sql ." - ". pg_last_error($con);
		}
	}
}
	$data = date('Y-m-d-h-s');

	##########################################################
	#               Gerando email de logs                    #
	##########################################################


	$sql = "select email_cadastros from tbl_fabrica where fabrica = $fabrica ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$para = pg_fetch_result($res, 0, 'email_cadastros');
	}


	if(!empty($log)){
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    	   	
	   	//$para = "paulos@atlas.ind.br, cicero@atlas.ind.br, alaelcio@atlas.ind.br, helpdesk@telecontrol.com.br";	

	    $assunto   = "ATLAS -Importaчуo de movimentaчуo financeira da Atlas";
		$mensagem  = "LOG - Importaчуo de movimentaчуo financeira da Atlas. \n ";
		$mensagem  .= "$log";
		mail($para, $assunto, $mensagem, $headers);
	}

	if(!empty($msg_erro_interno)){
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "jeffersons@atlas.ind.br, miguel@atlas.ind.br, alaelcio@atlas.ind.br, helpdesk@telecontrol.com.br";

	    $assunto   = "ATLAS - Erros na importaчуo de movimentaчуo financeira da Atlas";
		$mensagem  = "Erros na importaчуo de movimentaчуo financeira da Atlas. \n ";
		$mensagem  .= "$msg_erro_interno";
		mail($para, $assunto, $mensagem, $headers);
	}

$phpCron->termino();

	if (file_exists("/home/atlas/atlas-telecontrol/movimentos.txt")) {
		system("mv /home/atlas/atlas-telecontrol/movimentos.txt  /tmp/atlas/movimentos_$data.txt");
	}

?>