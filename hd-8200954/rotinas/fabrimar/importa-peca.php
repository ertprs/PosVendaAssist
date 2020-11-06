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
    $logClass->adicionaLog(array("titulo" => "Log de erro - Importa Peça - Fabrimar")); // Titulo
    $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    $logClass->adicionaEmail("fernando.saibro@fabrimar.com.br");
    $logClass->adicionaEmail("kevin.robinson@fabrimar.com.br");
	$logClass->adicionaEmail("anderson.dutra@fabrimar.com.br");

	/*
	* Resgata o Arquivo
	*/
	$file = ($ambiente == "teste") ? file_get_contents("entrada/exemplo-importa-pecas.txt") : file_get_contents("/home/fabrimar/fabrimar-telecontrol/telecontrol-peca.txt");
	$file = explode("\n", $file);

	/* Erro */
	$msg_erro = "";

	foreach($file as $linha){

		$campos = explode(";", $linha);

		$referencia 	= $campos[0];
		$descricao 		= $campos[1];
		$unidade 		= $campos[2];
		$origem 		= $campos[3];
		$ipi 			= $campos[4];
		$ncm 			= $campos[5];
		$ativo 			= $campos[6];
		$garantia 		= $campos[7];
		$multiplo 		= $campos[8];
		$peso 			= $campos[9];
		$devolucao_obrigatoria  = $campos[10];
		$bloqueada_garantia 	= $campos[11];
		$peca_critica 			= $campos[12];
		$aguarda_inspecao 		= $campos[13];

		/*
		* Tratativas
		*/
		$descricao 				= substr($descricao,0,50);
		$garantia 				= (!empty($garantia)) 				? $garantia : 0; 
		$ativo 					= ($ativo == "t") 					? "true" : "false";
		$devolucao_obrigatoria 	= ($devolucao_obrigatoria == "t") 	? "true" : "false";
		$peca_critica		 	= ($peca_critica == "t") 			? "true" : "false";
		$bloqueada_garantia 	= ($bloqueada_garantia == "t") 		? "true" : "false";
		$aguarda_inspecao 		= ($aguarda_inspecao == "t") 		? "true" : "false";
		$ipi 					= (!empty($ipi)) 					? str_replace(",", ".", $ipi) : 0;
		$peso 					= (!empty($peso)) 					? str_replace(",", ".", $peso) : 0;
		$multiplo 				= (!empty($multiplo)) 				? $multiplo : 0;

		/*
		* Verifica se a Peça ja existe em nossa base de dados
		*/
		$sql_peca = "SELECT tbl_peca.peca FROM tbl_peca
						WHERE  tbl_peca.referencia = '$referencia'
						AND    tbl_peca.fabrica    = $fabrica";
		$query_peca = pg_query($con, $sql_peca);

		/*
		* Caso exista a Peça insere, senão atualiza em nossa base de dados
		*/
		if (pg_num_rows($query_peca) == 0) {

			$tipo_opr = "inserir";

			$sql = "INSERT INTO tbl_peca (
							fabrica,
							referencia,
							descricao,
							unidade,
							origem,
							ipi,
							ncm,
							ativo,
							garantia_diferenciada,
							multiplo,
							peso,
							devolucao_obrigatoria,
							bloqueada_garantia,
							peca_critica,
							aguarda_inspecao,
							LOCALIZACAO
						) VALUES (
							$fabrica,
							'$referencia',
							(E'$descricao'),
							'$unidade',
							'$origem',
							$ipi,
							'$ncm',
							$ativo,
							$garantia,
							$multiplo,
							$peso,
							$devolucao_obrigatoria,
							$bloqueada_garantia,
							$peca_critica,
							$aguarda_inspecao,
							'INSERT'
						);";
		} else {

			$tipo_opr = "alterar";

			$peca = pg_fetch_result($query_peca, 0, 'peca');

			$sql = "UPDATE tbl_peca SET
							descricao = (E'$descricao'),
							unidade = '$unidade',
							origem = '$origem',
							ipi = $ipi,
							ncm = '$ncm',
							ativo = $ativo,
							garantia_diferenciada = $garantia,
							multiplo = $multiplo,
							peso = $peso,
							devolucao_obrigatoria = $devolucao_obrigatoria,
							bloqueada_garantia = $bloqueada_garantia,
							peca_critica = $peca_critica,
							aguarda_inspecao = $aguarda_inspecao,
							localizacao = 'UPDATE'
						WHERE tbl_peca.peca = $peca;";
		}

		$query = pg_query($con, $sql);

		if(strlen(pg_last_error()) > 0){
			$msg_erro .= "Erro ao {$tipo_opr} a peça {$referencia} <br />";
		}

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
