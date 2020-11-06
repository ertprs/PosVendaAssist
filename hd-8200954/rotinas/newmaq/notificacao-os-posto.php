<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__) . "/../../class/communicator.class.php";

// Armazena os periodos das datas que equivalem aos respectivos dias 
$data15Dias = (new DateTime('-15 days'))->format('Y-m-d');
$data20Dias = (new DateTime('-20 days'))->format('Y-m-d');
$data30Dias = (new DateTime('-30 days'))->format('Y-m-d');

// Busca todos os postos da fabrica e que tenham email para contato cadastrados
$sql = "SELECT tbl_posto.posto, tbl_posto_fabrica.contato_email 
	FROM tbl_posto_fabrica
	INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto 
	WHERE tbl_posto_fabrica.fabrica = 120 AND tbl_posto_fabrica.contato_email IS NOT NULL";

$stmt = $pdo->query($sql);
$arrPostos = $stmt->fetchAll(PDO::FETCH_OBJ);

// Percorre cada fábrica e verifica se existe OS que bate com a data de abertura dos dias acima, caso exista
// Ele monta uma tabela e manda por email para o respectivo posto
foreach ($arrPostos as $posto) {

	// Cria o sql que será usado para buscar as os's
	// Apenas OS's em aberto
	$sql = "SELECT tbl_os.os 
		FROM tbl_os
		WHERE tbl_os.fabrica = 120
		AND tbl_os.posto = :posto 
		AND tbl_os.data_abertura = :data 
		AND tbl_os.finalizada IS NULL 
		AND tbl_os.excluida IS NOT TRUE";

	$stmt = $pdo->prepare($sql);

	// Executa o sql que retorna OS's com 15 dias de abertura e armazena o resultado na variável $arr15Dias
	$stmt->execute([':data' => $data15Dias, ':posto' => $posto->posto]);
	$arr15Dias = $stmt->fetchAll(PDO::FETCH_OBJ);

	// Executa o sql que retorna OS's com 20 dias de abertura e armazena o resultado na variável $arr20Dias
	$stmt->execute([':data' => $data20Dias, ':posto' => $posto->posto]);
	$arr20Dias = $stmt->fetchAll(PDO::FETCH_OBJ);

	// Executa o sql que retorna OS's com 30 dias de abertura e armazena o resultado na variável $arr30Dias
	$stmt->execute([':data' => $data30Dias, ':posto' => $posto->posto]);
	$arr30Dias = $stmt->fetchAll(PDO::FETCH_OBJ);

	// Cria um cache da quantidade de elementos que retornaram de cada consulta
	$contador15Dias = count($arr15Dias);
	$contador20Dias = count($arr20Dias);
	$contador30Dias = count($arr30Dias);

	// Verifica se existe OS nas três condições
	// Caso não exista pula para próxima iteração
	if(empty($contador15Dias) AND empty($contador20Dias) AND empty($contador30Dias)) continue;
	
	// Verifica qual o máximo de OS possível para poder criar um número de linhas máximo
	$contador = 0;
	$contador = $contador > $contador15Dias ? $contador : $contador15Dias;
	$contador = $contador > $contador20Dias ? $contador : $contador20Dias;
	$contador = $contador > $contador30Dias ? $contador : $contador30Dias;

	// Inicia o conteúdo da tabela
	$content = "<div style='text-align: center'>
					<table class='tabela' border='1' style='border-collapse: collapse; text-align: center'>
						<tr style='background-color: #C9D7E7'>
							<th colspan='50' style='padding: 5px'> Ordem de Serviço </th>
						</tr>
						<tr>
							<th style='padding: 5px'>Aberta á 15 dias ({$contador15Dias})</th>
							<th style='padding: 5px'>Aberta á 20 dias ({$contador20Dias})</th>
							<th style='padding: 5px'>Aberta á 30 dias ({$contador30Dias})</th>
						</tr>";

			// Itera conforme a quantidade do contador pegando as os's de cada dia caso exista
			for($i = 0; $i < $contador; $i++) {
				$content .= "<tr>
						<td> {$arr15Dias[$i]->os} </td>
						<td> {$arr20Dias[$i]->os} </td>
						<td> {$arr30Dias[$i]->os} </td>
					     </tr>";
			}

	// Concatena a parte final da tabela
	$content .= 	"</table>
		    </div>";	


	// Configura o envio de email
	$tc = new TcComm('noreply@tc');
	$tc->setEmailDest( $_serverEnvironment === 'development' ? 'jpcorreia@telecontrol.com.br' : $posto->email);
	$tc->setEmailSubject('Ordem de Serviço');
	$tc->setEmailBody($content);
	$tc->sendMail();
}
