<?php

error_reporting(E_ALL ^ E_NOTICE);

try{

	/*
	* Includes de arquivos necessários
	*/
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

	/*
	* Inicia com o código da Fabrimar
	*/
	$fabrica = 145;
	$ambiente = "producao"; 

	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/*
	* Log Class
	*/
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log de erro - Importa Posto Linha - Fabrimar")); // Titulo
    $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    $logClass->adicionaEmail("fernando.saibro@fabrimar.com.br");
    $logClass->adicionaEmail("kevin.robinson@fabrimar.com.br");
	$logClass->adicionaEmail("anderson.dutra@fabrimar.com.br");

	/*
	* Resgata o Arquivo
	*/
	$file = ($ambiente == "teste") ? file_get_contents("entrada/exemplo-importa-posto-lista.txt") : file_get_contents("/home/fabrimar/fabrimar-telecontrol/telecontrol-posto-lista.txt");
	$file = explode("\n", $file);

	/* Erro */
	$msg_erro = "";

	foreach($file as $linha){

		$campos = explode(";", $linha);

		$cnpj 	= substr($campos[0], 1);
		$linha 	= $campos[1];
		$tabela = $campos[2];

		/*
		* ID Tabela
		*/
		$sql_tabela = "SELECT tabela FROM tbl_tabela
							WHERE tbl_tabela.sigla_tabela = TRIM('$tabela')
							AND tbl_tabela.fabrica = $fabrica LIMIT 1";
		$query_tabela = pg_query($con, $sql_tabela);

		if (pg_num_rows($query_tabela) == 1) {
			$tabela_id = pg_fetch_result($query_tabela, 0, 'tabela');
		}

		/*
		* ID Linha
		*/
		$sql_linha = "SELECT linha FROM tbl_linha
						WHERE tbl_linha.nome ILIKE '%$linha%'
						AND tbl_linha.fabrica = $fabrica LIMIT 1";
		$query_linha = pg_query($con, $sql_linha);

		if (pg_num_rows($query_linha) == 1) {
			$linha_id = pg_fetch_result($query_linha, 0, 'linha');
		}

		/*
		* ID Posto
		*/
		$sql_posto = "SELECT tbl_posto.posto FROM tbl_posto_fabrica
						JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto.cnpj = TRIM('$cnpj')
						WHERE tbl_posto_fabrica.fabrica = $fabrica LIMIT 1";
		$query_posto = pg_query($con, $sql_posto);

		if (pg_num_rows($query_posto) == 1) {
			$posto_id = pg_fetch_result($query_posto, 0, 'posto');
		}

		if(!empty($tabela_id) && !empty($linha_id) && !empty($posto_id)){

			if (pg_num_rows($query_tabela_item) == 0) {

				$tipo_opr = "inserir";

				$sql = "INSERT INTO tbl_posto_linha (
										posto,
										tabela,
										linha
									)VALUES(
										$posto_id,
										$tabela_id,
										$linha_id
									)";
			} else {

				$tipo_opr = "alterar";

				$sql = "UPDATE tbl_posto_linha SET
								tabela = $tabela_id
							WHERE tbl_posto_linha.linha = $linha_id
							AND   tbl_posto_linha.posto = $posto";

			}

			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				$msg_erro .= "Erro ao {$tipo_opr} a Linha {$linha} para o Posto {$cnpj} <br />";
			}

		}else{

			if(!empty($cnpj)){

				if(empty($tabela_id)){
					$msg_erro .= "Não foi localizado a tabela {$tabela} para o Posto {$cnpj} <br />";
				}

				if(empty($linha_id)){
					$msg_erro .= "Não foi localizado a linha {$linha} para o Posto {$cnpj} <br />";
				}

			}

			$erro = "Verifique se a Linha e Tabela estão cadastrados no sistema Telecontrol";

			$arquivo = ($ambiente == "teste") ? fopen('entrada/posto-linha-nao-importado.txt', 'a+') : fopen("/home/fabrimar/telecontrol-fabrimar/telecontrol-posto-linha-nao-importado.txt", "+a"); 
			fwrite($arquivo, $cnpj." - ".$msg_erro." \n");
			fclose($arquivo);

		}

		if(empty($erro)){
			$arquivo = ($ambiente == "teste") ? fopen('entrada/posto-linha-importado.txt', 'a+') : fopen("/home/fabrimar/telecontrol-fabrimar/telecontrol-posto-linha-importado.txt", "+a"); 
			fwrite($arquivo, $cnpj."\n");
			fclose($arquivo);
		}

		$erro = "";

	}

	/*
	* Erro
	*/
	if(!empty($msg_erro)){

        $logClass->adicionaLog($msg_erro);

        if($logClass->enviaEmails() == "200"){
          	echo "Log de erro enviado com Sucesso!";
        }else{
          	echo $logClass->enviaEmails();
        }

    }

	/*
	* Cron Término
	*/
	$phpCron->termino();

}catch(Exception $e) {
	echo $e->getMessage();
}

?>
