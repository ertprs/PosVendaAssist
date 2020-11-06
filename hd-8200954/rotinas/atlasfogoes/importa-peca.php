<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';


$fabrica  = 74;
$login_fabrica  = $fabrica ;
$arquivos = "/tmp/atlas";
$origem   = "/home/atlas/atlas-telecontrol";
#$origem   = "entrada";

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();

function limpa_string($dados){
	$retirar = array("-",".", "/", "*");
	$dados = str_replace($retirar, "", $dados);
	return $dados;
}

if(file_exists("$origem/peca.txt")){

	$conteudo_arquivo = file("$origem/peca.txt");

	foreach ($conteudo_arquivo as $linha) {
		$valores = explode("\t", $linha);
		
		if(sizeof($valores) > 1){

			$referencia 	= trim(limpa_string($valores[0]));
	 		$descricao		= trim(limpa_string($valores[1])); 
	 		$unidade		= trim(limpa_string($valores[2]));
	 		$ipi			= trim(limpa_string($valores[3]));
	 		$garantia_dif 	= trim(limpa_string($valores[4]));
				 		
			### VERIFICA EXISTÊNCIA DA PECA
			$sql = "SELECT tbl_peca.peca
					FROM   tbl_peca
					WHERE  tbl_peca.referencia = '$referencia'
					AND    tbl_peca.fabrica    = $fabrica";
			$result = pg_query($con, $sql);

			if(pg_num_rows($result) == 0){
				### INCLUI PECA QUE NÃO EXISTE
				$sql = "INSERT INTO tbl_peca (
							fabrica    ,
							referencia ,
							descricao  ,
							unidade    ,
							origem     ,
							ipi        ,
							garantia_diferenciada,
							controla_saldo
						)VALUES(
							'$fabrica'     ,
							'$referencia'  ,
							'$descricao'   ,
							'$unidade'     ,
							'NAC'        ,
							'$ipi'         ,
							'$garantia_dif',
							't'
						)";
				$res = pg_query($con, $sql);

				if (strlen(trim(pg_last_error($con)))>0) {
					$msg_erro_interno .= "Erro ao inserir peça $referencia. $sql " .pg_last_error($con);
				}else{
					$sql = "SELECT currval ('seq_peca')";
					$res = pg_query($con, $sql);
					$peca = pg_fetch_result($res, 0, 'peca');
					$log .= "Peça de referência $referencia já existe no banco de dados. \n\n";
				}

			}else{
				$peca = pg_fetch_result($result, 0, 'peca');
					
				$sql = "UPDATE tbl_peca SET
							descricao = $descricao   ,
							unidade   = $unidade     ,
							ipi       = $ipi         
						WHERE tbl_peca.peca = $peca ";
				$result = pg_query($con, $sql);
				if(strlen(trim(pg_last_error($con)))>0){
					$msg_erro_interno = "Erro ao atualizar peça $descricao" . pg_last_error($con);
				}else{
					$log .= "Dados da peça $referencia - $descricao atualizados";
				}
			}
		}
	}
}

	$data = date("Y-m-d");

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
	   	//$para = "jeffersons@atlas.ind.br, evandro.carlos@atlas.ind.br, helpdesk@telecontrol.com.br";
	    $assunto   = "Log do arquivo de importação de peças";
		$mensagem  = "Segue dados da importação de peças. \n ";
		$mensagem  .= "$log";
		mail($para, $assunto, $mensagem, $headers);
	}

	if(!empty($msg_erro_interno)){
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "paulos@atlas.ind.br, cicero@atlas.ind.br, alaelcio@atlas.ind.br, helpdesk@telecontrol.com.br";
	    $assunto   = "ATLAS - Erros na importação de peças";
		$mensagem  = "Segue dados da importação de peças. \n ";
		$mensagem  .= "$msg_erro_interno";
		mail($para, $assunto, $mensagem, $headers);

	}else{
		//system ("mv $origem/peca.txt $origem/bkp/peca-$data.txt");
	}

$phpCron->termino();

	if (file_exists("/home/atlas/atlas-telecontrol/peca.txt")) {
		system("mv /home/atlas/atlas-telecontrol/peca.txt  /tmp/atlas/peca_$data.txt");
	}
?>
