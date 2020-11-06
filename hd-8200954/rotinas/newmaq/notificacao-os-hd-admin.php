<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__) . "/../../class/communicator.class.php";

header('Content-Type: text/html; charset=UTF8');

// Armazena os periodos das datas que equivalem aos respectivos dias 
$data30Dias = (new DateTime('-30 days'))->format('Y-m-d');

// Cria o sql que será usado para buscar as os's
$sqlOS = "SELECT tbl_os.sua_os as os, tbl_os.posto, tbl_os.data_abertura, tbl_linha.nome, tbl_linha.codigo_linha
	  FROM tbl_os
	  INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
          INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = tbl_produto.fabrica_i
	  WHERE tbl_os.fabrica = 120
	  AND tbl_os.data_abertura <= '{$data30Dias}'
	  AND tbl_os.finalizada IS NULL
	  AND tbl_os.excluida IS NOT TRUE
	  ORDER BY tbl_os.data_abertura DESC";

// Executa a query
$stmt = $pdo->query($sqlOS);
$arr = $stmt->fetchAll(PDO::FETCH_OBJ);

// Ordena o array conforme o codigo_linha 
$arrFinalOS = [];
foreach($arr as $item){
	$arrFinalOS[(int) $item->codigo_linha][] = $item;
}

// Obtem os dados conforme a linha
$arrLavadoraOS = $arrFinalOS[1] ?? null; $arrFogaoOS = $arrFinalOS[2] ?? null; $arrRefrigeracaoOS = $arrFinalOS[3] ?? null;

$sqlHD = "SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado.status, tbl_hd_chamado.data, tbl_hd_chamado_extra.posto,
			   tbl_produto.produto, tbl_produto.descricao, tbl_linha.codigo_linha
		FROM tbl_hd_chamado 
		INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
		INNER JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto
		INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
		WHERE tbl_hd_chamado.fabrica = 120 
		AND   tbl_hd_chamado.fabrica_responsavel = 120 
		AND tbl_hd_chamado.status = 'Aberto'
		AND tbl_hd_chamado.data <= '{$data30Dias}'
		ORDER BY tbl_hd_chamado.data DESC";

// Executa a query
$stmt = $pdo->query($sqlHD);
$arr = $stmt->fetchAll(PDO::FETCH_OBJ);

// Ordena o array conforme o codigo_linha 
$arrFinalHD = [];
foreach($arr as $item){
	$arrFinalHD[(int) $item->codigo_linha][] = $item;
}

// Obtem os dados conforme a linha
$arrLavadoraHD = $arrFinalHD[1] ?? null; $arrFogaoHD = $arrFinalHD[2] ?? null; $arrRefrigeracaoHD = $arrFinalHD[3] ?? null;

function rendererOSToEmail($arrObj, $quantidade){
	$table = "<table class='tabela' border='1' style='border-collapse: collapse; text-align: center; margin-right: 10px'>
				<tr style='background-color: #C9D7E7'>
					<th colspan='50' style='padding: 5px'> Ordem de Serviço aberta á 30 dias ou mais ({quantidade})</th>
				</tr>
				<tr>
					<th style='padding: 5px 30px'> OS </th>
					<th style='padding: 5px 10px'> Posto </th>
					<th style='padding: 5px'> Data de Abertura </th>
					<th style='padding: 5px'> Dias em Aberto </th>
				</tr>
				{conteudo}
			 </table>";

	$tmp = ''; $dateCreate = "date_create"; $dateFormat = "date_format"; $dataHoje = new DateTime();
	foreach ($arrObj as $item) {
		$dataOS = new DateTime($item->data_abertura);
		$intervalo = $dataHoje->diff($dataOS);
	// Itera conforme a quantidade do contador pegando as os's de cada dia caso exista
		$tmp .= "<tr> 
					<td> {$item->os} </td>
					<td> {$item->posto} </td>
					<td> {$dateFormat( $dateCreate($item->data_abertura) , 'd/m/Y')} </td> 
					<td> {$intervalo->days} </td> 
				</tr>";
	}

	$parsed = str_replace('{quantidade}', $quantidade, $table);
	$parsed = str_replace('{conteudo}', $tmp, $parsed);

	return $parsed;
}

function rendererHDToEmail($arrObj, $quantidade){
	$table = "<table class='tabela' border='1' style='border-collapse: collapse; text-align: center'>
				<tr style='background-color: #C9D7E7'>
					<th colspan='50' style='padding: 5px'> Atendimento aberto á 30 dias ou mais ({quantidade})</th>
				</tr>
				<tr>
					<th style='padding: 5px'> Atendimento </th>
					<th style='padding: 5px 10px'> Posto </th>
					<th style='padding: 5px'> Data Abertura </th>
					<th style='padding: 5px'> Dias em Aberto </th>
				</tr>
				{conteudo}
			 </table>";

	$tmp = ''; $dateCreate = "date_create"; $dateFormat = "date_format"; $dataHoje = new DateTime();
	foreach ($arrObj as $item) {
		$dataHD = new DateTime($item->data);
		$intervalo = $dataHoje->diff($dataHD);
	// Itera conforme a quantidade do contador pegando as os's de cada dia caso exista
		$tmp .= "<tr> 
					<td> {$item->hd_chamado} </td>
					<td> {$item->posto} </td>
					<td> {$dateFormat( $dateCreate($item->data) , 'd/m/Y')} </td> 
					<td> {$intervalo->days} </td>
				</tr>";
	}

	$parsed = str_replace('{quantidade}', $quantidade, $table);
	$parsed = str_replace('{conteudo}', $tmp, $parsed);

	return $parsed;
}

// Configura o destino e o assunto do email que será enviado
$tc = new TcComm('noreply@tc');

// Cria a lista de emails
$listEmailLavadora = 'fernanda.cardoso@newmaq.com.br, antonio.carlos@newmaq.com.br';
$listEmailFogao = 'altieri.constancio@newup.com.br';
$listEmailRefrigeracao = 'at.agua@newup.com.br';

// Verifica se está no ambiente de desenvolvimento. Caso sim, altera a lista para email de teste
if( $_serverEnvironment === 'development' ){
	$listEmailLavadora = $listEmailFogao = $listEmailRefrigeracao = 'jpcorreia@telecontrol.com.br';
}

if( !empty($arrLavadoraOS) OR !empty($arrLavadoraHD)){

	$os = rendererOSToEmail($arrLavadoraOS, count($arrLavadoraOS));
	$hd = rendererHDToEmail($arrLavadoraHD, count($arrLavadoraHD));

	$html = "<div style='display: flex;'>
				{OS} 
				{HD}
			 </div>"; 

	$html = str_replace('{OS}', $os, $html);
	$html = str_replace('{HD}', $hd, $html);

	$tc->setEmailDest($listEmailLavadora);
	$tc->setEmailSubject('Ordem de Serviço e Atendimento [Lavadora]');
	$tc->setEmailBody( $html );
	$tc->sendMail();
}

if( !empty($arrFogaoOS) OR !empty($arrFogaoHD)){

	$os = rendererOSToEmail($arrFogaoOS, count($arrFogaoOS));
	$hd = rendererHDToEmail($arrFogaoHD, count($arrFogaoHD));

	$html = "<div style='display: flex;'>
				{OS} 
				{HD}
			</div>"; 

	$html = str_replace('{OS}', $os, $html);
	$html = str_replace('{HD}', $hd, $html);

	$tc->setEmailDest($listEmailFogao);
	$tc->setEmailSubject('Ordem de Serviço e Atendimento [Fogão]');
	$tc->setEmailBody( $html );
	$tc->sendMail();
}

if( !empty($arrRefrigeracaoOS) OR !empty($arrRefrigeracaoHD)){

	$os = rendererOSToEmail($arrRefrigeracaoOS, count($arrRefrigeracaoOS));
	$hd = rendererHDToEmail($arrRefrigeracaoHD, count($arrRefrigeracaoHD));

	$html = "<div style='display: flex;'>
				{OS} 
				{HD}
			</div>"; 

	$html = str_replace('{OS}', $os, $html);
	$html = str_replace('{HD}', $hd, $html);

	$tc->setEmailDest($listEmailRefrigeracao);
	$tc->setEmailSubject('Ordem de Serviço e Atendimento [Refrigeração]');
	$tc->setEmailBody( $html );
	$tc->sendMail();
}
